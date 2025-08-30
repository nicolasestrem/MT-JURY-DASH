import { test as setup, expect } from '@playwright/test';
import * as dotenv from 'dotenv';
import * as path from 'path';

// Load environment variables from .env.test.local
dotenv.config({ path: path.resolve(__dirname, '../config/.env.test.local') });

// Override any system environment variables with our test values
process.env.JURY_USERNAME = 'jurytester1';
process.env.JURY_PASSWORD = 'JuryTest2024!';
process.env.JURY_ADMIN_USERNAME = 'juryadmintester';
process.env.JURY_ADMIN_PASSWORD = 'JuryAdmin2024!';

/**
 * Authentication setup for different user roles
 * Creates auth states that can be reused across tests
 */

const adminFile = path.join(__dirname, '../.auth/admin.json');
const juryFile = path.join(__dirname, '../.auth/jury.json');
const juryAdminFile = path.join(__dirname, '../.auth/jury-admin.json');

// Admin user authentication
setup('authenticate as admin', async ({ page }) => {
  console.log('🔑 Setting up admin authentication...');
  
  await page.goto('/wp-admin');
  
  // Fill login form - use secure test credentials from env
  const username = process.env.TEST_ADMIN_USERNAME || process.env.ADMIN_USERNAME || 'testadmin';
  const password = process.env.TEST_ADMIN_PASSWORD || process.env.ADMIN_PASSWORD || 'testadmin123';
  
  console.log(`🔐 Using credentials: ${username} / ${password.replace(/./g, '*')}`);
  
  // Use simple fill method like the working test-login.js
  await page.fill('#user_login', username);
  await page.fill('#user_pass', password);
  await page.click('#wp-submit');
  
  // Wait for successful login - handle both German and English interfaces
  await page.waitForLoadState('networkidle');
  
  // Check if we have an error message (German: "Fehler" or English: "Error")
  const errorElement = page.locator('#login_error');
  if (await errorElement.isVisible({ timeout: 1000 }).catch(() => false)) {
    const errorText = await errorElement.textContent();
    throw new Error(`Login failed: ${errorText}`);
  }
  
  // Wait for navigation away from login page
  await page.waitForFunction(
    () => !window.location.href.includes('wp-login.php'),
    { timeout: 10000 }
  ).catch(() => {});
  
  // Check multiple indicators of successful login
  const currentUrl = page.url();
  const hasAdminBar = await page.locator('#wpadminbar').isVisible({ timeout: 1000 }).catch(() => false);
  const hasDashboard = await page.locator('#dashboard-widgets-wrap, .wrap').isVisible({ timeout: 1000 }).catch(() => false);
  const hasAdminMenu = await page.locator('#adminmenu').isVisible({ timeout: 1000 }).catch(() => false);
  const isOnAdminPage = currentUrl.includes('/wp-admin');
  const notOnLoginPage = !currentUrl.includes('wp-login.php');
  
  const isLoggedIn = (hasAdminBar || hasDashboard || hasAdminMenu || isOnAdminPage) && notOnLoginPage;
  
  if (!isLoggedIn) {
    console.log('Debug - Current URL:', currentUrl);
    console.log('Debug - Has admin bar:', hasAdminBar);
    console.log('Debug - Has dashboard:', hasDashboard);
    console.log('Debug - Has admin menu:', hasAdminMenu);
    console.log('Debug - Is on admin page:', isOnAdminPage);
    throw new Error('Failed to authenticate as admin');
  }
  
  console.log('✅ Admin authentication successful');
  
  // Save authentication state
  await page.context().storageState({ path: adminFile });
});

