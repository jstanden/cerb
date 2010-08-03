<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2010, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerberus Helpdesk Team
 * 
 * Sure, it would be so easy to just cheat and edit this file to use the 
 * software without paying for it.  But we trust you anyway.  In fact, we're 
 * writing this software for you! 
 * 
 * Quality software backed by a dedicated team takes money to develop.  We 
 * don't want to be out of the office bagging groceries when you call up 
 * needing a helping hand.  We'd rather spend our free time coding your 
 * feature requests than mowing the neighbors' lawns for rent money. 
 * 
 * We've never believed in hiding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your inbox that you probably 
 * haven't had since spammers found you in a game of 'E-mail Battleship'. 
 * Miss. Miss. You sunk my inbox!
 * 
 * A legitimate license entitles you to support from the developers,  
 * and the warm fuzzy feeling of feeding a couple of obsessed developers 
 * who want to help you get more done.
 *
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Joe Geck, Scott Luther,
 * 		and Jerry Kanoholani. 
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
class ChTicketsPage extends CerberusPageExtension {
	private $_TPL_PATH = '';
	
	function __construct($manifest) {
		$this->_TPL_PATH = dirname(dirname(dirname(__FILE__))) . '/templates/';
		parent::__construct($manifest);
	}

	function isVisible() {
		// check login
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}

		return true;
	}
	
	function getActivity() {
		return new Model_Activity('activity.tickets',array(
	    	""
	    ));
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);

		$visit = CerberusApplication::getVisit();
		$active_worker = $visit->getWorker();
		
		$response = DevblocksPlatform::getHttpResponse();
		$tpl->assign('request_path', implode('/',$response->path));
		
		// Remember the last tab/URL
		if(null == ($selected_tab = @$response->path[1])) {
			$selected_tab = $visit->get(CerberusVisit::KEY_MAIL_MODE, '');
		}
		$tpl->assign('selected_tab', $selected_tab);
		
		// ====== Renders
		switch($selected_tab) {
			case 'compose':
				if(!$active_worker->hasPriv('core.mail.send'))
					break;
				
				$settings = DevblocksPlatform::getPluginSettingsService();
				
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
				
				$tpl->display('file:' . $this->_TPL_PATH . 'tickets/compose/index.tpl');
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
				$destinations = CerberusApplication::getHelpdeskSenders();
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
				
				$tpl->display('file:' . $this->_TPL_PATH . 'tickets/create/index.tpl');
				break;
				
			default:
				// Clear all undo actions on reload
			    View_Ticket::clearLastActions();
			    				
				$quick_search_type = $visit->get('quick_search_type');
				$tpl->assign('quick_search_type', $quick_search_type);

				$tpl->display('file:' . $this->_TPL_PATH . 'tickets/index.tpl');
				break;
		}
		
	}
	
	function showWorkflowTabAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		$db = DevblocksPlatform::getDatabaseService();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		
		// Remember the tab
		$visit->set(CerberusVisit::KEY_MAIL_MODE, 'workflow');
		
		// Request path
		@$request = DevblocksPlatform::importGPC($_REQUEST['request'],'string','');
		$response_path = explode('/', $request);
		@array_shift($response_path); // tickets
		@$controller = array_shift($response_path); // workflow
		
		// Make sure the global URL was for us
		if(0!=strcasecmp('workflow',$controller))
			$response_path = null;

		$active_worker = CerberusApplication::getActiveWorker();
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$group_buckets = DAO_Bucket::getTeams();
		$tpl->assign('group_buckets', $group_buckets);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		// View
		$title = $translate->_('mail.overview.all_groups');

		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_Ticket';
		$defaults->id = CerberusApplication::VIEW_MAIL_WORKFLOW;
		$defaults->name = $title;
		$defaults->view_columns = array(
			SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
			SearchFields_Ticket::TICKET_UPDATED_DATE,
			SearchFields_Ticket::TICKET_TEAM_ID,
			SearchFields_Ticket::TICKET_CATEGORY_ID,
		);
		$defaults->renderLimit = 10;
		$defaults->renderSortBy = SearchFields_Ticket::TICKET_UPDATED_DATE;
		$defaults->renderSortAsc = 0;
		
		$workflowView = C4_AbstractViewLoader::getView(CerberusApplication::VIEW_MAIL_WORKFLOW, $defaults);
		
		$workflowView->paramsRequired = array(
			SearchFields_Ticket::TICKET_CLOSED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',CerberusTicketStatus::OPEN),
			SearchFields_Ticket::TICKET_WAITING => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_WAITING,'=',0),
			SearchFields_Ticket::VIRTUAL_ASSIGNABLE => new DevblocksSearchCriteria(SearchFields_Ticket::VIRTUAL_ASSIGNABLE,null,true),
			SearchFields_Ticket::VIRTUAL_WORKERS => new DevblocksSearchCriteria(SearchFields_Ticket::VIRTUAL_WORKERS,null,array()),
		);
		$workflowView->paramsHidden = array(
			SearchFields_Ticket::TICKET_CLOSED,
			SearchFields_Ticket::TICKET_WAITING,
			SearchFields_Ticket::VIRTUAL_ASSIGNABLE,
			SearchFields_Ticket::VIRTUAL_WORKERS,
		);
		
		$workflowView->renderPage = 0;
		
		// Filter persistence
		if(empty($response_path)) {
			@$response_path = explode('/',$visit->get(CerberusVisit::KEY_WORKFLOW_FILTER, 'all'));
		} else {
			// View Filter
			$visit->set(CerberusVisit::KEY_WORKFLOW_FILTER, implode('/',$response_path));
		}
		
		@$filter = array_shift($response_path);
		
		switch($filter) {
			case 'group':
				@$filter_group_id = array_shift($response_path);
				
				if(!is_null($filter_group_id) && isset($groups[$filter_group_id])) {
					$tpl->assign('filter_group_id', $filter_group_id);
					$title = $groups[$filter_group_id]->name;
					$workflowView->addParam(new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_TEAM_ID,'=',$filter_group_id));
					
					@$filter_bucket_id = array_shift($response_path);
					if(!is_null($filter_bucket_id)) {
						$tpl->assign('filter_bucket_id', $filter_bucket_id);
						@$title .= ': '.
							(($filter_bucket_id == 0) ? $translate->_('common.inbox') : $group_buckets[$filter_group_id][$filter_bucket_id]->name);
						$workflowView->addParam(new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CATEGORY_ID,'=',$filter_bucket_id));
					}
				}

				break;
				
			case 'all':
			default:
				$workflowView->removeParam(SearchFields_Ticket::TICKET_TEAM_ID);
				$workflowView->removeParam(SearchFields_Ticket::TICKET_CATEGORY_ID);
				break;
		}
		
		$workflowView->name = $title;
		C4_AbstractViewLoader::setView($workflowView->id, $workflowView);
		
		$tpl->assign('view', $workflowView);
		
		// Totals (only drill down as deep as a group)
		$original_params = $workflowView->getEditableParams();
		$workflowView->removeParam(SearchFields_Ticket::TICKET_CATEGORY_ID);
		$counts = $workflowView->getCounts('group');
		$workflowView->addParams($original_params, true);
		$tpl->assign('counts', $counts);
		
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
		
        $tpl->display('file:' . $this->_TPL_PATH . 'tickets/workflow/index.tpl');
	}
	
	function showMessagesTabAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		$visit = CerberusApplication::getVisit();
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Log activity
//		DAO_Worker::logActivity(
//			new Model_Activity(
//				'activity.mail.search'
//			)
//		);
		
		// Remember the tab
		$visit->set(CerberusVisit::KEY_MAIL_MODE, 'messages');		
		
		$defaults = new C4_AbstractViewModel();
		$defaults->id = CerberusApplication::VIEW_MAIL_MESSAGES;
		$defaults->class_name = 'View_Message';
