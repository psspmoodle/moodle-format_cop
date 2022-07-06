<?php

require_once('../../../config.php');
require_once($CFG->libdir.'/tablelib.php');

use format_cop\output\posts_summary_page;
use format_cop\output\table\posts_summary_table;

$courseid = optional_param('id', 0, PARAM_INT);
$view = optional_param('view', 'recent', PARAM_TEXT);
$userid = optional_param('userid', 0, PARAM_INT);

$course = get_course($courseid);
$context = context_course::instance($course->id);
require_course_login($course);

$PAGE->set_context($context);
$PAGE->set_course($course);
$url = new moodle_url('/course/format/cop/posts.php', ['id' => $courseid]);
$PAGE->set_url($url);

$table = posts_summary_table::create($courseid, $view);

$PAGE->set_title($table->view->get_title());
$PAGE->set_heading('Forums summary: ' . $table->view->get_title());

$summarypage = new posts_summary_page($course, $view, $url);

echo $OUTPUT->header();
echo $OUTPUT->render($summarypage);
$table->out(20, true);
echo $OUTPUT->footer();
