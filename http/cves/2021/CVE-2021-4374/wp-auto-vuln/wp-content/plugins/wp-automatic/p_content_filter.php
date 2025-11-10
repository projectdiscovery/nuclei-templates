<?php

add_filter('the_content', 'wp_automatic_the_content_filter');
	
function wp_automatic_the_content_filter($cnt){
		global $post;
		
		if(! isset($post->ID)) return $cnt;
		
		//fix youtube deleted rating images
		if( stristr($cnt, 'youtube.com/static/images') ){
			
			$icn_star_empty = plugins_url('images/youtube_imgs/icn_star_empty_11x11.gif' , __FILE__);
			$icn_star_half = plugins_url('images/youtube_imgs/icn_star_half_11x11.gif' , __FILE__);
			$icn_star_full = plugins_url('images/youtube_imgs/icn_star_full_11x11.gif' , __FILE__);
				
				
			$cnt = str_replace('http://gdata.youtube.com/static/images/icn_star_full_11x11.gif', $icn_star_full,$cnt );
			$cnt = str_replace('http://gdata.youtube.com/static/images/icn_star_half_11x11.gif', $icn_star_half,$cnt );
			$cnt = str_replace('http://gdata.youtube.com/static/images/icn_star_empty_11x11.gif', $icn_star_empty,$cnt );
			
		}
		
		//remove first image
		$active = get_post_meta( $post->ID ,'wp_automatic_remove_first_image' ,1) ;
		
		
		if($active == 'yes'){
			//return 'active remove ';
			
			$cnt= preg_replace ( '/<img [^>]*src=["|\'][^"|\']+.*?>/i', '' ,$cnt ,1 );
			
		}else{
			
		}
		
		//remove gallery from home
		if(stristr($cnt, 'wp_automatic_gallery')){
			
			if(! is_single()){
				$cnt = preg_replace('{<img.*?class="wp_automatic_gallery.*?>}', '', $cnt);
			}else{
				$cnt = preg_replace('{(<img src="([^<]*?)" class="wp_automatic_gallery")}' , "$1 data-a-src='$2'" , $cnt );
			}
		}
		
		 //replace instagram embeded videos 
		if(stristr($cnt, '[video') && stristr($cnt, '_n.mp4')){
			//get instagram post URL
			$original_link = get_post_meta( $post->ID ,'original_link' ,1) ;
			$videoEmbed = '<blockquote class="instagram-media" data-instgrm-permalink="'.$original_link.'" data-instgrm-version="8" style=" background:#FFF; border:0; border-radius:3px; box-shadow:0 0 1px 0 rgba(0,0,0,0.5),0 1px 10px 0 rgba(0,0,0,0.15); margin: 1px; max-width:658px; padding:0; width:99.375%; width:-webkit-calc(100% - 2px); width:calc(100% - 2px);"></blockquote> <script async defer src="//www.instagram.com/embed.js"></script>';
			$cnt = preg_replace('{\[video.*?\]}', $videoEmbed , $cnt);
		}
		
		//replace old instagram images urls
		if(stristr($cnt, '<img src="https://instagram.') || stristr($cnt, 'cdninstagram')){
			
			//get featured image URL
			$thumb_url = (get_the_post_thumbnail_url($post));
			if(trim($thumb_url) != ''){
				$cnt = preg_replace('{<img src="https://instagram.*?>}','<img src="'.$thumb_url.'" />',$cnt,1);
				$cnt = preg_replace('{<img src="https://scontent.*?>}','<img src="'.$thumb_url.'" />',$cnt,1);
				
				$cnt = preg_replace('{<img src="https://instagram.*?>}','',$cnt);
				$cnt = preg_replace('{<img src="https://scontent.*?>}','',$cnt);
				
			}
			
		}
		
		//remove amazon iframe wp_automatic_amazon_review
		if( strpos($cnt, 'wp_automatic_amazon_review')){
			$cnt = preg_replace('{<iframe.*?iframe>}','',$cnt);
		}
		 		
		return $cnt;
		
}

//link to source instead

add_filter('post_link','wp_automatic_permalink_changer' , 10, 3);

function wp_automatic_permalink_changer($permalink , $post, $leavename=false  ){
   
	 
	if (!empty($post->ID)) {

		$link_to_source = get_post_meta($post->ID, '_link_to_source', true);
 
		if ( trim($link_to_source) != '' ) {
			
			$new_permalink = get_post_meta($post->ID, 'original_link', true);
			if(trim($new_permalink) != ''  ) return $new_permalink;
			
		}
	}
	
	return $permalink;
}

 


