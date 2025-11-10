<?php

// Main Class
require_once 'core.php';
class WpAutomaticYoutube extends wp_automatic {
	
	/*
	 * ---* youtube get links ---
	 */
	function youtube_fetch_links($keyword, $camp) {
		echo "<br>so I should now get some links from youtube for keyword :" . $keyword;
		
		// check if there is an api key added
		$wp_automatic_yt_tocken = trim ( wp_automatic_single_item ( 'wp_automatic_yt_tocken', '' ) );
		
		if (trim ( $wp_automatic_yt_tocken ) == '') {
			echo '<br>Youtube API key is required, please visit settings page and add it';
			return false;
		}
		
		// ini options
		$camp_opt = unserialize ( $camp->camp_options );
		if (stristr ( $camp->camp_general, 'a:' ))
			$camp->camp_general = base64_encode ( $camp->camp_general );
		$camp_general = unserialize ( base64_decode ( $camp->camp_general ) );
		$camp_general = array_map ( 'wp_automatic_stripslashes', $camp_general );
		
		$sortby = $camp->camp_youtube_order;
		$camp_youtube_category = $camp->camp_youtube_cat;
		
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
			
			// check if it is reactivated or still deactivated
			if ($this->is_deactivated ( $camp->camp_id, $keyword )) {
				$start = 1;
			} else {
				// still deactivated
				return false;
			}
		}
		
		// limit check
		$this->is_allowed_to_call ();
		
		echo ' index:' . $start;
		
		// update start index to start+50
		if (! in_array ( 'OPT_YT_CACHE', $camp_opt )) {
			echo '<br>Caching is not enabled setting youtube page to query to 1';
			$nextstart = 1;
		} else {
			$nextstart = $start + 50;
		}
		
		$query = "update {$this->wp_prefix}automatic_keywords set keyword_start = $nextstart where keyword_id=$kid ";
		$this->db->query ( $query );
		
		// get items
		$orderby = $camp->camp_youtube_order;
		$cat = $camp->camp_youtube_cat;
		
		if ($cat != 'All')
			$criteria .= '&category=' . $cat;
		
		// base url
		$search_url = "https://www.googleapis.com/youtube/v3/search?part=snippet&type=video&key=" . trim ( $wp_automatic_yt_tocken ) . "&maxResults=50";
		
		$naked_search_url = $search_url; // minimal version of the search query
		                                 
		// keyword add
		if (trim ( $keyword ) != '*') {
			$search_url = $search_url . '&q=' . urlencode ( trim ( $keyword ) );
		}
		
		if (in_array ( 'OPT_YT_DATE', $camp_opt )) {
			$beforeDate = $camp_general ['cg_yt_dte_year'] . "-" . $camp_general ['cg_yt_dte_month'] . "-" . $camp_general ['cg_yt_dte_day'] . 'T00:00:00Z';
			$search_url .= "&publishedAfter=" . $beforeDate;
		}
		
		// published before
		if (in_array ( 'OPT_YT_BEFORE', $camp_opt )) {
			if (stristr ( $camp_general ['cg_yt_before'], '-' )) {
				$search_url .= "&publishedBefore=" . trim ( $camp_general ['cg_yt_before'] ) . 'T00:00:00Z';
			}
		}
		
		// OPT_YT_LIMIT_EMBED
		if (in_array ( 'OPT_YT_LIMIT_EMBED', $camp_opt )) {
			$search_url .= "&videoEmbeddable=true";
		}
		
		// license
		$cg_yt_license = $camp_general ['cg_yt_license'];
		if (trim ( $cg_yt_license ) != '' && $cg_yt_license != 'any') {
			$search_url .= "&videoLicense=" . $cg_yt_license;
		}
		
		// cg_yt_type
		$cg_yt_type = $camp_general ['cg_yt_type'];
		if (trim ( $cg_yt_type ) != '' && $cg_yt_type != 'any') {
			$search_url .= "&videoType=" . $cg_yt_type;
		}
		
		// videoDuration
		$cg_yt_duration = $camp_general ['cg_yt_duration'];
		if (trim ( $cg_yt_duration ) != '' && $cg_yt_duration != 'any') {
			$search_url .= "&videoDuration=" . $cg_yt_duration;
		}
		
