<?php

// Main Class
require_once 'core.php';

Class WpAutomaticEnvato extends wp_automatic{


/*
 * ---* youtube get links ---
 */
function Envato_fetch_items($keyword, $camp) {

	  echo "<br>so I should now get some items from Envato for keyword:".$keyword ;

	//Api token
	$wp_automatic_envato_token = get_option('wp_automatic_envato_token','');
	
	if(trim($wp_automatic_envato_token) == ''){
		  echo '<br>Please visit the plugin settings page and add an Envato authorization token.';
		return;
	} 
 
	// ini options
	$camp_opt = unserialize ( $camp->camp_options );
	if( stristr($camp->camp_general, 'a:') ) $camp->camp_general=base64_encode($camp->camp_general);
	$camp_general = unserialize ( base64_decode( $camp->camp_general ) );
	$camp_general=array_map('wp_automatic_stripslashes', $camp_general);
	
	// option
	$cg_ev_filter = $camp_general['cg_ev_filter'].'.net';
	
	// items url
	$EnvatoSearchUrl = "https://api.envato.com/v1/discovery/search/search/item" ;
	
 	// get start-index for this keyword
	$query = "select keyword_start ,keyword_id from {$this->wp_prefix}automatic_keywords where keyword_name='$keyword' and keyword_camp={$camp->camp_id}";
	$rows = $this->db->get_results ( $query );
	@$row = $rows [0];
	
	//If no rows add a keyword record
	if(count($rows) == 0){
		$query="insert into {$this->wp_prefix}automatic_keywords(keyword_name,keyword_camp,keyword_start) values ('$keyword','{$camp->camp_id}',1)";
		$this->db->query($query);
		$kid = $this->db->insert_id;
		$start = 1;
		
	}else{
		$kid = $row->keyword_id;
		$start = $row->keyword_start;
	}
	
	 
	

	if ($start == - 1) {
		  echo '<- exhausted link';
			
		if( ! in_array( 'OPT_EV_CACHE' , $camp_opt )){
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
	
	if($start == 0)$start = 1;
	 
	  echo ' index:' . $start;
	
	if($start > 0){
		$EnvatoSearchUrl.= '?page=' .$start;
	}else{
		$EnvatoSearchUrl.= '?';
	}
	
	//keyword 
	if(trim($keyword) != '' && trim($keyword) != "*" ){
		$EnvatoSearchUrl.='&term='.urlencode(trim($keyword));
	}
	
	//tag
	if(in_array('OPT_EV_TAGS', $camp_opt)){
		
		$cg_ev_tags = trim($camp_general['cg_ev_tags']);
		
		if(trim($cg_ev_tags) != ''){
			  echo '<br>Tag Filtering:'.$cg_ev_tags;
			
			$EnvatoSearchUrl.= '&tags='.$cg_ev_tags;
			
		}
		
	}
	
	//cat
	if(in_array('OPT_EV_CAT', $camp_opt)){
	
		$cg_ev_cat = trim($camp_general['cg_ev_cat']);
	
		if(trim($cg_ev_cat) != ''){
			  echo '<br>Cat Filtering:'.$cg_ev_cat;
				
			$EnvatoSearchUrl.= '&category='.$cg_ev_cat;
				
		}
	
	}
	
	//username
	if(in_array('OPT_EV_AUTHOR', $camp_opt)){
	
		$cg_ev_author = trim($camp_general['cg_ev_author']);
	
		if(trim($cg_ev_author) != ''){
			  echo '<br>Author Filtering:'.$cg_ev_author;
	
			$EnvatoSearchUrl.= '&username='.$cg_ev_author;
	
		}
	
	}
	
	//sort
	$cg_ev_sort = $camp_general['cg_ev_sort'];

	if(trim($cg_ev_sort) != ''){
		$EnvatoSearchUrl.= '&sort_by='.$cg_ev_sort;
	}
	
	$cg_ev_sort_dir	= $camp_general['cg_ev_sort_dir'] ;
	
	if(trim($cg_ev_sort_dir) != ''){
		$EnvatoSearchUrl.= '&sort_direction='.$cg_ev_sort_dir;
	}
	
	//api parameters
	if(in_array('OPT_EV_API', $camp_opt)){
	
		$cg_ev_api = trim($camp_general['cg_ev_api']);
	
		if(trim($cg_ev_api) != ''){
	
			$EnvatoSearchUrl.=  $cg_ev_api;
	
		}
	
	}
	
	//site filter
	$EnvatoSearchUrl.= '&site='.$cg_ev_filter;
	
	  echo '<br>EnvatoUrl: '.$EnvatoSearchUrl;
	
	// update start index to start+1
	$nextstart = $start + 1;

	$query = "update {$this->wp_prefix}automatic_keywords set keyword_start = $nextstart where keyword_id=$kid ";
	$this->db->query ( $query );

	// get items
	// curl get
	$x = 'error';
	$url = $EnvatoSearchUrl;
	curl_setopt ( $this->ch, CURLOPT_HTTPGET, 1 );
	curl_setopt ( $this->ch, CURLOPT_URL, trim ( $url ) );
	
	$headers[] = "Authorization: Bearer $wp_automatic_envato_token";
	curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
	
	$exec = curl_exec ( $this->ch );
	$x = curl_error ( $this->ch );
	
	// error check
	if(trim($x) != ''){
		  echo '<br>Curl error:'.$x;
		return false;
	}
	
	//Empty Reply
	if(  trim($exec) == '' ){
		  echo '<br>Envato returned an empty reply';
		return false;
	}
	
	//Error
	if( stristr( $exec , 'error_description'    ) ){
		  echo '<br>Envato returned an error:'.$exec;
		return false;
	}
	
	//Validate
	if( stristr( $exec , 'error_description'  ) ){
		  echo '<br>Envato returned an error:'.$exec;
		return false;
	}
	
	//Validate
	if(! stristr( $exec , '"took"'  ) ){
		  echo '<br>Envato returned an error:'.$exec;
		
		//if page 60 reset
		if(stristr($exec, 'page does not have a valid value')){
			
			  echo '<br>Keyword have no more items deactivating...';
			$query = "update {$this->wp_prefix}automatic_keywords set keyword_start = -1 where keyword_id=$kid ";
			$this->db->query ( $query );
			
			if(! in_array('OPT_NO_DEACTIVATE', $camp_opt))
			$this->deactivate_key($camp->camp_id, $keyword);
		}
		
		
		return false;
	}
	
	$jsonReply = json_decode($exec);
	$allItms = $jsonReply->matches;
	 	
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
		
 		$itm['item_id'] = $item->id;
 		$itm['item_title'] = $item->name;
 		$itm['item_link'] = $item->url;
 		$itm['item_description'] = $item->description_html;
 		$itm['item_category'] = $item->classification;
 		$itm['item_category_url'] = $item->classification_url;
 		$itm['item_price'] = $item->price_cents / 100;
 		$itm['item_author'] = $item->author_username;
 		$itm['item_author_url'] = $item->author_username;
 		$itm['item_published_at'] = $item->published_at;
 		$itm['item_updated_at'] = $item->updated_at;
 		$itm['item_author'] = $item->author_username;
 		$itm['item_author_url'] = $item->author_url;
 		$itm['item_author_image'] = $item->author_image;
 		$itm['item_rating'] = $item->rating->rating;
 		$itm['item_tags'] = implode(',', $item->tags);
 		$itm['item_author'] = $item->author_username;
 		$itm['item_sales'] = $item->number_of_sales;

 		$item_link = $item->url;
 		$id = $item->id;
/*
 		  echo '<pre>';
 		print_r($allItms);
 		
 		exit; 
 			
 		*/
 		
 		//site specific parameters
 		if($cg_ev_filter == 'codecanyon.net' || $cg_ev_filter == 'themeforest.net' || $cg_ev_filter == '3docean.net'){
 			$previews = (Array)$item->previews;
 			$previewsFirst = (reset( $previews ));
 			$itm['preview_img'] = $previewsFirst->landscape_url;
 			$itm['preview_icon'] = $previewsFirst->icon_url;
 			
 			if(isset($item->previews->live_site)){
 				$itm['live_site'] = $item->previews->live_site->url;
 			}else{
 				$itm['live_site'] = '';
 			}
 			
 		}elseif($cg_ev_filter == 'photodune.net'){
 			
 			$itm['preview_icon'] = $item->previews->icon_with_thumbnail_preview->icon_url;
 			$itm['preview_img'] = $item->previews->icon_with_thumbnail_preview->thumbnail_url;
 		
 		}elseif($cg_ev_filter == 'videohive.net'){
 			
 			$itm['preview_icon'] = $item->previews->icon_with_video_preview->icon_url;
 			$itm['preview_img'] = $item->previews->icon_with_video_preview->landscape_url;
 			$itm['preview_vid'] = $item->previews->icon_with_video_preview->video_url;
 			
 		}elseif($cg_ev_filter == 'audiojungle.net'){
 			
 			$itm['preview_icon'] = $item->previews->icon_with_audio_preview->icon_url;
 			$itm['preview_mp3'] = $item->previews->icon_with_audio_preview->mp3_url;
 			
 		}elseif($cg_ev_filter == 'graphicriver.net'){
 			
 			$itm['preview_icon'] = $item->previews->icon_with_square_preview->icon_url;
 			$itm['preview_img'] = $item->previews->icon_with_square_preview->square_url;
 			
 		}
 		
 		
 		/*
 		  echo '<pre>';
 		print_r($itm);
 		exit;
 		*/
 		
		
		if( $this->is_execluded($camp->camp_id, $item_link) ){
			  echo '<-- Execluded';
			continue;
		}
		
		  echo '<li> Link:'.$item_link;

		if ( ! $this->is_duplicate($item_link) )  {
			
			$data = ( base64_encode( serialize ( $itm ) ) );
			
			 
			$query = "INSERT INTO {$this->wp_prefix}automatic_general ( item_id , item_status , item_data ,item_type) values (  '$id', '0', '$data' ,'en_{$camp->camp_id}_$keyword')  ";
			$this->db->query ( $query );
			
		} else {
			  echo ' <- duplicated <a href="'.get_edit_post_link($this->duplicate_id).'">#'.$this->duplicate_id.'</a>';
		}
			
	}

	  echo '</ol>';

}
	
 	
/*
 * ---* Envato post ---
 */
function Envato_get_post($camp) {

	 
	// Campaign options
	$camp_opt = unserialize (  $camp->camp_options );
	$keywords = explode ( ',', $camp->camp_keywords );
 	
	foreach ( $keywords as $keyword ) {
			
		$keyword = trim($keyword);

		//update last keyword
		update_post_meta($camp->camp_id, 'last_keyword', trim($keyword));
			
		if (trim ( $keyword ) != '') {

			// getting links from the db for that keyword
			$query = "select * from {$this->wp_prefix}automatic_general where item_type=  'en_{$camp->camp_id}_$keyword' ";
			$this->used_keyword=$keyword;
			$res = $this->db->get_results ( $query );

			// when no links lets get new links
			if (count ( $res ) == 0) {
				
				//clean any old cache for this keyword
				$query_delete = "delete from {$this->wp_prefix}automatic_general where item_type='en_{$camp->camp_id}_$keyword' ";
				$this->db->query ( $query_delete );

				// get new fresh items
				$this->Envato_fetch_items ( $keyword, $camp );
				
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
						
					  echo '<br>Envato item ('. $t_link_url .') found cached but duplicated <a href="'.get_permalink($this->duplicate_id).'">#'.$this->duplicate_id.'</a>'  ;
						
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
				
				// update the link status to 1
				 $query = "delete from {$this->wp_prefix}automatic_general where id={$ret->id}";
				 $this->db->query ( $query );
					
				$this->db->query ( $query );
					
				// if cache not active let's delete the cached videos and reset indexes
				if (! in_array ( 'OPT_EV_CACHE', $camp_opt )) {
					  echo '<br>Cache disabled claring cache ...';
					$query = "delete from {$this->wp_prefix}automatic_general where item_type='en_{$camp->camp_id}_$keyword' ";
					$this->db->query ( $query );

					// reset index
					$query = "update {$this->wp_prefix}automatic_keywords set keyword_start = 0 where keyword_camp={$camp->camp_id}";
					$this->db->query ( $query );
					
				}
				
				$wp_automatic_envato_user = get_option('wp_automatic_envato_user','');
				$wp_automatic_envato_ir = get_option('wp_automatic_envato_ir' , ''); 
				
				$temp['affiliate_id'] = $wp_automatic_envato_user;
				
				$temp['live_site_affiliate'] = isset($temp['live_site']) ? $temp['live_site'] : '' ;
				$temp['item_link_affiliate'] = $temp['item_link'] ;
				
				if(trim($wp_automatic_envato_ir) != ''  ){
					$temp['item_link_affiliate'] = $wp_automatic_envato_ir . '?u=' . urlencode( $temp['item_link'] ) ;
					$temp['live_site_affiliate'] = trim( $temp['live_site_affiliate'] ) != '' ?  $wp_automatic_envato_ir . '?u=' . urlencode( $temp['live_site'] ) : '' ;
				}
				
				if(trim($wp_automatic_envato_user) == ''){
					  echo '<br>Visit the plugin settings page and add your Envato ID for affiliate integration';
				}
				   
				return $temp;
				
			} else {
					
				  echo '<br>No links found for this keyword';
			}
		} // if trim
	} // foreach keyword
}

}