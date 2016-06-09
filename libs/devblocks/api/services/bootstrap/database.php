<?php
class _DevblocksDatabaseManager {
	static $instance = null;
	
	private $_connections = array();
	private $_last_used_db = null;
	private $_has_written = false;
	
	const OPT_NO_READ_AFTER_WRITE = 1;
	
	private function __construct() {
		// We lazy load the connections
	}
	
	public function __get($name) {
		switch($name) {
			case '_master_db':
				return $this->_connectMaster();
				break;
				
			case '_slave_db':
				return $this->_connectSlave();
				break;
		}
		
		return null;
	}
	
	static function getInstance() {
		if(null == self::$instance) {
			// Bail out early for pre-install
			if(!defined('APP_DB_HOST') || !APP_DB_HOST)
				return null;
			
			self::$instance = new _DevblocksDatabaseManager();
		}
		
		return self::$instance;
	}
	
	private function _connectMaster($retries=0, $retries_interval_ms=500) {
		// Reuse an existing connection for this request
		if(isset($this->_connections['master']))
			return $this->_connections['master'];
		
		$persistent = (defined('APP_DB_PCONNECT') && APP_DB_PCONNECT) ? true : false;
		
		// [TODO] Fail to read-only mode?
		while(false === ($db = $this->_connect(APP_DB_HOST, APP_DB_USER, APP_DB_PASS, APP_DB_DATABASE, $persistent, APP_DB_OPT_MASTER_CONNECT_TIMEOUT_SECS))) {
			// Are we out of retries?
			if(--$retries < 0) {
				error_log(sprintf("[Cerb] Error connecting to the master database (%s). Please check MySQL and the framework.config.php settings.", APP_DB_HOST), E_USER_ERROR);
				DevblocksPlatform::dieWithHttpError("[Cerb] Error connecting to the master database.", 500);
			}
			
			error_log('Master connection failed, retrying...');
			
			// Wait between retries
			usleep($retries_interval_ms * 1000);
		}
		
		$this->_connections['master'] = $db;
		
		return $db;
	}
	
	private function _connectSlave() {
		// Reuse an existing connection for this request
		if(isset($this->_connections['slave']))
			return $this->_connections['slave'];
		
		// Use the master if we don't have a slave defined
		if(!defined('APP_DB_SLAVE_HOST') || !APP_DB_SLAVE_HOST) {
			return $this->_redirectSlaveToMaster();
		}
		
		// Inherit the user/pass from the master if not specified
		$persistent = (defined('APP_DB_PCONNECT') && APP_DB_PCONNECT) ? true : false;
		$user = (defined('APP_DB_SLAVE_USER') && APP_DB_SLAVE_USER) ? APP_DB_SLAVE_USER : APP_DB_USER;
		$pass = (defined('APP_DB_SLAVE_PASS') && APP_DB_SLAVE_PASS) ? APP_DB_SLAVE_PASS : APP_DB_PASS;
		
		if(false == ($db = $this->_connect(APP_DB_SLAVE_HOST, $user, $pass, APP_DB_DATABASE, $persistent, APP_DB_OPT_SLAVE_CONNECT_TIMEOUT_SECS))) {
			// [TODO] Cache slave failure for (n) seconds to retry, preventing spam hell on retry connections
			error_log(sprintf("[Cerb] Error connecting to the slave database (%s).", APP_DB_SLAVE_HOST), E_USER_ERROR);
			return $this->_redirectSlaveToMaster();
		}
		
		$this->_connections['slave'] = $db;
		
		return $db;
	}
	
	private function _redirectSlaveToMaster() {
		if($master = $this->_connectMaster())
			$this->_connections['slave'] = $master;
		
		return $master;
	}
	
	private function _connect($host, $user, $pass, $database, $persistent=false, $timeout_secs=5) {
		$db = mysqli_init();
		
		mysqli_options($db, MYSQLI_OPT_CONNECT_TIMEOUT, $timeout_secs);
		
		if($persistent)
			$host = 'p:' . $host;
		
		if(!@mysqli_real_connect($db, $host, $user, $pass, $database))
			return false;
		
		// Set the character encoding for this connection
		mysqli_set_charset($db, DB_CHARSET_CODE);
		
		return $db;
	}
	
	function getMasterConnection() {
		return $this->_master_db;
	}
	
	function getSlaveConnection() {
		return $this->_slave_db;
	}
	
	function isConnected() {
		if(empty($this->_connections))
			return false;
		
		foreach($this->_connections as $conn) {
			if(!$conn instanceof mysqli || !mysqli_ping($conn))
				return false;
		}
		
		return true;
	}
	
	// Always master
	function metaTables() {
		$tables = array();
		
		$sql = "SHOW TABLES";
		$rs = $this->GetArrayMaster($sql);
		
		foreach($rs as $row) {
			$table = array_shift($row);
			$tables[$table] = $table;
		}
		
		return $tables;
	}
	
