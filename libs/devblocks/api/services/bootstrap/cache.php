<?php
class _DevblocksCacheManager {
	private static $instance = null;
	private static $_cacher = null;
	private static $_bootstrap_cacher = null;
	private $_registry = array();
	private $_statistics = array();
	private $_io_reads_long = 0;
	private $_io_reads_short = 0;
	private $_io_writes = 0;

	/**
	* @return _DevblocksCacheManager
	*/
	public static function getInstance() {
		if(null == self::$instance)
			self::$instance = new _DevblocksCacheManager();
		
		return self::$instance;
	}
	
	public function setEngine($extension_id) {
		// If it's the same, ignore.
		if(self::$_cacher->id == $extension_id)
			return;
		
		if(false !== ($ext = Extension_DevblocksCacheEngine::get($extension_id))) {
			if($ext->init())
				self::$_cacher = $ext;
		}
	}
	
	private function __construct() {
		// Default to disk, and load directly without platform (bootstrap)
		$manifest = new DevblocksExtensionManifest();
		$manifest->id = DevblocksCacheEngine_Disk::ID;
		$manifest->point = 'devblocks.cache.engine';
		$manifest->plugin_id = 'devblocks.core';
		$manifest->class = 'DevblocksCacheEngine_Disk';
		$manifest->file = __FILE__;
		$manifest->name = 'Disk';
		$manifest->params = array();
		
		$class_file = APP_PATH . '/' . $manifest->file;
		$class_name = $manifest->class;

		if(!class_exists($class_name, true)) {
			return null;
		}
		
		if(false == ($ext = new $class_name($manifest))
			|| false == ($ext->init()))
				die("[ERROR] Can't initialize the Devblocks cache.");
			
		// Always keep the disk cacher around, since we'll need it for the bootstrap caches
		self::$_bootstrap_cacher = $ext;
		self::$_cacher = self::$_bootstrap_cacher;
	}

	public function save($data, $key, $tags=array(), $lifetime=0) {
		if(!$this->_isCacheableByExtension($key)) {
			$engine = self::$_bootstrap_cacher;
		} else {
			$engine = self::$_cacher;
		}
		
		// Monitor short-term cache memory usage
		@$this->_statistics[$key] = intval($this->_statistics[$key]);
		$this->_io_writes++;
		$this->_registry[$key] = $data;
		return $engine->save($data, $key, $tags, $lifetime);
	}
	
	public function load($key, $nocache=false) {
		if(!$this->_isCacheableByExtension($key)) {
			$engine = self::$_bootstrap_cacher;
		} else {
			$engine = self::$_cacher;
		}
		
		// Retrieving the long-term cache
		if($nocache || !isset($this->_registry[$key])) {
			if(false === ($this->_registry[$key] = $engine->load($key)))
				return NULL;
			
			@$this->_statistics[$key] = intval($this->_statistics[$key]) + 1;
			$this->_io_reads_long++;
			return $this->_registry[$key];
		}
		
		// Retrieving the short-term cache
		if(isset($this->_registry[$key])) {
			@$this->_statistics[$key] = intval($this->_statistics[$key]) + 1;
			$this->_io_reads_short++;
			return $this->_registry[$key];
		}
		
		return NULL;
	}
	
	public function remove($key) {
		if(!$this->_isCacheableByExtension($key)) {
			$engine = self::$_bootstrap_cacher;
		} else {
			$engine = self::$_cacher;
		}
		
		if(empty($key))
			return;
		unset($this->_registry[$key]);
		unset($this->_statistics[$key]);
		$engine->remove($key);
	}
	
	public function clean() { // $mode=null
		$this->_registry = array();
		$this->_statistics = array();

		// If we have a non-bootstrap cacher, wipe the bootstrap keys too
		if(self::$_bootstrap_cacher->id != self::$_cacher->id) {
			self::remove('devblocks_classloader_map');
			self::remove('devblocks_extensions');
			self::remove('devblocks_plugins');
			self::remove('devblocks_settings');
			self::remove('devblocks_tables');
		}
		
		self::$_cacher->clean();
	}
	
