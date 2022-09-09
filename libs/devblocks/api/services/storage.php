<?php
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
	
	public function setOptions($options=array()) {
		parent::setOptions($options);
		
		// Default
		if(!isset($this->_options['storage_path']))
			$this->_options['storage_path'] = APP_STORAGE_PATH . '/';
		
		return true;
	}
	
	function testConfig(Model_DevblocksStorageProfile $profile) {
		$path = DevblocksPlatform::importGPC($_POST['path'] ?? null, 'string','');
		
		if(empty($path))
			$path = APP_STORAGE_PATH . '/';
			
		if(!is_dir($path) || !is_writeable($path))
			return false;
			
		return true;
	}
	
	function renderConfig(Model_DevblocksStorageProfile $profile) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('profile', $profile);
		
		$tpl->display("devblocks:devblocks.core::storage_engine/config/disk.tpl");
	}
	
	function saveConfig(Model_DevblocksStorageProfile $profile) {
		$path = DevblocksPlatform::importGPC($_POST['path'] ?? null, 'string','');
		
		if(!is_dir($path) || !is_writeable($path))
			return;
		
		// Format path
		$path = rtrim($path,'\/') . '/';
			
		$fields = array(
			DAO_DevblocksStorageProfile::PARAMS_JSON => json_encode(array(
				'storage_path' => $path,
			)),
		);
		
		DAO_DevblocksStorageProfile::update($profile->id, $fields);
	}
	
	public function exists($namespace, $key) {
		$basepath = realpath($this->_options['storage_path']) . DIRECTORY_SEPARATOR;
		$filepath = $this->_options['storage_path'] . $this->escapeNamespace($namespace) . '/' . $key;
		
		if(false == ($filepath = realpath($filepath)))
			return false;
		
		if(!DevblocksPlatform::strStartsWith($filepath, $basepath))
			return false;
		
		return file_exists($filepath);
	}
	
	public function put($namespace, $id, $data) {
		$basepath = realpath($this->_options['storage_path']) . DIRECTORY_SEPARATOR;
		
		// Get a unique hash path for this namespace+id
		$hash = base_convert(sha1($this->escapeNamespace($namespace).$id),16,32);
		$key_prefix = sprintf("%s/%s",
			substr($hash,0,1),
			substr($hash,1,1)
		);
		
		$path = sprintf("%s%s/%s",
			$this->_options['storage_path'],
			$this->escapeNamespace($namespace),
			$key_prefix
		);
		
		// Create the hash path if it doesn't exist
		if(!is_dir($path)) {
			if(false === mkdir($path, 0755, true)) {
				return false;
			}
		}
		
		if(false == ($path = realpath($path)))
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
		$basepath = realpath($this->_options['storage_path']) . DIRECTORY_SEPARATOR;
		
		$path = sprintf("%s%s/%s",
			$this->_options['storage_path'],
			$this->escapeNamespace($namespace),
			$key
		);
		
		if(false == ($path = realpath($path)))
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
		$path = sprintf("%s%s/%s",
			$this->_options['storage_path'],
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
	
	private $_connections = array();
	
	// Lazy connections
	public function __get($name) {
		switch($name) {
			case '_master_db':
				if(isset($this->_connections['master_db']))
					return $this->_connections['master_db'];
				
				if(false != ($conn = $this->_getConnection(true))) {
					$this->_connections['master_db'] = $conn;
					return $conn;
				}
				break;
				
			case '_reader_db':
				if(isset($this->_connections['reader_db']))
					return $this->_connections['reader_db'];
					
				if(false != ($conn = $this->_getConnection(false))) {
					$this->_connections['reader_db'] = $conn;
					return $conn;
				}
				break;
		}
		
		return null;
	}
	
	private function _getConnection($is_master=true) {
		$conn = null;
		
		// Use the existing local connection by default
		if(empty($this->_options['host'])) {
			$db = DevblocksPlatform::services()->database();
			
			if($is_master) {
				$conn = $db->getMasterConnection();
				
			} else {
				$conn = $db->getReaderConnection();
			}
			
		// Use the provided connection details
		} else {
			if($is_master) {
				$db_port = !empty($this->_options['port'] ?? null) ? intval($this->_options['port']) : null;
				
				if(!($conn = mysqli_connect($this->_options['host'], $this->_options['user'], $this->_options['password'], null, $db_port))) {
					return false;
				}
					
				if(!mysqli_select_db($conn, $this->_options['database'])) {
					return false;
				}
				
			} else {
				// Always use the existing master connection for external DBs
				$conn = $this->_master_db;
			}
		}
		
		return $conn;
	}
	
	public function setOptions($options=array()) {
		parent::setOptions($options);
		
		return true;
	}

	function testConfig(Model_DevblocksStorageProfile $profile) {
		$host = DevblocksPlatform::importGPC($_POST['host'] ?? null, 'string','');
		$port = DevblocksPlatform::importGPC($_POST['port'] ?? null, 'string','');
		$user = DevblocksPlatform::importGPC($_POST['user'] ?? null, 'string','');
		$password = DevblocksPlatform::importGPC($_POST['password'] ?? null, 'string','');
		$database = DevblocksPlatform::importGPC($_POST['database'] ?? null, 'string','');
		
		if(empty($host)) {
			$host = APP_DB_HOST;
			$port = APP_DB_PORT;
			$user = APP_DB_USER;
			$password = APP_DB_PASS;
			$database = APP_DB_DATABASE;
		}
		
		$port = $port ? intval($port) : null;
		
		// Test connection
		if(!(@$this->_master_db = mysqli_connect($host, $user, $password, null, $port)))
			return false;
			
		// Test switching DB
		if(!@mysqli_select_db($this->_master_db, $database))
			return false;
		
		return true;
	}
	
	function renderConfig(Model_DevblocksStorageProfile $profile) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('profile', $profile);
		
		$tpl->display("devblocks:devblocks.core::storage_engine/config/database.tpl");
	}
	
	function saveConfig(Model_DevblocksStorageProfile $profile) {
		$host = DevblocksPlatform::importGPC($_POST['host'] ?? null, 'string','');
		$user = DevblocksPlatform::importGPC($_POST['user'] ?? null, 'string','');
		$password = DevblocksPlatform::importGPC($_POST['password'] ?? null, 'string','');
		$database = DevblocksPlatform::importGPC($_POST['database'] ?? null, 'string','');
		
		$fields = array(
			DAO_DevblocksStorageProfile::PARAMS_JSON => json_encode(array(
				'host' => $host,
				'user' => $user,
				'password' => $password,
				'database' => $database,
			)),
		);
		
		DAO_DevblocksStorageProfile::update($profile->id, $fields);
	}
	
	private function _createTable($namespace) {
		$rs = mysqli_query($this->_master_db, "SHOW TABLES");

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
		
		return (false !== $result) ? true : false;
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
		
		return $result ? true : false;
	}
};

