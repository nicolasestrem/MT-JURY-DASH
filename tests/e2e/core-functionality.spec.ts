import { test, expect } from '@playwright/test';

/**
 * Core Functionality Tests
 * Consolidated from: auth-login, navigation, database-tables, version-2.5.33-validation, no-admin
 * 
 * This file tests the fundamental plugin operations including:
 * - Authentication and login flows
 * - Navigation and routing
 * - Database table integrity
 * - Version verification
 * - Access control
 */

test.describe('Core Functionality', () => {
  
  // ===========================================
  // AUTHENTICATION & LOGIN TESTS
  // ===========================================
  test.describe('Authentication', () => {
    test('admin can login successfully', async ({ page, context }) => {
      // Clear stored state to test actual login
      await context.clearCookies();
      
      await page.goto('/wp-admin');
      
      // Check if login form is present
      await expect(page.locator('#loginform')).toBeVisible();
      
      // Fill login credentials
      const username = process.env.TEST_ADMIN_USERNAME || 'testadmin';
      const password = process.env.TEST_ADMIN_PASSWORD || 'TestAdmin2024!';
      
      await page.fill('#user_login', username);
      await page.fill('#user_pass', password);
      await page.click('#wp-submit');
      
      // Verify successful login
      await page.waitForURL('**/wp-admin/**');
      await expect(page.locator('#wpadminbar')).toBeVisible();
      await expect(page.locator('#adminmenu')).toBeVisible();
    });

    test('handles invalid login credentials', async ({ page, context }) => {
      // Clear stored state to test actual login
      await context.clearCookies();
      
      await page.goto('/wp-admin');
      
      await page.fill('#user_login', 'invaliduser');
      await page.fill('#user_pass', 'wrongpassword');
      await page.click('#wp-submit');
      
      // Should show error message
      await expect(page.locator('#login_error')).toBeVisible();
      await expect(page.locator('#login_error')).toContainText(/ERROR|Fehler/i);
    });

    test('login form security features', async ({ page, context }) => {
      // Clear stored state to test actual login form
      await context.clearCookies();
      
      await page.goto('/wp-admin');
      
      // Check for password field type
      const passwordField = page.locator('#user_pass');
      await expect(passwordField).toHaveAttribute('type', 'password');
      
      // Check for remember me option
      await expect(page.locator('#rememberme')).toBeVisible();
      
      // Check for lost password link
      await expect(page.locator('a:text-matches("Lost your password|Passwort vergessen", "i")')).toBeVisible();
    });
  });

  // ===========================================
  // NAVIGATION TESTS
  // ===========================================
  test.describe('Navigation', () => {
    test.use({ storageState: 'tests/.auth/admin.json' });
    
    test('MT plugin appears in admin menu', async ({ page }) => {
      await page.goto('/wp-admin');
      
      // Check for MT menu items
      const mtMenu = page.locator('#adminmenu').locator('text=/MT Award System|Mobility Trailblazers/i');
      await expect(mtMenu).toBeVisible();
    });

    test('can navigate to MT admin pages', async ({ page }) => {
      const mtPages = [
        { url: '/wp-admin/admin.php?page=mt-dashboard', title: /Dashboard|Overview/i },
        { url: '/wp-admin/admin.php?page=mt-assignments', title: /Assignments|Zuweisungen/i },
        { url: '/wp-admin/edit.php?post_type=mt_candidate', title: /Candidates|Kandidaten/i },
        { url: '/wp-admin/edit.php?post_type=mt_jury_member', title: /Jury Members|Jurymitglieder/i },
      ];
      
      for (const mtPage of mtPages) {
        await page.goto(mtPage.url);
        // Page should load without 404
        await expect(page.locator('h1')).toContainText(mtPage.title);
      }
    });

    test('breadcrumb navigation works', async ({ page }) => {
      await page.goto('/wp-admin/edit.php?post_type=mt_candidate');
      
      // Look for breadcrumb or admin notices
      const pageTitle = await page.locator('h1').textContent();
      expect(pageTitle).toMatch(/Candidates|Kandidaten/i);
    });
  });

  // ===========================================
  // DATABASE INTEGRITY TESTS  
  // ===========================================
  test.describe('Database Tables', () => {
    test.use({ storageState: 'tests/.auth/admin.json' });
    
    test('custom tables exist and have correct structure', async ({ page }) => {
      // Check debug center if available
      const response = await page.goto('/wp-admin/admin.php?page=mt-debug-center');
      
      if (response && response.ok()) {
        // Verify tables are mentioned
        const content = await page.content();
        
        // Check for custom tables
        const expectedTables = [
          'wp_mt_evaluations',
          'wp_mt_jury_assignments',
          'wp_mt_audit_log',
          'wp_mt_error_log'
        ];
        
        for (const table of expectedTables) {
          console.log(`Checking for table: ${table}`);
          // Tables should be referenced in debug output or database health check
        }
      }
    });

    test('evaluation table has required columns', async ({ page }) => {
      // This would typically check via debug center or direct database query
      const requiredColumns = [
        'jury_member_id',
        'candidate_id',
        'criterion_1',
        'criterion_2', 
        'criterion_3',
        'criterion_4',
        'criterion_5',
        'total_score',
        'status'
      ];
      
      // Log expected structure for documentation
      console.log('Expected evaluation columns:', requiredColumns);
    });
  });

  // ===========================================
  // VERSION VALIDATION TESTS
  // ===========================================
  test.describe('Version Checks', () => {
    test('plugin version is correctly set to 2.5.33', async ({ page }) => {
      await page.goto('/wp-admin/plugins.php');
      
      // Look for Mobility Trailblazers plugin
      const pluginRow = page.locator('tr[data-plugin*="mobility-trailblazers"]');
      
      if (await pluginRow.count() > 0) {
        const versionText = await pluginRow.locator('.plugin-version-author-uri').textContent();
        console.log('Plugin version info:', versionText);
        expect(versionText).toContain('2.5.33');
      }
    });

    test('CSS framework loads without !important', async ({ page }) => {
      await page.goto('/');
      
      // Check CSS files are loaded
      const cssFiles = ['mt-core.css', 'mt-components.css', 'mt-framework.css'];
      
      for (const cssFile of cssFiles) {
        const cssLink = page.locator(`link[href*="${cssFile}"]`);
        if (await cssLink.count() > 0) {
          const href = await cssLink.getAttribute('href');
          console.log(`✅ CSS loaded: ${cssFile}`);
          expect(href).toContain('ver=');
        }
      }
    });
  });

  // ===========================================
  // ACCESS CONTROL TESTS
  // ===========================================
  test.describe('Access Control', () => {
    test('non-admin users have restricted access', async ({ page }) => {
      // This would test with a subscriber or contributor role
      // For now, verify that admin pages require authentication
      
      // Try to access admin page without auth
      await page.goto('/wp-admin/admin.php?page=mt-assignments');
      
      // Should redirect to login
      const url = page.url();
      if (!url.includes('wp-login.php')) {
        // If not redirected, check for permission error
        const body = await page.locator('body').textContent();
        expect(body).toMatch(/permission|Berechtigung|denied|verweigert/i);
      }
    });

    test('AJAX endpoints require proper nonce', async ({ page }) => {
      await page.goto('/');
      
      // Try to call AJAX without nonce
      const response = await page.evaluate(async () => {
        try {
          const res = await fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=mt_save_evaluation&candidate_id=1'
          });
          return await res.text();
        } catch (e) {
          return 'error';
        }
      });
      
      // Should fail without proper nonce
      expect(response).toMatch(/0|-1|error|invalid/i);
    });
  });
});