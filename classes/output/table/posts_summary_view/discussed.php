<?php

namespace format_cop\output\table\posts_summary_view;

use coding_exception;
use dml_exception;

class discussed extends posts_summary_view
{
    /**
     * @param $cmids
     * @throws coding_exception
     * @throws dml_exception
     */
    public function __construct($cmids)
    {
        parent::__construct();
        $this->columns = ['discussed', 'discussionname', 'forumname', 'userfullname'];
        $this->default_column = 'discussed';
        $this->default_order = 'SORT_DESC';
        $this->headers = ['Post count', 'Discussion', 'Forum', 'Author'];
        $this->sql = $this->set_sql($cmids);
        $this->title = 'Most discussed';
        $this->countsql = $this->set_count_sql($cmids);
    }

    /**
     * @param $cmids
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     */
    protected function set_sql($cmids): array
    {
        global $DB;
        [$insql, $params] = $DB->get_in_or_equal($cmids, SQL_PARAMS_NAMED, 'cmids');
        return [
            'select' =>
                <<<END
                d.id discussionid
                ,sub.cmid
                ,f.name forumname
                ,d.name discussionname
                ,CONCAT(u.firstname, ' ', u.lastname) userfullname
                ,COUNT(p.id) discussed
                END,
            'from' =>
                <<<END
                {forum_posts} p
                JOIN {forum_discussions} d ON p.discussion = d.id
                JOIN {forum} f ON d.forum = f.id
                JOIN {user} u ON d.userid = u.id
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
            'where' => '1=1 GROUP BY d.id, sub.cmid, f.name, d.name, CONCAT(u.firstname, \' \', u.lastname)',
            'params' => $params
        ];
    }

    /**
     * @param $cmids
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     */
    protected function set_count_sql($cmids = []): array
    {
        global $DB;
        [$insql, $params] = $DB->get_in_or_equal($cmids, SQL_PARAMS_NAMED, 'cmids');
        return [ <<<END
                SELECT COUNT(1) 
                FROM {forum_posts} p
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
                WHERE 1=1
                END,
            $params
        ];

    }
}