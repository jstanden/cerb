<?php
class DevblocksStorageEngineDisk extends Extension_DevblocksStorageEngine {
	const ID = 'devblocks.storage.engine.disk'; 
	
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	public function setOptions($options) {
		parent::setOptions($options);
		
		// Default
		if(!isset($this->_options['storage_path']))
			$this->_options['storage_path'] = APP_STORAGE_PATH . '/';
	}
	
	function testConfig() {
		@$path = DevblocksPlatform::importGPC($_POST['path'],'string','');
		
		if(empty($path))
			$path = APP_STORAGE_PATH . '/';
			
		if(!is_dir($path) || !is_writeable($path))
			return false;
			
		return true;
	}
	
	function renderConfig(Model_DevblocksStorageProfile $profile) {
		$tpl = DevblocksPlatform::getTemplateService();
		$path = dirname(dirname(__FILE__)) . '/templates';
		
		$tpl->assign('profile', $profile);
		
		$tpl->display("file:{$path}/storage_engine/config/disk.tpl");
	}
	
	function saveConfig(Model_DevblocksStorageProfile $profile) {
		@$path = DevblocksPlatform::importGPC($_POST['path'],'string','');
		
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
		return file_exists($this->_options['storage_path'] . $this->escapeNamespace($namespace) . '/' . $key);
	}
	
	public function put($namespace, $id, $data) {
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

		$key = $key_prefix.'/'.$id;
			
		return $key;
	}

