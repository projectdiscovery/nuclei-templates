<?php
// Main Class
require_once 'core.php';
class WpAutomaticFacebook extends wp_automatic {
	
	/**
	 * function : fb_get_post
	 */
	function fb_get_post($camp) {
		
		// Authorisation info
		$wp_automatic_fb_cuser = trim ( get_option ( 'wp_automatic_fb_cuser', '' ) );
		$wp_automatic_fb_xs = trim ( get_option ( 'wp_automatic_fb_xs' ) );
		
		if (trim ( $wp_automatic_fb_cuser ) == '' || trim ( $wp_automatic_fb_xs ) == '') {
			// echo '<br><span style="color:red">Please visit the plugin settings page and add the required Facebook cookies values</span>';
			// return false;
		}
		
		// get page id
		$camp_general = unserialize ( base64_decode ( $camp->camp_general ) );
		$camp_opt = unserialize ( $camp->camp_options );
		
		echo '<br>Processing FB page:' . $camp_general ['cg_fb_page'];
		
		// PAGE ID
		$cg_fb_page_id = get_post_meta ( $camp->camp_id, 'cg_fb_page_id', 1 );
		
		// if a numeric id use it direclty
		$url = $camp_general ['cg_fb_page'];
		
		if (is_numeric ( $url )) {
			echo '<br>Numeric id added manually using it as the page id.';
			$cg_fb_page_id = trim ( $url );
		}
		
		// get page id if not still extracted
		if (trim ( $cg_fb_page_id ) == '') {
			echo '<br>Extracting page ID from original page link';
			
			// getting page name from url
			
			// curl get
			$x = 'error';
			$url = $camp_general ['cg_fb_page'];
			curl_setopt ( $this->ch, CURLOPT_HTTPGET, 1 );
			curl_setopt ( $this->ch, CURLOPT_URL, trim ( $url ) );
			
			// authorization
			curl_setopt ( $this->ch, CURLOPT_COOKIE, 'xs=' . $wp_automatic_fb_xs . ';c_user=' . $wp_automatic_fb_cuser );
			
			$exec = curl_exec ( $this->ch );
			$x = curl_error ( $this->ch );
			
			// entity_id if the fb page validation check
			if (stristr ( $exec, 'entity_id' ) || stristr ( $exec, '"pageID":' ) || stristr ( $exec, '"userID":"' ) || stristr ( $exec, '"groupID":"' )) {
				
				if ( preg_match( '{"pageID":"(\d*?)"}', $exec   ) ) {

					echo '<br>pageID found getting id from it';
					preg_match_all ( '{"pageID":"(\d*?)"}', $exec, $matches );
				 
					
				} elseif (stristr ( $exec, '"groupID"' )) {
					echo '<br>groupID found getting id from it';
					preg_match_all ( '{"groupID":"(\d*?)"}', $exec, $matches );
				} elseif (stristr ( $exec, '"userID"' )) {
					echo '<br>userID found getting id from it';
					preg_match_all ( '{"userID":"(\d*?)"}', $exec, $matches );
				} else {
					echo '<br>entity_id found getting id from it';
					preg_match_all ( '{entity_id":"(\d*?)"}', $exec, $matches );
				}
				
				$smatch = $matches [1];
				$cg_fb_page_id = $smatch [0];
				
				if (trim ( $cg_fb_page_id ) != '') {
					echo '<br>Successfully extracted entityID:' . $cg_fb_page_id;
					update_post_meta ( $camp->camp_id, 'cg_fb_page_id', $cg_fb_page_id );
				} else {
					echo '<br>Can not find numeric entityID';
				}
			} else {
				
				if (stristr ( $exec, 'PageComposerPagelet_' )) {
					
					// extracting
					preg_match_all ( '{PageComposerPagelet_(\d*?)"}', $exec, $matches );
					$smatch = $matches [1];
					$cg_fb_page_id = $smatch [0];
					
					if (trim ( $cg_fb_page_id ) != '') {
						echo '<br>Successfully extracted  :' . $cg_fb_page_id;
						update_post_meta ( $camp->camp_id, 'cg_fb_page_id', $cg_fb_page_id );
					} else {
						echo '<br>Can not find numeric entityID';
					}
				} else {
					echo '<br>entity_id does not exists either ';
					echo '<br>Can not find valid FB reply.';
					
					if( stristr($exec, 'email_container') ){
						echo '<br><span style="color:red">Please visit the plugin settings page and add the required Facebook cookies values</span>';
					}
					
					
				}
			}
		}
		
		// building feed
		if ((trim ( $cg_fb_page_id ) != '')) {
			
			$cg_fb_source = $camp_general ['cg_fb_source'];
			$cg_fb_from = $camp_general ['cg_fb_from'];
			
			if (trim ( $cg_fb_from ) != 'events')
				$cg_fb_from = 'posts';
			
			//https://m.facebook.com/page_content_list_view/more/?page_id=5550296508&start_cursor=21&num_to_fetch=4&surface_type=timeline
			$cg_fb_page_feed2 = $cg_fb_page_feed = "https://m.facebook.com/page_content_list_view/more/?page_id=" . $cg_fb_page_id . "&start_cursor=27&num_to_fetch=4&surface_type=timeline";
			
			// personal profiles
			if ($cg_fb_source == 'profile') {
				// https://m.facebook.com/profile/timeline/stream/?profile_id=837764889&replace_id=u_0_16
				$cg_fb_page_feed2 = $cg_fb_page_feed = 'https://m.facebook.com/profile/timeline/stream/?profile_id=' . $cg_fb_page_id . '&replace_id=u_0_16';
			} elseif ($cg_fb_source == 'group') {
				// https://m.facebook.com/groups/1432743533609453?&multi_permalinks
				$cg_fb_page_feed2 = $cg_fb_page_feed = 'https://m.facebook.com/groups/' . $cg_fb_page_id . '?&multi_permalinks';
				
				//https://mbasic.facebook.com/groups/212611592563841
				$cg_fb_page_feed2 = $cg_fb_page_feed = 'https://mbasic.facebook.com/groups/' . $cg_fb_page_id ;
			
			}
			
			// events endpoint
			if ($cg_fb_from == 'events') {
				
				if ($cg_fb_source == 'group') {
					
					$cg_fb_page_feed2 = $cg_fb_page_feed = "https://mbasic.facebook.com/" . $cg_fb_page_id . "/?view=events&refid=18";
				} else {
					$cg_fb_page_feed2 = $cg_fb_page_feed = "https://mbasic.facebook.com/" . $cg_fb_page_id . "/events?locale=en_US";
				}
			}
			 
			
			echo '<br>FB URL:' . $cg_fb_page_feed2;
		
			
			// load feed
			// curl get
			$x = 'error';
			$url = $cg_fb_page_feed;
			curl_setopt ( $this->ch, CURLOPT_HTTPGET, 1 );
			curl_setopt ( $this->ch, CURLOPT_URL, trim ( $url ) );
			
			// authorization
			curl_setopt ( $this->ch, CURLOPT_COOKIE, 'xs=' . $wp_automatic_fb_xs . ';c_user=' . $wp_automatic_fb_cuser );
			
			// CACHE
			$saveCache = false;
			
			// disable redirection temporarily
			@curl_setopt ( $this->ch, CURLOPT_FOLLOWLOCATION, 0 );
			
			if (in_array ( 'OPT_FB_CACHE', $camp_opt )) {
				
				$temp = get_post_meta ( $camp->camp_id, 'wp_automatic_cache', true );
				@$temp = base64_decode ( $temp );
				
				if (stristr ( $temp, 'messages' )) {
					
					echo '<br>Results loaded from the cache';
					$exec = $temp;
				} else {
					echo '<br>No valid cache found requesting facebook';
					 
					$saveCache = true;
					
					// nextpage if available
					$nextPageUrl = get_post_meta ( $camp->camp_id, 'nextPageUrl', true );
					if (trim ( $nextPageUrl != '' ) && in_array ( 'OPT_FB_OLD', $camp_opt ) && ! stristr ( $nextPageUrl, 'graph.' )  ) {
						 
						//deprecated m.facebook group pagination reset 
						if($cg_fb_source == 'group' && ! stristr($nextPageUrl , 'mbasic')){
							//skip		
						}else{
							echo '<br>Pagination url:' . $nextPageUrl;
							$url = $nextPageUrl;
							curl_setopt ( $this->ch, CURLOPT_URL, trim ( $nextPageUrl ) );
							
						}
						
					}
					
					$exec = $this->curl_exec_follow ( $this->ch );
					
				 
				}
			} else {
				
				// no cache, delete pagination & cache if existing
				delete_post_meta ( $camp->camp_id, 'nextPageUrl' );
				delete_post_meta ( $camp->camp_id, 'wp_automatic_fb_checked_years' );
				delete_post_meta ( $camp->camp_id, 'wp_automatic_cache' );
				
				$exec = $this->curl_exec_follow ( $this->ch );
				 
			}
 
			
			$x = curl_error ( $this->ch );
			
			echo '<-- Number of chars returned:' . strlen ( $exec ) . ' ' . $x;
			 
			// restore redirection
			@curl_setopt ( $this->ch, CURLOPT_FOLLOWLOCATION, 0 );
			
			 
			// suspecious activity message /checkpoint/block/
			/*
			if (stristr ( $exec, '/checkpoint/block/' )) {
				echo '<br>FB detected suspecious activity usaully because the server IP is different than your personal IP..... removing added session cookies';
				delete_option ( 'wp_automatic_fb_xs' );
			}*/
			
			 
			
			if ((stristr ( $exec, 'payload' ) && stristr ( $exec, $cg_fb_page_id )) || (stristr ( $exec, 'notifications.php' ) && ($cg_fb_from == 'events'  || $cg_fb_source == 'group') )) { // Checks that the object is created correctly
				// correct login
			} else {
				
				if(stristr($exec, 'facebook'))
				echo '<br><span style="color:orange">Warning: Not logged in which means the current session cookie is not correct or got expired. The plugin will not be able to paginate or access content that requires authentication (Add a new session if you have any issues)</span> ';

			}
			
			if (1) {
				
				// $exec = str_replace ( '&amp;', '&', $exec );
				
				// if save cache enbaled
				if ($saveCache) {
					echo '<br>Caching the results..';
					update_post_meta ( $camp->camp_id, 'wp_automatic_cache', base64_encode ( $exec ) );
				}
				
				  
					
				if ($cg_fb_from != 'events') {
				
					
					if ($cg_fb_source == 'group') {
						//group
						$list_exec_raw = $exec;
						 
					}else{
						
						$list_exec_raw = $exec = str_replace ( 'for (;;);', '', $exec );
						$json = (json_decode ( $exec ));
						
						if(isset ($json->payload)){
							$exec = $json->payload->actions [0]->html;
						}else{
							$exec = '';
						}
						
					}
					
							 
				 
					
					require_once 'inc/class.dom.php';
					$wpAutomaticDom = new wpAutomaticDom ( '<html><head></head><body>' . $exec . '</body>' );
					$items = $wpAutomaticDom->getContentByXPath ( '//article', false );
					
					 
					
					// Loop through each feed item and display each item as a hyperlink.
					
					// delete embeded articles
					$i = 0;
					foreach ( $items as $item ) {
						
						if (stristr ( $item, 'og_action_id' )) {
							// remove feeling action "og_action_id":"1872937199467035",
							$item = preg_replace ( '{"og_action_id":"\d*?",}', '', $item );
						}
						
						if ((! stristr ( $item, 'data-ft=\'{"top_level_post_id' ) && ! stristr ( $item, 'data-ft=\'{"qid' ) && ! stristr ( $item, 'data-ft=' )) || ! stristr ( $item, 'top_level_post_id' )) {
							unset ( $items [$i] );
						}
						
						$i ++;
					}
				} else {
					// extract events
					preg_match_all ( '{<a href="/events/(\d+)}', $exec, $events_matches );
					
					if (isset ( $events_matches [1] )) {
						$items = $events_matches [1];
					} else {
						$items = array ();
					}
				}
				
				echo ' items:' . count ( $items );
				
				$i = 0;
				
				foreach ( $items as $item ) {
					
					$is_an_album = false;
					
					if ($cg_fb_from == 'events') {
						echo '<br>Event:' . $item;
					}
					
					// remove image emoji
					$item = preg_replace ( '{<img class="\w*?" height="16".*?>}s', '', $item );
					
					// remove profile pic p32x32
					$item = preg_replace ( '{<img src="[^<]*?p32x32[^<]*?>}s', '', $item );
					$item = preg_replace ( '{<img src="[^<]*?p40x40[^<]*?>}s', '', $item );
					
					
					$imgsrc = ''; // ini
					              
					// txt content for title generation ini
					$txtContent = '';
					
					// get the post ID
					if ($cg_fb_from != 'events') {
						
						if (stristr ( $item, 'top_level_post_id":' )) {
							preg_match ( '{top_level_post_id":"(.*?)"}', $item, $pMatches );
						} else {
							preg_match ( '{top_level_post_id\.(\d*)}', $item, $pMatches );
						}
						
						$item_id = $cg_fb_page_id . '_' . $pMatches [1];
						$single_id = $pMatches [1];
						
						$isEvent = false; // ini
						$id_parts = explode ( '_', $item_id );
						
						$owner_id = $id_parts [0];
						
						// profile tagged post
						$original_content_owner_id =$cg_fb_page_id ;
						if ($cg_fb_source == 'profile') {
							 
							preg_match ( '{content_owner_id_new":"(.*?)"}s', $item, $from_matches2 );
							
							if (isset ( $from_matches2 [1] ) && trim ( $from_matches2 [1] ) != '') {
								$owner_id = $from_matches2 [1];
							}
							
						}
						
						$url = "https://www.facebook.com/{$owner_id}/posts/{$id_parts[1]}";
						
						if ((stristr ( $item, 'story_attachment_style":"new_album"' ) || stristr ( $item, 'story_attachment_style":"album"' )) && stristr ( $item, 'photo_id' )) {
							echo ' <-- seems to be a photo album: ';
							
							// get photo_id
							preg_match ( '{photo_id":"(.*?)"}', $item, $cMatches );
							
							$is_an_album = true;
							$album_url = "https://www.facebook.com/{$cMatches[1]}";
						}
					} else {
						// events
						$id_parts = array (
								$cg_fb_page_id,
								$item 
						);
						$url = "https://www.facebook.com/$item";
						$isEvent = true;
						
						$item_id = $single_id = $item;
					}
					
				 
					
					echo '<br>Link:' . $url;
					
					// check if execluded link due to exact match does not exists
					if ($this->is_execluded ( $camp->camp_id, $url )) {
						echo '<-- Excluded link';
						continue;
					}
				
				
					
					// Shared post or not 
					if( in_array ( 'OPT_FB_NEW', $camp_opt ) && stristr($item, 'original_content_id') ){
						echo '<-- Shared Post';
						continue;
					}
					
					// Owner only profile posts
					if (in_array ( 'OPT_FB_OWNER', $camp_opt ) && $cg_fb_source == 'profile' &&   $owner_id != $cg_fb_page_id    ) {
						echo '<-- Not profile owner post';
						continue;
					}
					
					// get created time
					if ($cg_fb_from != 'events') {
						 
						$created_time = '';
						preg_match ( '{publish_time":(\d*)}', $item, $tMatches );
						echo '<br>Created on:' . $created_time;
						if (isset ( $tMatches [1] ))
							$created_time = $tMatches [1];
					} else {
						$created_time = time ();
					}
					
					// check if old before loading original page if created_time exists in page
					$foundOldPost = false;
					if (in_array ( 'OPT_YT_DATE', $camp_opt ) && trim ( $created_time ) != '') {
						if ($this->is_link_old ( $camp->camp_id, ($created_time) )) {
							echo '<--old post execluding...';
							$foundOldPost = true;
							continue;
						}
					}
					
					if (! $this->is_duplicate ( $url )) {
						echo '<-- new link';
						$i ++;
					} else {
						echo '<-- duplicate in post <a href="' . get_edit_post_link ( $this->duplicate_id ) . '">#' . $this->duplicate_id . '</a>';
						continue;
					}
					  
 					
					// real item html if event. now the $item contains the event id only
					if ($cg_fb_from == 'events') {
						
						$mbasic_event_url = "https://mbasic.facebook.com/events/" . $item . '?locale=en_US';
						
						echo '<br>Basic event URL:' . $mbasic_event_url;
						
						// curl get
						$x = 'error';
						curl_setopt ( $this->ch, CURLOPT_HTTPGET, 1 );
						curl_setopt ( $this->ch, CURLOPT_URL, trim ( $mbasic_event_url ) );
						$item = $this->curl_exec_follow ( $this->ch );
						$x = curl_error ( $this->ch );
						
						// remove emojis
						$item = preg_replace ( '{<img class="\w*?" height="16".*?>}s', '', $item );
					}
					
					// found images
					preg_match_all ( '{<img src=".*?>}', str_replace ( '&amp;', '&', $item ), $imgMatchs );
					$all_imgs = $imgMatchs [0];
					
					$i = 0;
					foreach ( $all_imgs as $single_img ) {
						if (stristr ( $single_img, 'static' ) || stristr ( $single_img, '32x32' )  || stristr ( $single_img, '40x40' ) ) {
							unset ( $all_imgs [$i] );
						}
						
						$i ++;
					}
					
					
					$all_imgs = array_values ( $all_imgs );
					 
					
					// Finding the post type
					if ($cg_fb_from == 'events') {
						$type = 'event';
					} elseif (stristr ( $item, 'videoID' ) || stristr($item , 'video_redirect') || stristr ( $item, 'youtube.com%2Fwatch' )) {
						
						//video_redirect is for mbasic groups
						
						$type = "video";
					} elseif (stristr ( $item, '<a href="/notes' )) {
						$type = "note";
					} elseif (stristr ( $item, '/events/' )) {
						$type = "event";
					} elseif (stristr ( $item, 'offerx_' )) {
						$type = "offer";
					} elseif (stristr ( $item, 'l.php?' ) && stristr ( $item, '</h3></header>' )) {
						$type = "link";
					} elseif (count ( $all_imgs ) > 1 || stristr ( $item, '"story_attachment_style":"photo"' ) || stristr ( $item, '/photos/' )) {
						$type = "photo";
					} elseif (count ( $all_imgs ) > 0) {
						$type = "photo";
					} else {
						$type = 'status';
					}
					
					echo '<br>Item Type:' . $type;
					
					// type check
					if (in_array ( 'OPT_FB_POST_FILTER', $camp_opt )) {
						
						if (! in_array ( 'OPT_FB_POST_' . $type, $camp_opt )) {
							echo '<-- Skip this type not selected ';
							continue;
						}
					}
					
					// buidling content
					$title = '';
					$content = '';
					
					// textual content tn":"*s"}'><span>
					if ($cg_fb_from == 'events') {
						
						// event description https://mbasic.facebook.com/events/2927079347382496
						// Details</header></div></div><section class="_52ja _2pi9 _2pip _2s23">bla bla</section>
						preg_match_all ( '!header></div></div><section class=".*?">(.*?)</section>!s', $item, $contMatches );
					} elseif (stristr ( $item, '*s"}\'></div>' ) && stristr ( $item, 'tn":"H"' )) {
						
						echo '<br>Case#1 content';
						
						// possible sell post
						preg_match_all ( '!(<p>.*?</p>)!s', $item, $contMatches );
						
						// <span class="co">(Sold)</span><span>bla bla title</span>
						preg_match ( '!<span class="\w+?">\(.*?\)</span><span>(.*?)</span>!s', $item, $title_matches );
						
						if (isset ( $title_matches [1] ) && trim ( $title_matches [1] ) != '') {
							$title = $title_matches [1];
						}
					} else {
						
						echo '<br>Case#2 content'; // {"tn":"*s"}
						
						require_once 'inc/class.dom.php';
						
						$item_html = str_replace ( '{"tn":"*s"}', 'target', $item );
						
						$wpAutomaticDom = new wpAutomaticDom ( '<html><head></head><body>' . $item_html . '</body></html>' );
						$items = $wpAutomaticDom->getContentByXPath ( '//*[@data-ft="target"]' );
						
						$found_story_text = $items [0];
						$found_story_text .= isset ( $items [1] ) ? $items [1] : '';
						
						// faked matching array
						$contMatches = array (
								array (
										$found_story_text 
								),
								array (
										$found_story_text 
								) 
						);
						
						/*
						 * preg_match_all('!tn":"\*s"}\'>[\s]*<span[^<]*?>(.*?)</span>.?</?div!s', $item,$contMatches);
						 */
					}
					
					// colored post?
					$is_colored_post = false;
					
					if (stristr ( $item, 'background-image:url' )) {
						echo '-- colored';
						
						// preg_match_all('!tn":"\*s"}.*?<span>(.*?)</span>!s', $item,$contMatches);
						
						$is_colored_post = true;
					}
					
					$contMatches = $contMatches [1];
					
					if (count ( $contMatches ) == 2) {
						if ($contMatches [0] === $contMatches [1]) {
							unset ( $contMatches [1] );
						}
					}
					
					if (isset ( $contMatches [0] )) {
						
						$matched_text_content = implode ( '<br>', $contMatches );
						
						// Remove More Link
						$matched_text_content = preg_replace ( '{<span class="text_exposed_hide.*?span>}su', '', $matched_text_content );
						
						// Remove Translation if exists <div class="_2pim"><p>
						$matched_text_content = preg_replace ( '{<div class="_2pim"><p>.*?</p>}', '', $matched_text_content );
						
						// Remove Rate link <a href="#"><span class="_1_ig">Rate this translation</span></a>
						$matched_text_content = preg_replace ( '{<a href="#"><span class="_1_ig">.*?</a>}', '', $matched_text_content );
						
						// Remove See Translation link <div class="_x7p _3nc8"><a href="#" data-targetid="3592641020750357" data-ft="[]" data-sigil="m-see-translate-link">See translation</a></div>
						$matched_text_content = preg_replace ( '{<div class="_x7p _3nc8"><a href="#" .*?</a></div>}', '', $matched_text_content );
						
						if (stristr ( $matched_text_content, 'background-image' ) || stristr ( $matched_text_content, 'color:rgba' ) || stristr ( $matched_text_content, 'font-size' )) {
							$matched_text_content = strip_tags ( $matched_text_content, '<br><br /><a>' );
						}
						
						$txtContent = $matched_text_content;
						if (! in_array ( 'OPT_FB_TXT_SKIP', $camp_opt )) {
							
							if (stristr ( $matched_text_content, '<p> ' )) {
								$content = $matched_text_content;
							} else {
								$content = '<p>' . $matched_text_content . '</p> ';
							}
						}
					}
					
					// echo '----------------';
					// echo $content;
					
					$content = str_replace ( 'See Translation', '', $content );
					$content = preg_replace ( '{<span class="text_exposed_hide.*?span>}su', '', $content );
					
					// If shared, find original post id
					$original_post_url = $url; // ini
					                           
					// removed
					if (false && stristr ( $item, 'original_content_id' )) {
						preg_match ( '{"original_content_id":"(\d*?)"}s', $item, $original_id_matches );
						
						if (isset ( $original_id_matches [1] ) && trim ( $original_id_matches [1] ) != '') {
							$original_post_url = 'https://www.facebook.com/' . $original_id_matches [1];
							
							echo '<br>Original post URL:' . $original_post_url;
						}
					}
					
					// load original fb post permalinkPost
					// curl get
					$x = 'error';
					curl_setopt ( $this->ch, CURLOPT_HTTPGET, 1 );
					
					if (false && $is_an_album == true) {
						curl_setopt ( $this->ch, CURLOPT_URL, trim ( $album_url ) );
						echo '<br>exec2 album:' . $album_url;
					} else {
						curl_setopt ( $this->ch, CURLOPT_URL, trim ( $original_post_url ) );
						echo '<br>exec2:' . $original_post_url;
					}
 
 				
					// authorization again to clean group special post cookies
					curl_setopt ( $this->ch, CURLOPT_COOKIE, 'xs=' . $wp_automatic_fb_xs . ';c_user=' . $wp_automatic_fb_cuser );
					
					
					// new UI required headers
					$headers [] = "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9";
					$headers [] = "Sec-Fetch-User: ?1";
					curl_setopt ( $this->ch, CURLOPT_HTTPHEADER, $headers );
					
					$exec2 = $this->curl_exec_follow ( $this->ch );
				
					echo '<-- ' . strlen($exec2) . ' chars returned';
					
				
				 
					//must login block FB+Login+Page+Visit
					if(  stristr ( $exec2 , 'FB+Login+Page+Visit' ) || stristr($exec2, 'You must log in to continue.') ){
						echo '<br><br><span style="color:red"><b><u>ERROR !!!</u></b>: Not logged in which means the current session cookie is <b>not correct or got expired</b>. The plugin will not be able to <b>function</b> or access the content   (<b><u> ADD A NEW SESSION COOKIE TO THE PLUGIN SETTINGS PAGE</u></b>).</span> <br>';
						exit;
					}
					
					//verify valid session 
					if( ! stristr ( $exec2 , '"ACCOUNT_ID":"'. trim($wp_automatic_fb_cuser) .'"' ) ){
						echo '<br><br><span style="color:orange"><b><u>WARNING !!!</u></b>: Not logged in which means the current session cookie is <b>not correct or got expired</b> or you have the classic FB UI. The plugin will not be able to <b>fully function</b> or access content that requires authentication (<b><u> ADD A NEW SESSION COOKIE TO THE PLUGIN SETTINGS PAGE</u></b> if you have any issues).</span> <br>';
					}
					 
				
					// remove pinned post timeline_pinned_unit timeline_pinned_unit":{"id
					if (stristr ( $exec2, 'timeline_pinned_unit":{"id' )) {
						$exec2 = preg_replace ( '!timeline_pinned_unit":{"id.*?script>!s', '', $exec2 );
					}
					
					// remove more posts
					if (stristr ( $exec2, 'timeline_feed_units' )) {
						$exec2 = preg_replace ( '!timeline_feed_units":{"edges.*?script>!s', '', $exec2 );
					}

					
					
					/*
					 * echo '--------------------------- exec2:';
					 * echo $exec2;
					 * echo '--------------------- end exec2';
					 * exit;
					 *
					 *
					 * $node_json = json_decode($final_node);
					 */
					
					$exec2_raw = $exec2 = str_replace ( '&amp;', '&', $exec2 );
					$x = curl_error ( $this->ch );
					
					if (trim ( $exec2 ) == '') {
						echo '<-- was not able to load the original FB page ' . $x;
					}
					
					 
					
					// publish date 
					$created_time = '';
					if (trim ( $created_time ) == '') {
					 
						// get the created time "creation_time":1604609958
						preg_match ( '{"creation_time":(\d*)}', $exec2, $time_matches );
						
						if (isset ( $time_matches [1] ) && trim ( $time_matches [1] ) != '') {
							$created_time = $time_matches [1];
						
						}else{
							
							//"publish_time":1608988283,
							preg_match ( '!publish_time.?":(\d{10})!', $exec2 , $time_matches );
							if (isset ( $time_matches [1] ) && trim ( $time_matches [1] ) != '') {
								$created_time = $time_matches [1];
							}
							
						}
						
						echo '<br>Post published timestamp:'.$created_time;
						
						// check if old before loading original page if created_time exists in page
						$foundOldPost = false;
						if (in_array ( 'OPT_YT_DATE', $camp_opt ) && trim ( $created_time ) != '') {
							if ($this->is_link_old ( $camp->camp_id, ($created_time) )) {
								echo '<--old post execluding all coming older posts';
								$foundOldPost = true;
								break;
							}
						}
					}
					
				
					
					// utc convert
					if(trim($created_time) != ''){
						$created_time = date ( 'Y-m-d H:i:s', $created_time );
						$created_time = get_date_from_gmt ( $created_time );
					}
	
					$wpdate = $date = $created_time;
					
					// reading share count
					$shares_count = 0;

					// "share_count":{"count":0,
					preg_match ( '/"share_count":{"count":(.*?),/s', $exec2, $share_matches ); // pages share matches
					if (isset ( $share_matches [1] ) && trim ( $share_matches [1] ) != '')
						$shares_count = $share_matches [1];
					
					if (stristr ( $exec2, 'permalinkPost' )) {
						
						preg_match ( '{permalinkPost">.*?<div class="_4-u2 _4-u8}s', $exec2, $post_matches );
						
						if (isset ( $post_matches [0] ) && trim ( $post_matches [0] ) != '') {
							$exec2 = $post_matches [0];
						}
					}
					
					// if truncated text
					if (! stristr ( $item, '</p></span>' ) && trim ( $txtContent ) != '' && ! $is_colored_post) {
						
						echo '<br>Finding full textual content?';
						
						// <div class="_5pbx
						
						/*
						 * require_once 'inc/class.dom.php';
						 * $exec2_article = str_replace( array('<!--','-->') , '' , $exec2);
						 * $exec2_article = str_replace( array('<code','</code>' ) , array( '<div' , '</div>' ) , $exec2_article );
						 *
						 * $wpAutomaticDom = new wpAutomaticDom( $exec2_article ) ;
						 *
						 * echo $exec2_article;
						 *
						 * $items = $wpAutomaticDom->getContentByXPath('//*[@data-testid="post_message' );
						 *
						 * print_r($items);
						 * exit;
						 *
						 * //faked matching array
						 * $contMatches = array( array( $items[0] ) ,array( $items[0] ));
						 *
						 */
						
						 
						
						preg_match ( '! class="_5pbx.*?>(.*?)</div><div class="_3x-2" data-ft!s', str_replace ( '&amp;', '&', $exec2 ), $full_text_matches );
						
					 
						
						/*
						 * echo $exec2 . '--------------';
						 * print_r($full_text_matches);
						 * exit;
						 */
						
						
						
						if (stristr ( $exec2, 'ReCaptchav2Captcha' )) {
							
							echo '<br>Facebook asked for Capatcha when trying to load the full content. Proxies may be needed to get the full post content';
						}
						
						if (isset ( $full_text_matches [1] ) && trim ( $full_text_matches [1] ) != '') {
							echo '<--found-1';
							
							// remove image emoji
							$full_text_matches [1] = preg_replace ( '{<img class="\w*?" height="16".*?>}s', '', $full_text_matches [1] );
							
							$txtContent = $full_text_matches [1];
							if (! in_array ( 'OPT_FB_TXT_SKIP', $camp_opt ))
								$content = $full_text_matches [1];
						
						}elseif(  ( stristr ( $txtContent, 'story.php' ) || stristr(  $txtContent, '/permalink/'   )   )  && stristr($exec2, 'story":{"message":{"text') ){

							//NEW UI full content
							//story":{"message":{"text":"
							preg_match('!story":{"message":{"text":"(.*?)"}!s', $exec2, $full_text_matches );

							if(isset($full_text_matches[1]) && trim($full_text_matches[1]) != '' ){
								echo '<--found- NEW UI';
 								
								$txtContent = wp_automatic_fix_json_part( $full_text_matches [1] ) .'<br>' ;
							 
								
								if (! in_array ( 'OPT_FB_TXT_SKIP', $camp_opt ))
									$content = $txtContent;
								
							}
							
						
						} else {
							  
							// data-ft="&#123;&quot;tn&quot;:&quot;K&quot;&#125;">
							preg_match ( '!data-ft="&#123;&quot;tn&quot;:&quot;K&quot;&#125;">(.*?)</div>!s', str_replace ( '&amp;', '&', $exec2 ), $full_text_matches );
							
							if (isset ( $full_text_matches [1] ) && trim ( $full_text_matches [1] ) != '') {
								echo '<--found-2';
								
								// remove image emoji
								$full_text_matches [1] = preg_replace ( '{<img class="\w*?" height="16".*?>}s', '', $full_text_matches [1] );
								
								$txtContent = $full_text_matches [1];
								if (! in_array ( 'OPT_FB_TXT_SKIP', $camp_opt ))
									$content = $full_text_matches [1];
							}
						}
					} elseif (stristr ( $txtContent, '...' ) && stristr ( $txtContent, 'story.php' )) {
						
						echo '<br>A shared post with truncated text was found, getting the full text if possible';
						
						preg_match_all ( '!<div data-ad-preview="message.*?/div>!s', $exec2, $full_text_matches );
						
						$full_text_matches = $full_text_matches [0];
						
						if (count ( $full_text_matches ) > 2) {
							$full_text_matches = array (
									$full_text_matches [0],
									$full_text_matches [1] 
							);
						}
						
						$full_text_matches = implode ( '', $full_text_matches );
						
						preg_match_all ( '{<p>(.*?)</p>}s', $full_text_matches, $p_matches );
						
						$p_nobrcket = $p_matches [1];
						$p_bracket = $p_matches [0];
						
						if (count ( $p_matches [0] ) == 2) {
							
							if (stristr ( $p_nobrcket [1], $p_nobrcket [0] )) {
								unset ( $p_bracket [0] );
							}
						}
						
						$possible_full = implode ( '', $p_bracket );
						
						if (trim ( $possible_full ) != '') {
							// remove image emoji
							$possible_full = preg_replace ( '{<img class="\w*?" height="16".*?>}s', '', $possible_full );
							
							$txtContent = $possible_full;
							if (! in_array ( 'OPT_FB_TXT_SKIP', $camp_opt ))
								$content = $possible_full;
						}
					}
					
					$content = str_replace ( 'See Translation', '', $content );
					
					// remove recent photos widget from sidebar
					$exec2 = preg_replace ( '{<ul class="_5ks4.*?ul>}s', '', $exec2 );
					
					// full sized images urls
					$full_imgs_srcs = array (); // ini
					                            
					// empty attachement removal
					$exec2 = str_replace ( 'attachments_info:{}', '', $exec2 );
					
					
					
					
					if (stristr ( $exec2, 'attachments_info' )) {
						
						preg_match ( '!attachments_info:{.*?}}!s', $exec2, $full_imgs_html );
						
						if (isset ( $full_imgs_html [0] ) && stristr ( $full_imgs_html [0], 'url' )) {
							preg_match_all ( '!url:"(.*?)"!s', $full_imgs_html [0], $full_imgs_matches );
							
							if (isset ( $full_imgs_matches [1] ) && count ( $full_imgs_matches [1] ) > 0) {
								$full_imgs_srcs = $full_imgs_matches [1];
							}
						}
						
						echo '<br>Image found using:attachments_info ' ;
						
					} elseif (stristr ( $exec2, 'data-ploi="' )) {
						
						preg_match_all ( '!data-ploi="(.*?)"!s', $exec2, $poly_matches );
						
						if (isset ( $poly_matches [1] ) && count ( $poly_matches [1] ) > 0) {
							$full_imgs_srcs = $poly_matches [1];
						}
						
						echo '<br>Image found using:data-ploi ' ;
						
					} elseif (stristr ( $exec2, 'data-plsi="' )) {
						
						preg_match_all ( '!data-plsi="(.*?)"!s', $exec2, $poly_matches );
						
						if (isset ( $poly_matches [1] ) && count ( $poly_matches [1] ) > 0) {
							$full_imgs_srcs = $poly_matches [1];
						}
						
						echo '<br>Image found data-plsi ' ;
					
					} elseif (preg_match ( '!viewer_image":{"height":\d*,"width":\d*,"uri":"(.*?)"!', $exec2 )) {
						
						// Multiple images new UI
						// viewer_image":{"height":500,"width":1200,"uri":
						preg_match_all ( '!viewer_image":{"height":\d*,"width":\d*,"uri":"(.*?)"!', $exec2, $photo_matches );
						$json_part = '["' . implode ( '","', $photo_matches [1] ) . '"]';
						
						$full_imgs_srcs = array_unique ( json_decode ( $json_part ) );
						
						echo '<br>Image found viewer_image ' ;
						 
					} elseif (stristr ( $exec2, '"photo_image":{"uri"' )) {
						
						// single image new UI
						
						preg_match_all ( '!"photo_image":{"uri":"(.*?)"!', $exec2, $photo_matches );
						$json_part = '["' . implode ( '","', $photo_matches [1] ) . '"]';
						$full_imgs_srcs = array_unique ( json_decode ( $json_part ) );
						
						echo '<br>Image found photo_image ' ;
						
					} elseif (stristr ( $exec2, '"large_share_image"' )) {
						
						// link image
						// "large_share_image":{"height":261,"uri":"https:\/\/external.faly1-2.fna.fbcdn.net\/safe_image.php
						preg_match_all ( '!"large_share_image.*?"uri":"(.*?)"!', $exec2, $photo_matches );
						$json_part = '["' . implode ( '","', $photo_matches [1] ) . '"]';
						$full_imgs_srcs = array_unique ( json_decode ( $json_part ) );
						$all_imgs [0] = $full_imgs_srcs [0];
					
						echo '<br>Image found using large_share_image ' ;
						
					} elseif (stristr ( $exec2, 'GenericAttachmentMedia","image"' )) {
						
						// link image
						// "large_share_image":{"height":261,"uri":"https:\/\/external.faly1-2.fna.fbcdn.net\/safe_image.php
						preg_match_all ( '!GenericAttachmentMedia","image":{"height":\d*,"uri":"(.*?)"!', $exec2, $photo_matches );
						$json_part = '["' . implode ( '","', $photo_matches [1] ) . '"]';
						$full_imgs_srcs = array_unique ( json_decode ( $json_part ) );
						
						if (stristr ( $full_imgs_srcs [0], 'safe_image' )) {
							preg_match ( '{url\=(.*?)&}', $full_imgs_srcs [0], $external_img_founds );
							$full_imgs_srcs [0] = urldecode ( $external_img_founds [1] );
						}
						
						$all_imgs [0] = $full_imgs_srcs [0];
						
						echo '<br>Image found using GenericAttachmentMedia ' ;
						
					} elseif (stristr ( $exec2, 'scaledImageFit' )) {
						
						
						
						//remove logo image <div class="uiScaledImageContainer
						$exec2 = preg_replace('!<div class="uiScaledImageContainer.*?div>!', '', $exec2 , 1);
						
						preg_match_all ( '{<img class="scaledImageFit.*?src="(.*?)"}s', $exec2, $poly_matches );
						
						
						
						if (isset ( $poly_matches [1] ) && is_array ( $poly_matches [1] ) )  {
							$full_imgs_srcs = $poly_matches [1];
							$all_imgs [0] = $full_imgs_srcs [0];
						}
						
						echo '<br>Image found using scaledImageFit ' ;
						
					}
 					
					$full_imgs_srcs = array_filter($full_imgs_srcs);
					
					echo '<br>' . count ( $full_imgs_srcs ) . ' images found to add to the post ';
					 
					//status type fix if images found on exec2
					if($type == 'status' && count($full_imgs_srcs) > 0){
						$type = 'photo';
						echo '<-- Adjusting type to Photo instead';
					}
						
					// ini
					$ret ['vid_url'] = '';
					$ret ['vid_id'] = '';
					
					if ($type == 'link') {
						
						preg_match ( '{l.php\?u=(.*?)&}s', $item, $linkMatches );
						$foundLink = $linkMatches [1];
						$link = urldecode ( $foundLink );
						print_r ( '<br>Found Link:' . $link );
						
						// get link title h3 class=
						preg_match ( '!<h3 class="[^<]*?"><span class="[^<]*?"><span[^<]*?>([^<]*?)</span></span></h3></header>!s', $item, $linkTMatches );
						
						$title = $linkTitle = isset ( $linkTMatches [1] ) ? $linkTMatches [1] : '';
						
						// <h3 style="text-align: right" class="fg eu fh" dir="rtl">سب سے زیادہ جعلی لائسنس کے جعلی امتحانات شاہد خاقان کے زمانے میں پاس کئے گئے،دھماکہ خیز انکشافات</h3>
						if (trim ( $title ) == '') {
							preg_match ( '!<h3 style="text-align: right" class="[^<]*?" dir="rtl">([^<]*?)</h3>!s', $item, $linkTMatches );
							$title = $linkTitle = $linkTMatches [1];
						}
						
						echo '<br>Link title:' . $linkTitle;
						
						// no title for auto generation
						if (in_array ( 'OPT_GENERATE_NO_LINK', $camp_opt ))
							$title = '';
						
						// get image url
						if (isset ( $all_imgs [0] ) && trim ( $all_imgs [0] ) != '')
							$imgsrc = $link_img = str_replace ( '&amp;', '&', $all_imgs [0] );
						
						if (stristr ( $link_img, 'url=' ) && ! stristr ( $link_img, 'fbcdn.net' )) {
							$link_img_prts = explode ( 'url=', $link_img );
							$link_img = $link_img_prts [1];
							$link_img_prts = explode ( '&', $link_img );
							$link_img = urldecode ( $link_img_prts [0] );
							
							$imgsrc = $link_img;
						} else {
							
							// get the image from the loaded page
							preg_match ( '{<img class="scaledImageFit.*?src="(.*?)"}s', $exec2, $scaled_matches );
							if (isset ( $scaled_matches [1] ) && trim ( $scaled_matches [1] ) != '') {
								$imgsrc = $link_img = str_replace ( '&amp;', '&', $scaled_matches [1] );
							}
						}
						
						if (trim ( $link_img ) != '') {
							
							if (stristr ( $link_img, '<img' )) {
								$content .= '<p><a href="' . $link . '">' . $link_img . '</a> </p>';
							} else {
								$content .= '<p><a href="' . $link . '"><img title="' . $title . '" src="' . $link_img . '" /></a> </p>';
							}
						}
						
						// add link to the content
						$content .= '<p><a href="' . $link . '">' . $linkTitle . '</a></p>';
						
						// description is no more existing getting it _6m7 _3bt9
						
						preg_match ( '{_6m7 _3bt9">(.*?)</div>}s', $exec2, $description_matches );
						if (isset ( $description_matches [1] ) && trim ( $description_matches [1] ) != '') {
							
							$txtContent .= $description_matches [1];
							if (! in_array ( 'OPT_FB_TXT_SKIP', $camp_opt ))
								$content .= $description_matches [1];
						}
					} elseif ($type == 'video') {
						
						$style = '';
						
						if (in_array ( 'OPT_FB_VID_IMG_HIDE', $camp_opt )) {
							$style = ' style="display:none" ';
						}
						
						if (stristr ( $item, 'youtube.com%2Fwatch' )) {
							
							preg_match ( '{l.php\?u=(.*?)&}s', $item, $linkMatches );
							$foundLink = $linkMatches [1];
							$link = urldecode ( $foundLink );
							print_r ( '<br>Found Link:' . $link );
							
							// get link title
							preg_match ( '!><span>([^<]*?)</span></span></h3>!s', $item, $linkTMatches );
							$title = $linkTitle = $linkTMatches [1];
							
							echo '<br>Link title:' . $linkTitle;
							
							// get image url
							if (isset ( $all_imgs [0] ))
								$content = '<img ' . $style . ' title="' . $title . '" src="' . $all_imgs [0] . '" /></a><br>' . $content;
							
							$content .= '<br><br>[embed]' . $link . '[/embed]';
							
							$ret ['vid_embed'] = $link;
							
							$vidurl = $link;
						} else {
							
							// vid title
							
							// fix aria-label="Verified Page"
							$item = str_replace ( 'role="img" aria-label=', '', $item );
							preg_match ( '{aria-label="(.*?)"}s', $item, $vid_title_match );
							// $title = $vid_title_match [1];
							
							$watch = trim ( get_option ( 'wp_automatic_fb_w', '' ) );
							$watch_video = trim ( get_option ( 'wp_automatic_fb_wv', '' ) );
							
							$watch = (trim ( $watch ) != '') ? $watch : 'Watch';
							$watch_video = (trim ( $watch_video ) != '') ? $watch_video : 'Watch video';
							
							// $title = str_replace ( $watch_video, '', $title );
							// $title = preg_replace ( '{^' . $watch . '}', '', $title );
							
							echo '<br>Video title:' . $title;
							
				 
							
							// get image url {"__typename":"Video","thumbnailImage":{"uri":"
							preg_match_all ( '!{"__typename":"Video","thumbnailImage":{"uri":"(.*?)"!', $exec2, $imgMatch );
							$imgMatch = $imgMatch [1];
							
							// when pinned post is a video there will be two matches for videos
							if (count ( $imgMatch ) > 1) {
								$the_vid_img_raw = $imgMatch [1];
							
							}elseif(count ( $imgMatch ) == 0){
								//background: url('
							
								echo '<br>Finding image from bg ';
								preg_match( "!background: url\('(.*?)'!"  , $item , $matches_bg_img );
								
								
								$matches_bg_img[1] = str_replace( '\3a' , ':' , $matches_bg_img[1]);
								$matches_bg_img[1] = str_replace( '\3d' , '=' , $matches_bg_img[1]);
								$matches_bg_img[1] = str_replace( '\26' , '&' , $matches_bg_img[1]);
								$matches_bg_img[1] = str_replace(' ' , '', $matches_bg_img[1]);
								 
								$the_vid_img_raw = $matches_bg_img[1] ;
								
							} else {
								$the_vid_img_raw = $imgMatch [0];
							}
							
							$link_img = str_replace ( '\/', '/', $the_vid_img_raw );
							
							echo '<br>Video img:' . $link_img;
							
							$imgsrc = $link_img;
							
							if (count ( $full_imgs_srcs ) > 1 && ! stristr ( $exec2, '_3chq' )) {
								// mixed post multiple images
								foreach ( $full_imgs_srcs as $imgsrc ) {
									$content = $content . '<img ' . $style . ' title="' . $title . '" src="' . $imgsrc . '" /></a><br>';
								}
							} else {
								$content = '<img ' . $style . ' title="' . $title . '" src="' . $imgsrc . '" /></a><br>' . $content;
							}
							
							
						 
						if(stristr($item, '"videoID"')){
								
								//"videoID":"194028178836794"
								preg_match ( '{videoID":"(.*?)"}s', $item, $id_match );
						 
						}elseif (stristr ( $item, 'photo_id":"' )) {
								
								// vid id photo_id":"
								preg_match ( '{photo_id":"(.*?)"}s', $item, $id_match );
								
							} else {
								
								// maybe multiple videos/mixed post? source=media_collage&amp;id=348362152651078&amp;r
								preg_match( '{source=.*?&amp;id=(\d*?)&}', $item, $id_match );
								 
							}
							
							
							$vid_id = $id_match [1];
							$id_match = array($id_match[1]);
							
							echo '<br>Video ID:' . $vid_id;
							
							// vid url
							$vidurl = "https://www.facebook.com/video.php?v=$vid_id";
							echo '<br>Video URL:' . $vidurl;
							
							$ret ['vid_url'] = $vidurl;
							$ret ['vid_id'] = $vid_id;
							
							// embed code
							$vidAuto = '';
							if (in_array ( 'OPT_FB_VID_AUTO', $camp_opt )) {
								$vidAuto = ' autoplay= "true" ';
								$autoplay = 'true';
							} else {
								$autoplay = 'false';
							}
							
							$vid_mute = in_array ( 'OPT_FB_VID_MUTE', $camp_opt ) ? ' mute=1 ' : '';
							
							$js_mute = ! in_array ( 'OPT_FB_VID_MUTE', $camp_opt ) ? "window.fbAsyncInit = function() {FB.init({appId      : '',xfbml      : true,version    : 'v2.5'});var my_video_player;FB.Event.subscribe('xfbml.ready', function(msg) {if (msg.type === 'video') {my_video_player = msg.instance; my_video_player.unmute();}});};" : '';
							
							$ret ['vid_embed'] = '<div id="fb-root"></div><script>' . $js_mute . '(function(d, s, id) {  var js, fjs = d.getElementsByTagName(s)[0];  if (d.getElementById(id)) return;  js = d.createElement(s); js.id = id;  js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.3";  fjs.parentNode.insertBefore(js, fjs);}(document, \'script\', \'facebook-jssdk\'));</script><div class="fb-video" data-autoplay="' . $autoplay . '" data-allowfullscreen="true" data-href="https://www.facebook.com/video.php?v=' . $vid_id . '&amp;set=vb.500808182&amp;type=1"><div class="fb-xfbml-parse-ignore"></div></div>';
							
							if ((defined ( 'PARENT_THEME' ) && (PARENT_THEME == 'truemag' || PARENT_THEME == 'newstube')) || class_exists ( 'Cactus_video' )) {
							} else {
								
								if (! in_array ( 'OPT_FB_VID_SKIP', $camp_opt )) {
									
									foreach ( $id_match as $vid_id_single ) {
										$content .= '[fb_vid ' . $vid_mute . $vidAuto . ' id="' . $vid_id_single . '"]';
									}
								}
							}
						}
					} elseif ($type == 'note') {
						
						// title
						preg_match ( '!<div class="\w{2}">([^<]+?)</div>!s', $item, $title_matches );
						$title = $title_matches [1];
						
						// get image url <div class="_30q-" style="background-image: url
						
						if (stristr ( $exec2, '_30q-' )) {
							
							preg_match ( '{<div class="_30q-" style="background-image: url\((.*?)\)}s', $exec2, $full_img_matches );
							
							if (isset ( $full_img_matches [1] ) && trim ( $full_img_matches [1] ) != '') {
								$imgsrc = $link_img = str_replace ( '&amp;', '&', $full_img_matches [1] );
								$content = '<img   title="' . $title . '" src="' . $link_img . '" /></a><br>' . $content;
							}
						} elseif (isset ( $all_imgs [0] )) {
							preg_match ( '{<img src="(.*?)".*?>}', $all_imgs [0], $imgMatch );
							
							if (isset ( $imgMatch [1] ) && trim ( $imgMatch [1] ) != '') {
								$imgsrc = $link_img = str_replace ( '&amp;', '&', $imgMatch [1] );
								$content = '<img   title="' . $title . '" src="' . $link_img . '" /></a><br>' . $content;
							}
						}
						
						// description
						preg_match ( '!<div class="\w{2} \w{2}">([^<]+)</div>!s', $item, $content_matches );
						$content = $content . $content_matches [1];
					} elseif ($type == 'event') {
						
						// missing image, event description
						// event title
						
						if ($cg_fb_from == 'events') {
							// <title id="pageTitle">Decor ideas discussion</title>
							preg_match ( '!<title>(.*?)</title>!s', $exec2, $title_matches );
						} else {
							 //event from the timeline post
							 preg_match ( '!<h3 class="\w{2} \w{2} \w{2}">([^<]+?)</h3>!s', $item, $title_matches );
							 
							 if(empty($title_matches[1])){
							 	//{"__typename":"Event","name":"
							 	preg_match ( '!"__typename":"Event","name":"(.*?)"!s', $exec2, $title_matches );
							 	if(! empty($title_matches[1])) $title_matches[1] = wp_automatic_fix_json_part($title_matches[1]);
							 }
						
						}
						  
						$title = $title_matches [1];
						
						if (stristr ( $exec2, 'profile_picture_for_sticky_bar":{"uri":"' )) {
						
							//ntCoverPhotoRenderer","cover_photo":{"photo":{"image":{"uri":"https:\/
							//preg_match ( '!CoverPhotoRenderer","cover_photo":{"photo":{"image":{"uri":"(.*?)"!s', $exec2, $scaled_matches );
							
							//cover_photo":{"photo":{"viewer_image":{"height":960,"width":953},"accessibility_caption":"May be a cartoon of standing","image":{"height":960,"uri":"https:\/\/sc
							preg_match ( '!CoverPhotoRenderer","cover_photo":{"photo.*?"uri":"(.*?)"!s', $exec2, $scaled_matches );
							 
							
							if( empty($scaled_matches[1]))
 							preg_match ( '!image":{"height":\d*,"uri":"(.*?)"!s', $exec2, $scaled_matches );
						
						} elseif (stristr ( $exec2, '<video' )) {
							preg_match ( '{margin-top:0px;" src="(.*?)"}s', $exec2, $scaled_matches );
						}
						
						
						
						if (isset ( $scaled_matches [1] ) && trim ( $scaled_matches [1] ) != '') {
							$imgsrc = $link_img = str_replace ( '\/', '/', $scaled_matches [1] );
							$content = '<img   title="' . $title . '" src="' . $link_img . '" /></a><br>' . $content;
						
						 
						}
					} elseif ($type == 'offer') {
						
						// offer title
						preg_match ( '!<h3 class="\w{2} \w{2} \w{2}">([^<]+?)</h3>!s', $item, $title_matches );
						$title = $title_matches [1];
						
						if (trim ( $content ) == '')
							$content = $title;
						
						preg_match ( '{<img class="scaledImageFit.*?src="(.*?)"}s', $exec2, $scaled_matches );
						if (isset ( $scaled_matches [1] ) && trim ( $scaled_matches [1] ) != '') {
							$imgsrc = $link_img = str_replace ( '&amp;', '&', $scaled_matches [1] );
							$content = '<img   title="' . $title . '" src="' . $link_img . '" /></a><br>' . $content;
						}
					} elseif ($type == 'photo') {
						
						if (count ( $full_imgs_srcs ) > 0) {
							
							// full sized images found
							foreach ( $full_imgs_srcs as $single_img_src ) {
								
								if (in_array ( 'OPT_FB_IMG_LNK_DISABLE', $camp_opt )) {
									$content .= '<br><img class="wp_automatic_fb_img" title="' . $title . '" src="' . $single_img_src . '" />';
								} else {
									$content .= '<br><a href="' . $single_img_src . '"><img class="wp_automatic_fb_img" title="' . $title . '" src="' . $single_img_src . '" /></a>';
								}
							}
						} else {
							// small sized images
							$content = $content . implode ( '', $all_imgs );
						}
						
						preg_match ( '{src="(.*?)"}', $content, $src_matches );
						
						if (isset ( $src_matches [1] ) && trim ( $src_matches [1] ) != '') {
							$imgsrc = str_replace ( '&amp;', '&', $src_matches [1] );
						}
					}
					
					// check if title exits or generate it
					if (trim ( $title ) == '' && in_array ( 'OPT_GENERATE_FB_TITLE', $camp_opt )) {
						
						echo '<br>No title generating...';
						
						if (! function_exists ( 'wp_staticize_emoji' )) {
						} else {
						}
						
						// line breaks for title generation stop at line breaks
						$tempContent = str_replace ( '</p><p>', "\n", $txtContent );
						$tempContent = str_replace ( 'See Translation', '', $tempContent );
						$tempContent = str_replace ( '<br />', "\n", $tempContent );
						$tempContent = str_replace ( '<br >', "\n", $tempContent );
						$tempContent = str_replace ( '<br>', "\n", $tempContent );
						$tempContent = str_replace ( '<br/>', "\n", $tempContent );
						
						$tempContent = $this->removeEmoji ( strip_tags ( strip_shortcodes ( $tempContent ) ) );
						
						$tempContent = preg_replace ( '@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@', '', $tempContent );
						
						// Chars count
						$charsCount = $camp_general ['cg_fb_title_count'];
						if (! is_numeric ( $charsCount ))
							$charsCount = 80;
						
						if (function_exists ( 'mb_substr' )) {
							
							$newTitle = mb_substr ( $tempContent, 0, $charsCount );
							
							if (in_array ( 'OPT_GENERATE_FB_RETURN', $camp_opt ) && stristr ( $newTitle, "\n" )) {
								
								$suggestedTitle = preg_replace ( "{\n.*}", '', $newTitle );
								if (trim ( $suggestedTitle ) != '') {
									$newTitle = trim ( $suggestedTitle );
									
									if (in_array ( 'OPT_FB_STRIP_TITLE', $camp_opt )) {
										$before_title_removal = $content;
										$content = str_replace ( $suggestedTitle . "<br />", '', $content );
										
										if ($content == $before_title_removal) {
											$content = str_replace ( '<p>' . $suggestedTitle . "</p>", '', $content );
										}
										
										if ($content == $before_title_removal) {
											$content = str_replace ( $suggestedTitle, '', $content );
										}
									}
								}
							}
						} else {
							$newTitle = substr ( $tempContent, 0, $charsCount );
							
							echo '<br>mb_str is not installed !!!';
						}
						
						if (trim ( $newTitle ) == '') {
							echo '<- Empty title';
						} else {
							
							$title = $newTitle;
							
							if (! in_array ( 'OPT_GENERATE_FB_DOT', $camp_opt ) && $title != $tempContent) {
								$title .= '...';
							}
							
							echo ':' . $title;
						}
					}
					
					if (trim ( $title ) == '' && in_array ( 'OPT_GENERATE_FB_TITLE_DEFAULT', $camp_opt )) {
						
						 $title = $camp_general['cg_fb_title_default'];
						 echo '<-- Using default title:' . $title;
					}
					
					if (trim ( $title ) == '' && in_array ( 'OPT_FB_TITLE_SKIP', $camp_opt )) {
						echo '<-- No title skiping.';
						continue;
					}
					
					// remove referral suffix
					if (stristr ( $content, 'com/l.php' )) {
						
						// extract links
						preg_match_all ( '{"http://l\.facebook\.com/l\.php\?u=(.*?)"}', $content, $matches );
						
						$founds = $matches [0];
						$links = $matches [1];
						
						$i = 0;
						foreach ( $founds as $found ) {
							
							$found = str_replace ( '"', '', $found );
							$link = $links [$i];
							
							$link_parts = explode ( '&h', $link );
							$link = $link_parts [0];
							
							$content = str_replace ( $found, urldecode ( $link ), $content );
							
							$i ++;
						}
					}
					
					// replace thumbnails by full image for external links
					if (stristr ( $content, 'safe_image.php' )) {
						
						if (! stristr ( $content, 'fbstaging' )) {
							
							preg_match_all ( '{https://[^:]*?safe_image\.php.*?url=(.*?)"}', $content, $matches );
							
							$found_imgs = $matches [0];
							$found_imgs_links = $matches [1];
							
							$i = 0;
							
							foreach ( $found_imgs as $found_img ) {
								
								$found_imgs_links [$i] = preg_replace ( '{&.*}', '', $found_imgs_links [$i] );
								
								$found_img_link = urldecode ( $found_imgs_links [$i] );
								
								$content = str_replace ( $found_img, $found_img_link . "\"", $content );
								
								$imgsrc = $found_img_link;
							}
						} else {
							
							$content = str_replace ( '&w=130', '&w=650', $content );
							$content = str_replace ( '&h=130', '&h=650', $content );
							
							$imgsrc = str_replace ( '&w=130', '&w=650', $imgsrc );
							$imgsrc = str_replace ( '&h=130', '&h=650', $imgsrc );
						}
					}
					
					// small images check s130x130
					if (0 && stristr ( $content, '130x130' ) || 0 && $type == 'photo') {
						echo '<br>Small images found extracting full images..';
						
						preg_match_all ( '{"https://[^"]*?\w130x130/(.*?)\..*?"}', $content, $matches );
						
						$small_imgs_srcs = str_replace ( '"', '', $matches [0] );
						$small_imgs_ids = $matches [1];
						
						// remove _o or _n
						$small_imgs_ids = preg_replace ( '{_\D}', '', $small_imgs_ids );
						
						// remove start of the id
						$small_imgs_ids = preg_replace ( '{^\d*?_}', '', $small_imgs_ids );
						
						// get oritinal page
						$x = 'error';
						curl_setopt ( $this->ch, CURLOPT_HTTPGET, 1 );
						curl_setopt ( $this->ch, CURLOPT_URL, trim ( html_entity_decode ( $url ) ) );
						$exec = $this->curl_exec_follow ( $this->ch );
						$x = curl_error ( $this->ch );
						
						if (stristr ( $exec, '<img class="scaled' ) && 0) {
							echo '<br>success loaded original page';
							
							// get imgs displayed
							preg_match_all ( '{<img class="scaled.*?>}s', $exec, $all_scalled_imgs_matches );
							$plain_imas_html = implode ( ' ', $all_scalled_imgs_matches [0] );
							
							// get ids without date at start \d{8}_(\d*?_\d*?)_
							preg_match_all ( '{\d{4,8}_(\d*?_\d*?)_}', $plain_imas_html, $all_ids_imgs_matches );
							
							$all_ids_imgs = array_unique ( $all_ids_imgs_matches [1] );
							$small_imgs_ids = $all_ids_imgs;
							
							$firstImage = '';
							@$firstImage = $all_ids_imgs [0];
							
							$i = 0;
							foreach ( $small_imgs_ids as $small_imgs_id ) {
								
								unset ( $large_imgs_matches );
								
								// searching full image
								preg_match ( '{src="(https://[^"]*?' . $small_imgs_id . '.*?)"}', $exec, $large_imgs_matches );
								
								// ajaxify images
								unset ( $large_imgs_matches_ajax );
								preg_match ( '{src=(https%3A%2F%2F[^&]*?' . $small_imgs_id . '.*?)&}', $exec, $large_imgs_matches_ajax );
								
								if (trim ( $large_imgs_matches [1] ) != '') {
									
									$replace_img = $large_imgs_matches [1];
									
									// check if there is a larger ajaxify image or not
									if (isset ( $large_imgs_matches_ajax [1] ) && trim ( $large_imgs_matches_ajax [1] ) != '') {
										$replace_img = urldecode ( $large_imgs_matches_ajax [1] );
									}
									
									// if first image and image in the original content differs: case: added x photos to album
									if ($i == 0 && (! stristr ( $content, $small_imgs_id ) || ! stristr ( $content, 'w130x130' ))) {
										
										echo '<br>Removing first image first';
										$content = preg_replace ( '{<img.*?>}', '', $content );
									}
									
									// echo ' Replacing '.$small_imgs_srcs[$i] . ' with '.$replace_img;
									if (stristr ( $content, $small_imgs_id )) {
										
										$content = str_replace ( $small_imgs_srcs [$i], $replace_img, $content );
									} else {
										$content = str_replace ( '<!--reset_images-->', '<img class="wp_automatic_fb_img" src="' . $replace_img . '"/><!--reset_images-->', $content );
									}
								}
								
								$i ++;
							}
							
							if ($type == 'video') {
								echo '<br>Extracting vid image';
								
								preg_match ( '{background-image: url\((.*?)\)}', $exec, $vid_img_match );
								
								$vid_img = $vid_img_match [1];
								
								if (trim ( $vid_img ) != '') {
									$content = str_replace ( $item->picture, $vid_img, $content );
									echo '-> success';
								} else {
									echo '-> failed';
								}
							}
						} else {
							echo '<br>Can not find image id at soure loaded page small img id:' . $small_imgs_ids [0];
						}
					}
					
					// fix links of facebook short /
					// $content = str_replace('href="/', 'href="https://facebook.com/', $content);
					$content = preg_replace ( '{href="/(\w)}', 'href="https://facebook.com/$1', $content );
					
					// change img class
					$content = str_replace ( 'class="img"', 'class="wp_automatic_fb_img"', $content );
					
					// skip if no image
					if (in_array ( 'OPT_FB_IMG_SKIP', $camp_opt )) {
						
						if (! stristr ( $content, '<img' )) {
							echo 'Post have no image skipping...';
							$this->link_execlude ( $camp->camp_id, $url );
							continue;
						}
					}
					
					if ($isEvent == true) {
						
						$ret ['original_title'] = $title;
						$ret ['original_link'] = $url;
						$ret ['matched_content'] = $content;
						$ret ['original_date'] = $wpdate;
						$ret ['image_src'] = $imgsrc;
						$ret ['post_id'] = $item_id;
						
						// lat and long
						$lat = '';
						$long = '';
						
						if (stristr ( $exec2, '"latitude"' )) {
							
							// ,"latitude":-7.4464878707658e-12,"longitude":9.0949470177293e-13,
							preg_match ( '{"latitude":(.*?),"longitude":(.*?)\}}', $exec2, $loc_matches );
							 
							if (isset ( $loc_matches [1] ) && isset ( $loc_matches [2] )) {
								$lat = $loc_matches [1];
								$long = $loc_matches [2];
							}
						}
						
						$ret ['place_latitude'] = $lat;
						$ret ['place_longitude'] = $long;
						
						$ret ['place_map'] = isset ( $loc_matches [1] ) ? '<iframe src = "https://maps.google.com/maps?q=' . $lat . ',' . $long . '&hl=es;z=14&amp;output=embed"></iframe>' : '';
						$ret ['event_description'] = $txtContent;
						
						// start, end time content="2018-06-16T07:00:00-07:00 to 2018-08-16T11:00:00-07:00">
						$start_time = '';
						$end_time = '';
						$ret ['start_time_timestamp'] = '';
						$ret ['end_time_timestamp'] = '';
						$ret ['start_time'] = '';
						$ret ['end_time'] = '';
						
						// "utc_start_timestamp":1604865600,"utc_end_timestamp":null}
						//,"start_timestamp":1622181600,"end_timestamp":1622577600},
						preg_match ( '!"start_timestamp":(.*?),"end_timestamp":(.*?)}!', $exec2, $date_matches );
						
						if (isset ( $date_matches [1] ) && trim ( $date_matches [1] ) != '') {
						 
							
							$start_timestamp_no_timezoned = $date_matches [1]; //1604865600
							
							$start_date_no_timezoned = gmdate ( 'Y-m-d H:i:s', $start_timestamp_no_timezoned ); // 2020-11-08 20:00:00
 
							$start_date_timezoned = $start_date_no_timezoned ; //ini 2020-11-08 20:00:00
							
							//getting timezone "tz_display_name":"EST"
							preg_match ( '!"tz_display_name":"(.*?)"!', $exec2, $tz_matches );
							
							if(isset($tz_matches[1]) && trim($tz_matches[1]) != ''  ){
								//found timezone EST
								$found_timezone = str_replace('UTC' , 'GMT',  $tz_matches[1]);
								$start_date_timezoned = $start_date_timezoned . ' ' . $found_timezone ; //2020-11-08 20:00:00 EST
							
							}
							
							//getting correct UTC/GMT timestamp
							$start_date_utc = strtotime($start_date_timezoned); //1604883600
						
							
							$ret ['start_time'] = get_date_from_gmt ( gmdate ( 'Y-m-d H:i:s',  $start_date_utc ) ); // blog timezoned date 
							$ret ['start_time_timestamp'] = strtotime ( $ret ['start_time'] );
							
							//end date if exists
							if (isset ( $date_matches [2] ) && is_numeric($date_matches [2]) && $date_matches [2] != 0 ) {
								
								$end_timestamp_no_timezoned = $date_matches [2];
								$end_date_no_timezoned = gmdate ( 'Y-m-d H:i:s', $end_timestamp_no_timezoned ); // 2020-11-08 20:00:00
								$end_date_timezoned = $end_date_no_timezoned ; //ini 2020-11-08 20:00:00
								
								if(isset($tz_matches[1]) && trim($tz_matches[1]) != '' ){
									//found timezone EST
									$end_date_timezoned = $end_date_timezoned . ' ' .$found_timezone ; //2020-11-08 20:00:00 EST
								}
								
								$end_date_utc = strtotime($end_date_timezoned); //1604883600
								  
								$ret ['end_time'] = get_date_from_gmt ( gmdate ( 'Y-m-d H:i:s',$end_date_utc ) );
								$ret ['end_time_timestamp'] = strtotime ( $ret ['end_time'] );
							}
						}
						
						
						// event_place":{"__typename":"Page","name":"M&K Stationery","
						$place_name = '';
						preg_match ( '!event_place":{"__typename":".*?","name":"(.*?)","!', $exec2, $place_matches );
						
						
						$place_name = '';
						if (isset ( $place_matches [1] )) {
							$place_name = wp_automatic_fix_json_part( $place_matches [1] ) ;
						}  
						
						$ret ['place_name'] = $place_name;
						
						// "address":{"street":"(.*?)"},"city":{"contextual_name":"(.*?)",
						$place_address = '';
						
						preg_match ( '!"address":{"street":"(.*?)"},"city":{"contextual_name":"(.*?)",!', $exec2, $address_matches );
						
						if (isset ( $address_matches [1] ) && trim ( $address_matches [1] ) != '') {
							$place_address = wp_automatic_fix_json_part($address_matches [1]) . '<br>' . wp_automatic_fix_json_part($address_matches [2]) ;
						}else{
							
							//"event_place":{"__typename":"FreeformPlace","contextual_name":"5th St, Sandton, 2196, South Africa",
							 
						}
						
						$ret ['place_address'] = $place_address;
						
						//place street "address":{"street":"Tanta-Elhelw st. behind Kassem Amein"}
						preg_match ( '{"street":"(.*?)"}', $exec2, $street_matches );
						$ret ['place_street'] = (isset($street_matches[1]) && trim($street_matches[1]) != '' ) ?  wp_automatic_fix_json_part($street_matches[1]) : '';

						//"city":{"contextual_name":"Tanta"
						preg_match ( '!"city":{"contextual_name":"(.*?)"!', $exec2, $city_matches );
						$ret ['place_city'] = (isset($city_matches[1]) && trim($city_matches[1]) != '' ) ? wp_automatic_fix_json_part( $city_matches[1]) : '';
						
						//"event_connected_users_going":{"count":1}
						preg_match ( '!"event_connected_users_going":{"count":(.*?)}!', $exec2, $going_matches );
						$ret ['going_count'] = (isset($going_matches[1]) && trim($going_matches[1]) != '' ) ?  $going_matches[1] : '';
						
						//"event_connected_users_interested":{"count":2}
						preg_match ( '!"event_connected_users_interested":{"count":(.*?)}!', $exec2, $going_matches );
						$ret ['interested_count'] = (isset($going_matches[1]) && trim($going_matches[1]) != '' ) ?  $going_matches[1] : '';
						
						//no more available tags
						$ret ['place_email'] = '';
 						$ret ['place_phone'] = '';
 						$ret ['place_zip'] = '';
 						$ret ['place_country'] = '';
						
						 
						
					} else {
						
						// "i18n_reaction_count":"1.5K",
						$item_likes = 0;
						
						preg_match ( '{"i18n_reaction_count":"(.*?)"}s', $exec2, $likes_count_matches );
						 
						if (isset ( $likes_count_matches [1] ) ) {
							$item_likes = str_replace ( ',', '', $likes_count_matches [1] );
						}
						
						$ret ['original_title'] = $title;
						$ret ['original_link'] = $url;
						$ret ['matched_content'] = $content;
						$ret ['original_date'] = $wpdate;
						
						 
						// get from info
						//"actors":[{"__typename":"User","name":"Mohamed Moawad",
						//"actors":[{"__typename":"Page","name":"CNN"
						preg_match ( '!"actors":\[{"__typename":"\w*?","name":"(.*?)",!', $exec2, $from_matches );
						
						$from_name = '';
						if(isset($from_matches[1]) && trim($from_matches[1]) != '' ){
							$from_name = wp_automatic_fix_json_part( $from_matches[1] );
						}else{
							
							////og:title" content="CNN"
							preg_match('!og:title" content="(.*?)"!' , $exec2 , $from_matches );
							
							if(!empty($from_matches[1]))
								$from_name = $from_matches[1];
						}
						  
						
						$ret ['from_name'] = $from_name;
						
						// from ID actor_id%22%3A100026923221457%
						$sharer_id = $cg_fb_page_id;
						if (stristr ( $exec2, 'sharer_id' )) {
							
							preg_match ( '{sharer_id=(.*?)&}s', $exec2, $from_matches );
							
							if (isset ( $from_matches [1] ) && trim ( $from_matches [1] ) != '') {
								$sharer_id = $from_matches [1];
							}
							
							// from_name ownerName:"Gamal M. Elkomy"
							preg_match ( '{ownerName:"(.*?)"}s', $exec2, $from_name_matches );
							if (isset ( $from_name_matches [1] ) && trim ( $from_name_matches [1] ) != '') {
								$ret ['from_name'] = $from_name_matches [1];
							}
						} else {
							
							// closed group content_owner_id_new":"100002936112728"
							preg_match ( '{content_owner_id_new.*?(\d+)}s', $item, $from_matches2 );
							
							if (isset ( $from_matches2 [1] ) && trim ( $from_matches2 [1] ) != '') {
								$sharer_id = $from_matches2 [1];
							}
						}
						
				 
						
						$ret ['from_id'] = $sharer_id;
						$ret ['from_url'] = 'https://facebook.com/' . $sharer_id;
						$ret ['from_thumbnail'] = 'https://graph.facebook.com/' . $sharer_id . '/picture?type=large';
						
						$ret ['post_id'] = $item_id;
						$ret ['post_id_single'] = $single_id;
						$ret ['image_src'] = $imgsrc;
						$ret ['likes_count'] = $item_likes;
						
						// original url of the shared post
						if ($type == 'link') {
							$ret ['external_url'] = $link;
						} else {
							$ret ['external_url'] = '';
						}
						
						// shares
						if (! is_numeric ( $shares_count ))
							$shares_count = 0;
						$ret ['shares_count'] = $shares_count;
						
						// no title
						if (trim ( $title ) == '')
							$ret ['original_title'] = '(notitle)';
						
						// embed code
						$ret ['post_embed'] = '<div id="fb-root"></div><script>
(function(d, s, id) {
    var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id))
        return;
    js = d.createElement(s);
    js.id = id;
    js.src = "//connect.facebook.net/en_US/all.js#xfbml=1";
    fjs.parentNode.insertBefore(js, fjs);
}(document, \'script\', \'facebook-jssdk\'));
</script><div class="fb-post" data-href="https://www.facebook.com/' . $cg_fb_page_id . '/posts/' . $single_id . '"></div>';
						
						// hashtags >#support</a>
						// echo $ret['matched_content'];
						
						if (stristr ( $ret ['matched_content'], '#' )) {
							
							preg_match_all ( '{>#(.*?)</a>}', $ret ['matched_content'], $hash_matchs );
							$hash_matchs = $hash_matchs [1];
							$hash_tags = implode ( ',', $hash_matchs );
							$ret ['item_tags'] = strip_tags ( $hash_tags );
						} else {
							$ret ['item_tags'] = '';
						}
					}
					
					if ($cg_fb_source == 'group') {
						
						$ret ['source_link'] = 'https://www.facebook.com/groups/' . $cg_fb_page_id . '/permalink/' . $single_id;
					} else {
						
						$ret ['source_link'] = $ret ['original_link'];
					}
					
					// comments {"comments":[{"body..... }],"pinnedcomments
					//before_count":0,"count":44,"edges":[{"node":
					 
					if (in_array ( 'OPT_FB_COMMENT', $camp_opt )) {
						
						$correct_comment_part = ''; // ini
						
						if ( stristr ( $exec2_raw, '"edges":[{"node":' ) ) {
							
							$all_the_comments = array ();
							
							echo '<br>Comments found to extract using edges context';
							
							// edges:[{node .........,cursor:"AQHRJpdJxCiEwoRdtdydeisnvpjtTxJ9wk1_OAMIFGwB5ylEL1Wl2fidayVd8mErMcU2Sul_ftHtFzFoCRI_3cFGSg"}]
							preg_match_all ( '{"edges":\[\{"node":\{"id"[^<]*,"cursor":".*?"\}\]}s', $exec2_raw, $comment_part_matches );
							 
							$comment_part_matches = $comment_part_matches [0];
							 
							if (isset ( $comment_part_matches [0] ) && trim ( $comment_part_matches [0] ) != '') {
								
								$correct_comment_part = '{' . $comment_part_matches [0] . '}';
								//	$correct_comment_part = preg_replace ( '/(\s*?{\s*?|\s*?,\s*?)([\'"])?([a-zA-Z0-9_]+)([\'"])?:/', '$1"$3":', $correct_comment_part );
								
								$comments_json = (json_decode ( $correct_comment_part ));
								
								 
								if (isset ( $comments_json->edges )) {
									$all_comments = $comments_json->edges;
									
									foreach ( $all_comments as $single_comment ) {
										
										$a_comment = array ();
										
										$single_comment = $single_comment->node;
										
										if (stristr ( $single_comment->url, $single_id )) {
											
											$commment_txt = $single_comment->url;
											
											$a_comment ['text'] = $single_comment->body->text;
											
											// image content
											if (in_array ( 'OPT_FB_COMMENT_IMG_CNT', $camp_opt )) {
												if (isset ( $single_comment->attachments [0]->media->image->uri )) {
													
													$imgURI = $single_comment->attachments [0]->media->image->uri;
													
													if (trim ( $imgURI ) != '') {
														$a_comment ['text'] .= '<br><img src="' . $imgURI . '"/>';
													}
												}
											}
											
											$a_comment ['time'] = $single_comment->created_time;
											$a_comment ['author_id'] = $single_comment->author->id;
											$a_comment ['author_name'] = $single_comment->author->name;
											
											if (trim ( $a_comment ['text'] ) != '')
												$all_the_comments [] = $a_comment;
										}
									}
									
									if (count ( $all_the_comments ) > 0)
										$ret ['comments'] = array_reverse ( $all_the_comments );
								}
							} else {
								echo '<br>Can not extract the Json part for the comments';
							}
						}
					} // comments option active
					  
					// fix hashtags
					if (stristr ( $ret ['matched_content'], 'hash' )) {
						
						// <span class="es et">#</span><span class="eu">emergencias</span>
						$ret ['matched_content'] = preg_replace ( '{>#</span><span class="[^<]*?">}s', '>#', $ret ['matched_content'] );
					}
					
					$ret ['original_date_timestamp'] = strtotime ( $ret ['original_date'] );
					
					 
					return $ret;
				} // endforeach
				
				echo '<br>End of available items reached....';
				
				if (in_array ( 'OPT_FB_CACHE', $camp_opt )) {
					
					echo '<br>Deleting cache as no more valid items found...';
					delete_post_meta ( $camp->camp_id, 'wp_automatic_cache' );
					
					// Setting next page url
					$nextPageUrl = '';
					
					 
					 
					if ( stristr (   $list_exec_raw , '"see_more_cards_id\",\"href\"' ) || stristr ( $list_exec_raw, 'm_more_item\",\"href' ) || stristr ( $list_exec_raw, 'profile\\\\\\/timeline\\\\\\/stream' ) || (stristr ( $exec, 'serialized_cursor' ) && $cg_fb_from == 'events') || ($cg_fb_source == 'group' && stristr($list_exec_raw , "/groups/$cg_fb_page_id?bac" ) ) ) {
					
						if( $cg_fb_source == 'group' && stristr($list_exec_raw , "/groups/$cg_fb_page_id?bac" )){
						 
							//<a href="/groups/168889943173228?bacr=1612364339%3A3904622029599982%3A3904622029599982%2C0%2C0%3A7%3AKw%3D%3D&amp;multi_permalinks&amp;refid=18"><span>See Mor
							echo '<br>Pagination case#0 groups';
							preg_match ( '{<a href="(/groups/' . $cg_fb_page_id . '\?bacr.*?)"><span>}s', $exec, $next_page_matches );
							 
					}elseif (( stristr ( $exec, 'serialized_cursor' ) && $cg_fb_from == 'events')) {

								// <a href="/DuplexRooftopVenuePrague?v=events&amp;is_past&amp;serialized_cursor=AQHRbC1iSbVP7ovwPg2wTaw5UAntzEpVELbhs73QLHTkfFLA6biRLa8kZiUjo0VJWvnM8-1mPKTORyPdaim_hkPYNw&amp;has_more=1"><span>See More Events
							echo '<br>Pagination case#1 events';
							preg_match ( '{<a href="([^"]*?serialized_cursor.*?)"><span>}s', $exec, $next_page_matches );
							
						} elseif (  stristr ( $list_exec_raw, 'm_more_item\",\"href' )  ) {
							
							// "m_more_item\",\"href\":\"\\\/groups\
							echo '<br>Pagination case#2 group';
							preg_match ( '{"m_more_item.",."href.":."(.*?)"}s', $list_exec_raw, $next_page_matches );
							
							$possible_pagination_url = wp_automatic_fix_json_part(stripslashes($next_page_matches[1] ));
							
							if(trim($possible_pagination_url) != ''){
								$next_page_matches[1] = $possible_pagination_url;
							}
							
							
						} elseif (stristr (   $list_exec_raw , 'see_more_cards_id\",\"href\"' )) { 
							
							
							
							//_more_cards_id","href":"\/page_c
							preg_match ( '!"(.{4}page_content_list_view.*?)"!s', $list_exec_raw, $next_page_matches );
							
							$possible_pagination_url = wp_automatic_fix_json_part(stripslashes($next_page_matches[1] ));
							
							if(trim($possible_pagination_url) != ''){
								$next_page_matches[1] = $possible_pagination_url;
							}
							
							echo '<br>Pagination case#3 page';
							 
						} else {
							
							echo '<br>Pagination case#4 profile';
							
							//"href\":\"\\\/profile\\\/timeline\\\/stream\\\/?cursor=AQHRvsJLrnRauzCuDLYMaVWnQGpS22H16CqnVUO2rpEfWQXQdlXHgVAlEbRrzaTqGt4znEwetPnd5AKZEIxrGxaJB0zWtkwEFuEe6Jl4x0x_hTw5d_4THMtxEwdAKq6Sncy3&profile_id=837764889&replace_id=u_0_0\"
							preg_match ( '{"href.":."(.{4}profile.{4}timeline.{4}stream.{4}\?cursor\=.*?)"}s', $list_exec_raw, $next_page_matches );
						
							$possible_pagination_url = wp_automatic_fix_json_part(stripslashes($next_page_matches[1] ));
							
							if(trim($possible_pagination_url) != ''){
								$next_page_matches[1] = $possible_pagination_url;
							}
							
							
						}
						
						if ($foundOldPost) {
							echo '<br>Found old posts on current list of posts, lets set the pointer to first page again';
							delete_post_meta ( $camp->camp_id, 'nextPageUrl' );
							delete_post_meta ( $camp->camp_id, 'wp_automatic_fb_checked_years' );
						} elseif ( isset ( $next_page_matches [1] ) && trim( $next_page_matches [1] ) != '' ) {
							
							// show more link exists
							if($cg_fb_from == 'events' || $cg_fb_source == 'group' ){
								$nextPageUrl = "https://mbasic.facebook.com" . $next_page_matches [1];
							}else{
								$nextPageUrl = "https://m.facebook.com" . $next_page_matches [1];
							}
							
							echo '<br>Next page:' . $nextPageUrl;
							
							update_post_meta ( $camp->camp_id, 'nextPageUrl', $nextPageUrl );
 
						} else {
							// no next
							echo '<br>No next page available';
							delete_post_meta ( $camp->camp_id, 'nextPageUrl' );
							delete_post_meta ( $camp->camp_id, 'wp_automatic_fb_checked_years' );
						}
					} else {
						
						echo '<br>No next page available';
						delete_post_meta ( $camp->camp_id, 'nextPageUrl' );
						delete_post_meta ( $camp->camp_id, 'wp_automatic_fb_checked_years' );
					}
				}
			}
		} // trim pageid
	}
	
	function wp_automatic_fb_email_of_failure(){
		$wp_automatic_fb_email = get_option('wp_automatic_fb_email', '' );
		
		
	}
	
}