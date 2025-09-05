import { Page, Locator } from '@playwright/test';
import { BasePage } from './base-page';

export class JuryDashboardPage extends BasePage {
  readonly dashboardContainer: Locator;
  readonly assignmentsList: Locator;
  readonly evaluateButtons: Locator;
  readonly progressBars: Locator;
  readonly statsSection: Locator;
  readonly welcomeMessage: Locator;
  readonly filterOptions: Locator;
  readonly searchInput: Locator;

  constructor(page: Page) {
    super(page);
    
    this.dashboardContainer = page.locator('.jury-dashboard, .mt-jury-dashboard, #jury-dashboard');
    this.assignmentsList = page.locator('.assignments-list, .mt-assignments, .jury-assignments');
    this.evaluateButtons = page.locator('.evaluate-btn, .btn-evaluate, a:has-text("Evaluate")');
    this.progressBars = page.locator('.progress-bar, .mt-progress, .evaluation-progress');
    this.statsSection = page.locator('.jury-stats, .dashboard-stats, .mt-stats');
    this.welcomeMessage = page.locator('.welcome-message, .jury-welcome, h1:has-text("Welcome")');
    this.filterOptions = page.locator('.filter-status, select[name="status"], .status-filter');
    this.searchInput = page.locator('.search-assignments, input[placeholder*="Search"]');
  }

  /**
   * Navigate to jury dashboard
   */
  async goto() {
    await this.page.goto('/jury-dashboard');
    await this.waitForPageLoad();
  }

  /**
   * Get list of assigned candidates
   */
  async getAssignedCandidates(): Promise<Array<{
    name: string;
    status: string;
    progress: string;
  }>> {
    const assignments = await this.assignmentsList.locator('.assignment-item, .candidate-row, tr').all();
    
    const candidates = [];
    for (const assignment of assignments) {
      const name = await assignment.locator('.candidate-name, td.name, h3').textContent();
      const status = await assignment.locator('.status, .evaluation-status, td.status').textContent();
      const progress = await assignment.locator('.progress-text, .progress-value').textContent().catch(() => '0%');
      
      if (name) {
        candidates.push({
          name: name.trim(),
          status: status?.trim() || 'pending',
          progress: progress?.trim() || '0%'
        });
      }
    }
    
    return candidates;
  }

  /**
   * Start evaluation for a candidate
   */
  async startEvaluation(candidateName: string) {
    const candidateRow = this.assignmentsList.locator(`.assignment-item:has-text("${candidateName}"), tr:has-text("${candidateName}")`);
    const evaluateBtn = candidateRow.locator('.evaluate-btn, a:has-text("Evaluate"), button:has-text("Evaluate")');
    
    await evaluateBtn.click();
    await this.waitForPageLoad();
  }

  /**
   * Get dashboard statistics
   */
  async getStats(): Promise<{
    total: number;
    completed: number;
    inProgress: number;
    pending: number;
  }> {
    const stats = {
      total: 0,
      completed: 0,
      inProgress: 0,
      pending: 0
    };
    
    // Try different selector patterns
    const totalText = await this.statsSection.locator('.total-assignments, .stat-total, [data-stat="total"]').textContent().catch(() => '0');
    const completedText = await this.statsSection.locator('.completed-assignments, .stat-completed, [data-stat="completed"]').textContent().catch(() => '0');
    const inProgressText = await this.statsSection.locator('.in-progress-assignments, .stat-progress, [data-stat="in-progress"]').textContent().catch(() => '0');
    const pendingText = await this.statsSection.locator('.pending-assignments, .stat-pending, [data-stat="pending"]').textContent().catch(() => '0');
    
    stats.total = parseInt(totalText.replace(/\D/g, '') || '0');
    stats.completed = parseInt(completedText.replace(/\D/g, '') || '0');
    stats.inProgress = parseInt(inProgressText.replace(/\D/g, '') || '0');
    stats.pending = parseInt(pendingText.replace(/\D/g, '') || '0');
    
    return stats;
  }

  /**
   * Filter assignments by status
   */
  async filterByStatus(status: 'all' | 'pending' | 'in-progress' | 'completed') {
    await this.filterOptions.selectOption(status);
    await this.waitForPageLoad();
  }

  /**
   * Search for a candidate
   */
  async searchCandidate(name: string) {
    await this.searchInput.fill(name);
    await this.searchInput.press('Enter');
    await this.waitForPageLoad();
  }

  /**
   * Check if candidate is assigned
   */
  async isCandidateAssigned(candidateName: string): Promise<boolean> {
    const candidate = this.assignmentsList.locator(`:has-text("${candidateName}")`);
    return await candidate.count() > 0;
  }

  /**
   * Get evaluation progress for a candidate
   */
  async getCandidateProgress(candidateName: string): Promise<string> {
    const candidateRow = this.assignmentsList.locator(`.assignment-item:has-text("${candidateName}"), tr:has-text("${candidateName}")`);
    const progress = await candidateRow.locator('.progress-bar, .progress-value, .evaluation-progress').getAttribute('aria-valuenow')
      || await candidateRow.locator('.progress-text').textContent();
    
    return progress?.trim() || '0';
  }

  /**
   * View evaluation details
   */
  async viewEvaluationDetails(candidateName: string) {
    const candidateRow = this.assignmentsList.locator(`.assignment-item:has-text("${candidateName}"), tr:has-text("${candidateName}")`);
    const viewBtn = candidateRow.locator('a:has-text("View"), button:has-text("View"), .view-evaluation');
    
    if (await viewBtn.count() > 0) {
      await viewBtn.click();
      await this.waitForPageLoad();
    }
  }

  /**
   * Resume draft evaluation
   */
  async resumeDraftEvaluation(candidateName: string) {
    const candidateRow = this.assignmentsList.locator(`.assignment-item:has-text("${candidateName}"), tr:has-text("${candidateName}")`);
    const resumeBtn = candidateRow.locator('a:has-text("Resume"), button:has-text("Continue"), .resume-evaluation');
    
    await resumeBtn.click();
    await this.waitForPageLoad();
  }

  /**
   * Get welcome message text
   */
  async getWelcomeMessage(): Promise<string | null> {
    return await this.welcomeMessage.textContent();
  }

  /**
   * Check if dashboard is accessible
   */
  async isDashboardAccessible(): Promise<boolean> {
    return await this.dashboardContainer.count() > 0;
  }

  /**
   * Get count of assignments by status
   */
  async getAssignmentCounts(): Promise<{
    pending: number;
    inProgress: number;
    completed: number;
  }> {
    const assignments = await this.getAssignedCandidates();
    
    return {
      pending: assignments.filter(a => a.status.toLowerCase().includes('pending')).length,
      inProgress: assignments.filter(a => a.status.toLowerCase().includes('progress') || a.status.toLowerCase().includes('draft')).length,
      completed: assignments.filter(a => a.status.toLowerCase().includes('completed') || a.status.toLowerCase().includes('final')).length
    };
  }

  /**
   * Export evaluations
   */
  async exportEvaluations() {
    const exportBtn = this.page.locator('a:has-text("Export"), button:has-text("Export"), .export-evaluations');
    
    if (await exportBtn.count() > 0) {
      const downloadPromise = this.page.waitForEvent('download');
      await exportBtn.click();
      return await downloadPromise;
    }
    
    return null;
  }
}