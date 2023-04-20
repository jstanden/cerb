<?php
class DAO_Workflow extends Cerb_ORMHelper {
	const CONFIG_KATA = 'config_kata';
	const CREATED_AT = 'created_at';
	const DESCRIPTION = 'description';
	const HAS_EXTENSIONS = 'has_extensions';
	const ID = 'id';
	const NAME = 'name';
	const RESOURCES_KATA = 'resources_kata';
	const UPDATED_AT = 'updated_at';
	const WORKFLOW_KATA = 'workflow_kata';
	
	const _CACHE_ALL = 'workflows_all';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::CREATED_AT)
			->timestamp()
		;
		$validation
			->addField(self::HAS_EXTENSIONS)
			->number()
			->setMin(0)
			->setMax(255)
			->setEditable(false)
		;
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
		;
		$validation
			->addField(self::NAME)
			->string()
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
		
		if(!array_key_exists(DAO_Workflow::CREATED_AT, $fields))
			$fields[DAO_Workflow::CREATED_AT] = time();
		
		$sql = "INSERT INTO workflow () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations(Context_Workflow::ID, $id);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = [$ids];
		
		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
		
		$context = CerberusContexts::CONTEXT_WORKFLOW;
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
			parent::_update($batch_ids, 'workflow', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.workflow.update',
						[
							'fields' => $fields,
						]
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged($context, $batch_ids);
			}
		}
		
		self::clearCache();
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('workflow', $fields, $where);
		self::clearCache();
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_WORKFLOW;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		return true;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_Workflow[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();
		
		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, description, created_at, updated_at, workflow_kata, config_kata, resources_kata, has_extensions ".
			"FROM workflow ".
			$where_sql.
			$sort_sql.
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
	 *
	 * @param bool $nocache
	 * @return Model_Workflow[]
	 */
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::services()->cache();
		
		if($nocache || null === ($objects = $cache->load(self::_CACHE_ALL))) {
			$objects = self::getWhere(null, DAO_Workflow::NAME, true, null, DevblocksORMHelper::OPT_GET_MASTER_ONLY);
			
			if(!is_array($objects))
				return [];
			
			$cache->save($objects, self::_CACHE_ALL);
			
		} else {
			// Cloning avoids unintentional persisted changes to cached objects
			$objects = array_map(fn($o) => clone $o, $objects);
		}
		
		return $objects;
	}
	
	/**
	 * @param integer $id
	 * @return Model_Workflow
	 */
	static function get($id) {
		$workflows = self::getAll();
		
		if(array_key_exists($id, $workflows))
			return $workflows[$id];
		
		return null;
	}
	
	/**
	 *
	 * @param array $ids
	 * @return Model_Workflow[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}
	
	public static function getByName(string $name) : ?Model_Workflow {
		$workflows = self::getAll();
		
		$names_index = array_column($workflows, 'id', 'name');
		
		if(!array_key_exists($name, $names_index))
			return null;
		
		return $workflows[$names_index[$name]];
	}
	
	/**
	 * @param int $bits
	 * @return Model_Workflow[]
	 */
	public static function getWithExtensions(int $bits) : array {
		return array_filter(
			self::getAll(),
			fn(Model_Workflow $workflow) => $workflow->has_extensions & $bits,
		);
	}
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_Workflow[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return [];
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_Workflow();
			$object->config_kata = $row['config_kata'] ?? '';
			$object->created_at = intval($row['created_at'] ?? 0);
			$object->description = $row['description'] ?? '';
			$object->has_extensions = intval($row['has_extensions'] ?? 0);
			$object->id = intval($row['id'] ?? 0);
			$object->name = $row['name'] ?? '';
			$object->resources_kata = $row['resources_kata'] ?? '';
			$object->updated_at = intval($row['updated_at'] ?? 0);
			$object->workflow_kata = $row['workflow_kata'] ?? '';
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('workflow');
	}
	
	static function clearCache() {
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::_CACHE_ALL);
	}
	
	static function delete($ids) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		if(empty($ids)) return false;
		
		$context = CerberusContexts::CONTEXT_WORKFLOW;
		$ids_list = implode(',', self::qstrArray($ids));
		
		parent::_deleteAbstractBefore($context, $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM workflow WHERE id IN (%s)", $ids_list));
		
		parent::_deleteAbstractAfter($context, $ids);
		
		self::clearCache();
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Workflow::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_Workflow', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"workflow.id as %s, ".
			"workflow.name as %s, ".
			"workflow.description as %s, ".
			"workflow.created_at as %s, ".
			"workflow.updated_at as %s, ".
			"workflow.workflow_kata as %s, ".
			"workflow.config_kata as %s, ".
			"workflow.resources_kata as %s ",
			SearchFields_Workflow::ID,
			SearchFields_Workflow::NAME,
			SearchFields_Workflow::DESCRIPTION,
			SearchFields_Workflow::CREATED_AT,
			SearchFields_Workflow::UPDATED_AT,
			SearchFields_Workflow::WORKFLOW_KATA,
			SearchFields_Workflow::CONFIG_KATA,
			SearchFields_Workflow::RESOURCES_KATA
		);
		
		$join_sql = "FROM workflow ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
		
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_Workflow');
		
		return [
			'primary_table' => 'workflow',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		];
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
	 * @return array|false
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
			SearchFields_Workflow::ID,
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