	public function get($namespace, $key, &$fp=null) {
		$path = sprintf("%s%s/%s",
			$this->_options['storage_path'],
			$this->escapeNamespace($namespace),
			$key
		);
		
		// Read into file handle
		if($fp && is_resource($fp)) {
			$src_fp = fopen($path, 'rb');
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
			
		return false;
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
	
	private $_db = null;
	
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	public function setOptions($options) {
		parent::setOptions($options);
		
		// Use the existing local connection by default
		if(empty($this->_options['host'])) {
			$db = DevblocksPlatform::getDatabaseService();
			$this->_db = $db->getConnection();
			
		// Use the provided connection details
		} else {
			if(false == ($this->_db = mysql_connect($this->_options['host'], $this->_options['user'], $this->_options['password'], true))) {
				$this->_db = null;
				return false;
			}
				
			if(false == mysql_select_db($this->_options['database'], $this->_db)) {
				$this->_db = null;
				return false;
			}
		}
		
		return true;
	}	

	function testConfig() {
		@$host = DevblocksPlatform::importGPC($_POST['host'],'string','');
		@$user = DevblocksPlatform::importGPC($_POST['user'],'string','');
		@$password = DevblocksPlatform::importGPC($_POST['password'],'string','');
		@$database = DevblocksPlatform::importGPC($_POST['database'],'string','');
		
		if(empty($host)) {
			$host = APP_DB_HOST;
			$user = APP_DB_USER;
			$password = APP_DB_PASS;
			$database = APP_DB_DATABASE;
		}
		
		// Test connection
		if(false == (@$this->_db = mysql_connect($host, $user, $password)))
			return false;
			
		// Test switching DB
		if(false == @mysql_select_db($database, $this->_db))
			return false;
		
		return true;
	}
	
	function renderConfig(Model_DevblocksStorageProfile $profile) {
		$tpl = DevblocksPlatform::getTemplateService();
		$path = dirname(dirname(__FILE__)) . '/templates';
		
		$tpl->assign('profile', $profile);
		
		$tpl->display("file:{$path}/storage_engine/config/database.tpl");
	}
	
	function saveConfig(Model_DevblocksStorageProfile $profile) {
		@$host = DevblocksPlatform::importGPC($_POST['host'],'string','');
		@$user = DevblocksPlatform::importGPC($_POST['user'],'string','');
		@$password = DevblocksPlatform::importGPC($_POST['password'],'string','');
		@$database = DevblocksPlatform::importGPC($_POST['database'],'string','');
		
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
		$rs = mysql_query("SHOW TABLES", $this->_db);

		$tables = array();
		while($row = mysql_fetch_row($rs)) {
			$tables[$row[0]] = true;
		}
		
		$namespace = $this->escapeNamespace($namespace);
		
		if(isset($tables['storage_'.$namespace]))
			return true;
		
		$result = mysql_query(sprintf(
			"CREATE TABLE IF NOT EXISTS storage_%s (
				id INT UNSIGNED NOT NULL DEFAULT 0,
				data BLOB,
				chunk SMALLINT UNSIGNED DEFAULT 1,
				INDEX id (id),
				INDEX chunk (chunk)
			) ENGINE=MyISAM;",
			$this->escapeNamespace($namespace)
		), $this->_db);
		
		DevblocksPlatform::clearCache(DevblocksPlatform::CACHE_TABLES);
		
		return (false !== $result) ? true : false;
	}
	
	public function exists($namespace, $key) {
		$result = mysql_query(sprintf("SELECT id FROM storage_%s WHERE id=%d",
			$this->escapeNamespace($namespace),
			$key
		), $this->_db);
		
		return (mysql_num_rows($result)) ? true : false;
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
				mysql_real_escape_string($chunk, $this->_db),
				$chunks
			);
			if(false === ($result = mysql_query($sql, $this->_db))) {
				// Rollback
				$sql = sprintf("DELETE QUICK FROM storage_%s WHERE id = %d",
					$this->escapeNamespace($namespace),
					$id
				);
				mysql_query($sql, $this->_db);
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
		while(!feof($fp)) {
			$chunk = fread($fp, $chunk_size);
			
			// Chunk
			$sql = sprintf("INSERT INTO storage_%s (id, data, chunk) VALUES (%d, '%s', %d)",
				$this->escapeNamespace($namespace),
				$id,
				mysql_real_escape_string($chunk, $this->_db),
				$chunks
			);
			if(false === ($result = mysql_query($sql, $this->_db))) {
				// Rollback
				$sql = sprintf("DELETE QUICK FROM storage_%s WHERE id = %d",
					$this->escapeNamespace($namespace),
					$id
				);
				mysql_query($sql, $this->_db);
				return false;
			}
			
			$chunks++;
		}
		
		return true;
	}
	
	private function _put($namespace, $id, $data) {
		$sql = sprintf("DELETE QUICK FROM storage_%s WHERE id = %d",
			$this->escapeNamespace($namespace),
			$id
		);
		mysql_query($sql, $this->_db);

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
		if(false === ($result = mysql_query(sprintf("SELECT data FROM storage_%s WHERE id=%d ORDER BY chunk ASC",
				$this->escapeNamespace($namespace),
				$key
			), $this->_db)))
			return false;

		if($fp && is_resource($fp)) {
			while($row = mysql_fetch_row($result)) {
				if(false === fwrite($fp, $row[0], strlen($row[0]))) {
					mysql_free_result($result);
					return false;
				}
			}
			
			mysql_free_result($result);
			fseek($fp, 0);
			return true;
			
		} else {
			$contents = '';
			
			while($row = mysql_fetch_row($result)) {
				$contents .= $row[0];
			}
			
			mysql_free_result($result);
			return $contents;
		}
	}

	public function delete($namespace, $key) {
		$result = mysql_query(sprintf("DELETE FROM storage_%s WHERE id=%d",
			$this->escapeNamespace($namespace),
			$key
		), $this->_db);
		
		return $result ? true : false;
	}	
};

class DevblocksStorageEngineS3 extends Extension_DevblocksStorageEngine {
	const ID = 'devblocks.storage.engine.s3';
	
