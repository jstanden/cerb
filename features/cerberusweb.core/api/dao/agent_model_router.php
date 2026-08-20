<?php
class DAO_AgentModelRouter extends Cerb_ORMHelper {
	const CREATED_AT = 'created_at';
	const DESCRIPTION = 'description';
	const ID = 'id';
	const IS_DEFAULT = 'is_default';
	const IS_DISABLED = 'is_disabled';
	const LABEL = 'label';
	const MODELS_KATA = 'models_kata';
	const NAME = 'name';
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
			->setMaxLength(255)
			;
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// The system default -- the router used when nothing names one. Single-winner: setDefault() clears the
		// others, so this is set through that method rather than by writing the column directly.
		$validation
			->addField(self::IS_DEFAULT)
			->bit()
			;
		$validation
			->addField(self::IS_DISABLED)
			->bit()
			;
		// A friendly display name for pickers -- `name` is the URI handle (`default`), this is what a reader
		// should see ("Default"). Blank falls back to `name`. Same split as `agent_model`.
		$validation
			->addField(self::LABEL)
			->string()
			->setMaxLength(128)
			;
		// The ranked model list, in the SAME grammar as `agentPrompt: models:` -- each key is an `agent_model`
		// NAME, optionally `<name>/<alias>:` to mount the same record twice with different auth/endpoint/knobs.
		// Validated as parseable KATA on save so a typo surfaces here rather than at the first agent turn.
		$validation
			->addField(self::MODELS_KATA)
			->string()
			->setMaxLength(65535)
			->addValidator(function($value, &$error=null) {
				if('' === trim(strval($value)))
					return true;

				if(false === DevblocksPlatform::services()->kata()->parse($value, $parse_error)) {
					$error = 'must be valid KATA: ' . $parse_error;
					return false;
				}

				return true;
			})
			;
		// `name` IS the uri: `cerb:agent_model_router:<name>` is how a workflow or automation references a
		// router, so it's identifier-shaped and unique. Same rule as `agent_model.name`.
		$validation
			->addField(self::NAME)
			->string()
			->setMaxLength(128)
			->setRequired(true)
			->setUnique(__CLASS__)
			->addValidator(function($value, &$error=null) {
				if(!preg_match('/^[A-Za-z0-9_.-]+$/', $value)) {
					$error = "must only contain letters, numbers, dots, dashes, and underscores (it's the router's URI).";
					return false;
				}

				return true;
			})
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

		$sql = "INSERT INTO agent_model_router () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();

		if(!isset($fields[self::CREATED_AT]))
			$fields[self::CREATED_AT] = time();

		CerberusContexts::checkpointCreations(Context_AgentModelRouter::ID, $id);

		self::update($id, $fields);

		// The FIRST router becomes the default, so an environment is never router-less and nothing has to be
		// told to pick one. Checked after the insert (the new row is present), so "is there any other default"
		// is the real question -- not "is this the first row".
		if(!$db->GetOneMaster(sprintf("SELECT id FROM agent_model_router WHERE is_default = 1 AND id != %d LIMIT 1", $id)))
			self::setDefault($id);

