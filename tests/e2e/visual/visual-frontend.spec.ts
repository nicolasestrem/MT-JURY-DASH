import { test, expect } from '@playwright/test';
import { VIEWPORTS, gotoAndWait } from '../utils/visual';

type VisualPage = {
  name: string;
  path: string;
  role?: 'public' | 'jury';
  selector?: string; // a DOM hook to confirm page is ready
};

const PAGES: VisualPage[] = [
  {
    name: 'jury-dashboard',
    path: process.env.TEST_PATH_JURY_DASHBOARD || '/jury-dashboard',
    role: 'jury',
    selector: '.mt-jury-dashboard, .mt-root',
  },
  {
    name: 'candidates-grid',
    path: process.env.TEST_PATH_GRID || '/vote',
    role: 'jury',
    selector: '.mt-candidates-grid, .mt-root',
  },
  {
    name: 'winners',
    path: process.env.TEST_PATH_WINNERS || '/winners',
    role: 'public',
    selector: '.mt-winners, .mt-root',
  },
  // Optional: evaluation and candidate single require known slugs
  ...(process.env.TEST_PATH_EVALUATION
    ? [{ name: 'evaluation', path: process.env.TEST_PATH_EVALUATION, role: 'jury', selector: '.mt-evaluation-form, .mt-root' }]
    : []),
  ...(process.env.TEST_PATH_CANDIDATE
    ? [{ name: 'candidate-single', path: process.env.TEST_PATH_CANDIDATE, role: 'public', selector: '.mt-candidate-showcase, .mt-root' }]
    : []),
];

for (const vp of VIEWPORTS) {
  for (const pageDef of PAGES) {
    test.describe(`[visual][${vp.name}] ${pageDef.name}`, () => {
      test.use({ viewport: { width: vp.width, height: vp.height } });

      test(`should match snapshot for ${pageDef.name} @${vp.name}`, async ({ page }) => {
        await gotoAndWait(page, pageDef.path, pageDef.selector);
        await expect(page).toHaveScreenshot(`${pageDef.name}-${vp.name}.png`, { fullPage: true });
      });
    });
  }
}

