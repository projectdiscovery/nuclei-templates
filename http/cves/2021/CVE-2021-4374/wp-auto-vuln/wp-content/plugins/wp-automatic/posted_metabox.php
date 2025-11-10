<?php 

global $post;
global  $wpdb;
$prefix=$wpdb->prefix;
$post_id=$post->ID;

$query="SELECT * FROM {$prefix}automatic_log where action='Posted:$post_id' order by id DESC limit 10";
$rows=$wpdb->get_results($query);

$count=count($rows);


  echo '<p>Latest posts.</p><div class="latest-posts-container">'; 


foreach ($rows as $row){
	
	$displayTitle = str_replace('New post posted:','',$row->data);
	
	if(stristr($displayTitle , '></a')   ){
		
		$displayTitle = str_replace('><', '>(no title)<', $displayTitle);
		
	} 
	
	  echo '<div class="posted_itm">'. $displayTitle .'<br>on <small>'. get_date_from_gmt($row->date) .'</small><br></div>';
	
}

  echo '</div>';

//pagination
if( $count == 10){
	
	  echo '<button data-camp="'.$post_id.'" data-page="10" id="more_posted_posts">Load more ...</button><div class="spinner-more_posted_posts spinner"  ></div>';
	
}

	

?>