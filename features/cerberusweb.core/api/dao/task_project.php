<?php
class DAO_TaskProject extends Cerb_ORMHelper {
	const string CREATED_AT = 'created_at';
	const string ID = 'id';
	const string IS_CLOSED = 'is_closed';
	const string NAME = 'name';
	const string OWNER_CONTEXT = 'owner_context';
	const string OWNER_CONTEXT_ID = 'owner_context_id';
	const string UPDATED_AT = 'updated_at';

	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();

		$validation
			->addField(self::CREATED_AT)
			->timestamp()
			;
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// Archived/closed projects are hidden from the board's project picker
		$validation
			->addField(self::IS_CLOSED)
			->bit()
			;
		$validation
			->addField(self::NAME)
			->string()
			->setRequired(true)
			;
		// Delegate owner (worker = private; group/role = shared); governs ACL
		$validation
			->addField(self::OWNER_CONTEXT)
			->context()
			->setRequired(true)
			;
		$validation
			->addField(self::OWNER_CONTEXT_ID)
			->id()
			->setRequired(true)
			;
		$validation
			->addField(self::UPDATED_AT)
			->timestamp()
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

		$sql = "INSERT INTO task_project () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();

		if(!isset($fields[self::CREATED_AT]))
			$fields[self::CREATED_AT] = time();

		// Default to a private (worker-owned) project when no owner is given
		if(!isset($fields[self::OWNER_CONTEXT]) && ($active_worker = CerberusApplication::getActiveWorker())) {
			$fields[self::OWNER_CONTEXT] = CerberusContexts::CONTEXT_WORKER;
			$fields[self::OWNER_CONTEXT_ID] = $active_worker->id;
		}

		CerberusContexts::checkpointCreations(Context_TaskProject::ID, $id);

		self::update($id, $fields);

