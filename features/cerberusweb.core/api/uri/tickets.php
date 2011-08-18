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

class ChTicketsPage extends CerberusPageExtension {
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == ($worker = CerberusApplication::getActiveWorker()))
			return false;
		return true;
	}
	
	function getActivity() {
		return new Model_Activity('activity.tickets',array(
	    	""
	    ));
	}
	
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
			case 'compose':
				if(!$active_worker->hasPriv('core.mail.send'))
					break;
				
				$settings = DevblocksPlatform::getPluginSettingsService();
				
				@$defaults_to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');
				$tpl->assign('defaults_to', $defaults_to);
				
				// Workers
				$workers = DAO_Worker::getAllActive();
				$tpl->assign('workers', $workers);
				
				// Groups
				$teams = DAO_Group::getAll();
				$tpl->assign('teams', $teams);
				
				// Groups+Buckets
				$team_categories = DAO_Bucket::getTeams();
				$tpl->assign('team_categories', $team_categories);
				
				// SendMailToolbarItem Extensions
				$sendMailToolbarItems = DevblocksPlatform::getExtensions('cerberusweb.mail.send.toolbaritem', true);
				if(!empty($sendMailToolbarItems))
					$tpl->assign('sendmail_toolbaritems', $sendMailToolbarItems);

				// Attachments				
				$tpl->assign('upload_max_filesize', ini_get('upload_max_filesize'));

				// Preferences
				$tpl->assign('mail_status_compose', DAO_WorkerPref::get($active_worker->id,'mail_status_compose','waiting'));
				
				// Continue a draft?
				// [TODO] We could also display "you have xxx unsent drafts, would you like to continue one?"
				if(null != ($draft_id = @$response->path[2])) {
					$drafts = DAO_MailQueue::getWhere(sprintf("%s = %d AND %s = %d AND %s = %s",
						DAO_MailQueue::ID,
						$draft_id,
						DAO_MailQueue::WORKER_ID,
						$active_worker->id,
						DAO_MailQueue::TYPE,
						C4_ORMHelper::qstr(Model_MailQueue::TYPE_COMPOSE)
					));
					
					if(isset($drafts[$draft_id]))
						$tpl->assign('draft', $drafts[$draft_id]);
				}
				
				// Link to last created ticket
				if($visit->exists('compose.last_ticket')) {
					$ticket_mask = $visit->get('compose.last_ticket');
					$tpl->assign('last_ticket_mask', $ticket_mask);
					$visit->set('compose.last_ticket',null); // clear
				}
				
				$tpl->display('devblocks:cerberusweb.core::tickets/compose/index.tpl');
				break;
				
			case 'create':
				if(!$active_worker->hasPriv('core.mail.log_ticket'))
					break;
				
				// Workers
				$workers = DAO_Worker::getAllActive();
				$tpl->assign('workers', $workers);
				
				// Groups
				$teams = DAO_Group::getAll();
				$tpl->assign('teams', $teams);
				
				// Destinations
				$destinations = DAO_AddressOutgoing::getAll();
				$tpl->assign('destinations', $destinations);

				// Group+Buckets				
				$team_categories = DAO_Bucket::getTeams();
				$tpl->assign('team_categories', $team_categories);
				
				// LogMailToolbarItem Extensions
				$logMailToolbarItems = DevblocksPlatform::getExtensions('cerberusweb.mail.log.toolbaritem', true);
				if(!empty($logMailToolbarItems))
					$tpl->assign('logmail_toolbaritems', $logMailToolbarItems);
				
				// Attachments
				$tpl->assign('upload_max_filesize', ini_get('upload_max_filesize'));

				// Preferences
				$tpl->assign('mail_status_create', DAO_WorkerPref::get($active_worker->id,'mail_status_create','open'));
				
				// Continue a draft?
				// [TODO] We could also display "you have xxx unsent drafts, would you like to continue one?"
				if(null != ($draft_id = @$response->path[2])) {
					$drafts = DAO_MailQueue::getWhere(sprintf("%s = %d AND %s = %d AND %s = %s",
						DAO_MailQueue::ID,
						$draft_id,
						DAO_MailQueue::WORKER_ID,
						$active_worker->id,
						DAO_MailQueue::TYPE,
						C4_ORMHelper::qstr(Model_MailQueue::TYPE_OPEN_TICKET)
					));
					
					if(isset($drafts[$draft_id]))
						$tpl->assign('draft', $drafts[$draft_id]);
				}
				
				// Link to last created ticket
				if($visit->exists('compose.last_ticket')) {
					$ticket_mask = $visit->get('compose.last_ticket');
					$tpl->assign('last_ticket_mask', $ticket_mask);
					$visit->set('compose.last_ticket',null); // clear
				}
				
				$tpl->display('devblocks:cerberusweb.core::tickets/create/index.tpl');
				break;
				
			default:
				// Clear all undo actions on reload
			    View_Ticket::clearLastActions();
			    				
				$quick_search_type = $visit->get('quick_search_type');
				$tpl->assign('quick_search_type', $quick_search_type);

				$tpl->display('devblocks:cerberusweb.core::tickets/index.tpl');
				break;
		}
		
	}
	
	// Ajax
	function showTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		$visit = CerberusApplication::getVisit();
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_MailTab) {
				$visit->set(Extension_MailTab::POINT, $inst->manifest->params['uri']);
				$inst->showTab();
		}
	}
	
	// Post
	function saveTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_MailTab) {
				$inst->saveTab();
		}
	}
	
	/*
	 * [TODO] Proxy any func requests to be handled by the tab directly, 
	 * instead of forcing tabs to implement controllers.  This should check 
	 * for the *Action() functions just as a handleRequest would
	 */
	function handleTabActionAction() {
		@$tab = DevblocksPlatform::importGPC($_REQUEST['tab'],'string','');
		@$action = DevblocksPlatform::importGPC($_REQUEST['action'],'string','');

		if(null != ($tab_mft = DevblocksPlatform::getExtension($tab)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_MailTab) {
				if(method_exists($inst,$action.'Action')) {
					call_user_func(array(&$inst, $action.'Action'));
				}
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
		
		$group_buckets = DAO_Bucket::getTeams();
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
			SearchFields_Ticket::TICKET_TEAM_ID,
			SearchFields_Ticket::TICKET_CATEGORY_ID,
		);
		$defaults->renderLimit = 10;
		$defaults->renderSortBy = SearchFields_Ticket::TICKET_UPDATED_DATE;
		$defaults->renderSortAsc = 0;
		$defaults->renderSubtotals = SearchFields_Ticket::TICKET_TEAM_ID;
		
		$workflowView = C4_AbstractViewLoader::getView(CerberusApplication::VIEW_MAIL_WORKFLOW, $defaults);
		
		$workflowView->addParamsHidden(array(
			SearchFields_Ticket::REQUESTER_ID,
			SearchFields_Ticket::TICKET_CLOSED,
			SearchFields_Ticket::TICKET_DELETED,
			SearchFields_Ticket::TICKET_WAITING,
			SearchFields_Ticket::TICKET_CATEGORY_ID,
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
		
        $tpl->display('devblocks:cerberusweb.core::tickets/workflow/index.tpl');
	}
	
	function showMessagesTabAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Log activity
//		DAO_Worker::logActivity(
//			new Model_Activity(
//				'activity.mail.search'
//			)
//		);
		
		// Remember the tab
		$visit->set(Extension_MailTab::POINT, 'messages');
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = CerberusApplication::VIEW_MAIL_MESSAGES;
		$defaults->class_name = 'View_Message';
		$defaults->renderSortBy = SearchFields_Message::CREATED_DATE;
		$defaults->renderSortAsc = false;
		$defaults->paramsEditable = $defaults->paramsDefault;
		$view = C4_AbstractViewLoader::getView($defaults->id, $defaults);

		$view->addParamsDefault(array(
			new DevblocksSearchCriteria(SearchFields_Message::IS_OUTGOING,'=',1),
			new DevblocksSearchCriteria(SearchFields_Message::CREATED_DATE,DevblocksSearchCriteria::OPER_BETWEEN,array('-30 days', 'now')),
		), true);
		$view->addParamsRequired(array(
			'_req_group_id' => new DevblocksSearchCriteria(SearchFields_Message::TICKET_GROUP_ID,'in',array_keys($active_worker->getMemberships())),
		), true);
		
		C4_AbstractViewLoader::setView($view->id,$view);
		
		$tpl->assign('view', $view);
	
		$tpl->display('devblocks:cerberusweb.core::tickets/messages/index.tpl');
	}
	
	function showSearchTabAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Log activity
		DAO_Worker::logActivity(
			new Model_Activity(
				'activity.mail.search'
			)
		);
		
		// Remember the tab
		$visit->set(Extension_MailTab::POINT, 'search');
		
		// [TODO] Convert to defaults
		
		if(null == ($view = C4_AbstractViewLoader::getView(CerberusApplication::VIEW_SEARCH))) {
			$view = View_Ticket::createSearchView();
		}
		
		$view->addParamsDefault(array(
			SearchFields_Ticket::VIRTUAL_STATUS => new DevblocksSearchCriteria(SearchFields_Ticket::VIRTUAL_STATUS,'',array('open','waiting')),
		), true);
		$view->addParamsRequired(array(
			SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER => new DevblocksSearchCriteria(SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER,'=',$active_worker->id),
		), true);
		
		C4_AbstractViewLoader::setView($view->id,$view);
		
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::tickets/search/index.tpl');
	}
	
	function showDraftsTabAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		$tpl = DevblocksPlatform::getTemplateService();

		// Remember the tab
		$visit->set(Extension_MailTab::POINT, 'drafts');
		
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_MailQueue';
		$defaults->id = 'mail_drafts';
		
		$view = C4_AbstractViewLoader::getView($defaults->id, $defaults);
		$view->name = 'Drafts';
		
		$view->addColumnsHidden(array(
			SearchFields_MailQueue::ID,
			SearchFields_MailQueue::IS_QUEUED,
			SearchFields_MailQueue::QUEUE_FAILS,
			SearchFields_MailQueue::QUEUE_DELIVERY_DATE,
			SearchFields_MailQueue::TICKET_ID,
			SearchFields_MailQueue::WORKER_ID,
		), true);
		
		$view->addParamsRequired(array(
			SearchFields_MailQueue::WORKER_ID => new DevblocksSearchCriteria(SearchFields_MailQueue::WORKER_ID, DevblocksSearchCriteria::OPER_EQ, $active_worker->id),
			SearchFields_MailQueue::IS_QUEUED => new DevblocksSearchCriteria(SearchFields_MailQueue::IS_QUEUED, DevblocksSearchCriteria::OPER_EQ, 0),
		), true);
		$view->addParamsHidden(array(
			SearchFields_MailQueue::ID,
			SearchFields_MailQueue::IS_QUEUED,
			SearchFields_MailQueue::QUEUE_FAILS,
			SearchFields_MailQueue::QUEUE_DELIVERY_DATE,
			SearchFields_MailQueue::TICKET_ID,
			SearchFields_MailQueue::WORKER_ID,
		), true);
		
		C4_AbstractViewLoader::setView($view->id,$view);
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::mail/queue/index.tpl');
	}
	
	function saveDraftAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		@$draft_id = DevblocksPlatform::importGPC($_REQUEST['draft_id'],'integer',0); 

		// Common
		@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string',''); 
		@$subject = DevblocksPlatform::importGPC($_REQUEST['subject'],'string',''); 
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');

		// Compose
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['team_id'],'integer',0); 
		@$cc = DevblocksPlatform::importGPC($_REQUEST['cc'],'string',''); 
		@$bcc = DevblocksPlatform::importGPC($_REQUEST['bcc'],'string',''); 
		
		// Open Ticket
		@$requesters = DevblocksPlatform::importGPC($_REQUEST['reqs'],'string',''); 
		@$send_to_reqs = DevblocksPlatform::importGPC($_REQUEST['send_to_requesters'],'integer',0); 
		
		$params = array();
		
		$hint_to = null;
		$type = null;
		
		if(!empty($to))
			$params['to'] = $to;
			
		if(empty($subject) && empty($content))
			return json_encode(array());
			
		@$type = DevblocksPlatform::importGPC($_REQUEST['type'],'string','');
		
		switch($type) {
			case 'compose':
				if(!empty($cc))
					$params['cc'] = $cc;
				if(!empty($bcc))
					$params['bcc'] = $bcc;
				if(!empty($group_id))
					$params['group_id'] = $group_id;
					
				$type = 'mail.compose';
				$hint_to = $to;
				break;
				
			case 'create':
				if(!empty($requesters))
					$params['requesters'] = $requesters;
				if(!empty($send_to_reqs))
					$params['send_to_reqs'] = $send_to_reqs;
					
				$type = 'mail.open_ticket';
				$hint_to = $requesters;
				break;
				
			default:
				// Bail out
				echo json_encode(array());
				return;
				break;
		}
			
		$fields = array(
			DAO_MailQueue::TYPE => $type,
			DAO_MailQueue::TICKET_ID => 0,
			DAO_MailQueue::WORKER_ID => $active_worker->id,
			DAO_MailQueue::UPDATED => time(),
			DAO_MailQueue::HINT_TO => $hint_to,
			DAO_MailQueue::SUBJECT => $subject,
			DAO_MailQueue::BODY => $content,
			DAO_MailQueue::PARAMS_JSON => json_encode($params),
			DAO_MailQueue::IS_QUEUED => 0,
			DAO_MailQueue::QUEUE_DELIVERY_DATE => time(),
		);
		
		// Make sure the current worker is the draft author
		if(!empty($draft_id)) {
			$draft = DAO_MailQueue::getWhere(sprintf("%s = %d AND %s = %d",
				DAO_MailQueue::ID,
				$draft_id,
				DAO_MailQueue::WORKER_ID,
				$active_worker->id
			));
			
			if(!isset($draft[$draft_id]))
				$draft_id = null;
		}
		
		if(empty($draft_id)) {
			$draft_id = DAO_MailQueue::create($fields);
		} else {
			DAO_MailQueue::update($draft_id, $fields);
		}
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('timestamp', time());
		$html = $tpl->fetch('devblocks:cerberusweb.core::mail/queue/saved.tpl');
		
		echo json_encode(array('draft_id'=>$draft_id, 'html'=>$html));
	}
	
	function deleteDraftAction() {
		@$draft_id = DevblocksPlatform::importGPC($_REQUEST['draft_id'],'integer');
		
		@$active_worker = CerberusApplication::getActiveWorker();
		
		if(!empty($draft_id)
			&& null != ($draft = DAO_MailQueue::get($draft_id))
			&& ($active_worker->id == $draft->worker_id || $active_worker->is_superuser)) {
			
			DAO_MailQueue::delete($draft_id);
		}
	}
	
	function showDraftsPeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		@$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		
		if(null != ($draft = DAO_MailQueue::get($id)))
			if($active_worker->is_superuser || $draft->worker_id==$active_worker->id)
				$tpl->assign('draft', $draft);
		
		$tpl->display('devblocks:cerberusweb.core::mail/queue/peek.tpl');
	}
	
	function showDraftsBulkPanelAction() {
		@$id_csv = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);

	    if(!empty($id_csv)) {
	        $ids = DevblocksPlatform::parseCsvString($id_csv);
	        $tpl->assign('ids', implode(',', $ids));
	    }
		
	    // Lists
