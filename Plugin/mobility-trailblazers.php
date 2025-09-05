<?php
/**
 * Plugin Name: Mobility Trailblazers
 * Plugin URI: https://mobility-trailblazers.com
 * Description: Award management platform for recognizing mobility innovators in the DACH region
 * Version: 2.6.0
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

// Optional debug logging can be enabled with WP_DEBUG

// Define plugin constants
define('MT_VERSION', '2.6.0');
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

// Candidate routing now handled by MT_Candidate_Router (CPT-free)

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
