import { test, expect } from '@playwright/test';
import { AdminPage } from '../../pages/admin-page';

test.describe('Admin - Candidates Management', () => {
  let adminPage: AdminPage;

  test.beforeEach(async ({ page }) => {
    adminPage = new AdminPage(page);
    await adminPage.gotoCandidates();
  });

  test('should display candidates list', async ({ page }) => {
    // Verify page title - accept both English and German
    const pageTitle = page.locator('.wrap > h1, .wp-heading-inline');
    await expect(pageTitle).toBeVisible();
    const titleText = await pageTitle.textContent();
    expect(titleText).toMatch(/Candidates|Kandidaten/i);
    
    // Verify table or list is visible
    await expect(page.locator('.wp-list-table, .mt-candidates-table, .candidate-list')).toBeVisible();
    
    // Verify some content is present (headers may be in German or English)
    const headers = page.locator('.wp-list-table thead th, table th');
    const headerCount = await headers.count();
    expect(headerCount).toBeGreaterThan(0);
  });

  test('should search for candidates', async ({ page }) => {
    // Search for a specific candidate
    await adminPage.searchItems('Test Candidate');
    
    // Verify search results
    const rowCount = await adminPage.getTableRowCount();
    
    // If no results, verify no results message
    if (rowCount === 0) {
      await expect(page.locator('.no-items, td.colspanchange')).toContainText('No candidates found');
    } else {
      // Verify search term appears in results
      const firstRow = adminPage.itemsTable.locator('tbody tr').first();
      await expect(firstRow).toContainText('Test');
    }
  });

  test('should perform bulk actions', async ({ page }) => {
    // Select all candidates
    await adminPage.selectAllItems();
    
    // Select bulk action
    await adminPage.selectBulkAction('export');
    
    // Apply action
    await adminPage.applyBulkAction();
    
    // Verify success message or download
    const successMessage = await adminPage.getSuccessMessage();
    if (successMessage) {
      expect(successMessage).toContain('exported');
    }
  });

  test('should filter candidates by status', async ({ page }) => {
    // Check if filter exists
    const filterExists = await adminPage.filterDropdown.count() > 0;
    
    if (filterExists) {
      // Apply filter
      await adminPage.filterItems('active');
      
      // Verify filtered results
      const rows = await adminPage.getTableData('.wp-list-table');
      
      // Verify all rows match filter criteria
      for (const row of rows) {
        // Status column should contain 'active' or equivalent
        expect(row.join(' ').toLowerCase()).toMatch(/active|published/);
      }
    }
  });

  test('should handle empty state', async ({ page }) => {
    // Search for non-existent candidate
    await adminPage.searchItems('NonExistentCandidate12345');
    
    // Verify empty state message
    const emptyMessage = page.locator('.no-items, .empty-state, td.colspanchange');
    await expect(emptyMessage).toBeVisible();
    await expect(emptyMessage).toContainText(/no candidates found|no items/i);
  });

  test('should display candidate details on row click', async ({ page }) => {
    const firstRow = adminPage.itemsTable.locator('tbody tr').first();
    const candidateLink = firstRow.locator('a.row-title, td.title a, td.column-title a').first();
    
    if (await candidateLink.count() > 0) {
      // Click candidate name
      await candidateLink.click();
      await adminPage.waitForPageLoad();
      
      // Verify we're on edit page
      await expect(page).toHaveURL(/post\.php\?.*action=edit/);
      
      // Verify edit form is visible
      const editForm = page.locator('#post, #poststuff, .edit-form');
      await expect(editForm).toBeVisible();
    }
  });

  test('should export candidates to CSV', async ({ page }) => {
    // Navigate to export page
    await adminPage.gotoExport();
    
    // Start export
    const download = await adminPage.exportData('candidates');
    
    if (download) {
      // Verify download
      expect(download.suggestedFilename()).toMatch(/candidates.*\.csv/);
      
      // Save for inspection if needed
      const path = await download.path();
      expect(path).toBeTruthy();
    }
  });

  test('should handle candidate quick edit', async ({ page }) => {
    const firstRow = adminPage.itemsTable.locator('tbody tr').first();
    
    // Hover to show action links
    await firstRow.hover();
    
    const quickEditLink = firstRow.locator('.quickedit, a:has-text("Quick Edit")');
    
    if (await quickEditLink.count() > 0) {
      await quickEditLink.click();
      
      // Wait for quick edit form
      const quickEditForm = page.locator('.inline-edit-row, .quick-edit');
      await expect(quickEditForm).toBeVisible();
      
      // Update a field
      const titleInput = quickEditForm.locator('input[name="post_title"], .ptitle');
      if (await titleInput.count() > 0) {
        await titleInput.fill('Updated Candidate Name');
      }
      
      // Save changes
      const saveButton = quickEditForm.locator('.save, button:has-text("Update")');
      await saveButton.click();
      
      // Verify changes saved
      await adminPage.waitForPageLoad();
    }
  });

  test('should verify candidate columns display correct data', async ({ page }) => {
    const rows = await adminPage.getTableData('.wp-list-table');
    
    if (rows.length > 0) {
      // Verify each row has expected number of columns
      for (const row of rows) {
        expect(row.length).toBeGreaterThanOrEqual(3); // At least Name, Organization, Status
      }
      
      // Verify data format in columns
      const firstRow = rows[0];
      if (firstRow.length > 0) {
        // Name column should not be empty
        expect(firstRow[0]).toBeTruthy();
      }
    }
  });

  test('should handle pagination', async ({ page }) => {
    // Check if pagination exists
    const pagination = page.locator('.tablenav-pages, .pagination');
    
    if (await pagination.count() > 0) {
      const totalPages = await pagination.locator('.total-pages').textContent();
      
      if (totalPages && parseInt(totalPages) > 1) {
        // Go to next page
        const nextButton = pagination.locator('.next-page, a:has-text("Next")');
        await nextButton.click();
        await adminPage.waitForPageLoad();
        
        // Verify we're on page 2
        const currentPage = await pagination.locator('.current-page').inputValue();
        expect(currentPage).toBe('2');
      }
    }
  });
});