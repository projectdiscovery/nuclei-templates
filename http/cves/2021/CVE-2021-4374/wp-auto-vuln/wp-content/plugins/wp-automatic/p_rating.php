<?php
function wp_automatic_rating_notice() {
	
	// curl check
	if (! function_exists ( 'curl_exec' )) {
		  echo '<div class="error"><p><a href="http://curl.haxx.se/">cURL</a> is not installed or disabled. you must install it for Wordpress Automatic Plugin to function. </p></div>';
	}else{
		// version 7.69.1 causes easy handle not used
		$cu_version = curl_version ()['version'];
		if( $cu_version == '7.69.1'){
			echo '<div class="error"><p>Current cURL version <b>' . $cu_version . '</b> is known to have a bug that prevents WP Automatic Plugin from working correctly, Please either upgrade or downgrade cURL</p></div>'; 
		}
	}
	
	// if the user ftped the plugin without running the table modification via activation hook
	$tableversion = get_option ( 'wp_automatic_version' );
	
	if ($tableversion < 17) {
		  echo '<div class="error"><p>Wordpress automatic plugin must be deactivated and activated again for correct upgrade</p></div>';
	}
	
	// rating message
	$uri = $_SERVER ['HTTP_HOST'] . $_SERVER ['REQUEST_URI'];
	
	if (stristr ( $uri, '?' )) {
		$uri .= '&wp_automatic_rating=cancel';
	} else {
		$uri .= '?wp_automatic_rating=cancel';
	}
	
	 
	if (! stristr ( $uri, 'http' )) {
		$uri = '//' . $uri;
	}
	
	if (isset ( $_GET ['wp_automatic_rating'] )) {
		update_option ( 'wp_deandev_automatic_rating', 'cancel' );
	}
	
	$wp_automatic_rating = get_option ( 'wp_deandev_automatic_rating', '' );
	
	if (trim ( $wp_automatic_rating ) == '') {
		
		// get count of successful posts
		global $wpdb;
		$query = "SELECT count(*) as count FROM {$wpdb->prefix}automatic_log where action like 'Posted:%' and date > '2014-01-12 11:05:27' ";
		$rows = $wpdb->get_results ( $query );
		$row = $rows [0];
		$count = $row->count;
		
		if ($count > 10) {
			  echo '<div class="updated"><p>Do you mind helping (<a href="https://deandev.com/">ValvePress</a>) by rating  "Wordpress automatic plugin"? Your high rating will <strong>help us improve</strong> the plugin <a style="text-decoration: underline;" href="http://codecanyon.net/downloads">Rate Now »</a> <a  style="float:right"  href="' . $uri . '">(x) </a></p></div>';
		} // count ok
	} // rating yes
}
add_action ( 'admin_notices', 'wp_automatic_rating_notice' );
?>