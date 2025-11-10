<?php
/**
 * Finds a product deserver price update
 */
function wp_automatic_amazon_prices_update($using_api = true) {
	
	// get a product to update SELECT * FROM `wp_postmeta` WHERE `meta_key` LIKE '%product_price_updated%'
	global $wpdb;
	$prefix = $wpdb->prefix;
	
	$query = "SELECT * FROM `{$prefix}postmeta` WHERE `meta_key` = 'product_price_updated' ORDER BY `meta_value` ASC limit 1 ";
	$rows = $wpdb->get_results ( $query );
	
	if (count ( $rows ) > 0) {
		
		$time = time ();
		$yesterday = $time - 86400;
		
		$row = $rows [0];
		
		if ($row->meta_value < $yesterday) {
			$pid = $row->post_id;
			
			echo '<br>Updating an amazon product price at post:' . $pid;
			wp_automatic_amazon_price_update ( $pid, $using_api );
		}
	}
}

/**
 * Updates a specific post product price
 *
 * @param integer $pid
 */
function wp_automatic_amazon_price_update($pid, $using_api) {
	
	// get old price,asin,and more
	global $wpdb;
	$prefix = $wpdb->prefix;
	$price = ''; //ini
	
	$query = "SELECT * FROM `{$prefix}postmeta` WHERE `post_id` = '$pid' ";
	$rows = $wpdb->get_results ( $query );
	
	$isWooProduct = false;
	
	foreach ( $rows as $row ) {
		
		if ($row->meta_key == 'product_asin') {
			$product_asin = $row->meta_value;
		} elseif ($row->meta_key == 'product_price') {
			$product_price = $row->meta_value;
		} elseif ($row->meta_key == 'product_list_price') {
			$product_list_price = $row->meta_value;
		} elseif ($row->meta_key == 'original_link') {
			
			// find region
			preg_match ( '{amazon.(.*?)/}', $row->meta_value, $matchs );
			$region = ($matchs [1]);
		} elseif ($row->meta_key == '_price') {
			$isWooProduct = true;
		}
	}
	
	// getting details from amazon
	echo ' ASIN:' . $product_asin;
	
	// curl ini
	$ch = curl_init ();
	curl_setopt ( $ch, CURLOPT_HEADER, 0 );
	curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 10 );
	curl_setopt ( $ch, CURLOPT_TIMEOUT, 20 );
	curl_setopt ( $ch, CURLOPT_REFERER, 'http://www.bing.com/' );
	curl_setopt ( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8' );
	curl_setopt ( $ch, CURLOPT_MAXREDIRS, 5 ); // Good leeway for redirections.
	curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, 1 ); // Many login forms redirect at least once.
	
	if ($using_api) {
		
		// API method
		
		$amazonPublic = get_option ( 'wp_amazonpin_abk', '' );
		$amazonSecret = get_option ( 'wp_amazonpin_apvtk', '' );
		$amazonAid = get_option ( 'wp_amazonpin_aaid', '' );
		
		try {
			
			$obj = new wp_automatic_AmazonProductAPI ( trim ( $amazonPublic ), trim ( $amazonSecret ), trim ( $amazonAid ), $region );
			$obj->ch = $ch;
			
			$result = $obj->getItemByAsin ( $product_asin );
		} catch ( Exception $e ) {
			echo '<br>Exception:' . $e->getMessage ();
			return;
		}
		
		if (count ( $result ) > 0) {
			$Item = $result [0];
			
			if (! isset ( $Item->Offers->Listings [0]->Price )) {
				echo '<-- no price found';
				return;
			}
			
			// current price
			$price = '';
			$price = $Item->Offers->Listings [0]->Price->DisplayAmount;
			$price = trim ( preg_replace ( '{\(.*?\)}', '', $price ) );
			$price_numeric = $Item->Offers->Listings [0]->Price->Amount;
			
			// list price
			$ListPrice = '';
			
			if (isset ( $Item->Offers->Listings [0]->Price->Savings )) {
				$ListPrice = $Item->Offers->Listings [0]->Price->Savings->Amount + $price_numeric;
				$ListPrice = str_replace ( $price_numeric, $ListPrice, $price );
			}
			
			if (trim ( $ListPrice ) == '') {
				$ListPrice = $price;
			}
		}
	} else {
		
		// no API method
		
		$amazonAid = get_option ( 'wp_amazonpin_aaid', '' );
		
		require_once (dirname ( __FILE__ ) . '/inc/class.amazon.api.less.php');
		$obj = new wp_automatic_amazon_api_less ( $ch, $region );
		
		try {
			
			$agent = get_option ( 'wp_automatic_amazon_agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.163 Safari/537.36' );
			curl_setopt ( $ch, CURLOPT_USERAGENT, $agent );
			
			$item = $obj->getItemByAsin ( $product_asin );
			
			if( isset($item['item_price']) ){
				
				$price = $item['item_price'];
				$ListPrice = $item['item_pre_price'];
			}
			
		 
		} catch ( Exception $e ) {
			echo '<br>Amazon error:' . $e->getMessage ();
		}
	}
	
	
	// update price
	if (trim ( $price ) != '') {
		if ($price != $product_price || $ListPrice != $product_list_price) {
			
			echo '<-- Price changed. updating...';
			
			update_post_meta ( $pid, 'product_price', ( string ) $price );
			update_post_meta ( $pid, 'product_list_price', ( string ) $ListPrice );
			
			if ($isWooProduct) {
				
				$thousandSeparator = ',';
				
				// woo sousands separator
				if (class_exists ( 'WooCommerce' )) {
					$woocommerce_price_thousand_sep = get_option ( 'woocommerce_price_thousand_sep', '' );
					
					if ($woocommerce_price_thousand_sep == '.' || $woocommerce_price_thousand_sep == ',') {
						$thousandSeparator = $woocommerce_price_thousand_sep;
						echo '<br>Woo Thusand separator:' . $woocommerce_price_thousand_sep;
					}
				}
				
				// fixing listPrice
				$price_no_commas = str_replace ( $thousandSeparator, '', $ListPrice );
				preg_match ( '{\d.*\d}is', ($price_no_commas), $price_matches );
				update_post_meta ( $pid, '_regular_price', $price_matches [0] );
				;
				
				// fixing sell price
				$price_no_commas = str_replace ( $thousandSeparator, '', $price );
				preg_match ( '{\d.*\d}is', ($price_no_commas), $price_matches );
				update_post_meta ( $pid, '_price', $price_matches [0] );
				update_post_meta ( $pid, '_sale_price', $price_matches [0] );
				;
			}
		} else {
			
			echo '<-- Price is up-to-date';
		}
	}
	
	update_post_meta ( $pid, 'product_price_updated', time () );
}