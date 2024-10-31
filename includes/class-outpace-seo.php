<?php

/**
 * Outpace_SEO setup
 *
 * @package Outpace_SEO
 * @since   1.0.0
 */

defined('ABSPATH') || exit;

/**
 * Main Outpace_SEO Class.
 *
 * @class   Outpace_SEO
 */
final class Outpace_SEO
{

    /**
     * Outpace_SEO version.
     *
     * @var string
     */
    public $version = '1.3.1';

    /**
     * The single instance of the class.
     *
     * @var   Outpace_SEO
     * @since 1.0.0
     */
    protected static $instance = null;


    /**
     * Main Outpace_SEO Instance.
     *
     * Ensures only one instance of Outpace_SEO is loaded or can be loaded.
     *
     * @since  1.0.0
     * @static
     * @return Outpace_SEO - Main instance.
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Cloning is forbidden.
     *
     * @since 1.0.0
     */
    public function __clone()
    {
        outpaceseo_doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?', 'outpaceseo'), '1.0.0');
    }

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0.0
     */
    public function __wakeup()
    {
        outpaceseo_doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?', 'outpaceseo'), '1.0.0');
    }

    /**
     * Outpace_SEO Constructor.
     */
    public function __construct()
    {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();

        do_action('outpace_seo_loaded');
    }

    /**
     * Hook into actions and filters.
     *
     * @since 1.0.0
     */
    private function init_hooks()
    {
        register_activation_hook(OSEO_PLUGIN_FILE, array('OUTPACESEO_Install', 'install'));
        add_action('init', array($this, 'init'), 0);
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_styles'));
    }


    /**
     * Define Outpaceseo Constants.
     */
    private function define_constants()
    {
        $upload_dir = wp_upload_dir(null, false);

        $this->define('OSEO_ABSPATH', dirname(OSEO_PLUGIN_FILE) . '/');
        $this->define('OSEO_PLUGIN_BASENAME', plugin_basename(OSEO_PLUGIN_FILE));
        $this->define('OSEO_VERSION', $this->version);
        $this->define('OSEO_CACHE_KEY', 'outpaceseo_optimized_structured_data');
    }


    /**
     * Define constant if not already set.
     *
     * @param string      $name  Constant name.
     * @param string|bool $value Constant value.
     */
    private function define($name, $value)
    {
        if (!defined($name)) {
            define($name, $value);
        }
    }

    /**
     * Enqueue scripts.
     */
    public function admin_scripts()
    {
        $screen    = get_current_screen();
        $screen_id = $screen ? $screen->id : '';
        $suffix    = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
        wp_register_script('jquery-confirm', outpaceseo()->plugin_url() . '/assets/js/jquery-confirm/jquery-confirm.min.js', array('jquery'), '3.3.4', true);
        wp_register_script('outpaceseo_ace', outpaceseo()->plugin_url() . '/assets/js/ace/ace.js', array('jquery'), $this->version, true);
        wp_register_script('outpaceseo', outpaceseo()->plugin_url() . '/assets/js/outpaceseo.js', array('jquery'), $this->version, true);
        wp_register_script('bootstrap', outpaceseo()->plugin_url() . '/assets/js/bootstrap.min.js', array('jquery'), '5.2.0', true);
        wp_register_script('outpaceseo-schema-js', outpaceseo()->plugin_url() . '/assets/js/outpaceseo-schema.js', array('jquery', 'jquery-ui-tooltip'), $this->version, true);

        wp_localize_script(
            'outpaceseo',
            'outpaceseo_params',
            array(
                'i18n_outpaceseo_ok' => esc_html__('OK', 'outpaceseo'),
                'i18n_outpaceseo_cancel' => esc_html__('Cancel', 'outpaceseo'),
                'test_text'          => esc_html__('You are about to run the test bulk updater. Press OK to confirm.', 'outpaceseo'),
                'reset_text' => esc_html__('You are about to reset the counter. Press OK to confirm.', 'outpaceseo'),
                'run_text' => esc_html__('You are about to run the bulk updater. Press OK to confirm.', 'outpaceseo'),
                'ajax_nonce' => wp_create_nonce('process-ajax-nonce'),
            )
        );

        if ($screen_id === 'outpaceseo_script' || in_array($screen_id, outpaceseo_get_screen_ids(), true)) {
            wp_enqueue_script('outpaceseo');
            wp_enqueue_script('jquery-confirm');
            wp_enqueue_script('outpaceseo_ace');
        }
        wp_enqueue_script('outpaceseo-schema-js');
        wp_enqueue_script('bootstrap');
    }

