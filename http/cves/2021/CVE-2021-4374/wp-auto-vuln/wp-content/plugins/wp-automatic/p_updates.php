<?php
// UPDATES
$licenseactive=get_option('wp_automatic_license_active','');

	
	//fire checks
	require 'plugin-updates/plugin-update-checker.php';
	$wp_automatic_UpdateChecker = PucFactory::buildUpdateChecker(
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
