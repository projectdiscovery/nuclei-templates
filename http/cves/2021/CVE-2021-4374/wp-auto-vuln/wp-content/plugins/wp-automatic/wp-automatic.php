<?php
/*
 Plugin Name: Wordpress Automatic Plugin
 Plugin URI: http://codecanyon.net/item/wordpress-automatic-plugin/1904470?ref=ValvePress
 Description: WordPress Automatic posts quality articles, Amazon products, Clickbank products, Youtube videos, eBay items, Flicker images, RSS feeds posts on auto-pilot and much more.
 Version: 3.53.0
 Author: ValvePress
 Author URI: http://codecanyon.net/user/ValvePress/portfolio?ref=ValvePress
 */

/*  Copyright 2012-2021  Wordpress Automatic  (email : sweetheatmn@gmail.com) */

global $wpAutomaticTemp; //temp var used for displaying columns of campsigns 
global $wpAutomaticDemo;
$wpAutomaticDemo = false; 

if(  isset($_SERVER['HTTP_HOST']) &&   stristr($_SERVER['HTTP_HOST'], 'valvepress.com') ) $wpAutomaticDemo = true;
update_option( 'wp_automatic_license' , '4308eedb-1add-43a9-bbba-6f5d5aa6b8ee' );
update_option('wp_automatic_license_active','active');
update_option('wp_automatic_license_active_date',time());
update_option ( 'alb_license_active', '1' );
update_option ( 'alb_license_last', '01-01-2030');
update_option('wp_automatic_envato_token','4308eedb-1add-43a9-bbba-6f5d5aa6b8ee');

$licenseactive=get_option('wp_automatic_license_active','');
if(trim($licenseactive) != ''){
	
 
	//fire checks
	require_once  plugin_dir_path(__FILE__) . 'plugin-updates/plugin-update-checker.php';
	$wp_automatic_UpdateChecker = Puc_v4_Factory::buildUpdateChecker(
			'https://deandev.com/upgrades/meta/wp-automatic.json',
			__FILE__,
			'wp-automatic'
			);
	
	//append keys to the download url
	$wp_automatic_UpdateChecker->addResultFilter('wp_automatic_addResultFilter');
	function wp_automatic_addResultFilter($info){
		
		$wp_automatic_license = get_option('wp_automatic_license','');
		
		if(isset($info->download_url)){
			$info->download_url = $info->download_url . '&key='.$wp_automatic_license;
		}
		return $info;
	}
}

// amazon
require_once ( dirname(__FILE__) . '/inc/amazon_api_class.php');

/*  
 * Stylesheets & JS loading
 */
	require_once 'p_scripts.php';

/*
 * Creating a Custom Post Type
 */
	require_once 'post_type.php';

/*
 * Creating the admin menu
 */
	require_once 'p_menu.php';

/*
 * Settings
 */	
	require_once 'p_options.php';

/*
 * Log
 */	
	require_once 'p_log.php';
	

/*
 * Plugin functions
 */

	require_once 'p_functions.php';
	
	/*
	 * ajax handling
	 */
	require_once 'pajax.php';
	
/*
 * ads adding
 */
require_once 'p_ads.php';

/*
 * Meta Box
 */
require_once('p_meta.php');
require_once('metabox_time.php');

/*
 * Cron Schedule
 */
require_once 'automatic_schedule.php';

/*
 * clear feed cache 
 */

add_filter( 'wp_feed_cache_transient_lifetime', 'wp_automatic_feed_lifetime');

function wp_automatic_feed_lifetime( $a  ){
	 return 0 ; 
}

if(! function_exists('do_not_cache_feeds')){
	function do_not_cache_feeds(&$feed) {
		$feed->enable_cache(false);
	}
}
add_action( 'wp_feed_options', 'do_not_cache_feeds' );

/*
 * Filter the content to remove first image if active
 */
require_once 'p_content_filter.php';

/*
 * tables
 */
global $wpdb;
$mysqlVersion = $wpdb->db_version();

if( version_compare($mysqlVersion, '5.5.3') > 0 ){
	register_activation_hook( __FILE__, 'create_table_all' );
	require_once 'p_tables.php';
}

//removes quick edit from custom post type list

/**
 * custom request for cron job
 */
