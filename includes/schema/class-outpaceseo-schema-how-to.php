<?php

/**
 * Schemas Template.
 */

if (!class_exists('Outpaceseo_Schema_How_To')) {

    /**
     * Outpaceseo Schemas Initialization
     */
    class Outpaceseo_Schema_How_To
    {

        /**
         * Render Schema.
         *
         * @param  array $data Meta Data.
         * @param  array $post Current Post Array.
         * @return array
         */
        public static function render($data, $post)
        {
            $schema = array();

            $schema['@context'] = 'https://schema.org';
            $schema['@type']    = 'HowTo';

            if (isset($data['name']) && !empty($data['name'])) {
                $schema['name'] = wp_strip_all_tags($data['name']);
            }

            if (isset($data['description']) && !empty($data['description'])) {
                $schema['description'] = wp_strip_all_tags($data['description']);
            }

            if (isset($data['total-time']) && !empty($data['total-time'])) {
                $schema['totalTime'] = wp_strip_all_tags($data['total-time']);
            }

            if (isset($data['supply']) && !empty($data['supply'])) {

                foreach ($data['supply'] as $key => $value) {

                    if (isset($value['name']) && !empty($value['name'])) {

                        $schema['supply'][$key]['@type'] = 'HowToSupply';

                        if (isset($value['name']) && !empty($value['name'])) {
                            $schema['supply'][$key]['name'] = wp_strip_all_tags($value['name']);
                        }
                    }
                }
            }

            if (isset($data['tool']) && !empty($data['tool'])) {

                foreach ($data['tool'] as $key => $value) {

                    if (isset($value['name']) && !empty($value['name'])) {

                        $schema['tool'][$key]['@type'] = 'HowToTool';

                        if (isset($value['name']) && !empty($value['name'])) {
                            $schema['tool'][$key]['name'] = wp_strip_all_tags($value['name']);
                        }
                    }
                }
            }

            if (isset($data['steps']) && !empty($data['steps'])) {
                foreach ($data['steps'] as $key => $value) {
                    $schema['step'][$key]['@type'] = 'HowToStep';
                    if (isset($value['name']) && !empty($value['name'])) {
                        $schema['step'][$key]['name'] = $value['name'];
                    }
                    if (isset($value['url']) && !empty($value['url'])) {
                        $schema['step'][$key]['url'] = $value['url'];
                    }
                    if (isset($value['description']) && !empty($value['description'])) {
                        $schema['step'][$key]['itemListElement']['@type'] = 'HowToDirection';
                        $schema['step'][$key]['itemListElement']['text']  = $value['description'];
                    }
                    $step_image = wp_get_attachment_image_src($value['image'], 'full');
                    if (isset($value['image']) && !empty($value['image'])) {
                        $schema['step'][$key]['image'] = Outpaceseo_Schema_Template::get_image_schema($step_image, 'ImageObject');
                    }
                }
            }

            return apply_filters('outpaceseo_schema_how_to', $schema, $data, $post);
        }
    }
}
