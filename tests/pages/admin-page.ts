import { Page, Locator } from '@playwright/test';
import { BasePage } from './base-page';

export class AdminPage extends BasePage {
  readonly candidatesMenu: Locator;
  readonly addNewButton: Locator;
  readonly bulkActionsDropdown: Locator;
  readonly applyButton: Locator;
  readonly exportButton: Locator;
  readonly importButton: Locator;
  readonly itemsTable: Locator;
  readonly checkAll: Locator;
  readonly searchBox: Locator;
  readonly filterDropdown: Locator;
  readonly debugCenterLink: Locator;
  readonly dashboardWidgets: Locator;

  constructor(page: Page) {
    super(page);
    
    // Menu items
    this.candidatesMenu = page.locator('#adminmenu a:has-text("Candidates")');
    
    // Common admin elements
    this.addNewButton = page.locator('.page-title-action:has-text("Add New"), .add-new-h2');
    this.bulkActionsDropdown = page.locator('#bulk-action-selector-top, select[name="action"]');
    this.applyButton = page.locator('#doaction, input[type="submit"][value="Apply"]');
    this.exportButton = page.locator('button:has-text("Export"), a:has-text("Export"), .export-btn');
    this.importButton = page.locator('button:has-text("Import"), a:has-text("Import"), .import-btn');
    
    // Table elements
    this.itemsTable = page.locator('.wp-list-table, .mt-table, table.candidates-table');
    this.checkAll = page.locator('#cb-select-all-1, .check-all, input[type="checkbox"][id*="select-all"]');
    
    // Search and filter
    this.searchBox = page.locator('#post-search-input, .search-box input[type="search"]');
    this.filterDropdown = page.locator('select.postform, .filter-dropdown, select[name="filter"]');
    
    // MT specific
    this.debugCenterLink = page.locator('a:has-text("Debug Center")');
    this.dashboardWidgets = page.locator('.postbox, .dashboard-widget');
  }

  /**
   * Navigate to MT Award System admin page
   */
  async gotoMTAdmin(subpage: string = '') {
    const mtSlug = process.env.MT_ADMIN_MENU_SLUG || 'mt-award-system';
    await this.gotoAdmin(`/admin.php?page=${mtSlug}${subpage ? `&tab=${subpage}` : ''}`);
  }

  /**
   * Navigate to Candidates page
   */
  async gotoCandidates() {
    await this.gotoAdmin('/admin.php?page=mt-candidates');
  }

  /**
   * Navigate to Jury Members page
   */
  async gotoJuryMembers() {
    await this.gotoAdmin('/edit.php?post_type=mt_jury_member');
  }

  /**
   * Navigate to Assignments page
   */
  async gotoAssignments() {
    await this.gotoAdmin('/admin.php?page=mt-assignments');
  }

  /**
   * Navigate to Evaluations page
   */
  async gotoEvaluations() {
    await this.gotoAdmin('/admin.php?page=mt-evaluations');
  }

  /**
   * Navigate to Coaching page
   */
  async gotoCoaching() {
    await this.gotoAdmin('/admin.php?page=mt-coaching');
  }

  /**
   * Navigate to Rankings page
   */
  async gotoRankings() {
    await this.gotoMTAdmin('rankings');
  }

  /**
   * Navigate to Export page
   */
  async gotoExport() {
    await this.gotoMTAdmin('export');
  }

  /**
   * Navigate to Debug Center
   */
  async gotoDebugCenter() {
    await this.gotoMTAdmin('debug');
  }

  /**
   * Select bulk action
   */
  async selectBulkAction(action: string) {
    await this.bulkActionsDropdown.selectOption(action);
  }

  /**
   * Apply bulk action to selected items
   */
  async applyBulkAction() {
    await this.applyButton.click();
    await this.waitForPageLoad();
  }

  /**
   * Select all items in table
   */
  async selectAllItems() {
    await this.checkAll.check();
  }

  /**
   * Select specific items by text
   */
  async selectItemsByText(texts: string[]) {
    for (const text of texts) {
      const row = this.page.locator(`tr:has-text("${text}")`);
      const checkbox = row.locator('input[type="checkbox"]').first();
      await checkbox.check();
    }
  }

