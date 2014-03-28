<?php
class _DevblocksDatabaseManager {
	private $_db = null;
	static $instance = null;
	
	private function __construct(){
		// [TODO] Implement proper pconnect abstraction for mysqli
		$persistent = (defined('APP_DB_PCONNECT') && APP_DB_PCONNECT) ? true : false;
		
		if(false == ($conn = $this->connect(APP_DB_HOST, APP_DB_USER, APP_DB_PASS, APP_DB_DATABASE, $persistent))) {
			die("[Cerb] Error connecting to the database.  Please check MySQL and the framework.config.php settings.");
		}
	}
	
	static function getInstance() {
		if(null == self::$instance) {
			// Bail out early for pre-install
			if('' == APP_DB_DRIVER || '' == APP_DB_HOST)
				return null;
			
			self::$instance = new _DevblocksDatabaseManager();
		}
		
		return self::$instance;
	}
	
	function connect($host, $user, $pass, $database, $persistent=false) {
		if($persistent)
			$host = 'p:' . $host;
		
		if(false === ($this->_db = @mysqli_connect($host, $user, $pass, $database)))
			return false;

		// Encoding
		//mysqli_set_charset(DB_CHARSET_CODE, $this->_db);
		$this->Execute('SET NAMES ' . DB_CHARSET_CODE);
		
		return true;
	}
	
	function getConnection() {
		return $this->_db;
	}
	
	function isConnected() {
		if(!($this->_db instanceof mysqli)) {
			$this->_db = null;
			return false;
		}
		return mysqli_ping($this->_db);
	}
	
	function metaTables() {
		$tables = array();
		
		$sql = "SHOW TABLES";
		$rs = $this->GetArray($sql);
		
		foreach($rs as $row) {
			$table = array_shift($row);
			$tables[$table] = $table;
		}
		
		return $tables;
	}
	
	function metaTable($table_name) {
		$columns = array();
		$indexes = array();
		
		$sql = sprintf("SHOW COLUMNS FROM %s", $table_name);
		$rs = $this->GetArray($sql);
		
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
		$rs = $this->GetArray($sql);

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
			);
		}
		
		return array(
			$columns,
			$indexes
		);
	}
	
	function Execute($sql) {
		if(false === ($rs = mysqli_query($this->_db, $sql))) {
			error_log(sprintf("[%d] %s ::SQL:: %s",
				mysqli_errno(),
				mysqli_error(),
				$sql
			));
			return false;
		}
		
		return $rs;
	}
	
	function SelectLimit($sql, $limit, $start=0) {
		$limit = intval($limit);
		$start = intval($start);
		
		if($limit > 0)
			return $this->Execute($sql . sprintf(" LIMIT %d,%d", $start, $limit));
		else
			return $this->Execute($sql);
	}
	
	function escape($string) {
		return mysqli_real_escape_string($this->_db, $string);
	}
	
	function qstr($string) {
		return "'".mysqli_real_escape_string($this->_db, $string)."'";
	}
	
	function GetArray($sql) {
		$results = array();
		
		if(false !== ($rs = $this->Execute($sql))) {
			while($row = mysqli_fetch_assoc($rs)) {
				$results[] = $row;
			}
			mysqli_free_result($rs);
		}
		
		return $results;
	}
	
	function GetRow($sql) {
		if($rs = $this->Execute($sql)) {
			$row = mysqli_fetch_assoc($rs);
			mysqli_free_result($rs);
			return $row;
		}
		return false;
	}

	function GetOne($sql) {
		if(false !== ($rs = $this->Execute($sql))) {
			$row = mysqli_fetch_row($rs);
			mysqli_free_result($rs);
			return $row[0];
		}
		
		return false;
	}

	function LastInsertId() {
		return mysqli_insert_id($this->_db);
	}
	
	function Affected_Rows() {
		return mysqli_affected_rows($this->_db);
	}
	
	function ErrorMsg() {
		return mysqli_error($this->_db);
	}
};