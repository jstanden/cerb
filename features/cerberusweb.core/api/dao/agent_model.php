<?php
class DAO_AgentModel extends Cerb_ORMHelper {
	const API_ENDPOINT_URL = 'api_endpoint_url';
	const CONNECTED_ACCOUNT_ID = 'connected_account_id';
	const CONTEXT_WINDOW = 'context_window';
	const CREATED_AT = 'created_at';
	const DESCRIPTION = 'description';
	const HAS_VISION = 'has_vision';
	const ICON = 'icon';
	const ICON_COLOR = 'icon_color';
	const ID = 'id';
	const IS_DISABLED = 'is_disabled';
	const LABEL = 'label';
	const MODEL = 'model';
	const NAME = 'name';
	const PARAMS_KATA = 'params_kata';
	const PROVIDER = 'provider';
	const UPDATED_AT = 'updated_at';

	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();

		// A per-model endpoint override, lifted ahead of Model in the editor so it feeds the model-list fetch
		// and the connection test. Blank means "(auto)" -- use the provider's own default endpoint. It WINS
		// over any `api_endpoint_url:` left in `params_kata`.
		$validation
			->addField(self::API_ENDPOINT_URL)
			->url()
			;
		// The credentials this model authenticates with. Optional: a local provider (ollama, docker) needs none.
		$validation
			->addField(self::CONNECTED_ACCOUNT_ID)
			->id()
			->setNotEmpty(false)
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, true))
			;
		$validation
			->addField(self::CONTEXT_WINDOW)
			->uint()
			;
		$validation
			->addField(self::CREATED_AT)
			->timestamp()
			;
		$validation
			->addField(self::DESCRIPTION)
			->string()
			;
		$validation
			->addField(self::HAS_VISION)
			->bit()
			;
		// The brand mark this model reads as, overriding its provider's. Most models arrive over the
		// OpenAI-compatible API (llama.cpp, z.ai, Qwen, LM Studio), so the provider id can't name the vendor.
		// Blank falls back to the provider's own icon. Constrained to the shipped glyph set: an unknown name
		// renders as an empty box, and the value is interpolated into a class attribute.
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
		// The avatar's brand background. This lands inside a `style=` attribute, where the template layer's
		// htmlspecialchars does NOT stop CSS-context injection (`red;background-image:url(...)` needs no
		// quotes) -- so it's pinned to a strict hex, never free text.
		$validation
			->addField(self::ICON_COLOR)
			->string()
			->setMaxLength(32)
			->addValidator(function($value, &$error=null) use ($validation) {
				if('' === $value)
					return true;

				return ($validation->validators()->colorHex())($value, $error);
			})
			;
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		$validation
			->addField(self::IS_DISABLED)
			->bit()
			;
		// A friendly display name for pickers -- `name` is the URI handle (`glm-4.6`), this is what a reader
		// should see ("GLM 4.6"). Blank falls back to `name`.
		$validation
			->addField(self::LABEL)
			->string()
			->setMaxLength(128)
			;
		// The provider's model string (e.g. `claude-sonnet-5`). Free text on purpose -- providers ship new
		// model ids constantly, and a whitelist would block the day they land.
		$validation
			->addField(self::MODEL)
			->string()
			->setMaxLength(255)
			;
		// `name` IS the uri: `cerb:agent_model:<name>` is how automations reference a model, so it's
		// identifier-shaped and unique. Same rule as `agent_filesystem.name`, plus underscore.
		$validation
			->addField(self::NAME)
			->string()
			->setMaxLength(128)
			->setRequired(true)
			->setUnique(__CLASS__)
			->addValidator(function($value, &$error=null) {
				if(!preg_match('/^[A-Za-z0-9_.-]+$/', $value)) {
					$error = "must only contain letters, numbers, dots, dashes, and underscores (it's the model's URI).";
					return false;
				}
				
				return true;
			})
			;
		// The open tail of the `llm:<provider>:` block (cache/effort/thinking/compaction/...), in that same
		// grammar -- the block is deliberately open, so these can't be columns without a migration per knob.
		$validation
			->addField(self::PARAMS_KATA)
			->string()
			->setMaxLength(65535)
			;
		// An LLM provider extension id (`anthropic`, `openai`, ...) -- the `llm:<provider>:` block key.
		$validation
			->addField(self::PROVIDER)
			->string()
			->setMaxLength(32)
			->setRequired(true)
			->addValidator(function($value, &$error=null) {
				if(!in_array($value, DevblocksPlatform::services()->llm()->getProviderIds())) {
					$error = sprintf("must be a known LLM provider (%s).", implode(', ', DevblocksPlatform::services()->llm()->getProviderIds()));
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

		$sql = "INSERT INTO agent_model () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();

		if(!isset($fields[self::CREATED_AT]))
			$fields[self::CREATED_AT] = time();

		CerberusContexts::checkpointCreations(Context_AgentModel::ID, $id);

		self::update($id, $fields);

		return $id;
	}

	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = [$ids];

		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();

		$context = Context_AgentModel::ID;
		self::_updateAbstract($context, $ids, $fields);

		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;

			if($check_deltas) {
				CerberusContexts::checkpointChanges($context, $batch_ids);
			}

			parent::_update($batch_ids, 'agent_model', $fields);

			if($check_deltas) {
				DevblocksPlatform::markContextChanged($context, $batch_ids);
			}
		}
	}

	static function updateWhere($fields, $where) {
		parent::_updateWhere('agent_model', $fields, $where);
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
	 * @return Model_AgentModel[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);

		$sql = "SELECT api_endpoint_url, connected_account_id, context_window, created_at, description, has_vision, icon, icon_color, id, is_disabled, label, model, name, params_kata, provider, updated_at " .
			"FROM agent_model " .
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
	 * @return Model_AgentModel[]
	 */
	static function getAll($nocache=false) {
		$objects = self::getWhere(null, self::NAME, true, null, DevblocksORMHelper::OPT_GET_MASTER_ONLY);
		return $objects;
	}

	/**
	 * @param integer $id
	 * @return Model_AgentModel|null
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
	 * Resolve a model by its `name` -- which IS its uri (`cerb:agent_model:<name>`), so this is where
	 * every reference form ends up.
	 *
	 * @return Model_AgentModel|null
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

	/**
	 * @param array $ids
	 * @return Model_AgentModel[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}

	/**
	 * @param mysqli_result|false $rs
	 * @return Model_AgentModel[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];

		if(!($rs instanceof mysqli_result))
			return [];

		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_AgentModel();
			$object->api_endpoint_url = $row['api_endpoint_url'];
			$object->connected_account_id = intval($row['connected_account_id']);
			$object->context_window = intval($row['context_window']);
			$object->created_at = intval($row['created_at']);
			$object->description = $row['description'];
			$object->has_vision = intval($row['has_vision']);
			$object->icon = $row['icon'];
			$object->icon_color = $row['icon_color'];
			$object->id = intval($row['id']);
			$object->is_disabled = intval($row['is_disabled']);
			$object->label = $row['label'];
			$object->model = $row['model'];
			$object->name = $row['name'];
			$object->params_kata = $row['params_kata'];
			$object->provider = $row['provider'];
			$object->updated_at = intval($row['updated_at']);
			$objects[$object->id] = $object;
		}

		mysqli_free_result($rs);

		return $objects;
	}

	static function random() {
		return self::_getRandom('agent_model');
	}

	static function delete($ids) {
		$db = DevblocksPlatform::services()->database();

		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');

		if(empty($ids)) return false;

		$context = Context_AgentModel::ID;
		$ids_list = implode(',', self::qstrArray($ids));

		parent::_deleteAbstractBefore($context, $ids);

		$db->ExecuteMaster(sprintf("DELETE FROM agent_model WHERE id IN (%s)", $ids_list));

		parent::_deleteAbstractAfter($context, $ids);

		return true;
	}

	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_AgentModel::getFields();

		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_AgentModel', $sortBy);

		$select_sql = sprintf("SELECT " .
			"agent_model.api_endpoint_url as %s, " .
			"agent_model.connected_account_id as %s, " .
			"agent_model.context_window as %s, " .
			"agent_model.created_at as %s, " .
			"agent_model.description as %s, " .
			"agent_model.has_vision as %s, " .
			"agent_model.icon as %s, " .
			"agent_model.icon_color as %s, " .
			"agent_model.id as %s, " .
			"agent_model.is_disabled as %s, " .
			"agent_model.label as %s, " .
			"agent_model.model as %s, " .
			"agent_model.name as %s, " .
			"agent_model.provider as %s, " .
			"agent_model.updated_at as %s",
			SearchFields_AgentModel::API_ENDPOINT_URL,
			SearchFields_AgentModel::CONNECTED_ACCOUNT_ID,
			SearchFields_AgentModel::CONTEXT_WINDOW,
			SearchFields_AgentModel::CREATED_AT,
			SearchFields_AgentModel::DESCRIPTION,
			SearchFields_AgentModel::HAS_VISION,
			SearchFields_AgentModel::ICON,
			SearchFields_AgentModel::ICON_COLOR,
			SearchFields_AgentModel::ID,
			SearchFields_AgentModel::IS_DISABLED,
			SearchFields_AgentModel::LABEL,
			SearchFields_AgentModel::MODEL,
			SearchFields_AgentModel::NAME,
			SearchFields_AgentModel::PROVIDER,
			SearchFields_AgentModel::UPDATED_AT
		);

		$join_sql = "FROM agent_model ";

		$where_sql =
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ")
		;

		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_AgentModel');

		return [
			'primary_table' => 'agent_model',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		];
	}

	static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		return self::_searchWithTimeout(
			SearchFields_AgentModel::ID,
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

class SearchFields_AgentModel extends DevblocksSearchFields {
	const API_ENDPOINT_URL = 'a_api_endpoint_url';
	const CONNECTED_ACCOUNT_ID = 'a_connected_account_id';
	const CONTEXT_WINDOW = 'a_context_window';
	const CREATED_AT = 'a_created_at';
	const DESCRIPTION = 'a_description';
	const HAS_VISION = 'a_has_vision';
	const ICON = 'a_icon';
	const ICON_COLOR = 'a_icon_color';
	const ID = 'a_id';
	const IS_DISABLED = 'a_is_disabled';
	const LABEL = 'a_label';
	const MODEL = 'a_model';
	const NAME = 'a_name';
	const PROVIDER = 'a_provider';
	const UPDATED_AT = 'a_updated_at';

	const VIRTUAL_CONNECTED_ACCOUNT_SEARCH = '*_connected_account_search';

	static private $_fields = null;

	static function getTableName() : string {
		return 'agent_model';
	}

	static function getPrimaryKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_AgentModel::ID);
	}

	static function getUpdatedKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_AgentModel::UPDATED_AT);
	}

	static function getCreatedKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_AgentModel::CREATED_AT);
	}

	static function getCustomFieldContextKeys() {
		return [
			Context_AgentModel::ID => new DevblocksSearchFieldContextKeys('agent_model.id', self::ID),
		];
	}

	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONNECTED_ACCOUNT_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, 'agent_model.connected_account_id');

			default:
				if(DevblocksPlatform::strStartsWith($param->field, 'cf_')) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					if(null !== ($virtual_where_sql = self::_getWhereSQLForCommonVirtual($param, Context_AgentModel::ID, self::getPrimaryKey())))
						return $virtual_where_sql;

					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
		}
	}

	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case self::CONNECTED_ACCOUNT_ID:
				$models = DAO_ConnectedAccount::getIds($values);
				$label_map = array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');

				// The column is optional -- a local provider (ollama, docker) authenticates with nothing
				if(in_array(0, $values))
					$label_map[0] = DevblocksPlatform::translate('common.none');

				return $label_map;

			case self::ID:
				$models = DAO_AgentModel::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');

			case self::PROVIDER:
				// The vendor names a reader knows ("AWS Bedrock", not `aws_bedrock`). Embedding-only
				// providers (voyage, pinecone) aren't in this map, so they fall back to their raw id.
				$labels = array_column(DevblocksPlatform::services()->llm()->getAgentProviders(), 'label', 'id');
				return array_intersect_key($labels, array_flip($values));
		}

		return parent::getLabelsForKeyValues($key, $values);
	}

	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		switch($key) {
			case 'authentication':
				$key = 'authentication.id';
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
			self::API_ENDPOINT_URL => new DevblocksSearchField(self::API_ENDPOINT_URL, 'agent_model', 'api_endpoint_url', $translate->_('dao.agent_model.api_endpoint_url'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::CONNECTED_ACCOUNT_ID => new DevblocksSearchField(self::CONNECTED_ACCOUNT_ID, 'agent_model', 'connected_account_id', $translate->_('dao.agent_model.connected_account_id'), Model_CustomField::TYPE_NUMBER, true),
			self::CONTEXT_WINDOW => new DevblocksSearchField(self::CONTEXT_WINDOW, 'agent_model', 'context_window', $translate->_('dao.agent_model.context_window'), Model_CustomField::TYPE_NUMBER, true),
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'agent_model', 'created_at', $translate->_('common.created'), Model_CustomField::TYPE_DATE, true),
			self::DESCRIPTION => new DevblocksSearchField(self::DESCRIPTION, 'agent_model', 'description', $translate->_('common.description'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::HAS_VISION => new DevblocksSearchField(self::HAS_VISION, 'agent_model', 'has_vision', $translate->_('dao.agent_model.has_vision'), Model_CustomField::TYPE_CHECKBOX, true),
			self::ICON => new DevblocksSearchField(self::ICON, 'agent_model', 'icon', $translate->_('dao.agent_model.icon'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::ICON_COLOR => new DevblocksSearchField(self::ICON_COLOR, 'agent_model', 'icon_color', $translate->_('dao.agent_model.icon_color'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::ID => new DevblocksSearchField(self::ID, 'agent_model', 'id', $translate->_('common.id'), Model_CustomField::TYPE_NUMBER, true),
			self::IS_DISABLED => new DevblocksSearchField(self::IS_DISABLED, 'agent_model', 'is_disabled', $translate->_('dao.agent_model.is_disabled'), Model_CustomField::TYPE_CHECKBOX, true),
			self::LABEL => new DevblocksSearchField(self::LABEL, 'agent_model', 'label', $translate->_('dao.agent_model.label'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::MODEL => new DevblocksSearchField(self::MODEL, 'agent_model', 'model', $translate->_('dao.agent_model.model'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'agent_model', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::PROVIDER => new DevblocksSearchField(self::PROVIDER, 'agent_model', 'provider', $translate->_('dao.agent_model.provider'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'agent_model', 'updated_at', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),

			self::VIRTUAL_CONNECTED_ACCOUNT_SEARCH => new DevblocksSearchField(self::VIRTUAL_CONNECTED_ACCOUNT_SEARCH, '*', 'connected_account_search', null, null, false),
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

class Model_AgentModel extends DevblocksRecordModel {
	public $api_endpoint_url;
	public $connected_account_id;
	public $context_window;
	public $created_at;
	public $description;
	public $has_vision;
	public $icon;
	public $icon_color;
	public $id;
	public $is_disabled;
	public $label;
	public $model;
	public $name;
	public $params_kata;
	public $provider;
	public $updated_at;

	/**
	 * This model as the `llm:<provider>:` params block an automation would otherwise author inline.
	 *
	 * The typed columns and `params_kata` are already in that grammar, so this is a MERGE, not a
	 * translation: the parsed KATA supplies the open knobs (cache/effort/thinking/compaction/...) and the
	 * columns win over anything the KATA repeats — a column is the field someone actually edited in the form.
	 *
	 * @return array [provider_id, params] — feed straight to `llm()->getProvider()`
	 */
	public function getProviderParams(?string &$error=null) : array {
		$params = [];

		if('' !== trim(strval($this->params_kata))) {
			$kata = DevblocksPlatform::services()->kata();

			if(false === ($parsed = $kata->parse($this->params_kata, $error)))
				return [strval($this->provider), []];

			// `@bool`/`@int` annotations are applied by formatTree(), not parse() -- without it a
			// `cache@bool: yes` would arrive as the string "yes" under the key `cache@bool`.
			if(false === ($parsed = $kata->formatTree($parsed, null, $error)))
				return [strval($this->provider), []];

			if(is_array($parsed))
				$params = $parsed;
		}

		if('' !== trim(strval($this->model)))
			$params['model'] = $this->model;

		// A first-class column wins over any `api_endpoint_url:` in the parsed params_kata -- it's the field
		// someone actually edited in the form. Blank leaves the provider's own default endpoint in place.
		if('' !== trim(strval($this->api_endpoint_url)))
			$params['api_endpoint_url'] = $this->api_endpoint_url;

		if($this->connected_account_id)
			$params['authentication'] = sprintf('cerb:connected_account:%d', $this->connected_account_id);

		if($this->has_vision)
			$params['vision'] = true;

		if($this->context_window > 0)
			$params['context_window'] = intval($this->context_window);

		// Display metadata rides the params bag on purpose: a transcript stores the RESOLVED block, never
		// `agent_model.id`, so this is the only way the vendor a session actually ran survives the record being
		// renamed, re-pointed, or deleted. Providers read `$_params` by key and never enumerate it, so an
		// unknown `display:` is inert on the wire; `_capabilitySignature()` ignores it, so changing an icon
		// mid-session doesn't plant a summary boundary.
		$display = array_filter([
			'name' => trim(strval($this->label)),
			'icon' => trim(strval($this->icon)),
			'icon_color' => trim(strval($this->icon_color)),
		], fn($v) => '' !== $v);

		// array_replace, not assignment: a blank column must contribute nothing (same contract as
		// `api_endpoint_url`), so a `display:` authored in params_kata survives one column being set.
		if($display)
			$params['display'] = array_replace(
				is_array($params['display'] ?? null) ? $params['display'] : [],
				$display
			);

		return [strval($this->provider), $params];
	}

	/** The name a reader should see. `name` is the URI handle (`glm-4.6`); `label` is the friendly form. */
	public function getDisplayName() : string {
		return trim(strval($this->label)) ?: strval($this->name);
	}

	/**
	 * The fallback chain, as statics so the worklist can resolve a raw search row without hydrating a model.
	 * Its own override wins: `provider` can't name the vendor when a model arrives over the OpenAI-compatible
	 * API (llama.cpp, z.ai, Qwen, LM Studio).
	 */
	public static function displayIconFor($icon, $provider) : string {
		return trim(strval($icon))
			?: (DevblocksPlatform::services()->llm()->getProviderIcon(strval($provider)) ?: 'bot-message');
	}

	public static function displayIconColorFor($icon_color, $provider) : string {
		return trim(strval($icon_color))
			?: DevblocksPlatform::services()->llm()->getProviderIconColor(strval($provider));
	}

	/** The brand mark this model reads as, everywhere it's listed. */
	public function getDisplayIcon() : string {
		return self::displayIconFor($this->icon, $this->provider);
	}

	public function getDisplayIconColor() : string {
		return self::displayIconColorFor($this->icon_color, $this->provider);
	}
};

