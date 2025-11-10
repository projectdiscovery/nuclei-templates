<?php
add_action( 'wp_ajax_wp_automatic_reactivate_key', 'wp_automatic_reactivate_key_callback' );

function wp_automatic_reactivate_key_callback() {
 
	if(! isset($_POST['id'])  || ! isset($_POST['key'])){
		  echo 'Not valid request';
		die();
	}
	
	if(! is_numeric($_POST['id'])  || !  current_user_can('administrator')  ){
		  echo 'Not valid request';
		die();
	}
	
		$pid = $_POST['id'];
		$key = $_POST['key'];
		
		//deleting field 
		delete_post_meta($pid, $key);
		
		  echo 'Keyword Reactivated successfully. You can run the campaign again';
		
 die();
}

add_action( 'wp_ajax_wp_automatic_ajax', 'wp_automatic_ajax_callback' );

function wp_automatic_ajax_callback() {

	if(! isset($_POST['id'])  || ! isset($_POST['action'])){
		  echo 'Not valid request';
		die();
	}
	
	if(! is_numeric($_POST['id'])  || !  current_user_can('administrator')  ){
		  echo 'Not valid request';
		die();
	}

	$id = $_POST['id'];
	$action = $_POST['action'];
	$function = $_POST['function'];
	$data = $_POST['data'];
	
	 
	if( $function == 'forget_lastFirstFeedUrl'){
		delete_post_meta($id,$data.'_isItemsEndReached');
		
		  echo 'This fact was forgetten. You can run the campaign now';
		
	}
 

	die();
}

add_action( 'wp_ajax_wp_automatic_bulk', 'wp_automatic_bulk_callback' );

function wp_automatic_bulk_callback(){
	
	
	if(! isset($_POST['id'])  || ! isset($_POST['action'])  ){
		  echo 'Not valid request';
		die();
	}
	
	if(! is_numeric($_POST['id'])  || !  current_user_can('administrator')  ){
		  echo 'Not valid request';
		die();
	}
	
	$id = $_POST['id'];
	$key = $_POST['key'];
	 
	
	
	if( $key == 'deleteAll'){
		
		global $wpdb;
		$query="SELECT post_id FROM $wpdb->postmeta where $wpdb->postmeta.meta_key='wp_automatic_camp' and $wpdb->postmeta.meta_value=$id";
		$rows=$wpdb->get_results($query);
		
		$i=0;
		
		foreach ($rows as $row){
			
			$pid = $row->post_id;
			$ret = wp_delete_post($pid , true	);
			$i++;
			
		}
		
		delete_post_meta($id, 'wp_automatic_duplicate_cache');
		
		  echo $i.' posts deleted';
			
	}elseif( $key == 'forgetExcluded' ){
		delete_post_meta($id,'_execluded_links');
		
		  echo 'Excluded links forgotten.';
		
	}elseif( $key == 'forgetPosted' ){
		
		global $wpdb;
		
		$query="delete from {$wpdb->prefix}automatic_links where link_keyword=$id";
		$wpdb->query($query);
		
		delete_post_meta($id, 'wp_automatic_duplicate_cache');
		
		  echo 'Posts urls forgotten, This feature is only helpfull if you have activated the option to never post same url again as it deletes the urls from its memory.';
	
	}elseif( $key == 'reactivateAll' ){
		
		global $wpdb;
		
		
		$query = "SELECT * FROM {$wpdb->prefix}automatic_camps  where camp_id =$id";
		$camps = $wpdb->get_results ( $query );
		
		if(isset($camps[0])){
			$keywords = explode(',' , $camps[0]->camp_keywords);
		}
		
		foreach($keywords as $keyword){
			delete_post_meta( $id ,  '_' . md5(trim($keyword))  );
		}

		echo 'Reactivated';
	
	}
	
	die();
}


add_action( 'wp_ajax_wp_automatic_yt_playlists', 'wp_automatic_yt_playlists_callback' );

