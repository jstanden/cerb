<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2017, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/

class PageSection_SetupPortals extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(ChConfigurationPage::ID, 'portals');
		
		// View
		
		$defaults = C4_AbstractViewModel::loadFromClass('View_CommunityPortal');
		$defaults->id = 'portals_cfg';
		
		$view = C4_AbstractViewLoader::getView($defaults->id, $defaults);
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/portals/index.tpl');
	}
	
	function showAddPortalPeekAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tool_manifests = DevblocksPlatform::getExtensions('usermeet.tool', false);
		
		if(empty($tool_manifests)) {
			$tpl->assign('error_message', sprintf("There are no community portals available. Please enable a plugin like the Support Center and try again."));
			$tpl->display('devblocks:cerberusweb.core::internal/peek/peek_error.tpl');
			return;
		}
		
		$tpl->assign('tool_manifests', $tool_manifests);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/portals/add.tpl');
	}
	
	function saveAddPortalPeekAction() {
		@$name = DevblocksPlatform::importGPC($_POST['name'],'string', '');
		@$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'],'string', '');
		
		$portal_code = DAO_CommunityTool::generateUniqueCode();
		
		// Create portal
		$fields = array(
			DAO_CommunityTool::NAME => $name,
			DAO_CommunityTool::EXTENSION_ID => $extension_id,
			DAO_CommunityTool::CODE => $portal_code,
		);
		$portal_id = DAO_CommunityTool::create($fields);
		
		// Redirect to the display page
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','portal',$portal_code)));
	}
}