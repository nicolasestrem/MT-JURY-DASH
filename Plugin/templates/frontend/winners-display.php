<?php
/**
 * Winners Display Template
 *
 * @package MobilityTrailblazers
 * @since 2.0.1
 * @version 2.5.42 - Refactored to use repository pattern
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Template variables from shortcode
$show_scores = $atts['show_scores'] === 'yes';
$year = $atts['year'];

// Helper functions are available for backward compatibility
if (!function_exists('mt_get_candidate_meta')) {
    require_once MT_PLUGIN_DIR . 'includes/functions/mt-candidate-helpers.php';
}
?>

<div class="mt-root">
<div class="mt-winners-display">
    <div class="mt-winners-header">
        <h2><?php printf(__('Mobility Trailblazers %s Winners', 'mobility-trailblazers'), esc_html($year)); ?></h2>
        <p><?php _e('Celebrating the pioneers shaping the future of mobility', 'mobility-trailblazers'); ?></p>
    </div>
    
    <div class="mt-winners-grid">
        <?php 
        $rank = 1;
        foreach ($winners as $winner) : 
            // Use repository data if available
            if (isset($winner->post)) {
                $candidate = $winner->post;
                $candidate_data = isset($winner->candidate) ? $winner->candidate : null;
            } else {
                // Fallback to old method
                $candidate = get_post($winner->candidate_id);
                if (!$candidate) continue;
                $candidate_data = null;
            }
            
            // Get organization and position from repository data or post meta
            if ($candidate_data) {
                $organization = $candidate_data->organization;
                $position = $candidate_data->position;
                $name = $candidate_data->name;
                
                // Get categories from description sections
                $categories = [];
                if (!empty($candidate_data->description_sections)) {
                    $sections = is_string($candidate_data->description_sections)
                        ? json_decode($candidate_data->description_sections, true)
                        : $candidate_data->description_sections;
                    
                    if (isset($sections['category'])) {
                        $category_map = [
                            'startup' => __('Startup', 'mobility-trailblazers'),
                            'tech' => __('Technology', 'mobility-trailblazers'),
                            'gov' => __('Government', 'mobility-trailblazers'),
                            'innovation' => __('Innovation', 'mobility-trailblazers'),
                            'mobility' => __('Mobility', 'mobility-trailblazers'),
                            'sustainability' => __('Sustainability', 'mobility-trailblazers')
                        ];
                        $cat_slug = $sections['category'];
                        $cat_name = isset($category_map[$cat_slug]) ? $category_map[$cat_slug] : ucfirst($cat_slug);
                        
                        $categories = [(object)['name' => $cat_name, 'slug' => $cat_slug]];
                    }
                }
                
                // Get excerpt
                $excerpt = '';
                if (!empty($candidate_data->description_sections)) {
                    $sections = is_string($candidate_data->description_sections)
                        ? json_decode($candidate_data->description_sections, true)
                        : $candidate_data->description_sections;
                    if (isset($sections['description'])) {
                        $excerpt = wp_trim_words($sections['description'], 30);
                    }
                }
            } else {
                // Fallback to traditional methods
                $organization = get_post_meta($candidate->ID, '_mt_organization', true);
                $position = get_post_meta($candidate->ID, '_mt_position', true);
                $name = $candidate->post_title;
                $categories = wp_get_post_terms($candidate->ID, 'mt_award_category');
                $excerpt = $candidate->post_excerpt;
            }
            
            // Rank class
            $rank_class = '';
            if ($rank === 1) $rank_class = 'gold';
            elseif ($rank === 2) $rank_class = 'silver';
            elseif ($rank === 3) $rank_class = 'bronze';
        ?>
            <div class="mt-winner-card <?php echo esc_attr($rank_class); ?>">
                <div class="mt-winner-rank"><?php echo esc_html($rank); ?></div>
                
                <?php 
                // Get photo - try repository data first
                $has_photo = false;
                $photo_html = '';
                
                if ($candidate_data && !empty($candidate_data->photo_attachment_id)) {
                    $photo_url = wp_get_attachment_image_url($candidate_data->photo_attachment_id, 'medium');
                    if ($photo_url) {
                        $photo_html = '<img src="' . esc_url($photo_url) . '" alt="' . esc_attr($name) . '" class="mt-winner-photo" />';
                        $has_photo = true;
                    }
                } elseif (has_post_thumbnail($candidate->ID)) {
                    $photo_html = get_the_post_thumbnail($candidate->ID, 'medium', ['class' => 'mt-winner-photo']);
                    $has_photo = true;
                }
                ?>
                
                <?php if ($has_photo) : ?>
                    <?php echo $photo_html; ?>
                <?php else : ?>
                    <div class="mt-winner-photo mt-no-photo">
                        <span class="dashicons dashicons-awards"></span>
                    </div>
                <?php endif; ?>
                
                <h3 class="mt-winner-name"><?php echo esc_html($name); ?></h3>
                
                <?php if ($organization || $position) : ?>
                    <div class="mt-winner-meta">
                        <?php if ($position) : ?>
                            <span><?php echo esc_html($position); ?></span>
                        <?php endif; ?>
                        <?php if ($organization && $position) : ?>
                            <br>
                        <?php endif; ?>
                        <?php if ($organization) : ?>
                            <span><?php echo esc_html($organization); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($categories)) : ?>
                    <div class="mt-winner-category">
                        <?php echo esc_html($categories[0]->name); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($show_scores) : ?>
                    <div class="mt-winner-score">
                        <span class="mt-score-label"><?php _e('Average Score', 'mobility-trailblazers'); ?></span>
                        <span class="mt-score-value"><?php echo number_format($winner->avg_score, 1); ?>/10</span>
                    </div>
                <?php endif; ?>
                
                <?php if ($excerpt) : ?>
                    <div class="mt-winner-excerpt">
                        <?php echo wp_kses_post($excerpt); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php 
            $rank++;
        endforeach; 
        ?>
    </div>
</div>
</div><!-- .mt-root -->