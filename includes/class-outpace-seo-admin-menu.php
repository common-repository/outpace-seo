<?php

/**
 * Setup menus in WP admin.
 *
 * @package OutpaceSEO
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

if (class_exists('OPSEO_Admin_Menus', false)) {
    return new OPSEO_Admin_Menus();
}

/**
 * OPSEO_Admin_Menus Class.
 */
class OPSEO_Admin_Menus
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('admin_notices', array($this, 'op_conflict_notice'));
        add_action('admin_menu', array($this, 'admin_menu'), 9);
        add_action('admin_init', array($this, 'outpaceseo_register_settings'));
        add_action('add_meta_boxes', array($this, 'outpaceseo_metabox'));
        add_action('save_post', array($this, 'outpaceseo_metabox_save_postdata'));
        add_action('wp_head', array($this, 'op_custom_js'));
        add_action('wp_footer', array($this, 'op_custom_js_footer'));
        add_action('wp_body_open', array($this, 'op_custom_js_body'));
        add_filter('manage_edit-outpaceseo_script_columns', array($this, 'op_script_add_columns'));
        add_action('manage_outpaceseo_script_posts_custom_column',  array($this, 'op_script_column_value'));
        add_action('admin_head', array($this, 'op_hide_notices'), 1);
    }

    public function op_conflict_notice()
    {
        if (is_plugin_active('wordpress-seo/wp-seo.php')) {
            echo '<div class="error notice">
                <p>Outpace SEO plugin can not work properly. Please go to the <a href="' . admin_url('plugins.php') . '">
                plugin Page</a> to deactivate the <strong>Yoast SEO</strong> plugin in order to have better result.</p>
             </div>';
        }
    }

    public function op_hide_notices()
    {
        $screen    = get_current_screen();
        $screen_id = $screen ? $screen->id : '';
        if ($screen_id === 'edit-outpaceseo_script' || $screen_id === 'edit-outpaceseo_schema' || in_array($screen_id, outpaceseo_get_screen_ids(), true)) {
            remove_all_actions('admin_notices');
        }
    }

    /**
     * Show the custom colum value.
     *
     * @param array $col Column Title.
     */
    public function op_script_column_value($col)
    {
        global $post;
        switch ($col) {
            case 'script_status':
                $script_status = get_post_meta($post->ID, '_outpaceseo_script_status', true);
                if ($script_status) {
                    $text = "<span class='outpace_green'>Yes<span>";
                } else {
                    $text = "<span class='outpace_red'>No<span>";
                }
                echo $text;
                break;
            case 'script_position':
                $script_position = get_post_meta($post->ID, '_outpaceseo_script_position', true);
                echo strtoupper($script_position);
                break;
        }
    }

    /**
     * Adding custom column
     *
     * @param array $column Column Title.
     */
    public function op_script_add_columns($column)
    {
        unset($column['date']);

        $column['script_status'] = __('Active?', 'outpaceseo');
        $column['script_position'] = __('Position', 'outpaceseo');
        $column['date'] = 'Date';

        return $column;
    }

    /**
     * Adding custom JS on Header.
     */
    public function op_custom_js()
    {
        $args = array(
            'post_type' => 'outpaceseo_script',
            'posts_per_page' => -1
        );
        $custom_posts = get_posts($args);

        foreach ($custom_posts as $value) :
            $status = get_post_meta($value->ID, '_outpaceseo_script_status', true);
            $text = get_post_meta($value->ID, '_outpaceseo_script_code', true);
            $position = get_post_meta($value->ID, '_outpaceseo_script_position', true);
            $pagetrack = get_post_meta($value->ID, '_outpaceseo_script_pagetrack', true);
            $pagetrack_type = get_post_meta($value->ID, '_outpaceseo_script_pagetrack_type', true);
            $page = get_post_meta($value->ID, '_outpaceseo_script_page', true);
            $post = get_post_meta($value->ID, '_outpaceseo_script_post', true);
            if (!$status || $position != 'header') {
                continue;
            }

            if ($pagetrack == 'specific') {
                if ($pagetrack_type == 'page') {
                    if (is_page($page)) {
                        echo $text . "\n";
                    }
                } elseif ($pagetrack_type == 'post') {
                    if (is_single($post)) {
                        echo $text . "\n";
                    }
                }
            } else {
                echo $text . "\n";
            }
        endforeach;
        wp_reset_postdata();
    }

    /**
     * Adding Script on Body.
     */
    public function op_custom_js_body()
    {
        $args = array(
            'post_type' => 'outpaceseo_script',
            'posts_per_page' => -1
        );
        $custom_posts = get_posts($args);

        foreach ($custom_posts as $value) :
            $status = get_post_meta($value->ID, '_outpaceseo_script_status', true);
            $text = get_post_meta($value->ID, '_outpaceseo_script_code', true);
            $position = get_post_meta($value->ID, '_outpaceseo_script_position', true);
            $pagetrack = get_post_meta($value->ID, '_outpaceseo_script_pagetrack', true);
            $pagetrack_type = get_post_meta($value->ID, '_outpaceseo_script_pagetrack_type', true);
            $page = get_post_meta($value->ID, '_outpaceseo_script_page', true);
            $post = get_post_meta($value->ID, '_outpaceseo_script_post', true);
            if (!$status || $position != 'body') {
                continue;
            }

            if ($pagetrack == 'specific') {
                if ($pagetrack_type == 'page') {
                    if (is_page($page)) {
                        echo $text . "\n";
                    }
                } elseif ($pagetrack_type == 'post') {
                    if (is_single($post)) {
                        echo $text . "\n";
                    }
                }
            } else {
                echo $text . "\n";
            }
        endforeach;
        wp_reset_postdata();
    }

    /**
     * Adding Script on footer.
     */
    public function op_custom_js_footer()
    {
        $args = array(
            'post_type' => 'outpaceseo_script',
            'posts_per_page' => -1
        );
        $custom_posts = get_posts($args);

        foreach ($custom_posts as $value) :
            $status = get_post_meta($value->ID, '_outpaceseo_script_status', true);
            $text = get_post_meta($value->ID, '_outpaceseo_script_code', true);
            $position = get_post_meta($value->ID, '_outpaceseo_script_position', true);
            $pagetrack = get_post_meta($value->ID, '_outpaceseo_script_pagetrack', true);
            $pagetrack_type = get_post_meta($value->ID, '_outpaceseo_script_pagetrack_type', true);
            $page = get_post_meta($value->ID, '_outpaceseo_script_page', true);
            $post = get_post_meta($value->ID, '_outpaceseo_script_post', true);
            if (!$status || $position != 'footer') {
                continue;
            }

            if ($pagetrack == 'specific') {
                if ($pagetrack_type == 'page') {
                    if (is_page($page)) {
                        echo $text . "\n";
                    }
                } elseif ($pagetrack_type == 'post') {
                    if (is_single($post)) {
                        echo $text . "\n";
                    }
                }
            } else {
                echo $text . "\n";
            }
        endforeach;
        wp_reset_postdata();
    }

    /**
     * Register Submenu
     *
     * @param array $post_type Post Types.
     */
    public function outpaceseo_metabox($post_type)
    {
        if (in_array($post_type, array('post', 'page'))) {
            add_meta_box(
                'outpace_seo_section',
                __('Outpace SEO', 'outpaceseo'),
                array($this, 'outpace_seo_section'),
                $post_type,
                'advanced',
                'high'
            );
        }
        if ($post_type === 'outpaceseo_script') {
            add_meta_box(
                'outpace_script_section',
                __('Outpace SEO Script', 'outpaceseo'),
                array($this, 'outpace_script_section'),
                $post_type,
                'advanced',
                'high'
            );
        }
    }

    /**
     * Outpace Script Section.
     *
     * @param object $post Post.
     */
    public function outpace_script_section($post)
    {
        $status = get_post_meta($post->ID, '_outpaceseo_script_status', true);
        $text = get_post_meta($post->ID, '_outpaceseo_script_code', true);
        $text = str_replace('<', '&lt;', $text);
        $text = str_replace('>', '&gt;', $text);
        $position = get_post_meta($post->ID, '_outpaceseo_script_position', true);
        $pagetrack = get_post_meta($post->ID, '_outpaceseo_script_pagetrack', true);
        $style = $pagetrack == '' || $pagetrack == 'whole' ? 'style="display: none;"' : '';
        $pagetrack_type = get_post_meta($post->ID, '_outpaceseo_script_pagetrack_type', true);
        $script_post = get_post_meta($post->ID, '_outpaceseo_script_post', true);
        $script_page = get_post_meta($post->ID, '_outpaceseo_script_page', true);
        $pagetrack_type = get_post_meta($post->ID, '_outpaceseo_script_pagetrack_type', true);
        $pageStyle = '';
        $postStyle = '';
        if ($pagetrack == '' || $pagetrack == 'whole' || ($pagetrack == 'specific' && $pagetrack_type == 'page')) {
            $postStyle = 'style="display: none;"';
        }
        if ($pagetrack == '' || $pagetrack == 'whole' || $pagetrack == 'specific' && $pagetrack_type == 'post') {
            $pageStyle = 'style="display: none;"';
        }

        $args = array(
            'post_type' => 'page',
            'post_status' => 'publish'
        );
        $pages = get_pages($args);

        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish'
        );
        $posts = get_posts($args);
