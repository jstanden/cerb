<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/

class DAO_ExplorerSet extends Cerb_ORMHelper {
	const HASH = 'hash';
	const PARAMS_JSON = 'params_json';
	const POS = 'pos';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::HASH)
			->string()
			->setMaxLength(32)
			->setRequired(true)
			;
		$validation
			->addField(self::PARAMS_JSON)
			->string()
			->setMaxLength('32 bits')
			;
		$validation
			->addField(self::POS)
			->uint()
			;
		
		return $validation->getFields();
	}
	
	static function createFromModels($models) {
		// Polymorph
		if(!is_array($models) && $models instanceof Model_ExplorerSet)
			$models = array($models);

		if(!is_array($models))
			return false;
			
		$db = DevblocksPlatform::services()->database();

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
		
		$db->ExecuteMaster(sprintf("INSERT INTO explorer_set (hash, pos, params_json) ".
			"VALUES %s",
			implode(',', $values)
		));
	}
	
	/**
	 *
	 * @param string $hash
	 * @param array $pos
	 * @return Model_ExplorerSet
	 */
	static function get($hash, $pos) {
		if(!is_array($pos))
			$pos = array($pos);
		
		$db = DevblocksPlatform::services()->database();
		
		$rs = $db->ExecuteSlave(sprintf("SELECT hash, pos, params_json ".
			"FROM explorer_set ".
			"WHERE hash = %s ".
			"AND pos IN (%s) ",
			$db->qstr($hash),
			implode(',', $pos)
		));
		
		return self::_createObjectsFromResultSet($rs);
	}
	
	static function set($hash, $params, $pos) {
		$db = DevblocksPlatform::services()->database();
		
		$db->ExecuteMaster(sprintf("REPLACE INTO explorer_set (params_json, hash, pos) VALUES (%s,%s,%d)",
			$db->qstr(json_encode($params)),
			$db->qstr($hash),
			$pos
		));
	}
	
	private static function _createObjectsFromResultSet($rs) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
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
		$db = DevblocksPlatform::services()->database();
		
		$db->ExecuteMaster(sprintf("UPDATE explorer_set SET params_json = %s WHERE hash = %s AND pos = 0",
			$db->qstr(json_encode($params)),
			$db->qstr($hash)
		));
	}
	
	static function maint() {
		$db = DevblocksPlatform::services()->database();
		$logger = DevblocksPlatform::services()->log();
		
		$rs = $db->ExecuteMaster("SELECT hash, params_json FROM explorer_set WHERE pos = 0");
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			if(false !== ($params = @json_decode($row['params_json'], true))) {
				if(!isset($params['last_accessed']) || $params['last_accessed'] < time()-86400) { // idle for 24 hours
					$db->ExecuteMaster(sprintf("DELETE FROM explorer_set WHERE hash = %s",
						$db->qstr($row['hash'])
					));
				}
			}
		}
		
		$logger->info('[Maint] Cleaned up explorer items.');
	}
};

class Model_ExplorerSet {
	public $hash = '';
	public $pos = 0;
	public $params = array();
};
