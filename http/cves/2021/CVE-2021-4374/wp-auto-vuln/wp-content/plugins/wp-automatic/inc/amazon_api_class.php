<?php

/**
 * Class to access Amazons Product Advertising API
 * @author Mo Atef
 * @link http://www.deandev.com
 * @version 1.0
 */

// Signing requests class
require_once 'aws_signed_request.php';
class wp_automatic_AmazonProductAPI {
	
	/**
	 * curl handle
	 */
	public $ch;
	
	/**
	 * Your Amazon Access Key Id
	 * 
	 * @access private
	 * @var string
	 */
	private $public_key = "";
	
	/**
	 * Your Amazon Secret Access Key
	 * 
	 * @access private
	 * @var string
	 */
	private $private_key = "";
	
	/**
	 * Your Amazon Associate Tag
	 * Now required, effective from 25th Oct.
	 * 2011
	 * 
	 * @access private
	 * @var string
	 */
	private $associate_tag = "YOUR AMAZON ASSOCIATE TAG";
	private $region = "";
	private $host = "";
	
	/**
	 * Constants for product types
	 * 
	 * @access public
	 * @var string
	 */
	
	/*
	 * Only three categories are listed here.
	 * More categories can be found here:
	 * http://docs.amazonwebservices.com/AWSECommerceService/latest/DG/APPNDX_SearchIndexValues.html
	 */
	const MUSIC = "Music";
	const DVD = "DVD";
	const GAMES = "VideoGames";
	function __construct($public, $private, $ass, $region) {
		$this->public_key = $public;
		$this->private_key = $private;
		$this->associate_tag = $ass;
		$this->region = $region;
		$this->host = "webservices.amazon." . $this->region;
		
		$this->awsv4 = new wp_automatic_AwsV4 ( $public, $private );
		$this->awsv4->setRegionName ( $this->region ); // e.g com should be converted to us-east-1
		$this->awsv4->setServiceName ( "ProductAdvertisingAPI" );
		$this->awsv4->addHeader ( 'host', $this->host );
		$this->awsv4->setRequestMethod ( "POST" );
		$this->awsv4->addHeader ( 'content-encoding', 'amz-1.0' );
		$this->awsv4->addHeader ( 'content-type', 'application/json; charset=utf-8' );
		$this->awsv4->addHeader ( 'host', $this->host );
		
		$rand = rand ( 2, 3 );
		
		// sleep( $rand );
		
		echo '<br>Waiting for: ' . $rand . ' Seconds...<br>';
	}
	
	/**
	 * Check if the xml received from Amazon is valid
	 *
	 * @param mixed $response
	 *        	xml response to check
	 * @return bool false if the xml is invalid
	 * @return mixed the xml response if it is valid
	 * @return exception if we could not connect to Amazon
	 */
	private function verifyXmlResponse($response) 
	{
		@$err = $response->Error->Message;
		
		if (trim ( $err ) != '') {
			
			throw new Exception ( '<br>' . $err );
			return false;
		}
		
		if ($response === False) {
			throw new Exception ( "Could not connect to Amazon" );
		} else {
			if (isset ( $response->Items->Item->ItemAttributes->Title )) {
				return ($response);
			} else 
			{
				// print_r($response);
				$err = @$response->Items->Request->Errors->Error->Message;
				if (trim ( $err ) != '') {
					
					echo '<br><b>Amazon Error</b>:' . $err;
				}
				
				/*
				 * echo("<br>Invalid xml response.");
				 * print_r($response);
				 */
			}
		}
	}
	
	/**
	 * Query Amazon with the issued parameters
	 *
	 * @param array $parameters
	 *        	parameters to query around
	 * @return simpleXmlObject xml query response
	 */
	private function queryAmazon($parameters) {
		return wp_automatic_aws_signed_request ( $this->region, $parameters, $this->public_key, $this->private_key, $this->associate_tag );
	}
	
