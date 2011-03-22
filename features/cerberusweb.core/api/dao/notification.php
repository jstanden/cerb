<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2011, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerberus Helpdesk Team
 * 
 * Sure, it would be so easy to just cheat and edit this file to use the 
 * software without paying for it.  But we trust you anyway.  In fact, we're 
 * writing this software for you! 
 * 
 * Quality software backed by a dedicated team takes money to develop.  We 
 * don't want to be out of the office bagging groceries when you call up 
 * needing a helping hand.  We'd rather spend our free time coding your 
 * feature requests than mowing the neighbors' lawns for rent money. 
 * 
 * We've never believed in hiding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your inbox that you probably 
 * haven't had since spammers found you in a game of 'E-mail Battleship'. 
 * Miss. Miss. You sunk my inbox!
 * 
 * A legitimate license entitles you to support from the developers,  
 * and the warm fuzzy feeling of feeding a couple of obsessed developers 
 * who want to help you get more done.
 *
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Scott Luther,
 * 		and Jerry Kanoholani. 
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
class DAO_Notification extends DevblocksORMHelper {
	const CACHE_COUNT_PREFIX = 'notification_count_';
	
	const ID = 'id';
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
			Event_NotificationReceivedByOwner::trigger($id, $fields[self::WORKER_ID]);
		}
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'notification', $fields);
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
		
		$sql = "SELECT id, created_date, worker_id, message, is_read, url ".
			"FROM notification ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY id asc";
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
			
			$count = $db->GetOne($sql);
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
		
		return true;
	}

	static function maint() {
		$db = DevblocksPlatform::getDatabaseService();
		$logger = DevblocksPlatform::getConsoleLog();
		
		$db->Execute("DELETE QUICK FROM notification WHERE is_read = 1");
		
		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' notification records.');
	}
	
	static function clearCountCache($worker_id) {
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_COUNT_PREFIX.$worker_id);
	}

	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_Notification::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]) || '*'==substr($sortBy,0,1))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, array(),$fields,$sortBy);
		
		$select_sql = sprintf("SELECT ".
			"we.id as %s, ".
			"we.created_date as %s, ".
			"we.worker_id as %s, ".
			"we.message as %s, ".
			"we.is_read as %s, ".
			"we.url as %s ",
			    SearchFields_Notification::ID,
			    SearchFields_Notification::CREATED_DATE,
			    SearchFields_Notification::WORKER_ID,
			    SearchFields_Notification::MESSAGE,
			    SearchFields_Notification::IS_READ,
			    SearchFields_Notification::URL
		);
			
		$join_sql = "FROM notification we "
//			"INNER JOIN team tm ON (tm.id = t.team_id) ".
//			(isset($tables['ra']) ? "INNER JOIN requester r ON (r.ticket_id=t.id)" : " ").
		;
			
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
			self::CREATED_DATE => new DevblocksSearchField(self::CREATED_DATE, 'we', 'created_date', $translate->_('notification.created_date')),
			self::WORKER_ID => new DevblocksSearchField(self::WORKER_ID, 'we', 'worker_id', $translate->_('notification.worker_id')),
			self::MESSAGE => new DevblocksSearchField(self::MESSAGE, 'we', 'message', $translate->_('notification.message')),
			self::IS_READ => new DevblocksSearchField(self::IS_READ, 'we', 'is_read', $translate->_('notification.is_read')),
			self::URL => new DevblocksSearchField(self::URL, 'we', 'url', $translate->_('common.url')),
		);
		
		// Sort by label (translation-conscious)
		uasort($columns, create_function('$a, $b', "return strcasecmp(\$a->db_label,\$b->db_label);\n"));

		return $columns;		
	}
};

class Model_Notification {
	public $id;
	public $created_date;
	public $worker_id;
	public $message;
	public $is_read;
	public $url;
};

