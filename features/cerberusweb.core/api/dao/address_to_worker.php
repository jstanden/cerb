<?php
/***********************************************************************
| Cerb(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2013, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/

class DAO_AddressToWorker { // extends DevblocksORMHelper
	const ADDRESS = 'address';
	const WORKER_ID = 'worker_id';
	const IS_CONFIRMED = 'is_confirmed';
	const CODE = 'code';
	const CODE_EXPIRE = 'code_expire';

	static function assign($address, $worker_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($address) || empty($worker_id))
			return NULL;

		// Force lowercase
		$address = strtolower($address);

		$sql = sprintf("INSERT INTO address_to_worker (address, worker_id, is_confirmed, code, code_expire) ".
			"VALUES (%s, %d, 0, '', 0)",
			$db->qstr($address),
			$worker_id
		);
		$db->Execute($sql);

		return $address;
	}

	static function unassign($address) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($address))
			return NULL;
			
		$sql = sprintf("DELETE QUICK FROM address_to_worker WHERE address = %s",
			$db->qstr($address)
		);
		$db->Execute($sql);
	}
	
	static function unassignAll($worker_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($worker_id))
			return NULL;
			
		$sql = sprintf("DELETE QUICK FROM address_to_worker WHERE worker_id = %d",
			$worker_id
		);
		$db->Execute($sql);
	}
	
	static function update($addresses, $fields) {
		if(!is_array($addresses)) $addresses = array($addresses);
		
		$db = DevblocksPlatform::getDatabaseService();
		$sets = array();
		
		if(!is_array($fields) || empty($fields) || empty($addresses))
			return;
		
		foreach($fields as $k => $v) {
			if(is_null($v))
				$value = 'NULL';
			else
				$value = $db->qstr($v);
			
			$sets[] = sprintf("%s = %s",
				$k,
				$value
			);
		}
		
		$sql = sprintf("UPDATE %s SET %s WHERE %s IN ('%s')",
			'address_to_worker',
			implode(', ', $sets),
			self::ADDRESS,
			implode("','", $addresses)
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $worker_id
	 * @return Model_AddressToWorker[]
	 */
	static function getByWorker($worker_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$addresses = self::getWhere(sprintf("%s = %d",
			DAO_AddressToWorker::WORKER_ID,
			$worker_id
		));
		
		return $addresses;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $address
	 * @return Model_AddressToWorker
	 */
	static function getByAddress($address) {
		$db = DevblocksPlatform::getDatabaseService();
		
		// Force lower
		$address = strtolower($address);
		
		$addresses = self::getWhere(sprintf("%s = %s",
			DAO_AddressToWorker::ADDRESS,
			$db->qstr($address)
		));
		
		if(isset($addresses[$address]))
			return $addresses[$address];
			
		return NULL;
	}
	
	static function getAll($nocache=false, $with_disabled=false) {
		// [TODO] Cache? getAllActive?
		
		$workers = DAO_Worker::getAll();
		$addresses = self::getWhere();
		$results = array();
		
		foreach($addresses as $address) {
			@$worker = $workers[$address->worker_id];
			if(empty($worker) || $worker->is_disabled)
				continue;

			$results[$address->address] = $address;
		}
		
		return $results;
	}
	
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT address, worker_id, is_confirmed, code, code_expire ".
			"FROM address_to_worker ".
			(!empty($where) ? sprintf("WHERE %s ", $where) : " ").
			"ORDER BY address";
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());

		return self::_getObjectsFromResult($rs);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param resource $rs
	 * @return Model_AddressToWorker[]
	 */
	private static function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_AddressToWorker();
			$object->worker_id = intval($row['worker_id']);
			$object->address = strtolower($row['address']);
			$object->is_confirmed = intval($row['is_confirmed']);
			$object->code = $row['code'];
			$object->code_expire = intval($row['code_expire']);
			$objects[$object->address] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
};

class Model_AddressToWorker {
	public $address;
	public $worker_id;
	public $is_confirmed;
	public $code;
	public $code_expire;
};