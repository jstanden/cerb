<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2010, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerberus Helpdesk Team
 * 
 * Sure, it would be so easy to just cheat and edit this file to use the 
 * software without paying for it.  But we trust you anyway.  In fact, we're 
 * writing this software for you! 
 * 
 * Quality software backed by a dedicated team takes money to develop.  We 
 * don't want to be out of the office bagging groceries when you call up 
 * needing a helping hand.  We'd rather spend our free time coding your 
 * feature requests than mowing the neighbors' lawns for rent money. 
 * 
 * We've never believed in hiding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your inbox that you probably 
 * haven't had since spammers found you in a game of 'E-mail Battleship'. 
 * Miss. Miss. You sunk my inbox!
 * 
 * A legitimate license entitles you to support from the developers,  
 * and the warm fuzzy feeling of feeding a couple of obsessed developers 
 * who want to help you get more done.
 *
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Joe Geck, Scott Luther,
 * 		and Jerry Kanoholani. 
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */

class C4_ORMHelper extends DevblocksORMHelper {
	static public function qstr($str) {
		$db = DevblocksPlatform::getDatabaseService();
		return $db->qstr($str);	
	}
	
	static protected function _appendSelectJoinSqlForCustomFieldTables($tables, $params, $key, $select_sql, $join_sql) {
		$custom_fields = DAO_CustomField::getAll();
		$field_ids = array();
		
		$return_multiple_values = false; // can our CF return more than one hit? (GROUP BY)
		
		if(is_array($tables))
		foreach($tables as $tbl_name => $null) {
			// Filter and sanitize
			if(substr($tbl_name,0,3) != "cf_" // not a custom field 
				|| 0 == ($field_id = intval(substr($tbl_name,3)))) // not a field_id
				continue;

			// Make sure the field exists for this source
			if(!isset($custom_fields[$field_id]))
				continue; 
			
			$field_table = sprintf("cf_%d", $field_id);
			$value_table = '';
			
			// Join value by field data type
			switch($custom_fields[$field_id]->type) {
				case 'T': // multi-line CLOB
					$value_table = 'custom_field_clobvalue';
					break;
				case 'C': // checkbox
				case 'E': // date
				case 'N': // number
				case 'W': // worker
					$value_table = 'custom_field_numbervalue';
					break;
				default:
				case 'S': // single-line
				case 'D': // dropdown
				case 'U': // URL
					$value_table = 'custom_field_stringvalue';
					break;
			}

			$has_multiple_values = false;
			switch($custom_fields[$field_id]->type) {
				case Model_CustomField::TYPE_MULTI_PICKLIST:
				case Model_CustomField::TYPE_MULTI_CHECKBOX:
					$has_multiple_values = true;
					break;
			}

			// If we have multiple values but we don't need to WHERE the JOIN, be efficient and don't GROUP BY
			if(!isset($params['cf_'.$field_id])) {
				$select_sql .= sprintf(",(SELECT field_value FROM %s WHERE %s=source_id AND field_id=%d LIMIT 0,1) AS %s ",
					$value_table,
					$key,
					$field_id,
					$field_table
				);
				
			} else {
				$select_sql .= sprintf(", %s.field_value as %s ",
					$field_table,
					$field_table
				);
				
				$join_sql .= sprintf("LEFT JOIN %s %s ON (%s=%s.source_id AND %s.field_id=%d) ",
					$value_table,
					$field_table,
					$key,
					$field_table,
					$field_table,
					$field_id
				);
				
				// If we do need to WHERE this JOIN, make sure we GROUP BY
				if($has_multiple_values)
					$return_multiple_values = true;
			}
		}
		
		return array($select_sql, $join_sql, $return_multiple_values);
	}
};

class DAO_WorkerViewModel {
	// [TODO] Add an 'ephemeral' bit to clear record on login
	
	/**
	 * 
	 * @param integer $worker_id
	 * @param string $view_id
	 * @return C4_AbstractViewModel or false
	 */
	static public function getView($worker_id, $view_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$fields = array(
			'worker_id',
			'view_id',
			'is_ephemeral',
			'class_name',
			'title',
			'columns_json',
			'columns_hidden_json',
			'params_editable_json',
			'params_required_json',
			'params_default_json',
			'params_hidden_json',
			'render_page',
			'render_total',
			'render_limit',
			'render_sort_by',
			'render_sort_asc',
			'render_template',
		);
		
		$row = $db->GetRow(sprintf("SELECT %s FROM worker_view_model WHERE worker_id = %d AND view_id = %s",
			implode(',', $fields),
			$worker_id,
			$db->qstr($view_id)
		));
		
		if(!empty($row)) {
			$model = new C4_AbstractViewModel();
			$model->id = $row['view_id'];
			$model->is_ephemeral = $row['is_ephemeral'];
			$model->class_name = $row['class_name'];
			$model->name = $row['title'];
			$model->renderPage = $row['render_page'];
			$model->renderTotal = $row['render_total'];
			$model->renderLimit = $row['render_limit'];
			$model->renderSortBy = $row['render_sort_by'];
			$model->renderSortAsc = $row['render_sort_asc'];
			$model->renderTemplate = $row['render_template'];
			
			// JSON blocks
			$model->view_columns = json_decode($row['columns_json'], true);
			$model->columnsHidden = json_decode($row['columns_hidden_json'], true);
			$model->paramsEditable = self::decodeParamsJson($row['params_editable_json']);
			$model->paramsRequired = self::decodeParamsJson($row['params_required_json']);
			$model->paramsDefault = self::decodeParamsJson($row['params_default_json']);
			$model->paramsHidden = json_decode($row['params_hidden_json'], true);
			
			return $model;
		}
			
		return false; 
	}