    /**
     * Enqueue styles.
     */
    public function admin_styles()
    {
        wp_register_style('jquery-confirm', outpaceseo()->plugin_url() . '/assets/css/jquery-confirm/jquery-confirm.min.css', array(), '3.3.4');
        wp_register_style('outpaceseo-css', outpaceseo()->plugin_url() . '/assets/css/outpaceseo.css', array(), $this->version);
        wp_register_style('bootstrap', outpaceseo()->plugin_url() . '/assets/css/bootstrap.css', array(), '5.2.0');
        wp_enqueue_style('jquery-confirm');
        wp_enqueue_style('outpaceseo-css');
        // wp_enqueue_style('bootstrap');
    }

    /**
     * Include required core files used in admin and on the frontend.
     */
    public function includes()
    {
        include_once OUTPACE_INCLUDES . 'class-outpace-seo-functions.php';
        include_once OUTPACE_INCLUDES . 'class-outpace-seo-admin-menu.php';
        include_once OUTPACE_INCLUDES . 'class-outpace-seo-process.php';
        include_once OUTPACE_INCLUDES . 'class-outpace-seo-install.php';
        include_once OUTPACE_INCLUDES . 'class-outpace-seo-custom-post.php';
        include_once OUTPACE_INCLUDES . 'class-outpace-seo-schema.php';
        include_once OUTPACE_INCLUDES . 'class-outpace-seo-target-rule-field.php';
        include_once OUTPACE_INCLUDES . 'class-outpace-seo-schema-amp.php';
        include_once OUTPACE_INCLUDES . 'class-outpace-seo-schema-markup.php';
        include_once OUTPACE_INCLUDES . 'class-outpace-seo-schema-template.php';

        require_once(OUTPACE_INCLUDES . 'Lib/Controller.php');
        require_once(OUTPACE_INCLUDES . 'Lib/SitemapGenerator.php');
        require_once(OUTPACE_INCLUDES . 'Lib/QueryBuilder.php');
        require_once(OUTPACE_INCLUDES . 'class-outpace-seo-show-sitemap.php');
        require_once(OUTPACE_INCLUDES . 'class-outpace-seo-sitemap.php');

        if ($this->is_request('frontend')) {
            $this->frontend_includes();
        }
    }

    /**
     * Include required frontend files.
     */
    public function frontend_includes()
    {
        include_once OUTPACE_INCLUDES . 'class-outpace-seo-render.php';
    }

    /**
     * What type of request is this?
     *
     * @param  string $type admin, ajax, cron or frontend.
     * @return bool
     */
    private function is_request($type)
    {
        switch ($type) {
            case 'admin':
                return is_admin();
            case 'ajax':
                return defined('DOING_AJAX');
            case 'cron':
                return defined('DOING_CRON');
            case 'frontend':
                return (!is_admin() || defined('DOING_AJAX')) && !defined('DOING_CRON') && !defined('REST_REQUEST');
        }
    }

    /**
     * Init Outpace_SEO when WordPress Initialises.
     */
    public function init()
    {
        // Before init action.
        do_action('before_outpace_seo_init');

        // Set up localisation.
        $this->load_plugin_textdomain();

        // Init action.
        do_action('outpace_seo_init');
    }


    /**
     * Load Localisation files.
     *
     * Note: the first-loaded translation file overrides any following ones if the same translation is present.
     *
     * Locales found in:
     *      - WP_LANG_DIR/outpaceseo/outpaceseo-LOCALE.mo
     *      - WP_LANG_DIR/plugins/outpaceseo-LOCALE.mo
     */
    public function load_plugin_textdomain()
    {
        if (function_exists('determine_locale')) {
            $locale = determine_locale();
        } else {
            $locale = is_admin() ? get_user_locale() : get_locale();
        }

        $locale = apply_filters('plugin_locale', $locale, 'outpaceseo');

        unload_textdomain('outpaceseo');
        load_textdomain('outpaceseo', WP_LANG_DIR . '/outpaceseo/outpaceseo-' . $locale . '.mo');
        load_plugin_textdomain('outpaceseo', false, plugin_basename(dirname(OSEO_PLUGIN_FILE)) . '/languages');
    }

    /**
     * Get the plugin url.
     *
     * @param String $path Path.
     *
     * @return string
     */
    public function plugin_url($path = '/')
    {
        return untrailingslashit(plugins_url($path, OSEO_PLUGIN_FILE));
    }

    /**
     * Get the plugin path.
     *
     * @return string
     */
    public function plugin_path()
    {
        return untrailingslashit(plugin_dir_path(OSEO_PLUGIN_FILE));
    }

    /**
     * Get Ajax URL.
     *
     * @return string
     */
    public function ajax_url()
    {
        return admin_url('admin-ajax.php', 'relative');
    }
}

new Outpace_SEO();
