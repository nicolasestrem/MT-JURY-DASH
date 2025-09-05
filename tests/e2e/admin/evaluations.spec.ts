import { test, expect } from '@playwright/test';
import { AdminPage } from '../../pages/admin-page';

test.describe('Admin - Evaluations Management', () => {
  let adminPage: AdminPage;

  test.beforeEach(async ({ page }) => {
    adminPage = new AdminPage(page);
    await adminPage.gotoEvaluations();
  });

  test('should display evaluations dashboard', async ({ page }) => {
    // Verify page title
    await adminPage.verifyPageTitle('Evaluations');
    
    // Verify main sections are present
    const evaluationsSection = page.locator('.evaluations-section, .mt-evaluations, #evaluations');
    await expect(evaluationsSection).toBeVisible();
  });

  test('should display evaluation statistics', async ({ page }) => {
    // Look for statistics cards
    const statsCards = page.locator('.stat-card, .evaluation-stat, .mt-stat-box');
    
    if (await statsCards.count() > 0) {
      // Verify total evaluations
      const totalStat = page.locator('.total-evaluations, [data-stat="total"]');
      if (await totalStat.count() > 0) {
        const total = await totalStat.textContent();
        expect(total).toMatch(/\d+/);
      }
      
      // Verify completed evaluations
      const completedStat = page.locator('.completed-evaluations, [data-stat="completed"]');
      if (await completedStat.count() > 0) {
        const completed = await completedStat.textContent();
        expect(completed).toMatch(/\d+/);
      }
      
      // Verify pending evaluations
      const pendingStat = page.locator('.pending-evaluations, [data-stat="pending"]');
      if (await pendingStat.count() > 0) {
        const pending = await pendingStat.textContent();
        expect(pending).toMatch(/\d+/);
      }
    }
  });

  test('should display evaluations table', async ({ page }) => {
    const evaluationsTable = page.locator('.evaluations-table, table.evaluations, .wp-list-table');
    
    if (await evaluationsTable.count() > 0) {
      await expect(evaluationsTable).toBeVisible();
      
      // Verify table headers
      const headers = evaluationsTable.locator('thead th');
      const headerTexts = await headers.allTextContents();
      
      // Should include key columns
      expect(headerTexts.join(' ')).toMatch(/jury|candidate|score|status/i);
    }
  });

  test('should filter evaluations by status', async ({ page }) => {
    const statusFilter = page.locator('select[name*="status"], .filter-status, #evaluation_status');
    
    if (await statusFilter.count() > 0) {
      // Filter by completed
      await statusFilter.selectOption('completed');
      await adminPage.waitForPageLoad();
      
      // Verify filtered results
      const rows = await page.locator('.evaluation-row, tbody tr').all();
      
      for (const row of rows) {
        const statusCell = row.locator('.status, td.status, [data-label="Status"]');
        if (await statusCell.count() > 0) {
          const status = await statusCell.textContent();
          expect(status?.toLowerCase()).toContain('completed');
        }
      }
    }
  });

  test('should view evaluation details', async ({ page }) => {
    const firstRow = page.locator('.evaluation-row, tbody tr').first();
    const viewButton = firstRow.locator('a:has-text("View"), button:has-text("Details"), .view-evaluation');
    
    if (await viewButton.count() > 0) {
      await viewButton.click();
      await adminPage.waitForPageLoad();
      
      // Verify evaluation details are displayed
      const detailsSection = page.locator('.evaluation-details, .mt-evaluation-view, #evaluation-details');
      await expect(detailsSection).toBeVisible();
      
      // Verify scores are displayed
      const scores = page.locator('.score-item, .criteria-score, .mt-score');
      expect(await scores.count()).toBeGreaterThan(0);
    }
  });

  test('should display evaluation scores breakdown', async ({ page }) => {
    // Look for any evaluation with scores
    const evaluationWithScores = page.locator('.evaluation-row:has(.total-score), tr:has(.score)').first();
    
    if (await evaluationWithScores.count() > 0) {
      const viewLink = evaluationWithScores.locator('a:has-text("View"), .view-details');
      
      if (await viewLink.count() > 0) {
        await viewLink.click();
        await adminPage.waitForPageLoad();
        
        // Verify all 5 criteria scores are displayed
        const criteriaNames = [
          'Courage', 'Innovation', 'Implementation', 
          'Relevance', 'Visibility'
        ];
        
        for (const criteria of criteriaNames) {
          const scoreElement = page.locator(`.score-${criteria.toLowerCase()}, :has-text("${criteria}")`);
          await expect(scoreElement).toBeVisible();
        }
        
        // Verify total and average scores
        const totalScore = page.locator('.total-score, .score-total');
        const avgScore = page.locator('.average-score, .score-average');
        
        if (await totalScore.count() > 0) {
          const total = await totalScore.textContent();
          expect(total).toMatch(/\d+(\.\d+)?/);
        }
        
        if (await avgScore.count() > 0) {
          const avg = await avgScore.textContent();
          expect(avg).toMatch(/\d+(\.\d+)?/);
        }
      }
    }
  });

  test('should handle evaluation deletion', async ({ page }) => {
    const deleteButton = page.locator('.delete-evaluation, button:has-text("Delete")').first();
    
    if (await deleteButton.count() > 0) {
      // Store initial count
      const initialCount = await page.locator('.evaluation-row, tbody tr').count();
      
      await deleteButton.click();
      
      // Handle confirmation
      const confirmButton = page.locator('button:has-text("Confirm"), button:has-text("Yes")');
      if (await confirmButton.count() > 0) {
        await confirmButton.click();
      }
      
      await adminPage.waitForPageLoad();
      
      // Verify evaluation was deleted
      const newCount = await page.locator('.evaluation-row, tbody tr').count();
      
      if (initialCount > 0) {
        expect(newCount).toBeLessThan(initialCount);
      }
    }
  });

  test('should export evaluations data', async ({ page }) => {
    // Look for export button
    const exportButton = page.locator('a:has-text("Export Evaluations"), button:has-text("Export")');
    
    if (await exportButton.count() > 0) {
      const downloadPromise = page.waitForEvent('download');
      await exportButton.click();
      
      try {
        const download = await downloadPromise;
        expect(download.suggestedFilename()).toMatch(/evaluation.*\.csv/);
      } catch {
        // Try via export page
        await adminPage.gotoExport();
        const download = await adminPage.exportData('evaluations');
        if (download) {
          expect(download.suggestedFilename()).toMatch(/evaluation.*\.csv/);
        }
      }
    }
  });

  test('should display evaluation timeline', async ({ page }) => {
    const timeline = page.locator('.evaluation-timeline, .mt-timeline, .activity-log');
    
    if (await timeline.count() > 0) {
      await expect(timeline).toBeVisible();
      
      // Verify timeline items
      const timelineItems = timeline.locator('.timeline-item, .activity-item');
      
      if (await timelineItems.count() > 0) {
        const firstItem = timelineItems.first();
        
        // Should have timestamp
        const timestamp = firstItem.locator('.timestamp, .date, time');
        await expect(timestamp).toBeVisible();
        
        // Should have action description
        const description = firstItem.locator('.description, .action');
        await expect(description).toBeVisible();
      }
    }
  });

  test('should recalculate scores', async ({ page }) => {
    // Look for recalculate button
    const recalcButton = page.locator('button:has-text("Recalculate"), .recalc-scores');
    
    if (await recalcButton.count() > 0) {
      await recalcButton.click();
      
      // Handle confirmation if needed
      const confirmButton = page.locator('button:has-text("Confirm")');
      if (await confirmButton.count() > 0) {
        await confirmButton.click();
      }
      
      await adminPage.waitForPageLoad();
      
      // Verify success message
      const successMessage = await adminPage.getSuccessMessage();
      if (successMessage) {
        expect(successMessage).toMatch(/recalculated|updated|complete/i);
      }
    }
  });

  test('should display jury member evaluation summary', async ({ page }) => {
    // Look for jury summary section
    const jurySummary = page.locator('.jury-summary, .mt-jury-stats, .jury-evaluations');
    
    if (await jurySummary.count() > 0) {
      // Find jury member entries
      const juryEntries = jurySummary.locator('.jury-member, .jury-row');
      
      if (await juryEntries.count() > 0) {
        const firstJury = juryEntries.first();
        
        // Should display jury name
        const juryName = firstJury.locator('.jury-name, .member-name');
        await expect(juryName).toBeVisible();
        
        // Should display evaluation count
        const evalCount = firstJury.locator('.eval-count, .total-evaluations');
        const count = await evalCount.textContent();
        expect(count).toMatch(/\d+/);
      }
    }
  });

  test('should validate score ranges', async ({ page }) => {
    // View any evaluation details
    const viewButton = page.locator('a:has-text("View"), .view-evaluation').first();
    
    if (await viewButton.count() > 0) {
      await viewButton.click();
      await adminPage.waitForPageLoad();
      
      // Get all score values
      const scores = await page.locator('.score-value, .criteria-score-value').all();
      
      for (const scoreElement of scores) {
        const scoreText = await scoreElement.textContent();
        const score = parseFloat(scoreText?.replace(/[^\d.]/g, '') || '0');
        
        // Verify score is within valid range (0-10)
        expect(score).toBeGreaterThanOrEqual(0);
        expect(score).toBeLessThanOrEqual(10);
        
        // Verify score has valid increment (0.5)
        expect(score % 0.5).toBe(0);
      }
    }
  });
});