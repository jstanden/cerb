<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2007, WebGroup Media LLC
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
 * We've never believed in encoding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your in-box that you probably 
 * haven't had since spammers found you in a game of "E-mail Address 
 * Battleship".  Miss. Miss. You sunk my in-box!
 * 
 * A legitimate license entitles you to support, access to the developer 
 * mailing list, the ability to participate in betas and the warm fuzzy 
 * feeling of feeding a couple obsessed developers who want to help you get 
 * more done than 'the other guy'.
 *
 * - Jeff Standen, Mike Fogg, Brenan Cavish, Darren Sugita, Dan Hildebrandt
 * 		and Joe Geck.
 *   WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */

class ChCorePlugin extends DevblocksPlugin {
	function load(DevblocksPluginManifest $manifest) {
	}
};

class ChPageController extends DevblocksControllerExtension {
    const ID = 'core.controller.page';
    
	function __construct($manifest) {
		parent::__construct($manifest);

		/*
		 * [JAS]: Read in the page extensions from the entire system and register 
		 * the URI shortcuts from their manifests with the router.
		 */
        $router = DevblocksPlatform::getRoutingService();
        $pages = DevblocksPlatform::getExtensions('cerberusweb.page', false);
        
        foreach($pages as $manifest) { /* @var $manifest DevblocksExtensionManifest */
            $uri = $manifest->params['uri'];
            if(empty($uri)) continue;
            $router->addRoute($uri, self::ID);
        }
	}

	/**
	 * Enter description here...
	 *
	 * @param string $uri
	 * @return string $id
	 */
	private function _getPageIdByUri($uri) {
        $pages = DevblocksPlatform::getExtensions('cerberusweb.page', false);
        foreach($pages as $manifest) { /* @var $manifest DevblocksExtensionManifest */
            if(0 == strcasecmp($uri,$manifest->params['uri'])) {
                return $manifest->id;
            }
        }
        return NULL;
	}
	
	public function handleRequest(DevblocksHttpRequest $request) {
	    $path = $request->path;
		$controller = array_shift($path);

//        $pages = CerberusApplication::getPages();
        $pages = DevblocksPlatform::getExtensions('cerberusweb.page', true);

        $page_id = $this->_getPageIdByUri($controller);
        @$page = $pages[$page_id];

        if(empty($page)) {
	        switch($controller) {
	        	case "portal":
	        		die(); // 404
	        		break;
	        		
	        	default:
	        		return; // default page
	        		break;
	        }
        }

	    @$action = array_shift($path) . 'Action';

	    switch($action) {
	        case NULL:
	            // [TODO] Index/page render
	            break;
	            
	        default:
			    // Default action, call arg as a method suffixed with Action
				if(method_exists($page,$action)) {
					call_user_func(array(&$page, $action)); // [TODO] Pass HttpRequest as arg?
				}
	            break;
	    }
	}
	
	public function writeResponse(DevblocksHttpResponse $response) {
	    $path = $response->path;
		// [JAS]: Ajax? // [TODO] Explore outputting whitespace here for Safari
//	    if(empty($path))
//			return;

		$tpl = DevblocksPlatform::getTemplateService();
		$session = DevblocksPlatform::getSessionService();
		$settings = CerberusSettings::getInstance();
		$translate = DevblocksPlatform::getTranslationService();
		$visit = $session->getVisit();

		$controller = array_shift($path);
		$pages = DevblocksPlatform::getExtensions('cerberusweb.page', true);

		// Default page [TODO] This is supposed to come from framework.config.php
		if(empty($controller)) 
			$controller = 'tickets';

	    // [JAS]: Require us to always be logged in for Cerberus pages
	    // [TODO] This should probably consult with the page itself for ::authenticated()
		if(empty($visit))
			$controller = 'login';

	    $page_id = $this->_getPageIdByUri($controller); /* @var $page CerberusPageExtension */
	    @$page = $pages[$page_id];
        
        if(empty($page)) return; // 404
	    
		// [TODO] Reimplement
		if(!empty($visit) && !is_null($visit->getWorker())) {
		    DAO_Worker::logActivity($visit->getWorker()->id, $page->getActivity());
		}
		
		// [JAS]: Listeners (Step-by-step guided tour, etc.)
	    $listenerManifests = DevblocksPlatform::getExtensions('devblocks.listener.http');
	    foreach($listenerManifests as $listenerManifest) { /* @var $listenerManifest DevblocksExtensionManifest */
	         $inst = $listenerManifest->createInstance(); /* @var $inst DevblocksHttpRequestListenerExtension */
	         $inst->run($response, $tpl);
	    }
		
		// [JAS]: Pre-translate any dynamic strings
        $common_translated = array();
        if(!empty($visit) && !is_null($visit->getWorker()))
            $common_translated['header_signed_in'] = vsprintf($translate->_('header.signed_in'), array('<b>'.$visit->getWorker()->getName().'</b>'));
        $tpl->assign('common_translated', $common_translated);
		
        $tour_enabled = false;
		if(!empty($visit) && !is_null($visit->getWorker())) {
        	$worker = $visit->getWorker();
			$tour_enabled = DAO_WorkerPref::get($worker->id, 'assist_mode');
			$tour_enabled = ($tour_enabled===false) ? 1 : $tour_enabled;
			if(DEMO_MODE) $tour_enabled = 1; // override for DEMO
		}
		$tpl->assign('tour_enabled', $tour_enabled);
		
        // [JAS]: Variables provided to all page templates
		$tpl->assign('settings', $settings);
		$tpl->assign('session', $_SESSION);
		$tpl->assign('translate', $translate);
		$tpl->assign('visit', $visit);
		$tpl->assign('license',CerberusLicense::getInstance());
		
	    $active_worker = CerberusApplication::getActiveWorker();
	    $tpl->assign('active_worker', $active_worker);
	
	    if(!empty($active_worker)) {
	    	$active_worker_memberships = $active_worker->getMemberships();
	    	$tpl->assign('active_worker_memberships', $active_worker_memberships);
	    }
		
		$tpl->assign('pages',$pages);		
		$tpl->assign('page',$page);

		$tpl->assign('response_uri', implode('/', $response->path));
		
		$tpl_path = dirname(__FILE__) . '/templates/';
		$tpl->assign('tpl_path', $tpl_path);

		// Timings
		$tpl->assign('render_time', (microtime(true) - DevblocksPlatform::getStartTime()));
		if(function_exists('memory_get_usage') && function_exists('memory_get_peak_usage')) {
			$tpl->assign('render_memory', memory_get_usage() - DevblocksPlatform::getStartMemory());
			$tpl->assign('render_peak_memory', memory_get_peak_usage() - DevblocksPlatform::getStartPeakMemory());
		}
		
		$tpl->display($tpl_path.'border.php');
	}
};

class ChTranslations extends DevblocksTranslationsExtension {
	function __construct($manifest) {
		parent::__construct($manifest);	
	}
	
	function getTmxFile() {
		return dirname(__FILE__) . '/strings.xml';
	}
};

class ChTicketsPage extends CerberusPageExtension {
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

		return true;
	}
	
	function getActivity() {
		$visit = CerberusApplication::getVisit();
		$team_name = "";
		
		$team_id = $visit->get(CerberusVisit::KEY_WORKSPACE_GROUP_ID, 0);
		if($team_id) {
			if(null !== ($team = DAO_Group::getTeam($team_id))) {
				$team_name = $team->name;
			}
		}
		
		return new Model_Activity('activity.tickets',array(
	    	$team_name
	    ));
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$visit = CerberusApplication::getVisit();
		
		$response = DevblocksPlatform::getHttpResponse();
		@$section = $response->path[1];

		// [TODO] Change to a getAll cache
		$workers = DAO_Worker::getList();
		$tpl->assign('workers', $workers);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Clear all undo actions on reload
	    CerberusDashboardView::clearLastActions();
	    				
		$quick_search_type = $visit->get('quick_search_type');
		$tpl->assign('quick_search_type', $quick_search_type);
	    
		switch($section) {
			case 'search':
				$visit = CerberusApplication::getVisit();
				$viewManager = $visit->get(CerberusVisit::KEY_VIEW_MANAGER); /* @var $viewManager CerberusStaticViewManager */
								
				$view = $viewManager->getView(CerberusApplication::VIEW_SEARCH);
				
				$tpl->assign('view', $view);
				$tpl->assign('params', $view->params);
				
				// [TODO]: This should be filterable by a specific view later as well using searchDAO.
//				$viewActions = DAO_DashboardViewAction::getList();
//				$tpl->assign('viewActions', $viewActions);

				$teams = DAO_Group::getAll();
				$tpl->assign('teams', $teams);
				
				$buckets = DAO_Bucket::getAll();
				$tpl->assign('buckets', $buckets);
				
				$team_categories = DAO_Bucket::getTeams();
				$tpl->assign('team_categories', $team_categories);
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/search/index.tpl.php');
				break;
				
			case 'rss':
				$feeds = DAO_TicketRss::getByWorker($active_worker->id);
				$tpl->assign('feeds', $feeds);
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/rss/index.tpl.php');
				break;
			
			case 'create':
				$teams = DAO_Group::getAll();
				
				$team_id = $visit->get(CerberusVisit::KEY_WORKSPACE_GROUP_ID, 0);
				if($team_id) {
					$tpl->assign('team', $teams[$team_id]);
				}
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/create/index.tpl.php');
				break;
			
			case 'compose':
				$teams = DAO_Group::getAll();
				$settings = CerberusSettings::getInstance();

				$team_id = $visit->get(CerberusVisit::KEY_WORKSPACE_GROUP_ID, 0);
				if($team_id) {
					$tpl->assign('team', $teams[$team_id]);
					
					$default_sig = $settings->get(CerberusSettings::DEFAULT_SIGNATURE,'');
					$team_sig = $teams[$team_id]->signature;
					$worker = CerberusApplication::getActiveWorker();
					// Translate
					$tpl->assign('signature', str_replace(
			        	array('#first_name#','#last_name#','#title#'),
			        	array($worker->first_name,$worker->last_name,$worker->title),
			        	!empty($team_sig) ? $team_sig : $default_sig
					));
				}
					
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/compose/index.tpl.php');
				break;
			
			case 'team':
				$response = DevblocksPlatform::getHttpResponse();
				$response_path = $response->path;
				array_shift($response_path); // tickets
				array_shift($response_path); // team
				$team_id = array_shift($response_path); // id
				
				$team = DAO_Group::getTeam($team_id);
				
				if(empty($team))
				    break;

		        $tpl->cache_lifetime = "0";
			    $tpl->assign('team', $team);
			    
	            switch(array_shift($response_path)) {
	                default:
	                case 'general':
						$team_categories = DAO_Bucket::getByTeam($team_id);
						$tpl->assign('categories', $team_categories);
					    
						$group_settings = DAO_GroupSettings::getSettings($team_id);
						
						@$tpl->assign('group_settings', $group_settings);
						@$tpl->assign('group_spam_threshold', $group_settings[DAO_GroupSettings::SETTING_SPAM_THRESHOLD]);
						@$tpl->assign('group_spam_action', $group_settings[DAO_GroupSettings::SETTING_SPAM_ACTION]);
						@$tpl->assign('group_spam_action_param', $group_settings[DAO_GroupSettings::SETTING_SPAM_ACTION_PARAM]);
						
	                    $tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/teamwork/manage/index.tpl.php');
	                    break;
	                    
	                case 'members':
						$members = DAO_Group::getTeamMembers($team_id);
					    $tpl->assign('members', $members);
					    
					    $available_workers = array();
					    foreach($workers as $worker) {
//					    	if(!isset($members[$worker->id]))
					    		$available_workers[$worker->id] = $worker;
					    }
					    $tpl->assign('available_workers', $available_workers);
					    
	                    $tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/teamwork/manage/members.tpl.php');
	                    break;
	                    
	                case 'buckets':
						$team_categories = DAO_Bucket::getByTeam($team_id);
						$tpl->assign('categories', $team_categories);
	                    
						$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/teamwork/manage/buckets.tpl.php');
	                    break;
	                    
	                case 'routing':
	                    $team_rules = DAO_TeamRoutingRule::getByTeamId($team_id);
	                    $tpl->assign('team_rules', $team_rules);

	                    $category_name_hash = DAO_Bucket::getCategoryNameHash();
	                    $tpl->assign('category_name_hash', $category_name_hash);
	                    
						$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/teamwork/manage/routing.tpl.php');
	                    break;
	            }
	            
			    break;
				
			case 'workspaces':
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
						$visit->set(CerberusVisit::KEY_WORKSPACE_GROUP_ID, $team_id);
					}
				}
				
				$dashboards = DAO_Dashboard::getDashboards($visit->getWorker()->id);
				$tpl->assign('dashboards', $dashboards);

				$teams = DAO_Group::getAll();
				$tpl->assign('teams', $teams);
				
				$team_categories = DAO_Bucket::getTeams();
				$tpl->assign('team_categories', $team_categories);
				
				// [TODO] Be sure we're caching this
				$team_counts = DAO_Group::getTeamCounts(array_keys($teams));
				$tpl->assign('team_counts', $team_counts);

				
				// [mdf] Set the dashboard and group being browsed to "my tickets" if we were deleted 
				// from the group we attempted to access while browsing
				$active_worker = CerberusApplication::getActiveWorker();
				$memberships = $active_worker->getMemberships();
				$group_id = $visit->get(CerberusVisit::KEY_WORKSPACE_GROUP_ID, 0);
				if(!isset($memberships[$group_id])) {
					//we need to overwrite the activity that was set in ChPageController
					DAO_Worker::logActivity($active_worker->id, $this->getActivity());
					
					$visit->set(CerberusVisit::KEY_DASHBOARD_ID, 0);
					$visit->set(CerberusVisit::KEY_WORKSPACE_GROUP_ID, 0);
				}
				
				$active_dashboard_id = $visit->get(CerberusVisit::KEY_DASHBOARD_ID, 0);
				
//				$memberships = DAO_Worker::getGroupMemberships($active_worker->id);
//				if(empty($active_dashboard_id) && !empty($memberships)) {
//				    // [TODO] Set a default when someone first logs in
//	                list($team_key, $team_val) = each($memberships);
//	                $active_dashboard_id = 't' . $team_key;
//	                $visit->set(CerberusVisit::KEY_DASHBOARD_ID, $active_dashboard_id);
//	                $visit->set(CerberusVisit::KEY_WORKSPACE_GROUP_ID, $team_key);
//	            }
	            
				if(empty($active_dashboard_id)) { // custom dashboards
	            // My Tickets
					$myView = $viewManager->getView(CerberusApplication::VIEW_MY_TICKETS);
					
					// [JAS]: Recover from a bad cached ID.
					if(null == $myView) {
						$myView = new CerberusDashboardView();
						$myView->id = CerberusApplication::VIEW_MY_TICKETS;
						$myView->name = "New Messages for Me";
						$myView->dashboard_id = 0;
						$myView->view_columns = array(
							SearchFields_Ticket::TICKET_NEXT_ACTION,
							SearchFields_Ticket::TICKET_UPDATED_DATE,
//							SearchFields_Ticket::TICKET_LAST_WROTE,
							SearchFields_Ticket::TEAM_NAME,
//							SearchFields_Ticket::TICKET_CATEGORY_ID,
							SearchFields_Ticket::TICKET_SPAM_SCORE,
							SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
							);
						$myView->params = array(
							new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',CerberusTicketStatus::OPEN),
							new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_NEXT_WORKER_ID,'in',array($active_worker->id)),
							new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_LAST_ACTION_CODE,'in',array('O','R')),
						);
						$myView->renderLimit = 10;
						$myView->renderPage = 0;
						$myView->renderSortBy = SearchFields_Ticket::TICKET_UPDATED_DATE;
						$myView->renderSortAsc = 1;
						
						$viewManager->setView(CerberusApplication::VIEW_MY_TICKETS,$myView);
					}
					$views = array($myView->id => $myView);
					$tpl->assign('views', $views);
					
				// Nuke custom dashboards?
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
					$team_id = $visit->get(CerberusVisit::KEY_WORKSPACE_GROUP_ID, 0);
					
                    if($team_id) {
						$team = $teams[$team_id];
						$tpl->assign('dashboard_team_id', $team_id);

						$buckets = DAO_Bucket::getByTeam($team_id);
						$tpl->assign('buckets', $buckets);
						
						@$team_filters = $_SESSION['team_filters'][$team_id];
						if(empty($team_filters)) $team_filters = array();
						$tpl->assign('team_filters', $team_filters);
						
						$category_counts = DAO_Bucket::getCategoryCountsByTeam($team_id);
		                $tpl->assign('category_counts', $category_counts);
						
		                // [TODO] Move to API
	                    $active_worker = CerberusApplication::getActiveWorker();
			            $move_counts_str = DAO_WorkerPref::get($active_worker->id,''.DAO_WorkerPref::SETTING_TEAM_MOVE_COUNTS . $active_dashboard_id,serialize(array()));
			            if(is_string($move_counts_str)) {
			                $category_name_hash = DAO_Bucket::getCategoryNameHash();
			                $tpl->assign('category_name_hash', $category_name_hash);
			                
			                $move_counts = unserialize($move_counts_str);
			                $tpl->assign('move_to_counts', array_slice($move_counts,0,10,true));
			            }
		                
					    @$team_mode = array_shift($request_path);
							// ======================================================
							// Team Tickets (All)
							// ======================================================
							$teamView = $viewManager->getView(CerberusApplication::VIEW_TEAM_TICKETS);
							if(null == $teamView) {
								$teamView = new CerberusDashboardView();
								$teamView->id = CerberusApplication::VIEW_TEAM_TICKETS;
								$teamView->name = "Active Tickets";
								$teamView->dashboard_id = 0;
								$teamView->view_columns = array(
//									SearchFields_Ticket::TEAM_NAME,
									SearchFields_Ticket::TICKET_NEXT_ACTION,
									SearchFields_Ticket::TICKET_UPDATED_DATE,
									SearchFields_Ticket::TICKET_CATEGORY_ID,
									SearchFields_Ticket::TICKET_SPAM_SCORE,
									SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
									);
								$teamView->params = array();
								$teamView->renderLimit = 10;
								$teamView->renderPage = 0;
								$teamView->renderSortBy = SearchFields_Ticket::TICKET_UPDATED_DATE;
								$teamView->renderSortAsc = 0;
								
								$viewManager->setView(CerberusApplication::VIEW_TEAM_TICKETS,$teamView);
							}
							
							$teamView->name = $team->name . ": Active";
							$teamView->params = array(
								new DevblocksSearchCriteria(SearchFields_Ticket::TEAM_ID,'=',$team_id),
							);
							
							// [JAS]: Team Filters
							if(!empty($team_filters)) {
							    if(!empty($team_filters['categorized'])) {
                                    if(!empty($team_filters['categories'])) {
	    							    $cats = array_keys($team_filters['categories']);
                                        $teamView->params[] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CATEGORY_ID,DevblocksSearchCriteria::OPER_IN,$cats);
								    }
							    }
							    
							    if(!empty($team_filters['hide_assigned'])) {
							       // [TODO] Need to redo ownership
//							       $teamView->params[] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_TASKS,DevblocksSearchCriteria::OPER_EQ,0);
							    }

//							    if(!empty($team_filters['show_waiting'])) {
//							       $teamView->params[] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_STATUS,'in',array(CerberusTicketStatus::OPEN));
//							    } else {
							        $teamView->params[] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,DevblocksSearchCriteria::OPER_EQ,CerberusTicketStatus::OPEN);
