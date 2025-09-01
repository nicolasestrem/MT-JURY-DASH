import { test, expect } from '@playwright/test';

const DASHBOARD_PATH = process.env.TEST_PATH_JURY_DASHBOARD || '/jury-dashboard';

test.describe('Jury Dashboard increments', () => {
  test('plus/minus adjust by 1', async ({ page }) => {
    await page.goto(DASHBOARD_PATH, { waitUntil: 'networkidle' });
    // Find the first inline evaluation block if present
    const scoreInput = page.locator('.mt-score-input').first();
    const incBtn = page.locator('.mt-score-adjust[data-action="increase"]').first();
    const decBtn = page.locator('.mt-score-adjust[data-action="decrease"]').first();

    // If no controls exist, skip (environment may not have assignments)
    if (await scoreInput.count() === 0 || await incBtn.count() === 0 || await decBtn.count() === 0) {
      test.skip(true, 'No inline evaluation controls present for this user');
      return;
    }

    // Normalize to a known value (set to 5 if allowed)
    await scoreInput.fill('5');
    await scoreInput.dispatchEvent('change');

    // Increase
    await incBtn.click();
    const incVal = parseFloat(await scoreInput.inputValue());
    expect(incVal).toBe(6);

    // Decrease
    await decBtn.click();
    const decVal = parseFloat(await scoreInput.inputValue());
    expect(decVal).toBe(5);
  });
});

