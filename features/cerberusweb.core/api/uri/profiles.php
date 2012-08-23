<?php
/***********************************************************************
| Cerb(tm) developed by WebGroup Media, LLC.
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

class Page_Profiles extends CerberusPageExtension {
	const ID = 'cerberusweb.page.profiles';
	
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == ($worker = CerberusApplication::getActiveWorker()))
			return false;
		
		return true;
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		$response = DevblocksPlatform::getHttpResponse();
		
		$stack = $response->path;
		@array_shift($stack); // profiles
		@$section_uri = array_shift($stack);

		if(empty($section_uri))
			$section_uri = 'worker';

		// Subpage
		$subpage = Extension_PageSection::getExtensionByPageUri($this->manifest->id, $section_uri, true);
		$tpl->assign('subpage', $subpage);
		
		$tpl->display('devblocks:cerberusweb.core::profiles/index.tpl');
	}
	
	function handleSectionActionAction() {
		@$section_uri = DevblocksPlatform::importGPC($_REQUEST['section'],'string','');
		@$action = DevblocksPlatform::importGPC($_REQUEST['action'],'string','');

		$inst = Extension_PageSection::getExtensionByPageUri($this->manifest->id, $section_uri, true);
		
		if($inst instanceof Extension_PageSection && method_exists($inst, $action.'Action')) {
			call_user_func(array($inst, $action.'Action'));
		}
	}
	
	function showTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string','');
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer',0);
		
		$visit = CerberusApplication::getVisit();
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id))
				&& null != ($inst = $tab_mft->createInstance())
				&& $inst instanceof Extension_ContextProfileTab) {
			
			if(!empty($point))
				$visit->set($point, $inst->manifest->params['uri']);
			
			$inst->showTab($context, $context_id);
		}
	}
};
