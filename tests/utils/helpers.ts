import { Page } from '@playwright/test';
import fs from 'fs';
import path from 'path';

/**
 * Utility functions for Playwright tests
 */

/**
 * Generate random email address
 */
export function generateRandomEmail(): string {
  const timestamp = Date.now();
  const random = Math.random().toString(36).substring(7);
  return `test_${random}_${timestamp}@example.com`;
}

/**
 * Generate random name
 */
export function generateRandomName(): string {
  const firstNames = ['John', 'Jane', 'Michael', 'Sarah', 'David', 'Emma', 'Robert', 'Lisa'];
  const lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis'];
  
  const firstName = firstNames[Math.floor(Math.random() * firstNames.length)];
  const lastName = lastNames[Math.floor(Math.random() * lastNames.length)];
  
  return `${firstName} ${lastName}`;
}

/**
 * Generate random organization name
 */
export function generateOrganization(): string {
  const prefixes = ['Tech', 'Global', 'Future', 'Smart', 'Green', 'Digital', 'Urban', 'Next'];
  const suffixes = ['Mobility', 'Solutions', 'Innovations', 'Systems', 'Dynamics', 'Labs', 'Hub', 'Ventures'];
  
  const prefix = prefixes[Math.floor(Math.random() * prefixes.length)];
  const suffix = suffixes[Math.floor(Math.random() * suffixes.length)];
  
  return `${prefix} ${suffix}`;
}

/**
 * Generate random score (0-10 with 0.5 increments)
 */
export function generateRandomScore(): number {
  return Math.round(Math.random() * 20) / 2;
}

/**
 * Generate complete evaluation data
 */
export function generateEvaluationData() {
  return {
    scores: {
      courage: generateRandomScore(),
      innovation: generateRandomScore(),
      implementation: generateRandomScore(),
      relevance: generateRandomScore(),
      visibility: generateRandomScore()
    },
    comments: {
      courage: 'Test comment for courage criterion',
      innovation: 'Test comment for innovation criterion',
      implementation: 'Test comment for implementation criterion',
      relevance: 'Test comment for relevance criterion',
      visibility: 'Test comment for visibility criterion',
      general: 'This is a test evaluation generated for automated testing purposes.'
    }
  };
}

/**
 * Wait for WordPress AJAX to complete
 */
export async function waitForWPAjax(page: Page): Promise<void> {
  await page.waitForFunction(() => {
    return typeof jQuery !== 'undefined' ? jQuery.active === 0 : true;
  }, { timeout: 30000 });
}

/**
 * Check if element is in viewport
 */
export async function isInViewport(page: Page, selector: string): Promise<boolean> {
  return await page.locator(selector).evaluate(el => {
    const rect = el.getBoundingClientRect();
    return (
      rect.top >= 0 &&
      rect.left >= 0 &&
      rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
      rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
  });
}

/**
 * Scroll element into view
 */
export async function scrollIntoView(page: Page, selector: string): Promise<void> {
  await page.locator(selector).scrollIntoViewIfNeeded();
  await page.waitForTimeout(500); // Wait for scroll animation
}

/**
 * Take screenshot with timestamp
 */
export async function takeScreenshot(page: Page, name: string): Promise<string> {
  const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
  const filename = `${name}-${timestamp}.png`;
  const filepath = path.join('tests', 'reports', 'screenshots', filename);
  
  // Ensure directory exists
  const dir = path.dirname(filepath);
  if (!fs.existsSync(dir)) {
    fs.mkdirSync(dir, { recursive: true });
  }
  
  await page.screenshot({ path: filepath, fullPage: true });
  return filepath;
}

/**
 * Compare two numbers with tolerance
 */
export function numbersAreClose(a: number, b: number, tolerance: number = 0.01): boolean {
  return Math.abs(a - b) <= tolerance;
}

/**
 * Format score for display
 */
export function formatScore(score: number): string {
  return score.toFixed(1);
}

/**
 * Parse score from string
 */
export function parseScore(scoreText: string): number {
  const cleaned = scoreText.replace(/[^\d.]/g, '');
  return parseFloat(cleaned) || 0;
}

/**
 * Generate CSV content for testing imports
 */
export function generateTestCSV(rows: number = 10): string {
  const headers = ['Name', 'Email', 'Organization', 'Position', 'Country'];
  const data = [headers];
  
  for (let i = 0; i < rows; i++) {
    data.push([
      generateRandomName(),
      generateRandomEmail(),
      generateOrganization(),
      'Test Position',
      'Germany'
    ]);
  }
  
  return data.map(row => row.join(',')).join('\n');
}

/**
 * Wait for file download
 */
export async function waitForDownload(page: Page, action: () => Promise<void>): Promise<string> {
  const downloadPromise = page.waitForEvent('download');
  await action();
  const download = await downloadPromise;
  
  // Save to specific location
  const filename = download.suggestedFilename();
  const filepath = path.join('tests', 'downloads', filename);
  await download.saveAs(filepath);
  
  return filepath;
}

/**
 * Read CSV file content
 */
export function readCSV(filepath: string): string[][] {
  const content = fs.readFileSync(filepath, 'utf-8');
  const lines = content.split('\n').filter(line => line.trim());
  return lines.map(line => line.split(',').map(cell => cell.trim()));
}

/**
 * Mock API response
 */
export async function mockApiResponse(page: Page, url: string, response: any): Promise<void> {
  await page.route(url, route => {
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify(response)
    });
  });
}

