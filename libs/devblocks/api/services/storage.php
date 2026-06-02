<?php

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;

class _DevblocksStorageManager {
	static $_connections = array();
	
	/**
	 *
	 * @param string $extension_id
	 * @param array $options
	 * @return Extension_DevblocksStorageEngine
	 */
	static public function getEngine($extension_id, $options=array()) {
		$hash = sha1($extension_id.json_encode($options));
		
		if(isset(self::$_connections[$hash])) {
			return self::$_connections[$hash];
		}
		
		if(null !== ($engine = DevblocksPlatform::getExtension($extension_id, true))) {
			/* @var $engine Extension_DevblocksStorageEngine */
			if(!$engine->setOptions($options))
				return false;
			
			self::$_connections[$hash] = $engine;
			return self::$_connections[$hash];
		}
		
		return false;
	}
};

class DevblocksStorageEngineDisk extends Extension_DevblocksStorageEngine {
	const ID = 'devblocks.storage.engine.disk';
	
	public function setOptions($options=[]) {
		parent::setOptions($options);
		return true;
	}
	
	function testConfig(Model_DevblocksStorageProfile $profile) {
		$storage_path = APP_STORAGE_PATH . '/';

		if(!is_dir($storage_path) || !is_writeable($storage_path))
			return false;
			
		return true;
	}
	
	function renderConfig(Model_DevblocksStorageProfile $profile) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('profile', $profile);
		
		$tpl->display("devblocks:devblocks.core::storage_engine/config/disk.tpl");
	}
	
	function saveConfig(Model_DevblocksStorageProfile $profile) {
	}
	
	public function exists($namespace, $key) {
		$storage_path = APP_STORAGE_PATH . '/';
		$basepath = realpath($storage_path) . DIRECTORY_SEPARATOR;
		$filepath = $storage_path . $this->escapeNamespace($namespace) . '/' . $key;
		
		if(!($filepath = realpath($filepath)))
			return false;
		
		if(!DevblocksPlatform::strStartsWith($filepath, $basepath))
			return false;
		
		return file_exists($filepath);
	}
	
	public function put($namespace, $id, $data) {
		$storage_path = APP_STORAGE_PATH . '/';
		$basepath = realpath($storage_path) . DIRECTORY_SEPARATOR;
		
		// Get a unique hash path for this namespace+id
		$hash = base_convert(sha1($this->escapeNamespace($namespace).$id),16,32);
		$key_prefix = sprintf("%s/%s",
			substr($hash,0,1),
			substr($hash,1,1)
		);
		
		$path = sprintf("%s%s/%s",
			$storage_path,
			$this->escapeNamespace($namespace),
			$key_prefix
		);
		
		// Create the hash path if it doesn't exist
		if(!is_dir($path)) {
			if(false === mkdir($path, 0755, true)) {
				return false;
			}
		}
		
		if(!($path = realpath($path)))
			return false;
		
		if(!DevblocksPlatform::strStartsWith($path, $basepath))
			return false;
		
		// If we're writing from a file resource
		if(is_resource($data)) {
			fseek($data, 0);
			
			// Open the output file
			if(false === ($fout = fopen($path.'/'.$id, 'w+b')))
				return false;
			
			// Stream from input to output
			while(!feof($data)) {
				fwrite($fout, fread($data, 65535));
			}
			
			// Close output
			fclose($fout);
			
		} else {
			// Write the content
			if(false === file_put_contents($path.'/'.$id, $data))
				return false;
		}

		return $key_prefix.'/'.$id;
	}

	public function get($namespace, $key, &$fp=null) {
		$storage_path = APP_STORAGE_PATH . '/';
		$basepath = realpath($storage_path) . DIRECTORY_SEPARATOR;
		
		$path = sprintf("%s%s/%s",
			$storage_path,
			$this->escapeNamespace($namespace),
			$key
		);
		
		if(!($path = realpath($path)))
			return false;
		
		if(!DevblocksPlatform::strStartsWith($path, $basepath))
			return false;
		
		if(!file_exists($path))
			return false;
		
		//if(extension_loaded('zlib'))
		//$path = 'compress.zlib://' . $path;
		
		// Read into file handle
		if($fp && is_resource($fp)) {
			$src_fp = fopen($path, 'rb');
			if(is_resource($src_fp))
			while(!feof($src_fp)) {
				if(false === fwrite($fp, fread($src_fp, 65536))) {
					fclose($src_fp);
					return false;
				}
			}
			
			fseek($fp, 0);
			fclose($src_fp);
			return true;
			
		// Return full contents
		} else {
			if(false === ($contents = file_get_contents($path)))
				return false;
			return $contents;
		}
	}
	
	public function delete($namespace, $key) {
		$storage_path = APP_STORAGE_PATH . '/';
		$path = sprintf("%s%s/%s",
			$storage_path,
			$this->escapeNamespace($namespace),
			$key
		);
		
		if($this->exists($namespace, $key))
			return @unlink($path);
		
		return true;
	}
};

