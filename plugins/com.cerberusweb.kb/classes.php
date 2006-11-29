<?php

class ChKnowledgebaseModule extends CerberusModuleExtension {
	function ChKnowledgebaseModule($manifest) {
		$this->CerberusModuleExtension($manifest);
	}
	
	function isVisible() {
		// check login
		$session = UserMeetSessionManager::getInstance();
		$visit = $session->getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}
	
	function render() {
		$tpl = UserMeetTemplateManager::getInstance();
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/knowledgebase/index.tpl.php');
	}
};


class ChDisplayFnr extends CerberusDisplayModuleExtension {
	function ChDisplayFnr($manifest) {
		$this->CerberusDisplayModuleExtension($manifest);
	}

	function render($ticket) {
		$tpl = UserMeetTemplateManager::getInstance();
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/ticket_fnr.tpl.php');
	}
	
	function renderBody() {
		echo "Ticket F&R content goes here!";
	}
}

?>