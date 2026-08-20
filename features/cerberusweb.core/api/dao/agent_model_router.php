<?php
class DAO_AgentModelRouter extends Cerb_ORMHelper {
	const CREATED_AT = 'created_at';
	const DESCRIPTION = 'description';
	const ID = 'id';
	const LABEL = 'label';
	const MODELS_KATA = 'models_kata';
	const MODELS_QUERY = 'models_query';
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
		// The `agent_model` search that decides membership. Validated by RUNNING it through the same parser
		// the worklist uses, so `hasVison:y` fails here rather than silently matching nothing at the first turn.
		$validation
			->addField(self::MODELS_QUERY)
			->string()
			->setMaxLength(65535)
			->addValidator(function($value, &$error=null) {
				if('' === trim(strval($value)))
					return true;

				if(!($context_ext = Extension_DevblocksContext::get(Context_AgentModel::ID, true)))
					return true;

				$view = $context_ext->getTempView();

				if(false === $view->getParamsFromQuickSearch($value, [], $query_error)) {
					$error = 'must be a valid agent model search: ' . $query_error;
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

		return $id;
	}

	/** The name that IS the default. No flag column: uniqueness on `name` already guarantees one. */
	const DEFAULT_NAME = 'default';

	/**
	 * The router that resolves when nothing names one -- the record literally named `default`.
	 *
	 * There is no `is_default` column. A single-winner flag needs a clear-all-then-set write, an auto-flag on
	 * the first insert, and an auto-promote on delete, and it can still be edited into a state with none or
	 * two. A unique name can't. Deleting `default` is a missing default and errors clearly, which beats
	 * silently promoting an unrelated router whose policy nobody chose.
	 *
	 * @return Model_AgentModelRouter|null
	 */
	static function getDefault() {
		return self::getByName(self::DEFAULT_NAME);
	}

	/**
	 * Type-to-search for record choosers. Prefix match on `name` (house convention -- see
	 * `references/record-choosers.md`); an empty term returns the first 25, so the picker opens populated.
	 *
	 */
	static function autocomplete($term, $as='models') {
		$db = DevblocksPlatform::services()->database();
		$ids = [];

		$results = $db->GetArrayReader(sprintf("SELECT id ".
			"FROM agent_model_router ".
			"WHERE name LIKE %s ".
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

			self::clearQueryModelsCache();

			if($check_deltas) {
				DevblocksPlatform::markContextChanged($context, $batch_ids);
			}
		}
	}

	private const _CACHE_QUERY_MODELS = 'cerb:agent_model_router:query_models';

	/**
	 * The `agent_model` NAMES a router's `models_query` matches, in the query's own sort order.
	 *
	 * Cached as one map of `{router_id => [name, ...]}` -- names rather than models, because getModels()
	 * re-reads each record anyway to check its status. Busted by CRUD on either record type; the TTL is
	 * insurance for a query with a time-dependent term (`updated:`, and later `usage:`), which no
	 * write-triggered invalidation can catch.
	 *
	 * @return string[]
	 */
	static function getQueryModelNames(int $router_id) : array {
		$cache = DevblocksPlatform::services()->cache();

		if(!is_array($map = $cache->load(self::_CACHE_QUERY_MODELS)))
			$map = [];

		if(array_key_exists($router_id, $map))
			return $map[$router_id];

		$router = self::get($router_id);
		$names = $router ? self::resolveQueryModelNames(strval($router->models_query)) : [];


		$map[$router_id] = $names;
		$cache->save($map, self::_CACHE_QUERY_MODELS, [], 300);

		return $names;
	}

	/**
	 * Run an `agent_model` query and return the matching NAMES in its own sort order.
	 *
	 * **The query is OPTIONAL.** Blank means every available model -- the zero-config router, and what makes
	 * a fresh `default` work with nothing typed into it. Narrowing is the opt-in, not the baseline.
	 *
	 * Uncached on purpose: the editor's preview calls this with an UNSAVED query, so it has to reflect what's
	 * on screen rather than what's stored.
	 *
	 * @return string[]
	 */
	static function resolveQueryModelNames(string $query, ?string &$error=null) : array {
		if(!($context_ext = Extension_DevblocksContext::get(Context_AgentModel::ID, true)))
			return [];

		$view = $context_ext->getTempView();

		// Membership is enforced HERE, not in each router's query, so no query author can forget it and no
		// shipped router has to mention status.
		$view->addParamsRequiredWithQuickSearch(sprintf('status.id:%d', DAO_AgentModel::STATUS_AVAILABLE), true);

		if('' !== trim($query) && !$view->addParamsWithQuickSearch($query, true, [], $error))
			return [];

		// Every match, unpaged. The query's own `sort:` (if any) already set renderSortBy.
		$view->renderLimit = 0;
		$view->renderTotal = false;
		$view->renderSubtotals = null;

		list($models,) = $view->getData();

		$names = [];

		foreach($models as $row)
			$names[] = strval($row[SearchFields_AgentModel::NAME] ?? '');

		return array_values(array_filter($names));
	}

	static function clearQueryModelsCache() : void {
		DevblocksPlatform::services()->cache()->remove(self::_CACHE_QUERY_MODELS);
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

		$sql = "SELECT created_at, description, id, label, models_kata, models_query, name, updated_at " .
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
			$object->label = $row['label'];
			$object->models_kata = $row['models_kata'];
			$object->models_query = $row['models_query'];
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

		self::clearQueryModelsCache();

		parent::_deleteAbstractAfter($context, $ids);

		return true;
	}

	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_AgentModelRouter::getFields();

		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_AgentModelRouter', $sortBy);

		$select_sql = sprintf("SELECT " .
			"agent_model_router.created_at as %s, " .
			"agent_model_router.description as %s, " .
			"agent_model_router.id as %s, " .
			"agent_model_router.label as %s, " .
			"agent_model_router.models_query as %s, " .
			"agent_model_router.name as %s, " .
			"agent_model_router.updated_at as %s",
			SearchFields_AgentModelRouter::CREATED_AT,
			SearchFields_AgentModelRouter::DESCRIPTION,
			SearchFields_AgentModelRouter::ID,
			SearchFields_AgentModelRouter::LABEL,
			SearchFields_AgentModelRouter::MODELS_QUERY,
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
	const LABEL = 'a_label';
	const MODELS_QUERY = 'a_models_query';
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
			self::LABEL => new DevblocksSearchField(self::LABEL, 'agent_model_router', 'label', $translate->_('common.label'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::MODELS_QUERY => new DevblocksSearchField(self::MODELS_QUERY, 'agent_model_router', 'models_query', $translate->_('common.query'), Model_CustomField::TYPE_SINGLE_LINE, true),
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
	public $label;
	public $models_kata;
	public $models_query;
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
		$overrides = $this->_getKataOverrides($dict, $error);

		// Membership is ALWAYS the query. Blank matches every available model, so a router with nothing
		// configured still resolves -- `models_kata` only decorates what the query already found.
		$names = DAO_AgentModelRouter::getQueryModelNames($this->id);

		$out = [];

		foreach($names as $name) {
			$name = strval($name);

			// `<name>/<alias>` mounts one record more than once. A query yields each record once, so an
			// aliased entry is the only additive form the KATA still contributes.
			$record_name = DevblocksPlatform::services()->string()->strBefore($name, '/') ?: $name;

			if(!($record = DAO_AgentModel::getByName($record_name)))
				continue;

			// Re-checked against the RECORD every time, so a cached name can never resurrect a model that
			// has since been unlisted or disabled.
			if(!$record->isAvailable())
				continue;

			$out[$name] = $overrides[$name] ?? [];
		}

		// Aliased entries can't come from a query; carry them over so multi-mount survives one.
		foreach($overrides as $key => $params) {
			if(isset($out[$key]) || !str_contains($key, '/'))
				continue;

			$record_name = DevblocksPlatform::services()->string()->strBefore($key, '/') ?: $key;

			if(($record = DAO_AgentModel::getByName($record_name)) && $record->isAvailable())
				$out[$key] = $params;
		}

		return $out;
	}

	/**
	 * `models_kata` parsed to `{<key> => <overrides>}`. With a query set these are overrides only -- an entry
	 * naming a model the query didn't match contributes nothing (except an alias, see getModels()).
	 */
	private function _getKataOverrides(?DevblocksDictionaryDelegate $dict=null, ?string &$error=null) : array {
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

		foreach($models as $key => $params)
			$out[strval($key)] = is_array($params) ? $params : [];

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
			SearchFields_AgentModelRouter::MODELS_QUERY,
			SearchFields_AgentModelRouter::DESCRIPTION,
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
			'query' => [
				'type' => DevblocksSearchCriteria::TYPE_TEXT,
				'options' => ['param_key' => SearchFields_AgentModelRouter::MODELS_QUERY, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
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
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;

			case SearchFields_AgentModelRouter::DESCRIPTION:
			case SearchFields_AgentModelRouter::LABEL:
			case SearchFields_AgentModelRouter::MODELS_QUERY:
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

		$properties['label'] = [
			'label' => mb_ucfirst($translate->_('common.label')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->label,
		];

		$properties['models_query'] = [
			'label' => mb_ucfirst($translate->_('common.query')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->models_query,
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
			'label' => $prefix.$translate->_('common.label'),
			'models_kata' => $prefix.$translate->_('common.models'),
			'models_query' => $prefix.$translate->_('common.query'),
		];

		$token_types = [
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'created_at' => Model_CustomField::TYPE_DATE,
			'updated_at' => Model_CustomField::TYPE_DATE,
			'record_url' => Model_CustomField::TYPE_URL,
			'description' => Model_CustomField::TYPE_SINGLE_LINE,
			'label' => Model_CustomField::TYPE_SINGLE_LINE,
			'models_kata' => Model_CustomField::TYPE_SINGLE_LINE,
			'models_query' => Model_CustomField::TYPE_SINGLE_LINE,
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
			$token_values['label'] = $agent_model_router->label;
			$token_values['models_kata'] = $agent_model_router->models_kata;
			$token_values['models_query'] = $agent_model_router->models_query;
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
			'label' => DAO_AgentModelRouter::LABEL,
			'models_kata' => DAO_AgentModelRouter::MODELS_KATA,
			'models_query' => DAO_AgentModelRouter::MODELS_QUERY,
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

};
