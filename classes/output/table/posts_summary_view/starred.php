<?php

namespace format_cop\output\table\posts_summary_view;

use coding_exception;
use dml_exception;

class starred extends posts_summary_view
{
    /**
     * @param $cmids
     * @throws coding_exception
     * @throws dml_exception
     */
    public function __construct($cmids)
    {
        parent::__construct();
        $this->columns = ['forumname', 'discussionname', 'userfullname', 'modified'];
        $this->default_column = 'modified';
        $this->default_order = 'SORT_DESC';
        $this->headers = ['Forum', 'Discussion', 'Author', 'Date'];
        $this->sql = $this->set_sql($cmids);
        $this->title = 'Your starred discussions';
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
        global $DB, $USER;
        [$insql, $params] = $DB->get_in_or_equal($cmids, SQL_PARAMS_NAMED, 'cmids');
        $params['userid'] = $USER->id;
        return [
            'select' =>
                <<<END
                d.id discussionid
                ,sub.cmid cmid
                ,f.name forumname
                ,d.name discussionname
                ,d.timemodified modified
                ,CONCAT(u.firstname, ' ', u.lastname) userfullname
                END,
            'from' =>
                <<<END
                {forum_discussions} d
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
                JOIN {favourite} fav ON d.id = fav.itemid AND component = 'mod_forum' AND itemtype = 'discussions'
               END,
            'where' => 'fav.userid = :userid',
            'params' => $params
        ];
    }

    protected function set_count_sql(array $cmids = [])
    {
    }
}