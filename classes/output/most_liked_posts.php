<?php

namespace format_cop\output;

use moodle_exception;
use moodle_url;
use renderer_base;
use renderable;
use stdClass;
use templatable;

class most_liked_posts implements renderable, templatable
{
    private $posts;

    /**
     * @throws moodle_exception
     */
    public function __construct($mostliked)
    {
        $posts = [];
        foreach ($recentactivity as $item) {
            $post = new stdClass();
            $post->itemid = $item->content->id;
            $post->discussionid = $item->content->discussion;
            $post->forumname = $item->name;
            $post->forumurl = new moodle_url('/mod/forum/view.php', ["id" => $item->cmid]);
            $post->postname = $item->content->subject;
            $post->posturl = new moodle_url('/mod/forum/discuss.php', ["d" => $item->content->discussion], 'p' . $item->content->id);
            $post->userfullname = $item->user->firstname . ' ' . $item->user->lastname;
            $posts[] = $post;
        }
        $this->posts = $posts;
    }

    public function export_for_template(renderer_base $output): stdClass
    {
        $data = new stdClass();
        $data->posts = $this->posts;
        return $data;
    }
}