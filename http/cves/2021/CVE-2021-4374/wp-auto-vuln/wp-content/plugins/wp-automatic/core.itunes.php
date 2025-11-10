<?php

// Main Class
require_once 'core.php';

Class WpAutomaticItunes extends wp_automatic{


/*
 * ---* youtube get links ---
 */
function Itunes_fetch_items($keyword, $camp) {

	  echo "<br>so I should now get some items from Itunes" ;
	
	$wp_automatic_iu_id = get_option('wp_automatic_iu_id',true);
	
	if(trim($wp_automatic_iu_id) == '')   echo '<br>Please apply to the itunes program and add your id at the settings page to get commissions now you will not.';
 
	// ini options
	$camp_opt = unserialize ( $camp->camp_options );
	if( stristr($camp->camp_general, 'a:') ) $camp->camp_general=base64_encode($camp->camp_general);
	$camp_general = unserialize ( base64_decode( $camp->camp_general ) );
	$camp_general=array_map('wp_automatic_stripslashes', $camp_general);
	
	// media
	$cg_iu_media = $camp_general['cg_iu_media'];
	
	// items url
	$itunesSearchUrl = "https://itunes.apple.com/search?term=". urlencode(trim($keyword)) ."&media=$cg_iu_media" ;
	
	// Attribute
	$cg_iu_attribute = $camp_general['cg_iu_attribute'];
	
	if(trim($cg_iu_attribute) != '' && trim($cg_iu_attribute) != 'All' ){
		$itunesSearchUrl .= '&attribute='.$cg_iu_attribute;
	}
	
	// Country filter
	if(in_array('OPT_IU_COUNTRY', $camp_opt)){
		
		$cg_iu_lang = $camp_general['cg_iu_lang'];
		
		if(trim($cg_iu_lang) != ''){
			$itunesSearchUrl.= '&country='. trim($cg_iu_lang);
		}
		
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
	
	$itunesSearchUrl.= '&offset=' .$start;
	
	  echo '<br>ItunesUrl:'.$itunesSearchUrl;

	if ($start == - 1) {
		  echo '<- exhausted link';
			
		if( ! in_array( 'OPT_IU_CACHE' , $camp_opt )){
			$start =0;
			  echo '<br>Cache disabled resetting index to 0';
		}else{

			//check if it is reactivated or still deactivated
			if($this->is_deactivated($camp->camp_id, $keyword)){
				$start =0;
			}else{
				//still deactivated
				return false;
			}

		}
			
			
	}
	 
	  echo ' index:' . $start;

	// update start index to start+1
	$nextstart = $start + 50;

	$query = "update {$this->wp_prefix}automatic_keywords set keyword_start = $nextstart where keyword_id=$kid ";
	$this->db->query ( $query );

	// get items
	// curl get
	$x = 'error';
	$url = $itunesSearchUrl;
	curl_setopt ( $this->ch, CURLOPT_HTTPGET, 1 );
	curl_setopt ( $this->ch, CURLOPT_URL, trim ( $url ) );
	$exec = curl_exec ( $this->ch );
	$x = curl_error ( $this->ch );
	
	
	// error check
	if(trim($x) != ''){
		  echo '<br>Curl error:'.$x;
		return false;
	} 
	
	// validate reply
	if( ! stristr($exec, 'resultCount')){
		  echo '<br>Not expected response from Itunes';
	} 
	
	$jsonReply = json_decode($exec);
	
	$allItms = $jsonReply->results;
	
	// Check returned items count
 	if ( count($allItms) > 0 ) {

		  echo '<br>Valid reply returned with ' . count($allItms) . ' item';

	} else {
		
		  echo '<br>No items found';
		 
		  echo '<br>Keyword have no more items deactivating...';
		$query = "update {$this->wp_prefix}automatic_keywords set keyword_start = -1 where keyword_id=$kid ";
		$this->db->query ( $query );
		
		if(! in_array('OPT_NO_DEACTIVATE', $camp_opt))
		$this->deactivate_key($camp->camp_id, $keyword);
				
	}
	 
 
	  echo '<ol>';

	/*
	  echo '<pre>';
	print_r($allItms);
	exit;	
	
	*/
	foreach ( $allItms as $item ) {
		
		$itm = array();
		
		
		if(! ($item->wrapperType == 'audiobook') ){
			
			$item_link = $item->trackViewUrl;
			$id = $item->trackId;
			
			$itm['item_link'] = $item->trackViewUrl;
				
		}else{
			
			$item_link = $item->collectionViewUrl;
			$id = $item->collectionId;
			
			$itm['item_link'] = $item->collectionViewUrl;
			
		}
		
		 
		$itm['item_id'] = $id;
		$itm['item_link'] = $item_link;
		 
		// adaption 
		if($item->kind == 'song' ){
			
			$itm['item_description'] = $item->trackName;
			$itm['item_title'] = $item->trackName;
			
			$itm['item_collectionId'] = $item->collectionId;
			$itm['item_collectionName'] = $item->collectionName;
			$itm['item_collectionViewUrl'] = $item->collectionViewUrl;
			
			$itm['item_previewUrl'] = $item->previewUrl;
				
			$itm['item_img'] = str_replace('100x100', '600x600', $item->artworkUrl100);
			
			$itm['item_artistId'] = $item->artistId;
			$itm['item_artistName'] = $item->artistName;
			$itm['item_trackName'] = $item->trackName;
			$itm['item_artistViewUrl'] = $item->artistViewUrl;
			
			$itm['item_price'] = $item->trackPrice;
			$itm['item_collectionPrice'] = $item->collectionPrice;
			
			$itm['item_trackCount'] = $item->trackCount;
			$itm['item_trackNumber'] = $item->trackNumber;
			
			$itm['item_country'] = $item->country;
			$itm['item_currency'] = $item->currency;
				
		}elseif($item->kind == 'feature-movie'){
			
			
			$itm['item_description'] = $item->longDescription;
			$itm['item_title'] = $item->trackName;
			$itm['item_previewUrl'] = $item->previewUrl;
			$itm['item_img'] = str_replace('100x100', '600x600', $item->artworkUrl100);
			$itm['item_artistName'] = $item->artistName;
			$itm['item_trackName'] = $item->trackName;
			$itm['item_price'] = $item->trackPrice;
			$itm['item_country'] = $item->country;
			$itm['item_currency'] = $item->currency;
			
			
		}elseif($item->kind == 'podcast'){
			$itm['item_description'] = $item->trackName;
			$itm['item_title'] = $item->trackName;
			$itm['item_img'] = str_replace('100x100', '600x600', $item->artworkUrl100);
			$itm['item_artistName'] = $item->artistName;
			$itm['item_trackName'] = $item->trackName;
			$itm['item_price'] = $item->trackPrice;
			$itm['item_country'] = $item->country;
			$itm['item_currency'] = $item->currency;
			
			
		}elseif($item->kind == 'music-video'){
			
			$itm['item_description'] = $item->trackName;
			$itm['item_title'] = $item->trackName;
			$itm['item_previewUrl'] = $item->previewUrl;
			$itm['item_img'] = str_replace('100x100', '600x600', $item->artworkUrl100);
			$itm['item_artistName'] = $item->artistName;
			$itm['item_trackName'] = $item->trackName;
			$itm['item_price'] = $item->trackPrice;
			$itm['item_country'] = $item->country;
			$itm['item_currency'] = $item->currency;
			
		}elseif($item->wrapperType == 'audiobook'){
			
		 		
			$itm['item_description'] = $item->description;
			$itm['item_title'] = $item->collectionName;
			$itm['item_img'] = str_replace('100x100', '600x600', $item->artworkUrl100);
			$itm['item_artistName'] = $item->artistName;
			$itm['item_trackName'] = $item->collectionName;
			$itm['item_price'] = $item->collectionPrice;
			$itm['item_country'] = $item->country;
			$itm['item_currency'] = $item->currency;
			
		
		}elseif($item->kind == 'tv-episode'){
			
 				
			$itm['item_description'] = $item->longDescription;
			$itm['item_title'] = $item->trackName;
			$itm['item_img'] = str_replace('100x100', '600x600', $item->artworkUrl100);
			$itm['item_artistName'] = $item->artistName;
			$itm['item_previewUrl'] = $item->previewUrl;
			$itm['item_trackName'] = $item->trackName;
			$itm['item_price'] = $item->trackPrice;
			$itm['item_country'] = $item->country;
			$itm['item_currency'] = $item->currency;
			
			
		}elseif($item->kind == 'ebook'){
			
 				
			$itm['item_description'] = $item->description;
			$itm['item_title'] = $item->trackName;
			$itm['item_img'] = str_replace('100x100', '600x600', $item->artworkUrl100);
			$itm['item_artistName'] = $item->artistName;
			$itm['item_trackName'] = $item->trackName;
			$itm['item_price'] = $item->trackPrice;
			$itm['item_country'] = $item->country;
			$itm['item_currency'] = $item->currency;
			$itm['item_rating'] = $item->averageUserRating;
			$itm['item_rating_counts'] = $item->userRatingCount;
			
		}elseif($item->kind == 'software'){
			
 				
			$itm['item_description'] = $item->description;
			$itm['item_title'] = $item->trackName;
			$itm['item_img'] = str_replace('100x100', '600x600', $item->artworkUrl100);
			$itm['item_artistName'] = $item->artistName;
			$itm['item_artistViewUrl'] = $item->artistViewUrl;
			$itm['item_supportedDevices'] = implode(',', $item->supportedDevices);
			$itm['item_fileSize'] = number_format( ($item->fileSizeBytes)/1024 ,2 );
			$itm['item_sellerUrl'] = $item->sellerUrl;
			$itm['item_trackName'] = $item->trackName;
			$itm['item_price'] = $item->price;
			$itm['item_currency'] = $item->currency;
			$itm['item_version'] = $item->version;
			$itm['item_primaryGenreName'] = $item->primaryGenreName;
			$itm['item_rating'] = $item->averageUserRating;
			$itm['item_rating_counts'] = $item->userRatingCount;
			$itm['item_screenshotUrls'] = implode(',', $item->screenshotUrls);
			$itm['item_screenshot'] = implode('"><br><img style="width:300px;padding:5px" src="', $item->screenshotUrls);
			$itm['item_screenshot'] = '<img  style="width:300px;" src="'. $itm['item_screenshot'] .'">';
			 
			
			
		}
		
		//Release date
		if(isset($item->releaseDate)){
			$itm['item_releaseDate'] = $item->releaseDate;
			$itm['item_releaseDate'] =  str_replace('T', ' ', $itm['item_releaseDate']);
			$itm['item_releaseDate'] =  str_replace('Z', '', $itm['item_releaseDate']);
		}
		
		//Time
		if(isset($item->trackTimeMillis)){
			//track time
			$trackTimeMillis = $item->trackTimeMillis;
			$trackTimeMillis = number_format( $trackTimeMillis / (1000 * 60) , 2 );
			$itm['item_time'] = $trackTimeMillis;
		}
		
		
		
		if( $this->is_execluded($camp->camp_id, $item_link) ){
			  echo '<-- Execluded';
			continue;
		}
		
		  echo '<li> Link:'.$item_link;

		if ( ! $this->is_duplicate($item_link) )  {
			
			$data = ( base64_encode( serialize ( $itm ) ) );
			
			 
			$query = "INSERT INTO {$this->wp_prefix}automatic_general ( item_id , item_status , item_data ,item_type) values (  '$id', '0', '$data' ,'iu_{$camp->camp_id}_$keyword')  ";
			$this->db->query ( $query );
			
		} else {
			  echo ' <- duplicated <a href="'.get_edit_post_link($this->duplicate_id).'">#'.$this->duplicate_id.'</a>';
		}
			
	}

	  echo '</ol>';

}
	
 	
/*
 * ---* Itunes post ---
 */
function Itunes_get_post($camp) {

	 
	// Campaign options
	$camp_opt = unserialize (  $camp->camp_options );
	$keywords = explode ( ',', $camp->camp_keywords );
 	
	foreach ( $keywords as $keyword ) {
			
		$keyword = trim($keyword);

		//update last keyword
		update_post_meta($camp->camp_id, 'last_keyword', trim($keyword));
			
		if (trim ( $keyword ) != '') {

			// getting links from the db for that keyword
			$query = "select * from {$this->wp_prefix}automatic_general where item_type=  'iu_{$camp->camp_id}_$keyword' ";
			$this->used_keyword=$keyword;
			$res = $this->db->get_results ( $query );

			// when no links lets get new links
			if (count ( $res ) == 0) {

				// get new fresh items
				$this->Itunes_fetch_items ( $keyword, $camp );
				
				// getting links from the db for that keyword
				$res = $this->db->get_results ( $query );
			}

			 			
			//check if already duplicated
			//deleting duplicated items
			$res_count = count($res);
			for($i=0;$i< $res_count ;$i++){

				$t_row = $res[$i];
					
				$t_data =  unserialize ( base64_decode( $t_row->item_data ) );
				 
				$t_link_url= $t_data['item_link'] ;
				$id = $t_data['item_id']; 

				if( $this->is_duplicate($t_link_url) ){
						
					//duplicated item let's delete
					unset($res[$i]);
						
					  echo '<br>Itunes item ('. $t_link_url .') found cached but duplicated <a href="'.get_permalink($this->duplicate_id).'">#'.$this->duplicate_id.'</a>'  ;
						
					//delete the item
					$query = "delete from {$this->wp_prefix}automatic_general where  id={$t_row->id} ";
				
				 	
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
				 	
				 echo '<br>Found Link:'.$temp['item_link'];
				
				// update the link status to 1
				 $query = "delete from {$this->wp_prefix}automatic_general where id={$ret->id}";
				 $this->db->query ( $query );
					
				// if cache not active let's delete the cached videos and reset indexes
				if (! in_array ( 'OPT_IU_CACHE', $camp_opt )) {
					  echo '<br>Cache disabled claring cache ...';
					$query = "delete from {$this->wp_prefix}automatic_general where item_type='iu_{$camp->camp_id}_$keyword' ";
					$this->db->query ( $query );

					// reset index
					$query = "update {$this->wp_prefix}automatic_keywords set keyword_start =1 where keyword_camp={$camp->camp_id}";
					$this->db->query ( $query );
					
				}
				
				//Affiliate id
				// Affiliate id
				$wp_automatic_iu_id = get_option('wp_automatic_iu_id',true);
				
				if(trim($wp_automatic_iu_id) == '')  {
					$temp['affiliate_id'] = '';
				}else{
					$temp['affiliate_id'] = $wp_automatic_iu_id ;
				}

				return $temp;
				
			} else {
					
				  echo '<br>No links found for this keyword';
			}
		} // if trim
	} // foreach keyword
}

}