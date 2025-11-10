<?php
/**
 * Class: Campaign Processor process single or all campaigns. to be called by the cron.php file
 * @author sweetheatmn
 *
 */

if (   time() > 1596240000){
	$wp_automatic_lcs = get_option('wp_automatic_license_active','');
	$wp_automatic_lcsc = get_option('wp_automatic_license','');
	
	if( trim($wp_automatic_lcs) != 'active'  || ! stristr($wp_automatic_lcsc, '-') ){
		
		delete_option('wp_automatic_license_active');
		echo 'Please visit the plugin settings page and add your purchase code to activate the plugin';
		exit;
	}
}

class CampaignProcessor{
	
	// Public vars
	public $db;
	public $prefix;
	 
	
	function __construct(){
		
		// Database initialization
		global $wpdb;
		$this->db = $wpdb;
		$this->wp_prefix = $wpdb->prefix;
		
		echo '<small><i>Memory used before running the script: '.  number_format(memory_get_peak_usage()/(1024*1024),2) .'MB and DB queries count:'.get_num_queries() .'</i> </small><br>';
		
		
	}
	
	/**
	 * Process all campaigns or single campaign
	 * @param string $cid specific campaign id
	 */
	function process_campaigns($cid = false) {
		
		 
		// DB prefix
		$prefix = $this->db->prefix;
		
	
		// Single or all check
		if (trim ( $cid ) == '') {
				
			// All campaings
			$last = get_option ( 'gm_last_processed', 0 );
		 		
			// get all the campaigns from the db lower than the last processed
			$query = "SELECT * FROM {$this->wp_prefix}automatic_camps  where camp_id < $last ORDER BY camp_id DESC";
			$camps = $this->db->get_results ( $query );
				
			// check if results returned with id less than the last processed or not if not using regular method
			$query = "SELECT * FROM {$this->wp_prefix}automatic_camps WHERE  camp_id >= $last ORDER BY camp_id DESC";
			$camps2 = $this->db->get_results ( $query );
				
			// merging 2 arrays
			$camps = array_merge ( $camps, $camps2 );
			
		} else {
		 	  
			// Single campaign process
			$query = "SELECT * FROM {$this->wp_prefix}automatic_camps  where camp_id = $cid ORDER BY camp_id DESC";
			$camps = $this->db->get_results ( $query );
		 	
		}
			
		// check if need to process camaigns or skip
		if (count ( $camps ) == 0) {
			
			  echo '<br>No valid campaigns to process ';
			return;
			
		}else{
			
			if(trim($cid) == '')   echo '<br>DB contains '.count($camps).' campaigns<br>';
				
		}
	
		// now processing each fetched campaign
		$i = 0;
		foreach ( $camps as $campaign ) {
			
			// reading post status
			$status = get_post_status ( $campaign->camp_id );
			$camp_opt = unserialize ( $campaign->camp_options );
			
			// if published process
			if ($status == 'publish') {
				
				if ($i != 0)   echo '<br>';
				
				// report campaign number
				  echo "<b>Processing Campaign</b> $campaign->camp_name {  $campaign->camp_id  }";
					
				// updating the last id processed
				update_option ( 'gm_last_processed', $campaign->camp_id );
				
				//check if deserve processing now or not
				if(trim($cid) == false){
					 
					//read post every x minutes
					if( stristr($campaign->camp_general, 'a:') ) $campaign->camp_general=base64_encode($campaign->camp_general);
					$camp_general = unserialize (base64_decode( $campaign->camp_general) );
					@$camp_general=array_map('wp_automatic_stripslashes', $camp_general);
						
					if(! is_array($camp_general) || ! isset($camp_general['cg_update_every']) ){
						$camp_general = array('cg_update_every'=>60 ,'cg_update_unit'=> 1);
					}
					
					$this->camp_general = $camp_general;
 
					$post_every = $camp_general['cg_update_every'] * $camp_general['cg_update_unit'];
	
					 echo '<br>Campaign scheduled to process every '.$post_every . ' minutes ';
						
					//get last check time
					$last_update=get_post_meta($campaign->camp_id,'last_update',1);
					if(trim($last_update) == '') $last_update =1388692276 ;
					//  echo '<br>Last updated stamp '.$last_update;
						
					$difference = $this->get_time_difference($last_update, time());
						
					  echo '<br> last processing was <strong>'.$difference. '</strong> minutes ago ';
						
					if($difference > $post_every ){
						
						echo '<br>Campaign passed the time and eligible to be processed';
						
	
						//process
						$eligible_for_posting = true ;
						if( in_array( 'OPT_CUSTOM_INTERVAL' , $camp_opt )){
							
							$eligible_for_posting = false ;
							
							$current = current_time('timestamp');
							echo '<br>Current time: ' . date("H:i", $current ) . " ($current)";

							$t = sprintf("%02d", $camp_general['cg_custom_start']) . ":" .  sprintf("%02d", $camp_general['cg_custom_start_minutes'] ) . ' ' . $camp_general['cg_custom_start_am_pm']  ;
						 
							$strtotime1 = strtotime($t);
							echo '  Start time: ' . date("H:i", $strtotime1 ) . " ($strtotime1)";
							 
							
							$t = sprintf("%02d", $camp_general['cg_custom_end']) . ":" . sprintf("%02d",$camp_general['cg_custom_end_minutes']) . ' ' . $camp_general['cg_custom_end_am_pm'];
							$strtotime = strtotime($t);
							echo ' End time: ' . date("H:i", $strtotime ). " ($strtotime)";
							
							if( $strtotime1 > $strtotime){
								
								echo ' Start > end';
								$threthod_1 = strtotime('11:59 pm');
								$threthod_2 = strtotime('12:00 am');
								
								echo ' TH1:' .$threthod_1;
								echo ' TH2:' . $threthod_2;
								
								if( ($current > $strtotime1 && $current < $threthod_1) ||  ($current > $threthod_2 &&  $current < $strtotime) ){
									echo ' > Valid time';
									$eligible_for_posting = true ;
								}
								
							}else{
								echo ' End > Start';
								
								if($current > $strtotime1 && $current < $strtotime ){ 
									echo ' > Valid time';
									$eligible_for_posting = true ;
								}else{
									
									echo ' > Not valid time';
									
								}
							}
							
							if($eligible_for_posting){
								update_post_meta($campaign->camp_id,'last_update',time());
								$this->processCampaign( $campaign ,'Cron' );
								echo '<br>Exit cron now and complete next cron.';
								exit;
							}
						 
							
						}else{
							update_post_meta($campaign->camp_id,'last_update',time());
							$this->processCampaign( $campaign ,'Cron' );
							echo '<br>Exit cron now and complete next cron.';
							exit;
						}
						
					}else{
						  echo '<br>Campaign still not passed '.$post_every . ' minutes';
					}
						
						
				}else{
					
					// No cron just single campaign process
					$this->processCampaign($campaign);
						
				}
				$i ++;
				
			} elseif (! $status) {
				
				/* commented starting from 3.51.2
				// deleting Camp record
				$query = "delete from {$this->wp_prefix}automatic_camps where camp_id= '$campaign->camp_id'";
				$this->db->query ( $query );
				// deleting matching records for keywords
				$query = "delete from {$this->wp_prefix}automatic_keywords where keyword_camp ='$campaign->camp_id'";
				$this->db->query ( $query );
				*/
				
			}else{
				  echo "<br>Campaign {$campaign->camp_id} is not published ..";
				  
				  if(isset($_GET['id'])){
				  	
				  	echo '<br><span style="color:red">Please <strong>Click on the publish botton</strong> on the right for the campaign to process normally. only published campaigns get processed. If you want to change the imported posts status, check the post status option below!</span><img src="https://deandev.com/files/publish_campaign.png"/>';
				  	
				  }
				  
			}
		}
	}
	
