<?php

/**
 * Reder.
 *
 * @package OutpaceSEO
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

/**
 * OPSEO_Render Class.
 */
class OPSEO_Render
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_filter('pre_get_document_title', array($this, 'outpaceseo_title_tag'));
        remove_action('wp_head', 'rel_canonical');
        add_action('wp_head', array($this, 'outpaceseo_meta_desc'));
    }

    public function outpaceseo_title_tag($title)
    {
        global $post;
        $outpaceseo_title = get_post_meta($post->ID, '_outpaceseo_title', true);
        $global_search_option = get_option('outpaceseo_search_settings', get_outpaceseo_search_settings());

        if (!empty($outpaceseo_title)) {
            $title = $outpaceseo_title;
        } else {
            $title = isset($global_search_option['search']['title']) ? $global_search_option['search']['title'] : '';
        }
        return $title;
    }

    public function outpaceseo_meta_desc()
    {
        global $post;
        $outpaceseo_desc = get_post_meta($post->ID, '_outpaceseo_meta_desc', true);
        $global_search_option = get_option('outpaceseo_search_settings', get_outpaceseo_search_settings());
        $desc = isset($global_search_option['search']['description']) ? $global_search_option['search']['description'] : '';
        $indexing = get_post_meta($post->ID, '_outpaceseo_indexing', true);
        $follow = get_post_meta($post->ID, '_outpaceseo_follow', true);
        $canonical_url = get_post_meta($post->ID, '_outpaceseo_canonical_url', true);
        $index_status = $indexing == 'yes' ? 'index' : 'noindex';
        $follow_status = $follow == 'yes' ? 'follow' : 'nofollow';

        if (!empty($outpaceseo_desc)) {
            echo  "<meta name='description' content='" . stripslashes($outpaceseo_desc) . "' />\n";
        } else {
            echo  "<meta name='description' content='" . stripslashes($desc) . "' />\n";
        }

        if (!empty($indexing) || !empty($follow)) {
            echo "<meta name='robots' content='" . $index_status . ", " . $follow_status . "' />\n";
        }

        if (!empty($canonical_url)) {
            echo "<link rel='canonical' href='" . user_trailingslashit(trailingslashit($canonical_url)) . "' />\n";
        }
    }
}

new OPSEO_Render();
