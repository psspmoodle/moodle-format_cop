<?php

/**
 * format.php
 *
 * @package    format_cop
 * @copyright  Matt Donnelly CAMH, 2022
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$context = context_course::instance($course->id);
$course = course_get_format($course)->get_course();

$renderer = $PAGE->get_renderer('format_cop');
$renderer->print_multiple_section_page($course, null, null, null, null);