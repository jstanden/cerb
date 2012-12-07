<?php
class _DevblocksDatabaseManager {
	private $_db = null;
	static $instance = null;
	
	private function __construct(){
		// [TODO] Implement proper pconnect abstraction for mysqli
		$persistent = (defined('APP_DB_PCONNECT') && APP_DB_PCONNECT) ? true : false;
		$this->Connect(APP_DB_HOST, APP_DB_USER, APP_DB_PASS, APP_DB_DATABASE, $persistent);
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
	
	function Connect($host, $user, $pass, $database, $persistent=false) {
		if(false === (@$this->_db = mysql_pconnect($host, $user, $pass, !$persistent)))
			return false;

		if(false === mysql_select_db($database, $this->_db)) {
			return false;
		}
		
		// Encoding
		//mysql_set_charset(DB_CHARSET_CODE, $this->_db);
		$this->Execute('SET NAMES ' . DB_CHARSET_CODE);
		
		return true;
	}
	
	function getConnection() {
		return $this->_db;
	}
	
	function isConnected() {
		if(!is_resource($this->_db)) {
			$this->_db = null;
			return false;
		}
		return mysql_ping($this->_db);
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
		if(false === ($rs = mysql_query($sql, $this->_db))) {
			error_log(sprintf("[%d] %s ::SQL:: %s",
				mysql_errno(),
				mysql_error(),
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
		return mysql_real_escape_string($string, $this->_db);
	}
	
	function qstr($string) {
		return "'".mysql_real_escape_string($string, $this->_db)."'";
	}
	
	function GetArray($sql) {
		$results = array();
		
		if(false !== ($rs = $this->Execute($sql))) {
			while($row = mysql_fetch_assoc($rs)) {
				$results[] = $row;
			}
			mysql_free_result($rs);
		}
		
		return $results;
	}
	
	function GetRow($sql) {
		if($rs = $this->Execute($sql)) {
			$row = mysql_fetch_assoc($rs);
			mysql_free_result($rs);
			return $row;
		}
		return false;
	}

	function GetOne($sql) {
		if(false !== ($rs = $this->Execute($sql))) {
			$row = mysql_fetch_row($rs);
			mysql_free_result($rs);
			return $row[0];
		}
		
		return false;
	}

	function LastInsertId() {
		return mysql_insert_id($this->_db);
	}
	
	function Affected_Rows() {
		return mysql_affected_rows($this->_db);
	}
	
	function ErrorMsg() {
		return mysql_error($this->_db);
	}
};