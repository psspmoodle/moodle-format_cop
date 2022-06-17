<?php

namespace format_cop\output;

use format_cop\output\table\posts_base;
use moodle_exception;
use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use templatable;

class post_box_container implements renderable, templatable {

    /**
     * @var array Array of posts_base types
     */
    protected array $boxes;

    /**
     * @param posts_base[] $boxes
     */
    public function __construct(array $boxes) {
        $this->boxes = $boxes;
    }

    /**
     * @param $box
     * @return string
     * @throws moodle_exception
     */
    private function make_more_link($box): string {
        global $COURSE;
        return (new moodle_url('/course/format/cop/posts.php', ['id' => $COURSE->id, 'filter' => $box->get_posts_class_type()]))->out(false);
    }

    /**
     * @param $box
     * @return string
     * @throws moodle_exception
     */
    private function make_recent_posts_link(): string {
        global $COURSE;
        return (new moodle_url('/course/format/cop/posts.php', ['id' => $COURSE->id, 'filter' => 'recent']))->out(false);
    }

    /**
     * @param renderer_base $output
     * @return stdClass
     * @throws moodle_exception
     */
    public function export_for_template(renderer_base $output): stdClass {
        $boxdata = new stdClass();
        $boxes = [];
        foreach ($this->boxes as $box) {
            $data = new stdClass();
            $data->boxtitle = $box->get_title();
            $posts = $box->get_posts_summary_box();
            $data->morelink = count($posts) > 5 ? $this->make_more_link($box) : '';
            $data->posts = array_slice($posts, 0, 5);
            $boxes[] = $data;
        }
        $boxdata->boxdata = $boxes;
        $boxdata->recenturl = $this->make_recent_posts_link();
        return $boxdata;
    }
}