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

class DAO_Worker extends C4_ORMHelper {
	private function DAO_Worker() {}
	
	const CACHE_ALL = 'ch_workers';
	
	const ID = 'id';
	const FIRST_NAME = 'first_name';
	const LAST_NAME = 'last_name';
	const TITLE = 'title';
	const EMAIL = 'email';
	const PASSWORD = 'pass';
	const IS_SUPERUSER = 'is_superuser';
	const IS_DISABLED = 'is_disabled';
	const LAST_ACTIVITY = 'last_activity';
	const LAST_ACTIVITY_DATE = 'last_activity_date';
	const LAST_ACTIVITY_IP = 'last_activity_ip';
	
	static function create($fields) {
		if(empty($fields[DAO_Worker::EMAIL]) || empty($fields[DAO_Worker::PASSWORD]))
			return NULL;
			
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("INSERT INTO worker () ".
			"VALUES ()"
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		$id = $db->LastInsertId(); 

		self::update($id, $fields);
		
		self::clearCache();
		
		return $id;
	}

	static function clearCache() {
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
	}
	
	static function getAllActive() {
		return self::getAll(false, false);
	}
	
	static function getAllWithDisabled() {
		return self::getAll(false, true);
	}
	
	static function getAllOnline($idle_limit=600, $idle_kick_limit=0) {
		$session = DevblocksPlatform::getSessionService();

		$sessions = $session->getAll();
		$session_workers = array();
		$active_workers = array();
		$workers_to_sessions = array();
		
		// Track the active workers based on session data
		foreach($sessions as $session_id => $session_data) {
			$key = $session_data['session_key'];
			$data = $session->decodeSession($session_data['session_data']);
			@$visit = $data['db_visit']; /* @var $visit CerberusVisit */
			
			if(empty($visit))
				continue;
			
			if(!empty($visit) && null == ($worker = $visit->getWorker()))
				continue;

			// All workers from the sessions
			$session_workers[$worker->id] = $worker;

			// Map workers to sessions
			if(!isset($workers_to_sessions[$worker->id]))
				$workers_to_sessions[$worker->id] = array();
			$workers_to_sessions[$worker->id][$key] = $data;
		}
		
		// Sort workers by idle time (newest first)
		$sort_func = create_function('$a, $b', "return \$a->last_activity_date > \$b->last_activity_date;");
		uasort($session_workers, $sort_func);
		
		// Find active workers from sessions (idle but not logged out)
		foreach($session_workers as $worker_id => $worker) {
			if($worker->last_activity_date > time() - $idle_limit) {
				$active_workers[$worker->id] = $worker;
				
			} else {
				if($idle_kick_limit) {
					// Kill all sessions for this worker
					foreach($workers_to_sessions[$worker->id] as $session_key => $session_data) {
						$session->clear($session_key);
					}
					$idle_kick_limit--;
				}
			}
		}
		
		// Most recently active first
		$active_workers = array_reverse($active_workers, true);
		
		return $active_workers;
	}
	
	static function getAll($nocache=false, $with_disabled=true) {
	    $cache = DevblocksPlatform::getCacheService();
	    if($nocache || null === ($workers = $cache->load(self::CACHE_ALL))) {
    	    $workers = self::getWhere(null,array(DAO_Worker::FIRST_NAME,DAO_Worker::LAST_NAME),array(true,true));
    	    $cache->save($workers, self::CACHE_ALL);
	    }
	    
	    /*
	     * If the caller doesn't want disabled workers then remove them from the results,
	     * but don't bother caching two different versions (always cache all)
	     */
	    if(!$with_disabled) {
	    	foreach($workers as $worker_id => $worker) { /* @var $worker Model_Worker */
	    		if($worker->is_disabled)
	    			unset($workers[$worker_id]);
	    	}
	    }
	    
	    return $workers;
	}
	
	static function getWhere($where=null, $sortBy='first_name', $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		$sql = "SELECT id, first_name, last_name, email, pass, title, is_superuser, is_disabled, last_activity_date, last_activity, last_activity_ip ".
			"FROM worker ".
			$where_sql.
			$sort_sql.
			$limit_sql
			;
		$rs = $db->Execute($sql);
		
		return self::_createObjectsFromResultSet($rs);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param resource $rs
	 */
	static private function _createObjectsFromResultSet($rs=null) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_Worker();
			$object->id = intval($row['id']);
			$object->first_name = $row['first_name'];
			$object->last_name = $row['last_name'];
			$object->email = $row['email'];
			$object->pass = $row['pass'];
			$object->title = $row['title'];
			$object->is_superuser = intval($row['is_superuser']);
			$object->is_disabled = intval($row['is_disabled']);
			$object->last_activity_date = intval($row['last_activity_date']);
			
			if(!empty($row['last_activity']))
			    $object->last_activity = unserialize($row['last_activity']);
			
			if(!empty($row['last_activity_ip']))
				$object->last_activity_ip = long2ip($row['last_activity_ip']);
			    
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}	
	
	static function getList($ids=array()) {
		if(!is_array($ids)) $ids = array($ids);

		$workers = self::getWhere(
			sprintf("%s IN (%s)",
				DAO_Worker::ID,
				(!empty($ids) ? implode(',', $ids) : '0')
			),
			array(DAO_Worker::FIRST_NAME, DAO_Worker::LAST_NAME),
			array(true, true)
		);
		
		return $workers;
	}
	
	/**
	 * @return Model_Worker
	 */
	static function get($id) {
		if(empty($id)) return null;
		
		$workers = self::getAllWithDisabled();
		
		if(isset($workers[$id]))
			return $workers[$id];
			
		return null;
	}

	/**
	 * Enter description here...
	 *
	 * @param string $email
	 * @return integer $id
	 */
	static function getByEmail($email) {
		if(empty($email)) return null;
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT a.id FROM worker a WHERE a.email = %s",
			$db->qstr($email)
		);
		
		if(null != ($id = $db->GetOne($sql)))
			return $id;
		
		return null;		
	}
	
