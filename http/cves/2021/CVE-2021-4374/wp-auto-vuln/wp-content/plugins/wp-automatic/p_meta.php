<?php
 
//add novalidate to the form 
add_action('post_edit_form_tag', 'wp_automatic_post_edit_form_tag');
function wp_automatic_post_edit_form_tag() {
	global $post_type;

	if($post_type == 'wp_automatic'){
		  echo ' novalidate="novalidate" ';
	}
	
	
}

/* Add a new meta box to the admin menu. */
	add_action( 'admin_menu', 'wp_automatic_create_meta_box' );
	
	function wp_automatic_create_meta_box() {
		add_meta_box( 'status-meta-boxes', 'Run & Status', 'wp_automatic_status_meta_boxes', 'wp_automatic', 'normal', 'high' );
		add_meta_box( 'page-meta-boxes', 'Campaign options', 'wp_automatic_meta_boxes', 'wp_automatic', 'normal', 'high' );
		add_meta_box( 'temp-meta-boxes', 'Post template', 'wp_automatic_temp_meta_boxes', 'wp_automatic', 'normal', 'low' );
		add_meta_box( 'basic-meta-boxes', 'Post type, format & status', 'wp_automatic_basic_meta_boxes', 'wp_automatic', 'normal', 'low' );
		add_meta_box( 'imgs-meta-boxes', 'Images', 'wp_automatic_imgs_meta_boxes', 'wp_automatic', 'normal', 'low' );
		add_meta_box( 'posted-meta-box', 'Posted posts', 'wp_automatic_posted_meta_boxe', 'wp_automatic', 'side', 'low' );
		add_meta_box( 'actions-meta-box', 'Actions', 'wp_automatic_actions_meta_boxe', 'wp_automatic', 'side', 'low' );
		add_meta_box( 'categories-meta-boxes', 'Categories & Tags', 'wp_automatic_cats_meta_boxes', 'wp_automatic', 'normal', 'low' );
		add_meta_box( 'filters-meta-boxes', 'Posts filters', 'wp_automatic_filters_meta_boxes', 'wp_automatic', 'normal', 'low' );
		add_meta_box( 'tbs-meta-boxes', 'Rewriting, Translation & Multi-language', 'wp_automatic_tbs_meta_boxes', 'wp_automatic', 'normal', 'low' );
		add_meta_box( 'mods-meta-boxes', 'Content search/replace & modifications', 'wp_automatic_mods_meta_boxes', 'wp_automatic', 'normal', 'low' );
		add_meta_box( 'links-meta-boxes', 'Links', 'wp_automatic_links_meta_boxes', 'wp_automatic', 'normal', 'low' );
		add_meta_box( 'mil-meta-boxes', 'Miscellaneous', 'wp_automatic_mil_meta_boxes', 'wp_automatic', 'normal', 'low' );
		add_meta_box( 'links-meta-boxes', 'Links', 'wp_automatic_links_meta_boxes', 'wp_automatic', 'normal', 'low' );
		
		
	}
	
/* Saves the meta box data. */
	add_action( 'save_post', 'wp_automatic_save_meta_data' );

function wp_automatic_posted_meta_boxe(){
	require_once('posted_metabox.php');
}

function wp_automatic_actions_meta_boxe(){
	require_once('actions_metabox.php');
}

function wp_automatic_meta_boxes() {
	require_once('p_metabox.php');
}

function wp_automatic_status_meta_boxes(){
	require_once 'status_metabox.php';
} 
 
function wp_automatic_cats_meta_boxes(){
	require_once 'metabox_categories.php';
}

function wp_automatic_filters_meta_boxes(){
	require_once 'metabox_filters.php';
}

function wp_automatic_tbs_meta_boxes(){
	require_once 'metabox_tbs.php';
}

function wp_automatic_basic_meta_boxes(){
	require_once 'metabox_basic.php';
}


function wp_automatic_imgs_meta_boxes(){
	require_once 'metabox_imgs.php';
}

