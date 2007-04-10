<?php
class ChTranslations extends DevblocksTranslationsExtension {
	function __construct($manifest) {
		parent::__construct($manifest);	
	}
	
	function getTmxFile() {
		return dirname(__FILE__) . '/strings.xml';
	}
};

class ChTicketsPage extends CerberusPageExtension {
	private $tag_chooser_cfg;
	
	function __construct($manifest) {
		parent::__construct($manifest);

		// Store a Cloud Configuration for the chooser
		$this->tag_chooser_cfg = new CloudGlueConfiguration();
		$this->tag_chooser_cfg->indexName = 'tickets';
		$this->tag_chooser_cfg->extension = 'tickets';
		$this->tag_chooser_cfg->divName = 'tagChooserCloud';
		$this->tag_chooser_cfg->php_click = 'clickChooserTag';
		$this->tag_chooser_cfg->js_click = '';
		$this->tag_chooser_cfg->tagLimit = 50;
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
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		$response = DevblocksPlatform::getHttpResponse();
		@$section = $response->path[1];
		
		switch($section) {
			case 'search':
				$visit = CerberusApplication::getVisit();
				$viewManager = $visit->get(CerberusVisit::KEY_VIEW_MANAGER);
								
				$view = $viewManager->getView(CerberusApplication::VIEW_SEARCH);
				
				// [JAS]: Recover from a bad cached ID.
				if(null == $view) {
					$view = new CerberusDashboardView();
					$view->id = CerberusApplication::VIEW_SEARCH;
					$view->name = "Search Results";
					$view->dashboard_id = 0;
					$view->view_columns = array(
						CerberusSearchFields::TICKET_MASK,
						CerberusSearchFields::TICKET_STATUS,
						CerberusSearchFields::TICKET_PRIORITY,
						CerberusSearchFields::TEAM_NAME,
						CerberusSearchFields::TICKET_LAST_WROTE,
						CerberusSearchFields::TICKET_CREATED_DATE,
						);
					$view->params = array();
					$view->renderLimit = 100;
					$view->renderPage = 0;
					$view->renderSortBy = CerberusSearchFields::TICKET_CREATED_DATE;
					$view->renderSortAsc = 0;
					
					$viewManager->setView(CerberusApplication::VIEW_SEARCH,$view);
				}
				
				$tpl->assign('view', $view);
				$tpl->assign('params', $view->params);
				
				// [TODO]: This should be filterable by a specific view later as well using searchDAO.
				$viewActions = DAO_DashboardViewAction::getList();
				$tpl->assign('viewActions', $viewActions);
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/search/index.tpl.php');
				break;
				
			case 'create':
				$teams = DAO_Workflow::getTeams();
				$tpl->assign('teams', $teams);
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/create/index.tpl.php');
				break;
				
			case 'dashboards':
			default:
				$viewManager = $visit->get(CerberusVisit::KEY_VIEW_MANAGER);
				$request = DevblocksPlatform::getHttpRequest();
				$request_path = $request->path;
				array_shift($request_path); // tickets
				array_shift($request_path); // dashboards
				$mode = array_shift($request_path); // team/my
				
				// Bootloader
				if(!is_null($mode)) {
					if(0 == strcmp("team", $mode)) {
						$team_id = intval(array_shift($request_path));
						$visit->set(CerberusVisit::KEY_DASHBOARD_ID, 't'.$team_id);
						
					} elseif(0 == strcmp('my', $mode)) {
						$visit->set(CerberusVisit::KEY_DASHBOARD_ID, 0);
					}
				}
				
				$dashboards = DAO_Dashboard::getDashboards($visit->getWorker()->id);
				$tpl->assign('dashboards', $dashboards);

				$teams = DAO_Workflow::getTeams();
				$tpl->assign('teams', $teams);
				
				$active_dashboard_id = $visit->get(CerberusVisit::KEY_DASHBOARD_ID, 0);
				$tpl->assign('active_dashboard_id', $active_dashboard_id);
				
				if(empty($active_dashboard_id)) { // my tickets
					// My Tickets
					$myView = $viewManager->getView(CerberusApplication::VIEW_MY_TICKETS);
					
					// [JAS]: Recover from a bad cached ID.
					if(null == $myView) {
						$myView = new CerberusDashboardView();
						$myView->id = CerberusApplication::VIEW_MY_TICKETS;
						$myView->name = "My Ticket Tasks";
						$myView->dashboard_id = 0;
						$myView->view_columns = array(
							CerberusSearchFields::TICKET_MASK,
							CerberusSearchFields::TICKET_STATUS,
							CerberusSearchFields::TICKET_PRIORITY,
							CerberusSearchFields::TEAM_NAME,
							CerberusSearchFields::TICKET_LAST_WROTE,
							CerberusSearchFields::TICKET_UPDATED_DATE,
							);
						$myView->params = array(
							new CerberusSearchCriteria(CerberusSearchFields::TASK_WORKER_ID,'=',$visit->getWorker()->id),
							new CerberusSearchCriteria(CerberusSearchFields::TICKET_STATUS,'in',array(CerberusTicketStatus::OPEN)),
						);
						$myView->renderLimit = 10;
						$myView->renderPage = 0;
						$myView->renderSortBy = CerberusSearchFields::TICKET_UPDATED_DATE;
						$myView->renderSortAsc = 0;
						
						$viewManager->setView(CerberusApplication::VIEW_MY_TICKETS,$myView);
					}
					$views = array($myView->id => $myView);
					$tpl->assign('views', $views);

					$view_actions = CerberusApplication::getDashboardGlobalActions();
					$tpl->assign('viewActions', $view_actions);
					
//					// Trash Action
//					$fields = array(
//						DAO_DashboardViewAction::$FIELD_NAME => 'Trash',
//						DAO_DashboardViewAction::$FIELD_WORKER_ID => $worker_id,
//						DAO_DashboardViewAction::$FIELD_PARAMS => serialize(array(
//							'status' => CerberusTicketStatus::DELETED
//						))
//					);
//					$trash_action_id = DAO_DashboardViewAction::create($fields);
//
//					// Spam Action
//					// [TODO] Look up the spam mailbox id
//					$fields = array(
//						DAO_DashboardViewAction::$FIELD_NAME => 'Report Spam',
//						DAO_DashboardViewAction::$FIELD_WORKER_ID => $worker_id,
//						DAO_DashboardViewAction::$FIELD_PARAMS => serialize(array(
//							'status' => CerberusTicketStatus::DELETED,
//							'spam' => CerberusTicketSpamTraining::SPAM
//						))
//					);
//					$spam_action_id = DAO_DashboardViewAction::create($fields);
//
					
				} elseif(is_numeric($active_dashboard_id)) { // custom dashboards
					$activeDashboard = $dashboards[$active_dashboard_id];
					
					// [JAS]: [TODO] This needs to limit by the selected dashboard
					$views = DAO_Dashboard::getViews(); // getViews($dashboard_id)
					$tpl->assign('views', $views);
					
					// [TODO]: This should be filterable by a specific view later as well using searchDAO.
					$viewActions = DAO_DashboardViewAction::getList();
					$tpl->assign('viewActions', $viewActions);
					
				} else { // virtual dashboards
					// team dashboard
					if(0 == strcmp('t',substr($active_dashboard_id,0,1))) {
						$team_id = intval(substr($active_dashboard_id,1));
						$team = $teams[$team_id];
						
						$categories = DAO_Category::getByTeam($team_id);
						$tpl->assign('categories', $categories);
												
						// ======================================================
						// Team Tickets (All)
						// ======================================================
						$teamView = $viewManager->getView(CerberusApplication::VIEW_TEAM_TICKETS);
						if(null == $teamView) {
							$teamView = new CerberusDashboardView();
							$teamView->id = CerberusApplication::VIEW_TEAM_TICKETS;
							$teamView->name = "Active Team Tickets";
							$teamView->dashboard_id = 0;
							$teamView->view_columns = array(
								CerberusSearchFields::TICKET_MASK,
								CerberusSearchFields::TICKET_STATUS,
								CerberusSearchFields::TICKET_PRIORITY,
								CerberusSearchFields::TICKET_LAST_WROTE,
								CerberusSearchFields::TICKET_UPDATED_DATE,
								CerberusSearchFields::CATEGORY_NAME,
								);
							$teamView->params = array(
							);
							$teamView->renderLimit = 25;
							$teamView->renderPage = 0;
							$teamView->renderSortBy = CerberusSearchFields::TICKET_UPDATED_DATE;
							$teamView->renderSortAsc = 0;
							
							$viewManager->setView(CerberusApplication::VIEW_TEAM_TICKETS,$teamView);
						}
						
						$teamView->name = $team->name . ": Active Tickets";
						$teamView->params = array(
							new CerberusSearchCriteria(CerberusSearchFields::TEAM_ID,'=',$team_id),
							// [TODO] Not assigned...
							// [TODO] In categories... 
							new CerberusSearchCriteria(CerberusSearchFields::TICKET_STATUS,'in',array(CerberusTicketStatus::OPEN)),
						);
						
						// ======================================================
						// Team Tasks
						// ======================================================
						$teamTasksView = $viewManager->getView(CerberusApplication::VIEW_TEAM_TASKS);
						if(null == $teamTasksView) {
							$teamTasksView = new CerberusDashboardView();
							$teamTasksView->id = CerberusApplication::VIEW_TEAM_TASKS;
							$teamTasksView->name = "Ticket Tasks";
							$teamTasksView->dashboard_id = 0;
							$teamTasksView->view_columns = array(
								CerberusSearchFields::TICKET_MASK,
								CerberusSearchFields::TICKET_STATUS,
								CerberusSearchFields::TICKET_PRIORITY,
								CerberusSearchFields::TICKET_LAST_WROTE,
								CerberusSearchFields::TICKET_UPDATED_DATE,
								CerberusSearchFields::TEAM_NAME,
								CerberusSearchFields::CATEGORY_NAME,
								);
							$teamTasksView->params = array(
							);
							$teamTasksView->renderLimit = 25;
							$teamTasksView->renderPage = 0;
							$teamTasksView->renderSortBy = CerberusSearchFields::TICKET_UPDATED_DATE;
							$teamTasksView->renderSortAsc = 0;
							
							$viewManager->setView(CerberusApplication::VIEW_TEAM_TASKS,$teamTasksView);
						}
						
						$teamTasksView->name = $team->name . ": Tasks";
						$teamTasksView->params = array(
							new CerberusSearchCriteria(CerberusSearchFields::TASK_TEAM_ID,'=',$team_id),
							// [TODO] In categories... 
							new CerberusSearchCriteria(CerberusSearchFields::TICKET_STATUS,'in',array(CerberusTicketStatus::OPEN)),
						);
						
						$views = array(
							$teamView->id => $teamView,
							$teamTasksView->id => $teamTasksView
						);
						$tpl->assign('views', $views);
					}
				}
			
				// [TODO] This currently isn't working in translate substitutions
				$translate_tokens = array(
					"whos" => array(1)
				);
				$tpl->assign('translate_tokens', $translate_tokens);
				
				$tpl->cache_lifetime = "0";
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/index.tpl.php');
				break;
		}
		
	}
	
