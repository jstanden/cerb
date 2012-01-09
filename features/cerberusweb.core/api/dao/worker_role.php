<?php
/***********************************************************************
 | Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
 |-----------------------------------------------------------------------
 | All source code & content (c) Copyright 2012, WebGroup Media LLC
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
	const _CACHE_WORKER_PRIVS_PREFIX = 'ch_privs_worker_';
	const _CACHE_WORKER_ROLES_PREFIX = 'ch_roles_worker_';
	
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
	
	static function getRolesByWorker($worker_id, $nocache=false) {
		$cache = DevblocksPlatform::getCacheService();
		
		if($nocache || null === ($roles = $cache->load(self::_CACHE_WORKER_ROLES_PREFIX.$worker_id))) {
			$worker = DAO_Worker::get($worker_id);
			$memberships = $worker->getMemberships();
			$all_roles = DAO_WorkerRole::getAll();
			$roles = array();

			foreach($all_roles as $role_id => $role) {
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
					$roles[$role_id] = $role;
				}
			}

			$cache->save($roles, self::_CACHE_WORKER_ROLES_PREFIX.$worker_id);
		}
		
		return $roles;
	}
	
	static function getCumulativePrivsByWorker($worker_id, $nocache=false) {
		$cache = DevblocksPlatform::getCacheService();

		if($nocache || null === ($privs = $cache->load(self::_CACHE_WORKER_PRIVS_PREFIX.$worker_id))) {
			$worker = DAO_Worker::get($worker_id);
			$memberships = $worker->getMemberships();
			$roles = DAO_WorkerRole::getRolesByWorker($worker_id);
			$privs = array();
			
			foreach($roles as $role_id => $role) {
				switch($role->params['what']) {
					case 'all':
						$privs = array('*' => array());
						$cache->save($privs, self::_CACHE_WORKER_PRIVS_PREFIX.$worker_id);
						return;
						break;
						
					case 'itemized':
						$privs = array_merge($privs, DAO_WorkerRole::getRolePrivileges($role_id));
						break;
				}
			}
			
			$cache->save($privs, self::_CACHE_WORKER_PRIVS_PREFIX.$worker_id);
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
		self::clearWorkerCache();
		
		// Fire event
	    $eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'context.delete',
                array(
                	'context' => CerberusContexts::CONTEXT_ROLE,
                	'context_ids' => $ids
                )
            )
	    );
		
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
			$cache->remove(self::_CACHE_WORKER_PRIVS_PREFIX.$worker_id);
			$cache->remove(self::_CACHE_WORKER_ROLES_PREFIX.$worker_id);
		} else {
			$workers = DAO_Worker::getAll();
			foreach($workers as $worker_id => $worker) {
				$cache->remove(self::_CACHE_WORKER_PRIVS_PREFIX.$worker_id);
				$cache->remove(self::_CACHE_WORKER_ROLES_PREFIX.$worker_id);
			}
		}
	}
};

class Model_WorkerRole {
	public $id;
	public $name;
	public $params = array();
};

class Context_WorkerRole extends Extension_DevblocksContext {
	function authorize($context_id, Model_Worker $worker) {
		// Security
		try {
			if(empty($worker))
				throw new Exception();
			
			if($worker->is_superuser)
				return TRUE;
				
		} catch (Exception $e) {
			// Fail
		}
		
		return FALSE;
	}
	
	function getRandom() {
		//return DAO_WorkerRole::random();
	}
	
	function getMeta($context_id) {
		$worker_role = DAO_WorkerRole::get($context_id);
		$url_writer = DevblocksPlatform::getUrlService();
		
		$who = sprintf("%d-%s",
			$worker_role->id,
			DevblocksPlatform::strToPermalink($worker_role->name)
		); 
		
		return array(
			'id' => $worker_role->id,
			'name' => $worker_role->name,
			'permalink' => $url_writer->writeNoProxy('c=profiles&type=role&who='.$who, true),
		);
	}
	
	function getContext($role, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Role:';
			
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ROLE);
		
		// Polymorph
		if(is_numeric($role)) {
			$role = DAO_WorkerRole::get($role);
		} elseif($role instanceof Model_WorkerRole) {
			// It's what we want already.
		} else {
			$role = null;
		}
			
		// Token labels
		$token_labels = array(
			'name' => $prefix.$translate->_('common.name'),
			//'record_url' => $prefix.$translate->_('common.url.record'),			
		);
		
		if(is_array($fields))
		foreach($fields as $cf_id => $field) {
			$token_labels['custom_'.$cf_id] = $prefix.$field->name;
		}

		// Token values
		$token_values = array();
		
		// Worker token values
		if(null != $role) {
			$token_values['id'] = $role->id;
			$token_values['name'] = $role->name;
			
			// URL
// 			$url_writer = DevblocksPlatform::getUrlService();
// 			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=worker&id=%d-%s",$worker->id, DevblocksPlatform::strToPermalink($worker->getName())), true);
			
			$token_values['custom'] = array();
			
			$field_values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_ROLE, $role->id));
			if(is_array($field_values) && !empty($field_values)) {
				foreach($field_values as $cf_id => $cf_val) {
					if(!isset($fields[$cf_id]))
						continue;
					
					// The literal value
					if(null != $role)
						$token_values['custom'][$cf_id] = $cf_val;
					
					// Stringify
					if(is_array($cf_val))
						$cf_val = implode(', ', $cf_val);
						
					if(is_string($cf_val)) {
						if(null != $role)
							$token_values['custom_'.$cf_id] = $cf_val;
					}
				}
			}
		}
		
		return true;		
	}
	
	function getChooserView() {
		// View
		$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Roles';
		$view->view_columns = array(
			SearchFields_WorkerRole::NAME,
		);
		$view->addParams(array(
			//SearchFields_Worker::IS_DISABLED => new DevblocksSearchCriteria(SearchFields_Worker::IS_DISABLED,'=',0),
		), true);
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		C4_AbstractViewLoader::setView($view_id, $view);
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array()) {
		$view_id = str_replace('.','_',$this->id);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id; 
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Roles';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Worker::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_Worker::CONTEXT_LINK_ID,'=',$context_id),
			);
		}

		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
}