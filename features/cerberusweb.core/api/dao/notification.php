<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2015, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerbweb.com	    http://www.webgroupmedia.com/
***********************************************************************/

class DAO_Notification extends DevblocksORMHelper {
	const CACHE_COUNT_PREFIX = 'notification_count_';
	
	const ID = 'id';
	const CONTEXT = 'context';
	const CONTEXT_ID = 'context_id';
	const CREATED_DATE = 'created_date';
	const WORKER_ID = 'worker_id';
	const IS_READ = 'is_read';
	const ACTIVITY_POINT = 'activity_point';
	const ENTRY_JSON = 'entry_json';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("INSERT INTO notification () ".
			"VALUES ()"
		);
		$db->ExecuteMaster($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		// If a worker was provided
		if(isset($fields[self::WORKER_ID])) {
			// Invalidate the worker notification count cache
			self::clearCountCache($fields[self::WORKER_ID]);
			
			// Trigger notification
			Event_NotificationReceivedByWorker::trigger($id, $fields[self::WORKER_ID]);
		}
		
		return $id;
	}
	
	static function update($ids, $fields, $check_deltas=true) {
		if(!is_array($ids))
			$ids = array($ids);
		
		// Make a diff for the requested objects in batches
		
		$chunks = array_chunk($ids, 100, true);
		while($batch_ids = array_shift($chunks)) {
			if(empty($batch_ids))
				continue;

			// Send events
			if($check_deltas) {
				CerberusContexts::checkpointChanges(CerberusContexts::CONTEXT_NOTIFICATION, $batch_ids);
			}

			// Make changes
			parent::_update($batch_ids, 'notification', $fields);
			
			// Send events
			if($check_deltas) {
				
				// Trigger an event about the changes
				$eventMgr = DevblocksPlatform::getEventService();
				$eventMgr->trigger(
					new Model_DevblocksEvent(
						'dao.notification.update',
						array(
							'fields' => $fields,
						)
					)
				);
				
				// Log the context update
				DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_NOTIFICATION, $batch_ids);
			}
		}
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('notification', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @return Model_Notification[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, context, context_id, created_date, worker_id, is_read, activity_point, entry_json ".
			"FROM notification ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->ExecuteSlave($sql);

		$objects = self::_getObjectsFromResult($rs);

		return $objects;
	}

	/**
	 * @param integer $id
	 * @return Model_Notification	 */
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
	
	static function getUnreadByContextAndWorker($context, $context_id, $worker_id=0, $mark_read=false) {
		$count = self::getUnreadCountByWorker($worker_id);
		
		if(empty($count))
			return array();
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$notifications = self::getWhere(
			sprintf("%s = %s AND %s = %d AND %s = %d %s",
				self::CONTEXT,
				$db->qstr($context),
				self::CONTEXT_ID,
				$context_id,
				DAO_Notification::IS_READ,
				0,
				($worker_id ? sprintf(" AND %s = %d", DAO_Notification::WORKER_ID, $worker_id) : '')
			)
		);
		
		// Auto mark-read?
		if($mark_read && $worker_id && !empty($notifications)) {
			DAO_Notification::update(array_keys($notifications), array(
				DAO_Notification::IS_READ => 1,
			));
			
			self::clearCountCache($worker_id);
		}
		
		return $notifications;
	}
	
	static function getUnreadCountByWorker($worker_id) {
		$db = DevblocksPlatform::getDatabaseService();
		$cache = DevblocksPlatform::getCacheService();
		
		if(null === ($count = $cache->load(self::CACHE_COUNT_PREFIX.$worker_id))) {
			$sql = sprintf("SELECT count(*) ".
				"FROM notification ".
				"WHERE worker_id = %d ".
				"AND is_read = 0",
				$worker_id
			);
			
			$count = intval($db->GetOneSlave($sql));
			$cache->save($count, self::CACHE_COUNT_PREFIX.$worker_id);
		}
		
		return intval($count);
	}
	
	/**
	 * @param resource $rs
	 * @return Model_Notification[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();

		if($rs instanceof mysqli_result) {
			while($row = mysqli_fetch_assoc($rs)) {
				$object = new Model_Notification();
				$object->id = intval($row['id']);
				$object->context = $row['context'];
				$object->context_id = intval($row['context_id']);
				$object->created_date = intval($row['created_date']);
				$object->worker_id = intval($row['worker_id']);
				$object->is_read = intval($row['is_read']);
				$object->activity_point = $row['activity_point'];
				$object->entry_json = $row['entry_json'];
				$objects[$object->id] = $object;
			}
			
			mysqli_free_result($rs);
		}
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids))
			$ids = array($ids);
		
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids = DevblocksPlatform::sanitizeArray($ids, array('nonzero', 'unique'));
		$ids_list = implode(',', $ids);
		
		$db->ExecuteMaster(sprintf("DELETE FROM notification WHERE id IN (%s)", $ids_list));
		
		// Fire event
		$eventMgr = DevblocksPlatform::getEventService();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.delete',
				array(
					'context' => CerberusContexts::CONTEXT_NOTIFICATION,
					'context_ids' => $ids
				)
			)
		);
		
		// Clear cache
		
		self::clearCountCache();
		
		return true;
	}
	
	static function deleteByContext($context, $context_ids) {
		if(!is_array($context_ids))
			$context_ids = array($context_ids);
		
		if(empty($context_ids))
			return;
		
		$context_ids = DevblocksPlatform::sanitizeArray($context_ids, 'int');
			
		$db = DevblocksPlatform::getDatabaseService();
		
		$db->ExecuteMaster(sprintf("DELETE FROM notification WHERE context = %s AND context_id IN (%s) ",
			$db->qstr($context),
			implode(',', $context_ids)
		));
		
		// Clear cache
		
		self::clearCountCache();
		
		return true;
	}
	
	static function deleteByContextActivityAndWorker($context, $context_ids, $activity_point=null, $worker_ids=array()) {
		$db = DevblocksPlatform::getDatabaseService();
		
		if(!is_array($context_ids))
			$context_ids = array($context_ids);

		if(!is_array($worker_ids))
			$worker_ids = array($worker_ids);
		
		if(empty($context_ids))
			return;
		
		// Sanitize inputs

		$context_ids = DevblocksPlatform::sanitizeArray($context_ids, 'int');
		$worker_ids = DevblocksPlatform::sanitizeArray($worker_ids, 'int');

		// Build where clause
		
		$wheres = array();
		
		if(!empty($activity_point)) {
			$wheres[] = sprintf("AND activity_point = %s",
				$db->qstr($activity_point)
			);
		}
		
		if(!empty($worker_ids)) {
			
			$wheres[] = sprintf("AND worker_id IN (%s)",
				implode(',', $worker_ids)
			);
		}
		
		// Delete notifications
		
		$sql = sprintf("DELETE FROM notification WHERE context = %s AND context_id IN (%s) %s",
			$db->qstr($context),
			implode(',', $context_ids),
			implode(' ', $wheres)
		);
		
		$db->ExecuteMaster($sql);
		
		// Clear cache
		
		self::clearCountCache();
		
		return true;
	}

	static function maint() {
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog();
		
		$db->ExecuteMaster("DELETE FROM notification WHERE is_read = 1");
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' notification records.');
		
		// Fire event
		$eventMgr = DevblocksPlatform::getEventService();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'context.maint',
				array(
					'context' => CerberusContexts::CONTEXT_NOTIFICATION,
					'context_table' => 'notification',
					'context_key' => 'id',
				)
			)
		);
	}
	
	static function clearCountCache($worker_id=null) {
		$cache = DevblocksPlatform::getCacheService();
		
		$workers = array();
		
		// If we weren't given a worker, use all active workers
		if(is_null($worker_id)) {
			$workers = DAO_Worker::getAllActive();
			
		// Otherwise, if we were given a specific worker, just use them
		} else {
			
			if(false != ($worker = DAO_Worker::get($worker_id)))
				$workers = array($worker->id => $worker);
		}
		
		foreach($workers as $worker_id => $worker)
			$cache->remove(self::CACHE_COUNT_PREFIX.$worker_id);
	}

	public static function random() {
		return self::_getRandom('notification');
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Notification::getFields();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy])) // || !in_array($sortBy,$columns))
			$sortBy=null;

		list($tables,$wheres) = parent::_parseSearchParams($params, array(),$fields,$sortBy);
		
		$select_sql = sprintf("SELECT ".
			"we.id as %s, ".
			"we.context as %s, ".
			"we.context_id as %s, ".
			"we.created_date as %s, ".
			"we.worker_id as %s, ".
			"we.is_read as %s, ".
			"we.activity_point as %s, ".
			"we.entry_json as %s ",
				SearchFields_Notification::ID,
				SearchFields_Notification::CONTEXT,
				SearchFields_Notification::CONTEXT_ID,
				SearchFields_Notification::CREATED_DATE,
				SearchFields_Notification::WORKER_ID,
				SearchFields_Notification::IS_READ,
				SearchFields_Notification::ACTIVITY_POINT,
				SearchFields_Notification::ENTRY_JSON
		);
			
		$join_sql = "FROM notification we ";
			
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
		
		$sort_sql = (!empty($sortBy) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ");

		$result = array(
			'primary_table' => 'we',
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'has_multiple_values' => false,
			'sort' => $sort_sql,
		);
		
		return $result;
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
		
		$sql =
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY we.id ' : '').
			$sort_sql;
		
		// [TODO] Could push the select logic down a level too
		if($limit > 0) {
			$rs = $db->SelectLimit($sql,$limit,$page*$limit) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		} else {
			$rs = $db->ExecuteSlave($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
			$total = mysqli_num_rows($rs);
		}
		
		$results = array();
		
		while($row = mysqli_fetch_assoc($rs)) {
			$object_id = intval($row[SearchFields_Notification::ID]);
			$results[$object_id] = $row;
		}

		$total = count($results);
		
		if($withCounts) {
			// We can skip counting if we have a less-than-full single page
			if(!(0 == $page && $total < $limit)) {
				$count_sql =
					($has_multiple_values ? "SELECT COUNT(DISTINCT we.id) " : "SELECT COUNT(we.id) ").
					$join_sql.
					$where_sql;
				$total = $db->GetOneSlave($count_sql);
			}
		}
		
		mysqli_free_result($rs);
		
		return array($results,$total);
	}
	
};

class SearchFields_Notification implements IDevblocksSearchFields {
	// Worker Event
	const ID = 'we_id';
	const CONTEXT = 'we_context';
	const CONTEXT_ID = 'we_context_id';
	const CREATED_DATE = 'we_created_date';
	const WORKER_ID = 'we_worker_id';
	const IS_READ = 'we_is_read';
	const ACTIVITY_POINT = 'we_activity_point';
	const ENTRY_JSON = 'we_entry_json';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'we', 'id', $translate->_('common.id')),
			self::CONTEXT => new DevblocksSearchField(self::CONTEXT, 'we', 'context', null),
			self::CONTEXT_ID => new DevblocksSearchField(self::CONTEXT_ID, 'we', 'context_id', null),
			self::CREATED_DATE => new DevblocksSearchField(self::CREATED_DATE, 'we', 'created_date', $translate->_('common.created'), Model_CustomField::TYPE_DATE),
			self::WORKER_ID => new DevblocksSearchField(self::WORKER_ID, 'we', 'worker_id', $translate->_('notification.worker_id'), Model_CustomField::TYPE_WORKER),
			self::IS_READ => new DevblocksSearchField(self::IS_READ, 'we', 'is_read', $translate->_('notification.is_read'), Model_CustomField::TYPE_CHECKBOX),
			self::ACTIVITY_POINT => new DevblocksSearchField(self::ACTIVITY_POINT, 'we', 'activity_point', $translate->_('dao.context_activity_log.activity_point'), Model_CustomField::TYPE_SINGLE_LINE),
			self::ENTRY_JSON => new DevblocksSearchField(self::ENTRY_JSON, 'we', 'entry_json', $translate->_('dao.context_activity_log.entry'), Model_CustomField::TYPE_MULTI_LINE),
		);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;
	}
};

class Model_Notification {
	public $id;
	public $context;
	public $context_id;
	public $created_date;
	public $worker_id;
	public $is_read;
	public $activity_point;
	public $entry_json;
	
	public function getURL() {
		$url = null;
		
		// Invoke context class
		if(!empty($this->context)) {
			if(null != ($ctx = Extension_DevblocksContext::get($this->context))) { /* @var $ctx Extension_DevblocksContext */
				if($ctx instanceof IDevblocksContextProfile) { /* @var $ctx IDevblocksContextProfile */
					$url = $ctx->profileGetUrl($this->context_id);
					
				} else {
					$meta = $ctx->getMeta($this->context_id);
					if(isset($meta['permalink']) && !empty($meta['permalink']))
						$url = $meta['permalink'];
				}
			}
		}
		
		if(empty($url)) {
			$url_writer = DevblocksPlatform::getUrlService();
			$url = $url_writer->write('c=profiles&obj=worker&who=me&what=notifications', true);
		}
		
		return $url;
	}
	
	function markRead() {
		DAO_Notification::update($this->id, array(
			DAO_Notification::IS_READ => 1,
		));
		
		DAO_Notification::clearCountCache($this->worker_id);
	}
};

