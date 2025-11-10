<?php

// Main Class
require_once 'core.php';

Class WpAutomaticFlicker extends wp_automatic{


/*
 * ---* youtube get links ---
 */
function flicker_fetch_items($keyword, $camp) {
	  echo "<br>so I should now get some images from flicker for keyword :" . $keyword;

	$api_key = trim(get_option ( 'wp_automatic_flicker', '' ));

	if (trim ( $api_key ) == '') {
		  echo '<br>Flicker Api key required ';
		exit ();
	}

	// ini options
	$camp_opt = unserialize ( $camp->camp_options );
	if( stristr($camp->camp_general, 'a:') ) $camp->camp_general=base64_encode($camp->camp_general);
	$camp_general = unserialize ( base64_decode( $camp->camp_general ) );
	$camp_general=array_map('wp_automatic_stripslashes', $camp_general);

	$sortby = $camp_general ['cg_fl_order'];

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
			
		if( ! in_array( 'OPT_FL_CACHE' , $camp_opt )){
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

	  echo ' index:' . $start;

	// update start index to start+1
	$nextstart = $start + 1;

	$query = "update {$this->wp_prefix}automatic_keywords set keyword_start = $nextstart where keyword_id=$kid ";
	$this->db->query ( $query );

	// get items
	$orderby = $camp_general ['cg_fl_order'];

	$flink = "https://api.flickr.com/services/rest/?method=flickr.photos.search&api_key=$api_key&format=php_serial&page=$start&sort=$sortby";


	if (in_array ( 'OPT_FL_USER', $camp_opt )) {
		  echo '<br>Fetching images for specific user  ' . $camp_general ['cg_fl_user'];
		
		//if album
		$cg_fl_user_album = '';
		$isAlbum = false; // flag to know this is an album call
		$cg_fl_user_album = $camp_general['cg_fl_user_album'];
		
		if(trim($cg_fl_user_album) != ''){
			$flink = "https://api.flickr.com/services/rest/?method=flickr.photosets.getPhotos&api_key=$api_key&format=php_serial&page=$start&photoset_id=".trim($cg_fl_user_album);
			$isAlbum = true;
		}
		
		$flink = $flink . '&user_id=' . $camp_general ['cg_fl_user'];
		// if keyword *
		if (trim ( $keyword ) == '*') {
			  echo '<br>No filtering get all ';
		} else {
			// specific keyword
			$flink = $flink . '&text=' . urlencode($keyword);
		}
	} else {
		// no specific user just text
		$flink = $flink . '&text=' . urlencode($keyword);
	}

	//  echo '<br>Flink:'.$flink;

	//licensing license
	if(in_array('OPT_FL_LICENSE', $camp_opt)){
		$licenses = array();

		if(in_array('OPT_FL_LICENSE_0', $camp_opt)) $licenses[] = 0;
		if(in_array('OPT_FL_LICENSE_1', $camp_opt)) $licenses[] = 1;
		if(in_array('OPT_FL_LICENSE_2', $camp_opt)) $licenses[] = 2;
		if(in_array('OPT_FL_LICENSE_3', $camp_opt)) $licenses[] = 3;
		if(in_array('OPT_FL_LICENSE_4', $camp_opt)) $licenses[] = 4;
		if(in_array('OPT_FL_LICENSE_5', $camp_opt)) $licenses[] = 5;
		if(in_array('OPT_FL_LICENSE_6', $camp_opt)) $licenses[] = 6;
		if(in_array('OPT_FL_LICENSE_7', $camp_opt)) $licenses[] = 7;
		if(in_array('OPT_FL_LICENSE_8', $camp_opt)) $licenses[] = 8;

		if(count($licenses) > 0 ) $flink.="&license=".implode(',', $licenses);

	}


		
	// curl get
	$x = 'error';
	$url = $flink;
	curl_setopt ( $this->ch, CURLOPT_HTTPGET, 1 );
	curl_setopt ( $this->ch, CURLOPT_URL, trim ( $url ) );
	while ( trim ( $x ) != '' ) {
		$exec = curl_exec ( $this->ch );
		$x = curl_error ( $this->ch );
	}

	$result = unserialize ( $exec );
	 

	if (is_array ( $result )) {

		  echo '<br>Valid array returned from flicker ';
		
		//if photoset
		$imgs = array();
		
		if( $isAlbum ){
			
			if(is_array($result ['photoset'] ['photo'])){
				$imgs = $result ['photoset'] ['photo'];
			}
			
		}else{
			$imgs = $result ['photos'] ['photo'];
		}
		
		
			
		if (is_array ( $imgs )) {

			  echo '<br>Valid reply array returned with ' . count ( $imgs ) . ' child';

				
			if (count ( $imgs ) == 0) {
				  echo '<br>Keyword have no more images deactivating...';
				$query = "update {$this->wp_prefix}automatic_keywords set keyword_start = -1 where keyword_id=$kid ";
				$this->db->query ( $query );
					
				if(! in_array('OPT_NO_DEACTIVATE', $camp_opt))
				$this->deactivate_key($camp->camp_id, $keyword);
					
			}
		} else {
			  echo '<br>Did not find valid image array in the response ';
			$imgs = array ();
		}
	} else {
			
		  echo '<br>Flicker did not reuturn valid reply array ';
	}

	/*
	 * disable keyword if no new items if (count ( $search ) == 0) {   echo '<br>No more vids for this keyword deactivating it ..'; $query = "update {$this->wp_prefix}automatic_keywords set keyword_start = -1 where keyword_id=$kid"; $this->db->query ( $query ); return; }
	 */

	  echo '<ol>';

	foreach ( $imgs as $itm ) {
			
			
		$id = $itm ['id'];
		$data = serialize ( $itm );
			
		$item_link = 'http://flicker.com/' . $itm ['owner'] . '/' . $id;
		  echo '<li> Link:'.$item_link;
			
			
		if( $this->is_execluded($camp->camp_id, $item_link) ){
			  echo '<-- Execluded';
			continue;
		}
			
			

		if ( ! $this->is_duplicate($item_link) )  {
			$query = "INSERT INTO {$this->wp_prefix}automatic_general ( item_id , item_status , item_data ,item_type) values (  '$id', '0', '$data' ,'fl_{$camp->camp_id}_$keyword')  ";
			$this->db->query ( $query );
		} else {
			  echo ' <- duplicated <a href="'.get_edit_post_link($this->duplicate_id).'">#'.$this->duplicate_id.'</a>';
		}
			
	}

	  echo '</ol>';

}
	
	
/*
 * ---* flicker post ---
 */
function flicker_get_post($camp) {
	
	 
	
	$api_key = get_option ( 'wp_automatic_flicker', '' );

	if (trim ( $api_key ) == '') {
		  echo '<br>Flicker Api key required visit settings and add it ';
		exit ();
	}

	$camp_opt = unserialize ( $camp->camp_options );
	$keywords = explode ( ',', $camp->camp_keywords );

	foreach ( $keywords as $keyword ) {
			
		$keyword = trim($keyword);

		//update last keyword
		update_post_meta($camp->camp_id, 'last_keyword', trim($keyword));
			
		if (trim ( $keyword ) != '') {

			// getting links from the db for that keyword
			$query = "select * from {$this->wp_prefix}automatic_general where item_type=  'fl_{$camp->camp_id}_$keyword' ";
			$this->used_keyword=$keyword;

			$res = $this->db->get_results ( $query );

			// when no links lets get new links
			if (count ( $res ) == 0) {
				
				//clean any old cache for this keyword
				$query_delete = "delete from {$this->wp_prefix}automatic_general where item_type='fl_{$camp->camp_id}_$keyword' ";
				$this->db->query ( $query_delete );
				 
				$this->flicker_fetch_items ( $keyword, $camp );
				// getting links from the db for that keyword
					
				$res = $this->db->get_results ( $query );
			}


			//check if already duplicated
			//deleting duplicated items
			$res_count = count($res);
			for($i=0;$i< $res_count ;$i++){

				$t_row = $res[$i];
				$t_data =  unserialize ( $t_row->item_data );
				$t_link_url='http://flicker.com/' . $t_data ['owner'] . '/' . $t_row->item_id;

				if( $this->is_duplicate($t_link_url) ){
						
					//duplicated item let's delete
					unset($res[$i]);
						
					  echo '<br>Flicker image ('. $t_data ['title'] .') found cached but duplicated <a href="'.get_permalink($this->duplicate_id).'">#'.$this->duplicate_id.'</a>'  ;
						
					//delete the item
					$query = "delete from {$this->wp_prefix}automatic_general where id={$t_row->id} and item_type=  'fl_{$camp->camp_id}_$keyword' ";
					$this->db->query ( $query );
						
				}else{
					break;
				}

			}

			// check again if valid links found for that keyword otherwise skip it
			if (count ( $res ) > 0) {
					
				// lets process that link
				$ret = $res [$i];
					
				$data = unserialize ( $ret->item_data );
					
				
				$temp ['img_title'] = $data ['title'];
				$temp ['img_author'] = $data ['owner'];
				$temp ['img_src'] = "http://farm{$data['farm']}.staticflickr.com/{$data['server']}/{$data['id']}_{$data['secret']}.jpg";
				$temp ['img_src_s'] = "http://farm{$data['farm']}.staticflickr.com/{$data['server']}/{$data['id']}_{$data['secret']}_s.jpg";
				$temp ['img_src_q'] = "http://farm{$data['farm']}.staticflickr.com/{$data['server']}/{$data['id']}_{$data['secret']}_q.jpg";
				$temp ['img_src_t'] = "http://farm{$data['farm']}.staticflickr.com/{$data['server']}/{$data['id']}_{$data['secret']}_t.jpg";
				$temp ['img_src_m'] = "http://farm{$data['farm']}.staticflickr.com/{$data['server']}/{$data['id']}_{$data['secret']}_m.jpg";
				$temp ['img_src_n'] = "http://farm{$data['farm']}.staticflickr.com/{$data['server']}/{$data['id']}_{$data['secret']}_n.jpg";
				$temp ['img_src_z'] = "http://farm{$data['farm']}.staticflickr.com/{$data['server']}/{$data['id']}_{$data['secret']}_z.jpg";
				$temp ['img_src_c'] = "http://farm{$data['farm']}.staticflickr.com/{$data['server']}/{$data['id']}_{$data['secret']}_c.jpg";
				$temp ['img_src_b'] = "http://farm{$data['farm']}.staticflickr.com/{$data['server']}/{$data['id']}_{$data['secret']}_b.jpg";
				
				$temp ['img_src_o'] = $temp ['img_src_k'] = $temp ['img_src_h'] = $temp ['img_src_b'];
				
				$temp ['img_link'] = 'http://flicker.com/' . $data ['owner'] . '/' . $ret->item_id;
					
				  echo '<br>Found Link:<a href="'.$temp['img_link'].'">'.$temp ['img_title'].'</a>';
					
				// getting photo description
				// curl get
				$x = 'error';
				$url = "https://api.flickr.com/services/rest/?method=flickr.photos.getInfo&api_key=".trim($api_key)."&photo_id={$ret->item_id}&format=php_serial";
				
				//  echo '<br>Photo details flink:'.$url;
				
				curl_setopt ( $this->ch, CURLOPT_HTTPGET, 1 );
				curl_setopt ( $this->ch, CURLOPT_URL, trim ( $url ) );
				while ( trim ( $x ) != '' ) {
					$exec = curl_exec ( $this->ch );
					$x = curl_error ( $this->ch );
				}
					
				$exec = unserialize ( $exec );
					
				if (! is_array ( $exec )) {
					  echo '<br> Not valid array ';
				} else {

					$temp ['img_author_name'] = $exec ['photo'] ['owner'] ['username'];
					$temp ['img_description'] = $exec ['photo'] ['description'] ['_content'];
					$temp ['img_date_posted'] = date ( 'Y-m-d H:i:s', $exec ['photo'] ['dates'] ['posted'] );
					$temp ['img_date_taken'] = $exec ['photo'] ['dates'] ['taken'];
					$temp ['img_viewed'] = $exec ['photo'] ['views'];

					$tags = '';
					foreach ( $exec ['photo'] ['tags'] ['tag'] as $tag ) {
						$tags = $tags . ' , ' . $tag ['raw'];
					}

					$temp ['img_tags'] = $tags;
				}
					
				// update the link status to 1
				$query = "delete from {$this->wp_prefix}automatic_general where id={$ret->id}";
				$this->db->query ( $query );
				
				
				// if cache not active let's delete the cached videos and reset indexes
				if (! in_array ( 'OPT_FL_CACHE', $camp_opt )) {
					  echo '<br>Cache disabled claring cache ...';
					$query = "delete from {$this->wp_prefix}automatic_general where item_type='fl_{$camp->camp_id}_$keyword' ";
					$this->db->query ( $query );

					// reset index
					$query = "update {$this->wp_prefix}automatic_keywords set keyword_start =1 where keyword_camp={$camp->camp_id}";
					$this->db->query ( $query );
				}

				//big sizes _o , _k , _h
				if(stristr($camp->camp_post_content, 'img_src_o') || stristr($camp->camp_post_content, 'img_src_k') || stristr($camp->camp_post_content, 'img_src_h') || stristr($camp->camp_post_custom_v, 'img_src_o') || stristr($camp->camp_post_custom_v, 'img_src_k') || stristr($camp->camp_post_custom_v, 'img_src_h') ){
					  echo '<br>Getting big images sizes from Flicker for img with id:'.$data['id'].'...';
					
					$furl = "https://api.flickr.com/services/rest/?method=flickr.photos.getSizes&api_key=$api_key&format=php_serial&photo_id=".$data['id'];
					
					//curl get
					$x='error';
					 
					curl_setopt($this->ch, CURLOPT_HTTPGET, 1);
					curl_setopt($this->ch, CURLOPT_URL, trim($furl));
					$exec=curl_exec($this->ch);
					$x=curl_error($this->ch);
					
					 
					if(stristr($exec, '{s')){
						
						$sizesArr = unserialize($exec);
						$sizes = $sizesArr['sizes']['size'] ;
						
						
						
						foreach ($sizes as $size){
							
							
							if(stristr($size['source'], '_o')){
								$temp ['img_src_o'] = $size['source'];
							}elseif(stristr($size['source'], '_k')){
								$temp ['img_src_k'] = $size['source'];
							}elseif(stristr($size['source'], '_h')){
								$temp ['img_src_h'] = $size['source'];
							}
							
						}
						
						 
						
					}else{
						  echo '<br>Not valid reply from Flicker for sizes ignoring...';
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