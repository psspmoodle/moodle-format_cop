<?php

namespace format_cop\output;

use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use templatable;
use url_select;

class posts_summary_page implements templatable, renderable
{

    private stdClass $course;
    private string $view;
    private moodle_url $url;

    public function __construct($course, $view, $url)
    {
        $this->course = $course;
        $this->view = $view;
        $this->url = $url;
    }

    private function build_urlselect(): url_select
    {
        $viewtypes = [
            'Most recent posts' => 'recent',
            'Most liked posts' => 'liked',
            'Most discussed posts' => 'discussed',
            'Your starred discussions' => 'starred',
            'Your posts' => 'yourposts'
        ];
        $count = 0;
        foreach ($viewtypes as $type) {
            $clone = fullclone($this->url);
            $clone->param('view', $type);
            $urls[] = $clone->out(false);
            $count++;
        }
        $urls = array_combine($urls, array_keys($viewtypes));
        return new url_select($urls, array_keys($urls, array_search($this->view, $viewtypes))[0]);
    }

    /**
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass
    {
        $data = new stdclass();
        $data->searchform = forum_search_form($this->course);
        $data->urlselect = ($this->build_urlselect())->export_for_template($output);
        return $data;
    }
}