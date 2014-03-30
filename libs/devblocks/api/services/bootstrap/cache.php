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
	
	public function getEngine() {
		return self::$_cacher;
	}
	
	public function setEngine($extension_id, $config) {
		// If it's the same, ignore.
		if(self::$_cacher->id == $extension_id)
			return;
		
		if(false !== ($ext = Extension_DevblocksCacheEngine::get($extension_id))) {
			if($ext->setConfig($config))
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
		$manifest->name = 'Filesystem';
		$manifest->params = array();
		
		$class_name = $manifest->class;

		if(!class_exists($class_name, true)) {
			return null;
		}
		
		if(false == ($ext = new $class_name($manifest))
			|| false == ($ext->setConfig(array())))
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
			self::$_bootstrap_cacher->clean();
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
			case 'devblocks:plugin:devblocks.core:params':
			case 'devblocks_extensions':
			case 'devblocks_plugins':
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
	
	function setConfig(array $config) {
		if(!isset($config['cache_dir']))
			$config['cache_dir'] = APP_TEMP_PATH . DIRECTORY_SEPARATOR;
		
		if(!isset($config['key_prefix']))
			$config['key_prefix'] = 'cache--';
		
		if(true !== ($result = self::testConfig($config))) {
			trigger_error($result, E_USER_WARNING);
			return false;
		}
		
		$this->_config = $config;
		return true;
	}
	
	function testConfig(array $config) {
		if(!isset($config['cache_dir']))
			$config['cache_dir'] = APP_TEMP_PATH . DIRECTORY_SEPARATOR;
		
		if(!isset($config['key_prefix']))
			$config['key_prefix'] = 'cache--';
		
		if(!isset($config['cache_dir']) || empty($config['cache_dir']))
			return "DevblocksCacheEngine_Disk requires the 'cache_dir' option.";

		if(!is_writeable($config['cache_dir']))
			return sprintf("Devblocks requires write access to %s", $config['cache_dir']);
		
		return true;
	}
	
	function renderConfig() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('cacher', $this);
		$tpl->assign('cacher_config', $this->getConfig());
		$tpl->display('devblocks:devblocks.core::cache_engine/disk/config.tpl');
	}
	
	function renderStatus() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('cacher', $this);
		$tpl->assign('cacher_config', $this->getConfig());
		$tpl->display('devblocks:devblocks.core::cache_engine/disk/status.tpl');
	}
	
	private function _getFilename($key) {
		@$key_prefix = $this->_config['key_prefix'];
		
		$safe_key = preg_replace("/[^A-Za-z0-9_\-]/",'_', $key);
		return $key_prefix . $safe_key;
	}
	
	function load($key) {
		@$cache_dir = $this->_config['cache_dir'];
		
		if(empty($cache_dir))
			return NULL;
		
		$wrapper = @unserialize(file_get_contents($cache_dir . $this->_getFilename($key)));
		
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
		@$cache_dir = $this->_config['cache_dir'];
		
		if(empty($cache_dir))
			return false;
		
		$wrapper = array(
			'data' => $data,
		);
		
		// Are we setting a lifetime?
		if(!empty($lifetime)) {
			$wrapper['cache_until'] = time() + $lifetime;
		}
		
		$full_path_to_cache_file = $cache_dir . $this->_getFilename($key);
		
		file_put_contents($full_path_to_cache_file, serialize($wrapper));
		
		
		return true;
	}
	
	function remove($key) {
		@$cache_dir = $this->_config['cache_dir'];
		
		if(empty($cache_dir))
			return false;
		
		$file = $cache_dir . $this->_getFilename($key);
		if(file_exists($file) && is_writeable($file))
			@unlink($file);
	}
	
	function clean() {
		@$cache_dir = $this->_config['cache_dir'];
		
		$files = scandir($cache_dir);
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