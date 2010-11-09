<?php
class DAO_SupportCenterAddressShare extends DevblocksORMHelper {
	const SHARE_ADDRESS_ID = 'share_address_id';
	const WITH_ADDRESS_ID = 'with_address_id';
	const IS_ENABLED = 'is_enabled';
	
	static function getContactAddressesWithShared($contact_id, $only_ids=false) {
		$db = DevblocksPlatform::getDatabaseService();
		$ids = array();
		
		$sql = sprintf("SELECT share_address_id AS id ".
			"FROM supportcenter_address_share ".
			"WHERE is_enabled = 1 AND with_address_id IN(SELECT id FROM address WHERE contact_person_id = %d) ".
			"UNION ".
			"SELECT id ".
			"FROM address ".
			"WHERE contact_person_id = %d",
			$contact_id,
			$contact_id
		);
		$rs = $db->Execute($sql);
		
		if(is_resource($rs))
		while($row = mysql_fetch_array($rs)) {
			$id = intval($row['id']);
			$ids[$id] = $id;
		}

		if(empty($ids))
			return array();
			
		if($only_ids)
			return $ids;
		
		return DAO_Address::getWhere(sprintf("%s IN (%s)",
			DAO_Address::ID,
			implode(',', $ids)
		));
	}
	
	static function setSharedWith($share_address_id, $with_address_ids) {
		if(!is_array($with_address_ids))
			$with_address_ids = array($with_address_ids);
		
		$db = DevblocksPlatform::getDatabaseService();
		
		// Share the source address with each of the target addresses
		foreach($with_address_ids as $with_address_id) {
			$sql = sprintf("INSERT IGNORE INTO supportcenter_address_share (share_address_id, with_address_id, is_enabled) ".
				"VALUES (%d, %d, %d)",
				$share_address_id,
				$with_address_id,
				0
			);
			$db->Execute($sql);
		}
		
		return TRUE;
	}
	
	static function getSharedBy($address_ids, $only_enabled=true) {
		if(!is_array($address_ids))
			$address_ids = array($address_ids);
			
		$db = DevblocksPlatform::getDatabaseService();
		$sql = sprintf("SELECT share.email AS share_address, aw.email AS with_address, sas.share_address_id, sas.with_address_id, sas.is_enabled ".
			"FROM supportcenter_address_share AS sas ".
			"INNER JOIN address AS share ON (share.id=sas.share_address_id) ".
			"INNER JOIN address AS aw ON (aw.id=sas.with_address_id) ".
			"WHERE share_address_id IN(%s) %s",
			implode(',', $address_ids),
			($only_enabled ? sprintf("AND sas.is_enabled=1 ") : "")
		);
		$rs = $db->Execute($sql);
		
		return self::getObjectsFromResultSet($rs);
	}
	
	static function getSharedWith($address_ids, $only_enabled=true) {
		if(!is_array($address_ids))
			$address_ids = array($address_ids);
			
		$db = DevblocksPlatform::getDatabaseService();
		$sql = sprintf("SELECT share.email AS share_address, aw.email AS with_address, sas.share_address_id, sas.with_address_id, sas.is_enabled ".
			"FROM supportcenter_address_share AS sas ".
			"INNER JOIN address AS share ON (share.id=sas.share_address_id) ".
			"INNER JOIN address AS aw ON (aw.id=sas.with_address_id) ".
			"WHERE with_address_id IN(%s) %s",
			implode(',', $address_ids),
			($only_enabled ? sprintf("AND sas.is_enabled=1 ") : "")
		);
		$rs = $db->Execute($sql);
		
		return self::getObjectsFromResultSet($rs);
	}
	
	private static function getObjectsFromResultSet($rs) {
		$objects = array();
		
		if(is_resource($rs))
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_SupportCenterAddressShare();
			$object->share_address_id = $row['share_address_id'];
			$object->share_address = $row['share_address'];
			$object->with_address_id = $row['with_address_id'];
			$object->with_address = $row['with_address'];
			$object->is_enabled = intval($row['is_enabled']);
			$objects[] = $object;
		}
		
		return $objects;
	}
	
	public static function updateWhere($fields, $where) {
		return parent::_updateWhere('supportcenter_address_share', $fields, $where);
	}
	
	public static function deleteWhereNotIn($share_id, $share_with_ids) {
		if(empty($share_id) || !is_array($share_with_ids))
			return;
			
		if(empty($share_with_ids))
			$share_with_ids = array(-1);
		
		$db = DevblocksPlatform::getDatabaseService();
		$sql = sprintf("DELETE FROM supportcenter_address_share WHERE share_address_id = %d AND with_address_id NOT IN (%s)",
			$share_id,
			implode(',', $share_with_ids)
		);
		$db->Execute($sql);
	}
	
	public static function delete($share_id, $with_id) {
		if(empty($share_id) || empty($with_id))
			return FALSE;
		
		$db = DevblocksPlatform::getDatabaseService();
		$sql = sprintf("DELETE FROM supportcenter_address_share WHERE share_address_id = %d AND with_address_id = %d",
			$share_id,
			$with_id
		);
		$db->Execute($sql);
	}
	
	public static function maint() {
		$db = DevblocksPlatform::getDatabaseService();
		
		// Clear orphaned address share rows
		$sql = "DELETE supportcenter_address_share ".
			"FROM supportcenter_address_share ". 
			"LEFT JOIN address AS a1 ON (supportcenter_address_share.share_address_id = a1.id) ". 
			"LEFT JOIN address AS a2 ON (supportcenter_address_share.with_address_id = a2.id) ".
			"LEFT JOIN contact_person AS cp1 ON (a1.contact_person_id = cp1.id) ".
			"LEFT JOIN contact_person AS cp2 ON (a2.contact_person_id = cp2.id) ".
			"WHERE a1.id IS NULL ". // address is missing
			"OR a2.id IS NULL ".
			"OR a1.contact_person_id = 0 ". // contact isn't assigned
			"OR a2.contact_person_id = 0 ".
			"OR cp1.id IS NULL ". // contact is missing
			"OR cp2.id IS NULL "
			;
		$db->Execute($sql);
	}
};

class Model_SupportCenterAddressShare {
	public $share_address_id = 0;
	public $share_address = '';
	public $with_address_id = 0;
	public $with_address = '';
	public $is_enabled = 0;
};