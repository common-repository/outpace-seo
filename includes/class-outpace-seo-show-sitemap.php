<?php

namespace Outpace;

use Outpace\Lib\Controller;

class Frontend extends Controller
{
    /**
     * Sitemap constructor.
     */
    public function __construct()
    {
        add_filter('query_vars', [$this, 'register_query_vars'], 1, 1);
        add_filter('template_redirect', [$this, 'template_redirect'], 1, 0);
        add_shortcode('outpace_sitemap', [$this, 'outpace_html_sitemap']);
    }

    /**
     * Function
     * Booking form for the appointment
     *
     * @param [type] $atts
     * @return void
     */
    function outpace_html_sitemap($atts = array())
    {
        $atts = array_change_key_case((array) $atts, CASE_LOWER);
        $sitemap = new Sitemap();
        ob_start();
        $sitemap->generate_html_sitemap();
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }


    /**
     * Register Sitemap Query Variable
     * @param $query_vars
     * @return mixed
     */
    public function register_query_vars($query_vars)
    {
        array_push($query_vars, 'op_sitemap_xml');
        array_push($query_vars, 'op_sitemap_xsl');
        return $query_vars;
    }

    /**
     * Template Redirect
     */
    public function template_redirect()
    {
        global $wp_query;
        if (!empty($wp_query->query_vars['op_sitemap_xml'])) {
            $wp_query->is_404   = false;
            $wp_query->is_feed  = true;

            $sitemap = new Sitemap();
            $sitemap->show_sitemap();
            exit;
        }
        if (!empty($wp_query->query_vars['op_sitemap_xsl'])) {
            $wp_query->is_404   = false;
            $wp_query->is_feed  = true;

            Sitemap::generate_sitemap_xsl();
            exit;
        }
    }
}

new Frontend();