function wp_automatic_yt_playlists_callback() {
 
	//return ini
	$ret= array();
	$ret['status'] = 'error';
	$ret['message'] = '';
	$ret['data'] = '';
	
	//user channerl
	$user = trim($_POST['user']);
	
	//if empty user
	if(trim($user) == ''){
		$ret['message'] = 'empty user';
		print_r(json_encode($ret));
		die();
	}
	
	
	$start=1;
	$playlists=array();
	$playlist = array();
	$firstPlaylist['id'] = '';
	$firstPlaylist['title'] = '--CHOOSE A LIST--';

	$playlists[] = $firstPlaylist;
	
	for($i = 0;$i<5;$i++){ 
	
		//get user playlists feed page like: https://gdata.youtube.com/feeds/api/users/NAHBTV/playlists
		$wp_automatic_yt_tocken = wp_automatic_single_item('wp_automatic_yt_tocken');
		
		
		//$url="https://www.googleapis.com/youtube/v3/search?part=snippet&type=playlist&key=".trim($wp_automatic_yt_tocken)."&maxResults=50&channelId=".trim($user);
		$url="https://www.googleapis.com/youtube/v3/playlists?part=snippet&key=".trim($wp_automatic_yt_tocken)."&maxResults=50&channelId=".trim($user);

 		
		//page token
		if(isset($json_result->nextPageToken)){
			$url.= '&pageToken='.$json_result->nextPageToken;
		}
		
		//curl ini
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER,0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_TIMEOUT,20);
		curl_setopt($ch, CURLOPT_REFERER, 'http://www.bing.com/');
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.116 Safari/537.36');
		curl_setopt($ch, CURLOPT_MAXREDIRS, 5); // Good leeway for redirections.
		@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // Many login forms redirect at least once.
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		
		//curl get
		$x='error';
	 	curl_setopt($ch, CURLOPT_HTTPGET, 1);
		curl_setopt($ch, CURLOPT_URL, trim($url));
	 	$exec=curl_exec($ch);
		$x=curl_error($ch);
	 
		//if no response back
		if(trim($exec) == ''){
			$ret['message'] = 'Empty response from YT '.$x;
			print_r(json_encode($ret));
			die();
		}
		
		//extracting

		$json_result = json_decode($exec);
		
		 
		
		$items = $json_result->items;	
		
	 
		 
		$singlePlayCount = 0;
		foreach ($items as $entry){
		
			$playlist_id = $entry->id;
			$playlist['id'] = $playlist_id;
			$playlist['title'] =$entry->snippet->title ;
		
			$playlists[] = $playlist;
			
			$singlePlayCount++;
		
		}
		
		 
		
		if( $singlePlayCount < 50 ){
			 
			break;
		}  
		
		$start = $start +50;
	}
	
	
	$ret['status'] = 'success';
	$ret['data'] = $playlists;
	
	
	
	//save list 
	update_post_meta($_POST['pid'], 'wp_automatic_yt_playlists', $playlists);
 	
	print_r(json_encode($ret));
	
	 
	die();
	
	
	
	
	
	
 die();
}

// DailyMotion Playlists 
add_action( 'wp_ajax_wp_automatic_dm_playlists', 'wp_automatic_dm_playlists_callback' );

