<?php
class DevblocksStorageEngineGatekeeper extends Extension_DevblocksStorageEngine {
	const ID = 'devblocks.storage.engine.gatekeeper';
	
	private $_data = null;

	public function setOptions($options=array()) {
		parent::setOptions($options);

		// Fail, this info is required.
		if(!isset($this->_options['username']))
			return false;
		if(!isset($this->_options['password']))
			return false;
		if(!isset($this->_options['url']))
			return false;
	}

	function testConfig(Model_DevblocksStorageProfile $profile) {
		// Test S3 connection info
		$username = DevblocksPlatform::importGPC($_POST['username'] ?? null, 'string','');
		$password = DevblocksPlatform::importGPC($_POST['password'] ?? null, 'string','');
		$url = DevblocksPlatform::importGPC($_POST['url'] ?? null, 'string','');
		$bucket = DevblocksPlatform::importGPC($_POST['bucket'] ?? null, 'string','');
		$path_prefix = DevblocksPlatform::importGPC($_POST['path_prefix'] ?? null, 'string','');
		
		if(empty($url) || empty($username) || empty($password))
			return false;

		$path_prefix =
			0 == strlen(trim($path_prefix, '/'))
			? ''
			: (trim($path_prefix, '/') . '/')
			;
		
		$key = $path_prefix . '.cerb_test';

		// PUT
		
		$headers = array(
			//'Date' => gmdate(DATE_RFC2822),
			'Content-Type' => 'text/plain',
		);
		
		if(false == ($signed_url = $this->_getSignedURL($username, $password, $url, 'PUT', $key, $headers)))
			return false;

		if(false == $this->_execute($signed_url, 'PUT', 'CERB', $headers))
			return false;

		// GET
		
		if(false == ($signed_url = $this->_getSignedURL($username, $password, $url, 'GET', $key)))
			return false;

		if(false == $this->_execute($signed_url, 'GET'))
			return false;
		
		// DELETE
		
		if(false == ($signed_url = $this->_getSignedURL($username, $password, $url, 'DELETE', $key)))
			return false;

		if(false == $this->_execute($signed_url, 'DELETE'))
			return false;
		
		return true;
	}

	function renderConfig(Model_DevblocksStorageProfile $profile) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('profile', $profile);

