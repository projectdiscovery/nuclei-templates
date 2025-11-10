<?php

// Main Class
require_once 'core.php';

Class WpAutomaticCareerjet extends wp_automatic{
	
	
	
	function careerjet_fetch_items($keyword, $camp) {
		
		echo "<br>So, I should now get some items from Careerjet for keyword:".$keyword ;
		
		// ini options
		$camp_opt = unserialize ( $camp->camp_options );
		if( stristr($camp->camp_general, 'a:') ) $camp->camp_general=base64_encode($camp->camp_general);
		$camp_general = unserialize ( base64_decode( $camp->camp_general ) );
		$camp_general=array_map('wp_automatic_stripslashes', $camp_general);
		
		// items url
		 
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
			
			if( ! in_array( 'OPT_CJ_CACHE' , $camp_opt )){
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

		//keyword
		$searchKeyword = urlencode(trim($keyword));
		
		//affiliate ID
		$wp_automatic_cj_id = trim(get_option('wp_automatic_cj_id',''));
		
		if(trim($wp_automatic_cj_id) == ''){
			echo '<br><span style="color:red">Please visit the plugin settings page and add your <b>Careerjet affiliate ID</b>.</span>';
			return false;
		}
		
		$cg_cj_page = "http://public.api.careerjet.net/search?keywords=$searchKeyword&affid=$wp_automatic_cj_id&user_ip=::1&user_agent=Mozilla%2F5.0+%28Macintosh%3B+Intel+Mac+OS+X+10_13_2%29+AppleWebKit%2F537.36+%28KHTML%2C+like+Gecko%29+Chrome%2F64.0.3282.119+Safari%2F537.36";

		//locale_code
		$cg_cj_locale = $camp_general['cg_cj_locale'];
		$cg_cj_page.= '&locale_code=' . $cg_cj_locale;
		
		//sort
		$cg_cj_sort = $camp_general['cg_cj_sort'];
		$cg_cj_page.= '&sort=' . $cg_cj_sort;
		
		//location
		$cg_cj_location = trim($camp_general['cg_cj_location']);
		if( $cg_cj_location != '') $cg_cj_page .= '&location='.urlencode($cg_cj_location);
		
		//contracttype
		$cg_cj_contracttype = $camp_general['cg_cj_contracttype'];
		if(trim($cg_cj_contracttype) != 'all') $cg_cj_page .= '&contracttype='.$cg_cj_contracttype;
		
		//contractperiod
		$cg_cj_contractperiod = $camp_general['cg_cj_contractperiod'];
		if(trim($cg_cj_contractperiod) != 'all') $cg_cj_page .= '&contractperiod='.$cg_cj_contractperiod;
		
		//page
		if($start == 0) $start=1;
		$cg_cj_page = $cg_cj_page . '&page='.$start;
		
		echo '<br>Careerjet items url:'.$cg_cj_page;
		echo ' index:' . $start;
		
		// update start index to start+1
		$nextstart = $start + 1;
		
		$query = "update {$this->wp_prefix}automatic_keywords set keyword_start = $nextstart where keyword_id=$kid ";
		$this->db->query ( $query );
		
		// get items
		// curl get
		$x = 'error';
		$url = $cg_cj_page;
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
		if( ! stristr($exec, '{"')){
			echo '<br>Not expected response from Careerjet';
		}
		
		if(stristr($exec, 'solveLocations')){
			echo '<br><br><span style="color:red">Please change the location to a correct one from the listed locations below:</span>';
			$locJson = json_decode($exec);
			$locations = $locJson->solveLocations;
			
			foreach ($locations as $loc){
				echo '<br>'.$loc->name;
			}
			return false;
		}
		
		// decode json
		$jsonReply = json_decode($exec);
		
		 
		$allItms = array();
		if( isset($jsonReply->jobs) ){
			$allItms = $jsonReply->jobs;
		}
		
		
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
			
			$id = uniqid();
			
			$item = array();
			
			// match title
			$item['item_title'] = $itemTxt->title;
			
			// match description
			$item['item_description'] = $itemTxt->description;
			
			// match link
			$item_link = $item['item_url'] = $itemTxt->url;
			
			// match date
			$item['item_date'] = $itemTxt->date;
			$item['item_locations'] = $itemTxt->locations;
			$item['item_site'] = $itemTxt->site;
			$item['item_company'] = $itemTxt->company;
			$item['item_salary'] = $itemTxt->salary;
			
			$item['item_salary_min'] = isset($itemTxt->salary_min) ? $itemTxt->salary_min : '';
			$item['item_salary_max'] = isset($itemTxt->salary_max) ? $itemTxt->salary_max : '' ;
			$item['item_salary_type'] = isset( $itemTxt->salary_type) ? $itemTxt->salary_type : '' ;

			$item['item_salary_currency_code'] =  isset($itemTxt->salary_currency_code) ? $itemTxt->salary_currency_code : '' ;
			
			 
			$data = ( base64_encode( serialize ( $item ) ) );
			
			 
			echo '<li> Link:'.$item_link;
			
			//salary check
			if(in_array('OPT_CJ_SALARY', $camp_opt)){
				if(trim($itemTxt->salary) == ''){
					echo '<-- No salary skipping.';
					continue;
				}
			}
			
			//excluded link check
			if( $this->is_execluded($camp->camp_id, $item_link) ){
				echo '<-- Execluded';
				continue;
			}
			
			//duplicate link check
			if ( ! $this->is_duplicate($item_link) )  {
				$query = "INSERT INTO {$this->wp_prefix}automatic_general ( item_id , item_status , item_data ,item_type) values (  '$id', '0', '$data' ,'cj_{$camp->camp_id}_$keyword')  ";
				$this->db->query ( $query );
			} else {
				echo ' <- duplicated <a href="'.get_edit_post_link($this->duplicate_id).'">#'.$this->duplicate_id.'</a>';
			}
			
		}
		
		echo '</ol>';
		
	}
	
	
	/*
	 * ---* careerjet post ---
	 */
	function careerjet_get_post($camp) {
		
		// Campaign options
		$camp_opt = unserialize (  $camp->camp_options );
		$keywords = explode ( ',', $camp->camp_keywords );
		foreach ( $keywords as $keyword ) {
			
			$keyword = trim($keyword);
			
			//update last keyword
			update_post_meta($camp->camp_id, 'last_keyword', trim($keyword));
			
			if (trim ( $keyword ) != '') {
				
				// getting links from the db for that keyword
				$query = "select * from {$this->wp_prefix}automatic_general where item_type=  'cj_{$camp->camp_id}_$keyword' ";
				$this->used_keyword=$keyword;
				$res = $this->db->get_results ( $query );
				
				// when no links lets get new links
				if (count ( $res ) == 0) {
					
					//clean any old cache for this keyword
					$query_delete = "delete from {$this->wp_prefix}automatic_general where item_type='cj_{$camp->camp_id}_$keyword' ";
					$this->db->query ( $query_delete );
					
					// get new fresh items
					$this->careerjet_fetch_items ( $keyword, $camp );
					
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
						
						echo '<br>careerjet item ('. $t_data ['item_title'] .') found cached but duplicated <a href="'.get_permalink($this->duplicate_id).'">#'.$this->duplicate_id.'</a>'  ;
						
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
					
					$temp['item_source_site'] = '';
					if(in_array('OPT_CJ_FULL', $camp_opt)){
						$item_url = $temp['item_url'];
						
						echo '<br>Loading full page to find a description & source: '.$item_url;
						
						//curl get
						$x='error';
						curl_setopt($this->ch, CURLOPT_HTTPGET, 1);
						curl_setopt($this->ch, CURLOPT_URL, trim($item_url));
						$exec=$this->curl_exec_follow($this->ch);
						$x=curl_error($this->ch);
						
					
						
							preg_match('{<section class="content">(.*?)<p class="source}s' , $exec , $des_matches);
							 
							if( isset($des_matches[1]) && trim($des_matches[1]) != ''){
								echo '<-- Found full description';
								$temp['item_description'] = trim($des_matches[1]);
							
							}elseif(trim($exec) == ''){
								echo '<-- Did not get anything from CJ, maybe proxies are needed ' . $x;
							}else{
								echo '<-- Can not find class content, using summary instead got '. $exec.$x;
							}
						
							//source 
	 						preg_match( '{<p class="source">(.*?)</p>}s' , $exec , $source_matchs );
						
	 						if( isset($source_matchs[1]) && trim($source_matchs[1]) != ''){
	 							$temp['item_source_site'] = trim($source_matchs[1]);
	 						}
	 						 
						
					}
					
					
					// update the link status to 1
					$query = "delete from {$this->wp_prefix}automatic_general where id={$ret->id}";
					$this->db->query ( $query );
					
					// if cache not active let's delete the cached videos and reset indexes
					if (! in_array ( 'OPT_CJ_CACHE', $camp_opt )) {
						echo '<br>Cache disabled clearing cache ...';
						$query = "delete from {$this->wp_prefix}automatic_general where item_type='cj_{$camp->camp_id}_$keyword' ";
						$this->db->query ( $query );
						
						// reset index
						$query = "update {$this->wp_prefix}automatic_keywords set keyword_start =1 where keyword_camp={$camp->camp_id}";
						$this->db->query ( $query );
					}
					 
					return $temp;
				} else {
					
					echo '<br>No links found for this keyword';
				}
			} // if trim
		} // foreach keyword
	}
	
}