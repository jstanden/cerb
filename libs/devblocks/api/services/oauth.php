<?php
class _DevblocksOAuthService {
	private $_consumer_key = null;
	private $_consumer_secret = null;
	private $_token = null;
	private $_token_secret = null;
	
	private $_response_info = null;
	
	public function __construct($consumer_key=null, $consumer_secret=null) {
		$this->_consumer_key = $consumer_key;
		$this->_consumer_secret = $consumer_secret;
	}
	
	public function setTokens($token, $token_secret=null) {
		$this->_token = $token;
		$this->_token_secret = $token_secret;
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
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_timestamp' => time(),
			'oauth_version' => '1.0',
		);
		
		$oauth_headers = array_map('rawurlencode', $oauth_headers);
		
		$signature = $this->_getSignature('POST', $request_url, $oauth_headers);
		
		$oauth_headers['oauth_signature'] = $signature;
		
		ksort($oauth_headers);
		
		$auth_header = 'OAuth ' . urldecode(http_build_query($oauth_headers, '', ', '));
		
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: ' . $auth_header));
		
		curl_setopt($ch, CURLOPT_POST, true);
		
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
	
	public function getAccessToken($access_token_url, $postdata=array()) {
		if(!$this->_consumer_key || !$this->_consumer_secret || !$this->_token)
			return false;
		
		$ch = DevblocksPlatform::curlInit($access_token_url);
		
		$oauth_headers = array(
			'oauth_consumer_key' => $this->_consumer_key,
			'oauth_nonce' => sha1(uniqid(null, true)),
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_timestamp' => time(),
			'oauth_token' => $this->_token,
			'oauth_version' => '1.0',
		);
		
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
		
		$signature = $this->_getSignature('POST', $base_url, $params);
		
		$oauth_headers['oauth_signature'] = $signature;
		
		ksort($oauth_headers);
		
		$auth_header = 'OAuth ' . urldecode(http_build_query($oauth_headers, '', ', '));
		
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Authorization: ' . $auth_header,
			'Content-Type: application/x-www-form-urlencoded',
		));
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, urldecode(http_build_query($postdata)));
		
		if(false == ($out = DevblocksPlatform::curlExec($ch))) {
			error_log(sprintf("cURL error: %s", curl_error($ch)));
			return false;
		}
		
		parse_str($out, $results);
		
		$this->_response_info = curl_getinfo($ch);
		
		curl_close($ch);
		
		return $results;
	}
	
	private function _getSignature($method, $base_url, $params) {
		$query_string = urldecode(http_build_query($params, '', '&'));
		
		$string_to_sign = sprintf('%s&%s&%s', strtoupper($method), rawurlencode($base_url), rawurlencode($query_string));
		
		$signing_key = sprintf("%s&%s",
			rawurlencode($this->_consumer_secret),
			($this->_token_secret ? rawurlencode($this->_token_secret) : '')
		);
		
		$signature = rawurlencode(base64_encode(hash_hmac('sha1', $string_to_sign, $signing_key, true)));
		
		return $signature;
	}
	
	public function executeRequestWithToken($method, $url, $postdata=array()) {
		if(!$this->_token)
			return false;
		
		$method = strtoupper($method);
		
		$ch = DevblocksPlatform::curlInit($url);
		
		$auth_header = 'OAuth ' . rawurlencode($this->_token);
		
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Authorization: ' . $auth_header,
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
		
		curl_close($ch);
		
		return $out;
	}
	
	public function executeRequest($method, $url, $postdata=array()) {
		if(!$this->_consumer_key || !$this->_consumer_secret || !$this->_token)
			return false;
		
		$method = strtoupper($method);
		
		$ch = DevblocksPlatform::curlInit($url);
		
		$oauth_headers = array(
			'oauth_consumer_key' => $this->_consumer_key,
			'oauth_nonce' => sha1(uniqid(null, true)),
			'oauth_signature_method' => 'HMAC-SHA1',
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
		
		$signature = $this->_getSignature($method, $base_url, $params);
		
		$oauth_headers['oauth_signature'] = $signature;
		
		ksort($oauth_headers);
		
		$auth_header = 'OAuth ' . urldecode(http_build_query($oauth_headers, '', ', '));
		
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Authorization: ' . $auth_header,
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
		
		curl_close($ch);
		
		return $out;
	}
}