	// Always master
	function metaTablesDetailed() {
		$tables = array();
		
		$sql = "SHOW TABLE STATUS";
		$rs = $this->GetArrayMaster($sql);
		
		foreach($rs as $row) {
			$table = $row['Name'];
			$tables[$table] = $row;
		}
		
		return $tables;
	}
	
	// Always master
	function metaTable($table_name) {
		$columns = array();
		$indexes = array();
		
		$sql = sprintf("SHOW COLUMNS FROM %s", $table_name);
		$rs = $this->GetArrayMaster($sql);
		
		foreach($rs as $row) {
			$field = $row['Field'];
			
			$columns[$field] = array(
				'field' => $field,
				'type' => $row['Type'],
				'null' => $row['Null'],
				'key' => $row['Key'],
				'default' => $row['Default'],
				'extra' => $row['Extra'],
			);
		}
		
		$sql = sprintf("SHOW INDEXES FROM %s", $table_name);
		$rs = $this->GetArrayMaster($sql);

		foreach($rs as $row) {
			$key_name = $row['Key_name'];
			$column_name = $row['Column_name'];

			if(!isset($indexes[$key_name]))
				$indexes[$key_name] = array(
					'columns' => array(),
				);
			
			$indexes[$key_name]['columns'][$column_name] = array(
				'column_name' => $column_name,
				'cardinality' => $row['Cardinality'],
				'index_type' => $row['Index_type'],
				'subpart' => $row['Sub_part'],
			);
		}
		
		return array(
			$columns,
			$indexes
		);
	}
	
	/**
	 * Everything executes against the master by default
	 * 
	 * @deprecated
	 * @param string $sql
	 * @return mysql_result|boolean
	 */
	function Execute($sql) {
		return $this->ExecuteMaster($sql);
	}
	
	function ExecuteMaster($sql, $option_bits = 0) {
		if(DEVELOPMENT_MODE_QUERIES)
			$console = DevblocksPlatform::getConsoleLog('MASTER');
		
		if(APP_DB_OPT_READ_MASTER_AFTER_WRITE && '' != APP_DB_SLAVE_HOST) {
			// If we're ignoring master read-after-write, do nothing
			if($option_bits & _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE) {
				//error_log(sprintf("Ignoring master read-after-write: %s", $sql));
				
			// Otherwise, if we've written to master then start redirecting reads to master
			} else if(!$this->_has_written) {
				$cache = DevblocksPlatform::getCacheService();
				$local_only = !$cache->isVolatile() || !isset($_COOKIE['Devblocks']);
		
				$cache_key = 'session:db:last_write:' . session_id();
				//error_log(sprintf("Write to master (%s): %s", $cache_key, $sql));
				$cache->save(time(), $cache_key, array(), APP_DB_OPT_READ_MASTER_AFTER_WRITE, $local_only);
				$this->_has_written = true;
			}
		}
		
		return $this->_Execute($sql, $this->_master_db);
	}
	
	function ExecuteSlave($sql) {
		$db = $this->_slave_db;
		
		// Check if we're redirecting read-after-write to master
		if(APP_DB_OPT_READ_MASTER_AFTER_WRITE && '' != APP_DB_SLAVE_HOST) {
			$cache = DevblocksPlatform::getCacheService();
			/*
			 * Only perform READ_MASTER_AFTER_WRITE across HTTP requests if we have a high performing 
			 * cache and an active worker session. Otherwise only cache for this request.
			 */
			$local_only = !$cache->isVolatile() || !isset($_COOKIE['Devblocks']);
			$cache_key = 'session:db:last_write:' . session_id();
			
			// If we've already executed DML this request, or another request has recently, redirect reads to master
			if($this->_has_written || (false != ($last_write = $cache->load($cache_key, false, $local_only)))) {
				//error_log(sprintf("Redirecting read-after-write to master (%s): %s", $cache_key, $sql));
				$db = $this->_master_db;
				$this->_has_written = true;
			}
		}
		
		if(DEVELOPMENT_MODE_QUERIES)
			$console = DevblocksPlatform::getConsoleLog('SLAVE');
		
		return $this->_Execute($sql, $db);
	}
	
	private function _Execute($sql, $db, $option_bits = 0) {
		if(DEVELOPMENT_MODE_QUERIES) {
			if($console = DevblocksPlatform::getConsoleLog(null))
				$console->debug($sql);
		}
		
		$this->_last_used_db = $db;
		$fail_count = 0;

		if(false === ($rs = mysqli_query($db, $sql))) {
			// If the DB is down, try to reconnect
			if(!mysqli_ping($db)) {
				error_log("The MySQL connection closed prematurely.");

				// Reconnect
				if(spl_object_hash($db) == spl_object_hash($this->_connections['master'])) {
					error_log("Attempting to reconnect to master database...");
					unset($this->_connections['master']);
					$master_db = $this->_connectMaster(APP_DB_OPT_CONNECTION_RECONNECTS, APP_DB_OPT_CONNECTION_RECONNECTS_WAIT_MS);
					$db = $master_db;
					
				} else {
					error_log("Attempting to reconnect to slave database...");
					unset($this->_connections['slave']);
					$slave_db = $this->_connectSlave();
					$db = $slave_db;
				}
				
				// Try again after the reconnection
				if(false === ($rs = mysqli_query($db, $sql)))
					return false;
				
			} else {
				$error_msg = sprintf("[%d] %s ::SQL:: %s",
					mysqli_errno($db),
					mysqli_error($db),
					$sql
				);
				
				if(DEVELOPMENT_MODE && php_sapi_name() != 'cli') {
					trigger_error($error_msg, E_USER_WARNING);
				} else {
					error_log($error_msg);
				}
				
				return false;
			}
		}
		
		return $rs;
	}
	
