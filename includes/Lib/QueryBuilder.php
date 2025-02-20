<?php

namespace Outpace\Lib;

class QueryBuilder
{

    /**
     * Run SQL Query
     *
     * @param $sql
     * @return array|null|object
     */
    public static function run_query($sql)
    {
        global $wpdb;

        return $wpdb->get_results($sql);
    }
}
