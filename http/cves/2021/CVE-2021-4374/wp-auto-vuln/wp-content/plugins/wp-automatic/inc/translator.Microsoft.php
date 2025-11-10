<?php
/**
 * Class MicrosoftTranslator to translate texts
 * @author sweetheatmn
 * @version 1.1.0
 * Changelog: 1.1.0: added method to translate via POST Request + Translate posts more than 10000 chars count
 */
class MicrosoftTranslator {
	public $ch;
	public $accessToken;
	private $endpoint = "https://api.cognitive.microsofttranslator.com";
	private $key;
	private $region;
	
	/**
	 *
	 * Constructor to reciveve curl handler
	 *
	 * @param CURL $ch
	 *        	curl handler
	 */
	function __construct(&$ch) {
		
		// Set curl handler
		$this->ch = $ch;
		
		// Don't display headers for json decode
		curl_setopt ( $this->ch, CURLOPT_HEADER, 0 );
	}
	
	/**
	 *
	 * Get an authorization Token to use for translation
	 *
	 * @param text $clientID
	 * @param text $clientSecret
	 *
	 * @return string
	 *
	 */
	function getToken($clientId , $wp_automatic_mt_region) {
		$this->key = $clientId;
		$this->region = $wp_automatic_mt_region;
	}
	
	/**
	 *
	 * Translate text using Microsoft translator with POST Method
	 *
	 * @param string $sourceText
	 *        	Source Text
	 * @param string $fromLanguage
	 *        	From Language
	 * @param string $toLanguage
	 *        	To Lanuguage
	 *        	
	 * @return text
	 *
	 */
	function translateTextArr($sourceText, $fromLanguage, $toLanguage) {
		return $this->translateText ( $sourceText, $fromLanguage, $toLanguage );
		
		$inputStrArr = array (
				$sourceText 
		);
		
		// Post translate request
		$curlUrl = "http://api.microsofttranslator.com/V2/Http.svc/TranslateArray";
		$requestXml = '<TranslateArrayRequest><AppId/>' . '<From>' . $fromLanguage . '</From>' . '<Options>' . '<Category xmlns="http://schemas.datacontract.org/2004/07/Microsoft.MT.Web.Service.V2" />' . '<ContentType xmlns="http://schemas.datacontract.org/2004/07/Microsoft.MT.Web.Service.V2">text/plain</ContentType>' . '<ReservedFlags xmlns="http://schemas.datacontract.org/2004/07/Microsoft.MT.Web.Service.V2" />' . '<State xmlns="http://schemas.datacontract.org/2004/07/Microsoft.MT.Web.Service.V2" />' . '<Uri xmlns="http://schemas.datacontract.org/2004/07/Microsoft.MT.Web.Service.V2" />' . '<User xmlns="http://schemas.datacontract.org/2004/07/Microsoft.MT.Web.Service.V2" />' . '</Options>' . '<Texts>' . '<string xmlns="http://schemas.microsoft.com/2003/10/Serialization/Arrays">' . htmlspecialchars ( $sourceText ) . '</string>' . '</Texts>' . '<To>' . $toLanguage . '</To>' . '</TranslateArrayRequest>';
		
		curl_setopt ( $this->ch, CURLOPT_URL, $curlUrl );
		curl_setopt ( $this->ch, CURLOPT_POST, true );
		curl_setopt ( $this->ch, CURLOPT_POSTFIELDS, $requestXml );
		curl_setopt ( $this->ch, CURLOPT_HTTPHEADER, array (
				'Authorization: Bearer ' . $this->accessToken,
				"Content-Type: text/xml" 
		) );
		$exec = curl_exec ( $this->ch );
		$x = curl_error ( $this->ch );
		
		// Empty reply check
		if (trim ( $exec ) == '') {
			throw new Exception ( 'Empty translator token request reply with possible curl error ' . $x );
		}
		
		// Exception check
		if (stristr ( $exec, 'Argument Exception' )) {
			
			// Read exception
			preg_match ( '{Message\:(.*?)<}s', $exec, $matchs );
			
			$txtException = $matchs [1];
			throw new Exception ( 'Text Translate Argument Exception found ' . $txtException );
		}
		
		// TranslateApiException
		if (stristr ( $exec, 'TranslateApiException' )) {
			
			// Read exception
			preg_match ( '{Message\:(.*?)<}s', $exec, $matchs );
			
			$txtException = $matchs [1];
			throw new Exception ( 'Text Translate Method Exception found ' . $txtException );
		}
		
		if (! stristr ( $exec, 'ArrayOfTranslateArrayResponse' )) {
			
			echo $exec;
			
			throw new Exception ( 'Text Translate Method Not valid reply :' . substr ( $exec, 0, 15 ) );
		}
		
		// Load strings
		$xmlObject = simplexml_load_string ( $exec );
		
		$finalTranslation = '';
		
		foreach ( $xmlObject as $translatedText ) {
			$finalTranslation .= $translatedText->TranslatedText;
		}
		
		return $finalTranslation;
	}
	