	static function update($ids, $fields, $flush_cache=true) {
		if(!is_array($ids)) $ids = array($ids);
		
		$db = DevblocksPlatform::getDatabaseService();
		$sets = array();
		
		if(!is_array($fields) || empty($fields) || empty($ids))
			return;
		
		foreach($fields as $k => $v) {
			$sets[] = sprintf("%s = %s",
				$k,
				$db->qstr($v)
			);
		}
			
		$sql = sprintf("UPDATE worker SET %s WHERE id IN (%s)",
			implode(', ', $sets),
			implode(',', $ids)
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		if($flush_cache) {
			self::clearCache();
		}
	}
	
	static function maint() {
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog();
		
		$sql = "DELETE QUICK view_rss FROM view_rss LEFT JOIN worker ON view_rss.worker_id = worker.id WHERE worker.id IS NULL";
		$db->Execute($sql);
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' view_rss records.');
		
		$sql = "DELETE QUICK worker_pref FROM worker_pref LEFT JOIN worker ON worker_pref.worker_id = worker.id WHERE worker.id IS NULL";
		$db->Execute($sql);
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' worker_pref records.');

		$sql = "DELETE QUICK worker_view_model FROM worker_view_model LEFT JOIN worker ON worker_view_model.worker_id = worker.id WHERE worker.id IS NULL";
		$db->Execute($sql);
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' worker_view_model records.');
		
		$sql = "DELETE QUICK worker_to_group FROM worker_to_group LEFT JOIN worker ON worker_to_group.worker_id = worker.id WHERE worker.id IS NULL";
		$db->Execute($sql);
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' worker_to_group records.');
		
		$sql = "DELETE QUICK workspace FROM workspace LEFT JOIN worker ON (workspace.owner_context_id = worker.id) WHERE workspace.owner_context = 'cerberusweb.contexts.worker' AND worker.id IS NULL";
		$db->Execute($sql);
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' workspace records.');
		
		// Fire event
	    $eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'context.maint',
                array(
                	'context' => CerberusContexts::CONTEXT_WORKER,
                	'context_table' => 'worker',
                	'context_key' => 'id',
                )
            )
	    );
	}
	
	static function delete($id) {
		if(empty($id)) return;
		
		// [TODO] Delete worker notes, comments, etc.
		
		/* This event fires before the delete takes place in the db,
		 * so we can denote what is actually changing against the db state
		 */
	    $eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'worker.delete',
                array(
                    'worker_ids' => array($id),
                )
            )
	    );
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE QUICK FROM worker WHERE id = %d", $id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		$sql = sprintf("DELETE QUICK FROM address_to_worker WHERE worker_id = %d", $id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		$sql = sprintf("DELETE QUICK FROM worker_to_group WHERE worker_id = %d", $id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 

		$sql = sprintf("DELETE QUICK FROM view_rss WHERE worker_id = %d", $id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 

		// Fire event
	    $eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'context.delete',
                array(
                	'context' => CerberusContexts::CONTEXT_WORKER,
                	'context_ids' => array($id)
                )
            )
	    );
		
		// Invalidate caches
		self::clearCache();
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(DAO_Group::CACHE_ROSTERS);
	}
	
	static function login($email, $password) {
		$db = DevblocksPlatform::getDatabaseService();

		// [TODO] Uniquely salt hashes
		$sql = sprintf("SELECT id ".
			"FROM worker ".
			"WHERE is_disabled = 0 ".
			"AND email = %s ".
			"AND pass = MD5(%s)",
				$db->qstr($email),
				$db->qstr($password)
		);
		$worker_id = $db->GetOne($sql); // or die(__CLASS__ . ':' . $db->ErrorMsg()); 

		if(!empty($worker_id)) {
			return self::get($worker_id);
		}
		
		return null;
	}
	
	/**
	 * @return Model_GroupMember[]
	 */
	static function getWorkerGroups($worker_id) {
		// Get the cache
		$rosters = DAO_Group::getRosters();

		$memberships = array();
		
		// Remove any groups our desired worker isn't in
		if(is_array($rosters))
		foreach($rosters as $group_id => $members) {
			if(isset($members[$worker_id])) {
				$memberships[$group_id] = $members[$worker_id]; 
			}
		}
		
		return $memberships;
	}
	
	/**
	 * Store the workers last activity (provided by the page extension).
	 * 
	 * @param integer $worker_id
	 * @param Model_Activity $activity
	 */
	static function logActivity(Model_Activity $activity, $ignore_wait=false) {
		if(null === ($worker = CerberusApplication::getActiveWorker()))
			return;
			
		$ip = $_SERVER['REMOTE_ADDR'];
		if('::1' == $ip)
			$ip = '127.0.0.1';

		// Update activity once per 30 seconds
		if($ignore_wait || $worker->last_activity_date < (time()-30)) {
		    DAO_Worker::update($worker->id,array(
		        DAO_Worker::LAST_ACTIVITY_DATE => time(),
		        DAO_Worker::LAST_ACTIVITY => serialize($activity),
		        DAO_Worker::LAST_ACTIVITY_IP => sprintf("%u",ip2long($ip)),
		    ));
		}
	}

	public static function random() {
		return self::_getRandom('worker');
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Worker::getFields();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]) || !in_array($sortBy,$columns))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"w.id as %s, ".
			"w.first_name as %s, ".
			"w.last_name as %s, ".
			"w.title as %s, ".
			"w.email as %s, ".
			"w.is_superuser as %s, ".
			"w.last_activity_date as %s, ".
			"w.is_disabled as %s ",
			    SearchFields_Worker::ID,
			    SearchFields_Worker::FIRST_NAME,
			    SearchFields_Worker::LAST_NAME,
			    SearchFields_Worker::TITLE,
			    SearchFields_Worker::EMAIL,
			    SearchFields_Worker::IS_SUPERUSER,
			    SearchFields_Worker::LAST_ACTIVITY_DATE,
			    SearchFields_Worker::IS_DISABLED
			);
			
		$join_sql = "FROM worker w ".

		// Dynamic joins
		(isset($tables['context_link']) ? "INNER JOIN context_link ON (context_link.to_context = 'cerberusweb.contexts.worker' AND context_link.to_context_id = w.id) " : " ")
		;
		
		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			'w.id',
			$select_sql,
			$join_sql
		);
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
		
		// Virtuals
		foreach($params as $param) {
			if(!is_a($param, 'DevblocksSearchCriteria'))
				continue;
			
			$param_key = $param->field;
			settype($param_key, 'string');
			switch($param_key) {
				case SearchFields_Worker::VIRTUAL_GROUPS:
					$has_multiple_values = true;
					if(empty($param->value)) { // empty
						$join_sql .= "LEFT JOIN worker_to_group ON (worker_to_group.worker_id = w.id) ";
						$where_sql .= "AND worker_to_group.worker_id IS NULL ";
					} else {
						$join_sql .= sprintf("INNER JOIN worker_to_group ON (worker_to_group.worker_id = w.id AND worker_to_group.group_id IN (%s)) ",
							implode(',', $param->value)
						);
					}
					break;
			}
		}
		
		$result = array(
			'primary_table' => 'w',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => $has_multiple_values,
			'sort' => $sort_sql,
		);
		
		return $result;
	}	
	
	static function autocomplete($term) {
		$db = DevblocksPlatform::getDatabaseService();
		$workers = DAO_Worker::getAll();
		$objects = array();
		
		$results = $db->GetArray(sprintf("SELECT id ".
			"FROM worker ".
			"WHERE is_disabled = 0 ".
			"AND (".
			"first_name LIKE %s ".
			"OR last_name LIKE %s ".
			"%s".
			")",
			$db->qstr($term.'%'),
			$db->qstr($term.'%'),
			(false != strpos($term,' ') 
				? sprintf("OR concat(first_name,' ',last_name) LIKE %s ", $db->qstr($term.'%')) 
				: '')
		));
		
		if(is_array($results))
		foreach($results as $row) {
			$worker_id = $row['id'];
			
			if(!isset($workers[$worker_id]))
				continue;
				
			$objects[$worker_id] = $workers[$worker_id];
		}
		
		return $objects;
	}
	
    /**
     * Enter description here...
     *
     * @param DevblocksSearchCriteria[] $params
     * @param integer $limit
     * @param integer $page
     * @param string $sortBy
     * @param boolean $sortAsc
     * @param boolean $withCounts
     * @return array
     */
    static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();

		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$has_multiple_values = $query_parts['has_multiple_values'];
		$sort_sql = $query_parts['sort'];
		
		$sql = 
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY w.id ' : '').
			$sort_sql;
			
		if($limit > 0) {
    		$rs = $db->SelectLimit($sql,$limit,$page*$limit) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		} else {
		    $rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
            $total = mysql_num_rows($rs);
		}
		
		$results = array();
		$total = -1;
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$object_id = intval($row[SearchFields_Worker::ID]);
			$results[$object_id] = $result;
		}
		
		// [JAS]: Count all
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT w.id) " : "SELECT COUNT(w.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
    }			
    	
};

