<?php
class DAO_ExplorerSet {
	const HASH = 'hash';	
	const POS = 'pos';	
	const PARAMS_JSON = 'params_json';
	
	static function createFromModels($models) {
		// Polymorph
		if(!is_array($models) && $models instanceof Model_ExplorerSet)
			$models = array($models);

		if(!is_array($models))
			return false;
			
		$db = DevblocksPlatform::getDatabaseService();

		$values = array();
		
		foreach($models as $model) { /* @var $model Model_ExplorerSet */
			$values[] = sprintf("(%s, %d, %s)",
				$db->qstr($model->hash),
				$model->pos,
				$db->qstr(json_encode($model->params))
			);
		}

		if(empty($values))
			return;
		
		$db->Execute(sprintf("INSERT INTO explorer_set (hash, pos, params_json) ".
			"VALUES %s",
			implode(',', $values)
		));
	}
	
	static function get($hash, $pos) {
		if(!is_array($pos))
			$pos = array($pos);
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$rs = $db->Execute(sprintf("SELECT hash, pos, params_json ".
			"FROM explorer_set ".
			"WHERE hash = %s ".
			"AND pos IN (%s) ",
			$db->qstr($hash),
			implode(',', $pos)
		));
		
		return self::_createObjectsFromResultSet($rs);
	}
	
	private static function _createObjectsFromResultSet($rs) {
		$objects = array();
		
		if(false !== $rs)
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_ExplorerSet();
			$object->hash = $row['hash'];
			$object->pos = $row['pos'];
			
			if(!empty($row['params_json'])) {
				if(false !== ($params_json = json_decode($row['params_json'], true)))
					$object->params = $params_json;
			}
			
			$objects[$object->pos] = $object;
		}
		
		return $objects;
	} 
	
	static function update($hash, $params) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$db->Execute(sprintf("UPDATE explorer_set SET params_json = %s WHERE hash = %s AND pos = 0",
			$db->qstr(json_encode($params)),
			$db->qstr($hash)
		));
	}
	
	static function maint() {
		$db = DevblocksPlatform::getDatabaseService();
		
		$rs = $db->Execute("SELECT hash, params_json FROM explorer_set WHERE pos = 0");
		
		if(false !== $rs)
		while($row = mysql_fetch_assoc($rs)) {
			if(false !== ($params = @json_decode($row['params_json'], true))) {
				if(!isset($params['last_accessed']) || $params['last_accessed'] < time()-86400) { // idle for 24 hours 
					$db->Execute(sprintf("DELETE FROM explorer_set WHERE hash = %s",
						$db->qstr($row['hash'])
					));
				}
			} 
		}
	}
};

class Model_ExplorerSet {
	public $hash = '';
	public $pos = 0;
	public $params = array();
};
