import { test, expect } from '@playwright/test';
import { VIEWPORTS, gotoAndWait } from '../utils/visual';

const ADMIN_PAGES = [
  { name: 'admin-assignments', path: '/wp-admin/admin.php?page=mt-assignments', selector: '.wrap' },
  { name: 'admin-evaluations', path: '/wp-admin/admin.php?page=mt-evaluations', selector: '.wrap' },
  { name: 'admin-settings', path: '/wp-admin/admin.php?page=mt-settings', selector: '.wrap' },
];

for (const vp of VIEWPORTS) {
  for (const p of ADMIN_PAGES) {
    test.describe(`[visual][admin][${vp.name}] ${p.name}`, () => {
      test.use({ viewport: { width: vp.width, height: vp.height } });

      test(`should match snapshot for ${p.name} @${vp.name}`,
        { tag: ['admin'] },
        async ({ page }) => {
          await gotoAndWait(page, p.path, p.selector);
          await expect(page).toHaveScreenshot(`${p.name}-${vp.name}.png`, { fullPage: true });
        }
      );
    });
  }
}

