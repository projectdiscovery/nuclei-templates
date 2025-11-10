<?php

class wpAutomaticWallMart{
	
	public $ch; // curl handle
	public $apiKey; // api key
	public $lsPublisherId; // published ID
	
	function __construct(&$ch,$apiKey,$lsPublisherId = ''){
		
		$this->ch = $ch;
		$this->apiKey = $apiKey;
		
		if(trim($lsPublisherId) == '') $lsPublisherId = 1804192 ;
		
		$this->lsPublisherId = $lsPublisherId;
		
	}
	
	function getItemsByKeyword($keyword,$parms=array(),$start=0){
		
		//http://api.walmartlabs.com/v1/search?apiKey={apiKey}&lsPublisherId={Your LinkShare Publisher Id}&query=ipod

		// Encoded keyword
		$keywordEnc= urlencode( trim($keyword) );
		
		// Search URL 
		$wallMartUrl = "http://api.walmartlabs.com/v1/search?apiKey={$this->apiKey}&publisherId={$this->lsPublisherId}&query=$keywordEnc&responseGroup=full";
		
		// Additional Parms
		foreach ($parms as $key=>$value){
			
			$wallMartUrl.= '&'.trim($key) .'='.trim (urlencode($value));
			
		}
		
		  echo '<br>'.str_replace($this->apiKey, '[key]', $wallMartUrl) ;
		
		// Execute
		$x='error';
		curl_setopt($this->ch, CURLOPT_HTTPGET, 1);
		curl_setopt($this->ch, CURLOPT_URL, trim($wallMartUrl));
		$exec=curl_exec($this->ch);
		$x=curl_error($this->ch);
		
		//validate reply
		if(trim($exec) == ''){
			throw new Exception('Empty response from Walmart with possible curl error '.$x);
		}
		
		return $this->parseResult($exec);
		
	}
	
	function getItemByUPC(){
		
	}
	
	function parseResult($exec){
		
		//validate Json		
		if(!stristr($exec, '{')){
			throw new Exception('Not Json reply from walmart: '.$exec);
		}
		
		//Json decode
		$json = json_decode($exec);
		
		//return
		return $json->items;
		
	}
	
}