/**
 * ...
 * 
 */
class SearchFields_Worker implements IDevblocksSearchFields {
	// Worker
	const ID = 'w_id';
	const FIRST_NAME = 'w_first_name';
	const LAST_NAME = 'w_last_name';
	const TITLE = 'w_title';
	const EMAIL = 'w_email';
	const IS_SUPERUSER = 'w_is_superuser';
	const LAST_ACTIVITY = 'w_last_activity';
	const LAST_ACTIVITY_DATE = 'w_last_activity_date';
	const IS_DISABLED = 'w_is_disabled';
	
	const VIRTUAL_GROUPS = '*_groups';
	
	const CONTEXT_LINK = 'cl_context_from';
	const CONTEXT_LINK_ID = 'cl_context_from_id';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'w', 'id', $translate->_('common.id')),
			self::FIRST_NAME => new DevblocksSearchField(self::FIRST_NAME, 'w', 'first_name', $translate->_('worker.first_name')),
			self::LAST_NAME => new DevblocksSearchField(self::LAST_NAME, 'w', 'last_name', $translate->_('worker.last_name')),
			self::TITLE => new DevblocksSearchField(self::TITLE, 'w', 'title', $translate->_('worker.title')),
			self::EMAIL => new DevblocksSearchField(self::EMAIL, 'w', 'email', ucwords($translate->_('common.email'))),
			self::IS_SUPERUSER => new DevblocksSearchField(self::IS_SUPERUSER, 'w', 'is_superuser', $translate->_('worker.is_superuser')),
			self::LAST_ACTIVITY => new DevblocksSearchField(self::LAST_ACTIVITY, 'w', 'last_activity', $translate->_('worker.last_activity')),
			self::LAST_ACTIVITY_DATE => new DevblocksSearchField(self::LAST_ACTIVITY_DATE, 'w', 'last_activity_date', $translate->_('worker.last_activity_date')),
			self::IS_DISABLED => new DevblocksSearchField(self::IS_DISABLED, 'w', 'is_disabled', ucwords($translate->_('common.disabled'))),
			
			self::VIRTUAL_GROUPS => new DevblocksSearchField(self::VIRTUAL_GROUPS, '*', 'groups', $translate->_('common.groups')),
			
			self::CONTEXT_LINK => new DevblocksSearchField(self::CONTEXT_LINK, 'context_link', 'from_context', null),
			self::CONTEXT_LINK_ID => new DevblocksSearchField(self::CONTEXT_LINK_ID, 'context_link', 'from_context_id', null),
		);
		
		// Custom Fields
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_WORKER);

		if(is_array($fields))
		foreach($fields as $field_id => $field) {
			$key = 'cf_'.$field_id;
			$columns[$key] = new DevblocksSearchField($key,$key,'field_value',$field->name);
		}
		
		// Sort by label (translation-conscious)
		uasort($columns, create_function('$a, $b', "return strcasecmp(\$a->db_label,\$b->db_label);\n"));

		return $columns;		
	}
};

