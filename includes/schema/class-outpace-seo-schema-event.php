<?php

/**
 * Schemas Template.
 */

if (!class_exists('Outpaceseo_Schema_Event')) {

    /**
     * Outpaceseo Schemas Initialization
     */
    class Outpaceseo_Schema_Event
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
            if (empty($data['schema-type'])) {
                $data['schema-type'] = 'Event';
            }

            $schema['@context'] = 'https://schema.org';

            if (isset($data['schema-type']) && !empty($data['schema-type'])) {
                $schema['@type'] = $data['schema-type'];
            }

            $schema = self::prepare_basics($schema, $data);
            $schema = self::prepare_attendence_mode($schema, $data);
            $schema = self::prepare_location_by_attendence_mode($schema, $data);
            $schema = self::prepare_dates($schema, $data);
            $schema = self::prepare_offer($schema, $data);
            $schema = self::prepare_performer($schema, $data);

            return apply_filters('outpaceseo_schema_event', $schema, $data, $post);
        }

        /**
         * Prepare location by attendence mode schema field.
         *
         * @param  array $schema schema.
         * @param  array $data data.
         * @return array
         */
        public static function prepare_location_by_attendence_mode($schema, $data)
        {

            if ('OnlineEventAttendanceMode' === $data['event-attendance-mode']) {
                $schema = self::prepare_location($schema, $data, false);
            } elseif ('OfflineEventAttendanceMode' === $data['event-attendance-mode']) {
                $schema = self::prepare_location($schema, $data, true);
            } else {
                $online_location    = self::prepare_location($schema, $data, false);
                $offline_location   = self::prepare_location($schema, $data, true);
                $schema['location'] = array($online_location['location'], $offline_location['location']);
            }

            return $schema;
        }

        /**
         * Prepare location schema field.
         *
         * @param  array   $schema schema.
         * @param  array   $data data.
         * @param  boolean $offline offline.
         * @return array
         */
        public static function prepare_location($schema, $data, $offline = true)
        {

            if ($offline) {
                if (isset($data['location']) && !empty($data['location'])) {
                    $schema['location']['@type'] = 'Place';
                    $schema['location']['name']  = wp_strip_all_tags($data['location']);
                }

                $schema['location']['@type']            = 'Place';
                $schema['location']['address']['@type'] = 'PostalAddress';
                if (isset($data['location-street']) && !empty($data['location-street'])) {
                    $schema['location']['address']['streetAddress'] = wp_strip_all_tags($data['location-street']);
                }
                if (isset($data['location-locality']) && !empty($data['location-locality'])) {
                    $schema['location']['address']['addressLocality'] = wp_strip_all_tags($data['location-locality']);
                }
                if (isset($data['location-postal']) && !empty($data['location-postal'])) {
                    $schema['location']['address']['postalCode'] = wp_strip_all_tags($data['location-postal']);
                }
                if (isset($data['location-region']) && !empty($data['location-region'])) {
                    $schema['location']['address']['addressRegion'] = wp_strip_all_tags($data['location-region']);
                }
                if (isset($data['location-country']) && !empty($data['location-country'])) {

                    $schema['location']['address']['addressCountry']['@type'] = 'Country';
                    $schema['location']['address']['addressCountry']['name']  = wp_strip_all_tags($data['location-country']);
                }
            } else {
                $schema['location']['@type'] = 'VirtualLocation';
                $schema['location']['url']   = esc_url($data['online-location']);
            }
            return $schema;
        }

        /**
         * Prepare Offer schema field.
         *
         * @param  array $schema schema.
         * @param  array $data data.
         * @return array
         */
        public static function prepare_offer($schema, $data)
        {

            $schema['offers']['@type'] = 'Offer';
            $schema['offers']['price'] = '0';
            if (isset($data['price']) && !empty($data['price'])) {
                $schema['offers']['price'] = wp_strip_all_tags($data['price']);
            }
            if (isset($data['avail']) && !empty($data['avail'])) {
                $schema['offers']['availability'] = wp_strip_all_tags($data['avail']);
            }
            if (isset($data['currency']) && !empty($data['currency'])) {
                $schema['offers']['priceCurrency'] = wp_strip_all_tags($data['currency']);
            }
            if (isset($data['valid-from']) && !empty($data['valid-from'])) {
                $schema['offers']['validFrom'] = wp_strip_all_tags($data['valid-from']);
            }
            if (isset($data['ticket-buy-url']) && !empty($data['ticket-buy-url'])) {
                $schema['offers']['url'] = esc_url($data['ticket-buy-url']);
            }

            return $schema;
        }

        /**
         * Prepare Performer schema field.
         *
         * @param  array $schema schema.
         * @param  array $data data.
         * @return array
         */
        public static function prepare_performer($schema, $data)
        {

            if (isset($data['performer']) && !empty($data['performer'])) {
                $schema['performer']['@type'] = 'Person';
                $schema['performer']['name']  = wp_strip_all_tags($data['performer']);
            }
            $schema['organizer']['@type'] = 'Organization';
            if (isset($data['event-organizer-name']) && !empty($data['event-organizer-name'])) {
                $schema['organizer']['name'] = wp_strip_all_tags($data['event-organizer-name']);
            }
            if (isset($data['event-organizer-url']) && !empty($data['event-organizer-url'])) {
                $schema['organizer']['url'] = wp_strip_all_tags($data['event-organizer-url']);
            }

            return $schema;
        }

        /**
         * Prepare dates schema field.
         *
         * @param  array $schema schema.
         * @param  array $data data.
         * @return array
         */
        public static function prepare_dates($schema, $data)
        {

            $start_date = gmdate(DATE_ISO8601, strtotime($data['start-date']));
            if (isset($start_date) && !empty($start_date)) {
                $schema['startDate'] = wp_strip_all_tags($start_date);
            }

            if (isset($data['end-date']) && !empty($data['end-date'])) {
                $schema['endDate'] = wp_strip_all_tags($data['end-date']);
            }

            if ('EventRescheduled' === $data['event-status']) {
                $schema['previousStartDate'] = wp_strip_all_tags($data['previous-date']);
            }

            return $schema;
        }

        /**
         * Prepare attendence schema field.
         *
         * @param  array $schema schema.
         * @param  array $data data.
         * @return array
         */
        public static function prepare_attendence_mode($schema, $data)
        {
            if (isset($data['schema-type']) && isset($data['event-attendance-mode']) && !empty($data['event-attendance-mode'])) {

                $schema['eventAttendanceMode'] = 'https://schema.org/' . wp_strip_all_tags($data['event-attendance-mode']);
            }

            return $schema;
        }

        /**
         * Prepare basic schema field.
         *
         * @param  array $schema schema.
         * @param  array $data data.
         * @return array
         */
        public static function prepare_basics($schema, $data)
        {

            if (isset($data['name']) && !empty($data['name'])) {
                $schema['name'] = wp_strip_all_tags($data['name']);
            }

            if (isset($data['event-status']) && !empty($data['event-status'])) {
                $schema['eventStatus'] = 'https://schema.org/' . wp_strip_all_tags($data['event-status']);
            }

            if (isset($data['image']) && !empty($data['image'])) {
                $schema['image'] = Outpaceseo_Schema_Template::get_image_schema($data['image']);
            }

            if (isset($data['description']) && !empty($data['description'])) {
                $schema['description'] = wp_strip_all_tags($data['description']);
            }

            return $schema;
        }
    }
}
