<?php

// Main Class
require_once 'core.php';

// API less amazon module
class WpAutomaticAmazon extends wp_automatic {
	
	/*
	 * ---* Get Amazon Post ---
	 */
	function amazon_get_post($camp) {
		
		// refer to amazon
		curl_setopt ( $this->ch, CURLOPT_REFERER, "https://www.amazon.{$camp->camp_amazon_region}/" );
		
		$this->load_cookie ( 'wp_automatic_amazon' );
		
		if (stristr ( $camp->camp_general, 'a:' ))
			$camp->camp_general = base64_encode ( $camp->camp_general );
		$camp_general = unserialize ( base64_decode ( $camp->camp_general ) );
		
		// reading keywords that need to be processed
		$keywords = explode ( ',', $camp->camp_keywords );
		
		// default cat
		if (trim ( $camp->camp_amazon_cat ) == '') {
			$camp->camp_amazon_cat = 'All';
		}
		
		foreach ( $keywords as $keyword ) {
			
			$keyword = trim ( $keyword );
			
			if (trim ( $keyword ) != '') {
				$keyword = trim ( $keyword );
				echo ('<hr><b>Processing Keyword:</b> "' . $keyword . '"');
				
				// update last keyword
				update_post_meta ( $camp->camp_id, 'last_keyword', trim ( $keyword ) );
				
				// ASIN hack
				// getting links from the db for that keyword
				$query = "select * from {$this->wp_prefix}automatic_amazon_links where link_keyword='{$camp->camp_id}_$keyword' ";
				$res = $this->db->get_results ( $query );
				
				// when no links lets get new links
				if (count ( $res ) == 0) {
					
					// clean any old cache for this keyword
					$query_delete = "delete from {$this->wp_prefix}automatic_amazon_links where link_keyword='{$camp->camp_id}_$keyword' ";
					$this->db->query ( $query_delete );
					
					$this->amazon_fetch_links ( $keyword, $camp );
					
					// getting links from the db for that keyword
					$res = $this->db->get_results ( $query );
				}
				
				// delete already posted items from other campaigns
				// deleting duplicated items
				$res_count = count ( $res );
				
				for($i = 0; $i < $res_count; $i ++) {
					
					$t_row = $res [$i];
					$t_link_url = $t_row->link_url;
					
					// Duplicate check
					$possible_full_link = $t_link_url;
					
					if (! stristr ( $t_link_url, 'http' )) {
						// building link from ASIN
						$possible_full_link = 'https://amazon.' . $camp->camp_amazon_region . '/dp/' . $t_link_url;
					} else {
						
						// get asin
						$link_parts = explode ( '/dp/', $t_link_url );
						$asin = $link_parts [1];
					}
					
					if ($this->is_duplicate ( $possible_full_link )) {
						
						// duplicated item let's delete
						unset ( $res [$i] );
						
						echo '<br>Amazon Item (' . $possible_full_link . ') found cached but duplicated <a href="' . get_permalink ( $this->duplicate_id ) . '">#' . $this->duplicate_id . '</a>';
						
						// delete the item
						$query = "delete from {$this->wp_prefix}automatic_amazon_links where link_id={$t_row->link_id}";
						$this->db->query ( $query );
						
						continue;
					}
					
					if ( $this->is_execluded ( $camp->camp_id, $possible_full_link )   ) {
						
						// duplicated item let's delete
						unset ( $res [$i] );
						
						echo '<br>Amazon Item (' . $possible_full_link . ') found cached but excluded ';
						
						// delete the item
						$query = "delete from {$this->wp_prefix}automatic_amazon_links where link_id={$t_row->link_id}";
						$this->db->query ( $query );
						
						continue;
					}
					
				 
					$amazonAid = get_option ( 'wp_amazonpin_aaid', '' );
					
					// Get amazon item by ASIN if not complete info
					if (! stristr ( $t_link_url, 'http' )) {
						
						$asin = $t_link_url;
						
						echo '<br>Found ASIN:' . $asin . ' getting complete details from Amazon...';
						
						require_once (dirname ( __FILE__ ) . '/inc/class.amazon.api.less.php');
						$obj = new wp_automatic_amazon_api_less ( $this->ch, $camp->camp_amazon_region );
						
						try {
							
							$this->simulate_location ( $camp->camp_amazon_region );
							
							$agent = $this->get_user_agent ();
							curl_setopt ( $this->ch, CURLOPT_USERAGENT, $agent );
							
							// hack link_desc contains the slug Designer-Teenitor-Painting-Manicure-Rhinestones i.e https://amazon.com/slug/dp/asin
							$item = $obj->getItemByAsin ( $asin, $t_row->link_desc );
							 
						
						} catch ( Exception $e ) {
							echo '<br>Amazon error:' . $e->getMessage ();
						}
						
						if ($obj->update_agent_required)
							$this->update_user_agent ();
						
						if (isset ( $item ['link_title'] ) && trim ( $item ['item_price'] ) == '') {
							echo '<-- Can not find price, let us delete this product and try next run ';
						}
						
						if (isset ( $item ['link_title'] ) && trim ( $item ['item_price'] ) != '') {
							
							echo " Link : <a href=\"{$item['item_link']}\">{$item['link_title']}</a> <br>";
							
							$desc = '';
							@$desc = $item ['item_description'];
							
							// Features existence
							if (count ( $item ['item_features'] ) > 0) {
								echo '-- Features found appending to the description';
								$desc .= '<br>' . implode ( '<br>', $item ['item_features'] );
							}
							
							$title = $item ['link_title'];
							
							// Author if exists, inject it to title
							if (isset ( $Item->ItemAttributes->Author )) {
								$title .= '**' . $Item->ItemAttributes->Author;
							} else {
								$title .= '** ';
							}
							
							// Brand if exists
							if (isset ( $Item->ItemAttributes->Brand )) {
								$title .= '**' . $Item->ItemAttributes->Brand;
							} else {
								$title .= '** ';
							}
							
							// ISBN if exists
							if (isset ( $Item->ItemAttributes->ISBN )) {
								$title .= '**' . $Item->ItemAttributes->ISBN;
							} else {
								$title .= '** ';
							}
							
							// UPC if exists
							if (isset ( $Item->ItemAttributes->UPC )) {
								$title .= '**' . $Item->ItemAttributes->UPC;
							} else {
								$title .= '** ';
							}
							
							$t_link_url = $linkUrl = $item ['item_link'];
							
							if ($this->is_execluded ( $camp->camp_id, $linkUrl )) {
								echo '<-- Execluded';
								continue;
							}
							
							$price = '';
							
							// final saved price price,listprice
							$price = $item ['item_price'] . '-' . $item ['item_pre_price'];
							
							$imgurl = '';
							$imgurl = implode ( ',', $item ['item_images'] );
							
							// review url
							$review = $item ['item_reviews'];
							$review = str_replace ( 'atefpro', $amazonAid, $review );
							
							// building the item
							$t_row->link_url = $linkUrl;
							$t_row->link_title = $title;
							$t_row->link_desc = $desc;
							$t_row->link_price = $price;
							$t_row->link_img = $imgurl;
							$t_row->link_review = ( string ) $review;
							
							$res [$i] = $t_row;
						} else { // valid returned item
						         
							// delete non valid item
							$query = "delete from {$this->wp_prefix}automatic_amazon_links where link_id={$t_row->link_id}";
							$this->db->query ( $query );
							
							unset ( $res [$i] );
						}
					} // ASIN product
					  
					// If this item is valid, the link will be correct, lets break the loop
					if (stristr ( $t_link_url, 'http' ))
						break;
				}
				
				// check again if valid links found for that keyword otherwise skip it
				if (count ( $res ) > 0) {
					
					// lets process that link
					$ret = $res [$i];
					
					/*
					 * //fix commas and dots for es
					 * if($camp->camp_amazon_region == 'es' || $camp->camp_amazon_region == 'de' || $camp->camp_amazon_region == 'fr' || $camp->camp_amazon_region == 'it' ){
					 *
					 * $ret->link_price = str_replace(',','*',$ret->link_price);
					 * $ret->link_price = str_replace('.',',',$ret->link_price);
					 * $ret->link_price = str_replace('*','.',$ret->link_price);
					 *
					 * }
					 */
					
					// fix price split
					if (stristr ( $ret->link_price, '-' )) {
						
						// echo '<br>Found Price:'.$ret->link_price;
						
						$priceParts = explode ( '-', $ret->link_price );
						
						$ret->link_price = $priceParts [0];
						$salePrice = $priceParts [1];
					} else {
						$salePrice = $ret->link_price;
					}
					
					$offer_title = $ret->link_title;
					
					$temp ['product_author'] = '';
					$temp ['product_brand'] = '';
					$temp ['product_isbn'] = '';
					
					if (stristr ( $offer_title, '**' )) {
						
						$titleParts = explode ( '**', $offer_title );
						
						$offer_title = $titleParts [0];
						$temp ['product_author'] = $titleParts [1];
						$temp ['product_brand'] = $titleParts [2];
						
						if (isset ( $titleParts [3] ))
							$temp ['product_isbn'] = $titleParts [3];
						
						if (isset ( $titleParts [4] )) {
							$temp ['product_upc'] = $titleParts [4];
						} else {
							$temp ['product_upc'] = '';
						}
					}
					
					$offer_desc = $ret->link_desc;
					
					$offer_desc = str_replace ( 'View larger.', '', $offer_desc );
					
					$offer_url = $ret->link_url . '?tag=' . $amazonAid;
					$offer_price = trim ( $ret->link_price );
					$offer_img = $ret->link_img;
					
					$temp ['offer_title'] = $offer_title;
					$temp ['product_title'] = $offer_title;
					$temp ['offer_desc'] = $offer_desc;
					$temp ['product_desc'] = $offer_desc;
					$temp ['offer_url'] = $offer_url;
					$temp ['product_link'] = $offer_url;
					$temp ['source_link'] = $ret->link_url;
					$temp ['offer_price'] = $offer_price;
					$temp ['product_price'] = $offer_price;
					$temp ['product_list_price'] = $salePrice;
					$temp ['offer_img'] = $offer_img;
					$temp ['product_img'] = $offer_img;
					$temp ['price_numeric'] = '00.00';
					$temp ['price_currency'] = '$';
					
					// increasing expiration date of the review
					$ret->link_review = preg_replace ( '{exp\=20\d\d}', 'exp=2030', $ret->link_review );
					$ret->link_review = str_replace ( 'http://', '//', $ret->link_review );
					
					$temp ['review_link'] ='';
					$temp ['review_iframe'] = '';
					
					// chart url
					
					$tag = '';
					$subscription = '';
					
					// API Method
					
					if (stristr ( $offer_url, 'creativeASIN' )) {
						
						$enc_url = urldecode ( $offer_url );
						$enc_url = explode ( '?', $enc_url );
						$enc_parms = $enc_url [1];
						$enc_parms_arr = explode ( '&', $enc_parms );
						
						foreach ( $enc_parms_arr as $param ) {
							
							if (stristr ( $param, 'creativeASIN' )) {
								$asin = str_replace ( 'creativeASIN=', '', $param );
							} elseif (stristr ( $param, 'tag=' )) {
								$tag = str_replace ( 'tag=', '', $param );
							} elseif (stristr ( $param, 'SubscriptionId' )) {
								$subscription = str_replace ( 'SubscriptionId=', '', $param );
							}
						}
					} else {
						// Scrape method
						$tag = $amazonAid;
					}
					
					echo '<br>Product found: <a href="' . $ret->link_url . '">' . $asin . '</a>';
					
					$temp ['product_asin'] = $asin;
					
					$region = $camp->camp_amazon_region;
					
					$chart_url = "https://www.amazon.$region/gp/aws/cart/add.html?AssociateTag=$tag&ASIN.1=$asin&Quantity.1=1&SubscriptionId=$subscription";
					
					$temp ['chart_url'] = $chart_url;
					
					// price extraction
					if (trim ( $ret->link_price ) != '') {
						
						$thousandSeparator = ',';
						
						// restore commas and dots
						if ($camp->camp_amazon_region == 'es' || $camp->camp_amazon_region == 'de' || $camp->camp_amazon_region == 'fr' || $camp->camp_amazon_region == 'it') {
							$thousandSeparator = '.';
						}
						
						// woo sousands separator
						if (class_exists ( 'WooCommerce' )) {
							$woocommerce_price_thousand_sep = get_option ( 'woocommerce_price_thousand_sep', '' );
							
							if ($woocommerce_price_thousand_sep == '.' || $woocommerce_price_thousand_sep == ',') {
								$thousandSeparator = $woocommerce_price_thousand_sep;
								echo '<br>Woo Thusand separator:' . $woocommerce_price_thousand_sep;
							}
						}
						
						echo '<br>Returned Price:' . $ret->link_price;
						
						
						
						// good we have a price
						$price_no_commas = str_replace ( $thousandSeparator, '', $offer_price );
						
						preg_match ( '{\d.*\d}is', ($price_no_commas), $price_matches );
						
						$temp ['price_numeric'] = $price_matches [0];
						$temp ['price_currency'] = str_replace ( $price_matches [0], '', $offer_price );
						
						// fixing listPrice
						$price_no_commas = str_replace ( $thousandSeparator, '', $salePrice );
						preg_match ( '{\d.*\d}is', ($price_no_commas), $price_matches );
						$temp ['list_price_numeric'] = $price_matches [0];
					}
					
					$this->used_keyword = $ret->link_keyword;
					
					// update the link status to 1
					$query = "delete from {$this->wp_prefix}automatic_amazon_links where link_id={$ret->link_id}";
					$this->db->query ( $query );
					
					// fix imgSet
					
					// fix imgSet
					
					$temp ['product_imgs'] = $temp ['product_img'];
					if (stristr ( $temp ['product_img'], ',' )) {
						// imageset found
						$imgs = explode ( ',', $temp ['product_imgs'] );
						
						$temp ['product_img'] = $temp ['offer_img'] = $imgs [0];
					}
					
					// imgs html
					$cg_am_full_img_t = @$camp_general ['cg_am_full_img_t'];
					
					if (trim ( $cg_am_full_img_t ) == '') {
						$cg_am_full_img_t = '<img src="[img_src]" class="wp_automatic_gallery" />';
					}
					
					$product_imgs_html = '';
					
					$allImages = explode ( ',', $temp ['product_imgs'] );
					$allImages_html = '';
					
					foreach ( $allImages as $singleImage ) {
						
						$singleImageHtml = $cg_am_full_img_t;
						$singleImageHtml = str_replace ( '[img_src]', $singleImage, $singleImageHtml );
						$allImages_html .= $singleImageHtml;
					}
					
					$temp ['product_imgs_html'] = $allImages_html;
					
					// product price with discount
					if ($temp ['product_price'] == $temp ['product_list_price']) {
						$temp ['price_with_discount_fixed'] = $temp ['product_price'];
					} else {
						$temp ['price_with_discount_fixed'] = '<del>' . $temp ['product_list_price'] . '</del> - ' . $temp ['product_price'];
					}
					
					// no price
					if (trim ( $temp ['product_price'] ) == '')
						$temp ['product_price'] = $temp ['price_numeric'];
					if (trim ( $temp ['product_list_price'] ) == '')
						$temp ['product_list_price'] = $temp ['price_numeric'];
					
						 
						
					return $temp;
				} else {
					
					// return false;
				}
			} // trim
		} // foreach keyword
	}
	