class DevblocksStorageEngineDatabase extends Extension_DevblocksStorageEngine {
	const ID = 'devblocks.storage.engine.database';
	
	private $_connections = [];
	
	// Lazy connections
	public function __get($name) {
		switch($name) {
			case '_master_db':
				if(isset($this->_connections['master_db']))
					return $this->_connections['master_db'];
				
				if(($conn = $this->_getConnection(true))) {
					$this->_connections['master_db'] = $conn;
					return $conn;
				}
				break;
				
			case '_reader_db':
				if(isset($this->_connections['reader_db']))
					return $this->_connections['reader_db'];
					
				if(($conn = $this->_getConnection(false))) {
					$this->_connections['reader_db'] = $conn;
					return $conn;
				}
				break;
		}
		
		return null;
	}
	
	private function _getConnection($is_master=true) {
		// Use the existing local connection by default
		$db = DevblocksPlatform::services()->database();
		
		if($is_master) {
			$conn = $db->getMasterConnection();
		} else {
			$conn = $db->getReaderConnection();
		}
		
		return $conn;
	}
	
	public function setOptions($options=array()) {
		parent::setOptions($options);
		
		return true;
	}

	function testConfig(Model_DevblocksStorageProfile $profile) {
		$port = APP_DB_PORT ? intval(APP_DB_PORT) : null;
		
		// Test connection
		if(!(@$this->_master_db = mysqli_connect(APP_DB_HOST, APP_DB_USER, APP_DB_PASS, null, $port)))
			return false;
			
		// Test switching DB
		if(!@mysqli_select_db($this->_master_db, APP_DB_DATABASE))
			return false;
		
		return true;
	}
	
	function renderConfig(Model_DevblocksStorageProfile $profile) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('profile', $profile);
		
