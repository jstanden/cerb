<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2012, WebGroup Media LLC
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

class PageSection_WorkspacesGroup extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		$response = DevblocksPlatform::getHttpResponse();

		if(!isset($response->path[2]))
			return;
		
		@$group_id = intval($response->path[2]);
		
		if(null == ($group = DAO_Group::get($group_id)))
			return;
		
		$tpl->assign('group', $group);

		$tpl->assign('workspace_title', $group->name);
		
		$point = sprintf("core.page.workspaces.group.%d", $group_id);
		$tpl->assign('point', $point);
		
		$tpl->display('devblocks:cerberusweb.core::workspaces/tabs.tpl');
	}
};