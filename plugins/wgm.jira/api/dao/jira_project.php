<?php
class DAO_JiraProject extends Cerb_ORMHelper {
	const CONNECTED_ACCOUNT_ID = 'connected_account_id';
	const ID = 'id';
	const JIRA_ID = 'jira_id';
	const JIRA_KEY = 'jira_key';
	const LAST_CHECKED_AT = 'last_checked_at';
	const LAST_SYNCED_AT = 'last_synced_at';
	const LAST_SYNCED_CHECKPOINT = 'last_synced_checkpoint';
	const NAME = 'name';
	const URL = 'url';
	const UPDATED_AT = 'updated_at';
	
	const _CACHE_ALL = 'cache_jira_project_all';

	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// int(10) unsigned
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// int(10) unsigned
		$validation
			->addField(self::CONNECTED_ACCOUNT_ID)
			->id()
			->addValidator($validation->validators()->contextId(Context_ConnectedAccount::ID, true))
			;
		// int(10) unsigned
		$validation
			->addField(self::JIRA_ID)
			->id()
			->setEditable(false)
			;
		// varchar(32)
		$validation
			->addField(self::JIRA_KEY)
			->string()
			->setMaxLength(32)
			->setRequired(true)
			;
		// varchar(255)
		$validation
			->addField(self::NAME)
			->string()
			->setMaxLength(255)
			->setRequired(true)
			;
		// int(10) unsigned
		$validation
			->addField(self::LAST_CHECKED_AT)
			->timestamp()
			->setEditable(false)
			;
		// int(10) unsigned
		$validation
			->addField(self::LAST_SYNCED_AT)
			->timestamp()
			->setEditable(false)
			;
		// int(10) unsigned
		$validation
			->addField(self::UPDATED_AT)
			->timestamp()
			;
		// varchar(255)
		$validation
			->addField(self::URL)
			->url()
			;
		$validation
			->addField('_fieldsets')
			->string()
			->setMaxLength(65535)
			;
		$validation
			->addField('_links')
			->string()
			->setMaxLength(65535)
			;
			
