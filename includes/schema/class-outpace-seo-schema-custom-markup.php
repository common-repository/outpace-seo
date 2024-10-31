<?php

/**
 * Schemas Template.
 */

if (!class_exists('Outpaceseo_Schema_Custom_Markup')) {

    /**
     * Outpaceseo Schemas Initialization
     */
    class Outpaceseo_Schema_Custom_Markup
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
            if (isset($data['custom-markup']) && !empty($data['custom-markup'])) {
                $schema['custom-markup'] = $data['custom-markup'];
            }

            return apply_filters('outpaceseo_schema_article', $schema, $data, $post);
        }
    }
}
