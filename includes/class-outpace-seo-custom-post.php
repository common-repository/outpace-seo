<?php

/**
 * Setup menus in WP admin.
 *
 * @package OutpaceSEO
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

if (class_exists('OPSEO_Custom_Post_Type', false)) {
    return new OPSEO_Custom_Post_Type();
}

/**
 * OPSEO_Custom_Post_Type Class.
 */
class OPSEO_Custom_Post_Type
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('init', array($this, 'opseo_custom_posts'));
        add_action('init', array($this, 'outpaceseo_setup_wizard'));
        add_action('admin_init', array($this, 'admin_redirects'));
    }

    public function outpaceseo_setup_wizard()
    {
        if (isset($_GET['page']) && 'outpaceseo-schema-setup' === $_GET['page']) {

            include_once OUTPACE_INCLUDES . 'class-outpace-seo-schema-setup.php';
        }
    }

    public function admin_redirects()
    {
        global $pagenow;
        if ('post-new.php' === $pagenow && isset($_GET['post_type']) && 'outpaceseo_schema' === $_GET['post_type']) {

            wp_safe_redirect(admin_url('index.php?page=outpaceseo-schema-setup'));
            exit;
        }
    }

    public function opseo_custom_posts()
    {
        $post_types = [
            [
                'post_type' => 'outpaceseo_script',
                'singular'  => 'Script',
                'slug'      => 'script',
            ],
            [
                'post_type' => 'outpaceseo_schema',
                'singular'  => 'Schema',
                'slug'      => 'schema',
            ],
        ];

        foreach ($post_types as $key => $post_type) {
            $this->op_register_post_type($post_type);
        }
    }

    public function op_register_post_type($data)
    {
        $singular  = $data['singular'];
        $plural    = (isset($data['plural'])) ? $data['plural'] : $data['singular'] . 's';
        $post_type = $data['post_type'];
        $slug      = $data['slug'];

        // Headers and Footer Scripts
        $labels = array(
            'name'               => _x($plural, 'post type general name', 'outpaceseo'),
            'singular_name'      => _x($singular, 'post type singular name', 'outpaceseo'),
            'menu_name'          => _x($plural, 'admin menu', 'outpaceseo'),
            'name_admin_bar'     => _x($singular, 'add new on admin bar', 'outpaceseo'),
            'add_new'            => _x('Add New', $singular, 'outpaceseo'),
            'add_new_item'       => __('Add New ' . $singular, 'outpaceseo'),
            'new_item'           => __('New ' . $singular, 'outpaceseo'),
            'edit_item'          => __('Edit ' . $singular, 'outpaceseo'),
            'view_item'          => __('View ' . $singular, 'outpaceseo'),
            'all_items'          => __('All ' . $plural, 'outpaceseo'),
            'search_items'       => __('Search ' . $plural, 'outpaceseo'),
            'parent_item_colon'  => __('Parent ' . $plural . ':', 'outpaceseo'),
            'not_found'          => __('No ' . $plural . ' found.', 'outpaceseo'),
            'not_found_in_trash' => __('No ' . $plural . ' found in Trash.', 'outpaceseo')
        );
        $args = array(
            'labels'                => $labels,
            'description'        => __($singular . '.', 'outpaceseo'),
            'supports'              => array('title'),
            'hierarchical'          => false,
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => false,
            'menu_position'         => null,
            'menu_icon'             => 'dashicons-edit',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'capability_type'       => 'post',
            'query_var'    => true,
        );
        register_post_type($post_type, $args);
    }
}
