<?php
/**
 * Candidate Profile Shortcode
 *
 * @package MobilityTrailblazers
 * @since 2.5.42
 */

namespace MobilityTrailblazers\Shortcodes;

use MobilityTrailblazers\Repositories\MT_Candidate_Repository;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Shortcode class loaded

/**
 * Class MT_Candidate_Profile_Shortcode
 *
 * Displays a single candidate profile using the repository pattern
 */
class MT_Candidate_Profile_Shortcode {
    
    /**
     * Repository instance
     *
     * @var MT_Candidate_Repository
     */
    private $repository;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->repository = new MT_Candidate_Repository();
    }
    
    /**
     * Register shortcode
     *
     * @return void
     */
    public static function register() {
        // Register shortcode
        $instance = new self();
        add_shortcode('mt_candidate_profile', [$instance, 'render']);
        // Shortcode registered
    }
    
    /**
     * Render the shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render($atts) {
        $atts = shortcode_atts([
            'slug' => '',
            'id' => 0
        ], $atts);
        
        // Get candidate
        $candidate = null;
        if (!empty($atts['slug'])) {
            $candidate = $this->repository->find_by_slug($atts['slug']);
        } elseif (!empty($atts['id'])) {
            $candidate = $this->repository->find($atts['id']);
        } else {
            // Try to get from URL parameter
            $slug = isset($_GET['candidate']) ? sanitize_text_field($_GET['candidate']) : '';
            if ($slug) {
                $candidate = $this->repository->find_by_slug($slug);
            }
        }
        
        if (!$candidate) {
            return '<div class="mt-candidate-not-found">' . __('Candidate not found.', 'mobility-trailblazers') . '</div>';
        }
        
        // Set global for template compatibility
        $GLOBALS['mt_current_candidate'] = $candidate;
        
        // Start output buffering
        ob_start();
        
        // Load the appropriate template
        $template = $this->get_template_path();
        if ($template && file_exists($template)) {
            include $template;
        } else {
            $this->render_basic_profile($candidate);
        }
        
        return ob_get_clean();
    }
    
    /**
     * Get the template path
     *
     * @return string|false
     */
    private function get_template_path() {
        $use_enhanced = get_option('mt_use_enhanced_template', true);
        
        if ($use_enhanced) {
            $v2_template = MT_PLUGIN_DIR . 'templates/frontend/single/single-mt_candidate-enhanced-v2.php';
            if (file_exists($v2_template)) {
                return $v2_template;
            }
            
            $enhanced_template = MT_PLUGIN_DIR . 'templates/frontend/single/single-mt_candidate-enhanced.php';
            if (file_exists($enhanced_template)) {
                return $enhanced_template;
            }
        }
        
        $basic_template = MT_PLUGIN_DIR . 'templates/frontend/single/single-mt_candidate.php';
        if (file_exists($basic_template)) {
            return $basic_template;
        }
        
        return false;
    }
    
    /**
     * Render basic profile if no template exists
     *
     * @param object $candidate
     * @return void
     */
    private function render_basic_profile($candidate) {
        ?>
        <div class="mt-candidate-profile">
            <h1><?php echo esc_html($candidate->name); ?></h1>
            
            <?php if (!empty($candidate->organization)): ?>
                <p><strong><?php _e('Organization:', 'mobility-trailblazers'); ?></strong> <?php echo esc_html($candidate->organization); ?></p>
            <?php endif; ?>
            
            <?php if (!empty($candidate->position)): ?>
                <p><strong><?php _e('Position:', 'mobility-trailblazers'); ?></strong> <?php echo esc_html($candidate->position); ?></p>
            <?php endif; ?>
            
            <?php if (!empty($candidate->description)): ?>
                <div class="mt-candidate-description">
                    <?php echo wp_kses_post($candidate->description); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($candidate->photo_url)): ?>
                <img src="<?php echo esc_url($candidate->photo_url); ?>" alt="<?php echo esc_attr($candidate->name); ?>" />
            <?php endif; ?>
        </div>
        <?php
    }
}