	static public function decodeParamsJson($json) {
		$params = array();
		
		if(empty($json) || false === ($params_data = json_decode($json, true)))
			return array();
		
		if(is_array($params_data))
		foreach($params_data as $key => $data) {
			if(is_numeric(key($data))) {
				$params[$key] = self::_recurseParam($data);
			} else {
				$params[$key] = new DevblocksSearchCriteria($data['field'], $data['operator'], $data['value']); 
			}
		}
		
		return $params;
	}
	
	static private function _recurseParam($group) {
		$params = array();
		
		foreach($group as $key => $data) {
			if(is_array($data)) {
				if(is_numeric(key($data))) {
					$params[$key] = array(array_shift($data)) + self::_recurseParam($data);
				} else {
					$param = new DevblocksSearchCriteria($data['field'], $data['operator'], $data['value']);
					$params[$key] = $param;
				}
			} elseif(is_string($data)) {
				$params[$key] = $data;
			}
		}
		
		return $params;
	}
	
	static public function setView($worker_id, $view_id, C4_AbstractViewModel $model) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$fields = array(
			'worker_id' => $worker_id,
			'view_id' => $db->qstr($view_id),
			'is_ephemeral' => !empty($model->is_ephemeral) ? 1 : 0,
			'class_name' => $db->qstr($model->class_name),
			'title' => $db->qstr($model->name),
			'columns_json' => $db->qstr(json_encode($model->view_columns)),
			'columns_hidden_json' => $db->qstr(json_encode($model->columnsHidden)),
			'params_editable_json' => $db->qstr(json_encode($model->paramsEditable)),
			'params_required_json' => $db->qstr(json_encode($model->paramsRequired)),
			'params_default_json' => $db->qstr(json_encode($model->paramsDefault)),
			'params_hidden_json' => $db->qstr(json_encode($model->paramsHidden)),
			'render_page' => abs(intval($model->renderPage)),
			'render_total' => !empty($model->renderTotal) ? 1 : 0,
			'render_limit' => intval($model->renderLimit),
			'render_sort_by' => $db->qstr($model->renderSortBy),
			'render_sort_asc' => !empty($model->renderSortAsc) ? 1 : 0,
			'render_template' => $db->qstr($model->renderTemplate),
		);
		
		$db->Execute(sprintf("REPLACE INTO worker_view_model (%s)".
			"VALUES (%s)",
			implode(',', array_keys($fields)),
			implode(',', $fields)
		));
	}
	
	static public function deleteView($worker_id, $view_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$db->Execute(sprintf("DELETE FROM worker_view_model WHERE worker_id = %d AND view_id = %s",
			$worker_id,
			$db->qstr($view_id)
		));
	}
	
	/**
	 * Prepares for a new session by removing ephemeral views and 
	 * resetting all page cursors to the first page of the list.
	 * 
	 * @param integer$worker_id
	 */
	static public function flush($worker_id) {
		$db = DevblocksPlatform::getDatabaseService();
		$db->Execute(sprintf("DELETE FROM worker_view_model WHERE worker_id = %d and is_ephemeral = 1",
			$worker_id
		));
		$db->Execute(sprintf("UPDATE worker_view_model SET render_page = 0 WHERE worker_id = %d",
			$worker_id
		));
	}
};

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

