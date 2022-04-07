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
		
		if(defined('DEVBLOCKS_CACHE_ENGINE_PREVENT_CHANGE') && DEVBLOCKS_CACHE_ENGINE_PREVENT_CHANGE)
			return;
		
		if(false !== ($ext = Extension_DevblocksCacheEngine::get($extension_id))) {
			if($ext->setConfig($config))
				self::$_cacher = $ext;
		}
	}
	
	// This describes whether the cache manages its own TTL/LRU/LFU ejection
	public function isVolatile() {
		return self::$_cacher->isVolatile();
	}
	
	private function __construct() {
		$manifest = null;
		$cache_config = array();
		
		// Load the default directly without platform (bootstrap)
		if(defined('DEVBLOCKS_CACHE_ENGINE'))
		switch(DEVBLOCKS_CACHE_ENGINE) {
			case DevblocksCacheEngine_Redis::ID:
				$manifest = new DevblocksExtensionManifest();
				$manifest->id = DevblocksCacheEngine_Redis::ID;
				$manifest->point = 'devblocks.cache.engine';
				$manifest->plugin_id = 'devblocks.core';
				$manifest->class = 'DevblocksCacheEngine_Redis';
				$manifest->file = __FILE__;
				$manifest->name = 'Redis';
				$manifest->params = array();
				break;
			
			case DevblocksCacheEngine_Memcache::ID:
				$manifest = new DevblocksExtensionManifest();
				$manifest->id = DevblocksCacheEngine_Memcache::ID;
				$manifest->point = 'devblocks.cache.engine';
				$manifest->plugin_id = 'devblocks.core';
				$manifest->class = 'DevblocksCacheEngine_Memcache';
				$manifest->file = __FILE__;
				$manifest->name = 'Memcache';
				$manifest->params = array();
				break;
		}
		
		if(!$manifest) {
			// Default to disk
			$manifest = new DevblocksExtensionManifest();
			$manifest->id = DevblocksCacheEngine_Disk::ID;
			$manifest->point = 'devblocks.cache.engine';
			$manifest->plugin_id = 'devblocks.core';
			$manifest->class = 'DevblocksCacheEngine_Disk';
			$manifest->file = __FILE__;
			$manifest->name = 'Filesystem';
			$manifest->params = array();
		}
		
		// Allow options override
		if(defined('DEVBLOCKS_CACHE_ENGINE_OPTIONS')
				&& DEVBLOCKS_CACHE_ENGINE_OPTIONS
				&& false != ($options = json_decode(DEVBLOCKS_CACHE_ENGINE_OPTIONS, true)))
			$cache_config = $options;
		
		$class_name = $manifest->class;

		if(!class_exists($class_name, true)) {
			return null;
		}
		
		if(false == ($ext = new $class_name($manifest))
			|| false === ($ext->setConfig($cache_config)))
				DevblocksPlatform::dieWithHttpError("[ERROR] Can't initialize the Devblocks cache.", 500);
			
		// Always keep the disk cacher around, since we'll need it for the bootstrap caches
		self::$_bootstrap_cacher = $ext;
		self::$_cacher = self::$_bootstrap_cacher;
	}

	/**
	 * 
	 * @param mixed $data
	 * @param string $key
	 * @param array $tags
	 * @param integer $ttl
	 * @param boolean $local_only
	 * @return boolean
	 * @test DevblocksCacheTest
	 */
	public function save($data, $key, $tags=array(), $ttl=0, $local_only=false) {
		// Monitor short-term cache memory usage
		$this->_statistics[$key] = intval($this->_statistics[$key] ?? 0);
		$this->_io_writes++;
		$this->_registry[$key] = $data;
		
		if($local_only)
			return true;
		
		if(!$this->_isCacheableByExtension($key)) {
			$engine = self::$_bootstrap_cacher;
		} else {
			$engine = self::$_cacher;
		}
		
		return $engine->save($data, $key, $tags, $ttl);
	}
	
	public function getTagVersion($tag) {
		$cache_key = 'tag:' . $tag;
		
		if(false == ($ts = $this->load($cache_key))) {
			$ts = time();
			$this->save($ts, $cache_key);
		}
		
		return $ts;
	}

	/**
	 * 
	 * @param string $key
	 * @param boolean $nocache
	 * @param boolean $local_only
	 * @return mixed
	 * @test DevblocksCacheTest
	 */
	public function load($key, $nocache=false, $local_only=false) {
		// If this is a local request, only try the registry, not cache
		if($local_only) {
			return $this->_loadFromLocalRegistry($key);
		}
		
		if(!$this->_isCacheableByExtension($key)) {
			$engine = self::$_bootstrap_cacher;
		} else {
			$engine = self::$_cacher;
		}
		
		$tags = [];
		
		// Retrieving the long-term cache
		if($nocache || !isset($this->_registry[$key])) {
			if(false === ($this->_registry[$key] = $engine->load($key, $tags)))
				return NULL;
			
			// Are any tags expired?
			if($tags) {
				// One at a time so we can bail out early on invalidation
				foreach($tags as $tag => $cached_at) {
					if($this->getTagVersion($tag) > $cached_at) {
						$this->remove($key);
						return NULL;
					}
				}
			}
			
			$this->_statistics[$key] = intval($this->_statistics[$key] ?? 0) + 1;
			$this->_io_reads_long++;
			return $this->_registry[$key];
		}
		
		// Try the request cache
		return $this->_loadFromLocalRegistry($key);
	}
	
	private function _loadFromLocalRegistry($key) {
		// Retrieving the short-term cache
		if(isset($this->_registry[$key])) {
			$this->_statistics[$key] = intval($this->_statistics[$key] ?? 0) + 1;
			$this->_io_reads_short++;
			return $this->_registry[$key];
		}
		
		return NULL;
	}
	
	/**
	 * 
	 * @param string $key
	 * @param boolean $local_only
	 * @return boolean
	 * @test DevblocksCacheTest
	 */
	public function remove($key, $local_only=false) {
		if(empty($key))
			return;
		
		unset($this->_registry[$key]);
		unset($this->_statistics[$key]);

		if($local_only)
			return true;
		
		if(!$this->_isCacheableByExtension($key)) {
			$engine = self::$_bootstrap_cacher;
		} else {
			$engine = self::$_cacher;
		}
		
		return $engine->remove($key);
	}
	
	public function removeByTags(array $tags) {
		foreach($tags as $tag)
			$this->remove('tag:' . $tag);
	}
	
	public function clean() {
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
	
	private function _getCacheDir() {
		return APP_TEMP_PATH . DIRECTORY_SEPARATOR;
	}
	
	private function _getCacheFileByKey($key) {
		$cache_dir = $this->_getCacheDir();
		
		if(empty($cache_dir))
			return NULL;
		
		return $cache_dir . $this->_getFilename($key);
	}
	
	function setConfig(array $config) {
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
		$cache_dir = $this->_getCacheDir();
		
		if(!is_writeable($cache_dir))
			return sprintf("Cerb requires write access to %s", $cache_dir);
		
		return true;
	}
	
	function renderConfig() {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('cacher', $this);
		$tpl->assign('cacher_config', $this->getConfig());
		$tpl->display('devblocks:devblocks.core::cache_engine/disk/config.tpl');
	}
	
	function renderStatus() {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('cacher', $this);
		$tpl->assign('cacher_config', $this->getConfig());
		$tpl->display('devblocks:devblocks.core::cache_engine/disk/status.tpl');
	}
	
	function isVolatile() {
		return false;
	}
	
	private function _getFilename($key) {
		$key_prefix = $this->_config['key_prefix'] ?? null;
		$salt_suffix = '--' . substr(sha1(APP_DB_PASS ?? null),-8);
		
		$safe_key = DevblocksPlatform::strAlphaNum($key, '-', '_');
		return $key_prefix . $safe_key . $salt_suffix;
	}
	
	function load($key, &$tags=[]) {
		if(false == ($cache_file_path = $this->_getCacheFileByKey($key)))
			return null;
		
		if(!file_exists($cache_file_path))
			return null;
		
		if(false === ($fp = fopen($cache_file_path, 'r')))
			return null;
		
		flock($fp, LOCK_SH);
		
		$wrapper = @unserialize(file_get_contents($cache_file_path));
		
		fclose($fp);
		
		// If not wrapped, re-cache
		if(!is_array($wrapper) || !array_key_exists('__data', $wrapper))
			return null;
		
		// Check the cache expiration
		if(array_key_exists('__cache_until', $wrapper) && $wrapper['__cache_until']) {
			// If expired, kill it
			if(intval($wrapper['__cache_until']) < time()) {
				self::remove($key);
				return null;
			}
		}
		
		// Do we have tags?
		if(array_key_exists('__tags', $wrapper)) {
			$tags = $wrapper['__tags'];
		}
		
		// If not expired, return the data
		return $wrapper['__data'];
	}
	
	function save($data, $key, $tags=[], $ttl=0) {
		if(false == ($cache_file_path = $this->_getCacheFileByKey($key)))
			return false;
		
		$wrapper = [
			'__data' => $data,
		];
		
		if(is_array($tags) && $tags)
			$wrapper['__tags'] = array_fill_keys($tags, time());
		
		// Are we setting a TTL?
		if(!empty($ttl)) {
			$wrapper['__cache_until'] = time() + $ttl;
		}
		
		if(false === ($fp = fopen($cache_file_path, 'w')))
			return false;
		
		// Lock for writing
		flock($fp, LOCK_EX);
		
		if(false === fwrite($fp, serialize($wrapper)))
			return false;
		
		// Set the permissions more securely
		@chmod($cache_file_path, 0660);
		
		fclose($fp);
		
		return true;
	}
	
	function remove($key) {
		if(false == ($file = $this->_getCacheFileByKey($key)))
			return false;
		
		$cache_basedir = $this->_getCacheDir();
		
		if(false == ($file = realpath($file)))
			return null;
		
		if(!DevblocksPlatform::strStartsWith($file, $cache_basedir))
			return null;
		
		if(file_exists($file) && is_writeable($file))
			@unlink($file);
		
		return true;
	}
	
	function clean() {
		$cache_dir = $this->_getCacheDir();
		
		$files = scandir($cache_dir);
		unset($files['.']);
		unset($files['..']);
		
		if(is_array($files))
		foreach($files as $file) {
			if(0==strcmp('cache--', substr($file, 0, 7))) {
				if(file_exists($cache_dir . $file) && is_writeable($cache_dir . $file))
					@unlink($cache_dir . $file);
			}
		}
		
	}
};

