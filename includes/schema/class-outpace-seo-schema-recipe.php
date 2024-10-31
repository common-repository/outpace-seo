<?php

/**
 * Schemas Template.
 */

if (!class_exists('Outpaceseo_Schema_Recipe')) {

    /**
     * Outpaceseo Schemas Initialization
     */
    class Outpaceseo_Schema_Recipe
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
            $schema['@type']    = 'Recipe';

            if (isset($data['name']) && !empty($data['name'])) {
                $schema['name'] = wp_strip_all_tags($data['name']);
            }

            if (isset($data['image']) && !empty($data['image'])) {
                $schema['image'] = Outpaceseo_Schema_Template::get_image_schema($data['image']);
            }
            if (isset($data['reviewer-type']) && !empty($data['reviewer-type'])) {
                $schema['author']['@type'] = wp_strip_all_tags($data['reviewer-type']);
            } else {
                $schema['author']['@type'] = 'Person';
            }
            if (isset($data['author']) && !empty($data['author'])) {
                $schema['author']['name'] = wp_strip_all_tags($data['author']);
            }

            if (isset($data['description']) && !empty($data['description'])) {
                $schema['description'] = wp_strip_all_tags($data['description']);
            }

            if (isset($data['preperation-time']) && !empty($data['preperation-time'])) {
                $schema['prepTime'] = wp_strip_all_tags($data['preperation-time']);
            }

            if (isset($data['cook-time']) && !empty($data['cook-time'])) {
                $schema['cookTime'] = wp_strip_all_tags($data['cook-time']);
            }
            if (isset($data['recipe-yield']) && !empty($data['recipe-yield'])) {
                $schema['recipeYield'] = esc_html($data['recipe-yield']);
            }
            if (isset($data['recipe-keywords']) && !empty($data['recipe-keywords'])) {
                $schema['keywords'] = wp_strip_all_tags($data['recipe-keywords']);
            }

            if (isset($data['recipe-category']) && !empty($data['recipe-category'])) {
                $schema['recipeCategory'] = wp_strip_all_tags($data['recipe-category']);
            }

            if (isset($data['recipe-cuisine']) && !empty($data['recipe-cuisine'])) {
                $schema['recipeCuisine'] = wp_strip_all_tags($data['recipe-cuisine']);
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

            if (isset($data['nutrition']) && !empty($data['nutrition'])) {
                $schema['nutrition']['@type']    = 'NutritionInformation';
                $schema['nutrition']['calories'] = wp_strip_all_tags($data['nutrition']);
            }

            if (isset($data['ingredients']) && !empty($data['ingredients'])) {
                $recipe_ingredients = explode(',', $data['ingredients']);
                foreach ($recipe_ingredients as $key => $value) {
                    $schema['recipeIngredient'][$key] = wp_strip_all_tags($value);
                }
            }

            if (isset($data['recipe-instructions']) && !empty($data['recipe-instructions'])) {
                foreach ($data['recipe-instructions'] as $key => $value) {

                    if (isset($value['steps']) && !empty($value['steps'])) {

                        $schema['recipeInstructions'][$key]['@type'] = 'HowToStep';
                        $schema['recipeInstructions'][$key]['text']  = wp_strip_all_tags($value['steps']);
                        if (isset($value['name']) && !empty($value['name'])) {

                            $schema['recipeInstructions'][$key]['name'] = wp_strip_all_tags($value['name']);
                        }
                        if (isset($value['url']) && !empty($value['url'])) {
                            $schema['recipeInstructions'][$key]['url'] = wp_strip_all_tags($value['url']);
                        }
                        if (isset($value['image']) && !empty($value['image'])) {
                            $schema['recipeInstructions'][$key]['image'] = Outpaceseo_Schema_Template::get_image_schema($value['image'], 'URL');
                        }
                    }
                }
            }

            if (isset($data['recipe-video']) && !empty($data['recipe-video'])) {

                foreach ($data['recipe-video'] as $key => $value) {
                    if (isset($value['video-name']) && !empty($value['video-name'])) {
                        $schema['video'][$key]['@type'] = 'VideoObject';
                        if (isset($value['video-name']) && !empty($value['video-name'])) {

                            $schema['video'][$key]['name'] = wp_strip_all_tags($value['video-name']);
                        }
                        if (isset($value['video-desc']) && !empty($value['video-desc'])) {
                            $schema['video'][$key]['description'] = wp_strip_all_tags($value['video-desc']);
                        }
                        if (isset($value['video-image']) && !empty($value['video-image'])) {
                            $schema['video'][$key]['thumbnailUrl'] = Outpaceseo_Schema_Template::get_image_schema($value['video-image'], 'URL');
                        }
                        if (isset($value['recipe-video-content-url']) && !empty($value['recipe-video-content-url'])) {
                            $schema['video'][$key]['contentUrl'] = esc_url($value['recipe-video-content-url']);
                        }
                        if (isset($value['recipe-video-embed-url']) && !empty($value['recipe-video-embed-url'])) {
                            $schema['video'][$key]['embedUrl'] = esc_url($value['recipe-video-embed-url']);
                        }
                        if (isset($value['recipe-video-duration']) && !empty($value['recipe-video-duration'])) {
                            $schema['video'][$key]['duration'] = wp_strip_all_tags($value['recipe-video-duration']);
                        }
                        if (isset($value['recipe-video-upload-date']) && !empty($value['recipe-video-upload-date'])) {
                            $schema['video'][$key]['uploadDate'] = wp_strip_all_tags($value['recipe-video-upload-date']);
                        }
                        if (isset($value['recipe-video-interaction-count']) && !empty($value['recipe-video-interaction-count'])) {
                            $schema['video'][$key]['interactionCount'] = wp_strip_all_tags($value['recipe-video-interaction-count']);
                        }
                        if (isset($value['recipe-video-expires-date']) && !empty($value['recipe-video-expires-date'])) {
                            $schema['video'][$key]['expires'] = wp_strip_all_tags($value['recipe-video-expires-date']);
                        }
                    }
                }
            }

            return apply_filters('outpaceseo_schema_recipe', $schema, $data, $post);
        }
    }
}
