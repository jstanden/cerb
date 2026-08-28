<?php
/**
 * A tool an AI agent can call: the model-facing name, description and parameter schema, the transcript's icon
 * and action labels, and the `agent.tool` automation that answers it -- defined once and referenced by name
 * from any agent or surface.
 *
 * The half an automation record genuinely cannot hold. An automation knows what it DOES; it has nowhere to say
 * what the model should call it, what the transcript should say while it runs, or which arguments an admin has
 * pinned out of the model's reach.
 */
class DAO_AgentTool extends Cerb_ORMHelper {
	const CREATED_AT = 'created_at';
	const DESCRIPTION = 'description';
	const ICON = 'icon';
	const ID = 'id';
	const LABEL = 'label';
	const LABEL_ACTIVE = 'label_active';
	const LABEL_SUMMARY = 'label_summary';
	const NAME = 'name';
	const PARAMS_KATA = 'params_kata';
	const STATUS = 'status';
	const UPDATED_AT = 'updated_at';
	const URI = 'uri';

	const STATUS_AVAILABLE = 0;
	const STATUS_UNLISTED = 1;
	const STATUS_DISABLED = 2;

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
			->setMaxLength(65535)
			;
		$validation
			->addField(self::ICON)
			->string()
			->setMaxLength(64)
			->addValidator(function($value, &$error=null) {
				if('' === $value)
					return true;

				if(!in_array($value, DevblocksPlatform::services()->ui()->getCerbIcons(), true)) {
					$error = "must be a cerb-icons name.";
					return false;
				}

				return true;
			})
			;
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		$validation
			->addField(self::LABEL)
			->string()
			->setMaxLength(128)
			;
		$validation
			->addField(self::LABEL_ACTIVE)
			->string()
			->setMaxLength(255)
			;
		$validation
			->addField(self::LABEL_SUMMARY)
			->string()
			->setMaxLength(255)
			;
		$validation
			->addField(self::NAME)
			->string()
			->setMaxLength(64)
			->setRequired(true)
			->setUnique(__CLASS__)
			->addValidator(function($value, &$error=null) {
				// Simultaneously the provider's function name, the `tools:` entry key, and what a transcript
				// humanizes on `[_-]`. Lowercase snake_case is the only spelling that is all three. The leading
				// letter also keeps a name from ever reading as a numeric id.
				if(!preg_match('/^[a-z][a-z0-9_]*$/', $value)) {
					$error = "must start with a lowercase letter and may contain only lowercase letters, numbers, and underscores (it's the name the model calls).";
					return false;
				}

				return true;
			})
			;
		$validation
			->addField(self::PARAMS_KATA)
			->string()
			->setMaxLength(16777215)
			->addValidator(function($value, &$error=null) {
				if('' === trim($value))
					return true;

				$kata = DevblocksPlatform::services()->kata();

				if(false === $kata->validate($value, CerberusApplication::kataSchemas()->agentTool(), $error))
					return false;

				return true;
			})
			;
		$validation
			->addField(self::STATUS)
			->uint()
			->setPossibleValues(array_keys(Model_AgentTool::getStatuses()))
			;
		$validation
			->addField(self::UPDATED_AT)
			->timestamp()
			;
		$validation
			->addField(self::URI)
			->string()
			->setMaxLength(255)
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

		$sql = "INSERT INTO agent_tool () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();

		if(!isset($fields[self::CREATED_AT]))
			$fields[self::CREATED_AT] = time();

		CerberusContexts::checkpointCreations(Context_AgentTool::ID, $id);

		self::update($id, $fields);