class DevblocksCacheEngine_Memcache extends Extension_DevblocksCacheEngine {
	const ID = 'devblocks.cache.engine.memcache';
	
	private $_driver = null;
	private $_iteration = null;

	public function getDriver() {
		return $this->_driver;
	}
	
	private function _getIteration($new=false) {
		// First, check the class for our iteration
		if(!$new && !is_null($this->_iteration))
			return $this->_iteration;
		
		$key_prefix = $this->_config['key_prefix'] ?? null;
		$cache_key = $key_prefix . 'cacher:iteration';
		
		// Then check the Memcache
		if($new || null == ($iteration = $this->_driver->get($cache_key))) {
			// If not found, generate a new one and save it
			$iteration = dechex(mt_rand());
			$this->_set($cache_key, $iteration, 0);
		}
		
		if(is_array($iteration) && array_key_exists('__data', $iteration))
			$iteration = $iteration['__data'];
		
		$this->_iteration = $iteration;
		
		return $this->_iteration;
	}
	
	private function _getCacheKey($key) {
		$key_prefix = $this->_config['key_prefix'] ?? null;
		
		return sprintf("%s%s:%s",
			$key_prefix,
			$this->_getIteration(),
			$key
		);
	}
	
	function setConfig(array $config) {
		if(true !== ($result = $this->testConfig($config))) {
			trigger_error($result, E_USER_WARNING);
			return false;
		}
		
		$this->_config = $config;
		$this->_iteration = $this->_getIteration();
		return true;
	}
	
