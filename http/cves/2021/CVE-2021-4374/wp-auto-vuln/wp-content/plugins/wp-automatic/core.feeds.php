<?php

/*
 * ---* feed process camp ---
 */

// Main Class
require_once 'core.php';
class WpAutomaticFeeds extends wp_automatic {
	function feeds_get_post($camp) {
		
		// feeds
		$feeds = $camp->feeds;
		$feeds = explode ( "\n", $feeds );
		
		$msg = "Processing " . count ( $feeds ) . " Feeds for this campaign " . get_the_title ( $camp->camp_id );
		echo '<br>' . $msg;
		
		if (count ( $feeds ) > 0) {
			$this->log ( 'Process Feeds', $msg );
		}
		
		$n = 0;
		$max = 3;
		
		foreach ( $feeds as $feed ) {
			
			if ($n == 3) {
				$max = ( int ) trim ( get_option ( 'wp_automatic_feed_max', 3 ) );
			}
			
			if ($n == $max) {
				echo '<br>Processed ' . $max . ' feeds with nothing to find, will die now, Please activate rotating if you are not.';
				break;
			}
			
			if (trim ( $feed ) != '') {
				
				// fix //feeds
				if (! stristr ( $feed, 'http' ) && preg_match ( '{^//}', trim ( $feed ) )) {
					$feed = 'http:' . $feed;
				}
				
				// process feed
				echo '<b><br><br>Processing Feed:</b> ' . $feed;
				
				update_post_meta ( $camp->camp_id, 'last_feed', trim ( $feed ) );
				
				$cont = $this->feed_process_link ( $feed, $camp );
				$n ++;
				
				if (isset ( $cont ['cont'] ) && trim ( $cont ['cont'] ) != '') {
					return $cont;
				}
			}
		}
		
		return false;
	}
	
