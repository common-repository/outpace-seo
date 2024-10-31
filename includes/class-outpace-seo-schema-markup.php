<?php

/**
 * Outpaceseo Schema Markup.
 *
 */

if (!class_exists('Outpaceseo_Schema_Markup')) {

    /**
     * Outpaceseo Schemas Initialization
     *
     */
    class Outpaceseo_Schema_Markup
    {
        /**
         * Member Variable
         *
         * @var instance
         */
        private static $instance;

        /**
         * Member Variable
         *
         * @var instance
         */
        private static $schema_post_result = array();

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
         *  Constructor
         */
        public function __construct()
        {

            $this->init();
        }

        /**
         * Initalize
         *
         * @return void
         */
        public function init()
        {
            $settings['schema-location'] = 'head';
            add_filter('body_class', array($this, 'wp_schema_body_class'));

            if (isset($settings['schema-location'])) {

                switch ($settings['schema-location']) {
                    case 'head':
                        add_action('wp_head', array($this, 'schema_markup'));
                        break;
                    case 'footer':
                        add_action('wp_footer', array($this, 'schema_markup'));
                        break;
                    default:
                        break;
                }
            }
        }

        /**
         * Adding class to body
         *
         * @param  array $classes body classes.
         * @return array
         */
        public function wp_schema_body_class($classes)
        {

            $classes[] = 'outpaceseo-schema-' . OSEO_VERSION;

            return $classes;
        }

        /**
         * Function to get the client IP address
         *
         * @return string
         */
        public function get_client_ip()
        {

            if (getenv('HTTP_CLIENT_IP')) {
                $ipaddress = getenv('HTTP_CLIENT_IP');
            } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
                $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
            } elseif (getenv('HTTP_X_FORWARDED')) {
                $ipaddress = getenv('HTTP_X_FORWARDED');
            } elseif (getenv('HTTP_FORWARDED_FOR')) {
                $ipaddress = getenv('HTTP_FORWARDED_FOR');
            } elseif (getenv('HTTP_FORWARDED')) {
                $ipaddress = getenv('HTTP_FORWARDED');
            } elseif (getenv('REMOTE_ADDR')) {
                $ipaddress = getenv('REMOTE_ADDR');
            } else {
                $ipaddress = 'UNKNOWN';
            }

            return $ipaddress;
        }

        /**
         * Get current page schemas.
         *
         * @return array
         */
        public static function get_schema_posts()
        {

            if (is_singular()) {
                if (empty(self::$schema_post_result)) {

                    $option = array(
                        'location'  => 'outpaceseo-schema-location',
                        'exclusion' => 'outpaceseo-schema-exclusion',
                    );

                    self::$schema_post_result = Outpaceseo_Target_Rule_Fields::get_instance()->get_posts_by_conditions('outpaceseo_schema', $option);
                }
            }
            return self::$schema_post_result;
        }

        /**
         * Schema Markup in JSON-LD form.
         *
         * @return void
         */
        public function schema_markup()
        {
            $current_post_id = get_the_id();

            $result = self::get_schema_posts();

            if (is_array($result) && !empty($result)) {
                $json_ld_markup = '';
                foreach ($result as $post_id => $post_data) {

                    $schema_type = get_post_meta($post_id, 'outpaceseo-schema-type', true);
                    $schema_meta = get_post_meta($post_id, 'outpaceseo-' . $schema_type, true);

                    $schema_enabled            = false;
                    $schema_enabled_meta_key   = $schema_type . '-' . $post_id . '-enabled-schema';
                    $schema_enabled_meta_value = get_post_meta($current_post_id, $schema_enabled_meta_key, true);
                    $schema_enabled_meta_value = !empty($schema_enabled_meta_value) ? $schema_enabled_meta_value : 'disabled';
                    if (empty($current_post_id) || empty($schema_type) || empty($schema_meta) || ($schema_enabled && 'disabled' === $schema_enabled_meta_value)) {
                        continue;
                    }
                    do_action("outpaceseo_before_schema_markup_{$schema_type}", $current_post_id, $schema_type);

                    $enabled         = apply_filters('outpaceseo_schema_enabled', true, $current_post_id, $schema_type);
                    $enabled_comment = apply_filters('outpaceseo_comment_before_markup_enabled', true);
                    if (true === $enabled_comment) {
                        $json_ld_markup .= '<!-- Schema optimized by Outpaceseo -->';
                    }
                    if (true === $enabled) {
                        if ('custom-markup' === $schema_type) {
                            $custom_markup = Outpaceseo_Schema_Template::get_schema($current_post_id, $post_id, $schema_type, $schema_meta);
                            if (isset($custom_markup[$schema_type]) && !empty($custom_markup[$schema_type])) {
                                $custom_markup[$schema_type] = trim($custom_markup[$schema_type]);
                                $first_schema_character        = substr($custom_markup[$schema_type], 0, 1);
                                $last_schema_character         = substr($custom_markup[$schema_type], -1, 1);
                                if ('{' === $first_schema_character && '}' === $last_schema_character) {
                                    $json_ld_markup .= '<script type="application/ld+json">';
                                    $json_ld_markup .= $custom_markup[$schema_type];
                                    $json_ld_markup .= '</script>';
                                } else {
                                    $json_ld_markup .= $custom_markup[$schema_type];
                                }
                            }
                        } else {
                            // @codingStandardsIgnoreStart
                            $json_ld_markup .= '<script type="application/ld+json">';
                            if (version_compare(PHP_VERSION, '5.3', '>')) {
                                $json_ld_markup .= wp_json_encode(Outpaceseo_Schema_Template::get_schema($current_post_id, $post_id, $schema_type, $schema_meta), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            } else {
                                $json_ld_markup .= wp_json_encode(Outpaceseo_Schema_Template::get_schema($current_post_id, $post_id, $schema_type, $schema_meta));
                            }
                            // @codingStandardsIgnoreEnd
                            $json_ld_markup .= '</script>';
                        }
                        if (true === $enabled_comment) {
                            $json_ld_markup .= '<!-- / Schema optimized by Outpaceseo -->';
                        }
                    }

                    do_action("outpaceseo_after_schema_markup_{$schema_type}", $current_post_id, $schema_type);
                }
                echo $json_ld_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                $json_ld_markup = addcslashes($json_ld_markup, '"\\/');
            }
        }
    }
}

Outpaceseo_Schema_Markup::get_instance();
