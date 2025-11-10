<?php

//edit page
add_action('admin_print_scripts-' . 'edit.php', 'wp_automatic_admin_edit');

//bulk pin scripts
function wp_automatic_admin_edit(){
	
	if(isset( $_GET['post_type'] )){
		
		if($_GET['post_type'] == 'wp_automatic'){
			
			wp_enqueue_script(
			'wp_automatic_admin_edit',plugins_url( '/js/wp-automatic-admin-edit.js' , __FILE__ ), array(),'1.0.1' 
			);
		}
		
	}
	
	
}