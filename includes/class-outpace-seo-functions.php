<?php

/**
 * Outpace SEO Functions
 *
 * Where functions come to die.
 *
 * @package Outpace_SEO\Functions
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

if (!function_exists('is_ajax')) {

    /**
     * Is_ajax - Returns true when the page is loaded via ajax.
     *
     * @return bool
     */
    function is_ajax()
    {
        return function_exists('wp_doing_ajax') ? wp_doing_ajax() : defined('DOING_AJAX');
    }
}

/**
 * Wrapper for outpaceseo_doing_it_wrong.
 *
 * @since 1.0.0
 * @param string $function Function used.
 * @param string $message  Message to log.
 * @param string $version  Version the message was added in.
 */
function outpaceseo_doing_it_wrong($function, $message, $version)
{
    // @codingStandardsIgnoreStart
    $message .= ' Backtrace: ' . wp_debug_backtrace_summary();

    if (is_ajax()) {
        do_action('doing_it_wrong_run', $function, $message, $version);
        error_log("{$function} was called incorrectly. {$message}. This message was added in version {$version}.");
    } else {
        _doing_it_wrong($function, $message, $version);
    }
    // @codingStandardsIgnoreEnd
}

/**
 * Outpaceseo Validater and Sanitization.
 *
 * @param array $settings Outpaceseo settings.
 */
function outpace_settings_validater_and_sanitizer($settings)
{
    return $settings;
}

/**
 * Count total number of images in the database
 *
 */
function outpaceseo_total_images()
{
    global $wpdb;
    $total_images = $wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->prefix}posts WHERE post_type='attachment' AND post_mime_type LIKE 'image%'");

    return $total_images;
}

/**
 * Image name from filename
 *
 * @param	$image_id	Image Id.
 * @param	$bulk		Bulk upload or not?
 */
function outpaceseo_image_name_from_filename($image_id, $bulk = false)
{
    if ($image_id === NULL)
        return;

    $settings = get_outpaceseo_settings();

    $image_url            = wp_get_attachment_url($image_id);
    $image_extension     = pathinfo($image_url);
    $image_name         = basename($image_url, '.' . $image_extension['extension']);

    if ($bulk === true) {
        $image_name = str_replace('-', ' ', $image_name);
        $image_name = str_replace('_', ' ', $image_name);
        return $image_name;
    }

    $filter_chars = array();

    if (isset($settings['images']['hyphens']) && boolval($settings['images']['hyphens'])) {
        $filter_chars[] = '-';
    }
    if (isset($settings['images']['under_score']) && boolval($settings['images']['under_score'])) {
        $filter_chars[] = '_';
    }

    if (!empty($filter_chars)) {
        $image_name = str_replace($filter_chars, ' ', $image_name);
    }

    $image_name = preg_replace('/\s\s+/', ' ', $image_name);
    $image_name = trim($image_name);

    return $image_name;
}

/**
 * Image attributes updated on database.
 *
 * @param	$image_id	Image ID.
 * @param	$text		Text for the attributes
 * @param	$bulk		bulk updater or not?
 */