	//**** Local scope
	
	function clickteam() {
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','search')));
	}
	
	// [TODO] Nuke the message_id redundancy here, and such
	function createTicket() {
		//require_once(DEVBLOCKS_PATH . 'libs/pear/mimeDecode.php');

		$settings = CerberusSettings::getInstance();
		$to = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM);
		
		@$team_id = DevblocksPlatform::importGPC($_POST['team_id'],'integer'); 
		@$from = DevblocksPlatform::importGPC($_POST['from'],'string');
		@$subject = DevblocksPlatform::importGPC($_POST['subject'],'string');
		@$content = DevblocksPlatform::importGPC($_POST['content'],'string');
		@$files = $_FILES['attachment'];
		
		$team = DAO_Workflow::getTeam($team_id);

		$mail = sprintf(
			"From: %s\r\n".
			"To: %s\r\n".
			"Subject: %s\r\n".
			"Date: " . date('r') . "\r\n".
			"\r\n".
			"%s\r\n".
			"\r\n",
			$from,
			$to,
			$subject,
			$content
		);

		$params = array();
		$params['include_bodies']	= true;
		$params['decode_bodies']	= true;
		$params['decode_headers']	= true;
		$params['crlf']				= "\r\n";
		$params['input'] = $mail;
		$structure = Mail_mimeDecode::decode($params);
			
		$ticket = CerberusParser::parseMessage($structure); /* @var $ticket CerberusTicket */
		$messages = $ticket->getMessages();
		$message = array_shift($messages); /* @var $ticket CerberusMessage */
		$message_id = $message->id;
		
		// if this message was submitted with attachments, store them in the filestore and link them to the message_id in the db.
		if (is_array($files) && !empty($files)) {
			$settings = CerberusSettings::getInstance();
			$attachmentlocation = $settings->get(CerberusSettings::SAVE_FILE_PATH);
		
			/*
			// [TODO] This needs cleaned up
			if(is_array($files['tmp_name']))
			foreach ($files['tmp_name'] as $idx => $file) {
				copy($files['tmp_name'][$idx],$attachmentlocation.$message_id.$idx);
				DAO_Ticket::createAttachment($message_id, $files['name'][$idx], $message_id.$idx);
			}
			*/
		}
		
		// Routing override
		DAO_Ticket::updateTicket($ticket->id,array(
			DAO_Ticket::TEAM_ID => $team_id
		));

		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('display',$ticket->id)));
	}
	
	function mailbox() {
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;
		@$id = intval($stack[2]); 
		
		$view = DAO_Dashboard::getView(CerberusApplication::VIEW_SEARCH);
		$view->params = array(
			new CerberusSearchCriteria(CerberusSearchFields::MAILBOX_ID,'=', $id),
			new CerberusSearchCriteria(CerberusSearchFields::TICKET_STATUS,'in', array(CerberusTicketStatus::OPEN))
		);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','search')));
	}
	
	function viewSortBy() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$sortBy = DevblocksPlatform::importGPC($_REQUEST['sortBy']);
		
		$view = DAO_Dashboard::getView($id);
		$iSortAsc = intval($view->renderSortAsc);
		
		// [JAS]: If clicking the same header, toggle asc/desc.
		if(0 == strcasecmp($sortBy,$view->renderSortBy)) {
			$iSortAsc = (0 == $iSortAsc) ? 1 : 0;
		} else { // [JAS]: If a new header, start with asc.
			$iSortAsc = 1;
		}
		
		$fields = array(
			'sort_by' => $sortBy,
			'sort_asc' => $iSortAsc
		);
		DAO_Dashboard::updateView($id, $fields);
		
		echo ' ';
	}
	
	function viewPage() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$page = DevblocksPlatform::importGPC(DevblocksPlatform::importGPC($_REQUEST['page']));
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->assign('id',$id);
		
		$fields = array(
			'page' => $page
		);
		DAO_Dashboard::updateView($id,$fields);		
		
		echo ' ';
	}
	
	function viewRefresh() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->assign('id',$id);
		
		$view = DAO_Dashboard::getView($id);
		$tpl->assign('view', $view);
		
		// [TODO]: This should be filterable by a specific view later as well using searchDAO.
		$viewActions = DAO_DashboardViewAction::getList();
		$tpl->assign('viewActions', $viewActions);
		
		if(!empty($view)) {
			$tpl->cache_lifetime = "0";
			$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/ticket_view.tpl.php');
		} else {
			echo " ";
		}
	}
	
	function showTeamPanel() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$teams = DAO_Workflow::getTeams();
		$team_mailbox_counts = array();
		// [TODO] Counts need redone
		foreach ($teams as $team) { /* @var $team CerberusTeam */
			$team->count = 0;
//			$team_mailboxes = $team->getMailboxes(true);
//			foreach ($team_mailboxes as $team_mailbox) { /* @var $team_mailbox CerberusMailbox */
//				$team_mailbox_counts[$team_mailbox->id] = $team_mailbox->count;
//				$team->count += $team_mailbox->count;
//			}
		}
		$tpl->assign('teams', $teams);
		
//		$team_total_count = 0;
//		foreach ($team_mailbox_counts as $idx => $val) {
//			$team_total_count += $val;
//		}
		$tpl->assign('team_total_count', $team_total_count);
		
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/rpc/team_load_panel.tpl.php');
	}
	
	function showTagChooserPanel() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$cg = CloudGlue::getInstance();
		$cloud = $cg->getCloud($this->tag_chooser_cfg);
		
		$tpl->assign('cloud', $cloud);
		
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/rpc/tag_chooser_panel.tpl.php');
	}
	
	function changeDashboard() {
		$dashboard_id = DevblocksPlatform::importGPC($_POST['dashboard_id'], 'string', '0');
		
		$visit = DevblocksPlatform::getSessionService()->getVisit();
		$visit->set(CerberusVisit::KEY_DASHBOARD_ID, $dashboard_id);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','dashboards')));
	}
	
	function clickChooserTag() {
		$tag_id = DevblocksPlatform::importGPC($_REQUEST['tag']);
		$remove = intval($_REQUEST['remove']);
	
		$cg = DevblocksPlatform::getCloudGlueService();
		$tag = $cg->getTagById($tag_id);

		$tagCloud = $cg->getCloud($this->tag_chooser_cfg);
		
		if(empty($remove)) {
			$tagCloud->addToPath($tag);
		} else {
			$tagCloud->removeFromPath($tag);
		}
		
		$tagCloud->render();
	}
	
	function showWorkerChooserPanel() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/rpc/worker_chooser_panel.tpl.php');
	}
	
	function showAssignPanel() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$teams = DAO_Workflow::getTeams();
		$team_mailbox_counts = array();
		// [TODO] Counts need redone (plus should never be redundant in here with teamPanel)
		foreach ($teams as $team) { /* @var $team CerberusTeam */
			$team->count = 0;
//			$team_mailboxes = $team->getMailboxes(true);
//			foreach ($team_mailboxes as $team_mailbox) { /* @var $team_mailbox CerberusMailbox */
//				$team_mailbox_counts[$team_mailbox->id] = $team_mailbox->count;
//				$team->count += $team_mailbox->count;
//			}
		}
		$tpl->assign('teams', $teams);
		
