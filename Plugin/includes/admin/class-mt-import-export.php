<?php
/**
 * Export Handler
 *
 * @package MobilityTrailblazers
 * @since 2.2.23
 */

namespace MobilityTrailblazers\Admin;

use MobilityTrailblazers\Core\MT_Logger;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Import_Export
 *
 * Handles CSV export for candidates, evaluations, and assignments
 */
class MT_Import_Export {
    
    /**
     * Initialize the export handler
     */
    public static function init() {
        // Admin post handlers for export functionality
        add_action('admin_post_mt_export_candidates', [__CLASS__, 'export_candidates']);
        add_action('admin_post_mt_export_evaluations', [__CLASS__, 'export_evaluations']);
        add_action('admin_post_mt_export_assignments', [__CLASS__, 'export_assignments']);
    }
    
    /**
     * Export candidates to CSV
     */
    public static function export_candidates() {
        // Verify nonce and permissions
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'mt_export_candidates')) {
            wp_die(__('Security check failed', 'mobility-trailblazers'));
        }
        if (!current_user_can('mt_export_data')) {
            wp_die(__('Permission denied.', 'mobility-trailblazers'));
        }

        try {
            $candidate_repo = new \MobilityTrailblazers\Repositories\MT_Candidate_Repository();
            $candidates = $candidate_repo->find_all(['limit' => -1]);

            if (empty($candidates)) {
                MT_Logger::warning('No candidates found for export');
            }

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=candidates-' . date('Y-m-d') . '.csv');
            header('Pragma: no-cache');
            header('Expires: 0');

            $output = fopen('php://output', 'w');
            if ($output === false) {
                MT_Logger::error('Failed to open output stream for candidates export');
                wp_die(__('Export failed: Unable to create output file.', 'mobility-trailblazers'));
            }

            fputcsv($output, [
                'ID',
                'Name',
                'Company',
                'Position',
                'Country',
                'LinkedIn URL',
                'Website URL',
                'Article URL',
                'Created Date',
                'Modified Date'
            ]);

            foreach ($candidates as $candidate) {
                fputcsv($output, [
                    $candidate->id,
                    $candidate->name,
                    $candidate->organization,
                    $candidate->position,
                    $candidate->country,
                    $candidate->linkedin_url,
                    $candidate->website_url,
                    $candidate->article_url,
                    self::format_date_iso8601($candidate->created_at),
                    self::format_date_iso8601($candidate->updated_at)
                ]);
            }

            fclose($output);

            MT_Logger::info('Candidates export completed successfully', [
                'count' => count($candidates),
                'user_id' => get_current_user_id()
            ]);

        } catch (\Exception $e) {
            MT_Logger::error('Candidates export failed', [
                'error' => $e->getMessage(),
                'user_id' => get_current_user_id()
            ]);
            wp_die(__('Export failed: ', 'mobility-trailblazers') . $e->getMessage());
        }

        exit;
    }
    
    /**
     * Export evaluations to CSV
     */
    public static function export_evaluations() {
        global $wpdb;
        
        // Verify nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'mt_export_evaluations')) {
            wp_die(__('Security check failed', 'mobility-trailblazers'));
        }
        
        // Check permission - use proper capability
        if (!current_user_can('mt_export_data')) {
            MT_Logger::security_event('Unauthorized export attempt - evaluations', [
                'user_id' => get_current_user_id(),
                'user_login' => wp_get_current_user()->user_login,
                'export_type' => 'evaluations'
            ]);
            wp_die(__('Permission denied. You need export data capability.', 'mobility-trailblazers'));
        }
        
        try {
            // Get ALL evaluations with proper jury member data
            $table_name = $wpdb->prefix . 'mt_evaluations';
            $evaluations = $wpdb->get_results("
                SELECT e.*, 
                       c.post_title as candidate_name, 
                       COALESCE(j.post_title, u.display_name, CONCAT('User #', e.jury_member_id)) as jury_member
                FROM {$table_name} e
                LEFT JOIN {$wpdb->posts} c ON e.candidate_id = c.ID AND c.post_type = 'mt_candidate'
                LEFT JOIN {$wpdb->posts} j ON e.jury_member_id = j.ID AND j.post_type = 'mt_jury_member'
                LEFT JOIN {$wpdb->users} u ON e.jury_member_id = u.ID
                WHERE e.status IN ('completed', 'draft', 'in_progress')
                ORDER BY e.jury_member_id, e.candidate_id, e.created_at DESC
            ");
            
            // Check for database errors
            if ($wpdb->last_error) {
                MT_Logger::database_error('SELECT', 'mt_evaluations', $wpdb->last_error);
                wp_die(__('Export failed: Database error occurred.', 'mobility-trailblazers'));
            }
            
            if (empty($evaluations)) {
                MT_Logger::warning('No evaluations found for export');
            }
            
            // Set headers for download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=evaluations-' . date('Y-m-d') . '.csv');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            // Remove BOM - causes parsing issues
            
            // Open output stream with error checking
            $output = fopen('php://output', 'w');
            if ($output === false) {
                MT_Logger::error('Failed to open output stream for evaluations export');
                wp_die(__('Export failed: Unable to create output file.', 'mobility-trailblazers'));
            }
        
        // Write headers
        fputcsv($output, [
            'Candidate',
            'Jury Member',
            'Criterion 1',
            'Criterion 2',
            'Criterion 3',
            'Criterion 4',
            'Criterion 5',
            'Comments',
            'Status',
            'Date'
        ]);
        
        // Write data
        foreach ($evaluations as $evaluation) {
            fputcsv($output, [
                $evaluation->candidate_name,
                $evaluation->jury_member,
                $evaluation->courage_score,
                $evaluation->innovation_score,
                $evaluation->implementation_score,
                $evaluation->relevance_score,
                $evaluation->visibility_score,
                $evaluation->comments,
                $evaluation->status,
                self::format_date_iso8601($evaluation->created_at)
            ]);
        }
        
            fclose($output);
            
            MT_Logger::info('Evaluations export completed successfully', [
                'count' => count($evaluations),
                'user_id' => get_current_user_id()
            ]);
            
        } catch (\Exception $e) {
            MT_Logger::error('Evaluations export failed', [
                'error' => $e->getMessage(),
                'user_id' => get_current_user_id()
            ]);
            wp_die(__('Export failed: ', 'mobility-trailblazers') . $e->getMessage());
        }
        
        exit;
    }
    
    /**
     * Export assignments to CSV
     */
    public static function export_assignments() {
        global $wpdb;
        
        // Verify nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'mt_export_assignments')) {
            wp_die(__('Security check failed', 'mobility-trailblazers'));
        }
        
        // Check permission - use proper capability
        if (!current_user_can('mt_export_data')) {
            MT_Logger::security_event('Unauthorized export attempt - assignments', [
                'user_id' => get_current_user_id(),
                'user_login' => wp_get_current_user()->user_login,
                'export_type' => 'assignments'
            ]);
            wp_die(__('Permission denied. You need export data capability.', 'mobility-trailblazers'));
        }
        
        try {
            // Get assignments with proper data
            $table_name = $wpdb->prefix . 'mt_jury_assignments';
            $assignments = $wpdb->get_results("
                SELECT a.id,
                       a.jury_member_id,
                       a.candidate_id,
                       a.assigned_at,
                       a.assigned_by,
                       c.post_title as candidate_name,
                       COALESCE(j.post_title, u.display_name, CONCAT('User #', a.jury_member_id)) as jury_member
                FROM {$table_name} a
                LEFT JOIN {$wpdb->posts} c ON a.candidate_id = c.ID AND c.post_type = 'mt_candidate'
                LEFT JOIN {$wpdb->posts} j ON a.jury_member_id = j.ID AND j.post_type = 'mt_jury_member'
                LEFT JOIN {$wpdb->users} u ON a.jury_member_id = u.ID
                ORDER BY a.jury_member_id, a.candidate_id, a.assigned_at DESC
            ");
            
            // Check for database errors
            if ($wpdb->last_error) {
                MT_Logger::database_error('SELECT', 'mt_jury_assignments', $wpdb->last_error);
                wp_die(__('Export failed: Database error occurred.', 'mobility-trailblazers'));
            }
            
            if (empty($assignments)) {
                MT_Logger::warning('No assignments found for export');
            }
            
            // Set headers for download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=assignments-' . date('Y-m-d') . '.csv');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            // Remove BOM - causes parsing issues
            
            // Open output stream with error checking
            $output = fopen('php://output', 'w');
            if ($output === false) {
                MT_Logger::error('Failed to open output stream for assignments export');
                wp_die(__('Export failed: Unable to create output file.', 'mobility-trailblazers'));
            }
        
        // Write headers
        fputcsv($output, [
            'Jury Member',
            'Candidate',
            'Date Assigned',
            'Assigned By'
        ]);
        
        // Write data
        foreach ($assignments as $assignment) {
            fputcsv($output, [
                $assignment->jury_member ?: 'Unknown',
                $assignment->candidate_name ?: 'Unknown',
                self::format_date_iso8601($assignment->assigned_at),
                $assignment->assigned_by ?: ''
            ]);
        }
        
            fclose($output);
            
            MT_Logger::info('Assignments export completed successfully', [
                'count' => count($assignments),
                'user_id' => get_current_user_id()
            ]);
            
        } catch (\Exception $e) {
            MT_Logger::error('Assignments export failed', [
                'error' => $e->getMessage(),
                'user_id' => get_current_user_id()
            ]);
            wp_die(__('Export failed: ', 'mobility-trailblazers') . $e->getMessage());
        }
        
        exit;
    }
    
    /**
     * Export candidates with streaming for memory optimization
     * DEPRECATED - Use export_candidates() instead
     *
     * @param array $args Export arguments
     * @return void Outputs CSV directly
     * @since 2.2.28
     * @deprecated 2.5.41
     */
    private static function export_candidates_stream_deprecated($args = []) {
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="candidates-' . date('Y-m-d-His') . '.csv"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Remove BOM - causes parsing issues
        
        // Write headers
        $headers = [
            'ID',
            'Name',
            'Organisation',
            'Position',
            'Category',
            'Status',
            'LinkedIn',
            'Website',
            'Description',
            'Created Date',
            'Modified Date'
        ];
        fputcsv($output, $headers);
        
        // Query in batches to avoid memory issues
        $offset = 0;
        $batch_size = 100;
        
        while (true) {
            $candidates = get_posts([
                'post_type' => 'mt_candidate',
                'posts_per_page' => $batch_size,
                'offset' => $offset,
                'post_status' => 'any',
                'orderby' => 'ID',
                'order' => 'ASC'
            ]);
            
            if (empty($candidates)) {
                break;
            }
            
            foreach ($candidates as $candidate) {
                $row = [
                    $candidate->ID,
                    $candidate->post_title,
                    get_post_meta($candidate->ID, '_mt_organization', true),
                    get_post_meta($candidate->ID, '_mt_position', true),
                    get_post_meta($candidate->ID, '_mt_category_type', true),
                    $candidate->post_status,
                    get_post_meta($candidate->ID, '_mt_linkedin_url', true),
                    get_post_meta($candidate->ID, '_mt_website_url', true),
                    wp_strip_all_tags($candidate->post_content),
                    $candidate->post_date,
                    $candidate->post_modified
                ];
                fputcsv($output, $row);
                
                // Free memory
                unset($row);
            }
            
            $offset += $batch_size;
            
            // Clear WordPress object cache
            wp_cache_flush();
            
            // Prevent timeout on large exports
            if (function_exists('set_time_limit')) {
                set_time_limit(30);
            }
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Export evaluations with streaming for memory optimization
     * DEPRECATED - Use export_evaluations() instead
     *
     * @param array $args Export arguments
     * @return void Outputs CSV directly
     * @since 2.2.28
     * @deprecated 2.5.41
     */
    private static function export_evaluations_stream_deprecated($args = []) {
        global $wpdb;
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="evaluations-' . date('Y-m-d-His') . '.csv"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Remove BOM - causes parsing issues
        
        // Write headers
        $headers = [
            'Evaluation ID',
            'Jury Member',
            'Candidate',
            'Criterion 1',
            'Criterion 2',
            'Criterion 3',
            'Criterion 4',
            'Criterion 5',
            'Total Score',
            'Comments',
            'Status',
            'Created Date'
        ];
        fputcsv($output, $headers);
        
        // Query in batches using direct SQL for efficiency
        $table_name = $wpdb->prefix . 'mt_evaluations';
        $offset = 0;
        $batch_size = 100;
        
        while (true) {
            $evaluations = $wpdb->get_results($wpdb->prepare(
                "SELECT e.*, 
                        u.display_name as jury_name,
                        p.post_title as candidate_name
                 FROM {$table_name} e
                 LEFT JOIN {$wpdb->users} u ON e.jury_member_id = u.ID
                 LEFT JOIN {$wpdb->posts} p ON e.candidate_id = p.ID
                 ORDER BY e.id ASC
                 LIMIT %d OFFSET %d",
                $batch_size,
                $offset
            ));
            
            if (empty($evaluations)) {
                break;
            }
            
            foreach ($evaluations as $eval) {
                $total_score = $eval->courage_score + $eval->innovation_score + 
                              $eval->implementation_score + $eval->relevance_score + $eval->visibility_score;
                
                $row = [
                    $eval->id,
                    $eval->jury_name,
                    $eval->candidate_name,
                    $eval->courage_score,
                    $eval->innovation_score,
                    $eval->implementation_score,
                    $eval->relevance_score,
                    $eval->visibility_score,
                    $total_score,
                    $eval->comments,
                    $eval->status,
                    $eval->created_at
                ];
                fputcsv($output, $row);
                
                // Free memory
                unset($row);
            }
            
            $offset += $batch_size;
            
            // Prevent timeout on large exports
            if (function_exists('set_time_limit')) {
                set_time_limit(30);
            }
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Format date to ISO 8601 standard (Y-m-d H:i:s)
     * Handles null values and various input formats
     *
     * @param string|null $date Date to format
     * @return string Formatted date or empty string
     * @since 2.5.41
     */
    private static function format_date_iso8601($date) {
        if (empty($date) || $date === '0000-00-00 00:00:00') {
            return '';
        }
        
        try {
            // Convert to DateTime object and format consistently
            $datetime = new \DateTime($date);
            return $datetime->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            MT_Logger::warning('Date formatting failed', [
                'input_date' => $date,
                'error' => $e->getMessage()
            ]);
            return $date; // Return original if formatting fails
        }
    }
}

// Initialize the class
MT_Import_Export::init();