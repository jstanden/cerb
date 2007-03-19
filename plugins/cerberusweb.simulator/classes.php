<?php

class ChSimulatorModule extends CerberusModuleExtension {
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
		
//		$resources = CerberusSearchDAO::searchResources(
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
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/index.tpl.php');
	}
	
};

?>