<?php
/**
 * Candidates List Table
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

// Load WP_List_Table if not loaded
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class MT_Candidates_List_Table
 *
 * Custom list table for candidates without CPT
 */
class MT_Candidates_List_Table extends \WP_List_Table {
    
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
        parent::__construct([
            'singular' => __('Candidate', 'mobility-trailblazers'),
            'plural'   => __('Candidates', 'mobility-trailblazers'),
            'ajax'     => false
        ]);
        
        $this->repository = new MT_Candidate_Repository();
    }
    
    /**
     * Get columns
     */
    public function get_columns() {
        return [
            'cb'           => '<input type="checkbox" />',
            'photo'        => __('Photo', 'mobility-trailblazers'),
            'name'         => __('Name', 'mobility-trailblazers'),
            'organization' => __('Organization', 'mobility-trailblazers'),
            'position'     => __('Position', 'mobility-trailblazers'),
            'country'      => __('Country', 'mobility-trailblazers'),
            'links'        => __('Links', 'mobility-trailblazers'),
        ];
    }
    
    /**
     * Get sortable columns
     */
    public function get_sortable_columns() {
        return [
            'name'         => ['name', true],
            'organization' => ['organization', false],
            'country'      => ['country', false],
        ];
    }
    
    /**
     * Get bulk actions
     */
    public function get_bulk_actions() {
        return [
            'delete' => __('Delete', 'mobility-trailblazers'),
            'export' => __('Export', 'mobility-trailblazers'),
        ];
    }
    
    /**
     * Prepare items
     */
    public function prepare_items() {
        $per_page = $this->get_items_per_page('candidates_per_page', 20);
        $current_page = $this->get_pagenum();
        
        // Get orderby and order
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'name';
        $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'ASC';
        
        // Get search term
        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        
        // Build args
        $args = [
            'orderby' => $orderby,
            'order'   => $order,
            'limit'   => $per_page,
            'offset'  => ($current_page - 1) * $per_page
        ];
        
        // Get data
        $this->items = $this->repository->find_all($args);
        
        // Set pagination
        $total_items = $this->repository->count();
        
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);
        
        // Set column headers
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = [$columns, $hidden, $sortable];
    }
    
    /**
     * Column default
     */
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'organization':
            case 'position':
            case 'country':
                return esc_html($item->$column_name ?? '-');
            default:
                return '-';
        }
    }
    
    /**
     * Column checkbox
     */
    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="candidate[]" value="%s" />',
            $item->id
        );
    }
    
    /**
     * Column photo
     */
    public function column_photo($item) {
        if ($item->photo_attachment_id) {
            return wp_get_attachment_image($item->photo_attachment_id, [50, 50]);
        }
        return '<span class="dashicons dashicons-admin-users" style="font-size:40px;color:#ccc;"></span>';
    }
    
    /**
     * Column name
     */
    public function column_name($item) {
        // Build row actions
        $actions = [
            'edit' => sprintf(
                '<a href="%s">%s</a>',
                admin_url('admin.php?page=mt-candidates&action=edit&candidate=' . $item->id),
                __('Edit', 'mobility-trailblazers')
            ),
            'view' => sprintf(
                '<a href="%s" target="_blank">%s</a>',
                home_url('/candidate/' . $item->slug . '/'),
                __('View', 'mobility-trailblazers')
            ),
            'delete' => sprintf(
                '<a href="%s" onclick="return confirm(\'%s\');">%s</a>',
                wp_nonce_url(
                    admin_url('admin-post.php?action=mt_delete_candidate&candidate=' . $item->id),
                    'delete_candidate'
                ),
                esc_js(__('Are you sure you want to delete this candidate?', 'mobility-trailblazers')),
                __('Delete', 'mobility-trailblazers')
            ),
        ];
        
        return sprintf(
            '<strong><a href="%s">%s</a></strong>%s',
            admin_url('admin.php?page=mt-candidates&action=edit&candidate=' . $item->id),
            esc_html($item->name),
            $this->row_actions($actions)
        );
    }
    
    /**
     * Column links
     */
    public function column_links($item) {
        $links = [];
        
        if (!empty($item->linkedin_url)) {
            $links[] = sprintf(
                '<a href="%s" target="_blank" title="LinkedIn"><span class="dashicons dashicons-linkedin"></span></a>',
                esc_url($item->linkedin_url)
            );
        }
        
        if (!empty($item->website_url)) {
            $links[] = sprintf(
                '<a href="%s" target="_blank" title="Website"><span class="dashicons dashicons-admin-links"></span></a>',
                esc_url($item->website_url)
            );
        }
        
        if (!empty($item->article_url)) {
            $links[] = sprintf(
                '<a href="%s" target="_blank" title="Article"><span class="dashicons dashicons-media-document"></span></a>',
                esc_url($item->article_url)
            );
        }
        
        return !empty($links) ? implode(' ', $links) : '-';
    }
    
    /**
     * Process bulk actions
     */
    public function process_bulk_action() {
        // Security check
        if ('delete' === $this->current_action()) {
            $nonce = isset($_REQUEST['_wpnonce']) ? $_REQUEST['_wpnonce'] : '';
            
            if (!wp_verify_nonce($nonce, 'bulk-' . $this->_args['plural'])) {
                die(__('Security check failed', 'mobility-trailblazers'));
            }
            
            $ids = isset($_REQUEST['candidate']) ? $_REQUEST['candidate'] : [];
            
            if (!empty($ids)) {
                foreach ($ids as $id) {
                    $this->repository->delete(intval($id));
                }
            }
        }
    }
    
    /**
     * No items message
     */
    public function no_items() {
        _e('No candidates found.', 'mobility-trailblazers');
    }
}