<?php
/**
 * CSS Component Loader for Phase 2 Architecture
 * 
 * Manages loading of BEM components and CSS layers
 * Provides feature flags for progressive rollout
 * 
 * @package MobilityTrailblazers
 * @since 2.6.0
 */

namespace MobilityTrailblazers\Core;

class MT_CSS_Loader {
    
    /**
     * CSS version flag
     */
    const CSS_VERSION_PHASE2 = 'phase2';
    const CSS_VERSION_LEGACY = 'legacy';
    
    /**
     * Component registry
     */
    private $components = [];
    
    /**
     * Current CSS version
     */
    private $version;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->version = get_option('mt_css_version', self::CSS_VERSION_LEGACY);
        $this->register_components();
    }
    
    /**
     * Register BEM components
     */
    private function register_components() {
        $this->components = [
            // Core components
            'base' => [
                'file' => 'framework/mobility-trailblazers-framework-v4.css',
                'deps' => [],
                'version' => MT_VERSION,
                'media' => 'all',
                'priority' => 1
            ],
            
            // BEM Components
            'candidate-card' => [
                'file' => 'components/card/mt-candidate-card.css',
                'deps' => ['base'],
                'version' => MT_VERSION . '-bem',
                'media' => 'all',
                'priority' => 10,
                'contexts' => ['frontend', 'admin']
            ],
            
            'evaluation-form' => [
                'file' => 'components/form/mt-evaluation-form.css',
                'deps' => ['base'],
                'version' => MT_VERSION . '-bem',
                'media' => 'all',
                'priority' => 10,
                'contexts' => ['frontend', 'admin']
            ],
            
            'dashboard-widget' => [
                'file' => 'components/dashboard/mt-dashboard-widget.css',
                'deps' => ['base'],
                'version' => MT_VERSION . '-bem',
                'media' => 'all',
                'priority' => 10,
                'contexts' => ['admin']
            ],
            
            'assignments-table' => [
                'file' => 'components/table/mt-assignments-table.css',
                'deps' => ['base'],
                'version' => MT_VERSION . '-bem',
                'media' => 'all',
                'priority' => 10,
                'contexts' => ['admin']
            ],
            
            'jury-stats' => [
                'file' => 'components/stats/mt-jury-stats.css',
                'deps' => ['base'],
                'version' => MT_VERSION . '-bem',
                'media' => 'all',
                'priority' => 10,
                'contexts' => ['admin', 'frontend']
            ],
            
            'notification' => [
                'file' => 'components/notification/mt-notification.css',
                'deps' => ['base'],
                'version' => MT_VERSION . '-bem',
                'media' => 'all',
                'priority' => 10,
                'contexts' => ['admin', 'frontend']
            ]
        ];
    }
    
    /**
     * Initialize CSS loading
     */
    public function init() {
        add_action('wp_enqueue_scripts', [$this, 'load_frontend_styles'], 5);
        add_action('admin_enqueue_scripts', [$this, 'load_admin_styles'], 5);
        
        // Add inline CSS for layers
        add_action('wp_head', [$this, 'add_layer_definitions'], 1);
        add_action('admin_head', [$this, 'add_layer_definitions'], 1);
    }
    
    /**
     * Load frontend styles
     */
    public function load_frontend_styles() {
        if ($this->version === self::CSS_VERSION_PHASE2) {
            $this->load_components('frontend');
        } else {
            // Load legacy consolidated CSS
            wp_enqueue_style(
                'mt-phase2-consolidated',
                MT_PLUGIN_URL . 'assets/css/mt-phase2-consolidated.css',
                [],
                MT_VERSION
            );
        }
    }
    
    /**
     * Load admin styles
     */
    public function load_admin_styles() {
        if ($this->version === self::CSS_VERSION_PHASE2) {
            $this->load_components('admin');
        } else {
            // Load legacy consolidated CSS
            wp_enqueue_style(
                'mt-phase2-consolidated',
                MT_PLUGIN_URL . 'assets/css/mt-phase2-consolidated.css',
                [],
                MT_VERSION
            );
        }
    }
    
    /**
     * Load components for a specific context
     */
    private function load_components($context) {
        // Sort components by priority
        uasort($this->components, function($a, $b) {
            return ($a['priority'] ?? 10) - ($b['priority'] ?? 10);
        });
        
        foreach ($this->components as $handle => $component) {
            // Check if component should load in this context
            if (!isset($component['contexts']) || in_array($context, $component['contexts'])) {
                $this->enqueue_component($handle, $component);
            }
        }
    }
    
    /**
     * Enqueue a single component
     */
    private function enqueue_component($handle, $component) {
        $handle = 'mt-' . $handle;
        $url = MT_PLUGIN_URL . 'assets/css/' . $component['file'];
        
        wp_enqueue_style(
            $handle,
            $url,
            array_map(function($dep) { return 'mt-' . $dep; }, $component['deps']),
            $component['version'],
            $component['media']
        );
    }
    
    /**
     * Add CSS layer definitions
     */
    public function add_layer_definitions() {
        if ($this->version === self::CSS_VERSION_PHASE2) {
            ?>
            <style id="mt-css-layers">
                /* CSS Layer Order Definition */
                @layer reset, base, layout, components, utilities;
                
                /* CSS Custom Properties */
                @layer base {
                    :root {
                        /* Primary Colors */
                        --mt-primary: #003C3D;
                        --mt-secondary: #004C5F;
                        --mt-accent: #C1693C;
                        --mt-kupfer-bold: #AA4E2C;
                        
                        /* Status Colors */
                        --mt-success: #27ae60;
                        --mt-warning: #f39c12;
                        --mt-error: #e74c3c;
                        
                        /* Neutrals */
                        --mt-body-text: #302C37;
                        --mt-bg-base: #ffffff;
                        --mt-blue-accent: #e0f2f7;
                        
                        /* Shadows */
                        --mt-shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
                        --mt-shadow-md: 0 4px 8px rgba(0,0,0,0.1);
                        --mt-shadow-lg: 0 8px 16px rgba(0,0,0,0.15);
                        
                        /* Transitions */
                        --mt-transition: all 0.3s ease;
                    }
                }
            </style>
            <?php
        }
    }
    
    /**
     * Get current CSS version
     */
    public function get_version() {
        return $this->version;
    }
    
    /**
     * Set CSS version (for testing)
     */
    public function set_version($version) {
        $this->version = $version;
        update_option('mt_css_version', $version);
    }
    
    /**
     * Check if a component is loaded
     */
    public function is_component_loaded($component) {
        return isset($this->components[$component]);
    }
    
    /**
     * Get component info
     */
    public function get_component_info($component) {
        return $this->components[$component] ?? null;
    }
    
    /**
     * Get all components
     */
    public function get_all_components() {
        return $this->components;
    }
}