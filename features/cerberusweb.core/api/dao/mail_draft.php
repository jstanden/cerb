<?php
class DAO_MailDraft extends DevblocksORMHelper {
	const ID = 'id';
	const WORKER_ID = 'worker_id';
	const UPDATED = 'updated';
	const TYPE = 'type';
	const TICKET_ID = 'ticket_id';
	const HINT_TO = 'hint_to';
	const SUBJECT = 'subject';
	const BODY = 'body';
	const PARAMS_JSON = 'params_json';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('mail_draft_seq');
		
		$sql = sprintf("INSERT INTO mail_draft (id) ".
			"VALUES (%d)",
			$id
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'mail_draft', $fields);
	}
	
	static function updateWhere($fields, $where) {
		parent::_updateWhere('mail_draft', $fields, $where);
	}
	
	/**
	 * @param string $where
	 * @param mixed $sortBy
	 * @param mixed $sortAsc
	 * @param integer $limit
	 * @return Model_MailDraft[]
	 */
	static function getWhere($where=null, $sortBy=null, $sortAsc=true, $limit=null) {
		$db = DevblocksPlatform::getDatabaseService();

		list($where_sql, $sort_sql, $limit_sql) = self::_getWhereSQL($where, $sortBy, $sortAsc, $limit);
		
		// SQL
		$sql = "SELECT id, worker_id, updated, type, ticket_id, hint_to, subject, body, params_json ".
			"FROM mail_draft ".
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_MailDraft	 */
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
	 * @return Model_MailDraft[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_MailDraft();
			$object->id = $row['id'];
			$object->worker_id = $row['worker_id'];
			$object->updated = $row['updated'];
			$object->type = $row['type'];
			$object->ticket_id = $row['ticket_id'];
			$object->hint_to = $row['hint_to'];
			$object->subject = $row['subject'];
			$object->body = $row['body'];
			
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
		
		$db->Execute(sprintf("DELETE FROM mail_draft WHERE id IN (%s)", $ids_list));
		
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
		$fields = SearchFields_MailDraft::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields, $sortBy);
		$start = ($page * $limit); // [JAS]: 1-based
		$total = -1;
		
		$select_sql = sprintf("SELECT ".
			"mail_draft.id as %s, ".
			"mail_draft.worker_id as %s, ".
			"mail_draft.updated as %s, ".
			"mail_draft.type as %s, ".
			"mail_draft.ticket_id as %s, ".
			"mail_draft.hint_to as %s, ".
			"mail_draft.subject as %s ",
//			"mail_draft.body as %s, ".
//			"mail_draft.params_json as %s ",
				SearchFields_MailDraft::ID,
				SearchFields_MailDraft::WORKER_ID,
				SearchFields_MailDraft::UPDATED,
				SearchFields_MailDraft::TYPE,
				SearchFields_MailDraft::TICKET_ID,
				SearchFields_MailDraft::HINT_TO,
				SearchFields_MailDraft::SUBJECT
//				SearchFields_MailDraft::BODY,
//				SearchFields_MailDraft::PARAMS_JSON
			);
			
		$join_sql = "FROM mail_draft ";
		
		// Custom field joins
		//list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
		//	$tables,
		//	$params,
		//	'mail_draft.id',
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
			($has_multiple_values ? 'GROUP BY mail_draft.id ' : '').
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
			$object_id = intval($row[SearchFields_MailDraft::ID]);
			$results[$object_id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT mail_draft.id) " : "SELECT COUNT(mail_draft.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
	}
};

class SearchFields_MailDraft implements IDevblocksSearchFields {
	const ID = 'm_id';
	const WORKER_ID = 'm_worker_id';
	const UPDATED = 'm_updated';
	const TYPE = 'm_type';
	const TICKET_ID = 'm_ticket_id';
	const HINT_TO = 'm_hint_to';
	const SUBJECT = 'm_subject';
//	const BODY = 'm_body';
//	const PARAMS_JSON = 'm_params_json';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'mail_draft', 'id', $translate->_('id')),
			self::WORKER_ID => new DevblocksSearchField(self::WORKER_ID, 'mail_draft', 'worker_id', $translate->_('worker_id')),
			self::UPDATED => new DevblocksSearchField(self::UPDATED, 'mail_draft', 'updated', $translate->_('updated')),
			self::TYPE => new DevblocksSearchField(self::TYPE, 'mail_draft', 'type', $translate->_('type')),
			self::TICKET_ID => new DevblocksSearchField(self::TICKET_ID, 'mail_draft', 'ticket_id', $translate->_('ticket_id')),
			self::HINT_TO => new DevblocksSearchField(self::HINT_TO, 'mail_draft', 'hint_to', $translate->_('hint_to')),
			self::SUBJECT => new DevblocksSearchField(self::SUBJECT, 'mail_draft', 'subject', $translate->_('subject')),
//			self::BODY => new DevblocksSearchField(self::BODY, 'mail_draft', 'body', $translate->_('body')),
//			self::PARAMS_JSON => new DevblocksSearchField(self::PARAMS_JSON, 'mail_draft', 'params_json', $translate->_('params_json')),
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

class Model_MailDraft {
	const TYPE_COMPOSE = 'mail.compose';
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
	
