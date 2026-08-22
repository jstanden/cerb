<?php
class DAO_AgentFilesystem extends Cerb_ORMHelper {
	const CREATED_AT = 'created_at';
	const DESCRIPTION = 'description';
	const FILE_COUNT = 'file_count';
	const ID = 'id';
	const IS_DISABLED = 'is_disabled';
	const NAME = 'name';
	const TOTAL_BYTES = 'total_bytes';
	const TYPE = 'type';
	const UPDATED_AT = 'updated_at';

	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();

		$validation
			->addField(self::CREATED_AT)
			->timestamp()
			;
		$validation
			->addField(self::DESCRIPTION)
			->string()
			;
		$validation
			->addField(self::FILE_COUNT)
			->uint()
			;
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		$validation
			->addField(self::IS_DISABLED)
			->uint()
			;
		$validation
			->addField(self::NAME)
			->string()
			->setRequired(true)
			->setUnique(__CLASS__)
			->setMaxLength(255)
			->addValidator(function($string, &$error=null) {
				// Handle: letters, digits, and dashes, but must START with a letter (like a language identifier)
				// -- so a name can never be read as a numeric id. Digits are needed for id-based names (e.g.
				// per-worker memory volumes).
				if(!preg_match('/^[A-Za-z][A-Za-z0-9-]*$/', $string)) {
					$error = "must start with a letter and may contain only letters (A-Z, a-z), digits (0-9), and dashes -- no spaces or special characters.";
					return false;
				}
				return true;
			})
			;
		$validation
			->addField(self::TOTAL_BYTES)
			->uint()
			;
		$validation
			->addField(self::TYPE)
			->string()
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

		$sql = "INSERT INTO agent_filesystem () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();

		if(!isset($fields[self::CREATED_AT]))
			$fields[self::CREATED_AT] = time();

		CerberusContexts::checkpointCreations(Context_AgentFilesystem::ID, $id);

		self::update($id, $fields);