	/**
	 *
	 * Translate text using Microsoft translator with GET Method
	 *
	 * @param string $sourceText
	 *        	Source Text
	 * @param string $fromLanguage
	 *        	From Language
	 * @param string $toLanguage
	 *        	To Lanuguage
	 *        	
	 * @return text
	 *
	 */
	function translateText($sourceText, $fromLanguage, $toLanguage) {
		
		// Post translate request
		$curlUrl = "http://api.microsofttranslator.com/v2/Http.svc/Translate?text=" . urlencode ( $sourceText ) . "&from=$fromLanguage&to=$toLanguage";
		$curlUrl = $this->endpoint . "/translate?api-version=3.0&from=$fromLanguage&to=$toLanguage";
		// $curlUrl = "https://api.cognitive.microsofttranslator.com/translate?api-version=3.0&to=es";
		
		$requestBody = array (
				array (
						'Text' => $sourceText 
				) 
		);
		
		$curlpost = json_encode ( $requestBody );
		
		curl_setopt ( $this->ch, CURLOPT_URL, $curlUrl );
		curl_setopt ( $this->ch, CURLOPT_POST, true );
		curl_setopt ( $this->ch, CURLOPT_POSTFIELDS, $curlpost );
		
		$headers [] = "Ocp-Apim-Subscription-Key: " . $this->key;
		
		//region
		if(trim($this->region) != ''){
			$headers [] = "Ocp-Apim-Subscription-Region: " . trim($this->region)  ;
		}
		
		
		$headers [] = "Content-Type: application/json";
		curl_setopt ( $this->ch, CURLOPT_HTTPHEADER, $headers );
		
		$x = 'error';
		$exec = curl_exec ( $this->ch );
		$x = curl_error ( $this->ch );
		
		// Empty reply check
		if (trim ( $exec ) == '') {
			throw new Exception ( 'Empty translator token request reply with possible curl error ' . $x );
		}
		
		// Exception check
		if (stristr ( $exec, '"error":' )) {
			
			// Read exception
			preg_match ( '{"message":"(.*?)"}s', $exec, $matchs );
			
			$txtException = $matchs [1];
			throw new Exception ( 'Translator text returned an error: ' . $txtException );
		}
		
		// TranslateApiException
		if (stristr ( $exec, 'TranslateApiException' )) {
			
			// Read exception
			preg_match ( '{Message\:(.*?)<}s', $exec, $matchs );
			
			$txtException = $matchs [1];
			throw new Exception ( 'Text Translate Method Exception found ' . $txtException );
		}
		
		// Load strings
		$json = json_decode ( $exec );
		if (! isset ( $json [0] ) || ! isset ( $json [0]->translations )) {
			throw new Exception ( 'Returned Json does not contain the translations ' . $exec );
		}
		
		$finalTranslation = $json [0]->translations [0]->text;
		
		return $finalTranslation;
	}
	
	/**
	 * Translate Wrap translates 5000 chars by 5000 chars to skip translator limit
	 *
	 * @param unknown $sourceText
	 * @param unknown $fromLanguage
	 * @param unknown $toLanguage
	 */
	function translateWrap($sourceText, $fromLanguage, $toLanguage) {
		$translated = '';
		
		// if just one patch
		$charLimit = 4900;
		$charCount = $this->chars_count ( $sourceText );
		if ($charCount < $charLimit) {
			return $this->translateTextArr ( $sourceText, $fromLanguage, $toLanguage );
		} else {
			
			// multiple patches
			
			$patchsCount = floor ( $charCount / $charLimit ) + 1;
			
			for($i = 0; $i < $patchsCount; $i ++) {
				
				$patchStartIndex = $i * $charLimit;
				
				if (function_exists ( 'mb_substr' )) {
					$currentPath = mb_substr ( $sourceText, $patchStartIndex, $charLimit );
				} else {
					$currentPath = mb_substr ( $sourceText, $patchStartIndex, $charLimit );
				}
				
				$translated .= $this->translateTextArr ( $currentPath, $fromLanguage, $toLanguage );
			}
			
			return $translated;
		}
	}
	
	/**
	 * Count chars on text using mb_ module and if not exists it count it using strlen
	 *
	 * @param unknown $text
	 */
	function chars_count(&$text) {
		if (function_exists ( 'mb_strlen' )) {
			return mb_strlen ( $text );
		} else {
			return strlen ( $text );
		}
	}
	
	/**
	 * Gets a text substr using mb_string module if exists and if not use substr function
	 *
	 * @param unknown $text
	 * @param unknown $start
	 * @param unknown $end
	 */
	function text_substr(&$text, $start, $length) {
		if (function_exists ( 'mb_substr' )) {
			return mb_substr ( $text, $start, $length );
		} else {
			return substr ( $text, $start, $length );
		}
	}
}