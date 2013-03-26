<?php
class _DevblocksClassLoadManager {
	const CACHE_CLASS_MAP = 'devblocks_classloader_map';
	
	private static $instance = null;
	private $classMap = array();
	
	private function __construct() {
		$cache = _DevblocksCacheManager::getInstance();
		if(null !== ($map = $cache->load(self::CACHE_CLASS_MAP))) {
			$this->classMap = $map;
		} else {
			$this->_initLibs();
			$this->_initServices();
			$this->_initPlugins();
			$cache->save($this->classMap, self::CACHE_CLASS_MAP);
		}
	}
	
	/**
	 * @return _DevblocksClassLoadManager
	 */
	public static function getInstance() {
		if(null == self::$instance) {
			self::$instance = new _DevblocksClassLoadManager();
		}
		return self::$instance;
	}
	
	public function destroy() {
		self::$instance = null;
	}
	
	public function loadClass($className) {
		if(class_exists($className))
			return;
			
		@$file = $this->classMap[$className];

		if(!is_null($file) && file_exists($file)) {
			require_once($file);
		} else {
			// Not found
		}
	}
	
	public function registerClasses($file,$classes=array()) {
		if(is_array($classes))
		foreach($classes as $class) {
			$this->classMap[$class] = $file;
		}
	}
	
	private function _initLibs() {
		$this->registerClasses(DEVBLOCKS_PATH . 'libs/markdown/markdown.php', array(
			'markdown'
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'libs/pclzip/pclzip.lib.php', array(
			'PclZip'
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'libs/s3/S3.php', array(
			'S3'
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'libs/Twig/Autoloader.php', array(
			'Twig_Autoloader',
		));
	}
	
	private function _initServices() {
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/database.php', array(
			'_DevblocksDatabaseManager',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/date.php', array(
			'_DevblocksDateManager',
			'DevblocksCalendarHelper',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/email.php', array(
			'_DevblocksEmailManager',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/event.php', array(
			'_DevblocksEventManager',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/event/event_helper.php', array(
			'DevblocksEventHelper',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/logging.php', array(
			'_DevblocksLogManager',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/openid.php', array(
			'_DevblocksOpenIDManager',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/proxy.php', array(
			'_DevblocksProxy',
			'_DevblocksProxy_Curl',
			'_DevblocksProxy_Socket',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/registry.php', array(
			'_DevblocksRegistryManager',
			'DevblocksRegistryEntry',
			'DAO_DevblocksRegistry',
			'Model_DevblocksRegistry',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/sanitization.php', array(
			'_DevblocksSanitizationManager',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/search.php', array(
			'_DevblocksSearchManager',
			'_DevblocksSearchEngineMysqlFulltext',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/session.php', array(
			'_DevblocksSessionManager',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/settings.php', array(
			'_DevblocksPluginSettingsManager',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/storage.php', array(
			'_DevblocksStorageManager',
			'DevblocksStorageEngineDatabase',
			'DevblocksStorageEngineDisk',
			'DevblocksStorageEngineS3',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/template_builder.php', array(
			'_DevblocksTemplateBuilder',
			'_DevblocksTwigExtensions',
			'DevblocksDictionaryDelegate',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/template.php', array(
			'_DevblocksTemplateManager',
			'_DevblocksSmartyTemplateResource',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/translation.php', array(
			'_DevblocksTranslationManager',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/url.php', array(
			'_DevblocksUrlManager',
		));
	}
	
	private function _initPlugins() {
		// Load all the exported classes defined by plugin manifests
		$class_map = DAO_Platform::getClassLoaderMap();
		if(is_array($class_map) && !empty($class_map))
		foreach($class_map as $path => $classes) {
			$this->registerClasses($path, $classes);
		}
	}
};