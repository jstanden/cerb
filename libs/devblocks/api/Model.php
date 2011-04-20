<?php
class DevblocksTourCallout {
    public $selector = '';
    public $title = '';
    public $body = '';
    public $tipCorner = '';
    public $targetCorner = '';
    public $xOffset = 0;
    public $yOffset = 0;
    
    function __construct($selector='',$title='Callout',$body='...',$tipCorner='topLeft',$targetCorner='topLeft',$xOffset=0,$yOffset=0) {
        $this->selector = $selector;
        $this->title = $title;
        $this->body = $body;
        $this->tipCorner = $tipCorner;
        $this->targetCorner = $targetCorner;
        $this->xOffset = $xOffset;
        $this->yOffset = $yOffset;
    }
};
interface IDevblocksSearchFields {
    static function getFields();
}
class DevblocksSearchCriteria {
    const OPER_EQ = '=';
    const OPER_EQ_OR_NULL = 'equals or null';
    const OPER_NEQ = '!=';
    const OPER_IN = 'in';
    const OPER_IS_NULL = 'is null';
    const OPER_NIN = 'not in';
    const OPER_FULLTEXT = 'fulltext';
    const OPER_LIKE = 'like';
    const OPER_NOT_LIKE = 'not like';
    const OPER_GT = '>';
    const OPER_LT = '<';
    const OPER_GTE = '>=';
    const OPER_LTE = '<=';
    const OPER_BETWEEN = 'between';
    const OPER_TRUE = '1';
    
    const GROUP_OR = 'OR';
    const GROUP_AND = 'AND';
    
	public $field;
	public $operator;
	public $value;
	
	/**
	 * Enter description here...
	 *
	 * @param string $field
	 * @param string $oper
	 * @param mixed $value
	 * @return DevblocksSearchCriteria
	 */
	 public function DevblocksSearchCriteria($field,$oper,$value=null) {
		$this->field = $field;
		$this->operator = $oper;
		$this->value = $value;
	}
	
	/*
	 * [TODO] [JAS] Having to pass $fields here is kind of silly, but I'm ignoring 
	 * for now since it's only called in 2 abstracted places.
	 */
	public function getWhereSQL($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		$where = '';
		
		$db_field_name = $fields[$this->field]->db_table . '.' . $fields[$this->field]->db_column; 

		// [JAS]: Operators
		switch($this->operator) {
			case "eq":
			case "=":
				$where = sprintf("%s = %s",
					$db_field_name,
					self::_escapeSearchParam($this, $fields)
				);
				break;
				
			case DevblocksSearchCriteria::OPER_EQ_OR_NULL:
				$where = sprintf("(%s = %s OR %s IS NULL)",
					$db_field_name,
					self::_escapeSearchParam($this, $fields),
					$db_field_name
				);
				break;
				
			case "neq":
			case "!=":
				$where = sprintf("%s != %s",
					$db_field_name,
					self::_escapeSearchParam($this, $fields)
				);
				break;
			
			case "in":
				if(!is_array($this->value)) break;
				$value = (!empty($this->value)) ? $this->value : array(-1);
				$vals = array();
				
				// Escape quotes
				foreach($this->value as $idx=>$val) {
					$vals[$idx] = addslashes($val); // [TODO] Test
				}
				
				$where = sprintf("%s IN ('%s')",
					$db_field_name,
					implode("','",$vals)
				);
				break;

			case DevblocksSearchCriteria::OPER_NIN: // 'not in'
				if(!is_array($this->value)) break;
				$value = (!empty($this->value)) ? $this->value : array(-1);
				$where = sprintf("%s NOT IN ('%s')",
					$db_field_name,
					implode("','",$value)
				);
				break;
				
			case DevblocksSearchCriteria::OPER_FULLTEXT: // 'fulltext'
				$search = DevblocksPlatform::getSearchService();

				$value = null;
				$scope = null;
				
				if(!is_array($this->value)) {
					$value = $this->value;
					$scope = 'expert'; 
				} else {
					$value = $this->value[0];
					$scope = $this->value[1]; 
				}
				
				switch($scope) {
					case 'all':
						$value = $search->prepareText($value);
						$value = '+'.str_replace(' ', ' +', $value);
						break;
					case 'any':
						$value = $search->prepareText($value);
						break;
					case 'phrase':
						$value = '"'.$search->prepareText($value).'"';
						break;
					default:
					case 'expert':
						break;
				}
				
				$where = sprintf("MATCH(%s) AGAINST (%s IN BOOLEAN MODE)",
					$db_field_name,
					$db->qstr($value)
				);
				break;
			
			case DevblocksSearchCriteria::OPER_LIKE: // 'like'
				$where = sprintf("%s LIKE %s",
					$db_field_name,
					str_replace('*','%',self::_escapeSearchParam($this, $fields))
				);
				break;
			
			case DevblocksSearchCriteria::OPER_NOT_LIKE: // 'not like'
				$where = sprintf("%s NOT LIKE %s",
					$db_field_name,
					str_replace('*','%%',self::_escapeSearchParam($this, $fields))
				);
				break;
			
			case DevblocksSearchCriteria::OPER_IS_NULL: // 'is null'
				$where = sprintf("%s IS NULL",
					$db_field_name
				);
				break;
			
			case DevblocksSearchCriteria::OPER_TRUE:
				$where = '1';
				break;
				
			/*
			 * [TODO] Someday we may want to call this OPER_DATE_BETWEEN so it doesn't interfere 
			 * with the operator in other uses
			 */
			case DevblocksSearchCriteria::OPER_BETWEEN: // 'between'
				if(!is_array($this->value) && 2 != count($this->value))
					break;
					
				$from_date = $this->value[0];
				if(!is_numeric($from_date)) {
					// Translate periods into dashes on string dates
					if(false !== strpos($from_date,'.'))
						$from_date = str_replace(".", "-", $from_date);
						
					if(false === ($from_date = strtotime($from_date)))
						$from_date = 0;
				}
				
				$to_date = $this->value[1];
				if(!is_numeric($to_date)) {
					// Translate periods into dashes on string dates
					if(false !== strpos($to_date,'.'))
						$to_date = str_replace(".", "-", $to_date);
						
					if(false === ($to_date = strtotime($to_date)))
						$to_date = strtotime("now");
				}
				
				if(0 == $from_date) {
					$where = sprintf("(%s IS NULL OR %s BETWEEN %s and %s)",
						$db_field_name,
						$db_field_name,
						$from_date,
						$to_date
					);
				} else {
					$where = sprintf("%s BETWEEN %s and %s",
						$db_field_name,
						$from_date,
						$to_date
					);
				}
				break;
			
			case DevblocksSearchCriteria::OPER_GT:
			case DevblocksSearchCriteria::OPER_GTE:
			case DevblocksSearchCriteria::OPER_LT:
			case DevblocksSearchCriteria::OPER_LTE:
				$where = sprintf("%s %s %s",
					$db_field_name,
					$this->operator,
					self::_escapeSearchParam($this, $fields)
				);
			    break;
				
			default:
				break;
		}
		
		return $where;
	}
	
