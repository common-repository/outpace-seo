<?php

/**
 * Outpaceseo Schemas Template.
 *
 */

if (!class_exists('Outpaceseo_Schema_Template')) {

    /**
     * Outpaceseo Schemas Initialization
     */
    class Outpaceseo_Schema_Template
    {
        /**
         * Member Variable
         *
         * @var instance
         */
        private static $instance;

        /**
         *  Initiator
         */
        public static function get_instance()
        {
            if (!isset(self::$instance)) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * Get post data.
         *
         * @param array  $post post object array.
         * @param string $schema_key schema key.
         * @param bool   $single is single.
         * @param bool   $is_available global post compatibility.
         */
        public static function get_post_data($post, $schema_key, $single = true, $is_available = true)
        {
            $value = '';
            switch ($schema_key) {
                case 'blogname':
                    $value        = get_bloginfo('name');
                    $is_available = true;
                    break;

                case 'blogdescription':
                    $value        = get_bloginfo('description');
                    $is_available = true;
                    break;

                case 'site_url':
                    $value        = get_bloginfo('url');
                    $is_available = true;
                    break;

                case 'site_logo':

                    if (function_exists('the_custom_logo') && has_custom_logo()) {
                        $value = get_theme_mod('custom_logo');
                    }
                    $is_available = true;
                    break;

                case 'featured_img':
                case 'featured_image':
                    $op_default  = '';
                    $feature_img = get_post_thumbnail_id($post['ID']);
                    if (!empty($feature_img)) {
                        $value = $feature_img;
                    } elseif (!empty($op_default['url'])) {
                        $value = $op_default['url'];
                    } else {
                        $value = '';
                    }
                    $is_available = true;
                    break;

                case 'post_title':
                    $value        = $post[$schema_key];
                    $is_available = true;
                    break;

                case 'post_excerpt':
                case 'post_content':
                    $value        = do_shortcode($post[$schema_key]);
                    $is_available = true;
                    break;

                case 'post_date':
                    $value        = get_the_date('Y-m-d\TH:i:s', $post['ID']);
                    $is_available = true;
                    break;

                case 'post_modified':
                    $value        = get_the_modified_date('Y-m-d\TH:i:s', $post['ID']);
                    $is_available = true;
                    break;

                case 'post_permalink':
                    $value        = get_permalink($post['ID']);
                    $is_available = true;
                    break;

                case 'author_name':
                    $author_data  = get_userdata($post['post_author']);
                    $value        = $author_data->display_name;
                    $is_available = true;
                    break;

                case 'author_first_name':
                    $author_data  = get_userdata($post['post_author']);
                    $value        = isset($author_data->first_name) ? $author_data->first_name : $author_data->display_name;
                    $is_available = true;
                    break;

                case 'author_last_name':
                    $author_data  = get_userdata($post['post_author']);
                    $value        = isset($author_data->last_name) ? $author_data->last_name : $author_data->display_name;
                    $is_available = true;
                    break;
                case 'author_url':
                    $author_data  = get_userdata($post['post_author']);
                    $author_name  = $author_data->user_nicename;
                    $author_url   = get_author_posts_url($post['ID']);
                    $value        = $author_url . '' . $author_name;
                    $is_available = true;
                    break;

                case 'author_image':
                    $value        = array(
                        0 => get_avatar_url($post['post_author']),
                        1 => 96,
                        2 => 96,
                    );
                    $is_available = true;
                    break;

                default:
                    $value = get_post_meta($post['ID'], $schema_key, $single);
                    if (is_array($value)) {
                        $value = '';
                    }
                    break;
            }

            if (!$is_available && empty($value)) {

                if ('none' === $schema_key) {
                    return '';
                }
            }
            return $value;
        }

        /**
         * Get Meta value by key
         *
         * @param  int       $schema_id Schema Id.
         * @param  int|array $post Post Array.
         * @param  string    $meta_data Schema Meta data.
         * @param  string    $key Post meta key.
         * @param  string    $type field type.
         * @param  string    $create_field create custom field name.
         * @param  boolean   $single get_post_meta in array or single.
         */
        public static function get_meta_value($schema_id = 0, $post = 0, $meta_data = array(), $key = '', $type = 'text', $create_field = '', $single = true)
        {
            $schema_key         = isset($meta_data[$key]) ? $meta_data[$key] : '';

            if (empty($post) || empty($schema_key) || 'none' === $schema_key) {
                $value = '';
            } else {
                switch ($schema_key) {

                    case 'custom-text':
                    case 'fixed-text':
                        $value = isset($meta_data[$key . '-' . $schema_key]) ? $meta_data[$key . '-' . $schema_key] : '';
                        break;

                    case 'specific-field':
                        $meta_key = isset($meta_data[$key . '-' . $schema_key]) ? $meta_data[$key . '-' . $schema_key] : '';
                        $value    = !empty($meta_key) ? get_post_meta($post['ID'], $meta_key, $single) : '';
                        break;

                    case 'create-field':
                        $value = get_post_meta($post['ID'], $create_field, $single);
                        break;
                    case 'site_logo':
                        $logo_id          = get_post_thumbnail_id($post['ID']);
                        if (function_exists('the_custom_logo') && has_custom_logo()) {
                            $logo_id = get_theme_mod('custom_logo');
                        }
                        if (!empty($logo_id)) {
                            $value = self::get_image_object($logo_id, $key);
                        } else {
                            $value = '';
                        }

                        break;

                    default:
                        $value = self::get_post_data($post, $schema_key, $single);
                        break;
                }

                if ('image' === $type && !empty($value)) {
                    $value = self::get_image_object($value, $key);
                } elseif ('date' === $type && !empty($value)) {
                    $value = gmdate('Y-m-d\TH:i:s', strtotime($value));
                }
            }

            return $value;
        }

        /**
         * Logo Image Sizes
         *
         * @param array $sizes Sizes.
         *
         * @return array
         */
        public static function logo_image_sizes($sizes)
        {

            if (is_array($sizes)) {

                $sizes['aiosrs-logo-size'] = array(
                    'width'  => 600,
                    'height' => 60,
                    'crop'   => false,
                );
            }

            return $sizes;
        }

        /**
         * Generate logo image by its width.
         *
         * @param int $image_id Image id.
         */
        public static function generate_logo_by_width($image_id)
        {
            if ($image_id) {

                $image = get_post($image_id);

                if ($image) {
                    $fullsizepath = get_attached_file($image->ID);

                    if (false !== $fullsizepath || file_exists($fullsizepath)) {

                        require_once ABSPATH . 'wp-admin/includes/image.php';
                        $metadata = wp_generate_attachment_metadata($image->ID, $fullsizepath);

                        if (!is_wp_error($metadata) && !empty($metadata)) {
                            wp_update_attachment_metadata($image->ID, $metadata);
                        }
                    }
                }
            }
        }

        /**
         * Get Field type.
         *
         * @param  string $type   schema type.
         * @param  string $key    schema field key.
         * @param  string $parent schema parent field key.
         */
        public static function get_field_type($type, $key, $parent = '')
        {
            $schema_fields    = Outpaceseo_Schema::$schema_meta_fields;
            $review_image_key = substr($key, -5);
            if (empty($parent) && isset($schema_fields['outpaceseo-' . $type]['subkeys'][$key]['type'])) {
                return $schema_fields['outpaceseo-' . $type]['subkeys'][$key]['type'];
            } elseif (isset($schema_fields['outpaceseo-' . $type]['subkeys'][$parent]['fields'][$key]['type'])) {
                return $schema_fields['outpaceseo-' . $type]['subkeys'][$parent]['fields'][$key]['type'];
            } elseif ('review' === $type && empty($parent) && isset($review_image_key) && 'image' === $review_image_key) {
                return 'image';
            }
            return 'text';
        }

        /**
         * Strip shortcode from Content.
         *
         * @param  string $content   schema Description.
         * @param  bool   $do_shortcode   Condition.
         */
        public static function strip_markup($content, $do_shortcode = false)
        {
            if (self::maybe_do_shortcode($do_shortcode)) {
                $content = do_shortcode($content);
            }

            return wp_strip_all_tags($content);
        }

        /**
         * Check page builders.
         *
         * @param  bool $do_shortcode  Condition.
         * @return bool
         */
        public static function maybe_do_shortcode($do_shortcode)
        {
            $status = false;

            if (class_exists('FLBuilderModel') || class_exists('FusionBuilder') || class_exists('Vc_Manager') || class_exists('ET_Builder_Module') || true === $do_shortcode) {
                $status = true;
            }

            return apply_filters('outpaceseo_maybe_do_shortcode', $status);
        }


        /**
         * Get Schema Field.
         *
         * @param  string $type  schema type.
         * @param  string $field schema field.
         */
        public static function get_schema_field($type, $field)
        {
            $schema_fields = Outpaceseo_Schema::$schema_meta_fields;
            if (isset($schema_fields['outpaceseo-' . $type][$field])) {
                return $schema_fields['outpaceseo-' . $type][$field];
            }
            return '';
        }

        /**
         * Get Image field Schema markup.
         *
         * @param  array  $data_image Image data.
         * @param  string $type Image type ImageObject|URL|any.
         * @return array
         */
        public static function get_image_schema($data_image, $type = 'any')
        {

            $result = array();
            switch ($type) {
                case 'URL':
                    if (is_array($data_image)) {
                        if (isset($data_image[0]) && !empty($data_image[0])) {
                            $result = esc_url($data_image[0]);
                        }
                    } else {
                        $images = explode(',', $data_image);
                        if (filter_var($images[0], FILTER_VALIDATE_URL)) {
                            $result = esc_url($images[0]);
                        }
                    }
                    break;

                case 'ImageObject':
                    if (is_array($data_image)) {

                        $result['@type'] = 'ImageObject';
                        if (isset($data_image[0]) && !empty($data_image[0])) {
                            $result['url'] = esc_url($data_image[0]);
                        }
                        if (isset($data_image[1]) && !empty($data_image[1])) {
                            $result['width'] = (int) esc_html($data_image[1]);
                        }
                        if (isset($data_image[2]) && !empty($data_image[2])) {
                            $result['height'] = (int) esc_html($data_image[2]);
                        }
                    } else {
                        $images       = explode(',', $data_image);
                        $image_object = getimagesize($images[0]);
                        if ($image_object) {

                            $result['@type']               = 'ImageObject';
                            $result['url']                 = esc_url($images[0]);
                            list($width, $height, $type) = $image_object;
                            $result['width']               = (int) esc_html($width);
                            $result['height']              = (int) esc_html($height);
                        }
                    }
                    break;

                case 'ImageObject2':
                    if (is_array($data_image)) {

                        $result['@type'] = 'ImageObject';
                        if (isset($data_image[0]) && !empty($data_image[0])) {
                            $result['url'] = esc_url($data_image[0]);
                        }
                    } else {
                        $images       = explode(',', $data_image);
                        $image_object = getimagesize($images[0]);
                        if ($image_object) {

                            $result['@type'] = 'ImageObject';
                            $result['url']   = esc_url($images[0]);
                        }
                    }
                    break;

                default:
                    if (is_array($data_image)) {

                        $result['@type'] = 'ImageObject';

                        if (isset($data_image[0]) && !empty($data_image[0])) {
                            $result['url'] = esc_url($data_image[0]);
                        }
                        if (isset($data_image[1]) && !empty($data_image[1])) {
                            $result['width'] = (int) esc_html($data_image[1]);
                        }
                        if (isset($data_image[2]) && !empty($data_image[2])) {
                            $result['height'] = (int) esc_html($data_image[2]);
                        }
                    } else {
                        $image_urls = array();
                        $images     = explode(',', $data_image);
                        foreach ($images as $image) {
                            if (filter_var($image, FILTER_VALIDATE_URL)) {
                                $image_urls[] = esc_url($image);
                            }
                        }
                        $result = $image_urls;
                    }
                    break;
            }

            return $result;
        }


        /**
         * Get Schema.
         *
         * @param  int    $post_id     Post Id.
         * @param  int    $schema_id   Schema Id.
         * @param  string $type        Schema type.
         * @param  array  $schema_data Schema Meta Data.
         */
        public static function get_schema($post_id, $schema_id, $type, $schema_data)
        {
            $local_meta = get_post_meta($post_id);
            $data       = array();
            $post       = get_post($post_id, ARRAY_A);

            foreach ($schema_data as $key => $value) {
                $field_type = self::get_field_type($type, $key);

                if ('repeater' === $field_type && is_array($value)) {
                    $values                = array();
                    $repeater_field_values = isset($local_meta[$type . '-' . $schema_id . '-' . $key][0]) ? $local_meta[$type . '-' . $schema_id . '-' . $key][0] : '';

                    $repeater_field_values = maybe_unserialize($repeater_field_values);
                    if (!is_array($repeater_field_values) || empty($repeater_field_values)) {

                        $repeater_field_values = $value;
                    }

                    foreach ($repeater_field_values as $index => $repeater_values) {
                        foreach ($repeater_values as $repeater_key => $repeater_value) {
                            $field_type = self::get_field_type($type, $repeater_key, $key);

                            // Local support.
                            $local_data = isset($local_meta[$type . '-' . $schema_id . '-' . $key][0]) ? $local_meta[$type . '-' . $schema_id . '-' . $key][0] : '';

                            $local_data = maybe_unserialize($local_data);
                            if (isset($local_data[$index][$repeater_key]) && isset($local_data[$index][$repeater_key . '-fieldtype'])) {
                                $this_fieldtype = $local_data[$index][$repeater_key . '-fieldtype'];
                                $this_fieldval  = $local_data[$index][$repeater_key];
                                $this_fieldval  = self::prepare_global_data($this_fieldtype, $field_type, $this_fieldval, $post);
                                if (!empty($this_fieldval)) {
                                    $values[$index][$repeater_key] = $this_fieldval;
                                    continue;
                                }
                            }

                            $create_field                      = $type . '-' . $schema_id . '-' . $key . '-' . $index . '-' . $repeater_key;
                            $values[$index][$repeater_key] = self::get_meta_value($schema_id, $post, $repeater_values, $repeater_key, $field_type, $create_field);
                        }
                    }
                    $data[$key] = $values;
                } elseif ('repeater-target' === $field_type && is_array($value)) {

                    $data[$key] = get_post_meta($post_id, $type . '-' . $schema_id . '-' . $key, true);
                } else {
                    $create_field = $type . '-' . $schema_id . '-' . $key;
                    if ('accept-user-rating' === $value) {
                        $data[$key] = self::get_meta_value($schema_id, $post, $schema_data, $key, $field_type, $create_field);
                        continue;
                    }

                    if (isset($local_meta[$create_field][0]) && isset($local_meta[$create_field . '-fieldtype'][0])) {
                        $this_fieldtype = $local_meta[$create_field . '-fieldtype'][0];
                        $this_fieldval  = $local_meta[$create_field][0];

                        $this_fieldval = self::prepare_global_data($this_fieldtype, $field_type, $this_fieldval, $post);

                        if (('datetime-local' === $field_type || 'date' === $field_type) && !empty($this_fieldval)) {
                            $this_fieldval = gmdate(DATE_ISO8601, strtotime($this_fieldval));
                        }

                        if (!empty($this_fieldval)) {
                            $data[$key] = $this_fieldval;
                            continue;
                        }
                    }

                    $data[$key] = self::get_meta_value($schema_id, $post, $schema_data, $key, $field_type, $create_field);
                }
            }

            $path  = self::get_schema_field($type, 'path');
            $path .= 'class-outpace-seo-schema-' . $type . '.php';
            if (file_exists($path)) {
                require_once $path;

                $class_name = 'Outpaceseo_Schema_' . str_replace('-', '_', ucfirst($type));
                if (class_exists($class_name)) {
                    $schema_instance = new $class_name();
                    return $schema_instance->render($data, $post);
                }
            }
            return array();
        }

        /**
         * Gets parent pages of any post type.
         *
         * @param int $post_id ID of the post whose parents we want.
         */
        public static function get_parents($post_id = '')
        {
            $parents = array();
            if (0 === $post_id) {
                return $parents;
            }
            while ($post_id) {
                $page      = get_page($post_id);
                $parents[] = array(
                    'url'   => get_permalink($post_id),
                    'title' => get_the_title($post_id),
                );

                $post_id = $page->post_parent;
            }
            if ($parents) {
                $parents = array_reverse($parents);
            }

            return $parents;
        }

        /**
         * Searches for term parents of hierarchical taxonomies.
         *
         * @param int           $parent_id The ID of the first parent.
         * @param object|string $taxonomy The taxonomy of the term whose parents we want.
         */
        public static function get_term_parents($parent_id = '', $taxonomy = '')
        {
            $parents = array();
            if (empty($parent_id) || empty($taxonomy)) {
                return $parents;
            }
            while ($parent_id) {
                $parent    = get_term($parent_id, $taxonomy);
                $parents[] = array(
                    'url'   => get_term_link($parent, $taxonomy),
                    'title' => $parent->name,
                );
                $parent_id = $parent->parent;
            }
            if ($parents) {
                $parents = array_reverse($parents);
            }

            return $parents;
        }

        /**
         * Get Image as object.
         *
         * @param string $value image id.
         * @param string $key image type.
         */
        public static function get_image_object($value, $key)
        {
            if (is_numeric($value)) {
                $image_id = $value;
                if ('site-logo' === $key && apply_filters('outpaceseo_exclude_logo_optimize', true)) {
                    add_filter('intermediate_image_sizes_advanced', 'Outpaceseo_Schema_Template::logo_image_sizes', 10, 2);
                    $value = wp_get_attachment_image_src($image_id, 'aiosrs-logo-size');
                    if (isset($value[3]) && 1 !== $value[3]) {
                        self::generate_logo_by_width($image_id);
                        $value = wp_get_attachment_image_src($image_id, 'aiosrs-logo-size');
                    }
                    remove_filter('intermediate_image_sizes_advanced', 'Outpaceseo_Schema_Template::logo_image_sizes', 10, 2);
                } else {
                    $value = wp_get_attachment_image_src($image_id, 'full');
                }
            }
            return $value;
        }

        /**
         * Prepare gobal data.
         *
         * @param string $this_fieldtype this field type.
         * @param string $field_type field type.
         * @param string $this_fieldval this field val.
         * @param array  $post post array.
         */
        public static function prepare_global_data($this_fieldtype, $field_type, $this_fieldval, $post)
        {
            if ('custom-field' === $this_fieldtype) {
                if ('image' === $field_type) {
                    $this_fieldval = self::get_image_object($this_fieldval, $field_type);
                }
            } elseif ('global-field' === $this_fieldtype) {
                $this_fieldval = self::get_post_data($post, $this_fieldval, true, false);
                if ('image' === $field_type) {
                    $this_fieldval = self::get_image_object($this_fieldval, $field_type);
                }
            } elseif ('specific-field' === $this_fieldtype) {
                $this_fieldval = get_post_meta($post['ID'], $this_fieldval, true);
            }
            return $this_fieldval;
        }
    }
}
Outpaceseo_Schema_Template::get_instance();
