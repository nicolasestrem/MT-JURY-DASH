import { test, expect } from '@playwright/test';
import { AdminPage } from '../../pages/admin-page';
import { JuryDashboardPage } from '../../pages/jury-dashboard-page';
import { EvaluationFormPage } from '../../pages/evaluation-form-page';
import path from 'path';

// Visual regression tests compare screenshots against baseline images
test.describe('Visual Regression Tests', () => {
  
  test.describe('Admin Pages', () => {
    test.use({
      storageState: 'tests/.auth/admin.json'
    });
    
    test('admin dashboard visual test', async ({ page }) => {
      const adminPage = new AdminPage(page);
      await adminPage.gotoMTAdmin();
      
      // Wait for dynamic content to load
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(1000);
      
      // Take screenshot
      await expect(page).toHaveScreenshot('admin-dashboard.png', {
        fullPage: true,
        animations: 'disabled',
        mask: [page.locator('.date-time, .timestamp')], // Mask dynamic elements
        threshold: 0.2 // 20% difference threshold
      });
    });
    
    test('candidates list visual test', async ({ page }) => {
      const adminPage = new AdminPage(page);
      await adminPage.gotoCandidates();
      
      await page.waitForLoadState('networkidle');
      
      await expect(page).toHaveScreenshot('candidates-list.png', {
        fullPage: false, // Just viewport
        animations: 'disabled',
        mask: [
          page.locator('.updated, .date'),
          page.locator('.notice') // Mask notifications
        ]
      });
    });
    
    test('assignments page visual test', async ({ page }) => {
      const adminPage = new AdminPage(page);
      await adminPage.gotoAssignments();
      
      await page.waitForLoadState('networkidle');
      
      await expect(page).toHaveScreenshot('assignments-page.png', {
        fullPage: true,
        animations: 'disabled'
      });
    });
    
    test('evaluations page visual test', async ({ page }) => {
      const adminPage = new AdminPage(page);
      await adminPage.gotoEvaluations();
      
      await page.waitForLoadState('networkidle');
      
      await expect(page).toHaveScreenshot('evaluations-page.png', {
        fullPage: true,
        animations: 'disabled',
        mask: [page.locator('.timestamp, .date-submitted')]
      });
    });
    
    test('rankings page visual test', async ({ page }) => {
      const adminPage = new AdminPage(page);
      await adminPage.gotoRankings();
      
      await page.waitForLoadState('networkidle');
      
      await expect(page).toHaveScreenshot('rankings-page.png', {
        fullPage: true,
        animations: 'disabled'
      });
    });
  });
  
  test.describe('Jury Member Pages', () => {
    test.use({
      storageState: 'tests/.auth/jury.json'
    });
    
    test('jury dashboard visual test', async ({ page }) => {
      const dashboardPage = new JuryDashboardPage(page);
      await dashboardPage.goto();
      
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(1000);
      
      await expect(page).toHaveScreenshot('jury-dashboard.png', {
        fullPage: true,
        animations: 'disabled',
        mask: [page.locator('.welcome-message')] // May contain dynamic name
      });
    });
    
    test('evaluation form visual test', async ({ page }) => {
      const dashboardPage = new JuryDashboardPage(page);
      const evaluationPage = new EvaluationFormPage(page);
      
      await dashboardPage.goto();
      
      // Find a candidate to evaluate
      const candidates = await dashboardPage.getAssignedCandidates();
      if (candidates.length > 0) {
        await dashboardPage.startEvaluation(candidates[0].name);
        
        await page.waitForLoadState('networkidle');
        
        await expect(page).toHaveScreenshot('evaluation-form.png', {
          fullPage: true,
          animations: 'disabled'
        });
      }
    });
  });
  
  test.describe('Component Visual Tests', () => {
    test.use({
      storageState: 'tests/.auth/admin.json'
    });
    
    test('dashboard widgets visual test', async ({ page }) => {
      const adminPage = new AdminPage(page);
      await adminPage.gotoMTAdmin();
      
      await page.waitForLoadState('networkidle');
      
      // Capture each widget
      const widgets = await page.locator('.postbox, .dashboard-widget').all();
      
      for (let i = 0; i < Math.min(widgets.length, 5); i++) {
        await expect(widgets[i]).toHaveScreenshot(`widget-${i}.png`, {
          animations: 'disabled'
        });
      }
    });
    
    test('table components visual test', async ({ page }) => {
      const adminPage = new AdminPage(page);
      await adminPage.gotoCandidates();
      
      await page.waitForLoadState('networkidle');
      
      const table = page.locator('.wp-list-table');
      if (await table.count() > 0) {
        await expect(table).toHaveScreenshot('candidates-table.png', {
          animations: 'disabled'
        });
      }
    });
    
    test('form elements visual test', async ({ page }) => {
      const dashboardPage = new JuryDashboardPage(page);
      
      // Navigate to evaluation form
      await dashboardPage.goto();
      const candidates = await dashboardPage.getAssignedCandidates();
      
      if (candidates.length > 0) {
        await dashboardPage.startEvaluation(candidates[0].name);
        await page.waitForLoadState('networkidle');
        
        // Capture score inputs section
        const scoreSection = page.locator('.score-inputs, .criteria-scores, .evaluation-criteria').first();
        if (await scoreSection.count() > 0) {
          await expect(scoreSection).toHaveScreenshot('score-inputs.png', {
            animations: 'disabled'
          });
        }
        
        // Capture comment section
        const commentSection = page.locator('.comment-section, .evaluation-comments').first();
        if (await commentSection.count() > 0) {
          await expect(commentSection).toHaveScreenshot('comment-section.png', {
            animations: 'disabled'
          });
        }
      }
    });
  });
  
  test.describe('Responsive Visual Tests', () => {
    const viewports = [
      { name: 'mobile', width: 375, height: 812 },
      { name: 'tablet', width: 768, height: 1024 },
      { name: 'desktop', width: 1920, height: 1080 }
    ];
    
    for (const viewport of viewports) {
      test(`admin dashboard - ${viewport.name}`, async ({ page }) => {
        await page.setViewportSize({ width: viewport.width, height: viewport.height });
        
        const adminPage = new AdminPage(page);
        await adminPage.gotoMTAdmin();
        
        await page.waitForLoadState('networkidle');
        
        await expect(page).toHaveScreenshot(`admin-dashboard-${viewport.name}.png`, {
          fullPage: false,
          animations: 'disabled'
        });
      });
      
      test(`jury dashboard - ${viewport.name}`, async ({ page }) => {
        await page.setViewportSize({ width: viewport.width, height: viewport.height });
        
        const dashboardPage = new JuryDashboardPage(page);
        await dashboardPage.goto();
        
        await page.waitForLoadState('networkidle');
        
        await expect(page).toHaveScreenshot(`jury-dashboard-${viewport.name}.png`, {
          fullPage: false,
          animations: 'disabled'
        });
      });
    }
  });
  
  test.describe('State-based Visual Tests', () => {
    test.use({
      storageState: 'tests/.auth/admin.json'
    });
    
    test('empty state visual test', async ({ page }) => {
      const adminPage = new AdminPage(page);
      await adminPage.gotoCandidates();
      
      // Search for non-existent item to trigger empty state
      await adminPage.searchItems('NonExistentCandidate12345');
      await page.waitForLoadState('networkidle');
      
      const emptyState = page.locator('.no-items, .empty-state');
      if (await emptyState.count() > 0) {
        await expect(emptyState).toHaveScreenshot('empty-state.png', {
          animations: 'disabled'
        });
      }
    });
    
    test('loading state visual test', async ({ page }) => {
      // Slow down network to capture loading states
      await page.route('**/*', route => {
        setTimeout(() => route.continue(), 1000);
      });
      
      const adminPage = new AdminPage(page);
      const navigationPromise = adminPage.gotoCandidates();
      
      // Capture loading state quickly
      await page.waitForTimeout(100);
      
      const spinner = page.locator('.spinner, .loading');
      if (await spinner.count() > 0 && await spinner.isVisible()) {
        await expect(page).toHaveScreenshot('loading-state.png', {
          animations: 'disabled'
        });
      }
      
      await navigationPromise;
    });
    
    test('error state visual test', async ({ page }) => {
      // Mock an error response
      await page.route('**/wp-admin/admin-ajax.php', route => {
        route.fulfill({
          status: 500,
          body: 'Internal Server Error'
        });
      });
      
      const adminPage = new AdminPage(page);
      await adminPage.gotoMTAdmin();
      
      // Trigger an action that would cause an error
      const actionButton = page.locator('button').first();
      if (await actionButton.count() > 0) {
        await actionButton.click({ force: true }).catch(() => {});
      }
      
      await page.waitForTimeout(1000);
      
      const errorMessage = page.locator('.error, .notice-error, .alert-danger');
      if (await errorMessage.count() > 0) {
        await expect(errorMessage).toHaveScreenshot('error-state.png', {
          animations: 'disabled'
        });
      }
    });
  });
  
  test.describe('Update Visual Baselines', () => {
    test.skip('update all baselines', async ({ page }) => {
      // This test is skipped by default
      // Run with --update-snapshots flag to update all baseline images
      // npm test -- --update-snapshots
      
      console.log('To update visual baselines, run:');
      console.log('npx playwright test --update-snapshots');
    });
  });
});