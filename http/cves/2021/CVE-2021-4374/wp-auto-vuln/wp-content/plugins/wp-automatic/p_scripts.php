<?php 

//front end scripts

function wp_automatic_front_end_scripts(){
	
	//jquery
	wp_enqueue_script('jquery');
	
	
	if(is_single()){
		//custom gallery script
		wp_enqueue_script(
		'wp_automatic_gallery',plugins_url( '/js/main-front.js' , __FILE__ )
		);
		
		//custom gallery style
		wp_enqueue_style('wp_automatic_gallery_style',plugins_url( '/css/wp-automatic.css' , __FILE__ ) , array() , '1.0.0');
		
	}
}

add_action( 'wp_enqueue_scripts', 'wp_automatic_front_end_scripts' ); // wp_enqueue_scripts action hook to link only on the front-end



add_action('admin_print_scripts-' . 'post-new.php', 'wp_automatic_admin_scripts');
add_action('admin_print_scripts-' . 'post.php', 'wp_automatic_admin_scripts');

function wp_automatic_admin_scripts() {

	global $post_type;

	if( 'wp_automatic' == $post_type ){

		wp_enqueue_style('wp_automatic_basic_styles', plugins_url('css/style.css',__FILE__) ,array() , '1.1.0' ) ;
		wp_enqueue_style('wp_automatic_uniform', plugins_url('css/uniform.css',__FILE__));
		wp_enqueue_style('wp_automatic_gcomplete',plugins_url('css/jquery.gcomplete.default-themes.css',__FILE__));

		
		wp_enqueue_script('wp_automatic_unifom_script',plugins_url('js/jquery.uniform.min.js',__FILE__)  ,array() , '1.2.0');
		wp_enqueue_script('wp_automatic_jqtools_script',plugins_url('js/jquery.tools.js',__FILE__));
		wp_enqueue_script('wp_automatic_main_script',plugins_url('js/main.js',__FILE__),array(),'1.9.9');
		wp_enqueue_script('wp_automatic_jqcomplete_script',plugins_url('js/jquery.gcomplete.0.1.2.js',__FILE__));
	}

}


//log page
function wp_automatic_admin_head_log() {
	  echo '<link rel="stylesheet" type="text/css" href="' .plugins_url('css/style.wpautospinner.css', __FILE__). '">';
	  echo '<script src="'.plugins_url('js/jquery.tools.js', __FILE__).'" type="text/javascript"></script>';
	  echo '<script src="'.plugins_url('js/jquery.uniform.min.js', __FILE__).'" type="text/javascript"></script>';
	  echo '<script src="'.plugins_url('js/main_log.js', __FILE__).'" type="text/javascript"></script>';
}
add_action('admin_head-wp_automatic_page_gm_log', 'wp_automatic_admin_head_log');


?>