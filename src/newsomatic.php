<?php
namespace Newsomatic;
require_once 'wordpress_function.php';
require __DIR__ . '/plugin.php';
require_once 'newsomatic-news-post-generator/newsomatic-news-post-generator.php';
class NewsomaticPosts
{
    public function get_new_post(){
        write_post();
        return "Done!";
    }

    public function display_news(){
        $cron = write_post();
        $res = get_news_global_variable();
        return json_encode($res);
    }
}