//		$team_total_count = 0;
//		foreach ($team_mailbox_counts as $idx => $val) {
//			$team_total_count += $val;
//		}
		$tpl->assign('team_total_count', $team_total_count);
		
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/rpc/assign_panel.tpl.php');
	}
	
	function showViewActions() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->assign('id', $id);
		$tpl->assign('view_id', $view_id);

		// Status
		$statuses = CerberusTicketStatus::getOptions();
		$tpl->assign('statuses', $statuses);
		// Priority
		$priorities = CerberusTicketPriority::getOptions();
		unset($priorities[0]); // don't show 'none'
		$tpl->assign('priorities', $priorities);
		// Spam Training
		$training = CerberusTicketSpamTraining::getOptions();
		$tpl->assign('training', $training);
		// Teams
		$teams = DAO_Workflow::getTeams();
		$tpl->assign('teams', $teams);
		// Categories
		$team_categories = DAO_Category::getTeams();
		$tpl->assign('team_categories', $team_categories);
				
		// Load action object to populate fields
		$action = DAO_DashboardViewAction::get($id);
		$tpl->assign('action', $action);
		
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/rpc/view_actions_panel.tpl.php');
	}
	
	function showTaskPanel() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->assign('id', $id);

		// Ticket
		$ticket = DAO_Ticket::getTicket($ticket_id);
		$tpl->assign('ticket', $ticket);
		
		// Workers
		$workers = DAO_Worker::getList();
		$tpl->assign('workers', $workers);
		
		// Teams
		$teams = DAO_Workflow::getTeams();
		$tpl->assign('teams', $teams);
		
		// Task
		$task = DAO_Task::get($id);
		$tpl->assign('task', $task);
		
		// Task Owners
		$owners = DAO_Task::getOwners(array($id));
		$tpl->assign('owners', $owners[$id]);
		
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/rpc/task_panel.tpl.php');
	}
	
	function saveTaskPanel() {
		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer');
		@$ticket_id = DevblocksPlatform::importGPC($_POST['ticket_id'],'integer');
		@$title = DevblocksPlatform::importGPC($_POST['title'],'string');
		@$due_date = DevblocksPlatform::importGPC($_POST['due_date'],'string');
		@$completed = DevblocksPlatform::importGPC($_POST['completed'],'integer');
		@$content = DevblocksPlatform::importGPC($_POST['content'],'string');
		@$team_ids = DevblocksPlatform::importGPC($_POST['team_ids'],'array');
		@$worker_ids = DevblocksPlatform::importGPC($_POST['worker_ids'],'array');
		@$delete = DevblocksPlatform::importGPC($_POST['delete'],'integer');

		if(!empty($delete)) {
			DAO_Task::delete($id);
			
		} else {
			if(empty($due_date))
				$due_date = "Today";
			
			$fields = array(
				DAO_Task::TICKET_ID => $ticket_id,
				DAO_Task::TITLE => $title,
				DAO_Task::DUE_DATE => strtotime($due_date),
				DAO_Task::COMPLETED => $completed,
				DAO_Task::CONTENT => $content
			);
	
			if(empty($id)) { // new
				$id = DAO_Task::create($fields);
			} else {
				DAO_Task::update($id, $fields);
			}
			
			// Reassign Owners
			DAO_Task::setOwners($id, $team_ids, $worker_ids, true);
		}
	}
	
	function saveViewActionPanel() {
		@$action_id = DevblocksPlatform::importGPC($_POST['action_id']);
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id']);
		@$title = DevblocksPlatform::importGPC($_POST['title']);
		@$status = DevblocksPlatform::importGPC($_POST['status']);
		@$priority = DevblocksPlatform::importGPC($_POST['priority']);
		@$spam = DevblocksPlatform::importGPC($_POST['spam']);
		@$team = DevblocksPlatform::importGPC($_POST['team'],'string');
		
		$params = array();			
		
		if(!is_null($status))
			$params['status'] = $status;
		if(!is_null($priority))
			$params['priority'] = $priority;
		if(!is_null($spam))
			$params['spam'] = $spam;
		if(!is_null($team))
			$params['team'] = $team;

		$fields = array(
			DAO_DashboardViewAction::$FIELD_NAME => $title,
			DAO_DashboardViewAction::$FIELD_VIEW_ID => 0,
			DAO_DashboardViewAction::$FIELD_WORKER_ID => 1, // [TODO] Should be real
			DAO_DashboardViewAction::$FIELD_PARAMS => serialize($params)
		);
			
		if(empty($action_id)) {
			$action_id = DAO_DashboardViewAction::create($fields);
		} else {
			// [TODO]: Security check that the editor was the author of the original action.
			DAO_DashboardViewAction::update($action_id, $fields);  
		}
		
		echo ' ';
	}
	
	function runAction() {
		@$id = DevblocksPlatform::importGPC($_POST['id'],'string');
		@$action_id = DevblocksPlatform::importGPC($_POST['action_id'],'integer');
		@$ticket_ids = DevblocksPlatform::importGPC($_POST['ticket_id'],'array');
		
		if(empty($action_id) || empty($ticket_ids))
			return;
		
		$action = DAO_DashboardViewAction::get($action_id);
		if(empty($action)) return;
		
		$tickets = DAO_Ticket::getTickets($ticket_ids);
		if(empty($tickets)) return;
		
		// Run the action components
		$action->run($tickets);
		
		echo ' ';
	}
	
	function customize() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'string');

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->assign('id',$id);

		$view = DAO_Dashboard::getView($id);
		$tpl->assign('view',$view);
		
		$optColumns = CerberusApplication::getDashboardViewColumns();
		$tpl->assign('optColumns',$optColumns);
		
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/rpc/customize_view.tpl.php');
	}
	
	function saveCustomize() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'string');
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string');
		@$num_rows = DevblocksPlatform::importGPC($_REQUEST['num_rows'],'integer');
		@$columns = DevblocksPlatform::importGPC($_REQUEST['columns'],'array');
		@$delete = DevblocksPlatform::importGPC($_REQUEST['delete'],'integer');
		
		if(!empty($delete)) {
			DAO_Dashboard::deleteView($id);
			
		} else {
			// [JAS]: Clear any empty columns
			if(is_array($columns))
			foreach($columns as $k => $v) {
				if(empty($v))
					unset($columns[$k]);
			}

			$fields = array(
				'name' => $name,
				'view_columns' => serialize($columns),
				'num_rows' => $num_rows,
				'page' => 0 // reset paging
			);
			DAO_Dashboard::updateView($id,$fields);
		}

		echo ' ';
	}
	
	function searchview() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		$view = DAO_Dashboard::getView($id);

		$search_view = DAO_Dashboard::getView(CerberusApplication::VIEW_SEARCH);
		$fields = array(
			'params' => serialize($view->params)
		);
		DAO_Dashboard::updateView($search_view->id, $fields);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','search')));
	}
	
	function addView() {
		// [JAS]: [TODO] Use a real dashboard ID here.
		$view_id = DAO_Dashboard::createView('New Ticket List', 1, 10);
		
		$fields = array(
			'view_columns' => serialize(array(
				CerberusSearchFields::TICKET_MASK,
				CerberusSearchFields::TICKET_STATUS,
				CerberusSearchFields::TICKET_PRIORITY,
				CerberusSearchFields::TICKET_LAST_WROTE,
				CerberusSearchFields::TICKET_CREATED_DATE,
				CerberusSearchFields::TEAM_NAME,
			))
		);
		DAO_Dashboard::updateView($view_id,$fields);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','dashboards')));
	}
	
	function showHistoryPanel() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->cache_lifetime = "0";
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/history_panel.tpl.php');
	}
	
	function showContactPanel() {
		@$sAddress = DevblocksPlatform::importGPC($_REQUEST['address']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->cache_lifetime = "0";
		
		$address_id = DAO_Contact::lookupAddress($sAddress, false);
		$address = DAO_Contact::getAddress($address_id);
		
		$tpl->assign('address', $address);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/contact_panel.tpl.php');
	}
	
	// [JAS]: Search Functions =================================================
	
	function getCriteria() {
		@$field = DevblocksPlatform::importGPC($_REQUEST['field']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->cache_lifetime = "0";
		
		switch($field) {
			case CerberusSearchFields::TICKET_MASK:
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/search/criteria/ticket_mask.tpl.php');
				break;
				
			case CerberusSearchFields::TICKET_STATUS:
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/search/criteria/ticket_status.tpl.php');
				break;
				
			case CerberusSearchFields::TICKET_PRIORITY:
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/search/criteria/ticket_priority.tpl.php');
				break;
				
			case CerberusSearchFields::TICKET_SUBJECT:
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/search/criteria/ticket_subject.tpl.php');
				break;
				
			case CerberusSearchFields::REQUESTER_ADDRESS:
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/search/criteria/requester_email.tpl.php');
				break;
				
			case CerberusSearchFields::MESSAGE_CONTENT:
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/search/criteria/message_content.tpl.php');
				break;
				
			case CerberusSearchFields::TEAM_ID:
				$teams = DAO_Workflow::getTeams();
				$tpl->assign('teams', $teams);
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/search/criteria/ticket_team.tpl.php');
				break;
		}
	}
	
	function getCriteriaDialog() {
		@$divName = DevblocksPlatform::importGPC($_REQUEST['divName']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->cache_lifetime = "0";
		
		$tpl->assign('divName',$divName);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/search/rpc/add_criteria.tpl.php');
	}
	
	function addCriteria() {
		$view = DAO_Dashboard::getView(CerberusApplication::VIEW_SEARCH);
		
		$params = $view->params;
		@$field = DevblocksPlatform::importGPC($_REQUEST['field']);

		switch($field) {
			case CerberusSearchFields::TICKET_MASK:
				@$mask = DevblocksPlatform::importGPC($_REQUEST['mask']);
				$params[$field] = new CerberusSearchCriteria($field,'like',$mask);
				break;
			case CerberusSearchFields::TICKET_STATUS:
				@$status = DevblocksPlatform::importGPC($_REQUEST['status']);
				$params[$field] = new CerberusSearchCriteria($field,'in',$status);
				break;
			case CerberusSearchFields::TICKET_PRIORITY:
				@$priority = DevblocksPlatform::importGPC($_REQUEST['priority']);
				$params[$field] = new CerberusSearchCriteria($field,'in',$priority);
				break;
			case CerberusSearchFields::TICKET_SUBJECT:
				@$subject = DevblocksPlatform::importGPC($_REQUEST['subject']);
				$params[$field] = new CerberusSearchCriteria($field,'like',$subject);
				break;
			case CerberusSearchFields::REQUESTER_ADDRESS:
				@$requester = DevblocksPlatform::importGPC($_REQUEST['requester']);
				@$oper = DevblocksPlatform::importGPC($_REQUEST['oper']);
				$params[$field] = new CerberusSearchCriteria($field,$oper,$requester);
				break;
			case CerberusSearchFields::MESSAGE_CONTENT:
				@$requester = DevblocksPlatform::importGPC($_REQUEST['content']);
				$params[$field] = new CerberusSearchCriteria($field,'like',$requester);
				break;
			case CerberusSearchFields::TEAM_ID:
				@$team_ids = DevblocksPlatform::importGPC($_REQUEST['team_id'],'array');
				$params[$field] = new CerberusSearchCriteria($field,'in',$team_ids);
				break;
		}
		
		$fields = array(
			'params' => serialize($params)
		);
		DAO_Dashboard::updateView(CerberusApplication::VIEW_SEARCH, $fields);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','search')));
	}
	
	function removeCriteria() {
		$view = DAO_Dashboard::getView(CerberusApplication::VIEW_SEARCH);

		@$params = $view->params;
		
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;
		
		@$field = $stack[2];		

		if(isset($params[$field]))
			unset($params[$field]);
			
		$fields = array(
			'params' => serialize($params)
		);
		DAO_Dashboard::updateView(CerberusApplication::VIEW_SEARCH, $fields);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','search')));
	}
	
	function resetCriteria() {
		DAO_Dashboard::updateView(CerberusApplication::VIEW_SEARCH, array(
			'params' => serialize(array())
		));
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','search')));
	}
	
	function getLoadSearch() {
		@$divName = DevblocksPlatform::importGPC($_REQUEST['divName']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->cache_lifetime = "0";
		
		$tpl->assign('divName',$divName);
		
		$searches = DAO_Search::getSavedSearches(1); /* @var $searches CerberusDashboardView[] */
		$tpl->assign('searches', $searches);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/search/rpc/load_search.tpl.php');
	}
	
	function loadSearch() {
		@$search_id = DevblocksPlatform::importGPC($_REQUEST['search_id']);
		
		$view = DAO_Dashboard::getView($search_id);
		
		// [TODO] Load the saved search into the view
		DAO_Dashboard::updateView(CerberusApplication::VIEW_SEARCH, array(
			'name' => $view->name,
			'params' => serialize($view->params)
		));
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','search')));
	}
	
	function getSaveSearch() {
		@$divName = DevblocksPlatform::importGPC($_REQUEST['divName']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->cache_lifetime = "0";

		$tpl->assign('divName',$divName);
		
		$views = DAO_Dashboard::getViews(0);
		$tpl->assign('views', $views);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/search/rpc/save_search.tpl.php');
	}
	
	function saveSearch() {
//		@$search_id = $_SESSION['search_id'];
		$view = DAO_Dashboard::getView(CerberusApplication::VIEW_SEARCH);

		@$params = $view->params;
		@$columns = $view->view_columns;
		@$save_as = DevblocksPlatform::importGPC($_REQUEST['save_as']);

		if($save_as=='view') {
			@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);
			
			$fields = array(
				'params' => serialize($params)
			);
			DAO_Dashboard::updateView($view_id,$fields);
			echo "Saved as view!";
			
		} else { // named search
			@$name = DevblocksPlatform::importGPC($_REQUEST['name']);
			
			$view_id = DAO_Dashboard::createView($name, 0, 50, 't_created_date', 0, 'S');
			$fields = array(
				'view_columns' => serialize($columns),
				'params' => serialize($params),
				'sort_by' => $view->renderSortBy,
				'sort_asc' => $view->renderSortAsc,
				'num_rows' => $view->renderLimit
			);
			DAO_Dashboard::updateView($view_id, $fields);
			
			echo "Saved search!";
		}
	}
	
	function deleteSearch() {
		@$search_id = $_SESSION['search_id'];
		
		DAO_Dashboard::deleteView($search_id);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','search')));
	}
	
};

class ChConfigurationPage extends CerberusPageExtension  {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function isVisible() {
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		$command = array_shift($stack);
		
		switch($stack[0]) {
			case 'general':
				$settings = CerberusSettings::getInstance();
				$attachmentlocation = $settings->get(CerberusSettings::SAVE_FILE_PATH);
				$tpl->assign('attachmentlocation', $attachmentlocation);
		
				$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/general/index.tpl.php');				
				break;
				
			case 'mail':
				$routing = DAO_Mail::getMailboxRouting();
				$tpl->assign('routing', $routing);
		
				$pop3_accounts = DAO_Mail::getPop3Accounts();
				$tpl->assign('pop3_accounts', $pop3_accounts);
				
				$teams = DAO_Workflow::getTeams();
				$tpl->assign('teams', $teams);
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/mail/index.tpl.php');				
				break;
				
			case 'workflow':
				$workers = DAO_Worker::getList();
				$tpl->assign('workers', $workers);
				
				$teams = DAO_Workflow::getTeams();
				$tpl->assign('teams', $teams);
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/workflow/index.tpl.php');				
				break;
				
			case 'maintenance':
				$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/maintenance/index.tpl.php');
				break;
				
			case 'extensions':
				$plugins = DevblocksPlatform::getPluginRegistry();
				unset($plugins['cerberusweb.core']);
				$tpl->assign('plugins', $plugins);
				
				$points = DevblocksPlatform::getExtensionPoints();
				$tpl->assign('points', $points);
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/extensions/index.tpl.php');				
				break;
				
			default:
				$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/index.tpl.php');
				break;
		} // end switch
		
	}
	
	// Ajax
	function getWorker() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$worker = DAO_Worker::getAgent($id);
		$tpl->assign('worker', $worker);
		
		$teams = DAO_Workflow::getTeams();
		$tpl->assign('teams', $teams);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/workflow/edit_worker.tpl.php');
	}
	
	// Post
	function saveWorker() {
		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer');
		@$first_name = DevblocksPlatform::importGPC($_POST['first_name'],'string');
		@$last_name = DevblocksPlatform::importGPC($_POST['last_name'],'string');
		@$title = DevblocksPlatform::importGPC($_POST['title'],'string');
		@$primary_email = DevblocksPlatform::importGPC($_POST['primary_email'],'string');
		@$email = DevblocksPlatform::importGPC($_POST['email'],'string');
		@$password = DevblocksPlatform::importGPC($_POST['password'],'string');
		@$is_superuser = DevblocksPlatform::importGPC($_POST['is_superuser'],'integer');
		@$team_id = DevblocksPlatform::importGPC($_POST['team_id'],'array');
		@$delete = DevblocksPlatform::importGPC($_POST['delete'],'integer');
		
		// [TODO] The superuser set bit here needs to be protected by ACL
		
		if(empty($name)) $name = "No Name";
		
		if(!empty($id) && !empty($delete)) {
			DAO_Worker::deleteAgent($id);
			
		} elseif(!empty($id)) {
			$fields = array(
				DAO_Worker::FIRST_NAME => $first_name,
				DAO_Worker::LAST_NAME => $last_name,
				DAO_Worker::TITLE => $title,
				DAO_Worker::EMAIL => $email,
				DAO_Worker::IS_SUPERUSER => $is_superuser,
			);
			
			// if we're resetting the password
			if(!empty($password)) {
				$fields[DAO_Worker::PASSWORD] = md5($password);
			}
			
			DAO_Worker::updateAgent($id, $fields);
			DAO_Worker::setAgentTeams($id, $team_id);
			
		} else {
			// Don't dupe.
			if(null == DAO_Worker::lookupAgentEmail($email)) {
				// [TODO] This doesn't fill in all the fields (title, first/last, superuser)
				$id = DAO_Worker::create($email, $password);
				DAO_Worker::setAgentTeams($id, $team_id);
			}
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','workflow')));
	}
	
	// Ajax
	function getTeam() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$team = DAO_Workflow::getTeam($id);
		$tpl->assign('team', $team);
		
		$workers = DAO_Worker::getList();
		$tpl->assign('workers', $workers);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/workflow/edit_team.tpl.php');
	}
	
	// Post
	function saveTeam() {
		@$id = DevblocksPlatform::importGPC($_POST['id']);
		@$name = DevblocksPlatform::importGPC($_POST['name']);
//		@$mailbox_id = DevblocksPlatform::importGPC($_POST['mailbox_id']);
		@$agent_id = DevblocksPlatform::importGPC($_POST['agent_id'],'array');
		@$delete = DevblocksPlatform::importGPC($_POST['delete']);
		
		if(empty($name)) $name = "No Name";
		
		if(!empty($id) && !empty($delete)) {
			DAO_Workflow::deleteTeam($id);
			
		} elseif(!empty($id)) {
			$fields = array(
				'name' => $name
			);
			DAO_Workflow::updateTeam($id, $fields);
//			DAO_Workflow::setTeamMailboxes($id, $mailbox_id);
			DAO_Workflow::setTeamWorkers($id, $agent_id);
			
		} else {
			$id = DAO_Workflow::createTeam($name);
//			DAO_Workflow::setTeamMailboxes($id, $mailbox_id);
			DAO_Workflow::setTeamWorkers($id, $agent_id);
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','workflow')));
	}
	
	// Ajax
//	function getMailbox() {
//		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
//
//		$tpl = DevblocksPlatform::getTemplateService();
//		$tpl->cache_lifetime = "0";
//		$tpl->assign('path', dirname(__FILE__) . '/templates/');
//
//		$mailbox = DAO_Mail::getMailbox($id);
//		$tpl->assign('mailbox', $mailbox);
//		
//		$teams = DAO_Workflow::getTeams();
//		$tpl->assign('teams', $teams);
//		
//		$reply_address = DAO_Contact::getAddress($mailbox->reply_address_id);
//		$tpl->assign('reply_address', $reply_address);
//		
//		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/mail/edit_mailbox.tpl.php');
//	}
//	
//	// Post
//	function saveMailbox() {
//		@$id = DevblocksPlatform::importGPC($_POST['id']);
//		@$name = DevblocksPlatform::importGPC($_POST['name']);
//		@$reply_as = DevblocksPlatform::importGPC($_POST['reply_as']);
//		@$team_id = DevblocksPlatform::importGPC($_POST['team_id']);
//		@$delete = DevblocksPlatform::importGPC($_POST['delete']);
//		
//		if(empty($name)) $name = "No Name";
//		
//		if(!empty($id) && !empty($delete)) {
//			DAO_Mail::deleteMailbox($id);
//			
//		} elseif(!empty($id)) {
//			$reply_id = DAO_Contact::lookupAddress($reply_as, true);
//
//			$fields = array(
//				'name' => $name,
//				'reply_address_id' => $reply_id
//			);
//			DAO_Mail::updateMailbox($id, $fields);
//			DAO_Mail::setMailboxTeams($id, $team_id);
//			
//		} else {
//			$reply_id = DAO_Contact::lookupAddress($reply_as, true);
//			$id = DAO_Mail::createMailbox($name,$reply_id);
//			DAO_Mail::setMailboxTeams($id, $team_id);
//		}
//		
//		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','mail')));
//	}
	
	// Ajax  (DDH: I don't think this actually gets used anywhere...hm.)
	function getAttachmentLocation() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$settings = CerberusSettings::getInstance();
		$attachmentlocation = $settings->get(CerberusSettings::SAVE_FILE_PATH);
		$tpl->assign('attachmentlocation', $attachmentlocation);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/general/attachments.tpl.php');
	}
	
	// Post
	function saveAttachmentLocation() {
		@$id = DevblocksPlatform::importGPC($_POST['id']);
		@$attachmentlocation = DevblocksPlatform::importGPC($_POST['attachmentlocation']);
		if (!empty($attachmentlocation)
		&&	(	strrpos($attachmentlocation,'/') != strlen($attachmentlocation)
			||	strrpos($attachmentlocation,'\\') != strlen($attachmentlocation)
			)
		)
			$attachmentlocation = $attachmentlocation . '/';
		
		$settings = CerberusSettings::getInstance();
		$settings->set(CerberusSettings::SAVE_FILE_PATH,$attachmentlocation);
				
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','general')));
	}
	
	// Ajax
	function getPop3Account() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$pop3_account = DAO_Mail::getPop3Account($id);
		$tpl->assign('pop3_account', $pop3_account);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/mail/edit_pop3_account.tpl.php');
	}
	
	// Post
	function savePop3Account() {
		@$id = DevblocksPlatform::importGPC($_POST['id']);
		@$nickname = DevblocksPlatform::importGPC($_POST['nickname']);
		@$host = DevblocksPlatform::importGPC($_POST['host']);
		@$username = DevblocksPlatform::importGPC($_POST['username']);
		@$password = DevblocksPlatform::importGPC($_POST['password']);
		@$delete = DevblocksPlatform::importGPC($_POST['delete']);
		
		if(empty($nickname)) $nickname = "No Nickname";
		
		if(!empty($id) && !empty($delete)) {
			DAO_Mail::deletePop3Account($id);
		} elseif(!empty($id)) {
			$fields = array(
				'nickname' => $nickname,
				'host' => $host,
				'username' => $username,
				'password' => $password
			);
			DAO_Mail::updatePop3Account($id, $fields);
		} else {
			$id = DAO_Mail::createPop3Account($nickname,$host,$username,$password);
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','mail')));
	}
	
	// Ajax
	function ajaxGetRouting() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$routing = DAO_Mail::getMailboxRouting();
		$tpl->assign('routing', $routing);

		$teams = DAO_Workflow::getTeams();
		$tpl->assign('teams', $teams);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/mail/mail_routing.tpl.php');
	}
	
	// Form Submit
	function saveRouting() {
		@$positions = DevblocksPlatform::importGPC($_POST['positions'],'array');
		@$route_ids = DevblocksPlatform::importGPC($_POST['route_ids'],'array');
		@$route_team_id = DevblocksPlatform::importGPC($_POST['route_team_id'],'array');
		@$route_pattern = DevblocksPlatform::importGPC($_POST['route_pattern'],'array');
		@$default_team_id = DevblocksPlatform::importGPC($_POST['default_team_id'],'integer');
		@$add_pattern = DevblocksPlatform::importGPC($_POST['add_pattern'],'array');
		@$add_team_id = DevblocksPlatform::importGPC($_POST['add_team_id'],'array');
		@$route_remove = DevblocksPlatform::importGPC($_POST['route_remove'],'array');
		
		// Rule reordering
		if(is_array($route_ids) && is_array($positions)) {
			foreach($route_ids as $idx => $route_id) {
				$pos = $positions[$idx];
				$pattern = $route_pattern[$idx];
				$team_id = $route_team_id[$idx];
				
				if(empty($pattern)) {
					$route_remove[] = $route_id;
					continue;
				}
				
				$fields = array(
					DAO_Mail::ROUTING_POS => $pos,
					DAO_Mail::ROUTING_PATTERN => $pattern,
					DAO_Mail::ROUTING_TEAM_ID => $team_id,
				);
				DAO_Mail::updateMailboxRouting($route_id, $fields);
			}
		}
		
		// Add rules
		if(is_array($add_pattern)) {
			foreach($add_pattern as $k => $v) {
				if(empty($v)) continue;
				$team_id = $add_team_id[$k];
		 		$fields = array(
					DAO_Mail::ROUTING_PATTERN => $v,
					DAO_Mail::ROUTING_TEAM_ID => $team_id,
				);
				$route_id = DAO_Mail::createMailboxRouting($fields);
			}
		}
		
		// Removals
		if(is_array($route_remove)) {
			foreach($route_remove as $remove_id) {
				DAO_Mail::deleteMailboxRouting($remove_id);
			}
		}
		
		// Default team
		$settings = CerberusSettings::getInstance();
		$settings->set(CerberusSettings::DEFAULT_TEAM_ID, $default_team_id);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','mail')));
	}
	
	// Ajax
	function ajaxDeleteRouting() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		DAO_Mail::deleteMailboxRouting($id);
	}
	
	// Ajax
	function getMailRoutingAdd() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$teams = DAO_Workflow::getTeams();
		$tpl->assign('teams', $teams);

		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/mail/mail_routing_add.tpl.php');
	}
	
	// Ajax
