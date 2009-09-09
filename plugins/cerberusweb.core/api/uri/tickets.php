<?php
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
				
				$settings = CerberusSettings::getInstance();
				
				// Workers
				$workers = DAO_Worker::getAllActive();
				$tpl->assign('workers', $workers);
				
				// Groups
				$teams = DAO_Group::getAll();
				$tpl->assign_by_ref('teams', $teams);
				
				// Groups+Buckets
				$team_categories = DAO_Bucket::getTeams();
				$tpl->assign('team_categories', $team_categories);
				
				// SendMailToolbarItem Extensions
				$sendMailToolbarItems = DevblocksPlatform::getExtensions('cerberusweb.mail.send.toolbaritem', true);
				if(!empty($sendMailToolbarItems))
					$tpl->assign('sendmail_toolbaritems', $sendMailToolbarItems);

				// Attachments				
				$tpl->assign('upload_max_filesize', ini_get('upload_max_filesize'));

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
			    C4_TicketView::clearLastActions();
			    				
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
		
		$views = array();

		// Request path
		@$request = DevblocksPlatform::importGPC($_REQUEST['request'],'string','');
		$response_path = explode('/', $request);
		@array_shift($response_path); // tickets
		@$controller = array_shift($response_path); // workflow
		
		// Make sure the global URL was for us
		if(0!=strcasecmp('workflow',$controller))
			$response_path = null;

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$group_buckets = DAO_Bucket::getTeams();
		$tpl->assign('group_buckets', $group_buckets);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$memberships = $active_worker->getMemberships();

		// Totals
		$group_counts = DAO_WorkflowView::getGroupTotals();
		$tpl->assign('group_counts', $group_counts);

		// View
		$title = $translate->_('mail.overview.all_groups');

		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'C4_TicketView';
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

				$workflowView->params = array(
					SearchFields_Ticket::TICKET_CLOSED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',CerberusTicketStatus::OPEN),
					SearchFields_Ticket::TICKET_WAITING => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_WAITING,'=',0),
					SearchFields_Ticket::TICKET_NEXT_WORKER_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_NEXT_WORKER_ID,'=',0),
				);
				
				if(!is_null($filter_group_id) && isset($groups[$filter_group_id])) {
					$tpl->assign('filter_group_id', $filter_group_id);
					$title = $groups[$filter_group_id]->name;
					$workflowView->params[SearchFields_Ticket::TICKET_TEAM_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_TEAM_ID,'=',$filter_group_id);
					
					@$filter_bucket_id = array_shift($response_path);
					if(!is_null($filter_bucket_id)) {
						$tpl->assign('filter_bucket_id', $filter_bucket_id);
						@$title .= ': '.
							(($filter_bucket_id == 0) ? $translate->_('common.inbox') : $group_buckets[$filter_group_id][$filter_bucket_id]->name);
						$workflowView->params[SearchFields_Ticket::TICKET_CATEGORY_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CATEGORY_ID,'=',$filter_bucket_id);
					} else {
						$assignable_buckets = DAO_Bucket::getAssignableBuckets($filter_group_id);
						$assignable_bucket_ids = array_keys($assignable_buckets);
						
						// Does this manager want the inbox assignable?
						if(DAO_GroupSettings::get($filter_group_id, DAO_GroupSettings::SETTING_INBOX_IS_ASSIGNABLE, 1))
							array_unshift($assignable_bucket_ids,0);
						
						$workflowView->params[SearchFields_Ticket::TICKET_CATEGORY_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CATEGORY_ID,'in',$assignable_bucket_ids);
					}
				}

				break;
				
			case 'all':
			default:
				$workflowView->params = array(
					SearchFields_Ticket::TICKET_CLOSED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',CerberusTicketStatus::OPEN),
					SearchFields_Ticket::TICKET_WAITING => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_WAITING,'=',0),
					SearchFields_Ticket::TICKET_NEXT_WORKER_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_NEXT_WORKER_ID,'=',0),
				);

				$subparams = array(
					DevblocksSearchCriteria::GROUP_OR
				);
				
				if(is_array($memberships))
				foreach($memberships as $group_id => $member) {
					$assignable_buckets = DAO_Bucket::getAssignableBuckets($group_id);
					$assignable_bucket_ids = array_keys($assignable_buckets);
					
					// Does this manager want the inbox assignable?
					if(DAO_GroupSettings::get($group_id, DAO_GroupSettings::SETTING_INBOX_IS_ASSIGNABLE, 1))
						array_unshift($assignable_bucket_ids,0);
					
					$subparams[] = array(
						DevblocksSearchCriteria::GROUP_AND,
						SearchFields_Ticket::TICKET_TEAM_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_TEAM_ID,'=',$group_id),
						SearchFields_Ticket::TICKET_CATEGORY_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CATEGORY_ID,'in',$assignable_bucket_ids),
					);
				}
				
				// If we had subgroups from memberships
				if(1 < count($subparams))
					$workflowView->params['tmp_GrpBkt'] = $subparams;
				else // We're not in any groups
					$workflowView->params[SearchFields_Ticket::TICKET_TEAM_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_TEAM_ID,'=',-1);
				
				break;
		}
		
		$workflowView->name = $title;
		C4_AbstractViewLoader::setView($workflowView->id, $workflowView);
		$views[] = $workflowView;
		
		$tpl->assign('views', $views);
		
		// Log activity
		DAO_Worker::logActivity(
			$active_worker->id,
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
	
	function showOverviewTabAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		$db = DevblocksPlatform::getDatabaseService();
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();

		// Remember the tab
		$visit->set(CerberusVisit::KEY_MAIL_MODE, 'overview');		
		
		$views = array();

		// Request path
		@$request = DevblocksPlatform::importGPC($_REQUEST['request'],'string','');
		$response_path = explode('/', $request);
		@array_shift($response_path); // tickets
		@$controller = array_shift($response_path); // overview

		// Make sure the global URL was for us
		if(0!=strcasecmp('overview',$controller))
			$response_path = null;
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$group_buckets = DAO_Bucket::getTeams();
		$tpl->assign('group_buckets', $group_buckets);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$memberships = $active_worker->getMemberships();

		// Totals
		
		$group_counts = DAO_Overview::getGroupTotals();
		$tpl->assign('group_counts', $group_counts);

		$waiting_counts = DAO_Overview::getWaitingTotals();
		$tpl->assign('waiting_counts', $waiting_counts);
		
		$worker_counts = DAO_Overview::getWorkerTotals();
		$tpl->assign('worker_counts', $worker_counts);
		
		// All Open
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'C4_TicketView';
		$defaults->id = CerberusApplication::VIEW_OVERVIEW_ALL;
		$defaults->name = $translate->_('mail.overview.all_groups');
		$defaults->view_columns = array(
			SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
			SearchFields_Ticket::TICKET_UPDATED_DATE,
			SearchFields_Ticket::TICKET_TEAM_ID,
			SearchFields_Ticket::TICKET_CATEGORY_ID,
			SearchFields_Ticket::TICKET_NEXT_WORKER_ID,
		);
		$defaults->renderLimit = 10;
		$defaults->renderSortBy = SearchFields_Ticket::TICKET_UPDATED_DATE;
		$defaults->renderSortAsc = 0;
		
		$title = $translate->_('mail.overview.all_groups');
		$overView = C4_AbstractViewLoader::getView(CerberusApplication::VIEW_OVERVIEW_ALL, $defaults);
		
		$overView->renderPage = 0;
		
		// Filter persistence
		if(empty($response_path)) {
			@$response_path = explode('/',$visit->get(CerberusVisit::KEY_OVERVIEW_FILTER, 'all'));
		} else {
			// View Filter
			$visit->set(CerberusVisit::KEY_OVERVIEW_FILTER, implode('/',$response_path));
		}
		
		@$filter = array_shift($response_path);
		
		switch($filter) {
			case 'group':
				@$filter_group_id = array_shift($response_path);
				
				$overView->params = array(
					SearchFields_Ticket::TICKET_CLOSED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',CerberusTicketStatus::OPEN),
					SearchFields_Ticket::TICKET_WAITING => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_WAITING,'=',0),
				);
				
				if(!is_null($filter_group_id) && isset($groups[$filter_group_id])) {
					$tpl->assign('filter_group_id', $filter_group_id);
					$title = $groups[$filter_group_id]->name;
					$overView->params[SearchFields_Ticket::TICKET_TEAM_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_TEAM_ID,'=',$filter_group_id);
					
					@$filter_bucket_id = array_shift($response_path);
					if(!is_null($filter_bucket_id)) {
						$tpl->assign('filter_bucket_id', $filter_bucket_id);
						@$title .= ': '.
							(($filter_bucket_id == 0) ? $translate->_('common.inbox') : $group_buckets[$filter_group_id][$filter_bucket_id]->name);
						$overView->params[SearchFields_Ticket::TICKET_CATEGORY_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CATEGORY_ID,'=',$filter_bucket_id);
					}
				}

				break;
				
			case 'waiting':
				@$filter_group_id = array_shift($response_path);
				
				$overView->params = array(
					SearchFields_Ticket::TICKET_CLOSED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',CerberusTicketStatus::OPEN),
					SearchFields_Ticket::TICKET_WAITING => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_WAITING,'=',1),
				);
				
				if(!is_null($filter_group_id) && isset($groups[$filter_group_id])) {
					$tpl->assign('filter_group_id', $filter_group_id);
					$title = vsprintf($translate->_('mail.overview.waiting.title'), $groups[$filter_group_id]->name);
					$overView->params[SearchFields_Ticket::TICKET_TEAM_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_TEAM_ID,'=',$filter_group_id);
				}

				break;
				
			case 'worker':
				@$filter_worker_id = array_shift($response_path);

				$overView->params = array(
					SearchFields_Ticket::TICKET_CLOSED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',CerberusTicketStatus::OPEN),
					SearchFields_Ticket::TICKET_WAITING => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_WAITING,'=',0),
					$overView->params[SearchFields_Ticket::TICKET_TEAM_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_TEAM_ID,'in',array_keys($memberships)), // censor
				);

				if(!is_null($filter_worker_id)) {
					$title = vsprintf($translate->_('mail.overview.assigned.title'), $workers[$filter_worker_id]->getName());
					$overView->params[SearchFields_Ticket::TICKET_NEXT_WORKER_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_NEXT_WORKER_ID,'=',$filter_worker_id);
					
					@$filter_group_id = array_shift($response_path);
					if(!is_null($filter_group_id) && isset($groups[$filter_group_id])) {
						$overView->params[SearchFields_Ticket::TICKET_TEAM_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_TEAM_ID,'=',$filter_group_id);
					}
				}
				
				break;
				
			case 'all':
			default:
				$overView->params = array(
					SearchFields_Ticket::TICKET_CLOSED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',CerberusTicketStatus::OPEN),
					SearchFields_Ticket::TICKET_WAITING => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_WAITING,'=',0),
					SearchFields_Ticket::TICKET_TEAM_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_TEAM_ID,'in',array_keys($memberships)),
				);
				
				break;
		}
		
		$overView->name = $title;
		C4_AbstractViewLoader::setView($overView->id, $overView);
		$views[] = $overView;
		
		$tpl->assign('views', $views);
		
		// Log activity
		DAO_Worker::logActivity(
			$active_worker->id,
			new Model_Activity(
				'activity.mail.overview',
				array(
					'<i>'.$overView->name.'</i>'
				)
			)
		);
		
		// ====== Who's Online
		$whos_online = DAO_Worker::getAllOnline();
		if(!empty($whos_online)) {
			$tpl->assign('whos_online', $whos_online);
			$tpl->assign('whos_online_count', count($whos_online));
		}
		
        $tpl->display('file:' . $this->_TPL_PATH . 'tickets/overview/index.tpl');		
	}
	
	function showSearchTabAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