		$tpl->display("devblocks:devblocks.core::storage_engine/config/database.tpl");
	}
	
	function saveConfig(Model_DevblocksStorageProfile $profile) {
	}
	
	private function _createTable($namespace) {
		if(false === ($rs = mysqli_query($this->_master_db, "SHOW TABLES")))
			return false;

		$tables = [];
		while($row = mysqli_fetch_row($rs)) {
			$tables[$row[0]] = true;
		}
		
		$namespace = $this->escapeNamespace($namespace);
		
		if(isset($tables['storage_'.$namespace]))
			return true;
		
		$result = mysqli_query($this->_master_db, sprintf(
			"CREATE TABLE IF NOT EXISTS storage_%s (
				id INT UNSIGNED NOT NULL DEFAULT 0,
				data BLOB,
				chunk SMALLINT UNSIGNED DEFAULT 1,
				INDEX id_and_chunk (id, chunk)
			) ENGINE=%s;",
			$this->escapeNamespace($namespace),
			APP_DB_ENGINE
		));
		
		DevblocksPlatform::clearCache(DevblocksPlatform::CACHE_TABLES);
		
		return false !== $result;
	}
	
	public function exists($namespace, $key) {
		$result = mysqli_query($this->_master_db, sprintf("SELECT id FROM storage_%s WHERE id=%d",
			$this->escapeNamespace($namespace),
			$key
		));
		
		return (mysqli_num_rows($result)) ? true : false;
	}

	private function _writeChunksFromString($data, $namespace, $id) {
		$chunk_size = 65535;
		$chunks = 1;

		while($data && strlen($data)) {
			$chunk = substr($data, 0, $chunk_size);
			$data = substr($data, $chunk_size);
			
			// Chunk
			$sql = sprintf("INSERT INTO storage_%s (id, data, chunk) VALUES (%d, '%s', %d)",
				$this->escapeNamespace($namespace),
				$id,
				mysqli_real_escape_string($this->_master_db, $chunk),
				$chunks
			);
			if(false === (mysqli_query($this->_master_db, $sql))) {
				// Rollback
				$sql = sprintf("DELETE FROM storage_%s WHERE id = %d",
					$this->escapeNamespace($namespace),
					$id
				);
				mysqli_query($this->_master_db, $sql);
				return false;
			}
			
			$chunks++;
		}
		
		return true;
	}
	
	private function _writeChunksFromFile($fp, $namespace, $id) {
		$chunk_size = 65535;
		$chunks = 1;
		
		fseek($fp, 0);
		if(is_resource($fp))
		while(!feof($fp)) {
			$chunk = fread($fp, $chunk_size);
			
			// Chunk
			$sql = sprintf("INSERT INTO storage_%s (id, data, chunk) VALUES (%d, '%s', %d)",
				$this->escapeNamespace($namespace),
				$id,
				mysqli_real_escape_string($this->_master_db, $chunk),
				$chunks
			);
			if(false === (mysqli_query($this->_master_db, $sql))) {
				// Rollback
				$sql = sprintf("DELETE FROM storage_%s WHERE id = %d",
					$this->escapeNamespace($namespace),
					$id
				);
				mysqli_query($this->_master_db, $sql);
				return false;
			}
			
			$chunks++;
		}
		
		return true;
	}
	
	private function _put($namespace, $id, $data) {
		$sql = sprintf("DELETE FROM storage_%s WHERE id = %d",
			$this->escapeNamespace($namespace),
			$id
		);
		mysqli_query($this->_master_db, $sql);

		if(is_resource($data)) {
			if($this->_writeChunksFromFile($data, $namespace, $id))
				return $id;
		} else {
			if($this->_writeChunksFromString($data, $namespace, $id))
				return $id;
		}
			
		return false;
	}
	
	public function put($namespace, $id, $data) {
		// Try replacing first since this is the most efficient when things are working right
		$key = $this->_put($namespace, $id, $data);
		
		// If we failed, make sure the table exists
		if(false === $key) {
			if($this->_createTable($namespace)) {
				$key = $this->_put($namespace, $id, $data);
			}
		}
		
		return (false !== $key) ? $key : false;
	}

	// Pass an optional file pointer to write the response to (by reference)
	public function get($namespace, $key, &$fp=null) {
		if(false === ($result = mysqli_query($this->_reader_db, sprintf("SELECT data FROM storage_%s WHERE id=%d ORDER BY chunk ASC",
				$this->escapeNamespace($namespace),
				$key
			))))
			return false;

		if($fp && is_resource($fp)) {
			while($row = mysqli_fetch_row($result)) {
				if(false === fwrite($fp, $row[0], strlen($row[0]))) {
					mysqli_free_result($result);
					return false;
				}
			}
			
			mysqli_free_result($result);
			fseek($fp, 0);
			return true;
			
		} else {
			$contents = '';
			
			while($row = mysqli_fetch_row($result)) {
				$contents .= $row[0];
			}
			
			mysqli_free_result($result);
			return $contents;
		}
	}

	public function delete($namespace, $key) {
		$result = mysqli_query($this->_master_db, sprintf("DELETE FROM storage_%s WHERE id=%d",
			$this->escapeNamespace($namespace),
			$key
		));
		
		return (bool)$result;
	}
	
	public function batchDelete($namespace, $keys) {
		if(!is_array($keys) || !$keys)
			return true;
		
		$ids = DevblocksPlatform::sanitizeArray($keys, 'int');
		
		$sql = sprintf("DELETE FROM storage_%s WHERE id IN (%s)",
			$this->escapeNamespace($namespace),
			implode(',', $ids)
		);
		$result = mysqli_query($this->_master_db, $sql);
		
		return (bool)$result;
	}
};

class DevblocksStorageEngineS3 extends Extension_DevblocksStorageEngine {
	const ID = 'devblocks.storage.engine.s3';

	private $_signer = null;
	private $_region = 'us-east-1';

