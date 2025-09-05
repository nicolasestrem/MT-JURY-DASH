<?php
/**
 * CPT to Custom Table Migration
 *
 * Migrates candidate data from Custom Post Type to wp_mt_candidates table
 *
 * @package MobilityTrailblazers
 * @since 2.5.42
 */

namespace MobilityTrailblazers\Migrations;

use MobilityTrailblazers\Core\MT_Logger;
use MobilityTrailblazers\Core\MT_Audit_Logger;
use MobilityTrailblazers\Repositories\MT_Candidate_Repository;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_CPT_To_Table_Migration
 *
 * Handles migration of candidate data from CPT to custom table
 */
class MT_CPT_To_Table_Migration {
    
    /**
     * Batch size for processing
     */
    const BATCH_SIZE = 100;
    
    /**
     * Candidate repository
     *
     * @var MT_Candidate_Repository
     */
    private $candidate_repository;
    
    /**
     * Migration statistics
     *
     * @var array
     */
    private $stats = [
        'total' => 0,
        'migrated' => 0,
        'skipped' => 0,
        'failed' => 0,
        'errors' => []
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->candidate_repository = new MT_Candidate_Repository();
    }
    
    /**
     * Run the migration
     *
     * @param array $args Optional arguments
     * @return array Migration results
     */
    public function run($args = []) {
        global $wpdb;
        
        $batch_size = isset($args['batch_size']) ? intval($args['batch_size']) : self::BATCH_SIZE;
        $offset = isset($args['offset']) ? intval($args['offset']) : 0;
        $dry_run = isset($args['dry_run']) ? (bool) $args['dry_run'] : false;
        
        MT_Logger::info('Starting CPT to table migration', [
            'batch_size' => $batch_size,
            'offset' => $offset,
            'dry_run' => $dry_run
        ]);
        
        // Create backup first (unless dry run)
        if (!$dry_run) {
            $this->create_backup();
        }
        
        // Get total count of candidates
        $total_candidates = wp_count_posts('mt_candidate');
        $this->stats['total'] = $total_candidates->publish + $total_candidates->draft + $total_candidates->private;
        
        // Process in batches
        $has_more = true;
        $current_offset = $offset;
        
        while ($has_more) {
            $candidates = get_posts([
                'post_type' => 'mt_candidate',
                'posts_per_page' => $batch_size,
                'offset' => $current_offset,
                'post_status' => 'any',
                'orderby' => 'ID',
                'order' => 'ASC'
            ]);
            
            if (empty($candidates)) {
                $has_more = false;
                break;
            }
            
            foreach ($candidates as $candidate_post) {
                $result = $this->migrate_single_candidate($candidate_post, $dry_run);
                
                if ($result === true) {
                    $this->stats['migrated']++;
                } elseif ($result === 'skipped') {
                    $this->stats['skipped']++;
                } else {
                    $this->stats['failed']++;
                    $this->stats['errors'][] = $result;
                }
            }
            
            $current_offset += $batch_size;
            
            // Check if we've processed all
            if (count($candidates) < $batch_size) {
                $has_more = false;
            }
            
            // Allow for memory cleanup
            if (!$dry_run) {
                wp_cache_flush();
            }
        }
        
        // Log completion
        MT_Logger::info('CPT to table migration completed', $this->stats);
        
        // Log audit event
        if (!$dry_run) {
            MT_Audit_Logger::log('migration_completed', 'candidate_migration', 0, [
                'stats' => $this->stats
            ]);
        }
        
        return $this->stats;
    }
    
