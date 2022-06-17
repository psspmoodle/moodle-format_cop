<?php

require_once('../../../config.php');
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->dirroot.'/mod/forum/lib.php');

use format_cop\output\table\posts_factory;

$courseid = optional_param('id', 0, PARAM_INT);
$filter = optional_param('filter', 'recent', PARAM_TEXT);
$userid = optional_param('userid', 0, PARAM_INT);

$course = get_course($courseid);
$context = context_course::instance($course->id);
$PAGE->set_context($context);
$PAGE->set_url('/course/format/cop/posts.php');

if ($forums = forum_get_readable_forums($USER->id, $courseid)) {
    $table = posts_factory::create($filter, $forums);
}

$PAGE->set_title($table->get_title());
$PAGE->set_heading($table->get_title());
// @TODO fix breadcrumbs to include other CoP pages

$PAGE->navbar->add($course->fullname, new moodle_url('/course/view.php'), ['id' => $COURSE->id]);
$PAGE->navbar->add($table->get_title(), new moodle_url('/course/format/cop/posts.php'));
echo $OUTPUT->header();
// Get the forums in this course accessible to the user


if ($forums) {
    echo forum_search_form($course);
    $table->out(40, true);
}
echo $OUTPUT->footer();