//							    }

							} else { // defaults (no filters)
                                $teamView->params[] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,DevblocksSearchCriteria::OPER_EQ,CerberusTicketStatus::OPEN);
                                							    
							}
							
//					        $view_key = CerberusVisit::KEY_VIEW_TIPS . $active_dashboard_id;
//					        $view_tips = $visit->get($view_key,array());
//					        $teamView->tips = $view_tips;
							
							$views = array(
								$teamView->id => $teamView
							);
							$tpl->assign('views', $views);
                    }
				}
				
				// [TODO]: This should be filterable by a specific view later as well using searchDAO.
//				$viewActions = DAO_DashboardViewAction::getList();
//				$tpl->assign('viewActions', $viewActions);

				$tpl->assign('active_dashboard_id', $active_dashboard_id);

				list($whos_online_workers, $whos_online_count) = DAO_Worker::search(
				    array(
				        new DevblocksSearchCriteria(SearchFields_Worker::LAST_ACTIVITY_DATE,DevblocksSearchCriteria::OPER_GT,(time()-60*15)), // idle < 15 mins
				        new DevblocksSearchCriteria(SearchFields_Worker::LAST_ACTIVITY,DevblocksSearchCriteria::OPER_NOT_LIKE,'%translation_code";N;%'), // translation code not null (not just logged out)
				    ),
				    -1,
				    0,
				    SearchFields_Worker::LAST_ACTIVITY_DATE,
				    false,
				    false
				);
				
				$whos_online = DAO_Worker::getList(array_keys($whos_online_workers));
				$tpl->assign('whos_online', $whos_online);
				
				$translate = DevblocksPlatform::getTranslationService();
				$translated = array(
					'whos_heading' => vsprintf($translate->_('whos_online.heading'),array($whos_online_count))
				);
				$tpl->assign('translated', $translated);
				
				$tpl->cache_lifetime = "0";
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/index.tpl.php');
				break;
		}
		
	}
	
	//**** Local scope

	// [TODO] [JAS]: In the future this should only advance groups this worker belongs to
	function nextGroupAction() {
		$visit = CerberusApplication::getVisit();
		$worker = CerberusApplication::getActiveWorker();
		$memberships = DAO_Worker::getGroupMemberships($worker->id);
		
		$groups = DAO_Group::getAll();
		$group_ids = array_keys($groups);
		
		// Only show memberships
		foreach($group_ids as $idx => $gid) {
			if(!isset($memberships[$gid]))
				unset($group_ids[$idx]);
		}

		array_unshift($group_ids, 0);
		
		$group_id = $visit->get(CerberusVisit::KEY_WORKSPACE_GROUP_ID, 0);
		
		if(!empty($group_ids)) {
			$next = false;
			reset($group_ids);
			
			while(false !== ($idx = current($group_ids))) {
				if($idx == $group_id) {
					if(false === ($next = next($group_ids))) {
						$next = reset($group_ids);
					}
					break;
				}
				next($group_ids);
			} 
			
			if(false !== $next) {
				$visit->set(CerberusVisit::KEY_DASHBOARD_ID, (empty($next) ? '' : ('t'.$next)));
				$visit->set(CerberusVisit::KEY_WORKSPACE_GROUP_ID, $next);
			}
		}
		
		return new DevblocksHttpResponse(array('tickets','workspaces'));
	}
	
	// Ajax
	// [TODO] Move to another page
	function showCalloutAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'string');

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$callouts = CerberusApplication::getTourCallouts();
		
	    $callout = array();
	    if(isset($callouts[$id]))
	        $callout = $callouts[$id];
		
	    $tpl->assign('callout',$callout);
		
		$tpl->cache_lifetime = "0";
		$tpl->display('tour/callout.tpl.php');
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
        
        CerberusDashboardView::setLastAction($view_id,$last_action);
        //====================================	    
	    
	    CerberusBayes::markTicketAsSpam($id);
	    
	    // [TODO] Move categories (according to config)
	    $fields = array(
	        DAO_Ticket::IS_DELETED => 1,
	        DAO_Ticket::IS_CLOSED => CerberusTicketStatus::CLOSED
	    );
	    DAO_Ticket::updateTicket($id, $fields);
	    
	    $tpl = DevblocksPlatform::getTemplateService();
		$path = dirname(__FILE__) . '/templates/';
		$tpl->assign('path', $path);

	    $visit = CerberusApplication::getVisit();
		$viewManager = $visit->get(CerberusVisit::KEY_VIEW_MANAGER); /* @var $viewManager CerberusStaticViewManager */
		$view = $viewManager->getView($view_id);
		$tpl->assign('view', $view);
		
		if(!empty($last_action) && !is_null($last_action->ticket_ids)) {
			$tpl->assign('last_action_count', count($last_action->ticket_ids));
		}
		
		$tpl->assign('last_action', $last_action);
		$tpl->cache_lifetime = "0";
		$tpl->display($path.'tickets/rpc/ticket_view_output.tpl.php');
	} 
	
	// Post
	// [TODO] Move to another page
	function doStopTourAction() {
		$worker = CerberusApplication::getActiveWorker();
		DAO_WorkerPref::set($worker->id, 'assist_mode', 0);
	}
	
	// Post
	function saveTeamFiltersAction() {
	    @$team_id = DevblocksPlatform::importGPC($_POST['team_id'],'integer');
	    @$categories = DevblocksPlatform::importGPC($_POST['categories'],'array');
	    @$categorized = DevblocksPlatform::importGPC($_POST['categorized'],'integer');
//	    @$show_waiting = DevblocksPlatform::importGPC($_POST['show_waiting'],'integer');
//	    @$hide_assigned = DevblocksPlatform::importGPC($_POST['hide_assigned'],'integer');
	    @$add_buckets = DevblocksPlatform::importGPC($_POST['add_buckets'],'string');

	    // Adds: Sort and insert team categories
	    if(!empty($add_buckets)) {
		    $buckets = CerberusApplication::parseCrlfString($add_buckets);
	
		    if(is_array($buckets))
		    foreach($buckets as $bucket) {
	            if(empty($bucket))
	                continue;
	                
		        $bucket_id = DAO_Bucket::create($bucket, $team_id);
		    }
	    }
	    
	    if(!isset($_SESSION['team_filters']))
	        $_SESSION['team_filters'] = array();
	    
	    $filters = array(
	        'categories' => array_flip($categories),
	        'categorized' => $categorized,
//	        'hide_assigned' => $hide_assigned,
//	        'show_waiting' => $show_waiting
	    );
	    $_SESSION['team_filters'][$team_id] = $filters;
	    
	    //DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','workspaces','team',$team_id)));
	    DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','workspaces','team',$team_id)));
	}
	
	// Ajax
	function refreshTeamFiltersAction() {
//	    @$team_id = DevblocksPlatform::importGPC($_POST['team_id'],'integer');

        $visit = CerberusApplication::getVisit();
        $active_dashboard_id = $visit->get(CerberusVisit::KEY_DASHBOARD_ID, 0);
        
        $team_id = $visit->get(CerberusVisit::KEY_WORKSPACE_GROUP_ID, 0);
        
		$tpl = DevblocksPlatform::getTemplateService();
		$path = dirname(__FILE__) . '/templates/';
		$tpl->assign('path', $path);
		
		$tpl->assign('active_dashboard_id', $active_dashboard_id);
		$tpl->assign('dashboard_team_id', $team_id);

	    $active_worker = CerberusApplication::getActiveWorker();
	    if(!empty($active_worker)) {
	    	$active_worker_memberships = $active_worker->getMemberships();
	    	$tpl->assign('active_worker_memberships', $active_worker_memberships);
	    }
		
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		
		$buckets = DAO_Bucket::getByTeam($team_id);
		$tpl->assign('buckets', $buckets);
				
		@$team_filters = $_SESSION['team_filters'][$team_id];
		if(empty($team_filters)) $team_filters = array();
		$tpl->assign('team_filters', $team_filters);
		
		$team_counts = DAO_Group::getTeamCounts(array_keys($teams));
		$tpl->assign('team_counts', $team_counts);
		
		$category_counts = DAO_Bucket::getCategoryCountsByTeam($team_id);
        $tpl->assign('category_counts', $category_counts);
		
		$tpl->display($path.'tickets/dashboard_menu.tpl.php');
	    
//	    DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','dashboards','team',$team_id)));
	}
	
	// Post	
	function doQuickSearchAction() {
        @$type = DevblocksPlatform::importGPC($_POST['type'],'string'); 
        @$query = DevblocksPlatform::importGPC($_POST['query'],'string');

        $visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
        $viewMgr = $visit->get(CerberusVisit::KEY_VIEW_MANAGER); /* @var $viewMgr CerberusStaticViewManager */
        $searchView = $viewMgr->getView(CerberusApplication::VIEW_SEARCH); /* @var $searchView CerberusDashboardView */

        $visit->set('quick_search_type', $type);
        
        $params = array();
        
        if($query && false===strpos($query,'*'))
            $query = '*' . $query . '*';
        
        switch($type) {
            case "mask":
                $params[SearchFields_Ticket::TICKET_MASK] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MASK,DevblocksSearchCriteria::OPER_LIKE,strtoupper($query));
                break;
                
            case "sender":
                $params[SearchFields_Ticket::TICKET_FIRST_WROTE] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE,DevblocksSearchCriteria::OPER_LIKE,strtolower($query));               
                break;
                
            case "subject":
                $params[SearchFields_Ticket::TICKET_SUBJECT] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_SUBJECT,DevblocksSearchCriteria::OPER_LIKE,$query);               
                break;
                
            case "content":
                $params[SearchFields_Ticket::TICKET_MESSAGE_CONTENT] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MESSAGE_CONTENT,DevblocksSearchCriteria::OPER_LIKE,$query);               
                break;
        }
        
        $searchView->params = $params;
        $searchView->renderSortBy = null;
        $viewMgr->setView(CerberusApplication::VIEW_SEARCH,$searchView);
        
        //DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','search')));
        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','search')));
	}
	
	// Ajax
	function showPreviewAction() {
	    @$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
	    
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(__FILE__) . '/templates/';
		$tpl->assign('path', $tpl_path);
	    
	    $ticket = DAO_Ticket::getTicket($id); /* @var $ticket CerberusTicket */
	    $messages = DAO_Ticket::getMessagesByTicket($id);
	    
        if(!empty($messages)) {	    
	        $last = array_pop($messages);
	        $content = DAO_MessageContent::get($last->id);
        }
	    
	    $tpl->assign('ticket', $ticket);
	    $tpl->assign('message', $last);
	    $tpl->assign('content', $content);
	    
	    $workers = DAO_Worker::getList(); // ::getAll();
		$tpl->assign('workers', $workers);
	    
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/rpc/preview_panel.tpl.php');
	}
	
	// Post
	// [TODO] Change to Ajax Post?
	function savePreviewAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$next_action = DevblocksPlatform::importGPC($_REQUEST['next_action'],'string','');
		@$next_worker_id = DevblocksPlatform::importGPC($_REQUEST['next_worker_id'],'integer',0);
		
		$fields = array(
			DAO_Ticket::NEXT_ACTION => $next_action,
			DAO_Ticket::NEXT_WORKER_ID => $next_worker_id,
		);
		DAO_Ticket::updateTicket($id, $fields);
		
		// [TODO] Abstract View Redraw
	}
	
	function clickteamAction() {
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','search')));
	}
	
	function composeMailAction() {
		@$team_id = DevblocksPlatform::importGPC($_POST['team_id'],'integer'); 
		@$to = DevblocksPlatform::importGPC($_POST['to'],'string');
		@$subject = DevblocksPlatform::importGPC($_POST['subject'],'string');
		@$content = DevblocksPlatform::importGPC($_POST['content'],'string');
		@$files = $_FILES['attachment'];
		
		if(DEMO_MODE) {
			return;
		}
		
		$settings = CerberusSettings::getInstance();
		$default_from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM);
		$default_personal = $settings->get(CerberusSettings::DEFAULT_REPLY_PERSONAL);
		$group_settings = DAO_GroupSettings::getSettings($team_id);
		@$team_from = $group_settings[DAO_GroupSettings::SETTING_REPLY_FROM];
		@$team_personal = $group_settings[DAO_GroupSettings::SETTING_REPLY_PERSONAL];
		
		$from = !empty($team_from) ? $team_from : $default_from;
		$personal = !empty($team_personal) ? $team_personal : $default_personal;

		$sendTo = new Swift_Address($to);
		$sendFrom = new Swift_Address($from, $personal);
		
		$mail_service = DevblocksPlatform::getMailService();
		$mailer = $mail_service->getMailer();
		$email = $mail_service->createMessage();

		$email->setTo($sendTo);
		$email->setFrom($sendFrom);
		$email->setSubject($subject);
		$email->generateId();
		$email->headers->set('X-Mailer','Cerberus Helpdesk (Build '.APP_BUILD.')');
		
		$email->attach(new Swift_Message_Part($content));
		
		// [TODO] These attachments should probably save to the DB
		
		// Mime Attachments
		if (is_array($files) && !empty($files)) {
			foreach ($files['tmp_name'] as $idx => $file) {
				if(empty($file) || empty($files['name'][$idx]))
					continue;

				$email->attach(new Swift_Message_Attachment(
					new Swift_File($file), $files['name'][$idx], $files['type'][$idx]));
			}
		}
		
		// [TODO] Allow seperated addresses (parseRfcAddress)
//		$mailer->log->enable();
		if(!$mailer->send($email, $sendTo, $sendFrom)) {
			// [TODO] Report when the message wasn't sent.
		}
//		$mailer->log->dump();
		
		$worker = CerberusApplication::getActiveWorker();
		$fromAddressId = CerberusApplication::hashLookupAddressId($from, true);
		
		// [TODO] This really should be in the Mail API
		$fields = array(
			DAO_Ticket::MASK => CerberusApplication::generateTicketMask(),
			DAO_Ticket::SUBJECT => $subject,
			DAO_Ticket::CREATED_DATE => time(),
			DAO_Ticket::FIRST_WROTE_ID => $fromAddressId,
			DAO_Ticket::LAST_WROTE_ID => $fromAddressId,
			DAO_Ticket::LAST_ACTION_CODE => CerberusTicketActionCode::TICKET_WORKER_REPLY,
			DAO_Ticket::LAST_WORKER_ID => $worker->id,
			DAO_Ticket::NEXT_WORKER_ID => 0, // [TODO] Implement
			DAO_Ticket::TEAM_ID => $team_id,
		);
		$ticket_id = DAO_Ticket::createTicket($fields);
		
	    $fields = array(
	        DAO_Message::TICKET_ID => $ticket_id,
	        DAO_Message::CREATED_DATE => time(),
	        DAO_Message::ADDRESS_ID => $fromAddressId,
	    );
		$message_id = DAO_Message::create($fields);
	    
		// Content
	    DAO_MessageContent::update($message_id, $content);

	    // Headers
		foreach($email->headers->getList() as $hdr => $v) {
			if(null != ($hdr_val = $email->headers->getEncoded($hdr))) {
				if(!empty($hdr_val))
	    			DAO_MessageHeader::update($message_id, $ticket_id, $hdr, $hdr_val);
			}
		}
		
		// Set recipients to requesters
		// [TODO] Allow seperated addresses (parseRfcAddress)
		if(null != ($reqAddressId = CerberusApplication::hashLookupAddressId($to, true)))
			DAO_Ticket::createRequester($reqAddressId, $ticket_id);
		
		//DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','compose')));
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','compose')));
	}
	
	// [TODO] Nuke the message_id redundancy here, and such
	function createTicketAction() {
		//require_once(DEVBLOCKS_PATH . 'libs/pear/mimeDecode.php');

		$settings = CerberusSettings::getInstance();
		$to = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM);
		
		@$team_id = DevblocksPlatform::importGPC($_POST['team_id'],'integer'); 
		@$from = DevblocksPlatform::importGPC($_POST['from'],'string');
		@$subject = DevblocksPlatform::importGPC($_POST['subject'],'string');
		@$content = DevblocksPlatform::importGPC($_POST['content'],'string');
		@$files = $_FILES['attachment'];
		
		$team = DAO_Group::getTeam($team_id);

		$message = new CerberusParserMessage();
		$message->headers['from'] = $from;
		$message->headers['to'] = $to;
		$message->headers['subject'] = $subject;
		$message->headers['date'] = date('r');
		
		$message->body = $content;
	    
		$ticket_id = CerberusParser::parseMessage($message);