//		$db = DevblocksPlatform::getDatabaseService();
		$visit = CerberusApplication::getVisit();
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Log activity
		DAO_Worker::logActivity(
			$active_worker->id,
			new Model_Activity(
				'activity.mail.search'
			)
		);
		
		// Remember the tab
		$visit->set(CerberusVisit::KEY_MAIL_MODE, 'search');		
		
		// Request path
//		@$request = DevblocksPlatform::importGPC($_REQUEST['request'],'string','');
//		$response_path = explode('/', $request);
//		@array_shift($response_path); // tickets
//		@array_shift($response_path); // overview

		$tpl->assign('response_uri', 'tickets/search');
		
		$view = C4_AbstractViewLoader::getView(CerberusApplication::VIEW_SEARCH);
		
		if(null == $view) {
			$view = C4_TicketView::createSearchView();
			C4_AbstractViewLoader::setView($view->id,$view);
		}
		
		$tpl->assign('view', $view);
		$tpl->assign('params', $view->params);
	
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		
		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);
		
		$team_categories = DAO_Bucket::getTeams();
		$tpl->assign('team_categories', $team_categories);
		
		$tpl->assign('view_fields', C4_TicketView::getFields());
		$tpl->assign('view_searchable_fields', C4_TicketView::getSearchFields());
		
		$tpl->display('file:' . $this->_TPL_PATH . 'tickets/search/index.tpl');
	}
	
	// Ajax
	function refreshSidebarAction() {
		$db = DevblocksPlatform::getDatabaseService();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);

		$visit = CerberusApplication::getVisit();
		$active_worker = CerberusApplication::getActiveWorker();

		$section = $visit->get(CerberusVisit::KEY_MAIL_MODE, '');
		
		switch($section) {
			case 'workflow':
				$groups = DAO_Group::getAll();
				$tpl->assign('groups', $groups);
				
				$group_buckets = DAO_Bucket::getTeams();
				$tpl->assign('group_buckets', $group_buckets);
				
				$group_counts = DAO_WorkflowView::getGroupTotals();
				$tpl->assign('group_counts', $group_counts);
				
				$tpl->display('file:' . $this->_TPL_PATH . 'tickets/workflow/sidebar.tpl');
				break;
				
			case 'overview':
				$groups = DAO_Group::getAll();
				$tpl->assign('groups', $groups);
				
				$group_buckets = DAO_Bucket::getTeams();
				$tpl->assign('group_buckets', $group_buckets);
				
				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				
				$group_counts = DAO_Overview::getGroupTotals();
				$tpl->assign('group_counts', $group_counts);
				
				$waiting_counts = DAO_Overview::getWaitingTotals();
				$tpl->assign('waiting_counts', $waiting_counts);
				
				$worker_counts = DAO_Overview::getWorkerTotals();
				$tpl->assign('worker_counts', $worker_counts);
				
				$tpl->display('file:' . $this->_TPL_PATH . 'tickets/overview/sidebar.tpl');
				break;
		}
	}
	
	// Ajax
	// [TODO] Move to 'c=internal'
	function showCalloutAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'string');

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);

		$callouts = CerberusApplication::getTourCallouts();
		
	    $callout = array();
	    if(isset($callouts[$id]))
	        $callout = $callouts[$id];
		
	    $tpl->assign('callout',$callout);
		
		$tpl->cache_lifetime = "0";
		$tpl->display('tour/callout.tpl');
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
        
        C4_TicketView::setLastAction($view_id,$last_action);
        //====================================	    
	    
	    CerberusBayes::markTicketAsSpam($id);
	    
	    // [TODO] Move categories (according to config)
	    $fields = array(
	        DAO_Ticket::IS_DELETED => 1,
	        DAO_Ticket::IS_CLOSED => CerberusTicketStatus::CLOSED
	    );
	    DAO_Ticket::updateTicket($id, $fields);
	    
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
		$tpl->cache_lifetime = "0";
		$tpl->display($path.'tickets/rpc/ticket_view_output.tpl');
	} 
	
	// Post
	// [TODO] Move to another page
	function doStopTourAction() {
//		$request = DevblocksPlatform::getHttpRequest();

		$worker = CerberusApplication::getActiveWorker();
		DAO_WorkerPref::set($worker->id, 'assist_mode', 0);
		
//		DevblocksPlatform::redirect(new DevblocksHttpResponse($request->path, $request->query));
	}
	
	// Post	
	function doQuickSearchAction() {
        @$type = DevblocksPlatform::importGPC($_POST['type'],'string'); 
        @$query = DevblocksPlatform::importGPC($_POST['query'],'string');

        $query = trim($query);
        
        $visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
		$searchView = C4_AbstractViewLoader::getView(CerberusApplication::VIEW_SEARCH);
		
		if(null == $searchView)
			$searchView = C4_TicketView::createSearchView();

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
                
            case "content":
            	$params[SearchFields_Ticket::TICKET_MESSAGE_CONTENT] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MESSAGE_CONTENT,DevblocksSearchCriteria::OPER_FULLTEXT,$query);               
                break;
                
            case "org":
		        if($query && false===strpos($query,'*'))
		            $query = '*' . $query . '*';
            	$params[SearchFields_Ticket::ORG_NAME] = new DevblocksSearchCriteria(SearchFields_Ticket::ORG_NAME,DevblocksSearchCriteria::OPER_LIKE,$query);               
                break;
                
        }
        
        $searchView->params = $params;
        $searchView->renderPage = 0;
        $searchView->renderSortBy = null;
        
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
		$tpl->assign_by_ref('teams', $teams);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		// Load Defaults
		$team_id = intval($visit->get('compose.defaults.from', ''));
		$subject = $visit->get('compose.defaults.subject', '');
		$closed = intval($visit->get('compose.defaults.closed', ''));
		$next_worker_id = intval($visit->get('compose.defaults.next_worker_id', ''));
		$tpl->assign('default_group_id', $team_id);
		$tpl->assign('default_subject', $subject);
		$tpl->assign('default_closed', $closed);
		$tpl->assign('default_next_worker_id', $next_worker_id);
		
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
		@$next_worker_id = DevblocksPlatform::importGPC($_POST['next_worker_id'],'integer',0);

		$visit = CerberusApplication::getVisit();

		// Save Defaults
		$visit->set('compose.defaults.from', $team_id);
		$visit->set('compose.defaults.subject', $subject);
		$visit->set('compose.defaults.closed', $closed);
		$visit->set('compose.defaults.next_worker_id', $next_worker_id);
		
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
			'next_worker_id' => $next_worker_id,
		);
		
		$ticket_id = CerberusMail::compose($properties);

		if(!empty($view_id)) {
			$defaults = new C4_AbstractViewModel();
			$defaults->class_name = 'C4_TicketView';
			$defaults->id = $view_id;
			
			$view = C4_AbstractViewLoader::getView($view_id, $defaults);
			$view->render();
		}
		exit;
	}
	
	function getComposeSignatureAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer',0);
		
		$settings = CerberusSettings::getInstance();
		$group = DAO_Group::getTeam($group_id);

		$active_worker = CerberusApplication::getActiveWorker();
		$worker = DAO_Worker::getAgent($active_worker->id); // Use the most recent info (not session)
		$sig = $settings->get(CerberusSettings::DEFAULT_SIGNATURE,'');

		if(!empty($group->signature)) {
			$sig = $group->signature;
		}

		/*
		 * [TODO] This is the 3rd place this replace happens, we really need 
		 * to move the signature translation into something like CerberusApplication
		 */
		echo sprintf("\r\n%s\r\n",
			str_replace(
		        array('#first_name#','#last_name#','#title#'),
		        array($worker->first_name,$worker->last_name,$worker->title),
		        $sig
			)
		);
	}
	
	// Ajax
	function showPreviewAction() {
	    @$tid = DevblocksPlatform::importGPC($_REQUEST['tid'],'integer',0);
	    @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
	    
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = $this->_TPL_PATH;
		$tpl->assign('path', $tpl_path);

		$tpl->assign('view_id', $view_id);
		
		if(null != ($ticket = DAO_Ticket::getTicket($tid))) {
			/* @var $ticket CerberusTicket */
		    $tpl->assign('ticket', $ticket);
		}
		
		if(null != ($messages = DAO_Ticket::getMessagesByTicket($tid))) {
	        if(!empty($messages)) {	    
		        @$last = array_pop($messages);
		        @$content = DAO_MessageContent::get($last->id);
				
			    $tpl->assign('message', $last);
			    $tpl->assign('content', $content);
	        }
		}
	    
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		
		$team_categories = DAO_Bucket::getTeams();
		$tpl->assign('team_categories', $team_categories);
	    
	    $workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
	    
		// Custom fields
		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Ticket::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		$custom_field_values = DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Ticket::ID, $ticket->id);
		if(isset($custom_field_values[$ticket->id]))
			$tpl->assign('custom_field_values', $custom_field_values[$ticket->id]);
		
		// Display
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . $this->_TPL_PATH . 'tickets/rpc/preview_panel.tpl');
	}
	
	// Ajax
	function savePreviewAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$subject = DevblocksPlatform::importGPC($_REQUEST['subject'],'string','');
		@$closed = DevblocksPlatform::importGPC($_REQUEST['closed'],'integer',0);
		@$next_worker_id = DevblocksPlatform::importGPC($_REQUEST['next_worker_id'],'integer',0);
		@$bucket = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'string','');
		@$spam_training = DevblocksPlatform::importGPC($_REQUEST['spam_training'],'string','');
		
		$fields = array(
			DAO_Ticket::SUBJECT => $subject,
			DAO_Ticket::NEXT_WORKER_ID => $next_worker_id,
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
		
		DAO_Ticket::updateTicket($id, $fields);
		
		// Custom field saves
		@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
		DAO_CustomFieldValue::handleFormPost(ChCustomFieldSource_Ticket::ID, $id, $field_ids);
		
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'C4_TicketView';
		$defaults->id = $view_id;
		
		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->render();
		exit;
	}
	
	function composeMailAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker->hasPriv('core.mail.send'))
			return;
		
		@$team_id = DevblocksPlatform::importGPC($_POST['team_id'],'integer'); 
		@$to = DevblocksPlatform::importGPC($_POST['to'],'string');
		@$cc = DevblocksPlatform::importGPC($_POST['cc'],'string','');
		@$bcc = DevblocksPlatform::importGPC($_POST['bcc'],'string','');
		@$subject = DevblocksPlatform::importGPC($_POST['subject'],'string','(no subject)');
		@$content = DevblocksPlatform::importGPC($_POST['content'],'string');
		@$files = $_FILES['attachment'];
		
		@$closed = DevblocksPlatform::importGPC($_POST['closed'],'integer',0);
		@$move_bucket = DevblocksPlatform::importGPC($_POST['bucket_id'],'string','');
		@$next_worker_id = DevblocksPlatform::importGPC($_POST['next_worker_id'],'integer',0);
		@$ticket_reopen = DevblocksPlatform::importGPC($_POST['ticket_reopen'],'string','');
		@$unlock_date = DevblocksPlatform::importGPC($_POST['unlock_date'],'string','');
		
		if(DEMO_MODE) {
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','compose')));
			return;
		}

		if(empty($to)) {
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','compose')));
			return;
		}

		$properties = array(
			'team_id' => $team_id,
			'to' => $to,
			'cc' => $cc,
			'bcc' => $bcc,
			'subject' => $subject,
			'content' => $content,
			'files' => $files,
			'closed' => $closed,
			'move_bucket' => $move_bucket,
			'next_worker_id' => $next_worker_id,
			'ticket_reopen' => $ticket_reopen,
			'unlock_date' => $unlock_date,
		);
		
		$ticket_id = CerberusMail::compose($properties);
		
		$ticket = DAO_Ticket::getTicket($ticket_id);
		
		$visit = CerberusApplication::getVisit(); /* @var CerberusVisit $visit */
		$visit->set('compose.last_ticket', $ticket->mask);
		
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
		
		@$send_to_requesters = DevblocksPlatform::importGPC($_POST['send_to_requesters'],'integer',0);
		@$closed = DevblocksPlatform::importGPC($_POST['closed'],'integer',0);
		@$move_bucket = DevblocksPlatform::importGPC($_POST['bucket_id'],'string','');
		@$next_worker_id = DevblocksPlatform::importGPC($_POST['next_worker_id'],'integer',0);
		@$ticket_reopen = DevblocksPlatform::importGPC($_POST['ticket_reopen'],'string','');
		@$unlock_date = DevblocksPlatform::importGPC($_POST['unlock_date'],'string','');
		
		if(DEMO_MODE) {
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','create')));
			return;
		}

		// ********
		
		$message = new CerberusParserMessage();
		$message->headers['date'] = date('r'); 
		$message->headers['to'] = 'cerberus@localhost';
		$message->headers['subject'] = $subject;
		$message->headers['message-id'] = CerberusApplication::generateMessageId();
		//$message->headers['x-cerberus-portal'] = 1; 
		
		// Sender
		$fromList = imap_rfc822_parse_adrlist($reqs,'');
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
		
		$ticket = DAO_Ticket::getTicket($ticket_id);
		
		// Add additional requesters to ticket
		if(is_array($fromList) && !empty($fromList))
		foreach($fromList as $requester) {
			if(empty($requester))
				continue;
			$host = empty($requester->host) ? 'localhost' : $requester->host;
			$requester_addy = DAO_Address::lookupAddress($requester->mailbox . '@' . $host, true);
			DAO_Ticket::createRequester($requester_addy->id, $ticket_id);
		}
		
		// Worker reply
		$properties = array(
		    'message_id' => $ticket->first_message_id,
		    'ticket_id' => $ticket_id,
		    'subject' => $subject,
		    'content' => $content,
		    'files' => @$_FILES['attachment'],
		    'next_worker_id' => $next_worker_id,
		    'closed' => $closed,
		    'bucket_id' => $move_bucket,
		    'ticket_reopen' => $ticket_reopen,
		    'unlock_date' => $unlock_date,
		    'agent_id' => $active_worker->id,
			'dont_send' => (false==$send_to_requesters),
		);
		
		CerberusMail::sendTicketMessage($properties);
		
		// ********

