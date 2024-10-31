<?php

namespace Outpace\Lib;

class Controller
{

    protected static $slug = 'op_xml';

    /**
     * Get Custom Post Types
     * @return string[]|\WP_Post_Type[]
     */
    public function get_cpt()
    {
        $args = [
            'public'    => true,
            '_builtin'  => false
        ];

        return get_post_types($args, 'names', 'and');
    }

    /**
     * Get Taxonomy Types
     * @return string[]|\WP_Post_Type[]
     */
    public function get_taxonomy_types()
    {
        $args = [
            'public'    => true,
            'show_ui'   => true
        ];

        return get_taxonomies($args, 'names', 'and');
    }
}
