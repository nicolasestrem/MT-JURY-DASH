<?php
/**
 * Candidate Helper Functions
 *
 * Provides backward compatibility and helper functions for candidate data access
 * after migration from CPT to custom table
 *
 * @package MobilityTrailblazers
 * @since 2.5.42
 */

use MobilityTrailblazers\Repositories\MT_Candidate_Repository;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get candidate by ID or post ID
 *
 * This function provides backward compatibility by accepting either
 * the new table ID or the legacy WordPress post ID
 *
 * @param int $id_or_post_id Either table ID or WordPress post ID
 * @return object|null Candidate object or null if not found
 */
function mt_get_candidate($id_or_post_id) {
    static $repository = null;
    
    if ($repository === null) {
        $repository = new MT_Candidate_Repository();
    }
    
    // Try by table ID first (new way)
    $candidate = $repository->find($id_or_post_id);
    
    if (!$candidate) {
        // Try by post ID (legacy way)
        $candidate = $repository->find_by_post_id($id_or_post_id);
    }
    
    return $candidate;
}

/**
 * Get candidate by WordPress post ID
 *
 * @param int $post_id WordPress post ID
 * @return object|null Candidate object or null if not found
 */
function mt_get_candidate_by_post_id($post_id) {
    static $repository = null;
    
    if ($repository === null) {
        $repository = new MT_Candidate_Repository();
    }
    
    return $repository->find_by_post_id($post_id);
}

/**
 * Get all candidates
 *
 * @param array $args Query arguments
 * @return array Array of candidate objects
 */
function mt_get_all_candidates($args = []) {
    static $repository = null;
    
    if ($repository === null) {
        $repository = new MT_Candidate_Repository();
    }
    
    return $repository->find_all($args);
}

/**
 * Get candidate meta value (backward compatibility)
 *
 * Maps old meta keys to new object properties
 *
 * @param int $candidate_id Candidate ID (post ID for backward compat)
 * @param string $meta_key Meta key
 * @param bool $single Return single value (ignored, for compatibility)
 * @return mixed Meta value or empty string if not found
 */
function mt_get_candidate_meta($candidate_id, $meta_key, $single = true) {
    $candidate = mt_get_candidate($candidate_id);
    
    if (!$candidate) {
        return '';
    }
    
    // Map old meta keys to new properties
    $meta_map = [
        '_mt_organization' => 'organization',
        '_mt_company' => 'organization', // Alias
        '_mt_position' => 'position',
        '_mt_country' => 'country',
        '_mt_linkedin_url' => 'linkedin_url',
        '_mt_linkedin' => 'linkedin_url', // Alias
        '_mt_website_url' => 'website_url',
        '_mt_website' => 'website_url', // Alias
        '_mt_article_url' => 'article_url',
        '_mt_email' => 'email',
        '_mt_import_id' => 'import_id',
    ];
    
    // Check if it's a mapped meta key
    if (isset($meta_map[$meta_key])) {
        $property = $meta_map[$meta_key];
        return isset($candidate->$property) ? $candidate->$property : '';
    }
    
    // Check in description sections for other meta
    if (!empty($candidate->description_sections)) {
        $sections = is_string($candidate->description_sections) 
            ? json_decode($candidate->description_sections, true) 
            : $candidate->description_sections;
            
        // Remove _mt_ prefix if present
        $clean_key = str_replace('_mt_', '', $meta_key);
        
        if (isset($sections[$clean_key])) {
            return $sections[$clean_key];
        }
    }
    
    return '';
}

/**
 * Convert candidate object to WP_Post-like structure
 *
 * For templates that expect WP_Post properties
 *
 * @param object $candidate Candidate object from repository
 * @return object Object with WP_Post-like properties
 */
function mt_candidate_to_post($candidate) {
    if (!$candidate) {
        return null;
    }
    
    $post_like = new stdClass();
    
    // Map to WP_Post properties
    $post_like->ID = $candidate->post_id ?: $candidate->id;
    $post_like->post_title = $candidate->name;
    $post_like->post_name = $candidate->slug;
    $post_like->post_type = 'mt_candidate';
    $post_like->post_status = 'publish';
    $post_like->post_date = $candidate->created_at;
    $post_like->post_modified = $candidate->updated_at;
    
    // Handle description
    $description = '';
    if (!empty($candidate->description_sections)) {
        $sections = is_string($candidate->description_sections) 
            ? json_decode($candidate->description_sections, true) 
            : $candidate->description_sections;
        $description = isset($sections['description']) ? $sections['description'] : '';
    }
    $post_like->post_content = $description;
    $post_like->post_excerpt = wp_trim_words($description, 55);
    
    // Add candidate data as properties for easy access
    $post_like->candidate_data = $candidate;
    
    return $post_like;
}

/**
 * Get candidate repository instance
 *
 * @return MT_Candidate_Repository
 */
function mt_get_candidate_repository() {
    static $repository = null;
    
    if ($repository === null) {
        $repository = new MT_Candidate_Repository();
    }
    
    return $repository;
}