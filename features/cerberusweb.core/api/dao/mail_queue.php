<?php
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
	const QUEUE_PRIORITY = 'queue_priority';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('mail_queue_seq');
		
		$sql = sprintf("INSERT INTO mail_queue (id) ".
			"VALUES (%d)",
			$id
		);
		$db->Execute($sql);
		
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
		$sql = "SELECT id, worker_id, updated, type, ticket_id, hint_to, subject, body, params_json, is_queued, queue_fails, queue_priority ".
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
			$object->queue_fails = $row['queue_fails'];
			$object->queue_priority = $row['queue_priority'];
			
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
		$fields = SearchFields_MailQueue::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		$start = ($page * $limit); // [JAS]: 1-based
		$total = -1;
		
		$select_sql = sprintf("SELECT ".
			"mail_queue.id as %s, ".
			"mail_queue.worker_id as %s, ".
			"mail_queue.updated as %s, ".
			"mail_queue.type as %s, ".
			"mail_queue.ticket_id as %s, ".
			"mail_queue.hint_to as %s, ".
			"mail_queue.subject as %s, ".
			"mail_queue.is_queued as %s, ".
			"mail_queue.queue_fails as %s, ".
			"mail_queue.queue_priority as %s ",
//			"mail_queue.body as %s, ".
//			"mail_queue.params_json as %s ",
				SearchFields_MailQueue::ID,
				SearchFields_MailQueue::WORKER_ID,
				SearchFields_MailQueue::UPDATED,
				SearchFields_MailQueue::TYPE,
				SearchFields_MailQueue::TICKET_ID,
				SearchFields_MailQueue::HINT_TO,
				SearchFields_MailQueue::SUBJECT,
				SearchFields_MailQueue::IS_QUEUED,
				SearchFields_MailQueue::QUEUE_FAILS,
				SearchFields_MailQueue::QUEUE_PRIORITY
//				SearchFields_MailQueue::BODY,
//				SearchFields_MailQueue::PARAMS_JSON
			);
			
		$join_sql = "FROM mail_queue ";
		
		// Custom field joins
		$has_multiple_values = false;
		//list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
		//	$tables,
		//	$params,
		//	'mail_queue.id',
		//	$select_sql,
		//	$join_sql
		//);
				
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "");
			
		$sort_sql = (!empty($sortBy)) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ";
			
		$sql = 
			$select_sql.
			$join_sql.
			$where_sql.
			($has_multiple_values ? 'GROUP BY mail_queue.id ' : '').
			$sort_sql;
			
		// [TODO] Could push the select logic down a level too
		if($limit > 0) {
    		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		} else {
		    $rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
            $total = mysql_num_rows($rs);
		}
		
		$results = array();
		
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
	const QUEUE_FAILS = 'm_queue_fails';
	const QUEUE_PRIORITY = 'm_queue_priority';
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
			self::QUEUE_FAILS => new DevblocksSearchField(self::QUEUE_FAILS, 'mail_queue', 'queue_fails', $translate->_('mail_queue.queue_fails')),
			self::QUEUE_PRIORITY => new DevblocksSearchField(self::QUEUE_PRIORITY, 'mail_queue', 'queue_priority', $translate->_('mail_queue.queue_priority')),
//			self::BODY => new DevblocksSearchField(self::BODY, 'mail_queue', 'body', $translate->_('common.content')),
//			self::PARAMS_JSON => new DevblocksSearchField(self::PARAMS_JSON, 'mail_queue', 'params_json', $translate->_('mail_queue.params_json')),
		);
		
		// Custom Fields
		//$fields = DAO_CustomField::getBySource(PsCustomFieldSource_XXX::ID);

		//if(is_array($fields))
		//foreach($fields as $field_id => $field) {
		//	$key = 'cf_'.$field_id;
		//	$columns[$key] = new DevblocksSearchField($key,$key,'field_value',$field->name);
		//}
		
		// Sort by label (translation-conscious)
		uasort($columns, create_function('$a, $b', "return strcasecmp(\$a->db_label,\$b->db_label);\n"));

		return $columns;		
	}
};

class Model_MailQueue {
	const TYPE_COMPOSE = 'mail.compose';
	const TYPE_OPEN_TICKET = 'mail.open_ticket';
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
	public $queue_fails;
	public $queue_priority;
	
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
				
			case Model_MailQueue::TYPE_OPEN_TICKET:
				$success = $this->_sendOpenTicket($this->type);
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
		$properties['team_id'] = $this->params['group_id'];

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

		// Files + Next step
		//'files' => $files,
		//'closed' => $closed,
		//'next_worker_id' => $next_worker_id,

		// Send mail
		if(false == ($ticket_id = CerberusMail::compose($properties)))
			return false;
			
