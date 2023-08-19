<?php
class DAO_WorkspaceList extends Cerb_ORMHelper {
	const COLUMNS_JSON = 'columns_json';
	const CONTEXT = 'context';
	const ID = 'id';
	const NAME = 'name';
	const OPTIONS_JSON = 'options_json';
	const PARAMS_EDITABLE_JSON = 'params_editable_json';
	const PARAMS_REQUIRED_JSON = 'params_required_json';
	const PARAMS_REQUIRED_QUERY = 'params_required_query';
	const RENDER_LIMIT = 'render_limit';
	const RENDER_SUBTOTALS = 'render_subtotals';
	const RENDER_SORT_JSON = 'render_sort_json';
	const UPDATED_AT = 'updated_at';
	const WORKSPACE_TAB_ID = 'workspace_tab_id';
	const WORKSPACE_TAB_POS = 'workspace_tab_pos';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// text
		$validation
			->addField(self::COLUMNS_JSON)
			->string()
			->setMaxLength(16777215)
			;
		// varchar(255)
		$validation
			->addField(self::CONTEXT)
			->context()
			->setRequired(true)
			;
		// int(10) unsigned
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// varchar(255)
		$validation
			->addField(self::NAME)
			->string()
			->setRequired(true)
			;
		// text
		$validation
			->addField(self::OPTIONS_JSON)
			->string()
			->setMaxLength(16777215)
			;
		// text
		$validation
			->addField(self::PARAMS_EDITABLE_JSON)
			->string()
			->setMaxLength(16777215)
			;
		// text
		$validation
			->addField(self::PARAMS_REQUIRED_JSON)
			->string()
			->setMaxLength(16777215)
			;
		// text
		$validation
			->addField(self::PARAMS_REQUIRED_QUERY)
			->string()
			->setMaxLength(65536)
			;
		// smallint(5) unsigned
		$validation
			->addField(self::RENDER_LIMIT)
			->uint(2)
			->setMin(1)
			->setMax(250)
			;
		// varchar(255)
		$validation
			->addField(self::RENDER_SUBTOTALS)
			->string()
			;
		// varchar(255)
		$validation
			->addField(self::RENDER_SORT_JSON)
			->string()
			;
		// int(10) unsigned
		$validation
			->addField(self::UPDATED_AT)
			->timestamp()
			;
		// int(10) unsigned
		$validation
			->addField(self::WORKSPACE_TAB_ID)
			->id()
			->setRequired(true)
			->addValidator($validation->validators()->contextId(Context_WorkspaceTab::ID))
			;
		// smallint(5) unsigned
		$validation
			->addField(self::WORKSPACE_TAB_POS)
			->uint(2)
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
	
	static function create($fields) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("INSERT INTO workspace_list () ".
			"VALUES ()"
		);
		if(false == ($db->ExecuteMaster($sql)))
			return false;
		
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations(CerberusContexts::CONTEXT_WORKSPACE_WORKLIST, $id);

		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
			
		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
			
