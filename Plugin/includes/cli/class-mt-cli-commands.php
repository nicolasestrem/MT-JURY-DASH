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