class DAO_ViewRss extends DevblocksORMHelper {
	const ID = 'id';
	const TITLE = 'title';
	const HASH = 'hash';
	const WORKER_ID = 'worker_id';
	const CREATED = 'created';
	const SOURCE_EXTENSION = 'source_extension';
	const PARAMS = 'params';
	
	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("INSERT INTO view_rss () ".
			"VALUES ()"
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		$id = $db->LastInsertId(); 
		
		self::update($id, $fields);
		
		return $id;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param array $ids
	 * @return Model_ViewRss[]
	 */
	static function getList($ids) {
		if(!is_array($ids)) $ids = array($ids);
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id,hash,title,worker_id,created,source_extension,params ".
			"FROM view_rss ".
			(!empty($ids) ? sprintf("WHERE id IN (%s)",implode(',',$ids)) : " ").
		"";
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		return self::_getObjectsFromResults($rs);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $hash
	 * @return Model_ViewRss
	 */
	static function getByHash($hash) {
		if(empty($hash)) return array();
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT id,hash,title,worker_id,created,source_extension,params ".
			"FROM view_rss ".
			"WHERE hash = %s",
				$db->qstr($hash)
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		$objects = self::_getObjectsFromResults($rs);
		
		if(empty($objects))
			return null;
		
		return array_shift($objects);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $worker_id
	 * @return Model_ViewRss[]
	 */
	static function getByWorker($worker_id) {
		if(empty($worker_id)) return array();
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT id,hash,title,worker_id,created,source_extension,params ".
			"FROM view_rss ".
			"WHERE worker_id = %d",
				$worker_id
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		$objects = self::_getObjectsFromResults($rs);
		
		return $objects;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param resource $rs
	 * @return Model_ViewRss[]
	 */
	private static function _getObjectsFromResults($rs) { 
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_ViewRss();
			$object->id = intval($row['id']);
			$object->title = $row['title'];
			$object->hash = $row['hash'];
			$object->worker_id = intval($row['worker_id']);
			$object->created = intval($row['created']);
			$object->source_extension = $row['source_extension'];
			
			$params = $row['params'];
			
			if(!empty($params))
				@$object->params = unserialize($params);
			
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}

	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return Model_ViewRss
	 */
	static function getId($id) {
		if(empty($id)) return null;

		$feeds = self::getList($id);
		if(isset($feeds[$id]))
			return $feeds[$id];
		
		return null;
	}
	
	static function update($ids, $fields) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		parent::_update($ids, 'view_rss', $fields);
	}
	
	static function delete($id) {
		if(empty($id))
			return;
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE QUICK FROM view_rss WHERE id = %d",
			$id
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
	}
};

class DAO_Mail {
	// Pop3 Accounts
	
	static function createPop3Account($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("INSERT INTO pop3_account () ".
			"VALUES ()"
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		$id = $db->LastInsertId(); 
		
		self::updatePop3Account($id, $fields);
		
		return $id;
	}
	
	static function getPop3Accounts($ids=array()) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		$pop3accounts = array();
		
		$sql = "SELECT id, enabled, nickname, protocol, host, username, password, port ".
			"FROM pop3_account ".
			((!empty($ids) ? sprintf("WHERE id IN (%s)", implode(',', $ids)) : " ").
			"ORDER BY nickname "
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		while($row = mysql_fetch_assoc($rs)) {
			$pop3 = new Model_Pop3Account();
			$pop3->id = intval($row['id']);
			$pop3->enabled = intval($row['enabled']);
			$pop3->nickname = $row['nickname'];
			$pop3->protocol = $row['protocol'];
			$pop3->host = $row['host'];
			$pop3->username = $row['username'];
			$pop3->password = $row['password'];
			$pop3->port = intval($row['port']);
			$pop3accounts[$pop3->id] = $pop3;
		}
		
		mysql_free_result($rs);
		
		return $pop3accounts;		
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return Model_Pop3Account
	 */
	static function getPop3Account($id) {
		$accounts = DAO_Mail::getPop3Accounts(array($id));
		
		if(isset($accounts[$id]))
			return $accounts[$id];
			
		return null;
	}
	
	static function updatePop3Account($id, $fields) {
		$db = DevblocksPlatform::getDatabaseService();
		$sets = array();
		
		if(!is_array($fields) || empty($fields) || empty($id))
			return;
		
		foreach($fields as $k => $v) {
			$sets[] = sprintf("%s = %s",
				$k,
				$db->qstr($v)
			);
		}
			
		$sql = sprintf("UPDATE pop3_account SET %s WHERE id = %d",
			implode(', ', $sets),
			$id
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
	}
	
	static function deletePop3Account($id) {
		if(empty($id))
			return;
			
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE QUICK FROM pop3_account WHERE id = %d",
			$id			
		);
		
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
	}
};

class DAO_MailToGroupRule extends DevblocksORMHelper {
	const ID = 'id';
	const POS = 'pos';
	const CREATED = 'created';
	const NAME = 'name';
	const CRITERIA_SER = 'criteria_ser';
	const ACTIONS_SER = 'actions_ser';
	const IS_STICKY = 'is_sticky';
	const STICKY_ORDER = 'sticky_order';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("INSERT INTO mail_to_group_rule (created) ".
			"VALUES (%d)",
			time()
		);
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'mail_to_group_rule', $fields);
	}
	
	/**
	 * @param string $where
	 * @return Model_MailToGroupRule[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, pos, created, name, criteria_ser, actions_ser, is_sticky, sticky_order ".
			"FROM mail_to_group_rule ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY is_sticky DESC, sticky_order ASC, pos DESC";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_MailToGroupRule	 */
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
	 * @return Model_MailToGroupRule[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_MailToGroupRule();
			$object->id = $row['id'];
			$object->pos = $row['pos'];
			$object->created = $row['created'];
			$object->name = $row['name'];
			$criteria_ser = $row['criteria_ser'];
			$actions_ser = $row['actions_ser'];
			$object->is_sticky = $row['is_sticky'];
			$object->sticky_order = $row['sticky_order'];

			$object->criteria = (!empty($criteria_ser)) ? @unserialize($criteria_ser) : array();
			$object->actions = (!empty($actions_ser)) ? @unserialize($actions_ser) : array();

			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		
		if(empty($ids))
			return;
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM mail_to_group_rule WHERE id IN (%s)", $ids_list));
		
		return true;
	}

	/**
	 * Increment the number of times we've matched this rule
	 *
	 * @param integer $id
	 */
	static function increment($id) {
		$db = DevblocksPlatform::getDatabaseService();
		$db->Execute(sprintf("UPDATE mail_to_group_rule SET pos = pos + 1 WHERE id = %d",
			$id
		));
	}

};

class DAO_WorkerWorkspaceList extends DevblocksORMHelper {
	const ID = 'id';
	const WORKER_ID = 'worker_id';
	const WORKSPACE = 'workspace';
	const SOURCE_EXTENSION = 'source_extension';
	const LIST_VIEW = 'list_view';
	const LIST_POS = 'list_pos';
	
	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($fields))
			return NULL;
		
		$sql = sprintf("INSERT INTO worker_workspace_list () ".
			"VALUES ()"
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		$id = $db->LastInsertId();

		self::update($id, $fields);
		
		return $id;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return Model_WorkerWorkspaceList
	 */
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
	 * Enter description here...
	 *
	 * @param string $where
	 * @return Model_WorkerWorkspaceList[]
	 */
	static function getWhere($where) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, worker_id, workspace, source_extension, list_view, list_pos ".
			"FROM worker_workspace_list ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : " ").
			"ORDER BY list_pos ASC";
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 

		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_WorkerWorkspaceList();
			$object->id = intval($row['id']);
			$object->worker_id = intval($row['worker_id']);
			$object->workspace = $row['workspace'];
			$object->source_extension = $row['source_extension'];
			$object->list_pos = intval($row['list_pos']);
			
			$list_view = $row['list_view'];
			if(!empty($list_view)) {
				@$object->list_view = unserialize($list_view);
			}
			
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	static function getWorkspaces($worker_id = 0) {
		$workspaces = array();
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT DISTINCT workspace AS workspace ".
			"FROM worker_workspace_list ".
			(!empty($worker_id) ? sprintf("WHERE worker_id = %d ",$worker_id) : " ").
			"ORDER BY workspace";
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 

		
		while($row = mysql_fetch_assoc($rs)) {
			$workspaces[] = $row['workspace'];
		}
		
		mysql_free_result($rs);
		
		return $workspaces;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'worker_workspace_list', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('worker_workspace_list', $fields, $where);
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		
		if(empty($ids))
			return;
		
		$db = DevblocksPlatform::getDatabaseService();
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE QUICK FROM worker_workspace_list WHERE id IN (%s)", $ids_list)) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
	}
};

class DAO_CustomField extends DevblocksORMHelper {
	const ID = 'id';
	const NAME = 'name';
	const TYPE = 'type';
	const GROUP_ID = 'group_id';
	const SOURCE_EXTENSION = 'source_extension';
	const POS = 'pos';
	const OPTIONS = 'options';
	
	const CACHE_ALL = 'ch_customfields'; 
	
	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("INSERT INTO custom_field () ".
			"VALUES ()"
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		$id = $db->LastInsertId(); 

		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'custom_field', $fields);
		
		self::clearCache();
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return Model_CustomField|null
	 */
	static function get($id) {
		$fields = self::getAll();
		
		if(isset($fields[$id]))
			return $fields[$id];
			
		return null;
	}
	
	static function getBySourceAndGroupId($source_ext_id, $group_id) {
		$fields = self::getAll();

		// Filter out groups that don't match
		foreach($fields as $field_id => $field) { /* @var $field Model_CustomField */
			if($group_id != $field->group_id || $source_ext_id != $field->source_extension) {
				unset($fields[$field_id]);
			}
		}
		
		return $fields;
	}
	
	static function getBySource($source_ext_id) {
		$fields = self::getAll();
		
		// Filter fields to only the requested source
		foreach($fields as $idx => $field) { /* @var $field Model_CustomField */
			if(0 != strcasecmp($field->source_extension, $source_ext_id))
				unset($fields[$idx]);
		}
		
		return $fields;
	}
	
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::getCacheService();
		
		if(null === ($objects = $cache->load(self::CACHE_ALL))) {
			$db = DevblocksPlatform::getDatabaseService();
			$sql = "SELECT id, name, type, source_extension, group_id, pos, options ".
				"FROM custom_field ".
				"ORDER BY group_id ASC, pos ASC "
			;
			$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
			
			$objects = self::_createObjectsFromResultSet($rs);
			
			$cache->save($objects, self::CACHE_ALL);
		}
		
		return $objects;
	}
	
	private static function _createObjectsFromResultSet($rs) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_CustomField();
			$object->id = intval($row['id']);
			$object->name = $row['name'];
			$object->type = $row['type'];
			$object->source_extension = $row['source_extension'];
			$object->group_id = intval($row['group_id']);
			$object->pos = intval($row['pos']);
			$object->options = DevblocksPlatform::parseCrlfString($row['options']);
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	public static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		
		if(empty($ids))
			return;
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$id_string = implode(',', $ids);
		
		$sql = sprintf("DELETE QUICK FROM custom_field WHERE id IN (%s)",$id_string);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 

		if(is_array($ids))
		foreach($ids as $id) {
			DAO_CustomFieldValue::deleteByFieldId($id);
		}
		
		self::clearCache();
	}
	
	public static function clearCache() {
		// Invalidate cache on changes
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_ALL);
	}
};