function wp_automatic_dm_playlists_callback() {

	//return ini
	$ret= array();
	$ret['status'] = 'error';
	$ret['message'] = '';
	$ret['data'] = '';

	//user channel
	$user = trim($_POST['user']);

	//if empty user
	if(trim($user) == ''){
		$ret['message'] = 'empty user';
		print_r(json_encode($ret));
		die();
	}


	$start=1;
	$playlists=array();
	$playlist = array();
	$firstPlaylist['id'] = '';
	$firstPlaylist['title'] = '--CHOOSE A LIST--';

	$playlists[] = $firstPlaylist;

	  
		//https://api.dailymotion.com/playlists?owner=Dakar&limit=100
		$url="https://api.dailymotion.com/playlists?limit=100&owner=".trim($user);

 
		//curl ini
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER,0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_TIMEOUT,20);
		curl_setopt($ch, CURLOPT_REFERER, 'http://www.bing.com/');
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8');
		curl_setopt($ch, CURLOPT_MAXREDIRS, 5); // Good leeway for redirections.
		@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // Many login forms redirect at least once.

		//curl get
		$x='error';
		curl_setopt($ch, CURLOPT_HTTPGET, 1);
		curl_setopt($ch, CURLOPT_URL, trim($url));
		$exec=curl_exec($ch);
		$x=curl_error($ch);

		//if no response back
		if(trim($exec) == ''){
			$ret['message'] = 'Empty response from YT '.$x;
			print_r(json_encode($ret));
			die();
		}

		//extracting

		$json_result = json_decode($exec);

		

		$items = $json_result->list;


			
		 
		foreach ($items as $entry){

			$playlist_id = $entry->id;
			$playlist['id'] = $playlist_id;
			$playlist['title'] =$entry->name ;

			$playlists[] = $playlist;
				 
		}


	$ret['status'] = 'success';
	$ret['data'] = $playlists;


	//save list
	update_post_meta($_POST['pid'], 'wp_automatic_dm_playlists', $playlists);

	print_r(json_encode($ret));

	die();

}



add_action( 'wp_ajax_wp_automatic_more_posted_posts', 'more_posted_posts_callback' );

function more_posted_posts_callback() {
 
	//global 
	global $wpdb;
	$prefix=$wpdb->prefix;
	
	//from data
	$camp = $_POST['camp'];
	$page = $_POST['page'];
	
	if(! is_numeric($_POST['camp'])  || !  current_user_can('administrator')  ){
		  echo 'Not valid request';
		die();
	}
	
	
	
	//get rows
	$query="SELECT * FROM {$prefix}automatic_log where action='Posted:$camp' order by id DESC limit $page , 10";
	$rows=$wpdb->get_results($query);
	
	foreach ($rows as $row){
		  echo '<div class="posted_itm">'. str_replace('New post posted:','',$row->data) .'<br>on <small>'.$row->date .'</small><br></div>';
	} 
	
	
 die();
}

add_action( 'wp_ajax_wp_automatic_campaign_duplicate', 'wp_automatic_campaign_duplicate_callback' );

function wp_automatic_campaign_duplicate_callback() {
 
	//getting camp id
	$href=$_POST['href'];
	$title = $_POST['campName'];
	
	preg_match('{post=(.*?)&}', $href,$matches);

	$camp_id = $matches[1];
	
	if(trim($camp_id) != '' && is_numeric($camp_id)){

		//insert post 
		$post['post_title'] = $title;
		$post['post_type'] = 'wp_automatic';
		$post['post_status'] = 'draft';
		
		$new_postID = wp_insert_post($post);
		
		if(! is_numeric($new_postID)){
			  echo 'Failed to create a new post';
			exit;
		}
		 
		//le't duplicate the record 
		global  $wpdb;
		$prefix = $wpdb->prefix;
		
		$wpdb->query("CREATE TEMPORARY TABLE tmptable SELECT * FROM {$prefix}automatic_camps WHERE camp_id = $camp_id;");
		$wpdb->query("UPDATE tmptable SET camp_id = $new_postID ");
		$wpdb->query("INSERT INTO {$prefix}automatic_camps SELECT * FROM tmptable WHERE camp_id = $new_postID;");
		
		  echo 'Campaign duplicated with a draft status, reload the page to edit';
		
	}else{
		  echo 'Invalid cmap id';
	}
	
	
 die();
}

