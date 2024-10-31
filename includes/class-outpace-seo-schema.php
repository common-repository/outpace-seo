<?php

/**
 * Outpaceseo Schema.
 */
/**
 * Outpaceseo Schemas Initialization
 *
 */
class OutpaceSEO_Schema
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
     * @var $wp_schema_actions
     */
    public static $wp_schema_action = 'outpaceseo_schema';

    /**
     * Member Variable
     *
     * @var $meta_option
     */
    public static $meta_option = array();

    /**
     * Member Variable
     *
     * @var $post_metadata
     */
    public static $post_metadata = array();

    /**
     * Member Variable
     *
     * @var $schema_meta_fields
     */
    public static $schema_meta_fields = array();

    /**
     * Member Variable
     *
     * @var $schema_item_types
     */
    public static $schema_item_types = array();

    /**
     * Member Variable
     *
     * @var $schema_meta_keys
     */
    public static $schema_meta_keys = array();

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

        add_action('init', array($this, 'init_schema_fields'));
        add_action('load-post.php', array($this, 'init_metabox'));
        add_action('load-post-new.php', array($this, 'init_metabox'));
        add_action('wp_ajax_op_get_specific_meta_fields', array($this, 'op_get_specific_meta_fields'));
        add_action('wp_ajax_fetch_item_type_html', array($this, 'get_review_item_type_html'));
        add_filter('post_updated_messages', array($this, 'custom_post_type_post_update_messages'));
        add_filter('outpaceseo_post_metadata', array($this, 'acf_compatibility'));

        if (is_admin()) {
            add_action('manage_outpaceseo_schema_posts_custom_column', array($this, 'column_content'), 10, 2);
            add_filter('manage_outpaceseo_schema_posts_columns', array($this, 'column_headings'));
        }

        add_filter('outpaceseo_mapping_option_string_custom-text', array($this, 'custom_text_string'), 10, 2);
        add_action('admin_enqueue_scripts', array($this, 'wpsp_scripts'));
    }

    /**
     *  Add script for Datetimepicker.
     */
    public function wpsp_scripts()
    {

        $schema_post_type_name = get_current_screen()->post_type;
        $enqueue_admin_script  = Outpaceseo_Target_Rule_Fields::outpaceseo_enqueue_admin_script();

        if (true === $enqueue_admin_script || 'outpaceseo_schema' === $schema_post_type_name) {
            wp_enqueue_script(
                'op_datetimepicker_script',
                outpaceseo()->plugin_url() . '/assets/js/timepicker.min.js',
                array('jquery-ui-datepicker', 'jquery-ui-slider'),
                OSEO_VERSION,
                true
            );

            wp_enqueue_style('op_jquery_ui_css', 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css', false, OSEO_VERSION, false);
        }
    }

    /**
     *  Fetch the HTML item type for Review.
     */
    public function get_review_item_type_html()
    {

        if (!current_user_can('manage_options')) {
            return false;
        }

        check_ajax_referer('schema_nonce', 'nonce');

        $item_type        = filter_input(INPUT_POST, 'itemType', FILTER_SANITIZE_STRING);
        $post_id          = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
        $item_type_fields = self::$schema_item_types[$item_type]['subkeys'];

        foreach ($item_type_fields as  $key => $item_type_field) {

            $review_meta_data = get_post_meta($post_id, 'outpaceseo-review', true);
            $item_key         = $item_type . '-' . $key;
            $schemas_meta     = array('outpaceseo-review' => $review_meta_data);

            self::get_meta_markup(
                array(
                    'name'            => 'outpaceseo-review',
                    'subkey'          => $item_key,
                    'subkey_data'     => $item_type_field,
                    'item_type_class' => 'op-review-item-type-field',
                ),
                $schemas_meta
            );
        }
    }


    /**
     * Filter String for fixed text.
     *
     * @param  string $label Option Label.
     * @param  string $type  Option field type.
     * @return string
     */
    public function custom_text_string($label, $type)
    {

        switch ($type) {
            case 'dropdown':
                $label = __('Fixed Option', 'outpaceseo');
                break;

            case 'multi-select':
                $label = __('Fixed Options', 'outpaceseo');
                break;

            case 'number':
                $label = __('Fixed Number', 'outpaceseo');
                break;

            case 'date':
                $label = __('Fixed Date', 'outpaceseo');
                break;

            case 'time':
                $label = __('Fixed Time', 'outpaceseo');
                break;

            case 'datetime-local':
                $label = __('Fixed Date & Time', 'outpaceseo');
                break;

            case 'rating':
                $label = __('Fixed Rating', 'outpaceseo');
                break;
            default:
                break;
        }
        return $label;
    }

    /**
     * Adds or removes list table column headings.
     *
     * @param array $columns Array of columns.
     */
    public static function column_headings($columns)
    {

        unset($columns['date']);

        $columns['outpaceseo_schema_type']          = __('Type', 'outpaceseo');
        $columns['outpaceseo_schema_display_rules'] = __('Target Location', 'outpaceseo');
        $columns['date']                        = __('Date', 'outpaceseo');

        return $columns;
    }

    /**
     * Adds the custom list table column content.
     * @param array $column Name of column.
     * @param int   $post_id Post id.
     */
    public function column_content($column, $post_id)
    {
        if ('outpaceseo_schema_type' === $column) {
            $meta_key = get_post_meta($post_id, 'outpaceseo-schema-type', true);
            echo isset(self::$schema_meta_fields['outpaceseo-' . $meta_key]['label']) ? esc_html(self::$schema_meta_fields['outpaceseo-' . $meta_key]['label']) : '';
        } elseif ('outpaceseo_schema_display_rules' === $column) {
            $locations = get_post_meta($post_id, 'outpaceseo-schema-location', true);
            if (!empty($locations)) {
                echo '<div class="outpaceseo-schema-location-wrap" style="margin-bottom: 5px;">';
                echo '<strong>' . esc_html__('Enable On: ', 'outpaceseo') . '</strong>';
                $this->column_display_location_rules($locations);
                echo '</div>';
            }

            $locations = get_post_meta($post_id, 'outpaceseo-schema-exclusion', true);
            if (!empty($locations)) {
                echo '<div class="outpaceseo-schema-exclusion-wrap" style="margin-bottom: 5px;">';
                echo '<strong>' . esc_html__('Exclude From: ', 'outpaceseo') . '</strong>';
                $this->column_display_location_rules($locations);
                echo '</div>';
            }
        }
    }

    /**
     * Get Markup of Location rules for Display rule column.
     *
     * @param array $locations Array of locations.
     */
    public function column_display_location_rules($locations)
    {

        $location_label = array();
        $index          = array_search('specifics', $locations['rule'], true);
        if (false !== $index && !empty($index)) {
            unset($locations['rule'][$index]);
        }

        if (isset($locations['rule']) && is_array($locations['rule'])) {
            foreach ($locations['rule'] as $location) {
                $location_label[] = Outpaceseo_Target_Rule_Fields::get_location_by_key($location);
            }
        }
        if (isset($locations['specific']) && is_array($locations['specific'])) {
            foreach ($locations['specific'] as $location) {
                $location_label[] = Outpaceseo_Target_Rule_Fields::get_location_by_key($location);
            }
        }

        echo esc_html(join(', ', $location_label));
    }

    /**
     * Ajax handeler to return the posts based on the search query.
     * When searching for the post/pages only titles are searched for.
     */
    public function op_get_specific_meta_fields()
    {

        if (!current_user_can('manage_options')) {
            return false;
        }

        check_ajax_referer('spec_schema', 'nonce_ajax');

        $search_string = isset($_POST['q']) ? sanitize_text_field($_POST['q']) : '';
        $data          = array();
        $result        = array();

        global $wpdb;
        // WPCS: unprepared SQL OK.
        $op_meta_array = $wpdb->get_results("SELECT DISTINCT meta_key FROM {$wpdb->postmeta} WHERE meta_key LIKE '%{$search_string}%'", ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        $schema_post_meta_fields = array_merge(self::$schema_meta_keys, array('outpaceseo-schema-type', 'outpaceseo-schema-location', 'outpaceseo-schema-exclusion'));
        if (isset($op_meta_array) && !empty($op_meta_array)) {
            foreach ($op_meta_array as $value) {
                if (!in_array($value['meta_key'], $schema_post_meta_fields, true)) {
                    $data[] = array(
                        'id'   => $value['meta_key'],
                        'text' => preg_replace('/^_/', '', esc_html(str_replace('_', ' ', $value['meta_key']))),
                    );
                }
            }
        }

        if (is_array($data) && !empty($data)) {
            $result[] = array(
                'children' => $data,
            );
        }

        wp_send_json($result);
    }

    /**
     * Initialize Schema Meta fields.
     *
     */
    public function init_schema_fields()
    {
        $doc_link         = '';

        self::$schema_meta_fields = apply_filters(
            'outpaceseo_schema_meta_fields',
            array(
                'outpaceseo-article'              => array(
                    'key'            => 'article',
                    'icon'           => 'dashicons dashicons-media-default',
                    'label'          => __('Article', 'outpaceseo'),
                    'guideline-link' => 'https://developers.google.com/search/docs/data-types/articles',
                    'path'           => OUTPACE_INCLUDES . 'schema/',
                    'subkeys'        => array(
                        'schema-type'      => array(
                            'label'    => esc_html__('Article Type', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'article',
                            'choices'  => array(
                                'Article'          => esc_html__('Article (General)', 'outpaceseo'),
                                'AdvertiserContentArticle' => esc_html__('Advertiser Content Article', 'outpaceseo'),
                                'BlogPosting'      => esc_html__('Blog Posting', 'outpaceseo'),
                                'NewsArticle'      => esc_html__('News Article', 'outpaceseo'),
                                'Report'           => esc_html__('Report', 'outpaceseo'),
                                'SatiricalArticle' => esc_html__('Satirical Article', 'outpaceseo'),
                                'ScholarlyArticle' => esc_html__('Scholarly Article', 'outpaceseo'),
                                'TechArticle'      => esc_html__('Tech Article', 'outpaceseo'),
                            ),
                            'required' => true,
                        ),
                        'author'           => array(
                            'label'    => esc_html__('Author Name', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'author_name',
                            'required' => true,
                        ),
                        'author-url'       => array(
                            'label'   => esc_html__('Author URL', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'author_url',
                        ),
                        'image'            => array(
                            'label'    => esc_html__('Image', 'outpaceseo'),
                            'type'     => 'image',
                            'default'  => 'featured_img',
                            'required' => true,
                        ),
                        'description'      => array(
                            'label'   => esc_html__('Short Description', 'outpaceseo'),
                            'type'    => 'textarea',
                            'default' => 'post_excerpt',
                        ),
                        'main-entity'      => array(
                            'label'   => esc_html__('URL', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'post_permalink',
                        ),
                        'name'             => array(
                            'label'    => esc_html__('Headline', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'post_title',
                            'required' => true,
                        ),
                        'published-date'   => array(
                            'label'    => esc_html__('Published Date', 'outpaceseo'),
                            'type'     => 'date',
                            'default'  => 'post_date',
                            'required' => true,
                        ),
                        'modified-date'    => array(
                            'label'    => esc_html__('Modified Date', 'outpaceseo'),
                            'type'     => 'date',
                            'default'  => 'post_modified',
                            'required' => true,
                        ),
                        'orgnization-name' => array(
                            'label'    => esc_html__('Publisher Name', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'blogname',
                            'required' => true,
                        ),
                        'site-logo'        => array(
                            'label'    => esc_html__('Publisher Logo', 'outpaceseo'),
                            'type'     => 'image',
                            'default'  => 'site_logo',
                            'required' => true,
                        ),
                    ),
                ),
                'outpaceseo-book'                 => array(
                    'key'            => 'book',
                    'icon'           => 'dashicons dashicons-book',
                    'label'          => __('Book', 'outpaceseo'),
                    'guideline-link' => 'https://developers.google.com/search/docs/data-types/books',
                    'path'           => OUTPACE_INCLUDES . 'schema/',
                    'subkeys'        => array(
                        'name'         => array(
                            'label'    => esc_html__('Title Of The Book', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'post_title',
                            'required' => true,
                        ),
                        'image'        => array(
                            'label'   => esc_html__('Image Of The Book', 'outpaceseo'),
                            'type'    => 'image',
                            'default' => 'featured_img',
                        ),
                        'author'       => array(
                            'label'    => esc_html__('Author Name', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'none',
                            'required' => true,
                        ),
                        'url'          => array(
                            'label'    => esc_html__('URL', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'post_permalink',
                            'required' => true,
                        ),
                        'work-example' => array(
                            'label'    => esc_html__('Book Edition Name', 'outpaceseo'),
                            'type'     => 'repeater',
                            'required' => true,
                            'fields'   => array(
                                'serial-number'   => array(
                                    'label'       => esc_html__('ISBN', 'outpaceseo'),
                                    'type'        => 'number',
                                    'default'     => 'none',
                                    'required'    => true,
                                    'description' => esc_html__('The International Standard Book Number (ISBN) is a unique numeric commercial book identifier. ISBN having 10 or 13 digit number.', 'outpaceseo'),
                                ),
                                'book-format'     => array(
                                    'label'         => esc_html__('Book Format', 'outpaceseo'),
                                    'type'          => 'dropdown',
                                    'default'       => 'none',
                                    'dropdown-type' => 'book-format',
                                    'required'      => true,
                                    'description'   => esc_html__('The format of the book using one or more of the [ EBook, Hardcover, Paperback, AudioBook ] values', 'outpaceseo'),

                                ),
                                'book-edition'    => array(
                                    'label'   => esc_html__('Book Edition', 'outpaceseo'),
                                    'type'    => 'text',
                                    'default' => 'none',
                                ),

                                'url-template'    => array(
                                    'label'       => esc_html__('Platform URL Template', 'outpaceseo'),
                                    'type'        => 'text',
                                    'default'     => 'none',
                                    'required'    => true,
                                    'description' => esc_html__('Provide the link in which platform works. For example desktop web browsers link', 'outpaceseo'),
                                ),
                                'action-platform' => array(
                                    'label'         => esc_html__(' Work Platforms', 'outpaceseo'),
                                    'type'          => 'multi-select',
                                    'default'       => 'none',
                                    'dropdown-type' => 'action-platform',
                                    'required'      => true,
                                    'description'   => esc_html__('The platform(s) on which the link works For example Works on desktop web browsers, Works on mobile web browsers.', 'outpaceseo'),
                                ),
                                'price'           => array(
                                    'label'    => esc_html__('Offer Price', 'outpaceseo'),
                                    'type'     => 'number',
                                    'default'  => 'none',
                                    'required' => true,
                                    'attrs'    => array(
                                        'min'  => '0',
                                        'step' => 'any',
                                    ),
                                ),
                                'currency'        => array(
                                    'label'         => esc_html__('Offer Price Currency', 'outpaceseo'),
                                    'type'          => 'dropdown',
                                    'default'       => 'none',
                                    'required'      => true,
                                    'dropdown-type' => 'currency',
                                ),
                                'country'         => array(
                                    'label'         => esc_html__('Offer Eligible Country', 'outpaceseo'),
                                    'type'          => 'multi-select',
                                    'default'       => 'none',
                                    'dropdown-type' => 'country',
                                ),
                                'avail'           => array(
                                    'label'         => esc_html__('Offer Availability Status', 'outpaceseo'),
                                    'type'          => 'dropdown',
                                    'default'       => 'none',
                                    'dropdown-type' => 'availability',
                                ),
                            ),
                        ),
                        'same-as'      => array(
                            'label'       => esc_html__('A Reference Link', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'none',
                            'description' => esc_html__('A reference page that unambiguously indicates the item\'s identity; for example, the URL of the item\'s Wikipedia page, Freebase page, or official website.', 'outpaceseo'),
                        ),
                    ),
                ),
                'outpaceseo-course'               => array(
                    'key'            => 'course',
                    'icon'           => 'dashicons dashicons-media-default',
                    'label'          => __('Course', 'outpaceseo'),
                    'guideline-link' => 'https://developers.google.com/search/docs/data-types/courses',
                    'path'           => OUTPACE_INCLUDES . 'schema/',
                    'subkeys'        => array(
                        'name'             => array(
                            'label'    => esc_html__('Course Title', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'post_title',
                            'required' => true,
                        ),
                        'description'      => array(
                            'label'       => esc_html__('Description', 'outpaceseo'),
                            'type'        => 'textarea',
                            'default'     => 'post_content',
                            'description' => esc_html__('A description of the course. Display limit of 60 characters.', 'outpaceseo'),
                            'required'    => true,
                        ),
                        'course-code'      => array(
                            'label'       => esc_html__('Course Code', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'none',
                            'description' => esc_html__('The identifier for the Course used by the course provider (e.g. CS101 or 6.001).', 'outpaceseo'),
                        ),
                        'orgnization-name' => array(
                            'label'       => esc_html__('Course Provider', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'create-field',
                            'description' => esc_html__('The organization that publishes the source content of the course. For example, UC Berkeley.', 'outpaceseo'),
                        ),
                        'course-instance'  => array(
                            'label'       => esc_html__('Course Instance', 'outpaceseo'),
                            'type'        => 'repeater',
                            'description' => esc_html__('An offering of the course at a specific time and place or through specific media or mode of study or to a specific section of students.', 'outpaceseo'),
                            'fields'      => array(
                                'name'                 => array(
                                    'label'    => esc_html__('Instance Name', 'outpaceseo'),
                                    'type'     => 'text',
                                    'default'  => 'none',
                                    'required' => true,
                                ),
                                'description'          => array(
                                    'label'    => esc_html__('Instance Description', 'outpaceseo'),
                                    'type'     => 'textarea',
                                    'default'  => 'none',
                                    'required' => true,
                                ),
                                'course-mode'          => array(
                                    'label'       => esc_html__('Course Mode', 'outpaceseo'),
                                    'type'        => 'text',
                                    'default'     => 'none',
                                    'description' => esc_html__('The medium or means of delivery of the course instance or the mode of study, either as a text label (e.g. "online", "onsite" or "blended"; "synchronous" or "asynchronous"; "full-time" or "part-time") or as a URL reference to a term from a controlled vocabulary (e.g. https://ceds.ed.gov/element/001311#Asynchronous )', 'outpaceseo'),
                                ),
                                'image'                => array(
                                    'label'   => esc_html__('Image', 'outpaceseo'),
                                    'type'    => 'image',
                                    'default' => 'none',
                                ),
                                'event-status'         => array(
                                    'label'         => esc_html__('Course Status', 'outpaceseo'),
                                    'type'          => 'dropdown',
                                    'default'       => 'custom-text',
                                    'dropdown-type' => 'event-status',
                                    'required'      => false,
                                    'description'   => esc_html__('The status of the Course Instance.', 'outpaceseo'),

                                ),
                                'event-attendance-mode' => array(
                                    'label'         => esc_html__('Course Attendance Mode', 'outpaceseo'),
                                    'type'          => 'dropdown',
                                    'default'       => 'custom-text',
                                    'dropdown-type' => 'event-attendance-mode',
                                    'required'      => false,
                                    'description'   => esc_html__('The location of the Course Instance. There are different requirements depending on if the Course is happening online or at a physical location.', 'outpaceseo'),

                                ),
                                'start-date'           => array(
                                    'label'    => esc_html__('Start Date', 'outpaceseo'),
                                    'type'     => 'date',
                                    'default'  => 'none',
                                    'required' => true,
                                ),
                                'end-date'             => array(
                                    'label'   => esc_html__('End Date', 'outpaceseo'),
                                    'type'    => 'date',
                                    'default' => 'none',
                                ),
                                'previous-date'        => array(
                                    'label'   => esc_html__('Course Previous Start Date', 'outpaceseo'),
                                    'type'    => 'datetime-local',
                                    'class'   => 'wpsp-event-status-rescheduled',
                                    'default' => 'custom-text',
                                ),
                                'online-location'      => array(
                                    'label'   => esc_html__('Online Course URL', 'outpaceseo'),
                                    'type'    => 'text',
                                    'class'   => 'wpsp-event-status-online',
                                    'default' => 'post_permalink',
                                ),
                                'course-organizer-name' => array(
                                    'label'       => esc_html__('Course Organizer Name', 'outpaceseo'),
                                    'type'        => 'text',
                                    'default'     => 'create-field',
                                    'required'    => false,
                                    'description' => esc_html__('The person or organization that is hosting the Course.', 'outpaceseo'),

                                ),
                                'course-organizer-url' => array(
                                    'label'    => esc_html__('Course Organizer URL', 'outpaceseo'),
                                    'type'     => 'text',
                                    'default'  => 'create-field',
                                    'required' => false,
                                ),
                                'location-name'        => array(
                                    'label'       => esc_html__('Location Name', 'outpaceseo'),
                                    'type'        => 'text',
                                    'class'       => 'wpsp-event-status-offline',
                                    'default'     => 'none',
                                    'description' => esc_html__('The venue of the course.', 'outpaceseo'),
                                ),
                                'location-address'     => array(
                                    'label'    => esc_html__('Location Address', 'outpaceseo'),
                                    'type'     => 'text',
                                    'default'  => 'none',
                                    'class'    => 'wpsp-event-status-offline',
                                    'required' => true,
                                ),
                                'price'                => array(
                                    'label'   => esc_html__('Price', 'outpaceseo'),
                                    'type'    => 'number',
                                    'default' => 'none',
                                    'attrs'   => array(
                                        'min'  => '0',
                                        'step' => 'any',
                                    ),
                                ),
                                'currency'             => array(
                                    'label'         => esc_html__('Currency', 'outpaceseo'),
                                    'type'          => 'dropdown',
                                    'default'       => 'custom-text',
                                    'dropdown-type' => 'currency',
                                ),
                                'valid-from'           => array(
                                    'label'   => esc_html__('Valid From', 'outpaceseo'),
                                    'type'    => 'date',
                                    'default' => 'none',
                                ),
                                'url'                  => array(
                                    'label'   => esc_html__('Offer URL', 'outpaceseo'),
                                    'type'    => 'text',
                                    'default' => 'none',
                                ),
                                'avail'                => array(
                                    'label'         => esc_html__('Availability', 'outpaceseo'),
                                    'type'          => 'dropdown',
                                    'default'       => 'none',
                                    'dropdown-type' => 'availability',
                                ),
                                'performer'            => array(
                                    'label'   => esc_html__('Performer', 'outpaceseo'),
                                    'type'    => 'text',
                                    'default' => 'none',
                                ),
                            ),
                        ),
                        'same-as'          => array(
                            'label'       => esc_html__('A Reference Link', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'none',
                            'description' => esc_html__('A reference page that unambiguously indicates the item\'s identity; for example, the URL of the item\'s Wikipedia page, Freebase page, or official website.', 'outpaceseo'),
                        ),
                        'rating'           => array(
                            'label'   => esc_html__('Rating', 'outpaceseo'),
                            'type'    => 'rating',
                            'default' => 'none',
                        ),
                        'review-count'     => array(
                            'label'       => esc_html__('Review Count', 'outpaceseo'),
                            'type'        => 'number',
                            'default'     => 'none',
                            'description' => esc_html__('The count of total number of reviews. e.g. "11"', 'outpaceseo'),
                        ),
                    ),
                ),
                'outpaceseo-event'                => array(
                    'key'            => 'event',
                    'icon'           => 'dashicons dashicons-tickets-alt',
                    'label'          => __('Event', 'outpaceseo'),
                    'guideline-link' => 'https://developers.google.com/search/docs/data-types/events',
                    'path'           => OUTPACE_INCLUDES . 'schema/',
                    'subkeys'        => array(
                        'schema-type'           => array(
                            'label'   => esc_html__('Event Type', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'Event',
                            'choices' => array(
                                'Event'            => esc_html__('Event (General)', 'outpaceseo'),
                                'BusinessEvent'    => esc_html__('Business Event', 'outpaceseo'),
                                'ChildrensEvent'   => esc_html__('Childrens Event', 'outpaceseo'),
                                'ComedyEvent'      => esc_html__('Comedy Event', 'outpaceseo'),
                                'CourseInstance'   => esc_html__('Course Instance', 'outpaceseo'),
                                'DanceEvent'       => esc_html__('Dance Event', 'outpaceseo'),
                                'DeliveryEvent'    => esc_html__('Delivery Event', 'outpaceseo'),
                                'EducationEvent'   => esc_html__('Education Event', 'outpaceseo'),
                                'EventSeries'      => esc_html__('EventSeries', 'outpaceseo'),
                                'ExhibitionEvent'  => esc_html__('Exhibition Event', 'outpaceseo'),
                                'Festival'         => esc_html__('Festival', 'outpaceseo'),
                                'FoodEvent'        => esc_html__('Food Event', 'outpaceseo'),
                                'LiteraryEvent'    => esc_html__('Literary Event', 'outpaceseo'),
                                'MusicEvent'       => esc_html__('Music Event', 'outpaceseo'),
                                'PublicationEvent' => esc_html__('Publication Event', 'outpaceseo'),
                                'SaleEvent'        => esc_html__('Sale Event', 'outpaceseo'),
                                'ScreeningEvent'   => esc_html__('Screening Event', 'outpaceseo'),
                                'SocialEvent'      => esc_html__('Social Event', 'outpaceseo'),
                                'SportsEvent'      => esc_html__('Sports Event', 'outpaceseo'),
                                'TheaterEvent'     => esc_html__('Theater Event', 'outpaceseo'),
                                'VisualArtsEvent'  => esc_html__('Visual Arts Event', 'outpaceseo'),

                            ),
                        ),
                        'name'                  => array(
                            'label'    => esc_html__(' Name', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'post_title',
                            'required' => true,
                        ),
                        'description'           => array(
                            'label'   => esc_html__(' Description', 'outpaceseo'),
                            'type'    => 'textarea',
                            'default' => 'post_content',
                        ),
                        'image'                 => array(
                            'label'   => esc_html__(' Image/Logo', 'outpaceseo'),
                            'type'    => 'image',
                            'default' => 'featured_img',
                        ),
                        'event-status'          => array(
                            'label'         => esc_html__(' Status', 'outpaceseo'),
                            'type'          => 'dropdown',
                            'default'       => 'custom-text',
                            'dropdown-type' => 'event-status',
                            'required'      => false,
                            'description'   => esc_html__('The status of the event. If you don\'t use this field, Google understands the eventStatus to be EventScheduled. ', 'outpaceseo'),

                        ),
                        'event-attendance-mode' => array(
                            'label'         => esc_html__(' Attendance Mode', 'outpaceseo'),
                            'type'          => 'dropdown',
                            'default'       => 'custom-text',
                            'dropdown-type' => 'event-attendance-mode',
                            'required'      => false,
                            'description'   => esc_html__('The location of the event. There are different requirements depending on if the event is happening online or at a physical location.', 'outpaceseo'),

                        ),
                        'start-date'            => array(
                            'label'    => esc_html__('Start Date', 'outpaceseo'),
                            'type'     => 'datetime-local',
                            'default'  => 'none',
                            'required' => true,
                        ),
                        'end-date'              => array(
                            'label'   => esc_html__('End Date', 'outpaceseo'),
                            'type'    => 'datetime-local',
                            'default' => 'none',
                        ),
                        'previous-date'         => array(
                            'label'   => esc_html__('Previous Start Date', 'outpaceseo'),
                            'type'    => 'datetime-local',
                            'class'   => 'wpsp-event-status-rescheduled',
                            'default' => 'custom-text',
                        ),
                        'online-location'       => array(
                            'label'   => esc_html__('Online Event URL', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'post_permalink',
                            'class'   => 'wpsp-event-status-online',

                        ),
                        'location'              => array(
                            'label'       => esc_html__('Location Name', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'none',
                            'class'       => 'wpsp-event-status-offline',
                            'description' => esc_html__('The detailed name of the place or venue where the event is being held. This property is only recommended for events that take place at a physical location.', 'outpaceseo'),
                        ),
                        'location-street'       => array(
                            'label'    => esc_html__('Street Address', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'none',
                            'class'    => 'wpsp-event-status-offline',
                            'required' => true,
                        ),
                        'location-locality'     => array(
                            'label'    => esc_html__('Locality', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'none',
                            'class'    => 'wpsp-event-status-offline',
                            'required' => true,
                        ),
                        'location-postal'       => array(
                            'label'    => esc_html__('Postal Code', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'none',
                            'class'    => 'wpsp-event-status-offline',
                            'required' => true,
                        ),
                        'location-region'       => array(
                            'label'    => esc_html__('Region', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'none',
                            'class'    => 'wpsp-event-status-offline',
                            'required' => true,
                        ),
                        'location-country'      => array(
                            'label'         => esc_html__('Country', 'outpaceseo'),
                            'type'          => 'dropdown',
                            'default'       => 'none',
                            'required'      => true,
                            'class'         => 'wpsp-event-status-offline',
                            'dropdown-type' => 'country',
                        ),

                        'avail'                 => array(
                            'label'         => esc_html__('Availability', 'outpaceseo'),
                            'type'          => 'dropdown',
                            'default'       => 'none',
                            'dropdown-type' => 'availability',
                            'description'   => esc_html__('The availability of this event, for example In stock, Out of stock, Pre-order, etc.', 'outpaceseo'),
                        ),
                        'price'                 => array(
                            'label'   => esc_html__('Price', 'outpaceseo'),
                            'type'    => 'number',
                            'default' => 'none',
                            'attrs'   => array(
                                'min'  => '0',
                                'step' => 'any',
                            ),
                        ),
                        'currency'              => array(
                            'label'         => esc_html__('Currency', 'outpaceseo'),
                            'type'          => 'dropdown',
                            'default'       => 'none',
                            'dropdown-type' => 'currency',
                        ),
                        'valid-from'            => array(
                            'label'   => esc_html__('Valid From', 'outpaceseo'),
                            'type'    => 'date',
                            'default' => 'none',
                        ),
                        'ticket-buy-url'        => array(
                            'label'   => esc_html__('Online Ticket URL', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'none',
                        ),
                        'performer'             => array(
                            'label'   => esc_html__('Performer', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'none',
                        ),
                        'event-organizer-name'  => array(
                            'label'         => esc_html__('Organizer Name', 'outpaceseo'),
                            'type'          => 'text',
                            'default'       => 'none',
                            'dropdown-type' => 'event-attendance-mode',
                            'required'      => false,
                            'description'   => esc_html__('The person or organization that is hosting the event.', 'outpaceseo'),

                        ),
                        'event-organizer-url'   => array(
                            'label'         => esc_html__('Organizer URL', 'outpaceseo'),
                            'type'          => 'text',
                            'default'       => 'none',
                            'dropdown-type' => 'event-attendance-mode',
                            'required'      => false,
                        ),
                    ),
                ),
                'outpaceseo-job-posting'          => array(
                    'key'            => 'job-posting',
                    'icon'           => 'dashicons dashicons-businessman',
                    'label'          => __('Job Posting', 'outpaceseo'),
                    'guideline-link' => 'https://developers.google.com/search/docs/data-types/job-postings',
                    'path'           => OUTPACE_INCLUDES . 'schema/',
                    'subkeys'        => array(
                        'title'                   => array(
                            'label'    => esc_html__('Job Title', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'none',
                            'required' => true,
                        ),
                        'orgnization-name'        => array(
                            'label'    => esc_html__('Hiring Organization', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'blogname',
                            'required' => true,
                        ),
                        'same-as'                 => array(
                            'label'       => esc_html__('Hiring Organization URL', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'site_url',
                            'required'    => true,
                            'description' => esc_html__('A referenced URL of the organization page to identity information. E.g. The URL of the Organization Wikipedia page, Wikidata entry, or official website.', 'outpaceseo'),
                        ),
                        'organization-logo'       => array(
                            'label'   => esc_html__('Hiring Organization Logo', 'outpaceseo'),
                            'type'    => 'image',
                            'default' => 'site_logo',
                        ),
                        'industry'                => array(
                            'label'   => esc_html__('Industry', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'none',
                        ),
                        'job-type'                => array(
                            'label'         => esc_html__('Employment Type', 'outpaceseo'),
                            'type'          => 'dropdown',
                            'default'       => 'none',
                            'dropdown-type' => 'employment',
                        ),
                        'description'             => array(
                            'label'    => esc_html__('Job Description', 'outpaceseo'),
                            'type'     => 'textarea',
                            'default'  => 'post_content',
                            'required' => true,
                        ),
                        'start-date'              => array(
                            'label'    => esc_html__('Date Posted', 'outpaceseo'),
                            'type'     => 'date',
                            'default'  => 'post_date',
                            'required' => true,
                        ),
                        'expiry-date'             => array(
                            'label'   => esc_html__('Valid Through', 'outpaceseo'),
                            'type'    => 'date',
                            'default' => 'none',
                        ),
                        'education-requirements'  => array(
                            'label'       => esc_html__('Education', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'none',
                            'description' => esc_html__('Educational background needed for the position or Occupation.', 'outpaceseo'),
                        ),
                        'experience-requirements' => array(
                            'label'   => esc_html__('Job Experience', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'none',
                        ),
                        'qualifications'          => array(
                            'label'       => esc_html__('Qualifications', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'none',
                            'description' => esc_html__('Specific qualifications required for this role or Occupation.For example A diploma, academic degree, certification.', 'outpaceseo'),
                        ),
                        'responsibilities'        => array(
                            'label'   => esc_html__('Responsibilities', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'none',
                        ),
                        'skills'                  => array(
                            'label'   => esc_html__('Skills', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'none',
                        ),
                        'work-hours'              => array(
                            'label'   => esc_html__('Work Hours', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'none',
                        ),
                        'job-location-type'       => array(
                            'label'       => esc_html__('Job Location Type', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'none',
                            'required'    => false,
                            'description' => esc_html__('Use value "TELECOMMUTE" for jobs in which the employee may or must work remotely 100% of the time.', 'outpaceseo'),
                        ),
                        'remote-location'         => array(
                            'label'  => esc_html__('Remote Location', 'outpaceseo'),
                            'type'   => 'repeater',
                            'fields' => array(
                                'applicant-location' => array(
                                    'label'       => esc_html__('Applicant Location', 'outpaceseo'),
                                    'type'        => 'text',
                                    'default'     => 'create-field',
                                    'required'    => false,
                                    'description' => esc_html__('The geographic location(s) in which employees may be located to be eligible for the Remote job.', 'outpaceseo'),
                                ),
                            ),
                        ),
                        'location-street'         => array(
                            'label'    => esc_html__('Street Address', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'none',
                            'required' => true,
                        ),
                        'location-locality'       => array(
                            'label'    => esc_html__('Locality', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'none',
                            'required' => true,
                        ),
                        'location-postal'         => array(
                            'label'    => esc_html__('Postal Code', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'none',
                            'required' => true,
                        ),
                        'location-region'         => array(
                            'label'    => esc_html__('Region', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'none',
                            'required' => true,
                        ),
                        'location-country'        => array(
                            'label'         => esc_html__('Country', 'outpaceseo'),
                            'type'          => 'dropdown',
                            'default'       => 'none',
                            'required'      => true,
                            'dropdown-type' => 'country',
                        ),
                        'salary'                  => array(
                            'label'   => esc_html__('Base Salary', 'outpaceseo'),
                            'type'    => 'number',
                            'default' => 'none',
                        ),
                        'salary-min-value'        => array(
                            'label'   => esc_html__('Min Salary', 'outpaceseo'),
                            'type'    => 'number',
                            'default' => 'create-field',
                        ),
                        'salary-max-value'        => array(
                            'label'   => esc_html__('Max Salary', 'outpaceseo'),
                            'type'    => 'number',
                            'default' => 'create-field',
                        ),
                        'salary-currency'         => array(
                            'label'         => esc_html__('Salary In Currency', 'outpaceseo'),
                            'type'          => 'dropdown',
                            'default'       => 'none',
                            'dropdown-type' => 'currency',
                        ),
                        'salary-unit'             => array(
                            'label'         => esc_html__('Salary Per Unit', 'outpaceseo'),
                            'type'          => 'dropdown',
                            'default'       => 'none',
                            'dropdown-type' => 'time-unit',
                            'description'   => esc_html__('A string or text indicating the unit of salary measurement. For example MONTH, YEAR.', 'outpaceseo'),
                        ),
                    ),
                ),
                'outpaceseo-local-business'       => array(
                    'key'            => 'local-business',
                    'icon'           => 'dashicons dashicons-admin-site',
                    'label'          => __('Local Business', 'outpaceseo'),
                    'guideline-link' => 'https://developers.google.com/search/docs/data-types/local-businesses',
                    'path'           => OUTPACE_INCLUDES . 'schema/',
                    'subkeys'        => array(
                        'name'                => array(
                            'label'    => esc_html__('Business Name', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'blogname',
                            'required' => true,
                        ),
                        'schema-type'         => array(
                            'label'   => esc_html__('Local Business Type', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'LocalBusiness',
                            'choices' => array(
                                'LocalBusiness'        => esc_html__('General', 'outpaceseo'),
                                'AnimalShelter'        => esc_html__('Animal Shelter', 'outpaceseo'),
                                'AutomotiveBusiness'   => esc_html__('Automotive', 'outpaceseo'),
                                'ChildCare'            => esc_html__('Child Care', 'outpaceseo'),
                                'Dentist'              => esc_html__('Dentist', 'outpaceseo'),
                                'DryCleaningOrLaundry' => esc_html__('Dry Cleaning Or Laundry', 'outpaceseo'),
                                'EmergencyService'     => esc_html__('Emergency Service', 'outpaceseo'),
                                'EmploymentAgency'     => esc_html__('Employment Agency', 'outpaceseo'),
                                'EntertainmentBusiness' => esc_html__('Entertainment', 'outpaceseo'),
                                'FinancialService'     => esc_html__('Financial Service', 'outpaceseo'),
                                'FoodEstablishment'    => esc_html__('Food Establishment', 'outpaceseo'),
                                'GovernmentOffice'     => esc_html__('Government Office', 'outpaceseo'),
                                'HealthAndBeautyBusiness' => esc_html__('Health And Beauty', 'outpaceseo'),
                                'HomeAndConstructionBusiness' => esc_html__('Home And Construction', 'outpaceseo'),
                                'InternetCafe'         => esc_html__('Internet Cafe', 'outpaceseo'),
                                'LegalService'         => esc_html__('Legal Service', 'outpaceseo'),
                                'Library'              => esc_html__('Library', 'outpaceseo'),
                                'Locksmith'            => esc_html__('Locksmith', 'outpaceseo'),
                                'LodgingBusiness'      => esc_html__('Lodging', 'outpaceseo'),
                                'MedicalBusiness'      => esc_html__('Medical Business', 'outpaceseo'),
                                'RadioStation'         => esc_html__('Radio Station', 'outpaceseo'),
                                'RealEstateAgent'      => esc_html__('Real Estate Agent', 'outpaceseo'),
                                'RecyclingCenter'      => esc_html__('Recycling Center', 'outpaceseo'),
                                'SelfStorage'          => esc_html__('Self Storage', 'outpaceseo'),
                                'ShoppingCenter'       => esc_html__('Shopping Center', 'outpaceseo'),
                                'SportsActivityLocation' => esc_html__('Sports Activity Location', 'outpaceseo'),
                                'Store'                => esc_html__('Store', 'outpaceseo'),
                                'TelevisionStation'    => esc_html__('Television Station', 'outpaceseo'),
                                'TouristInformationCenter' => esc_html__('Tourist Information Center', 'outpaceseo'),
                                'TravelAgency'         => esc_html__('Travel Agency', 'outpaceseo'),
                            ),
                        ),
                        'image'               => array(
                            'label'    => esc_html__('Business Image', 'outpaceseo'),
                            'type'     => 'image',
                            'default'  => 'none',
                            'required' => true,
                        ),
                        'telephone'           => array(
                            'label'   => esc_html__('Telephone', 'outpaceseo'),
                            'type'    => 'tel',
                            'default' => 'none',
                        ),
                        'price-range'         => array(
                            'label'       => esc_html__('Price Range', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'none',
                            'description' => esc_html__('The relative price range of a business, commonly specified by either a numerical range (for example, "$10-15") or a normalized number of currency signs (for example, "$$$")', 'outpaceseo'),
                        ),
                        'url'                 => array(
                            'label'   => esc_html__('URL', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'site_url',
                        ),
                        'location-street'     => array(
                            'label'    => esc_html__('Street Address', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'none',
                            'required' => true,
                        ),
                        'location-locality'   => array(
                            'label'    => esc_html__('Locality', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'none',
                            'required' => true,
                        ),
                        'location-postal'     => array(
                            'label'    => esc_html__('Postal Code', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'none',
                            'required' => true,
                        ),
                        'location-region'     => array(
                            'label'    => esc_html__('Region', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'none',
                            'required' => true,
                        ),
                        'location-country'    => array(
                            'label'         => esc_html__('Country', 'outpaceseo'),
                            'type'          => 'dropdown',
                            'default'       => 'none',
                            'dropdown-type' => 'country',
                            'required'      => true,
                        ),

                        'hours-specification' => array(
                            'label'  => esc_html__('Hours Specification', 'outpaceseo'),
                            'type'   => 'repeater',
                            'fields' => array(
                                'days'   => array(
                                    'label'         => esc_html__('Day Of Week', 'outpaceseo'),
                                    'type'          => 'multi-select',
                                    'default'       => 'none',
                                    'required'      => true,
                                    'dropdown-type' => 'days',
                                    'description'   => esc_html__('Here, you can select multiple days. e.g. "11"', 'outpaceseo'),
                                ),
                                'opens'  => array(
                                    'label'    => esc_html__('Opens', 'outpaceseo'),
                                    'type'     => 'time',
                                    'default'  => 'none',
                                    'required' => true,
                                ),
                                'closes' => array(
                                    'label'    => esc_html__('Closes', 'outpaceseo'),
                                    'type'     => 'time',
                                    'default'  => 'none',
                                    'required' => true,
                                ),
                            ),
                        ),
                        'geo-latitude'        => array(
                            'label'       => esc_html__(' Latitude', 'outpaceseo'),
                            'type'        => 'number',
                            'default'     => 'create-field',
                            'required'    => false,
                            'attrs'       => array(
                                'step' => 'any',
                            ),
                            'description' => esc_html__('The latitude of the business location. . e.g. "37.293058"', 'outpaceseo'),
                        ),
                        'geo-longitude'       => array(
                            'label'       => esc_html__('Longitude', 'outpaceseo'),
                            'type'        => 'number',
                            'default'     => 'create-field',
                            'required'    => false,
                            'attrs'       => array(
                                'step' => 'any',
                            ),
                            'description' => esc_html__('The longitude of the business location. e.g. "-121.988331"', 'outpaceseo'),
                        ),
                        'rating'              => array(
                            'label'   => esc_html__('Rating', 'outpaceseo'),
                            'type'    => 'rating',
                            'default' => 'accept-user-rating',
                        ),
                        'review-count'        => array(
                            'label'       => esc_html__('Review Count', 'outpaceseo'),
                            'type'        => 'number',
                            'default'     => 'none',
                            'description' => esc_html__('The count of total number of reviews. e.g. "11"', 'outpaceseo'),
                        ),
                    ),
                ),
                'outpaceseo-review'               => array(
                    'key'            => 'review',
                    'icon'           => 'dashicons dashicons-admin-comments',
                    'label'          => __('Review', 'outpaceseo'),
                    'guideline-link' => 'https://developers.google.com/search/docs/data-types/reviews',
                    'path'           => OUTPACE_INCLUDES . 'schema/',
                    'subkeys'        => array(
                        'schema-type'    => array(
                            'label'    => esc_html__('Review Item Type', 'outpaceseo'),
                            'type'     => 'text',
                            'required' => true,
                            'choices'  => array(
                                ''                   => esc_html__('Select Item Type', 'outpaceseo'),
                                'outpaceseo-product' => esc_html__('Product', 'outpaceseo'),
                                'outpaceseo-book'    => esc_html__('Book', 'outpaceseo'),
                                'outpaceseo-course'  => esc_html__('Course', 'outpaceseo'),
                                'outpaceseo-event'   => esc_html__('Event', 'outpaceseo'),
                                'outpaceseo-local-business' => esc_html__('Local business', 'outpaceseo'),
                                'outpaceseo-recipe'  => esc_html__('Recipe', 'outpaceseo'),
                                'outpaceseo-software-application' => esc_html__('Software Application', 'outpaceseo'),
                                'outpaceseo-movie'   => esc_html__('Movie', 'outpaceseo'),
                                'outpaceseo-organization' => esc_html__('Organization', 'outpaceseo'),
                            ),
                        ),
                        'review-body'    => array(
                            'label'    => esc_html__('Review Body', 'outpaceseo'),
                            'type'     => 'textarea',
                            'default'  => 'post_content',
                            'required' => false,
                        ),
                        'date'           => array(
                            'label'    => esc_html__('Review Date', 'outpaceseo'),
                            'type'     => 'date',
                            'default'  => 'post_date',
                            'required' => false,
                        ),
                        'rating'         => array(
                            'label'   => esc_html__('Review Rating', 'outpaceseo'),
                            'type'    => 'rating',
                            'default' => 'none',
                        ),
                        'reviewer-type'  => array(
                            'label'   => esc_html__('Reviewer Type', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'Person',
                            'choices' => array(
                                'Person'       => esc_html__('Person', 'outpaceseo'),
                                'Organization' => esc_html__('Organization', 'outpaceseo'),
                            ),
                        ),
                        'reviewer-name'  => array(
                            'label'   => esc_html__('Reviewer Name', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'author_name',
                        ),
                        'publisher-name' => array(
                            'label'   => esc_html__('Publisher Name', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'author_name',
                        ),
                    ),
                ),
                'outpaceseo-person'               => array(
                    'key'            => 'person',
                    'icon'           => 'dashicons dashicons-admin-users',
                    'label'          => __('Person', 'outpaceseo'),
                    'guideline-link' => 'https://schema.org/Person',
                    'path'           => OUTPACE_INCLUDES . 'schema/',
                    'subkeys'        => array(

                        'name'         => array(
                            'label'    => esc_html__('Person Name', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'none',
                            'required' => true,
                        ),
                        'gender'       => array(
                            'label'         => esc_html__('Gender', 'outpaceseo'),
                            'type'          => 'dropdown',
                            'dropdown-type' => 'gender-select',
                            'default'       => 'none',
                        ),
                        'dob'          => array(
                            'label'   => esc_html__('DOB', 'outpaceseo'),
                            'type'    => 'date',
                            'default' => 'none',
                        ),
                        'member'       => array(
                            'label'       => esc_html__('Member Of', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'none',
                            'description' => esc_html__('An Organization (or ProgramMembership) to which this Person or Organization belongs.', 'outpaceseo'),
                        ),
                        'email'        => array(
                            'label'    => esc_html__('Person Email', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'none',
                            'required' => false,
                        ),
                        'telephone'    => array(
                            'label'   => esc_html__('Telephone', 'outpaceseo'),
                            'type'    => 'tel',
                            'default' => 'none',
                        ),
                        'image'        => array(
                            'label'   => esc_html__('Photograph', 'outpaceseo'),
                            'type'    => 'image',
                            'default' => 'none',
                        ),
                        'job-title'    => array(
                            'label'   => esc_html__('Job Title', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'none',
                        ),
                        'homepage-url' => array(
                            'label'   => esc_html__('Homepage URL', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'none',
                        ),
                        'nationality'  => array(
                            'label'    => esc_html__('Nationality', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'none',
                            'required' => false,
                        ),
                        'street'       => array(
                            'label'    => esc_html__('Street Address', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'none',
                            'required' => false,
                        ),
                        'locality'     => array(
                            'label'    => esc_html__('Locality', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'none',
                            'required' => false,
                        ),
                        'postal'       => array(
                            'label'    => esc_html__('Postal Code', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'none',
                            'required' => false,
                        ),
                        'region'       => array(
                            'label'    => esc_html__('Region', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'none',
                            'required' => false,
                        ),
                        'country'      => array(
                            'label'         => esc_html__('Country', 'outpaceseo'),
                            'type'          => 'dropdown',
                            'default'       => 'none',
                            'dropdown-type' => 'country',
                        ),
                        'add-url'      => array(
                            'label'       => esc_html__('A Reference Link', 'outpaceseo'),
                            'type'        => 'repeater',
                            'description' => esc_html__('A reference page that unambiguously indicates the item\'s identity; for example, the URL of the item\'s Wikipedia page, Freebase page, or official website.', 'outpaceseo'),
                            'fields'      => array(
                                'same-as' => array(
                                    'label'    => esc_html__('URL', 'outpaceseo'),
                                    'type'     => 'text',
                                    'default'  => 'none',
                                    'required' => false,
                                ),
                            ),
                        ),
                    ),
                ),
                'outpaceseo-product'              => array(
                    'key'            => 'product',
                    'icon'           => 'dashicons dashicons-cart',
                    'label'          => __('Product', 'outpaceseo'),
                    'guideline-link' => 'https://developers.google.com/search/docs/data-types/products',
                    'path'           => OUTPACE_INCLUDES . 'schema/',
                    'subkeys'        => array(
                        'name'              => array(
                            'label'    => esc_html__('Product Name', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'post_title',
                            'required' => true,
                        ),
                        'brand-name'        => array(
                            'label'   => esc_html__('Product Brand', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'none',
                        ),
                        'image'             => array(
                            'label'    => esc_html__('Product Image', 'outpaceseo'),
                            'type'     => 'image',
                            'default'  => 'featured_img',
                            'required' => true,
                        ),
                        'url'               => array(
                            'label'   => esc_html__('Product URL', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'post_permalink',
                        ),
                        'description'       => array(
                            'label'   => esc_html__('Product Description', 'outpaceseo'),
                            'type'    => 'textarea',
                            'default' => 'post_content',
                        ),
                        'sku'               => array(
                            'label'       => esc_html__('Product SKU', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'create-field',
                            'description' => esc_html__('The Stock Keeping Unit (SKU) is a unique numerical identifying number that refers to a specific stock item in a retailers inventory or product catalog.', 'outpaceseo'),
                        ),
                        'mpn'               => array(
                            'label'       => esc_html__('Product MPN', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'create-field',
                            'description' => esc_html__('The Manufacturer Part Number (MPN) of the product, or the product to which the offer refers. e.g. "925872"', 'outpaceseo'),
                        ),
                        'avail'             => array(
                            'label'         => esc_html__('Product Availability', 'outpaceseo'),
                            'type'          => 'dropdown',
                            'default'       => 'none',
                            'dropdown-type' => 'availability',
                        ),
                        'price-valid-until' => array(
                            'label'       => esc_html__('Price Valid Until', 'outpaceseo'),
                            'type'        => 'datetime-local',
                            'default'     => 'create-field',
                            'description' => esc_html__('The date after which the price will no longer be available. e.g. "31/12/2021 09:00 AM"', 'outpaceseo'),
                        ),
                        'price'             => array(
                            'label'   => esc_html__('Product Price', 'outpaceseo'),
                            'type'    => 'number',
                            'default' => 'none',
                            'attrs'   => array(
                                'min'  => '0',
                                'step' => '0.01',
                            ),
                        ),
                        'currency'          => array(
                            'label'         => esc_html__('Currency', 'outpaceseo'),
                            'type'          => 'dropdown',
                            'default'       => 'none',
                            'dropdown-type' => 'currency',
                        ),
                        'product-review'    => array(
                            'label'  => esc_html__('Review', 'outpaceseo'),
                            'type'   => 'repeater',
                            'fields' => array(
                                'reviewer-type'  => array(
                                    'label'   => esc_html__('Reviewer Type', 'outpaceseo'),
                                    'type'    => 'text',
                                    'default' => 'Person',
                                    'choices' => array(
                                        'Person'       => esc_html__('Person', 'outpaceseo'),
                                        'Organization' => esc_html__('Organization', 'outpaceseo'),
                                    ),
                                ),
                                'reviewer-name'  => array(
                                    'label'    => esc_html__('Reviewer Name', 'outpaceseo'),
                                    'type'     => 'text',
                                    'default'  => 'author_name',
                                    'required' => true,
                                ),
                                'product-rating' => array(
                                    'label'   => esc_html__('Product Rating', 'outpaceseo'),
                                    'type'    => 'rating',
                                    'default' => 'none',
                                ),
                                'review-body'    => array(
                                    'label'   => esc_html__('Review Body', 'outpaceseo'),
                                    'type'    => 'textarea',
                                    'default' => 'post_content',
                                ),
                            ),
                        ),
                        'rating'            => array(
                            'label'   => esc_html__('Rating', 'outpaceseo'),
                            'type'    => 'rating',
                            'default' => 'accept-user-rating',
                        ),
                        'review-count'      => array(
                            'label'       => esc_html__('Review Count', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'none',
                            'description' => esc_html__('The count of total number of reviews. e.g. "11"', 'outpaceseo'),
                        ),
                        'product-review'    => array(
                            'label'  => esc_html__('Review', 'outpaceseo'),
                            'type'   => 'repeater',
                            'fields' => array(
                                'reviewer-type'  => array(
                                    'label'   => esc_html__('Reviewer Type', 'outpaceseo'),
                                    'type'    => 'text',
                                    'default' => 'Person',
                                    'choices' => array(
                                        'Person'       => esc_html__('Person', 'outpaceseo'),
                                        'Organization' => esc_html__('Organization', 'outpaceseo'),
                                    ),
                                ),
                                'reviewer-name'  => array(
                                    'label'    => esc_html__('Reviewer Name', 'outpaceseo'),
                                    'type'     => 'text',
                                    'default'  => 'author_name',
                                    'required' => true,
                                ),
                                'product-rating' => array(
                                    'label'   => esc_html__('Product Rating', 'outpaceseo'),
                                    'type'    => 'rating',
                                    'default' => 'none',
                                ),
                                'review-body'    => array(
                                    'label'   => esc_html__('Review Body', 'outpaceseo'),
                                    'type'    => 'textarea',
                                    'default' => 'post_content',
                                ),
                            ),
                        ),
                    ),
                ),
                'outpaceseo-recipe'               => array(
                    'key'            => 'recipe',
                    'icon'           => 'dashicons dashicons-carrot',
                    'label'          => __('Recipe', 'outpaceseo'),
                    'guideline-link' => 'https://developers.google.com/search/docs/data-types/recipes',
                    'path'           => OUTPACE_INCLUDES . 'schema/',
                    'subkeys'        => array(
                        'name'                => array(
                            'label'    => esc_html__('Recipe Name', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'none',
                            'required' => true,
                        ),
                        'image'               => array(
                            'label'    => esc_html__('Recipe Photo', 'outpaceseo'),
                            'type'     => 'image',
                            'default'  => 'featured_img',
                            'required' => true,
                        ),
                        'description'         => array(
                            'label'   => esc_html__('Recipe Description', 'outpaceseo'),
                            'type'    => 'textarea',
                            'default' => 'post_content',
                        ),
                        'reviewer-type'       => array(
                            'label'   => esc_html__('Author Type', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'Person',
                            'choices' => array(
                                'Person'       => esc_html__('Person', 'outpaceseo'),
                                'Organization' => esc_html__('Organization', 'outpaceseo'),
                            ),
                        ),
                        'author'              => array(
                            'label'   => esc_html__('Author Name', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'author_name',
                        ),
                        'preperation-time'    => array(
                            'label'   => esc_html__('Preparation Time', 'outpaceseo'),
                            'type'    => 'time-duration',
                            'default' => 'none',
                        ),
                        'cook-time'           => array(
                            'label'   => esc_html__('Cook Time', 'outpaceseo'),
                            'type'    => 'time-duration',
                            'default' => 'none',
                        ),
                        'recipe-keywords'     => array(
                            'label'       => esc_html__('Keywords', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'create-field',
                            'description' => esc_html__('e.g. "winter apple pie", "nutmeg crust"', 'outpaceseo'),
                        ),
                        'recipe-category'     => array(
                            'label'       => esc_html__('Recipe Category', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'create-field',
                            'description' => esc_html__('e.g. "dinner", "entree", or "dessert"', 'outpaceseo'),
                        ),
                        'recipe-cuisine'      => array(
                            'label'       => esc_html__('Recipe Cuisine', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'create-field',
                            'description' => esc_html__('e.g. "French", "Indian", or "American"', 'outpaceseo'),
                        ),
                        'nutrition'           => array(
                            'label'       => esc_html__('Recipe Calories', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'none',
                            'description' => esc_html__('The number of calories in the recipe.', 'outpaceseo'),
                        ),
                        'ingredients'         => array(
                            'label'       => esc_html__('Recipe Ingredients', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'none',
                            'description' => esc_html__('Ingredient used in the recipe. Separate multiple ingredients with comma(,).', 'outpaceseo'),
                        ),
                        'recipe-yield'        => array(
                            'label'    => esc_html__('Recipe Yield', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'create-field',
                            'required' => false,
                        ),
                        'recipe-instructions' => array(
                            'label'  => esc_html__('Recipe Instructions', 'outpaceseo'),
                            'type'   => 'repeater',
                            'fields' => array(
                                'name'  => array(
                                    'label'    => esc_html__('Instructions Name', 'outpaceseo'),
                                    'type'     => 'text',
                                    'default'  => 'create-field',
                                    'required' => false,
                                ),
                                'steps' => array(
                                    'label'   => esc_html__('Instructions Step', 'outpaceseo'),
                                    'type'    => 'text',
                                    'default' => 'create-field',
                                ),
                                'url'   => array(
                                    'label'    => esc_html__('Instructions URL', 'outpaceseo'),
                                    'type'     => 'text',
                                    'default'  => 'create-field',
                                    'required' => false,
                                ),
                                'image' => array(
                                    'label'    => esc_html__('Instructions Image', 'outpaceseo'),
                                    'type'     => 'image',
                                    'default'  => 'create-field',
                                    'required' => false,
                                ),

                            ),
                        ),
                        'recipe-video'        => array(
                            'label'         => esc_html__('Recipe Video', 'outpaceseo'),
                            'type'          => 'repeater',
                            'is_recommnded' => true,
                            'fields'        => array(
                                'video-name'  => array(
                                    'label'    => esc_html__('Video Name', 'outpaceseo'),
                                    'type'     => 'text',
                                    'default'  => 'create-field',
                                    'required' => true,
                                ),
                                'video-desc'  => array(
                                    'label'    => esc_html__('Video Description', 'outpaceseo'),
                                    'type'     => 'textarea',
                                    'default'  => 'create-field',
                                    'required' => true,
                                ),
                                'video-image' => array(
                                    'label'    => esc_html__('Thumbnail URL', 'outpaceseo'),
                                    'type'     => 'image',
                                    'default'  => 'create-field',
                                    'required' => true,
                                ),
                                'recipe-video-content-url' => array(
                                    'label'   => esc_html__('Content URL', 'outpaceseo'),
                                    'type'    => 'text',
                                    'default' => 'create-field',
                                ),
                                'recipe-video-embed-url' => array(
                                    'label'   => esc_html__('Embed URL', 'outpaceseo'),
                                    'type'    => 'text',
                                    'default' => 'create-field',
                                ),
                                'recipe-video-duration' => array(
                                    'label'   => esc_html__('Duration', 'outpaceseo'),
                                    'type'    => 'time-duration',
                                    'default' => 'create-field',
                                ),
                                'recipe-video-upload-date' => array(
                                    'label'    => esc_html__('Upload Date', 'outpaceseo'),
                                    'type'     => 'date',
                                    'default'  => 'post_date',
                                    'required' => true,
                                ),
                                'recipe-video-expires-date' => array(
                                    'label'   => esc_html__('Expires On', 'outpaceseo'),
                                    'type'    => 'date',
                                    'default' => 'create-field',
                                ),
                                'recipe-video-interaction-count' => array(
                                    'label'       => esc_html__('Interaction Count', 'outpaceseo'),
                                    'type'        => 'number',
                                    'default'     => 'create-field',
                                    'description' => esc_html__('The number of times the video has been watched.', 'outpaceseo'),
                                ),
                            ),
                        ),
                        'rating'              => array(
                            'label'   => esc_html__('Rating', 'outpaceseo'),
                            'type'    => 'rating',
                            'default' => 'accept-user-rating',
                        ),
                        'review-count'        => array(
                            'label'       => esc_html__('Review Count', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'none',
                            'description' => esc_html__('The count of total number of reviews. e.g. "11"', 'outpaceseo'),
                        ),
                    ),
                ),
                'outpaceseo-service'              => array(
                    'key'            => 'service',
                    'icon'           => 'dashicons dashicons-admin-generic',
                    'label'          => __('Service', 'outpaceseo'),
                    'guideline-link' => 'https://schema.org/Service',
                    'path'           => OUTPACE_INCLUDES . 'schema/',
                    'subkeys'        => array(
                        'name'              => array(
                            'label'    => esc_html__('Service Name', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'post_title',
                            'required' => true,
                        ),
                        'type'              => array(
                            'label'       => esc_html__('Service Type', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'none',
                            'required'    => true,
                            'description' => esc_html__('The type of service being offered, e.g. Broadcast Service, Cable Or Satellite Service, etc.', 'outpaceseo'),
                        ),
                        'area'              => array(
                            'label'       => esc_html__('Service Area', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'none',
                            'description' => esc_html__('The geographic area where a service or offered item is provided.', 'outpaceseo'),
                        ),
                        'provider'          => array(
                            'label'    => esc_html__('Service Provider Name', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'blogname',
                            'required' => true,
                        ),
                        'location-image'    => array(
                            'label'       => esc_html__('Service Provider Image', 'outpaceseo'),
                            'type'        => 'image',
                            'default'     => 'none',
                            'required'    => true,
                            'description' => esc_html__('The service provider or service operator Image .', 'outpaceseo'),
                        ),
                        'description'       => array(
                            'label'   => esc_html__('Service Description', 'outpaceseo'),
                            'type'    => 'textarea',
                            'default' => 'post_content',
                        ),
                        'image'             => array(
                            'label'       => esc_html__('Service Image', 'outpaceseo'),
                            'type'        => 'image',
                            'default'     => 'featured_img',
                            'description' => esc_html__('Here,you can add specific service image.', 'outpaceseo'),
                        ),
                        'location-locality' => array(
                            'label'   => esc_html__('Locality', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'none',
                        ),
                        'location-region'   => array(
                            'label'   => esc_html__('Region', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'none',
                        ),
                        'location-street'   => array(
                            'label'   => esc_html__('Street Address', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'none',
                        ),
                        'telephone'         => array(
                            'label'   => esc_html__('Telephone', 'outpaceseo'),
                            'type'    => 'tel',
                            'default' => 'none',
                        ),
                        'price-range'       => array(
                            'label'   => esc_html__('Price Range', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'none',
                        ),
                    ),
                ),
                'outpaceseo-software-application' => array(
                    'key'            => 'software-application',
                    'icon'           => 'dashicons dashicons-dashboard',
                    'label'          => __('Software Application', 'outpaceseo'),
                    'guideline-link' => 'https://developers.google.com/search/docs/data-types/software-apps',
                    'path'           => OUTPACE_INCLUDES . 'schema/',
                    'subkeys'        => array(
                        'name'             => array(
                            'label'    => esc_html__('Application Name', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'post_title',
                            'required' => true,
                        ),
                        'category'         => array(
                            'label'         => esc_html__('Application Type', 'outpaceseo'),
                            'type'          => 'dropdown',
                            'default'       => 'none',
                            'dropdown-type' => 'software-category',
                        ),
                        'operating-system' => array(
                            'label'       => esc_html__('Operating System', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'none',
                            'description' => esc_html__('Software for the operating system, for example, "Windows 7", "OSX 10.6", "Android 1.6"', 'outpaceseo'),
                        ),
                        'price'            => array(
                            'label'    => esc_html__('Price', 'outpaceseo'),
                            'type'     => 'number',
                            'default'  => 'none',
                            'required' => true,
                            'attrs'    => array(
                                'min'  => '0',
                                'step' => 'any',
                            ),
                        ),
                        'currency'         => array(
                            'label'         => esc_html__('Currency', 'outpaceseo'),
                            'type'          => 'dropdown',
                            'default'       => 'none',
                            'dropdown-type' => 'currency',
                        ),
                        'image'            => array(
                            'label'   => esc_html__('Application Image', 'outpaceseo'),
                            'type'    => 'image',
                            'default' => 'featured_img',
                        ),
                        'rating'           => array(
                            'label'    => esc_html__('Rating', 'outpaceseo'),
                            'type'     => 'rating',
                            'required' => true,
                            'default'  => 'accept-user-rating',
                        ),
                        'review-count'     => array(
                            'label'       => esc_html__('Review Count', 'outpaceseo'),
                            'type'        => 'number',
                            'required'    => true,
                            'default'     => 'none',
                            'description' => esc_html__('The count of total number of reviews. e.g. "11"', 'outpaceseo'),
                        ),
                    ),
                ),
                'outpaceseo-video-object'         => array(
                    'key'            => 'video-object',
                    'icon'           => 'dashicons dashicons-video-alt3',
                    'label'          => __('Video Object', 'outpaceseo'),

                    'guideline-link' => 'https://developers.google.com/search/docs/data-types/videos',
                    'path'           => OUTPACE_INCLUDES . 'schema/',
                    'subkeys'        => array(
                        'name'              => array(
                            'label'    => esc_html__('Video Title', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'post_title',
                            'required' => true,
                        ),
                        'description'       => array(
                            'label'    => esc_html__('Video Description', 'outpaceseo'),
                            'type'     => 'textarea',
                            'default'  => 'post_content',
                            'required' => true,
                        ),
                        'image'             => array(
                            'label'    => esc_html__('Video Thumbnail', 'outpaceseo'),
                            'type'     => 'image',
                            'default'  => 'featured_img',
                            'required' => true,
                        ),
                        'upload-date'       => array(
                            'label'    => esc_html__('Video Upload Date', 'outpaceseo'),
                            'type'     => 'date',
                            'default'  => 'post_date',
                            'required' => true,
                        ),
                        'orgnization-name'  => array(
                            'label'   => esc_html__('Publisher Name', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'blogname',
                        ),
                        'site-logo'         => array(
                            'label'   => esc_html__('Publisher Logo', 'outpaceseo'),
                            'type'    => 'image',
                            'default' => 'site_logo',
                        ),
                        'content-url'       => array(
                            'label'   => esc_html__('Content URL', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'none',
                        ),
                        'embed-url'         => array(
                            'label'   => esc_html__('Embed URL', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'none',
                        ),
                        'duration'          => array(
                            'label'   => esc_html__('Video Duration', 'outpaceseo'),
                            'type'    => 'time-duration',
                            'default' => 'none',
                        ),
                        'expires-date'      => array(
                            'label'   => esc_html__('Video Expires On', 'outpaceseo'),
                            'type'    => 'date',
                            'default' => 'none',
                        ),
                        'interaction-count' => array(
                            'label'       => esc_html__('Video Interaction Count', 'outpaceseo'),
                            'type'        => 'number',
                            'default'     => 'none',
                            'description' => esc_html__('The number of times the video has been watched.', 'outpaceseo'),
                        ),
                    ),
                ),
                'outpaceseo-faq'                  => array(
                    'key'            => 'faq',
                    'icon'           => 'dashicons dashicons-editor-help',
                    'label'          => __('FAQ', 'outpaceseo'),
                    'guideline-link' => 'https://developers.google.com/search/docs/data-types/faqpage',
                    'path'           => OUTPACE_INCLUDES . 'schema/',
                    'subkeys'        => array(

                        'question-answer' => array(
                            'label'  => esc_html__('Question-Answer', 'outpaceseo'),
                            'type'   => 'repeater-target',
                            'fields' => array(
                                'question' => array(
                                    'label'    => esc_html__('Question', 'outpaceseo'),
                                    'type'     => 'text',
                                    'default'  => 'create-field',
                                    'required' => true,
                                ),
                                'answer'   => array(
                                    'label'    => esc_html__('Answer', 'outpaceseo'),
                                    'type'     => 'textarea',
                                    'default'  => 'create-field',
                                    'required' => true,
                                ),
                            ),
                        ),
                    ),
                ),
                'outpaceseo-how-to'               => array(
                    'key'            => 'how-to',
                    'icon'           => 'dashicons dashicons-list-view',
                    'label'          => __('How-to', 'outpaceseo'),
                    'guideline-link' => 'https://schema.org/HowTo',
                    'path'           => OUTPACE_INCLUDES . 'schema/',
                    'subkeys'        => array(
                        'name'        => array(
                            'label'    => esc_html__('Name', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'post_title',
                            'required' => true,
                        ),
                        'description' => array(
                            'label'   => esc_html__('Description', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'post_content',
                        ),
                        'total-time'  => array(
                            'label'       => esc_html__('Total Time', 'outpaceseo'),
                            'type'        => 'time-duration',
                            'default'     => 'create-field',
                            'description' => esc_html__('The total time required to perform instructions or a direction (including time to prepare the supplies).', 'outpaceseo'),
                        ),
                        'supply'      => array(
                            'label'       => esc_html__('Materials', 'outpaceseo'),
                            'type'        => 'repeater-target',
                            'description' => esc_html__('The supply property lists the item(s) “consumed when performing instructions or a direction.”', 'outpaceseo'),
                            'fields'      => array(
                                'name' => array(
                                    'label'    => esc_html__('Name', 'outpaceseo'),
                                    'type'     => 'text',
                                    'default'  => 'none',
                                    'required' => true,
                                ),
                            ),
                        ),
                        'tool'        => array(
                            'label'       => esc_html__('Tools', 'outpaceseo'),
                            'type'        => 'repeater-target',
                            'description' => esc_html__('The tool property lists the item(s) used (but not consumed) when performing instructions or a direction.', 'outpaceseo'),
                            'fields'      => array(
                                'name' => array(
                                    'label'    => esc_html__('Name', 'outpaceseo'),
                                    'type'     => 'text',
                                    'default'  => 'none',
                                    'required' => true,
                                ),
                            ),
                        ),
                        'steps'       => array(
                            'label'       => esc_html__('Steps', 'outpaceseo'),
                            'type'        => 'repeater-target',
                            'required'    => true,
                            'description' => esc_html__('Google needs at least two steps.', 'outpaceseo'),
                            'fields'      => array(
                                'name'        => array(
                                    'label'    => esc_html__('Step Name', 'outpaceseo'),
                                    'type'     => 'text',
                                    'default'  => 'none',
                                    'required' => false,
                                ),
                                'description' => array(
                                    'label'    => esc_html__('Step Description', 'outpaceseo'),
                                    'type'     => 'textarea',
                                    'default'  => 'none',
                                    'required' => true,
                                ),
                                'url'         => array(
                                    'label'    => esc_html__('Step URL', 'outpaceseo'),
                                    'type'     => 'text',
                                    'default'  => 'post_permalink',
                                    'required' => false,
                                ),
                                'image'       => array(
                                    'label'    => esc_html__('Step Image', 'outpaceseo'),
                                    'type'     => 'image',
                                    'default'  => 'none',
                                    'required' => false,
                                ),
                            ),
                        ),

                    ),
                ),
                'outpaceseo-custom-markup'        => array(
                    'key'            => 'custom-markup',
                    'icon'           => 'dashicons dashicons-edit-page',
                    'label'          => __('Custom Markup', 'outpaceseo'),
                    'guideline-link' => '',
                    'path'           => OUTPACE_INCLUDES . 'schema/',
                    'subkeys'        => array(
                        'custom-markup' => array(
                            'label'       => esc_html__('Custom Markup', 'outpaceseo'),
                            'type'        => 'textarea',
                            'default'     => 'none',
                            'description' => esc_html__(
                                'Be sure to add custom schema markup in JSON-LD format.
								As the custom schema markup in JSON-LD format, make sure to add it in script tag.
								Validate schema markup with the Structured Data Testing Tool or Rich Results Test before adding to the website.',
                                'outpaceseo'
                            ),
                            'required'    => false,
                        ),
                    ),
                ),
                'outpaceseo-image-license'        => array(
                    'key'            => 'image-license',
                    'icon'           => 'dashicons dashicons-format-image',
                    'label'          => __('Image License', 'outpaceseo'),

                    'guideline-link' => 'https://developers.google.com/search/docs/advanced/structured-data/image-license-metadata',
                    'path'           => OUTPACE_INCLUDES . 'schema/',
                    'subkeys'        => array(
                        'image-license' => array(
                            'label'       => esc_html__('Image License', 'outpaceseo'),
                            'type'        => 'repeater',
                            'description' => esc_html__('Include the license property for your image to be eligible to be shown with the Licensable badge', 'outpaceseo'),
                            'fields'      => array(
                                'content-url'          => array(
                                    'label'    => esc_html__('Content URL', 'outpaceseo'),
                                    'type'     => 'image',
                                    'default'  => 'featured_img',
                                    'required' => true,
                                ),
                                'license'              => array(
                                    'label'    => esc_html__('License', 'outpaceseo'),
                                    'type'     => 'text',
                                    'default'  => 'create-field',
                                    'required' => true,
                                ),
                                'acquire-license-Page' => array(
                                    'label'   => esc_html__('Acquire License Page', 'outpaceseo'),
                                    'type'    => 'text',
                                    'default' => 'create-field',
                                ),
                            ),
                        ),
                    ),
                ),
            )
        );

        self::$schema_item_types = apply_filters(
            'outpaceseo_schema_item_type_recommended',
            array(
                'outpaceseo-book'                 => array(
                    'subkeys' => array(
                        'name'          => array(
                            'label'    => esc_html__('Book Name', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'post_title',
                            'required' => true,
                        ),
                        'description'   => array(
                            'label'    => esc_html__('Book Description', 'outpaceseo'),
                            'type'     => 'textarea',
                            'default'  => 'post_content',
                            'required' => true,
                        ),
                        'serial-number' => array(

                            'label'       => esc_html__('Book ISBN', 'outpaceseo'),
                            'type'        => 'number',
                            'default'     => 'create-field',
                            'required'    => true,
                            'description' => esc_html__('The International Standard Book Number (ISBN) is a unique numeric commercial book identifier. ISBN having 10 or 13 digit number.', 'outpaceseo'),
                        ),
                        'author'        => array(
                            'label'    => esc_html__('Book Author Name', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'create-field',
                            'required' => true,
                        ),
                        'same-As'       => array(
                            'label'       => esc_html__('Same As', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'create-field',
                            'description' => esc_html__('A reference page that unambiguously indicates the item\'s identity; for example, the URL of the item\'s Wikipedia page, Freebase page, or official website.', 'outpaceseo'),
                        ),
                    ),
                ),
                'outpaceseo-course'               => array(
                    'subkeys' => array(
                        'name'             => array(
                            'label'    => esc_html__('Course Name', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'post_title',
                            'required' => true,
                        ),
                        'description'      => array(
                            'label'   => esc_html__('Course Description', 'outpaceseo'),
                            'type'    => 'textarea',
                            'default' => 'post_content',
                        ),
                        'orgnization-name' => array(
                            'label'   => esc_html__('Course Organization Name', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'blogname',
                        ),
                    ),
                ),
                'outpaceseo-event'                => array(
                    'subkeys' => array(
                        'name'                  => array(
                            'label'    => esc_html__('Event Name', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'post_title',
                            'required' => true,
                        ),
                        'image'                 => array(
                            'label'   => esc_html__('Event Image', 'outpaceseo'),
                            'type'    => 'image',
                            'default' => 'featured_img',
                        ),
                        'event-status'          => array(
                            'label'         => esc_html__(' Status', 'outpaceseo'),
                            'type'          => 'dropdown',
                            'default'       => 'custom-text',
                            'dropdown-type' => 'event-status',
                            'required'      => false,
                            'description'   => esc_html__('The status of the event. If you don\'t use this field, Google understands the eventStatus to be EventScheduled. ', 'outpaceseo'),

                        ),
                        'event-attendance-mode' => array(
                            'label'         => esc_html__(' Attendance Mode', 'outpaceseo'),
                            'type'          => 'dropdown',
                            'default'       => 'custom-text',
                            'dropdown-type' => 'event-attendance-mode',
                            'required'      => false,
                            'description'   => esc_html__('The location of the event. There are different requirements depending on if the event is happening online or at a physical location.', 'outpaceseo'),

                        ),
                        'start-date'            => array(
                            'label'    => esc_html__('Event Start Date', 'outpaceseo'),
                            'type'     => 'datetime-local',
                            'default'  => 'create-field',
                            'required' => true,
                        ),
                        'end-date'              => array(
                            'label'   => esc_html__('Event End Date', 'outpaceseo'),
                            'type'    => 'datetime-local',
                            'default' => 'create-field',
                        ),
                        'previous-date'         => array(
                            'label'   => esc_html__('Previous Start Date', 'outpaceseo'),
                            'type'    => 'datetime-local',
                            'class'   => 'wpsp-event-status-rescheduled',
                            'default' => 'custom-text',
                        ),
                        'online-location'       => array(
                            'label'   => esc_html__('Online Event URL', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'post_permalink',
                            'class'   => 'wpsp-event-status-online',

                        ),
                        'event-status'          => array(
                            'label'         => esc_html__(' Status', 'outpaceseo'),
                            'type'          => 'dropdown',
                            'default'       => 'custom-text',
                            'dropdown-type' => 'event-status',
                            'required'      => false,
                            'description'   => esc_html__('The status of the event. If you don\'t use this field, Google understands the eventStatus to be EventScheduled. ', 'outpaceseo'),

                        ),
                        'event-attendance-mode' => array(
                            'label'         => esc_html__(' Attendance Mode', 'outpaceseo'),
                            'type'          => 'dropdown',
                            'default'       => 'custom-text',
                            'dropdown-type' => 'event-attendance-mode',
                            'required'      => false,
                            'description'   => esc_html__('The location of the event. There are different requirements depending on if the event is happening online or at a physical location.', 'outpaceseo'),

                        ),
                        'location'              => array(
                            'label'   => esc_html__('Event Location Name', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'create-field',
                            'class'   => 'wpsp-event-status-offline',
                        ),
                        'location-street'       => array(
                            'label'    => esc_html__('Event Street Address', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'create-field',
                            'required' => true,
                            'class'    => 'wpsp-event-status-offline',
                        ),
                        'location-locality'     => array(
                            'label'    => esc_html__('Event Locality', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'create-field',
                            'required' => true,
                            'class'    => 'wpsp-event-status-offline',
                        ),
                        'location-postal'       => array(
                            'label'    => esc_html__('Event Postal Code', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'create-field',
                            'required' => true,
                            'class'    => 'wpsp-event-status-offline',
                        ),
                        'location-region'       => array(
                            'label'    => esc_html__('Event Region', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'create-field',
                            'required' => true,
                            'class'    => 'wpsp-event-status-offline',
                        ),
                        'location-country'      => array(
                            'label'         => esc_html__('Event Country', 'outpaceseo'),
                            'type'          => 'dropdown',
                            'default'       => 'create-field',
                            'required'      => true,
                            'dropdown-type' => 'country',
                            'class'         => 'wpsp-event-status-offline',
                        ),
                        'avail'                 => array(
                            'label'         => esc_html__('Event Offer Availability', 'outpaceseo'),
                            'type'          => 'dropdown',
                            'default'       => 'create-field',
                            'dropdown-type' => 'availability',
                        ),
                        'price'                 => array(
                            'label'   => esc_html__('Event Offer Price', 'outpaceseo'),
                            'type'    => 'number',
                            'default' => 'create-field',
                            'attrs'   => array(
                                'min'  => '0',
                                'step' => 'any',
                            ),
                        ),
                        'currency'              => array(
                            'label'         => esc_html__('Event Currency', 'outpaceseo'),
                            'type'          => 'dropdown',
                            'default'       => 'create-field',
                            'dropdown-type' => 'currency',
                        ),
                        'valid-from'            => array(
                            'label'   => esc_html__('Event Offer Valid From', 'outpaceseo'),
                            'type'    => 'date',
                            'default' => 'create-field',
                        ),
                        'ticket-buy-url'        => array(
                            'label'   => esc_html__('Event Ticket Link', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'create-field',
                        ),
                        'performer'             => array(
                            'label'   => esc_html__('Event Performer', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'create-field',
                        ),
                        'description'           => array(
                            'label'   => esc_html__('Event Description', 'outpaceseo'),
                            'type'    => 'textarea',
                            'default' => 'post_content',
                        ),
                        'event-organizer-name'  => array(
                            'label'         => esc_html__('Organizer Name', 'outpaceseo'),
                            'type'          => 'text',
                            'default'       => 'none',
                            'dropdown-type' => 'event-attendance-mode',
                            'required'      => false,
                            'description'   => esc_html__('The person or organization that is hosting the event.', 'outpaceseo'),

                        ),
                        'event-organizer-url'   => array(
                            'label'         => esc_html__('Organizer URL', 'outpaceseo'),
                            'type'          => 'text',
                            'default'       => 'none',
                            'dropdown-type' => 'event-attendance-mode',
                            'required'      => false,
                        ),
                    ),

                ),
                'outpaceseo-local-business'       => array(
                    'subkeys' => array(
                        'name'              => array(
                            'label'    => esc_html__('Local Business Name', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'blogname',
                            'required' => true,
                        ),
                        'image'             => array(
                            'label'    => esc_html__('Local Business Image', 'outpaceseo'),
                            'type'     => 'image',
                            'default'  => 'create-field',
                            'required' => true,
                        ),
                        'telephone'         => array(
                            'label'   => esc_html__('Local Business Telephone', 'outpaceseo'),
                            'type'    => 'tel',
                            'default' => 'create-field',
                        ),
                        'location-street'   => array(
                            'label'    => esc_html__('Local Business Street Address', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'create-field',
                            'required' => true,
                        ),
                        'location-locality' => array(
                            'label'    => esc_html__('Local Business Locality', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'create-field',
                            'required' => true,
                        ),
                        'location-postal'   => array(
                            'label'    => esc_html__('Local Business Postal Code', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'create-field',
                            'required' => true,
                        ),
                        'location-region'   => array(
                            'label'    => esc_html__('Local Business Region', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'create-field',
                            'required' => true,
                        ),
                        'location-country'  => array(
                            'label'         => esc_html__('Local Business Country', 'outpaceseo'),
                            'type'          => 'dropdown',
                            'default'       => 'create-field',
                            'dropdown-type' => 'country',
                        ),
                        'price-range'       => array(
                            'label'   => esc_html__('Local Business Price Range', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'create-field',
                        ),
                    ),
                ),
                'outpaceseo-product'              => array(
                    'subkeys' => array(
                        'name'              => array(
                            'label'    => esc_html__('Product Name', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'post_title',
                            'required' => true,
                        ),
                        'brand-name'        => array(
                            'label'   => esc_html__('Product Brand Name', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'create-field',
                        ),
                        'image'             => array(
                            'label'    => esc_html__(' Product Image', 'outpaceseo'),
                            'type'     => 'image',
                            'default'  => 'featured_img',
                            'required' => true,
                        ),
                        'url'               => array(
                            'label'   => esc_html__(' Product URL', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'post_permalink',
                        ),
                        'description'       => array(
                            'label'    => esc_html__('Product Description', 'outpaceseo'),
                            'type'     => 'textarea',
                            'default'  => 'post_content',
                            'required' => true,
                        ),
                        'sku'               => array(
                            'label'       => esc_html__('Product SKU', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'create-field',
                            'description' => esc_html__('The Stock Keeping Unit (SKU), a merchant-specific identifier for a product or service, or the product e.g. "0446310786"', 'outpaceseo'),
                        ),
                        'mpn'               => array(
                            'label'       => esc_html__('Product MPN', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'create-field',
                            'description' => esc_html__('The Manufacturer Part Number (MPN) of the product, or the product to which the offer refers. e.g. "925872"', 'outpaceseo'),
                        ),
                        'avail'             => array(
                            'label'         => esc_html__('Product Availability', 'outpaceseo'),
                            'type'          => 'dropdown',
                            'default'       => 'create-field',
                            'dropdown-type' => 'availability',
                        ),
                        'price-valid-until' => array(
                            'label'   => esc_html__('Product Price Valid Until', 'outpaceseo'),
                            'type'    => 'datetime-local',
                            'default' => 'create-field',
                        ),
                        'price'             => array(
                            'label'   => esc_html__('Product Price', 'outpaceseo'),
                            'type'    => 'number',
                            'default' => 'create-field',
                            'attrs'   => array(
                                'min'  => '0',
                                'step' => '0.01',
                            ),
                        ),
                        'currency'          => array(
                            'label'         => esc_html__('Product Currency', 'outpaceseo'),
                            'type'          => 'dropdown',
                            'default'       => 'create-field',
                            'dropdown-type' => 'currency',
                        ),
                        'rating'            => array(
                            'label'   => esc_html__('Product Rating', 'outpaceseo'),
                            'type'    => 'rating',
                            'default' => 'accept-user-rating',
                        ),
                        'review-count'      => array(
                            'label'       => esc_html__('Product Review Count', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'none',
                            'description' => esc_html__('The count of total number of reviews. e.g. "11"', 'outpaceseo'),
                        ),
                    ),
                ),
                'outpaceseo-recipe'               => array(
                    'subkeys' => array(
                        'name'                      => array(
                            'label'    => esc_html__('Recipe Name', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'create-field',
                            'required' => true,
                        ),
                        'image'                     => array(
                            'label'    => esc_html__('Recipe Photo', 'outpaceseo'),
                            'type'     => 'image',
                            'default'  => 'featured_img',
                            'required' => true,
                        ),
                        'description'               => array(
                            'label'   => esc_html__('Recipe Description', 'outpaceseo'),
                            'type'    => 'textarea',
                            'default' => 'post_content',
                        ),
                        'author'                    => array(
                            'label'   => esc_html__('Recipe Author Name', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'author_name',
                        ),
                        'preperation-time'          => array(
                            'label'   => esc_html__('Recipe Preparation Time', 'outpaceseo'),
                            'type'    => 'time-duration',
                            'default' => 'create-field',
                        ),
                        'cook-time'                 => array(
                            'label'   => esc_html__('Recipe Cook Time', 'outpaceseo'),
                            'type'    => 'time-duration',
                            'default' => 'create-field',
                        ),
                        'recipe-keywords'           => array(
                            'label'       => esc_html__('Recipe Keywords', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'create-field',
                            'description' => esc_html__('e.g. "winter apple pie", "nutmeg crust"', 'outpaceseo'),
                        ),
                        'recipe-category'           => array(
                            'label'       => esc_html__('Recipe Category', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'create-field',
                            'description' => esc_html__('e.g. "dinner", "entree", or "dessert"', 'outpaceseo'),
                        ),
                        'recipe-cuisine'            => array(
                            'label'       => esc_html__('Recipe Cuisine', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'create-field',
                            'description' => esc_html__('e.g. "French", "Indian", or "American"', 'outpaceseo'),
                        ),
                        'nutrition'                 => array(
                            'label'       => esc_html__('Recipe Calories', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'create-field',
                            'description' => esc_html__('The number of calories in the recipe.', 'outpaceseo'),
                        ),
                        'ingredients'               => array(
                            'label'       => esc_html__('Recipe Ingredients', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'create-field',
                            'description' => esc_html__('Ingredient used in the recipe. Separate multiple ingredients with comma(,).', 'outpaceseo'),
                        ),
                        'recipe-instructions'       => array(
                            'label'       => esc_html__('Recipe Instructions Step', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'create-field',
                            'description' => esc_html__('Recipe Instructions Steps used in the recipe. Separate multiple Instructions Steps with comma(,).', 'outpaceseo'),
                        ),
                        'video-name'                => array(
                            'label'    => esc_html__('Recipe Video Name', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'create-field',
                            'required' => true,
                        ),
                        'video-desc'                => array(
                            'label'    => esc_html__('Recipe Video Description', 'outpaceseo'),
                            'type'     => 'textarea',
                            'default'  => 'create-field',
                            'required' => true,
                        ),
                        'video-image'               => array(
                            'label'    => esc_html__('Recipe Video Thumbnail Url', 'outpaceseo'),
                            'type'     => 'image',
                            'default'  => 'create-field',
                            'required' => true,
                        ),
                        'recipe-video-content-url'  => array(
                            'label'   => esc_html__('Recipe Video Content URL', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'create-field',
                        ),
                        'recipe-video-embed-url'    => array(
                            'label'   => esc_html__('Recipe Video Embed URL', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'create-field',
                        ),
                        'recipe-video-duration'     => array(
                            'label'   => esc_html__('Recipe Video  Duration', 'outpaceseo'),
                            'type'    => 'time-duration',
                            'default' => 'create-field',
                        ),
                        'recipe-video-upload-date'  => array(
                            'label'    => esc_html__('Recipe Video Upload Date', 'outpaceseo'),
                            'type'     => 'date',
                            'default'  => 'post_date',
                            'required' => true,
                        ),
                        'recipe-video-expires-date' => array(
                            'label'   => esc_html__('Recipe Video Expires On', 'outpaceseo'),
                            'type'    => 'date',
                            'default' => 'create-field',
                        ),
                        'recipe-video-interaction-count' => array(
                            'label'   => esc_html__('Recipe Video Interaction Count', 'outpaceseo'),
                            'type'    => 'number',
                            'default' => 'create-field',
                        ),
                        'rating'                    => array(
                            'label'   => esc_html__('Recipe Video Rating', 'outpaceseo'),
                            'type'    => 'rating',
                            'default' => 'accept-user-rating',
                        ),
                        'review-count'              => array(
                            'label'       => esc_html__('Recipe Video Review Count', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'number',
                            'description' => esc_html__('The count of total number of reviews. e.g. "11"', 'outpaceseo'),
                        ),
                    ),
                ),
                'outpaceseo-software-application' => array(
                    'subkeys' => array(
                        'name'             => array(
                            'label'    => esc_html__('Software Application Name', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'post_title',
                            'required' => true,
                        ),
                        'operating-system' => array(
                            'label'       => esc_html__('Software Application Operating System', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'create-field',
                            'description' => esc_html__('Software for the operating system, for example, "Windows 7", "OSX 10.6", "Android 1.6"', 'outpaceseo'),
                        ),
                        'category'         => array(
                            'label'         => esc_html__('Software Application Category', 'outpaceseo'),
                            'type'          => 'dropdown',
                            'default'       => 'create-field',
                            'dropdown-type' => 'software-category',
                        ),
                        'rating'           => array(
                            'label'    => esc_html__('Software Application Rating', 'outpaceseo'),
                            'type'     => 'rating',
                            'required' => true,
                            'default'  => 'accept-user-rating',
                        ),
                        'review-count'     => array(
                            'label'       => esc_html__('Software Application Review Count', 'outpaceseo'),
                            'type'        => 'number',
                            'required'    => true,
                            'default'     => 'number',
                            'description' => esc_html__('The count of total number of reviews. e.g. "11"', 'outpaceseo'),
                        ),
                        'price'            => array(
                            'label'    => esc_html__('Software Application Price', 'outpaceseo'),
                            'type'     => 'number',
                            'default'  => 'create-field',
                            'required' => true,
                            'attrs'    => array(
                                'min'  => '0',
                                'step' => 'any',
                            ),
                        ),
                        'currency'         => array(
                            'label'         => esc_html__('Software Application Currency', 'outpaceseo'),
                            'type'          => 'dropdown',
                            'default'       => 'create-field',
                            'dropdown-type' => 'currency',
                        ),
                    ),
                ),
                'outpaceseo-movie'                => array(
                    'subkeys' => array(
                        'name'          => array(
                            'label'    => esc_html__('Movie Name', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'post_title',
                            'required' => true,
                        ),
                        'description'   => array(
                            'label'    => esc_html__('Movie Description', 'outpaceseo'),
                            'type'     => 'textarea',
                            'default'  => 'post_content',
                            'required' => true,
                        ),
                        'same-As'       => array(
                            'label'       => esc_html__('Movie SameAs', 'outpaceseo'),
                            'type'        => 'text',
                            'default'     => 'create-field',
                            'required'    => true,
                            'description' => esc_html__('A reference page that unambiguously indicates the item\'s identity; for example, the URL of the item\'s Wikipedia page, Freebase page, or official website.', 'outpaceseo'),
                        ),
                        'image'         => array(
                            'label'    => esc_html__('Movie Image', 'outpaceseo'),
                            'type'     => 'image',
                            'default'  => 'featured_img',
                            'required' => true,
                        ),
                        'dateCreated'   => array(
                            'label'   => esc_html__('Movie Date', 'outpaceseo'),
                            'type'    => 'datetime-local',
                            'default' => 'create-field',
                        ),
                        'director-name' => array(
                            'label'   => esc_html__('Movie Director Name', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'create-field',
                        ),
                    ),
                ),
                'outpaceseo-organization'         => array(
                    'subkeys' => array(
                        'name'              => array(
                            'label'    => esc_html__('Organization Name', 'outpaceseo'),
                            'type'     => 'text',
                            'default'  => 'blogname',
                            'required' => true,
                        ),
                        'location-street'   => array(
                            'label'   => esc_html__('Organization Street Address', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'create-field',
                        ),
                        'location-locality' => array(
                            'label'   => esc_html__('Organization Locality', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'create-field',
                        ),
                        'location-postal'   => array(
                            'label'   => esc_html__('Organization Postal Code', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'create-field',
                        ),
                        'location-region'   => array(
                            'label'   => esc_html__('Organization Region', 'outpaceseo'),
                            'type'    => 'text',
                            'default' => 'create-field',
                        ),
                        'location-country'  => array(
                            'label'         => esc_html__('Organization Country', 'outpaceseo'),
                            'type'          => 'dropdown',
                            'default'       => 'create-field',
                            'dropdown-type' => 'country',
                        ),
                    ),
                ),
            )
        );
    }

    /**
     * Get Meta list for Schema's meta field options.
     *
     * @param string $type Field type.
     */
    public static function get_meta_list($type)
    {

        if (empty(self::$post_metadata)) {

            self::$post_metadata = apply_filters(
                'outpaceseo_post_metadata',
                array(
                    'text'  => array(
                        array(
                            'label'     => __('Site Meta', 'outpaceseo'),
                            'meta-list' => array(
                                'blogname'        => __('Site Title', 'outpaceseo'),
                                'blogdescription' => __('Tagline', 'outpaceseo'),
                                'site_url'        => __('Site URL', 'outpaceseo'),
                            ),
                        ),
                        array(
                            'label'     => __('Post Meta (Basic Fields)', 'outpaceseo'),
                            'meta-list' => array(
                                'post_title'        => __('Title', 'outpaceseo'),
                                'post_content'      => __('Content', 'outpaceseo'),
                                'post_excerpt'      => __('Excerpt', 'outpaceseo'),
                                'post_permalink'    => __('Permalink', 'outpaceseo'),
                                'author_name'       => __('Author Name', 'outpaceseo'),
                                'author_first_name' => __('Author First Name', 'outpaceseo'),
                                'author_last_name'  => __('Author Last Name', 'outpaceseo'),
                                'author_url'        => __('Author URL', 'outpaceseo'),
                                'post_date'         => __('Publish Date', 'outpaceseo'),
                                'post_modified'     => __('Last Modify Date', 'outpaceseo'),
                            ),
                        ),
                        array(
                            'label'     => __('Add Custom Info', 'outpaceseo'),
                            'meta-list' => array(
                                'custom-text'  => __('Fixed Text', 'outpaceseo'),
                                'create-field' => __('New Custom Field', 'outpaceseo'),
                            ),
                        ),
                        array(
                            'label'     => __('All Other Custom Fields', 'outpaceseo'),
                            'meta-list' => array(
                                'specific-field' => __('Select Other Custom Fields Here', 'outpaceseo'),
                            ),
                        ),
                    ),
                    'image' => array(
                        array(
                            'label'     => __('Site Meta', 'outpaceseo'),
                            'meta-list' => array(
                                'site_logo' => __('Logo', 'outpaceseo'),
                            ),
                        ),
                        array(
                            'label'     => __('Post Meta (Basic Fields)', 'outpaceseo'),
                            'meta-list' => array(
                                'featured_img' => __('Featured Image', 'outpaceseo'),
                                'author_image' => __('Author Image', 'outpaceseo'),
                            ),
                        ),
                        array(
                            'label'     => __('Add Custom Info', 'outpaceseo'),
                            'meta-list' => array(
                                'custom-text'  => __('Fixed Image', 'outpaceseo'),
                                'fixed-text'   => __('Image URL', 'outpaceseo'),
                                'create-field' => __('New Custom Field', 'outpaceseo'),
                            ),
                        ),
                        array(
                            'label'     => __('All Other Custom Fields', 'outpaceseo'),
                            'meta-list' => array(
                                'specific-field' => __('Select Other Custom Fields Here', 'outpaceseo'),
                            ),
                        ),
                    ),
                )
            );
        }

        return self::$post_metadata[$type];
    }

    /**
     * Advanced Custom Fields compatibility.
     *
     * @param  array $fields Meta fields array.
     */
    public function acf_compatibility($fields)
    {

        if (function_exists('acf') && class_exists('acf')) {
            $post_type = 'acf';
            if ((defined('ACF_PRO') && ACF_PRO) || (defined('ACF') && ACF)) {
                $post_type = 'acf-field-group';
            }
            $text_acf_field  = array();
            $image_acf_field = array();
            $args            = array(
                'post_type'      => $post_type,
                'posts_per_page' => -1,
                'post_status'    => 'publish',
            );

            $the_query = new WP_Query($args);
            if ($the_query->have_posts()) :
                while ($the_query->have_posts()) :
                    $the_query->the_post();

                    $post_id = get_the_id();

                    // @codingStandardsIgnoreStart
                    $acf_fields = apply_filters('acf/field_group/get_fields', array(), $post_id); // WPCS: XSS OK.
                    // @codingStandardsIgnoreEnd

                    if ('acf-field-group' === $post_type) {
                        $acf_fields = acf_get_fields($post_id);
                    }

                    if (is_array($acf_fields) && !empty($acf_fields)) {
                        foreach ($acf_fields as $key => $value) {

                            if ('image' === $value['type']) {
                                $image_acf_field[$value['name']] = $value['label'];
                            } else {
                                $text_acf_field[$value['name']] = $value['label'];
                            }
                        }
                    }
                endwhile;
            endif;
            wp_reset_postdata();

            if (!empty($text_acf_field)) {
                $fields['text'][] = array(
                    'label'     => __('Advanced Custom Fields', 'outpaceseo'),
                    'meta-list' => $text_acf_field,
                );
            }

            if (!empty($image_acf_field)) {
                $fields['image'][] = array(
                    'label'     => __('Advanced Custom Fields', 'outpaceseo'),
                    'meta-list' => $image_acf_field,
                );
            }
        }

        return $fields;
    }

    /**
     * Init Metabox
     */
    public function init_metabox()
    {
        add_action('add_meta_boxes', array($this, 'setup_meta_box'));
        add_action('save_post', array($this, 'save_meta_box'));

        /**
         * Set metabox options
         *
         * @see http://php.net/manual/en/filter.filters.sanitize.php
         */
        self::$meta_option = apply_filters(
            'outpaceseo_schema_meta_box_options',
            array(
                'outpaceseo-schema-type'      => array(
                    'default'  => 'article',
                    'sanitize' => 'FILTER_DEFAULT',
                ),
                'outpaceseo-schema-location'  => array(
                    'default'  => array(
                        'rule' => array(
                            'basic-singulars',
                        ),
                    ),
                    'sanitize' => 'FILTER_DEFAULT',
                ),
                'outpaceseo-schema-exclusion' => array(
                    'default'  => array(),
                    'sanitize' => 'FILTER_DEFAULT',
                ),
            )
        );

        $schema_meta_keys = array();
        foreach (self::$schema_meta_fields as $key => $value) {
            self::$schema_meta_keys[]  = $key;
            self::$meta_option[$key] = array(
                'default'  => array(),
                'sanitize' => 'FILTER_DEFAULT',
            );
        }
    }

    /**
     *  Setup Metabox
     */
    public function setup_meta_box()
    {

        // Get all posts.
        $post_types = get_post_types();

        if ('outpaceseo_schema' === get_post_type()) {
            foreach ($post_types as $type) {

                if ('attachment' !== $type) {
                    add_meta_box(
                        'outpaceseo-schema-settings',
                        __('Schema Settings', 'outpaceseo'),
                        array($this, 'markup_meta_box'),
                        $type,
                        'normal',
                        'high'
                    );
                }
            }
        }
    }

    /**
     * Get metabox options
     */
    public static function get_meta_option()
    {
        return self::$meta_option;
    }

    /**
     * Metabox Markup
     *
     * @param  object $post Post object.
     */
    public function markup_meta_box($post)
    {

        wp_nonce_field(basename(__FILE__), 'outpaceseo_schema');
        $stored = get_post_meta($post->ID);

        $current_post_type = isset($stored['outpaceseo-schema-type']) ? $stored['outpaceseo-schema-type'] : '';
        $current_post_type = is_array($current_post_type) ? 'outpaceseo-' . reset($current_post_type) : '';

        if (empty($current_post_type)) {
            return;
        }

        $schema_meta_keys = array('outpaceseo-schema-location', 'outpaceseo-schema-exclusion', $current_post_type);

        foreach ($stored as $key => $value) {
            if (in_array($key, $schema_meta_keys, true)) {
                self::$meta_option[$key]['default'] = (isset($stored[$key][0])) ? maybe_unserialize($stored[$key][0]) : '';
            } else {
                self::$meta_option[$key]['default'] = (isset($stored[$key][0])) ? $stored[$key][0] : '';
            }
        }

        $meta = self::get_meta_option();


        $schema_type       = (isset($meta['outpaceseo-schema-type']['default'])) ? $meta['outpaceseo-schema-type']['default'] : '';
        $display_locations = (isset($meta['outpaceseo-schema-location']['default'])) ? $meta['outpaceseo-schema-location']['default'] : '';
        $exclude_locations = (isset($meta['outpaceseo-schema-exclusion']['default'])) ? $meta['outpaceseo-schema-exclusion']['default'] : '';

        $schemas_meta = array(
            'schema_type'       => $schema_type,
            'include-locations' => $display_locations,
            'exclude-locations' => $exclude_locations,
        );

        $schemas_meta[$current_post_type] = (isset($meta[$current_post_type]['default'])) ? $meta[$current_post_type]['default'] : array();

        do_action('aiosrs_schema_settings_markup_before', $meta);
        $this->render($schemas_meta);
        do_action('aiosrs_schema_settings_markup_after', $meta);
    }

    /**
     * Page Header Tabs
     *
     * @param  array $meta_values Post meta.
     */
    public function render($meta_values)
    {

        $allowd_fields = array_keys($meta_values);

?>
        <table class="outpaceseo-schema-table widefat">
            <tr class="outpaceseo-schema-row">
                <td class="outpaceseo-schema-row-heading">
                    <label><?php esc_html_e('Schema Type', 'outpaceseo'); ?></label>
                    <?php if (!isset($meta_values['schema_type']) || empty($meta_values['schema_type']) || !isset(self::$schema_meta_fields['outpaceseo-' . $meta_values['schema_type']])) { ?>
                        <i class="outpaceseo-schema-heading-help dashicons dashicons-editor-help" title="<?php echo esc_attr__('Select schema type.', 'outpaceseo'); ?>"></i>
                    <?php } ?>
                </td>
                <td class="outpaceseo-schema-row-content">
                    <div class="outpaceseo-schema-type-wrap">
                        <?php
                        if (isset($meta_values['schema_type']) && !empty($meta_values['schema_type']) && isset(self::$schema_meta_fields['outpaceseo-' . $meta_values['schema_type']])) {
                            $meta_key = $meta_values['schema_type'];
                            echo esc_html(self::$schema_meta_fields['outpaceseo-' . $meta_key]['label']);
                        ?>
                            <input type="hidden" id="outpaceseo-schema-type" name="outpaceseo-schema-type" class="outpaceseo-schema-type" value="<?php echo esc_attr($meta_key); ?>">
                        <?php } else { ?>
                            <select id="outpaceseo-schema-type" name="outpaceseo-schema-type" class="outpaceseo-schema-type">
                                <?php foreach (self::$schema_meta_fields as $key => $schema_field) { ?>
                                    <option <?php selected($schema_field['key'], $meta_values['schema_type']); ?> value="<?php echo esc_attr($schema_field['key']); ?>"><?php echo esc_html($schema_field['label']); ?></option>
                                <?php } ?>
                            </select>
                        <?php } ?>
                    </div>
                </td>
            </tr>
        </table>
        <div class="outpaceseo-schema-row-select-target">
            <h2 class="outpaceseo-schema-row-heading-select-target">
                <label><?php esc_html_e('Set Target Location', 'outpaceseo'); ?></label>
                <i class="outpaceseo-schema-heading-help dashicons dashicons-editor-help" title="<?php echo esc_attr__('Select location where this schema should be integrated.', 'outpaceseo'); ?>"></i>
            </h2>
        </div>
        <table class="outpaceseo-schema-table widefat">
            <tr class="outpaceseo-schema-row">
                <td class="outpaceseo-schema-row-heading">
                    <label><?php esc_html_e('Enable On', 'outpaceseo'); ?></label>
                    <i class="outpaceseo-schema-heading-help dashicons dashicons-editor-help" title="<?php echo esc_attr__('Add target locations where this Schema should appear.', 'outpaceseo'); ?>"></i>
                </td>
                <td class="outpaceseo-schema-row-content">
                    <?php
                    Outpaceseo_Target_Rule_Fields::target_rule_settings_field(
                        'outpaceseo-schema-location',
                        array(
                            'title'          => __('Display Rules', 'outpaceseo'),
                            'value'          => '[{"type":"basic-global","specific":null}]',
                            'tags'           => 'site,enable,target,pages',
                            'rule_type'      => 'display',
                            'add_rule_label' => __('Add “AND” Rule', 'outpaceseo'),
                        ),
                        $meta_values['include-locations']
                    );
                    ?>
                </td>
            </tr>
            <tr class="outpaceseo-schema-row">
                <td class="outpaceseo-schema-row-heading">
                    <label><?php esc_html_e('Exclude From', 'outpaceseo'); ?></label>
                    <i class="outpaceseo-schema-heading-help dashicons dashicons-editor-help" title="<?php echo esc_attr__('This Schema will not appear at these locations.', 'outpaceseo'); ?>"></i>
                </td>
                <td class="outpaceseo-schema-row-content">
                    <?php
                    Outpaceseo_Target_Rule_Fields::target_rule_settings_field(
                        'outpaceseo-schema-exclusion',
                        array(
                            'title'          => __('Exclude On', 'outpaceseo'),
                            'value'          => '[]',
                            'tags'           => 'site,enable,target,pages',
                            'add_rule_label' => __('Add “OR” Rule', 'outpaceseo'),
                            'rule_type'      => 'exclude',
                        ),
                        $meta_values['exclude-locations']
                    );
                    ?>
                </td>
            </tr>
        </table>
        <div class="outpaceseo-schema-row-select-target">
            <h2 class="outpaceseo-schema-row-heading-select-target">
                <label><?php esc_html_e('All Schema Fields', 'outpaceseo'); ?></label>
                <i class="outpaceseo-schema-heading-help dashicons dashicons-editor-help" title="<?php echo esc_attr__('Below are the fields/properties that Google Requires you to fill so that the schema will work properly.', 'outpaceseo'); ?>"></i>
            </h2>
        </div>
        <?php foreach (self::$schema_meta_fields as $key => $value) { ?>

            <?php
            if (!in_array($key, $allowd_fields, true)) {
                continue;
            }
            ?>

            <table id="op-<?php echo esc_attr($value['key']); ?>-schema-meta-wrap" class="outpaceseo-schema-table outpaceseo-schema-meta-wrap widefat" <?php echo ($value['key'] !== $meta_values['schema_type']) ? 'style="display: none;"' : ''; ?>>
                <?php if (isset($value['guideline-link']) && !empty($value['guideline-link'])) { ?>
                    <tr class="outpaceseo-schema-row">
                        <td class="outpaceseo-schema-row-heading">
                            <label><?php esc_html_e('Guidelines', 'outpaceseo'); ?></label>
                        </td>
                        <td class="outpaceseo-schema-row-content">
                            <a href="<?php echo esc_url($value['guideline-link']); ?>" class="outpaceseo-guideline-link" target="_blank" rel="noopener noreferrer">
                                <?php
                                printf(
                                    /* translators: %s Schema type */
                                    esc_html__('Read Guidelines for %s Schema', 'outpaceseo'),
                                    esc_attr($value['label'])
                                );
                                ?>
                                <i class="dashicons dashicons-external"></i>
                            </a>
                        </td>
                    </tr>
                <?php } ?>
                <?php
                foreach ($value['subkeys'] as $subkey => $subkey_data) {
                    self::get_meta_markup(
                        array(
                            'name'        => $key,
                            'subkey'      => $subkey,
                            'subkey_data' => $subkey_data,
                        ),
                        $meta_values
                    );
                }
                ?>
            </table>
        <?php } ?>

        <?php
    }

    /**
     * Get Meta field markup
     *
     * @param  array $option_meta Meta fields.
     * @param  array $meta_values Meta Values array.
     */
    public static function get_meta_markup($option_meta, $meta_values)
    {

        if (!empty($option_meta)) {

            $name   = $option_meta['name'];
            $subkey = $option_meta['subkey'];

            $is_item_type_render = isset($option_meta['item_type_class']) ? $option_meta['item_type_class'] : '';

            if (isset($option_meta['index'])) {
                $index                = $option_meta['index'];
                $name_subkey          = $option_meta['name_subkey'];
                $option_value         = (isset($meta_values[$name][$name_subkey][$index][$subkey])) ? $meta_values[$name][$name_subkey][$index][$subkey] : $option_meta['subkey_data']['default'];
                $custom_text_value    = (isset($meta_values[$name][$name_subkey][$index][$subkey . '-custom-text']) && self::is_not_empty($meta_values[$name][$name_subkey][$index][$subkey . '-custom-text'])) ? $meta_values[$name][$name_subkey][$index][$subkey . '-custom-text'] : '';
                $fixed_text_value     = (isset($meta_values[$name][$name_subkey][$index][$subkey . '-fixed-text']) && !empty($meta_values[$name][$name_subkey][$index][$subkey . '-fixed-text'])) ? $meta_values[$name][$name_subkey][$index][$subkey . '-fixed-text'] : '';
                $specific_field_value = (isset($meta_values[$name][$name_subkey][$index][$subkey . '-specific-field']) && !empty($meta_values[$name][$name_subkey][$index][$subkey . '-specific-field'])) ? $meta_values[$name][$name_subkey][$index][$subkey . '-specific-field'] : '';

                $name = $name . '[' . $name_subkey . '][' . $index . ']';
            } else {
                $option_value         = (isset($meta_values[$name][$subkey])) ? $meta_values[$name][$subkey] : (isset($option_meta['subkey_data']['default']) ? $option_meta['subkey_data']['default'] : '');
                $custom_text_value    = (isset($meta_values[$name][$subkey . '-custom-text']) && self::is_not_empty($meta_values[$name][$subkey . '-custom-text'])) ? $meta_values[$name][$subkey . '-custom-text'] : '';
                $fixed_text_value     = (isset($meta_values[$name][$subkey . '-fixed-text']) && !empty($meta_values[$name][$subkey . '-fixed-text'])) ? $meta_values[$name][$subkey . '-fixed-text'] : '';
                $specific_field_value = (isset($meta_values[$name][$subkey . '-specific-field']) && !empty($meta_values[$name][$subkey . '-specific-field'])) ? $meta_values[$name][$subkey . '-specific-field'] : '';
            }
            if ('event-status' === $subkey && empty($custom_text_value)) {
                $custom_text_value = 'EventScheduled';
            }
            if ('event-attendance-mode' === $subkey && empty($custom_text_value)) {
                $custom_text_value = 'OfflineEventAttendanceMode';
            }
            $required = (isset($option_meta['subkey_data']['required']) && true === $option_meta['subkey_data']['required']) ? true : false;

            $option_type = isset($option_meta['subkey_data']['type']) ? $option_meta['subkey_data']['type'] : 'text';

            $replace_name = str_replace(array('][', '-[', ']-', ']', '['), '-', $name . '-');
            $option_name  = $name . '[' . $subkey . ']';
            $option_id    = $replace_name . $subkey;
            $option_class = $replace_name . $subkey;

            $fixed_text_name  = $name . '[' . $subkey . '-fixed-text]';
            $fixed_text_id    = $replace_name . $subkey . '-custom-text';
            $fixed_text_class = $replace_name . $subkey . '-custom-text';

            $custom_meta_attrs = array(
                'name'          => $name . '[' . $subkey . '-custom-text]',
                'id'            => $replace_name . $subkey . '-custom-text',
                'class'         => $replace_name . $subkey . '-custom-text wpsp-' . $option_type . '-' . $subkey,
                'dropdown-type' => isset($option_meta['subkey_data']['dropdown-type']) ? $option_meta['subkey_data']['dropdown-type'] : '',
            );

            $specific_field_name = $name . '[' . $subkey . '-specific-field]';

            $attrs = '';
            if (isset($option_meta['subkey_data']['attrs']) && !empty($option_meta['subkey_data']['attrs'])) {
                foreach ($option_meta['subkey_data']['attrs'] as $key => $value) {
                    $attrs .= $key . '="' . esc_attr($value) . '" ';
                }
            }

            $dep_class = isset($option_meta['subkey_data']['class']) ? $option_meta['subkey_data']['class'] : '';

        ?>
            <tr class="<?php echo ('repeater-target' === $option_meta['subkey_data']['type']) ? 'op-hidden' : ''; ?> outpaceseo-schema-row outpaceseo-schema-row-<?php echo esc_attr($option_type); ?>-type <?php echo esc_html($is_item_type_render); ?>">
                <td class="outpaceseo-schema-row-heading <?php echo esc_attr($dep_class); ?>">
                    <label>
                        <?php echo esc_html($option_meta['subkey_data']['label']); ?>
                        <?php if ($required) { ?>
                            <span class="required">*</span>
                        <?php } ?>
                    </label>
                    <?php if (isset($option_meta['subkey_data']['description']) && !empty($option_meta['subkey_data']['description'])) { ?>
                        <i class="outpaceseo-schema-heading-help dashicons dashicons-editor-help" title="<?php echo esc_attr($option_meta['subkey_data']['description']); ?>"></i>
                    <?php } ?>
                </td>
                <td class="outpaceseo-schema-row-content <?php echo esc_attr($dep_class); ?>">
                    <div class="outpaceseo-schema-type-wrap">
                        <?php if ('repeater' === $option_meta['subkey_data']['type']) : ?>
                            <?php
                            if (is_array($option_value) && count($option_value) > 0) {
                                foreach ($option_value as $index => $option_subkey_value) {
                            ?>
                                    <div class="aiosrs-pro-repeater-table-wrap">
                                        <a href="#" class="op-repeater-close dashicons dashicons-no-alt"></a>
                                        <table class="aiosrs-pro-repeater-table" style="border-collapse: separate; border-spacing: 0 1em;">
                                            <?php
                                            foreach ($option_meta['subkey_data']['fields'] as $repeater_subkey => $repeater_subkey_data) {
                                                self::get_meta_markup(
                                                    array(
                                                        'name'        => $name,
                                                        'name_subkey' => $subkey,
                                                        'index'       => $index,
                                                        'subkey'      => $repeater_subkey,
                                                        'subkey_data' => $repeater_subkey_data,
                                                    ),
                                                    $meta_values
                                                );
                                            }
                                            ?>
                                        </table>
                                    </div>
                                <?php
                                }
                            } else {
                                ?>
                                <div class="aiosrs-pro-repeater-table-wrap">
                                    <a href="#" class="op-repeater-close dashicons dashicons-no-alt"></a>
                                    <table class="aiosrs-pro-repeater-table">
                                        <?php
                                        foreach ($option_meta['subkey_data']['fields'] as $repeater_subkey => $repeater_subkey_data) {
                                            self::get_meta_markup(
                                                array(
                                                    'name' => $name,
                                                    'name_subkey' => $subkey,
                                                    'index' => 0,
                                                    'subkey' => $repeater_subkey,
                                                    'subkey_data' => $repeater_subkey_data,
                                                ),
                                                $meta_values
                                            );
                                        }
                                        ?>
                                    </table>
                                </div>
                            <?php } ?>
                            <button type="button" class="op-repeater-add-new-btn button">+ Add</button>
                        <?php elseif ('repeater-target' === $option_meta['subkey_data']['type']) : ?>

                            <div class="aiosrs-pro-repeater-table-wrap">
                                <table class="aiosrs-pro-repeater-table">
                                    <?php
                                    foreach ($option_meta['subkey_data']['fields'] as $repeater_subkey => $repeater_subkey_data) {
                                        self::get_meta_markup(
                                            array(
                                                'name' => $name,
                                                'name_subkey' => $subkey,
                                                'index' => 0,
                                                'subkey' => $repeater_subkey,
                                                'subkey_data' => $repeater_subkey_data,
                                            ),
                                            $meta_values
                                        );
                                    }
                                    ?>
                                </table>
                            </div>

                        <?php else : ?>
                            <?php

                            $temp_option_meta                 = $option_meta;
                            $temp_option_meta['option_id']    = $option_id;
                            $temp_option_meta['option_name']  = $option_name;
                            $temp_option_meta['option_class'] = $option_class;

                            self::render_meta_box_dropdown($temp_option_meta, $option_value);

                            ?>
                            <div class="outpaceseo-schema-specific-field-wrap <?php echo ('specific-field' !== $option_value) ? 'op-hidden' : ''; ?>">
                                <select id="<?php echo esc_attr($specific_field_name); ?>" name="<?php echo esc_attr($specific_field_name); ?>" class="outpaceseo-schema-select2 outpaceseo-schema-specific-field">
                                    <?php if ($specific_field_value) { ?>
                                        <option value="<?php echo esc_attr($specific_field_value); ?>" selected="selected"><?php echo esc_html(preg_replace('/^_/', '', esc_html(str_replace('_', ' ', $specific_field_value)))); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="outpaceseo-schema-custom-text-wrap <?php echo ('custom-text' !== $option_value && 'fixed-text' !== $option_value) ? 'op-hidden' : ''; ?>">
                                <?php self::get_custom_field_default($option_type, $custom_text_value, $custom_meta_attrs, $attrs); ?>
                            </div>
                            <?php if ('image' === $option_type) { ?>
                                <div class="outpaceseo-schema-fixed-text-wrap <?php echo ('fixed-text' !== $option_value) ? 'op-hidden' : ''; ?>">
                                    <input type="text" id="<?php echo esc_attr($fixed_text_id); ?>" name="<?php echo esc_attr($fixed_text_name); ?>" class="<?php echo esc_attr($fixed_text_class); ?>" value="<?php echo esc_attr($fixed_text_value); ?>" <?php echo esc_attr($attrs); ?>>
                                </div>
                            <?php } ?>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php
        }
    }

    /**
     * Render meta box dropdown.
     *
     * @param array  $option_meta option meta.
     * @param string $option_value option value.
     * @param bool   $connected is connected.
     */
    public static function render_meta_box_dropdown($option_meta, $option_value = '', $connected = false)
    {

        $name   = isset($option_meta['name']) ? $option_meta['name'] : '';
        $subkey = isset($option_meta['subkey']) ? $option_meta['subkey'] : '';

        $option_id    = $connected ? $name : $option_meta['option_id'];
        $option_class = $connected ? '' : $option_meta['option_class'];
        $option_name  = $connected ? $name : $option_meta['option_name'];

        if ($connected) {
            $option_type = isset($option_meta['type']) ? $option_meta['type'] : 'text';
        } else {
            $option_type = isset($option_meta['subkey_data']['type']) ? $option_meta['subkey_data']['type'] : 'text';
        }

        $get_meta_type = ('image' === $option_type) ? 'image' : 'text';

        $attr = isset($option_meta['attr']) ? $option_meta['attr'] : '';

        ?>
        <select <?php echo esc_attr($attr); ?> id="<?php echo esc_attr($option_id); ?>" name="<?php echo esc_attr($option_name); ?>" class="<?php echo esc_attr($option_class); ?> outpaceseo-schema-meta-field">

            <?php if (isset($option_meta['subkey_data']['choices']) && is_array($option_meta['subkey_data']['choices']) && !empty($option_meta['subkey_data']['choices'])) : ?>
                <?php foreach ($option_meta['subkey_data']['choices'] as $key => $value) : ?>
                    <option <?php selected($key, $option_value); ?> value="<?php echo esc_attr($key); ?>"><?php echo esc_html($value); ?></option>
                <?php endforeach; ?>
            <?php else : ?>
                <option value='none'><?php printf('&mdash; %s &mdash;', esc_html__('None', 'outpaceseo')); ?></option>

                <?php $post_metadata = apply_filters('outpaceseo_meta_options', self::get_meta_list($get_meta_type), $name, $subkey); ?>
                <?php if (is_array($post_metadata) && !empty($post_metadata)) : ?>
                    <?php
                    foreach ($post_metadata as $post_meta) :
                    ?>
                        <optgroup label="<?php echo esc_attr($post_meta['label']); ?>">
                            <?php if (is_array($post_meta['meta-list']) && !empty($post_meta['meta-list'])) : ?>
                                <?php foreach ($post_meta['meta-list'] as $key => $value) : ?>
                                    <?php
                                    if ($connected && ('custom-text' === $key || 'fixed-text' === $key)) {
                                        continue;
                                    }
                                    ?>
                                    <?php $value = apply_filters('outpaceseo_mapping_option_string_' . $key, $value, $option_type); ?>
                                    <option <?php selected($key, $option_value); ?> value="<?php echo esc_attr($key); ?>"><?php echo esc_html($value); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </optgroup>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
        </select>

        <?php
    }

    /**
     * Get Custom field default value.
     *
     * @param string $type Custom Field type.
     * @param string $default_value Custom Field value.
     * @param array  $attrs Field attrubutes.
     * @param array  $field_attrs Field attrubutes in string.
     */
    public static function get_custom_field_default($type = 'text', $default_value = '', $attrs = array(), $field_attrs = '')
    {

        switch ($type) {
            case 'text':
            case 'number':
        ?>
                <input type="<?php echo esc_attr($type); ?>" id="<?php echo isset($attrs['id']) ? esc_attr($attrs['id']) : ''; ?>" class="<?php echo isset($attrs['class']) ? esc_attr($attrs['class']) : ''; ?>" name="<?php echo isset($attrs['name']) ? esc_attr($attrs['name']) : ''; ?>" value="<?php echo isset($default_value) ? esc_attr($default_value) : ''; ?>" step="any" <?php echo esc_attr($field_attrs); ?>>
            <?php
                break;
            case 'tel':
            case 'time':
            ?>
                <input type="<?php echo esc_attr($type); ?>" id="<?php echo isset($attrs['id']) ? esc_attr($attrs['id']) : ''; ?>" class="<?php echo isset($attrs['class']) ? esc_attr($attrs['class']) : ''; ?>" name="<?php echo isset($attrs['name']) ? esc_attr($attrs['name']) : ''; ?>" value="<?php echo isset($default_value) ? esc_attr($default_value) : ''; ?>" <?php echo esc_attr($field_attrs); ?> />
            <?php
                break;

            case 'datetime-local':
            ?>
                <input type="text" id="<?php echo isset($attrs['id']) ? esc_attr($attrs['id']) : ''; ?>" readonly class="wpsp-datetime-local-field <?php echo isset($attrs['class']) ? esc_attr($attrs['class']) : ''; ?>" name="<?php echo isset($attrs['name']) ? esc_attr($attrs['name']) : ''; ?>" value="<?php echo isset($default_value) ? esc_attr($default_value) : ''; ?>" <?php echo esc_attr($field_attrs); ?> />
            <?php
                break;

            case 'date':
            ?>
                <input type="text" id="<?php echo isset($attrs['id']) ? esc_attr($attrs['id']) : ''; ?>" readonly class="wpsp-date-field <?php echo isset($attrs['class']) ? esc_attr($attrs['class']) : ''; ?>" name="<?php echo isset($attrs['name']) ? esc_attr($attrs['name']) : ''; ?>" value="<?php echo isset($default_value) ? esc_attr($default_value) : ''; ?>" <?php echo esc_attr($field_attrs); ?> />
            <?php
                break;

            case 'textarea':
            ?>

                <textarea id="<?php echo isset($attrs['id']) ? esc_attr($attrs['id']) : ''; ?>" class="op-textarea-field <?php echo isset($attrs['class']) ? esc_attr($attrs['class']) : ''; ?>" name="<?php echo isset($attrs['name']) ? esc_attr($attrs['name']) : ''; ?>" <?php echo esc_attr($field_attrs); ?>><?php echo isset($default_value) ? esc_attr($default_value) : ''; ?></textarea>
            <?php
                break;


            case 'image':
                if (!empty($default_value)) {
                    $image_url = wp_get_attachment_url($default_value);
                }
            ?>
                <div class="aiosrs-pro-custom-field-single-image">
                    <input type="hidden" id="<?php echo isset($attrs['id']) ? esc_attr($attrs['id']) : ''; ?>" class="single-image-field <?php echo isset($attrs['class']) ? esc_attr($attrs['class']) : ''; ?>" name="<?php echo isset($attrs['name']) ? esc_attr($attrs['name']) : ''; ?>" value="<?php echo isset($default_value) ? esc_attr($default_value) : ''; ?>" <?php echo esc_attr($field_attrs); ?>>
                    <div class="image-field-wrap <?php echo (isset($image_url) && !empty($image_url)) ? 'op-custom-image-selected' : ''; ?>">
                        <a href="#" class="aiosrs-image-select button"><span class="dashicons dashicons-format-image"></span><?php esc_html_e('Select Image', 'outpaceseo'); ?></a>
                        <a href="#" class="aiosrs-image-remove dashicons dashicons-no-alt wp-ui-text-highlight"></a>
                        <?php if (isset($image_url) && !empty($image_url)) : ?>
                            <a href="#" class="aiosrs-image-select img"><img src="<?php echo esc_url($image_url); ?>" alt="" ; /></a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php
                break;

            case 'multi-select':
                $selected_options = array();
                $option_list      = self::get_dropdown_options($attrs['dropdown-type']);
                $option_list      = array_filter($option_list);
                if (!empty($default_value)) {
                    $selected_options = explode(',', $default_value);
                }
            ?>
                <div class="multi-select-wrap">
                    <input type="hidden" id="<?php echo isset($attrs['id']) ? esc_attr($attrs['id']) : ''; ?>" class="<?php echo isset($attrs['class']) ? esc_attr($attrs['class']) : ''; ?>" name="<?php echo isset($attrs['name']) ? esc_attr($attrs['name']) : ''; ?>" value="<?php echo isset($default_value) ? esc_attr($default_value) : ''; ?>" <?php echo esc_attr($field_attrs); ?>>
                    <select multiple="true">
                        <?php
                        if (!empty($option_list)) {
                            foreach ($option_list as $key => $value) {
                                $value = explode(':', trim($value));
                                $key   = $value[0];
                                $text  = isset($value[1]) ? $value[1] : $value[0];
                        ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php in_array($key, $selected_options, true) ? selected(1) : ''; ?>><?php echo esc_attr($text); ?></option>
                        <?php
                            }
                        }
                        ?>
                    </select>
                </div>
            <?php
                break;

            case 'dropdown':
                $option_list = self::get_dropdown_options($attrs['dropdown-type']);
                $option_list = array_filter($option_list);
            ?>
                <select id="<?php echo isset($attrs['id']) ? esc_attr($attrs['id']) : ''; ?>" class="<?php echo isset($attrs['class']) ? esc_attr($attrs['class']) : ''; ?>" name="<?php echo isset($attrs['name']) ? esc_attr($attrs['name']) : ''; ?>">
                    <?php
                    if (!empty($option_list)) {
                        foreach ($option_list as $key => $value) {
                    ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($default_value, $key); ?>><?php echo esc_attr($value); ?></option>
                    <?php
                        }
                    }
                    ?>
                </select>
            <?php
                break;

            case 'time-duration':
            ?>
                <div class="aiosrs-pro-custom-field-time-duration">
                    <input type="hidden" id="<?php echo isset($attrs['id']) ? esc_attr($attrs['id']) : ''; ?>" class="time-duration-field <?php echo isset($attrs['class']) ? esc_attr($attrs['class']) : ''; ?>" name="<?php echo isset($attrs['name']) ? esc_attr($attrs['name']) : ''; ?>" value="<?php echo isset($default_value) ? esc_attr($default_value) : ''; ?>" <?php echo esc_attr($field_attrs); ?>>
                    <div class="time-duration-wrap">
                        <input type="text" readonly class="wpsp-time-duration-field" value="<?php echo esc_attr(self::get_time_duration($default_value)); ?>">
                    </div>
                </div>
            <?php
                break;

            default:
            ?>
                <input type="text" id="<?php echo isset($attrs['id']) ? esc_attr($attrs['id']) : ''; ?>" class="<?php echo isset($attrs['class']) ? esc_attr($attrs['class']) : ''; ?>" name="<?php echo isset($attrs['name']) ? esc_attr($attrs['name']) : ''; ?>" value="<?php echo isset($default_value) ? esc_attr($default_value) : ''; ?>" <?php echo esc_attr($field_attrs); ?>>
<?php
                break;
        }
    }


    /**
     * Get the value of time duration from ISO 8601 format.
     *
     * @param string $option_default time format string.
     * @return string
     */
    public static function get_time_duration($option_default)
    {

        $option_default = trim($option_default);
        if (!empty($option_default)) {
            if (strpos($option_default, ':') !== false) {
                $option_default = preg_replace('/:/', 'H', $option_default, 1);
                $option_default = preg_replace('/:/', 'M', $option_default, 1);
                $option_default = 'PT' . $option_default . 'S';
            }
            try {
                $interval = new DateInterval($option_default);
            } catch (Exception  $e) {
                unset($interval);
            }
        }

        $duration_day  = isset($interval) ? $interval->format('%d') : 0;
        $duration_hour = isset($interval) ? $interval->format('%h') : 0;
        $duration_min  = isset($interval) ? $interval->format('%i') : 0;
        $duration_sec  = isset($interval) ? $interval->format('%s') : 0;
        $duration_hour = $duration_hour + ($duration_day * 24);

        return str_pad($duration_hour, 2, '0', STR_PAD_LEFT)
            . ':' . str_pad($duration_min, 2, '0', STR_PAD_LEFT)
            . ':' . str_pad($duration_sec, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Get Dropdown options.
     *
     * @param  string $name Field Name.
     */
    public static function get_dropdown_options($name = '')
    {

        switch ($name) {
            case 'availability':
                $return = apply_filters(
                    'outpaceseo_availability_options',
                    array(
                        'Discontinued'        => __('Discontinued', 'outpaceseo'),
                        'InStock'             => __('In Stock', 'outpaceseo'),
                        'InStoreOnly'         => __('In Store Only', 'outpaceseo'),
                        'LimitedAvailability' => __('Limited Availability', 'outpaceseo'),
                        'OnlineOnly'          => __('Online Only', 'outpaceseo'),
                        'OutOfStock'          => __('Out Of Stock', 'outpaceseo'),
                        'PreOrder'            => __('Pre Order', 'outpaceseo'),
                        'PreSale'             => __('Pre Sale', 'outpaceseo'),
                        'SoldOut'             => __('Sold Out', 'outpaceseo'),
                        'BackOrder'           => __('Back Order', 'outpaceseo'),

                    )
                );
                break;

            case 'book-format':
                $return = apply_filters(
                    'outpaceseo_book_format_options',
                    array(
                        'EBook'     => __('EBook', 'outpaceseo'),
                        'Hardcover' => __('Hardcover', 'outpaceseo'),
                        'Paperback' => __('Paperback', 'outpaceseo'),
                        'AudioBook' => __('AudioBook', 'outpaceseo'),
                    )
                );
                break;
            case 'event-status':
                $return = apply_filters(
                    'outpaceseo_event_status_options',
                    array(
                        'EventScheduled'   => __('Scheduled', 'outpaceseo'),
                        'EventRescheduled' => __('Rescheduled', 'outpaceseo'),
                        'EventPostponed'   => __('Postponed', 'outpaceseo'),
                        'EventMovedOnline' => __('Moved Online', 'outpaceseo'),
                        'EventCancelled'   => __('Cancelled', 'outpaceseo'),
                    )
                );
                break;
            case 'event-attendance-mode':
                $return = apply_filters(
                    'outpaceseo_event_attendance_mode_options',
                    array(
                        'OfflineEventAttendanceMode' => __('Physical Location', 'outpaceseo'),
                        'OnlineEventAttendanceMode'  => __('Online Event', 'outpaceseo'),
                        'MixedEventAttendanceMode'   => __('Mix Of Online & Physical Locations', 'outpaceseo'),

                    )
                );
                break;
            case 'action-platform':
                $return = apply_filters(
                    'outpaceseo_action_platform_options',
                    array(
                        'DesktopWebPlatform' => __('DesktopWebPlatform', 'outpaceseo'),
                        'MobileWebPlatform'  => __('MobileWebPlatform', 'outpaceseo'),
                        'AndroidPlatform'    => __('AndroidPlatform', 'outpaceseo'),
                        'IOSPlatform'        => __('IOSPlatform', 'outpaceseo'),
                    )
                );
                break;
            case 'days':
                $return = apply_filters(
                    'outpaceseo_days_options',
                    array(
                        'Monday'    => __('Monday', 'outpaceseo'),
                        'Tuesday'   => __('Tuesday', 'outpaceseo'),
                        'Wednesday' => __('Wednesday', 'outpaceseo'),
                        'Thursday'  => __('Thursday', 'outpaceseo'),
                        'Friday'    => __('Friday', 'outpaceseo'),
                        'Saturday'  => __('Saturday', 'outpaceseo'),
                        'Sunday'    => __('Sunday', 'outpaceseo'),
                    )
                );
                break;
            case 'country':
                $return = apply_filters(
                    'outpaceseo_country_options',
                    array(
                        'AF' => __('Afghanistan', 'outpaceseo'),
                        'AX' => __('Åland Islands', 'outpaceseo'),
                        'AL' => __('Albania', 'outpaceseo'),
                        'DZ' => __('Algeria', 'outpaceseo'),
                        'AS' => __('American Samoa', 'outpaceseo'),
                        'AD' => __('Andorra', 'outpaceseo'),
                        'AO' => __('Angola', 'outpaceseo'),
                        'AI' => __('Anguilla', 'outpaceseo'),
                        'AQ' => __('Antarctica', 'outpaceseo'),
                        'AG' => __('Antigua and Barbuda', 'outpaceseo'),
                        'AR' => __('Argentina', 'outpaceseo'),
                        'AM' => __('Armenia', 'outpaceseo'),
                        'AW' => __('Aruba', 'outpaceseo'),
                        'AU' => __('Australia', 'outpaceseo'),
                        'AT' => __('Austria', 'outpaceseo'),
                        'AZ' => __('Azerbaijan', 'outpaceseo'),
                        'BH' => __('Bahrain', 'outpaceseo'),
                        'BS' => __('Bahamas', 'outpaceseo'),
                        'BD' => __('Bangladesh', 'outpaceseo'),
                        'BB' => __('Barbados', 'outpaceseo'),
                        'BY' => __('Belarus', 'outpaceseo'),
                        'BE' => __('Belgium', 'outpaceseo'),
                        'BZ' => __('Belize', 'outpaceseo'),
                        'BJ' => __('Benin', 'outpaceseo'),
                        'BM' => __('Bermuda', 'outpaceseo'),
                        'BT' => __('Bhutan', 'outpaceseo'),
                        'BQ' => __('Bonaire, Sint Eustatius and Saba', 'outpaceseo'),
                        'BA' => __('Bosnia and Herzegovina', 'outpaceseo'),
                        'BW' => __('Botswana', 'outpaceseo'),
                        'BV' => __('Bouvet Island', 'outpaceseo'),
                        'BR' => __('Brazil', 'outpaceseo'),
                        'IO' => __('British Indian Ocean Territory', 'outpaceseo'),
                        'BN' => __('Brunei Darussalam', 'outpaceseo'),
                        'BG' => __('Bulgaria', 'outpaceseo'),
                        'BF' => __('Burkina Faso', 'outpaceseo'),
                        'BI' => __('Burundi', 'outpaceseo'),
                        'KH' => __('Cambodia', 'outpaceseo'),
                        'CM' => __('Cameroon', 'outpaceseo'),
                        'CA' => __('Canada', 'outpaceseo'),
                        'CV' => __('Cape Verde', 'outpaceseo'),
                        'KY' => __('Cayman Islands', 'outpaceseo'),
                        'CF' => __('Central African Republic', 'outpaceseo'),
                        'TD' => __('Chad', 'outpaceseo'),
                        'CL' => __('Chile', 'outpaceseo'),
                        'CN' => __('China', 'outpaceseo'),
                        'CX' => __('Christmas Island', 'outpaceseo'),
                        'CC' => __('Cocos (Keeling) Islands', 'outpaceseo'),
                        'CO' => __('Colombia', 'outpaceseo'),
                        'KM' => __('Comoros', 'outpaceseo'),
                        'CG' => __('Congo', 'outpaceseo'),
                        'CD' => __('Congo, the Democratic Republic of the', 'outpaceseo'),
                        'CK' => __('Cook Islands', 'outpaceseo'),
                        'CR' => __('Costa Rica', 'outpaceseo'),
                        'CI' => __('Ivory Coast', 'outpaceseo'),
                        'HR' => __('Croatia', 'outpaceseo'),
                        'CU' => __('Cuba', 'outpaceseo'),
                        'CW' => __('Curaçao', 'outpaceseo'),
                        'CY' => __('Cyprus', 'outpaceseo'),
                        'CZ' => __('Czech Republic', 'outpaceseo'),
                        'DK' => __('Denmark', 'outpaceseo'),
                        'DJ' => __('Djibouti', 'outpaceseo'),
                        'DM' => __('Dominica', 'outpaceseo'),
                        'DO' => __('Dominican Republic', 'outpaceseo'),
                        'EC' => __('Ecuador', 'outpaceseo'),
                        'EG' => __('Egypt', 'outpaceseo'),
                        'SV' => __('El Salvador', 'outpaceseo'),
                        'GQ' => __('Equatorial Guinea', 'outpaceseo'),
                        'ER' => __('Eritrea', 'outpaceseo'),
                        'EE' => __('Estonia', 'outpaceseo'),
                        'ET' => __('Ethiopia', 'outpaceseo'),
                        'FK' => __('Falkland Islands (Malvinas)', 'outpaceseo'),
                        'FO' => __('Faroe Islands', 'outpaceseo'),
                        'FM' => __('Federated States of Micronesia', 'outpaceseo'),
                        'FJ' => __('Fiji', 'outpaceseo'),
                        'FI' => __('Finland', 'outpaceseo'),
                        'FR' => __('France', 'outpaceseo'),
                        'GF' => __('French Guiana', 'outpaceseo'),
                        'PF' => __('French Polynesia', 'outpaceseo'),
                        'TF' => __('French Southern Territories', 'outpaceseo'),
                        'GA' => __('Gabon', 'outpaceseo'),
                        'GM' => __('Gambia', 'outpaceseo'),
                        'GE' => __('Georgia', 'outpaceseo'),
                        'DE' => __('Germany', 'outpaceseo'),
                        'GH' => __('Ghana', 'outpaceseo'),
                        'GI' => __('Gibraltar', 'outpaceseo'),
                        'GR' => __('Greece', 'outpaceseo'),
                        'GL' => __('Greenland', 'outpaceseo'),
                        'GD' => __('Grenada', 'outpaceseo'),
                        'GP' => __('Guadeloupe', 'outpaceseo'),
                        'GU' => __('Guam', 'outpaceseo'),
                        'GT' => __('Guatemala', 'outpaceseo'),
                        'GG' => __('Guernsey', 'outpaceseo'),
                        'GN' => __('Guinea', 'outpaceseo'),
                        'GW' => __('Guinea-Bissau', 'outpaceseo'),
                        'GY' => __('Guyana', 'outpaceseo'),
                        'HT' => __('Haiti', 'outpaceseo'),
                        'HM' => __('Heard Island and McDonald Islands', 'outpaceseo'),
                        'VA' => __('Holy See (Vatican City State)', 'outpaceseo'),
                        'HN' => __('Honduras', 'outpaceseo'),
                        'HK' => __('Hong Kong', 'outpaceseo'),
                        'HU' => __('Hungary', 'outpaceseo'),
                        'IS' => __('Iceland', 'outpaceseo'),
                        'IN' => __('India', 'outpaceseo'),
                        'ID' => __('Indonesia', 'outpaceseo'),
                        'IR' => __('Iran, Islamic Republic of', 'outpaceseo'),
                        'IQ' => __('Iraq', 'outpaceseo'),
                        'IE' => __('Ireland', 'outpaceseo'),
                        'IM' => __('Isle of Man', 'outpaceseo'),
                        'IL' => __('Israel', 'outpaceseo'),
                        'IT' => __('Italy', 'outpaceseo'),
                        'JM' => __('Jamaica', 'outpaceseo'),
                        'JP' => __('Japan', 'outpaceseo'),
                        'JE' => __('Jersey', 'outpaceseo'),
                        'JO' => __('Jordan', 'outpaceseo'),
                        'KZ' => __('Kazakhstan', 'outpaceseo'),
                        'KE' => __('Kenya', 'outpaceseo'),
                        'KI' => __('Kiribati', 'outpaceseo'),
                        'KP' => __('Korea, Democratic People\'s Republic of', 'outpaceseo'),
                        'KR' => __('Korea, Republic of', 'outpaceseo'),
                        'KW' => __('Kuwait', 'outpaceseo'),
                        'KG' => __('Kyrgyzstan', 'outpaceseo'),
                        'LA' => __('Laos', 'outpaceseo'),
                        'LV' => __('Latvia', 'outpaceseo'),
                        'LB' => __('Lebanon', 'outpaceseo'),
                        'LS' => __('Lesotho', 'outpaceseo'),
                        'LR' => __('Liberia', 'outpaceseo'),
                        'LY' => __('Libya', 'outpaceseo'),
                        'LI' => __('Liechtenstein', 'outpaceseo'),
                        'LT' => __('Lithuania', 'outpaceseo'),
                        'LU' => __('Luxembourg', 'outpaceseo'),
                        'MO' => __('Macao', 'outpaceseo'),
                        'MG' => __('Madagascar', 'outpaceseo'),
                        'MW' => __('Malawi', 'outpaceseo'),
                        'MY' => __('Malaysia', 'outpaceseo'),
                        'MV' => __('Maldives', 'outpaceseo'),
                        'ML' => __('Mali', 'outpaceseo'),
                        'MT' => __('Malta', 'outpaceseo'),
                        'MH' => __('Marshall Islands', 'outpaceseo'),
                        'MQ' => __('Martinique', 'outpaceseo'),
                        'MR' => __('Mauritania', 'outpaceseo'),
                        'MU' => __('Mauritius', 'outpaceseo'),
                        'YT' => __('Mayotte', 'outpaceseo'),
                        'MX' => __('Mexico', 'outpaceseo'),
                        'MC' => __('Monaco', 'outpaceseo'),
                        'MN' => __('Mongolia', 'outpaceseo'),
                        'ME' => __('Montenegro', 'outpaceseo'),
                        'MS' => __('Montserrat', 'outpaceseo'),
                        'MA' => __('Morocco', 'outpaceseo'),
                        'MZ' => __('Mozambique', 'outpaceseo'),
                        'MM' => __('Myanmar', 'outpaceseo'),
                        'NA' => __('Namibia', 'outpaceseo'),
                        'NR' => __('Nauru', 'outpaceseo'),
                        'NP' => __('Nepal', 'outpaceseo'),
                        'NL' => __('Netherlands', 'outpaceseo'),
                        'NC' => __('New Caledonia', 'outpaceseo'),
                        'NZ' => __('New Zealand', 'outpaceseo'),
                        'NI' => __('Nicaragua', 'outpaceseo'),
                        'NE' => __('Niger', 'outpaceseo'),
                        'NG' => __('Nigeria', 'outpaceseo'),
                        'NU' => __('Niue', 'outpaceseo'),
                        'NF' => __('Norfolk Island', 'outpaceseo'),
                        'MP' => __('Northern Mariana Islands', 'outpaceseo'),
                        'NO' => __('Norway', 'outpaceseo'),
                        'OM' => __('Oman', 'outpaceseo'),
                        'PK' => __('Pakistan', 'outpaceseo'),
                        'PW' => __('Palau', 'outpaceseo'),
                        'PS' => __('Palestine, State of', 'outpaceseo'),
                        'PA' => __('Panama', 'outpaceseo'),
                        'PG' => __('Papua New Guinea', 'outpaceseo'),
                        'PY' => __('Paraguay', 'outpaceseo'),
                        'PE' => __('Peru', 'outpaceseo'),
                        'PH' => __('Philippines', 'outpaceseo'),
                        'PN' => __('Pitcairn', 'outpaceseo'),
                        'BO' => __('Plurinational State of Bolivia', 'outpaceseo'),
                        'PL' => __('Poland', 'outpaceseo'),
                        'PT' => __('Portugal', 'outpaceseo'),
                        'PR' => __('Puerto Rico', 'outpaceseo'),
                        'QA' => __('Qatar', 'outpaceseo'),
                        'RE' => __('Réunion', 'outpaceseo'),
                        'MK' => __('Republic of Macedonia', 'outpaceseo'),
                        'MD' => __('Republic of Moldova', 'outpaceseo'),
                        'RO' => __('Romania', 'outpaceseo'),
                        'RU' => __('Russian Federation', 'outpaceseo'),
                        'RW' => __('Rwanda', 'outpaceseo'),
                        'BL' => __('Saint Barthélemy', 'outpaceseo'),
                        'SH' => __('Saint Helena, Ascension and Tristan da Cunha', 'outpaceseo'),
                        'KN' => __('Saint Kitts and Nevis', 'outpaceseo'),
                        'LC' => __('Saint Lucia', 'outpaceseo'),
                        'MF' => __('Saint Martin (French part', 'outpaceseo'),
                        'PM' => __('Saint Pierre and Miquelon', 'outpaceseo'),
                        'VC' => __('Saint Vincent and the Grenadines', 'outpaceseo'),
                        'WS' => __('Samoa', 'outpaceseo'),
                        'SM' => __('San Marino', 'outpaceseo'),
                        'ST' => __('Sao Tome and Principe', 'outpaceseo'),
                        'SA' => __('Saudi Arabia', 'outpaceseo'),
                        'SN' => __('Senegal', 'outpaceseo'),
                        'RS' => __('Serbia', 'outpaceseo'),
                        'SC' => __('Seychelles', 'outpaceseo'),
                        'SL' => __('Sierra Leone', 'outpaceseo'),
                        'SG' => __('Singapore', 'outpaceseo'),
                        'SX' => __('Sint Maarten (Dutch part)', 'outpaceseo'),
                        'SK' => __('Slovakia', 'outpaceseo'),
                        'SI' => __('Slovenia', 'outpaceseo'),
                        'SB' => __('Solomon Islands', 'outpaceseo'),
                        'SO' => __('Somalia', 'outpaceseo'),
                        'ZA' => __('South Africa', 'outpaceseo'),
                        'GS' => __('South Georgia and the South Sandwich Islands', 'outpaceseo'),
                        'SS' => __('South Sudan', 'outpaceseo'),
                        'ES' => __('Spain', 'outpaceseo'),
                        'LK' => __('Sri Lanka', 'outpaceseo'),
                        'SD' => __('Sudan', 'outpaceseo'),
                        'SR' => __('Suriname', 'outpaceseo'),
                        'SJ' => __('Svalbard and Jan Mayen', 'outpaceseo'),
                        'SZ' => __('Swaziland', 'outpaceseo'),
                        'SE' => __('Sweden', 'outpaceseo'),
                        'CH' => __('Switzerland', 'outpaceseo'),
                        'SY' => __('Syrian Arab Republic', 'outpaceseo'),
                        'TW' => __('Taiwan, Province of China', 'outpaceseo'),
                        'TJ' => __('Tajikistan', 'outpaceseo'),
                        'TZ' => __('Tanzania, United Republic of', 'outpaceseo'),
                        'TH' => __('Thailand', 'outpaceseo'),
                        'TL' => __('Timor-Leste', 'outpaceseo'),
                        'TG' => __('Togo', 'outpaceseo'),
                        'TK' => __('Tokelau', 'outpaceseo'),
                        'TO' => __('Tonga', 'outpaceseo'),
                        'TT' => __('Trinidad and Tobago', 'outpaceseo'),
                        'TN' => __('Tunisia', 'outpaceseo'),
                        'TR' => __('Turkey', 'outpaceseo'),
                        'TM' => __('Turkmenistan', 'outpaceseo'),
                        'TC' => __('Turks and Caicos Islands', 'outpaceseo'),
                        'TV' => __('Tuvalu', 'outpaceseo'),
                        'UG' => __('Uganda', 'outpaceseo'),
                        'UA' => __('Ukraine', 'outpaceseo'),
                        'AE' => __('United Arab Emirates', 'outpaceseo'),
                        'GB' => __('United Kingdom', 'outpaceseo'),
                        'US' => __('United States', 'outpaceseo'),
                        'UM' => __('United States Minor Outlying Islands', 'outpaceseo'),
                        'UY' => __('Uruguay', 'outpaceseo'),
                        'UZ' => __('Uzbekistan', 'outpaceseo'),
                        'VU' => __('Vanuatu', 'outpaceseo'),
                        'VE' => __('Venezuela, Bolivarian Republic of', 'outpaceseo'),
                        'VN' => __('Viet Nam', 'outpaceseo'),
                        'VG' => __('Virgin Islands, British', 'outpaceseo'),
                        'VI' => __('Virgin Islands, U.S', 'outpaceseo'),
                        'WF' => __('Wallis and Futuna', 'outpaceseo'),
                        'EH' => __('Western Sahara', 'outpaceseo'),
                        'YE' => __('Yemen', 'outpaceseo'),
                        'ZM' => __('Zambia', 'outpaceseo'),
                        'ZW' => __('Zimbabwe', 'outpaceseo'),
                    )
                );
                break;
            case 'employment':
                $return = apply_filters(
                    'outpaceseo_employment_options',
                    array(
                        'FULL_TIME'  => __('FULL TIME', 'outpaceseo'),
                        'PART_TIME'  => __('PART TIME', 'outpaceseo'),
                        'CONTRACTOR' => __('CONTRACTOR', 'outpaceseo'),
                        'TEMPORARY'  => __('TEMPORARY', 'outpaceseo'),
                        'INTERN'     => __('INTERN', 'outpaceseo'),
                        'VOLUNTEER'  => __('VOLUNTEER', 'outpaceseo'),
                        'PER_DIEM'   => __('PER DIEM', 'outpaceseo'),
                        'OTHER'      => __('OTHER', 'outpaceseo'),
                    )
                );
                break;
            case 'currency':
                $return = apply_filters(
                    'outpaceseo_currency_options',
                    array(
                        'AFA' => __('Afghan Afghani', 'outpaceseo'),
                        'ALL' => __('Albanian Lek', 'outpaceseo'),
                        'DZD' => __('Algerian Dinar', 'outpaceseo'),
                        'AOA' => __('Angolan Kwanza', 'outpaceseo'),
                        'ARS' => __('Argentine Peso', 'outpaceseo'),
                        'AMD' => __('Armenian Dram', 'outpaceseo'),
                        'AWG' => __('Aruban Florin', 'outpaceseo'),
                        'AUD' => __('Australian Dollar', 'outpaceseo'),
                        'AZN' => __('Azerbaijani Manat', 'outpaceseo'),
                        'BSD' => __('Bahamian Dollar', 'outpaceseo'),
                        'BHD' => __('Bahraini Dinar', 'outpaceseo'),
                        'BDT' => __('Bangladeshi Taka', 'outpaceseo'),
                        'BBD' => __('Barbadian Dollar', 'outpaceseo'),
                        'BYR' => __('Belarusian Ruble', 'outpaceseo'),
                        'BEF' => __('Belgian Franc', 'outpaceseo'),
                        'BZD' => __('Belize Dollar', 'outpaceseo'),
                        'BMD' => __('Bermudan Dollar', 'outpaceseo'),
                        'BTN' => __('Bhutanese Ngultrum', 'outpaceseo'),
                        'BTC' => __('Bitcoin', 'outpaceseo'),
                        'BOB' => __('Bolivian Boliviano', 'outpaceseo'),
                        'BAM' => __('Bosnia-Herzegovina Convertible Mark', 'outpaceseo'),
                        'BWP' => __('Botswanan Pula', 'outpaceseo'),
                        'BRL' => __('Brazilian Real', 'outpaceseo'),
                        'GBP' => __('British Pound', 'outpaceseo'),
                        'BND' => __('Brunei Dollar', 'outpaceseo'),
                        'BGN' => __('Bulgarian Lev', 'outpaceseo'),
                        'BIF' => __('Burundian Franc', 'outpaceseo'),
                        'KHR' => __('Cambodian Riel', 'outpaceseo'),
                        'CAD' => __('Canadian Dollar', 'outpaceseo'),
                        'CVE' => __('Cape Verdean Escudo', 'outpaceseo'),
                        'KYD' => __('Cayman Islands Dollar', 'outpaceseo'),
                        'XAF' => __('Central African CFA Franc', 'outpaceseo'),
                        'XPF' => __('CFP Franc', 'outpaceseo'),
                        'CLP' => __('Chilean Peso', 'outpaceseo'),
                        'CNY' => __('Chinese Yuan', 'outpaceseo'),
                        'COP' => __('Colombian Peso', 'outpaceseo'),
                        'KMF' => __('Comorian Franc', 'outpaceseo'),
                        'CDF' => __('Congolese Franc', 'outpaceseo'),
                        'CRC' => __('Costa Rican Colón', 'outpaceseo'),
                        'HRK' => __('Croatian Kuna', 'outpaceseo'),
                        'CUC' => __('Cuban Convertible Peso', 'outpaceseo'),
                        'CZK' => __('Czech Koruna', 'outpaceseo'),
                        'DKK' => __('Danish Krone', 'outpaceseo'),
                        'DJF' => __('Djiboutian Franc', 'outpaceseo'),
                        'DOP' => __('Dominican Peso', 'outpaceseo'),
                        'XCD' => __('East Caribbean Dollar', 'outpaceseo'),
                        'EGP' => __('Egyptian Pound', 'outpaceseo'),
                        'ERN' => __('Eritrean Nakfa', 'outpaceseo'),
                        'EEK' => __('Estonian Kroon', 'outpaceseo'),
                        'ETB' => __('Ethiopian Birr', 'outpaceseo'),
                        'EUR' => __('Euro', 'outpaceseo'),
                        'FKP' => __('Falkland Islands Pound', 'outpaceseo'),
                        'FJD' => __('Fijian Dollar', 'outpaceseo'),
                        'GMD' => __('Gambian Dalasi', 'outpaceseo'),
                        'GEL' => __('Georgian Lari', 'outpaceseo'),
                        'DEM' => __('German Mark', 'outpaceseo'),
                        'GHS' => __('Ghanaian Cedi', 'outpaceseo'),
                        'GIP' => __('Gibraltar Pound', 'outpaceseo'),
                        'GRD' => __('Greek Drachma', 'outpaceseo'),
                        'GTQ' => __('Guatemalan Quetzal', 'outpaceseo'),
                        'GNF' => __('Guinean Franc', 'outpaceseo'),
                        'GYD' => __('Guyanaese Dollar', 'outpaceseo'),
                        'HTG' => __('Haitian Gourde', 'outpaceseo'),
                        'HNL' => __('Honduran Lempira', 'outpaceseo'),
                        'HKD' => __('Hong Kong Dollar', 'outpaceseo'),
                        'HUF' => __('Hungarian Forint', 'outpaceseo'),
                        'ISK' => __('Icelandic Króna', 'outpaceseo'),
                        'INR' => __('Indian Rupee', 'outpaceseo'),
                        'IDR' => __('Indonesian Rupiah', 'outpaceseo'),
                        'IRR' => __('Iranian Rial', 'outpaceseo'),
                        'IQD' => __('Iraqi Dinar', 'outpaceseo'),
                        'ILS' => __('Israeli New Shekel', 'outpaceseo'),
                        'ITL' => __('Italian Lira', 'outpaceseo'),
                        'JMD' => __('Jamaican Dollar', 'outpaceseo'),
                        'JPY' => __('Japanese Yen', 'outpaceseo'),
                        'JOD' => __('Jordanian Dinar', 'outpaceseo'),
                        'KZT' => __('Kazakhstani Tenge', 'outpaceseo'),
                        'KES' => __('Kenyan Shilling', 'outpaceseo'),
                        'KWD' => __('Kuwaiti Dinar', 'outpaceseo'),
                        'KGS' => __('Kyrgystani Som', 'outpaceseo'),
                        'LAK' => __('Laotian Kip', 'outpaceseo'),
                        'LVL' => __('Latvian Lats', 'outpaceseo'),
                        'LBP' => __('Lebanese Pound', 'outpaceseo'),
                        'LSL' => __('Lesotho Loti', 'outpaceseo'),
                        'LRD' => __('Liberian Dollar', 'outpaceseo'),
                        'LYD' => __('Libyan Dinar', 'outpaceseo'),
                        'LTL' => __('Lithuanian Litas', 'outpaceseo'),
                        'MOP' => __('Macanese Pataca', 'outpaceseo'),
                        'MKD' => __('Macedonian Denar', 'outpaceseo'),
                        'MGA' => __('Malagasy Ariary', 'outpaceseo'),
                        'MWK' => __('Malawian Kwacha', 'outpaceseo'),
                        'MYR' => __('Malaysian Ringgit', 'outpaceseo'),
                        'MVR' => __('Maldivian Rufiyaa', 'outpaceseo'),
                        'MRO' => __('Mauritanian Ouguiya', 'outpaceseo'),
                        'MUR' => __('Mauritian Rupee', 'outpaceseo'),
                        'MXN' => __('Mexican Peso', 'outpaceseo'),
                        'MDL' => __('Moldovan Leu', 'outpaceseo'),
                        'MNT' => __('Mongolian Tugrik', 'outpaceseo'),
                        'MAD' => __('Moroccan Dirham', 'outpaceseo'),
                        'MZM' => __('Mozambican Metical', 'outpaceseo'),
                        'MMK' => __('Myanmar Kyat', 'outpaceseo'),
                        'NAD' => __('Namibian Dollar', 'outpaceseo'),
                        'NPR' => __('Nepalese Rupee', 'outpaceseo'),
                        'ANG' => __('Netherlands Antillean Guilder', 'outpaceseo'),
                        'TWD' => __('New Taiwan Dollar', 'outpaceseo'),
                        'NZD' => __('New Zealand Dollar', 'outpaceseo'),
                        'NIO' => __('Nicaraguan Córdoba', 'outpaceseo'),
                        'NGN' => __('Nigerian Naira', 'outpaceseo'),
                        'KPW' => __('North Korean Won', 'outpaceseo'),
                        'NOK' => __('Norwegian Krone', 'outpaceseo'),
                        'OMR' => __('Omani Rial', 'outpaceseo'),
                        'PKR' => __('Pakistani Rupee', 'outpaceseo'),
                        'PAB' => __('Panamanian Balboa', 'outpaceseo'),
                        'PGK' => __('Papua New Guinean Kina', 'outpaceseo'),
                        'PYG' => __('Paraguayan Guarani', 'outpaceseo'),
                        'PEN' => __('Peruvian Sol', 'outpaceseo'),
                        'PHP' => __('Philippine Peso', 'outpaceseo'),
                        'PLN' => __('Polish Zloty', 'outpaceseo'),
                        'QAR' => __('Qatari Rial', 'outpaceseo'),
                        'RON' => __('Romanian Leu', 'outpaceseo'),
                        'RUB' => __('Russian Ruble', 'outpaceseo'),
                        'RWF' => __('Rwandan Franc', 'outpaceseo'),
                        'SVC' => __('Salvadoran Colón', 'outpaceseo'),
                        'WST' => __('Samoan Tala', 'outpaceseo'),
                        'SAR' => __('Saudi Riyal', 'outpaceseo'),
                        'RSD' => __('Serbian Dinar', 'outpaceseo'),
                        'SCR' => __('Seychellois Rupee', 'outpaceseo'),
                        'SLL' => __('Sierra Leonean Leone', 'outpaceseo'),
                        'SGD' => __('Singapore Dollar', 'outpaceseo'),
                        'SKK' => __('Slovak Koruna', 'outpaceseo'),
                        'SBD' => __('Solomon Islands Dollar', 'outpaceseo'),
                        'SOS' => __('Somali Shilling', 'outpaceseo'),
                        'ZAR' => __('South African Rand', 'outpaceseo'),
                        'KRW' => __('South Korean Won', 'outpaceseo'),
                        'XDR' => __('Special Drawing Rights', 'outpaceseo'),
                        'LKR' => __('Sri Lankan Rupee', 'outpaceseo'),
                        'SHP' => __('St. Helena Pound', 'outpaceseo'),
                        'SDG' => __('Sudanese Pound', 'outpaceseo'),
                        'SRD' => __('Surinamese Dollar', 'outpaceseo'),
                        'SZL' => __('Swazi Lilangeni', 'outpaceseo'),
                        'SEK' => __('Swedish Krona', 'outpaceseo'),
                        'CHF' => __('Swiss Franc', 'outpaceseo'),
                        'SYP' => __('Syrian Pound', 'outpaceseo'),
                        'STD' => __('São Tomé & Príncipe Dobra', 'outpaceseo'),
                        'TJS' => __('Tajikistani Somoni', 'outpaceseo'),
                        'TZS' => __('Tanzanian Shilling', 'outpaceseo'),
                        'THB' => __('Thai Baht', 'outpaceseo'),
                        'TOP' => __('Tongan Pa\'anga', 'outpaceseo'),
                        'TTD' => __('Trinidad & Tobago Dollar', 'outpaceseo'),
                        'TND' => __('Tunisian Dinar', 'outpaceseo'),
                        'TRY' => __('Turkish Lira', 'outpaceseo'),
                        'TMT' => __('Turkmenistani Manat', 'outpaceseo'),
                        'UGX' => __('Ugandan Shilling', 'outpaceseo'),
                        'UAH' => __('Ukrainian Hryvnia', 'outpaceseo'),
                        'AED' => __('United Arab Emirates Dirham', 'outpaceseo'),
                        'UYU' => __('Uruguayan Peso', 'outpaceseo'),
                        'USD' => __('US Dollar', 'outpaceseo'),
                        'UZS' => __('Uzbekistani Som', 'outpaceseo'),
                        'VUV' => __('Vanuatu Vatu', 'outpaceseo'),
                        'VEF' => __('Venezuelan Bolívar', 'outpaceseo'),
                        'VND' => __('Vietnamese Dong', 'outpaceseo'),
                        'XOF' => __('West African CFA Franc', 'outpaceseo'),
                        'YER' => __('Yemeni Rial', 'outpaceseo'),
                        'ZMK' => __('Zambian Kwacha', 'outpaceseo'),
                    )
                );
                break;
            case 'software-category':
                $return = apply_filters(
                    'outpaceseo_software_category_options',
                    array(
                        'BusinessApplication '        => __('Business App', 'outpaceseo'),
                        'GameApplication'             => __('Game App', 'outpaceseo'),
                        'MultimediaApplication'       => __('Multimedia App', 'outpaceseo'),
                        'MobileApplication'           => __('Mobile App', 'outpaceseo'),
                        'WebApplication'              => __('Web App', 'outpaceseo'),
                        'SocialNetworkingApplication' => __('Social Networking App', 'outpaceseo'),
                        'TravelApplication'           => __('Travel App', 'outpaceseo'),
                        'ShoppingApplication'         => __('Shopping App', 'outpaceseo'),
                        'SportsApplication'           => __('Sports App', 'outpaceseo'),
                        'LifestyleApplication'        => __('Lifestyle App', 'outpaceseo'),
                        'DesignApplication '          => __('Design App', 'outpaceseo'),
                        'DeveloperApplication'        => __('Developer App', 'outpaceseo'),
                        'DriverApplication'           => __('Driver App', 'outpaceseo'),
                        'EducationalApplication'      => __('Educational App', 'outpaceseo'),
                        'HealthApplication'           => __('Health App', 'outpaceseo'),
                        'FinanceApplication '         => __('Finance App', 'outpaceseo'),
                        'SecurityApplication'         => __('Security App', 'outpaceseo'),
                        'BrowserApplication'          => __('Browser App', 'outpaceseo'),
                        'CommunicationApplication'    => __('Communication App', 'outpaceseo'),
                        'DesktopEnhancementApplication' => __('Desktop Enhancement App', 'outpaceseo'),
                        'EntertainmentApplication '   => __('Business App', 'outpaceseo'),
                        'HomeApplication'             => __('Home App', 'outpaceseo'),
                        'UtilitiesApplication'        => __('Utilities App', 'outpaceseo'),
                        'ReferenceApplication'        => __('Reference App', 'outpaceseo'),
                    )
                );
                break;
            case 'time-unit':
                $return = apply_filters(
                    'outpaceseo_time_unit_options',
                    array(
                        'HOUR'  => 'HOUR',
                        'WEEK'  => 'WEEK',
                        'MONTH' => 'MONTH',
                        'YEAR'  => 'YEAR',
                        'DAY'   => 'DAY',
                    )
                );
                break;
            case 'gender-select':
                $return = apply_filters(
                    'outpaceseo_gender_options',
                    array(
                        'Male'   => 'Male',
                        'Female' => 'Female',
                        'Other'  => 'Other',
                    )
                );
                break;
            case 'Organization-type':
                $return = apply_filters(
                    'outpaceseo_organization_type_options',
                    array(
                        'organization'            => 'General/ Other',
                        'Corporation'             => 'Corporation',
                        'Airline'                 => 'Airline',
                        'Consortium'              => 'Consortium',
                        'EducationalOrganization' => ' Educational Organization',
                        'CollegeOrUniversity'     => '&mdash; College Or University',
                        'ElementarySchool'        => '&mdash; Elementary School',
                        'HighSchool'              => '&mdash; High School',
                        'MiddleSchool'            => '&mdash; Middle School',
                        'Preschool'               => '&mdash; Pre School',
                        'School'                  => '&mdash; School',
                        'GovernmentOrganization'  => 'Government Organization',
                        'MedicalOrganization'     => 'Medical Organization',
                        'DiagnosticLab'           => '&mdash; Diagnostic Lab',
                        'VeterinaryCare'          => '&mdash; Veterinary Care',
                        'NGO'                     => 'NGO',
                        'PerformingGroup'         => 'Performing Group',
                        'DanceGroup'              => '&mdash; Dance Group',
                        'MusicGroup'              => '&mdash; Music Group',
                        'TheaterGroup'            => '&mdash;Theater Group',
                        'NewsMediaOrganization'   => 'News Media Organization',
                        'Project'                 => 'Project',
                        'ResearchProject'         => '&mdash; Research Project',
                        'FundingAgency'           => '&mdash; Funding Agency',
                        'SportsOrganization'      => 'Sports Organization',
                        'SportsTeam'              => '&mdash; Sports Team',
                        'LibrarySystem'           => 'Library System',
                        'WorkersUnion'            => 'Workers Union',

                    )
                );
                break;
            default:
                $return = apply_filters('outpaceseo_dropdown_options', array());
                break;
        }
        return array('' => __('-- None --', 'outpaceseo')) + $return;
    }

    /**
     * Function to filter only Blank value.
     *
     * @since 1.1.3
     * @param  mixed $var Variable.
     * @return boolean
     */
    public static function is_not_empty($var)
    {

        return !empty($var) || '0' === $var;
    }

    /**
     * Metabox Save
     *
     * @param  number $post_id Post ID.
     */
    public function save_meta_box($post_id)
    {

        // Checks save status.
        $is_autosave = wp_is_post_autosave($post_id);
        $is_revision = wp_is_post_revision($post_id);

        $is_valid_nonce = (isset($_POST['outpaceseo_schema']) && wp_verify_nonce($_POST['outpaceseo_schema'], basename(__FILE__))) ? true : false;

        // Exits script depending on save status.
        if ($is_autosave || $is_revision || !$is_valid_nonce) {
            return;
        }

        /**
         * Get meta options
         */
        $post_meta = self::get_meta_option();
        foreach ($post_meta as $key => $data) {
            if (in_array($key, self::$schema_meta_keys, true)) {

                if (!isset($_POST[$key])) {
                    continue;
                }

                $_POST[$key] = array_filter($_POST[$key], __CLASS__ . '::is_not_empty');

                $meta_value = array();
                foreach ($_POST[$key] as $meta_key => $value) {
                    $subkey_type = isset(self::$schema_meta_fields[$key]['subkeys'][$meta_key]['type']) ? self::$schema_meta_fields[$key]['subkeys'][$meta_key]['type'] : 'text';
                    if (('repeater' === $subkey_type || 'repeater-target' === $subkey_type) && is_array($value)) {
                        $i = 0;
                        foreach ($value as $repeater_value) {
                            $meta_value[$meta_key][$i] = array_map('esc_attr', $repeater_value);
                            $i++;
                        }
                    } else {
                        if ('custom-markup-custom-text' === $meta_key) {
                            $meta_value[$meta_key] = $value;
                        } else {
                            $meta_value[$meta_key] = esc_attr($value);
                        }
                    }
                }
            } elseif (in_array($key, array('outpaceseo-schema-location', 'outpaceseo-schema-exclusion'), true)) {
                $meta_value = Outpaceseo_Target_Rule_Fields::get_format_rule_value($_POST, $key);
            } else {
                // Sanitize values.
                $sanitize_filter = (isset($data['sanitize'])) ? $data['sanitize'] : 'FILTER_DEFAULT';

                switch ($sanitize_filter) {

                    case 'FILTER_SANITIZE_STRING':
                        $meta_value = filter_input(INPUT_POST, $key, FILTER_SANITIZE_STRING);
                        break;

                    case 'FILTER_SANITIZE_URL':
                        $meta_value = filter_input(INPUT_POST, $key, FILTER_SANITIZE_URL);
                        break;

                    case 'FILTER_SANITIZE_NUMBER_INT':
                        $meta_value = filter_input(INPUT_POST, $key, FILTER_SANITIZE_NUMBER_INT);
                        break;

                    default:
                        $meta_value = filter_input(INPUT_POST, $key, FILTER_DEFAULT);
                        break;
                }
            }

            // Store values.
            if ($meta_value) {
                update_post_meta($post_id, $key, $meta_value);
            } else {
                delete_post_meta($post_id, $key);
            }
        }
    }

    /**
     * Add Update messages for any custom post type
     *
     * @param array $messages Array of default messages.
     */
    public function custom_post_type_post_update_messages($messages)
    {
        if (isset($_REQUEST['outpaceseo_admin_page_nonce']) && !wp_verify_nonce($_REQUEST['outpaceseo_admin_page_nonce'], 'outpaceseo_admin_page')) {
            return false;
        }
        $custom_post_type = get_post_type(get_the_ID());

        if ('outpaceseo_schema' === $custom_post_type) {

            $obj                           = get_post_type_object($custom_post_type);
            $singular_name                 = $obj->labels->singular_name;
            $messages[$custom_post_type] = array(
                0  => '', // Unused. Messages start at index 1.
                /* translators: %s: singular custom post type name */
                1  => sprintf(__('%s updated.', 'outpaceseo'), $singular_name),
                /* translators: %s: singular custom post type name */
                2  => sprintf(__('Custom %s updated.', 'outpaceseo'), $singular_name),
                /* translators: %s: singular custom post type name */
                3  => sprintf(__('Custom %s deleted.', 'outpaceseo'), $singular_name),
                /* translators: %s: singular custom post type name */
                4  => sprintf(__('%s updated.', 'outpaceseo'), $singular_name),
                /* translators: %1$s: singular custom post type name ,%2$s: date and time of the revision */
                5  => isset($_GET['revision']) ? sprintf(__('%1$s restored to revision from %2$s', 'outpaceseo'), $singular_name, wp_post_revision_title((int) $_GET['revision'], false)) : false,
                /* translators: %s: singular custom post type name */
                6  => sprintf(__('%s published.', 'outpaceseo'), $singular_name),
                /* translators: %s: singular custom post type name */
                7  => sprintf(__('%s saved.', 'outpaceseo'), $singular_name),
                /* translators: %s: singular custom post type name */
                8  => sprintf(__('%s submitted.', 'outpaceseo'), $singular_name),
                /* translators: %s: singular custom post type name */
                9  => sprintf(__('%s scheduled for.', 'outpaceseo'), $singular_name),
                /* translators: %s: singular custom post type name */
                10 => sprintf(__('%s draft updated.', 'outpaceseo'), $singular_name),
            );
        }

        return $messages;
    }
}
OutpaceSEO_Schema::get_instance();