class View_Notification extends C4_AbstractView implements IAbstractView_Subtotals, IAbstractView_QuickSearch {
	const DEFAULT_ID = 'notifications';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Notifications';
		$this->renderLimit = 100;
		$this->renderSortBy = SearchFields_Notification::CREATED_DATE;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_Notification::CREATED_DATE,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_Notification::CONTEXT,
			SearchFields_Notification::CONTEXT_ID,
			SearchFields_Notification::ENTRY_JSON,
			SearchFields_Notification::ID,
		));
		
		$this->addParamsHidden(array(
			SearchFields_Notification::CONTEXT,
			SearchFields_Notification::CONTEXT_ID,
			SearchFields_Notification::ENTRY_JSON,
			SearchFields_Notification::ID,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_Notification::search(
			$this->view_columns,
			$this->getParams(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		return $objects;
	}

	function getDataAsObjects($ids=null) {
		return $this->_getDataAsObjects('DAO_Notification', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_Notification', $size);
	}
	
	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable(true);
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// DAO
				case SearchFields_Notification::ACTIVITY_POINT:
				case SearchFields_Notification::IS_READ:
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
			case SearchFields_Notification::ACTIVITY_POINT:
				$label_map = array();
				$translate = DevblocksPlatform::getTranslationService();
				
				$activities = DevblocksPlatform::getActivityPointRegistry();
				if(is_array($activities))
				foreach($activities as $k => $data) {
					@$string_id = $data['params']['label_key'];
					if(!empty($string_id)) {
						$label_map[$k] = $translate->_($string_id);
					}
				}
				$counts = $this->_getSubtotalCountForStringColumn('DAO_Notification', $column, $label_map, 'in', 'options[]');
				break;
				
			case SearchFields_Notification::IS_READ:
				$counts = $this->_getSubtotalCountForBooleanColumn('DAO_Notification', $column);
				break;
			
			default:
				// Custom fields
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn('DAO_Notification', $column, 'n.id');
				}
				
				break;
		}
		
		return $counts;
	}
	
	function getQuickSearchFields() {
		$fields = array(
			'_fulltext' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Notification::ACTIVITY_POINT, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'activity' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_TEXT,
					'options' => array('param_key' => SearchFields_Notification::ACTIVITY_POINT, 'match' => DevblocksSearchCriteria::OPTION_TEXT_PARTIAL),
				),
			'created' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_DATE,
					'options' => array('param_key' => SearchFields_Notification::CREATED_DATE),
				),
			'id' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_NUMBER,
					'options' => array('param_key' => SearchFields_Notification::ID),
				),
			'isRead' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_BOOL,
					'options' => array('param_key' => SearchFields_Notification::IS_READ),
				),
			'worker' => 
				array(
					'type' => DevblocksSearchCriteria::TYPE_WORKER,
					'options' => array('param_key' => SearchFields_Notification::WORKER_ID),
				),
		);
		
		// Add searchable custom fields
		
		$fields = self::_appendFieldsFromQuickSearchContext(CerberusContexts::CONTEXT_NOTIFICATION, $fields, null);
		
		// Sort by keys
		
		ksort($fields);
		
		return $fields;
	}	
	
	function getParamsFromQuickSearchFields($fields) {
		$search_fields = $this->getQuickSearchFields();
		$params = DevblocksSearchCriteria::getParamsFromQueryFields($fields, $search_fields);

		// Handle virtual fields and overrides
		if(is_array($fields))
		foreach($fields as $k => $v) {
			switch($k) {
				// ...
			}
		}
		
		$this->renderPage = 0;
		$this->addParams($params, true);
		
		return $params;
	}
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$tpl->assign('view_template', 'devblocks:cerberusweb.core::preferences/tabs/notifications/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		switch($field) {
			case 'placeholder_string':
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
			case SearchFields_Notification::IS_READ:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
			case SearchFields_Notification::CREATED_DATE:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
			case SearchFields_Notification::WORKER_ID:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__context_worker.tpl');
				break;
			case SearchFields_Notification::ACTIVITY_POINT:
				$activities = DevblocksPlatform::getActivityPointRegistry();
				$options = array();
				
				foreach($activities as $activity_id => $activity) {
					if(isset($activity['params']['label_key']))
						$options[$activity_id] = $activity['params']['label_key'];
				}
				
				$tpl->assign('options', $options);
				
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__list.tpl');
				break;
			default:
				echo '';
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_Notification::IS_READ:
				$this->_renderCriteriaParamBoolean($param);
				break;
				
			case SearchFields_Notification::WORKER_ID:
				$this->_renderCriteriaParamWorker($param);
				break;
				
			case SearchFields_Notification::ACTIVITY_POINT:
				$strings = array();
				
				$activities = DevblocksPlatform::getActivityPointRegistry();
				$translate = DevblocksPlatform::getTranslationService();
				
				if(is_array($values))
				foreach($values as $v) {
					$string = $v;
					if(isset($activities[$v])) {
						@$string_id = $activities[$v]['params']['label_key'];
						if(!empty($string_id))
							$string = $translate->_($string_id);
					}
					
					$strings[] = $string;
				}
				
				return implode(' or ', $strings);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_Notification::getFields();
	}

	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case 'placeholder_string':
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_Notification::WORKER_ID:
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_ids);
				break;
				
			case SearchFields_Notification::CREATED_DATE:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_Notification::IS_READ:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_Notification::ACTIVITY_POINT:
				@$options = DevblocksPlatform::importGPC($_REQUEST['options'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,DevblocksSearchCriteria::OPER_IN,$options);
				break;
		}

		if(!empty($criteria)) {
			$this->addParam($criteria);
			$this->renderPage = 0;
		}
	}
	
	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(600); // 10m
		
		$change_fields = array();
