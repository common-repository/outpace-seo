<?php

/**
 * Schemas Template.
 */

if (!class_exists('Outpaceseo_Schema_Video_Object')) {

    /**
     * Outpaceseo Schemas Initialization
     */
    class Outpaceseo_Schema_Video_Object
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
            $schema['@type']    = 'VideoObject';

            if (isset($data['name']) && !empty($data['name'])) {
                $schema['name'] = wp_strip_all_tags($data['name']);
            }

            if (isset($data['description']) && !empty($data['description'])) {
                $schema['description'] = wp_strip_all_tags($data['description']);
            }

            if (isset($data['orgnization-name']) && !empty($data['orgnization-name'])) {
                $schema['publisher']['@type'] = 'Organization';
                $schema['publisher']['name']  = wp_strip_all_tags($data['orgnization-name']);
            }

            if (isset($data['site-logo']) && !empty($data['site-logo'])) {
                $schema['publisher']['@type'] = 'Organization';
                $schema['publisher']['logo']  = Outpaceseo_Schema_Template::get_image_schema($data['site-logo'], 'ImageObject');
            }

            if (isset($data['image']) && !empty($data['image'])) {
                $schema['thumbnailUrl'] = Outpaceseo_Schema_Template::get_image_schema($data['image'], 'URL');
            }

            if (isset($data['upload-date']) && !empty($data['upload-date'])) {
                $schema['uploadDate'] = wp_strip_all_tags($data['upload-date']);
            }

            if (isset($data['duration']) && !empty($data['duration'])) {
                $schema['duration'] = wp_strip_all_tags($data['duration']);
            }

            if (isset($data['content-url']) && !empty($data['content-url'])) {
                $schema['contentUrl'] = esc_url($data['content-url']);
            }

            if (isset($data['embed-url']) && !empty($data['embed-url'])) {
                $schema['embedUrl'] = esc_url($data['embed-url']);
            }

            if (isset($data['expires-date']) && !empty($data['expires-date'])) {
                $schema['expires'] = wp_strip_all_tags($data['expires-date']);
            }

            if (isset($data['interaction-count']) && !empty($data['interaction-count'])) {
                $schema['interactionCount'] = wp_strip_all_tags($data['interaction-count']);
            }

            return apply_filters('outpaceseo_schema_video_object', $schema, $data, $post);
        }
    }
}
