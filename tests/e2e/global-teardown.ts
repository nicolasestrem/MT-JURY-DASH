import { FullConfig } from '@playwright/test';
import path from 'path';
import fs from 'fs';

async function globalTeardown(config: FullConfig) {
  console.log('\n🧹 Starting test suite teardown\n');
  
  // Read setup info
  const setupInfoPath = path.join(__dirname, '../.setup-info.json');
  if (fs.existsSync(setupInfoPath)) {
    const setupInfo = JSON.parse(fs.readFileSync(setupInfoPath, 'utf-8'));
    const duration = Date.now() - new Date(setupInfo.timestamp).getTime();
    console.log(`⏱️  Total test duration: ${Math.round(duration / 1000)}s`);
  }
  
  // Clean up temporary files (optional)
  const tempDirs = ['downloads'];
  
  tempDirs.forEach(dir => {
    const dirPath = path.join(__dirname, '..', dir);
    if (fs.existsSync(dirPath)) {
      try {
        // Only clean if directory has files
        const files = fs.readdirSync(dirPath);
        if (files.length > 0) {
          files.forEach(file => {
            const filePath = path.join(dirPath, file);
            if (fs.statSync(filePath).isFile()) {
              fs.unlinkSync(filePath);
            }
          });
          console.log(`✓ Cleaned ${files.length} file(s) from ${dir}`);
        }
      } catch (error) {
        console.warn(`⚠️  Could not clean ${dir}:`, error);
      }
    }
  });
  
  // Generate test summary if test results exist
  const resultsDir = path.join(__dirname, '../reports/test-results');
  if (fs.existsSync(resultsDir)) {
    const resultFiles = fs.readdirSync(resultsDir).filter(f => f.endsWith('.json'));
    if (resultFiles.length > 0) {
      console.log(`\n📊 Test Results Summary`);
      console.log(`  - Result files generated: ${resultFiles.length}`);
      console.log(`  - Report location: ${path.join(__dirname, '../reports/playwright-report')}`);
      console.log(`  - View report: npm run show-report`);
    }
  }
  
  // Clean up auth files if in CI environment
  if (process.env.CI) {
    const authDir = path.join(__dirname, '../.auth');
    if (fs.existsSync(authDir)) {
      const authFiles = fs.readdirSync(authDir);
      authFiles.forEach(file => {
        fs.unlinkSync(path.join(authDir, file));
      });
      console.log(`✓ Cleaned ${authFiles.length} auth file(s) (CI mode)`);
    }
  }
  
  // Log any warnings or important notes
  console.log('\n📝 Teardown Notes:');
  console.log('  - Authentication states preserved for next run');
  console.log('  - Test reports available in tests/reports/');
  console.log('  - Screenshots/videos saved for failed tests');
  
  console.log('\n✅ Global teardown completed\n');
}

export default globalTeardown;