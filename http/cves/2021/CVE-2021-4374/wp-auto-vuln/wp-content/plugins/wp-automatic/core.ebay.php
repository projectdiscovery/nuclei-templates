<?php

// Main Class
require_once 'core.php';

Class WpAutomaticeBay extends wp_automatic{


/*
 * ebay fetch items
 */
function ebay_fetch_items($keyword, $camp) {

	$filter_number = 0; //ini
	//ref:https://docs.google.com/spreadsheet/ccc?key=0Auf5oUAL4RXDdHhiSFpUYjloaUFOM0NEQnF2d1FodGc&hl=en_US

	  echo "<br>so I should now get some items from ebay for keyword :" . $keyword;

	$campaignid = get_option ( 'wp_automatic_ebay_camp', '' );

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
		
		
		if ($start == - 1 ) {
			  echo '<- exhausted keyword';
			
			//check if it is reactivated or still deactivated
			if($this->is_deactivated($camp->camp_id, $keyword)){
				$start =1;
			}else{
				//still deactivated
				return false;
			}
			
			
		}
		
		  echo ' index:' . $start;
		
		// update start index to start+1
		if( ! in_array( 'OPT_EB_CACHE' , $camp_opt )){
			  echo '<br>Caching is not enabled setting eBay page to query to 1';
			$nextstart =1;
		}else{
			$nextstart = $start + 1;
		}
		
		 
		
		$query = "update {$this->wp_prefix}automatic_keywords set keyword_start = $nextstart where keyword_id=$kid ";
		$this->db->query ( $query );
		
	
	// prepare the link
	$elink = 'http://rest.ebay.com/epn/v1/find/item.rss?';
	$elink = 'https://svcs.ebay.com/services/search/FindingService/v1?OPERATION-NAME=findItemsAdvanced&SERVICE-VERSION=1.0.0&RESPONSE-DATA-FORMAT=JSON&REST-PAYLOAD=TRUE';

	$wp_automatic_ebay_app = trim(get_option('wp_automatic_ebay_app' , ''));
	if (trim ( $wp_automatic_ebay_app ) == '') {
		echo '<br><span style="color:red">Please visit the plugin settings page and add the required eBay APP ID</span>';
		$wp_automatic_ebay_app = "DeanDev07-ba44-495b-9106-d8d1c333fbb";
	}
	
	
	// Auth
	$elink .= '&SECURITY-APPNAME=' . $wp_automatic_ebay_app;

	// ebay site &GLOBAL-ID=EBAY-US
	$cg_eb_site = wp_automatic_fix_category($camp_general['cg_eb_site']);
	$elink .= '&GLOBAL-ID=' .  $cg_eb_site ;
	
	// campaign id
	if (trim ( $campaignid ) == '') {
		echo '<br><span style="color:orange">Please visit the plugin settings page and add the eBay campaign ID to get commisions</span>';
		$campaignid = 5338743934;
	} 
	
	//startpage paginationInput.pageNumber	, paginationInput.entriesPerPage	
	$elink.= '&paginationInput.pageNumber=' . $start;
	
	//Category
	if(in_array('OPT_EBAY_CUSTOM', $camp_opt) && trim($camp_general['cg_ebay_custom']) != '' ){
		
		$cg_ebay_custom = $camp_general['cg_ebay_custom'];
		
		if(stristr($cg_ebay_custom, ',')){
			
			$cg_ebay_custom_arr = explode(',',$cg_ebay_custom);
			$cg_ebay_custom_arr = array_filter($cg_ebay_custom_arr);
			
			$cg_ebay_custom = $cg_ebay_custom_arr[0];
			
			$cg_ebay_custom = isset($cg_ebay_custom_arr[1]) ? $cg_ebay_custom . '&categoryId(1)=' .trim($cg_ebay_custom_arr[1]) : $cg_ebay_custom ;
			$cg_ebay_custom = isset($cg_ebay_custom_arr[2]) ? $cg_ebay_custom . '&categoryId(2)=' .trim($cg_ebay_custom_arr[2]) : $cg_ebay_custom;
			
		}
		
		$elink .= '&categoryId(0)=' .trim( $cg_ebay_custom );
			
	}else{
			
		// ebay category cg_eb_cat
		$cg_eb_cat = $camp_general ['cg_eb_cat'];
			
		if (trim ( $cg_eb_cat != '0' )) {
			$elink .= '&categoryId=' . $cg_eb_cat;
		}
	}


	// if user
	if (in_array ( 'OPT_EB_USER', $camp_opt )) {
		$cg_eb_user = $camp_general ['cg_eb_user'];
		$elink .= "&itemFilter($filter_number).name=Seller&itemFilter($filter_number).value(0)=" . trim($cg_eb_user) ;
		$filter_number++;
		
		if (in_array ( 'OPT_EB_FULL', $camp_opt )) {
			  echo '<br>No filtering add all ..';
			//$elink .= '&keywords=';
		} else {
			// keyword
			$elink .= '&keywords=' . urlencode($keyword);
		}
	} else {
		// keyword
		$elink .= '&keywords=' . urlencode($keyword);
	}

	// listing type ListingType
	//$elink .= '&listingType1=' . $camp_general ['cg_eb_listing'];
	if( $camp_general ['cg_eb_listing'] != 'All' ){
		$elink .= "&itemFilter($filter_number).name=ListingType&itemFilter($filter_number).value(0)=" . $camp_general ['cg_eb_listing'] ;
		$filter_number++;
	}
	
	
	// price range
	if (in_array ( 'OPT_EB_PRICE', $camp_opt )) {
		$cg_eb_min = $camp_general ['cg_eb_min'];
		$cg_eb_max = $camp_general ['cg_eb_max'];
			
		// min
		if (trim ( $cg_eb_min ) != ''){
			$elink .= "&itemFilter($filter_number).name=MinPrice&itemFilter($filter_number).value(0)=" . trim($cg_eb_min) ;
			$filter_number++;
		}
		
		// max
		if (trim ( $cg_eb_max ) != ''){
			$elink .= "&itemFilter($filter_number).name=MaxPrice&itemFilter($filter_number).value(0)=" . trim($cg_eb_max) ;
			$filter_number++;
		}
	}

	// TopRatedSellerOnly
	if (in_array ( 'OPT_EB_TOP', $camp_opt )) {
		$elink .= "&itemFilter($filter_number).name=TopRatedSellerOnly&itemFilter($filter_number).value(0)=true";
		$filter_number++;
	}

	// FreeShippingOnly
	if (in_array ( 'OPT_EB_SHIP', $camp_opt )) {
		$elink .= "&itemFilter($filter_number).name=FreeShippingOnly&itemFilter($filter_number).value(0)=true";
		$filter_number++;
	}

	// OPT_EB_DESCRIPTION
	if (in_array ( 'OPT_EB_DESCRIPTION', $camp_opt )) {
		$elink .= '&descriptionSearch=true';
	}

	// append params
	if(in_array('OPT_EB_PARAM', $camp_opt)){
		$elink.= trim($camp_general['cg_eb_param']);
	}
	
	//affiliate affiliate.trackingId	
	$elink.= "&affiliate.networkId=9&affiliate.trackingId=".$campaignid;
	
	// listing order cg_eb_order
	$elink .= '&sortOrder=' . $camp_general ['cg_eb_order'];
	
	
	echo '<br>Link:' . $elink;
		
	// curl get
	$x = 'error';
	$url = $elink;
	curl_setopt ( $this->ch, CURLOPT_HTTPGET, 1 );
	curl_setopt ( $this->ch, CURLOPT_URL, trim ( $url ) );
	$exec = curl_exec ( $this->ch );
	$x = curl_error ( $this->ch );
 	
	 $json_reply = json_decode($exec);
	 
 
	
	 //error report  
	 if( isset ( $json_reply->findItemsAdvancedResponse[0]->errorMessage ) ){
	 	echo '<br>eBay returned an error: <span style="color:red">'.  $json_reply->findItemsAdvancedResponse[0]->errorMessage[0]->error[0]->message[0] . '</span>';
	 }
	 
	 if( isset ( $json_reply->errorMessage ) ){
	 	echo '<br>eBay returned an error: <span style="color:red">'.  $json_reply->errorMessage[0]->error[0]->message[0] . '</span>';
	 }
	 
	 $search_results = $json_reply->findItemsAdvancedResponse[0]->searchResult[0]->item;
	 
	
	 
	 echo '<br>Got ' . count($search_results) . ' items from eBay';
	 
	 /*
	 
	// titles
	preg_match_all ( '/<item><title>(.*?)<\/title>/', $exec, $matches );
	$titles = ($matches [1]);

	// imgs
	preg_match_all ( '/src=\'(.*?)\'/', $exec, $matches );
	$imgs = ($matches [1]);

	// links
	preg_match_all ( '/guid><link>(.*?)<\/link>/', $exec, $matches );
	$links = ($matches [1]);

	// ids <guid>
	preg_match_all ( '/guid>(.*?)<\/guid>/', $exec, $matches );
	$ids = ($matches [1]);

	// pubDate
	preg_match_all ( '/pubDate>(.*?)<\/pubDate>/', $exec, $matches );
	$pubdates = ($matches [1]);

	// bids count BidCount>0</e
	preg_match_all ( '/BidCount>(.*?)<\/e\:BidCount/', $exec, $matches );
	$bids = ($matches [1]);

	// current price <e:CurrentPrice>79.99</e:CurrentPrice>
	preg_match_all ( '/CurrentPrice>(.*?)<\/e\:CurrentPrice/', $exec, $matches );
	$prices = ($matches [1]);

	// bin BuyItNowPrice
	preg_match_all ( '/BuyItNowPrice>(.*?)<\/e\:BuyItNowPrice/', $exec, $matches );
	$bins = ($matches [1]);

	// listing end time ListingEndTime
	preg_match_all ( '/ListingEndTime>(.*?)<\/e\:ListingEndTime/', $exec, $matches );
	$ends = ($matches [1]);

	 */

	  
	 if ( count ( $search_results )== 0 || ( $json_reply->findItemsAdvancedResponse[0]->paginationOutput[0]->totalPages[0]  <   $start   ) || $start >100 ) {
	 
			
		  echo '<br>End of eBay search results, resetting page number';
		
		//set start to -1 exhausted
		$query = "update {$this->wp_prefix}automatic_keywords set keyword_start = -1 where keyword_id=$kid";
		$this->db->query ( $query );
		
		//deactivate for 60 minutes
		if(! in_array('OPT_NO_DEACTIVATE', $camp_opt))
			$this->deactivate_key($camp->camp_id, $keyword);
		
	}

	$i = 0;
	  echo '<ol>';
	  
	  
	  
	  foreach ( $search_results as $item_new ) {
		
	  	 
		 $id = $item_new->itemId[0];
		 $item_link = $item_new->viewItemURL[0];
		 
		 // eBay sg,hk link fix 
		 if( $cg_eb_site == 'EBAY-SG'){
		 
		 	$item_link = 'https://www.ebay.com.sg/itm/' . $id;
		 
		 }elseif($cg_eb_site == 'EBAY-HK'){
		 	
		 	$item_link = 'https://www.ebay.com.hk/itm/' . $id;
		 }
		 
		 //convert to new link
		 if(stristr($item_link, 'rover')){
		 	$item_link = $this->ebay_convert_rover_link($item_link, $cg_eb_site);
		 }
		 
		 echo '<li>Link:' . $item_link;
		 
		 
		$itm ['item_id'] = $id;
		$itm ['item_title'] = $item_new->title[0];
		
		$itm ['item_subtitle'] = isset($item_new->subtitle[0])? $item_new->subtitle[0] : '';
		$itm ['item_category'] = $item_new->primaryCategory[0]->categoryName[0];
		$itm ['item_payment'] = isset($item_new->paymentMethod[0]) ? $item_new->paymentMethod[0] : '' ;
		$itm ['item_postal'] = isset($item_new->postalCode[0])? $item_new->postalCode[0] : '' ;
		$itm ['item_location'] = $item_new->location[0];
		
		
		$itm ['item_img'] = $item_new->galleryURL[0];
		$itm ['item_link'] = $item_link ;
		$itm ['item_publish_date'] = str_replace ( 'T', ' ', str_replace ( 'Z', ' ',  $item_new->listingInfo[0]->startTime[0]  ) );
		$itm ['item_publish_date'] = str_replace ( '.000', '', $itm ['item_publish_date'] );
		$itm ['item_bids'] =  isset($item_new->sellingStatus[0]->bidCount[0]) ? $item_new->sellingStatus[0]->bidCount[0] : '' ;
		$itm ['item_price'] = $item_new->sellingStatus[0]->currentPrice[0]->__value__ ;
		$itm ['item_bin'] =    isset($item_new->listingInfo[0]->buyItNowPrice[0]->__value__) ? $item_new->listingInfo[0]->buyItNowPrice[0]->__value__ : '';
		$itm ['item_end_date'] = str_replace ( 'T', ' ', str_replace ( 'Z', ' ', $item_new->listingInfo[0]->endTime[0] ) );
		$itm ['item_end_date'] = str_replace ( '.000', '', $itm ['item_end_date'] );
			
		 
		$data = base64_encode(serialize ( $itm ));
			
			
		if( $this->is_execluded($camp->camp_id, $item_link) ){
			  echo '<-- Execluded';
			continue;
		}
			
		if ( ! $this->is_duplicate($item_link) )  {
			$query = "INSERT INTO {$this->wp_prefix}automatic_general ( item_id , item_status , item_data ,item_type) values (    '$id', '0', '$data' ,'eb_{$camp->camp_id}_$keyword')  ";
			$this->db->query ( $query );
		} else {
			  echo ' <- duplicated <a href="'.get_edit_post_link($this->duplicate_id).'">#'.$this->duplicate_id.'</a>';
		}
			
		$i ++;
			
		  echo '</li>';
			
	}

	  echo '</ol>';


	  echo '<br>' . $i . ' items from ebay';
}
	
/*
 * ebay get post
 */
function ebay_get_post($camp) {
	
	// Campaign options
	$camp_opt = unserialize ( $camp->camp_options );
	
	// Campaign Keywords
	$keywords = explode ( ',', $camp->camp_keywords );

	// General options
	if( stristr($camp->camp_general, 'a:') ) $camp->camp_general=base64_encode($camp->camp_general);
	$camp_general = unserialize ( base64_decode( $camp->camp_general) );
	$camp_general=array_map('wp_automatic_stripslashes', $camp_general);
		
	$cg_eb_site = wp_automatic_fix_category($camp_general['cg_eb_site']);

	// loop keywords
	foreach ( $keywords as $keyword ) {
			
		$keyword = trim($keyword);
			
		if (trim ( $keyword ) != '') {
				
			//update last keyword
			update_post_meta($camp->camp_id, 'last_keyword', trim($keyword));

			$this->used_keyword = $keyword;

			// getting links from the db for that keyword
			$query = "select * from {$this->wp_prefix}automatic_general where item_type=  'eb_{$camp->camp_id}_$keyword' ";
			$res = $this->db->get_results ( $query );

			// when no links lets get new links
			if (count ( $res ) == 0) {
				
				//clean any old cache for this keyword
				$query_delete = "delete from {$this->wp_prefix}automatic_general where item_type='eb_{$camp->camp_id}_$keyword' ";
				$this->db->query ( $query_delete );
				
				$this->ebay_fetch_items ( $keyword, $camp );
				// getting links from the db for that keyword
				$res = $this->db->get_results ( $query );
			}

			//check duplicate
			//deleting duplicated items
			$res_count = count($res);
			for($i=0;$i< $res_count ;$i++){

				$t_row = $res[$i];
				$t_data =  unserialize ( base64_decode($t_row->item_data) );

			
				
				//rover $t_data ['item_link']
				if(stristr($t_data ['item_link'], 'rover')){
					$t_data ['item_link'] = $this->ebay_convert_rover_link($t_data ['item_link'], $cg_eb_site);
					 
				}
			
			 		
				$t_link_url=$t_data ['item_link'];

				if( $this->is_duplicate($t_link_url) ){
						
					//duplicated item let's delete
					unset($res[$i]);
						
					  echo '<br>eBay item ('. $t_data['item_title'] .') found cached but duplicated <a href="'.get_permalink($this->duplicate_id).'">#'.$this->duplicate_id.'</a>'  ;
						
					//delete the item
					$query = "delete from {$this->wp_prefix}automatic_general where  id= {$t_row->id} ";
					$this->db->query ( $query );
						
				}else{
					break;
				}

			}

			// check again if valid links found for that keyword otherwise skip it
			if (count ( $res ) > 0) {
					
				// lets process that link
				$ret = $res [$i];
					
				$data = unserialize ( base64_decode($ret->item_data) );
				
				//rover $t_data ['item_link']
				if(stristr($data ['item_link'], 'rover')){
					$data ['item_link'] = $this->ebay_convert_rover_link($data ['item_link'], $cg_eb_site);
				}
				
				$item_id  =$data['item_id'];
					
				// get item big image and description
				// curl get
				$x = 'error';
				$url = $data ['item_link'];
					
				  echo '<br>Found Link:'.$url;
					
				$region = $cg_eb_site;

				$ext = $this->ebay_site_to_domain($cg_eb_site);
				 
					
				$the_link = $url ;
					
				  echo '<br>Item link with desc '.$the_link;

				//curl get
				$x='error';
				$url=$the_link;
				curl_setopt($this->ch, CURLOPT_HTTPGET, 1);
				curl_setopt($this->ch, CURLOPT_URL, trim($the_link));
				curl_setopt($this->ch,CURLOPT_HTTPHEADER,array('Cookie: ebay=%5Ecv%3D15555%5Esbf%3D%23100000%5Ejs%3D1%5E' ));
				$exec=$this->curl_exec_follow($this->ch);
				$x=curl_error($this->ch);
				
				//dom
				require_once 'inc/class.dom.php';
				$wpAutomaticDom = new wpAutomaticDom($exec);
					
				  
				// extract img itemprop="image" src="
				if(stristr($exec, 'maxImageUrl":"')){
					preg_match_all('{maxImageUrl":"(.*?)"}', $exec,$matches);
				}else{
					//displayImgUrl":"
					preg_match_all('{displayImgUrl":"(.*?)"}', $exec,$matches);
				}
					
				$all_imgs = array_unique($matches[1]);
				
				$json_txt= implode('","', $all_imgs);
				$json_txt = '["'.$json_txt.'"]';
					
				$imgs_arr = json_decode($json_txt);
					
				$img = $imgs_arr[0];
				
					
				if (trim ( $img ) != '') {
					$data ['item_img'] = $img;
				}
					
				// extract description
				$data['item_desc']=$data['item_title'];
				$data['item_images'] = '<img src="'.$data['item_img'] .'" />';
					
				// update the link status to 1
				$query = "delete from {$this->wp_prefix}automatic_general where id={$ret->id}";
				$this->db->query ( $query );
					
				$this->db->query ( $query );
					
				// if cache not active let's delete the cached items and reset indexes
				if (! in_array ( 'OPT_EB_CACHE', $camp_opt )) {
					 echo '<br>Cache disabled claring cache ...';
					$query = "delete from {$this->wp_prefix}automatic_general where item_type='eb_{$camp->camp_id}_$keyword' ";
					$this->db->query ( $query );
				}
					
					
				//if full description and all images needed extract them
				if(in_array('OPT_EB_FULL_DESC', $camp_opt) || in_array('OPT_EB_FULL_IMG', $camp_opt) || in_array('OPT_EB_FULL_DESC_SPEC', $camp_opt) ){

					  echo '<br>Extracting full description and images from original product page...';

					
					  
					//building url

					//extract ebay site ext
					$item_link=$data['item_link'] ;
 
					if(trim($exec) != ''){
						  	
						//specification box
						if(in_array('OPT_EB_FULL_DESC_SPEC', $camp_opt)){

						
							
							$ret2 = $wpAutomaticDom->getContentByClass('itemAttr' , false);
							
							 
							$extract2='';
								
							foreach ($ret2 as $itm ) {
								$extract2 = $extract2 . $itm ;
							}
								
							if(trim($extract2) == ''){
								  echo '<br>Nothing found to extract for itemAttr';
							}else{
								  echo '<br>Rule itemAttr extracted ' . strlen($extract2) .' charchters ';

								$extract2 = preg_replace('{<span id="hiddenContent.*?span>}', '</td>', $extract2);
								$extract2 = preg_replace('{<span id="readFull.*?span>}', '</td>', $extract2);

									
								$extract2 = str_replace('50.0%', '30.0%', $extract2);

								$data['item_desc'] = $extract2.$data['item_desc'];
									
								
							}
							
							 
							
							//prodDetailDesc
							$ret3 = $wpAutomaticDom->getContentByClass('prodDetailDesc', false);	
							
							$extract3='';
								
							foreach ($ret3 as $itm ) {
								$extract3 = $extract3 . $itm ;
							}
								
							if(trim($extract3) == ''){
								  echo '<br>Nothing found to extract for item prodDetailDesc';
							}else{
								  echo '<br>Rule prodDetailDesc extracted ' . strlen($extract3) .' charchters ';
									
								$extract3 = preg_replace('{<span id="hiddenContent.*?span>}', '</td>', $extract3);
								$extract3 = preg_replace('{<span id="readFull.*?span>}', '</td>', $extract3);
									
									
								$data['item_desc'] = $data['item_desc']. $extract3;
									
							}
								
								
								
						}
						
						
							
							
						if(in_array('OPT_EB_FULL_DESC', $camp_opt)){

							//getting iframe <iframe id="desc_ifr
							if(1){
									
								$ret = $wpAutomaticDom->getContentByID('desc_ifr' , false);

								
								$extract='';

								foreach ($ret as $itm ) {
									$extract = $extract . $itm ;
								}

								if(trim($extract) == ''){
									  echo '<br>Nothing found to extract for desc_ifr';
								}else{
									  echo '<br>Rule desc_ifr extracted ' . strlen($extract) .' charchters ';


									if ( trim( $camp_general['cg_eb_iframe_h'] ) == '' ){
										$camp_general['cg_eb_iframe_h'] =  500;
									}

									$extract = str_replace('height="10000"', 'height="'.$camp_general['cg_eb_iframe_h'].'"', $extract);
										
									$data['item_desc'] =$data['item_desc'].  $extract;
										

								}
									
									

							}else{
								  echo '<br>Simple html dom can not load the html';
							}
								
						}// OPT_EB_FULL_DESC
							
						//extracting images
						if( in_array('OPT_EB_FULL_IMG', $camp_opt) )  {

								

							if( count($imgs_arr) > 0 ){


								//form html
								$data['item_images'] = $imgs_arr;

									
									
							}else{
								  echo '<br>did not find additional images from original source';
							}

						}//OPT_EB_FULL_IMG
						
						//remove see all conditions link <span id="seeAll , hiddenContent
						$data['item_desc'] = preg_replace( '{<span id=".*?span>}s' , ''  , $data['item_desc'] );
						$data['item_desc'] = preg_replace( '{<!--.*?-->}s' , ''  , $data['item_desc'] );
						
					}else{
						  echo '<br>Can not load original product page';
					}
 
				}
					
 				
				$data['item_end_date'] = get_date_from_gmt($data['item_end_date']);
				$data['item_publish_date'] = get_date_from_gmt($data['item_publish_date']);
					
				// Prices .0 fix to .00
				$data['item_price'] = number_format($data['item_price'],2);
				
				//remove , from price
				$data['item_price_numeric'] = str_replace(',','', $data['item_price'] );
				
				if( trim($data['item_bin']) != '' &&  is_float($data['item_bin']))
				$data['item_bin'] = number_format($data['item_bin'],2);
			 
				//seller
				$seller_arr = $wpAutomaticDom->getContentByClass('mbg-nw' );
				
				$data['item_seller_username'] = '' ;
				$data['item_seller_url'] = '' ; 
				
				if(isset( $seller_arr[0] ) && trim( $seller_arr[0]) != ''){
					$data['item_seller_username'] =  $seller_arr[0];
					$data['item_seller_url'] = "http://www.$ext/usr/$seller_arr[0]";
				}
				
				//item location
				$data['item_location'] = '';
				
				$location_arr = $wpAutomaticDom->getContentByXPath( "//*[@itemprop='availableAtOrFrom']" );
				
				if(isset( $location_arr[0] ) && trim( $location_arr[0]) != ''){
					$data['item_location'] = $location_arr[0];
				}
				

				//areaServed ships to sh-gspShipsTo
				$data['item_ships_to'] = '';
				
				$ships_to_arr = $wpAutomaticDom->getContentByXPath( "//*[@itemprop='areaServed']" );
				
				if(isset( $ships_to_arr[0] ) && trim( $ships_to_arr[0]) != ''){
					$data['item_ships_to'] = trim(preg_replace( '{<span.*}s' , '' ,  $ships_to_arr[0] ) );
				}
				
				if(trim($data['item_ships_to']) == ''){
					
					//sh-gspShipsTo
					$ships_to_arr = $wpAutomaticDom->getContentByXPath( "//*[@class='sh-gspShipsTo']" );
					
					if(isset( $ships_to_arr[0] ) && trim( $ships_to_arr[0]) != ''){
						$data['item_ships_to'] = trim(preg_replace( '{<.*}s' , '' ,  $ships_to_arr[0] ) );
					}
				}
				
				$data['item_ships_to'] = trim(str_replace('|' , ' ' , $data['item_ships_to'] ) ) ;
				
				//item condition itemCondition
				$data['item_condition'] = '';
				
				$arr = array();
				$arr = $wpAutomaticDom->getContentByXPath( "//*[@itemprop='itemCondition']" );
				
				if(isset( $arr[0] ) && trim( $arr[0]) != ''){
					$data['item_condition'] = $arr[0];
				}
				
				//return policy vi-ret-accrd-txt
				$data['item_return_policy'] = '';
				
				$arr = array();
				$arr = $wpAutomaticDom->getContentByID( "vi-ret-accrd-txt" );
				
				if(isset( $arr[0] ) && trim( $arr[0]) != ''){
					$data['item_return_policy'] = $arr[0];
				}
				
				//delivery after payment sh-DlvryDtl class
				$data['item_shipping_start'] = '';
				
				$arr = array();
				$arr = $wpAutomaticDom->getContentByClass( "sh-DlvryDtl" );
				
				if(isset( $arr[0] ) && trim( $arr[0]) != ''){
					$data['item_shipping_start'] = $arr[0];
				}
				
				//auction or bin id="bidBtn_btn" 
				if(stristr($exec, 'id="bidBtn_btn"' )){
					$data['item_listing_type'] = 'auction';
				}else{
					$data['item_listing_type'] = 'buy it now	';
				}
				
				
				return $data;
				
			
				
			} else {
					
				  echo '<br>No links found for this criteria';
			}
		} // if trim
	} // foreach keyword
}



/**
 * Convert rover links to new links format
 * @param unknown $rover
 * @param unknown $ebay_site
 */
function ebay_convert_rover_link($rover , $cg_eb_site){
	
	//Converting:https://rover.ebay.com/rover/1/711-53200-19255-0/1?ff3=2&toolid=10044&campid=5338743934&customid=&lgeo=1&vectorid=229466&item=264920110296
	//Found Link:https://www.ebay.com/itm/264920110296?mkrid=711-53200-19255-0&siteid=0&mkcid=1&campid=5338743934&toolid=10044&customid=&mkevt=1
	
	$parse_url = parse_url ( $rover );
	$path_parts = explode ( '/', $parse_url ['path'] );
	$mkrid = $path_parts [3];
	$domain = $this->ebay_site_to_domain ( $cg_eb_site );
	$siteid = $this->ebay_region_to_siteid ( $cg_eb_site );
	
	parse_str ( $parse_url ['query'], $params );
	
	$correct_url = "https://www.{$domain}/itm/{$params['item']}?mkrid={$mkrid}&siteid={$siteid}&mkcid=1&campid={$params['campid']}&toolid=10044&customid=&mkevt=1";
	
	
	return $correct_url;
	
	
}

function ebay_site_to_domain($cg_eb_site) {
	switch ($cg_eb_site) {
		
		case 'EBAY-US' :
			return 'ebay.com';
			break;
		case 'EBAY-ENCA' :
			return 'ebay.ca';
			break;
		case 'EBAY-GB' :
			return 'ebay.co.uk';
			break;
		case 'EBAY-AU' :
			return 'ebay.com.au';
			break;
		case 'EBAY-AT' :
			return 'ebay.at';
			break;
		case 'EBAY-FRBE' :
			return 'befr.ebay.be';
			break;
		case 'EBAY-FR' :
			return 'ebay.fr';
			break;
		case 'EBAY-DE' :
			return 'ebay.de';
			break;
		case 'EBAY-MOTOR' :
			return 'ebay.com';
			break;
		case 'EBAY-IT' :
			return 'ebay.it';
			break;
		case 'EBAY-NLBE' :
			return 'befr.ebay.be';
			break;
		case 'EBAY-NL' :
			return 'ebay.nl';
			break;
		case 'EBAY-ES' :
			return 'ebay.es';
			break;
		case 'EBAY-CH' :
			return 'ebay.ch';
			break;
		case 'EBAY-HK' :
			return 'ebay.com.hk';
			break;
		case 'EBAY-IN' :
			return 'ebay.com';
			break;
		case 'EBAY-IE' :
			return 'ebay.ie';
			break;
		case 'EBAY-MY' :
			return 'ebay.com.my';
			break;
		case 'EBAY-FRCA' :
			return 'cafr.ebay.ca';
			break;
		case 'EBAY-PH' :
			return 'ebay.ph';
			break;
		case 'EBAY-PL' :
			return 'ebay.pl';
			break;
		case 'EBAY-SG' :
			return 'ebay.com.sg';
			break;
			
	}
}

function ebay_region_to_siteid($cg_eb_site) {
	switch ($cg_eb_site) {
		
		case 'EBAY-US' :
			return '0';
			break;
		case 'EBAY-ENCA' :
			return '2';
			break;
		case 'EBAY-GB' :
			return '3';
			break;
		case 'EBAY-AU' :
			return '15';
			break;
		case 'EBAY-AT' :
			return '16';
			break;
		case 'EBAY-FRBE' :
			return '23';
			break;
		case 'EBAY-FR' :
			return '71';
			break;
		case 'EBAY-DE' :
			return '77';
			break;
		case 'EBAY-MOTOR' :
			return '0';
			break;
		case 'EBAY-IT' :
			return '101';
			break;
		case 'EBAY-NLBE' :
			return '23';
			break;
		case 'EBAY-NL' :
			return '146';
			break;
		case 'EBAY-ES' :
			return '186';
			break;
		case 'EBAY-CH' :
			return '193';
			break;
		case 'EBAY-HK' :
			return '201';
			break;
		case 'EBAY-IN' :
			return '0';
			break;
		case 'EBAY-IE' :
			return '205';
			break;
		case 'EBAY-MY' :
			return '207';
			break;
		case 'EBAY-FRCA' :
			return '210';
			break;
		case 'EBAY-PH' :
			return '211';
			break;
		case 'EBAY-PL' :
			return '212';
			break;
		case 'EBAY-SG' :
			return '216';
			break;
	}
}

}