add_action( 'wp_ajax_wp_automatic_iframe', 'wp_automatic_iframe_callback' );
function wp_automatic_iframe_callback() {

	 
		//auth check	
		if(!current_user_can('administrator')) die();
	
		// Detect the URL
		$_GET['url'] = $_GET['address'] ;
		$url = null;
	
		//detect cookie
		$cookie = isset($_GET['theCookie']) ? $_GET['theCookie'] : '' ;

		 
		$url = $_GET['url'];
	 	
	 	// about:blank if passed no URL
		if ( !$url ) {
				header('Location: about:blank');
				exit();
		}
		
		//if feed, get a URL
		if(isset($_GET['sourse']) && $_GET['sourse'] == 'Feeds'){
			
			$url_pts = explode("\n" , $url);
			 
			$rss =fetch_feed(trim($url_pts[0]));
			
			if (! is_wp_error ( $rss )){
				$maxitems = $rss->get_item_quantity ();
				$rss_items = $rss->get_items ( rand(0,$maxitems - 1 ), 1);
			}else{
				echo '<br>Error parsing the feed';
			 
				die();
			}
			
			$url = esc_url ( $rss_items[0]->get_permalink () );
			 
			
		}
		
		
		
		
		// Request the URL. Return 404 always if failed
		//curl ini
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER,0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_TIMEOUT,20);
		curl_setopt($ch, CURLOPT_REFERER, 'http://www.bing.com/');
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.116 Safari/537.36');
		curl_setopt($ch, CURLOPT_MAXREDIRS, 5); // Good leeway for redirections.
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // Many login forms redirect at least once.
 		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
 		
 		if(isset($_GET['clean_encoding']))
 		curl_setopt($ch, CURLOPT_ENCODING , "");
		
		// set the cookie
		//if(trim($cookie) != '')   
		//curl_setopt($ch,CURLOPT_HTTPHEADER,'Cookie: '.trim($cookie));

		$headers[] = "Cookie: $cookie ";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		
		//curl get
		$x='error';
		curl_setopt($ch, CURLOPT_HTTPGET, 1);
		curl_setopt($ch, CURLOPT_URL, html_entity_decode(trim($url)));
		$content=curl_exec($ch);
		$x=curl_error($ch);
		
		if(trim($x) != ''){
			echo 'Problem loading URL: '. $x;
		}
		 
		if (  trim($content) == '' ) {
			header('404 Not Found');
			exit();
		}
		
	
		
		// Supply base element to resolve relative path
		if ( !preg_match('/<base\s/i', $content) ) {
			$base = '<base href="' . $url . '">';
			$content = preg_replace('{(<head.*?>)}',  "$1$base" , $content);
		}
	
		
		//fix href="//
		$content = str_replace('src="//', 'src="https://', $content);
		$content = str_replace('href="//', 'href="https://', $content);
		
		//fix this form <link href="App_Themes/Site_Blue/bootstrap.css"
		//$content = preg_replace( '{href="([a-g]|[i-z])}is' , "href=\"/$1" , $content);
		//removed as conflicted with https://www.ionos.fr/digitalguide/hebergement/blogs/
		
	
		// Supply protocol and domain before absolute path turns 'href="/' to 'href="http:domain.com/'
		if ( preg_match('!^https?://[^/]+!', $url, $matches) ) {
			$stem = $matches[0];
			$content = preg_replace('!(\s)(src|href)(=")/!i', "\\1\\2\\3$stem/", $content);
			$content = preg_replace('!(\s)(url)(\s*\(\s*["\']?)/!i', "\\1\\2\\3$stem/", $content);
		}
		 
		//strip scripts $res['cont'] = preg_replace('{<script.*?script>}s', '', $res['cont']);
		$beforeJS = $content;
		$content = preg_replace('{<script.*?</script>}s', '', $content);
		
		if(trim($content) == '') $content = $beforeJS; //sometimes replace returns NULL ticket #7848
		
		
		echo $content."<style>
  body {
    font-family: sans-serif;
  }
  .highlight {
    box-shadow:inset 0 0 0 1000px rgba(255, 0, 0, 0.5) !important;
    outline: 1px solid red !important;
  }
</style>";
 die();
}