	static protected function _escapeSearchParam(DevblocksSearchCriteria $param, $fields) {
	    $db = DevblocksPlatform::getDatabaseService();
	    $field = $fields[$param->field];
	    $where_value = null;

	    if($field) {
	    	if(!is_array($param->value)) {
	    		$where_value = $db->qstr($param->value);
	    	} else {
	    		$where_value = array();
	    		foreach($param->value as $v) {
	    			$where_value[] = $db->qstr($v);
	    		}
	    	}
	    }

        return $where_value;
	}
};

class DevblocksSearchField {
	public $token;
	public $db_table;
	public $db_column;
	public $db_label;
	
	function __construct($token, $db_table, $db_column, $db_label=null) {
		$this->token = $token;
		$this->db_table = $db_table;
		$this->db_column = $db_column;
		$this->db_label = $db_label;
	}
};

class DevblocksAclPrivilege {
	var $id = '';
	var $plugin_id = '';
	var $label = '';
};

class DevblocksEventPoint {
    var $id = '';
    var $plugin_id = '';
    var $name = '';
    var $param = array();
};

class DevblocksExtensionPoint {
	var $id = '';
    var $plugin_id = '';
	var $extensions = array();
};

class DevblocksTemplate {
	var $set = '';
	var $plugin_id = '';
	var $path = '';
};

/**
 * Manifest information for plugin.
 * @ingroup plugin
 */
class DevblocksPluginManifest {
	var $id = '';
	var $enabled = 0;
	var $name = '';
	var $description = '';
	var $author = '';
	var $revision = 0;
	var $link = '';
	var $dir = '';
	var $manifest_cache = array();
	
	var $extension_points = array();
	var $event_points = array();
	var $acl_privs = array();
	var $class_loader = array();
	var $uri_routing = array();
	var $extensions = array();
	
	function setEnabled($bool) {
		$this->enabled = ($bool) ? 1 : 0;
		
		// Persist to DB
		$fields = array(
			'enabled' => $this->enabled
		);
		DAO_Platform::updatePlugin($this->id, $fields);
	}
	
	/**
	 * 
	 */
	function getActivityPoints() {
		$points = array();

		if(isset($this->manifest_cache['activity_points']))
		foreach($this->manifest_cache['activity_points'] as $point=> $data) {
			$points[$point] = $data;
		}
		
		return $points;
	}
	
	/**
	 * return DevblocksPatch[]
	 */
	function getPatches() {
		$patches = array();
		
		if(isset($this->manifest_cache['patches']))
		foreach($this->manifest_cache['patches'] as $patch) {
			$path = APP_PATH . '/' . $this->dir . '/' . $patch['file'];
			$patches[] = new DevblocksPatch($this->id, $patch['version'], $patch['revision'], $path);
		}
		
		return $patches;
	}
	
