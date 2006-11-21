<?php
class CerberusApplication {
	
	function getModules() {
		$modules = array();
		$extModules = UserMeetPlatform::getExtensions("com.cerberusweb.module");
		foreach($extModules as $mod) { /* @var $mod UserMeetExtensionManifest */
			$instance = $mod->createInstance(); /* @var $instance CerberusModuleExtension */
			if(is_a($instance,'usermeetextension') && $instance->isVisible())
				$modules[] = $instance;
		}
		return $modules;
	}
	
	function setActiveModule($module=null) {
		static $activeModule;
		if(!is_null($module)) $activeModule = $module;
		return $activeModule;
	}
	
	function getActiveModule() {
		return CerberusApplication::setActiveModule(); // returns
	}
	
	// ***************** DUMMY
	function getTeamList() {
		$um_db = UserMeetDatabase::getInstance();

		$teams = array();
		
		$sql = sprintf("SELECT t.id , t.name ".
			"FROM team t ".
			"ORDER BY t.name ASC"
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		while(!$rs->EOF) {
			$team = new stdClass();
			$team->id = intval($rs->fields['id']);
			$team->name = $rs->fields['name'];
			$teams[$team->id] = $team;
			$rs->MoveNext();
		}
		
		return $teams;
	}

	function getMailboxList() {
		$um_db = UserMeetDatabase::getInstance();

		$mailboxes = array();
		
		$sql = sprintf("SELECT m.id , m.name ".
			"FROM mailbox m ".
			"ORDER BY m.name ASC"
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		while(!$rs->EOF) {
			$mailbox = new stdClass();
			$mailbox->id = intval($rs->fields['id']);
			$mailbox->name = $rs->fields['name'];
			$mailboxes[$mailbox->id] = $mailbox;
			$rs->MoveNext();
		}
		
		return $mailboxes;
	}
	
	function createTeam($name) {
		$um_db = UserMeetDatabase::getInstance();
		$newId = $um_db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO team (id, name) VALUES (%d,%s)",
			$newId,
			$um_db->qstr($name)
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return $newId;
	}
	
	function createMailbox($name) {
		$um_db = UserMeetDatabase::getInstance();
		$newId = $um_db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO mailbox (id, name) VALUES (%d,%s)",
			$newId,
			$um_db->qstr($name)
		);
		$um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		return $newId;
	}
	
	// ***************** DUMMY
	
};

/**
 * Enter description here...
 *
 * @addtogroup dao
 */
class CerberusTicketDAO {
	function createTicket($mask, $subject, $status, $last_wrote) {
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

	function getTicketList() {
		$um_db = UserMeetDatabase::getInstance();
		
		$tickets = array();
		
		$sql = sprintf("SELECT t.id , t.mask, t.subject, t.status, t.last_wrote ".
			"FROM ticket t"
		);
		$rs = $um_db->SelectLimit($sql,2,0) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		while(!$rs->EOF) {
			$ticket = new stdClass();
			$ticket->id = intval($rs->fields['id']);
			$ticket->mask = $rs->fields['mask'];
			$ticket->subject = $rs->fields['subject'];
			$ticket->status = $rs->fields['status'];
			$ticket->last_wrote = $rs->fields['last_wrote'];
			$tickets[$ticket->id] = $ticket;
			$rs->MoveNext();
		}

		// [JAS]: Count all
		$rs = $um_db->Execute($sql);
		$total = $rs->RecordCount();
		
		return array($tickets,$total);
	}

	function getTicket($id) {
		$um_db = UserMeetDatabase::getInstance();
		
		$ticket = null;
		
		$sql = sprintf("SELECT t.id , t.mask, t.subject, t.status, t.last_wrote ".
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
			$ticket->last_wrote = $rs->fields['last_wrote'];
		}
		
		return $ticket;
	}
	
};

class CerberusModuleExtension extends UserMeetExtension {
	function CerberusModuleExtension($manifest) {
		$this->UserMeetExtension($manifest,1);
	}
	
	function isVisible() { return true; }
	function render() { }
	
	function getLink() { return "#"; }
	function click() { 
//		echo "You clicked: " . __CLASS__ . "->" . __FUNCTION__ . "!<br>";
		CerberusApplication::setActiveModule($this->manifest->id);
	}
};

?>