//		list($messages,$null) = DAO_Ticket::getMessagesByTicket($ticket_id);
//		$message = array_shift($messages); /* @var $message CerberusMessage */
//		$message_id = $message->id;
//		
//		// if this message was submitted with attachments, store them in the filestore and link them to the message_id in the db.
//		if (is_array($files) && !empty($files)) {
//		
//			/*
//			// [TODO] This needs cleaned up
//			if(is_array($files['tmp_name']))
//			foreach ($files['tmp_name'] as $idx => $file) {
//				copy($files['tmp_name'][$idx],$attachmentlocation.$message_id.$idx);
//				DAO_Ticket::createAttachment($message_id, $files['name'][$idx], $message_id.$idx);
//			}
//			*/
//		}
		
		// Routing override
		DAO_Ticket::updateTicket($ticket_id,array(
			DAO_Ticket::TEAM_ID => $team_id
		));

		//DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('display',$ticket_id)));
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$ticket_id)));
	}
	
	function mailboxAction() {
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;
		@$id = intval($stack[2]); 
		
		$view = DAO_Dashboard::getView(CerberusApplication::VIEW_SEARCH);
		$view->params = array(
			new DevblocksSearchCriteria(SearchFields_Ticket::MAILBOX_ID,'=', $id),
			new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=', CerberusTicketStatus::OPEN)
		);
		
		//DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','search')));
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','search')));
	}
	
	function showViewAutoAssistAction() {
        @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
        @$mode = DevblocksPlatform::importGPC($_REQUEST['mode'],'string','senders');
        @$mode_param = DevblocksPlatform::importGPC($_REQUEST['mode_param'],'string','');

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(__FILE__) . '/templates/';
		$tpl->assign('path', $tpl_path);
        
        $visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
        $active_dashboard_id = $visit->get(CerberusVisit::KEY_DASHBOARD_ID, 0);
        
        $viewMgr = $visit->get(CerberusVisit::KEY_VIEW_MANAGER); /* @var CerberusStaticViewManager $viewMgr */
        $view = $viewMgr->getView($view_id); /* @var $view CerberusDashboardView */
        
        $tpl->assign('view_id', $view_id);
        $tpl->assign('mode', $mode);

        if($mode == "headers" && empty($mode_param)) {
            $headers = DAO_MessageHeader::getUnique();
            $tpl->assign('headers', $headers);
            
	        $tpl->display($tpl_path.'tickets/rpc/ticket_view_assist_headers.tpl.php');
	        
        } else {
			$team_id = $visit->get(CerberusVisit::KEY_WORKSPACE_GROUP_ID, 0);;
		    $tpl->assign('dashboard_team_id', $team_id);
	        
			$teams = DAO_Group::getAll();
			$tpl->assign('teams', $teams);
			
			$team_categories = DAO_Bucket::getTeams();
			$tpl->assign('team_categories', $team_categories);
			
			$category_name_hash = DAO_Bucket::getCategoryNameHash();
			$tpl->assign('category_name_hash', $category_name_hash);
	        
	        // [JAS]: Calculate statistics about the current view (top unique senders/subjects/domains)
		    $biggest = DAO_Ticket::analyze($view->params, 15, $mode, $mode_param);
		    $tpl->assign('biggest', $biggest);
	        
	        $tpl->display($tpl_path.'tickets/rpc/ticket_view_assist.tpl.php');
        }
	}
	
	function viewAutoAssistAction() {
	    @$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');

        $visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
        $viewMgr = $visit->get(CerberusVisit::KEY_VIEW_MANAGER); /* @var $viewMgr CerberusStaticViewManager */
        $view = $viewMgr->getView($view_id); /* @var $view CerberusDashboardView */

        $active_dashboard_id = $visit->get(CerberusVisit::KEY_DASHBOARD_ID, 0);
		$dashboard_team_id = $visit->get(CerberusVisit::KEY_WORKSPACE_GROUP_ID, 0);
        
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
	        @$always = (isset($piles_always[$hash])) ? 1 : 0;
	        
	        /*
	         * [TODO] [JAS]: Somewhere here we should be ignoring these values for a bit
	         * so other options have a chance to bubble up
	         */
	        if(empty($hash) || empty($moveto) || empty($type) || empty($val))
	            continue;
	        
	        switch(strtolower(substr($moveto,0,1))) {
	            // Team/Bucket Move
	            case 't':
	            case 'c':
	                $doActions = array('team' => $moveto);
	                break;
	                
	            // Action
	            case 'a':
	                switch(strtolower(substr($moveto,1))) {
	                    case 'c': // close
	                        $doActions = array('closed' => CerberusTicketStatus::CLOSED);
	                        break;
	                    case 's': // spam
	                        $doActions = array('spam' => CerberusTicketSpamTraining::SPAM);
	                        break;
	                    case 'd': // delete
	                        $doActions = array('closed' => 2);
	                        break;
	                }
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
            
            $doAlways = ($always && $dashboard_team_id) ? $dashboard_team_id : 0;

            $view->doBulkUpdate($doType, $doTypeParam, $doData, $doActions, array(), $doAlways);
	    }

	    // Reset the paging since we may have reduced our list size
	    $view->renderPage = 0;
	    $viewMgr->setView($view_id, $view);
	    	    
        //DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets')));
        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets')));
	}
	
	function viewMoveTicketsAction() {
	    @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
	    @$ticket_ids = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'array');
	    @$move_to = DevblocksPlatform::importGPC($_REQUEST['move_to'],'string');
	    
	    if(empty($ticket_ids)) {
	    	echo ' ';
	    	return;
	    }
	    
        $visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
	    $active_dashboard_id = $visit->get(CerberusVisit::KEY_DASHBOARD_ID, 0);	    
	    
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
        
        CerberusDashboardView::setLastAction($view_id,$last_action);
	    
	    // Make our changes to the entire list of tickets
	    if(!empty($ticket_ids) && !empty($team_id)) {
	        DAO_Ticket::updateTicket($ticket_ids, $fields);
	        
		    $eventMgr = DevblocksPlatform::getEventService();
		    $eventMgr->trigger(
		        new Model_DevblocksEvent(
		            'ticket.moved', // [TODO] Const
	                array(
	                    'ticket_ids' => $ticket_ids,
	                    'tickets' => $orig_tickets,
	                    'team_id' => $team_id,
	                    'bucket_id' => $category_id,
	                )
	            )
	        );
	    }
	    
	    // Increment the counter of uses for this move (by # of tickets affected)
	    // [TODO] Move this into a WorkerPrefs API class
	    $active_worker = CerberusApplication::getActiveWorker(); /* @var $$active_worker CerberusWorker */
	    if($active_worker->id) {
	        $move_counts_str = DAO_WorkerPref::get($active_worker->id,''.DAO_WorkerPref::SETTING_TEAM_MOVE_COUNTS . $active_dashboard_id,serialize(array()));
	        if(is_string($move_counts_str)) {
	            $move_counts = unserialize($move_counts_str);
	            @$move_counts[$move_to] = intval($move_counts[$move_to]) + count($ticket_ids);
	            arsort($move_counts);
	            DAO_WorkerPref::set($active_worker->id,''.DAO_WorkerPref::SETTING_TEAM_MOVE_COUNTS . $active_dashboard_id,serialize($move_counts));
	        }
	    }
	    
	    echo ' ';
	    return;
	}
	
	function viewMergeTicketsAction() {
	    @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
	    @$ticket_ids = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'array');

//        $fields = array(
//            DAO_Ticket::IS_CLOSED => 0,
//            DAO_Ticket::IS_DELETED => 0,
//        );
	    
        //====================================
	    // Undo functionality
//        $last_action = new Model_TicketViewLastAction();
//        $last_action->action = Model_TicketViewLastAction::ACTION_NOT_SPAM;
//
//        if(is_array($ticket_ids))
//        foreach($ticket_ids as $ticket_id) {
////            CerberusBayes::calculateTicketSpamProbability($ticket_id); // [TODO] Ugly (optimize -- use the 'interesting_words' to do a word bayes spam score?
//            
//            $last_action->ticket_ids[$ticket_id] = array(
//                DAO_Ticket::SPAM_TRAINING => CerberusTicketSpamTraining::BLANK,
//                DAO_Ticket::SPAM_SCORE => 0.0001, // [TODO] Fix
//                DAO_Ticket::IS_CLOSED => 0,
//                DAO_Ticket::IS_DELETED => 0
//            );
//        }
//
//        $last_action->action_params = $fields;
//        
//        CerberusDashboardView::setLastAction($view_id,$last_action);
        CerberusDashboardView::setLastAction($view_id,null);
        //====================================

	    if(!empty($ticket_ids)) {
	    	$oldest_id = DAO_Ticket::merge($ticket_ids);
	    }
	    
	    echo ' ';
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
        
        CerberusDashboardView::setLastAction($view_id,$last_action);
        //====================================
	    
        DAO_Ticket::updateTicket($ticket_ids, $fields);
	    
	    echo ' ';
	    return;
	}
	
	function viewSurrenderTicketsAction() {
	    @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
	    @$ticket_ids = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'array');
	    
        $fields = array(
            DAO_Ticket::NEXT_WORKER_ID => 0,
        );
	    
        $worker = CerberusApplication::getActiveWorker();
        
        //====================================
	    // Undo functionality
        $last_action = new Model_TicketViewLastAction();
        $last_action->action = Model_TicketViewLastAction::ACTION_SURRENDER;

        if(is_array($ticket_ids))
        foreach($ticket_ids as $ticket_id) {
            $last_action->ticket_ids[$ticket_id] = array(
                DAO_Ticket::NEXT_WORKER_ID => $worker->id
            );
        }

        $last_action->action_params = $fields;
        
        CerberusDashboardView::setLastAction($view_id,$last_action);
        //====================================
	    
        DAO_Ticket::updateTicket($ticket_ids, $fields);
	    
	    echo ' ';
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
        
        CerberusDashboardView::setLastAction($view_id,$last_action);
        //====================================

        // [TODO] Bayes should really be smart enough to allow training of batches of IDs
	    if(!empty($ticket_ids))
	    foreach($ticket_ids as $id) {
	        CerberusBayes::markTicketAsNotSpam($id);
	    }
	    
        DAO_Ticket::updateTicket($ticket_ids, $fields);
	    
	    echo ' ';
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
        
        CerberusDashboardView::setLastAction($view_id,$last_action);
        //====================================
	    
        // {TODO] Batch
	    if(!empty($ticket_ids))
	    foreach($ticket_ids as $id) {
	        CerberusBayes::markTicketAsSpam($id);
	    }
	    
        DAO_Ticket::updateTicket($ticket_ids, $fields);
	    
	    echo ' ';
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
        
        CerberusDashboardView::setLastAction($view_id,$last_action);
        //====================================
	    
        DAO_Ticket::updateTicket($ticket_ids, $fields);
	    
	    echo ' ';
	    return;
	}
	
	function viewUndoAction() {
	    @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
	    @$clear = DevblocksPlatform::importGPC($_REQUEST['clear'],'integer',0);
	    $last_action = CerberusDashboardView::getLastAction($view_id);
	    
	    if($clear || empty($last_action)) {
            CerberusDashboardView::setLastAction($view_id,null);
   	        echo ' ';
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
	    
	    echo ' ';
	    return;
	}
	
	function viewSortByAction() {
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
	
	function viewPageAction() {
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
	
	function viewRefreshAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->assign('id',$id);

        $visit = CerberusApplication::getVisit();
		
		$view = DAO_Dashboard::getView($id);
        $active_dashboard_id = $visit->get(CerberusVisit::KEY_DASHBOARD_ID, 0);
		
		// [TODO]: This should be filterable by a specific view later as well using searchDAO.
		$viewActions = DAO_DashboardViewAction::getList();
		$tpl->assign('viewActions', $viewActions);
		
		// [TODO] Once this moves to the global scope and is cached I don't need to include it everywhere
		$workers = DAO_Worker::getList();
		$tpl->assign('workers', $workers);
		
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		
		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);
		
		$team_categories = DAO_Bucket::getTeams();
		$tpl->assign('team_categories', $team_categories);
		
		// Undo?
	    $last_action = CerberusDashboardView::getLastAction($id);
	    $tpl->assign('last_action', $last_action);
	    if(!empty($last_action) && !is_null($last_action->ticket_ids)) {
	        $tpl->assign('last_action_count', count($last_action->ticket_ids));
	    }

//	    // View Suggestions
//        $view_key = CerberusVisit::KEY_VIEW_TIPS . $active_dashboard_id;
//        $view_tips = $visit->get($view_key,array());
//        $view->tips = $view_tips; // [TODO] Formalize
	    
        // View Quick Moves
        $active_team_id = $visit->get(CerberusVisit::KEY_WORKSPACE_GROUP_ID, 0);
		if($active_team_id) {
			// [TODO] Move this into an API
	        $active_worker = CerberusApplication::getActiveWorker();
            $move_counts_str = DAO_WorkerPref::get($active_worker->id,''.DAO_WorkerPref::SETTING_TEAM_MOVE_COUNTS . $active_dashboard_id,serialize(array()));
            if(is_string($move_counts_str)) {
    	        // [TODO] We no longer need the move hash, do we?
	            // [TODO] Phase this out.
                $category_name_hash = DAO_Bucket::getCategoryNameHash();
                $tpl->assign('category_name_hash', $category_name_hash);
                
	            $categories = DAO_Bucket::getByTeam($active_team_id);
	            $tpl->assign('categories', $categories);
                 
                $move_counts = unserialize($move_counts_str);
                $tpl->assign('move_to_counts', array_slice($move_counts,0,10,true));
            }
		}
		
		$tpl->assign('dashboard_team_id', $active_team_id);
		
		$tpl->assign('view', $view);
		
		if(!empty($view)) {
			$tpl->cache_lifetime = "0";
			$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/ticket_view.tpl.php');
		} else {
			echo " ";
		}
	}
	
	// Post
	function saveTeamGeneralAction() {
	    @$team_id = DevblocksPlatform::importGPC($_REQUEST['team_id'],'integer');
	    
	    //========== GENERAL
	    @$signature = DevblocksPlatform::importGPC($_REQUEST['signature'],'string','');
	    @$sender_address = DevblocksPlatform::importGPC($_REQUEST['sender_address'],'string','');
	    @$sender_personal = DevblocksPlatform::importGPC($_REQUEST['sender_personal'],'string','');
	    @$spam_threshold = DevblocksPlatform::importGPC($_REQUEST['spam_threshold'],'integer',80);
	    @$spam_action = DevblocksPlatform::importGPC($_REQUEST['spam_action'],'integer',0);
	    @$spam_moveto = DevblocksPlatform::importGPC($_REQUEST['spam_action_moveto'],'integer',0);
	    
	    // [TODO] Move this into DAO_GroupSettings
	    DAO_Group::updateTeam($team_id, array(
	        DAO_Group::TEAM_SIGNATURE => $signature
	    ));
	    
	    // [TODO] Verify the sender address has been used in a 'To' header in the past
		// select count(header_value) from message_header where header_name = 'to' AND (header_value = 'sales@webgroupmedia.com' OR header_value = '<sales@webgroupmedia.com>');
		// DAO_MessageHeader::
	    
	    DAO_GroupSettings::set($team_id, DAO_GroupSettings::SETTING_REPLY_FROM, $sender_address);
	    DAO_GroupSettings::set($team_id, DAO_GroupSettings::SETTING_REPLY_PERSONAL, $sender_personal);
	    DAO_GroupSettings::set($team_id, DAO_GroupSettings::SETTING_SPAM_THRESHOLD, $spam_threshold);
	    DAO_GroupSettings::set($team_id, DAO_GroupSettings::SETTING_SPAM_ACTION, $spam_action);
	    DAO_GroupSettings::set($team_id, DAO_GroupSettings::SETTING_SPAM_ACTION_PARAM, $spam_moveto);
	       
        //DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','team',$team_id,'general')));
        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','team',$team_id,'general')));
	}
	
	function addTeamMemberAction() {
		@$team_id = DevblocksPlatform::importGPC($_REQUEST['team_id'],'integer');
		@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_ids'],'array');
		@$is_manager = DevblocksPlatform::importGPC($_REQUEST['is_manager'],'integer');

		foreach($worker_ids as $worker_id) {
			DAO_Group::setTeamMember($team_id, $worker_id, $is_manager);
		}
		
		//DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','team',$team_id,'members')));
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','team',$team_id,'members')));
	}
	
	function removeTeamMemberAction() {
		@$team_id = DevblocksPlatform::importGPC($_REQUEST['team_id'],'integer');
		@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'integer');

		DAO_Group::unsetTeamMember($team_id, $worker_id);
		
		//DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','team',$team_id,'members')));
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','team',$team_id,'members')));
	}
	
	function saveTeamBucketsAction() {
	    @$team_id = DevblocksPlatform::importGPC($_REQUEST['team_id'],'integer');
	    
	    //========== BUCKETS   
	    @$ids = DevblocksPlatform::importGPC($_REQUEST['ids'],'array');
	    @$add_str = DevblocksPlatform::importGPC($_REQUEST['add'],'string');
	    @$names = DevblocksPlatform::importGPC($_REQUEST['names'],'array');
	    @$deletes = DevblocksPlatform::importGPC($_REQUEST['deletes'],'array');
	    
	    // Updates
	    $cats = DAO_Bucket::getList($ids);
	    foreach($ids as $idx => $id) {
	        $cat = $cats[$id];
	        if(0 != strcasecmp($cat->name,$names[$idx])) {
	            DAO_Bucket::update($id, $names[$idx]);
	        }
	    }
	    
	    // Adds: Sort and insert team categories
	    $categories = CerberusApplication::parseCrlfString($add_str);

	    if(is_array($categories))
	    foreach($categories as $category) {
	        // [TODO] Dupe checking
	        $cat_id = DAO_Bucket::create($category, $team_id);
	    }
	    
	    if(!empty($deletes))
	        DAO_Bucket::delete(array_values($deletes));
	        
        //DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','team',$team_id,'buckets')));	        
        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','team',$team_id,'buckets')));
	}
	
	function saveTeamRoutingAction() {
	    @$team_id = DevblocksPlatform::importGPC($_REQUEST['team_id'],'integer');
	    @$deletes = DevblocksPlatform::importGPC($_REQUEST['deletes'],'array');
	    
	    if(!empty($team_id) && !empty($deletes)) {
	        DAO_TeamRoutingRule::delete($deletes);
	    }
	    
        //DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','team',$team_id,'routing')));   
        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','team',$team_id,'routing')));
   	}

	function changeDashboardAction() {
		$dashboard_id = DevblocksPlatform::importGPC($_POST['dashboard_id'], 'string', '');
		$team_id = 0;

		// Cache the current team id
		if(0 == strcmp('t',substr($dashboard_id,0,1))) {
		    $team_id = intval(substr($dashboard_id,1));
        }
		
		$visit = DevblocksPlatform::getSessionService()->getVisit();
		$visit->set(CerberusVisit::KEY_DASHBOARD_ID, $dashboard_id);
		$visit->set(CerberusVisit::KEY_WORKSPACE_GROUP_ID, $team_id);
        
//		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','workspaces')));
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','workspaces')));
	}
	
	function showBatchPanelAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);
		@$team_id = DevblocksPlatform::importGPC($_REQUEST['team_id'],'integer',0);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
