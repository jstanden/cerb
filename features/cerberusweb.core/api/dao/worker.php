<?php
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
	const LAST_ACTIVITY_DATE = 'last_activity_date';
	const LAST_ACTIVITY = 'last_activity';
	
	// [TODO] Convert to ::create($id, $fields)
	static function create($email, $password, $first_name, $last_name, $title) {
		if(empty($email) || empty($password))
			return null;
			
		$db = DevblocksPlatform::getDatabaseService();
		$id = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO worker (id, email, pass, first_name, last_name, title, is_superuser, is_disabled) ".
			"VALUES (%d, %s, %s, %s, %s, %s,0,0)",
			$id,
			$db->qstr($email),
			$db->qstr(md5($password)),
			$db->qstr($first_name),
			$db->qstr($last_name),
			$db->qstr($title)
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 

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
	
	static function getAllOnline() {
		list($whos_online_workers, $null) = self::search(
			array(),
		    array(
		        new DevblocksSearchCriteria(SearchFields_Worker::LAST_ACTIVITY_DATE,DevblocksSearchCriteria::OPER_GT,(time()-60*15)), // idle < 15 mins
		        new DevblocksSearchCriteria(SearchFields_Worker::LAST_ACTIVITY,DevblocksSearchCriteria::OPER_NOT_LIKE,'%translation_code";N;%'), // translation code not null (not just logged out)
		    ),
		    -1,
		    0,
		    SearchFields_Worker::LAST_ACTIVITY_DATE,
		    false,
		    false
		);
		
		if(!empty($whos_online_workers))
			return self::getList(array_keys($whos_online_workers));
			
		return array();
	}
	
	static function getAll($nocache=false, $with_disabled=true) {
	    $cache = DevblocksPlatform::getCacheService();
	    if($nocache || null === ($workers = $cache->load(self::CACHE_ALL))) {
    	    $workers = self::getList();
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
	
	static function getList($ids=array()) {
		if(!is_array($ids)) $ids = array($ids);
		
		$db = DevblocksPlatform::getDatabaseService();
		$workers = array();
		
		$sql = "SELECT a.id, a.first_name, a.last_name, a.email, a.pass, a.title, a.is_superuser, a.is_disabled, a.last_activity_date, a.last_activity ".
			"FROM worker a ".
			((!empty($ids) ? sprintf("WHERE a.id IN (%s) ",implode(',',$ids)) : " ").
			"ORDER BY a.first_name, a.last_name "
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		while($row = mysql_fetch_assoc($rs)) {
			$worker = new Model_Worker();
			$worker->id = intval($row['id']);
			$worker->first_name = $row['first_name'];
			$worker->last_name = $row['last_name'];
			$worker->email = $row['email'];
			$worker->pass = $row['pass'];
			$worker->title = $row['title'];
			$worker->is_superuser = intval($row['is_superuser']);
			$worker->is_disabled = intval($row['is_disabled']);
			$worker->last_activity_date = intval($row['last_activity_date']);
			
			if(!empty($row['last_activity']))
			    $worker->last_activity = unserialize($row['last_activity']);
			
			$workers[$worker->id] = $worker;
		}
		
		mysql_free_result($rs);
		
		return $workers;		
	}
	
	/**
	 * @return Model_Worker
	 */
	static function getAgent($id) {
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
	static function lookupAgentEmail($email) {
		if(empty($email)) return null;
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT a.id FROM worker a WHERE a.email = %s",
			$db->qstr($email)
		);
		
		if(null != ($id = $db->GetOne($sql)))
			return $id;
		
		return null;		
	}
	
	static function updateAgent($ids, $fields, $flush_cache=true) {
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
		
		$sql = "DELETE QUICK watcher_mail_filter FROM watcher_mail_filter LEFT JOIN worker ON watcher_mail_filter.worker_id = worker.id WHERE worker.id IS NULL";
		$db->Execute($sql);
		
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' watcher_mail_filter records.');
		
		$sql = "DELETE QUICK worker_pref FROM worker_pref LEFT JOIN worker ON worker_pref.worker_id = worker.id WHERE worker.id IS NULL";
		$db->Execute($sql);
		
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' worker_pref records.');
		
		$sql = "DELETE QUICK worker_to_team FROM worker_to_team LEFT JOIN worker ON worker_to_team.agent_id = worker.id WHERE worker.id IS NULL";
		$db->Execute($sql);
		
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' worker_to_team records.');
		
		$sql = "DELETE QUICK worker_workspace_list FROM worker_workspace_list LEFT JOIN worker ON worker_workspace_list.worker_id = worker.id WHERE worker.id IS NULL";
		$db->Execute($sql);
		
		// [TODO] Clear out workers from any group_inbox_filter rows
		
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' worker_workspace_list records.');
	}
	
	static function deleteAgent($id) {
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
		
		$sql = sprintf("DELETE QUICK FROM worker_to_team WHERE agent_id = %d", $id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 

		$sql = sprintf("DELETE QUICK FROM view_rss WHERE worker_id = %d", $id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 

		$sql = sprintf("DELETE QUICK FROM worker_workspace_list WHERE worker_id = %d", $id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 

		// Clear assigned workers
		$sql = sprintf("UPDATE ticket SET next_worker_id = 0 WHERE next_worker_id = %d", $id);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 

		// Clear roles
		$db->Execute(sprintf("DELETE FROM worker_to_role WHERE worker_id = %d", $id));
		
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
			return self::getAgent($worker_id);
		}
		
		return null;
	}
	
	static function setAgentTeams($agent_id, $team_ids) {
		if(!is_array($team_ids)) $team_ids = array($team_ids);
		if(empty($agent_id)) return;
		$db = DevblocksPlatform::getDatabaseService();

		$sql = sprintf("DELETE QUICK FROM worker_to_team WHERE agent_id = %d",
			$agent_id
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		foreach($team_ids as $team_id) {
			$sql = sprintf("INSERT INTO worker_to_team (agent_id, team_id) ".
				"VALUES (%d,%d)",
				$agent_id,
				$team_id
			);
			$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		}
		
		// Invalidate caches
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(DAO_Group::CACHE_ROSTERS);
	}
	
	/**
	 * @return Model_TeamMember[]
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
	static function logActivity($worker_id, Model_Activity $activity) {
	    DAO_Worker::updateAgent($worker_id,array(
	        DAO_Worker::LAST_ACTIVITY_DATE => time(),
	        DAO_Worker::LAST_ACTIVITY => serialize($activity)
	    ),false);
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
		$fields = SearchFields_Worker::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		$total = -1;
		
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
			
		$join_sql = "FROM worker w ";
		
		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			'w.id',
			$select_sql,
			$join_sql
		);
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
			
		$sql = 
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY w.id ' : '').
			$sort_sql;
			
		// [TODO] Could push the select logic down a level too
		if($limit > 0) {
    		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		} else {
		    $rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
            $total = mysql_num_rows($rs);
		}
		
		$results = array();
		
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
		);
		
		// Custom Fields
		$fields = DAO_CustomField::getBySource(ChCustomFieldSource_Worker::ID);

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

	/**
	 * @return Model_TeamMember[]
	 */
	function getMemberships() {
		return DAO_Worker::getWorkerGroups($this->id); 
	}

	function hasPriv($priv_id) {
		// We don't need to do much work if we're a superuser
		if($this->is_superuser)
			return true;
		
		$settings = DevblocksPlatform::getPluginSettingsService();
		$acl_enabled = $settings->get('cerberusweb.core',CerberusSettings::ACL_ENABLED,CerberusSettingsDefaults::ACL_ENABLED);
			
		// ACL is a paid feature (please respect the licensing and support the project!)
		$license = CerberusLicense::getInstance();
		if(!$acl_enabled || !isset($license['serial']) || isset($license['a']))
			return ("core.config"==substr($priv_id,0,11)) ? false : true;
			
		// Check the aggregated worker privs from roles
		$acl = DAO_WorkerRole::getACL();
		$privs_by_worker = $acl[DAO_WorkerRole::CACHE_KEY_PRIVS_BY_WORKER];
		
		if(!empty($priv_id) && isset($privs_by_worker[$this->id][$priv_id]))
			return true;
			
		return false;
	}
	
	function isTeamManager($team_id) {
		@$memberships = $this->getMemberships();
		$teams = DAO_Group::getAll();
		if(
			empty($team_id) // null
			|| !isset($teams[$team_id]) // doesn't exist
			|| !isset($memberships[$team_id])  // not a member
			|| (!$memberships[$team_id]->is_manager && !$this->is_superuser) // not a manager or superuser
		){
			return false;
		}
		return true;
	}

	function isTeamMember($team_id) {
		@$memberships = $this->getMemberships();
		$teams = DAO_Group::getAll();
		if(
			empty($team_id) // null
			|| !isset($teams[$team_id]) // not a team
			|| !isset($memberships[$team_id]) // not a member
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

class View_Worker extends C4_AbstractView {
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
		
		$this->doResetCriteria();
	}

	function getData() {
		return DAO_Worker::search(
			$this->view_columns,
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc
		);
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Worker::ID);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_fields', $this->getColumns());
		$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/configuration/tabs/workers/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_Worker::EMAIL:
			case SearchFields_Worker::FIRST_NAME:
			case SearchFields_Worker::LAST_NAME:
			case SearchFields_Worker::TITLE:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__string.tpl');
				break;
			case SearchFields_Worker::IS_DISABLED:
			case SearchFields_Worker::IS_SUPERUSER:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__bool.tpl');
				break;
			case SearchFields_Worker::LAST_ACTIVITY_DATE:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__date.tpl');
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
//			case SearchFields_WorkerEvent::WORKER_ID:
//				$workers = DAO_Worker::getAll();
//				$strings = array();
//
//				foreach($values as $val) {
//					if(empty($val))
//					$strings[] = "Nobody";
//					elseif(!isset($workers[$val]))
//					continue;
//					else
//					$strings[] = $workers[$val]->getName();
//				}
//				echo implode(", ", $strings);
//				break;
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	static function getFields() {
		return SearchFields_Worker::getFields();
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_Worker::ID]);
		unset($fields[SearchFields_Worker::LAST_ACTIVITY]);
		return $fields;
	}

	static function getColumns() {
		$fields = self::getFields();
		unset($fields[SearchFields_Worker::LAST_ACTIVITY]);
		return $fields;
	}

	function doResetCriteria() {
		parent::doResetCriteria();
		
//		$this->params = array(
//			SearchFields_WorkerEvent::NUM_NONSPAM => new DevblocksSearchCriteria(SearchFields_WorkerEvent::NUM_NONSPAM,'>',0),
//		);
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Worker::EMAIL:
			case SearchFields_Worker::FIRST_NAME:
			case SearchFields_Worker::LAST_NAME:
			case SearchFields_Worker::TITLE:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
				
			case SearchFields_Worker::LAST_ACTIVITY_DATE:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
				
			case SearchFields_Worker::IS_DISABLED:
			case SearchFields_Worker::IS_SUPERUSER:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
		}

		if(!empty($criteria)) {
			$this->params[$field] = $criteria;
			$this->renderPage = 0;
		}
	}

	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(600); // [TODO] Temp!
	  
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
			$this->params,
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
			DAO_Worker::updateAgent($batch_ids, $change_fields);
			
			// Custom Fields
			self::_doBulkSetCustomFields(ChCustomFieldSource_Worker::ID, $custom_fields, $batch_ids);
			
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