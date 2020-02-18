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

class DAO_Task extends Cerb_ORMHelper {
	const COMPLETED_DATE = 'completed_date';
	const CREATED_AT = 'created_at';
	const DUE_DATE = 'due_date';
	const ID = 'id';
	const IMPORTANCE = 'importance';
	const OWNER_ID = 'owner_id';
	const REOPEN_AT = 'reopen_at';
	const STATUS_ID = 'status_id';
	const TITLE = 'title';
	const UPDATED_DATE = 'updated_date';
	
	private function __construct() {}

	static function getFields() {
		$validation = DevblocksPlatform::services()->validation();
		
		// int(10) unsigned
		$validation
			->addField(self::COMPLETED_DATE)
			->timestamp()
			;
		// int(10) unsigned
		$validation
			->addField(self::CREATED_AT)
			->timestamp()
			;
		// int(10) unsigned
		$validation
			->addField(self::DUE_DATE)
			->timestamp()
			;
		// int(10) unsigned
		$validation
			->addField(self::ID)
			->id()
			->setEditable(false)
			;
		// tinyint(3) unsigned
		$validation
			->addField(self::IMPORTANCE)
			->uint(1)
			->setMin(0)
			->setMax(100)
			;
		// int(10) unsigned
		$validation
			->addField(self::OWNER_ID)
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_WORKER, true))
			;
		// int(10) unsigned
		$validation
			->addField(self::REOPEN_AT)
			->timestamp()
			;
		// tinyint(3) unsigned
		$validation
			->addField(self::STATUS_ID)
			->uint(1)
			->setMin(0)
			->setMax(3)
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
			->addField(self::UPDATED_DATE)
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
	
	static function create($fields, $custom_fields=[]) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("INSERT INTO task () ".
			"VALUES ()"
		);
		$db->ExecuteMaster($sql);
		
		$id = $db->LastInsertId();
		
		if(!isset($fields[DAO_Task::CREATED_AT]))
			$fields[DAO_Task::CREATED_AT] = time();
		
		self::update($id, $fields);
		
		if(!empty($custom_fields)) {
			DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_TASK, $id, $custom_fields);
		}
		
		/*
		 * Log the activity of a new task being created
		 */
		
		if(isset($fields[DAO_Task::TITLE])) {
			$entry = array(
				//{{actor}} created task {{target}}
				'message' => 'activities.task.created',
				'variables' => array(
					'target' => $fields[DAO_Task::TITLE],
					),
				'urls' => array(
					'target' => sprintf("ctx://%s:%d", CerberusContexts::CONTEXT_TASK, $id),
					)
			);
			CerberusContexts::logActivity('task.created', CerberusContexts::CONTEXT_TASK, $id, $entry, null, null);
		}
		
		// New task
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'task.create',
				array(
					'task_id' => $id,
					'fields' => $fields,
					'custom_fields' => $custom_fields,
				)
			)
		);
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
		
		if(!isset($fields[self::UPDATED_DATE]))
			$fields[self::UPDATED_DATE] = time();
		
		$context = CerberusContexts::CONTEXT_TASK;
		self::_updateAbstract($context, $ids, $fields);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
			
			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_TASK, $batch_ids);
			}
			
			// Make changes
			parent::_update($batch_ids, 'task', $fields);
			
			// Send events
			if($check_deltas) {
				// Local events
				self::_processUpdateEvents($batch_ids, $fields);
				
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::services()->event();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.task.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_TASK, $batch_ids);
			}
		}
	}
	
	static public function onBeforeUpdateByActor($actor, &$fields, $id=null, &$error=null) {
		$context = CerberusContexts::CONTEXT_TASK;
		
		if(!self::_onBeforeUpdateByActorCheckContextPrivs($actor, $context, $id, $error))
			return false;
		
		return true;
	}
	
	/**
	 * @param Model_ContextBulkUpdate $update
	 * @return boolean
	 */
	static function bulkUpdate(Model_ContextBulkUpdate $update) {
		$do = $update->actions;
		$ids = $update->context_ids;

		// Make sure we have actions
		if(empty($ids) || empty($do))
			return false;
		
		$update->markInProgress();
		
		$change_fields = [];
		$custom_fields = [];
		$deleted = false;

		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				case 'delete':
					$deleted = true;
					break;
				case 'due':
					@$date = strtotime($v);
					$change_fields[DAO_Task::DUE_DATE] = intval($date);
					break;
				case 'importance':
					@$importance = DevblocksPlatform::intClamp($v, 0, 100);
					$change_fields[DAO_Task::IMPORTANCE] = $importance;
					break;
				case 'owner':
					@$owner_id = intval($v);
					$change_fields[DAO_Task::OWNER_ID] = $owner_id;
					break;
				case 'reopen':
					@$date = strtotime($v);
					$change_fields[DAO_Task::REOPEN_AT] = intval($date);
					break;
				case 'status':
					switch($v) {
						case 1: // completed
							$change_fields[DAO_Task::STATUS_ID] = 1;
							$change_fields[DAO_Task::COMPLETED_DATE] = time();
							break;
						case 2: // waiting
							$change_fields[DAO_Task::STATUS_ID] = 1;
							$change_fields[DAO_Task::COMPLETED_DATE] = time();
							break;
						default: // active
							$change_fields[DAO_Task::STATUS_ID] = 0;
							$change_fields[DAO_Task::COMPLETED_DATE] = 0;
							$change_fields[DAO_Task::REOPEN_AT] = 0;
							break;
					}
					break;
				default:
					// Custom fields
					if(DevblocksPlatform::strStartsWith($k, 'cf_')) {
						$custom_fields[substr($k,3)] = $v;
					}
					break;
			}
		}
		
		if($deleted) {
			DAO_Task::delete($ids);
			
		} else {
			DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_TASK, $ids);
			
			DAO_Task::update($ids, $change_fields, false);
			
			// Custom Fields
			C4_AbstractView::_doBulkSetCustomFields(CerberusContexts::CONTEXT_TASK, $custom_fields, $ids);
			
			// Scheduled behavior
			if(isset($do['behavior']))
				C4_AbstractView::_doBulkScheduleBehavior(CerberusContexts::CONTEXT_TASK, $do['behavior'], $ids);
			
			// Watchers
			if(isset($do['watchers']))
				C4_AbstractView::_doBulkChangeWatchers(CerberusContexts::CONTEXT_TASK, $do['watchers'], $ids);
			
			CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_TASK, $ids);
		}
		
		$update->markCompleted();
		return true;
	}
	
	static function _processUpdateEvents($ids, $change_fields) {
		// We only care about these fields, so abort if they aren't referenced

		$observed_fields = array(
			DAO_Task::STATUS_ID,
		);
		
		$used_fields = array_intersect($observed_fields, array_keys($change_fields));
		
		if(empty($used_fields))
			return;
		
		// Load records only if they're needed
		
		if(false == ($before_models = CerberusContexts::getCheckpoints(CerberusContexts::CONTEXT_TASK, $ids)))
			return;
		
		if(false == ($models = DAO_Task::getIds($ids)))
			return;
		
		foreach($models as $id => $model) {
			if(!isset($before_models[$id]))
				continue;
			
			$before_model = (object) $before_models[$id];
			
			/*
			 * Task completed
			 */
			
			// [TODO] We can merge this with 'Record changed'
			
			@$status_id = $change_fields[DAO_Task::STATUS_ID];
			
			if($status_id == $before_model->status_id)
				unset($change_fields[DAO_Task::STATUS_ID]);
			
			if(isset($change_fields[DAO_Task::STATUS_ID]) && 1 ==  $model->status_id) {
				/*
				 * Log activity (task.status.*)
				 */
				$entry = array(
					//{{actor}} completed task {{target}}
					'message' => 'activities.task.status.completed',
					'variables' => array(
						'target' => sprintf("%s", $model->title),
						),
					'urls' => array(
						'target' => sprintf("ctx://%s:%d", CerberusContexts::CONTEXT_TASK, $model->id)
						)
				);
				CerberusContexts::logActivity('task.status.completed', CerberusContexts::CONTEXT_TASK, $model->id, $entry);
			}
		}
		
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('task', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @return Model_Task[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = "SELECT id, title, owner_id, status_id, importance, due_date, reopen_at, created_at, updated_date, completed_date ".
			"FROM task ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY id asc";
		$rs = $db->ExecuteSlave($sql);
		
		return self::_getObjectsFromResult($rs);
	}
	
	/**
	 * @param integer $id
	 * @return Model_Task	 */
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
	 * @param resource $rs
	 * @return Model_Task[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object = new Model_Task();
			$object->id = $row['id'];
			$object->title = $row['title'];
			$object->created_at = intval($row['created_at']);
			$object->updated_date = intval($row['updated_date']);
			$object->due_date = intval($row['due_date']);
			$object->owner_id = intval($row['owner_id']);
			$object->importance = intval($row['importance']);
			$object->reopen_at = intval($row['reopen_at']);
			$object->status_id = intval($row['status_id']);
			$object->completed_date = intval($row['completed_date']);
			$objects[$object->id] = $object;
		}
		
		mysqli_free_result($rs);
		
		return $objects;
	}
	
	static function mergeIds($from_ids, $to_id) {
		$context = CerberusContexts::CONTEXT_TASK;
		
		if(empty($from_ids) || empty($to_id))
			return false;
			
		if(!is_numeric($to_id) || !is_array($from_ids))
			return false;
		
		self::_mergeIds($context, $from_ids, $to_id);
		
		return true;
	}
	
	/**
	 *
	 * @param array $ids
	 */
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::services()->database();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		// Tasks
		$db->ExecuteMaster(sprintf("DELETE FROM task WHERE id IN (%s)", $ids_list));

		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_TASK,
					'context_ids' => $ids
				)
			)
		);

		return true;
	}
	
	public static function maint() {
		$db = DevblocksPlatform::services()->database();
		
		// Fix missing owners
		$sql = "UPDATE task SET owner_id = 0 WHERE owner_id != 0 AND owner_id NOT IN (SELECT id FROM worker)";
		$db->ExecuteMaster($sql);
		
		// Fire event
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.maint',
				array(
					'context' => CerberusContexts::CONTEXT_TASK,
					'context_table' => 'task',
					'context_key' => 'id',
				)
			)
		);
	}
	
	public static function random() {
		return self::_getRandom('task');
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Task::getFields();
		
		list(,$wheres) = parent::_parseSearchParams($params, $columns, 'SearchFields_Task', $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"t.id as %s, ".
			"t.title as %s, ".
			"t.created_at as %s, ".
			"t.updated_date as %s, ".
			"t.owner_id as %s, ".
			"t.importance as %s, ".
			"t.due_date as %s, ".
			"t.reopen_at as %s, ".
			"t.status_id as %s, ".
			"t.completed_date as %s ",
				SearchFields_Task::ID,
				SearchFields_Task::TITLE,
				SearchFields_Task::CREATED_AT,
				SearchFields_Task::UPDATED_DATE,
				SearchFields_Task::OWNER_ID,
				SearchFields_Task::IMPORTANCE,
				SearchFields_Task::DUE_DATE,
				SearchFields_Task::REOPEN_AT,
				SearchFields_Task::STATUS_ID,
				SearchFields_Task::COMPLETED_DATE
		);

		$join_sql =
			"FROM task t ";

		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = self::_buildSortClause($sortBy, $sortAsc, $fields, $select_sql, 'SearchFields_Task');
		
		$result = array(
			'primary_table' => 't',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'sort' => $sort_sql,
		);
		
		return $result;
	}

	/**
	 *
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
		
		// Build it
		$sql =
			$select_sql.
			$join_sql.
			$where_sql.
			$sort_sql;
		
		if(false == ($rs = $db->SelectLimit($sql,$limit,$page*$limit)))
			return false;
		
		$results = [];
		
		if(!($rs instanceof mysqli_result))
			return false;
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object_id = intval($row[SearchFields_Task::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					"SELECT COUNT(t.id) ".
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}
	
};

class SearchFields_Task extends DevblocksSearchFields {
	// Task
	const ID = 't_id';
	const CREATED_AT = 't_created_at';
	const UPDATED_DATE = 't_updated_date';
	const OWNER_ID = 't_owner_id';
	const IMPORTANCE = 't_importance';
	const DUE_DATE = 't_due_date';
	const REOPEN_AT = 't_reopen_at';
	const STATUS_ID = 't_status_id';
	const COMPLETED_DATE = 't_completed_date';
	const TITLE = 't_title';
	
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_OWNER_SEARCH = '*_owner_search';
	const VIRTUAL_WATCHERS = '*_workers';
	
	// Comment Content
	const FULLTEXT_COMMENT_CONTENT = 'ftcc_content';
	
	static private $_fields = null;
	
	static function getPrimaryKey() {
		return 't.id';
	}
	
	static function getCustomFieldContextKeys() {
		return array(
			CerberusContexts::CONTEXT_TASK => new DevblocksSearchFieldContextKeys('t.id', self::ID),
		);
	}
	
	static function getWhereSQL(DevblocksSearchCriteria $param) {
		switch($param->field) {
			case self::FULLTEXT_COMMENT_CONTENT:
				return self::_getWhereSQLFromCommentFulltextField($param, Search_CommentContent::ID, CerberusContexts::CONTEXT_TASK, self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_CONTEXT_LINK:
				return self::_getWhereSQLFromContextLinksField($param, CerberusContexts::CONTEXT_TASK, self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_HAS_FIELDSET:
				return self::_getWhereSQLFromVirtualSearchSqlField($param, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, sprintf('SELECT context_id FROM context_to_custom_fieldset WHERE context = %s AND custom_fieldset_id IN (%%s)', Cerb_ORMHelper::qstr(CerberusContexts::CONTEXT_TASK)), self::getPrimaryKey());
				break;
				
			case self::VIRTUAL_OWNER_SEARCH:
				return self::_getWhereSQLFromVirtualSearchField($param, CerberusContexts::CONTEXT_WORKER, 't.owner_id');
				break;
				
			case self::VIRTUAL_WATCHERS:
				return self::_getWhereSQLFromWatchersField($param, CerberusContexts::CONTEXT_TASK, self::getPrimaryKey());
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
			case 'owner':
				$key = 'owner.id';
				break;
		}
		
		return parent::getFieldForSubtotalKey($key, $context, $query_fields, $search_fields, $primary_key);
	}
	
	static function getLabelsForKeyValues($key, $values) {
		switch($key) {
			case SearchFields_Task::ID:
				$models = DAO_Task::getIds($values);
				return array_column(DevblocksPlatform::objectsToArrays($models), 'title', 'id');
				break;
				
			case SearchFields_Task::OWNER_ID:
				$models = DAO_Worker::getIds($values);
				$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, CerberusContexts::CONTEXT_WORKER);
				$label_map = array_column(DevblocksPlatform::objectsToArrays($dicts), '_label', 'id');
				if(in_array(0, $values))
					$label_map[0] = DevblocksPlatform::translate('common.nobody');
				return $label_map;
				break;
				
			case SearchFields_Task::STATUS_ID:
				$label_map = [
					0 => DevblocksPlatform::translate('status.open'),
					1 => DevblocksPlatform::translate('status.closed'),
					2 => DevblocksPlatform::translate('status.waiting.abbr'),
				];
				return $label_map;
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
			self::ID => new DevblocksSearchField(self::ID, 't', 'id', $translate->_('common.id'), null, true),
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 't', 'created_at', $translate->_('common.created'), Model_CustomField::TYPE_DATE, true),
			self::UPDATED_DATE => new DevblocksSearchField(self::UPDATED_DATE, 't', 'updated_date', $translate->_('common.updated'), Model_CustomField::TYPE_DATE, true),
			self::TITLE => new DevblocksSearchField(self::TITLE, 't', 'title', $translate->_('common.title'), Model_CustomField::TYPE_SINGLE_LINE, true),
			self::STATUS_ID => new DevblocksSearchField(self::STATUS_ID, 't', 'status_id', $translate->_('common.status'), null, true),
			self::OWNER_ID => new DevblocksSearchField(self::OWNER_ID, 't', 'owner_id', $translate->_('common.owner'), Model_CustomField::TYPE_WORKER, true),
			self::IMPORTANCE => new DevblocksSearchField(self::IMPORTANCE, 't', 'importance', $translate->_('common.importance'), Model_CustomField::TYPE_NUMBER, true),
			self::DUE_DATE => new DevblocksSearchField(self::DUE_DATE, 't', 'due_date', $translate->_('task.due_date'), Model_CustomField::TYPE_DATE, true),
			self::REOPEN_AT => new DevblocksSearchField(self::REOPEN_AT, 't', 'reopen_at', $translate->_('common.reopen_at'), Model_CustomField::TYPE_DATE, true),
			self::COMPLETED_DATE => new DevblocksSearchField(self::COMPLETED_DATE, 't', 'completed_date', $translate->_('task.completed_date'), Model_CustomField::TYPE_DATE, true),
			
			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null, false),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null, false),
			self::VIRTUAL_OWNER_SEARCH => new DevblocksSearchField(self::VIRTUAL_OWNER_SEARCH, '*', 'owner_search', null, null, false),
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers'), 'WS', false),
			
			self::FULLTEXT_COMMENT_CONTENT => new DevblocksSearchField(self::FULLTEXT_COMMENT_CONTENT, 'ftcc', 'content', $translate->_('comment.filters.content'), 'FT', false),
		);
		
		// Fulltext indexes
		
		$columns[self::FULLTEXT_COMMENT_CONTENT]->ft_schema = Search_CommentContent::ID;
		
		// Custom fields with fieldsets
		
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array_keys(self::getCustomFieldContextKeys()));
		
		if(is_array($custom_columns))
			$columns = array_merge($columns, $custom_columns);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');
		
		return $columns;
	}
};

class Model_Task {
	public $id;
	public $title;
	public $created_at;
	public $owner_id;
	public $importance;
	public $due_date = 0;
	public $reopen_at = 0;
	public $status_id = 0;
	public $completed_date;
	public $updated_date;
	
	function getStatusText() {
		$labels = [
			0 => DevblocksPlatform::translateCapitalized('status.open'),
			1 => DevblocksPlatform::translateCapitalized('status.closed'),
			2 => DevblocksPlatform::translateCapitalized('status.waiting.abbr'),
		];
		
		return @$labels[$this->status_id];
	}
	
	function getOwner() {
		if(!$this->owner_id || false === ($worker = DAO_Worker::get($this->owner_id)))
			return null;
		
		return $worker;
	}
};

class View_Task extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'tasks';
	const DEFAULT_TITLE = 'Tasks';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = self::DEFAULT_TITLE;
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Task::DUE_DATE;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_Task::UPDATED_DATE,
			SearchFields_Task::DUE_DATE,
			SearchFields_Task::IMPORTANCE,
			SearchFields_Task::OWNER_ID,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_Task::ID,
			SearchFields_Task::FULLTEXT_COMMENT_CONTENT,
			SearchFields_Task::VIRTUAL_CONTEXT_LINK,
			SearchFields_Task::VIRTUAL_HAS_FIELDSET,
			SearchFields_Task::VIRTUAL_OWNER_SEARCH,
			SearchFields_Task::VIRTUAL_WATCHERS,
		));
		
		$this->addParamsDefault(array(
			SearchFields_Task::STATUS_ID => new DevblocksSearchCriteria(SearchFields_Task::STATUS_ID,'=',0),
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_Task::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		
		$this->_lazyLoadCustomFieldsIntoObjects($objects, 'SearchFields_Task');
		
		return $objects;
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_Task', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_Task', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = [];

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				case SearchFields_Task::IMPORTANCE:
				case SearchFields_Task::OWNER_ID:
				case SearchFields_Task::STATUS_ID:
					$pass = true;
					break;
					
				// Virtuals
				case SearchFields_Task::VIRTUAL_CONTEXT_LINK:
				case SearchFields_Task::VIRTUAL_HAS_FIELDSET:
				case SearchFields_Task::VIRTUAL_WATCHERS:
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
		$context = CerberusContexts::CONTEXT_TASK;

		if(!isset($fields[$column]))
			return [];
		
		switch($column) {
			case SearchFields_Task::IMPORTANCE:
				$counts = $this->_getSubtotalCountForNumberColumn($context, $column);
				break;
				
			case SearchFields_Task::OWNER_ID:
				$label_map = function(array $values) use ($column) {
					return SearchFields_Task::getLabelsForKeyValues($column, $values);
				};
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map, 'in', 'worker_id');
				break;
				
			case SearchFields_Task::STATUS_ID:
				$label_map = function(array $values) use ($column) {
					return SearchFields_Task::getLabelsForKeyValues($column, $values);
				};
				$counts = $this->_getSubtotalCountForStringColumn($context, $column, $label_map, 'in', 'options');
				break;
				
			case SearchFields_Task::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn($context, $column);
				break;
				
			case SearchFields_Task::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn($context, $column);
				break;
				
			case SearchFields_Task::VIRTUAL_WATCHERS:
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
		$search_fields = SearchFields_Task::getFields();
		
		$fields = array(
			'text' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Task::TITLE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'comments' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_FULLTEXT,
					'options' => array('param_key' => SearchFields_Task::FULLTEXT_COMMENT_CONTENT),
				),
			'completed' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Task::COMPLETED_DATE),
				),
			'created' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Task::CREATED_AT),
				),
			'due' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Task::DUE_DATE),
				),
			'fieldset' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Task::VIRTUAL_HAS_FIELDSET),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_CUSTOM_FIELDSET, 'qr' => 'context:' . CerberusContexts::CONTEXT_TASK],
					]
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Task::ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_TASK, 'q' => ''],
					]
				),
			'importance' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Task::IMPORTANCE),
				),
			'owner' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Task::VIRTUAL_OWNER_SEARCH),
					'examples' => [
						['type' => 'search', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					]
				),
			'owner.id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_WORKER,
					'options' => array('param_key' => SearchFields_Task::OWNER_ID),
					'examples' => [
						['type' => 'chooser', 'context' => CerberusContexts::CONTEXT_WORKER, 'q' => ''],
					]
				),
			'reopen' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Task::REOPEN_AT),
				),
			'status' =>
				array(
					'type' => DevblocksSearchCriteria::TYPE_VIRTUAL,
					'options' => array('param_key' => SearchFields_Task::STATUS_ID),
					'examples' => array(
						'open',
						'waiting',
						'closed',
						'deleted',
						'[o,w]',
						'![d]',
					),
				),
			'title' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Task::TITLE, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'updated' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Task::UPDATED_DATE),
				),
			'watchers' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_WORKER,
					'options' => array('param_key' => SearchFields_Task::VIRTUAL_WATCHERS),
				),
		);
		
		// Add quick search links
		
		$fields = self::_appendVirtualFiltersFromQuickSearchContexts('links', $fields, 'links', SearchFields_Task::VIRTUAL_CONTEXT_LINK);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_TASK, $fields, null);
		
		// Engine/schema examples: Comments
		
		$ft_examples = [];
		
		if(false != ($schema = Extension_DevblocksSearchSchema::get(Search_CommentContent::ID))) {
			if(false != ($engine = $schema->getEngine())) {
				$ft_examples = $engine->getQuickSearchExamples($schema);
			}
		}
		
		if(!empty($ft_examples))
			$fields['comments']['examples'] = $ft_examples;
		
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
				break;
			
			case 'owner':
				return DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, SearchFields_Task::VIRTUAL_OWNER_SEARCH);
				break;
				
			case 'isCompleted':
			case 'status':
				$field_key = SearchFields_Task::STATUS_ID;
				$oper = null;
				$value = null;
				
				CerbQuickSearchLexer::getOperArrayFromTokens($tokens, $oper, $value);
				
				$values = [];
				
				// Normalize status labels
				foreach($value as $status) {
					switch(substr(DevblocksPlatform::strLower($status), 0, 1)) {
						case 'o':
						case '0':
							$values['0'] = true;
							break;
						case 'w':
						case '2':
							$values['2'] = true;
							break;
						case 'c':
						case '1':
							$values['1'] = true;
							break;
					}
				}
				
				return new DevblocksSearchCriteria(
					$field_key,
					$oper,
					array_keys($values)
				);
				break;
				
			default:
				if($field == 'links' || substr($field, 0, 6) == 'links.')
					return DevblocksSearchCriteria::getContextLinksParamFromTokens($field, $tokens);
				
				$search_fields = $this->getQuickSearchFields();
				$param = DevblocksSearchCriteria::getParamFromQueryFieldTokens($field, $tokens, $search_fields);
				return $param;
				break;
		}
		
		return false;
	}
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

		$tpl->assign('timestamp_now', time());

		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TASK);
		$tpl->assign('custom_fields', $custom_fields);
		
		switch($this->renderTemplate) {
			case 'contextlinks_chooser':
			default:
				$tpl->assign('view_template', 'devblocks:cerberusweb.core::tasks/view.tpl');
				$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
				break;
		}
		
		$tpl->clearAssign('custom_fields');
		$tpl->clearAssign('id');
	}

	function renderVirtualCriteria($param) {
		$key = $param->field;
		
		switch($key) {
			case SearchFields_Task::VIRTUAL_CONTEXT_LINK:
				$this->_renderVirtualContextLinks($param);
				break;
				
			case SearchFields_Task::VIRTUAL_HAS_FIELDSET:
				$this->_renderVirtualHasFieldset($param);
				break;
				
			case SearchFields_Task::VIRTUAL_OWNER_SEARCH:
				echo sprintf("%s matches <b>%s</b>",
					DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.owner')),
					DevblocksPlatform::strEscapeHtml($param->value)
				);
				break;
				
			case SearchFields_Task::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}
	
	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_Task::STATUS_ID:
				$label_map = SearchFields_Task::getLabelsForKeyValues($field, $values);
				parent::_renderCriteriaParamString($param, $label_map);
				break;
				
			case SearchFields_Task::OWNER_ID:
				$this->_renderCriteriaParamWorker($param);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_Task::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Task::TITLE:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_Task::IMPORTANCE:
				@$value = DevblocksPlatform::importGPC($_POST['value'],'integer',0);
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_Task::CREATED_AT:
			case SearchFields_Task::UPDATED_DATE:
			case SearchFields_Task::COMPLETED_DATE:
			case SearchFields_Task::REOPEN_AT:
			case SearchFields_Task::DUE_DATE:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;

			case SearchFields_Task::STATUS_ID:
				@$options = DevblocksPlatform::importGPC($_POST['options'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_Task::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_POST['context_link'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_Task::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_POST['options'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_Task::OWNER_ID:
			case SearchFields_Task::VIRTUAL_WATCHERS:
				@$worker_ids = DevblocksPlatform::importGPC($_POST['worker_id'],'array',[]);
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_ids);
				break;
				
			case SearchFields_Task::FULLTEXT_COMMENT_CONTENT:
				@$scope = DevblocksPlatform::importGPC($_POST['scope'],'string','expert');
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_FULLTEXT,array($value,$scope));
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

class Context_Task extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek, IDevblocksContextImport, IDevblocksContextMerge {
	const ID = 'cerberusweb.contexts.task';
	
	static function isReadableByActor($models, $actor) {
		// Everyone can read
		return CerberusContexts::allowEverything($models);
	}
	
	static function isWriteableByActor($models, $actor) {
		// Everyone can modify
		return CerberusContexts::allowEverything($models);
	}
	
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::services()->url();
		$url = $url_writer->writeNoProxy('c=profiles&type=task&id='.$context_id, true);
		return $url;
	}
	
	function profileGetFields($model=null) {
		$translate = DevblocksPlatform::getTranslationService();
		$properties = [];
		
		/* @var $model Model_Task */
		
		if(is_null($model))
			$model = new Model_Task();
		
		$properties['name'] = array(
			'label' => mb_ucfirst($translate->_('common.name')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->id,
			'params' => [
				'context' => self::ID,
			],
		);
		
		$properties['id'] = array(
			'label' => DevblocksPlatform::translate('common.id'),
			'type' => Model_CustomField::TYPE_NUMBER,
			'value' => $model->id,
		);
		
		$properties['status'] = array(
			'label' => mb_ucfirst($translate->_('common.status')),
			'type' => Model_CustomField::TYPE_SINGLE_LINE,
			'value' => $model->getStatusText(),
		);
		
		$properties['reopen_at'] = array(
			'label' => mb_ucfirst($translate->_('common.reopen_at')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->reopen_at,
		);
		
		$properties['due_date'] = array(
			'label' => mb_ucfirst($translate->_('task.due_date')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->due_date,
		);
			
		$properties['completed_date'] = array(
			'label' => mb_ucfirst($translate->_('task.completed_date')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->completed_date,
		);
		
		$properties['owner_id'] = array(
			'label' => mb_ucfirst($translate->_('common.owner')),
			'type' => Model_CustomField::TYPE_LINK,
			'value' => $model->owner_id,
			'params' => [
				'context' => CerberusContexts::CONTEXT_WORKER,
			]
		);
		
		$properties['importance'] = array(
			'label' => mb_ucfirst($translate->_('common.importance')),
			'type' => 'slider',
			'value' => $model->importance,
			'params' => [
				'min' => 0,
				'mid' => 50,
				'max' => 100,
			],
		);
		
		$properties['created_at'] = array(
			'label' => mb_ucfirst($translate->_('common.created')),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->created_at,
		);
		
		$properties['updated_date'] = array(
			'label' => DevblocksPlatform::translateCapitalized('common.updated'),
			'type' => Model_CustomField::TYPE_DATE,
			'value' => $model->updated_date,
		);
		
		return $properties;
	}
	
	function getMeta($context_id) {
		$task = DAO_Task::get($context_id);

		$url = $this->profileGetUrl($context_id);
		$friendly = DevblocksPlatform::strToPermalink($task->title);
		
		if(!empty($friendly))
			$url .= '-' . $friendly;
		
		return array(
			'id' => $task->id,
			'name' => $task->title,
			'permalink' => $url,
			'owner_id' => $task->owner_id,
			'updated' => $task->updated_date,
		);
	}
	
	function getRandom() {
		return DAO_Task::random();
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
			'status',
			'reopen',
			'importance',
			'due',
			'updated',
			'owner__label',
		);
	}
	
	function getContext($task, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Task:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TASK);

		// Polymorph
		if(is_numeric($task)) {
			$task = DAO_Task::get($task);
		} elseif($task instanceof Model_Task) {
			// It's what we want already.
		} elseif(is_array($task)) {
			$task = Cerb_ORMHelper::recastArrayToModel($task, 'Model_Task');
		} else {
			$task = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'created' => $prefix.$translate->_('common.created'),
			'completed' => $prefix.$translate->_('task.completed_date'),
			'due' => $prefix.$translate->_('task.due_date'),
			'id' => $prefix.$translate->_('common.id'),
			'importance' => $prefix.$translate->_('common.importance'),
			'reopen' => $prefix.$translate->_('common.reopen_at'),
			'status' => $prefix.$translate->_('common.status'),
			'title' => $prefix.$translate->_('common.title'),
			'updated' => $prefix.$translate->_('common.updated'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);

		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'created' => Model_CustomField::TYPE_DATE,
			'completed' => Model_CustomField::TYPE_DATE,
			'due' => Model_CustomField::TYPE_DATE,
			'id' => Model_CustomField::TYPE_NUMBER,
			'importance' => Model_CustomField::TYPE_NUMBER,
			'reopen' => Model_CustomField::TYPE_DATE,
			'status' => Model_CustomField::TYPE_SINGLE_LINE,
			'title' => Model_CustomField::TYPE_SINGLE_LINE,
			'updated' => Model_CustomField::TYPE_DATE,
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
		
		$token_values['_context'] = CerberusContexts::CONTEXT_TASK;
		$token_values['_types'] = $token_types;
		
		if($task) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $task->title ?: '(no name)';
			$token_values['created'] = $task->created_at;
			$token_values['completed'] = $task->completed_date;
			$token_values['due'] = $task->due_date;
			$token_values['id'] = $task->id;
			$token_values['importance'] = $task->importance;
			$token_values['is_completed'] = $task->status_id == 1;
			$token_values['reopen'] = $task->reopen_at;
			$token_values['status_id'] = $task->status_id;
			$token_values['owner_id'] = $task->owner_id;
			$token_values['title'] = $task->title;
			$token_values['updated'] = $task->updated_date;
			
			// Status
			switch($task->status_id) {
				case 0:
					$token_values['status'] = mb_convert_case($translate->_('status.open'), MB_CASE_LOWER);
					break;
				case 1:
					$token_values['status'] = mb_convert_case($translate->_('status.closed'), MB_CASE_LOWER);
					break;
				case 2:
					$token_values['status'] = mb_convert_case($translate->_('status.waiting.abbr'), MB_CASE_LOWER);
					break;
			}
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($task, $token_values);
			
			// URL
			$url_writer = DevblocksPlatform::services()->url();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=task&id=%d-%s",$task->id, DevblocksPlatform::strToPermalink($task->title)), true);
		}
		
		// Owner
		$merge_token_labels = [];
		$merge_token_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, null, $merge_token_labels, $merge_token_values, '', true);

			// Clear dupe content
			CerberusContexts::scrubTokensWithRegexp(
				$merge_token_labels,
				$merge_token_values,
				array(
					"#^address_org_#",
				)
			);
		
			CerberusContexts::merge(
				'owner_',
				$prefix.'Owner:',
				$merge_token_labels,
				$merge_token_values,
				$token_labels,
				$token_values
			);

		return true;
	}
	
	function getKeyToDaoFieldMap() {
		$map = parent::getKeyToDaoFieldMap();
		
		$map = array_merge($map, [
			'created' => DAO_Task::CREATED_AT,
			'completed' => DAO_Task::COMPLETED_DATE,
			'due' => DAO_Task::DUE_DATE,
			'id' => DAO_Task::ID,
			'importance' => DAO_Task::IMPORTANCE,
			'links' => '_links',
			'owner_id' => DAO_Task::OWNER_ID,
			'reopen' => DAO_Task::REOPEN_AT,
			'status_id' => DAO_Task::STATUS_ID,
			'title' => DAO_Task::TITLE,
			'updated' => DAO_Task::UPDATED_DATE,
		]);
		
		return $map;
	}
	
	function getKeyMeta() {
		$keys = parent::getKeyMeta();
		
		$keys['status'] = [
			'is_immutable' => false,
			'is_required' => false,
			'notes' => '`o` (open), `w` (waiting), `c` (closed); alternative to `status_id`',
			'type' => 'string',
		];
		
		$keys['completed']['notes'] = "The date/time this task was completed";
		$keys['due']['notes'] = "The date/time of this task's deadline";
		$keys['importance']['notes'] = "A number from `0` (least) to `100` (most)";
		$keys['owner_id']['notes'] = "The ID of the [worker](/docs/records/types/worker/) responsible for this task";
		$keys['reopen']['notes'] = "If the status is `waiting`, the date/time to automatically change the status back to `open`";
		$keys['status_id']['notes'] = "`0` (open), `1` (closed), `2` (waiting); alternative to `status`";
		$keys['title']['notes'] = "The name of this task";
		
		return $keys;
	}
	
	function getDaoFieldsFromKeyAndValue($key, $value, &$out_fields, &$error) {
		switch(DevblocksPlatform::strLower($key)) {
			case 'owner':
				break;
				
			case 'status':
				$statuses_to_ids = [
					'o' => 0,
					'w' => 2,
					'c' => 1,
				];
				
				$status_label = DevblocksPlatform::strLower(mb_substr($value,0,1));
				@$status_id = $statuses_to_ids[$status_label];
				
				if(is_null($status_id)) {
					$error = 'Status must be: open, waiting, or closed.';
					return false;
				}
				
				$out_fields[DAO_Task::STATUS_ID] = $status_id;
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

		$context = CerberusContexts::CONTEXT_TASK;
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
		$view->name = 'Tasks';
		$view->view_columns = array(
			SearchFields_Task::DUE_DATE,
			SearchFields_Task::IMPORTANCE,
			SearchFields_Task::OWNER_ID,
			SearchFields_Task::UPDATED_DATE,
		);
		$view->addParams(array(
			SearchFields_Task::STATUS_ID => new DevblocksSearchCriteria(SearchFields_Task::STATUS_ID,'=',0),
		), true);
		$view->renderSortBy = SearchFields_Task::UPDATED_DATE;
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
		$view->name = 'Tasks';
		
		$params_req = [];
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Task::VIRTUAL_CONTEXT_LINK,'in',array($context.':'.$context_id)),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='', $edit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$context = CerberusContexts::CONTEXT_TASK;
		
		$tpl->assign('view_id', $view_id);
		
		$task = null;
		
		if($context_id) {
			if(false == ($task = DAO_Task::get($context_id)))
				DevblocksPlatform::dieWithHttpError(null, 404);
		} else {
			$task = new Model_Task();
			$task->importance = 50;
		}
		
		if(!$context_id || $edit) {
			if($task && $task->id) {
				if(!Context_Task::isWriteableByActor($task, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
			}
			
			$tpl->assign('task', $task);
			
			// Custom fields
			$custom_fields = DAO_CustomField::getByContext($context, false);
			$tpl->assign('custom_fields', $custom_fields);
	
			$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
			if(isset($custom_field_values[$context_id]))
				$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
			
			$types = Model_CustomField::getTypes();
			$tpl->assign('types', $types);
			
			// Library
			if(!$context_id) {
				$packages = DAO_PackageLibrary::getByPoint('task');
				$tpl->assign('packages', $packages);
			}
			
			// View
			$tpl->assign('id', $context_id);
			$tpl->assign('view_id', $view_id);
			$tpl->display('devblocks:cerberusweb.core::tasks/rpc/peek_edit.tpl');
			
		} else {
			Page_Profiles::renderCard($context, $context_id, $task);
		}
	}
	
	function mergeGetKeys() {
		$keys = [
			'due',
			'importance',
			'owner__label',
			'reopen',
			'status',
			'title',
		];
		
		return $keys;
	}
	
	function importGetKeys() {
		// [TODO] Translate
	
		$keys = array(
			'created_at' => array(
				'label' => 'Created Date',
				'type' => Model_CustomField::TYPE_DATE,
				'param' => SearchFields_Task::CREATED_AT,
			),
			'completed_date' => array(
				'label' => 'Completed Date',
				'type' => Model_CustomField::TYPE_DATE,
				'param' => SearchFields_Task::COMPLETED_DATE,
			),
			'due_date' => array(
				'label' => 'Due Date',
				'type' => Model_CustomField::TYPE_DATE,
				'param' => SearchFields_Task::DUE_DATE,
			),
			'importance' => array(
				'label' => 'Importance',
				'type' => Model_CustomField::TYPE_NUMBER,
				'param' => SearchFields_Task::IMPORTANCE,
			),
			'owner_id' => array(
				'label' => 'Owner',
				'type' => Model_CustomField::TYPE_WORKER,
				'param' => SearchFields_Task::OWNER_ID,
			),
			'reopen' => array(
				'label' => 'Reopen At',
				'type' => Model_CustomField::TYPE_DATE,
				'param' => SearchFields_Task::REOPEN_AT,
			),
			'status_id' => array(
				'label' => 'Status',
				'type' => Model_CustomField::TYPE_NUMBER,
				'param' => SearchFields_Task::STATUS_ID,
			),
			'title' => array(
				'label' => 'Title',
				'type' => Model_CustomField::TYPE_SINGLE_LINE,
				'param' => SearchFields_Task::TITLE,
				'required' => true,
			),
			'updated_date' => array(
				'label' => 'Updated Date',
				'type' => Model_CustomField::TYPE_DATE,
				'param' => SearchFields_Task::UPDATED_DATE,
			),
		);
	
		$fields = SearchFields_Task::getFields();
		self::_getImportCustomFields($fields, $keys);
	
		DevblocksPlatform::sortObjects($keys, '[label]', true);
	
		return $keys;
	}
	
	function importKeyValue($key, $value) {
		switch($key) {
		}
	
		return $value;
	}
	
	function importSaveObject(array $fields, array $custom_fields, array $meta) {
		if(isset($fields[DAO_Task::STATUS_ID]) && !in_array($fields[DAO_Task::STATUS_ID], [0,1,2]))
			unset($fields[DAO_Task::STATUS_ID]);
		
		if(isset($fields[DAO_Task::STATUS_ID]) && 1 == $fields[DAO_Task::STATUS_ID] && !isset($fields[DAO_Task::COMPLETED_DATE]))
			$fields[DAO_Task::COMPLETED_DATE] = time();
		
		if(!isset($fields[DAO_Task::CREATED_AT]))
			$fields[DAO_Task::CREATED_AT] = time();
		
		if(!isset($fields[DAO_Task::UPDATED_DATE]))
			$fields[DAO_Task::UPDATED_DATE] = time();
		
		// If new...
		if(!isset($meta['object_id']) || empty($meta['object_id'])) {
			// Make sure we have a name
			if(!isset($fields[DAO_Task::TITLE])) {
				$fields[DAO_Task::TITLE] = 'New ' . $this->manifest->name;
			}
	
			// Create
			$meta['object_id'] = DAO_Task::create($fields);
	
		} else {
			// Update
			DAO_Task::update($meta['object_id'], $fields);
		}
	
		// Custom fields
		if(!empty($custom_fields) && !empty($meta['object_id'])) {
			DAO_CustomFieldValue::formatAndSetFieldValues($this->manifest->id, $meta['object_id'], $custom_fields, false, true, true); //$is_blank_unset (4th)
		}
	}
};
