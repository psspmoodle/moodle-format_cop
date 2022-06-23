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
     * @throws coding_exception
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
        echo $this->output->heading($this->page_title(), 2, 'accesshide');
        $summary = new stdClass();
        $summary->summary = file_rewrite_pluginfile_urls($course->summary, 'pluginfile.php',
            (context_course::instance($course->id))->id, 'course', 'summary', null);
        echo $this->courserenderer->render_from_template('format_cop/course_summary', $summary);
        echo $this->print_post_views();
        echo $this->print_course_modules();
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
            $module = new course_module($cm, $this->courserenderer);
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