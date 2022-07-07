<?php

namespace format_cop\output\table;

use DateTime;
use Exception;
use format_cop\output\table\posts_summary_view\discussed;
use format_cop\output\table\posts_summary_view\liked;
use format_cop\output\table\posts_summary_view\posts_summary_view;
use format_cop\output\table\posts_summary_view\recent;
use format_cop\output\table\posts_summary_view\resource;
use format_cop\output\table\posts_summary_view\starred;
use format_cop\output\table\posts_summary_view\yourposts;
use html_writer;
use moodle_exception;
use moodle_url;
use table_sql;

class posts_summary_table extends table_sql
{

    /**
     * @var posts_summary_view A summary view to display in the table
     */
    public posts_summary_view $view;

    /**
     * Factory method.
     *
     * @param int $courseid
     * @param array|string $viewtype
     * @return array|posts_summary_table
     */
    public static function create(int $courseid, $viewtype)
    {
        $forumcmids = self::get_course_forum_cmids($courseid);
        if (is_array($viewtype)) {
            $types = [];
            foreach ($viewtype as $view) {
                $types[] = self::create_single($courseid, $view, $forumcmids);
            }
            return $types;
        }
        return self::create_single($courseid, $viewtype, $forumcmids);
    }

    /**
     * @param int $courseid
     * @param string $viewtype
     * @param $forumcmids
     * @return posts_summary_table
     */
    private static function create_single(int $courseid, string $viewtype, $forumcmids): posts_summary_table
    {
        switch ($viewtype) {
            case 'liked':
                return new self($courseid, new liked($forumcmids));
            case 'discussed':
                return new self($courseid, new discussed($forumcmids));
            case 'starred':
                return new self($courseid, new starred($forumcmids));
            case 'resource':
                return new self($courseid, new resource($forumcmids));
            case 'yourposts':
                return new self($courseid, new yourposts($forumcmids));
            case 'recent':
            default:
                return new self($courseid, new recent($forumcmids));
        }
    }


    /**
     * Public static because it's also called from
     *
     * @return false|mixed
     */
    public function get_onerow()
    {
        global $DB;
        return $DB->get_record_sql(
        /** @lang sql */
            "SELECT {$this->view->get_sql()['select']} 
            FROM {$this->view->get_sql()['from']} 
            WHERE {$this->view->get_sql()['where']}",
            $this->view->get_sql()['params'],
            IGNORE_MULTIPLE
        );
    }

    /**
     * The constructor is only ever called from within the factory methods above.
     *
     * @param int $courseid
     * @param posts_summary_view $view
     * @throws moodle_exception
     */
    private function __construct(int $courseid, posts_summary_view $view)
    {
        $this->view = $view;
        // Class name = view type name
        $class = substr(get_class($view), strrpos(get_class($view), "\\") + 1);
        // Need one of these for each view, otherwise the sort order won't work via the $SESSION setting
        $uniqueid = 'format-cop-' . $class;
        // Call parent
        parent::__construct($uniqueid);
        // Turn off collapsing
        $this->collapsible(false);
        $this->define_baseurl(new moodle_url('/course/format/cop/posts.php', ['id' => $courseid, 'view' => $class]));
        // Set table properties from view
        $this->sortable(true, $this->view->get_default_column(), $this->view->get_default_order());
        $this->set_sql(...array_values($view->get_sql()));
        if ($countsql = $view->get_count_sql()) {
            $this->set_count_sql(...$countsql);
        }
    }


    /**
     * Column is common to all views. 'col_' methods are called from parent.
     *
     * @param $record
     * @return string
     * @throws Exception
     */
    public function col_modified($record)
    {
        return $this->get_post_formatted_datetime($record->modified);
    }

    /**
     * Column is common to all views. 'col_' methods are called from parent.
     *
     * @param $record
     * @return string
     * @throws moodle_exception
     */
    public function col_post($record)
    {
        $url = $this->make_posturl($record->discussionid, $record->postid);
        return html_writer::tag('a', $record->postname, ['href'=>$url]);
    }

    /**
     * Column is common to all views. 'col_' methods are called from parent.
     *
     * @param $record
     * @return string
     * @throws moodle_exception
     */
    public function col_discussionname($record)
    {
        $url = $this->make_posturl($record->discussionid);
        return html_writer::tag('a', $record->discussionname, ['href'=>$url]);
    }

    /**
     * Column is common to all views. 'col_' methods are called from parent.
     *
     * @param $record
     * @return string
     * @throws moodle_exception
     */
    public function col_forumname($record)
    {
        $url = $this->make_forumurl($record->cmid);
        return html_writer::tag('a', $record->forumname, ['href'=>$url]);
    }

    /**
     * @param int $courseid
     * @return array
     */
    protected static function get_course_forum_cmids(int $courseid): array {
        global $USER;
        $forumcmids = [];
        foreach (forum_get_readable_forums($USER->id, $courseid) as $forum) {
            $forumcmids[] = $forum->cm->id;
        }
        return $forumcmids;
    }

    /**
     * @param $timestamp
     * @return string
     */
    public function get_post_formatted_datetime($timestamp): string {
        $usertime = usertime($timestamp);
        $datetime = new DateTime(userdate($usertime));
        return $datetime->format('j M Y');
    }

    /**
     * @param $cmid
     * @return moodle_url
     */
    public function make_forumurl($cmid): moodle_url {
        return new moodle_url('/mod/forum/view.php', ["id" => $cmid]);
    }

    /**
     * @param $discussionid
     * @param $postid
     * @return moodle_url
     */
    public function make_posturl($discussionid, $postid = null): moodle_url {
        $anchor = $postid > 0 ? 'p' . $postid : null;
        return new moodle_url('/mod/forum/discuss.php', ["d" => $discussionid], $anchor);
    }

    /**
     * @param $pagesize
     * @param $useinitialsbar
     * @param $downloadhelpbutton
     * @return void
     */
    public function out($pagesize, $useinitialsbar, $downloadhelpbutton = '')
    {
        $this->define_columns($this->view->get_columns());
        $this->define_headers($this->view->get_headers());
        $this->pagesize = $pagesize;
        $this->setup();
        if ($this->get_onerow()) {
            $this->query_db($pagesize, $useinitialsbar);
            $this->build_table();
            $this->close_recordset();
        }
        $this->finish_output();
    }
}