<?php

namespace format_cop\output;

use cm_info;
use core_tag_collection;
use core_tag_tag;
use dml_exception;
use dml_missing_record_exception;
use moodle_exception;
use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use templatable;
use theme_boostchild\output\core\course_renderer;

class course_module implements templatable, renderable
{

    private cm_info $cm;
    private course_renderer $courserenderer;
    private $coursemodedit;

    public function __construct($cm, $courserenderer)
    {
        global $PAGE;
        $this->cm = $cm;
        $this->courserenderer = $courserenderer;
        if ($PAGE->user_is_editing()) {
            $editactions = course_get_cm_edit_actions($cm, $cm->indent, 0);
            $this->coursemodedit = $this->courserenderer->course_section_cm_edit_actions($editactions, $cm);
        }
    }

    /**
     * @return stdClass|bool;
     * @throws dml_missing_record_exception
     */
    private function get_most_recent_featured_post()
    {
        if ($tagid = core_tag_tag::get_by_name(core_tag_collection::get_default(), 'featured', 'id')) {
            $featuredtag = core_tag_tag::get($tagid->id);
            $contextid = $this->cm->context->id;
            if ($featuredposts = $featuredtag->get_tagged_items(
                'mod_forum',
                'forum_posts',
                null, null,
                "tt.contextid = :contextid",
                ['contextid' => $contextid]
            )) {
                usort($featuredposts, function($x, $y) {
                    return $y->modified <=> $x->modified;
                });
                return $featuredposts[0];
            }
        }
        return false;
    }

    /**
     * @inheritDoc
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function export_for_template(renderer_base $output)
    {
        global $DB;
        $data = new stdClass();
        $data->name = $this->cm->get_name();
        $data->url = $this->cm->url;
        if ($featured = $this->get_most_recent_featured_post()) {
            $data->featuredtitle = $featured->subject;
            $data->featuredurl = new moodle_url('/mod/forum/discuss.php', ['d' => $featured->discussion], 'p' . $featured->id);
        }
        $record = $DB->get_record($this->cm->modname, ['id' => $this->cm->instance]);
        $text = file_rewrite_pluginfile_urls($record->intro, 'pluginfile.php',
            $this->cm->context->id, 'mod_' . $this->cm->modname, 'intro', null);
        $data->content = $text;
        $imgs = (util::open_domdocument($text))->getElementsByTagName('img');
        if ($imgs->length > 0) {
            $data->imgsrc = $imgs->item(0)->attributes->getNamedItem('src')->value;
        }
        $data->coursemodedit = $this->coursemodedit;
        $data->afterlink = $this->cm->afterlink;
        $data->visible = $this->cm->uservisible;
        $data->restrictions = $this->courserenderer->course_section_cm_availability($this->cm);
        return $data;
    }
}