//Canonical urls
function wp_automatic_rel_canonical_with_custom_tag_override()
{
	if( !is_singular() )
		return;

	global $wp_the_query;
	if( !$id = $wp_the_query->get_queried_object_id() )
		return;

	// check whether the current post has content in the "canonical_url" custom field
	$canonical_url = get_post_meta( $id, 'canonical_url', true );
	if( '' != $canonical_url )
	{
		// trailing slash functions copied from http://core.trac.wordpress.org/attachment/ticket/18660/canonical.6.patch
		$link = user_trailingslashit( trailingslashit( $canonical_url ) );
		  echo "<link rel='canonical' href='" . esc_url( $link ) . "' />\n";
	} 
	
}

// remove the default WordPress canonical URL function
if( function_exists( 'rel_canonical' ) )
{
	remove_action( 'wp_head', 'rel_canonical' );
}
// replace the default WordPress canonical URL function with your own
add_action( 'wp_head', 'wp_automatic_rel_canonical_with_custom_tag_override' );

//Facebook videos
add_shortcode( 'fb_vid', 'wp_automatic_fbvid_shortcode_func' );


function wp_automatic_fbvid_shortcode_func( $atts ) {
 
	$cont='';

	extract( shortcode_atts( array(
	'id' => 'something',
	'autoplay' => 'false',
	'mute' => 0
		
	), $atts ) );
	
	$js_mute = '';
	if( $mute != 1  && $autoplay == 'false'  ) {
		$js_mute = "window.fbAsyncInit = function() {FB.init({appId      : '',xfbml      : true,version    : 'v2.5'});var my_video_player;FB.Event.subscribe('xfbml.ready', function(msg) {if (msg.type === 'video') {my_video_player = msg.instance; my_video_player.unmute();}});};";
	}
	 
	return '<div id="fb-root"></div><script>' . $js_mute . '(function(d, s, id) {  var js, fjs = d.getElementsByTagName(s)[0];  if (d.getElementById(id)) return;  js = d.createElement(s); js.id = id;  js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.3";  fjs.parentNode.insertBefore(js, fjs);}(document, \'script\', \'facebook-jssdk\'));  </script><div class="fb-video"    data-autoplay="'.$autoplay.'" data-allowfullscreen="true" data-href="https://www.facebook.com/video.php?v='.$id.'&set=vb.500808182&type=1"><div class="fb-xfbml-parse-ignore"></div></div>


	

';
	//return '<iframe src="https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2Fdubsmashegyptian%2Fvideos%2F1924060651230370%2F&show_text=0&width=560"  style="border:none;overflow:hidden" scrolling="no" frameborder="0" allowTransparency="true" allowFullScreen="true"></iframe>';
}

//eBay redirect 
add_action('template_redirect', 'wp_automatic_eb_redirect_end');

function wp_automatic_eb_redirect_end(){
	
	 
	global $wp_the_query;
	if( !$id = $wp_the_query->get_queried_object_id() )
		return;

	// check whether the current post has content in the "canonical_url" custom field
	$wp_automatic_redirect_date = get_post_meta( $id, 'wp_automatic_redirect_date', true );
	 
	if(trim($wp_automatic_redirect_date) != ''){
		if( current_time('timestamp') > $wp_automatic_redirect_date ){
			
			//trash 
			$wp_automatic_trash_date = get_post_meta( $id, 'wp_automatic_trash_date', true );
			
			if(trim($wp_automatic_trash_date) != ''){
				//trash
				wp_trash_post($id);
			}
			
			$wp_automatic_redirect_link = get_post_meta( $id, 'wp_automatic_redirect_link', true );
			wp_redirect($wp_automatic_redirect_link,301);
			
			
			
		}
	}
 	
}

//Redable time shortcode
add_shortcode( 'readable_time', 'wp_automatic_redable_time' );


function wp_automatic_redable_time( $atts ,$cont='') {

	$now = date ( 'Y-m-d H:i:s' );
	
	$now = strtotime (get_date_from_gmt( $now) );
	$cont = strtotime($cont);
	
	if($now > $cont){
		return 'Ended';
	}else{
		return human_time_diff(    $cont , ($now) );
	}
	
	
 	
}

