<?php

// check curl , conflict with wp pinner
function wp_automatic_update_notice() {
	
	// installed version
	$plugin_data = get_plugin_data ( str_replace ( 'updated.php', 'wp-automatic.php', __FILE__ ) );
	$version = $plugin_data ['Version'];
	
	// current version
	$version_current = wp_automatic_latest_version ();
	
	$version_diff = version_compare ( $version_current, $version );
	
	if ($version_diff > 0 && trim($version) != '' ) {
		?>
<div class="error">
	<p><?php    echo 'Please install the latest version ('.trim($version_current).') of <a href="http://codecanyon.net/item/wordpress-automatic-plugin/1904470?ref=DeanDev" title="Download Now »">Wordpress Automatic Plugin</a> which helps you stay up-to-date with the most stable, secure version the plugin. <a href="http://codecanyon.net/downloads?ref=DeanDev">Download Now »</a>'; ?></p>
</div>
<?php
	}
	
	//mysql version 
	global $wpdb;
	$mysqlVersion = $wpdb->db_version();
	
	if( version_compare($mysqlVersion, '5.5.3') < 0 ){
		?>
<div class="error">
	<p><?php    echo 'Please update your MySQL version to something higher than (5.5.3) so you can use WordPress Automatic Plugin. Your current version ('.$mysqlVersion.') is out of date. once you update it, deactivate and activate the plugin again. '; ?></p>
</div>
<?php
	}
	
	
	//table version
	$wp_automatic_version = get_option('wp_automatic_version' , 199 );
	
	if( $wp_automatic_version < 202 ){
		
		$update_url = home_url('?wp_automatic=test');
		
		?>
	
		<div class="error">
			<p><?php    echo '<a href="https://codecanyon.net/item/wordpress-automatic-plugin/1904470">WordPress Automatic plugin</a> tables update required. Please visit the update URL <a target="_blank" href="'.$update_url.'">HERE</a>, it will keep refreshing, leave it till it tells you congratulation!'; ?></p>
		</div>
	
	<?php 
	}
	
}

// function to get latest version return version
function wp_automatic_latest_version() {
	$latest_version = get_option ( 'wp_automatic_version_last', '' );
	
	if (trim ( $latest_version ) == '') {
		return wp_automatic_update_version ();
	} else {
		// now a registred version is available let's send it if before 24h or update if more than that .
		$last_update = get_option ( 'wp_automatic_version_updated', '1379233804' );
		$diff = wp_automatic_get_time_difference ( $last_update, time ( ) );
		
		if ($diff < 7200) {
			return $latest_version;
		} else {
			return wp_automatic_update_version ();
		}
		
		return $latest_version;
	}
} // function latest version
  
// function to update latest version from deandev
function wp_automatic_update_version() {
	if (function_exists ( 'curl_init' )) {
		
		// curl ini
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_HEADER, 0 );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 10 );
		curl_setopt ( $ch, CURLOPT_TIMEOUT, 20 );
		curl_setopt ( $ch, CURLOPT_REFERER, 'http://www.bing.com/' );
		curl_setopt ( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8' );
		curl_setopt ( $ch, CURLOPT_MAXREDIRS, 5 ); // Good leeway for redirections.
		                                           
		// curl get
		
		update_option ( 'wp_automatic_version_updated', time () );
		
		$x = 'error';
		$url = 'https://deandev.com/versions/wp-automatic.txt';
		curl_setopt ( $ch, CURLOPT_HTTPGET, 1 );
		curl_setopt ( $ch, CURLOPT_URL, trim ( $url ) );
		
		$exec = curl_exec ( $ch );
		$x = curl_error ( $ch );
		
		$version_current = $exec;
		
		if (stristr ( $exec, '.' )) {
			
			update_option ( 'wp_automatic_version_last', $version_current );
			
			return $exec;
		} else {
			
			update_option ( 'wp_automatic_version_last', '1.0.0' );
			
			return '1.0.0';
		}
	}
} // end function updated version
function wp_automatic_get_time_difference($start, $end) {
	$uts ['start'] = $start;
	$uts ['end'] = $end;
	
	if ($uts ['start'] !== - 1 && $uts ['end'] !== - 1) {
		if ($uts ['end'] >= $uts ['start']) {
			$diff = $uts ['end'] - $uts ['start'];
			
			return round ( $diff / 60, 0 );
		}
	}
} // end function

add_action ( 'admin_notices', 'wp_automatic_update_notice' );