class SearchFields_Workflow extends DevblocksSearchFields {
	const ID = 'a_id';
	const NAME = 'a_name';
	const DESCRIPTION = 'a_description';
	const CREATED_AT = 'a_created_at';
	const UPDATED_AT = 'a_updated_at';
	const WORKFLOW_KATA = 'a_workflow_kata';
	const CONFIG_KATA = 'a_config_kata';
	const RESOURCES_KATA = 'a_resources_kata';
	
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_WATCHERS = '*_workers';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'workflow.id';
	}
	
	static function getCustomFieldContextKeys() {
		return [
			Context_Workflow::ID => new DevblocksSearchFieldContextKeys('workflow.id', self::ID),
		];
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_WORKFLOW, self::getPrimaryKey());
			
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_WORKFLOW), '%s'), self::getPrimaryKey());
			
			case self::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, CerberusContexts::CONTEXT_WORKFLOW, self::getPrimaryKey());
			
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
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_Workflow::ID:
				$models = DAO_Workflow::getIds($values);
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
			self::ID => new DevblocksSearchField(self::ID, 'workflow', 'id', $translate->_('common.id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'workflow', 'name', $translate->_('common.name'), null, true),
			self::DESCRIPTION => new DevblocksSearchField(self::DESCRIPTION, 'workflow', 'description', $translate->_('common.description'), null, true),
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'workflow', 'created_at', $translate->_('common.created'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'workflow', 'updated_at', $translate->_('common.updated'), null, true),
			self::WORKFLOW_KATA => new DevblocksSearchField(self::WORKFLOW_KATA, 'workflow', 'workflow_kata', $translate->_('common.template'), null, true),
			self::CONFIG_KATA => new DevblocksSearchField(self::CONFIG_KATA, 'workflow', 'config_kata', $translate->_('common.configuration'), null, true),
			self::RESOURCES_KATA => new DevblocksSearchField(self::RESOURCES_KATA, 'workflow', 'resources_config', $translate->_('common.resources'), null, true),
			
			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers'), 'WS', false),
		];
		
		// Custom Fields
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));
		
		if(!empty($custom_columns))
			$columns = array_merge($columns, $custom_columns);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');
		
		return $columns;
	}
};

class Model_Workflow extends DevblocksRecordModel {
	public string $config_kata = '';
	public int $created_at = 0;
	public string $description = '';
	public int $has_extensions = 0;
	public int $id = 0;
	public string $name = '';
	public string $resources_kata = '';
	public int $updated_at = 0;
	public string $workflow_kata = '';
	
	const HAS_ACTIVITIES = 1;
	const HAS_PERMISSIONS = 2;
	const HAS_TRANSLATIONS = 4;
	
	public function getKata(?string &$error=null) : array|false {
		$kata = DevblocksPlatform::services()->kata();
		$error = null;
		
		if(false === ($workflow_template = $kata->parse($this->workflow_kata, $error)))
			return false;
		
		return $workflow_template;
	}
	
	public function getConfig(?string &$error=null) : array|false {
		$kata = DevblocksPlatform::services()->kata();
		$error = null;
		
		if(false === ($workflow_config = $kata->parse($this->config_kata, $error)))
			return [];
		
		$config_dict = DevblocksDictionaryDelegate::instance([]);
		
		if(false === ($config = $kata->formatTree($workflow_config ?? [], $config_dict, $error)))
			return [];
		
		return $config;
	}
	
