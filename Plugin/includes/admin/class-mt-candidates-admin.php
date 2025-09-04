<?php
/**
 * Candidates Admin Page
 *
 * @package MobilityTrailblazers
 * @since 2.5.43
 */

namespace MobilityTrailblazers\Admin;

use MobilityTrailblazers\Repositories\MT_Candidate_Repository;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Candidates_Admin
 *
 * Handles the candidates admin page without CPT
 */
class MT_Candidates_Admin {
    
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
     * Initialize the admin page
     */
    public function init() {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_post_mt_save_candidate', [$this, 'save_candidate']);
        add_action('admin_post_mt_delete_candidate', [$this, 'delete_candidate']);
        add_action('wp_ajax_mt_quick_edit_candidate', [$this, 'ajax_quick_edit']);
    }
    
    /**
     * Add menu page
     */
    public function add_menu_page() {
        add_submenu_page(
            'mobility-trailblazers',
            __('Candidates', 'mobility-trailblazers'),
            __('Candidates', 'mobility-trailblazers'),
            'mt_manage_candidates',
            'mt-candidates',
            [$this, 'render_page']
        );
    }
    
    /**
     * Render the candidates page
     */
    public function render_page() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        
        switch ($action) {
            case 'edit':
                $this->render_edit_page();
                break;
            case 'new':
                $this->render_new_page();
                break;
            default:
                $this->render_list_page();
                break;
        }
    }
    
    /**
     * Render list page
     */
    private function render_list_page() {
        // Include list table class
        if (!class_exists('MT_Candidates_List_Table')) {
            require_once MT_PLUGIN_DIR . 'includes/admin/class-mt-candidates-list-table.php';
        }
        
        $list_table = new MT_Candidates_List_Table();
        $list_table->prepare_items();
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Candidates', 'mobility-trailblazers'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=mt-candidates&action=new'); ?>" class="page-title-action">
                <?php _e('Add New', 'mobility-trailblazers'); ?>
            </a>
            
            <?php if (isset($_GET['message'])) : ?>
                <?php $this->display_admin_notice(); ?>
            <?php endif; ?>
            
            <form method="get">
                <input type="hidden" name="page" value="mt-candidates" />
                <?php
                $list_table->search_box(__('Search Candidates', 'mobility-trailblazers'), 'candidate');
                $list_table->display();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render edit page
     */
    private function render_edit_page() {
        $candidate_id = isset($_GET['candidate']) ? intval($_GET['candidate']) : 0;
        $candidate = $this->repository->find($candidate_id);
        
        if (!$candidate) {
            wp_die(__('Candidate not found.', 'mobility-trailblazers'));
        }
        
        $this->render_form($candidate);
    }
    
    /**
     * Render new candidate page
     */
    private function render_new_page() {
        $this->render_form();
    }
    
    /**
     * Render candidate form
     */
    private function render_form($candidate = null) {
        $is_new = ($candidate === null);
        $form_action = $is_new ? 'mt_save_candidate' : 'mt_save_candidate';
        
        // Decode description sections if exists
        $description_sections = [];
        if ($candidate && !empty($candidate->description_sections)) {
            $description_sections = is_string($candidate->description_sections) 
                ? json_decode($candidate->description_sections, true) 
                : $candidate->description_sections;
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo $is_new ? __('Add New Candidate', 'mobility-trailblazers') : __('Edit Candidate', 'mobility-trailblazers'); ?></h1>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
                <?php wp_nonce_field('mt_save_candidate', 'mt_candidate_nonce'); ?>
                <input type="hidden" name="action" value="<?php echo $form_action; ?>">
                <?php if (!$is_new) : ?>
                    <input type="hidden" name="candidate_id" value="<?php echo $candidate->id; ?>">
                <?php endif; ?>
                
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="candidate_name"><?php _e('Name', 'mobility-trailblazers'); ?> <span class="required">*</span></label>
                            </th>
                            <td>
                                <input type="text" name="candidate[name]" id="candidate_name" 
                                       value="<?php echo $candidate ? esc_attr($candidate->name) : ''; ?>" 
                                       class="regular-text" required />
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="candidate_slug"><?php _e('Slug', 'mobility-trailblazers'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="candidate[slug]" id="candidate_slug" 
                                       value="<?php echo $candidate ? esc_attr($candidate->slug) : ''; ?>" 
                                       class="regular-text" />
                                <p class="description"><?php _e('Leave empty to auto-generate from name.', 'mobility-trailblazers'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="candidate_organization"><?php _e('Organization', 'mobility-trailblazers'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="candidate[organization]" id="candidate_organization" 
                                       value="<?php echo $candidate ? esc_attr($candidate->organization) : ''; ?>" 
                                       class="regular-text" />
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="candidate_position"><?php _e('Position', 'mobility-trailblazers'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="candidate[position]" id="candidate_position" 
                                       value="<?php echo $candidate ? esc_attr($candidate->position) : ''; ?>" 
                                       class="regular-text" />
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="candidate_country"><?php _e('Country', 'mobility-trailblazers'); ?></label>
                            </th>
                            <td>
                                <select name="candidate[country]" id="candidate_country">
                                    <option value=""><?php _e('Select Country', 'mobility-trailblazers'); ?></option>
                                    <option value="Germany" <?php selected($candidate ? $candidate->country : '', 'Germany'); ?>>Germany</option>
                                    <option value="Austria" <?php selected($candidate ? $candidate->country : '', 'Austria'); ?>>Austria</option>
                                    <option value="Switzerland" <?php selected($candidate ? $candidate->country : '', 'Switzerland'); ?>>Switzerland</option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="candidate_linkedin"><?php _e('LinkedIn URL', 'mobility-trailblazers'); ?></label>
                            </th>
                            <td>
                                <input type="url" name="candidate[linkedin_url]" id="candidate_linkedin" 
                                       value="<?php echo $candidate ? esc_url($candidate->linkedin_url) : ''; ?>" 
                                       class="regular-text" />
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="candidate_website"><?php _e('Website URL', 'mobility-trailblazers'); ?></label>
                            </th>
                            <td>
                                <input type="url" name="candidate[website_url]" id="candidate_website" 
                                       value="<?php echo $candidate ? esc_url($candidate->website_url) : ''; ?>" 
                                       class="regular-text" />
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="candidate_description"><?php _e('Description', 'mobility-trailblazers'); ?></label>
                            </th>
                            <td>
                                <?php
                                $description = isset($description_sections['description']) ? $description_sections['description'] : '';
                                wp_editor($description, 'candidate_description', [
                                    'textarea_name' => 'candidate[description]',
                                    'textarea_rows' => 10,
                                    'media_buttons' => false,
                                ]);
                                ?>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label><?php _e('Photo', 'mobility-trailblazers'); ?></label>
                            </th>
                            <td>
                                <div id="candidate-photo-preview">
                                    <?php if ($candidate && $candidate->photo_attachment_id) : ?>
                                        <?php echo wp_get_attachment_image($candidate->photo_attachment_id, 'thumbnail'); ?>
                                    <?php endif; ?>
                                </div>
                                <input type="hidden" name="candidate[photo_attachment_id]" id="candidate_photo_id" 
                                       value="<?php echo $candidate ? $candidate->photo_attachment_id : ''; ?>" />
                                <button type="button" class="button" id="upload-photo-button">
                                    <?php _e('Select Photo', 'mobility-trailblazers'); ?>
                                </button>
                                <button type="button" class="button" id="remove-photo-button" 
                                        style="<?php echo (!$candidate || !$candidate->photo_attachment_id) ? 'display:none;' : ''; ?>">
                                    <?php _e('Remove Photo', 'mobility-trailblazers'); ?>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary">
                        <?php echo $is_new ? __('Add Candidate', 'mobility-trailblazers') : __('Update Candidate', 'mobility-trailblazers'); ?>
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=mt-candidates'); ?>" class="button">
                        <?php _e('Cancel', 'mobility-trailblazers'); ?>
                    </a>
                </p>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Media uploader
            var mediaUploader;
            
            $('#upload-photo-button').on('click', function(e) {
                e.preventDefault();
                
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                mediaUploader = wp.media({
                    title: '<?php _e('Select Candidate Photo', 'mobility-trailblazers'); ?>',
                    button: {
                        text: '<?php _e('Use this photo', 'mobility-trailblazers'); ?>'
                    },
                    multiple: false
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#candidate_photo_id').val(attachment.id);
                    $('#candidate-photo-preview').html('<img src="' + attachment.sizes.thumbnail.url + '" />');
                    $('#remove-photo-button').show();
                });
                
                mediaUploader.open();
            });
            
            $('#remove-photo-button').on('click', function(e) {
                e.preventDefault();
                $('#candidate_photo_id').val('');
                $('#candidate-photo-preview').html('');
                $(this).hide();
            });
            
            // Auto-generate slug
            $('#candidate_name').on('blur', function() {
                if (!$('#candidate_slug').val()) {
                    var slug = $(this).val().toLowerCase()
                        .replace(/[^\w\s-]/g, '')
                        .replace(/\s+/g, '-')
                        .replace(/--+/g, '-')
                        .trim();
                    $('#candidate_slug').val(slug);
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Save candidate
     */
    public function save_candidate() {
        // Check nonce
        if (!isset($_POST['mt_candidate_nonce']) || !wp_verify_nonce($_POST['mt_candidate_nonce'], 'mt_save_candidate')) {
            wp_die(__('Security check failed.', 'mobility-trailblazers'));
        }
        
        // Check permissions
        if (!current_user_can('mt_manage_candidates')) {
            wp_die(__('You do not have permission to perform this action.', 'mobility-trailblazers'));
        }
        
        $candidate_data = $_POST['candidate'];
        $candidate_id = isset($_POST['candidate_id']) ? intval($_POST['candidate_id']) : 0;
        
        // Generate slug if empty
        if (empty($candidate_data['slug'])) {
            $candidate_data['slug'] = sanitize_title($candidate_data['name']);
        }
        
        // Prepare description sections
        $description_sections = [];
        if (!empty($candidate_data['description'])) {
            $description_sections['description'] = wp_kses_post($candidate_data['description']);
        }
        $candidate_data['description_sections'] = $description_sections;
        unset($candidate_data['description']);
        
        if ($candidate_id) {
            // Update existing
            $success = $this->repository->update($candidate_id, $candidate_data);
            $message = $success ? 'updated' : 'error';
        } else {
            // Create new
            $new_id = $this->repository->create($candidate_data);
            $success = ($new_id !== false);
            $message = $success ? 'added' : 'error';
        }
        
        wp_redirect(admin_url('admin.php?page=mt-candidates&message=' . $message));
        exit;
    }
    
    /**
     * Delete candidate
     */
    public function delete_candidate() {
        // Check nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_candidate')) {
            wp_die(__('Security check failed.', 'mobility-trailblazers'));
        }
        
        // Check permissions
        if (!current_user_can('mt_manage_candidates')) {
            wp_die(__('You do not have permission to perform this action.', 'mobility-trailblazers'));
        }
        
        $candidate_id = isset($_GET['candidate']) ? intval($_GET['candidate']) : 0;
        
        if ($candidate_id) {
            $success = $this->repository->delete($candidate_id);
            $message = $success ? 'deleted' : 'error';
        } else {
            $message = 'error';
        }
        
        wp_redirect(admin_url('admin.php?page=mt-candidates&message=' . $message));
        exit;
    }
    
    /**
     * Display admin notice
     */
    private function display_admin_notice() {
        $message = isset($_GET['message']) ? $_GET['message'] : '';
        $class = 'notice notice-success is-dismissible';
        $text = '';
        
        switch ($message) {
            case 'added':
                $text = __('Candidate added successfully.', 'mobility-trailblazers');
                break;
            case 'updated':
                $text = __('Candidate updated successfully.', 'mobility-trailblazers');
                break;
            case 'deleted':
                $text = __('Candidate deleted successfully.', 'mobility-trailblazers');
                break;
            case 'error':
                $text = __('An error occurred. Please try again.', 'mobility-trailblazers');
                $class = 'notice notice-error is-dismissible';
                break;
        }
        
        if ($text) {
            printf('<div class="%s"><p>%s</p></div>', $class, $text);
        }
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_assets($hook) {
        if (strpos($hook, 'mt-candidates') === false) {
            return;
        }
        
        wp_enqueue_media();
        wp_enqueue_editor();
    }
}