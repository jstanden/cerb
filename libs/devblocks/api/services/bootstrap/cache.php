<?php
class _DevblocksCacheManager {
    private static $instance = null;
    private static $_cacher = null;
	private $_registry = array();
	private $_statistics = array();
	private $_io_reads_long = 0;
	private $_io_reads_short = 0;
	private $_io_writes = 0;
    
    private function __construct() {}

    /**
     * @return _DevblocksCacheManager
     */
    public static function getInstance() {
		if(null == self::$instance) {
			self::$instance = new _DevblocksCacheManager();
			
			$options = array(
				'key_prefix' => ((defined('DEVBLOCKS_CACHE_PREFIX') && DEVBLOCKS_CACHE_PREFIX) ? DEVBLOCKS_CACHE_PREFIX : null), 
			);
			
			// Shared-memory cache
		    if((extension_loaded('memcache') || extension_loaded('memcached')) 
		    	&& defined('DEVBLOCKS_MEMCACHED_SERVERS') && DEVBLOCKS_MEMCACHED_SERVERS) {
		    	$pairs = DevblocksPlatform::parseCsvString(DEVBLOCKS_MEMCACHED_SERVERS);
		    	$servers = array();
		    	
		    	if(is_array($pairs) && !empty($pairs))
		    	foreach($pairs as $server) {
		    		list($host,$port) = explode(':',$server);
		    		
		    		if(empty($host) || empty($port))
		    			continue;
		    			
		    		$servers[] = array(
		    			'host'=>$host,
		    			'port'=>$port,
//		    			'persistent'=>true
		    		);
		    	}
		    	
				$options['servers'] = $servers;
				
				self::$_cacher = new _DevblocksCacheManagerMemcached($options);
				
				// Test
				if(false == (self::$_cacher->test())) {
					self::$_cacher = null;
				}
		    }

		    // Disk-based cache (default)
		    if(null == self::$_cacher) {
		    	$options['cache_dir'] = APP_TEMP_PATH; 
				
				self::$_cacher = new _DevblocksCacheManagerDisk($options);
		    }
		}
		
		return self::$instance;
    }
    
	public function save($data, $key, $tags=array(), $lifetime=0) {
		// Monitor short-term cache memory usage
		@$this->_statistics[$key] = intval($this->_statistics[$key]);
		$this->_io_writes++;
		self::$_cacher->save($data, $key, $tags, $lifetime);
		$this->_registry[$key] = $data;
	}
	
	public function load($key, $nocache=false) {
		// Retrieving the long-term cache
		if($nocache || !isset($this->_registry[$key])) {
			if(false === ($this->_registry[$key] = self::$_cacher->load($key)))
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
		if(empty($key))
			return;
		unset($this->_registry[$key]);
		unset($this->_statistics[$key]);
		self::$_cacher->remove($key);
	}
	
	public function clean() { // $mode=null
		$this->_registry = array();
		$this->_statistics = array();
		
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
};

abstract class _DevblocksCacheManagerAbstract {
	protected $_options;
	protected $_prefix = 'devblocks_cache---';
	
	function __construct($options) {
		if(is_array($options))
			$this->_options = $options;
		
		// Key prefix
		if(!isset($this->_options['key_prefix']))
			$this->_options['key_prefix'] = '';
	}
	
	function save($data, $key, $tags=array(), $lifetime=0) {}
	function load($key) {}
	function remove($key) {}
	function test() {}
	function clean() {} // $mode=null
};

class _DevblocksCacheManagerMemcached extends _DevblocksCacheManagerAbstract {
	private $_driver;
	
	function __construct($options) {
		parent::__construct($options);
		
		if(extension_loaded('memcached'))
			$this->_driver = new Memcached();
		elseif(extension_loaded('memcache'))
			$this->_driver = new Memcache();
		else
			die("PECL/Memcache or PECL/Memcached is not loaded.");
			
		// Check servers option
		if(!isset($this->_options['servers']) || !is_array($this->_options['servers']))
			die("_DevblocksCacheManagerMemcached requires the 'servers' option.");
			
		if(is_array($this->_options['servers']))
		foreach($this->_options['servers'] as $params) {
			$this->_driver->addServer($params['host'], $params['port']);
		}
	}
	
	function save($data, $key, $tags=array(), $lifetime=0) {
		$key = $this->_options['key_prefix'] . $key;
		
		if($this->_driver instanceof Memcached)
			return $this->_driver->set($key, $data, $lifetime);
		else
			return $this->_driver->set($key, $data, 0, $lifetime);
	}
	
	function load($key) {
		$key = $this->_options['key_prefix'] . $key;
		@$val = $this->_driver->get($key);
		return $val;
	}
	
	function remove($key) {
		if(empty($key))
			return;
		
		$key = $this->_options['key_prefix'] . $key;
		$this->_driver->delete($key);
	}

	function test() {
		try {
			@$version = $this->_driver->getVersion();
			return !empty($version);
		} catch(Exception $e) {
			return false;
		}
	}
	
	function clean() {
		$this->_driver->flush();
	}
};

class _DevblocksCacheManagerDisk extends _DevblocksCacheManagerAbstract {
	function __construct($options) {
		parent::__construct($options);

		$path = $this->_getPath();
		
		if(null == $path)
			die("_DevblocksCacheManagerDisk requires the 'cache_dir' option.");

		// Ensure we have a trailing slash
		$this->_options['cache_dir'] = rtrim($path,"\\/") . DIRECTORY_SEPARATOR;
			
		if(!is_writeable($path))
			die("_DevblocksCacheManagerDisk requires write access to the 'path' directory ($path)");
	}
	
	private function _getPath() {
		return $this->_options['cache_dir'];
	}
	
	private function _getFilename($key) {
		$safe_key = preg_replace("/[^A-Za-z0-9_\-]/",'_', $key);
		return $this->_prefix . $safe_key;
	}
	
	function load($key) {
		$key = $this->_options['key_prefix'] . $key;
		
		$wrapper = @unserialize(file_get_contents($this->_getPath() . $this->_getFilename($key)));
		
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
		$key = $this->_options['key_prefix'] . $key;
		
		$wrapper = array(
			'data' => $data,
		);
		
		// Are we setting a lifetime?
		if(!empty($lifetime)) {
			$wrapper['cache_until'] = time() + $lifetime;
		}
		
		return file_put_contents($this->_getPath() . $this->_getFilename($key), serialize($wrapper));
	}
	
	function remove($key) {
		$key = $this->_options['key_prefix'] . $key;
		$file = $this->_getPath() . $this->_getFilename($key);
		if(file_exists($file) && is_writeable($file))
			@unlink($file);
	}
	
	function test() {
		return is_writeable($this->_options['cache_dir']);
	}
	
	function clean() {
		$path = $this->_getPath();
		
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