<?php
abstract class DevblocksApplication {
	
}

/**
 * The superclass of instanced extensions.
 *
 * @abstract 
 * @ingroup plugin
 */
class DevblocksExtension {
	public $manifest = null;
	public $id  = '';
	
	/**
	 * Constructor
	 *
	 * @private
	 * @param DevblocksExtensionManifest $manifest
	 * @return DevblocksExtension
	 */
	function DevblocksExtension($manifest) { /* @var $manifest DevblocksExtensionManifest */
        if(empty($manifest)) return;
        
		$this->manifest = $manifest;
		$this->id = $manifest->id;
	}
	
	function getParams() {
		return $this->manifest->getParams();
	}
	
	function setParam($key, $value) {
		return $this->manifest->setParam($key, $value);
	}
	
	function getParam($key,$default=null) {
		return $this->manifest->getParam($key, $default);
	}
};

abstract class Extension_DevblocksContext extends DevblocksExtension {
   	function authorize($context_id) {
		return true;
	}
    
    abstract function getPermalink($context_id);
    abstract function getContext($object, &$token_labels, &$token_values, $prefix=null);
    abstract function getChooserView();
    function getViewClass() {
    	return @$this->manifest->params['view_class'];
    }
    abstract function getView($context=null, $context_id=null, $options=array());
};

abstract class DevblocksHttpResponseListenerExtension extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest);
	}
    
	function run(DevblocksHttpResponse $request, Smarty $tpl) {
	}
}

abstract class Extension_DevblocksStorageEngine extends DevblocksExtension {
	protected $_options = array();

	function __construct($manifest) {
		$this->DevblocksExtension($manifest);
	}
	
	abstract function renderConfig(Model_DevblocksStorageProfile $profile);
	abstract function saveConfig(Model_DevblocksStorageProfile $profile);
	abstract function testConfig();
	
	abstract function exists($namespace, $key);
	abstract function put($namespace, $id, $data);
	abstract function get($namespace, $key, &$fp=null);
	abstract function delete($namespace, $key);
	
	public function setOptions($options=array()) {
		if(is_array($options))
			$this->_options = $options;
	}

	protected function escapeNamespace($namespace) {
		return strtolower(DevblocksPlatform::strAlphaNumUnder($namespace));
	}
};

abstract class Extension_DevblocksStorageSchema extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest);
	}
	
	abstract function render();
	abstract function renderConfig();
	abstract function saveConfig();
	
	abstract public static function getActiveStorageProfile();

	abstract public static function get($object, &$fp=null);
	abstract public static function put($id, $contents, $profile=null);
	abstract public static function delete($ids);
	abstract public static function archive($stop_time=null);
	abstract public static function unarchive($stop_time=null);
	
	protected function _stats($table_name) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$stats = array();
		
		$results = $db->GetArray(sprintf("SELECT storage_extension, storage_profile_id, count(id) as hits, sum(storage_size) as bytes FROM %s GROUP BY storage_extension, storage_profile_id ORDER BY storage_extension",
			$table_name
		));
		foreach($results as $result) {
			$stats[$result['storage_extension'].':'.intval($result['storage_profile_id'])] = array(
				'storage_extension' => $result['storage_extension'],
				'storage_profile_id' => $result['storage_profile_id'],
				'count' => intval($result['hits']),
				'bytes' => intval($result['bytes']),
			);
		}
		
		return $stats;
	}
	
};

abstract class DevblocksControllerExtension extends DevblocksExtension implements DevblocksHttpRequestHandler {
	public function handleRequest(DevblocksHttpRequest $request) {}
	public function writeResponse(DevblocksHttpResponse $response) {}
};

abstract class DevblocksEventListenerExtension extends DevblocksExtension {
    /**
     * @param Model_DevblocksEvent $event
     */
    function handleEvent(Model_DevblocksEvent $event) {}
};

interface DevblocksHttpRequestHandler {
	/**
	 * @param DevblocksHttpRequest
	 * @return DevblocksHttpResponse
	 */
	public function handleRequest(DevblocksHttpRequest $request);
	public function writeResponse(DevblocksHttpResponse $response);
}

class DevblocksHttpRequest extends DevblocksHttpIO {
	/**
	 * @param array $path
	 */
	function __construct($path, $query=array()) {
		parent::__construct($path, $query);
	}
}

class DevblocksHttpResponse extends DevblocksHttpIO {
	/**
	 * @param array $path
	 */
	function __construct($path, $query=array()) {
		parent::__construct($path, $query);
	}
}

abstract class DevblocksHttpIO {
	public $path = array();
	public $query = array();
	
	/**
	 * Enter description here...
	 *
	 * @param array $path
	 */
	function __construct($path,$query=array()) {
		$this->path = $path;
		$this->query = $query;
	}
}
