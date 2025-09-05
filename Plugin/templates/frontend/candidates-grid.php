<?php
/**
 * Candidates Grid Template
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
$columns = intval($atts['columns']);
$show_bio = $atts['show_bio'] === 'yes';
$show_category = $atts['show_category'] === 'yes';

// Helper functions are available for backward compatibility
if (!function_exists('mt_get_candidate_meta')) {
    require_once MT_PLUGIN_DIR . 'includes/functions/mt-candidate-helpers.php';
}

// Pre-process candidate data from repository
$candidate_data = [];
$candidate_categories = [];
$candidate_metadata = [];

if ($candidates->have_posts()) {
    foreach ($candidates->posts as $post) {
        $id = $post->ID;
        
        // Store candidate data from repository
        if (isset($post->candidate_data)) {
            $candidate_data[$id] = $post->candidate_data;
            
            // Extract organization and position from repository data
            $candidate_metadata[$id] = [
                'organization' => $post->candidate_data->organization ?? '',
                'position' => $post->candidate_data->position ?? ''
            ];
            
            // Extract categories from description_sections
            if ($show_category && !empty($post->candidate_data->description_sections)) {
                $sections = is_string($post->candidate_data->description_sections)
                    ? json_decode($post->candidate_data->description_sections, true)
                    : $post->candidate_data->description_sections;
                    
                if (isset($sections['category'])) {
                    // Map category slug to display name
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
                    
                    $candidate_categories[$id] = [
                        (object)['name' => $cat_name, 'slug' => $cat_slug]
                    ];
                }
            }
        } else {
            // Fallback: use traditional WordPress methods if repository data not available
            $candidate_metadata[$id] = [
                'organization' => get_post_meta($id, '_mt_organization', true),
                'position' => get_post_meta($id, '_mt_position', true)
            ];
            
            if ($show_category) {
                $terms = wp_get_object_terms($id, 'mt_award_category');
                if (!is_wp_error($terms) && !empty($terms)) {
                    $candidate_categories[$id] = $terms;
                }
            }
        }
    }
}
?>

<div class="mt-root">
<div class="mt-candidates-grid columns-<?php echo esc_attr($columns); ?>">
    <?php while ($candidates->have_posts()) : $candidates->the_post(); 
        $candidate_id = get_the_ID();
        $candidate = isset($candidate_data[$candidate_id]) ? $candidate_data[$candidate_id] : null;
        
        // Get data from pre-processed arrays
        $organization = $candidate_metadata[$candidate_id]['organization'] ?? '';
        $position = $candidate_metadata[$candidate_id]['position'] ?? '';
        $categories = $candidate_categories[$candidate_id] ?? [];
        
        // Get name and permalink - use repository data if available
        if ($candidate) {
            $name = $candidate->name;
            $slug = $candidate->slug;
            $permalink = home_url('/candidate/' . $slug . '/');
        } else {
            $name = get_the_title();
            $permalink = get_permalink();
        }
        
        // Special handling for Friedrich Dräxlmaier (Issue #13)
        $image_style = '';
        if ($candidate_id == 4627) {
            $image_style = 'style="object-position: center 20% !important; object-fit: cover !important;"';
        }
    ?>
        <div class="mt-candidate-grid-item" data-candidate-id="<?php echo $candidate_id; ?>">
            <a href="<?php echo esc_url($permalink); ?>" class="mt-candidate-link">
                <?php if (has_post_thumbnail()) : ?>
                    <div class="mt-candidate-image">
                        <?php 
                        // Apply inline style for specific candidates
                        if ($candidate_id == 4627) {
                            the_post_thumbnail('medium', [
                                'class' => 'mt-candidate-photo',
                                'style' => 'object-position: center 20% !important; object-fit: cover !important;'
                            ]);
                        } else {
                            the_post_thumbnail('medium', ['class' => 'mt-candidate-photo']);
                        }
                        ?>
                        <span class="mt-view-profile-overlay"><?php _e('View Profile', 'mobility-trailblazers'); ?></span>
                    </div>
                <?php elseif ($candidate && !empty($candidate->photo_attachment_id)) : ?>
                    <div class="mt-candidate-image">
                        <?php 
                        $photo_url = wp_get_attachment_image_url($candidate->photo_attachment_id, 'medium');
                        if ($photo_url) {
                            $img_attrs = [
                                'src' => $photo_url,
                                'class' => 'mt-candidate-photo',
                                'alt' => esc_attr($name)
                            ];
                            if ($candidate_id == 4627) {
                                $img_attrs['style'] = 'object-position: center 20% !important; object-fit: cover !important;';
                            }
                            echo '<img ';
                            foreach ($img_attrs as $attr => $value) {
                                echo $attr . '="' . esc_attr($value) . '" ';
                            }
                            echo '/>';
                        }
                        ?>
                        <span class="mt-view-profile-overlay"><?php _e('View Profile', 'mobility-trailblazers'); ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="mt-candidate-info">
                    <h3><?php echo esc_html($name); ?></h3>
                
                <?php if ($organization || $position) : ?>
                    <div class="mt-candidate-meta">
                        <?php if ($position) : ?>
                            <span class="mt-position"><?php echo esc_html($position); ?></span>
                        <?php endif; ?>
                        <?php if ($organization && $position) : ?>
                            <span class="mt-separator">@</span>
                        <?php endif; ?>
                        <?php if ($organization) : ?>
                            <span class="mt-organization"><?php echo esc_html($organization); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($show_category && !empty($categories)) : ?>
                    <div class="mt-candidate-categories">
                        <?php foreach ($categories as $category) : ?>
                            <span class="mt-category-tag"><?php echo esc_html($category->name); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($show_bio) : ?>
                    <?php 
                    // Get excerpt - use repository data if available
                    $excerpt = '';
                    if ($candidate && !empty($candidate->description_sections)) {
                        $sections = is_string($candidate->description_sections)
                            ? json_decode($candidate->description_sections, true)
                            : $candidate->description_sections;
                        if (isset($sections['description'])) {
                            $excerpt = wp_trim_words($sections['description'], 20);
                        }
                    }
                    if (empty($excerpt) && has_excerpt()) {
                        $excerpt = get_the_excerpt();
                    }
                    ?>
                    <?php if (!empty($excerpt)) : ?>
                        <div class="mt-candidate-bio">
                            <?php echo esc_html($excerpt); ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                </div>
            </a>
        </div>
    <?php endwhile; ?>
</div>
</div><!-- .mt-root -->