		return $id;
	}

	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = [$ids];

		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();

		$context = Context_AgentTool::ID;
		self::_updateAbstract($context, $ids, $fields);

		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;

			if($check_deltas) {
				CerberusContexts::checkpointChanges($context, $batch_ids);
			}

			parent::_update($batch_ids, 'agent_tool', $fields);

			if($check_deltas) {
				DevblocksPlatform::markContextChanged($context, $batch_ids);
			}
		}

		self::clearCache();
	}

	static function updateWhere($fields, $where) {
		parent::_updateWhere('agent_tool', $fields, $where);

		self::clearCache();
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
	 * @return Model_AgentTool[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);

		$sql = "SELECT created_at, description, icon, id, label, label_active, label_summary, name, params_kata, status, updated_at, uri " .
			"FROM agent_tool " .
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

	// One cache entry for the whole table. There are few tools, and every LLM turn reads the set twice
	// (once to build the provider schemas, once to label the transcript), so a per-row lookup would be the
	// hot path.
	const _CACHE_ALL = 'cerb:agent_tools:all';

	/**
	 * @return Model_AgentTool[]
	 */
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::services()->cache();

		if($nocache || false === ($objects = $cache->get(self::_CACHE_ALL))) {
			$objects = self::getWhere(null, self::NAME, true, null, DevblocksORMHelper::OPT_GET_MASTER_ONLY);
			$cache->save($objects, self::_CACHE_ALL);
		}

		return $objects;
	}

	static function clearCache() : void {
		DevblocksPlatform::services()->cache()->remove(self::_CACHE_ALL);
	}

	/**
	 * The model-facing tool name is how a `tools:` entry refers to a record, so this is the main lookup.
	 *
	 * @return Model_AgentTool|null
	 */
	static function getByName(string $name) : ?Model_AgentTool {
		$name = DevblocksPlatform::strLower(trim($name));

		if('' === $name)
			return null;

		foreach(self::getAll() as $object) {
			if(DevblocksPlatform::strLower($object->name) === $name)
				return $object;
		}

		return null;
	}

	/**
	 * Reverse lookup from the automation that answers a tool, for `getUsageMeta()`.
	 *
	 * @return Model_AgentTool[]
	 */
	static function getByUri(string $uri) : array {
		$uri = trim($uri);

		if('' === $uri)
			return [];

		return array_filter(self::getAll(), fn($object) => $object->uri === $uri);
	}

	/**
	 * @param integer $id
	 * @return Model_AgentTool|null
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
	 * @return Model_AgentTool[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}

	/**
	 * @param mysqli_result|false $rs
	 * @return Model_AgentTool[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];

		if(!($rs instanceof mysqli_result))
			return [];

		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_AgentTool();
			$object->created_at = intval($row['created_at']);
			$object->description = $row['description'];
			$object->icon = $row['icon'];
			$object->id = intval($row['id']);
			$object->label = $row['label'];
			$object->label_active = $row['label_active'];
			$object->label_summary = $row['label_summary'];
			$object->name = $row['name'];
			$object->params_kata = $row['params_kata'];
			$object->status = intval($row['status']);
			$object->updated_at = intval($row['updated_at']);
			$object->uri = $row['uri'];
			$objects[$object->id] = $object;
		}

		mysqli_free_result($rs);

		return $objects;
	}

	static function random() {
		return self::_getRandom('agent_tool');
	}

	// Matches the model-facing name or the human label, so typing either finds the tool. Whole-token, no
	// leading wildcard -- see references/record-choosers.md.
	static function autocomplete($term, $as='models') {
		$db = DevblocksPlatform::services()->database();
		$ids = [];

		$results = $db->GetArrayReader(sprintf("SELECT id ".
			"FROM agent_tool ".
			"WHERE (name LIKE %s OR label LIKE %s) ".
			"ORDER BY name ASC ".
			"LIMIT 25 ",
			$db->qstr($term.'%'),
			$db->qstr($term.'%')
		));

		if(is_array($results))
			foreach($results as $row)
				$ids[] = $row['id'];

		switch($as) {
			case 'ids':
				return $ids;
			default:
				return DAO_AgentTool::getIds($ids);
		}
	}

	static function delete($ids) {
		$db = DevblocksPlatform::services()->database();

		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');

		if(empty($ids)) return false;

		$context = Context_AgentTool::ID;
		$ids_list = implode(',', self::qstrArray($ids));

		parent::_deleteAbstractBefore($context, $ids);

		$db->ExecuteMaster(sprintf("DELETE FROM agent_tool WHERE id IN (%s)", $ids_list));

		parent::_deleteAbstractAfter($context, $ids);

		self::clearCache();

		return true;
	}

	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_AgentTool::getFields();

		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_AgentTool', $sortBy);

		$select_sql = sprintf("SELECT " .
			"agent_tool.created_at as %s, ".
			"agent_tool.description as %s, ".
			"agent_tool.icon as %s, ".
			"agent_tool.id as %s, ".
			"agent_tool.label as %s, ".
			"agent_tool.label_active as %s, ".
			"agent_tool.label_summary as %s, ".
			"agent_tool.name as %s, ".
			"agent_tool.params_kata as %s, ".
			"agent_tool.status as %s, ".
			"agent_tool.updated_at as %s, ".
			"agent_tool.uri as %s",
			SearchFields_AgentTool::CREATED_AT, SearchFields_AgentTool::DESCRIPTION, SearchFields_AgentTool::ICON, SearchFields_AgentTool::ID, SearchFields_AgentTool::LABEL, SearchFields_AgentTool::LABEL_ACTIVE, SearchFields_AgentTool::LABEL_SUMMARY, SearchFields_AgentTool::NAME, SearchFields_AgentTool::PARAMS_KATA, SearchFields_AgentTool::STATUS, SearchFields_AgentTool::UPDATED_AT, SearchFields_AgentTool::URI
		);

		$join_sql = "FROM agent_tool ";

		$where_sql =
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ")
		;

		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_AgentTool');

		return [
			'primary_table' => 'agent_tool',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		];
	}

	static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		return self::_searchWithTimeout(
			SearchFields_AgentTool::ID,
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

class SearchFields_AgentTool extends DevblocksSearchFields {
	const CREATED_AT = 'a_created_at';
	const DESCRIPTION = 'a_description';
	const ICON = 'a_icon';
	const ID = 'a_id';
	const LABEL = 'a_label';
	const LABEL_ACTIVE = 'a_label_active';
	const LABEL_SUMMARY = 'a_label_summary';
	const NAME = 'a_name';
	const PARAMS_KATA = 'a_params_kata';
	const STATUS = 'a_status';
	const UPDATED_AT = 'a_updated_at';
	const URI = 'a_uri';

	static private $_fields = null;

	static function getTableName() : string {
		return 'agent_tool';
	}

	static function getPrimaryKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_AgentTool::ID);
	}

	static function getUpdatedKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_AgentTool::UPDATED_AT);
	}

	static function getCreatedKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_AgentTool::CREATED_AT);
	}

	static function getCustomFieldContextKeys() {
		return [
			Context_AgentTool::ID => new DevblocksSearchFieldContextKeys('agent_tool.id', self::ID),
		];
	}

	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			default:
				if(DevblocksPlatform::strStartsWith($param->field, 'cf_')) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					if(null !== ($virtual_where_sql = self::_getWhereSQLForCommonVirtual($param, Context_AgentTool::ID, self::getPrimaryKey())))
						return $virtual_where_sql;

					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
		}
	}

	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case self::ID:
				$models = DAO_AgentTool::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');

			case self::STATUS:
				$statuses = Model_AgentTool::getStatuses();
				return array_combine($values, array_map(fn($v) => mb_ucfirst($statuses[intval($v)] ?? ''), $values));
		}

		return parent::getLabelsForKeyValues($key, $values);
	}

	// A subtotal click filters on the numeric form; `status:` is the word form and would not round-trip.
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		switch($key) {
			case 'status':
				$key = 'status.id';
				break;
		}

		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}

	static function getFields() {
		if(is_null(self::$_fields))
			self::$_fields = self::_getFields();

		return self::$_fields;
	}

	static function _getFields() {
		$translate = DevblocksPlatform::getTranslationService();

		$columns = [
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'agent_tool', 'created_at', $translate->_('common.created'), Model_CustomField::TYPE_DATE, true),
			self::DESCRIPTION => new DevblocksSearchField(self::DESCRIPTION, 'agent_tool', 'description', $translate->_('common.description'), Model_CustomField::TYPE_MULTI_LINE, true),
			self::ICON => new DevblocksSearchField(self::ICON, 'agent_tool', 'icon', $translate->_('common.icon'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::ID => new DevblocksSearchField(self::ID, 'agent_tool', 'id', $translate->_('common.id'), Model_CustomField::TYPE_NUMBER, true),
			self::LABEL => new DevblocksSearchField(self::LABEL, 'agent_tool', 'label', $translate->_('common.label'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::LABEL_ACTIVE => new DevblocksSearchField(self::LABEL_ACTIVE, 'agent_tool', 'label_active', $translate->_('dao.agent_tool.label_active'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::LABEL_SUMMARY => new DevblocksSearchField(self::LABEL_SUMMARY, 'agent_tool', 'label_summary', $translate->_('dao.agent_tool.label_summary'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'agent_tool', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::PARAMS_KATA => new DevblocksSearchField(self::PARAMS_KATA, 'agent_tool', 'params_kata', $translate->_('common.parameters'), Model_CustomField::TYPE_MULTI_LINE, true),
			self::STATUS => new DevblocksSearchField(self::STATUS, 'agent_tool', 'status', $translate->_('common.status'), Model_CustomField::TYPE_NUMBER, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'agent_tool', 'updated_at', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),
			self::URI => new DevblocksSearchField(self::URI, 'agent_tool', 'uri', $translate->_('common.uri'), Model_CustomField::TYPE_SINGLE_LINE, true),
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

class Model_AgentTool extends DevblocksRecordModel {
	public $created_at;
	public $description;
	public $icon;
	public $id;
	public $label;
	public $label_active;
	public $label_summary;
	public $name;
	public $params_kata;
	public $status;
	public $updated_at;
	public $uri;

	private ?array $_params = null;

	/**
	 * Available = offered by a chooser. Unlisted = hidden from choosers but still runs when an agent names
	 * it. Disabled = refused everywhere, including by name.
	 */
	public static function getStatuses() : array {
		return [
			DAO_AgentTool::STATUS_AVAILABLE => 'available',
			DAO_AgentTool::STATUS_UNLISTED => 'unlisted',
			DAO_AgentTool::STATUS_DISABLED => 'disabled',
		];
	}

	/** Choosers may offer this tool. */
	public function isAvailable() : bool {
		return DAO_AgentTool::STATUS_AVAILABLE == $this->status;
	}

	/** Usable at all -- an agent naming it directly still runs it unless it's disabled. */
	public function isUsable() : bool {
		return DAO_AgentTool::STATUS_DISABLED != $this->status;
	}

	/**
	 * The parsed `params_kata`, as `{parameters, defaults, pinned}`.
	 *
	 * `parameters:` is the model-facing schema, in the same grammar an author writes under `tool/<name>:`
	 * (`string/<name>: {description, enum, required}`), so the provider schema builder reads it unchanged.
	 * `defaults:` and `pinned:` are flat scalar maps: a default fills a parameter the model omitted, a pinned
	 * value is merged over whatever the model sent AND stripped from the schema, so the model never sees it.
	 *
	 * Both are flat on purpose -- they are the two blocks an agent or surface may override, and
	 * `Cerb\Agent\Config::resolve()` deep-merges with `array_replace_recursive`, which merges LISTS by index.
	 * Keeping the only list-valued leaf (`enum@csv:`) here, on the record, keeps that hazard out of reach.
	 */
	public function getParams() : array {
		if(!is_null($this->_params))
			return $this->_params;

		$parsed = [];

		if('' !== trim(strval($this->params_kata))) {
			$kata = DevblocksPlatform::services()->kata();
			$error = null;

			if(false !== ($tree = $kata->parse($this->params_kata, $error)))
				$parsed = $kata->formatTree($tree, null, $error) ?: [];
		}

		return $this->_params = [
			'parameters' => is_array($parsed['parameters'] ?? null) ? $parsed['parameters'] : [],
			'defaults' => is_array($parsed['defaults'] ?? null) ? $parsed['defaults'] : [],
			'pinned' => is_array($parsed['pinned'] ?? null) ? $parsed['pinned'] : [],
		];
	}

	public function getDisplayName() : string {
		return trim(strval($this->label)) ?: strval($this->name);
	}

	/**
	 * The fallback chain, as a static so the worklist can resolve a raw search row without hydrating a model.
	 * A transcript falls back to `hammer` on its own; this is the glyph a tool reads as when it's LISTED.
	 */
	public static function displayIconFor($icon) : string {
		return trim(strval($icon)) ?: 'wrench';
	}

	public function getDisplayIcon() : string {
		return self::displayIconFor($this->icon);
	}
};