/**
 * Clear all cookies and storage
 */
export async function clearAllStorage(page: Page): Promise<void> {
  await page.context().clearCookies();
  await page.evaluate(() => {
    localStorage.clear();
    sessionStorage.clear();
  });
}

/**
 * Login programmatically (without UI)
 */
export async function loginProgrammatically(
  page: Page, 
  username: string, 
  password: string
): Promise<void> {
  // This would make a direct API call to WordPress login
  // For now, using UI-based login
  await page.goto('/wp-login.php');
  await page.locator('#user_login').fill(username);
  await page.locator('#user_pass').fill(password);
  await page.locator('#wp-submit').click();
  await page.waitForURL('**/wp-admin/**');
}

/**
 * Get WordPress nonce
 */
export async function getWPNonce(page: Page): Promise<string> {
  return await page.evaluate(() => {
    // Try multiple possible nonce locations
    const nonceElement = document.querySelector('#_wpnonce, input[name="_wpnonce"], meta[name="nonce"]');
    if (nonceElement) {
      return (nonceElement as HTMLInputElement).value || nonceElement.getAttribute('content') || '';
    }
    
    // Try from JavaScript global
    if (typeof (window as any).mtAjax !== 'undefined') {
      return (window as any).mtAjax.nonce || '';
    }
    
    return '';
  });
}

/**
 * Retry action with backoff
 */
export async function retryWithBackoff<T>(
  action: () => Promise<T>,
  maxRetries: number = 3,
  delay: number = 1000
): Promise<T> {
  for (let i = 0; i < maxRetries; i++) {
    try {
      return await action();
    } catch (error) {
      if (i === maxRetries - 1) throw error;
      await new Promise(resolve => setTimeout(resolve, delay * Math.pow(2, i)));
    }
  }
  throw new Error('Max retries reached');
}

/**
 * Check if running in CI environment
 */
export function isCI(): boolean {
  return process.env.CI === 'true' || process.env.CI === '1';
}

/**
 * Get base URL from environment
 */
export function getBaseURL(): string {
  return process.env.TEST_URL || 'http://localhost:8080';
}

/**
 * Format date for testing
 */
export function formatDate(date: Date): string {
  return date.toISOString().split('T')[0];
}

/**
 * Generate test data for specific number of items
 */
export function generateTestData(type: 'candidates' | 'jury' | 'evaluations', count: number): any[] {
  const data = [];
  
  for (let i = 0; i < count; i++) {
    switch (type) {
      case 'candidates':
        data.push({
          name: generateRandomName(),
          email: generateRandomEmail(),
          organization: generateOrganization(),
          position: 'Test Position ' + i,
          description: 'Test candidate description ' + i
        });
        break;
      
      case 'jury':
        data.push({
          name: generateRandomName(),
          email: generateRandomEmail(),
          expertise: 'Test Expertise ' + i,
          bio: 'Test jury member bio ' + i
        });
        break;
      
      case 'evaluations':
        data.push(generateEvaluationData());
        break;
    }
  }
  
  return data;
}