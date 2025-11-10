<?php
/**
 * Class:Translator to translate using Deepl
 * @author Atef (sweetheatmn@gmail.com)
 * @version 1.0.0
 */

class DeeplTranslator{
	
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
	 * Translate text using  Deepl
	 * @param unknown $sourceText
	 * @param unknown $fromLanguage
	 * @param unknown $toLanguage
	 * @return string translated text
	 */
	
	function translateText($sourceText , $fromLanguage ,$toLanguage){
		
		//translating
		$x='error';
		
		
		$curlurl='https://translate.yandex.net/api/v1.5/tr.json/translate?key='.$this->key.'&lang='.$fromLanguage.'-'.$toLanguage;
		$curlurl = 'https://api.deepl.com/v2/translate?auth_key='.$this->key.'&target_lang='.$toLanguage;
		
		if($fromLanguage != 'auto') $curlurl.= '&source_lang=' . $fromLanguage ;
		
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
		
	 
		//validate translation
		if( ! isset($json_reply->translations)){
			throw new Exception('No Translation was returned, unexpected reply from Yandex' . $exec);
		}
	 
		
		//translation valid, return
		return $json_reply->translations[0]->text;
		
	}
	
}