//	function getMailboxRoutingDialog() {
//		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
//		
//		$tpl = DevblocksPlatform::getTemplateService();
//		$tpl->cache_lifetime = "0";
//		$tpl->assign('path', dirname(__FILE__) . '/templates/');
//
//		$tpl->assign('id', $id);
//
//
//		$routing = DAO_Mail::getMailboxRouting();
//		$tpl->assign('routing', $routing);
//
//		if(!empty($id)) {
//			@$route = $routing[$id];
//			$tpl->assign('route', $route);
//		}
//		
//		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/mail/edit_mailbox_routing.tpl.php');
//	}
//	
//	// Ajax
//	function saveMailboxRoutingDialog() {
//		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer');
//		@$pattern = DevblocksPlatform::importGPC($_POST['pattern'],'string');
//		@$team_id = DevblocksPlatform::importGPC($_POST['team_id'],'integer');
//		
//		if(empty($id)) {
//			$id = DAO_Mail::createMailboxRouting();
//		}
//		
//		$fields = array(
//			DAO_Mail::ROUTING_PATTERN => $pattern,
//			DAO_Mail::ROUTING_TEAM_ID => $team_id,
//		);
//		DAO_Mail::updateMailboxRouting($id, $fields);
//		
//		// [JAS]: Send the new mailbox name to the server 
//		// [TODO] Necessary?
//		$team = DAO_Workflow::getTeam($team_id);
//		echo $team->name;
//	}
	
	// Ajax
	function refreshPlugins() {
//		if(!ACL_TypeMonkey::hasPriv(ACL_TypeMonkey::SETUP)) return;
		
		DevblocksPlatform::readPlugins();
		DevblocksPlatform::clearCache();
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','extensions')));
	}
	
	function savePlugins() {
//		if(!ACL_TypeMonkey::hasPriv(ACL_TypeMonkey::SETUP)) return;
		
		$plugins_enabled = DevblocksPlatform::importGPC($_REQUEST['plugins_enabled'],'array');
		$pluginStack = DevblocksPlatform::getPluginRegistry();
		
		if(is_array($plugins_enabled))
		foreach($plugins_enabled as $plugin_id) {
			$plugin = $pluginStack[$plugin_id];
			$plugin->setEnabled(true);
			unset($pluginStack[$plugin_id]);
		}

		// [JAS]: Clear unchecked plugins
		foreach($pluginStack as $plugin) {
			// [JAS]: We can't force disable core here [TODO] Improve
			if($plugin->id=='cerberusweb.core') continue;
			$plugin->setEnabled(false);
		}

		DevblocksPlatform::clearCache();
		
		// Run any enabled plugin patches
		// [TODO] Should the platform do this automatically on enable in order?
		$patchMgr = DevblocksPlatform::getPatchService();
		$patches = DevblocksPlatform::getExtensions("devblocks.patch.container");
		
		if(is_array($patches))
		foreach($patches as $patch_manifest) { /* @var $patch_manifest DevblocksExtensionManifest */ 
			 $container = $patch_manifest->createInstance(); /* @var $container DevblocksPatchContainerExtension */
			 $patchMgr->registerPatchContainer($container);
		}
		
		if(!$patchMgr->run()) { // fail
			die("Failed updating plugins."); // [TODO] Make this more graceful
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','extensions')));
	}
	
}

