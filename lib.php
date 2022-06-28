<?php

/**
 * Format lib
 *
 * @package    format_cop
 * @copyright  Matt Donnelly CAMH, 2022
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot. '/course/format/lib.php');
require_once($CFG->dirroot. '/mod/forum/lib.php');

/**
 * Main class for the Community of Practice course format
 *
 * @package    format_cop
 * @copyright  2022 Matt Donnelly
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_cop extends format_base {

    /**
     * NOTE: not supplying key values (i.e. leaving them null) to $coursenode::add made the course module action menu disappear
     *
     * @param $navigation
     * @param navigation_node $node
     * @return array
     * @throws moodle_exception
     */
    public function extend_course_navigation($navigation, navigation_node $node): array
    {
        global $PAGE, $USER;
//        parent::extend_course_navigation($navigation, $node);
        $node->add(
            'Forums summary',
            new moodle_url('/course/format/cop/posts.php', ['id' => $this->courseid]),
            navigation_node::TYPE_CUSTOM,
            'Forums summary',
            'summary',
            new pix_icon('t/viewdetails', 'summary')
        );
        // Show only forums user can actually access
        foreach (forum_get_readable_forums($USER->id, $this->courseid) as $forum) {
            $node->add(
                $forum->name,
                new moodle_url('/mod/forum/view.php', ['id' => $forum->cm->id]),
                navigation_node::TYPE_ACTIVITY,
                $forum->name,
                $forum->cm->id,
                new pix_icon('t/unblock', 'forum')
            );
        }
        // Link to course calendar
        $eventsurl = new moodle_url('/calendar/view.php', ['course' => $this->courseid]);
        $eventsnode = $node->add(
            'Events',
            $eventsurl,
            navigation_node::TYPE_CUSTOM,
            'Events',
            'events',
            new pix_icon('i/calendar', 'events calendar')
        );
        // I'm guessing this is required because the calendar exists outside the course url 'umbrella'
        if ($eventsurl->compare($PAGE->url, URL_MATCH_BASE)) {
            navigation_node::override_active_url($eventsurl);
            $eventsnode->make_active();
        }
        // If we don't do this, nothing in the sidebar nav would be active when you are in an activity any deeper than the
        // 'root' activity page (e.g. if you are in a forum discussion).
        $forumurl = new moodle_url('/mod/forum/discuss.php');
        if ($forumurl->compare($PAGE->url, URL_MATCH_BASE)) {
            navigation_node::override_active_url($forumurl);
            $forumnode = $node->find($PAGE->cm->id, navigation_node::TYPE_ACTIVITY);
            $forumnode->make_active();
        }
        return [];
    }
}