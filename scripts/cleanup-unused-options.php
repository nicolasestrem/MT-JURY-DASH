<?php
/**
 * Database Cleanup Script - Remove Unused Language Options
 * 
 * This script removes outdated language settings from the database
 * that are no longer functional in the plugin.
 * 
 * @package MobilityTrailblazers
 * @since 4.1.0
 */

// Check if being run from WP-CLI
if (!defined('WP_CLI')) {
    // If not WP-CLI, try to load WordPress
    $wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
    
    if (!file_exists($wp_load_path)) {
        die("Error: Could not find wp-load.php. Please run this script from the WordPress root directory or use WP-CLI.\n");
    }
    
    require_once($wp_load_path);
}

echo "=================================\n";
echo "MT Settings Database Cleanup\n";
echo "=================================\n\n";

// List of deprecated options to remove
$deprecated_options = [
    'mt_default_language',
    'mt_enable_language_switcher',
    'mt_auto_detect_language',
];

echo "Removing deprecated language options...\n\n";

$removed_count = 0;
$errors = [];

foreach ($deprecated_options as $option_name) {
    $current_value = get_option($option_name);
    
    if ($current_value !== false) {
        echo "Found option: {$option_name} = " . print_r($current_value, true) . "\n";
        
        if (delete_option($option_name)) {
            echo "  ✓ Successfully removed\n";
            $removed_count++;
        } else {
            echo "  ✗ Failed to remove\n";
            $errors[] = $option_name;
        }
    } else {
        echo "Option not found: {$option_name} (already clean)\n";
    }
    echo "\n";
}

// Enable animation settings if they exist but are disabled
echo "Checking animation settings...\n\n";

$presentation_settings = get_option('mt_candidate_presentation', []);

if (isset($presentation_settings['enable_animations']) && $presentation_settings['enable_animations'] == 0) {
    echo "Animation setting found but disabled. Enabling...\n";
    $presentation_settings['enable_animations'] = 1;
    
    if (isset($presentation_settings['enable_hover_effects']) && $presentation_settings['enable_hover_effects'] == 0) {
        echo "Hover effects also disabled. Enabling...\n";
        $presentation_settings['enable_hover_effects'] = 1;
    }
    
    if (update_option('mt_candidate_presentation', $presentation_settings)) {
        echo "  ✓ Animation settings enabled successfully\n";
    } else {
        echo "  ✗ Failed to enable animation settings\n";
        $errors[] = 'animation_settings';
    }
} else {
    echo "Animation settings are already properly configured.\n";
}

// Summary
echo "\n=================================\n";
echo "Cleanup Summary\n";
echo "=================================\n\n";

echo "Deprecated options removed: {$removed_count}\n";

if (!empty($errors)) {
    echo "\nErrors encountered:\n";
    foreach ($errors as $error) {
        echo "  - Failed to process: {$error}\n";
    }
    echo "\nPlease check your database permissions and try again.\n";
} else {
    echo "\n✓ All cleanup operations completed successfully!\n";
}

echo "\n";

// Clear caches
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
    echo "Cache cleared.\n";
}

echo "\nCleanup script completed.\n";