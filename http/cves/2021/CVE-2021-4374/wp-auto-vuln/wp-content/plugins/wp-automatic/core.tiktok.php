<?php

// Main Class
require_once 'core.php';
class WpAutomatictiktok extends wp_automatic {
	
	function tiktok_get_post($camp) {
		
		//random user agent 
		curl_setopt ( $this->ch, CURLOPT_USERAGENT,  $this->randomUserAgent() );
		  
		// ini keywords
		$camp_opt = unserialize ( $camp->camp_options );
		$keywords = explode ( ',', $camp->camp_keywords );
		$camp_general = unserialize ( base64_decode ( $camp->camp_general ) );
		
		// looping keywords
		foreach ( $keywords as $keyword ) {
			
			$keyword = trim ( $keyword );
			
			// update last keyword
			update_post_meta ( $camp->camp_id, 'last_keyword', trim ( $keyword ) );
			
			// when valid keyword
			if (trim ( $keyword ) != '') {
				
				// record current used keyword
				$this->used_keyword = $keyword;
				
				echo '<br>Let\'s post a TikTok Video for the key:' . $keyword;
				
			  
				// getting links from the db for that keyword
				$query = "select * from {$this->wp_prefix}automatic_general where item_type=  'tt_{$camp->camp_id}_$keyword' ";
				$res = $this->db->get_results ( $query );
				
				// when no links lets get new links
				if (count ( $res ) == 0) {
					
					// clean any old cache for this keyword
					$query_delete = "delete from {$this->wp_prefix}automatic_general where item_type='tt_{$camp->camp_id}_$keyword' ";
					$this->db->query ( $query_delete );
					
					// get new links
					$this->tiktok_fetch_items ( $keyword, $camp );
					
					// getting links from the db for that keyword
					$res = $this->db->get_results ( $query );
				}
				
				// check if already duplicated
				// deleting duplicated items
				
				$item_count = count ( $res );
				
				for($i = 0; $i < $item_count; $i ++) {
					
					$t_row = $res [$i];
					
					$t_data = unserialize ( base64_decode ( $t_row->item_data ) );
					
					$t_link_url = $t_data ['item_url'];
					
					echo '<br>Link:' . $t_link_url . '<-published:' . $t_data ['item_created_date'];
					
					// check if older than a specific date
					
					// check if older than minimum date
					if (in_array ( 'OPT_YT_DATE', $camp_opt )) {
						
						if ($this->is_link_old ( $camp->camp_id, strtotime ( $t_data ['item_created_date'] ) )) {
							unset ( $res [$i] );
							echo '<--old post execluding...';
							
							$query = "delete from {$this->wp_prefix}automatic_general where id={$t_row->id}";
							$this->db->query ( $query );
							
							continue;
						}
					}
					
					// check if link is duplicated
					if ($this->is_duplicate ( $t_link_url )) {
						
						// duplicated item let's delete
						unset ( $res [$i] );
						
						echo '<br>tiktok pic (' . $t_data ['item_title'] . ') found cached but duplicated <a href="' . get_permalink ( $this->duplicate_id ) . '">#' . $this->duplicate_id . '</a>';
						
						// delete the item
						$query = "delete from {$this->wp_prefix}automatic_general where id={$t_row->id}";
						$this->db->query ( $query );
					} else {
						
						break;
					}
				} // end for
				
				// check again if valid links found for that keyword otherwise skip it
				if (count ( $res ) > 0) {
					
					// lets process that link
					$ret = $res [$i];
					
					$temp = unserialize ( base64_decode ( $ret->item_data ) );
					
					
					// generating title
					if (@trim ( $temp ['item_title'] ) == '') {
						
						if (in_array ( 'OPT_IT_AUTO_TITLE', $camp_opt )) {
							
							echo '<br>No title generating...';
							
							$cg_it_title_count = $camp_general ['cg_it_title_count'];
							if (! is_numeric ( $cg_it_title_count ))
								$cg_it_title_count = 80;
								
								// Clean content from tags , emoji and more
								$contentClean = $this->removeEmoji ( strip_tags ( strip_shortcodes ( $this->strip_urls ( $temp ['item_description'] ) ) ) );
								
								// remove hashtags
								if (in_array ( 'OPT_TT_NO_TTL_TAG', $camp_opt )) {
									$contentClean = preg_replace ( '{#\S*}', '', $contentClean );
								}
								
								// remove mentions
								if (in_array ( 'OPT_TT_NO_TTL_MEN', $camp_opt )) {
									$contentClean = preg_replace ( '{@\S*}', '', $contentClean );
								}
								
								if (function_exists ( 'mb_substr' )) {
									$newTitle = (mb_substr ( $contentClean, 0, $cg_it_title_count ));
								} else {
									$newTitle = (substr ( $contentClean, 0, $cg_it_title_count ));
								}
								
								$temp ['item_title'] = in_array ( 'OPT_GENERATE_TW_DOT', $camp_opt ) ? ($newTitle) : ($newTitle) . '...';
						} else {
							
							$temp ['item_title'] = '(notitle)';
						}
					}
					
					// report link
					echo '<br>Found Link:' . $temp ['item_url'] . ' <-published:' . $t_data ['item_created_date'];
					
					// update the link status to 1
					$query = "delete from {$this->wp_prefix}automatic_general where id={$ret->id}";
					$this->db->query ( $query );

					// if cache not active let's delete the cached videos and reset indexes
					if (! in_array ( 'OPT_IT_CACHE', $camp_opt )) {
						echo '<br>Cache disabled claring cache ...';
						$query = "delete from {$this->wp_prefix}automatic_general where item_type='tt_{$camp->camp_id}_$keyword' ";
						$this->db->query ( $query );
						
						// reset index
						$query = "update {$this->wp_prefix}automatic_keywords set keyword_start =1 where keyword_camp={$camp->camp_id}";
						$this->db->query ( $query );
						
						delete_post_meta ( $camp->camp_id, 'wp_tiktok_next_max_id' . md5 ( $keyword ) );
					}
					
					$temp['item_embed'] = '<blockquote class="tiktok-embed" cite="'. $temp['item_url'] .'" data-video-id="' . $temp['item_id'] . '" style="max-width: 605px;min-width: 325px;" ><section> </section> </blockquote> <script async src="https://www.tiktok.com/embed.js"></script>';
					

				
					
					// remove hashtags
					if (in_array ( 'OPT_TT_NO_CNT_TAG', $camp_opt )) {
						$temp ['item_description'] = preg_replace ( '{#\S*}', '', $temp ['item_description'] );
					}
					
					// fix date
					$temp ['item_created_date'] = get_date_from_gmt ( $temp ['item_created_date'] );
					
			 
					
					if(in_array('OPT_IT_CACHE', $camp_opt)){
						echo '<br>Cache enabled, lets retrive fresh images...';
						
						//curl get
						$x='error';
						curl_setopt( $this->ch, CURLOPT_HTTPGET, 1);
						curl_setopt( $this->ch, CURLOPT_URL,  $temp['item_url'] );
						$exec=curl_exec( $this->ch );
						$x=curl_error($this->ch);
					 
						//"originCover":"https:/
						preg_match ( '!"originCover":"(.*?)"!' ,  $exec , $matches);
						
						if(isset($matches[1]) && trim($matches[1]) != '' ){
							echo '<-- found cover pic';
							$temp ['item_img'] = wp_automatic_fix_json_part( $matches[1] );
						}else{
							echo '<-- cover pic not found';
						}
						
						//"avatarThumb"
						preg_match ( '!"avatarThumb":"(.*?)"!' ,  $exec , $matches);
						
						if(isset($matches[1]) && trim($matches[1]) != '' ){
							echo '<-- found profile pic';
							$temp ['item_user_profile_pic'] = wp_automatic_fix_json_part( $matches[1] );
						}else{
							echo '<-- profile pic not found';
						}
						
						
						
					}
					
					// item images ini
					$temp ['item_images'] = '<img src="' . $temp ['item_img'] . '" />';
					 
					
					return $temp;
				} else {
					echo '<br>No links found for this keyword';
				}
			} // if trim
		} // foreach keyword
	}
	function tiktok_fetch_items($keyword, $camp) {
		
		// report
		echo "<br>So I should now get some items from tiktok for keyword :" . $keyword;
	 	
		//random user agent
		$random_agent = $this->randomUserAgent();
		curl_setopt ( $this->ch, CURLOPT_USERAGENT,  $random_agent );
		
		// ini options
		$camp_opt = unserialize ( $camp->camp_options );
		if (stristr ( $camp->camp_general, 'a:' ))
			$camp->camp_general = base64_encode ( $camp->camp_general );
			
			$camp_general = unserialize ( base64_decode ( $camp->camp_general ) );
			$camp_general = array_map ( 'wp_automatic_stripslashes', $camp_general );
			
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
					
					if (! in_array ( 'OPT_IT_CACHE', $camp_opt )) {
						$start = 1;
						echo '<br>Cache disabled resetting index to 1';
					} else {
						
						// check if it is reactivated or still deactivated
						if ($this->is_deactivated ( $camp->camp_id, $keyword )) {
							$start = 1;
						} else {
							// still deactivated
							return false;
						}
					}
				} else {
					
					if (! in_array ( 'OPT_IT_CACHE', $camp_opt )) {
						$start = 1;
						echo '<br>Cache disabled resetting index to 1';
					}
				}
				
