<?php

/**
 * Schemas Template.
 */

if (!class_exists('Outpaceseo_Schema_FAQ')) {

    /**
     * Outpaceseo Schemas Initialization
     */
    class Outpaceseo_Schema_FAQ
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
            global $post;
            $schema = array();
            if (isset($data['question-answer'][0]['question']) && !empty($data['question-answer'][0]['question'])) {

                $schema['@context'] = 'https://schema.org';
                $schema['type']     = 'FAQPage';
                foreach ($data['question-answer'] as $key => $value) {
                    if (isset($value['question']) && !empty($value['question'])) {
                        $schema['mainEntity'][$key]['@type'] = 'Question';
                        $schema['mainEntity'][$key]['name']  = $value['question'];
                    }
                    if (isset($value['answer']) && !empty($value['answer'])) {
                        $schema['mainEntity'][$key]['acceptedAnswer']['@type'] = 'Answer';
                        $schema['mainEntity'][$key]['acceptedAnswer']['text']  = $value['answer'];
                    }
                }
            }
            return apply_filters('outpaceseo_schema_faq', $schema, $data, $post);
        }
    }
}
