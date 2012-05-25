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
		$events = Extension_DevblocksEvent::getByContext($context, false);
		$tpl->assign('events', $events);
		
		// Triggers
		$triggers = DAO_TriggerEvent::getByOwner($context, $context_id, 'event.mail.received.app', true);
		$tpl->assign('triggers', $triggers);

		$triggers_by_event = array();
		
		foreach($triggers as $trigger) {
			if(!isset($triggers_by_event[$trigger->event_point]))
				$triggers_by_event[$trigger->event_point] = array();
			
			$triggers_by_event[$trigger->event_point][$trigger->id] = $trigger;
		}
		
		$tpl->assign('triggers_by_event', $triggers_by_event);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/mail_filtering/index.tpl');
	}
}