//		$tpl->assign('id', $id);
		$tpl->assign('view_id', $view_id);
		$tpl->assign('team_id', $team_id);

	    $unique_sender_ids = array();
	    $unique_subjects = array();
	    
	    if(!empty($ids)) {
	        $ticket_ids = CerberusApplication::parseCsvString($ids);
	        if(empty($ticket_ids)) break;
	        $tickets = DAO_Ticket::getTickets($ticket_ids);
		    
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
		
		// Status
		$statuses = CerberusTicketStatus::getOptions();
		$tpl->assign('statuses', $statuses);

		// Spam Training
		$training = CerberusTicketSpamTraining::getOptions();
		$tpl->assign('training', $training);
		
		// [TODO] Cache these
		// Teams
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		// Categories
		$team_categories = DAO_Bucket::getTeams();
		$tpl->assign('team_categories', $team_categories);
				
		// Load action object to populate fields
//		$action = DAO_DashboardViewAction::get($id);
//		$tpl->assign('action', $action);
		
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/rpc/batch_panel.tpl.php');
	}
	
	// Ajax
	function doBatchUpdateAction() {
	    @$ticket_id_str = DevblocksPlatform::importGPC($_REQUEST['ticket_ids'],'string');
	    @$shortcut_name = DevblocksPlatform::importGPC($_REQUEST['shortcut_name'],'string','');

	    @$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
	    @$senders = DevblocksPlatform::importGPC($_REQUEST['senders'],'string','');
	    @$subjects = DevblocksPlatform::importGPC($_REQUEST['subjects'],'string','');
	    @$always_do_for_team = DevblocksPlatform::importGPC($_REQUEST['always_do_for_team'],'integer',0);
	    
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
	    $viewMgr = CerberusApplication::getVisit()->get(CerberusVisit::KEY_VIEW_MANAGER); /* @var CerberusStaticViewManager $viewMgr */
		$view = $viewMgr->getView($view_id); /* @var $view CerberusDashboardView */

		@$closed = DevblocksPlatform::importGPC($_POST['closed'],'string','');
		@$spam = DevblocksPlatform::importGPC($_POST['spam'],'string','');
		@$team = DevblocksPlatform::importGPC($_POST['team'],'string','');

		$ticket_ids = CerberusApplication::parseCsvString($ticket_id_str);
        $subjects = CerberusApplication::parseCrlfString($subjects);
        $senders = CerberusApplication::parseCrlfString($senders);
		
		$do = array();
		
		if(!is_null($closed))
			$do['closed'] = $closed;
		if(!is_null($spam))
			$do['spam'] = $spam;
		if(!is_null($team))
			$do['team'] = $team;
		
	    $data = array();
	    if($filter == 'sender')
	        $data = $senders;
	    elseif($filter == 'subject')
	        $data = $subjects;
			
		$view->doBulkUpdate($filter, '', $data, $do, $ticket_ids, $always_do_for_team);
		
		echo ' ';
		return;
	}
	
	// Post
	function removeRssAction() {
		@$id = DevblocksPlatform::importGPC($_POST['id']);
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(null != ($feed = DAO_TicketRss::getId($id)) && $feed->worker_id == $active_worker->id) {
			DAO_TicketRss::delete($id);
		}
		
		//DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','rss')));
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','rss')));
	}
	
	// ajax
	function showViewRssAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);
		
	    $viewMgr = CerberusApplication::getVisit()->get(CerberusVisit::KEY_VIEW_MANAGER); /* @var CerberusStaticViewManager $viewMgr */
		$view = $viewMgr->getView($view_id); /* @var $view CerberusDashboardView */
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->assign('view_id', $view_id);
		$tpl->assign('view', $view);
		
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/rpc/view_rss_builder.tpl.php');
	}
	
	// post
	function viewBuildRssAction() {
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id']);
		@$title = DevblocksPlatform::importGPC($_POST['title']);
		$active_worker = CerberusApplication::getActiveWorker();

	    $viewMgr = CerberusApplication::getVisit()->get(CerberusVisit::KEY_VIEW_MANAGER); /* @var CerberusStaticViewManager $viewMgr */
		$view = $viewMgr->getView($view_id); /* @var $view CerberusDashboardView */
		
		$now = time();
		$hash = md5($title.$view_id.$active_worker->id.$now);
		
		$params = array(
			'params' => $view->params,
			'sort_by' => $view->renderSortBy,
			'sort_asc' => $view->renderSortAsc
		);
		
		$fields = array(
			DAO_TicketRss::TITLE => $title, 
			DAO_TicketRss::HASH => $hash, 
			DAO_TicketRss::CREATED => $now,
			DAO_TicketRss::WORKER_ID => $active_worker->id,
			DAO_TicketRss::PARAMS => serialize($params)
		);
		$feed_id = DAO_TicketRss::create($fields);
				
		//DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','rss')));
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','rss')));
	}
	
	function showViewActionsAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->assign('id', $id);
		$tpl->assign('view_id', $view_id);

		// Status
		$statuses = CerberusTicketStatus::getOptions();
		$tpl->assign('statuses', $statuses);
		// Spam Training
		$training = CerberusTicketSpamTraining::getOptions();
		$tpl->assign('training', $training);
		// Teams
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		// Categories
		$team_categories = DAO_Bucket::getTeams();
		$tpl->assign('team_categories', $team_categories);
				
		// Load action object to populate fields
		$action = DAO_DashboardViewAction::get($id);
		$tpl->assign('action', $action);
		
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/rpc/view_actions_panel.tpl.php');
	}
	
	function saveViewActionPanelAction() {
		@$action_id = DevblocksPlatform::importGPC($_POST['action_id']);
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id']);
		@$title = DevblocksPlatform::importGPC($_POST['title']);
		@$closed = DevblocksPlatform::importGPC($_POST['closed']);
		@$spam = DevblocksPlatform::importGPC($_POST['spam']);
		@$team = DevblocksPlatform::importGPC($_POST['team'],'string');
		@$delete = DevblocksPlatform::importGPC($_POST['delete'],'integer');
		
		$params = array();			
		
		if($delete) {
		    DAO_DashboardViewAction::delete($action_id);
		    
		} else {
			if(!is_null($closed))
				$params['closed'] = $closed;
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
		}
		
		echo ' ';
	}
	
	function runActionAction() {
		@$id = DevblocksPlatform::importGPC($_POST['id'],'string');
		@$action_id = DevblocksPlatform::importGPC($_POST['action_id'],'integer');
		@$ticket_ids = DevblocksPlatform::importGPC($_POST['ticket_id'],'array');
		
		if(empty($action_id) || empty($ticket_ids))
			return;
		
		$action = DAO_DashboardViewAction::get($action_id);
		if(empty($action)) return;
		
//		$tickets = DAO_Ticket::getTickets($ticket_ids);
//		if(empty($tickets)) return;
		
		// Run the action components
		$action->run($ticket_ids);
		
		echo ' ';
	}
	
	function saveTaskPanelAction() {
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
	
	function customizeAction() {
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
	
	function saveCustomizeAction() {
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
	
	function searchviewAction() {
		$visit = CerberusApplication::getVisit();
		$viewManager = $visit->get(CerberusVisit::KEY_VIEW_MANAGER); /* @var $viewManager CerberusStaticViewManager */
	    
	    $response = DevblocksPlatform::getHttpRequest();
	    $path = $response->path;
	    array_shift($path); // tickets
	    array_shift($path); // searchview
	    $id = array_shift($path);

		$view = $viewManager->getView($id);

		if(!empty($view->params)) {
		    $params = array();
		    
		    // Index by field name for search system
		    if(is_array($view->params))
		    foreach($view->params as $criteria) { /* @var $criteria DevblocksSearchCriteria */
                $params[$criteria->field] = $criteria;
		    }
		}
		
		$search_view = $viewManager->getView(CerberusApplication::VIEW_SEARCH);
		$search_view->params = $params;
		$search_view->renderPage = 0;
		$viewManager->setView($search_view->id, $search_view);

		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','search')));
	}
	
	function addViewAction() {
		// [JAS]: [TODO] Use a real dashboard ID here.
		$view_id = DAO_Dashboard::createView('New Ticket List', 1, 10);
		
		$fields = array(
			'view_columns' => serialize(array(
				SearchFields_Ticket::TICKET_NEXT_ACTION,
				SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
				SearchFields_Ticket::TICKET_CREATED_DATE,
				SearchFields_Ticket::TEAM_NAME,
			))
		);
		DAO_Dashboard::updateView($view_id,$fields);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','workspaces')));
	}
	
	// [JAS]: Search Functions =================================================
	
	function getCriteriaAction() {
		@$field = DevblocksPlatform::importGPC($_REQUEST['field']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->cache_lifetime = "0";
		
		switch($field) {
			case SearchFields_Ticket::TICKET_MASK:
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/search/criteria/ticket_mask.tpl.php');
				break;
				
			case SearchFields_Ticket::TICKET_CLOSED:
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/search/criteria/ticket_status.tpl.php');
				break;
				
			case SearchFields_Ticket::TICKET_SPAM_SCORE:
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/search/criteria/ticket_spam_score.tpl.php');
				break;
				
			case SearchFields_Ticket::TICKET_SUBJECT:
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/search/criteria/ticket_subject.tpl.php');
				break;
				
			case SearchFields_Ticket::TICKET_CREATED_DATE:
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/search/criteria/ticket_created.tpl.php');
				break;
				
			case SearchFields_Ticket::TICKET_UPDATED_DATE:
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/search/criteria/ticket_updated.tpl.php');
				break;
				
			case SearchFields_Ticket::TICKET_FIRST_WROTE:
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/search/criteria/ticket_first_wrote.tpl.php');
				break;
				
			case SearchFields_Ticket::TICKET_LAST_WROTE:
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/search/criteria/ticket_last_wrote.tpl.php');
				break;
				
//			case SearchFields_Ticket::REQUESTER_ADDRESS:
//				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/search/criteria/requester_email.tpl.php');
//				break;

			case SearchFields_Ticket::TICKET_NEXT_ACTION:
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/search/criteria/ticket_next_action.tpl.php');
				break;
				
			case SearchFields_Ticket::TICKET_LAST_ACTION_CODE:
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/search/criteria/ticket_last_action.tpl.php');
				break;
				
			case SearchFields_Ticket::TICKET_LAST_WORKER_ID:
				$workers = DAO_Worker::getList(); // [TODO] ::getAll()
				$tpl->assign('workers', $workers);
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/search/criteria/ticket_last_worker.tpl.php');
				break;

			case SearchFields_Ticket::TICKET_NEXT_WORKER_ID:
				$workers = DAO_Worker::getList(); // [TODO] ::getAll()
				$tpl->assign('workers', $workers);
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/search/criteria/ticket_next_worker.tpl.php');
				break;

			case SearchFields_Ticket::TICKET_MESSAGE_CONTENT:
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/search/criteria/message_content.tpl.php');
				break;
				
			case SearchFields_Ticket::TEAM_ID:
				$teams = DAO_Group::getAll();
				$tpl->assign('teams', $teams);
				
				$team_categories = DAO_Bucket::getTeams();
				$tpl->assign('team_categories', $team_categories);
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/search/criteria/ticket_team.tpl.php');
				break;
		}
	}
	
	function addCriteriaAction() {
		$view = DAO_Dashboard::getView(CerberusApplication::VIEW_SEARCH);
		
		$params = $view->params;
		@$field = DevblocksPlatform::importGPC($_REQUEST['field']);
		@$oper = DevblocksPlatform::importGPC($_REQUEST['oper']);

		// [JAS]: Auto wildcards
	    $wildcards = 
	        ($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE) 
	        ? true : false;
		
		switch($field) {
			case SearchFields_Ticket::TICKET_MASK:
				@$mask = strtoupper(DevblocksPlatform::importGPC($_REQUEST['mask'],'string',''));
				if(!empty($mask)) {
				    if($wildcards && false===strpos($mask,'*'))
				        $mask = '*' . $mask . '*';
				    $params[$field] = new DevblocksSearchCriteria($field,$oper,$mask);
				} else {
				    unset($params[$field]);
				}
				break;
			case SearchFields_Ticket::TICKET_CLOSED:
				@$status = DevblocksPlatform::importGPC($_REQUEST['status'],'array',array());
				if(!empty($status) && is_array($status)) {
				    $params[$field] = new DevblocksSearchCriteria($field,$oper,$status);
				} else {
				    unset($params[$field]);
				}
				break;
			case SearchFields_Ticket::TICKET_SPAM_SCORE:
			    @$score = DevblocksPlatform::importGPC($_REQUEST['score'],'integer',null);
				if(!is_null($score) && is_numeric($score)) {
				    $params[$field] = new DevblocksSearchCriteria($field,$oper,intval($score)/100);
				} else {
				    unset($params[$field]);
				}
			    break;
			case SearchFields_Ticket::TICKET_SUBJECT:
				@$subject = DevblocksPlatform::importGPC($_REQUEST['subject']);
			    if($wildcards && false===strpos($subject,'*'))
			        $subject = '*' . $subject . '*';
				$params[$field] = new DevblocksSearchCriteria($field,$oper,$subject);
				break;
			case SearchFields_Ticket::TICKET_CREATED_DATE:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','yesterday');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','today');
				$params[$field] = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
			case SearchFields_Ticket::TICKET_UPDATED_DATE:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','yesterday');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','today');
				$params[$field] = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
			case SearchFields_Ticket::TICKET_FIRST_WROTE:
				@$email = DevblocksPlatform::importGPC($_REQUEST['email']);
			    if($wildcards && false===strpos($email,'*'))
			        $email = '*' . $email . '*';
				$params[$field] = new DevblocksSearchCriteria($field,$oper,strtolower($email));
				break;
			case SearchFields_Ticket::TICKET_LAST_WROTE:
				@$email = DevblocksPlatform::importGPC($_REQUEST['email']);
			    if($wildcards && false===strpos($email,'*'))
			        $email = '*' . $email . '*';
				$params[$field] = new DevblocksSearchCriteria($field,$oper,strtolower($email));
				break;
//			case SearchFields_Ticket::REQUESTER_ADDRESS:
//				@$requester = DevblocksPlatform::importGPC($_REQUEST['requester']);
//				$params[$field] = new DevblocksSearchCriteria($field,$oper,$requester);
//				break;
			case SearchFields_Ticket::TICKET_MESSAGE_CONTENT:
				@$content = DevblocksPlatform::importGPC($_REQUEST['content']);
				$params[$field] = new DevblocksSearchCriteria($field,$oper,'*'.$content.'*');
				break;
			case SearchFields_Ticket::TICKET_NEXT_ACTION:
				@$action = DevblocksPlatform::importGPC($_REQUEST['action']);
			    if($wildcards && false===strpos($action,'*'))
			        $action = '*' . $action . '*';
				$params[$field] = new DevblocksSearchCriteria($field,$oper,$action);
				break;
			case SearchFields_Ticket::TICKET_LAST_ACTION_CODE:
				@$last_action_code = DevblocksPlatform::importGPC($_REQUEST['last_action'],'array',array());
				$params[$field] = new DevblocksSearchCriteria($field,$oper,$last_action_code);
				break;
			case SearchFields_Ticket::TICKET_LAST_WORKER_ID:
				@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$params[$field] = new DevblocksSearchCriteria($field,$oper,$worker_id);
				break;
			case SearchFields_Ticket::TICKET_NEXT_WORKER_ID:
				@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$params[$field] = new DevblocksSearchCriteria($field,$oper,$worker_id);
				break;
			case SearchFields_Ticket::TEAM_ID:
				@$team_ids = DevblocksPlatform::importGPC($_REQUEST['team_id'],'array');
				@$bucket_ids = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'array');

				if(!empty($team_ids))
					$params[SearchFields_Ticket::TEAM_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TEAM_ID,$oper,$team_ids);
				if(!empty($bucket_ids))
					$params[SearchFields_Ticket::TICKET_CATEGORY_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CATEGORY_ID,$oper,$bucket_ids);
					
				break;
		}
		
		$fields = array(
			'params' => serialize($params),
			'page' => 0
		);
		DAO_Dashboard::updateView(CerberusApplication::VIEW_SEARCH, $fields);
		
		//DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','search')));
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','search')));
	}

	// Form
	function removeCriteriaAction() {
		$view = DAO_Dashboard::getView(CerberusApplication::VIEW_SEARCH);

		@$params =& $view->params;
		
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;

		@$field = $stack[2];		

		if(isset($params[$field]))
			unset($params[$field]);
		
		$fields = array(
			'params' => serialize($params),
			'page' => 0
		);
		DAO_Dashboard::updateView(CerberusApplication::VIEW_SEARCH, $fields);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','search')));
	}
	
	function resetCriteriaAction() {
		DAO_Dashboard::updateView(CerberusApplication::VIEW_SEARCH, array(
			'sort_by' => null,
			'params' => serialize(array(
				SearchFields_Ticket::TICKET_CLOSED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,DevblocksSearchCriteria::OPER_EQ,0)
			))
		));
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','search')));
	}
	
	function getLoadSearchAction() {
		@$divName = DevblocksPlatform::importGPC($_REQUEST['divName']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->cache_lifetime = "0";
		
		$tpl->assign('divName',$divName);
		
		$searches = DAO_Search::getSavedSearches(1); /* @var $searches CerberusDashboardView[] */
		$tpl->assign('searches', $searches);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/search/rpc/load_search.tpl.php');
	}
	
	function loadSearchAction() {
		@$search_id = DevblocksPlatform::importGPC($_REQUEST['search_id']);
		
		$view = DAO_Dashboard::getView($search_id);
		
		// [TODO] Load the saved search into the view
		DAO_Dashboard::updateView(CerberusApplication::VIEW_SEARCH, array(
			'name' => $view->name,
			'params' => serialize($view->params)
		));
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','search')));
	}
	
	function getSaveSearchAction() {
		@$divName = DevblocksPlatform::importGPC($_REQUEST['divName']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->cache_lifetime = "0";

		$tpl->assign('divName',$divName);
		
		$views = DAO_Dashboard::getViews(0);
		$tpl->assign('views', $views);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/search/rpc/save_search.tpl.php');
	}
	
	function saveSearchAction() {
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
	
	function deleteSearchAction() {
		@$search_id = $_SESSION['search_id'];
		
		DAO_Dashboard::deleteView($search_id);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','search')));
	}
	
};

class ChConfigurationPage extends CerberusPageExtension  {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	// [TODO] Refactor to isAuthorized
	function isVisible() {
		$worker = CerberusApplication::getActiveWorker();
		
		if(empty($worker)) {
			return false;
		} elseif($worker->is_superuser) {
			return true;
		}
	}
	
	function getActivity() {
	    return new Model_Activity('activity.config');
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$worker = CerberusApplication::getActiveWorker();
		if(!$worker || !$worker->is_superuser) {
			echo "Access denied.";
			return;
		}
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		$command = array_shift($stack);
		
		switch(array_shift($stack)) {
		    default:
			case 'general':
				$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/general/index.tpl.php');
				break;
				
			case 'fnr':
				$topics = DAO_FnrTopic::getWhere();
				$tpl->assign('topics', $topics);
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/fnr/index.tpl.php');
				break;
				
			case 'mail':
				$routing = DAO_Mail::getMailboxRouting();
				$tpl->assign('routing', $routing);
		
				$teams = DAO_Group::getAll();
				$tpl->assign('teams', $teams);
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/mail/index.tpl.php');				
				break;
				
			case 'workflow':
				/*
				 * [IMPORTANT -- Yes, this is simply a line in the sand.]
				 * You're welcome to modify the code to meet your needs, but please respect 
				 * our licensing.  Buy a legitimate copy to help support the project!
				 * http://www.cerberusweb.com/
				 */
				$workers = DAO_Worker::getList();
				$tpl->assign('workers', $workers);

				$teams = DAO_Group::getAll();
				$tpl->assign('teams', $teams);
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/workflow/index.tpl.php');				
				break;
				
			case 'extensions':
				// Auto synchronize when viewing Config->Extensions
		        DevblocksPlatform::readPlugins();
				
				$plugins = DevblocksPlatform::getPluginRegistry();
				unset($plugins['cerberusweb.core']);
				$tpl->assign('plugins', $plugins);
				
				$points = DevblocksPlatform::getExtensionPoints();
				$tpl->assign('points', $points);
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/extensions/index.tpl.php');				
				break;

			case 'licenses':
				$license = CerberusLicense::getInstance();
				$tpl->assign('license', $license);

				$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/licenses/index.tpl.php');				
				break;
				
			case 'jobs':
				
			    switch(array_shift($stack)) {
			        case 'manage':
					    $id = array_shift($stack);

					    $manifest = DevblocksPlatform::getExtension($id);
					    $job = $manifest->createInstance();
					    
					    if(!$job instanceof CerberusCronPageExtension)
					        die("Bad!");
			            
					    $tpl->assign('job', $job);
					        
			            $tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/jobs/job.tpl.php');
			            break;
			            
			        default:
					    $jobs = DevblocksPlatform::getExtensions('cerberusweb.cron', true);
						$tpl->assign('jobs', $jobs);
						
					    $tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/jobs/index.tpl.php');
			            break;
			    }
			    
			    break;
			    
		} // end switch
		
	}
	
	// Ajax
	function showFnrTopicPanelAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		if(!empty($id)) {
			$topic = DAO_FnrTopic::get($id);
			$tpl->assign('topic', $topic);
		}
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/fnr/topic_panel.tpl.php');
	}
	
	// Ajax
	function showFnrResourcePanelAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$topics = DAO_FnrTopic::getWhere();
		$tpl->assign('topics', $topics);
		
		if(!empty($id)) {
			$resource = DAO_FnrExternalResource::get($id);
			$tpl->assign('resource', $resource);
		}
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/fnr/external_resource_panel.tpl.php');
	}
	
	// Post
	function doFnrTopicAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string','');
		@$delete = DevblocksPlatform::importGPC($_REQUEST['delete'],'integer',0);

		if(DEMO_MODE) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','fnr')));
			return;
		}
		
		if(empty($id)) { // add
			$fields = array(
				DAO_FnrTopic::NAME => $name
			);
			$topic_id = DAO_FnrTopic::create($fields);
			
		} else { // edit
			if(!empty($delete)) {
				DAO_FnrTopic::delete($id);
				
			} else {
				$fields = array(
					DAO_FnrTopic::NAME => $name
				);
				$topic_id = DAO_FnrTopic::update($id, $fields);
			}
			
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','fnr')));
	}
	
	// Post
	function doFnrResourceAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string','');
		@$url = DevblocksPlatform::importGPC($_REQUEST['url'],'string','');
		@$topic_id = DevblocksPlatform::importGPC($_REQUEST['topic_id'],'integer',0);
		@$topic_name = DevblocksPlatform::importGPC($_REQUEST['topic_name'],'string','');
		@$delete = DevblocksPlatform::importGPC($_REQUEST['delete'],'integer',0);
		
		if(DEMO_MODE) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','fnr')));
			return;
		}
		
		if(empty($topic_id) && !empty($topic_name)) {
			$fields = array(
				DAO_FnrTopic::NAME => $topic_name
			);
			$topic_id = DAO_FnrTopic::create($fields);
		}
		
		if(empty($id)) { // add
			$fields = array(
				DAO_FnrExternalResource::NAME => $name,
				DAO_FnrExternalResource::TOPIC_ID => $topic_id,
				DAO_FnrExternalResource::URL => $url,
			);
			$id = DAO_FnrExternalResource::create($fields);
			
		} else { // edit
			if(!empty($delete)) {
				DAO_FnrExternalResource::delete($id);
				
			} else {
				$fields = array(
					DAO_FnrExternalResource::NAME => $name,
					DAO_FnrExternalResource::TOPIC_ID => $topic_id,
					DAO_FnrExternalResource::URL => $url,
				);
				DAO_FnrExternalResource::update($id, $fields);
			}
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','fnr')));
	}
	
	// Post
	function saveJobAction() {
		$worker = CerberusApplication::getActiveWorker();
		if(!$worker || !$worker->is_superuser) {
			echo "Access denied.";
			return;
		}
		
		if(DEMO_MODE) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','jobs')));
			return;
		}
		
	    // [TODO] Save the job changes
	    @$id = DevblocksPlatform::importGPC($_REQUEST['id'],'string','');
	    @$enabled = DevblocksPlatform::importGPC($_REQUEST['enabled'],'integer',0);
	    @$locked = DevblocksPlatform::importGPC($_REQUEST['locked'],'integer',0);
	    @$duration = DevblocksPlatform::importGPC($_REQUEST['duration'],'integer',5);
	    @$term = DevblocksPlatform::importGPC($_REQUEST['term'],'string','m');
	    @$starting = DevblocksPlatform::importGPC($_REQUEST['starting'],'string','');
	    	    
	    $manifest = DevblocksPlatform::getExtension($id);
	    $job = $manifest->createInstance(); /* @var $job CerberusCronPageExtension */

	    if(!empty($starting)) {
		    $starting_time = strtotime($starting);
		    if(false === $starting_time) $starting_time = time();
		    $starting_time -= CerberusCronPageExtension::getIntervalAsSeconds($duration, $term);
    	    $job->setParam(CerberusCronPageExtension::PARAM_LASTRUN, $starting_time);
	    }
	    
	    if(!$job instanceof CerberusCronPageExtension)
	        die("Bad!");
	    
	    // [TODO] This is really kludgey
	    $job->setParam(CerberusCronPageExtension::PARAM_ENABLED, $enabled);
	    $job->setParam(CerberusCronPageExtension::PARAM_LOCKED, $locked);
	    $job->setParam(CerberusCronPageExtension::PARAM_DURATION, $duration);
	    $job->setParam(CerberusCronPageExtension::PARAM_TERM, $term);
	    
	    $job->saveConfigurationAction();
	    	    
	    DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','jobs')));
	}
	
	// Post
	function saveLicensesAction() {
		@$key = DevblocksPlatform::importGPC($_POST['key'],'string','');

		if(DEMO_MODE) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','licenses')));
			return;
		}
		
		if(empty($key)) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','licenses','empty')));
			return;
		}
		
		// Clean off the wrapper
		@$lines = explode("\r\n", trim($key));
		$company = '';
		$features = array();
		$key = '';
		$valid=0;
		if(is_array($lines))
		foreach($lines as $line) {
			if(0==strcmp(substr($line,0,3),'---')) {
				$valid++;continue;
			}
			if(preg_match("/^(.*?)\: (.*?)$/",$line,$matches)) {
				if(0==strcmp($matches[1],"Company"))
					$company = $matches[2];
				if(0==strcmp($matches[1],"Feature"))
					$features[$matches[2]] = true;
			} else {
				$key .= trim($line);
			}
		}
		
		if(2!=$valid || 0!=$key%4) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','licenses','invalid')));
			return;
		}
		
		// Save for reuse in form in case we need to redraw
		$settings = CerberusSettings::getInstance();
		$settings->set('company', trim($company));
		
		ksort($features);
		
		/*
		 * [IMPORTANT -- Yes, this is simply a line in the sand.]
		 * You're welcome to modify the code to meet your needs, but please respect 
		 * our licensing.  Buy a legitimate copy to help support the project!
		 * http://www.cerberusweb.com/
		 */
		$license = CerberusLicense::getInstance();
		// $license['name'] = CerberusHelper::strip_magic_quotes($company,'string');
		$license['name'] = $company;
		$license['features'] = $features;
		$license['key'] = CerberusHelper::strip_magic_quotes($key,'string');
		
		$settings->set(CerberusSettings::LICENSE, serialize($license));
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','licenses')));
	}
	
	// Ajax
	function getWorkerAction() {
		$worker = CerberusApplication::getActiveWorker();
		if(!$worker || !$worker->is_superuser) {
			echo "Access denied.";
			return;
		}
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$worker = DAO_Worker::getAgent($id);
		$tpl->assign('worker', $worker);
		
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/workflow/edit_worker.tpl.php');
	}
	
	// Post
	function saveWorkerAction() {
		$worker = CerberusApplication::getActiveWorker();
		if(!$worker || !$worker->is_superuser) {
			echo "Access denied.";
			return;
		}
		
		if(DEMO_MODE) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','workflow')));
			return;
		}
		
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

		// Global privs
		@$can_delete = DevblocksPlatform::importGPC($_POST['can_delete'],'integer');
		
		// [TODO] The superuser set bit here needs to be protected by ACL
		
		if(empty($name)) $name = "No Name";
		
		if(!empty($id) && !empty($delete)) {
			DAO_Worker::deleteAgent($id);
			$active_worker = CerberusApplication::getActiveWorker();
			//[mdf] if deleting one's self, logout
			if($active_worker->id == $id) {
				DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login','signout')));
				exit;
			}
		} else {
			if(empty($id) && null == DAO_Worker::lookupAgentEmail($email)) {
				$workers = DAO_Worker::getList();
				$license = CerberusLicense::getInstance();
				if ((!empty($license) && !empty($license['key'])) || count($workers) < 3) {
					$id = DAO_Worker::create($email, $password, '', '', '');
				}
				else {
					//not licensed and worker limit reached
					DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','workflow')));
					return;
				}
			}
		    
			$fields = array(
				DAO_Worker::FIRST_NAME => $first_name,
				DAO_Worker::LAST_NAME => $last_name,
				DAO_Worker::TITLE => $title,
				DAO_Worker::EMAIL => $email,
				DAO_Worker::IS_SUPERUSER => $is_superuser,
				DAO_Worker::CAN_DELETE => $can_delete,
			);
			
			// if we're resetting the password
			if(!empty($password)) {
				$fields[DAO_Worker::PASSWORD] = md5($password);
			}
			
			DAO_Worker::updateAgent($id, $fields);
			DAO_Worker::setAgentTeams($id, $team_id);
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','workflow')));
	}
	
	// Ajax
	function getTeamAction() {
		$worker = CerberusApplication::getActiveWorker();
		if(!$worker || !$worker->is_superuser) {
			echo "Access denied.";
			return;
		}
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		
		@$team = $teams[$id];
		$tpl->assign('team', $team);
		
		$workers = DAO_Worker::getList();
		$tpl->assign('workers', $workers);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/workflow/edit_team.tpl.php');
	}
	
	// Post
	function saveTeamAction() {
		$worker = CerberusApplication::getActiveWorker();
		if(!$worker || !$worker->is_superuser) {
			echo "Access denied.";
			return;
		}
		
		if(DEMO_MODE) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','workflow')));
			return;
		}
		
		@$id = DevblocksPlatform::importGPC($_POST['id']);
		@$name = DevblocksPlatform::importGPC($_POST['name']);
		@$leader_id = DevblocksPlatform::importGPC($_POST['leader_id'],'integer',0);
		@$delete = DevblocksPlatform::importGPC($_POST['delete_box']);
		@$delete_move_id = DevblocksPlatform::importGPC($_POST['delete_move_id'],'integer',0);
		
		if(empty($name)) $name = "No Name";
		
		if(!empty($id) && !empty($delete)) {
			if(!empty($delete_move_id)) {
				$fields = array(
					DAO_Ticket::TEAM_ID => $delete_move_id
				);
				$where = sprintf("%s=%d",
					DAO_Ticket::TEAM_ID,
					$id
				);
				DAO_Ticket::updateWhere($fields, $where);
				
				DAO_Group::deleteTeam($id);
			}
			
		} elseif(!empty($id)) {
			$fields = array(
				DAO_Group::TEAM_NAME => $name,
			);
			DAO_Group::updateTeam($id, $fields);
			
		} else {
			$fields = array(
				DAO_Group::TEAM_NAME => $name,
			);
			$id = DAO_Group::createTeam($fields);
			
			DAO_Group::setTeamMember($id, $leader_id, true);
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','workflow')));
	}
	
	// Post
	function saveSettingsAction() {
		$worker = CerberusApplication::getActiveWorker();
		if(!$worker || !$worker->is_superuser) {
			echo "Access denied.";
			return;
		}
		
		if(DEMO_MODE) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','general')));
			return;
		}
		
	    @$title = DevblocksPlatform::importGPC($_POST['title'],'string');
	    @$logo = DevblocksPlatform::importGPC($_POST['logo'],'string');
	    @$attachments_enabled = DevblocksPlatform::importGPC($_POST['attachments_enabled'],'integer',0);
	    @$attachments_max_size = DevblocksPlatform::importGPC($_POST['attachments_max_size'],'integer',10);
	    @$authorized_ips_str = DevblocksPlatform::importGPC($_POST['authorized_ips'],'string','');
	    
	    $settings = CerberusSettings::getInstance();
	    $settings->set(CerberusSettings::HELPDESK_TITLE, $title);
	    $settings->set(CerberusSettings::HELPDESK_LOGO_URL, $logo); // [TODO] Enforce some kind of max resolution?
	    $settings->set(CerberusSettings::ATTACHMENTS_ENABLED, $attachments_enabled);
	    $settings->set(CerberusSettings::ATTACHMENTS_MAX_SIZE, $attachments_max_size);
	    $settings->set(CerberusSettings::AUTHORIZED_IPS, $authorized_ips_str);
	    
	    DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','general')));
	}
	
	// Form Submit
	function saveOutgoingMailSettingsAction() {
		$worker = CerberusApplication::getActiveWorker();
		if(!$worker || !$worker->is_superuser) {
			echo "Access denied.";
			return;
		}
		
		if(DEMO_MODE) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','mail')));
			return;
		}
		
	    @$default_reply_address = DevblocksPlatform::importGPC($_REQUEST['sender_address'],'string');
	    @$default_reply_personal = DevblocksPlatform::importGPC($_REQUEST['sender_personal'],'string');
	    @$default_signature = DevblocksPlatform::importGPC($_POST['default_signature'],'string');
	    @$smtp_host = DevblocksPlatform::importGPC($_REQUEST['smtp_host'],'string');
	    @$smtp_auth_enabled = DevblocksPlatform::importGPC($_REQUEST['smtp_auth_enabled'],'integer', 0);
	    @$smtp_auth_user = DevblocksPlatform::importGPC($_REQUEST['smtp_auth_user'],'string');
	    @$smtp_auth_pass = DevblocksPlatform::importGPC($_REQUEST['smtp_auth_pass'],'string');
	    
	    $settings = CerberusSettings::getInstance();
	    $settings->set(CerberusSettings::DEFAULT_REPLY_FROM, $default_reply_address);
	    $settings->set(CerberusSettings::DEFAULT_REPLY_PERSONAL, $default_reply_personal);
	    $settings->set(CerberusSettings::DEFAULT_SIGNATURE, $default_signature);
	    $settings->set(CerberusSettings::SMTP_HOST, $smtp_host);
	    $settings->set(CerberusSettings::SMTP_AUTH_ENABLED, $smtp_auth_enabled);
	    $settings->set(CerberusSettings::SMTP_AUTH_USER, $smtp_auth_user);
	    $settings->set(CerberusSettings::SMTP_AUTH_PASS, $smtp_auth_pass);
	    
	    DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','mail')));
	}
	
	// Ajax
	function ajaxGetRoutingAction() {
		$worker = CerberusApplication::getActiveWorker();
		if(!$worker || !$worker->is_superuser) {
			echo "Access denied.";
			return;
		}
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$routing = DAO_Mail::getMailboxRouting();
		$tpl->assign('routing', $routing);

		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/mail/mail_routing.tpl.php');
	}
	
	// Form Submit
	function saveRoutingAction() {
		$worker = CerberusApplication::getActiveWorker();
		if(!$worker || !$worker->is_superuser) {
			echo "Access denied.";
			return;
		}
		
		if(DEMO_MODE) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','mail')));
			return;
		}
		
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
	function ajaxDeleteRoutingAction() {
		$worker = CerberusApplication::getActiveWorker();
		if(!$worker || !$worker->is_superuser) {
			echo "Access denied.";
			return;
		}
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		DAO_Mail::deleteMailboxRouting($id);
	}
	
	// Ajax
	function getMailRoutingAddAction() {
		$worker = CerberusApplication::getActiveWorker();
		if(!$worker || !$worker->is_superuser) {
			echo "Access denied.";
			return;
		}
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$teams = DAO_Group::getTeams();
		$tpl->assign('teams', $teams);

		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/mail/mail_routing_add.tpl.php');
	}
	
	function savePluginsAction() {
		$worker = CerberusApplication::getActiveWorker();
		if(!$worker || !$worker->is_superuser) {
			echo "Access denied.";
			return;
		}
		
		if(DEMO_MODE) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','extensions')));
			return;
		}
		
