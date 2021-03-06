<?php

namespace format_cop\output\table\posts_summary_view;

class recent extends posts_summary_view
{
    /**
     * @param $cmids
     */
    public function __construct($cmids)
    {
        parent::__construct();
        $this->columns = ['forumname', 'post', 'userfullname', 'modified'];
        $this->default_column = 'modified';
        $this->default_order = 'SORT_DESC';
        $this->headers = ['Forum', 'Post', 'Author', 'Date'];
        $this->sql = $this->set_sql($cmids);
        $this->title = 'Most recent';
    }

    /**
     * @param $cmids
     * @return array
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
                ,sub.cmid
                ,f.name forumname
                ,p.subject postname
                ,p.modified
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
                END,
            'where' => '1=1',
            'params' => $params
        ];
    }
}