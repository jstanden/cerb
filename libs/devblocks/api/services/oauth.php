<?php
class _DevblocksOAuthService {
	private $_consumer_key = null;
	private $_consumer_secret = null;
	private $_token = null;
	private $_token_secret = null;
	
	private $_signature_method = null;
	private $_response_info = null;
	
	public function __construct($consumer_key=null, $consumer_secret=null, $signature_method='HMAC-SHA1') {
		$this->_consumer_key = $consumer_key;
		$this->_consumer_secret = $consumer_secret;
		$this->_signature_method = $signature_method;
	}
	
	public function setTokens($token, $token_secret=null) {
		$this->_token = $token;
		$this->_token_secret = $token_secret;
	}
	
	public function getToken() {
		return $this->_token;
	}
	
	public function getResponseInfo() {
		return $this->_response_info;
	}
	
	public function getRequestTokens($request_url, $redirect_url) {
		$ch = DevblocksPlatform::curlInit($request_url);

		$oauth_headers = array(
			'oauth_callback' => $redirect_url,
			'oauth_consumer_key' => $this->_consumer_key,
			'oauth_nonce' => sha1(uniqid(null, true)),
			'oauth_signature_method' => $this->_signature_method,
			'oauth_timestamp' => time(),
			'oauth_version' => '1.0',
		);
		
		$oauth_headers = array_map('rawurlencode', $oauth_headers);
		
		curl_setopt($ch, CURLOPT_POST, true);
		
		switch($this->_signature_method) {
			case 'HMAC-SHA1':
				$signature = $this->getSignatureForHttpRequest('POST', $request_url, $oauth_headers);
				$oauth_headers['oauth_signature'] = $signature;
				break;
				
			case 'PLAINTEXT':
				$oauth_headers['realm'] = '';
				$oauth_headers['oauth_signature'] = sprintf('%s%s', $this->_consumer_secret, '&');
				break;
		}
		
		ksort($oauth_headers);
		
		$auth_header = 'OAuth ' . rawurldecode(implode(', ', array_map(function($v,$k) {
			return $k . '="' . rawurlencode($v) . '"'; 
		}, $oauth_headers, array_keys($oauth_headers))));
		
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Authorization: ' . $auth_header,
			'User-Agent: Cerb ' . APP_VERSION,
		));
		
		if(false == ($out = DevblocksPlatform::curlExec($ch))) {
			error_log(sprintf("cURL error: %s", curl_error($ch)));
			return false;
		}
		
		parse_str($out, $results);
		
		$this->_response_info = curl_getinfo($ch);
		
		curl_close($ch);
		
		return $results;
	}
	
	public function getAuthenticationURL($authorize_url, $request_token=null) {
		$url = $authorize_url;
		
		if($request_token)
			$url .= '?oauth_token=' . rawurlencode($request_token);
		
		return $url;
	}
	
	public function getAccessToken($access_token_url, $postdata=array(), $oauth_headers=array(), $accept=null) {
		if(!$this->_consumer_key || !$this->_consumer_secret || !$this->_token)
			return false;
		
		$ch = DevblocksPlatform::curlInit($access_token_url);
		
		$oauth_headers = array_merge($oauth_headers, array(
			'oauth_consumer_key' => $this->_consumer_key,
			'oauth_nonce' => sha1(uniqid(null, true)),
			'oauth_signature_method' => $this->_signature_method,
			'oauth_timestamp' => time(),
			'oauth_token' => $this->_token,
			'oauth_version' => '1.0',
		));
		
		$url_parts = parse_url($access_token_url);
		
		$base_url = sprintf('%s://%s%s%s',
			$url_parts['scheme'],
			$url_parts['host'],
			(isset($url_parts['port']) && !in_array(intval($url_parts['port']),array(0,80,443))) ? sprintf(':%d', $url_parts['port']) : '',
			$url_parts['path']
		);
		
		$query = array();
		
		if(isset($url_parts['query']))
			parse_str($url_parts['query'], $query);
		
		$oauth_headers = array_map('rawurlencode', $oauth_headers);
		$query = array_map('rawurlencode', $query);
		$postdata = array_map('rawurlencode', $postdata);
		
		$params = array_merge($oauth_headers, $query, $postdata);
		
		asort($params);
		ksort($params);
		
		switch($this->_signature_method) {
			case 'HMAC-SHA1':
				$signature = $this->getSignatureForHttpRequest('POST', $base_url, $params);
				$oauth_headers['oauth_signature'] = $signature;
				break;
				
			case 'PLAINTEXT':
				$oauth_headers['realm'] = '';
				$signature = sprintf('%s&', $this->_consumer_secret);
				$oauth_headers['oauth_signature'] = $signature;
				break;
		}
		
		
		ksort($oauth_headers);
		
		$auth_header = 'OAuth ' . rawurldecode(implode(', ', array_map(function($v,$k) {
			return $k . '="' . rawurlencode($v) . '"'; 
		}, $oauth_headers, array_keys($oauth_headers))));
		
		$http_headers = array(
			'Authorization: ' . $auth_header,
			'Content-Type: application/x-www-form-urlencoded',
			'User-Agent: Cerb ' . APP_VERSION,
		);
		
		if(!empty($accept))
			$http_headers[] = 'Accept: ' . $accept;
		
		curl_setopt($ch, CURLOPT_HTTPHEADER, $http_headers);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, urldecode(http_build_query($postdata)));
		
		if(false == ($out = DevblocksPlatform::curlExec($ch))) {
			error_log(sprintf("cURL error: %s", curl_error($ch)));
			return false;
		}
		
		$this->_response_info = curl_getinfo($ch);
		
		$content_type = $this->_response_info['content_type'];
		
		if(false !== strpos($content_type, ';'))
			@list($content_type, $content_type_opts) = explode(';', $content_type);
		
		$results = array();
		
		// Handle JSON or FORM responses
		switch(trim(strtolower($content_type))) {
			case 'application/json':
				$results = json_decode($out, true);
				break;
				
			case 'application/x-www-form-urlencoded':
			case 'text/html':
			case 'text/plain':
				parse_str($out, $results);
				break;
		}
		
		curl_close($ch);
		
		return $results;
	}
	
	public function executeRequestWithToken($method, $url, $postdata=array(), $token_type='OAuth') {
		if(!$this->_token)
			return false;
		
		$method = strtoupper($method);
		
		$ch = DevblocksPlatform::curlInit($url);
		
		$auth_header = sprintf('%s%s%s', $token_type, !empty($token_type) ? ' ' : '', $this->_token);
		
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Authorization: ' . $auth_header,
			'User-Agent: Cerb ' . APP_VERSION,
		));
		
		switch($method) {
			case 'GET':
				break;
				
			case 'POST':
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, urldecode(http_build_query($postdata)));
				break;
		}
		
		if(false == ($out = DevblocksPlatform::curlExec($ch))) {
			error_log(sprintf("cURL error: %s", curl_error($ch)));
			return false;
		}
		
		$this->_response_info = curl_getinfo($ch);
		
		$content_type = $this->_response_info['content_type'];
		
		if(false !== strpos($content_type, ';'))
			@list($content_type, $content_type_opts) = explode(';', $content_type);
		
		$results = array();
		
		// Handle JSON or FORM responses
		switch(trim(strtolower($content_type))) {
			case 'application/json':
				$results = json_decode($out, true);
				break;
				
			case 'application/x-www-form-urlencoded':
				parse_str($out, $results);
				break;
		}
		
		curl_close($ch);
		
		return $results;
	}
	
	public function getSignatureForHttpRequest($method, $url, $params) {
		$query_string = urldecode(http_build_query($params, '', '&'));
		
		$string_to_sign = sprintf('%s&%s&%s', strtoupper($method), rawurlencode($url), rawurlencode($query_string));
		
		$signing_key = sprintf("%s&%s",
			rawurlencode($this->_consumer_secret),
			($this->_token_secret ? rawurlencode($this->_token_secret) : '')
		);
		
		$signature = rawurlencode(base64_encode(hash_hmac('sha1', $string_to_sign, $signing_key, true)));
		
		return $signature;
	}
	
	private function _getContentTypeFromHeaders($headers) {
		if(!is_array($headers))
			return false;
		
		foreach($headers as $header) {
			list($k, $v) = explode(':', $header, 2);
			
			if(0 == strcasecmp($k, 'Content-Type')) {
				@list($content_type, $encoding) = explode(';', $v);
				return trim(strtolower($content_type));
			}
		}
		
		return null;
	}
	
	// Redundant with executeRequest()
	public function authenticateHttpRequest(&$ch, &$verb, &$url, &$body, &$headers) {
		if(!$this->_consumer_key || !$this->_consumer_secret || !$this->_token)
			return false;

		$payload = null;
		$postdata = $body;

		if(!is_array($postdata)) {
			switch($this->_getContentTypeFromHeaders($headers)) {
				// Decode pre-encoded form params for signing
				case 'application/x-www-form-urlencoded':
					$postdata = array();
					parse_str($body, $postdata);
					break;
				
				// Otherwise, keep the plaintext as a payload
				default:
					$payload = $postdata;
					$postdata = array();
					break;
			}
		}
		
		$method = strtoupper($verb);
		
		$oauth_headers = array(
			'oauth_consumer_key' => $this->_consumer_key,
			'oauth_nonce' => sha1(uniqid(null, true)),
			'oauth_signature_method' => $this->_signature_method,
			'oauth_timestamp' => time(),
			'oauth_token' => $this->_token,
			'oauth_version' => '1.0',
		);
		
		$url_parts = parse_url($url);
		
		$base_url = sprintf('%s://%s%s%s',
			$url_parts['scheme'],
			$url_parts['host'],
			(isset($url_parts['port']) && !in_array(intval($url_parts['port']),array(0,80,443))) ? sprintf(':%d', $url_parts['port']) : '',
			$url_parts['path']
		);
		
		$query = array();
		
		if(isset($url_parts['query']))
			parse_str($url_parts['query'], $query);
		
		$oauth_headers = array_map('rawurlencode', $oauth_headers);
		$query = array_map('rawurlencode', $query);
		$postdata = array_map('rawurlencode', $postdata);
		
		$params = array_merge($oauth_headers, $query, $postdata);
		
		asort($params);
		ksort($params);
		
		switch($this->_signature_method) {
			case 'HMAC-SHA1':
				$signature = $this->getSignatureForHttpRequest($method, $base_url, $params);
				$oauth_headers['oauth_signature'] = $signature;
				break;
				
			case 'PLAINTEXT':
				$signature = sprintf('%s&%s', $this->_consumer_secret, $this->_token_secret);
				$oauth_headers['oauth_signature'] = $signature;
				$oauth_headers['realm'] = '';
				break;
		}
		
		ksort($oauth_headers);
		
		$auth_header = 'OAuth ' . rawurldecode(implode(', ', array_map(function($v, $k) {
			return $k . '="' . rawurlencode($v) . '"'; 
		}, $oauth_headers, array_keys($oauth_headers))));
		
		$headers[] = 'Authorization: ' . $auth_header;
		return true;
	}
	
	public function executeRequest($method, $url, $postdata=array()) {
		if(!$this->_consumer_key || !$this->_consumer_secret || !$this->_token)
			return false;
		
		$payload = null;
		
		if(!is_array($postdata)) {
			$payload = $postdata;
			$postdata = array();
		}
		
		$method = strtoupper($method);
		
		$ch = DevblocksPlatform::curlInit($url);
		
		$oauth_headers = array(
			'oauth_consumer_key' => $this->_consumer_key,
			'oauth_nonce' => sha1(uniqid(null, true)),
			'oauth_signature_method' => $this->_signature_method,
			'oauth_timestamp' => time(),
			'oauth_token' => $this->_token,
			'oauth_version' => '1.0',
		);
		
		$url_parts = parse_url($url);
		
		$base_url = sprintf('%s://%s%s%s',
			$url_parts['scheme'],
			$url_parts['host'],
			(isset($url_parts['port']) && !in_array(intval($url_parts['port']),array(0,80,443))) ? sprintf(':%d', $url_parts['port']) : '',
			$url_parts['path']
		);
		
		$query = array();
		
		// [TODO] Merge payload
		
		if(isset($url_parts['query']))
			parse_str($url_parts['query'], $query);
		
		$oauth_headers = array_map('rawurlencode', $oauth_headers);
		$query = array_map('rawurlencode', $query);
		$postdata = array_map('rawurlencode', $postdata);
		
		$params = array_merge($oauth_headers, $query, $postdata);
		
		asort($params);
		ksort($params);
		
		switch($this->_signature_method) {
			case 'HMAC-SHA1':
				$signature = $this->getSignatureForHttpRequest($method, $base_url, $params);
				$oauth_headers['oauth_signature'] = $signature;
				break;
				
			case 'PLAINTEXT':
				$signature = sprintf('%s&%s', $this->_consumer_secret, $this->_token_secret);
				$oauth_headers['oauth_signature'] = $signature;
				$oauth_headers['realm'] = '';
				break;
		}
		
		ksort($oauth_headers);
		
		$auth_header = 'OAuth ' . rawurldecode(implode(', ', array_map(function($v,$k) {
			return $k . '="' . rawurlencode($v) . '"'; 
		}, $oauth_headers, array_keys($oauth_headers))));
		
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Authorization: ' . $auth_header,
			'User-Agent: Cerb ' . APP_VERSION,
		));
		
		switch($method) {
			case 'GET':
				break;
				
			case 'POST':
				curl_setopt($ch, CURLOPT_POST, true);
				
				if(!is_null($payload)) {
					curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
				} else {
					curl_setopt($ch, CURLOPT_POSTFIELDS, urldecode(http_build_query($postdata)));
				}
				break;
		}
		
		if(false == ($out = DevblocksPlatform::curlExec($ch))) {
			error_log(sprintf("cURL error: %s", curl_error($ch)));
			return false;
		}
		
		$this->_response_info = curl_getinfo($ch);
		
		curl_close($ch);
		
		return $out;
	}
}