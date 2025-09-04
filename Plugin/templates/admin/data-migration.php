<?php
/**
 * Data Migration Admin Page Template
 *
 * @package MobilityTrailblazers
 * @since 2.5.42
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get current statistics
$cpt_count = wp_count_posts('mt_candidate');
$total_cpt = $cpt_count->publish + $cpt_count->draft + $cpt_count->private;

$table_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_candidates");
$table_with_post_id = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_candidates WHERE post_id IS NOT NULL");

// Check if verification was run
$verification = isset($verification) ? $verification : null;
$migration_results = isset($results) ? $results : null;
?>

<div class="wrap">
    <h1><?php _e('Data Migration Tools', 'mobility-trailblazers'); ?></h1>
    
    <div class="notice notice-info">
        <p>
            <strong><?php _e('Phase 2: Architectural Unification', 'mobility-trailblazers'); ?></strong><br>
            <?php _e('This tool migrates candidate data from the legacy Custom Post Type (CPT) to the modern custom table structure.', 'mobility-trailblazers'); ?>
        </p>
    </div>
    
    <!-- Current Status -->
    <div class="card" style="max-width: 800px;">
        <h2><?php _e('Current Status', 'mobility-trailblazers'); ?></h2>
        
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php _e('Data Source', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Total Records', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Status', 'mobility-trailblazers'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong><?php _e('Custom Post Type (Legacy)', 'mobility-trailblazers'); ?></strong></td>
                    <td><?php echo esc_html($total_cpt); ?></td>
                    <td>
                        <?php if ($total_cpt > 0): ?>
                            <span class="dashicons dashicons-warning" style="color: orange;"></span>
                            <?php _e('Contains data - migration needed', 'mobility-trailblazers'); ?>
                        <?php else: ?>
                            <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                            <?php _e('Empty', 'mobility-trailblazers'); ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php _e('Custom Table (New)', 'mobility-trailblazers'); ?></strong></td>
                    <td><?php echo esc_html($table_count); ?></td>
                    <td>
                        <?php if ($table_count > 0): ?>
                            <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                            <?php printf(__('%d records (%d linked to CPT)', 'mobility-trailblazers'), $table_count, $table_with_post_id); ?>
                        <?php else: ?>
                            <span class="dashicons dashicons-info" style="color: blue;"></span>
                            <?php _e('Ready for migration', 'mobility-trailblazers'); ?>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <?php if ($total_cpt > $table_with_post_id): ?>
            <p class="description" style="margin-top: 10px;">
                <span class="dashicons dashicons-info"></span>
                <?php 
                $unmigrated = $total_cpt - $table_with_post_id;
                printf(
                    _n(
                        '%d candidate needs to be migrated.',
                        '%d candidates need to be migrated.',
                        $unmigrated,
                        'mobility-trailblazers'
                    ),
                    $unmigrated
                );
                ?>
            </p>
        <?php elseif ($total_cpt > 0 && $total_cpt == $table_with_post_id): ?>
            <p class="description" style="margin-top: 10px; color: green;">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php _e('All candidates have been migrated successfully!', 'mobility-trailblazers'); ?>
            </p>
        <?php endif; ?>
    </div>
    
    <!-- Migration Actions -->
    <div class="card" style="max-width: 800px; margin-top: 20px;">
        <h2><?php _e('Migration Actions', 'mobility-trailblazers'); ?></h2>
        
        <form method="post" action="">
            <?php wp_nonce_field('mt_data_migration', 'mt_migration_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Migration Mode', 'mobility-trailblazers'); ?></th>
                    <td>
                        <label>
                            <input type="radio" name="dry_run" value="1" checked>
                            <strong><?php _e('Dry Run', 'mobility-trailblazers'); ?></strong> - 
                            <?php _e('Test migration without making changes', 'mobility-trailblazers'); ?>
                        </label><br>
                        <label>
                            <input type="radio" name="dry_run" value="0">
                            <strong><?php _e('Live Migration', 'mobility-trailblazers'); ?></strong> - 
                            <?php _e('Perform actual data migration', 'mobility-trailblazers'); ?>
                        </label>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" name="mt_migrate_candidates" class="button button-primary">
                    <span class="dashicons dashicons-migrate" style="vertical-align: text-top;"></span>
                    <?php _e('Run Migration', 'mobility-trailblazers'); ?>
                </button>
                <button type="submit" name="mt_verify_migration" class="button button-secondary">
                    <span class="dashicons dashicons-yes" style="vertical-align: text-top;"></span>
                    <?php _e('Verify Migration', 'mobility-trailblazers'); ?>
                </button>
            </p>
        </form>
    </div>
    
    <!-- Migration Results -->
    <?php if ($migration_results): ?>
    <div class="card" style="max-width: 800px; margin-top: 20px;">
        <h2><?php _e('Migration Results', 'mobility-trailblazers'); ?></h2>
        
        <table class="widefat">
            <tbody>
                <tr>
                    <th><?php _e('Total Candidates', 'mobility-trailblazers'); ?></th>
                    <td><?php echo esc_html($migration_results['total']); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Successfully Migrated', 'mobility-trailblazers'); ?></th>
                    <td style="color: green;">
                        <strong><?php echo esc_html($migration_results['migrated']); ?></strong>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Skipped (Already Migrated)', 'mobility-trailblazers'); ?></th>
                    <td style="color: blue;">
                        <?php echo esc_html($migration_results['skipped']); ?>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Failed', 'mobility-trailblazers'); ?></th>
                    <td style="color: <?php echo $migration_results['failed'] > 0 ? 'red' : 'green'; ?>;">
                        <?php echo esc_html($migration_results['failed']); ?>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <?php if (!empty($migration_results['errors'])): ?>
        <h3><?php _e('Errors', 'mobility-trailblazers'); ?></h3>
        <div style="background: #fff; border: 1px solid #ccc; padding: 10px; max-height: 200px; overflow-y: auto;">
            <ul>
                <?php foreach ($migration_results['errors'] as $error): ?>
                    <li style="color: red;"><?php echo esc_html($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- Verification Results -->
    <?php if ($verification): ?>
    <div class="card" style="max-width: 800px; margin-top: 20px;">
        <h2><?php _e('Verification Results', 'mobility-trailblazers'); ?></h2>
        
        <?php if ($verification['success']): ?>
            <div class="notice notice-success inline">
                <p>
                    <span class="dashicons dashicons-yes-alt"></span>
                    <strong><?php _e('Migration verification passed!', 'mobility-trailblazers'); ?></strong>
                </p>
            </div>
        <?php else: ?>
            <div class="notice notice-warning inline">
                <p>
                    <span class="dashicons dashicons-warning"></span>
                    <strong><?php _e('Migration verification found issues', 'mobility-trailblazers'); ?></strong>
                </p>
            </div>
        <?php endif; ?>
        
        <!-- Count Check -->
        <h3><?php _e('Count Verification', 'mobility-trailblazers'); ?></h3>
        <table class="widefat">
            <tbody>
                <tr>
                    <th><?php _e('CPT Total', 'mobility-trailblazers'); ?></th>
                    <td><?php echo esc_html($verification['checks']['count']['cpt_total']); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Table Total', 'mobility-trailblazers'); ?></th>
                    <td><?php echo esc_html($verification['checks']['count']['table_total']); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Match', 'mobility-trailblazers'); ?></th>
                    <td>
                        <?php if ($verification['checks']['count']['match']): ?>
                            <span class="dashicons dashicons-yes-alt" style="color: green;"></span> <?php _e('Yes', 'mobility-trailblazers'); ?>
                        <?php else: ?>
                            <span class="dashicons dashicons-no" style="color: red;"></span> <?php _e('No', 'mobility-trailblazers'); ?>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <!-- Sample Verification -->
        <?php if (isset($verification['checks']['samples'])): ?>
        <h3><?php _e('Sample Data Verification', 'mobility-trailblazers'); ?></h3>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php _e('Post ID', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('CPT Title', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Table Name', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Match', 'mobility-trailblazers'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($verification['checks']['samples'] as $post_id => $sample): ?>
                <tr>
                    <td><?php echo esc_html($post_id); ?></td>
                    <td><?php echo esc_html($sample['post_title']); ?></td>
                    <td><?php echo esc_html($sample['table_name'] ?? 'N/A'); ?></td>
                    <td>
                        <?php if ($sample['match']): ?>
                            <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                        <?php else: ?>
                            <span class="dashicons dashicons-no" style="color: red;"></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        
        <!-- Required Fields Check -->
        <h3><?php _e('Required Fields Check', 'mobility-trailblazers'); ?></h3>
        <p>
            <?php if ($verification['checks']['required_fields']['valid']): ?>
                <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                <?php _e('All required fields are populated correctly.', 'mobility-trailblazers'); ?>
            <?php else: ?>
                <span class="dashicons dashicons-warning" style="color: orange;"></span>
                <?php printf(__('%d records have missing required fields.', 'mobility-trailblazers'), $verification['checks']['required_fields']['empty_count']); ?>
            <?php endif; ?>
        </p>
    </div>
    <?php endif; ?>
    
    <!-- WP-CLI Instructions -->
    <div class="card" style="max-width: 800px; margin-top: 20px;">
        <h2><?php _e('WP-CLI Commands', 'mobility-trailblazers'); ?></h2>
        <p><?php _e('You can also run the migration from the command line:', 'mobility-trailblazers'); ?></p>
        <pre style="background: #f0f0f0; padding: 10px; overflow-x: auto;">
# Dry run (test without changes)
wp mt migrate-candidates --dry-run

# Live migration
wp mt migrate-candidates

# Verify migration
wp mt migrate-candidates --verify

# Custom batch size
wp mt migrate-candidates --batch-size=50
        </pre>
    </div>
</div>