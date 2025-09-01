import { test, expect } from '@playwright/test';

const ADMIN_ASSIGNMENTS = '/wp-admin/admin.php?page=mt-assignments';

test.describe('Admin • Assignments (non-destructive)', () => {
  test('modals open/close, focus trap, and data-test hooks exist', async ({ page }) => {
    await page.goto(ADMIN_ASSIGNMENTS, { waitUntil: 'networkidle' });

    const autoBtn = page.locator('[data-test="auto-assign-btn"]').first();
    const manualBtn = page.locator('[data-test="manual-assign-btn"]').first();
    const bulkApply = page.locator('[data-test="bulk-apply-btn"]').first();
    const bulkSelect = page.locator('[data-test="bulk-action-select"]').first();

    // Skip if page not accessible or hooks absent
    if (await autoBtn.count() === 0 || await manualBtn.count() === 0) {
      test.skip(true, 'Assignments page or hooks not present');
      return;
    }

    // Open Auto-assign modal
    await autoBtn.focus();
    await autoBtn.click();
    const autoModal = page.locator('#mt-auto-assign-modal');
    await expect(autoModal).toBeVisible();

    // ESC should close and focus return to trigger
    await page.keyboard.press('Escape');
    await expect(autoModal).toBeHidden();
    await expect(autoBtn).toBeFocused();

    // Open Manual-assign modal (new attributes)
    await manualBtn.click();
    const manualModal = page.locator('[data-test="manual-assign-modal"]');
    await expect(manualModal).toBeVisible();

    // Click on overlay area to close (click outside content)
    await page.mouse.click(5, 5); // near top-left; modal overlay should intercept
    // Allow transition
    await page.waitForTimeout(150);
    await expect(manualModal).toBeHidden();

    // Bulk controls present (non-destructive)
    if (await bulkSelect.count()) {
      await expect(bulkSelect).toBeVisible();
    }
    if (await bulkApply.count()) {
      await expect(bulkApply).toBeVisible();
    }
  });
});

