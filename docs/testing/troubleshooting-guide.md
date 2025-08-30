# Test Environment Troubleshooting Guide

**Last Updated:** August 30, 2025  
**Version:** 2.5.41

## Common Issues & Solutions

### 🔴 Authentication Failures

#### Problem: "User not found" error
```
Error: Der Benutzername jurymember1 ist auf dieser Website nicht registriert
```

**Solutions:**
1. Check if test accounts exist:
```bash
wp user list --role=mt_jury_member
wp user get jurytester1
```

2. Create missing accounts:
```bash
wp user create jurytester1 jurytester1@test.local \
  --role=mt_jury_member --user_pass=JuryTest2024!
wp user add-cap jurytester1 read
wp user add-cap jurytester1 edit_dashboard
```

3. Verify credentials in .env files match created accounts

---

#### Problem: "Failed to authenticate as jury member"
```
Error: Failed to authenticate as jury member
```

**Solutions:**
1. Add required capabilities:
```bash
wp user add-cap jurytester1 read
wp user add-cap jurytester1 edit_dashboard
```

2. Clear browser cookies and retry:
```javascript
await context.clearCookies();
```

3. Check if wp-admin is accessible for the role

---

### 🔴 Environment Variable Issues

#### Problem: Wrong usernames in test logs
```
Using credentials: jurymember1 / ****
(Should be: jurytester1)
```

**Solutions:**
1. Check for system environment variables:
```bash
echo $JURY_USERNAME
echo $JURY_ADMIN_USERNAME
```

2. Override in auth.setup.ts:
```javascript
process.env.JURY_USERNAME = 'jurytester1';
process.env.JURY_PASSWORD = 'JuryTest2024!';
```

3. Ensure .env files have quoted passwords:
```env
JURY_PASSWORD="JuryTest2024!"  # Correct
JURY_PASSWORD=JuryTest2024!    # Wrong - special chars break
```

---

#### Problem: Environment variables not loading
```
TypeError: Cannot read properties of undefined
```

**Solutions:**
1. Check .env file locations:
```
tests/.env.test
tests/config/.env.test
tests/config/.env.test.local
```

2. Verify dotenv loading in playwright.config.ts:
```javascript
dotenv.config({ path: path.resolve(__dirname, 'tests/config/.env.test') });
dotenv.config({ path: path.resolve(__dirname, 'tests/config/.env.test.local') });
```

3. Use fallback values in tests:
```javascript
const username = process.env.JURY_USERNAME || 'jurytester1';
```

---

### 🔴 Test Execution Issues

#### Problem: "Cannot find module" error
```
Error: Cannot find module '../../package.json'
```

**Solution:**
```bash
npm install
npx playwright install chromium
```

---

#### Problem: Tests timeout during setup
```
TimeoutError: page.waitForURL: Timeout 30000ms exceeded
```

**Solutions:**
1. Check if WordPress is running:
```bash
curl http://localhost:8080
```

2. Verify Docker containers:
```bash
docker ps
docker-compose up -d
```

3. Increase timeout in playwright.config.ts:
```javascript
timeout: 60000,
navigationTimeout: 30000,
```

---

#### Problem: False negative - login form not found
```
Error: expect(locator).toBeVisible() failed
Locator: locator('#loginform')
```

**Solution:**
Clear cookies before testing login:
```javascript
test('admin can login', async ({ page, context }) => {
  await context.clearCookies(); // Add this
  await page.goto('/wp-admin');
  // ... rest of test
});
```

---

### 🔴 Docker & Environment Issues

#### Problem: Containers not running
```
Error: WordPress not accessible at http://localhost:8080
```

**Solutions:**
1. Check container status:
```bash
docker-compose ps
docker-compose logs wordpress
```

2. Restart containers:
```bash
docker-compose down
docker-compose up -d
```

3. Check port conflicts:
```bash
netstat -an | grep 8080
lsof -i :8080
```

---

#### Problem: Database connection errors
```
Error: Error establishing a database connection
```

**Solutions:**
1. Check database container:
```bash
docker-compose logs db
docker exec -it mobility_mariadb_dev mysql -u root -p
```

2. Verify database credentials in wp-config.php

3. Reset database:
```bash
docker-compose down -v
docker-compose up -d
```

---

### 🔴 Role & Permission Issues

