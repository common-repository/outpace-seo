<?php

/**
 * Schemas Template.
 */

if (!class_exists('Outpaceseo_Schema_Software_Application')) {

    /**
     * Outpaceseo Schemas Initialization
     */
    class Outpaceseo_Schema_Software_Application
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
            $schema['@type']    = 'SoftwareApplication';

            if (isset($data['name']) && !empty($data['name'])) {
                $schema['name'] = wp_strip_all_tags($data['name']);
            }

            if (isset($data['operating-system']) && !empty($data['operating-system'])) {
                $schema['operatingSystem'] = wp_strip_all_tags($data['operating-system']);
            }

            if (isset($data['category']) && !empty($data['category'])) {
                $schema['applicationCategory'] = wp_strip_all_tags($data['category']);
            }

            if (isset($data['image']) && !empty($data['image'])) {
                $schema['image'] = Outpaceseo_Schema_Template::get_image_schema($data['image']);
            }

            if ((isset($data['rating']) && !empty($data['rating'])) ||
                (isset($data['review-count']) && !empty($data['review-count']))
            ) {

                $schema['aggregateRating']['@type'] = 'AggregateRating';

                if (isset($data['rating']) && !empty($data['rating'])) {
                    $schema['aggregateRating']['ratingValue'] = wp_strip_all_tags($data['rating']);
                }
                if (isset($data['review-count']) && !empty($data['review-count'])) {
                    $schema['aggregateRating']['reviewCount'] = wp_strip_all_tags($data['review-count']);
                }
            }

            $schema['offers']['@type'] = 'Offer';
            $schema['offers']['price'] = '0';

            if (isset($data['price']) && !empty($data['price'])) {
                $schema['offers']['price'] = wp_strip_all_tags($data['price']);
            }

            if (isset($data['currency']) && !empty($data['currency'])) {
                $schema['offers']['priceCurrency'] = wp_strip_all_tags($data['currency']);
            }

            return apply_filters('outpaceseo_schema_software_application', $schema, $data, $post);
        }
    }
}