//		$custom_fields = array();

		// Make sure we have actions
		if(empty($do))
			return;

		// Make sure we have checked items if we want a checked list
		if(0 == strcasecmp($filter,"checks") && empty($ids))
			return;
			
		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				case 'is_read':
					if(1==intval($v)) {
						$change_fields[DAO_Notification::IS_READ] = 1;
					} else { // active
						$change_fields[DAO_Notification::IS_READ] = 0;
					}
					break;
				default:
					// Custom fields
//					if(substr($k,0,3)=="cf_") {
//						$custom_fields[substr($k,3)] = $v;
//					}
			}
		}
		
		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_Notification::search(
				array(),
				$this->getParams(),
				100,
				$pg++,
				SearchFields_Notification::ID,
				true,
				false
			);
			 
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			DAO_Notification::update($batch_ids, $change_fields);
			
			// Custom Fields
			//self::_doBulkSetCustomFields(CerberusContexts::CONTEXT_TASK, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		if(isset($change_fields[DAO_Notification::IS_READ])) {
			if(null != ($active_worker = CerberusApplication::getActiveWorker()))
				DAO_Notification::clearCountCache($active_worker->id);
		}
		
		unset($ids);
	}
};

class Context_Notification extends Extension_DevblocksContext {
	function authorize($context_id, Model_Worker $worker) {
		// Security
		try {
			if(empty($worker))
				throw new Exception();
			
			if($worker->is_superuser)
				return TRUE;
				
			if(null == ($notification = DAO_Notification::get($context_id)))
				throw new Exception();
				
			return $notification->worker_id == $worker->id;
				
		} catch (Exception $e) {
			// Fail
		}
		
		return FALSE;
	}
	
