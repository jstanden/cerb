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

class PageSection_MailWorkspaces extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$active_worker = $visit->getWorker();
		$response = DevblocksPlatform::getHttpResponse();
		
		// Remember the last tab/URL
		if(null == ($selected_tab = @$response->path[1])) {
			$selected_tab = $visit->get(Extension_MailTab::POINT, '');
		}
		$tpl->assign('selected_tab', $selected_tab);
		
		// ====== Renders
		switch($selected_tab) {
			default:
				// Clear all undo actions on reload
			    View_Ticket::clearLastActions();
			    				
				$quick_search_type = $visit->get('quick_search_type');
				$tpl->assign('quick_search_type', $quick_search_type);

				$tpl->display('devblocks:cerberusweb.core::mail/section/workspaces.tpl');
				break;
		}
	}
	
	function showWorkflowTabAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Remember the tab
		$visit->set(Extension_MailTab::POINT, 'workflow');
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$group_buckets = DAO_Bucket::getGroups();
		$tpl->assign('group_buckets', $group_buckets);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		// View
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_Ticket';
		$defaults->id = CerberusApplication::VIEW_MAIL_WORKFLOW;
		$defaults->view_columns = array(
			SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
			SearchFields_Ticket::TICKET_UPDATED_DATE,
			SearchFields_Ticket::TICKET_GROUP_ID,
			SearchFields_Ticket::TICKET_BUCKET_ID,
		);
		$defaults->renderLimit = 10;
		$defaults->renderSortBy = SearchFields_Ticket::TICKET_UPDATED_DATE;
		$defaults->renderSortAsc = 0;
		$defaults->renderSubtotals = SearchFields_Ticket::TICKET_GROUP_ID;
		
		$workflowView = C4_AbstractViewLoader::getView(CerberusApplication::VIEW_MAIL_WORKFLOW, $defaults);
		
		$workflowView->addParamsHidden(array(
			SearchFields_Ticket::REQUESTER_ID,
			SearchFields_Ticket::TICKET_CLOSED,
			SearchFields_Ticket::TICKET_DELETED,
			SearchFields_Ticket::TICKET_WAITING,
			SearchFields_Ticket::TICKET_BUCKET_ID,
			SearchFields_Ticket::CONTEXT_LINK,
			SearchFields_Ticket::CONTEXT_LINK_ID,
			SearchFields_Ticket::VIRTUAL_ASSIGNABLE,
			SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER,
			SearchFields_Ticket::VIRTUAL_STATUS,
		), true);
		$workflowView->addParamsDefault(array(
		), true);
		$workflowView->addParamsRequired(array(
			SearchFields_Ticket::VIRTUAL_STATUS => new DevblocksSearchCriteria(SearchFields_Ticket::VIRTUAL_STATUS,'',array('open')),
			SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER => new DevblocksSearchCriteria(SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER,'=',$active_worker->id),
			SearchFields_Ticket::VIRTUAL_ASSIGNABLE => new DevblocksSearchCriteria(SearchFields_Ticket::VIRTUAL_ASSIGNABLE,null,true),
		), true);
		
		$workflowView->renderPage = 0;
		
		$workflowView->name = $translate->_('mail.workflow');
		C4_AbstractViewLoader::setView($workflowView->id, $workflowView);
		
		$tpl->assign('view', $workflowView);
		
		// Log activity
		DAO_Worker::logActivity(
			new Model_Activity(
				'activity.mail.workflow',
				array(
					'<i>'.$workflowView->name.'</i>'
				)
			)
		);
		
		// ====== Who's Online
		$whos_online = DAO_Worker::getAllOnline();
		if(!empty($whos_online)) {
			$tpl->assign('whos_online', $whos_online);
			$tpl->assign('whos_online_count', count($whos_online));
		}
		
        $tpl->display('devblocks:cerberusweb.core::mail/section/workspaces/workflow.tpl');
	}	
}