	public function getConfigOptions(array $config_values=[], ?string &$error=null) : array|false {
		$kata = DevblocksPlatform::services()->kata();
		$error = null;
		
		if(!($workflow_template = $this->getKata($error)))
			return false;
		
		$workflow_config = $workflow_template['config'] ?? [];
		$config_dict = new DevblocksDictionaryDelegate([]);
		
		if(!($workflow_config = $kata->formatTree($workflow_config, $config_dict, $error)))
			return false;
		
		$results = [];
		
		$possible_types = [
			'chooser',
			'text',
		];
		
		foreach($workflow_config as $option_key => $option_params) {
			$option_type = DevblocksPlatform::services()->string()->strBefore($option_key, '/');
			$option_name = DevblocksPlatform::services()->string()->strAfter($option_key, '/') ?: $option_type;
			
			if(!in_array($option_type, $possible_types)) {
				$error = sprintf('`config:%s` is not a valid type: %s',
					$option_key,
					implode(', ', $possible_types)
				);
				return false;
			}
			
			if('chooser' == $option_type) {
				$possible_params = [
					'label',
					'default',
					'multiple',
					'record_query',
					'record_type',
				];
				
				// Schema validation
				if($invalid_params = array_diff(array_keys($option_params), $possible_params)) {
					$error = sprintf("Unknown params (%s). Must be one or more of: %s",
						implode(', ', $invalid_params),
						implode(',', $possible_params)
					);
					return false;
				}
				
				if(array_key_exists($option_name, $config_values)) {
					if($option_params['multiple'] ?? false) {
						if(is_array($config_values[$option_name] ?? null)) {
							$models = CerberusContexts::getModels($option_params['record_type'] ?? '', $config_values[$option_name]);
							
							if($models) {
								$record_dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $option_params['record_type'] ?? '');
								$option_params['record_labels'] = array_map(fn($dict) => $dict->get('_label'), $record_dicts);
							}
						}
						
					} else {
						$model = CerberusContexts::getModel($option_params['record_type'] ?? '', intval($config_values[$option_name]));
						
						if($model) {
							$record_dict = DevblocksDictionaryDelegate::getDictionaryFromModel($model, $option_params['record_type'] ?? '');
							$option_params['record_label'] = $record_dict->get('_label');
						}
					}
				}
			}
			
			$option = [
				'type' => $option_type,
				'key' => $option_name,
				'value' => ($config_values[$option_name] ?? null) ?? ($option_params['default'] ?? ''),
				'params' => $option_params,
			];
			
			$results[$option_name] = $option;
		}
	
