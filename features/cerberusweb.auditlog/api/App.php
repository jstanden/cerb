<?php
class ChAuditLogEventListener extends DevblocksEventListenerExtension {
    function __construct($manifest) {
        parent::__construct($manifest);
    }

    /**
     * @param Model_DevblocksEvent $event
     */
    function handleEvent(Model_DevblocksEvent $event) {
        switch($event->id) {
            case 'cron.maint':
            	DAO_TicketAuditLog::maint();
            	break;
            
            case 'ticket.merge':
            	// Listen for ticket merges and update our internal ticket_id records
            	
            	@$new_ticket_id = $event->params['new_ticket_id'];
            	@$old_ticket_ids = $event->params['old_ticket_ids'];
            	
            	if(empty($new_ticket_id) || empty($old_ticket_ids))
            		return;
            	
            	$fields = array(
            		DAO_TicketAuditLog::TICKET_ID => $new_ticket_id,
            	);
            	DAO_TicketAuditLog::updateWhere($fields, sprintf(
            		"%s IN (%s)",
            		DAO_TicketAuditLog::TICKET_ID,
            		implode(',', $old_ticket_ids)
            	));
            	
            	break;
            	
            case 'ticket.property.pre_change':
            	@$ticket_ids = $event->params['ticket_ids'];
            	@$changed_fields = $event->params['changed_fields'];

            	// Filter out any mandatory changes we could care less about
				unset($changed_fields[DAO_Ticket::UPDATED_DATE]);
				unset($changed_fields[DAO_Ticket::MASK]);
				unset($changed_fields[DAO_Ticket::FIRST_MESSAGE_ID]);
				unset($changed_fields[DAO_Ticket::LAST_MESSAGE_ID]);
				unset($changed_fields[DAO_Ticket::FIRST_WROTE_ID]);
				unset($changed_fields[DAO_Ticket::LAST_WROTE_ID]);
				unset($changed_fields[DAO_Ticket::INTERESTING_WORDS]);
            	
            	@$tickets = DAO_Ticket::getTickets($ticket_ids);
            	// Is a worker around to invoke this change?  0 = automatic
            	@$worker_id = (null != ($active_worker = CerberusApplication::getActiveWorker()) && !empty($active_worker->id))
            		? $active_worker->id
            		: 0;
            	
            	if(is_array($tickets) 
            		&& !empty($tickets) 
            		&& is_array($changed_fields) 
            		&& !empty($changed_fields))
            	foreach($tickets as $ticket_id => $ticket) { /* @var $ticket Model_Ticket */
            		foreach($changed_fields as $changed_field => $changed_value) {
            			if(is_array($changed_value))
							$changed_value = implode("\r\n", $changed_value);
						
            			// If different
            			if(isset($ticket->$changed_field) 
            				&& 0 != strcmp($ticket->$changed_field,$changed_value)) {
		            		$fields = array(
		            			DAO_TicketAuditLog::TICKET_ID => $ticket_id,
		            			DAO_TicketAuditLog::WORKER_ID => $worker_id,
		            			DAO_TicketAuditLog::CHANGE_DATE => time(),
		            			DAO_TicketAuditLog::CHANGE_FIELD => $changed_field,
		            			DAO_TicketAuditLog::CHANGE_VALUE => substr($changed_value,0,128),
		            		);
			            	$log_id = DAO_TicketAuditLog::create($fields);
            			}
            		}
            	}
            	break;
        }
    }
};

class ChAuditLogTicketTab extends Extension_TicketTab {
	private $tpl_path = null; 
	
    function __construct($manifest) {
        parent::__construct($manifest);
        $this->tpl_path = dirname(dirname(__FILE__)).'/templates';
    }
	
	function showTab() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer', 0);

