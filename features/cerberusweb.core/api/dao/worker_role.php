<?php
/***********************************************************************
 | Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
 |-----------------------------------------------------------------------
 | All source code & content (c) Copyright 2011, WebGroup Media LLC
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

class DAO_WorkerRole extends DevblocksORMHelper {
	const _CACHE_ROLES_ALL = 'ch_roles_all';
	const _CACHE_WORKER_PREFIX = 'ch_roles_worker_';
	
	const ID = 'id';
	const NAME = 'name';
	const PARAMS_JSON = 'params_json';
	
	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("INSERT INTO worker_role () ".
			"VALUES ()"
		);
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'worker_role', $fields);
		
		self::clearCache();
	}
	
	static function getCumulativePrivsByWorker($worker_id, $nocache=false) {
		$cache = DevblocksPlatform::getCacheService();

		if($nocache || null === ($privs = $cache->load(self::_CACHE_WORKER_PREFIX.$worker_id))) {
			$worker = DAO_Worker::get($worker_id);
			$memberships = $worker->getMemberships();
			$roles = DAO_WorkerRole::getAll();
			$privs = array();
			
			foreach($roles as $role_id => $role) {
				if('none' == $role->params['what'])
					continue;
				
				if(
					// If this applies to everyone
					'all' == $role->params['who'] ||
					(
						// ... or any group this worker is in
						'groups' == $role->params['who'] &&
						($in_groups = array_intersect(array_keys($memberships), $role->params['who_list'])) &&
						!empty($in_groups)
					) || 
					(
						// ... or this worker is on the list
						'workers' == $role->params['who'] &&
						in_array($worker_id, $role->params['who_list'])
					) 
				) {
					switch($role->params['what']) {
						case 'all':
							$privs = array('*' => array());
							$cache->save($privs, self::_CACHE_WORKER_PREFIX.$worker_id);
							return;
							break;
							
						case 'itemized':
							$privs = array_merge($privs, DAO_WorkerRole::getRolePrivileges($role_id));
							break;
					}
				}
			}
			
			$cache->save($privs, self::_CACHE_WORKER_PREFIX.$worker_id);
		}
		
		return $privs;
	}
	
	static function getAll($nocache=false) {
	    $cache = DevblocksPlatform::getCacheService();
	    if($nocache || null === ($roles = $cache->load(self::_CACHE_ROLES_ALL))) {
    	    $roles = DAO_WorkerRole::getWhere();
    	    $cache->save($roles, self::_CACHE_ROLES_ALL);
	    }
	    
	    return $roles;
	}
	
	/**
	 * @param string $where
	 * @return Model_WorkerRole[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, name, params_json ".
			"FROM worker_role ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY name asc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_WorkerRole	 */
	static function get($id) {
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_WorkerRole[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_WorkerRole();
			$object->id = $row['id'];
			$object->name = $row['name'];
			
			@$params = json_decode($row['params_json'], true) or array();
			$object->params = $params;
			
			$objects[$object->id] = $object;
		}
		mysql_free_result($rs);
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM worker_role WHERE id IN (%s)", $ids_list));
		$db->Execute(sprintf("DELETE FROM worker_role_acl WHERE role_id IN (%s)", $ids_list));
		
		self::clearCache();
		
		return true;
	}
	
	static function getRolePrivileges($role_id) {
		// [TODO] Cache all?
		
		$db = DevblocksPlatform::getDatabaseService();
		$acl = DevblocksPlatform::getAclRegistry();
		
		$privs = array();
		
		$results = $db->GetArray(sprintf("SELECT priv_id FROM worker_role_acl WHERE role_id = %d", $role_id));

		foreach($results as $row) {
			@$priv = $row['priv_id'];
			$privs[$priv] = isset($acl[$priv]) ? $acl[$priv] : array();
		}
		
		return $privs;
	}
	
	/**
	 * @param integer $role_id
	 * @param array $privileges
	 * @param boolean $replace
	 */
	static function setRolePrivileges($role_id, $privileges) {
		if(!is_array($privileges)) $privileges = array($privileges);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($role_id))
			return;
		
		// Wipe all privileges on blank replace
		$sql = sprintf("DELETE FROM worker_role_acl WHERE role_id = %d", $role_id);
		$db->Execute($sql);

		// Set ACLs according to the new list
		if(!empty($privileges)) {
			foreach($privileges as $priv) { /* @var $priv DevblocksAclPrivilege */
				$sql = sprintf("INSERT INTO worker_role_acl (role_id, priv_id) ".
					"VALUES (%d, %s)",
					$role_id,
					$db->qstr($priv)
				);
				$db->Execute($sql);
			}
		}
	}
	
	static function clearCache() {
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::_CACHE_ROLES_ALL);
	}
	
	static function clearWorkerCache($worker_id=null) {
		$cache = DevblocksPlatform::getCacheService();
		
		if(!empty($worker_id)) {
			$cache->remove(self::_CACHE_WORKER_PREFIX.$worker_id);
		} else {
			$workers = DAO_Worker::getAll();
			foreach($workers as $worker_id => $worker)
				$cache->remove(self::_CACHE_WORKER_PREFIX.$worker_id);
		}
	}
};

class Model_WorkerRole {
	public $id;
	public $name;
	public $params = array();
};