	/**
	 * Return details of products searched by various types
	 *
	 * @param string $search
	 *        	search term
	 * @param string $category
	 *        	search category
	 * @param string $searchType
	 *        	type of search
	 * @return mixed simpleXML object
	 */
	public function searchProducts($search, $ItemPage, $category, $searchType = "UPC") {
		$allowedTypes = array (
				"UPC",
				"TITLE",
				"ARTIST",
				"KEYWORD" 
		);
		$allowedCategories = array (
				"Music",
				"DVD",
				"VideoGames" 
		);
		
		switch ($searchType) {
			case "UPC" :
				$parameters = array (
						"Operation" => "ItemLookup",
						"ItemId" => $search,
						"SearchIndex" => $category,
						"IdType" => "UPC",
						"ItemPage" => $ItemPage,
						"ResponseGroup" => "Medium" 
				);
				break;
			
			case "TITLE" :
				$parameters = array (
						"Operation" => "ItemSearch",
						"Title" => $search,
						"ItemPage" => $ItemPage,
						"SearchIndex" => $category,
						"ResponseGroup" => "Medium" 
				);
				break;
		}
		
		$xml_response = $this->queryAmazon ( $parameters );
		
		return $this->verifyXmlResponse ( $xml_response );
	}
	
	/**
	 * Return details of a product searched by UPC
	 *
	 * @param int $upc_code
	 *        	UPC code of the product to search
	 * @param string $product_type
	 *        	type of the product
	 * @return mixed simpleXML object
	 */
	public function getItemByUpc($upc_code, $product_type) {
		$parameters = array (
				"Operation" => "ItemLookup",
				"ItemId" => $upc_code,
				"SearchIndex" => $product_type,
				"IdType" => "UPC",
				"ResponseGroup" => "Medium" 
		);
		
		$xml_response = $this->queryAmazon ( $parameters );
		
		return $this->verifyXmlResponse ( $xml_response );
	}
	
	/**
	 * Return details of a product searched by ASIN
	 *
	 * @param int $asin_code
	 *        	ASIN code of the product to search
	 * @return mixed simpleXML object
	 */
	public function getItemByAsin($asin_code) {
		$uriPath = "/paapi5/getitems";
		
		$search_params ['PartnerTag'] = $this->associate_tag;
		$search_params ['PartnerType'] = 'Associates';
		$search_params ['Marketplace'] = 'www.amazon.' . $this->region;
		$search_params ['ItemIds'] = array (
				$asin_code 
		);
		$search_params ['Resources'] = array (
				"Images.Primary.Large",
				"Images.Variants.Large",
				"ItemInfo.ContentInfo",
				"ItemInfo.Features",
				"ItemInfo.ProductInfo",
				"ItemInfo.Title",
				"Offers.Listings.Price" 
		);
		
		$payload = json_encode ( $search_params );
		
		$this->awsv4->addHeader ( 'x-amz-target', 'com.amazon.paapi5.v1.ProductAdvertisingAPIv1.GetItems' );
		
		$this->awsv4->setPath ( $uriPath );
		$this->awsv4->setPayload ( $payload );
		$headers = $this->awsv4->getHeaders ();
		$headerString = "";
		
		$cu_headers = array ();
		
		foreach ( $headers as $key => $value ) {
			$cu_headers [] = $key . ': ' . $value;
		}
		
		$request_url = 'https://' . $this->host . $uriPath;
		
		// curl URL
		$curlurl = $request_url;
		
		// Headers
		curl_setopt ( $this->ch, CURLOPT_HTTPHEADER, $cu_headers );
		
		curl_setopt ( $this->ch, CURLOPT_URL, $curlurl );
		curl_setopt ( $this->ch, CURLOPT_POST, true );
		curl_setopt ( $this->ch, CURLOPT_POSTFIELDS, $payload );
		$x = 'error';
		$exec = curl_exec ( $this->ch );
		
		// empty response
		if (trim ( $exec ) == '') {
			echo '<br>Request we issued to Amazon did not return any data ' . $x;
			return array ();
		}
		
		// no json reply
		if (! stristr ( $exec, '{' )) {
			echo '<br>Returned response does not look like a Json reply:' . $exec . $x;
			return array ();
		}
		
		$result = json_decode ( $exec );
		
		// amazon error report
		if (isset ( $result->Errors )) {
			throw new Exception ( '<br><strong>Amazon returned an error</strong>	  <span style="color:red">' . $result->Errors [0]->Code . ':' . $result->Errors [0]->Message . '</span>' );
		}
		
		if (isset ( $result->ItemsResult->Items )) {
			return $result->ItemsResult->Items;
		} else {
			return array ();
		}
	}
	