?>
        <div class="script_status_wrapper" style="margin-bottom:5px;">
            <label for="script_status"><?php _e('Active', 'outpaceseo'); ?></label>
            <input type="checkbox" id="script_status" name="outpaceseo_script_settings[status]" value="1" <?php if (isset($status)) checked('1', $status); ?>><br>
        </div>
        <div id="codeAce" style="height:400px; width:700px;"><?php echo esc_html($text); ?></div>
        <textarea id="code" name="outpaceseo_script_settings[code]" ui-visible="" style="display: none;"></textarea><br>
        <p><?php _e('Position inside the code', 'outpaceseo') ?></p>
        <select id="position" name="outpaceseo_script_settings[position]">
            <option value="header" <?php if ($position == 'header') echo 'selected="selected"'; ?>>On Header</option>
            <option value="body" <?php if ($position == 'body') echo 'selected="selected"'; ?>>On Body</option>
            <option value="footer" <?php if ($position == 'footer') echo 'selected="selected"'; ?>>On Footer</option>
        </select>
        <div class="outpace_script_pagetrack">
            <p><?php _e('In which page do you want to insert this code?', 'outpaceseo') ?></p>
            <select id="position" name="outpaceseo_script_settings[pagetrack]">
                <option value="whole" <?php if ($pagetrack == 'whole') echo 'selected="selected"'; ?>>In the whole website</option>
                <option value="specific" <?php if ($pagetrack == 'specific') echo 'selected="selected"'; ?>>In specific pages or posts</option>
            </select>
        </div>
        <div class="only-specific" <?php echo $style; ?>>
            <p><?php _e('Include tracking code in which pages?', 'outpaceseo') ?></p>
            <select id="position" name="outpaceseo_script_settings[pagetrack_type]">
                <option value="page" <?php if ($pagetrack_type == 'page') echo 'selected="selected"'; ?>>Page</option>
                <option value="post" <?php if ($pagetrack_type == 'post') echo 'selected="selected"'; ?>>Post</option>
            </select>
        </div>
        <div class="pages" <?php echo $pageStyle; ?>>
            <p><?php _e('Choose the page', 'outpaceseo') ?></p>
            <select name="outpaceseo_script_settings[page]">
                <?php
                foreach ($pages as $value) { ?>
                    <option value=<?php echo $value->ID; ?> <?php if ($script_page == $value->ID) echo 'selected="selected"'; ?>><?php echo $value->post_title; ?></option>
                <?php }
                ?>
            </select>
        </div>
        <div class="posts" <?php echo $postStyle; ?>>
            <p><?php _e('Choose the post', 'outpaceseo') ?></p>
            <select name="outpaceseo_script_settings[post]">
                <?php
                foreach ($posts as $value) { ?>
                    <option value=<?php echo $value->ID; ?> <?php if ($script_post == $value->ID) echo 'selected="selected"'; ?>><?php echo $value->post_title; ?></option>
                <?php }
                ?>
            </select>
        </div>
    <?php
        wp_nonce_field(
            'outpaceseo_nonce_field',
            'outpace_seo_script_nonce'
        );
    }

    /**
     * Search Tab Section
     *
     * @param object $post Post.
     */
    public function outpace_seo_section($post)
    {
        $title = get_post_meta($post->ID, '_outpaceseo_title', true);
        $desc = get_post_meta($post->ID, '_outpaceseo_meta_desc', true);
        $slug = get_post_meta($post->ID, '_outpaceseo_page_slug', true);
        $indexing = get_post_meta($post->ID, '_outpaceseo_indexing', true);
        $follow = get_post_meta($post->ID, '_outpaceseo_follow', true);
        $canonical_url = get_post_meta($post->ID, '_outpaceseo_canonical_url', true);

        if (empty($slug)) {
            global $post;
            $slug = $post->post_name;
        }

    ?>
        <div class="outpace-seo-metabox">
            <div class="outpace_for_title">
                <label for="outpace_meta_title" class="form-label">
                    <?php _e('Meta Title', 'outpaceseo') ?>
                    <div class="tooltip-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="12" fill="currentColor" class="bi bi-question-square-fill" viewBox="0 0 16 16">
                            <path d="M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2zm3.496 6.033a.237.237 0 0 1-.24-.247C5.35 4.091 6.737 3.5 8.005 3.5c1.396 0 2.672.73 2.672 2.24 0 1.08-.635 1.594-1.244 2.057-.737.559-1.01.768-1.01 1.486v.105a.25.25 0 0 1-.25.25h-.81a.25.25 0 0 1-.25-.246l-.004-.217c-.038-.927.495-1.498 1.168-1.987.59-.444.965-.736.965-1.371 0-.825-.628-1.168-1.314-1.168-.803 0-1.253.478-1.342 1.134-.018.137-.128.25-.266.25h-.825zm2.325 6.443c-.584 0-1.009-.394-1.009-.927 0-.552.425-.94 1.01-.94.609 0 1.028.388 1.028.94 0 .533-.42.927-1.029.927z" />
                        </svg>
                        <span class="tooltip-wrappertext"><?php _e('Meta Title here', 'outpaceseo') ?></span>
                    </div>
                </label>
                <input type="text" class="form-control" id="outpace_meta_title" autocomplete="off" value="<?= esc_attr($title) ?>" name="outpace_title">
            </div>
            <div class="outpace_for_slug">
                <label for="outpace_page_slug" class="form-label">
                    <?php _e('Slug', 'outpaceseo') ?>
                    <div class="tooltip-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="12" fill="currentColor" class="bi bi-question-square-fill" viewBox="0 0 16 16">
                            <path d="M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2zm3.496 6.033a.237.237 0 0 1-.24-.247C5.35 4.091 6.737 3.5 8.005 3.5c1.396 0 2.672.73 2.672 2.24 0 1.08-.635 1.594-1.244 2.057-.737.559-1.01.768-1.01 1.486v.105a.25.25 0 0 1-.25.25h-.81a.25.25 0 0 1-.25-.246l-.004-.217c-.038-.927.495-1.498 1.168-1.987.59-.444.965-.736.965-1.371 0-.825-.628-1.168-1.314-1.168-.803 0-1.253.478-1.342 1.134-.018.137-.128.25-.266.25h-.825zm2.325 6.443c-.584 0-1.009-.394-1.009-.927 0-.552.425-.94 1.01-.94.609 0 1.028.388 1.028.94 0 .533-.42.927-1.029.927z" />
                        </svg>
                        <span class="tooltip-wrappertext"><?php _e('Page slug here', 'outpaceseo') ?></span>
                    </div>
                </label>
                <input type="text" class="form-control" id="outpace_page_slug" autocomplete="off" value="<?= esc_attr($slug) ?>" name="outpace_page_slug">
            </div>
            <div class="outpace_for_description">
                <label for="outpace_meta_description" class="form-label">
                    <?php _e('Meta description', 'outpaceseo') ?>
                    <div class="tooltip-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="12" fill="currentColor" class="bi bi-question-square-fill" viewBox="0 0 16 16">
                            <path d="M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2zm3.496 6.033a.237.237 0 0 1-.24-.247C5.35 4.091 6.737 3.5 8.005 3.5c1.396 0 2.672.73 2.672 2.24 0 1.08-.635 1.594-1.244 2.057-.737.559-1.01.768-1.01 1.486v.105a.25.25 0 0 1-.25.25h-.81a.25.25 0 0 1-.25-.246l-.004-.217c-.038-.927.495-1.498 1.168-1.987.59-.444.965-.736.965-1.371 0-.825-.628-1.168-1.314-1.168-.803 0-1.253.478-1.342 1.134-.018.137-.128.25-.266.25h-.825zm2.325 6.443c-.584 0-1.009-.394-1.009-.927 0-.552.425-.94 1.01-.94.609 0 1.028.388 1.028.94 0 .533-.42.927-1.029.927z" />
                        </svg>
                        <span class="tooltip-wrappertext"><?php _e('Meta Description here', 'outpaceseo') ?></span>
                    </div>
                </label>
                <textarea class="form-control" id="outpace_meta_description" rows="3" name="outpace_desc"><?= esc_html($desc) ?></textarea>
            </div>
            <div class="outpace_for_canonical_url">
                <label for="outpace_canonical_url" class="form-label">
                    <?php _e('Canonical URL', 'outpaceseo') ?>
                    <div class="tooltip-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="12" fill="currentColor" class="bi bi-question-square-fill" viewBox="0 0 16 16">
                            <path d="M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2zm3.496 6.033a.237.237 0 0 1-.24-.247C5.35 4.091 6.737 3.5 8.005 3.5c1.396 0 2.672.73 2.672 2.24 0 1.08-.635 1.594-1.244 2.057-.737.559-1.01.768-1.01 1.486v.105a.25.25 0 0 1-.25.25h-.81a.25.25 0 0 1-.25-.246l-.004-.217c-.038-.927.495-1.498 1.168-1.987.59-.444.965-.736.965-1.371 0-.825-.628-1.168-1.314-1.168-.803 0-1.253.478-1.342 1.134-.018.137-.128.25-.266.25h-.825zm2.325 6.443c-.584 0-1.009-.394-1.009-.927 0-.552.425-.94 1.01-.94.609 0 1.028.388 1.028.94 0 .533-.42.927-1.029.927z" />
                        </svg>
                        <span class="tooltip-wrappertext"><?php _e('Insert canonical URL', 'outpaceseo') ?></span>
                    </div>
                </label>
                <input type="text" class="form-control" id="outpace_canonical_url" autocomplete="off" value="<?= esc_attr($canonical_url) ?>" name="outpace_canonical_url">
            </div>
            <div class="outpace_for_indexing">
                <label for="outpace_indexing" class="form-label">
                    <?php _e(' Allow search engines to show this page in search results?', 'outpaceseo') ?>
                    <div class="tooltip-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="12" fill="currentColor" class="bi bi-question-square-fill" viewBox="0 0 16 16">
                            <path d="M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2zm3.496 6.033a.237.237 0 0 1-.24-.247C5.35 4.091 6.737 3.5 8.005 3.5c1.396 0 2.672.73 2.672 2.24 0 1.08-.635 1.594-1.244 2.057-.737.559-1.01.768-1.01 1.486v.105a.25.25 0 0 1-.25.25h-.81a.25.25 0 0 1-.25-.246l-.004-.217c-.038-.927.495-1.498 1.168-1.987.59-.444.965-.736.965-1.371 0-.825-.628-1.168-1.314-1.168-.803 0-1.253.478-1.342 1.134-.018.137-.128.25-.266.25h-.825zm2.325 6.443c-.584 0-1.009-.394-1.009-.927 0-.552.425-.94 1.01-.94.609 0 1.028.388 1.028.94 0 .533-.42.927-1.029.927z" />
                        </svg>
                        <span class="tooltip-wrappertext">tooltip-wrapper text</span>
                    </div>
                </label>
                <select id="outpace_indexing" class="form-select" name="outpace_indexing">
                    <option value="yes" <?= $indexing == 'yes' ? ' selected="selected"' : ''; ?>>Yes</option>
                    <option value="no" <?= $indexing == 'no' ? ' selected="selected"' : ''; ?>>No</option>
                </select>
            </div>
            <div class="outpace_for_indexing">
                <label for="outpace_canonical_url" class="form-label">
                    Allow search engines to follow links on this page?
                    <div class="tooltip-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="12" fill="currentColor" class="bi bi-question-square-fill" viewBox="0 0 16 16">
                            <path d="M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2zm3.496 6.033a.237.237 0 0 1-.24-.247C5.35 4.091 6.737 3.5 8.005 3.5c1.396 0 2.672.73 2.672 2.24 0 1.08-.635 1.594-1.244 2.057-.737.559-1.01.768-1.01 1.486v.105a.25.25 0 0 1-.25.25h-.81a.25.25 0 0 1-.25-.246l-.004-.217c-.038-.927.495-1.498 1.168-1.987.59-.444.965-.736.965-1.371 0-.825-.628-1.168-1.314-1.168-.803 0-1.253.478-1.342 1.134-.018.137-.128.25-.266.25h-.825zm2.325 6.443c-.584 0-1.009-.394-1.009-.927 0-.552.425-.94 1.01-.94.609 0 1.028.388 1.028.94 0 .533-.42.927-1.029.927z" />
                        </svg>
                        <span class="tooltip-wrappertext">tooltip-wrapper text</span>
                    </div>
                </label>
                <input type="radio" id="outpace_follow" name="outpace_follow" value="yes" <?php echo (empty($follow) || $follow == 'yes') ? 'checked' : '' ?>>
                <label for="outpace_follow">Yes</label>
                <input type="radio" id="outpace_no_follow" name="outpace_follow" value="no" <?php echo ($follow == 'no') ? 'checked' : '' ?>>
                <label for="outpace_no_follow">No</label>
            </div>
        </div>

        <script>
            function insertVariable(name) {
                let currVal = document.getElementById("outpace_meta_title_slot").innerHTML
                document.getElementById("outpace_meta_title_slot").innerHTML = currVal + '<span contentEditable="false">' + name + '</span>&nbsp;'
            }

            function insertVariable1(name) {
                let currVal = document.getElementById("outpace_meta_description_slot").innerHTML
                document.getElementById("outpace_meta_description_slot").innerHTML = currVal + '<span contentEditable="false">' + name + '</span>&nbsp;'
            }
        </script>
<?php
        wp_nonce_field(
            'outpaceseo_nonce_field',
            'outpace_seo_nonce_meta_box'
        );
    }

    /**
     * Save postdata.
     *
     * @param int $post_id Post ID.
     */
    public function outpaceseo_metabox_save_postdata($post_id)
    {
        $post_type = isset($_POST['post_type']) ? $_POST['post_type'] : '';
        $outpace_seo_nonce_meta_box = isset($_POST['outpace_seo_nonce_meta_box']) ? $_POST['outpace_seo_nonce_meta_box'] : '';

        # Is the current user is authorised to do this action?
        if ((($post_type === 'page') && current_user_can('edit_page', $post_id) || current_user_can('edit_post', $post_id))) { // If it's a page, OR, if it's a post, can the user edit it?

            # Stop WP from clearing custom fields on autosave:
            if (((!defined('DOING_AUTOSAVE')) || (!DOING_AUTOSAVE)) && ((!defined('DOING_AJAX')) || (!DOING_AJAX))) {

                if ($post_type === 'outpaceseo_script') {
                    # Nonce verification:
                    if (wp_verify_nonce($_POST['outpace_seo_script_nonce'], 'outpaceseo_nonce_field')) {
                        $status = isset($_POST['outpaceseo_script_settings']['status']) ? $_POST['outpaceseo_script_settings']['status'] : false;
                        $code = isset($_POST['outpaceseo_script_settings']['code']) ? $_POST['outpaceseo_script_settings']['code'] : '';
                        $position = isset($_POST['outpaceseo_script_settings']['position']) ? $_POST['outpaceseo_script_settings']['position'] : '';
                        $pagetrack = isset($_POST['outpaceseo_script_settings']['pagetrack']) ? $_POST['outpaceseo_script_settings']['pagetrack'] : '';
                        $pagetrack_type = isset($_POST['outpaceseo_script_settings']['pagetrack_type']) ? $_POST['outpaceseo_script_settings']['pagetrack_type'] : '';
                        $page = isset($_POST['outpaceseo_script_settings']['page']) ? $_POST['outpaceseo_script_settings']['page'] : '';
                        $post = isset($_POST['outpaceseo_script_settings']['post']) ? $_POST['outpaceseo_script_settings']['post'] : '';

                        if ($status !== '') {
                            add_post_meta($post_id, '_outpaceseo_script_status', $status, true) or update_post_meta($post_id, '_outpaceseo_script_status', $status);
                        } else {
                            delete_post_meta($post_id, '_outpaceseo_script_status');
                        }
                        if ($code !== '') {
                            add_post_meta($post_id, '_outpaceseo_script_code', $code, true) or update_post_meta($post_id, '_outpaceseo_script_code', $code);
                        } else {
                            delete_post_meta($post_id, '_outpaceseo_script_code');
                        }
                        if ($position !== '') {
                            add_post_meta($post_id, '_outpaceseo_script_position', $position, true) or update_post_meta($post_id, '_outpaceseo_script_position', $position);
                        } else {
                            delete_post_meta($post_id, '_outpaceseo_script_position');
                        }
                        if ($pagetrack !== '') {
                            add_post_meta($post_id, '_outpaceseo_script_pagetrack', $pagetrack, true) or update_post_meta($post_id, '_outpaceseo_script_pagetrack', $pagetrack);
                        } else {
                            delete_post_meta($post_id, '_outpaceseo_script_pagetrack');
                        }
                        if ($pagetrack_type !== '') {
                            add_post_meta($post_id, '_outpaceseo_script_pagetrack_type', $pagetrack_type, true) or update_post_meta($post_id, '_outpaceseo_script_pagetrack_type', $pagetrack_type);
                        } else {
                            delete_post_meta($post_id, '_outpaceseo_script_pagetrack_type');
                        }
                        if ($page !== '') {
                            add_post_meta($post_id, '_outpaceseo_script_page', $page, true) or update_post_meta($post_id, '_outpaceseo_script_page', $page);
                        } else {
                            delete_post_meta($post_id, '_outpaceseo_script_page');
                        }
                        if ($post !== '') {
                            add_post_meta($post_id, '_outpaceseo_script_post', $post, true) or update_post_meta($post_id, '_outpaceseo_script_post', $post);
                        } else {
                            delete_post_meta($post_id, '_outpaceseo_script_post');
                        }
                    }
                } else {
                    # Nonce verification:
                    if (wp_verify_nonce($outpace_seo_nonce_meta_box, 'outpaceseo_nonce_field')) {

                        $title = isset($_POST['outpace_title']) ? sanitize_text_field($_POST['outpace_title']) : '';
                        $desc = isset($_POST['outpace_desc']) ? sanitize_text_field($_POST['outpace_desc']) : '';
                        $slug = isset($_POST['outpace_page_slug']) ? sanitize_text_field($_POST['outpace_page_slug']) : '';
                        $slug = str_replace(' ', '-', strtolower($slug));
                        $indexing = isset($_POST['outpace_indexing']) ? sanitize_text_field($_POST['outpace_indexing']) : '';
                        $follow = isset($_POST['outpace_follow']) ? sanitize_text_field($_POST['outpace_follow']) : '';
                        $canonical_url = isset($_POST['outpace_canonical_url']) ? sanitize_text_field($_POST['outpace_canonical_url']) : '';
                        $old_slug = get_post_field('post_name', $post_id);
                        $old_title = get_post_meta($post_id, '_outpaceseo_title', true);
                        $old_desc = get_post_meta($post_id, '_outpaceseo_meta_desc', true);
                        $old_index = get_post_meta($post_id, '_outpaceseo_indexing', true);
                        $old_follow = get_post_meta($post_id, '_outpaceseo_follow', true);
                        $old_url = get_post_meta($post_id, '_outpaceseo_canonical_url', true);

                        if ($title != $old_title) {
                            if ($title !== '') {
                                add_post_meta($post_id, '_outpaceseo_title', $title, true) or update_post_meta($post_id, '_outpaceseo_title', $title);
                            } else {
                                delete_post_meta($post_id, '_outpaceseo_title');
                            }
                        }
                        if ($desc != $old_desc) {
                            if ($desc !== '') {
                                add_post_meta($post_id, '_outpaceseo_meta_desc', $desc, true) or update_post_meta($post_id, '_outpaceseo_meta_desc', $desc);
                            } else {
                                delete_post_meta($post_id, '_outpaceseo_meta_desc');
                            }
                        }
                        if ($indexing != $old_index) {
                            if ($indexing !== '') {
                                add_post_meta($post_id, '_outpaceseo_indexing', $indexing, true) or update_post_meta($post_id, '_outpaceseo_indexing', $indexing);
                            } else {
                                delete_post_meta($post_id, '_outpaceseo_indexing');
                            }
                        }
                        if ($follow != $old_follow) {
                            if ($follow !== '') {
                                add_post_meta($post_id, '_outpaceseo_follow', $follow, true) or update_post_meta($post_id, '_outpaceseo_follow', $follow);
                            } else {
                                delete_post_meta($post_id, '_outpaceseo_follow');
                            }
                        }
                        if ($canonical_url != $old_url) {
                            if ($canonical_url !== '') {
                                add_post_meta($post_id, '_outpaceseo_canonical_url', $canonical_url, true) or update_post_meta($post_id, '_outpaceseo_canonical_url', $canonical_url);
                            } else {
                                delete_post_meta($post_id, '_outpaceseo_canonical_url');
                            }
                        }
                        if ($old_slug != $slug) {
                            if ($slug !== '') {
                                add_post_meta($post_id, '_outpaceseo_page_slug', $slug, true) or update_post_meta($post_id, '_outpaceseo_page_slug', $slug);
                                $data = array(
                                    'ID' => $post_id,
                                    'post_name' => $slug,
                                );
                                wp_update_post($data, true);
                            } else {
                                delete_post_meta($post_id, '_outpaceseo_page_slug');
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Outpaceseo registering the settings.
     */
    public function outpaceseo_register_settings()
    {
        register_setting(
            'outpaceseo_settings_group',
            'outpaceseo_settings',
            'outpace_settings_validater_and_sanitizer',
        );

        register_setting(
            'outpaceseo_settings_search_group',
            'outpaceseo_search_settings',
            'outpace_search_settings_validater_and_sanitizer',
        );

        register_setting(
            'outpaceseo_settings_sitemap_group',
            'outpaceseo_sitemap_settings',
            'outpace_sitemap_settings_validater_and_sanitizer',
        );

        add_settings_section(
            'outpaceseo_basic_settings_section_id',
            __('Basic Settings', 'outpaceseo'),
            '__return_false',
            'outpaceseo_basic_settings_section'
        );

        add_settings_section(
            'outpaceseo_search_settings_section_id',
            __('Search', 'outpaceseo'),
            '__return_false',
            'outpaceseo_search_settings_section'
        );


        add_settings_section(
            'outpaceseo_sitemap_settings_section_id',
            __('Sitemap', 'outpaceseo'),
            '__return_false',
            'outpaceseo_sitemap_settings_section'
        );

        add_settings_field(
            'outpaceseo_basic_settings',
            __('General Settings<p class="outpaceseo-description">These are the basic features of this plugin.</p>', 'outpaceseo'),        // Title
            'outpaceseo_general_settings_callback',
            'outpaceseo_basic_settings_section',
            'outpaceseo_basic_settings_section_id'
        );

        add_settings_field(
            'outpaceseo_search_settings',
            'Test',
            'outpaceseo_search_settings_callback',
            'outpaceseo_search_settings_section',
            'outpaceseo_search_settings_section_id'
        );

        add_settings_field(
            'outpaceseo_sitemap_settings',
            'Test',
            'outpaceseo_sitemap_settings_callback',
            'outpaceseo_sitemap_settings_section',
            'outpaceseo_sitemap_settings_section_id'
        );
    }

    /**
     * Returns a base64 URL for the SVG for use in the menu.
     *
     * @param  string $fill   SVG Fill color code. Default: '#82878c'.
     * @param  bool   $base64 Whether or not to return base64-encoded SVG.
     * @return string
     */
    public static function get_icon_svg($fill = '#82878c', $base64 = true)
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 604.9 671.9">
		<path fill="' . $fill . '" d="M602.4,211.9v247.9c0,30.5-16.3,58.6-42.6,73.8l-214.7,124c-26.4,15.2-58.9,15.2-85.3,0L163.7,602v-91.6
			l106.5,61.5c20,11.6,44.7,11.6,64.7,0l155.8-90c20-11.6,32.4-32.9,32.4-56.1V116.9l36.7,21.2C586.2,153.3,602.4,181.5,602.4,211.9z"
			/>
		<path fill="' . $fill . '" d="M345.1,14.2C318.7-1,286.2-1,259.8,14.2L45.2,138.1c-26.4,15.2-42.6,43.4-42.6,73.8v247.9
			c0,30.5,16.3,58.6,42.6,73.8l36.7,21.2V255.1l0,0v-9.2c0-23.1,12.3-44.5,32.4-56l12.4-7.1L270.2,100c20-11.6,44.7-11.6,64.7,0
			l106.4,61.3V69.7L345.1,14.2z"/>
		<path fill="' . $fill . '" d="M421,243.9L389.9,226l-67-38.7c-12.6-7.3-28.1-7.3-40.7,0l-90.3,52.1l-7.8,4.5c-12.6,7.3-20.4,20.7-20.4,35.3
			v113.3c0,14.6,7.8,28,20.4,35.3l31.1,17.9l67,38.7c12.6,7.3,28.1,7.3,40.7,0l98.1-56.6c12.6-7.3,20.4-20.7,20.4-35.3V279.2
			C441.3,264.7,433.6,251.2,421,243.9z M262.4,412.5c0,8.4-6.6,15.4-15,16c-0.4,0-0.8,0.1-1.1,0.1c-0.4,0-0.8,0-1.1-0.1
			c-8.4-0.6-14.9-7.6-14.9-16v-88.3c0-8.4,6.6-15.4,14.9-16c0.4,0,0.8,0,1.1,0s0.8,0,1.2,0c8.4,0.6,14.9,7.6,14.9,16V412.5z
				M318.6,380c0,8.4-6.5,15.4-14.9,16c-0.8,0.1-1.5,0.1-2.3,0c-8.4-0.6-14.9-7.6-14.9-16v-88.3c0-8.4,6.5-15.4,14.9-16
			c0.8-0.1,1.5-0.1,2.3,0c8.4,0.6,14.9,7.6,14.9,16V380z M374.8,347.6c0,8.4-6.5,15.4-14.9,16c-0.4,0-0.8,0.1-1.1,0.1
			c-0.4,0-0.8,0-1.1-0.1c-8.4-0.6-15-7.6-15-16v-88.3c0-8.4,6.6-15.4,15-16c0.4,0,0.8-0.1,1.1-0.1c0.4,0,0.8,0,1.1,0.1
			c8.4,0.6,14.9,7.6,14.9,16V347.6z"/>
   	</svg>';

        if ($base64) {
            return 'data:image/svg+xml;base64,' . base64_encode($svg);
        }

        return $svg;
    }

    /**
     * Adding the Menu.
     */
    public function admin_menu()
    {
        add_menu_page(
            __('Outpace SEO', 'outpaceseo'),
            'Outpace SEO',
            'manage_options',
            'outpaceseo',
            array($this, 'outpace_seo_page'),
            self::get_icon_svg(),
            6
        );
        add_submenu_page(
            'outpaceseo',
            'outpaceseo_script',
            'Outpace Script',
            'manage_options',
            'edit.php?post_type=outpaceseo_script'
        );
        add_submenu_page(
            'outpaceseo',
            'outpaceseo_schema',
            'Outpace Schema',
            'manage_options',
            'edit.php?post_type=outpaceseo_schema'
        );
    }

    /**
     * Display a outpaceseo menu page
     */
    public function outpace_seo_page()
    {
        include_once dirname(__FILE__) . '/views/html-admin-options.php';
    }

    /**
     * Image Attr Page.
     */
    public static function img_attr_page()
    {
        include_once dirname(__FILE__) . '/views/html-admin-img-attr.php';
    }

    /**
     * Search Page.
     */
    public static function search_page()
    {
        include_once dirname(__FILE__) . '/views/html-admin-search.php';
    }

    /**
     * Sitemap Page.
     */
    public static function sitemap_page()
    {
        include_once dirname(__FILE__) . '/views/html-admin-sitemap.php';
    }

    /**
     * Bulk uploader options.
     */
    public static function bulk_uploader()
    {
        include_once dirname(__FILE__) . '/views/html-admin-bulk-uploader.php';
    }

    /**
     * Basic options.
     */
    public static function basic_options()
    {
        include_once dirname(__FILE__) . '/views/html-admin-basic-options.php';
    }
}
