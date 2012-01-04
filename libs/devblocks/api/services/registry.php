<?php
class _DevblocksRegistryManager {
	static $_instance = null;
	
	private $_registry = array();
	
	static function getInstance() {
		if(null == self::$_instance) {
			self::$_instance = new _DevblocksRegistryManager();
		}

		return self::$_instance;
	}
	
	private function __construct() {
	}
	
	// Retrieve
	public function get($key, $as=DevblocksRegistryEntry::TYPE_STRING, $default=null) {
		$this->_initIfEmpty($key, $default, $as);
		
		$entry = $this->_registry[$key]; /* @var $entry DevblocksRegistryEntry */

		// If we haven't loaded this variable yet (write before read)
		if(!$entry->loaded) {
			// If it was previously persisted
			if(null != ($result = DAO_DevblocksRegistry::get($key))) {
				$entry->initial_value = $result->entry_value;
				$entry->loaded = true;
				
				// If this is a delta entry, merge the differences
				if($entry->delta) {
					$entry->value = intval($entry->initial_value) + ($entry->value);
				}
			}
		}
		
		// [TODO] As
		
		return $entry->value;
	}
	
	private function _initIfEmpty($key, $value=null, $as=DevblocksRegistryEntry::TYPE_STRING) {
		if(null != (@$var = $this->_registry[$key]))
			return true;
		
		// Lazy load from DB
		if(null != ($result = DAO_DevblocksRegistry::get($key))) {
			$entry = new DevblocksRegistryEntry($result->entry_key, $result->entry_value, $result->entry_type);
			$entry->initial_value = $result->entry_value;
			$entry->loaded = true;

		// Initialize locally
		} else {
			$entry = new DevblocksRegistryEntry($key, $value, $as);
			$entry->loaded = true;
		}
		
		$this->_registry[$key] = $entry;
		return true;
	}
	
	public function persist($key, $bool=true) {
		if(isset($this->_registry[$key])) {
			$this->_registry[$key]->ephemeral = !$bool;
		}
	}
	
	public function save() {
		//var_dump($this->_registry);

		// Only persist non-ephemeral, 'dirty' variables
		foreach($this->_registry as $k => $var) { /* @var $var DevblocksRegistryEntry */
			if($var->ephemeral || !$var->dirty)
				continue;

			if($var->delta) {
				// If we've been adding to an initial value, find the delta
				if(!empty($var->initial_value)) {
					$value = intval($var->value) - intval($var->initial_value);
				} else {
					$value = intval($var->value);
				}
			}
			
			DAO_DevblocksRegistry::set($var->key, $value, $var->as, $var->delta);
		}
	}
	
	// Store
	public function set($key, $value, $as=DevblocksRegistryEntry::TYPE_STRING) {
		$this->_initIfEmpty($key, null, $as);
		$this->_registry[$key]->set($value, $as);
	}
	
	// Increment/Decrement
	public function increment($key, $by, $min=0, $max=PHP_INT_MAX, $wrap=true) {
		$this->_initIfEmpty($key, 0, DevblocksRegistryEntry::TYPE_NUMBER);
		$this->_registry[$key]->increment($by, $min, $max, $wrap);
	}
};

class DevblocksRegistryEntry {
	const TYPE_STRING = 'string';
	const TYPE_NUMBER = 'number';
	const TYPE_BOOL = 'bool';
	
	public $key = null;
	public $initial_value = null;
	public $value = null;
	public $as = null;
	public $delta = null;
	public $ephemeral = null;
	public $dirty = false;
	public $loaded = false;
	
	public function __construct($key, $value=null, $as=null, $ephemeral=false, $delta=false) {
		$this->key = $key;
		$this->initial_value = null;
		$this->value = $value;
		$this->as = $as;
		$this->delta = $delta;
		$this->ephemeral = $ephemeral;
	}
	
	public function increment($by, $min=0, $max=PHP_INT_MAX, $wrap=true) {
		$this->as = self::TYPE_NUMBER;
		$val = intval($this->value) + intval($by);
		
		// Wrap at bounds
		if($wrap) {
			if($val < $min) {
				$val = $max;
			} elseif($val > $max) {
				$val = $min;
			}
		
		// Otherwise, clamp
		} else {
			$val = max($min, min($max, $val));
		}

		$this->value = $val;
		$this->delta = true;
		$this->dirty = true;
	}
	
	public function set($val, $as=DevblocksRegistryEntry::TYPE_STRING) {
		$this->as = $as;
		$this->value = $val;
		$this->delta = false;
		$this->dirty = true;
	}
};

class DAO_DevblocksRegistry extends C4_ORMHelper {
	const ENTRY_KEY = 'entry_key';
	const ENTRY_TYPE = 'entry_type';
	const ENTRY_VALUE = 'entry_value';
	
	public static function get($key) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$row = $db->GetRow(sprintf("SELECT entry_key, entry_type, entry_value FROM devblocks_registry WHERE entry_key = %s",
			$db->qstr($key)
		));
		
		if(empty($row))
			return null;
		
		$object = new Model_DevblocksRegistry();
		$object->entry_key = $row['entry_key'];
		$object->entry_type = $row['entry_type'];
		$object->entry_value = $row['entry_value'];
		
		return $object;
	}
	
	public static function set($key, $value, $as=DevblocksRegistryEntry::TYPE_STRING, $delta=false) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if($delta && $as == DevblocksRegistryEntry::TYPE_NUMBER) {
			// Delta update if the row exists
			$db->Execute(sprintf("UPDATE devblocks_registry SET entry_value = entry_value + %d WHERE entry_key = %s",
				$value,
				$db->qstr($key)
			));
			
			$result = $db->Affected_Rows();
			
			// Othewise, create it
			if(empty($result)) {
				$db->Execute(sprintf("INSERT INTO devblocks_registry (entry_key, entry_type, entry_value) VALUES (%s, %s, %s)",
					$db->qstr($key),
					$db->qstr($as),
					$db->qstr($value)
				));
			}
			
		} else {
			$db->Execute(sprintf("REPLACE INTO devblocks_registry (entry_key, entry_type, entry_value) VALUES (%s, %s, %s)",
				$db->qstr($key),
				$db->qstr($as),
				$db->qstr($value)
			));
		}
	}
};

class Model_DevblocksRegistry {
	public $entry_key;
	public $entry_type;
	public $entry_value;
};
