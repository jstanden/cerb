<?php
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
