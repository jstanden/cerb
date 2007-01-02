<?php

class ChKnowledgebaseModule extends CerberusModuleExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
		
	function isVisible() {
		// check login
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/knowledgebase/index.tpl.php');
	}
};


class ChDisplayFnr extends CerberusDisplayModuleExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function render($ticket) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/ticket_fnr.tpl.php');
	}
	
	function renderBody() {
		echo "Ticket F&R content goes here!";
	}
}

?>