		return $results;
	}
	
	public function populateTemplateSummary(Smarty $tpl, ?string &$error=null) : bool {
		$error = null;
		
		$sheet_kata = <<< EOD
      layout:
        headings@bool: no
        paging@bool: no
        filtering@bool: no
      columns:
        text/_key:
          label: Key
        card/_label:
          label: Record
          params:
            bold@bool: yes
      EOD;
		
		$sheets = DevblocksPlatform::services()->sheet()->withDefaultTypes();
		
		if(!($sheet = $sheets->parse($sheet_kata, $error))) {
			$error = 'Error: ' . $error;
			return false;
		}
		
		$records = $this->getResourceRecordDictionaries();
		$tpl->assign('records', $records);
		
		$layout = $sheets->getLayout($sheet);
		$columns = $sheets->getColumns($sheet);
		$rows = $sheets->getRows($sheet, $records);
		
		$tpl->assign('layout', $layout);
		$tpl->assign('columns', $columns);
		$tpl->assign('rows', $rows);
		
		return true;
	}
	
	public function getResources(?string &$error=null) : array|false {
		$kata = DevblocksPlatform::services()->kata();
		$error = null;
		
		if(false === ($workflow_resources = $kata->parse($this->resources_kata, $error)))
			return false;
		
		$resources_dict = DevblocksDictionaryDelegate::instance([]);
		
		if(false === ($resources = $kata->formatTree($workflow_resources ?? [], $resources_dict, $error)))
			return false;
		
		return $resources;
	}
	
	public function getResourceRecordDictionaries(?string &$error=null) : array {
		if(!($resources = $this->getResources($error)))
			return [];
		
		if(!array_key_exists('records', $resources))
			return [];
		
		$record_types = [];
		
		// Group records by type
		foreach($resources['records'] as $record_key => $record_id) {
			$record_type = DevblocksPlatform::services()->string()->strBefore($record_key, '/');
			
			if(!array_key_exists($record_type, $record_types))
				$record_types[$record_type] = [];
			
			$record_types[$record_type][$record_key] = $record_id;
		}
		
		ksort($record_types);
		
		foreach($record_types as $record_type => $records) {
			ksort($records);
			
			$models = CerberusContexts::getModels($record_type, array_values($records));
			$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $record_type, ['_label']);
			
			foreach($records as $record_name => $record_id) {
				if(array_key_exists($record_id, $dicts)) {
					
					// Record type overrides
					if('community_portal' == $record_type) {
						if (array_key_exists($record_id, $models)) {
							$dicts[$record_id]->set('params', DAO_CommunityToolProperty::getAllByTool($models[$record_id]->code));
						}
					}

					$record_types[$record_type][$record_name] = $dicts[$record_id];
					// Index dictionary keys
					$record_types[$record_type][$record_name]->set('_key', $record_name);
				} else {
					// [TODO] A missing resource should be an error/alert
					unset($record_types[$record_type][$record_name]);
				}
			}
		}
		
		return array_merge(...array_values($record_types));
	}
	
	public function setConfigValues(array $config_values=[]) : void {
		$kata = DevblocksPlatform::services()->kata();
		$current_values = $kata->parse($this->config_kata);
		$current_values = $kata->formatTree($current_values);
		$this->config_kata = $kata->emit(array_merge($current_values ?: [], $config_values));
	}
	
	public function getChangesAutomationInitialState(&$error=null) : array|false {
		if(false === ($resources = $this->getResources($error))) {
			$resources = [];
		}
		
		if(false === ($config_values = $this->getConfig($error))) {
			$config_values = [];
		}
		
		$initial_state = [
			//'__simulate' => 1,
			'config' => $config_values,
			'records' => [],
		];
		
		// Record resources
		foreach(($resources['records'] ?? []) as $record_key => $record_id) {
			$record_type = DevblocksPlatform::services()->string()->strBefore($record_key, '/');
			$record_name = DevblocksPlatform::services()->string()->strAfter($record_key, '/');
			
			$initial_state['records'][$record_name] = [
				'_context' => $record_type,
				'id' => $record_id
			];
		}
		
		return $initial_state;
	}
	
	public function getChangesAutomation(Model_Workflow $new, ?array &$resource_keys=[]) : Model_Automation|false {
		$kata = DevblocksPlatform::services()->kata();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		$error = null;
		
		$lexer = [
			'tag_comment'   => ['$$#', '#'],
			'tag_block'     => ['$$%', '%'],
			'tag_variable'  => ['$${', '}'],
			'interpolation' => ['#$${', '}'],
		];
		
		$initial_state = [];

		$funcPlaceholders = function(&$v) use ($tpl_builder, &$initial_state, &$lexer) {
			if(is_array($v))
				return;
			
			if(str_contains((string)$v, '$${')) {
				if(is_string($v)) {
					$v = $tpl_builder->build($v, $initial_state, $lexer);
				} elseif ($v instanceof DevblocksKataRawString) {
					$v->setString($tpl_builder->build($v, $initial_state, $lexer));
				}
			}
		};
		
		if(!($was_template = $this->getParsedTemplate()))
			$was_template = [];
		
		if(false === ($initial_state = $this->getChangesAutomationInitialState($error)))
			return false;
		
		array_walk_recursive($was_template, $funcPlaceholders);
		
		if(!($new_template = $new->getParsedTemplate()))
			$new_template = [];
		
		if(false === ($initial_state = $new->getChangesAutomationInitialState($error)))
			return false;
		
		array_walk_recursive($new_template, $funcPlaceholders);
		
		if(false === ($workflow_resources = $this->getResources($error)))
			return false;
		
		$automation = new Model_Automation();
		$automation->id = 0;
		$automation->policy_kata = <<< EOD
		  commands:
		    record.create:
		      allow@bool: yes
		    record.update:
		      allow@bool: yes
		    record.delete:
		      allow@bool: yes
		EOD;
		$script = [
			'start' => [],
		];
		
		$was_records = $was_template['records'] ?? [];
		$new_records = $new_template['records'] ?? [];
		
		// Record deletion
		foreach($was_records as $record_key => $was_record) {
			$record_type = DevblocksPlatform::services()->string()->strBefore($record_key, '/');
			$record_name = DevblocksPlatform::services()->string()->strAfter($record_key, '/') ?: $record_type;
			
			// Delete
			if(!array_key_exists($record_key, $new_records)) {
				$was_record_id = intval($workflow_resources['records'][$record_key] ?? null);
				$deletion_policy = DevblocksPlatform::strLower($was_record['deletionPolicy'] ?? '');
				
				// If retaining, skip deletion
				if('retain' == $deletion_policy) {
					$resource_keys['records'][$record_key] = [
						'action' => 'detach',
						'was_record_id' => $was_record_id,
					];
					continue;
				}
				
				$action = [
					'output' => $record_name,
					'inputs' => [
						'record_type' => $record_type,
						'record_id' => $was_record_id,
					],
					'on_success' => [
						'var.unset/record' => [
							'inputs' => [
								'key' => 'records:' . $record_name,
							],
						],
					],
					'on_error' => [],
				];
				
				if($was_record_id) {
					$resource_keys['records'][$record_key] = [
						'action' => 'delete',
						'record_type' => $record_type,
						'record_id' => $was_record_id,
					];
					$script['start']['record.delete/' . $record_name] = $action;
				}
			}
		}
		
		foreach($new_records as $record_key => $new_record) {
			$record_type = DevblocksPlatform::services()->string()->strBefore($record_key, '/');
			$record_name = DevblocksPlatform::services()->string()->strAfter($record_key, '/') ?: $record_type;
			
			if(array_key_exists($record_key, $was_records)) {
				$delta = $kata->treeDiff($was_records[$record_key], $new_record, '', true);
				
				// Update
				if($delta) {
					$was_record_id = intval($workflow_resources['records'][$record_key] ?? null);
					
					// If we don't have the former record, re-create instead
					if(!$was_record_id) {
						unset($was_records[$record_key]);
						
					} else {
						$action = [
							'output' => $record_name,
							'inputs' => [
								'record_type' => $record_type,
								'record_id' => $was_record_id,
								'fields' => $delta['fields'],
							],
						];
						
						if($action['inputs']['fields'] ?? null) {
							$resource_keys['records'][$record_key] = [
								'action' => 'update',
								'record_type' => $record_type,
								'record_id' => $was_record_id,
								'fields' => $delta['fields'],
							];
							$script['start']['record.update/' . $record_name] = $action;
							continue;
						}
					}
				}
			}
			
			// Create
			if(!array_key_exists($record_key, $was_records)) {
				$action = [
					'output' => 'new_record',
					'inputs' => [
						'record_type' => $record_type,
						'fields' => $new_record['fields'] ?? [],
					],
					'on_success' => [
						'var.set/record' => [
							'inputs' => [
								'key' => 'records:' . $record_name,
								'value@json' => '{{new_record|json_encode}}',
							],
						],
					],
				];
				
				if($action['inputs']['fields'] ?? null) {
					$resource_keys['records'][$record_key] = [
						'action' => 'create',
						'record_type' => $record_type,
						'fields' => $new_record['fields'] ?? [],
					];
					$script['start']['record.create/' . $record_name] = $action;
				}
			}
		}
		
		// Extensions
		
		$was_extensions = $was_template['extensions'] ?? [];
		$new_extensions = $new_template['extensions'] ?? [];
		
		// Extension deletion
		foreach($was_extensions as $extension_key => $was_extension) {
			// Delete
			if(!array_key_exists($extension_key, $new_extensions)) {
				$resource_keys['extensions'][$extension_key] = [
					'action' => 'delete',
					'was' => $was_extension,
				];
			}
		}
		
		foreach($new_extensions as $extension_key => $new_extension) {
			if(array_key_exists($extension_key, $was_extensions)) {
				$delta = $kata->treeDiff($was_extensions[$extension_key], $new_extension, '', true);
				
				// Update
				if($delta) {
					$resource_keys['extensions'][$extension_key] = [
						'action' => 'update',
						'was' => $was_extensions[$extension_key],
						'new' => $new_extension,
						'delta' => $delta,
					];
				}
				
			} else {
				// Insert
				$resource_keys['extensions'][$extension_key] = [
					'action' => 'create',
					'new' => $new_extension,
				];
			}
		}
		
		if(empty($script['start']))
			$script = [];
		
		$automation->script = $kata->emit($script);
		
		return $automation;
	}
	
	/*
	public function getTemplateDrift(?array &$resource_keys=[], array $initial_state=[]) : Model_Automation|false {
		$kata = DevblocksPlatform::services()->kata();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		$error = null;
		
		if(false === ($workflow_template = $this->getKata($error)))
			return false;
		
		if(false === ($workflow_resources = $this->getResources($error)))
			return false;
		
		// [TODO] Lazy evaluate placeholders (we need IDs in dependency order)
		
		if(!($workflow_template = $kata->formatTree($workflow_template, null, $error, true)))
			return false;
		
		$lexer = [
			'tag_comment'   => ['$$#', '#'],
			'tag_block'     => ['$$%', '%'],
			'tag_variable'  => ['$${', '}'],
			'interpolation' => ['#$${', '}'],
		];
		
		$records_template = $workflow_template['records'] ?? [];
		
		$automation = new Model_Automation();
		$automation->id = 0;
		$automation->policy_kata = <<< EOD
		  commands:
		    record.create:
		      allow@bool: yes
		    record.update:
		      allow@bool: yes
		EOD;
		$script = [
			'start' => [],
		];
		
		$record_dicts = $this->getResourceRecordDictionaries();
		
		$resource_keys['records'] = [];
		
		foreach($records_template as $record_key => $record_params) {
			$record_type = DevblocksPlatform::services()->string()->strBefore($record_key, '/');
			$record_name = DevblocksPlatform::services()->string()->strAfter($record_key, '/') ?: $record_type;
			
			$record_fields = $record_params['fields'] ?? [];
			
			if(array_key_exists($record_key, $record_dicts)) {
				$record_fields = array_filter(
					$record_fields,
					function($v,$k) use ($record_dicts, $record_key, &$tpl_builder, &$initial_state, $lexer) {
						$vv = $record_dicts[$record_key]->get($k);
						
						if($v instanceof DevblocksKataRawString)
							$v = (string) $v;
						
						if(
							is_string($v)
							&& str_contains($v, '$${')
						) {
							$v = $tpl_builder->build($v, $initial_state, $lexer);
						}
						
						if(is_array($v)) { // This should be done in a nested manner for arrays (e.g. params->keys)
							array_walk_recursive($v, function(&$val) use ($tpl_builder, $initial_state, $lexer) {
								if($val instanceof DevblocksKataRawString)
									$val = (string) $val;
								
								if(
									is_string($val)
									&& str_contains($val, '${{')
								) {
									$val = $tpl_builder->build($val, $initial_state, $lexer);
								}
							});
						}
						
						if($k == 'context' || DevblocksPlatform::strEndsWith($k, '_context')) {
							if(CerberusContexts::isSameContext($v, $vv))
								return false;
							
						} else {
							// [TODO] Make a more sophisticated comparator that checks types + ignores line endings
							if(is_string($v) && is_string($vv)) {
								$v = trim(str_replace("\r", "", $v));
								$vv = trim(str_replace("\r", "", $vv));
							}
							
							if($v == $vv)
								return false;
						}
						
						return true;
					},
					ARRAY_FILTER_USE_BOTH
				);
			}
			
			if(empty($record_fields))
				continue;
			
			if(array_key_exists($record_key, ($workflow_resources['records'] ?? []))) {
				$action = [
					'output' => $record_name,
					'inputs' => [
						'record_type' => $record_type,
						'record_id' => intval($workflow_resources['records'][$record_key]),
						'fields' => $record_fields,
					],
				];
				
				$resource_keys['records'][$record_key] = 'update';
				$script['start']['record.update/' . uniqid()] = $action;
				
			} else {
				$action = [
					'output' => 'new_record',
					'inputs' => [
						'record_type' => $record_type,
						'fields' => $record_fields,
					],
					'on_success' => [
						'var.set/record' => [
							'inputs' => [
								'key' => 'records:' . $record_name,
								'value@json' => '{{new_record|json_encode}}',
							],
						],
					],
				];
				
				$resource_keys['records'][$record_key] = 'create';
				$script['start']['record.create/' . uniqid()] = $action;
			}
		}
		
		if(empty($script['start']))
			$script = [];
		
		$automation->script = $kata->emit($script);
		
		if(str_contains($automation->script, '$${')) {
			$automation->script = $tpl_builder->build($automation->script, $initial_state, $lexer);
		}
		
		return $automation;
	}
	*/
	
	public function getParsedTemplate(): array|false {
		$kata = DevblocksPlatform::services()->kata();
		
		$error = null;
		
		if(false === ($workflow_template = $this->getKata($error)))
			return false;
		
		if(!($workflow_template = $kata->formatTree($workflow_template, null, $error, true)))
			return false;
		
		return $workflow_template;
	}
};

