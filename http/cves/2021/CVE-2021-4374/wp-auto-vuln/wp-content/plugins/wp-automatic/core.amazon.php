<?php

// Main Class
require_once 'core.php';
class WpAutomaticAmazon extends wp_automatic {
	
	/*
	 * ---* Get Amazon Post ---
	 */
	function amazon_get_post($camp) {
		
		// affiliate ID
		$amazonAid = get_option ( 'wp_amazonpin_aaid', '' );
		
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
					
					// get amazon item by ASIN if not complete info
					$was_an_asin = false; // to skip duplicate check again as asin get already duplicate checked
					
					if (! stristr ( $t_link_url, 'http' )) {
						
						$was_an_asin = true;
						
						$asin = $t_link_url;
						
						echo '<br>Found ASIN:' . $asin;
						
						// check if duplicate or excluded before getting detailed information
						$t_link_url = $linkUrl = 'https://amazon.' . $camp->camp_amazon_region . '/dp/' . $asin;
						
						if ($this->is_execluded ( $camp->camp_id, $linkUrl )) {
							echo '<-- Execluded';
							continue;
						}
						
						if ($this->is_duplicate ( $t_link_url )) {
							
							// duplicated item let's delete
							unset ( $res [$i] );
							
							echo '<br>Amazon Item (' . $t_link_url . ') found cached but duplicated <a href="' . get_permalink ( $this->duplicate_id ) . '">#' . $this->duplicate_id . '</a>';
							
							// delete the item
							$query = "delete from {$this->wp_prefix}automatic_amazon_links where link_id={$t_row->link_id}";
							$this->db->query ( $query );
							continue;
						}
						
						echo ' getting complete details from Amazon...';
						
						$amazonPublic = get_option ( 'wp_amazonpin_abk', '' );
						$amazonSecret = get_option ( 'wp_amazonpin_apvtk', '' );
						
						$obj = new wp_automatic_AmazonProductAPI ( trim ( $amazonPublic ), trim ( $amazonSecret ), trim ( $amazonAid ), $camp->camp_amazon_region );
						
						try {
							
							$result = $obj->getItemByAsin ( $asin );
							$Item = $result->Items->Item;
						} catch ( Exception $e ) {
							echo '<br>Amazon error:' . $e->getMessage ();
						}
						
						if (isset ( $Item->ASIN ) && isset ( $Item->DetailPageURL )) {
							
							echo " Link : <a href=\"{$Item->DetailPageURL}\">{$Item->ItemAttributes->Title}</a> <br>";
							
							$desc = '';
							@$desc = $Item->EditorialReviews->EditorialReview->Content;
							
							// Features existence
							if (isset ( $Item->ItemAttributes->Feature )) {
								echo '-- Features found appending to the description';
								$desc .= implode ( '<br>', ( array ) $Item->ItemAttributes->Feature );
							}
							
							$title = ( string ) $Item->ItemAttributes->Title;
							
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
							
							$price = '';
							
							$price = @$Item->Offers->Offer->OfferListing->SalePrice->FormattedPrice;
							
							if (trim ( $price ) == '') {
								$price = @$Item->Offers->Offer->OfferListing->Price->FormattedPrice;
							}
							if (trim ( $price ) == '') {
								@$price = $Item->ItemAttributes->ListPrice->FormattedPrice;
							}
							
							if (trim ( $price ) == '') {
								@$price = $Item->OfferSummary->LowestNewPrice->FormattedPrice;
							}
							
							if (trim ( $price ) == '') {
								@$price = $Item->OfferSummary->LowestCollectiblePrice->FormattedPrice;
							}
							
							if (trim ( $price ) == '') {
								@$price = $Item->OfferSummary->LowestUsedPrice->FormattedPrice;
							}
							
							if (trim ( $price ) == '') {
								@$price = $Item->VariationSummary->LowestPrice->FormattedPrice;
							}
							
							// list price
							$ListPrice = $Item->ItemAttributes->ListPrice->FormattedPrice;
							
							if (trim ( $ListPrice ) == '') {
								$ListPrice = $price;
							}
							
							// final saved price price,listprice
							$price = $price . '-' . $ListPrice;
							
							$imgurl = '';
							$imgurl = $Item->LargeImage->URL;
							
							if (trim ( $imgurl ) == '') {
								// get it from the sets
								@$imgurl = $Item->ImageSets->ImageSet [0]->LargeImage->URL;
							}
							
							if (trim ( $imgurl ) == '') {
								// get it from the sets
								@$imgurl = $Item->Variations->Item [0]->LargeImage->URL;
							}
							
							// Checking for an image set
							if (trim ( $imgurl ) != '') {
								$imgSets = array ();
								$img2url = '';
								$imgs = array ();
								@$img2url = $Item->ImageSets->ImageSet [1]->LargeImage->URL;
								
								if (trim ( $img2url ) != '') {
									// found an image set
									$imgSets = $Item->ImageSets->ImageSet;
									foreach ( $imgSets as $imgSet ) {
										$imgs [] = $imgSet->LargeImage->URL;
									}
									
									if (count ( $imgs ) > 0) {
										$imgs = implode ( ',', $imgs );
										$imgurl = $imgs;
									}
								}
							}
							
							// review url
							$review = $Item->CustomerReviews->IFrameURL;
							
							// building the item
							$t_row->link_url = $linkUrl;
							$t_row->link_title = $title;
							$t_row->link_desc = ( string ) $desc;
							$t_row->link_price = ( string ) $price;
							$t_row->link_img = ( string ) $imgurl;
							$t_row->link_review = ( string ) $review;
							
							$res [$i] = $t_row;
						} else { // valid returned item
						         
							// delete non valid item
							$query = "delete from {$this->wp_prefix}automatic_amazon_links where link_id={$t_row->link_id}";
							$this->db->query ( $query );
							
							unset ( $res [$i] );
						}
					} // ASIN product
					
					if (! $was_an_asin && $this->is_duplicate ( $t_link_url )) {
						
						// duplicated item let's delete
						unset ( $res [$i] );
						
						echo '<br>Amazon Item (' . $t_row->link_title . ') found cached but duplicated <a href="' . get_permalink ( $this->duplicate_id ) . '">#' . $this->duplicate_id . '</a>';
						
						// delete the item
						$query = "delete from {$this->wp_prefix}automatic_amazon_links where link_id={$t_row->link_id}";
						$this->db->query ( $query );
					} else {
						if (stristr ( $t_link_url, 'http' ))
							break;
					}
				}
				
