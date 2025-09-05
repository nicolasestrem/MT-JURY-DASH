# Mobility Trailblazers Playwright Test Suite

Comprehensive end-to-end testing suite for the Mobility Trailblazers WordPress plugin using Playwright.

## 📋 Table of Contents

- [Overview](#overview)
- [Test Coverage](#test-coverage)
- [Setup](#setup)
- [Running Tests](#running-tests)
- [Test Structure](#test-structure)
- [Writing Tests](#writing-tests)
- [Visual Regression Testing](#visual-regression-testing)
- [CI/CD Integration](#cicd-integration)
- [Troubleshooting](#troubleshooting)

## 🎯 Overview

This test suite provides comprehensive coverage for the Mobility Trailblazers award management platform, including:

- Admin functionality testing
- Jury member workflow validation
- Evaluation system testing
- Visual regression testing
- Mobile responsiveness testing
- Performance testing

## 📊 Test Coverage

### Critical Paths (100% coverage required)
- ✅ User authentication and role-based access
- ✅ Jury evaluation submission workflow
- ✅ Score calculations and rankings
- ✅ Data exports (CSV)
- ✅ Assignment creation and management

### Admin Features
- ✅ Candidate management (CRUD operations)
- ✅ Jury member management
- ✅ Assignment creation (manual and auto-assign)
- ✅ Evaluation monitoring
- ✅ Rankings and statistics
- ✅ Export functionality
- ✅ Debug center

### Jury Member Features
- ✅ Dashboard access and navigation
- ✅ Viewing assignments
- ✅ Submitting evaluations (5-criteria scoring)
- ✅ Saving drafts
- ✅ Progress tracking

### Visual & Responsive
- ✅ Visual regression testing for key pages
- ✅ Mobile responsive testing (375px, 768px, 1920px)
- ✅ Component-level visual tests
- ✅ State-based visual tests (empty, loading, error)

## 🚀 Setup

### Prerequisites

1. **Node.js 16+** installed
2. **Docker** running with WordPress environment
3. **Plugin activated** in WordPress

### Installation

```bash
# Install dependencies
npm install

# Install Playwright browsers
npx playwright install

# Create test users (requires Docker)
./scripts/setup-test-users.sh  # Linux/Mac
# or
./scripts/setup-test-users.ps1 # Windows PowerShell
```

### Environment Configuration

1. Copy the environment template:
```bash
cp tests/config/.env.test.local.example tests/config/.env.test.local
```

2. Update `tests/config/.env.test.local` with your settings:
```env
TEST_URL=http://localhost:8080
TEST_ADMIN_PASSWORD=YourSecurePassword
TEST_JURY_PASSWORD=YourSecurePassword
```

## 🧪 Running Tests

### All Tests
```bash
npm test
# or
npx playwright test
```

### Specific Test Suites
```bash
# Admin tests only
npx playwright test tests/e2e/admin

# Jury tests only
npx playwright test tests/e2e/jury

# Visual regression tests
npx playwright test tests/e2e/visual

# Specific test file
npx playwright test tests/e2e/admin/candidates.spec.ts
```

### Test Modes
```bash
# Interactive UI mode
npm run test:ui

# Debug mode
npm run test:debug

# Headed mode (see browser)
npm run test:headed

# Specific project
npx playwright test --project=admin
npx playwright test --project=jury-member
npx playwright test --project=mobile
```

### Test Filtering
```bash
# Run tests matching pattern
npx playwright test --grep="evaluation"

# Exclude tests
npx playwright test --grep-invert="visual"
```

## 📁 Test Structure

```
tests/
├── .auth/                      # Authentication states
│   ├── admin.json             # Admin session
│   ├── jury.json              # Jury member session
│   └── jury-admin.json        # Jury admin session
├── config/
│   ├── .env.test              # Default test configuration
│   └── .env.test.local        # Local overrides (gitignored)
├── e2e/
│   ├── auth.setup.ts          # Authentication setup
│   ├── global-setup.ts        # Global test setup
│   ├── global-teardown.ts     # Global test teardown
│   ├── admin/                 # Admin test suites
│   │   ├── candidates.spec.ts
│   │   ├── assignments.spec.ts
│   │   ├── evaluations.spec.ts
│   │   └── dashboard.spec.ts
│   ├── jury/                  # Jury member tests
│   │   ├── jury-dashboard.spec.ts
│   │   ├── jury-evaluation.spec.ts
│   │   └── jury-workflow.spec.ts
│   └── visual/                # Visual regression tests
│       └── visual-regression.spec.ts
├── fixtures/                  # Test data
│   └── test-data.json
├── pages/                     # Page Object Models
│   ├── base-page.ts
│   ├── admin-page.ts
│   ├── jury-dashboard-page.ts
│   └── evaluation-form-page.ts
├── utils/                     # Test utilities
│   └── helpers.ts
└── reports/                   # Test reports (gitignored)
    ├── playwright-report/
    ├── test-results/
    └── screenshots/
```

## ✍️ Writing Tests

### Basic Test Structure
```typescript
import { test, expect } from '@playwright/test';
import { AdminPage } from '../../pages/admin-page';

test.describe('Feature Name', () => {
  let adminPage: AdminPage;

  test.beforeEach(async ({ page }) => {
    adminPage = new AdminPage(page);
    await adminPage.goto();
  });

  test('should do something', async ({ page }) => {
    // Arrange
    const expectedValue = 'Expected';
    
    // Act
    await adminPage.performAction();
    
    // Assert
    await expect(page.locator('.result')).toHaveText(expectedValue);
  });
});
```

### Using Page Objects
```typescript
// Navigate to specific pages
await adminPage.gotoCandidates();
await adminPage.gotoEvaluations();

// Perform actions
await adminPage.selectBulkAction('export');
await adminPage.searchItems('candidate name');

// Get data
const stats = await dashboardPage.getStats();
const candidates = await dashboardPage.getAssignedCandidates();
```

### Test Data
```typescript
import testData from '../../fixtures/test-data.json';

test('should use test data', async ({ page }) => {
  const candidate = testData.candidates[0];
  await page.fill('#name', candidate.name);
  await page.fill('#email', candidate.email);
});
```

## 📸 Visual Regression Testing

### Capturing Screenshots
```typescript
await expect(page).toHaveScreenshot('page-name.png', {
  fullPage: true,
  animations: 'disabled',
  mask: [page.locator('.dynamic-content')]
});
```

### Updating Baselines
```bash
# Update all visual baselines
npx playwright test --update-snapshots

# Update specific test baselines
npx playwright test visual-regression --update-snapshots
```

### Visual Test Configuration
- **Threshold**: 0.2 (20% difference allowed)
- **Animations**: Disabled for consistency
- **Masked elements**: Timestamps, dates, dynamic content

## 🔄 CI/CD Integration

### GitHub Actions Example
```yaml
name: E2E Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3
        with:
          node-version: '18'
      
      - name: Install dependencies
        run: npm ci
      
      - name: Install Playwright
        run: npx playwright install --with-deps
      
      - name: Run tests
        run: npm test
        env:
          TEST_URL: ${{ secrets.TEST_URL }}
          CI: true
      
      - uses: actions/upload-artifact@v3
        if: always()
        with:
          name: playwright-report
          path: tests/reports/
```

## 🐛 Troubleshooting

### Common Issues

#### Authentication Failures
```bash
# Clear auth state and re-run setup
rm -rf tests/.auth/*
npx playwright test auth.setup.ts
```

#### Test Timeouts
```typescript
// Increase timeout for specific test
test('slow test', async ({ page }) => {
  test.setTimeout(120000); // 2 minutes
  // test code
});
```

#### Visual Test Failures
```bash
# View visual diff report
npx playwright show-report

# Accept new baselines
npx playwright test --update-snapshots
```

#### Environment Issues
```bash
# Verify WordPress is running
curl http://localhost:8080

# Check plugin activation
docker exec mobility_wordpress_dev wp plugin list

# View container logs
docker logs mobility_wordpress_dev
```

### Debug Commands
```bash
# Run with debug output
DEBUG=pw:api npx playwright test

# Run in debug mode
npx playwright test --debug

# Generate trace
npx playwright test --trace on
```

### Viewing Reports
```bash
# Open HTML report
npm run show-report

# View trace
npx playwright show-trace trace.zip
```

## 📝 Best Practices

1. **Use Page Objects**: Keep tests maintainable by using page object models
2. **Test Isolation**: Each test should be independent
3. **Explicit Waits**: Use `waitForSelector` instead of arbitrary timeouts
4. **Meaningful Assertions**: Use descriptive expect messages
5. **Test Data**: Use fixtures for consistent test data
6. **Cleanup**: Tests should clean up after themselves
7. **Parallel Execution**: Tests should support parallel execution

## 🚧 Test Maintenance

### Regular Tasks
- Update test data fixtures monthly
- Review and update visual baselines quarterly
- Remove obsolete tests
- Add tests for new features
- Monitor test execution time

### Performance Targets
- Individual test: < 30 seconds
- Full suite: < 10 minutes
- Visual tests: < 5 minutes

## 📚 Additional Resources

- [Playwright Documentation](https://playwright.dev)
- [Page Object Model Pattern](https://playwright.dev/docs/pom)
- [Visual Testing Guide](https://playwright.dev/docs/test-snapshots)
- [CI/CD Best Practices](https://playwright.dev/docs/ci)

## 🤝 Contributing

When adding new tests:
1. Follow existing patterns and structure
2. Add page objects for new pages
3. Update this README with new coverage
4. Ensure tests pass locally before committing
5. Add visual tests for new UI components

---

*Last Updated: September 2025*
*Playwright Version: 1.55.0*