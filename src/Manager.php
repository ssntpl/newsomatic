<?php

namespace Ssntpl\Newsomatic;
require_once 'wordpressFunctions.php';
require 'wordpressEnvironment/plugin.php';
require_once 'newsomatic-news-post-generator/newsomatic-news-post-generator.php';

class Manager
{
    static $postData = [];
    public function getPosts(){
        createPost();
        return "Done!";
    }

    public function displayPosts(){
        createPost();
        $response = self::$postData;
        return $response;
    }
}
