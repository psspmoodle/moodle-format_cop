<?php

namespace format_cop\output\table\posts_summary_view;

use coding_exception;
use dml_exception;

class liked extends posts_summary_view
{
    /**
     * @param $cmids
     * @throws coding_exception
     * @throws dml_exception
     */
    public function __construct($cmids)
    {
        parent::__construct();
        $this->columns = ['likes', 'post', 'forumname', 'userfullname', 'modified'];
        $this->default_column = 'likes';
        $this->default_order = 'SORT_DESC';
        $this->headers = ['Likes', 'Post', 'Forum', 'Author', 'Date'];
        $this->sql = $this->set_sql($cmids);
        $this->title = 'Most liked';
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
                p.id postid
                ,d.id discussionid
                ,sub.cmid cmid
                ,f.name forumname
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
            'where' => '1=1 GROUP BY r.itemid, p.id, d.id, sub.cmid, f.name, p.subject, p.modified, CONCAT(u.firstname, \' \', u.lastname)',  // 1=1 required or query execution will explode
            'params' => $params
        ];
    }

    /**
     * @param array $cmids
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     */
    protected function set_count_sql(array $cmids = []): array
    {
        global $DB;
        [$insql, $params] = $DB->get_in_or_equal($cmids, SQL_PARAMS_NAMED, 'cmids');
        return [ <<<END
                SELECT COUNT(1) FROM {forum_posts} p
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
                WHERE 1=1
                END,
                $params
        ];
    }
}