class Model_Worker {
	public $id;
	public $first_name;
	public $last_name;
	public $email;
	public $pass;
	public $title;
	public $is_superuser=0;
	public $is_disabled=0;
	public $last_activity;
	public $last_activity_date;
	public $last_activity_ip;

	/**
	 * @return Model_GroupMember[]
	 */
	function getMemberships() {
		return DAO_Worker::getWorkerGroups($this->id); 
	}

	function getRoles() {
		return DAO_WorkerRole::getRolesByWorker($this->id);
	}
	
	/**
	 * @return Model_Address
	 */
	function getAddress() {
		return DAO_Address::getByEmail($this->email);
	}
	
	function hasPriv($priv_id) {
		// We don't need to do much work if we're a superuser
		if($this->is_superuser)
			return true;

		// Check the aggregated worker privs from roles
		$privs = DAO_WorkerRole::getCumulativePrivsByWorker($this->id);
		
		// If they have the 'everything' privilege, or no roles, permit non-config ACL
		if(isset($privs['*']))
			return ("core.config"==substr($priv_id,0,11)) ? false : true;
		
		if(!empty($priv_id) && isset($privs[$priv_id]))
			return true;
		
		return false;
	}
	
	function isGroupManager($group_id) {
		@$memberships = $this->getMemberships();
		$groups = DAO_Group::getAll();
		if(
			empty($group_id) // null
			|| !isset($groups[$group_id]) // doesn't exist
			|| !isset($memberships[$group_id])  // not a member
			|| (!$memberships[$group_id]->is_manager && !$this->is_superuser) // not a manager or superuser
		){
			return false;
		}
		return true;
	}

