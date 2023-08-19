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
/*
 * IMPORTANT LICENSING NOTE from your friends at Cerb
 *
 * Sure, it would be really easy to just cheat and edit this file to use
 * Cerb without paying for a license.  We trust you anyway.
 *
 * It takes a significant amount of time and money to develop, maintain,
 * and support high-quality enterprise software with a dedicated team.
 * For Cerb's entire history we've avoided taking money from outside
 * investors, and instead we've relied on actual sales from satisfied
 * customers to keep the project running.
 *
 * We've never believed in hiding our source code out of paranoia over not
 * getting paid.  We want you to have the full source code and be able to
 * make the tweaks your organization requires to get more done -- despite
 * having less of everything than you might need (time, people, money,
 * energy).  We shouldn't be your bottleneck.
 *
 * As a legitimate license owner, your feedback will help steer the project.
 * We'll also prioritize your issues, and work closely with you to make sure
 * your teams' needs are being met.
 *
 * - Jeff Standen and Dan Hildebrandt
 *	 Founders at Webgroup Media LLC; Developers of Cerb
 */

class DAO_DevblocksSession extends Cerb_ORMHelper {
	const CREATED = 'created';
	const SESSION_DATA = 'session_data';
	const SESSION_ID = 'session_id';
	const UPDATED = 'updated';
	const USER_AGENT = 'user_agent';
	const USER_ID = 'user_id';
	const USER_IP = 'user_ip';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::CREATED)
			->timestamp()
			;
		$validation
			->addField(self::SESSION_DATA)
			->string()
			->setMaxLength(65535)
			;
		$validation
			->addField(self::SESSION_ID)
			->string()
			->setMaxLength(40)
			;
		$validation
			->addField(self::UPDATED)
			->timestamp()
			;
		$validation
			->addField(self::USER_AGENT)
			->string()
			;
		$validation
			->addField(self::USER_ID)
			->id()
			;
		$validation
			->addField(self::USER_IP)
			->string()
			->setMaxLength(32)
			;
			
		return $validation->getFields();
	}

	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = "INSERT INTO devblocks_session () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		if(!is_array($ids)) $ids = [$ids];
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
			
			// Make changes
			parent::_update($batch_ids, 'devblocks_session', $fields, 'session_id');
		}
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('devblocks_session', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_DevblocksSession[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT session_id, created, updated, session_data, user_id, user_ip, user_agent ".
			"FROM devblocks_session ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->QueryReader($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param string $id
	 * @return Model_DevblocksSession
	 */
	static function get($id) {
		$db = DevblocksPlatform::services()->database();
		
		$objects = self::getWhere(sprintf("%s = %s",
			self::SESSION_ID,
			$db->qstr($id)
		));
		
		if(array_key_exists($id, $objects))
			return $objects[$id];
		
		return null;
	}
	
	static function getByUserId($user_id) {
		return self::getWhere(
			sprintf("%s = %d",
				self::escape(DAO_DevblocksSession::USER_ID),
				$user_id
			),
			DAO_DevblocksSession::UPDATED,
			false,
			null
		);
	}
	
	static function getLatestByUserId($user_id) {
		$sessions = self::getByUserId($user_id);
		
		if(is_array($sessions) && !empty($sessions))
			return array_shift($sessions);
		
		return NULL;
	}
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_DevblocksSession[]|false
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_DevblocksSession();
			$object->session_id = $row['session_id'];
			$object->created = intval($row['created']);
			$object->updated = intval($row['updated']);
			$object->session_data = $row['session_data'];
			$object->user_id = intval($row['user_id']);
			$object->user_ip = $row['user_ip'];
			$object->user_agent = $row['user_agent'];
			$objects[$object->session_id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function delete($ids) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($ids)) $ids = [$ids];
		
		if(empty($ids)) return false;

		array_walk($ids, function(&$id) use ($db) {
			$id = $db->qstr($id);
		});
		
		$ids_list = implode(',', self::qstrArray($ids));
		
		$db->ExecuteMaster(sprintf("DELETE FROM devblocks_session WHERE session_id IN (%s)", $ids_list));
		
		return true;
	}
	
	static function deleteByUserIds($ids) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		if(empty($ids)) return false;
		
		$ids_list = implode(',', self::qstrArray($ids));
		
		$db->ExecuteMaster(sprintf("DELETE FROM devblocks_session WHERE user_id IN (%s)", $ids_list));
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_DevblocksSession::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_DevblocksSession', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"devblocks_session.session_id as %s, ".
			"devblocks_session.created as %s, ".
			"devblocks_session.updated as %s, ".
			"devblocks_session.session_data as %s, ".
			"devblocks_session.user_id as %s, ".
			"devblocks_session.user_ip as %s, ".
			"devblocks_session.user_agent as %s ",
				SearchFields_DevblocksSession::SESSION_ID,
				SearchFields_DevblocksSession::CREATED,
				SearchFields_DevblocksSession::UPDATED,
				SearchFields_DevblocksSession::SESSION_DATA,
				SearchFields_DevblocksSession::USER_ID,
				SearchFields_DevblocksSession::USER_IP,
				SearchFields_DevblocksSession::USER_AGENT
			);
			
		$join_sql = "FROM devblocks_session ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_DevblocksSession');
	
		return array(
			'primary_table' => 'devblocks_session',
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
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$sort_sql = $query_parts['sort'];
		
		return self::_searchWithTimeout(
			SearchFields_DevblocksSession::SESSION_ID,
			$select_sql,
			$join_sql,
			$where_sql,
			$sort_sql,
			$page,
			$limit,
			$withCounts
		);
	}
};

class Model_DevblocksSession {
	public $session_id;
	public $created;
	public $updated;
	public $session_data;
	public $user_id;
	public $user_ip;
	public $user_agent;
};