				echo ' index:' . $start;
				
				// update start index to start+1
				$nextstart = $start + 1;
				$query = "update {$this->wp_prefix}automatic_keywords set keyword_start = $nextstart where keyword_id=$kid ";
				$this->db->query ( $query );
				
				// pagination
				if ( $start == 1 ) {
					
					// use first base query
					$wp_tiktok_next_max_id = 0;
					echo ' Posting from the first page...';
				} else {
					
					// not first page get the bookmark
					$wp_tiktok_next_max_id = get_post_meta ( $camp->camp_id, 'wp_tiktok_next_max_id' . md5 ( $keyword ), 1 );
					
					if (trim ( $wp_tiktok_next_max_id ) == '') {
						echo '<br>No new page max id';
						$wp_tiktok_next_max_id = 0;
					} else {
						if (in_array ( 'OPT_IT_CACHE', $camp_opt )) {
							echo '<br>max_id:' . $wp_tiktok_next_max_id;
						} else {
							$start = 1;
							echo '<br>Cache disabled resetting index to 1';
							$wp_tiktok_next_max_id = 0;
						}
					}
				}
				
				// if specific user posting
				if (in_array ( 'OPT_TT_USER', $camp_opt )) {
					
					$cg_tt_user = trim ( $camp_general ['cg_tt_user'] );
					echo '<br>Specific user:' . $cg_tt_user;
					$qkeyword_md5 = '__' . md5($cg_tt_user);
					
					// prepare keyword
					  
					$challenge_id = get_post_meta( $camp->camp_id ,  $qkeyword_md5 , true );
					
					//get challenge if empty
					if(trim($challenge_id) == '' ){
						//get the challenge
						echo '<br>Getting the challenge ID for the user.... ' ;
						
						//curl get
						$x='error';
						$url='https://www.tiktok.com/@' .$cg_tt_user ;
						curl_setopt($this->ch, CURLOPT_HTTPGET, 1);
						curl_setopt($this->ch, CURLOPT_URL, trim($url));
						$exec=curl_exec($this->ch);
						  
						//tiktok-verify-page
						if( true || stristr($exec, 'tiktok-verify-page') ){
							echo '<br>Found TikTok capacha page... using auto proxy...';
							
							$new_exec = $this->wp_automatic_auto_proxy($url, 'secUid');
							
							if( strpos($new_exec,  'secUid' ) ){
								$exec = $new_exec;
							}
							
						}
						
						//"secUid":"MS4wLjABAAAAb6sufevCrDdNOnNRH7uwM_esGiD5sfScJxGfEQBsDyW6K10YJcwlvGR-lzqMBidP"
						preg_match('!"secUid":"(.*?)"!' , $exec , $challenges_found);
						
						if(isset($challenges_found[1]) && trim($challenges_found[1]) != ''){
							add_post_meta( $camp->camp_id ,  $qkeyword_md5 , $challenges_found[1] );
							$challenge_id = $challenges_found[1];
							echo '<-- Found:' . $challenge_id;
							
						}else{
							echo '<-- Not Found';
							
							echo $exec;
							
						}
					}
					
					//get by user
					$random_agent_enc = urlencode($random_agent);
					echo '<br>Random agent:'.$random_agent;
					$tik_url = "https://m.tiktok.com/api/post/item_list/?aid=1988&app_name=tiktok_web&device_platform=web&referer=&root_referer=https:%2F%2Fwww.google.com%2F&user_agent=Mozilla%2F5.0+(Macintosh%3B+Intel+Mac+OS+X+10_14_6)+AppleWebKit%2F537.36+(KHTML,+like+Gecko)+Chrome%2F88.0.4324.96+Safari%2F537.36&cookie_enabled=true&screen_width=1280&screen_height=800&browser_language=en-US&browser_platform=MacIntel&browser_name=Mozilla&browser_version=5.0+(Macintosh%3B+Intel+Mac+OS+X+10_14_6)+AppleWebKit%2F537.36+(KHTML,+like+Gecko)+Chrome%2F88.0.4324.96+Safari%2F537.36&browser_online=true&ac=3g&timezone_name=Africa%2FCairo&page_referer=https:%2F%2Fwww.tiktok.com%2Ftag%2Fforyoupage%3Flang%3Den&priority_region=&verifyFp=verify_kkdzsf0j_twyzymaf_rj0P_4sMu_A03Z_PyrC1RBW8ssF&appId=1233&region=EG&appType=m&isAndroid=false&isMobile=false&isIOS=false&OS=mac&did=6873144183268607493&count=30&cursor=$wp_tiktok_next_max_id&secUid=$challenge_id&language=en&_signature=_02B4Z6wo00d014helegAAIDBhMNkfYr8pPuIXpFAAIJ642" ;
					$tik_url = "https://m.tiktok.com/api/post/item_list/?aid=1988&app_name=tiktok_web&device_platform=web&referer=&root_referer=https:%2F%2Fwww.google.com%2F&user_agent=$random_agent_enc&cookie_enabled=true&screen_width=1280&screen_height=800&browser_language=en-US&browser_platform=MacIntel&browser_name=Mozilla&browser_version=5.0+(Macintosh%3B+Intel+Mac+OS+X+10_14_6)+AppleWebKit%2F537.36+(KHTML,+like+Gecko)+Chrome%2F88.0.4324.96+Safari%2F537.36&browser_online=true&ac=3g&timezone_name=Africa%2FCairo&page_referer=https:%2F%2Fwww.tiktok.com%2Ftag%2Fforyoupage%3Flang%3Den&priority_region=&verifyFp=verify_kkdzsf0j_twyzymaf_rj0P_4sMu_A03Z_PyrC1RBW8ssF&appId=1233&region=EG&appType=m&isAndroid=false&isMobile=false&isIOS=false&OS=mac&did=6873144183268607493&count=30&cursor=$wp_tiktok_next_max_id&secUid=$challenge_id&language=en&_signature=_02B4Z6wo00d014helegAAIDBhMNkfYr8pPuIXpFAAIJ642" ;
					
					
				} else {
					
					// prepare keyword
					$qkeyword = trim(str_replace ( ' ', '', $keyword ));
					$qkeyword = str_replace ( '#', '', $qkeyword );
					$qkeyword_md5 = '__' . md5($qkeyword);
					
					$challenge_id = get_post_meta( $camp->camp_id ,  $qkeyword_md5 , true );
					 
					//get challenge if empty 
					if(trim($challenge_id) == '' ){
						//get the challenge
						
						echo '<br>Getting the challenge ID for the term.... ' ;
						
						//curl get
						$x='error';
						$url='https://www.tiktok.com/tag/' .$qkeyword ;
						curl_setopt($this->ch, CURLOPT_HTTPGET, 1);
						curl_setopt($this->ch, CURLOPT_URL, trim($url));
						$exec=curl_exec($this->ch);
						 
						preg_match('!challenge/detail/(\d*)!' , $exec , $challenges_found);

						if(isset($challenges_found[1]) && is_numeric($challenges_found[1])){
							add_post_meta( $camp->camp_id ,  $qkeyword_md5 , $challenges_found[1] );
							$challenge_id = $challenges_found[1];
							echo '<-- Found:' . $challenge_id;
						}
					}
			 		
					//get by keyword
					$tik_url = "https://m.tiktok.com/api/challenge/item_list/?aid=1988&app_name=tiktok_web&device_platform=web&referer=&root_referer=https:%2F%2Fwww.google.com%2F&user_agent=Mozilla%2F5.0+(Macintosh%3B+Intel+Mac+OS+X+10_14_6)+AppleWebKit%2F537.36+(KHTML,+like+Gecko)+Chrome%2F88.0.4324.96+Safari%2F537.36&cookie_enabled=true&screen_width=1280&screen_height=800&browser_language=en-US&browser_platform=MacIntel&browser_name=Mozilla&browser_version=5.0+(Macintosh%3B+Intel+Mac+OS+X+10_14_6)+AppleWebKit%2F537.36+(KHTML,+like+Gecko)+Chrome%2F88.0.4324.96+Safari%2F537.36&browser_online=true&ac=3g&timezone_name=Africa%2FCairo&page_referer=https:%2F%2Fwww.tiktok.com%2F%3Flang%3Den&priority_region=&verifyFp=verify_kkdzsf0j_twyzymaf_rj0P_4sMu_A03Z_PyrC1RBW8ssF&appId=1233&region=EG&appType=m&isAndroid=false&isMobile=false&isIOS=false&OS=mac&did=6873144183268607493&challengeID=$challenge_id&count=30&cursor=$wp_tiktok_next_max_id&_signature=_02B4Z6wo00901oZG7CQAAIDAitsds2tULt6GR-iAAMGN65" ;
					
				}
	 
				
				//get 
				//curl get
				echo '<br>Loading:' . $tik_url;
				$x='error';
				curl_setopt($this->ch, CURLOPT_HTTPGET, 1);
				curl_setopt($this->ch, CURLOPT_URL, trim($tik_url));
				$exec=curl_exec($this->ch);
				$x=curl_error($this->ch);
				
			 
				$items = array();
				
				
				
