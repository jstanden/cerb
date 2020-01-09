<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/

class DAO_ContextScheduledBehavior extends Cerb_ORMHelper {
	const BEHAVIOR_ID = 'behavior_id';
	const CONTEXT = 'context';
	const CONTEXT_ID = 'context_id';
	const ID = 'id';
	const REPEAT_JSON = 'repeat_json';
	const RUN_DATE = 'run_date';
	const RUN_LITERAL = 'run_literal';
	const RUN_RELATIVE = 'run_relative';
	const VARIABLES_JSON = 'variables_json';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::BEHAVIOR_ID)
			->id()
			->setRequired(true)
			;
		$validation
			->addField(self::CONTEXT, 'target__context')
			->context()
			->setRequired(true)
			;
		$validation
			->addField(self::CONTEXT_ID, 'target_id')
			->id()
			->setRequired(true)
			;
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		$validation
			->addField(self::REPEAT_JSON)
			->string()
			->setMaxLength(16777215)
			;
		$validation
			->addField(self::RUN_DATE)
			->timestamp()
			->setRequired(true)
			;
		$validation
			->addField(self::RUN_LITERAL)
			->string()
			;
		$validation
			->addField(self::RUN_RELATIVE)
			->string()
			;
		$validation
			->addField(self::VARIABLES_JSON)
			->string()
			->setMaxLength(16777215)
			;
			
		return $validation->getFields();
	}

	static function create($fields) {
		$db = DevblocksPlatform::services()->database();

		$sql = "INSERT INTO context_scheduled_behavior () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();

		self::update($id, $fields);

		return $id;
	}

	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
			
		$context = CerberusContexts::CONTEXT_BEHAVIOR_SCHEDULED;
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
			parent::_update($batch_ids, 'context_scheduled_behavior', $fields);
			
			// Send events
			if($check_deltas) {
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.context_scheduled_behavior.update',
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
		parent::_updateWhere('context_scheduled_behavior', $fields, $where);
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_BEHAVIOR_SCHEDULED;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		// Verify the behavior_id and context are in agreement
		
		@$behavior_id = $fields['behavior_id'];
		@$context = $fields['context'];
		
		if($behavior_id && $context) {
			if(false == ($behavior = DAO_TriggerEvent::get($behavior_id))) { /* @var $behavior Model_TriggerEvent */
				$error = sprintf("The given `behavior_id` (#%d) does not exist.", $behavior_id);
				return false;
			}
			
			// Verify the behavior is a macro
			
			$event = $behavior->getEvent();
			
			@$macro_context = $event->manifest->params['macro_context'];
			
			if(!$macro_context || 0 != strcasecmp($macro_context, $context)) {
				$error = sprintf("The given `behavior_id` is not a macro for `%s`.", $context);
				return false;
			}
		}
		
		return true;
	}
	
	static function updateRelativeSchedules($context, $context_ids) {
		$db = DevblocksPlatform::services()->database();
		
		if(empty($context_ids))
			return;
		
		$sql = sprintf("%s = %s AND %s IN (%s) AND %s != ''",
			self::CONTEXT,
			$db->qstr($context),
			self::CONTEXT_ID,
			implode(',', $context_ids),
			self::RUN_RELATIVE
		);
		
		$objects = DAO_ContextScheduledBehavior::getWhere($sql);

		if(is_array($objects))
		foreach($objects as $object) { /* @var $object Model_ContextScheduledBehavior */
			if(null == ($macro = DAO_TriggerEvent::get($object->behavior_id)))
				continue;
			
			if(null == ($event = $macro->getEvent()))
				continue;
			
			$event = $macro->getEvent();
			$event_model = $event->generateSampleEventModel($macro, $object->context_id);
			$event->setEvent($event_model, $macro);
			$values = $event->getValues();
			
			$tpl_builder = DevblocksPlatform::services()->templateBuilder();
			@$run_relative_timestamp = strtotime($tpl_builder->build(sprintf("{{%s|date}}",$object->run_relative), $values));
			
			if(empty($run_relative_timestamp))
				$run_relative_timestamp = time();
			
			$run_date = @strtotime($object->run_literal, $run_relative_timestamp);
			
			DAO_ContextScheduledBehavior::update($object->id, array(
				DAO_ContextScheduledBehavior::RUN_DATE => $run_date,
			));
		}
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_ContextScheduledBehavior[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);

		// SQL
		$sql = "SELECT id, context, context_id, behavior_id, run_date, run_relative, run_literal, variables_json, repeat_json ".
			"FROM context_scheduled_behavior ".
			$where_sql.
			$sort_sql.
			$limit_sql
			;
		
		if($options & Cerb_ORMHelper::OPT_GET_MASTER_ONLY) {
			$rs = $db->ExecuteMaster($sql, _DevblocksDatabaseManager::OPT_NO_READ_AFTER_WRITE);
		} else {
			$rs = $db->ExecuteSlave($sql);
		}
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_ContextScheduledBehavior
	 */
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
	 * @return Model_ContextScheduledBehavior[]
	 */
	static function getIds($ids) {
		return parent::getIds($ids);
	}
	
	/**
	 *
	 * Enter description here ...
	 * @param string $context
	 * @param integer $context_id
	 * @return Model_ContextScheduledBehavior
	 */
	static public function getByContext($context, $context_id) {
		$objects = self::getWhere(
			sprintf("%s = %s AND %s = %d",
				self::CONTEXT,
				Cerb_ORMHelper::qstr($context),
				self::CONTEXT_ID,
				$context_id
			),
			self::RUN_DATE,
			true
		);

		return $objects;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_ContextScheduledBehavior[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;

		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_ContextScheduledBehavior();
			$object->id = $row['id'];
			$object->context = $row['context'];
			$object->context_id = $row['context_id'];
			$object->behavior_id = $row['behavior_id'];
			$object->run_date = $row['run_date'];
			$object->run_relative = $row['run_relative'];
			$object->run_literal = $row['run_literal'];
			if(!empty($row['variables_json']))
				$object->variables = @json_decode($row['variables_json'], true);
			if(!empty($row['repeat_json']))
				$object->repeat = @json_decode($row['repeat_json'], true);
			$objects[$object->id] = $object;
		}

		mysqli_free_result($rs);

		return $objects;
	}
	
	static function random() {
		return self::_getRandom('context_scheduled_behavior');
	}

	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();

		if(empty($ids))
			return;

		$ids_list = implode(',', $ids);

		$db->ExecuteMaster(sprintf("DELETE FROM context_scheduled_behavior WHERE id IN (%s)", $ids_list));

		return true;
	}
	
	static function deleteByContext($context, $context_ids) {
		if(!is_array($context_ids))
			$context_ids = array($context_ids);
		
		if(empty($context_ids))
			return;
		
		$context_ids = DevblocksPlatform::sanitizeArray($context_ids, 'int');
			
		$db = DevblocksPlatform::services()->database();
		
		$db->ExecuteMaster(sprintf("DELETE FROM context_scheduled_behavior WHERE context = %s AND context_id IN (%s) ",
			$db->qstr($context),
			implode(',', $context_ids)
		));
		
		return true;
	}
	
	static function deleteByBehavior($behavior_ids, $only_context=null, $only_context_id=null) {
		if(!is_array($behavior_ids)) $behavior_ids = array($behavior_ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($behavior_ids))
			return;

		DevblocksPlatform::sanitizeArray($behavior_ids, 'integer');
		$ids_list = implode(',', $behavior_ids);
		
		$wheres = array();
		
		$wheres[] = sprintf("behavior_id IN (%s)",
			$ids_list
		);
		
		// Are we limiting this delete to a single context or object?
		if(!empty($only_context)) {
			$wheres[] = sprintf("context = %s",
				$db->qstr($only_context)
			);
			
			if(!empty($only_context_id)) {
				$wheres[] = sprintf("context_id = %d",
					$only_context_id
				);
			}
		}
		
		// Join where clauses
		$where = implode(' AND ', $wheres);
		
		// Query
		$db->ExecuteMaster(sprintf("DELETE FROM context_scheduled_behavior WHERE %s", $where));
		
		return true;
	}

	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_ContextScheduledBehavior::getFields();

		list(, $wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_ContextScheduledBehavior', $sortBy);

		$select_sql = sprintf("SELECT ".
			"context_scheduled_behavior.id as %s, ".
			"context_scheduled_behavior.context as %s, ".
			"context_scheduled_behavior.context_id as %s, ".
			"context_scheduled_behavior.behavior_id as %s, ".
			"context_scheduled_behavior.run_date as %s, ".
			"context_scheduled_behavior.run_relative as %s, ".
			"context_scheduled_behavior.run_literal as %s, ".
			"context_scheduled_behavior.variables_json as %s, ".
			"context_scheduled_behavior.repeat_json as %s, ".
			"trigger_event.title as %s, ".
			"trigger_event.bot_id as %s ",
				SearchFields_ContextScheduledBehavior::ID,
				SearchFields_ContextScheduledBehavior::CONTEXT,
				SearchFields_ContextScheduledBehavior::CONTEXT_ID,
				SearchFields_ContextScheduledBehavior::BEHAVIOR_ID,
				SearchFields_ContextScheduledBehavior::RUN_DATE,
				SearchFields_ContextScheduledBehavior::RUN_RELATIVE,
				SearchFields_ContextScheduledBehavior::RUN_LITERAL,
				SearchFields_ContextScheduledBehavior::VARIABLES_JSON,
				SearchFields_ContextScheduledBehavior::REPEAT_JSON,
				SearchFields_ContextScheduledBehavior::BEHAVIOR_NAME,
				SearchFields_ContextScheduledBehavior::BEHAVIOR_BOT_ID
		);
			
		$join_sql = "FROM context_scheduled_behavior ".
			"INNER JOIN trigger_event ON (context_scheduled_behavior.behavior_id=trigger_event.id) "
			;

		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_ContextScheduledBehavior');

		return array(
			'primary_table' => 'context_scheduled_behavior',
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
	 */
	static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::services()->database();

		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$sort_sql = $query_parts['sort'];

		$sql =
			$select_sql.
			$join_sql.
			$where_sql.
			$sort_sql
			;
			
		if($limit > 0) {
			if(false == ($rs = $db->SelectLimit($sql,$limit,$page*$limit)))
				return false;
		} else {
			if(false == ($rs = $db->ExecuteSlave($sql)))
				return false;
			$total = mysqli_num_rows($rs);
		}

		$results = [];
		
		if(!($rs instanceof mysqli_result))
			return false;

		while($row = mysqli_fetch_assoc($rs)) {
			$object_id = intval($row[SearchFields_ContextScheduledBehavior::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(context_scheduled_behavior.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}

		mysqli_free_result($rs);

		return array($results,$total);
	}

	static function buildVariables($var_keys, $var_vals, $trigger) {
		$vars = [];
		
		if(!is_array($var_keys) || !is_array($var_vals))
			return [];
		
		if(empty($var_keys) || empty($var_vals))
			return [];
		
		foreach($var_keys as $var) {
			if(isset($var_vals[$var])) {
				@$var_mft = $trigger->variables[$var];
				$val = $var_vals[$var];
				
				if(!empty($var_mft)) {
					// Parse dates
					switch($var_mft['type']) {
						case Model_CustomField::TYPE_DATE:
							@$val = strtotime($val);
							break;
					}
				}
				
				$vars[$var] = $val;
			}
		}
		return $vars;
	}
};

class SearchFields_ContextScheduledBehavior extends DevblocksSearchFields {
	const BEHAVIOR_ID = 'c_behavior_id';
	const CONTEXT = 'c_context';
	const CONTEXT_ID = 'c_context_id';
	const ID = 'c_id';
	const REPEAT_JSON = 'c_repeat_json';
	const RUN_DATE = 'c_run_date';
	const RUN_LITERAL = 'c_run_literal';
	const RUN_RELATIVE = 'c_run_relative';
	const VARIABLES_JSON = 'c_variables_json';
	
	const BEHAVIOR_NAME = 'b_behavior_name';
	const BEHAVIOR_BOT_ID = 'b_behavior_bot_id';
	
	const VIRTUAL_BEHAVIOR_SEARCH = '*_behavior_search';
	const VIRTUAL_BOT_SEARCH = '*_bot_search';
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_TARGET = '*_target';

	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'context_scheduled_behavior.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_BEHAVIOR_SCHEDULED => new DevblocksSearchFieldContextKeys('context_scheduled_behavior.id', self::ID),
			CerberusContexts::CONTEXT_BEHAVIOR => new DevblocksSearchFieldContextKeys('trigger_event.bot_id', self::BEHAVIOR_ID),
			CerberusContexts::CONTEXT_BOT => new DevblocksSearchFieldContextKeys('trigger_event.bot_id', self::BEHAVIOR_BOT_ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_BEHAVIOR_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_BEHAVIOR, 'context_scheduled_behavior.behavior_id');
				break;
				
			case self::VIRTUAL_BOT_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_BOT, 'trigger_event.bot_id');
				break;
				
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_BEHAVIOR_SCHEDULED, self::getPrimaryKey());
				break;
			
			case self::VIRTUAL_TARGET:
				return self::_getWhereSQLFromContextAndID($param, 'context_scheduled_behavior.context', 'context_scheduled_behavior.context_id');
				break;
				
			default:
				if('cf_' == substr($param->field, 0, 3)) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
				break;
		}
	}
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		switch($key) {
			case 'behavior':
				$key = 'behavior.id';
				break;
				
			case 'on':
				$field_target_context = $search_fields[SearchFields_ContextScheduledBehavior::CONTEXT];
				$field_target_context_id = $search_fields[SearchFields_ContextScheduledBehavior::CONTEXT_ID];
				
				return [
					'key_query' => $key,
					'key_select' => 'on',
					'type' => DevblocksSearchCriteria::TYPE_CONTEXT,
					'sql_select' => sprintf("CONCAT_WS(':', %s.%s, %s.%s)",
						Cerb_ORMHelper::escape($field_target_context->db_table),
						Cerb_ORMHelper::escape($field_target_context->db_column),
						Cerb_ORMHelper::escape($field_target_context_id->db_table),
						Cerb_ORMHelper::escape($field_target_context_id->db_column)
					),
					'get_value_as_filter_callback' => parent::getValueAsFilterCallback()->link('on'),
				];
				break;
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_ContextScheduledBehavior::BEHAVIOR_ID:
				$models = DAO_TriggerEvent::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'title', 'id');
				break;
				
			case SearchFields_ContextScheduledBehavior::ID:
				$models = DAO_ContextScheduledBehavior::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				break;
				
			case 'on':
				return parent::_getLabelsForKeyContextAndIdValues($values);
				break;
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
			self::BEHAVIOR_ID => new DevblocksSearchField(self::BEHAVIOR_ID, 'context_scheduled_behavior', 'behavior_id', $translate->_('common.behavior'), null, true),
			self::CONTEXT => new DevblocksSearchField(self::CONTEXT, 'context_scheduled_behavior', 'context', $translate->_('common.record.type'), null, true),
			self::CONTEXT_ID => new DevblocksSearchField(self::CONTEXT_ID, 'context_scheduled_behavior', 'context_id', $translate->_('common.record.id'), null, true),
			self::ID => new DevblocksSearchField(self::ID, 'context_scheduled_behavior', 'id', $translate->_('common.id'), Model_CustomField::TYPE_NUMBER, true),
			self::REPEAT_JSON => new DevblocksSearchField(self::REPEAT_JSON, 'context_scheduled_behavior', 'repeat_json', $translate->_('dao.context_scheduled_behavior.repeat_json'), null, false),
			self::RUN_DATE => new DevblocksSearchField(self::RUN_DATE, 'context_scheduled_behavior', 'run_date', $translate->_('dao.context_scheduled_behavior.run_date'), Model_CustomField::TYPE_DATE, true),
			self::RUN_LITERAL => new DevblocksSearchField(self::RUN_LITERAL, 'context_scheduled_behavior', 'run_literal', $translate->_('dao.context_scheduled_behavior.run_literal'), null, false),
			self::RUN_RELATIVE => new DevblocksSearchField(self::RUN_RELATIVE, 'context_scheduled_behavior', 'run_relative', $translate->_('dao.context_scheduled_behavior.run_relative'), null, false),
			self::VARIABLES_JSON => new DevblocksSearchField(self::VARIABLES_JSON, 'context_scheduled_behavior', 'variables_json', $translate->_('dao.context_scheduled_behavior.variables_json'), null, false),
			
			self::BEHAVIOR_NAME => new DevblocksSearchField(self::BEHAVIOR_NAME, 'trigger_event', 'title', $translate->_('common.name'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::BEHAVIOR_BOT_ID => new DevblocksSearchField(self::BEHAVIOR_BOT_ID, 'trigger_event', 'bot_id', $translate->_('common.bot'), null, true),
			
			self::VIRTUAL_BEHAVIOR_SEARCH => new DevblocksSearchField(self::VIRTUAL_BEHAVIOR_SEARCH, '*', 'behavior_search', null, null, false),
			self::VIRTUAL_BOT_SEARCH => new DevblocksSearchField(self::VIRTUAL_BOT_SEARCH, '*', 'bot_search', null, null, false),
			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_TARGET => new DevblocksSearchField(self::VIRTUAL_TARGET, '*', 'target', $translate->_('common.on'), null, false),
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

class Model_ContextScheduledBehavior {
	public $behavior_id;
	public $context;
	public $context_id;
	public $id;
	public $repeat = [];
	public $run_date;
	public $run_literal;
	public $run_relative;
	public $variables = [];
	
	function getBehavior() {
		return DAO_TriggerEvent::get($this->behavior_id);
	}
	
	function getRecordDictionary() {
		$labels = $values = [];
		CerberusContexts::getContext($this->context, $this->context_id, $labels, $values, '', true, true);
		return DevblocksDictionaryDelegate::instance($values);
	}
	
	function run() {
		try {
			if(empty($this->context) || empty($this->context_id) || empty($this->behavior_id))
				throw new Exception("Missing properties.");
	
			// Load macro
			if(null == ($macro = DAO_TriggerEvent::get($this->behavior_id))) /* @var $macro Model_TriggerEvent */
				throw new Exception("Invalid macro.");
			
			// Load event manifest
			if(null == ($ext = Extension_DevblocksEvent::get($macro->event_point, false))) /* @var $ext DevblocksExtensionManifest */
				throw new Exception("Invalid event.");
			
		} catch(Exception $e) {
			DAO_ContextScheduledBehavior::delete($this->id);
			return;
		}
		
		// Format variables
		
		foreach($this->variables as $var_key => $var_val) {
			if(!isset($macro->variables[$var_key]))
				continue;
			
			try {
				$this->variables[$var_key] = $macro->formatVariable($macro->variables[$var_key], $var_val);
				
			} catch(Exception $e) {
			}
		}
		
		// Are we going to be rescheduling this behavior?
		$reschedule_date = $this->getNextOccurrence();
	
		if(!empty($reschedule_date)) {
			DAO_ContextScheduledBehavior::update($this->id, array(
				DAO_ContextScheduledBehavior::RUN_DATE => $reschedule_date,
			));
			
		} else {
			DAO_ContextScheduledBehavior::delete($this->id);
		}
		
		if(!method_exists($ext->class, 'trigger'))
			return;
		
		// Execute
		call_user_func([$ext->class, 'trigger'], $macro->id, $this->context_id, $this->variables);
	}
	
	function getNextOccurrence() {
		if(empty($this->repeat) || !isset($this->repeat['freq']))
			return null;
		
		// Do we have end conditions?
		if(isset($this->repeat['end'])) {
			$end = $this->repeat['end'];
			switch($end['term']) {
				// End after a specific date
				case 'date':
					// If we've passed the end date
					$on = intval(@$end['options']['on']);
					if($on <= time()) {
						// Don't repeat
						return null;
					}
					break;
			}
		}
		
		$next_run_date = null;
		$dates = array();
		
		switch($this->repeat['freq']) {
			case 'interval':
				$now = ($this->run_date <= time()) ? time() : $this->run_date;
				@$next = strtotime($this->repeat['options']['every_n'], $now);
				
				if(!empty($next))
					$next_run_date = $next;
				
				break;
				
			case 'weekly':
				$days = isset($this->repeat['options']['day']) ? $this->repeat['options']['day'] : array();
				$dates = DevblocksCalendarHelper::getWeeklyDates($this->run_date, $days, null, 1);
				break;
				
			case 'monthly':
				$days = isset($this->repeat['options']['day']) ? $this->repeat['options']['day'] : array();
				$dates = DevblocksCalendarHelper::getMonthlyDates($this->run_date, $days, null, 2);
				break;
				
			case 'yearly':
				$months = isset($this->repeat['options']['month']) ? $this->repeat['options']['month'] : array();
				$dates = DevblocksCalendarHelper::getYearlyDates($this->run_date, $months, null, 2);
				break;
		}
		
		if(!empty($dates)) {
			$next_run_date = array_shift($dates);
			$next_run_date = strtotime(date('H:i', $this->run_date), $next_run_date);
		}

		if(empty($next_run_date))
			return false;
		
		return $next_run_date;
	}
};

class View_ContextScheduledBehavior extends C4_AbstractView implements IAbstractView_QuickSearch, IAbstractView_Subtotals {
	const DEFAULT_ID = 'contextscheduledbehavior';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();

		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('Scheduled Behaviors');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_ContextScheduledBehavior::RUN_DATE;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_ContextScheduledBehavior::RUN_DATE,
			SearchFields_ContextScheduledBehavior::BEHAVIOR_BOT_ID,
			SearchFields_ContextScheduledBehavior::BEHAVIOR_NAME,
			SearchFields_ContextScheduledBehavior::VIRTUAL_TARGET,
		);
		$this->addColumnsHidden(array(
			SearchFields_ContextScheduledBehavior::BEHAVIOR_ID,
			SearchFields_ContextScheduledBehavior::CONTEXT,
			SearchFields_ContextScheduledBehavior::CONTEXT_ID,
			SearchFields_ContextScheduledBehavior::RUN_LITERAL,
			SearchFields_ContextScheduledBehavior::RUN_RELATIVE,
			SearchFields_ContextScheduledBehavior::VARIABLES_JSON,
			SearchFields_ContextScheduledBehavior::VIRTUAL_BEHAVIOR_SEARCH,
			SearchFields_ContextScheduledBehavior::VIRTUAL_BOT_SEARCH,
			SearchFields_ContextScheduledBehavior::VIRTUAL_CONTEXT_LINK,
		));

		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_ContextScheduledBehavior::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_ContextScheduledBehavior');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_ContextScheduledBehavior', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_ContextScheduledBehavior', $size);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
				case SearchFields_ContextScheduledBehavior::BEHAVIOR_ID:
					$pass = true;
					break;
					
				// Virtuals
				case SearchFields_ContextScheduledBehavior::VIRTUAL_CONTEXT_LINK:
				case SearchFields_ContextScheduledBehavior::VIRTUAL_TARGET:
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
		$context = CerberusContexts::CONTEXT_BEHAVIOR_SCHEDULED;

		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_ContextScheduledBehavior::BEHAVIOR_ID:
				$label_map = function(array $ids) use ($column) {
					$labels = SearchFields_ContextScheduledBehavior::getLabelsForKeyValues($column, $ids);
					return $labels;
				};
				$counts = $this->_getSubtotalCountForNumberColumn($context, $column, $label_map);
				break;

			case SearchFields_ContextScheduledBehavior::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;

			case SearchFields_ContextScheduledBehavior::VIRTUAL_TARGET:
				$counts = $this->_getSubtotalCountForContextAndIdColumns($context, $column, DAO_ContextScheduledBehavior::CONTEXT, DAO_ContextScheduledBehavior::CONTEXT_ID);
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
		$search_fields = SearchFields_ContextScheduledBehavior::getFields();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_ContextScheduledBehavior::BEHAVIOR_NAME, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'behavior' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_ContextScheduledBehavior::VIRTUAL_BEHAVIOR_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_BEHAVIOR, 'q' => ''],
					]
				),
			'behavior.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_ContextScheduledBehavior::BEHAVIOR_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_BEHAVIOR, 'q' => ''],
					]
				),
			'bot' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_ContextScheduledBehavior::VIRTUAL_BOT_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_BOT, 'q' => ''],
					]
				),
			'bot.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_ContextScheduledBehavior::BEHAVIOR_BOT_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_BOT, 'q' => ''],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_ContextScheduledBehavior::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_BEHAVIOR_SCHEDULED, 'q' => ''],
					]
				),
			'runDate' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_ContextScheduledBehavior::RUN_DATE),
				),
		);
		
		// On:
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('on', $fields, 'search', SearchFields_ContextScheduledBehavior::VIRTUAL_TARGET);
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		
		ksort($fields);
		
		return $fields;
	}	
	
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'behavior':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_ContextScheduledBehavior::VIRTUAL_BEHAVIOR_SEARCH);
				break;
				
			case 'bot':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_ContextScheduledBehavior::VIRTUAL_BOT_SEARCH);
				break;
				
			default:
				if($field == 'on' || DevblocksPlatform::strStartsWith($field, 'on.'))
					return DevblocksSearchCriteria::getVirtualContextParamFromTokens($field, $tokens, 'on', SearchFields_ContextScheduledBehavior::VIRTUAL_TARGET);
				
				$search_fields = $this->getQuickSearchFields();
				return DevblocksSearchCriteria::getParamFromQueryFieldTokens($field, $tokens, $search_fields);
				break;
		}
		
		return false;
	}
	
	function render() {
		$this->_sanitize();

		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);
		
		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_BEHAVIOR_SCHEDULED);
		$tpl->assign('custom_fields', $custom_fields);
		
		switch($this->renderTemplate) {
			default:
				$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/bot/scheduled_behavior/view.tpl');
				$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_ContextScheduledBehavior::BEHAVIOR_ID:
				$label_map = SearchFields_ContextScheduledBehavior::getLabelsForKeyValues($field, $values);
				parent::_renderCriteriaParamString($param, $label_map);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}
	
	function renderVirtualCriteria($param) {
		$field = $param->field;

		switch($field) {
			case SearchFields_ContextScheduledBehavior::VIRTUAL_BEHAVIOR_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.behavior')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
			
			case SearchFields_ContextScheduledBehavior::VIRTUAL_BOT_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.bot')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_ContextScheduledBehavior::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
			
			case SearchFields_ContextScheduledBehavior::VIRTUAL_TARGET:
				$this->_renderVirtualContextLinks($param, 'On', 'On', 'On');
				break;
		}
	}

	function getFields() {
		return SearchFields_ContextScheduledBehavior::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_ContextScheduledBehavior::BEHAVIOR_ID:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_ContextScheduledBehavior::BEHAVIOR_NAME:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case 'placeholder_number':
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;

			case SearchFields_ContextScheduledBehavior::RUN_DATE:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;

			case 'placeholder_bool':
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_ContextScheduledBehavior::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			// [TODO]
			case SearchFields_ContextScheduledBehavior::BEHAVIOR_BOT_ID:
				break;
				
			case SearchFields_ContextScheduledBehavior::VIRTUAL_TARGET:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
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

class Context_ContextScheduledBehavior extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek {
	const ID = 'cerberusweb.contexts.behavior.scheduled';
	
	static function isReadableByActor($models, $actor) {
		// Everyone can read
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Admins can modify
		if(false != ($actor = CerberusContexts::polymorphActorToDictionary($actor)))
			if(CerberusContexts::isActorAnAdmin($actor))
				return CerberusContexts::allowEverything($models);
		
		return CerberusContexts::denyEverything($models);
	}

	function getRandom() {
		return DAO_ContextScheduledBehavior::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=scheduled_behavior&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_ContextScheduledBehavior();
		
		$properties['behavior_id'] = [
			'label' => mb_ucfirst($translate->_('common.behavior')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->behavior_id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_BEHAVIOR,
			]
		];
		
		$properties['id'] = [
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		];
		
		$properties['run_date'] = [
			'label' => mb_ucfirst($translate->_('dao.context_scheduled_behavior.run_date')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->run_date,
		];
		
		$properties['context_id'] = [
			'label' => mb_ucfirst($translate->_('common.target')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->context_id,
			'params' => [
				'context' => $model->context,
			]
		];
		
		return $properties;
	}
	
	function getMeta($context_id) {
		$context_scheduled_behavior = DAO_ContextScheduledBehavior::get($context_id);
		
		$url = $this->profileGetUrl($context_id);
		
		return array(
			'id' => $context_scheduled_behavior->id,
			'name' => '', //$context_scheduled_behavior->name,
			'permalink' => $url,
			//'updated' => $context_scheduled_behavior->updated_at, // [TODO]
		);
	}
	
	function getDefaultProperties() {
		return [
			'run_date',
			'behavior__label',
			'target__label',
		];
	}
	
	function getContext($context_scheduled_behavior, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Scheduled Behavior:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_BEHAVIOR_SCHEDULED);

		// Polymorph
		if(is_numeric($context_scheduled_behavior)) {
			$context_scheduled_behavior = DAO_ContextScheduledBehavior::get($context_scheduled_behavior);
		} elseif($context_scheduled_behavior instanceof Model_ContextScheduledBehavior) {
			// It's what we want already.
		} elseif(is_array($context_scheduled_behavior)) {
			$context_scheduled_behavior = Cerb_ORMHelper::recastArrayToModel($context_scheduled_behavior, 'Model_ContextScheduledBehavior');
		} else {
			$context_scheduled_behavior = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'name' => $prefix.$translate->_('common.name'),
			'record_url' => $prefix.$translate->_('common.url.record'),
			'run_date' => $prefix.$translate->_('dao.context_scheduled_behavior.run_date'),
			'target__label' => $prefix.$translate->_('common.target'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'record_url' => Model_CustomField::TYPE_URL,
			'run_date' => Model_CustomField::TYPE_DATE,
			'target__label' => 'context_url',
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = [];
		
		$token_values['_context'] = CerberusContexts::CONTEXT_BEHAVIOR_SCHEDULED;
		$token_values['_types'] = $token_types;
		
		if($context_scheduled_behavior) {
			$behavior = $context_scheduled_behavior->getBehavior();
			
			$token_values['_loaded'] = true;
			$token_values['_label'] = $behavior->title;
			$token_values['id'] = $context_scheduled_behavior->id;
			$token_values['behavior_id'] = $context_scheduled_behavior->behavior_id;
			$token_values['run_date'] = $context_scheduled_behavior->run_date;
			$token_values['target__context'] = $context_scheduled_behavior->context;
			$token_values['target_id'] = $context_scheduled_behavior->context_id;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($context_scheduled_behavior, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=scheduled_behavior&id=%d", $context_scheduled_behavior->id), true);
		}
		
		// Behavior
		$merge_token_labels = $merge_token_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_BEHAVIOR, null, $merge_token_labels, $merge_token_values, '', true);

		CerberusContexts::merge(
			'behavior_',
			$prefix.'Behavior:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'behavior_id' => DAO_ContextScheduledBehavior::BEHAVIOR_ID,
			'id' => DAO_ContextScheduledBehavior::ID,
			'links' => '_links',
			'run_date' => DAO_ContextScheduledBehavior::RUN_DATE,
			'target__context' => DAO_ContextScheduledBehavior::CONTEXT,
			'target_id' => DAO_ContextScheduledBehavior::CONTEXT_ID,
		];
	}
	
	function getKeyMeta() {
		$keys = parent::getKeyMeta();
		
		$keys['variables'] = [
			'is_immutable' => false,
			'is_required' => false,
			'notes' => 'JSON-encoded key/value object',
			'type' => 'object',
		];
		
		$keys['behavior_id']['notes'] = "The ID of the [behavior](/docs/records/types/behavior/) to be scheduled";
		$keys['run_date']['notes'] = "The date/time to run the scheduled behavior";
		$keys['target__context']['notes'] = "The [record type](/docs/records/types/) of the target record to run the behavior against";
		$keys['target_id']['notes'] = "The ID of the target record";
		
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		switch(DevblocksPlatform::strLower($key)) {
			case 'variables':
				if(!is_array($value)) {
					$error = 'must be an object.';
					return false;
				}
				
				if(false == ($json = json_encode($value))) {
					$error = 'could not be JSON encoded.';
					return false;
				}
				
				$out_fields[DAO_ContextScheduledBehavior::VARIABLES_JSON] = $json;
				break;
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
		
		$context = CerberusContexts::CONTEXT_BEHAVIOR_SCHEDULED;
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
		$view->name = 'Context Scheduled Behavior';

		$view->renderSortBy = SearchFields_ContextScheduledBehavior::RUN_DATE;
		$view->renderSortAsc = true;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=[], $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Context Scheduled Behavior';
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_ContextScheduledBehavior::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);
		
		$context = CerberusContexts::CONTEXT_BEHAVIOR_SCHEDULED;
		
		if(!empty($context_id)) {
			$model = DAO_ContextScheduledBehavior::get($context_id);
			
		} else {
			$model = new Model_ContextScheduledBehavior();
			
			if(!empty($edit)) {
				$tokens = explode(' ', trim($edit));
				
				foreach($tokens as $token) {
					@list($k,$v) = explode(':', $token);
					
					if($v)
					switch($k) {
						/*
						case 'email':
							$model->primary_email_id = intval($v);
							break;
						*/
					}
				}
			}
		}
		
		if(empty($context_id) || $edit) {
			// Contexts
			$contexts = Extension_DevblocksContext::getByMacros(false);
			$tpl->assign('contexts', $contexts);
			
			// Current event point
			if($model->behavior_id && false != ($behavior = $model->getBehavior())) {
				$tpl->assign('event_point', $behavior->event_point);
			} else {
				//$tpl->assign('event_point', key($contexts));
			}
			
			// Custom fields
			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);
			
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
			
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);
			
			// Model
			$tpl->assign('model', $model);
			
			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::internal/bot/scheduled_behavior/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
	}
};