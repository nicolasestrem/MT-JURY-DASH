<?php
/**
 * Migration script to move candidates from CPT to custom table.
 *
 * @package MobilityTrailblazers\CLI
 */

namespace MobilityTrailblazers\CLI;

if (!defined('WP_CLI') || !WP_CLI) {
    return;
}

/**
 * Migrates candidates from the mt_candidate CPT to the wp_mt_candidates custom table.
 */
class MT_Migration_Command extends \WP_CLI_Command {

    /**
     * Migrates candidates from CPT to custom table.
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : Perform a dry run without actually modifying the database.
     *
     * ## EXAMPLES
     *
     *     wp mt migrate_candidates
     *     wp mt migrate_candidates --dry-run
     *
     * @when after_wp_load
     */
    public function migrate_candidates($args, $assoc_args) {
        global $wpdb;

        $dry_run = isset($assoc_args['dry-run']);
        $table_name = $wpdb->prefix . 'mt_candidates';

        $posts = get_posts([
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'post_status' => 'any',
        ]);

        if (empty($posts)) {
            \WP_CLI::success('No candidates found to migrate.');
            return;
        }

        $count = 0;
        foreach ($posts as $post) {
            // Check for duplicates
            $existing_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table_name} WHERE post_id = %d", $post->ID));
            if ($existing_id) {
                \WP_CLI::log("Skipping duplicate candidate: {$post->post_title} (Post ID: {$post->ID})");
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

            if ($dry_run) {
                \WP_CLI::log("Dry run: Would insert candidate {$post->post_title}");
            } else {
                $wpdb->insert($table_name, $candidate_data);
                if ($wpdb->last_error) {
                    \WP_CLI::warning("Failed to insert candidate {$post->post_title}: {$wpdb->last_error}");
                } else {
                    \WP_CLI::log("Inserted candidate {$post->post_title}");
                    $count++;
                }
            }
        }

        if ($dry_run) {
            \WP_CLI::success("Dry run complete. Would have processed " . count($posts) . " candidates.");
        } else {
            \WP_CLI::success("Migration complete. {$count} candidates migrated.");
        }
    }
}

\WP_CLI::add_command('mt', 'MobilityTrailblazers\CLI\MT_Migration_Command');
