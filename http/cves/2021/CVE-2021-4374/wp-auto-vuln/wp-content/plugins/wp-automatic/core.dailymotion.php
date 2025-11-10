<?php

// Main Class
require_once 'core.php';

Class WpAutomaticDailyMotion extends wp_automatic{


	/*
	 * ---* youtube get links ---
	 */
	function DailyMotion_fetch_items($keyword, $camp) {

		$keyword = trim($keyword);
		
		  echo "<br>so I should now get some items from DailyMotion for keyword:".$keyword ;

		// ini options
		$camp_opt = unserialize ( $camp->camp_options );
		if( stristr($camp->camp_general, 'a:') ) $camp->camp_general=base64_encode($camp->camp_general);
		$camp_general = unserialize ( base64_decode( $camp->camp_general ) );
		$camp_general=array_map('wp_automatic_stripslashes', $camp_general);

		
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

		if ($start == - 1 || $start >100) {
			  echo '<- exhausted link';
				
			if( ! in_array( 'OPT_DM_CACHE' , $camp_opt )){
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

		
		$filter = array() ;
		
		
		//build filter
		if( $keyword != '*' ){
			$filter[] = 'search='.urlencode($keyword);
		}
		
		//specific owner / playlist
		if( in_array('OPT_DM_PLAYLIST', $camp_opt)  && trim($camp_general['cg_dm_playlist']) != '' ){
			
			$cg_dm_playlist_txt = trim($camp_general['cg_dm_playlist']);
			$filter[]= 'playlist='. $cg_dm_playlist_txt;
			
		}elseif(in_array('OPT_DM_USER', $camp_opt)){
			$cg_dm_user = $camp_general['cg_dm_user'];
			if(trim($cg_dm_user) != ''){
				$filter[]= 'owner='.trim($cg_dm_user);
			}
		}
		
		//date filter 
		if(trim($this->minimum_post_timestamp) != '' &&  $this->minimum_post_timestamp_camp == $camp->camp_id ){
			$filter[]= 'created_after='.trim($this->minimum_post_timestamp);
		}
		
		//country filter 
		if(in_array('OPT_DM_LIMIT_CTRY', $camp_opt)){
			
			$cg_dm_ctr = trim($camp_general['cg_dm_ctr']);
			
			if($cg_dm_ctr != ''){
				$filter[] = 'country='.$cg_dm_ctr;
			}
			
		}
		
		//lang filter
		if(in_array('OPT_DM_LIMIT_LANG', $camp_opt)){
			
			$cg_dm_lang = trim($camp_general['cg_dm_lang']);

			if($cg_dm_lang != ''){
				$filter[] = 'detected_language='.$cg_dm_lang;
			}
				
		}
		
		//channel filter
		if(in_array('OPT_DM_LIMIT_CHANNEL', $camp_opt)){
			$cg_dm_channel = trim($camp_general['cg_dm_channel']);
			
			if($cg_dm_channel != ''){
				$filter[] = 'channel='.$cg_dm_channel;
			}
		}
		
		// update start index to start+1
		$nextstart = $start + 1;

		$query = "update {$this->wp_prefix}automatic_keywords set keyword_start = $nextstart where keyword_id=$kid ";
		$this->db->query ( $query );

		// get items

		//dm class
		require_once 'inc/class.dailymotion.php';
		$dmo = new wpAutomatic_DailyMotion($this->ch);
		
		try {
			$allItms = $dmo->getVideosByKeyword($filter,$start);
		} catch (Exception $e) {
			  echo 'Exception:'.$e->getMessage();
			return false;
		}
		 
		// Check returned items count
		if ( count($allItms) > 0 ) {
			
			  echo '<br>Valid reply returned with ' . count($allItms) . ' item';
			
			// reverse array 
			if(in_array('OPT_DM_REVERSE', $camp_opt)){
				  echo '<br>Reversing order...';
				$allItms = array_reverse($allItms);
			}
			
			
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
			$itm['item_title'] = $item->title;
			$itm['item_image'] = $item->thumbnail_url;
			$itm['item_link'] = 'http://www.dailymotion.com/video/'.$item->id;
			$itm['item_duration'] = gmdate("H:i:s", $item->duration);
			$itm['item_views'] = $item->views_total;
			$itm['item_description'] = $item->description;
			$itm['item_channel'] = $item->channel;
			$itm['item_category_url'] = 'http://www.dailymotion.com/en/'.$item->channel.'/news/1';
			$itm['item_author'] = $item->{'owner.screenname'};
			$itm['item_author_id'] = $item->{'owner.username'};
			$itm['item_author_url'] = 'http://www.dailymotion.com/'.$item->{'owner.username'};
			$itm['item_author_image'] = $item->{'owner.avatar_360_url'};
			$itm['item_published_at'] = $item->created_time;
			$itm['item_likes'] = $item->likes_total;
			
			//tags
			$itm['item_tags'] = ''; // ini
			if(isset($item->tags) && is_array($item->tags)){
				$itm['item_tags'] = implode(',', $item->tags );
			}
			 
			$item_link = $itm['item_link'];
			$id = $item->id;

			if( $this->is_execluded($camp->camp_id, $item_link) ){
				  echo '<-- Execluded';
				continue;
			}

			  echo '<li> Link:'.$item_link;

			if ( ! $this->is_duplicate($item_link) )  {
					
				$data = ( base64_encode( serialize ( $itm ) ) );
					
				$query = "INSERT INTO {$this->wp_prefix}automatic_general ( item_id , item_status , item_data ,item_type) values (  '$id', '0', '$data' ,'dm_{$camp->camp_id}_$keyword')  ";
				$this->db->query ( $query );
					
			} else {
				  echo ' <- duplicated <a href="'.get_edit_post_link($this->duplicate_id).'">#'.$this->duplicate_id.'</a>';
			}
				
		}

		  echo '</ol>';

	}


	/*
	 * ---* DailyMotion post ---
	 */
	function DailyMotion_get_post($camp) {


		// Campaign options
		$camp_opt = unserialize (  $camp->camp_options );
		$keywords = explode ( ',', $camp->camp_keywords );
		
		if( stristr($camp->camp_general, 'a:') ) $camp->camp_general=base64_encode($camp->camp_general);
		$camp_general = unserialize ( base64_decode( $camp->camp_general ) );
		$camp_general=array_map('wp_automatic_stripslashes', $camp_general);

		foreach ( $keywords as $keyword ) {
				
			$keyword = trim($keyword);

			//update last keyword
			update_post_meta($camp->camp_id, 'last_keyword', trim($keyword));
				
			if (trim ( $keyword ) != '') {

				// getting links from the db for that keyword
				$query = "select * from {$this->wp_prefix}automatic_general where item_type=  'dm_{$camp->camp_id}_$keyword' ";
				$this->used_keyword=$keyword;
				$res = $this->db->get_results ( $query );

				// when no links lets get new links
				if (count ( $res ) == 0) {
					
					//clean any old cache for this keyword
					$query_delete = "delete from {$this->wp_prefix}automatic_general where item_type='dm_{$camp->camp_id}_$keyword' ";
					$this->db->query ( $query_delete );

					// get new fresh items
					$this->DailyMotion_fetch_items ( $keyword, $camp );

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

						  echo '<br>DailyMotion item ('. $t_link_url .') found cached but duplicated <a href="'.get_permalink($this->duplicate_id).'">#'.$this->duplicate_id.'</a>'  ;
 						
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
					if (! in_array ( 'OPT_DM_CACHE', $camp_opt )) {
						  echo '<br>Cache disabled claring cache ...';
						$query = "delete from {$this->wp_prefix}automatic_general where item_type='dm_{$camp->camp_id}_$keyword' ";
						$this->db->query ( $query );

						// reset index
						$query = "update {$this->wp_prefix}automatic_keywords set keyword_start = 0 where keyword_camp={$camp->camp_id}";
						$this->db->query ( $query );
							
					}

					//custom width
					$vid_width  = 560;
					$vid_height = 315;
					
					if(trim($camp_general['cg_dm_width']) != '' ) $vid_width = trim($camp_general['cg_dm_width']) ; 
					if(trim($camp_general['cg_dm_height']) != '' ) $vid_height = trim($camp_general['cg_dm_height']) ;
					
					//additional paramters i.e ?autoPlay=1
					$additionalParams = '';
					
					if(in_array('OPT_DM_AUTO', $camp_opt)){
						$additionalParams = '?autoPlay=1';
					}
					
					//vid_player
					$temp['vid_player'] = '<iframe frameborder="0" width="'.$vid_width.'" height="'.$vid_height.'" src="//www.dailymotion.com/embed/video/'.$temp['item_id'].''.$additionalParams.'" allowfullscreen></iframe>';

					//date item_published_at
					$temp['item_published_at_formated'] = get_date_from_gmt( gmdate('Y-m-d H:i:s' ,$temp['item_published_at'] )) ; 
					
					//source_link
					$temp['source_link'] = $temp['item_link'];
					
					//used tags
					$this->used_tags = $temp['item_tags'];
					 
					return $temp;

				} else {
						
					  echo '<br>No links found for this keyword';
				}
			} // if trim
		} // foreach keyword
	}

}