//	    $lists = DAO_FeedbackList::getWhere();
//	    $tpl->assign('lists', $lists);
	    
		// Custom Fields
//		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_FEEDBACK);
//		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->display('devblocks:cerberusweb.core::mail/queue/bulk.tpl');		
	}
	
	function doDraftsBulkUpdateAction() {
		// Filter: whole list or check
	    @$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
	    $ids = array();
	    
	    // View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		
		// Draft fields
		@$status = trim(DevblocksPlatform::importGPC($_POST['status'],'string'));

		$do = array();
		
		// Do: Status
		if(0 != strlen($status))
			$do['status'] = $status;
			
		// Do: Custom fields
//		$do = DAO_CustomFieldValue::handleBulkPost($do);

		switch($filter) {
			// Checked rows
			case 'checks':
			    @$ids_str = DevblocksPlatform::importGPC($_REQUEST['ids'],'string');
				$ids = DevblocksPlatform::parseCsvString($ids_str);
				break;
			case 'sample':
				@$sample_size = min(DevblocksPlatform::importGPC($_REQUEST['filter_sample_size'],'integer',0),9999);
				$filter = 'checks';
				$ids = $view->getDataSample($sample_size);
				break;
			default:
				break;
		}

		$view->doBulkUpdate($filter, $do, $ids);
		
		$view->render();
		return;		
	}

	function viewDraftsExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::getUrlService();
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time()); 
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);

		// Page start
		@$explore_from = DevblocksPlatform::importGPC($_REQUEST['explore_from'],'integer',0);
		if(empty($explore_from)) {
			$orig_pos = 1+($view->renderPage * $view->renderLimit);
		} else {
			$orig_pos = 1;
		}
		
		$view->renderPage = 0;
		$view->renderLimit = 250;
		$pos = 0;
		
		do {
			$models = array();
			list($results, $total) = $view->getData();

			// Summary row
			if(0==$view->renderPage) {
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'title' => $view->name,
					'created' => time(),
					'worker_id' => $active_worker->id,
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=tickets&tab=drafts', true),
				);
				$models[] = $model; 
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $draft_id => $row) {
				if($draft_id==$explore_from)
					$orig_pos = $pos;
				
				if($row[SearchFields_MailQueue::TYPE]==Model_MailQueue::TYPE_COMPOSE) {
					$url = $url_writer->writeNoProxy(sprintf("c=tickets&a=compose&id=%d", $draft_id), true);
				} elseif($row[SearchFields_MailQueue::TYPE]==Model_MailQueue::TYPE_TICKET_REPLY) {
					$url = $url_writer->writeNoProxy(sprintf("c=display&id=%d", $row[SearchFields_MailQueue::TICKET_ID]), true) . sprintf("#draft%d", $draft_id);
				}

				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_MailQueue::ID],
					'url' => $url,
				);
				$models[] = $model; 
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
	
	function viewTicketsExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::getUrlService();
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time()); 
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);

		// Page start
		@$explore_from = DevblocksPlatform::importGPC($_REQUEST['explore_from'],'integer',0);
		if(empty($explore_from)) {
			$orig_pos = 1+($view->renderPage * $view->renderLimit);
		} else {
			$orig_pos = 1;
		}
		
		$view->renderPage = 0;
		$view->renderLimit = 250;
		$pos = 0;
		
		do {
			$models = array();
			list($results, $total) = $view->getData();

			// Summary row
			if(0==$view->renderPage) {
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'title' => $view->name,
					'created' => time(),
					'worker_id' => $active_worker->id,
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=tickets', true),
				);
				$models[] = $model; 
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $ticket_id => $row) {
				if($ticket_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->writeNoProxy(sprintf("c=display&mask=%s", $row[SearchFields_Ticket::TICKET_MASK]), true);

				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_Ticket::TICKET_ID],
					'url' => $url,
				);
				$models[] = $model; 
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}
	
	function viewMessagesExploreAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::getUrlService();
		
		// Generate hash
		$hash = md5($view_id.$active_worker->id.time()); 
		
		// Loop through view and get IDs
		$view = C4_AbstractViewLoader::getView($view_id);

		// Page start
		@$explore_from = DevblocksPlatform::importGPC($_REQUEST['explore_from'],'integer',0);
		if(empty($explore_from)) {
			$orig_pos = 1+($view->renderPage * $view->renderLimit);
		} else {
			$orig_pos = 1;
		}
		
		$view->renderPage = 0;
		$view->renderLimit = 250;
		$pos = 0;
		
		do {
			$models = array();
			list($results, $total) = $view->getData();

			// Summary row
			if(0==$view->renderPage) {
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'title' => $view->name,
					'created' => time(),
					//'worker_id' => $active_worker->id,
					'total' => $total,
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->writeNoProxy('c=tickets&tab=messages', true),
//					'toolbar_extension_id' => 'cerberusweb.explorer.toolbar.',
				);
				$models[] = $model; 
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $id => $row) {
				if($id==$explore_from)
					$orig_pos = $pos;
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $id,
					'url' => $url_writer->writeNoProxy(sprintf("c=display&id=%s", $row[SearchFields_Message::TICKET_MASK]), true),
				);
				$models[] = $model; 
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}	
	
	// Ajax
	function reportSpamAction() {
	    @$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
	    @$view_id = DevblocksPlatform::importGPC($_REQUEST['viewId'],'string');
	    if(empty($id)) return;

		$fields = array(
				DAO_Ticket::IS_CLOSED => 1,
				DAO_Ticket::IS_DELETED => 1,
		);
	    
	    
        //====================================
	    // Undo functionality
        $last_action = new Model_TicketViewLastAction();
        $last_action->action = Model_TicketViewLastAction::ACTION_SPAM;

		$last_action->ticket_ids[$id] = array(
				DAO_Ticket::SPAM_TRAINING => CerberusTicketSpamTraining::BLANK,
				DAO_Ticket::SPAM_SCORE => 0.5000, // [TODO] Fix
				DAO_Ticket::IS_CLOSED => 0,
				DAO_Ticket::IS_DELETED => 0
		);

        $last_action->action_params = $fields;
        
        View_Ticket::setLastAction($view_id,$last_action);
        //====================================	    
	    
	    CerberusBayes::markTicketAsSpam($id);
	    
	    // [TODO] Move categories (according to config)
	    $fields = array(
	        DAO_Ticket::IS_DELETED => 1,
	        DAO_Ticket::IS_CLOSED => CerberusTicketStatus::CLOSED
	    );
	    DAO_Ticket::update($id, $fields);
	    
	    $tpl = DevblocksPlatform::getTemplateService();

	    $visit = CerberusApplication::getVisit();
		$view = C4_AbstractViewLoader::getView($view_id);
		$tpl->assign('view', $view);
		
		if(!empty($last_action) && !is_null($last_action->ticket_ids)) {
			$tpl->assign('last_action_count', count($last_action->ticket_ids));
		}
		
		$tpl->assign('last_action', $last_action);
		$tpl->display('devblocks:cerberusweb.core::tickets/rpc/ticket_view_output.tpl');
	} 
	
	// Post	
	function doQuickSearchAction() {
        @$type = DevblocksPlatform::importGPC($_POST['type'],'string'); 
        @$query = DevblocksPlatform::importGPC($_POST['query'],'string');

        $query = trim($query);
        
        $visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
		$active_worker = CerberusApplication::getActiveWorker();
		$searchView = C4_AbstractViewLoader::getView(CerberusApplication::VIEW_SEARCH);
		
		if(null == $searchView)
			$searchView = View_Ticket::createSearchView();

        $visit->set('quick_search_type', $type);
        
        $params = array();
        
        switch($type) {
            case "mask":
            	if(is_numeric($query)) {
            		$params[SearchFields_Ticket::TICKET_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_ID,DevblocksSearchCriteria::OPER_EQ,intval($query));
            	} else {
			        if($query && false===strpos($query,'*'))
			            $query .= '*';
            		$params[SearchFields_Ticket::TICKET_MASK] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MASK,DevblocksSearchCriteria::OPER_LIKE,strtoupper($query));
            	}
                break;
                
            case "sender":
		        if($query && false===strpos($query,'*'))
		            $query .= '*';
                $params[SearchFields_Ticket::TICKET_FIRST_WROTE] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE,DevblocksSearchCriteria::OPER_LIKE,strtolower($query));               
                break;
                
            case "requester":
		        if($query && false===strpos($query,'*'))
		            $query .= '*';
                $params[SearchFields_Ticket::REQUESTER_ADDRESS] = new DevblocksSearchCriteria(SearchFields_Ticket::REQUESTER_ADDRESS,DevblocksSearchCriteria::OPER_LIKE,strtolower($query));               
                break;
                
            case "subject":
		        if($query && false===strpos($query,'*'))
		            $query .= '*';
            	$params[SearchFields_Ticket::TICKET_SUBJECT] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_SUBJECT,DevblocksSearchCriteria::OPER_LIKE,$query);               
                break;
                
            case "org":
		        if($query && false===strpos($query,'*'))
		            $query .= '*';
            	$params[SearchFields_Ticket::ORG_NAME] = new DevblocksSearchCriteria(SearchFields_Ticket::ORG_NAME,DevblocksSearchCriteria::OPER_LIKE,$query);               
                break;
                
            case "comments_all":
            	$params[SearchFields_Ticket::FULLTEXT_COMMENT_CONTENT] = new DevblocksSearchCriteria(SearchFields_Ticket::FULLTEXT_COMMENT_CONTENT,DevblocksSearchCriteria::OPER_FULLTEXT,array($query,'all'));               
                break;
                
            case "comments_phrase":
            	$params[SearchFields_Ticket::FULLTEXT_COMMENT_CONTENT] = new DevblocksSearchCriteria(SearchFields_Ticket::FULLTEXT_COMMENT_CONTENT,DevblocksSearchCriteria::OPER_FULLTEXT,array($query,'phrase'));               
                break;
                
            case "messages_all":
            	$params[SearchFields_Ticket::FULLTEXT_MESSAGE_CONTENT] = new DevblocksSearchCriteria(SearchFields_Ticket::FULLTEXT_MESSAGE_CONTENT,DevblocksSearchCriteria::OPER_FULLTEXT,array($query,'all'));               
                break;
                
            case "messages_phrase":
            	$params[SearchFields_Ticket::FULLTEXT_MESSAGE_CONTENT] = new DevblocksSearchCriteria(SearchFields_Ticket::FULLTEXT_MESSAGE_CONTENT,DevblocksSearchCriteria::OPER_FULLTEXT,array($query,'phrase'));               
                break;
                
        }
        
		// Force group ACL
		if(!$active_worker->is_superuser)
        	$params[SearchFields_Ticket::TICKET_TEAM_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_TEAM_ID, 'in', array_keys($active_worker->getMemberships()));
        
        $searchView->addParams($params, true);
        $searchView->renderPage = 0;
        
        C4_AbstractViewLoader::setView($searchView->id,$searchView);
        
        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','search')));
	}

	function showComposePeekAction() {
	    @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');
	    
		$visit = CerberusApplication::getVisit();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('view_id', $view_id);
		$tpl->assign('to', $to);
		
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		// Load Defaults
		$team_id = intval($visit->get('compose.defaults.from', ''));
		$tpl->assign('default_group_id', $team_id);
		
		$subject = $visit->get('compose.defaults.subject', '');
		$tpl->assign('default_subject', $subject);
		
		// Preferences
		$tpl->assign('mail_status_compose', DAO_WorkerPref::get($active_worker->id,'mail_status_compose','waiting'));
		
		$tpl->display('devblocks:cerberusweb.core::tickets/compose/peek.tpl');
	}
	
	function saveComposePeekAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$team_id = DevblocksPlatform::importGPC($_POST['team_id'],'integer'); 
		@$to = DevblocksPlatform::importGPC($_POST['to'],'string');
		@$cc = DevblocksPlatform::importGPC($_POST['cc'],'string','');
		@$bcc = DevblocksPlatform::importGPC($_POST['bcc'],'string','');
		@$subject = DevblocksPlatform::importGPC($_POST['subject'],'string','(no subject)');
		@$content = DevblocksPlatform::importGPC($_POST['content'],'string');
		@$files = $_FILES['attachment'];
		@$closed = DevblocksPlatform::importGPC($_POST['closed'],'integer',0);
		@$ticket_reopen = DevblocksPlatform::importGPC($_POST['ticket_reopen'],'string','');
		@$add_me_as_watcher = DevblocksPlatform::importGPC($_POST['add_me_as_watcher'],'integer',0);

		$visit = CerberusApplication::getVisit();

		// Save Defaults
		$visit->set('compose.defaults.from', $team_id);
		$visit->set('compose.defaults.subject', $subject);
		
		// Send
		$properties = array(
			'team_id' => $team_id,
			'to' => $to,
//			'cc' => $cc,
//			'bcc' => $bcc,
			'subject' => $subject,
			'content' => $content,
			'files' => $files,
			'closed' => $closed,
			'ticket_reopen' => $ticket_reopen,
		);
		
		$ticket_id = CerberusMail::compose($properties);

		if($add_me_as_watcher) {
			$active_worker = CerberusApplication::getActiveWorker();
			CerberusContexts::addWatchers(CerberusContexts::CONTEXT_TICKET, $ticket_id, $active_worker->id);
		}
		
		if(!empty($view_id)) {
			$defaults = new C4_AbstractViewModel();
			$defaults->class_name = 'View_Ticket';
			$defaults->id = $view_id;
			
			$view = C4_AbstractViewLoader::getView($view_id, $defaults);
			$view->render();
		}
		exit;
	}
	
	function getComposeSignatureAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer',0);
		@$bucket_id = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'integer',0);
		@$raw = DevblocksPlatform::importGPC($_REQUEST['raw'],'integer',0);
		
		// Parsed or raw?
		$active_worker = !empty($raw) ? null : CerberusApplication::getActiveWorker();
		
		if(empty($group_id) || null == ($group = DAO_Group::get($group_id))) {
			$replyto_default = DAO_AddressOutgoing::getDefault();
			echo $replyto_default->getReplySignature($active_worker);
			
		} else {
			echo $group->getReplySignature($bucket_id, $active_worker);
		}
	}
	
	// [TODO] Refactor for group-based signatures
	function getLogTicketSignatureAction() {
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$replyto_default = DAO_AddressOutgoing::getDefault();
		
		if(false !== ($address = DAO_Address::lookupAddress($email, false)))
			$replyto = DAO_AddressOutgoing::get($address->id);
			
		if(!empty($replyto->reply_signature)) {
			$sig = $replyto->reply_signature;
		} else {
			$sig = $replyto_default->reply_signature;
		}
		
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $active_worker, $token_labels, $token_values);
		echo $tpl_builder->build($sig, $token_values);
	}
	
	// Ajax
	function showPreviewAction() {
	    @$tid = DevblocksPlatform::importGPC($_REQUEST['tid'],'integer',0);
	    @$msgid = DevblocksPlatform::importGPC($_REQUEST['msgid'],'integer',0);
	    @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
	    @$edit_mode = DevblocksPlatform::importGPC($_REQUEST['edit'],'integer',0);
	    
		$tpl = DevblocksPlatform::getTemplateService();

		$tpl->assign('view_id', $view_id);
		$tpl->assign('edit_mode', $edit_mode);

		$messages = array();
		
		if(null != ($ticket = DAO_Ticket::get($tid))) {
			/* @var $ticket Model_Ticket */
		    $tpl->assign('ticket', $ticket);
		    
			$messages = $ticket->getMessages();
		}
		
		// Do we have a specific message to look at?
		if(!empty($msgid) && null != (@$message = $messages[$msgid])) {
			 // Good
		} else {
			$message = null;
			$msgid = null;
			
			if(is_array($messages)) {
				if(null != ($message = end($messages)))
					$msgid = $message->id;
			}
		}

		if(!empty($message)) {
			$tpl->assign('message', $message);
			$tpl->assign('content', $message->getContent());
		}
		
		// Paging
		$message_ids = array_keys($messages);
		$tpl->assign('p_count', count($message_ids));
		if(false !== ($pos = array_search($msgid, $message_ids))) {
			$tpl->assign('p', $pos);
			// Prev
			if($pos > 0)
				$tpl->assign('p_prev', $message_ids[$pos-1]);
			// Next
			if($pos+1 < count($message_ids))
				$tpl->assign('p_next', $message_ids[$pos+1]);
		}
		
		// Props
		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
		
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		
		$team_categories = DAO_Bucket::getTeams();
		$tpl->assign('team_categories', $team_categories);
	    
		// Watchers
		$object_watchers = DAO_ContextLink::getContextLinks(CerberusContexts::CONTEXT_TICKET, array($ticket->id), CerberusContexts::CONTEXT_WORKER);
		$tpl->assign('object_watchers', $object_watchers);
		
		// Custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TICKET);
		$tpl->assign('custom_fields', $custom_fields);
		
		$custom_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_TICKET, $ticket->id);
		if(isset($custom_field_values[$ticket->id]))
			$tpl->assign('custom_field_values', $custom_field_values[$ticket->id]);
		
		// Comments
		$comments = DAO_Comment::getByContext(CerberusContexts::CONTEXT_TICKET, $ticket->id);
		$last_comment = array_shift($comments);
		unset($comments);
		$tpl->assign('last_comment', $last_comment);
			
		// Display
		$tpl->display('devblocks:cerberusweb.core::tickets/rpc/preview_panel.tpl');
	}
	
	// Ajax
	function savePreviewAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$subject = DevblocksPlatform::importGPC($_REQUEST['subject'],'string','');
		@$closed = DevblocksPlatform::importGPC($_REQUEST['closed'],'integer',0);
		@$owner_id = DevblocksPlatform::importGPC($_REQUEST['owner_id'],'integer',0);
		@$bucket = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'string','');
		@$spam_training = DevblocksPlatform::importGPC($_REQUEST['spam_training'],'string','');
		@$ticket_reopen = DevblocksPlatform::importGPC(@$_REQUEST['ticket_reopen'],'string','');
		@$comment = DevblocksPlatform::importGPC(@$_REQUEST['comment'],'string','');

		$active_worker = CerberusApplication::getActiveWorker();
		
		$fields = array(
			DAO_Ticket::SUBJECT => $subject,
			DAO_Ticket::OWNER_ID => $owner_id,
		);
		
		// Status
		if(isset($closed)) {
			switch($closed) {
				case 0: // open
					$fields[DAO_Ticket::IS_WAITING] = 0;
					$fields[DAO_Ticket::IS_CLOSED] = 0;
					$fields[DAO_Ticket::IS_DELETED] = 0;
					$fields[DAO_Ticket::DUE_DATE] = 0;
					break;
				case 1: // closed
					$fields[DAO_Ticket::IS_WAITING] = 0;
					$fields[DAO_Ticket::IS_CLOSED] = 1;
					$fields[DAO_Ticket::IS_DELETED] = 0;
					break;
				case 2: // waiting
					$fields[DAO_Ticket::IS_WAITING] = 1;
					$fields[DAO_Ticket::IS_CLOSED] = 0;
					$fields[DAO_Ticket::IS_DELETED] = 0;
					break;
				case 3: // deleted
					$fields[DAO_Ticket::IS_WAITING] = 0;
					$fields[DAO_Ticket::IS_CLOSED] = 1;
					$fields[DAO_Ticket::IS_DELETED] = 1;
					$fields[DAO_Ticket::DUE_DATE] = 0;
					break;
			}
			
			if(1==$closed || 2==$closed) {
				if(!empty($ticket_reopen) && false !== ($due = strtotime($ticket_reopen))) {
					$fields[DAO_Ticket::DUE_DATE] = $due;
				} else {
					$fields[DAO_Ticket::DUE_DATE] = 0;
				}
			}
		}
		
		// Team/Category
		if(!empty($bucket)) {
			list($team_id,$bucket_id) = CerberusApplication::translateTeamCategoryCode($bucket);

			if(!empty($team_id)) {
			    $fields[DAO_Ticket::TEAM_ID] = $team_id;
			    $fields[DAO_Ticket::CATEGORY_ID] = $bucket_id;
			}
		}
		
		// Spam Training
		if(!empty($spam_training)) {
			if('S'==$spam_training)
				CerberusBayes::markTicketAsSpam($id);
			elseif('N'==$spam_training)
				CerberusBayes::markTicketAsNotSpam($id);
		}
		
		DAO_Ticket::update($id, $fields);
		
		// Custom field saves
		@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
		DAO_CustomFieldValue::handleFormPost(CerberusContexts::CONTEXT_TICKET, $id, $field_ids);

		// Comments
		if(!empty($comment)) {
			@$also_notify_worker_ids = DevblocksPlatform::importGPC($_REQUEST['notify_worker_ids'],'array',array());
			
			$fields = array(
				DAO_Comment::CREATED => time(),
				DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_TICKET,
				DAO_Comment::CONTEXT_ID => $id,
				DAO_Comment::COMMENT => $comment,
				DAO_Comment::ADDRESS_ID => $active_worker->getAddress()->id,
			);
			$comment_id = DAO_Comment::create($fields, $also_notify_worker_ids);
		}		
		exit;
	}
	
	function composeMailAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->hasPriv('core.mail.send'))
			return;
		
		@$draft_id = DevblocksPlatform::importGPC($_POST['draft_id'],'integer');
		 
		@$team_id = DevblocksPlatform::importGPC($_POST['team_id'],'integer'); 
		@$to = DevblocksPlatform::importGPC($_POST['to'],'string');
		@$cc = DevblocksPlatform::importGPC($_POST['cc'],'string','');
		@$bcc = DevblocksPlatform::importGPC($_POST['bcc'],'string','');
		@$subject = DevblocksPlatform::importGPC($_POST['subject'],'string','(no subject)');
		@$content = DevblocksPlatform::importGPC($_POST['content'],'string');
		@$files = $_FILES['attachment'];
		
		@$closed = DevblocksPlatform::importGPC($_POST['closed'],'integer',0);
		@$move_bucket = DevblocksPlatform::importGPC($_POST['bucket_id'],'string','');
		@$ticket_reopen = DevblocksPlatform::importGPC($_POST['ticket_reopen'],'string','');
		@$add_me_as_watcher = DevblocksPlatform::importGPC($_POST['add_me_as_watcher'],'integer',0);
		
		if(empty($to)) {
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','compose')));
			return;
		}

		$properties = array(
			'draft_id' => $draft_id,
			'team_id' => $team_id,
			'to' => $to,
			'cc' => $cc,
			'bcc' => $bcc,
			'subject' => $subject,
			'content' => $content,
			'files' => $files,
			'closed' => $closed,
			'move_bucket' => $move_bucket,
			'ticket_reopen' => $ticket_reopen,
		);
		$ticket_id = CerberusMail::compose($properties);
		
		if(!empty($ticket_id)) {
			if(!empty($draft_id))
				DAO_MailQueue::delete($draft_id);
				
			if($add_me_as_watcher) {
				$worker = CerberusApplication::getActiveWorker();
				CerberusContexts::addWatchers(CerberusContexts::CONTEXT_TICKET, $ticket_id, $active_worker->id);
			}
				
			$ticket = DAO_Ticket::get($ticket_id);
			
			$visit = CerberusApplication::getVisit(); /* @var CerberusVisit $visit */
			$visit->set('compose.last_ticket', $ticket->mask);
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','compose')));
		exit;
	}
	
	function logTicketAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->hasPriv('core.mail.log_ticket'))
			return;
			
		$translate = DevblocksPlatform::getTranslationService();
		
		@$to = DevblocksPlatform::importGPC($_POST['to'],'string');
		@$reqs = DevblocksPlatform::importGPC($_POST['reqs'],'string');
		@$subject = DevblocksPlatform::importGPC($_POST['subject'],'string');
		@$content = DevblocksPlatform::importGPC($_POST['content'],'string');
		
		@$draft_id = DevblocksPlatform::importGPC($_POST['draft_id'],'integer');		
		
		@$closed = DevblocksPlatform::importGPC($_POST['closed'],'integer',0);
		@$ticket_reopen = DevblocksPlatform::importGPC($_POST['ticket_reopen'],'string','');
		@$watcher_ids = DevblocksPlatform::importGPC($_POST['worker_id'],'array',array());
		
		// ********
		
		$message = new CerberusParserMessage();
		$message->headers['date'] = date('r'); 
		$message->headers['to'] = $to;
		$message->headers['subject'] = $subject;
		$message->headers['message-id'] = CerberusApplication::generateMessageId();
		//$message->headers['x-cerberus-portal'] = 1; 
		
		// Sender
		$fromList = imap_rfc822_parse_adrlist(rtrim($reqs,', '),'');
		
		if(empty($fromList) || !is_array($fromList)) {
			return; // abort with message
		}
		$from = array_shift($fromList);
		$from_address = $from->mailbox . '@' . $from->host;
		$message->headers['from'] = $from_address;

		$message->body =
			vsprintf($translate->_('mail.create.on_behalf'), $active_worker->getName()). 
			"\n".
			"\n".
			$content
			;
		
		// Files
		if(isset($_FILES['attachment']))
		foreach($_FILES['attachment']['name'] as $idx => $tmp_name) {
			@$tmp_file = $_FILES['attachment']['tmp_name'][$idx];
			if(empty($tmp_file))
				continue;
			$file = new ParserFile();
			$file->setTempFile($tmp_file, $_FILES['attachment']['type'][$idx]);
			$file->file_size = $_FILES['attachment']['size'][$idx];
			$message->files[$tmp_name] = $file;
		}
			
		// [TODO] Custom fields
		
		// Parse
		// [TODO] Parser override not spam
		$ticket_id = CerberusParser::parseMessage($message);
		
		if(!empty($draft_id))
			DAO_MailQueue::delete($draft_id);
			
		// Add additional requesters to ticket
		if(is_array($fromList) && !empty($fromList))
		foreach($fromList as $requester) {
			if(empty($requester))
				continue;
			$host = empty($requester->host) ? 'localhost' : $requester->host;
			DAO_Ticket::createRequester($requester->mailbox . '@' . $host, $ticket_id);
		}
		
		$fields = array();

		// Status
		if(!empty($closed)) {
			switch($closed) {
				case 1:
					$fields[DAO_Ticket::IS_WAITING] = 0;
					$fields[DAO_Ticket::IS_CLOSED] = 1;
					$fields[DAO_Ticket::IS_DELETED] = 0;
					break;
				case 2:
					$fields[DAO_Ticket::IS_WAITING] = 1;
					$fields[DAO_Ticket::IS_CLOSED] = 0;
					$fields[DAO_Ticket::IS_DELETED] = 0;
					break;
			}
			
			if(false !== (@$ticket_reopen = strtotime($ticket_reopen)))
				$fields[DAO_Ticket::DUE_DATE] = $ticket_reopen; 
		}
		
		// Watchers
		if(!empty($watcher_ids))
			CerberusContexts::addWatchers(CerberusContexts::CONTEXT_TICKET, $ticket_id, $watcher_ids);
		
		if(!empty($fields))
			DAO_Ticket::update($ticket_id, $fields);
		
		$ticket = DAO_Ticket::get($ticket_id);			
		
		// ********

		$visit = CerberusApplication::getVisit(); /* @var CerberusVisit $visit */
		$visit->set('compose.last_ticket', $ticket->mask);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','create')));
	}
	
	function showViewAutoAssistAction() {
        @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
        @$mode = DevblocksPlatform::importGPC($_REQUEST['mode'],'string','senders');
        @$mode_param = DevblocksPlatform::importGPC($_REQUEST['mode_param'],'string','');

		$tpl = DevblocksPlatform::getTemplateService();
        
        $visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */

        $view = C4_AbstractViewLoader::getView($view_id);
        
        $tpl->assign('view_id', $view_id);
        $tpl->assign('mode', $mode);

        if($mode == "headers" && empty($mode_param)) {
	        $tpl->display('devblocks:cerberusweb.core::tickets/rpc/ticket_view_assist_headers.tpl');
	        
        } else {
			$teams = DAO_Group::getAll();
			$tpl->assign('teams', $teams);
			
			$team_categories = DAO_Bucket::getTeams();
			$tpl->assign('team_categories', $team_categories);
			
			$category_name_hash = DAO_Bucket::getCategoryNameHash();
			$tpl->assign('category_name_hash', $category_name_hash);
	        
			$workers = DAO_Worker::getAllActive();
			$tpl->assign('workers', $workers);
			
			// Enforce group memberships
			$active_worker = CerberusApplication::getActiveWorker();
			$memberships = $active_worker->getMemberships();
			
			$params = $view->getParams();
			$params[] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_TEAM_ID, 'in', array_keys($memberships)); 
			
	        // [JAS]: Calculate statistics about the current view (top unique senders/subjects/domains)
	        
		    $biggest = DAO_Ticket::analyze($params, 15, $mode, $mode_param);
		    $tpl->assign('biggest', $biggest);
	        
	        $tpl->display('devblocks:cerberusweb.core::tickets/rpc/ticket_view_assist.tpl');
        }
	}
	
	function viewAutoAssistAction() {
	    @$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');

        $visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
		$view = C4_AbstractViewLoader::getView($view_id);

		$buckets = DAO_Bucket::getAll();
		
	    @$piles_always = DevblocksPlatform::importGPC($_POST['piles_always'],'array', array());
	    @$piles_hash = DevblocksPlatform::importGPC($_POST['piles_hash'],'array', array());
	    @$piles_moveto = DevblocksPlatform::importGPC($_POST['piles_moveto'],'array', array());
	    @$piles_type = DevblocksPlatform::importGPC($_POST['piles_type'],'array', array());
	    @$piles_type_param = DevblocksPlatform::importGPC($_POST['piles_type_param'],'array', array());
	    @$piles_value = DevblocksPlatform::importGPC($_POST['piles_value'],'array', array());
	    
	    $piles_always = array_flip($piles_always); // Flip hash

	    // Enforce worker memberships
		$active_worker = CerberusApplication::getActiveWorker();
		$memberships = $active_worker->getMemberships();
		$view->addParam(new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_TEAM_ID, 'in', array_keys($memberships)), 'tmpMemberships'); 
	    
	    foreach($piles_hash as $idx => $hash) {
	        @$moveto = $piles_moveto[$idx];
	        @$type = $piles_type[$idx];
	        @$type_param = $piles_type_param[$idx];
	        @$val = $piles_value[$idx];
	        
	        /*
	         * [TODO] [JAS]: Somewhere here we should be ignoring these values for a bit
	         * so other options have a chance to bubble up
	         */
	        if(empty($hash) || empty($moveto) || empty($type) || empty($val))
	            continue;
	        
	        switch(strtolower(substr($moveto,0,1))) {
	            // Team/Bucket Move
	            case 't':
	            	$g_id = intval(substr($moveto,1));
	            	$doActions = array(
	            		'move' => array(
	            			'group_id' => $g_id,
	            			'bucket_id' => 0,
	            		)
	            	);
	            	break;
	            	
	            case 'c':
            		$b_id = intval(substr($moveto,1));
            		@$g_id = intval($buckets[$b_id]->team_id);
            		
            		if(!empty($g_id))
	            	$doActions = array(
	            		'move' => array(
	            			'group_id' => $g_id,
	            			'bucket_id' => $b_id,
	            		)
	            	);
	                break;
	                
	            // Action
	            case 'a':
	                switch(strtolower(substr($moveto,1))) {
	                    case 'c': // close
							$doActions = array(
								'status' => array(
									'is_closed' => 1,
									'is_deleted' => 0,
								)
							);
	                    	break;
	                    case 's': // spam
							$doActions = array(
								'status' => array(
									'is_closed' => 1,
									'is_deleted' => 1,
								),
								'spam' => array(
									'is_spam' => 1,
								)
							);
							break;
	                    case 'd': // delete
							$doActions = array(
								'status' => array(
									'is_closed' => 1,
									'is_deleted' => 1,
								)
							);
	                    	break;
	                }
	                break;
	                
				// Worker
	            case 'w':
            		$w_id = intval(substr($moveto,1));
            		
            		if(!empty($w_id))
	            	$doActions = array(
	            		'watchers' => array(
	            			'add' => array($w_id),
	            		)
	            	);
	                break;
	                
	            default:
	                $doActions = array();
	                break;
	        }
	        
            $doTypeParam = $type_param;
            
            // Domains, senders are both sender batch actions
	        switch($type) {
	            default:
	            case 'sender':
	                $doType = 'sender';
	                break;
	                
	            case 'subject':
	                $doType = 'subject';
	                break;
	                
	            case 'header':
	                $doType = 'header';
	                break;
	        }

            // Make wildcards
            $doData = array();
            if($type=="domain") {
                $doData = array('*'.$val);
            } else {
                $doData = array($val);
            }
            
            $view->doBulkUpdate($doType, $doTypeParam, $doData, $doActions, array());
	    }

	    $view->renderPage = 0; // Reset the paging since we may have reduced our list size
	    $view->removeParam('tmpMemberships'); // Remove our filter
	    C4_AbstractViewLoader::setView($view_id,$view);
	    	    
        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets')));
	}

	function viewMoveTicketsAction() {
	    @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
	    @$ticket_ids = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'array');
	    @$move_to = DevblocksPlatform::importGPC($_REQUEST['move_to'],'string');
	    
	    if(empty($ticket_ids)) {
		    $view = C4_AbstractViewLoader::getView($view_id);
		    $view->render();
		    return;
	    }
	    
        $visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
	    
	    list($team_id,$category_id) = CerberusApplication::translateTeamCategoryCode($move_to);

        $fields = array(
            DAO_Ticket::TEAM_ID => $team_id,
            DAO_Ticket::CATEGORY_ID => $category_id,
        );
	    
        //====================================
	    // Undo functionality
        $orig_tickets = DAO_Ticket::getTickets($ticket_ids);
        
        $last_action = new Model_TicketViewLastAction();
        $last_action->action = Model_TicketViewLastAction::ACTION_MOVE;
        $last_action->action_params = $fields;

        if(is_array($orig_tickets))
        foreach($orig_tickets as $orig_ticket_idx => $orig_ticket) { /* @var $orig_ticket Model_Ticket */
            $last_action->ticket_ids[$orig_ticket_idx] = array(
                DAO_Ticket::TEAM_ID => $orig_ticket->team_id,
                DAO_Ticket::CATEGORY_ID => $orig_ticket->category_id
            );
            $orig_ticket->team_id = $team_id;
            $orig_ticket->category_id = $category_id;
            $orig_tickets[$orig_ticket_idx] = $orig_ticket;
        }
        
        View_Ticket::setLastAction($view_id,$last_action);
	    
	    // Make our changes to the entire list of tickets
	    if(!empty($ticket_ids) && !empty($team_id)) {
	        DAO_Ticket::update($ticket_ids, $fields);
	    }
	    
	    $view = C4_AbstractViewLoader::getView($view_id);
	    $view->render();
	    return;
	}

	function viewMergeTicketsAction() {
	    @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
	    @$ticket_ids = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'array');

        View_Ticket::setLastAction($view_id,null);
        //====================================

	    if(!empty($ticket_ids)) {
	    	$oldest_id = DAO_Ticket::merge($ticket_ids);
	    }
	    
	    $view = C4_AbstractViewLoader::getView($view_id);
	    $view->render();
	    return;
	}
	
	function viewCloseTicketsAction() {
	    @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
	    @$ticket_ids = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'array');
	    
        $fields = array(
            DAO_Ticket::IS_CLOSED => CerberusTicketStatus::CLOSED,
        );
	    
        //====================================
	    // Undo functionality
        $last_action = new Model_TicketViewLastAction();
        $last_action->action = Model_TicketViewLastAction::ACTION_CLOSE;

        if(is_array($ticket_ids))
        foreach($ticket_ids as $ticket_id) {
            $last_action->ticket_ids[$ticket_id] = array(
                DAO_Ticket::IS_CLOSED => CerberusTicketStatus::OPEN
            );
        }

        $last_action->action_params = $fields;
        
        View_Ticket::setLastAction($view_id,$last_action);
        //====================================
	    
        DAO_Ticket::update($ticket_ids, $fields);
	    
	    $view = C4_AbstractViewLoader::getView($view_id);
	    $view->render();
	    return;
	}
	
	function viewWaitingTicketsAction() {
	    @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
	    @$ticket_ids = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'array');

        $fields = array(
            DAO_Ticket::IS_WAITING => 1,
        );
	    
        //====================================
	    // Undo functionality
        $last_action = new Model_TicketViewLastAction();
        $last_action->action = Model_TicketViewLastAction::ACTION_WAITING;

        if(is_array($ticket_ids))
        foreach($ticket_ids as $ticket_id) {
            $last_action->ticket_ids[$ticket_id] = array(
                DAO_Ticket::IS_WAITING => 0,
            );
        }

        $last_action->action_params = $fields;
        
        View_Ticket::setLastAction($view_id,$last_action);
        //====================================

        DAO_Ticket::update($ticket_ids, $fields);
	    
	    $view = C4_AbstractViewLoader::getView($view_id);
	    $view->render();
	    return;
	}
	
	function viewNotWaitingTicketsAction() {
	    @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
	    @$ticket_ids = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'array');

        $fields = array(
            DAO_Ticket::IS_WAITING => 0,
        );
	    
        //====================================
	    // Undo functionality
        $last_action = new Model_TicketViewLastAction();
        $last_action->action = Model_TicketViewLastAction::ACTION_NOT_WAITING;

        if(is_array($ticket_ids))
        foreach($ticket_ids as $ticket_id) {
            $last_action->ticket_ids[$ticket_id] = array(
                DAO_Ticket::IS_WAITING => 1,
            );
        }

        $last_action->action_params = $fields;
        
        View_Ticket::setLastAction($view_id,$last_action);
        //====================================

        DAO_Ticket::update($ticket_ids, $fields);
	    
	    $view = C4_AbstractViewLoader::getView($view_id);
	    $view->render();
	    return;
	}
	
	function viewNotSpamTicketsAction() {
	    @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
	    @$ticket_ids = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'array');

        $fields = array(
            DAO_Ticket::IS_CLOSED => 0,
            DAO_Ticket::IS_DELETED => 0,
        );
	    
        //====================================
	    // Undo functionality
        $last_action = new Model_TicketViewLastAction();
        $last_action->action = Model_TicketViewLastAction::ACTION_NOT_SPAM;

        if(is_array($ticket_ids))
        foreach($ticket_ids as $ticket_id) {
//            CerberusBayes::calculateTicketSpamProbability($ticket_id); // [TODO] Ugly (optimize -- use the 'interesting_words' to do a word bayes spam score?
            
            $last_action->ticket_ids[$ticket_id] = array(
                DAO_Ticket::SPAM_TRAINING => CerberusTicketSpamTraining::BLANK,
                DAO_Ticket::SPAM_SCORE => 0.0001, // [TODO] Fix
                DAO_Ticket::IS_CLOSED => 0,
                DAO_Ticket::IS_DELETED => 0
            );
        }

        $last_action->action_params = $fields;
        
        View_Ticket::setLastAction($view_id,$last_action);
        //====================================

        // [TODO] Bayes should really be smart enough to allow training of batches of IDs
	    if(!empty($ticket_ids))
	    foreach($ticket_ids as $id) {
	        CerberusBayes::markTicketAsNotSpam($id);
	    }
	    
        DAO_Ticket::update($ticket_ids, $fields);
	    
	    $view = C4_AbstractViewLoader::getView($view_id);
	    $view->render();
	    return;
	}
	
	function viewSpamTicketsAction() {
	    @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
	    @$ticket_ids = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'array');

        $fields = array(
            DAO_Ticket::IS_CLOSED => 1,
            DAO_Ticket::IS_DELETED => 1,
        );
	    
        //====================================
	    // Undo functionality
        $last_action = new Model_TicketViewLastAction();
        $last_action->action = Model_TicketViewLastAction::ACTION_SPAM;

        if(is_array($ticket_ids))
        foreach($ticket_ids as $ticket_id) {
//            CerberusBayes::calculateTicketSpamProbability($ticket_id); // [TODO] Ugly (optimize -- use the 'interesting_words' to do a word bayes spam score?
            
            $last_action->ticket_ids[$ticket_id] = array(
                DAO_Ticket::SPAM_TRAINING => CerberusTicketSpamTraining::BLANK,
                DAO_Ticket::SPAM_SCORE => 0.5000, // [TODO] Fix
                DAO_Ticket::IS_CLOSED => 0,
                DAO_Ticket::IS_DELETED => 0
            );
        }

        $last_action->action_params = $fields;
        
        View_Ticket::setLastAction($view_id,$last_action);
        //====================================
	    
        // {TODO] Batch
	    if(!empty($ticket_ids))
	    foreach($ticket_ids as $id) {
	        CerberusBayes::markTicketAsSpam($id);
	    }
	    
        DAO_Ticket::update($ticket_ids, $fields);
	    
	    $view = C4_AbstractViewLoader::getView($view_id);
	    $view->render();
	    return;
	}
	
	function viewDeleteTicketsAction() {
	    @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
	    @$ticket_ids = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'array');

        $fields = array(
            DAO_Ticket::IS_CLOSED => 1,
            DAO_Ticket::IS_DELETED => 1,
        );
	    
        //====================================
	    // Undo functionality
        $last_action = new Model_TicketViewLastAction();
        $last_action->action = Model_TicketViewLastAction::ACTION_DELETE;

        if(is_array($ticket_ids))
        foreach($ticket_ids as $ticket_id) {
            $last_action->ticket_ids[$ticket_id] = array(
                DAO_Ticket::IS_CLOSED => 0,
                DAO_Ticket::IS_DELETED => 0
            );
        }

        $last_action->action_params = $fields;
        
        View_Ticket::setLastAction($view_id,$last_action);
        //====================================
	    
        DAO_Ticket::update($ticket_ids, $fields);
	    
	    $view = C4_AbstractViewLoader::getView($view_id);
	    $view->render();
	    return;
	}
	
	function viewUndoAction() {
	    @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
	    @$clear = DevblocksPlatform::importGPC($_REQUEST['clear'],'integer',0);
	    $last_action = View_Ticket::getLastAction($view_id);
	    
	    if($clear || empty($last_action)) {
            View_Ticket::setLastAction($view_id,null);
		    $view = C4_AbstractViewLoader::getView($view_id);
		    $view->render();
	        return;
	    }
	    
	    /*
	     * [TODO] This could be optimized by only doing the row-level updates for the 
	     * MOVE action, all the rest can just be a single DAO_Ticket::update($ids, ...)
	     */
	    if(is_array($last_action->ticket_ids) && !empty($last_action->ticket_ids))
	    foreach($last_action->ticket_ids as $ticket_id => $fields) {
	        DAO_Ticket::update($ticket_id, $fields);
	    }
	    
	    $visit = CerberusApplication::getVisit();
	    $visit->set(CerberusVisit::KEY_VIEW_LAST_ACTION,null);
	    
	    $view = C4_AbstractViewLoader::getView($view_id);
	    $view->render();
	    return;
	}

	function showBatchPanelAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);

	    $unique_sender_ids = array();
	    $unique_subjects = array();
	    
	    if(!empty($ids)) {
	        $ticket_ids = DevblocksPlatform::parseCsvString($ids);
	        
	        if(empty($ticket_ids))
	        	break;
	        
	        $tickets = DAO_Ticket::getTickets($ticket_ids);
	        if(is_array($tickets))
		    foreach($tickets as $ticket) { /* @var $ticket Model_Ticket */
	            $ptr =& $unique_sender_ids[$ticket->first_wrote_address_id]; 
		        $ptr = intval($ptr) + 1;
		        $ptr =& $unique_subjects[$ticket->subject];
		        $ptr = intval($ptr) + 1;
		    }
	
		    arsort($unique_subjects); // sort by occurrences
		    
		    $senders = DAO_Address::getWhere(
		    	sprintf("%s IN (%s)",
		    		DAO_Address::ID,
		    		implode(',',array_keys($unique_sender_ids))
		    ));
		    
		    foreach($senders as $sender) {
		        $ptr =& $unique_senders[$sender->email];
		        $ptr = intval($ptr) + 1;
		    }
		    
		    arsort($unique_senders);
		    
		    unset($senders);
		    unset($unique_sender_ids);
		    
	        @$tpl->assign('ticket_ids', $ticket_ids);
	        @$tpl->assign('unique_senders', $unique_senders);
	        @$tpl->assign('unique_subjects', $unique_subjects);
	    }
		
		// Teams
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		
		// Categories
		$team_categories = DAO_Bucket::getTeams(); // [TODO] Cache these
		$tpl->assign('team_categories', $team_categories);
		
		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
		
		// Custom Fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TICKET);
		$tpl->assign('custom_fields', $custom_fields);
		
		// Macros
		$macros = DAO_TriggerEvent::getByOwner(CerberusContexts::CONTEXT_WORKER, $active_worker->id, 'event.macro.ticket');
		$tpl->assign('macros', $macros);
		
		// Broadcast
		CerberusContexts::getContext(CerberusContexts::CONTEXT_TICKET, null, $token_labels, $token_values);
		$tpl->assign('token_labels', $token_labels);
		
		$tpl->display('devblocks:cerberusweb.core::tickets/rpc/batch_panel.tpl');
	}
	
	// Ajax
	function doBatchUpdateAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$ticket_id_str = DevblocksPlatform::importGPC($_REQUEST['ticket_ids'],'string');
	    @$shortcut_name = DevblocksPlatform::importGPC($_REQUEST['shortcut_name'],'string','');

	    @$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
	    @$senders = DevblocksPlatform::importGPC($_REQUEST['senders'],'string','');
	    @$subjects = DevblocksPlatform::importGPC($_REQUEST['subjects'],'string','');
	    
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);

        $subjects = DevblocksPlatform::parseCrlfString($subjects);
        $senders = DevblocksPlatform::parseCrlfString($senders);
		
		// Scheduled behavior
		@$behavior_id = DevblocksPlatform::importGPC($_POST['behavior_id'],'string','');
		@$behavior_when = DevblocksPlatform::importGPC($_POST['behavior_when'],'string','');
        
		$do = array();
		
		// Move to Group/Bucket
		// [TODO] This logic is repeated in several places -- try to condense (like custom field form handlers)
		@$move_code = DevblocksPlatform::importGPC($_REQUEST['do_move'],'string',null);
		if(0 != strlen($move_code)) {
			list($g_id, $b_id) = CerberusApplication::translateTeamCategoryCode($move_code);
			$do['move'] = array(
				'group_id' => intval($g_id),
				'bucket_id' => intval($b_id),
			);
		}
		
		// Watchers
		$watcher_params = array();
		
		@$watcher_add_ids = DevblocksPlatform::importGPC($_REQUEST['do_watcher_add_ids'],'array',array());
		if(!empty($watcher_add_ids))
			$watcher_params['add'] = $watcher_add_ids;
			
		@$watcher_remove_ids = DevblocksPlatform::importGPC($_REQUEST['do_watcher_remove_ids'],'array',array());
		if(!empty($watcher_remove_ids))
			$watcher_params['remove'] = $watcher_remove_ids;
		
		if(!empty($watcher_params))
			$do['watchers'] = $watcher_params;
			
		// Spam training
		@$is_spam = DevblocksPlatform::importGPC($_REQUEST['do_spam'],'string',null);
		if(0 != strlen($is_spam)) {
			$do['spam'] = array(
				'is_spam' => (!$is_spam?0:1)
			);
		}
		
		// Owner
		@$owner_id = DevblocksPlatform::importGPC($_REQUEST['do_owner'],'string',null);
		if(0 != strlen($owner_id)) {
			$do['owner'] = array(
				'worker_id' => intval($owner_id),
			);
		}
		
		// Set status
		@$status = DevblocksPlatform::importGPC($_REQUEST['do_status'],'string',null);
		if(0 != strlen($status)) {
			$do['status'] = array(
				'is_waiting' => (3==$status?1:0), // explicit waiting
				'is_closed' => ((0==$status||3==$status)?0:1), // not open or waiting
				'is_deleted' => (2==$status?1:0), // explicit deleted
			);
			
			// Waiting until
			$reopen = '';
			switch($status) {
				case 1: // closed
				case 3: // waiting
					@$reopen = DevblocksPlatform::importGPC($_REQUEST['do_reopen'],'string',null);
					break;
			}
			
			$do['reopen'] = array(
				'date' => $reopen,
			);
		}
		
		// Do: Scheduled Behavior
		if(0 != strlen($behavior_id)) {
			$do['behavior'] = array(
				'id' => $behavior_id,
				'when' => $behavior_when,
			);
		}
		
		// Broadcast: Mass Reply
		if($active_worker->hasPriv('core.ticket.view.actions.broadcast_reply')) {
			@$do_broadcast = DevblocksPlatform::importGPC($_REQUEST['do_broadcast'],'string',null);
			@$broadcast_message = DevblocksPlatform::importGPC($_REQUEST['broadcast_message'],'string',null);
			@$broadcast_is_queued = DevblocksPlatform::importGPC($_REQUEST['broadcast_is_queued'],'integer',0);
			if(0 != strlen($do_broadcast) && !empty($broadcast_message)) {
				$do['broadcast'] = array(
					'message' => $broadcast_message,
					'is_queued' => $broadcast_is_queued,
					'worker_id' => $active_worker->id,
				);
			}
		}
		
	    $data = array();
	    $ids = array();
	    
	    switch($filter) {
	    	case 'sender':
		        $data = $senders;
	    		break;
	    	case 'subject':
		        $data = $subjects;
	    		break;
	    	case 'checks':
		    	$filter = ''; // bulk update just looks for $ids == !null
		        $ids = DevblocksPlatform::parseCsvString($ticket_id_str);
	    		break;
			case 'sample':
				@$sample_size = min(DevblocksPlatform::importGPC($_REQUEST['filter_sample_size'],'integer',0),9999);
				$filter = '';
				$ids = $view->getDataSample($sample_size);
				break;
			default:
				break;
	    }
		
	    // Restrict to current worker groups
		$memberships = $active_worker->getMemberships();
		$view->addParam(new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_TEAM_ID, 'in', array_keys($memberships)), 'tmp'); 
	    
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);
		
		$view->doBulkUpdate($filter, '', $data, $do, $ids);
		
		// Clear our temporary group restriction before re-rendering
		$view->removeParam('tmp');
		
		$view->render();
		return;
	}

	function doBatchUpdateBroadcastTestAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$view = C4_AbstractViewLoader::getView($view_id);

		$tpl = DevblocksPlatform::getTemplateService();
		
		if($active_worker->hasPriv('core.ticket.view.actions.broadcast_reply')) {
			@$broadcast_message = DevblocksPlatform::importGPC($_REQUEST['broadcast_message'],'string',null);

			@$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
			@$ids = DevblocksPlatform::importGPC($_REQUEST['ids'],'string','');
			
			// Filter to checked
			if('checks' == $filter && !empty($ids)) {
				$view->addParam(new DevblocksSearchCriteria(SearchFields_Ticket::ID,'in',explode(',', $ids)));
			}
			
			$results = $view->getDataSample(1);
			
			if(empty($results)) {
				$success = false;
				$output = "There aren't any rows in this view!";
				
			} else {
				// Try to build the template
				CerberusContexts::getContext(CerberusContexts::CONTEXT_TICKET, current($results), $token_labels, $token_values);
				if(false === ($out = $tpl_builder->build($broadcast_message, $token_values))) {
					// If we failed, show the compile errors
					$errors = $tpl_builder->getErrors();
					$success= false;
					$output = @array_shift($errors);
				} else {
					// If successful, return the parsed template
					$success = true;
					$output = $out;
				}
			}
			
			$tpl->assign('success', $success);
			$tpl->assign('output', $output);
			$tpl->display('devblocks:cerberusweb.core::internal/renderers/test_results.tpl');
		}
	}
	
	// ajax
	function showViewRssAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$source = DevblocksPlatform::importGPC($_REQUEST['source'],'string','');
		
		$view = C4_AbstractViewLoader::getView($view_id);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		$tpl->assign('view', $view);
		$tpl->assign('source', $source);
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/view_rss_builder.tpl');
	}
	
	// post
	// [TODO] Move to 'internal'
	function viewBuildRssAction() {
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id']);
		@$source = DevblocksPlatform::importGPC($_POST['source']);
		@$title = DevblocksPlatform::importGPC($_POST['title']);
		$active_worker = CerberusApplication::getActiveWorker();

		$view = C4_AbstractViewLoader::getView($view_id);
		
		$hash = md5($title.$view_id.$active_worker->id.time());
		
	    // Restrict to current worker groups
		$active_worker = CerberusApplication::getActiveWorker();
		
		$params = array(
			'params' => $view->getParams(),
			'sort_by' => $view->renderSortBy,
			'sort_asc' => $view->renderSortAsc
		);
		
		$fields = array(
			DAO_ViewRss::TITLE => $title, 
			DAO_ViewRss::HASH => $hash, 
			DAO_ViewRss::CREATED => time(),
			DAO_ViewRss::WORKER_ID => $active_worker->id,
			DAO_ViewRss::SOURCE_EXTENSION => $source, 
			DAO_ViewRss::PARAMS => serialize($params),
		);
		$feed_id = DAO_ViewRss::create($fields);
				
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('preferences','rss')));
	}
	
	function searchviewAction() {
		$visit = CerberusApplication::getVisit();
	    
	    $response = DevblocksPlatform::getHttpRequest();
	    $path = $response->path;
	    array_shift($path); // tickets
	    array_shift($path); // searchview
	    $id = array_shift($path);

	    $view = C4_AbstractViewLoader::getView($id);
	    $view_params = $view->getParams();

		if(!empty($view_params)) {
		    $params = array();
		    
		    // Index by field name for search system
		    if(is_array($view_params))
		    foreach($view_params as $key => $criteria) { /* @var $criteria DevblocksSearchCriteria */
                $params[$key] = $criteria;
		    }
		}
		
		if(null == ($search_view = C4_AbstractViewLoader::getView(CerberusApplication::VIEW_SEARCH))) {
			$search_view = View_Ticket::createSearchView();
		}
		$search_view->addParams($params, true);
		$search_view->renderPage = 0;
		C4_AbstractViewLoader::setView($search_view->id,$search_view);

		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','search')));
	}
};
