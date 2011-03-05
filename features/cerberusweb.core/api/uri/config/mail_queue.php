<?php
class PageSection_SetupMailQueue extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(ChConfigurationPage::ID, 'mail_queue');
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = 'config_mail_queue';
		$defaults->name = 'Mail Queue';
		$defaults->class_name = 'View_MailQueue';
		$defaults->view_columns = array(
			SearchFields_MailQueue::HINT_TO,
			SearchFields_MailQueue::UPDATED,
			SearchFields_MailQueue::WORKER_ID,
			SearchFields_MailQueue::QUEUE_FAILS,
			SearchFields_MailQueue::QUEUE_PRIORITY,
		);
		
		if(null != ($view = C4_AbstractViewLoader::getView($defaults->id, $defaults))) {
			$view->addColumnsHidden(array(
				SearchFields_MailQueue::ID,
				SearchFields_MailQueue::IS_QUEUED,
				SearchFields_MailQueue::TICKET_ID,
			));
			$view->addParamsRequired(array(
				SearchFields_MailQueue::IS_QUEUED => new DevblocksSearchCriteria(SearchFields_MailQueue::IS_QUEUED,'=', 1)
			), true);
			$view->addParamsHidden(array(
				SearchFields_MailQueue::ID,
				SearchFields_MailQueue::IS_QUEUED,
				SearchFields_MailQueue::TICKET_ID,
			), true);
			
			C4_AbstractViewLoader::setView($view->id, $view);
			
			$tpl->assign('view', $view);
		} 
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/mail_queue/index.tpl');		
	}
}