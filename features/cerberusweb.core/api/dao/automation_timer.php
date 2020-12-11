<?php
// [TODO] Enabled/disabled
// [TODO] Last update message (from automation/await)
class DAO_AutomationTimer extends Cerb_ORMHelper {
	const AUTOMATIONS_KATA = 'automations_kata';
	const CONTINUATION_ID = 'continuation_id';
	const CREATED_AT = 'created_at';
	const ID = 'id';
	const NAME = 'name';
	const RESUME_AT = 'resume_at';
	const UPDATED_AT = 'updated_at';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::AUTOMATIONS_KATA)
			->string()
			->setMaxLength('16 bits')
		;
		$validation
			->addField(self::CONTINUATION_ID)
			->string()
		;
		$validation
			->addField(self::CREATED_AT)
			->timestamp()
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
			->addField(self::RESUME_AT)
			->timestamp()
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
		
		if(!array_key_exists(self::CREATED_AT, $fields))
			$fields[DAO_AutomationTimer::CREATED_AT] = time();
		
		$sql = "INSERT INTO automation_timer () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
		
		if(!array_key_exists(self::UPDATED_AT, $fields))
			$fields[self::UPDATED_AT] = time();
		
		$context = CerberusContexts::CONTEXT_AUTOMATION_TIMER;
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
			parent::_update($batch_ids, 'automation_timer', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.automation_timer.update',
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
		parent::_updateWhere('automation_timer', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_AUTOMATION_TIMER;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		return true;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_AutomationTimer[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();
		
		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, name, automations_kata, continuation_id, resume_at, created_at, updated_at ".
			"FROM automation_timer ".
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
	 *
	 * @param bool $nocache
	 * @return Model_AutomationTimer[]
	 */
	static function getAll($nocache=false) {
		//$cache = DevblocksPlatform::services()->cache();
		//if($nocache || null === ($objects = $cache->load(self::_CACHE_ALL))) {
		$objects = self::getWhere(null, DAO_AutomationTimer::NAME, true, null, Cerb_ORMHelper::OPT_GET_MASTER_ONLY);
		
		//if(!is_array($objects))
		//	return false;
		
		//$cache->save($objects, self::_CACHE_ALL);
		//}
		
		return $objects;
	}
	
	/**
	 * @param integer $id
	 * @return Model_AutomationTimer	 */
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
	
	/**
	 *
	 * @param array $ids
	 * @return Model_AutomationTimer[]
	 */
	static function getIds($ids) {
		return parent::getIds($ids);
	}
	
	/**
	 * @param resource $rs
	 * @return Model_AutomationTimer[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_AutomationTimer();
			$object->id = $row['id'];
			$object->name = $row['name'];
			$object->automations_kata = $row['automations_kata'];
			$object->continuation_id = $row['continuation_id'];
			$object->resume_at = $row['resume_at'];
			$object->created_at = $row['created_at'];
			$object->updated_at = $row['updated_at'];
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function random() {
		return self::_getRandom('automation_timer');
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM automation_timer WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_AUTOMATION_TIMER,
					'context_ids' => $ids
				)
			)
		);
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_AutomationTimer::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_AutomationTimer', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"automation_timer.id as %s, ".
			"automation_timer.name as %s, ".
			"automation_timer.resume_at as %s, ".
			"automation_timer.created_at as %s, ".
			"automation_timer.updated_at as %s ",
			SearchFields_AutomationTimer::ID,
			SearchFields_AutomationTimer::NAME,
			SearchFields_AutomationTimer::RESUME_AT,
			SearchFields_AutomationTimer::CREATED_AT,
			SearchFields_AutomationTimer::UPDATED_AT
		);
		
		$join_sql = "FROM automation_timer ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
		
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_AutomationTimer');
		
		return array(
			'primary_table' => 'automation_timer',
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
			SearchFields_AutomationTimer::ID,
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

class SearchFields_AutomationTimer extends DevblocksSearchFields {
	const ID = 'a_id';
	const NAME = 'a_name';
	const RESUME_AT = 'a_resume_at';
	const CREATED_AT = 'a_created_at';
	const UPDATED_AT = 'a_updated_at';
	
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_WATCHERS = '*_workers';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'automation_timer.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_AUTOMATION_TIMER => new DevblocksSearchFieldContextKeys('automation_timer.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_AUTOMATION_TIMER, self::getPrimaryKey());
			
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_AUTOMATION_TIMER)), self::getPrimaryKey());
			
			/*
			case self::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, '', self::getPrimaryKey());
				break;
			*/
			
			default:
				if('cf_' == substr($param->field, 0, 3)) {
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
			case SearchFields_AutomationTimer::ID:
				$models = DAO_AutomationTimer::getIds($values);
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
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 'automation_timer', 'created_at', $translate->_('common.created'), null, true),
			self::ID => new DevblocksSearchField(self::ID, 'automation_timer', 'id', $translate->_('common.id'), null, true),
			self::NAME => new DevblocksSearchField(self::NAME, 'automation_timer', 'name', $translate->_('common.name'), null, true),
			self::RESUME_AT => new DevblocksSearchField(self::RESUME_AT, 'automation_timer', 'resume_at', $translate->_('common.resume.at'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'automation_timer', 'updated_at', $translate->_('common.updated'), null, true),
			
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

class Model_AutomationTimer {
	public $automations_kata;
	public $continuation_id;
	public $created_at;
	public $id;
	public $name;
	public $resume_at;
	public $updated_at;
	
	/**
	 * @return DevblocksDictionaryDelegate|false
	 */
	public function run() {
		if($this->continuation_id) {
			$this->_continue();
		} else {
			$this->_start();
		}
	}
	
	// [TODO] Rather than deleting the timer, disable it in a completed status?
	
	private function _start() {
		$event_handler = DevblocksPlatform::services()->ui()->eventHandler();
		
		$error = null;
		$handler = null;
		
		try {
			$dict = DevblocksDictionaryDelegate::instance([]);
			
			$handlers = $event_handler->parse($this->automations_kata, $dict, $error);
			
			$dict = DevblocksDictionaryDelegate::getDictionaryFromModel($this, CerberusContexts::CONTEXT_AUTOMATION_TIMER);
			
			$initial_state = $dict->getDictionary(null, false, 'timer_');
			
			$automation_results = $event_handler->handleOnce(
				AutomationTrigger_AutomationTimer::ID,
				$handlers,
				$initial_state,
				$error,
				null,
				$handler
			);
			
			if(false == $automation_results)
				throw new Exception_DevblocksAutomationError();
				
			$exit_code = $automation_results->get('__exit');
			
			// Are we resuming later?
			if ('await' == $exit_code
				&& $handler instanceof Model_Automation
				&& null != ($resume_at = $automation_results->getKeyPath('__return.datetime'))
			) {
				
				$state_data = [
					'trigger' => AutomationTrigger_AutomationTimer::ID,
					'timer_id' => $this->id,
					'dict' => $automation_results->getDictionary(),
				];
				
				// Create a continuation?
				$continuation_id = DAO_AutomationExecution::create([
					DAO_AutomationExecution::UPDATED_AT => time(),
					DAO_AutomationExecution::EXPIRES_AT => $resume_at + 86400,
					DAO_AutomationExecution::STATE => $exit_code,
					DAO_AutomationExecution::STATE_DATA => json_encode($state_data),
					DAO_AutomationExecution::URI => $handler->name,
				]);
				
				// Update record
				DAO_AutomationTimer::update($this->id, [
					DAO_AutomationTimer::CONTINUATION_ID => $continuation_id,
					DAO_AutomationTimer::RESUME_AT => intval($resume_at),
				]);
				
				return $automation_results;
				
			// We're not resuming; we're done
			} else {
				//if('error' == $exit_code) {
				// [TODO] Log automation errors
				//}
			}
			
			DAO_AutomationTimer::delete($this->id);
			
		} catch(Exception_DevblocksAutomationError $e) {
				DAO_AutomationTimer::delete($this->id);
		}
		
		return $automation_results;
	}
	
	private function _continue() {
		$automator = DevblocksPlatform::services()->automation();
		
		$error = null;
		
		try {
			if(false == ($continuation = DAO_AutomationExecution::getByToken($this->continuation_id)))
				throw new Exception_DevblocksAutomationError();
			
			if($continuation->state != 'await')
				throw new Exception_DevblocksAutomationError();
				
			// Verify this timer owns the continuation
			if($continuation->state_data['timer_id'] != $this->id)
				throw new Exception_DevblocksAutomationError();
			
			// Verify the await timestamp has elapsed
			$resume_at = $continuation->state_data['dict']['__return']['datetime'] ?? 0;
			
			// Has the expected time not elapsed yet?
			if($resume_at > time()) {
				// Reschedule
				DAO_AutomationTimer::update($this->id, [
					DAO_AutomationTimer::RESUME_AT => $resume_at,
					DAO_AutomationTimer::UPDATED_AT => time(),
				]);
				return false;
			}
			
			// Load the continuation automation
			if(false == ($automation = DAO_Automation::getByUri($continuation->uri)))
				throw new Exception_DevblocksAutomationError();
			
			if($automation->extension_id != AutomationTrigger_AutomationTimer::ID)
				throw new Exception_DevblocksAutomationError();
			
			$automation_results = $automator->executeScript(
				$automation,
				$continuation->state_data['dict'],
				$error
			);
			
			if(false == $automation_results)
				throw new Exception_DevblocksAutomationError();
			
			$exit_code = $automation_results->get('__exit');
			
			// Error?
			//if('error' == $exit_code) {
				// [TODO]
			//}
			
			// Are we resuming again?
			if('await' == $exit_code 
				&& null != ($resume_at = $automation_results->getKeyPath('__return.datetime'))
			) {
				$continuation->state_data['dict'] = $automation_results->getDictionary();
				
				DAO_AutomationExecution::update($continuation->token, [
					DAO_AutomationExecution::STATE => $exit_code,
					DAO_AutomationExecution::STATE_DATA => json_encode($continuation->state_data),
					DAO_AutomationExecution::UPDATED_AT => time(),
					DAO_AutomationExecution::EXPIRES_AT => $resume_at + 86400,
				]);
				
				DAO_AutomationTimer::update($this->id, [
					DAO_AutomationTimer::RESUME_AT => intval($resume_at),
					DAO_AutomationTimer::UPDATED_AT => time(),
				]);
				
				return $automation_results;
			}
			
			// Cleanup
			DAO_AutomationExecution::delete($continuation->id);
			DAO_AutomationTimer::delete($this->id);
			
			return $automation_results;
			
		} catch (Exception_DevblocksAutomationError $e) {
			DAO_AutomationTimer::delete($this->id);
			return false;
		}
	}
};

class View_AutomationTimer extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'automation_timers';
	
	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('common.automation.timers');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_AutomationTimer::RESUME_AT;
		$this->renderSortAsc = true;
		
		$this->view_columns = array(
			SearchFields_AutomationTimer::NAME,
			SearchFields_AutomationTimer::RESUME_AT,
			SearchFields_AutomationTimer::UPDATED_AT,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_AutomationTimer::VIRTUAL_CONTEXT_LINK,
			SearchFields_AutomationTimer::VIRTUAL_HAS_FIELDSET,
			SearchFields_AutomationTimer::VIRTUAL_WATCHERS,
		));
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_AutomationTimer::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_AutomationTimer');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_AutomationTimer', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_AutomationTimer', $size);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];
		
		if(is_array($all_fields))
			foreach($all_fields as $field_key => $field_model) {
				$pass = false;
				
				switch($field_key) {
					// Fields
//				case SearchFields_AutomationTimer::EXAMPLE:
//					$pass = true;
//					break;
					
					// Virtuals
					case SearchFields_AutomationTimer::VIRTUAL_CONTEXT_LINK:
					case SearchFields_AutomationTimer::VIRTUAL_HAS_FIELDSET:
					case SearchFields_AutomationTimer::VIRTUAL_WATCHERS:
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
		$context = CerberusContexts::CONTEXT_AUTOMATION_TIMER;
		
		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
//			case SearchFields_AutomationTimer::EXAMPLE_BOOL:
//				$counts = $this->_getSubtotalCountForBooleanColumn($context, $column);
//				break;

//			case SearchFields_AutomationTimer::EXAMPLE_STRING:
//				$counts = $this->_getSubtotalCountForStringColumn($context, $column);
//				break;
			
			case SearchFields_AutomationTimer::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;
			
			case SearchFields_AutomationTimer::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
			
			case SearchFields_AutomationTimer::VIRTUAL_WATCHERS:
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
		$search_fields = SearchFields_AutomationTimer::getFields();
		
		$fields = array(
			'text' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_AutomationTimer::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'created' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_AutomationTimer::CREATED_AT),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_AutomationTimer::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_AUTOMATION_TIMER],
					]
				),
			'id' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_AutomationTimer::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_AUTOMATION_TIMER, 'q' => ''],
					]
				),
			'name' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_AutomationTimer::NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'resumeAt' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_AutomationTimer::RESUME_AT),
				),
			'updated' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_AutomationTimer::UPDATED_AT),
				),
			'watchers' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_WORKER,
					'options' => array('param_key' => SearchFields_AutomationTimer::VIRTUAL_WATCHERS),
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_AutomationTimer::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_AUTOMATION_TIMER, $fields, null);
		
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
		
		return false;
	}
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);
		
		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_AUTOMATION_TIMER);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->assign('view_template', 'devblocks:cerberusweb.core::records/types/automation_timer/view.tpl');
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
			case SearchFields_AutomationTimer::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
			
			case SearchFields_AutomationTimer::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
			
			case SearchFields_AutomationTimer::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}
	
	function getFields() {
		return SearchFields_AutomationTimer::getFields();
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;
		
		switch($field) {
			case SearchFields_AutomationTimer::NAME:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
			
			case SearchFields_AutomationTimer::ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
			
			case SearchFields_AutomationTimer::RESUME_AT:
			case SearchFields_AutomationTimer::CREATED_AT:
			case SearchFields_AutomationTimer::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
			
			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_POST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
			
			case SearchFields_AutomationTimer::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_POST['context_link'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
			
			case SearchFields_AutomationTimer::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_POST['options'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
			
			case SearchFields_AutomationTimer::VIRTUAL_WATCHERS:
				@$worker_ids = DevblocksPlatform::importGPC($_POST['worker_id'],'array',[]);
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

class Context_AutomationTimer extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	const ID = CerberusContexts::CONTEXT_AUTOMATION_TIMER;
	
	static function isReadableByActor($models, $actor) {
		// Everyone can read
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Only admin workers can modify
		
		if(false == ($actor = CerberusContexts::polymorphActorToDictionary($actor)))
			CerberusContexts::denyEverything($models);
		
		if(CerberusContexts::isActorAnAdmin($actor))
			return CerberusContexts::allowEverything($models);
		
		return CerberusContexts::denyEverything($models);
	}
	
	static function isDeletableByActor($models, $actor) {
		return self::isWriteableByActor($models, $actor);
	}
	
	function getRandom() {
		return DAO_AutomationTimer::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
		
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=automation_timer&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_AutomationTimer();
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		);
		
		$properties['created'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.created'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->created_at,
		);
		
		$properties['resume_at'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.resume_at'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->resume_at,
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		);
		
		$properties['id'] = array(
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		$automation_timer = DAO_AutomationTimer::get($context_id);
		
		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($automation_timer->name);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $automation_timer->id,
			'name' => $automation_timer->name,
			'permalink' => $url,
			'updated' => $automation_timer->updated_at,
		);
	}
	
	function getDefaultProperties() {
		return array(
			'updated_at',
		);
	}
	
	function getContext($automation_timer, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Automation Timer:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_AUTOMATION_TIMER);
		
		// Polymorph
		if(is_numeric($automation_timer)) {
			$automation_timer = DAO_AutomationTimer::get($automation_timer);
		} elseif($automation_timer instanceof Model_AutomationTimer) {
			// It's what we want already.
		} elseif(is_array($automation_timer)) {
			$automation_timer = Cerb_ORMHelper::recastArrayToModel($automation_timer, 'Model_AutomationTimer');
		} else {
			$automation_timer = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'created_at' => $prefix.$translate->_('common.created'),
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'resume_at' => $prefix.$translate->_('common.resume_at'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'created_at' => Model_CustomField::TYPE_DATE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'resume_at' => Model_CustomField::TYPE_DATE,
			'updated_at' => Model_CustomField::TYPE_DATE,
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
		
		$token_values['_context'] = CerberusContexts::CONTEXT_AUTOMATION_TIMER;
		$token_values['_types'] = $token_types;
		
		if($automation_timer) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $automation_timer->name;
			$token_values['created_at'] = $automation_timer->created_at;
			$token_values['id'] = $automation_timer->id;
			$token_values['name'] = $automation_timer->name;
			$token_values['resume_at'] = $automation_timer->resume_at;
			$token_values['updated_at'] = $automation_timer->updated_at;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($automation_timer, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=automation_timer&id=%d-%s",$automation_timer->id, DevblocksPlatform::strToPermalink($automation_timer->name)), true);
		}
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'id' => DAO_AutomationTimer::ID,
			'links' => '_links',
			'automations_kata' => DAO_AutomationTimer::AUTOMATIONS_KATA,
			'created_at' => DAO_AutomationTimer::CREATED_AT,
			'name' => DAO_AutomationTimer::NAME,
			'resume_at' => DAO_AutomationTimer::RESUME_AT,
			'updated_at' => DAO_AutomationTimer::UPDATED_AT,
		];
	}
	
	function getKeyMeta($with_dao_fields=true) {
		$keys = parent::getKeyMeta($with_dao_fields);
		return $keys;
	}
	
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
			return;
		
		$context = CerberusContexts::CONTEXT_AUTOMATION_TIMER;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = [];
		
		if(!$is_loaded) {
			$labels = [];
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		}
		
		switch($token) {
			default:
				$defaults = $this->_lazyLoadDefaults($token, $context, $context_id);
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
		$view->name = DevblocksPlatform::translateCapitalized('common.automation.timers');
		$view->renderSortBy = SearchFields_AutomationTimer::UPDATED_AT;
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
		$view->name = DevblocksPlatform::translateCapitalized('common.automation.timers');
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_AutomationTimer::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = CerberusContexts::CONTEXT_AUTOMATION_TIMER;
		
		$tpl->assign('view_id', $view_id);
		
		$model = null;
		
		if($context_id) {
			if(false == ($model = DAO_AutomationTimer::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 403);
		}
		
		if(empty($context_id) || $edit) {
			if($model) {
				if(!CerberusContexts::isWriteableByActor($context, $model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
				
				$tpl->assign('model', $model);
			}
			
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
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::records/types/automation_timer/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};

