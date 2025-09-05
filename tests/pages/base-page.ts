import { Page, Locator, expect } from '@playwright/test';

export class BasePage {
  readonly page: Page;
  readonly adminBar: Locator;
  readonly adminMenu: Locator;
  readonly pageTitle: Locator;
  readonly notifications: Locator;
  readonly spinner: Locator;

  constructor(page: Page) {
    this.page = page;
    this.adminBar = page.locator('#wpadminbar');
    this.adminMenu = page.locator('#adminmenu');
    this.pageTitle = page.locator('.wrap > h1, .entry-title, .page-title');
    this.notifications = page.locator('.notice, .mt-notice, .alert');
    this.spinner = page.locator('.spinner, .loading, .mt-spinner');
  }

  /**
   * Navigate to a specific URL
   */
  async goto(url: string) {
    await this.page.goto(url);
    await this.waitForPageLoad();
  }

  /**
   * Navigate to WordPress admin page
   */
  async gotoAdmin(path: string = '') {
    const adminPath = process.env.WP_ADMIN_PATH || '/wp-admin';
    await this.page.goto(`${adminPath}${path}`);
    await this.waitForPageLoad();
  }

  /**
   * Wait for page to be fully loaded
   */
  async waitForPageLoad() {
    await this.page.waitForLoadState('networkidle');
    await this.waitForSpinnersToDisappear();
  }

  /**
   * Wait for all spinners to disappear
   */
  async waitForSpinnersToDisappear() {
    try {
      await this.spinner.waitFor({ state: 'visible', timeout: 1000 });
      await this.spinner.waitFor({ state: 'hidden', timeout: 30000 });
    } catch {
      // No spinner appeared, which is fine
    }
  }

  /**
   * Check if user is logged in
   */
  async isLoggedIn(): Promise<boolean> {
    return await this.page.locator('body.logged-in').count() > 0
      || await this.adminBar.count() > 0;
  }

  /**
   * Get success notification text
   */
  async getSuccessMessage(): Promise<string | null> {
    const successNotice = this.page.locator('.notice-success, .mt-success, .alert-success').first();
    if (await successNotice.count() > 0) {
      return await successNotice.textContent();
    }
    return null;
  }

  /**
   * Get error notification text
   */
  async getErrorMessage(): Promise<string | null> {
    const errorNotice = this.page.locator('.notice-error, .mt-error, .alert-danger').first();
    if (await errorNotice.count() > 0) {
      return await errorNotice.textContent();
    }
    return null;
  }

  /**
   * Dismiss all notifications
   */
  async dismissNotifications() {
    const dismissButtons = this.page.locator('.notice-dismiss, .close, .mt-dismiss');
    const count = await dismissButtons.count();
    for (let i = 0; i < count; i++) {
      await dismissButtons.nth(i).click();
    }
  }

  /**
   * Take a screenshot with a descriptive name
   */
  async screenshot(name: string) {
    await this.page.screenshot({ 
      path: `tests/reports/screenshots/${name}-${Date.now()}.png`,
      fullPage: true 
    });
  }

  /**
   * Verify page title contains expected text
   */
  async verifyPageTitle(expectedTitle: string) {
    await expect(this.pageTitle).toContainText(expectedTitle);
  }

  /**
   * Click admin menu item
   */
  async clickAdminMenuItem(menuText: string, submenuText?: string) {
    const menuItem = this.adminMenu.locator(`li:has-text("${menuText}")`);
    await menuItem.click();
    
    if (submenuText) {
      await menuItem.locator(`a:has-text("${submenuText}")`).click();
    }
    
    await this.waitForPageLoad();
  }

  /**
   * Perform search
   */
  async search(searchTerm: string) {
    const searchInput = this.page.locator('input[type="search"], #search, .search-input, .mt-search-input').first();
    await searchInput.fill(searchTerm);
    await searchInput.press('Enter');
    await this.waitForPageLoad();
  }

  /**
   * Handle WordPress AJAX request
   */
  async waitForAjax() {
    // Wait for jQuery AJAX to complete
    await this.page.waitForFunction(() => {
      return typeof jQuery !== 'undefined' ? jQuery.active === 0 : true;
    }, { timeout: 30000 });
  }

  /**
   * Verify element is visible
   */
  async verifyElementVisible(selector: string) {
    await expect(this.page.locator(selector)).toBeVisible();
  }

  /**
   * Verify element is hidden
   */
  async verifyElementHidden(selector: string) {
    await expect(this.page.locator(selector)).toBeHidden();
  }

  /**
   * Get table data as array
   */
  async getTableData(tableSelector: string): Promise<any[]> {
    return await this.page.locator(`${tableSelector} tbody tr`).evaluateAll(rows => {
      return rows.map(row => {
        const cells = row.querySelectorAll('td');
        return Array.from(cells).map(cell => cell.textContent?.trim() || '');
      });
    });
  }
}