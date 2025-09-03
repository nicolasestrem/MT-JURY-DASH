# Mobility Trailblazers – Code Audit (read-only)

## Executive summary
- **Critical Security Gaps:** The plugin has multiple raw SQL queries that are vulnerable to SQL Injection. While many AJAX endpoints and form submissions have nonce checks, the lack of consistent use of `$wpdb->prepare()` is a critical risk.
- **Architectural Inconsistency:** There's a clear and well-documented shift towards a modern Repository-Service architecture, but significant parts of the codebase still rely on older, direct WordPress functions (`get_post_meta`, `get_posts`), creating a hybrid system that is difficult to maintain and debug. A custom table (`wp_mt_candidates`) exists alongside the `mt_candidate` CPT, indicating a partial or incomplete data migration. 
- **Redundant & Unused Assets:** The `assets` directory, particularly CSS, is cluttered with numerous hotfix files, backups, and seemingly abandoned experiments (e.g., `v4` styles). This increases plugin size and makes frontend development confusing and risky.
- **Incomplete Features:** Several features, such as the Elementor integration and some debug/tooling pages, appear to be partially implemented or contain placeholder logic, posing a risk if accessed or enabled in a production environment.
- **Poor i18n Practices:** A "German compatibility" PHP file is used as a fallback for translations. This is not a standard or reliable way to handle localization and will cause issues with language packs and caching.

## Scorecard
- **Security: [D]**
  - Rationale: The presence of numerous unprepared SQL queries creates a significant SQL Injection risk. While nonce and capability checks are present in many places, they are not universally applied, and there is some inconsistent input sanitization. The plugin is a mix of secure and insecure practices.
- **Reliability: [C]**
  - Rationale: The architectural inconsistencies (CPTs vs. custom tables, legacy code mixed with new patterns) and potential for race conditions or data mismatches are high. The plugin relies on fragile mechanisms like a PHP-based translation fallback. Error handling is present but inconsistent.
- **Performance: [C]**
  - Rationale: The code shows awareness of performance issues (e.g., caching, attempts at batch processing in deprecated functions), but some key operations, like data exports, still load all objects into memory (`posts_per_page => -1`), which will fail on large datasets. Database queries lack indexes in some verifiable cases.
- **Maintainability: [D]**
  - Rationale: The mix of architectures, redundant code, and cluttered asset directory makes the codebase difficult to understand and safely modify. The lack of clear "source of truth" for data (CPT vs. custom table) is a major maintenance burden. Naming conventions are also inconsistent.
- **i18n: [D]**
  - Rationale: The use of a PHP file for translations is a major anti-pattern. While the correct text domain is used in most places, the fallback mechanism is fragile and non-standard. A full audit of user-facing strings is required to ensure 100% coverage.

## Findings by severity
---
### P0 (must fix before release)
- **ID:** MT-001
- **Severity:** P0
- **Area:** Security
- **File(s):** `C:\Users\nicol\Desktop\MT-JURY-DASH\Plugin\includes\repositories\class-mt-candidate-repository.php:109`, `C:\Users\nicol\Desktop\MT-JURY-DASH\Plugin\includes\admin\class-mt-maintenance-tools.php:290`, `C:\Users\nicol\Desktop\MT-JURY-DASH\Plugin\includes\admin\class-mt-coaching.php:153`
- **What:** Multiple database queries are executed without being passed through `$wpdb->prepare()`.
- **Why:** This exposes the plugin to SQL Injection vulnerabilities. An attacker could potentially manipulate database queries to read sensitive data, modify data, or cause a denial of service. This is especially critical in functions accessible by lower-privileged users or in AJAX handlers.
- **Evidence:**
  ```php
  // class-mt-candidate-repository.php:109
  $results = $wpdb->get_results($sql); // $sql is built with string concatenation

  // class-mt-maintenance-tools.php:290
  $result = $wpdb->query("OPTIMIZE TABLE `$table`"); // $table is a variable

  // class-mt-coaching.php:153
  $stats = $wpdb->get_results("\n      SELECT ...\n  "); // Static query string, but still a bad practice
  ```
- **Related docs:** `C:\Users\nicol\Desktop\MT-JURY-DASH\docs\SECURITY-PATCHES.md` (Implied by general security best practices)
- **Next step:** Refactor all database queries to use `$wpdb->prepare()` for any dynamic values.

