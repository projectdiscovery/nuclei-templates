<?php
/**
 * Class:Translator to translate using Google
 * @version 1.4.0 
 * updated to cope with new gtranslate changes @22 June 2020
 */
class GoogleTranslator {
	public $ch; // curl handler to use
	
	/**
	 * Constructor to recieve curl handler
	 *
	 * @param curl $ch
	 */
	function __construct(&$ch) {
		$this->ch = $ch;
	}
	function translateText($sourceText, $fromLanguage, $toLanguage) {
		$string = $sourceText;
		
		// protected tags [19...]
		preg_match_all ( '{\[\d*\]}', $string, $all_protected_matchs );
		$all_protected_matchs = $all_protected_matchs [0];
		
		// get strings to translate
		$string = preg_replace ( '{\[\d*\]}', '(*)', $string );
		$string_arr = explode ( '(*)', $string );
		
		// implode of texts for encoding
		$string_raw = implode ( '', $string_arr );
		
		// query string
		$string_enc = '';
		$i = 0;
		
		foreach ( $string_arr as $single_string ) {
			
			if ($i == 0) {
				$string_enc = 'q=' . urlencode ( $single_string );
			} else {
				$string_enc .= '&q=' . urlencode ( $single_string );
			}
			
			$i ++;
		}
		
		$string_raw = implode ( '', $string_arr );
		$article_size = function_exists ( 'mb_strlen' ) ? mb_strlen ( $string_raw ) : strlen ( $string_raw );
		
		if (isset ( $_GET ['debug'] ))
			echo "\n\n\n\n--- Requested translation raw-------\n" . $string_raw . "\n\n\n";
		
		echo '<br>Translated text char count: ' . $article_size;
		
		if ($article_size > 13000) {
			throw new Exception ( 'Translated article is very long, it exceeds the limit of 13000 chars' );
		}
		
		timer_start ();
		$tkk = $this->generateTkk ();
		$tk = $this->generateTk ( $string_raw, $tkk );
		echo '<br>Time taken to decode text for translation:' . timer_stop ();
		
		// fix auto from language
		if ($fromLanguage == 'auto')
			$fromLanguage = '';
		
		$args = [ 
				'anno' => 3,
				'client' => 'te_lib',
				'format' => 'html',
				'v' => '1.0',
				'key' => 'AIzaSyBOti4mM-6x9WDnZIjIeyEU21OpBXqWBgw',
				'logld' => 'vTE_20200210_00',
				'sl' => $fromLanguage,
				'tl' => $toLanguage,
				'sp' => 'nmt',
				'tc' => 1,
				'sr' => 1,
				'tk' => $tk,
				'mode' => 1 
		];
		
		curl_setopt ( $this->ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8' );
		// curl post
		$curlurl = 'https://translate.googleapis.com/translate_a/t?' . http_build_query ( $args );
		$curlpost = $string_enc;
		curl_setopt ( $this->ch, CURLOPT_URL, $curlurl );
		curl_setopt ( $this->ch, CURLOPT_POST, true );
		curl_setopt ( $this->ch, CURLOPT_POSTFIELDS, $curlpost );
		curl_setopt ( $this->ch, CURLOPT_TIMEOUT, 20 );
		
		$x = 'error';
		$exec = curl_exec ( $this->ch );
		$x = curl_error ( $this->ch );
		
		if (trim ( $exec ) == '') {
			throw new Exception ( 'empty response from gtranslate ' . $x );
		}
		
		// Error 403
		if (strpos ( $exec, 'Error 403' )) {
			throw new Exception ( 'Google returned forbidden which means proxies may be needed on the plugin settings page' );
		}
		
		if (isset ( $_GET ['debug'] ))
			echo '<br><br>Return:' . $exec . $x;
		
		$json_result = json_decode ( $exec );
		
		if (! isset ( $json_result [0] )) {
			throw new Exception ( 'Can not get JSON from returned response' );
		}
		
		$returned_text_plain = implode ( '(*)', $json_result );
		$returned_text_plain = preg_replace ( '{<i>.*?</i>}s', '', $returned_text_plain );
		$returned_text_plain = str_replace ( array (
				'<b>',
				'</b>' 
		), '', $returned_text_plain );
		
		foreach ( $all_protected_matchs as $protected_term ) {
			$returned_text_plain = preg_replace ( '{\(\*\)}', $protected_term, $returned_text_plain, 1 );
		}
		
		// clean last glue if existing
		$translated = $returned_text_plain = str_replace ( '(*)', '', $returned_text_plain );
		
		if (isset ( $_GET ['debug'] ))
			echo '<br><br>Final trans:' . $translated;
		
		return $translated;
	}
	private function generateTkk() {
		$upload_dir = wp_upload_dir ();
		$cache = $upload_dir ['basedir'] . '/tkk.cache';
		if (file_exists ( $cache ) && filemtime ( $cache ) > strtotime ( 'now - 1 hour' )) {
			
			return file_get_contents ( $cache );
		}
		
		// curl get
		$x = 'error';
		$url = 'https://translate.googleapis.com/translate_a/element.js';
		curl_setopt ( $this->ch, CURLOPT_HTTPGET, 1 );
		curl_setopt ( $this->ch, CURLOPT_URL, trim ( $url ) );
		$data = curl_exec ( $this->ch );
		
		$response = $data;
		
		$pos1 = mb_strpos ( $response, 'c._ctkk=\'' ) + mb_strlen ( 'c._ctkk=\'' );
		$pos2 = mb_strpos ( $response, '\'', $pos1 );
		$tkk = mb_substr ( $response, $pos1, $pos2 - $pos1 );
		file_put_contents ( $cache, $tkk );
		return $tkk;
	}
	private function generateTk($f0, $w1) {
		// ported from js to php from https://translate.googleapis.com/element/TE_20200210_00/e/js/element/element_main.js
		$w1 = explode ( '.', $w1 );
		$n2 = $w1 [0];
		for($j3 = [ ], $t4 = 0, $h5 = 0; $h5 < strlen ( mb_convert_encoding ( $f0, 'UTF-16LE', 'UTF-8' ) ) / 2; $h5 ++) {
			$z6 = ord ( mb_convert_encoding ( $f0, 'UTF-16LE', 'UTF-8' ) [$h5 * 2] ) + (ord ( mb_convert_encoding ( $f0, 'UTF-16LE', 'UTF-8' ) [$h5 * 2 + 1] ) << 8);
			if (128 > $z6) {
				$j3 [$t4 ++] = $z6;
			} else {
				if (2048 > $z6) {
					$j3 [$t4 ++] = ($z6 >> 6) | 192;
				} else {
					if (55296 == ($z6 & 64512) && $h5 + 1 < strlen ( mb_convert_encoding ( $f0, 'UTF-16LE', 'UTF-8' ) ) / 2 && 56320 == ((ord ( mb_convert_encoding ( $f0, 'UTF-16LE', 'UTF-8' ) [($h5 + 1) * 2] ) + (ord ( mb_convert_encoding ( $f0, 'UTF-16LE', 'UTF-8' ) [($h5 + 1) * 2 + 1] ) << 8)) & 64512)) {
						$h5 ++;
						$z6 = 65536 + (($z6 & 1023) << 10) + ((ord ( mb_convert_encoding ( $f0, 'UTF-16LE', 'UTF-8' ) [$h5 * 2] ) + (ord ( mb_convert_encoding ( $f0, 'UTF-16LE', 'UTF-8' ) [$h5 * 2 + 1] ) << 8)) & 1023);
						$j3 [$t4 ++] = ($z6 >> 18) | 240;
						$j3 [$t4 ++] = (($z6 >> 12) & 63) | 128;
					} else {
						$j3 [$t4 ++] = ($z6 >> 12) | 224;
					}
					$j3 [$t4 ++] = (($z6 >> 6) & 63) | 128;
				}
				$j3 [$t4 ++] = ($z6 & 63) | 128;
			}
		}
		$f0 = $n2;
		for($t4 = 0; $t4 < count ( $j3 ); $t4 ++) {
			$f0 += $j3 [$t4];
			$c7 = $f0;
			$x8 = '+-a^+6';
			for($r9 = 0; $r9 < strlen ( $x8 ) - 2; $r9 += 3) {
				$u10 = $x8 [$r9 + 2];
				$u10 = 'a' <= $u10 ? ord ( $u10 [0] ) - 87 : intval ( $u10 );
				$a11 = $c7;
				$c12 = $u10;
				if ($c12 >= 32 || $c12 < - 32) {
					$c13 = ( int ) ($c12 / 32);
					$c12 = $c12 - $c13 * 32;
				}
				if ($c12 < 0) {
					$c12 = 32 + $c12;
				}
				if ($c12 == 0) {
					return (($a11 >> 1) & 0x7fffffff) * 2 + (($a11 >> $c12) & 1);
				}
				if ($a11 < 0) {
					$a11 = $a11 >> 1;
					$a11 &= 2147483647;
					$a11 |= 0x40000000;
					$a11 = $a11 >> $c12 - 1;
				} else {
					$a11 = $a11 >> $c12;
				}
				$b14 = $a11;
				$u10 = '+' == $x8 [$r9 + 1] ? $b14 : $c7 << $u10;
				$c7 = '+' == $x8 [$r9] ? ($c7 + $u10) & 4294967295 : $c7 ^ $u10;
			}
			$f0 = $c7;
		}
		$c7 = $f0;
		$x8 = '+-3^+b+-f';
		for($r9 = 0; $r9 < strlen ( $x8 ) - 2; $r9 += 3) {
			$u10 = $x8 [$r9 + 2];
			$u10 = 'a' <= $u10 ? ord ( $u10 [0] ) - 87 : intval ( $u10 );
			$a11 = $c7;
			$c12 = $u10;
			if ($c12 >= 32 || $c12 < - 32) {
				$c13 = ( int ) ($c12 / 32);
				$c12 = $c12 - $c13 * 32;
			}
			if ($c12 < 0) {
				$c12 = 32 + $c12;
			}
			if ($c12 == 0) {
				return (($a11 >> 1) & 0x7fffffff) * 2 + (($a11 >> $c12) & 1);
			}
			if ($a11 < 0) {
				$a11 = $a11 >> 1;
				$a11 &= 2147483647;
				$a11 |= 0x40000000;
				$a11 = $a11 >> $c12 - 1;
			} else {
				$a11 = $a11 >> $c12;
			}
			$b14 = $a11;
			$u10 = '+' == $x8 [$r9 + 1] ? $b14 : $c7 << $u10;
			$c7 = '+' == $x8 [$r9] ? ($c7 + $u10) & 4294967295 : $c7 ^ $u10;
		}
		$f0 = $c7;
		$f0 ^= $w1 [1] ? $w1 [1] + 0 : 0;
		if (0 > $f0) {
			$f0 = ($f0 & 2147483647) + 2147483648;
		}
		$f0 = fmod ( $f0, pow ( 10, 6 ) );
		return $f0 . '.' . ($f0 ^ $n2);
	}
	
	/**
	 * Deprecated since 28 May 2020 Google started to show Captcha
	 * Translate text using Google Post request to google translate
	 *
	 * @param unknown $sourceText
	 * @param unknown $fromLanguage
	 * @param unknown $toLanguage
	 * @return string translated text
	 */
	function old_translateText_1($sourceText, $fromLanguage, $toLanguage) {
		
		// saving the content to a temp file
		if (trim ( ini_get ( 'open_basedir' ) ) != '') {
			
			echo '<br>open_basedir exists';
			$upload_dir = wp_upload_dir ();
			$tmpFileUri = $upload_dir ['basedir'] . '/wp_automatic_tmp';
			$tmpHandle = fopen ( $tmpFileUri, "w+" );
			fwrite ( $tmpHandle, $sourceText );
		} else {
			
			$tmpHandle = tmpfile ();
			$metaDatas = stream_get_meta_data ( $tmpHandle );
			$tmpFileUri = $metaDatas ['uri'];
			
			fwrite ( $tmpHandle, $sourceText );
		}
		
		// translate file url
		curl_setopt ( $this->ch, CURLOPT_URL, "https://translate.googleusercontent.com/translate_f" );
		curl_setopt ( $this->ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $this->ch, CURLOPT_POST, true );
		
		if (class_exists ( 'CurlFile' )) {
			$curlFile = new \CurlFile ( $tmpFileUri, 'text/plain', 'test.txt' );
		} else {
			$curlFile = '@' . $tmpFileUri . ';type=text/plain;filename=test.txt';
		}
		
		$post = [ 
				'file' => $curlFile,
				'sl' => $fromLanguage,
				'tl' => $toLanguage,
				'js' => 'y',
				'prev' => '_t',
				'hl' => 'en',
				'ie' => 'UTF-8' 
		
		];
		
		curl_setopt ( $this->ch, CURLOPT_POSTFIELDS, $post );
		
		$headers = array ();
		$headers [] = "User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:49.0) Gecko/20100101 Firefox/49.0";
		$headers [] = "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.2878.95 Safari/537.36";
		
		$headers [] = "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
		$headers [] = "Accept-Language: en-US,en;q=0.5";
		$headers [] = "Referer: https://translate.google.com/?tr=f&hl=en";
		$headers [] = "Connection: keep-alive";
		$headers [] = "Upgrade-Insecure-Requests: 1";
		
		curl_setopt ( $this->ch, CURLOPT_HTTPHEADER, $headers );
		
		$exec = curl_exec ( $this->ch );
		
		$x = curl_error ( $this->ch );
		
		// close and delete temp file
		fclose ( $tmpHandle );
		
		// Empty response check
		if (trim ( $exec ) == '') {
			throw new Exception ( 'Empty translator reply with possible curl error ' . $x );
		}
		
		// Validate response result box
		if (stristr ( $exec, 'Error 403' )) {
			echo $exec;
			throw new Exception ( 'Error 403 from Google' );
		}
		
		// extra <pre removal fix
		$exec = str_replace ( '<pre>', '', $exec );
		$exec = str_replace ( '</pre>', '', $exec );
		
		return $exec;
	}
	function old_translateText_2($sourceText, $fromLanguage, $toLanguage) {
		
		// saving the content to a temp file
		$upload_dir = wp_upload_dir ();
		$tmpFileUri = $upload_dir ['basedir'] . '/wp_automatic_tmp.txt';
		$tmpHandle = fopen ( $tmpFileUri, "w+" );
		fwrite ( $tmpHandle, $sourceText );
		
		$translation_file_url = $upload_dir ['baseurl'] . '/wp_automatic_tmp.txt';
		
		echo '<br>Translation file URL' . $translation_file_url;
		
		// test on localhost
		// $translation_file_url = 'https://deandev.com/files/wp_automatic_tmp.txt';
		
		$url = "https://translate.google.com/translate?hl=en&ie=UTF8&prev=_t&sl=$fromLanguage&tl=$toLanguage&u=" . urlencode ( $translation_file_url );
		
		// Translate a url
		curl_setopt ( $this->ch, CURLOPT_HTTPGET, 1 );
		curl_setopt ( $this->ch, CURLOPT_URL, trim ( $url ) );
		
		$exec = curl_exec ( $this->ch );
		$x = curl_error ( $this->ch );
		
		// Extract iframe url _p
		preg_match ( '{(https://translate.googleusercontent.com.*?)"}', $exec, $translateUrls );
		$translateUrl = $translateUrls [1];
		
		// Validate _p url
		if (! stristr ( $translateUrl, '_p' )) {
			throw new Exception ( '_p url can not be extracted' );
		}
		
		// process _p url
		curl_setopt ( $this->ch, CURLOPT_HTTPGET, 1 );
		curl_setopt ( $this->ch, CURLOPT_URL, trim ( $translateUrl ) );
		
		$exec = curl_exec ( $this->ch );
		$x = curl_error ( $this->ch );
		
		// Extract _c url
		preg_match ( '{URL=(.*?)"}', $exec, $translateUrls2 );
		$translateUrl2 = html_entity_decode ( $translateUrls2 [1] );
		
		// Validate _c url
		if (! stristr ( $translateUrl2, '_c' )) {
			throw new Exception ( '_c url can not be extracted' );
		}
		
		// process _c url
		curl_setopt ( $this->ch, CURLOPT_HTTPGET, 1 );
		curl_setopt ( $this->ch, CURLOPT_URL, trim ( $translateUrl2 ) );
		$exec = curl_exec ( $this->ch );
		
		// validate final content
		if (trim ( $exec ) == '') {
			throw new Exception ( '_c url returned empty response' );
		}
		
		// clean content
		$exec = preg_replace ( '{<span class="google-src-text.*?>.*?</span>}', "", $exec );
		$exec = preg_replace ( '{<span class="notranslate.*?>(.*?)</span>}', "$1", $exec );
		$exec = str_replace ( ' style=";text-align:left;direction:ltr"', '', $exec );
		
		// Return result
		return $exec;
	}
}