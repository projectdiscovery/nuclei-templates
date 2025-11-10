<?php
/**
 * Class:Translator to translate using Google
 * @author sweetheatmn (sweetheatmn@gmail.com)
 * @version 1.2.0 
 * updated to cope with new gtranslate changes @18 september 2016
 */

class GoogleTranslator{
	
	public $ch; //curl handler to use 
	
	/**
	 * Constructor to recieve curl handler
	 * @param curl $ch
	 */
	function __construct(&$ch){
		$this->ch = $ch;
	}
	
	/**
	 * Translate text using Google Post request to google translate 
	 * @param unknown $sourceText
	 * @param unknown $fromLanguage
	 * @param unknown $toLanguage
	 * @return string translated text
	 */
	
	function translateText($sourceText , $fromLanguage ,$toLanguage){
		
		//saving the content to a temp file
		
		
		if (   trim(ini_get('open_basedir')) != ''){
			
			  echo '<br>open_basedir exists';
			$upload_dir = wp_upload_dir ();
			$tmpFileUri = $upload_dir['basedir'].'/wp_automatic_tmp';
			$tmpHandle = fopen($tmpFileUri, "w+");
			fwrite($tmpHandle, $sourceText);
 			
			
		}else{
			
 			
			$tmpHandle = tmpfile();
			$metaDatas = stream_get_meta_data($tmpHandle);
			$tmpFileUri = $metaDatas['uri'];
			
			 
			
			fwrite($tmpHandle, $sourceText);
			
		}
		
 
		//translate file url
		curl_setopt($this->ch, CURLOPT_URL, "https://translate.googleusercontent.com/translate_f");
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->ch, CURLOPT_POST, true );
		
		if( class_exists('CurlFile')){
			$curlFile = new \CurlFile( $tmpFileUri, 'text/plain', 'test.txt');
		}else{
			$curlFile = '@'.$tmpFileUri.';type=text/plain;filename=test.txt';
		}
		
		$post = [
				'file' => $curlFile,
				'sl'   => $fromLanguage,
				'tl'   => $toLanguage,
				'js'   => 'y',
				'prev'   => '_t',
				'hl'   => 'en',
				'ie'   => 'UTF-8',
		
		];
		
		
		curl_setopt ( $this->ch, CURLOPT_POSTFIELDS, $post );
		
		
		$headers = array();
		$headers[] = "User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:49.0) Gecko/20100101 Firefox/49.0";
		$headers[] = "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
		$headers[] = "Accept-Language: en-US,en;q=0.5";
		$headers[] = "Referer: https://translate.google.com/?tr=f&hl=en";
		$headers[] = "Connection: keep-alive";
		$headers[] = "Upgrade-Insecure-Requests: 1";
		 
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
		
		$exec = curl_exec($this->ch);
		
		
		$x = curl_error($this->ch);
		
		//close and delete temp file
		fclose($tmpHandle);

		// Empty response check 
		if(trim($exec) == ''){
			throw new Exception('Empty translator reply with possible curl error '.$x);
		}
		
		// Validate response result box 
		if(   stristr($exec, 'Error 403') ){
			  echo $exec;
			throw new Exception('Error 403 from Google');
		}
		
		//extra <pre removal fix
		$exec= str_replace('<pre>','' , $exec );
		$exec= str_replace('</pre>','' , $exec );
		
		return $exec ;
 		
	}
	
}