class DAO_CustomFieldValue extends DevblocksORMHelper {
	const FIELD_ID = 'field_id';
	const SOURCE_EXTENSION = 'source_extension';
	const SOURCE_ID = 'source_id';
	const FIELD_VALUE = 'field_value';
	
	public static function getValueTableName($field_id) {
		$field = DAO_CustomField::get($field_id);
		
		// Determine value table by type
		$table = null;
		switch($field->type) {
			// stringvalue
			case Model_CustomField::TYPE_SINGLE_LINE:
			case Model_CustomField::TYPE_DROPDOWN:	
			case Model_CustomField::TYPE_MULTI_CHECKBOX:	
			case Model_CustomField::TYPE_MULTI_PICKLIST:
			case Model_CustomField::TYPE_URL:
				$table = 'custom_field_stringvalue';	
				break;
			// clobvalue
			case Model_CustomField::TYPE_MULTI_LINE:
				$table = 'custom_field_clobvalue';
				break;
			// number
			case Model_CustomField::TYPE_CHECKBOX:
			case Model_CustomField::TYPE_DATE:
			case Model_CustomField::TYPE_NUMBER:
			case Model_CustomField::TYPE_WORKER:
				$table = 'custom_field_numbervalue';
				break;	
		}
		
		return $table;
	}
	
