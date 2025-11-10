<?php


add_filter ( 'cron_schedules', 'wp_automatic_once_a_minute' );
function wp_automatic_once_a_minute($schedules) {
	
	// Adds once weekly to the existing schedules.
	$schedules ['once_a_minute'] = array (
			'interval' => 60,
			'display' => __ ( 'once a minute' ) 
	);
	return $schedules;
}

if (! wp_next_scheduled ( 'wp_automatic_hook' )) {
	wp_schedule_event ( time (), 'once_a_minute', 'wp_automatic_hook' );
}

add_action ( 'wp_automatic_hook', 'wp_automatic_function' );
function wp_automatic_function() {
	
  
	$opt = get_option ( 'wp_automatic_options', array ('OPT_CRON') );
	if (in_array ( 'OPT_CRON', $opt )) {
		
		// Camapign processor 
		require_once dirname(__FILE__) . '/campaignsProcessor.php';
		$CampaignProcessor = new CampaignProcessor() ;
		
		
		// Trigger Processing
		$CampaignProcessor->process_campaigns(false);
 	
	} else {
		
	}

}