class ChFilesPage extends CerberusPageExtension {
	function __construct($manifest) {
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
	}
	
	/*
	 * Request Overload
	 */
	function handleRequest($request) {
		$stack = $request->path;				// URLS like: /files/10000/plaintext.txt
		array_shift($stack);					// files	
		$file_id = array_shift($stack); 		// 10000
		$file_name = array_shift($stack); 		// plaintext.txt
		
		// [TODO] Do a security check the current user can see the parent ticket (team check)
		if(empty($file_id) || empty($file_name))
			die("File Not Found");
		
		// Set headers
		header("Expires: Mon, 26 Nov 1962 00:00:00 GMT\n");
		header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT\n");
		header("Cache-control: private\n");
		header("Pragma: no-cache\n");
		header("Content-Type: " . ChFilesPage::getMimeType($file_name) . "\n");
		header("Content-transfer-encoding: binary\n"); 
		header("Content-Length: " . ChFilesPage::getFileSize($file_id) . "\n");
		
		echo(ChFilesPage::getFileContents($file_id));

		exit;
	}
	
	private function getMimeType($file_name) {
		$fexp=explode('.',$file_name);
		$ext=$fexp[sizeof($fexp)-1];
		 
		$mimetype = array( 
		    'bmp'=>'image/bmp',
		    'doc'=>'application/msword', 
		    'gif'=>'image/gif',
		    'gz'=>'application/x-gzip-compressed',
		    'htm'=>'text/html', 
		    'html'=>'text/html', 
		    'jpg'=>'image/jpeg', 
		    'mp3'=>'audio/x-mp3',
		    'pdf'=>'application/pdf', 
		    'php'=>'text/plain', 
		    'swf'=>'application/x-shockwave-flash',
		    'tar'=>'application/x-tar',
		    'tgz'=>'application/x-gzip-compressed',
		    'tif'=>'image/tiff',
		    'tiff'=>'image/tiff',
		    'txt'=>'text/plain', 
		    'vsd'=>'application/vnd.visio',
		    'vss'=>'application/vnd.visio',
		    'vst'=>'application/vnd.visio',
		    'vsw'=>'application/vnd.visio',
		    'wav'=>'audio/x-wav',
		    'xls'=>'application/vnd.ms-excel',
		    'xml'=>'text/xml',
		    'zip'=>'application/x-zip-compressed' 
		    ); 
		        
		if(isset($mimetype[strtolower($ext)]))
			return($mimetype[strtolower($ext)]);
		else
			return("application/octet-stream");
	}