		// videoDefinition
		$cg_yt_definition = $camp_general ['cg_yt_definition'];
		if (trim ( $cg_yt_definition ) != '' && $cg_yt_definition != 'any') {
			$search_url .= "&videoDefinition=" . $cg_yt_definition;
		}
		
		// safeSearch
		$cg_yt_safe = $camp_general ['cg_yt_safe'];
		if (trim ( $cg_yt_safe ) != '' && $cg_yt_safe != 'moderate') {
			$search_url .= "&safeSearch=" . $cg_yt_safe;
		}
		
		// order
		$camp_youtube_order = $camp->camp_youtube_order;
		if (trim ( $camp_youtube_order ) == 'published')
			$camp_youtube_order = 'date';
		
		if ($camp_youtube_order != 'relevance')
			$search_url .= "&order=" . $camp_youtube_order;
		
		// videoCategoryId
		$videoCategoryId = $camp->camp_youtube_cat;
		if (trim ( $videoCategoryId ) != 'All' && is_numeric ( $videoCategoryId )) {
			$search_url .= "&videoCategoryId=" . $videoCategoryId;
		}
		
		// regionCode
		if (in_array ( 'OPT_YT_LIMIT_CTRY', $camp_opt ) && trim ( $camp_general ['cg_yt_ctr'] ) != '') {
			$search_url .= "&regionCode=" . trim ( $camp_general ['cg_yt_ctr'] );
		}
		
		// relevanceLanguage
		if (in_array ( 'OPT_YT_LIMIT_LANG', $camp_opt ) && trim ( $camp_general ['cg_yt_lang'] ) != '') {
			$search_url .= "&relevanceLanguage=" . trim ( $camp_general ['cg_yt_lang'] );
		}
		
		if (in_array ( 'OPT_YT_USER', $camp_opt )) {
			echo '<br>Fetching vids for specific User/Channel ' . $camp->camp_yt_user;
			
			$camp_yt_user = trim ( $camp->camp_yt_user );
			
			//https://www.youtube.com/channel/UCRrW0ddrbFnJCbyZqHHv4KQ
			if(  stristr( $camp_yt_user , 'https' ) ){
				$camp_yt_user = trim(str_replace('https://www.youtube.com/channel/', '', $camp_yt_user));
			}
			
		 
			// playlistify it to decrease used quote as normal search quote is 100 but playlist is 2 or
			if ( ! in_array ( 'OPT_YT_LIVE', $camp_opt ) && ! in_array ( 'OPT_YT_PLAYLIST', $camp_opt ) && ($search_url == $naked_search_url || $search_url == $naked_search_url . '&order=date')) {
				// lets playlistify
				
				echo '<br>Playlistifying....';
				
				$channel_playlist_key_name = 'wp_automatic_playlist_id_' . md5 ( trim ( $camp_yt_user ) );
				$channel_playlist_id = get_post_meta ( $camp->camp_id, $channel_playlist_key_name, true );
				
				// grab playlist ID for firt time
				if (trim ( $channel_playlist_id ) == '') {
					
					// get the ID from YT for first time
					$playlist_api_url = "https://www.googleapis.com/youtube/v3/channels?part=snippet%2CcontentDetails%2Cstatistics&id=" . $camp_yt_user . "&key=" . $wp_automatic_yt_tocken;
					
					echo '<br>Getting Playlist ID of this channel...';
					
					// curl get
					curl_setopt ( $this->ch, CURLOPT_HTTPGET, 1 );
					curl_setopt ( $this->ch, CURLOPT_URL, trim ( $playlist_api_url ) );
					$exec = curl_exec ( $this->ch );
					
					if (stristr ( $exec, '{' )) {
						$json_reply = json_decode ( $exec );
						
						if (isset ( $json_reply->items ) && isset ( $json_reply->items [0] )) {
							$channel_playlist_id = $json_reply->items [0]->contentDetails->relatedPlaylists->uploads;
							echo '<- found:' . $channel_playlist_id;
							
							update_post_meta ( $camp->camp_id, $channel_playlist_key_name, $channel_playlist_id );
						}
					}
				} // first playlist
				
				if (trim ( $channel_playlist_id ) != '') {
					// nice we have the uploads playlist lets playlistify now
					$camp_opt [] = 'OPT_YT_PLAYLIST';
					$camp_general ['cg_yt_playlist'] = $channel_playlist_id;
				}
			}
			
			// check if playlist
			if (in_array ( 'OPT_YT_PLAYLIST', $camp_opt )) {
				echo '<br>Specific Playlist:' . $camp_general ['cg_yt_playlist'];
				
				$part = "snippet";
				
				if (in_array ( 'OPT_YT_DATE', $camp_opt )) {
					$part = "snippet,contentDetails";
				}
				
				$search_url = "https://www.googleapis.com/youtube/v3/playlistItems?part={$part}&playlistId=" . $camp_general ['cg_yt_playlist'] . "&key=" . trim ( $wp_automatic_yt_tocken ) . "&maxResults=50";
			} else {
				
				$search_url .= "&channelId=" . ($camp_yt_user);
			}
		} elseif (in_array ( 'YT_ID', $camp_opt )) {
			
			// post by ID
			$search_url = 'https://www.googleapis.com/youtube/v3/videos?key=' . $wp_automatic_yt_tocken . '&part=snippet&id=' . urlencode ( trim ( $keyword ) );
		} else {
			// no user just search
		}
		
