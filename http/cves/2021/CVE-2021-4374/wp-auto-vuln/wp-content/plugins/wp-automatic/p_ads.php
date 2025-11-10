<?php 
 
function wp_automatic_ad1_wp_shortcode_func( $atts ) {

	$ad1=get_option('wp_automatic_ad1');
	return stripslashes($ad1);
}

function wp_automatic_ad2_wp_shortcode_func( $atts ) {

	$ad2=get_option('wp_automatic_ad2');
	return stripslashes($ad2);
}

add_shortcode( 'ad_1', 'wp_automatic_ad1_wp_shortcode_func' );
add_shortcode( 'ad_2', 'wp_automatic_ad2_wp_shortcode_func' );

?>