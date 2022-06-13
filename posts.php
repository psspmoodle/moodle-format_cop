<?php

require_once('../config.php');
require_once($CFG->libdir.'/tablelib.php');

use core_table\local\filter\filter;
use core_table\local\filter\integer_filter;

const DEFAULT_PAGE_SIZE = 20;

$page         = optional_param('page', 0, PARAM_INT); // Which page to show.
$perpage      = optional_param('perpage', DEFAULT_PAGE_SIZE, PARAM_INT); // How many per page.
$contextid    = optional_param('contextid', 0, PARAM_INT); // One of this or.
$courseid     = optional_param('id', 0, PARAM_INT); // This are required.

$filterset = new \core_user\table\posts_filterset();
$filterset->add_filter(new integer_filter('courseid', filter::JOINTYPE_DEFAULT, [(int)$course->id]));

$participanttable = new \core_user\table\participants("user-index-participants-{$course->id}");