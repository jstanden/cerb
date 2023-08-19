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

class DAO_TriggerEvent extends Cerb_ORMHelper {
	const BOT_ID = 'bot_id';
	const EVENT_PARAMS_JSON = 'event_params_json';
	const EVENT_POINT = 'event_point';
	const ID = 'id';
	const IS_DISABLED = 'is_disabled';
	const IS_PRIVATE = 'is_private';
	const PRIORITY = 'priority';
	const TITLE = 'title';
	const UPDATED_AT = 'updated_at';
	const URI = 'uri';
	const VARIABLES_JSON = 'variables_json';
	
	const CACHE_ALL = 'cerberus_cache_behavior_all';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// int(10) unsigned
		$validation
			->addField(self::BOT_ID)
			->id()
			->setRequired(true)
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_BOT))
			;
		// text
		$validation
			->addField(self::EVENT_PARAMS_JSON)
			->string()
			->setMaxLength(65535)
			;
		// varchar(255)
		$validation
			->addField(self::EVENT_POINT)
			->string()
			->setMaxLength(255)
			->setRequired(true)
			->addValidator(function($value, &$error=null) {
				if(false == (Extension_DevblocksEvent::get($value, false))) {
					$error = sprintf("'%s' is an invalid event point.", $value);
					return false;
				}
				
				return true;
			})
			;
		// int(10) unsigned
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// tinyint(4)
		$validation
			->addField(self::IS_DISABLED)
			->bit()
			;
		// tinyint(3) unsigned
		$validation
			->addField(self::IS_PRIVATE)
			->bit()
			;
		// int(10) unsigned
		$validation
			->addField(self::PRIORITY)
			->uint(4)
			;
		// varchar(255)
		$validation
			->addField(self::TITLE)
			->string()
			->setMaxLength(255)
			->setRequired(true)
			;
		// int(10) unsigned
		$validation
			->addField(self::UPDATED_AT)
			->timestamp()
			;
		// varchar(255)
		$validation
			->addField(self::URI)
			->string()
			->setUnique(get_class())
			->setNotEmpty(false)
			->addValidator(function($string, &$error=null) {
				if(0 != strcmp($string, DevblocksPlatform::strAlphaNum($string, '_'))) {
					$error = "may only contain letters, numbers, and underscores";
					return false;
				}
				
				if(strlen($string) > 128) {
					$error = "must be shorter than 128 characters.";
					return false;
				}
				
				return true;
			})
			;
		// text
		$validation
			->addField(self::VARIABLES_JSON)
			->string()
			->setMaxLength(65535)
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
		
		if(!isset($fields[self::UPDATED_AT]))
			$fields[self::UPDATED_AT] = time();
		
		$sql = "INSERT INTO trigger_event () VALUES ()";
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		CerberusContexts::checkpointCreations(CerberusContexts::CONTEXT_BEHAVIOR, $id);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		$context = CerberusContexts::CONTEXT_BEHAVIOR;
		self::_updateAbstract($context, $ids, $fields);
		
		parent::_update($ids, 'trigger_event', $fields);
		self::clearCache();
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('trigger_event', $fields, $where);
		self::clearCache();
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_BEHAVIOR;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		if(!$id && !isset($fields[self::BOT_ID])) {
			$error = "A 'bot_id' is required.";
			return false;
		}
		
		if(isset($fields[self::BOT_ID])) {
			@$bot_id = $fields[self::BOT_ID];
			
			if(!$bot_id || false == ($bot = DAO_Bot::get($bot_id))) {
				$error = "Invalid 'bot_id' value.";
				return false;
			}
			
			if(!CerberusContexts::isOwnableBy($bot->owner_context, $bot->owner_context_id, $actor)) {
				$error = "You do not have permission to create behaviors on this bot.";
				return false;
			}
		}
		
		return true;
	}
	
	static function recursiveImportDecisionNodes($nodes, $behavior_id, $parent_id, $pos=0) {
		if(!is_array($nodes) || empty($nodes))
			return;
		
		$response = [];
		
		$status_ids = [
			'live' => 0,
			'disabled' => 1,
			'simulator' => 2,
		];
		
		foreach($nodes as $node) {
			if(
				!isset($node['type'])
				|| !isset($node['title'])
				|| !in_array($node['type'], ['action','loop','outcome','subroutine','switch'])
			)
				return false;
			
			$status_id = @$status_ids[$node['status']] ?? 0;
			
			$fields = [
				DAO_DecisionNode::NODE_TYPE => $node['type'],
				DAO_DecisionNode::TITLE => $node['title'],
				DAO_DecisionNode::PARENT_ID => $parent_id,
				DAO_DecisionNode::TRIGGER_ID => $behavior_id,
				DAO_DecisionNode::POS => $pos++,
				DAO_DecisionNode::STATUS_ID => $status_id,
				DAO_DecisionNode::PARAMS_JSON => isset($node['params']) ? json_encode($node['params']) : '',
			];
			
			$node_id = DAO_DecisionNode::create($fields);
			
			if(!$response) {
				$node['id'] = $node_id;
				$response = $node;
			}
			
			if(isset($node['nodes']) && is_array($node['nodes']) && !empty($node['nodes']))
				if(false == (self::recursiveImportDecisionNodes($node['nodes'], $behavior_id, $node_id)))
					return false;
		}
		
		return $response;
	}
	
	/**
	 *
	 * @param bool $nocache
	 * @return Model_TriggerEvent[]
	 */
	static function getAll($nocache=false) {
		$cache = DevblocksPlatform::services()->cache();
		if($nocache || null === ($behaviors = $cache->load(self::CACHE_ALL))) {
			$behaviors = self::getWhere(
				null,
				DAO_TriggerEvent::PRIORITY,
				true,
				null,
				Cerb_ORMHelper::OPT_GET_MASTER_ONLY
			);
			
			if(!is_array($behaviors))
				return false;
			
			$cache->save($behaviors, self::CACHE_ALL);
		}
		
		return $behaviors;
	}
	
	static function getReadableByActor($actor, $event_point=null, $with_disabled=false, $ignore_admins=false) {
		$actor = CerberusContexts::polymorphActorToDictionary($actor, false);
		$bots = DAO_Bot::getAll();
		$bot_privs = [];
		
		if($event_point) {
			$behaviors = DAO_TriggerEvent::getByEvent($event_point, $with_disabled);
		} else {
			$behaviors = DAO_TriggerEvent::getAll();
		}
		
		if(empty($behaviors))
			return [];
		
		$results = [];
		
		if(is_array($behaviors))
		foreach($behaviors as $behavior_id => $behavior) { /* @var $behavior Model_TriggerEvent */
			if(false == ($bot = $bots[$behavior->bot_id]))
				continue;
			
			if(!array_key_exists($bot->id, $bot_privs)) {
				$bot_privs[$bot->id] = Context_Bot::isReadableByActor($bot, $actor, $ignore_admins);
			}
			
			// Ignore bots the actor doesn't have access to
			if(false == @$bot_privs[$bot->id])
				continue;
		
			// Ignore disabled
			if(!$with_disabled && $bot->is_disabled)
				continue;
		
			// Private behaviors only show up to same actor
			if($behavior->is_private && !($actor->_context == CerberusContexts::CONTEXT_BOT && $bot->id == $actor->id))
				continue;
			
			$result = clone $behavior; /* @var $result Model_TriggerEvent */
			
			$has_public_vars = false;
			if(is_array($result->variables))
			foreach($result->variables as $var_data) {
				if(empty($var_data['is_private']))
					$has_public_vars = true;
			}
			$result->has_public_vars = $has_public_vars;
			
			$results[$behavior_id] = $result;
		}
		
		DevblocksPlatform::sortObjects($results, 'title', true);
		
		return $results;
	}
	
	/**
	 *
	 * @param integer $va_id
	 * @param string $event_point
	 * @return Model_TriggerEvent[]
	 */
	static function getByBot($va, $event_point=null, $with_disabled=false, $sort_by='title') {
		// Polymorph if necessary
		if(is_numeric($va))
			$va = DAO_Bot::get($va);
		
		// If we didn't resolve to a VA model
		if(!($va instanceof Model_Bot))
			return [];
		
		if(!$with_disabled && $va->is_disabled)
			return [];
		
		$behaviors = self::getAll();
		$results = [];

		if(is_array($behaviors))
		foreach($behaviors as $behavior_id => $behavior) { /* @var $behavior Model_TriggerEvent */
			if($behavior->bot_id != $va->id)
				continue;
			
			if($event_point && $behavior->event_point != $event_point)
				continue;
			
			if(!$with_disabled && $behavior->is_disabled)
				continue;
			
			// Are we only showing approved events?
			// Are we removing denied events?
			if(!$va->canUseEvent($behavior->event_point))
				continue;
			
			$results[$behavior_id] = $behavior;
		}
		
		// Sort
		
		switch($sort_by) {
			case 'title':
			case 'priority':
				break;
				
			default:
				$sort_by = 'title';
				break;
		}
		
		DevblocksPlatform::sortObjects($results, $sort_by, true);
		
		return $results;
	}
	
	static function getByEvent($event_id, $with_disabled=false) {
		$vas = DAO_Bot::getAll();
		$behaviors = [];

		foreach($vas as $va) { /* @var $va Model_Bot */
			$va_behaviors = $va->getBehaviors($event_id, $with_disabled, 'priority');
			
			if(!empty($va_behaviors))
				$behaviors += $va_behaviors;
		}
		
		return $behaviors;
	}
	
	/**
	 * 
	 * @param string $uri
	 * @param boolean $with_disabled
	 */
	static function getByUri($uri, $with_disabled=false) {
		if(empty($uri))
			return null;
		
		$behaviors = self::getAll();
		
		$behaviors = array_filter($behaviors, function($behavior) use ($with_disabled, $uri) {
			if(!$with_disabled && $behavior->isDisabled())
				return false;
			
			if($behavior->uri && $behavior->uri == $uri)
				return true;
			
			return false;
		});
		
		if(!empty($behaviors))
			return reset($behaviors);
		
		return null;
	}
	
	/**
	 * @param integer $id
	 * @return Model_TriggerEvent
	 */
	static function get($id) {
		if(empty($id))
			return null;
		
		$behaviors = self::getAll();
		
		if(isset($behaviors[$id]))
			return $behaviors[$id];
		
		return null;
	}
	
	static function getVariableTypes() {
		// Variables types
		$variable_types = [
			Model_CustomField::TYPE_DATE => 'Date',
			Model_CustomField::TYPE_NUMBER => 'Number',
			'contexts' => 'List:(Mixed Records)',
			Model_CustomField::TYPE_DROPDOWN => 'Picklist',
			Model_CustomField::TYPE_LINK => 'Record ID',
			Model_CustomField::TYPE_SINGLE_LINE => 'Text',
			Model_CustomField::TYPE_WORKER => 'Worker',
			Model_CustomField::TYPE_CHECKBOX => 'Yes/No',
		];
		
		$contexts_list = Extension_DevblocksContext::getAll(false, 'va_variable');
		foreach($contexts_list as $list_context_id => $list_context) {
			$context_aliases = Extension_DevblocksContext::getAliasesForContext($list_context);
			$plural = $context_aliases['plural'] ?? null;
			$variable_types['ctx_' . $list_context_id] = 'List:' . DevblocksPlatform::strUpperFirst($plural ?: $list_context->name);
		}
		
		return $variable_types;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_TriggerEvent[]
	 */
	static function getWhere($where=null, $sortBy=DAO_TriggerEvent::PRIORITY, $sortAsc=true, $limit=null, $options=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, title, uri, is_disabled, is_private, event_point, bot_id, priority, event_params_json, updated_at, variables_json ".
			"FROM trigger_event ".
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
	 * @param mysqli_result|false $rs
	 * @return Model_TriggerEvent[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return $objects;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_TriggerEvent();
			$object->id = intval($row['id']);
			$object->title = $row['title'];
			$object->uri = $row['uri'];
			$object->is_disabled = intval($row['is_disabled']);
			$object->is_private = intval($row['is_private']);
			$object->priority = intval($row['priority']);
			$object->event_point = $row['event_point'];
			$object->bot_id = $row['bot_id'];
			$object->updated_at = intval($row['updated_at']);
			$object->event_params = @json_decode($row['event_params_json'], true);
			
			$variables = @json_decode($row['variables_json'], true);
			$object->variables = is_array($variables) ? $variables : [];
			
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static public function countByBot($bot_id) {
		$db = DevblocksPlatform::services()->database();
		return $db->GetOneReader(sprintf("SELECT count(*) FROM trigger_event ".
			"WHERE bot_id = %d",
			$bot_id
		));
	}
	
	static function delete($ids) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		if(empty($ids)) return false;
		
		$ids_list = implode(',', self::qstrArray($ids));
		
		// [TODO] Use DAO_DecisionNode::deleteByTrigger() to cascade
		$db->ExecuteMaster(sprintf("DELETE FROM decision_node WHERE trigger_id IN (%s)", $ids_list));
		
		$db->ExecuteMaster(sprintf("DELETE FROM trigger_event WHERE id IN (%s)", $ids_list));
		
		foreach($ids as $id)
			$db->ExecuteMaster(sprintf("DELETE FROM devblocks_registry WHERE entry_key LIKE 'trigger.%d.%%'", $id));
		
		DAO_ContextScheduledBehavior::deleteByBehavior($ids);
		
		self::clearCache();
		return true;
	}
	
	static function deleteByBot($va_id) {
		$results = self::getWhere(sprintf("%s = %d",
			self::BOT_ID,
			$va_id
		));
		
		if(is_array($results))
		foreach($results as $result) {
			self::delete($result->id);
		}
		
		return TRUE;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_TriggerEvent::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_TriggerEvent', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"trigger_event.id as %s, ".
			"trigger_event.title as %s, ".
			"trigger_event.uri as %s, ".
			"trigger_event.is_disabled as %s, ".
			"trigger_event.is_private as %s, ".
			"trigger_event.priority as %s, ".
			"trigger_event.bot_id as %s, ".
			"trigger_event.updated_at as %s, ".
			"trigger_event.event_point as %s ",
				SearchFields_TriggerEvent::ID,
				SearchFields_TriggerEvent::TITLE,
				SearchFields_TriggerEvent::URI,
				SearchFields_TriggerEvent::IS_DISABLED,
				SearchFields_TriggerEvent::IS_PRIVATE,
				SearchFields_TriggerEvent::PRIORITY,
				SearchFields_TriggerEvent::BOT_ID,
				SearchFields_TriggerEvent::UPDATED_AT,
				SearchFields_TriggerEvent::EVENT_POINT
			);
			
		$join_sql = "FROM trigger_event ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_TriggerEvent');
	
		return array(
			'primary_table' => 'trigger_event',
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
			SearchFields_TriggerEvent::ID,
			$select_sql,
			$join_sql,
			$where_sql,
			$sort_sql,
			$page,
			$limit,
			$withCounts
		);
	}

	static public function clearCache() {
		$cache = DevblocksPlatform::services()->cache();
		$cache->remove(self::CACHE_ALL);
	}
	
	static public function getNextPosByParent($trigger_id, $parent_id) {
		$db = DevblocksPlatform::services()->database();

		$count = $db->GetOneMaster(sprintf("SELECT MAX(pos) FROM decision_node ".
			"WHERE trigger_id = %d AND parent_id = %d",
			$trigger_id,
			$parent_id
		));

		if(is_null($count))
			return 0;

		return intval($count) + 1;
	}
};