	// Always slave
	function SelectLimit($sql, $limit, $start=0) {
		$limit = intval($limit);
		$start = intval($start);
		
		if($limit > 0)
			return $this->ExecuteSlave($sql . sprintf(" LIMIT %d,%d", $start, $limit));
		else
			return $this->ExecuteSlave($sql);
	}
	
	function escape($string) {
		return mysqli_real_escape_string($this->_slave_db, $string);
	}
	
	function qstr($string) {
		return "'".mysqli_real_escape_string($this->_slave_db, $string)."'";
	}
	
	/**
	 * Defaults to slave
	 * 
	 * @deprecated
	 * @param string $sql
	 * @return array|boolean
	 */
	function GetArray($sql) {
		return $this->GetArraySlave($sql);
	}
	
	function GetArrayMaster($sql) {
		if(DEVELOPMENT_MODE_QUERIES)
			$console = DevblocksPlatform::getConsoleLog('MASTER');
		
		$rs = $this->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		
		return $this->_GetArray($rs);
	}
	
	function GetArraySlave($sql) {
		if(DEVELOPMENT_MODE_QUERIES)
			$console = DevblocksPlatform::getConsoleLog('SLAVE');
		
		$rs = $this->ExecuteSlave($sql);
		
		return $this->_GetArray($rs);
	}
	
	private function _GetArray($rs) {
		$results = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$results[] = $row;
		}
		mysqli_free_result($rs);
		
		return $results;
	}
	
	/**
	 * Defaults to slave
	 *
	 * @deprecated
	 * @param string $sql
	 * @return array|boolean
	 */
	public function GetRow($sql) {
		return $this->GetRowSlave($sql);
	}
	
	public function GetRowMaster($sql) {
		if(DEVELOPMENT_MODE_QUERIES)
			$console = DevblocksPlatform::getConsoleLog('MASTER');
		
		$rs = $this->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		return $this->_GetRow($rs);
	}
	
	public function GetRowSlave($sql) {
		if(DEVELOPMENT_MODE_QUERIES)
			$console = DevblocksPlatform::getConsoleLog('SLAVE');
		
		$rs = $this->ExecuteSlave($sql);
		return $this->_GetRow($rs);
	}
	
	private function _GetRow($rs) {
		if($rs instanceof mysqli_result) {
			$row = mysqli_fetch_assoc($rs);
			mysqli_free_result($rs);
			return $row;
		}
		return false;
	}
	
	/**
	 * Defaults to slave
	 *  
	 * @deprecated
	 * @param string $sql
	 * @return mixed|boolean
	 */
	function GetOne($sql) {
		return $this->GetOneSlave($sql, $this->_slave_db);
	}
	
	function GetOneMaster($sql) {
		if(DEVELOPMENT_MODE_QUERIES)
			$console = DevblocksPlatform::getConsoleLog('MASTER');
		
		$rs = $this->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		return $this->_GetOne($rs);
	}
	
	function GetOneSlave($sql) {
		if(DEVELOPMENT_MODE_QUERIES)
			$console = DevblocksPlatform::getConsoleLog('SLAVE');
		
		$rs = $this->ExecuteSlave($sql);
		return $this->_GetOne($rs);
	}

	private function _GetOne($rs) {
		if($rs instanceof mysqli_result) {
			$row = mysqli_fetch_row($rs);
			mysqli_free_result($rs);
			return $row[0];
		}
		
		return false;
	}

	// Always master
	function LastInsertId() {
		return mysqli_insert_id($this->_master_db);
	}
	
	// Always master
	function Affected_Rows() {
		return mysqli_affected_rows($this->_master_db);
	}
	
	// Always last connection
	function Found_Rows() {
		$rs = $this->_Execute("SELECT FOUND_ROWS()", $this->_last_used_db);
		
		if($rs instanceof mysqli_result) {
			$row = mysqli_fetch_row($rs);
			mysqli_free_result($rs);
			return $row[0];
		}
			
		return false;
	}
	
	// By default, this reports on the last used DB connection
	function ErrorMsg() {
		return $this->_ErrorMsg($this->_last_used_db);
	}
	
	function ErrorMsgMaster() {
		return $this->_ErrorMsg($this->_master_db);
	}
	
	function ErrorMsgSlave() {
		return $this->_ErrorMsg($this->_slave_db);
	}
	
	private function _ErrorMsg($db) {
		if(!($db instanceof mysqli))
			return null;
		
		return mysqli_error($db);
	}
};