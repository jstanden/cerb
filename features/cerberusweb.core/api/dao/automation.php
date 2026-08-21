<?php
class DAO_Automation extends Cerb_ORMHelper {
	const CREATED_AT = 'created_at';
	const DESCRIPTION = 'description';
	const EXTENSION_ID = 'extension_id';
	const EXTENSION_PARAMS_JSON = 'extension_params_json';
	const ID = 'id';
	const NAME = 'name';
	const POLICY_KATA = 'policy_kata';
	const SCRIPT = 'script';
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
			->addField(self::EXTENSION_ID, DevblocksPlatform::translateCapitalized('common.trigger'))
			->string()
			->setRequired(true)
			->addValidator($validation->validators()->extension('Extension_AutomationTrigger'))
			;
		$validation
			->addField(self::EXTENSION_PARAMS_JSON)
			->string()
			->setMaxLength(16777215)
			;
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// varchar(255)
		$validation
			->addField(self::NAME)
			->string()
			->setUnique(__CLASS__)
			->addValidator(function($string, &$error=null) {
				if(0 != strcmp($string, DevblocksPlatform::strAlphaNum($string, '.-_'))) {
					$error = "may only contain letters, numbers, dots, dashes, and underscores";
					return false;
				}
				
				if(strlen($string) > 255) {
					$error = "must be shorter than 255 characters.";
					return false;
				}
				
				return true;
			})
			;
		$validation
			->addField(self::POLICY_KATA)
			->string()
			->setMaxLength('24 bits')
			;
		$validation
			->addField(self::SCRIPT)
			->string($validation::STRING_UTF8MB4)
			->setMaxLength('24 bits')
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
		
		if(!array_key_exists(DAO_Automation::NAME, $fields))
			$fields[DAO_Automation::NAME] = uniqid('automation_');
		
		if(!array_key_exists(DAO_Automation::CREATED_AT, $fields))
			$fields[DAO_Automation::CREATED_AT] = time();
		
		$sql = "INSERT INTO automation () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations(CerberusContexts::CONTEXT_AUTOMATION, $id);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		if(empty($ids)) return false;
			
		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
		
		$context = CerberusContexts::CONTEXT_AUTOMATION;
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
			parent::_update($batch_ids, 'automation', $fields);
			