		$context = CerberusContexts::CONTEXT_WORKSPACE_WORKLIST;
		self::_updateAbstract($context, $ids, $fields);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
				
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges($context, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'workspace_list', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.workspace_list.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged($context, $batch_ids);
			}
		}
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('workspace_list', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_WORKSPACE_WORKLIST;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		if(!$id && !isset($fields[self::WORKSPACE_TAB_ID])) {
			$error = "A 'workspace_tab_id' is required.";
			return false;
		}
		
		if(isset($fields[self::WORKSPACE_TAB_ID])) {
			@$tab_id = $fields[self::WORKSPACE_TAB_ID];
			
			if(!$tab_id) {
				$error = "Invalid 'workspace_tab_id' value.";
				return false;
			}
			
			if(!Context_WorkspaceTab::isWriteableByActor($tab_id, $actor)) {
				$error = "You do not have permission to create worklists on this workspace tab.";
				return false;
			}
		}
		
		return true;
	}
	
	static function onUpdateByActor($actor, $fields, $id) {
		DAO_WorkerViewModel::updateFromWorkspaceList($id);
	}
	
	/**
	 *
	 * @param integer $id
	 * @return Model_WorkspaceList
	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
			
		return null;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_WorkspaceList[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();
		
		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, workspace_tab_pos, workspace_tab_id, context, options_json, columns_json, params_editable_json, params_required_json, params_required_query, render_limit, render_subtotals, render_sort_json, updated_at ".
			"FROM workspace_list ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		
		if($options & Cerb_ORMHelper::OPT_GET_MASTER_ONLY) {
			$rs = $db->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		} else {
			$rs = $db->QueryReader($sql);
		}
		
		return self::_getObjectsFromResult($rs);
	}
	
	/**
	 * 
	 * @param array $ids
	 * @return Model_WorkspaceList[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}
	
	static function getByTab($tab_id) {
		// [TODO] Use the cache and not a query
		return DAO_WorkspaceList::getWhere(
			sprintf("%s = %d",
				DAO_WorkspaceList::WORKSPACE_TAB_ID,
				$tab_id
			),
			DAO_WorkspaceList::WORKSPACE_TAB_POS
		);
	}
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_WorkspaceList[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_WorkspaceList();
			$object->columns = [];
			$object->context = $row['context'];
			$object->id = intval($row['id']);
			$object->name = $row['name'];
			$object->options = [];
			$object->params_editable = [];
			$object->params_required = [];
			$object->params_required_query = $row['params_required_query'];
			$object->render_limit = intval($row['render_limit']) ?: 10;
			$object->render_sort = [];
			$object->render_subtotals = $row['render_subtotals'];
			$object->updated_at = intval($row['updated_at']);
			$object->workspace_tab_id = intval($row['workspace_tab_id']);
			$object->workspace_tab_pos = $row['workspace_tab_pos'];
			
			if(($columns_json = json_decode($row['columns_json'] ?? '', true)))
				$object->columns = $columns_json;
			
			if(($options_json = json_decode($row['options_json'] ?? '', true)))
				$object->options = $options_json;
			
			if(($params_editable_json = json_decode($row['params_editable_json'] ?? '', true)))
				$object->params_editable = $params_editable_json;
			
			if(($params_required_json = json_decode($row['params_required_json'] ?? '', true)))
				$object->params_required = $params_required_json;
			
			if(($render_sort_json = json_decode($row['render_sort_json'] ?? '', true)))
				$object->render_sort = $render_sort_json;
			
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('workspace_list');
	}
	
	static function countByWorkspaceTabId($tab_id) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("SELECT count(workspace_tab_id) FROM workspace_list WHERE workspace_tab_id = %d",
			$tab_id
		);
		return intval($db->GetOneReader($sql));
	}
	
	static function delete($ids) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		if(empty($ids)) return false;
		
		$context = CerberusContexts::CONTEXT_WORKSPACE_WORKLIST;
		$ids_list = implode(',', self::qstrArray($ids));
		
		parent::_deleteAbstractBefore($context, $ids);

		if(!($db->ExecuteMaster(sprintf("DELETE FROM workspace_list WHERE id IN (%s)", $ids_list))))
			return false;
		
		// Delete worker view prefs
		foreach($ids as $id) {
			$db->ExecuteMaster(sprintf("DELETE FROM worker_view_model WHERE view_id = 'cust_%d'", $id));
		}
		
		parent::_deleteAbstractAfter($context, $ids);
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_WorkspaceList::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_WorkspaceList', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"workspace_list.id as %s, ".
			"workspace_list.name as %s, ".
			"workspace_list.options_json as %s, ".
			"workspace_list.columns_json as %s, ".
			"workspace_list.params_editable_json as %s, ".
			"workspace_list.params_required_json as %s, ".
			"workspace_list.params_required_query as %s, ".
			"workspace_list.render_limit as %s, ".
			"workspace_list.render_subtotals as %s, ".
			"workspace_list.render_sort_json as %s, ".
			"workspace_list.workspace_tab_pos as %s, ".
			"workspace_list.workspace_tab_id as %s, ".
			"workspace_list.context as %s, ".
			"workspace_list.updated_at as %s ",
				SearchFields_WorkspaceList::ID,
				SearchFields_WorkspaceList::NAME,
				SearchFields_WorkspaceList::OPTIONS_JSON,
				SearchFields_WorkspaceList::COLUMNS_JSON,
				SearchFields_WorkspaceList::PARAMS_EDITABLE_JSON,
				SearchFields_WorkspaceList::PARAMS_REQUIRED_JSON,
				SearchFields_WorkspaceList::PARAMS_REQUIRED_QUERY,
				SearchFields_WorkspaceList::RENDER_LIMIT,
				SearchFields_WorkspaceList::RENDER_SUBTOTALS,
				SearchFields_WorkspaceList::RENDER_SORT_JSON,
				SearchFields_WorkspaceList::WORKSPACE_TAB_POS,
				SearchFields_WorkspaceList::WORKSPACE_TAB_ID,
				SearchFields_WorkspaceList::CONTEXT,
				SearchFields_WorkspaceList::UPDATED_AT
			);
			
		$join_sql = "FROM workspace_list ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_WorkspaceList');
	
		return array(
			'primary_table' => 'workspace_list',
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
			SearchFields_WorkspaceList::ID,
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

class SearchFields_WorkspaceList extends DevblocksSearchFields {
	const COLUMNS_HIDDEN_JSON = 'w_columns_hidden_json';
	const CONTEXT = 'w_context';
	const ID = 'w_id';
	const NAME = 'w_name';
	const OPTIONS_JSON = 'w_options_json';
	const COLUMNS_JSON = 'w_columns_json';
	const PARAMS_EDITABLE_JSON = 'w_params_editable_json';
	const PARAMS_REQUIRED_JSON = 'w_params_required_json';
	const PARAMS_REQUIRED_QUERY = 'w_params_required_query';
	const RENDER_LIMIT = 'w_render_limit';
	const RENDER_SUBTOTALS = 'w_render_subtotals';
	const RENDER_SORT_JSON = 'w_render_sort_json';
	const UPDATED_AT = 'w_updated_at';
	const WORKSPACE_TAB_ID = 'w_workspace_tab_id';
	const WORKSPACE_TAB_POS = 'w_workspace_tab_pos';

	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_TAB_SEARCH = '*_tab_search';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'workspace_list.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_WORKSPACE_WORKLIST => new DevblocksSearchFieldContextKeys('workspace_list.id', self::ID),
			CerberusContexts::CONTEXT_WORKSPACE_TAB => new DevblocksSearchFieldContextKeys('workspace_list.workspace_tab_id', self::WORKSPACE_TAB_ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_WORKSPACE_WORKLIST, self::getPrimaryKey());
				
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_WORKSPACE_WORKLIST), '%s'), self::getPrimaryKey());
			
			case self::VIRTUAL_TAB_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_WORKSPACE_TAB, 'workspace_list.workspace_tab_id');
				
			default:
				if(DevblocksPlatform::strStartsWith($param->field, 'cf_')) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
		}
	}
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		switch($key) {
			case 'tab':
				$key = 'tab.id';
				break;
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_WorkspaceList::CONTEXT:
				return parent::_getLabelsForKeyContextValues();
				
			case SearchFields_WorkspaceList::ID:
				$models = DAO_WorkspaceList::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				
			case SearchFields_WorkspaceList::WORKSPACE_TAB_ID:
				$models = DAO_WorkspaceTab::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
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
			self::COLUMNS_HIDDEN_JSON => new DevblocksSearchField(self::COLUMNS_HIDDEN_JSON, 'workspace_list', 'columns_hidden_json', $translate->_(''), null, true),
			self::CONTEXT => new DevblocksSearchField(self::CONTEXT, 'workspace_list', 'context', $translate->_('common.type'), null, true),
			self::ID => new DevblocksSearchField(self::ID, 'workspace_list', 'id', $translate->_('common.id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'workspace_list', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::OPTIONS_JSON => new DevblocksSearchField(self::OPTIONS_JSON, 'workspace_list', 'options_json', $translate->_(''), null, true),
			self::PARAMS_EDITABLE_JSON => new DevblocksSearchField(self::PARAMS_EDITABLE_JSON, 'workspace_list', 'params_editable_json', $translate->_(''), null, true),
			self::PARAMS_REQUIRED_JSON => new DevblocksSearchField(self::PARAMS_REQUIRED_JSON, 'workspace_list', 'params_required_json', $translate->_(''), null, true),
			self::PARAMS_REQUIRED_QUERY => new DevblocksSearchField(self::PARAMS_REQUIRED_QUERY, 'workspace_list', 'params_required_query', $translate->_(''), null, true),
			self::RENDER_LIMIT => new DevblocksSearchField(self::RENDER_LIMIT, 'workspace_list', 'render_limit', $translate->_(''), Model_CustomField::TYPE_NUMBER, true),
			self::RENDER_SORT_JSON => new DevblocksSearchField(self::RENDER_SORT_JSON, 'workspace_list', 'render_sort_json', $translate->_(''), null, true),
			self::RENDER_SUBTOTALS => new DevblocksSearchField(self::RENDER_SUBTOTALS, 'workspace_list', 'render_subtotals', $translate->_(''), Model_CustomField::TYPE_NUMBER, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'workspace_list', 'updated_at', $translate->_('common.updated'), null, true),
			self::WORKSPACE_TAB_ID => new DevblocksSearchField(self::WORKSPACE_TAB_ID, 'workspace_list', 'workspace_tab_id', $translate->_('common.workspace.tab'), null, true),
			self::WORKSPACE_TAB_POS => new DevblocksSearchField(self::WORKSPACE_TAB_POS, 'workspace_list', 'workspace_tab_pos', $translate->_('common.order'), Model_CustomField::TYPE_NUMBER, true),

			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_TAB_SEARCH => new DevblocksSearchField(self::VIRTUAL_TAB_SEARCH, '*', 'tab_search', null, null, false),
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

class Model_WorkspaceList extends DevblocksRecordModel {
	public $columns = [];
	public $context = '';
	public $id = 0;
	public $name = '';
	public $options = [];
	public $params_editable = [];
	public $params_required = [];
	public $params_required_query = '';
	public $render_limit = 0;
	public $render_sort = [];
	public $render_subtotals = '';
	public $updated_at = 0;
	public $workspace_tab_id = 0;
	public $workspace_tab_pos = 0;
	
	function getParamsEditable() {
		return C4_AbstractViewLoader::convertParamsJsonToObject($this->params_editable);
	}
	
	function getParamsRequired() {
		return C4_AbstractViewLoader::convertParamsJsonToObject($this->params_required);
	}
	
	function getWorkspaceTab() {
		return DAO_WorkspaceTab::get($this->workspace_tab_id);
	}
	
	function getWorkspacePage() {
		if(false == ($tab = $this->getWorkspaceTab()))
			return;
		
		return $tab->getWorkspacePage();
	}
};

class View_WorkspaceList extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'workspace_lists';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('common.workspace.worklists');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_WorkspaceList::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_WorkspaceList::NAME,
			SearchFields_WorkspaceList::CONTEXT,
			SearchFields_WorkspaceList::WORKSPACE_TAB_ID,
			SearchFields_WorkspaceList::UPDATED_AT,
		);

		$this->addColumnsHidden(array(
			SearchFields_WorkspaceList::VIRTUAL_CONTEXT_LINK,
			SearchFields_WorkspaceList::VIRTUAL_HAS_FIELDSET,
			SearchFields_WorkspaceList::VIRTUAL_TAB_SEARCH,
		));
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_WorkspaceList::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_WorkspaceList');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null, &$total=null) {
		return $this->_getDataAsObjects('DAO_WorkspaceList', $ids, $total);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_WorkspaceList', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
				case SearchFields_WorkspaceList::CONTEXT:
				case SearchFields_WorkspaceList::WORKSPACE_TAB_ID:
					$pass = true;
					break;
					
				// Virtuals
				case SearchFields_WorkspaceList::VIRTUAL_CONTEXT_LINK:
				case SearchFields_WorkspaceList::VIRTUAL_HAS_FIELDSET:
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
		$context = CerberusContexts::CONTEXT_WORKSPACE_WORKLIST;

		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_WorkspaceList::CONTEXT:
			case SearchFields_WorkspaceList::WORKSPACE_TAB_ID:
				$label_map = function(array $values) use ($column) {
					return SearchFields_WorkspaceList::getLabelsForKeyValues($column, $values);
				};
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map);
				break;
				
			case SearchFields_WorkspaceList::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;

			case SearchFields_WorkspaceList::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
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
		$search_fields = SearchFields_WorkspaceList::getFields();
	
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_WorkspaceList::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_WorkspaceList::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_WORKSPACE_WORKLIST],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_WorkspaceList::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_WORKSPACE_WORKLIST, 'q' => ''],
					]
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_WorkspaceList::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'tab' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => ['param_key' => SearchFields_WorkspaceList::VIRTUAL_TAB_SEARCH],
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_WORKSPACE_TAB, 'q' => ''],
					]
				],
			'tab.pos' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_WorkspaceList::WORKSPACE_TAB_POS),
				),
			'tab.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_WorkspaceList::WORKSPACE_TAB_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_WORKSPACE_TAB, 'q' => 'type:"core.workspace.tab.worklists"'],
					]
				),
			'type' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_WorkspaceList::CONTEXT, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_WorkspaceList::UPDATED_AT),
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_WorkspaceList::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_WORKSPACE_WORKLIST, $fields, null);
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		ksort($fields);
		
		return $fields;
	}	
	
	// [TODO] Implement quick search fields
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'fieldset':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, '*_has_fieldset');
			
			case 'tab':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_WorkspaceList::VIRTUAL_TAB_SEARCH);
				
			default:
				if($field == 'links' || substr($field, 0, 6) == 'links.')
					return DevblocksSearchCriteria::getContextLinksParamFromTokens($field, $tokens);
				
				$search_fields = $this->getQuickSearchFields();
				return DevblocksSearchCriteria::getParamFromQueryFieldTokens($field, $tokens, $search_fields);
		}
	}
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_WORKSPACE_WORKLIST);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/workspaces/worklists/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_WorkspaceList::CONTEXT:
			case SearchFields_WorkspaceList::WORKSPACE_TAB_ID:
				$label_map = SearchFields_WorkspaceList::getLabelsForKeyValues($field, $values);
				parent::_renderCriteriaParamString($param, $label_map);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_WorkspaceList::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_WorkspaceList::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
			
			case SearchFields_WorkspaceList::VIRTUAL_TAB_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.tab')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
		}
	}

	function getFields() {
		return SearchFields_WorkspaceList::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_WorkspaceList::COLUMNS_HIDDEN_JSON:
			case SearchFields_WorkspaceList::CONTEXT:
			case SearchFields_WorkspaceList::NAME:
			case SearchFields_WorkspaceList::OPTIONS_JSON:
			case SearchFields_WorkspaceList::PARAMS_EDITABLE_JSON:
			case SearchFields_WorkspaceList::PARAMS_REQUIRED_JSON:
			case SearchFields_WorkspaceList::PARAMS_REQUIRED_QUERY:
			case SearchFields_WorkspaceList::RENDER_SORT_JSON:
			case SearchFields_WorkspaceList::RENDER_SUBTOTALS:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_WorkspaceList::ID:
			case SearchFields_WorkspaceList::RENDER_LIMIT:
			case SearchFields_WorkspaceList::WORKSPACE_TAB_ID:
			case SearchFields_WorkspaceList::WORKSPACE_TAB_POS:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_WorkspaceList::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_WorkspaceList::VIRTUAL_CONTEXT_LINK:
				$context_links = DevblocksPlatform::importGPC($_POST['context_link'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_WorkspaceList::VIRTUAL_HAS_FIELDSET:
				$options = DevblocksPlatform::importGPC($_POST['options'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
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

class Context_WorkspaceList extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	const ID = CerberusContexts::CONTEXT_WORKSPACE_WORKLIST;
	const URI = 'workspace_list';
	
	static function isReadableByActor($models, $actor) {
		return CerberusContexts::isReadableByDelegateOwner($actor, CerberusContexts::CONTEXT_WORKSPACE_WORKLIST, $models, 'tab_page_owner_');
	}
	
	static function isWriteableByActor($models, $actor) {
		return CerberusContexts::isWriteableByDelegateOwner($actor, CerberusContexts::CONTEXT_WORKSPACE_WORKLIST, $models, 'tab_page_owner_');
	}
	
	static function isDeletableByActor($models, $actor) {
		return self::isWriteableByActor($models, $actor);
	}
	
	function getRandom() {
		return DAO_WorkspaceList::random();
	}
	
	function getDaoClass() {
		return 'DAO_WorkspaceList';
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=workspace_list&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_WorkspaceList();
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		);
		
		$properties['context'] = array(
			'label' => mb_ucfirst($translate->_('common.type')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->context,
		);
		
		$properties['context'] = array(
			'label' => mb_ucfirst($translate->_('common.tab')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->workspace_tab_id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_WORKSPACE_TAB,
			],
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		);
		
		$properties['id'] = array(
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		if(null == ($workspace_list = DAO_WorkspaceList::get($context_id)))
			return [];
		
		$url = $this->profileGetUrl($context_id);

		return [
			'id' => $workspace_list->id,
			'name' => $workspace_list->name,
			'permalink' => $url,
			'updated' => $workspace_list->updated_at,
		];
	}
	
	function getDefaultProperties() {
		return [
			'context',
			'tab_page__label',
			'tab__label',
			'tab_page_owner__label',
			'updated_at',
		];
	}
	
	function getContext($worklist, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Workspace Worklist:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_WORKSPACE_WORKLIST);
		
		// Polymorph
		if(is_numeric($worklist)) {
			$worklist = DAO_WorkspaceList::get($worklist);
		} elseif($worklist instanceof Model_WorkspaceList) {
			// It's what we want already.
		} elseif(is_array($worklist)) {
			$worklist = Cerb_ORMHelper::recastArrayToModel($worklist, 'Model_WorkspaceList');
		} else {
			$worklist = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'columns' => $prefix.$translate->_('common.columns'),
			'context' => $prefix.$translate->_('common.context'),
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'options' => $prefix.$translate->_('common.options'),
			'params' => $prefix.$translate->_('common.params'),
			'pos' => $prefix.$translate->_('common.order'),
			'record_url' => $prefix.$translate->_('common.url.record'),
			'render_limit' => $prefix.$translate->_('Render Limit'),
			'render_sort' => $prefix.$translate->_('common.sort'),
			'render_subtotals' => $prefix.$translate->_('common.subtotals'),
			'updated_at' => $prefix.$translate->_('common.updated'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'columns' => null,
			'context' => Model_CustomField::TYPE_SINGLE_LINE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'options' => null,
			'params' => null,
			'pos' => Model_CustomField::TYPE_NUMBER,
			'record_url' => Model_CustomField::TYPE_URL,
			'render_limit' => Model_CustomField::TYPE_NUMBER,
			'render_sort' => null,
			'render_subtotals' => Model_CustomField::TYPE_SINGLE_LINE,
			'updated_at' => Model_CustomField::TYPE_DATE,
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = [];
		
		$token_values['_context'] = Context_WorkspaceList::ID;
		$token_values['_type'] = Context_WorkspaceList::URI;
		
		$token_values['_types'] = $token_types;
		
		// Token values
		if(null != $worklist) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $worklist->name;
			$token_values['columns'] = $worklist->columns;
			$token_values['context'] = $worklist->context;
			$token_values['id'] = $worklist->id;
			$token_values['name'] = $worklist->name;
			$token_values['options'] = $worklist->options;
			$token_values['params'] = $worklist->params_editable;
			$token_values['pos'] = $worklist->workspace_tab_pos;
			$token_values['render_limit'] = $worklist->render_limit;
			$token_values['render_sort'] = $worklist->render_sort;
			$token_values['render_subtotals'] = $worklist->render_subtotals;
			$token_values['updated_at'] = $worklist->updated_at;
			
			$token_values['tab_id'] = $worklist->workspace_tab_id;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($worklist, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=workspace_list&id=%d-%s",$worklist->id, DevblocksPlatform::strToPermalink($worklist->name)), true);
		}
		
		// Tab
		$merge_token_labels = [];
		$merge_token_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKSPACE_TAB, null, $merge_token_labels, $merge_token_values, '', true);

		CerberusContexts::merge(
			'tab_',
			$prefix.'Tab:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'columns' => DAO_WorkspaceList::COLUMNS_JSON,
			'context' => DAO_WorkspaceList::CONTEXT,
			'id' => DAO_WorkspaceList::ID,
			'links' => '_links',
			'name' => DAO_WorkspaceList::NAME,
			'options' => DAO_WorkspaceList::OPTIONS_JSON,
			'params' => DAO_WorkspaceList::PARAMS_EDITABLE_JSON,
			'params_required' => DAO_WorkspaceList::PARAMS_REQUIRED_JSON,
			'params_required_query' => DAO_WorkspaceList::PARAMS_REQUIRED_QUERY,
			'pos' => DAO_WorkspaceList::WORKSPACE_TAB_POS,
			'render_limit' => DAO_WorkspaceList::RENDER_LIMIT,
			'render_sort' => DAO_WorkspaceList::RENDER_SORT_JSON,
			'render_subtotals' => DAO_WorkspaceList::RENDER_SUBTOTALS,
			'tab_id' => DAO_WorkspaceList::WORKSPACE_TAB_ID,
			'updated_at' => DAO_WorkspaceList::UPDATED_AT,
		];
	}
	
	function getKeyMeta($with_dao_fields=true) {
		$keys = parent::getKeyMeta($with_dao_fields);
		
		$keys['context']['notes'] = "The [record type](/docs/records/types/) of the worklist";
		$keys['params_required_query']['notes'] = "The [search query](/docs/search/) for required filters";
		$keys['pos']['notes'] = "The order of the worklist on the workspace tab; `0` is first";
		$keys['render_limit']['notes'] = "The number of records per page";
		$keys['tab_id']['notes'] = "The ID of the [workspace tab](/docs/records/types/workspace_tab/) containing this worklist";
		
		$keys['columns'] = [
			'key' => 'columns',
			'is_immutable' => false,
			'is_required' => false,
			'notes' => 'JSON-encoded key/value array of column names',
			'type' => 'object',
		];
		
		$keys['options'] = [
			'key' => 'options',
			'is_immutable' => false,
			'is_required' => false,
			'notes' => 'JSON-encoded key/value object',
			'type' => 'object',
		];
		
		unset($keys['params']);
		unset($keys['params_required']);
		unset($keys['render_sort']);
		unset($keys['render_subtotals']);
		
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, $data, &$error) {
		$dict_key = DevblocksPlatform::strLower($key);
		
		switch($dict_key) {
			case 'columns':
				if(!is_array($value)) {
					$error = 'must be an object.';
					return false;
				}
				
				// [TODO] Validate the search fields by type
				$out_fields[DAO_WorkspaceList::COLUMNS_JSON] = json_encode($value);
				break;
			
				
			case 'options':
				if(!is_array($value)) {
					$error = 'must be an object.';
					return false;
				}
				
				$out_fields[DAO_WorkspaceList::OPTIONS_JSON] = json_encode($value);
				break;
				
			case 'params':
				if(!is_array($value)) {
					$error = 'must be an object.';
					return false;
				}
				
				$out_fields[DAO_WorkspaceList::PARAMS_EDITABLE_JSON] = json_encode($value);
				break;
				
			case 'params_required':
				if(!is_array($value)) {
					$error = 'must be an object.';
					return false;
				}
				
				$out_fields[DAO_WorkspaceList::PARAMS_REQUIRED_JSON] = json_encode($value);
				break;
			
			case 'render_sort':
				if(!is_array($value)) {
					$error = 'must be an object.';
					return false;
				}
				
				// Make sure the array is boolean formatted
				$value = DevblocksPlatform::sanitizeArray($value, 'boolean');
				
				$out_fields[DAO_WorkspaceList::RENDER_SORT_JSON] = json_encode($value);
				break;
				
			// Backwards compatibility with <= 8.2 packages
			case 'view':
				if(!is_array($value)) {
					$error = 'must be an object.';
					return false;
				}
				
				if(!isset($value['title'])) {
					$error = "is missing the 'title' key.";
					return false;
				}
				
				if(!isset($value['model'])) {
					$error = "is missing the 'model' key.";
					return false;
				}
				
				if(false == (@$view = C4_AbstractViewLoader::unserializeViewFromAbstractJson($value['model'], ''))) {
					$error = 'is not a valid worklist.';
					return false;
				}
				
				$out_fields[DAO_WorkspaceList::NAME] = $value['title'];
				$out_fields[DAO_WorkspaceList::OPTIONS_JSON] = json_encode($view->options);
				$out_fields[DAO_WorkspaceList::COLUMNS_JSON] = json_encode($view->view_columns);
				$out_fields[DAO_WorkspaceList::PARAMS_EDITABLE_JSON] = json_encode($view->getEditableParams());
				$out_fields[DAO_WorkspaceList::PARAMS_REQUIRED_JSON] = json_encode($view->getParamsRequired());
				$out_fields[DAO_WorkspaceList::PARAMS_REQUIRED_QUERY] = $view->getParamsRequiredQuery();
				$out_fields[DAO_WorkspaceList::RENDER_LIMIT] = $view->renderLimit;
				$out_fields[DAO_WorkspaceList::RENDER_SORT_JSON] = json_encode($view->getSorts());
				$out_fields[DAO_WorkspaceList::RENDER_SUBTOTALS] = $view->renderSubtotals;
				break;
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
		
		$context = CerberusContexts::CONTEXT_WORKSPACE_WORKLIST;
		$context_id = $dictionary['id'];
		
		$is_loaded = $dictionary['_loaded'] ?? false;
		$values = [];
		
		if(!$is_loaded) {
			$labels = [];
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, false);
		}
		
		switch($token) {
			case 'data':
				$values = $dictionary;
				break;
			
			default:
				$defaults = $this->_lazyLoadDefaults($token, $dictionary);
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
		$view->name = DevblocksPlatform::translateCapitalized('common.workspace.worklists');
		/*
		$view->addParams(array(
			SearchFields_WorkspaceList::UPDATED_AT => new DevblocksSearchCriteria(SearchFields_WorkspaceList::UPDATED_AT,'=',0),
		), true);
		*/
		$view->renderSortBy = SearchFields_WorkspaceList::UPDATED_AT;
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
		$view->name = DevblocksPlatform::translateCapitalized('common.workspace.worklists');
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_WorkspaceList::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = CerberusContexts::CONTEXT_WORKSPACE_WORKLIST;
		
		$tpl->assign('view_id', $view_id);
		
		if($context_id) {
			if(false == ($model = DAO_WorkspaceList::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
		} else {
			$model = new Model_WorkspaceList();
		}
		
		if(empty($context_id) || $edit) {
			if($model && $model->id) {
				if(!Context_WorkspaceList::isWriteableByActor($model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
			}
			
			$tpl->assign('model', $model);
			
			// Contexts
			$contexts = array_map(
				function($manifest) {
					$aliases = Extension_DevblocksContext::getAliasesForContext($manifest);
					$plural = @$aliases['plural'] ?: $aliases['singular'];
					return DevblocksPlatform::strUpperFirst($plural);
				},
				Extension_DevblocksContext::getAll(false, ['workspace'])
			);
			asort($contexts);
			$tpl->assign('contexts', $contexts);
			
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
			$tpl->display('devblocks:cerberusweb.core::internal/workspaces/worklists/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};