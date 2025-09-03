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
        global $wpdb; // CRITICAL FIX: Add missing global declaration
        
        // Verify nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'mt_export_candidates')) {
            wp_die(__('Security check failed', 'mobility-trailblazers'));
        }
        
        // Check permission - use proper capability
        if (!current_user_can('mt_export_data')) {
            MT_Logger::security_event('Unauthorized export attempt - candidates', [
                'user_id' => get_current_user_id(),
                'user_login' => wp_get_current_user()->user_login,
                'export_type' => 'candidates'
            ]);
            wp_die(__('Permission denied. You need export data capability.', 'mobility-trailblazers'));
        }
        
        try {
            $specific_candidates = [];
            $filename_suffix = '';
            
            // Check if specific candidate IDs were provided via transient (from bulk export)
            if (isset($_GET['export_key'])) {
                $export_key = sanitize_text_field($_GET['export_key']);
                
                // Verify the transient key belongs to the current user for security
                if (strpos($export_key, 'mt_export_candidates_' . get_current_user_id() . '_') === 0) {
                    $specific_candidates = get_transient($export_key);
                    
                    // Delete transient immediately after use for security
                    delete_transient($export_key);
                    
                    if ($specific_candidates && is_array($specific_candidates)) {
                        // Validate all IDs are integers and belong to mt_candidate post type
                        $specific_candidates = array_map('intval', $specific_candidates);
                        $specific_candidates = array_filter($specific_candidates, function($id) {
                            return $id > 0 && get_post_type($id) === 'mt_candidate';
                        });
                        
                        if (!empty($specific_candidates)) {
                            $filename_suffix = '-selected';
                            MT_Logger::info('Bulk export of selected candidates', [
                                'count' => count($specific_candidates),
                                'candidate_ids' => $specific_candidates,
                                'user_id' => get_current_user_id()
                            ]);
                        } else {
                            MT_Logger::warning('Invalid candidate IDs provided for bulk export', [
                                'user_id' => get_current_user_id()
                            ]);
                            wp_die(__('Invalid candidate selection for export.', 'mobility-trailblazers'));
                        }
                    } else {
                        MT_Logger::warning('Export transient expired or invalid', [
                            'export_key' => $export_key,
                            'user_id' => get_current_user_id()
                        ]);
                        wp_die(__('Export session expired. Please try again.', 'mobility-trailblazers'));
                    }
                } else {
                    MT_Logger::security_event('Invalid export key attempted', [
                        'export_key' => $export_key,
                        'user_id' => get_current_user_id()
                    ]);
                    wp_die(__('Security check failed - invalid export key.', 'mobility-trailblazers'));
                }
            }
            
            // Build query arguments
            $query_args = [
                'post_type' => 'mt_candidate',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
                'post_status' => 'any'
            ];
            
            // If specific candidates were requested, filter by them
            if (!empty($specific_candidates)) {
                $query_args['post__in'] = $specific_candidates;
            }
            
            // Get candidates
            $candidates = get_posts($query_args);
            
            if (empty($candidates)) {
                MT_Logger::warning('No candidates found for export');
            }
            
            // Set headers for download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=candidates' . $filename_suffix . '-' . date('Y-m-d') . '.csv');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            // Remove BOM - causes parsing issues
            
            // Open output stream with error checking
            $output = fopen('php://output', 'w');
            if ($output === false) {
                MT_Logger::error('Failed to open output stream for candidates export');
                wp_die(__('Export failed: Unable to create output file.', 'mobility-trailblazers'));
            }
        
        // Write headers with consistent structure
        fputcsv($output, [
            'ID',
            'Name',
            'Company',
            'Category',
            'Description',
            'Innovation',
            'Website',
            'LinkedIn',
            'Email',
            'Status',
            'Created Date',
            'Modified Date'
        ]);
        
        // Optimize meta data fetching - get all meta at once
        $candidate_ids = wp_list_pluck($candidates, 'ID');
        if (!empty($candidate_ids)) {
            // SECURITY FIX: Properly handle IN clause to prevent SQL injection
            // Ensure all IDs are integers
            $candidate_ids = array_map('intval', $candidate_ids);
            
            // Build the query with proper placeholders
            $placeholders = array_fill(0, count($candidate_ids), '%d');
            $in_placeholders = implode(',', $placeholders);
            
            // Build the complete query with all parameters
            $query = "SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta} 
                     WHERE post_id IN ({$in_placeholders}) 
                     AND meta_key IN (%s, %s, %s, %s, %s, %s, %s, %s)";
            
            // Merge all parameters for prepare
            $query_params = array_merge(
                $candidate_ids,
                [
                    '_mt_candidate_name',
                    '_mt_organization', 
                    '_mt_category_type',
                    '_mt_description_full',
                    '_mt_innovation',
                    '_mt_website_url',
                    '_mt_linkedin_url',
                    '_mt_email'
                ]
            );
            
            $meta_query = $wpdb->prepare($query, $query_params);
            $all_meta = $wpdb->get_results($meta_query);
            
            // Organize meta by post ID
            $meta_by_post = [];
            foreach ($all_meta as $meta) {
                if (!isset($meta_by_post[$meta->post_id])) {
                    $meta_by_post[$meta->post_id] = [];
                }
                $meta_by_post[$meta->post_id][$meta->meta_key] = $meta->meta_value;
            }
            
            // Write data using cached meta
            foreach ($candidates as $candidate) {
                $meta = isset($meta_by_post[$candidate->ID]) ? $meta_by_post[$candidate->ID] : [];
                fputcsv($output, [
                    $candidate->ID,
                    isset($meta['_mt_candidate_name']) ? $meta['_mt_candidate_name'] : $candidate->post_title,
                    isset($meta['_mt_organization']) ? $meta['_mt_organization'] : '',
                    isset($meta['_mt_category_type']) ? $meta['_mt_category_type'] : '',
                    isset($meta['_mt_description_full']) ? $meta['_mt_description_full'] : $candidate->post_content,
                    isset($meta['_mt_innovation']) ? $meta['_mt_innovation'] : '',
                    isset($meta['_mt_website_url']) ? $meta['_mt_website_url'] : '',
                    isset($meta['_mt_linkedin_url']) ? $meta['_mt_linkedin_url'] : '',
                    isset($meta['_mt_email']) ? $meta['_mt_email'] : '',
                    $candidate->post_status,
                    self::format_date_iso8601($candidate->post_date),
                    self::format_date_iso8601($candidate->post_modified)
                ]);
            }
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