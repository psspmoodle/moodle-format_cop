<?php

namespace format_cop\output\table;

use DateTime;
use dml_exception;
use Exception;
use html_writer;
use moodle_exception;
use moodle_url;
use table_sql;

abstract class posts_base extends table_sql
{

    const UNIQUEID = 'format-cop'; // Common unique table ID for all children

    /**
     * @var array Array of forum DB records, only those accessible to the user
     */
    protected array $forums;

    /**
     * @var int
     */
    protected int $posts_summary_box_length = 5;

    /**
     * Called from child classes only.
     *
     * @param array $forums
     * @throws moodle_exception
     */
    protected function __construct(array $forums)
    {
        $this->forums = $forums;
        // Call parent
        parent::__construct(self::UNIQUEID);
        // Turn off collapsing for all children
        $this->collapsible(false);
        $this->define_baseurl(new moodle_url('/course/format/cop/posts.php', [
            'filter' => $this->get_posts_class_type(),
            'id' => current($forums)->course
        ]));
    }

    /**
     * Children must provide their own name.
     *
     * @return string
     */
    abstract public function get_title(): string;

    /**
     * Returns part of class name following underscore. Used for URL construction.
     *
     * @return false|string
     */
    public function get_posts_class_type()
    {
        return substr(static::class, strrpos(static::class, '_') + 1);
    }

    /**
     * Column common for every child class.
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
     * Column common for every child class.
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
     * Column common for every child class.
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
     * Concatenates the child class sql for regular (not table_sql-based) DB call.
     *
     * @return array
     */
    public function get_posts_summary_box_sql(): array
    {
        $sql = $this->sql();
        $string = '';
        foreach ($sql as $keyword => $statement) {
            if (!is_array($statement)) {
                $string .=  ' ' . $keyword . ' ' . $statement;
            }
        }
        return [$string, $sql['params']];
    }

    /**
     * This exists to circumvent the whole table_sql apparatus. We are using the same sql as the table,
     * but it's being called by a renderer for output to Mustache template. So same data, different structure.
     *
     * @return array
     * @throws dml_exception
     * @throws Exception
     */
    public function get_posts_summary_box(): array
    {
        global $DB;
        $records = $DB->get_records_sql(...$this->get_posts_summary_box_sql());
        foreach ($records as $record) {
            $record->modified = $this->get_post_formatted_datetime($record->modified);
            $record->forumurl = $this->make_forumurl($record->cmid);
            $record->posturl = $this->make_posturl($record->discussionid, $record->postid);
        }
        $records = array_values($records);
        return array_slice($records, 0, $this->posts_summary_box_length);
    }

    /**
     * Get the cmids of the forums we were given.
     *
     * @return array
     */
    protected function get_forum_cmids(): array {
        $forumcmids = [];
        foreach ($this->forums as $forum) {
            $forumcmids[] = $forum->cm->id;
        }
        return $forumcmids;
    }

    /**
     * @param $timestamp
     * @return string
     * @throws Exception
     */
    protected function get_post_formatted_datetime($timestamp): string {
        $usertime = usertime($timestamp);
        $datetime = new DateTime(userdate($usertime));
        return $datetime->format('j M Y');
    }

    /**
     * @param $cmid
     * @return moodle_url
     * @throws moodle_exception
     */
    protected function make_forumurl($cmid): moodle_url {
        return new moodle_url('/mod/forum/view.php', ["id" => $cmid]);
    }

    /**
     * @param $discussionid
     * @param $postid
     * @return moodle_url
     * @throws moodle_exception
     */
    protected function make_posturl($discussionid, $postid): moodle_url {
        return new moodle_url('/mod/forum/discuss.php', ["d" => $discussionid], 'p' . $postid);
    }

    public function out($pagesize, $useinitialsbar, $downloadhelpbutton = '')
    {
        parent::out($pagesize, $useinitialsbar, $downloadhelpbutton); // TODO: Change the autogenerated stub
    }

}