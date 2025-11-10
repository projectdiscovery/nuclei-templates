<?php

// Main Class
require_once 'core.php';

Class WpAutomaticVimeo extends wp_automatic{
	
	
	/**
	 * Vimeo get post function
	 */
	function vimeo_get_post($camp){
	
		//ini keywords
		$camp_opt = unserialize ( $camp->camp_options );
		$keywords = explode ( ',', $camp->camp_keywords );
	
		//looping keywords
	
		foreach ( $keywords as $keyword ) {
				
			$keyword = trim($keyword);
	
			//update last keyword
			update_post_meta($camp->camp_id, 'last_keyword', trim($keyword));
	
			//when valid keyword
			if (trim ( $keyword ) != '') {
	
				//record current used keyword
				$this->used_keyword=$keyword;
	
	
				// getting links from the db for that keyword
				$query = "select * from {$this->wp_prefix}automatic_general where item_type=  'vm_{$camp->camp_id}_$keyword' ";
				$res = $this->db->get_results ( $query );
	
				// when no links lets get new links
				if (count ( $res ) == 0) {
					
					//clean any old cache for this keyword
					$query_delete = "delete from {$this->wp_prefix}automatic_general where item_type='vm_{$camp->camp_id}_$keyword' ";
					$this->db->query ( $query_delete );
						
					//get new links
					$this->vimeo_fetch_items ( $keyword, $camp );
						
					// getting links from the db for that keyword
					$res = $this->db->get_results ( $query );
				}
	
				//check if already duplicated
				//deleting duplicated items
				$res_count = count($res);
				for($i=0;$i< $res_count;$i++){
	
					$t_row = $res[$i];
						
					$t_data =  unserialize (base64_decode( $t_row->item_data) );
	
					$t_link_url=$t_data['vid_url'];
	
					if( $this->is_duplicate($t_link_url) ){
							
						//duplicated item let's delete
						unset($res[$i]);
							
						  echo '<br>Vimeo video ('. $t_data ['vid_title'] .') found cached but duplicated <a href="'.get_permalink($this->duplicate_id).'">#'.$this->duplicate_id.'</a>'  ;
							
						//delete the item
						$query = "delete from {$this->wp_prefix}automatic_general where id={$t_row->id} ";
						$this->db->query ( $query );
							
					}else{
						break;
					}
	
				}
	
				// check again if valid links found for that keyword otherwise skip it
				if (count ( $res ) > 0) {
	
					// lets process that link
					$ret = $res [$i];
					$temp = unserialize ( base64_decode($ret->item_data ));
	
					//report link
					  echo '<br>Found Link:<a href="'.$temp['vid_url'].'">'.$temp ['vid_title'].'</a>';
	
					$temp['source_link'] = $temp['vid_url'];
					
					// update the link status to 1
					$query = "delete from {$this->wp_prefix}automatic_general where id={$ret->id}";
					$this->db->query ( $query );
	
					// if cache not active let's delete the cached videos and reset indexes
					if (! in_array ( 'OPT_VM_CACHE', $camp_opt )) {
						  echo '<br>Cache disabled claring cache ...';
						$query = "delete from {$this->wp_prefix}automatic_general where item_type='vm_{$camp->camp_id}_$keyword' ";
						$this->db->query ( $query );
	
						// reset index
						$query = "update {$this->wp_prefix}automatic_keywords set keyword_start =1 where keyword_camp={$camp->camp_id}";
						$this->db->query ( $query );
					}
	
					//fix vid duration from seconds to readable
					$temp['vid_duration_readable'] = gmdate("H:i:s", $temp['vid_duration']);

						
						
					return $temp;
				} else {
	
					  echo '<br>No links found for this keyword';
				}
			} // if trim
		} // foreach keyword
	
	
	}
		
	/**
	 * vimeo fetch items
	 *
	 */
	
	function vimeo_fetch_items($keyword, $camp){
	
		//report
		  echo "<br>So I should now get some videos from vimeo for keyword :" . $keyword;
	
		//access tocken
		$access_tocken = get_option('wp_automatic_vm_tocken','');
	
		//validate tocken
		if (trim ( $access_tocken ) == '') {
			  echo '<br>Vimeo Access token is required visit settings and add it...';
			exit ();
		}
	
		// ini options
		$camp_opt = unserialize ( $camp->camp_options );
		if( stristr($camp->camp_general, 'a:') ) $camp->camp_general=base64_encode($camp->camp_general);
		$camp_general = unserialize ( base64_decode( $camp->camp_general ) );
		$camp_general=array_map('wp_automatic_stripslashes', $camp_general);
	
		// get start-index for this keyword
		$query = "select keyword_start ,keyword_id from {$this->wp_prefix}automatic_keywords where keyword_name='$keyword' and keyword_camp={$camp->camp_id}";
		$rows = $this->db->get_results ( $query );
		$row = $rows [0];
		$kid = $row->keyword_id;
		$start = $row->keyword_start;
		if ($start == 0)
			$start = 1;
	
		if ($start == - 1) {
			  echo '<- exhausted keyword';
	
			if( ! in_array( 'OPT_VM_CACHE' , $camp_opt )){
				$start =1;
				  echo '<br>Cache disabled resetting index to 1';
			}else{
	
				//check if it is reactivated or still deactivated
				if($this->is_deactivated($camp->camp_id, $keyword)){
					$start =1;
				}else{
					//still deactivated
					return false;
				}
	
			}
				
		}
	
		  echo ' index:' . $start;
	
		// update start index to start+1
		$nextstart = $start + 1;
		$query = "update {$this->wp_prefix}automatic_keywords set keyword_start = $nextstart where keyword_id=$kid ";
		$this->db->query ( $query );
	
		//auth
		curl_setopt($this->ch,CURLOPT_HTTPHEADER,array('Authorization: bearer '.trim($access_tocken)));
	
		//if specific user posting
		if(in_array('OPT_VM_USER', $camp_opt)){
	
			$author = $camp_general['cg_vm_user'];
			 
			$url = 'https://api.vimeo.com/'.$camp_general['cg_vm_user_channel'].'/'.trim($author).'/videos?page='.$start.'&per_page=50';
			//$url = 'https://api.vimeo.com/me/videos?page='.$start.'&per_page=50';
				
			if($keyword !='*'){
				$url.= '&query='.urlencode($keyword);
			}
				
			if($camp_general['cg_vm_order'] != 'relevant'){
				$url.='&sort='.$camp_general['cg_vm_order'].'&direction='.$camp_general['cg_vm_order_dir'];
			}
				
	
		}else{
	
			// get items
			$url='https://api.vimeo.com/videos?query='.urlencode($keyword).'&page='.$start.'&per_page=50&sort='.$camp_general['cg_vm_order'].'&direction='.$camp_general['cg_vm_order_dir'];
				
			//filter?
			if( $camp_general['cg_vm_cc'] != 'none' ){
				$url.= '&filter='.$camp_general['cg_vm_cc'];
			}
				
		}
	 
		//report url
		 echo '<br>Vimeo url:'.$url;
	
		curl_setopt($this->ch, CURLOPT_HTTPGET, 1);
		curl_setopt($this->ch, CURLOPT_URL, trim($url));
		$exec=curl_exec($this->ch);
		$x=curl_error($this->ch);
	
		//  echo $exec.$x;
	
		//validating reply
		if(stristr($exec, 'paging')){
			//valid reply
				
			//handle vids
			$arr = json_decode($exec);
			$vids = $arr->data;
			unset($arr);
	
			//if reversion order
			if(in_array('OPT_VM_REVERSE', $camp_opt)){
				  echo '<br>Reversing order';
				$vids = array_reverse($vids);
			}
				
			  echo '<ol>';
	
			//loop videos
			$i = 0;
			foreach ($vids as $vid){
	
				$itm['vid_url'] =  $vid->link;
	
				echo '<li>'.$itm['vid_url'];
	
	
				$itm['vid_title'] = $vid->name;
				$itm['vid_id'] = str_replace('/videos/', '', $vid->uri) ;
				$itm['vid_description'] = $vid->description;
				$itm['vid_duration'] = $vid->duration;
				$itm['vid_width'] = $vid->width;
				$itm['vid_height'] = $vid->height;
				//$itm['vid_rating'] = $vid->content_rating;
				$itm['vid_img'] = $vid->pictures->sizes[count($vid->pictures->sizes) -1]->link;
	
				//embed code
				$itm['vid_embed'] = $vid->embed->html;
	
				//replace width and height
				$itm['vid_embed'] = str_replace('width="'.$itm['vid_width'].'"', 'width="560"', $itm['vid_embed']);
				$itm['vid_embed'] = str_replace('height="'.$itm['vid_height'].'"', 'height="315"', $itm['vid_embed']);
			 	
				//extract tags
				$tags=$vid->tags;
				$tags_arr = array();
	
				foreach ($tags as $tag){
					$tags_arr[]=$tag->tag;
				}
	
				if(count($tags_arr) > 0 ){
					$itm['vid_tags'] = implode(',', $tags_arr);
				}else{
					$itm['vid_tags'] = '';
				}
	
	
	
				$itm['vid_views'] = $vid->stats->plays;
				$itm['vid_created_time'] = $vid->created_time;
				$itm['vid_modified_time'] = $vid->modified_time;
	
				//fixing dates
				$itm['vid_created_time'] =  str_replace('T', ' ', $itm['vid_created_time']) ;
				$itm['vid_created_time'] =   str_replace('+00:00', '', $itm['vid_created_time']);
	
				$itm['vid_modified_time'] =  str_replace('T', ' ', $itm['vid_modified_time']) ;
				$itm['vid_modified_time'] =   str_replace('+00:00', '', $itm['vid_modified_time']);
	
	
	
	
				$itm['vid_likes'] = $vid->metadata->connections->likes->total;
				$itm['vid_author_name'] = $vid->user->name;
				$itm['vid_author_id'] =  str_replace('/users/', '', $vid->user->uri)  ;
				$itm['vid_author_link'] = $vid->user->link;
				$itm['vid_author_picutre'] = @$vid->user->pictures->sizes[count($vid->user->pictures->sizes) -1]->link;
				$itm['vid_comments_count'] = $vid->metadata->connections->comments->total;
	
				$data = base64_encode(serialize ( $itm ));
					
					
				if( $this->is_execluded($camp->camp_id, $itm['vid_url']) ){
					  echo '<-- Execluded';
					continue;
				}
					
				if ( ! $this->is_duplicate($itm['vid_url']) )  {
					$query = "INSERT INTO {$this->wp_prefix}automatic_general ( item_id , item_status , item_data ,item_type) values (    '{$itm['vid_id']}', '0', '$data' ,'vm_{$camp->camp_id}_$keyword')  ";
					$this->db->query ( $query );
				} else {
					  echo ' <- duplicated <a href="'.get_edit_post_link($this->duplicate_id).'">#'.$this->duplicate_id.'</a>';
				}
	
				  echo '</li>';
				$i++;
	
			}
				
			  echo '</ol>';
				
			  echo '<br>Total '. $i .' videos added cached';
	
			//check if nothing found so deactivate
			if($i == 0){
				  echo '<br>No new vimeo vids found ';
				  echo '<br>Keyword have no more images deactivating...';
				$query = "update {$this->wp_prefix}automatic_keywords set keyword_start = -1 where keyword_id=$kid ";
				$this->db->query ( $query );
				if(! in_array('OPT_NO_DEACTIVATE', $camp_opt))
				$this->deactivate_key($camp->camp_id, $keyword);
					
			}
				
		}else{
				
			//no valid reply
			  echo '<br>No Valid reply for video search from vimeo<br>'.$exec;
				
			if(stristr($exec, 'valid user token') ){
				  echo '<br>Please visit the plugin settings and add a Vimeo api access token';
			}
	
			if(stristr($exec,'Page is out of bounds') || stristr($exec, 'enough content') ){
	
				$query = "update {$this->wp_prefix}automatic_keywords set keyword_start = -1 where keyword_id=$kid ";
				$this->db->query ( $query );
	
				//deactivate for 60 minutes
				if(! in_array('OPT_NO_DEACTIVATE', $camp_opt))
				$this->deactivate_key($camp->camp_id, $keyword);
	
	
			}
				
	
	
		}
	
	}
	

}