//		if(!ACL_TypeMonkey::hasPriv(ACL_TypeMonkey::SETUP)) return;
		
		@$plugins_enabled = DevblocksPlatform::importGPC($_REQUEST['plugins_enabled'],'array');
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

class ChWelcomePage extends CerberusPageExtension {
	function __construct($manifest) {
		parent::__construct($manifest);

//		$path = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
//		
//		DevblocksPlatform::registerClasses($path. 'api/DAO.php', array(
//		    'DAO_Faq'
//		));
	}
		
	function isVisible() {
		// check login
		$visit = CerberusApplication::getVisit();
		
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

		$tpl->display('file:' . dirname(__FILE__) . '/templates/welcome/index.tpl.php');
	}
};

class ChContactsPage extends CerberusPageExtension {
	function __construct($manifest) {
		parent::__construct($manifest);

//		$path = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
//		
//		DevblocksPlatform::registerClasses($path. 'api/DAO.php', array(
//		    'DAO_Faq'
//		));
	}
		
	function isVisible() {
		// check login
		$visit = CerberusApplication::getVisit();
		
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
		
		$visit = CerberusApplication::getVisit();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;

		array_shift($stack); // contacts
		
		switch(array_shift($stack)) {
			case 'import':
				switch(array_shift($stack)) {
					case 'step2':
						$type = $visit->get('import.last.type', '');
						
						switch($type) {
							case 'orgs':
								$fields = DAO_ContactOrg::getFields();
								$tpl->assign('fields',$fields);
								break;
							case 'addys':
								$fields = DAO_Address::getFields();
								$tpl->assign('fields',$fields);
								break;
						}
						
						$tpl->display('file:' . dirname(__FILE__) . '/templates/contacts/import/mapping.tpl.php');
						break;
					default:
						$tpl->display('file:' . dirname(__FILE__) . '/templates/contacts/import/index.tpl.php');
						break;
				}
				break;

			case 'addresses':
				$view = C4_AbstractViewLoader::getView('C4_AddressView', C4_AddressView::DEFAULT_ID);
				$tpl->assign('view', $view);
				$tpl->assign('contacts_page', 'addresses');
				$tpl->assign('search_columns', SearchFields_Address::getFields());
				$tpl->display('file:' . dirname(__FILE__) . '/templates/contacts/addresses/index.tpl.php');
				break;
				
			default:
			case 'orgs':
				$param = array_shift($stack);
				
				if(!is_null($param) && is_numeric($param)) { // display
					$contact = DAO_ContactOrg::get($param);
					$tpl->assign('contact', $contact);
					$tpl->display('file:' . dirname(__FILE__) . '/templates/contacts/orgs/display.tpl.php');
					
				} else { // list
					$view = C4_AbstractViewLoader::getView('C4_ContactOrgView', C4_ContactOrgView::DEFAULT_ID);
					$tpl->assign('view', $view);
					$tpl->assign('contacts_page', 'orgs');
					$tpl->assign('search_columns', SearchFields_ContactOrg::getFields());
					$tpl->display('file:' . dirname(__FILE__) . '/templates/contacts/orgs/index.tpl.php');
				}
				break;
		}	
	}
	
	// Post
	function parseUploadAction() {
		@$type = DevblocksPlatform::importGPC($_REQUEST['type'],'string','');
		$csv_file = $_FILES['csv_file'];

		$visit = CerberusApplication::getVisit();
		$tpl = DevblocksPlatform::getTemplateService();
		
		$filename = basename($csv_file['tmp_name']);
		$newfilename = DEVBLOCKS_PATH . 'tmp/' . $filename;
		
		if(!rename($csv_file['tmp_name'], $newfilename)) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('contacts','import')));
			return; // [TODO] Throw error
		}
		
		// [TODO] Move these to a request holding object?
		$visit->set('import.last.type', $type);
		$visit->set('import.last.csv', $newfilename);
		
		$fp = fopen($newfilename, "rt");
		if($fp) {
			$parts = fgetcsv($fp, 8192, ',', '"');
			$tpl->assign('parts', $parts);
		}
		
		@fclose($fp);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('contacts','import','step2')));
	}
	
	// Post
	function doImportAction() {
		@$pos = DevblocksPlatform::importGPC($_REQUEST['pos'],'array',array());
		@$field = DevblocksPlatform::importGPC($_REQUEST['field'],'array',array());
		@$sync_column = DevblocksPlatform::importGPC($_REQUEST['sync_column'],'string','');
		@$include_first = DevblocksPlatform::importGPC($_REQUEST['include_first'],'integer',0);
		
		$visit = CerberusApplication::getVisit();
		$db = DevblocksPlatform::getDatabaseService();
		
		$csv_file = $visit->get('import.last.csv','');
		$type = $visit->get('import.last.type','');
		
		$fp = fopen($csv_file, "rt");
		if(!$fp) return;

		// [JAS]: Do we need to consume a first row of headings?
		if(!$include_first)
			@fgetcsv($fp, 8192, ',', '"');
		
		while(!feof($fp)) {
			$parts = fgetcsv($fp, 8192, ',', '"');
			$fields = array();
			$sync_field = '';
			$sync_val = '';
			
			foreach($pos as $idx => $p) {
				$key = $field[$idx];
				$val = $parts[$idx];
				if(!empty($key) && !empty($val)) {
					// [JAS]: Special overrides
					if($type=="orgs") {
						switch($key) {
							// Multi-Line
							case 'street':
								@$val = isset($fields[$key]) ? ($fields[$key].', '.$val) : ($val);
								break;
							
							// Dates
							case 'created':
								@$val = !is_numeric($val) ? strtotime($val) : $val;
								break;
						}
					} elseif($type=="addys") {
						switch($key) {
							// Org (from string into id)
							case 'contact_org_id':
								if(null != ($org_id = DAO_ContactOrg::lookup($val, true))) {
									$val = $org_id;
								} else {
									$val = 0;
								}
								
								break;
						}
					}
					
					$fields[$key] = $val;
					
					// [JAS]: Are we looking for matches in a certain field?
					if($sync_column==$key && !empty($val)) {
						$sync_field = $key;
						$sync_val = $val;
					}
				}
			}
			
			if(!empty($fields)) {
				if($type=="orgs") {
					@$orgs = DAO_ContactOrg::getWhere(
						(!empty($sync_field) && !empty($sync_val)) 
							? sprintf('%s = %s', $sync_field, $db->qstr($sync_val))
							: sprintf('name = %s', $db->qstr($fields['name']))
					);

					if(isset($fields['name'])) {
						if(empty($orgs)) {
							$id = DAO_ContactOrg::create($fields);
						} else {
							DAO_ContactOrg::update(key($orgs), $fields);
						}
					}
				} elseif ($type=="addys") {
					if(!empty($sync_field) && !empty($sync_val))
						@$addys = DAO_Address::getWhere(
							sprintf('%s = %s', $sync_field, $db->qstr($sync_val))
						);
					
					if(isset($fields['email'])) {
						if(empty($addys)) {
							$id = DAO_Address::create($fields);
						} else {
	//						echo "DUPE: ",$fields['first_name'],' ',$fields['last_name'],"<BR>";
							DAO_Address::update(key($addys), $fields);
						}
					}
				}
			}
		}
		
		@unlink($csv_file); // nuke the imported file
		
		$visit->set('import.last.csv',null);
		$visit->set('import.last.type',null);
	}
	
