<?php
/**
 * Grab full content from feeds knowing the summary text and the full source html
 */
 

class AtefFullContent{

	public  $debug = true;
	
	/**
	 * Remove headers , styles footer and so on
	 * @param unknown $doc
	 */
	function prepareDoc($doc){
		
		// Get body
		$doc = preg_match('{<body.*</body>}s', $doc , $bodyMatchs );
		$docBody = $bodyMatchs[0];
		
		if(trim($docBody) == ''){
			$this->echoDebug('No Body Found ... skiping');
			return false;
		}
		
		// Remove styles and js
		$pattersToStrip = array('{<noscript.*?script>}s','{<script.*?script>}s');
		$docBody = preg_replace($pattersToStrip , '', $docBody);
		
		print_r($docBody);
		
		//  echo $doc;
		
	}
	
	function echoDebug($msg){
		print_r($msg);
	}
	
	
}