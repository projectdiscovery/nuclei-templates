<?php

// Main Class
require_once 'core.php';

Class WpAutomaticPinterest extends wp_automatic{


/**function pinterest_get_post: return valid pinterest pin to post
 * @param unknown $camp
 */
function pinterest_get_post($camp){

	//ini keywords
	$camp_opt = unserialize ( $camp->camp_options );
	$keywords = explode ( ',', $camp->camp_keywords );
	$camp_general=unserialize(base64_decode($camp->camp_general));

	//looping keywords
	foreach ( $keywords as $keyword ) {
			
		$keyword = trim($keyword);

		//update last keyword
		update_post_meta($camp->camp_id, 'last_keyword', trim($keyword));

		//when valid keyword
		if (trim ( $keyword ) != '') {

			//record current used keyword
			$this->used_keyword=$keyword;


			// getting links from the db for that keyword
			$query = "select * from {$this->wp_prefix}automatic_general where item_type=  'pt_{$camp->camp_id}_$keyword' ";
			$res = $this->db->get_results ( $query );

			// when no links lets get new links
			if (count ( $res ) == 0) {
				
				//clean any old cache for this keyword
				$query_delete = "delete from {$this->wp_prefix}automatic_general where item_type='pt_{$camp->camp_id}_$keyword' ";
				$this->db->query ( $query_delete );

				//get new links
				$this->pinterest_fetch_items ( $keyword, $camp );

				// getting links from the db for that keyword
				$res = $this->db->get_results ( $query );
			}

			//check if already duplicated
			//deleting duplicated items
			$res_count = count($res);
			for($i=0;$i< $res_count;$i++){

				$t_row = $res[$i];

				$t_data =  unserialize (base64_decode( $t_row->item_data) );

				$t_link_url=$t_data['pin_url'];

				if( $this->is_duplicate($t_link_url) ){
						
					//duplicated item let's delete
					unset($res[$i]);
						
					  echo '<br>Pinterest pin ('. $t_data ['pin_title'] .') found cached but duplicated <a href="'.get_permalink($this->duplicate_id).'">#'.$this->duplicate_id.'</a>'  ;
						
					//delete the item
					$query = "delete from {$this->wp_prefix}automatic_general where id= {$t_row->id} ";
					$this->db->query ( $query );
						
				}else{
					break;
				}

			}

			// check again if valid links found for that keyword otherwise skip it
			if (count ( $res ) > 0) {

				// lets process that link
				$ret = $res [$i];
					
				$temp = unserialize ( base64_decode($ret->item_data ));
					
				//generating title for pinterest
				if( trim($temp['pin_title']) == '' ){
						
					if(in_array('OPT_PT_AUTO_TITLE', $camp_opt)){

						  echo '<br>No title generating...';

						$cg_pt_title_count = $camp_general['cg_pt_title_count'];
						if(! is_numeric($cg_pt_title_count)) $cg_pt_title_count = 80;
							
						$newTitle = substr( strip_tags( $temp['pin_description']), 0,  $cg_pt_title_count);
						
						if($newTitle == strip_tags( $temp['pin_description']) ){
							$temp['pin_title'] = $newTitle ;
						}else{
						    $temp['pin_title'] = $newTitle.'...';
						}
						
					}
					
					
					if( trim($temp['pin_title']) == '' ){
							
						$temp['pin_title'] = '(notitle)';
							
					}

				}


				//report link
				  echo '<br>Found Link:'.$temp['pin_url'] ;

				// update the link status to 1
				  $query = "delete from {$this->wp_prefix}automatic_general where id={$ret->id}";
				  $this->db->query ( $query );

				// if cache not active let's delete the cached videos and reset indexes
				if (! in_array ( 'OPT_PT_CACHE', $camp_opt )) {
					echo '<br>Cache disabled claring cache ...';
					$query = "delete from {$this->wp_prefix}automatic_general where item_type='pt_{$camp->camp_id}_$keyword' ";
					$this->db->query ( $query );

					// reset index
					$query = "update {$this->wp_prefix}automatic_keywords set keyword_start =1 where keyword_camp={$camp->camp_id}";
					$this->db->query ( $query );
				}
					
				//fix tags links
				$temp['pin_description'] = str_replace('<a href="/', '<a href="https://pinterest.com/', $temp['pin_description'] );

					

				return $temp;
					
					
			} else {

				  echo '<br>No links found for this keyword';
			}
		} // if trim
	} // foreach keyword



}
	
/**
 * function pinterest_fetch_items: get new items from pinterest for specific keyword
 * @param unknown $keyword
 * @param unknown $camp
 */
function pinterest_fetch_items($keyword,$camp){

	//report
	  echo "<br>So I should now get some pins from Pinterest for keyword :" . $keyword;

		
	// ini options
	$camp_opt = unserialize ( $camp->camp_options );
	if( stristr($camp->camp_general, 'a:') ) $camp->camp_general=base64_encode($camp->camp_general);
	$camp_general = unserialize ( base64_decode( $camp->camp_general ) );
	$camp_general=array_map('wp_automatic_stripslashes', $camp_general);
	$pin_host = 'www.pinterest.com';

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

		if( ! in_array( 'OPT_PT_CACHE' , $camp_opt )){
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

	}elseif( ! in_array( 'OPT_PT_CACHE' , $camp_opt ) ){
		$start =1;
		echo '<br>Cache disabled resetting index to 1';
	}
 
	echo ' index:' . $start;

	// update start index to start+1
	$nextstart = $start + 1;
	$query = "update {$this->wp_prefix}automatic_keywords set keyword_start = $nextstart where keyword_id=$kid ";
	$this->db->query ( $query );

	//prepare referes
	curl_setopt($this->ch,CURLOPT_HTTPHEADER,array('Referer: https://www.pinterest.com/','X-NEW-APP: 1','X-Requested-With: XMLHttpRequest'));

	//if specific user posting
	if(in_array('OPT_PT_USER', $camp_opt)){

			
		$cg_pt_user_channel = $camp_general['cg_pt_user_channel'];
		$author = $camp_general['cg_pt_user'];
			
			
		if( $cg_pt_user_channel == 'users' ){

			// get requrest url from the zero index

			if( $start == 1 ){

				//use first basse query
				$url ="https://www.pinterest.com/resource/UserPinsResource/get/?source_url=%2Fwelkerpatrick%2Fpins%2F&data=%7B%22options%22%3A%7B%22username%22%3A%22welkerpatrick%22%7D%2C%22context%22%3A%7B%7D%7D&module_path=App()%3EUserProfilePage(resource%3DUserResource(username%3Dwelkerpatrick))%3EUserInfoBar(tab%3Dpins%2C+spinner%3D%5Bobject+Object%5D%2C+resource%3DUserResource(username%3Dwelkerpatrick%2C+invite_code%3Dnull))&_=1430667298559";

			}else{
					
				//not first page get the bookmark
				$wp_pinterest_bookmark = get_post_meta ($camp->camp_id,'wp_pinterest_bookmark'.md5($keyword),1);
					
				if(trim($wp_pinterest_bookmark) == ''){
					  echo '<br>No Bookmark';
					$url ="https://www.pinterest.com/resource/UserPinsResource/get/?source_url=%2Fwelkerpatrick%2Fpins%2F&data=%7B%22options%22%3A%7B%22username%22%3A%22welkerpatrick%22%7D%2C%22context%22%3A%7B%7D%7D&module_path=App()%3EUserProfilePage(resource%3DUserResource(username%3Dwelkerpatrick))%3EUserInfoBar(tab%3Dpins%2C+spinner%3D%5Bobject+Object%5D%2C+resource%3DUserResource(username%3Dwelkerpatrick%2C+invite_code%3Dnull))&_=1430667298559";
				}else{
					  echo '<br>Bookmark:'.$wp_pinterest_bookmark;
					$url = "https://www.pinterest.com/resource/UserPinsResource/get/?source_url=%2Fwelkerpatrick%2Fpins%2F&data=%7B%22options%22%3A%7B%22username%22%3A%22welkerpatrick%22%2C%22bookmarks%22%3A%5B%22".urlencode($wp_pinterest_bookmark)."%22%5D%7D%2C%22context%22%3A%7B%7D%7D&module_path=App()%3EUserProfilePage(resource%3DUserResource(username%3Dwelkerpatrick))%3EUserInfoBar(tab%3Dpins%2C+spinner%3D%5Bobject+Object%5D%2C+resource%3DUserResource(username%3Dwelkerpatrick%2C+invite_code%3Dnull))&_=1430667298566";
				}
					
			}

			//replace username
			$url = str_replace( 'welkerpatrick', $author, $url);

		}else{

			//board id
			$wp_pinterest_board_id = get_post_meta($camp->camp_id,'wp_pinterest_board_id', 1);

			if(trim($wp_pinterest_board_id) == ''){
					
				//must get board id from it's page
				//curl get
				$x='error';
				$url='https://api.pinterest.com/v3/pidgets/boards/'.trim($author).'/pins/';
				
				  echo '<br>Board id Link:'.$url;
				curl_setopt($this->ch, CURLOPT_HTTPGET, 1);
				curl_setopt($this->ch, CURLOPT_URL, trim($url));
				$exec=curl_exec($this->ch);
				$x=curl_error($this->ch);
				
				 
				
				//extract id
				$exIDjson = json_decode($exec);
				$exID = $exIDjson->data->board->id;
					
				  echo '<br>Extracted board ID:'.$exID;
					
				if(is_numeric($exID)){
					update_post_meta($camp->camp_id, 'wp_pinterest_board_id', $exID);
					$wp_pinterest_board_id = $exID;
				}
			}

			if(! is_numeric($wp_pinterest_board_id)){
				  echo '<br>Can not get valid board id. make sure your data is correct';
				return false;
			}


			//specific board
			if($start == 1){
				$url ="https://www.pinterest.com/resource/BoardFeedResource/get/?source_url=%2Fwelkerpatrick%2Frecipes%2F&data=%7B%22options%22%3A%7B%22board_id%22%3A%221266774834151486%22%2C%22board_url%22%3A%22%2Fwelkerpatrick%2Frecipes%2F%22%2C%22board_layout%22%3A%22default%22%2C%22prepend%22%3Atrue%2C%22page_size%22%3Anull%2C%22access%22%3A%5B%5D%7D%2C%22context%22%3A%7B%7D%7D&module_path=App()%3EUserProfilePage(resource%3DUserResource(username%3Dwelkerpatrick))%3EUserProfileContent(resource%3DUserResource(username%3Dwelkerpatrick))%3EUserBoards()%3EGrid(resource%3DProfileBoardsResource(username%3Dwelkerpatrick))%3EGridItems(resource%3DProfileBoardsResource(username%3Dwelkerpatrick))%3EBoard(show_board_context%3Dfalse%2C+show_user_icon%3Dfalse%2C+view_type%3DboardCoverImage%2C+component_type%3D1%2C+resource%3DBoardResource(board_id%3D1266774834151486))&_=1430694254166";
			}else{
				$wp_pinterest_bookmark = get_post_meta ($camp->camp_id,'wp_pinterest_bookmark'.md5($keyword),1);
					
				if(trim($wp_pinterest_bookmark) == ''){
					$url ="https://www.pinterest.com/resource/BoardFeedResource/get/?source_url=%2Fwelkerpatrick%2Frecipes%2F&data=%7B%22options%22%3A%7B%22board_id%22%3A%221266774834151486%22%2C%22board_url%22%3A%22%2Fwelkerpatrick%2Frecipes%2F%22%2C%22board_layout%22%3A%22default%22%2C%22prepend%22%3Atrue%2C%22page_size%22%3Anull%2C%22access%22%3A%5B%5D%7D%2C%22context%22%3A%7B%7D%7D&module_path=App()%3EUserProfilePage(resource%3DUserResource(username%3Dwelkerpatrick))%3EUserProfileContent(resource%3DUserResource(username%3Dwelkerpatrick))%3EUserBoards()%3EGrid(resource%3DProfileBoardsResource(username%3Dwelkerpatrick))%3EGridItems(resource%3DProfileBoardsResource(username%3Dwelkerpatrick))%3EBoard(show_board_context%3Dfalse%2C+show_user_icon%3Dfalse%2C+view_type%3DboardCoverImage%2C+component_type%3D1%2C+resource%3DBoardResource(board_id%3D1266774834151486))&_=1430694254166";
				}else{
					  echo '<br>Bookmark:'.$wp_pinterest_bookmark;
					$url ="https://www.pinterest.com/resource/BoardFeedResource/get/?source_url=%2Fwelkerpatrick%2Frecipes%2F&data=%7B%22options%22%3A%7B%22board_id%22%3A%221266774834151486%22%2C%22board_url%22%3A%22%2Fwelkerpatrick%2Frecipes%2F%22%2C%22board_layout%22%3A%22default%22%2C%22prepend%22%3Atrue%2C%22page_size%22%3Anull%2C%22access%22%3A%5B%5D%2C%22bookmarks%22%3A%5B%22".urlencode($wp_pinterest_bookmark)."%22%5D%7D%2C%22context%22%3A%7B%7D%7D&module_path=UserProfilePage(resource%3DUserResource(username%3Dwelkerpatrick))%3EUserProfileContent(resource%3DUserResource(username%3Dwelkerpatrick))%3EUserBoards()%3EGrid(resource%3DProfileBoardsResource(username%3Dwelkerpatrick))%3EGridItems(resource%3DProfileBoardsResource(username%3Dwelkerpatrick))%3EBoard(show_board_context%3Dfalse%2C+show_user_icon%3Dfalse%2C+view_type%3DboardCoverImage%2C+component_type%3D1%2C+resource%3DBoardResource(board_id%3D1266774834151486))&_=1430694254182";
						
				}
					
			}

			//replacing board id
			$url = str_replace('1266774834151486', $wp_pinterest_board_id, $url);

		}
			

	}else{

		// get requrest url from the zero index
		if( $start == 1 ){
				
			//use first basse query
			$url ="https://www.pinterest.com/resource/BaseSearchResource/get/?source_url=%2Fsearch%2Fpins%2F%3Fq%3Darabian&data=%7B%22options%22%3A%7B%22restrict%22%3Anull%2C%22scope%22%3A%22pins%22%2C%22constraint_string%22%3Anull%2C%22show_scope_selector%22%3Atrue%2C%22query%22%3A%22arabian%22%7D%2C%22context%22%3A%7B%7D%2C%22module%22%3A%7B%22name%22%3A%22SearchPage%22%2C%22options%22%3A%7B%22restrict%22%3Anull%2C%22scope%22%3A%22pins%22%2C%22constraint_string%22%3Anull%2C%22show_scope_selector%22%3Atrue%2C%22query%22%3A%22arabian%22%7D%7D%2C%22render_type%22%3A1%2C%22error_strategy%22%3A0%7D&module_path=App()%3EHeader()%3ESearchForm()%3ETypeaheadField(enable_recent_queries%3Dtrue%2C+support_guided_search%3Dtrue%2C+resource_name%3DAdvancedTypeaheadResource%2C+name%3Dq%2C+tags%3Dautocomplete%2C+class_name%3DbuttonOnRight%2C+type%3Dtokenized%2C+prefetch_on_focus%3Dtrue%2C+value%3D%22%22%2C+input_log_element_type%3D227%2C+hide_tokens_on_focus%3Dundefined%2C+support_advanced_typeahead%3Dfalse%2C+view_type%3Dguided%2C+populate_on_result_highlight%3Dtrue%2C+search_delay%3D0%2C+search_on_focus%3Dtrue%2C+placeholder%3DDiscover%2C+show_remove_all%3Dtrue)&_=1430685210358";
				
		}else{
				
			//not first page get the bookmark
			$wp_pinterest_bookmark = get_post_meta ($camp->camp_id,'wp_pinterest_bookmark'.md5($keyword),1);
				
			if(trim($wp_pinterest_bookmark) == ''){
				  echo '<br>No Bookmark';
				$url ="https://www.pinterest.com/resource/BaseSearchResource/get/?source_url=%2Fsearch%2Fpins%2F%3Fq%3Darabian&data=%7B%22options%22%3A%7B%22restrict%22%3Anull%2C%22scope%22%3A%22pins%22%2C%22constraint_string%22%3Anull%2C%22show_scope_selector%22%3Atrue%2C%22query%22%3A%22arabian%22%7D%2C%22context%22%3A%7B%7D%2C%22module%22%3A%7B%22name%22%3A%22SearchPage%22%2C%22options%22%3A%7B%22restrict%22%3Anull%2C%22scope%22%3A%22pins%22%2C%22constraint_string%22%3Anull%2C%22show_scope_selector%22%3Atrue%2C%22query%22%3A%22arabian%22%7D%7D%2C%22render_type%22%3A1%2C%22error_strategy%22%3A0%7D&module_path=App()%3EHeader()%3ESearchForm()%3ETypeaheadField(enable_recent_queries%3Dtrue%2C+support_guided_search%3Dtrue%2C+resource_name%3DAdvancedTypeaheadResource%2C+name%3Dq%2C+tags%3Dautocomplete%2C+class_name%3DbuttonOnRight%2C+type%3Dtokenized%2C+prefetch_on_focus%3Dtrue%2C+value%3D%22%22%2C+input_log_element_type%3D227%2C+hide_tokens_on_focus%3Dundefined%2C+support_advanced_typeahead%3Dfalse%2C+view_type%3Dguided%2C+populate_on_result_highlight%3Dtrue%2C+search_delay%3D0%2C+search_on_focus%3Dtrue%2C+placeholder%3DDiscover%2C+show_remove_all%3Dtrue)&_=1430685210358";
			}else{
				  echo '<br>Bookmark:'.$wp_pinterest_bookmark;
				$url = "https://www.pinterest.com/resource/SearchResource/get/?source_url=%2Fsearch%2Fpins%2F%3Fq%3Darabian&data=%7B%22options%22%3A%7B%22layout%22%3Anull%2C%22places%22%3Afalse%2C%22constraint_string%22%3Anull%2C%22show_scope_selector%22%3Atrue%2C%22query%22%3A%22arabian%22%2C%22scope%22%3A%22pins%22%2C%22bookmarks%22%3A%5B%22".urlencode($wp_pinterest_bookmark)."%22%5D%7D%2C%22context%22%3A%7B%7D%7D&module_path=App()%3EHeader()%3ESearchForm()%3ETypeaheadField(enable_recent_queries%3Dtrue%2C+support_guided_search%3Dtrue%2C+resource_name%3DAdvancedTypeaheadResource%2C+name%3Dq%2C+tags%3Dautocomplete%2C+class_name%3DbuttonOnRight%2C+type%3Dtokenized%2C+prefetch_on_focus%3Dtrue%2C+value%3D%22%22%2C+input_log_element_type%3D227%2C+hide_tokens_on_focus%3Dundefined%2C+support_advanced_typeahead%3Dfalse%2C+view_type%3Dguided%2C+populate_on_result_highlight%3Dtrue%2C+search_delay%3D0%2C+search_on_focus%3Dtrue%2C+placeholder%3DDiscover%2C+show_remove_all%3Dtrue)&_=1430685210363";
			}
				
		}
			
		//replace keyword
		$url = str_replace('arabian', urlencode($keyword), $url);
	}

	//report url
	  echo '<br>Pinterest url:<abbr title="'.$url.'">'.substr($url, 0,50).'...</abbr> ';
		
	curl_setopt($this->ch, CURLOPT_HTTPGET, 1);
	curl_setopt($this->ch, CURLOPT_URL, trim($url));
	curl_setopt($this->ch, CURLOPT_ENCODING , "");
	$exec=curl_exec($this->ch);
	$x=curl_error($this->ch);
	

 	//validating reply
	if(stristr($exec, 'request_identifier')){
		//valid reply

		//handle pins
		$arr = json_decode($exec);

	 
		
		//pins get
		if ( !in_array('OPT_PT_USER', $camp_opt) && $start ==1 ){
			// first basesearch with no user specified
			// $pins = $arr->resource_data_cache[0]->data->results;
			$pins = $arr->resource_response->data->results;
			
			$new_bookmark = $arr->resource->options->bookmarks[0];
			unset($pins[0]);
			
		}else{

			$pins = $arr->resource_response->data;
			$new_bookmark = $arr->resource->options->bookmarks[0];
			 
		}
			
		//echo '<pre>';
		//print_r($pins);
		//exit;
			
		//delete first item of specific boards search
		if(in_array('OPT_PT_USER', $camp_opt) && $cg_pt_user_channel != 'users' && count($pins) > 0){
			unset($pins[0]);
		}
			

		//if reversion order
		if(in_array('OPT_PT_REVERSE', $camp_opt)){
			  echo '<br>Reversing order';
			$pins = array_reverse($pins);
		}

		  echo '<ol>';

		
		  
		//loop pins
		$i = 0;
		foreach ($pins as $pin){

		 
			$itm['pin_url'] =  'https://'.$pin_host.'/pin/'.$pin->id;
			  echo '<li>'.$itm['pin_url'];

			 
			  
			$itm['pin_id'] = $pin->id;
			$itm['pin_domain'] = $pin->domain;
			$itm['pin_link'] = $pin->link;
			
			$itm['repin_count'] = $pin->repin_count;
			$pin_img = current( (Array)$pin->images );
			
			if(isset($pin->images->orig->url)) $pin_img = $pin->images->orig;
			 
		 
			$itm['pin_img'] = $pin_img->url ;
			$itm['pin_img_width'] = $pin_img->width;
			$itm['pin_img_height'] = $pin_img->height;
			$itm['pin_description'] = html_entity_decode($pin->description_html);
			$itm['pin_board_id'] = $pin->board->id;
			$itm['pin_board_url'] = 'https://'. $pin_host . $pin->board->url;
			$itm['pin_board_name'] = $pin->board->name;
			$itm['pin_pinner_username'] = $pin->pinner->username;
			$itm['pin_pinner_full_name'] = $pin->pinner->full_name;
			$itm['pin_pinner_id'] = $pin->pinner->id;
			$itm['pin_title'] = $pin->title;
			
			
			if( trim( $itm['pin_title'] ) == '' ){
				
				if( isset($pin->grid_title) && trim($pin->grid_title) != '' ){
					
					$itm['pin_title'] = $pin->grid_title;
					
				}elseif(  isset($pin->rich_summary->display_name) && trim($pin->rich_summary->display_name) != ''  ){

					$itm['pin_title'] = $pin->rich_summary->display_name;
				
				}
				
			}
			
			if(stristr($itm['pin_title'] , '&#')) $itm['pin_title'] = html_entity_decode($itm['pin_title'] , ENT_QUOTES);
			if(stristr($itm['pin_description'] , '&#')) $itm['pin_description'] =  htmlspecialchars_decode($itm['pin_description'], ENT_QUOTES);
			 	
			
			 
			$data = base64_encode(serialize ( $itm ));

			if( $this->is_execluded($camp->camp_id, $itm['pin_url']) ){
				  echo '<-- Execluded';
				continue;
			}
				
			if ( ! $this->is_duplicate($itm['pin_url']) )  {
				$query = "INSERT INTO {$this->wp_prefix}automatic_general ( item_id , item_status , item_data ,item_type) values (    '{$itm['pin_id']}', '0', '$data' ,'pt_{$camp->camp_id}_$keyword')  ";
				$this->db->query ( $query );
			} else {
				  echo ' <- duplicated <a href="'.get_edit_post_link($this->duplicate_id).'">#'.$this->duplicate_id.'</a>';
			}

			  echo '</li>';
			$i++;

		}

		  echo '</ol>';

		  echo '<br>Total '. $i .' pins found & cached';
		  
		  //bookmark not found error workaround: {"api_error_code":10,"message":"Bookmark not found."
		  $bookmark_name = 'bookmark_try_'.md5($keyword);
		  if(stristr($exec, 'api_error_code":10,')){
		  		
		  		$bookmark_trials = get_post_meta($camp->camp_id , $bookmark_name , 1);
		  		
		  		if(! is_numeric($bookmark_trials) ) $bookmark_trials = 0 ;
		  		
		  		echo '<br>Bookmark not found error found applying a workaround and sleeping instead of cleaning the bookmark';
		  		
		  		if($bookmark_trials > 2){
		  			echo '<br>Bookmark exceeded maximum number of allowed trials, deleting...';
		  			delete_post_meta($camp->camp_id, $bookmark_name);
		  		}else{
		  			$bookmark_trials++;
		  			echo '<br>Failed trials:' .$bookmark_trials;
		  			update_post_meta( $camp->camp_id , $bookmark_name , $bookmark_trials );
		  			return false;
		  		}
		  		
		  	
		  }else{
			  	delete_post_meta($camp->camp_id, $bookmark_name);
		  }

		//check if nothing found so deactivate
		if($i == 0 ){
			  echo '<br>No new pins found ';
			  echo '<br>Keyword have no more images deactivating...';
			$query = "update {$this->wp_prefix}automatic_keywords set keyword_start = -1 where keyword_id=$kid ";
			$this->db->query ( $query );
			
			if(! in_array('OPT_NO_DEACTIVATE', $camp_opt))
			$this->deactivate_key($camp->camp_id, $keyword);
				
			//delete bookmark value
			delete_post_meta($camp->camp_id, 'wp_pinterest_bookmark'.md5($keyword));
		}else{

			  echo '<br>Updating bookmark:'.$new_bookmark;
			update_post_meta($camp->camp_id, 'wp_pinterest_bookmark'.md5($keyword), $new_bookmark ) ;
		}

	}else{
			
		//no valid reply
		  echo '<br>No Valid reply for pins search from Pinterest<br>'.$exec;
			
	}


}

}