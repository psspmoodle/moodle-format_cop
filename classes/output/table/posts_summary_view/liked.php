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
}