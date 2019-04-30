<?php
class _DevblocksRegistryManager {
	static $_instance = null;
	
	private $_registry = [];
	
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
		
		switch($as) {
			case DevblocksRegistryEntry::TYPE_BOOL:
				return !empty($entry->value);
				break;
				
			case DevblocksRegistryEntry::TYPE_NUMBER:
				return intval($entry->value);
				break;
				
			case DevblocksRegistryEntry::TYPE_STRING:
				return $entry->value;
				break;
				
			case DevblocksRegistryEntry::TYPE_JSON:
				return json_decode($entry->value, true);
				break;
				
			default:
				return $entry->value;
				break;
		}
	}
	
	private function _initIfEmpty($key, $value=null, $as=DevblocksRegistryEntry::TYPE_STRING) {
		if(array_key_exists($key, $this->_registry))
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
	
	public function persist($key, $bool=true, $expires_at=0) {
		if(isset($this->_registry[$key])) {
			$this->_registry[$key]->ephemeral = !$bool;
			
			if($bool) {
				$this->_registry[$key]->expires_at = $expires_at;
			} else {
				$this->_registry[$key]->expires_at = null;
			}
		}
	}
	
	public function save() {
		//var_dump($this->_registry);

		// Only persist non-ephemeral, 'dirty' variables
		foreach($this->_registry as $var) { /* @var $var DevblocksRegistryEntry */
			if($var->ephemeral || !$var->dirty)
				continue;
			
			if($var->delta) {
				// If we've been adding to an initial value, find the delta
				if(!empty($var->initial_value)) {
					$var->value = intval($var->value) - intval($var->initial_value);
				} else {
					$var->value = intval($var->value);
				}
			}
			
			DAO_DevblocksRegistry::set($var->key, $var->value, $var->as, $var->expires_at, $var->delta);
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
	
	public function delete($key) {
		if(array_key_exists($key, $this->_registry)) {
			DAO_DevblocksRegistry::delete($key);
			unset($this->_registry[$key]);
		}
	}
};

class DevblocksRegistryEntry {
	const TYPE_STRING = 'string';
	const TYPE_NUMBER = 'number';
	const TYPE_BOOL = 'bool';
	const TYPE_JSON = 'json';
	
	public $key = null;
	public $initial_value = null;
	public $value = null;
	public $as = null;
	public $delta = null;
	public $ephemeral = null;
	public $expires_at = 0;
	public $dirty = false;
	public $loaded = false;
	
	public function __construct($key, $value=null, $as=null, $ephemeral=false, $delta=false, $expires_at=0) {
		$this->key = $key;
		$this->initial_value = null;
		$this->value = $value;
		$this->as = $as;
		$this->delta = $delta;
		$this->ephemeral = $ephemeral;
		$this->expires_at = 0;
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
		$this->value = ($as == DevblocksRegistryEntry::TYPE_JSON) ? json_encode($val) : $val;
		$this->delta = false;
		$this->dirty = true;
	}
};

class DAO_DevblocksRegistry extends DevblocksORMHelper {
	const ENTRY_KEY = 'entry_key';
	const ENTRY_TYPE = 'entry_type';
	const ENTRY_VALUE = 'entry_value';
	const ENTRY_EXPIRES_AT = 'entry_expires_at';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// varchar(255)
		$validation
			->addField(self::ENTRY_KEY)
			->string()
			->setMaxLength(255)
			;
		// varchar(32)
		$validation
			->addField(self::ENTRY_TYPE)
			->string()
			->setMaxLength(32)
			;
		// text
		$validation
			->addField(self::ENTRY_VALUE)
			->string()
			->setMaxLength(65535)
			;
		// timestamp
		$validation
			->addField(self::ENTRY_EXPIRES_AT)
			->timestamp()
			;

		return $validation->getFields();
	}
	
	public static function get($key) {
		$db = DevblocksPlatform::services()->database();
		
		$row = $db->GetRowMaster(sprintf("SELECT entry_key, entry_type, entry_value, entry_expires_at FROM devblocks_registry WHERE entry_key = %s",
			$db->qstr($key)
		));
		
		if(empty($row))
			return null;
		
		$object = new Model_DevblocksRegistry();
		$object->entry_key = $row['entry_key'];
		$object->entry_type = $row['entry_type'];
		$object->entry_value = $row['entry_value'];
		$object->entry_expires_at = intval($row['entry_expires_at']);
		
		return $object;
	}
	
	public static function set($key, $value, $as=DevblocksRegistryEntry::TYPE_STRING, $expires_at=0, $delta=false) {
		$db = DevblocksPlatform::services()->database();
		
		if($delta && $as == DevblocksRegistryEntry::TYPE_NUMBER) {
			// Delta update if the row exists
			$db->ExecuteMaster(sprintf("UPDATE devblocks_registry SET entry_value = entry_value + %d, entry_expires_at=%d WHERE entry_key = %s",
				$value,
				$expires_at,
				$db->qstr($key)
			));
			
			$result = $db->Affected_Rows();
			
			// Othewise, create it
			if(empty($result)) {
				$db->ExecuteMaster(sprintf("INSERT INTO devblocks_registry (entry_key, entry_type, entry_value, entry_expires_at) VALUES (%s, %s, %s, %d)",
					$db->qstr($key),
					$db->qstr($as),
					$db->qstr($value),
					$expires_at
				));
			}
			
		} else {
			$db->ExecuteMaster(sprintf("REPLACE INTO devblocks_registry (entry_key, entry_type, entry_value, entry_expires_at) VALUES (%s, %s, %s, %d)",
				$db->qstr($key),
				$db->qstr($as),
				$db->qstr($value),
				$expires_at
			));
		}
	}
	
	public static function delete($key) {
		$db = DevblocksPlatform::services()->database();
		
		$db->ExecuteMaster(sprintf("DELETE FROM devblocks_registry WHERE entry_key = %s",
			$db->qstr($key)
		));
	}
	
	public static function maint() {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("DELETE FROM devblocks_registry WHERE entry_expires_at BETWEEN 1 AND %d",
			time()
		);
		$db->ExecuteMaster($sql);
		
		return true;
	}
};

class Model_DevblocksRegistry {
	public $entry_key = null;
	public $entry_type = null;
	public $entry_value = null;
	public $entry_expires_at = 0;
};
