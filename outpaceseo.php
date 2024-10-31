<?php

/**
 * Plugin Name: Outpace SEO
 * Plugin URI: https://outpaceseo.com/wordpress-seo-plugin/
 * Description: Enhance your SEO, improve website traffic, and reduce the number of plugins on your site.
 * Version: 1.3.1
 * Author: Outpaceseo
 * Author URI: https://outpaceseo.com/
 * Text Domain: outpaceseo
 * Domain Path: /languages/
 *
 * @package Outpace_Seo
 */

defined('ABSPATH') || exit;

if (!defined('OSEO_PLUGIN_FILE')) {
    define('OSEO_PLUGIN_FILE', __FILE__);
}

if (!defined('OUTPACE_INCLUDES')) {
    define('OUTPACE_INCLUDES', dirname(OSEO_PLUGIN_FILE) . '/includes/');
}


if (!class_exists('Outpace_SEO')) {
    include_once OUTPACE_INCLUDES . 'class-outpace-seo.php';
}

/**
 * Main instance of Outpace_SEO.
 *
 * Returns the main instance of Outpace_SEO to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return Outpace_SEO
 */
function outpaceseo()
{
    return Outpace_SEO::instance();
}
