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

class DAO_ContextActivityLog extends Cerb_ORMHelper {
	const ACTIVITY_POINT = 'activity_point';
	const ACTOR_CONTEXT = 'actor_context';
	const ACTOR_CONTEXT_ID = 'actor_context_id';
	const CREATED = 'created';
	const ENTRY_JSON = 'entry_json';
	const ID = 'id';
	const TARGET_CONTEXT = 'target_context';
	const TARGET_CONTEXT_ID = 'target_context_id';
	
	private function __construct() {}
	
	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField(self::ACTIVITY_POINT)
			->string()
			->setMaxLength(128)
			->setRequired(true)
			;
		$validation
			->addField(self::ACTOR_CONTEXT)
			->context()
			->setRequired(true)
			;
		$validation
			->addField(self::ACTOR_CONTEXT_ID)
			->id()
			->setRequired(true)
			;
		$validation
			->addField(self::CREATED)
			->timestamp()
			;
		$validation
			->addField(self::ENTRY_JSON)
			->string()
			->setMaxLength(16777215)
			->setRequired(true)
			;
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		$validation
			->addField(self::TARGET_CONTEXT)
			->context()
			->setRequired(true)
			;
		$validation
			->addField(self::TARGET_CONTEXT_ID)
			->id()
			->setRequired(true)
			;
		$validation
			->addField('_links')
			->string()
			->setMaxLength(65535)
			;
			
