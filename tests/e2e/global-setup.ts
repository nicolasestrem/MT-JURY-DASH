import { chromium, FullConfig } from '@playwright/test';
import dotenv from 'dotenv';
import path from 'path';
import fs from 'fs';

async function globalSetup(config: FullConfig) {
  console.log('\n🚀 Starting Mobility Trailblazers Test Suite Setup\n');
  
  // Load environment variables
  dotenv.config({ path: path.resolve(__dirname, '../config/.env.test') });
  dotenv.config({ path: path.resolve(__dirname, '../config/.env.test.local') });
  
  // Create necessary directories
  const authDir = path.join(__dirname, '../.auth');
  const downloadsDir = path.join(__dirname, '../downloads');
  const reportsDir = path.join(__dirname, '../reports');
  
  [authDir, downloadsDir, reportsDir].forEach(dir => {
    if (!fs.existsSync(dir)) {
      fs.mkdirSync(dir, { recursive: true });
      console.log(`✓ Created directory: ${dir}`);
    }
  });
  
  // Verify test environment is accessible
  const browser = await chromium.launch();
  const context = await browser.newContext();
  const page = await context.newPage();
  
  try {
    const testUrl = process.env.TEST_URL || 'http://localhost:8080';
    console.log(`🔍 Checking test environment at: ${testUrl}`);
    
    const response = await page.goto(testUrl, { 
      waitUntil: 'domcontentloaded',
      timeout: 30000 
    });
    
    if (!response || !response.ok()) {
      throw new Error(`Test environment not accessible at ${testUrl}. Status: ${response?.status()}`);
    }
    
    console.log('✓ Test environment is accessible');
    
    // Check if WordPress is installed
    const isWordPress = await page.locator('meta[name="generator"][content*="WordPress"]').count() > 0
      || await page.locator('link[rel="https://api.w.org/"]').count() > 0;
    
    if (!isWordPress) {
      console.warn('⚠️  WordPress indicators not found. Ensure WordPress is properly installed.');
    } else {
      console.log('✓ WordPress installation detected');
    }
    
    // Check if plugin is active
    const pluginActive = await page.evaluate(() => {
      // Check for plugin-specific elements or classes
      return document.querySelector('.mt-plugin-active') !== null
        || document.body.classList.contains('mobility-trailblazers-active');
    });
    
    if (pluginActive) {
      console.log('✓ Mobility Trailblazers plugin appears to be active');
    } else {
      console.log('ℹ️  Plugin activation status could not be verified from frontend');
    }
    
  } catch (error) {
    console.error('❌ Setup failed:', error);
    throw error;
  } finally {
    await browser.close();
  }
  
  // Set up test data if needed
  console.log('\n📊 Test Data Setup');
  console.log('-------------------');
  
  // Check if test users exist (this would normally use WP-CLI or API)
  const testUsers = [
    { username: 'testadmin', role: 'Administrator' },
    { username: 'jurytester1', role: 'Jury Member' },
    { username: 'juryadmintester', role: 'Jury Admin' }
  ];
  
  console.log('Expected test users:');
  testUsers.forEach(user => {
    console.log(`  - ${user.username} (${user.role})`);
  });
  
  console.log('\n✅ Global setup completed successfully\n');
  
  // Store setup timestamp
  const setupInfo = {
    timestamp: new Date().toISOString(),
    environment: process.env.TEST_URL || 'http://localhost:8080',
    headless: process.env.TEST_HEADLESS !== 'false'
  };
  
  fs.writeFileSync(
    path.join(__dirname, '../.setup-info.json'),
    JSON.stringify(setupInfo, null, 2)
  );
}

export default globalSetup;