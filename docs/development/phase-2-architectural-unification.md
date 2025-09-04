# Phase 2 Architectural Unification

This document details the changes made during the second phase of the plugin refactoring.

## 2.1 Single Source of Truth for Candidate Data (Ref: MT-003)

The primary goal of this phase was to eliminate the dual data storage for candidates (both in a Custom Post Type and a custom database table), which was a significant source of bugs and inconsistency.

### Changes Made:

- **Migration Script Created:**
  - A new WP-CLI script has been created at `Plugin/scripts/migrations/migrate-cpt-to-table.php`.
  - This script provides the `wp mt migrate_candidates` command to migrate all candidate data from the `mt_candidate` CPT (`wp_posts` and `wp_postmeta` tables) to the `wp_mt_candidates` custom table.
  - The script includes a `--dry-run` flag to allow for safe testing before performing the actual migration.

- **Admin UI for Migration:**
  - For users without WP-CLI access, a temporary admin page has been added under **Mobility Trailblazers -> Data Migration**.
  - This UI allows triggering the migration process with a button, including a dry-run option.
  - **Fixes to Admin UI:**
    - Corrected the redirect URL after migration to ensure proper page display (`admin.php` instead of `tools.php`).
    - Implemented a check to prevent duplicate entries during migration, making the process idempotent and safe to re-run. Existing candidates in the `wp_mt_candidates` table will be skipped.

- **Data Access Refactoring:**
  - The `export_candidates()` method in `Plugin/includes/admin/class-mt-import-export.php` has been refactored.
  - It no longer uses `get_posts` or `get_post_meta` to fetch candidate data. Instead, it now uses the `MT_Candidate_Repository` to fetch data directly from the `wp_mt_candidates` custom table.

- **Candidate CPT Deprecated:**
  - The `mt_candidate` Custom Post Type has been deprecated.
  - In `Plugin/includes/core/class-mt-post-types.php`, the CPT registration has been modified to hide it from the admin UI (`show_ui` => `false`), from the public (`public` => `false`), and from the menu (`show_in_menu` => `false`).
  - The meta box for candidate details has also been removed.

## 2.2 Internationalization (i18n) Modernization (Ref: MT-004)

This task aimed to remove non-standard i18n practices.

### Changes Made:

- **Removed PHP Translation Fallback:**
  - The `require_once` call for `german-translation-compatibility.php` has been removed from `Plugin/mobility-trailblazers.php`.
  - The `german-translation-compatibility.php` file has been deleted from the repository.
  - All translations must now be handled through the standard `.po` and `.mo` files.

- **Fixed Non-Translatable String:**
  - The hardcoded `error_log` message in `Plugin/uninstall.php` has been wrapped in a `__()` function to make it translatable.

## General Code Quality Improvements

### Fixes for `wpdb::prepare` Incorrect Usage:

- Addressed instances where `wpdb::prepare()` was incorrectly used with static SQL queries (queries without dynamic placeholders).
- These calls have been replaced with direct `$wpdb->query()` or `$wpdb->get_results()` calls, as `prepare` is only necessary when sanitizing dynamic input within a query.
- **Affected Files:**
  - `Plugin/includes/admin/class-mt-maintenance-tools.php`
  - `Plugin/includes/repositories/class-mt-candidate-repository.php` (initial fixes)
  - `Plugin/includes/repositories/class-mt-assignment-repository.php` (initial fixes)