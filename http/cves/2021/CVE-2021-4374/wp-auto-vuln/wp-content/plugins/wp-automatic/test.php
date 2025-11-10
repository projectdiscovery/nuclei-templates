<?php
 
 
if( ! function_exists('get_option') ) exit;

global $wpdb;

//current table version
$wp_automatic_version = get_option('wp_automatic_version' , 199 );

echo '<br>Current table version:'.$wp_automatic_version. ' the latest is 202';

//table version 201
if($wp_automatic_version < 201 ){
	
	//remove all status != 0 from camp_general as status is no more used
	$querys = "DELETE FROM `{$prefix}automatic_general` WHERE `item_status` = 1;
ALTER TABLE `{$prefix}automatic_general` CHANGE `item_type` `item_type` VARCHAR(250) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `{$prefix}automatic_general` ADD INDEX(`item_type`);
DELETE FROM `{$prefix}automatic_amazon_links` WHERE `link_status` = 1;
ALTER TABLE `{$prefix}automatic_amazon_links` ADD INDEX(`link_keyword`);
DELETE FROM `{$prefix}automatic_articles_links` WHERE `status` = 1;
ALTER TABLE `{$prefix}automatic_articles_links` ADD INDEX(`keyword`);
DELETE FROM `{$prefix}automatic_clickbank_links` WHERE `link_status` = 1;
ALTER TABLE `{$prefix}automatic_clickbank_links` ADD INDEX(`link_keyword`);
DELETE FROM `{$prefix}automatic_youtube_links` WHERE `link_status` = 1;
ALTER TABLE `{$prefix}automatic_youtube_links` ADD INDEX(`link_keyword`);" ;
	
	//executing quiries
	$que=explode(';',$querys);
	
	foreach($que  as $query){
		if(trim($query)!=''){
			$resval = $wpdb->query($query);
		}
	}
	
	update_option('wp_automatic_version' , 201 ); //table version 200
	echo '<br>Update 1 of 2 implemented successfully';
	
}//version 201

//table version 202 change original_title tag to md5 of original link for duplication check
if($wp_automatic_version < 202 ){
	
	$last_meta = get_option( 'wp_automatic_last_meta' , '0' );
	
	$query="SELECT * FROM {$prefix}postmeta where meta_key= 'original_link' and meta_id > $last_meta order by meta_id ASC limit 1000";
	$rows=$wpdb->get_results($query);
	
	
	if(count($rows) == 0){
		echo '<br>Congratulation! Tables were updated successfully';
		update_option('wp_automatic_version' , 202 ); //table version 202
	}
	
	 
	foreach ($rows as $meta_row){
		 
		$md5 = md5($meta_row->meta_value);
		$update_query = "update {$prefix}postmeta set meta_key = '$md5' where meta_key = 'original_title' and post_id={$meta_row->post_id} ";
		$r = $wpdb->query($update_query);
		
	}
	
	$last_meta = $meta_row->meta_id;
	update_option('wp_automatic_last_meta' , $last_meta);
	echo '<br>Updated 1000 record Last meta ID is :'.$last_meta.' updating another batch....';
	
	echo '<script type="text/javascript"> window.location.reload(); </script>';
	

}else{
	echo '<br>Your database tables are already up to date!';
}
   