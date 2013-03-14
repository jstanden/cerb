<?php
/***********************************************************************
| Cerb(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2013, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/

class PageSection_SetupAttendants extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(ChConfigurationPage::ID, 'attendants');
		
		// Context
		
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer','');
		
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		
		// Roles/Groups/Workers
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$roles = DAO_WorkerRole::getAll();
		$tpl->assign('roles', $roles);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		// Render
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/attendants/index.tpl');
	}
};