	function testConfig(array $config) {
		if(extension_loaded('memcached')) {
			$this->_driver = new Memcached();
			$this->_driver->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT);
			
		} elseif(extension_loaded('memcache')) {
			$this->_driver = new Memcache();
			
		} else {
			return "The 'Memcache' or 'Memcached' PHP extension is not loaded.";
		}
		
		if(empty($config['host']))
			return "The 'host' setting is required.";
		
		if(empty($config['port']))
			return "The 'port' setting is required.";
		
		$this->_driver->addServer($config['host'], $config['port']);
		
		if(false == @$this->_driver->getVersion())
			return sprintf("Failed to connect to the Memcached server at %s:%d.", $config['host'], $config['port']);
		
		return true;
	}
	
	function renderConfig() {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('cacher', $this);
		$tpl->assign('cacher_config', $this->getConfig());
		$tpl->display('devblocks:devblocks.core::cache_engine/memcached/config.tpl');
	}
	
	function renderStatus() {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('cacher', $this);
		$tpl->assign('cacher_config', $this->getConfig());
		$tpl->display('devblocks:devblocks.core::cache_engine/memcached/status.tpl');
	}
	
	function isVolatile() {
		return true;
	}
	
	private function _set($cache_key, $data, $ttl, $tags=[]) {
		$wrapper = [
			'__data' => $data,
		];
		
		if($tags) {
			$wrapper['__tags'] = array_fill_keys($tags, time());
		}
		
		if($this->_driver instanceof Memcached) {
			return $this->_driver->set($cache_key, $wrapper, $ttl);
		} else {
			return $this->_driver->set($cache_key, $wrapper, 0, $ttl);
		}
	}
	
	function save($data, $key, $tags=[], $ttl=0) {
		$cache_key = $this->_getCacheKey($key);
		
		if(empty($ttl))
			$ttl = 86400; // 1 day (any value is needed for LRU)

		$this->_set($cache_key, $data, $ttl, $tags);
		return true;
	}
	
	function load($key, &$tags=[]) {
		$cache_key = $this->_getCacheKey($key);
		
		@$wrapper = $this->_driver->get($cache_key);
		
		if(!is_array($wrapper) || !array_key_exists('__data', $wrapper))
			return null;
		
		if(array_key_exists('__tags', $wrapper))
			$tags = $wrapper['__tags'];
		
		return $wrapper['__data'];
	}
	
	function remove($key) {
		if(empty($key))
			return;

		$cache_key = $this->_getCacheKey($key);
		return $this->_driver->delete($cache_key);
	}

	function clean() {
		$this->_getIteration(true);
	}
};