class DevblocksStorageEngineS3 extends Extension_DevblocksStorageEngine {
	const ID = 'devblocks.storage.engine.s3';
	
	private $_s3 = null;
	
	public function setOptions($options=array()) {
		parent::setOptions($options);
		
		// Fail, this info is required.
		if(!isset($this->_options['access_key']))
			return false;
		if(!isset($this->_options['secret_key']))
			return false;
		if(!isset($this->_options['bucket']))
			return false;
		
		if(!isset($this->_options['host']) || empty($this->_options['host'])) {
			$this->_s3 = new S3($this->_options['access_key'], $this->_options['secret_key'], true);
		} else {
			$this->_s3 = new S3($this->_options['access_key'], $this->_options['secret_key'], true, $this->_options['host']);
		}
		
		return true;
	}
	
	function testConfig(Model_DevblocksStorageProfile $profile) {
		// Test S3 connection info
		$access_key = DevblocksPlatform::importGPC($_POST['access_key'] ?? null, 'string', null);
		$secret_key = DevblocksPlatform::importGPC($_POST['secret_key'] ?? null, 'string', null);
		$bucket = DevblocksPlatform::importGPC($_POST['bucket'] ?? null, 'string','');
		$path_prefix = DevblocksPlatform::importGPC($_POST['path_prefix'] ?? null, 'string','');
		$host = DevblocksPlatform::importGPC($_POST['host'] ?? null, 'string', 's3.amazonaws.com');
		
		// If blank, try using a previously saved copy.
		if(empty($secret_key) && isset($profile->params['secret_key']))
			$secret_key = $profile->params['secret_key'];
		
		$path_prefix =
			0 == strlen(trim($path_prefix, '/'))
			? ''
			: (trim($path_prefix, '/') . '/')
			;
		
		try {
			if(!empty($host))
				$s3 = new S3($access_key, $secret_key, true, $host);
			else
				$s3 = new S3($access_key, $secret_key, true);
			
			// Test a PUT, GET, and DELETE to verify the AWS credentials
			
			$uri = $path_prefix . '.cerb_s3_test';
			
			// PUT
			if(false == @$s3->putObject("CERB", $bucket, $uri))
				return false;
			
			// GET
			if(false == ($result = @$s3->getObject($bucket, $uri))
				|| !isset($result->body)
				|| $result->body != 'CERB')
				return false;
			
			// DELETE
			if(false == @$s3->deleteObject($bucket, $uri))
				return false;
			
		} catch(Exception $e) {
			return false;
		}
		
		return true;
	}
	