	public function setOptions($options=array()) {
		parent::setOptions($options);

		// Fail, this info is required.
		if(!isset($this->_options['bucket']))
			return false;

		// Credentials live in an encrypted connected account, not in the storage profile
		$connected_account_id = intval($this->_options['connected_account_id'] ?? 0);

		if(!$connected_account_id)
			return false;

		if(!($account = DAO_ConnectedAccount::get($connected_account_id)))
			return false;

		// Decrypt without an actor; storage I/O runs headless
		if(!($credentials = $account->decryptParams()))
			return false;

		if(!isset($credentials['access_key']) || !isset($credentials['secret_key']))
			return false;

		// Default to the global S3 endpoint when no regional host is given
		if(!isset($this->_options['host']) || empty($this->_options['host']))
			$this->_options['host'] = 's3.amazonaws.com';

		$this->_region = $this->_resolveRegion($this->_options['host'], $this->_options['region'] ?? '');
		$this->_signer = DevblocksPlatform::services()->aws()->signer($credentials['access_key'], $credentials['secret_key']);

		return true;
	}

	private function _resolveRegion(string $endpoint, string $region='') : string {
		// An explicit region wins (required for S3-compatible hosts like MinIO/Wasabi)
		if($region)
			return $region;

		// Otherwise derive it from an AWS regional endpoint for backward compatibility
		if($derived = DevblocksPlatform::services()->aws()->deriveRegionFromHost($endpoint))
			return $derived;

		return 'us-east-1';
	}

	/**
	 * Mirror the S3 virtual-hosted addressing rules. A bucket name containing a dot would
	 * break the wildcard TLS cert over HTTPS and must use path-style.
	 */
	private function _isDnsBucketName(string $bucket) : bool {
		if(strlen($bucket) > 63 || preg_match('/[^a-z0-9\.-]/', $bucket))
			return false;
		if(str_contains($bucket, '.'))
			return false;
		if(str_contains($bucket, '-.') || str_contains($bucket, '..'))
			return false;
		if(!preg_match('/^[0-9a-z]/', $bucket) || !preg_match('/[0-9a-z]$/', $bucket))
			return false;
		return true;
	}

	private function _resolveS3Url(string $bucket, string $endpoint, string $object_path, string $query='') : array {
		// The endpoint may include an optional scheme and port: [scheme://]host[:port].
		// Defaults to HTTPS; an explicit http:// (e.g. a local MinIO) is allowed.
		$scheme = 'https';

		if(preg_match('#^(https?)://(.*)$#i', $endpoint, $matches)) {
			$scheme = DevblocksPlatform::strLower($matches[1]);
			$endpoint = $matches[2];
		}

		$endpoint = rtrim($endpoint, '/');

		// Split off an explicit port so we can detect non-AWS endpoints (MinIO, on-prem, etc.)
		$host_only = $endpoint;
		$has_port = false;

		if(preg_match('#^(.+):(\d+)$#', $endpoint, $matches)) {
			$host_only = $matches[1];
			$has_port = true;
		}

		// Object keys are unreserved (namespace is alphanum+underscore, key is base32+digits),
		// so this single segment-wise encoding is stable through signing and on the wire.
		$encoded = '/' . str_replace('%2F', '/', rawurlencode($object_path));

		// Virtual-hosted addressing only works against an AWS DNS endpoint on the default port.
		// Everything else (MinIO, ngrok, on-prem, any custom port or IP) uses path-style, which is
		// universally supported and avoids bucket-prefixed-hostname TLS certificate mismatches.
		$is_aws = (bool) preg_match('/\.amazonaws\.com$/i', $host_only);
		$use_vhost = $is_aws && !$has_port && $this->_isDnsBucketName($bucket);

		if($use_vhost) {
			$authority = $bucket . '.' . $endpoint;
			$path = $encoded;
		} else {
			$authority = $endpoint;
			$path = '/' . $bucket . $encoded;
		}

		return [
			'host' => $authority,
			'url' => $scheme . '://' . $authority . $path . ($query !== '' ? '?' . $query : ''),
		];
	}