	function purge() {
		$db = DevblocksPlatform::getDatabaseService();
		$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup
		
        $db->Execute(sprintf("DELETE FROM %splugin WHERE id = %s",
            $prefix,
            $db->qstr($this->id)
        ));
        $db->Execute(sprintf("DELETE FROM %sextension WHERE plugin_id = %s",
            $prefix,
            $db->qstr($this->id)
        ));
        $db->Execute(sprintf("DELETE %1\$sproperty_store FROM %1\$sproperty_store ".
        	"LEFT JOIN %1\$sextension ON (%1\$sproperty_store.extension_id=%1\$sextension.id) ".
        	"WHERE %1\$sextension.id IS NULL",
        	$prefix
        ));
	}
};

/**
 * Manifest information for a plugin's extension.
 * @ingroup plugin
 */
class DevblocksExtensionManifest {
	var $id = '';
	var $plugin_id ='';
	var $point = '';
	var $name = '';
	var $file = '';
	var $class = '';
	var $params = array();

	function DevblocksExtensionManifest() {}
	
	/**
	 * Creates and loads a usable extension from a manifest record.  The object returned 
	 * will be of type $class defined by the manifest.  $instance_id is passed as an 
	 * argument to uniquely identify multiple instances of an extension.
	 *
	 * @param integer $instance_id
	 * @return object
	 */
	function createInstance() {
		if(empty($this->id) || empty($this->plugin_id)) // empty($instance_id) || 
			return null;

		if(null == ($plugin = DevblocksPlatform::getPlugin($this->plugin_id)))
			return;

		$class_file = APP_PATH . '/' . $plugin->dir . '/' . $this->file;
		$class_name = $this->class;

		DevblocksPlatform::registerClasses($class_file,array($class_name));

		if(!class_exists($class_name, true)) {
			return null;
		}
		
		$instance = new $class_name($this);
		return $instance;
	}
	
	/**
	 * @return DevblocksPluginManifest
	 */
	function getPlugin() {
		$plugin = DevblocksPlatform::getPlugin($this->plugin_id);
		return $plugin;
	}
	
	function getParams() {
		return DAO_DevblocksExtensionPropertyStore::getByExtension($this->id);
	}
	
	function setParam($key, $value) {
		return DAO_DevblocksExtensionPropertyStore::put($this->id, $key, $value);
	}
	
	function getParam($key, $default=null) {
		return DAO_DevblocksExtensionPropertyStore::get($this->id, $key, $default);
	}
};

/**
 * A single session instance
 *
 * @ingroup core
 * [TODO] Evaluate if this is even needed, or if apps can have their own unguided visit object
 */
abstract class DevblocksVisit {
	private $registry = array();
	
	public function exists($key) {
		return isset($this->registry[$key]);
	}
	
	public function get($key, $default=null) {
		@$value = $this->registry[$key];
		
		if(is_null($value) && !is_null($default)) 
			$value = $default;
			
		return $value;
	}
	
	public function set($key, $object) {
		$this->registry[$key] = $object;
	}
};


/**
 * 
 */
class DevblocksPatch {
	private $plugin_id = ''; // cerberusweb.core
	private $version = '';
	private $revision = 0; // 100
	private $filename = ''; // 4.0.0.php
	
	public function __construct($plugin_id, $version, $revision, $filename) {
		$this->plugin_id = $plugin_id;
		$this->version = $version;
		$this->revision = intval($revision);
		$this->filename = $filename;
	}
	
	public function run() {
	    if($this->hasRun())
	        return TRUE;

	    if(empty($this->filename) || !file_exists($this->filename))
	        return FALSE;

		if(false === ($result = require_once($this->filename)))
			return FALSE;
		
		DAO_Platform::setPatchRan($this->plugin_id, $this->revision);
		
		return TRUE;
	}
	
	/**
	 * @return boolean
	 */
	public function hasRun() {
		// Compare PLUGIN_ID + REVISION in script history
		return DAO_Platform::hasPatchRun($this->plugin_id,$this->revision);
	}
	
	public function getPluginId() {
		return $this->plugin_id;
	}
	
	public function getFilename() {
		return $this->filename;
	}
	
	public function getVersion() {
		return $this->version;
	}
	
	public function getRevision() {
		return $this->revision;
	}
	
};

class Model_DevblocksEvent {
  public $id = '';
  public $params = array(); 

  function __construct($id='',$params=array()) {
      $this->id = $id;
      $this->params = $params;
  }
};

class Model_DevblocksTemplate {
	public $id;
	public $plugin_id;
	public $path;
	public $tag;
	public $last_updated;
	public $content;
};

class Model_Translation {
	public $id;
	public $string_id;
	public $lang_code;
	public $string_default;
	public $string_override;
};

class Model_DevblocksStorageProfile {
	public $id;
	public $name;
	public $extension_id;
	public $params_json;
	public $params = array();
	
	function getUsageStats() {
		// Schemas
		$storage_schemas = DevblocksPlatform::getExtensions('devblocks.storage.schema', true, true);
		
		// Stats
		$storage_schema_stats = array();
		foreach($storage_schemas as $schema) {
			$stats = $schema->getStats();
			$key = $this->extension_id . ':' . intval($this->id);
			if(isset($stats[$key]))
				$storage_schema_stats[$schema->id] = $stats[$key];
		}
		
		return $storage_schema_stats;
	}
};
