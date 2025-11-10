<?php

// Main Class
require_once 'core.php';
class WpAutomaticInstagram extends wp_automatic {
	function instagram_get_post($camp) {
		
		// sess required
		$wp_automatic_ig_sess = trim ( get_option ( 'wp_automatic_ig_sess', '' ) );
		
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
				
				echo '<br>Let\'s post an Instagram image for the keyword:' . $keyword;
				
				if ($wp_automatic_ig_sess == '') {
					echo '<br><span style="color:red">Please visit the plugin settings page and add the required Instagram session cookie.</span>';
					return false;
				}
				
				// getting links from the db for that keyword
				$query = "select * from {$this->wp_prefix}automatic_general where item_type=  'it_{$camp->camp_id}_$keyword' ";
				$res = $this->db->get_results ( $query );
				
				// when no links lets get new links
				if (count ( $res ) == 0) {
					
					// clean any old cache for this keyword
					$query_delete = "delete from {$this->wp_prefix}automatic_general where item_type='it_{$camp->camp_id}_$keyword' ";
					$this->db->query ( $query_delete );
					
					// get new links
					$this->instagram_fetch_items ( $keyword, $camp );
					
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
						
						echo '<br>Instagram pic (' . $t_data ['item_title'] . ') found cached but duplicated <a href="' . get_permalink ( $this->duplicate_id ) . '">#' . $this->duplicate_id . '</a>';
						
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
					
					// item images ini
					$temp ['item_images'] = '<img src="' . $temp ['item_img'] . '" />';
					
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
							if (in_array ( 'OPT_IT_NO_TTL_TAG', $camp_opt )) {
								$contentClean = preg_replace ( '{#\S*}', '', $contentClean );
							}
							
							// remove mentions
							if (in_array ( 'OPT_IT_NO_TTL_MEN', $camp_opt )) {
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
					
					// Get item details if needed at three cases
					// cases are 1-video 2-comments needed 3- tag item_user_username or item_user_name in content
					
					$isDetailedItemInfoRequired = true;
					
					if (isset ( $temp ['is_video'] ) && $temp ['is_video'] == 'yes') {
						
						// case 1 video
						echo '<br>Video found need to get detailed item info from instagram';
						$isDetailedItemInfoRequired = true;
					} elseif ($this->do_tag_exists ( $camp, array (
							'item_user_profile_pic',
							'item_user_username',
							'item_user_name',
							'item_user_profile_pic',
							'item_location_name',
							'item_location_id',
							'item_location_url' 
					) )) {
						
						// case 3 tags used
						echo '<br>Special tags are used, need to get detailed item info from instagram';
						$isDetailedItemInfoRequired = true;
					} elseif (in_array ( 'OPT_IT_COMMENT', $camp_opt )) {
						
						// case 2 comments
						echo '<br>Comments needed, need to get detailed item info from instagram';
						$isDetailedItemInfoRequired = true;
					} elseif ($temp ['item_type'] == 'GraphSidecar' && in_array ( 'OPT_IT_SLIDER', $camp_opt )) {
						echo '<br>More than one image exists, getting more details from Instagram';
						$isDetailedItemInfoRequired = true;
					}
					
					// get more details if needed
					if ($isDetailedItemInfoRequired) {
						
						echo '<br>Now loading IG original Page URL...';
						
						require_once 'inc/class.instagram.php';
						
						$instaScrape = new InstaScrape ( $this->ch, $wp_automatic_ig_sess, true );
						
						try {
							$fullItemDetails = $instaScrape->getItemByID ( $t_data ['item_id'] );
						} catch ( Exception $e ) {
							
							echo 'Failed:' . $e->getMessage ();
							return;
						}
						
						// fresh item img
						$display_resources = $fullItemDetails->graphql->shortcode_media->display_resources;
						
						foreach ( $display_resources as $display_resource ) {
							$temp ['item_img'] = $display_resource->src;
						}
						
						// item images ini
						$img_template = stripslashes ( $camp_general ['cg_it_full_img_t'] );
						if (trim ( $img_template ) == '')
							$img_template = '<img src="[img_src]" />';
						
							$temp ['item_images'] = str_replace('[img_src]', $temp ['item_img'], $img_template); 
						
						// slider images
						if (in_array ( 'OPT_IT_SLIDER', $camp_opt )) {
							
							if (isset ( $fullItemDetails->graphql->shortcode_media->edge_sidecar_to_children->edges )) {
								
								$all_childs_html = '';
								$img_template = stripslashes ( $camp_general ['cg_it_full_img_t'] );
								if (trim ( $img_template ) == '')
									$img_template = '<img src="[img_src]" />';
								$all_childs = $fullItemDetails->graphql->shortcode_media->edge_sidecar_to_children->edges;
								
								foreach ( $all_childs as $child ) {
									
									$all_childs_html .= str_replace ( '[img_src]', $child->node->display_url, $img_template );
									
									if (! stristr ( $img_template, '<img ' )) {
										$all_childs_html .= '<img class="delete" src="' . $child->node->display_url . '" />';
									}
								}
								
								if (trim ( $all_childs_html ) != '')
									$temp ['item_images'] = $all_childs_html;
							}
						}
						
						// if( isset($fullItemDetails->media->code) && trim($fullItemDetails->media->code) == $t_data['item_id'] ){
						
						if (isset ( $fullItemDetails->graphql->shortcode_media->shortcode ) && trim ( $fullItemDetails->graphql->shortcode_media->shortcode ) == $t_data ['item_id']) {
							echo '<-- Valid item details returned from Instagram';
							
							// case 3
							$temp ['item_user_username'] = $fullItemDetails->graphql->shortcode_media->owner->username;
							$temp ['item_user_profile_pic'] = $fullItemDetails->graphql->shortcode_media->owner->profile_pic_url;
							
							if (trim ( $fullItemDetails->graphql->shortcode_media->owner->full_name ) != '') {
								$temp ['item_user_name'] = $fullItemDetails->graphql->shortcode_media->owner->full_name;
							} else {
								$temp ['item_user_name'] = $fullItemDetails->graphql->shortcode_media->owner->username;
							}
							
							// case 1 video
							$temp ['item_vid_embed'] =''; // ini video embed code
							if (isset ( $temp ['is_video'] ) && $temp ['is_video'] == 'yes') {
								
								$autoPlay = '';
								if (in_array ( 'OPT_IT_VID_AUTO', $camp_opt )) {
									$autoPlay = ' autoplay="on" ';
								}
								
								if (in_array ( 'OPT_IT_VID_LOOP', $camp_opt )) {
									$autoPlay .= ' loop="on" ';
								}
								
								$vidUrl = $fullItemDetails->graphql->shortcode_media->video_url;
								
								// $videoEmbed = ' [video src="' . $vidUrl .'" '.$autoPlay.'] ' ;
								$videoEmbed = ' <blockquote class="instagram-media" data-instgrm-permalink="' . $temp ['item_url'] . '" data-instgrm-version="8" style=" background:#FFF; border:0; border-radius:3px; box-shadow:0 0 1px 0 rgba(0,0,0,0.5),0 1px 10px 0 rgba(0,0,0,0.15); margin: 1px; max-width:658px; padding:0; width:99.375%; width:-webkit-calc(100% - 2px); width:calc(100% - 2px);">&nbsp;</blockquote> <script async defer src="//www.instagram.com/embed.js"></script> ';
								
								if (in_array ( 'OPT_IT_NO_VID_EMBED', $camp_opt )) {
								} elseif (in_array ( 'OPT_IT_VID_TOP', $camp_opt )) {
									$temp ['item_description'] = $videoEmbed . $temp ['item_description'];
								} else {
									$temp ['item_description'] .= $videoEmbed;
								}
								
								$temp ['item_vid_embed'] = $videoEmbed;
							}
							
							// case 2 comments
							if (in_array ( 'OPT_IT_COMMENT', $camp_opt )) {
								
								// $commentsArray = $fullItemDetails->graphql->shortcode_media->comments->nodes;
								$commentsArray = $fullItemDetails->graphql->shortcode_media->edge_media_to_parent_comment->edges;
								
								$temp ['item_comments'] = $commentsArray;
							}
							
							// location
							if (isset ( $fullItemDetails->graphql->shortcode_media->location->name )) {
								$temp ['item_location_name'] = $fullItemDetails->graphql->shortcode_media->location->name;
								$temp ['item_location_id'] = $fullItemDetails->graphql->shortcode_media->location->id;
								$temp ['item_location_url'] = 'https://www.instagram.com/explore/locations/' . $temp ['item_location_id'] . '/' . $fullItemDetails->graphql->shortcode_media->location->id . '/';
							} else {
								$temp ['item_location_name'] = '';
								$temp ['item_location_id'] = '';
								$temp ['item_location_url'] = '';
							}
						} else {
							echo '<-- returned data is not valid';
							print_r ( $fullItemDetails );
							
							$temp ['item_location_name'] = '';
							$temp ['item_location_id'] = '';
							$temp ['item_location_url'] = '';
						}
					}
					
					// Embed videos
					
					// if cache not active let's delete the cached videos and reset indexes
					if (! in_array ( 'OPT_IT_CACHE', $camp_opt )) {
						echo '<br>Cache disabled claring cache ...';
						$query = "delete from {$this->wp_prefix}automatic_general where item_type='it_{$camp->camp_id}_$keyword' ";
						$this->db->query ( $query );
						
						// reset index
						$query = "update {$this->wp_prefix}automatic_keywords set keyword_start =1 where keyword_camp={$camp->camp_id}";
						$this->db->query ( $query );
						
						delete_post_meta ( $camp->camp_id, 'wp_instagram_next_max_id' . md5 ( $keyword ) );
					}
					
					// Item embed
					if (stristr ( $temp ['item_description'], '[embed' )) {
						
						$temp ['item_embed'] = '<blockquote class="instagram-media" data-instgrm-captioned data-instgrm-version="6" style=" background:#FFF; border:0; border-radius:3px; box-shadow:0 0 1px 0 rgba(0,0,0,0.5),0 1px 10px 0 rgba(0,0,0,0.15); margin: 1px; max-width:658px; padding:0; width:99.375%; width:-webkit-calc(100% - 2px); width:calc(100% - 2px);"><div style="padding:8px;"> <div style=" background:#F8F8F8; line-height:0; margin-top:40px; padding:50.0% 0; text-align:center; width:100%;"> <div style=" background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACwAAAAsCAMAAAApWqozAAAAGFBMVEUiIiI9PT0eHh4gIB4hIBkcHBwcHBwcHBydr+JQAAAACHRSTlMABA4YHyQsM5jtaMwAAADfSURBVDjL7ZVBEgMhCAQBAf//42xcNbpAqakcM0ftUmFAAIBE81IqBJdS3lS6zs3bIpB9WED3YYXFPmHRfT8sgyrCP1x8uEUxLMzNWElFOYCV6mHWWwMzdPEKHlhLw7NWJqkHc4uIZphavDzA2JPzUDsBZziNae2S6owH8xPmX8G7zzgKEOPUoYHvGz1TBCxMkd3kwNVbU0gKHkx+iZILf77IofhrY1nYFnB/lQPb79drWOyJVa/DAvg9B/rLB4cC+Nqgdz/TvBbBnr6GBReqn/nRmDgaQEej7WhonozjF+Y2I/fZou/qAAAAAElFTkSuQmCC); display:block; height:44px; margin:0 auto -44px; position:relative; top:-22px; width:44px;"></div></div> <p style=" margin:8px 0 0 0; padding:0 4px;"> <a href="' . $temp ['item_url'] . '" style=" color:#000; font-family:Arial,sans-serif; font-size:14px; font-style:normal; font-weight:normal; line-height:17px; text-decoration:none; word-wrap:break-word;" target="_blank">' . strip_shortcodes ( $temp ['item_description'] ) . '</a></p> <p style=" color:#c9c8cd; font-family:Arial,sans-serif; font-size:14px; line-height:17px; margin-bottom:0; margin-top:8px; overflow:hidden; padding:8px 0 7px; text-align:center; text-overflow:ellipsis; white-space:nowrap;">A video posted by ' . $temp ['item_user_name'] . ' (@' . $temp ['item_user_username'] . ') on <time style=" font-family:Arial,sans-serif; font-size:14px; line-height:17px;" datetime="' . $temp ['item_created_date'] . '">' . $temp ['item_created_date'] . '</time></p></div></blockquote><script async defer src="//platform.instagram.com/en_US/embeds.js"></script>';
					} else {
						$temp ['item_embed'] = '<blockquote class="instagram-media" data-instgrm-captioned data-instgrm-version="6" style=" background:#FFF; border:0; border-radius:3px; box-shadow:0 0 1px 0 rgba(0,0,0,0.5),0 1px 10px 0 rgba(0,0,0,0.15); margin: 1px; max-width:658px; padding:0; width:99.375%; width:-webkit-calc(100% - 2px); width:calc(100% - 2px);"><div style="padding:8px;"> <div style=" background:#F8F8F8; line-height:0; margin-top:40px; padding:50.0% 0; text-align:center; width:100%;"> <div style=" background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACwAAAAsCAMAAAApWqozAAAAGFBMVEUiIiI9PT0eHh4gIB4hIBkcHBwcHBwcHBydr+JQAAAACHRSTlMABA4YHyQsM5jtaMwAAADfSURBVDjL7ZVBEgMhCAQBAf//42xcNbpAqakcM0ftUmFAAIBE81IqBJdS3lS6zs3bIpB9WED3YYXFPmHRfT8sgyrCP1x8uEUxLMzNWElFOYCV6mHWWwMzdPEKHlhLw7NWJqkHc4uIZphavDzA2JPzUDsBZziNae2S6owH8xPmX8G7zzgKEOPUoYHvGz1TBCxMkd3kwNVbU0gKHkx+iZILf77IofhrY1nYFnB/lQPb79drWOyJVa/DAvg9B/rLB4cC+Nqgdz/TvBbBnr6GBReqn/nRmDgaQEej7WhonozjF+Y2I/fZou/qAAAAAElFTkSuQmCC); display:block; height:44px; margin:0 auto -44px; position:relative; top:-22px; width:44px;"></div></div> <p style=" margin:8px 0 0 0; padding:0 4px;"> <a href="' . $temp ['item_url'] . '" style=" color:#000; font-family:Arial,sans-serif; font-size:14px; font-style:normal; font-weight:normal; line-height:17px; text-decoration:none; word-wrap:break-word;" target="_blank">' . $temp ['item_description'] . '</a></p> <p style=" color:#c9c8cd; font-family:Arial,sans-serif; font-size:14px; line-height:17px; margin-bottom:0; margin-top:8px; overflow:hidden; padding:8px 0 7px; text-align:center; text-overflow:ellipsis; white-space:nowrap;">A photo posted by ' . $temp ['item_user_name'] . ' (@' . $temp ['item_user_username'] . ') on <time style=" font-family:Arial,sans-serif; font-size:14px; line-height:17px;" datetime="' . $temp ['item_created_date'] . '">' . $temp ['item_created_date'] . '</time></p></div></blockquote><script async defer src="//platform.instagram.com/en_US/embeds.js"></script>';
					}
					
					// Item tags
					if (in_array ( 'OPT_IT_TAGS', $camp_opt )) {
						
						echo '<br>Extracting tags';
						
						$itemDescription = $temp ['item_description'];
						$itemDescription = str_replace ( '#', ' #', $itemDescription );
						$itemDescription = preg_replace ( '{<blockquote.*?blockquote>}', '', $itemDescription );
						$itemDescription = $itemDescription;
						$itemDescription = $itemDescription . ' ';
						
						preg_match_all ( '{#[^#]*?\s}', $itemDescription, $tagMatchs );
						
						$temp ['item_tags'] = implode ( ',', $tagMatchs [0] );
						$temp ['item_tags'] = str_replace ( '#', '', $temp ['item_tags'] );
					}
					
					// remove hashtags
					if (in_array ( 'OPT_IT_NO_CNT_TAG', $camp_opt )) {
						$temp ['item_description'] = preg_replace ( '{#\S*}', '', $temp ['item_description'] );
					}
					
					// fix date
					$temp ['item_created_date'] = get_date_from_gmt ( $temp ['item_created_date'] );
					
					// hide video image
					if (! in_array ( 'OPT_IT_NO_VID_IMG_HIDE', $camp_opt ) && isset ( $temp ['is_video'] ) && $temp ['is_video'] == 'yes') {
						$temp ['item_images'] = str_replace ( '<img', '<img style="display:none" ', $temp ['item_images'] );
					}
					
					return $temp;
				} else {
					echo '<br>No links found for this keyword';
				}
			} // if trim
		} // foreach keyword
	}
	function instagram_fetch_items($keyword, $camp) {
		
		// report
		echo "<br>So I should now get some pics from Instagram for keyword :" . $keyword;
		$wp_automatic_ig_sess = trim ( get_option ( 'wp_automatic_ig_sess', '' ) );
		
		// Instascrpe
		require_once 'inc/class.instagram.php';
		$instaScrape = new InstaScrape ( $this->ch, $wp_automatic_ig_sess, true );
		
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
		if ($start == 1 || (in_array ( 'OPT_IT_POPULAR', $camp_opt ) && ! in_array ( 'OPT_IT_USER', $camp_opt ))) {
			
			// use first base query
			$wp_instagram_next_max_id = 0;
			echo ' Posting from the first page...';
		} else {
			
			// not first page get the bookmark
			$wp_instagram_next_max_id = get_post_meta ( $camp->camp_id, 'wp_instagram_next_max_id' . md5 ( $keyword ), 1 );
			
			if (trim ( $wp_instagram_next_max_id ) == '') {
				echo '<br>No new page max id';
				$wp_instagram_next_max_id = 0;
			} else {
				if (in_array ( 'OPT_IT_CACHE', $camp_opt )) {
					echo '<br>max_id:' . $wp_instagram_next_max_id;
				} else {
					$start = 1;
					echo '<br>Cache disabled resetting index to 1';
					$wp_instagram_next_max_id = 0;
				}
			}
		}
		
		// if specific user posting
		if (in_array ( 'OPT_IT_USER', $camp_opt )) {
			
			$cg_it_user = trim ( $camp_general ['cg_it_user'] );
			echo '<br>Specific user:' . $cg_it_user;
			
			// check if is a numeric id or we will need to grap the id
			$cg_it_user_numeric = get_post_meta ( $camp->camp_id, 'wp_instagram_user_' . trim ( $cg_it_user ), 1 );
			
			if (trim ( $cg_it_user_numeric ) == '') {
				
				echo '<br>Getting numeric user ID from Instagram..';
				
				try {
					
					$cg_it_user_numeric = $instaScrape->getUserIDFromName ( $cg_it_user );
					
					if (is_numeric ( $cg_it_user_numeric )) {
						
						echo '<br>Found the id:' . $cg_it_user_numeric;
						update_post_meta ( $camp->camp_id, 'wp_instagram_user_' . trim ( $cg_it_user ), $cg_it_user_numeric );
					}
				} catch ( Exception $e ) {
					
					echo 'Failed:' . $e->getMessage ();
					return;
				}
			} else {
				echo ' id:' . $cg_it_user_numeric;
			} // no vlaid nueric gnerate
			  
			// build url;
			if (is_numeric ( $cg_it_user_numeric )) {
				
				// get items
				
				try {
					
					$jsonArr = $instaScrape->getUserItems ( $cg_it_user_numeric, 10, $wp_instagram_next_max_id );
				} catch ( Exception $e ) {
					
					echo '<br>Exception:' . $e->getMessage ();
					
					// execution failure
					if (stristr ( $e->getMessage (), 'execution failure' )) {
						echo '<br>Execution failure, deleting next page token';
						delete_post_meta ( $camp->camp_id, 'wp_instagram_next_max_id' . md5 ( $keyword ) );
					}
					
		
					
					return;
				}
			} else {
				echo '<br>Can not find valid numeric id for the user .. exiting';
				return;
			}
		} else {
			
			// prepare keyword
			$qkeyword = str_replace ( ' ', '', $keyword );
			$qkeyword = str_replace ( '#', '', $qkeyword );
			
			try {
				
				$jsonArr = $instaScrape->getItemsByHashtag ( $qkeyword, 50, $wp_instagram_next_max_id );
			} catch ( Exception $e ) {
				
				// execution failure
				if (stristr ( $e->getMessage (), 'execution failure' )) {
					echo '<br>Execution failure, deleting next page token';
					delete_post_meta ( $camp->camp_id, 'wp_instagram_next_max_id' . md5 ( $keyword ) );
				}
				
				if (stristr ( $e->getMessage (), 'No hashtag exist' )) {
					
					$query = "update {$this->wp_prefix}automatic_keywords set keyword_start = -1 where keyword_id=$kid ";
					$this->db->query ( $query );
					
					$this->deactivate_key ( $camp->camp_id, $keyword  , 0);
				}
				
				echo '<br>Exception:' . $e->getMessage ();
				return;
			}
		}
		
		// echo '<pre>Prining arr';
		 //print_r($jsonArr);
		//exit;
		
		//recent sometimes get disabled in USA
		if( ! in_array ( 'OPT_IT_USER', $camp_opt ) && isset($jsonArr->graphql) && $jsonArr->graphql->hashtag->edge_hashtag_to_media->count == 0 && count($jsonArr->graphql->hashtag->edge_hashtag_to_top_posts->edges) >0){
			
			echo '<br>Did not find any recent images, getting popular instead. Hashtag recent images from IG are now disabled on USA due to elections, <a href="https://help.instagram.com/861508690592298">check more</a>';
			$camp_opt[] = 'OPT_IT_POPULAR';
			
		}
		 
		// validating reply
		if (isset ( $jsonArr->status ) || isset($jsonArr->graphql) || isset($jsonArr->data->name) ) {
			
		
			if (in_array ( 'OPT_IT_USER', $camp_opt )) {
				$items = $jsonArr->data->user->edge_owner_to_timeline_media->edges;
				$page_info = $jsonArr->data->user->edge_owner_to_timeline_media->page_info;
			} elseif (in_array ( 'OPT_IT_POPULAR', $camp_opt )) {
				
				$items = $jsonArr->graphql->hashtag->edge_hashtag_to_top_posts->edges;
				$page_info = $jsonArr->graphql->hashtag->edge_hashtag_to_top_posts->page_info;
			} else {
				
			
				if(isset($jsonArr->graphql)){
					$items = $jsonArr->graphql->hashtag->edge_hashtag_to_media->edges;
					$page_info = $jsonArr->graphql->hashtag->edge_hashtag_to_media->page_info;
				}else{
					
					$items = array();
					
					foreach( $jsonArr->data->recent->sections as $single_section){
						$items = array_merge($items , $single_section->layout_content->medias );
					}
					 
				}
			
			}
			
			// reverse
			if (in_array ( 'OPT_IT_REVERSE', $camp_opt )) {
				echo '<br>Reversing order';
				$items = array_reverse ( $items );
			}
			
			echo '<ol>';
			
			// loop pins
			$i = 0;
			foreach ( $items as $item ) {
				
				// clean itm
				unset ( $itm );
				
				
				//first type graphql
				if(isset($item->node)){
					$item = $item->node;
					
					
					// report
					echo '<li>http://instagram.com/p/' . $item->shortcode;
					
					// build item
					$itm ['item_id'] = $item->shortcode;
					$itm ['item_url'] = 'http://instagram.com/p/' . $item->shortcode;
					$itm ['item_description'] = $item->edge_media_to_caption->edges [0]->node->text;
					$itm ['video_view_count'] = 0;
					
					// if video embed it
					if (trim ( $item->is_video != '' ) && $item->is_video == 1) {
						
						// don't post videos filter
						if (in_array ( 'OPT_IT_NO_VID', $camp_opt )) {
							echo '<-- Video skipping it.';
							$i ++;
							continue;
						}
						
						$itm ['is_video'] = 'yes';
						$itm ['video_view_count'] = $item->video_view_count;
					} else {
						// img
						
						// don't post videos filter
						if (in_array ( 'OPT_IT_NO_IMG', $camp_opt ) && ! in_array ( 'OPT_IT_NO_VID', $camp_opt )) {
							echo '<-- Image skipping it.';
							$i ++;
							continue;
						}
					}
					
					$itm ['item_img'] = $item->display_url;
					$itm ['item_img_width'] = $item->dimensions->width;
					$itm ['item_img_height'] = $item->dimensions->height;
					$itm ['item_user_id'] = $item->owner->id;
					$itm ['item_created_time'] = $item->taken_at_timestamp;
					
					// item date
					$itm ['item_created_date'] = date ( 'Y-m-d H:i:s', $item->taken_at_timestamp );
					$itm ['item_likes_count'] = $item->edge_liked_by->count;
					
					// not availabe with tag calls
					$itm ['item_user_username'] = @$item->owner->username;
					
					// full name
					$itm ['item_user_name'] = '';
					if (isset ( $item->owner->full_name ) && trim ( $item->owner->full_name ) != '') {
						$itm ['item_user_name'] = $item->owner->full_name;
					} else {
						$itm ['item_user_name'] = isset ( $item->owner->username ) ? $item->owner->username : '';
					}
					
					$itm ['item_user_profile_pic'] = isset ( $item->owner->profile_pic_url ) ? $item->owner->profile_pic_url : '';
					
					// comments postponed
					$commentsArray = array ();
					$itm ['item_comments'] = $commentsArray;
					
					// item type
					$itm ['item_type'] = isset ( $item->__typename ) ? $item->__typename : '';
					
					
					
				}else{
					 
					//the old way no graph
					$item = $item->media;

					echo '<li>http://instagram.com/p/' . $item->code;
					
					// build item
					$itm ['item_id'] = $item->code;
					$itm ['item_url'] = 'http://instagram.com/p/' . $item->code;
					$itm ['item_description'] = $item->caption->text;
					$itm ['video_view_count'] = 0;
					
					// if video embed it
					if (trim ( $item->is_video != '' ) && $item->is_video == 1) {
						
						// don't post videos filter
						if (in_array ( 'OPT_IT_NO_VID', $camp_opt )) {
							echo '<-- Video skipping it.';
							$i ++;
							continue;
						}
						
						$itm ['is_video'] = 'yes';
						$itm ['video_view_count'] = $item->video_view_count;
					} else {
						// img
						
						// don't post videos filter
						if (in_array ( 'OPT_IT_NO_IMG', $camp_opt ) && ! in_array ( 'OPT_IT_NO_VID', $camp_opt )) {
							echo '<-- Image skipping it.';
							$i ++;
							continue;
						}
					}
					
					$itm ['item_img'] = $item->image_versions2->candidates[0]->url;
					$itm ['item_img_width'] = $item->image_versions2->candidates[0]->width;
					$itm ['item_img_height'] = $item->image_versions2->candidates[0]->height;
					$itm ['item_user_id'] = $item->user->pk;
					$itm ['item_created_time'] = $item->taken_at;
					
					// item date
					$itm ['item_created_date'] = date ( 'Y-m-d H:i:s', $item->taken_at );
					$itm ['item_likes_count'] = $item->like_count;
					$itm ['item_user_username'] = $item->user->username;
					
					// full name
					$itm ['item_user_name'] = $item->user->full_name; 
					$itm ['item_user_profile_pic'] = $item->user->profile_pic_url;
					
					// comments postponed
					$commentsArray = array ();
					$itm ['item_comments'] = $commentsArray;
					
					// item type
					$itm ['item_type'] = isset ( $item->__typename ) ? $item->__typename : '';

					echo '<pre>';
					print_r($itm);
				exit;
					
				}
				
				
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
					$query = "INSERT INTO {$this->wp_prefix}automatic_general ( item_id , item_status , item_data ,item_type) values (    '{$itm['item_id']}', '0', '$data' ,'it_{$camp->camp_id}_$keyword')  ";
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
				echo '<br>No new pics found ';
				echo '<br>Keyword have no more images deactivating...';
				$query = "update {$this->wp_prefix}automatic_keywords set keyword_start = -1 where keyword_id=$kid ";
				$this->db->query ( $query );
				
				if (! in_array ( 'OPT_NO_DEACTIVATE', $camp_opt ))
					$this->deactivate_key ( $camp->camp_id, $keyword );
				
				// delete bookmark value
				delete_post_meta ( $camp->camp_id, 'wp_instagram_next_max_id' . md5 ( $keyword ) );
			} elseif ($was_there_old_items && ! in_array ( 'OPT_IT_POPULAR', $camp_opt )) {
				
				echo '<br>Found old posts among returned list, setting pointer to the first page again ';
				$query = "update {$this->wp_prefix}automatic_keywords set keyword_start = 0 where keyword_id=$kid ";
				$this->db->query ( $query );
				
				// delete bookmark value
				delete_post_meta ( $camp->camp_id, 'wp_instagram_next_max_id' . md5 ( $keyword ) );
			} else {
				
				// get max id
				if (isset ( $page_info->has_next_page ) && $page_info->has_next_page == 1) {
					
					echo '<br>Updating max_id:' . $page_info->end_cursor;
					update_post_meta ( $camp->camp_id, 'wp_instagram_next_max_id' . md5 ( $keyword ), $page_info->end_cursor );
				} else {
					echo '<br>No pagination found deleting next page index';
					delete_post_meta ( $camp->camp_id, 'wp_instagram_next_max_id' . md5 ( $keyword ) );
					
					// disable queries for an hour if cache disabled
					if (in_array ( 'OPT_IT_CACHE', $camp_opt )) {
						
						$query = "update {$this->wp_prefix}automatic_keywords set keyword_start = -1 where keyword_id=$kid ";
						$this->db->query ( $query );
						
						if (! in_array ( 'OPT_NO_DEACTIVATE', $camp_opt ))
							$this->deactivate_key ( $camp->camp_id, $keyword );
						
						// delete bookmark value
						delete_post_meta ( $camp->camp_id, 'wp_instagram_next_max_id' . md5 ( $keyword ) );
					}
				}
			}
		} else {
			
			// no valid reply
			echo '<br>No Valid reply for instagram search <br>' . $exec;
		}
	}
}