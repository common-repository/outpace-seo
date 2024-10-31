<?php

/**
 * Schemas Template.
 */

if (!class_exists('Outpaceseo_Schema_Course')) {

    /**
     * Outpaceseo Schemas Initialization
     */
    class Outpaceseo_Schema_Course
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
            $schema['@type']    = 'Course';

            if (empty($data['schema-type'])) {
                $data['schema-type'] = 'Course';
            }

            if (isset($data['name']) && !empty($data['name'])) {
                $schema['name'] = wp_strip_all_tags($data['name']);
            }

            if (isset($data['course-code']) && !empty($data['course-code'])) {
                $schema['courseCode'] = wp_strip_all_tags($data['course-code']);
            }

            if (isset($data['description']) && !empty($data['description'])) {
                $schema['description'] = wp_strip_all_tags($data['description']);
            }

            if (isset($data['course-instance']) && !empty($data['course-instance'])) {

                foreach ($data['course-instance'] as $key => $value) {

                    if (
                        isset($value['name']) && !empty($value['name']) ||
                        isset($value['description']) && !empty($value['description']) ||
                        isset($value['start-date']) && !empty($value['start-date']) ||
                        isset($value['location-address']) && !empty($value['location-address'])
                    ) {

                        $schema['hasCourseInstance'][$key]['@type'] = 'CourseInstance';
                        if (isset($value['name']) && !empty($value['name'])) {
                            $schema['hasCourseInstance'][$key]['name'] = wp_strip_all_tags($value['name']);
                        }

                        if (isset($value['description']) && !empty($value['description'])) {
                            $schema['hasCourseInstance'][$key]['description'] = wp_strip_all_tags($value['description']);
                        }

                        if (isset($value['course-mode']) && !empty($value['course-mode'])) {
                            $schema['hasCourseInstance'][$key]['courseMode'] = wp_strip_all_tags($value['course-mode']);
                        }

                        if (isset($value['event-status']) && !empty($value['event-status'])) {
                            $schema['hasCourseInstance'][$key]['eventStatus'] = wp_strip_all_tags($value['event-status']);
                        }

                        if (isset($value['event-attendance-mode']) && !empty($value['event-attendance-mode'])) {
                            $schema['hasCourseInstance'][$key]['eventAttendanceMode'] = wp_strip_all_tags($value['event-attendance-mode']);
                        }

                        if (isset($value['start-date']) && !empty($value['start-date'])) {
                            if ('OfflineEventAttendanceMode' !== isset($value['event-attendance-mode'])) {
                                $start_date                                       = gmdate(DATE_ISO8601, strtotime($value['start-date']));
                                $schema['hasCourseInstance'][$key]['startDate'] = wp_strip_all_tags($start_date);
                            } else {
                                $schema['hasCourseInstance'][$key]['startDate'] = wp_strip_all_tags($value['start-date']);
                            }
                        }

                        if (isset($value['end-date']) && !empty($value['end-date'])) {
                            $schema['hasCourseInstance'][$key]['endDate'] = wp_strip_all_tags($value['end-date']);
                        }

                        if (isset($value['previous-date']) && !empty($value['previous-date']) && 'EventRescheduled' === $value['event-status']) {
                            $schema['hasCourseInstance'][$key]['previousStartDate'] = wp_strip_all_tags($value['previous-date']);
                        }

                        if (isset($value['online-location']) && !empty($value['online-location']) && 'OfflineEventAttendanceMode' !== $value['event-attendance-mode'] || 'MixedEventAttendanceMode' === $value['event-attendance-mode']) {
                            $schema['hasCourseInstance'][$key]['location']['@type'] = 'VirtualLocation';
                            $schema['hasCourseInstance'][$key]['location']['url']   = esc_url($value['online-location']);
                        }

                        if (isset($value['image']) && !empty($value['image'])) {
                            $schema['hasCourseInstance'][$key]['image'] = Outpaceseo_Schema_Template::get_image_schema($value['image']);
                        }

                        if (isset($value['location-name']) && !empty($value['location-name']) && 'OnlineEventAttendanceMode' !== $value['event-attendance-mode']) {
                            $schema['hasCourseInstance'][$key]['location']['@type'] = 'Place';
                            $schema['hasCourseInstance'][$key]['location']['name']  = wp_strip_all_tags($value['location-name']);
                        }

                        if (isset($value['location-address']) && !empty($value['location-address']) && 'OnlineEventAttendanceMode' !== $value['event-attendance-mode']) {
                            $schema['hasCourseInstance'][$key]['location']['@type']   = 'Place';
                            $schema['hasCourseInstance'][$key]['location']['address'] = wp_strip_all_tags($value['location-address']);
                        }

                        $schema['hasCourseInstance'][$key]['organizer']['@type'] = 'Organization';
                        if (isset($value['course-organizer-name']) && !empty($value['course-organizer-name'])) {
                            $schema['hasCourseInstance'][$key]['organizer']['name'] = wp_strip_all_tags($value['course-organizer-name']);
                        }
                        if (isset($value['course-organizer-url']) && !empty($value['course-organizer-url'])) {
                            $schema['hasCourseInstance'][$key]['organizer']['url'] = wp_strip_all_tags($value['course-organizer-url']);
                        }

                        $schema['hasCourseInstance'][$key]['offers']['@type'] = 'Offer';
                        $schema['hasCourseInstance'][$key]['offers']['price'] = '0';

                        if (isset($value['price']) && !empty($value['price'])) {
                            $schema['hasCourseInstance'][$key]['offers']['price'] = wp_strip_all_tags($value['price']);
                        }

                        if (isset($value['currency']) && !empty($value['currency'])) {
                            $schema['hasCourseInstance'][$key]['offers']['priceCurrency'] = wp_strip_all_tags($value['currency']);
                        }

                        if (isset($value['url']) && !empty($value['url'])) {
                            $schema['hasCourseInstance'][$key]['offers']['url'] = wp_strip_all_tags($value['url']);
                        }

                        if (isset($value['valid-from']) && !empty($value['valid-from'])) {
                            $schema['hasCourseInstance'][$key]['offers']['validFrom'] = wp_strip_all_tags($value['valid-from']);
                        }

                        if (isset($value['avail']) && !empty($value['avail'])) {
                            $schema['hasCourseInstance'][$key]['offers']['availability'] = wp_strip_all_tags($value['avail']);
                        }

                        if (isset($value['performer']) && !empty($value['performer'])) {
                            $schema['hasCourseInstance'][$key]['performer']['@type'] = 'Person';
                            $schema['hasCourseInstance'][$key]['performer']['name']  = wp_strip_all_tags($value['performer']);
                        }
                    }
                }
            }

            if ((isset($data['orgnization-name']) && !empty($data['orgnization-name'])) ||
                (isset($data['same-as']) && !empty($data['same-as']))
            ) {

                $schema['provider']['@type'] = 'Organization';

                if (isset($data['orgnization-name']) && !empty($data['orgnization-name'])) {
                    $schema['provider']['name'] = wp_strip_all_tags($data['orgnization-name']);
                }
                if (isset($data['same-as']) && !empty($data['same-as'])) {
                    $schema['provider']['sameAs'] = esc_url($data['same-as']);
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

            return apply_filters('outpaceseo_schema_course', $schema, $data, $post);
        }
    }
}