	private $_s3 = null;
	
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	public function setOptions($options) {
		parent::setOptions($options);
		
		// Fail, this info is required.
		if(!isset($this->_options['access_key']))
			return false;
		if(!isset($this->_options['secret_key']))
			return false;
		if(!isset($this->_options['bucket']))
			return false;
		
		$this->_s3 = new S3($this->_options['access_key'], $this->_options['secret_key']);
			
		if(false === @$this->_s3->getBucket($this->_options['bucket'])) {
			if(false === @$this->_s3->putBucket($this->_options['bucket'], S3::ACL_PRIVATE)) {
				return false;
			}
		}
	}	
	
	function testConfig() {
		// Test S3 connection info
		@$access_key = DevblocksPlatform::importGPC($_POST['access_key'],'string','');
		@$secret_key = DevblocksPlatform::importGPC($_POST['secret_key'],'string','');
		@$bucket = DevblocksPlatform::importGPC($_POST['bucket'],'string','');
		
		try {
			$s3 = new S3($access_key, $secret_key);
			if(@!$s3->listBuckets())
				return false;	
		} catch(Exception $e) {
			return false;
		}
		
		return true;
	}
	
	function renderConfig(Model_DevblocksStorageProfile $profile) {
		$tpl = DevblocksPlatform::getTemplateService();
		$path = dirname(dirname(__FILE__)) . '/templates';
		
		$tpl->assign('profile', $profile);
		
		$tpl->display("file:{$path}/storage_engine/config/s3.tpl");
	}
	
	function saveConfig(Model_DevblocksStorageProfile $profile) {
		@$access_key = DevblocksPlatform::importGPC($_POST['access_key'],'string','');
		@$secret_key = DevblocksPlatform::importGPC($_POST['secret_key'],'string','');
		@$bucket = DevblocksPlatform::importGPC($_POST['bucket'],'string','');
		
		$fields = array(
			DAO_DevblocksStorageProfile::PARAMS_JSON => json_encode(array(
				'access_key' => $access_key,
				'secret_key' => $secret_key,
				'bucket' => $bucket,
			)),
		);
		
		DAO_DevblocksStorageProfile::update($profile->id, $fields);
	}
	
	public function exists($namespace, $key) {
		@$bucket = $this->_options['bucket'];
		return false !== ($info = $this->_s3->getObjectInfo($bucket, $key));
	}
	
	public function put($namespace, $id, $data) {
		@$bucket = $this->_options['bucket'];
		
		// Get a unique hash path for this namespace+id
		$hash = base_convert(sha1($this->escapeNamespace($namespace).$id), 16, 32);
		$path = sprintf("%s/%s/%s/%d",
			$this->escapeNamespace($namespace),
			substr($hash,0,1),
			substr($hash,1,1),
			$id
		);
		
		if(is_resource($data)) {
			// Write the content from stream
			$stat = fstat($data);
			if(false === $this->_s3->putObject($this->_s3->inputResource($data, $stat['size']), $bucket, $path, S3::ACL_PRIVATE)) {
				return false;
			}
		} else {
			// Write the content from string
			if(false === $this->_s3->putObject($data, $bucket, $path, S3::ACL_PRIVATE)) {
				return false;
			}
		}
		
		return $path;
	}

	public function get($namespace, $key, &$fp=null) {
		@$bucket = $this->_options['bucket'];
		
		if($fp && is_resource($fp)) {
			// Use the filename rather than $fp because the S3 lib will fclose($fp)
			$tmpfile = DevblocksPlatform::getTempFileInfo($fp);
			if(false !== ($tmp = $this->_s3->getObject($bucket, $key, $tmpfile))) {
				fseek($fp, 0);
				return true;
			}
			
		} else {
			if(false !== ($object = $this->_s3->getObject($bucket, $key))
				&& isset($object->body))
				return $object->body;
		}
			
		return false;
	}
	
	public function delete($namespace, $key) {
		@$bucket = $this->_options['bucket'];

		return $this->_s3->deleteObject($bucket, $key);
	}	
};