class View_AgentTool extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'agenttool';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('Agent Tool');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_AgentTool::NAME;
		$this->renderSortAsc = true;

		$this->view_columns = [
			SearchFields_AgentTool::NAME,
			SearchFields_AgentTool::DESCRIPTION,
			SearchFields_AgentTool::URI,
			SearchFields_AgentTool::STATUS,
			SearchFields_AgentTool::UPDATED_AT,
		];

		// `params_kata` is a document, and the icon columns are drawn as the row's glyph rather than read.
		$this->addColumnsHidden([
			SearchFields_AgentTool::ICON,
			SearchFields_AgentTool::PARAMS_KATA,
		]);

		$this->doResetCriteria();
	}

	function getRowIcon($row) : string {
		return Model_AgentTool::displayIconFor($row[SearchFields_AgentTool::ICON] ?? '');
	}

	protected function _getData() {
		return DAO_AgentTool::search(
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
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_AgentTool');
		return $objects;
	}

	function getDataAsObjects($ids=null, &$total=null) {
		return $this->_getDataAsObjects('DAO_AgentTool', $ids, $total);
	}

	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_AgentTool', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;

			switch($field_key) {
				case SearchFields_AgentTool::STATUS:
					$pass = true;
					break;

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
		$context = Context_AgentTool::ID;

		if(!array_key_exists($column, $fields))
			return [];

		switch($column) {
			case SearchFields_AgentTool::STATUS:
				$label_map = function(array $values) use ($column) {
					return SearchFields_AgentTool::getLabelsForKeyValues($column, $values);
				};
				$counts = $this->_getSubtotalCountForNumberColumn($context, $column, $label_map, 'in');
				break;

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

	// `status:available` and `status:a` both work, and so does the raw integer -- nobody should get an empty
	// result for typing the number into the word form.
	private static function _getStatusParamFromTokens($tokens) {
		$oper = null;
		$values = [];

		CerbQuickSearchLexer::getOperArrayFromTokens($tokens, $oper, $values);

		$ids = [];
		$statuses = Model_AgentTool::getStatuses();

		foreach($values as $value) {
			$value = trim(strval($value));

			if(is_numeric($value)) {
				if(array_key_exists(intval($value), $statuses))
					$ids[] = intval($value);

				continue;
			}

			foreach($statuses as $id => $label) {
				if(0 === strncasecmp($value, $label, 1))
					$ids[] = $id;
			}
		}

		return new DevblocksSearchCriteria(SearchFields_AgentTool::STATUS, $oper, $ids);
	}

	function getQuickSearchFields() {
		$search_fields = SearchFields_AgentTool::getFields();

		$fields = [
			'created' => [
				'type' => DevblocksSearchCriteria::TYPE_DATE,
				'options' => ['param_key' => SearchFields_AgentTool::CREATED_AT],
			],
			'fieldset' => [
				'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
				'options' => ['param_key' => DevblocksSearchField::VIRTUAL_HAS_FIELDSET],
				'examples' => [
					['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . Context_AgentTool::ID],
				]
			],
			'id' => [
				'type' => DevblocksSearchCriteria::TYPE_NUMBER,
				'options' => ['param_key' => SearchFields_AgentTool::ID],
				'examples' => [
					['type' => 'chooser', 'context' => Context_AgentTool::ID, 'q' => ''],
				]
			],
			'description' => [
				'type' => DevblocksSearchCriteria::TYPE_TEXT,
				'options' => ['param_key' => SearchFields_AgentTool::DESCRIPTION, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
			],
			'label' => [
				'type' => DevblocksSearchCriteria::TYPE_TEXT,
				'options' => ['param_key' => SearchFields_AgentTool::LABEL, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
			],
			'name' => [
				'type' => DevblocksSearchCriteria::TYPE_TEXT,
				'options' => ['param_key' => SearchFields_AgentTool::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
			],
			'status' => [
				'type' => DevblocksSearchCriteria::TYPE_TEXT,
				'options' => ['param_key' => SearchFields_AgentTool::STATUS],
				'examples' => array_merge(array_values(Model_AgentTool::getStatuses()), ['[a,u]', '![d]']),
			],
			'status.id' => [
				'type' => DevblocksSearchCriteria::TYPE_NUMBER,
				'options' => ['param_key' => SearchFields_AgentTool::STATUS],
			],
			'uri' => [
				'type' => DevblocksSearchCriteria::TYPE_TEXT,
				'options' => ['param_key' => SearchFields_AgentTool::URI, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
			],
			'updated' => [
				'type' => DevblocksSearchCriteria::TYPE_DATE,
				'options' => ['param_key' => SearchFields_AgentTool::UPDATED_AT],
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
		$fields = self::_appendFieldsFromQuickSearchContext(Context_AgentTool::ID, $fields, null);
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

			case 'status':
				return self::_getStatusParamFromTokens($tokens);

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

		$custom_fields = DAO_CustomField::getByContext(Context_AgentTool::ID);
		$tpl->assign('custom_fields', $custom_fields);

		// The status cell reads a word, not the stored integer.
		$tpl->assign('statuses', Model_AgentTool::getStatuses());

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::records/types/agent_tool/view.tpl');
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
		return SearchFields_AgentTool::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_AgentTool::CREATED_AT:
			case SearchFields_AgentTool::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;

			case SearchFields_AgentTool::ID:
			case SearchFields_AgentTool::STATUS:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;

			case SearchFields_AgentTool::DESCRIPTION:
			case SearchFields_AgentTool::ICON:
			case SearchFields_AgentTool::LABEL:
			case SearchFields_AgentTool::LABEL_ACTIVE:
			case SearchFields_AgentTool::LABEL_SUMMARY:
			case SearchFields_AgentTool::NAME:
			case SearchFields_AgentTool::PARAMS_KATA:
			case SearchFields_AgentTool::URI:
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

class Context_AgentTool extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek, IDevblocksContextAutocomplete {
	const ID = 'cerb.contexts.agent.tool';
	const URI = 'agent_tool';

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
		return DAO_AgentTool::random();
	}

	// Required for a `CerbUI.RecordChooser` to resolve anything -- without it a chooser on this context
	// renders only "...".
	function autocomplete($term, $query=null) {
		$list = [];

		$models = DAO_AgentTool::autocomplete($term);

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
		return $url_writer->writeNoProxy('c=profiles&type=agent_tool&id='.$context_id, true);
	}

	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];

		if(is_null($model))
			$model = new Model_AgentTool();

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
			'type' => Model_CustomField::TYPE_MULTI_LINE,
			'value' => $model->description,
		];

		$properties['id'] = [
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		];

		$properties['label'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.label'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->label,
		];

		$properties['label_active'] = [
			'label' => mb_ucfirst($translate->_('dao.agent_tool.label_active')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->label_active,
		];

		$properties['label_summary'] = [
			'label' => mb_ucfirst($translate->_('dao.agent_tool.label_summary')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->label_summary,
		];

		$properties['status'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.status'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => mb_ucfirst(Model_AgentTool::getStatuses()[$model->status] ?? ''),
		];

		$properties['updated'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		];

		$properties['uri'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.uri'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->uri,
		];

		return $properties;
	}

	function getMeta($context_id) {
		if(null == ($agent_tool = DAO_AgentTool::get($context_id)))
			return [];

		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($agent_tool->name);

		if(!empty($friendly))
			$url .= '-' . $friendly;

		return [
			'id' => $agent_tool->id,
			'name' => $agent_tool->name,
			'permalink' => $url,
			'updated' => $agent_tool->updated_at,
			'created' => $agent_tool->created_at,
		];
	}

	function getDefaultProperties() : array {
		return [
			'created_at',
			'updated_at',
		];
	}

	function getContext($agent_tool, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Agent Tool:';

		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(Context_AgentTool::ID);

		if(is_numeric($agent_tool)) {
			$agent_tool = DAO_AgentTool::get($agent_tool);
		} elseif($agent_tool instanceof Model_AgentTool) {
			DevblocksPlatform::noop();
		} elseif(is_array($agent_tool)) {
			$agent_tool = Cerb_ORMHelper::recastArrayToModel($agent_tool, 'Model_AgentTool');
		} else {
			$agent_tool = null;
		}

		$token_labels = [
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'created_at' => $prefix.$translate->_('common.created'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'record_url' => $prefix.$translate->_('common.url.record'),
			'description' => $prefix.$translate->_('common.description'),
			'icon' => $prefix.$translate->_('common.icon'),
			'label' => $prefix.$translate->_('common.label'),
			'label_active' => $prefix.$translate->_('dao.agent_tool.label_active'),
			'label_summary' => $prefix.$translate->_('dao.agent_tool.label_summary'),
			'params_kata' => $prefix.$translate->_('common.parameters'),
			'status' => $prefix.$translate->_('common.status'),
			'uri' => $prefix.$translate->_('common.uri'),
		];

		$token_types = [
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'created_at' => Model_CustomField::TYPE_DATE,
			'updated_at' => Model_CustomField::TYPE_DATE,
			'record_url' => Model_CustomField::TYPE_URL,
			'description' => Model_CustomField::TYPE_MULTI_LINE,
			'icon' => Model_CustomField::TYPE_SINGLE_LINE,
			'label' => Model_CustomField::TYPE_SINGLE_LINE,
			'label_active' => Model_CustomField::TYPE_SINGLE_LINE,
			'label_summary' => Model_CustomField::TYPE_SINGLE_LINE,
			'params_kata' => Model_CustomField::TYPE_SINGLE_LINE,
			'status' => Model_CustomField::TYPE_SINGLE_LINE,
			'uri' => Model_CustomField::TYPE_SINGLE_LINE,
		];

		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);

		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);

		$token_values = [];
		$token_values['_context'] = Context_AgentTool::ID;
		$token_values['_type'] = 'agent_tool';
		$token_values['_types'] = $token_types;

		if($agent_tool) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $agent_tool->name;
			$token_values['_icon'] = $agent_tool->getDisplayIcon();
			$token_values['id'] = $agent_tool->id;
			$token_values['name'] = $agent_tool->name;
			$token_values['created_at'] = $agent_tool->created_at;
			$token_values['updated_at'] = $agent_tool->updated_at;
			$token_values['description'] = $agent_tool->description;
			$token_values['icon'] = $agent_tool->icon;
			$token_values['label'] = $agent_tool->label;
			$token_values['label_active'] = $agent_tool->label_active;
			$token_values['label_summary'] = $agent_tool->label_summary;
			$token_values['params_kata'] = $agent_tool->params_kata;
			$token_values['status'] = Model_AgentTool::getStatuses()[$agent_tool->status] ?? '';
			$token_values['uri'] = $agent_tool->uri;
			$token_values = $this->_importModelCustomFieldsAsValues($agent_tool, $token_values);

			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(
				sprintf("c=profiles&type=agent_tool&id=%d-%s", $agent_tool->id, DevblocksPlatform::strToPermalink($agent_tool->name)), true
			);
		}

		return true;
	}

	function getKeyToDaoFieldMap() {
		return [
			'created_at' => DAO_AgentTool::CREATED_AT,
			'description' => DAO_AgentTool::DESCRIPTION,
			'icon' => DAO_AgentTool::ICON,
			'id' => DAO_AgentTool::ID,
			'label' => DAO_AgentTool::LABEL,
			'label_active' => DAO_AgentTool::LABEL_ACTIVE,
			'label_summary' => DAO_AgentTool::LABEL_SUMMARY,
			'name' => DAO_AgentTool::NAME,
			'params_kata' => DAO_AgentTool::PARAMS_KATA,
			'status' => DAO_AgentTool::STATUS,
			'updated_at' => DAO_AgentTool::UPDATED_AT,
			'uri' => DAO_AgentTool::URI,
			'links' => '_links',
		];
	}

	function getKeyMeta($with_dao_fields=true) {
		$keys = parent::getKeyMeta($with_dao_fields);
		return $keys;
	}

	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, $data, &$error) {
		// Accept the same names the editor shows and a query matches, so `status: unlisted` works rather than
		// only its stored integer.
		switch(DevblocksPlatform::strLower($key)) {
			case 'status':
				if(is_numeric($value))
					break;

				if(false === ($id = array_search(DevblocksPlatform::strLower(trim(strval($value))), Model_AgentTool::getStatuses(), true))) {
					$error = sprintf("`status` must be one of: %s", implode(', ', Model_AgentTool::getStatuses()));
					return false;
				}

				$out_fields[DAO_AgentTool::STATUS] = $id;
				break;
		}

		return true;
	}

	function lazyLoadGetKeys() {
		return parent::lazyLoadGetKeys();
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;

		$context = Context_AgentTool::ID;
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
		$view->name = 'Agent Tool';
		$view->renderSortBy = SearchFields_AgentTool::UPDATED_AT;
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
		$view->name = 'Agent Tool';

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
		$context = Context_AgentTool::ID;

		$tpl->assign('view_id', $view_id);

		$model = null;

		if($context_id) {
			if(!($model = DAO_AgentTool::get($context_id)))
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

			// A brand new record has no model, and the template reads `$model->...` throughout.
			if(!$model)
				$tpl->assign('model', new Model_AgentTool());

			// Seed the automation chooser from the stored URI. The chooser posts an id; the save action turns
			// it back into `cerb:automation:<name>`, so a renamed automation stays connected.
			if($model && '' !== trim(strval($model->uri))) {
				$automation = DAO_Automation::getByUri($model->uri);
				$tpl->assign('automation', $automation);
			}

			$tpl->assign('statuses', Model_AgentTool::getStatuses());

			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);

			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);

			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);

			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::records/types/agent_tool/peek_edit.tpl');

		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};
