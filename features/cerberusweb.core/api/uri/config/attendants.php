<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2016, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.io/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.io	    http://webgroup.media
***********************************************************************/

class PageSection_SetupAttendants extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(ChConfigurationPage::ID, 'bots');
		
		// Context
		
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$context_id = DevblocksPlatform::importGPC($_REQUEST['context_id'],'integer','');
		
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);

		$view_id = 'setup_virtual_attendants';
		
		$view = C4_AbstractViewLoader::getView($view_id);
		
		if(null == $view) {
			$ctx = Extension_DevblocksContext::get(CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT);
			$view = $ctx->getChooserView($view_id);
		}
		
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/attendants/index.tpl');
	}
};