		$tpl->display("devblocks:wgm.storage.s3.gatekeeper::storage_engine/config/gatekeeper.tpl");
	}

	function saveConfig(Model_DevblocksStorageProfile $profile) {
		$username = DevblocksPlatform::importGPC($_POST['username'] ?? null, 'string','');
		$password = DevblocksPlatform::importGPC($_POST['password'] ?? null, 'string','');
		$url = DevblocksPlatform::importGPC($_POST['url'] ?? null, 'string', '');
		$bucket = DevblocksPlatform::importGPC($_POST['bucket'] ?? null, 'string', '');
		$path_prefix = DevblocksPlatform::importGPC($_POST['path_prefix'] ?? null, 'string', '');

		$path_prefix =
			0 == strlen(trim($path_prefix, '/'))
			? ''
			: (trim($path_prefix, '/') . '/')
			;
		
		$fields = array(
		DAO_DevblocksStorageProfile::PARAMS_JSON => json_encode(array(
				'username' => $username,
				'password' => $password,
				'url' => $url,
				'bucket' => $bucket,
				'path_prefix' => $path_prefix,
			)),
		);

		DAO_DevblocksStorageProfile::update($profile->id, $fields);
	}

	public function exists($namespace, $key) {
		//$bucket = $this->_options['bucket'] ?? null;
		$path = $this->_options['path_prefix'] . $this->escapeNamespace($namespace) . '/' . $key;
		
		if(false == ($url = $this->_getSignedURL($this->_options['username'], $this->_options['password'], $this->_options['url'], 'GET', $path)))
			return false;
		
		return true;
	}
	
	public function put($namespace, $id, $data, $length = null) {
		//$bucket = $this->_options['bucket'] ?? null;
		
		// Get a unique hash path for this namespace+id
		$hash = base_convert(sha1($this->escapeNamespace($namespace).$id), 16, 32);
		
		$key = sprintf("%s/%s/%d",
			substr($hash,0,1),
			substr($hash,1,1),
			$id
		);
		
		// [TODO] This should store the real Content-Type
		$headers = array(
			'Content-Type' => 'application/octet-stream',
		);
		
		$path = $this->_options['path_prefix'] . $this->escapeNamespace($namespace) . '/' . $key;
	
		if(false == ($url = $this->_getSignedURL($this->_options['username'], $this->_options['password'], $this->_options['url'], 'PUT', $path, $headers)))
			return false;
		
		if(false == ($this->_execute($url, 'PUT', $data, $headers)))
			return false;
	
		return $key;
	}
	
	public function get($namespace, $key, &$fp=null) {
		//$bucket = $this->_options['bucket'] ?? null;
		$path = $this->_options['path_prefix'] . $this->escapeNamespace($namespace) . '/' . $key;
		
		if(false == ($url = $this->_getSignedURL($this->_options['username'], $this->_options['password'], $this->_options['url'], 'GET', $path)))
			return false;
		
		if($fp && is_resource($fp)) {
			// [TODO] Make this work with streams
			if(false == ($data = $this->_execute($url, 'GET')))
				return false;
				
			//$tmpfile = DevblocksPlatform::getTempFileInfo($fp);
			//file_put_contents($tmpfile, $data);
			fputs($fp, $data, strlen($data));
			fseek($fp, 0);
			return TRUE;
				
		} else {
			if(false == ($data = $this->_execute($url, 'GET')))
				return false;
				
			return $data;
		}
	
		return false;
	}
	
	public function delete($namespace, $key) {
		//$bucket = $this->_options['bucket'] ?? null;
		//$path = $this->_options['path_prefix'] . $this->escapeNamespace($namespace) . '/' . $key;
	
		/*
		// [TODO] Fail gracefully if resource doesn't exist (pass)
		if(false ==  ($url = $this->_getSignedURL($this->_options['username'], $this->_options['password'], $this->_options['url'], 'DELETE', $path)))
			return false;
	
		if(false == ($data = $this->_execute($url, 'DELETE')))
			return false;
		*/
		
		// Queue up batch DELETEs
		$profile_id = isset($this->_options['_profile_id']) ? $this->_options['_profile_id'] : 0;
		DAO_DevblocksStorageQueue::enqueueDelete($namespace, $key, $this->manifest->id, $profile_id);
	
		return TRUE;
	}
	
	public function batchDelete($namespace, $keys) {
		$bucket = $this->_options['bucket'] ?? null;
		$ns = $this->escapeNamespace($namespace);
		$path_prefix = $this->_options['path_prefix'];
		
		$paths = array_map(function($e) use ($ns, $bucket, $path_prefix) {
			return $path_prefix . $ns . '/' . $e;
		}, $keys);

		$dom = new DOMDocument();
		$dom->formatOutput = true;
		
		$node_delete = $dom->createElement('Delete');
		
		$node_quiet = $dom->createElement('Quiet', 'true');
		$node_delete->appendChild($node_quiet);
		
		if(is_array($paths))
		foreach($paths as $path) {
			$node_object = $dom->createElement('Object');
			
			$node_key = $dom->createElement('Key', $path);
			$node_object->appendChild($node_key);
			
			$node_delete->appendChild($node_object);
		}
		
		$dom->appendChild($node_delete);
		
		$data = $dom->saveXML();
		
		$http_date = gmdate('D, d M Y H:i:s') . ' GMT';
		
		$headers = array(
			'Content-MD5' => base64_encode(md5($data, true)),
			'Content-Type' => 'application/xml',
			'Date' => $http_date,
		);
		
		if(false == ($url = $this->_getSignedURL($this->_options['username'], $this->_options['password'], $this->_options['url'], 'POST', '?delete', $headers)))
			return false;

		$parsed = parse_url($url);
		$query_str = $parsed['query'];
		$query = DevblocksPlatform::strParseQueryString($query_str);
		
		$headers['Authorization'] = sprintf("AWS %s:%s",
			$query['AWSAccessKeyId'],
			$query['Signature']
		);
		
		$url = sprintf("http://s3.amazonaws.com/%s/?delete",
			$bucket
		);
		
		if(false === ($result = $this->_execute($url, 'POST', $data, $headers)))
			return false;
		
		// Handle the case where some fail to delete (e.g. AccessDenied)
		
		$xml = simplexml_load_string($result);
		
		$errors = array();
		
		if(isset($xml->Error))
		foreach($xml->Error as $error) {
			$errors[] = str_replace($path_prefix . $ns . '/', '', $error->Key);
		}
		
		// Return the keys that were actually deleted, ignoring any errors
		return array_diff($keys, $errors);
	}
	
	private function _getSignedURL($username, $password, $url, $verb = 'GET', $key = null, $headers = array()) {
		$logger = DevblocksPlatform::services()->log();
		
		$header = array();
		$ch = DevblocksPlatform::curlInit();
		
		if(!isset($headers['Date']))
			$headers['Date'] = gmdate('D, d M Y H:i:s') . ' GMT';
		
		$payload = array('json' => json_encode(array(
			'verb' => $verb,
			'key' => $key,
			'headers' => $headers,
		)));
		
		$header[] = 'Date: ' . $headers['Date'];
		$header[] = 'Content-Type: application/x-www-form-urlencoded; charset=utf-8';
		
		$postfields = '';
		
		if(!is_null($payload)) {
			if(is_array($payload)) {
				foreach($payload as $key => $value) {
					$postfields .= $key.'='.rawurlencode($value) . '&';
				}
				rtrim($postfields,'&');
				
			} elseif (is_string($payload)) {
				$postfields = $payload;
			}
		}
		
		$header[] = 'Content-Length: ' .  strlen($postfields);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
		
		// Authentication
		$url_parts = parse_url($url);
		$url_path = $url_parts['path'];
		
		$url_query = '';
		if(isset($url_parts['query']) && !empty($url_parts))
			$url_query = $this->_sortQueryString($url_parts['query']);
		
		$secret = DevblocksPlatform::strLower(md5($password));
		
		// Hardcoded as POST because we will only ever POST to the gatekeeper script
		$string_to_sign = sprintf("%s\n%s\n%s\n%s\n%s\n%s\n",
			'POST',
			$headers['Date'],
			$url_path,
			$url_query,
			$postfields,
			$secret
		);
		$hash = md5($string_to_sign); // base64_encode(sha1(
		$header[] = 'Cerb-Auth: '.sprintf("%s:%s",$username,$hash);
		
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		
		$output = DevblocksPlatform::curlExec($ch);
		
		// Check status code
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if(2 != substr($status,0,1)) {
			$logger->error(sprintf("[Storage] Error connecting to Gatekeeper: %s", $status));
			return false;
		}
		
		// Parse output
		$output = json_decode($output, true);
		
		if(!is_array($output) || empty($output) || $output['__status'] == 'error') {
			$logger->error(sprintf('Error connecting to Gatekeeper: %s', $output['message']));
			return false;
			
		} else {
			$output = $output['message'];
		}
		
		return $output;
	}
	
	// [TODO] Make this streaming safe
	private function _execute($url, $verb = 'GET', $data = null, $headers = array()) {
		$logger = DevblocksPlatform::services()->log();
		
		try {
			$ch = DevblocksPlatform::curlInit($url);
			$http_date = gmdate(DATE_RFC2822);
	
			//curl_setopt($ch, CURLOPT_VERBOSE, true);
			//curl_setopt($ch, CURLINFO_HEADER_OUT, true);
	
			if(is_resource($data)) {
				$stat = fstat($data);
				$length = $stat['size'];
				
				$this->_data = $data;
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
				curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
				curl_setopt($ch, CURLOPT_LOW_SPEED_LIMIT, 1);
				curl_setopt($ch, CURLOPT_LOW_SPEED_TIME, 180);
				curl_setopt($ch, CURLOPT_READFUNCTION, array($this, 'streamData'));
				curl_setopt($ch, CURLOPT_UPLOAD, true);
				curl_setopt($ch, CURLINFO_CONTENT_LENGTH_UPLOAD, $length);
				
			} else {
				$length = strlen($data);
				
				switch($verb) {
					case 'GET':
						break;
						
					case 'POST':
					case 'PUT':
						curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $verb);
						curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
						break;
						
					case 'DELETE':
						curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
						break;
				}
			}
			
			if(isset($headers['Date']))
				$http_date = $headers['Date'];
			
			$header[] = 'Date: ' . $http_date;

			if(isset($headers['Authorization']))
				$header[] = 'Authorization: ' . $headers['Authorization'];
			
			if(isset($headers['Content-MD5']))
				$header[] = 'Content-MD5: ' . $headers['Content-MD5'];
			
			if(isset($headers['Content-Type']))
				$header[] = 'Content-Type: ' . $headers['Content-Type'];
			
			if(!empty($length))
				$header[] = 'Content-Length: '. $length;
			
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			$response = DevblocksPlatform::curlExec($ch);
			
			// Check status code
			$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			
			if(2 != substr($status,0,1)) {
				$logger->error(sprintf('[Storage] Error connecting to remote URL: %s %s - %s', $verb, $url, $status));
				return false;
			}
	
			curl_close($ch);
			
		} catch (Exception $e) {
			return false;
		}
		
		// Return
		switch($verb) {
			case 'GET':
			case 'POST':
				return $response;
				
			default:
				return true;
		}
	}
	
	public function streamData($handle, $fd, $length) {
		return fread($this->_data, $length);
	}
	
	private function _sortQueryString($query) {
		// Strip the leading ?
		if(substr($query,0,1)=='?') $query = substr($query,1);
		$args = array();
		$parts = explode('&', $query);
		foreach($parts as $part) {
			$pair = explode('=', $part, 2);
			if(is_array($pair) && 2==count($pair))
			$args[$pair[0]] = $part;
		}
		ksort($args);
		return implode("&", $args);
	}
};