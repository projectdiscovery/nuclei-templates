<?php

// Main class to inherit wp_automatic
require_once 'core.php';

// Specific articles class
Class WpAutomaticArticles extends wp_automatic{

/*
 * ---* article base get links for a keyword ---
 */
function article_base_getlinks($keyword, $camp) {
	
	$camp_opt = unserialize ( $camp->camp_options );
	
	if (stristr ( $camp->camp_general, 'a:' ))
		$camp->camp_general = base64_encode ( $camp->camp_general );
		$camp_general = unserialize ( base64_decode ( $camp->camp_general ) );
		
	// get associated page num from the keyword and camp id from wp_automatic_articles_keys
	$query = "select * from {$this->wp_prefix}automatic_articles_keys where keyword = '$keyword' and camp_id  = '$camp->camp_id'";
	$camp_key = $this->db->get_results ( $query );

	if (count ( $camp_key ) == 0){
		echo '<br>Keyword record not found';
		return false;
	}
	
	$camp_key = $camp_key [0];
	$foundUrls = array();
	
	$page = $camp_key->page_num;
	if (   $page == - 1) {
		//check if it is reactivated or still deactivated
		if($this->is_deactivated($camp->camp_id, $keyword)){
			$page = '0000';
		}else{
			//still deactivated
			return false;
		}
	}
		
	//Make sure start is 0,1,2 for bing
	if( ! stristr($page, '1994') ){
		$page = 0;
	 
	}else{
		$page = str_replace('1994', '', $page);
		 
	}
	
	if($page >9 ) $page = 9;
	
	$startIndex = 1 + 10 * $page;
	
		echo '<br>Trying to call EA for new links start from page:' . $page;
		//$keywordenc = urlencode ( 'site:ezinearticles.com '. trim($keyword). ' inurl:"id"'   );
		//$keywordenc = urlencode ( trim($keyword). ' inurl:"id"'   );
		$keywordenc = urlencode ( trim($keyword)   );
		
		
		 
		echo '<br>Using Google custom search to find new articles...';
		
		 //verify Google custom search key existence
		$wp_automatic_search_key = get_option('wp_automatic_search_key','');
		
		if(trim($wp_automatic_search_key) == ''){
			echo '<br><span style="color:red" >Google custom search API key is required. Please visit the plugin settings page and add it inside EzineArticles settings box</span>';
			return false;
		}
  
		//Good we have some keys, verify usage limits
		
		//Now get one key out of many if applicable
		$wp_rankie_googlecustom_keys = explode(',', $wp_automatic_search_key);
		$wp_rankie_googlecustom_keys = array_filter($wp_rankie_googlecustom_keys);
		
 
		
		$now = time();
		
		$validWorkingKey = '';
		foreach ($wp_rankie_googlecustom_keys as $current_key){
			
			if(trim($current_key) != ''){
				
				//check if key is disabled or not
				$current_keyMd5 = md5($current_key);
				$disabledTill = get_option('wp_automatic_'.$current_keyMd5,'1463843434');
				
				if($disabledTill > $now){
					continue;
				}else{
					$validWorkingKey = $current_key;
					break;
				}
				
			}
			
		}
		
		if(trim($validWorkingKey) == ''){
			echo '<br><span style="color:red" >Custom search API keys reached its daily search requests limit, we will try again after one hour. each key gives us 100 daily search request.</b>';
			return false;
		}else{
			echo '<br>Using an added key:'.substr($validWorkingKey, 0,5).'.....';
		}
		
		$wp_rankie_googlecustom_id = '013156076200156289477:aavh3lmtysa';
		$wp_rankie_googlecustom_id = '013156076200156289477:e_o1j3uv0rs';
		
		$wp_rankie_ezmlm_gl = 'google.com';
		
		$url ="https://www.googleapis.com/customsearch/v1?key=" . urlencode(  trim($validWorkingKey) ) . "&cx=" . urlencode(  trim($wp_rankie_googlecustom_id) ) . "&q=".$keywordenc.'&googlehost='.urlencode($wp_rankie_ezmlm_gl).'&start='.$startIndex;
		
		//date limit 
		if(in_array('OPT_ARTICLES_DATE' , $camp_opt )){
			
			$cg_articles_date_last_val = $camp_general['cg_articles_date_last_val'];
			$cg_articles_date_last = $camp_general['cg_articles_date_last'];
			
			
			if(is_numeric($cg_articles_date_last_val)){
				
				if($cg_articles_date_last == 'Months'){
					
					$url.= "&dateRestrict=m{$cg_articles_date_last_val}";
					
				}else{
					 
					//years
					$url.= "&dateRestrict=y{$cg_articles_date_last_val}";
					
				}
				
			}
			
		}
		
		//echo '<br>Search URL:'.$url;
		
		 //curl get
		 $x='error';
		 curl_setopt($this->ch, CURLOPT_HTTPGET, 1);
		 curl_setopt($this->ch, CURLOPT_URL, trim($url));
		 $exec=curl_exec($this->ch);
		 $x=curl_error($this->ch);
	 	 
		 //validate a reply
		 if(trim($exec) == ''){
		 	echo '<br>Empty reply from Google search API with possible cURL error '.$x;
		 	return false;
		 }
		 
	 	//validate json
		 if( ! stristr($exec , '{') ){
		 	echo '<br>Not a json reply '.$exec;
		 	return false;
		 }
		 
		 
		 
		 // good let's get results
		 $jsonReply = json_decode($exec);

		 
		 if(isset($jsonReply->error)){
		 	
		 	$jsonErr = $jsonReply->error->errors[0];
		 	
		 	$errReason  = $jsonErr->reason;
		 	$errMessage = $jsonErr->message;
		 	
		 	$message = 'Api returned an error: '.$errReason.' '.$errMessage;
		 	echo '<br>'.$message;
		 	
		 	
		 	// disable limited keys
		 	if($errReason == 'dailyLimitExceeded'){
		 		update_option('wp_automatic_'.$current_keyMd5,$now + 60*60);
		 	}
		 	
		 	$return['message'] = $message;
		 	return $return;
		 	
		 }
		 
		 if(isset($jsonReply->items)){
		 	$foundLinks = $jsonReply->items;
		 }else{
		 	$foundLinks = array();
		 }
		 
		 foreach ($foundLinks as $foundLink){
		 	
		 		$finalUrl= $foundLink->link;
		 		$foundUrls[] = $finalUrl;
		 	
		 }
		 
	
	// No links? return if yes	
	if(count($foundUrls) == 0 ){
		echo '<br> no matching results found for this keyword';
		$query = "update {$this->wp_prefix}automatic_articles_keys set page_num = '-1'  where keyword = '$keyword' and camp_id  = '$camp->camp_id'";
		$this->db->query ( $query );

		//deactivate permanently
		$this->deactivate_key($camp->camp_id, $keyword,0);
		return false;
	}else{
		// good lets update next page
		$page++;
	
		if($page > 9){

			$query = "update {$this->wp_prefix}automatic_articles_keys set page_num = '-1'  where keyword = '$keyword' and camp_id  = '$camp->camp_id'";
			$this->db->query ( $query );
			//deactivate for 60 minutes
			$this->deactivate_key($camp->camp_id, $keyword ,0);
			
		}else{
			
			$page= "1994$page";
			$query = "update {$this->wp_prefix}automatic_articles_keys set page_num = $page  where keyword = '$keyword' and camp_id  = '$camp->camp_id' ";
			$this->db->query ( $query );
			
		}
		
	
	}
	
	
		
	// Report links count
	echo '<br>Articles links got from EA:' . count ( $foundUrls );
	$this->log ( 'links found', count ( $foundUrls ) . ' New Links added from ezine articles to post articles from' );
	
	
	echo '<ol>';
	$i = 0;
	foreach ( $foundUrls as $link ) {

		$link =  urldecode($link);
		
		// verify id in link
		echo '<li>Link:'.($link);
		$link_url = $link;
			
		if (stristr ( $link, 'id=' )) {
				
			// verify uniqueness
			if( $this->is_execluded($camp->camp_id, $link_url) ){
				  echo '<-- Execluded';
				continue;
			}

			if ( ! $this->is_duplicate($link_url) )  {
				
				$title = '';
				$cache = '';
				
				// cache link
				$urlEncoded = urlencode($link_url);
				$bingcache= '';
				
				$query = "insert into {$this->wp_prefix}automatic_articles_links (link,keyword,page_num,title,bing_cache) values('$link' ,'$keyword','$page','$title','$bingcache')";
				$this->db->query ( $query );
				
				
				$freshlinks = 1;
				
				
			} else {
				  echo ' <- duplicated <a href="'.get_edit_post_link($this->duplicate_id).'">#'.$this->duplicate_id.'</a>';
			}
				
			  echo '</li>';

			// incrementing i
				
		} // if contain id
		$i ++;
	} // foreach link

	  echo '</ol>';
		
	// updating page num
	$page = $page + 1;
	$pageDisplayed = $page + 1;
	

	return;
}
	
/*
 * ---* articlebase process camp ---
 */
function articlebase_get_post($camp) {
	
	$keywords = $camp->camp_keywords;
	$keywords = explode ( ",", $keywords );

	foreach ( $keywords as $keyword ) {
			
		$keyword = trim($keyword);
			
		if (trim ( $keyword ) != '') {
				
				
			//update last keyword
			update_post_meta($camp->camp_id, 'last_keyword', trim($keyword));

			// check if keyword exhausted to skip
			$query = "select * from {$this->wp_prefix}automatic_articles_keys where keyword = '$keyword' and camp_id='$camp->camp_id'";
			$key = $this->db->get_results ( $query );
			$key = $key [0];

				
			// process feed
			  echo '<br><b>Getting article for Keyword:</b>' . $keyword;
				
			// get links to fetch and post on the blogs
			$query = "select * from {$this->wp_prefix}automatic_articles_links where keyword = '$keyword' ";
			$links = $this->db->get_results ( $query );
				
			// when no links available get some links
			if (count ( $links ) == 0) {
				
				//clean any old cache for this keyword
				$query_delete = "delete from {$this->wp_prefix}automatic_articles_links where  keyword = '$keyword'  ";
				$this->db->query ( $query_delete );

				$this->article_base_getlinks ( $keyword, $camp );
				
				// get links to fetch and post on the blogs
				$links = $this->db->get_results ( $query );
			}
				
			// if no links then return
			if (count ( $links ) != 0) {

				foreach ( $links as $link ) {
					 
					// update the link status to 1
					$query = "delete from {$this->wp_prefix}automatic_articles_links where id={$link->id}";
					$this->db->query ( $query );
						
					// processing page and getting content
					$url = ($link->link) ;
					$title = $link->title;
					$cacheTxt = $link->bing_cache;

					echo '<br>Processing Article :' . urldecode($url);
					
					//duplicaate check
					if($this->is_duplicate($url)){
						echo ' <- duplicated <a href="'.get_edit_post_link($this->duplicate_id).'">#'.$this->duplicate_id.'</a>';
						continue;
					}

					$binglink =  "http://webcache.googleusercontent.com/search?q=cache:".urlencode($url);
					echo '<br>Cache link:'.$binglink;

					$headers = array();
					curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
					
					
					curl_setopt ( $this->ch, CURLOPT_HTTPGET, 1 );
					curl_setopt ( $this->ch, CURLOPT_URL, trim (  ( $binglink ) ) );
					curl_setopt ( $this->ch, CURLOPT_REFERER, 'http://ezinearticles.com' );
					$exec = curl_exec ( $this->ch );
					$x = curl_error($this->ch);
 
					
					// bing cache if google cache failed 
					if(  ! stristr($exec,'comments') && stristr($cacheTxt, '|')){
						echo '<br>Google cache did not return the correct content, trying Bing cache instead...';
						
						$cacheParts = explode('|', $cacheTxt);
						
						$cacheLink = "http://cc.bingj.com/cache.aspx?q=123458&d=".$cacheParts[2]."&mkt=en-XA&setlang=en-US&w=".$cacheParts[3];
						
						echo '<br>Cache link:'.$cacheLink;
						
						curl_setopt ( $this->ch, CURLOPT_URL, trim (  ( $cacheLink ) ) );
						curl_setopt ( $this->ch, CURLOPT_REFERER, 'http:/bing.com' );
						$exec = curl_exec ( $this->ch );
						$x = curl_error($this->ch);
						
						
					}
					
					$cacheLoadSuccess = false; // success flag
					
					if(stristr($exec,'comments')){
						//valid google cache
						  echo '<br>Successfully loaded the page from cache';
						$cacheLoadSuccess = true;
					}else{
						
						// Google translate
						 echo '<br>Google cache failed Loading using GtranslateProxy...';
						
						require_once 'inc/proxy.GoogleTranslate.php';
						
						try {
							
							$GoogleTranslateProxy = new GoogleTranslateProxy($this->ch);
							$exec = $GoogleTranslateProxy->fetch($url);
						
						} catch (Exception $e) {
						
							  echo '<br>ProxyViaGoogleException:'.$e->getMessage();
						
						}
						
						// Validate Google Translate Proxy						
						if(stristr($exec,'comments')){
							//valid google cache
							  echo '<br>Successfully loaded the page from GTranslateProxy';
							$cacheLoadSuccess = true;
							
							// pre Gtranslate adaption
							$exec = str_replace( 'article-content>','article-content">',$exec);
							$exec = str_replace('<div id=article-resource', '<div id="article-resource', $exec);
							$exec = str_replace('rel=author', 'rel="author', $exec);
							
						} 
						 	
						if(!$cacheLoadSuccess){
							
							// Direct call
							  echo '<br>Google cache didnot return valid result direct call to ezine '.$x;
							curl_setopt ( $this->ch, CURLOPT_URL, trim (  ( urldecode( $url ) ) ) );
							curl_setopt ( $this->ch, CURLOPT_REFERER, 'http://ezinearticles.com' );
							$exec = curl_exec ( $this->ch );
							 
	
							if(stristr($exec, 'comments')){
								  echo '<br>Ezinearticles returned the article successfully ';
							}else{
								if(stristr($exec,'excessive amount')){
									  echo '<br>Ezinearticles says there is excessive amount of traffic';
									return false ;
								}else{
									  echo '<br>Ezinearticles did not return the article we called... Will die now and try with a new article another time. ';
									return false ;
								}
							}
							
						}
 	
					}
						

						
					// extracting articles
					$arr = explode ( 'article-content">', $exec );
					$lastpart = $arr [1];
						
					unset ( $arr );
					$newarr = explode ( '<div id="article-resource', $lastpart );
					
						
					$cont =   $newarr [0];
					
					//remove last closing </div>
					$cont = preg_replace('{</div>$}s', '', trim($cont));

					//striping js
					$cont = preg_replace('{<script.*?script>}s', '', $cont);
					$cont = preg_replace('{<div class=["]?mobile-ad-container["]?>.*?</div>}s', '', $cont);

					// get the title <title>Make Money With Google Profit Kits Exposed - Don't Get Ripped Off!</title>
					@preg_match_all ( "{<title>(.*?)</title>}", $exec, $matches, PREG_PATTERN_ORDER );
					@$res = $matches [1];
					@$ttl = $res [0];
						
					if (isset ( $ttl )) {
						$title = $ttl;
					}
						
					// get author name and author link <a href="/?expert=Naina_Jain" rel="author" class="author-name" title="EzineArticles Expert Author Naina Jain"> Naina Jain </a>
					@preg_match_all ( '{<a href=(.*?) rel="author.*?>(.*?)</a>}', $exec, $matches, PREG_PATTERN_ORDER );
					
					$author_link =  $matches [1] [0];
					
					// remove "
					$author_link = str_replace('"', '', $author_link);
					
					// fix from translation url
					if (stristr($author_link, 'translate')){
						
						$authorParts = explode('u=', $author_link);
						$author_link = $authorParts[1] ;
						$author_link = preg_replace('{&.*}', '', $author_link );
						
						
					}
					
					// fix relative linking
					if( ! stristr( $author_link , 'ezinearticles' )){
						$author_link = 'http://ezinearticles.com' . $author_link;
					}
					
					$author_name = trim ( $matches [2] [0] );
						
					$ret ['cont'] = $cont;
					$ret ['title'] = $title;
					$ret ['original_title'] = $title;
					$ret ['source_link'] = ($url);
					$ret ['author_name'] = $author_name;
					$ret ['author_link'] = $author_link;
					$ret ['matched_content'] = $cont;
					$this->used_keyword=$link->keyword;
					if( trim($ret['cont']) == '' )   echo ' exec:'.$exec;
						
						
					return $ret;
				} // foreach link
			} // if count(links)
				
		} // if keyword not ''
	} // foreach keyword
}

}