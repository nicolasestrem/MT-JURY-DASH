# Phase 1 Security Hardening

This document details the changes made during the first phase of the security hardening initiative.

## 1.1 SQL Injection Vulnerabilities (Ref: MT-001)

Multiple files were identified as having raw SQL queries, making them vulnerable to SQL Injection. All identified queries have been refactored to use `$wpdb->prepare()`.

### Changes Made:

- **`includes/repositories/class-mt-candidate-repository.php`**
  - The `find_all()` method was refactored to use `$wpdb->prepare()` for all its parameters, including the `ORDER BY` clause which is now validated against an allow-list.
  - The `truncate()` and `count()` methods were updated to use safer alternatives.

- **`includes/admin/class-mt-maintenance-tools.php`**
  - All methods using raw SQL queries (`optimize_tables`, `repair_tables`, `repair_orphaned_data`, `cleanup_old_data`, `rebuild_indexes`, `clear_all_caches`, `clear_transients`, `factory_reset`, `export_all_data`) have been updated to use `$wpdb->prepare()` or safer alternatives.

- **`includes/repositories/class-mt-assignment-repository.php`**
  - All methods with raw SQL queries (`find_all`, `get_statistics`, `get_unassigned_candidates`, `clear_all`, `clear_all_assignment_caches`, `count`, `cleanup_orphaned_assignments`, `verify_integrity`, `rebalance_assignments`) have been refactored to use `$wpdb->prepare()`.

- **`includes/admin/class-mt-coaching.php`**
  - The `get_coaching_statistics()` method was updated to use `$wpdb->prepare()` for the `IN` clause.

## 1.2. Security Check Bypass (Ref: MT-002)

The security check bypass in `includes/ajax/class-mt-evaluation-ajax.php` has been removed.

### Changes Made:

- **`includes/ajax/class-mt-evaluation-ajax.php`**
  - The conditional block in the `save_inline_evaluation()` method that allowed administrators and users with the `mt_manage_evaluations` capability to bypass the assignment check has been removed. The logic is now absolute: an evaluation can only be submitted if a valid assignment exists between the jury member and the candidate.