	function renderConfig(Model_DevblocksStorageProfile $profile) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('profile', $profile);
		
		$tpl->display("devblocks:devblocks.core::storage_engine/config/s3.tpl");
	}
	
	function saveConfig(Model_DevblocksStorageProfile $profile) {
		$access_key = DevblocksPlatform::importGPC($_POST['access_key'] ?? null, 'string', null);
		$secret_key = DevblocksPlatform::importGPC($_POST['secret_key'] ?? null, 'string', null);
		$bucket = DevblocksPlatform::importGPC($_POST['bucket'] ?? null, 'string', '');
		$path_prefix = DevblocksPlatform::importGPC($_POST['path_prefix'] ?? null, 'string', '');
		$host = DevblocksPlatform::importGPC($_POST['host'] ?? null, 'string', '');
		
		// If blank, try using a previously saved copy.
		if(empty($secret_key) && isset($profile->params['secret_key']))
			$secret_key = $profile->params['secret_key'];

		$path_prefix =
			0 == strlen(trim($path_prefix, '/'))
			? ''
			: (trim($path_prefix, '/') . '/')
			;
		
		$fields = array(
			DAO_DevblocksStorageProfile::PARAMS_JSON => json_encode(array(
				'access_key' => $access_key,
				'secret_key' => $secret_key,
				'host' => $host,
				'bucket' => $bucket,
				'path_prefix' => $path_prefix,
			)),
		);
		
		DAO_DevblocksStorageProfile::update($profile->id, $fields);
	}
	
	public function exists($namespace, $key) {
		$bucket = $this->_options['bucket'] ?? null;
		$path = $this->_options['path_prefix'] . $this->escapeNamespace($namespace) . '/' . $key;
		
		return false !== (@$this->_s3->getObjectInfo($bucket, $path));
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
		
		if(is_resource($data)) {
			// Write the content from stream
			if(false === ($object = @$this->_s3->inputResource($data))) {
				return false;
			}
			
			if(false === @$this->_s3->putObject($object, $bucket, $path, S3::ACL_PRIVATE)) {
				return false;
			}
			
		} else {
			// Write the content from string
			if(false === @$this->_s3->putObject($data, $bucket, $path, S3::ACL_PRIVATE)) {
				return false;
			}
		}
		
		return $key;
	}

	public function get($namespace, $key, &$fp=null) {
		$bucket = $this->_options['bucket'] ?? null;
		$path = $this->_options['path_prefix'] . $this->escapeNamespace($namespace) . '/' . $key;
		
		if($fp && is_resource($fp)) {
			// Use the filename rather than $fp because the S3 lib will fclose($fp)
			$tmpfile = DevblocksPlatform::getTempFileInfo($fp);
			if(false !== (@$this->_s3->getObject($bucket, $path, $tmpfile))) {
				fseek($fp, 0);
				return true;
			}
			
		} else {
			if(false !== ($object = @$this->_s3->getObject($bucket, $path))
				&& isset($object->body))
				return $object->body;
		}
			
		return false;
	}
	
	public function delete($namespace, $key) {
		// Queue up batch DELETEs
		$profile_id = isset($this->_options['_profile_id']) ? $this->_options['_profile_id'] : 0;
		DAO_DevblocksStorageQueue::enqueueDelete($namespace, $key, $this->manifest->id, $profile_id);
	}
	
	public function batchDelete($namespace, $keys) {
		$bucket = $this->_options['bucket'] ?? null;
		$errors = array();
		
		$ns = $this->escapeNamespace($namespace);
		$path_prefix = $this->_options['path_prefix'];
		
		$paths = array_map(function($e) use ($ns, $path_prefix) {
			return $path_prefix . $ns . '/'. $e;
		}, $keys);
		
		// Handle the case where some objects fail to delete (e.g. AccessDenied)
		
		foreach($paths as $path) {
			if(false === (@$this->_s3->deleteObject($bucket, $path))) {
				$errors[] = str_replace($path_prefix . $ns . '/', '', $path);
			}
		}
		
		// Return the keys that were actually deleted, ignoring any errors
		if(!empty($errors))
			return array_diff($keys, $errors);
		
		return $keys;
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
	
	public function exists($namespace, $key) {
		$token_url = sprintf("%s/%s/%s/%s",
			rtrim(CERB_CLOUD_STORAGE_ENDPOINT,'/'),
			CERB_CLOUD_SUBDOMAIN,
			$this->escapeNamespace($namespace),
			$key
		);
		
		$http_client = new GuzzleHttp\Client();
		
		$response = $http_client->head($token_url, [
			'http_errors' => false,
			'headers' => [
				'Authorization' => CERB_CLOUD_TOKEN,
			]
		]);
		
		if(200 == $response->getStatusCode())
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
		
		$http_client = new GuzzleHttp\Client();
		
		$response = $http_client->request('PUT', $token_url, [
			'http_errors' => false,
			'headers' => [
				'Authorization' => CERB_CLOUD_TOKEN,
			],
		]);
		
		$response_body = $response->getBody()->getContents();
		
		if(
			false == ($response_json = @json_decode($response_body, true))
			|| !is_array($response_json)
			|| !array_key_exists('url', $response_json)
		) {
			return false;
		}
		
		// The pre-signed URL
		$put_url = $response_json['url'];
		
		$response = $http_client->put($put_url, [
			'http_errors' => false,
			'headers' => [],
			'body' => $data,
		]);
		
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
		
		$http_client = new GuzzleHttp\Client();
		
		$response = $http_client->get($token_url, [
			'http_errors' => false,
			'headers' => [
				'Authorization' => CERB_CLOUD_TOKEN,
			]
		]);
		
		if(
			false == ($response_json = @json_decode($response->getBody()->getContents(), true))
			|| !is_array($response_json)
			|| !array_key_exists('url', $response_json)
		) {
			return false;
		}
		
		// The pre-signed URL
		$get_url = $response_json['url'];
		
		$response = $http_client->get($get_url, [
			'http_errors' => false,
		]);
		
		if(200 != $response->getStatusCode())
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
		
		return false;
	}
	
	public function delete($namespace, $key) {
		$token_url = sprintf("%s/%s/%s/%s",
			rtrim(CERB_CLOUD_STORAGE_ENDPOINT,'/'),
			CERB_CLOUD_SUBDOMAIN,
			$this->escapeNamespace($namespace),
			$key
		);
		
		$http_client = new GuzzleHttp\Client();
		
		$response = $http_client->delete($token_url, [
			'http_errors' => false,
			'headers' => [
				'Authorization' => CERB_CLOUD_TOKEN,
			]
		]);
		
		// [TODO] A 200-OK response may contain an embedded error message
		
		if(200 ==  $response->getStatusCode())
			return $key;
		
		return true;
	}
};