//		$defaults->view_columns = array(
//		);
		$defaults->paramsDefault = array(
			new DevblocksSearchCriteria(SearchFields_Message::IS_OUTGOING,'=',1),
			new DevblocksSearchCriteria(SearchFields_Message::CREATED_DATE,DevblocksSearchCriteria::OPER_BETWEEN,array('-30 days', 'now')),
		);
		$defaults->paramsRequired = array(
			new DevblocksSearchCriteria(SearchFields_Message::TICKET_GROUP_ID,'in',array_keys($active_worker->getMemberships())),
		);
		$defaults->paramsEditable = $defaults->paramsDefault;
		$defaults->renderSortBy = SearchFields_Message::CREATED_DATE;
		$defaults->renderSortAsc = false;
		
		$view = C4_AbstractViewLoader::getView($defaults->id, $defaults);
		
		C4_AbstractViewLoader::setView($view->id,$view);
		
		$tpl->assign('view', $view);
	
		$tpl->display('file:' . $this->_TPL_PATH . 'tickets/messages/index.tpl');
	}
	
	function showSearchTabAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		$visit = CerberusApplication::getVisit();
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Log activity
		DAO_Worker::logActivity(
			new Model_Activity(
				'activity.mail.search'
			)
		);
		
		// Remember the tab
		$visit->set(CerberusVisit::KEY_MAIL_MODE, 'search');		
		
		$view = C4_AbstractViewLoader::getView(CerberusApplication::VIEW_SEARCH);
		
		// [TODO] Convert to defaults
		
		if(null == $view) {
			$view = View_Ticket::createSearchView();
			C4_AbstractViewLoader::setView($view->id,$view);
		}
		
		$tpl->assign('view', $view);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'tickets/search/index.tpl');
	}
	
	function viewSidebarAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['id'],'string','');
		@$field = DevblocksPlatform::importGPC($_REQUEST['field'],'string');

		if(empty($field))
			$field = 'group';
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);
		$tpl->assign('field', $field);
		
		if(null != ($view = C4_AbstractViewLoader::getView($view_id))) {
			$counts = $view->getCounts($field);
			$tpl->assign('counts', $counts);
		}
		
		$tpl->display('devblocks:cerberusweb.core::tickets/view_sidebar.tpl');
	}
	
	function showDraftsTabAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);

		// Remember the tab
		$visit->set(CerberusVisit::KEY_MAIL_MODE, 'drafts');
		
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_MailQueue';
		$defaults->id = 'mail_drafts';
		
		$view = C4_AbstractViewLoader::getView($defaults->id, $defaults);
		$view->name = 'Drafts';
		
		$view->columnsHidden = array(
			SearchFields_MailQueue::ID,
			SearchFields_MailQueue::IS_QUEUED,
			SearchFields_MailQueue::QUEUE_FAILS,
			SearchFields_MailQueue::QUEUE_PRIORITY,
			SearchFields_MailQueue::TICKET_ID,
		);
		
		$view->paramsRequired = array(
			SearchFields_MailQueue::WORKER_ID => new DevblocksSearchCriteria(SearchFields_MailQueue::WORKER_ID, DevblocksSearchCriteria::OPER_EQ, $active_worker->id),
			SearchFields_MailQueue::IS_QUEUED => new DevblocksSearchCriteria(SearchFields_MailQueue::IS_QUEUED, DevblocksSearchCriteria::OPER_EQ, 0),
		);
		$view->paramsHidden = array(
			SearchFields_MailQueue::ID,
			SearchFields_MailQueue::IS_QUEUED,
			SearchFields_MailQueue::QUEUE_FAILS,
			SearchFields_MailQueue::QUEUE_PRIORITY,
			SearchFields_MailQueue::TICKET_ID,
		);
		
		C4_AbstractViewLoader::setView($view->id,$view);
		$tpl->assign('view', $view);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'mail/queue/index.tpl');
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
			DAO_MailQueue::QUEUE_PRIORITY => 0,
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
		$html = $tpl->fetch('file:' . $this->_TPL_PATH . 'mail/queue/saved.tpl');
		
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
		$path = $this->_TPL_PATH;
		$tpl->assign('path', $path);
		$tpl->assign('view_id', $view_id);
		
		if(null != ($draft = DAO_MailQueue::get($id)))
			if($active_worker->is_superuser || $draft->worker_id==$active_worker->id)
				$tpl->assign('draft', $draft);
		
		$tpl->display('file:' . $path . 'mail/queue/peek.tpl');
	}
	
	function showDraftsBulkPanelAction() {
		@$id_csv = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$path = $this->_TPL_PATH;
		$tpl->assign('path', $path);
		$tpl->assign('view_id', $view_id);

	    if(!empty($id_csv)) {
	        $ids = DevblocksPlatform::parseCsvString($id_csv);
	        $tpl->assign('ids', implode(',', $ids));
	    }
		
	    // Lists
//	    $lists = DAO_FeedbackList::getWhere();
//	    $tpl->assign('lists', $lists);
	    
		// Custom Fields
//		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_FeedbackEntry::ID);
//		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->display('file:' . $path . 'mail/queue/bulk.tpl');		
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
			default:
				break;
		}

		$view->doBulkUpdate($filter, $do, $ids);
		
		$view->render();
		return;		
	}
	
	function showSnippetsTabAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);

		// Remember the tab
		$visit->set(CerberusVisit::KEY_MAIL_MODE, 'snippets');
		
		// [TODO] Use $defaults
		$view = C4_AbstractViewLoader::getView('mail_snippets');
		
		if(null == $view) {
			$view = new View_Snippet();
			$view->id = 'mail_snippets';
			$view->name = 'Mail Snippets';
		}
		
		$view->columnsHidden[] = SearchFields_Snippet::ID;
		$view->columnsHidden[] = SearchFields_Snippet::IS_PRIVATE;
		
		$view->paramsRequired = array(
			SearchFields_Snippet::CONTEXT => new DevblocksSearchCriteria(SearchFields_Snippet::CONTEXT, DevblocksSearchCriteria::OPER_IN, array('cerberusweb.contexts.plaintext','cerberusweb.contexts.ticket','cerberusweb.contexts.worker')),
		);
		$view->paramsHidden[] = SearchFields_Snippet::ID;
		$view->paramsHidden[] = SearchFields_Snippet::IS_PRIVATE;
		
		C4_AbstractViewLoader::setView($view->id,$view);
		$tpl->assign('view', $view);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'mail/snippets/index.tpl');
	}	
	
	function showSnippetsPeekAction() {
		@$snippet_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		$tpl->assign('view_id', $view_id);
		
		if(null == ($snippet = DAO_Snippet::get($snippet_id))) {
			$snippet = new Model_Snippet();
			$snippet->id = 0;
			$snippet->context = !empty($context) ? $context : 'cerberusweb.contexts.plaintext';
		}
		$tpl->assign('snippet', $snippet);
		
		switch($snippet->context) {
			case 'cerberusweb.contexts.plaintext':
				break;
			case 'cerberusweb.contexts.ticket':
				CerberusContexts::getContext(CerberusContexts::CONTEXT_TICKET, null, $token_labels, $token_values);
				$tpl->assign('token_labels', $token_labels);
				break;
			case 'cerberusweb.contexts.worker':
				CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, null, $token_labels, $token_values);
				$tpl->assign('token_labels', $token_labels);
				break;
		}
			
		$tpl->display('file:' . $this->_TPL_PATH . 'mail/snippets/peek.tpl');
	}
	
	function saveSnippetsPeekAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string','');
		@$context = DevblocksPlatform::importGPC($_REQUEST['context'],'string','');
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);

		$active_worker = CerberusApplication::getActiveWorker();

		$fields = array(
			DAO_Snippet::TITLE => $title,
			DAO_Snippet::CONTENT => $content,
			DAO_Snippet::LAST_UPDATED => time(),
			DAO_Snippet::LAST_UPDATED_BY => $active_worker->id,
		);

		if(empty($id)) {
			$fields[DAO_Snippet::CREATED_BY] = $active_worker->id;
			$fields[DAO_Snippet::CONTEXT] = $context;
			$fields[DAO_Snippet::IS_PRIVATE] = 0;
			
			$id = DAO_Snippet::create($fields);
			
		} else {
			// Make sure we have permission
			if($active_worker->is_superuser || null != DAO_Snippet::getWhere(sprintf("%s = %d AND %s = %d",
				DAO_Snippet::ID,
				$id,
				DAO_Snippet::CREATED_BY,
				$active_worker->id
			))) {
				if($do_delete) {
					DAO_Snippet::delete($id);
				} else {
					DAO_Snippet::update($id, $fields);
				}
			}
		}
		
		if(null !== ($view = C4_AbstractViewLoader::getView($view_id))) {
			$view->render();
		}
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
		$view->renderLimit = 25;
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->write('c=tickets&tab=drafts', true),
				);
				$models[] = $model; 
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $draft_id => $row) {
				if($draft_id==$explore_from)
					$orig_pos = $pos;
				
				if($row[SearchFields_MailQueue::TYPE]==Model_MailQueue::TYPE_COMPOSE) {
					$url = $url_writer->write(sprintf("c=tickets&a=compose&id=%d", $draft_id), true);
				} elseif($row[SearchFields_MailQueue::TYPE]==Model_MailQueue::TYPE_TICKET_REPLY) {
					$url = $url_writer->write(sprintf("c=display&id=%d", $row[SearchFields_MailQueue::TICKET_ID]), true) . sprintf("#draft%d", $draft_id);
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
		$view->renderLimit = 25;
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->write('c=tickets', true),
				);
				$models[] = $model; 
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $ticket_id => $row) {
				if($ticket_id==$explore_from)
					$orig_pos = $pos;
				
				$url = $url_writer->write(sprintf("c=display&mask=%s", $row[SearchFields_Ticket::TICKET_MASK]), true);

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
	
	// Ajax
	// [TODO] Merge w/ the other sidebar method
	function refreshSidebarAction() {
		$db = DevblocksPlatform::getDatabaseService();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);

		$visit = CerberusApplication::getVisit();
		$active_worker = CerberusApplication::getActiveWorker();

		$section = $visit->get(CerberusVisit::KEY_MAIL_MODE, '');
		
		switch($section) {
			case 'workflow':
				// Since we don't re-save the view, we can remove filters that we don't want to restrict the count
				$view = C4_AbstractViewLoader::getView(CerberusApplication::VIEW_MAIL_WORKFLOW);
				$view->removeParam(SearchFields_Ticket::TICKET_CATEGORY_ID);
				$counts = $view->getCounts('group');
				$tpl->assign('counts', $counts);
				
				$tpl->display('file:' . $this->_TPL_PATH . 'tickets/workflow/sidebar.tpl');
				break;
		}
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
		$path = $this->_TPL_PATH;
		$tpl->assign('path', $path);

	    $visit = CerberusApplication::getVisit();
		$view = C4_AbstractViewLoader::getView($view_id);
		$tpl->assign('view', $view);
		
		if(!empty($last_action) && !is_null($last_action->ticket_ids)) {
			$tpl->assign('last_action_count', count($last_action->ticket_ids));
		}
		
		$tpl->assign('last_action', $last_action);
		$tpl->display($path.'tickets/rpc/ticket_view_output.tpl');
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
			            $query = '*' . $query . '*';
            		$params[SearchFields_Ticket::TICKET_MASK] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MASK,DevblocksSearchCriteria::OPER_LIKE,strtoupper($query));
            	}
                break;
                
            case "sender":
		        if($query && false===strpos($query,'*'))
		            $query = '*' . $query . '*';
                $params[SearchFields_Ticket::TICKET_FIRST_WROTE] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE,DevblocksSearchCriteria::OPER_LIKE,strtolower($query));               
                break;
                
            case "requester":
		        if($query && false===strpos($query,'*'))
		            $query = '*' . $query . '*';
                $params[SearchFields_Ticket::REQUESTER_ADDRESS] = new DevblocksSearchCriteria(SearchFields_Ticket::REQUESTER_ADDRESS,DevblocksSearchCriteria::OPER_LIKE,strtolower($query));               
                break;
                
            case "subject":
		        if($query && false===strpos($query,'*'))
		            $query = '*' . $query . '*';
            	$params[SearchFields_Ticket::TICKET_SUBJECT] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_SUBJECT,DevblocksSearchCriteria::OPER_LIKE,$query);               
                break;
                
            case "org":
		        if($query && false===strpos($query,'*'))
		            $query = '*' . $query . '*';
            	$params[SearchFields_Ticket::ORG_NAME] = new DevblocksSearchCriteria(SearchFields_Ticket::ORG_NAME,DevblocksSearchCriteria::OPER_LIKE,$query);               
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
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = $this->_TPL_PATH;
		$tpl->assign('path', $tpl_path);
		
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
		
		$closed = intval($visit->get('compose.defaults.closed', ''));
		$tpl->assign('default_closed', $closed);
		
		$context_worker_ids = $visit->get('compose.defaults.context_worker_ids', '');
		if(is_array($context_worker_ids) && !empty($context_worker_ids)) {
			$context_workers = DAO_Worker::getList($context_worker_ids);
			$tpl->assign('context_workers', $context_workers);
		}
		
		$tpl->display('file:' . $this->_TPL_PATH . 'tickets/compose/peek.tpl');
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
		@$context_worker_ids = DevblocksPlatform::importGPC($_POST['worker_id'],'array',array());

		$visit = CerberusApplication::getVisit();

		// Save Defaults
		$visit->set('compose.defaults.from', $team_id);
		$visit->set('compose.defaults.subject', $subject);
		$visit->set('compose.defaults.closed', $closed);
		$visit->set('compose.defaults.context_worker_ids', $context_worker_ids);
		
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
			'context_workers' => $context_worker_ids,
		);
		
		$ticket_id = CerberusMail::compose($properties);

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
		
		$settings = DevblocksPlatform::getPluginSettingsService();
		$group = DAO_Group::getTeam($group_id);

		$active_worker = CerberusApplication::getActiveWorker();
		$sig = $settings->get('cerberusweb.core',CerberusSettings::DEFAULT_SIGNATURE,CerberusSettingsDefaults::DEFAULT_SIGNATURE);

		if(!empty($group->signature)) {
			$sig = $group->signature;
		}

		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $active_worker, $token_labels, $token_values);
		echo "\r\n", $tpl_builder->build($sig, $token_values), "\r\n";
	}
	
	function getLogTicketSignatureAction() {
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$group_settings = DAO_GroupSettings::getSettings();
		
		$group_id = 0;
		
		// Translate email to group id
		if(is_array($group_settings))
		foreach($group_settings as $settings_group_id => $settings) {
			if(0==strcasecmp($settings[DAO_GroupSettings::SETTING_REPLY_FROM], $email)) {
				$group_id = $settings_group_id;
				break;
			}
		}
		
		if(!empty($group_id) && null != ($group = DAO_Group::getTeam($group_id)) && !empty($group->signature)) {
			$sig = $group->signature;
		} else {
			$sig = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::DEFAULT_SIGNATURE, CerberusSettingsDefaults::DEFAULT_SIGNATURE);
		}

		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $active_worker, $token_labels, $token_values);
		echo "\r\n", $tpl_builder->build($sig, $token_values), "\r\n";
	}
	
	// Ajax
	function showPreviewAction() {
	    @$tid = DevblocksPlatform::importGPC($_REQUEST['tid'],'integer',0);
	    @$msgid = DevblocksPlatform::importGPC($_REQUEST['msgid'],'integer',0);
	    @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
	    @$edit_mode = DevblocksPlatform::importGPC($_REQUEST['edit'],'integer',0);
	    
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = $this->_TPL_PATH;
		$tpl->assign('path', $tpl_path);

		$tpl->assign('view_id', $view_id);
		$tpl->assign('edit_mode', $edit_mode);
		
		if(null != ($ticket = DAO_Ticket::get($tid))) {
			/* @var $ticket Model_Ticket */
		    $tpl->assign('ticket', $ticket);
		}
		
		// Do we have a specific message to look at?
		if(!empty($msgid) && null != ($message = DAO_Message::get($msgid)) && $message->ticket_id == $tid) {
			 // Good
		} else {
			$message = $ticket->getLastMessage();
		}

		if(!empty($message)) {
			$tpl->assign('message', $message);
			$tpl->assign('content', $message->getContent());
		}
		
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		
		$team_categories = DAO_Bucket::getTeams();
		$tpl->assign('team_categories', $team_categories);
	    
		// Workers
		$context_workers = CerberusContexts::getWorkers(CerberusContexts::CONTEXT_TICKET, $ticket->id);
		$tpl->assign('context_workers', $context_workers);
		
		// Custom fields
		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Ticket::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		$custom_field_values = DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Ticket::ID, $ticket->id);
		if(isset($custom_field_values[$ticket->id]))
			$tpl->assign('custom_field_values', $custom_field_values[$ticket->id]);
		
		// Display
		$tpl->display('file:' . $this->_TPL_PATH . 'tickets/rpc/preview_panel.tpl');
	}
	
	// Ajax
	function savePreviewAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$subject = DevblocksPlatform::importGPC($_REQUEST['subject'],'string','');
		@$closed = DevblocksPlatform::importGPC($_REQUEST['closed'],'integer',0);
		@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
		@$bucket = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'string','');
		@$spam_training = DevblocksPlatform::importGPC($_REQUEST['spam_training'],'string','');
		
		$fields = array(
			DAO_Ticket::SUBJECT => $subject,
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
		
		// Context Workers
		CerberusContexts::setWorkers(CerberusContexts::CONTEXT_TICKET, $id, $worker_ids);
		
		// Custom field saves
		@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
		DAO_CustomFieldValue::handleFormPost(ChCustomFieldSource_Ticket::ID, $id, $field_ids);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_Ticket';
		$defaults->id = $view_id;
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->render();
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
		@$owner_ids = DevblocksPlatform::importGPC($_POST['worker_id'],'array',array());
		@$ticket_reopen = DevblocksPlatform::importGPC($_POST['ticket_reopen'],'string','');
		
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
			'context_workers' => $owner_ids,
			'ticket_reopen' => $ticket_reopen,
		);
		
		$ticket_id = CerberusMail::compose($properties);
		
		if(!empty($ticket_id)) {
			if(!empty($draft_id))
				DAO_MailQueue::delete($draft_id);
				
			$ticket = DAO_Ticket::get($ticket_id);
			
			$visit = CerberusApplication::getVisit(); /* @var CerberusVisit $visit */
			$visit->set('compose.last_ticket', $ticket->mask);
		}

		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','compose')));
	}
	
	function logTicketAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->hasPriv('core.mail.log_ticket'))
			return;
		
		@$to = DevblocksPlatform::importGPC($_POST['to'],'string');
		@$reqs = DevblocksPlatform::importGPC($_POST['reqs'],'string');
		@$subject = DevblocksPlatform::importGPC($_POST['subject'],'string');
		@$content = DevblocksPlatform::importGPC($_POST['content'],'string');
		
		@$draft_id = DevblocksPlatform::importGPC($_POST['draft_id'],'integer');		
		
		@$send_to_requesters = DevblocksPlatform::importGPC($_POST['send_to_requesters'],'integer',0);
		@$closed = DevblocksPlatform::importGPC($_POST['closed'],'integer',0);
		@$move_bucket = DevblocksPlatform::importGPC($_POST['bucket_id'],'string','');
		@$owner_ids = DevblocksPlatform::importGPC($_POST['worker_id'],'array',array());
		@$ticket_reopen = DevblocksPlatform::importGPC($_POST['ticket_reopen'],'string','');
		
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

		$message->body = sprintf(
			"(... This message was manually created by %s on behalf of the requesters ...)\r\n",
			$active_worker->getName()
		);

