<?php

/**
 * Installation related functions and actions.
 *
 * @package Outpaceseo
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

/**
 * OUTPACESEO_Install Class.
 */
class OUTPACESEO_Install
{

    public static function init()
    {
        add_filter('plugin_action_links_' . OSEO_PLUGIN_BASENAME, array(__CLASS__, 'plugin_action_links'));
        self::set_rewrite_hooks();
        self::activate_rewrite_rules();
        flush_rewrite_rules();
    }
    /**
     * Install Outpaceseo.
     */
    public static function install()
    {
        $settings = get_option('outpaceseo_settings');
        $search_settings = get_option('outpaceseo_search_settings');

        if ($settings !== false) {
            update_option('outpaceseo_settings', $settings);
        }
        if ($search_settings !== false) {
            update_option('outpaceseo_search_settings', $search_settings);
        }

        add_option('outpaceseo_bulk_counter', '0');
    }

    /**
     * Set Rewrite Hooks
     */
    public static function set_rewrite_hooks()
    {
        add_filter('rewrite_rules_array', [self::class, 'add_rewrite_rules'], 1, 1);
    }

    /**
     * Add Custom Rewrite Rules
     *
     * @param $wp_rules
     * @return array
     */
    public static function add_rewrite_rules($wp_rules)
    {
        $sitemap_url    = str_replace('.', '\.', 'op_sitemap.xml') . '$';
        $outpace_rules  = [
            $sitemap_url => 'index.php?op_sitemap_xml=true',
            'sitemap\.xsl$' => 'index.php?op_sitemap_xsl=true'
        ];

        return array_merge($outpace_rules, $wp_rules);
    }

    /**
     * Activate Rewrite Rules
     */
    public static function activate_rewrite_rules()
    {
        flush_rewrite_rules(false);
        update_option('op_rules', '1.0');
    }

    /**
     * Display action links in the Plugins list table.
     *
     * @param  array $actions Plugin Action links.
     */
    public static function plugin_action_links($actions)
    {
        $new_actions = array(
            'settings' => '<a href="' . admin_url('admin.php?page=outpaceseo') . '" aria-label="' . esc_attr__('View Outpaceseo Settings', 'outpaceseo') . '">' . esc_html__('Settings', 'outpaceseo') . '</a>',
        );

        return array_merge($new_actions, $actions);
    }
}
OUTPACESEO_Install::init();