		return $validation->getFields();
	}
	
	static function create($fields, $check_deltas=true) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = "INSERT INTO jira_project () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields, $check_deltas);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
		
		$context = Context_JiraProject::ID;
		self::_updateAbstract($context, $ids, $fields);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
			
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(Context_JiraProject::ID, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'jira_project', $fields);
			
			// Send events
			if($check_deltas) {
				
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.jira_project.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(Context_JiraProject::ID, $batch_ids);
			}
		}
		
		self::clearCache();
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('jira_project', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = 'cerberusweb.contexts.jira.project';
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		return true;
	}
	
	/**
	 * @param bool $nocache
	 * @return Model_JiraProject[]
	 */
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::services()->cache();
		if($nocache || null === ($projects = $cache->load(self::_CACHE_ALL))) {
			$projects = self::getWhere(
				null,
				DAO_JiraProject::NAME,
				true,
				null,
				Cerb_ORMHelper::OPT_GET_MASTER_ONLY
			);
			
			if(!is_array($projects))
				return false;
			
			$cache->save($projects, self::_CACHE_ALL);
		}
		
		return $projects;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_JiraProject[]
	 */
	static function getWhere($where=null, $sortBy=DAO_JiraProject::NAME, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, jira_id, jira_key, name, url, updated_at, last_checked_at, last_synced_at, last_synced_checkpoint, connected_account_id ".
			"FROM jira_project ".
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
	 * @param integer $id
	 * @return Model_JiraProject
	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$projects = DAO_JiraProject::getAll();
		
		if(isset($projects[$id]))
			return $projects[$id];
		
		return null;
	}
	
	/**
	 * 
	 * @param array $ids
	 * @return Model_JiraProject[]
	 */
	static function getIds($ids) {
		return parent::getIds($ids);
	}
	
	static function random() {
		return self::_getRandom('jira_project');
	}
	
	/**
	 *
	 * @param integer $remote_id
	 * @return Model_JiraProject|null
	 */
	static function getByJiraId($remote_id, $nocache=false) {
		// If we're ignoring the cache, check the database directly
		if($nocache) {
			$results = DAO_JiraProject::getWhere(
				sprintf("%s = %d",
					DAO_JiraProject::JIRA_ID,
					$remote_id
				)
			);
			
			if(is_array($results) && !empty($results))
				return array_shift($results);
			
			return null;
		}
		
		
		$projects = DAO_JiraProject::getAll();
		
		foreach($projects as $project) { /* @var $project Model_JiraProject */
			if($project->jira_id == $remote_id)
				return $project;
		}
		
		return null;
	}
	
	/**
	 *
	 * @param string $jira_key
	 * @return Model_JiraProject|null
	 */
	static function getByJiraKey($jira_key) {
		$projects = DAO_JiraProject::getAll();
		
		foreach($projects as $project) { /* @var $project Model_JiraProject */
			if($project->jira_key == $jira_key)
				return $project;
		}
		
		return null;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_JiraProject[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_JiraProject();
			$object->id = intval($row['id']);
			$object->jira_id = $row['jira_id'];
			$object->jira_key = $row['jira_key'];
			$object->name = $row['name'];
			$object->url = $row['url'];
			$object->last_checked_at = intval($row['last_checked_at']);
			$object->last_synced_at = intval($row['last_synced_at']);
			$object->last_synced_checkpoint = intval($row['last_synced_checkpoint']);
			$object->connected_account_id = intval($row['connected_account_id']);
			$object->updated_at = intval($row['updated_at']);
			
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM jira_project WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => Context_JiraProject::ID,
					'context_ids' => $ids
				)
			)
		);
		
		self::clearCache();
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_JiraProject::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_JiraProject', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"jira_project.id as %s, ".
			"jira_project.jira_id as %s, ".
			"jira_project.jira_key as %s, ".
			"jira_project.name as %s, ".
			"jira_project.url as %s, ".
			"jira_project.updated_at as %s, ".
			"jira_project.last_checked_at as %s, ".
			"jira_project.last_synced_at as %s, ".
			"jira_project.connected_account_id as %s ",
				SearchFields_JiraProject::ID,
				SearchFields_JiraProject::JIRA_ID,
				SearchFields_JiraProject::JIRA_KEY,
				SearchFields_JiraProject::NAME,
				SearchFields_JiraProject::URL,
				SearchFields_JiraProject::UPDATED_AT,
				SearchFields_JiraProject::LAST_CHECKED_AT,
				SearchFields_JiraProject::LAST_SYNCED_AT,
				SearchFields_JiraProject::CONNECTED_ACCOUNT_ID
			);
			
		$join_sql = "FROM jira_project ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_JiraProject');
	
		return array(
			'primary_table' => 'jira_project',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
	}
	
	/**
	 *
	 * @param array $columns
	 * @param DevblocksSearchCriteria[] $params
	 * @param integer $limit
	 * @param integer $page
	 * @param string $sortBy
	 * @param boolean $sortAsc
	 * @param boolean $withCounts
	 * @return array
	 */
	static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::services()->database();
		
		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$sort_sql = $query_parts['sort'];
		
		$sql =
			$select_sql.
			$join_sql.
			$where_sql.
			$sort_sql;
			
		if($limit > 0) {
			if(false == ($rs = $db->SelectLimit($sql,$limit,$page*$limit)))
				return false;
		} else {
			if(false == ($rs = $db->ExecuteSlave($sql)))
				return false;
			$total = mysqli_num_rows($rs);
		}
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		$results = [];
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object_id = intval($row[SearchFields_JiraProject::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(jira_project.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}

	static public function clearCache() {
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::_CACHE_ALL);
	}
	
};