class SearchFields_TriggerEvent extends DevblocksSearchFields {
	const ID = 't_id';
	const TITLE = 't_title';
	const IS_DISABLED = 't_is_disabled';
	const IS_PRIVATE = 't_is_private';
	const PRIORITY = 't_priority';
	const BOT_ID = 't_bot_id';
	const EVENT_POINT = 't_event_point';
	const URI = 't_uri';
	const UPDATED_AT = 't_updated_at';
	
	const VIRTUAL_BOT_SEARCH = '*_bot_search';
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_USABLE_BY = '*_usable_by';
	const VIRTUAL_WATCHERS = '*_workers';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'trigger_event.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_BEHAVIOR => new DevblocksSearchFieldContextKeys('trigger_event.id', self::ID),
			CerberusContexts::CONTEXT_BOT => new DevblocksSearchFieldContextKeys('trigger_event.bot_id', self::BOT_ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_BOT_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_BOT, 'trigger_event.bot_id');
				
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_BEHAVIOR, self::getPrimaryKey());

			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_BEHAVIOR), '%s'), self::getPrimaryKey());
			
			case self::VIRTUAL_USABLE_BY:
				return self::_getWhereSQLForUsableBy($param, self::getPrimaryKey());
				
			case self::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, CerberusContexts::CONTEXT_BEHAVIOR, self::getPrimaryKey());
			
			default:
				if(DevblocksPlatform::strStartsWith($param->field, 'cf_')) {
					return self::_getWhereSQLFromCustomFields($param);
				} else {
					return $param->getWhereSQL(self::getFields(), self::getPrimaryKey());
				}
		}
	}
	
	static private function _getWhereSQLForUsableBy($param, $pkey) {
		// Handle nested quick search filters first
		if($param->operator == DevblocksSearchCriteria::OPER_CUSTOM) {
			if(!is_array($param->value))
				return '0';
			
			$actor_context = $param->value['context'];
			$actor_id = $param->value['id'];
			
			$behaviors = DAO_TriggerEvent::getReadableByActor([$actor_context, $actor_id], null, false, true);
			
			if(empty($behaviors))
				return '0';
			
			$behavior_ids = array_keys($behaviors);
			
			$sql = sprintf("%s IN (%s)",
				$pkey,
				implode(',', $behavior_ids)
			);
			
			return $sql;
		}
		
		return '0';
	}
	
	static function getFieldForSubtotalKey($key, $context, array $query_fields, array $search_fields, $primary_key) {
		switch($key) {
			case 'bot':
				$key = 'bot.id';
				break;
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_TriggerEvent::BOT_ID:
				$models = DAO_Bot::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'name', 'id');
				
			case SearchFields_TriggerEvent::EVENT_POINT:
				return parent::_getLabelsForKeyExtensionValues(Extension_DevblocksEvent::POINT);
				
			case SearchFields_TriggerEvent::ID:
				$models = DAO_TriggerEvent::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'title', 'id');
				
			case SearchFields_TriggerEvent::IS_DISABLED:
			case SearchFields_TriggerEvent::IS_PRIVATE:
				return parent::_getLabelsForKeyBooleanValues();
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
			self::ID => new DevblocksSearchField(self::ID, 'trigger_event', 'id', $translate->_('common.id'), null, true),
			self::TITLE => new DevblocksSearchField(self::TITLE, 'trigger_event', 'title', $translate->_('common.title'), null, true),
			self::URI => new DevblocksSearchField(self::URI, 'trigger_event', 'uri', $translate->_('common.uri'), null, true),
			self::IS_DISABLED => new DevblocksSearchField(self::IS_DISABLED, 'trigger_event', 'is_disabled', $translate->_('dao.trigger_event.is_disabled'), null, true),
			self::IS_PRIVATE => new DevblocksSearchField(self::IS_PRIVATE, 'trigger_event', 'is_private', $translate->_('common.is_private'), null, true),
			self::PRIORITY => new DevblocksSearchField(self::PRIORITY, 'trigger_event', 'priority', $translate->_('common.priority'), null, true),
			self::BOT_ID => new DevblocksSearchField(self::BOT_ID, 'trigger_event', 'bot_id', $translate->_('common.bot'), null, true),
			self::EVENT_POINT => new DevblocksSearchField(self::EVENT_POINT, 'trigger_event', 'event_point', $translate->_('common.event'), null, true),
			self::UPDATED_AT => new DevblocksSearchField(self::UPDATED_AT, 'trigger_event', 'updated_at', $translate->_('common.updated'), null, true),
				
			self::VIRTUAL_BOT_SEARCH => new DevblocksSearchField(self::VIRTUAL_BOT_SEARCH, '*', 'bot_search', null, null, false),
			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_USABLE_BY => new DevblocksSearchField(self::VIRTUAL_USABLE_BY, '*', 'usable_by', null, null, false),
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

