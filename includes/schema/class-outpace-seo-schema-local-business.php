<?php

/**
 * Schemas Template.
 */

if (!class_exists('Outpaceseo_Schema_Local_Business')) {

    /**
     * Outpaceseo Schemas Initialization
     */
    class Outpaceseo_Schema_Local_Business
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

            if (isset($data['schema-type']) && !empty($data['schema-type']) && 'ProfessionalService' !== $data['schema-type']) {
                $schema['@type'] = $data['schema-type'];
            } else {
                $schema['@type'] = 'LocalBusiness';
            }

            if (isset($data['name']) && !empty($data['name'])) {
                $schema['name'] = wp_strip_all_tags($data['name']);
            }

            if (isset($data['image']) && !empty($data['image'])) {
                $schema['image'] = Outpaceseo_Schema_Template::get_image_schema($data['image']);
            }

            if (isset($data['telephone']) && !empty($data['telephone'])) {
                $schema['telephone'] = wp_strip_all_tags($data['telephone']);
            }

            if (isset($data['url']) && !empty($data['url'])) {
                $schema['url'] = wp_strip_all_tags($data['url']);
            }

            if ((isset($data['location-street']) && !empty($data['location-street'])) ||
                (isset($data['location-locality']) && !empty($data['location-locality'])) ||
                (isset($data['location-postal']) && !empty($data['location-postal'])) ||
                (isset($data['location-region']) && !empty($data['location-region'])) ||
                (isset($data['location-country']) && !empty($data['location-country']))
            ) {

                $schema['address']['@type'] = 'PostalAddress';

                if (isset($data['location-street']) && !empty($data['location-street'])) {
                    $schema['address']['streetAddress'] = wp_strip_all_tags($data['location-street']);
                }
                if (isset($data['location-locality']) && !empty($data['location-locality'])) {
                    $schema['address']['addressLocality'] = wp_strip_all_tags($data['location-locality']);
                }
                if (isset($data['location-postal']) && !empty($data['location-postal'])) {
                    $schema['address']['postalCode'] = wp_strip_all_tags($data['location-postal']);
                }
                if (isset($data['location-region']) && !empty($data['location-region'])) {
                    $schema['address']['addressRegion'] = wp_strip_all_tags($data['location-region']);
                }
                if (isset($data['location-country']) && !empty($data['location-country'])) {
                    $schema['address']['addressCountry'] = wp_strip_all_tags($data['location-country']);
                }
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

            if (isset($data['price-range']) && !empty($data['price-range'])) {
                $schema['priceRange'] = wp_strip_all_tags($data['price-range']);
            }

            if (isset($data['hours-specification']) && !empty($data['hours-specification'])) {
                foreach ($data['hours-specification'] as $key => $value) {
                    $schema['openingHoursSpecification'][$key]['@type'] = 'OpeningHoursSpecification';
                    $days = explode(',', $value['days']);
                    $days = array_map('trim', $days);
                    $schema['openingHoursSpecification'][$key]['dayOfWeek'] = $days;
                    $schema['openingHoursSpecification'][$key]['opens']     = $value['opens'];
                    $schema['openingHoursSpecification'][$key]['closes']    = $value['closes'];
                }
            }
            if (isset($data['geo-latitude']) && isset($data['geo-longitude'])) {
                $schema['geo']['@type']     = 'GeoCoordinates';
                $schema['geo']['latitude']  = wp_strip_all_tags($data['geo-latitude']);
                $schema['geo']['longitude'] = wp_strip_all_tags($data['geo-longitude']);
            }
            $contact_type       = BSF_Outpaceseo_Pro_Helper::$settings['wp-schema-pro-corporate-contact'];
            $contact_hear       = isset($contact_type['contact-hear']) ? $contact_type['contact-hear'] : '';
            $contact_toll       = isset($contact_type['contact-toll']) ? $contact_type['contact-toll'] : '';
            $contact_point_type = $contact_hear . ' ' . $contact_toll;
            $contact_point_type = explode(' ', $contact_point_type);
            if ('1' === $contact_type['cp-schema-type'] && true === apply_filters('outpaceseo_contactpoint_local_business_schema_enabled', true) && isset($contact_type['contact-type']) && !empty($contact_type['contact-type'])) {
                $schema['ContactPoint']['@type'] = 'ContactPoint';

                if (isset($contact_type['contact-type']) && !empty($contact_type['contact-type'])) {
                    $schema['ContactPoint']['contactType'] = wp_strip_all_tags($contact_type['contact-type']);
                }
                if (isset($contact_type['telephone']) && !empty($contact_type['telephone'])) {
                    $schema['ContactPoint']['telephone'] = wp_strip_all_tags($contact_type['telephone']);
                }
                if (isset($contact_type['url']) && !empty($contact_type['url'])) {
                    $schema['ContactPoint']['url'] = esc_url($contact_type['url']);
                }
                if (isset($contact_type['email']) && !empty($contact_type['email'])) {
                    $schema['ContactPoint']['email'] = wp_strip_all_tags($contact_type['email']);
                }
                if (isset($contact_type['areaServed']) && !empty($contact_type['areaServed'])) {
                    $language = explode(',', $contact_type['areaServed']);
                    foreach ($language as $key => $value) {
                        $schema['ContactPoint']['areaServed'][$key] = wp_strip_all_tags($value);
                    }
                }
                foreach ($contact_point_type  as $key => $value) {
                    $schema['ContactPoint']['contactOption'][$key] = wp_strip_all_tags($value);
                }
                if (isset($contact_type['availableLanguage']) && !empty($contact_type['availableLanguage'])) {
                    $schema['ContactPoint']['availableLanguage'] = wp_strip_all_tags($contact_type['availableLanguage']);
                }
            }

            return apply_filters('outpaceseo_schema_local_business', $schema, $data, $post);
        }
    }
}