class View_Workflow extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'workflow';
	
	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('common.workflows');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Workflow::ID;
		$this->renderSortAsc = true;
		
		$this->view_columns = [
			SearchFields_Workflow::NAME,
			SearchFields_Workflow::DESCRIPTION,
			SearchFields_Workflow::UPDATED_AT,
		];
		
		$this->addColumnsHidden([
			SearchFields_Workflow::WORKFLOW_KATA,
			SearchFields_Workflow::CONFIG_KATA,
			SearchFields_Workflow::RESOURCES_KATA,
			SearchFields_Workflow::VIRTUAL_CONTEXT_LINK,
			SearchFields_Workflow::VIRTUAL_HAS_FIELDSET,
			SearchFields_Workflow::VIRTUAL_WATCHERS,
		]);
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_Workflow::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_Workflow');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null, &$total=null) {
		return $this->_getDataAsObjects('DAO_Workflow', $ids, $total);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_Workflow', $size);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];
		
		if(is_array($all_fields))
			foreach($all_fields as $field_key => $field_model) {
				$pass = false;
				
				switch($field_key) {
					// Virtuals
					case SearchFields_Workflow::VIRTUAL_CONTEXT_LINK:
					case SearchFields_Workflow::VIRTUAL_HAS_FIELDSET:
					case SearchFields_Workflow::VIRTUAL_WATCHERS:
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
		$context = CerberusContexts::CONTEXT_WORKFLOW;
		
		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
//			case SearchFields_Workflow::EXAMPLE_BOOL:
//				$counts = $this->_getSubtotalCountForBooleanColumn($context, $column);
//				break;

//			case SearchFields_Workflow::EXAMPLE_STRING:
//				$counts = $this->_getSubtotalCountForStringColumn($context, $column);
//				break;
			
			case SearchFields_Workflow::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;
			
			case SearchFields_Workflow::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
			
			case SearchFields_Workflow::VIRTUAL_WATCHERS:
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
		// [TODO] Implement quick search fields
		$search_fields = SearchFields_Workflow::getFields();
		
		$fields = [
			'text' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => ['param_key' => SearchFields_Workflow::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
				],
			'created' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => ['param_key' => SearchFields_Workflow::CREATED_AT],
				],
			'fieldset' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => ['param_key' => SearchFields_Workflow::VIRTUAL_HAS_FIELDSET],
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_WORKFLOW],
					]
				],
			'id' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => ['param_key' => SearchFields_Workflow::ID],
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_WORKFLOW, 'q' => ''],
					]
				],
			'name' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => ['param_key' => SearchFields_Workflow::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
				],
			'updated' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => ['param_key' => SearchFields_Workflow::UPDATED_AT],
				],
			'watchers' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => ['param_key' => SearchFields_Workflow::VIRTUAL_WATCHERS],
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					],
				],
		];
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_Workflow::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_WORKFLOW, $fields, null);
		
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
			
			case 'watchers':
				return DevblocksSearchCriteria::getWatcherParamFromTokens(SearchFields_Workflow::VIRTUAL_WATCHERS, $tokens);
			
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
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_WORKFLOW);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->assign('view_template', 'devblocks:cerberusweb.core::records/types/workflow/view.tpl');
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
			case SearchFields_Workflow::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
			
			case SearchFields_Workflow::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
			
			case SearchFields_Workflow::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}
	
	function getFields() {
		return SearchFields_Workflow::getFields();
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;
		
		switch($field) {
			case SearchFields_Workflow::WORKFLOW_KATA:
			case SearchFields_Workflow::CONFIG_KATA:
			case SearchFields_Workflow::DESCRIPTION:
			case SearchFields_Workflow::NAME:
			case SearchFields_Workflow::RESOURCES_KATA:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
			
			case SearchFields_Workflow::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
			
			case SearchFields_Workflow::CREATED_AT:
			case SearchFields_Workflow::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
			
			case 'placeholder_bool':
				$bool = DevblocksPlatform::importGPC($_POST['bool'] ?? null, 'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
			
			case SearchFields_Workflow::VIRTUAL_CONTEXT_LINK:
				$context_links = DevblocksPlatform::importGPC($_POST['context_link'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
			
			case SearchFields_Workflow::VIRTUAL_HAS_FIELDSET:
				$options = DevblocksPlatform::importGPC($_POST['options'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
			
			case SearchFields_Workflow::VIRTUAL_WATCHERS:
				$worker_ids = DevblocksPlatform::importGPC($_POST['worker_id'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_ids);
				break;
			
			default:
				// Custom Fields
				if(DevblocksPlatform::strStartsWith($field, 'cf_')) {
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

class Context_Workflow extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	const ID = CerberusContexts::CONTEXT_WORKFLOW;
	const URI = 'workflow';
	
	static function isReadableByActor($models, $actor) {
		// Everyone can read
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Everyone can modify
		return CerberusContexts::allowEverything($models);
	}
	
	static function isDeletableByActor($models, $actor) {
		return self::isWriteableByActor($models, $actor);
	}
	
	function getRandom() {
		return DAO_Workflow::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
		
		$url_writer = DevblocksPlatform::services()->url();
		return $url_writer->writeNoProxy('c=profiles&type=workflow&id='.$context_id, true);
	}
	
	// [TODO] Profile fields
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_Workflow();
		
		$properties['created'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.created'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->created_at,
		];
		
		$properties['name'] = [
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		];
		
		$properties['updated'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		];
		
		$properties['id'] = [
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		];
		
		return $properties;
	}
	
	function getMeta($context_id) {
		if(null == ($workflow = DAO_Workflow::get($context_id)))
			return [];
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($workflow->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return [
			'id' => $workflow->id,
			'name' => $workflow->name,
			'permalink' => $url,
			'updated' => $workflow->updated_at,
		];
	}
	
	function getDefaultProperties() {
		return [
			'updated_at',
		];
	}
	
	/*
function getContextIdFromAlias($alias) {
	// Is it a URI?
	if(false != ($model = DAO_Workflow::getByUri($alias)))
		return $model->id;
	
	return null;
}
	*/
	
	function getContext($workflow, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Workflow:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_WORKFLOW);
		
		// Polymorph
		if(is_numeric($workflow)) {
			$workflow = DAO_Workflow::get($workflow);
		} elseif($workflow instanceof Model_Workflow) {
			// It's what we want already.
			DevblocksPlatform::noop();
		} elseif(is_array($workflow)) {
			$workflow = Cerb_ORMHelper::recastArrayToModel($workflow, 'Model_Workflow');
		} else {
			$workflow = null;
		}
		
		// Token labels
		$token_labels = [
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		];
		
		// Token types
		$token_types = [
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'updated_at' => Model_CustomField::TYPE_DATE,
			'record_url' => Model_CustomField::TYPE_URL,
		];
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = [];
		
		$token_values['_context'] = CerberusContexts::CONTEXT_WORKFLOW;
		$token_values['_type'] = 'workflow';
		$token_values['_types'] = $token_types;
		
		if($workflow) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $workflow->name;
			$token_values['id'] = $workflow->id;
			$token_values['name'] = $workflow->name;
			$token_values['updated_at'] = $workflow->updated_at;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($workflow, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=workflow&id=%d-%s",$workflow->id, DevblocksPlatform::strToPermalink($workflow->name)), true);
		}
		
		return true;
	}
	
	// [TODO]
	function getKeyToDaoFieldMap() {
		return [
			'id' => DAO_Workflow::ID,
			'links' => '_links',
			'name' => DAO_Workflow::NAME,
			'updated_at' => DAO_Workflow::UPDATED_AT,
		];
	}
	
	// [TODO]
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
		$lazy_keys = parent::lazyLoadGetKeys();
		return $lazy_keys;
	}
	
	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_WORKFLOW;
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
		
		// View
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Workflows';
		/*
		$view->addParams([
			SearchFields_Workflow::UPDATED_AT => new DevblocksSearchCriteria(SearchFields_Workflow::UPDATED_AT,'=',0),
		], true);
		*/
		$view->renderSortBy = SearchFields_Workflow::UPDATED_AT;
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
		$view->name = 'Workflows';
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = [
				new DevblocksSearchCriteria(SearchFields_Workflow::VIRTUAL_CONTEXT_LINK,'in',[$context.':'.$context_id]),
			];
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = CerberusContexts::CONTEXT_WORKFLOW;
		
		$tpl->assign('view_id', $view_id);
		
		$model = null;
		$error = null;
		
		if($context_id) {
			if(!($model = DAO_Workflow::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 403);
		}
		
		if(!$model) {
			$model = new Model_Workflow();
			$model->id = 0;
		}
		
		if(empty($context_id) || $edit) {
			if(!CerberusContexts::isWriteableByActor($context, $model, $active_worker))
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			// Custom fields
			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);
			
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
			
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);
			
			// Summary
			if(!$model->populateTemplateSummary($tpl, $error)) {
			}
			
			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('model', $model);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::records/types/workflow/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};
