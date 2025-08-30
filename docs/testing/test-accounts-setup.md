# Test Accounts Setup Guide

**Last Updated:** August 30, 2025  
**Version:** 2.5.41  
**Environment:** Staging (http://localhost:8080)

## Test Accounts Overview

Three dedicated test accounts have been created for the Mobility Trailblazers test suite, each with specific roles and permissions to enable comprehensive testing of different user scenarios.

## Account Details

### 1. Test Administrator
- **Username:** `testadmin`
- **Password:** `TestAdmin2024!`
- **Email:** `testadmin@test.local`
- **Role:** `administrator`
- **User ID:** 45
- **Capabilities:** Full WordPress admin access, all plugin features

### 2. Jury Member Tester
- **Username:** `jurytester1`
- **Password:** `JuryTest2024!`
- **Email:** `jurytester1@test.local`
- **Role:** `mt_jury_member`
- **User ID:** 46
- **Additional Capabilities:** `read`, `edit_dashboard` (for wp-admin access)
- **Capabilities:** 
  - View assigned candidates
  - Submit evaluations (5 criteria, 0-10 scale)
  - Access jury dashboard
  - View evaluation progress
  - Basic WordPress admin access (dashboard only)

### 3. Jury Admin Tester
- **Username:** `juryadmintester`
- **Password:** `JuryAdmin2024!`
- **Email:** `juryadmin@test.local`
- **Roles:** `mt_jury_admin`, `mt_jury_member`
- **User ID:** 47
- **Capabilities:**
  - All jury member capabilities
  - Manage jury assignments
  - View all evaluations
  - Access assignment statistics
  - Auto-assign candidates to jury members

## Roles & Permissions Matrix

| Feature | Administrator | Jury Admin | Jury Member |
|---------|--------------|------------|-------------|
| WordPress Admin Access | ✅ Full | ✅ Limited | ✅ Limited |
| MT Plugin Settings | ✅ | ❌ | ❌ |
| Manage Candidates | ✅ | ❌ | ❌ |
| Manage Jury Members | ✅ | ❌ | ❌ |
| Manage Assignments | ✅ | ✅ | ❌ |
| View All Evaluations | ✅ | ✅ | ❌ |
| Submit Evaluations | ✅ | ✅ | ✅ |
| View Own Evaluations | ✅ | ✅ | ✅ |
| Access Jury Dashboard | ✅ | ✅ | ✅ |
| View Assignment Stats | ✅ | ✅ | ❌ |
| Import/Export Data | ✅ | ❌ | ❌ |
| Debug Center Access | ✅ | ❌ | ❌ |

## Environment Configuration

### Test Environment File
Location: `tests/config/.env.test.local`

```env
# Test user credentials for authentication tests
TEST_ADMIN_USERNAME=testadmin
TEST_ADMIN_PASSWORD=TestAdmin2024!
JURY_USERNAME=jurytester1
JURY_PASSWORD=JuryTest2024!
JURY_ADMIN_USERNAME=juryadmintester
JURY_ADMIN_PASSWORD=JuryAdmin2024!

# Test URL
TEST_URL=http://localhost:8080
```

## How to Recreate Test Accounts

If test accounts need to be recreated, use the following WP-CLI commands:

```bash
# Create administrator account
wp user create testadmin testadmin@test.local \
  --role=administrator \
  --user_pass=TestAdmin2024!

# Create jury member account
wp user create jurytester1 jurytester1@test.local \
  --role=mt_jury_member \
  --user_pass=JuryTest2024!

# Add basic wp-admin capabilities for testing
wp user add-cap jurytester1 read
wp user add-cap jurytester1 edit_dashboard

# Create jury admin account
wp user create juryadmintester juryadmin@test.local \
  --role=mt_jury_admin \
  --user_pass=JuryAdmin2024!

# Add jury member role to jury admin (for dual role)
wp user add-role juryadmintester mt_jury_member
```

## Test Authentication Flow

### 1. Initial Setup
Before running tests, authentication states must be created:

```bash
# From project root
npm run test:auth

# Or manually
cd build
npx playwright test ../tests/e2e/auth.setup.ts --project=setup --config=../playwright.config.ts
```

### 2. Authentication States
Authentication states are stored in `tests/.auth/`:
- `admin.json` - testadmin session
- `jury.json` - jurytester1 session
- `jury-admin.json` - juryadmintester session

### 3. Test Execution
Tests automatically use the appropriate authentication state based on the test requirements:

```javascript
// Admin tests
test.use({ storageState: 'tests/.auth/admin.json' });

// Jury member tests
test.use({ storageState: 'tests/.auth/jury.json' });

// Jury admin tests
test.use({ storageState: 'tests/.auth/jury-admin.json' });
```

## Verification Commands

### Check Account Existence
```sql
SELECT u.ID, u.user_login, u.user_email, 
       (SELECT meta_value FROM wp_usermeta 
        WHERE user_id = u.ID AND meta_key = 'wp_capabilities') as roles
FROM wp_users u 
WHERE u.user_login IN ('testadmin', 'jurytester1', 'juryadmintester');
```

### Verify Roles
```bash
# Using WP-CLI
wp user get testadmin --field=roles
wp user get jurytester1 --field=roles
wp user get juryadmintester --field=roles
```

## Test Scenarios by Account

### testadmin (Administrator)
- Full plugin functionality testing
- Settings configuration
- Import/Export operations
- Debug center access
- Candidate management
- Database operations

### jurytester1 (Jury Member)
- Evaluation form submission
- Jury dashboard navigation
- Candidate viewing (assigned only)
- Progress tracking
- Score validation (0-10 scale)

### juryadmintester (Jury Admin)
- Assignment management
- Auto-assignment functionality
- Statistics viewing
- Evaluation oversight
- Jury member coordination

## Troubleshooting

### Login Failures
1. Verify account exists in database
2. Check password is correct
3. Ensure roles are properly assigned
4. Clear browser cookies/cache
5. Regenerate auth states

### Permission Issues
1. Verify custom roles exist (`mt_jury_member`, `mt_jury_admin`)
2. Check role capabilities are registered
3. Ensure plugin is active
4. Verify database tables exist

### Test Environment Issues
1. Confirm Docker containers running
2. Check URL is http://localhost:8080
3. Verify .env.test.local exists
4. Ensure environment variables loaded

## Security Notes

- Test accounts use strong passwords
- Accounts are for staging environment only
- Production data remains in environment (per requirements)
- Credentials stored in .env.test.local (gitignored)
- Authentication states not committed to repository

## Related Documentation

- [Test Suite Overview](../README.md)
- [Playwright Configuration](../../playwright.config.ts)
- [Authentication Setup](../../tests/e2e/auth.setup.ts)
- [Environment Setup Guide](../../tests/SETUP-GUIDE.md)

---

**Note:** These test accounts are configured specifically for the staging environment at http://localhost:8080. Do not use these credentials in production environments.