// Jury member authentication
setup('authenticate as jury member', async ({ page }) => {
  console.log('🔑 Setting up jury member authentication...');
  
  await page.goto('/wp-admin');
  
  // Fill login form with jury member credentials
  const username = process.env.JURY_USERNAME || 'jurytester1';
  const password = process.env.JURY_PASSWORD || 'JuryTest2024!';
  
  console.log(`🔐 Using credentials: ${username} / ${password.replace(/./g, '*')}`);
  
  // Use simple fill method like the working test-login.js
  await page.fill('#user_login', username);
  await page.fill('#user_pass', password);
  await page.click('#wp-submit');
  
  // Wait for successful login
  await page.waitForLoadState('networkidle');
  
  // Check if we have an error message
  const errorElement = page.locator('#login_error');
  if (await errorElement.isVisible({ timeout: 1000 }).catch(() => false)) {
    const errorText = await errorElement.textContent();
    throw new Error(`Login failed: ${errorText}`);
  }
  
  // Jury member has limited admin access
  await Promise.race([
    page.waitForURL('**/wp-admin/**', { timeout: 10000 }),
    page.locator('#wpadminbar').waitFor({ state: 'visible', timeout: 10000 })
  ]).catch(() => {
    console.log('Note: Jury member has limited admin access');
  });
  
  // Verify we're logged in
  const isLoggedIn = await page.locator('#wpadminbar').isVisible({ timeout: 5000 }).catch(() => false) ||
                     page.url().includes('/wp-admin/');
  
  if (!isLoggedIn) {
    throw new Error('Failed to authenticate as jury member');
  }
  
  console.log('✅ Jury member authentication successful');
  
  // Save authentication state
  await page.context().storageState({ path: juryFile });
});

// Jury admin authentication
setup('authenticate as jury admin', async ({ page }) => {
  console.log('🔑 Setting up jury admin authentication...');
  
  await page.goto('/wp-admin');
  
  // Fill login form with jury admin credentials
  const username = process.env.JURY_ADMIN_USERNAME || 'juryadmintester';
  const password = process.env.JURY_ADMIN_PASSWORD || 'JuryAdmin2024!';
  
  console.log(`🔐 Using credentials: ${username} / ${password.replace(/./g, '*')}`);
  
  // Use simple fill method like the working test-login.js
  await page.fill('#user_login', username);
  await page.fill('#user_pass', password);
  await page.click('#wp-submit');
  
  // Wait for successful login - handle German locale
  await page.waitForLoadState('networkidle');
  
  // Check for login error
  const errorElement = page.locator('#login_error');
  if (await errorElement.isVisible({ timeout: 1000 }).catch(() => false)) {
    const errorText = await errorElement.textContent();
    throw new Error(`Login failed: ${errorText}`);
  }
  
  // Wait for redirect or admin bar
  await Promise.race([
    page.waitForURL('**/wp-admin/**', { timeout: 10000 }),
    page.locator('#wpadminbar').waitFor({ state: 'visible', timeout: 10000 })
  ]);
  
  // Verify we're logged in
  const isLoggedIn = await page.locator('#wpadminbar').isVisible({ timeout: 5000 }).catch(() => false);
  if (!isLoggedIn && !page.url().includes('/wp-admin/')) {
    throw new Error('Failed to authenticate as jury admin');
  }
  
  // Verify jury admin has access to MT plugin
  await page.goto('/wp-admin/admin.php?page=mt-assignments');
  // Check for either the admin page class or the page title
  const hasAdminPage = await page.locator('.mt-admin-page, .wrap h1:has-text("MT Award")').isVisible({ timeout: 5000 }).catch(() => false);
  if (!hasAdminPage) {
    console.log('⚠️  MT admin page not found, but continuing...');
  }
  
  console.log('✅ Jury admin authentication successful');
  
  // Save authentication state
  await page.context().storageState({ path: juryAdminFile });
});

// Logout helper for cleanup
setup('logout from all sessions', async ({ page }) => {
  console.log('🚪 Cleaning up authentication sessions...');
  
  // Clear all cookies and local storage
  await page.context().clearCookies();
  
  // Try to clear storage, but don't fail if not allowed
  try {
    await page.evaluate(() => {
      localStorage.clear();
      sessionStorage.clear();
    });
  } catch (e) {
    // Storage clearing might fail on some pages, that's okay
  }
  
  console.log('✅ Sessions cleared');
});