class SearchFields_DevblocksSession extends DevblocksSearchFields {
	const SESSION_ID = 'd_session_id';
	const CREATED = 'd_created';
	const UPDATED = 'd_updated';
	const SESSION_DATA = 'd_session_data';
	const USER_ID = 'd_user_id';
	const USER_IP = 'd_user_ip';
	const USER_AGENT = 'd_user_agent';
	
	const VIRTUAL_WORKER_SEARCH = '*_worker_search';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'devblocks_session.session_id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			'' => new DevblocksSearchFieldContextKeys('devblocks_session.session_id', self::SESSION_ID),
			CerberusContexts::CONTEXT_WORKER => new DevblocksSearchFieldContextKeys('devblocks_session.user_id', self::USER_ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		$field = $param->field;
		
		switch($field) {
			case SearchFields_DevblocksSession::VIRTUAL_WORKER_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_WORKER, 'devblocks_session.user_id');
				break;
				
			default:
				if('cf_' == substr($field, 0, 3)) {
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
			case SearchFields_DevblocksSession::ID:
				$models = DAO_DevblocksSession::getIds($values);
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
			self::SESSION_ID => new DevblocksSearchField(self::SESSION_ID, 'devblocks_session', 'session_id', $translate->_('common.id'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::CREATED => new DevblocksSearchField(self::CREATED, 'devblocks_session', 'created', $translate->_('common.created'), Model_CustomField::TYPE_DATE, true),
			self::UPDATED => new DevblocksSearchField(self::UPDATED, 'devblocks_session', 'updated', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),
			self::SESSION_DATA => new DevblocksSearchField(self::SESSION_DATA, 'devblocks_session', 'session_data', $translate->_('dao.devblocks_session.session_data'), Model_CustomField::TYPE_MULTI_LINE, true),
			self::USER_ID => new DevblocksSearchField(self::USER_ID, 'devblocks_session', 'user_id', $translate->_('common.worker'), Model_CustomField::TYPE_WORKER, true),
			self::USER_IP => new DevblocksSearchField(self::USER_IP, 'devblocks_session', 'user_ip', $translate->_('dao.devblocks_session.user_ip'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::USER_AGENT => new DevblocksSearchField(self::USER_AGENT, 'devblocks_session', 'user_agent', $translate->_('dao.devblocks_session.user_agent'), Model_CustomField::TYPE_SINGLE_LINE, true),
				
			self::VIRTUAL_WORKER_SEARCH => new DevblocksSearchField(self::VIRTUAL_WORKER_SEARCH, '*', 'worker_search', null, null, true),
		);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class View_DevblocksSession extends C4_AbstractView implements IAbstractView_QuickSearch { /* IAbstractView_Subtotals */
	const DEFAULT_ID = 'devblocks_sessions';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('Sessions');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_DevblocksSession::UPDATED;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_DevblocksSession::USER_ID,
			SearchFields_DevblocksSession::CREATED,
			SearchFields_DevblocksSession::UPDATED,
			SearchFields_DevblocksSession::USER_IP,
			SearchFields_DevblocksSession::USER_AGENT,
		);

		$this->addColumnsHidden(array(
			SearchFields_DevblocksSession::SESSION_ID,
			SearchFields_DevblocksSession::SESSION_DATA,
			SearchFields_DevblocksSession::VIRTUAL_WORKER_SEARCH,
		));
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_DevblocksSession::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
	}
	
	function getData() {
		$objects = $this->_getDataBoundedTimed();
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_DevblocksSession');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null, &$total=null) {
		return $this->_getDataAsObjects('DAO_DevblocksSession', $ids, $total);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_DevblocksSession', $size);
	}

	/*
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
				case SearchFields_DevblocksSession::USER_ID:
					$pass = true;
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
		$context = null;

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_DevblocksSession::USER_ID:
				$label_map = array();
				
				$workers = DAO_Worker::getAll();
				foreach($workers as $worker_id => $worker) {
					$label_map[$worker_id] = $worker->getName();
				}
				
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map, 'in', 'worker_id[]');
				break;
		}
		
		return $counts;
	}
	*/
	
	function getQuickSearchFields() {
		$search_fields = SearchFields_DevblocksSession::getFields();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_DevblocksSession::USER_AGENT, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'created' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_DevblocksSession::CREATED),
				),
			'ip' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_DevblocksSession::USER_IP, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_DevblocksSession::UPDATED),
				),
			'userAgent' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_DevblocksSession::USER_AGENT, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'worker' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_DevblocksSession::VIRTUAL_WORKER_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					]
				),
			'worker.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_DevblocksSession::USER_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					]
				),
		);
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		
		ksort($fields);
		
		return $fields;
	}
	
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'worker':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_DevblocksSession::VIRTUAL_WORKER_SEARCH);
				break;
			
			default:
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

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$tpl->assign('view_template', 'devblocks:cerberusweb.core::configuration/section/sessions/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteriaParam($param) {
		$field = $param->field;

		switch($field) {
			case SearchFields_DevblocksSession::USER_ID:
				parent::_renderCriteriaParamWorker($param);
				break;
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_DevblocksSession::VIRTUAL_WORKER_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.worker')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
		}
	}

	function getFields() {
		return SearchFields_DevblocksSession::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_DevblocksSession::SESSION_ID:
			case SearchFields_DevblocksSession::USER_IP:
			case SearchFields_DevblocksSession::USER_AGENT:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_DevblocksSession::CREATED:
			case SearchFields_DevblocksSession::UPDATED:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_DevblocksSession::USER_ID:
				$criteria = $this->_doSetCriteriaWorker($field, $oper);
		}

		if(!empty($criteria)) {
			$this->addParam($criteria, $field);
			$this->renderPage = 0;
		}
	}
};