class SearchFields_JiraProject extends DevblocksSearchFields {
	const ID = 'j_id';
	const JIRA_ID = 'j_jira_id';
	const JIRA_KEY = 'j_jira_key';
	const NAME = 'j_name';
	const URL = 'j_url';
	const LAST_CHECKED_AT = 'j_last_checked_at';
	const LAST_SYNCED_AT = 'j_last_synced_at';
	const CONNECTED_ACCOUNT_ID = 'j_connected_account_id';
	const UPDATED_AT = 'j_updated_at';

	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_WATCHERS = '*_workers';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'jira_project.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			Context_JiraProject::ID => new DevblocksSearchFieldContextKeys('jira_project.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, Context_JiraProject::ID, self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%%s)', Cerb_ORMHelper::qstr(Context_JiraProject::ID)), self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, Context_JiraProject::ID, self::getPrimaryKey());
				break;
			
			default:
				if('cf_' == substr($param->field, 0, 3)) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
				break;
		}
	}
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		switch($key) {
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_JiraProject::ID:
				$models = DAO_JiraProject::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				break;
		}
		
		return parent::getLabelsForKeyValues($key, $values);
	}
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		if(is_null(self::$_fields))
			self::$_fields = self::_getFields();
		
		return self::$_fields;
	}
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function _getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'jira_project', 'id', $translate->_('common.id'), null, true),
			self::JIRA_ID => new DevblocksSearchField(self::JIRA_ID, 'jira_project', 'jira_id', $translate->_('dao.jira_project.jira_id'), null, true),
			self::JIRA_KEY => new DevblocksSearchField(self::JIRA_KEY, 'jira_project', 'jira_key', $translate->_('dao.jira_project.jira_key'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'jira_project', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::URL => new DevblocksSearchField(self::URL, 'jira_project', 'url', $translate->_('common.url'), Model_CustomField::TYPE_URL, true),
			self::LAST_CHECKED_AT => new DevblocksSearchField(self::LAST_CHECKED_AT, 'jira_project', 'last_checked_at', $translate->_('dao.jira_project.last_checked_at'), Model_CustomField::TYPE_DATE, true),
			self::LAST_SYNCED_AT => new DevblocksSearchField(self::LAST_SYNCED_AT, 'jira_project', 'last_synced_at', $translate->_('dao.jira_project.last_synced_at'), Model_CustomField::TYPE_DATE, true),
			self::CONNECTED_ACCOUNT_ID => new DevblocksSearchField(self::CONNECTED_ACCOUNT_ID, 'jira_project', 'connected_account_id', $translate->_('common.connected_account'), Model_CustomField::TYPE_NUMBER, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'jira_project', 'updated_at', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),

			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers'), 'WS', false),
		);
		
		// Custom Fields
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));
		
		if(!empty($custom_columns))
			$columns = array_merge($columns, $custom_columns);

		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_JiraProject {
	public $connected_account_id = 0;
	public $id = 0;
	public $jira_id = null;
	public $jira_key = null;
	public $name = null;
	public $url = null;
	public $issue_types = [];
	public $statuses = [];
	public $versions = [];
	public $last_checked_at = 0;
	public $last_synced_at = 0;
	public $updated_at = 0;
	
	private $_base_url = null;
	
	function getConnectedAccount() {
		if(!$this->connected_account_id)
			return null;
		
		return DAO_ConnectedAccount::get($this->connected_account_id);
	}
	
	function getConnectedService() {
		if(false == ($account = $this->getConnectedAccount()))
			return null;
		
		return $account->getService();
	}
	
	function getBaseUrl() {
		if(!is_null($this->_base_url))
			return $this->_base_url;
		
		if(method_exists($this, 'getConnectedService')) {
			if(false == ($service = $this->getConnectedService()))
				return null;
			
			$service_params = $service->decryptParams();
			$this->_base_url = @$service_params['base_url'];
			return $this->_base_url;
			
		// [TODO] Remove in 9.1
		} else {
			if(false == ($account = $this->getConnectedAccount()))
				return null;
			
			$account_params = $account->decryptParams();
			$this->_base_url = @$account_params['base_url'];
			return $this->_base_url;
		}
	}
};