class View_AgentModel extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'agent_models';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('Agent Model');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_AgentModel::NAME;
		$this->renderSortAsc = true;

		// The at-a-glance answer to "what models do we have here": which vendor, which model string, how big a
		// window, and is it live. The rest are available but off by default.
		$this->view_columns = [
			SearchFields_AgentModel::NAME,
			SearchFields_AgentModel::PROVIDER,
			SearchFields_AgentModel::MODEL,
			SearchFields_AgentModel::CONNECTED_ACCOUNT_ID,
			SearchFields_AgentModel::CONTEXT_WINDOW,
			SearchFields_AgentModel::HAS_VISION,
			SearchFields_AgentModel::IS_DISABLED,
			SearchFields_AgentModel::UPDATED_AT,
		];

		$this->addColumnsHidden([
			SearchFields_AgentModel::ID,
			SearchFields_AgentModel::VIRTUAL_CONNECTED_ACCOUNT_SEARCH,
		]);

		$this->doResetCriteria();
	}

	// The glyph/color for a worklist ROW. Rows are raw search arrays, not models, and Model_AgentModel isn't in
	// Smarty's static-class allowlist — so the template calls these on $view and the fallback chain stays
	// defined in exactly one place.
	function getRowIcon($row) : string {
		return Model_AgentModel::displayIconFor(
			$row[SearchFields_AgentModel::ICON] ?? '',
			$row[SearchFields_AgentModel::PROVIDER] ?? ''
		);
	}

	function getRowIconColor($row) : string {
		return Model_AgentModel::displayIconColorFor(
			$row[SearchFields_AgentModel::ICON_COLOR] ?? '',
			$row[SearchFields_AgentModel::PROVIDER] ?? ''
		);
	}

	protected function _getData() {
		return DAO_AgentModel::search(
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
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_AgentModel');
		return $objects;
	}

	function getDataAsObjects($ids=null, &$total=null) {
		return $this->_getDataAsObjects('DAO_AgentModel', $ids, $total);
	}

	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_AgentModel', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;

			switch($field_key) {
				// Low-cardinality only. `model`, `label`, and `description` are near-unique per row,
				// so grouping by them would just re-list the worklist.
				case SearchFields_AgentModel::API_ENDPOINT_URL:
				case SearchFields_AgentModel::CONNECTED_ACCOUNT_ID:
				case SearchFields_AgentModel::CONTEXT_WINDOW:
				case SearchFields_AgentModel::HAS_VISION:
				case SearchFields_AgentModel::IS_DISABLED:
				case SearchFields_AgentModel::PROVIDER:
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
		$context = Context_AgentModel::ID;

		if(!array_key_exists($column, $fields))
			return [];

		switch($column) {
			case SearchFields_AgentModel::HAS_VISION:
			case SearchFields_AgentModel::IS_DISABLED:
				$counts = $this->_getSubtotalCountForBooleanColumn($context, $column);
				break;

			case SearchFields_AgentModel::API_ENDPOINT_URL:
				$counts = $this->_getSubtotalCountForStringColumn($context, $column);
				break;

			case SearchFields_AgentModel::PROVIDER:
				$label_map = function(array $values) use ($column) {
					return SearchFields_AgentModel::getLabelsForKeyValues($column, $values);
				};
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map);
				break;

			case SearchFields_AgentModel::CONNECTED_ACCOUNT_ID:
				$label_map = function(array $values) use ($column) {
					return SearchFields_AgentModel::getLabelsForKeyValues($column, $values);
				};
				$counts = $this->_getSubtotalCountForNumberColumn($context, $column, $label_map, 'in');
				break;

			case SearchFields_AgentModel::CONTEXT_WINDOW:
				// Group by the size a reader recognizes (`200K`) rather than the raw token count
				$label_map = function(array $values) {
					$map = [];

					foreach($values as $value)
						$map[$value] = DevblocksPlatform::strPrettyNumber($value);

					return $map;
				};
				$counts = $this->_getSubtotalCountForNumberColumn($context, $column, $label_map);
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

	function getQuickSearchFields() {
		$search_fields = SearchFields_AgentModel::getFields();

		// Whatever providers this install actually has, so the autocomplete can't offer a dead id
		$provider_labels = array_column(DevblocksPlatform::services()->llm()->getAgentProviders(), 'label', 'id');

		$fields = [
			'apiEndpointUrl' => [
				'type' => DevblocksSearchCriteria::TYPE_TEXT,
				'options' => ['param_key' => SearchFields_AgentModel::API_ENDPOINT_URL, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
			],
			// Named for the column's own label ("Authentication"), which is what the worklist header,
			// the peek, and the profile all say -- not for the record type behind it.
			'authentication' => [
				'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
				'options' => [
					'param_key' => SearchFields_AgentModel::VIRTUAL_CONNECTED_ACCOUNT_SEARCH,
					'select_key' => 'agent_model.connected_account_id',
				],
				'examples' => [
					['type' => 'search', 'context' => CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, 'q' => ''],
				]
			],
			'authentication.id' => [
				'type' => DevblocksSearchCriteria::TYPE_NUMBER,
				'options' => ['param_key' => SearchFields_AgentModel::CONNECTED_ACCOUNT_ID],
				'examples' => [
					['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, 'q' => ''],
				]
			],
			'contextWindow' => [
				'type' => DevblocksSearchCriteria::TYPE_NUMBER,
				'options' => ['param_key' => SearchFields_AgentModel::CONTEXT_WINDOW],
			],
			'created' => [
				'type' => DevblocksSearchCriteria::TYPE_DATE,
				'options' => ['param_key' => SearchFields_AgentModel::CREATED_AT],
			],
			'description' => [
				'type' => DevblocksSearchCriteria::TYPE_TEXT,
				'options' => ['param_key' => SearchFields_AgentModel::DESCRIPTION, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
			],
			'fieldset' => [
				'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
				'options' => ['param_key' => DevblocksSearchField::VIRTUAL_HAS_FIELDSET],
				'examples' => [
					['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . Context_AgentModel::ID],
				]
			],
			'hasVision' => [
				'type' => DevblocksSearchCriteria::TYPE_BOOL,
				'options' => ['param_key' => SearchFields_AgentModel::HAS_VISION],
			],
			'icon' => [
				'type' => DevblocksSearchCriteria::TYPE_TEXT,
				'options' => ['param_key' => SearchFields_AgentModel::ICON],
			],
			'id' => [
				'type' => DevblocksSearchCriteria::TYPE_NUMBER,
				'options' => ['param_key' => SearchFields_AgentModel::ID],
				'examples' => [
					['type' => 'chooser', 'context' => Context_AgentModel::ID, 'q' => ''],
				]
			],
			'isDisabled' => [
				'type' => DevblocksSearchCriteria::TYPE_BOOL,
				'options' => ['param_key' => SearchFields_AgentModel::IS_DISABLED],
			],
			'label' => [
				'type' => DevblocksSearchCriteria::TYPE_TEXT,
				'options' => ['param_key' => SearchFields_AgentModel::LABEL, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
			],
			'model' => [
				'type' => DevblocksSearchCriteria::TYPE_TEXT,
				'options' => ['param_key' => SearchFields_AgentModel::MODEL, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
			],
			'name' => [
				'type' => DevblocksSearchCriteria::TYPE_TEXT,
				'options' => ['param_key' => SearchFields_AgentModel::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
			],
			'provider' => [
				'type' => DevblocksSearchCriteria::TYPE_TEXT,
				'options' => ['param_key' => SearchFields_AgentModel::PROVIDER],
				'examples' => [
					['type' => 'list', 'values' => $provider_labels],
				]
			],
			'updated' => [
				'type' => DevblocksSearchCriteria::TYPE_DATE,
				'options' => ['param_key' => SearchFields_AgentModel::UPDATED_AT],
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
		$fields = self::_appendFieldsFromQuickSearchContext(Context_AgentModel::ID, $fields, null);
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		ksort($fields);

		return $fields;
	}

	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'authentication':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_AgentModel::VIRTUAL_CONNECTED_ACCOUNT_SEARCH);

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

		$custom_fields = DAO_CustomField::getByContext(Context_AgentModel::ID);
		$tpl->assign('custom_fields', $custom_fields);

		// Resolved in ONE query here so the Authentication cell can print a NAME instead of an id.
		// Never from the template: a per-row lookup there is a query per row. There are only ever a
		// handful of connected accounts, so getAll() is cheaper than collecting ids off the rows.
		if(in_array(SearchFields_AgentModel::CONNECTED_ACCOUNT_ID, $this->view_columns))
			$tpl->assign('connected_accounts', DAO_ConnectedAccount::getAll());

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::records/types/agent_model/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteriaParam($param) {
		switch($param->field) {
			case SearchFields_AgentModel::CONNECTED_ACCOUNT_ID:
			case SearchFields_AgentModel::PROVIDER:
				$field = $param->field;
				$label_map = function($values) use ($field) {
					return SearchFields_AgentModel::getLabelsForKeyValues($field, $values);
				};
				parent::_renderCriteriaParamString($param, $label_map);
				break;

			case SearchFields_AgentModel::HAS_VISION:
			case SearchFields_AgentModel::IS_DISABLED:
				parent::_renderCriteriaParamBoolean($param);
				break;

			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) : void {
		switch($param->field) {
			case SearchFields_AgentModel::VIRTUAL_CONNECTED_ACCOUNT_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('dao.agent_model.connected_account_id')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;

			default:
				$this->_renderVirtualCriteria($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_AgentModel::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_AgentModel::CREATED_AT:
			case SearchFields_AgentModel::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;

			case SearchFields_AgentModel::CONNECTED_ACCOUNT_ID:
			case SearchFields_AgentModel::CONTEXT_WINDOW:
			case SearchFields_AgentModel::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;

			// A bit column posts its value as `bool`, not `value` -- that's the payload
			// _getSubtotalCountForBooleanColumn() builds when a subtotal row is clicked.
			case SearchFields_AgentModel::HAS_VISION:
			case SearchFields_AgentModel::IS_DISABLED:
				$bool = DevblocksPlatform::importGPC($_POST['bool'] ?? null, 'integer', 1);
				$criteria = new DevblocksSearchCriteria($field, $oper, $bool);
				break;

			case SearchFields_AgentModel::API_ENDPOINT_URL:
			case SearchFields_AgentModel::DESCRIPTION:
			case SearchFields_AgentModel::ICON:
			case SearchFields_AgentModel::ICON_COLOR:
			case SearchFields_AgentModel::LABEL:
			case SearchFields_AgentModel::MODEL:
			case SearchFields_AgentModel::NAME:
			case SearchFields_AgentModel::PROVIDER:
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

class Context_AgentModel extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	const ID = 'cerb.contexts.agent.model';
	const URI = 'agent_model';

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
		return DAO_AgentModel::random();
	}

	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';

		$url_writer = DevblocksPlatform::services()->url();
		return $url_writer->writeNoProxy('c=profiles&type=agent_model&id='.$context_id, true);
	}

	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];

		if(is_null($model))
			$model = new Model_AgentModel();

		$properties['name'] = [
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => ['context' => self::ID],
		];

		$properties['connected_account_id'] = [
			'label' => mb_ucfirst($translate->_('dao.agent_model.connected_account_id')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->connected_account_id,
			'params' => ['context' => CerberusContexts::CONTEXT_CONNECTED_ACCOUNT],
		];

		$properties['context_window'] = [
			'label' => mb_ucfirst($translate->_('dao.agent_model.context_window')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->context_window,
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

		$properties['has_vision'] = [
			'label' => mb_ucfirst($translate->_('dao.agent_model.has_vision')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $model->has_vision,
		];

		$properties['icon'] = [
			'label' => mb_ucfirst($translate->_('dao.agent_model.icon')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->icon,
		];

		$properties['id'] = [
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		];

		$properties['is_disabled'] = [
			'label' => mb_ucfirst($translate->_('dao.agent_model.is_disabled')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $model->is_disabled,
		];

		$properties['label'] = [
			'label' => mb_ucfirst($translate->_('dao.agent_model.label')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->label,
		];

		$properties['model'] = [
			'label' => mb_ucfirst($translate->_('dao.agent_model.model')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->model,
		];

		$properties['params_kata'] = [
			'label' => mb_ucfirst($translate->_('dao.agent_model.params_kata')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->params_kata,
		];

		$properties['provider'] = [
			'label' => mb_ucfirst($translate->_('dao.agent_model.provider')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->provider,
		];

		$properties['updated'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		];

		return $properties;
	}

	function getMeta($context_id) {
		if(null == ($agent_model = DAO_AgentModel::get($context_id)))
			return [];

		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($agent_model->name);

		if(!empty($friendly))
			$url .= '-' . $friendly;

		return [
			'id' => $agent_model->id,
			'name' => $agent_model->name,
			'permalink' => $url,
			'updated' => $agent_model->updated_at,
			'created' => $agent_model->created_at,
			// A model reads as its vendor everywhere it's listed
			'icon' => $agent_model->getDisplayIcon(),
			'icon_color' => $agent_model->getDisplayIconColor(),
		];
	}

	function getDefaultProperties() : array {
		return [
			'provider',
			'model',
			'connected_account_id',
			'context_window',
			'has_vision',
			'is_disabled',
			'updated_at',
		];
	}

	function getContext($agent_model, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Agent Model:';

		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(Context_AgentModel::ID);

		if(is_numeric($agent_model)) {
			$agent_model = DAO_AgentModel::get($agent_model);
		} elseif($agent_model instanceof Model_AgentModel) {
			DevblocksPlatform::noop();
		} elseif(is_array($agent_model)) {
			$agent_model = Cerb_ORMHelper::recastArrayToModel($agent_model, 'Model_AgentModel');
		} else {
			$agent_model = null;
		}

		$token_labels = [
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'created_at' => $prefix.$translate->_('common.created'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'record_url' => $prefix.$translate->_('common.url.record'),
			'api_endpoint_url' => $prefix.$translate->_('dao.agent_model.api_endpoint_url'),
			'connected_account_id' => $prefix.$translate->_('dao.agent_model.connected_account_id'),
			'context_window' => $prefix.$translate->_('dao.agent_model.context_window'),
			'description' => $prefix.$translate->_('common.description'),
			'has_vision' => $prefix.$translate->_('dao.agent_model.has_vision'),
			'icon' => $prefix.$translate->_('dao.agent_model.icon'),
			'icon_color' => $prefix.$translate->_('dao.agent_model.icon_color'),
			'is_disabled' => $prefix.$translate->_('dao.agent_model.is_disabled'),
			'label' => $prefix.$translate->_('dao.agent_model.label'),
			'model' => $prefix.$translate->_('dao.agent_model.model'),
			'params_kata' => $prefix.$translate->_('dao.agent_model.params_kata'),
			'provider' => $prefix.$translate->_('dao.agent_model.provider'),
		];

		$token_types = [
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'created_at' => Model_CustomField::TYPE_DATE,
			'updated_at' => Model_CustomField::TYPE_DATE,
			'record_url' => Model_CustomField::TYPE_URL,
			'api_endpoint_url' => Model_CustomField::TYPE_SINGLE_LINE,
			'connected_account_id' => Model_CustomField::TYPE_SINGLE_LINE,
			'context_window' => Model_CustomField::TYPE_SINGLE_LINE,
			'description' => Model_CustomField::TYPE_SINGLE_LINE,
			'has_vision' => Model_CustomField::TYPE_SINGLE_LINE,
			'icon' => Model_CustomField::TYPE_SINGLE_LINE,
			'icon_color' => Model_CustomField::TYPE_SINGLE_LINE,
			'is_disabled' => Model_CustomField::TYPE_SINGLE_LINE,
			'label' => Model_CustomField::TYPE_SINGLE_LINE,
			'model' => Model_CustomField::TYPE_SINGLE_LINE,
			'params_kata' => Model_CustomField::TYPE_SINGLE_LINE,
			'provider' => Model_CustomField::TYPE_SINGLE_LINE,
		];

		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);

		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);

		$token_values = [];
		$token_values['_context'] = Context_AgentModel::ID;
		$token_values['_type'] = 'agent_model';
		$token_values['_types'] = $token_types;

		if($agent_model) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $agent_model->name;
			// The per-record glyph the shared card/profile chrome reads via $context_ext->getIcon($dict) —
			// resolved (override, else the provider's brand), so a model reads as its VENDOR rather than as
			// the generic record-type mark. Sits where `_image_url` would; a context avatar would outrank it.
			$token_values['_icon'] = $agent_model->getDisplayIcon();
			$token_values['_icon_color'] = $agent_model->getDisplayIconColor();
			$token_values['id'] = $agent_model->id;
			$token_values['name'] = $agent_model->name;
			$token_values['created_at'] = $agent_model->created_at;
			$token_values['updated_at'] = $agent_model->updated_at;
			$token_values['api_endpoint_url'] = $agent_model->api_endpoint_url;
			$token_values['connected_account_id'] = $agent_model->connected_account_id;
			$token_values['context_window'] = $agent_model->context_window;
			$token_values['description'] = $agent_model->description;
			$token_values['has_vision'] = $agent_model->has_vision;
			$token_values['icon'] = $agent_model->icon;
			$token_values['icon_color'] = $agent_model->icon_color;
			$token_values['is_disabled'] = $agent_model->is_disabled;
			$token_values['label'] = $agent_model->label;
			$token_values['model'] = $agent_model->model;
			$token_values['params_kata'] = $agent_model->params_kata;
			$token_values['provider'] = $agent_model->provider;
			$token_values = $this->_importModelCustomFieldsAsValues($agent_model, $token_values);

			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(
				sprintf("c=profiles&type=agent_model&id=%d-%s", $agent_model->id, DevblocksPlatform::strToPermalink($agent_model->name)), true
			);
		}

		return true;
	}

	function getKeyToDaoFieldMap() {
		return [
			'api_endpoint_url' => DAO_AgentModel::API_ENDPOINT_URL,
			'connected_account_id' => DAO_AgentModel::CONNECTED_ACCOUNT_ID,
			'context_window' => DAO_AgentModel::CONTEXT_WINDOW,
			'created_at' => DAO_AgentModel::CREATED_AT,
			'description' => DAO_AgentModel::DESCRIPTION,
			'has_vision' => DAO_AgentModel::HAS_VISION,
			'icon' => DAO_AgentModel::ICON,
			'icon_color' => DAO_AgentModel::ICON_COLOR,
			'id' => DAO_AgentModel::ID,
			'is_disabled' => DAO_AgentModel::IS_DISABLED,
			'label' => DAO_AgentModel::LABEL,
			'model' => DAO_AgentModel::MODEL,
			'name' => DAO_AgentModel::NAME,
			'params_kata' => DAO_AgentModel::PARAMS_KATA,
			'provider' => DAO_AgentModel::PROVIDER,
			'updated_at' => DAO_AgentModel::UPDATED_AT,
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

		$context = Context_AgentModel::ID;
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
		$view->name = 'Agent Model';
		$view->renderSortBy = SearchFields_AgentModel::UPDATED_AT;
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
		$view->name = 'Agent Model';

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
		$context = Context_AgentModel::ID;

		$tpl->assign('view_id', $view_id);

		$model = null;

		if($context_id) {
			if(!($model = DAO_AgentModel::get($context_id)))
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

			// Everything provider-specific in this editor comes from the provider EXTENSIONS, so a new
			// provider (or a new knob on an existing one) shows up here with no change to this form:
			// model-id suggestions + the default endpoint from the provider catalog, and the params
			// autocomplete from each provider's own getChatKataAutocomplete().
			$provider_catalog = self::_getProviderCatalog();
			$tpl->assign('providers', array_values($provider_catalog));
			$tpl->assign('providers_json', json_encode($provider_catalog));

			// Seed the auth chooser's chip
			if($model && $model->connected_account_id
				&& ($connected_account = DAO_ConnectedAccount::get($model->connected_account_id)))
				$tpl->assign('connected_account', $connected_account);

			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::records/types/agent_model/peek_edit.tpl');

		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}

	/**
	 * The per-provider editor catalog: `[provider_id => {id, label, icon, models, endpoint_default, params}]`.
	 *
	 * `params` is that provider's OWN `llm:<provider>:` autocomplete, re-keyed to the root — because the
	 * params editor's content is already scoped to one provider, so `thinking:` should suggest at the top
	 * level, not under `anthropic:`. This is how a provider extension contributes its config surface here:
	 * whatever it returns from `getChatKataAutocomplete()` shows up, including knobs added later.
	 */
	private static function _getProviderCatalog() : array {
		$llm = DevblocksPlatform::services()->llm();

		// One call gives every provider's block, keyed `<provider>:` + `<provider>:<subpath>:`
		$autocomplete = $llm->getKataProviderAutocomplete('');

		$out = [];

		foreach($llm->getAgentProviders() as $provider) {
			$prefix = $provider['id'] . ':';
			$params = [];

			foreach($autocomplete as $path => $suggestions) {
				if(!str_starts_with($path, $prefix))
					continue;

				// `anthropic:` → '' (the root), `anthropic:thinking:type:` → `thinking:type:`
				$params[substr($path, strlen($prefix))] = $suggestions;
			}

			$out[$provider['id']] = $provider + ['params' => $params];
		}

		return $out;
	}
};