function wp_automatic_mods_meta_boxes(){
	require_once 'metabox_mods.php';
}

function wp_automatic_mil_meta_boxes(){
	require_once 'metabox_mil.php';
}

function wp_automatic_links_meta_boxes(){
	require_once 'metabox_links.php';
}

function wp_automatic_temp_meta_boxes(){
	require_once 'metabox_temp.php';
}


function wp_automatic_save_meta_data( $post_id ) {
 
// db instance
global $wpdb;

// db prefix
$prefix=$wpdb->prefix ;

// post type
$post_type = get_post_type( $post_id );

// if not wp_automatic skip saving
if($post_type != 'wp_automatic') return false;

// Campaign fields
if( ! array_key_exists('camp_post_every',$_POST)) return false;

// INI Vars
$camp_post_every = $_POST['camp_post_every'];
$camp_keywords=addslashes($_POST['camp_keywords']);
@$camp_cb_category=addslashes($_POST['camp_cb_category']);
@$camp_replace_link=addslashes($_POST['camp_replace_link']);
@$camp_add_star=addslashes($_POST['camp_add_star']);
@$camp_post_title=addslashes($_POST['camp_post_title']);
@$camp_post_content=addslashes($_POST['camp_post_content']);

@$camp_post_category=($_POST['camp_post_category']);


$camp_post_status = $_POST['camp_post_status'] ;
@$post_title=addslashes($_POST['post_title']);
@$camp_amazon_cat=addslashes($_POST['camp_amazon_cat']);
@$camp_amazon_region=addslashes($_POST['camp_amazon_region']);
@$camp_options=serialize($_POST['camp_options']);
@$feeds=addslashes($_POST['feeds']);
@$camp_type=$_POST['camp_type'];
@$camp_search_order=$_POST['camp_search_order'];
@$camp_youtube_cat=$_POST['camp_youtube_cat'];
@$camp_youtube_order=$_POST['camp_youtube_order'];
@$camp_post_author=$_POST['camp_post_author'];
@$camp_post_type=$_POST['camp_post_type'];
@$camp_post_exact=$_POST['camp_post_exact'];
@$camp_post_execlude=$_POST['camp_post_execlude'];
@$camp_yt_user=$_POST['camp_yt_user'];
@$camp_translate_from=$_POST['camp_translate_from'];
@$camp_translate_to=$_POST['camp_translate_to'];
@$camp_translate_to_2=$_POST['camp_translate_to_2'];
@$camp_post_custom_k=serialize($_POST['camp_post_custom_k']);
$camp_post_custom_v = array();
	
 
// multiple cats
if(is_array($camp_post_category)){
	$camp_post_category = implode(',', $camp_post_category);
}


// custom fields
if(isset($_POST['camp_post_custom_v'])){
	foreach ($_POST['camp_post_custom_v'] as $ckey){
		
		$camp_post_custom_v[] = stripslashes($ckey);
	}
	
	@$camp_post_custom_v = addslashes( serialize($camp_post_custom_v));

}

// If not a valid save ignore 
if ( (trim($camp_keywords) == '' && !( $camp_type =='Feeds' ||  $camp_type =='Spintax' ||  $camp_type =='Facebook' || $camp_type =='Craigslist' || $camp_type =='Reddit' || $camp_type =='Single' || $camp_type =='Multi') )  || ($camp_type == 'Feeds' && trim($feeds) == '')  ) return false;


// adding keywords to the table

// reading keywords that need to be processed
$rawKeywords = $camp_keywords;
if(! stristr($rawKeywords, ',')){
	 
	$newLinesCount = substr_count($rawKeywords, "\n");
		
	if($newLinesCount > 0 ){
		$keywords = explode("\n", $rawKeywords);
			
		$rawKeywords = implode(',', $keywords);
		
	}
		
}

$keywords=explode(',',$rawKeywords);

$keywords = array_filter($keywords);
  
if(count($keywords) > 0){

	//loping keywords
	foreach($keywords as $keyword){
		
		$keyword = trim($keyword);
		
		if( trim($keyword) != ''){
			
			$query="INSERT IGNORE  INTO {$prefix}automatic_keywords(keyword_name,keyword_camp) values('$keyword','$post_id')";
			@$wpdb->query($query);
			
			$query="INSERT IGNORE INTO {$prefix}automatic_articles_keys(keyword,camp_id) values('$keyword','$post_id')";
			@$wpdb->query($query);
		}
		
	}

}

//deleting current record for campaign
if(is_numeric($post_id) && $post_id >0){
	$query="delete from {$prefix}automatic_camps where camp_id = '$post_id'";
	$wpdb->query($query);
}

//building camp_general array
$camp_general=array(); 
foreach($_POST as $key=>$value){
	
	if(stristr($key,'cg')){
		$camp_general[$key]= $value ;
	}
	
}


//$camp_general['cg_eb_full_img_t'] = htmlentities($camp_general['cg_eb_full_img_t']);
$camp_general = base64_encode(serialize($camp_general));


// inserting new record for the campaign
$query="INSERT INTO  {$prefix}automatic_camps  (camp_general, camp_post_custom_k, camp_post_custom_v, camp_translate_from, camp_translate_to , camp_translate_to_2 , camp_yt_user,camp_post_exact,camp_post_execlude,camp_post_type,camp_post_author,camp_youtube_cat,camp_youtube_order,camp_search_order,camp_amazon_cat, camp_amazon_region ,camp_type, camp_id,camp_post_every,camp_options ,camp_keywords,camp_cb_category,camp_replace_link,camp_add_star,camp_post_title,camp_post_content,camp_post_category,camp_post_status,camp_name ,feeds)VALUES ( '$camp_general', '$camp_post_custom_k','$camp_post_custom_v', '$camp_translate_from', '$camp_translate_to', '$camp_translate_to_2', '$camp_yt_user',  '$camp_post_exact','$camp_post_execlude', '$camp_post_type', '$camp_post_author', '$camp_youtube_cat','$camp_youtube_order','$camp_search_order','$camp_amazon_cat','$camp_amazon_region','$camp_type','$post_id','$camp_post_every','$camp_options','$camp_keywords','$camp_cb_category','$camp_replace_link','$camp_add_star','$camp_post_title','$camp_post_content','$camp_post_category','$camp_post_status','$post_title','$feeds')";

$qRet = $wpdb->query($query);
 
//removed @v3.24 adding new record foreach new feed

/*
$feeds=$_POST['feeds'];
$feeds=explode("\n",$feeds);


foreach($feeds as $feed){
 
 	if( trim($feed) != ''){
			//appending 
			$feed=trim($feed);
			$feed=addslashes($feed);
			
			
			$query="INSERT IGNORE INTO {$prefix}automatic_feeds_list(camp_id,feed) VALUES('$post_id','$feed')";
			$wpdb->query($query);
			
		 
				

	}
}
 
 */

}//end function

//metabox order
add_filter( 'get_user_option_meta-box-order_wp_automatic', 'wp_automatic_metabox_order' );

function wp_automatic_metabox_order( $order ) {
	
	 
	
	return array(
			'side' =>  'wp_automatic_page-meta-boxes3,submitdiv,posted-meta-box,actions-meta-box',
			'normal' => 'status-meta-boxes,page-meta-boxes,slugdiv,temp-meta-boxes,basic-meta-boxes,imgs-meta-boxes,categories-meta-boxes,filters-meta-boxes,tbs-meta-boxes,mods-meta-boxes,links-meta-boxes,mil-meta-boxes,woothemes-settings'
					
					);
	 
}

//remove custom metas
add_action('add_meta_boxes_wp_automatic', 'wp_automatic_remove_bad_meta');

function wp_automatic_remove_bad_meta($args1){
	//print_r($args1);
	//exit;
}
?>