				if( strpos($exec, 'itemList') ){
					$json_arr = json_decode($exec);
					$items = $json_arr->itemList;
				 
					
					// reverse
					if (in_array ( 'OPT_TT_REVERSE', $camp_opt )) {
						echo '<br>Reversing order';
						$items = array_reverse ( $items );
					}
					
					echo '<ol>';
					
					// loop items
					$i = 0;
					foreach ( $items as $item ) {
						
						//echo '<pre>';
						//print_r($item);
						
						// clean itm
						unset ( $itm );
						
						// report https://www.tiktok.com/@kelly_bove/video/6855066585514659077
						
						
						// build item
						$itm ['item_id'] = $item->id;
						$itm ['item_url'] = 'https://www.tiktok.com/@' . $item->author->uniqueId . '/video/' . $item->id;
						
						echo '<li>' . $itm ['item_url'];
						
						
						$itm ['item_description'] = $item->desc;
						$itm ['video_view_count'] = $item->stats->playCount;
						$itm ['item_img'] = $item->video->originCover;
						$itm ['item_img_width'] = $item->video->width;
						$itm ['item_img_height'] = $item->video->height;
						$itm ['item_user_id'] = $item->author->id;
						$itm ['item_created_time'] = $item->createTime;
						
						// item date
						$itm ['item_created_date'] = date ( 'Y-m-d H:i:s', $item->createTime );
						$itm ['item_likes_count'] = $item->stats->diggCount;
						$itm ['item_user_username'] = $item->author->uniqueId;
						$itm ['item_user_link'] = 'https://www.tiktok.com/@' .$item->author->uniqueId;
						
						// full name
						$itm ['item_user_name'] = $item->author->nickname;;
						$itm ['item_user_profile_pic'] = $item->author->avatarThumb;
						
						//item_tags
						$all_tags = array();
						
						if(isset($item->challenges)){
							foreach($item->challenges as $new_tag){
								$all_tags[] = $new_tag->title;
							}
						}
						$itm ['item_tags'] = implode(',', $all_tags); 
						 
						
						// comments postponed
						/*
						$commentsArray = array ();
						$itm ['item_comments'] = $commentsArray;
						*/
					 
						 
						$data = base64_encode ( serialize ( $itm ) );
						
						$i ++;
						
						if ($this->is_execluded ( $camp->camp_id, $itm ['item_url'] )) {
							echo '<-- Execluded';
							continue;
						}
						
						// check if old
						$was_there_old_items = false;
						
						if (in_array ( 'OPT_YT_DATE', $camp_opt )) {
							
							if ($this->is_link_old ( $camp->camp_id, strtotime ( $itm ['item_created_date'] ) )) {
								
								unset ( $items [$i] );
								echo '<--old post execluding...';
								
								$was_there_old_items = true;
								
								continue;
							}
						}
						
						if (! $this->is_duplicate ( $itm ['item_url'] )) {
							$query = "INSERT INTO {$this->wp_prefix}automatic_general ( item_id , item_status , item_data ,item_type) values (    '{$itm['item_id']}', '0', '$data' ,'tt_{$camp->camp_id}_$keyword')  ";
							$this->db->query ( $query );
						} else {
							echo ' <- duplicated <a href="' . get_edit_post_link ( $this->duplicate_id ) . '">#' . $this->duplicate_id . '</a>';
						}
						
						echo '</li>';
					}
					
					echo '</ol>';
					
					echo '<br>Total ' . $i . ' pics found & cached';
					
					// check if nothing found so deactivate
					if ($i == 0) {
						echo '<br>No new items got found ';
						echo '<br>Keyword have no more items deactivating...';
						$query = "update {$this->wp_prefix}automatic_keywords set keyword_start = -1 where keyword_id=$kid ";
						$this->db->query ( $query );
						
						if (! in_array ( 'OPT_NO_DEACTIVATE', $camp_opt ))
							$this->deactivate_key ( $camp->camp_id, $keyword );
							
							// delete bookmark value
							delete_post_meta ( $camp->camp_id, 'wp_tiktok_next_max_id' . md5 ( $keyword ) );
							 
					} else {
						
						 
						// get max id
						if (isset ( $json_arr->hasMore ) && $json_arr->hasMore == 1) {
							
							echo '<br>Updating max_id:' . $json_arr->cursor;
							update_post_meta ( $camp->camp_id, 'wp_tiktok_next_max_id' . md5 ( $keyword ), $json_arr->cursor);
						} else {
							echo '<br>No pagination found deleting next page index';
							delete_post_meta ( $camp->camp_id, 'wp_tiktok_next_max_id' . md5 ( $keyword ) );
							
							// disable queries for an hour if cache disabled
							if (in_array ( 'OPT_IT_CACHE', $camp_opt )) {
								
								$query = "update {$this->wp_prefix}automatic_keywords set keyword_start = -1 where keyword_id=$kid ";
								$this->db->query ( $query );
								
								if (! in_array ( 'OPT_NO_DEACTIVATE', $camp_opt ))
									$this->deactivate_key ( $camp->camp_id, $keyword );
									
									// delete bookmark value
									delete_post_meta ( $camp->camp_id, 'wp_tiktok_next_max_id' . md5 ( $keyword ) );
							}
						}
					}
				} else {
					
					// no valid reply
					echo '<br>No Valid reply for tiktok search <br>' . $exec;
				}
	}
}