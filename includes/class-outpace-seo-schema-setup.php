<?php

/**
 * Outpaceseo Schema Wizard
 *
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('OUTPACESEO_Schema_Wizard')) :

    /**
     * OUTPACESEO_Schema_Wizard class.
     */
    class OUTPACESEO_Schema_Wizard
    {
        /**
         * Hook in tabs.
         */
        public function __construct()
        {
            if (apply_filters('op_enable_setup_wizard', true) && current_user_can('manage_options')) {
                add_action('admin_menu', array($this, 'admin_menus'));
                add_action('admin_init', array($this, 'setup_wizard'), 20);
            }
        }

        /**
         * Add admin menus.
         */
        public function admin_menus()
        {
            add_dashboard_page('', '', 'manage_options', 'outpaceseo-schema-setup', '');
        }

        /**
         * Show the setup wizard.
         */
        public function setup_wizard()
        {
            if (empty($_GET['page']) || 'outpaceseo-schema-setup' !== $_GET['page']) {
                return;
            }

            $this->steps = array(
                'basic-config' => array(
                    'name'    => __('Choose Schema Type', 'outpaceseo'),
                    'view'    => array($this, 'choose_schema_type'),
                    'handler' => array($this, 'choose_schema_type_save'),
                ),
                'enable-on'    => array(
                    'name'    => __('Set Target Pages', 'outpaceseo'),
                    'view'    => array($this, 'implement_on_callback'),
                    'handler' => array($this, 'implement_on_callback_save'),
                ),
                'setup-ready'  => array(
                    'name'    => __('Ready!', 'outpaceseo'),
                    'view'    => array($this, 'schema_ready'),
                    'handler' => '',
                ),
            );

            $this->step = isset($_GET['step']) ? sanitize_key($_GET['step']) : current(array_keys($this->steps));
            wp_enqueue_style('outpaceseo-schema-css', outpaceseo()->plugin_url() . '/assets/css/outpaceseo-schema.css', array(), OSEO_VERSION);
            wp_register_script('outpaceseo-schema-js', outpaceseo()->plugin_url() . '/assets/js/outpaceseo-schema.js', array('jquery', 'jquery-ui-tooltip'), OSEO_VERSION, true);
            wp_register_script('outpaceseo-schema-setup', outpaceseo()->plugin_url() . '/assets/js/outpaceseo.js', array('jquery'), OSEO_VERSION, true);

            wp_enqueue_style('op-target-rule-select2', outpaceseo()->plugin_url() . '/assets/css/select2.css', '', OSEO_VERSION, false);
            wp_enqueue_style('op-target-rule', outpaceseo()->plugin_url() . '/assets/css/target-rule.css', '', OSEO_VERSION, false);
            wp_register_script('op-target-rule-select2', outpaceseo()->plugin_url() . '/assets/js/select2.js', array('jquery', 'backbone', 'wp-util'), OSEO_VERSION, true);
            wp_register_script('op-target-rule', outpaceseo()->plugin_url() . '/assets/js/target-rule.js', array('jquery', 'op-target-rule-select2'), OSEO_VERSION, true);
            wp_register_script('op-user-role', outpaceseo()->plugin_url() . '/assets/js/user-role.js', array('jquery'), OSEO_VERSION, true);

            wp_enqueue_media();
            wp_localize_script(
                'op-target-rule',
                'Targetrule',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'security' => wp_create_nonce('schema_nonce'),
                )
            );
            if ((isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'outpaceseo-schema-setup')) && !empty($_POST['save_step']) && isset($this->steps[$this->step]['handler'])) {
                call_user_func($this->steps[$this->step]['handler']);
            }

            ob_start();
            $this->setup_wizard_header();
            $this->setup_wizard_steps();
            $this->setup_wizard_content();
            $this->setup_wizard_footer();
            exit;
        }

        /**
         * Get next step link
         */
        public function get_next_step_link()
        {
            $keys = array_keys($this->steps);
            return add_query_arg('step', $keys[array_search($this->step, array_keys($this->steps), true) + 1]);
        }

        /**
         * Setup Wizard Header.
         */
        public function setup_wizard_header()
        {
?>
            <!DOCTYPE html>
            <html lang="en">

            <head>
                <meta name="viewport" content="width=device-width" />
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                <title><?php esc_html_e('Outpaceseo schema setup', 'outpaceseo'); ?></title>
                <script type="text/javascript">
                    addLoadEvent = function(func) {
                        if (typeof jQuery != "undefined") jQuery(document).ready(func);
                        else if (typeof wpOnload != 'function') {
                            wpOnload = func;
                        } else {
                            var oldonload = wpOnload;
                            wpOnload = function() {
                                oldonload();
                                func();
                            }
                        }
                    };
                    var ajaxurl = '<?php echo esc_url(admin_url('admin-ajax.php', 'relative')); ?>';
                </script>
                <?php wp_print_scripts(array('op-target-rule-select2', 'op-target-rule', 'op-user-role', 'outpaceseo-schema-js', 'outpaceseo-schema-setup')); ?>
                <?php do_action('admin_print_styles'); ?>
            </head>

            <body class="outpaceseo-schema-setup wp-core-ui">
                <div id="op-schema-heading">
                    <h3 class="op_setup_heading"><?php esc_html_e('Outpaceseo Schema', 'outpaceseo'); ?></h3>
                </div>
            <?php
        }
        /**
         * Setup Wizard Footer.
         */
        public function setup_wizard_footer()
        {

            $admin_url = admin_url('edit.php?post_type=outpaceseo_schema');
            ?>
                <div class="close-button-wrapper">
                    <a href="<?php echo esc_url($admin_url); ?>" class="wizard-close-link"><?php esc_html_e('Exit Setup Wizard', 'outpaceseo'); ?></a>
                </div>
            </body>

            </html>
        <?php
        }

        /**
         * Output the steps.
         */
        public function setup_wizard_steps()
        {

            $ouput_steps = $this->steps;
        ?>
            <ol class="outpaceseo-schema-setup-steps">
                <?php
                foreach ($ouput_steps as $step_key => $step) :
                    $classes = '';
                    if ($step_key === $this->step) {
                        $classes = 'active';
                    } elseif (array_search($this->step, array_keys($this->steps), true) > array_search($step_key, array_keys($this->steps), true)) {
                        $classes = 'done';
                    }
                ?>
                    <li class="<?php echo esc_attr($classes); ?>">
                        <span><?php echo esc_html($step['name']); ?></span>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php
        }

        /**
         * Output the content for the current step.
         */
        public function setup_wizard_content()
        {
            echo '<div class="outpaceseo-schema-setup-content">';
            call_user_func($this->steps[$this->step]['view']);
            echo '</div>';
        }

        /**
         * Introduction step.
         */
        public function choose_schema_type()
        {
        ?>
            <h1><?php esc_html_e('Select the Schema Type:', 'outpaceseo'); ?></h1>
            <form method="post">
                <input type="hidden" id="outpaceseo-schema-title" name="outpaceseo-schema-title" class="outpaceseo-schema-title">
                <input type="hidden" id="outpaceseo-schema-type" name="outpaceseo-schema-type" class="outpaceseo-schema-type">
                <table class="form-table outpaceseo-basic-config">
                    <tr>
                        <td>
                            <?php foreach (OutpaceSEO_Schema::$schema_meta_fields as $key => $schema_field) { ?>
                                <span class="outpaceseo-schema-temp-wrap" data-schema-type="<?php echo esc_attr($schema_field['key']); ?>" data-schema-title="<?php echo isset($schema_field['label']) ? esc_attr($schema_field['label']) : ''; ?>">
                                    <i class="<?php echo isset($schema_field['icon']) ? esc_attr($schema_field['icon']) : 'dashicons dashicons-media-default'; ?>"></i>
                                    <?php echo isset($schema_field['label']) ? esc_attr($schema_field['label']) : ''; ?>
                                </span>
                            <?php } ?>
                        </td>
                    </tr>
                </table>

                <p class="outpaceseo-schema-setup-actions step">
                    <input type="submit" class="uct-activate button-primary button button-large button-next" disabled="true" value="<?php esc_html_e('Next', 'outpaceseo'); ?>" name="save_step" />
                    <?php wp_nonce_field('outpaceseo-schema-setup'); ?>
                </p>
            </form>
        <?php
        }

        /**
         * Save Locale Settings.
         */
        public function choose_schema_type_save()
        {
            check_admin_referer('outpaceseo-schema-setup');

            $redirect_url = $this->get_next_step_link();
            $title        = isset($_POST['outpaceseo-schema-title']) ? sanitize_text_field($_POST['outpaceseo-schema-title']) : 0;
            $type         = isset($_POST['outpaceseo-schema-type']) ? sanitize_text_field($_POST['outpaceseo-schema-type']) : 0;
            $default_fields = array();
            if (isset(OutpaceSEO_Schema::$schema_meta_fields['outpaceseo-' . $type]['subkeys'])) {
                $default_data = OutpaceSEO_Schema::$schema_meta_fields['outpaceseo-' . $type]['subkeys'];
                foreach ($default_data as $key => $value) {
                    if ('repeater' === $value['type']) {
                        foreach ($value['fields'] as $subkey => $subvalue) {
                            if (isset($subvalue['default']) && 'none' !== $subvalue['default']) {
                                $default_fields[$key][0][$subkey] = $subvalue['default'];
                            } else {
                                $default_fields[$key][0][$subkey] = 'create-field';
                            }
                        }
                    } else {
                        if (isset($value['default']) && 'none' !== $value['default']) {
                            $default_fields[$key] = $value['default'];
                        } else {
                            $default_fields[$key] = 'create-field';
                        }
                    }
                }
            }

            $postarr = array(
                'post_type'   => 'outpaceseo_schema',
                'post_title'  => $title,
                'post_status' => 'publish',
                'meta_input'  => array(
                    'outpaceseo-schema-type' => $type,
                    'outpaceseo-' . $type    => $default_fields,
                ),
            );
            $post_id = wp_insert_post($postarr);

            if (!is_wp_error($post_id)) {
                $redirect_url = add_query_arg('schema-id', $post_id, $redirect_url);
            }

            wp_safe_redirect(esc_url_raw($redirect_url));
            exit;
        }

        /**
         * Locale settings
         */
        public function implement_on_callback()
        {
            $schema_id    = 0;
            $title        = '';
            $redirect_url = $this->get_next_step_link();

            if (isset($_GET['schema-id']) && !empty($_GET['schema-id'])) {
                $schema_id    = intval($_GET['schema-id']);
                $redirect_url = add_query_arg('schema-id', $schema_id, $redirect_url);
                $title        = get_the_title($schema_id);
            }

            $meta_values = array(
                'include-locations' => array(
                    'rule' => array('basic-singulars'),
                ),
                'exclude-locations' => array(),
            );
        ?>

            <h1>
                <?php
                printf(
                    /* translators: 1 schema title */
                    wp_kses_post('Where %s schema should be integrated?', 'outpaceseo'),
                    esc_html($title)
                );
                ?>
            </h1>
            <form method="post">
                <input type="hidden" name="schema-id" value="<?php echo esc_attr($schema_id); ?>">
                <table class="outpaceseo-schema-table widefat">
                    <tr class="outpaceseo-schema-row">
                        <td class="outpaceseo-schema-row-heading">
                            <label><?php esc_html_e('Enable On', 'outpaceseo'); ?></label>
                            <i class="outpaceseo-schema-heading-help dashicons dashicons-editor-help" title="<?php echo esc_attr__('Add target pages where this Schema should appear.', 'outpaceseo'); ?>"></i>
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
                            <i class="outpaceseo-schema-heading-help dashicons dashicons-editor-help" title="<?php echo esc_attr__('This Schema will not appear at these pages.', 'outpaceseo'); ?>"></i>
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
                <p class="outpaceseo-schema-setup-actions step">
                    <input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e('Next', 'outpaceseo'); ?>" name="save_step" />
                    <?php wp_nonce_field('outpaceseo-schema-setup'); ?>
                </p>
            </form>
        <?php
        }

        /**
         * Save Locale Settings.
         */
        public function implement_on_callback_save()
        {
            check_admin_referer('outpaceseo-schema-setup');

            $schema_id    = isset($_POST['schema-id']) ? sanitize_text_field($_POST['schema-id']) : 0;
            $enabled_on   = Outpaceseo_Target_Rule_Fields::get_format_rule_value($_POST, 'outpaceseo-schema-location');
            $exclude_from = Outpaceseo_Target_Rule_Fields::get_format_rule_value($_POST, 'outpaceseo-schema-exclusion');
            $redirect_url = $this->get_next_step_link();
            if ($schema_id) {
                $redirect_url = add_query_arg('schema-id', $schema_id, $redirect_url);
                update_post_meta($schema_id, 'outpaceseo-schema-location', $enabled_on);
                update_post_meta($schema_id, 'outpaceseo-schema-exclusion', $exclude_from);
            }

            wp_safe_redirect(esc_url_raw($redirect_url));
            exit;
        }

        /**
         * Get Location rules of schema for Custom meta box.
         *
         * @param  array $enabled_on   Enabled on rules.
         * @param  array $exclude_from Exlcude on rules.
         * @return array
         */
        public static function get_display_rules_for_meta_box($enabled_on, $exclude_from)
        {
            $locations        = array();
            $enabled_location = array();
            $exclude_location = array();

            $args       = array(
                'public'   => true,
                '_builtin' => true,
            );
            $post_types = get_post_types($args);
            unset($post_types['attachment']);

            $args['_builtin'] = false;
            $custom_post_type = get_post_types($args);
            $post_types       = array_merge($post_types, $custom_post_type);

            if (!empty($enabled_on) && isset($enabled_on['rule'])) {
                $enabled_location = $enabled_on['rule'];
            }
            if (!empty($exclude_from) && isset($exclude_from['rule'])) {
                $exclude_location = $exclude_from['rule'];
            }

            if (in_array('specifics', $enabled_location, true) || (in_array('basic-singulars', $enabled_location, true) && !in_array('basic-singulars', $exclude_location, true))) {
                foreach ($post_types as $post_type) {
                    $locations[$post_type] = 1;
                }
            } else {
                foreach ($post_types as $post_type) {
                    $key = $post_type . '|all';
                    if (in_array($key, $enabled_location, true) && !in_array($key, $exclude_location, true)) {
                        $locations[$post_type] = 1;
                    }
                }
            }
            return $locations;
        }

        /**
         * Final step.
         */
        public function schema_ready()
        {
            $schema_id = 0;
            $title     = '';

            if (isset($_GET['schema-id']) && !empty($_GET['schema-id'])) {
                $schema_id = intval($_GET['schema-id']);
                $title     = get_the_title($schema_id);
            }

        ?>
            <h1><?php esc_html_e('Your Schema is Ready!', 'outpaceseo'); ?></h1>

            <div class="outpaceseo-schema-setup-next-steps">
                <div class="outpaceseo-schema-setup-next-steps-last">

                    <p class="success">
                        <?php
                        printf(
                            /* translators: 1 schema title */
                            wp_kses_post('Congratulations! The <i>%s</i> Schema has been added and enabled on selected target locations.', 'outpaceseo'),
                            esc_html($title)
                        );
                        ?>
                    </p>
                    <p class="success">
                        <strong><?php esc_html_e('Here’s what to do next:', 'outpaceseo'); ?></strong><br>
                        <?php esc_html_e('Step 1: Complete the setup and proceed to fill the required properties of this schema.', 'outpaceseo'); ?><br>
                        <?php esc_html_e('Step 2: Add necessary Schema information on individual pages and posts.', 'outpaceseo'); ?><br>
                        <?php esc_html_e('Step 3: Test if Schema is integrated correctly.', 'outpaceseo'); ?>
                    </p>

                    <table class="form-table aiosrs-pro-schema-ready">
                        <tr>
                            <td>
                                <a href="<?php echo ($schema_id) ? esc_attr(get_edit_post_link($schema_id)) : '#'; ?>" type="button" class="button button-primary button-hero"><?php esc_html_e('Complete Setup', 'outpaceseo'); ?></a>
                            </td>
                        </tr>
                    </table>

                </div>
            </div>
<?php
        }
    }

    new OUTPACESEO_Schema_Wizard();

endif;