	private function getFileSize($file_id) {
		$settings = CerberusSettings::getInstance();
		$file_path = $settings->get(CerberusSettings::SAVE_FILE_PATH);
		if (!empty($file_path))
			return filesize($file_path.$file_id);
	}
	
	private function getFileContents($file_id) {
		$settings = CerberusSettings::getInstance();
		$file_path = $settings->get(CerberusSettings::SAVE_FILE_PATH);
		if (!empty($file_path))
			return file_get_contents($file_path.$file_id,false);
	}
}

class ChCronPage extends CerberusPageExtension {
	function __construct($manifest) {
		parent::__construct($manifest);	
	}
	
	function isVisible() {
		// [TODO] This should restrict by IP rather than session
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}
	
	/*
	 * Request Overload
	 */
	function handleRequest($request) {
		$cron_manifests = DevblocksPlatform::getExtensions('cerberusweb.cron');
		
		if(is_array($cron_manifests))
		foreach($cron_manifests as $manifest) { /* @var $manifest DevblocksExtensionManifest */
			$instance = $manifest->createInstance();

			if($instance)
				$instance->run();
		}
		
		exit;
	}
}

class ChTestsPage extends CerberusPageExtension {
	function __construct($manifest) {
		parent::__construct($manifest);	
	}
	
	function isVisible() {
//		$session = DevblocksPlatform::getSessionService();
//		$visit = $session->getVisit();
//		
//		if(empty($visit)) {
//			return false;
//		} else {
//			return true;
//		}
		return true;
	}
	
	/*
	 * Request Overload
	 */
	function handleRequest($request) {
		// [TODO] Add testing extension point to Cerb/Devblocks
		
//		$cron_manifests = DevblocksPlatform::getExtensions('cerberusweb.cron');
//		
//		if(is_array($cron_manifests))
//		foreach($cron_manifests as $manifest) { /* @var $manifest DevblocksExtensionManifest */
//			$instance = $manifest->createInstance();
//
//			if($instance)
//				$instance->run();
//		}

		require_once 'PHPUnit/Framework.php';
		require_once 'api/CerberusTestListener.class.php';
		
		$suite = new PHPUnit_Framework_TestSuite('Cerberus Helpdesk');
		
		require_once 'api/Application.tests.php';
		$suite->addTestSuite('ApplicationTest');
		$suite->addTestSuite('CerberusBayesTest');
		$suite->addTestSuite('CerberusParserTest');
		
		$result = new PHPUnit_Framework_TestResult;
		$result->addListener(new CerberusTestListener);
		 
		$suite->run($result);
		
		exit;
	}
}

