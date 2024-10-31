<?php

/**
 * Setup menus in WP admin.
 *
 * @package OutpaceSEO
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

/**
 * OPSEO_Admin_Menus Class.
 */
class OPSEO_Process
{
    /**
     * Hook in tabs.
     */
    public function __construct()
    {
        add_action('add_attachment', array($this, 'outpaceseo_auto_image_attributes'));
        add_action('wp_ajax_outpaceseo_count_remaining_images', array($this, 'outpaceseo_count_remaining_images'));
        add_action('wp_ajax_rename_old_image', array($this, 'rename_old_image'));
        add_action('wp_ajax_outpaceseo_reset_bulk_updater_counter', array($this, 'outpaceseo_reset_bulk_updater_counter'));
    }

    /**
     * Reset bulk updater counter.
     */
    public function outpaceseo_reset_bulk_updater_counter()
    {
        check_ajax_referer('process-ajax-nonce', 'security');

        update_option('outpaceseo_bulk_counter', '0');

        $response = array(
            'message'            => __('Counter reset. The bulk updater will start from scratch in the next run.', 'outpaceseo'),
            'remaining_images'    => outpaceseo_count_remaining_images(true),
        );
        wp_send_json($response);
    }

    /**
     * Remaining image count.
     */
    public function outpaceseo_count_remaining_images()
    {
        $total_images = outpaceseo_total_images();

        $images_processed_count = get_option('outpaceseo_bulk_counter', '0');
        $images_processed_count = intval($images_processed_count);

        $reamining_images = max($total_images - $images_processed_count, 0);

        wp_send_json_success($reamining_images);
    }

    /**
     * Rename Old Images.
     */
    public function rename_old_image()
    {
        check_ajax_referer('process-ajax-nonce', 'security');

        $counter = get_option('outpaceseo_bulk_counter');
        $counter = intval($counter);

        global $wpdb;
        $image = $wpdb->get_row($wpdb->prepare(
            "SELECT ID, post_parent
            FROM {$wpdb->prefix}posts
            WHERE post_type = %s AND post_mime_type
            LIKE %s
            ORDER BY post_date LIMIT 1 OFFSET {$counter}
            ",
            'attachment',
            'image%'
        ));

        if ($image === NULL) {
            wp_die();
        }

        $image_name = outpaceseo_image_name_from_filename($image->ID, true);

        outpaceseo_update_image($image->ID, $image_name, true);

        $counter++;
        update_option('outpaceseo_bulk_counter', $counter);

        $image_url = wp_get_attachment_url($image->ID);

        echo wp_kses_post(__('Image attributes updated for: ', 'outpaceseo') . '<a href="' . get_edit_post_link($image->ID) . '">' . $image_url . '</a>');

        wp_die();
    }

    /**
     * Auto Image attributes.
     *
     * @param int $post_id ID of the post.
     */
    public function outpaceseo_auto_image_attributes($post_id)
    {

        if (!wp_attachment_is_image($post_id))
            return;

        $image = get_post($post_id);

        $image_name = $this->outpaceseo_process($image->ID);

        $this->outpaceseo_update($image->ID, $image_name);
    }

    /**
     * Process of getting attributes of Image via file name.
     *
     * @param int $image_id ID of the image.
     * @param bool $bulk Bulk uploader option.
     */
    public function outpaceseo_process($image_id, $bulk = false)
    {
        if ($image_id === NULL) return;

        $settings = get_outpaceseo_settings();

        $image_url            = wp_get_attachment_url($image_id);
        $image_extension     = pathinfo($image_url);
        $image_name         = basename($image_url, '.' . $image_extension['extension']);

        $filter_chars = array();

        if (isset($settings['image']['hyphens']) && boolval($settings['image']['hyphens'])) {
            $filter_chars[] = '-';
        }
        if (isset($settings['image']['under_score']) && boolval($settings['image']['under_score'])) {
            $filter_chars[] = '_';
        }
        if (isset($settings['image']['under_score']) && boolval($settings['image']['under_score'])) {
            $image_name = preg_replace('/[0-9]+/', '', $image_name);
        }

        if (!empty($filter_chars)) {
            $image_name = str_replace($filter_chars, ' ', $image_name);
        }

        $image_name = preg_replace('/\s\s+/', ' ', $image_name);
        $image_name = trim($image_name);

        if ($image_name === '') {
            $image_name = get_bloginfo('name');
        }

        return $image_name;
    }

    /**
     * Outpaceseo Update Image attr.
     *
     * @param int $image_id ID of the Image.
     * @param string $text Name of the image which is filtered.
     * @param bool $bulk Bulk uploader option.
     */
    public function outpaceseo_update($image_id, $text, $bulk = false)
    {
        if ($image_id === NULL) return false;

        $settings = get_outpaceseo_settings();

        $image            = array();
        $image['ID']     = $image_id;


        if (isset($settings['image']['image_title']) && boolval($settings['image']['image_title'])) {
            $image['post_title']     = $text;
        }
        if (isset($settings['image']['image_caption']) && boolval($settings['image']['image_caption'])) {
            $image['post_excerpt'] = $text;
        }
        if (isset($settings['image']['image_description']) && boolval($settings['image']['image_description'])) {
            $image['post_content'] = $text;
        }
        if (isset($settings['image']['image_alttext']) && boolval($settings['image']['image_alttext'])) {
            update_post_meta($image_id, '_wp_attachment_image_alt', $text);
        }

        $return_id = wp_update_post($image);

        if ($return_id == 0) return false;

        return true;
    }
}

new OPSEO_Process();