	function isGroupMember($group_id) {
		@$memberships = $this->getMemberships();
		$groups = DAO_Group::getAll();
		if(
			empty($group_id) // null
			|| !isset($groups[$group_id]) // not a group
			|| !isset($memberships[$group_id]) // not a member
		) {
			return false;
		}
		return true;
	}
	
	function getName($reverse=false) {
		if(!$reverse) {
			$name = sprintf("%s%s%s",
				$this->first_name,
				(!empty($this->first_name) && !empty($this->last_name)) ? " " : "",
				$this->last_name
			);
		} else {
			$name = sprintf("%s%s%s",
				$this->last_name,
				(!empty($this->first_name) && !empty($this->last_name)) ? ", " : "",
				$this->first_name
			);
		}
		
		return $name;
	}
};

class WorkerPrefs {
	static function setDontNotifyOnActivities($worker_id, $array) {
		if(empty($worker_id) || !is_array($array))
			return;
		
		DAO_WorkerPref::set($worker_id, 'dont_notify_on_activities_json', json_encode($array));
	}
	
	static function getDontNotifyOnActivities($worker_id) {
		$dont_notify_on_activities = DAO_WorkerPref::get($worker_id, 'dont_notify_on_activities_json', null);
		if(empty($dont_notify_on_activities) || false == ($dont_notify_on_activities = @json_decode($dont_notify_on_activities, true))) {
			$dont_notify_on_activities = array();
		}
		return $dont_notify_on_activities;
	}
};