class Model_TriggerEvent extends DevblocksRecordModel {
	public $bot_id;
	public $event_params = [];
	public $event_point;
	public $id;
	public $is_disabled;
	public $is_private;
	public $priority;
	public $title;
	public $updated_at;
	public $uri;
	public $variables = [];
	
	private $_nodes = [];
	
	public function isDisabled() {
		if($this->is_disabled)
			return true;
		
		if(false == ($bot = $this->getBot()))
			return true;
		
		if($bot->is_disabled)
			return true;
		
		return false;
	}
	
	public function hasPublicVariables() {
		if(is_array($this->variables))
		foreach($this->variables as $v) {
			if(isset($v['is_private']) && !$v['is_private'])
				return true;
		}
		
		return false;
	}
	
	/**
	 * @return Extension_DevblocksEvent
	 */
	public function getEvent() {
		if(null == ($event = Extension_DevblocksEvent::get($this->event_point, true))
			|| !$event instanceof Extension_DevblocksEvent)
			return NULL;
		
		return $event;
	}
	
	public function getBot() {
		return DAO_Bot::get($this->bot_id);
	}
	
	public function getNextPosByParent($parent_id) {
		return DAO_TriggerEvent::getNextPosByParent($this->id, $parent_id);
	}
	
	public function formatVariable($var, $value, DevblocksDictionaryDelegate $dict=null) {
		switch($var['type']) {
			case Model_CustomField::TYPE_MULTI_LINE:
			case Model_CustomField::TYPE_SINGLE_LINE:
			case Model_CustomField::TYPE_URL:
				settype($value, 'string');
				break;
				
			case Model_CustomField::TYPE_DROPDOWN:
				$options = DevblocksPlatform::parseCrlfString($var['params']['options'], true);
	
				if(!is_array($options))
					throw new Exception(sprintf("The picklist variable '%s' has no options.",
						$var['key']
					));
				
				if(!in_array($value, $options))
					throw new Exception(sprintf("The picklist variable '%s' has no option '%s'. Valid options are: %s",
						$var['key'],
						$value,
						implode(', ', $options)
					));
				break;
				
			case Model_CustomField::TYPE_CHECKBOX:
				$value = !empty($value) ? 1 : 0;
				break;
				
			case Model_CustomField::TYPE_CURRENCY:
			case Model_CustomField::TYPE_DECIMAL:
			case Model_CustomField::TYPE_NUMBER:
				settype($value, 'integer');
				break;
				
			case Model_CustomField::TYPE_LINK:
				@$context = DevblocksPlatform::importVar($var['params']['context'], 'string', null);
				
				settype($value, 'integer');
				
				// Also add a key for the context when suffixed `_id`
				if($dict instanceof DevblocksDictionaryDelegate && $context && DevblocksPlatform::strEndsWith($var['key'], '_id')) {
					$ctx_key = mb_substr($var['key'], 0, -3) . '__context';
					$dict->set($ctx_key, $context);
				}
				break;
				
			case Model_CustomField::TYPE_WORKER:
				if($dict instanceof DevblocksDictionaryDelegate && is_string($value) && DevblocksPlatform::strStartsWith($value, 'var_')) {
					$value = $dict->$value;
					
					if(is_array($value))
						$value = key($value);
				}
				
				settype($value, 'integer');
				
				if(false == (DAO_Worker::get($value)))
					throw new Exception(sprintf("The worker variable '%s' can not be set to invalid worker #%d.",
						$var['key'],
						$value
					));
				
				break;
				
			case Model_CustomField::TYPE_DATE:
				if(is_numeric($value))
					break;
				
				settype($value, 'string');
				
				if(false == ($value = strtotime($value))) {
					throw new Exception(sprintf("The date variable '%s' has an invalid value.",
						$var['key']
					));
				}
				break;
				
			// [TODO] Future public variable types
			case Model_CustomField::TYPE_LIST:
			case Model_CustomField::TYPE_MULTI_CHECKBOX:
			case Model_CustomField::TYPE_FILE:
			case Model_CustomField::TYPE_FILES:
				break;
				
			default:
				if('ctx_' == substr($var['type'], 0, 4)) {
					$objects = [];
					$json = null;
					
					if(is_array($value)) {
						$json = $value;
						
					} elseif (is_string($value)) {
						@$json = json_decode($value, true);
						
					}
					
					if(!is_array($json)) {
						throw new Exception(sprintf("The list variable '%s' must be set to an array of IDs.",
							$var['key']
						));
					}
						
					$context = substr($var['type'], 4);
					
					foreach($json as $context_id) {
						$labels = [];
						$values = [];
						
						CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
						
						if(!isset($values['_loaded']))
							continue;
						
						$objects[$context_id] = new DevblocksDictionaryDelegate($values);
					}
					
					$value = $objects;
				}
				break;
		}
		
		return $value;
	}
	
