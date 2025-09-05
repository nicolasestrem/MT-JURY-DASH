<?php
/**
 * WP-CLI Commands
 *
 * @package MobilityTrailblazers
 * @since 2.5.26
 */

namespace MobilityTrailblazers\CLI;

// Import service has been removed
use WP_CLI;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_CLI_Commands
 *
 * WP-CLI commands for Mobility Trailblazers
 */
class MT_CLI_Commands {
    
    /**
     * Import functionality has been removed
     * Use the export functionality or manual candidate creation instead
     */
    
    // display_results method removed with import functionality
    
    /**
     * Show database upgrade status
     *
     * ## EXAMPLES
     *
     *     wp mt db-upgrade
     *
     * @when after_wp_load
     */
    public function db_upgrade($args, $assoc_args) {
        WP_CLI::log('Running database upgrade...');
        
        require_once plugin_dir_path(__FILE__) . '../core/class-mt-database-upgrade.php';
        \MobilityTrailblazers\Core\MT_Database_Upgrade::run();
        
        global $wpdb;
        $table = $wpdb->prefix . 'mt_candidates';
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        
        if ($exists) {
            WP_CLI::success("Candidates table created successfully: $table");
            
            // Show table structure
            $columns = $wpdb->get_results("SHOW COLUMNS FROM $table");
            $column_data = [];
            foreach ($columns as $column) {
                $column_data[] = [
                    'Field' => $column->Field,
                    'Type' => $column->Type,
                    'Null' => $column->Null,
                    'Key' => $column->Key
                ];
            }
            
            WP_CLI::log('');
            WP_CLI::log('Table Structure:');
            \WP_CLI\Utils\format_items('table', $column_data, ['Field', 'Type', 'Null', 'Key']);
        } else {
            WP_CLI::error("Failed to create candidates table");
        }
    }
    
    /**
     * Migrate candidates from CPT to custom table
     *
     * ## OPTIONS
     *
     * [--batch-size=<size>]
     * : Number of candidates to process per batch. Default: 100
     *
     * [--dry-run]
     * : Run migration without making changes
     *
     * [--verify]
     * : Only verify migration integrity without migrating
     *
     * ## EXAMPLES
     *
     *     wp mt migrate-candidates
     *     wp mt migrate-candidates --dry-run
     *     wp mt migrate-candidates --batch-size=50
     *     wp mt migrate-candidates --verify
     *
     * @when after_wp_load
     */
    public function migrate_candidates($args, $assoc_args) {
        require_once plugin_dir_path(__FILE__) . '../migrations/class-mt-cpt-to-table-migration.php';
        
        $migration = new \MobilityTrailblazers\Migrations\MT_CPT_To_Table_Migration();
        
        // Check if we're only verifying
        if (isset($assoc_args['verify'])) {
            WP_CLI::log('Verifying migration integrity...');
            $results = $migration->verify_migration();
            
            if ($results['success']) {
                WP_CLI::success('Migration verification passed!');
            } else {
                WP_CLI::warning('Migration verification found issues:');
            }
            
            // Display verification results
            foreach ($results['checks'] as $check_name => $check_data) {
                WP_CLI::log('');
                WP_CLI::log(ucfirst(str_replace('_', ' ', $check_name)) . ':');
                
                if (is_array($check_data)) {
                    foreach ($check_data as $key => $value) {
                        if (is_array($value)) {
                            WP_CLI::log('  ' . $key . ': ' . json_encode($value));
                        } else {
                            WP_CLI::log('  ' . $key . ': ' . $value);
                        }
                    }
                }
            }
            
            return;
        }
        
        // Prepare migration arguments
        $migration_args = [
            'batch_size' => isset($assoc_args['batch-size']) ? intval($assoc_args['batch-size']) : 100,
            'dry_run' => isset($assoc_args['dry-run'])
        ];
        
        if ($migration_args['dry_run']) {
            WP_CLI::log('Running migration in DRY RUN mode - no changes will be made');
        }
        
        WP_CLI::log('Starting candidate migration from CPT to custom table...');
        WP_CLI::log('Batch size: ' . $migration_args['batch_size']);
        
        // Run migration
        $results = $migration->run($migration_args);
        
        // Display results
        WP_CLI::log('');
        WP_CLI::log('Migration Results:');
        WP_CLI::log('  Total candidates: ' . $results['total']);
        WP_CLI::log('  Successfully migrated: ' . $results['migrated']);
        WP_CLI::log('  Skipped (already migrated): ' . $results['skipped']);
        WP_CLI::log('  Failed: ' . $results['failed']);
        
        if (!empty($results['errors'])) {
            WP_CLI::log('');
            WP_CLI::warning('Errors encountered:');
            foreach ($results['errors'] as $error) {
                WP_CLI::log('  - ' . $error);
            }
        }
        
        if ($results['failed'] == 0) {
            WP_CLI::success('Migration completed successfully!');
            
            if (!$migration_args['dry_run']) {
                WP_CLI::log('');
                WP_CLI::log('Running verification...');
                $verify_results = $migration->verify_migration();
                
                if ($verify_results['success']) {
                    WP_CLI::success('Migration verification passed!');
                } else {
                    WP_CLI::warning('Migration completed but verification found issues. Please review.');
                }
            }
        } else {
            WP_CLI::error('Migration completed with errors. Please review and retry failed items.');
        }
    }
    
    /**
     * List all candidates
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Output format. Default: table
     *
     * ## EXAMPLES
     *
     *     wp mt list-candidates
     *     wp mt list-candidates --format=json
     *
     * @when after_wp_load
     */
    public function list_candidates($args, $assoc_args) {
        $format = $assoc_args['format'] ?? 'table';
        
        $repository = new \MobilityTrailblazers\Repositories\MT_Candidate_Repository();
        $candidates = $repository->find_all();
        
        if (empty($candidates)) {
            WP_CLI::log('No candidates found.');
            return;
        }
        
        $data = [];
        foreach ($candidates as $candidate) {
            $sections = $candidate->description_sections ?? [];
            $section_count = 0;
            foreach ($sections as $content) {
                if (!empty($content)) {
                    $section_count++;
                }
            }
            
            $data[] = [
                'ID' => $candidate->id,
                'Name' => $candidate->name,
                'Organization' => $candidate->organization,
                'Position' => $candidate->position,
                'Country' => $candidate->country,
                'Sections' => $section_count . '/6',
                'Has Photo' => $candidate->photo_attachment_id ? 'Yes' : 'No'
            ];
        }
        
        \WP_CLI\Utils\format_items($format, $data, ['ID', 'Name', 'Organization', 'Position', 'Country', 'Sections', 'Has Photo']);
        
        WP_CLI::log('');
        WP_CLI::success('Total candidates: ' . count($candidates));
    }
}