	/*
	 * ---* processing feed link ---
	 */
	function feed_process_link($feed, $camp) {
		
		// detect encoding mbstring
		if (! function_exists ( 'mb_detect_encoding' )) {
			echo '<br><span style="color:red;">mbstring PHP extension that is responsible for text encoding is not installed,Install it. You may get encoding problems.</span>';
		}
		
		// php-xml check
		if (! function_exists ( 'simplexml_load_string' )) {
			echo '<br><span style="color:red;">php-xml PHP extension that is responsible for parsing XML is not installed. Please <a href="https://stackoverflow.com/questions/38793676/php-xml-extension-not-installed">install it</a> and try again</span>';
		}
		
		// ini
		if (stristr ( $camp->camp_general, 'a:' ))
			$camp->camp_general = base64_encode ( $camp->camp_general );
		$camp_general = unserialize ( base64_decode ( $camp->camp_general ) );
		$camp_general = array_map ( 'wp_automatic_stripslashes', $camp_general );
		$camp_opt = unserialize ( $camp->camp_options );
		
		// Feed extraction method old format adaption
		if (in_array ( 'OPT_FEED_CUSTOM_R', $camp_opt )) {
			$camp_general ['cg_feed_custom_regex'] = array (
					$camp_general ['cg_feed_custom_regex'],
					$camp_general ['cg_feed_custom_regex2'] 
			);
		}
		
		if (in_array ( 'OPT_FEED_CUSTOM', $camp_opt )) {
			
			$camp_general ['cg_feed_extraction_method'] = 'css';
			
			$camp_general ['cg_custom_selector'] = array (
					$camp_general ['cg_custom_selector'],
					$camp_general ['cg_custom_selector2'],
					$camp_general ['cg_custom_selector3'] 
			);
			$camp_general ['cg_feed_custom_id'] = array (
					$camp_general ['cg_feed_custom_id'],
					$camp_general ['cg_feed_custom_id2'],
					$camp_general ['cg_feed_custom_id3'] 
			);
			
			$cg_feed_css_size = array ();
			$cg_feed_css_size [] = in_array ( 'OPT_SELECTOR_SINGLE', $camp_options ) ? 'single' : 'all';
			$cg_feed_css_size [] = in_array ( 'OPT_SELECTOR_SINGLE2', $camp_options ) ? 'single' : 'all';
			$cg_feed_css_size [] = in_array ( 'OPT_SELECTOR_SINGLE3', $camp_options ) ? 'single' : 'all';
			$camp_general ['cg_feed_css_size'] = $cg_feed_css_size;
			
			$cg_feed_css_wrap = array ();
			$cg_feed_css_wrap [] = in_array ( 'OPT_SELECTOR_INNER', $camp_options ) ? 'inner' : 'outer';
			$cg_feed_css_wrap [] = in_array ( 'OPT_SELECTOR_INNER2', $camp_options ) ? 'inner' : 'outer';
			$cg_feed_css_wrap [] = in_array ( 'OPT_SELECTOR_INNER3', $camp_options ) ? 'inner' : 'outer';
			$camp_general ['cg_feed_css_wrap'] = $cg_feed_css_wrap;
		}
		
		if (isset ( $camp_general ['cg_feed_extraction_method'] )) {
			switch ($camp_general ['cg_feed_extraction_method']) {
				
				case 'summary' :
					$camp_opt [] = 'OPT_SUMARRY_FEED';
					break;
				
				case 'css' :
					$camp_opt [] = 'OPT_FEED_CUSTOM';
					break;
				
				case 'auto' :
					$camp_opt [] = 'OPT_FULL_FEED';
					break;
				
				case 'regex' :
					$camp_opt [] = 'OPT_FEED_CUSTOM_R';
					break;
				
				case 'visual' :
					$camp_opt [] = 'OPT_FEED_CUSTOM';
					$camp_general ['cg_feed_extraction_method'] = 'css';
					
					$camp_general ['cg_feed_custom_id'] = $camp_general ['cg_feed_visual'];
					
					$cg_feed_css_size = array ();
					$cg_feed_css_wrap = array ();
					$cg_custom_selector = array ();
					
					foreach ( $camp_general ['cg_feed_visual'] as $singleVisual ) {
						$cg_feed_css_size [] = 'single';
						$cg_feed_css_wrap [] = 'outer';
						$cg_custom_selector [] = 'xpath';
					}
					
					$camp_general ['cg_feed_css_size'] = $cg_feed_css_size;
					$camp_general ['cg_feed_css_wrap'] = $cg_feed_css_wrap;
					$camp_general ['cg_custom_selector'] = $cg_custom_selector;
					
					break;
			}
		}
		
		if (in_array ( 'OPT_STRIP_CSS', $camp_opt ) && ! is_array ( $camp_general ['cg_feed_custom_strip_id'] )) {
			
			$cg_feed_custom_strip_id [] = $camp_general ['cg_feed_custom_strip_id'];
			$cg_feed_custom_strip_id [] = $camp_general ['cg_feed_custom_strip_id2'];
			$cg_feed_custom_strip_id [] = $camp_general ['cg_feed_custom_strip_id3'];
			
			$cg_custom_strip_selector [] = $camp_general ['cg_custom_strip_selector'];
			$cg_custom_strip_selector [] = $camp_general ['cg_custom_strip_selector2'];
			$cg_custom_strip_selector [] = $camp_general ['cg_custom_strip_selector3'];
			
			$cg_feed_custom_strip_id = array_filter ( $cg_feed_custom_strip_id );
			$cg_custom_strip_selector = array_filter ( $cg_custom_strip_selector );
			
			$camp_general ['cg_feed_custom_strip_id'] = $cg_feed_custom_strip_id;
			$camp_general ['cg_custom_strip_selector'] = $cg_custom_strip_selector;
		}
		
		$feedMd5 = md5 ( $feed );
		$isItemsEndReached = get_post_meta ( $camp->camp_id, $feedMd5 . '_isItemsEndReached', 1 );
		$lastProcessedFeedUrl = get_post_meta ( $camp->camp_id, $feedMd5 . '_lastProcessedFeedUrl', 1 );
		$lastFirstFeedUrl = get_post_meta ( $camp->camp_id, $feedMd5 . '_lastFirstFeedUrl', 1 );
		
		// check last time adition
		$feed = trim ( $feed );
		$myfeed = addslashes ( $feed );
		
		// removed @ v3.24
		/*
		 * $query = "select * from {$this->wp_prefix}automatic_feeds_list where feed='$myfeed' and camp_id = '$camp->camp_id' limit 1";
		 * $feeds = $this->db->get_results ( $query );
		 * $feed_o = $feeds [0];
		 */
		
		// report processed feed
		$this->log ( 'Process Feed', '<a href="' . $feed . '">' . $feed . '</a>' );
		
		// If force feed
		
		if (in_array ( 'OPT_FEED_FORCE', $camp_opt )) {
			
			if (! function_exists ( 'wp_automatic_force_feed' )) {
				add_action ( 'wp_feed_options', 'wp_automatic_force_feed', 10, 1 );
				function wp_automatic_force_feed($feed) {
					$feed->force_feed ( true );
					
				}
			}
		}
		
		//disable order by date for multi-page scraper 
		if(isset($camp->camp_sub_type) &&  $camp->camp_sub_type == 'Multi'){
			add_action ( 'wp_feed_options', 'wp_automatic_order_feed', 10, 1 );
		
			if(! function_exists('wp_automatic_order_feed')){
			
				function wp_automatic_order_feed($feed) {
					$feed->enable_order_by_date(false);
				}
				
			}
		
		}
		
		// loading SimplePie
		include_once (ABSPATH . WPINC . '/feed.php');
		
		// Add action to fix the problem of curl transfer closed without complete data
		// Wrong feed length fix
		if (! function_exists ( 'wp_automatic_setup_curl_options' )) {
			// feed timeout
			function wp_automatic_setup_curl_options($curl) {
				if (is_resource ( $curl )) {
					curl_setopt ( $curl, CURLOPT_HTTPHEADER, array (
							'Expect:' 
					) );
				}
			}
		}
		
		if (in_array ( 'OPT_FEED_NOLENGTH', $camp_opt )) {
			echo ' expecting none';
			add_action ( 'http_api_curl', 'wp_automatic_setup_curl_options' );
		}
		
		if (! function_exists ( 'wp_automatic_wp_feed_options' )) {
			function wp_automatic_wp_feed_options($args) {
				$args->set_useragent ( 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/41.0.2272.76 ' );
			}
			add_action ( 'wp_feed_options', 'wp_automatic_wp_feed_options' );
		}
		
		// Trim returned feed content because some feed add empty spaces before feed content
		if (! function_exists ( 'wp_automatic_trim_feed_content' )) {
			function wp_automatic_trim_feed_content($args) {
				$args ['body'] = trim ( $args ['body'] );
				
				// $args['body'] = preg_replace('{article/(\d+?)/}' , "article/$1wpp/", trim($args['body']) ) ;
				
				return $args;
			}
		}
		
		add_filter ( 'http_response', 'wp_automatic_trim_feed_content' );
		
		if (stristr ( $feed, 'news.google' ) && ! function_exists ( 'wp_automatic_feed_options' )) {
			
			// Fix Google news image stripped
			echo '<br>Google news feed found, disabling sanitization...';
			function wp_automatic_feed_options($feed) {
				$feed->set_sanitize_class ( 'SimplePie_Sanitize' );
				$feed->sanitize = new SimplePie_Sanitize ();
			}
			add_action ( 'wp_feed_options', 'wp_automatic_feed_options' );
		}
		
		// If proxified download the feed content to a test file for
		// exclude multipage scraper as it is already a local feed /wp-content/uploads/277d7822fe5662e66d660a42eaae4910_837.xml
		$localFeed = '';
		if ($this->isProxified && ! (stristr ( $feed, '.xml' ) && stristr ( $feed, 'wp-content' ))) {
			// downloading the feed content
			// print_r( $this->download_file
			$downloadedFileUrl = $this->download_file ( $feed, '.html' );
			
			if (trim ( $downloadedFileUrl ) != '') {
				echo '<br>Feed downloaded using a proxy ' . $downloadedFileUrl;
				$localFeed = $downloadedFileUrl . '?key=' . md5 ( trim ( $feed ) );
			}
		}
		
		// fix ssl verification
		add_filter ( 'https_ssl_verify', '__return_false' );
		
		// Fetch feed content
		if (trim ( $localFeed ) == '') {
			$rss = fetch_feed ( stripslashes ( $feed ) );
		} else {
			echo '<br>Loaded locally';
			$rss = fetch_feed ( $localFeed );
		}
		
		// Remove added filter
		remove_filter ( 'http_response', 'wp_automatic_trim_feed_content' );
		
		if (! is_wp_error ( $rss )) { // Checks that the object is created correctly
			
			if (trim ( $rss->raw_data ) == '') {
				echo '<br>Feed was loaded from cache';
			} else {
				echo '<br>Feed was freshly loaded from the source';
			}
			
			// Figure out how many total items there are, but limit it to 5.
			$maxitems = @$rss->get_item_quantity ();
			
			echo '<br>Feed contains ' . $maxitems . ' posts';
			
			// Build an array of all the items, starting with element 0 (first element).
			$rss_items = $rss->get_items ( 0, $maxitems );
			
			// feed name
			$res ['feed_name'] = $rss->get_title ();
			
			// remove the expect again as it makes jetpack publicize to not work
			remove_action ( 'http_api_curl', 'wp_automatic_setup_curl_options' );
		} else {
			$error_string = $rss->get_error_message ();
			echo '<br><strong>Error:</strong>' . $error_string;
		}
		
		if (! isset ( $maxitems ) || $maxitems == 0)
			return false;
		else 
		
		// reverse order if exists
		if (in_array ( 'OPT_FEED_REVERSE', $camp_opt )) {
			echo '<br>Reversing order';
			$rss_items = array_reverse ( $rss_items );
		}
		
		// Loop through each feed item and display each item as a hyperlink.
		$i = 0;
		$i2 = 0; // refer to items number in feed
		
		foreach ( $rss_items as $item ) :
			
			$url = esc_url ( $item->get_permalink () );
			
			if (trim ( $url ) == '') {
				echo '<br>Feed item does not contain a URL, what a bad feed, will use a fake URL';
				$url = md5 ( $item->get_title () );
			}
			
			$original_url = $url; // used for exclusion because the effective_url may change below
			                      
			// google news links correction
			if (stristr ( $url, 'news.google' ) && stristr ( $url, 'url=' )) {
				$urlParts = explode ( 'url=', $url );
				$correctUrl = $urlParts [1];
				$url = $correctUrl;
			}
			
			// Google alerts links correction
			if (stristr ( $feed, 'alerts/feeds' ) && stristr ( $feed, 'google' )) {
				preg_match ( '{url\=(.*?)[&]}', $url, $urlMatches );
				$correctUrl = $urlMatches [1];
				
				if (trim ( $correctUrl ) != '') {
					$url = $correctUrl;
				}
			}
			
			// check if no new links: the last first url is the same
			if ($i2 == 0) {
				
				if ($isItemsEndReached == 'yes') {
					
					// Last time there were no new links check if the case didn't change
					if ($lastFirstFeedUrl == md5 ( $url )) {
						
						// still no new links stop checking now
						echo '<br>First url in the feed:' . $url;
						echo '<br>This link was the same as the last time we did not find new links so ... skipping till new posts get added <a href="#" class="wp_automatic_ajax" data-action="wp_automatic_ajax" data-function="forget_lastFirstFeedUrl" data-data="' . $feedMd5 . '" data-camp="' . $camp->camp_id . '" >Forget this fact Now</a>.';
						
						// delete transient cache
						delete_transient ( 'feed_' . $feedMd5 );
						
						return false;
					} else {
						// new links found remove the isItemsEndReached flag
						delete_post_meta ( $camp->camp_id, $feedMd5 . '_isItemsEndReached' );
					}
				}
			}
			
			// Record first feed url
			if ($i2 == 0) {
				update_post_meta ( $camp->camp_id, $feedMd5 . '_lastFirstFeedUrl', md5 ( $url ) );
			}
			
			// one more link, increase index2
			$i2 ++;
			
			if (trim ( $url ) == '') {
				echo '<br>item have no url skipping';
				continue;
			}
			
			// current post url
			echo '<br>post URL: ' . $url;
			
			// post date
			$wpdate = $item->get_date ( "" );
			
			if( trim($wpdate) != '' ) echo '<br>- Published: ' . $wpdate;
			
			if(trim($wpdate) != ''){
				
				//IST fix timesofindia
				$wpdate = str_replace(' IST', ' +05:30', $wpdate);
				
				$wpdate = date ( "Y-m-d H:i:s", strtotime($wpdate)) ;
				echo ' == ' . $wpdate . ' GMT';
			}
			
			$publish_date = get_date_from_gmt ( $wpdate );
			
			 
			// post categories
			$cats = ($item->get_categories ());
			
			// separate categories with commas
			$cat_str = '';
			if (isset ( $cats )) {
				foreach ( $cats as $cat ) {
					if (trim ( $cat_str ) != '')
						$cat_str .= ',';
					$cat_str .= $cat->term;
				}
			}
			
			// fix empty titles
			if (trim ( $item->get_title () ) == '') {
				echo '<--Empty title skipping';
				continue;
			}
			
			// &# encoded chars
			if (stristr ( $url, '&#' )) {
				$url = html_entity_decode ( $url );
			}
			
			// check if execluded link due to exact match does not exists
			if ($this->is_execluded ( $camp->camp_id, $url )) {
				echo '<-- Excluded link';
				continue;
			}
			
			 
			
			// check if older than minimum date
			if ( $wpdate != '' && $this->is_link_old ( $camp->camp_id, strtotime ( $wpdate ) )) {
				echo '<--old post execluding...';
				continue;
			}
			
			// check media images
			unset ( $media_image_url );
			$enclosures = $item->get_enclosures ();
			
			$i = 0;
			foreach ( $enclosures as $enclosure ) {
				
				if (trim ( $enclosure->type ) != '') {
					
					$enclosure_link = $enclosure->link;
					
					$res ['enclosure_link_' . $i] = $enclosure_link;
					
					if (isset ( $enclosure->type ) && stristr ( $enclosure->type, 'image' ) && isset ( $enclosure->link )) {
						$media_image_url = $enclosure->link;
					}
				}
				$i ++;
			}
			
			// Duplicate check
			if (! $this->is_duplicate ( $url )) {
				
				echo '<-- new link';
				
				$title = strip_tags ( $item->get_title () );
				
				// fix &apos;
				$title = str_replace ( '&amp;apos;', "'", $title );
				$title = str_replace ( '&apos;', "'", $title );
				
				// check if there is a post published with the same title
				if (in_array ( 'OPT_FEED_TITLE_SKIP', $camp_opt )) {
					if ($this->is_title_duplicate ( $title, $camp->camp_post_type )) {
						echo '<-- duplicate title skipping..';
						continue;
					}
				}
				
				$i ++;
				
				 
				// post content
				$html = $item->get_content ();
				
				// fix single quotes
				$html = $this->convert_single_quotes ( $html );
				
				$postAuthor = $item->get_author ();
				@$authorName = $postAuthor->name;
				
				if (trim ( $authorName ) == '') {
					@$authorName = $postAuthor->email;
				}
				
				if (trim ( $authorName ) == '') {
					@$authorName = $postAuthor->link;
				}
				
				$res ['author'] = $authorName;
				@$res ['author_link'] = $postAuthor->link;
				
				// source domain
				$res ['source_domain'] = $parse_url = parse_url ( $url, PHP_URL_HOST );
				
				// encoded URL
				$res ['source_url_encoded'] = urlencode ( $url );
				
				// If empty content make content = title
				if (trim ( $html ) == '') {
					if (trim ( $title ) != '')
						$html = $title;
				}
				
				// loging the feeds
				$md5 = md5 ( $url );
				
				// if not image escape it
				$res ['cont'] = $html;
				$res ['original_content'] = $html;
				$res ['title'] = $title;
				$res ['original_title'] = $title;
				$res ['matched_content'] = $html;
				$res ['source_link'] = $url;
				$res ['publish_date'] = $publish_date;
				$res ['wpdate'] = $wpdate;
				$res ['cats'] = $cat_str;
				$res ['tags'] = '';
				$res ['enclosure_link'] = isset ( $enclosure_link ) ? $enclosure_link : '';
				
				// custom atributes
				$arr = array ();
				$arrValues = array_values ( $item->data ['child'] );
				
				foreach ( $arrValues as $arrValue ) {
					if (is_array ( $arrValue )) {
						$arr = array_merge ( $arr, $arrValue );
					}
				}
				
				$res ['attributes'] = $arr;
				 
				$og_title_enabled = in_array ( 'OPT_FEED_OG_TTL', $camp_opt ) || (isset ( $camp_general ['cg_ml_ttl_method'] ) && trim ( $camp_general ['cg_ml_ttl_method'] ) != 'auto');
				
				// original post content
				if (in_array ( 'OPT_FULL_FEED', $camp_opt ) || in_array ( 'OPT_FEED_CUSTOM', $camp_opt ) || in_array ( 'OPT_FEED_CUSTOM_R', $camp_opt ) || in_array ( 'OPT_ORIGINAL_META', $camp_opt ) || in_array ( 'OPT_ORIGINAL_CATS', $camp_opt ) || in_array ( 'OPT_ORIGINAL_TAGS', $camp_opt ) || in_array ( 'OPT_ORIGINAL_AUTHOR', $camp_opt ) || in_array ( 'OPT_FEED_PTF', $camp_opt ) || in_array ( 'OPT_FEEDS_OG_IMG', $camp_opt ) || $og_title_enabled) {
					
					echo '<br>Loading original post content ...';
					
					// get content
					$x = 'error';
					curl_setopt ( $this->ch, CURLOPT_HTTPGET, 1 );
					curl_setopt ( $this->ch, CURLOPT_URL, trim ( html_entity_decode ( $url ) ) );
					
					// encoding
					if (in_array ( 'OPT_FEED_ENCODING', $camp_opt )) {
						echo '<br>Clearing encoding..';
						curl_setopt ( $this->ch, CURLOPT_ENCODING, "" );
					}
					
					// cookie
					$cg_sn_cookie = $camp_general ['cg_ml_cookie'];
					
					if (trim ( $cg_sn_cookie ) != '') {
						$headers [] = "Cookie: $cg_sn_cookie ";
						curl_setopt ( $this->ch, CURLOPT_HTTPHEADER, $headers );
					}
					
					// Source page html
					timer_start ();
					
					if(in_array('OPT_FEED_NORED' , $camp_opt)){
						$original_cont = curl_exec ( $this->ch );
					}else{
						$original_cont = $this->curl_exec_follow ( $this->ch );
					}
					
					 
					
					$x = curl_error ( $this->ch );
					
				  
					echo ' <--' . strlen ( $original_cont ) . ' chars returned in ' . timer_stop () . ' seconds';
					 
					 
					// converting encoding
					if (in_array ( 'OPT_FEED_CONVERT_ENC', $camp_opt )) {
						echo '<br>Converting encoding from ' . $camp_general ['cg_feed_encoding'] . ' to utf-8';
						$original_cont = iconv ( trim ( $camp_general ['cg_feed_encoding'] ) . '//IGNORE', "UTF-8//IGNORE", $original_cont );
					}
					
					// fix single quote used instead of regular quote
					$original_cont = $this->convert_single_quotes ( $original_cont );
					 
					
					// fix lazy loading
					if (in_array ( 'OPT_FEED_LAZY', $camp_opt )) {
						
						$cg_feed_lazy = trim ( $camp_general ['cg_feed_lazy'] );
						
						if ($cg_feed_lazy == '')
							$cg_feed_lazy = 'data-src';
						
						preg_match_all ( '{<img .*?>}s', $original_cont, $imgsMatchs );
						
						$imgsMatchs = $imgsMatchs [0];
						
						foreach ( $imgsMatchs as $imgMatch ) {
							
							if (stristr ( $imgMatch, $cg_feed_lazy )) {
								
								$newImg = $imgMatch;
								$newImg = str_replace ( ' src=', ' bad-src=', $newImg );
								$newImg = preg_replace ( '{ bad-src=[\'|"].*?["|\'] }s', ' ', $newImg );
								$newImg = str_replace ( $cg_feed_lazy, 'src', $newImg );
								$original_cont = str_replace ( $imgMatch, $newImg, $original_cont );
							}
						}
					} else {
						$original_cont = $this->lazy_loading_auto_fix ( $original_cont );
					}
					
					// fix images
					$original_cont = $this->fix_relative_paths ( $original_cont, $url );
				
				} // end-content-extraction
				  
				// date if not existing
				if (  (in_array ( 'OPT_ORIGINAL_TIME', $camp_opt ) || in_array( 'OPT_YT_DATE' , $camp_opt) ) && ! isset ( $res ['wpdate'] )  ) {
					echo '<br>Finidng original date... ';
					
					preg_match ( "!20\d{2}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d*)?.\d{2}:\d{2}!s", $original_cont, $date_matches );
					
					if (isset ( $date_matches [0] ) && trim ( $date_matches [0] ) != '') {
						echo '<-- Found:' . $date_matches [0];
						
						$res ['wpdate'] = $wpdate =  date ( "Y-m-d H:i:s", strtotime ( $date_matches [0] ) );
						
						// gmt date
						$publish_date = get_date_from_gmt ( $res ['wpdate'] );
						$res ['publish_date'] = $publish_date;
						
						// check if older than minimum date
						if ( $wpdate != '' && $this->is_link_old ( $camp->camp_id, strtotime ( $wpdate ) )) {
							echo '<--old post execluding...';
							$this->link_execlude ( $camp->camp_id, $original_url );
							continue;
						}
						
					} else {
						 echo '<- can not find';
					}
				}
				
				// FULL CONTENT
				if (in_array ( 'OPT_FULL_FEED', $camp_opt )) {
					
					// test url
					// $url ="http://news.jarm.com/view/75431";
					
					// get scripts
					$postponedScripts = array ();
					preg_match_all ( '{<script.*?</script>}s', $original_cont, $scriptMatchs );
					$scriptMatchs = $scriptMatchs [0];
					
					foreach ( $scriptMatchs as $singleScript ) {
						if (stristr ( $singleScript, 'connect.facebook' )) {
							$postponedScripts [] = $singleScript;
						}
						
						$original_cont = str_replace ( $singleScript, '', $original_cont );
					}
					
					$x = curl_error ( $this->ch );
					$url = curl_getinfo ( $this->ch, CURLINFO_EFFECTIVE_URL );
					
					// Redability instantiate
					require_once 'inc/wp_automatic_readability/wp_automatic_Readability.php';
					
					$wp_automatic_Readability = new wp_automatic_Readability ( $original_cont, $url );
					
					$wp_automatic_Readability->debug = false;
					$result = $wp_automatic_Readability->init ();
					
					if ($result) {
						
						// Redability title
						$title = $wp_automatic_Readability->getTitle ()->textContent;
						
						// Redability Content
						$content = $wp_automatic_Readability->getContent ()->innerHTML;
						
						// twitter embed fix
						if (stristr ( $content, 'twitter.com' ) && ! stristr ( $content, 'platform.twitter' )) {
							$content .= '<script async src="//platform.twitter.com/widgets.js" charset="utf-8"></script>';
						}
						
						// Remove wp_automatic_Readability attributes
						$content = preg_replace ( '{ wp_automatic_Readability\=".*?"}s', '', $content );
						
						// Fix iframe if exists
						preg_match_all ( '{<iframe[^<]*/>}s', $content, $ifrMatches );
						$iframesFound = $ifrMatches [0];
						
						foreach ( $iframesFound as $iframeFound ) {
							
							$correctIframe = str_replace ( '/>', '></iframe>', $iframeFound );
							$content = str_replace ( $iframeFound, $correctIframe, $content );
						}
						
						// add postponed scripts
						if (count ( $postponedScripts ) > 0) {
							$content .= implode ( '', $postponedScripts );
						}
						
						// Cleaning redability for better memory
						unset ( $wp_automatic_Readability );
						unset ( $result );
						
						// Check existence of title words in the content
						$title_arr = explode ( ' ', $title );
						
						$valid = '';
						$nocompare = array (
								'is',
								'Is',
								'the',
								'The',
								'this',
								'This',
								'and',
								'And',
								'or',
								'Or',
								'in',
								'In',
								'if',
								'IF',
								'a',
								'A',
								'|',
								'-' 
						);
						foreach ( $title_arr as $title_word ) {
							
							if (strlen ( $title_word ) > 3) {
								
								if (! in_array ( $title_word, $nocompare ) && preg_match ( '/\b' . preg_quote ( trim ( $title_word ), '/' ) . '\b/ui', $content )) {
									echo '<br>Title word ' . $title_word . ' exists on the content, approving.';
									
									// echo $content;
									$valid = 'yeah';
									break;
								} else {
									// echo '<br>Word '.$title_word .' does not exists';
								}
							}
						}
						
						if (trim ( $valid ) != '') {
							
							$res ['cont'] = $content;
							$res ['matched_content'] = $content;
							$res ['og_img'] = '';
							
							if (! in_array ( 'OPT_FEED_TITLE_NO', $camp_opt )) {
								$res ['title'] = $title;
								$res ['original_title'] = $title;
							}
							
							// let's find og:image may be the content we got has no image
							preg_match ( '{<meta[^<]*?property=["|\']og:image["|\'][^<]*?>}s', $html, $plain_og_matches );
							
							if (isset ( $plain_og_matches [0] ) && @stristr ( $plain_og_matches [0], 'og:image' )) {
								preg_match ( '{content=["|\'](.*?)["|\']}s', $plain_og_matches [0], $matches );
								$og_img = $matches [1];
								
								if (trim ( $og_img ) != '') {
									
									$res ['og_img'] = $og_img;
								}
							} // If og:image
						} else {
							echo '<br>Can not make sure if the returned content is the full content, using excerpt instead.';
						}
					} else {
						echo '<br>Looks like we couldn\'t find the full content. :( returning summary';
					}
					
					// Class or ID extraction
				} elseif (in_array ( 'OPT_FEED_CUSTOM', $camp_opt )) {
					
					// Load dom
					require_once 'inc/class.dom.php';
					$wpAutomaticDom = new wpAutomaticDom ( $original_cont );
					
					$cg_custom_selector = $camp_general ['cg_custom_selector'];
					$cg_feed_custom_id = $camp_general ['cg_feed_custom_id'];
					$cg_feed_custom_id = array_filter ( $cg_feed_custom_id );
					$cg_feed_css_size = $camp_general ['cg_feed_css_size'];
					$cg_feed_css_wrap = $camp_general ['cg_feed_css_wrap'];
					
					echo '<br>Extracting content from original post for ';
					
					// test url
					// $url = "http://news.jarm.com/view/75431";
					$wholeFound = '';
					if (1) {
						$i = 0;
						foreach ( $cg_feed_custom_id as $cg_selecotr_data ) {
							
							$cg_selector = $cg_custom_selector [$i];
							$cg_feed_css_size_s = $cg_feed_css_size [$i];
							$cg_feed_css_wrap_s = $cg_feed_css_wrap [$i];
							
							echo '<br>' . $cg_selector . ' = "' . $cg_selecotr_data . '"';
							
							if ($cg_feed_css_wrap_s == 'inner') {
								$inner = true;
							} else {
								$inner = false;
							}
							
							if ($cg_selector == 'xpath') {
								$ret = $wpAutomaticDom->getContentByXPath ( stripslashes ( $cg_selecotr_data ), $inner );
							} elseif ($cg_selector == 'class') {
								$ret = $wpAutomaticDom->getContentByClass ( $cg_selecotr_data, $inner );
							} elseif ($cg_selector == 'id') {
								$ret = $wpAutomaticDom->getContentByID ( $cg_selecotr_data, $inner );
							}
							
							$extract = '';
							
							foreach ( $ret as $itm ) {
								
								$extract .= $itm;
								
								if ($cg_feed_css_size_s == 'single') {
									break;
								}
							}
							
							$rule_num = $i + 1;
							$res ['rule_' . $rule_num] = $extract;
							$res ['rule_' . $rule_num . '_plain'] = strip_tags ( $extract );
							
							if (trim ( $extract ) == '') {
								echo '<br>Nothing found to extract for this rule';
							} else {
								echo ' <-- ' . strlen ( $extract ) . ' chars extracted';
								$wholeFound = (trim ( $wholeFound ) == '') ? $extract : $wholeFound . '<br>' . $extract;
							}
							$i ++;
						}
						if (trim ( $wholeFound ) != '') {
							$res ['cont'] = $wholeFound;
							$res ['matched_content'] = $wholeFound;
						}
					} else {
						echo '<br>could not parse the content returning summary';
					}
					
					// REGEX EXTRACT
				} elseif (in_array ( 'OPT_FEED_CUSTOM_R', $camp_opt )) {
					
					echo '<br>Extracting content using REGEX ';
					$cg_feed_custom_regex = $camp_general ['cg_feed_custom_regex'];
					
					$finalmatch = '';
					
					foreach ( $cg_feed_custom_regex as $cg_feed_custom_regex_single ) {
						
						$cg_feed_custom_regex_single = html_entity_decode ( $cg_feed_custom_regex_single );
						
						if (trim ( $cg_feed_custom_regex_single ) != '') {
							
							$finalmatch2 = '';
							
							// we have a regex
							echo '<br>Regex :' . htmlspecialchars ( $cg_feed_custom_regex_single );
							
							// extracting
							if (trim ( $original_cont ) != '') {
								preg_match_all ( '{' . $cg_feed_custom_regex_single . '}is', $original_cont, $matchregex );
								
								for($i = 1; $i < count ( $matchregex ); $i ++) {
									
									foreach ( $matchregex [$i] as $newmatch ) {
										
										if (trim ( $newmatch ) != '') {
											if (trim ( $finalmatch ) != '') {
												$finalmatch .= '<br>' . $newmatch;
												$finalmatch2 .= '<br>' . $newmatch;
											} else {
												$finalmatch .= $newmatch;
												$finalmatch2 .= $newmatch;
											}
										}
									}
								}
								
						 
								
								echo '<-- ' . strlen ( $finalmatch2 ) . ' chars found';
							} else {
								echo '<br>Can not load original content.';
							}
						} // rule not empty
					} // foreach rule
					
					if (trim ( $finalmatch ) != '') {
						// overwirte
						echo '<br>' . strlen ( $finalmatch ) . ' chars extracted using REGEX';
						$res ['cont'] = $finalmatch;
						$res ['matched_content'] = $finalmatch;
					} else {
						echo '<br>Nothing extracted using REGEX using summary instead..';
					}
				}
				
				// redirect_url tag finding
				$redirect = '';
				$redirect = curl_getinfo ( $this->ch, CURLINFO_EFFECTIVE_URL );
				
				if (trim ( $redirect ) != '') {
					$res ['redirect_url'] = $redirect;
				} else {
					$res ['redirect_url'] = $res ['source_link'];
				}
				
				// Stripping content using id or class from $res[cont]
				if (in_array ( 'OPT_STRIP_CSS', $camp_opt )) {
					
					echo '<br>Stripping content using:- ';
					
					$cg_selector = $camp_general ['cg_custom_strip_selector'];
					$cg_selecotr_data = $camp_general ['cg_feed_custom_strip_id'];
					$cg_selecotr_data = array_filter ( $cg_selecotr_data );
					
					// Load dom
					$final_doc = new DOMDocument ();
					
					// getting encoding
					
					preg_match_all ( '{charset=["|\']([^"]+?)["|\']}', $original_cont, $encMatches );
					$possibleCharSet = $encMatches [1];
					
					$possibleCharSet = isset ( $possibleCharSet [0] ) ? $possibleCharSet [0] : '';
					
					if (trim ( $possibleCharSet ) == '')
						$possibleCharSet = 'UTF-8';
					
					// overwrite to utf if already utf-8
					if ($possibleCharSet != 'UTF-8' && function_exists ( 'mb_detect_encoding' ) && mb_detect_encoding ( $res ['cont'] ) == 'UTF-8') {
						
						echo '<br>Source encoding is ' . $possibleCharSet . ' but we still think it is utf-8 resetting...';
						$possibleCharSet = 'UTF-8';
					}
					
					$charSetMeta = '<meta http-equiv="content-type" content="text/html; charset=' . $possibleCharSet . '"/>';
					
					$full_html = '<head>' . $charSetMeta . '</head><body>' . $res ['cont'] . '</body>';
					
					@$final_doc->loadHTML ( $full_html );
					$selector = new DOMXPath ( $final_doc );
					
					$html_to_count = $final_doc->saveHTML ( $final_doc->documentElement );
					
					$i = 0;
					$inner = false;
					foreach ( $cg_selecotr_data as $cg_selector_data_single ) {
						
						echo '<br> - ' . $cg_selector [$i] . ' = "' . $cg_selector_data_single . '" ';
						
						if (trim ( $cg_selector_data_single ) != '') {
							
							if ($cg_selector [$i] == 'class') {
								$query_final = '//*[contains(attribute::class, "' . trim ( $cg_selector_data_single ) . '")]';
							} elseif ($cg_selector [$i] == 'id') {
								$query_final = "//*[@id='" . trim ( $cg_selector_data_single ) . "']";
							}
							
							$countBefore = $this->chars_count ( $html_to_count );
							
							foreach ( $selector->query ( $query_final ) as $e ) {
								$e->parentNode->removeChild ( $e );
							}
							
							$html_to_count = $final_doc->saveHTML ( $final_doc->documentElement );
							
							$countAfter = $this->chars_count ( $html_to_count );
							
							echo '<-- ' . ($countBefore - $countAfter) . ' chars removed';
						}
						
						$i ++;
					}
					
					$contentAfterReplacement = $final_doc->saveHTML ( $final_doc->documentElement );
					$contentAfterReplacement = str_replace ( array (
							'<html>',
							'</html>',
							'<body>',
							'</body>',
							$charSetMeta 
					), '', $contentAfterReplacement );
					$contentAfterReplacement = preg_replace ( '{<head>.*?</head>}', '', $contentAfterReplacement );
					
					$res ['cont'] = trim ( $contentAfterReplacement );
					
					// overwirte
					$res ['matched_content'] = $res ['cont'];
				}
				
				// Stripping content using REGEX
				if (in_array ( 'OPT_STRIP_R', $camp_opt )) {
					$current_content = $res ['matched_content'];
					$current_title = $res ['title'];
					$cg_post_strip = html_entity_decode ( $camp_general ['cg_post_strip'] );
					
					$cg_post_strip = explode ( "\n", $cg_post_strip );
					$cg_post_strip = array_filter ( $cg_post_strip );
					
					foreach ( $cg_post_strip as $strip_pattern ) {
						if (trim ( $strip_pattern ) != '') {
							
							// $strip_pattern ='<img[^>]+\\>';
							
							echo '<br>Stripping using REGEX:' . htmlentities ( $strip_pattern );
							
							$countBefore = $this->chars_count ( $current_content );
							
							$current_content = preg_replace ( '{' . trim ( $strip_pattern ) . '}is', '', $current_content );
							
							// replacing in rules
							$i = 1;
							while ( isset ( $res ["rule_$i"] ) ) {
								
								$res ["rule_$i"] = preg_replace ( '{' . trim ( $strip_pattern ) . '}is', '', $res ["rule_$i"] );
								$i ++;
							}
							
							$countAfter = $this->chars_count ( $current_content );
							
							echo ' <-- ' . ($countBefore - $countAfter) . ' chars removed';
							
							$current_title = preg_replace ( '{' . trim ( $strip_pattern ) . '}is', '', $current_title );
						}
					}
					
					if (trim ( $current_content ) != '') {
						$res ['matched_content'] = $current_content;
						$res ['cont'] = $current_content;
					}
					
					if (trim ( $current_title ) != '') {
						$res ['matched_title'] = $current_title;
						$res ['original_title'] = $current_title;
						$res ['title'] = $current_title;
					}
				} // end regex replace
				  
				// strip tags
				if (in_array ( 'OPT_STRIP_T', $camp_opt )) {
					
					echo '<br>Stripping html tags...';
					
					$cg_allowed_tags = trim ( $camp_general ['cg_allowed_tags'] );
					
					if (! stristr ( $cg_allowed_tags, '<script' )) {
						$res ['matched_content'] = preg_replace ( '{<script.*?script>}s', '', $res ['matched_content'] );
						$res ['cont'] = preg_replace ( '{<script.*?script>}s', '', $res ['cont'] );
						
						$res ['matched_content'] = preg_replace ( '{<noscript.*?noscript>}s', '', $res ['matched_content'] );
						$res ['cont'] = preg_replace ( '{<noscript.*?noscript>}s', '', $res ['cont'] );
					}
					
					$res ['matched_content'] = strip_tags ( $res ['matched_content'], $cg_allowed_tags );
					$res ['cont'] = strip_tags ( $res ['cont'], $cg_allowed_tags );
				}
				
				// validate content size
				
				// MUST CONTENT
				if (in_array ( 'OPT_MUST_CONTENT', $camp_opt )) {
					
					if (trim ( $res ['cont'] ) == '') {
						echo '<--No content excluding';
						$this->link_execlude ( $camp->camp_id, $original_url );
						continue;
					}
				}
				
				// limit content
				if (in_array ( 'OPT_MIN_LENGTH', $camp_opt )) {
					
					$contentTextual = preg_replace ( '{<script.*?script>}s', '', $res ['cont'] );
					$contentTextual = strip_tags ( $contentTextual );
					$contentTextual = str_replace ( ' ', '', $contentTextual );
					
					if (function_exists ( 'mb_strlen' )) {
						$contentLength = mb_strlen ( $contentTextual );
					} else {
						$contentLength = strlen ( $contentTextual );
					}
					
					unset ( $contentTextual );
					
					echo '<br>Content length:' . $contentLength;
					
					if ($contentLength < $camp_general ['cg_min_length']) {
						echo '<--Shorter than the minimum... Excluding';
						$this->link_execlude ( $camp->camp_id, $original_url );
						continue;
					} else {
						echo '<--Valid length';
					}
				}
				
				// Entity decode
				if (in_array ( 'OPT_FEED_ENTITIES', $camp_opt )) {
					echo '<br>Decoding html entities';
					
					// php 5.3 and lower convert &nbsp; to invalid charchters that broke everything
					
					$res ['original_title'] = str_replace ( '&nbsp;', ' ', $res ['original_title'] );
					$res ['matched_content'] = str_replace ( '&nbsp;', ' ', $res ['matched_content'] );
					
					$res ['original_title'] = html_entity_decode ( $res ['original_title'], ENT_QUOTES | ENT_HTML401 );
					$res ['title'] = html_entity_decode ( $res ['title'], ENT_QUOTES | ENT_HTML401 );
					
					$res ['matched_content'] = html_entity_decode ( $res ['matched_content'], ENT_QUOTES | ENT_HTML401 );
					$res ['cont'] = $res ['matched_content'];
				} // end entity decode
				  
				// Clean googleads and <script tag
				$res ['cont'] = preg_replace ( '{<ins.*?ins>}s', '', $res ['cont'] );
				$res ['cont'] = preg_replace ( '{<ins.*?>}s', '', $res ['cont'] );
				
				if (! in_array ( 'OPT_FEED_SCRIPT', $camp_opt ))
					$res ['cont'] = preg_replace ( '{<script.*?script>}s', '', $res ['cont'] );
				
				$res ['cont'] = preg_replace ( '{\(adsbygoogle.*?\);}s', '', $res ['cont'] );
				$res ['matched_content'] = $res ['cont'];
				
				// meta tags
				if (in_array ( 'OPT_ORIGINAL_META', $camp_opt )) {
					
					// extract all metas
					preg_match_all ( '{<meta.*?>}s', $original_cont, $metaMatches );
					
					$allMeta = $metaMatches [0];
					
					foreach ( $allMeta as $singleMeta ) {
						
						if (stristr ( $singleMeta, 'keywords' )) {
							
							if (preg_match ( '{name[\s]?=[\s]?["\']keywords["\']}', $singleMeta )) {
								
								preg_match_all ( '{content[\s]?=[\s]?[\'"](.*?)[\'"]}s', $singleMeta, $realTagsMatches );
								$realTagsMatches = $realTagsMatches [1];
								
								if (trim ( $realTagsMatches [0] ) != '') {
									
									echo '<br>Meta tags:' . $realTagsMatches [0];
									$res ['tags'] = $realTagsMatches [0];
								}
							}
						}
					}
				}
				
				// Extract cats from original source
				if (in_array ( 'OPT_ORIGINAL_CATS', $camp_opt ) && trim ( $camp_general ['cg_feed_custom_id_cat'] ) != '') {
					
					echo '<br>Extracting original post categories ';
					
					$cg_selector_cat = $camp_general ['cg_custom_selector_cat'];
					$cg_selecotr_data_cat = $camp_general ['cg_feed_custom_id_cat'];
					$inner = false;
					
					echo ' for ' . $cg_selector_cat . ' = ' . $cg_selecotr_data_cat;
					
					// dom class
					if (! isset ( $wpAutomaticDom )) {
						require_once 'inc/class.dom.php';
						$wpAutomaticDom = new wpAutomaticDom ( $original_cont );
					}
					
					if (1) {
						
						$extract = '';
						
						if ($cg_selector_cat == 'class') {
							$extract = $wpAutomaticDom->getContentByClass ( $cg_selecotr_data_cat, $inner );
						} elseif ($cg_selector_cat == 'id') {
							$extract = $wpAutomaticDom->getContentByID ( $cg_selecotr_data_cat, $inner );
						} elseif ($cg_selector_cat == 'xpath') {
							$extract = $wpAutomaticDom->getContentByXPath ( stripslashes ( $cg_selecotr_data_cat ), $inner );
						}
						
						if (is_array ( $extract )) {
							
							if (in_array ( 'OPT_SELECTOR_SINGLE_CAT', $camp_opt )) {
								$extract = $extract [0];
							} else {
								$extract = implode ( ' ', $extract );
							}
						}
						
						if (trim ( $extract ) == '') {
							echo '<br>Nothing found to extract for this category rule';
						} else {
							echo '<br>Cat Rule extracted ' . strlen ( $extract ) . ' charchters ';
							
							if (stristr ( $extract, '<a' )) {
								preg_match_all ( '{<a .*?>(.*?)</a}su', $extract, $cats_matches );
								
								$cats_founds = $cats_matches [1];
								$cat_founds = array_map ( 'strip_tags', $cats_founds );
								$cats_str = implode ( ',', $cat_founds );
								
								echo ' found cats:' . $cats_str;
								$res ['cats'] = $cats_str;
							}
						}
					}
				}
				
				// Extract tags from original source
				if (in_array ( 'OPT_ORIGINAL_TAGS', $camp_opt ) && trim ( $camp_general ['cg_feed_custom_id_tag'] ) != '') {
					
					echo '<br>Extracting original post tags ';
					
					$cg_selector_tag = $camp_general ['cg_custom_selector_tag'];
					$cg_selecotr_data_tag = $camp_general ['cg_feed_custom_id_tag'];
					$inner = false;
					
					echo ' for ' . $cg_selector_tag . ' = ' . $cg_selecotr_data_tag;
					
					// dom class
					if (! isset ( $wpAutomaticDom )) {
						require_once 'inc/class.dom.php';
						$wpAutomaticDom = new wpAutomaticDom ( $original_cont );
					}
					
					if (1) {
						
						$extract = '';
						
						if ($cg_selector_tag == 'class') {
							$extract = $wpAutomaticDom->getContentByClass ( $cg_selecotr_data_tag, $inner );
						} elseif ($cg_selector_tag == 'id') {
							$extract = $wpAutomaticDom->getContentByID ( $cg_selecotr_data_tag, $inner );
						} elseif ($cg_selector_tag == 'xpath') {
							$extract = $wpAutomaticDom->getContentByXPath ( stripslashes ( $cg_selecotr_data_tag ), $inner );
						}
						
						if (is_array ( $extract )) {
							
							if (in_array ( 'OPT_SELECTOR_SINGLE_TAG', $camp_opt )) {
								$extract = $extract [0];
							} else {
								$extract = implode ( ' ', $extract );
							}
						}
						
						if (trim ( $extract ) == '') {
							echo '<br>Nothing found to extract for this tag rule';
						} else {
							echo '<br>Tag Rule extracted ' . strlen ( $extract ) . ' charchters ';
							
							if (stristr ( $extract, '<a' )) {
								preg_match_all ( '{<a .*?>(.*?)</a}su', $extract, $tags_matches );
								
								$tags_founds = $tags_matches [1];
								$tags_founds = array_map ( 'strip_tags', $tags_founds );
								
								$tags_str = implode ( ',', $tags_founds );
								
								echo ' Found tags:' . $tags_str;
								$res ['tags'] = $tags_str;
							}
						}
					}
				} elseif (in_array ( 'OPT_ORIGINAL_TAGS', $camp_opt )) {
					
					echo '<br>You must add a valid ID/Class to Extract tags, No tags will get extracted.';
				} // extract tags
				  
				// extract author from original source
				if (in_array ( 'OPT_ORIGINAL_AUTHOR', $camp_opt ) && trim ( $camp_general ['cg_feed_custom_id_author'] ) != '') {
					
					echo '<br>Extracting original post author ';
					
					 
					
					$cg_selector_author = $camp_general ['cg_custom_selector_author'];
					$cg_selecotr_data_author = $camp_general ['cg_feed_custom_id_author'];
					$inner = false;
					
					echo ' for ' . $cg_selector_author . ' = ' . $cg_selecotr_data_author;
					
					// dom class
					if (! isset ( $wpAutomaticDom )) {
						require_once 'inc/class.dom.php';
						$wpAutomaticDom = new wpAutomaticDom ( $original_cont );
					}
					
					if (1) {
						
						$extract = '';
						
						if ($cg_selector_author == 'class') {
							$extract = $wpAutomaticDom->getContentByClass ( $cg_selecotr_data_author, $inner );
						} elseif ($cg_selector_author == 'id') {
							$extract = $wpAutomaticDom->getContentByID ( $cg_selecotr_data_author, $inner );
						} elseif ($cg_selector_author == 'xpath') {
							$extract = $wpAutomaticDom->getContentByXPath ( stripslashes ( $cg_selecotr_data_author ), $inner );
						}
						
						if (is_array ( $extract )) {
							
							if (in_array ( 'OPT_SELECTOR_SINGLE_AUTHOR', $camp_opt )) {
								$extract = $extract [0];
							} else {
								$extract = implode ( ' ', $extract );
							}
						}
						
						// Validate returned author
						if (trim ( $extract ) == '') {
							echo '<br>Nothing found to extract for this author rule';
						} else {
							echo '<br>author Rule extracted ' . strlen ( $extract ) . ' charchters ';
							
							if (stristr ( $extract, '<a' )) {
								preg_match_all ( '{<a .*?>(.*?)</a}su', $extract, $author_matches );
								
								$author_founds = $author_matches [1];
								$author_str = strip_tags ( $author_founds [0] );
								
								echo ' Found author:' . $author_str;
								$res ['author'] = $author_str;
							}else{
								
								//no links
								$author_str = strip_tags ( $extract );
								
								if(strlen($author_str) < 100){
									echo ' Found author:' . $author_str;
									$res ['author'] = $author_str;
								}
								
							}
						}
					}
				} elseif (in_array ( 'OPT_ORIGINAL_AUTHOR', $camp_opt )) {
					
					if (trim ( $res ['author'] ) == '') {
						
						echo '<br>You must add a valid ID/Class to Extract Author, No Author will get extracted.';
					}
				} // extract author
				
				if (! in_array ( 'OPT_ENCLUSURE', $camp_opt )) {
					if (isset ( $media_image_url ) && ! stristr ( $res ['cont'], '<img' )) {
						echo '<br>enclosure image:' . $media_image_url;
						$res ['cont'] = '<img src="' . $media_image_url . '" /><br>' . $res ['cont'];
					}
				}
				
				// Part to custom field OPT_FEED_PTF
				
				// Extracted custom fields ini
				$customFieldsArr = array ();
				$ruleFields = array (); // fields names
				
				If (in_array ( 'OPT_FEED_PTF', $camp_opt )) {
					
					echo '<br>Specific Part to custom field extraction';
					
					// Load rules
					$cg_part_to_field = trim ( html_entity_decode ( $camp_general ['cg_part_to_field'] ) );
					$cg_part_to_field_parts = explode ( "\n", $cg_part_to_field );
					
					// Process rules
					foreach ( $cg_part_to_field_parts as $cg_part_to_field_part ) {
						echo '<br>Rule:' . htmlentities ( $cg_part_to_field_part );
						
						// Validate format |
						if (! stristr ( $cg_part_to_field_part, '|' )) {
							echo '<- Wrong format...';
							continue;
						}
						
						// Parse rule
						$rule_parts = explode ( '|', $cg_part_to_field_part );
						
						$rule_method = trim ( $rule_parts [0] );
						$rule_value = trim ( $rule_parts [1] );
						$rule_field = trim ( $rule_parts [2] );
						
						$rule_single = 0;
						@$rule_single = $rule_parts [3];
						
						// Validate rule
						if (trim ( $rule_method ) == '' || trim ( $rule_value ) == '' || trim ( $rule_field ) == '') {
							echo '<- Wrong format...';
							continue;
						}
						
						$ruleFields [] = $rule_field;
						
						// Validate rule method: class,id,regex,xpath
						if ($rule_method != 'id' && $rule_method != 'class' && $rule_method != 'regex' && $rule_method != 'xpath') {
							echo '<- Wrong Method:' . $rule_method;
							continue;
						}
						
						// id,class,xPath
						if ($rule_method == 'id' || $rule_method == 'class' || $rule_method == 'xpath') {
							
							
							// Dom object
							$doc = new DOMDocument ();
							
							// Load Dom
							@$doc->loadHTML ( $original_cont );
							
							
							// xPath object
							$xpath = new DOMXPath ( $doc );
							
							// xPath query
							if ($rule_method != 'xpath') {
								$xpathMatches = $xpath->query ( "//*[@" . $rule_method . "='" . $rule_value . "']" );
							} else {
								$xpathMatches = $xpath->query ( "$rule_value" );
							}
							
							// Single item ?
							if ($rule_single) {
								$xpathMatches = array (
										$xpathMatches->item ( 0 ) 
								);
							}
							
							// Rule result ini
							$rule_result = '';
							
							foreach ( $xpathMatches as $xpathMatch ) {
								
								if ($rule_field == 'tags' || $rule_field == 'categories') {
									$rule_result .= ',' . $xpathMatch->nodeValue;
								} else {
									$rule_result .= $xpathMatch->nodeValue;
								}
							}
							
							// Store field to be added
							if (trim ( $rule_result ) != '') {
								echo ' <--' . $this->chars_count ( $rule_result ) . ' chars extracted';
								
								if ($rule_field == 'categories') {
									$res ['cats'] = $rule_result;
								} else {
									$customFieldsArr [] = array (
											$rule_field,
											$rule_result 
									);
								}
							} else {
								echo '<-- nothing found';
							}
						} else {
							
							// Regex extract
							$matchregex = array ();
							$finalmatch = '';
							 
							// Match
							preg_match_all ( '{' . trim ( $rule_value ) . '}is', $original_cont, $matchregex );
							
							$matchregex_vals = $matchregex [1];
							
							if (isset ( $matchregex [2] ))
								$matchregex_vals = array_merge ( $matchregex_vals, $matchregex [2] );
							
							// single match
							if ($rule_single && count ( $matchregex_vals ) > 1) {
								$matchregex_vals = array (
										$matchregex_vals [0] 
								);
							}
							
							// Read matches
							foreach ( $matchregex_vals as $newmatch ) {
								
								if (trim ( $newmatch ) != '') {
									
									if (trim ( $finalmatch ) == '') {
										$finalmatch .= '' . $newmatch;
									} else {
										
										if ($rule_field == 'tags' || $rule_field == 'categories') {
											
											$finalmatch .= ',' . $newmatch;
										} else {
											$finalmatch .= $newmatch;
										}
									}
								}
							}
							
							// Store field to be added
							if (trim ( $finalmatch ) != '') {
								
								echo ' <--' . $this->chars_count ( $finalmatch ) . ' chars extracted';
								
								if ($rule_field == 'categories') { 
									$res ['cats'] = $finalmatch;
								

								} else {
									$customFieldsArr [] = array (
											$rule_field,
											$finalmatch 
									);
								}
							} else {
								echo '<-- Nothing found';
							}
						}
					} // foreach rule
				} // if part to field enabled
				
				 
				 
				
				
				$res ['custom_fields'] = $customFieldsArr;
				
				foreach ( $ruleFields as $field_name ) {
					
					$field_value = ''; // ini
					
					foreach ( $customFieldsArr as $fieldsArray ) {
						
						if ($fieldsArray [0] == $field_name) {
							$field_value = $fieldsArray [1];
						}
					}
					
					$res [$field_name] = $field_value;
				}
				
				// og:image check
				
				// $url="kenh14.vn/kham-pha/5-hoi-chung-benh-ky-quac-nghe-thoi-cung-toat-mo-hoi-hot-20151219200950753.chn";
				
				$currentOgImage = ''; // for og:image found check
				
				if (in_array ( 'OPT_FEEDS_OG_IMG', $camp_opt )) {
					
					// getting the og:image
					// let's find og:image
					
					// if no http
					$original_cont = str_replace ( 'content="//', 'content="http://', $original_cont );
					
					echo '<br>Extracting og:image :';
					
					// let's find og:image may be the content we got has no image
					preg_match ( '{<meta[^<]*?(?:property|name)=["|\']og:image["|\'][^<]*?>}s', $original_cont, $plain_og_matches );
					
					if (isset ( $plain_og_matches [0] ) && stristr ( $plain_og_matches [0], 'og:image' )) {
						preg_match ( '{content=["|\'](.*?)["|\']}s', $plain_og_matches [0], $matches );
						$og_img = $matches [1];
						
						if (trim ( $og_img ) != '') {
							
							$og_img_short = preg_replace ( '{http://.*?/}', '', $og_img );
							echo $og_img_short;
							if (trim ( $og_img_short ) == '') {
								$og_img_short = $og_img;
							}
							
							// get og_title
							preg_match_all ( '/<img .*>/', $original_cont, $all_images );
							
							$all_images = $all_images [0];
							$foundAlt = ''; // ini
							foreach ( $all_images as $single_image ) {
								if (stristr ( $single_image, $og_img_short )) {
									// extract alt text
									preg_match ( '/alt=["|\'](.*?)["|\']/', $single_image, $alt_matches );
									$foundAlt = (isset ( $alt_matches [1] )) ? $alt_matches [1] : '';
								}
							}
							
							$res ['og_img'] = $og_img;
							
							// no http format but //tec
							if (! stristr ( $og_img, 'http' ) && stristr ( $og_img, '//' )) {
								$res ['og_img'] = 'https:' . $og_img;
							}
							
							$res ['og_img'] = trim($res ['og_img'] );
							$res ['og_alt'] = $foundAlt;
							$currentOgImage = $og_img;
						}
					}
				}
				
				// fix FB embeds
				if (stristr ( $res ['cont'], 'fb-post' ) && ! stristr ( $res ['cont'], 'connect.facebook' )) {
					echo '<br>Possible Facebook embeds found adding embed scripts';
					$res ['cont'] .= '<div id="fb-root"></div>
<script async defer src="https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v3.2"></script>';
				}
				
				// fix twitter embeds
				if (stristr ( $res ['cont'], 'twitter.com' ) && ! stristr ( $res ['cont'], 'platform.twitter' )) {
					echo '<br>Possible tweets found without twitter JS. adding JS';
					$res ['cont'] .= '<script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>';
				}
				
				// fix instagram.com embeds
				if (stristr ( $res ['cont'], 'instagram.com' ) && ! stristr ( $res ['cont'], 'platform.instagram' )) {
					echo '<br>Possible Instagram embeds found without JS. adding JS';
					$res ['cont'] .= '<script async defer src="https://platform.instagram.com/en_US/embeds.js"></script>';
				}
				
				// fix youtube no height embeds
				if (stristr ( $res ['cont'], 'youtube.com/embed' )) {
					
					preg_match_all ( '{<iframe[^>]*?youtube.com/embed/(.*?)["|\?].*?>(?:</iframe>)?}', $res ['cont'], $yt_matches );
					
					$yt_matches_full = $yt_matches [0];
					$yt_matches_ids = $yt_matches [1];
					
					if (count ( $yt_matches_full ) > 0) {
						
						$i = 0;
						foreach ( $yt_matches_full as $embed ) {
							
							echo '<br>Youtube video embed format changed to WordPress for video :' . $yt_matches_ids [$i];
							
							$res ['cont'] = str_replace ( $embed, '[embed]https://www.youtube.com/watch?v=' . $yt_matches_ids [$i] . '[/embed]', $res ['cont'] );
							
							$i ++;
						}
					}
				}
				
				// check if image or not
				if (in_array ( 'OPT_MUST_IMAGE', $camp_opt ) && ! stristr ( $res ['cont'], '<img' ) && trim ( $currentOgImage ) == '') {
					
					echo '<br>Post contains no images skipping it ...';
					
					// Excluding it
					$this->link_execlude ( $camp->camp_id, $original_url );
				} else {
					
					// og image fix
					if (isset ( $res ['og_img'] ) && trim ( $res ['og_img'] ) != '') {
						
						// make sure it has the domain
						if (! stristr ( $og_img, 'http:' )) {
							if (stristr ( $og_img, '//' )) {
								
								$og_img = 'http:' . $og_img;
							} else {
								
								$host = parse_url ( $url, PHP_URL_HOST );
								
								// no domain at all
								$og_img = '/' . $og_img;
								$og_img = str_replace ( '//', '/', $og_img );
								$og_img = 'http://' . $host . $og_img;
								$res ['og_img'] = $og_img;
							}
						}
					}
					
					// og title or custom title extraction method
					if (in_array ( 'OPT_FEED_OG_TTL', $camp_opt ) || (isset ( $camp_general ['cg_ml_ttl_method'] ) && trim ( $camp_general ['cg_ml_ttl_method'] ) != 'auto')) {
						
						if (in_array ( 'OPT_FEED_OG_TTL', $camp_opt )) {
							// let's find og:image may be the content we got has no image
							preg_match ( '{<meta[^<]*?property=["|\']og:title["|\'][^<]*?>}s', $original_cont, $plain_og_matches );
							
							if (@stristr ( $plain_og_matches [0], 'og:title' )) {
								preg_match ( '{content[\s]*=[\s]*"(.*?)"}s', $plain_og_matches [0], $matches );
								
								$og_ttl = $matches [1];
								
								echo '<br>og:title:' . html_entity_decode ( htmlspecialchars_decode ( $og_ttl ) );
								
								if (trim ( $og_ttl ) != '') {
									$og_ttl = htmlspecialchars_decode ( $og_ttl, ENT_QUOTES );
									$res ['title'] = html_entity_decode ( $og_ttl );
								}
							} // If og:title
						} else {
							
							// custom extraction method
							$cg_ml_ttl_method = $camp_general ['cg_ml_ttl_method'];
							
							require_once 'inc/class.dom.php';
							$wpAutomaticDom = new wpAutomaticDom ( $original_cont );
							
							$finalContent = '';
							
							if ($cg_ml_ttl_method == 'visual') {
								
								$cg_ml_visual = $camp_general ['cg_ml_visual'];
								$path = isset ( $cg_ml_visual [0] ) ? $cg_ml_visual [0] : '';
								
								if (trim ( $path ) == '') {
									echo '<br>No path found for pagination, please set the extraction rule for pagination if you want to make use of pagination.';
								}
								
								foreach ( $cg_ml_visual as $xpath ) {
									
									echo '<br>Getting title for XPath:' . $path;
									
									try {
										
										$finalContent = $wpAutomaticDom->getContentByXPath ( $xpath, false );
										
										if (is_array ( $finalContent ))
											$finalContent = implode ( '', $finalContent );
										
										echo '<-- ' . strlen ( $finalContent ) . ' chars';
									} catch ( Exception $e ) {
										echo '<br>Error:' . $e->getMessage ();
									}
								}
							} elseif ($cg_ml_ttl_method == 'css') {
								
								$cg_ml_css_type = $camp_general ['cg_ml_css_type'];
								$cg_ml_css = $camp_general ['cg_ml_css'];
								$cg_ml_css_wrap = $camp_general ['cg_ml_css_wrap'];
								$cg_ml_css_size = $camp_general ['cg_ml_css_size'];
								$finalContent = '';
								
								$i = 0;
								foreach ( $cg_ml_css_type as $singleType ) {
									
									if ($cg_ml_css_wrap [$i] == 'inner') {
										$inner = true;
									} else {
										$inner = false;
									}
									
									echo '<br>Extracting content by ' . $cg_ml_css_type [$i] . ' : ' . $cg_ml_css [$i];
									
									if ($cg_ml_css_type [$i] == 'class') {
										$content = $wpAutomaticDom->getContentByClass ( $cg_ml_css [$i], $inner );
									} elseif ($cg_ml_css_type [$i] == 'id') {
										$content = $wpAutomaticDom->getContentByID ( $cg_ml_css [$i], $inner );
									} elseif ($cg_ml_css_type [$i] == 'xpath') {
										$content = $wpAutomaticDom->getContentByXPath ( stripslashes ( $cg_ml_css [$i] ), $inner );
									}
									
									if (is_array ( $content )) {
										
										if ($cg_ml_css_size [$i] == 'single') {
											$content = $content [0];
										} else {
											$content = implode ( "\n", $content );
										}
										
										$finalContent .= $content . "\n";
									}
									
									echo '<-- ' . strlen ( $content ) . ' chars';
									
									$i ++;
								} // foreach rule
							} elseif ($cg_ml_ttl_method == 'regex') {
								
								$cg_ml_regex = $camp_general ['cg_ml_regex'];
								$finalContent = '';
								$i = 0;
								foreach ( $cg_ml_regex as $cg_ml_regex_s ) {
									
									echo '<br>Extracting content by REGEX : ' . htmlentities ( stripslashes ( $cg_ml_regex_s ) );
									
									$content = $wpAutomaticDom->getContentByRegex ( stripslashes ( $cg_ml_regex_s ) );
									
									$content = implode ( $content );
									
									echo '<-- ' . strlen ( $content ) . ' chars';
									
									if (trim ( $content ) != '') {
										$finalContent .= $content . "\n";
									}
									
									$i ++;
								}
							}
						}
						
						$possibleTitle = '';
						if (trim ( $finalContent ) != '') {
							$possibleTitle = strip_tags ( $finalContent );
						}
						
						if (trim ( $possibleTitle ) != '') {
							$res ['original_title'] = html_entity_decode ( $possibleTitle, ENT_QUOTES | ENT_HTML401 );
							$res ['title'] = html_entity_decode ( $possibleTitle, ENT_QUOTES | ENT_HTML401 );
						}
					}
					
				 
					
 					return $res;
				}
			} else {
				
				// duplicated link
				echo ' <-- duplicate in post <a href="' . admin_url ( 'post.php?post=' . $this->duplicate_id . '&action=edit' ) . '">#' . $this->duplicate_id . '</a>';
			}
		endforeach
		;
		
		echo '<br>End of feed items reached.';
		
		if (isset ( $camp->camp_sub_type ) && $camp->camp_sub_type == 'Multi') {
			
			delete_post_meta ( $camp->camp_id, 'wp_automatic_cache' );
		} else {
			
			// Set isItemsEndReached flag to yes
			update_post_meta ( $camp->camp_id, $feedMd5 . '_isItemsEndReached', 'yes' );
		}
	} // end function
}