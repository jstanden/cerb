<?php
/***********************************************************************
| Cerb(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2013, WebGroup Media LLC
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
			SearchFields_MailQueue::QUEUE_DELIVERY_DATE,
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