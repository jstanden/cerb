<?php

class ChKnowledgebasePage extends CerberusPageExtension {
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
		
		$tree = DAO_Kb::getCategoryTree();
		$tpl->assign('node', $tree[$id]);

		$half = round(count($tree[$id]->children)/2);
		$tpl->assign('half', $half);
		
		$trail = DAO_Kb::getBreadcrumbTrail($tree,$id);
		$tpl->assign('trail', $trail);
		
		$resources = DAO_Search::searchResources(
			array(
				new DevblocksSearchCriteria(CerberusResourceSearchFields::KB_CATEGORY_ID,'in',array($id))
			),
			25,
			0,
			CerberusResourceSearchFields::KB_TITLE,
			1
		);
		$tpl->assign('resources', $resources[0]);
		$tpl->assign('resources_total', $resources[1]);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/knowledgebase/index.tpl.php');
	}
	
	function getKbCategoryDialog() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$tree = DAO_Kb::getCategoryTree();
		$tpl->assign('node', $tree[0]);
		
		$sorted = array();
		DAO_Kb::buildTreeMap($tree, $sorted);
		$tpl->assign('tree', $tree);
		$tpl->assign('sorted', $sorted);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/knowledgebase/category_jump_dialog.tpl.php');
	}
	
	function getKbCategoryModifyDialog() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$parent = DevblocksPlatform::importGPC($_REQUEST['parent']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		if(!empty($id)) {
			$node = DAO_Kb::getCategory($id);
			$tpl->assign('node', $node);
		}
		
		$tpl->assign('id', $id);
		
		if(empty($parent) && !empty($node)) $parent = $node->parent_id;
		$tpl->assign('parent', $parent);

		$tree = DAO_Kb::getCategoryTree();
		
		// [JAS]: Remove our own category from the tree so we don't create a 
		// parallel universe by setting ourselves as our own parent
		unset($tree[$id]);
		
		$sorted = array();
		DAO_Kb::buildTreeMap($tree, $sorted);
		$tpl->assign('tree', $tree);
		$tpl->assign('sorted', $sorted);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/knowledgebase/category_modify_dialog.tpl.php');
	}
};


class ChDisplayFnr extends CerberusDisplayPageExtension {
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