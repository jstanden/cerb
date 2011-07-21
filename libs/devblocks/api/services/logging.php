<?php
class _DevblocksLogManager {
	static $_instance = null;
	private $_prefix = '';
	
    // Used the ZF classifications
	private static $_log_levels = array(
		'emerg' => 0,		// Emergency: system is unusable
		'emergency' => 0,	
		'alert' => 1,		// Alert: action must be taken immediately
		'crit' => 2,		// Critical: critical conditions
		'critical' => 2,	
		'err' => 3,			// Error: error conditions
		'error' => 3,		
		'warn' => 4,		// Warning: warning conditions
		'warning' => 4,		
		'notice' => 5,		// Notice: normal but significant condition
		'info' => 6,		// Informational: informational messages
		'debug' => 7,		// Debug: debug messages
	);

	private $_log_level = 0;
	private $_fp = null;
	
	static function getConsoleLog($prefix='') {
		if(null == self::$_instance) {
			self::$_instance = new _DevblocksLogManager();
		}
		
		self::$_instance->setPrefix($prefix);
		
		return self::$_instance;
	}
	
	private function __construct() {
		// Allow query string overloading Devblocks-wide
		@$log_level = DevblocksPlatform::importGPC($_REQUEST['loglevel'],'integer', 0);
		$this->_log_level = intval($log_level);
		
		// Open file pointer
		$this->_fp = fopen('php://output', 'w+');
	}

	public function getLogLevel() {
		return $this->_log_level;
	}
	
	public function setLogLevel($log_level) {
		$old = $this->_log_level;
		$this->_log_level = intval($log_level);
		return $old;
	}
	
	public function setPrefix($prefix='') {
		$this->_prefix = $prefix;
	}
	
	public function __destruct() {
		@fclose($this->_fp);	
	}	
	
	public function __call($name, $args) {
		if(empty($args))
			$args = array('');
			
		if(isset(self::$_log_levels[$name])) {
			if(self::$_log_levels[$name] <= $this->_log_level) {
				$out = sprintf("[%s] %s%s<BR>\n",
					strtoupper($name),
					(!empty($this->_prefix) ? ('['.$this->_prefix.'] ') : ''),
					$args[0]
				);
				fputs($this->_fp, $out);
			}
		}
	}
};