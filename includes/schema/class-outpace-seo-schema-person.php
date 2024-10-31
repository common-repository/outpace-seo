<?php

/**
 * Schemas Template.
 */

if (!class_exists('Outpaceseo_Schema_Person')) {

    /**
     * Outpaceseo Schemas Initialization
     */
    class Outpaceseo_Schema_Person
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
            $schema['@type']    = 'Person';

            if (isset($data['name']) && !empty($data['name'])) {
                $schema['name'] = wp_strip_all_tags($data['name']);
            }

            if ((isset($data['street']) && !empty($data['street'])) ||
                (isset($data['locality']) && !empty($data['locality'])) ||
                (isset($data['postal']) && !empty($data['postal'])) ||
                (isset($data['region']) && !empty($data['region']))
            ) {

                $schema['address']['@type'] = 'PostalAddress';

                if (isset($data['locality']) && !empty($data['locality'])) {
                    $schema['address']['addressLocality'] = wp_strip_all_tags($data['locality']);
                }

                if (isset($data['region']) && !empty($data['region'])) {
                    $schema['address']['addressRegion'] = wp_strip_all_tags($data['region']);
                }

                if (isset($data['postal']) && !empty($data['postal'])) {
                    $schema['address']['postalCode'] = wp_strip_all_tags($data['postal']);
                }

                if (isset($data['street']) && !empty($data['street'])) {
                    $schema['address']['streetAddress'] = wp_strip_all_tags($data['street']);
                }
            }

            if (isset($data['email']) && !empty($data['email'])) {
                $schema['email'] = wp_strip_all_tags($data['email']);
            }

            if (isset($data['gender']) && !empty($data['gender'])) {
                $schema['gender'] = wp_strip_all_tags($data['gender']);
            }

            if (isset($data['dob']) && !empty($data['dob'])) {
                $date_informat       = gmdate('Y.m.d', strtotime($data['dob']));
                $schema['birthDate'] = wp_strip_all_tags($date_informat);
            }

            if (isset($data['member']) && !empty($data['member'])) {
                $schema['memberOf'] = wp_strip_all_tags($data['member']);
            }

            if (isset($data['nationality']) && !empty($data['nationality'])) {
                $schema['nationality'] = wp_strip_all_tags($data['nationality']);
            }

            if (isset($data['image']) && !empty($data['image'])) {
                $schema['image'] = Outpaceseo_Schema_Template::get_image_schema($data['image']);
            }

            if (isset($data['job-title']) && !empty($data['job-title'])) {
                $schema['jobTitle'] = wp_strip_all_tags($data['job-title']);
            }

            if (isset($data['telephone']) && !empty($data['telephone'])) {
                $schema['telephone'] = wp_strip_all_tags($data['telephone']);
            }

            if (isset($data['homepage-url']) && !empty($data['homepage-url'])) {
                $schema['url'] = esc_url($data['homepage-url']);
            }

            if (isset($data['add-url']) && !empty($data['add-url'])) {
                foreach ($data['add-url'] as $key => $value) {
                    if (isset($value['same-as']) && !empty($value['same-as'])) {
                        $schema['sameAs'][$key] = esc_url($value['same-as']);
                    }
                }
            }
            $contact_type       = array();
            $cp_schema_type       = isset($contact_type['cp-schema-type']) ? $contact_type['cp-schema-type'] : '';
            $contact_hear       = isset($contact_type['contact-hear']) ? $contact_type['contact-hear'] : '';
            $contact_toll       = isset($contact_type['contact-toll']) ? $contact_type['contact-toll'] : '';
            $contact_point_type = $contact_hear . ' ' . $contact_toll;
            $contact_point_type = explode(' ', $contact_point_type);
            if ('1' === $cp_schema_type && true === apply_filters('outpaceseo_contactpoint_person_schema_enabled', true) && isset($contact_type['contact-type']) && !empty($contact_type['contact-type'])) {
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

            return apply_filters('outpaceseo_schema_person', $schema, $data, $post);
        }
    }
}