	/**
	 * Build, sign (AWS SigV4), and send a single S3 REST request.
	 *
	 * @param string|resource|\Psr\Http\Message\StreamInterface|null $body
	 * @return ResponseInterface|false
	 */
	private function _s3Request($signer, string $region, string $endpoint, ?string $bucket, string $method, string $object_path, $body, string $content_sha256, array $headers=[], array $send_options=[], string $query='') {
		$resolved = $this->_resolveS3Url($bucket ?? '', $endpoint, $object_path, $query);

		// These must be present and signed for S3 SigV4
		$headers = array_merge([
			'x-amz-date' => gmdate('Ymd\THis\Z'),
			'x-amz-content-sha256' => $content_sha256,
		], $headers);

		$request = new Request($method, $resolved['url'], $headers, $body);
		$request = $signer->sign($request, $region, 's3', $content_sha256);

		// Return the response for any status code (e.g. a 404 HEAD) instead of throwing
		$send_options = array_merge(['http_errors' => false, 'verify' => false], $send_options);

		$error = $error_response = null;
		return DevblocksPlatform::services()->http()->sendRequest($request, $send_options, $error, $error_response);
	}

	function testConfig(Model_DevblocksStorageProfile $profile) {
		// Test S3 connection info
		$connected_account_id = DevblocksPlatform::importGPC($_POST['connected_account_id'] ?? null, 'integer', 0);
		$bucket = DevblocksPlatform::importGPC($_POST['bucket'] ?? null, 'string','');
		$path_prefix = DevblocksPlatform::importGPC($_POST['path_prefix'] ?? null, 'string','');
		$host = DevblocksPlatform::importGPC($_POST['host'] ?? null, 'string', 's3.amazonaws.com');
		$region = DevblocksPlatform::importGPC($_POST['region'] ?? null, 'string', '');

		// Fall back to the saved account when the form didn't submit one
		if(empty($connected_account_id) && isset($profile->params['connected_account_id']))
			$connected_account_id = intval($profile->params['connected_account_id']);

		if(empty($connected_account_id))
			return false;

		// Pull the credentials from the encrypted connected account
		if(!($account = DAO_ConnectedAccount::get($connected_account_id)))
			return false;

		if(!($credentials = $account->decryptParams()))
			return false;

		$access_key = $credentials['access_key'] ?? null;
		$secret_key = $credentials['secret_key'] ?? null;

		if(empty($access_key) || empty($secret_key))
			return false;

		if(empty($host))
			$host = 's3.amazonaws.com';
		
		$path_prefix =
			0 == strlen(trim($path_prefix, '/'))
			? ''
			: (trim($path_prefix, '/') . '/')
			;

		try {
			$signer = DevblocksPlatform::services()->aws()->signer($access_key, $secret_key);
			$region = $this->_resolveRegion($host, $region);

			// Test a PUT, GET, and DELETE to verify the AWS credentials

			$uri = $path_prefix . '.cerb_s3_test';
			$empty_hash = hash('sha256', '');

			// PUT
			$body = 'CERB';
			$response = $this->_s3Request($signer, $region, $host, $bucket, 'PUT', $uri, $body, hash('sha256', $body), [
				'Content-Type' => 'text/plain',
				'x-amz-acl' => 'private',
				'Content-Length' => strlen($body),
			], ['timeout' => 30]);
			if(!($response instanceof ResponseInterface) || 200 != $response->getStatusCode()) {
				return false;
			}

			// GET
			$response = $this->_s3Request($signer, $region, $host, $bucket, 'GET', $uri, null, $empty_hash, [], ['timeout' => 30]);
			if(!($response instanceof ResponseInterface) || 200 != $response->getStatusCode() || 'CERB' != $response->getBody()->getContents()) {
				return false;
			}

			// DELETE
			$response = $this->_s3Request($signer, $region, $host, $bucket, 'DELETE', $uri, null, $empty_hash, [], ['timeout' => 30]);
			if(!($response instanceof ResponseInterface) || !in_array($response->getStatusCode(), [200, 204])) {
				return false;
			}

		} catch(Exception $e) {
			DevblocksPlatform::logException($e);
			return false;
		}

		return true;
	}

	function renderConfig(Model_DevblocksStorageProfile $profile) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('profile', $profile);

		// Resolve the linked connected account for the chooser (no static calls in templates)
		$connected_account = null;

		if(($connected_account_id = intval($profile->params['connected_account_id'] ?? 0)))
			$connected_account = DAO_ConnectedAccount::get($connected_account_id);

