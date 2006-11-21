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
		if(!empty($module)) $activeModule = $module;
		return $activeModule;
	}
	
	function getActiveModule() {
		return CerberusApplication::setActiveModule(); // returns
	}
	
	// ***************** DUMMY
	function getTicketList() {
		$um_db = UserMeetDatabase::getInstance();
		
		$tickets = array();
		
		$sql = sprintf("SELECT t.id , t.mask, t.subject, t.status, t.last_wrote ".
			"FROM ticket t"
		);
		$rs = $um_db->Execute($sql) or die(__CLASS__ . ':' . $um_db->ErrorMsg()); /* @var $rs ADORecordSet */
		while(!$rs->EOF) {
			$ticket = new stdClass();
			$ticket->id = intval($rs->fields['id']);
			$ticket->mask = $rs->fields['mask'];
			$ticket->subject = $rs->fields['subject'];
			$ticket->status = $rs->fields['status'];
			$ticket->last_wrote = $rs->fields['last_wrote'];
			$tickets[] = $ticket;
			$rs->MoveNext();
		}
		
		return $tickets;
	}
	
	function getTicket($id) {
		
	}
	// ***************** DUMMY
	
};

class CerberusModuleExtension extends UserMeetExtension {
	function CerberusModuleExtension($manifest) {
		$this->UserMeetExtension($manifest,1);
	}
	
	function isVisible() { return true; }
	function render() { }
	
	function getLink() { return "#"; }
	function click() { 
		echo "You clicked: " . __CLASS__ . "->" . __FUNCTION__ . "!<br>";
		CerberusApplication::setActiveModule($this->manifest->id);
	}
};

?>