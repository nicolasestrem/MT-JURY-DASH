import { test, expect } from '@playwright/test';
import { JuryDashboardPage } from '../../pages/jury-dashboard-page';

test.use({
  storageState: 'tests/.auth/jury.json'
});

test.describe('Jury Member - Dashboard', () => {
  let dashboardPage: JuryDashboardPage;

  test.beforeEach(async ({ page }) => {
    dashboardPage = new JuryDashboardPage(page);
    await dashboardPage.goto();
  });

  test('should display jury dashboard', async ({ page }) => {
    // Verify dashboard is accessible
    const isAccessible = await dashboardPage.isDashboardAccessible();
    expect(isAccessible).toBeTruthy();
    
    // Verify welcome message is displayed
    const welcomeMessage = await dashboardPage.getWelcomeMessage();
    expect(welcomeMessage).toBeTruthy();
  });

  test('should display assigned candidates', async ({ page }) => {
    // Get list of assigned candidates
    const candidates = await dashboardPage.getAssignedCandidates();
    
    // Verify assignments are displayed
    if (candidates.length > 0) {
      // Verify each candidate has required information
      for (const candidate of candidates) {
        expect(candidate.name).toBeTruthy();
        expect(candidate.status).toBeTruthy();
        expect(candidate.progress).toBeTruthy();
      }
    } else {
      // Verify empty state message
      const emptyMessage = page.locator('.no-assignments, .empty-state');
      await expect(emptyMessage).toBeVisible();
    }
  });

  test('should display dashboard statistics', async ({ page }) => {
    const stats = await dashboardPage.getStats();
    
    // Verify statistics are numbers
    expect(typeof stats.total).toBe('number');
    expect(typeof stats.completed).toBe('number');
    expect(typeof stats.inProgress).toBe('number');
    expect(typeof stats.pending).toBe('number');
    
    // Verify logical consistency
    expect(stats.total).toBeGreaterThanOrEqual(0);
    expect(stats.completed + stats.inProgress + stats.pending).toBeLessThanOrEqual(stats.total + 1); // Allow for rounding
  });

  test('should filter assignments by status', async ({ page }) => {
    // Get initial candidates
    const allCandidates = await dashboardPage.getAssignedCandidates();
    
    if (allCandidates.length > 0) {
      // Filter by pending
      await dashboardPage.filterByStatus('pending');
      const pendingCandidates = await dashboardPage.getAssignedCandidates();
      
      // Verify filtered results
      for (const candidate of pendingCandidates) {
        expect(candidate.status.toLowerCase()).toContain('pending');
      }
      
      // Filter by completed
      await dashboardPage.filterByStatus('completed');
      const completedCandidates = await dashboardPage.getAssignedCandidates();
      
      // Verify filtered results
      for (const candidate of completedCandidates) {
        expect(candidate.status.toLowerCase()).toMatch(/completed|final/);
      }
    }
  });

  test('should search for specific candidate', async ({ page }) => {
    const candidates = await dashboardPage.getAssignedCandidates();
    
    if (candidates.length > 0) {
      // Search for first candidate
      const searchName = candidates[0].name.split(' ')[0];
      await dashboardPage.searchCandidate(searchName);
      
      // Verify search results
      const searchResults = await dashboardPage.getAssignedCandidates();
      
      for (const result of searchResults) {
        expect(result.name.toLowerCase()).toContain(searchName.toLowerCase());
      }
    }
  });

  test('should start new evaluation', async ({ page }) => {
    const candidates = await dashboardPage.getAssignedCandidates();
    
    // Find a pending candidate
    const pendingCandidate = candidates.find(c => 
      c.status.toLowerCase().includes('pending') || 
      c.progress === '0%'
    );
    
    if (pendingCandidate) {
      await dashboardPage.startEvaluation(pendingCandidate.name);
      
      // Verify navigation to evaluation form
      await expect(page).toHaveURL(/evaluation|evaluate/);
      
      // Verify evaluation form is loaded
      const evaluationForm = page.locator('.evaluation-form, .mt-evaluation-form');
      await expect(evaluationForm).toBeVisible();
    }
  });

  test('should resume draft evaluation', async ({ page }) => {
    const candidates = await dashboardPage.getAssignedCandidates();
    
    // Find a draft/in-progress candidate
    const draftCandidate = candidates.find(c => 
      c.status.toLowerCase().includes('draft') || 
      c.status.toLowerCase().includes('progress')
    );
    
    if (draftCandidate) {
      await dashboardPage.resumeDraftEvaluation(draftCandidate.name);
      
      // Verify navigation to evaluation form
      await expect(page).toHaveURL(/evaluation|evaluate/);
      
      // Verify form has existing data (draft)
      const evaluationForm = page.locator('.evaluation-form, .mt-evaluation-form');
      await expect(evaluationForm).toBeVisible();
      
      // Check if any scores are already filled
      const filledScores = await page.locator('input[type="number"][value]:not([value=""]), select option:checked:not([value=""])').count();
      expect(filledScores).toBeGreaterThan(0);
    }
  });

  test('should view completed evaluation', async ({ page }) => {
    const candidates = await dashboardPage.getAssignedCandidates();
    
    // Find a completed candidate
    const completedCandidate = candidates.find(c => 
      c.status.toLowerCase().includes('completed') || 
      c.progress === '100%'
    );
    
    if (completedCandidate) {
      await dashboardPage.viewEvaluationDetails(completedCandidate.name);
      
      // Verify evaluation details are displayed
      const detailsView = page.locator('.evaluation-details, .evaluation-view, .mt-evaluation-summary');
      await expect(detailsView).toBeVisible();
      
      // Verify scores are displayed (read-only)
      const scores = page.locator('.score-display, .criteria-score, .score-value');
      expect(await scores.count()).toBeGreaterThan(0);
    }
  });

  test('should display progress bars correctly', async ({ page }) => {
    const candidates = await dashboardPage.getAssignedCandidates();
    
    for (const candidate of candidates) {
      const progress = parseFloat(candidate.progress.replace('%', ''));
      
      // Verify progress is valid percentage
      expect(progress).toBeGreaterThanOrEqual(0);
      expect(progress).toBeLessThanOrEqual(100);
      
      // Verify progress correlates with status
      if (candidate.status.toLowerCase().includes('completed')) {
        expect(progress).toBe(100);
      } else if (candidate.status.toLowerCase().includes('pending')) {
        expect(progress).toBe(0);
      } else if (candidate.status.toLowerCase().includes('progress')) {
        expect(progress).toBeGreaterThan(0);
        expect(progress).toBeLessThan(100);
      }
    }
  });

  test('should handle no assignments gracefully', async ({ page }) => {
    // Search for non-existent candidate to simulate empty state
    await dashboardPage.searchCandidate('NonExistentCandidate123456');
    
    // Verify empty state is shown
    const emptyState = page.locator('.no-results, .empty-state, .no-assignments');
    await expect(emptyState).toBeVisible();
    
    // Verify helpful message is displayed
    const emptyMessage = await emptyState.textContent();
    expect(emptyMessage).toBeTruthy();
  });

  test('should display assignment counts correctly', async ({ page }) => {
    const stats = await dashboardPage.getStats();
    const counts = await dashboardPage.getAssignmentCounts();
    
    // Verify counts match statistics
    expect(counts.pending).toBeLessThanOrEqual(stats.total);
    expect(counts.inProgress).toBeLessThanOrEqual(stats.total);
    expect(counts.completed).toBeLessThanOrEqual(stats.total);
    
    // Verify total consistency
    const sumOfCounts = counts.pending + counts.inProgress + counts.completed;
    expect(sumOfCounts).toBeLessThanOrEqual(stats.total + 1); // Allow for rounding
  });

  test('should refresh dashboard data', async ({ page }) => {
    // Look for refresh button
    const refreshButton = page.locator('button:has-text("Refresh"), .refresh-btn, button[aria-label="Refresh"]');
    
    if (await refreshButton.count() > 0) {
      // Get initial data
      const initialCandidates = await dashboardPage.getAssignedCandidates();
      
      // Trigger refresh
      await refreshButton.click();
      await dashboardPage.waitForPageLoad();
      
      // Verify page reloaded
      const refreshedCandidates = await dashboardPage.getAssignedCandidates();
      
      // Data should be consistent (same structure)
      if (initialCandidates.length > 0 && refreshedCandidates.length > 0) {
        expect(refreshedCandidates[0]).toHaveProperty('name');
        expect(refreshedCandidates[0]).toHaveProperty('status');
        expect(refreshedCandidates[0]).toHaveProperty('progress');
      }
    }
  });

  test('should navigate to evaluation form from progress bar', async ({ page }) => {
    const candidates = await dashboardPage.getAssignedCandidates();
    
    // Find candidate with partial progress
    const inProgressCandidate = candidates.find(c => {
      const progress = parseFloat(c.progress.replace('%', ''));
      return progress > 0 && progress < 100;
    });
    
    if (inProgressCandidate) {
      // Click on progress bar
      const candidateRow = dashboardPage.assignmentsList.locator(
        `.assignment-item:has-text("${inProgressCandidate.name}")`
      );
      const progressBar = candidateRow.locator('.progress-bar, .mt-progress');
      
      if (await progressBar.count() > 0) {
        await progressBar.click();
        
        // Should navigate to evaluation
        await expect(page).toHaveURL(/evaluation|evaluate/);
      }
    }
  });
});