<?php

class ChFaqPage extends CerberusPageExtension {
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
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
//		@$id = intval(DevblocksPlatform::importGPC($_REQUEST['id']));
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@$id = intval($stack[1]);
		
//		$tree = DAO_Kb::getCategoryTree();
//		$tpl->assign('node', $tree[$id]);
		
//		$resources = DAO_Search::searchResources(
//			array(
//				new CerberusSearchCriteria(CerberusResourceSearchFields::KB_CATEGORY_ID,'in',array($id))
//			),
//			25,
//			0,
//			CerberusResourceSearchFields::KB_TITLE,
//			1
//		);
//		$tpl->assign('resources', $resources[0]);
//		$tpl->assign('resources_total', $resources[1]);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/faq/index.tpl.php');
	}
	
};


class ChDisplayFaq extends CerberusDisplayPageExtension {
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