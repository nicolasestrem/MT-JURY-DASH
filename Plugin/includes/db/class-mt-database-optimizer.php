<?php
/**
 * Database Optimizer
 * 
 * @package MobilityTrailblazers
 * @since 2.5.42
 */

namespace MobilityTrailblazers\Database;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database Optimizer Class
 * 
 * Handles database optimization, index creation, and query performance
 */
class MT_Database_Optimizer {
    
    /**
     * Optimize database tables and create indexes
     */
    public static function optimize() {
        global $wpdb;
        
        // Create indexes for evaluation table
        self::create_evaluation_indexes();
        
        // Create indexes for assignment table  
        self::create_assignment_indexes();
        
        // Create indexes for audit log table
        self::create_audit_log_indexes();
        
        // Optimize WordPress core tables for our queries
        self::optimize_wp_tables();
        
        // Analyze tables for query optimization
        self::analyze_tables();
    }
    
    /**
     * Create indexes for evaluation table
     */
    private static function create_evaluation_indexes() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mt_evaluations';
        
        // Composite index for jury member queries
        $wpdb->query("ALTER TABLE {$table_name} ADD INDEX idx_jury_status (jury_member_id, status, total_score DESC)");
        
        // Composite index for candidate queries
        $wpdb->query("ALTER TABLE {$table_name} ADD INDEX idx_candidate_status (candidate_id, status, total_score DESC)");
        
        // Index for status filtering
        $wpdb->query("ALTER TABLE {$table_name} ADD INDEX idx_status_date (status, created_at DESC)");
        
        // Covering index for ranking queries
        $wpdb->query("ALTER TABLE {$table_name} ADD INDEX idx_ranking_query (
            jury_member_id, 
            candidate_id, 
            status, 
            total_score DESC, 
            courage_score, 
            innovation_score, 
            implementation_score, 
            relevance_score, 
            visibility_score
        )");
    }
    
    /**
     * Create indexes for assignment table
     */
    private static function create_assignment_indexes() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mt_jury_assignments';
        
        // Composite index for jury lookups
        $wpdb->query("ALTER TABLE {$table_name} ADD INDEX idx_jury_candidate (jury_member_id, candidate_id)");
        
        // Index for candidate lookups
        $wpdb->query("ALTER TABLE {$table_name} ADD INDEX idx_candidate_jury (candidate_id, jury_member_id)");
        
        // Index for assignment date queries
        $wpdb->query("ALTER TABLE {$table_name} ADD INDEX idx_assigned_date (assigned_at DESC)");
        
        // Unique constraint to prevent duplicates
        $wpdb->query("ALTER TABLE {$table_name} ADD UNIQUE KEY unique_assignment (jury_member_id, candidate_id)");
    }
    
    /**
     * Create indexes for audit log table
     */
    private static function create_audit_log_indexes() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mt_audit_log';
        
        // Index for user activity queries
        $wpdb->query("ALTER TABLE {$table_name} ADD INDEX idx_user_action (user_id, action, created_at DESC)");
        
        // Index for action filtering
        $wpdb->query("ALTER TABLE {$table_name} ADD INDEX idx_action_date (action, created_at DESC)");
        
        // Index for object lookups
        $wpdb->query("ALTER TABLE {$table_name} ADD INDEX idx_object_type_id (object_type, object_id)");
    }
    
    /**
     * Optimize WordPress core tables
     */
    private static function optimize_wp_tables() {
        global $wpdb;
        
        // Add index for mt_candidate post type queries
        $wpdb->query("ALTER TABLE {$wpdb->posts} ADD INDEX idx_mt_post_type (post_type, post_status, post_date DESC)");
        
        // Add index for mt_jury_member post type queries  
        $wpdb->query("ALTER TABLE {$wpdb->posts} ADD INDEX idx_mt_jury_type (post_type, post_status, ID)");
        
        // Optimize postmeta for our custom fields
        $wpdb->query("ALTER TABLE {$wpdb->postmeta} ADD INDEX idx_mt_meta_key (meta_key(20), post_id)");
        
        // Add index for term relationships
        $wpdb->query("ALTER TABLE {$wpdb->term_relationships} ADD INDEX idx_mt_term_object (term_taxonomy_id, object_id)");
    }
    
    /**
     * Analyze tables for query optimization
     */
    private static function analyze_tables() {
        global $wpdb;
        
        // Analyze our custom tables
        $wpdb->query("ANALYZE TABLE {$wpdb->prefix}mt_evaluations");
        $wpdb->query("ANALYZE TABLE {$wpdb->prefix}mt_jury_assignments");
        $wpdb->query("ANALYZE TABLE {$wpdb->prefix}mt_audit_log");
        
        // Optimize tables to reclaim space
        $wpdb->query("OPTIMIZE TABLE {$wpdb->prefix}mt_evaluations");
        $wpdb->query("OPTIMIZE TABLE {$wpdb->prefix}mt_jury_assignments");
        $wpdb->query("OPTIMIZE TABLE {$wpdb->prefix}mt_audit_log");
    }
    
    /**
     * Check if indexes exist
     */
    public static function check_indexes() {
        global $wpdb;
        
        $missing_indexes = [];
        
        // Check evaluation table indexes
        $eval_table = $wpdb->prefix . 'mt_evaluations';
        $eval_indexes = $wpdb->get_results("SHOW INDEX FROM {$eval_table}");
        $eval_index_names = wp_list_pluck($eval_indexes, 'Key_name');
        
        $required_eval_indexes = ['idx_jury_status', 'idx_candidate_status', 'idx_status_date', 'idx_ranking_query'];
        foreach ($required_eval_indexes as $index) {
            if (!in_array($index, $eval_index_names)) {
                $missing_indexes[] = "{$eval_table}.{$index}";
            }
        }
        
        // Check assignment table indexes
        $assign_table = $wpdb->prefix . 'mt_jury_assignments';
        $assign_indexes = $wpdb->get_results("SHOW INDEX FROM {$assign_table}");
        $assign_index_names = wp_list_pluck($assign_indexes, 'Key_name');
        
        $required_assign_indexes = ['idx_jury_candidate', 'idx_candidate_jury', 'idx_assigned_date', 'unique_assignment'];
        foreach ($required_assign_indexes as $index) {
            if (!in_array($index, $assign_index_names)) {
                $missing_indexes[] = "{$assign_table}.{$index}";
            }
        }
        
        return $missing_indexes;
    }
    
    /**
     * Get slow queries
     */
    public static function get_slow_queries() {
        global $wpdb;
        
        if (!defined('SAVEQUERIES') || !SAVEQUERIES) {
            return [];
        }
        
        $slow_queries = [];
        $threshold = 0.05; // 50ms
        
        foreach ($wpdb->queries as $query_data) {
            if ($query_data[1] > $threshold) {
                $slow_queries[] = [
                    'query' => $query_data[0],
                    'time' => $query_data[1],
                    'caller' => $query_data[2]
                ];
            }
        }
        
        return $slow_queries;
    }
}