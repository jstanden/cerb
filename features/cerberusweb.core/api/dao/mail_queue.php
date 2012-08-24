<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2012, WebGroup Media LLC
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

class DAO_MailQueue extends DevblocksORMHelper {
	const ID = 'id';
	const WORKER_ID = 'worker_id';
	const UPDATED = 'updated';
	const TYPE = 'type';
	const TICKET_ID = 'ticket_id';
	const HINT_TO = 'hint_to';
	const SUBJECT = 'subject';
	const BODY = 'body';
	const PARAMS_JSON = 'params_json';
	const IS_QUEUED = 'is_queued';
	const QUEUE_FAILS = 'queue_fails';
	const QUEUE_DELIVERY_DATE = 'queue_delivery_date';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "INSERT INTO mail_queue () VALUES ()";
		$db->Execute($sql);
		$id = $db->LastInsertId();
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'mail_queue', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('mail_queue', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_MailQueue[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, worker_id, updated, type, ticket_id, hint_to, subject, body, params_json, is_queued, queue_delivery_date, queue_fails ".
			"FROM mail_queue ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_MailQueue	 */
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
	 * @return Model_MailQueue[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_MailQueue();
			$object->id = $row['id'];
			$object->worker_id = $row['worker_id'];
			$object->updated = $row['updated'];
			$object->type = $row['type'];
			$object->ticket_id = $row['ticket_id'];
			$object->hint_to = $row['hint_to'];
			$object->subject = $row['subject'];
			$object->body = $row['body'];
			$object->is_queued = $row['is_queued'];
			$object->queue_delivery_date = $row['queue_delivery_date'];
			$object->queue_fails = $row['queue_fails'];
			
			// Unserialize params
			$params_json = $row['params_json'];
			if(!empty($params_json))
				$object->params = json_decode($params_json, true);
			
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
		
		$db->Execute(sprintf("DELETE FROM mail_queue WHERE id IN (%s)", $ids_list));
		
		return true;
	}
	
	public static function getSearchQueryComponents($columns, $params, $sortBy=null, $sortAsc=null) {
		$fields = SearchFields_MailQueue::getFields();
		
		// Sanitize
		if('*'==substr($sortBy,0,1) || !isset($fields[$sortBy]) || (!empty($columns) && !in_array($sortBy, $columns)))
			$sortBy=null;
		
        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		
		$select_sql = sprintf("SELECT ".
			"mail_queue.id as %s, ".
			"mail_queue.worker_id as %s, ".
			"mail_queue.updated as %s, ".
			"mail_queue.type as %s, ".
			"mail_queue.ticket_id as %s, ".
			"mail_queue.hint_to as %s, ".
			"mail_queue.subject as %s, ".
			"mail_queue.is_queued as %s, ".
			"mail_queue.queue_delivery_date as %s, ".
			"mail_queue.queue_fails as %s ",
				SearchFields_MailQueue::ID,
				SearchFields_MailQueue::WORKER_ID,
				SearchFields_MailQueue::UPDATED,
				SearchFields_MailQueue::TYPE,
				SearchFields_MailQueue::TICKET_ID,
				SearchFields_MailQueue::HINT_TO,
				SearchFields_MailQueue::SUBJECT,
				SearchFields_MailQueue::IS_QUEUED,
				SearchFields_MailQueue::QUEUE_DELIVERY_DATE,
				SearchFields_MailQueue::QUEUE_FAILS
			);
			
		$join_sql = "FROM mail_queue ";
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "WHERE 1 ");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
		
		$result = array(
			'primary_table' => 'mail_queue',
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
			($has_multiple_values ? 'GROUP BY mail_queue.id ' : '').
			$sort_sql;
			
		// [TODO] Could push the select logic down a level too
		if($limit > 0) {
    		$rs = $db->SelectLimit($sql,$limit,$page*$limit) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		} else {
		    $rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
            $total = mysql_num_rows($rs);
		}
		
		$results = array();
		$total = -1;
		
		while($row = mysql_fetch_assoc($rs)) {
			$result = array();
			foreach($row as $f => $v) {
				$result[$f] = $v;
			}
			$object_id = intval($row[SearchFields_MailQueue::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT mail_queue.id) " : "SELECT COUNT(mail_queue.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}
};

class SearchFields_MailQueue implements IDevblocksSearchFields {
	const ID = 'm_id';
	const WORKER_ID = 'm_worker_id';
	const UPDATED = 'm_updated';
	const TYPE = 'm_type';
	const TICKET_ID = 'm_ticket_id';
	const HINT_TO = 'm_hint_to';
	const SUBJECT = 'm_subject';
	const IS_QUEUED = 'm_is_queued';
	const QUEUE_DELIVERY_DATE = 'm_queue_delivery_date';
	const QUEUE_FAILS = 'm_queue_fails';
//	const BODY = 'm_body';
//	const PARAMS_JSON = 'm_params_json';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'mail_queue', 'id', $translate->_('common.id')),
			self::WORKER_ID => new DevblocksSearchField(self::WORKER_ID, 'mail_queue', 'worker_id', ucwords($translate->_('common.worker'))),
			self::UPDATED => new DevblocksSearchField(self::UPDATED, 'mail_queue', 'updated', ucwords($translate->_('common.updated'))),
			self::TYPE => new DevblocksSearchField(self::TYPE, 'mail_queue', 'type', $translate->_('mail_queue.type')),
			self::TICKET_ID => new DevblocksSearchField(self::TICKET_ID, 'mail_queue', 'ticket_id', $translate->_('mail_queue.ticket_id')),
			self::HINT_TO => new DevblocksSearchField(self::HINT_TO, 'mail_queue', 'hint_to', $translate->_('message.header.to')),
			self::SUBJECT => new DevblocksSearchField(self::SUBJECT, 'mail_queue', 'subject', $translate->_('message.header.subject')),
			self::IS_QUEUED => new DevblocksSearchField(self::IS_QUEUED, 'mail_queue', 'is_queued', $translate->_('mail_queue.is_queued')),
			self::QUEUE_DELIVERY_DATE => new DevblocksSearchField(self::QUEUE_DELIVERY_DATE, 'mail_queue', 'queue_delivery_date', $translate->_('mail_queue.queue_delivery_date')),
			self::QUEUE_FAILS => new DevblocksSearchField(self::QUEUE_FAILS, 'mail_queue', 'queue_fails', $translate->_('mail_queue.queue_fails')),
//			self::BODY => new DevblocksSearchField(self::BODY, 'mail_queue', 'body', $translate->_('common.content')),
//			self::PARAMS_JSON => new DevblocksSearchField(self::PARAMS_JSON, 'mail_queue', 'params_json', $translate->_('mail_queue.params_json')),
		);
		
		// Sort by label (translation-conscious)
		DevblocksPlatform::sortObjects($columns, 'db_label');

		return $columns;		
	}
};

class Model_MailQueue {
	const TYPE_COMPOSE = 'mail.compose';
	const TYPE_TICKET_FORWARD = 'ticket.forward';
	const TYPE_TICKET_REPLY = 'ticket.reply';
	
	public $id;
	public $worker_id;
	public $updated;
	public $type;
	public $ticket_id;
	public $hint_to;
	public $subject;
	public $body;
	public $params;
	public $is_queued;
	public $queue_delivery_date;
	public $queue_fails;
	
	/**
	 * @return boolean
	 */
	public function send() {
		$success = false;
		
		// Determine the type of message
		switch($this->type) {
			case Model_MailQueue::TYPE_COMPOSE:
				$success = $this->_sendCompose($this->type);
				break;
				
			case Model_MailQueue::TYPE_TICKET_FORWARD:
			case Model_MailQueue::TYPE_TICKET_REPLY:
				$success = $this->_sendTicketReply($this->type);
				break;
		}
		
		if($success) {
			// Delete the draft on success
			DAO_MailQueue::delete($this->id);
		}
		
		return $success;
	}
	
	private function _sendCompose($type) {
		$properties = array(
			'draft_id' => $this->id,
		);

		// From
		if(!isset($this->params['group_id']))
			return false;
		$properties['group_id'] = $this->params['group_id'];

		// To+Cc+Bcc
		if(!isset($this->params['to']))
			return false;
		$properties['to'] = $this->params['to'];
		
		if(isset($this->params['cc']))
			$properties['cc'] = $this->params['cc'];

		if(isset($this->params['bcc']))
			$properties['bcc'] = $this->params['bcc'];

		// Subject
		if(empty($this->subject))
			return false;
		$properties['subject'] = $this->subject;

		// Message body
		if(empty($this->body))
			return false;
		$properties['content'] = $this->body;

		// Next action
		$properties['closed'] = isset($this->params['next_is_closed']) ? intval($this->params['next_is_closed']) : 0; 

		// Org
		if(isset($this->params['org_id'])) {
			$properties['org_id'] = intval($this->params['org_id']);
		}
		
		// Worker
		$properties['worker_id'] = empty($this->worker_id) ? $this->worker_id : 0;
		
		// Files + Next step
		//'files' => $files,

		// Send mail
		if(false == ($ticket_id = CerberusMail::compose($properties)))
			return false;
			
		return true;
	}
	
	private function _sendTicketReply($type) {
		$properties = array(
			'draft_id' => $this->id,
		);
		
		// In reply to message-id
		if(!isset($this->params['in_reply_message_id']))
			return false;
		$properties['message_id'] = $this->params['in_reply_message_id'];

		// Ticket ID
		if(isset($this->ticket_id))
			$properties['ticket_id'] = $this->ticket_id;

		if($type == Model_MailQueue::TYPE_TICKET_FORWARD)
			$properties['is_forward'] = 1;
			
		// Auto-reply
		if(isset($this->params['is_autoreply']) && !empty($this->params['is_autoreply']))
			$properties['is_autoreply'] = true;
			
		// To
		if(isset($this->params['to']))
			$properties['to'] = $this->params['to'];
			
		// Cc+Bcc
		if(isset($this->params['cc']))
			$properties['cc'] = $this->params['cc'];

		if(isset($this->params['bcc']))
			$properties['bcc'] = $this->params['bcc'];
		
		// Subject
		if(!empty($this->subject))
			$properties['subject'] = $this->subject;
			
		// Content
		if(empty($this->body))
			return false;
		$properties['content'] = $this->body;

		// Worker
		$properties['worker_id'] = empty($this->worker_id) ? $this->worker_id : 0;
		
		// Attachments
		// 'files' => $attachment_files,
		// 'files' => @$_FILES['attachment'],
		
//	    'closed' => DevblocksPlatform::importGPC(@$_REQUEST['closed'],'integer',0),
//	    'bucket_id' => DevblocksPlatform::importGPC(@$_REQUEST['bucket_id'],'string',''),
//	    'ticket_reopen' => DevblocksPlatform::importGPC(@$_REQUEST['ticket_reopen'],'string',''),

//	    'forward_files' => DevblocksPlatform::importGPC(@$_REQUEST['forward_files'],'array',array()),
		
		// [TODO] Custom fields
		
		// Send message
		return CerberusMail::sendTicketMessage($properties);
	}
};

class View_MailQueue extends C4_AbstractView implements IAbstractView_Subtotals {
	const DEFAULT_ID = 'mail_queue';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('Mail Queue');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_MailQueue::UPDATED;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_MailQueue::HINT_TO,
			SearchFields_MailQueue::UPDATED,
		);
		
		$this->addColumnsHidden(array(
			SearchFields_MailQueue::TICKET_ID,
		));
		
		$this->addParamsHidden(array(
			SearchFields_MailQueue::ID,
			SearchFields_MailQueue::TICKET_ID,
		));
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_MailQueue::search(
			array(),
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
		return $this->_getDataAsObjects('DAO_MailQueue', $ids);
	}
	
	function getDataSample($size) {
		return $this->_doGetDataSample('DAO_MailQueue', $size);
	}

	function getSubtotalFields() {
		$all_fields = $this->getParamsAvailable();
		
		$fields = array();

		if(is_array($all_fields))
		foreach($all_fields as $field_key => $field_model) {
			$pass = false;
			
			switch($field_key) {
				// DAO
				case SearchFields_MailQueue::TYPE:
				case SearchFields_MailQueue::WORKER_ID:
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
			case SearchFields_MailQueue::TYPE:
				$label_map = array(
					'mail.compose' => 'Compose',
					'ticket.reply' => 'Reply',
				);
				$counts = $this->_getSubtotalCountForStringColumn('DAO_MailQueue', $column, $label_map);
				break;

			case SearchFields_MailQueue::WORKER_ID:
				$label_map = array();
				$workers = DAO_Worker::getAll();
				foreach($workers as $worker_id => $worker)
					$label_map[$worker_id] = $worker->getName();
				$counts = $this->_getSubtotalCountForStringColumn('DAO_MailQueue', $column, $label_map, 'in', 'worker_id[]');
				break;
			
			default:
				// Custom fields
				if('cf_' == substr($column,0,3)) {
					$counts = $this->_getSubtotalCountForCustomColumn('DAO_MailQueue', $column, 'm.id');
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

		$tpl->assign('view_template', 'devblocks:cerberusweb.core::mail/queue/view.tpl');
		$tpl->display('devblocks:cerberusweb.core::internal/views/subtotals_and_view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		switch($field) {
			case SearchFields_MailQueue::HINT_TO:
			case SearchFields_MailQueue::SUBJECT:
			case SearchFields_MailQueue::TYPE:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__string.tpl');
				break;
			case SearchFields_MailQueue::ID:
			case SearchFields_MailQueue::TICKET_ID:
			case SearchFields_MailQueue::QUEUE_FAILS:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__number.tpl');
				break;
			case SearchFields_MailQueue::IS_QUEUED:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__bool.tpl');
				break;
			case SearchFields_MailQueue::QUEUE_DELIVERY_DATE:
			case SearchFields_MailQueue::UPDATED:
				$tpl->display('devblocks:cerberusweb.core::internal/views/criteria/__date.tpl');
				break;
			case SearchFields_MailQueue::WORKER_ID:
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
			case SearchFields_MailQueue::IS_QUEUED:
				$this->_renderCriteriaParamBoolean($param);
				break;
			
			case SearchFields_MailQueue::WORKER_ID:
				$this->_renderCriteriaParamWorker($param);
				break;
				
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	function getFields() {
		return SearchFields_MailQueue::getFields();
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_MailQueue::TYPE:
			case SearchFields_MailQueue::HINT_TO:
			case SearchFields_MailQueue::SUBJECT:
				$criteria = $this->_doSetCriteriaString($field, $oper, $value);
				break;
				
			case SearchFields_MailQueue::ID:
			case SearchFields_MailQueue::TICKET_ID:
			case SearchFields_MailQueue::QUEUE_FAILS:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_MailQueue::QUEUE_DELIVERY_DATE:
			case SearchFields_MailQueue::UPDATED:
				$criteria = $this->_doSetCriteriaDate($field, $oper);
				break;
				
			case SearchFields_MailQueue::IS_QUEUED:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
				
			case SearchFields_MailQueue::WORKER_ID:
				@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_id);
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
				case 'status':
					switch($v) {
						case 'queue':
							$change_fields[DAO_MailQueue::IS_QUEUED] = 1;
							$change_fields[DAO_MailQueue::QUEUE_FAILS] = 0;
							break;
						case 'draft':
							$change_fields[DAO_MailQueue::IS_QUEUED] = 0;
							$change_fields[DAO_MailQueue::QUEUE_FAILS] = 0;
							break;
						case 'delete':
							$deleted = true;
							break;
					}
					break;
				default:
					break;
			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			$params = $this->getParams();
			list($objects,$null) = DAO_MailQueue::search(
				array(),
				$params,
				100,
				$pg++,
				SearchFields_MailQueue::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			if(!$deleted) { 
				DAO_MailQueue::update($batch_ids, $change_fields);
			} else {
				DAO_MailQueue::delete($batch_ids);
			}
			
			unset($batch_ids);
		}

		unset($ids);
	}			
};

class Context_Draft extends Extension_DevblocksContext {
	function getMeta($context_id) {
		if(null == ($draft = DAO_MailQueue::get($context_id)))
			return false;
		
		//$url = $this->profileGetUrl($context_id);
		//$friendly = DevblocksPlatform::strToPermalink($task->title);
		
// 		if(!empty($friendly))
// 			$url .= '-' . $friendly;
		
		return array(
			'id' => $context_id,
			'name' => $draft->subject,
			'permalink' => '',
		);
	}
	
	function getRandom() {
		//return DAO_MailQueue::random();
	}
	
	function getContext($object, &$token_labels, &$token_values, $prefix=null) {
		if(is_null($prefix))
			$prefix = 'Draft:';
		
		$translate = DevblocksPlatform::getTranslationService();
		$fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_DRAFT);

		// Polymorph
		if(is_numeric($object)) {
			$object = DAO_MailQueue::get($object);
		} elseif($object instanceof Model_MailQueue) {
			// It's what we want already.
		} else {
			$object = null;
		}
		
		// Token labels
		$token_labels = array(
			'content' => $prefix.$translate->_('common.content'),
			'id' => $prefix.$translate->_('common.id'),
			'subject' => $prefix.$translate->_('message.header.subject'),
			'to' => $prefix.$translate->_('message.header.to'),
			'updated|date' => $prefix.$translate->_('common.updated'),
		);
		
		// Token values
		$token_values = array();
		
		$token_values['_context'] = CerberusContexts::CONTEXT_DRAFT;
		
		if($object) {
			$token_values['_loaded'] = true;
			$token_values['_label'] = $object->subject;
			$token_values['content'] = $object->body;
			$token_values['id'] = $object->id;
			$token_values['subject'] = $object->subject;
			$token_values['to'] = $object->hint_to;
			$token_values['updated'] = $object->updated;
		}

		return true;
	}

	function lazyLoadContextValues($token, $dictionary) {
		if(!isset($dictionary['id']))
			return;
		
		$context = CerberusContexts::CONTEXT_DRAFT;
		$context_id = $dictionary['id'];
		
		@$is_loaded = $dictionary['_loaded'];
		$values = array();
		
		if(!$is_loaded) {
			$labels = array();
			CerberusContexts::getContext($context, $context_id, $labels, $values);
		}
		
		switch($token) {
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
		$view->name = 'Drafts';
		
		$view->view_columns = array(
			SearchFields_MailQueue::HINT_TO,
			SearchFields_MailQueue::UPDATED,
			SearchFields_MailQueue::TYPE,
		);
		
		$view->addColumnsHidden(array(
			SearchFields_MailQueue::ID,
			SearchFields_MailQueue::IS_QUEUED,
			SearchFields_MailQueue::QUEUE_FAILS,
			SearchFields_MailQueue::QUEUE_DELIVERY_DATE,
			SearchFields_MailQueue::TICKET_ID,
		));
		
		$view->addParams(array(
			SearchFields_MailQueue::WORKER_ID => new DevblocksSearchCriteria(SearchFields_MailQueue::WORKER_ID, DevblocksSearchCriteria::OPER_EQ, $active_worker->id),
		), true);
		
		$view->addParamsRequired(array(
			SearchFields_MailQueue::IS_QUEUED => new DevblocksSearchCriteria(SearchFields_MailQueue::IS_QUEUED,'=',0),
		), true);
		
		$view->addParamsHidden(array(
			SearchFields_MailQueue::ID,
			SearchFields_MailQueue::IS_QUEUED,
			SearchFields_MailQueue::QUEUE_DELIVERY_DATE,
			SearchFields_MailQueue::QUEUE_FAILS,
			SearchFields_MailQueue::TICKET_ID,
		));
		
		$view->renderSortBy = SearchFields_MailQueue::UPDATED;
		$view->renderSortAsc = false;
		$view->renderLimit = 10;
		$view->renderFilters = false;
		$view->renderTemplate = 'contextlinks_chooser';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;		
	}
	
	function getView($context=null, $context_id=null, $options=array()) {
		// [TODO]
		return NULL;
		
		$view_id = str_replace('.','_',$this->id);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = $view_id; 
		$defaults->class_name = $this->getViewClass();
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = 'Drafts';
		
		$params_req = array();
		
		if(!empty($context) && !empty($context_id)) {
			$params_req = array(
// 				new DevblocksSearchCriteria(SearchFields_MailQueue::CONTEXT_LINK,'=',$context),
// 				new DevblocksSearchCriteria(SearchFields_MailQueue::CONTEXT_LINK_ID,'=',$context_id),
			);
		}
		
		$view->addParamsRequired($params_req, true);
		
		$view->renderTemplate = 'context';
		C4_AbstractViewLoader::setView($view_id, $view);
		return $view;
	}
};