class View_Worker extends C4_AbstractView implements IAbstractView_Subtotals {
	const DEFAULT_ID = 'workers';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Workers';
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Worker::FIRST_NAME;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_Worker::FIRST_NAME,
			SearchFields_Worker::LAST_NAME,
			SearchFields_Worker::TITLE,
			SearchFields_Worker::EMAIL,
			SearchFields_Worker::LAST_ACTIVITY_DATE,
			SearchFields_Worker::IS_SUPERUSER,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_Worker::LAST_ACTIVITY,
			SearchFields_Worker::CONTEXT_LINK,
			SearchFields_Worker::CONTEXT_LINK_ID,
			SearchFields_Worker::VIRTUAL_GROUPS,
		));
		
		$this->addParamsHidden(array(
			SearchFields_Worker::ID,
			SearchFields_Worker::LAST_ACTIVITY,
			SearchFields_Worker::CONTEXT_LINK,
			SearchFields_Worker::CONTEXT_LINK_ID,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		return DAO_Worker::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable();
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// DAO
				case SearchFields_Worker::FIRST_NAME:
				case SearchFields_Worker::IS_DISABLED:
				case SearchFields_Worker::IS_SUPERUSER:
				case SearchFields_Worker::LAST_NAME:
				case SearchFields_Worker::TITLE:
					$pass = true;
					break;
					
				// Valid custom fields
				default:
					if('cf_' == substr($field_key,0,3))
						$pass = $this->_canSubtotalCustomField($field_key);
					break;
			}
			
			if($pass)
				$fields[$field_key] = $field_model;
		}
		
		return $fields;
	}
	
	function getSubtotalCounts($column) {
		$counts = array();
		$fields = $this->getFields();

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_Worker::FIRST_NAME:
			case SearchFields_Worker::LAST_NAME:
			case SearchFields_Worker::TITLE:
				$counts = $this->_getSubtotalCountForStringColumn('DAO_Worker', $column);
				break;

			case SearchFields_Worker::IS_DISABLED:
			case SearchFields_Worker::IS_SUPERUSER:
				$counts = $this->_getSubtotalCountForBooleanColumn('DAO_Worker', $column);
				break;
			
			default:
				// Custom fields
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn('DAO_Worker', $column, 'w.id');
				}
				
				break;
		}
		
		return $counts;
	}	
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_WORKER);
		$tpl->assign('custom_fields', $custom_fields);

		switch($this->renderTemplate) {
			case 'contextlinks_chooser':
			default:
				$tpl->assign('view_template', 'devblocks:cerberusweb.core::workers/view.tpl');
				$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_Worker::VIRTUAL_GROUPS:
				if(empty($param->value)) {
					echo "<b>Not</b> a member of any groups";
					
				} elseif(is_array($param->value)) {
					$groups = DAO_Group::getAll();
					$strings = array();
					
					foreach($param->value as $group_id) {
						if(isset($groups[$group_id]))
							$strings[] = '<b>'.$groups[$group_id]->name.'</b>';
					}
					
					echo sprintf("Group member of %s", implode(' or ', $strings));
				}
				break;
		}
	}	
	
	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_Worker::EMAIL:
			case SearchFields_Worker::FIRST_NAME:
			case SearchFields_Worker::LAST_NAME:
			case SearchFields_Worker::TITLE:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case SearchFields_Worker::IS_DISABLED:
			case SearchFields_Worker::IS_SUPERUSER:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
				
			case SearchFields_Worker::LAST_ACTIVITY_DATE:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
				
			case SearchFields_Worker::VIRTUAL_GROUPS:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_group.tpl');
				break;
				
			default:
				// Custom Fields
				if('cf_' == substr($field,0,3)) {
					$this->_renderCriteriaCustomField($tpl, substr($field,3));
				} else {
					echo ' ';
				}
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_Worker::IS_DISABLED:
			case SearchFields_Worker::IS_SUPERUSER:
				$this->_renderCriteriaParamBoolean($param);
				break;
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_Worker::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Worker::EMAIL:
			case SearchFields_Worker::FIRST_NAME:
			case SearchFields_Worker::LAST_NAME:
			case SearchFields_Worker::TITLE:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_Worker::LAST_ACTIVITY_DATE:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_Worker::IS_DISABLED:
			case SearchFields_Worker::IS_SUPERUSER:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_Worker::VIRTUAL_GROUPS:
				@$group_ids = DevblocksPlatform::importGPC($_REQUEST['group_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,'in', $group_ids);
				break;
				
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria);
			$this->renderPage = 0;
		}
	}

	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(600); // 10m
		
		$change_fields = array();
		$custom_fields = array();

		if(empty($do))
			return;

		// Make sure we have checked items if we want a checked list
		if(0 == strcasecmp($filter,"checks") && empty($ids))
			return;
			
		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				case 'is_disabled':
					$change_fields[DAO_Worker::IS_DISABLED] = intval($v);
					break;
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
					break;

			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_Worker::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_Worker::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			DAO_Worker::update($batch_ids, $change_fields);
			
			// Custom Fields
			self::_doBulkSetCustomFields(CerberusContexts::CONTEXT_WORKER, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}
};