		$tpl->assign('connected_account', $connected_account);
		$tpl->assign('connected_account_context', CerberusContexts::CONTEXT_CONNECTED_ACCOUNT);

		$tpl->display("devblocks:devblocks.core::storage_engine/config/s3.tpl");
	}

	function saveConfig(Model_DevblocksStorageProfile $profile) {
		$connected_account_id = DevblocksPlatform::importGPC($_POST['connected_account_id'] ?? null, 'integer', 0);
		$bucket = DevblocksPlatform::importGPC($_POST['bucket'] ?? null, 'string', '');
		$path_prefix = DevblocksPlatform::importGPC($_POST['path_prefix'] ?? null, 'string', '');
		$host = DevblocksPlatform::importGPC($_POST['host'] ?? null, 'string', '');
		$region = DevblocksPlatform::importGPC($_POST['region'] ?? null, 'string', '');

		$path_prefix =
			0 == strlen(trim($path_prefix, '/'))
			? ''
			: (trim($path_prefix, '/') . '/')
			;

		$fields = array(
			DAO_DevblocksStorageProfile::PARAMS_JSON => json_encode(array(
				'connected_account_id' => $connected_account_id,
				'host' => $host,
				'region' => $region,
				'bucket' => $bucket,
				'path_prefix' => $path_prefix,
			)),
		);

		DAO_DevblocksStorageProfile::update($profile->id, $fields);
	}

	public function exists($namespace, $key) {
		$bucket = $this->_options['bucket'] ?? null;
		$path = $this->_options['path_prefix'] . $this->escapeNamespace($namespace) . '/' . $key;

		$response = $this->_s3Request($this->_signer, $this->_region, $this->_options['host'], $bucket, 'HEAD', $path, null, hash('sha256', ''), [], ['timeout' => 30]);

		return ($response instanceof ResponseInterface && 200 == $response->getStatusCode());
	}

	public function put($namespace, $id, $data) {
		$bucket = $this->_options['bucket'] ?? null;

		// Get a unique hash path for this namespace+id
		$hash = base_convert(sha1($this->escapeNamespace($namespace).$id), 16, 32);

		$key = sprintf("%s/%s/%d",
			substr($hash,0,1),
			substr($hash,1,1),
			$id
		);

		$path = $this->_options['path_prefix'] . $this->escapeNamespace($namespace) . '/' . $key;

		$headers = [
			'Content-Type' => 'application/octet-stream',
			'x-amz-acl' => 'private',
		];

		if(is_resource($data)) {
			// Stream large content without buffering it into memory to hash
			$stat = fstat($data);
			fseek($data, 0);

			$headers['Content-Length'] = $stat['size'] ?? 0;
			$content_sha256 = 'UNSIGNED-PAYLOAD';
			$body = Utils::streamFor($data);

		} else {
			// Hash small string content end-to-end
			$headers['Content-Length'] = strlen($data);
			$content_sha256 = hash('sha256', $data);
			$body = $data;
		}

		$response = $this->_s3Request($this->_signer, $this->_region, $this->_options['host'], $bucket, 'PUT', $path, $body, $content_sha256, $headers, ['timeout' => 60]);

		if($response instanceof ResponseInterface && 200 == $response->getStatusCode())
			return $key;

		return false;
	}

	public function get($namespace, $key, &$fp=null) {
		$bucket = $this->_options['bucket'] ?? null;
		$path = $this->_options['path_prefix'] . $this->escapeNamespace($namespace) . '/' . $key;

		// Stream the response so large objects aren't buffered into memory
		$response = $this->_s3Request($this->_signer, $this->_region, $this->_options['host'], $bucket, 'GET', $path, null, hash('sha256', ''), [], ['timeout' => 60, 'stream' => true]);

		if(!($response instanceof ResponseInterface) || 200 != $response->getStatusCode())
			return false;

		if($fp && is_resource($fp)) {
			$body = $response->getBody();

			while(!$body->eof())
				fwrite($fp, $body->read(65536));

			fseek($fp, 0);
			return true;

		} else {
			return $response->getBody()->getContents();
		}
	}

	public function deletesAreDeferred() : bool {
		// S3 deletes are remote/slow; defer them to the background storage queue
		return true;
	}

	public function getDeleteBatchSize() : int {
		// The DeleteObjects API accepts up to 1000 keys per request
		return 1000;
	}

	public function delete($namespace, $key) {
		// Immediate single delete; deferral/batching happens upstream in deleteKeys()
		return $this->batchDelete($namespace, [$key]);
	}

	public function batchDelete($namespace, $keys) {
		if(!is_array($keys) || !$keys)
			return [];

		$bucket = $this->_options['bucket'] ?? null;
		$ns = $this->escapeNamespace($namespace);
		$path_prefix = $this->_options['path_prefix'];

		$key_prefix = $path_prefix . $ns . '/';
		$deleted = [];

		// Native multi-object delete (DeleteObjects): one signed POST {bucket}/?delete per <=1000 keys
		foreach(array_chunk(array_values($keys), 1000) as $chunk) {
			// Quiet mode: only failures are returned in the response
			$xml = '<?xml version="1.0" encoding="UTF-8"?><Delete><Quiet>true</Quiet>';

			foreach($chunk as $key) {
				$xml .= '<Object><Key>' . htmlspecialchars($key_prefix . $key, ENT_XML1, 'UTF-8') . '</Key></Object>';
			}

			$xml .= '</Delete>';

			$headers = [
				'Content-Type' => 'application/xml',
				'Content-MD5' => base64_encode(md5($xml, true)),
				'Content-Length' => strlen($xml),
			];

			$response = $this->_s3Request($this->_signer, $this->_region, $this->_options['host'], $bucket, 'POST', '', $xml, hash('sha256', $xml), $headers, ['timeout' => 60], 'delete=');

			// On a hard failure the whole chunk is unconfirmed; leave its keys for retry
			if(!($response instanceof ResponseInterface) || 200 != $response->getStatusCode())
				continue;

			// Parse per-key <Error> entries; everything else in the chunk was deleted
			$failed = [];

			if(($body = $response->getBody()->getContents()) && ($doc = DevblocksPlatform::parseXml($body)) && isset($doc->Error)) {
				foreach($doc->Error as $error) {
					$failed[] = substr((string) $error->Key, strlen($key_prefix));
				}
			}

			$deleted = array_merge($deleted, array_values(array_diff($chunk, $failed)));
		}

		// The keys actually deleted (caller treats missing keys as failures to retry)
		return $deleted;
	}
};