	/**
	 * 
	 * @param object $source_ext_id
	 * @param object $source_id
	 * @param object $values
	 * @return 
	 */
	public static function formatAndSetFieldValues($source_ext_id, $source_id, $values, $is_blank_unset=true, $delta=false, $autoadd_options=false) {
		if(empty($source_ext_id) || empty($source_id) || !is_array($values))
			return;

		$fields = DAO_CustomField:: getBySource($source_ext_id);

		foreach($values as $field_id => $value) {
			if(!isset($fields[$field_id]))
				continue;

			$field =& $fields[$field_id]; /* @var $field Model_CustomField */
			$is_delta = ($field->type==Model_CustomField::TYPE_MULTI_CHECKBOX || $field->type==Model_CustomField::TYPE_MULTI_PICKLIST) 
					? $delta 
					: false
					;

			// if the field is blank
			if(
				(is_array($value) && empty($value))
				||
				(!is_array($value) && 0==strlen($value))
			) {
				// ... and blanks should unset
				if($is_blank_unset && !$is_delta)
					self::unsetFieldValue($source_ext_id, $source_id, $field_id);
				
				// Skip setting
				continue;
			}

			switch($field->type) {
				case Model_CustomField::TYPE_SINGLE_LINE:
				case Model_CustomField::TYPE_URL:
					$value = (strlen($value) > 255) ? substr($value,0,255) : $value;
					self::setFieldValue($source_ext_id, $source_id, $field_id, $value);
					break;

				case Model_CustomField::TYPE_MULTI_LINE:
					self::setFieldValue($source_ext_id, $source_id, $field_id, $value);
					break;

				case Model_CustomField::TYPE_DROPDOWN:
					// If we're setting a field that doesn't exist yet, add it.
					if($autoadd_options && !in_array($value, $field->options) && !empty($value)) {
						$field->options[] = $value;
						DAO_CustomField::update($field_id, array(DAO_CustomField::OPTIONS => implode("\n",$field->options)));
					}
					
					// If we're allowed to add/remove fields without touching the rest
					if(in_array($value, $field->options))
						self::setFieldValue($source_ext_id, $source_id, $field_id, $value); 
					
					break;
					
				case Model_CustomField::TYPE_MULTI_PICKLIST:
				case Model_CustomField::TYPE_MULTI_CHECKBOX:
					if(!is_array($value))
						$value = array($value);

					// If we're setting a field that doesn't exist yet, add it.
					foreach($value as $v) {
						if($autoadd_options && !in_array($v, $field->options) && !empty($v)) {
							$field->options[] = $v;
							DAO_CustomField::update($field_id, array(DAO_CustomField::OPTIONS => implode("\n",$field->options)));
						}
					}

					if(!$delta) {
						self::unsetFieldValue($source_ext_id, $source_id, $field_id);
					}
					
					// Protect from injection in cases where it's not desireable (controlled above)
					foreach($value as $idx => $v) {
						if(!in_array($v, $field->options))
							continue;

						$is_unset = ('-'==substr($v,0,1)) ? true : false;
						$v = ltrim($v,'+-');
							
						if($is_unset) {
							if($delta)
								self::unsetFieldValue($source_ext_id, $source_id, $field_id, $v);
						} else {
							self::setFieldValue($source_ext_id, $source_id, $field_id, $v, true);
						}
					}

					break;

				case Model_CustomField::TYPE_CHECKBOX:
					$value = !empty($value) ? 1 : 0;
					self::setFieldValue($source_ext_id, $source_id, $field_id, $value);
					break;

				case Model_CustomField::TYPE_DATE:
					@$value = strtotime($value);
					self::setFieldValue($source_ext_id, $source_id, $field_id, $value);
					break;

				case Model_CustomField::TYPE_NUMBER:
					$value = intval($value);
					self::setFieldValue($source_ext_id, $source_id, $field_id, $value);
					break;
					
				case Model_CustomField::TYPE_WORKER:
					$value = intval($value);
					self::setFieldValue($source_ext_id, $source_id, $field_id, $value);
					break;
			}
		}
		
	}
	
	public static function setFieldValue($source_ext_id, $source_id, $field_id, $value, $delta=false) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(null == ($field = DAO_CustomField::get($field_id)))
			return FALSE;
		
		if(null == ($table_name = self::getValueTableName($field_id)))
			return FALSE;