	/*
	 * ---* Get Amazon links ---
	 */
	function amazon_fetch_links($keyword, $camp) {
		echo "<br>Requesting links from Amazon for keyword: \"" . $keyword . '"...';
		
		// Amazon cookie
		$this->load_cookie ( 'wp_automatic_amazon' );
		
		// ini
		$md5 = md5 ( $keyword );
		$camp_opt = unserialize ( $camp->camp_options );
		
		if (stristr ( $camp->camp_general, 'a:' ))
			$camp->camp_general = base64_encode ( $camp->camp_general );
		$camp_general = unserialize ( base64_decode ( $camp->camp_general ) );
		
		$camp_general = array_map ( 'wp_automatic_stripslashes', $camp_general );
		$amazonAid = get_option ( 'wp_amazonpin_aaid', '' );
		
		// Search Keyword
		$clickkey = ($keyword);
		
		// getting start
		$query = "select  * from {$this->wp_prefix}automatic_keywords where keyword_name='$keyword' and keyword_camp = {$camp->camp_id} ";
		$ret = $this->db->get_results ( $query );
		
		$row = $ret [0];
		$start = $row->amazon_start;
		
		// deactivate check
		// check if the start = -1 this means the keyword is exhausted
		if ($start == '-1') {
			echo "<br>Keyword \"$keyword\" is already exhausted and does not have any new links to fetch.";
			
			// check if it is reactivated or still deactivated
			if ($this->is_deactivated ( $camp->camp_id, $keyword )) {
				$start = 1;
			} else {
				// still deactivated
				return false;
			}
		}
		
		echo '<br>Current page is :' . $start;
		
		if (1) {
			
			$moreUrl = get_post_meta ( $camp->camp_id, $md5 . '_more', 1 );
			
			$scrapePage = $start;
			
			echo '<br>Scrapping Amazon page ' . $scrapePage . ' for new items.';
			
			if (trim ( $moreUrl ) == '') {
				
				// custom URL
				if (in_array ( 'OPT_AMAZON_CUSTOM', $camp_opt ) && trim ( $camp_general ['cg_am_custom_urls'] ) != '') {
					
					$cg_am_custom_urls = $camp_general ['cg_am_custom_urls'];
					
					if (! in_array ( 'OPT_AM_NO_KEYS', $camp_opt )) {
						
						$keyword_enc = urlencode ( trim ( $keyword ) );
						$moreUrl = str_replace ( '[keyword]', $keyword_enc, $cg_am_custom_urls );
					} else {
						$moreUrl = $cg_am_custom_urls;
					}
				} else {
					if ($scrapePage == 1) {
						
						$moreUrl = "https://www.amazon.{$camp->camp_amazon_region}/s?k=" . urlencode ( trim ( $keyword ) ) . "&ref=nb_sb_noss_2";
						$moreUrl = "https://www.amazon.{$camp->camp_amazon_region}/s/ref=nb_sb_noss_2?url=search-alias%3Daps&field-keywords=" . urlencode ( trim ( $keyword ) );
					} else {
						
						// next pages, get the qid https://www.amazon.com/s?k=timtam&page=2&qid=1586957596&ref=sr_pg_2
						// https://www.amazon.com/s?k=timtam&page=3&qid=1586957596&ref=sr_pg_3
						
						$qid = $row->clickbank_start;
						
						if (! is_numeric ( $qid ) || $qid < 10)
							$qid = 1569443499;
						
						// $moreUrl = "https://www.amazon.{$camp->camp_amazon_region}/s?k=" . urlencode( trim($keyword) ) . "&qid=1569443499&ref=sr_pg_2";
						$moreUrl = "https://www.amazon.{$camp->camp_amazon_region}/s?k=" . urlencode ( trim ( $keyword ) ) . "&page=$scrapePage&qid=$qid&ref=sr_pg_$scrapePage";
					}
				}
			}
			
			// page number
			if ($scrapePage != 1) {
				if (! stristr ( $moreUrl, 'page=' )) {
					$scrapeURL = $moreUrl . '&page=' . $scrapePage;
				} else {
					$scrapeURL = $moreUrl;
				}
			} else {
				$scrapeURL = $moreUrl;
			}
			
			// high price and low price
			if (in_array ( 'OPT_AM_PRICE', $camp_opt )) {
				$cg_am_min = $camp_general ['cg_am_min'];
				$cg_am_max = $camp_general ['cg_am_max'];
				
				if (trim ( $cg_am_min ) != '' && is_numeric ( $cg_am_min )) {
					$scrapeURL .= '&low-price=' . $cg_am_min / 100;
				}
				
				if (trim ( $cg_am_max ) != '' && is_numeric ( $cg_am_max )) {
					$scrapeURL .= '&high-price=' . $cg_am_max / 100;
				}
			} else {
				
				if (stristr ( $scrapeURL, '?' )) {
					// $scrapeURL.= '&low-price=1';
				}
			}
			
			// sort
			if (in_array ( 'OPT_AM_ORDER', $camp_opt )) {
				$cg_am_order = trim ( $camp_general ['cg_am_order'] );
				if ($cg_am_order != '')
					$scrapeURL .= '&sort=' . $cg_am_order;
			}
			
			echo '<br>Scrape URL: ' . $scrapeURL;
			
			require_once (dirname ( __FILE__ ) . '/inc/class.amazon.api.less.php');
			
			$obj = new wp_automatic_amazon_api_less ( $this->ch, $camp->camp_amazon_region );
			
			try {
				
				$this->simulate_location ( $camp->camp_amazon_region );
				
				$agent = $this->get_user_agent ();
				curl_setopt ( $this->ch, CURLOPT_USERAGENT, $agent );
				
				if (preg_match ( '!^B0!', $keyword )) {
					
					if ($start == 1) {
						$ASINs = array (
								$keyword 
						);
					} else {
						$ASINs = array ();
					}
				} else {
					
					 
					$ASINs = $obj->getASINs ( $scrapeURL );
					
					if ($obj->update_agent_required)
						$this->update_user_agent ();
				}
				
				$slugs = $obj->slugs;
				
				$i = 0;
				foreach ( $ASINs as $ASIN ) {
					echo '<br>ASIN:' . $ASIN;
					$slug = '';
					
					if (isset ( $slugs [$i] ))
						$slug = $slugs [$i];
					// echo '<img src="http://images.amazon.com/images/P/' . $ASIN . '.01._PE30_PI_SCMZZZZZZZ_.jpg" />';
					
					$linkKeyword = "{$camp->camp_id}_$keyword";
					// $query = "INSERT INTO {$this->wp_prefix}automatic_amazon_links ( link_url , link_title , link_keyword , link_status ,link_desc,link_price,link_img,link_review)VALUES ( '$linkUrl', '$title', '{$camp->camp_id}_$keyword', '0','$desc','{$price}','{$imgurl}','{$review}')";
					$query = "INSERT INTO {$this->wp_prefix}automatic_amazon_links (link_url,link_keyword , link_status , link_desc) SELECT * FROM (SELECT '$ASIN' , '$linkKeyword' , '0' , '$slug' ) AS tmp WHERE NOT EXISTS ( SELECT link_url FROM {$this->wp_prefix}automatic_amazon_links WHERE link_url like '%$ASIN' ) LIMIT 1";
					
					$insert = $this->db->query ( $query );
					
					if ($insert != false) {
						echo '<-- NEW';
					}
					
					$i ++;
				}
				
				if (count ( $ASINs ) == 0 || preg_match ( '!^B0!', $keyword )) {
					
					// there was no links lets deactivate
					$newstart = '-1';
					$query = "update {$this->wp_prefix}automatic_keywords set amazon_start  = '$newstart' where keyword_name='$keyword'  and keyword_camp = {$camp->camp_id} ";
					$this->db->query ( $query );
					
					// deactivate key permanently
					if (! in_array ( 'OPT_NO_DEACTIVATE', $camp_opt ))
						$this->deactivate_key ( $camp->camp_id, $keyword, 0 );
					
					echo '<br>No more items at amazon to get deactivating the keyword permanently';
				} else {
					
					// coming reques qid, save it to clickbank_start
					if (isset ( $obj->next_request_qid ) && is_numeric ( $obj->next_request_qid )) {
						$qid_part = ", clickbank_start={$obj->next_request_qid} ";
					} else {
						$qid_part = "";
					}
					
					// increase start
					$newstart = $start + 1;
					$query = "update {$this->wp_prefix}automatic_keywords set  amazon_start  = '$newstart' $qid_part where keyword_name='$keyword'  and keyword_camp = {$camp->camp_id} ";
					$this->db->query ( $query );
				}
				
				return;
			} catch ( Exception $e ) {
				echo '<br>Exception: ' . $e->getMessage ();
				return;
			}
		}
	} // end func
	function simulate_location($region) {
		
		// disable this feature now
		return true;
		
		if ($this->isAmazonLocationSimulated == false) {
			
			// region to location
			if ($region == 'com') {
				$curlpost = "locationType=LOCATION_INPUT&zipCode=10001&storeContext=gateway&deviceType=web&pageType=Search&actionSource=glow";
			} elseif ($region == 'co.uk') {
				$curlpost = "locationType=LOCATION_INPUT&zipCode=E1+7EF&storeContext=generic&deviceType=web&pageType=Gateway&actionSource=glow";
			} elseif ($region == 'ca') {
				$curlpost = 'locationType=LOCATION_INPUT&zipCode=V5K+0A1&storeContext=generic&deviceType=web&pageType=Gateway&actionSource=glow';
			} elseif ($region == 'de') {
				$curlpost = "locationType=LOCATION_INPUT&zipCode=10178&storeContext=generic&deviceType=web&pageType=Gateway&actionSource=glow";
			} elseif ($region == 'fr') {
				$curlpost = "locationType=LOCATION_INPUT&zipCode=75000&storeContext=generic&deviceType=web&pageType=Gateway&actionSource=glow";
			} elseif ($region == 'it') {
				$curlpost = "locationType=LOCATION_INPUT&zipCode=00127&storeContext=generic&deviceType=web&pageType=Gateway&actionSource=glow";
			} elseif ($region == 'es') {
				$curlpost = "locationType=LOCATION_INPUT&zipCode=08005&storeContext=generic&deviceType=web&pageType=Gateway&actionSource=glow";
			} elseif ($region == 'co.jp') {
				$curlpost = "locationType=LOCATION_INPUT&zipCode=100-0000&storeContext=generic&deviceType=web&pageType=Gateway&actionSource=glow";
			} elseif ($region == 'in') {
				$curlpost = "locationType=LOCATION_INPUT&zipCode=110001&storeContext=generic&deviceType=web&pageType=Gateway&actionSource=glow";
			} elseif ($region == 'com.br') {
				$curlpost = "locationType=LOCATION_INPUT&zipCode=20010-000&storeContext=generic&deviceType=web&pageType=Gateway&actionSource=glow";
			} elseif ($region == 'com.mx') {
				$curlpost = "locationType=LOCATION_INPUT&zipCode=44100&storeContext=generic&deviceType=web&pageType=Gateway&actionSource=glow";
			}
			
			/*
			 * elseif(){
			 * $curlpost = "locationType=LOCATION_INPUT&zipCode=2000&storeContext=generic&deviceType=web&pageType=Gateway&actionSource=glow";
			 * //2000
			 *
			 * }
			 */
			
			echo '<br>Simulating location  for amazon.' . $region;
			
			// curl post
			$curlurl = "https://www.amazon.$region/gp/delivery/ajax/address-change.html";
			curl_setopt ( $this->ch, CURLOPT_URL, $curlurl );
			curl_setopt ( $this->ch, CURLOPT_POST, true );
			curl_setopt ( $this->ch, CURLOPT_POSTFIELDS, $curlpost );
			$x = 'error';
			$exec = curl_exec ( $this->ch );
			$x = curl_error ( $this->ch );
			
			$this->isAmazonLocationSimulated = true;
		}
	}
	
	/**
	 * Generate a new browser user agent and save it
	 * 
	 * @return mixed
	 */
	function update_user_agent() {
		$new_agent = $this->randomUserAgent ();
		update_option ( 'wp_automatic_amazon_agent', $new_agent );
		return $new_agent;
	}
	function get_user_agent() {
		
		$agent = trim(get_option ( 'wp_automatic_amazon_agent', '' ));
		
	 	if(  $agent  == '' || $agent == 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.163 Safari/537.36'){
			echo '<br>Generating new agent';
	 		return $this->update_user_agent ();
	 	}
		return $agent;	
	}
}