<?php
class _DevblocksOAuthService {
	private static $_instance = null;
	
	static function getInstance() {
		if(is_null(self::$_instance))
			self::$_instance = new _DevblocksOAuthService();
		
		return self::$_instance;
	}
	
	private function __construct() {}
	
	function getOAuth1Client($consumer_key=null, $consumer_secret=null, $signature_method='HMAC-SHA1') {
		return new _DevblocksOAuth1Client($consumer_key, $consumer_secret, $signature_method);
	}
	
	function getServerPrivateKeyPath() {
		return APP_STORAGE_PATH . '/keys/oauth2-server.key';
	}
	
	function getServerPrivateKey() {
		$key_file = $this->getServerPrivateKeyPath();
		
		if(!file_exists($key_file))
			$this->_generateAndSaveKeys();
		
		return new \League\OAuth2\Server\CryptKey($key_file);
	}
	
	function getServerPublicKeyPath() {
		return APP_STORAGE_PATH . '/keys/oauth2-server.pub';
	}
	
	function getServerPublicKey() {
		$key_file = $this->getServerPublicKeyPath();
		
		if(!file_exists($key_file))
			$this->_generateAndSaveKeys();
		
		return new \League\OAuth2\Server\CryptKey($key_file);
	}
	
	private function _generateAndSaveKeys($key_options = ['digest_alg' => 'sha512', 'private_key_bits' => 4096, 'private_key_type' => OPENSSL_KEYTYPE_RSA]) {
		$key_path = APP_STORAGE_PATH . '/keys/';
		$key_pair = DevblocksPlatform::services()->encryption()->generateRsaKeyPair($key_options);
		
		if(!file_exists($key_path))
			mkdir($key_path, 0770);
		
		$public_key_file = $key_path . 'oauth2-server.pub';
		$private_key_file = $key_path . 'oauth2-server.key';
		
		file_put_contents($private_key_file, $key_pair['private']);
		file_put_contents($public_key_file, $key_pair['public']);
		chmod($private_key_file, 0660);
		chmod($public_key_file, 0660);
	}
}

