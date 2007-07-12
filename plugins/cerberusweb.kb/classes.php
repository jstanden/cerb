<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2007, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerberus Helpdesk Team
 * 
 * Sure, it would be so easy to just cheat and edit this file to use the 
 * software without paying for it.  But we trust you anyway.  In fact, we're 
 * writing this software for you! 
 * 
 * Quality software backed by a dedicated team takes money to develop.  We 
 * don't want to be out of the office bagging groceries when you call up 
 * needing a helping hand.  We'd rather spend our free time coding your 
 * feature requests than mowing the neighbors' lawns for rent money. 
 * 
 * We've never believed in encoding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your in-box that you probably 
 * haven't had since spammers found you in a game of "E-mail Address 
 * Battleship".  Miss. Miss. You sunk my in-box!
 * 
 * A legitimate license entitles you to support, access to the developer 
 * mailing list, the ability to participate in betas and the warm fuzzy 
 * feeling of feeding a couple obsessed developers who want to help you get 
 * more done than 'the other guy'.
 *
 * - Jeff Standen, Mike Fogg, Brenan Cavish, Darren Sugita, Dan Hildebrandt
 * 		and Joe Geck.
 *   WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
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