	function getRandom() {
		return DAO_Notification::random();
	}
	
	function getMeta($context_id) {
		$notification = DAO_Notification::get($context_id);
		$url_writer = DevblocksPlatform::getUrlService();
		
		if(false == ($url = $notification->getURL())) {
			$url = $url_writer->writeNoProxy('c=preferences&action=redirectRead&id='.$context_id, true);
		}
		
		return array(
			'id' => $notification->id,
			'name' => CerberusContexts::formatActivityLogEntry(json_decode($notification->entry_json, true),'html'),
			'permalink' => $url,
		);
	}
	
	function getPropertyLabels(DevblocksDictionaryDelegate $dict) {
		$labels = $dict->_labels;
		$prefix = $labels['_label'];
		
		if(!empty($prefix)) {
			array_walk($labels, function(&$label, $key) use ($prefix) {
				$label = preg_replace(sprintf("#^%s #", preg_quote($prefix)), '', $label);
				
				switch($key) {
				}
				
				$label = mb_convert_case($label, MB_CASE_LOWER);
				$label[0] = mb_convert_case($label[0], MB_CASE_UPPER);
			});
		}
		
		asort($labels);
		
		return $labels;
	}
	
	// [TODO] Interface
	function getDefaultProperties() {
		return array(
			'assignee__label',
			'target__label',
			'activity_point',
			'created',
			'is_read',
		);
	}
	