		return $id;
	}

	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = [$ids];

		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();

		$context = Context_TaskProject::ID;
		self::_updateAbstract($context, $ids, $fields);

		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;

			if($check_deltas) {
				CerberusContexts::checkpointChanges($context, $batch_ids);
			}

			parent::_update($batch_ids, 'task_project', $fields);

			if($check_deltas) {
				DevblocksPlatform::markContextChanged($context, $batch_ids);
			}
		}
	}

	static function updateWhere($fields, $where) {
		parent::_updateWhere('task_project', $fields, $where);
	}

	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = Context_TaskProject::ID;

		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;

		@$owner_context = $fields[self::OWNER_CONTEXT] ?? null;
		@$owner_context_id = intval($fields[self::OWNER_CONTEXT_ID] ?? 0);

		// The actor must be able to assign this owner
		if($owner_context) {
			if(!CerberusContexts::isOwnableBy($owner_context, $owner_context_id, $actor)) {
				$error = DevblocksPlatform::translate('error.core.no_acl.owner');
				return false;
			}
		}

		return true;
	}

	/**
	 * @param string $where
	 * @return Model_TaskProject[]
	 */
	/**
	 * @param string $term the text typed into the chooser
	 * @param string $as 'models' (default) or 'ids'
	 * @param string|null $query the chooser's scope query, so a caller can narrow which projects it
	 *   offers (e.g. `owner.worker:(id:5)`). Applied first, then the always-on filters below.
	 */
	static function autocomplete($term, $as='models', $query=null) {
		$context_ext = Extension_DevblocksContext::get(Context_TaskProject::ID);

		$view = $context_ext->getSearchView('autocomplete_task_project');
		$view->is_ephemeral = true;
		$view->renderPage = 0;
		$view->renderLimit = 25;
		$view->renderSortBy = SearchFields_TaskProject::NAME;
		$view->renderSortAsc = true;
		$view->renderTotal = false;

		$view->addParamsWithQuickSearch($query, true);

		// Archived projects are hidden from the picker
		$view->addParamsWithQuickSearch('closed:n', false);

		// An empty term opens the picker on the first 25. The wildcards are explicit because a bound
		// value is forced to quoted text, which skips the `name:` field's own partial-match wrapping --
		// without them this would be an exact-name match.
		if(0 != strlen(strval($term)))
			$view->addParamsWithQuickSearch('name:${term}', false, ['term' => '*' . $term . '*']);

		list($results,) = $view->getData();

		return match ($as) {
			'ids' => array_keys($results),
			default => DAO_TaskProject::getIds(array_keys($results)),
		};
	}

	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);

		$sql = "SELECT created_at, id, is_closed, name, owner_context, owner_context_id, updated_at " .
			"FROM task_project " .
			$where_sql .
			$sort_sql .
			$limit_sql
		;

		if($options & DevblocksORMHelper::OPT_GET_MASTER_ONLY) {
			$rs = $db->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		} else {
			$rs = $db->QueryReader($sql);
		}

		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @return Model_TaskProject[]
	 */
	static function getAll($nocache=false) {
		$objects = self::getWhere(null, self::NAME, true, null, DevblocksORMHelper::OPT_GET_MASTER_ONLY);
		return $objects;
	}

	/**
	 * @param integer $id
	 * @return Model_TaskProject|null
	 */
	static function get($id) {
		if(empty($id))
			return null;

		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));

		if(array_key_exists($id, $objects))
			return $objects[$id];

		return null;
	}

	/**
	 * @param array $ids
	 * @return Model_TaskProject[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}

	/**
	 * @param mysqli_result|false $rs
	 * @return Model_TaskProject[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];

		if(!($rs instanceof mysqli_result))
			return [];

		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_TaskProject();
			$object->created_at = intval($row['created_at']);
			$object->id = intval($row['id']);
			$object->is_closed = intval($row['is_closed']);
			$object->name = $row['name'];
			$object->owner_context = $row['owner_context'];
			$object->owner_context_id = intval($row['owner_context_id']);
			$object->updated_at = intval($row['updated_at']);
			$objects[$object->id] = $object;
		}

		mysqli_free_result($rs);

		return $objects;
	}

	/**
	 * Batched task counts per project, bucketed to match the daily task board
	 * (todo / in-progress / waiting / done). Projects with no tasks are omitted;
	 * callers should default those to zeros.
	 *
	 * @param int[] $project_ids
	 * @return array<int,array{todo:int,inprogress:int,waiting:int,done:int,total:int}>
	 */
	public static function getTaskCountsForProjects(array $project_ids) : array {
		$db = DevblocksPlatform::services()->database();

		$project_ids = array_unique(array_filter(array_map('intval', $project_ids)));

		if(!$project_ids)
			return [];
		
		// Data query:
		// type:worklist.subtotals
		// of:tasks
		// by.count:[project~1000,status~1000,isActive~1000]
		// query:(project.id:[1,2,3])
		// format:dictionaries
		
		$rows = $db->GetArrayMaster(sprintf(
			"SELECT project_id, ".
			"COALESCE(SUM(IF(status_id=0 AND is_active=0,1,0)),0) AS todo, ".
			"COALESCE(SUM(IF(status_id=0 AND is_active=1,1,0)),0) AS inprogress, ".
			"COALESCE(SUM(IF(status_id=2,1,0)),0) AS waiting, ".
			"COALESCE(SUM(IF(status_id=1,1,0)),0) AS done, ".
			"COUNT(*) AS total ".
			"FROM task WHERE project_id IN (%s) GROUP BY project_id",
			implode(',', $project_ids)
		));

		$counts = [];

		foreach($rows as $row) {
			$counts[intval($row['project_id'])] = [
				'todo' => intval($row['todo']),
				'inprogress' => intval($row['inprogress']),
				'waiting' => intval($row['waiting']),
				'done' => intval($row['done']),
				'total' => intval($row['total']),
			];
		}

		return $counts;
	}

	static function random() {
		return self::_getRandom('task_project');
	}

	static function delete($ids) {
		$db = DevblocksPlatform::services()->database();

		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');

		if(empty($ids)) return false;

		$context = Context_TaskProject::ID;
		$ids_list = implode(',', self::qstrArray($ids));

		parent::_deleteAbstractBefore($context, $ids);

		// Clear the project on task records
		$db->ExecuteMaster(sprintf("UPDATE task SET project_id = 0 WHERE project_id IN (%s)", $ids_list));
		
		$db->ExecuteMaster(sprintf("DELETE FROM task_project WHERE id IN (%s)", $ids_list));

		parent::_deleteAbstractAfter($context, $ids);

		return true;
	}

	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null): array {
		$fields = SearchFields_TaskProject::getFields();

		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_TaskProject', $sortBy);

		$select_sql = sprintf("SELECT " .
			"task_project.created_at as %s, " .
			"task_project.id as %s, " .
			"task_project.is_closed as %s, " .
			"task_project.name as %s, " .
			"task_project.owner_context as %s, " .
			"task_project.owner_context_id as %s, " .
			"task_project.updated_at as %s",
			SearchFields_TaskProject::CREATED_AT,
			SearchFields_TaskProject::ID,
			SearchFields_TaskProject::IS_CLOSED,
			SearchFields_TaskProject::NAME,
			SearchFields_TaskProject::OWNER_CONTEXT,
			SearchFields_TaskProject::OWNER_CONTEXT_ID,
			SearchFields_TaskProject::UPDATED_AT
		);

		$join_sql = "FROM task_project ";

		$where_sql =
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ")
		;

		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_TaskProject');

		return [
			'primary_table' => 'task_project',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		];
	}

	static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		return self::_searchWithTimeout(
			SearchFields_TaskProject::ID,
			$query_parts['select'],
			$query_parts['join'],
			$query_parts['where'],
			$query_parts['sort'],
			$page,
			$limit,
			$withCounts
		);
	}
};