#### Problem: Custom roles not found
```
Error: Invalid role: mt_jury_member
```

**Solutions:**
1. Check if plugin is active:
```bash
wp plugin list | grep mobility
wp plugin activate mobility-trailblazers
```

2. Verify roles exist:
```bash
wp role list
```

3. Re-register roles:
```bash
wp eval "do_action('init');"
```

---

#### Problem: Jury member can't access wp-admin
```
Error: You do not have sufficient permissions
```

**Solution:**
Add required capabilities:
```bash
wp user add-cap jurytester1 read
wp user add-cap jurytester1 edit_dashboard
```

---

### 🔴 Test Data Issues

#### Problem: Test data creation fails
```
Warning: Could not create test data automatically
```

**Solutions:**
1. Create test data manually:
```bash
# Create test candidates
wp post create --post_type=mt_candidate \
  --post_title="Test Candidate 1" --post_status=publish

# Create assignments
wp eval "MT_Assignment_Service::auto_assign(20, 3);"
```

2. Skip test data creation by removing from global-setup.ts

3. Use existing production data (if appropriate)

---

### 🔴 Visual & UI Test Issues

#### Problem: Screenshot comparison failures
```
Error: Screenshot comparison failed
```

**Solutions:**
1. Update baseline screenshots:
```bash
npx playwright test --update-snapshots
```

2. Clear screenshot cache:
```bash
rm -rf tests/screenshots/
```

3. Check for environment differences (fonts, resolution)

---

#### Problem: ARIA attribute failures
```
Error: Element missing ARIA attributes
```

**Solutions:**
1. Review widget implementation for accessibility

2. Update test expectations to match implementation

3. Add missing ARIA attributes to elements

---

## Quick Diagnostics Script

Create `diagnose.js`:
```javascript
const dotenv = require('dotenv');
const path = require('path');

// Load environment files
dotenv.config({ path: 'tests/config/.env.test' });
dotenv.config({ path: 'tests/config/.env.test.local' });

console.log('🔍 Diagnostic Report\n');
console.log('Environment Variables:');
console.log('  ADMIN_USERNAME:', process.env.ADMIN_USERNAME || '❌ NOT SET');
console.log('  JURY_USERNAME:', process.env.JURY_USERNAME || '❌ NOT SET');
console.log('  JURY_ADMIN_USERNAME:', process.env.JURY_ADMIN_USERNAME || '❌ NOT SET');
console.log('  TEST_URL:', process.env.TEST_URL || '❌ NOT SET');

// Test WordPress connection
const http = require('http');
http.get('http://localhost:8080', (res) => {
  console.log('\nWordPress Status:');
  console.log('  Status Code:', res.statusCode);
  console.log('  Accessible:', res.statusCode === 200 ? '✅' : '❌');
}).on('error', (err) => {
  console.log('\nWordPress Status:');
  console.log('  ❌ Not accessible:', err.message);
});
```

Run with: `node diagnose.js`

---

## Emergency Recovery

### Complete Reset
```bash
# 1. Stop everything
docker-compose down -v

# 2. Clean test artifacts
rm -rf tests/.auth/
rm -rf tests/reports/
rm -rf node_modules/

# 3. Reinstall
npm install
npx playwright install chromium

# 4. Restart Docker
docker-compose up -d

# 5. Wait for WordPress
sleep 30

# 6. Create test accounts
./scripts/create-test-accounts.sh

# 7. Run auth setup
npx playwright test tests/e2e/auth.setup.ts --project=setup

# 8. Test
npx playwright test
```

---

## Getting Help

### Check Logs
```bash
# Docker logs
docker-compose logs -f wordpress
docker-compose logs -f db

# Test reports
npx playwright show-report

# WordPress debug
tail -f wp-content/debug.log
```

### Debug Mode
```bash
# Run tests with debug
npx playwright test --debug

# Run with headed browser
npx playwright test --headed

# Run specific test
npx playwright test --grep "admin can login"
```

### Contact Support
- GitHub Issues: [Report Issue](https://github.com/nicolasestrem/mobility-trailblazers/issues)
- Documentation: `/docs/testing/`
- Test Reports: `tests/reports/playwright-report/`

---

**Remember:** Most issues are related to:
1. Missing or wrong credentials
2. Environment variables not loading
3. Docker containers not running
4. Cached authentication states

Always start troubleshooting by checking these four areas first!