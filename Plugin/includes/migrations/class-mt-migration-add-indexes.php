<?php
/**
 * Database Migration - Add Performance Indexes
 *
 * @package MobilityTrailblazers
 * @since 2.2.1
 */

namespace MobilityTrailblazers\Migrations;

use MobilityTrailblazers\Core\MT_Logger;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Migration_Add_Indexes
 *
 * Adds performance indexes to database tables
 */
class MT_Migration_Add_Indexes {
    
    /**
     * Run the migration
     *
     * @return bool
     */
    public static function run() {
        global $wpdb;
        
        $success = true;
        
        // Add composite indexes for mt_evaluations table
        $evaluations_table = $wpdb->prefix . 'mt_evaluations';
        
        // Composite index for jury member and status (for progress queries)
        $index1 = self::add_index($evaluations_table, 'idx_jury_status', ['jury_member_id', 'status']);
        if (!$index1) $success = false;
        
        // Composite index for candidate and status (for ranking queries)
        $index2 = self::add_index($evaluations_table, 'idx_candidate_status', ['candidate_id', 'status']);
        if (!$index2) $success = false;
        
        // Index for total_score (for ranking and sorting)
        $index3 = self::add_index($evaluations_table, 'idx_total_score', ['total_score']);
        if (!$index3) $success = false;
        
        // Composite index for status and total_score (for filtered rankings)
        $index4 = self::add_index($evaluations_table, 'idx_status_score', ['status', 'total_score']);
        if (!$index4) $success = false;
        
        // Add composite indexes for mt_jury_assignments table
        $assignments_table = $wpdb->prefix . 'mt_jury_assignments';
        
        // Composite index for jury member and assigned date (for recent assignments)
        $index5 = self::add_index($assignments_table, 'idx_jury_date', ['jury_member_id', 'assigned_at']);
        if (!$index5) $success = false;
        
        // Index for assigned_by (for tracking who made assignments)
        $index6 = self::add_index($assignments_table, 'idx_assigned_by', ['assigned_by']);
        if (!$index6) $success = false;
        
        // Log migration completion
        if ($success) {
            update_option('mt_migration_indexes_added', current_time('mysql'));
            MT_Logger::info('Database migration completed successfully: performance indexes added');
        } else {
            MT_Logger::error('Database migration failed: some indexes could not be added');
        }
        
        return $success;
    }
    
    /**
     * Add an index to a table if it doesn't exist
     *
     * @param string $table Table name
     * @param string $index_name Index name
     * @param array $columns Column names
     * @return bool
     */
    private static function add_index($table, $index_name, $columns) {
        global $wpdb;
        
        // Whitelist known tables and sanitize identifiers
        $allowed_tables = [
            $wpdb->prefix . 'mt_evaluations',
            $wpdb->prefix . 'mt_jury_assignments',
        ];
        if (!in_array($table, $allowed_tables, true)) {
            MT_Logger::security_event('Blocked index operation on unexpected table', [
                'table' => $table,
                'index_name' => $index_name,
            ]);
            return false;
        }

        $index_name_safe = sanitize_key($index_name);
        // Check if index already exists (prepare only the value; identifiers validated + backticked)
        $existing = $wpdb->get_results(
            $wpdb->prepare(
                'SHOW INDEX FROM `' . esc_sql($table) . '` WHERE Key_name = %s',
                $index_name_safe
            )
        );
        
        if (!empty($existing)) {
            MT_Logger::debug("Database index already exists", [
                'index_name' => $index_name,
                'table' => $table
            ]);
            return true;
        }
        
        // Build column list (sanitize each identifier)
        $column_list = implode(
            ', ',
            array_map(function ($col) {
                $col = sanitize_key($col);
                return '`' . $col . '`';
            }, $columns)
        );
        
        // Add the index (identifiers validated and backticked)
        $query = sprintf(
            'ALTER TABLE `%s` ADD INDEX `%s` (%s)',
            esc_sql($table),
            esc_sql($index_name_safe),
            $column_list
        );
        $result = $wpdb->query($query);
        
        if ($result === false) {
            MT_Logger::database_error('ADD INDEX', $table, $wpdb->last_error, [
                'index_name' => $index_name,
                'columns' => $columns,
                'query' => $query
            ]);
            return false;
        }
        
        MT_Logger::info('Database index added successfully', [
            'index_name' => $index_name,
            'table' => $table,
            'columns' => $columns
        ]);
        return true;
    }
    
    /**
     * Check if migration is needed
     *
     * @return bool
     */
    public static function is_needed() {
        $migration_done = get_option('mt_migration_indexes_added', false);
        return !$migration_done;
    }
    
    /**
     * Rollback the migration
     *
     * @return bool
     */
    public static function rollback() {
        global $wpdb;
        
        $success = true;
        
        // Define indexes to remove
        $indexes = [
            $wpdb->prefix . 'mt_evaluations' => [
                'idx_jury_status',
                'idx_candidate_status',
                'idx_total_score',
                'idx_status_score'
            ],
            $wpdb->prefix . 'mt_jury_assignments' => [
                'idx_jury_date',
                'idx_assigned_by'
            ]
        ];
        
        // Remove each index
        foreach ($indexes as $table => $index_list) {
            foreach ($index_list as $index_name) {
                // Check if index exists (sanitize index name)
                $index_name_safe = sanitize_key($index_name);
                $existing = $wpdb->get_results(
                    $wpdb->prepare(
                        'SHOW INDEX FROM `' . esc_sql($table) . '` WHERE Key_name = %s',
                        $index_name_safe
                    )
                );
                
                if (!empty($existing)) {
                    $query = sprintf(
                        'ALTER TABLE `%s` DROP INDEX `%s`',
                        esc_sql($table),
                        esc_sql($index_name_safe)
                    );
                    $result = $wpdb->query($query);
                    
                    if ($result === false) {
                        MT_Logger::database_error('DROP INDEX', $table, $wpdb->last_error, [
                            'index_name' => $index_name
                        ]);
                        $success = false;
                    } else {
                        MT_Logger::info('Database index removed successfully', [
                            'index_name' => $index_name,
                            'table' => $table
                        ]);
                    }
                }
            }
        }
        
        // Remove migration flag
        if ($success) {
            delete_option('mt_migration_indexes_added');
        }
        
        return $success;
    }
}
