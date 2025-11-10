<?php

if ( !class_exists('Puc_v4p9_OAuthSignature', false) ):

	/**
	 * A basic signature generator for zero-legged OAuth 1.0.
	 */
	class Puc_v4p9_OAuthSignature {
		private $consumerKey = '';
		private $consumerSecret = '';

		public function __construct($consumerKey, $consumerSecret) {
			$this->consumerKey = $consumerKey;
			$this->consumerSecret = $consumerSecret;
		}

		/**
		 * Sign a URL using OAuth 1.0.
		 *
		 * @param string $url The URL to be signed. It may contain query parameters.
		 * @param string $method HTTP method such as "GET", "POST" and so on.
		 * @return string The signed URL.
		 */
		public function sign($url, $method = 'GET') {
			$parameters = array();

			//Parse query parameters.
			$query = parse_url($url, PHP_URL_QUERY);
			if ( !empty($query) ) {
				parse_str($query, $parsedParams);
				if ( is_array($parameters) ) {
					$parameters = $parsedParams;
				}
				//Remove the query string from the URL. We'll replace it later.
				$url = substr($url, 0, strpos($url, '?'));
			}

			$parameters = array_merge(
				$parameters,
				array(
					'oauth_consumer_key' => $this->consumerKey,
					'oauth_nonce' => $this->nonce(),
					'oauth_signature_method' => 'HMAC-SHA1',
					'oauth_timestamp' => time(),
					'oauth_version' => '1.0',
				)
			);
			unset($parameters['oauth_signature']);

			//Parameters must be sorted alphabetically before signing.
			ksort($parameters);

			//The most complicated part of the request - generating the signature.
			//The string to sign contains the HTTP method, the URL path, and all of
			//our query parameters. Everything is URL encoded. Then we concatenate
			//them with ampersands into a single string to hash.
			$encodedVerb = urlencode($method);
			$encodedUrl = urlencode($url);
			$encodedParams = urlencode(http_build_query($parameters, '', '&'));

			$stringToSign = $encodedVerb . '&' . $encodedUrl . '&' . $encodedParams;

			//Since we only have one OAuth token (the consumer secret) we only have
			//to use it as our HMAC key. However, we still have to append an & to it
			//as if we were using it with additional tokens.
			$secret = urlencode($this->consumerSecret) . '&';

			//The signature is a hash of the consumer key and the base string. Note
			//that we have to get the raw output from hash_hmac and base64 encode
			//the binary data result.
			$parameters['oauth_signature'] = base64_encode(hash_hmac('sha1', $stringToSign, $secret, true));

			return ($url . '?' . http_build_query($parameters));
		}

		/**
		 * Generate a random nonce.
		 *
		 * @return string
		 */
		private function nonce() {
			$mt = microtime();

			$rand = null;
			if ( is_callable('random_bytes') ) {
				try {
					$rand = random_bytes(16);
				} catch (Exception $ex) {
					//Fall back to mt_rand (below).
				}
			}
			if ( $rand === null ) {
				$rand = mt_rand();
			}

			return md5($mt . '_' . $rand);
		}
	}

endif;
