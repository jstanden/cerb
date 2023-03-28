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
			->setUnique(get_class())
			->addValidator(function($string, &$error=null) {
				if(0 != strcmp($string, DevblocksPlatform::strAlphaNum($string, '.-_'))) {
					$error = "may only contain letters, numbers, dashes, and dots";
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
			->string()
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
		if(!is_array($ids))
			$ids = array($ids);
			
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
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.automation.update',
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
		parent::_updateWhere('automation', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_AUTOMATION;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		if(!CerberusContexts::isActorAnAdmin($actor)) {
			$error = DevblocksPlatform::translate('error.core.no_acl.admin');
			return false;
		}
		
		$dict = DevblocksDictionaryDelegate::instance([]);
		
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
		
		if(false == $results || 1 != count($results))
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
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	public static function getByUri(string $interaction_uri, $extension_ids=null) {
		if(!is_null($extension_ids) && !is_array($extension_ids))
			$extension_ids = [$extension_ids];
		
		if(DevblocksPlatform::strStartsWith($interaction_uri, 'cerb:')) {
			if(false == ($uri_parts = DevblocksPlatform::services()->ui()->parseURI($interaction_uri)))
				return null;
			
			if(CerberusContexts::CONTEXT_AUTOMATION != $uri_parts['context'])
				return null;
			
			$interaction_uri = $uri_parts['context_id'];
		}
		
		// [TODO] Cache
		$objects = self::getWhere(sprintf("%s = %s",
				self::NAME,
				Cerb_ORMHelper::qstr($interaction_uri)
			),
			null,
			null,
			1
		);
		
		if(!$objects)
			return null;
		
		$object = array_shift($objects);
		
		if(!$extension_ids || in_array($object->extension_id, $extension_ids))
			return $object;
		
		return null;
	}
	
	public static function getByUris(array $uris, string $extension_id=null) {
		if(!$uris)
			return [];
		
		// [TODO] Cache
		$objects = self::getWhere(sprintf("%s IN (%s)",
			self::NAME,
			implode(',', Cerb_ORMHelper::qstrArray($uris))
		));
		
		if(!$objects)
			return [];
		
		if(!$extension_id)
			return $objects;
		
		return array_filter($objects, function($automation) use ($extension_id) {
			if($extension_id == $automation->extension_id)
				return true;
			
			return false;
		});
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
		
		if(!is_array($ids))
			$ids = [$ids];
		
		if(empty($ids))
			return true;
		
		$ids_list = implode(',', $ids);
		
		DAO_RecordChangeset::delete('automation', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM automation WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_AUTOMATION,
					'context_ids' => $ids
				)
			)
		);
		
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
		
		$db->ExecuteMaster(sprintf("INSERT INTO automation (name, description, extension_id, script, policy_kata, created_at, updated_at) ".
			"VALUES (%s, %s, %s, %s, %s, %d, %d) ".
			"ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), name=VALUES(name), extension_id=VALUES(extension_id), script=VALUES(script), policy_kata=VALUES(policy_kata), created_at=VALUES(created_at), updated_at=VALUES(updated_at)",
			$db->qstr($automation_data['name']),
			$db->qstr($automation_data['description']),
			$db->qstr($automation_data['extension_id']),
			$db->qstr($automation_data['script']),
			$db->qstr($automation_data['policy_kata']),
			$automation_data['created_at'],
			$automation_data['updated_at']
		));
		
		return $db->LastInsertId();
	}
};

class SearchFields_Automation extends DevblocksSearchFields {
	const CREATED_AT = 'a_created_at';
	const DESCRIPTION = 'a_description';
	const EXTENSION_ID = 'a_extension_id';
	const NAME = 'a_name';
	const ID = 'a_id';
	const UPDATED_AT = 'a_updated_at';

	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_WATCHERS = '*_workers';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'automation.id';
	}
	
	static function getCustomFieldContextKeys() {
		return [
			CerberusContexts::CONTEXT_AUTOMATION => new DevblocksSearchFieldContextKeys('automation.id', self::ID),
		];
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_AUTOMATION, self::getPrimaryKey());
			
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_AUTOMATION), '%s'), self::getPrimaryKey());
			
			case self::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, CerberusContexts::CONTEXT_AUTOMATION, self::getPrimaryKey());
				
			default:
				if('cf_' == substr($param->field, 0, 3)) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
		}
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
		
		$columns = array(
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'automation', 'created_at', $translate->_('common.created'), null, true),
			self::DESCRIPTION => new DevblocksSearchField(self::DESCRIPTION, 'automation', 'description', $translate->_('common.description'), null, true),
			self::EXTENSION_ID => new DevblocksSearchField(self::EXTENSION_ID, 'automation', 'extension_id', $translate->_('common.extension'), null, true),
			self::ID => new DevblocksSearchField(self::ID, 'automation', 'id', $translate->_('common.id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'automation', 'name', $translate->_('common.name'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'automation', 'updated_at', $translate->_('common.updated'), null, true),

			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers'), 'WS', false),
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

class Model_Automation {
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
	
	private $_ast = null;
	private $_ast_symbols = null;
	
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
	
	public function getSyntaxTree(&$error=null, &$symbol_meta=[]) {
		// If cached
		if(!is_null($this->_ast)) {
			$symbol_meta = $this->_ast_symbols;
			return $this->_ast;
		}
		
		$automator = DevblocksPlatform::services()->automation();
		
		$error = null;
		
		$tree = DevblocksPlatform::services()->kata()->parse($this->script, $error, true, $this->_ast_symbols);
		
		if(!is_array($tree))
			return false;
		
		unset($tree['inputs']);
		
		$this->_ast = $automator->buildAstFromKata($tree, $error);
		
		$symbol_meta = $this->_ast_symbols;
		
		return $this->_ast;
	}
	
	/**
	 * @param DevblocksDictionaryDelegate $dict
	 * @param string $error
	 * @return DevblocksDictionaryDelegate|false
	 */
	public function execute(DevblocksDictionaryDelegate $dict, &$error=null) {
		$automator = DevblocksPlatform::services()->automation();
		
		if(false == $automator->runAST($this, $dict, $error))
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
			DAO_AutomationLog::AUTOMATION_NODE => $node_path,
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

		$this->view_columns = array(
			SearchFields_Automation::NAME,
			SearchFields_Automation::EXTENSION_ID,
			SearchFields_Automation::UPDATED_AT,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_Automation::VIRTUAL_CONTEXT_LINK,
			SearchFields_Automation::VIRTUAL_HAS_FIELDSET,
			SearchFields_Automation::VIRTUAL_WATCHERS,
		));
		
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
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_Automation', $ids);
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
					
				// Virtuals
				case SearchFields_Automation::VIRTUAL_CONTEXT_LINK:
				case SearchFields_Automation::VIRTUAL_HAS_FIELDSET:
				case SearchFields_Automation::VIRTUAL_WATCHERS:
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
		$context = CerberusContexts::CONTEXT_AUTOMATION;

		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_Automation::EXTENSION_ID:
				$counts = $this->_getSubtotalCountForStringColumn($context, $column);
				break;
				
			case SearchFields_Automation::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;

			case SearchFields_Automation::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
				
			case SearchFields_Automation::VIRTUAL_WATCHERS:
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
		$search_fields = SearchFields_Automation::getFields();
	
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Automation::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
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
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Automation::VIRTUAL_HAS_FIELDSET),
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
					'options' => array('param_key' => SearchFields_Automation::VIRTUAL_WATCHERS),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					],
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_Automation::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_AUTOMATION, $fields, null);
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		ksort($fields);
		
		return $fields;
	}	
	
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'fieldset':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, '*_has_fieldset');
			
			case 'watchers':
				return DevblocksSearchCriteria::getWatcherParamFromTokens(SearchFields_Automation::VIRTUAL_WATCHERS, $tokens);
			
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

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_Automation::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_Automation::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
				
			case SearchFields_Automation::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
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
				
			case SearchFields_Automation::VIRTUAL_CONTEXT_LINK:
				$context_links = DevblocksPlatform::importGPC($_POST['context_link'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_Automation::VIRTUAL_HAS_FIELDSET:
				$options = DevblocksPlatform::importGPC($_POST['options'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_Automation::VIRTUAL_WATCHERS:
				$worker_ids = DevblocksPlatform::importGPC($_POST['worker_id'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_ids);
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

class Context_Automation extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek, IDevblocksContextAutocomplete, IDevblocksContextUri {
	const ID = CerberusContexts::CONTEXT_AUTOMATION;
	const URI = 'automation';
	
	static function isReadableByActor($models, $actor) {
		// Everyone can read
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Only admin workers can modify
		
		if(false == ($actor = CerberusContexts::polymorphActorToDictionary($actor)))
			return CerberusContexts::denyEverything($models);
		
		if(CerberusContexts::isActorAnAdmin($actor))
			return CerberusContexts::allowEverything($models);
		
		return CerberusContexts::denyEverything($models);
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
	
	function getDefaultProperties() {
		return array(
			'trigger_event',
			'updated_at',
		);
	}
	
	function autocompleteUri($term, $uri_params=null) : array {
		$query = null;
		
		if(array_key_exists('triggers', $uri_params) && $uri_params['triggers']) {
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
			true;
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
	
	// [TODO] Params
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
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
				
				if(false == ($automation = DAO_Automation::get($context_id)))
					break;
				
				$kata = DevblocksPlatform::services()->kata();
				$error = null;
				
				$automation_kata = $kata->parse($automation->script, $error, true);
			
				$inputs = [];
				
				if(array_key_exists('inputs', $automation_kata)) {
					$inputs = $kata->formatTree($automation_kata['inputs'], DevblocksDictionaryDelegate::instance([]));
					
					foreach ($inputs as $k => &$input) {
						list($input_type, $input_key) = array_pad(explode('/', $k, 2), 2, '');
						$input['key'] = $input_key;
						$input['type'] = $input_type;
					}
				}
				
				$values['inputs'] = $inputs;
				break;
				
			case 'outputs':
				$values['outputs'] = [];
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
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Automation::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
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
		} else {
			//DevblocksPlatform::dieWithHttpError(null, 404);
		}
		
		if(!$context_id || $edit) {
			if($model) {
				if(!Context_Automation::isWriteableByActor($model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
			} else {
				$model = new Model_Automation();
				$model->id = 0;
				$model->script = "# [TODO] You can optionally declare custom inputs here\n#inputs:\n#  text/name:\n#    required@bool: yes\n#  record/ticket:\n#    required@bool: yes\n#    record_type: ticket\n\nstart:\n  # [TODO] Your logic goes here (use Ctrl+Space for autocompletion)\n  ";
				$model->policy_kata = "commands:\n  # [TODO] Specify a command policy here (use Ctrl+Space for autocompletion)\n  ";
				
				if(is_string($lookup_id) && $lookup_id)
					$model->name = $lookup_id;
			}
			
			// Trigger extensions
			$extensions = Extension_AutomationTrigger::getAll(false);
			$tpl->assign('extensions', $extensions);
			
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
			$tpl->assign('model', $model);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::internal/automation/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};
