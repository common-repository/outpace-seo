<?php

/**
 * Schemas Template.
 */

if (!class_exists('Outpaceseo_Schema_Article')) {

    /**
     * Outpaceseo Schemas Initialization

     */
    class Outpaceseo_Schema_Article
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
            $schema           = array();
            if (empty($data['schema-type'])) {
                $data['schema-type'] = 'Article';
            }

            $schema['@context'] = 'https://schema.org';
            if (isset($data['schema-type']) && !empty($data['schema-type'])) {
                $schema['@type'] = $data['schema-type'];
            }

            if (isset($data['main-entity']) && !empty($data['main-entity'])) {
                $schema['mainEntityOfPage']['@type'] = 'WebPage';
                $schema['mainEntityOfPage']['@id']   = esc_url($data['main-entity']);
            }

            if (isset($data['name']) && !empty($data['name'])) {
                $schema['headline'] = wp_strip_all_tags($data['name']);
            }

            if (isset($data['image']) && !empty($data['image'])) {
                $schema['image'] = Outpaceseo_Schema_Template::get_image_schema($data['image']);
            }

            if (isset($data['published-date']) && !empty($data['published-date'])) {
                $schema['datePublished'] = wp_strip_all_tags($data['published-date']);
            }

            if (isset($data['modified-date']) && !empty($data['modified-date'])) {
                $schema['dateModified'] = wp_strip_all_tags($data['modified-date']);
            }

            if (isset($data['author']) && !empty($data['author'])) {
                $schema['author']['@type'] = 'Person';
                $schema['author']['name']  = wp_strip_all_tags($data['author']);
                if (isset($data['author-url']) && !empty($data['author-url'])) {
                    $schema['author']['url'] = wp_strip_all_tags($data['author-url']);
                }
            }

            if (isset($data['orgnization-name']) && !empty($data['orgnization-name'])) {
                $schema['publisher']['@type'] = 'Organization';
                $schema['publisher']['name']  = wp_strip_all_tags($data['orgnization-name']);
            }

            if (isset($data['site-logo']) && !empty($data['site-logo'])) {
                $schema['publisher']['@type'] = 'Organization';
                $schema['publisher']['logo']  = Outpaceseo_Schema_Template::get_image_schema($data['site-logo'], 'ImageObject2');
            } else {
                $logo_id = get_post_thumbnail_id($post['ID']);
                if ($logo_id) {
                    add_filter('intermediate_image_sizes_advanced', 'Outpaceseo_Schema_Template::logo_image_sizes', 10, 2);
                    $logo_image = wp_get_attachment_image_src($logo_id, 'aiosrs-logo-size');
                    if (isset($logo_image[3]) && 1 !== $logo_image[3]) {
                        Outpaceseo_Schema_Template::generate_logo_by_width($logo_id);
                        $logo_image = wp_get_attachment_image_src($logo_id, 'aiosrs-logo-size');
                    }
                    remove_filter('intermediate_image_sizes_advanced', 'Outpaceseo_Schema_Template::logo_image_sizes', 10, 2);
                    $schema['publisher']['@type'] = 'Organization';
                    $schema['publisher']['logo']  = Outpaceseo_Schema_Template::get_image_schema($logo_image, 'ImageObject');
                }
            }

            if (isset($data['description']) && !empty($data['description'])) {
                $schema['description'] = wp_strip_all_tags($data['description']);
            }

            return apply_filters('outpaceseo_schema_article', $schema, $data, $post);
        }
    }
}