	public function printStatistics() {
		arsort($this->_statistics);
		print_r($this->_statistics);
		echo "<BR>";
		echo "Reads (short): ",$this->_io_reads_short,"<BR>";
		echo "Reads (long): ",$this->_io_reads_long,"<BR>";
		echo "Writes: ",$this->_io_writes,"<BR>";
	}
	
	private function _isCacheableByExtension($key) {
		// These should always come from disk:
		switch($key) {
			case 'devblocks_classloader_map':
			case 'devblocks_extensions':
			case 'devblocks_plugins':
			case 'devblocks_settings':
			case 'devblocks_tables':
				return false;
				break;
				
			default:
				break;
		}
		
		return true;
	}
};

class DevblocksCacheEngine_Disk extends Extension_DevblocksCacheEngine {
	const ID = 'devblocks.cache.engine.disk';
	
	private $_config = array();
	
	public function __get($var) {
		if(isset($this->_config[$var]))
			return $this->_config[$var];
		
		// Lazy load variables
		switch($var) {
			case '_cache_dir':
				// Default to temp path
				$path = APP_TEMP_PATH;
				
				// Ensure we have a trailing slash
				$cache_dir = rtrim($path, "\\/") . DIRECTORY_SEPARATOR;
				$this->_config[$var] = $cache_dir;
				
				return $cache_dir;
				break;
				
			case '_key_prefix':
				$key_prefix = 'devblocks_cache---';
				$this->_config[$var] = $key_prefix;
				return $key_prefix;
				break;
		}
	}
	
	// [TODO] It's probably okay for this one to die()
	public function init() {
		$cache_dir = $this->_cache_dir;
		
		if(null == $cache_dir) {
			trigger_error("DevblocksCacheEngine_Disk requires the 'cache_dir' option.", E_USER_WARNING);
			return false;
		}

		if(!is_writeable($cache_dir)) {
			trigger_error(sprintf("DevblocksCacheEngine_Disk requires write access to the 'path' directory (%s)", $cache_dir), E_USER_WARNING);
			return false;
		}
		
		return true;
	}
	
	private function _getFilename($key) {
		$safe_key = preg_replace("/[^A-Za-z0-9_\-]/",'_', $key);
		return $this->_key_prefix . $safe_key;
	}
	
	function load($key) {
		$wrapper = @unserialize(file_get_contents($this->_cache_dir . $this->_getFilename($key)));
		
		// If this is wrapped data, check the cache expiration
		if(is_array($wrapper) && isset($wrapper['data'])) {
			if(isset($wrapper['cache_until']) && !empty($wrapper['cache_until'])) {
				// If expired, kill it
				if(intval($wrapper['cache_until']) < time()) {
					self::remove($key);
					return NULL;
				}
			}
			
			// If not expired, return the data
			return $wrapper['data'];
		}
		
		// If this wasn't wrapped, return whatever it was
		return $wrapper;
	}
	
	function save($data, $key, $tags=array(), $lifetime=0) {
		$wrapper = array(
			'data' => $data,
		);
		
		// Are we setting a lifetime?
		if(!empty($lifetime)) {
			$wrapper['cache_until'] = time() + $lifetime;
		}
		
		return file_put_contents($this->_cache_dir . $this->_getFilename($key), serialize($wrapper));
	}
	
	function remove($key) {
		$file = $this->_cache_dir . $this->_getFilename($key);
		if(file_exists($file) && is_writeable($file))
			@unlink($file);
	}
	
	function clean() {
		$path = $this->_cache_dir;
		
		$files = scandir($path);
		unset($files['.']);
		unset($files['..']);
		
		if(is_array($files))
		foreach($files as $file) {
			if(0==strcmp('devblocks_cache',substr($file,0,15))) {
				if(file_exists($path . $file) && is_writeable($path . $file))
					@unlink($path . $file);
			}
		}
		
	}
};