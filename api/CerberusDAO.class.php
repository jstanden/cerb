<?php

class CerberusContactDAO {
	private function CerberusContactDAO() {}
	
	static function lookupAddress($email,$create_if_null=false) {
		$um_db = UserMeetDatabase::getInstance();
		$id = null;
		
		$sql = sprintf("SELECT id FROM address WHERE email = %s",
			$um_db->qstr($email)
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if(!$rs->EOF) {
			$id = $rs->fields['id'];
		} elseif($create_if_null) {
			$id = CerberusContactDAO::createAddress($email);
		}
		
		return $id;
	}

	static function createAddress($email,$personal='') {
		$um_db = UserMeetDatabase::getInstance();
		
		if(null != ($id = CerberusContactDAO::lookupAddress($email,false)))
			return $id;

		$id = $um_db->GenID('address_seq');
		
		$sql = sprintf("INSERT INTO address (id,email,personal) VALUES (%d,%s,%s)",
			$id,
			$um_db->qstr($email),
			$um_db->qstr($personal)
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return $id;
	}
	
}

/**
 * Enter description here...
 *
 * @addtogroup dao
 */
class CerberusTicketDAO {
	
	private function CerberusTicketDAO() {}
	
	static function createTicket($mask, $subject, $status, $mailbox_id, $last_wrote, $created_date) {
		$um_db = UserMeetDatabase::getInstance();
		$newId = $um_db->GenID('ticket_seq');
		
		$sql = sprintf("INSERT INTO ticket (id, mask, subject, status, last_wrote, first_wrote, created_date, updated_date, priority) ".
			"VALUES (%d,%s,%s,%s,%s,%s,%d,%d,0)",
			$newId,
			$um_db->qstr($mask),
			$um_db->qstr($subject),
			$um_db->qstr($status),
			$um_db->qstr($last_wrote),
			$um_db->qstr($last_wrote),
			$created_date,
			gmmktime()
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return $newId;
	}

	static function createMessage($ticket_id,$created_date,$address_id,$headers,$content) {
		$um_db = UserMeetDatabase::getInstance();
		$newId = $um_db->GenID('message_seq');
		
		// [JAS]: Flatten an array of headers into a string.
		$sHeaders = serialize($headers);
//		if(is_array($headers)) {
//			foreach($headers as $k => $v) {
//				if(is_array($v)) {
//					foreach($v as $vv) {
//						$sHeaders .= ucwords($k) . ": " . $vv . "%crlf%";
//					}
//				} else {
//					$sHeaders .= ucwords($k) . ": " . $v . "%crlf%";
//				}
//			}
//		}

		$sql = sprintf("INSERT INTO message (id,ticket_id,created_date,address_id,headers,content) ".
			"VALUES (%d,%d,%d,%d,%s,%s)",
				$newId,
				$ticket_id,
				$created_date,
				$address_id,
				$um_db->qstr($sHeaders),
				$um_db->qstr($content)
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return $newId;
	}
	
	/**
	 * Enter description here...
	 *
	 */
	static function searchTickets($params,$limit=10,$page=0,$sortBy=null,$sortAsc=null) {
		$um_db = UserMeetDatabase::getInstance();
		
		$tickets = array();
		$start = min($page * $limit,1);
		
		$sql = sprintf("SELECT t.id , t.mask, t.subject, t.status, t.priority, t.first_wrote, t.last_wrote, t.created_date, t.updated_date ".
			"FROM ticket t ".
			(!empty($sortBy) ? sprintf("ORDER BY %s %s",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : "")
		);
		$rs = $um_db->SelectLimit($sql,$limit,0) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		while(!$rs->EOF) {
			$ticket = new CerberusTicket();
			$ticket->id = intval($rs->fields['id']);
			$ticket->mask = $rs->fields['mask'];
			$ticket->subject = $rs->fields['subject'];
			$ticket->status = $rs->fields['status'];
			$ticket->priority = $rs->fields['priority'];
			$ticket->last_wrote = $rs->fields['last_wrote'];
			$ticket->first_wrote = $rs->fields['first_wrote'];
			$ticket->created_date = intval($rs->fields['created_date']);
			$ticket->updated_date = intval($rs->fields['updated_date']);
			$tickets[$ticket->id] = $ticket;
			$rs->MoveNext();
		}

		// [JAS]: Count all
		$rs = $um_db->Execute($sql);
		$total = $rs->RecordCount();
		
		return array($tickets,$total);
	}
	
	// [JAS]: [TODO] Replace this inner function with a ticket ID search using searchTicket()?  Removes redundancy
	static function getTicket($id) {
		$um_db = UserMeetDatabase::getInstance();
		
		$ticket = null;
		
		$sql = sprintf("SELECT t.id , t.mask, t.subject, t.status, t.priority, t.first_wrote, t.last_wrote, t.created_date, t.updated_date ".
			"FROM ticket t ".
			"WHERE t.id = %d",
			$id
		);
		$rs = $um_db->SelectLimit($sql,2,0) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if(!$rs->EOF) {
			$ticket = new CerberusTicket();
			$ticket->id = intval($rs->fields['id']);
			$ticket->mask = $rs->fields['mask'];
			$ticket->subject = $rs->fields['subject'];
			$ticket->status = $rs->fields['status'];
			$ticket->priority = $rs->fields['priority'];
			$ticket->last_wrote = $rs->fields['last_wrote'];
			$ticket->first_wrote = $rs->fields['first_wrote'];
			$ticket->created_date = intval($rs->fields['created_date']);
			$ticket->updated_date = intval($rs->fields['updated_date']);
		}
		
		return $ticket;
	}
	
	static function getMessagesByTicket($ticket_id) {
		$um_db = UserMeetDatabase::getInstance();
		$messages = array();
		
		$sql = sprintf("SELECT m.id , m.ticket_id, m.created_date, m.address_id, m.headers ".
			"FROM message m ".
			"WHERE m.ticket_id = %d ".
			"ORDER BY m.created_date ASC ",
			$ticket_id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		while(!$rs->EOF) {
			$message = new CerberusMessage();
			$message->id = intval($rs->fields['id']);
			$message->ticket_id = intval($rs->fields['ticket_id']);
			$message->created_date = intval($rs->fields['created_date']);
			$message->address_id = intval($rs->fields['address_id']);
			
			$headers = unserialize($rs->fields['headers']);
			$message->headers = $headers;

			$messages[$message->id] = $message;
			$rs->MoveNext();
		}

		// [JAS]: Count all
		$rs = $um_db->Execute($sql);
		$total = $rs->RecordCount();
		
		return array($messages,$total);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id message id
	 * @return CerberusMessage
	 */
	static function getMessage($id) {
		$um_db = UserMeetDatabase::getInstance();
		$message = null;
		
		$sql = sprintf("SELECT m.id , m.ticket_id, m.created_date, m.address_id, m.headers ".
			"FROM message m ".
			"WHERE m.id = %d ".
			"ORDER BY m.created_date ASC ",
			$id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		if(!$rs->EOF) {
			$message = new CerberusMessage();
			$message->id = intval($rs->fields['id']);
			$message->ticket_id = intval($rs->fields['ticket_id']);
			$message->created_date = intval($rs->fields['created_date']);
			$message->address_id = intval($rs->fields['address_id']);
			
			$headers = unserialize($rs->fields['headers']);
			$message->headers = $headers;
		}

		// [JAS]: Count all
//		$rs = $um_db->Execute($sql);
//		$total = $rs->RecordCount();
		
		return $message;
//		return array($messages,$total);
	}
	
	static function getRequestersByTicket($ticket_id) {
		$um_db = UserMeetDatabase::getInstance();
		$addresses = array();
		
		$sql = sprintf("SELECT a.id , a.email, a.personal ".
			"FROM address a ".
			"INNER JOIN requester r ON (r.ticket_id = %d AND a.id=r.address_id) ".
			"ORDER BY a.personal, a.email ASC ",
			$ticket_id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		while(!$rs->EOF) {
			$address = new CerberusAddress();
			$address->id = intval($rs->fields['id']);
			$address->email = $rs->fields['email'];
			$address->personal = $rs->fields['personal'];
			$addresses[$address->id] = $address;
			$rs->MoveNext();
		}

		// [JAS]: Count all
//		$rs = $um_db->Execute($sql);
//		$total = $rs->RecordCount();
//		return array($addresses,$total);

		return $addresses;
	}
	
	static function getMessageContent($id) {
		$um_db = UserMeetDatabase::getInstance();
		$content = '';
		
		$sql = sprintf("SELECT m.id, m.content ".
			"FROM message m ".
			"WHERE m.id = %d ",
			$id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if(!$rs->EOF) {
			$content = $rs->fields['content'];
		}
		
		return $content;
	}
	
	static function createRequester($address_id,$ticket_id) {
		$um_db = UserMeetDatabase::getInstance();

		$um_db->Replace("requester",array("address_id"=>$address_id,"ticket_id"=>$ticket_id),array("address_id","ticket_id")) 
			or die(__CLASS__ . ':' . $um_db->ErrorMsg());;
		
		return true;
	}

	
};

/**
 * Enter description here...
 *
 * @addtogroup dao
 */
class CerberusDashboardDAO {
	private function CerberusDashboardDAO() {}
	
	static function createDashboard($name) {
		$um_db = UserMeetDatabase::getInstance();
		$newId = $um_db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO dashboard (id, name) VALUES (%d, %s)",
			$newId,
			$um_db->qstr($name)
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return $newId;
	}
	
	static function createView($name,$dashboard_id,$num_rows=10,$sort_by=null,$sort_asc=1) {
		$um_db = UserMeetDatabase::getInstance();
		$newId = $um_db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO dashboard_view (id, name, dashboard_id, num_rows, sort_by, sort_asc) ".
			"VALUES (%d, %s, %d, %d, %s, %s)",
			$newId,
			$um_db->qstr($name),
			$dashboard_id,
			$num_rows,
			$um_db->qstr($sort_by),
			$sort_asc
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return $newId;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $dashboard_id
	 * @return CerberusDashboardView[]
	 */
	static function getViews($dashboard_id=0) {
		$um_db = UserMeetDatabase::getInstance();
		
		$sql = sprintf("SELECT v.id, v.name, v.dashboard_id, v.num_rows, v.sort_by, v.sort_asc ".
			"FROM dashboard_view v ".
			(!empty($dashboard_id) ? sprintf("WHERE v.dashboard_id = %d ", $dashboard_id) : " ")
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$views = array();
		
		while(!$rs->EOF) {
			$view = new CerberusDashboardView();
			$view->id = $rs->fields['id'];
			$view->name = $rs->fields['name'];
			$view->dashboard_id = intval($rs->fields['dashboard_id']);
			$view->renderLimit = intval($rs->fields['num_rows']);
			$view->renderSortBy = $rs->fields['sort_by'];
			$view->renderSortAsc = intval($rs->fields['sort_asc']);
			$views[$view->id] = $view; 
			$rs->MoveNext();
		}
		
		return $views;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $view_id
	 * @return CerberusDashboardView
	 */
	static function getView($view_id) {
		$um_db = UserMeetDatabase::getInstance();
		
		$sql = sprintf("SELECT v.id, v.name, v.dashboard_id, v.num_rows, v.sort_by, v.sort_asc ".
			"FROM dashboard_view v ".
			"WHERE v.id = %d ",
			$view_id
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */

		if(!$rs->EOF) {
			$view = new CerberusDashboardView();
			$view->id = $rs->fields['id'];
			$view->name = $rs->fields['name'];
			$view->dashboard_id = intval($rs->fields['dashboard_id']);
			$view->renderLimit = intval($rs->fields['num_rows']);
			$view->renderSortBy = $rs->fields['sort_by'];
			$view->renderSortAsc = intval($rs->fields['sort_asc']);
			$views[$view->id] = $view; 
			return $view;
		}
		
		return null;
	}
};

?>