	/**
	 * Return details of a product searched by keyword
	 *
	 * @param string $keyword
	 *        	keyword to search
	 * @param string $product_type
	 *        	type of the product
	 * @return mixed simpleXML object
	 */
	public function getItemByKeyword($keyword, $ItemPage, $product_type, $additionalParam = array(), $min = '', $max = '') {
		if ($keyword != "*")
			$search_params ['Keywords'] = $keyword;
		$search_params ['Operation'] = 'SearchItems';
		$search_params ['PartnerTag'] = $this->associate_tag;
		$search_params ['PartnerType'] = 'Associates';
		$search_params ['Marketplace'] = 'www.amazon.' . $this->region;
		$search_params ['ItemPage'] = ( int ) $ItemPage;
		$search_params ['Resources'] = array (
				"Images.Primary.Large",
				"Images.Variants.Large",
				"ItemInfo.ContentInfo",
				"ItemInfo.Features",
				"ItemInfo.ProductInfo",
				"ItemInfo.Title",
				"Offers.Listings.Price" 
		);
		
		// min price
		if ($min != "") {
			$search_params ['MinPrice'] = ( int ) $min;
		} else {
			// 1 cent
			$search_params ['MinPrice'] = 1;
		}
		
		// max price
		if ($max != "")
			$search_params ['MaxPrice'] = ( int ) $max;
		
		$search_params = array_merge ( $search_params, $additionalParam );
		
		// Search Index
		if ($product_type != 'All')
			$search_params ['SearchIndex'] = $product_type;
		
		$payload = json_encode ( $search_params );
		
		$uriPath = "/paapi5/searchitems";
		
		$this->awsv4->addHeader ( 'x-amz-target', 'com.amazon.paapi5.v1.ProductAdvertisingAPIv1.SearchItems' );
		$this->awsv4->setPath ( $uriPath );
		$this->awsv4->setPayload ( $payload );
		$headers = $this->awsv4->getHeaders ();
		$headerString = "";
		
		$cu_headers = array ();
		
		foreach ( $headers as $key => $value ) {
			$cu_headers [] = $key . ': ' . $value;
		}
		
		$request_url = 'https://' . $this->host . $uriPath;
		
		// curl URL
		$curlurl = $request_url;
		
		// Headers
		curl_setopt ( $this->ch, CURLOPT_HTTPHEADER, $cu_headers );
		
		curl_setopt ( $this->ch, CURLOPT_URL, $curlurl );
		curl_setopt ( $this->ch, CURLOPT_POST, true );
		curl_setopt ( $this->ch, CURLOPT_POSTFIELDS, $payload );
		$x = 'error';
		$exec = curl_exec ( $this->ch );
		$x = curl_error ( $this->ch );
		
		// empty response
		if (trim ( $exec ) == '') {
			throw new Exception ( 'Request we issued to Amazon did not return any data ' . $x );
		}
		
		// no json reply
		if (! stristr ( $exec, '{' )) {
			throw new Exception ( '<br>Returned response does not look like a Json reply:' . $exec . $x );
		}
		
		$result = json_decode ( $exec );
		
		// print_r($result);
		
		// amazon error report
		if (isset ( $result->Errors )) {
			
			// no results
			if ($result->Errors [0]->Code == 'NoResults') {
				echo '<br>Amazon tells that there were no results matching your search';
				return array ();
			}
			
			throw new Exception ( '<br><strong>Amazon returned an error</strong>	  <span style="color:red">' . $result->Errors [0]->Code . ':' . $result->Errors [0]->Message . '</span>' );
		}
		
		if (isset ( $result->SearchResult->Items )) {
			return $result->SearchResult->Items;
		} else {
			return array ();
		}
	}
	
	/**
	 * Return list of ASINs of items by scraping the page
	 * 
	 * @param string $pageUrl
	 * @param string $ch
	 *        	curl handler
	 */
	public function getASINs($moreUrl, &$ch) {
		
		// curl get
		$x = 'error';
		$url = $moreUrl;
		curl_setopt ( $ch, CURLOPT_HTTPGET, 1 );
		curl_setopt ( $ch, CURLOPT_URL, trim ( $url ) );
		curl_setopt ( $ch, CURLOPT_ENCODING, "" );
		
		$exec = curl_exec ( $ch );
		$x = curl_error ( $ch );
		
		// validate reply
		if (trim ( $exec ) == '') {
			throw new Exception ( 'Empty reply from Amazon with possible curl error ' . $x );
		}
		
		// validate products found
		if (! stristr ( $exec, 'data-asin' )) {
			// throw new Exception('No items found') ;
			echo '<br>No items found';
			return array ();
		}
		
		// extract products
		preg_match_all ( '{data-asin="(.*?)"}', $exec, $productMatchs );
		$asins = $productMatchs [1];
		
		if (stristr ( $exec, 'proceedWarning' )) {
			echo '<br>Reached end page of items';
			return array ();
		}
		
		return ($asins);
	}
}

?>
