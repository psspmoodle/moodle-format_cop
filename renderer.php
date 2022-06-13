<?php

/**
 * Renderer
 *
 * @package    format_cop
 * @copyright  Matt Donnelly CAMH, 2022
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use format_cop\output\post_box_container;
use format_cop\output\posts_since_last_visit;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/format/renderer.php');
require_once($CFG->dirroot.'/mod/forum/lib.php');

/**
 * Basic renderer for topics_advanced format.
 *
 * @copyright 2012 Dan Poltawski
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_cop_renderer extends format_section_renderer_base
{
    /**
     * Constructor method, calls the parent constructor
     *
     * @param moodle_page $page
     * @param string $target one of rendering target constants
     */
    public function __construct(moodle_page $page, $target)
    {
        parent::__construct($page, $target);

        // Since format_topics_advanced_renderer::section_edit_controls() only displays the 'Set current section' control when editing mode is on
        // we need to be sure that the link 'Turn editing mode on' is available for a user who does not have any other managing capability.
        $page->set_other_editing_capability('moodle/course:setcurrentsection');
    }


    /**
     * Generate the starting container html for a list of sections
     * @return string HTML to output.
     */
    protected function start_section_list()
    {
        return html_writer::start_tag('ul', array('class' => 'topics'));
    }

    /**
     * Generate the closing container html for a list of sections
     * @return string HTML to output.
     */
    protected function end_section_list()
    {
        return html_writer::end_tag('ul');
    }

    /**
     * Generate the title for this section page
     * @return string the page title
     */
    protected function page_title()
    {
        return get_string('coppage', 'format_cop');
    }

    /**
     * Generate the edit control items of a section
     *
     * @param stdClass $course The course entry from DB
     * @param stdClass $section The course_section entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @return array of edit control items
     */
    protected function section_edit_control_items($course, $section, $onsectionpage = false)
    {
        global $PAGE;

        if (!$PAGE->user_is_editing()) {
            return array();
        }

        $coursecontext = context_course::instance($course->id);

        if ($onsectionpage) {
            $url = course_get_url($course, $section->section);
        } else {
            $url = course_get_url($course);
        }
        $url->param('sesskey', sesskey());

        $controls = array();
        if ($section->section && has_capability('moodle/course:setcurrentsection', $coursecontext)) {
            if ($course->marker == $section->section) {  // Show the "light globe" on/off.
                $url->param('marker', 0);
                $highlightoff = get_string('highlightoff');
                $controls['highlight'] = array('url' => $url, "icon" => 'i/marked',
                    'name' => $highlightoff,
                    'pixattr' => array('class' => ''),
                    'attr' => array('class' => 'editing_highlight',
                        'data-action' => 'removemarker'));
            } else {
                $url->param('marker', $section->section);
                $highlight = get_string('highlight');
                $controls['highlight'] = array('url' => $url, "icon" => 'i/marker',
                    'name' => $highlight,
                    'pixattr' => array('class' => ''),
                    'attr' => array('class' => 'editing_highlight',
                        'data-action' => 'setmarker'));
            }
        }

        $parentcontrols = parent::section_edit_control_items($course, $section, $onsectionpage);

        // If the edit key exists, we are going to insert our controls after it.
        if (array_key_exists("edit", $parentcontrols)) {
            $merged = array();
            // We can't use splice because we are using associative arrays.
            // Step through the array and merge the arrays.
            foreach ($parentcontrols as $key => $action) {
                $merged[$key] = $action;
                if ($key == "edit") {
                    // If we have come to the edit key, merge these controls here.
                    $merged = array_merge($merged, $controls);
                }
            }

            return $merged;
        } else {
            return array_merge($controls, $parentcontrols);
        }
    }

    /**
     * Output the html for a multiple section page
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections (argument not used)
     * @param array $mods (argument not used)
     * @param array $modnames (argument not used)
     * @param array $modnamesused (argument not used)
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused) {
        global $PAGE;

        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();
        $context = context_course::instance($course->id);
        echo $this->output->heading($this->page_title(), 2, 'accesshide');

        echo $this->print_post_boxes();

        // Section list
        echo $this->start_section_list();
        $numsections = course_get_format($course)->get_last_section_number();
        foreach ($modinfo->get_section_info_all() as $section => $thissection) {
            if ($section > $numsections) {
                // activities inside this section are 'orphaned', this section will be printed as 'stealth' below
                continue;
            }
            // Show the section if the user is permitted to access it, OR if it's not available
            // but there is some available info text which explains the reason & should display,
            // OR it is hidden but the course has a setting to display hidden sections as unavilable.
            $showsection = $thissection->uservisible ||
                ($thissection->visible && !$thissection->available && !empty($thissection->availableinfo)) ||
                (!$thissection->visible && !$course->hiddensections);
            if (!$showsection) {
                continue;
            }
            echo $this->section_header($thissection, $course, false, 0);
            if ($thissection->uservisible) {
                echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                echo $this->courserenderer->course_section_add_cm_control($course, $section, 0);
            }
            echo $this->section_footer();
        }
        if ($PAGE->user_is_editing() and has_capability('moodle/course:update', $context)) {
            echo $this->change_number_sections($course, 0);
        }
        echo $this->end_section_list();
    }

    /**
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function print_post_boxes(): string {
        global $COURSE, $USER;
        // Get the forums in this course accessible to the user
        if (!$forums = forum_get_readable_forums($USER->id, $COURSE->id)) {
            return '';
        }
        $boxes[] = [
            'boxtitle' => 'Since your last visit',
            'posts' => $this->get_posts_since_last_visit($forums, 5)
        ];
        $boxes[] = [
            'boxtitle' => 'Most liked',
            'posts' => $this->get_posts_most_liked($forums)
        ];
        $boxes[] = [
            'boxtitle' => 'Most discussed',
            'posts' => $this->get_posts_most_discussed($forums, 5)
        ];
        return $this->courserenderer->render(new post_box_container($boxes));
    }

    /**
     * @param $forums
     * @param $length
     * @return array
     * @throws dml_exception
     * @throws moodle_exception
     * @throws Exception
     */
    public function get_posts_since_last_visit($forums, $length = null): array {
        global $COURSE, $USER;
        // Get course module IDs for every forum
        $forumcmids = [];
        foreach ($forums as $forum) {
            $forumcmids[] = $forum->cm->id;
        }
        // forum_get_recent_mod_activity's first and second args are passed by reference
        $recentactivity = [];
        $index = 0;
        // $lastlogout will be current time if user hasn't logged in before
        $lastlogout = $this->get_user_last_logout_timestamp();
        foreach ($forumcmids as $cmid) {
            forum_get_recent_mod_activity($recentactivity, $index, $lastlogout, $COURSE->id, $cmid);
        }
        $postdata = [];
        // forum_get_recent_mod_activity doesn't return proper 'post' objects so we need to normalize structure
        foreach ($recentactivity as $recent) {
            $post = new stdClass();
            $post->id = $recent->content->id;
            $post->modified = $recent->timestamp;
            $post->discussionid = $recent->content->discussion;
            $post->forumname = $recent->name;
            $post-> name = $recent->content->subject;
            $post->userid = $recent->user->id;
            $post->userfullname = $recent->user->firstname . ' ' . $recent->user->lastname;
            $post->forumurl = $this->make_forumurl($recent->cmid);
            $post->posturl = $this->make_posturl($post->discussionid, $post->id);
            $postdata[] = $post;
        }
        // 1. Don't include any of the user's own posts they might make while logged in
        // 2. Don't include posts the user has already read
        $postdata = array_filter($postdata, function($item) use ($USER) {
            return ($item->userid !== $USER->id) AND forum_tp_is_post_read($USER->id, $item) === false;
        });
        // Sort by time, descending
        usort($postdata, function($x, $y) {
            if ($x->modified === $y->modified) {
                return 0;
            }
            return $x->modified < $y->modified ? 1 : -1;
        });
        // This is sloppy to put here, but meh
        foreach ($postdata as $item) {
            $item->modified = $this->get_post_formatted_datetime($item->modified);
        }
        // Cut to length if required
        return $length === null ? $postdata : array_slice($postdata, 0, $length);
    }

    /**
     * @return string
     * @throws dml_exception
     */
    public function get_user_last_logout_timestamp(): string {
        global $DB, $USER;
        $params = ['userid' => $USER->id];
        $sql = /** @lang sql */
            "SELECT userid
                ,timecreated time
            FROM {logstore_standard_log}
            WHERE userid = :userid and eventname LIKE \"%loggedout%\"
            ORDER BY time DESC
            LIMIT 1";
        if ($record = $DB->get_records_sql($sql, $params)) {
            return $record[$USER->id]->time;
        } else {
            return usertime(time());
        }
    }

    /**
     * @param $forums
     * @return array
     * @throws dml_exception
     * @throws moodle_exception
     * @throws Exception
     */
    public function get_posts_most_liked($forums): array {
        global $DB;
        // Get forums actually using the Like scale
        $likescale = grade_scale::fetch(['name' => 'Like']);
        $forums = array_filter($forums, function($forum) use ($likescale) {
            return $forum->scale === '-' . $likescale->id;
        });
        // Get course module IDs for every forum
        $cmids = $this->get_forum_cmids($forums);
        $sql = /** @lang sql */
            "SELECT r.itemid id
                ,cm.id cmid
                ,f.name forumname
                ,p.discussion discussionid
                ,p.subject name
                ,u.id userid
                ,p.modified modified
                ,CONCAT(u.firstname, ' ', u.lastname) userfullname
                ,COUNT(r.itemid) likes
            FROM {rating} r
            JOIN {context} cxt ON r.contextid = cxt.id
            JOIN {course_modules} cm ON cxt.instanceid = cm.id
            JOIN {forum} f ON cm.instance = f.id
            JOIN {forum_posts} p ON r.itemid = p.id
            JOIN {user} u ON p.userid = u.id
            WHERE cm.id IN ({$cmids})
            GROUP BY r.itemid
            ORDER BY likes DESC
            LIMIT 5";
        $records = $DB->get_records_sql($sql);
        foreach ($records as $record) {
            $record->modified = $this->get_post_formatted_datetime($record->modified);
            $record->forumurl = $this->make_forumurl($record->cmid);
            $record->posturl = $this->make_posturl($record->discussionid, $record->id);
        }
        return array_values($records);
    }

    /**
     * @param $forums
     * @param $length
     * @return array
     * @throws dml_exception
     * @throws moodle_exception
     * @throws Exception
     */
    private function get_posts_most_discussed($forums, $length = null): array {
        global $DB;
        $cmids = $this->get_forum_cmids($forums);
        $sql =/** @lang sql */
            "SELECT d.id
                ,sub.cmid cmid
                ,f.name forumname
                ,p.discussion discussionid
                ,d.name name
                ,d.timemodified modified
                ,CONCAT(u.firstname, ' ', u.lastname) userfullname
                ,COUNT(p.discussion) totalposts
            FROM {forum_posts} p
            JOIN {forum_discussions} d on p.discussion = d.id
            JOIN {forum} f ON f.id = d.forum
            JOIN (SELECT cm.id cmid
                    ,instance instance
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module
                  WHERE m.name = 'forum'
                  AND cm.id IN ({$cmids})) sub ON f.id = sub.instance
            JOIN {user} u ON u.id = d.userid
            GROUP BY p.discussion
            ORDER BY totalposts DESC";
        $records = $DB->get_records_sql($sql);
        foreach ($records as $record) {
            $record->modified = $this->get_post_formatted_datetime($record->modified);
            $record->forumurl = $this->make_forumurl($record->cmid);
            $record->posturl = $this->make_posturl($record->discussionid, $record->id);
        }
        $records = array_values($records);
        return $length === null ? $records : array_slice($records, 0, $length);
    }

    /**
     * @param $cmid
     * @return moodle_url
     * @throws moodle_exception
     */
    private function make_forumurl($cmid): moodle_url {
        return new moodle_url('/mod/forum/view.php', ["id" => $cmid]);
    }

    /**
     * @param $discussionid
     * @param $postid
     * @return moodle_url
     * @throws moodle_exception
     */
    private function make_posturl($discussionid, $postid): moodle_url {
        return new moodle_url('/mod/forum/discuss.php', ["d" => $discussionid], 'p' . $postid);
    }

    /**
     * @param $forums
     * @return string
     */
    private function get_forum_cmids($forums): string {
        $forumcmids = [];
        foreach ($forums as $forum) {
            $forumcmids[] = $forum->cm->id;
        }
        return implode(',', $forumcmids);
    }

    /**
     * @param $timestamp
     * @return string
     * @throws Exception
     */
    private function get_post_formatted_datetime($timestamp): string {
        $usertime = usertime($timestamp);
        $datetime = new DateTime(userdate($usertime));
        return $datetime->format('j M Y');
    }

}