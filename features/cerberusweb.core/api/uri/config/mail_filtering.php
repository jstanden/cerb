<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2011, WebGroup Media LLC
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

class PageSection_SetupMailFiltering extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();

		$visit->set(ChConfigurationPage::ID, 'mail_filtering');
		
		$context = 'cerberusweb.contexts.app';
		$context_id = 0;
//		$point = 'cerberusweb.page.config.attendants';
		
		if(empty($context)) // || empty($context_id)
			return;
			
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
			
		// Events
		$events = array(
			Event_MailReceivedByApp::ID => DevblocksPlatform::getExtension(Event_MailReceivedByApp::ID, false),
		);
		$tpl->assign('events', $events);
		
		// Triggers
		$triggers = DAO_TriggerEvent::getByOwner($context, $context_id, null, true);
		$tpl->assign('triggers', $triggers);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/mail_filtering/index.tpl');
	}
}