// Formated date
add_shortcode( 'formated_date', 'wp_automatic_formated_date' );


function wp_automatic_formated_date( $atts ,$cont='') {

	$a = shortcode_atts( array(
			'format' => 'M',
			'timestamp' => ''
 
	), $atts );
	
	
	$timeStamp = $a['timestamp'];
	
	if(trim($timeStamp) == '')
	$timeStamp = strtotime($cont);
	
	return date(  $a['format'] , $timeStamp);

}

add_shortcode( 'permalink', 'wp_automatic_permalink' );

function wp_automatic_permalink( $atts ,$cont='') {

 global $post;
 return $post->guid;

}

add_shortcode( 'price_with_discount', 'wp_automatic_price_with_discount' );

function wp_automatic_price_with_discount() {

	global $post; 
	
	if(! isset($post->ID)) return;
	
	$pID = $post->ID;

	$price = get_post_meta($pID,'product_price',1);
	$listPrice = get_post_meta($pID,'product_list_price',1);
	
	if($price == $listPrice){
		return $price;
	}else{
		return '<del>'.$listPrice.'</del> - '. $price ;
	}
	
	
	
}

add_shortcode( 'price_update_date', 'wp_automatic_price_update_date' );

function wp_automatic_price_update_date() {
	
	global $post;
	
	if(! isset($post->ID)) return;
	$pID = $post->ID;
	
	$utc = get_post_meta($pID,'product_price_updated',1);
	
	 if(! is_numeric($utc) || trim($utc) != '') $utc =  time();
	
	 
	return  date ( 'M d,Y H:i:s '  , $utc ).'UTC';
	
}

add_filter( 'get_avatar' , 'wp_automatic_custom_avatar' , 1 , 5 );
add_filter( 'get_comment_author_link' , 'wp_automatic_custom_comment_link' , 1 , 5 );
add_filter( 'get_comment_author_url' , 'wp_automatic_custom_comment_url' , 1 , 5 );

function wp_automatic_custom_comment_url($return){
	
	if(  stristr($return	, 'yt3.')  &&  stristr($return	, '|') ){
		
		$return_parts = explode('|' , $return );
		
		if( isset($return_parts[1]) && trim($return_parts[1]) != '' ){
			return 'https://www.youtube.com/channel/' . $return_parts[1] ;
		}else{
			return '';
		}
		
	}
	
}

//custom fb avatar
function wp_automatic_custom_avatar( $avatar, $id_or_email, $size, $default, $alt ) {
	 								
	//print_r($id_or_email);
	$theMail = '';
	if ( is_numeric( $id_or_email ) ) {
		
		$theMail = (int) $id_or_email;
	 
		
	} elseif ( is_object( $id_or_email ) ) {
		
		if ( ! empty( $id_or_email->comment_author_email) ) {
			$theMail = $id_or_email->comment_author_email ;
		}
		
	}else{
		//email
		$theMail = $id_or_email;
	}

	if(stristr($theMail, 'fb.com')){
	
		$theMailParts = explode('@', $theMail);
		$fbUserID = $theMailParts[0];
		$fbImage = "https://graph.facebook.com/$fbUserID/picture?type=large";
		
		   $avatar = "<img alt='{$alt}' src='{$fbImage}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
	
	}elseif(stristr($theMail, 'photo.jpg')){
		
		$avatar = "<img alt='{$alt}' src='{$theMail}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
		   
	}elseif ( isset($id_or_email->comment_author_url) &&   stristr( $id_or_email->comment_author_url	, 'yt3.')  &&  stristr( $id_or_email->comment_author_url	, '|') ){
		
		$imgParts = explode('|', $id_or_email->comment_author_url);
		
		
		$avatar = "<img alt='{$alt}' src='{$imgParts[0]}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
		
	}
	
	return $avatar;
	
}


function wp_automatic_custom_comment_link (  $return , $author , $comment_id) {
	
 
	
	if(  stristr($return	, 'yt3.')  &&  stristr($return	, '|') ){
		 $return =  preg_replace("{https://yt3.*?\|}", "https://www.youtube.com/channel/", $return);
		 $return = str_replace('https://www.youtube.com/channel/\'', '#\'', $return) ;
		 $return = str_replace('https://www.youtube.com/channel/"', '#"', $return) ;
	}
	
	return $return;
		
}