//		if(empty($to) || empty($team_id)) {
//			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','create')));
//			return;
//		}
		
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
            $headers = DAO_MessageHeader::getUnique();
            $tpl->assign('headers', $headers);
            
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
	       	// [TODO] Test impact
			$active_worker = CerberusApplication::getActiveWorker();
			$memberships = $active_worker->getMemberships();
			$view->params[] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_TEAM_ID, 'in', array_keys($memberships)); 
			
	        // [JAS]: Calculate statistics about the current view (top unique senders/subjects/domains)
		    $biggest = DAO_Ticket::analyze($view->params, 15, $mode, $mode_param);
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
	            		'assign' => array(
	            			'worker_id' => $w_id,
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

	    // Reset the paging since we may have reduced our list size
	    $view->renderPage = 0;
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
        foreach($orig_tickets as $orig_ticket_idx => $orig_ticket) { /* @var $orig_ticket CerberusTicket */
            $last_action->ticket_ids[$orig_ticket_idx] = array(
                DAO_Ticket::TEAM_ID => $orig_ticket->team_id,
                DAO_Ticket::CATEGORY_ID => $orig_ticket->category_id
            );
            $orig_ticket->team_id = $team_id;
            $orig_ticket->category_id = $category_id;
            $orig_tickets[$orig_ticket_idx] = $orig_ticket;
        }
        
        C4_TicketView::setLastAction($view_id,$last_action);
	    
	    // Make our changes to the entire list of tickets
	    if(!empty($ticket_ids) && !empty($team_id)) {
	        DAO_Ticket::updateTicket($ticket_ids, $fields);
	    }
	    
	    $view = C4_AbstractViewLoader::getView($view_id);
	    $view->render();
	    return;
	}

	function viewTakeTicketsAction() {
	    @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
	    @$ticket_ids = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'array');
	    
	    $active_worker = CerberusApplication::getActiveWorker();
	    
        $fields = array(
            DAO_Ticket::NEXT_WORKER_ID => $active_worker->id,
        );
	    
        //====================================
	    // Undo functionality
        $last_action = new Model_TicketViewLastAction();
        $last_action->action = Model_TicketViewLastAction::ACTION_TAKE;

        if(is_array($ticket_ids)) {
			@$orig_tickets = DAO_Ticket::getTickets($ticket_ids); /* @var CerberusTicket[] $orig_tickets */

	        foreach($ticket_ids as $ticket_id) {
	            $last_action->ticket_ids[$ticket_id] = array(
	                DAO_Ticket::NEXT_WORKER_ID => $orig_tickets[$ticket_id]->next_worker_id
	            );
	        }
        }

        $last_action->action_params = $fields;
        
        C4_TicketView::setLastAction($view_id,$last_action);
        //====================================
	    
        DAO_Ticket::updateTicket($ticket_ids, $fields);
	    
	    $view = C4_AbstractViewLoader::getView($view_id);
	    $view->render();
	    return;
	}

	function viewSurrenderTicketsAction() {
	    @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
	    @$ticket_ids = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'array');
	    
	    $active_worker = CerberusApplication::getActiveWorker();

	    $fields = array(
            DAO_Ticket::NEXT_WORKER_ID => 0,
            DAO_Ticket::UNLOCK_DATE => 0,
        );
	    
        //====================================
	    // Undo functionality
        $last_action = new Model_TicketViewLastAction();
        $last_action->action = Model_TicketViewLastAction::ACTION_SURRENDER;

        if(is_array($ticket_ids)) {
			@$orig_tickets = DAO_Ticket::getTickets($ticket_ids); /* @var CerberusTicket[] $orig_tickets */

	        foreach($ticket_ids as $ticket_id) {
	        	// Only surrender what we own
	        	if($orig_tickets[$ticket_id]->next_worker_id != $active_worker->id) {
	        		unset($ticket_ids[$ticket_id]);
	        		continue;
	        	}
	        	
	            $last_action->ticket_ids[$ticket_id] = array(
	                DAO_Ticket::NEXT_WORKER_ID => $active_worker->id
	            );
	        }
        }

        $last_action->action_params = $fields;
        
        C4_TicketView::setLastAction($view_id,$last_action);
        //====================================
	    
        DAO_Ticket::updateTicket($ticket_ids, $fields);
	    
	    $view = C4_AbstractViewLoader::getView($view_id);
	    $view->render();
	    return;
	}
	
	function viewMergeTicketsAction() {
	    @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
	    @$ticket_ids = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'array');

        C4_TicketView::setLastAction($view_id,null);
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
        
        C4_TicketView::setLastAction($view_id,$last_action);
        //====================================
	    
        DAO_Ticket::updateTicket($ticket_ids, $fields);
	    
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
        
        C4_TicketView::setLastAction($view_id,$last_action);
        //====================================

        DAO_Ticket::updateTicket($ticket_ids, $fields);
	    
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
        
        C4_TicketView::setLastAction($view_id,$last_action);
        //====================================

        DAO_Ticket::updateTicket($ticket_ids, $fields);
	    
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
        
        C4_TicketView::setLastAction($view_id,$last_action);
        //====================================

        // [TODO] Bayes should really be smart enough to allow training of batches of IDs
	    if(!empty($ticket_ids))
	    foreach($ticket_ids as $id) {
	        CerberusBayes::markTicketAsNotSpam($id);
	    }
	    
        DAO_Ticket::updateTicket($ticket_ids, $fields);
	    
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
        
        C4_TicketView::setLastAction($view_id,$last_action);
        //====================================
	    
        // {TODO] Batch
	    if(!empty($ticket_ids))
	    foreach($ticket_ids as $id) {
	        CerberusBayes::markTicketAsSpam($id);
	    }
	    
        DAO_Ticket::updateTicket($ticket_ids, $fields);
	    
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
        
        C4_TicketView::setLastAction($view_id,$last_action);
        //====================================
	    
        DAO_Ticket::updateTicket($ticket_ids, $fields);
	    
	    $view = C4_AbstractViewLoader::getView($view_id);
	    $view->render();
	    return;
	}
	
	function viewUndoAction() {
	    @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
	    @$clear = DevblocksPlatform::importGPC($_REQUEST['clear'],'integer',0);
	    $last_action = C4_TicketView::getLastAction($view_id);
	    
	    if($clear || empty($last_action)) {
            C4_TicketView::setLastAction($view_id,null);
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
	        DAO_Ticket::updateTicket($ticket_id, $fields);
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
		    foreach($tickets as $ticket) { /* @var $ticket CerberusTicket */
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
		
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . $this->_TPL_PATH . 'tickets/rpc/batch_panel.tpl');
	}
	
	// Ajax
	function doBatchUpdateAction() {
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
		
		// Assign to worker
		@$worker_id = DevblocksPlatform::importGPC($_REQUEST['do_assign'],'string',null);
		if(0 != strlen($worker_id)) {
			$do['assign'] = array(
				'worker_id' => intval($worker_id)
			);
		}
			
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
		$active_worker = CerberusApplication::getActiveWorker();
		$memberships = $active_worker->getMemberships();
		$view->params['tmp'] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_TEAM_ID, 'in', array_keys($memberships)); 
	    
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);
		
		$view->doBulkUpdate($filter, '', $data, $do, $ticket_ids);
		
		// Clear our temporary group restriction before re-rendering
		unset($view->params['tmp']);
		
		$view->render();
		return;
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
		
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . $this->_TPL_PATH . 'internal/views/view_rss_builder.tpl');
	}
	
	// post
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
			'params' => $view->params,
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

		if(!empty($view->params)) {
		    $params = array();
		    
		    // Index by field name for search system
		    if(is_array($view->params))
		    foreach($view->params as $key => $criteria) { /* @var $criteria DevblocksSearchCriteria */
                $params[$key] = $criteria;
		    }
		}
		
		if(null == ($search_view = C4_AbstractViewLoader::getView(CerberusApplication::VIEW_SEARCH))) {
			$search_view = C4_TicketView::createSearchView();
		}
		$search_view->params = $params;
		$search_view->renderPage = 0;
		C4_AbstractViewLoader::setView($search_view->id,$search_view);

		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','search')));
	}
};
