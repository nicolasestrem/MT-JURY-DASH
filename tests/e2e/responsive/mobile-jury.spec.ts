import { test, expect, devices } from '@playwright/test';
import { JuryDashboardPage } from '../../pages/jury-dashboard-page';
import { EvaluationFormPage } from '../../pages/evaluation-form-page';

// Use mobile viewport and user agent
test.use({
  ...devices['iPhone 13'],
  storageState: 'tests/.auth/jury.json'
});

test.describe('Mobile - Jury Member Experience', () => {
  let dashboardPage: JuryDashboardPage;
  let evaluationPage: EvaluationFormPage;

  test.beforeEach(async ({ page }) => {
    dashboardPage = new JuryDashboardPage(page);
    evaluationPage = new EvaluationFormPage(page);
  });

  test('should display mobile-optimized jury dashboard', async ({ page }) => {
    await dashboardPage.goto();
    
    // Verify mobile menu is visible
    const mobileMenu = page.locator('.mobile-menu, .hamburger-menu, button[aria-label="Menu"]');
    if (await mobileMenu.count() > 0) {
      await expect(mobileMenu).toBeVisible();
    }
    
    // Verify dashboard is accessible
    const isAccessible = await dashboardPage.isDashboardAccessible();
    expect(isAccessible).toBeTruthy();
    
    // Verify responsive layout
    const dashboardContainer = dashboardPage.dashboardContainer;
    const width = await dashboardContainer.evaluate(el => el.clientWidth);
    expect(width).toBeLessThanOrEqual(430); // iPhone 13 Pro Max width
  });

  test('should handle mobile navigation', async ({ page }) => {
    await dashboardPage.goto();
    
    // Check if hamburger menu exists
    const hamburger = page.locator('.hamburger, .mobile-menu-toggle, #mobile-menu-button');
    
    if (await hamburger.count() > 0) {
      // Open mobile menu
      await hamburger.click();
      
      // Verify menu is expanded
      const mobileNav = page.locator('.mobile-nav, .nav-menu, #mobile-navigation');
      await expect(mobileNav).toBeVisible();
      
      // Close menu
      const closeButton = page.locator('.close-menu, .menu-close, [aria-label="Close menu"]');
      if (await closeButton.count() > 0) {
        await closeButton.click();
        await expect(mobileNav).toBeHidden();
      }
    }
  });

  test('should display assignments in mobile-friendly format', async ({ page }) => {
    await dashboardPage.goto();
    
    const candidates = await dashboardPage.getAssignedCandidates();
    
    if (candidates.length > 0) {
      // Verify cards/list items are stacked vertically
      const assignmentItems = page.locator('.assignment-item, .candidate-card');
      const firstItem = assignmentItems.first();
      const secondItem = assignmentItems.nth(1);
      
      if (await secondItem.count() > 0) {
        const firstBox = await firstItem.boundingBox();
        const secondBox = await secondItem.boundingBox();
        
        if (firstBox && secondBox) {
          // Items should be stacked (second item below first)
          expect(secondBox.y).toBeGreaterThan(firstBox.y);
        }
      }
    }
  });

  test('should handle touch interactions', async ({ page }) => {
    await dashboardPage.goto();
    
    const candidates = await dashboardPage.getAssignedCandidates();
    
    if (candidates.length > 0) {
      // Find evaluate button
      const evaluateButton = dashboardPage.evaluateButtons.first();
      
      // Verify button is large enough for touch (minimum 44x44 pixels)
      const box = await evaluateButton.boundingBox();
      if (box) {
        expect(box.width).toBeGreaterThanOrEqual(44);
        expect(box.height).toBeGreaterThanOrEqual(44);
      }
      
      // Tap to start evaluation
      await evaluateButton.tap();
      await page.waitForLoadState('networkidle');
      
      // Should navigate to evaluation form
      await expect(page).toHaveURL(/evaluation|evaluate/);
    }
  });

  test('should display evaluation form optimized for mobile', async ({ page }) => {
    await dashboardPage.goto();
    
    const candidates = await dashboardPage.getAssignedCandidates();
    const pendingCandidate = candidates.find(c => c.status.toLowerCase().includes('pending'));
    
    if (pendingCandidate) {
      await dashboardPage.startEvaluation(pendingCandidate.name);
      await evaluationPage.waitForPageLoad();
      
      // Verify form is mobile-optimized
      const formWidth = await evaluationPage.formContainer.evaluate(el => el.clientWidth);
      expect(formWidth).toBeLessThanOrEqual(430);
      
      // Verify score inputs are touch-friendly
      const scoreInputs = page.locator('input[type="number"], select, input[type="range"]');
      const inputCount = await scoreInputs.count();
      
      for (let i = 0; i < Math.min(inputCount, 3); i++) {
        const input = scoreInputs.nth(i);
        const box = await input.boundingBox();
        
        if (box) {
          // Inputs should be large enough for touch
          expect(box.height).toBeGreaterThanOrEqual(40);
        }
      }
    }
  });

  test('should handle mobile form submission', async ({ page }) => {
    await dashboardPage.goto();
    
    const candidates = await dashboardPage.getAssignedCandidates();
    const pendingCandidate = candidates.find(c => c.status.toLowerCase().includes('pending'));
    
    if (pendingCandidate) {
      await dashboardPage.startEvaluation(pendingCandidate.name);
      await evaluationPage.waitForPageLoad();
      
      // Fill scores using mobile-friendly inputs
      const scores = {
        courage: 7,
        innovation: 8,
        implementation: 7.5,
        relevance: 8.5,
        visibility: 7
      };
      
      await evaluationPage.fillScores(scores);
      
      // Scroll to save button
      await evaluationPage.saveDraftButton.scrollIntoViewIfNeeded();
      
      // Save as draft
      await evaluationPage.saveDraftButton.tap();
      await evaluationPage.waitForAjax();
      
      // Verify success message is visible
      const successMessage = await evaluationPage.getSuccessMessage();
      expect(successMessage).toBeTruthy();
    }
  });

  test('should display mobile-optimized statistics', async ({ page }) => {
    await dashboardPage.goto();
    
    const stats = await dashboardPage.getStats();
    
    // Verify stats are displayed
    expect(stats).toBeTruthy();
    
    // Check if stats are in mobile-friendly layout
    const statsSection = dashboardPage.statsSection;
    if (await statsSection.count() > 0) {
      // Stats should be stacked or in a 2x2 grid on mobile
      const statItems = statsSection.locator('.stat-item, .stat-card');
      const itemCount = await statItems.count();
      
      if (itemCount > 1) {
        const firstItem = statItems.first();
        const secondItem = statItems.nth(1);
        
        const firstBox = await firstItem.boundingBox();
        const secondBox = await secondItem.boundingBox();
        
        if (firstBox && secondBox) {
          // Items should be either stacked or side-by-side in pairs
          const isStacked = secondBox.y > firstBox.y + firstBox.height;
          const isSideBySide = Math.abs(secondBox.y - firstBox.y) < 10;
          
          expect(isStacked || isSideBySide).toBeTruthy();
        }
      }
    }
  });

  test('should handle mobile search functionality', async ({ page }) => {
    await dashboardPage.goto();
    
    // Verify search input is accessible
    const searchInput = dashboardPage.searchInput;
    
    if (await searchInput.count() > 0) {
      // Check if search is initially hidden in mobile menu
      if (!await searchInput.isVisible()) {
        // Look for search toggle button
        const searchToggle = page.locator('.search-toggle, button[aria-label="Search"]');
        if (await searchToggle.count() > 0) {
          await searchToggle.tap();
        }
      }
      
      // Search should now be visible
      await expect(searchInput).toBeVisible();
      
      // Verify input is touch-friendly
      const box = await searchInput.boundingBox();
      if (box) {
        expect(box.height).toBeGreaterThanOrEqual(40);
      }
      
      // Perform search
      await searchInput.fill('Test');
      await searchInput.press('Enter');
      await dashboardPage.waitForPageLoad();
    }
  });

  test('should handle orientation changes', async ({ page }) => {
    await dashboardPage.goto();
    
    // Portrait orientation (default)
    let viewport = page.viewportSize();
    expect(viewport?.width).toBeLessThan(viewport?.height || 0);
    
    // Switch to landscape
    await page.setViewportSize({ width: 844, height: 390 });
    await page.waitForTimeout(500); // Wait for re-render
    
    // Verify layout adjusts
    const dashboardWidth = await dashboardPage.dashboardContainer.evaluate(el => el.clientWidth);
    expect(dashboardWidth).toBeGreaterThan(390);
    
    // Switch back to portrait
    await page.setViewportSize({ width: 390, height: 844 });
    await page.waitForTimeout(500);
    
    // Verify layout adjusts back
    const portraitWidth = await dashboardPage.dashboardContainer.evaluate(el => el.clientWidth);
    expect(portraitWidth).toBeLessThanOrEqual(390);
  });

  test('should handle mobile scrolling', async ({ page }) => {
    await dashboardPage.goto();
    
    // Get initial scroll position
    const initialScroll = await page.evaluate(() => window.scrollY);
    expect(initialScroll).toBe(0);
    
    // Scroll down
    await page.evaluate(() => window.scrollTo(0, 500));
    await page.waitForTimeout(300);
    
    const afterScroll = await page.evaluate(() => window.scrollY);
    expect(afterScroll).toBeGreaterThan(0);
    
    // Check for sticky header if present
    const header = page.locator('header, .site-header, .mobile-header');
    if (await header.count() > 0) {
      const headerPosition = await header.evaluate(el => {
        const styles = window.getComputedStyle(el);
        return styles.position;
      });
      
      // Header might be fixed or sticky on mobile
      if (headerPosition === 'fixed' || headerPosition === 'sticky') {
        await expect(header).toBeVisible();
      }
    }
  });

  test('should display mobile-friendly progress indicators', async ({ page }) => {
    await dashboardPage.goto();
    
    const candidates = await dashboardPage.getAssignedCandidates();
    
    for (const candidate of candidates.slice(0, 3)) {
      const progress = parseFloat(candidate.progress.replace('%', ''));
      
      // Find progress bar for this candidate
      const candidateElement = page.locator(`.assignment-item:has-text("${candidate.name}")`);
      const progressBar = candidateElement.locator('.progress-bar, .progress');
      
      if (await progressBar.count() > 0) {
        // Verify progress bar is visible and sized appropriately
        const box = await progressBar.boundingBox();
        if (box) {
          // Progress bar should be wide enough to be visible on mobile
          expect(box.width).toBeGreaterThan(50);
          expect(box.height).toBeGreaterThan(4);
        }
        
        // Verify progress text is readable
        const progressText = candidateElement.locator('.progress-text, .progress-value');
        if (await progressText.count() > 0) {
          const fontSize = await progressText.evaluate(el => {
            return parseFloat(window.getComputedStyle(el).fontSize);
          });
          
          // Font size should be readable on mobile (at least 12px)
          expect(fontSize).toBeGreaterThanOrEqual(12);
        }
      }
    }
  });

  test('should handle offline mode gracefully', async ({ page, context }) => {
    await dashboardPage.goto();
    
    // Go offline
    await context.setOffline(true);
    
    // Try to perform an action
    const candidates = await dashboardPage.getAssignedCandidates();
    
    if (candidates.length > 0) {
      // Try to start evaluation while offline
      const evaluateButton = dashboardPage.evaluateButtons.first();
      
      if (await evaluateButton.count() > 0) {
        await evaluateButton.tap();
        
        // Should show offline message or handle gracefully
        await page.waitForTimeout(2000);
        
        // Check for offline indicator
        const offlineMessage = page.locator('.offline-message, .connection-error, [aria-label="Offline"]');
        
        if (await offlineMessage.count() > 0) {
          await expect(offlineMessage).toBeVisible();
        }
      }
    }
    
    // Go back online
    await context.setOffline(false);
  });
});