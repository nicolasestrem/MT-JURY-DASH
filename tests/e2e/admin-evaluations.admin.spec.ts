import { test, expect } from '@playwright/test';

const ADMIN_EVALUATIONS = '/wp-admin/admin.php?page=mt-evaluations';

test.describe('Admin • Evaluations (non-destructive)', () => {
  test('filters/bulk hooks exist and details modal opens/closes', async ({ page }) => {
    await page.goto(ADMIN_EVALUATIONS, { waitUntil: 'networkidle' });

    const filterStatus = page.locator('[data-test="eval-filter-status"]');
    const bulkTop = page.locator('[data-test="eval-bulk-select-top"]');
    const viewDetails = page.locator('[data-test="eval-view-details"]').first();

    if ((await viewDetails.count()) === 0) {
      test.skip(true, 'No evaluations listed to open details');
      return;
    }

    // Verify data-test hooks
    await expect(filterStatus).toBeVisible();
    await expect(bulkTop).toBeVisible();

    // Open details modal
    await viewDetails.click();
    const modal = page.locator('[data-test="evaluation-modal"]');
    const modalBody = page.locator('[data-test="evaluation-modal-body"]');
    const closeBtn = page.locator('[data-test="evaluation-modal-close"]');

    await expect(modal).toBeVisible();
    await expect(modalBody).toBeVisible();

    // ESC should close
    await page.keyboard.press('Escape');
    await expect(modal).toBeHidden();

    // Reopen and close via close button
    await viewDetails.click();
    await expect(modal).toBeVisible();
    await closeBtn.click();
    await expect(modal).toBeHidden();
  });
});

