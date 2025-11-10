<?php

// Main Class
require_once 'core.php';

Class WpAutomaticCraigslist extends wp_automatic{


/*
 * ---* youtube get links ---
 */
function craigslist_fetch_items($keyword, $camp) {

	  echo "<br>so I should now get some items from craigslist" ;
 
	// ini options
	$camp_opt = unserialize ( $camp->camp_options );
	if( stristr($camp->camp_general, 'a:') ) $camp->camp_general=base64_encode($camp->camp_general);
	$camp_general = unserialize ( base64_decode( $camp->camp_general ) );
	$camp_general=array_map('wp_automatic_stripslashes', $camp_general);
	
	// items url
	$cg_cl_page = trim( $camp_general['cg_cl_page'] );
	
	// verify valid link
	if(  !( stristr($cg_cl_page, 'http') && stristr($cg_cl_page, 'craigslist.org') ) ){
		  echo '<br>Provided craigslist link is not valid please visit craigslist.org and get a correct one';
		return false;
	}
	
	//if not query, make it a query ?q=
	if( ! stristr($cg_cl_page, '?') ){
		$cg_cl_page.= '?query=';
	}
	
	//changing format to rss 
	$cg_cl_page.= "&format=rss";
	
	
	
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
	
	
	
	 

	if ($start == - 1) {
		  echo '<- exhausted link';
			
		if( ! in_array( 'OPT_CL_CACHE' , $camp_opt )){
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
	
	//start 
	$cg_cl_page.='&s='.$start;
	
	  echo '<br>Craigslist items url:'.$cg_cl_page;

	  echo ' index:' . $start;

	// update start index to start+1
	$nextstart = $start + 25;

	$query = "update {$this->wp_prefix}automatic_keywords set keyword_start = $nextstart where keyword_id=$kid ";
	$this->db->query ( $query );

	// get items
	// curl get
	$x = 'error';
	$url = $cg_cl_page;
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
	if( ! stristr($exec, '<?xml')){
		  echo '<br>Not expected response from Craigslist';
		  
		  if(stristr($exec,'IP has been automatically blocked')){
		  		echo '<br>Your server IP is blocked from Craigslist, you will need to use proxies on the plugin settings page';
		  }
		  
	} 
	
	 
	// load items from feed txt
	// Matching items
	preg_match_all('{<item .*?>(.*?)</item}s', $exec , $itmsMatchs );
	$allItms = $itmsMatchs[0];
	
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
		
		
		// clean item
		$itemTxt = html_entity_decode( $itemTxt );
		$itemTxt = str_replace('<![CDATA[', '', $itemTxt);
		$itemTxt = str_replace(']]>', '', $itemTxt);
		
		// match title
		preg_match('{<title>(.*?)</title>}s', $itemTxt , $ttlMatchs );
		$item['item_title'] = $ttlMatchs[1];
		
		// match description
		preg_match('{<description>(.*?)</description>}s', $itemTxt , $descMatchs );
		$item['item_description'] = $descMatchs[1];
		
		// match link
		preg_match('{<link>(.*?)</link>}s', $itemTxt , $lnkMatchs );
		$item['item_link'] = $lnkMatchs[1];
		$item_link = $item['item_link'];
		
		// match date
		preg_match('{<dc:date>(.*?)</dc:date>}s', $itemTxt , $lnkMatchs );
		$item['item_date'] = $lnkMatchs[1];
		
		// match img
		
		$item['item_img'] ='';
		if(preg_match('{(https?://images.craigslist.*?)"}s', $itemTxt , $imgMatchs )){
			$item['item_img'] = $imgMatchs[1];
		}
		
		 
		
	 	// get id
		$ex = preg_match('{(\d*?)\.html}', $item['item_link'] , $allMatchs );
		$id = $allMatchs[1];
		
		$data = ( base64_encode( serialize ( $item ) ) );
		
		  echo '<li> Link:'.$item_link;
		
		// No image skip
		if( trim($item['item_img']) == '' && in_array('OPT_CL_IMG', $camp_opt) ){
			  echo '<- No image skip';
			continue;
		}
		
		if( $this->is_execluded($camp->camp_id, $item_link) ){
			  echo '<-- Execluded';
			continue;
		}

		if ( ! $this->is_duplicate($item_link) )  {
			$query = "INSERT INTO {$this->wp_prefix}automatic_general ( item_id , item_status , item_data ,item_type) values (  '$id', '0', '$data' ,'cl_{$camp->camp_id}_$keyword')  ";
			$this->db->query ( $query );
		} else {
			  echo ' <- duplicated <a href="'.get_edit_post_link($this->duplicate_id).'">#'.$this->duplicate_id.'</a>';
		}
			
	}

	  echo '</ol>';

}
	
 	
/*
 * ---* craigslist post ---
 */
function craigslist_get_post($camp) {

	 
	// Campaign options
	$camp_opt = unserialize (  $camp->camp_options );
	$keywords = array('*');
 	
	foreach ( $keywords as $keyword ) {
			
		$keyword = trim($keyword);

		//update last keyword
		update_post_meta($camp->camp_id, 'last_keyword', trim($keyword));
			
		if (trim ( $keyword ) != '') {

			// getting links from the db for that keyword
			$query = "select * from {$this->wp_prefix}automatic_general where item_type=  'cl_{$camp->camp_id}_$keyword' ";
			$this->used_keyword=$keyword;
			$res = $this->db->get_results ( $query );

			// when no links lets get new links
			if (count ( $res ) == 0) {
				
				//clean any old cache for this keyword
				$query_delete = "delete from {$this->wp_prefix}automatic_general where item_type='cl_{$camp->camp_id}_$keyword' ";
				$this->db->query ( $query_delete );

				// get new fresh items
				$this->craigslist_fetch_items ( $keyword, $camp );
				
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

				if( $this->is_duplicate($t_link_url) ){
						
					//duplicated item let's delete
					unset($res[$i]);
						
					  echo '<br>craigslist item ('. $t_data ['item_title'] .') found cached but duplicated <a href="'.get_permalink($this->duplicate_id).'">#'.$this->duplicate_id.'</a>'  ;
						
					//delete the item
					$query = "delete from {$this->wp_prefix}automatic_general where id={$t_row->id} ";
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
				
				// clean show content 
				if(stristr($temp['item_description'], 'showcontact')){
					  echo '<br>Removing contact link';
					$temp['item_description'] = preg_replace('{<a.*?/a>}s', '', $temp['item_description']);
				}
				
				// getting full description
				 
				// getting full image
				if(trim($temp['item_img']) != '' ){
					
					$fullImg = str_replace('300x300', '600x450', $temp['item_img'] );
					  echo '<br>Full Image:'.$fullImg;
				
					$temp['item_img'] = $fullImg; 
				
				}
				
				// Img shortcode
				$temp['item_img_html'] = '';
				if(trim($temp['item_img']) !=''){
					$temp['item_img_html'] = '<img src="'.$temp['item_img'].'" />';
				}
				
				// update the link status to 1
				$query = "delete from {$this->wp_prefix}automatic_general where id={$ret->id}";
				$this->db->query ( $query );
					
				// if cache not active let's delete the cached videos and reset indexes
				if (! in_array ( 'OPT_CL_CACHE', $camp_opt )) {
					 
					echo '<br>Cache disabled claring cache ...';
					$query = "delete from {$this->wp_prefix}automatic_general where item_type='cl_{$camp->camp_id}_$keyword' ";
					$this->db->query ( $query );

					// reset index
					$query = "update {$this->wp_prefix}automatic_keywords set keyword_start =1 where keyword_camp={$camp->camp_id}";
					$this->db->query ( $query );
				}
				
				//remove after price
				$item_title = $temp['item_title'] ;
				  
				//Extracting the price
				preg_match('{\d*$}', $item_title,$priceMatches);

				$temp['item_price'] = '' ;
				if(is_numeric($priceMatches[0])){
					$temp['item_price'] =  $priceMatches[0] ;
				}else{
					//not the last part 
					preg_match('{ .(\d+) }', $item_title , $price_matches);
					
					if( isset($price_matches[1]) && trim($price_matches[1])  != ''){
						$temp['item_price'] = $price_matches[1] ;
					}
 					
					
				}
					
				return $temp;
			} else {
					
				  echo '<br>No links found for this keyword';
			}
		} // if trim
	} // foreach keyword
}

}