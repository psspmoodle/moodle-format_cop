<?php

/**
 * Renderer
 *
 * @package    format_cop
 * @copyright  Matt Donnelly CAMH, 2022
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use format_cop\output\course_module;
use format_cop\output\post_box_container;
use format_cop\output\table\posts_summary_table;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/format/renderer.php');
require_once($CFG->dirroot.'/mod/forum/lib.php');
require_once($CFG->libdir.'/tablelib.php');

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
    }

    /**
     * @return void
     */
    protected function start_section_list(): void
    {
    }

    /**
     * @return void
     */
    protected function end_section_list(): void
    {
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
        echo $this->print_post_views();
        echo $this->print_course_modules();
//
//        echo <<<END
//<div class="content mt-3">
//    <div class="section_availability"></div>
//    <div class="summary"></div>
//    <div class="container">
//        <div class="row">
//            <div class="col-12 border p-3 d-flex">
//                <img class="cop-forum img-fluid" src="http://localhost/dev311/moodle/draftfile.php/5/user/draft/109963612/eddie-or-jonathan.jpeg" style="margin: -1rem 1rem -1rem -1rem; max-width: 25%">
//                <div>
//                    <h4><a href="#">OSP Clinicians forum</a></h4>
//                    <p class="mb-0">Lorem ipsum dolor sit amet consectetur adipisicing elit. Iste a corrupti omnis magni, libero temporibus voluptatum, quae incidunt dolores eaque est quasi consectetur voluptates saepe ipsa recusandae? Nihil, veniam assumenda!</p>
//                                    <div>
//                        <span class="d-block ml-3 mt-2"><strong>Featured discussion:</strong> Are you more like Eddie or Jonathan?</span>
//                    </div>
//                </div>
//            </div>
//
//        </div>
//    </div>
//</div>
//<div class="content mt-3">
//    <div class="section_availability"></div>
//    <div class="summary"></div>
//    <div class="container">
//        <div class="row">
//            <div class="col-12 border p-3 d-flex">
//                <img class="cop-forum img-fluid" src="http://localhost/dev311/moodle/draftfile.php/5/user/draft/109963612/eddie-or-jonathan.jpeg" style="margin: -1rem 1rem -1rem -1rem; max-width: 25%">
//                <div>
//                    <h4><a href="#">Faculty forum</a></h4>
//                    <p class="mb-0">Lorem ipsum dolor sit amet consectetur adipisicing elit. Iste a corrupti omnis magni, libero temporibus voluptatum, quae incidunt dolores eaque est quasi consectetur voluptates saepe ipsa recusandae? Nihil, veniam assumenda!</p>
//                                    <div>
//                        <span class="d-block ml-3 mt-2"><strong>Featured discussion:</strong> Are you more like Eddie or Jonathan?</span>
//                    </div>
//                </div>
//            </div>
//
//        </div>
//    </div>
//</div>
//<div class="content mt-3">
//    <div class="section_availability"></div>
//    <div class="summary"></div>
//    <div class="container">
//        <div class="row">
//            <div class="col-12 border p-3 d-flex">
//                <img class="cop-forum img-fluid" src="http://localhost/dev311/moodle/draftfile.php/5/user/draft/109963612/eddie-or-jonathan.jpeg" style="margin: -1rem 1rem -1rem -1rem; max-width: 25%">
//                <div>
//                    <h4><a href="#">Clinical Consultants forum</a></h4>
//                    <p class="mb-0">Lorem ipsum dolor sit amet consectetur adipisicing elit. Iste a corrupti omnis magni, libero temporibus voluptatum, quae incidunt dolores eaque est quasi consectetur voluptates saepe ipsa recusandae? Nihil, veniam assumenda!</p>
//                                    <div>
//                        <span class="d-block ml-3 mt-2"><strong>Featured discussion:</strong> Are you more like Eddie or Jonathan?</span>
//                    </div>
//                </div>
//            </div>
//
//        </div>
//    </div>
//</div>
//END;
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
     * @throws moodle_exception
     */
    private function print_course_modules(): string
    {
        $output = '';
        $modinfo = get_fast_modinfo($this->page->course->id);
        foreach ($modinfo->get_cms() as $cm) {
            $module = new course_module($cm);
            $output .= $this->courserenderer->render($module);
        }
        return $output;
    }

    /**
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    private function print_post_views(): string
    {
        $views = posts_summary_table::create($this->page->course->id, ['recent', 'liked', 'discussed']);
        return $this->courserenderer->render(new post_box_container($views));
    }

}