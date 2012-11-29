<?php
/***********************************************************************
| Cerb(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2012, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/

class DAO_Notification extends DevblocksORMHelper {
	const CACHE_COUNT_PREFIX = 'notification_count_';
	
	const ID = 'id';
	const CONTEXT = 'context';
	const CONTEXT_ID = 'context_id';
	const CREATED_DATE = 'created_date';
	const WORKER_ID = 'worker_id';
	const MESSAGE = 'message';
	const IS_READ = 'is_read';
	const URL = 'url';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("INSERT INTO notification () ".
			"VALUES ()"
		);
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		// If a worker was provided
		if(isset($fields[self::WORKER_ID])) {
			// Invalidate the worker notification count cache
			$cache = DevblocksPlatform::getCacheService();
			self::clearCountCache($fields[self::WORKER_ID]);
			
			// Trigger notification
			Event_NotificationReceivedByWorker::trigger($id, $fields[self::WORKER_ID]);
		}
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'notification', $fields);
		
		// Log the context update
		DevblocksPlatform::markContextChanged(CerberusContexts::CONTEXT_NOTIFICATION, $ids);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('notification', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @return Model_Notification[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, context, context_id, created_date, worker_id, message, is_read, url ".
			"FROM notification ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY id desc";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_Notification	 */
	static function get($id) {
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
			
			$count = intval($db->GetOne($sql));
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
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_Notification();
			$object->id = $row['id'];
			$object->context = $row['context'];
			$object->context_id = $row['context_id'];
			$object->created_date = $row['created_date'];
			$object->worker_id = $row['worker_id'];
			$object->message = $row['message'];
			$object->url = $row['url'];
			$object->is_read = $row['is_read'];
			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM notification WHERE id IN (%s)", $ids_list));
		
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
		
		return true;
	}
	
	static function deleteByContext($context, $context_ids) {
		if(!is_array($context_ids)) 
			$context_ids = array($context_ids);
		
		if(empty($context_ids))
			return;
			
		$db = DevblocksPlatform::getDatabaseService();
		
		$db->Execute(sprintf("DELETE FROM notification WHERE context = %s AND context_id IN (%s) ", 
			$db->qstr($context),
			implode(',', $context_ids)
		));
		
		return true;
	}

	static function maint() {
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog();
		
		$db->Execute("DELETE QUICK FROM notification WHERE is_read = 1");
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
	
	static function clearCountCache($worker_id) {
		$cache = DevblocksPlatform::getCacheService();
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
			"we.message as %s, ".
			"we.is_read as %s, ".
			"we.url as %s ",
				SearchFields_Notification::ID,
				SearchFields_Notification::CONTEXT,
				SearchFields_Notification::CONTEXT_ID,
				SearchFields_Notification::CREATED_DATE,
				SearchFields_Notification::WORKER_ID,
				SearchFields_Notification::MESSAGE,
				SearchFields_Notification::IS_READ,
				SearchFields_Notification::URL
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
	static function search($params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();

		// Build search queries
		$query_parts = self::getSearchQueryComponents(array(),$params,$sortBy,$sortAsc);

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
			$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
			$total = mysql_num_rows($rs);
		}
		
		$results = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$ticket_id = intval($row[SearchFields_Notification::ID]);
			$results[$ticket_id] = $result;
		}

		// [JAS]: Count all
		$total = -1;
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT we.id) " : "SELECT COUNT(we.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
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
	const MESSAGE = 'we_message';
	const IS_READ = 'we_is_read';
	const URL = 'we_url';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'we', 'id', $translate->_('notification.id')),
			self::CONTEXT => new DevblocksSearchField(self::CONTEXT, 'we', 'context', null),
			self::CONTEXT_ID => new DevblocksSearchField(self::CONTEXT_ID, 'we', 'context_id', null),
			self::CREATED_DATE => new DevblocksSearchField(self::CREATED_DATE, 'we', 'created_date', $translate->_('notification.created_date'), Model_CustomField::TYPE_DATE),
			self::WORKER_ID => new DevblocksSearchField(self::WORKER_ID, 'we', 'worker_id', $translate->_('notification.worker_id'), Model_CustomField::TYPE_WORKER),
			self::MESSAGE => new DevblocksSearchField(self::MESSAGE, 'we', 'message', $translate->_('notification.message'), Model_CustomField::TYPE_SINGLE_LINE),
			self::IS_READ => new DevblocksSearchField(self::IS_READ, 'we', 'is_read', $translate->_('notification.is_read'), Model_CustomField::TYPE_CHECKBOX),
			self::URL => new DevblocksSearchField(self::URL, 'we', 'url', $translate->_('common.url'), Model_CustomField::TYPE_SINGLE_LINE),
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
	public $message;
	public $is_read;
	public $url;
	
	public function getURL() {
		$url = $this->url;
		
		if(substr($this->url,0,6) == 'ctx://') {
			$url = CerberusContexts::parseContextUrl($this->url);
			
		// Check if we have a context link, otherwise use raw URL
		} elseif(!empty($this->context)) {
			// Invoke context class
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
		
		return $url;
	}
};

