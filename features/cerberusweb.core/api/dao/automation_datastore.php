<?php
class DAO_AutomationDatastore extends Cerb_ORMHelper {
	const DATA_KEY = 'data_key';
	const DATA_VALUE = 'data_value';
	const EXPIRES_AT = 'expires_at';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::DATA_KEY)
			->string()
			->setRequired(true)
			;
		$validation
			->addField(self::DATA_VALUE)
			->string()
			->setMaxLength('24 bits')
			->setRequired(true)
			;
		$validation
			->addField(self::EXPIRES_AT)
			->timestamp()
			;
		
		return $validation->getFields();
	}
	
	/**
	 * @param string|array $key
	 * @return string|array
	 */
	static function get($key) {
		$db = DevblocksPlatform::services()->database();
	
		if(is_array($key)) {
			$is_single = false;
		
		} else if(is_string($key)) {
			$is_single = true;
			$key = [$key];
			
		} else {
			return [];
		}
		
		$sql = sprintf("SELECT data_key, data_value, expires_at FROM automation_datastore ".
			"WHERE data_key IN (%s) AND (expires_at = 0 OR expires_at > %d)",
			implode(',', $db->qstrArray($key)),
			time()
		);
		
		$data = $db->GetArrayReader($sql);
		$results = [];
		
		foreach($data as $row) {
			$results[$row['data_key']] = json_decode($row['data_value'], true);
		}
		
		if($is_single) {
			return current($results);
		} else {
			return $results;
		}
	}
	
	static function set(string $key, $value, $expires_at=0) {
		$db = DevblocksPlatform::services()->database();
		
		if(!$key)
			return false;
		
		$sql = sprintf("REPLACE INTO automation_datastore (data_key, data_value, expires_at) ".
			"VALUES (%s, %s, %d)",
			$db->qstr($key),
			$db->qstr(json_encode($value)),
			$expires_at
		);
		$db->ExecuteMaster($sql);
		
		return true;
	}
	
	static function delete(array $keys) {
		$db = DevblocksPlatform::services()->database();
		
		if(!$keys)
			return false;
		
		$sql = sprintf("DELETE FROM automation_datastore ".
			"WHERE data_key IN (%s)",
			implode(',', $db->qstrArray($keys))
		);
		$db->ExecuteMaster($sql);
		
		return true;
	}
	
	static function maint() {
		$db = DevblocksPlatform::services()->database();
		
		// Delete any expired keys (0=forever)
		$sql = sprintf("DELETE FROM automation_datastore WHERE expires_at BETWEEN 1 AND %d",
			time()
		);
		$db->ExecuteMaster($sql);
		
		return true;
	}
};