		return $validation->getFields();
	}
	
	static function create($fields) {
		@$target_context = $fields[DAO_ContextActivityLog::TARGET_CONTEXT];
		@$target_context_id = $fields[DAO_ContextActivityLog::TARGET_CONTEXT_ID];
		
		if(is_null($target_context))
			$fields[DAO_ContextActivityLog::TARGET_CONTEXT] = '';
		
		if(is_null($target_context_id))
			$fields[DAO_ContextActivityLog::TARGET_CONTEXT_ID] = 0;
		
		if(!isset($fields[self::CREATED]))
			$fields[self::CREATED] = time();
		
		$id = parent::_insert('context_activity_log', $fields);
		
		CerberusContexts::checkpointCreations(CerberusContexts::CONTEXT_ACTIVITY_LOG, [$id]);
		CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_ACTIVITY_LOG, [$id]);
		DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_ACTIVITY_LOG, [$id]);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		$context = CerberusContexts::CONTEXT_ACTIVITY_LOG;
		self::_updateAbstract($context, $ids, $fields);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;

			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_ACTIVITY_LOG, $batch_ids);
			}

			// Make changes
			parent::_update($batch_ids, 'context_activity_log', $fields);
			
			// Send events
			if($check_deltas) {
				
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.context_activity_log.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_ACTIVITY_LOG, $batch_ids);
			}
		}
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_ACTIVITY_LOG;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		if(!CerberusContexts::isActorAnAdmin($actor)) {
			$error = DevblocksPlatform::translate('error.core.no_acl.admin');
			return false;
		}
		
		return true;
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_ContextActivityLog[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::services()->database();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, activity_point, actor_context, actor_context_id, target_context, target_context_id, created, entry_json ".
			"FROM context_activity_log ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->QueryReader($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_ContextActivityLog	 */
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
	 * @param string $actor_context
	 * @param integer $actor_context_id
	 * @return Model_ContextActivityLog|NULL
	 */
	static function getLatestEntriesByActor($actor_context, $actor_context_id, $limit = 1, $only_activities = [], $since = 0) {
		// Filter to only this worker
		$sql = sprintf("%s = %s AND %s = %d",
			self::escape(DAO_ContextActivityLog::ACTOR_CONTEXT),
			self::qstr($actor_context),
			self::escape(DAO_ContextActivityLog::ACTOR_CONTEXT_ID),
			$actor_context_id
		);
		
		// Are we're limiting our search to only some activities?
		if(is_array($only_activities) && !empty($only_activities)) {
			$filter_activities = array_map(function($v) {
				return Cerb_ORMHelper::qstr($v);
			}, $only_activities);
			
			$sql .= sprintf(" AND %s IN (%s)",
				self::escape(DAO_ContextActivityLog::ACTIVITY_POINT),
				implode(',', $filter_activities)
			);
		}
		
		if($since) {
			$sql .= sprintf(" AND %s >= %d",
				self::escape(DAO_ContextActivityLog::CREATED),
				$since
			);
		}
		
		// Grab the entries
		$results = self::getWhere(
			$sql,
			DAO_ContextActivityLog::CREATED,
			false,
			max(1, intval($limit))
		);
		
		if(is_array($results) && $results) {
			if(1 == $limit) {
				return array_shift($results);
			} else {
				return $results;
			}
		}
		
		return [];
	}
	
	/**
	 * 
	 * @param string $target_context
	 * @param integer $target_context_id
	 * @return Model_ContextActivityLog|NULL
	 */
	static function getLatestEntriesByTarget($target_context, $target_context_id, $limit = 1, $only_activities = [], $since = 0) {
		// Filter to only this worker
		$sql = sprintf("%s = %s AND %s = %d",
			self::escape(DAO_ContextActivityLog::TARGET_CONTEXT),
			self::qstr($target_context),
			self::escape(DAO_ContextActivityLog::TARGET_CONTEXT_ID),
			$target_context_id
		);
		
		// Are we're limiting our search to only some activities?
		if(is_array($only_activities) && !empty($only_activities)) {
			$filter_activities = array_map(function($v) {
				return Cerb_ORMHelper::qstr($v);
			}, $only_activities);
			
			$sql .= sprintf(" AND %s IN (%s)",
				self::escape(DAO_ContextActivityLog::ACTIVITY_POINT),
				implode(',', $filter_activities)
			);
		}
		
		if($since) {
			$sql .= sprintf(" AND %s >= %d",
				self::escape(DAO_ContextActivityLog::CREATED),
				$since
			);
		}
		
		// Grab the entries
		$results = self::getWhere(
			$sql,
			DAO_ContextActivityLog::CREATED,
			false,
			max(1, intval($limit))
		);
		
		if(is_array($results) && $results) {
			if(1 == $limit) {
				return array_shift($results);
			} else {
				return $results;
			}
		}
		
		return [];
	}
	
	/**
	 * @param mysqli_result|false $rs
	 * @return Model_ContextActivityLog[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_ContextActivityLog();
			$object->id = $row['id'];
			$object->activity_point = $row['activity_point'];
			$object->actor_context = $row['actor_context'];
			$object->actor_context_id = $row['actor_context_id'];
			$object->target_context = $row['target_context'];
			$object->target_context_id = $row['target_context_id'];
			$object->created = $row['created'];
			$object->entry_json = $row['entry_json'];
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}

	static function random() {
		return self::_getRandom('context_activity_log');
	}
	
	static public function countByTarget($from_context, $from_context_id) {
		$db = DevblocksPlatform::services()->database();
		return $db->GetOneReader(sprintf("SELECT count(*) FROM context_activity_log ".
			"WHERE target_context = %s AND target_context_id = %d",
			$db->qstr($from_context),
			$from_context_id
		));
	}
	
	static function delete($ids) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($ids)) $ids = [$ids];
		$ids = DevblocksPlatform::sanitizeArray($ids, 'int');
		
		if(empty($ids)) return false;
		
		$ids_list = implode(',', self::qstrArray($ids));
		
		$db->ExecuteMaster(sprintf("DELETE FROM context_activity_log WHERE id IN (%s)", $ids_list));
		
		return true;
	}
	
	static function deleteByContext($context, $context_ids) {
		if(!is_array($context_ids))
			$context_ids = array($context_ids);
		
		if(empty($context_ids))
			return;
			
		$db = DevblocksPlatform::services()->database();
		
		$db->ExecuteMaster(sprintf("DELETE FROM context_activity_log WHERE (actor_context = %s AND actor_context_id IN (%s)) OR (target_context = %s AND target_context_id IN (%s)) ",
			$db->qstr($context),
			implode(',', $context_ids),
			$db->qstr($context),
			implode(',', $context_ids)
		));
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_ContextActivityLog::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_ContextActivityLog', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"context_activity_log.id as %s, ".
			"context_activity_log.activity_point as %s, ".
			"context_activity_log.actor_context as %s, ".
			"context_activity_log.actor_context_id as %s, ".
			"context_activity_log.target_context as %s, ".
			"context_activity_log.target_context_id as %s, ".
			"context_activity_log.created as %s, ".
			"context_activity_log.entry_json as %s ",
				SearchFields_ContextActivityLog::ID,
				SearchFields_ContextActivityLog::ACTIVITY_POINT,
				SearchFields_ContextActivityLog::ACTOR_CONTEXT,
				SearchFields_ContextActivityLog::ACTOR_CONTEXT_ID,
				SearchFields_ContextActivityLog::TARGET_CONTEXT,
				SearchFields_ContextActivityLog::TARGET_CONTEXT_ID,
				SearchFields_ContextActivityLog::CREATED,
				SearchFields_ContextActivityLog::ENTRY_JSON
			);
			
		$join_sql = "FROM context_activity_log ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_ContextActivityLog');
	
		return array(
			'primary_table' => 'context_activity_log',
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
			SearchFields_ContextActivityLog::ID,
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

class SearchFields_ContextActivityLog extends DevblocksSearchFields {
	const ID = 'c_id';
	const ACTIVITY_POINT = 'c_activity_point';
	const ACTOR_CONTEXT = 'c_actor_context';
	const ACTOR_CONTEXT_ID = 'c_actor_context_id';
	const TARGET_CONTEXT = 'c_target_context';
	const TARGET_CONTEXT_ID = 'c_target_context_id';
	const CREATED = 'c_created';
	const ENTRY_JSON = 'c_entry_json';
	
	const VIRTUAL_ACTOR = '*_actor';
	const VIRTUAL_TARGET = '*_target';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 'context_activity_log.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_ACTIVITY_LOG => new DevblocksSearchFieldContextKeys('context_activity_log.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::VIRTUAL_ACTOR:
			case self::VIRTUAL_TARGET:
				$context_field = match($param->field) {
					self::VIRTUAL_ACTOR => 'actor_context',
					self::VIRTUAL_TARGET => 'target_context',
				};
				
				// Handle nested quick search filters first
				if($param->operator == DevblocksSearchCriteria::OPER_CUSTOM) {
					list($alias, $query) = array_pad(explode(':', $param->value, 2), 2, null);
					
					if(empty($alias) || !($ext = Extension_DevblocksContext::getByAlias(str_replace('.', ' ', $alias), true)))
						return '-1';
					
					if(!($view = $ext->getTempView()))
						return '-1';
					
					$view->renderPage = 0;
					$view->addParamsWithQuickSearch($query, true);
					
					$params = $view->getParams();
					
					if(!($dao_class = $ext->getDaoClass()) || !class_exists($dao_class))
						return '-1';
					
					if(!($search_class = $ext->getSearchClass()) || !class_exists($search_class))
						return '-1';
					
					if(!($primary_key = $search_class::getPrimaryKey()))
						return '-1';
					
					$query_parts = $dao_class::getSearchQueryComponents(array(), $params);
					
					$query_parts['select'] = sprintf("SELECT %s ", $primary_key);
					
					$sql = 
						$query_parts['select']
						. $query_parts['join']
						. $query_parts['where']
						. $query_parts['sort']
						;
					
					// Performance: Resolve the worker subquery into a list of IDs
					if(CerberusContexts::isSameContext($ext->id, CerberusContexts::CONTEXT_WORKER)) {
						$db = DevblocksPlatform::services()->database();
						$results = $db->GetArrayReader($sql);
						$sql = '-1';
						
						if(is_array($results) && $results) {
							$sql = implode(
								',',
								DevblocksPlatform::sanitizeArray(array_column($results, 'id'), 'int')
							);
						}
					}
					
					return sprintf("%s = %s AND %s_id IN (%s) ",
						$context_field,
						Cerb_ORMHelper::qstr($ext->id),
						$context_field,
						$sql
					);
				}

				if(is_array($param->value)) {
					$wheres = [];
					foreach($param->value as $context_pair) {
						list($context, $context_id) = array_pad(explode(':', $context_pair), 2, null);
						if(!empty($context_id)) {
							$wheres[] = sprintf("(%s = %s AND %s_id = %d)",
								$context_field,
								Cerb_ORMHelper::qstr($context),
								$context_field,
								$context_id
							);
						} else {
							$wheres[] = sprintf("(%s = %s)",
								$context_field,
								Cerb_ORMHelper::qstr($context)
							);
						}
					}
				}
				
				if(!empty($wheres))
					return 
						($param->operator == DevblocksSearchCriteria::OPER_NIN ? 'NOT ' : '') .
						'(' . implode(' OR ', $wheres) . ') '
						;
				
				break;
				
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
			case 'actor':
				$field_actor_context = $search_fields[SearchFields_ContextActivityLog::ACTOR_CONTEXT];
				$field_actor_context_id = $search_fields[SearchFields_ContextActivityLog::ACTOR_CONTEXT_ID];
				
				return [
					'key_query' => $key,
					'key_select' => 'actor',
					'type' => DevblocksSearchCriteria::TYPE_CONTEXT,
					'sql_select' => sprintf("CONCAT_WS(':', %s.%s, %s.%s)",
						Cerb_ORMHelper::escape($field_actor_context->db_table),
						Cerb_ORMHelper::escape($field_actor_context->db_column),
						Cerb_ORMHelper::escape($field_actor_context_id->db_table),
						Cerb_ORMHelper::escape($field_actor_context_id->db_column)
					),
					'get_value_as_filter_callback' => parent::getValueAsFilterCallback()->link('actor'),
				];
				break;
				
			case 'actor.type':
				$search_field = $search_fields[SearchFields_ContextActivityLog::ACTOR_CONTEXT];
				
				return [
					'key_query' => $key,
					'key_select' => $search_field->token,
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'sql_select' => sprintf("%s.%s",
						Cerb_ORMHelper::escape($search_field->db_table),
						Cerb_ORMHelper::escape($search_field->db_column)
					),
					'get_value_as_filter_callback' => parent::getValueAsFilterCallback()->linkType('actor'),
				];
				break;
				
			case 'target':
				$field_target_context = $search_fields[SearchFields_ContextActivityLog::TARGET_CONTEXT];
				$field_target_context_id = $search_fields[SearchFields_ContextActivityLog::TARGET_CONTEXT_ID];
				
				return [
					'key_query' => $key,
					'key_select' => 'target',
					'type' => DevblocksSearchCriteria::TYPE_CONTEXT,
					'sql_select' => sprintf("CONCAT_WS(':', %s.%s, %s.%s)",
						Cerb_ORMHelper::escape($field_target_context->db_table),
						Cerb_ORMHelper::escape($field_target_context->db_column),
						Cerb_ORMHelper::escape($field_target_context_id->db_table),
						Cerb_ORMHelper::escape($field_target_context_id->db_column)
					),
					'get_value_as_filter_callback' => parent::getValueAsFilterCallback()->link('target'),
				];
				break;
				
			case 'target.type':
				$search_field = $search_fields[SearchFields_ContextActivityLog::TARGET_CONTEXT];
				
				return [
					'key_query' => $key,
					'key_select' => $search_field->token,
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'sql_select' => sprintf("%s.%s",
						Cerb_ORMHelper::escape($search_field->db_table),
						Cerb_ORMHelper::escape($search_field->db_column)
					),
					'get_value_as_filter_callback' => parent::getValueAsFilterCallback()->linkType('target'),
				];
				break;
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_ContextActivityLog::ACTIVITY_POINT:
				$strings = [];
				
				$activities = DevblocksPlatform::getActivityPointRegistry();
				$translate = DevblocksPlatform::getTranslationService();
				
				if(is_array($values))
				foreach($values as $v) {
					$string = $v;
					if(isset($activities[$v])) {
						$string_id = $activities[$v]['params']['label_key'] ?? null;
						if(!empty($string_id))
							$string = $translate->_($string_id);
					}
					
					$strings[$v] = $string;
				}
				
				return $strings;
				break;
			
			case SearchFields_ContextActivityLog::ACTOR_CONTEXT:
			case SearchFields_ContextActivityLog::TARGET_CONTEXT:
				return self::_getLabelsForKeyContextValues($values);
				break;
				
			case 'actor':
			case 'target':
				return self::_getLabelsForKeyContextAndIdValues($values);
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
			self::ID => new DevblocksSearchField(self::ID, 'context_activity_log', 'id', $translate->_('common.id'), null, true),
			self::ACTIVITY_POINT => new DevblocksSearchField(self::ACTIVITY_POINT, 'context_activity_log', 'activity_point', $translate->_('dao.context_activity_log.activity_point'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::ACTOR_CONTEXT => new DevblocksSearchField(self::ACTOR_CONTEXT, 'context_activity_log', 'actor_context', $translate->_('dao.context_activity_log.actor_context'), null, true),
			self::ACTOR_CONTEXT_ID => new DevblocksSearchField(self::ACTOR_CONTEXT_ID, 'context_activity_log', 'actor_context_id', $translate->_('dao.context_activity_log.actor_context_id'), null, true),
			self::TARGET_CONTEXT => new DevblocksSearchField(self::TARGET_CONTEXT, 'context_activity_log', 'target_context', $translate->_('dao.context_activity_log.target_context'), null, true),
			self::TARGET_CONTEXT_ID => new DevblocksSearchField(self::TARGET_CONTEXT_ID, 'context_activity_log', 'target_context_id', $translate->_('dao.context_activity_log.target_context_id'), null, true),
			self::CREATED => new DevblocksSearchField(self::CREATED, 'context_activity_log', 'created', $translate->_('common.created'), Model_CustomField::TYPE_DATE, true),
			self::ENTRY_JSON => new DevblocksSearchField(self::ENTRY_JSON, 'context_activity_log', 'entry_json', $translate->_('dao.context_activity_log.entry'), null, false),
				
			self::VIRTUAL_ACTOR => new DevblocksSearchField(self::VIRTUAL_ACTOR, '*', 'actor', $translate->_('common.actor'), null, false),
			self::VIRTUAL_TARGET => new DevblocksSearchField(self::VIRTUAL_TARGET, '*', 'target', $translate->_('common.target'), null, false),
		);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_ContextActivityLog extends DevblocksRecordModel {
	public $id;
	public $activity_point;
	public $actor_context;
	public $actor_context_id;
	public $target_context;
	public $target_context_id;
	public $created;
	public $entry_json;
};

class View_ContextActivityLog extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'context_activity_log';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Activity Log';
		$this->renderLimit = 10;
		$this->renderSortBy = SearchFields_ContextActivityLog::CREATED;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_ContextActivityLog::CREATED,
			SearchFields_ContextActivityLog::ID,
		);
		$this->addColumnsHidden(array(
			SearchFields_ContextActivityLog::ACTOR_CONTEXT_ID,
			SearchFields_ContextActivityLog::TARGET_CONTEXT_ID,
		));
		
		$this->doResetCriteria();
	}
	
	/**
	 * @return array|false
	 * @throws Exception_DevblocksDatabaseQueryTimeout
	 */
	protected function _getData() {
		return DAO_ContextActivityLog::search(
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
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_ContextActivityLog');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null, &$total=null) {
		return $this->_getDataAsObjects('DAO_ContextActivityLog', $ids, $total);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_ContextActivityLog', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);

		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// DAO
				case SearchFields_ContextActivityLog::ACTIVITY_POINT:
					$pass = true;
					break;
					
				case SearchFields_ContextActivityLog::VIRTUAL_ACTOR:
				case SearchFields_ContextActivityLog::VIRTUAL_TARGET:
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
		$counts = array();
		$fields = $this->getFields();
		$context = CerberusContexts::CONTEXT_ACTIVITY_LOG;

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_ContextActivityLog::ACTIVITY_POINT:
				$label_map = array();
				$translate = DevblocksPlatform::getTranslationService();
				
				$activities = DevblocksPlatform::getActivityPointRegistry();
				if(is_array($activities))
				foreach($activities as $k => $data) {
					$string_id = $data['params']['label_key'] ?? null;
					if(!empty($string_id)) {
						$label_map[$k] = $translate->_($string_id);
					}
				}
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map, 'in', 'options[]');
				break;
				
			case SearchFields_ContextActivityLog::VIRTUAL_ACTOR:
				$counts = $this->_getSubtotalCountForContextAndIdColumns($context, $column, DAO_ContextActivityLog::ACTOR_CONTEXT, DAO_ContextActivityLog::ACTOR_CONTEXT_ID);
				break;
				
			case SearchFields_ContextActivityLog::VIRTUAL_TARGET:
				$counts = $this->_getSubtotalCountForContextAndIdColumns($context, $column, DAO_ContextActivityLog::TARGET_CONTEXT, DAO_ContextActivityLog::TARGET_CONTEXT_ID);
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
		$search_fields = SearchFields_ContextActivityLog::getFields();
		
		$activities = array_map(function($e) { 
			return $e['params']['label_key'];
		}, DevblocksPlatform::getActivityPointRegistry());
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_ContextActivityLog::ACTIVITY_POINT, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PREFIX),
				),
			'activity' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_ContextActivityLog::ACTIVITY_POINT),
					'examples' => [
						['type' => 'list', 'values' => $activities],
					],
				),
			'created' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_ContextActivityLog::CREATED),
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_ContextActivityLog::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_ACTIVITY_LOG, 'q' => ''],
					]
				),
		);
		
		// Add dynamic actor.* and target.* filters
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('actor', $fields, 'search', SearchFields_ContextActivityLog::VIRTUAL_ACTOR);
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('target', $fields, 'search', SearchFields_ContextActivityLog::VIRTUAL_TARGET);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_ACTIVITY_LOG, $fields, null);
		
		// Add is_sortable
		
		$fields = self::_setSortableQuickSearchFields($fields, $search_fields);
		
		// Sort by keys
		
		ksort($fields);
		
		return $fields;
	}	

	function getParamFromQuickSearchFieldTokens($field, $tokens) {
		switch($field) {
			default:
				if($field == 'actor' || DevblocksPlatform::strStartsWith($field, 'actor.'))
					return DevblocksSearchCriteria::getVirtualContextParamFromTokens($field, $tokens, 'actor', SearchFields_ContextActivityLog::VIRTUAL_ACTOR);
					
				if($field == 'target' || DevblocksPlatform::strStartsWith($field, 'target.'))
					return DevblocksSearchCriteria::getVirtualContextParamFromTokens($field, $tokens, 'target', SearchFields_ContextActivityLog::VIRTUAL_TARGET);
				
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

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::internal/activity_log/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_ContextActivityLog::VIRTUAL_ACTOR:
				$this->_renderVirtualContextLinks($param, 'Actor', 'Actors', 'Actor is');
				break;
			
			case SearchFields_ContextActivityLog::VIRTUAL_TARGET:
				$this->_renderVirtualContextLinks($param, 'Target', 'Targets', 'Target is');
				break;
		}
	}
	
	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_ContextActivityLog::ACTOR_CONTEXT:
			case SearchFields_ContextActivityLog::TARGET_CONTEXT:
				$strings = SearchFields_ContextActivityLog::getLabelsForKeyValues($field, $values);
				return implode(' or ', $strings);
				break;
				
			case SearchFields_ContextActivityLog::ACTIVITY_POINT:
				$strings = SearchFields_ContextActivityLog::getLabelsForKeyValues($field, $values);
				return implode(' or ', $strings);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_ContextActivityLog::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_ContextActivityLog::ENTRY_JSON:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_ContextActivityLog::ID:
			case SearchFields_ContextActivityLog::ACTOR_CONTEXT_ID:
			case SearchFields_ContextActivityLog::TARGET_CONTEXT_ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_ContextActivityLog::CREATED:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_ContextActivityLog::ACTIVITY_POINT:
				$options = DevblocksPlatform::importGPC($_POST['options'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_ContextActivityLog::ACTOR_CONTEXT:
			case SearchFields_ContextActivityLog::TARGET_CONTEXT:
				$contexts = DevblocksPlatform::importGPC($_POST['contexts'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$contexts);
				break;
				
			case SearchFields_ContextActivityLog::VIRTUAL_ACTOR:
			case SearchFields_ContextActivityLog::VIRTUAL_TARGET:
				$context_links = DevblocksPlatform::importGPC($_POST['context_link'] ?? null, 'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria, $field);
			$this->renderPage = 0;
		}
	}
};

class Context_ContextActivityLog extends Extension_DevblocksContext implements IDevblocksContextProfile {
	const ID = 'cerberusweb.contexts.activity_log';
	const URI = 'activity_log';
	
	static function isReadableByActor($models, $actor) {
		// Everyone can read
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Only admins can modify
		if(false == ($actor = CerberusContexts::polymorphActorToDictionary($actor)))
			return CerberusContexts::denyEverything($models);
		
		// Admins can do whatever they want
		if(CerberusContexts::isActorAnAdmin($actor))
			return CerberusContexts::allowEverything($models);
		
		return CerberusContexts::denyEverything($models);
	}
	
	static function isDeletableByActor($models, $actor) {
		return self::isWriteableByActor($models, $actor);
	}
	
	function profileGetUrl($context_id) {
		return null;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		/* @var $model Model_ContextActivityLog */
		
		if(is_null($model))
			$model = new Model_ContextActivityLog();
		
		$properties['label'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		);
		
		// [TODO] Translate value
		$properties['activity'] = array(
			'label' => mb_ucfirst($translate->_('common.activity')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->activity_point,
		);
		
		$properties['created'] = array(
			'label' => mb_ucfirst($translate->_('common.created')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->created,
		);
		
		$properties['actor'] = array(
			'label' => mb_ucfirst($translate->_('common.actor')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->actor_context_id,
			'params' => [
				'context' => $model->actor_context,
			],
		);
		
		$properties['target'] = array(
			'label' => mb_ucfirst($translate->_('common.target')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->target_context_id,
			'params' => [
				'context' => $model->target_context,
			],
		);
		
		$properties['id'] = array(
			'label' => mb_ucfirst($translate->_('common.id')),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		);
		
		return $properties;
	}
	
	function getRandom() {
		return DAO_ContextActivityLog::random();
	}
	
	function getMeta($context_id) {
		if(null == ($entry = DAO_ContextActivityLog::get($context_id)))
			return [];
		
		return array(
			'id' => $entry->id,
			'name' => CerberusContexts::formatActivityLogEntry(json_decode($entry->entry_json, true), 'text'),
			'permalink' => null,
			'updated' => $entry->created,
		);
	}
	
	function getPropertyLabels(DevblocksDictionaryDelegate $dict) {
		$labels = $dict->_labels;
		$prefix = $labels['_label'];
		
		if(!empty($prefix)) {
			array_walk($labels, function(&$label, $key) use ($prefix) {
				$label = preg_replace(sprintf("#^%s #", preg_quote($prefix)), '', $label);
				
				// [TODO] Use translations
				switch($key) {
				}
				
				$label = mb_convert_case($label, MB_CASE_LOWER);
				$label[0] = mb_convert_case($label[0], MB_CASE_UPPER);
			});
		}
		
		asort($labels);
		
		return $labels;
	}
	
	function getDefaultProperties() {
		return array(
			'event',
			'created',
			'actor__label',
			'target__label',
		);
	}
	
	function getContext($entry, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Activity:';
		
		$translate = DevblocksPlatform::getTranslationService();
		
		// Polymorph
		if(is_numeric($entry)) {
			$entry = DAO_ContextActivityLog::get($entry);
		} elseif($entry instanceof Model_ContextActivityLog) {
			// It's what we want already.
		} elseif(is_array($entry)) {
			$entry = Cerb_ORMHelper::recastArrayToModel($entry, 'Model_ContextActivityLog');
		} else {
			$entry = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'activity_point' => sprintf("%s %s", $prefix.$translate->_('common.event'), $prefix.$translate->_('common.id')),
			'event' => $prefix.$translate->_('common.event'),
			'created' => $prefix.$translate->_('common.created'),
			'actor__label' => $prefix.$translate->_('common.actor'),
			'target__label' => $prefix.$translate->_('common.target'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'activity_point' => Model_CustomField::TYPE_SINGLE_LINE,
			'created' => Model_CustomField::TYPE_DATE,
			'event' => Model_CustomField::TYPE_SINGLE_LINE,
			'actor__label' => 'context_url',
			'target__label' => 'context_url',
		);
		
		// Token values
		$token_values = array();
		
		$token_values['_context'] = Context_ContextActivityLog::ID;
		$token_values['_type'] = Context_ContextActivityLog::URI;
		
		$token_values['_types'] = $token_types;

		// Address token values
		if(null != $entry) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = CerberusContexts::formatActivityLogEntry(json_decode($entry->entry_json,true),'text');
			$token_values['id'] = $entry->id;
			$token_values['activity_point'] = $entry->activity_point;
			$token_values['created'] = $entry->created;
			
			$activities = DevblocksPlatform::getActivityPointRegistry();
			if(isset($activities[$entry->activity_point])) {
				$token_values['event'] = $activities[$entry->activity_point]['params']['label_key'];
			}
			
			$token_values['actor__context'] = $entry->actor_context;
			$token_values['actor_id'] = $entry->actor_context_id;
			
			$token_values['target__context'] = $entry->target_context;
			$token_values['target_id'] = $entry->target_context_id;
			
			$token_values['params'] = json_decode($entry->entry_json, true);
		}
		
		return true;
	}
	
	function getKeyToDaoFieldMap() {
		return [
			'activity_point' => DAO_ContextActivityLog::ACTIVITY_POINT,
			'actor__context' => DAO_ContextActivityLog::ACTOR_CONTEXT,
			'actor_id' => DAO_ContextActivityLog::ACTOR_CONTEXT_ID,
			'created' => DAO_ContextActivityLog::CREATED,
			'id' => DAO_ContextActivityLog::ID,
			'links' => '_links',
			'target__context' => DAO_ContextActivityLog::TARGET_CONTEXT,
			'target_id' => DAO_ContextActivityLog::TARGET_CONTEXT_ID,
		];
	}
	
	function getKeyMeta($with_dao_fields=true) {
		$keys = parent::getKeyMeta($with_dao_fields);

		$keys['activity_point']['notes'] = "The event ID that occurred (or `custom.other`)";
		$keys['actor__context']['notes'] = "The actor's record type";
		$keys['actor_id']['notes'] = "The actor's record ID";
		$keys['target__context']['notes'] = "The target's record type";
		$keys['target_id']['notes'] = "The target's record ID";
		
		$keys['params'] = [
			'key' => 'params',
			'is_immutable' => false,
			'is_required' => false,
			'notes' => 'JSON-encoded key/value object',
			'type' => 'object',
			'_reference' => [
				'params' => [
					'message' => 'The log message with your own `{{variables}}`',
					'variables' => 'A key/value object of placeholder values',
					'urls' => 'A key/value object of optional variable urls in the format `ctx://record_type:123`',
				]
			],		
		];
		
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, $data, &$error) {
		$dict_key = DevblocksPlatform::strLower($key);
		switch($dict_key) {
			
			case 'params':
				if(!is_array($value)) {
					$error = 'must be an object.';
					return false;
				}
				
				if(false == ($json = json_encode($value))) {
					$error = 'could not be JSON encoded.';
					return false;
				}
				
				$out_fields[DAO_Notification::ENTRY_JSON] = $json;
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
		
		$context = CerberusContexts::CONTEXT_ACTIVITY_LOG;
		$context_id = $dictionary['id'];
		
		$is_loaded = $dictionary['_loaded'] ?? false;
		$values = array();
		
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
		$view->name = 'Activity Log';
		
		$view->addParamsDefault(array(
			//SearchFields_ContextActivityLog::IS_BANNED => new DevblocksSearchCriteria(SearchFields_ContextActivityLog::IS_BANNED,'=',0),
			//SearchFields_ContextActivityLog::IS_DEFUNCT => new DevblocksSearchCriteria(SearchFields_ContextActivityLog::IS_DEFUNCT,'=',0),
		), true);
		$view->addParams($view->getParamsDefault(), true);
		
		$view->renderSortBy = SearchFields_ContextActivityLog::CREATED;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderTemplate = 'contextlinks_chooser';
		
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array(), $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = C4_AbstractViewModel::loadFromClass($this->getViewClass());
		$defaults->id = $view_id;
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Activity Log';
		
		$params_req = array();
		
		if($context && $context_id) {
			$params_req = [
				//new DevblocksSearchCriteria(SearchFields_ContextActivityLog::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			];
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
};