		// Data formating
		switch($field->type) {
			case 'D': // dropdown
			case 'S': // string
			case 'U': // URL
				if(255 < strlen($value))
					$value = substr($value,0,255);
				break;
			case 'E': // date
			case 'N': // number
			case 'W': // worker
				$value = intval($value);
		}
		
		// Clear existing values (beats replace logic)
		self::unsetFieldValue($source_ext_id, $source_id, $field_id, ($delta?$value:null));

		// Set values consistently
		if(!is_array($value))
			$value = array($value);
			
		foreach($value as $v) {
			$sql = sprintf("INSERT INTO %s (field_id, source_extension, source_id, field_value) ".
				"VALUES (%d, %s, %d, %s)",
				$table_name,
				$field_id,
				$db->qstr($source_ext_id),
				$source_id,
				$db->qstr($v)
			);
			$db->Execute($sql);
		}
		
		return TRUE;
	}
	
	public static function unsetFieldValue($source_ext_id, $source_id, $field_id, $value=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(null == ($field = DAO_CustomField::get($field_id)))
			return FALSE;
		
		if(null == ($table_name = self::getValueTableName($field_id)))
			return FALSE;
		
		if(!is_array($value))
			$value = array($value);
			
		foreach($value as $v) {
			// Delete all values or optionally a specific given value
			$sql = sprintf("DELETE QUICK FROM %s WHERE source_extension = '%s' AND source_id = %d AND field_id = %d %s",
				$table_name,
				$source_ext_id,
				$source_id,
				$field_id,
				(!is_null($v) ? sprintf("AND field_value = %s ",$db->qstr($v)) : "")
			);
			$db->Execute($sql);
		}
		
		return TRUE;
	}
	
	public static function handleBulkPost($do) {
		@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'],'array',array());

		$fields = DAO_CustomField::getAll();
		
		if(is_array($field_ids))
		foreach($field_ids as $field_id) {
			if(!isset($fields[$field_id]))
				continue;
			
			switch($fields[$field_id]->type) {
				case Model_CustomField::TYPE_MULTI_LINE:
				case Model_CustomField::TYPE_SINGLE_LINE:
				case Model_CustomField::TYPE_URL:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'string','');
					$do['cf_'.$field_id] = array('value' => $field_value);
					break;
					
				case Model_CustomField::TYPE_NUMBER:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'string','');
					$field_value = (0==strlen($field_value)) ? '' : intval($field_value);
					$do['cf_'.$field_id] = array('value' => $field_value);
					break;
					
				case Model_CustomField::TYPE_DROPDOWN:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'string','');
					$do['cf_'.$field_id] = array('value' => $field_value);
					break;
					
				case Model_CustomField::TYPE_MULTI_PICKLIST:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'array',array());
					$do['cf_'.$field_id] = array('value' => $field_value);
					break;
					
				case Model_CustomField::TYPE_CHECKBOX:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'integer',0);
					$do['cf_'.$field_id] = array('value' => !empty($field_value) ? 1 : 0);
					break;

				case Model_CustomField::TYPE_MULTI_CHECKBOX:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'array',array());
					$do['cf_'.$field_id] = array('value' => $field_value);
					break;
					
				case Model_CustomField::TYPE_DATE:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'string','');
					$do['cf_'.$field_id] = array('value' => $field_value);
					break;
					
				case Model_CustomField::TYPE_WORKER:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'string','');
					$do['cf_'.$field_id] = array('value' => $field_value);
					break;
					
			}
		}
		
		return $do;
	}
	
	public static function handleFormPost($source_ext_id, $source_id, $field_ids) {
		$fields = DAO_CustomField::getBySource($source_ext_id);
		
		if(is_array($field_ids))
		foreach($field_ids as $field_id) {
			if(!isset($fields[$field_id]))
				continue;
			
			switch($fields[$field_id]->type) {
				case Model_CustomField::TYPE_MULTI_LINE:
				case Model_CustomField::TYPE_SINGLE_LINE:
				case Model_CustomField::TYPE_URL:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'string','');
					if(0 != strlen($field_value)) {
						DAO_CustomFieldValue::setFieldValue($source_ext_id, $source_id, $field_id, $field_value);
					} else {
						DAO_CustomFieldValue::unsetFieldValue($source_ext_id, $source_id, $field_id);
					}
					break;
					
				case Model_CustomField::TYPE_DROPDOWN:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'string','');
					if(0 != strlen($field_value)) {
						DAO_CustomFieldValue::setFieldValue($source_ext_id, $source_id, $field_id, $field_value);
					} else {
						DAO_CustomFieldValue::unsetFieldValue($source_ext_id, $source_id, $field_id);
					}
					break;
					
				case Model_CustomField::TYPE_MULTI_PICKLIST:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'array',array());
					if(!empty($field_value)) {
						DAO_CustomFieldValue::setFieldValue($source_ext_id, $source_id, $field_id, $field_value);
					} else {
						DAO_CustomFieldValue::unsetFieldValue($source_ext_id, $source_id, $field_id);
					}
					break;
					
				case Model_CustomField::TYPE_CHECKBOX:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'integer',0);
					$set = !empty($field_value) ? 1 : 0;
					DAO_CustomFieldValue::setFieldValue($source_ext_id, $source_id, $field_id, $set);
					break;

				case Model_CustomField::TYPE_MULTI_CHECKBOX:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'array',array());
					if(!empty($field_value)) {
						DAO_CustomFieldValue::setFieldValue($source_ext_id, $source_id, $field_id, $field_value);
					} else {
						DAO_CustomFieldValue::unsetFieldValue($source_ext_id, $source_id, $field_id);
					}
					break;
				
				case Model_CustomField::TYPE_DATE:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'string','');
					@$date = strtotime($field_value);
					if(!empty($date)) {
						DAO_CustomFieldValue::setFieldValue($source_ext_id, $source_id, $field_id, $date);
					} else {
						DAO_CustomFieldValue::unsetFieldValue($source_ext_id, $source_id, $field_id);
					}
					break;

				case Model_CustomField::TYPE_NUMBER:
				case Model_CustomField::TYPE_WORKER:
					@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'string','');
					if(0 != strlen($field_value)) {
						DAO_CustomFieldValue::setFieldValue($source_ext_id, $source_id, $field_id, intval($field_value));
					} else {
						DAO_CustomFieldValue::unsetFieldValue($source_ext_id, $source_id, $field_id);
					}
					break;
			}
		}
		
		return true;
	}
	
	public static function getValuesBySourceIds($source_ext_id, $source_ids) {
		if(is_null($source_ids))
			return array();
		elseif(!is_array($source_ids))
			$source_ids = array($source_ids);

		if(empty($source_ids))
			return array();
			
		$db = DevblocksPlatform::getDatabaseService();
		
		$results = array();
		
		$fields = DAO_CustomField::getAll();
			
		// [TODO] This is inefficient (and redundant)
			
		// STRINGS
		$sql = sprintf("SELECT source_id, field_id, field_value ".
			"FROM custom_field_stringvalue ".
			"WHERE source_extension = '%s' AND source_id IN (%s)",
			$source_ext_id,
			implode(',', $source_ids)
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		while($row = mysql_fetch_assoc($rs)) {
			$source_id = intval($row['source_id']);
			$field_id = intval($row['field_id']);
			$field_value = $row['field_value'];
			
			if(!isset($results[$source_id]))
				$results[$source_id] = array();
				
			$source =& $results[$source_id];
			
			// If multiple value type (multi-picklist, multi-checkbox)
			if($fields[$field_id]->type=='M' || $fields[$field_id]->type=='X') {
				if(!isset($source[$field_id]))
					$source[$field_id] = array();
					
				$source[$field_id][$field_value] = $field_value;
				
			} else { // single value
				$source[$field_id] = $field_value;
				
			}
		}
		
		mysql_free_result($rs);
		
		// CLOBS
		$sql = sprintf("SELECT source_id, field_id, field_value ".
			"FROM custom_field_clobvalue ".
			"WHERE source_extension = '%s' AND source_id IN (%s)",
			$source_ext_id,
			implode(',', $source_ids)
		);
		
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 

		while($row = mysql_fetch_assoc($rs)) {
			$source_id = intval($row['source_id']);
			$field_id = intval($row['field_id']);
			$field_value = $row['field_value'];
			
			if(!isset($results[$source_id]))
				$results[$source_id] = array();
				
			$source =& $results[$source_id];
			$source[$field_id] = $field_value;
		}
		
		mysql_free_result($rs);

		// NUMBERS
		$sql = sprintf("SELECT source_id, field_id, field_value ".
			"FROM custom_field_numbervalue ".
			"WHERE source_extension = '%s' AND source_id IN (%s)",
			$source_ext_id,
			implode(',', $source_ids)
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 

		while($row = mysql_fetch_assoc($rs)) {
			$source_id = intval($row['source_id']);
			$field_id = intval($row['field_id']);
			$field_value = $row['field_value'];
			
			if(!isset($results[$source_id]))
				$results[$source_id] = array();
				
			$source =& $results[$source_id];
			$source[$field_id] = $field_value;
		}
		
		mysql_free_result($rs);
		
		return $results;
	}
	
	public static function deleteBySourceIds($source_extension, $source_ids) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(!is_array($source_ids)) $source_ids = array($source_ids);
		$ids_list = implode(',', $source_ids);

		$tables = array('custom_field_stringvalue','custom_field_clobvalue','custom_field_numbervalue');
		
		if(!empty($source_ids))
		foreach($tables as $table) {
			$sql = sprintf("DELETE QUICK FROM %s WHERE source_extension = %s AND source_id IN (%s)",
				$table,
				$db->qstr($source_extension),
				implode(',', $source_ids)
			);
			$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		}
	}
	
	public static function deleteByFieldId($field_id) {
		$db = DevblocksPlatform::getDatabaseService();

		$tables = array('custom_field_stringvalue','custom_field_clobvalue','custom_field_numbervalue');

		foreach($tables as $table) {
			$sql = sprintf("DELETE QUICK FROM %s WHERE field_id = %d",
				$table,
				$field_id
			);
			$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		}

	}
};