	private function _getNodes() {
		if(empty($this->_nodes))
			$this->_nodes = DAO_DecisionNode::getByTriggerParent($this->id);
		
		return $this->_nodes;
	}
	
	public function getNodes($of_type=null) {
		$nodes = $this->_getNodes();
		
		if($of_type) {
			$nodes = array_filter($nodes, function($node) use ($of_type) {
				if($of_type == $node->node_type)
					return true;
				
				return false;
			});
		}
		
		return $nodes;
	}
	
	public function getDecisionTreeData($root_id = 0) {
		$nodes = $this->_getNodes();
		$tree = $this->_getTree();
		$depths = [];
		$this->_recurseBuildTreeDepths($tree, $root_id, $depths);
		
		return array('nodes' => $nodes, 'tree' => $tree, 'depths' => $depths);
	}
	
	private function _getTree() {
		$nodes = $this->_getNodes();
		$tree = array(0 => []); // root
		
		foreach($nodes as $node) {
			if(!isset($tree[$node->id]))
				$tree[$node->id] = [];
				
			// Parent chain
			if(!isset($tree[$node->parent_id]))
				$tree[$node->parent_id] = [];
			
			$tree[$node->parent_id][$node->id] = $node->id;
		}
		
		return $tree;
	}
	
	private function _recurseBuildTreeDepths($tree, $node_id, &$out, $depth=0) {
		foreach($tree[$node_id] as $child_id) {
			$out[$child_id] = $depth;
			$this->_recurseBuildTreeDepths($tree, $child_id, $out, $depth+1);
		}
	}
	
	public function runDecisionTree(DevblocksDictionaryDelegate $dict, $dry_run=false, Extension_DevblocksEvent $event=null) {
		return $this->_runDecisionTree($dict, $dry_run, $event);
	}
	
	public function resumeDecisionTree(DevblocksDictionaryDelegate $dict, $dry_run=false, Extension_DevblocksEvent $event=null, array $replay=[]) {
		return $this->_runDecisionTree($dict, $dry_run, $event, $replay);
	}
	
	private function _runDecisionTree(DevblocksDictionaryDelegate $dict, $dry_run=false, Extension_DevblocksEvent $event=null, array $replay=[]) {
		$metrics = DevblocksPlatform::services()->metrics();
		
		$start_runtime = intval(microtime(true));
		
		$nodes = $this->_getNodes();
		$tree = $this->_getTree();
		$path = [];
		
		// Lazy load the event if necessary (otherwise reuse a passed scope)
		if(is_null($event))
			$event = $this->getEvent();
		
		/**
		 * Late-binding for behavior placeholders (this works inside shared context loops)
		 */
		
		$dict->__trigger = $this;
		
		$merge_labels = $merge_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_BEHAVIOR, $this, $merge_labels, $merge_values, null, true);
		$dict->scrubKeys('behavior_');
		$dict->merge('behavior_', '', $merge_labels, $merge_values);
		
		/**
		 * Run behavior
		 */
		
		$this->_recurseRunTree($event, $nodes, $tree, 0, $dict, $path, $replay, $dry_run);
		
		$result = end($path) ?: '';
		$exit_state = 'STOP';
		
		if($result === 'SUSPEND') {
			array_pop($path);
			$exit_state = 'SUSPEND';
		}
		
		$runtime_ms = intval((microtime(true) - $start_runtime) * 1000);
		$metrics->increment('cerb.behavior.invocations', 1, ['behavior_id'=>$this->id,'event'=>$this->event_point]);
		$metrics->increment('cerb.behavior.duration', $runtime_ms, ['behavior_id'=>$this->id,'event'=>$this->event_point]);
		