		return $id;
	}

	/**
	 * The system default router -- what resolves when nothing names one. There is always exactly one (create()
	 * auto-flags the first, and setDefault() is single-winner), so callers never have to handle "none".
	 *
	 * @return Model_AgentModelRouter|null
	 */
	static function getDefault() {
		$objects = self::getWhere(sprintf("%s = 1", self::IS_DEFAULT), self::NAME, true, 1);
		return reset($objects) ?: null;
	}

	/**
	 * Single-winner flip. Same two-statement shape as DAO_Group::setDefaultGroup() -- clear every row, then set
	 * one. Raw SQL rather than update() on purpose: clearing the previous default is bookkeeping, not a change
	 * anyone asked for, so it shouldn't fire change events on an unrelated record.
	 */
	static function setDefault($id) {
		$db = DevblocksPlatform::services()->database();

		$db->ExecuteMaster("UPDATE agent_model_router SET is_default = 0");
		$db->ExecuteMaster(sprintf("UPDATE agent_model_router SET is_default = 1 WHERE id = %d", $id));

		DevblocksPlatform::markContextChanged(Context_AgentModelRouter::ID, $id);
	}

	/**
	 * Type-to-search for record choosers. Prefix match on `name` (house convention -- see
	 * `references/record-choosers.md`); an empty term returns the first 25, so the picker opens populated.
	 *
	 * Disabled routers are omitted: they can't be resolved, so offering one would let someone pick something
	 * that silently falls back to the default.
	 */
	static function autocomplete($term, $as='models') {
		$db = DevblocksPlatform::services()->database();
		$ids = [];

		$results = $db->GetArrayReader(sprintf("SELECT id ".
			"FROM agent_model_router ".
			"WHERE is_disabled = 0 AND name LIKE %s ".
			"ORDER BY name ASC ".
			"LIMIT 25 ",
			$db->qstr($term.'%')
		)) ?: [];

		foreach($results as $row)
			$ids[] = $row['id'];

		return match($as) {
			'ids' => $ids,
			default => self::getIds($ids),
		};
	}

	/**
	 * Resolve a router by its `name` -- which IS its uri (`cerb:agent_model_router:<name>`), so this is where
	 * every reference form ends up. Mirrors DAO_AgentModel::getByName().
	 *
	 * @return Model_AgentModelRouter|null
	 */
	static function getByName(string $name) {
		if('' === trim($name))
			return null;

		$db = DevblocksPlatform::services()->database();

		$objects = self::getWhere(sprintf("%s = %s",
			self::NAME,
			$db->qstr($name)
		));

		return reset($objects) ?: null;
	}

	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = [$ids];

		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();

		$context = Context_AgentModelRouter::ID;
		self::_updateAbstract($context, $ids, $fields);

		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;

			if($check_deltas) {
				CerberusContexts::checkpointChanges($context, $batch_ids);
			}

			parent::_update($batch_ids, 'agent_model_router', $fields);

			if($check_deltas) {
				DevblocksPlatform::markContextChanged($context, $batch_ids);
			}
		}
	}

	static function updateWhere($fields, $where) {
		parent::_updateWhere('agent_model_router', $fields, $where);
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
	 * @return Model_AgentModelRouter[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);

		$sql = "SELECT created_at, description, id, is_default, is_disabled, label, models_kata, name, updated_at " .
			"FROM agent_model_router " .
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
	 * @return Model_AgentModelRouter[]
	 */
	static function getAll($nocache=false) {
		$objects = self::getWhere(null, self::NAME, true, null, DevblocksORMHelper::OPT_GET_MASTER_ONLY);
		return $objects;
	}

	/**
	 * @param integer $id
	 * @return Model_AgentModelRouter|null
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
	 * @return Model_AgentModelRouter[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}

	/**
	 * @param mysqli_result|false $rs
	 * @return Model_AgentModelRouter[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];

		if(!($rs instanceof mysqli_result))
			return [];

		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_AgentModelRouter();
			$object->created_at = intval($row['created_at']);
			$object->description = $row['description'];
			$object->id = intval($row['id']);
			$object->is_default = intval($row['is_default']);
			$object->is_disabled = intval($row['is_disabled']);
			$object->label = $row['label'];
			$object->models_kata = $row['models_kata'];
			$object->name = $row['name'];
			$object->updated_at = intval($row['updated_at']);
			$objects[$object->id] = $object;
		}

		mysqli_free_result($rs);

		return $objects;
	}

	static function random() {
		return self::_getRandom('agent_model_router');
	}

	static function delete($ids) {
		$db = DevblocksPlatform::services()->database();

		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');

		if(empty($ids)) return false;

		$context = Context_AgentModelRouter::ID;
		$ids_list = implode(',', self::qstrArray($ids));

		parent::_deleteAbstractBefore($context, $ids);

		$db->ExecuteMaster(sprintf("DELETE FROM agent_model_router WHERE id IN (%s)", $ids_list));

		parent::_deleteAbstractAfter($context, $ids);

		// Deleting the default would otherwise leave the system with no router to fall back to. Promote another
		// rather than refusing the delete: the admin's intent (remove this one) is honored, and the invariant
		// "there is always a default" survives. Oldest first, so it's the least surprising survivor.
		if(!$db->GetOneMaster("SELECT id FROM agent_model_router WHERE is_default = 1 LIMIT 1")) {
			if(($next_id = $db->GetOneMaster("SELECT id FROM agent_model_router ORDER BY id LIMIT 1")))
				self::setDefault($next_id);
		}

		return true;
	}

	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_AgentModelRouter::getFields();

		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_AgentModelRouter', $sortBy);

		$select_sql = sprintf("SELECT " .
			"agent_model_router.created_at as %s, " .
			"agent_model_router.description as %s, " .
			"agent_model_router.id as %s, " .
			"agent_model_router.is_default as %s, " .
			"agent_model_router.is_disabled as %s, " .
			"agent_model_router.label as %s, " .
			"agent_model_router.name as %s, " .
			"agent_model_router.updated_at as %s",
			SearchFields_AgentModelRouter::CREATED_AT,
			SearchFields_AgentModelRouter::DESCRIPTION,
			SearchFields_AgentModelRouter::ID,
			SearchFields_AgentModelRouter::IS_DEFAULT,
			SearchFields_AgentModelRouter::IS_DISABLED,
			SearchFields_AgentModelRouter::LABEL,
			SearchFields_AgentModelRouter::NAME,
			SearchFields_AgentModelRouter::UPDATED_AT
		);

		$join_sql = "FROM agent_model_router ";

		$where_sql =
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ")
		;

		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_AgentModelRouter');

		return [
			'primary_table' => 'agent_model_router',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		];
	}

	static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		return self::_searchWithTimeout(
			SearchFields_AgentModelRouter::ID,
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

class SearchFields_AgentModelRouter extends DevblocksSearchFields {
	const CREATED_AT = 'a_created_at';
	const DESCRIPTION = 'a_description';
	const ID = 'a_id';
	const IS_DEFAULT = 'a_is_default';
	const IS_DISABLED = 'a_is_disabled';
	const LABEL = 'a_label';
	const NAME = 'a_name';
	const UPDATED_AT = 'a_updated_at';

	static private $_fields = null;

	static function getTableName() : string {
		return 'agent_model_router';
	}

	static function getPrimaryKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_AgentModelRouter::ID);
	}

	static function getUpdatedKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_AgentModelRouter::UPDATED_AT);
	}

	static function getCreatedKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_AgentModelRouter::CREATED_AT);
	}

	static function getCustomFieldContextKeys() {
		return [
			Context_AgentModelRouter::ID => new DevblocksSearchFieldContextKeys('agent_model_router.id', self::ID),
		];
	}

	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			default:
				if(DevblocksPlatform::strStartsWith($param->field, 'cf_')) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					if(null !== ($virtual_where_sql = self::_getWhereSQLForCommonVirtual($param, Context_AgentModelRouter::ID, self::getPrimaryKey())))
						return $virtual_where_sql;

					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
		}
	}

	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case self::ID:
				$models = DAO_AgentModelRouter::getIds($values);
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
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'agent_model_router', 'created_at', $translate->_('common.created'), Model_CustomField::TYPE_DATE, true),
			self::DESCRIPTION => new DevblocksSearchField(self::DESCRIPTION, 'agent_model_router', 'description', $translate->_('common.description'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::ID => new DevblocksSearchField(self::ID, 'agent_model_router', 'id', $translate->_('common.id'), Model_CustomField::TYPE_NUMBER, true),
			self::IS_DEFAULT => new DevblocksSearchField(self::IS_DEFAULT, 'agent_model_router', 'is_default', $translate->_('common.default'), Model_CustomField::TYPE_CHECKBOX, true),
			self::IS_DISABLED => new DevblocksSearchField(self::IS_DISABLED, 'agent_model_router', 'is_disabled', $translate->_('common.disabled'), Model_CustomField::TYPE_CHECKBOX, true),
			self::LABEL => new DevblocksSearchField(self::LABEL, 'agent_model_router', 'label', $translate->_('common.label'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'agent_model_router', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'agent_model_router', 'updated_at', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),
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

class Model_AgentModelRouter extends DevblocksRecordModel {
	public $created_at;
	public $description;
	public $id;
	public $is_default;
	public $is_disabled;
	public $label;
	public $models_kata;
	public $name;
	public $updated_at;

	/**
	 * The ranked model map this router offers, in the exact shape `agentPrompt: models:` and
	 * `llm.agent: model:` already consume: `{<key> => <overrides>}` in author order.
	 *
	 * **The `models:` wrapper is implied.** The whole document IS the list, so it starts at model names -- one
	 * less level of indentation to author and one less thing to get wrong. Nothing else lives in this field.
	 *
	 * Keys are `agent_model` NAMES, optionally `<name>/<alias>` to mount the same record more than once with
	 * different auth/endpoint/knobs. The record name is ALWAYS the first segment, so an alias can never drift to
	 * a different record (and therefore a different provider) -- which is why the alias lives in the key rather
	 * than as a reference inside the entry. (`model:` inside an entry already means the provider's model string.)
	 *
	 * Entries naming a missing or disabled record are dropped here, so a caller never has to re-check: this
	 * returns only what can actually run. Order is preserved, including for aliases.
	 *
	 * @param DevblocksDictionaryDelegate|null $dict evaluates `{{...}}` in the doc (e.g. `disabled@bool:`)
	 * @return array `{<key> => <overrides>}`; empty when nothing resolves
	 */
	public function getModels(?DevblocksDictionaryDelegate $dict=null, ?string &$error=null) : array {
		if('' === trim(strval($this->models_kata)))
			return [];

		$kata = DevblocksPlatform::services()->kata();

		if(false === ($models = $kata->parse($this->models_kata, $error)))
			return [];

		// `@bool`/`@int` annotations are applied by formatTree(), not parse() -- without it a `disabled@bool: yes`
		// would arrive as the string "yes" under the key `disabled@bool`. Passing a dict also resolves any
		// `{{...}}` in the doc; without one they stay literal.
		if(false === ($models = $kata->formatTree($models, $dict, $error)))
			return [];

		if(!is_array($models))
			return [];

		$out = [];

		foreach($models as $key => $overrides) {
			$key = strval($key);
			$overrides = is_array($overrides) ? $overrides : [];

			// `<name>/<alias>` -- the record name is the part before the slash. Same `type/name` split the
			// automation engine uses for node names.
			$model_name = DevblocksPlatform::services()->string()->strBefore($key, '/') ?: $key;

			if(!($record = DAO_AgentModel::getByName($model_name)))
				continue;

			// Routers offer Available models only. Unlisted is reachable by naming it, not by routing;
			// disabled is refused everywhere. Entries stay in the doc either way, so position survives.
			if(!$record->isAvailable())
				continue;

			$out[$key] = $overrides;
		}

		return $out;
	}

	/** The name a reader should see. `name` is the URI handle; `label` is the friendly form. */
	public function getDisplayName() : string {
		return trim(strval($this->label)) ?: strval($this->name);
	}

	/**
	 * A router reads as a ROUTER everywhere it's listed -- a monogram of its initials says nothing, and these
	 * are picked from a chooser where the glyph is the fastest way to know what kind of thing you're looking at.
	 * A derived per-record mark, so no `context_avatar` row and no icon column (see `references/avatars.md`).
	 */
	public function getDisplayIcon() : string {
		return 'bot-route';
	}

	/**
	 * A stable color per router, so two routers stay visually distinct in a list. Same derivation the monogram
	 * avatars use (`Page_Avatars::renderMonogram`): seed on a hash of the identity, then three channels in
	 * 25..180 -- which keeps it dark enough to read against a light chip.
	 *
	 * `mt_srand()` with no argument afterward is NOT optional: leaving the generator seeded would make every
	 * later mt_rand() in the request deterministic.
	 */
	public function getDisplayIconColor() : string {
		mt_srand(crc32(strval($this->name)));
		$rgb = [mt_rand(25,180), mt_rand(25,180), mt_rand(25,180)];
		mt_srand();

		return sprintf('#%02x%02x%02x', $rgb[0], $rgb[1], $rgb[2]);
	}
};

