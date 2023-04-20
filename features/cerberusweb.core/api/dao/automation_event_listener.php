<?php
/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

class DAO_AutomationEventListener extends Cerb_ORMHelper {
	const CREATED_AT = 'created_at';
	const EVENT_KATA = 'event_kata';
	const EVENT_NAME = 'event_name';
	const ID = 'id';
	const IS_DISABLED = 'is_disabled';
	const NAME = 'name';
	const PRIORITY = 'priority';
	const UPDATED_AT = 'updated_at';
	const WORKFLOW_ID = 'workflow_id';
	
	const _CACHE_ALL = 'automation_event_listeners_all';

	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::CREATED_AT)
			->timestamp()
		;
		$validation
			->addField(self::EVENT_NAME)
			->string()
			->setRequired(true)
		;
		$validation
			->addField(self::EVENT_KATA)
			->string()
			->setMaxLength('24 bits')
		;
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
		;
		$validation
			->addField(self::IS_DISABLED)
			->number()
			->setMin(0)
			->setMax(1)
		;
		$validation
			->addField(self::NAME)
			->string()
			->setRequired(true)
		;
		$validation
			->addField(self::PRIORITY)
			->number()
			->setMin(0)
			->setMax(255)
		;
		$validation
			->addField(self::UPDATED_AT)
			->timestamp()
		;
		$validation
			->addField(self::WORKFLOW_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_WORKFLOW, true))
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
		
		if(!array_key_exists(DAO_AutomationEventListener::CREATED_AT, $fields))
			$fields[DAO_AutomationEventListener::CREATED_AT] = time();
		
		$sql = "INSERT INTO automation_event_listener () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations(Context_AutomationEventListener::ID, $id);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = [$ids];
		
		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
		
		$context = CerberusContexts::CONTEXT_AUTOMATION_EVENT_LISTENER;
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
			parent::_update($batch_ids, 'automation_event_listener', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.automation_event_listener.update',
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
		self::clearCache();
		parent::_updateWhere('automation_event_listener', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_AUTOMATION_EVENT_LISTENER;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		if(!CerberusContexts::isActorAnAdmin($actor)) {
			$error = DevblocksPlatform::translate('error.core.no_acl.admin');
			return false;
		}
		
		if(array_key_exists(self::EVENT_KATA, $fields)) {
			$kata = DevblocksPlatform::services()->kata();
			
			if(false === $kata->validate($fields[self::EVENT_KATA], CerberusApplication::kataSchemas()->automationEvent(), $error))
				return false;
		}
		
		return true;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_AutomationEventListener[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();
		
		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		$sql = "SELECT id, name, event_name, is_disabled, priority, created_at, updated_at, event_kata, workflow_id ".
			"FROM automation_event_listener ".
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
	 * @return Model_AutomationEventListener[]
	 */
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::services()->cache();
		if($nocache || null === ($objects = $cache->load(self::_CACHE_ALL))) {
			$objects = self::getWhere(null, DAO_AutomationEventListener::PRIORITY, true, null, DevblocksORMHelper::OPT_GET_MASTER_ONLY);
		
			if(!is_array($objects))
				return [];
		
			$cache->save($objects, self::_CACHE_ALL);
		}
		
		return $objects;
	}
	
	/**
	 * @param integer $id
	 * @return Model_AutomationEventListener
	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$objects = self::getAll();
		
		if(array_key_exists($id, $objects))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 *
	 * @param array $ids
	 * @return Model_AutomationEventListener[]
	 */
	static function getIds(array $ids) : array {
		return parent::getIds($ids);
	}
	
	static function getByEvent($event_name, $with_disabled=false) {
		$event_listeners = self::getAll();
		
		return array_filter(
			$event_listeners,
			fn($listener) => ($with_disabled || !$listener->is_disabled) && $listener->event_name == $event_name
		);
	} 
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_AutomationEventListener[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return [];
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_AutomationEventListener();
			$object->id = intval($row['id'] ?? 0);
			$object->name = $row['name'] ?? '';
			$object->event_name = $row['event_name'] ?? '';
			$object->is_disabled = intval($row['is_disabled'] ?? 0);
			$object->priority = intval($row['priority'] ?? 50);
			$object->created_at = intval($row['created_at'] ?? 0);
			$object->updated_at = intval($row['updated_at'] ?? 0);
			$object->event_kata = $row['event_kata'] ?? '';
			$object->workflow_id = intval($row['workflow_id'] ?? 0);
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('automation_event_listener');
	}

	static function clearCache() {
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::_CACHE_ALL);
	}
	
	static function delete($ids) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($ids))
			$ids = [$ids];
		
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		if(empty($ids))
			return false;
		
		$context = CerberusContexts::CONTEXT_AUTOMATION_EVENT_LISTENER;
		$ids_list = implode(',', self::qstrArray($ids));
		
		parent::_deleteAbstractBefore($context, $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM automation_event_listener WHERE id IN (%s)", $ids_list));
		
		parent::_deleteAbstractAfter($context, $ids);

		self::clearCache();
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_AutomationEventListener::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_AutomationEventListener', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"automation_event_listener.id as %s, ".
			"automation_event_listener.name as %s, ".
			"automation_event_listener.event_name as %s, ".
			"automation_event_listener.is_disabled as %s, ".
			"automation_event_listener.priority as %s, ".
			"automation_event_listener.created_at as %s, ".
			"automation_event_listener.updated_at as %s, ".
			"automation_event_listener.workflow_id as %s ",
			SearchFields_AutomationEventListener::ID,
			SearchFields_AutomationEventListener::NAME,
			SearchFields_AutomationEventListener::EVENT_NAME,
			SearchFields_AutomationEventListener::IS_DISABLED,
			SearchFields_AutomationEventListener::PRIORITY,
			SearchFields_AutomationEventListener::CREATED_AT,
			SearchFields_AutomationEventListener::UPDATED_AT,
			SearchFields_AutomationEventListener::WORKFLOW_ID
		);
		
		$join_sql = "FROM automation_event_listener ";
		
		$where_sql = (!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
		
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_AutomationEventListener');
		
		return [
			'primary_table' => 'automation_event_listener',
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
			SearchFields_AutomationEventListener::ID,
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

class SearchFields_AutomationEventListener extends DevblocksSearchFields {
	const ID = 'a_id';
	const NAME = 'a_name';
	const EVENT_NAME = 'a_event_name';
	const IS_DISABLED = 'a_is_disabled';
	const PRIORITY = 'a_priority';
	const CREATED_AT = 'a_created_at';
	const UPDATED_AT = 'a_updated_at';
	const WORKFLOW_ID = 'a_workflow_id';
	
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'automation_event_listener.id';
	}
	
	static function getCustomFieldContextKeys() {
		return [
			CerberusContexts::CONTEXT_AUTOMATION_EVENT_LISTENER => new DevblocksSearchFieldContextKeys('automation_event_listener.id', self::ID),
			CerberusContexts::CONTEXT_WORKFLOW => new DevblocksSearchFieldContextKeys('automation_event_listener.workflow_id', self::WORKFLOW_ID),
		];
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_AUTOMATION_EVENT_LISTENER, self::getPrimaryKey());
			
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_AUTOMATION_EVENT_LISTENER), '%s'), self::getPrimaryKey());
			
			default:
				if(DevblocksPlatform::strStartsWith($param->field, 'cf_')) {
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
		if($key == SearchFields_AutomationEventListener::ID) {
			$models = DAO_AutomationEventListener::getIds($values);
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
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'automation_event_listener', 'created_at', $translate->_('common.created'), null, true),
			self::EVENT_NAME => new DevblocksSearchField(self::EVENT_NAME, 'automation_event_listener', 'event_name', $translate->_('common.event'), null, true),
			self::ID => new DevblocksSearchField(self::ID, 'automation_event_listener', 'id', $translate->_('common.id'), null, true),
			self::IS_DISABLED => new DevblocksSearchField(self::IS_DISABLED, 'automation_event_listener', 'is_disabled', $translate->_('common.disabled'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'automation_event_listener', 'name', $translate->_('common.name'), null, true),
			self::PRIORITY => new DevblocksSearchField(self::PRIORITY, 'automation_event_listener', 'priority', $translate->_('common.priority'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'automation_event_listener', 'updated_at', $translate->_('common.updated'), null, true),
			self::WORKFLOW_ID => new DevblocksSearchField(self::WORKFLOW_ID, 'automation_event_listener', 'workflow_id', $translate->_('common.workflow'), null, true),
			
			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
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

class Model_AutomationEventListener extends DevblocksRecordModel {
	public int $created_at = 0;
	public int $id = 0;
	public int $is_disabled = 0;
	public int $priority = 0;
	public int $updated_at = 0;
	public int $workflow_id = 0;
	public string $event_kata = '';
	public string $event_name = '';
	public string $name = '';
	
	public function getEventExtension() : ?Extension_AutomationTrigger {
		if(!($event = DAO_AutomationEvent::getByName($this->event_name)))
			return null;
		
		/** @noinspection PhpUnnecessaryLocalVariableInspection */
		$ext = $event->getExtension(); /* @var $ext Extension_AutomationTrigger */
		
		return $ext;
	}
};

class View_AutomationEventListener extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'automation_event_listeners';
	
	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('common.automation.event.listeners');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_AutomationEventListener::PRIORITY;
		$this->renderSortAsc = true;
		
		$this->view_columns = [
			SearchFields_AutomationEventListener::NAME,
			SearchFields_AutomationEventListener::EVENT_NAME,
			SearchFields_AutomationEventListener::PRIORITY,
			SearchFields_AutomationEventListener::IS_DISABLED,
			SearchFields_AutomationEventListener::WORKFLOW_ID,
			SearchFields_AutomationEventListener::UPDATED_AT,
		];
		
		$this->addColumnsHidden([
			SearchFields_AutomationEventListener::VIRTUAL_CONTEXT_LINK,
			SearchFields_AutomationEventListener::VIRTUAL_HAS_FIELDSET,
		]);
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_AutomationEventListener::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_AutomationEventListener');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null, &$total=null) {
		return $this->_getDataAsObjects('DAO_AutomationEventListener', $ids, $total);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_AutomationEventListener', $size);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];
		
		if(is_array($all_fields))
			foreach($all_fields as $field_key => $field_model) {
				$pass = false;
				
				switch($field_key) {
					case SearchFields_AutomationEventListener::EVENT_NAME:
					case SearchFields_AutomationEventListener::IS_DISABLED:
					case SearchFields_AutomationEventListener::PRIORITY:
					case SearchFields_AutomationEventListener::WORKFLOW_ID:
					case SearchFields_AutomationEventListener::VIRTUAL_CONTEXT_LINK:
					case SearchFields_AutomationEventListener::VIRTUAL_HAS_FIELDSET:
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
		$context = CerberusContexts::CONTEXT_AUTOMATION_EVENT_LISTENER;
		
		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_AutomationEventListener::IS_DISABLED:
				$counts = $this->_getSubtotalCountForBooleanColumn($context, $column);
				break;

			case SearchFields_AutomationEventListener::EVENT_NAME:
				$counts = $this->_getSubtotalCountForStringColumn($context, $column);
				break;
			
			case SearchFields_AutomationEventListener::PRIORITY:
			case SearchFields_AutomationEventListener::WORKFLOW_ID:
				$counts = $this->_getSubtotalCountForNumberColumn($context, $column);
				break;
			
			case SearchFields_AutomationEventListener::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;
			
			case SearchFields_AutomationEventListener::VIRTUAL_HAS_FIELDSET:
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
		$search_fields = SearchFields_AutomationEventListener::getFields();
		
		$fields = [
			'text' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => ['param_key' => SearchFields_AutomationEventListener::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
				],
			'created' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => ['param_key' => SearchFields_AutomationEventListener::CREATED_AT],
				],
			'event' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => ['param_key' => SearchFields_AutomationEventListener::EVENT_NAME],
				],
			'fieldset' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => ['param_key' => SearchFields_AutomationEventListener::VIRTUAL_HAS_FIELDSET],
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_AUTOMATION_EVENT_LISTENER],
					]
				],
			'id' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => ['param_key' => SearchFields_AutomationEventListener::ID],
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_AUTOMATION_EVENT_LISTENER, 'q' => ''],
					]
				],
			'isDisabled' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => ['param_key' => SearchFields_AutomationEventListener::IS_DISABLED],
				],
			'name' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => ['param_key' => SearchFields_AutomationEventListener::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL],
				],
			'priority' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => ['param_key' => SearchFields_AutomationEventListener::PRIORITY],
				],
			'updated' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => ['param_key' => SearchFields_AutomationEventListener::UPDATED_AT],
				],
			'workflow.id' =>
				[
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => ['param_key' => SearchFields_AutomationEventListener::WORKFLOW_ID],
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_WORKFLOW, 'q' => ''],
					]
				],
		];
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_AutomationEventListener::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_AUTOMATION_EVENT_LISTENER, $fields, null);
		
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
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_AUTOMATION_EVENT_LISTENER);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->assign('view_template', 'devblocks:cerberusweb.core::records/types/automation_event_listener/view.tpl');
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
			case SearchFields_AutomationEventListener::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
			
			case SearchFields_AutomationEventListener::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
		}
	}
	
	function getFields() {
		return SearchFields_AutomationEventListener::getFields();
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;
		
		switch($field) {
			case SearchFields_AutomationEventListener::NAME:
			case SearchFields_AutomationEventListener::EVENT_NAME:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
			
			case SearchFields_AutomationEventListener::ID:
			case SearchFields_AutomationEventListener::PRIORITY:
			case SearchFields_AutomationEventListener::WORKFLOW_ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
			
			case SearchFields_AutomationEventListener::CREATED_AT:
			case SearchFields_AutomationEventListener::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
			
			case SearchFields_AutomationEventListener::IS_DISABLED:
				$bool = DevblocksPlatform::importGPC($_POST['bool'] ?? null, 'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
			
			case SearchFields_AutomationEventListener::VIRTUAL_CONTEXT_LINK:
				$context_links = DevblocksPlatform::importGPC($_POST['context_link'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
			
			case SearchFields_AutomationEventListener::VIRTUAL_HAS_FIELDSET:
				$options = DevblocksPlatform::importGPC($_POST['options'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
			
			default:
				// Custom Fields
				if(str_starts_with($field, 'cf_')) {
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

class Context_AutomationEventListener extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	const ID = CerberusContexts::CONTEXT_AUTOMATION_EVENT_LISTENER;
	const URI = 'automation_event_listener';
	
	static function isReadableByActor($models, $actor) : bool {
		// Everyone can read
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) : bool {
		// Only admin workers can modify
		
		if(!($actor = CerberusContexts::polymorphActorToDictionary($actor)))
			return CerberusContexts::denyEverything($models);
		
		if(CerberusContexts::isActorAnAdmin($actor))
			return CerberusContexts::allowEverything($models);
		
		return CerberusContexts::denyEverything($models);
	}
	
	static function isDeletableByActor($models, $actor) : bool {
		return self::isWriteableByActor($models, $actor);
	}
	
	function getRandom() {
		return DAO_AutomationEventListener::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
		
		$url_writer = DevblocksPlatform::services()->url();
		return $url_writer->writeNoProxy('c=profiles&type=automation_event_listener&id='.$context_id, true);
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_AutomationEventListener();
		
		$properties['created'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.created'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->created_at,
		];

		$properties['event_name'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.automation.event'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->event_name,
		];
		
		$properties['id'] = [
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		];

		$properties['is_disabled'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.disabled'),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $model->is_disabled,
		];
		
		$properties['name'] = [
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		];
		
		$properties['priority'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.priority'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->priority,
		];
		
		$properties['updated'] = [
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		];
		
		$properties['workflow_id'] = [
			'label' => mb_ucfirst($translate->_('common.workflow')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->workflow_id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_WORKFLOW,
			],
		];
		
		return $properties;
	}
	
	function getMeta($context_id) {
		if(null == ($automation_event_listener = DAO_AutomationEventListener::get($context_id)))
			return [];
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($automation_event_listener->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return [
			'id' => $automation_event_listener->id,
			'name' => $automation_event_listener->name,
			'permalink' => $url,
			'updated' => $automation_event_listener->updated_at,
		];
	}
	
	function getDefaultProperties() {
		return [
			'event_name',
			'updated_at',
		];
	}
	
	function getContext($object, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Automation Event Listener:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_AUTOMATION_EVENT_LISTENER);
		
		// Polymorph
		if(is_numeric($object)) {
			$object = DAO_AutomationEventListener::get($object);
		} elseif($object instanceof Model_AutomationEventListener) {
			// It's what we want already.
			DevblocksPlatform::noop();
		} elseif(is_array($object)) {
			$object = Cerb_ORMHelper::recastArrayToModel($object, 'Model_AutomationEventListener');
		} else {
			$object = null;
		}
		
		// Token labels
		$token_labels = [
			'_label' => $prefix,
			'event_kata' => $prefix.$translate->_('Event KATA'),
			'event_name' => $prefix.$translate->_('common.automation.event'),
			'id' => $prefix.$translate->_('common.id'),
			'is_disabled' => $prefix.$translate->_('common.disabled'),
			'name' => $prefix.$translate->_('common.name'),
			'priority' => $prefix.$translate->_('common.priority'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'workflow_id' => $prefix.$translate->_('common.workflow.id'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		];
		
		// Token types
		$token_types = [
			'_label' => 'context_url',
			'event_kata' => Model_CustomField::TYPE_MULTI_LINE,
			'event_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'is_disabled' => Model_CustomField::TYPE_CHECKBOX,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'priority' => Model_CustomField::TYPE_NUMBER,
			'updated_at' => Model_CustomField::TYPE_DATE,
			'workflow_id' => Model_CustomField::TYPE_NUMBER,
			'record_url' => Model_CustomField::TYPE_URL,
		];
		
		// Custom field/fieldset token labels
		if(($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = [];
		
		$token_values['_context'] = CerberusContexts::CONTEXT_AUTOMATION_EVENT_LISTENER;
		$token_values['_type'] = 'automation_event_listener';
		$token_values['_types'] = $token_types;
		
		if($object) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $object->name;
			$token_values['event_kata'] = $object->event_kata;
			$token_values['event_name'] = $object->event_name;
			$token_values['id'] = $object->id;
			$token_values['is_disabled'] = $object->is_disabled;
			$token_values['name'] = $object->name;
			$token_values['priority'] = $object->priority;
			$token_values['updated_at'] = $object->updated_at;
			$token_values['workflow_id'] = $object->workflow_id;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($object, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=automation_event_listener&id=%d-%s",$object->id, DevblocksPlatform::strToPermalink($object->name)), true);
		}
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'event_kata' => DAO_AutomationEventListener::EVENT_KATA,
			'event_name' => DAO_AutomationEventListener::EVENT_NAME,
			'id' => DAO_AutomationEventListener::ID,
			'is_disabled' => DAO_AutomationEventListener::IS_DISABLED,
			'links' => '_links',
			'name' => DAO_AutomationEventListener::NAME,
			'priority' => DAO_AutomationEventListener::PRIORITY,
			'updated_at' => DAO_AutomationEventListener::UPDATED_AT,
			'workflow_id' => DAO_AutomationEventListener::WORKFLOW_ID,
		];
	}
	
	function getKeyMeta($with_dao_fields=true) {
		return parent::getKeyMeta($with_dao_fields);
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
		
		$context = CerberusContexts::CONTEXT_AUTOMATION_EVENT_LISTENER;
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
		$view->name = 'Automation Event Listener';
		/*
		$view->addParams([
			SearchFields_AutomationEventListener::UPDATED_AT => new DevblocksSearchCriteria(SearchFields_AutomationEventListener::UPDATED_AT,'=',0),
		], true);
		*/
		$view->renderSortBy = SearchFields_AutomationEventListener::UPDATED_AT;
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
		$view->name = 'Automation Event Listener';
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = [
				new DevblocksSearchCriteria(SearchFields_AutomationEventListener::VIRTUAL_CONTEXT_LINK,'in',[$context.':'.$context_id]),
			];
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = CerberusContexts::CONTEXT_AUTOMATION_EVENT_LISTENER;
		
		$tpl->assign('view_id', $view_id);
		
		$model = null;
		
		if($context_id) {
			if(!$model && !($model = DAO_AutomationEventListener::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 403);
		}
		
		if(empty($context_id) || $edit) {
			if(!$active_worker->is_superuser)
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			if($model) {
				if(!CerberusContexts::isWriteableByActor($context, $model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
				
			} else {
				$model = new Model_AutomationEventListener();
				$model->priority = 100;
				
				if(!empty($edit)) {
					$tokens = explode(' ', trim($edit));
					
					foreach ($tokens as $token) {
						list($k, $v) = array_pad(explode(':', $token, 2), 2, null);
						
						if (empty($k) || empty($v))
							continue;
						
						if($k == 'event') {
							$model->event_name = $v;
						}
					}
				}
			}
			
			$tpl->assign('model', $model);
			
			// Custom fields
			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);
			
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
			
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);
			
			// Editor toolbar
			
			$toolbar_dict = DevblocksDictionaryDelegate::instance([
				'caller_name' => 'cerb.eventHandler.automation',
				
				'worker__context' => CerberusContexts::CONTEXT_WORKER,
				'worker_id' => $active_worker->id
			]);
			
			$trigger_inputs = [];
			
			if(($trigger_ext = $model->getEventExtension())) {
				$tpl->assign('trigger_ext', $trigger_ext);
				
				$toolbar = $trigger_ext->getEventToolbar();
				$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar, $toolbar_dict);
				
				$tpl->assign('toolbar', $toolbar);
				
				$trigger_inputs = $trigger_ext->getEventPlaceholders();
			}
			
			$tpl->assign('trigger_inputs', $trigger_inputs);
			
			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::records/types/automation_event_listener/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}	
};
