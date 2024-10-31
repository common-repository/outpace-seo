<?php

/**
 * Schemas Template.
 */

if (!class_exists('Outpaceseo_Schema_Image_License')) {

    /**
     * Outpaceseo Schemas Initialization
     */
    class Outpaceseo_Schema_Image_License
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

            if (isset($data['image-license']) && !empty($data['image-license'])) {
                foreach ($data['image-license'] as $key => $value) {
                    $schema[$key]['@context'] = 'https://schema.org';
                    $schema[$key]['@type']    = 'ImageObject';
                    if (isset($value['content-url']) && !empty($value['content-url'])) {
                        $schema[$key]['contentUrl'] = Outpaceseo_Schema_Template::get_image_schema($value['content-url'], 'URL');
                    }
                    if (isset($value['license']) && !empty($value['license'])) {
                        $schema[$key]['license'] = esc_url($value['license']);
                    }
                    if (isset($value['acquire-license-Page']) && !empty($value['acquire-license-Page'])) {
                        $schema[$key]['acquireLicensePage'] = esc_url($value['acquire-license-Page']);
                    }
                }
            }

            return apply_filters('outpaceseo_schema_image_license', $schema, $data, $post);
        }
    }
}
