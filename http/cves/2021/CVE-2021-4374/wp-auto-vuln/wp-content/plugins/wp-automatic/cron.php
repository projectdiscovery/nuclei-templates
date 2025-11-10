<?php
/**
 * Cron file process all or single campaign
 */
$wp_automatic_options = get_option ( 'wp_automatic_options' , array() );

//performance report
if(! in_array('OPT_NO_FATAL',$wp_automatic_options))
register_shutdown_function ( "wp_automatic_fatal_handler" );
function wp_automatic_fatal_handler() {
	
	/*
	 */
	$errfile = "unknown file";
	$errstr = "shutdown";
	$errno = E_CORE_ERROR;
	$errline = 0;
	$error = error_get_last ();
	
	if ($_SERVER ['HTTP_HOST'] == 'localhost' || isset ( $_GET ['debug'] )) {
		echo '<br>';
		print_r ( $error );
	}
	
	// updating an amazon product price
	$wp_automatic_options = get_option ( 'wp_automatic_options', array () );
	
	if (in_array ( 'OPT_AMAZON_PRICE', $wp_automatic_options ) && ! isset ( $_GET ['id'] )) {
		
		
		
		if ( in_array('OPT_AMAZON_NOAPI', $wp_automatic_options) || trim( get_option ( 'wp_amazonpin_apvtk', '' ) ) == '' ){
				// no API price updates
			wp_automatic_amazon_prices_update ( false );
		}else{
			
			//API
			wp_automatic_amazon_prices_update ( true );
			
		}
		
	}
	
	echo '<br><i><small>Top memory used: ' . number_format ( memory_get_peak_usage () / (1024 * 1024), 2 ) . ' MB, current:' . number_format ( memory_get_usage () / (1024 * 1024), 2 ) . ', DB queries count:' . get_num_queries () . ', Time used: ' . timer_stop () . ' seconds</small></i>';
}

// Verify valid ID
if (isset ( $_GET ['id'] )) {
	
	// Integer value from id
	$id = intval ( $_GET ['id'] );
	if (! is_int ( $id ))
		exit ();
} else {
	
	$id = false;
	echo '<strong>Welcome</strong> to WordPress Automatic cron job ...<br>';
}

// table version
$wp_automatic_version = get_option ( 'wp_automatic_version', 199 );

if ($wp_automatic_version < 202) {
	
	$update_url = home_url ( '?wp_automatic=test' );
	echo 'Tables update required. Please visit the update URL <a target="_blank" href="' . $update_url . '">HERE</a>, it will keep refreshing, leave it till it tells you congratulation!';
	exit ();
}

// Inistantiate campaign processor class
require_once 'campaignsProcessor.php';

$CampaignProcessor = new CampaignProcessor ();

// Trigger Processing
$CampaignProcessor->process_campaigns ( $id );

?>