//		// Custom Fields
//		
//		if(!empty($aFieldIds))
//		foreach($aFieldIds as $iIdx => $iFieldId) {
//			if(!empty($iFieldId)) {
//				$field =& $fields[$iFieldId]; /* @var $field Model_CustomField */
//				$value = "";
//				
//				switch($field->type) {
//					case Model_CustomField::TYPE_SINGLE_LINE:
//					case Model_CustomField::TYPE_MULTI_LINE:
//					case Model_CustomField::TYPE_URL:
//						@$value = trim($aFollowUpA[$iIdx]);
//						break;
//					
//					case Model_CustomField::TYPE_NUMBER:
//						@$value = $aFollowUpA[$iIdx];
//						if(!is_numeric($value) || 0 == strlen($value))
//							$value = null;
//						break;
//						
//					case Model_CustomField::TYPE_DATE:
//						if(false !== ($time = strtotime($aFollowUpA[$iIdx])))
//							@$value = intval($time);
//						break;
//						
//					case Model_CustomField::TYPE_DROPDOWN:
//						@$value = $aFollowUpA[$iIdx];
//						break;
//						
//					case Model_CustomField::TYPE_MULTI_PICKLIST:
//						@$value = DevblocksPlatform::importGPC($_POST['followup_a_'.$iIdx],'array',array());
//						break;
//						
//					case Model_CustomField::TYPE_CHECKBOX:
//						@$value = (isset($aFollowUpA[$iIdx]) && !empty($aFollowUpA[$iIdx])) ? 1 : 0;
//						break;
//						
//					case Model_CustomField::TYPE_MULTI_CHECKBOX:
//						@$value = DevblocksPlatform::importGPC($_POST['followup_a_'.$iIdx],'array',array());
//						break;
//						
//					case Model_CustomField::TYPE_WORKER:
//						@$value = DevblocksPlatform::importGPC($_POST['followup_a_'.$iIdx],'integer',0);
//						break;
//				}
//				
//				if((is_array($value) && !empty($value)) 
//					|| (!is_array($value) && 0 != strlen($value)))
//						$message->custom_fields[$iFieldId] = $value;
//			}
//		}
		
		// Parse
		$ticket_id = CerberusParser::parseMessage($message);
		
		$ticket = DAO_Ticket::get($ticket_id);
		
		// Add additional requesters to ticket
		if(is_array($fromList) && !empty($fromList))
		foreach($fromList as $requester) {
			if(empty($requester))
				continue;
			$host = empty($requester->host) ? 'localhost' : $requester->host;
			DAO_Ticket::createRequester($requester->mailbox . '@' . $host, $ticket_id);
		}
		
		// Worker reply
		$properties = array(
		    'draft_id' => $draft_id,
		    'message_id' => $ticket->first_message_id,
		    'ticket_id' => $ticket_id,
		    'subject' => $subject,
		    'content' => $content,
		    'files' => @$_FILES['attachment'],
		    'closed' => $closed,
		    'bucket_id' => $move_bucket,
		    'ticket_reopen' => $ticket_reopen,
		    'agent_id' => $active_worker->id,
			'dont_send' => (false==$send_to_requesters),
		);
		
		// Don't reset owners to 'blank', but allow overrides from GUI log ticket form
		if(!empty($owner_ids))
	    	$properties['context_workers'] = $owner_ids;
		
		if(CerberusMail::sendTicketMessage($properties)) {
			if(!empty($draft_id))
				DAO_MailQueue::delete($draft_id);
		}
		
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
		$tpl_path = $this->_TPL_PATH;
		$tpl->assign('path', $tpl_path);
        
        $visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */

        $view = C4_AbstractViewLoader::getView($view_id);
        
        $tpl->assign('view_id', $view_id);
        $tpl->assign('mode', $mode);

        if($mode == "headers" && empty($mode_param)) {
	        $tpl->display($tpl_path.'tickets/rpc/ticket_view_assist_headers.tpl');
	        
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
	        
	        $tpl->display($tpl_path.'tickets/rpc/ticket_view_assist.tpl');
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
	            		'owner' => array(
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

	function viewTakeTicketsAction() {
	    @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
	    @$ticket_ids = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'array');
	    
	    $active_worker = CerberusApplication::getActiveWorker();
	    
        //====================================
	    // Undo functionality
	    // [TODO] Reimplement UNDO
		//====================================

	    // Set our context links
	    foreach($ticket_ids as $ticket_id) {
			CerberusContexts::addWorkers(CerberusContexts::CONTEXT_TICKET, $ticket_id, array($active_worker->id));	    	
	    }
	    
	    $view = C4_AbstractViewLoader::getView($view_id);
	    $view->render();
	    return;
	}

	function viewSurrenderTicketsAction() {
	    @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
	    @$ticket_ids = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'array');
	    
	    $active_worker = CerberusApplication::getActiveWorker();

        //====================================
	    // Undo functionality
	    // [TODO] Reimplement
        //====================================
	    
        //DAO_Ticket::update($ticket_ids, $fields);
        
        foreach($ticket_ids as $ticket_id) {
        	CerberusContexts::removeWorkers(CerberusContexts::CONTEXT_TICKET, $ticket_id, array($active_worker->id));
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

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
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
		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Ticket::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		// Broadcast
		CerberusContexts::getContext(CerberusContexts::CONTEXT_TICKET, null, $token_labels, $token_values);
		$tpl->assign('token_labels', $token_labels);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'tickets/rpc/batch_panel.tpl');
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
		
		$do = array();
		
		// [TODO] This logic is repeated in several places -- try to condense (like custom field form handlers)
		
		// Move to Group/Bucket
		@$move_code = DevblocksPlatform::importGPC($_REQUEST['do_move'],'string',null);
		if(0 != strlen($move_code)) {
			list($g_id, $b_id) = CerberusApplication::translateTeamCategoryCode($move_code);
			$do['move'] = array(
				'group_id' => intval($g_id),
				'bucket_id' => intval($b_id),
			);
		}
		
		// Owners
		$owner_options = array();
		
		@$owner_add_ids = DevblocksPlatform::importGPC($_REQUEST['do_owner_add_ids'],'array',array());
		if(!empty($owner_add_ids))
			$owner_params['add'] = $owner_add_ids;
			
		@$owner_remove_ids = DevblocksPlatform::importGPC($_REQUEST['do_owner_remove_ids'],'array',array());
		if(!empty($owner_remove_ids))
			$owner_params['remove'] = $owner_remove_ids;
		
		if(!empty($owner_params))
			$do['owner'] = $owner_params;
			
		// Spam training
		@$is_spam = DevblocksPlatform::importGPC($_REQUEST['do_spam'],'string',null);
		if(0 != strlen($is_spam)) {
			$do['spam'] = array(
				'is_spam' => (!$is_spam?0:1)
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
	    $ticket_ids = array();
	    
	    if($filter == 'sender') {
	        $data = $senders;
		} elseif($filter == 'subject') {
	        $data = $subjects;
	    } elseif($filter == 'checks') {
	    	$filter = ''; // bulk update just looks for $ticket_ids == !null
	        $ticket_ids = DevblocksPlatform::parseCsvString($ticket_id_str);
	    }
		
	    // Restrict to current worker groups
		$memberships = $active_worker->getMemberships();
		$view->addParam(new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_TEAM_ID, 'in', array_keys($memberships)), 'tmp'); 
	    
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);
		
		$view->doBulkUpdate($filter, '', $data, $do, $ticket_ids);
		
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
		$tpl->assign('path', $this->_TPL_PATH);
		
		if($active_worker->hasPriv('core.ticket.view.actions.broadcast_reply')) {
			@$broadcast_message = DevblocksPlatform::importGPC($_REQUEST['broadcast_message'],'string',null);

			// Get total
			$view->renderPage = 0;
			$view->renderLimit = 1;
			$view->renderTotal = true;
			list($null, $total) = $view->getData();
			
			// Get the first row from the view
			$view->renderPage = mt_rand(0, $total-1);
			$view->renderLimit = 1;
			$view->renderTotal = false;
			list($result, $null) = $view->getData();
			
			if(empty($result)) {
				$success = false;
				$output = "There aren't any rows in this view!";
				
			} else {
				// Try to build the template
				CerberusContexts::getContext(CerberusContexts::CONTEXT_TICKET, array_shift($result), $token_labels, $token_values);
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
			$tpl->assign('output', htmlentities($output, null, LANG_CHARSET_CODE));
			$tpl->display('file:'.$this->_TPL_PATH.'internal/renderers/test_results.tpl');
		}
	}
	
	// ajax
	function showViewRssAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$source = DevblocksPlatform::importGPC($_REQUEST['source'],'string','');
		
		$view = C4_AbstractViewLoader::getView($view_id);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		$tpl->assign('view_id', $view_id);
		$tpl->assign('view', $view);
		$tpl->assign('source', $source);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'internal/views/view_rss_builder.tpl');
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