class View_AgentModelRouter extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'agentmodelrouter';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('Agent Model Router');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_AgentModelRouter::NAME;
		$this->renderSortAsc = true;

		// The at-a-glance answer to "which routers exist and which one runs by default". `models_kata` is a
		// multi-line document -- it reads as noise in a cell, so it's available but off by default.
		$this->view_columns = [
			SearchFields_AgentModelRouter::NAME,
			SearchFields_AgentModelRouter::DESCRIPTION,
			SearchFields_AgentModelRouter::IS_DEFAULT,
			SearchFields_AgentModelRouter::IS_DISABLED,
			SearchFields_AgentModelRouter::UPDATED_AT,
		];

		$this->addColumnsHidden([
			SearchFields_AgentModelRouter::ID,
		]);

		$this->doResetCriteria();
	}

	// The glyph/color for a worklist ROW. Rows are raw search arrays, not models, and Model_AgentModelRouter
	// isn't in Smarty's static-class allowlist -- so the template calls these on $view and the derivation stays
	// defined in one place. Mirrors View_AgentModel.
	function getRowIcon($row) : string {
		return 'bot-route';
	}

	function getRowIconColor($row) : string {
		$model = new Model_AgentModelRouter();
		$model->name = strval($row[SearchFields_AgentModelRouter::NAME] ?? '');
		return $model->getDisplayIconColor();
	}

	protected function _getData() {
		return DAO_AgentModelRouter::search(
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
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_AgentModelRouter');
		return $objects;
	}

	function getDataAsObjects($ids=null, &$total=null) {
		return $this->_getDataAsObjects('DAO_AgentModelRouter', $ids, $total);
	}

	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_AgentModelRouter', $size);
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
		$context = Context_AgentModelRouter::ID;

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
		$search_fields = SearchFields_AgentModelRouter::getFields();

		$fields = [
			'created' => [
				'type' => DevblocksSearchCriteria::TYPE_DATE,
				'options' => ['param_key' => SearchFields_AgentModelRouter::CREATED_AT],
			],
			'fieldset' => [
				'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
				'options' => ['param_key' => DevblocksSearchField::VIRTUAL_HAS_FIELDSET],
				'examples' => [
					['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . Context_AgentModelRouter::ID],
				]
			],
			'id' => [
				'type' => DevblocksSearchCriteria::TYPE_NUMBER,
				'options' => ['param_key' => SearchFields_AgentModelRouter::ID],
				'examples' => [
					['type' => 'chooser', 'context' => Context_AgentModelRouter::ID, 'q' => ''],
				]
			],
			'name' => [
				'type' => DevblocksSearchCriteria::TYPE_TEXT,
				'options' => ['param_key' => SearchFields_AgentModelRouter::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
			],
			'updated' => [
				'type' => DevblocksSearchCriteria::TYPE_DATE,
				'options' => ['param_key' => SearchFields_AgentModelRouter::UPDATED_AT],
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
		$fields = self::_appendFieldsFromQuickSearchContext(Context_AgentModelRouter::ID, $fields, null);
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

		$custom_fields = DAO_CustomField::getByContext(Context_AgentModelRouter::ID);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::records/types/agent_model_router/view.tpl');
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
		return SearchFields_AgentModelRouter::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_AgentModelRouter::CREATED_AT:
			case SearchFields_AgentModelRouter::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;

			case SearchFields_AgentModelRouter::ID:
			case SearchFields_AgentModelRouter::IS_DEFAULT:
			case SearchFields_AgentModelRouter::IS_DISABLED:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;

			case SearchFields_AgentModelRouter::DESCRIPTION:
			case SearchFields_AgentModelRouter::LABEL:
			case SearchFields_AgentModelRouter::NAME:
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

class Context_AgentModelRouter extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek, IDevblocksContextAutocomplete {
	const ID = 'cerb.contexts.agent.model.router';
	const URI = 'agent_model_router';

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
		return DAO_AgentModelRouter::random();
	}

	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';

		$url_writer = DevblocksPlatform::services()->url();
		return $url_writer->writeNoProxy('c=profiles&type=agent_model_router&id='.$context_id, true);
	}

	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];

		if(is_null($model))
			$model = new Model_AgentModelRouter();

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

		$properties['id'] = [
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		];

		$properties['is_default'] = [
			'label' => mb_ucfirst($translate->_('common.default')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->is_default,
		];

		$properties['is_disabled'] = [
			'label' => mb_ucfirst($translate->_('common.disabled')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->is_disabled,
		];

		$properties['label'] = [
			'label' => mb_ucfirst($translate->_('common.label')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->label,
		];

		$properties['models_kata'] = [
			'label' => mb_ucfirst($translate->_('common.models')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->models_kata,
		];

		$properties['updated'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		];

		return $properties;
	}

	/**
	 * Without this a `CerbUI.RecordChooser` popup opens with a search box that only ever shows "…" -- the
	 * endpoint returns [] unless the context implements IDevblocksContextAutocomplete.
	 */
	function autocomplete($term, $query=null) {
		$list = [];

		foreach(DAO_AgentModelRouter::autocomplete($term) as $id => $model) {
			$entry = new stdClass();
			$entry->label = $model->getDisplayName();
			$entry->value = sprintf("%d", $id);
			$list[] = $entry;
		}

		return $list;
	}

	function getMeta($context_id) {
		if(null == ($agent_model_router = DAO_AgentModelRouter::get($context_id)))
			return [];

		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($agent_model_router->name);

		if(!empty($friendly))
			$url .= '-' . $friendly;

		return [
			'id' => $agent_model_router->id,
			'name' => $agent_model_router->name,
			'permalink' => $url,
			'updated' => $agent_model_router->updated_at,
			'created' => $agent_model_router->created_at,
			// A router reads as a router, not as its initials
			'icon' => $agent_model_router->getDisplayIcon(),
			'icon_color' => $agent_model_router->getDisplayIconColor(),
		];
	}

	function getDefaultProperties() : array {
		return [
			'label',
			'description',
			'is_default',
			'is_disabled',
			'updated_at',
		];
	}

	function getContext($agent_model_router, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Agent Model Router:';

		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(Context_AgentModelRouter::ID);

		if(is_numeric($agent_model_router)) {
			$agent_model_router = DAO_AgentModelRouter::get($agent_model_router);
		} elseif($agent_model_router instanceof Model_AgentModelRouter) {
			DevblocksPlatform::noop();
		} elseif(is_array($agent_model_router)) {
			$agent_model_router = Cerb_ORMHelper::recastArrayToModel($agent_model_router, 'Model_AgentModelRouter');
		} else {
			$agent_model_router = null;
		}

		$token_labels = [
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'created_at' => $prefix.$translate->_('common.created'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'record_url' => $prefix.$translate->_('common.url.record'),
			'description' => $prefix.$translate->_('common.description'),
			'is_default' => $prefix.$translate->_('common.default'),
			'is_disabled' => $prefix.$translate->_('common.disabled'),
			'label' => $prefix.$translate->_('common.label'),
			'models_kata' => $prefix.$translate->_('common.models'),
		];

		$token_types = [
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'created_at' => Model_CustomField::TYPE_DATE,
			'updated_at' => Model_CustomField::TYPE_DATE,
			'record_url' => Model_CustomField::TYPE_URL,
			'description' => Model_CustomField::TYPE_SINGLE_LINE,
			'is_default' => Model_CustomField::TYPE_SINGLE_LINE,
			'is_disabled' => Model_CustomField::TYPE_SINGLE_LINE,
			'label' => Model_CustomField::TYPE_SINGLE_LINE,
			'models_kata' => Model_CustomField::TYPE_SINGLE_LINE,
		];

		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);

		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);

		$token_values = [];
		$token_values['_context'] = Context_AgentModelRouter::ID;
		$token_values['_type'] = 'agent_model_router';
		$token_values['_types'] = $token_types;

		if($agent_model_router) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $agent_model_router->name;
			// The per-record glyph the shared card/profile chrome reads via $context_ext->getIcon($dict).
			// Precedence there is: context avatar image -> `_icon`/`_icon_color` -> the manifest icon. Setting
			// these is what replaces the initials monogram with the router mark.
			$token_values['_icon'] = $agent_model_router->getDisplayIcon();
			$token_values['_icon_color'] = $agent_model_router->getDisplayIconColor();
			$token_values['id'] = $agent_model_router->id;
			$token_values['name'] = $agent_model_router->name;
			$token_values['created_at'] = $agent_model_router->created_at;
			$token_values['updated_at'] = $agent_model_router->updated_at;
			$token_values['description'] = $agent_model_router->description;
			$token_values['is_default'] = $agent_model_router->is_default;
			$token_values['is_disabled'] = $agent_model_router->is_disabled;
			$token_values['label'] = $agent_model_router->label;
			$token_values['models_kata'] = $agent_model_router->models_kata;
			$token_values = $this->_importModelCustomFieldsAsValues($agent_model_router, $token_values);

			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(
				sprintf("c=profiles&type=agent_model_router&id=%d-%s", $agent_model_router->id, DevblocksPlatform::strToPermalink($agent_model_router->name)), true
			);
		}

		return true;
	}

	function getKeyToDaoFieldMap() {
		return [
			'created_at' => DAO_AgentModelRouter::CREATED_AT,
			'description' => DAO_AgentModelRouter::DESCRIPTION,
			'id' => DAO_AgentModelRouter::ID,
			'is_default' => DAO_AgentModelRouter::IS_DEFAULT,
			'is_disabled' => DAO_AgentModelRouter::IS_DISABLED,
			'label' => DAO_AgentModelRouter::LABEL,
			'models_kata' => DAO_AgentModelRouter::MODELS_KATA,
			'name' => DAO_AgentModelRouter::NAME,
			'updated_at' => DAO_AgentModelRouter::UPDATED_AT,
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

		$context = Context_AgentModelRouter::ID;
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
		$view->name = 'Agent Model Router';
		$view->renderSortBy = SearchFields_AgentModelRouter::UPDATED_AT;
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
		$view->name = 'Agent Model Router';

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
		$context = Context_AgentModelRouter::ID;

		$tpl->assign('view_id', $view_id);

		$model = null;

		if($context_id) {
			if(!($model = DAO_AgentModelRouter::get($context_id)))
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

			// Autocomplete comes from the LLM service, which builds it from the live `agent_model` records -- so a
			// newly added model shows up here on the next popup with no change to this form.
			$tpl->assign('models_autocomplete_json', json_encode(self::_getModelsAutocomplete()));

			// A NEW router starts prefilled with every enabled model, so nobody has to type a list they already
			// have -- reorder with Alt+Up/Down and drop with Alt+D (KataEditor's own bindings). Only for new
			// records: prefilling an existing one would silently re-add models its author deliberately removed.
			if(!$context_id)
				$tpl->assign('models_kata_default', self::_getEnabledModelsKata());

			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::records/types/agent_model_router/peek_edit.tpl');

		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}

	/**
	 * The `models_kata` editor's suggestion map, keyed by path from the document root.
	 *
	 * Rooted at `''` because the `models:` wrapper is IMPLIED here -- the document is the list, so typing at the
	 * top level immediately suggests model names rather than the one key that would always be first.
	 *
	 * `getKataAgentModelAutocomplete()` is the same helper `llm.agent:`/`llm.chat: model:` and the agentPrompt
	 * catalog use, so the grammar offered here is identical to the grammar those commands accept -- and a model
	 * added later appears with no change to this form.
	 */
	private static function _getModelsAutocomplete() : array {
		// Prefix '' because the `models:` wrapper is implied here -- the document IS the list, so the model
		// names sit at the root rather than one level down.
		$map = DevblocksPlatform::services()->llm()->getKataAgentModelAutocomplete('');

		// The name list is an EXACT path (the document root).
		$root = $map[''] ?? [];
		unset($map['']);

		// Everything else is REGEX-keyed -- model names are preg_quote'd (a literal `.`/`-` would otherwise
		// over-match) and the `<name>/<alias>` form adds an optional segment. `kataFieldSource()` only applies
		// regex matching inside the `'*'` bucket; keys at the top level are compared literally, so these have to
		// live under `'*'` or they never match.
		return [
			'' => $root,
			'*' => $map,
		];
	}

	/**
	 * Every enabled model as a starting document, in name order -- what a NEW router opens with.
	 *
	 * The point is that nobody types a list they already have: open the editor, reorder with Alt+Up/Down, drop
	 * what you don't want with Alt+D, save. Bare keys with no overrides, because the record already supplies
	 * provider/model/auth and an override is the exception.
	 */
	private static function _getEnabledModelsKata() : string {
		$out = '';

		foreach(DAO_AgentModel::getAll() as $model) {
			if(!$model->isAvailable())
				continue;

			$out .= sprintf("%s:\n", $model->name);
		}

		return $out;
	}
};
