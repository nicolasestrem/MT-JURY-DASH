<?php
/**
 * Assignments Admin Page Handler
 * 
 * Properly handles script enqueuing and page rendering for the assignments page
 * 
 * @package MobilityTrailblazers
 * @since 2.5.42
 */

if (!defined('ABSPATH')) {
    exit;
}

class MT_Assignments_Page {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Enqueue scripts and styles for the assignments page
     * 
     * @param string $hook_suffix The current admin page hook suffix
     */
    public function enqueue_scripts($hook_suffix) {
        // Only load on our assignments page
        if (strpos($hook_suffix, 'mt-assignments') === false) {
            return;
        }
        
        // Enqueue jQuery if not already loaded
        wp_enqueue_script('jquery');
        
        // Enqueue main admin script
        wp_enqueue_script(
            'mt-admin',
            MT_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            MT_VERSION,
            true
        );
        
        // Localize admin script with proper nonces
        wp_localize_script('mt-admin', 'mt_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mt_admin_nonce'),
            'auto_assign_nonce' => wp_create_nonce('mt_auto_assign_action'),
            'manual_assign_nonce' => wp_create_nonce('mt_manual_assign_action'),
            'remove_assignment_nonce' => wp_create_nonce('mt_remove_assignment_action'),
            'clear_assignments_nonce' => wp_create_nonce('mt_clear_assignments_action'),
            'i18n' => array(
                'processing' => __('Processing...', 'mobility-trailblazers'),
                'error_occurred' => __('An error occurred. Please try again.', 'mobility-trailblazers'),
                'assignments_created' => __('Assignments created successfully.', 'mobility-trailblazers'),
                'select_jury_and_candidates' => __('Please select a jury member and at least one candidate.', 'mobility-trailblazers'),
                'confirm_remove_assignment' => __('Are you sure you want to remove this assignment?', 'mobility-trailblazers'),
                'assignment_removed' => __('Assignment removed successfully.', 'mobility-trailblazers'),
                'no_assignments' => __('No assignments yet', 'mobility-trailblazers'),
                'confirm_clear_all' => __('Are you sure you want to clear ALL assignments? This cannot be undone!', 'mobility-trailblazers'),
                'confirm_clear_all_second' => __('This will remove ALL jury assignments. Are you absolutely sure?', 'mobility-trailblazers'),
                'all_assignments_cleared' => __('All assignments have been cleared.', 'mobility-trailblazers'),
                'clearing' => __('Clearing...', 'mobility-trailblazers'),
                'clear_all' => __('Clear All', 'mobility-trailblazers'),
                'remove' => __('Remove', 'mobility-trailblazers'),
                'assign_selected' => __('Assign Selected', 'mobility-trailblazers'),
                'select_jury_candidates' => __('Please select a jury member and at least one candidate.', 'mobility-trailblazers'),
                'error' => __('Error', 'mobility-trailblazers'),
                'assignments_cleared' => __('All assignments have been cleared.', 'mobility-trailblazers')
            )
        ));
        
        // Enqueue the dedicated assignments script
        wp_enqueue_script(
            'mt-assignments',
            MT_PLUGIN_URL . 'assets/js/mt-assignments.js',
            array('jquery', 'mt-admin'),
            MT_VERSION,
            true
        );
        
        // Enqueue fallback script for edge cases
        wp_enqueue_script(
            'mt-assignments-fallback',
            MT_PLUGIN_URL . 'assets/js/mt-assignments-fallback.js',
            array('jquery', 'mt-admin'),
            MT_VERSION,
            true
        );
        
        // Enqueue modal debug script only in development
        if ((defined('WP_DEBUG') && WP_DEBUG) && (defined('MT_DEBUG') && MT_DEBUG)) {
            wp_enqueue_script(
                'mt-modal-debug',
                MT_PLUGIN_URL . 'assets/js/mt-modal-debug.js',
                array('jquery'),
                MT_VERSION . '.2',
                true
            );
        }
        
        // Enqueue styles
        wp_enqueue_style(
            'mt-admin',
            MT_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            MT_VERSION
        );
        
        // Enqueue modal fix CSS
        wp_enqueue_style(
            'mt-modal-fix',
            MT_PLUGIN_URL . 'assets/css/mt-modal-fix.css',
            array(),
            MT_VERSION
        );
        
        // Add inline script for debug flag
        if (defined('MT_DEBUG') && MT_DEBUG) {
            wp_add_inline_script('mt-assignments', 'window.MT_DEBUG = true;', 'before');
        }
    }
    
    /**
     * Render the assignments page
     * 
     * This method should be called from the main plugin to render the page content
     */
    public function render_page() {
        // Include the template file
        $template_path = MT_PLUGIN_DIR . 'templates/admin/assignments.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="error"><p>' . __('Template file not found.', 'mobility-trailblazers') . '</p></div>';
        }
    }
}