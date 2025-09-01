SECURITY PATCHES - STAGING UPLOAD INSTRUCTIONS
==============================================

CRITICAL: Only upload these 4 files to staging!

FILES TO UPLOAD:
----------------
1. includes/admin/class-mt-import-export.php
2. includes/repositories/class-mt-audit-log-repository.php  
3. includes/ajax/class-mt-base-ajax.php
4. includes/ajax/class-mt-evaluation-ajax.php

STAGING FTP UPLOAD STEPS:
-------------------------
1. Connect to staging server via FTP

2. Navigate to: /wp-content/plugins/mobility-trailblazers/

3. BACKUP the original files first:
   - Download the 4 original files from staging
   - Save them in a "staging-backup-2025-08-31" folder

4. Upload the patched files to their exact locations:
   - class-mt-import-export.php → includes/admin/
   - class-mt-audit-log-repository.php → includes/repositories/
   - class-mt-base-ajax.php → includes/ajax/
   - class-mt-evaluation-ajax.php → includes/ajax/

5. Clear WordPress cache (if caching plugin is active)

TESTING ON STAGING:
------------------
After upload, test these features:
1. ✓ Export candidates CSV (tests SQL injection fix #1)
2. ✓ View audit logs with sorting (tests SQL injection fix #2)
3. ✓ Submit an evaluation (tests nonce + rate limiting)
4. ✓ Try submitting 11 evaluations in 1 minute (should hit rate limit)
5. ✓ Save inline evaluation from grid (tests all fixes)

WHAT WAS FIXED:
--------------
- SQL Injection in export function
- SQL Injection in audit log ORDER BY
- Standardized nonce verification
- Added rate limiting (10 evaluations/minute)

If everything works on staging → Apply same files to production

ROLLBACK IF NEEDED:
------------------
Simply re-upload the backed-up original files