class DAO_WorkerPref extends DevblocksORMHelper {
    const CACHE_PREFIX = 'ch_workerpref_';
    
    static function delete($worker_id, $key) {
    	$db = DevblocksPlatform::getDatabaseService();
    	$db->Execute(sprintf("DELETE FROM worker_pref WHERE worker_id = %d AND setting = %s",
    		$worker_id,
    		$db->qstr($key)
    	));
    	
		// Invalidate cache
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_PREFIX.$worker_id);
    }
    
	static function set($worker_id, $key, $value) {
		// Persist long-term
		$db = DevblocksPlatform::getDatabaseService();
		
		$db->Execute(sprintf("REPLACE INTO worker_pref (worker_id, setting, value) ".
			"VALUES (%d, %s, %s)",
			$worker_id,
			$db->qstr($key),
			$db->qstr($value)
		));
		
		// Invalidate cache
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_PREFIX.$worker_id);
	}
	
	static function get($worker_id, $key, $default=null) {
		$value = null;
		
		if(null !== ($worker_prefs = self::getByWorker($worker_id))) {
			if(isset($worker_prefs[$key])) {
				$value = $worker_prefs[$key];
			}
		}
		
		if(null === $value && !is_null($default)) {
		    return $default;
		}
		
		return $value;
	}

	static function getByWorker($worker_id) {
		$cache = DevblocksPlatform::getCacheService();
		
		if(null === ($objects = $cache->load(self::CACHE_PREFIX.$worker_id))) {
			$db = DevblocksPlatform::getDatabaseService();
			$sql = sprintf("SELECT setting, value FROM worker_pref WHERE worker_id = %d", $worker_id);
			$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
			
			$objects = array();
			
			while($row = mysql_fetch_assoc($rs)) {
			    $objects[$row['setting']] = $row['value'];
			}
			
			mysql_free_result($rs);
			
			$cache->save($objects, self::CACHE_PREFIX.$worker_id);
		}
		
		return $objects;
	}
};

