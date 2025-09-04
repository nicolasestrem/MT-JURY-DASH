<?php
/**
 * Plugin Name: Mobility Trailblazers
 * Plugin URI: https://mobility-trailblazers.com
 * Description: Award management platform for recognizing mobility innovators in the DACH region
 * Version: 2.5.41
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Mobility Trailblazers - Nicolas Estrem
 * Author URI: https://mobility-trailblazers.com
 * Text Domain: mobility-trailblazers
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * Copyright (c) 2025 Mobility Trailblazers - Nicolas Estrem
 *
 * This program is free software. You can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation. Either version 2 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Debug: Log plugin loading
error_log('MT Plugin: Loading at ' . date('Y-m-d H:i:s'));

// Define plugin constants
define('MT_VERSION', '2.5.41');
define('MT_PLUGIN_FILE', __FILE__);
define('MT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MT_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Environment detection (can be overridden in wp-config.php)
if (!defined('MT_ENVIRONMENT')) {
    // Automatic detection based on domain or WP environment
    if (defined('WP_ENVIRONMENT_TYPE')) {
        $wp_env = WP_ENVIRONMENT_TYPE;
        if (in_array($wp_env, ['local', 'development'])) {
            define('MT_ENVIRONMENT', 'development');
        } elseif ($wp_env === 'staging') {
            define('MT_ENVIRONMENT', 'staging');
        } else {
            define('MT_ENVIRONMENT', 'production');
        }
    } elseif (function_exists('wp_get_environment_type')) {
        $wp_env = wp_get_environment_type();
        if (in_array($wp_env, ['local', 'development'])) {
            define('MT_ENVIRONMENT', 'development');
        } elseif ($wp_env === 'staging') {
            define('MT_ENVIRONMENT', 'staging');
        } else {
            define('MT_ENVIRONMENT', 'production');
        }
    } else {
        // Default to production for safety
        define('MT_ENVIRONMENT', 'production');
    }
}

// Require the autoloader
require_once MT_PLUGIN_DIR . 'includes/core/class-mt-autoloader.php';

// Register autoloader
MobilityTrailblazers\Core\MT_Autoloader::register();

// REMOVED: German translation compatibility layer (deprecated in v2.5.42)
// Translations now handled exclusively through standard .mo files

// Load username dot fix to prevent dots in usernames
if (file_exists(MT_PLUGIN_DIR . 'includes/fixes/class-mt-username-dot-fix.php')) {
    require_once MT_PLUGIN_DIR . 'includes/fixes/class-mt-username-dot-fix.php';
    add_action('init', ['MobilityTrailblazers\Fixes\MT_Username_Dot_Fix', 'init']);
}

// Load candidate helper functions for backward compatibility
if (file_exists(MT_PLUGIN_DIR . 'includes/functions/mt-candidate-helpers.php')) {
    require_once MT_PLUGIN_DIR . 'includes/functions/mt-candidate-helpers.php';
}

// Bootstrap container early for AJAX requests
// This ensures the container is ready before any AJAX handlers try to use it
if (defined('DOING_AJAX') && DOING_AJAX) {
    // For AJAX requests, bootstrap immediately
    $plugin = MobilityTrailblazers\Core\MT_Plugin::get_instance();
    $plugin->ensure_services_for_ajax();
}

// Initialize the plugin
add_action('plugins_loaded', function() {
    // Text domain is loaded in MT_I18n class to avoid duplication
    
    // Initialize core
    $plugin = MobilityTrailblazers\Core\MT_Plugin::get_instance();
    $plugin->init();
    
    // Initialize migration runner
    MobilityTrailblazers\Core\MT_Migration_Runner::init();
    
    // Register simple candidate profile shortcode
    add_shortcode('mt_candidate', function($atts) {
        $atts = shortcode_atts([
            'slug' => ''
        ], $atts);
        
        if (empty($atts['slug'])) {
            return '<p>No candidate specified</p>';
        }
        
        // Load repository
        require_once MT_PLUGIN_DIR . 'includes/repositories/class-mt-candidate-repository.php';
        $repository = new MobilityTrailblazers\Repositories\MT_Candidate_Repository();
        $candidate = $repository->find_by_slug($atts['slug']);
        
        if (!$candidate) {
            return '<p>Candidate not found: ' . esc_html($atts['slug']) . '</p>';
        }
        
        // Simple output
        $output = '<div class="mt-candidate-profile">';
        $output .= '<h2>' . esc_html($candidate->name) . '</h2>';
        if ($candidate->photo_url) {
            $output .= '<img src="' . esc_url($candidate->photo_url) . '" alt="' . esc_attr($candidate->name) . '" style="max-width: 300px; height: auto;" />';
        }
        if ($candidate->organization) {
            $output .= '<p><strong>Organization:</strong> ' . esc_html($candidate->organization) . '</p>';
        }
        if ($candidate->position) {
            $output .= '<p><strong>Position:</strong> ' . esc_html($candidate->position) . '</p>';
        }
        if ($candidate->description) {
            $output .= '<div class="description">' . wp_kses_post($candidate->description) . '</div>';
        }
        $output .= '</div>';
        
        return $output;
    });
}, 5); // Run early with priority 5

// Simple candidate router - handles /candidate/slug/ URLs
add_action('template_redirect', function() {
    try {
        $request_uri = $_SERVER['REQUEST_URI'];
        
        // Debug: Log that we're in template_redirect
        if (strpos($request_uri, '/candidate/') === 0) {
            error_log('MT: template_redirect hook reached for: ' . $request_uri);
        }
        
        // Check if this is a candidate URL
        if (preg_match('#^/candidate/([^/]+)/?$#', $request_uri, $matches)) {
            error_log('MT: Candidate URL matched: ' . $matches[1]);
            $candidate_slug = $matches[1];
            
            // Load candidate from repository
            error_log('MT: Loading repository...');
            require_once MT_PLUGIN_DIR . 'includes/repositories/class-mt-candidate-repository.php';
            error_log('MT: Creating repository instance...');
            $repository = new MobilityTrailblazers\Repositories\MT_Candidate_Repository();
            error_log('MT: Finding candidate by slug: ' . $candidate_slug);
            $candidate = $repository->find_by_slug($candidate_slug);
            error_log('MT: Candidate found: ' . ($candidate ? 'yes' : 'no'));
        
        if (!$candidate) {
            // Candidate not found
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            return;
        }
        
        // Store candidate for template use
        $GLOBALS['mt_current_candidate'] = $candidate;
        
        // Override WordPress query
        global $wp_query, $post;
        
        // Create fake post object for compatibility
        $fake_post = new WP_Post((object)[
            'ID' => $candidate->id,
            'post_title' => $candidate->name,
            'post_name' => $candidate->slug,
            'post_content' => $candidate->description ?? '',
            'post_excerpt' => '',
            'post_status' => 'publish',
            'post_type' => 'mt_candidate',
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_author' => 1,
            'post_date' => current_time('mysql'),
            'post_date_gmt' => current_time('mysql', true),
            'post_modified' => current_time('mysql'),
            'post_modified_gmt' => current_time('mysql', true),
            'guid' => home_url('/candidate/' . $candidate->slug . '/'),
            'post_parent' => 0,
            'menu_order' => 0
        ]);
        
        // Set up query state
        $post = $fake_post;
        $GLOBALS['post'] = $fake_post;
        
        $wp_query->post = $fake_post;
        $wp_query->posts = [$fake_post];
        $wp_query->queried_object = $fake_post;
        $wp_query->queried_object_id = $fake_post->ID;
        $wp_query->post_count = 1;
        $wp_query->found_posts = 1;
        $wp_query->max_num_pages = 1;
        
        $wp_query->is_404 = false;
        $wp_query->is_page = false;
        $wp_query->is_single = true;
        $wp_query->is_singular = true;
        $wp_query->is_home = false;
        $wp_query->is_archive = false;
        
        setup_postdata($post);
        
        // Load the template
        $template = '';
        $use_enhanced = get_option('mt_use_enhanced_template', true);
        
        if ($use_enhanced && file_exists(MT_PLUGIN_DIR . 'templates/frontend/single/single-mt_candidate-enhanced-v2.php')) {
            $template = MT_PLUGIN_DIR . 'templates/frontend/single/single-mt_candidate-enhanced-v2.php';
        } else if ($use_enhanced && file_exists(MT_PLUGIN_DIR . 'templates/frontend/single/single-mt_candidate-enhanced.php')) {
            $template = MT_PLUGIN_DIR . 'templates/frontend/single/single-mt_candidate-enhanced.php';
        } else if (file_exists(MT_PLUGIN_DIR . 'templates/frontend/single/single-mt_candidate.php')) {
            $template = MT_PLUGIN_DIR . 'templates/frontend/single/single-mt_candidate.php';
        }
        
        if ($template) {
            // Debug log
            error_log('MT: Loading template: ' . $template);
            error_log('MT: Candidate data: ' . print_r($candidate, true));
            
            // Load the template
            include($template);
            exit;
        } else {
            error_log('MT: No template found for candidate');
        }
    }
    } catch (\Exception $e) {
        error_log('MT Candidate Router Error: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        wp_die('Error loading candidate page: ' . $e->getMessage());
    }
}, 1);

// Keep the old router class for now but disable it
class MT_Candidate_Router_OLD {
    public static function init() {
        // Disabled for now
    }
    
    public static function add_endpoint() {
        // Add rewrite endpoint for candidates
        add_rewrite_endpoint('candidate', EP_ROOT);
        
        // Also add our custom rewrite rule
        add_rewrite_rule(
            '^candidate/([^/]+)/?$',
            'index.php?candidate=$matches[1]',
            'top'
        );
        
        // Check if we need to flush
        $rules = get_option('rewrite_rules');
        if (!$rules || !isset($rules['^candidate/([^/]+)/?$'])) {
            flush_rewrite_rules();
        }
    }
    
    public static function handle_candidate_request() {
        try {
            // Check if we're on a candidate page
            $candidate_slug = get_query_var('candidate', '');
            
            if (empty($candidate_slug)) {
                // Check the URL directly as a fallback
                $request_uri = $_SERVER['REQUEST_URI'];
                if (preg_match('#^/candidate/([^/]+)/?$#', $request_uri, $matches)) {
                    $candidate_slug = $matches[1];
                }
            }
            
            if (!empty($candidate_slug)) {
                // Load candidate data from repository
                require_once MT_PLUGIN_DIR . 'includes/repositories/class-mt-candidate-repository.php';
                $repository = new MobilityTrailblazers\Repositories\MT_Candidate_Repository();
                $candidate = $repository->find_by_slug($candidate_slug);
                
                if ($candidate) {
                    // Store candidate data for template use
                    $GLOBALS['mt_current_candidate'] = $candidate;
                    $GLOBALS['mt_is_candidate_page'] = true;
                } else {
                    // Candidate not found - trigger 404
                    global $wp_query;
                    $wp_query->set_404();
                    status_header(404);
                }
            }
        } catch (\Exception $e) {
            error_log('MT_Candidate_Router Error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
        }
    }
    
    public static function template_include($template) {
        try {
            global $wp_query;
            
            // Check if this is a candidate page
            if (!empty($GLOBALS['mt_is_candidate_page']) && !empty($GLOBALS['mt_current_candidate'])) {
                $candidate = $GLOBALS['mt_current_candidate'];
            
            // Set up WordPress query state for template compatibility
            $wp_query->is_404 = false;
            $wp_query->is_single = true;
            $wp_query->is_singular = true;
            $wp_query->is_page = false;
            $wp_query->is_home = false;
            
            // Create a fake post object for template compatibility
            $post = new WP_Post((object)[
                'ID' => $candidate->id,
                'post_title' => $candidate->name,
                'post_name' => $candidate->slug,
                'post_content' => $candidate->description ?? '',
                'post_status' => 'publish',
                'post_type' => 'mt_candidate',
                'comment_status' => 'closed',
                'ping_status' => 'closed',
                'post_author' => 1,
                'post_date' => current_time('mysql'),
                'post_date_gmt' => current_time('mysql', true),
                'post_modified' => current_time('mysql'),
                'post_modified_gmt' => current_time('mysql', true),
                'guid' => home_url('/candidate/' . $candidate->slug . '/'),
                'post_parent' => 0,
                'menu_order' => 0,
                'post_mime_type' => '',
                'filter' => 'raw'
            ]);
            
            // Set up global post object
            $GLOBALS['post'] = $post;
            $wp_query->post = $post;
            $wp_query->posts = [$post];
            $wp_query->queried_object = $post;
            $wp_query->queried_object_id = $post->ID;
            $wp_query->post_count = 1;
            $wp_query->found_posts = 1;
            $wp_query->max_num_pages = 1;
            setup_postdata($post);
            
            // Check if enhanced template should be used
            $use_enhanced = get_option('mt_use_enhanced_template', true);
            
            // Determine which template to use
            $templates = [];
            if ($use_enhanced) {
                $templates[] = MT_PLUGIN_DIR . 'templates/frontend/single/single-mt_candidate-enhanced-v2.php';
                $templates[] = MT_PLUGIN_DIR . 'templates/frontend/single/single-mt_candidate-enhanced.php';
            }
            $templates[] = MT_PLUGIN_DIR . 'templates/frontend/single/single-mt_candidate.php';
            
            // Find first existing template
            foreach ($templates as $tmpl) {
                if (file_exists($tmpl)) {
                    return $tmpl;
                }
            }
        }
        } catch (\Exception $e) {
            error_log('MT_Candidate_Router template_include Error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
        }
        
        return $template;
    }
    
    public static function flush_rules() {
        self::add_endpoint();
        flush_rewrite_rules();
    }
}

// Initialize the router - DISABLED (using simpler approach above)
// MT_Candidate_Router::init();

// Activation hook
register_activation_hook(__FILE__, function() {
    $activator = new MobilityTrailblazers\Core\MT_Activator();
    $activator->activate();
    
    // Run migrations on activation
    MobilityTrailblazers\Core\MT_Migration_Runner::activate();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    $deactivator = new MobilityTrailblazers\Core\MT_Deactivator();
    $deactivator->deactivate();
});

// Uninstall hook
register_uninstall_hook(__FILE__, ['MobilityTrailblazers\Core\MT_Uninstaller', 'uninstall']);

// Register WP-CLI commands
if (defined('WP_CLI') && WP_CLI) {
    // Only load vendor autoload if it exists (for composer dependencies)
    $vendor_autoload = MT_PLUGIN_DIR . 'vendor/autoload.php';
    if (file_exists($vendor_autoload)) {
        require_once $vendor_autoload;
    }
    
    $cli_commands_file = MT_PLUGIN_DIR . 'includes/cli/class-mt-cli-commands.php';
    if (file_exists($cli_commands_file)) {
        require_once $cli_commands_file;
        
        $cli_commands = new MobilityTrailblazers\CLI\MT_CLI_Commands();
        // Import candidates command has been removed
        WP_CLI::add_command('mt db-upgrade', [$cli_commands, 'db_upgrade']);
        WP_CLI::add_command('mt list-candidates', [$cli_commands, 'list_candidates']);
    }
} 
