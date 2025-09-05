<?php
/**
 * Candidate Router (CPT-free)
 *
 * Provides clean routing for /candidate/{slug}/ using repository data,
 * without relying on a custom post type.
 *
 * @package MobilityTrailblazers
 * @since 2.6.0
 */

namespace MobilityTrailblazers\Core;

use MobilityTrailblazers\Repositories\MT_Candidate_Repository;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class MT_Candidate_Router {

    public static function init() {
        add_action('init', [__CLASS__, 'add_rewrite_rules']);
        add_filter('query_vars', [__CLASS__, 'add_query_vars']);
        add_action('parse_request', [__CLASS__, 'maybe_mark_candidate_request']);
        add_filter('template_include', [__CLASS__, 'template_include'], 20);
        add_action('wp', [__CLASS__, 'maybe_prepare_fake_post']);

        // One-time rewrite flush if rules missing
        add_action('init', [__CLASS__, 'maybe_flush_rules_lazily'], 99);
    }

    public static function add_query_vars($vars) {
        $vars[] = 'mt_candidate_slug';
        return $vars;
    }

    public static function add_rewrite_rules() {
        add_rewrite_rule('^candidate/([^/]+)/?$', 'index.php?mt_candidate_slug=$matches[1]', 'top');
    }

    public static function maybe_flush_rules_lazily() {
        $rules = get_option('rewrite_rules');
        if (!$rules || !isset($rules['^candidate/([^/]+)/?$'])) {
            flush_rewrite_rules(false);
        }
    }

    public static function maybe_mark_candidate_request($wp) {
        if (!empty($wp->query_vars['mt_candidate_slug'])) {
            $GLOBALS['mt_is_candidate_page'] = true;
        }
    }

    public static function load_candidate_by_slug($slug) {
        if (empty($slug)) {
            return null;
        }
        require_once MT_PLUGIN_DIR . 'includes/repositories/class-mt-candidate-repository.php';
        $repo = new MT_Candidate_Repository();
        return $repo->find_by_slug(sanitize_title($slug));
    }

    public static function maybe_prepare_fake_post() {
        // Only prepare for candidate pages
        $slug = get_query_var('mt_candidate_slug');
        if (empty($slug)) {
            return;
        }

        $candidate = self::load_candidate_by_slug($slug);
        if (!$candidate) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            return;
        }

        // Expose to templates
        $GLOBALS['mt_current_candidate'] = $candidate;

        // Create a fake post to satisfy themes and body classes
        $fake = new \WP_Post((object) [
            'ID' => (int)($candidate->post_id ?: 0) ?: 99999999, // stable dummy if no WP post
            'post_title' => $candidate->name,
            'post_name' => $candidate->slug,
            'post_content' => $candidate->description ?? '',
            'post_excerpt' => '',
            'post_status' => 'publish',
            'post_type' => 'mt_candidate',
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_author' => 0,
            'post_date' => current_time('mysql'),
            'post_date_gmt' => current_time('mysql', true),
            'post_modified' => current_time('mysql'),
            'post_modified_gmt' => current_time('mysql', true),
            'guid' => home_url('/candidate/' . $candidate->slug . '/'),
            'post_parent' => 0,
            'menu_order' => 0,
            'filter' => 'raw',
        ]);

        global $wp_query, $post;
        $post = $fake;
        $GLOBALS['post'] = $fake;
        $wp_query->post = $fake;
        $wp_query->posts = [$fake];
        $wp_query->queried_object = $fake;
        $wp_query->queried_object_id = $fake->ID;
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
    }

    public static function template_include($template) {
        $slug = get_query_var('mt_candidate_slug');
        if (empty($slug)) {
            return $template;
        }

        // Choose in-plugin templates, preferring enhanced variants
        $candidates = [
            MT_PLUGIN_DIR . 'templates/frontend/single/single-mt_candidate-enhanced-v2.php',
            MT_PLUGIN_DIR . 'templates/frontend/single/single-mt_candidate-enhanced.php',
            MT_PLUGIN_DIR . 'templates/frontend/single/single-mt_candidate.php',
        ];
        foreach ($candidates as $tmpl) {
            if (file_exists($tmpl)) {
                return $tmpl;
            }
        }

        return $template;
    }
}

