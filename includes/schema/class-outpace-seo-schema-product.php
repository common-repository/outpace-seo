<?php

/**
 * Schemas Template.
 */

if (!class_exists('Outpaceseo_Schema_Product')) {

    /**
     * Outpaceseo Schemas Initialization
     */
    class Outpaceseo_Schema_Product
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
            $schema             = array();
            $schema['@context'] = 'https://schema.org';
            $schema['@type']    = 'Product';
            if (isset($data['name']) && !empty($data['name'])) {
                $schema['name'] = wp_strip_all_tags($data['name']);
            }

            if (isset($data['image']) && !empty($data['image'])) {
                $schema['image'] = Outpaceseo_Schema_Template::get_image_schema($data['image']);
            }

            if (isset($data['description']) && !empty($data['description'])) {
                $schema['description'] = wp_strip_all_tags($data['description']);
            }

            if (isset($data['sku']) && !empty($data['sku'])) {
                $schema['sku'] = wp_strip_all_tags($data['sku']);
            }
            if (isset($data['mpn']) && !empty($data['mpn'])) {
                $schema['mpn'] = wp_strip_all_tags($data['mpn']);
            }
            if (isset($data['brand-name']) && !empty($data['brand-name'])) {
                $schema['brand']['@type'] = 'Organization';
                $schema['brand']['name']  = wp_strip_all_tags($data['brand-name']);
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
            if (apply_filters('outpaceseo_remove_product_offers', true)) {
                $schema['offers']['@type'] = 'Offer';
                $schema['offers']['price'] = '0';
                if (isset($data['price']) && !empty($data['price'])) {
                    $schema['offers']['price'] = wp_strip_all_tags($data['price']);
                }
                if (isset($data['price-valid-until']) && !empty($data['price-valid-until'])) {
                    $schema['offers']['priceValidUntil'] = wp_strip_all_tags($data['price-valid-until']);
                }

                if (isset($data['url']) && !empty($data['url'])) {
                    $schema['offers']['url'] = esc_url($data['url']);
                }

                if ((isset($data['currency']) && !empty($data['currency'])) ||
                    (isset($data['avail']) && !empty($data['avail']))
                ) {

                    if (isset($data['currency']) && !empty($data['currency'])) {
                        $schema['offers']['priceCurrency'] = wp_strip_all_tags($data['currency']);
                    }
                    if (isset($data['avail']) && !empty($data['avail'])) {
                        $schema['offers']['availability'] = wp_strip_all_tags($data['avail']);
                    }
                }
            }

            if (apply_filters('outpaceseo_remove_product_reviews', true) && isset($data['product-review']) && !empty($data['product-review'])) {
                foreach ($data['product-review'] as $key => $value) {
                    if ((isset($value['reviewer-name']) && !empty($value['reviewer-name'])) && (isset($value['product-rating']) && !empty($value['product-rating']))) {
                        $schema['review'][$key]['@type'] = 'Review';
                        if (isset($value['reviewer-name']) && !empty($value['reviewer-name'])) {
                            $schema['review'][$key]['author']['name'] = wp_strip_all_tags($value['reviewer-name']);
                            if (isset($value['reviewer-type']) && !empty($value['reviewer-type'])) {
                                $schema['review'][$key]['author']['@type'] = wp_strip_all_tags($value['reviewer-type']);
                            } else {
                                $schema['review'][$key]['author']['@type'] = 'Person';
                            }
                        }

                        if (isset($value['product-rating']) && !empty($value['product-rating'])) {
                            $schema['review'][$key]['reviewRating']['@type']       = 'Rating';
                            $schema['review'][$key]['reviewRating']['ratingValue'] = wp_strip_all_tags($value['product-rating']);
                        }

                        if (isset($value['review-body']) && !empty($value['review-body'])) {
                            $schema['review'][$key]['reviewBody'] = wp_strip_all_tags($value['review-body']);
                        }
                    }
                }
            }

            if (defined('WC_VERSION') && apply_filters('outpaceseo_add_woocommerce_review', false)) {
                $comments = get_comments(
                    array(
                        'number'      => 5,
                        'post_id'     => $post['ID'],
                        'status'      => 'approve',
                        'post_status' => 'publish',
                        'post_type'   => 'product',
                        'parent'      => 0,
                        'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                            array(
                                'key'     => 'rating',
                                'type'    => 'NUMERIC',
                                'compare' => '>',
                                'value'   => 0,
                            ),
                        ),
                    )
                );

                if ($comments) {
                    foreach ($comments as $key => $comment) {
                        $schema['review'][$key]['@type']                           = 'Review';
                        $schema['review'][$key]['reviewRating']['@type']       = 'Rating';
                        $schema['review'][$key]['reviewRating']['ratingValue'] = get_comment_meta($comment->comment_ID, 'rating', true);
                        $schema['review'][$key]['author']['@type']             = 'Person';
                        $schema['review'][$key]['author']['name']              = get_comment_author($comment);
                        $schema['review'][$key]['reviewBody']                  = get_comment_text($comment);
                    }
                }
            }

            return apply_filters('outpaceseo_outpaceseoduct', $schema, $data, $post);
        }
    }
}
