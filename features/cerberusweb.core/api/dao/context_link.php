<?php
class DAO_ContextLink {
	const FROM_CONTEXT = 'from_context';
	const FROM_CONTEXT_ID = 'from_context_id';
	const TO_CONTEXT = 'to_context';
	const TO_CONTEXT_ID = 'to_context_id';

	// [TODO] setLinks
	static public function setLink($src_context, $src_context_id, $dst_context, $dst_context_id, $is_reciprocal=true) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("INSERT IGNORE INTO context_link (from_context, from_context_id, to_context, to_context_id) ".
			"VALUES (%s, %d, %s, %d) ",
			$db->qstr($src_context),
			$src_context_id,
			$db->qstr($dst_context),
			$dst_context_id
		);
		$db->Execute($sql);
		
		// Reciprocal
		if($is_reciprocal) {
			$sql = sprintf("INSERT IGNORE INTO context_link (from_context, from_context_id, to_context, to_context_id) ".
				"VALUES (%s, %d, %s, %d) ",
				$db->qstr($dst_context),
				$dst_context_id,
				$db->qstr($src_context),
				$src_context_id
			);
			$db->Execute($sql);
		}
	}
	
	static public function getLinks($context, $context_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT to_context AS context, to_context_id AS context_id ".
			"FROM context_link ".
			"WHERE (%s = %s AND %s = %d) ",
			self::FROM_CONTEXT,
			$db->qstr($context),
			self::FROM_CONTEXT_ID,
			$context_id
		);
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResultSet($rs);
	}
	
	static public function getWorkers($context, $context_id) {
		list($results, $null) = DAO_Worker::search(
			array(
				SearchFields_Worker::ID,
			),
			array(
				new DevblocksSearchCriteria(SearchFields_Worker::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_Worker::CONTEXT_LINK_ID,'=',$context_id),
			),
			0,
			0,
			null,
			null,
			false
		);
		
		$workers = array();
		
		if(!empty($results)) {
			$workers = DAO_Worker::getWhere(sprintf("%s IN (%s)",
				DAO_Worker::ID,
				implode(',', array_keys($results))
			));
		}
		
		return $workers;
	}
	
	static private function _getObjectsFromResultSet($rs) {
		$objects = array();
		
		if(is_resource($rs))
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_ContextLink($row['context'], $row['context_id']);
			$objects[] = $object;
		}
		
		return $objects;
	}
	
	static public function delete($context, $context_ids) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(!is_array($context_ids))
			$context_ids = array($context_ids);
		
		$ids = implode(',', $context_ids);
		
		if(empty($ids))
			return;
		
		$sql = sprintf("DELETE FROM context_link WHERE (from_context = %s AND from_context_id IN (%s)) OR (to_context = %s AND to_context_id IN (%s))",
			$db->qstr($context),
			$ids,
			$db->qstr($context),
			$ids
		);
		$db->Execute($sql);
	}
	
	static public function deleteLink($src_context, $src_context_id, $dst_context, $dst_context_id, $is_reciprocal=true) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE FROM context_link WHERE from_context = %s AND from_context_id = %d AND to_context = %s AND to_context_id = %d",
			$db->qstr($src_context),
			$src_context_id,
			$db->qstr($dst_context),
			$dst_context_id
		);
		$db->Execute($sql);

		if($is_reciprocal) {
			$sql = sprintf("DELETE FROM context_link WHERE from_context = %s AND from_context_id = %d AND to_context = %s AND to_context_id = %d",
				$db->qstr($dst_context),
				$dst_context_id,
				$db->qstr($src_context),
				$src_context_id
			);
			$db->Execute($sql);
		}
		
		return true;
	}
};

class Model_ContextLink {
	public $context = '';
	public $context_id = 0;
	
	function __construct($context, $context_id) {
		$this->context = $context;
		$this->context_id = $context_id;
	}
};