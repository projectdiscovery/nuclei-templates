<?php

// Main class to inherit wp_automatic
require_once 'core.php';

// Specific articles class
Class WpAutomaticArticlesBase extends wp_automatic{

/*
 * ---* article base get links for a keyword ---
 */
function article_base_getlinks($keyword, $camp) {
	
	$camp_opt = unserialize ( $camp->camp_options );
	
	// get associated page num from the keyword and camp id from wp_automatic_articles_keys
	$query = "select * from {$this->wp_prefix}automatic_articles_keys where keyword = '$keyword' and camp_id  = '$camp->camp_id'";
	$camp_key = $this->db->get_results ( $query );
	$camp_key = $camp_key [0];
		

	// Verify keyword exists
	if (count ( $camp_key ) == 0){
		  echo '<br>Keyword record not found';
		return false;
	}
		
	// Result start first= 1,51,101 .. etc
	$page = $camp_key->page_num;
	
	if (   $page == - 1) {

		//check if it is reactivated or still deactivated
		if($this->is_deactivated($camp->camp_id, $keyword)){
			$page = '1';
		}else{
			//still deactivated
			return false;
		}

	}
	
	// Start index from 1 not 0
	if($page == 0) $page=1;
	
	// Report start result
	  echo '<br>Trying to call AB for new links start from result:' . $page;
	
	// Keyword encoded
	$keywordenc = urlencode ( 'site:articlesbase.com '. trim($keyword)   );
	 	
	// Bing Link
	$curlurl="https://www.bing.com/search?q=$keywordenc&PC=U316&FORM=CHROMN&count=50&first=$page";

	// Report Search Url
	  echo '<br>Bing url:'.$curlurl;
	
	// Curl Get
	$x='error';
	curl_setopt($this->ch, CURLOPT_HTTPGET, 1);
	curl_setopt($this->ch, CURLOPT_URL, trim($curlurl));
	$exec=$this->curl_exec_follow($this->ch);
	$x=curl_error($this->ch);
	  
	
	   
	//no content and 302 header exists
	if(trim($exec) == ''){
		  echo '<br>Empty results from Bing page';
		return false;
	}
	
	/*
	if(stristr($exec, 'no results')   ){
		  echo '<br>Yandex No results found';
		$this->deactivate_key($camp->camp_id, $keyword);
		return false;
	}*/
		
	 
	
	// Articles links <li class="b_algo"><h2><a href="http://www.articlesbase.com/chocolate-articles/what-you-should-know-about-craving-sweets-705813.html"
	preg_match_all('{<h2><a href="(http://www.articlesbase.*?)".*?li>}s', $exec , $matchsArr);
	$foundUrls = $matchsArr[1];
	
	// Cache urls u="
	preg_match_all('{<li class="b_algo".*?<div class="b_attribution"\s?(?:u="(.*?)")?.*?li>}s', $exec , $cacheMatchsArr);
	$cacheLinksArr = $cacheMatchsArr[1];
	
	
	// No links? return if yes	
	if(count($foundUrls) == 0 ){
		  echo '<br> no matching results found for this keyword';
		$query = "update {$this->wp_prefix}automatic_articles_keys set page_num = '-1'  where keyword = '$keyword' and camp_id  = '$camp->camp_id'";
		$this->db->query ( $query );

		//deactivate for 60 minutes
		if(! in_array('OPT_NO_DEACTIVATE', $camp_opt))
		$this->deactivate_key($camp->camp_id, $keyword);
		
		return false;
	}
		
	// Report links count
	  echo '<br>Links got from AB:' . count ( $foundUrls );
	$this->log ( 'links found', count ( $foundUrls ) . ' New Links added from Articles base to post articles from' );
		
	
	  echo '<ol>';
	$i = 0;
	foreach ( $foundUrls as $link ) {

		// verify id in link
		  echo '<li>Link:'.($link);
		$link_url = $link;
			
		if (stristr ( $link, 'articlesbase.com' )  && preg_match('{\d+\.html}', $link) ) {
				
			// verify uniqueness
			if( $this->is_execluded($camp->camp_id, $link_url) ){
				  echo '<-- Execluded';
				continue;
			}
				

			if ( ! $this->is_duplicate($link_url) )  {
				
				$title = '';
				$cache = '';
				
				// cache link
				$cache = '';
				$cache = $cacheLinksArr[$i]; 
				 
				$query = "insert into {$this->wp_prefix}automatic_articles_links (link,keyword,page_num,title,bing_cache) values('$link' ,'$keyword','$page','$title','$cache')";
				$this->db->query ( $query );
				$freshlinks = 1;
			} else {
				  echo ' <- duplicated <a href="'.get_edit_post_link($this->duplicate_id).'">#'.$this->duplicate_id.'</a>';
			}
				
			  echo '</li>';

			// incrementing i
				
		}else{
			  echo '<- Not an article url,skipping';
		} // if contain id
		$i ++;
	} // foreach link

	  echo '</ol>';
	
	 
	// updating page num
	$page = $page + 50;
		
	//check if this page exists
	if(stristr($exec, 'first='.$page)){
		  echo '<br>Next Page exists starts at: '.$page;
	}else{

		//  echo $exec;

		  echo '<br>Last page reached resetting search index...';
		$page = -1;
		
		if(! in_array('OPT_NO_DEACTIVATE', $camp_opt))
		$this->deactivate_key($camp->camp_id, $keyword);

	}
	
	 
	
	$query = "update {$this->wp_prefix}automatic_articles_keys set page_num = $page  where keyword = '$keyword' and camp_id  = '$camp->camp_id' ";
	$this->db->query ( $query );
	 
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
			$query = "select * from {$this->wp_prefix}automatic_articles_links where keyword = '$keyword' and status =0 and link like '%articlesbase.com%'";
			$links = $this->db->get_results ( $query );
				
			// when no links available get some links
			if (count ( $links ) == 0) {

				$this->article_base_getlinks ( $keyword, $camp );
				// get links to fetch and post on the blogs
				$query = "select * from {$this->wp_prefix}automatic_articles_links where keyword = '$keyword' and status =0 and link like '%articlesbase.com%'";
				$links = $this->db->get_results ( $query );
			}
				
			// if no links then return
			if (count ( $links ) != 0) {

				foreach ( $links as $link ) {
						
					// updating status of the link to posted or 1
					$query = "update {$this->wp_prefix}automatic_articles_links set status = '1' where id = '$link->id'";
					$this->db->query ( $query );
						
					// processing page and getting content
					$url = htmlspecialchars_decode($link->link) ;

					$title = $link->title;

						
					  echo '<br>Processing Article :' . ($url);

					 
					$binglink =  "http://webcache.googleusercontent.com/search?q=cache:".urlencode($url);

					curl_setopt ( $this->ch, CURLOPT_HTTPGET, 1 );
					curl_setopt ( $this->ch, CURLOPT_URL, trim (  ( $binglink ) ) );
					curl_setopt ( $this->ch, CURLOPT_REFERER, 'http://ezinearticles.com' );
					$exec = curl_exec ( $this->ch );
					$x = curl_error($this->ch);
					 
 
					$cacheLoadSuccess = false; // success flag
					
					if(stristr($exec,'About the Author')){
						//valid google cache
						  echo '<br>Successfully loaded the page from Google Cache';
						$cacheLoadSuccess = true;
					}else{
						
						  echo '<br>No valid Googe cache found';
						
						// Bing cache
						$cacheLink ='';
						$cacheToken = $link->bing_cache;
						
						if(trim($cacheToken) != '' && stristr($cacheToken, '|')){
							
							$cacheTokenParms = explode( '|' , $cacheToken );
							
							 
							$cacheTokenParam1 = $cacheTokenParms[2];
							$cacheTokenParam2 = $cacheTokenParms[3];
							
							if(trim($cacheTokenParam1) != '' && trim($cacheTokenParam1) != ''){
								
								$cacheLink = "http://cc.bingj.com/cache.aspx?d=$cacheTokenParam1&mkt=en-US&setlang=en-US&w=$cacheTokenParam2";
								
							}
							
						}else{
							  echo '<br>No cache token found';
						}
						 
						
						if(trim($cacheLink) != ''){
							
							  echo '<br>Google cache failed Loading from Bing cache<br> Link:'.$cacheLink;
							
							curl_setopt ( $this->ch, CURLOPT_URL, trim (  $cacheLink  ) );
							$exec = curl_exec ( $this->ch );
							
							if(stristr($exec,'About the Author')){
								//valid google cache
								  echo '<br>Successfully loaded the page from Bing Cache';
								$cacheLoadSuccess = true;
							} 
							 	
						}
						
						 
						if(!$cacheLoadSuccess){
							
							
							// Direct call
							  echo '<br>Bing cache didnot return valid result direct call to ArticleBase '.$x;
							curl_setopt ( $this->ch, CURLOPT_URL, trim (  ( urldecode( $url ) ) ) );
							curl_setopt ( $this->ch, CURLOPT_REFERER, 'http://ezinearticles.com' );
							$exec = curl_exec ( $this->ch );
	
							if(stristr($exec, 'About the Author')){
								  echo '<br>Ezinearticles returned the article successfully ';
							}else{
								
								 
								$this->link_execlude( $camp->camp_id, $link->link );
								
								  echo '<br>No article found at this link';
								
								return false;
							}
							
						}
 	
					}
						
					// Extracting content
					preg_match('{<div class="dropcap text">(.*?)<input type="hidden}s', $exec , $ArtMatches);
					$articleContentRaw = $ArtMatches[1];
					$articleContentClean = $articleContentRaw;
					
					// Cleaning content 
					$articleContentClean = preg_replace("{<script.*?script>}s", '', $articleContentRaw);
					$articleContentClean = preg_replace("{<div.*?\s</div>}s", '', $articleContentClean);
					$cont = trim($articleContentClean);

					// get the title <title>Make Money With Google Profit Kits Exposed - Don't Get Ripped Off!</title>
					@preg_match_all ( "{<title>(.*?)</title>}", $exec, $matches, PREG_PATTERN_ORDER );
					@$res = $matches [1];
					@$ttl = $res [0];
						
					if (isset ( $ttl )) {
						$title = $ttl;
					}
					
					// Extract author 
					preg_match('{<a href="(http://www.articlesbase.com/authors/.*?)" title="(.*?)\'s}s', $exec , $authorMatches );
					$author_link = $authorMatches[1];
					$author_name = $authorMatches[2];
					
						
					$ret ['cont'] = $cont;
					$ret ['title'] = $title;
					$ret ['original_title'] = $title;
					$ret ['source_link'] = urldecode($url);
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