		$visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
		$translate = DevblocksPlatform::getTranslationService();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->tpl_path);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'C4_TicketAuditLogView';
		$defaults->id = 'audit_log';
		$defaults->view_columns = array(
			SearchFields_TicketAuditLog::CHANGE_DATE,
			SearchFields_TicketAuditLog::WORKER_ID,
			SearchFields_TicketAuditLog::CHANGE_FIELD,
			SearchFields_TicketAuditLog::CHANGE_VALUE,
		);
		$defaults->params = array();
		$defaults->renderLimit = 15;
		$defaults->renderPage = 0;
		$defaults->renderSortBy = SearchFields_TicketAuditLog::CHANGE_DATE;
		$defaults->renderSortAsc = false;
		
		$view = C4_AbstractViewLoader::getView('audit_log', $defaults);
		
		$view->params = array(
			SearchFields_TicketAuditLog::TICKET_ID => new DevblocksSearchCriteria(SearchFields_TicketAuditLog::TICKET_ID,DevblocksSearchCriteria::OPER_EQ,$ticket_id)
		);
		$view->renderPage = 0;
		
		C4_AbstractViewLoader::setView($view->id,$view);
		
		$tpl->assign('view', $view);
		
		$tpl->display('file:' . $this->tpl_path . '/display/log/index.tpl');
	}
	
	function saveTab() {
		
	}
};

class DAO_TicketAuditLog extends DevblocksORMHelper {
	const ID = 'id';
	const WORKER_ID = 'worker_id';
	const TICKET_ID = 'ticket_id';
	const CHANGE_DATE = 'change_date';
	const CHANGE_FIELD = 'change_field';
	const CHANGE_VALUE = 'change_value';
	
	public static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('ticket_audit_log_seq');
		
		$sql = sprintf("INSERT INTO ticket_audit_log (id, worker_id, ticket_id, change_date, change_field, change_value) ".
			"VALUES (%d,0,0,%d,'','')",
			$id,
			time()
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	/**
	 * @return Model_TicketAuditLog[]
	 */
	public static function getWhere($where) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, worker_id, ticket_id, change_date, change_field, change_value ".
			"FROM ticket_audit_log ".
			(!empty($where)?sprintf("WHERE %s ",$where):" ").
			"ORDER BY id "
			;
		$rs = $db->Execute($sql);
		
		return self::_createObjectsFromResultSet($rs);
	}
	
	static private function _createObjectsFromResultSet($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
		    $object = new Model_TicketAuditLog();
		    $object->id = intval($row['id']);
		    $object->ticket_id = intval($row['ticket_id']);
		    $object->worker_id = intval($row['worker_id']);
		    $object->change_date = intval($row['change_date']);
		    $object->change_field = $row['change_field'];
		    $object->change_value = $row['change_value'];
		    $objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
			
	public static function update($ids, $fields) {
		parent::_update($ids, 'ticket_audit_log', $fields);
	}
	
	public static function updateWhere($fields, $where) {
		parent::_updateWhere('ticket_audit_log', $fields, $where);
	}
	
	public static function maint() {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "DELETE QUICK ticket_audit_log FROM ticket_audit_log LEFT JOIN ticket ON ticket_audit_log.ticket_id=ticket.id WHERE ticket.id IS NULL";
		$db->Execute($sql);
	}
	
	public static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		
		$db = DevblocksPlatform::getDatabaseService();
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE QUICK FROM ticket_audit_log WHERE id IN (%s)", $ids_list));
	}
	
	public static function deleteByTicketIds($ids) {
		if(!is_array($ids)) $ids = array($ids);
		
		$db = DevblocksPlatform::getDatabaseService();
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE QUICK FROM ticket_audit_log WHERE ticket_id IN (%s)", $ids_list));
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
		$fields = SearchFields_TicketAuditLog::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, array(), $fields,$sortBy);
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		$total = -1;
		
		$sql = sprintf("SELECT ".
			"l.id as %s, ".
			"l.ticket_id as %s, ".
			"l.worker_id as %s, ".
			"l.change_date as %s, ".
			"l.change_field as %s, ".
			"l.change_value as %s ".
			"FROM ticket_audit_log l ",
//			"INNER JOIN team tm ON (tm.id = t.team_id) ".
			    SearchFields_TicketAuditLog::ID,
			    SearchFields_TicketAuditLog::TICKET_ID,
			    SearchFields_TicketAuditLog::WORKER_ID,
			    SearchFields_TicketAuditLog::CHANGE_DATE,
			    SearchFields_TicketAuditLog::CHANGE_FIELD,
			    SearchFields_TicketAuditLog::CHANGE_VALUE
			).
			
			// [JAS]: Dynamic table joins
//			(isset($tables['ra']) ? "INNER JOIN requester r ON (r.ticket_id=t.id)" : " ").
			
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "").
			(!empty($sortBy) ? sprintf("ORDER BY %s %s",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : "")
		;
		// [TODO] Could push the select logic down a level too
		if($limit > 0) {
    		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
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
			$id = intval($row[SearchFields_TicketAuditLog::ID]);
			$results[$id] = $result;
		}

		// [JAS]: Count all
		if($withCounts) {
		    $rs = $db->Execute($sql);
		    $total = mysql_num_rows($rs);
		}
		
		mysql_free_result($rs);
		
		return array($results,$total);
    }
	
};