class View_JiraProject extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'jira_projects';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('JIRA Projects');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_JiraProject::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_JiraProject::NAME,
			SearchFields_JiraProject::JIRA_KEY,
			SearchFields_JiraProject::URL,
			SearchFields_JiraProject::CONNECTED_ACCOUNT_ID,
			SearchFields_JiraProject::UPDATED_AT,
			SearchFields_JiraProject::LAST_CHECKED_AT,
			SearchFields_JiraProject::LAST_SYNCED_AT,
		);

		$this->addColumnsHidden(array(
			SearchFields_JiraProject::ID,
			SearchFields_JiraProject::JIRA_ID,
			SearchFields_JiraProject::VIRTUAL_CONTEXT_LINK,
			SearchFields_JiraProject::VIRTUAL_HAS_FIELDSET,
			SearchFields_JiraProject::VIRTUAL_WATCHERS,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_JiraProject::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_JiraProject');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_JiraProject', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_JiraProject', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
				case SearchFields_JiraProject::URL:
					$pass = true;
					break;
					
				// Virtuals
				case SearchFields_JiraProject::VIRTUAL_CONTEXT_LINK:
				case SearchFields_JiraProject::VIRTUAL_HAS_FIELDSET:
				case SearchFields_JiraProject::VIRTUAL_WATCHERS:
					$pass = true;
					break;
					
				// Valid custom fields
				default:
					if(DevblocksPlatform::strStartsWith($field_key, 'cf_'))
						$pass = $this->_canSubtotalCustomField($field_key);
					break;
			}
			
			if($pass)
				$fields[$field_key] = $field_model;
		}
		
		return $fields;
	}
	
	function getSubtotalCounts($column) {
		$counts = [];
		$fields = $this->getFields();
		$context = Context_JiraProject::ID;

		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_JiraProject::URL:
				$counts = $this->_getSubtotalCountForStringColumn($context, $column);
				break;
				
			case SearchFields_JiraProject::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;
				
			case SearchFields_JiraProject::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
				
			case SearchFields_JiraProject::VIRTUAL_WATCHERS:
				$counts = $this->_getSubtotalCountForWatcherColumn($context, $column);
				break;
			
			default:
				// Custom fields
				if(DevblocksPlatform::strStartsWith($column, 'cf_')) {
					$counts = $this->_getSubtotalCountForCustomColumn($context, $column);
				}
				
				break;
		}
		
		return $counts;
	}
	
	function getQuickSearchFields() {
		$search_fields = SearchFields_JiraProject::getFields();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_JiraProject::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_JiraProject::ID),
					'examples' => [
						['type' => 'chooser', 'context' => Context_JiraProject::ID, 'q' => ''],
					]
				),
			'account.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_JiraProject::CONNECTED_ACCOUNT_ID),
					'examples' => [
						['type' => 'chooser', 'context' => Context_ConnectedAccount::ID, 'q' => ''],
					]
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_JiraProject::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . Context_JiraProject::ID],
					]
				),
			'key' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_JiraProject::JIRA_KEY),
				),
			'lastCheckedAt' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_JiraProject::LAST_CHECKED_AT),
				),
			'lastSyncAt' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_JiraProject::LAST_SYNCED_AT),
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_JiraProject::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'url' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_JiraProject::URL),
				),
			'watchers' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_WORKER,
					'options' => array('param_key' => SearchFields_JiraProject::VIRTUAL_WATCHERS),
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_JiraProject::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(Context_JiraProject::ID, $fields, null);
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		
		ksort($fields);
		
		return $fields;
	}
	
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'fieldset':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, '*_has_fieldset');
				break;
			
			default:
				if($field == 'links' || substr($field, 0, 6) == 'links.')
					return DevblocksSearchCriteria::getContextLinksParamFromTokens($field, $tokens);
				
				$search_fields = $this->getQuickSearchFields();
				return DevblocksSearchCriteria::getParamFromQueryFieldTokens($field, $tokens, $search_fields);
				break;
		}
		
		return false;
	}
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(Context_JiraProject::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->assign('view_template', 'devblocks:wgm.jira::jira_project/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteriaParam($param) {
		$field = $param->field;

		switch($field) {
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_JiraProject::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_JiraProject::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
			
			case SearchFields_JiraProject::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_JiraProject::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_JiraProject::JIRA_KEY:
			case SearchFields_JiraProject::NAME:
			case SearchFields_JiraProject::URL:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_JiraProject::ID:
			case SearchFields_JiraProject::JIRA_ID:
			case SearchFields_JiraProject::CONNECTED_ACCOUNT_ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_JiraProject::LAST_CHECKED_AT:
			case SearchFields_JiraProject::LAST_SYNCED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_JiraProject::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_JiraProject::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_JiraProject::VIRTUAL_WATCHERS:
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_ids);
				break;
				
			default:
				// Custom Fields
				if(substr($field,0,3)=='cf_') {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				}
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria, $field);
			$this->renderPage = 0;
		}
	}
};

