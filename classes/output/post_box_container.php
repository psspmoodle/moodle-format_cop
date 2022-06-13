<?php

namespace format_cop\output;

use renderable;
use renderer_base;
use stdClass;
use templatable;

class post_box_container implements renderable, templatable {

    /**
     * @var array
     */
    protected array $boxes;

    /**
     * @param $boxes
     */
    public function __construct($boxes) {
        $this->boxes = $boxes;
    }

    /**
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $boxdata = new stdClass();
        $boxes = [];
        foreach ($this->boxes as $box) {
            $data = new stdClass();
            $data->boxtitle = $box['boxtitle'];
            $data->posts = $box['posts'];
            $boxes[] = $data;
        }
        $boxdata->boxdata = $boxes;
        return $boxdata;
    }
}