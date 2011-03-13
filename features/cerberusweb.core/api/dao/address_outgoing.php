<?php
class DAO_AddressOutgoing extends DevblocksORMHelper {
	const ADDRESS_ID = 'address_id';
	const IS_DEFAULT = 'is_default';
	const REPLY_PERSONAL = 'reply_personal';
	const REPLY_SIGNATURE = 'reply_signature';
	
	const _CACHE_ALL = 'dao_address_outgoing_all';
	
	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		@$id = $fields[self::ADDRESS_ID];
		
		if(empty($id))
			return false;
		
		$sql = sprintf("INSERT IGNORE INTO address_outgoing (address_id) ".
			"VALUES (%d)",
			$id
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		
		self::update($id, $fields);
		
		return $id;		
	}
	
	static function update($ids, $fields) {
		self::_update($ids, 'address_outgoing', $fields, 'address_id');
		self::clearCache();
	}
	
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::getCacheService();

		if($nocache || null === ($froms = $cache->load(self::_CACHE_ALL))) {
			$db = DevblocksPlatform::getDatabaseService();
			$froms = array();
			
			$sql = "SELECT address_outgoing.address_id, address.email, address_outgoing.is_default, address_outgoing.reply_personal, address_outgoing.reply_signature ".
				"FROM address_outgoing ".
				"INNER JOIN address ON (address.id=address_outgoing.address_id) ".
				"ORDER BY address.email ASC "
				;
			$rs = $db->Execute($sql);
			
			$froms = self::_getObjectsFromResultSet($rs);
			
			$cache->save($froms, self::_CACHE_ALL);
		}
		
		return $froms;		
	}
	
	/**
	 * 
	 * @return Model_AddressOutgoing
	 */
	static public function getDefault() {
		$froms = self::getAll();
		
		foreach($froms as $from) {
			if($from->is_default)
				return $from;
		}
		
//		$default = new Model_AddressOutgoing();
//		$default->address_id = 0;
//		$default->email = 'do-not-reply';
		
		return null;
	}
	
	static public function setDefault($address_id) {
		$db = DevblocksPlatform::getDatabaseService();
		$db->Execute("UPDATE address_outgoing SET is_default = 0");
		$db->Execute(sprintf("UPDATE address_outgoing SET is_default = 1 WHERE address_id = %d", $address_id));
	}
	
	/**
	 * 
	 * @param integer $id
	 * @return Model_AddressOutgoing|null
	 */
	static public function get($id) {
		$addresses = self::getAll();		
		
		if(isset($addresses[$id]))
			return $addresses[$id];
			
		return null;
	}
	
	static private function _getObjectsFromResultSet($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_AddressOutgoing();
			$object->address_id = intval($row['address_id']);
			$object->email = $row['email'];
			$object->is_default = intval($row['is_default']);
			$object->reply_personal = $row['reply_personal'];
			$object->reply_signature = $row['reply_signature'];
			$objects[$object->address_id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;		
	}
	
	static function clearCache() {
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::_CACHE_ALL);
	}
};

class Model_AddressOutgoing {
	public $address_id;
	public $email;
	public $is_default = 0;
	public $reply_personal = '';
	public $reply_signature = '';
};