	function getContext($notification, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Notification:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_NOTIFICATION);
		$url_writer = DevblocksPLatform::getUrlService();

		// Polymorph
		if(is_numeric($notification)) {
			$notification = DAO_Notification::get($notification);
		} elseif($notification instanceof Model_Notification) {
			// It's what we want already.
		} elseif(is_array($notification)) {
			$notification = Cerb_ORMHelper::recastArrayToModel($notification, 'Model_Notification');
		} else {
			$notification = null;
		}
		
		// Token labels
		$token_labels = array(
			'_label' => $prefix,
			'id' => $prefix.$translate->_('common.id'),
			'created' => $prefix.$translate->_('common.created'),
			'is_read' => $prefix.'is read',
			'target__label' => $prefix.$translate->_('common.target'),
			'activity_point' => $prefix.$translate->_('dao.context_activity_log.activity_point'),
			'message' => $prefix.$translate->_('common.message'),
			'message_html' => $prefix.'Message (HTML)',
			'url' => $prefix.$translate->_('common.url'),
		);
		
		// Token types
		$token_types = array(
			'_label' => 'context_url',
			'id' => Model_CustomField::TYPE_NUMBER,
			'created' => Model_CustomField::TYPE_DATE,
			'is_read' => Model_CustomField::TYPE_CHECKBOX,
			'target__label' => 'context_url',
			'activity_point' => Model_CustomField::TYPE_SINGLE_LINE,
			'message' => Model_CustomField::TYPE_SINGLE_LINE,
			'message_html' => Model_CustomField::TYPE_SINGLE_LINE,
			'url' => Model_CustomField::TYPE_URL,
		);
		