function wp_automatic_parse_request($wp) {

	//secret word 
	$wp_automatic_secret = trim(get_option('wp_automatic_cron_secret'));
	if(trim($wp_automatic_secret) == '') $wp_automatic_secret = 'cron';
	
	// only process requests with "my-plugin=ajax-handler"
	if (array_key_exists('wp_automatic', $wp->query_vars)) {
			
		if($wp->query_vars['wp_automatic'] == $wp_automatic_secret){
 
			require_once(dirname(__FILE__) . '/cron.php');
			exit;

		}elseif ($wp->query_vars['wp_automatic'] == 'download'){
			require_once 'downloader.php';
			exit;
		}elseif ($wp->query_vars['wp_automatic'] == 'test'){
			require_once 'test.php';
			exit;
		}elseif($wp->query_vars['wp_automatic'] == 'show_ip'){
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_HEADER,0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($ch, CURLOPT_TIMEOUT,20);
			curl_setopt($ch, CURLOPT_REFERER, 'http://www.bing.com/');
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8');
			curl_setopt($ch, CURLOPT_MAXREDIRS, 5); // Good leeway for redirections.
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // Many login forms redirect at least once.
			
			//curl get
			$x='error';
			$url='http://www.whatismyip.com/';
			
			curl_setopt($ch, CURLOPT_HTTPGET, 0);
			curl_setopt($ch, CURLOPT_URL, trim($url));
			
			$exec=curl_exec($ch);
			$x=curl_error($ch);
			
			if(strpos($exec,'<span>Your IP</span>')){
				preg_match('{<span>Your IP</span>:(.*?)<}', $exec , $ip_matches);
				print_r($ip_matches[1]);
			}else{
			
				echo $exec.$x;
			}
			exit;
		}
	}
}
add_action('parse_request', 'wp_automatic_parse_request');



function wp_automatic_query_vars($vars) {
	$vars[] = 'wp_automatic';
	return $vars;
}
add_filter('query_vars', 'wp_automatic_query_vars');

/*
 * support widget
 */

if( ! $wpAutomaticDemo)
require_once 'widget.php';


/*
 * rating notice
 */
require_once 'p_rating.php';
require_once 'p_license.php';

/*
 * update notice
 */
require_once 'updated.php';

/*
 * admin edit
 */
require_once 'wp-automatic-admin-edit.php';

/*
 *amazon product price update
 */
require_once 'wp-automatic-amazon-prices.php';

//sorting function by length
function wp_automatic_sort($a,$b){
	return strlen($b)-strlen($a);
}

//stripslashes with array support
function wp_automatic_stripslashes($toStrip){
	if(is_array($toStrip)){
		
		return array_map('wp_automatic_stripslashes',$toStrip);
		
	}else{
		return stripslashes($toStrip);
	}
}

//rotate and get a single item
function wp_automatic_single_item($option_name){
	
	$option_value = get_option($option_name);
	
	//empty value
	if(trim($option_value) == '') return $option_value;
	
	$multiple_items = array_filter( explode("\n",  $option_value ) );
	
	 
	//single value
	if(count($multiple_items) == 1) return trim($multiple_items[0]);
	
	//multiple items, return first and send it to the last 
	if(count($multiple_items) > 0 ){
		
		$first_item = $multiple_items[0];
		
		unset($multiple_items[0]);
		
		$multiple_items[] = $first_item;
		
		 update_option($option_name , implode("\n" ,$multiple_items ));
		
		 return trim($first_item);
		
		
	}
	
	return trim($option_value);
	
}

//fix relative link global
function wp_automatic_fix_relative_link ($found_link,$host,$http_prefix,$the_path,$base_url = ''){
	
	if (! stristr ( $found_link, 'http' )) {
		
		if (stristr ( $found_link, '//' )) {
			$found_link = $http_prefix . $found_link;
		} else {
			
			
			if( preg_match ( '{^/}' ,  $found_link ) ){
				
				$found_link = $host  . $found_link;
				
			}else{
				
				if(trim($base_url) != ''){
					$found_link = $base_url   . $found_link;
				}else{
					
					if(trim ($the_path) != ''){
						$found_link = $host . $the_path .  '/' . $found_link;
					}else{
						$found_link = $host . '/' . $found_link;
					}
					
				}
			}
			
			
			
		}
	}
	
	return $found_link;
	
}

//ebay backward compatiblility ebay site number to region
function wp_automatic_fix_category($category){
	
	if (is_numeric($category) && $category > 0){
		
		switch ($category) {
			case 1:
			return 'EBAY-US';
			break;

			case 2:
				return 'EBAY-IE';
				break;
				
			case 3:
				return 'EBAY-AT';
				break;
				
			case 4:
				return 'EBAY-AU';
				break;
				
				
			case 5:
				return 'EBAY-FRBE';
				break;
				
				
			case 7:
				return 'EBAY-ENCA';
				break;
				
			case 10:
				return 'EBAY-FR';
				break;
				
			case 11:
				return 'EBAY-DE';
				break;
				
			case 12:
				return 'EBAY-IT';
				break;
				
			case 13:
				return 'EBAY-ES';
				break;
				
			case 14:
				return 'EBAY-CH';
				break;
				
			case 15:
				return 'EBAY-GB';
				break;
				
			case 16:
				return 'EBAY-NL';
				break;
				 
			default:
				return 'EBAY-US';
			break;
		}
		
	}else{
		return $category;
	}
	
}

//convert extracted part from json to regular text 
function wp_automatic_fix_json_part($json_part){

	$json_string = '["' . $json_part . '"]';

	 $json = json_decode($json_string) ;
	 return $json[0];
	
}


function remove_taxonomies_metaboxes() {
	remove_meta_box( 'td_post_theme_settings_metabox', 'wp_automatic', 'normal' );
}
add_action( 'add_meta_boxes_wp_automatic' , 'remove_taxonomies_metaboxes' ,50);
?>