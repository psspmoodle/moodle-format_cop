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
        global $USER;
//        parent::extend_course_navigation($navigation, $node);
        $coursenode = $navigation->find($this->courseid, navigation_node::TYPE_COURSE);
        $coursenode->add(
            'Forums summary',
            new moodle_url('/course/format/cop/posts.php', ['id' => $this->courseid]),
            navigation_node::TYPE_CUSTOM,
            'Forums summary',
            99,
            new pix_icon('t/viewdetails', 'summary')
        );
        $forums = forum_get_readable_forums($USER->id, $this->courseid);
        foreach ($forums as $forum) {
            $coursenode->add(
                $forum->name,
                new moodle_url('/mod/forum/view.php', ['id' => $forum->cm->id]),
                navigation_node::TYPE_ACTIVITY,
                $forum->name,
                $forum->cm->id,
                new pix_icon('t/unblock', 'forum')
            );
        }
        return [];
    }
}