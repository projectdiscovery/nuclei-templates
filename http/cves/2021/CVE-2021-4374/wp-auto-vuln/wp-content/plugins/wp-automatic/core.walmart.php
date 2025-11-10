<?php

// Main Class
require_once 'core.php';

Class WpAutomaticWalmart extends wp_automatic{


	/*
	 * ---* youtube get links ---
	 */
	function walmart_fetch_items($keyword, $camp) {

		  echo "<br>so I should now get some items from Walmart for keyword: ".$keyword ;

		// ini options
		$camp_opt = unserialize ( $camp->camp_options );
		if( stristr($camp->camp_general, 'a:') ) $camp->camp_general=base64_encode($camp->camp_general);
		$camp_general = unserialize ( base64_decode( $camp->camp_general ) );
		$camp_general=array_map('wp_automatic_stripslashes', $camp_general);
  
		$apiKey = get_option('wp_automatic_wm_api','');
		$lsPublisherId = get_option('wp_automatic_wm_ir_publisher','');
		
		if(trim($apiKey) == ''){
			  echo '<br><span style="color:red">Walmart API key is required !. Please visit the plugin settings page and add it.</span>';
			exit;
		}
		
		if(trim($lsPublisherId) == ''){
			echo '<br><span style="color:red">Walmart Affiliate Publisher ID was not added. Please visit the plugin settings page and add it if you want to make commisions.</span>';
		}
		
		// get start-index for this keyword
		$query = "select keyword_start ,keyword_id from {$this->wp_prefix}automatic_keywords where keyword_name='$keyword' and keyword_camp={$camp->camp_id}";
		$rows = $this->db->get_results ( $query );
		@$row = $rows [0];

		//If no rows add a keyword record
		if(count($rows) == 0){
			$query="insert into {$this->wp_prefix}automatic_keywords(keyword_name,keyword_camp,keyword_start) values ('$keyword','{$camp->camp_id}',1)";
			$this->db->query($query);
			$kid = $this->db->insert_id;
			$start = 0;

		}else{
			$kid = $row->keyword_id;
			$start = $row->keyword_start;
		}

		  echo ' index:'.$start;

		if ($start == - 1) {
			  echo '<- exhausted link';

			if( ! in_array( 'OPT_RD_CACHE' , $camp_opt )){
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
		
		if($start == 0 ) $start = 1;

		require_once 'inc/class.walmart.php';
		
		$walMart = new wpAutomaticWallMart($this->ch,$apiKey,$lsPublisherId);
		
		$allItms = array(); //ini
		$parms = array();
		
		
		
		//correct start
		$walmartStart = 1 + ($start - 1) * 10 ;
		
		$parms['start'] = $walmartStart;
		
		//cat
		if(in_array('OPT_WM_CAT', $camp_opt)){
			
			$cg_wm_cat = trim($camp_general['cg_wm_cat']);
			
			if(trim($cg_wm_cat) != ''){
				$parms['categoryId'] = $cg_wm_cat;
			}
			
		}
		
		//sort
		if(in_array('OPT_WM_ORDER', $camp_opt)){
			
			$cg_wm_sort = trim( $camp_general['cg_wm_sort'] ) ;
			
			if(trim($cg_wm_sort) != ''){
				$parms['sort'] = $cg_wm_sort;
				
				//asc or desc
				if( $cg_wm_sort == 'price' ||  $cg_wm_sort == 'title' || $cg_wm_sort == 'customerRating' ){
					$cg_wm_sort_dir = $camp_general['cg_wm_sort_dir'];
					$parms['order'] = $cg_wm_sort_dir;
				}
				
			}
			
		}
		
		//price range
		if(in_array('OPT_WM_RANGE', $camp_opt)){
			
			$cg_wm_price_from =trim( $camp_general['cg_wm_price_from'] );
			$cg_wm_price_to   = trim( $camp_general['cg_wm_price_to'] );
			
			if( $cg_wm_price_from != '' && $cg_wm_price_to != '' && is_numeric($cg_wm_price_from) && is_numeric($cg_wm_price_to) ){
				$parms['facet'] = 'on';
				$parms['facet.range'] = "price:[$cg_wm_price_from TO $cg_wm_price_to]";
			}
			
			
		}
		
		try {
			
			$allItms = $walMart->getItemsByKeyword($keyword,$parms);
			 
			
		} catch (Exception $e) {
			
			  echo '<br>Error:'.$e->getMessage();
			
		}

		  echo ' index:' . $start;
			
		// update start index to start+1
		$nextstart = $start + 1;

		$query = "update {$this->wp_prefix}automatic_keywords set keyword_start = $nextstart where keyword_id=$kid ";
		$this->db->query ( $query );

 
		// Check returned items count
		if ( count($allItms) > 0 ) {

			  echo '<br>Valid reply returned with ' . count($allItms) . ' item';

		} else {

			  echo '<br>No items found';
			 
				
			  echo '<br>Keyword have no more images deactivating...';
			$query = "update {$this->wp_prefix}automatic_keywords set keyword_start = -1 where keyword_id=$kid ";
			$this->db->query ( $query );

			if(! in_array('OPT_NO_DEACTIVATE', $camp_opt))
				$this->deactivate_key($camp->camp_id, $keyword);

		}


		  echo '<ol>';

	 

		foreach ( $allItms as $itemTxt ) {
			
			 
			$item = array();
			
			//id
			$id = $item['item_id'] = $itemTxt->itemId;
			
			//title
			$item['item_title'] = $itemTxt->name;
			
			//salePrice
			$item['item_price'] = $itemTxt->salePrice;
			
			//upc
			$item['item_upc'] = $itemTxt->upc;
			
			//shortDescription
			$item['item_short_description'] = $itemTxt->shortDescription;
			
			//longDescription
			$item['item_description'] = $itemTxt->longDescription;
			
			//largeImage
			$item['item_img'] = $itemTxt->largeImage;
			
			//productUrl
			$item_link = $item['item_url'] = "https://www.walmart.com/ip/".$item['item_id'];
			
			//productTrackingUrl
			$item['product_affiliate_url'] = $itemTxt->productTrackingUrl;
				
			//addToCartUrl
			$item['item_cart_url'] = $itemTxt->addToCartUrl;
				
			//affiliateAddToCartUrl
			$item['item_cart_affiliate_url'] = $itemTxt->affiliateAddToCartUrl;
			
			//customerRating
			$item['item_rating'] = $itemTxt->customerRating;
			
			//customerRatingImage
			$item['item_rating_img'] = $itemTxt->customerRatingImage;
			
			//msrp
			$item['item_list_price'] = $itemTxt->msrp;
			
			//imageEntities
			$item_images = array();
			$item_images[] = $itemTxt->largeImage;
			
			if(isset($itemTxt->imageEntities)){
				foreach ($itemTxt->imageEntities as $imageEntity){
					
					if($imageEntity->entityType == 'SECONDARY'){
						$item_images[]= $imageEntity->largeImage;	
					}
					
				}
			}
			
			$item['item_imgs'] = implode(',', $item_images);
			
			  echo '<li> Link:'.$item_link;
			

			if( $this->is_execluded($camp->camp_id, $item_link) ){
				  echo '<-- Execluded';
				continue;
			}

			if ( ! $this->is_duplicate($item_link) )  {
				
				$data = ( base64_encode( serialize ( $item ) ) );
				
				$query = "INSERT INTO {$this->wp_prefix}automatic_general ( item_id , item_status , item_data ,item_type) values (  '$id', '0', '$data' ,'wm_{$camp->camp_id}_$keyword')  ";
				$this->db->query ( $query );
			} else {
				  echo ' <- duplicated <a href="'.get_edit_post_link($this->duplicate_id).'">#'.$this->duplicate_id.'</a>';
			}

		}

		  echo '</ol>';

	}


	/*
	 * ---* walmart post ---
	 */
	function walmart_get_post($camp) {


		// Campaign options
		$camp_opt = unserialize (  $camp->camp_options );
		$keywords = explode ( ',', $camp->camp_keywords );

		foreach ( $keywords as $keyword ) {

			$keyword = trim($keyword);

			//update last keyword
			update_post_meta($camp->camp_id, 'last_keyword', trim($keyword));

			if (trim ( $keyword ) != '') {

				// getting links from the db for that keyword
				$query = "select * from {$this->wp_prefix}automatic_general where item_type=  'wm_{$camp->camp_id}_$keyword' ";
				$this->used_keyword=$keyword;
				$res = $this->db->get_results ( $query );

				// when no links lets get new links
				if (count ( $res ) == 0) {
					
					//clean any old cache for this keyword
					$query_delete = "delete from {$this->wp_prefix}automatic_general where item_type='wm_{$camp->camp_id}_$keyword' ";
					$this->db->query ( $query_delete );

					// get new fresh items
					$this->walmart_fetch_items ( $keyword, $camp );

					// getting links from the db for that keyword
					$res = $this->db->get_results ( $query );
				}

				//check if already duplicated
				//deleting duplicated items
				$res_count = count($res);
				for($i=0;$i< $res_count ;$i++){

					$t_row = $res[$i];

					
					$t_data =  unserialize ( base64_decode( $t_row->item_data ) );

					$t_link_url= $t_data['item_url'] ;

					if( $this->is_duplicate($t_link_url) ){

						//duplicated item let's delete
						unset($res[$i]);

						  echo '<br>walmart item ('. $t_data ['item_title'] .') found cached but duplicated <a href="'.get_permalink($this->duplicate_id).'">#'.$this->duplicate_id.'</a>'  ;

						//delete the item
						$query = "delete from {$this->wp_prefix}automatic_general where id='{$t_row->id}' ";
						$this->db->query ( $query );

					}else{
						break;
					}

				}

				// check again if valid links found for that keyword otherwise skip it
				if (count ( $res ) > 0) {

					// lets process that link
					$ret = $res [$i];

					$data = unserialize ( base64_decode( $ret->item_data )  );

					$temp = $data;

					  echo '<br>Found Link:'.$temp['item_url'];
						
					// empty item_description fix
					if(trim($temp['item_description']) == '') $temp['item_description'] = $temp['item_title'];
						
					// Item img html
					if(trim($temp['item_img']) != ''){
						$temp['item_img_html'] = '<img src="'.$temp['item_img'].'" />';
					}else{
						$temp['item_img_html'] = '';
					}
					
					//imgs html
					$cg_wm_full_img_t = @$camp_general['cg_wm_full_img_t'];
					
					if(trim($cg_wm_full_img_t) == ''){
						$cg_wm_full_img_t = '<img src="[img_src]" class="wp_automatic_gallery" />';
					}
					
					$product_imgs_html = '' ;
					
					$allImages = explode(',',  $temp['item_imgs'] );
					$allImages = array_filter($allImages);
					
					$allImages_html = '';
					
					foreach($allImages as $singleImage){
						
						if(trim($singleImage) != ''){
						
							$singleImageHtml = $cg_wm_full_img_t;
							$singleImageHtml = str_replace('[img_src]', $singleImage, $singleImageHtml);
							$allImages_html.= $singleImageHtml;
						}
					}
					
					$temp['item_imgs_html'] = $allImages_html;
						

					// update the link status to 1
					$query = "delete from {$this->wp_prefix}automatic_general where id={$ret->id}";
					$this->db->query ( $query );

					// if cache not active let's delete the cached videos and reset indexes
					if (! in_array ( 'OPT_WM_CACHE', $camp_opt )) {
						  echo '<br>Cache disabled claring cache ...';
						$query = "delete from {$this->wp_prefix}automatic_general where item_type='wm_{$camp->camp_id}_$keyword' and item_status ='0'";
						$this->db->query ( $query );

						// reset index
						$query = "update {$this->wp_prefix}automatic_keywords set keyword_start =1 where keyword_camp={$camp->camp_id}";
						$this->db->query ( $query );
					}
						
					$temp['item_link'] = $temp['item_url']  ; 
 					
					//decode
					$temp['item_description'] = html_entity_decode($temp['item_description']);
					
					//item_list_price
					if(trim($temp['item_list_price']) == '') $temp['item_list_price']= $temp['item_price']; 
					
					return $temp;
				} else {

					  echo '<br>No links found for this keyword';
				}
			} // if trim
		} // foreach keyword
	}

}