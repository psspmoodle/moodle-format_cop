<?php

namespace format_cop\output\table\posts_summary_view;

use coding_exception;
use dml_exception;

class resource extends posts_summary_view
{
    /**
     * @param $cmids
     * @throws coding_exception
     * @throws dml_exception
     */
    public function __construct($cmids)
    {
        parent::__construct();
        $this->columns = ['post', 'forumname', 'userfullname'];
        $this->default_column = 'modified';
        $this->default_order = 'SORT_DESC';
        $this->headers = ['Post', 'Forum', 'Author'];
        $this->sql = $this->set_sql($cmids);
        $this->title = "Posts tagged 'resource'";
        $this->countsql = [];
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
                ,p.subject postname
                ,sub.cmid
                ,f.name forumname
                ,d.name discussionname
                ,CONCAT(u.firstname, ' ', u.lastname) userfullname
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
                JOIN {tag_instance} ti ON ti.itemid = p.id AND ti.component = 'mod_forum' AND ti.itemtype = 'forum_posts'
                JOIN {tag} t ON ti.tagid = t.id
                END,
            'where' => "t.name = 'resource'",
            'params' => $params
        ];
    }

    /**
     * @param $cmids
     * @return array
     */
    protected function set_count_sql($cmids = []): array
    {
    }
}