class DevblocksStorageEngine_CerbCloudS3 extends Extension_DevblocksStorageEngine {
	const ID = 'cerb.cloud.storage.engine.s3';
	
	public function setOptions($options=array()) {
		if(
			!defined('CERB_CLOUD_SUBDOMAIN')
			|| !defined('CERB_CLOUD_TOKEN')
			|| !defined('CERB_CLOUD_STORAGE_ENDPOINT')
		) {
			return false;
		}
		
		parent::setOptions($options);
		
		return true;
	}
	
	function testConfig(Model_DevblocksStorageProfile $profile) {
		// No public configuration
	}
	
	function renderConfig(Model_DevblocksStorageProfile $profile) {
		// No public configuration
	}
	
	function saveConfig(Model_DevblocksStorageProfile $profile) {
		// No public configuration
	}
	
	private function _httpRequestWithRetries(string $method, string $uri,  int $num_retries=1, array $options=[]) {
		$http_client = new GuzzleHttp\Client();
		
		// Include an attempt for the first request
		$num_retries++;
		
		do {
			try {
				$response = $http_client->request($method, $uri, $options);
				
				if($response instanceof ResponseInterface)
					return $response;
				
			} catch (ConnectException $e) {
				DevblocksPlatform::logException($e);
				
				// Special handling for errors
				if(
					($handler_context = $e->getHandlerContext())
					&& is_array($handler_context)
					&& array_key_exists('errno', $handler_context)
				) {
					switch($handler_context['errno']) {
						case CURLE_COULDNT_CONNECT:
							usleep(250_000);
							break;
							
						case CURLE_OPERATION_TIMEDOUT:
							$num_retries = 0;
							break;
					}
				}
				
			} catch (Throwable $e) {
				DevblocksPlatform::logException($e);
			}
			
		} while(--$num_retries > 0);
		
		return false;
	}
	
