<?php 	// adding menu to dashboard
	if(is_admin()){ add_action('admin_menu', 'wp_automatic_init'); }
	
	function wp_automatic_init(){
		add_submenu_page( 'edit.php?post_type=wp_automatic', 'Settings', 'Settings', 'administrator', 'gm_setting', 'gm_setting' );
		
		 add_submenu_page( 'edit.php?post_type=wp_automatic', 'Log', 'Log', 'administrator', 'gm_log', 'gm_log' );
		 
	}
?>