		return true;
	}
	
	private function _sendOpenTicket($type) {
		// [TODO] This shouldn't be redundant with open ticket functionality

		// Worker
		if(null == ($worker = DAO_worker::get($this->worker_id)))
			return false;
		
		// To
		if(!isset($this->params['to']))
			return false;
		$to = $this->params['to'];

		// Requesters
		if(!isset($this->params['requesters']))
			return false;
		$reqs = $this->params['requesters'];
		
		// Send to requesters
		$send_to_reqs = false;
		if(isset($this->params['send_to_reqs']))
			$send_to_reqs = true;
			
		// Subject
		if(empty($this->subject))
			return false;

		// Message body
		if(empty($this->body))
			return false;
		
		$message = new CerberusParserMessage();
		$message->headers['date'] = date('r'); 
		$message->headers['to'] = $to;
		$message->headers['subject'] = $this->subject;
		$message->headers['message-id'] = CerberusApplication::generateMessageId();
		
		// Sender
		$fromList = imap_rfc822_parse_adrlist(rtrim($reqs,', '),'');
		
		if(empty($fromList) || !is_array($fromList)) {
			return false; // abort with message
		}
		$from = array_shift($fromList);
		$from_address = $from->mailbox . '@' . $from->host;
		$message->headers['from'] = $from_address;

		$message->body = sprintf(
			"(... This message was manually created by %s on behalf of the requesters ...)\r\n",
			$worker->getName()
		);

		// Parse
		$ticket_id = CerberusParser::parseMessage($message);
		
		$ticket = DAO_Ticket::get($ticket_id);
		
		// Add additional requesters to ticket
		if(is_array($fromList) && !empty($fromList))
		foreach($fromList as $requester) {
			if(empty($requester))
				continue;
			$host = empty($requester->host) ? 'localhost' : $requester->host;
			DAO_Ticket::createRequester($requester->mailbox . '@' . $host, $ticket_id);
		}
		
		// Worker reply
		$properties = array(
			'draft_id' => $this->id,
		    'message_id' => $ticket->first_message_id,
		    'ticket_id' => $ticket_id,
		    'subject' => $this->subject,
		    'content' => $this->body,
//		    'files' => @$_FILES['attachment'],
//		    'next_worker_id' => $next_worker_id,
//		    'closed' => $closed,
//		    'bucket_id' => $move_bucket,
//		    'ticket_reopen' => $ticket_reopen,
//		    'unlock_date' => $unlock_date,
		    'agent_id' => $worker->id,
			'dont_send' => (false==$send_to_reqs),
		);
		
		return CerberusMail::sendTicketMessage($properties);
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
		if(empty($this->worker_id))
			return false;
		$properties['agent_id'] = $this->worker_id;
		
		// Attachments
		// 'files' => $attachment_files,
		// 'files' => @$_FILES['attachment'],
		
//	    'next_worker_id' => DevblocksPlatform::importGPC(@$_REQUEST['next_worker_id'],'integer',0),
//	    'closed' => DevblocksPlatform::importGPC(@$_REQUEST['closed'],'integer',0),
//	    'bucket_id' => DevblocksPlatform::importGPC(@$_REQUEST['bucket_id'],'string',''),
//	    'ticket_reopen' => DevblocksPlatform::importGPC(@$_REQUEST['ticket_reopen'],'string',''),
//	    'unlock_date' => DevblocksPlatform::importGPC(@$_REQUEST['unlock_date'],'string',''),

//	    'forward_files' => DevblocksPlatform::importGPC(@$_REQUEST['forward_files'],'array',array()),
		
		// Send message
		return CerberusMail::sendTicketMessage($properties);
	}
};

class View_MailQueue extends C4_AbstractView {
	const DEFAULT_ID = 'mail_queue';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		// [TODO] (translations)
		$this->name = $translate->_('Mail Queue');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_MailQueue::UPDATED;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_MailQueue::HINT_TO,
			SearchFields_MailQueue::UPDATED,
		);
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_MailQueue::search(
			array(),
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc,
			$this->renderTotal
		);
		return $objects;
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$path = dirname(dirname(dirname(__FILE__))) . '/templates/';
		
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		$tpl->assign('view_fields', $this->getColumns());
		
		// [TODO] Set your template path
		$tpl->display('file:'.$path.'mail/queue/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_MailQueue::HINT_TO:
			case SearchFields_MailQueue::SUBJECT:
			case SearchFields_MailQueue::TYPE:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__string.tpl');
				break;
			case SearchFields_MailQueue::ID:
			case SearchFields_MailQueue::TICKET_ID:
			case SearchFields_MailQueue::QUEUE_FAILS:
			case SearchFields_MailQueue::QUEUE_PRIORITY:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__number.tpl');
				break;
			case SearchFields_MailQueue::IS_QUEUED:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__bool.tpl');
				break;
			case SearchFields_MailQueue::UPDATED:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__date.tpl');
				break;
			case SearchFields_MailQueue::WORKER_ID:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__worker.tpl');
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
			case SearchFields_MailQueue::WORKER_ID:
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

	static function getFields() {
		return SearchFields_MailQueue::getFields();
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_MailQueue::ID]);
		unset($fields[SearchFields_MailQueue::TICKET_ID]);
		return $fields;
	}

	static function getColumns() {
		$fields = self::getFields();
		unset($fields[SearchFields_MailQueue::TICKET_ID]);
		return $fields;
	}

	function doResetCriteria() {
		parent::doResetCriteria();
		
		$this->params = array(
			//SearchFields_MailQueue::ID => new DevblocksSearchCriteria(SearchFields_MailQueue::ID,'!=',0),
		);
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_MailQueue::TYPE:
			case SearchFields_MailQueue::HINT_TO:
			case SearchFields_MailQueue::SUBJECT:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case SearchFields_MailQueue::ID:
			case SearchFields_MailQueue::TICKET_ID:
			case SearchFields_MailQueue::QUEUE_FAILS:
			case SearchFields_MailQueue::QUEUE_PRIORITY:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_MailQueue::UPDATED:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
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
			$this->params[$field] = $criteria;
			$this->renderPage = 0;
		}
	}
		
	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(0);
	  
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
			list($objects,$null) = DAO_MailQueue::search(
				$this->params,
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
				
				// Custom Fields
				//self::_doBulkSetCustomFields(ChCustomFieldSource_MailDraft::ID, $custom_fields, $batch_ids);
			} else {
				DAO_MailQueue::delete($batch_ids);
			}
			
			unset($batch_ids);
		}

		unset($ids);
	}			
};