	public function exists($namespace, $key) : bool {
		$token_url = sprintf("%s/%s/%s/%s",
			rtrim(CERB_CLOUD_STORAGE_ENDPOINT,'/'),
			CERB_CLOUD_SUBDOMAIN,
			$this->escapeNamespace($namespace),
			$key
		);
		
		$response = $this->_httpRequestWithRetries('HEAD', $token_url, 1, [
			'http_errors' => false,
			'timeout' => '8',
			'headers' => [
				'Authorization' => CERB_CLOUD_TOKEN,
			]
		]);
		
		if($response instanceof ResponseInterface && 200 == $response->getStatusCode())
			return true;
	
		return false;
	}
	
	public function put($namespace, $id, $data) {
		// Get a unique hash path for this namespace+id
		$hash = base_convert(sha1($this->escapeNamespace($namespace) . $id), 16, 32);
		
		$key = sprintf("%s/%s/%d",
			substr($hash, 0, 1),
			substr($hash, 1, 1),
			$id
		);
		
		$token_url = sprintf("%s/%s/%s/%s",
			rtrim(CERB_CLOUD_STORAGE_ENDPOINT,'/'),
			CERB_CLOUD_SUBDOMAIN,
			$this->escapeNamespace($namespace),
			$key
		);
		
		$response = $this->_httpRequestWithRetries('PUT', $token_url, 1, [
			'http_errors' => false,
			'timeout' => '8',
			'headers' => [
				'Authorization' => CERB_CLOUD_TOKEN,
			],
		]);
		
		if(
			!($response instanceof ResponseInterface)
			|| !($response_json = @json_decode($response->getBody()->getContents(), true))
			|| !is_array($response_json)
			|| !array_key_exists('url', $response_json)
		) {
			return false;
		}
		
		// The pre-signed URL
		$put_url = $response_json['url'];
		
		$response = $this->_httpRequestWithRetries('PUT', $put_url, 1, [
			'http_errors' => false,
			'headers' => [],
			'timeout' => '30',
			'body' => $data,
		]);
		
		if(!($response instanceof ResponseInterface))
			return false;
		
		if(200 ==  $response->getStatusCode())
			return $key;
		
		return false;
	}

	public function get($namespace, $key, &$fp=null) {
		$token_url = sprintf("%s/%s/%s/%s",
			rtrim(CERB_CLOUD_STORAGE_ENDPOINT,'/'),
			CERB_CLOUD_SUBDOMAIN,
			$this->escapeNamespace($namespace),
			$key
		);
		
		$response = $this->_httpRequestWithRetries('GET', $token_url, 1, [
			'http_errors' => false,
			'timeout' => '8',
			'headers' => [
				'Authorization' => CERB_CLOUD_TOKEN,
			]
		]);
		
		if(
			!($response instanceof ResponseInterface)
			|| !($response_json = @json_decode($response->getBody()->getContents(), true))
			|| !is_array($response_json)
			|| !array_key_exists('url', $response_json)
		) {
			return false;
		}
		
		// The pre-signed URL
		$get_url = $response_json['url'];
		
		$response = $this->_httpRequestWithRetries('GET', $get_url, 1, [
			'http_errors' => false,
			'timeout' => '30',
		]);
		
		if(!($response instanceof ResponseInterface) || 200 != $response->getStatusCode())
			return false;
	
		if($fp && is_resource($fp)) {
			$body = $response->getBody();
			
			while(!$body->eof())
				fwrite($fp, $body->read(65536));
			
			fseek($fp, 0);
			return true;
			
		} else {
			return $response->getBody()->getContents();
		}
	}
	
	public function delete($namespace, $key) {
		$token_url = sprintf("%s/%s/%s/%s",
			rtrim(CERB_CLOUD_STORAGE_ENDPOINT,'/'),
			CERB_CLOUD_SUBDOMAIN,
			$this->escapeNamespace($namespace),
			$key
		);
		
		$response = $this->_httpRequestWithRetries('DELETE', $token_url, 1, [
			'http_errors' => false,
			'timeout' => '8',
			'headers' => [
				'Authorization' => CERB_CLOUD_TOKEN,
			]
		]);
		
		if($response instanceof ResponseInterface && 200 == $response->getStatusCode())
			return $key;
		
		return true;
	}
};