class DevblocksCacheEngine_Redis extends Extension_DevblocksCacheEngine {
	const ID = 'devblocks.cache.engine.redis';
	
	private $_driver = null;
	private $_iteration = null;
	
	public function getDriver() {
		return $this->_driver;
	}

	private function _getIteration($new=false) {
		// First, check the class for our iteration
		if(!$new && !is_null($this->_iteration))
			return $this->_iteration;
		
		$key_prefix = $this->_config['key_prefix'] ?? null;
		$cache_key = $key_prefix . 'cacher:iteration';
		
		// Then check the Redis cache
		if($new 
			|| null == ($wrapper = $this->_driver->get($cache_key)) 
			|| false === ($wrapper = @unserialize($wrapper))
			|| !is_array($wrapper) 
			|| !array_key_exists('__data', $wrapper)) 
		{
			// If not found, generate a new one and save it
			$iteration = dechex(mt_rand());
			
			$wrapper = [
				'__data' => $iteration,
			];
			
			$this->_driver->set($cache_key, serialize($wrapper));
		}
		
		$this->_iteration = $wrapper['__data'];
		return $this->_iteration;
	}
	
	private function _getCacheKey($key) {
		$key_prefix = $this->_config['key_prefix'] ?? null;
		
		return sprintf("%s%s:%s",
			$key_prefix,
			$this->_getIteration(),
			$key
		);
	}
	
	function setConfig(array $config) {
		if(true !== ($result = $this->testConfig($config))) {
			trigger_error($result, E_USER_WARNING);
			return false;
		}
		$this->_config = $config;
		$this->_iteration = $this->_getIteration();
		return true;
	}
	
	function testConfig(array $config) {
		if(!extension_loaded('redis'))
			return "The 'Redis' PHP extension is not loaded.";
			
		if(empty($config['host']))
			return "The 'host' setting is required.";
		
		if(empty($config['port']))
			return "The 'port' setting is required.";
		
		if(!empty($config['database']) && !is_numeric($config['database']))
			return "The 'database' setting must be a number.";
		
		$this->_driver = new Redis();
		
		if(false == @$this->_driver->connect($config['host'], $config['port']))
			return sprintf("Failed to connect to the Redis server at %s:%d.", $config['host'], $config['port']);
		
		if(!empty($config['auth']))
			if(false == $this->_driver->auth($config['auth']))
				return 'Failed to authenticate with the Redis server.';
		
		if(0 != strlen($config['database']))
			if(false == ($this->_driver->select($config['database'])))
				return sprintf('Failed to connect to database %d.', $config['database']);
		
		return true;
	}
	
	function renderConfig() {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('cacher', $this);
		$tpl->assign('cacher_config', $this->getConfig());
		$tpl->display('devblocks:devblocks.core::cache_engine/redis/config.tpl');
	}

	function renderStatus() {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('cacher', $this);
		$tpl->assign('cacher_config', $this->getConfig());
		$tpl->display('devblocks:devblocks.core::cache_engine/redis/status.tpl');
	}
	
	function isVolatile() {
		return true;
	}
	
	function save($data, $key, $tags=[], $ttl=0) {
		$cache_key = $this->_getCacheKey($key);
		
		if(empty($ttl))
			$ttl = 86400; // 1 day (any value is needed for LRU)
		
		$wrapper = [
			'__data' => $data,
		];
		
		if($tags) {
			$wrapper['__tags'] = array_fill_keys($tags, time());
		}
		
		$this->_driver->setex($cache_key, $ttl, serialize($wrapper));
		return true;
	}
	
	function load($key, &$tags=[]) {
		$cache_key = $this->_getCacheKey($key);
		
		if(
			null === ($wrapper = $this->_driver->get($cache_key))
			|| false === ($wrapper = @unserialize($wrapper))
		) {
			return null;
		}
		
		if(!is_array($wrapper) || !array_key_exists('__data', $wrapper))
			return null;
		
		if(array_key_exists('__tags', $wrapper))
			$tags = $wrapper['__tags'];
		
		return $wrapper['__data'];
	}
	
	function remove($key) {
		$cache_key = $this->_getCacheKey($key);
		
		return $this->_driver->del($cache_key);
	}

	function clean() {
		$this->_getIteration(true);
	}
};