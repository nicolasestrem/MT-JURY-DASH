# Backend Reference

This document provides a reference for the backend components of the Mobility Trailblazers plugin.

## Core Components

### `MobilityTrailblazers\Core\MT_Plugin`

The main plugin class, responsible for initializing all plugin components, including post types, taxonomies, admin pages, AJAX handlers, and shortcodes.

### `MobilityTrailblazers\Core\MT_Container`

A lightweight dependency injection container that manages the creation and resolution of the plugin's services and repositories.

### `MobilityTrailblazers\Core\MT_Service_Provider`

An abstract base class for service providers. Service providers are used to register services and their dependencies with the DI container.

### `MobilityTrailblazers\Core\MT_Autoloader`

A custom autoloader that loads the plugin's classes as they are needed.

## Services

Services contain the business logic of the plugin.

### `MobilityTrailblazers\Services\MT_Assignment_Service`

Handles the business logic for assigning candidates to jury members.

**Public Methods:**

*   `process(array $data)`: Processes a manual or automatic assignment request.
*   `remove_by_id(int $assignment_id)`: Removes an assignment by its ID.
*   `remove_assignment(int $jury_member_id, int $candidate_id)`: Removes an assignment by jury member and candidate ID.
*   `validate(array $data)`: Validates manual assignment data.
*   `get_errors()`: Returns an array of validation errors.
*   `validate_assignment_distribution(string $method)`: Validates if the assignment distribution is balanced.
*   `rebalance_assignments()`: Rebalances assignments for a more even distribution.
*   `get_distribution_statistics()`: Returns detailed statistics about the current assignment distribution.
*   `get_summary()`: Returns a summary of assignment statistics.
*   `auto_assign(string $method, int $candidates_per_jury)`: Automatically assigns candidates to jury members.

### `MobilityTrailblazers\Services\MT_Candidate_Import_Service`

Provides functionality for importing candidates from an Excel file.

**Public Methods:**

*   `import_from_excel(string $file_path, bool $dry_run)`: Imports candidates from an Excel file.
*   `parse_german_sections(string $description)`: Parses the German description text into sections.
*   `backup_existing_candidates()`: Creates a CSV backup of the existing candidates.
*   `truncate_candidate_data()`: Deletes all candidate data.
*   `import_candidate_photos(string $photos_dir, bool $dry_run)`: Imports and attaches candidate photos.
*   `get_results()`: Returns the results of the import process.

### `MobilityTrailblazers\Services\MT_Diagnostic_Service`

Performs system health checks and provides diagnostic information.

**Public Methods:**

*   `run_full_diagnostic()`: Runs a complete system diagnostic.
*   `run_diagnostic(string $type)`: Runs a specific diagnostic check.
*   `export_diagnostics(array $diagnostics)`: Exports the diagnostic data as a JSON string.

### `MobilityTrailblazers\Services\MT_Evaluation_Service`

Manages the business logic for candidate evaluations.

**Public Methods:**

*   `process(array $data)`: Processes an evaluation submission.
*   `save_draft(array $data)`: Saves an evaluation as a draft.
*   `submit_final(array $data)`: Submits a final evaluation.
*   `save_evaluation(array $data)`: Saves or updates an evaluation.
*   `validate(array $data)`: Validates evaluation data.
*   `get_errors()`: Returns an array of validation errors.
*   `get_criteria()`: Returns the evaluation criteria.
*   `get_jury_progress(int $jury_member_id)`: Returns the evaluation progress for a jury member.
*   `get_assignment_progress(int $jury_member_id)`: Returns the assignment progress for a jury member.

## Repositories

Repositories are responsible for all database interactions.

### `MobilityTrailblazers\Repositories\MT_Assignment_Repository`

Handles database operations for jury assignments in the `wp_mt_jury_assignments` table.

**Public Methods:**

