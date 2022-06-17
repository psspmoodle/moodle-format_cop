<?php

namespace format_cop\output\table;

use coding_exception;
use dml_exception;

class posts_factory
{
    /**
     * @param $filter
     * @param $forums
     * @return array|posts_discussed|posts_liked|posts_recent
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function create($filter, $forums)
    {
        if (is_array($filter)) {
            $types = [];
            foreach ($filter as $f) {
                $types[] = self::create_single($f, $forums);
            }
            return $types;
        }
        return self::create_single($filter, $forums);
    }

    /**
     * @param $filter
     * @param $forums
     * @return posts_discussed|posts_liked|posts_recent
     * @throws coding_exception
     * @throws dml_exception
     */
    private static function create_single($filter, $forums) {
        switch ($filter) {
            case 'liked':
                return new posts_liked($forums);
            case 'discussed':
                return new posts_discussed($forums);
            case 'recent':
            default:
                return new posts_recent($forums);
        }
    }
}