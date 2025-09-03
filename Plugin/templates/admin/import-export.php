<?php
/**
 * Admin Import/Export Page Template
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get any messages from the URL
$export_message = '';
$message_type = 'success';
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'export_started':
            $export_message = __('Export started. Download should begin automatically.', 'mobility-trailblazers');
            $message_type = 'success';
            break;
    }
}
?>

<div class="wrap">
    <h1><?php _e('Export Data', 'mobility-trailblazers'); ?></h1>
    
    <?php if ($export_message): ?>
        <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
            <p><?php echo esc_html($export_message); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="mt-import-export-container">
        <div class="mt-section">
            <h2><?php _e('Export Data', 'mobility-trailblazers'); ?></h2>
            <p><?php _e('Export your data as CSV files for backup or analysis.', 'mobility-trailblazers'); ?></p>
            
            <div class="mt-export-buttons">
                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=mt_export_candidates'), 'mt_export_candidates'); ?>" 
                   class="button button-primary">
                    <?php _e('Export Candidates', 'mobility-trailblazers'); ?>
                </a>
                
                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=mt_export_evaluations'), 'mt_export_evaluations'); ?>" 
                   class="button button-primary">
                    <?php _e('Export Evaluations', 'mobility-trailblazers'); ?>
                </a>
                
                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=mt_export_assignments'), 'mt_export_assignments'); ?>" 
                   class="button button-primary">
                    <?php _e('Export Assignments', 'mobility-trailblazers'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.mt-import-export-container {
    max-width: 800px;
    margin-top: 20px;
}

.mt-section {
    background: #fff;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.mt-export-buttons {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

</style>