- **ID:** MT-002
- **Severity:** P0
- **Area:** Security
- **File(s):** `C:\Users\nicol\Desktop\MT-JURY-DASH\Plugin\includes\ajax\class-mt-evaluation-ajax.php:803`
- **What:** An administrator or manager is explicitly allowed to bypass a critical security check that ensures a jury member is assigned to a candidate before submitting an evaluation.
- **Why:** While intended for administrative convenience, this creates a dangerous precedent and a potential security hole. If an admin account is compromised, or if a user with `mt_manage_evaluations` capability is not intended to have universal evaluation rights, this could lead to unauthorized data modification. Security checks should be absolute.
- **Evidence:**
  ```php
  // class-mt-evaluation-ajax.php:803
  if (!$assignment_exists) {
      if ($can_evaluate_all) { // True for admins
          MT_Logger::warning('Admin/Manager evaluating without assignment', ...);
          MT_Logger::info('Legacy behavior: allowing evaluation for admin/manager without assignment');
      } else {
          $this->error(__('You are not assigned to evaluate this candidate', 'mobility-trailblazers'));
          return;
      }
  }
  ```
- **Related docs:** `C:\Users\nicol\Desktop\MT-JURY-DASH\docs\ajax-api.md`
- **Next step:** Remove the logic that allows evaluation without a valid assignment, regardless of user role.

---
### P1 (should fix soon)
- **ID:** MT-003
- **Severity:** P1
- **Area:** DB | Architecture
- **File(s):** `C:\Users\nicol\Desktop\MT-JURY-DASH\Plugin\includes\repositories\class-mt-candidate-repository.php`, `C:\Users\nicol\Desktop\MT-JURY-DASH\Plugin\includes\admin\class-mt-import-export.php`
- **What:** The plugin uses both a custom table (`wp_mt_candidates`) and a Custom Post Type (`mt_candidate`) to manage candidate data. The repository pattern uses the custom table, while older functions (like exports) still pull data from the CPT and its postmeta.
- **Why:** This creates two sources of truth for the same data, leading to inconsistencies, bugs, and confusion. It's unclear which data is current, and updates might only be saved to one location. This is a significant maintenance and reliability risk.
- **Evidence:** The `MT_Candidate_Repository` exclusively queries `wp_mt_candidates`. However, `MT_Import_Export::export_candidates()` uses `get_posts(['post_type' => 'mt_candidate'])` and `get_post_meta()`.
- **Related docs:** `C:\Users\nicol\Desktop\MT-JURY-DASH\docs\database-schema.md`, `C:\Users\nicol\Desktop\MT-JURY-DASH\docs\architecture.md`
- **Next step:** Decide on a single source of truth for candidate data and refactor all code to use it, migrating any legacy data if necessary.

- **ID:** MT-004
- **Severity:** P1
- **Area:** i18n
- **File(s):** `C:\Users\nicol\Desktop\MT-JURY-DASH\Plugin\includes\german-translation-compatibility.php`
- **What:** The plugin includes a PHP file that appears to contain raw German translations as a fallback.
- **Why:** This is a highly unconventional and fragile method for internationalization. It bypasses the standard `.mo` file loading process, can cause conflicts with official language packs, is not cache-friendly, and makes managing translations extremely difficult.
- **Evidence:** The file `german-translation-compatibility.php` exists and is required in the main plugin file.
- **Related docs:** None. This is an anti-pattern.
- **Next step:** Remove the PHP-based translation file and ensure all strings are correctly loaded from the `.pot` and `.mo` files using standard WordPress i18n functions.