class ChDisplayPage extends CerberusPageExtension {
	function __construct($manifest) {
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
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;

		@$id = $stack[1];
		
		// [JAS]: Mask
		if(!is_numeric($id)) {
			$ticket = DAO_Ticket::getTicketByMask($id);
			$id = $mask_ticket->id;
		} else {
			$ticket = DAO_Ticket::getTicket($id);
		}
		
		$tpl->assign('ticket', $ticket);

		$teams = DAO_Workflow::getTeams();
		$tpl->assign('teams', $teams);
		
		$team_categories = DAO_Category::getTeams();
		$tpl->assign('team_categories', $team_categories);
		
		$display_module_manifests = DevblocksPlatform::getExtensions("cerberusweb.display.module");
		$display_modules = array();
		
		if(is_array($display_module_manifests))
		foreach($display_module_manifests as $dmm) { /* @var $dmm DevblocksExtensionManifest */
			$display_modules[] = $dmm->createInstance(1);
		}
		$tpl->assign('display_modules', $display_modules);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/index.tpl.php');
	}

	function updateProperties() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']); // ticket id
		@$status = DevblocksPlatform::importGPC($_REQUEST['status']);
		@$priority = DevblocksPlatform::importGPC($_REQUEST['priority'],'integer');
		@$subject = DevblocksPlatform::importGPC($_REQUEST['subject']);
		@$training = DevblocksPlatform::importGPC($_REQUEST['training']);
		@$category_code = DevblocksPlatform::importGPC($_REQUEST['category_id'],'string');
		
		// Anti-Spam
		if(!empty($training)) {
			if(0 == strcasecmp($training,'N')) {
				CerberusBayes::markTicketAsNotSpam($id); }
			else { 
				CerberusBayes::markTicketAsSpam($id); }
		}

		$categories = DAO_Category::getList();
		
		// Team/Category
		list($team_id,$category_id) = CerberusApplication::translateTeamCategoryCode($category_code);
		
		// Properties
		$properties = array(
			DAO_Ticket::STATUS => $status,
			DAO_Ticket::PRIORITY => intval($priority),
			DAO_Ticket::SUBJECT => $subject,
			DAO_Ticket::UPDATED_DATE => gmmktime(),
			DAO_Ticket::TEAM_ID => $team_id,
			DAO_Ticket::CATEGORY_ID => $category_id,
		);
		DAO_Ticket::updateTicket($id, $properties);

		// Tags
		if(!empty($tags)) {
			$tags = CerberusApplication::parseCsvString($tags);
			DAO_CloudGlue::applyTags($tags, $id, CerberusApplication::INDEX_TICKETS);
		}
		
		// Workers
		if(!empty($workers)) {
			$tokens = CerberusApplication::parseCsvString($workers);
			
			foreach($tokens as $token) {
				$agent_id = DAO_Worker::lookupAgentEmail($token);
				if(empty($agent_id)) continue;
				DAO_Ticket::flagTicket($id, $agent_id);
			}
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('display',$id)));
	}
	
	function reply()	{ ChDisplayPage::loadMessageTemplate(CerberusMessageType::EMAIL); }
	function forward()	{ ChDisplayPage::loadMessageTemplate(CerberusMessageType::FORWARD); }
	function comment()	{ ChDisplayPage::loadMessageTemplate(CerberusMessageType::COMMENT); }
	
	function loadMessageTemplate($type) {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->assign('id',$id);
		
		$message = DAO_Ticket::getMessage($id);
		$tpl->assign('message',$message);
		
		$ticket = DAO_Ticket::getTicket($message->ticket_id);
		$tpl->assign('ticket',$ticket);
		
		$tpl->cache_lifetime = "0";
		
		switch ($type) {
			case CerberusMessageType::FORWARD :
				$tpl->display('file:' . dirname(__FILE__) . '/templates/display/rpc/forward.tpl.php');
				break;
			case CerberusMessageType::EMAIL :
				$tpl->display('file:' . dirname(__FILE__) . '/templates/display/rpc/reply.tpl.php');
				break;
			case CerberusMessageType::COMMENT :
				$tpl->display('file:' . dirname(__FILE__) . '/templates/display/rpc/comment.tpl.php');
				break;
		}
	}
	
	function sendReply()	{ CerberusApplication::sendMessage(CerberusMessageType::EMAIL); }
	function sendForward()	{ CerberusApplication::sendMessage(CerberusMessageType::FORWARD); }
	function sendComment()	{ CerberusApplication::sendMessage(CerberusMessageType::COMMENT); }
	
	function refreshRequesters() {
		$tpl = DevblocksPlatform::getTemplateService();
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']); // ticket id
		
		$ticket = DAO_Ticket::getTicket($id);

		$tpl->assign('ticket',$ticket);
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/requesters.tpl.php');
	}

	function saveRequester() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']); // ticket id
		@$add_requester = DevblocksPlatform::importGPC($_POST['add_requester']);
		
		// I'd really like to know why the *$#! this doesn't work.  The if statement works fine atomically...
		// [JAS]: [TODO] Just convert it to the Zend_Validate stuff.
//		require_once(APP_PATH . '/libs/pear/Mail/RFC822.php');
//		if (false === Mail_RFC822::isValidInetAddress($add_requester)) {
//			return $add_requester . DevblocksTranslationManager::say('ticket.requester.invalid');
//		}
		
		$address_id = DAO_Contact::lookupAddress($add_requester, true);
		DAO_Ticket::createRequester($address_id, $id);
		
		echo ' ';
	}
	
	function reloadTasks() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['ticket_id']); // ticket id
		
//		$tpl = DevblocksPlatform::getTemplateService();
//		$tpl->cache_lifetime = "0";
//		$tpl->assign('path', dirname(__FILE__) . '/templates/');

//		$response = DevblocksPlatform::getHttpResponse();
//		$stack = $response->path;

		$manifest = DevblocksPlatform::getExtension('core.display.module.tasks');
		$ext = $manifest->createInstance(); /* @var $ext ChDisplayTicketTasks */
		
		$ticket = DAO_Ticket::getTicket($id);
		
		$ext->renderBody($ticket);
	}
	
};

class ChSignInPage extends CerberusPageExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function isVisible() {
		// check login
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
//		if(empty($visit)) {
//			return true;
//		} else {
//			return false;
//		}

		return true;
	}
	
	function show() {
//		echo "You clicked: " . __CLASS__ . "->" . __FUNCTION__ . "!<br>";
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('login')));
	}
	
	function render() {
		$manifest = DevblocksPlatform::getExtension('login.default');
		$inst = $manifest->createInstance(1); /* @var $inst CerberusLoginPageExtension */
		$inst->renderLoginForm();
	}
	
	function signout() {
//		echo "Sign out: " . __CLASS__ . "->" . __FUNCTION__ . "!<br>";
		$session = DevblocksPlatform::getSessionService();
		$session->clear();
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('login')));
	}
};

class ChPreferencesPage extends CerberusPageExtension {
	function __construct($manifest) {
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
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->cache_lifetime = "0";

		$tpl->display('file:' . dirname(__FILE__) . '/templates/preferences/index.tpl.php');
	}
}

class ChDisplayTicketHistory extends CerberusDisplayPageExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function render($ticket) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/ticket_history.tpl.php');
	}
	
	function renderBody($ticket) {
		/* @var $ticket CerberusTicket */
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		
		$requesters = $ticket->getRequesters();
		
		$history_tickets = DAO_Search::searchTickets(
			array(
				new CerberusSearchCriteria(CerberusSearchFields::REQUESTER_ID,'in',array_keys($requesters))
			),
			10,
			0,
			CerberusSearchFields::TICKET_CREATED_DATE,
			0
		);
		$tpl->assign('history_tickets', $history_tickets[0]);
		$tpl->assign('history_count', $history_tickets[1]);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/history/index.tpl.php');
	}
}

class ChDisplayTicketLog extends CerberusDisplayPageExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function render($ticket) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/ticket_log.tpl.php');
	}
	
	function renderBody($ticket) {
		echo "Ticket log content goes here!";
	}
}

class ChDisplayTicketTasks extends CerberusDisplayPageExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function render($ticket) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/ticket_tasks.tpl.php');
	}
	
	/**
	 * @param CerberusTicket $ticket
	 */
	function renderBody(CerberusTicket $ticket) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
