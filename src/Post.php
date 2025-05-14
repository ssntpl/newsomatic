<?php

namespace Ssntpl\Newsomatic;
use Ssntpl\Newsomatic\Manager;
class Post
{

    public function checkPost( $post_id = null) {
		$post_data = Manager::$postData;
		if(isset($post_data)){
			foreach($post_data as $data){
				if($post_id == $data['newsomatic_post_id']){
					return [1];
				}
			}
		}
		return [];
	}

    public function insertPost($post_data = []){
	}
}