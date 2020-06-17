<?php
class DAO_BotDatastore extends Cerb_ORMHelper {
	const BOT_ID = 'bot_id';
	const DATA_KEY = 'data_key';
	const DATA_VALUE = 'data_value';
	const EXPIRES_AT = 'expires_at';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::BOT_ID)
			->id()
			->setRequired(true)
			;
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
	
	static function get($bot_id, $key) {
		$db = DevblocksPlatform::services()->database();
		
		if(empty($bot_id) || empty($key) || !is_string($key))
			return false;
		
		$sql = sprintf("SELECT data_value FROM bot_datastore ".
			"WHERE bot_id = %d AND data_key = %s AND (expires_at = 0 OR expires_at > %d)",
			$bot_id,
			$db->qstr($key),
			time()
		);
		return $db->GetOneReader($sql);
	}
	
	static function set($bot_id, $key, $value, $expires_at=0) {
		$db = DevblocksPlatform::services()->database();
		
		if(empty($bot_id) || empty($key) || !is_string($key) || !is_string($value))
			return false;
		
		// If we're setting a blank value, delete it instead
		if(0 == strlen($value)) {
			$sql = sprintf("DELETE FROM bot_datastore ".
				"WHERE bot_id = %d AND data_key = %s",
				$bot_id,
				$db->qstr($key)
			);
			$db->ExecuteMaster($sql);
			
		} else {
			$sql = sprintf("REPLACE INTO bot_datastore (bot_id, data_key, data_value, expires_at) ".
				"VALUES (%d, %s, %s, %d)",
				$bot_id,
				$db->qstr($key),
				$db->qstr($value),
				$expires_at
			);
			$db->ExecuteMaster($sql);
		}
		
		return true;
	}
	
	static function maint() {
		$db = DevblocksPlatform::services()->database();
		
		// Delete any expired keys (0=forever)
		$sql = sprintf("DELETE FROM bot_datastore WHERE expires_at BETWEEN 1 AND %d",
			time()
		);
		$db->ExecuteMaster($sql);
		
		return true;
	}
};
