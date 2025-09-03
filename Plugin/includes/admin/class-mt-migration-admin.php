<?php
/**
 * Admin interface for running one-time migrations.
 *
 * @package MobilityTrailblazers\Admin
 */

namespace MobilityTrailblazers\Admin;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class MT_Migration_Admin {

    const ACTION_NAME = 'mt_run_cpt_migration';

    public function init() {
        add_action('admin_menu', [$this, 'add_migration_page']);
        add_action('admin_post_' . self::ACTION_NAME, [$this, 'handle_migration']);
    }

    public function add_migration_page() {
        add_submenu_page(
            'mobility-trailblazers', // Parent slug
            __('Data Migration', 'mobility-trailblazers'),
            __('Data Migration', 'mobility-trailblazers'),
            'manage_options',
            'mt-data-migration',
            [$this, 'render_migration_page']
        );
    }

    public function render_migration_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Data Migration', 'mobility-trailblazers'); ?></h1>
            <?php if (isset($_GET['migration_status'])) : ?>
                <div id="message" class="updated notice is-dismissible">
                    <p><?php echo esc_html(urldecode($_GET['migration_status'])); ?></p>
                </div>
            <?php endif; ?>
            <div class="card">
                <h2><?php _e('Migrate Candidates to Custom Table', 'mobility-trailblazers'); ?></h2>
                <p><?php _e('This tool will migrate candidates from the old Custom Post Type storage to the new, more efficient custom database table. This is a one-time operation.', 'mobility-trailblazers'); ?></p>
                <p><strong><?php _e('Important:', 'mobility-trailblazers'); ?></strong> <?php _e('Please back up your database before running this process.', 'mobility-trailblazers'); ?></p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="<?php echo esc_attr(self::ACTION_NAME); ?>" />
                    <?php wp_nonce_field(self::ACTION_NAME); ?>
                    <p>
                        <label>
                            <input type="checkbox" name="dry_run" value="1" checked />
                            <?php _e('Perform a dry run first (recommended)', 'mobility-trailblazers'); ?>
                        </label>
                    </p>
                    <?php submit_button(__('Run Migration', 'mobility-trailblazers'), 'primary'); ?>
                </form>
            </div>
        </div>
        <?php
    }

    public function handle_migration() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], self::ACTION_NAME)) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }

        global $wpdb;

        $dry_run = isset($_POST['dry_run']);
        $table_name = $wpdb->prefix . 'mt_candidates';

        $posts = get_posts([
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'post_status' => 'any',
        ]);

        if (empty($posts)) {
            $this->redirect_with_status('No candidates found to migrate.');
            return;
        }

        $count = 0;
        $skipped = 0;
        foreach ($posts as $post) {
            // Check if the candidate already exists in the new table
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table_name} WHERE post_id = %d", $post->ID));
            if ($exists) {
                $skipped++;
                continue;
            }

            $candidate_data = [
                'post_id' => $post->ID,
                'slug' => $post->post_name,
                'name' => $post->post_title,
                'organization' => get_post_meta($post->ID, '_mt_organization', true),
                'position' => get_post_meta($post->ID, '_mt_position', true),
                'country' => get_post_meta($post->ID, '_mt_country', true),
                'linkedin_url' => get_post_meta($post->ID, '_mt_linkedin_url', true),
                'website_url' => get_post_meta($post->ID, '_mt_website_url', true),
                'article_url' => get_post_meta($post->ID, '_mt_article_url', true),
                'description_sections' => get_post_meta($post->ID, '_mt_description_sections', true),
                'photo_attachment_id' => get_post_thumbnail_id($post->ID),
                'created_at' => $post->post_date,
                'updated_at' => $post->post_modified,
            ];

            if (!$dry_run) {
                $wpdb->insert($table_name, $candidate_data);
                if ($wpdb->last_error) {
                    // Log error but continue
                } else {
                    $count++;
                }
            }
        }

        if ($dry_run) {
            $status = "Dry run complete. " . count($posts) . " candidates would be processed.";
        } else {
            $status = "Migration complete. {$count} candidates were successfully migrated.";
        }
        
        $this->redirect_with_status($status);
    }

    private function redirect_with_status($status) {
        wp_safe_redirect(add_query_arg(
            ['page' => 'mt-data-migration', 'migration_status' => urlencode($status)],
            admin_url('admin.php')
        ));
        exit;
    }
}