function outpaceseo_update_image($image_id, $text, $bulk = false)
{
    if ($image_id === NULL) return false;

    $settings = get_outpaceseo_settings();

    $image            = array();
    $image['ID']     = $image_id;

    if ($bulk == true) {
        $image['post_title']     = $text;
        $image['post_excerpt']     = $text;
        $image['post_content']     = $text;

        update_post_meta($image_id, '_wp_attachment_image_alt', $text);
    } else {
        if (isset($settings['image']['image_title']) && boolval($settings['image']['image_title'])) {
            $image['post_title'] = $text;
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
    }

    $return_id = wp_update_post($image);

    if ($return_id == 0) return false;

    return true;
}

/**
 * Get all Outpaceseo screen ids.
 *
 * @return array
 */
function outpaceseo_get_screen_ids()
{
    $screen_ids    = array(
        'toplevel_page_outpaceseo'
    );

    return apply_filters('outpaceseo_screen_ids', $screen_ids);
}

/**
 * Count Remaining Images.
 */
function outpaceseo_count_remaining_images()
{
    $total_images = outpaceseo_total_images();

    $images_processed_count = get_option('outpaceseo_bulk_counter', '0');
    $images_processed_count = intval($images_processed_count);

    $reamining_images = max($total_images - $images_processed_count, 0);

    return $reamining_images;
}

/**
 * Get number of images updated
 *
 */
function outpaceseo_updated_image_count()
{
    $counter = get_option('outpaceseo_bulk_counter');
    return $counter;
}

/**
 * Set global default values for settings.
 */
function get_outpaceseo_settings()
{

    $default_settings = array('image' => array(
        'image_title'             => '1',
        'image_caption'         => '1',
        'image_description'     => '1',
        'image_alttext'         => '1',
        'hyphens'                 => '1',
        'under_score'             => '1',
        'numbers'                 => '1',
        //'image_title_to_html' 	=> '1',
    ));

    $settings = get_option('outpaceseo_settings', $default_settings);

    return $settings;
}

/**
 * Set global default values for settings.
 */
function get_outpaceseo_search_settings()
{

    $default_settings = array('search' => array(
        'title'             => '',
        'description'         => '',
    ));

    $settings = get_option('outpaceseo_search_settings', $default_settings);

    return $settings;
}

/**
 * Set global default values for settings.
 */
function get_outpaceseo_sitemap_settings()
{

    $default_settings = array('sitemap' => array(
        'homepage'        => true,
        'pages'           => true,
        'posts'           => true,
        'recent_archive'  => true,
        'categories'      => true,
        'tags'            => true,
        'author_pages'    => true,
        'older_archive'   => true,
    ));

    $settings = get_option('outpaceseo_sitemap_settings', $default_settings);

    return $settings;
}


function outpaceseo_search_settings_callback()
{
    $settings = get_outpaceseo_search_settings();
    $title = isset($settings['search']['title']) ? $settings['search']['title'] : '';
    $desc = isset($settings['search']['description']) ? $settings['search']['description'] : '';
?>
    <div class="os-wrapper">
        <div class="outpaceseo_search_single">
            <label for="outpace_seo_title" class="form-label">
                <?php _e('SEO Title', 'outpaceseo') ?>
                <div class="tooltip-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="12" fill="currentColor" class="bi bi-question-square-fill" viewBox="0 0 16 16">
                        <path d="M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2zm3.496 6.033a.237.237 0 0 1-.24-.247C5.35 4.091 6.737 3.5 8.005 3.5c1.396 0 2.672.73 2.672 2.24 0 1.08-.635 1.594-1.244 2.057-.737.559-1.01.768-1.01 1.486v.105a.25.25 0 0 1-.25.25h-.81a.25.25 0 0 1-.25-.246l-.004-.217c-.038-.927.495-1.498 1.168-1.987.59-.444.965-.736.965-1.371 0-.825-.628-1.168-1.314-1.168-.803 0-1.253.478-1.342 1.134-.018.137-.128.25-.266.25h-.825zm2.325 6.443c-.584 0-1.009-.394-1.009-.927 0-.552.425-.94 1.01-.94.609 0 1.028.388 1.028.94 0 .533-.42.927-1.029.927z" />
                    </svg>
                    <span class="tooltip-wrappertext"><?php _e('SEO Title here', 'outpaceseo'); ?></span>
                </div>
            </label>
            <input type="text" class="regular-text" name="outpaceseo_search_settings[search][title]" id="outpace_seo_title" value="<?php echo esc_attr($title); ?>" />
        </div>
        <div class="outpaceseo_search_single">
            <label for="outpace_meta_description" class="form-label">
                <?php _e('Meta description', 'outpaceseo') ?>
                <div class="tooltip-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="12" fill="currentColor" class="bi bi-question-square-fill" viewBox="0 0 16 16">
                        <path d="M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2zm3.496 6.033a.237.237 0 0 1-.24-.247C5.35 4.091 6.737 3.5 8.005 3.5c1.396 0 2.672.73 2.672 2.24 0 1.08-.635 1.594-1.244 2.057-.737.559-1.01.768-1.01 1.486v.105a.25.25 0 0 1-.25.25h-.81a.25.25 0 0 1-.25-.246l-.004-.217c-.038-.927.495-1.498 1.168-1.987.59-.444.965-.736.965-1.371 0-.825-.628-1.168-1.314-1.168-.803 0-1.253.478-1.342 1.134-.018.137-.128.25-.266.25h-.825zm2.325 6.443c-.584 0-1.009-.394-1.009-.927 0-.552.425-.94 1.01-.94.609 0 1.028.388 1.028.94 0 .533-.42.927-1.029.927z" />
                    </svg>
                    <span class="tooltip-wrappertext"><?php _e('Meta Description here', 'outpaceseo'); ?></span>
                </div>
            </label>
            <textarea class="regular-text" name="outpaceseo_search_settings[search][description]" rows="3" id="outpace_meta_description"><?php echo esc_attr($desc); ?></textarea>
        </div>
    </div>
<?php
}

function outpaceseo_sitemap_settings_callback()
{
    $settings = get_outpaceseo_sitemap_settings();
?>
    <div class="os-wrapper">
        <div class="op_notice">
            <div>
                <p class="sitemap-p"><?php _e('Please use this shortcode for HTML sitemap', 'outpaceseo'); ?> <code>[outpace_sitemap]</code></p>
            </div>
            <div>
                <p class="sitemap-p"><?php _e('You can preview your sitemap here', 'outpaceseo'); ?> <a href=<?php echo get_home_url() . '/op_sitemap.xml'; ?> target="_blank"><?php echo get_home_url() . '/op_sitemap.xml'; ?></a></p>
            </div>
        </div>
        <fieldset>
            <label for="outpaceseo_sitemap_settings[sitemap][homepage]">
                <input type="checkbox" name="outpaceseo_sitemap_settings[sitemap][homepage]" id="outpaceseo_sitemap_settings[sitemap][homepage]" value="1" <?php if (isset($settings['sitemap']['homepage'])) checked('1', $settings['sitemap']['homepage']); ?>>
                <span><?php _e('Include Homepage', 'outpaceseo') ?></span>
            </label><br>
            <label for="outpaceseo_sitemap_settings[sitemap][pages]">
                <input type="checkbox" name="outpaceseo_sitemap_settings[sitemap][pages]" id="outpaceseo_sitemap_settings[sitemap][pages]" value="1" <?php if (isset($settings['sitemap']['pages'])) checked('1', $settings['sitemap']['pages']); ?>>
                <span><?php _e('Include Pages', 'outpaceseo') ?></span>
            </label><br>
            <label for="outpaceseo_sitemap_settings[sitemap][posts]">
                <input type="checkbox" name="outpaceseo_sitemap_settings[sitemap][posts]" id="outpaceseo_sitemap_settings[sitemap][posts]" value="1" <?php if (isset($settings['sitemap']['posts'])) checked('1', $settings['sitemap']['posts']); ?>>
                <span><?php _e('Include Posts', 'outpaceseo') ?></span>
            </label><br>
            <label for="outpaceseo_sitemap_settings[sitemap][recent_archive]">
                <input type="checkbox" name="outpaceseo_sitemap_settings[sitemap][recent_archive]" id="outpaceseo_sitemap_settings[sitemap][recent_archive]" value="1" <?php if (isset($settings['sitemap']['recent_archive'])) checked('1', $settings['sitemap']['recent_archive']); ?>>
                <span><?php _e('Include Recent Archive', 'outpaceseo') ?></span>
            </label><br>
        </fieldset>

        <fieldset>
            <label for="outpaceseo_sitemap_settings[sitemap][categories]">
                <input type="checkbox" name="outpaceseo_sitemap_settings[sitemap][categories]" id="outpaceseo_sitemap_settings[sitemap][categories]" value="1" <?php if (isset($settings['sitemap']['categories'])) checked('1', $settings['sitemap']['categories']); ?>>
                <span><?php _e('Include Categories', 'outpaceseo') ?></span>
            </label><br>
            <label for="outpaceseo_sitemap_settings[sitemap][tags]">
                <input type="checkbox" name="outpaceseo_sitemap_settings[sitemap][tags]" id="outpaceseo_sitemap_settings[sitemap][tags]" value="1" <?php if (isset($settings['sitemap']['tags'])) checked('1', $settings['sitemap']['tags']); ?>>
                <span><?php _e('Includes Tags', 'outpaceseo') ?></span>
            </label><br>
            <label for="outpaceseo_sitemap_settings[sitemap][older_archive]">
                <input type="checkbox" name="outpaceseo_sitemap_settings[sitemap][older_archive]" id="outpaceseo_sitemap_settings[sitemap][older_archive]" value="1" <?php if (isset($settings['sitemap']['older_archive'])) checked('1', $settings['sitemap']['older_archive']); ?>>
                <span><?php _e('Include Older Archive', 'outpaceseo') ?></span>
            </label></br>
            <label for="outpaceseo_sitemap_settings[sitemap][author_pages]">
                <input type="checkbox" name="outpaceseo_sitemap_settings[sitemap][author_pages]" id="outpaceseo_sitemap_settings[sitemap][author_pages]" value="1" <?php if (isset($settings['sitemap']['author_pages'])) checked('1', $settings['sitemap']['author_pages']); ?>>
                <span><?php _e('Include Author Pages', 'outpaceseo') ?></span>
            </label>
    </div>
<?php
}

/**
 * Outpaceseo General Settings HTML.
 */
function outpaceseo_general_settings_callback()
{
    $settings = get_outpaceseo_settings();
?>
    <div class="os-wrapper">
        <fieldset>
            <label for="outpaceseo_settings[image][image_title]">
                <input type="checkbox" name="outpaceseo_settings[image][image_title]" id="outpaceseo_settings[image][image_title]" value="1" <?php if (isset($settings['image']['image_title'])) checked('1', $settings['image']['image_title']); ?>>
                <span><?php _e('Set Image Title for new uploads', 'outpaceseo') ?></span>
            </label><br>
            <label for="outpaceseo_settings[image][image_alttext]">
                <input type="checkbox" name="outpaceseo_settings[image][image_alttext]" id="outpaceseo_settings[image][image_alttext]" value="1" <?php if (isset($settings['image']['image_alttext'])) checked('1', $settings['image']['image_alttext']); ?>>
                <span><?php _e('Set Image Alt Text for new uploads', 'outpaceseo') ?></span>
            </label><br>
            <label for="outpaceseo_settings[image][image_caption]">
                <input type="checkbox" name="outpaceseo_settings[image][image_caption]" id="outpaceseo_settings[image][image_caption]" value="1" <?php if (isset($settings['image']['image_caption'])) checked('1', $settings['image']['image_caption']); ?>>
                <span><?php _e('Set Image Caption for new uploads', 'outpaceseo') ?></span>
            </label><br>
            <label for="outpaceseo_settings[image][image_description]">
                <input type="checkbox" name="outpaceseo_settings[image][image_description]" id="outpaceseo_settings[image][image_description]" value="1" <?php if (isset($settings['image']['image_description'])) checked('1', $settings['image']['image_description']); ?>>
                <span><?php _e('Set Image Description for new uploads', 'outpaceseo') ?></span>
            </label><br>
        </fieldset>

        <fieldset>
            <label for="outpaceseo_settings[image][hyphens]">
                <input type="checkbox" name="outpaceseo_settings[image][hyphens]" id="outpaceseo_settings[image][hyphens]" value="1" <?php if (isset($settings['image']['hyphens'])) checked('1', $settings['image']['hyphens']); ?>>
                <span><?php _e('Remove hyphens ( - ) from filename', 'outpaceseo') ?></span>
            </label><br>
            <label for="outpaceseo_settings[image][under_score]">
                <input type="checkbox" name="outpaceseo_settings[image][under_score]" id="outpaceseo_settings[image][under_score]" value="1" <?php if (isset($settings['image']['under_score'])) checked('1', $settings['image']['under_score']); ?>>
                <span><?php _e('Remove underscores ( _ ) from filename', 'outpaceseo') ?></span>
            </label><br>
            <label for="outpaceseo_settings[image][numbers]">
                <input type="checkbox" name="outpaceseo_settings[image][numbers]" id="outpaceseo_settings[image][numbers]" value="1" <?php if (isset($settings['image']['numbers'])) checked('1', $settings['image']['numbers']); ?>>
                <span><?php _e('Remove numbers ( 0-9 ) from filename', 'outpaceseo') ?></span>
            </label><br>
        </fieldset>

        <!-- <fieldset>
			<label for="outpaceseo_settings[image][image_title_to_html]">
				<input type="checkbox" name="outpaceseo_settings[image][image_title_to_html]" id="outpaceseo_settings[image][image_title_to_html]" value="1" <?php if (isset($settings['image']['image_title_to_html'])) checked('1', $settings['image']['image_title_to_html']); ?>>
				<span><?php _e('Insert Image Title into post HTML. This will add title="Image Title" in the &lt;img&gt; tag', 'outpaceseo') ?></span>
			</label><br>
		</fieldset> -->
    </div>
<?php
}