class Context_Worker extends Extension_DevblocksContext {
	function authorize($context_id, Model_Worker $worker) {
		// Security
		try {
			if(empty($worker))
				throw new Exception();
			
			if($worker->is_superuser)
				return TRUE;
				
			if($context_id == $worker->id)
				return TRUE;			
				
		} catch (Exception $e) {
			// Fail
		}
		
		return FALSE;
	}
	
	function getRandom() {
		return DAO_Worker::random();
	}
	
	function getMeta($context_id) {
		$worker = DAO_Worker::get($context_id);
		$url_writer = DevblocksPlatform::getUrlService();
		
		$worker_name = $worker->getName();
		
		$who = sprintf("%d-%s",
			$worker->id,
			DevblocksPlatform::strToPermalink($worker_name)
		); 
		
		return array(
			'id' => $worker->id,
			'name' => $worker_name,
			'permalink' => $url_writer->writeNoProxy('c=profiles&type=worker&who='.$who, true),
		);
	}
	
	function getContext($worker, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Worker:';
			
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_WORKER);
		
		// Polymorph
		if(is_numeric($worker)) {
			$worker = DAO_Worker::get($worker);
		} elseif($worker instanceof Model_Worker) {
			// It's what we want already.
		} else {
			$worker = null;
		}
			
		// Token labels
		$token_labels = array(
			'first_name' => $prefix.$translate->_('worker.first_name'),
			'full_name' => $prefix.$translate->_('worker.full_name'),
			'last_name' => $prefix.$translate->_('worker.last_name'),
			'title' => $prefix.$translate->_('worker.title'),
			'record_url' => $prefix.$translate->_('common.url.record'),			
		);
		
		if(is_array($fields))
		foreach($fields as $cf_id => $field) {
			$token_labels['custom_'.$cf_id] = $prefix.$field->name;
		}

		// Token values
		$token_values = array();
		
		// Worker token values
		if(null != $worker) {
			$token_values['id'] = $worker->id;
			$token_values['full_name'] = $worker->getName();
			if(!empty($worker->first_name))
				$token_values['first_name'] = $worker->first_name;
			if(!empty($worker->last_name))
				$token_values['last_name'] = $worker->last_name;
			if(!empty($worker->title))
				$token_values['title'] = $worker->title;
			
			// URL
			$url_writer = DevblocksPlatform::getUrlService();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=worker&id=%d-%s",$worker->id, DevblocksPlatform::strToPermalink($worker->getName())), true);
			
			$token_values['custom'] = array();
			
			$field_values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_WORKER, $worker->id));
			if(is_array($field_values) && !empty($field_values)) {
				foreach($field_values as $cf_id => $cf_val) {
					if(!isset($fields[$cf_id]))
						continue;
					
					// The literal value
					if(null != $worker)
						$token_values['custom'][$cf_id] = $cf_val;
					
					// Stringify
					if(is_array($cf_val))
						$cf_val = implode(', ', $cf_val);
						
					if(is_string($cf_val)) {
						if(null != $worker)
							$token_values['custom_'.$cf_id] = $cf_val;
					}
				}
			}
		}
		
		// Worker email
		@$worker_email = !is_null($worker) ? $worker->email : null;
		$merge_token_labels = array();
		$merge_token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_ADDRESS, $worker_email, $merge_token_labels, $merge_token_values, null, true);

		CerberusContexts::merge(
			'address_',
			$prefix,
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);		
		
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
		$view->name = 'Workers';
		$view->view_columns = array(
			SearchFields_Worker::FIRST_NAME,
			SearchFields_Worker::LAST_NAME,
			SearchFields_Worker::TITLE,
		);
		$view->addParams(array(
			SearchFields_Worker::IS_DISABLED => new DevblocksSearchCriteria(SearchFields_Worker::IS_DISABLED,'=',0),
		), true);
		$view->renderLimit = 10;
		$view->renderFilters = true;
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
		$view->name = 'Workers';
		
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