class _DevblocksOAuth1Client {
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
			'Content-Length: ' . 0, // This shouldn't be required, but some IdPs are looking for it
			'User-Agent: Cerb ' . APP_VERSION,
		));
		
		if(false == ($out = DevblocksPlatform::curlExec($ch))) {
			error_log(sprintf("cURL error: %s", curl_error($ch)));
			return false;
		}
		
		$results = DevblocksPlatform::strParseQueryString($out);
		
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
	
	public function getRefreshToken($refresh_token_url, $postdata=[]) {
		if(!$this->_consumer_key || !$this->_consumer_secret)
			return false;
		
		$ch = DevblocksPlatform::curlInit($refresh_token_url);
		
		$url_parts = parse_url($refresh_token_url);
		
		$query = [];
		
		if(isset($url_parts['query']))
			$query = DevblocksPlatform::strParseQueryString($url_parts['query']);
		
		$query = array_map('rawurlencode', $query);
		$postdata = array_map('rawurlencode', $postdata);
		
		$params = array_merge($query, $postdata);

		$http_headers = array(
			'Content-Type: application/x-www-form-urlencoded',
			'User-Agent: Cerb ' . APP_VERSION,
		);
		
		curl_setopt($ch, CURLOPT_HTTPHEADER, $http_headers);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, urldecode(http_build_query($params)));
		
		if(false == ($out = DevblocksPlatform::curlExec($ch))) {
			error_log(sprintf("cURL error: %s", curl_error($ch)));
			return false;
		}
		
		$this->_response_info = curl_getinfo($ch);
		
		$content_type = $this->_response_info['content_type'];
		
		if(false !== strpos($content_type, ';'))
			@list($content_type,) = explode(';', $content_type);
		
		$results = array();
		
		// Handle JSON or FORM responses
		switch(trim(DevblocksPlatform::strLower($content_type))) {
			case 'application/json':
				$results = json_decode($out, true);
				break;
				
			case 'application/x-www-form-urlencoded':
			case 'text/html':
			case 'text/plain':
				$results = DevblocksPlatform::strParseQueryString($out);
				break;
		}
		
		curl_close($ch);
		
		return $results;
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
		
		$query = [];
		
		if(isset($url_parts['query']))
			$query = DevblocksPlatform::strParseQueryString($url_parts['query']);
		
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
			@list($content_type,) = explode(';', $content_type);
		
		$results = array();
		
		// Handle JSON or FORM responses
		switch(trim(DevblocksPlatform::strLower($content_type))) {
			case 'application/json':
				$results = json_decode($out, true);
				break;
				
			case 'application/x-www-form-urlencoded':
			case 'text/html':
			case 'text/plain':
				$results = DevblocksPlatform::strParseQueryString($out);
				break;
		}
		
		curl_close($ch);
		
		return $results;
	}
	
	public function executeRequestWithToken($method, $url, $postdata=array(), $token_type='OAuth') {
		if(!$this->_token)
			return false;
		
		$method = DevblocksPlatform::strUpper($method);
		
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
			@list($content_type,) = explode(';', $content_type);
		
		$results = array();
		
		// Handle JSON or FORM responses
		switch(trim(DevblocksPlatform::strLower($content_type))) {
			case 'application/json':
				$results = json_decode($out, true);
				break;
				
			case 'application/x-www-form-urlencoded':
				$results = DevblocksPlatform::strParseQueryString($out);
				break;
		}
		
		curl_close($ch);
		
		return $results;
	}
	
	public function getSignatureForHttpRequest($method, $url, $params) {
		$query_string = urldecode(http_build_query($params, '', '&'));
		
		$string_to_sign = sprintf('%s&%s&%s', DevblocksPlatform::strUpper($method), rawurlencode($url), rawurlencode($query_string));
		
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
		
		foreach($headers as $k => $v) {
			if(0 == strcasecmp($k, 'Content-Type')) {
				$v = implode('; ', $v);
				@list($content_type,) = explode(';', $v);
				return trim(DevblocksPlatform::strLower($content_type));
			}
		}
		
		return null;
	}
	
	// Redundant with executeRequest()
	public function authenticateHttpRequest(Psr\Http\Message\RequestInterface &$request, &$options = []) : bool {
		if(!$this->_consumer_key || !$this->_consumer_secret || !$this->_token)
			return false;
		
		$method = DevblocksPlatform::strUpper($request->getMethod());
		$headers = $request->getHeaders();
		$body = $request->getBody()->getContents();
		
		if(!is_array($body)) {
			switch($this->_getContentTypeFromHeaders($headers)) {
				// Decode pre-encoded form params for signing
				case 'application/x-www-form-urlencoded':
					$postdata = DevblocksPlatform::strParseQueryString($body);
					break;
				
				// Otherwise, keep the plaintext as a payload
				default:
					$postdata = [];
					break;
			}
		}
		
		$oauth_headers = [
			'oauth_consumer_key' => $this->_consumer_key,
			'oauth_nonce' => sha1(uniqid(null, true)),
			'oauth_signature_method' => $this->_signature_method,
			'oauth_timestamp' => time(),
			'oauth_token' => $this->_token,
			'oauth_version' => '1.0',
		];
		
		$port = $request->getUri()->getPort();
		
		$base_url = sprintf('%s://%s%s%s',
			$request->getUri()->getScheme(),
			$request->getUri()->getHost(),
			($port && !in_array(intval($port),array(0,80,443))) ? sprintf(':%d', $port) : '',
			$request->getUri()->getPath()
		);
		
		$query = [];
		
		if($request->getUri()->getQuery())
			$query = DevblocksPlatform::strParseQueryString($request->getUri()->getQuery());
		
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
		
		$request = $request
			->withHeader('Authorization', $auth_header)
			;
		
		return true;
	}
	
	public function executeRequest($method, $url, $postdata=array()) {
		if(!$this->_consumer_key || !$this->_consumer_secret || !$this->_token)
			return false;
		
		$payload = null;
		
		if(!is_array($postdata)) {
			$payload = $postdata;
			$postdata = [];
		}
		
		$method = DevblocksPlatform::strUpper($method);
		
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
		
		if(isset($url_parts['query']))
			$query = DevblocksPlatform::strParseQueryString($url_parts['query']);
		
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