//	// Form (Step1)
//	function importOrgsAction() {
//		$csv_file = $_FILES['csv_file'];
//		$db = DevblocksPlatform::getDatabaseService();
//		@set_time_limit(0);
//
//		$primary_column = 1; // dupe checking // [TODO] Pull from form
//		$inserts = 0;
//		$updates = 0;
//		$invalids = 0;
//		
//		$fp = fopen($csv_file['tmp_name'], "rt");
//		if($fp) {
//			$line = fgets($fp, 8192); // Header Row
//			while(!feof($fp)) {
//				$line = fgets($fp, 8192);
//				$line = substr($line, 1, strlen($line)-2); // get rid of outer
//
//				if(empty($line))
//					continue;
//				
//				$parts = explode('","', $line);
//				
//				if(13 != count($parts)) {
//					echo "BAD ROW: $line<BR>";
//					$invalids++;
//					continue;
//				}
//				
//				$hits = array();
//				if(strlen($primary_column)) {
//					$hits = DAO_ContactOrg::getWhere(
//						sprintf("%s = %s",
//							DAO_ContactOrg::ACCOUNT_NUMBER,
//							$db->qstr($parts[$primary_column])
//					));
//				}
//				
//				@$row_created = (is_numeric($parts[12]) ? $parts[12] : strftime($parts[12]));
//				if(empty($row_created)) $row_created = time();
//				
//				$fields = array(
//					DAO_ContactOrg::ACCOUNT_NUMBER => $parts[1],
//					DAO_ContactOrg::NAME => $parts[0],
//					DAO_ContactOrg::STREET => $parts[2], // [TODO] +3+4
//					DAO_ContactOrg::CITY => $parts[5],
//					DAO_ContactOrg::PROVINCE => $parts[6],
//					DAO_ContactOrg::POSTAL => $parts[7],
//					DAO_ContactOrg::COUNTRY => $parts[8],
//					DAO_ContactOrg::PHONE => $parts[9],
//					DAO_ContactOrg::FAX => $parts[10],
//					DAO_ContactOrg::WEBSITE => $parts[11],
//					DAO_ContactOrg::CREATED => $row_created,
//				);
//				
//				if(empty($hits)) { // insert
//					$id = DAO_ContactOrg::create($fields);
//					$inserts++;
//				} else { // update/sync
//					DAO_ContactOrg::update(key($hits), $fields);
//					$updates++;
//				}
//			}
//			@fclose($fp);
//		}
//		
//		$total = $inserts + $updates;
//		echo "IMPORTED: $total contacts ($inserts inserts, $updates updates, $invalids invalids)<br>";
//		
//		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('contacts','import')));
//	}
	
	function showTabDetailsAction() {
		@$org = DevblocksPlatform::importGPC($_REQUEST['org']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$contact = DAO_ContactOrg::get($org);
		$tpl->assign('contact', $contact);

		$tpl->display('file:' . dirname(__FILE__) . '/templates/contacts/orgs/tabs/details.tpl.php');
		exit;
	}
	
	function showTabPeopleAction() {
		@$org = DevblocksPlatform::importGPC($_REQUEST['org']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$contact = DAO_ContactOrg::get($org);
		$tpl->assign('contact', $contact);
		
		$view = C4_AbstractViewLoader::getView('C4_AddressView', 'org_contacts');
		$view->id = 'org_contacts';
		$view->name = 'Organization Contacts';
		$view->view_columns = array(
			SearchFields_Address::FIRST_NAME,
			SearchFields_Address::LAST_NAME,
//			SearchFields_Address::EMAIL,
			SearchFields_Address::ORG_NAME,
		);
		$view->params = array(
			new DevblocksSearchCriteria(SearchFields_Address::CONTACT_ORG_ID,'=',$org)
		);
		$tpl->assign('view', $view);
		
		$tpl->assign('contacts_page', 'orgs');
		$tpl->assign('search_columns', SearchFields_Address::getFields());
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/contacts/orgs/tabs/people.tpl.php');
		exit;
	}
	
	function showTabHistoryAction() {
		@$org = DevblocksPlatform::importGPC($_REQUEST['org']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$contact = DAO_ContactOrg::get($org);
		$tpl->assign('contact', $contact);
		
		$visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
		$viewMgr = $visit->get(CerberusVisit::KEY_VIEW_MANAGER); /* @var $viewMgr CerberusStaticViewManager */
		
		if(null == (@$tickets_view = $viewMgr->getView('contact_history'))) {
			$tickets_view = new CerberusDashboardView();
			$tickets_view->id = 'contact_history';
			$tickets_view->name = 'Contact History';
			$tickets_view->view_columns = array(
				SearchFields_Ticket::TICKET_NEXT_ACTION,
				SearchFields_Ticket::TICKET_CREATED_DATE,
				SearchFields_Ticket::TEAM_NAME,
				SearchFields_Ticket::TICKET_CATEGORY_ID,
				SearchFields_Ticket::TICKET_SPAM_SCORE,
				SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
			);
			$tickets_view->params = array(
//				SearchFields_Ticket::TICKET_FIRST_WROTE_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE_ID,DevblocksSearchCriteria::OPER_IN,$address_ids)
			);
			$tickets_view->renderLimit = 10;
			$tickets_view->renderPage = 0;
			$tickets_view->renderSortBy = SearchFields_Ticket::TICKET_CREATED_DATE;
			$tickets_view->renderSortAsc = false;
			$viewMgr->setView('contact_history', $tickets_view);
		}

//		$addresses = DAO_Address::getWhere(sprintf("%s=%d",
//			DAO_Address::CONTACT_ORG_ID,
//			$contact->id
//		));
//
//		$addys = array();
//		foreach($addresses as $addy) {
//			if(!empty($addy))
//				$addys[] = $addy->email;
//		}
//		$address_ids = !empty($addresses) ? array_keys($addresses) : array(-1);
		
		@$tickets_view->name = "Most recent tickets from " . htmlentities($contact->name);
		$tickets_view->params = array(
			SearchFields_Ticket::TICKET_FIRST_CONTACT_ORG_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_CONTACT_ORG_ID,DevblocksSearchCriteria::OPER_EQ,$contact->id)
		);
		$tpl->assign('contact_history', $tickets_view);
		
		$workers = DAO_Worker::getList();
		$tpl->assign('workers', $workers);
		
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		
		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);
		
		$team_categories = DAO_Bucket::getTeams();
		$tpl->assign('team_categories', $team_categories);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/contacts/orgs/tabs/history.tpl.php');
		exit;
	}
	
	function updateContactOrgAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer');
		@$org_name = DevblocksPlatform::importGPC($_REQUEST['org_name'], 'string');
		@$account_num = DevblocksPlatform::importGPC($_REQUEST['account_num'], 'string');
		@$street = DevblocksPlatform::importGPC($_REQUEST['street'], 'string');
		@$city = DevblocksPlatform::importGPC($_REQUEST['city'], 'string');
		@$province = DevblocksPlatform::importGPC($_REQUEST['province'], 'string');
		@$postal = DevblocksPlatform::importGPC($_REQUEST['postal'], 'string');
		@$country = DevblocksPlatform::importGPC($_REQUEST['country'], 'string');
		@$phone =  DevblocksPlatform::importGPC($_REQUEST['phone'], 'string');
		@$fax =  DevblocksPlatform::importGPC($_REQUEST['fax'], 'string');
		@$website = DevblocksPlatform::importGPC($_REQUEST['website'], 'string');
		
		$fields = array(
				DAO_ContactOrg::ACCOUNT_NUMBER => $account_num,
				DAO_ContactOrg::CITY => $city,
				DAO_ContactOrg::COUNTRY => $country,
				DAO_ContactOrg::CREATED => time(),
				DAO_ContactOrg::FAX => $fax,
				DAO_ContactOrg::NAME => $org_name,
				DAO_ContactOrg::PHONE => $phone,
				DAO_ContactOrg::POSTAL => $postal,
				DAO_ContactOrg::PROVINCE => $province,
				DAO_ContactOrg::STREET => $street,
				DAO_ContactOrg::WEBSITE => $website
		);
		
		if($id==0) {
			$id = DAO_ContactOrg::create($fields);
		}
		else {
			DAO_ContactOrg::update($id, $fields);	
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('contacts','orgs',$id)));
	}
	
	function showAddressPeekAction() {
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		list($addresses,$null) = DAO_Address::search(
			array(
				new DevblocksSearchCriteria(SearchFields_Address::EMAIL,DevblocksSearchCriteria::OPER_EQ,$email)
			),
			1,
			0,
			null,
			null,
			false
		);
		
		$address = array_shift($addresses);
		
//		$id = DAO_Address::lookupAddress($email, false);
//		$address = DAO_Address::get($id);
		$tpl->assign('address', $address);
		$tpl->assign('view_id', $view_id);
		$tpl->display('file:' . dirname(__FILE__) . '/templates/contacts/addresses/address_peek.tpl.php');
	}
	
	function showOrgPeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer','');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$contact = DAO_ContactOrg::get($id);
		$tpl->assign('contact', $contact);
		$tpl->assign('view_id', $view_id);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/contacts/orgs/org_peek.tpl.php');
	}
	
	function saveContactAction() {
		$db = DevblocksPlatform::getDatabaseService();
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		@$first_name = DevblocksPlatform::importGPC($_REQUEST['first_name'],'string','');
		@$last_name = DevblocksPlatform::importGPC($_REQUEST['last_name'],'string','');
		@$contact_org = DevblocksPlatform::importGPC($_REQUEST['contact_org'],'string','');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string', '');

		$contact_org_id = 0;
		
		if(!empty($contact_org)) {
			$contact_org_id = DAO_ContactOrg::lookup($contact_org, true);
		}
		
		$fields = array(
			DAO_Address::FIRST_NAME => $first_name,
			DAO_Address::LAST_NAME => $last_name,
			DAO_Address::CONTACT_ORG_ID => $contact_org_id
		);
		
		DAO_Address::update($id, $fields);
		
		$view = C4_AbstractViewLoader::getView('', $view_id);
		$view->render();
	}
	
	function saveOrgPeekAction() {
		$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		$org_name = DevblocksPlatform::importGPC($_REQUEST['org_name'],'string','');
		$account_num = DevblocksPlatform::importGPC($_REQUEST['account_num'],'string','');
		$street = DevblocksPlatform::importGPC($_REQUEST['street'],'string','');
		$city = DevblocksPlatform::importGPC($_REQUEST['city'],'string','');
		$province = DevblocksPlatform::importGPC($_REQUEST['province'],'string','');
		$postal = DevblocksPlatform::importGPC($_REQUEST['postal'],'string','');
		$country = DevblocksPlatform::importGPC($_REQUEST['country'],'string','');
		$phone = DevblocksPlatform::importGPC($_REQUEST['phone'],'string','');
		$fax = DevblocksPlatform::importGPC($_REQUEST['fax'],'string','');
		$website = DevblocksPlatform::importGPC($_REQUEST['website'],'string','');
		$delete = DevblocksPlatform::importGPC($_REQUEST['delete'],'integer',0);

		if(!empty($id) && !empty($delete)) { // delete
			DAO_ContactOrg::delete($id);
			
		} else { // create/edit
			$fields = array(DAO_ContactOrg::NAME => $org_name,
					DAO_ContactOrg::ACCOUNT_NUMBER => $account_num,
					DAO_ContactOrg::STREET => $street,
					DAO_ContactOrg::CITY => $city,
					DAO_ContactOrg::PROVINCE => $province,
					DAO_ContactOrg::POSTAL => $postal,
					DAO_ContactOrg::COUNTRY => $country,
					DAO_ContactOrg::PHONE => $phone,
					DAO_ContactOrg::FAX => $fax,
					DAO_ContactOrg::WEBSITE => $website
					);
	
			if($id==0) {
				$id = DAO_ContactOrg::create($fields);
			}
			else {
				DAO_ContactOrg::update($id, $fields);	
			}
		}		
		
		$view = C4_AbstractViewLoader::getView('', $view_id);
		$view->render();		
	}
	
	function getOrgsAutoCompletionsAction() {
		@$starts_with = DevblocksPlatform::importGPC($_REQUEST['query'],'string','');
		
		$params = array(
			DAO_ContactOrg::NAME => $starts_with
		);
		
		list($orgs,$null) = DAO_ContactOrg::search(
				array(
					new DevblocksSearchCriteria(SearchFields_ContactOrg::NAME,DevblocksSearchCriteria::OPER_LIKE, $starts_with. '*'), 
					),
				-1,
			    0,
			    SearchFields_ContactOrg::NAME,
			    true,
			    false
		);
		
//		$prefix='';
//		echo '{';
//		echo '"result": ['; 
//		foreach($orgs AS $val){
//			echo $prefix;
//	        echo '{"name":"' . $val[SearchFields_ContactOrg::NAME] . '",'; 
//	        echo '"id":"' . $val[SearchFields_ContactOrg::ID] . '"'; 
//	        echo '}';
//	        $prefix = ',';
//	    } 
//		echo ']';	
//		echo '}';
//		exit();
		
		foreach($orgs AS $val){
			echo $val[SearchFields_ContactOrg::NAME] . "\t";
			echo $val[SearchFields_ContactOrg::ID] . "\n";
		}
		exit();
	}
};

class ChFilesController extends DevblocksControllerExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
		
		$router = DevblocksPlatform::getRoutingService();
		$router->addRoute('files','core.controller.files');
	}
	
	function isVisible() {
	    // [TODO] SECURITY
	}
	
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		$stack = $request->path;				// URLS like: /files/10000/plaintext.txt
		array_shift($stack);					// files	
		$file_id = array_shift($stack); 		// 10000
		$file_name = array_shift($stack); 		// plaintext.txt
		
		// [TODO] Do a security check the current user can see the parent ticket (team check)
		if(empty($file_id) || empty($file_name) || null == ($file = DAO_Attachment::get($file_id)))
			die("File not found.");
			
		// Set headers
		header("Expires: Mon, 26 Nov 1962 00:00:00 GMT\n");
		header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT\n");
		header("Cache-control: private\n");
		header("Pragma: no-cache\n");
		header("Content-Type: " . $file->mime_type . "\n");
		header("Content-transfer-encoding: binary\n"); 
		header("Content-Length: " . $file->file_size . "\n");
		
		echo($file->getFileContents());
		
		exit;
	}
};

class ChCronController extends DevblocksControllerExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
		
		$router = DevblocksPlatform::getRoutingService();
		$router->addRoute('cron','core.controller.cron');
	}
	
	function isVisible() {
		// [TODO] This should restrict by IP rather than session
	}
	
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
	    $settings = CerberusSettings::getInstance();
	    $authorized_ips_str = $settings->get(CerberusSettings::AUTHORIZED_IPS);
	    $authorized_ips = CerberusApplication::parseCrlfString($authorized_ips_str);
	    
	    $authorized_ip_defaults = CerberusApplication::parseCsvString(AUTHORIZED_IPS_DEFAULTS);
	    $authorized_ips = array_merge($authorized_ips, $authorized_ip_defaults);
	    
	    $pass = false;
		foreach ($authorized_ips as $ip)
		{
			if(substr($ip,0,strlen($ip)) == substr($_SERVER['REMOTE_ADDR'],0,strlen($ip)))
		 	{ $pass=true; break; }
		}
	    if(!$pass) {
		    echo 'Your IP address ('.$_SERVER['REMOTE_ADDR'].') is not authorized to run scheduler jobs.';
		    return;
	    }
		
		$stack = $request->path;
		
		array_shift($stack); // cron
		$job_id = array_shift($stack);

        @set_time_limit(0); // Unlimited (if possible)
		
		$url = DevblocksPlatform::getUrlService();
        $timelimit = intval(ini_get('max_execution_time'));
		
		echo "<HTML>".
		"<HEAD>".
		"<TITLE></TITLE>".
		(empty($job_id) ?  "<meta http-equiv='Refresh' content='30;" . $url->write('c=cron') . "'>" : ""). // only auto refresh on all jobs
	    "</HEAD>".
		"<BODY onload=\"setTimeout(\\\"window.location.replace('".$url->write('c=cron')."')\\\",30);\">";

	    // [TODO] Determine if we're on a time limit under 60 seconds
		
	    $cron_manifests = DevblocksPlatform::getExtensions('cerberusweb.cron', true);
        $jobs = array();
	    
	    if(empty($job_id)) { // do everything 
			
		    // Determine who wants to go first by next time and longest waiting
            $nexttime = time() + 86400;
		    
			if(is_array($cron_manifests))
			foreach($cron_manifests as $idx => $instance) { /* @var $instance CerberusCronPageExtension */
			    $lastrun = $instance->getParam(CerberusCronPageExtension::PARAM_LASTRUN, 0);
			    
			    if($instance->isReadyToRun()) {
			        if($timelimit) {
			            if($lastrun < $nexttime) {
			                $jobs[0] = $cron_manifests[$idx];
	    		            $nexttime = $lastrun;
			            }
			        } else {
    			        $jobs[] =& $cron_manifests[$idx];
			        }
			    }
			}
			
	    } else { // single job
	        $manifest = DevblocksPlatform::getExtension($job_id);
	        if(empty($manifest)) exit;
	        	        
	        $instance = $manifest->createInstance();
	        
			if($instance) {
			    if($instance->isReadyToRun()) {
			        $jobs[0] =& $instance;
			    }
			}
	    }
	    
		if(!empty($jobs)) {
		    foreach($jobs as $nextjob) {
		        $nextjob->setParam(CerberusCronPageExtension::PARAM_LOCKED, time());
	    	    $nextjob->_run();
	        }
		} else {
		    echo "Nothing to do yet!  (Waiting 30 seconds)";
		}
			
	    echo "</BODY>".
	    "</HTML>";
		
		exit;
	}
}

