<?php

namespace format_cop\output;

use format_cop\output\table\posts_summary_table;
use format_cop\output\table\posts_summary_view\posts_summary_view;
use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use templatable;
use Traversable;

class post_box_container implements renderable, templatable {

    /**
     * @var array Array of posts_summary_view types
     */
    protected array $tables;

    /**
     * @param posts_summary_view[] views
     */
    public function __construct(array $tables)
    {
        $this->tables = $tables;
    }

    /**
     * @return string
     */
    private function make_recent_posts_link(): string
    {
        global $COURSE;
        $params = ['id' => $COURSE->id, 'view' => 'recent'];
        return (new moodle_url('/course/format/cop/posts.php', $params))->out(false);
    }

    /**
     * @param posts_summary_table $posts
     * @param int $length
     * @return array|Traversable|null
     */
    private function prepare_postdata(posts_summary_table $posts, int $length = 5)
    {
        foreach ($posts->rawdata as $post) {
            if (property_exists($post, 'modified')) {
                $post->modified = $posts->get_post_formatted_datetime($post->modified);
            }
            $post->forumurl = $posts->make_forumurl($post->cmid);
            $postid = null;
            if (property_exists($post, 'postid')) {
                $postid = $post->postid;
            }
            $post->posturl = $posts->make_posturl($post->discussionid, $postid);
        }
        return array_slice($posts->rawdata, 0, $length);
    }

    /**
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass
    {
        $boxdata = new stdClass();
        $boxes = [];
        foreach ($this->tables as $table) {
            if ($table->sql) {
                $data = new stdClass();
                $data->boxtitle = $table->view->get_title();
                if ($onerow = $table->get_onerow()) {
                    $table->define_columns(array_keys((array) $onerow));
                    $table->define_headers(array_keys((array) $onerow));
                    $table->setup();
                    $table->query_db(6, false);
                    $data->posts = array_values($this->prepare_postdata($table));
                    $data->morelink = count($table->rawdata) > 5 ? ($table->baseurl)->out(false) : '';
                }
                $boxes[] = $data;
            }
        }
        $boxdata->boxdata = $boxes;
        $boxdata->recenturl = $this->make_recent_posts_link();
        return $boxdata;
    }
}