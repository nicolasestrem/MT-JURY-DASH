import { test } from '@playwright/test';
import path from 'path';

async function tryLogin(page, username: string, password: string) {
  await page.goto('/wp-login.php');
  await page.fill('input#user_login', username);
  await page.fill('input#user_pass', password);
  await page.click('input#wp-submit');
  await page.waitForLoadState('networkidle');
  // Heuristic: presence of wp-admin bar implies logged in
  const isLogged = await page.locator('#wpadminbar').count().then(c => c > 0);
  return isLogged;
}

test('seed auth storage states', async ({ browser, baseURL }) => {
  const ctx = await browser.newContext();
  const page = await ctx.newPage();

  const adminUser = process.env.TEST_ADMIN_USER;
  const adminPass = process.env.TEST_ADMIN_PASS;
  const juryUser = process.env.TEST_JURY_USER;
  const juryPass = process.env.TEST_JURY_PASS;

  // Admin storage state
  if (adminUser && adminPass) {
    try {
      const ok = await tryLogin(page, adminUser, adminPass);
      if (!ok) console.warn('Admin login did not confirm, writing storage state anyway');
    } catch (e) {
      console.warn('Admin login failed:', e);
    }
  }
  await ctx.storageState({ path: path.resolve(__dirname, '../.auth/admin.json') });

  // New context for jury
  const ctxJury = await browser.newContext();
  const pageJury = await ctxJury.newPage();
  if (juryUser && juryPass) {
    try {
      const ok = await tryLogin(pageJury, juryUser, juryPass);
      if (!ok) console.warn('Jury login did not confirm, writing storage state anyway');
    } catch (e) {
      console.warn('Jury login failed:', e);
    }
  }
  await ctxJury.storageState({ path: path.resolve(__dirname, '../.auth/jury.json') });

  await ctx.close();
  await ctxJury.close();
});