		// check nextpagetoken
		$nextPageToken = get_post_meta ( $camp->camp_id, 'wp_automatic_yt_nt_' . md5 ( $keyword ), true );
		
		if (in_array ( 'OPT_YT_CACHE', $camp_opt )) {
			
			if (trim ( $nextPageToken ) != '') {
				echo '<br>nextPageToken:' . $nextPageToken;
				$search_url .= '&pageToken=' . $nextPageToken;
			} else {
				echo '<br>No page token let it the first page';
			}
		}
		
		//live only OPT_YT_LIVE_ONLY
		if (in_array ( 'OPT_YT_LIVE_ONLY', $camp_opt )) {
			$search_url .= '&eventType=live';
		}
		
		//CC videoCaption only
		if (in_array ( 'OPT_YT_CC', $camp_opt )) {
			$search_url .= '&videoCaption=closedCaption';
		}
		
		echo '<br>Search URL:' . $search_url;
		
		// process request
		curl_setopt ( $this->ch, CURLOPT_HTTPGET, 1 );
		curl_setopt ( $this->ch, CURLOPT_URL, trim ( $search_url ) );
		$exec = curl_exec ( $this->ch );
		
		$x = curl_error ( $this->ch );
		
		// verify reply
		if (! stristr ( $exec, '"kind"' )) {
			echo '<br>Not valid reply from Youtube:' . $exec . $x;
			return false;
		}
		
		$json_exec = json_decode ( $exec );
		
		// check nextpage token
		if (isset ( $json_exec->nextPageToken ) && trim ( $json_exec->nextPageToken ) != '') {
			$newnextPageToken = $json_exec->nextPageToken;
			echo '<br>New page token:' . $newnextPageToken;
			update_post_meta ( $camp->camp_id, 'wp_automatic_yt_nt_' . md5 ( $keyword ), $newnextPageToken );
		} else {
			// delete the token
			echo '<br>No next page token';
			delete_post_meta ( $camp->camp_id, 'wp_automatic_yt_nt_' . md5 ( $keyword ) );
			
			// set start to -1 exhausted
			$query = "update {$this->wp_prefix}automatic_keywords set keyword_start = -1 where keyword_id=$kid";
			$this->db->query ( $query );
			
			// deactivate for 60 minutes
			if (! in_array ( 'OPT_NO_DEACTIVATE', $camp_opt )) {
				
				if (! in_array ( 'YT_ID', $camp_opt )) {
					$this->deactivate_key ( $camp->camp_id, $keyword );
				} else {
					$this->deactivate_key ( $camp->camp_id, $keyword, 0 );
				}
			}
		}
		
		// get items
		$search = array ();
		$search = $json_exec->items;
		
		// disable keyword if no new items
		if (count ( $search ) == 0) {
			echo '<br>No more vids for this keyword deactivating it ..';
			$query = "update {$this->wp_prefix}automatic_keywords set keyword_start = -1 where keyword_id=$kid";
			$this->db->query ( $query );
			
			// deleting nextpage token
			delete_post_meta ( $camp->camp_id, 'wp_automatic_yt_nt_' . md5 ( $keyword ) );
			
			// deactivate for 60 minutes
			if (! in_array ( 'OPT_NO_DEACTIVATE', $camp_opt ))
				$this->deactivate_key ( $camp->camp_id, $keyword );
			
			return;
		}
		