class ChTestsController extends DevblocksControllerExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
		$router = DevblocksPlatform::getRoutingService();
		$router->addRoute('tests','core.controller.tests');
	}
	
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		// [TODO] Add testing extension point to Cerb/Devblocks

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

class ChRssController extends DevblocksControllerExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
		$router = DevblocksPlatform::getRoutingService();
		$router->addRoute('rss','core.controller.rss');
	}
	
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		// [TODO] Do we want any concept of authentication here?

        $stack = $request->path;
        
        array_shift($stack); // rss
        $hash = array_shift($stack);

        $feed = DAO_TicketRss::getByHash($hash);
        
        if(empty($feed)) {
            die("Bad feed data.");
        }
        
        // [TODO] Implement logins for the wiretap app
        header("Content-Type: text/xml");

        $xmlstr = <<<XML
		<rss version='2.0'>
		</rss>
XML;

        $xml = new SimpleXMLElement($xmlstr);

        // Channel
        $channel = $xml->addChild('channel');
        
        $channel->addChild('title', $feed->title);

        $url = DevblocksPlatform::getUrlService();
        $channel->addChild('link', $url->write('',true));

        $channel->addChild('description', '');

		list($tickets, $null) = DAO_Ticket::search(
			$feed->params['params'],
			100,
			0,
			SearchFields_Ticket::TICKET_UPDATED_DATE, // $feed->params['sort_by'],
			false, // $feed->params['sort_asc'],
			false
		);

        $translate = DevblocksPlatform::getTranslationService();

        foreach($tickets as $ticket) {
        	$created = intval($ticket[SearchFields_Ticket::TICKET_UPDATED_DATE]);
            if(empty($created)) $created = time();

            $eItem = $channel->addChild('item');
            
            $escapedSubject = htmlentities($ticket[SearchFields_Ticket::TICKET_SUBJECT]);
            //filter out a couple non-UTF-8 characters (0xC and ESC)
            $escapedSubject = preg_replace("/[]/", '', $escapedSubject);
            $eTitle = $eItem->addChild('title',$escapedSubject );

            $eDesc = $eItem->addChild('description', $this->_getTicketLastAction($ticket));
            
            $url = DevblocksPlatform::getUrlService();
            $link = $url->write('c=display&id='.$ticket[SearchFields_Ticket::TICKET_MASK], true);
            $eLink = $eItem->addChild('link', $link);
            	
            $eDate = $eItem->addChild('pubDate', gmdate('D, d M Y H:i:s T',$created));
            
            $eGuid = $eItem->addChild('guid', md5($escapedSubject .$created));
        }

        echo $xml->asXML();
	    
		exit;
	}
	
	private function _getTicketLastAction($ticket) {
		static $workers = null;
		$action_code = $ticket[SearchFields_Ticket::TICKET_LAST_ACTION_CODE];
		$output = '';
		
		if(is_null($workers))
			$workers = DAO_Worker::getList();

		// [TODO] Translate
		switch($action_code) {
			case CerberusTicketActionCode::TICKET_OPENED:
				$output = sprintf("New from %s",
					$ticket[SearchFields_Ticket::TICKET_LAST_WROTE]
				);
				break;
			case CerberusTicketActionCode::TICKET_CUSTOMER_REPLY:
				@$worker_id = $ticket[SearchFields_Ticket::TICKET_NEXT_WORKER_ID];
				@$worker = $workers[$worker_id];
				$output = sprintf("Incoming for %s",
					(!empty($worker) ? $worker->getName() : "Helpdesk")
				);
				break;
			case CerberusTicketActionCode::TICKET_WORKER_REPLY:
				@$worker_id = $ticket[SearchFields_Ticket::TICKET_LAST_WORKER_ID];
				@$worker = $workers[$worker_id];
				$output = sprintf("Outgoing from %s",
					(!empty($worker) ? $worker->getName() : "Helpdesk")
				);
				break;
		}
		
		return $output;
	}
};

class ChUpdateController extends DevblocksControllerExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
		$router = DevblocksPlatform::getRoutingService();
		$router->addRoute('update','core.controller.update');
	}
	
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
	    @set_time_limit(0); // no timelimit (when possible)
	    DevblocksPlatform::clearCache();
	    
	    $stack = $request->path;
	    array_shift($stack); // update

	    switch(array_shift($stack)) {
	    	case 'locked':
	    		if(!DevblocksPlatform::versionConsistencyCheck()) {
	    			$url = DevblocksPlatform::getUrlService();
	    			echo "<h1>Cerberus Helpdesk 4.0</h1>";
	    			echo "The helpdesk is currently waiting for an administrator to finish upgrading. ".
	    				"Please wait a few minutes and then ". 
		    			sprintf("<a href='%s'>try again</a>.<br><br>",
							$url->write('c=update&a=locked')
		    			);
	    			echo sprintf("If you're an admin you may <a href='%s'>finish the upgrade</a>.",
	    				$url->write('c=update')
	    			);
	    		} else {
	    			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login')));
	    		}
	    		break;
	    		
	    	default:
			    $path = DEVBLOCKS_PATH . 'tmp' . DIRECTORY_SEPARATOR;
				$file = $path . 'c4update_lock';	    		
				
				if(!file_exists($file) || @filectime($file)+600 < time()) { // 10 min lock
					touch($file);

				    $settings = CerberusSettings::getInstance();
				    $authorized_ips_str = $settings->get(CerberusSettings::AUTHORIZED_IPS);
				    $authorized_ips = CerberusApplication::parseCrlfString($authorized_ips_str);
				    
			   	    $authorized_ip_defaults = CerberusApplication::parseCsvString(AUTHORIZED_IPS_DEFAULTS);
				    $authorized_ips = array_merge($authorized_ips, $authorized_ip_defaults);
				    
				    $pass = false;
					foreach ($authorized_ips as $ip)
					{
						if(substr($ip,0,strlen($ip)) == substr($_SERVER['REMOTE_ADDR'],0,strlen($ip)))
					 	{ $pass=true; break; }
					}
				    if(!$pass) {
					    echo 'Your IP address ('.$_SERVER['REMOTE_ADDR'].') is not authorized to update this helpdesk.';
					    return;
				    }

				    //echo "Running plugin patches...<br>";
				    if(DevblocksPlatform::runPluginPatches()) {
						@unlink($file);
				    	DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login')));
				    } else {
						@unlink($file);
				    	echo "Failure!"; // [TODO] Needs elaboration
				    } 
				    break;
				}
				else {
					echo "Another administrator is currently running update.  Please wait...";
//					DevblocksPlatform::redirect(new DevblocksHttpResponse(array('update','locked')));
				}
	    }
	    
		exit;
	}
}

class ChInternalController extends DevblocksControllerExtension {
	const ID = 'core.controller.internal';
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$router = DevblocksPlatform::getRoutingService();
		$router->addRoute('internal',self::ID);
	}
	
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		$worker = CerberusApplication::getActiveWorker();
		if(empty($worker)) return;
		
		$stack = $request->path;
		array_shift($stack); // internal
		
	    @$action = array_shift($stack) . 'Action';

	    switch($action) {
	        case NULL:
	            // [TODO] Index/page render
	            break;
	            
	        default:
			    // Default action, call arg as a method suffixed with Action
				if(method_exists($this,$action)) {
					call_user_func(array(&$this, $action));
				}
	            break;
	    }
	}
	
	// Ajax
	function viewRefreshAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		$view = C4_AbstractViewLoader::getView('', $id);
		$view->render();
	}
	
	function viewSortByAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$sortBy = DevblocksPlatform::importGPC($_REQUEST['sortBy']);
		
		$view = C4_AbstractViewLoader::getView('', $id);
		$view->doSortBy($sortBy);
		C4_AbstractViewLoader::setView('', $id, $view);
		
		$view->render();
	}
	
	function viewPageAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$page = DevblocksPlatform::importGPC(DevblocksPlatform::importGPC($_REQUEST['page']));
		
		$view = C4_AbstractViewLoader::getView('', $id);
		$view->doPage($page);
		C4_AbstractViewLoader::setView('', $id, $view);
		
		$view->render();
	}
	
	function viewGetCriteriaAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$field = DevblocksPlatform::importGPC($_REQUEST['field']);
		
		$view = C4_AbstractViewLoader::getView('', $id);
		$view->renderCriteria($field);		
	}
	
	// Post
	function viewAddCriteriaAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$response_uri = DevblocksPlatform::importGPC($_REQUEST['response_uri']);
		@$field = DevblocksPlatform::importGPC($_REQUEST['field']);
		@$oper = DevblocksPlatform::importGPC($_REQUEST['oper']);
		@$value = DevblocksPlatform::importGPC($_REQUEST['value']);
		
		$view = C4_AbstractViewLoader::getView('', $id);
		$view->doSetCriteria($field, $oper, $value);
		C4_AbstractViewLoader::setView('', $id, $view);
		
		// [TODO] Need to put them back on org or person (depending on which was active)
		if(!empty($response_uri))
			DevblocksPlatform::redirect(new DevblocksHttpResponse(explode('/', $response_uri)));
	}
	
	function viewRemoveCriteriaAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$response_uri = DevblocksPlatform::importGPC($_REQUEST['response_uri']);
		@$field = DevblocksPlatform::importGPC($_REQUEST['field']);
		
		$view = C4_AbstractViewLoader::getView('', $id);
		$view->doRemoveCriteria($field);
		C4_AbstractViewLoader::setView('', $id, $view);
		
		if(!empty($response_uri))
			DevblocksPlatform::redirect(new DevblocksHttpResponse(explode('/', $response_uri)));
	}
	
	function viewResetCriteriaAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$response_uri = DevblocksPlatform::importGPC($_REQUEST['response_uri']);
		
		$view = C4_AbstractViewLoader::getView('', $id);
		$view->doResetCriteria();
		C4_AbstractViewLoader::setView('', $id, $view);

		if(!empty($response_uri))
			DevblocksPlatform::redirect(new DevblocksHttpResponse(explode('/', $response_uri)));
	}
	
	// Ajax
	function viewCustomizeAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $id);

		$view = C4_AbstractViewLoader::getView('', $id);
		$tpl->assign('view', $view);

		$tpl->assign('optColumns', $view->getSearchFields());
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/contacts/customize_view.tpl.php');
	}
	
	// Post?
	function viewSaveCustomizeAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$columns = DevblocksPlatform::importGPC($_REQUEST['columns'],'array', array());
		@$num_rows = DevblocksPlatform::importGPC($_REQUEST['num_rows'],'integer',10);
		
		$view = C4_AbstractViewLoader::getView('', $id);
		$view->doCustomize($columns, $num_rows);		
		C4_AbstractViewLoader::setView('', $id, $view);
		
		$view->render();
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
	
	function getActivity() {
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		@$id = $stack[1];
	       
		$url = DevblocksPlatform::getUrlService();
		$link = sprintf("<a href='%s'>#%s</a>",
		    $url->write("c=display&id=".$id),
		    $id
		);
	    return new Model_Activity('activity.display_ticket',array($link));
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
			$id = DAO_Ticket::getTicketIdByMask($id);
		}
		$ticket = DAO_Ticket::getTicket($id);
	
		if(empty($ticket)) {
			echo "<H1>Invalid Ticket ID.</H1>";
			return;
		}
		
		$tpl->assign('ticket', $ticket);

		$workers = DAO_Worker::getList();  // [TODO] ::getAll();
		$tpl->assign('workers', $workers);
		
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		
		$team_categories = DAO_Bucket::getTeams();
		$tpl->assign('team_categories', $team_categories);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/index.tpl.php');
	}

	function getMessageAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']); // message id
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$message = DAO_Ticket::getMessage($id);
		$tpl->assign('message', $message);
		
		$ticket = DAO_Ticket::getTicket($message->id);
		$tpl->assign('ticket', $ticket);
		
		$content = DAO_MessageContent::get($id);
		$tpl->assign('content', $content);
		
		$notes = DAO_MessageNote::getByMessageId($id);
		$tpl->assign('message_notes', $notes);
		
		// [TODO] Workers?
		
		$tpl->assign('expanded', true);
		$tpl->register_modifier('makehrefs', array('CerberusUtils', 'smarty_modifier_makehrefs')); 
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/conversation/message.tpl.php');
	}

	function updatePropertiesAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']); // ticket id
		@$closed = DevblocksPlatform::importGPC($_REQUEST['closed'],'integer',0);
		@$spam = DevblocksPlatform::importGPC($_REQUEST['spam'],'integer',0);
		@$deleted = DevblocksPlatform::importGPC($_REQUEST['deleted'],'integer',0);
		@$bucket = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'string');
		
		// Anti-Spam
		if(!empty($spam)) {
		    CerberusBayes::markTicketAsSpam($id);
		    // [mdf] if the spam button was clicked override the default params for deleted/closed
		    $closed=1;
		    $deleted=1;
		}

//        $ticket = DAO_Ticket::getTicket($id);
		$categories = DAO_Bucket::getAll();

		// Properties
		$properties = array(
			DAO_Ticket::IS_CLOSED => intval($closed),
			DAO_Ticket::IS_DELETED => intval($deleted),
			DAO_Ticket::UPDATED_DATE => time(),
		);
				
		// Team/Category
		if(!empty($bucket)) {
			list($team_id,$bucket_id) = CerberusApplication::translateTeamCategoryCode($bucket);

			if(!empty($team_id)) {
			    $properties[DAO_Ticket::TEAM_ID] = $team_id;
			    $properties[DAO_Ticket::CATEGORY_ID] = $bucket_id;
			}
		}
		
		DAO_Ticket::updateTicket($id, $properties);

		if(!empty($team_id)) {
		    $eventMgr = DevblocksPlatform::getEventService();
		    $eventMgr->trigger(
		        new Model_DevblocksEvent(
		            'ticket.moved', // [TODO] Const
	                array(
	                    'ticket_ids' => array($id),
	                    'team_id' => $team_id,
	                    'bucket_id' => $bucket_id,
	                )
	            )
		    );
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('display',$id)));
	}

	/**
	 * Enter description here...
	 * @param string $message_id
	 */
	private function _renderNotes($message_id) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->assign('message_id', $message_id);
		
		$notes = DAO_MessageNote::getByMessageId($message_id);
		$message_notes = array();
		
		// [TODO] DAO-ize? (shared in render())
		if(is_array($notes))
		foreach($notes as $note) {
			if(!isset($message_notes[$note->message_id]))
				$message_notes[$note->message_id] = array();
			$message_notes[$note->message_id][$note->id] = $note;
		}
		$tpl->assign('message_notes', $message_notes);
				
		$workers = DAO_Worker::getList();
		$tpl->assign('workers', $workers);

		$tpl->register_modifier('makehrefs', array('CerberusUtils', 'smarty_modifier_makehrefs')); 
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/conversation/notes.tpl.php');
	}
	
	function addNoteAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->assign('id',$id);
		
		$message = DAO_Ticket::getMessage($id);
		$tpl->assign('message',$message);
		
		$worker = CerberusApplication::getActiveWorker();
		$tpl->assign('worker', $worker);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/rpc/add_note.tpl.php');
	}
	
	function doAddNoteAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer',0);
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');
		
		$worker = CerberusApplication::getActiveWorker();
		
		$fields = array(
			DAO_MessageNote::MESSAGE_ID => $id,
			DAO_MessageNote::CREATED => time(),
			DAO_MessageNote::WORKER_ID => $worker->id,
			DAO_MessageNote::CONTENT => $content,
		);
		$note_id = DAO_MessageNote::create($fields);
		
		// [TODO] This really should use an anchor to go back to the message (#r100)
//		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('display',$ticket_id)));

		$this->_renderNotes($id);
	}
	
	function deleteNoteAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		$note = DAO_MessageNote::get($id);
		$message_id = $note->message_id;
		DAO_MessageNote::delete($id);
		unset($note);
		
		$this->_renderNotes($message_id);
	}
	
	function replyAction() { 
	    ChDisplayPage::loadMessageTemplate(CerberusMessageType::EMAIL);
	}
	