class DAO_Overview {
	static function getGroupTotals() {
		$db = DevblocksPlatform::getDatabaseService();

		$active_worker = CerberusApplication::getActiveWorker();
		$memberships = $active_worker->getMemberships();

		// Does the active worker want to filter anything out?
		// [TODO] DAO_WorkerPref should really auto serialize/deserialize
		
		if(empty($memberships))
			return array();
		
		// Group Loads
		$sql = sprintf("SELECT count(*) AS hits, team_id, category_id ".
		"FROM ticket ".
		"WHERE is_waiting = 0 AND is_closed = 0 AND is_deleted = 0 ".
		"GROUP BY team_id, category_id "
		);
		$rs = $db->Execute($sql);

		$group_counts = array();
		while($row = mysql_fetch_assoc($rs)) {
			$team_id = intval($row['team_id']);
			$category_id = intval($row['category_id']);
			$hits = intval($row['hits']);
				
			if(isset($memberships[$team_id])) {
				// If the active worker is filtering out these buckets, don't total.
				if(!isset($group_counts[$team_id]))
					$group_counts[$team_id] = array();

				$group_counts[$team_id][$category_id] = $hits;
				@$group_counts[$team_id]['total'] = intval($group_counts[$team_id]['total']) + $hits;
			}
		}
		
		mysql_free_result($rs);

		return $group_counts;
	}

