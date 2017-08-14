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
			if(false == ($this->_initLibs()))
				return false;
					
			if(false == ($this->_initServices()))
				return false;
			
			if(false == ($this->_initPlugins()))
				return false;
			
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
		if(class_exists($className, false))
			return;
		
		@$file = $this->classMap[$className];
		
		if(!is_null($file) && file_exists($file)) {
			require_once($file);
			
			if(class_exists($className, false))
				return true;
		}
		
		if (false != ($file = $this->_loadDynamicClass($className))) {
			require_once($file);
			return true;
		}
		
		// Not found
		return false;
	}
	
	private function _loadDynamicClass($class_name) {
		if(
			DevblocksPlatform::strStartsWith($class_name, 'Context_AbstractCustomRecord_')
			|| DevblocksPlatform::strStartsWith($class_name, 'DAO_AbstractCustomRecord_')
			|| DevblocksPlatform::strStartsWith($class_name, 'Model_AbstractCustomRecord_')
			|| DevblocksPlatform::strStartsWith($class_name, 'SearchFields_AbstractCustomRecord_')
			|| DevblocksPlatform::strStartsWith($class_name, 'View_AbstractCustomRecord_')
			) {
			
			$class_id = intval(substr($class_name, strrpos($class_name, '_')+1));
			
			if(!$class_id)
				return false;
			
			$class_path = sprintf("%s/classes", APP_STORAGE_PATH);
			$class_file = sprintf("%s/abstract_record_%d.php", $class_path, $class_id);
			
			if(file_exists($class_file))
				return $class_file;
			
			if(!file_exists($class_path))
			if(!mkdir($class_path, 0770))
				return false;
			
			$class_code = sprintf(
				'<?php'. PHP_EOL .  
				'class Context_AbstractCustomRecord_%1$d extends Context_AbstractCustomRecord { const ID = "%2$s"; const _ID = %1$d; }'. PHP_EOL .
				'class DAO_AbstractCustomRecord_%1$d extends DAO_AbstractCustomRecord { const _ID = %1$d; }'. PHP_EOL .
				'class Model_AbstractCustomRecord_%1$d extends Model_AbstractCustomRecord { const _ID = %1$d; }'. PHP_EOL .
				'class SearchFields_AbstractCustomRecord_%1$d extends SearchFields_AbstractCustomRecord { const _ID = %1$d; }'. PHP_EOL .
				'class View_AbstractCustomRecord_%1$d extends View_AbstractCustomRecord { const _ID = %1$d; }'. PHP_EOL .
				'',
				$class_id,
				sprintf("contexts.custom_record.%d", $class_id)
			);
			
			if(!file_put_contents($class_file, $class_code))
				return false;
			
			chmod($class_file, 0660);
			
			return $class_file;
		}
	}
	
	public function registerAutoloadPath($path, $ns_prefix) {
		if(!file_exists($path))
			return;
		
		$dir = new RecursiveDirectoryIterator($path);
		$iter = new RecursiveIteratorIterator($dir);
		$regex = new RegexIterator($iter, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);
		
		foreach($regex as $class_file => $o) {
			$class_name = substr($class_file, strlen($path), strlen($class_file)-strlen($path)-4);
			$class_name = $ns_prefix . str_replace(DIRECTORY_SEPARATOR, '\\', $class_name);
			$this->classMap[$class_name] = $class_file;
		}
	}
	
	public function registerClasses($file, $classes=array()) {
		if(is_array($classes))
		foreach($classes as $class) {
			$this->classMap[$class] = $file;
		}
	}
	
	private function _initLibs() {
		$this->registerAutoloadPath(DEVBLOCKS_PATH . 'libs/css_selector/', 
			"Symfony\\Component\\CssSelector\\"
		);
		
		$this->registerAutoloadPath(DEVBLOCKS_PATH . 'libs/css_to_inline_styles/',
			"TijsVerkoyen\\CssToInlineStyles\\"
		);
		
		$this->registerClasses(DEVBLOCKS_PATH . 'libs/parsedown/Parsedown.php', array(
			'Parsedown'
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'libs/parsedown/ParsedownExtra.php', array(
			'ParsedownExtra'
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
		
		return true;
	}
	
	private function _initServices() {
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/bayes_classifier.php', array(
			'_DevblocksBayesClassifierService',
		));
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
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/encryption.php', array(
			'_DevblocksEncryptionService',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/event.php', array(
			'_DevblocksEventManager',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/event/event_helper.php', array(
			'DevblocksEventHelper',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/gpg.php', array(
			'_DevblocksGPGService',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/nlp.php', array(
			'_DevblocksNaturalLanguageManager',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/nn.php', array(
			'_DevblocksNeuralNetworkService',
			'DevblocksNeuralNetwork',
			'DevblocksNeuralNetworkNode',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/oauth.php', array(
			'_DevblocksOAuthService',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/openid.php', array(
			'_DevblocksOpenIDManager',
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
			'_DevblocksSearchEngineMysqlFulltext',
			'_DevblocksSearchEngineSphinx',
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
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/validation.php', array(
			'Exception_DevblocksValidationError',
			'_DevblocksValidationService',
		));
		
		return true;
	}
	
	private function _initPlugins() {
		// Load all the exported classes defined by plugin manifests
		$class_map = DAO_Platform::getClassLoaderMap();
		if(is_array($class_map) && !empty($class_map))
		foreach($class_map as $path => $classes) {
			$this->registerClasses($path, $classes);
		}
		
		return true;
	}
};