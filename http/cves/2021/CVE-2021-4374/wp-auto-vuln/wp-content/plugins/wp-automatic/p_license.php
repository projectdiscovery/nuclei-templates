<?php
function wp_automatic_license_notice() {
	 
	$licenseactive=get_option('wp_automatic_license_active','');
			
			//reactivating
			
			//check last activate date
			$lastcheck=get_option('wp_automatic_license_active_date');
			$purchase = get_option('wp_automatic_license');
			$seconds_diff = time() - $lastcheck;
			$minutes_diff = $seconds_diff / 60 ;
			
			
			
				//reset date
				update_option('wp_automatic_license_active_date', time());

				//reactivate
				//activating
				//curl ini
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_HEADER,0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
				curl_setopt($ch, CURLOPT_TIMEOUT,20);
				curl_setopt($ch, CURLOPT_REFERER, 'http://www.bing.com/');
				curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8');
				curl_setopt($ch, CURLOPT_MAXREDIRS, 5); // Good leeway for redirections.
				
				//curl get
				$x='error';
				
				$proxy = false;
				
				if($proxy == false){
					$url='https://deandev.com/license/index.php?itm=1904470&domain='.$_SERVER['HTTP_HOST'].'&purchase='.$purchase;
				}else{
					$url='http://deandev-proxy.appspot.com/license/index.php?itm=1904470&domain='.$_SERVER['HTTP_HOST'].'&purchase='.$purchase;
				}
				
				curl_setopt($ch, CURLOPT_HTTPGET, 1);
				curl_setopt($ch, CURLOPT_URL, trim($url));
				
				
				$exec=curl_exec($ch);
				
				$x=curl_error($ch);
				
				$resback=$exec;
				@$resarr=json_decode($resback);
				
				curl_close($ch);
				$wp_pinterest_active_message='License Activated';
	
}
add_action( 'admin_notices', 'wp_automatic_license_notice' );