//	function forwardAction() {
//	    ChDisplayPage::loadMessageTemplate(CerberusMessageType::FORWARD);
//	}
//	
//	function commentAction() {
//	    ChDisplayPage::loadMessageTemplate(CerberusMessageType::COMMENT);
//	}
	
	function loadMessageTemplate($type) {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->assign('id',$id);
		
		$message = DAO_Ticket::getMessage($id);
		$tpl->assign('message',$message);
		
		$ticket = DAO_Ticket::getTicket($message->ticket_id);
		$tpl->assign('ticket',$ticket);
		
		$workers = DAO_Worker::getList(); // [TODO] ::getAll()
		$tpl->assign('workers', $workers);
		
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		
		$team_categories = DAO_Bucket::getTeams();
		$tpl->assign('team_categories', $team_categories);

		@$ticket_team = $teams[$ticket->team_id];
		
		// Signatures
		if(null != ($worker = CerberusApplication::getActiveWorker())) { /* @var $worker CerberusWorker */
			if(!empty($ticket_team) && !empty($ticket_team->signature)) {
	            $signature = $ticket_team->signature;
			} else {
			    // [TODO] Default signature
		        $settings = CerberusSettings::getInstance();
		        $signature = $settings->get(CerberusSettings::DEFAULT_SIGNATURE);
			}
			
			$tpl->assign('signature', str_replace(
			        array('#first_name#','#last_name#','#title#'),
			        array($worker->first_name,$worker->last_name,$worker->title),
			        $signature
			));
		}
		
		$tpl->assign('upload_max_filesize', ini_get('upload_max_filesize'));
		
		$tpl->cache_lifetime = "0";
		
		switch ($type) {
//			case CerberusMessageType::FORWARD :
//				$tpl->display('file:' . dirname(__FILE__) . '/templates/display/rpc/forward.tpl.php');
//				break;
			case CerberusMessageType::EMAIL :
				$tpl->display('file:' . dirname(__FILE__) . '/templates/display/rpc/reply.tpl.php');
				break;
//			case CerberusMessageType::COMMENT :
//				$tpl->display('file:' . dirname(__FILE__) . '/templates/display/rpc/comment.tpl.php');
//				break;
		}
	}
	
	function sendReplyAction() {
	    @$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
	    
	    $worker = CerberusApplication::getActiveWorker();
	    
		$properties = array(
		    'type' => CerberusMessageType::EMAIL,
		    'message_id' => DevblocksPlatform::importGPC(@$_REQUEST['id']),
		    'ticket_id' => $ticket_id,
		    'cc' => DevblocksPlatform::importGPC(@$_REQUEST['cc']),
		    'bcc' => DevblocksPlatform::importGPC(@$_REQUEST['bcc']),
		    'subject' => DevblocksPlatform::importGPC(@$_REQUEST['subject'],'string'),
		    'content' => DevblocksPlatform::importGPC(@$_REQUEST['content']),
		    'files' => @$_FILES['attachment'],
		    'next_action' => DevblocksPlatform::importGPC(@$_REQUEST['next_action'],'string',''),
		    'next_worker_id' => DevblocksPlatform::importGPC(@$_REQUEST['next_worker_id'],'integer',0),
		    'closed' => DevblocksPlatform::importGPC(@$_REQUEST['closed'],'integer',0),
		    'bucket_id' => DevblocksPlatform::importGPC(@$_REQUEST['bucket_id'],'string',''),
		    'ticket_reopen' => DevblocksPlatform::importGPC(@$_REQUEST['ticket_reopen'],'string',''),
		    'agent_id' => @$worker->id,
		);
		
		CerberusMail::sendTicketMessage($properties);

        DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('display',$ticket_id)));
	}
	
	function showConversationAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$ticket = DAO_Ticket::getTicket($id);
		$tpl->assign('ticket', $ticket);

		$messages = $ticket->getMessages();
		arsort($messages);
		$tpl->assign('messages', $messages);
		
		$notes = DAO_MessageNote::getByTicketId($id);
		$message_notes = array();
		// Index notes by message id
		if(is_array($notes))
		foreach($notes as $note) {
			if(!isset($message_notes[$note->message_id]))
				$message_notes[$note->message_id] = array();
			$message_notes[$note->message_id][$note->id] = $note;
		}
		$tpl->assign('message_notes', $message_notes);
		
		// [TODO] Cache this (getAll)
		$workers = DAO_Worker::getList();
		$tpl->assign('workers', $workers);
		
		$tpl->register_modifier('makehrefs', array('CerberusUtils', 'smarty_modifier_makehrefs')); 
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/conversation/index.tpl.php');
	}
	
	function showPropertiesAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$tpl->assign('ticket_id', $ticket_id);
		
		$ticket = DAO_Ticket::getTicket($ticket_id);
		$tpl->assign('ticket', $ticket);

		$requesters = DAO_Ticket::getRequestersByTicket($ticket_id);
		$tpl->assign('requesters', $requesters);
		
		$workers = DAO_Worker::getList();
		$tpl->assign('workers', $workers);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/properties/index.tpl.php');
	}
	
	// Post
	function savePropertiesAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_POST['ticket_id'],'integer',0);
		@$add = DevblocksPlatform::importGPC($_POST['add'],'string','');
		@$remove = DevblocksPlatform::importGPC($_POST['remove'],'array',array());
		@$next_worker_id = DevblocksPlatform::importGPC($_POST['next_worker_id'],'integer',0);
		@$next_action = DevblocksPlatform::importGPC($_POST['next_action'],'string','');
		
		@$ticket = DAO_Ticket::getTicket($ticket_id);
		
		if(empty($ticket_id) || empty($ticket))
			return;
		
		$fields = array();
		
		// Properties

		if(!empty($next_worker_id))
			$fields[DAO_Ticket::NEXT_WORKER_ID] = $next_worker_id;
			
		if(!empty($next_action))
			$fields[DAO_Ticket::NEXT_ACTION] = $next_action;

		if(!empty($fields))
			DAO_Ticket::updateTicket($ticket_id, $fields);
			
		// Requesters
			
		if(!empty($add)) {
			$adds = CerberusApplication::parseCrlfString($add);
			$adds = array_unique($adds);
			
			foreach($adds as $addy) {
				if(null != ($address_id = DAO_Address::lookupAddress($addy, true))) {
					DAO_Ticket::createRequester($address_id, $ticket_id);
//					echo "Added <b>$addy</b> as a recipient.<br>";
				} else {
//					echo "Ignored invalid e-mail address: <b>$addy</b><br>";
				}
			}
		}
		
		if(!empty($remove) && is_array($remove)) {
			foreach($remove as $address_id) {
				$addy = DAO_Address::get($address_id);
				DAO_Ticket::deleteRequester($ticket_id, $address_id);
//				echo "Removed <b>" . $addy->email . "</b> as a recipient.<br>";
			}
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$ticket->mask)));
	}
	
	function showContactHistoryAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$ticket = DAO_Ticket::getTicket($ticket_id);
		
		$contact = DAO_Address::get($ticket->first_wrote_address_id);
		$tpl->assign('contact', $contact);
		
		$visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
		$viewMgr = $visit->get(CerberusVisit::KEY_VIEW_MANAGER); /* @var $viewMgr CerberusStaticViewManager */
		
		if(null == (@$view = $viewMgr->getView('contact_history'))) {
			$view = new CerberusDashboardView();
			$view->id = 'contact_history';
			$view->name = 'Contact History';
			$view->view_columns = array(
				SearchFields_Ticket::TICKET_NEXT_ACTION,
				SearchFields_Ticket::TICKET_CREATED_DATE,
				SearchFields_Ticket::TEAM_NAME,
				SearchFields_Ticket::TICKET_CATEGORY_ID,
				SearchFields_Ticket::TICKET_SPAM_SCORE,
				SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
			);
			$view->params = array(
//				SearchFields_Ticket::TICKET_FIRST_WROTE => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE,DevblocksSearchCriteria::OPER_EQ,0)
			);
			$view->renderLimit = 10;
			$view->renderPage = 0;
			$view->renderSortBy = SearchFields_Ticket::TICKET_CREATED_DATE;
			$view->renderSortAsc = false;
			$viewMgr->setView('contact_history', $view);
		}

		$view->name = "Most recent tickets from " . htmlentities($contact->email);
		$view->params = array(
			SearchFields_Ticket::TICKET_FIRST_WROTE => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE,DevblocksSearchCriteria::OPER_EQ,$contact->email)
		);
		$tpl->assign('view', $view);
		
		$viewActions = DAO_DashboardViewAction::getList();
		$tpl->assign('viewActions', $viewActions);
		
		$workers = DAO_Worker::getList();
		$tpl->assign('workers', $workers);
		
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		
		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);
		
		$team_categories = DAO_Bucket::getTeams();
		$tpl->assign('team_categories', $team_categories);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/history/index.tpl.php');
	}

	// Ajax
	function showTemplatesPanelAction() {
		@$reply_id = DevblocksPlatform::importGPC($_REQUEST['reply_id'],'integer');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$tpl->assign('reply_id', $reply_id);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/rpc/email_templates/templates_panel.tpl.php');
	}
	
	// Ajax
	function showTemplateEditPanelAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		@$reply_id = DevblocksPlatform::importGPC($_REQUEST['reply_id'],'integer');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$tpl->assign('reply_id', $reply_id);

		$folders = DAO_MailTemplateReply::getFolders();
		$tpl->assign('folders', $folders);
		
		$template = DAO_MailTemplateReply::get($id);
		$tpl->assign('template', $template);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/rpc/email_templates/template_edit_panel.tpl.php');
	}
	
	// Ajax
	function saveReplyTemplateAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string','');
		@$description = DevblocksPlatform::importGPC($_REQUEST['description'],'string','');
		@$folder = DevblocksPlatform::importGPC($_REQUEST['folder'],'string','');
		@$folder_new = DevblocksPlatform::importGPC($_REQUEST['folder_new'],'string','');
		@$content = DevblocksPlatform::importGPC($_REQUEST['template'],'string','');
		@$delete = DevblocksPlatform::importGPC($_REQUEST['delete'],'integer',0);
		
		$worker = CerberusApplication::getActiveWorker();
		
		// [TODO] DAO
		// [TODO] Delete

		if(empty($delete)) {
			$fields = array(
				DAO_MailTemplateReply::TITLE => $title,
				DAO_MailTemplateReply::FOLDER => (!empty($folder)?$folder:$folder_new),
				DAO_MailTemplateReply::DESCRIPTION => $description,
				DAO_MailTemplateReply::CONTENT => $content,
				DAO_MailTemplateReply::OWNER_ID => $worker->id,
			);
			
			if(empty($id)) { // new
				$id = DAO_MailTemplateReply::create($fields);
				
			} else { // edit
				DAO_MailTemplateReply::update($id, $fields);			
				
			}
			
		} else { // delete
			DAO_MailTemplateReply::delete($id);
		}
		
	}
	
	// Ajax
	function getTemplateAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		@$reply_id = DevblocksPlatform::importGPC($_REQUEST['reply_id'],'integer');

		$template = DAO_MailTemplateReply::get($id);
		echo $template->getRenderedContent($reply_id);
	}

	// Ajax
	function getTemplatesAction() {
		@$reply_id = DevblocksPlatform::importGPC($_REQUEST['reply_id'],'integer');
		@$folder = DevblocksPlatform::importGPC($_REQUEST['folder'],'string','');
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$tpl->assign('reply_id', $reply_id);
		
		if(empty($folder)) {
			$where = null;
		} else {
			$where = sprintf("%s=%s",
				DAO_MailTemplateReply::FOLDER,
				$db->qstr($folder)
			);
		} 
		
		$templates = DAO_MailTemplateReply::getWhere($where);
		$tpl->assign('templates', $templates);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/rpc/email_templates/template_results.tpl.php');
	} 
	
	// Ajax
	function showTemplateListAction() {
		@$reply_id = DevblocksPlatform::importGPC($_REQUEST['reply_id'],'integer');
		@$folder = DevblocksPlatform::importGPC($_REQUEST['folder'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$tpl->assign('reply_id', $reply_id);
		
		// [TODO] Folder filter
		$folders = DAO_MailTemplateReply::getFolders();
		$tpl->assign('folders', $folders);
		
		$templates = DAO_MailTemplateReply::getWhere();
		$tpl->assign('templates', $templates);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/rpc/email_templates/template_list.tpl.php');
	}
	
	// Ajax
	function showFnrPanelAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$topics = DAO_FnrTopic::getWhere();
		$tpl->assign('topics', $topics);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/rpc/reply_links_panel.tpl.php');
	}
	
	function doFnrAction() {
		@$q = DevblocksPlatform::importGPC($_POST['q'], 'string', '');
		@$sources = DevblocksPlatform::importGPC($_POST['sources'], 'array', array());

		@$sources = array_flip($sources);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$topics = DAO_FnrTopic::getWhere();
		
    	$feeds = array();
    	$where = null;
    	
    	if(!empty($sources)) {
    		$where = sprintf("%s IN (%s)",
    			DAO_FnrExternalResource::ID,
    			implode(',', array_keys($sources))
    		);
    	}
    	
    	$resources = DAO_FnrExternalResource::getWhere($where);
    	
    	foreach($resources as $resource) { /* @var $resource Model_FnrExternalResource */
	    	try {
	    		$url = str_replace("#find#",urlencode($q),$resource->url);
	    		$feed = Zend_Feed::import($url);
	   			if($feed->count())
	   				$feeds[] = array(
	   					'name' => $resource->name,
	   					'topic_name' => @$topics[$resource->topic_id]->name, 
	   					'feed' => $feed
	   				);
	    	} catch(Exception $e) {}
    	}

		// $rss_jira = "http://www.wgmdev.com/jira/secure/IssueNavigator.jspa?view=rss&pid=10060&summary=true&description=true&tempMax=25&reset=true&decorator=none&query=".urlencode($q);
		// $atom_forums = "http://forum.cerberusweb.com/search.php?PostBackAction=Search&Type=Topics&btnSubmit=Search&Feed=Atom&Keywords=".urlencode($q);
		// $rss_wiki = "http://wiki.cerberusdemo.com/index.php/Special:SearchFeed?term=".urlencode($q);

    	$tpl->assign('terms', $q);
    	$tpl->assign('feeds', $feeds);
    	$tpl->assign('sources', $sources);		
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/fnr/results.tpl.php');
	}
	
};

class ChSignInPage extends CerberusPageExtension {
    const KEY_FORGOT_EMAIL = 'login.recover.email';
    const KEY_FORGOT_SENTCODE = 'login.recover.sentcode';
    const KEY_FORGOT_CODE = 'login.recover.code';
    
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function isVisible() {
		return true;
	}
	
	function render() {
	    $response = DevblocksPlatform::getHttpResponse();
	    $stack = $response->path;
	    array_shift($stack); // login
        $section = array_shift($stack);
        
        switch($section) {
            case "forgot":
                $step = array_shift($stack);
                $tpl = DevblocksPlatform::getTemplateService();
                $path = realpath(dirname(__FILE__) . "/templates");
                
                switch($step) {
                    default:
                    case "step1":
                        $tpl->display("file:${path}/login/forgot1.tpl.php");
                        break;
                    
                    case "step2":
                        $tpl->display("file:${path}/login/forgot2.tpl.php");
                        break;
                        
                    case "step3":
                        $tpl->display("file:${path}/login/forgot3.tpl.php");
                        break;
                }
                
                break;
            default:
				$manifest = DevblocksPlatform::getExtension('login.default');
				$inst = $manifest->createInstance(1); /* @var $inst CerberusLoginPageExtension */
				$inst->renderLoginForm();
                break;
        }
	}
	
	function showAction() {
//		echo "You clicked: " . __CLASS__ . "->" . __FUNCTION__ . "!<br>";
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('login')));
	}

	// POST
	function authenticateAction() {
		@$email				= DevblocksPlatform::importGPC($_POST['email']);
		@$password			= DevblocksPlatform::importGPC($_POST['password']);
		@$original_path		= explode(',',DevblocksPlatform::importGPC($_POST['original_path']));
		@$original_query_str= DevblocksPlatform::importGPC($_POST['original_query']);
		//@$original_url		= DevblocksPlatform::importGPC($_POST['original_url']);
		
		$manifest = DevblocksPlatform::getExtension('login.default');
		$inst = $manifest->createInstance(); /* @var $inst CerberusLoginPageExtension */

		$url_service = DevblocksPlatform::getUrlService();
		
		if($inst->authenticate(array('email' => $email, 'password' => $password))) {
			//authentication passed
			$original_query = $url_service->parseQueryString($original_query_str);
			if($original_path[0]=='')
				unset($original_path[0]);
			
			$devblocks_response = new DevblocksHttpResponse($original_path, $original_query);
			if($devblocks_response->path[0]=='login') {
				$session = DevblocksPlatform::getSessionService();
				$visit = $session->getVisit();
		        $tour_enabled = false;
				if(!empty($visit) && !is_null($visit->getWorker())) {
		        	$worker = $visit->getWorker();
					$tour_enabled = DAO_WorkerPref::get($worker->id, 'assist_mode');
					$tour_enabled = ($tour_enabled===false) ? 1 : $tour_enabled;
				}
				$next_page = ($tour_enabled) ?  'welcome' : 'tickets';				
				$devblocks_response = new DevblocksHttpResponse(array($next_page));
			}
		}
		else {
			//authentication failed
			$devblocks_response = new DevblocksHttpResponse(array('login'));
		}
		DevblocksPlatform::redirect($devblocks_response);
	}
	
	function signoutAction() {
//		echo "Sign out: " . __CLASS__ . "->" . __FUNCTION__ . "!<br>";
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		DAO_Worker::logActivity($visit->getWorker()->id, new Model_Activity(null));
		$session->clear();
		//DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('login')));
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login')));
	}
	
	// Post
	function doRecoverStep1Action() {
	    @$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string');
	    
	    $worker = DAO_Worker::lookupAgentEmail($email);
	    
	    if(empty($email) || empty($worker))
	        return;
	    
	    $_SESSION[self::KEY_FORGOT_EMAIL] = $email;
	    
	    $mail_service = DevblocksPlatform::getMailService();
	    $mailer = $mail_service->getMailer();
		$mail = $mail_service->createMessage();
	    
	    $code = CerberusApplication::generatePassword(10);
	    
	    $_SESSION[self::KEY_FORGOT_SENTCODE] = $code;
	    $settings = CerberusSettings::getInstance();
		$from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM);
	    $personal = $settings->get(CerberusSettings::DEFAULT_REPLY_PERSONAL);
		
		$sendTo = new Swift_Address($email);
		$sendFrom = new Swift_Address($from, $personal);
	    
		// Headers
		$mail->setTo($sendTo);
		$mail->setFrom($sendFrom);
		$mail->setSubject("Confirm helpdesk password recovery.");
		$mail->generateId();
		$mail->headers->set('X-Mailer','Cerberus Helpdesk (Build '.APP_BUILD.')');

		$mail->attach(new Swift_Message_Part(
			sprintf("This confirmation code will allow you to reset your helpdesk login:\n\n%s",
	        	$code
	    )));
		
		if(!$mailer->send($mail, $sendTo, $sendFrom)) {
			// [TODO] Report when the message wasn't sent.
		}
	    
	    //DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('login','forgot','step2')));
	    DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login','forgot','step2')));
	}
	
	// Post
	function doRecoverStep2Action() {
        @$code = DevblocksPlatform::importGPC($_REQUEST['code'],'string');

        $email = $_SESSION[self::KEY_FORGOT_EMAIL];
        $sentcode = $_SESSION[self::KEY_FORGOT_SENTCODE];
        $_SESSION[self::KEY_FORGOT_CODE] = $code;
        
	    $worker_id = DAO_Worker::lookupAgentEmail($email);
	    
	    if(empty($email) || empty($worker_id) || empty($code))
	        return;
        
	    if(0 == strcmp($sentcode,$code)) { // passed
            //DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('login','forgot','step3')));
            DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('login','forgot','step3')));	        
	    } else {
            //DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('login','forgot','step2')));	        
            DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login','forgot','step2')));
	    }
	}
	
	// Post
	function doRecoverStep3Action() {
        @$password = DevblocksPlatform::importGPC($_REQUEST['password'],'string');

        $email = $_SESSION[self::KEY_FORGOT_EMAIL];
        $sentcode = $_SESSION[self::KEY_FORGOT_SENTCODE];
        $code = $_SESSION[self::KEY_FORGOT_CODE];
        
	    $worker_id = DAO_Worker::lookupAgentEmail($email);
	    
	    if(empty($email) || empty($code) || empty($worker_id))
	        return;
        
	    if(0 == strcmp($sentcode,$code)) { // passed
	        DAO_Worker::updateAgent($worker_id, array(
	            DAO_Worker::PASSWORD => md5($password)
	        ));
	        
            unset($_SESSION[self::KEY_FORGOT_EMAIL]);
            unset($_SESSION[self::KEY_FORGOT_CODE]);
            unset($_SESSION[self::KEY_FORGOT_SENTCODE]);
            
            //DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('login')));
            DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login')));
	    } else {
	        //DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('login','forgot','step2')));
	        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login','forgot','step2')));
	    }
        
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
		$tpl_path = dirname(__FILE__) . '/templates';
		$tpl->assign('path', $tpl_path);
		$tpl->cache_lifetime = "0";

		$response = DevblocksPlatform::getHttpResponse();
		$path = $response->path;
		
		array_shift($path); // preferences
		
		switch(strtolower(array_shift($path))) {
		    case 'general':
		    default:
				$worker = CerberusApplication::getActiveWorker();
				
				$tour_enabled = DAO_WorkerPref::get($worker->id, 'assist_mode');
				$tour_enabled = ($tour_enabled===false) ? 1 : $tour_enabled;

				$tpl->assign('assist_mode', $tour_enabled);
				$tpl->display('file:' . $tpl_path . '/preferences/general.tpl.php');
				break;
		}
	}
	
	// Post
	function saveDefaultsAction() {
		@$timezone = DevblocksPlatform::importGPC($_REQUEST['timezone'],'string');
		@$default_signature = DevblocksPlatform::importGPC($_REQUEST['default_signature'],'string');
		@$reply_box_height = DevblocksPlatform::importGPC($_REQUEST['reply_box_height'],'integer');
	    
		$worker = CerberusApplication::getActiveWorker();
   		
		$new_password = DevblocksPlatform::importGPC($_REQUEST['change_pass'],'string');
		$verify_password = DevblocksPlatform::importGPC($_REQUEST['change_pass_verify'],'string');
    	
		//[mdf] if nonempty passwords match, update worker's password
		if($new_password != "" && $new_password===$verify_password) {
			$session = DevblocksPlatform::getSessionService();
			$fields = array(
				DAO_Worker::PASSWORD => md5($new_password)
			);
			DAO_Worker::updateAgent($worker->id, $fields);
		}

		@$assist_mode = DevblocksPlatform::importGPC($_REQUEST['assist_mode'],'integer',0);
		DAO_WorkerPref::set($worker->id, 'assist_mode', $assist_mode);
	}
};

?>
