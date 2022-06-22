<?php

namespace format_cop\output;

use cm_info;
use renderable;
use renderer_base;
use stdClass;
use templatable;

class course_module implements templatable, renderable
{

    private cm_info $cm;

    public function __construct($cm)
    {
        $this->cm = $cm;
    }

    /**
     * @inheritDoc
     */
    public function export_for_template(renderer_base $output)
    {
        $data = new stdClass();
        $data->name = $this->cm->get_name();
        $data->content = $this->cm->content;
    }
}