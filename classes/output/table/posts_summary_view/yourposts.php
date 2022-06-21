<?php

namespace format_cop\output\table\posts_summary_view;

use coding_exception;
use dml_exception;

class yourposts implements posts_summary_view
{
    private array $columns;

    private string $default_column;

    private string $default_order;

    private array $headers;

    private array $sql;

    private string $title;

    /**
     * @param $cmids
     * @throws coding_exception
     * @throws dml_exception
     */
    public function __construct($cmids)
    {
        $this->columns = ['post', 'forumname', 'modified'];
        $this->default_column = 'modified';
        $this->default_order = 'SORT_DESC';
        $this->headers = ['Post', 'Forum', 'Date'];
        $this->sql = $this->set_sql($cmids);
        $this->title = 'Your posts';
    }

    /**
     * @param $cmids
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     */
    private function set_sql($cmids): array
    {
        global $DB, $USER;
        [$insql, $params] = $DB->get_in_or_equal($cmids, SQL_PARAMS_NAMED, 'cmids');
        $params['userid'] = $USER->id;
        return [
            'select' =>
                <<<END
                p.id postid
                ,d.id discussionid
                ,sub.cmid cmid
                ,f.name forumname
                ,d.name discussionname
                ,p.subject postname 
                ,p.modified modified
                ,CONCAT(u.firstname, ' ', u.lastname) userfullname
                END,
            'from' =>
                <<<END
                {forum_posts} p
                JOIN {forum_discussions} d ON p.discussion = d.id
                JOIN {forum} f ON d.forum = f.id
                JOIN {user} u ON p.userid = u.id
                JOIN (
                    SELECT cm.id cmid
                    ,cm.instance instance
                    FROM {course_modules} cm
                    JOIN {modules} m ON m.id = cm.module
                    WHERE m.name = 'forum'
                    AND cm.id $insql
                ) sub ON f.id = sub.instance
                JOIN {context} cxt ON sub.cmid = cxt.instanceid AND cxt.contextlevel = 70
                END,
            'where' => 'p.userid = :userid',
            'params' => $params
        ];
    }

    public function get_sql(): array
    {
        return $this->sql;
    }

    /**
     * @return string
     */
    public function get_default_column(): string
    {
        return $this->default_column;
    }

    /**
     * @return string
     */
    public function get_default_order(): string
    {
        return $this->default_order;
    }

    /**
     * @return string
     */
    public function get_title(): string
    {
        return $this->title;
    }

    /**
     * @return string[]
     */
    public function get_headers(): array
    {
        return $this->headers;
    }

    /**
     * @return string[]
     */
    public function get_columns(): array
    {
        return $this->columns;
    }
}