		return $id;
	}

	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = [$ids];

		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();

		$context = Context_AgentFilesystem::ID;
		self::_updateAbstract($context, $ids, $fields);

		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;

			if($check_deltas) {
				CerberusContexts::checkpointChanges($context, $batch_ids);
			}

			parent::_update($batch_ids, 'agent_filesystem', $fields);

			if($check_deltas) {
				DevblocksPlatform::markContextChanged($context, $batch_ids);
			}
		}
	}

	static function updateWhere($fields, $where) {
		parent::_updateWhere('agent_filesystem', $fields, $where);
	}

	private static bool $_defer_recount = false;

	/** [filesystem_id => true] collected while deferred */
	private static array $_deferred_recount_ids = [];

	/**
	 * Collect `recount()` calls instead of running them, until `flushRecount()`.
	 *
	 * For a bulk writer whose files go in one at a time (the ZIP importer writes a row per archive entry):
	 * without this, a thousand-file archive would re-count the volume a thousand times. ALWAYS flush in a
	 * `finally` -- an abandoned window leaves the counters stale until the next write.
	 */
	static function deferRecount() : void {
		self::$_defer_recount = true;
	}

	static function flushRecount() : void {
		self::$_defer_recount = false;

		$deferred = self::$_deferred_recount_ids;
		self::$_deferred_recount_ids = [];

		if($deferred)
			self::recount(array_keys($deferred));
	}

	/**
	 * Refresh the cached `file_count` / `total_bytes` for one or more volumes.
	 *
	 * These are a denormalization of `agent_file`, so they belong to whatever writes files -- and files are
	 * written from four places (the ZIP importer, the VFS `write`/`rm` commands, the peek editor, and
	 * `record.*` from an automation or a workflow import). Only the importer ever maintained them, so a volume
	 * populated any other way read "0 files, 0 bytes" on its card, its profile, and in the `mounts:`
	 * autocomplete. Hanging it off `DAO_AgentFile`'s write paths covers all four at once.
	 *
	 * Counted in SQL rather than read-modify-write: concurrent writers (an agent writing while an import runs)
	 * would otherwise race, and the correct value is always one query away. A volume whose last file just went
	 * is not returned by the subqueries at all -- `COUNT` of nothing is 0, which is exactly the answer.
	 *
	 * `updated_at` is deliberately NOT bumped: a counter refresh is bookkeeping, and bumping it would make
	 * every agent file write look like an edit to the volume in "recently updated" worklists.
	 */
	static function recount($filesystem_ids) : void {
		if(!is_array($filesystem_ids))
			$filesystem_ids = [$filesystem_ids];

		if(!($filesystem_ids = DevblocksPlatform::sanitizeArray($filesystem_ids, 'int', ['unique', 'nonzero'])))
			return;

		if(self::$_defer_recount) {
			foreach($filesystem_ids as $filesystem_id)
				self::$_deferred_recount_ids[$filesystem_id] = true;

			return;
		}

		$db = DevblocksPlatform::services()->database();

		$db->ExecuteMaster(sprintf("UPDATE agent_filesystem SET ".
			"file_count = (SELECT COUNT(1) FROM agent_file WHERE filesystem_id = agent_filesystem.id), ".
			"total_bytes = (SELECT COALESCE(SUM(size),0) FROM agent_file WHERE filesystem_id = agent_filesystem.id) ".
			"WHERE id IN (%s)",
			implode(',', $filesystem_ids)
		));
	}

	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		if(!CerberusContexts::isActorAnAdmin($actor)) {
			$error = DevblocksPlatform::translate('error.core.no_acl.admin');
			return false;
		}

		return true;
	}

	/**
	 * @param string $where
	 * @return Model_AgentFilesystem[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);

		$sql = "SELECT created_at, description, file_count, id, is_disabled, name, total_bytes, type, updated_at " .
			"FROM agent_filesystem " .
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
	 * @return Model_AgentFilesystem[]
	 */
	static function getAll($nocache=false) {
		$objects = self::getWhere(null, self::NAME, true, null, DevblocksORMHelper::OPT_GET_MASTER_ONLY);
		return $objects;
	}

	/**
	 * @param integer $id
	 * @return Model_AgentFilesystem|null
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
	 * @return Model_AgentFilesystem[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}

	/**
	 * @param mysqli_result|false $rs
	 * @return Model_AgentFilesystem[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];

		if(!($rs instanceof mysqli_result))
			return [];

		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_AgentFilesystem();
			$object->created_at = intval($row['created_at']);
			$object->description = $row['description'];
			$object->file_count = intval($row['file_count']);
			$object->id = intval($row['id']);
			$object->is_disabled = intval($row['is_disabled']);
			$object->name = $row['name'];
			$object->total_bytes = intval($row['total_bytes']);
			$object->type = $row['type'];
			$object->updated_at = intval($row['updated_at']);
			$objects[$object->id] = $object;
		}

		mysqli_free_result($rs);

		return $objects;
	}

	static function random() {
		return self::_getRandom('agent_filesystem');
	}

	static function delete($ids) {
		$db = DevblocksPlatform::services()->database();

		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');

		if(empty($ids)) return false;

		$context = Context_AgentFilesystem::ID;
		$ids_list = implode(',', self::qstrArray($ids));

		parent::_deleteAbstractBefore($context, $ids);

		$db->ExecuteMaster(sprintf("DELETE FROM agent_filesystem WHERE id IN (%s)", $ids_list));

		parent::_deleteAbstractAfter($context, $ids);

		return true;
	}

	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_AgentFilesystem::getFields();

		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_AgentFilesystem', $sortBy);

		$select_sql = sprintf("SELECT " .
			"agent_filesystem.created_at as %s, " .
			"agent_filesystem.description as %s, " .
			"agent_filesystem.file_count as %s, " .
			"agent_filesystem.id as %s, " .
			"agent_filesystem.is_disabled as %s, " .
			"agent_filesystem.name as %s, " .
			"agent_filesystem.total_bytes as %s, " .
			"agent_filesystem.type as %s, " .
			"agent_filesystem.updated_at as %s",
			SearchFields_AgentFilesystem::CREATED_AT,
			SearchFields_AgentFilesystem::DESCRIPTION,
			SearchFields_AgentFilesystem::FILE_COUNT,
			SearchFields_AgentFilesystem::ID,
			SearchFields_AgentFilesystem::IS_DISABLED,
			SearchFields_AgentFilesystem::NAME,
			SearchFields_AgentFilesystem::TOTAL_BYTES,
			SearchFields_AgentFilesystem::TYPE,
			SearchFields_AgentFilesystem::UPDATED_AT
		);

		$join_sql = "FROM agent_filesystem ";

		$where_sql =
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ")
		;

		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_AgentFilesystem');

		return [
			'primary_table' => 'agent_filesystem',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		];
	}

	static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		return self::_searchWithTimeout(
			SearchFields_AgentFilesystem::ID,
			$query_parts['select'],
			$query_parts['join'],
			$query_parts['where'],
			$query_parts['sort'],
			$page,
			$limit,
			$withCounts
		);
	}

	static function autocomplete($term, $as='models') {
		$db = DevblocksPlatform::services()->database();
		$ids = [];

		$results = $db->GetArrayReader(sprintf("SELECT id ".
			"FROM agent_filesystem ".
			"WHERE name LIKE %s ".
			"ORDER BY name ASC ".
			"LIMIT 25 ",
			$db->qstr($term.'%')
		));

		if(is_array($results))
			foreach($results as $row)
				$ids[] = $row['id'];

		switch($as) {
			case 'ids':
				return $ids;
			default:
				return DAO_AgentFilesystem::getIds($ids);
		}
	}
};

class SearchFields_AgentFilesystem extends DevblocksSearchFields {
	const CREATED_AT = 'a_created_at';
	const DESCRIPTION = 'a_description';
	const FILE_COUNT = 'a_file_count';
	const ID = 'a_id';
	const IS_DISABLED = 'a_is_disabled';
	const NAME = 'a_name';
	const TOTAL_BYTES = 'a_total_bytes';
	const TYPE = 'a_type';
	const UPDATED_AT = 'a_updated_at';

	static private $_fields = null;

	static function getTableName() : string {
		return 'agent_filesystem';
	}

	static function getPrimaryKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_AgentFilesystem::ID);
	}

	static function getUpdatedKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_AgentFilesystem::UPDATED_AT);
	}

	static function getCreatedKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_AgentFilesystem::CREATED_AT);
	}

	static function getCustomFieldContextKeys() {
		return [
			Context_AgentFilesystem::ID => new DevblocksSearchFieldContextKeys('agent_filesystem.id', self::ID),
		];
	}

	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			default:
				if(DevblocksPlatform::strStartsWith($param->field, 'cf_')) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					if(null !== ($virtual_where_sql = self::_getWhereSQLForCommonVirtual($param, Context_AgentFilesystem::ID, self::getPrimaryKey())))
						return $virtual_where_sql;

					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
		}
	}

	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case self::ID:
				$models = DAO_AgentFilesystem::getIds($values);
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
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'agent_filesystem', 'created_at', $translate->_('common.created'), Model_CustomField::TYPE_DATE, true),
			self::DESCRIPTION => new DevblocksSearchField(self::DESCRIPTION, 'agent_filesystem', 'description', $translate->_('common.description'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::FILE_COUNT => new DevblocksSearchField(self::FILE_COUNT, 'agent_filesystem', 'file_count', $translate->_('dao.agent_filesystem.file_count'), Model_CustomField::TYPE_NUMBER, true),
			self::ID => new DevblocksSearchField(self::ID, 'agent_filesystem', 'id', $translate->_('common.id'), Model_CustomField::TYPE_NUMBER, true),
			self::IS_DISABLED => new DevblocksSearchField(self::IS_DISABLED, 'agent_filesystem', 'is_disabled', $translate->_('dao.agent_filesystem.is_disabled'), Model_CustomField::TYPE_CHECKBOX, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'agent_filesystem', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::TOTAL_BYTES => new DevblocksSearchField(self::TOTAL_BYTES, 'agent_filesystem', 'total_bytes', $translate->_('dao.agent_filesystem.total_bytes'), Model_CustomField::TYPE_NUMBER, true),
			self::TYPE => new DevblocksSearchField(self::TYPE, 'agent_filesystem', 'type', $translate->_('dao.agent_filesystem.type'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'agent_filesystem', 'updated_at', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),
		];

		if(($virtual_columns = DevblocksSearchField::getVirtualFields()))
			$columns = array_merge($columns, $virtual_columns);

		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));

		if(!empty($custom_columns))
			$columns = array_merge($columns, $custom_columns);

		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_AgentFilesystem extends DevblocksRecordModel {
	public $created_at;
	public $description;
	public $file_count;
	public $id;
	public $is_disabled;
	public $name;
	public $total_bytes;
	public $type;
	public $updated_at;
};

class View_AgentFilesystem extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'agent_filesystems';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('Agent Filesystem');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_AgentFilesystem::ID;
		$this->renderSortAsc = true;

		// Explicit default column order (not alphabetized)
		$this->view_columns = [
			SearchFields_AgentFilesystem::NAME,
			SearchFields_AgentFilesystem::DESCRIPTION,
			SearchFields_AgentFilesystem::FILE_COUNT,
			SearchFields_AgentFilesystem::TOTAL_BYTES,
			SearchFields_AgentFilesystem::UPDATED_AT,
		];

		$this->addColumnsHidden([]);

		$this->doResetCriteria();
	}

	protected function _getData() {
		return DAO_AgentFilesystem::search(
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
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_AgentFilesystem');
		return $objects;
	}

	function getDataAsObjects($ids=null, &$total=null) {
		return $this->_getDataAsObjects('DAO_AgentFilesystem', $ids, $total);
	}

	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_AgentFilesystem', $size);
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
		$context = Context_AgentFilesystem::ID;

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
		$search_fields = SearchFields_AgentFilesystem::getFields();

		$fields = [
			'created' => [
				'type' => DevblocksSearchCriteria::TYPE_DATE,
				'options' => ['param_key' => SearchFields_AgentFilesystem::CREATED_AT],
			],
			'fieldset' => [
				'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
				'options' => ['param_key' => DevblocksSearchField::VIRTUAL_HAS_FIELDSET],
				'examples' => [
					['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . Context_AgentFilesystem::ID],
				]
			],
			'id' => [
				'type' => DevblocksSearchCriteria::TYPE_NUMBER,
				'options' => ['param_key' => SearchFields_AgentFilesystem::ID],
				'examples' => [
					['type' => 'chooser', 'context' => Context_AgentFilesystem::ID, 'q' => ''],
				]
			],
			'name' => [
				'type' => DevblocksSearchCriteria::TYPE_TEXT,
				'options' => ['param_key' => SearchFields_AgentFilesystem::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
			],
			'updated' => [
				'type' => DevblocksSearchCriteria::TYPE_DATE,
				'options' => ['param_key' => SearchFields_AgentFilesystem::UPDATED_AT],
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
		$fields = self::_appendFieldsFromQuickSearchContext(Context_AgentFilesystem::ID, $fields, null);
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		ksort($fields);

		return $fields;
	}

	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'fieldset':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, '*_has_fieldset');

			case 'watchers':
				return DevblocksSearchCriteria::getWatcherParamFromTokens(DevblocksSearchField::VIRTUAL_WATCHERS, $tokens);

			default:
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

		$custom_fields = DAO_CustomField::getByContext(Context_AgentFilesystem::ID);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::records/types/agent_filesystem/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteriaParam($param) {
		switch($param->field) {
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) : void {
		switch($param->field) {
			default:
				$this->_renderVirtualCriteria($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_AgentFilesystem::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_AgentFilesystem::CREATED_AT:
			case SearchFields_AgentFilesystem::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;

			case SearchFields_AgentFilesystem::FILE_COUNT:
			case SearchFields_AgentFilesystem::ID:
			case SearchFields_AgentFilesystem::IS_DISABLED:
			case SearchFields_AgentFilesystem::TOTAL_BYTES:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;

			case SearchFields_AgentFilesystem::DESCRIPTION:
			case SearchFields_AgentFilesystem::NAME:
			case SearchFields_AgentFilesystem::TYPE:
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

class Context_AgentFilesystem extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek, IDevblocksContextAutocomplete {
	const ID = 'cerb.contexts.agent.filesystem';
	const URI = 'agent_filesystem';

	static function isReadableByActor($models, $actor) {
		return CerberusContexts::allowEverything($models);
	}

	static function isWriteableByActor($models, $actor) {
		return self::_isWriteableOnlyByAdmin($models, $actor);
	}

	static function isDeletableByActor($models, $actor) {
		return self::isWriteableByActor($models, $actor);
	}

	function getRandom() {
		return DAO_AgentFilesystem::random();
	}

	function autocomplete($term, $query=null) {
		$list = [];

		$models = DAO_AgentFilesystem::autocomplete($term);

		if(is_array($models))
			foreach($models as $id => $model) {
				$entry = new stdClass();
				$entry->label = $model->name;
				$entry->value = sprintf("%d", $id);
				$list[] = $entry;
			}

		return $list;
	}

	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';

		$url_writer = DevblocksPlatform::services()->url();
		return $url_writer->writeNoProxy('c=profiles&type=agent_filesystem&id='.$context_id, true);
	}

	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];

		if(is_null($model))
			$model = new Model_AgentFilesystem();

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

		$properties['description'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.description'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->description,
		];

		$properties['file_count'] = [
			'label' => mb_ucfirst($translate->_('dao.agent_filesystem.file_count')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->file_count,
		];

		$properties['id'] = [
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		];

		$properties['is_disabled'] = [
			'label' => mb_ucfirst($translate->_('dao.agent_filesystem.is_disabled')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->is_disabled,
		];

		$properties['total_bytes'] = [
			'label' => mb_ucfirst($translate->_('dao.agent_filesystem.total_bytes')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->total_bytes,
		];

		$properties['type'] = [
			'label' => mb_ucfirst($translate->_('dao.agent_filesystem.type')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->type,
		];

		$properties['updated'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		];

		return $properties;
	}

	function getMeta($context_id) {
		if(null == ($agent_filesystem = DAO_AgentFilesystem::get($context_id)))
			return [];

		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($agent_filesystem->name);

		if(!empty($friendly))
			$url .= '-' . $friendly;

		return [
			'id' => $agent_filesystem->id,
			'name' => $agent_filesystem->name,
			'permalink' => $url,
			'updated' => $agent_filesystem->updated_at,
			'created' => $agent_filesystem->created_at,
		];
	}

	function getDefaultProperties() : array {
		return [
			'created_at',
			'updated_at',
		];
	}

	function getContext($agent_filesystem, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Agent Filesystem:';

		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(Context_AgentFilesystem::ID);

		if(is_numeric($agent_filesystem)) {
			$agent_filesystem = DAO_AgentFilesystem::get($agent_filesystem);
		} elseif($agent_filesystem instanceof Model_AgentFilesystem) {
			DevblocksPlatform::noop();
		} elseif(is_array($agent_filesystem)) {
			$agent_filesystem = Cerb_ORMHelper::recastArrayToModel($agent_filesystem, 'Model_AgentFilesystem');
		} else {
			$agent_filesystem = null;
		}

		$token_labels = [
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'created_at' => $prefix.$translate->_('common.created'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'record_url' => $prefix.$translate->_('common.url.record'),
			'description' => $prefix.$translate->_('common.description'),
			'file_count' => $prefix.$translate->_('dao.agent_filesystem.file_count'),
			'is_disabled' => $prefix.$translate->_('dao.agent_filesystem.is_disabled'),
			'total_bytes' => $prefix.$translate->_('dao.agent_filesystem.total_bytes'),
			'type' => $prefix.$translate->_('dao.agent_filesystem.type'),
		];

		$token_types = [
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'created_at' => Model_CustomField::TYPE_DATE,
			'updated_at' => Model_CustomField::TYPE_DATE,
			'record_url' => Model_CustomField::TYPE_URL,
			'description' => Model_CustomField::TYPE_SINGLE_LINE,
			'file_count' => Model_CustomField::TYPE_SINGLE_LINE,
			'is_disabled' => Model_CustomField::TYPE_SINGLE_LINE,
			'total_bytes' => Model_CustomField::TYPE_SINGLE_LINE,
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
		];

		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);

		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);

		$token_values = [];
		$token_values['_context'] = Context_AgentFilesystem::ID;
		$token_values['_type'] = 'agent_filesystem';
		$token_values['_types'] = $token_types;

		if($agent_filesystem) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $agent_filesystem->name;
			$token_values['id'] = $agent_filesystem->id;
			$token_values['name'] = $agent_filesystem->name;
			$token_values['created_at'] = $agent_filesystem->created_at;
			$token_values['updated_at'] = $agent_filesystem->updated_at;
			$token_values['description'] = $agent_filesystem->description;
			$token_values['file_count'] = $agent_filesystem->file_count;
			$token_values['is_disabled'] = $agent_filesystem->is_disabled;
			$token_values['total_bytes'] = $agent_filesystem->total_bytes;
			$token_values['type'] = $agent_filesystem->type;
			$token_values = $this->_importModelCustomFieldsAsValues($agent_filesystem, $token_values);

			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(
				sprintf("c=profiles&type=agent_filesystem&id=%d-%s", $agent_filesystem->id, DevblocksPlatform::strToPermalink($agent_filesystem->name)), true
			);
		}

		return true;
	}

	function getKeyToDaoFieldMap() {
		return [
			'created_at' => DAO_AgentFilesystem::CREATED_AT,
			'description' => DAO_AgentFilesystem::DESCRIPTION,
			'file_count' => DAO_AgentFilesystem::FILE_COUNT,
			'id' => DAO_AgentFilesystem::ID,
			'is_disabled' => DAO_AgentFilesystem::IS_DISABLED,
			'name' => DAO_AgentFilesystem::NAME,
			'total_bytes' => DAO_AgentFilesystem::TOTAL_BYTES,
			'type' => DAO_AgentFilesystem::TYPE,
			'updated_at' => DAO_AgentFilesystem::UPDATED_AT,
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

		$context = Context_AgentFilesystem::ID;
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
		$view->name = 'Agent Filesystem';
		$view->renderSortBy = SearchFields_AgentFilesystem::UPDATED_AT;
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
		$view->name = 'Agent Filesystem';

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
		$context = Context_AgentFilesystem::ID;

		$tpl->assign('view_id', $view_id);

		$model = null;

		if($context_id) {
			if(!($model = DAO_AgentFilesystem::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 403);
		}

		if(empty($context_id) || $edit) {
			// ACL
			if(!$active_worker->is_superuser)
				DevblocksPlatform::dieWithHttpError(null, 403);

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

			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);

			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::records/types/agent_filesystem/peek_edit.tpl');

		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};