		echo '<ol>';
		
		// reversing?
		if (in_array ( 'OPT_YT_REVERSE', $camp_opt )) {
			echo '<br>Reversing vids list order.';
			$search = array_reverse ( $search );
		}
		
		foreach ( $search as $itm ) {
			
			// general added details
			$general = array ();
			
			// get vid id from response
			if (stristr ( $search_url, 'playlistItems' )) {
				$vid_id = $itm->snippet->resourceId->videoId;
			} elseif (in_array ( 'YT_ID', $camp_opt )) {
				$vid_id = $itm->id;
			} else {
				$vid_id = $itm->id->videoId;
			}
			
			// vid url
			$link_url = 'https://www.youtube.com/watch?v=' . $vid_id;
			$httplink_url = 'http://www.youtube.com/watch?v=' . $vid_id;
			
			// vid thumbnail
			$link_img = $itm->snippet->thumbnails->high->url;
			
			// get largest size
			// $link_img = str_replace('hqdefault', 'hqdefault', $link_img);
			
			// get item title
			$link_title = addslashes ( $itm->snippet->title );
			
			// Skip private videos
			if ($link_title == 'Private video') {
				continue;
			}
			
			// Skip premiere
			if (isset ( $itm->snippet->liveBroadcastContent ) && $itm->snippet->liveBroadcastContent == 'upcoming') {
				continue;
			}
			
			// get item description
			$link_desc = addslashes ( $itm->snippet->description );
			
			// report link
			echo '<li>' . $link_title . '</li>';
			
			// validate exact and banned
			if (! $this->validate_exacts ( $link_desc, $link_title, $camp_opt, $camp )) {
				continue;
			}
			
			// channel title
			$general ['vid_author_title'] = $itm->snippet->channelTitle;
			
			// channel id
			$author = addslashes ( $itm->snippet->channelId );
			
			// link time
			if (isset ( $itm->contentDetails ) && in_array ( 'OPT_YT_PLAYLIST', $camp_opt )) {
				$link_time = strtotime ( $itm->contentDetails->videoPublishedAt );
				
				echo ' Published:' . $itm->contentDetails->videoPublishedAt;
			} else {
				$link_time = strtotime ( $itm->snippet->publishedAt );
			}
			
			// Clear these values and generate at runtime to save costs of api requests
			$link_player = '';
			
			// needs a separate request with v3 api
			$link_views = '';
			$link_rating = '';
			$link_duration = '';
			
			// echo 'Published:'. date('Y-m-d',$itm['time']).' ';
			if ($this->is_execluded ( $camp->camp_id, $link_url )) {
				echo '<-- Execluded';
				continue;
			}
			
			// check if older than minimum date
			if ((in_array ( 'OPT_YT_DATE', $camp_opt ) && in_array ( 'OPT_YT_PLAYLIST', $camp_opt )) || in_array ( 'OPT_YT_DATE_T', $camp_opt )) {
				
				if ($this->is_link_old ( $camp->camp_id, $link_time )) {
					echo '<--old post execluding...';
					continue;
				}
			}
			
			// serializing general
			$general = base64_encode ( serialize ( $general ) );
			
			// $link_title =addslashes($link_title);
			
			if (! $this->is_duplicate ( $link_url )) {
				$query = "INSERT INTO {$this->wp_prefix}automatic_youtube_links ( link_url , link_title , link_keyword  , link_status , link_desc ,link_time,link_rating ,link_views,link_player,link_img,link_author,link_duration, link_general ) VALUES ( '$link_url', '$link_title', '{$camp->camp_id}_$keyword', '0' ,'$link_desc','$link_time','$link_rating','$link_views','$link_player','$link_img','$author','$link_duration','$general')";
				$this->db->query ( $query );
			} else {
				echo ' <- duplicated <a href="' . get_edit_post_link ( $this->duplicate_id ) . '">#' . $this->duplicate_id . '</a>';
			}
		}
		echo '</ol>';
	}
	
	/*
	 * ---* youtube post ---
	 */
	function youtube_get_post($camp) {
		$camp_opt = unserialize ( $camp->camp_options );
		$keywords = explode ( ',', $camp->camp_keywords );
		if (stristr ( $camp->camp_general, 'a:' ))
			$camp->camp_general = base64_encode ( $camp->camp_general );
		$camp_general = unserialize ( base64_decode ( $camp->camp_general ) );
		$camp_general = array_map ( 'wp_automatic_stripslashes', $camp_general );
		$camp_post_content = $camp->camp_post_content;
		$camp_post_custom_v = implode ( ',', unserialize ( $camp->camp_post_custom_v ) );
		$camp_post_title = $camp->camp_post_title;
		
		foreach ( $keywords as $keyword ) {
			
			$keyword = trim ( $keyword );
			
			if (trim ( $keyword ) != '') {
				
				echo '<br>Keyword:' . $keyword;
				
				// update last keyword
				update_post_meta ( $camp->camp_id, 'last_keyword', trim ( $keyword ) );
				
				// getting links from the db for that keyword
				$query = "select * from {$this->wp_prefix}automatic_youtube_links where link_keyword='{$camp->camp_id}_$keyword' ";
				$res = $this->db->get_results ( $query );
				
				// when no links lets get new links
				if (count ( $res ) == 0) {
					
					// clean any old cache for this keyword
					$query_delete = "delete from {$this->wp_prefix}automatic_youtube_links where link_keyword='{$camp->camp_id}_$keyword' ";
					$this->db->query ( $query_delete );
					
					$this->youtube_fetch_links ( $keyword, $camp );
					// getting links from the db for that keyword
					$res = $this->db->get_results ( $query );
				}
				
				// deleting duplicated items
				$res_count = count ( $res );
				for($i = 0; $i < $res_count; $i ++) {
					
					$t_row = $res [$i];
					$t_link_url = $t_row->link_url;
					$t_link_url_http = str_replace ( 'https', 'http', $t_link_url );
					
					if ($this->is_duplicate ( $t_link_url ) || $this->is_duplicate ( $t_link_url_http )) {
						
						// duplicated item let's delete
						unset ( $res [$i] );
						
						echo '<br>Vid (' . $t_row->link_title . ') found cached but duplicated <a href="' . get_permalink ( $this->duplicate_id ) . '">#' . $this->duplicate_id . '</a>';
						
						// delete the item
						$query = "delete from {$this->wp_prefix}automatic_youtube_links where link_id='{$t_row->link_id}'";
						$this->db->query ( $query );
					} else {
						break;
					}
				}
				
				// check again if valid links found for that keyword otherwise skip it
				if (count ( $res ) > 0) {
					
					// lets process that link
					$ret = $res [$i];
					
					echo '<br>Link:' . $ret->link_url;
					
					// extract video id
					$temp_ex = explode ( 'v=', $ret->link_url );
					$vid_id = $temp_ex [1];
					
					// set used url
					$this->used_link = trim ( $ret->link_url );
					
					$temp ['vid_title'] = trim ( $ret->link_title );
					$temp ['vid_url'] = trim ( $ret->link_url );
					$temp ['source_link'] = trim ( $ret->link_url );
					$temp ['vid_time'] = trim ( $ret->link_time );
					
					$temp ['vid_author'] = trim ( $ret->link_author );
					
					// generate player
					$width = $camp_general ['cg_yt_width'];
					$height = $camp_general ['cg_yt_height'];
					if (trim ( $width ) == '')
						$width = 580;
					if (trim ( $height ) == '')
						$height = 385;
					
					$embedsrc = "https://www.youtube.com/embed/" . $vid_id;
					
					if (in_array ( 'OPT_YT_SUGGESTED', $camp_opt ) && in_array ( 'OPT_YT_AUTO', $camp_opt )) {
						
						$embedsrc .= '?rel=0&autoplay=1';
					} elseif (in_array ( 'OPT_YT_SUGGESTED', $camp_opt )) {
						
						$embedsrc .= '?rel=0';
					}
					
					if (in_array ( 'OPT_YT_AUTO', $camp_opt )) {
						
						if (stristr ( $embedsrc, '?' )) {
							$embedsrc .= '&autoplay=1';
						} else {
							$embedsrc .= '?autoplay=1';
						}
					}
					
					if (in_array ( 'OPT_YT_CAPTION', $camp_opt )) {
						
						if (stristr ( $embedsrc, '?' )) {
							$embedsrc .= '&cc_load_policy=1';
						} else {
							$embedsrc .= '?cc_load_policy=1';
						}
					}
					
					// lang
					if (in_array ( 'OPT_YT_PLAYER_LANG', $camp_opt )) {
						
						$plang = trim ( $camp_general ['cg_yt_plang'] );
						
						if (stristr ( $embedsrc, '?' )) {
							$embedsrc .= '&hl=' . $plang;
						} else {
							$embedsrc .= '?hl=' . $plang;
						}
					}
					
					// yt logo
					if (in_array ( 'OPT_YT_LOGO', $camp_opt )) {
						
						if (stristr ( $embedsrc, '?' )) {
							
							$embedsrc .= '&modestbranding=1';
						} else {
							
							$embedsrc .= '?modestbranding=1';
						}
					}
					
					// title tag
					if (in_array ( 'OPT_YT_F_TITLE', $camp_opt )) {
						
						$title_part = 'title = "' . esc_attr ( $temp ['vid_title'] ) . '"  ';
					} else {
						$title_part = '';
					}
					
					$temp ['vid_player'] = '<iframe ' . $title_part . ' width="' . $width . '" height="' . $height . '" src="' . $embedsrc . '" frameborder="0" allowfullscreen></iframe>';
					
					// ini get video details flag if true will request yt again for new data
					$get_vid_details = false;
					$get_vid_details_parts = array ();
					
					// statistics part
					$temp ['vid_views'] = trim ( $ret->link_views );
					$temp ['vid_rating'] = trim ( $ret->link_rating );
					
					// general
					$general = unserialize ( base64_decode ( $ret->link_general ) );
					$temp ['vid_author_title'] = $general ['vid_author_title'];
					
					// merging post content with custom fields values to check what tags
					$camp_post_content_original = $camp_post_content;
					$camp_post_content = $camp_post_custom_v . $camp_post_content . ' ' . $camp_post_title;
					
					if (stristr ( $camp_post_content, 'vid_views' ) || stristr ( $camp_post_content, 'vid_rating' ) || stristr ( $camp_post_content, 'vid_likes' ) || stristr ( $camp_post_content, 'vid_dislikes' )) {
						
						$get_vid_details = true;
						$get_vid_details_parts [] = 'statistics';
					} elseif (defined ( 'PARENT_THEME' )) {
						if (PARENT_THEME == 'truemag' || PARENT_THEME == 'newstube') {
							
							$get_vid_details = true;
							$get_vid_details_parts [] = 'statistics';
						}
					}
					
					// contentdetails part
					$temp ['vid_duration'] = trim ( $ret->link_duration );
					
					if (stristr ( $camp_post_content, 'vid_duration' ) || class_exists ( 'Cactus_video' )) {
						$get_vid_details = true;
						$get_vid_details_parts [] = 'contentDetails';
					}
					
					// snippet part full content
					$temp ['vid_desc'] = trim ( $ret->link_desc );
					
					// if full description from youtube or tags let's get them
					if (in_array ( 'OPT_YT_FULL_CNT', $camp_opt ) || (in_array ( 'OPT_YT_PLAYLIST', $camp_opt )) || in_array ( 'OPT_YT_TAG', $camp_opt ) ) {
						$get_vid_details = true;
						$get_vid_details_parts [] = 'snippet';
					}
					
					// restore the content
					$camp_post_content = $camp_post_content_original;
					
					// now get the video details again if active
					if ($get_vid_details) {
						
						echo '<br>Getting more details from youtube for the vid..';
						
						// token
						$wp_automatic_yt_tocken = trim ( wp_automatic_single_item ( 'wp_automatic_yt_tocken' ) );
						
						// curl get
						$x = 'error';
						$ccurl = 'https://www.googleapis.com/youtube/v3/videos?key=' . $wp_automatic_yt_tocken . '&part=' . implode ( ',', $get_vid_details_parts ) . '&id=' . $vid_id;
						
						echo '<br>yt link:' . $ccurl;
						
						curl_setopt ( $this->ch, CURLOPT_HTTPGET, 1 );
						curl_setopt ( $this->ch, CURLOPT_URL, trim ( $ccurl ) );
						$exec = curl_exec ( $this->ch );
						$x = curl_error ( $this->ch );
						
						if (stristr ( $exec, 'kind' )) {
							
							$json_exec = json_decode ( $exec );
							$theItem = $json_exec->items [0];
							
							// check snippet
							if (isset ( $theItem->snippet )) {
								
								// playlist get correct author and author id
								if (in_array ( 'OPT_YT_PLAYLIST', $camp_opt )) {
									
									$temp ['vid_author_title'] = $theItem->snippet->channelTitle;
									
									// channel id
									$temp ['vid_author'] = addslashes ( $theItem->snippet->channelId );
								}
								
								// setting full content
								if (in_array ( 'OPT_YT_FULL_CNT', $camp_opt )) {
									$temp ['vid_desc'] = $theItem->snippet->description;
									echo '<br>Full description set ';
								}
								
								$temp ['vid_time'] = strtotime ( $theItem->snippet->publishedAt );
							}
							
							// check contentdetails details
							if (isset ( $theItem->contentDetails )) {
								
								$youtube_time = $theItem->contentDetails->duration;
								
								$DTClass = new DateTime ( '@0' ); // Unix epoch
								$DTClass->add ( new DateInterval ( $youtube_time ) );
								$temp ['vid_duration'] = $DTClass->format ( 'H:i:s' );
							}
							
							// check statistics details
							if (isset ( $theItem->statistics )) {
								$temp ['vid_views'] = $theItem->statistics->viewCount;
								
								$likeCount = $theItem->statistics->likeCount;
								$dislikeCount = $theItem->statistics->dislikeCount;
								
								@$rating = $likeCount / ($likeCount + $dislikeCount);
								$rating = $rating * 5;
								$rating = number_format ( $rating, 2 );
								
								$temp ['vid_rating'] = $rating;
								$temp ['vid_likes'] = $theItem->statistics->likeCount;
								$temp ['vid_dislikes'] = $theItem->statistics->dislikeCount;
							}
							
							//vid_tags
							if(isset($theItem->snippet->tags) && count($theItem->snippet->tags) > 0 ){
								echo '<br>'.count($theItem->snippet->tags) . ' tags found';
								$temp ['vid_tags'] = implode(',' , $theItem->snippet->tags);
								$this->used_tags = $temp ['vid_tags'];
								
							}
						} else {
							echo '<br>no valid reply from youtube ';
						}
					}
					
					$temp ['vid_img'] = trim ( $ret->link_img );
					
					$temp ['vid_id'] = trim ( $vid_id );
					$this->used_keyword = $ret->link_keyword;
					
					// if vid_image contains markup extract the source only
					if (stristr ( $temp ['vid_img'], '<img' )) {
						preg_match_all ( '/src\="(.*?)"/', $temp ['vid_img'], $matches );
						$temp ['vid_img'] = $matches [1] [0];
					}
					
					 
					
					// update the link status to 1
					$query = "delete from {$this->wp_prefix}automatic_youtube_links where link_id={$ret->link_id}";
					$this->db->query ( $query );
					
					// if cache not active let's delete the cached videos and reset indexes
					if (! in_array ( 'OPT_YT_CACHE', $camp_opt )) {
						echo '<br>Cache disabled claring cache ...';
						$query = "delete from {$this->wp_prefix}automatic_youtube_links where link_keyword='{$camp->camp_id}_$keyword' ";
						// $query = "update {$this->wp_prefix}automatic_youtube_links set link_status ='1' where link_keyword='{$camp->camp_id}_$keyword' and link_status ='0'";
						
						$this->db->query ( $query );
						
						// reset index
						$query = "update {$this->wp_prefix}automatic_keywords set keyword_start =1 where keyword_camp={$camp->camp_id}";
						$this->db->query ( $query );
					}
					
					// Vid_date publish date
					$temp ['vid_date'] = get_date_from_gmt ( gmdate ( 'Y-m-d H:i:s', $temp ['vid_time'] ) );
					
					// OPT_YT_HYPER
					if (in_array ( 'OPT_YT_HYPER', $camp_opt )) {
						
						$temp ['vid_desc'] = $this->hyperlink_this ( $temp ['vid_desc'] );
					}
					
					// download link
					$temp ['vid_download_url'] = 'https://www.youtubepp.com/watch?v=' . $temp ['vid_id'];
					
					return $temp;
				} else {
					
					echo '<br>No links found for this keyword';
				}
			} // if trim
		} // foreach keyword
	}
}