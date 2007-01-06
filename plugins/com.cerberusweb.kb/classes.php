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
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		@$id = intval($_REQUEST['id']);
		
		$tree = DAO_Kb::getCategoryTree();
		$tpl->assign('node', $tree[$id]);

		$half = round(count($tree[$id]->children)/2);
		$tpl->assign('half', $half);
		
		$trail = DAO_Kb::getBreadcrumbTrail($tree,$id);
		$tpl->assign('trail', $trail);
		
		$resources = CerberusSearchDAO::searchResources(
			array(
				new CerberusSearchCriteria(CerberusResourceSearchFields::KB_CATEGORY_ID,'in',array($id))
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
		@$id = $_REQUEST['id'];
		@$parent = $_REQUEST['parent'];
		
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