<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2013, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/

class DAO_Task extends Cerb_ORMHelper {
	const ID = 'id';
	const TITLE = 'title';
	const CREATED_AT = 'created_at';
	const UPDATED_DATE = 'updated_date';
	const DUE_DATE = 'due_date';
	const IS_COMPLETED = 'is_completed';
	const COMPLETED_DATE = 'completed_date';

	static function create($fields, $custom_fields=array()) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("INSERT INTO task () ".
			"VALUES ()"
		);
		$db->Execute($sql);
		
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
		$eventMgr = DevblocksPlatform::getEventService();
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
		
		// Virtual Attendant events
		Event_TaskCreatedByWorker::trigger($id, null);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		if(!is_array($ids))
			$ids = array($ids);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;
			
			// Get state before changes
			$object_changes = parent::_getUpdateDeltas($batch_ids, $fields, get_class());

			// Make changes
			parent::_update($batch_ids, 'task', $fields);
			
			// Send events
			if(!empty($object_changes)) {
				// Local events
				self::_processUpdateEvents($object_changes);
				
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::getEventService();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.task.update',
						array(
							'objects' => $object_changes,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_TASK, $batch_ids);
			}
		}
	}
	
	static function _processUpdateEvents($objects) {
		if(is_array($objects))
		foreach($objects as $object_id => $object) {
			@$model = $object['model'];
			@$changes = $object['changes'];
			
			if(empty($model) || empty($changes))
				continue;
			
			/*
			 * Task completed
			 */
			@$is_completed = $changes[DAO_Task::IS_COMPLETED];
			
			if(!empty($is_completed) && !empty($model[DAO_Task::IS_COMPLETED])) {
				/*
				 * Log activity (task.status.*)
				 */
				$entry = array(
					//{{actor}} completed task {{target}}
					'message' => 'activities.task.status.completed',
					'variables' => array(
						'target' => sprintf("%s", $model[DAO_Task::TITLE]),
						),
					'urls' => array(
						'target' => sprintf("ctx://%s:%d", CerberusContexts::CONTEXT_TASK, $object_id)
						)
				);
				CerberusContexts::logActivity('task.status.completed', CerberusContexts::CONTEXT_TASK, $object_id, $entry);
			}
			
		} // foreach
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('task', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @return Model_Task[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, title, due_date, created_at, updated_date, is_completed, completed_date ".
			"FROM task ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY id asc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_Task	 */
	static function get($id) {
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
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_Task();
			$object->id = $row['id'];
			$object->title = $row['title'];
			$object->created_at = intval($row['created_at']);
			$object->updated_date = intval($row['updated_date']);
			$object->due_date = intval($row['due_date']);
			$object->is_completed = intval($row['is_completed']);
			$object->completed_date = intval($row['completed_date']);
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param array $ids
	 */
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		// Tasks
		$db->Execute(sprintf("DELETE QUICK FROM task WHERE id IN (%s)", $ids_list));

		// Fire event
		$eventMgr = DevblocksPlatform::getEventService();
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
		// Fire event
		$eventMgr = DevblocksPlatform::getEventService();
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
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]) || !in_array($sortBy,$columns))
			$sortBy=null;
		
		list($tables, $wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"t.id as %s, ".
			"t.title as %s, ".
			"t.created_at as %s, ".
			"t.updated_date as %s, ".
			"t.due_date as %s, ".
			"t.is_completed as %s, ".
			"t.completed_date as %s ",
				SearchFields_Task::ID,
				SearchFields_Task::TITLE,
				SearchFields_Task::CREATED_AT,
				SearchFields_Task::UPDATED_DATE,
				SearchFields_Task::DUE_DATE,
				SearchFields_Task::IS_COMPLETED,
				SearchFields_Task::COMPLETED_DATE
		);

		$join_sql =
			"FROM task t ".

			// [JAS]: Dynamic table joins
			(isset($tables['context_link']) ? "INNER JOIN context_link ON (context_link.to_context = 'cerberusweb.contexts.task' AND context_link.to_context_id = t.id) " : " ").
			(isset($tables['ftcc']) ? "INNER JOIN comment ON (comment.context = 'cerberusweb.contexts.task' AND comment.context_id = t.id) " : " ").
			(isset($tables['ftcc']) ? "INNER JOIN fulltext_comment_content ftcc ON (ftcc.id=comment.id) " : " ")
			;

		// Custom field joins
		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
			$tables,
			$params,
			't.id',
			$select_sql,
			$join_sql
		);
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql =	(!empty($sortBy) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ");
		
		// Translate virtual fields
		
		$args = array(
			'join_sql' => &$join_sql,
			'where_sql' => &$where_sql,
			'tables' => &$tables,
			'has_multiple_values' => &$has_multiple_values
		);
		
		array_walk_recursive(
			$params,
			array('DAO_Task', '_translateVirtualParameters'),
			$args
		);
		
		$result = array(
			'primary_table' => 't',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => $has_multiple_values,
			'sort' => $sort_sql,
		);
		
		return $result;
	}

	private static function _translateVirtualParameters($param, $key, &$args) {
		if(!is_a($param, 'DevblocksSearchCriteria'))
			return;
			
		$from_context = CerberusContexts::CONTEXT_TASK;
		$from_index = 't.id';
		
		$param_key = $param->field;
		settype($param_key, 'string');
		
		switch($param_key) {
			case SearchFields_Task::VIRTUAL_CONTEXT_LINK:
				$args['has_multiple_values'] = true;
				self::_searchComponentsVirtualContextLinks($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;
				
			case SearchFields_Task::VIRTUAL_HAS_FIELDSET:
				self::_searchComponentsVirtualHasFieldset($param, $from_context, $from_index, $args['join_sql'], $args['where_sql']);
				break;
			
			case SearchFields_Task::VIRTUAL_WATCHERS:
				$args['has_multiple_values'] = true;
				self::_searchComponentsVirtualWatchers($param, $from_context, $from_index, $args['join_sql'], $args['where_sql'], $args['tables']);
				break;
		}
	}
	
	/**
	 * Enter description here...
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
		$db = DevblocksPlatform::getDatabaseService();

		// Build search queries
		$query_parts = self::getSearchQueryComponents($columns,$params,$sortBy,$sortAsc);

		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$has_multiple_values = $query_parts['has_multiple_values'];
		$sort_sql = $query_parts['sort'];
		
		// Build it
		$sql =
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY t.id ' : '').
			$sort_sql;
		
		$rs = $db->SelectLimit($sql,$limit,$page*$limit) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		
		$results = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$id = intval($row[SearchFields_Task::ID]);
			$results[$id] = $result;
		}

		// [JAS]: Count all
		$total = -1;
		if($withCounts) {
			$count_sql =
				($has_multiple_values ? "SELECT COUNT(DISTINCT t.id) " : "SELECT COUNT(t.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}
	
};

class SearchFields_Task implements IDevblocksSearchFields {
	// Task
	const ID = 't_id';
	const CREATED_AT = 't_created_at';
	const UPDATED_DATE = 't_updated_date';
	const DUE_DATE = 't_due_date';
	const IS_COMPLETED = 't_is_completed';
	const COMPLETED_DATE = 't_completed_date';
	const TITLE = 't_title';
	
	const VIRTUAL_CONTEXT_LINK = '*_context_link';
	const VIRTUAL_HAS_FIELDSET = '*_has_fieldset';
	const VIRTUAL_WATCHERS = '*_workers';
	
	const CONTEXT_LINK = 'cl_context_from';
	const CONTEXT_LINK_ID = 'cl_context_from_id';
	
	// Comment Content
	const FULLTEXT_COMMENT_CONTENT = 'ftcc_content';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 't', 'id', $translate->_('common.id')),
			self::CREATED_AT => new DevblocksSearchField(self::CREATED_AT, 't', 'created_at', $translate->_('common.created'), Model_CustomField::TYPE_DATE),
			self::UPDATED_DATE => new DevblocksSearchField(self::UPDATED_DATE, 't', 'updated_date', $translate->_('common.updated'), Model_CustomField::TYPE_DATE),
			self::TITLE => new DevblocksSearchField(self::TITLE, 't', 'title', $translate->_('common.title'), Model_CustomField::TYPE_SINGLE_LINE),
			self::IS_COMPLETED => new DevblocksSearchField(self::IS_COMPLETED, 't', 'is_completed', $translate->_('task.is_completed'), Model_CustomField::TYPE_CHECKBOX),
			self::DUE_DATE => new DevblocksSearchField(self::DUE_DATE, 't', 'due_date', $translate->_('task.due_date'), Model_CustomField::TYPE_DATE),
			self::COMPLETED_DATE => new DevblocksSearchField(self::COMPLETED_DATE, 't', 'completed_date', $translate->_('task.completed_date'), Model_CustomField::TYPE_DATE),
			
			self::VIRTUAL_CONTEXT_LINK => new DevblocksSearchField(self::VIRTUAL_CONTEXT_LINK, '*', 'context_link', $translate->_('common.links'), null),
			self::VIRTUAL_HAS_FIELDSET => new DevblocksSearchField(self::VIRTUAL_HAS_FIELDSET, '*', 'has_fieldset', $translate->_('common.fieldset'), null),
			self::VIRTUAL_WATCHERS => new DevblocksSearchField(self::VIRTUAL_WATCHERS, '*', 'workers', $translate->_('common.watchers'), 'WS'),
			
			self::CONTEXT_LINK => new DevblocksSearchField(self::CONTEXT_LINK, 'context_link', 'from_context', null),
			self::CONTEXT_LINK_ID => new DevblocksSearchField(self::CONTEXT_LINK_ID, 'context_link', 'from_context_id', null),
		);
		
		$tables = DevblocksPlatform::getDatabaseTables();
		if(isset($tables['fulltext_comment_content'])) {
			$columns[self::FULLTEXT_COMMENT_CONTENT] = new DevblocksSearchField(self::FULLTEXT_COMMENT_CONTENT, 'ftcc', 'content', $translate->_('comment.filters.content'), 'FT');
		}
		
		// Custom fields with fieldsets
		
		$custom_columns = DevblocksSearchField::getCustomSearchFieldsByContexts(array(
			CerberusContexts::CONTEXT_TASK,
		));
		
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
	public $due_date;
	public $is_completed;
	public $completed_date;
	public $updated_date;
};

class View_Task extends C4_AbstractView implements IAbstractView_Subtotals {
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
		);
		
		$this->addColumnsHidden(array(
			SearchFields_Task::ID,
			SearchFields_Task::CONTEXT_LINK,
			SearchFields_Task::CONTEXT_LINK_ID,
			SearchFields_Task::FULLTEXT_COMMENT_CONTENT,
			SearchFields_Task::VIRTUAL_CONTEXT_LINK,
			SearchFields_Task::VIRTUAL_HAS_FIELDSET,
			SearchFields_Task::VIRTUAL_WATCHERS,
		));
		
		$this->addParamsHidden(array(
			SearchFields_Task::ID,
			SearchFields_Task::CONTEXT_LINK,
			SearchFields_Task::CONTEXT_LINK_ID,
		));
		
		$this->addParamsDefault(array(
			SearchFields_Task::IS_COMPLETED => new DevblocksSearchCriteria(SearchFields_Task::IS_COMPLETED,'=',0),
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		return $this->_objects = DAO_Task::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
	}
	
	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_Task', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_Task', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// Booleans
				case SearchFields_Task::IS_COMPLETED:
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
					if('cf_' == substr($field_key,0,3))
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

		if(!isset($fields[$column]))
			return array();
		
		switch($column) {
			case SearchFields_Task::IS_COMPLETED:
				$counts = $this->_getSubtotalCountForBooleanColumn('DAO_Task', $column);
				break;
				
			case SearchFields_Task::VIRTUAL_CONTEXT_LINK:
				$counts = $this->_getSubtotalCountForContextLinkColumn('DAO_Task', CerberusContexts::CONTEXT_TASK, $column);
				break;
				
			case SearchFields_Task::VIRTUAL_HAS_FIELDSET:
				$counts = $this->_getSubtotalCountForHasFieldsetColumn('DAO_Task', CerberusContexts::CONTEXT_TASK, $column);
				break;
				
			case SearchFields_Task::VIRTUAL_WATCHERS:
				$counts = $this->_getSubtotalCountForWatcherColumn('DAO_Task', $column);
				break;
			
			default:
				// Custom fields
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn('DAO_Task', $column, 't.id');
				}
				
				break;
		}
		
		return $counts;
	}
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

		$tpl->assign('timestamp_now', time());

		// Pull the results so we can do some row introspection
		$results = $this->getData();
		$tpl->assign('results', $results);

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
		$tpl->clearAssign('results');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);
		
		switch($field) {
			case SearchFields_Task::TITLE:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
				
			case SearchFields_Task::IS_COMPLETED:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
				
			case SearchFields_Task::CREATED_AT:
			case SearchFields_Task::UPDATED_DATE:
			case SearchFields_Task::DUE_DATE:
			case SearchFields_Task::COMPLETED_DATE:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
				
			case SearchFields_Task::FULLTEXT_COMMENT_CONTENT:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__fulltext.tpl');
				break;
				
			case SearchFields_Task::VIRTUAL_CONTEXT_LINK:
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_link.tpl');
				break;
				
			case SearchFields_Task::VIRTUAL_HAS_FIELDSET:
				$this->_renderCriteriaHasFieldset($tpl, CerberusContexts::CONTEXT_TASK);
				break;
			
			case SearchFields_Task::VIRTUAL_WATCHERS:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_worker.tpl');
				break;
				
			default:
				// Custom Fields
				if('cf_' == substr($field,0,3)) {
					$this->_renderCriteriaCustomField($tpl, substr($field,3));
				} else {
					echo ' ';
				}
				break;
		}
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
			
			case SearchFields_Task::VIRTUAL_WATCHERS:
				$this->_renderVirtualWatchers($param);
				break;
		}
	}
	
	function renderCriteriaParam($param) {
		$field = !is_null($param_key) ? $param_key : $param->field;
		$translate = DevblocksPlatform::getTranslationService();
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_Task::IS_COMPLETED:
				$this->_renderCriteriaParamBoolean($param);
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
				
			case SearchFields_Task::CREATED_AT:
			case SearchFields_Task::UPDATED_DATE:
			case SearchFields_Task::COMPLETED_DATE:
			case SearchFields_Task::DUE_DATE:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;

			case SearchFields_Task::IS_COMPLETED:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_Task::VIRTUAL_CONTEXT_LINK:
				@$context_links = DevblocksPlatform::importGPC($_REQUEST['context_link'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$context_links);
				break;
				
			case SearchFields_Task::VIRTUAL_HAS_FIELDSET:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
				
			case SearchFields_Task::VIRTUAL_WATCHERS:
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_ids);
				break;
				
			case SearchFields_Task::FULLTEXT_COMMENT_CONTENT:
				@$scope = DevblocksPlatform::importGPC($_REQUEST['scope'],'string','expert');
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
	
	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(600); // 10m
		
		$change_fields = array();
		$custom_fields = array();
		$deleted = false;

		// Make sure we have actions
		if(empty($do))
			return;

		// Make sure we have checked items if we want a checked list
		if(0 == strcasecmp($filter,"checks") && empty($ids))
			return;
			
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
				case 'status':
					switch($v) {
						case 1: // completed
							$change_fields[DAO_Task::IS_COMPLETED] = 1;
							$change_fields[DAO_Task::COMPLETED_DATE] = time();
							break;
						default: // active
							$change_fields[DAO_Task::IS_COMPLETED] = 0;
							$change_fields[DAO_Task::COMPLETED_DATE] = 0;
							break;
					}
					break;
				default:
					// Custom fields
					if(substr($k,0,3)=="cf_") {
						$custom_fields[substr($k,3)] = $v;
					}
			}
		}
		
		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_Task::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_Task::ID,
				true,
				false
			);
			 
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			if($deleted) {
				DAO_Task::delete($batch_ids);
				
			} else {
				DAO_Task::update($batch_ids, $change_fields);
				
				// Custom Fields
				self::_doBulkSetCustomFields(CerberusContexts::CONTEXT_TASK, $custom_fields, $batch_ids);
				
				// Scheduled behavior
				if(isset($do['behavior']) && is_array($do['behavior'])) {
					$behavior_id = $do['behavior']['id'];
					@$behavior_when = strtotime($do['behavior']['when']) or time();
					@$behavior_params = isset($do['behavior']['params']) ? $do['behavior']['params'] : array();
					
					if(!empty($batch_ids) && !empty($behavior_id))
					foreach($batch_ids as $batch_id) {
						DAO_ContextScheduledBehavior::create(array(
							DAO_ContextScheduledBehavior::BEHAVIOR_ID => $behavior_id,
							DAO_ContextScheduledBehavior::CONTEXT => CerberusContexts::CONTEXT_TASK,
							DAO_ContextScheduledBehavior::CONTEXT_ID => $batch_id,
							DAO_ContextScheduledBehavior::RUN_DATE => $behavior_when,
							DAO_ContextScheduledBehavior::VARIABLES_JSON => json_encode($behavior_params),
						));
					}
				}
				
				// Watchers
				if(isset($do['watchers']) && is_array($do['watchers'])) {
					$watcher_params = $do['watchers'];
					foreach($batch_ids as $batch_id) {
						if(isset($watcher_params['add']) && is_array($watcher_params['add']))
							CerberusContexts::addWatchers(CerberusContexts::CONTEXT_TASK, $batch_id, $watcher_params['add']);
						if(isset($watcher_params['remove']) && is_array($watcher_params['remove']))
							CerberusContexts::removeWatchers(CerberusContexts::CONTEXT_TASK, $batch_id, $watcher_params['remove']);
					}
				}
			}
			
			unset($batch_ids);
		}

		unset($ids);
	}
};

class Context_Task extends Extension_DevblocksContext implements IDevblocksContextProfile, IDevblocksContextPeek, IDevblocksContextImport {
	function profileGetUrl($context_id) {
		if(empty($context_id))
			return '';
	
		$url_writer = DevblocksPlatform::getUrlService();
		$url = $url_writer->writeNoProxy('c=profiles&type=task&id='.$context_id, true);
		return $url;
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
		);
	}
	
	function getRandom() {
		return DAO_Task::random();
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
		} else {
			$task = null;
		}
		
		// Token labels
		$token_labels = array(
			'created|date' => $prefix.$translate->_('common.created'),
			'completed|date' => $prefix.$translate->_('task.completed_date'),
			'due|date' => $prefix.$translate->_('task.due_date'),
			'id' => $prefix.$translate->_('common.id'),
			'is_completed' => $prefix.$translate->_('task.is_completed'),
			'status' => $prefix.$translate->_('common.status'),
			'title' => $prefix.$translate->_('common.title'),
			'updated|date' => $prefix.$translate->_('common.updated'),
			'record_url' => $prefix.$translate->_('common.url.record'),
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Token values
		$token_values = array();
		
		$token_values['_context'] = CerberusContexts::CONTEXT_TASK;
		
		if($task) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $task->title;
			$token_values['created'] = $task->created_at;
			$token_values['completed'] = $task->completed_date;
			$token_values['due'] = $task->due_date;
			$token_values['id'] = $task->id;
			$token_values['is_completed'] = $task->is_completed;
			$token_values['title'] = $task->title;
			$token_values['updated'] = $task->updated_date;
			
			// Status
			if($task->is_completed) {
				$token_values['status'] = 'completed';
			} else {
				$token_values['status'] = 'active';
			}
			
			// URL
			
			$url_writer = DevblocksPlatform::getUrlService();
			$token_values['record_url'] = $url_writer->writeNoProxy(sprintf("c=profiles&type=task&id=%d-%s",$task->id, DevblocksPlatform::strToPermalink($task->title)), true);
		}

		return true;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_TASK;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true);
		}
		
		switch($token) {
			case 'watchers':
				$watchers = array(
					$token => CerberusContexts::getWatchers($context, $context_id, true),
				);
				$values = array_merge($values, $watchers);
				break;
				
			default:
				if(substr($token,0,7) == 'custom_') {
					$fields = $this->_lazyLoadCustomFields($context, $context_id);
					$values = array_merge($values, $fields);
				}
				break;
		}
		
		return $values;
	}
	
	function getChooserView($view_id=null) {
		$active_worker = CerberusApplication::getActiveWorker();

		if(empty($view_id))
			$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
		
		// View
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Tasks';
		$view->view_columns = array(
			SearchFields_Task::UPDATED_DATE,
			SearchFields_Task::DUE_DATE,
		);
		$view->addParams(array(
			SearchFields_Task::IS_COMPLETED => new DevblocksSearchCriteria(SearchFields_Task::IS_COMPLETED,'=',0),
		), true);
		$view->renderSortBy = SearchFields_Task::UPDATED_DATE;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderFilters = false;
		$view->renderTemplate = 'contextlinks_chooser';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array()) {
		$view_id = str_replace('.','_',$this->id);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Tasks';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Task::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_Task::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
	
	function renderPeekPopup($context_id=0, $view_id='') {
		$tpl = DevblocksPlatform::getTemplateService();

		if(!empty($context_id)) {
			$task = DAO_Task::get($context_id);
			$tpl->assign('task', $task);
		}

		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TASK, false);
		$tpl->assign('custom_fields', $custom_fields);

		$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_TASK, $context_id);
		if(isset($custom_field_values[$context_id]))
			$tpl->assign('custom_field_values', $custom_field_values[$context_id]);
		
		$types = Model_CustomField::getTypes();
		$tpl->assign('types', $types);

		// Comments
		$comments = DAO_Comment::getByContext(CerberusContexts::CONTEXT_TASK, $context_id);
		$last_comment = array_shift($comments);
		unset($comments);
		$tpl->assign('last_comment', $last_comment);

		// View
		$tpl->assign('id', $context_id);
		$tpl->assign('view_id', $view_id);
		$tpl->display('devblocks:cerberusweb.core::tasks/rpc/peek.tpl');
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
			'is_completed' => array(
				'label' => 'Is Completed',
				'type' => Model_CustomField::TYPE_CHECKBOX,
				'param' => SearchFields_Task::IS_COMPLETED,
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
	
		$cfields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TASK);
	
		foreach($cfields as $cfield_id => $cfield) {
			$keys['cf_' . $cfield_id] = array(
				'label' => $cfield->name,
				'type' => $cfield->type,
				'param' => 'cf_' . $cfield_id,
			);
		}
	
		DevblocksPlatform::sortObjects($keys, '[label]', true);
	
		return $keys;
	}
	
	function importKeyValue($key, $value) {
		switch($key) {
		}
	
		return $value;
	}
	
	function importSaveObject(array $fields, array $custom_fields, array $meta) {
		if(isset($fields[DAO_Task::IS_COMPLETED]) && !empty($fields[DAO_Task::IS_COMPLETED]) && !isset($fields[DAO_Task::COMPLETED_DATE])) {
			$fields[DAO_Task::COMPLETED_DATE] = time();
		}
		
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