		// Custom field/fieldset token labels
		if(false !== ($custom_field_labels = $this->_getTokenLabelsFromCustomFields($fields, $prefix)) && is_array($custom_field_labels))
			$token_labels = array_merge($token_labels, $custom_field_labels);
		
		// Custom field/fieldset token types
		if(false !== ($custom_field_types = $this->_getTokenTypesFromCustomFields($fields, $prefix)) && is_array($custom_field_types))
			$token_types = array_merge($token_types, $custom_field_types);
		
		// Token values
		$token_values = array();
		
		$token_values['_context'] = CerberusContexts::CONTEXT_NOTIFICATION;
		$token_values['_types'] = $token_types;
		
		if($notification) {
			$entry = json_decode($notification->entry_json, true);
			
			$token_values['_loaded'] = true;
			$token_values['_label'] = trim(strtr(CerberusContexts::formatActivityLogEntry($entry,'text'),"\r\n",' '));
			$token_values['activity_point'] = $notification->activity_point;
			$token_values['created'] = $notification->created_date;
			$token_values['id'] = $notification->id;
			$token_values['is_read'] = $notification->is_read;
			$token_values['message'] = CerberusContexts::formatActivityLogEntry($entry,'text');
			$token_values['message_html'] = CerberusContexts::formatActivityLogEntry($entry,'html');
			$token_values['url'] = $notification->getURL();
			
			$token_values['target__context'] = $notification->context;
			$token_values['target_id'] = $notification->context_id;
			
			// Custom fields
			$token_values = $this->_importModelCustomFieldsAsValues($notification, $token_values);
			
			// Url
			$redirect_url = $url_writer->writeNoProxy(sprintf("c=preferences&a=redirectRead&id=%d", $notification->id), true);
			$token_values['url_markread'] = $redirect_url;
			
			// Assignee
			@$assignee_id = $notification->worker_id;
			$token_values['assignee_id'] = $assignee_id;
		}

