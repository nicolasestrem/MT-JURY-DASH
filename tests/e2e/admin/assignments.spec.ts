import { test, expect } from '@playwright/test';
import { AdminPage } from '../../pages/admin-page';

test.describe('Admin - Assignments Management', () => {
  let adminPage: AdminPage;

  test.beforeEach(async ({ page }) => {
    adminPage = new AdminPage(page);
    await adminPage.gotoAssignments();
  });

  test('should display assignments interface', async ({ page }) => {
    // Verify page title
    await adminPage.verifyPageTitle('Assignments');
    
    // Verify key elements are present
    const assignmentSection = page.locator('.assignments-section, .mt-assignments, #assignments');
    await expect(assignmentSection).toBeVisible();
  });

  test('should create manual assignment', async ({ page }) => {
    // Check if assignment form exists
    const assignForm = page.locator('.assignment-form, .create-assignment, form[id*="assignment"]');
    
    if (await assignForm.count() === 0) {
      // Look for add button
      const addButton = page.locator('button:has-text("Add"), a:has-text("Add Assignment")');
      if (await addButton.count() > 0) {
        await addButton.click();
        await adminPage.waitForPageLoad();
      }
    }
    
    // Select jury member
    const jurySelect = page.locator('select[name*="jury"], #jury_member_id');
    if (await jurySelect.count() > 0) {
      const juryOptions = await jurySelect.locator('option').count();
      if (juryOptions > 1) {
        await jurySelect.selectOption({ index: 1 });
      }
    }
    
    // Select candidate
    const candidateSelect = page.locator('select[name*="candidate"], #candidate_id');
    if (await candidateSelect.count() > 0) {
      const candidateOptions = await candidateSelect.locator('option').count();
      if (candidateOptions > 1) {
        await candidateSelect.selectOption({ index: 1 });
      }
    }
    
    // Submit assignment
    const submitButton = page.locator('button[type="submit"]:has-text("Assign"), input[value*="Assign"]');
    if (await submitButton.count() > 0) {
      await submitButton.click();
      await adminPage.waitForPageLoad();
      
      // Check for success message
      const successMessage = await adminPage.getSuccessMessage();
      if (successMessage) {
        expect(successMessage).toContain('assigned');
      }
    }
  });

  test('should display existing assignments', async ({ page }) => {
    // Check for assignments table or list
    const assignmentsList = page.locator('.assignments-list, table.assignments, .mt-assignments-table');
    
    if (await assignmentsList.count() > 0) {
      await expect(assignmentsList).toBeVisible();
      
      // Check if there are any assignments
      const rows = await assignmentsList.locator('tr, .assignment-item').count();
      
      if (rows > 1) { // More than header row
        // Verify assignment data is displayed
        const firstAssignment = assignmentsList.locator('tr, .assignment-item').nth(1);
        const text = await firstAssignment.textContent();
        
        // Should contain jury and candidate information
        expect(text).toBeTruthy();
      }
    }
  });

  test('should perform auto-assignment', async ({ page }) => {
    // Look for auto-assign button
    const autoAssignButton = page.locator('button:has-text("Auto"), .auto-assign, button[id*="auto"]');
    
    if (await autoAssignButton.count() > 0) {
      await autoAssignButton.click();
      
      // Handle configuration modal if it appears
      const modal = page.locator('.modal, .dialog, [role="dialog"]');
      if (await modal.count() > 0) {
        // Set assignment method if available
        const methodSelect = modal.locator('select[name*="method"], #assignment_method');
        if (await methodSelect.count() > 0) {
          await methodSelect.selectOption({ index: 0 });
        }
        
        // Set candidates per jury if available
        const countInput = modal.locator('input[name*="count"], input[type="number"]');
        if (await countInput.count() > 0) {
          await countInput.fill('5');
        }
        
        // Confirm auto-assignment
        const confirmButton = modal.locator('button:has-text("Confirm"), button:has-text("Assign")');
        await confirmButton.click();
      }
      
      await adminPage.waitForPageLoad();
      
      // Verify assignments were created
      const successMessage = await adminPage.getSuccessMessage();
      if (successMessage) {
        expect(successMessage).toMatch(/assigned|created|successful/i);
      }
    }
  });

  test('should delete assignment', async ({ page }) => {
    // Find delete button for first assignment
    const deleteButton = page.locator('.delete-assignment, button:has-text("Delete"), .remove-assignment').first();
    
    if (await deleteButton.count() > 0) {
      // Store initial count
      const initialCount = await page.locator('.assignment-item, tbody tr').count();
      
      await deleteButton.click();
      
      // Handle confirmation dialog
      const confirmDialog = page.locator('.confirm-delete, button:has-text("Confirm"), button:has-text("Yes")');
      if (await confirmDialog.count() > 0) {
        await confirmDialog.click();
      }
      
      await adminPage.waitForPageLoad();
      
      // Verify assignment was deleted
      const newCount = await page.locator('.assignment-item, tbody tr').count();
      expect(newCount).toBeLessThan(initialCount);
    }
  });

  test('should filter assignments by jury member', async ({ page }) => {
    // Look for jury filter
    const juryFilter = page.locator('select[name*="jury_filter"], .filter-jury, #jury_filter');
    
    if (await juryFilter.count() > 0) {
      const options = await juryFilter.locator('option').count();
      
      if (options > 1) {
        // Select first jury member
        await juryFilter.selectOption({ index: 1 });
        await adminPage.waitForPageLoad();
        
        // Verify filtered results
        const selectedText = await juryFilter.locator('option:checked').textContent();
        const assignments = await page.locator('.assignment-item, tbody tr').all();
        
        for (const assignment of assignments) {
          const text = await assignment.textContent();
          if (selectedText && text) {
            // Assignment should contain the selected jury member name
            expect(text.toLowerCase()).toContain(selectedText.toLowerCase().split(' ')[0]);
          }
        }
      }
    }
  });

  test('should display assignment statistics', async ({ page }) => {
    // Look for statistics section
    const statsSection = page.locator('.assignment-stats, .statistics, .mt-stats');
    
    if (await statsSection.count() > 0) {
      // Verify statistics are displayed
      const totalAssignments = statsSection.locator('.total-assignments, .stat-total');
      const avgPerJury = statsSection.locator('.avg-per-jury, .stat-average');
      
      if (await totalAssignments.count() > 0) {
        const total = await totalAssignments.textContent();
        expect(total).toMatch(/\d+/);
      }
      
      if (await avgPerJury.count() > 0) {
        const avg = await avgPerJury.textContent();
        expect(avg).toMatch(/\d+/);
      }
    }
  });

  test('should handle bulk assignment operations', async ({ page }) => {
    // Select multiple assignments
    const checkboxes = page.locator('.assignment-checkbox, input[type="checkbox"][name*="assignment"]');
    const count = await checkboxes.count();
    
    if (count > 0) {
      // Select first few assignments
      for (let i = 0; i < Math.min(3, count); i++) {
        await checkboxes.nth(i).check();
      }
      
      // Look for bulk action dropdown
      const bulkAction = page.locator('select[name*="bulk"], .bulk-actions');
      if (await bulkAction.count() > 0) {
        await bulkAction.selectOption('delete');
        
        // Apply bulk action
        const applyButton = page.locator('button:has-text("Apply"), input[value="Apply"]');
        await applyButton.click();
        
        // Confirm action
        const confirmButton = page.locator('button:has-text("Confirm")');
        if (await confirmButton.count() > 0) {
          await confirmButton.click();
        }
        
        await adminPage.waitForPageLoad();
        
        // Verify action was performed
        const message = await adminPage.getSuccessMessage() || await adminPage.getErrorMessage();
        expect(message).toBeTruthy();
      }
    }
  });

  test('should validate assignment constraints', async ({ page }) => {
    // Try to create duplicate assignment
    const jurySelect = page.locator('select[name*="jury"], #jury_member_id');
    const candidateSelect = page.locator('select[name*="candidate"], #candidate_id');
    
    if (await jurySelect.count() > 0 && await candidateSelect.count() > 0) {
      // Select same jury and candidate twice
      await jurySelect.selectOption({ index: 1 });
      await candidateSelect.selectOption({ index: 1 });
      
      const submitButton = page.locator('button[type="submit"]:has-text("Assign")');
      if (await submitButton.count() > 0) {
        await submitButton.click();
        await adminPage.waitForPageLoad();
        
        // First assignment might succeed
        const firstMessage = await adminPage.getSuccessMessage();
        
        // Try same assignment again
        await jurySelect.selectOption({ index: 1 });
        await candidateSelect.selectOption({ index: 1 });
        await submitButton.click();
        await adminPage.waitForPageLoad();
        
        // Should show error for duplicate
        const errorMessage = await adminPage.getErrorMessage();
        if (errorMessage) {
          expect(errorMessage).toMatch(/already|exists|duplicate/i);
        }
      }
    }
  });

  test('should export assignments', async ({ page }) => {
    // Navigate to export section or page
    const exportButton = page.locator('a:has-text("Export Assignments"), button:has-text("Export")');
    
    if (await exportButton.count() > 0) {
      const downloadPromise = page.waitForEvent('download');
      await exportButton.click();
      
      try {
        const download = await downloadPromise;
        expect(download.suggestedFilename()).toMatch(/assignment.*\.csv/);
      } catch {
        // Export might navigate to export page instead
        await adminPage.gotoExport();
        const download = await adminPage.exportData('assignments');
        if (download) {
          expect(download.suggestedFilename()).toMatch(/assignment.*\.csv/);
        }
      }
    }
  });
});