		return [
			'path' => $path,
			'exit_state' => $exit_state,
		];
	}
	
	private function _recurseRunTree($event, $nodes, $tree, $node_id, DevblocksDictionaryDelegate $dict, &$path, &$replay, $dry_run=false) {
		$logger = DevblocksPlatform::services()->log('Bot');

		// Did the last action request that we exit early?
		if(false !== in_array(end($path) ?: '', ['STOP','SUSPEND']))
			return;
		
		$replay_id = null;
		
		if(is_array($replay) && !empty($replay)) {
			$replay_id = array_shift($replay);
			reset($replay);
			
			$node_id = $replay_id;
			EventListener_Triggers::logNode($node_id);
			
			if(!empty($node_id))
				$logger->info('REPLAY ' . $nodes[$node_id]->node_type . ' :: ' . $nodes[$node_id]->title . ' (' . $node_id . ')');
		}
		
		$pass = true;
		
		if(!empty($node_id) && isset($nodes[$node_id])) {
			switch($nodes[$node_id]->status_id) {
				// Disabled
				case 1:
					return;
					break;
					
				// Simulator only
				case 2:
					if(!$dry_run)
						return;
					break;
			}
			
			// If these conditions match...
			if(empty($replay_id))
				$logger->info('ENTER ' . $nodes[$node_id]->node_type . ' :: ' . $nodes[$node_id]->title . ' (' . $node_id . ')');
			
			// Handle the node type
			switch($nodes[$node_id]->node_type) {
				case 'subroutine':
					if($replay_id)
						break;
					
					$pass = true;
					$dict->__goto = $node_id;
					break;
					
				case 'loop':
					$foreach_json = $nodes[$node_id]->params['foreach_json'] ?? null;
					$as_placeholder = $nodes[$node_id]->params['as_placeholder'] ?? null;
					
					$tpl_builder = DevblocksPlatform::services()->templateBuilder();
					
					if(empty($foreach_json) || empty($as_placeholder)) {
						$pass = false;
						break;
					}
					
					if(false == ($json = json_decode($tpl_builder->build($foreach_json, $dict), true)) || !is_array($json)) {
						$pass = false;
						break;
					}
					
					$dict->set($as_placeholder . '__stack', $json);
					$dict->set($as_placeholder . '__counter', 0);
					
					if($replay_id)
						break;
					
					$pass = true;
					EventListener_Triggers::logNode($node_id);
					break;
					
				case 'outcome':
					if($replay_id)
						break;
					
					$cond_groups = $nodes[$node_id]->params['groups'] ?? null;
					
					if(is_array($cond_groups))
					foreach($cond_groups as $cond_group) {
						@$any = intval($cond_group['any']);
						$conditions = $cond_group['conditions'] ?? null;
						$group_pass = true;
						$logger->info(sprintf("Conditions are in `%s` group.", ($any ? 'any' : 'all')));
						
						if(!empty($conditions) && is_array($conditions))
						foreach($conditions as $condition_data) {
							// If something failed and we require all to pass
							if(!$group_pass && empty($any))
								continue;
								
							if(!isset($condition_data['condition']))
								continue;
							
							$condition = $condition_data['condition'];
							
							$group_pass = $event->runCondition($condition, $this, $condition_data, $dict);
							
							// Any
							if($group_pass && !empty($any))
								break;
						}
						
						$pass = $group_pass;
						
						// Any condition group failing is enough to stop
						if(empty($pass))
							break;
					}
					
					if($pass)
						EventListener_Triggers::logNode($node_id);
					break;
					
				case 'switch':
					if($replay_id)
						break;
					
					$pass = true;
					EventListener_Triggers::logNode($node_id);
					break;
					
				case 'action':
					$pass = true;
					EventListener_Triggers::logNode($node_id);
					
					// Run all the actions
					if(is_array(@$nodes[$node_id]->params['actions']))
					foreach($nodes[$node_id]->params['actions'] as $params) {
						if(!isset($params['action']))
							continue;
						
						$action = $params['action'];
						
						if(!$replay_id || $action == '_run_subroutine')
							$event->runAction($action, $this, $params, $dict, $dry_run);
						
						if(isset($dict->__exit)) {
							$path[] = $node_id;
							$path[] = ('suspend' == $dict->__exit) ? 'SUSPEND' : 'STOP';
							unset($dict->__exit);
							return;
						}
						
						switch($action) {
							case '_run_subroutine':
								if($dict->__goto) {
									$path[] = $node_id;
									
									@$new_state = intval($dict->__goto);
									unset($dict->__goto);
									
									$this->_recurseRunTree($event, $nodes, $tree, $new_state, $dict, $path, $replay, $dry_run);
									return;
								}
								break;
						}
					}
					
					break;
			}
			
			if($nodes[$node_id]->node_type == 'outcome' && !$replay_id) {
				$logger->info('');
				$logger->info($pass ? 'Using this outcome.' : 'Skipping this outcome.');
			}
			$logger->info('');
		}
		
		if($pass)
			$path[] = $node_id;

		$switch = false;
		$loop = false;
		
		do {
			if($node_id && 'loop' == $nodes[$node_id]->node_type) {
				$as_placeholder = $nodes[$node_id]->params['as_placeholder'] ?? null;
				@$as_placeholder_key = $as_placeholder . '__key';
				@$as_placeholder_stack = $as_placeholder . '__stack';
				@$as_placeholder_counter = $as_placeholder . '__counter';
				
				if(is_array($dict->$as_placeholder_stack) && count($dict->$as_placeholder_stack)) {
					$dict->set($as_placeholder_key, key($dict->$as_placeholder_stack));
					$dict->set($as_placeholder, current($dict->$as_placeholder_stack));
					$dict->set($as_placeholder_counter, intval($dict->$as_placeholder_counter) + 1);
					
					if($dict->$as_placeholder !== '*')
						array_shift($dict->$as_placeholder_stack);
					
					$loop = true;
				} else {
					$dict->unset($as_placeholder);
					$dict->unset($as_placeholder_key);
					$dict->unset($as_placeholder_stack);
					break;
				}
			}
			
			foreach($tree[$node_id] as $child_id) {
				// Then continue navigating down the tree...
				$parent_type = empty($node_id) ? 'trigger' : $nodes[$node_id]->node_type;
				$child_type = $nodes[$child_id]->node_type;
				
				if(!empty($replay)) {
					reset($replay);
					$replay_child_id = current($replay);
					
					if($replay_child_id != $child_id)
						continue;
				}
				
				switch($child_type) {
					// Always run all actions
					case 'action':
						if($pass) {
							$this->_recurseRunTree($event, $nodes, $tree, $child_id, $dict, $path, $replay, $dry_run);
							
							// If one of the actions said to stop...
							if(true === in_array(end($path) ?: '', ['STOP','SUSPEND']))
								return;
						}
						break;
						
					case 'subroutine':
						// Don't automatically run subroutines
						break;
						
					default:
						switch($parent_type) {
							case 'trigger':
								if($pass)
									$this->_recurseRunTree($event, $nodes, $tree, $child_id, $dict, $path, $replay, $dry_run);
								break;
								
							case 'subroutine':
								if($pass)
									$this->_recurseRunTree($event, $nodes, $tree, $child_id, $dict, $path, $replay, $dry_run);
								break;
								
							case 'loop':
								if($pass)
									$this->_recurseRunTree($event, $nodes, $tree, $child_id, $dict, $path, $replay, $dry_run);
								break;
								
							case 'outcome':
								if($pass)
									$this->_recurseRunTree($event, $nodes, $tree, $child_id, $dict, $path, $replay, $dry_run);
								break;
								
							case 'switch':
								// Only run the first successful child outcome
								if($pass && !$switch)
									if($this->_recurseRunTree($event, $nodes, $tree, $child_id, $dict, $path, $replay, $dry_run))
										$switch = true;
								break;
								
							case 'action':
								// No children
								break;
						}
						break;
				}
			}
			
			// If an action broke our loop, follow along
			if(!empty($replay) && !in_array(current($replay), $tree[$node_id]))
				$loop = false;
			
		} while($loop);
		
		return $pass;
	}
	
	function prepareResumeDecisionTree($message, &$interaction, &$actions, DevblocksDictionaryDelegate &$dict, &$resume_path) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		// Do we have special prompt handling instructions?
		if(isset($interaction->session_data['_prompt'])) {
			$prompt = $interaction->session_data['_prompt'] ?? null;
			
			// Are we saving a copy of the latest message into a placeholder?
			if(false != (@$var = $prompt['var'])) {
				// If we lazy loaded a sub dictionary on the last attempt, clear it
				if(DevblocksPlatform::strEndsWith($var, '_id'))
					$dict->scrubKeys(substr($var, 0, -2));
				
				// Prompt-specific options
				switch(@$prompt['action']) {
					case 'prompt.chooser':
						if(!DevblocksPlatform::strEndsWith($var, '_id') || !isset($prompt['context']))
							break;
							
						$dict->set(substr($var,0,-2) . '_context', $prompt['context']);
						break;
						
					case 'prompt.file':
						if(!DevblocksPlatform::strEndsWith($var, '_id'))
							break;
							
						$dict->set(substr($var,0,-2) . '_context', CerberusContexts::CONTEXT_ATTACHMENT);
						break;
				}
				
				if(false != (@$format_tpl = $prompt['format'])) {
					$var_message = $tpl_builder->build($format_tpl, $dict);
					$dict->set($var, $var_message);
				} else {
					$dict->set($var, $message);
				}
				
				if(false != (@$validate_tpl = $prompt['validate'])) {
					$validation_result = trim($tpl_builder->build($validate_tpl, $dict));
					
					if(!empty($validation_result)) {
						// Synthesize a response
						$actions[] = [
							'_action' => 'message.send',
							'message' => $validation_result,
							'format' => '',
							'delay_ms' => '1000',
						];
						
						// Replay the last action node containing the prompt
						array_splice($resume_path,-1,1);
					}
				}
			}
			unset($interaction->session_data['_prompt']);
		}
		
		return true;
	}
	
	function exportToJson($root_id=0) {
		if(null == ($event = $this->getEvent()))
			return;
		
		$ptrs = [
			'0' => [
				'nodes' => [],
			],
		];
		
		$tree_data = $this->getDecisionTreeData();
		
		$nodes = $tree_data['nodes'];
		$depths = $tree_data['depths'];
		
		$root = null;
		
		$statuses = [
			0 => 'live',
			1 => 'disabled',
			2 => 'simulator',
		];
		
		foreach(array_keys($depths) as $node_id) {
			$node = $nodes[$node_id]; /* @var $node Model_DecisionNode */
			
			$ptrs[$node->id] = array(
				'type' => $node->node_type,
				'title' => $node->title,
				'status' => $statuses[$node->status_id],
			);
			
			if(!empty($node->params_json))
				$ptrs[$node->id]['params'] = json_decode($node->params_json, true);
			
			$parent =& $ptrs[$node->parent_id];
			
			if(!isset($parent['nodes']))
				$parent['nodes'] = [];
			
			$ptr =& $ptrs[$node->id];
			
			if($node->id == $root_id) {
				$root = [];
				$root[] =& $ptr;
			}
			
			$parent['nodes'][] =& $ptr;
		}
		
		$export_type = 'behavior_fragment';
		
		if(!$root_id || is_null($root)) {
			$root = $ptrs[0]['nodes'];
			$export_type = 'behavior';
		}
		
		$array = array(
			$export_type => array(
				'uid' => 'behavior_'.$this->id,
				'title' => $this->title,
				'uri' => null,
				'is_disabled' => $this->is_disabled ? true : false,
				'is_private' => $this->is_private ? true : false,
				'priority' => $this->priority,
				'event' => array(
					'key' => $this->event_point,
					'label' => $event->manifest->name,
				),
			),
		);
		
		if($this->uri) {
			$array[$export_type]['uri'] = $this->uri;
		} else {
			unset($array[$export_type]['uri']);
		}
		
		if(isset($this->event_params) && !empty($this->event_params))
			$array[$export_type]['event']['params'] = $this->event_params;
		
		if(!empty($this->variables))
			$array[$export_type]['variables'] = $this->variables;
		
		if($root) {
			$array[$export_type]['nodes'] = $root;
		} else {
			$array[$export_type]['nodes'] = [];
		}
		
		return DevblocksPlatform::strFormatJson($array);
	}
};

