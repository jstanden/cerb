<?php

/**
 * Enter description here...
 *
 * @addtogroup dao
 */
class CerberusTicketDAO {
	
	private function CerberusTicketDAO() {}
	
	static function createTicket($mask, $subject, $status, $last_wrote) {
		$um_db = UserMeetDatabase::getInstance();
		$newId = $um_db->GenID('ticket_seq');
		
		$sql = sprintf("INSERT INTO ticket (id, mask, subject, status, last_wrote) VALUES (%d,%s,%s,%s,%s)",
			$newId,
			$um_db->qstr($mask),
			$um_db->qstr($subject),
			$um_db->qstr($status),
			$um_db->qstr($last_wrote)
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
		
		$sql = sprintf("SELECT t.id , t.mask, t.subject, t.status, t.priority, t.last_wrote, t.created_date, t.updated_date ".
			"FROM ticket t ".
			(!empty($sortBy) ? sprintf("ORDER BY %s %s",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : "")
		);
		$rs = $um_db->SelectLimit($sql,$limit,0) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		while(!$rs->EOF) {
			$ticket = new stdClass();
			$ticket->id = intval($rs->fields['id']);
			$ticket->mask = $rs->fields['mask'];
			$ticket->subject = $rs->fields['subject'];
			$ticket->status = $rs->fields['status'];
			$ticket->priority = $rs->fields['priority'];
			$ticket->last_wrote = $rs->fields['last_wrote'];
			$ticket->created_date = $rs->fields['created_date'];
			$ticket->updated_date = $rs->fields['updated_date'];
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
		
		$sql = sprintf("SELECT t.id , t.mask, t.subject, t.status, t.priority, t.last_wrote, t.created_date, t.updated_date ".
			"FROM ticket t ".
			"WHERE t.id = %d",
			$id
		);
		$rs = $um_db->SelectLimit($sql,2,0) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		if(!$rs->EOF) {
			$ticket = new stdClass();
			$ticket->id = intval($rs->fields['id']);
			$ticket->mask = $rs->fields['mask'];
			$ticket->subject = $rs->fields['subject'];
			$ticket->status = $rs->fields['status'];
			$ticket->priority = $rs->fields['priority'];
			$ticket->last_wrote = $rs->fields['last_wrote'];
			$ticket->created_date = $rs->fields['created_date'];
			$ticket->updated_date = $rs->fields['updated_date'];
		}
		
		return $ticket;
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