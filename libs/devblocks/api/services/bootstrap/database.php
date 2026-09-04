<?php
class _DevblocksDatabaseManager {
	static $instance = null;
	
	private $_connections = [];
	private $_last_used_db = null;
	private $_has_written = false;
	private $_timezone = null;
	private $_timezone_stack = [];
	
	const OPT_NO_READ_AFTER_WRITE = 1;
	
	private function __construct() {
		// We lazy load the connections
	}
	
	public function __get($name) {
		switch($name) {
			case '_master_db':
				return $this->_connectMaster();
				
			case '_reader_db':
				return $this->_connectReader();
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
		
		$persistent = defined('APP_DB_PCONNECT') && APP_DB_PCONNECT;
		
		while(false === ($db = $this->_connect(APP_DB_HOST, APP_DB_USER, APP_DB_PASS, APP_DB_DATABASE, APP_DB_PORT, $persistent, APP_DB_OPT_MASTER_CONNECT_TIMEOUT_SECS, APP_DB_OPT_MASTER_READ_TIMEOUT_SECS))) {
			// Are we out of retries?
			if(--$retries < 0) {
				error_log(sprintf("[Cerb] Error connecting to the master database (%s). Please check MySQL and the framework.config.php settings.", APP_DB_HOST), E_USER_ERROR);
				CerberusApplication::respondWithErrorReason(CerbErrorReason::DatabaseConnectionError);
			}
			
			DevblocksPlatform::logError('Master connection failed, retrying...');
			
			// Wait between retries
			usleep($retries_interval_ms * 1000);
		}
		
		$this->_connections['master'] = $db;
		
		return $db;
	}
	
	private function _resetReader() {
		if($this->_connections['master'] == $this->_connections['reader'])
			unset($this->_connections['master']);
		
		unset($this->_connections['reader']);
		unset($this->_master_db);
		unset($this->_reader_db);
	}
	
	private function _connectReader() {
		// Reuse an existing connection for this request
		if(isset($this->_connections['reader']))
			return $this->_connections['reader'];
		
		// Use the master if we don't have a reader endpoint defined
		if(!defined('APP_DB_READER_HOST') || !APP_DB_READER_HOST) {
			return $this->_redirectReaderToMaster();
		}
		
		// Inherit the user/pass from the master if not specified
		$persistent = (defined('APP_DB_PCONNECT') && APP_DB_PCONNECT) ? true : false;
		$user = (defined('APP_DB_READER_USER') && APP_DB_READER_USER) ? APP_DB_READER_USER : APP_DB_USER;
		$pass = (defined('APP_DB_READER_PASS') && APP_DB_READER_PASS) ? APP_DB_READER_PASS : APP_DB_PASS;
		
		if(!($db = $this->_connect(APP_DB_READER_HOST, $user, $pass, APP_DB_DATABASE, APP_DB_PORT, $persistent, APP_DB_OPT_READER_CONNECT_TIMEOUT_SECS, APP_DB_OPT_READER_READ_TIMEOUT_SECS))) {
			// [TODO] Cache reader failure for (n) seconds to retry, preventing spam hell on retry connections
			error_log(sprintf("[Cerb] Error connecting to the reader database (%s).", APP_DB_READER_HOST), E_USER_ERROR);
			return $this->_redirectReaderToMaster();
		}
		
		$this->_connections['reader'] = $db;
		
		return $db;
	}
	
	private function _connectNewReader() {
		// Inherit the user/pass from the master if not specified
		$host = APP_DB_READER_HOST ?: APP_DB_HOST;
		$port = APP_DB_READER_PORT ?: APP_DB_PORT;
		$user = APP_DB_READER_USER ?: APP_DB_USER;
		$pass = APP_DB_READER_PASS ?: APP_DB_PASS;
		
		if(!($db = $this->_connect($host, $user, $pass, APP_DB_DATABASE, $port, APP_DB_PCONNECT, APP_DB_OPT_READER_CONNECT_TIMEOUT_SECS, APP_DB_OPT_READER_READ_TIMEOUT_SECS))) {
			error_log(sprintf("[Cerb] Error connecting to a reader host (%s).", $host), E_USER_ERROR);
			return false;
		}
		
		// Match the current timezone if set
		if($this->_timezone) {
			$this->_SetReaderTimezone($db, $this->_timezone);
		}
		
		return $db;
	}	
	
	private function _redirectReaderToMaster() {
		if($master = $this->_connectMaster())
			$this->_connections['reader'] = $master;
		
		return $master;
	}
	
	private function _connect($host, $user, $pass, $database, $port=null, $persistent=false, $connect_timeout=3, $read_timeout=30) {
		$db = mysqli_init();
		
		mysqli_options($db, MYSQLI_OPT_CONNECT_TIMEOUT, $connect_timeout);
		mysqli_options($db, MYSQLI_OPT_READ_TIMEOUT, $read_timeout);
		mysqli_report(MYSQLI_REPORT_ERROR);
		
		if($persistent)
			$host = 'p:' . $host;
		
		$port = $port ? intval($port) : null;
		
		if(!@mysqli_real_connect($db, $host, $user, $pass, $database, $port))
			return false;
		
		// Set the character encoding for this connection
		$charset = DB_CHARSET_CODE;
		
		// Upgrade utf8 to utf8mb4
		if(DevblocksPlatform::strLower($charset) == 'utf8') {
			$charset = 'utf8mb4';
		}
		
		mysqli_set_charset($db, $charset);
		
		return $db;
	}
	
	/**
	 * @return mysqli|false
	 */
	function getMasterConnection() {
		return $this->_master_db;
	}
	
	/**
	 * @return mysqli|false
	 */
	function getReaderConnection() {
		return $this->_reader_db;
	}
	
	/**
	 * @return mysqli|false
	 */
	function getNewReaderConnection() {
		return $this->_connectNewReader();
	}
	
	function isConnected() {
		if(empty($this->_connections))
			return false;
		
		foreach($this->_connections as $conn) {
			if(!$conn instanceof mysqli)
				return false;
			
			if(!$this->_ping($conn))
				return false;
		}
		
		return true;
	}
	
	private function _ping($conn) : bool {
		try {
			return @mysqli_query($conn, 'DO 1') !== false;
		} catch(mysqli_sql_exception) {
			return false;
		}
	}
	
	// Always master
	function metaTables() {
		$tables = [];
		
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
		$tables = [];
		
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
		$columns = [];
		$indexes = [];
		
		$sql = sprintf("SHOW FULL COLUMNS FROM %s", $table_name);

        if(!($rs = $this->GetArrayMaster($sql)))
            return [$columns, $indexes];
		
		foreach($rs as $row) {
			$field = $row['Field'];
			
			$columns[$field] = [
				'field' => $field,
				'type' => $row['Type'],
				'collation' => $row['Collation'],
				'null' => $row['Null'],
				'key' => $row['Key'],
				'default' => $row['Default'],
				'extra' => $row['Extra'],
			];
		}
		
		$sql = sprintf("SHOW INDEXES FROM %s", $table_name);
		$rs = $this->GetArrayMaster($sql);

		foreach($rs as $row) {
			$key_name = $row['Key_name'];
			$column_name = $row['Column_name'];

			if(!isset($indexes[$key_name]))
				$indexes[$key_name] = [
					'columns' => [],
				];
			
			$indexes[$key_name]['columns'][$column_name] = [
				'column_name' => $column_name,
				'cardinality' => $row['Cardinality'],
				'index_type' => $row['Index_type'],
				'subpart' => $row['Sub_part'],
				'unique' => empty($row['Non_unique']),
			];
		}
		
		return [$columns, $indexes];
	}
	
	function dumpSchemaKata($as_string=true) : string|array {
		$kata = DevblocksPlatform::services()->kata();
		$output = ['tables' => []];
		
		foreach($this->metaTables() as $table_name) {
			$table_output = [];
			
			$table_data = $this->metaTable($table_name);
			
			$table_output['columns'] = array_map(
				function($column) {
					// `nullable` replaces the raw `null` in place; the schema reference orders keys
					// field, type, collation, nullable, key, default, extra
					$output = [];

					foreach($column as $column_key => $column_value) {
						if('null' == $column_key) {
							$output['nullable'] = strval($column_value) == 'NO' ? 'NOT NULL' : 'NULL';
						} else {
							$output[$column_key] = $column_value;
						}
					}

					$output['collation'] = strval($output['collation'] ?? '');
					$output['default'] = strval($output['default'] ?? '');

					// Normalize ints by removing length (MySQL 8+)
					if(DevblocksPlatform::strStartsWith($output['type'] ?? '', ['tinyint(','smallint(','int(','mediumint(','bigint('])) {
						$output['type'] = preg_replace('#\(\d+\)#', '', $output['type']);
					}

					return $output;
				},
				$table_data[0]
			);
			
			$table_output['indexes'] = array_map(
				function($index) {
					foreach(array_keys($index['columns'] ?? []) as $column_name) {
						$index['columns'][$column_name]['subpart'] = strval($index['columns'][$column_name]['subpart']);
						$index['columns'][$column_name]['unique'] = strval($index['columns'][$column_name]['unique']);
						unset($index['columns'][$column_name]['cardinality']);
					}
					return $index;
				},
				$table_data[1]
			);
			
			ksort($table_output['columns']);
			ksort($table_output['indexes']);
			
			$output['tables'][$table_name] = $table_output;
		}
		
		ksort($output['tables']);
		
		return $as_string ? $kata->emit($output) : $output;
	}
	
	/**
	 * @param string $sql
	 * @param int $option_bits
	 * @return mysqli_result|false
	 */
	function ExecuteMaster($sql, $option_bits = 0) {
		return $this->ExecuteWriter($sql, $option_bits);
	}
	
	function ExecuteWriter($sql, $option_bits = 0) {
		if(DEVELOPMENT_MODE_QUERIES)
			DevblocksPlatform::services()->log('MASTER');
		
		if(APP_DB_OPT_READ_MASTER_AFTER_WRITE && '' != APP_DB_READER_HOST) {
			// If we're ignoring master read-after-write, do nothing
			if($option_bits & _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE) {
				//error_log(sprintf("Ignoring master read-after-write: %s", $sql));
				
			// Otherwise, if we've written to master then start redirecting reads to master
			} else if(!$this->_has_written) {
				$cache = DevblocksPlatform::services()->cache();
				$local_only = !$cache->isVolatile() || !isset($_COOKIE['Cerb']);
		
				$cache_key = 'session:db:last_write:' . session_id();
				//error_log(sprintf("Write to master (%s): %s", $cache_key, $sql));
				$cache->save(time(), $cache_key, [], APP_DB_OPT_READ_MASTER_AFTER_WRITE, $local_only);
				$this->_has_written = true;
			}
		}
		
		return $this->_Execute($sql, $this->_master_db);
	}
	
	function ExecuteWriterOrFail($sql, $fail_message = 'A required database query failed. Check the log for more details.', $option_bits = 0) {
		if(false === ($result = $this->ExecuteWriter($sql, $option_bits)))
			DevblocksPlatform::dieWithHttpError($fail_message);
		
		return $result;
	}
	
	function QueryReader($sql) {
		$db = $this->_reader_db;
		
		// Check if we're redirecting read-after-write to master
		if(APP_DB_OPT_READ_MASTER_AFTER_WRITE && '' != APP_DB_READER_HOST) {
			$cache = DevblocksPlatform::services()->cache();
			/*
			 * Only perform READ_MASTER_AFTER_WRITE across HTTP requests if we have a high performing 
			 * cache and an active worker session. Otherwise only cache for this request.
			 */
			$local_only = !$cache->isVolatile() || !isset($_COOKIE['Cerb']);
			$cache_key = 'session:db:last_write:' . session_id();
			
			// If we've already executed DML this request, or another request has recently, redirect reads to master
			if($this->_has_written || (false != ($cache->load($cache_key, false, $local_only)))) {
				//error_log(sprintf("Redirecting read-after-write to master (%s): %s", $cache_key, $sql));
				$db = $this->_master_db;
				$this->_has_written = true;
			}
		}
		
		if(DEVELOPMENT_MODE_QUERIES)
			DevblocksPlatform::services()->log('READER');
		
		return $this->_Execute($sql, $db);
	}
	
	/**
	 * @param string|string[] $sqls
	 * @param int|int[] $time_limit_ms
	 * @return mysqli_result[]|mysqli_result|false
	 */
	function QueryReaderAsync($sqls, $time_limit_ms=10000) {
		$return_single = false;
		
		if(is_string($sqls)) {
			$return_single = true;
			$sqls = [$sqls];
		}
		
		if(!is_array($sqls))
			return false;
		
		if(is_string($time_limit_ms) || is_numeric($time_limit_ms)) {
			$time_limits = array_fill(0, count($sqls), $time_limit_ms);
		} else if (is_array($time_limit_ms)) {
			$time_limits = array_pad($time_limit_ms, count($sqls), current($time_limit_ms));
		} else {
			$time_limits = array_fill(0, count($sqls), 10000);
		}
		
		$started_at = microtime(true) * 1000;
		
		$results = [];
		$connections = [];
		
		foreach($sqls as $idx => $sql) {
			if(0 == $idx) {
				$db = $this->getReaderConnection();
			} else {
				$db = $this->getNewReaderConnection();
			}
			
			if(!($db instanceof mysqli))
				return false;
			
			mysqli_query($db, $sql, MYSQLI_ASYNC);
			$connections[] = $db;
			$results[$db->thread_id] = false;
		}
		
		do {
			$elapsed_ms = (microtime(true) * 1000) - $started_at;
			
			// Close any incomplete connections
			foreach($connections as $idx => $db) {
				// If we timed out on this query
				if(false === $results[$db->thread_id] && $elapsed_ms >= $time_limits[$idx]) {
					// Open a new connection to control the other threads
					$monitor_db = $this->getNewReaderConnection();
					
					// Mark the thread as timed out
					$results[$db->thread_id] = new Exception_DevblocksDatabaseQueryTimeout();
					
					DevblocksPlatform::logError(
						sprintf("Timed out ::SQL:: (%s pid:%d time:%dms) %s\n",
							APP_DB_DATABASE,
							$db->thread_id,
							$time_limits[$idx],
							$sqls[$idx]
						)
					);
					
					// Kill the timed out thread using the new connection
					if(
						$monitor_db instanceof mysqli
						&& $db instanceof mysqli
					) {
						mysqli_kill($monitor_db, $db->thread_id);
						mysqli_close($db);
						mysqli_close($monitor_db);
					}
					
					unset($connections[$idx]);
					
					// If it was the main reader, reconnect
					if(0 === $idx) {
						$this->_resetReader();
					}
				}
			}
			
			$links = $errors = $rejects = [];
			
			foreach($connections as $db)
				$links[] = $errors[] = $rejects[] = $db;
			
			if($connections)
				mysqli_poll($links, $errors, $rejects, 0, 500_000);
			
			foreach ($links as $idx => $link) {
				$rs = mysqli_reap_async_query($link);
				
				// If we already have a result, skip it
				if(false !== $results[$link->thread_id]) {
					/** @noinspection PhpExpressionResultUnusedInspection */
					true;
					
				// If successful
				} else if ($rs instanceof mysqli_result) {
					$results[$link->thread_id] = $rs;
					
				// If an error
				} else {
					$mysql_errno = mysqli_errno($link);
					$mysql_error = mysqli_error($link);
					
					$results[$link->thread_id] = new Exception_DevblocksDatabaseQueryError();
					
					$error_msg = sprintf("[%d] %s ::SQL:: %s\n",
						$mysql_errno,
						$mysql_error,
						$sqls[$idx]
					);
					
					DevblocksPlatform::logError($error_msg);
				}
			}
			
			$processed = count(array_filter($results, fn($res) => $res !== false));
			
		} while ($processed < count($sqls));
		
		if($return_single) {
			return array_shift($results);
		} else {
			return array_values($results);
		}
	}
	
	private function _IsWriter($db) : bool {
		return spl_object_hash($db) == spl_object_hash($this->_connections['master']);
	}
	
	private function _Reconnect($db) : bool {
		// Reconnect
		if($this->_IsWriter($db)) {
			unset($this->_connections['master']);
			$master_db = $this->_connectMaster(APP_DB_OPT_CONNECTION_RECONNECTS, APP_DB_OPT_CONNECTION_RECONNECTS_WAIT_MS);
			$db = $master_db;
			
		} else {
			unset($this->_connections['reader']);
			$reader_db = $this->_connectReader();
			$db = $reader_db;
		}
		
		if(!($db instanceof mysqli) || !$this->_ping($db))
			return false;
		
		return true;
	}
	
	private function _Execute($sql, $db, $option_bits = 0) {
		if(DEVELOPMENT_MODE_QUERIES) {
			if($console = DevblocksPlatform::services()->log(null))
				$console->debug($sql);
		}
		
		$this->_last_used_db = $db;

		if(false === ($rs = mysqli_query($db, $sql))) {
			$mysql_errno = mysqli_errno($db);
			$mysql_error = mysqli_error($db);
			
			// MySQL server has gone away (likely a read timeout)
			if (2006 == $mysql_errno) {
				DevblocksPlatform::logError(sprintf("[%d] Read Timeout ::SQL:: %s",
					$mysql_errno,
					$sql
				));
				
				$this->_Reconnect($db);
				
				return false;
				
			// If the DB is down, try to reconnect
			} else if(!$this->_ping($db)) {
				DevblocksPlatform::logError("The MySQL connection closed prematurely.");
				
				$this->_Reconnect($db);
				
				// Try again after the reconnection
				if(!$this->_ping($db) || false === ($rs = mysqli_query($db, $sql))) {
					DevblocksPlatform::logError('Failed to reconnect to the database.');
					
					$error_msg = sprintf("[%d] %s ::SQL:: %s",
						$mysql_errno,
						$mysql_error,
						$sql
					);

					DevblocksPlatform::logError($error_msg, true);
					return false;
				}
				
			} else {
				$error_msg = sprintf("[%d] %s ::SQL:: %s",
					$mysql_errno,
					$mysql_error,
					$sql
				);
				
				DevblocksPlatform::logError($error_msg);
				return false;
			}
		}
		
		return $rs;
	}
	
	function escape($string) {
		return mysqli_real_escape_string($this->_reader_db, $string);
	}
	
	function escapeArray(array $array) {
		$results = [];

		foreach($array as $string) {
			if(!is_string($string))
				$string = strval($string);
			
			$results[] = mysqli_real_escape_string($this->_reader_db, $string);
		}
		
		return $results;
	}
	
	/**
	 * MySQL user-level locks are SERVER-WIDE, not per-schema: GET_LOCK('x') from two databases on one
	 * server is the SAME lock. Cerb Cloud runs multi-tenant masters, so an unprefixed name silently
	 * gives every tenant on a master ONE shared pool -- a capacity floor with no symptom on any page,
	 * which is the same undiagnosable shape as the isLicensed() bug.
	 *
	 * Every lock therefore goes through getLockName(). Callers name a logical lock ('slot:5'); only
	 * this layer knows a tenant exists.
	 *
	 * The schema is HASHED rather than used raw so the prefix costs a fixed 9 bytes against the 64
	 * character limit no matter how long a tenant database name is. 32 bits over the ~30 schemas a
	 * master carries makes collision negligible, and it stays recoverable by hand:
	 * substr(hash('sha256', '<schema>'), 0, 8).
	 */
	const int LOCK_NAME_MAX_LEN = 64;
	
	private ?string $_lock_prefix = null;
	
	static function getLockPrefixFor(string $schema) : string {
		return substr(hash('sha256', $schema), 0, 8) . ':';
	}
	
	/**
	 * MySQL 8 REJECTS an over-long lock name outright (ER_USER_LOCK_WRONG_NAME) rather than truncating
	 * it, and a mangled lock name is the exact failure the prefix exists to prevent -- so fold the
	 * logical half deterministically instead of trimming it.
	 *
	 * Pure and static so the naming can be pinned by tests: the CI runner has no database, and
	 * getInstance() returns null there, so anything reachable only through the instance is untestable.
	 */
	static function composeLockName(string $prefix, string $name) : string {
		if(strlen($prefix) + strlen($name) > self::LOCK_NAME_MAX_LEN)
			$name = sha1($name);
		
		return $prefix . $name;
	}
	
	private function _getLockPrefix() : string {
		return $this->_lock_prefix ??= self::getLockPrefixFor(APP_DB_DATABASE);
	}
	
	function getLockName(string $name) : string {
		return self::composeLockName($this->_getLockPrefix(), $name);
	}
	
	/**
	 * Take a named advisory lock. `$timeout` of 0 means fail immediately rather than wait.
	 *
	 * Master-only, and that is not a routing preference: GET_LOCK state lives ON the connection that
	 * took it, so an acquire and its release have to run on the same one.
	 */
	function getLock(string $name, int $timeout=0) : bool {
		return boolval($this->GetOneMaster(sprintf("SELECT GET_LOCK(%s, %d)",
			$this->qstr($this->getLockName($name)),
			max(0, $timeout)
		)));
	}
	
	function releaseLock(string $name) : void {
		$this->ExecuteMaster(sprintf("DO RELEASE_LOCK(%s)", $this->qstr($this->getLockName($name))));
	}
	
	/**
	 * Which of `$names` are held right now, as name => bool, in ONE round trip.
	 *
	 * Null means the READ failed. Callers must not fold that into "nothing is held" -- an idle-looking
	 * pool is precisely the reading that suppresses a throttle notice.
	 *
	 * @param string[] $names
	 * @return array<string,bool>|null
	 */
	function getLocksUsed(array $names) : ?array {
		if(!$names)
			return [];
		
		$terms = [];
		$keys = [];
		
		foreach(array_values($names) as $idx => $name) {
			$key = sprintf('l%d', $idx);
			$keys[$key] = $name;
			
			// IS_USED_LOCK returns the holding connection id, or NULL when free. IF(... IS NULL, 0, 1)
			// rather than `IS NOT NULL`, which cannot be combined in an expression without a syntax
			// error that fails silently here.
			$terms[] = sprintf("IF(IS_USED_LOCK(%s) IS NULL, 0, 1) AS %s",
				$this->qstr($this->getLockName($name)),
				$key
			);
		}
		
		$row = $this->GetRowMaster('SELECT ' . implode(', ', $terms));
		
		if(!is_array($row))
			return null;
		
		$used = [];
		
		foreach($keys as $key => $name)
			$used[$name] = boolval($row[$key] ?? false);
		
		return $used;
	}
	
	function qstr($string) {
		if(!is_string($string))
			$string = strval($string);

		return "'".mysqli_real_escape_string($this->_reader_db, $string)."'";
	}
	
	function qstrArray(array $array) {
		$results = [];

		foreach($array as $string) {
			if(!is_string($string))
				$string = strval($string);
			
			$results[] = "'".mysqli_real_escape_string($this->_reader_db, $string)."'";
		}
		
		return $results;
	}
	
	function GetArrayMaster($sql) {
		if(DEVELOPMENT_MODE_QUERIES)
			DevblocksPlatform::services()->log('MASTER');
		
		$rs = $this->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		
		return $this->_GetArray($rs);
	}
	
	/**
	 * @param $sql
	 * @param int $timeout
	 * @return array|bool
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	function GetArrayReader($sql, $timeout=0) {
		if(DEVELOPMENT_MODE_QUERIES)
			DevblocksPlatform::services()->log('READER');
		
		if($timeout) {
			$rs = $this->QueryReaderAsync($sql, $timeout);
			
			if($rs instanceof Exception_DevblocksDatabaseQueryTimeout)
				throw $rs;
				
		} else {
			$rs = $this->QueryReader($sql);
		}
		
		return $this->_GetArray($rs);
	}
	
	private function _GetArray($rs) {
		$results = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$results[] = $row;
		}
		
		$this->Free($rs);
		
		return $results;
	}
	
	public function GetRowMaster($sql) {
		if(DEVELOPMENT_MODE_QUERIES)
			DevblocksPlatform::services()->log('MASTER');
		
		$rs = $this->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		return $this->_GetRow($rs);
	}
	
	/**
	 * @param $sql
	 * @param int $timeout
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	public function GetRowReader($sql, $timeout=0) {
		if(DEVELOPMENT_MODE_QUERIES)
			DevblocksPlatform::services()->log('READER');
		
		if($timeout) {
			$rs = $this->QueryReaderAsync($sql, $timeout);
			
			if($rs instanceof Exception_DevblocksDatabaseQueryTimeout)
				throw $rs;
			
		} else {
			$rs = $this->QueryReader($sql);
		}
		
		return $this->_GetRow($rs);
	}
	
	private function _GetRow($rs) {
		if($rs instanceof mysqli_result) {
			$row = mysqli_fetch_assoc($rs);
			$this->Free($rs);
			return $row;
		}
		return false;
	}
	
	function GetOneMaster($sql) {
		if(DEVELOPMENT_MODE_QUERIES)
			DevblocksPlatform::services()->log('MASTER');
		
		$rs = $this->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		return $this->GetOneFromResultset($rs);
	}
	
	/**
	 * @param $sql
	 * @param int $timeout
	 * @return false|mixed
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	function GetOneReader($sql, $timeout=0) {
		if(DEVELOPMENT_MODE_QUERIES)
			DevblocksPlatform::services()->log('READER');
		
		if($timeout) {
			$rs = $this->QueryReaderAsync($sql, $timeout);
			
			if($rs instanceof Exception_DevblocksDatabaseQueryTimeout)
				throw $rs;
			
		} else {
			$rs = $this->QueryReader($sql);
		}
		
		return $this->GetOneFromResultset($rs);
	}
	
	function GetOneFromResultset($rs) {
		if($rs instanceof mysqli_result) {
			if(0 == mysqli_num_rows($rs))
				return false;
				
			$row = mysqli_fetch_row($rs);
			$this->Free($rs);
			
			if(count($row))
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
	
	function ErrorMsgReader() {
		return $this->_ErrorMsg($this->_reader_db);
	}
	
	private function _ErrorMsg($db) {
		if(!($db instanceof mysqli))
			return null;
		
		return mysqli_error($db);
	}
	
	function Free($resultsets) {
		if($resultsets instanceof mysqli_result) {
			$resultsets = [$resultsets];
		} else if(!is_array($resultsets)) {
			return false;
		}
		
		foreach($resultsets as $rs) {
			if($rs instanceof mysqli_result)
				mysqli_free_result($rs);
		}
		
		return true;
	}
	
	public function ResetReaderTimezone() {
		$db = $this->getReaderConnection();
		
		array_pop($this->_timezone_stack);
		
		if(false != ($last_timezone = current($this->_timezone_stack))) {
			$this->_SetReaderTimezone($db, $last_timezone);
			
		} else {
			if(false === mysqli_query($db, "SET @@SESSION.time_zone = @@GLOBAL.time_zone"))
				return false;
		}
		
		return true;
	}
	
	private function _SetReaderTimezone($db, $timezone) {
		if(false === mysqli_query($db, sprintf("SET @@SESSION.time_zone = %s", $this->qstr($timezone)))) {
			// If an invalid timezone then the time_zones tables probably aren't populated
			if(1298 == mysqli_errno($db)) {
				$timezone_offset = DevblocksPlatform::services()->date()->getTimezoneOffsetFromLocation($timezone);
				
				// Try a timezone offset (doesn't account for DST, but gets close enough on misconfigured systems)
				if(false === mysqli_query($db, sprintf("SET @@SESSION.time_zone = %s", $this->qstr($timezone_offset))))
					return false;
			}
		}
		
		return true;
	}
	
	public function SetReaderTimezone($timezone) {
		if(!DevblocksPlatform::services()->date()->isValidTimezoneLocation($timezone))
			return false;
		
		$this->_timezone = $timezone;
		$this->_timezone_stack[] = $timezone;
		
		$db = $this->getReaderConnection();
		
		return $this->_SetReaderTimezone($db, $timezone);
	}
};