class SearchFields_TaskProject extends DevblocksSearchFields {
	const string CREATED_AT = 't_created_at';
	const string ID = 't_id';
	const string IS_CLOSED = 't_is_closed';
	const string NAME = 't_name';
	const string OWNER_CONTEXT = 't_owner_context';
	const string OWNER_CONTEXT_ID = 't_owner_context_id';
	const string UPDATED_AT = 't_updated_at';

	const string VIRTUAL_TASKS = '*_tasks'; // distbar display column
	const string VIRTUAL_TASKS_SEARCH = '*_tasks_search'; // deep filter (hidden)

	static private $_fields = null;

	static function getTableName() : string {
		return 'task_project';
	}

	static function getPrimaryKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_TaskProject::ID);
	}

	static function getUpdatedKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_TaskProject::UPDATED_AT);
	}

	static function getCreatedKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_TaskProject::CREATED_AT);
	}

	static function getCustomFieldContextKeys() {
		return [
			Context_TaskProject::ID => new DevblocksSearchFieldContextKeys('task_project.id', self::ID),
		];
	}

	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case DevblocksSearchField::VIRTUAL_OWNER:
				return self::_getWhereSQLFromContextAndID($param, 'task_project.owner_context', 'task_project.owner_context_id');

			case self::VIRTUAL_TASKS_SEARCH:
				return self::_getWhereSQLFromVirtualSearchSqlField(
					$param, Context_Task::ID,
					"SELECT project_id FROM task WHERE id IN (%s)",
					self::getPrimaryKey()
				);

			default:
				if(DevblocksPlatform::strStartsWith($param->field, 'cf_')) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					if(null !== ($virtual_where_sql = self::_getWhereSQLForCommonVirtual($param, Context_TaskProject::ID, self::getPrimaryKey())))
						return $virtual_where_sql;

					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
		}
	}

	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case self::ID:
				$models = DAO_TaskProject::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
		}

		return parent::getLabelsForKeyValues($key, $values);
	}

	static function getFields() {
		if(is_null(self::$_fields))
			self::$_fields = self::_getFields();

		return self::$_fields;
	}

	static function _getFields() {
		$translate = DevblocksPlatform::getTranslationService();

		$columns = [
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'task_project', 'created_at', $translate->_('common.created'), Model_CustomField::TYPE_DATE, true),
			self::ID => new DevblocksSearchField(self::ID, 'task_project', 'id', $translate->_('common.id'), Model_CustomField::TYPE_NUMBER, true),
			self::IS_CLOSED => new DevblocksSearchField(self::IS_CLOSED, 'task_project', 'is_closed', $translate->_('common.closed'), Model_CustomField::TYPE_CHECKBOX, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'task_project', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::OWNER_CONTEXT => new DevblocksSearchField(self::OWNER_CONTEXT, 'task_project', 'owner_context', $translate->_('common.owner'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::OWNER_CONTEXT_ID => new DevblocksSearchField(self::OWNER_CONTEXT_ID, 'task_project', 'owner_context_id', $translate->_('common.owner'), Model_CustomField::TYPE_NUMBER, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'task_project', 'updated_at', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),

			self::VIRTUAL_TASKS => new DevblocksSearchField(self::VIRTUAL_TASKS, '*', '', $translate->_('common.tasks'), DevblocksSearchCriteria::TYPE_VIRTUAL_DISTBAR, false),
			self::VIRTUAL_TASKS_SEARCH => new DevblocksSearchField(self::VIRTUAL_TASKS_SEARCH, '*', 'tasks_search', null, null, false),
		];

		if(($virtual_columns = DevblocksSearchField::getVirtualFields(owner: true)))
			$columns = array_merge($columns, $virtual_columns);

		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));

		if(!empty($custom_columns))
			$columns = array_merge($columns, $custom_columns);

		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_TaskProject extends DevblocksRecordModel {
	public int $created_at = 0;
	public int $id = 0;
	public int $is_closed = 0;
	public string $name = '';
	public string $owner_context = '';
	public int $owner_context_id = 0;
	public int $updated_at = 0;
};

class View_TaskProject extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'task_projects';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('Task Project');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_TaskProject::ID;
		$this->renderSortAsc = true;

		$this->view_columns = [
			SearchFields_TaskProject::NAME,
			DevblocksSearchField::VIRTUAL_OWNER,
			SearchFields_TaskProject::IS_CLOSED,
			SearchFields_TaskProject::UPDATED_AT,
			SearchFields_TaskProject::VIRTUAL_TASKS,
		];

		$this->addColumnsHidden([
			SearchFields_TaskProject::OWNER_CONTEXT,
			SearchFields_TaskProject::OWNER_CONTEXT_ID,
		]);

		$this->doResetCriteria();
	}

	protected function _getData() {
		return DAO_TaskProject::search(
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
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_TaskProject');
		return $objects;
	}

	function getDataAsObjects($ids=null, &$total=null) {
		return $this->_getDataAsObjects('DAO_TaskProject', $ids, $total);
	}

	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_TaskProject', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;

			switch($field_key) {
				default:
					if(DevblocksPlatform::strStartsWith($field_key, 'cf_')) {
						$pass = $this->_canSubtotalCustomField($field_key);
					} else if (str_starts_with($field_key, '*_')) {
						$pass = $this->_canSubtotalVirtualField($field_key);
					}
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
		$context = Context_TaskProject::ID;

		if(!array_key_exists($column, $fields))
			return [];

		switch($column) {
			default:
				if(DevblocksPlatform::strStartsWith($column, 'cf_')) {
					$counts = $this->_getSubtotalCountForCustomColumn($context, $column);
				} else if(DevblocksPlatform::strStartsWith($column, '*_')) {
					$counts = $this->_getSubtotalCountForVirtualField($context, $column);
				}
				break;
		}

		return $counts;
	}

	function getQuickSearchDefaultFilter(?DevblocksSearchCriteria $criteria=null) : string {
		return 'name';
	}

	function getQuickSearchFields() {
		$search_fields = SearchFields_TaskProject::getFields();

		$fields = [
			'created' => [
				'type' => DevblocksSearchCriteria::TYPE_DATE,
				'options' => ['param_key' => SearchFields_TaskProject::CREATED_AT],
			],
			'fieldset' => [
				'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
				'options' => ['param_key' => DevblocksSearchField::VIRTUAL_HAS_FIELDSET],
				'examples' => [
					['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . Context_TaskProject::ID],
				]
			],
			'closed' => [
				'type' => DevblocksSearchCriteria::TYPE_BOOL,
				'options' => ['param_key' => SearchFields_TaskProject::IS_CLOSED],
			],
			'id' => [
				'type' => DevblocksSearchCriteria::TYPE_NUMBER,
				'options' => ['param_key' => SearchFields_TaskProject::ID],
				'examples' => [
					['type' => 'chooser', 'context' => Context_TaskProject::ID, 'q' => ''],
				]
			],
			'name' => [
				'type' => DevblocksSearchCriteria::TYPE_TEXT,
				'options' => ['param_key' => SearchFields_TaskProject::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
			],
			'owner' => [
				'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
				'options' => ['param_key' => DevblocksSearchField::VIRTUAL_OWNER],
				'examples' => [
					['type' => 'search', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					['type' => 'search', 'context' => CerberusContexts::CONTEXT_GROUP, 'q' => ''],
					['type' => 'search', 'context' => CerberusContexts::CONTEXT_ROLE, 'q' => ''],
				],
			],
			'tasks' => [
				'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
				'options' => ['param_key' => SearchFields_TaskProject::VIRTUAL_TASKS_SEARCH],
				'examples' => [
					['type' => 'search', 'context' => Context_Task::ID, 'q' => ''],
				],
			],
			'updated' => [
				'type' => DevblocksSearchCriteria::TYPE_DATE,
				'options' => ['param_key' => SearchFields_TaskProject::UPDATED_AT],
			],
			'watchers' => [
				'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
				'options' => ['param_key' => DevblocksSearchField::VIRTUAL_WATCHERS],
				'examples' => [
					['type' => 'search', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
				],
			],
		];

		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', DevblocksSearchField::VIRTUAL_CONTEXT_LINK);
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('owner', $fields, 'owner', DevblocksSearchField::VIRTUAL_OWNER);
		$fields = self::_appendFieldsFromQuickSearchContext(Context_TaskProject::ID, $fields, null);
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		ksort($fields);

		return $fields;
	}

	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'fieldset':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, '*_has_fieldset');

			case 'tasks':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_TaskProject::VIRTUAL_TASKS_SEARCH);

			case 'watchers':
				return DevblocksSearchCriteria::getWatcherParamFromTokens(DevblocksSearchField::VIRTUAL_WATCHERS, $tokens);

			default:
				if($field == 'owner' || str_starts_with($field, 'owner.'))
					return DevblocksSearchCriteria::getVirtualContextParamFromTokens($field, $tokens, 'owner', DevblocksSearchField::VIRTUAL_OWNER);

				if($field == 'links' || str_starts_with($field, 'links.'))
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

		$custom_fields = DAO_CustomField::getByContext(Context_TaskProject::ID);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::records/types/task_project/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteriaParam($param) {
		switch($param->field) {
			case SearchFields_TaskProject::IS_CLOSED:
				$this->_renderCriteriaParamBoolean($param);
				break;

			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) : void {
		switch($param->field) {
			case DevblocksSearchField::VIRTUAL_OWNER:
				$this->_renderVirtualContextLinks($param, 'Owner', 'Owners', 'Owner is');
				break;

			case SearchFields_TaskProject::VIRTUAL_TASKS_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.tasks')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;

			default:
				$this->_renderVirtualCriteria($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_TaskProject::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_TaskProject::CREATED_AT:
			case SearchFields_TaskProject::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;

			case SearchFields_TaskProject::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;

			case SearchFields_TaskProject::IS_CLOSED:
				$bool = DevblocksPlatform::importGPC($_POST['bool'] ?? null, 'integer', 1);
				$criteria = new DevblocksSearchCriteria($field, $oper, $bool);
				break;

			case SearchFields_TaskProject::NAME:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;

			default:
				if(str_starts_with($field, 'cf_')) {
					$criteria = $this->_doSetCriteriaCustomField($field, substr($field,3));
				} else if (str_starts_with($field, '*_')) {
					if(($virtual_criteria = $this->_doSetCriteriaVirtual($field, $_POST, $oper)))
						$criteria = $virtual_criteria;
				}
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria, $field);
			$this->renderPage = 0;
		}
	}
};

class Context_TaskProject extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek, IDevblocksContextAutocomplete {
	const ID = 'cerb.contexts.task.project';
	const URI = 'task_project';

	static function isReadableByActor($models, $actor) {
		// Readable by the project's delegate owner (worker = private; group/role = shared members)
		return CerberusContexts::isReadableByDelegateOwner($actor, self::ID, $models);
	}

	static function isWriteableByActor($models, $actor) {
		// Writeable by the project's delegate owner
		return CerberusContexts::isWriteableByDelegateOwner($actor, self::ID, $models);
	}

	static function isDeletableByActor($models, $actor) {
		return self::isWriteableByActor($models, $actor);
	}

	// Autocomplete suggestions for the record chooser. Projects can share a name (esp. personal
	// worker-owned ones), so each row carries its owner in `meta` to disambiguate.
	function autocomplete($term, $query=null) {
		$active_worker = CerberusApplication::getActiveWorker();
		$list = [];

		$models = DAO_TaskProject::autocomplete($term, 'models', $query);

		if(!$models)
			return $list;

		// Keep only readable projects
		$readable = self::isReadableByActor($models, $active_worker);

		// Resolve each owner's label once (worker/group/role/app)
		$owner_labels = [];
		foreach($models as $model) {
			$key = $model->owner_context . ':' . $model->owner_context_id;
			if(!array_key_exists($key, $owner_labels)) {
				$labels = $values = [];
				CerberusContexts::getContext($model->owner_context, $model->owner_context_id, $labels, $values, null, true, true);
				$owner_labels[$key] = $values['_label'] ?? '';
			}
		}

		foreach($models as $id => $model) {
			if(empty($readable[$id]))
				continue;

			$entry = new stdClass();
			$entry->value = (string) $id;
			$entry->label = $model->name;

			$owner_label = $owner_labels[$model->owner_context . ':' . $model->owner_context_id] ?? '';
			if($owner_label)
				$entry->meta = ['owner' => $owner_label];

			$list[] = $entry;
		}

		return $list;
	}

	function getRandom() {
		return DAO_TaskProject::random();
	}

	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';

		$url_writer = DevblocksPlatform::services()->url();
		return $url_writer->writeNoProxy('c=profiles&type=task_project&id='.$context_id, true);
	}

	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];

		if(is_null($model))
			$model = new Model_TaskProject();

		$properties['name'] = [
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => ['context' => self::ID],
		];

		$properties['created'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.created'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->created_at,
		];

		$properties['id'] = [
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		];
		
		$properties['owner'] = [
			'label' => mb_ucfirst($translate->_('common.owner')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->owner_context_id,
			'params' => [
				'context' => $model->owner_context,
			],
		];

		$properties['updated'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		];

		return $properties;
	}

	function getMeta($context_id) {
		if(null == ($task_project = DAO_TaskProject::get($context_id)))
			return [];

		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($task_project->name);

		if(!empty($friendly))
			$url .= '-' . $friendly;

		return [
			'created' => $task_project->created_at,
			'id' => $task_project->id,
			'name' => $task_project->name,
			'permalink' => $url,
			'updated' => $task_project->updated_at,
		];
	}

	function getDefaultProperties() : array {
		return [
			'owner',
			'created_at',
			'updated_at',
		];
	}

	function getContext($task_project, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Task Project:';

		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(Context_TaskProject::ID);

		if(is_numeric($task_project)) {
			$task_project = DAO_TaskProject::get($task_project);
		} elseif($task_project instanceof Model_TaskProject) {
			DevblocksPlatform::noop();
		} elseif(is_array($task_project)) {
			$task_project = Cerb_ORMHelper::recastArrayToModel($task_project, 'Model_TaskProject');
		} else {
			$task_project = null;
		}

		$token_labels = [
			'_label' => $prefix,
			'created_at' => $prefix.$translate->_('common.created'),
			'id' => $prefix.$translate->_('common.id'),
			'is_closed' => $prefix.$translate->_('common.closed'),
			'name' => $prefix.$translate->_('common.name'),
			'owner__label' => $prefix.$translate->_('common.owner'),
			'record_url' => $prefix.$translate->_('common.url.record'),
			'updated_at' => $prefix.$translate->_('common.updated'),
		];

		$token_types = [
			'_label' => 'context_url',
			'created_at' => Model_CustomField::TYPE_DATE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'is_closed' => Model_CustomField::TYPE_CHECKBOX,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'owner__label' => 'context_url',
			'record_url' => Model_CustomField::TYPE_URL,
			'updated_at' => Model_CustomField::TYPE_DATE,
		];

		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);

		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);

		$token_values = [];
		$token_values['_context'] = Context_TaskProject::ID;
		$token_values['_type'] = 'task_project';
		$token_values['_types'] = $token_types;

		if($task_project) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $task_project->name;
			$token_values['created_at'] = $task_project->created_at;
			$token_values['id'] = $task_project->id;
			$token_values['is_closed'] = $task_project->is_closed;
			$token_values['name'] = $task_project->name;
			$token_values['owner__context'] = $task_project->owner_context;
			$token_values['owner_id'] = $task_project->owner_context_id;
			$token_values['updated_at'] = $task_project->updated_at;
			$token_values = $this->_importModelCustomFieldsAsValues($task_project, $token_values);

			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(
				sprintf("c=profiles&type=task_project&id=%d-%s", $task_project->id, DevblocksPlatform::strToPermalink($task_project->name)), true
			);
		}

		return true;
	}

	function getKeyToDaoFieldMap() {
		return [
			'created_at' => DAO_TaskProject::CREATED_AT,
			'id' => DAO_TaskProject::ID,
			'is_closed' => DAO_TaskProject::IS_CLOSED,
			'name' => DAO_TaskProject::NAME,
			'owner__context' => DAO_TaskProject::OWNER_CONTEXT,
			'owner_id' => DAO_TaskProject::OWNER_CONTEXT_ID,
			'updated_at' => DAO_TaskProject::UPDATED_AT,
			'links' => '_links',
		];
	}

	function getKeyMeta($with_dao_fields=true) {
		$keys = parent::getKeyMeta($with_dao_fields);
		return $keys;
	}

	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, $data, &$error) {
		switch(DevblocksPlatform::strLower($key)) {
		}
		return true;
	}

	function lazyLoadGetKeys() {
		return parent::lazyLoadGetKeys();
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;

		$context = Context_TaskProject::ID;
		$context_id = $dictionary['id'];
		$is_loaded = $dictionary['_loaded'] ?? false;
		$values = [];

		if(!$is_loaded) {
			$labels = [];
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}

		switch($token) {
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

		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Task Project';
		$view->renderSortBy = SearchFields_TaskProject::UPDATED_AT;
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
		$view->name = 'Task Project';

		$params_req = [];

		if(!empty($context) && !empty($context_id)) {
			$params_req = [
				new DevblocksSearchCriteria(DevblocksSearchField::VIRTUAL_CONTEXT_LINK,'in',[$context.':'.$context_id]),
			];
		}

		$view->addParamsRequired($params_req, true);
		$view->renderTemplate = 'context';
		return $view;
	}

	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = Context_TaskProject::ID;

		$tpl->assign('view_id', $view_id);

		$model = null;

		if($context_id) {
			if(!($model = DAO_TaskProject::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 403);
		}

		if(empty($context_id) || $edit) {
			if($model) {
				if(!CerberusContexts::isWriteableByActor($context, $model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);

				$tpl->assign('model', $model);
			}

			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);

			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);

			// Owner picker — the menu_actor_owner.tpl shim resolves + renders the current owner itself
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);

			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::records/types/task_project/peek_edit.tpl');

		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};