class View_Notification extends C4_AbstractView implements IAbstractView_Subtotals {
	const DEFAULT_ID = 'notifications';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Notifications';
		$this->renderLimit = 100;
		$this->renderSortBy = SearchFields_Notification::CREATED_DATE;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_Notification::CREATED_DATE,
			SearchFields_Notification::MESSAGE,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_Notification::CONTEXT,
			SearchFields_Notification::CONTEXT_ID,
			SearchFields_Notification::ID,
		));
		
		$this->addParamsHidden(array(
			SearchFields_Notification::CONTEXT,
			SearchFields_Notification::CONTEXT_ID,
			SearchFields_Notification::ID,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_Notification::search(
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
		$all_fields = $this->getParamsAvailable();
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// DAO
				case SearchFields_Notification::IS_READ:
				case SearchFields_Notification::URL:
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
			case SearchFields_Notification::URL:
				$url_writer = DevblocksPlatform::getUrlService();
				$base_url = $url_writer->writeNoProxy('', true);
				
				$counts = $this->_getSubtotalCountForStringColumn('DAO_Notification', $column);
				
				foreach($counts as $k => $v) {
					@$counts[$k]['label'] = str_replace($base_url, '', $v['label']);
					
					if($k == '(none)') {
						@$counts[$k]['filter']['values'] = array('value' => '');
					} else {
						@$counts[$k]['filter']['values'] = array('value' => $k);
					}
				}
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
			case SearchFields_Notification::MESSAGE:
			case SearchFields_Notification::URL:
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
			case SearchFields_Notification::MESSAGE:
			case SearchFields_Notification::URL:
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
				//array(),
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
		
		$url = null;
		
		if(!empty($notification->context)) {
			$url = $notification->getURL();
		}
		
		if(empty($url)) {
			$url = $url_writer->writeNoProxy('c=preferences&action=redirectRead&id='.$context_id, true);
		}
		
		return array(
			'id' => $notification->id,
			'name' => $notification->message,
			'permalink' => $url,
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
		} else {
			$notification = null;
		}
		
		// Token labels
		$token_labels = array(
			'id' => $prefix.$translate->_('common.id'),
			'context' => $prefix.$translate->_('common.context'),
			'context_id' => $prefix.$translate->_('common.context_id'),
			'created|date' => $prefix.$translate->_('common.created'),
			'message' => $prefix.'message',
			'is_read' => $prefix.'is read',
			'url' => $prefix.$translate->_('common.url'),
		);
		
		if(is_array($fields))
		foreach($fields as $cf_id => $field) {
			$token_labels['custom_'.$cf_id] = $prefix.$field->name;
		}

		// Token values
		$token_values = array();
		
		$token_values['_context'] = CerberusContexts::CONTEXT_NOTIFICATION;
		
		if($notification) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = trim(strtr($notification->message,"\r\n",' '));
			$token_values['id'] = $notification->id;
			$token_values['context'] = $notification->context;
			$token_values['context_id'] = $notification->context_id;
			$token_values['created'] = $notification->created_date;
			$token_values['message'] = $notification->message;
			$token_values['is_read'] = $notification->is_read;
			$token_values['url'] = $notification->url; //$notification->getURL();
			
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
			'Assignee:',
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
			CerberusContexts::getContext($context, $context_id, $labels, $values);
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
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;		
	}
	
	function getView($context=null, $context_id=null, $options=array()) {
		$view_id = str_replace('.','_',$this->id);
		
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
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
};