  /**
   * Export data
   */
  async exportData(type: 'candidates' | 'evaluations' | 'assignments') {
    const exportLink = this.page.locator(`a[href*="export_${type}"], button[data-export="${type}"]`);
    
    // Start waiting for download before clicking
    const downloadPromise = this.page.waitForEvent('download');
    await exportLink.click();
    
    const download = await downloadPromise;
    return download;
  }

  /**
   * Get dashboard widget data
   */
  async getDashboardWidgetData(widgetTitle: string): Promise<any> {
    const widget = this.dashboardWidgets.filter({ hasText: widgetTitle });
    
    if (await widget.count() === 0) {
      return null;
    }
    
    return await widget.evaluate(el => {
      const data: any = {};
      
      // Get widget stats
      const stats = el.querySelectorAll('.stat-value, .widget-value, .count');
      stats.forEach((stat, index) => {
        data[`stat_${index}`] = stat.textContent?.trim();
      });
      
      // Get widget lists
      const listItems = el.querySelectorAll('li, .list-item');
      data.items = Array.from(listItems).map(item => item.textContent?.trim());
      
      return data;
    });
  }

  /**
   * Create new assignment
   */
  async createAssignment(juryMember: string, candidateName: string) {
    // This would interact with the assignment creation interface
    await this.gotoAssignments();
    
    // Click add new or find the assignment interface
    const addButton = this.page.locator('button:has-text("Add Assignment"), .add-assignment');
    if (await addButton.count() > 0) {
      await addButton.click();
    }
    
    // Select jury member
    const jurySelect = this.page.locator('select[name*="jury"], #jury_member_id');
    await jurySelect.selectOption({ label: juryMember });
    
    // Select candidate
    const candidateSelect = this.page.locator('select[name*="candidate"], #candidate_id');
    await candidateSelect.selectOption({ label: candidateName });
    
    // Submit
    const submitButton = this.page.locator('button[type="submit"]:has-text("Assign"), input[type="submit"][value*="Assign"]');
    await submitButton.click();
    
    await this.waitForPageLoad();
  }

  /**
   * Auto-assign candidates
   */
  async autoAssignCandidates(options: { method?: string; count?: number } = {}) {
    await this.gotoAssignments();
    
    const autoAssignButton = this.page.locator('button:has-text("Auto-Assign"), .auto-assign-btn');
    await autoAssignButton.click();
    
    // Handle modal or options if they appear
    if (options.method) {
      const methodSelect = this.page.locator('select[name="assignment_method"]');
      if (await methodSelect.count() > 0) {
        await methodSelect.selectOption(options.method);
      }
    }
    
    if (options.count) {
      const countInput = this.page.locator('input[name="candidates_per_jury"]');
      if (await countInput.count() > 0) {
        await countInput.fill(options.count.toString());
      }
    }
    
    // Confirm auto-assignment
    const confirmButton = this.page.locator('button:has-text("Confirm"), .confirm-auto-assign');
    if (await confirmButton.count() > 0) {
      await confirmButton.click();
    }
    
    await this.waitForPageLoad();
  }

  /**
   * Search for items
   */
  async searchItems(searchTerm: string) {
    await this.searchBox.fill(searchTerm);
    await this.searchBox.press('Enter');
    await this.waitForPageLoad();
  }

  /**
   * Filter items
   */
  async filterItems(filterValue: string) {
    await this.filterDropdown.selectOption(filterValue);
    await this.waitForPageLoad();
  }

  /**
   * Get table row count
   */
  async getTableRowCount(): Promise<number> {
    return await this.itemsTable.locator('tbody tr').count();
  }

  /**
   * Verify candidate exists in table
   */
  async verifyCandidateExists(candidateName: string): Promise<boolean> {
    const row = this.itemsTable.locator(`tr:has-text("${candidateName}")`);
    return await row.count() > 0;
  }

  /**
   * Get evaluation statistics
   */
  async getEvaluationStats(): Promise<any> {
    await this.gotoEvaluations();
    
    return {
      total: await this.page.locator('.total-evaluations, .stat-total').textContent(),
      completed: await this.page.locator('.completed-evaluations, .stat-completed').textContent(),
      pending: await this.page.locator('.pending-evaluations, .stat-pending').textContent(),
      draft: await this.page.locator('.draft-evaluations, .stat-draft').textContent()
    };
  }
}