*   `find(int $id)`: Finds an assignment by its ID.
*   `find_all(array $args)`: Finds all assignments matching the given arguments.
*   `create(array $data)`: Creates a new assignment.
*   `update(int $id, array $data)`: Updates an assignment.
*   `delete(int $id, bool $cascade_evaluations)`: Deletes an assignment.
*   `exists(int $jury_member_id, int $candidate_id)`: Checks if an assignment exists.
*   `get_by_jury_member(int $jury_member_id)`: Gets all assignments for a jury member.
*   `get_by_candidate(int $candidate_id)`: Gets all assignments for a candidate.
*   `get_by_jury_and_candidate(int $jury_member_id, int $candidate_id)`: Gets an assignment by jury member and candidate.
*   `bulk_create(array $assignments)`: Creates multiple assignments at once.
*   `delete_by_jury_member(int $jury_member_id, bool $cascade_evaluations)`: Deletes all assignments for a jury member.
*   `delete_by_candidate(int $candidate_id)`: Deletes all assignments for a candidate.
*   `get_statistics()`: Gets statistics about the assignments.
*   `get_unassigned_candidates()`: Gets a list of unassigned candidates.
*   `clear_all(bool $cascade_evaluations)`: Deletes all assignments.
*   `count(array $args)`: Counts the total number of assignments.
*   `cleanup_orphaned_assignments()`: Removes assignments for non-existent candidates or jury members.
*   `verify_integrity()`: Verifies the integrity of the assignments table.
*   `auto_distribute(array $options)`: Automatically distributes assignments.
*   `rebalance_assignments()`: Rebalances assignments for a more even distribution.

### `MobilityTrailblazers\Repositories\MT_Audit_Log_Repository`

Manages the audit log in the `wp_mt_audit_log` table.

**Public Methods:**

*   `get_logs(array $args)`: Gets a paginated list of audit logs.
*   `get_by_id(int $id)`: Gets an audit log by its ID.
*   `get_unique_actions()`: Gets a list of unique actions from the log.
*   `get_unique_object_types()`: Gets a list of unique object types from the log.
*   `cleanup_old_logs(int $days)`: Deletes logs older than a specified number of days.
*   `get_statistics()`: Gets statistics about the audit log.
*   `log(...)`: Logs an audit event.
*   `get_by_user(int $user_id, int $limit)`: Gets logs for a specific user.
*   `get_by_object(string $object_type, int $object_id, int $limit)`: Gets logs for a specific object.
*   `get_by_action(string $action, int $limit)`: Gets logs for a specific action.

### `MobilityTrailblazers\Repositories\MT_Candidate_Repository`

Handles database operations for candidates in the `wp_mt_candidates` table.

**Public Methods:**

*   `find(int $id)`: Finds a candidate by ID.
*   `find_all(array $args)`: Finds all candidates matching the given arguments.
*   `create(array $data)`: Creates a new candidate.
*   `update(int $id, array $data)`: Updates a candidate.
*   `delete(int $id)`: Deletes a candidate.
*   `find_by_slug(string $slug)`: Finds a candidate by slug.
*   `find_by_name(string $name)`: Finds a candidate by name.
*   `find_by_post_id(int $post_id)`: Finds a candidate by post ID.
*   `update_description_sections(int $id, array $sections)`: Updates the description sections for a candidate.
*   `truncate()`: Deletes all candidates.
*   `count()`: Counts the total number of candidates.

### `MobilityTrailblazers\Repositories\MT_Evaluation_Repository`

Manages database operations for evaluations in the `wp_mt_evaluations` table.

**Public Methods:**

*   `find(int $id)`: Finds an evaluation by ID.
*   `find_all(array $args)`: Finds all evaluations matching the given arguments.
*   `create(array $data)`: Creates a new evaluation.
*   `update(int $id, array $data)`: Updates an evaluation.
*   `delete(int $id)`: Deletes an evaluation.
*   `exists(int $jury_member_id, int $candidate_id)`: Checks if an evaluation exists.
*   `get_by_jury_member(int $jury_member_id)`: Gets all evaluations for a jury member.
*   `get_by_candidate(int $candidate_id)`: Gets all evaluations for a candidate.
*   `get_average_score_for_candidate(int $candidate_id)`: Gets the average score for a candidate.
*   `find_by_jury_and_candidate(int $jury_member_id, int $candidate_id)`: Finds an evaluation by jury member and candidate.
*   `save(array $data)`: Saves or updates an evaluation.
*   `get_statistics(array $args)`: Gets statistics about the evaluations.
*   `get_top_candidates(int $limit, string $category)`: Gets the top-ranked candidates.
*   `get_ranked_candidates_for_jury(int $jury_member_id, int $limit)`: Gets the ranked candidates for a specific jury member.
*   `get_overall_rankings(int $limit)`: Gets the overall candidate rankings.
*   `clear_all_evaluation_caches()`: Clears all evaluation-related caches.
*   `delete_orphaned_evaluations(...)`: Deletes evaluations that have no corresponding assignment.
*   `can_delete(int $id)`: Checks if an evaluation can be safely deleted.
*   `force_delete(int $id)`: Forcibly deletes an evaluation.
*   `sync_with_assignments()`: Synchronizes evaluations with assignments.