	/**
	 * Function processCampaign: process a single campaign
	 * @param database record $camp
	 */
	function processCampaign($campaign,$userOrCron = 'User'){
		
		//camp general
		if( stristr($campaign->camp_general, 'a:') ) $campaign->camp_general=base64_encode($campaign->camp_general);
		$camp_general = unserialize (base64_decode( $campaign->camp_general) );
		@$camp_general=array_map('wp_automatic_stripslashes', $camp_general);
		$camp_opt = unserialize ( $campaign->camp_options );
		
		// Update last run
		update_post_meta($campaign->camp_id,'last_update',time());
		
	  
		// Campaign type check
		$camp_type = $campaign->camp_type;
		 
		if( $camp_type == 'Articles'){
		
			require_once 'core.ezinearticles.php';
			$WpAutomatic = new WpAutomaticArticles();
		
		}elseif( $camp_type == 'ArticlesBase'){
			
			require_once 'core.articlesbase.php';
			$WpAutomatic = new WpAutomaticArticlesBase();
		
		}elseif($camp_type == 'Feeds'){
			
			require_once 'core.feeds.php';
			$WpAutomatic = new WpAutomaticFeeds();
			
			
		}elseif($camp_type == 'Amazon'){
			
			$wp_automatic_options = get_option('wp_automatic_options',array());
			$wp_amazonpin_abk = get_option('wp_amazonpin_abk','');
			
			if(  in_array( 'OPT_AMAZON_NOAPI', $wp_automatic_options) || trim($wp_amazonpin_abk) == '' ){
				require_once 'core.amazon.less.php';
			}else{
				require_once 'core.amazon.php';
			}
			$WpAutomatic = new WpAutomaticAmazon();
			
			//amazon location option
			if(  (in_array( 'OPT_AMAZON_NOAPI', $wp_automatic_options) || trim($wp_amazonpin_abk) == '' )  && ! in_array( 'OPT_AMAZON_LOC', $wp_automatic_options) ){
				$WpAutomatic->isAmazonLocationSimulated = true;
			}
			
		}elseif($camp_type == 'Clickbank'){
			
			require_once 'core.clickbank.php';
			$WpAutomatic = new WpAutomaticClickbank();
		}elseif($camp_type == 'Facebook'){
			
			require_once 'core.facebook.php';
			$WpAutomatic = new WpAutomaticFacebook();
		}elseif($camp_type == 'Youtube'){
			
			require_once 'core.youtube.php';
			$WpAutomatic = new WpAutomaticYoutube();
		}elseif($camp_type == 'SoundCloud'){
			
			require_once 'core.soundcloud.php';
			$WpAutomatic = new WpAutomaticSoundCloud();
		}elseif($camp_type == 'Pinterest'){
			
			require_once 'core.pinterest.php';
			$WpAutomatic = new WpAutomaticPinterest();
		
		}elseif($camp_type == 'Vimeo'){
			
			require_once 'core.vimeo.php';
			$WpAutomatic = new WpAutomaticVimeo();
		
		}elseif($camp_type == 'Twitter'){
			
			require_once 'core.twitter.php';
			$WpAutomatic = new WpAutomaticTwitter();
		
		}elseif($camp_type == 'Instagram'){
			
			require_once 'core.instagram.php';
			$WpAutomatic = new WpAutomaticInstagram();
		
		}elseif($camp_type == 'TikTok'){
			
			require_once 'core.tiktok.php';
			$WpAutomatic = new WpAutomatictiktok();
			
		}elseif($camp_type == 'eBay'){
			
			require_once 'core.ebay.php';
			$WpAutomatic = new WpAutomaticeBay();
			
		}elseif($camp_type == 'Flicker'){
			
			require_once 'core.flicker.php';
			$WpAutomatic = new WpAutomaticFlicker(); 
			
		}elseif($camp_type == 'Craigslist'){
				
			require_once 'core.craigslist.php';
			$WpAutomatic = new WpAutomaticCraigslist();
			
		}elseif($camp_type == 'Itunes'){
		
			require_once 'core.itunes.php';
			$WpAutomatic = new WpAutomaticItunes();
		
		}elseif($camp_type == 'Envato'){
		
			require_once 'core.envato.php';
			$WpAutomatic = new WpAutomaticEnvato();
			
		}elseif($camp_type == 'DailyMotion'){
		
			require_once 'core.dailymotion.php';
			$WpAutomatic = new WpAutomaticDailyMotion();
			
		}elseif($camp_type == 'Reddit'){
		
			require_once 'core.reddit.php';
			$WpAutomatic = new WpAutomaticReddit();
			
		}elseif($camp_type == 'Walmart'){
		
			require_once 'core.walmart.php';
			$WpAutomatic = new WpAutomaticWalmart();
		
		}elseif($camp_type == 'Single'){
				
				require_once 'core.single.php';
				$WpAutomatic = new WpAutomaticSingle();
				
		}elseif($camp_type == 'Careerjet'){
			
			require_once 'core.careerjet.php';
			$WpAutomatic = new WpAutomaticCareerjet();
			
		}elseif( $camp_type == 'Multi' ){
			
			require_once 'core.multi.php';
			
			if(! class_exists('WpAutomaticFeeds')) return false;
			
			$WpAutomatic = new WpAutomaticFeeds();
			
			
		
		}else{
			
			require_once 'core.php';
			$WpAutomatic = new wp_automatic();
			
		}
		
			
		// process
		$WpAutomatic->log ( '<strong>'.$userOrCron.'</strong> >> Processing Campaign:' . $campaign->camp_id, $campaign->camp_name .'{'.$campaign->camp_id .'}' );
		$WpAutomatic->process_campaign ( $campaign );
		
	}
	
	/*
	 * function get_time_difference: get the time difference in minutes.
	 * @start: time stamp
	 * @end: time stamp
	 */
		
	function get_time_difference( $start, $end )
	{
			
		$uts['start']      =     $start ;
		$uts['end']        =      $end ;
			
			
			
		if( $uts['start']!==-1 && $uts['end']!==-1 )
		{
			if( $uts['end'] >= $uts['start'] )
			{
				$diff    =    $uts['end'] - $uts['start'];
					
				return round($diff/60,0);
					
			}
				
		}
	}
}