	/**
	 * @return boolean
	 */
	public function send() {
		$success = false;
		
		// Determine the type of message
		switch($this->type) {
			case Model_MailDraft::TYPE_COMPOSE:
				$success = $this->_sendCompose();
				break;
				
			case Model_MailDraft::TYPE_TICKET_REPLY:
				$success = $this->_sendTicketReply();
				break;
		}
		
		if($success) {
			// [TODO] Delete the draft
		}
	}
	
	private function _sendCompose() {
		$properties = array();

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
			
		echo $ticket_id;
		
		return true;
	}
	
	private function _sendTicketReply() {
		$properties = array();
		
		// In reply to message-id
		if(!isset($this->params['in_reply_message_id']))
			return false;
		$properties['message_id'] = $this->params['in_reply_message_id'];

		// Ticket ID
		if(isset($this->ticket_id))
			$properties['ticket_id'] = $this->ticket_id;

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

//	    'to' => DevblocksPlatform::importGPC(@$_REQUEST['to']),
//	    'forward_files' => DevblocksPlatform::importGPC(@$_REQUEST['forward_files'],'array',array()),
		
		// Send message
		return CerberusMail::sendTicketMessage($properties);
	}
};

class View_MailDraft extends C4_AbstractView {
	const DEFAULT_ID = 'mail_drafts';

	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
	
		$this->id = self::DEFAULT_ID;
		// [TODO] (translations)
		$this->name = $translate->_('Drafts');
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_MailDraft::UPDATED;
		$this->renderSortAsc = false;

		$this->view_columns = array(
			SearchFields_MailDraft::SUBJECT,
			SearchFields_MailDraft::HINT_TO,
			SearchFields_MailDraft::UPDATED,
		);
		
		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_MailDraft::search(
			array(),
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc
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
		$tpl->display('file:'.$path.'tickets/drafts/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_MailDraft::HINT_TO:
			case SearchFields_MailDraft::SUBJECT:
			case SearchFields_MailDraft::TYPE:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__string.tpl');
				break;
			case SearchFields_MailDraft::ID:
			case SearchFields_MailDraft::TICKET_ID:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__number.tpl');
				break;
//			case 'placeholder_bool':
//				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__bool.tpl');
//				break;
			case SearchFields_MailDraft::UPDATED:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__date.tpl');
				break;
			case SearchFields_MailDraft::WORKER_ID:
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
			case SearchFields_MailDraft::WORKER_ID:
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
		return SearchFields_MailDraft::getFields();
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_MailDraft::ID]);
		unset($fields[SearchFields_MailDraft::TICKET_ID]);
		return $fields;
	}

	static function getColumns() {
		$fields = self::getFields();
		unset($fields[SearchFields_MailDraft::TICKET_ID]);
		return $fields;
	}

	function doResetCriteria() {
		parent::doResetCriteria();
		
		$this->params = array(
			//SearchFields_MailDraft::ID => new DevblocksSearchCriteria(SearchFields_MailDraft::ID,'!=',0),
		);
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_MailDraft::TYPE:
			case SearchFields_MailDraft::HINT_TO:
			case SearchFields_MailDraft::SUBJECT:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case SearchFields_MailDraft::ID:
			case SearchFields_MailDraft::TICKET_ID:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
				
			case SearchFields_MailDraft::UPDATED:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
				
//			case 'placeholder_bool':
//				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
//				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
//				break;
				
			case SearchFields_MailDraft::WORKER_ID:
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

		// Make sure we have actions
		if(empty($do))
			return;

		// Make sure we have checked items if we want a checked list
		if(0 == strcasecmp($filter,"checks") && empty($ids))
			return;
			
		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				// [TODO] Implement actions
				case 'example':
					//$change_fields[DAO_MailDraft::EXAMPLE] = 'some value';
					break;
				default:
					break;
			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_MailDraft::search(
				$this->params,
				100,
				$pg++,
				SearchFields_MailDraft::ID,
				true,
				false
			);
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			DAO_MailDraft::update($batch_ids, $change_fields);

			// Custom Fields
			//self::_doBulkSetCustomFields(ChCustomFieldSource_MailDraft::ID, $custom_fields, $batch_ids);
			
			unset($batch_ids);
		}

		unset($ids);
	}			
};