class SearchFields_TicketAuditLog implements IDevblocksSearchFields {
	// Audit Log
	const ID = 'l_id';
	const WORKER_ID = 'l_worker_id';
	const TICKET_ID = 'l_ticket_id';
	const CHANGE_DATE = 'l_change_date';
	const CHANGE_FIELD = 'l_change_field';
	const CHANGE_VALUE = 'l_change_value';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'l', 'id'),
			self::WORKER_ID => new DevblocksSearchField(self::WORKER_ID, 'l', 'worker_id',$translate->_('auditlog_entry.worker_id')),
			self::TICKET_ID => new DevblocksSearchField(self::TICKET_ID, 'l', 'ticket_id',$translate->_('auditlog_entry.ticket_id')),
			self::CHANGE_DATE => new DevblocksSearchField(self::CHANGE_DATE, 'l', 'change_date',$translate->_('auditlog_entry.change_date')),
			self::CHANGE_FIELD => new DevblocksSearchField(self::CHANGE_FIELD, 'l', 'change_field',$translate->_('auditlog_entry.change_field')),
			self::CHANGE_VALUE => new DevblocksSearchField(self::CHANGE_VALUE, 'l', 'change_value',$translate->_('auditlog_entry.change_value')),
		);
		
		// Sort by label (translation-conscious)
		uasort($columns, create_function('$a, $b', "return strcasecmp(\$a->db_label,\$b->db_label);\n"));
		
		return $columns;
	}
};

class Model_TicketAuditLog {
	public $id = 0;
	public $ticket_id = 0;
	public $worker_id = 0;
	public $change_date = 0;
	public $change_field = '';
	public $change_value = '';
};

class C4_TicketAuditLogView extends C4_AbstractView {
	const DEFAULT_ID = 'audit_log';
	
	function __construct() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$this->id = self::DEFAULT_ID;
		$this->name = $translate->_('auditlog.audit_log');
		$this->renderLimit = 15;
		$this->renderSortBy = 'l_change_date';
		$this->renderSortAsc = false;
		
		$this->view_columns = array(
			SearchFields_TicketAuditLog::CHANGE_DATE,
			SearchFields_TicketAuditLog::WORKER_ID,
			SearchFields_TicketAuditLog::CHANGE_FIELD,
			SearchFields_TicketAuditLog::CHANGE_VALUE,
		);
	}
	
	function getData() {
		$objects = DAO_TicketAuditLog::search(
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
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);
		$tpl->assign('view_fields', $this->getColumns());
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);
		
		$ticket_fields = SearchFields_Ticket::getFields();
		$tpl->assign('ticket_fields', $ticket_fields);
		
		$tpl->display('file:' . APP_PATH . '/features/cerberusweb.auditlog/templates/display/log/log_view.tpl');
	}
	
	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		
		switch($field) {
			case SearchFields_TicketAuditLog::CHANGE_FIELD:
			case SearchFields_TicketAuditLog::CHANGE_VALUE:
				$tpl->display('file:' . APP_PATH . '/features/cerberusweb.core/templates/internal/views/criteria/__string.tpl');
				break;
			default:
				echo '';
				break;
		}
	}

	static function getFields() {
		return SearchFields_TicketAuditLog::getFields();
	}
	
	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_TicketAuditLog::ID]);
		return $fields;
	}
	
	static function getColumns() {
		$fields = self::getFields();
		return $fields;
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;
		
		switch($field) {
			case SearchFields_TicketAuditLog::ID:
			case SearchFields_TicketAuditLog::WORKER_ID:
			case SearchFields_TicketAuditLog::TICKET_ID:
			case SearchFields_TicketAuditLog::CHANGE_DATE:
			case SearchFields_TicketAuditLog::CHANGE_FIELD:
			case SearchFields_TicketAuditLog::CHANGE_VALUE:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE) 
					&& false === (strpos($value,'*'))) {
						$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
		}
		
		if(!empty($criteria)) {
			$this->params[$field] = $criteria;
			$this->renderPage = 0;
		}
	}	
};

?>