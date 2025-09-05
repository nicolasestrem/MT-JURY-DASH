import { test as setup, expect } from '@playwright/test';
import path from 'path';

const authDir = path.join(__dirname, '../.auth');

// Helper function for frontend login
async function loginViaFrontend(page, username: string, password: string) {
  // Go to frontend page
  await page.goto('/');
  
  // Wait for page to load
  await page.waitForLoadState('networkidle');
  
  // Check if already logged in (look for welcome message or logout link)
  const isLoggedIn = await page.locator('.mt-jury-welcome, .logout-link, a[href*="logout"], .wp-block-loginout > a').count() > 0;
  
  if (!isLoggedIn) {
    // Check if login form exists on frontend
    const loginFormExists = await page.locator('#loginform').count() > 0;
    
    if (!loginFormExists) {
      console.log('❌ Login form not found on frontend, checking if redirect needed...');
      
      // Check if we need to go to a specific login page
      const loginLink = page.locator('a:has-text("Login"), a:has-text("Anmelden")');
      if (await loginLink.count() > 0) {
        console.log('  Found login link, clicking it...');
        await loginLink.first().click();
        await page.waitForLoadState('networkidle');
      }
    }
    
    // Try again to find the login form
    try {
      await page.waitForSelector('#loginform', { state: 'visible', timeout: 5000 });
    } catch (e) {
      console.log('❌ Login form still not found, falling back to WordPress login page');
      // Fallback to WordPress login page
      await page.goto('/wp-login.php');
      await page.waitForLoadState('networkidle');
    }
    
    // Fill in the login form using the actual IDs
    // These IDs work both on frontend and WordPress login page
    await page.locator('#user_login').fill(username);
    await page.locator('#user_pass').fill(password);
    
    // Log what we're doing for debugging
    console.log(`  Logging in as: ${username}`);
    console.log(`  Using password: "${password}" (length: ${password.length})`);
    
    // Check "Remember Me" if available
    const rememberMe = page.locator('#rememberme');
    if (await rememberMe.count() > 0) {
      await rememberMe.check();
    }
    
    // Submit the form
    await page.locator('#wp-submit').click();
    
    // Wait for login to complete
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);
    
    // Check if login was successful by looking for any logged-in indicator
    const loggedInNow = await page.locator('#wpadminbar, .logged-in, a[href*="logout"]').count() > 0;
    if (!loggedInNow) {
      console.log('⚠️ Login might have failed, checking for error messages...');
      const errorMsg = await page.locator('#login_error, .error').textContent().catch(() => '');
      if (errorMsg) {
        console.log('  Error message:', errorMsg);
      }
    } else {
      console.log('  ✓ Login successful');
    }
  }
}

// Setup authentication for admin user
setup('authenticate as admin', async ({ page }) => {
  const adminUsername = process.env.TEST_ADMIN_USERNAME || 'testadmin';
  const adminPassword = process.env.TEST_ADMIN_PASSWORD || 'CHANGE_ME';
  
  console.log('🔐 Setting up admin authentication...');
  
  // Login via frontend
  await loginViaFrontend(page, adminUsername, adminPassword);
  
  // Admins can access wp-admin, navigate there
  await page.goto('/wp-admin/');
  await page.waitForLoadState('networkidle');
  
  // Verify successful login - admin should see admin bar or admin menu
  const adminIndicators = page.locator('#wpadminbar, #adminmenu, .dashboard-widget-title, #dashboard-widgets');
  await expect(adminIndicators.first()).toBeVisible({ timeout: 10000 });
  
  // Save authentication state
  await page.context().storageState({ path: path.join(authDir, 'admin.json') });
  
  console.log('✓ Admin authentication setup complete');
});

// Setup authentication for jury member
setup('authenticate as jury member', async ({ page }) => {
  const juryUsername = process.env.TEST_JURY_USERNAME || 'jurytester1';
  const juryPassword = process.env.TEST_JURY_PASSWORD || 'CHANGE_ME';
  
  console.log('🔐 Setting up jury member authentication...');
  
  // Login via frontend
  await loginViaFrontend(page, juryUsername, juryPassword);
  
  // Navigate back to homepage to see jury dashboard
  await page.goto('/');
  await page.waitForLoadState('networkidle');
  
  // Verify we're logged in - check for any of these elements that appear after login
  const loggedInIndicators = page.locator(
    ':text("WILLKOMMEN"), :text("Welcome"), .mt-jury-dashboard, .jury-stats, ' +
    '.mt-jury-welcome, .logged-in .wp-block-loginout > a, #wpadminbar, ' +
    'a[href*="logout"], .mt-candidate-grid'
  );
  
  // Wait for at least one indicator to be visible
  await expect(loggedInIndicators.first()).toBeVisible({ timeout: 10000 });
  
  // Save authentication state
  await page.context().storageState({ path: path.join(authDir, 'jury.json') });
  
  console.log('✓ Jury member authentication setup complete');
});

// Setup authentication for jury admin
setup('authenticate as jury admin', async ({ page }) => {
  const juryAdminUsername = process.env.TEST_JURY_ADMIN_USERNAME || 'juryadmintester';
  const juryAdminPassword = process.env.TEST_JURY_ADMIN_PASSWORD || 'CHANGE_ME';
  
  console.log('🔐 Setting up jury admin authentication...');
  
  // Login via frontend
  await loginViaFrontend(page, juryAdminUsername, juryAdminPassword);
  
  // Navigate back to homepage to see jury dashboard (jury admin now sees it)
  await page.goto('/');
  await page.waitForLoadState('networkidle');
  
  // Verify we're logged in - jury admin should see jury dashboard like jury members
  const loggedInIndicators = page.locator(
    ':text("WILLKOMMEN"), :text("Welcome"), .mt-jury-dashboard, .jury-stats, ' +
    '.mt-jury-welcome, .logged-in .wp-block-loginout > a, #wpadminbar, ' +
    'a[href*="logout"], .mt-candidate-grid'
  );
  
  // Wait for at least one indicator to be visible
  await expect(loggedInIndicators.first()).toBeVisible({ timeout: 10000 });
  
  // Save authentication state
  await page.context().storageState({ path: path.join(authDir, 'jury-admin.json') });
  
  console.log('✓ Jury admin authentication setup complete');
});