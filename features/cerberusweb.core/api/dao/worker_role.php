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
	const _CACHE_ALL = 'ch_acl';
	
	const CACHE_KEY_ROLES = 'roles';
	const CACHE_KEY_PRIVS_BY_ROLE = 'privs_by_role';
	const CACHE_KEY_WORKERS_BY_ROLE = 'workers_by_role';
	const CACHE_KEY_PRIVS_BY_WORKER = 'privs_by_worker';
	
	const ID = 'id';
	const NAME = 'name';

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
	}
	
	static function getACL($nocache=false) {
	    $cache = DevblocksPlatform::getCacheService();
	    if($nocache || null === ($acl = $cache->load(self::_CACHE_ALL))) {
	    	$db = DevblocksPlatform::getDatabaseService();
	    	
	    	// All roles
	    	$all_roles = self::getWhere();
	    	$all_worker_ids = array();

	    	// All privileges by role
	    	$all_privs = array();
	    	$rs = $db->Execute("SELECT role_id, priv_id FROM worker_role_acl WHERE has_priv = 1 ORDER BY role_id, priv_id");
	    	while($row = mysql_fetch_assoc($rs)) {
	    		$role_id = intval($row['role_id']);
	    		$priv_id = $row['priv_id'];
	    		if(!isset($all_privs[$role_id]))
	    			$all_privs[$role_id] = array();
	    		
	    		$all_privs[$role_id][$priv_id] = $priv_id;
	    	}
	    	mysql_free_result($rs);
	    	
	    	// All workers by role
	    	$all_rosters = array();
	    	$rs = $db->Execute("SELECT role_id, worker_id FROM worker_to_role");
	    	while($row = mysql_fetch_assoc($rs)) {
	    		$role_id = intval($row['role_id']);
	    		$worker_id = intval($row['worker_id']);
	    		if(!isset($all_rosters[$role_id]))
	    			$all_rosters[$role_id] = array();

	    		$all_rosters[$role_id][$worker_id] = $worker_id;
	    		$all_worker_ids[$worker_id] = $worker_id;
	    	}
	    	mysql_free_result($rs);
	    	
	    	// Aggregate privs by workers' roles (if set anywhere, keep)
	    	$privs_by_worker = array();
	    	if(is_array($all_worker_ids))
	    	foreach($all_worker_ids as $worker_id) {
	    		if(!isset($privs_by_worker[$worker_id]))
	    			$privs_by_worker[$worker_id] = array();
	    		
	    		foreach($all_rosters as $role_id => $role_roster) {
	    			if(isset($role_roster[$worker_id]) && isset($all_privs[$role_id])) {
	    				// If we have privs from other groups, merge on the keys
	    				$current_privs = (is_array($privs_by_worker[$worker_id])) ? $privs_by_worker[$worker_id] : array();
    					$privs_by_worker[$worker_id] = array_merge($current_privs,$all_privs[$role_id]);
	    			}
	    		}
	    	}
	    	
	    	$acl = array(
	    		self::CACHE_KEY_ROLES => $all_roles,
	    		self::CACHE_KEY_PRIVS_BY_ROLE => $all_privs,
	    		self::CACHE_KEY_WORKERS_BY_ROLE => $all_rosters,
	    		self::CACHE_KEY_PRIVS_BY_WORKER => $privs_by_worker,
	    	);
	    	
    	    $cache->save($acl, self::_CACHE_ALL);
	    }
	    
	    return $acl;
	    
	}
	
	/**
	 * @param string $where
	 * @return Model_WorkerRole[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, name ".
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
		$db->Execute(sprintf("DELETE FROM worker_to_role WHERE role_id IN (%s)", $ids_list));
		$db->Execute(sprintf("DELETE FROM worker_role_acl WHERE role_id IN (%s)", $ids_list));
		
		return true;
	}
	
	static function getRolePrivileges($role_id) {
		$acl = self::getACL();
		
		if(empty($role_id) || !isset($acl[self::CACHE_KEY_PRIVS_BY_ROLE][$role_id]))
			return array();
		
		return $acl[self::CACHE_KEY_PRIVS_BY_ROLE][$role_id];
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

		// Load entire ACL list
		$acl = DevblocksPlatform::getAclRegistry();
		
		// Set ACLs according to the new master list
		if(!empty($privileges) && !empty($acl)) {
			foreach($privileges as $priv) { /* @var $priv DevblocksAclPrivilege */
				$sql = sprintf("INSERT INTO worker_role_acl (role_id, priv_id, has_priv) ".
					"VALUES (%d, %s, %d)",
					$role_id,
					$db->qstr($priv),
					1
				);
				$db->Execute($sql);
			}
		}
		
		unset($privileges);
		
		self::clearCache();
	}
	
	static function getRoleWorkers($role_id) {
		$acl = self::getACL();
		
		if(empty($role_id) || !isset($acl[self::CACHE_KEY_WORKERS_BY_ROLE][$role_id]))
			return array();
		
		return $acl[self::CACHE_KEY_WORKERS_BY_ROLE][$role_id];
	}
	
	static function setRoleWorkers($role_id, $worker_ids) {
		if(!is_array($worker_ids)) $worker_ids = array($worker_ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($role_id))
			return;
			
		// Wipe roster
		$sql = sprintf("DELETE FROM worker_to_role WHERE role_id = %d", $role_id);
		$db->Execute($sql);
		
		// Add desired workers to role's roster		
		if(is_array($worker_ids))
		foreach($worker_ids as $worker_id) {
			$sql = sprintf("INSERT INTO worker_to_role (worker_id, role_id) ".
				"VALUES (%d, %d)",
				$worker_id,
				$role_id
			);
			$db->Execute($sql);
		}
		
		self::clearCache();
	}
	
	static function clearCache() {
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::_CACHE_ALL);
	}
};

class Model_WorkerRole {
	public $id;
	public $name;
};