<?php
class _DevblocksClassLoadManager {
	const CACHE_CLASS_MAP = 'devblocks_classloader_map';
	
	private static $instance = null;
	private $classMap = array();
	
	private function __construct() {
		$cache = _DevblocksCacheManager::getInstance();
		
		if(null !== ($map = $cache->load(self::CACHE_CLASS_MAP))) {
			$sanity_a = $map['_DevblocksEventManager'];
			$sanity_b = implode(DIRECTORY_SEPARATOR, [APP_PATH,'libs','devblocks','api','services','event.php']);
			
			// If the root path has changed, don't use the cache and regenerate
			if($sanity_a == $sanity_b) {
				$this->classMap = $map;
				return;
			} else {
				DevblocksPlatform::logError('[Platform] Filesystem path change detected, reloading classloader cache.');
			}
		}
			
		if(!($this->_initLibs()))
			return;
				
		if(!($this->_initServices()))
			return;
		
		if(!($this->_initPlugins()))
			return;
		
		$cache->save($this->classMap, self::CACHE_CLASS_MAP);
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
		
		$file = $this->classMap[$className] ?? null;
		
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
			|| DevblocksPlatform::strStartsWith($class_name, 'Event_AbstractCustomRecord_')
			|| DevblocksPlatform::strStartsWith($class_name, 'Model_AbstractCustomRecord_')
			|| DevblocksPlatform::strStartsWith($class_name, 'Profile_AbstractCustomRecord_')
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
				'class Context_AbstractCustomRecord_%1$d extends Context_AbstractCustomRecord { const ID = "contexts.custom_record.%1$s"; const _ID = %1$d; }'. PHP_EOL .
				'class DAO_AbstractCustomRecord_%1$d extends DAO_AbstractCustomRecord { const _ID = %1$d; }'. PHP_EOL .
				'class Event_AbstractCustomRecord_%1$d extends Event_AbstractCustomRecordMacro { const ID = "event.macro.custom_record.%1$d"; const _ID = %1$d; }' . PHP_EOL .
				'class Model_AbstractCustomRecord_%1$d extends Model_AbstractCustomRecord { const _ID = %1$d; }'. PHP_EOL .
				'class Profile_AbstractCustomRecord_%1$d extends PageSection_ProfilesAbstractCustomRecord { const ID = "profile.custom_record.%1$d"; const _ID = %1$d; }' . PHP_EOL .
				'class SearchFields_AbstractCustomRecord_%1$d extends SearchFields_AbstractCustomRecord { const _ID = %1$d; }'. PHP_EOL .
				'class View_AbstractCustomRecord_%1$d extends View_AbstractCustomRecord { const _ID = %1$d; }'. PHP_EOL .
				'',
				$class_id
			);
			
			if(!file_put_contents($class_file, $class_code))
				return false;
			
			chmod($class_file, 0660);
			
			return $class_file;
		}
	}
	
	public function registerPsr4Path($path, $ns_prefix='') {
		if(!file_exists($path))
			return;
		
		$dir = new RecursiveDirectoryIterator($path);
		$iter = new RecursiveIteratorIterator($dir);
		$regex = new RegexIterator($iter, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);
		
		foreach($regex as $class_file => $o) {
			if(is_null($o))
				continue;
			$class_name = substr($class_file, strlen($path), strlen($class_file)-strlen($path)-4);
			$class_name = $ns_prefix . str_replace(DIRECTORY_SEPARATOR, '\\', $class_name);
			$this->classMap[$class_name] = $class_file;
		}
	}
	
	public function registerClassPath($path) {
		if(!file_exists($path))
			return;
		
		$dir = new RecursiveDirectoryIterator($path);
		$iter = new RecursiveIteratorIterator($dir);
		$regex = new RegexIterator($iter, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);
		
		foreach($regex as $class_file => $o) {
			if(is_null($o))
				continue;
			$class_name = substr($class_file, strlen($path), strlen($class_file)-strlen($path)-4);
			$class_name = str_replace(DIRECTORY_SEPARATOR, '_', $class_name);
			$this->classMap[$class_name] = $class_file;
		}
	}
	
	public function registerClasses($file, $classes=[]) {
		if(is_array($classes))
		foreach($classes as $class) {
			$this->classMap[$class] = $file;
		}
	}
	
	private function _initLibs() {
		$this->registerClasses(DEVBLOCKS_PATH . 'libs/s3/S3.php', array(
			'S3'
		));
		
		return true;
	}
	
	private function _initServices() {
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/automation.php', [
			'_DevblocksAutomationService',
			'CerbAutomationAstNode',
			'CerbAutomationPolicy',
			'Exception_DevblocksAutomationError',
		]);
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/bayes_classifier.php', array(
			'_DevblocksBayesClassifierService',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/captcha.php', [
			'_DevblocksCaptchaService',
		]);
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/chart.php', [
			'_DevblocksChartService',
		]);
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/data.php', array(
			'_DevblocksDataService',
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
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/file.php', array(
			'_DevblocksFileService',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/http.php', array(
			'_DevblocksHttpService',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/gpg.php', array(
			'_DevblocksGPGService',
			'Extension_DevblocksGpgEngine',
			'DevblocksGpgEngine_OpenPGP',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/kata.php', [
			'_DevblocksKataService',
		]);
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/metrics.php', [
			'_DevblocksMetricsService',
		]);
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/mfa.php', array(
			'_DevblocksMultiFactorAuthService',
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
			'_DevblocksOAuth1Client',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/openid.php', array(
			'_DevblocksOpenIDManager',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/queue.php', [
			'_DevblocksQueueService',
		]);
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
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/session.php', array(
			'_DevblocksSessionManager',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/settings.php', array(
			'_DevblocksPluginSettingsManager',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/sheet.php', [
			'_DevblocksSheetService',
			'_DevblocksSheetServiceTypes',
		]);
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/stats.php', [
			'_DevblocksStatsService',
		]);
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/storage.php', array(
			'_DevblocksStorageManager',
			'DevblocksStorageEngineDatabase',
			'DevblocksStorageEngineDisk',
			'DevblocksStorageEngineS3',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/string.php', [
			'_DevblocksStringService',
		]);
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/template_builder.php', array(
			'_DevblocksTemplateBuilder',
			'_DevblocksTwigExtensions',
			'_DevblocksTwigSecurityPolicy',
			'DevblocksDictionaryDelegate',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/template.php', array(
			'_DevblocksTemplateManager',
			'_DevblocksSmartyTemplateResource',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/translation.php', array(
			'_DevblocksTranslationManager',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/ui.php', array(
			'_DevblocksUiManager',
			'DevblocksUiEventHandler',
			'DevblocksUiToolbar',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/url.php', array(
			'_DevblocksUrlManager',
			'Cerb_HTMLPurifier_URIFilter_Email',
			'Cerb_HTMLPurifier_URIFilter_Extract',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/validation.php', array(
			'_DevblocksValidationService',
			'DevblocksValidationField',
			'Exception_DevblocksValidationError',
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'api/services/vobject.php', array(
			'_DevblocksVObjectService',
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