			// Send events
			if($check_deltas) {
				// Log the context update
				DevblocksPlatform::markContextChanged($context, $batch_ids);
			}
		}
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('automation', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		if(!CerberusContexts::isActorAnAdmin($actor)) {
			$error = DevblocksPlatform::translate('error.core.no_acl.admin');
			return false;
		}
		
		$context = CerberusContexts::CONTEXT_AUTOMATION;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		if(array_key_exists(DAO_Automation::SCRIPT, $fields)) {
			$kata = DevblocksPlatform::services()->kata();
			if(false === $kata->validate($fields[DAO_Automation::SCRIPT], CerberusApplication::kataSchemas()->automation(), $error)) {
				$error = 'Automation: ' . $error;
				return false;
			}
		}

		if(array_key_exists(DAO_Automation::POLICY_KATA, $fields)) {
			$kata = DevblocksPlatform::services()->kata();
			if(false === $kata->validate($fields[DAO_Automation::POLICY_KATA], CerberusApplication::kataSchemas()->automationPolicy(), $error)) {
				$error = 'Automation policy: ' . $error;
				return false;
			}
		}
		
		return true;
	}
	
	static function count() {
		$db = DevblocksPlatform::services()->database();
		return $db->GetOneMaster('SELECT COUNT(id) FROM automation');
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_Automation[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, description, extension_id, extension_params_json, created_at, updated_at, script, policy_kata ".
			"FROM automation ".
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
	 * @param $name
	 * @param $extension_ids
	 * @return Model_Automation
	 */
	static function getByNameAndTrigger($name, $extension_ids) : ?Model_Automation {
		if(is_string($extension_ids))
			$extension_ids = [$extension_ids];
		
		if(!is_array($extension_ids) || empty($extension_ids))
			return null;
		
		// [TODO] Cache
		$results = self::getWhere(
			sprintf("%s = %s AND %s IN (%s)",
				Cerb_ORMHelper::escape(DAO_Automation::NAME),
				Cerb_ORMHelper::qstr($name),
				Cerb_ORMHelper::escape(DAO_Automation::EXTENSION_ID),
				implode(',', Cerb_ORMHelper::qstrArray($extension_ids))
			),
			null,
			true,
			1
		);
		
		if(!$results || 1 != count($results))
			return null;
		
		return current($results);
	}
	
	/**
	 * @param integer $id
	 * @return Model_Automation	 */
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
	 * @param string $interaction_uri
	 * @param string|array $extension_ids
	 * @return Model_Automation|null
	 */
	public static function getByUri(string $interaction_uri, $extension_ids=null) {
		$objects = self::getByUris([$interaction_uri], $extension_ids);
		
		if(!$objects)
			return null;
		
		return array_shift($objects);
	}
	
	public static function getByUris(array $uris, mixed $extension_ids=null) {
		$cache = DevblocksPlatform::services()->cache();

		if(!is_null($extension_ids) && !is_array($extension_ids))
			$extension_ids = [$extension_ids];

		// Normalize and sort the URIs
		
		$uris = array_filter(array_map(
			function($uri) {
				if(DevblocksPlatform::strStartsWith($uri, 'cerb:')) {
					if(!($uri_parts = DevblocksPlatform::services()->ui()->parseURI($uri)))
						return null;
					
					if(CerberusContexts::CONTEXT_AUTOMATION != $uri_parts['context'])
						return null;
					
					$uri = $uri_parts['context_id'];
				}
				
				return $uri;
			},
			$uris
		));
		
		if(!$uris)
			return [];
		
		sort($uris);
		
		// Cache during a single request
		$cache_key = 'automation:uris:' . sha1(json_encode($uris));
		
		if(null === ($objects = $cache->load($cache_key, false, true))) {
			$objects = self::getWhere(sprintf("%s IN (%s)",
				self::NAME,
				implode(',', Cerb_ORMHelper::qstrArray($uris))
			));
			
			$cache->save($objects, $cache_key, [], 0, true);
		}
		
		if(!$objects)
			return [];
		
		if(!$extension_ids)
			return $objects;
		
		return array_filter($objects, function($automation) use ($extension_ids) {
			return in_array($automation->extension_id, $extension_ids);
		});
	}
	
	public static function getByTrigger(string $extension_id) {
		$objects = self::getWhere(sprintf("%s = %s",
			self::EXTENSION_ID,
			Cerb_ORMHelper::qstr($extension_id)
		));
		
		if(!$objects)
			return [];
		
		return $objects;
	}
	
	/**
	 * 
	 * @param array $ids
	 * @return Model_Automation[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}	
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_Automation[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return null;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_Automation();
			$object->description = $row['description'];
			$object->extension_id = $row['extension_id'];
			$object->created_at = intval($row['created_at']);
			$object->id = intval($row['id']);
			$object->name = $row['name'];
			$object->policy_kata = $row['policy_kata'];
			$object->script = $row['script'];
			$object->updated_at = intval($row['updated_at']);
			
			$params = json_decode($row['extension_params_json'] ?? '', true);
			$object->extension_params = $params ?? [];
			
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('automation');
	}
	
	static function delete($ids) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		if(empty($ids)) return false;
		
		$context = CerberusContexts::CONTEXT_AUTOMATION;
		$ids_list = implode(',', self::qstrArray($ids));
		
		parent::_deleteAbstractBefore($context, $ids);
		
		DAO_RecordChangeset::delete('automation', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM automation WHERE id IN (%s)", $ids_list));
		
		parent::_deleteAbstractAfter($context, $ids);
		
		return true;
	}
	
	static function autocomplete($term, $as='models', $query=null) {
		$context_ext = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_AUTOMATION);
		
		$view = $context_ext->getSearchView('autocomplete_automation');
		$view->is_ephemeral = true;
		$view->renderPage = 0;
		$view->addParamsWithQuickSearch($query, true);
		
		$params = [
			SearchFields_Automation::NAME => new DevblocksSearchCriteria(SearchFields_Automation::NAME, DevblocksSearchCriteria::OPER_LIKE, '*'.$term.'*'),
		];
			
		$view->addParams($params);
			
		$view->renderLimit = 25;
		$view->renderSortBy = SearchFields_Automation::NAME;
		$view->renderSortAsc = false;
		$view->renderTotal = false;
		
		list($results,) = $view->getData();
		
		switch($as) {
			case 'ids':
				return array_keys($results);
			
			default:
				return DAO_Automation::getIds(array_keys($results));
		}
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Automation::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_Automation', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"automation.id as %s, ".
			"automation.name as %s, ".
			"automation.description as %s, ".
			"automation.extension_id as %s, ".
			"automation.created_at as %s, ".
			"automation.updated_at as %s ",
				SearchFields_Automation::ID,
				SearchFields_Automation::NAME,
				SearchFields_Automation::DESCRIPTION,
				SearchFields_Automation::EXTENSION_ID,
				SearchFields_Automation::CREATED_AT,
				SearchFields_Automation::UPDATED_AT
			);
			
		$join_sql = "FROM automation ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_Automation');
	
		return array(
			'primary_table' => 'automation',
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
			SearchFields_Automation::ID,
			$select_sql,
			$join_sql,
			$where_sql,
			$sort_sql,
			$page,
			$limit,
			$withCounts
		);
	}
	
	static function importFromJson($automation_data) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($automation_data) || !array_key_exists('name', $automation_data))
			return false;
		
		$automation_data = array_merge(
			[
				'description' => '',
				'extension_id' => '',
				'script' => '',
				'policy_kata' => 0,
				'created_at' => time(),
				'updated_at' => time(),
			],
			$automation_data
		);
		
		if(!($automation_id = $db->GetOneMaster(sprintf('SELECT id FROM automation WHERE name = %s',
			$db->qstr($automation_data['name'])
		)))) {
			$db->ExecuteMaster(sprintf("INSERT INTO automation (name, description, extension_id, script, policy_kata, created_at, updated_at) ".
				"VALUES (%s, %s, %s, %s, %s, %d, %d)",
				$db->qstr($automation_data['name']),
				$db->qstr($automation_data['description']),
				$db->qstr($automation_data['extension_id']),
				$db->qstr($automation_data['script']),
				$db->qstr($automation_data['policy_kata']),
				$automation_data['created_at'],
				$automation_data['updated_at']
			));
			$automation_id = $db->LastInsertId();
			
		} else {
			$db->ExecuteMaster(sprintf("UPDATE automation SET description=%s, extension_id=%s, script=%s, policy_kata=%s, created_at=%d, updated_at=%d WHERE id = %d",
				$db->qstr($automation_data['description']),
				$db->qstr($automation_data['extension_id']),
				$db->qstr($automation_data['script']),
				$db->qstr($automation_data['policy_kata']),
				$automation_data['created_at'],
				$automation_data['updated_at'],
				$automation_id
			));
		}
		
		return $automation_id;
	}
};

class SearchFields_Automation extends DevblocksSearchFields {
	const CREATED_AT = 'a_created_at';
	const DESCRIPTION = 'a_description';
	const EXTENSION_ID = 'a_extension_id';
	const NAME = 'a_name';
	const ID = 'a_id';
	const UPDATED_AT = 'a_updated_at';

	const VIRTUAL_SPARKLINE = '*_sparkline';
	const VIRTUAL_USAGE = '*_usage';

	static private $_fields = null;
	
	static function getTableName() : string {
		return 'automation';
	}
	
	static function getPrimaryKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_Automation::ID);
	}
	
	static function getUpdatedKey() : string {
		return sprintf('%s.%s', self::getTableName(), DAO_Automation::UPDATED_AT);
	}
	
	static function getCustomFieldContextKeys() {
		return [
			CerberusContexts::CONTEXT_AUTOMATION => new DevblocksSearchFieldContextKeys('automation.id', self::ID),
		];
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_USAGE:
				return self::_getWhereSQLFromUsageFilter($param);

			default:
				if(DevblocksPlatform::strStartsWith($param->field, 'cf_')) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					if(null !== ($virtual_where_sql = self::_getWhereSQLForCommonVirtual($param, CerberusContexts::CONTEXT_AUTOMATION, self::getPrimaryKey())))
						return $virtual_where_sql;

					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
		}
	}

	// The usage:(...) threshold vocabulary, shared by the SQL filter and the quick-search validator.
	static function getMetricFilterMap() : array {
		return [
			'runs' => DAO_MetricValue::metricFilterSeries('cerb.automation.invocations', 'counter'),
			'errors' => DAO_MetricValue::metricFilterSeries('cerb.automation.invocations', 'counter', ['query' => ['exit_state' => 'error']]),
			'duration' => DAO_MetricValue::metricFilterSeries('cerb.automation.duration', 'counter', ['unit' => 'ms', 'default' => 'avg']),
		];
	}

	// Constrain the worklist to automations whose metric usage matches usage:(...). The matched
	// `automation_id` dimension values ARE automation ids, so we filter the primary key directly.
	private static function _getWhereSQLFromUsageFilter(DevblocksSearchCriteria $param) : string {
		if($param->operator != DevblocksSearchCriteria::OPER_CUSTOM || !is_string($param->value))
			return '0=1';

		$matches = DAO_MetricValue::getDimensionValuesByMetricQuery(
			$param->value,
			self::getMetricFilterMap(),
			'automation_id',
			CerberusApplication::getActiveWorker()?->timezone ?: null
		);

		// null = invalid criteria (typo, unknown key, unparseable value) => match nothing (fail loud)
		if(is_null($matches))
			return '0=1';

		$ids = array_filter(array_map('intval', $matches));

		if(!$ids)
			return '0=1';

		return sprintf('%s IN (%s)', self::getPrimaryKey(), implode(',', $ids));
	}
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_Automation::ID:
				$models = DAO_Automation::getIds($values);
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
		
		$columns = [
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'automation', 'created_at', $translate->_('common.created'), Model_CustomField::TYPE_DATE, true),
			self::DESCRIPTION => new DevblocksSearchField(self::DESCRIPTION, 'automation', 'description', $translate->_('common.description'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::EXTENSION_ID => new DevblocksSearchField(self::EXTENSION_ID, 'automation', 'extension_id', $translate->_('common.extension'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::ID => new DevblocksSearchField(self::ID, 'automation', 'id', $translate->_('common.id'), Model_CustomField::TYPE_NUMBER, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'automation', 'name', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'automation', 'updated_at', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),

			// Virtual, display-only inline sparkline (runs + duration, loaded async); not sortable
			self::VIRTUAL_SPARKLINE => new DevblocksSearchField(self::VIRTUAL_SPARKLINE, '*', '', 'Usage', DevblocksSearchCriteria::TYPE_VIRTUAL_SPARKLINES, false),

			// Virtual, search-only: usage:(runs:>100 duration:>2000 since:"-24 hours"); hidden as a column
			self::VIRTUAL_USAGE => new DevblocksSearchField(self::VIRTUAL_USAGE, '*', '', 'Usage', null, false),
		];

		// Virtual fields
		if(($virtual_columns = DevblocksSearchField::getVirtualFields()))
			$columns = array_merge($columns, $virtual_columns);
		
		// Custom Fields
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));
		
		if(!empty($custom_columns))
			$columns = array_merge($columns, $custom_columns);

		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_Automation extends DevblocksRecordModel {
	public $created_at;
	public $description;
	public $extension_id = null;
	public $extension_params = [];
	public $id;
	public $name = null;
	public $policy_kata = null;
	public $script = null;
	public $updated_at;
	
	private $_environment = [];
	private $_policy = null;
	private $_inputs_meta = null;

	private $_ast = null;
	private $_ast_symbols = null;
	private $_ast_inputs = [];
	private $_ast_docs = null;
	
	/**
	 * @return Extension_AutomationTrigger
	 */
	public function getTriggerExtension() {
		/** @noinspection PhpUnnecessaryLocalVariableInspection */
		$ext = Extension_AutomationTrigger::get($this->extension_id);
		/* @var $ext Extension_AutomationTrigger */
		return $ext;
	}
	
	public function setEnvironment(array $environment) {
		$this->_environment = $environment;
	}
	
	public function getEnvironment() {
		return $this->_environment;
	}
	
	/**
	 * @return CerbAutomationPolicy|null
	 */
	public function getPolicy() {
		if(!is_null($this->_policy)) {
			return $this->_policy;
		}
		
		$kata = DevblocksPlatform::services()->kata();
		
		$error = null;
		
		if(false === ($policy_kata = $kata->parse($this->policy_kata, $error)))
			return null;
		
		$this->_policy = new CerbAutomationPolicy($policy_kata);

		return $this->_policy;
	}

	/**
	 * Parse this automation's `inputs:` KATA block into structured field metadata.
	 *
	 * Each entry is keyed by its raw `type/name` token and augmented with `key` and `type`.
	 * Shared by the automation `inputs` context token, the bulk-update prompt UI, and the
	 * LLM tool-schema builder.
	 *
	 * @return array
	 */
	public function getInputsMeta() : array {
		if(!is_null($this->_inputs_meta))
			return $this->_inputs_meta;

		$kata = DevblocksPlatform::services()->kata();
		$error = null;

		$inputs = [];
		$automation_kata = $kata->parse($this->script, $error, true);

		if(is_array($automation_kata) && array_key_exists('inputs', $automation_kata)) {
			$inputs = $kata->formatTree($automation_kata['inputs'], DevblocksDictionaryDelegate::instance([]));

			foreach($inputs as $k => &$input) {
				list($input_type, $input_key) = array_pad(explode('/', $k, 2), 2, '');
				$input['key'] = $input_key;
				$input['type'] = $input_type;
			}
		}

		$this->_inputs_meta = $inputs;

		return $this->_inputs_meta;
	}

	public function getSyntaxTree(&$error=null, &$symbol_meta=[]) : CerbAutomationAstNode|false {
		// If cached
		if(!is_null($this->_ast)) {
			$symbol_meta = $this->_ast_symbols;
			return $this->_ast;
		}
		
		$automator = DevblocksPlatform::services()->automation();
		
		$error = null;
		
		if(!($tree = DevblocksPlatform::services()->kata()->parse($this->script, $error, true, $this->_ast_symbols)))
			return false;
		
		if(!is_array($tree))
			return false;
		
		// Kept for the viewer to preview `start:` node inputs (unset here so they don't become AST nodes).
		$this->_ast_inputs = $tree['inputs'] ?? [];
		unset($tree['inputs']);

		$this->_ast = $automator->buildAstFromKata($tree, $error);
		
		$symbol_meta = $this->_ast_symbols;

		return $this->_ast;
	}

	/**
	 * Author-supplied node decorators from `# @name value` comments (viewer-only — the execution parse in
	 * getSyntaxTree() stays comment-free). A decorator comment decorates the NEXT non-comment sibling key;
	 * a run of decorators chains onto the same key. Returns a map of node-path => ['node' => "...", ...].
	 */
	public function getSyntaxDocs() : array {
		if(!is_null($this->_ast_docs))
			return $this->_ast_docs;

		$this->_ast_docs = [];

		$error = null;
		$symbols = [];

		// Second parse WITH comments retained (keep_comments=true).
		$tree = DevblocksPlatform::services()->kata()->parse($this->script, $error, true, $symbols, true);

		if(!is_array($tree))
			return $this->_ast_docs;

		$docs = [];

		$walk = function($node, $path) use (&$walk, &$docs) {
			if(!is_array($node))
				return;

			$pending = [];

			foreach($node as $k => $v) {
				$k = strval($k);

				// Comment nodes (incl. blank lines) are ordered siblings. Accumulate `# @name value` decorators;
				// skip plain comments/blanks without breaking the run.
				if(str_starts_with($k, '#comment_')) {
					if(is_string($v) && preg_match('/^#\s*@([\w.]+)\s+(.*)$/', $v, $m)) {
						$name = $m[1];
						$value = trim($m[2]);
						$pending[$name] = array_key_exists($name, $pending) ? $pending[$name] . "\n" . $value : $value;
					}
					continue;
				}

				// A real key — flush any pending decorators onto it (its colon-path == the graph node id).
				$child_path = ($path === '' ? '' : $path . ':') . preg_replace('/@.*$/', '', $k);

				if($pending) {
					$docs[$child_path] = $pending;
					$pending = [];
				}

				if(is_array($v))
					$walk($v, $child_path);
			}
		};

		$walk($tree, '');

		return $this->_ast_docs = $docs;
	}

	public function getSyntaxGraph(&$error=null) : ?array {
		$error = null;
		$symbol_meta = [];
		
		if(false === ($tree = $this->getSyntaxTree($error, $symbol_meta))) {
			return null;
		}
		
		$nodes = [];
		$edges = [];
		
		$recurseAst = function(CerbAutomationAstNodeVisitor $visitor) use (&$recurseAst, &$nodes, &$edges) {
			// Record the node meta on the first visit
			if(!array_key_exists($visitor->node->getId(), $nodes)) {
				$nodes[$visitor->node->getId()] = [
					'label' => $visitor->node->getName(),
					'type' => $visitor->node->getType(),
					'name_type' => $visitor->node->getNameType(),
				];
				if(in_array($visitor->node->getNameType(), ['await','set','return','error']))
					$nodes[$visitor->node->getId()]['params'] = $visitor->node->getParams();

				// Action commands that write their result to an output placeholder (e.g. http.request output: response).
				// The set/await/return/error family is excluded: they're type 'action' too and can carry an `output`
				// key (e.g. `return: output@text:` whose value is a text body, not a placeholder name) — those are
				// previewed from their params above, not as a result placeholder.
				if($visitor->node->getType() == 'action'
						&& !in_array($visitor->node->getNameType(), ['await','set','return','error'])
						&& $visitor->node->hasParam('output')) {
					$output_name = $visitor->node->getParam('output');
					if(is_string($output_name) && $output_name !== '')
						$nodes[$visitor->node->getId()]['output'] = $output_name;
				}
			}
			
			if (
				$visitor->node->getType() == 'root'
				|| ($visitor->node->getType() == 'action' && in_array($visitor->node->getName(), ['return','error']))
			) {
				if($visitor->node->getType() == 'root') {
					array_pop($visitor->path);
					$visitor->depth++;
				}
				
				// Populate nodes + edges for this path distinct path
				$path = array_values(array_unique($visitor->path));
				
				while(count($path) > 1) {
					$current = array_shift($path);
					$next = current($path);
					$label = '';
					
					// Collapse success/error nodes into edge labels
					$event_labels = [
						'on_success' => 'success',
						'on_error' => 'error',
						'on_simulate' => 'simulate',
						'on_tool' => 'tool',
					];
					$event_key = null;
					foreach(array_keys($event_labels) as $key)
						if(str_ends_with($next, ':' . $key)) {
							$event_key = $key;
							break;
						}
					if($event_key) {
						array_shift($path);
						$next = current($path);
						$label = $event_labels[$event_key];
						// Label independent outcome edges 
					} elseif(preg_match('#.*?:outcome[^:]*$#', $current, $matches)) {
						$parts = explode(':', $matches[0]);
						array_pop($parts);
						$parent = array_pop($parts);
						if(!str_starts_with($parent, 'decision')) {
							if(str_starts_with($next, $current . ':then:')) {
								$label = 'true';
							} else {
								$label = 'false';
							}
						}
					}
					
					if(!$next) {
						$nodes['exit'] = [
							'label' => 'exit',
							'type' => 'action',
							'name_type' => 'exit',
						];
						$next = 'exit';
					}
					
					$edge = [
						'from' => $current,
						'to' => $next,
						'label' => $label,
					];
					
					$edges[sha1(json_encode($edge))] = $edge;
				}
				
				return;
			}
			
			// Node activation (first visit only)
			if(!array_key_exists($visitor->node->getId(), $visitor->state)) {
				// If an action w/ events, synthesize the `on_error:` if it doesn't exist
				if(
					$visitor->node->getType() == 'action'
					&& !in_array($visitor->node->getNameType(), ['await','error','log','log.error','log.warn','return','set'])
				) {
					if(!$visitor->node->getChildBySuffix(':on_success')) {
						$on_success = new CerbAutomationAstNode($visitor->node->getId() . ':on_success', 'event', []);
						$visitor->node->addChild($on_success);
					}
					if(!$visitor->node->getChildBySuffix(':on_error')) {
						$on_error = new CerbAutomationAstNode($visitor->node->getId() . ':on_error', 'event', []);
						$on_error->addChild(new CerbAutomationAstNode($visitor->node->getId() . ':on_error:error', 'action', []));
						$visitor->node->addChild($on_error);
					}
				}
				
				if(
					$visitor->node->getName() == 'llm.agent'
				) {
					if(!$visitor->node->getChildBySuffix(':on_tool')) {
						$on_tool = new CerbAutomationAstNode($visitor->node->getId() . ':on_tool', 'event', []);
						$visitor->node->addChild($on_tool);
					}
				}
				
				$visitor->state[$visitor->node->getId()] = $visitor->node->getChildren();
			}

			if($visitor->state[$visitor->node->getId()]) {
				// Fork on decision outcomes + action events
				if(in_array($visitor->node->getType(), ['decision','action','llm.agent'])) {
					$children = $visitor->state[$visitor->node->getId()];
					$visitor->state[$visitor->node->getId()] = [];
					
					foreach ($children as $child) {
						// Omit empty event wrappers (including the implicit llm.agent `on_tool` and action `on_success`).
						if($child->getType() == 'event' && !$child->getChildren())
							continue;
						$next = clone $visitor;
						$next->setNode($child);
						$next->depth++;
						$recurseAst($next);
					}
					
					// Also recurse no matching decision
					if($visitor->node->getType() == 'decision') {
						$hasCatchallOutcome = false;
						$decision_dict = DevblocksDictionaryDelegate::instance([]);
						
						foreach ($children as $child) {
							$params = $child->getParams($decision_dict);
							if(
								$child->getType() == 'outcome' 
								&& !array_key_exists('if', $params)
							) {
								$hasCatchallOutcome = true;
								break;
							}
						}
						
						// Link back to the parent if we don't have a catchall outcome (no `if:`)
						if(!$hasCatchallOutcome) {
							$next = clone $visitor;
							$next->setNode($visitor->node->getParent());
							$next->depth--;
							$recurseAst($next);
						}
					} elseif(in_array($visitor->node->getType(), ['action', 'llm.agent'])) {
						// The node's main outlet always continues to its next sibling/ancestor. Event bodies are branches.
						$next = clone $visitor;
						$next->setNode($visitor->node->getParent());
						$next->depth--;
						$recurseAst($next);
					}
					
				} elseif(in_array($visitor->node->getType(), ['repeat', 'while'])) {
					// loop body
					if($visitor->state[$visitor->node->getId()]) {
						$next = clone $visitor;
						$next->setNode(array_shift($next->state[$visitor->node->getId()]));
						$next->depth++;
						$recurseAst($next);
					}

					// loop done
					$next = clone $visitor;
					$next->setNode($visitor->node->getParent());
					$next->depth--;
					$recurseAst($next);

				} elseif($visitor->node->getType() == 'outcome' && $visitor->node->getParent()->getType() != 'decision') {
					// outcome true
					$next = clone $visitor;
					$next->setNode(array_shift($next->state[$visitor->node->getId()]));
					$next->depth++;
					$recurseAst($next);
					
					// outcome false
					$next = clone $visitor;
					$next->setNode($visitor->node->getParent());
					$next->depth--;
					$recurseAst($next);
					
				} else {
					$next = clone $visitor;
					$next->setNode(array_shift($next->state[$visitor->node->getId()]));
					$next->depth++;
					$recurseAst($next);
				}
				
			} else {
				$next = clone $visitor;
				$next->setNode($visitor->node->getParent());
				$next->depth--;
				$recurseAst($next);
			}
		};
		
		$visitor = new CerbAutomationAstNodeVisitor($tree->getChild('start'));
		
		$recurseAst($visitor);
		
		unset($nodes['automation']);
		
		$nodes = array_filter(
			$nodes,
			fn($node_id) => !DevblocksPlatform::strEndsWith($node_id,[':on_success',':on_error',':on_simulate',':on_tool']),
			ARRAY_FILTER_USE_KEY
		);
		
		foreach($nodes as $node_id => $node) {
			$shape = null;
			
			if($node['type'] == 'start') {
				$shape = 'circle';
			} elseif($node['name_type'] == 'return') {
				$shape = 'ellipse';
			} elseif(in_array($node['name_type'], ['error','exit'])) {
				$shape = 'ellipse';
			} elseif(in_array($node['name_type'], ['repeat', 'while'])) {
				$shape = 'parallelogram';
			} elseif($node['type'] == 'decision') {
				$shape = 'diamond';
				if (str_contains($node['label'], '/'))
					$node['label'] = DevblocksPlatform::services()->string()->strAfter($node['label'], '/');
			} elseif($node['type'] == 'outcome') {
				$shape = 'cds';
				if (str_contains($node['label'], '/'))
					$node['label'] = DevblocksPlatform::services()->string()->strAfter($node['label'], '/');
			}
			
			$nodes[$node_id]['id'] = $node_id;
			$nodes[$node_id]['label'] = $node['label'];
			
			if(in_array($shape, ['circle', 'rect', 'ellipse', 'diamond']))
				$nodes[$node_id]['shape'] = $shape;
		}
		
		$edges = array_values($edges);
		
		return [
			'nodes' => $nodes,
			'edges' => $edges,
			'symbol_meta' => $symbol_meta,
		];
	}
	
	// Re-shape getSyntaxGraph()'s control-flow output into the CerbUI.NodeGraph document the Visualize tab renders
	// (read-only). The AST walk stays the source of truth; we only remap: control-flow node type/name_type → an
	// abstract block-type id (matching NODE_TYPES in tab_visualize.tpl), labeled divergent edges → branch outlets
	// (CerbUI.NodeEdge can't draw edge labels), and a `tier` per node so NodeGraph's tiered layout columns the
	// happy-path spine vs. its fan-out branches. symbol_meta (node id → editor line) is carried through unchanged.
	public function getSyntaxGraphForViewer(&$error=null) : ?array {
		if(!($graph = $this->getSyntaxGraph($error)))
			return null;

		$src_nodes = $graph['nodes'];
		$src_edges = $graph['edges'];

		// Map a control-flow node to an abstract block type (ids match NODE_TYPES in tab_visualize.tpl).
		$blockType = function(array $n) : string {
			$type = $n['type'] ?? '';
			$name_type = $n['name_type'] ?? '';

			if($type == 'start')
				return 'start';
			if($name_type == 'return')
				return 'return';
			if(in_array($name_type, ['error','exit']))
				return 'exit';
			if(in_array($name_type, ['repeat','while']))
				return 'loop';
			if($name_type == 'await')
				return 'await';
			if($type == 'decision')
				return 'decision';
			if($type == 'outcome')
				return 'outcome';
			return 'action';
		};

		$isLoop = fn($node_id) => in_array($src_nodes[$node_id]['name_type'] ?? '', ['repeat', 'while']);
		$isLoopBodyEdge = fn(array $edge) => $isLoop($edge['from']) && str_starts_with($edge['to'], $edge['from'] . ':do:');

		// The path walker may emit multiple loop-exit shortcuts for nested loops. Keep the structurally nearest
		// post-loop sibling (the target sharing the longest AST id prefix with the loop) and discard farther skips.
		$loop_exit_best = [];
		foreach($src_edges as $i => $edge) {
			if(!$isLoop($edge['from']) || $isLoopBodyEdge($edge))
				continue;
			$from_parts = explode(':', $edge['from']);
			$to_parts = explode(':', $edge['to']);
			$score = 0;
			while(isset($from_parts[$score], $to_parts[$score]) && $from_parts[$score] === $to_parts[$score])
				$score++;
			if(!isset($loop_exit_best[$edge['from']]) || $score > $loop_exit_best[$edge['from']]['score'])
				$loop_exit_best[$edge['from']] = ['index' => $i, 'score' => $score];
		}
		$src_edges = array_values(array_filter($src_edges, function($edge, $i) use ($isLoop, $isLoopBodyEdge, $loop_exit_best) {
			return !$isLoop($edge['from'])
				|| $isLoopBodyEdge($edge)
				|| ($loop_exit_best[$edge['from']]['index'] ?? $i) === $i;
		}, ARRAY_FILTER_USE_BOTH));

		// A "divergent" edge fans out to its own column (tier+1) and gets a labeled branch outlet: error/true,
		// loop `do` edges, plus every edge leaving a decision (its outcomes). Success/unlabeled/false edges are the
		// happy-path spine — they stay on the source's main outlet and flow straight down, same tier.
		$isDivergent = function(array $edge) use ($src_nodes, $isLoopBodyEdge) : bool {
			if($isLoopBodyEdge($edge))
				return true;
			if(in_array($edge['label'] ?? '', ['error','simulate','success','tool','true']))
				return true;
			// A decision's real outcomes are its CHILDREN (id path under the decision). A decision→sibling edge
			// (the fall-through when no outcome matches, whose intermediate spine node array_unique collapsed) is
			// NOT a branch — treat it as spine continuation, else it's mistaken for an outcome and its own forward
			// continuation is suppressed as a merge-back, orphaning the node that follows it.
			$from = $src_nodes[$edge['from']] ?? null;
			return $from && ($from['type'] ?? '') == 'decision' && str_starts_with($edge['to'], $edge['from'] . ':');
		};

		// A safe branch-outlet handle token ([a-z0-9_]) derived from a label.
		$slug = fn($label) => trim(strtolower(preg_replace('/[^a-z0-9]+/i', '_', (string) $label)), '_') ?: 'branch';

		// Per-source branch routing: give each divergent edge a unique labeled outlet on its source node (success vs
		// error on an action, the condition on each decision outcome). Decision outcomes are unlabeled here, so name
		// them after the target outcome's label.
		$branches = [];      // node_id => [ ['name'=>…, 'label'=>…], … ]
		$edge_handle = [];   // edge index => branch name (divergent edges only)
		$edge_scope = [];    // edge index => scope prefix the divergent edge opens
		$used_names = [];    // node_id => [ name => true ]
		$branch_scopes = []; // subtree prefixes whose outbound continuation is implied by the parent node

		foreach($src_edges as $i => $edge) {
			if(!$isDivergent($edge))
				continue;

			$from_id = $edge['from'];
			$label = $edge['label'] ?? '';

			if($isLoopBodyEdge($edge))
				$label = 'do';
			elseif($label === 'true')
				$label = 'then';
			elseif($label === 'error')
				$label = 'on_error';
			elseif($label === 'success')
				$label = 'on_success';
			elseif($label === 'simulate')
				$label = 'on_simulate';
			elseif($label === 'tool')
				$label = 'on_tool';
			elseif($label === '')
				$label = $src_nodes[$edge['to']]['label'] ?? 'else';

			$name = $base = $slug($label);
			$n = 1;
			while(isset($used_names[$from_id][$name]))
				$name = $base . '_' . (++$n);
			$used_names[$from_id][$name] = true;

			$line_key = null;
			if($isLoopBodyEdge($edge))
				$line_key = $from_id . ':do';
			elseif(($edge['label'] ?? '') == 'true')
				$line_key = $from_id . ':then';
			elseif(in_array($edge['label'] ?? '', ['error', 'simulate', 'success', 'tool']))
				$line_key = $from_id . ':on_' . ($edge['label'] == 'tool' ? 'tool' : $edge['label']);
			elseif(($src_nodes[$from_id]['type'] ?? '') == 'decision')
				$line_key = $edge['to'];

			$branch = ['name' => $name, 'label' => $label];
			if($line_key !== null && isset($graph['symbol_meta'][$line_key]))
				$branch['line'] = $graph['symbol_meta'][$line_key];
			$branches[$from_id][] = $branch;
			$edge_handle[$i] = $name;

			if($isLoopBodyEdge($edge))
				$scope_prefix = $from_id . ':do:';
			elseif(($edge['label'] ?? '') == 'true')
				$scope_prefix = $from_id . ':then:';
			elseif(in_array($edge['label'] ?? '', ['error', 'simulate', 'success', 'tool']))
				$scope_prefix = $from_id . ':on_' . $edge['label'] . ':';
			else
				$scope_prefix = $edge['to'] . ':';
			$branch_scopes[] = ['prefix' => $scope_prefix, 'root' => $edge['to']];
			$edge_scope[$i] = $scope_prefix;
		}

		// Tier (column) per node: a divergent edge steps to tier+1, while the spine keeps the tier. Relax to the
		// lowest reachable tier so an earlier branch traversal can't pull a shared continuation off the main spine.
		$incoming = array_fill_keys(array_keys($src_nodes), 0);
		$out = [];
		foreach($src_edges as $i => $edge) {
			if(isset($incoming[$edge['to']]))
				$incoming[$edge['to']]++;
			$out[$edge['from']][] = $i;
		}

		$tier = [];
		$queue = [];
		foreach($src_nodes as $id => $n) {
			if(($incoming[$id] ?? 0) == 0) {
				$tier[$id] = 0;
				$queue[] = $id;
			}
		}
		// Fallback for a cycle-only graph (no zero-incoming root): seed the first node.
		if(!$queue && $src_nodes) {
			$first = array_key_first($src_nodes);
			$tier[$first] = 0;
			$queue[] = $first;
		}

		while($queue) {
			$id = array_shift($queue);
			foreach($out[$id] ?? [] as $i) {
				$to = $src_edges[$i]['to'];
				$next_tier = $tier[$id] + ($isDivergent($src_edges[$i]) ? 1 : 0);
				if(!array_key_exists($to, $tier) || $next_tier < $tier[$to]) {
					$tier[$to] = $next_tier;
					$queue[] = $to;
				}
			}
		}

		// Nodes fed by a branch (off to the side) pin their inlet to the top-left corner so the curved edge lands clean.
		$branch_targets = [];
		foreach($edge_handle as $i => $name)
			$branch_targets[$src_edges[$i]['to']] = true;

		$nodes = [];
		$form_component_meta = [];
		if(($trigger_extension = $this->getTriggerExtension()) && method_exists($trigger_extension, 'getFormComponentMeta'))
			$form_component_meta = $trigger_extension::getFormComponentMeta();

		// Icon for a `start:` input preview row: record inputs use their record type's icon; others get a type icon.
		$inputIcon = function($input_type, $input_data) {
			switch($input_type) {
				case 'record':
				case 'records':
					$alias = $input_data['record_type'] ?? '';
					if($alias && (($ctx = Extension_DevblocksContext::getByAlias($alias, false)) || ($ctx = Extension_DevblocksContext::get($alias, false))))
						return $ctx->params['icon'] ?? 'collection';
					return 'collection';
				case 'number':
					return 'hash';
				case 'array':
					return 'list';
				case 'text':
				default:
					return 'text';
			}
		};

		// Author-supplied `# @node` decorators, keyed by node path (== node id).
		$docs = $this->getSyntaxDocs();

		foreach($src_nodes as $id => $n) {
			$node_label = $n['label'] ?? $id;
			$preview_rows = [];
			$name_type = $n['name_type'] ?? '';

			// Icon overrides beyond the abstract block-type default (error → square, set → placeholders, llm → bot).
			$node_icon = null;
			if($name_type === 'error') $node_icon = 'shield';
			elseif($name_type === 'set') $node_icon = 'placeholders';
			elseif(in_array($name_type, ['llm.agent','llm.chat'])) $node_icon = 'bot';
			if(($n['name_type'] ?? '') == 'await' && ($params = $n['params'] ?? [])) {
				$subtype_key = array_key_first($params);
				$subtype = preg_replace('/@.*$/', '', strval($subtype_key));
				if($subtype === 'form') $node_icon = 'todo';
				elseif($subtype === 'explore') $node_icon = 'compass';
				$label_parts = explode('/', $node_label, 2);
				$node_label = $subtype . (isset($label_parts[1]) ? '/' . $label_parts[1] : '');
				if($subtype == 'form' && is_array($params[$subtype_key]['elements'] ?? null)) {
					foreach(array_keys($params[$subtype_key]['elements']) as $element_key) {
						$element_path = preg_replace('/@.*$/', '', strval($element_key));
						[$element_type, $prompt_name] = array_pad(explode('/', $element_path, 2), 2, null);
						$row = [
							'label' => $prompt_name ?: $element_type,
							'icon' => $form_component_meta[$element_type]['icon'] ?? 'form',
							'tooltip' => $element_type,
						];
						$line_key = $id . ':' . $subtype . ':elements:' . $element_path;
						if(isset($graph['symbol_meta'][$line_key]))
							$row['line'] = $graph['symbol_meta'][$line_key];
						$preview_rows[] = $row;
					}
				}
			} elseif(in_array($n['name_type'] ?? '', ['set','return','error']) && ($params = $n['params'] ?? [])) {
				// Surface the placeholder keys these blocks assign to the working dictionary — what `set:` stores and
				// what `return:`/`error:` hand back (keys only, not values — like an await form's fields).
				foreach(array_keys($params) as $set_key) {
					$key_path = preg_replace('/@.*$/', '', strval($set_key));
					if($key_path === '')
						continue;
					$row = [
						'label' => $key_path,
						'icon' => 'placeholders',
						'tooltip' => $key_path,
					];
					$line_key = $id . ':' . $key_path;
					if(isset($graph['symbol_meta'][$line_key]))
						$row['line'] = $graph['symbol_meta'][$line_key];
					$preview_rows[] = $row;
				}
			} elseif($id === 'start' && ($inputs = $this->_ast_inputs ?? [])) {
				// Surface the automation's declared `inputs:` on the start node — name + type icon only.
				foreach($inputs as $input_idx => $input_data) {
					if(!is_array($input_data))
						$input_data = [];
					[$input_type, $input_name] = array_pad(explode('/', strval($input_idx), 2), 2, null);
					if(!$input_name)
						continue;
					$row = [
						'label' => $input_name,
						'icon' => $inputIcon($input_type, $input_data),
						'tooltip' => $input_idx,
					];
					$line_key = 'inputs:' . $input_idx;
					if(isset($graph['symbol_meta'][$line_key]))
						$row['line'] = $graph['symbol_meta'][$line_key];
					$preview_rows[] = $row;
				}
			}

			// An action command's output placeholder (what it writes its result to, e.g. http.request → response).
			if(!empty($n['output'])) {
				$out_row = [
					'label' => $n['output'],
					'icon' => 'placeholders',
					'tooltip' => 'output',
					'variant' => 'output',
				];
				$line_key = $id . ':output';
				if(isset($graph['symbol_meta'][$line_key]))
					$out_row['line'] = $graph['symbol_meta'][$line_key];
				$preview_rows[] = $out_row;
			}

			$node = [
				'id' => $id,
				'type' => $blockType($n),
				'label' => $node_label,
				'tier' => $tier[$id] ?? 0,
			];
			if($node_icon)
				$node['icon'] = $node_icon;
			// Author-supplied `# @node <text>` summary → node description (shown at the top of the node body).
			if(isset($docs[$id]['node']) && $docs[$id]['node'] !== '')
				$node['description'] = $docs[$id]['node'];
			if($preview_rows)
				$node['previewRows'] = $preview_rows;
			if(!empty($branches[$id]))
				$node['branches'] = $branches[$id];
			if(isset($branch_targets[$id]))
				$node['inletCorner'] = true;
			$nodes[] = $node;
		}

		usort($branch_scopes, fn($a, $b) => strlen($b['prefix']) <=> strlen($a['prefix']));
		$scopeForNode = function($node_id) use ($branch_scopes) {
			foreach($branch_scopes as $scope)
				if($node_id === $scope['root'] || str_starts_with($node_id, $scope['prefix']))
					return $scope;
			return null;
		};

		// A branch scope loops back to its parent iff an edge leaves it (the same edges suppressed below).
		// Terminal branches (ending in return/exit/error) have none → their outlet draws unidirectional.
		$scope_returns = [];
		foreach($src_edges as $edge) {
			if(($scope = $scopeForNode($edge['from']))
				&& $edge['to'] !== $scope['root']
				&& !str_starts_with($edge['to'], $scope['prefix']))
				$scope_returns[$scope['prefix']] = true;
		}

		$edges = [];
		$used_edges = [];
		foreach($src_edges as $i => $edge) {
			// Branch completion returns to its parent implicitly. Only internal branch edges are drawn.
			if(($scope = $scopeForNode($edge['from']))
				&& $edge['to'] !== $scope['root']
				&& !str_starts_with($edge['to'], $scope['prefix']))
				continue;

			$e = [
				'source' => $edge['from'],
				'target' => $edge['to'],
			];
			if(isset($edge_handle[$i])) {
				$e['sourceHandle'] = 'branch:' . $edge_handle[$i];
				$e['curve'] = true;
				// Only imply a return arrow when the branch actually flows back; terminal chains stay one-way.
				if(!empty($scope_returns[$edge_scope[$i] ?? '']))
					$e['bidirectional'] = true;
			}
			$edge_key = sha1(json_encode($e));
			if(!isset($used_edges[$edge_key])) {
				$edges[] = $e;
				$used_edges[$edge_key] = true;
			}
		}

		return [
			'nodes' => $nodes,
			'edges' => $edges,
			'symbol_meta' => $graph['symbol_meta'],
		];
	}

	// Command id => ordered scope dimensions. Keys ARE the policy-enforced command set (the Actions that call
	// $policy->isCommandAllowed(self::ID,...) — see libs/devblocks/api/services/automation/Action/*). A command NOT
	// listed here isn't policy-gated (set/return/log*/var.*/await) and needs no rule; an EMPTY dim list means enforced
	// but not statically scopable → a bare `allow`. Each dim: `param` = raw-params path to the literal we scope on,
	// `subject` = the expression LHS, `style` = predicate shape, `label` = the deny rule's uniqueness tag.
	// KEEP IN SYNC with the Action classes' isCommandAllowed() calls.
	private static function _getPolicyScopeMap() : array {
		$record  = [['param' => ['inputs','record_type'], 'subject' => 'inputs.record_type', 'style' => 'record_type', 'label' => 'type']];
		$storage = [['param' => ['inputs','key'],          'subject' => 'inputs.key',         'style' => 'in_list',     'label' => 'key']];
		$queue   = [['param' => ['inputs','queue_name'],   'subject' => 'inputs.queue_name',  'style' => 'in_list',     'label' => 'queue_name']];
		$fileUri = [['param' => ['inputs','uri'],          'subject' => 'inputs.uri',          'style' => 'in_list',     'label' => 'uri']];
		return [
			'record.create' => $record, 'record.get' => $record, 'record.update' => $record,
			'record.delete' => $record, 'record.search' => $record, 'record.upsert' => $record,
			'http.request' => [
				['param' => ['inputs','method'], 'subject' => 'inputs.method', 'style' => 'in_list',    'label' => 'method'],
				['param' => ['inputs','url'],    'subject' => 'inputs.url',    'style' => 'url_prefix', 'label' => 'url'],
			],
			'api.command' => [['param' => ['inputs','name'], 'subject' => 'inputs.name', 'style' => 'in_list', 'label' => 'name']],
			'storage.get' => $storage, 'storage.set' => $storage, 'storage.delete' => $storage,
			'queue.push' => $queue, 'queue.pop' => $queue,
			'metric.increment' => [['param' => ['inputs','metric_name'], 'subject' => 'inputs.metric_name', 'style' => 'in_list', 'label' => 'metric_name']],
			'file.read' => $fileUri, 'file.write' => $fileUri,
			'function' => [['param' => ['uri'], 'subject' => 'uri', 'style' => 'in_list', 'label' => 'uri']],
			'llm.router' => [],
			'llm.chat' => [], 'llm.embed' => [], 'llm.agent' => [], 'data.query' => [],
			'email.parse' => [], 'encrypt.pgp' => [], 'decrypt.pgp' => [],
		];
	}

	// Generate the tightest-scoped `commands:` policy KATA that would still allow this automation's script (principle
	// of least privilege). Walks the AST for every policy-enforced command it invokes and scopes each on the static
	// literals it passes (record type, HTTP method/host, api.command name, storage key, queue/metric name, file/
	// function uri); any dimension fed a {{placeholder}} can't be known statically, so that command is granted
	// unscoped. Reusable server-side (build a Model_Automation from a script string and call this). Returns null on a
	// parse error. Only `commands:` is generated — `settings:`/`callers:` are the author's to add.
	public function generatePolicyKata(&$error=null) : ?string {
		if(false === ($tree = $this->getSyntaxTree($error)))
			return null;

		$scope_map = self::_getPolicyScopeMap();
		$string = DevblocksPlatform::services()->string();

		// A value we can scope on: a non-empty scalar string with no scripting tags and no single quote (which would
		// break the single-quoted emit). Anything else (a {{ }}/{% %} placeholder, an array, a missing value) is dynamic.
		$isStatic = fn($v) => is_string($v) && $v !== '' && !str_contains($v, '{{') && !str_contains($v, '{%') && !str_contains($v, "'");

		// Read a dotted path out of a node's RAW params (no dict → placeholders stay literal), tolerating @annotated keys.
		$readParam = function($params, array $path) use ($string) {
			$cur = $params;
			foreach($path as $seg) {
				if(!is_array($cur))
					return null;
				if(array_key_exists($seg, $cur)) { $cur = $cur[$seg]; continue; }
				$match = null;
				foreach($cur as $k => $v) {
					if(is_string($k) && $seg === $string->strBefore($k, '@')) { $match = $v; break; }
				}
				if($match === null)
					return null;
				$cur = $match;
			}
			return $cur;
		};

		// cmd => label => ['values' => set, 'dynamic' => bool]
		$used = [];

		$walk = function(CerbAutomationAstNode $node) use (&$walk, &$used, $scope_map, $isStatic, $readParam) {
			$cmd = $node->getNameType();
			if($cmd !== null && array_key_exists($cmd, $scope_map)) {
				if(!array_key_exists($cmd, $used))
					$used[$cmd] = [];
				$params = $node->getParams();
				foreach($scope_map[$cmd] as $dim) {
					if(!array_key_exists($dim['label'], $used[$cmd]))
						$used[$cmd][$dim['label']] = ['values' => [], 'dynamic' => false];
					$raw = $readParam($params, $dim['param']);
					if($raw === null || !$isStatic($raw)) {
						$used[$cmd][$dim['label']]['dynamic'] = true;
					} elseif($dim['style'] === 'url_prefix') {
						if(null === ($prefix = $this->_policyUrlPrefix($raw)))
							$used[$cmd][$dim['label']]['dynamic'] = true;
						else
							$used[$cmd][$dim['label']]['values'][$prefix] = true;
					} else {
						$used[$cmd][$dim['label']]['values'][$raw] = true;
					}
				}
			}
			foreach($node->getChildren() as $child)
				$walk($child);
		};
		$walk($tree);

		// Emit deterministically (sorted commands + values).
		$lines = ['commands:'];

		if(!$used) {
			$lines[] = '  # No privileged commands detected.';
			return implode("\n", $lines);
		}

		ksort($used);
		foreach($used as $cmd => $dims) {
			$lines[] = '  ' . $cmd . ':';
			foreach($scope_map[$cmd] as $dim) {
				$slot = $dims[$dim['label']] ?? null;
				if(!$slot)
					continue;
				if($slot['dynamic'] || !$slot['values']) {
					// The command used this dimension but we couldn't pin it statically — leave it unscoped, flagged.
					$lines[] = '    # ' . $dim['subject'] . ' is dynamic — scope manually';
					continue;
				}
				$values = array_keys($slot['values']);
				sort($values);
				$lines[] = '    ' . $this->_policyDenyRule($dim, $values);
			}
			$lines[] = '    allow@bool: yes';
		}

		return implode("\n", $lines);
	}

	// Build a single `deny/<label>@bool: {{…}}` guard denying anything outside the allowed value set.
	private function _policyDenyRule(array $dim, array $values) : string {
		$q = fn($v) => "'" . $v . "'";   // values are pre-vetted quote-free by generatePolicyKata()'s $isStatic
		switch($dim['style']) {
			case 'record_type':
				return sprintf('deny/%s@bool: {{%s is not record type (%s)}}', $dim['label'], $dim['subject'], implode(', ', array_map($q, $values)));
			case 'url_prefix':
				$conds = array_map(fn($v) => sprintf('%s is not prefixed (%s)', $dim['subject'], $q($v)), $values);
				return sprintf('deny/%s@bool: {{%s}}', $dim['label'], implode(' and ', $conds));
			case 'in_list':
			default:
				return sprintf('deny/%s@bool: {{%s not in [%s]}}', $dim['label'], $dim['subject'], implode(', ', array_map($q, $values)));
		}
	}

	// Reduce a static URL to a scheme://host[:port]/ prefix to scope http.request; null if it isn't a usable absolute URL.
	private function _policyUrlPrefix(string $url) : ?string {
		$parts = @parse_url($url);
		if(!is_array($parts) || empty($parts['scheme']) || empty($parts['host']))
			return null;
		$prefix = $parts['scheme'] . '://' . $parts['host'];
		if(!empty($parts['port']))
			$prefix .= ':' . $parts['port'];
		return $prefix . '/';
	}

	/**
	 * @param DevblocksDictionaryDelegate $dict
	 * @param string $error
	 * @return DevblocksDictionaryDelegate|false
	 */
	public function execute(DevblocksDictionaryDelegate $dict, &$error=null) {
		$automator = DevblocksPlatform::services()->automation();
		
		if(!$automator->runAST($this, $dict, $error))
			return false;
		
		// Convert any nested dictionaries to arrays
		$nested_keys = [];
		
		$findNested = function($node, $path=[]) use (&$findNested, &$nested_keys) {
			if($node instanceof DevblocksDictionaryDelegate) {
				if($path) {
					$nested_keys[] = implode('.', $path);
				}
				
				foreach($node as $k => $v) {
					$path[] = $k;
					$findNested($v, $path);
					array_pop($path);
				}
				
			} else if(is_array($node)) {
				foreach ($node as $k => $v) {
					$path[] = $k;
					$findNested($v, $path);
					array_pop($path);
				}
			}
		};
		
		foreach($dict as $k => $v) {
			$findNested($v, [$k]);
		}
		
		// Sort the deepest paths first
		rsort($nested_keys);
		
		$dict->set('__expandable', $nested_keys);
		
		return $dict;
	}
	
	public function getParams(CerbAutomationAstNode $node, DevblocksDictionaryDelegate $dict) {
		$script_error = null;
		
		$params = $node->getParams($dict, $script_error);
		
		if($script_error) {
			$this->logError(
				'Scripting error: ' . $script_error,
				$node->getId(),
				3 // error
			);
		}
		
		return $params;
	}
	
	public function logError($log_message, $node_path, $log_level=3) {
		$fields = [
			DAO_AutomationLog::LOG_MESSAGE => $log_message,
			DAO_AutomationLog::LOG_LEVEL => $log_level,
			DAO_AutomationLog::CREATED_AT => time(),
			DAO_AutomationLog::AUTOMATION_NAME => $this->name ?? '',
			// `automation_node` is NOT NULL. Not every error is attributable to a node (a gated await failure has
			// no node path), and create() INSERTs then UPDATEs — so a null here doesn't just lose the log entry,
			// it leaves an orphan row behind with column defaults. Coerce, as the callers in automation.php do.
			DAO_AutomationLog::AUTOMATION_NODE => $node_path ?? '',
		];
		
		return DAO_AutomationLog::create($fields);
	}
};

class View_Automation extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'automations';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('common.automations');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Automation::ID;
		$this->renderSortAsc = true;

		$this->view_columns = [
			SearchFields_Automation::NAME,
			SearchFields_Automation::EXTENSION_ID,
			SearchFields_Automation::UPDATED_AT,
			SearchFields_Automation::VIRTUAL_SPARKLINE,
		];

		// Search-only virtual field; never offered as a worklist column
		$this->addColumnsHidden([
			SearchFields_Automation::VIRTUAL_USAGE,
		]);

		$this->doResetCriteria();
	}
	
	/**
	 * @return array
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_Automation::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_Automation');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null, &$total=null) {
		return $this->_getDataAsObjects('DAO_Automation', $ids, $total);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_Automation', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
				case SearchFields_Automation::EXTENSION_ID:
					$pass = true;
					break;
					
				// Valid custom fields
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
		$context = CerberusContexts::CONTEXT_AUTOMATION;

		if(!array_key_exists($column, $fields))
			return [];
		
		switch($column) {
			case SearchFields_Automation::EXTENSION_ID:
				$counts = $this->_getSubtotalCountForStringColumn($context, $column);
				break;
				
			default:
				// Custom fields
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
		$search_fields = SearchFields_Automation::getFields();
	
		$fields = array(
			'created' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Automation::CREATED_AT),
				),
			'name' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Automation::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
					'suggester' => [
						'type' => 'autocomplete',
						'query' => 'type:worklist.subtotals of:automation by:name~25 query:(name:*{{term}}*) format:dictionaries',
						'key' => 'name',
						'limit' => 25,
					]
				),
			'trigger' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Automation::EXTENSION_ID),
					'suggester' => [
						'type' => 'autocomplete',
						'query' => 'type:worklist.subtotals of:automation by:trigger~25 query:(trigger:*{{term}}*) format:dictionaries',
						'key' => 'trigger',
						'limit' => 25,
					]
				),
			'usage' => // parameterized metric filter; in-parens sub-keys autocompleted from getMetricFilterMap()
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => ['param_key' => SearchFields_Automation::VIRTUAL_USAGE],
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => ['param_key' => DevblocksSearchField::VIRTUAL_HAS_FIELDSET],
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_AUTOMATION],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Automation::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_AUTOMATION, 'q' => ''],
					]
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Automation::UPDATED_AT),
				),
			'watchers' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => ['param_key' => DevblocksSearchField::VIRTUAL_WATCHERS],
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					],
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', DevblocksSearchField::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_AUTOMATION, $fields, null);
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		ksort($fields);
		
		return $fields;
	}	
	
	function getQuickSearchMetricFilterMap(string $field_key) : ?array {
		return $field_key == 'usage' ? SearchFields_Automation::getMetricFilterMap() : null;
	}

	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'fieldset':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, '*_has_fieldset');

			case 'usage':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Automation::VIRTUAL_USAGE);

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

		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_AUTOMATION);
		$tpl->assign('custom_fields', $custom_fields);
		
		// Contexts
		$contexts = Extension_DevblocksContext::getAll(false);
		$tpl->assign('contexts', $contexts);
		
		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/automation/view.tpl');
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

	function renderVirtualCriteria($param) : void {
		switch($param->field) {
			case SearchFields_Automation::VIRTUAL_USAGE:
				echo sprintf("Usage matches <b>%s</b>", DevblocksPlatform::strEscapeHtml($param->value));
				break;

			default:
				$this->_renderVirtualCriteria($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_Automation::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Automation::NAME:
			case SearchFields_Automation::EXTENSION_ID:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_Automation::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_Automation::CREATED_AT:
			case SearchFields_Automation::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
			
			default:
				// Custom Fields
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

class Context_Automation extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek, IDevblocksContextAutocomplete, IDevblocksContextUri, IDevblocksContextWorkflow {
	const ID = CerberusContexts::CONTEXT_AUTOMATION;
	const URI = 'automation';
	
	static function isReadableByActor($models, $actor) {
		// Everyone can read
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		return self::_isWriteableOnlyByAdmin($models, $actor);
	}
	
	static function isDeletableByActor($models, $actor) {
		return self::isWriteableByActor($models, $actor);
	}
	
	function getRandom() {
		return DAO_Automation::random();
	}
	
	function profileGetUrl($context_id) {
		$url_writer = DevblocksPlatform::services()->url();
		
		if(empty($context_id))
			return '';
	
		return $url_writer->writeNoProxy('c=profiles&type=automation&id='.$context_id, true);
	}
	
	function profileGetFields($model=null) {
		$properties = [];
		
		if(is_null($model))
			$model = new Model_Automation();
		
		$properties['id'] = [
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		];
		
		$properties['name'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.name'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->name,
		];
		
		$properties['description'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.description'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->description,
		];
		
		$properties['extension_id'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.trigger'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->extension_id,
		];
		
		$properties['policy_kata'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.policy'),
			'type' => Model_CustomField::TYPE_MULTI_LINE,
			'value' => $model->policy_kata,
		];
		
		$properties['created'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.created'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->created_at,
		];
		
		$properties['updated'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		];
		
		return $properties;
	}
	
	function getMeta($context_id) {
		if(null == ($automation = DAO_Automation::get($context_id)))
			return [];
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($automation->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $automation->id,
			'name' => $automation->name,
			'permalink' => $url,
			'updated' => $automation->updated_at,
		);
	}
	
	function getDefaultProperties() : array {
		return [
			'trigger_event',
			'updated_at',
		];
	}
	
	function autocompleteUri($term, $uri_params=null) : array {
		$query = null;
		
		if(is_array($uri_params) && array_key_exists('triggers', $uri_params) && $uri_params['triggers']) {
			$query = sprintf('trigger:[%s]', implode(',', $uri_params['triggers']));
		}
		
		$results = DAO_Automation::autocomplete($term, 'models', $query);
		
		return array_column($results, 'name');
	}
	
	function autocomplete($term, $query=null) {
		$results = DAO_Automation::autocomplete($term, 'models', $query);
		$list = [];
		
		if(is_array($results))
			foreach($results as $automation_id => $automation) {
				$entry = new stdClass();
				$entry->label = $automation->name;
				$entry->value = intval($automation_id);
				$list[] = $entry;
			}
		
		return $list;
	}
	
	function getContextIdFromAlias($alias) {
		// Is it a URI?
		if(false != ($model = DAO_Automation::getByUri($alias)))
			return $model->id;
		
		return null;
	}
	
	function getContext($automation, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Automation:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_AUTOMATION);

		// Polymorph
		if(is_numeric($automation)) {
			$automation = DAO_Automation::get($automation);
		} elseif($automation instanceof Model_Automation) {
			// It's what we want already.
			DevblocksPlatform::noop();
		} elseif(is_array($automation)) {
			$automation = Cerb_ORMHelper::recastArrayToModel($automation, 'Model_Automation');
		} else {
			$automation = null;
		}
		
		// Token labels
		$token_labels = [
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'description' => $prefix.$translate->_('common.description'),
			'extension_id' => $prefix.$translate->_('common.trigger'),
			'extension_params' => $prefix.$translate->_('common.trigger') . ' params',
			'created_at' => $prefix.$translate->_('common.created'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'policy_kata' => $prefix.$translate->_('common.policy'),
			'script' => $prefix.$translate->_('common.script'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		];
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'description' => Model_CustomField::TYPE_SINGLE_LINE,
			'extension_id' => Model_CustomField::TYPE_SINGLE_LINE,
			'extension_params' => null, // array
			'created_at' => Model_CustomField::TYPE_DATE,
			'updated_at' => Model_CustomField::TYPE_DATE,
			'policy_kata' => Model_CustomField::TYPE_MULTI_LINE,
			'record_url' => Model_CustomField::TYPE_URL,
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = [];
		
		$token_values['_context'] = Context_Automation::ID;
		$token_values['_type'] = Context_Automation::URI;
		$token_values['_types'] = $token_types;
		
		if($automation) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $automation->name;
			$token_values['name'] = $automation->name;
			$token_values['description'] = $automation->description;
			$token_values['id'] = $automation->id;
			$token_values['extension_id'] = $automation->extension_id;
			$token_values['extension_params'] = $automation->extension_params;
			$token_values['created_at'] = $automation->created_at;
			$token_values['updated_at'] = $automation->updated_at;
			$token_values['policy_kata'] = $automation->policy_kata;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($automation, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=automation&id=%d-%s",$automation->id, DevblocksPlatform::strToPermalink($automation->name)), true);
		}
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'created_at' => DAO_Automation::CREATED_AT,
			'description' => DAO_Automation::DESCRIPTION,
			'extension_id' => DAO_Automation::EXTENSION_ID,
			'id' => DAO_Automation::ID,
			'links' => '_links',
			'name' => DAO_Automation::NAME,
			'policy_kata' => DAO_Automation::POLICY_KATA,
			'script' => DAO_Automation::SCRIPT,
			'updated_at' => DAO_Automation::UPDATED_AT,
		];
	}
	
	// [TODO] Params
	function getKeyMeta($with_dao_fields=true) {
		return parent::getKeyMeta($with_dao_fields);
	}
	
	function getKeyAutocompleteSuggestions() : array {
		$triggers = Extension_AutomationTrigger::getAll(false);
		
		return [
			'extension_id' => array_column($triggers, 'id'),
		];
	}
	
	// [TODO] Params
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, $data, &$error) {
		switch(DevblocksPlatform::strLower($key)) {
		}
		
		return true;
	}
	
	function lazyLoadGetKeys() {
		$lazy_keys = parent::lazyLoadGetKeys();
		
		$lazy_keys['script'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.script'),
			'type' => Model_CustomField::TYPE_MULTI_LINE,
		];
		
		return $lazy_keys;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return false;
		
		$context = CerberusContexts::CONTEXT_AUTOMATION;
		$context_id = $dictionary['id'];
		
		$is_loaded = $dictionary['_loaded'] ?? false;
		$values = [];
		
		if(!$is_loaded) {
			$labels = [];
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
			case 'inputs':
				$values['inputs'] = [];

				if(!($automation = DAO_Automation::get($context_id)))
					break;

				$values['inputs'] = $automation->getInputsMeta();
				break;
				
			case 'outputs':
				$values['outputs'] = [];
				break;
			
			case 'script':
				$values['script'] = '';
				
				if(!($automation = DAO_Automation::get($context_id)))
					break;
				
				$values['script'] = $automation->script;
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
		$view->name = 'Automation';
		$view->renderSortBy = SearchFields_Automation::UPDATED_AT;
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
		$view->name = 'Automation';
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = [
				new DevblocksSearchCriteria(DevblocksSearchField::VIRTUAL_CONTEXT_LINK, 'in', [$context.':'.$context_id]),
			];
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = CerberusContexts::CONTEXT_AUTOMATION;
		
		$tpl->assign('view_id', $view_id);
		
		$model = null;
		
		$lookup_id = $context_id;
		
		// Load by URI if not given a numeric ID
		if($context_id && !is_numeric($context_id)) {
			$context_id = DAO_Automation::getByUri($context_id);
		}
		
		if($context_id instanceof Model_Automation) {
			$model = $context_id;
			$context_id = $model->id;
			
		} else if ($context_id && is_numeric($context_id)) {
			$model = DAO_Automation::get($context_id);
		}
		
		if(!$context_id || $edit) {
			if(!$active_worker->is_superuser)
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			if($model) {
				if(!Context_Automation::isWriteableByActor($model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
			} else {
				$model = new Model_Automation();
				$model->id = 0;
				$model->script = "# [TODO] You can optionally declare custom inputs here\n#inputs:\n#  text/name:\n#    required@bool: yes\n#  record/ticket:\n#    required@bool: yes\n#    record_type: ticket\n#    expand: customfields,group_,owner_\n\nstart:\n  # [TODO] Your logic goes here (use Ctrl+Space for autocompletion)\n  ";
				$model->policy_kata = "commands:\n  # [TODO] Specify a command policy here (use Ctrl+Space for autocompletion)\n  ";

				// The Automation Builder now opens on a template picker (peek_edit.tpl); a picked template sets
				// the trigger/script/policy IN the editor, so we no longer seed an example name from $lookup_id
				// (name stays the author's to fill).
			}

			// Automation Builder candidates (grouped by section) for the new-record template picker.
			if(!$model->id)
				$tpl->assign('automation_templates', \Cerb\Extensions\Extension_AutomationTemplate::getGrouped());
			
			// Trigger extensions
			$extensions = Extension_AutomationTrigger::getAll(false);
			$tpl->assign('extensions', $extensions);
			
			// Custom fields
			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);
	
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(array_key_exists($context_id ?? '', $custom_field_values))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
			
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);
			
			// Workflow-managed automations are version-controlled; warn that hand-edits get overwritten.
			$workflow_id = DAO_WorkflowResource::getWorkflowIdByRecord(Context_Automation::ID, $model->id);

			if($workflow_id && ($workflow = DAO_Workflow::get($workflow_id))) {
				$tpl->assign('workflow', $workflow);

				if(($ctx_workflow = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_WORKFLOW, true)))
					$tpl->assign('workflow_url', $ctx_workflow->profileGetUrl($workflow->id));
			}

			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('model', $model);
			$tpl->assign('view_id', $view_id);

			// The agent.pane toolbar, scoped to this editor via {{component}} = 'automation'. Its items launch an
			// interaction inline into the editor's agent pane; the caller name must match a caller the launched
			// automation's policy allows. Empty until a toolbar section is authored (the pane hides its toggle).
			$agent_toolbar_html = '';

			$agent_toolbar_dict = DevblocksDictionaryDelegate::instance([
				'component' => 'automation',
				'caller_name' => 'agent.pane',
				'worker_id' => $active_worker->id,
				'worker__context' => CerberusContexts::CONTEXT_WORKER,
			]);

			if(($agent_toolbar = DAO_Toolbar::getKataByName('agent.pane', $agent_toolbar_dict)))
				$agent_toolbar_html = DevblocksPlatform::services()->ui()->toolbar()->fetch($agent_toolbar);

			$tpl->assign('agent_toolbar_html_json', json_encode($agent_toolbar_html));

			$tpl->display('devblocks:cerberusweb.core::internal/automation/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
	
	function workflowExport(array $ids, DevblocksWorkflowExportModel $export_model, bool $include_children = false) : array {
		$workflow_kata = [
			'records' => [],
		];
		
		$record_uri = CerberusContexts::getContextName($this->id, 'uri');
		
		$models = DAO_Automation::getIds($ids);
		
		foreach($models as $model) {
			$model_key = $export_model->getLabelMapFor(sprintf('%s_%d', $record_uri, $model->id));
			$record_key = sprintf('%s/%s', $record_uri, $model_key);
			
			$workflow_kata['records'][$record_key] = [
				'fields' => [
					'name' => $model->name,
					'extension_id' => $model->extension_id,
					'description' => $model->description,
					'script' => new DevblocksKataRawString($model->script ?? ''),
					'policy_kata' => new DevblocksKataRawString($model->policy_kata ?? ''),
				],
			];
		}
		
		return $workflow_kata;
	}
};
