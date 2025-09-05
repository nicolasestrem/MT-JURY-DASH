### **Action Plan: Mobility Trailblazers Plugin Refactoring**

This document outlines the necessary steps to address the security, architectural, and maintainability issues discovered during the code audit. All CSS-related findings are explicitly out of scope.

#### **Phase 1: Critical Security Hardening (Immediate Priority)**

This phase addresses P0 vulnerabilities that pose a direct and immediate risk to the platform.

**1.1. Remediate all SQL Injection Vulnerabilities (Ref: MT-001)**
   - **Objective:** Ensure no user-controllable input can be used to manipulate database queries.
   - **Action Items:** 
     1.  Conduct a full-codebase search for all direct database calls: `$wpdb->query`, `$wpdb->get_row`, `$wpdb->get_results`, etc.
     2.  Systematically refactor every identified query to use `$wpdb->prepare()`. Pay special attention to queries that use variables in the SQL string.
     3.  **Priority Files to Audit:**
         - `C:\Users\nicol\Desktop\MT-JURY-DASH\Plugin\includes\repositories\class-mt-candidate-repository.php`
         - `C:\Users\nicol\Desktop\MT-JURY-DASH\Plugin\includes\admin\class-mt-maintenance-tools.php`
         - `C:\Users\nicol\Desktop\MT-JURY-DASH\Plugin\includes\repositories\class-mt-assignment-repository.php`
         - `C:\Users\nicol\Desktop\MT-JURY-DASH\Plugin\includes\admin\class-mt-coaching.php`
     4.  Remove the `@suppress` comment for `WordPress.DB.PreparedSQL.NotPrepared` once the queries are fixed.

**1.2. Eliminate Security Check Bypass (Ref: MT-002)**
   - **Objective:** Enforce security checks universally, without exceptions for user roles.
   - **Action Items:** 
     1.  Navigate to `C:\Users\nicol\Desktop\MT-JURY-DASH\Plugin\includes\ajax\class-mt-evaluation-ajax.php`.
     2.  Remove the conditional block that allows users with `mt_manage_evaluations` capability (including administrators) to submit an evaluation without a valid candidate assignment.
     3.  The logic should be absolute: if no assignment exists between the jury member and the candidate, the evaluation must be denied with an error, regardless of the user's role.

#### **Phase 2: Architectural Unification & Data Integrity**

This phase resolves the core architectural problem of having two data sources for candidates, which is a major source of bugs and confusion.

**2.1. Establish a Single Source of Truth for Candidate Data (Ref: MT-003)**
   - **Objective:** Consolidate all candidate data into one authoritative source and eliminate the legacy CPT-based storage.
   - **Action Items:** 
     1.  **Decision:** Formally declare the `wp_mt_candidates` custom table as the single source of truth for all candidate data. The `mt_candidate` Custom Post Type will be deprecated.
     2.  **Develop a Migration Script:** Create a one-time WP-CLI command or admin-triggered script that migrates all data from `wp_posts` and `wp_postmeta` (for `post_type = 'mt_candidate'`) into the `wp_mt_candidates` table. This script must handle all fields, including name, organization, description, etc.
     3.  **Refactor Data Access:**
         - Audit the entire plugin for any use of `get_posts`, `get_post_meta`, `update_post_meta`, etc., related to the `mt_candidate` CPT.
         - Replace all such calls with methods from the `MT_Candidate_Repository` class.
         - **Key files to refactor:** `class-mt-import-export.php`, `class-mt-post-types.php`, and various admin page handlers.
     4.  **Deprecate the CPT:** Once all data is migrated and code is refactored, modify the `mt_candidate` CPT registration in `class-mt-post-types.php` to set `'public' => false`, `'show_ui' => false`, and `'show_in_menu' => false` to hide it from the admin UI. Do not remove it entirely until all code paths are confirmed to be updated.

**2.2. Modernize Internationalization (i18n) (Ref: MT-004)**
   - **Objective:** Use standard, reliable WordPress methods for all translations.
   - **Action Items:** 
     1.  Audit the `C:\Users\nicol\Desktop\MT-JURY-DASH\Plugin\includes\german-translation-compatibility.php` file and ensure every string it contains is also present in the `languages/mobility-trailblazers.pot` file and translated in the `de_DE.po` file.
     2.  Once translations are verified, remove the `require_once` for `german-translation-compatibility.php` from the main plugin file.
     3.  Delete the `german-translation-compatibility.php` file.
     4.  Fix any non-translatable strings, such as the one in `uninstall.php`.



#### **Phase 3: Code Cleanup and Maintainability**

This phase addresses the remaining P2 findings to improve the developer experience and reduce future technical debt.

**3.1. Remove Redundant and Deprecated Code (Ref: MT-007)**
   - **Objective:** Simplify the codebase by removing unused code.
   - **Action Items:** 
     1.  Delete the deprecated streaming methods (`export_candidates_stream_deprecated`, `export_evaluations_stream_deprecated`) from `class-mt-import-export.php`.
     2.  Audit and remove unused template files like `evaluations-inline-fix.php`.

**3.2. Enforce Naming and Structural Conventions (Ref: MT-006)**
   - **Objective:** Ensure the entire codebase follows a single, predictable set of rules.
   - **Action Items:** 
     1.  Rename files like `mt_candidate_rollback.css` to `mt-candidate-rollback.css`.
     2.  Consolidate all Elementor-related code into the `/includes/elementor/` directory to resolve the structural confusion.
     3.  Perform a full-codebase review to find and fix any other deviations from the documented naming conventions.
