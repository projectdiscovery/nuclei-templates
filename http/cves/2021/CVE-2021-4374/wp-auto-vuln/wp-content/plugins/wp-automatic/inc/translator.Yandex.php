<?php
/**
 * Class:Translator to translate using Yandex
 * @author Atef (sweetheatmn@gmail.com)
 * @version 1.0.0
 */

class YandexTranslator{
	
	public $ch; //curl handler to use
	public $key;
	
	/**
	 * Constructor to recieve curl handler
	 * @param curl $ch
	 */
	function __construct(&$ch,$key){
		$this->ch = $ch;
		$this->key = $key;
	}
	
	/**
	 * Translate text using Google Post request to google translate
	 * @param unknown $sourceText
	 * @param unknown $fromLanguage
	 * @param unknown $toLanguage
	 * @return string translated text
	 */
	
	function translateText($sourceText , $fromLanguage ,$toLanguage){
		
		//translating
		$x='error';
		$curlurl='https://translate.yandex.net/api/v1.5/tr.json/translate?key='.$this->key.'&lang='.$fromLanguage.'-'.$toLanguage;
		
		$curlurl = str_replace('auto-','' , $curlurl);
		
		
		
	 	$curlpost= "text=" . urlencode($sourceText)  ;
	 	 
	 	curl_setopt($this->ch, CURLOPT_URL, $curlurl);
		curl_setopt($this->ch, CURLOPT_POST, true);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $curlpost); 
		$x='error';
		$exec=curl_exec($this->ch);
		$x=curl_error($this->ch);
		
 
	 		 		
		// Empty response check
		if(trim($exec) == ''){
			throw new Exception('Empty translator reply with possible curl error '.$x);
		}
		
		// Validate JSON {"
		if(   ! stristr($exec, '{"') ){
			throw new Exception('No JSON was returned, unexpected reply from Yandex' . $exec);
		}
		
		//json decode
		$json_reply = json_decode($exec);
		
		//validate JSON
		if( ! isset($json_reply->code)){
			throw new Exception('No code was found in  JSON , unexpected reply from Yandex' . $exec);
		}
		
		$code = $json_reply->code;
		 
		if($code != 200){
			throw new Exception('Yandex error ' . $json_reply->message);
		}
		
		$translated_array = $json_reply->text;
		
		if ( trim($translated_array[0]) == ''){
			throw new Exception('Can not extract returned translation' );
		}
		 
		
		return $translated_array[0] ;
		
	}
	
}