- **ID:** MT-005
- **Severity:** P1
- **Area:** Redundancy | Maintainability
- **File(s):** `C:\Users\nicol\Desktop\MT-JURY-DASH\Plugin\assets\css\`
- **What:** The CSS directory is filled with numerous files that appear to be backups, hotfixes, or deprecated versions.
- **Why:** This makes it impossible for developers to know which stylesheet is authoritative. It increases the risk of regressions when making changes, bloats the plugin size, and indicates a lack of a coherent asset build process.
- **Evidence:** Files named `candidate-enhanced-v2-backup.css`, `candidate-single-hotfix.css`, `evaluation-fix.css`, `mt_candidate_rollback.css`.
- **Related docs:** `C:\Users\nicol\Desktop\MT-JURY-DASH\docs\css-audit\` (which itself implies styling issues)
- **Next step:** Audit all CSS files, consolidate them into a clear, unified structure, and remove all unused and redundant files.

---
### P2 (nice to fix)
- **ID:** MT-006
- **Severity:** P2
- **Area:** Naming | Consistency
- **File(s):** `C:\Users\nicol\Desktop\MT-JURY-DASH\Plugin\assets\css\mt_candidate_rollback.css`, `C:\Users\nicol\Desktop\MT-JURY-DASH\Plugin\includes\admin\tools\`
- **What:** Naming conventions are not consistently followed. Some files use underscores instead of hyphens, and the directory structure for Elementor widgets is split between two locations.
- **Why:** Inconsistent naming and structure make the codebase harder to navigate and understand, increasing the cognitive load on developers and the chance of errors.
- **Evidence:** `mt_candidate_rollback.css` uses an underscore. Elementor tools exist in both `/includes/elementor/widgets` and `/includes/admin/tools`.
- **Related docs:** Implicit from project standards.
- **Next step:** Rename files and restructure directories to strictly adhere to the documented conventions.

- **ID:** MT-007
- **Severity:** P2
- **Area:** Redundancy
- **File(s):** `C:\Users\nicol\Desktop\MT-JURY-DASH\Plugin\includes\admin\class-mt-import-export.php:500-665`
- **What:** The `MT_Import_Export` class contains two `_deprecated` methods for streaming exports (`export_candidates_stream_deprecated`, `export_evaluations_stream_deprecated`).
- **Why:** Deprecated code adds clutter and can be confusing for developers. If it is truly unused, it should be removed to clean up the codebase.
- **Evidence:**
  ```php
  // class-mt-import-export.php
  /**
   * @deprecated 2.5.41
   */
  private static function export_candidates_stream_deprecated($args = []) { ... }
  ```
- **Related docs:** None.
- **Next step:** Confirm the deprecated methods are no longer used and remove them.

- **ID:** MT-008
- **Severity:** P2
- **Area:** Performance
- **File(s):** `C:\Users\nicol\Desktop\MT-JURY-DASH\Plugin\includes\admin\class-mt-import-export.php:133`
- **What:** The `export_candidates` function uses `get_posts` with `'posts_per_page' => -1`, which loads all candidate posts into memory at once.
- **Why:** This can lead to memory exhaustion and fatal errors on sites with a large number of candidates. The existence of deprecated "streaming" functions suggests this has been a problem in the past.
- **Evidence:**
  ```php
  // class-mt-import-export.php:133
  $query_args = [
      'post_type' => 'mt_candidate',
      'posts_per_page' => -1,
      // ...
  ];
  $candidates = get_posts($query_args);
  ```
- **Related docs:** None.
- **Next step:** Refactor the export functionality to process data in batches to keep memory usage low.

## Broken or incomplete features
- **Feature:** Data Migration (CPT to Custom Table)
  - **Expected per /docs/:** The architecture and database schema docs imply a move to custom tables for performance and clarity, managed by repositories.
  - **Actual:** The migration is incomplete. Core data models exist in two different forms (posts/postmeta and custom tables). Code to handle both co-exists, but it's unclear which is the "master" record.
  - **Reproduction:** Compare the logic in `MT_Candidate_Repository` (uses custom table) with `MT_Import_Export` (uses CPT).

- **Feature:** Elementor Integration
  - **Expected per /docs/:** The plugin should provide a set of functional Elementor widgets.
  - **Actual:** The code for the widgets is scattered and appears to contain non-production-ready code. The `class-mt-elementor-export.php` file, for example, seems more like a developer tool than a user-facing feature. The functionality is not clearly documented for end-users.

## Redundancy & dead code map
- `C:\Users\nicol\Desktop\MT-JURY-DASH\Plugin\assets\css\candidate-enhanced-v2-backup.css`: Backup file, safe to remove.
- `C:\Users\nicol\Desktop\MT-JURY-DASH\Plugin\assets\css\*.hotfix.css`, `*.fix.css`: Multiple hotfix files should be merged into the main stylesheets and removed.
- `C:\Users\nicol\Desktop\MT-JURY-DASH\Plugin\assets\min\`: This entire directory appears to be a redundant artifact from a previous build process.
- `C:\Users\nicol\Desktop\MT-JURY-DASH\Plugin\includes\admin\class-mt-import-export.php` (deprecated methods): The streaming export methods marked as deprecated are safe to remove after confirming they are not called anywhere.
- `C:\Users\nicol\Desktop\MT-JURY-DASH\Plugin\templates\admin\evaluations-inline-fix.php`: Likely dead code from a previous bug fix.

## Consistency checklist
- **Class Naming:** Mostly consistent (`MT_*`).
- **Method Naming:** Mostly consistent (`snake_case`).
- **File Naming:** **Inconsistent.** `mt_candidate_rollback.css` uses underscore. Many PHP files do not follow the `class-` prefix rule.
- **DB Table Naming:** Consistent (`mt_*`).
- **CSS Class Naming:** Mostly consistent (`mt-` prefix), but a full BEM audit is required.
- **Text Domain:** Consistent (`mobility-trailblazers`).
- **Enqueue Rules:** Appears to be handled centrally in `class-mt-public-assets.php` and `class-mt-admin.php`, which is good practice.
- **Template Loader:** Uses a standard `MT_Template_Loader` class, which is good.

## i18n coverage
- **Coverage:** Uncertain without a full string scan. However, the presence of `german-translation-compatibility.php` suggests that standard i18n practices may not have been followed consistently.
- **Missing/Wrong Text Domain:**
  - `C:\Users\nicol\Desktop\MT-JURY-DASH\Plugin\uninstall.php:54`: `error_log('Mobility Trailblazers: Plugin uninstalled. Data preserved as per user settings.');` - This string is not translatable.

## Performance hotspots
- **Query/File Path:** `C:\Users\nicol\Desktop\MT-JURY-DASH\Plugin\includes\admin\class-mt-import-export.php`
  - **Reason:** The export functions load all candidate or evaluation objects into memory at once, which will cause fatal errors on large sites.
  - **Suggested Target:** Implement batched processing for all data exports.
- **Query/File Path:** `C:\Users\nicol\Desktop\MT-JURY-DASH\Plugin\repositories\class-mt-evaluation-repository.php` (and others)
  - **Reason:** Many repository methods that query for data do not use any form of caching (e.g., Transients API).
  - **Suggested Target:** Introduce transient caching for expensive or frequently executed queries, especially for frontend display (e.g., rankings, statistics).

## Appendix A – File inventory
```
C:\USERS\NICOL\DESKTOP\MT-JURY-DASH\PLUGIN
│   LICENSE
│   mobility-trailblazers.php   # Main plugin file, entry point
│   README.md
│   SECURITY.md
│   uninstall.php               # Handles data cleanup on uninstall
│
├───assets
│   ├───css                     # Numerous CSS files, needs cleanup
│   │   ├───min                 # Redundant minified files
│   │   └───v4                  # Incomplete/abandoned v4 styles
│   └───js
│       └───min                 # Minified JS with source maps
├───includes
│   ├───admin                   # Admin area functionality
│   │   └───tools               # Elementor tools, oddly placed
│   ├───ajax                    # AJAX handlers, well-structured
│   ├───cli                     # WP-CLI commands
│   ├───core                    # Core bootstrapping, DI container, autoloader
│   ├───elementor
│   │   └───widgets
│   ├───fixes                   # One-off fixes
│   ├───interfaces              # PHP interfaces for DI
│   ├───legacy                  # Backwards compatibility layer
│   ├───migrations
│   ├───providers               # Service providers for DI
│   ├───public
│   │   └───renderers
│   ├───repositories            # Data access layer
│   ├───services                # Business logic
│   ├───utilities
│   └───widgets
├───languages                   # Standard i18n files
└───templates                   # View files, separated for admin/frontend
```

## Appendix B – Query & hook index
- **Custom Tables:**
  - `wp_mt_candidates`
  - `wp_mt_jury_assignments`
  - `wp_mt_evaluations`
  - `wp_mt_audit_log`
  - `wp_mt_error_log`
- **Registered AJAX Hooks (`wp_ajax_`):**
  - `mt_update_candidate_content`, `mt_get_candidate_content`
  - `mt_export_coaching_report`
  - `mt_export_candidates`, `mt_export_evaluations`, `mt_export_assignments`
  - `mt_upload_import_file`, `mt_get_dashboard_stats`, `mt_clear_data`, `mt_force_db_upgrade`
  - `mt_bulk_candidate_action`
  - `mt_get_jury_assignments`, `mt_get_unassigned_candidates`, `mt_create_assignment`, `mt_remove_assignment`, `mt_bulk_assign`, `mt_clear_all_assignments`, `mt_auto_assign`
  - `mt_submit_evaluation`, `mt_get_evaluation`, `mt_get_candidate_details`, `mt_get_jury_progress`, `mt_get_jury_rankings`, `mt_save_inline_evaluation`
  - `mt_switch_language` (also `wp_ajax_nopriv_`)
- **Registered Shortcodes:**
  - Location: `includes/core/class-mt-shortcodes.php` (requires reading file to list specific shortcodes)
- **Registered Hooks (Actions/Filters):**
  - Numerous hooks are registered throughout the plugin, primarily in the `init()` methods of various classes. Key hooks include `plugins_loaded` for initialization, `admin_menu` for adding pages, and `save_post` for intercepting CPT updates.