class View_Notification extends C4_AbstractView {
	const DEFAULT_ID = 'notifications';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Worker Events';
		$this->renderLimit = 100;
		$this->renderSortBy = SearchFields_Notification::CREATED_DATE;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_Notification::MESSAGE,
			SearchFields_Notification::CREATED_DATE,
		);
		$this->addColumnsHidden(array(
			SearchFields_Notification::ID,
		));
		
		$this->addParamsHidden(array(
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

	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_Notification', $size);
	}
	
	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$tpl->display('devblocks:cerberusweb.core::preferences/tabs/notifications/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_Notification::MESSAGE:
			case SearchFields_Notification::URL:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
//			case SearchFields_Notification::ID:
//			case SearchFields_Notification::MESSAGE_ID:
//			case SearchFields_Notification::TICKET_ID:
//				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
//				break;
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
			case SearchFields_Notification::WORKER_ID:
				$workers = DAO_Worker::getAll();
				$strings = array();

				foreach($values as $val) {
					if(empty($val))
					$strings[] = "Nobody";
					elseif(!isset($workers[$val]))
					continue;
					else
					$strings[] = $workers[$val]->getName();
				}
				echo implode(", ", $strings);
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
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = $value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case SearchFields_Notification::WORKER_ID:
				@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_ids);
				break;
				
			case SearchFields_Notification::CREATED_DATE:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
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
		@set_time_limit(600); // [TODO] Temp!
	  
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
	
    function getPermalink($context_id) {
    	$url_writer = DevblocksPlatform::getUrlService();
    	return $url_writer->write('c=preferences&action=redirectRead&id='.$context_id, true);
    }

	function getContext($notification, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Notification:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_NOTIFICATION);

		// Polymorph
		if(is_numeric($notification)) {
			$notification = DAO_Notification::get($notification);
		} elseif($notification instanceof Model_Notification) {
			// It's what we want already.
		} else {
			$notification = null;
		}
		
		// Token labels
		// [TODO] Needs to also return META (data type code -- like custom fields)
		$token_labels = array(
			'id' => $prefix.$translate->_('common.id'),
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
		
		if($notification) {
			$token_values['id'] = $notification->id;
			$token_values['created'] = $notification->created_date;
			$token_values['message'] = $notification->message;
			$token_values['is_read'] = $notification->is_read;
			$token_values['url'] = $notification->url;
			
			$token_values['custom'] = array();
			
			$field_values = array_shift(DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_NOTIFICATION, $notification->id));
			if(is_array($field_values) && !empty($field_values)) {
				foreach($field_values as $cf_id => $cf_val) {
					if(!isset($fields[$cf_id]))
						continue;
					
					// The literal value
					if(null != $notification)
						$token_values['custom'][$cf_id] = $cf_val;
					
					// Stringify
					if(is_array($cf_val))
						$cf_val = implode(', ', $cf_val);
						
					if(is_string($cf_val)) {
						if(null != $notification)
							$token_values['custom_'.$cf_id] = $cf_val;
					}
				}
			}
		}

		// Assignee
		@$assignee_id = $notification->worker_id;
		$merge_token_labels = array();
		$merge_token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $assignee_id, $merge_token_labels, $merge_token_values, '', true);

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

	function getChooserView() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		// View
		$view_id = 'chooser_'.str_replace('.','_',$this->id).time().mt_rand(0,9999);
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id;
		$defaults->is_ephemeral = true;
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Notifications';
//		$view->view_columns = array(
//			SearchFields_Message::UPDATED_DATE,
//			SearchFields_Message::DUE_DATE,
//		);
		$view->addParamsRequired(array(
			SearchFields_Notification::WORKER_ID => new DevblocksSearchCriteria(SearchFields_Notification::WORKER_ID,'=',$active_worker->id),
		), true);
		$view->addParams(array(
//			SearchFields_Task::IS_COMPLETED => new DevblocksSearchCriteria(SearchFields_Task::IS_COMPLETED,'=',0),
		), true);
		$view->renderSortBy = SearchFields_Notification::CREATED_DATE;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
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
		
		$params = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params = array(
				new DevblocksSearchCriteria(SearchFields_Notification::CONTEXT_LINK,'=',$context),
				new DevblocksSearchCriteria(SearchFields_Notification::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
//		if(isset($options['filter_open']))
//			$params[] = new DevblocksSearchCriteria(SearchFields_Message::IS_COMPLETED,'=',0);
		
		$view->addParams($params, true);
		
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
};