		// Assignee
		$merge_token_labels = array();
		$merge_token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, null, $merge_token_labels, $merge_token_values, '', true);

		CerberusContexts::merge(
			'assignee_',
			$prefix.'Assignee:',
			$merge_token_labels,
			$merge_token_values,
			$token_labels,
			$token_values
		);
		
		return true;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_NOTIFICATION;
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
					$fields = $this->_lazyLoadCustomFields($token, $context, $context_id);
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
		$view->name = 'Notifications';
		
		$params = array(
			SearchFields_Notification::IS_READ => new DevblocksSearchCriteria(SearchFields_Notification::IS_READ, '=', 0),
		);
				
		if(!empty($active_worker)) {
			$params[SearchFields_Notification::WORKER_ID] = new DevblocksSearchCriteria(SearchFields_Notification::WORKER_ID,'in',array($active_worker->id));
		}
		
		$view->addParams($params, true);
		$view->addParamsDefault($params, true);
		$view->addParamsRequired(array(), true);
		
		$view->renderSortBy = SearchFields_Notification::CREATED_DATE;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderFilters = false;
		$view->renderTemplate = 'contextlinks_chooser';
		return $view;
	}
	
	function getView($context=null, $context_id=null, $options=array(), $view_id=null) {
		$view_id = !empty($view_id) ? $view_id : str_replace('.','_',$this->id);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Notifications';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
				new DevblocksSearchCriteria(SearchFields_Notification::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_Notification::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		return $view;
	}
};