//		
		$tasks = DAO_Task::getByTicket($ticket->id);
		$tpl->assign('tasks', $tasks);
		
		$task_owners = DAO_Task::getOwners(array_keys($tasks));
		$tpl->assign('task_owners', $task_owners);
		
		$tpl->assign('ticket', $ticket);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/tasks/index.tpl.php');
	}
	
//	function getTagDialog() {
//		@$tag_id = intval(DevblocksPlatform::importGPC($_REQUEST['id']));
//		@$ticket_id = intval(DevblocksPlatform::importGPC($_REQUEST['ticket_id']));
//		
//		$tpl = DevblocksPlatform::getTemplateService();
//		$tpl->caching = 0;
//		$tpl->cache_lifetime = 0;
//		
//		$tag = DAO_Workflow::getTag($tag_id);
//		$tpl->assign('tag', $tag);
//		
//		$tpl->assign('ticket_id', $ticket_id);
//		
//		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/workflow/tag_dialog.tpl.php');
//	}
//	
//	function saveTagDialog() {
//		@$id = intval(DevblocksPlatform::importGPC($_REQUEST['id']));
//		@$ticket_id = intval(DevblocksPlatform::importGPC($_REQUEST['ticket_id']));
//		@$str_terms = DevblocksPlatform::importGPC($_REQUEST['terms']);
//		@$untag = intval(DevblocksPlatform::importGPC($_REQUEST['untag']));
//		
//		if(!empty($untag) && !empty($ticket_id)) {
////			DAO_Ticket::untagTicket($ticket_id, $id);
//			// [TODO]: CloudGlue needs to delete specific tags on content ids here.
//		} else {
//			// Save Changes
//			
//			// Terms
//			// [TODO] Terms need to be added to CloudGlue
////			$terms = preg_split("/[\r\n]/", $str_terms);
////			
////			if(!empty($terms)) {
////				DAO_Workflow::setTagTerms($id, $terms);
////			}
//		}
//		
//		echo ' ';
//	}
//	
//	function autoTag() {
//		@$q = DevblocksPlatform::importGPC($_REQUEST['q']);
//		header("Content-Type: text/plain");
//		
//		$tags = DAO_Workflow::searchTags($q, 10);
//		
//		if(is_array($tags))
//		foreach($tags as $tag) {
//			echo $tag->name,"\t",$tag->id,"\n";
//		}
//	}
//	
//	function autoWorker() {
//		@$q = DevblocksPlatform::importGPC($_REQUEST['q']);
//		header("Content-Type: text/plain");
//		
//		$workers = DAO_Worker::searchAgents($q, 10);
//		
//		if(is_array($workers))
//		foreach($workers as $worker) {
//			echo $worker->login,"\t",$worker->id,"\n";
//		}
//	}
//	
//	function autoAddress() {
//		@$q = DevblocksPlatform::importGPC($_REQUEST['q']);
//		header("Content-Type: text/plain");
//		
//		$addresses = DAO_Mail::searchAddresses($q, 10);
//		
//		if(is_array($addresses))
//		foreach($addresses as $address) {
//			echo $address->email,"\t",$address->id,"\n";
//		}
//	}
//	
//	
//	function getAgentDialog() {
//		@$id = intval(DevblocksPlatform::importGPC($_REQUEST['id']));
//		@$ticket_id = intval(DevblocksPlatform::importGPC($_REQUEST['ticket_id']));
//		
//		$tpl = DevblocksPlatform::getTemplateService();
//		$tpl->caching = 0;
//		$tpl->cache_lifetime = 0;
//		
//		$agent = DAO_Worker::getAgent($id);
//		$tpl->assign('agent', $agent);
//		
//		$tpl->assign('ticket_id', $ticket_id);
//		
//		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/workflow/agent_dialog.tpl.php');
//	}
//	
//	function saveAgentDialog() {
//		@$id = intval(DevblocksPlatform::importGPC($_REQUEST['id']));
//		@$ticket_id = intval(DevblocksPlatform::importGPC($_REQUEST['ticket_id']));
//		@$unassign = intval(DevblocksPlatform::importGPC($_REQUEST['unassign']));
//		
//		if(!empty($unassign) && !empty($ticket_id)) {
//			DAO_Ticket::unflagTicket($ticket_id, $id);
//			DAO_Ticket::unsuggestTicket($ticket_id, $id);
//		} else {
//			// save changes
//		}
//		
//		echo ' ';
//	}
	
//	function showApplyTags() {
//		@$id = intval(DevblocksPlatform::importGPC($_REQUEST['id']));
//		
//		$session = DevblocksPlatform::getSessionService();
//		$visit = $session->getVisit();
//		
//		$tpl = DevblocksPlatform::getTemplateService();
//		$tpl->caching = 0;
//		$tpl->cache_lifetime = 0;
//		
//		$tpl->assign('moduleLabel', $this->manifest->id);
//		
//		$ticket = DAO_Ticket::getTicket($id);
//		$tpl->assign('ticket', $ticket);
//		
//		$favoriteTags = DAO_Worker::getFavoriteTags($visit->worker->id);
//		$tpl->assign('favoriteTags', $favoriteTags);
//		
//		$suggestedTags = DAO_Workflow::getTags();
//		$tpl->assign('suggestedTags', $suggestedTags);
//		
//		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/workflow/add_tags.tpl.php');
//	}
	
	/*
	function showFavTags() {
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->caching = 0;
		$tpl->cache_lifetime = 0;

		$favoriteTags = DAO_Worker::getFavoriteTags($visit->worker->id);
		$tpl->assign('favoriteTags', $favoriteTags);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/workflow/add_favtags.tpl.php');
	}

	function saveFavoriteTags() {
		@$favTagEntry = DevblocksPlatform::importGPC($_POST['favTagEntry']);
		
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		DAO_Worker::setFavoriteTags($visit->worker->id, $favTagEntry);
		
		echo ' ';
	}
	
	function showFavWorkers() {
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->caching = 0;
		$tpl->cache_lifetime = 0;

		$tpl->assign('moduleLabel', $this->manifest->id);

		$agents = DAO_Worker::getList();
		$tpl->assign('agents', $agents);
		
		$favoriteWorkers = DAO_Worker::getFavoriteWorkers($visit->worker->id);
		$tpl->assign('favoriteWorkers', $favoriteWorkers);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/workflow/add_favworkers.tpl.php');
	}

	function saveFavoriteWorkers() {
		@$favWorkerEntry = DevblocksPlatform::importGPC($_POST['favWorkerEntry']);
		
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		DAO_Worker::setFavoriteWorkers($visit->worker->id, $favWorkerEntry);
		
		echo ' ';
	}
	
	function showFlagAgents() {
		@$id = intval(DevblocksPlatform::importGPC($_REQUEST['id']));

		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->caching = 0;
		$tpl->cache_lifetime = 0;

		$tpl->assign('moduleLabel', $this->manifest->id);

		$ticket = DAO_Ticket::getTicket($id);
		$tpl->assign('ticket', $ticket);
		
		$favoriteWorkers = DAO_Worker::getFavoriteWorkers($visit->worker->id);
		$tpl->assign('favoriteWorkers', $favoriteWorkers);
		
		$agents = DAO_Worker::getList();
		$tpl->assign('agents', $agents);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/workflow/add_flags.tpl.php');
	}

	function showSuggestAgents() {
		@$id = intval(DevblocksPlatform::importGPC($_REQUEST['id']));
		
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->caching = 0;
		$tpl->cache_lifetime = 0;

		$tpl->assign('moduleLabel', $this->manifest->id);

		$ticket = DAO_Ticket::getTicket($id);
		$tpl->assign('ticket', $ticket);
		
		$favoriteWorkers = DAO_Worker::getFavoriteWorkers($visit->worker->id);
		$tpl->assign('favoriteWorkers', $favoriteWorkers);
		
		$agents = DAO_Worker::getList();
		$tpl->assign('agents', $agents);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/workflow/add_suggestions.tpl.php');
	}

	function suggestAgents() {
		@$id = intval(DevblocksPlatform::importGPC($_POST['id']));
		@$agentEntry = DevblocksPlatform::importGPC($_POST['workerEntry']);
		
		$tokens = CerberusApplication::parseCsvString($agentEntry);
		
		foreach($tokens as $token) {
			$agent_id = DAO_Worker::lookupAgentEmail($token);
			if(empty($agent_id)) continue;
			DAO_Ticket::suggestTicket($id, $agent_id);
		}
		
		echo ' ';
	}
	*/
	
}

class ChDisplayTicketFields extends CerberusDisplayPageExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function render($ticket) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/ticket_fields.tpl.php');
	}
	
	function renderBody($ticket) {
		echo "Ticket custom fields content goes here!";
	}
}

class ChDisplayTicketConversation extends CerberusDisplayPageExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function render($ticket) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/ticket_conversation.tpl.php');
	}
}



?>
