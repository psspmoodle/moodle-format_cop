<?php

namespace format_cop\output\table;

use coding_exception;
use dml_exception;
use moodle_exception;

/**
 *
 */
class posts_liked extends posts_base
{
    /**
     * @param $forums
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function __construct($forums)
    {
        $this->sortable(true, 'likes', 'DESC');
        parent::__construct($forums);
        parent::set_sql(...array_values($this->sql()));
    }

    /**
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     */
    public function sql(): array
    {
        global $DB;
        $cmids = $this->get_forum_cmids();
        [$insql, $params] = $DB->get_in_or_equal($cmids, SQL_PARAMS_NAMED, 'cmids');

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
                ,COUNT(r.itemid) likes
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
                JOIN {rating} r ON r.contextid = cxt.id AND r.itemid = p.id
                END,
            'where' => '1=1 GROUP BY r.itemid',  // 1=1 required or query execution will explode
            'params' => $params
        ];
    }

    /**
     * @return string
     */
    public function get_title(): string
    {
        return 'Most liked';
    }

    /**
     * @param $pagesize
     * @param $useinitialsbar
     * @param $downloadhelpbutton
     * @return void
     */
    public function out($pagesize, $useinitialsbar, $downloadhelpbutton = '')
    {
        $this->define_headers(['Likes', 'Post', 'Forum', 'User', 'Date' ]);
        $this->define_columns(['likes', 'post', 'forumname', 'userfullname', 'modified']);
        parent::out($pagesize, $useinitialsbar, $downloadhelpbutton);
    }

}