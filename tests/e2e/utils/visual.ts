import { Page, test } from '@playwright/test';

export type Viewport = { name: string; width: number; height: number };

export const VIEWPORTS: Viewport[] = [
  { name: '1440', width: 1440, height: 900 },
  { name: '1200', width: 1200, height: 900 },
  { name: '992', width: 992, height: 800 },
  { name: '768', width: 768, height: 900 },
  { name: '480', width: 480, height: 900 },
];

export async function gotoAndWait(page: Page, path: string, readySelector?: string) {
  await page.goto(path, { waitUntil: 'networkidle' });
  if (readySelector) {
    const found = await page.locator(readySelector).first().count();
    if (!found) test.skip(`Skipping: selector ${readySelector} not found on ${path}`);
  }
  // Give layout a moment to settle
  await page.waitForTimeout(250);
}

