<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2015, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerbweb.com	    http://www.webgroupmedia.com/
***********************************************************************/

class DAO_AddressToWorker extends Cerb_ORMHelper {
	const _CACHE_ALL = 'cerb:dao:address_to_worker:all';
	
	const ADDRESS_ID = 'address_id';
	const WORKER_ID = 'worker_id';
	const IS_CONFIRMED = 'is_confirmed';
	const CODE = 'code';
	const CODE_EXPIRE = 'code_expire';

	static function assign($address_id, $worker_id, $is_confirmed=false) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($address_id) || empty($worker_id))
			return false;

		$sql = sprintf("REPLACE INTO address_to_worker (address_id, worker_id, is_confirmed, code, code_expire) ".
			"VALUES (%d, %d, %d, '', 0)",
			$address_id,
			$worker_id,
			($is_confirmed ? 1 : 0)
		);
		$db->ExecuteMaster($sql);

		self::clearCache();
		return true;
	}

	static function unassign($address_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($address_id))
			return false;
			
		$sql = sprintf("DELETE FROM address_to_worker WHERE address_id = %d",
			$address_id
		);
		$db->ExecuteMaster($sql);
		
		self::clearCache();
		return true;
	}
	
	static function unassignAll($worker_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($worker_id))
			return NULL;
			
		$sql = sprintf("DELETE FROM address_to_worker WHERE worker_id = %d",
			$worker_id
		);
		$db->ExecuteMaster($sql);
		
		self::clearCache();
	}
	
	static function update($address_ids, $fields) {
		if(!is_array($address_ids))
			$address_ids = array($address_ids);
		
		$address_ids = DevblocksPlatform::sanitizeArray($address_ids, 'int');
		
		$db = DevblocksPlatform::getDatabaseService();
		$sets = array();
		
		if(!is_array($fields) || empty($fields) || empty($address_ids))
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
		
		$sql = sprintf("UPDATE %s SET %s WHERE %s IN (%s)",
			'address_to_worker',
			implode(', ', $sets),
			$db->escape(self::ADDRESS_ID),
			implode(',', $address_ids)
		);
		$db->ExecuteMaster($sql);
		
		self::clearCache();
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $worker_id
	 * @return Model_AddressToWorker[]
	 */
	static function getByWorker($worker_id) {
		$addresses = self::getAll();
		
		$addresses = array_filter($addresses, function($address) use ($worker_id) {
			return ($address->worker_id == $worker_id);
		});
		
		return $addresses;
	}
	
	static function getByWorkers() {
		$addys = self::getAll();
		$workers = DAO_Worker::getAll();
		
		array_walk($addys, function($addy) use ($workers) {
			if(!$addy->is_confirmed)
				return;
			
			if(!isset($workers[$addy->worker_id]))
				return;
			
			if(!isset($workers[$addy->worker_id]->relay_emails))
				$workers[$addy->worker_id]->relay_emails = array();
				
			$workers[$addy->worker_id]->relay_emails[] = $addy->address_id;
		});
		
		return $workers;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $address
	 * @return Model_AddressToWorker
	 */
	static function getByEmail($email) {
		if(false == ($model = DAO_Address::getByEmail($email)))
			return false;
		
		$addresses = self::getAll(); // Use the cache
		
		foreach($addresses as $address) {
			if($address->address_id == $model->id)
				return $address; 
		}
		
		return NULL;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $address_id
	 * @return Model_AddressToWorker
	 */
	static function getByAddressId($address_id) {
		$addresses = self::getAll(); // Use the cache
		
		if(isset($addresses[$address_id]))
			return $addresses[$address_id];
			
		return NULL;
	}
	
	static function getAll($nocache=false, $with_disabled=false) {
		$cache = DevblocksPlatform::getCacheService();
		
		if($nocache || null === ($results = $cache->load(self::_CACHE_ALL))) {
			$results = self::getWhere(
				null,
				null,
				null,
				null,
				Cerb_ORMHelper::OPT_GET_MASTER_ONLY
			);
			
			if(!is_array($results))
				return false;
			
			if(!empty($results))
				$cache->save($results, self::_CACHE_ALL);
		}
		
		if(!$with_disabled) {
			$workers = DAO_Worker::getAll();
			
			$results = array_filter($results, function($address) use ($workers) {
				@$worker = $workers[$address->worker_id];
				return !(empty($worker) || $worker->is_disabled);
			});
		}
		
		return $results;
	}
	
	static function getWhere($where=null, $sortBy=null, $sortAsc=null, $limit=null, $options=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT address_id, worker_id, is_confirmed, code, code_expire ".
			"FROM address_to_worker ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		
		if($options & Cerb_ORMHelper::OPT_GET_MASTER_ONLY) {
			$rs = $db->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		} else {
			$rs = $db->ExecuteSlave($sql);
		}
		
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
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_AddressToWorker();
			$object->worker_id = intval($row['worker_id']);
			$object->address_id = intval($row['address_id']);
			$object->is_confirmed = intval($row['is_confirmed']);
			$object->code = $row['code'];
			$object->code_expire = intval($row['code_expire']);
			$objects[$object->address_id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	public static function clearCache() {
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::_CACHE_ALL);
	}
};

class Model_AddressToWorker {
	public $address_id = 0;
	public $worker_id = 0;
	public $is_confirmed = 0;
	public $code = '';
	public $code_expire = 0;
	
	private $_model = null;
	
	function getWorker() {
		return DAO_Worker::get($this->worker_id);
	}
	
	function getEmailModel() {
		if(null == $this->_model)
			$this->_model = DAO_Address::get($this->address_id);
		
		return $this->_model;
	}
	
	function getEmailAsString() {
		if(false == ($model = $this->getEmailModel()))
			return '';
	
		return $model->email;
	}
};