				// check again if valid links found for that keyword otherwise skip it
				if (count ( $res ) > 0) {
					
					// lets process that link
					$ret = $res [$i];
					
					 
					// ;
					
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
					
					$offer_url = $ret->link_url;
					
					$temp ['source_link'] = $offer_url;
					
					$offer_price = trim ( $ret->link_price );
					$offer_img = $ret->link_img;
					
					if (! stristr ( $offer_url, 'tag' )) {
						$offer_url .= '?tag=' . $amazonAid;
					}
					
					$temp ['offer_title'] = $offer_title;
					$temp ['product_title'] = $offer_title;
					$temp ['offer_desc'] = $offer_desc;
					$temp ['product_desc'] = $offer_desc;
					$temp ['offer_url'] = $offer_url;
					$temp ['product_link'] = $offer_url;
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
					
					$temp ['review_link'] = '';
					$temp ['review_iframe'] = '';
					
					// chart url
					if (stristr ( $offer_url, 'creativeASIN' )) {
						
						$enc_url = urldecode ( $offer_url );
						$enc_url = explode ( '?', $enc_url );
						$enc_parms = $enc_url [1];
						$enc_parms_arr = explode ( '&', $enc_parms );
						
						$asin = '';
						$tag = '';
						$subscription = '';
						
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
						
						$link_parts = explode ( 'dp/', $ret->link_url );
						
						$asin = $link_parts [1];
					}
					
					echo '<br>Product found: <a href="' . $ret->link_url . '">' . $asin . '</a>';
					
					$temp ['product_asin'] = $asin;
					
					$region = $camp->camp_amazon_region;
					
					$chart_url = "https://www.amazon.{$camp->camp_amazon_region}/gp/aws/cart/add.html?AssociateTag=$amazonAid&ASIN.1=$asin&Quantity.1=1";
					
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
					$temp ['product_imgs'] = $temp ['product_img'];
					if (stristr ( $temp ['product_img'], ',' )) {
						// imageset found
						$imgs = explode ( ',', $temp ['product_imgs'] );
						
						// swap last image to the top
						$imgsCount = count ( $imgs );
						$lastImageIndex = $imgsCount - 1;
						$lastImage = $imgs [$lastImageIndex];
						unset ( $imgs [$lastImageIndex] );
						
						if (! stristr ( ($lastImage), 'jpg' )) {
							$imgs = array_merge ( array (), $imgs );
						} else {
							$imgs = array_merge ( array (
									$lastImage 
							), $imgs );
						}
						
						$temp ['product_imgs'] = implode ( ',', $imgs );
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
		echo "<br>Requesting new products from Amazon for the keyword: \"" . $keyword . '"...';
		
		// ini options
		$md5 = md5 ( $keyword );
		$camp_opt = unserialize ( $camp->camp_options );
		
		if (stristr ( $camp->camp_general, 'a:' ))
			$camp->camp_general = base64_encode ( $camp->camp_general );
		$camp_general = unserialize ( base64_decode ( $camp->camp_general ) );
		
		$camp_general = array_map ( 'wp_automatic_stripslashes', $camp_general );
		$amazonPublic = get_option ( 'wp_amazonpin_abk', '' );
		$amazonSecret = get_option ( 'wp_amazonpin_apvtk', '' );
		$amazonAid = get_option ( 'wp_amazonpin_aaid', '' );
		
		if (trim ( $amazonPublic ) == '' || trim ( $amazonSecret ) == '' || trim ( $amazonAid ) == '') {
			$this->log ( 'Error', 'Amazon Public Key,Private Key and associate id required visit settings and add them' );
			echo '<br>Amazon Public Key,Private Key and associate id required visit settings and add them';
			return false;
		}
		
		// Search keyword
		$clickkey = ($keyword);
		
		// Getting start page
		$query = "select  * from {$this->wp_prefix}automatic_keywords where keyword_name='$keyword' and keyword_camp = {$camp->camp_id} ";
		$ret = $this->db->get_results ( $query );
		
		$row = $ret [0];
		$start = $row->amazon_start;
		
		// max api pages
		$maxApiPages = 10;
		
		// check if scrapping
		$first_sort_by_high_call = false; //flag for setting click_key to 2 if true
		if ( ! in_array('OPT_AMAZON_NOMAGIC' , $camp_opt) && ($start > $maxApiPages ||  $row->clickbank_start != 1) ) {
			
			
			//set order to high to low
			$camp_general ['cg_am_order'] = "Price:HighToLow";
			$camp_opt[] = "OPT_AM_ORDER";
			
			//max price which is lowest price returned last call (clickbank_start field will contain the max price)
			if($row->clickbank_start > 1){
				
				if( is_numeric($camp_general ['cg_am_max']) && $camp_general ['cg_am_max'] < $row->clickbank_start   ){
					//do nothing let the set value overwrite
				}else{
					$camp_general ['cg_am_max'] = $row->clickbank_start ;
				}
				
				$camp_opt[] = "OPT_AM_PRICE";
				
				echo '<br>Getting more products ordered by price high to low and is cheaper than '.$camp_general ['cg_am_max'] / 100 ;
				
			}elseif( $row->clickbank_start == 1 ){
				
				echo '<br>Getting more products ordered by price high to low';
				
				//first sorty by high call 
				$first_sort_by_high_call = true;
				
			}else{
				//0
				
				echo '<br>Getting more products ordered by price high to low';
				
			}
			
			//reset start page to 1
			if($start > $maxApiPages) 	$start = 1;
	
		}
		
		// check if the start = -1 this means the keyword is exhausted
		if ($start == '-1' || $start == 11) {
			echo "<br>Keyword \"$keyword\" is already exhausted and does not have any new links to fetch.";
			
			// check if it is reactivated or still deactivated
			if ($this->is_deactivated ( $camp->camp_id, $keyword )) {
				$start = 1;
			} else {
				// still deactivated
				return false;
			}
		}
		
		echo '<br>Current search page number is :' . $start . '/10';
		
		// amazon
		
		$obj = new wp_automatic_AmazonProductAPI ( trim ( $amazonPublic ), trim ( $amazonSecret ), trim ( $amazonAid ), $camp->camp_amazon_region );
		$obj->ch = $this->ch;
		
		try {
			
			// additional params
			$additionalParm = array ();
			
			// node param
			if (in_array ( 'OPT_AMAZON_NODE', $camp_opt ) & trim ( $camp_general ['cg_am_node'] ) != '') {
				echo '<br>Specific node : ' . $camp_general ['cg_am_node'];
				$additionalParm ['BrowseNodeId'] = $camp_general ['cg_am_node'];
			}
			
			// min and max param
			$max = '';
			$min = '';
			
			if (in_array ( 'OPT_AM_PRICE', $camp_opt )) {
				$min = $camp_general ['cg_am_min'];
				$max = $camp_general ['cg_am_max'];
				
				echo '<br>Price range ' . $min . ' - ' . $max;
			}
			
			// search param
			if (in_array ( 'OPT_AMAZON_PARAM', $camp_opt )) {
				$additionalParm [$camp_general ['cg_am_param_type']] = $camp_general ['cg_am_param'];
			}
			
			// order param
			if (in_array ( 'OPT_AM_ORDER', $camp_opt )) {
				$additionalParm ['SortBy'] = $camp_general ['cg_am_order'];
			}
			
			// amazon merchant
			if (in_array ( 'OPT_AMAZON_MERCHANT', $camp_opt )) {
				$additionalParm ['Merchant'] = 'Amazon';
			}
			
			// condition parameter
			if (in_array ( 'OPT_AM_CONDITION', $camp_opt )) {
				$additionalParm ['Condition'] = $camp_general ['cg_am_condition'];
			}
			
			if (stristr ( $clickkey, 'B0' )) {
				$result = $obj->getItemByAsin ( $clickkey );
			} else {
				$result = $obj->getItemByKeyword ( "$clickkey", $start, $camp->camp_amazon_cat, $additionalParm, $min, $max );
			}
		} catch ( Exception $e ) {
			$this->log ( 'Amazon Error', $e->getMessage () );
			echo '<br>' . $e->getMessage ();
			return false;
		}
		
		//echo ' <pre>';
		//print_r ( $result );
		
		if (count ( $result ) != 0) {
			
			// $pagesNum = $result->Items->TotalPages;
			// echo '<br>Available Pages:' . $pagesNum;
			
			$camp_cb_category = $camp->camp_cb_category;
			
			if (isset ( $result->Items->MoreSearchResultsUrl )) {
				$moreUrl = $result->Items->MoreSearchResultsUrl;
				$moreUrl .= '';
				
				if (stristr ( $moreUrl, 'location' )) {
					$locParts = explode ( 'location=', $moreUrl );
					$locParts2 = explode ( '&', $locParts [1] );
					$finalMore = $locParts2 [0];
					
					if (trim ( $finalMore ) != '')
						$moreUrl = urldecode ( $finalMore );
				}
				
				echo '<br>More items url:' . $moreUrl;
				update_post_meta ( $camp->camp_id, $md5 . '_more', $moreUrl );
			}
			
			echo '<ol>';
			
			$last_price = ''; 
			
			foreach ( $result as $Item ) {
				 
				echo "<li>ASIN : {$Item->ASIN}  Link : <a href=\"{$Item->DetailPageURL}\">{$Item->ItemInfo->Title->DisplayValue}</a> <br>";
				
				// Product URL
				$linkUrl = 'https://amazon.' . $camp->camp_amazon_region . '/dp/' . $Item->ASIN;
				
				// Excluded?
				if ($this->is_execluded ( $camp->camp_id, $linkUrl )) {
					echo '<-- Execluded';
					continue;
				}
				
				// no price?
				if(! isset($Item->Offers)){
					echo '<-- No price';
					continue;
				}
				
				//numeric price grab
				if(isset($Item->Offers)){
				$price_numeric = $Item->Offers->Listings[0]->Price->Amount;
				$last_price = $price_numeric * 100;
				echo '<--last price:'.$last_price;
				}
				
				// Duplicate?
				if (! $this->is_duplicate ( $linkUrl )) {
					
					$desc = '';
					
					// Features existence
					if (isset ( $Item->ItemInfo->Features )) {
						echo '-- Features found appending to the description';
						$desc .= implode ( '<br>', $Item->ItemInfo->Features->DisplayValues );
					}
					
					$desc = addslashes ( $desc );
					$title = addslashes ( $Item->ItemInfo->Title->DisplayValue );
					
					
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
					
					// current price
					$price = '';
					$price = $Item->Offers->Listings[0]->Price->DisplayAmount;
					$price = trim (preg_replace( '{\(.*?\)}' , '' , $price ) );
					$price_numeric = $Item->Offers->Listings[0]->Price->Amount;
					$last_price = $price_numeric * 100;
					
					// list price
					$ListPrice = '';
					
					if(isset( $Item->Offers->Listings[0]->Price->Savings )){
						$ListPrice = $Item->Offers->Listings[0]->Price->Savings->Amount + $price_numeric;  
						$ListPrice = str_replace( $price_numeric , $ListPrice , $price );
						
					}
					
					if (trim ( $ListPrice ) == '') {
						$ListPrice = $price;
					}
					
					// final saved price price-listprice
					$price = $price . '-' . $ListPrice;
					
					echo '<br>Price:'.$price;
					
					// Product Image
					$imgurl = '';
					$imgurl = $Item->Images->Primary->Large->URL;
					
					// Checking for an image set
					
					if (trim ( $imgurl ) != '') {
					
						$imgs = array();
						
						if (isset ( $Item->Images->Variants )) {
							
							foreach ( $Item->Images->Variants as $imgSet ) {
								$imgs [] = $imgSet->Large->URL;
							}
							
							if (count ( $imgs ) > 0) {
								$imgs = implode ( ',', $imgs );
								$imgurl =     $imgs . ',' . $imgurl ;
							}
						}
					}
					 
					
					// review url
					$review = "https://www.amazon.{$camp->camp_amazon_region}/reviews/iframe?akid=AKIAJDYHK6WW2AYDNYJA&alinkCode=xm2&asin={$Item->ASIN}&atag={$amazonAid}&exp=2030-07-19T16%3A07%3A21Z&v=2&sig=ofoCKfF6T0LDaPzBPX%252BB2tnjuzE3gCl%252BstWxTFdnCJQ%253D";
				 
					 $query = "INSERT INTO {$this->wp_prefix}automatic_amazon_links ( link_url , link_title , link_keyword  , link_status ,link_desc,link_price,link_img,link_review)VALUES ( '$linkUrl', '$title', '{$camp->camp_id}_$keyword', '0','$desc','{$price}','{$imgurl}','{$review}')";
					 $insert = $this->db->query ( $query );
					
					 
					 
				
				} else {
					echo ' <- duplicated <a href="' . get_edit_post_link ( $this->duplicate_id ) . '">#' . $this->duplicate_id . '</a>';
				}
				
				echo '</li>';
			}
			
			echo '</ol>';
			
		
			
		} // if count
		
		if ( count($result) > 0  ) {
			
			// if ASIN
			if (count($result)  == 1 && stristr ( $keyword, 'B0' )) {
				echo '<br>Keyword is an ASIN ... ';
				
				$this->deactivate_key ( $camp->camp_id, $keyword, 0 );
				
				$newstart = '-1';
				$query = "update {$this->wp_prefix}automatic_keywords set amazon_start  = '$newstart' where keyword_name='$keyword'  and keyword_camp = {$camp->camp_id} ";
				$this->db->query ( $query );
				
				return true;
			}
			
			// increment the start with 1
			$newstart = $start + 1;
			
			//clickbank_start	max next price if last page 
			$clickbank_start_part = '';
			
			if($first_sort_by_high_call){
				//first key update
				$clickbank_start_part = " , clickbank_start	= 0 ";
			}elseif($newstart == 11 &&  in_array( 'OPT_AM_ORDER'  , $camp_opt) && $camp_general ['cg_am_order'] == "Price:HighToLow" ){
				//update price as next call will be a next set of 10 pages
				
				//if price == old edge price, change it not to get same exact previoud data
				if($last_price == $row->clickbank_start ) $last_price-- ;
				$clickbank_start_part = " , clickbank_start	= $last_price ";
			}
			
			
			$query = "update {$this->wp_prefix}automatic_keywords set  amazon_start  = '$newstart' $clickbank_start_part where keyword_name='$keyword'  and keyword_camp = {$camp->camp_id} ";
			$this->db->query ( $query );
			 
			//deactivate if next page is 11 and magic search is disabled
			if($newstart == 11 && in_array('OPT_AMAZON_NOMAGIC',$camp_opt)){
				if (! in_array ( 'OPT_NO_DEACTIVATE', $camp_opt )) { 
					$this->deactivate_key ( $camp->camp_id, $keyword ) ;
				}
			}
			
			return true;
		} else {
			// there was no links lets deactivate
			$newstart = '-1';
			$query = "update {$this->wp_prefix}automatic_keywords set amazon_start  = '$newstart' , clickbank_start	= 1  where keyword_name='$keyword'  and keyword_camp = {$camp->camp_id} ";
			$this->db->query ( $query );
			
			// deactivate key
			if (! in_array ( 'OPT_NO_DEACTIVATE', $camp_opt )) {
				
				if (stristr ( $keyword, 'B0' )) {
					
					echo '<br>Keyword is an ASIN ... ';
					$this->deactivate_key ( $camp->camp_id, $keyword, 0 );
				} else {
					$this->deactivate_key ( $camp->camp_id, $keyword );
				}
			}
			
			echo '<br>No more items at amazon to get ';
			
			return false;
		}
	} // end func
}