	static function getWaitingTotals() {
		$db = DevblocksPlatform::getDatabaseService();

		$active_worker = CerberusApplication::getActiveWorker();
		$memberships = $active_worker->getMemberships();

		if(empty($memberships))
			return array();
		
		// Waiting For Reply Loads
		$sql = sprintf("SELECT count(*) AS hits, team_id, category_id ".
		"FROM ticket ".
		"WHERE is_waiting = 1 AND is_closed = 0 AND is_deleted = 0 ".
		"GROUP BY team_id, category_id "
		);
		$rs = $db->Execute($sql);

		$waiting_counts = array();
		while($row = mysql_fetch_assoc($rs)) {
			$team_id = intval($row['team_id']);
			$category_id = intval($row['category_id']);
			$hits = intval($row['hits']);
				
			if(isset($memberships[$team_id])) {
				if(!isset($waiting_counts[$team_id]))
				$waiting_counts[$team_id] = array();

				$waiting_counts[$team_id][$category_id] = $hits;
				@$waiting_counts[$team_id]['total'] = intval($waiting_counts[$team_id]['total']) + $hits;
			}
		}
		
		mysql_free_result($rs);

		return $waiting_counts;
	}

	static function getWorkerTotals() {
		$db = DevblocksPlatform::getDatabaseService();

		$active_worker = CerberusApplication::getActiveWorker();
		$memberships = $active_worker->getMemberships();
		
		if(empty($memberships))
			return array();
		
		// Worker Loads
		$sql = sprintf("SELECT COUNT(from_context_id) AS hits, from_context_id AS worker_id ".
			"FROM context_link ".
			"INNER JOIN ticket ON (context_link.to_context_id=ticket.id) ".
			"WHERE ticket.is_closed = 0 ".
			"AND ticket.is_waiting = 0 ".
			"AND ticket.is_deleted = 0 ".
			"AND ticket.team_id IN (%s) ".
			"AND from_context = 'cerberusweb.contexts.worker' ".
			"AND to_context = 'cerberusweb.contexts.ticket' ".
			"GROUP BY from_context_id",
			implode(',', array_keys($memberships))
		);
		$rs = $db->Execute($sql);

		$worker_counts = array();
		while($row = mysql_fetch_assoc($rs)) {
			$hits = intval($row['hits']);
			$worker_id = intval($row['worker_id']);
			$worker_counts[$worker_id] = $hits;
		}
		
		mysql_free_result($rs);

		return $worker_counts;
	}
}

class DAO_WorkflowView {
	static function getGroupTotals() {
		$db = DevblocksPlatform::getDatabaseService();

		$active_worker = CerberusApplication::getActiveWorker();
		$memberships = $active_worker->getMemberships();

		if(empty($memberships))
			return array();
		
		// Group Loads
		$sql = "SELECT count(t.id) AS hits, t.team_id, t.category_id ". 
			"FROM ticket t ".
			"LEFT JOIN category c ON (t.category_id=c.id) ". 
			"LEFT JOIN context_link ON (context_link.from_context = 'cerberusweb.contexts.ticket' AND context_link.from_context_id = t.id AND context_link.to_context = 'cerberusweb.contexts.worker') ".
			"WHERE context_link.to_context_id IS NULL ".
			"AND t.is_waiting = 0 ".
			"AND t.is_closed = 0 ".
			"AND (c.id IS NULL OR c.is_assignable = 1) ".
			"GROUP BY t.team_id, c.pos"
		; 
		$rs = $db->Execute($sql);

		$group_counts = array();
		while($row = mysql_fetch_assoc($rs)) {
			$team_id = intval($row['team_id']);
			$category_id = intval($row['category_id']);
			$hits = intval($row['hits']);
				
			if(isset($memberships[$team_id])) {
				// If the group manager doesn't want this group inbox assignable (default to YES)
				if(empty($category_id) && !DAO_GroupSettings::get($team_id, DAO_GroupSettings::SETTING_INBOX_IS_ASSIGNABLE, 1)) {
					// ...skip the unassignable inbox	
				} else {
					if(!isset($group_counts[$team_id]))
						$group_counts[$team_id] = array();
						
					$group_counts[$team_id][$category_id] = $hits;
					@$group_counts[$team_id]['total'] = intval($group_counts[$team_id]['total']) + $hits;
				}
			}
		}
		
		mysql_free_result($rs);

		return $group_counts;
	}
};
