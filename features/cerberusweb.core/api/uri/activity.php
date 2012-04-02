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

class ChActivityPage extends CerberusPageExtension {
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == ($worker = CerberusApplication::getActiveWorker()))
			return false;
		
		return $worker->hasPriv('core.activity');
	}
	
	function getActivity() {
		return new Model_Activity('activity.activity');
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$response = DevblocksPlatform::getHttpResponse();

		// Remember the last tab/URL
		$visit = CerberusApplication::getVisit();
		if(null == ($selected_tab = @$response->path[1])) {
			$selected_tab = $visit->get(Extension_ActivityTab::POINT, '');
		}
		$tpl->assign('selected_tab', $selected_tab);

		// Path
		$stack = $response->path;
		array_shift($stack); // activity
		
		$tab_manifests = DevblocksPlatform::getExtensions(Extension_ActivityTab::POINT, false);
		DevblocksPlatform::sortObjects($tab_manifests, 'name');
		$tpl->assign('tab_manifests', $tab_manifests);
		
		$tpl->display('devblocks:cerberusweb.core::activity/index.tpl');
	}
	
	// Ajax
	function showTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		$visit = CerberusApplication::getVisit();
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_ActivityTab) {
				$visit->set(Extension_ActivityTab::POINT, $inst->manifest->params['uri']);
				$inst->showTab();
		}
	}
	
};
