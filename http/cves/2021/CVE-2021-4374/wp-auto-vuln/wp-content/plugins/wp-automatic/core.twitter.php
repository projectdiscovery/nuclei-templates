<?php

// Main Class
require_once 'core.php';

Class WpAutomaticTwitter extends wp_automatic{


function twitter_fetch_items($keyword,$camp ){

	//report
	  echo "<br>So I should now get some tweets from Twitter for Search :" . $keyword;

	//verify twitter token
	$wp_automatic_tw_consumer = trim( get_option('wp_automatic_tw_consumer',''));
	$wp_automatic_tw_secret = trim( get_option('wp_automatic_tw_secret',''));

	if( ($wp_automatic_tw_consumer) == '' || $wp_automatic_tw_consumer == ''){
		  echo '<br>Twitter consumer key and secret key are required, please visit the settings page and add it';
		return false;
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

		if( ! in_array( 'OPT_IT_CACHE' , $camp_opt )){
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

	}elseif( ! in_array( 'OPT_IT_CACHE' , $camp_opt ) ){
		$start =1;
		echo '<br>Cache disabled resetting index to 1';
	}


	//generating token if not exists
	$wp_automatic_tw_token = get_option('wp_automatic_tw_token','');

	if(trim($wp_automatic_tw_token) == ''){
			
		echo '<br>Generating a new twitter access token...';			
		$concated = urlencode($wp_automatic_tw_consumer) . ':'. urlencode($wp_automatic_tw_secret);
			
		$concatedBase64 = base64_encode($concated);

		//curl get
		$x='error';
		$url='https://api.twitter.com/oauth2/token';
			
		curl_setopt($this->ch,CURLOPT_HTTPHEADER,array("Authorization:Basic $concatedBase64" , "Content-Type:application/x-www-form-urlencoded;charset=UTF-8."));
			
		//curl post
		curl_setopt($this->ch, CURLOPT_URL, $url);
		curl_setopt($this->ch, CURLOPT_POST, true);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
		$exec=curl_exec($this->ch);
		
		 
		$x=curl_error($this->ch);
			
		if(stristr($exec, 'bearer')){

			$token_json = json_decode($exec);
			$wp_automatic_tw_token = $token_json->access_token;

			if(trim($wp_automatic_tw_token) == ''){
				  echo '<br>Can not extract twitter token from twitter response:'.$exec;
			}else{
				update_option('wp_automatic_tw_token', $wp_automatic_tw_token);
			}


		}else{
			  echo '<br>Response from twitter does not contain the expected token:'.$exec;
			return false;
		}
			
	}

	//good we now have a valid twitter token
	  echo ' index:' . $start;

	// update start index to start+1
	$nextstart = $start + 1;
	$query = "update {$this->wp_prefix}automatic_keywords set keyword_start = $nextstart where keyword_id=$kid ";
	$this->db->query ( $query );
 
	//building the twitter url
	$url='https://api.twitter.com/1.1/search/tweets.json?tweet_mode=extended&q='.urlencode(trim($keyword));

	if(stristr($keyword, 'from:')){
		
		$userKey = str_replace('from:', '', $keyword);
		
		$url = 	$url='https://api.twitter.com/1.1/statuses/user_timeline.json?tweet_mode=extended&screen_name='.urlencode(trim($userKey));
		
	}
	
	//language
	if(in_array('OPT_TW_COUNTRY', $camp_opt)){
			
		$cg_tw_lang = $camp_general['cg_tw_lang'];
			
		if(trim($cg_tw_lang) != ''){
			$url.='&lang='.trim($cg_tw_lang);
		}
	}

		
	//pagination
	// get requrest url from the zero index
	if( $start == 1 ){

		//use first base query


	}else{

		//not first page get the bookmark
		$wp_tw_next_max_id = get_post_meta ($camp->camp_id,'wp_twitter_next_max_id'.md5($keyword),1);

		if(trim($wp_tw_next_max_id) == ''){
			  echo '<br>No new page max id';
				
		}else{
			  echo '<br>max_id:'.$wp_tw_next_max_id;
			$url = $url ."&max_id=".$wp_tw_next_max_id ;
		}

	}

	//report url
	 echo '<br>Twitter url:'.$url;
		
	//skip ssl
	curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
	
	//authorize
	curl_setopt($this->ch,CURLOPT_HTTPHEADER,array("Authorization: Bearer $wp_automatic_tw_token"));
	curl_setopt($this->ch, CURLOPT_HTTPGET, 1);
	curl_setopt($this->ch, CURLOPT_URL, trim($url));
	$exec=curl_exec($this->ch);
	$x=curl_error($this->ch);

	
	//validating reply
	if(stristr($exec, 'search_metadata')   || (stristr($keyword, 'from:') && stristr($exec, '{')  ) ){
		//valid reply

		//handle pins
		$arr = json_decode($exec);
	 
		if(stristr($keyword, 'from:')){
			$items = $arr;			
		}else{
			$items = $arr->statuses;
		}
			
		//reverse
		if(in_array('OPT_PT_REVERSE', $camp_opt)){
			  echo '<br>Reversing order';
			$items = array_reverse($items);
		}
			
		  echo '<ol>';

		//loop pins
		$i = 0;
		$max_id = 99999999999999999999999999999999999999;
		
		 
		
		foreach ($items as $item){
			
			
			$itm = array();
			
			$i++;
		 
			//max_id
			if($item->id < $max_id)
			$max_id = $item->id;
			$max_id_str = $item->id_str;
			
			//report
			$itemUrl = 'https://twitter.com/'.$item->user->screen_name.'/statuses/'.$item->id_str ;
		    echo '<li>'.$itemUrl;

			//check if retweet
			if(in_array('OPT_TW_RT',$camp_opt)){
				if(isset($item->retweeted_status)){
					  echo '<-- Retweet skipping...';
					continue;
				}
			}
			
			//check if reply to
			if(in_array('OPT_TW_RE',$camp_opt)){
				if(isset($item->in_reply_to_user_id) && trim($item->in_reply_to_user_id) != '' ){
					if($item->in_reply_to_user_id != $item->user->id)
					  echo '<-- Reply skipping...';
					continue;
				}
			}
				
			//build item\
			
			//If RT, replace the status with the original tweet
			if(isset($item->retweeted_status)){
				$item = $item->retweeted_status;
			}
			
			// HASHTAG
			if(in_array('OPT_TW_TAG', $camp_opt)){
				if ( count($item->entities->hashtags) > 0 ){
					$hashtags = $item->entities->hashtags;
					$hashtagsArr = array();
					foreach ($hashtags as $hashtag){
						$hashtagsArr[] =  $hashtag->text;
					}
					
					$itm['item_hashtags'] = implode(',', $hashtagsArr);
					
				}
			}
			 
			$itm['item_id']  = $item->id;
			$itm['item_url'] = 'https://twitter.com/'.$item->user->screen_name.'/statuses/'.$item->id_str ;
			$itm['item_description'] = $item->full_text;
			
			
			//fix &amp;
			$itm['item_description'] = str_replace('&amp;', '&', $itm['item_description']);
			
			$itm['item_description'] =  $this->hyperlink_this( $itm['item_description']);
			 
			//original post link
			$original_post_url =  '';
			$original_post_url = isset($item->entities->urls[0]->expanded_url) ? $item->entities->urls[0]->expanded_url : '' ;
			
		 
			if(trim($original_post_url) == ''){
				$original_post_url = $itm['item_url'];
			}
			
			$itm['item_original_link'] = $original_post_url ; 
				
			//check images
			$itm['item_image'] ='';
			$all_imgs = '';
			if(isset($item->entities->media[0])){

				$media_img =$item->entities->media[0];

				if($media_img->type == 'photo'){
					//good let's append it
					$all_imgs.= '<img src="'.$media_img->media_url_https.'" /><br>' ;
					$itm['item_image'] = $media_img->media_url_https;
				}
					
			}
				
			$itm['item_retweet_count'] = $item->retweet_count;
			$itm['item_favorite_count'] = $item->favorite_count;
			$itm['item_author_id'] = $item->user->id_str;
			$itm['item_author_name'] = $item->user->name;
			$itm['item_author_screen_name'] = $item->user->screen_name;
			$itm['item_author_description'] = $item->user->description;
			$itm['item_author_url'] = $item->user->url;
				
			if(trim($itm['item_author_url']) == ''){
				$itm['item_author_url'] = 'https://twitter.com/intent/user?user_id='.$itm['item_author_id'];
			}
				
			$itm['item_author_profile_image'] = str_replace('normal', '200x200', $item->user->profile_image_url  )  ;
			$itm['item_author_profile_background_image'] = $item->user->profile_background_image_url;
			$itm['item_created_at'] = $item->created_at;

			// VIDEO
			$itm['item_video_url'] = '';
			if(isset($item->extended_entities)){
				if(isset($item->extended_entities->media)){
					if(isset($item->extended_entities->media[0]->type) && ($item->extended_entities->media[0]->type == 'video' || $item->extended_entities->media[0]->type == 'animated_gif'  )  ){
						$vidURL =  'https://twitter.com/'.$itm['item_author_screen_name'].'/status/'.$itm['item_id'];
						$itm['item_video_url'] = $vidURL;
					}
				}
			}
			
			// More images if exist
			if(isset($item->extended_entities)){
				if(isset($item->extended_entities->media)){
					foreach ($item->extended_entities->media as $media_item){
						if($media_item->type == 'photo'){
							if(! stristr($all_imgs, $media_item->media_url_https)){
								$all_imgs.= '<br><img src="' . $media_item->media_url_https . '"/>';
							}
						}
					}
				}
			}
			
			
			$itm['item_description'] = $all_imgs . '<br><br>' . $itm['item_description'];
			
			//expand URLs
			if(  in_array('OPT_TW_EXPAND', $camp_opt) ){
				
				if(isset($item->entities->urls)){
					
					foreach ($item->entities->urls as $single_url){
						
						$itm['item_description'] = str_replace( 'href="' . $single_url->url . '"' , 'href="'. $single_url->expanded_url . '"' , $itm['item_description'] );
						$itm['item_description'] = str_replace( '>' . $single_url->url . '<' , '>' .  $single_url->display_url . '<' , $itm['item_description'] );
						  
					}
					
				}
				
			}
			 
			$data = base64_encode(serialize ( $itm ));

			if( $this->is_execluded($camp->camp_id, $itm['item_url']) ){
				  echo '<-- Execluded';
				continue;
			}
			
			//check if old
			if( in_array('OPT_YT_DATE', $camp_opt)     ){
				if($this->is_link_old($camp->camp_id,  strtotime(  $item->created_at  ) )){
					  echo '<--old post execluding...';
					continue;
				}else{
					  echo ' <- created:'. $item->created_at ;
				}
			}

			if ( ! $this->is_duplicate($itm['item_url']) )  {
				
			
				
				
				$query = "INSERT INTO {$this->wp_prefix}automatic_general ( item_id , item_status , item_data ,item_type) values (    '{$itm['item_id']}', '0', '$data' ,'tw_{$camp->camp_id}_$keyword')  ";
				$this->db->query ( $query );
			} else {
				  echo ' <- duplicated <a href="'.get_edit_post_link($this->duplicate_id).'">#'.$this->duplicate_id.'</a>';
			}

			  echo '</li>';

		}

		echo '</ol>';
		echo '<br>Total '. $i .' Tweets found & cached';

		//check if nothing found so deactivate
		if($i == 0 || ($i==1 && $max_id == $wp_tw_next_max_id) ){
			
			echo '<br>No new tweets found ';
			echo '<br>Keyword has no more tweets deactivating...';
			$query = "update {$this->wp_prefix}automatic_keywords set keyword_start = -1 where keyword_id=$kid ";
			$this->db->query ( $query );
			
			if(! in_array('OPT_NO_DEACTIVATE', $camp_opt))
			$this->deactivate_key($camp->camp_id, $keyword);
				
			//delete bookmark value
			delete_post_meta($camp->camp_id, 'wp_twitter_next_max_id'.md5($keyword));
		
		}else{

			//get max id
			if($max_id != 0){
				echo '<br>Updating max ID '.$max_id_str;
				update_post_meta($camp->camp_id, 'wp_twitter_next_max_id'.md5($keyword), $max_id_str ) ;
					
			}else{
					
				echo '<br>No pagination found deleting next page index';
				delete_post_meta($camp->camp_id, 'wp_twitter_next_max_id'.md5($keyword));
			
			}

		}

	}else{
			
		//no valid reply
		  echo '<br>No Valid reply for twitter search <br>'.$exec;
			
	}



}
	
//Twitter
function twitter_get_post($camp){
		
	//ini keywords
	$camp_opt = unserialize ( $camp->camp_options );
	$keywords = explode ( ',', $camp->camp_keywords );
	$camp_general=unserialize(base64_decode($camp->camp_general));
		
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
			$query = "select * from {$this->wp_prefix}automatic_general where item_type=  'tw_{$camp->camp_id}_$keyword' ";
			$res = $this->db->get_results ( $query );
				
			// when no links lets get new links
			if (count ( $res ) == 0) {
				
				//clean any old cache for this keyword
				$query_delete = "delete from {$this->wp_prefix}automatic_general where item_type='tw_{$camp->camp_id}_$keyword' ";
				$this->db->query ( $query_delete );
					
				//get new links
				$this->twitter_fetch_items( $keyword, $camp );
					
				// getting links from the db for that keyword
				$res = $this->db->get_results ( $query );
			}
				
			//check if already duplicated
			//deleting duplicated items
			$res_count = count($res);
			for($i=0;$i< $res_count;$i++){
					
				$t_row = $res[$i];
					
				$t_data =  unserialize (base64_decode( $t_row->item_data) );
					
				$t_link_url=$t_data['item_url'];
					
				if( $this->is_duplicate($t_link_url) ){
						
					//duplicated item let's delete
					unset($res[$i]);
						
					echo '<br>Tweet ('. $t_data ['item_title'] .') found cached but duplicated <a href="'.get_permalink($this->duplicate_id).'">#'.$this->duplicate_id.'</a>'  ;
						
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
					
				//generating title
				if(   @trim($temp['item_title']) == '' ){
						
					if(in_array('OPT_IT_AUTO_TITLE', $camp_opt)){
							
						  echo '<br>No title generating...';
							
						$cg_it_title_count = $camp_general['cg_it_title_count'];
						if(! is_numeric($cg_it_title_count)) $cg_it_title_count = 80;
 
						
						//remove links 
						$cleanContent = preg_replace('{<a .*?a>}' , '' , $temp['item_description'] );
						$cleanContent = $this->removeEmoji( $this->strip_urls( strip_tags($cleanContent) ));
						
						 
						
						if(function_exists('mb_substr')){
							$newTitle = ( mb_substr($cleanContent , 0,$cg_it_title_count));
						}else{
							$newTitle = ( substr( $cleanContent , 0,$cg_it_title_count));
						}
							
						// Clean RT's RT @GoogleStreetArt:
						if( stristr($newTitle, 'RT') && in_array('OPT_IT_TITLE_CLEAN', $camp_opt)){
							  echo '<br>Cleaning RT';
							$newTitle = preg_replace('{RT @.*?: }', '', $newTitle);
						}
							
						if(in_array('OPT_GENERATE_TW_DOT', $camp_opt)){
							$temp['item_title'] = ($newTitle);
						}else{
							$temp['item_title'] = ($newTitle).'...';
						}
						  echo '<br>Generated title:'.$temp['item_title'];
							
							
					}else{
							
						$temp['item_title'] = '(notitle)';
							
					}
						
				}
					
					
				//report link
				  echo '<br>Found Link:'.$temp['item_url'] ;
					
				// update the link status to 1
				$query = "delete from {$this->wp_prefix}automatic_general where id={$ret->id}";
				$this->db->query ( $query );
					
				// if cache not active let's delete the cached items and reset indexes
				if (! in_array ( 'OPT_IT_CACHE', $camp_opt )) {
					 
					echo '<br>Cache disabled claring cache ...';
					$query = "delete from {$this->wp_prefix}automatic_general where item_type='tw_{$camp->camp_id}_$keyword' ";
					$this->db->query ( $query );
						
					// reset index
					$query = "update {$this->wp_prefix}automatic_keywords set keyword_start =1 where keyword_camp={$camp->camp_id}";
					$this->db->query ( $query );
						
					delete_post_meta($camp->camp_id, 'wp_instagram_next_max_id'.md5($keyword));

				}
					
				

				//if card OPT_TW_CARDS
				if(in_array('OPT_TW_CARDS', $camp_opt) || stristr($camp->camp_post_content, 'item_embed') ){
					
					$item_id = $temp['item_id'];

					//getting card embed https://api.twitter.com/1/statuses/oembed.json?url=https://twitter.com/zzz/status/463440424141459456

					  echo '<br>Getting embed code from twitter...';

					//curl get
					$x='error';
					$url='https://api.twitter.com/1/statuses/oembed.json?url=https://twitter.com/zzz/status/463440424141459456';
					$url= str_replace('463440424141459456', $item_id, $url);

					curl_setopt($this->ch, CURLOPT_HTTPGET, 1);
					curl_setopt($this->ch, CURLOPT_URL, trim($url));

					$exec=curl_exec($this->ch);
					$x=curl_error($this->ch);

					if(stristr($exec, 'widgets.js')){

						$json_embed = json_decode($exec);
							
						$embed_html = $json_embed->html;
							
						if(trim($embed_html) !=''){
							
							$temp['item_embed']=$embed_html;
							
							if(in_array('OPT_TW_CARDS', $camp_opt) ) {
								$temp['item_description']=$embed_html;
							}
							
							
						}else{
							  echo '<br>Can not extract embed html.';
						}
							
							
					}else{
						  echo '<br>Non expected embed reply.';
					}
						
				
				}
					
				//Auto embed video 
				$temp['item_video_embed'] = '';
				if(in_array('OPT_TW_VID_EMBED', $camp_opt) && ! stristr(($camp->camp_post_content), 'item_video_url') && trim($temp['item_video_url']) != ''  ){
					 
					$vidEmbed = '<blockquote class="twitter-video"><a href="'.$temp['item_video_url'].'"></a></blockquote>
<script async src="//platform.twitter.com/widgets.js" charset="utf-8"></script>
				';
					
					$temp['item_video_embed'] = $vidEmbed;
					
					$temp['item_description'] = $temp['item_description'] . $vidEmbed; 
				}
				
				//Fix date timezone
				$temp['item_created_at'] = get_date_from_gmt(  gmdate('Y-m-d H:i:s' ,strtotime($temp['item_created_at'] ) )   ) ;

				 
				
				//external image from shared links
				if(trim($temp['item_image']) == '' && trim($temp['item_original_link']) != '' && ! stristr($temp['item_original_link'], 'twitter.com') ){
					echo '<br>Extracting image from external link:'.$temp['item_original_link'];
					
					//curl get 
					$x='error';
					curl_setopt($this->ch, CURLOPT_HTTPGET, 1);
					curl_setopt($this->ch, CURLOPT_URL, trim( $temp['item_original_link'] ));
					
					if(stristr($temp['item_original_link'], 'bit.ly'))
					curl_setopt($this->ch,CURLOPT_ENCODING,'gzip, deflate, br');
					
					$exec=curl_exec($this->ch);
					$x=curl_error($this->ch);
					
					 
				 
					if( stristr($exec, 'twitter:image') || stristr($exec, 'og:image') ){
						preg_match('{twitter:image" content="(.*?)"}', $exec,$imgMatchs);
						
						 
						
						if( isset($imgMatchs[1]) && trim($imgMatchs[1]) == '') preg_match('{og:image" content="(.*?)"}', $exec,$imgMatchs);
						
						
						if( isset($imgMatchs[1]) &&  trim($imgMatchs[1]) != ''  ){
							$temp['item_image'] = $imgMatchs[1] ;
							$temp['item_description'] = '<img src="'.$imgMatchs[1] .'"/><br><br>'.$temp['item_description'];
						}
 						
					}
					
					 
					
				}
			 
				return $temp;
					
			} else {
					
				  echo '<br>No links found for this keyword';
			}
		} // if trim
	} // foreach keyword
		
		
		
}

}