class View_TriggerEvent extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'trigger';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = DevblocksPlatform::translateCapitalized('common.behaviors');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_TriggerEvent::UPDATED_AT;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_TriggerEvent::EVENT_POINT,
			SearchFields_TriggerEvent::BOT_ID,
			SearchFields_TriggerEvent::URI,
			SearchFields_TriggerEvent::PRIORITY,
			SearchFields_TriggerEvent::UPDATED_AT,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_TriggerEvent::VIRTUAL_BOT_SEARCH,
			SearchFields_TriggerEvent::VIRTUAL_CONTEXT_LINK,
			SearchFields_TriggerEvent::VIRTUAL_USABLE_BY,
		));
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_TriggerEvent::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_TriggerEvent');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null, &$total=null) {
		return $this->_getDataAsObjects('DAO_TriggerEvent', $ids, $total);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_TriggerEvent', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Fields
				case SearchFields_TriggerEvent::EVENT_POINT:
				case SearchFields_TriggerEvent::IS_DISABLED:
				case SearchFields_TriggerEvent::IS_PRIVATE:
				case SearchFields_TriggerEvent::PRIORITY:
				case SearchFields_TriggerEvent::BOT_ID:
					$pass = true;
					break;
					
				// Virtuals
				case SearchFields_TriggerEvent::VIRTUAL_CONTEXT_LINK:
				case SearchFields_TriggerEvent::VIRTUAL_HAS_FIELDSET:
				case SearchFields_TriggerEvent::VIRTUAL_WATCHERS:
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
		$context = CerberusContexts::CONTEXT_BEHAVIOR;

		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_TriggerEvent::BOT_ID:
			case SearchFields_TriggerEvent::EVENT_POINT:
				$label_map = function(array $values) use ($column) {
					return SearchFields_TriggerEvent::getLabelsForKeyValues($column, $values);
				};
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map);
				break;
				
			case SearchFields_TriggerEvent::IS_DISABLED:
			case SearchFields_TriggerEvent::IS_PRIVATE:
				$counts = $this->_getSubtotalCountForBooleanColumn($context, $column);
				break;
				
			case SearchFields_TriggerEvent::PRIORITY:
				$counts = $this->_getSubtotalCountForNumberColumn($context, $column);
				break;
			
			case SearchFields_TriggerEvent::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;

			case SearchFields_TriggerEvent::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
				
			case SearchFields_TriggerEvent::VIRTUAL_WATCHERS:
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
		$search_fields = SearchFields_TriggerEvent::getFields();
		
		$event_extensions = DevblocksPlatform::getExtensions('devblocks.event', false);
		DevblocksPlatform::sortObjects($event_extensions, 'name');
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_TriggerEvent::TITLE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'bot' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_TriggerEvent::VIRTUAL_BOT_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_BOT, 'q' => ''],
					]
				),
			'bot.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_TriggerEvent::BOT_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_BOT, 'q' => ''],
					]
				),
			'disabled' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => array('param_key' => SearchFields_TriggerEvent::IS_DISABLED),
				),
			'event' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_TriggerEvent::EVENT_POINT),
					'suggester' => [
						'type' => 'autocomplete',
						'query' => 'type:worklist.subtotals of:behaviors by:event~25 query:(event:*{{term}}*) format:dictionaries',
						'key' => 'event',
						'limit' => 25,
					]
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_TriggerEvent::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_BEHAVIOR],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_TriggerEvent::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_BEHAVIOR, 'q' => ''],
					]
				),
			'priority' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_TriggerEvent::PRIORITY),
				),
			'private' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => array('param_key' => SearchFields_TriggerEvent::IS_PRIVATE),
				),
			'name' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_TriggerEvent::TITLE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
					'suggester' => [
						'type' => 'autocomplete',
						'query' => 'type:worklist.subtotals of:behavior by:name~25 query:(name:*{{term}}*) format:dictionaries',
						'key' => 'name',
						'limit' => 25,
					]
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_TriggerEvent::UPDATED_AT),
				),
			'uri' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_TriggerEvent::URI),
					'suggester' => [
						'type' => 'autocomplete',
						'query' => 'type:worklist.records of:behaviors query:(uri:*{{term}}* limit:25) format:dictionaries',
						'key' => 'uri',
						'limit' => 25,
					]
				),
			'usableBy.bot' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_TriggerEvent::VIRTUAL_USABLE_BY),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_BOT, 'q' => ''],
					]
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_TriggerEvent::VIRTUAL_CONTEXT_LINK);

		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_BEHAVIOR, $fields, null);
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		ksort($fields);
		
		return $fields;
	}	
	
	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			case 'bot':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_TriggerEvent::VIRTUAL_BOT_SEARCH);
				
			case 'fieldset':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, '*_has_fieldset');
				
			case 'usableBy.bot':
				$oper = $value = null;
				CerbQuickSearchLexer::getOperStringFromTokens($tokens, $oper, $value);
				$bot_id = intval($value);
				
				return new DevblocksSearchCriteria(
					SearchFields_TriggerEvent::VIRTUAL_USABLE_BY,
					DevblocksSearchCriteria::OPER_CUSTOM,
					['context' => CerberusContexts::CONTEXT_BOT, 'id' => $bot_id]
				);
				
			default:
				$search_fields = $this->getQuickSearchFields();
				return DevblocksSearchCriteria::getParamFromQueryFieldTokens($field, $tokens, $search_fields);
		}
	}
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Bots
		$bots = DAO_Bot::getAll();
		$tpl->assign('bots', $bots);
		
		// Events
		$events = Extension_DevblocksEvent::getAll(false);
		$tpl->assign('events', $events);
		
		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_BEHAVIOR);
		$tpl->assign('custom_fields', $custom_fields);

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/bot/behavior/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteriaParam($param) {
		$field = $param->field;

		switch($field) {
			case SearchFields_TriggerEvent::EVENT_POINT:
				$events = Extension_DevblocksEvent::getAll(false);
				$labels = array_column(json_decode(json_encode($events), true), 'name', 'id');
				parent::_renderCriteriaParamString($param, $labels);
				break;
				
			case SearchFields_TriggerEvent::IS_DISABLED:
			case SearchFields_TriggerEvent::IS_PRIVATE:
				parent::_renderCriteriaParamBoolean($param);
				break;
				
			case SearchFields_TriggerEvent::BOT_ID:
				$bots = DAO_Bot::getAll();
				$labels = array_column(json_decode(json_encode($bots), true), 'name', 'id');
				parent::_renderCriteriaParamString($param, $labels);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_TriggerEvent::VIRTUAL_BOT_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.bot')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
			
			case SearchFields_TriggerEvent::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;

			case SearchFields_TriggerEvent::VIRTUAL_USABLE_BY:
				if(!is_array($param->value) || !isset($param->value['context']))
					return;
				
				switch($param->value['context']) {
					case CerberusContexts::CONTEXT_BOT:
						if(false == ($bot = DAO_Bot::get($param->value['id']))) {
							$bot_name = '(invalid bot)';
						} else {
							$bot_name = $bot->name;
						}
						
						echo sprintf("Usable by %s <b>%s</b>",
							DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translate('common.bot', DevblocksPlatform::TRANSLATE_LOWER)),
							DevblocksPlatform::strEscapeHtml($bot_name)
						);
						break;
					
					case CerberusContexts::CONTEXT_WORKER:
						if(false == ($worker = DAO_Worker::get($param->value['id']))) {
							$worker_name = '(invalid worker)';
						} else {
							$worker_name = $worker->getName();
						}
						
						echo sprintf("Usable by %s <b>%s</b>",
							DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translate('common.worker', DevblocksPlatform::TRANSLATE_LOWER)),
							DevblocksPlatform::strEscapeHtml($worker_name)
						);
						break;
				}
				break;
			
			case SearchFields_TriggerEvent::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
			
			case SearchFields_TriggerEvent::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}
	
	function getFields() {
		return SearchFields_TriggerEvent::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_TriggerEvent::TITLE:
			case SearchFields_TriggerEvent::URI:
			case SearchFields_TriggerEvent::EVENT_POINT:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_TriggerEvent::ID:
			case SearchFields_TriggerEvent::PRIORITY:
			case SearchFields_TriggerEvent::BOT_ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_TriggerEvent::UPDATED_AT:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;

			case SearchFields_TriggerEvent::IS_DISABLED:
			case SearchFields_TriggerEvent::IS_PRIVATE:
				$bool = DevblocksPlatform::importGPC($_POST['bool'] ?? null, 'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
			
			case SearchFields_TriggerEvent::VIRTUAL_CONTEXT_LINK:
				$context_links = DevblocksPlatform::importGPC($_POST['context_link'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;

			case SearchFields_TriggerEvent::VIRTUAL_WATCHERS:
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

class Context_TriggerEvent extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek, IDevblocksContextAutocomplete { // IDevblocksContextImport
	const ID = CerberusContexts::CONTEXT_BEHAVIOR;
	const URI = 'behavior';
	
	static function isReadableByActor($models, $actor, $ignore_admins=false) {
		return CerberusContexts::isReadableByDelegateOwner($actor, CerberusContexts::CONTEXT_BEHAVIOR, $models, 'bot_owner_', $ignore_admins);
	}
	
	static function isWriteableByActor($models, $actor, $ignore_admins=false) {
		return CerberusContexts::isWriteableByDelegateOwner($actor, CerberusContexts::CONTEXT_BEHAVIOR, $models, 'bot_owner_', $ignore_admins);
	}
	
	static function isDeletableByActor($models, $actor, $ignore_admins=false) {
		return self::isWriteableByActor($models, $actor, $ignore_admins);
	}
	
	function autocomplete($term, $query=null) {
		$list = [];
		
		$context_ext = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_BEHAVIOR);
		
		$view = $context_ext->getSearchView('autocomplete_behavior');
		$view->renderLimit = 25;
		$view->renderPage = 0;
		$view->renderSortBy = SearchFields_TriggerEvent::UPDATED_AT;
		$view->renderSortAsc = false;
		$view->is_ephemeral = true;
		
		$view->addParamsWithQuickSearch($query, true);
		$view->addParam(new DevblocksSearchCriteria(SearchFields_TriggerEvent::TITLE,DevblocksSearchCriteria::OPER_LIKE,'%'.$term.'%'));
		
		list($results,) = $view->getData();
		
		foreach($results AS $row){
			$entry = new stdClass();
			$entry->label = $row[SearchFields_TriggerEvent::TITLE];
			$entry->value = $row[SearchFields_TriggerEvent::ID];
			$list[] = $entry;
		}
		
		return $list;
	}
	
	function getRandom() {
		return 0; // DAO_TriggerEvent::random();
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=trigger_event&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		if(is_null($model))
			$model = new Model_TriggerEvent();
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_BEHAVIOR,
			],
		);
		
		$properties['bot_id'] = array(
			'label' => mb_ucfirst($translate->_('common.bot')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->bot_id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_BOT,
			]
		);
		
		$properties['event_point'] = array(
			'label' => mb_ucfirst($translate->_('common.event')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->getEvent()->manifest->name ?? '',
		);
		
		$properties['updated'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_at,
		);
		
		$properties['is_disabled'] = array(
			'label' => mb_ucfirst($translate->_('common.disabled')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $model->is_disabled,
		);
		
		$properties['is_private'] = array(
			'label' => mb_ucfirst($translate->_('common.is_private')),
			'type' => Model_CustomField::TYPE_CHECKBOX,
			'value' => $model->is_private,
		);
		
		$properties['priority'] = array(
			'label' => mb_ucfirst($translate->_('common.priority')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->priority,
		);
		
		$properties['id'] = array(
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		);
		
		$properties['uri'] = array(
			'label' => DevblocksPlatform::translate('common.uri'),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->uri,
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		if(null == ($trigger_event = DAO_TriggerEvent::get($context_id)))
			return [];
		
		$url = $this->profileGetUrl($context_id);
		$label = $trigger_event->uri ?: $trigger_event->title;
		
		$friendly = DevblocksPlatform::strToPermalink($label);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $trigger_event->id,
			'name' => $trigger_event->title,
			'permalink' => $url,
			'updated' => $trigger_event->updated_at,
		);
	}
	
	function getDefaultProperties() {
		return array(
			'bot__label',
			'id',
			'uri',
			'priority',
			'updated_at',
			'is_disabled',
		);
	}
	
	function getContextIdFromAlias($alias) {
		// Is it a URI?
		if(false != ($model = DAO_TriggerEvent::getByUri($alias)))
			return $model->id;
		
		return null;
	}
	
	function getContext($trigger_event, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Behavior:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_BEHAVIOR);

		// Polymorph
		if(is_numeric($trigger_event)) {
			$trigger_event = DAO_TriggerEvent::get($trigger_event);
		} elseif($trigger_event instanceof Model_TriggerEvent) {
			// It's what we want already.
		} elseif(is_array($trigger_event)) {
			$trigger_event = Cerb_ORMHelper::recastArrayToModel($trigger_event, 'Model_TriggerEvent');
		} else {
			$trigger_event = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'event_point' => $prefix.$translate->_('common.event'),
			'event_point_name' => $prefix.$translate->_('common.event'),
			'id' => $prefix.$translate->_('common.id'),
			'is_disabled' => $prefix.$translate->_('dao.trigger_event.is_disabled'),
			'is_private' => $prefix.$translate->_('common.is_private'),
			'name' => $prefix.$translate->_('common.name'),
			'priority' => $prefix.$translate->_('common.priority'),
			'updated_at' => $prefix.$translate->_('common.updated'),
			'uri' => $prefix.$translate->_('common.uri'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'event_point' => Model_CustomField::TYPE_SINGLE_LINE,
			'event_point_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'is_disabled' => Model_CustomField::TYPE_CHECKBOX,
			'is_private' => Model_CustomField::TYPE_CHECKBOX,
			'name' => Model_CustomField::TYPE_SINGLE_LINE,
			'priority' => Model_CustomField::TYPE_NUMBER,
			'updated_at' => Model_CustomField::TYPE_DATE,
			'uri' => Model_CustomField::TYPE_SINGLE_LINE,
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
		
		$token_values['_context'] = Context_TriggerEvent::ID;
		$token_values['_type'] = Context_TriggerEvent::URI;
		
		$token_values['_types'] = $token_types;
		
		if($trigger_event) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $trigger_event->title;
			$token_values['event_point'] = $trigger_event->event_point;
			$token_values['id'] = $trigger_event->id;
			$token_values['is_disabled'] = $trigger_event->is_disabled;
			$token_values['is_private'] = $trigger_event->is_private;
			$token_values['name'] = $trigger_event->title;
			$token_values['priority'] = $trigger_event->priority;
			$token_values['updated_at'] = $trigger_event->updated_at;
			$token_values['uri'] = $trigger_event->uri;
			
			$token_values['bot_id'] = $trigger_event->bot_id;
			
			// Friendly names
			
			if(null != ($event = $trigger_event->getEvent())) {
				$token_values['event_point_name'] = $event->manifest->name;
			}
			
			// Variables
			$token_values['variables'] = $trigger_event->variables;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($trigger_event, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=behavior&id=%d-%s",$trigger_event->id, DevblocksPlatform::strToPermalink($trigger_event->title)), true);
		}
		
		// Bot
		$merge_token_labels = [];
		$merge_token_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_BOT, null, $merge_token_labels, $merge_token_values, '', true);

			CerberusContexts::merge(
				'bot_',
				$prefix.'Bot:',
				$merge_token_labels,
				$merge_token_values,
				$token_labels,
				$token_values
			);
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'bot_id' => DAO_TriggerEvent::BOT_ID,
			'event_point' => DAO_TriggerEvent::EVENT_POINT,
			'id' => DAO_TriggerEvent::ID,
			'is_disabled' => DAO_TriggerEvent::IS_DISABLED,
			'is_private' => DAO_TriggerEvent::IS_PRIVATE,
			'links' => '_links',
			'name' => DAO_TriggerEvent::TITLE,
			'priority' => DAO_TriggerEvent::PRIORITY,
			'updated_at' => DAO_TriggerEvent::UPDATED_AT,
			'uri' => DAO_TriggerEvent::URI,
		];
	}
	
	function getKeyMeta($with_dao_fields=true) {
		$keys = parent::getKeyMeta($with_dao_fields);
		
		$keys['bot_id']['notes'] = "[Bot](/docs/records/types/bot/)";
		$keys['event_point']['notes'] = 'The event of the behavior';
		$keys['is_disabled']['notes'] = 'Is this behavior disabled?';
		$keys['is_private']['notes'] = 'Is this behavior only visible to the parent bot?';
		$keys['name']['notes'] = "The behavior's name";
		$keys['priority']['notes'] = "Any positive number; `0` is highest priority";
		
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
		
		$context = CerberusContexts::CONTEXT_BEHAVIOR;
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
		$view->name = 'Behavior';
		/*
		$view->addParams(array(
			SearchFields_TriggerEvent::UPDATED_AT => new DevblocksSearchCriteria(SearchFields_TriggerEvent::UPDATED_AT,'=',0),
		), true);
		*/
		$view->renderSortBy = SearchFields_TriggerEvent::UPDATED_AT;
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
		$view->name = 'Behavior';
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = [
				new DevblocksSearchCriteria(SearchFields_TriggerEvent::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			];
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = CerberusContexts::CONTEXT_BEHAVIOR;
		
		$tpl->assign('view_id', $view_id);
		
		$model = null;
		
		if($context_id) {
			if(is_numeric($context_id)) {
				if(!($model = DAO_TriggerEvent::get($context_id)))
					DevblocksPlatform::dieWithHttpError(null, 404);
				
			} else {
				if(!($model = DAO_TriggerEvent::getByUri($context_id)))
					DevblocksPlatform::dieWithHttpError(null, 404);
			}
		}
		
		if(!($model instanceof Model_TriggerEvent))
			$model = new Model_TriggerEvent();
		
		$tpl->assign('model', $model);
		
		if(!$context_id || $edit) {
			if($model) {
				if(!Context_TriggerEvent::isWriteableByActor($model, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
			}
			
			// Custom Fields
			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);
	
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
			
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);
			
			$bots = DAO_Bot::getAll();
			$tpl->assign('bots', $bots);
			
			if(!empty($model)) {
				$ext = Extension_DevblocksEvent::get($model->event_point, true);
				$tpl->assign('ext', $ext);
				
				if(isset($bots[$model->bot_id]))
					$tpl->assign('bot', $bots[$model->bot_id]);
			}
			
			// Check view for defaults by filter
			if(($view = C4_AbstractViewLoader::getView($view_id))) {
				$filters = $view->findParam(SearchFields_TriggerEvent::BOT_ID, $view->getParams());
				
				if(($filter = array_shift($filters))) {
					$bot_id = is_array($filter->value) ? array_shift($filter->value) : $filter->value;
					
					if(array_key_exists($bot_id, $bots)) {
						$bot = $bots[$bot_id];
						$tpl->assign('bot', $bot);
						
						$events = Extension_DevblocksEvent::getByContext($bot->owner_context, false);
						
						// Filter the available events by VA
						$events = $bot->filterEventsByAllowed($events);
						
						// Menu
						$labels = [];
						foreach($events as $event) { /* @var $event DevblocksExtensionManifest */
							if($event->params['deprecated'] ?? null)
								continue;
							
							if(!($label = ($event->params['menu_key'] ?? null)))
								$label = $event->name;
							
							$labels[$event->id] = $label;
						}
						
						$events_menu = Extension_DevblocksContext::getPlaceholderTree($labels, ':', ' ', false);
						
						$tpl->assign('events', $events);
						$tpl->assign('events_menu', $events_menu);
					}
				}
			}
			
			$variable_types = DAO_TriggerEvent::getVariableTypes();
			$tpl->assign('variable_types', $variable_types);
			
			$variables_menu = Extension_DevblocksContext::getPlaceholderTree($variable_types, ':', '');
			$tpl->assign('variables_menu', $variables_menu);
			
			$context_mfts = Extension_DevblocksContext::getAll(false, ['va_variable']);
			$tpl->assign('context_mfts', $context_mfts);
			
			// Library
			if(!$context_id) {
				$packages = DAO_PackageLibrary::getByPoint('behavior');
				$tpl->assign('packages', $packages);
			}
			
			$tpl->display('devblocks:cerberusweb.core::internal/bot/behavior/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $model);
		}
		
	}
};