    /**
     * Migrate a single candidate
     *
     * @param WP_Post $candidate_post The candidate post
     * @param bool $dry_run Whether this is a dry run
     * @return mixed True on success, 'skipped' if already migrated, error message on failure
     */
    private function migrate_single_candidate($candidate_post, $dry_run = false) {
        global $wpdb;
        
        try {
            // Check if already migrated (has entry in custom table with this post_id)
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}mt_candidates WHERE post_id = %d",
                $candidate_post->ID
            ));
            
            if ($existing) {
                MT_Logger::debug('Candidate already migrated', [
                    'post_id' => $candidate_post->ID,
                    'table_id' => $existing->id
                ]);
                return 'skipped';
            }
            
            // Gather all candidate data
            $candidate_data = $this->extract_candidate_data($candidate_post);
            
            if ($dry_run) {
                MT_Logger::info('Dry run - would migrate candidate', [
                    'post_id' => $candidate_post->ID,
                    'data' => $candidate_data
                ]);
                return true;
            }
            
            // Insert into custom table
            $result = $wpdb->insert(
                $wpdb->prefix . 'mt_candidates',
                $candidate_data,
                [
                    '%s', // slug
                    '%s', // name
                    '%s', // organization
                    '%s', // position
                    '%s', // country
                    '%s', // linkedin_url
                    '%s', // website_url
                    '%s', // article_url
                    '%s', // description_sections
                    '%d', // photo_attachment_id
                    '%d', // post_id
                    '%s', // import_id
                    '%s', // created_at
                    '%s'  // updated_at
                ]
            );
            
            if ($result === false) {
                throw new \Exception('Database insert failed: ' . $wpdb->last_error);
            }
            
            MT_Logger::info('Candidate migrated successfully', [
                'post_id' => $candidate_post->ID,
                'new_id' => $wpdb->insert_id
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            MT_Logger::error('Failed to migrate candidate', [
                'post_id' => $candidate_post->ID,
                'error' => $e->getMessage()
            ]);
            
            return 'Failed to migrate post ' . $candidate_post->ID . ': ' . $e->getMessage();
        }
    }
    
    /**
     * Extract candidate data from post and postmeta
     *
     * @param WP_Post $post The candidate post
     * @return array Candidate data for insertion
     */
    private function extract_candidate_data($post) {
        // Get all post meta
        $meta = get_post_meta($post->ID);
        
        // Build description sections array
        $description_sections = [];
        
        // Add main content as description
        if (!empty($post->post_content)) {
            $description_sections['description'] = $post->post_content;
        }
        
        // Add custom fields to description sections
        $section_fields = [
            'innovation' => 'mt_innovation',
            'implementation' => 'mt_implementation', 
            'relevance' => 'mt_relevance',
            'courage' => 'mt_courage',
            'visibility' => 'mt_visibility',
            'category' => 'mt_category',
            'award_category' => 'mt_award_category'
        ];
        
        foreach ($section_fields as $key => $meta_key) {
            if (isset($meta[$meta_key][0]) && !empty($meta[$meta_key][0])) {
                $description_sections[$key] = $meta[$meta_key][0];
            }
        }
        
        // Get featured image ID
        $photo_id = get_post_thumbnail_id($post->ID);
        
        // Prepare data array
        $data = [
            'slug' => $post->post_name,
            'name' => $post->post_title,
            'organization' => isset($meta['mt_company'][0]) ? $meta['mt_company'][0] : 
                             (isset($meta['mt_organization'][0]) ? $meta['mt_organization'][0] : ''),
            'position' => isset($meta['mt_position'][0]) ? $meta['mt_position'][0] : '',
            'country' => isset($meta['mt_country'][0]) ? $meta['mt_country'][0] : 'Germany',
            'linkedin_url' => isset($meta['mt_linkedin'][0]) ? $meta['mt_linkedin'][0] : 
                            (isset($meta['mt_linkedin_url'][0]) ? $meta['mt_linkedin_url'][0] : ''),
            'website_url' => isset($meta['mt_website'][0]) ? $meta['mt_website'][0] :
                           (isset($meta['mt_website_url'][0]) ? $meta['mt_website_url'][0] : ''),
            'article_url' => isset($meta['mt_article_url'][0]) ? $meta['mt_article_url'][0] : '',
            'description_sections' => json_encode($description_sections),
            'photo_attachment_id' => $photo_id ?: null,
            'post_id' => $post->ID,
            'import_id' => isset($meta['mt_import_id'][0]) ? $meta['mt_import_id'][0] : null,
            'created_at' => $post->post_date,
            'updated_at' => $post->post_modified
        ];
        
        return $data;
    }
    
    /**
     * Create backup of current data
     *
     * @return bool Success status
     */
    private function create_backup() {
        global $wpdb;
        
        try {
            // Create backup table name with timestamp
            $backup_table = $wpdb->prefix . 'mt_candidates_backup_' . date('YmdHis');
            
            // Create backup table as copy of current table
            $wpdb->query("CREATE TABLE IF NOT EXISTS `$backup_table` LIKE `{$wpdb->prefix}mt_candidates`");
            $wpdb->query("INSERT INTO `$backup_table` SELECT * FROM `{$wpdb->prefix}mt_candidates`");
            
            MT_Logger::info('Backup created', ['table' => $backup_table]);
            
            return true;
            
        } catch (\Exception $e) {
            MT_Logger::error('Failed to create backup', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Verify migration integrity
     *
     * @return array Verification results
     */
    public function verify_migration() {
        global $wpdb;
        
        $results = [
            'success' => true,
            'checks' => []
        ];
        
        // Check 1: Count comparison
        $cpt_count = wp_count_posts('mt_candidate');
        $total_cpt = $cpt_count->publish + $cpt_count->draft + $cpt_count->private;
        
        $table_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_candidates WHERE post_id IS NOT NULL");
        
        $results['checks']['count'] = [
            'cpt_total' => $total_cpt,
            'table_total' => $table_count,
            'match' => ($total_cpt == $table_count)
        ];
        
        if (!$results['checks']['count']['match']) {
            $results['success'] = false;
        }
        
        // Check 2: Sample data verification
        $sample_posts = get_posts([
            'post_type' => 'mt_candidate',
            'posts_per_page' => 5,
            'orderby' => 'rand'
        ]);
        
        foreach ($sample_posts as $post) {
            $table_data = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}mt_candidates WHERE post_id = %d",
                $post->ID
            ));
            
            $results['checks']['samples'][$post->ID] = [
                'post_title' => $post->post_title,
                'table_name' => $table_data ? $table_data->name : null,
                'match' => $table_data && ($table_data->name == $post->post_title)
            ];
            
            if (!$results['checks']['samples'][$post->ID]['match']) {
                $results['success'] = false;
            }
        }
        
        // Check 3: Required fields populated
        $empty_fields = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mt_candidates 
             WHERE post_id IS NOT NULL 
             AND (name IS NULL OR name = '' OR slug IS NULL OR slug = '')"
        );
        
        $results['checks']['required_fields'] = [
            'empty_count' => $empty_fields,
            'valid' => ($empty_fields == 0)
        ];
        
        if (!$results['checks']['required_fields']['valid']) {
            $results['success'] = false;
        }
        
        MT_Logger::info('Migration verification completed', $results);
        
        return $results;
    }
}