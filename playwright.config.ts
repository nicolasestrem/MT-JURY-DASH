import { defineConfig, devices } from '@playwright/test';
import dotenv from 'dotenv';
import path from 'path';

// Load test environment variables
dotenv.config({ path: path.resolve(__dirname, 'tests/config/.env.test') });
// Also load local test credentials if they exist
dotenv.config({ path: path.resolve(__dirname, 'tests/config/.env.test.local') });

/**
 * Mobility Trailblazers E2E Test Configuration
 * See https://playwright.dev/docs/test-configuration.
 */
export default defineConfig({
  testDir: './tests/e2e',
  
  /* Run tests in files in parallel */
  fullyParallel: true,
  
  /* Fail the build on CI if you accidentally left test.only in the source code. */
  forbidOnly: !!process.env.CI,
  
  /* Retry on CI only */
  retries: process.env.CI ? 2 : 0,
  
  /* Opt out of parallel tests on CI. */
  workers: process.env.CI ? 1 : 3,
  
  /* Reporter to use. See https://playwright.dev/docs/test-reporters */
  reporter: [
    ['html', { outputFolder: 'tests/reports/playwright-report' }],
    ['list']
  ],
  
  /* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
  use: {
    /* Base URL to use in actions like `await page.goto('/')`. */
    baseURL: process.env.TEST_URL || 'http://localhost:8080',

    /* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
    trace: 'on-first-retry',
    
    /* Take screenshot on failure */
    screenshot: 'only-on-failure',
    
    /* Record video on failure */
    video: 'retain-on-failure',
    
    /* Global timeout for each action */
    actionTimeout: 30000,
    
    /* Navigation timeout */
    navigationTimeout: 30000,
  },

  /* Global timeout */
  timeout: 60000,
  
  /* Global setup and teardown */
  globalSetup: './tests/e2e/global-setup.ts',
  globalTeardown: './tests/e2e/global-teardown.ts',

  /* Configure projects for major browsers */
  projects: [
    // Setup project for authentication
    {
      name: 'setup',
      testMatch: /.*\.setup\.ts/,
    },

    // Main test suite in Chrome
    {
      name: 'chromium',
      use: { 
        ...devices['Desktop Chrome'],
        storageState: 'tests/.auth/admin.json'
      },
      dependencies: ['setup'],
      testIgnore: ['**/visual/**'],
    },

    // Firefox tests (optional)
    {
      name: 'firefox',
      use: { 
        ...devices['Desktop Firefox'],
        storageState: 'tests/.auth/admin.json'
      },
      dependencies: ['setup'],
      // Only run on CI or when explicitly requested
      testIgnore: process.env.CI ? [] : ['**/*.spec.ts']
    },

    // Mobile responsive tests
    {
      name: 'mobile',
      use: { 
        ...devices['Pixel 5'],
        storageState: 'tests/.auth/admin.json'
      },
      dependencies: ['setup'],
      testMatch: ['**/responsive*.spec.ts', '**/mobile*.spec.ts']
    },

    // Jury member specific tests
    {
      name: 'jury-member',
      use: { 
        ...devices['Desktop Chrome'],
        storageState: 'tests/.auth/jury.json'
      },
      dependencies: ['setup'],
      testMatch: /.*jury.*\.spec\.ts/,
    },

    // Admin specific tests
    {
      name: 'admin',
      use: { 
        ...devices['Desktop Chrome'],
        storageState: 'tests/.auth/admin.json'
      },
      dependencies: ['setup'],
      testMatch: /.*admin.*\.spec\.ts/,
      testIgnore: ['**/visual/**'],
    },
    // Visual regression projects
    {
      name: 'visual-frontend',
      use: {
        ...devices['Desktop Chrome'],
        storageState: 'tests/.auth/jury.json',
      },
      dependencies: ['setup'],
      testMatch: ['**/visual/visual-frontend.spec.ts'],
    },
    {
      name: 'visual-admin',
      use: {
        ...devices['Desktop Chrome'],
        storageState: 'tests/.auth/admin.json',
      },
      dependencies: ['setup'],
      testMatch: ['**/visual/visual-admin.spec.ts'],
    },
  ],

  /* Run your local dev server before starting the tests */
  webServer: {
    command: 'echo "WordPress server should be running on localhost:8080"',
    url: 'http://localhost:8080',
    reuseExistingServer: true,
    timeout: 120 * 1000,
  },

  /* Test output directory */
  outputDir: 'tests/reports/test-results',
  
  /* Expect timeout */
  expect: {
    timeout: 10000,
    toHaveScreenshot: {
      // Global visual regression cap: ≤ 3% pixel ratio difference
      maxDiffPixelRatio: 0.03,
    },
  },
});