class Context_JiraProject extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	const ID = 'cerberusweb.contexts.jira.project';
	
	static function isReadableByActor($models, $actor) {
		// Everyone can view
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Everyone can modify
		return CerberusContexts::allowEverything($models);
	}
	
	function getRandom() {
		return DAO_JiraProject::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=jira_project&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_JiraProject();
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		);
		
		$properties['connected_account'] = array(
			'label' => mb_ucfirst($translate->_('common.connected_account')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->connected_account_id,
			'params' => [
				'context' => Context_ConnectedAccount::ID,
			],
		);
		
		$properties['last_synced_at'] = array(
			'label' => mb_ucfirst($translate->_('dao.jira_project.last_synced_at')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->last_synced_at,
		);
		
		$properties['url'] = array(
			'label' => mb_ucfirst($translate->_('common.url')),
			'type' => Model_CustomField::TYPE_URL,
			'value' => $model->url,
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		$jira_project = DAO_JiraProject::get($context_id);
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($jira_project->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $jira_project->id,
			'name' => $jira_project->name,
			'permalink' => $url,
			'updated' => 0, // [TODO]
		);
	}
	
	function getPropertyLabels(DevblocksDictionaryDelegate $dict) {
		$labels = $dict->_labels;
		$prefix = $labels['_label'];
		
		if(!empty($prefix)) {
			array_walk($labels, function(&$label, $key) use ($prefix) {
				$label = preg_replace(sprintf("#^%s #", preg_quote($prefix)), '', $label);
				
				switch($key) {
				}
				
				$label = mb_convert_case($label, MB_CASE_LOWER);
				$label[0] = mb_convert_case($label[0], MB_CASE_UPPER);
			});
		}
		
		asort($labels);
		
		return $labels;
	}
	
	function getDefaultProperties() {
		return array(
			'jira_key',
			'connected_account__label',
			'last_checked_at',
			'last_synced_at',
			'url',
		);
	}
	
	function getContext($jira_project, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Jira Project:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(Context_JiraProject::ID);

		// Polymorph
		if(is_numeric($jira_project)) {
			$jira_project = DAO_JiraProject::get($jira_project);
		} elseif($jira_project instanceof Model_JiraProject) {
			// It's what we want already.
		} elseif(is_array($jira_project)) {
			$jira_project = Cerb_ORMHelper::recastArrayToModel($jira_project, 'Model_JiraProject');
		} else {
			$jira_project = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'jira_key' => $prefix.$translate->_('dao.jira_project.jira_key'),
			'last_checked_at' => $prefix.$translate->_('dao.jira_project.last_checked_at'),
			'last_synced_at' => $prefix.$translate->_('dao.jira_project.last_synced_at'),
			'name' => $prefix.$translate->_('common.name'),
			'record_url' => $prefix.$translate->_('common.url.record'),
			'url' => $prefix.$translate->_('common.url'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'jira_key' => Model_CustomField::TYPE_SINGLE_LINE,
			'last_checked_at' => Model_CustomField::TYPE_DATE,
			'last_synced_at' => Model_CustomField::TYPE_DATE,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'record_url' => Model_CustomField::TYPE_URL,
			'url' => Model_CustomField::TYPE_URL,
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = [];
		
		$token_values['_context'] = Context_JiraProject::ID;
		$token_values['_types'] = $token_types;
		
		if($jira_project) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $jira_project->name;
			$token_values['connected_account_id'] = $jira_project->connected_account_id;
			$token_values['id'] = $jira_project->id;
			$token_values['jira_key'] = $jira_project->jira_key;
			$token_values['last_checked_at'] = $jira_project->last_checked_at;
			$token_values['last_synced_at'] = $jira_project->last_synced_at;
			$token_values['name'] = $jira_project->name;
			$token_values['url'] = $jira_project->url;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($jira_project, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=jira_project&id=%d-%s",$jira_project->id, DevblocksPlatform::strToPermalink($jira_project->name)), true);
		}
		
		// Connected account
		$merge_token_labels = $merge_token_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, null, $merge_token_labels, $merge_token_values, '', true);

			CerberusContexts::merge(
				'connected_account_',
				$prefix.'Connected Account:',
				$merge_token_labels,
				$merge_token_values,
				$token_labels,
				$token_values
			);
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'connected_account_id' => DAO_JiraProject::CONNECTED_ACCOUNT_ID,
			'id' => DAO_JiraProject::ID,
			'jira_key' => DAO_JiraProject::JIRA_KEY,
			'last_checked_at' => DAO_JiraProject::LAST_CHECKED_AT,
			'last_synced_at' => DAO_JiraProject::LAST_SYNCED_AT,
			'links' => '_links',
			'name' => DAO_JiraProject::NAME,
			'url' => DAO_JiraProject::URL,
		];
	}
	
	function getKeyMeta() {
		$keys = parent::getKeyMeta();
		
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		switch(DevblocksPlatform::strLower($key)) {
		}
		
		return true;
	}
	
	function lazyLoadGetKeys() {
		$lazy_keys = parent::lazyLoadGetKeys();
		return $lazy_keys;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = Context_JiraProject::ID;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = [];
		
		if(!$is_loaded) {
			$labels = [];
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
			default:
				$defaults = $this->_lazyLoadDefaults($token, $context, $context_id);
				$values = array_merge($values, $defaults);
				break;
		}
		
		return $values;
	}

	function getChooserView($view_id=null) {
		if(empty($view_id))
			$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
	
		// View
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Jira Project';
		$view->renderSortBy = SearchFields_JiraProject::LAST_CHECKED_AT;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=[], $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Jira Project';
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_JiraProject::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);
		
		$context = 'cerberusweb.contexts.jira.project';
		
		if(!empty($context_id)) {
			$model = DAO_JiraProject::get($context_id);
		}
		
		if(empty($context_id) || $edit) {
			if(isset($model))
				$tpl->assign('model', $model);
			
			// Custom fields
			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);
	
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
			
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);
			
			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:wgm.jira::jira_project/peek_edit.tpl');
			
		} else {
			// Links
			$links = array(
				$context => array(
					$context_id => 
						DAO_ContextLink::getContextLinkCounts(
							$context,
							$context_id,
							[]
						),
				),
			);
			$tpl->assign('links', $links);
			
			// Timeline
			if($context_id) {
				$timeline_json = Page_Profiles::getTimelineJson(Extension_DevblocksContext::getTimelineComments($context, $context_id));
				$tpl->assign('timeline_json', $timeline_json);
			}

			// Context
			if(false == ($context_ext = Extension_DevblocksContext::get($context)))
				return;
			
			// Dictionary
			$labels = [];
			$values = [];
			CerberusContexts::getContext($context, $model, $labels, $values, '', true, false);
			$dict = DevblocksDictionaryDelegate::instance($values);
			$tpl->assign('dict', $dict);
			
			$properties = $context_ext->getCardProperties();
			$tpl->assign('properties', $properties);
			
			// Card search buttons
			$search_buttons = $context_ext->getCardSearchButtons($dict, []);
			$tpl->assign('search_buttons', $search_buttons);
			
			$tpl->display('devblocks:wgm.jira::jira_project/peek.tpl');
		}
	}
};