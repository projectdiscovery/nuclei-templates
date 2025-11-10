<?php

// Main Class
require_once 'core.php';

Class WpAutomaticClickbank extends wp_automatic{


/*
 * ---* Get Clickbank links ---
 */
function clickbank_fetch_links($keyword, $camp) {
	  echo "<br>so I should now get some links from clickbank ...";

	// ini
	$camp_opt = unserialize ( $camp->camp_options );
	$wp_wp_automatic_cbu = get_option('wp_wp_automatic_cbu','');
	
	// Camp general
	if( stristr($camp->camp_general, 'a:') ) $camp->camp_general=base64_encode($camp->camp_general);
	$camp_general = unserialize ( base64_decode( $camp->camp_general ) );
	$camp_general=array_map('wp_automatic_stripslashes', $camp_general);

	// using clickbank
	$clickkey = urlencode ( $keyword );

	// getting start
	$query = "select clickbank_start from {$this->wp_prefix}automatic_keywords where keyword_name='$keyword' and keyword_camp='{$camp->camp_id}' ";
	$ret = $this->db->get_results ( $query );

	$row = $ret [0];
	$start = $row->clickbank_start;
	// check if the start = -1 this means the keyword is exhausted
	if ($start == '-1') {
			
		//check if it is reactivated or still deactivated
		if($this->is_deactivated($camp->camp_id, $keyword)){
			$start =1;
		}else{
			//still deactivated
			return false;
		}
			
	}

	$sortby = $camp->camp_search_order;
	$camp_cb_category = $camp->camp_cb_category;
	
	
	
	// Lang
	$cg_cb_lang = '';
	@$cg_cb_lang = @$camp_general['cg_cb_lang'];
	
	if(trim($cg_cb_lang) == ''){
		$cg_cb_lang = 'EN';
	}

	//$clickbank = "https://accounts.clickbank.com/mkplSearchResult.htm?dores=true&includeKeywords=seo";
	//$clickbank = "https://$cbname.accounts.clickbank.com/account/mkplSearchResult.htm?includeKeywords=$clickkey&resultsPerPage=50&firstResult=$start&sortField=$sortby&$camp_cb_category&productLanguages=$cg_cb_lang";
	
	$search_keyword = $clickkey;
	
	if($keyword == '*') $search_keyword = '';
	
	$clickbank = "https://accounts.clickbank.com/mkplSearchResult.htm?includeKeywords=$search_keyword&resultsPerPage=50&firstResult=$start&sortField=$sortby&$camp_cb_category";
	//&productLanguages=$cg_cb_lang
	
	  echo "<br>Clickbank Remote Link:$clickbank....";

	// login to clickbank
	 		

	// Get
	$x = 'error';
		
	 
		$url = $clickbank;
		curl_setopt ( $this->ch, CURLOPT_HTTPGET, 1 );
		curl_setopt ( $this->ch, CURLOPT_URL, trim ( $url ) );
			
		$cont = curl_exec ( $this->ch );
		  echo $x = curl_error ( $this->ch );

	 

		
	preg_match_all ( '/details">.*?(http:\/\/zzzzz\..*?net).*?>(.*?)<\/a>/s', $cont, $matches, PREG_PATTERN_ORDER );

	$links = $matches [1];
	$titles = $matches [2];


	  echo '<br>links found:' . count ( $links );

	// descreptions <div class="description">The #1 Ballet Product. Fundamental Dance Skills. Gaining Popularity Very Quickly. We Cloak Your Affiliate Link. Http://www.ballet-bible.com/affiliates.php</div>
	preg_match_all ( '{description">(.*?)</div>}', $cont, $matches, PREG_PATTERN_ORDER );
	$descs = $matches [1];

		
	  echo '<ol>';
	for($i = 0; $i < count ( $links ); $i ++) {
		$title = addslashes ( $titles [$i] );
			
		  echo '<li>' . $links [$i] . '<br>' . $titles [$i] . '</li>';
			

		// check if exists
			
			
		$link_url = str_replace('zzzzz', $wp_wp_automatic_cbu,$links[$i]) ;
			
		if( $this->is_execluded($camp->camp_id, $link_url) ){
			  echo '<-- Execluded';
			continue;
		}

		if ( ! $this->is_duplicate($link_url) )  {
			$desc = addslashes ( $descs [$i] );
			$query = "INSERT INTO {$this->wp_prefix}automatic_clickbank_links ( link_url , link_title , link_keyword  , link_status , link_desc )VALUES ( '$links[$i]', '$title', '$keyword', '0' ,'$desc')";
			$this->db->query ( $query );

		} else {
			  echo ' <- duplicated <a href="'.get_edit_post_link($this->duplicate_id).'">#'.$this->duplicate_id.'</a>';
		}
			
			

	}
	  echo '</ol>';

	if (count ( $links ) > 0) {
		// increment the start with 50
		$newstart = $start + 50;
		$query = "update {$this->wp_prefix}automatic_keywords set  clickbank_start  = '$newstart' where keyword_name='$keyword'";
		$this->db->query ( $query );
		return true;
	} else {
		// there was no links lets deactivate
		$newstart = '-1';
		$query = "update {$this->wp_prefix}automatic_keywords set clickbank_start  = '$newstart' where keyword_name='$keyword'";
		$this->db->query ( $query );
			
		$this->deactivate_key($camp->camp_id, $keyword);
			
		return false;
	}
}
function clickbank_get_post($camp) {

	$keywords = explode ( ',', $camp->camp_keywords );
	
	// ini options
	$camp_opt = unserialize ( $camp->camp_options );
	
	
	foreach ( $keywords as $keyword ) {
			
		$keyword = trim($keyword);
			
		if (trim ( $keyword ) != '') {
				
			//update last keyword
			update_post_meta($camp->camp_id, 'last_keyword', trim($keyword));

			// getting links from the db for that keyword
			$query = "select * from {$this->wp_prefix}automatic_clickbank_links where link_keyword='$keyword' ";
			$res = $this->db->get_results ( $query );

			
			// when no links lets get new links
			if (count ( $res ) == 0) {
				
				//clean any old cache for this keyword
				$query_delete = "delete from {$this->wp_prefix}automatic_clickbank_links where link_keyword='$keyword' ";
				$this->db->query ( $query_delete );
				
				$this->clickbank_fetch_links ( $keyword, $camp );
				// getting links from the db for that keyword
				$query = "select * from {$this->wp_prefix}automatic_clickbank_links where link_keyword='$keyword' ";
				$res = $this->db->get_results ( $query );
			}

			//duplicate but posted
			//deleting duplicated items
			$res_count = count($res);
			for($i=0;$i< $res_count;$i++){

				$t_row = $res[$i];
				$t_link_url=$t_row->link_url;

				if( $this->is_duplicate($t_link_url) ){
						
					//duplicated item let's delete
					unset($res[$i]);
						
					  echo '<br>Clickbank Item ('. $t_row->link_title .') found cached but duplicated <a href="'.get_permalink($this->duplicate_id).'">#'.$this->duplicate_id.'</a>'  ;
						
					//delete the item
					$query = "delete from {$this->wp_prefix}automatic_clickbank_links where link_id='{$t_row->link_id}'";
					$this->db->query ( $query );
						
				}else{
					break;
				}

			}

			// check again if valid links found for that keyword otherwise skip it
			if (count ( $res ) > 0) {
				// ini
				$cbname = trim( get_option ( 'wp_wp_automatic_cbu', '' ));
					
				if (trim ( $cbname ) == '') {
					$message = '<a href="http://clickbank.net">Click Bank</a> account needed visit settings and add the username ';
					  echo "<br>$message";
					$this->log ( 'Error', $message );
				}
					
				// lets process that link
				$ret = $res [$i];
					
				$offer_title = $ret->link_title;
				$offer_url = $ret->link_url;
				
				$offer_url_parts = explode('.', $offer_url);
				$offer_id = strtolower( $offer_url_parts[1] ) ;
				
				 
				
				$offer_url = str_replace ( 'zzzzz', $cbname, $offer_url );
				$offer_desc = $ret->link_desc;
					
				// lets call the downloader for offer_title and offer_real_link
				$downloader_link = get_home_url () . '/wp-content/plugins/wp-automatic/downloader.php';
				$downloader_link = site_url('?wp_automatic=download');
					
				curl_setopt ( $this->ch, CURLOPT_HTTPGET, 1 );
				curl_setopt ( $this->ch, CURLOPT_URL, trim ( $downloader_link . '&link=' . str_replace ( 'http:', 'httpz:', $offer_url ) ) );
				$exec = curl_exec ( $this->ch );

					
				$json = json_decode ( $exec );
				// print_r($json);
				@$original_link = $json->link;
				@$original_title = $json->title;
				
				
				
				if(in_array('OPT_CB_DESCRIPTION', $camp_opt)){
					
					$ps = $json->text;
					
					if(is_array($ps)){
						$offer_desc = '<p>'.implode('</p><p>', $ps).'</p>';
					}
						
				}
				
				$original_link = str_replace ( "?hop=$cbname", '', $original_link );
					
				if (trim ( $original_link ) == '' || trim ( $original_title ) == '') {
					  echo '<br>Could not extract original url from hop link, using hop instead';

					$original_title = $offer_title;

					if (trim ( $original_link ) == '') {
						$original_link = $offer_url;
					}
				} else {

					$offer_title = $original_title;
				}
				
				// img
				$tempo = str_replace ( "http://$cbname", 'zzzz', $original_link );
				$tempo = str_replace ( "https://$cbname", 'zzzz', $original_link );
				$tempo = str_replace ( 'http://', '', $tempo );
				$tempo = str_replace ( 'https://', '', $tempo );
				$tempo = str_replace ( "?hop=$cbname", '', $tempo );
				
				$tempo = urlencode ( trim ( $tempo ) );
				$wp_amazonpin_tw = get_option ( 'wp_amazonpin_tw', 400 );
					
				$img = '<img class="product_thumb"  src="https://www.cbtrends.com/images/vendor-pages/'. $offer_id .'-x400-thumb.jpg" />';
					
				$temp = array ();
				$temp ['title'] = $offer_title;
				$temp ['original_title'] = $offer_title;
				$temp ['offer_link'] = $offer_url;
				$temp ['source_link'] = $offer_url;
				$temp ['product_link'] = $offer_url;
				$temp ['original_link'] = $original_link;
				$temp ['product_original_link'] = $original_link;
				$temp ['offer_desc'] = $offer_desc;
				$temp ['product_desc'] = $offer_desc;
				$temp ['img'] = $img;
				$temp ['product_img'] = $img;
				
				//$temp ['product_img_src'] = 'http://pagepeeker.com/t/l/' . strtolower ( $tempo ) ;
				$temp['product_img_src'] = "https://www.cbtrends.com/images/vendor-pages/$offer_id-x400-thumb.jpg";
				$this->used_keyword = $ret->link_keyword ;
				 
				// update the link status to 1
				$query = "delete from {$this->wp_prefix}automatic_clickbank_links where link_id={$ret->link_id}";
				$this->db->query ( $query );
				
					
				return $temp;
			} else {
					
				  echo '<br>No links found for this keyword';
			}
		} // if trim
	} // foreach keyword
} // end funs

}