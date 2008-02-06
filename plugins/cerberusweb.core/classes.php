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
	public function _getPageIdByUri($uri) {
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
        @$page = $pages[$page_id]; /* @var $page CerberusPageExtension */

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
			    
			    if($page->isVisible()) {
					if(method_exists($page,$action)) {
						call_user_func(array(&$page, $action)); // [TODO] Pass HttpRequest as arg?
					}
				} else {
					// if Ajax [TODO] percolate isAjax from platform to handleRequest
					// die("Access denied.  Session expired?");
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

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Clear all undo actions on reload
	    C4_TicketView::clearLastActions();
	    				
		$quick_search_type = $visit->get('quick_search_type');
		$tpl->assign('quick_search_type', $quick_search_type);
	    
		// ====== Who's Online
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
		
		// Remember the last subsection
		switch($section) {
			case 'overview':
			case 'lists':
			case 'search':
				$visit->set(CerberusVisit::KEY_MAIL_MODE, $section);
				break;
			case NULL:
				$section = $visit->get(CerberusVisit::KEY_MAIL_MODE, '');
				break;
		}
		
		// ====== Renders
		switch($section) {
			case 'search':
				$view = C4_AbstractViewLoader::getView('', CerberusApplication::VIEW_SEARCH);
				
				// [TODO] Need to make a search view if it's gone
				if(null == $view) {
					$view = C4_TicketView::createSearchView();
				
					C4_AbstractViewLoader::setView($view->id,$view);
//					$this->setView(CerberusApplication::VIEW_SEARCH,$view);
				}
				
				$tpl->assign('view', $view);
				$tpl->assign('params', $view->params);

//				// [TODO] Once this moves to the global scope and is cached I don't need to include it everywhere
//				$workers = DAO_Worker::getAll();
//				$tpl->assign('workers', $workers);
				
				$teams = DAO_Group::getAll();
				$tpl->assign('teams', $teams);
				
				$buckets = DAO_Bucket::getAll();
				$tpl->assign('buckets', $buckets);
				
				$team_categories = DAO_Bucket::getTeams();
				$tpl->assign('team_categories', $team_categories);
				
				$tpl->assign('view_fields', C4_TicketView::getFields());
				$tpl->assign('view_searchable_fields', C4_TicketView::getSearchFields());
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/search/index.tpl.php');
				break;
				
//			case 'create':
//				$teams = DAO_Group::getAll();
//				
//				$team_id = $visit->get(CerberusVisit::KEY_WORKSPACE_GROUP_ID, 0);
//				if($team_id) {
//					$tpl->assign('team', $teams[$team_id]);
//				}
//				
//				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/create/index.tpl.php');
//				break;
			
			case 'compose':
				$settings = CerberusSettings::getInstance();
				$teams = DAO_Group::getAll();
				
				if($visit->exists('compose.last_ticket')) {
					$ticket_mask = $visit->get('compose.last_ticket');
					$tpl->assign('last_ticket_mask', $ticket_mask);
					$visit->set('compose.last_ticket',null); // clear
				}
				
				$worker = CerberusApplication::getActiveWorker();
				foreach($teams as $team){
					$team->signature = rawurlencode(str_replace(
			        	array('#first_name#','#last_name#','#title#'),
			        	array($worker->first_name,$worker->last_name,$worker->title),
			        	$team->signature
			        ));
				}
				
				$tpl->assign_by_ref('teams', $teams);
				
				$default_sig = $settings->get(CerberusSettings::DEFAULT_SIGNATURE,'');
				$tpl->assign('default_sig',rawurlencode(str_replace(
		        	array('#first_name#','#last_name#','#title#'),
		        	array($worker->first_name,$worker->last_name,$worker->title),
		        	$default_sig
		        )));
				
				$team_categories = DAO_Bucket::getTeams();
				$tpl->assign('team_categories', $team_categories);
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/compose/index.tpl.php');
				break;
			
			default:
			case 'overview':
				$db = DevblocksPlatform::getDatabaseService();
				$views = array();

				$response_path = $response->path;
				@array_shift($response_path); // tickets
				@array_shift($response_path); // overview
				
				$groups = DAO_Group::getAll();
				$tpl->assign('groups', $groups);
				
				$group_buckets = DAO_Bucket::getTeams();
				$tpl->assign('group_buckets', $group_buckets);
				
				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				
				$slas = DAO_Sla::getAll();
				$tpl->assign('slas', $slas);
				
				$memberships = $active_worker->getMemberships();

				// Totals
				
				$group_counts = C4_Overview::getGroupTotals();
				$tpl->assign('group_counts', $group_counts);

				$waiting_counts = C4_Overview::getWaitingTotals();
				$tpl->assign('waiting_counts', $waiting_counts);
				
				$worker_counts = C4_Overview::getWorkerTotals();
				$tpl->assign('worker_counts', $worker_counts);
				
				$sla_counts = C4_Overview::getSlaTotals();
				$tpl->assign('sla_counts', $sla_counts);
				
				// All Open
				$overView = C4_AbstractViewLoader::getView('', CerberusApplication::VIEW_OVERVIEW_ALL);
				
				$title = "All Groups (Spam Filtered)";
				
				// [JAS]: Recover from a bad cached ID.
				if(null == $overView) {
					// Defaults
					$overViewDefaults = new C4_AbstractViewModel();
					$overViewDefaults->view_columns = array(
						SearchFields_Ticket::TICKET_NEXT_ACTION,
						SearchFields_Ticket::TICKET_UPDATED_DATE,
						SearchFields_Ticket::TEAM_NAME,
						SearchFields_Ticket::TICKET_CATEGORY_ID,
						SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
					);
					$overViewDefaults->renderLimit = 10;
					$overViewDefaults->renderSortBy = SearchFields_Ticket::TICKET_UPDATED_DATE;
					$overViewDefaults->renderSortAsc = 0;

					// If the worker has other default preferences, load them instead
					if(!DEMO_MODE && null != ($overViewPrefsStr = DAO_WorkerPref::get($active_worker->id, DAO_WorkerPref::SETTING_OVERVIEW, null))) {
						@$overViewPrefs = unserialize($overViewPrefsStr); /* @var C4_AbstractViewModel $overViewPrefs */

						if($overViewPrefs instanceof C4_AbstractViewModel) {
							if(!is_null($overViewPrefs->view_columns)) 
								$overViewDefaults->view_columns = $overViewPrefs->view_columns;
							if(!is_null($overViewPrefs->renderLimit)) 
								$overViewDefaults->renderLimit = $overViewPrefs->renderLimit;
							if(!is_null($overViewPrefs->renderSortBy)) 
								$overViewDefaults->renderSortBy = $overViewPrefs->renderSortBy;
							if(!is_null($overViewPrefs->renderSortAsc)) 
								$overViewDefaults->renderSortAsc = $overViewPrefs->renderSortAsc;
						}
					}
					
					$overView = new C4_TicketView();
					$overView->id = CerberusApplication::VIEW_OVERVIEW_ALL;
					$overView->name = $title;
					$overView->dashboard_id = 0;
					$overView->view_columns = $overViewDefaults->view_columns;
					$overView->params = array();
					$overView->renderLimit = $overViewDefaults->renderLimit;
					$overView->renderPage = 0;
					$overView->renderSortBy = $overViewDefaults->renderSortBy;
					$overView->renderSortAsc = $overViewDefaults->renderSortAsc;
					
					C4_AbstractViewLoader::setView($overView->id,$overView);
				}
				
				$overView->params = array(
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',CerberusTicketStatus::OPEN),
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_WAITING,'=',0),
				);
				
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
							SearchFields_Ticket::TICKET_NEXT_WORKER_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_NEXT_WORKER_ID,'=',0),
						);
						
						if(!is_null($filter_group_id) && isset($groups[$filter_group_id])) {
							$tpl->assign('filter_group_id', $filter_group_id);
							$title = $groups[$filter_group_id]->name;
							$overView->params[SearchFields_Ticket::TEAM_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TEAM_ID,'=',$filter_group_id);
							
							@$filter_bucket_id = array_shift($response_path);
							if(!is_null($filter_bucket_id)) {
								$tpl->assign('filter_bucket_id', $filter_bucket_id);
								@$title .= ': '.
									(($filter_bucket_id == 0) ? 'Inbox' : $group_buckets[$filter_group_id][$filter_bucket_id]->name);
								$overView->params[SearchFields_Ticket::TICKET_CATEGORY_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CATEGORY_ID,'=',$filter_bucket_id);
							} else {
								@$title .= ' (Spam Filtered)';
								$overView->params[SearchFields_Ticket::TICKET_SPAM_SCORE] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_SPAM_SCORE,'<=','0.9000');								
							}
						}

						break;
						
					case 'waiting':
						@$filter_waiting_id = array_shift($response_path);
						
						$overView->params = array(
							SearchFields_Ticket::TICKET_CLOSED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',CerberusTicketStatus::OPEN),
							SearchFields_Ticket::TICKET_WAITING => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_WAITING,'=',1),
						);
						
						if(!is_null($filter_waiting_id) && isset($groups[$filter_waiting_id])) {
							$tpl->assign('filter_waiting_id', $filter_waiting_id);
							$title = '[Waiting] ' . $groups[$filter_waiting_id]->name;
							$overView->params[SearchFields_Ticket::TEAM_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TEAM_ID,'=',$filter_waiting_id);
							
							@$filter_bucket_id = array_shift($response_path);
							if(!is_null($filter_bucket_id)) {
								$tpl->assign('filter_bucket_id', $filter_bucket_id);
								@$title .= ': '.
									(($filter_bucket_id == 0) ? 'Inbox' : $group_buckets[$filter_waiting_id][$filter_bucket_id]->name);
								$overView->params[SearchFields_Ticket::TICKET_CATEGORY_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CATEGORY_ID,'=',$filter_bucket_id);
							}
						}

						break;
						
					case 'worker':
						@$filter_worker_id = array_shift($response_path);

						$overView->params = array(
							SearchFields_Ticket::TICKET_CLOSED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',CerberusTicketStatus::OPEN),
							SearchFields_Ticket::TICKET_WAITING => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_WAITING,'=',0),
						);

						if(!is_null($filter_worker_id)) {
							$tpl->assign('filter_bucket_id', $filter_bucket_id);
							$title = "For ".$workers[$filter_worker_id]->getName();
							$overView->params[SearchFields_Ticket::TICKET_NEXT_WORKER_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_NEXT_WORKER_ID,'=',$filter_worker_id);
							
							@$filter_group_id = array_shift($response_path);
							if(!is_null($filter_group_id) && isset($groups[$filter_group_id])) {
								$title .= ' in '.$groups[$filter_group_id]->name;
								$overView->params[SearchFields_Ticket::TEAM_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TEAM_ID,'=',$filter_group_id);
							}
						}
						
						break;
						
					case 'sla':
						@$filter_sla_id = array_shift($response_path);
						
						$overView->params = array(
							SearchFields_Ticket::TICKET_CLOSED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',CerberusTicketStatus::OPEN),
							SearchFields_Ticket::TICKET_WAITING => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_WAITING,'=',0),							
							SearchFields_Ticket::TICKET_NEXT_WORKER_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_NEXT_WORKER_ID,'=',0),
						);

						if(!is_null($filter_sla_id)) {
							$tpl->assign('filter_sla_id', $filter_sla_id);
							$title = "".$slas[$filter_sla_id]->name;
							$overView->params[SearchFields_Ticket::TICKET_SLA_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_SLA_ID,'=',$filter_sla_id);
						}
						
						break;
						
					case 'all':
					default:
						$overView->params = array(
							SearchFields_Ticket::TICKET_CLOSED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',CerberusTicketStatus::OPEN),
							SearchFields_Ticket::TICKET_WAITING => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_WAITING,'=',0),
							SearchFields_Ticket::TICKET_NEXT_WORKER_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_NEXT_WORKER_ID,'=',0),
							SearchFields_Ticket::TICKET_SPAM_SCORE => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_SPAM_SCORE,'<=','0.9000'),
							SearchFields_Ticket::TEAM_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TEAM_ID,'in',array_keys($memberships)),
						);
						
						break;
				}
				
				$overView->name = $title;
				C4_AbstractViewLoader::setView($overView->id, $overView);
				$views[] = $overView;
				
				$tpl->assign('views', $views);
				
	        	$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/overview/index.tpl.php');
	        	break;
			    
	        case 'lists':
				$request = DevblocksPlatform::getHttpRequest();
				$request_path = $request->path;
				array_shift($request_path); // tickets
				array_shift($request_path); // lists

				$db = DevblocksPlatform::getDatabaseService();
				
				$current_workspace = $visit->get(CerberusVisit::KEY_MY_WORKSPACE,'');
				
				$workspaces = DAO_WorkerWorkspaceList::getWorkspaces($active_worker->id);
				$tpl->assign('workspaces', $workspaces);

				// Fix a bad/old cache
				if(!empty($current_workspace) && false === array_search($current_workspace,$workspaces))
					$current_workspace = '';
				
				$views = array();
					
				if(empty($current_workspace) && !empty($workspaces)) { // custom dashboards
					$current_workspace = reset($workspaces);
				}
				
				if(!empty($current_workspace)) {
					$lists = DAO_WorkerWorkspaceList::getWhere(sprintf("%s = %d AND %s = %s",
						DAO_WorkerWorkspaceList::WORKER_ID,
						$active_worker->id,
						DAO_WorkerWorkspaceList::WORKSPACE,
						$db->qstr($current_workspace)
					));
					
					if(is_array($lists) && !empty($lists))
					foreach($lists as $list) { /* @var $list Model_WorkerWorkspaceList */
						$view_id = 'cust_'.$list->id;
						if(null == ($view = C4_AbstractViewLoader::getView('',$view_id))) {
							$list_view = $list->list_view; /* @var $list_view Model_WorkerWorkspaceListView */
							
							$view = new C4_TicketView();
							$view->id = $view_id;
							$view->name = $list_view->title;
							$view->renderLimit = $list_view->num_rows;
							$view->renderPage = 0;
							$view->view_columns = $list_view->columns;
							$view->params = $list_view->params;
							C4_AbstractViewLoader::setView($view_id, $view);
						}
						$views[] = $view;
					}
				
					$tpl->assign('current_workspace', $current_workspace);
					$tpl->assign('views', $views);
				}
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/lists/index.tpl.php');
				break;
		}
		
	}
	
	//**** Local scope

		// Ajax
	function refreshOverviewTotalsAction() {
		$db = DevblocksPlatform::getDatabaseService();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$visit = CerberusApplication::getVisit();
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$response_path = explode('/',$visit->get(CerberusVisit::KEY_OVERVIEW_FILTER, 'all'));
		@$filter = array_shift($response_path);
		
		switch($filter) {
			case 'group':
				@$filter_group_id = array_shift($response_path);
				if(!empty($filter_group_id))
					$tpl->assign('filter_group_id', $filter_group_id);
				break;
			case 'waiting':
				@$filter_waiting_id = array_shift($response_path);
				if(!empty($filter_waiting_id))
					$tpl->assign('filter_waiting_id', $filter_waiting_id);
				break;
		}
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$group_buckets = DAO_Bucket::getTeams();
		$tpl->assign('group_buckets', $group_buckets);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$slas = DAO_Sla::getAll();
		$tpl->assign('slas', $slas);
		
		$group_counts = C4_Overview::getGroupTotals();
		$tpl->assign('group_counts', $group_counts);
		
		$waiting_counts = C4_Overview::getWaitingTotals();
		$tpl->assign('waiting_counts', $waiting_counts);
		
		$worker_counts = C4_Overview::getWorkerTotals();
		$tpl->assign('worker_counts', $worker_counts);
		
		$sla_counts = C4_Overview::getSlaTotals();
		$tpl->assign('sla_counts', $sla_counts);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/overview/sidebar.tpl.php');
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
		$path = dirname(__FILE__) . '/templates/';
		$tpl->assign('path', $path);

	    $visit = CerberusApplication::getVisit();
		$view = C4_AbstractViewLoader::getView('',$view_id);
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
//		$request = DevblocksPlatform::getHttpRequest();

		$worker = CerberusApplication::getActiveWorker();
		DAO_WorkerPref::set($worker->id, 'assist_mode', 0);
		
//		DevblocksPlatform::redirect(new DevblocksHttpResponse($request->path, $request->query));
	}
	
	function showReorderWorkspacePanelAction() {
		@$workspace = DevblocksPlatform::importGPC($_REQUEST['workspace'],'string', '');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(__FILE__) . '/templates/';
		$tpl->assign('path', $tpl_path);

		$db = DevblocksPlatform::getDatabaseService();
		
		$active_worker = CerberusApplication::getActiveWorker();

		$tpl->assign('workspace', $workspace);
		
		$worklists = DAO_WorkerWorkspaceList::getWhere(sprintf("%s = %s AND %s = %d",
			DAO_WorkerWorkspaceList::WORKSPACE,
			$db->qstr($workspace),
			DAO_WorkerWorkspaceList::WORKER_ID,
			$active_worker->id
		));
		$tpl->assign('worklists', $worklists);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/lists/reorder_workspace_panel.tpl.php');
	}
	
	function doReorderWorkspaceAction() {
		@$workspace = DevblocksPlatform::importGPC($_POST['workspace'],'string', '');
		@$ids = DevblocksPlatform::importGPC($_POST['ids'],'array', array());
		@$pos = DevblocksPlatform::importGPC($_POST['pos'],'array', array());
		
		if(is_array($ids) && !empty($ids))
		foreach($ids as $idx => $id) {
			DAO_WorkerWorkspaceList::update($id,array(
				DAO_WorkerWorkspaceList::LIST_POS => @intval($pos[$idx])
			));
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','lists')));	
	}
	
	function showAddListPanelAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(__FILE__) . '/templates/';
		$tpl->assign('path', $tpl_path);

		$active_worker = CerberusApplication::getActiveWorker();
		
		$workspaces = DAO_WorkerWorkspaceList::getWorkspaces($active_worker->id);
		$tpl->assign('workspaces', $workspaces);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/lists/add_view_panel.tpl.php');
	}
	
	function saveAddListPanelAction() {
		@$list_title = DevblocksPlatform::importGPC($_POST['list_title'],'string', '');
		@$workspace = DevblocksPlatform::importGPC($_POST['workspace'],'string', '');
		@$new_workspace = DevblocksPlatform::importGPC($_POST['new_workspace'],'string', '');
		
		if(empty($workspace) && empty($new_workspace))
			$new_workspace = "New Workspace";
			
		if(empty($list_title))
			$list_title = "New List";
		
		$workspace_name = (!empty($new_workspace) ? $new_workspace : $workspace);
			
		// [TODO] Fix crossing layers (app->DAO)
		$active_worker = CerberusApplication::getActiveWorker();
		
		// List
		$list_view = new Model_WorkerWorkspaceListView();
		$list_view->title = $list_title;
		$list_view->num_rows = 10;
		$list_view->columns = array(
			SearchFields_Ticket::TICKET_NEXT_ACTION,
			SearchFields_Ticket::TICKET_UPDATED_DATE,
			SearchFields_Ticket::TICKET_CATEGORY_ID,
			SearchFields_Ticket::TICKET_SPAM_SCORE,
			SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
		);
		$list_view->params = array(
			SearchFields_Ticket::TICKET_CLOSED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',CerberusTicketStatus::OPEN)
		);
		
		$fields = array(
			DAO_WorkerWorkspaceList::WORKER_ID => $active_worker->id,
			DAO_WorkerWorkspaceList::WORKSPACE => $workspace_name,
			DAO_WorkerWorkspaceList::LIST_VIEW => serialize($list_view),
			DAO_WorkerWorkspaceList::LIST_POS => 0 // [TODO] Add to end?
		);
		$list_id = DAO_WorkerWorkspaceList::create($fields);
		
		// [TODO] Switch response to proper workspace
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','lists')));
	}
	
	// Post
//	function saveTeamFiltersAction() {
//	    @$team_id = DevblocksPlatform::importGPC($_POST['team_id'],'integer');
//	    @$categories = DevblocksPlatform::importGPC($_POST['categories'],'array');
//	    @$categorized = DevblocksPlatform::importGPC($_POST['categorized'],'integer');
////	    @$show_waiting = DevblocksPlatform::importGPC($_POST['show_waiting'],'integer');
////	    @$hide_assigned = DevblocksPlatform::importGPC($_POST['hide_assigned'],'integer');
//	    @$add_buckets = DevblocksPlatform::importGPC($_POST['add_buckets'],'string');
//
//	    // Adds: Sort and insert team categories
//	    if(!empty($add_buckets)) {
//		    $buckets = DevblocksPlatform::parseCrlfString($add_buckets);
//	
//		    if(is_array($buckets))
//		    foreach($buckets as $bucket) {
//	            if(empty($bucket))
//	                continue;
//	                
//		        $bucket_id = DAO_Bucket::create($bucket, $team_id);
//		    }
//	    }
//	    
//	    if(!isset($_SESSION['team_filters']))
//	        $_SESSION['team_filters'] = array();
//	    
//	    $filters = array(
//	        'categories' => array_flip($categories),
//	        'categorized' => $categorized,
////	        'hide_assigned' => $hide_assigned,
////	        'show_waiting' => $show_waiting
//	    );
//	    $_SESSION['team_filters'][$team_id] = $filters;
//	    
//	    //DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','workspaces','team',$team_id)));
//	    DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','workspaces','team',$team_id)));
//	}
	
	// Ajax
//	function refreshTeamFiltersAction() {
////	    @$team_id = DevblocksPlatform::importGPC($_POST['team_id'],'integer');
//
//        $visit = CerberusApplication::getVisit();
//        $active_dashboard_id = $visit->get(CerberusVisit::KEY_DASHBOARD_ID, 0);
//        
//        $team_id = $visit->get(CerberusVisit::KEY_WORKSPACE_GROUP_ID, 0);
//        
//		$tpl = DevblocksPlatform::getTemplateService();
//		$path = dirname(__FILE__) . '/templates/';
//		$tpl->assign('path', $path);
//		
//		$tpl->assign('active_dashboard_id', $active_dashboard_id);
//		$tpl->assign('dashboard_team_id', $team_id);
//
//	    $active_worker = CerberusApplication::getActiveWorker();
//	    if(!empty($active_worker)) {
//	    	$active_worker_memberships = $active_worker->getMemberships();
//	    	$tpl->assign('active_worker_memberships', $active_worker_memberships);
//	    }
//		
//		$teams = DAO_Group::getAll();
//		$tpl->assign('teams', $teams);
//		
//		$buckets = DAO_Bucket::getByTeam($team_id);
//		$tpl->assign('buckets', $buckets);
//				
//		@$team_filters = $_SESSION['team_filters'][$team_id];
//		if(empty($team_filters)) $team_filters = array();
//		$tpl->assign('team_filters', $team_filters);
//		
//		$team_counts = DAO_Group::getTeamCounts(array_keys($teams));
//		$tpl->assign('team_counts', $team_counts);
//		
//		$category_counts = DAO_Bucket::getCategoryCountsByTeam($team_id);
//        $tpl->assign('category_counts', $category_counts);
//		
//		$tpl->display($path.'tickets/dashboard_menu.tpl.php');
//	    
////	    DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','dashboards','team',$team_id)));
//	}
	
	// Post	
	function doQuickSearchAction() {
        @$type = DevblocksPlatform::importGPC($_POST['type'],'string'); 
        @$query = DevblocksPlatform::importGPC($_POST['query'],'string');

        $visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
		$searchView = C4_AbstractViewLoader::getView('',CerberusApplication::VIEW_SEARCH);
		
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
                
            case "subject":
		        if($query && false===strpos($query,'*'))
		            $query = '*' . $query . '*';
            	$params[SearchFields_Ticket::TICKET_SUBJECT] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_SUBJECT,DevblocksSearchCriteria::OPER_LIKE,$query);               
                break;
                
            case "content":
		        if($query && false===strpos($query,'*'))
		            $query = '*' . $query . '*';
            	$params[SearchFields_Ticket::TICKET_MESSAGE_CONTENT] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MESSAGE_CONTENT,DevblocksSearchCriteria::OPER_LIKE,$query);               
                break;
        }
        
        $searchView->params = $params;
        $searchView->renderPage = 0;
        $searchView->renderSortBy = null;
        
        C4_AbstractViewLoader::setView($searchView->id,$searchView);
        
        //DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','search')));
        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','search')));
	}

	// Ajax
	function showAddInboxRulePanelAction() {
	    @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
	    
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(__FILE__) . '/templates/';
		$tpl->assign('path', $tpl_path);
		
		$tpl->assign('ticket_id', $ticket_id);
		$tpl->assign('view_id', $view_id);
		
		@$ticket = DAO_Ticket::getTicket($ticket_id);
		$tpl->assign('ticket', $ticket);

		@$first_address = DAO_Address::get($ticket->first_wrote_address_id);
		$tpl->assign('first_address', $first_address);
		
		// Status
		$statuses = CerberusTicketStatus::getOptions();
		$tpl->assign('statuses', $statuses);

		// Spam Training
		$training = CerberusTicketSpamTraining::getOptions();
		$tpl->assign('training', $training);

		// Grops
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		
		// Buckets
		$team_categories = DAO_Bucket::getTeams();
		$tpl->assign('team_categories', $team_categories);
	    
		// Workers
	    $workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/rpc/inbox_rule_panel.tpl.php');
	}
	
	// Ajax
	function saveAddInboxRulePanelAction() {
   		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
   		@$team_id = DevblocksPlatform::importGPC($_REQUEST['team_id'],'integer');
   		@$field = DevblocksPlatform::importGPC($_REQUEST['field'],'string');
   		@$value = DevblocksPlatform::importGPC($_REQUEST['val'],'string');

   		$view = C4_AbstractViewLoader::getView('C4_TicketView', $view_id); /* @var $view C4_TicketView */
   		
   		if(empty($team_id) || empty($field) || empty($value)) {
   			$view->render();
   			exit;
   		}
   		
   		@$move = DevblocksPlatform::importGPC($_REQUEST['move'],'string');
   		@$status = DevblocksPlatform::importGPC($_REQUEST['status'],'string');
   		@$spam = DevblocksPlatform::importGPC($_REQUEST['spam'],'string');
   		@$assign = DevblocksPlatform::importGPC($_REQUEST['assign'],'string');
   		@$whole_list = DevblocksPlatform::importGPC($_REQUEST['whole_list'],'integer',0);
   		
		$do = array();
	    $fields = array(
   			DAO_TeamRoutingRule::TEAM_ID => $team_id,
   			DAO_TeamRoutingRule::HEADER => (($field=='sender')?'from':'subject'),
   			DAO_TeamRoutingRule::PATTERN => $value,
   			DAO_TeamRoutingRule::POS => 0
   		);

   		if(!empty($move)) {
			$fields[DAO_TeamRoutingRule::DO_MOVE] = $move;
			$do['team'] = $move;   			
   		}
   		if(0 != strlen($status)) {
			$fields[DAO_TeamRoutingRule::DO_STATUS] = intval($status);
			$do['closed'] = $status;   			
   		}
   		if(0 != strlen($spam)) {
			$fields[DAO_TeamRoutingRule::DO_SPAM] = intval($spam);
			$do['spam'] = $spam;   			
   		}
   		if(0 != strlen($assign)) {
			$fields[DAO_TeamRoutingRule::DO_ASSIGN] = intval($assign);
			$do['next_worker'] = $assign;
   		}
   		
   		$routing_id = DAO_TeamRoutingRule::create($fields);
   		
   		// Apply rule to current view
		$view->doBulkUpdate($field, '', array($value), $do, array(), 0);

   		$view->render();
   		exit;
	}
	
	// Ajax
	function showPreviewAction() {
	    @$tid = DevblocksPlatform::importGPC($_REQUEST['tid'],'integer',0);
	    @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
	    
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(__FILE__) . '/templates/';
		$tpl->assign('path', $tpl_path);

		$tpl->assign('view_id', $view_id);
		
		$ticket = DAO_Ticket::getTicket($tid); /* @var $ticket CerberusTicket */
	    $messages = DAO_Ticket::getMessagesByTicket($tid);
	    
        if(!empty($messages)) {	    
	        $last = array_pop($messages);
	        $content = DAO_MessageContent::get($last->id);
        }
	    
	    $tpl->assign('ticket', $ticket);
	    $tpl->assign('message', $last);
	    $tpl->assign('content', $content);
	    
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		
		$team_categories = DAO_Bucket::getTeams();
		$tpl->assign('team_categories', $team_categories);
	    
	    $workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
	    
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/rpc/preview_panel.tpl.php');
	}
	
	// Ajax
	function savePreviewAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$next_action = DevblocksPlatform::importGPC($_REQUEST['next_action'],'string','');
		@$next_worker_id = DevblocksPlatform::importGPC($_REQUEST['next_worker_id'],'integer',0);
		@$bucket = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'string','');
		
		$fields = array(
			DAO_Ticket::NEXT_ACTION => $next_action,
			DAO_Ticket::NEXT_WORKER_ID => $next_worker_id,
		);
		
		// Team/Category
		if(!empty($bucket)) {
			list($team_id,$bucket_id) = CerberusApplication::translateTeamCategoryCode($bucket);

			if(!empty($team_id)) {
			    $fields[DAO_Ticket::TEAM_ID] = $team_id;
			    $fields[DAO_Ticket::CATEGORY_ID] = $bucket_id;
			}
		}
		
		DAO_Ticket::updateTicket($id, $fields);
		
		$view = C4_AbstractViewLoader::getView('C4_TicketView', $view_id);
		$view->render();
		exit;
	}
	
	function clickteamAction() {
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','search')));
	}
	
	function composeMailAction() {
		@$team_id = DevblocksPlatform::importGPC($_POST['team_id'],'integer'); 
		@$to = DevblocksPlatform::importGPC($_POST['to'],'string');
		@$cc = DevblocksPlatform::importGPC($_POST['cc'],'string','');
		@$bcc = DevblocksPlatform::importGPC($_POST['bcc'],'string','');
		@$subject = DevblocksPlatform::importGPC($_POST['subject'],'string','(no subject)');
		@$content = DevblocksPlatform::importGPC($_POST['content'],'string');
		@$files = $_FILES['attachment'];
		
		if(DEMO_MODE) {
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','compose')));
			return;
		}

		if(empty($to)) {
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','compose')));
			return;
		}
		
		// [TODO] [JAS] This method is pretty horribly redundant with CerberusMail::*
		
		$settings = CerberusSettings::getInstance();
		$default_from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM);
		$default_personal = $settings->get(CerberusSettings::DEFAULT_REPLY_PERSONAL);
		$group_settings = DAO_GroupSettings::getSettings($team_id);
		@$team_from = $group_settings[DAO_GroupSettings::SETTING_REPLY_FROM];
		@$team_personal = $group_settings[DAO_GroupSettings::SETTING_REPLY_PERSONAL];
		
		$from = !empty($team_from) ? $team_from : $default_from;
		$personal = !empty($team_personal) ? $team_personal : $default_personal;

		if(empty($subject)) $subject = '(no subject)';
		
		$mail_succeeded = true;
		try {
			$sendTo = new Swift_RecipientList();
			$sendTo->addTo($to);
			
			$sendFrom = new Swift_Address($from, $personal);
			
			$mail_service = DevblocksPlatform::getMailService();
			$mailer = $mail_service->getMailer();
			$email = $mail_service->createMessage();
	
			$email->setTo($to);
			
			// cc
			$ccs = array();
			if(!empty($cc) && null != ($ccList = DevblocksPlatform::parseCsvString($cc))) {
				foreach($ccList as $ccAddy) {
					$sendTo->addCc($ccAddy);
					$ccs[] = new Swift_Address($ccAddy);
				}
				if(!empty($ccs))
					$email->setCc($ccs);
			}
			
			// bcc
			if(!empty($bcc) && null != ($bccList = DevblocksPlatform::parseCsvString($bcc))) {
				foreach($bccList as $bccAddy) {
					$sendTo->addBcc($bccAddy);
				}
			}
			
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
				$mail_succeeded = false;
				throw new Exception('Mail failed to send: unknown reason');
			}
	//		$mailer->log->dump();
		} catch (Exception $e) {
			// tag mail as failed, add note to message after message gets created			
			$mail_succeeded = false;
		}
		
		$worker = CerberusApplication::getActiveWorker();
		$fromAddressInst = CerberusApplication::hashLookupAddress($from, true);
		$fromAddressId = $fromAddressInst->id;
		
		// [TODO] this is redundant with the Parser code.  Should be refactored later
		// Is this address covered by an SLA?
		$sla_id = 0;
		$sla_priority = 0;
		$toAddressInst = CerberusApplication::hashLookupAddress($to, true);
		if(!empty($toAddressInst->sla_id)) {
			if(null != ($toAddressSla = DAO_Sla::get($toAddressInst->sla_id))) {
				@$sla_id = $toAddressSla->id;
				@$sla_priority = $toAddressSla->priority;
			}
		}
		
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
			DAO_Ticket::SLA_ID => $sla_id,
			DAO_Ticket::SLA_PRIORITY => $sla_priority,
		);
		
		// "Next:" [TODO] This is highly redundant with CerberusMail
		@$closed = DevblocksPlatform::importGPC($_POST['closed'],'integer',0);
		@$move_bucket = DevblocksPlatform::importGPC($_POST['bucket_id'],'string','');
		@$next_worker_id = DevblocksPlatform::importGPC($_POST['next_worker_id'],'integer',0);
		@$next_action = DevblocksPlatform::importGPC($_POST['next_action'],'string','');
		@$ticket_reopen = DevblocksPlatform::importGPC($_POST['ticket_reopen'],'string','');
		
		if(isset($closed) && 1==$closed)
			$fields[DAO_Ticket::IS_CLOSED] = 1;
		if(isset($closed) && 2==$closed)
			$fields[DAO_Ticket::IS_WAITING] = 1;
		if(!empty($move_bucket)) {
	        list($team_id, $bucket_id) = CerberusApplication::translateTeamCategoryCode($move_bucket);
		    $fields[DAO_Ticket::TEAM_ID] = $team_id;
		    $fields[DAO_Ticket::CATEGORY_ID] = $bucket_id;
		}
		if(isset($next_worker_id))
			$fields[DAO_Ticket::NEXT_WORKER_ID] = intval($next_worker_id);
		if(!empty($next_action))
			$fields[DAO_Ticket::NEXT_ACTION] = $next_action;
		if(!empty($ticket_reopen)) {
			$due = strtotime($ticket_reopen);
			if($due) $fields[DAO_Ticket::DUE_DATE] = $due;
		}
		// End "Next:"
		
		$ticket_id = DAO_Ticket::createTicket($fields);
		
	    $fields = array(
	        DAO_Message::TICKET_ID => $ticket_id,
	        DAO_Message::CREATED_DATE => time(),
	        DAO_Message::ADDRESS_ID => $fromAddressId,
	        DAO_Message::IS_OUTGOING => 1,
	        DAO_Message::WORKER_ID => (!empty($worker->id) ? $worker->id : 0)
	    );
		$message_id = DAO_Message::create($fields);
	    
		// Content
	    DAO_MessageContent::update($message_id, $content);

	    // Headers
	    if (!empty($email) && !empty($email->headers)) {
			foreach($email->headers->getList() as $hdr => $v) {
				if(null != ($hdr_val = $email->headers->getEncoded($hdr))) {
					if(!empty($hdr_val))
		    			DAO_MessageHeader::update($message_id, $ticket_id, $hdr, $hdr_val);
				}
			}
	    }
		
		// Set recipients to requesters
		// [TODO] Allow seperated addresses (parseRfcAddress)
		if(null != ($reqAddressInst = CerberusApplication::hashLookupAddress($to, true))) {
			$reqAddressId = $reqAddressInst->id;
			DAO_Ticket::createRequester($reqAddressId, $ticket_id);
		}
		
		// add files to ticket
		// [TODO] redundant with parser (like most of the rest of this function)
		if (is_array($files) && !empty($files)) {
			$attachment_path = APP_PATH . '/storage/attachments/';
		
			reset($files);
			foreach ($files['tmp_name'] as $idx => $file) {
				if(empty($file) || empty($files['name'][$idx]) || !file_exists($file))
					continue;
					
				$fields = array(
					DAO_Attachment::MESSAGE_ID => $message_id,
					DAO_Attachment::DISPLAY_NAME => $files['name'][$idx],
					DAO_Attachment::MIME_TYPE => $files['type'][$idx],
					DAO_Attachment::FILE_SIZE => filesize($file)
				);
				$file_id = DAO_Attachment::create($fields);
				
	            $attachment_bucket = sprintf("%03d/",
	                rand(1,100)
	            );
	            $attachment_file = $file_id;
	            
	            if(!file_exists($attachment_path.$attachment_bucket)) {
	                mkdir($attachment_path.$attachment_bucket, 0775, true);
	            }

	            if(!is_writeable($attachment_path.$attachment_bucket)) {
	            	echo "Can't write to " . $attachment_path.$attachment_bucket . "<BR>";
	            }
	            
	            copy($file, $attachment_path.$attachment_bucket.$attachment_file);
	            @unlink($file);
			    
			    DAO_Attachment::update($file_id, array(
			        DAO_Attachment::FILEPATH => $attachment_bucket.$attachment_file
			    ));
			}
		}
		
		// if email sending failed, add an error note to the message
		if ($mail_succeeded === false) {
			$fields = array(
				DAO_MessageNote::MESSAGE_ID => $message_id,
				DAO_MessageNote::CREATED => time(),
				DAO_MessageNote::WORKER_ID => 0,
				DAO_MessageNote::CONTENT => 'Exception thrown while sending email: ' . $e->getMessage(),
				DAO_MessageNote::TYPE => Model_MessageNote::TYPE_ERROR,
			);
			DAO_MessageNote::create($fields);
		}
		
		$ticket = DAO_Ticket::getTicket($ticket_id);
		
		$visit = CerberusApplication::getVisit(); /* @var CerberusVisit $visit */
		$visit->set('compose.last_ticket', $ticket->mask);
		
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
	
	function showViewCopyAction() {
        @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(__FILE__) . '/templates/';
		$tpl->assign('path', $tpl_path);
        
        $view = C4_AbstractViewLoader::getView('',$view_id);
        
		$active_worker = CerberusApplication::getActiveWorker();

		$workspaces = DAO_WorkerWorkspaceList::getWorkspaces($active_worker->id);
		$tpl->assign('workspaces', $workspaces);
        
        $tpl->assign('view_id', $view_id);
		$tpl->assign('view', $view);

        $tpl->display($tpl_path.'tickets/rpc/ticket_view_copy.tpl.php');
	}
	
	function doViewCopyAction() {
	    @$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView('', $view_id);
	    
		@$list_title = DevblocksPlatform::importGPC($_POST['list_title'],'string', '');
		@$workspace = DevblocksPlatform::importGPC($_POST['workspace'],'string', '');
		@$new_workspace = DevblocksPlatform::importGPC($_POST['new_workspace'],'string', '');
		
		if(empty($workspace) && empty($new_workspace))
			$new_workspace = "New Workspace";
			
		if(empty($list_title))
			$list_title = "New List";
		
		$workspace_name = (!empty($new_workspace) ? $new_workspace : $workspace);
			
		// [TODO] Fix crossing layers (app->DAO)
		$active_worker = CerberusApplication::getActiveWorker();
		
		// List
		$list_view = new Model_WorkerWorkspaceListView();
		$list_view->title = $list_title;
		$list_view->num_rows = $view->renderLimit;
		$list_view->columns = $view->view_columns;
		$list_view->params = $view->params;
		
		$fields = array(
			DAO_WorkerWorkspaceList::WORKER_ID => $active_worker->id,
			DAO_WorkerWorkspaceList::WORKSPACE => $workspace_name,
			DAO_WorkerWorkspaceList::LIST_VIEW => serialize($list_view)
		);
		$list_id = DAO_WorkerWorkspaceList::create($fields);
		
		// [TODO] Switch response to proper workspace
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','lists')));
	}
	
	function showViewAutoAssignAction() {
        @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(__FILE__) . '/templates/';
		$tpl->assign('path', $tpl_path);
        
        $view = C4_AbstractViewLoader::getView('',$view_id);
        
        // Not assigned
        $view->params[SearchFields_Ticket::TICKET_NEXT_WORKER_ID] = 
        	new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_NEXT_WORKER_ID,'=',0);
        
        // Not closed
        $view->params[SearchFields_Ticket::TICKET_CLOSED] = 
        	new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',0);
        
        // Not already replied to
        $view->params[SearchFields_Ticket::TICKET_LAST_ACTION_CODE] = 
        	new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_LAST_ACTION_CODE,'in',array('O','R'));
        
        $tpl->assign('view_id', $view_id);
        
        $results = $view->getData();
        $tpl->assign('num_assignable', $results[1]);
        
        $tpl->display($tpl_path.'tickets/rpc/ticket_view_assign.tpl.php');
	}
	
	function doViewAutoAssignAction() {
	    @$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
	    @$how_many = DevblocksPlatform::importGPC($_POST['how_many'],'integer',5);
	    
	    $active_worker = CerberusApplication::getActiveWorker();
	    
	    $search_view = C4_AbstractViewLoader::getView('', CerberusApplication::VIEW_SEARCH);
	    $view = C4_AbstractViewLoader::getView('',$view_id);
	    
	    if(empty($search_view)) {
	    	$search_view = C4_TicketView::createSearchView();
	    }

        // Not assigned
        $view->params[SearchFields_Ticket::TICKET_NEXT_WORKER_ID] = 
        	new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_NEXT_WORKER_ID,'=',0);
        
        // Not closed
        $view->params[SearchFields_Ticket::TICKET_CLOSED] = 
        	new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',0);
        
        // Not already replied to
        $view->params[SearchFields_Ticket::TICKET_LAST_ACTION_CODE] = 
        	new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_LAST_ACTION_CODE,'in',array('O','R'));
	    
       	// Sort by Service Level priority
		$view->renderLimit = $how_many;
		$view->renderSortBy = SearchFields_Ticket::TICKET_SLA_PRIORITY;	
		$view->renderSortAsc = 0;
        
		// Grab $how_many rows from the top
		list($assign_tickets, $null) = $view->getData();
		
		if(is_array($assign_tickets)) {
			DAO_Ticket::updateTicket(array_keys($assign_tickets),array(
				DAO_Ticket::NEXT_WORKER_ID => $active_worker->id
			));
			
			// Set our new search parameters and persist
			$search_view->renderPage = 0;
			$search_view->params = array(
				SearchFields_Ticket::TICKET_CLOSED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',0),
				SearchFields_Ticket::TICKET_NEXT_WORKER_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_NEXT_WORKER_ID,'=',$active_worker->id),
				SearchFields_Ticket::TICKET_LAST_ACTION_CODE => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_LAST_ACTION_CODE,'in',array('O','R')),
			);
			$search_view->renderSortBy = SearchFields_Ticket::TICKET_SLA_PRIORITY;
			$search_view->renderSortAsc = 0;
			
			list($my_tickets,$null) = $search_view->getData();
			C4_AbstractViewLoader::setView($search_view->id, $search_view);
			
			// View our current tickets, displaying the first one
			if(null != ($first_ticket = reset($my_tickets))) {
				DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display','browse',$first_ticket[SearchFields_Ticket::TICKET_MASK],'search')));
			}
		}
	}
	
	function showViewAutoAssistAction() {
        @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
        @$mode = DevblocksPlatform::importGPC($_REQUEST['mode'],'string','subjects');
        @$mode_param = DevblocksPlatform::importGPC($_REQUEST['mode_param'],'string','');

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(__FILE__) . '/templates/';
		$tpl->assign('path', $tpl_path);
        
        $visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
        $active_dashboard_id = $visit->get(CerberusVisit::KEY_DASHBOARD_ID, 0);

        $view = C4_AbstractViewLoader::getView('',$view_id);
        
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
		$view = C4_AbstractViewLoader::getView('',$view_id);

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
            
            $view->doBulkUpdate($doType, $doTypeParam, $doData, $doActions, array(), $always);
	    }

	    // Reset the paging since we may have reduced our list size
	    $view->renderPage = 0;
	    C4_AbstractViewLoader::setView($view_id,$view);
	    	    
        //DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets')));
        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets')));
	}

	function viewMoveTicketsAction() {
	    @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
	    @$ticket_ids = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'array');
	    @$move_to = DevblocksPlatform::importGPC($_REQUEST['move_to'],'string');
	    
	    if(empty($ticket_ids)) {
		    $view = C4_AbstractViewLoader::getView('',$view_id);
		    $view->render();
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
        
        C4_TicketView::setLastAction($view_id,$last_action);
	    
	    // Make our changes to the entire list of tickets
	    if(!empty($ticket_ids) && !empty($team_id)) {
	        DAO_Ticket::updateTicket($ticket_ids, $fields);
	    }
	    
	    // Increment the counter of uses for this move (by # of tickets affected)
	    // [TODO] Move this into a WorkerPrefs API class
	    $active_worker = CerberusApplication::getActiveWorker(); /* @var $$active_worker CerberusWorker */
	    if($active_worker->id) {
	        $move_counts_str = DAO_WorkerPref::get($active_worker->id,''.DAO_WorkerPref::SETTING_MOVE_COUNTS,serialize(array()));
	        if(is_string($move_counts_str)) {
	            $move_counts = unserialize($move_counts_str);
	            @$move_counts[$move_to] = intval($move_counts[$move_to]) + count($ticket_ids);
	            arsort($move_counts);
	            DAO_WorkerPref::set($active_worker->id,''.DAO_WorkerPref::SETTING_MOVE_COUNTS,serialize($move_counts));
	        }
	    }
	    
	    $view = C4_AbstractViewLoader::getView('',$view_id);
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
	    
	    $view = C4_AbstractViewLoader::getView('',$view_id);
	    $view->render();
	    return;
	}

	function viewSurrenderTicketsAction() {
	    @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
	    @$ticket_ids = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'array');
	    
	    $active_worker = CerberusApplication::getActiveWorker();
	    
        $fields = array(
            DAO_Ticket::NEXT_WORKER_ID => 0,
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
	    
	    $view = C4_AbstractViewLoader::getView('',$view_id);
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
	    
	    $view = C4_AbstractViewLoader::getView('',$view_id);
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
	    
	    $view = C4_AbstractViewLoader::getView('',$view_id);
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
	    
	    $view = C4_AbstractViewLoader::getView('',$view_id);
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
	    
	    $view = C4_AbstractViewLoader::getView('',$view_id);
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
	    
	    $view = C4_AbstractViewLoader::getView('',$view_id);
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
	    
	    $view = C4_AbstractViewLoader::getView('',$view_id);
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
	    
	    $view = C4_AbstractViewLoader::getView('',$view_id);
	    $view->render();
	    return;
	}
	
	function viewUndoAction() {
	    @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
	    @$clear = DevblocksPlatform::importGPC($_REQUEST['clear'],'integer',0);
	    $last_action = C4_TicketView::getLastAction($view_id);
	    
	    if($clear || empty($last_action)) {
            C4_TicketView::setLastAction($view_id,null);
		    $view = C4_AbstractViewLoader::getView('',$view_id);
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
	    
	    $view = C4_AbstractViewLoader::getView('',$view_id);
	    $view->render();
	    return;
	}

//	function changeDashboardAction() {
//		@$dashboard_id = DevblocksPlatform::importGPC($_POST['dashboard_id'], 'string', '');
//		$team_id = 0;
//
//		// Cache the current team id
//		if(0 == strcmp('t',substr($dashboard_id,0,1))) {
//		    $team_id = intval(substr($dashboard_id,1));
//        }
//		
//		$visit = DevblocksPlatform::getSessionService()->getVisit();
//		$visit->set(CerberusVisit::KEY_DASHBOARD_ID, $dashboard_id);
//		$visit->set(CerberusVisit::KEY_WORKSPACE_GROUP_ID, $team_id);
//        
////		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','workspaces')));
//		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','workspaces')));
//	}
	
	function changeMyWorkspaceAction() {
		$workspace = DevblocksPlatform::importGPC($_POST['workspace'], 'string', '');
		
		$visit = DevblocksPlatform::getSessionService()->getVisit();
		$visit->set(CerberusVisit::KEY_MY_WORKSPACE, $workspace);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','lists')));
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
	        $ticket_ids = DevblocksPlatform::parseCsvString($ids);
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
		
		// Teams
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		// [TODO] Cache these
		// Categories
		$team_categories = DAO_Bucket::getTeams();
		$tpl->assign('team_categories', $team_categories);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
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
	    @$always = DevblocksPlatform::importGPC($_REQUEST['always'],'integer',0);
	    
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView('',$view_id);

		@$closed = DevblocksPlatform::importGPC($_POST['closed'],'string','');
		@$spam = DevblocksPlatform::importGPC($_POST['spam'],'string','');
		@$team = DevblocksPlatform::importGPC($_POST['team'],'string','');
		@$next_worker = DevblocksPlatform::importGPC($_POST['next_worker'],'string','');

        $subjects = DevblocksPlatform::parseCrlfString($subjects);
        $senders = DevblocksPlatform::parseCrlfString($senders);
		
		$do = array();
		
		if(!is_null($closed))
			$do['closed'] = $closed;
		if(!is_null($spam))
			$do['spam'] = $spam;
		if(!is_null($team))
			$do['team'] = $team;
		if(!is_null($next_worker))
			$do['next_worker'] = $next_worker;
		
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
		
	    // [TODO] Reimplement 'always'
		$view->doBulkUpdate($filter, '', $data, $do, $ticket_ids, $always);
		
		$view->render();
		return;
	}

	// ajax
	function showViewRssAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);
		
		$view = C4_AbstractViewLoader::getView('',$view_id);
		
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

		$view = C4_AbstractViewLoader::getView('',$view_id);
		
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
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('preferences','rss')));
	}
	
	function searchviewAction() {
		$visit = CerberusApplication::getVisit();
	    
	    $response = DevblocksPlatform::getHttpRequest();
	    $path = $response->path;
	    array_shift($path); // tickets
	    array_shift($path); // searchview
	    $id = array_shift($path);

	    $view = C4_AbstractViewLoader::getView('',$id);

		if(!empty($view->params)) {
		    $params = array();
		    
		    // Index by field name for search system
		    if(is_array($view->params))
		    foreach($view->params as $criteria) { /* @var $criteria DevblocksSearchCriteria */
                $params[$criteria->field] = $criteria;
		    }
		}
		
		if(null == ($search_view = C4_AbstractViewLoader::getView('',CerberusApplication::VIEW_SEARCH))) {
			$search_view = C4_TicketView::createSearchView();
		}
		$search_view->params = $params;
		$search_view->renderPage = 0;
		C4_AbstractViewLoader::setView($search_view->id,$search_view);

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

		if(file_exists(APP_PATH . '/install/')) {
			$tpl->assign('install_dir_warning', true);
		}
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		$command = array_shift($stack);
		
		switch(array_shift($stack)) {
		    default:
			case 'general':
				$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/general/index.tpl.php');
				break;
				
			case 'sla':
				$slas = DAO_Sla::getAll();
				$tpl->assign('slas', $slas);
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/sla/index.tpl.php');
				break;
				
			case 'fnr':
				$topics = DAO_FnrTopic::getWhere();
				$tpl->assign('topics', $topics);
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/fnr/index.tpl.php');
				break;
				
			case 'mail':
				@$test_connection = array_shift($stack);
				
				$settings = CerberusSettings::getInstance();
				$mail_service = DevblocksPlatform::getMailService();
				
				$routing = DAO_Mail::getMailboxRouting();
				$tpl->assign('routing', $routing);

				$teams = DAO_Group::getAll();
				$tpl->assign('teams', $teams);

				$pop3_accounts = DAO_Mail::getPop3Accounts();
				$tpl->assign('pop3_accounts', $pop3_accounts);
				
				$smtp_host = $settings->get(CerberusSettings::SMTP_HOST,'');
				$smtp_port = $settings->get(CerberusSettings::SMTP_PORT,25);
				$smtp_auth_enabled = $settings->get(CerberusSettings::SMTP_AUTH_ENABLED,false);
				if ($smtp_auth_enabled) {
					$smtp_auth_user = $settings->get(CerberusSettings::SMTP_AUTH_USER,'');
					$smtp_auth_pass = $settings->get(CerberusSettings::SMTP_AUTH_PASS,''); 
				} else {
					$smtp_auth_user = '';
					$smtp_auth_pass = ''; 
				}
				$smtp_enc = $settings->get(CerberusSettings::SMTP_ENCRYPTION_TYPE,'None');
				
				// [JAS]: Test the provided SMTP settings and give form feedback
				if(!empty($test_connection) && !empty($smtp_host)) {
					$mailer = null;
					try {
						$mailer = $mail_service->getMailer($smtp_host, $smtp_auth_user, $smtp_auth_pass, $smtp_port, $smtp_enc); // [TODO] port
						$mailer->connect();
						$mailer->disconnect();
						
						if(!empty($smtp_host))
							$settings->set(CerberusSettings::SMTP_HOST, $smtp_host);
						if(!empty($smtp_auth_user) || $smtp_auth_user == '')
							$settings->set(CerberusSettings::SMTP_AUTH_USER, $smtp_auth_user);
						if(!empty($smtp_auth_pass) || $smtp_auth_user == '')
							$settings->set(CerberusSettings::SMTP_AUTH_PASS, $smtp_auth_pass);
						if(!empty($smtp_enc))
							$settings->set(CerberusSettings::SMTP_ENCRYPTION_TYPE, $smtp_enc);
						
						$tpl->assign('smtp_test', true);
						
					} catch(Exception $e) {
						$tpl->assign('smtp_test', false);
						$tpl->assign('smtp_test_output', 'SMTP Connection Failed: '.$e->getMessage());
					}
				}
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/mail/index.tpl.php');				
				break;
				
			case 'workflow':
				/*
				 * [IMPORTANT -- Yes, this is simply a line in the sand.]
				 * You're welcome to modify the code to meet your needs, but please respect 
				 * our licensing.  Buy a legitimate copy to help support the project!
				 * http://www.cerberusweb.com/
				 */
				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);

				$teams = DAO_Group::getAll();
				$tpl->assign('teams', $teams);
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/workflow/index.tpl.php');				
				break;
				
			case 'extensions':
				@$plugin_id = array_shift($stack);
				
				if(!empty($plugin_id) && null != ($plugin = DevblocksPlatform::getPlugin($plugin_id))) {
					$inst = $plugin->createInstance();
					$tpl->assign('plugin', $plugin);
					$tpl->assign('inst', $inst);
					$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/extensions/configure_plugin.tpl.php');
					
				} else {
					// Auto synchronize when viewing Config->Extensions
			        DevblocksPlatform::readPlugins();
					
					$plugins = DevblocksPlatform::getPluginRegistry();
					unset($plugins['cerberusweb.core']);
					$tpl->assign('plugins', $plugins);
					
					$points = DevblocksPlatform::getExtensionPoints();
					$tpl->assign('points', $points);
					
					$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/extensions/index.tpl.php');
				}				
				break;

			case 'fields':
				$types = Model_TicketField::getTypes();
				$tpl->assign('types', $types);
				
				$fields = DAO_TicketField::getWhere(sprintf("%s = %d",
					DAO_TicketField::GROUP_ID,
					0
				));
				$tpl->assign('ticket_fields', $fields);
				
				$groups = DAO_Group::getAll();
				$tpl->assign('groups', $groups);
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/fields/index.tpl.php');				
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
		@$delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);

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
		@$delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
		
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
	function addCustomFieldAction() {
		$worker = CerberusApplication::getActiveWorker();
		if(!$worker || !$worker->is_superuser) {
			echo "Access denied.";
			return;
		}
		
		if(DEMO_MODE) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','fields')));
			return;
		}
		
		@$name = DevblocksPlatform::importGPC($_POST['name'],'string','');
		@$type = DevblocksPlatform::importGPC($_POST['type'],'string','');
		@$options = DevblocksPlatform::importGPC($_POST['options'],'string','');
		@$group_id = DevblocksPlatform::importGPC($_POST['group_id'],'integer',0);
		
		$fields = array(
			DAO_TicketField::NAME => $name,
			DAO_TicketField::TYPE => $type,
			DAO_TicketField::GROUP_ID => $group_id,
			DAO_TicketField::OPTIONS => $options,
		);
		
		$id = DAO_TicketField::create($fields);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','fields')));
	}
	
	// Post
	function saveCustomFieldsAction() {
		$worker = CerberusApplication::getActiveWorker();
		if(!$worker || !$worker->is_superuser) {
			echo "Access denied.";
			return;
		}
		
		if(DEMO_MODE) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','fields')));
			return;
		}
		
		@$ids = DevblocksPlatform::importGPC($_POST['ids'],'array',array());
		@$names = DevblocksPlatform::importGPC($_POST['names'],'array',array());
		@$orders = DevblocksPlatform::importGPC($_POST['orders'],'array',array());
		@$options = DevblocksPlatform::importGPC($_POST['options'],'array',array());
		@$allow_delete = DevblocksPlatform::importGPC($_POST['allow_delete'],'integer',0);
		@$deletes = DevblocksPlatform::importGPC($_POST['deletes'],'array',array());
		
		if(!empty($ids))
		foreach($ids as $idx => $id) {
			@$name = $names[$idx];
			@$order = intval($orders[$idx]);
			@$option = $options[$idx];
			@$delete = (false !== array_search($id,$deletes) ? 1 : 0);
			
			if($allow_delete && $delete) {
				DAO_TicketField::delete($id);
				
			} else {
				$fields = array(
					DAO_TicketField::NAME => $name, 
					DAO_TicketField::POS => $order, 
					DAO_TicketField::OPTIONS => !is_null($option) ? $option : '', 
				);
				DAO_TicketField::update($id, $fields);
			}
		}		
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','fields')));
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
		$users = 0;
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
				if(0==strcmp($matches[1],"Users"))
					$users = $matches[2];
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
		
		// Save for reuse in form in case we need to redraw on error
		$settings = CerberusSettings::getInstance();
		$settings->set('company', trim($company));
		$_SESSION['lk_users'] = intval($users);
		
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
		$license['users'] = intval($users);
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
		@$delete = DevblocksPlatform::importGPC($_POST['do_delete'],'integer');

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
				$workers = DAO_Worker::getAll();
				$license = CerberusLicense::getInstance();
				if ((!empty($license) && !empty($license['key'])) || count($workers) < 3) {
					// Creating new worker.  If password is empty, email it to them
				    if(empty($password)) {
				    	$settings = CerberusSettings::getInstance();
						$replyFrom = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM);
						$replyPersonal = $settings->get(CerberusSettings::DEFAULT_REPLY_PERSONAL, '');
						$url = DevblocksPlatform::getUrlService();
				    	
						$password = CerberusApplication::generatePassword(8);
				    	
						try {
					        $mail_service = DevblocksPlatform::getMailService();
					        $mailer = $mail_service->getMailer();
					        $mail = $mail_service->createMessage();
					        
					        $sendTo = new Swift_Address($email, $first_name . $last_name);
					        $sendFrom = new Swift_Address($replyFrom, $replyPersonal);
					        
					        $mail->setSubject('Your new helpdesk login information!');
					        $mail->generateId();
					        $mail->headers->set('X-Mailer','Cerberus Helpdesk (Build '.APP_BUILD.')');
					        
						    $body = sprintf("Your new helpdesk login information is below:\r\n".
								"\r\n".
						        "URL: %s\r\n".
						        "Login: %s\r\n".
						        "Password: %s\r\n".
						        "\r\n".
						        "You should change your password from Preferences after logging in for the first time.\r\n".
						        "\r\n",
							        $url->write('',true),
							        $email,
							        $password
						    );
					        
						    $mail->attach(new Swift_Message_Part($body));
	
							if(!$mailer->send($mail, $sendTo, $sendFrom)) {
								throw new Exception('Password notification email failed to send.');
							}
						} catch (Exception $e) {
							// [TODO] need to report to the admin when the password email doesn't send.  The try->catch
							// will keep it from killing php, but the password will be empty and the user will never get an email.
						}
				    }
					
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
			
			if(null == DAO_AddressToWorker::getByAddress($email)) {
				DAO_AddressToWorker::assign($email, $id);
				DAO_AddressToWorker::update($email, array(
					DAO_AddressToWorker::IS_CONFIRMED => 1
				));
			}
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
		
		if(!empty($id)) {
			@$members = DAO_Group::getTeamMembers($id);
			$tpl->assign('members', $members);
		}
		
		$workers = DAO_Worker::getAll();
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
//		@$leader_id = DevblocksPlatform::importGPC($_POST['leader_id'],'integer',0);
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
//			DAO_Group::setTeamMember($id, $leader_id, true);
		}
		
		@$worker_ids = DevblocksPlatform::importGPC($_POST['worker_ids'],'array',array());
		@$worker_levels = DevblocksPlatform::importGPC($_POST['worker_levels'],'array',array());
		
	    @$members = DAO_Group::getTeamMembers($id);
	    
	    if(is_array($worker_ids) && !empty($worker_ids))
	    foreach($worker_ids as $idx => $worker_id) {
	    	@$level = $worker_levels[$idx];
	    	if(isset($members[$worker_id]) && empty($level)) {
	    		DAO_Group::unsetTeamMember($id, $worker_id);
	    	} elseif(!empty($level)) { // member|manager
				 DAO_Group::setTeamMember($id, $worker_id, (1==$level)?false:true);
	    	}
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
	    @$authorized_ips_str = DevblocksPlatform::importGPC($_POST['authorized_ips'],'string','');
	    
	    $settings = CerberusSettings::getInstance();
	    $settings->set(CerberusSettings::HELPDESK_TITLE, $title);
	    $settings->set(CerberusSettings::HELPDESK_LOGO_URL, $logo); // [TODO] Enforce some kind of max resolution?
	    $settings->set(CerberusSettings::AUTHORIZED_IPS, $authorized_ips_str);
	    
	    DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','general')));
	}
	
	function saveIncomingMailSettingsAction() {
		$worker = CerberusApplication::getActiveWorker();
		if(!$worker || !$worker->is_superuser) {
			echo "Access denied.";
			return;
		}
		
		if(DEMO_MODE) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','mail')));
			return;
		}
		
	    @$attachments_enabled = DevblocksPlatform::importGPC($_POST['attachments_enabled'],'integer',0);
	    @$attachments_max_size = DevblocksPlatform::importGPC($_POST['attachments_max_size'],'integer',10);
	    @$parser_autoreq = DevblocksPlatform::importGPC($_POST['parser_autoreq'],'integer',0);
	    @$parser_autoreq_exclude = DevblocksPlatform::importGPC($_POST['parser_autoreq_exclude'],'string','');
		
	    $settings = CerberusSettings::getInstance();
	    $settings->set(CerberusSettings::ATTACHMENTS_ENABLED, $attachments_enabled);
	    $settings->set(CerberusSettings::ATTACHMENTS_MAX_SIZE, $attachments_max_size);
	    $settings->set(CerberusSettings::PARSER_AUTO_REQ, $parser_autoreq);
	    $settings->set(CerberusSettings::PARSER_AUTO_REQ_EXCLUDE, $parser_autoreq_exclude);
		
	    // Pop3 Accounts

		@$ar_ids = DevblocksPlatform::importGPC($_POST['account_id'],'array');
		@$ar_enabled = DevblocksPlatform::importGPC($_POST['pop3_enabled'],'array');
		@$ar_nickname = DevblocksPlatform::importGPC($_POST['nickname'],'array');
		@$ar_protocol = DevblocksPlatform::importGPC($_POST['protocol'],'array');
		@$ar_host = DevblocksPlatform::importGPC($_POST['host'],'array');
		@$ar_username = DevblocksPlatform::importGPC($_POST['username'],'array');
		@$ar_password = DevblocksPlatform::importGPC($_POST['password'],'array');
		@$ar_port = DevblocksPlatform::importGPC($_POST['port'],'array');
		@$ar_delete = DevblocksPlatform::importGPC($_POST['delete'],'array');

		if(!is_array($ar_ids))
		    return;
		    
		foreach($ar_ids as $idx => $id) {
		    $nickname = $ar_nickname[$idx];
		    $protocol = $ar_protocol[$idx];
		    $host = $ar_host[$idx];
		    $username = $ar_username[$idx];
		    $password = $ar_password[$idx];
		    $port = $ar_port[$idx];
		    
			if(empty($nickname)) $nickname = "No Nickname";
			
			// Defaults
			if(empty($port)) {
			    switch($protocol) {
			        case 'pop3':
			            $port = 110; 
			            break;
			        case 'pop3-ssl':
			            $port = 995;
			            break;
			        case 'imap':
			            $port = 143;
			            break;
			        case 'imap-ssl':
			            $port = 993;
			            break;
			    }
			}
			
			if(!empty($id) && is_numeric(array_search($id, $ar_delete))) {
				DAO_Mail::deletePop3Account($id);
				
			} elseif(!empty($id)) {
			    $enabled = (is_array($ar_enabled) && is_numeric(array_search($id, $ar_enabled))) ? 1 : 0;
			    
			    // [JAS]: [TODO] convert to field constants
				$fields = array(
				    'enabled' => $enabled,
					'nickname' => $nickname,
					'protocol' => $protocol,
					'host' => $host,
					'username' => $username,
					'password' => $password,
					'port' => $port
				);
				DAO_Mail::updatePop3Account($id, $fields);
				
			} else {
	            if(!empty($host) && !empty($username)) {
				    // [JAS]: [TODO] convert to field constants
	                $fields = array(
					    'enabled' => 1,
						'nickname' => $nickname,
						'protocol' => $protocol,
						'host' => $host,
						'username' => $username,
						'password' => $password,
						'port' => $port
					);
				    $id = DAO_Mail::createPop3Account($fields);
	            }
			}
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','mail')));
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
	    @$default_signature_pos = DevblocksPlatform::importGPC($_POST['default_signature_pos'],'integer',0);
	    @$smtp_host = DevblocksPlatform::importGPC($_REQUEST['smtp_host'],'string','localhost');
	    @$smtp_port = DevblocksPlatform::importGPC($_REQUEST['smtp_port'],'integer',25);
	    @$smtp_auth_enabled = DevblocksPlatform::importGPC($_REQUEST['smtp_auth_enabled'],'integer', 0);
	    @$smtp_auth_user = DevblocksPlatform::importGPC($_REQUEST['smtp_auth_user'],'string');
	    @$smtp_auth_pass = DevblocksPlatform::importGPC($_REQUEST['smtp_auth_pass'],'string');
	    @$smtp_enc = DevblocksPlatform::importGPC($_REQUEST['smtp_enc'],'string','None');
	    
	    $settings = CerberusSettings::getInstance();
	    $settings->set(CerberusSettings::DEFAULT_REPLY_FROM, $default_reply_address);
	    $settings->set(CerberusSettings::DEFAULT_REPLY_PERSONAL, $default_reply_personal);
	    $settings->set(CerberusSettings::DEFAULT_SIGNATURE, $default_signature);
	    $settings->set(CerberusSettings::DEFAULT_SIGNATURE_POS, $default_signature_pos);
	    $settings->set(CerberusSettings::SMTP_HOST, $smtp_host);
	    $settings->set(CerberusSettings::SMTP_PORT, $smtp_port);
	    $settings->set(CerberusSettings::SMTP_AUTH_ENABLED, $smtp_auth_enabled);
	    $settings->set(CerberusSettings::SMTP_AUTH_USER, $smtp_auth_user);
	    $settings->set(CerberusSettings::SMTP_AUTH_PASS, $smtp_auth_pass);
	    $settings->set(CerberusSettings::SMTP_ENCRYPTION_TYPE, $smtp_enc);
	    
	    DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','mail','test')));
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
		@$default_team_id = DevblocksPlatform::importGPC($_POST['default_team_id'],'integer',0);
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
		if(DEMO_MODE) {
			return;
		}
		
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
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','extensions')));
	}
	
	// POST
	function saveConfigurePluginAction() {
		@$plugin_id = DevblocksPlatform::importGPC($_REQUEST['plugin_id'],'string');

		if(!empty($plugin_id) && null != ($plugin = DevblocksPlatform::getPlugin($plugin_id))) {
			$inst = $plugin->createInstance();
			$inst->saveConfiguration($plugin);
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','extensions')));
	}
	
	function saveSlaAction() {
		$worker = CerberusApplication::getActiveWorker();
		if(!$worker || !$worker->is_superuser) {
			echo "Access denied.";
			return;
		}
		
		if(DEMO_MODE) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','sla')));
			return;
		}
		
		// Edits
		@$sla_ids = DevblocksPlatform::importGPC($_POST['sla_ids'],'array',array());
		@$sla_names = DevblocksPlatform::importGPC($_POST['sla_names'],'array',array());
		@$sla_priorities = DevblocksPlatform::importGPC($_POST['sla_priorities'],'array',array());
		
		$orig_slas = DAO_Sla::getAll();
		
		if(is_array($sla_ids) && !empty($sla_ids))
		foreach($sla_ids as $idx => $sla_id) {
			@$sla_name = $sla_names[$idx];
			@$sla_order = intval($sla_priorities[$idx]);
			
			if(empty($sla_name))
				continue;
				
			// Between 1-100
			$sla_order = max(min($sla_order, 100),1);
			
			$fields = array(
				DAO_Sla::NAME => $sla_name,
				DAO_Sla::PRIORITY => $sla_order,
			);
			DAO_Sla::update($sla_id, $fields);
			
			// Update priority on any existing tickets with this SLA (if changed)
			if($orig_slas[$sla_id]->priority != $sla_order) {
				DAO_Ticket::updateWhere(
					array(
						DAO_Ticket::SLA_PRIORITY => $sla_order
					),
					sprintf("%s = %d",
						DAO_Ticket::SLA_ID,
						$sla_id
					)
				);
			}
		}
		
		// Deletes
		@$sla_deletes = DevblocksPlatform::importGPC($_POST['sla_deletes'],'array',array());
		
		if(is_array($sla_deletes) && !empty($sla_deletes))
			DAO_Sla::delete($sla_deletes);
		
		// Create
		@$add_sla = DevblocksPlatform::importGPC($_POST['add_sla'],'string','');
		
		if(!empty($add_sla)) {
			$sla_id = DAO_Sla::create(array(
				DAO_Sla::NAME => $add_sla,
				DAO_Sla::PRIORITY => 1
			));
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','sla')));
	}
	
}

class ChTasksPage extends CerberusPageExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
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

		@array_shift($stack); // tasks
		
		switch(array_shift($stack)) {
			default:
			case 'overview':
				$source_renderers = DevblocksPlatform::getExtensions('cerberusweb.task.source', true);
				$tpl->assign('source_renderers', $source_renderers);
				
				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				
				$unassigned_totals = DAO_Task::getUnassignedSourceTotals();
				$tpl->assign('unassigned_totals', $unassigned_totals);
				
				$assigned_totals = DAO_Task::getAssignedSourceTotals();
				$tpl->assign('assigned_totals', $assigned_totals);
				
				if(null == ($tasks_view = C4_AbstractViewLoader::getView('', C4_TaskView::DEFAULT_ID))) {
					$tasks_view = new C4_TaskView();
					C4_AbstractViewLoader::setView(C4_TaskView::DEFAULT_ID, $tasks_view);
				} 

				if(!empty($stack)) {
					switch(array_shift($stack)) {
						case 'all':
							$tasks_view->doResetCriteria();							
							$tasks_view->name = C4_TaskView::DEFAULT_TITLE;
							break;
							
						case 'available':
							$params = array();
							$params[SearchFields_Task::IS_COMPLETED] = new DevblocksSearchCriteria(SearchFields_Task::IS_COMPLETED,'=',0);
							$params[SearchFields_Task::WORKER_ID] = new DevblocksSearchCriteria(SearchFields_Task::WORKER_ID,'=',0);
							$tasks_view->params = $params;
							$tasks_view->name = 'Available Tasks';
							break;
							
						case 'source':
							@$source_id = array_shift($stack);
							if(!empty($source_id)) {
								@$inst = DevblocksPlatform::getExtension($source_id)->createInstance();
								$title = 'Tasks: ' . (!empty($inst) ? $inst->getSourceName() : $source_id);
							} else {
								$source_id = '';
								$title= 'Tasks: Generic';
							}
							
							$params = array();
							$params[SearchFields_Task::IS_COMPLETED] = new DevblocksSearchCriteria(SearchFields_Task::IS_COMPLETED,'=',0);
							$params[SearchFields_Task::SOURCE_EXTENSION] = new DevblocksSearchCriteria(SearchFields_Task::SOURCE_EXTENSION,'=',$source_id);
							$tasks_view->params = $params;
							$tasks_view->name = $title;
							break;
							
						case 'today':
							$params = array();
							$params[SearchFields_Task::IS_COMPLETED] = new DevblocksSearchCriteria(SearchFields_Task::IS_COMPLETED,'=',0);
							$params[SearchFields_Task::DUE_DATE] = new DevblocksSearchCriteria(SearchFields_Task::DUE_DATE,DevblocksSearchCriteria::OPER_BETWEEN,array(1,strtotime('tomorrow')));
							$tasks_view->params = $params;
							$tasks_view->name = 'All Open Tasks Due Today';
							break;
							
						case 'worker':
							@$worker_id = array_shift($stack);
							if(!isset($workers[$worker_id]))
								break;
							
							$params = array();
							$params[SearchFields_Task::IS_COMPLETED] = new DevblocksSearchCriteria(SearchFields_Task::IS_COMPLETED,'=',0);
							$params[SearchFields_Task::WORKER_ID] = new DevblocksSearchCriteria(SearchFields_Task::WORKER_ID,'=',$worker_id);
							$tasks_view->params = $params;
							$tasks_view->name = 'For ' . $workers[$worker_id]->getName();
							break;
					}
					
					C4_AbstractViewLoader::setView($tasks_view->id, $tasks_view);
				}
				
				$tpl->assign('tasks_view', $tasks_view);
				break;
		}
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/tasks/index.tpl.php');
	}
	
	function showTaskPeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer','');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$link_namespace = DevblocksPlatform::importGPC($_REQUEST['link_namespace'],'string',''); // opt
		@$link_object_id = DevblocksPlatform::importGPC($_REQUEST['link_object_id'],'integer',0); // opt
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		if(!empty($id)) {
			$task = DAO_Task::get($id);
			$tpl->assign('task', $task);
			
			if(!empty($task->source_extension) && !empty($task->source_id)) {
				if(null != ($mft = DevblocksPlatform::getExtension($task->source_extension))) {
					$source_info = $mft->createInstance();
					@$tpl->assign('source_info', $source_info->getSourceInfo($task->source_id));
				}
			}
		}

		// Only used on create
		if(!empty($link_namespace) && !empty($link_object_id)) {
			$tpl->assign('link_namespace', $link_namespace);
			$tpl->assign('link_object_id', $link_object_id);
		}
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$tpl->assign('id', $id);
		$tpl->assign('view_id', $view_id);
		$tpl->display('file:' . dirname(__FILE__) . '/templates/tasks/rpc/peek.tpl.php');
	}
	
	function saveTaskPeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer','');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$link_namespace = DevblocksPlatform::importGPC($_REQUEST['link_namespace'],'string','');
		@$link_object_id = DevblocksPlatform::importGPC($_REQUEST['link_object_id'],'integer',0);

		$fields = array();

		// Title
		@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string','');
		if(!empty($title))
			$fields[DAO_Task::TITLE] = $title;

		// Completed
		@$completed = DevblocksPlatform::importGPC($_REQUEST['completed'],'integer',0);
		
		$fields[DAO_Task::IS_COMPLETED] = intval($completed);
		
		if($completed)
			$fields[DAO_Task::COMPLETED_DATE] = time();
			
		// Due Date
		@$due_date = DevblocksPlatform::importGPC($_REQUEST['due_date'],'string','');
		@$fields[DAO_Task::DUE_DATE] = empty($due_date) ? 0 : intval(strtotime($due_date));		

		// Priority
		@$priority = DevblocksPlatform::importGPC($_REQUEST['priority'],'integer',4);
		@$fields[DAO_Task::PRIORITY] = intval($priority);
		
		// Worker
		@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'integer',0);
		@$fields[DAO_Task::WORKER_ID] = intval($worker_id);
		
		// Content
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');
		@$fields[DAO_Task::CONTENT] = $content;

		// Link to object (optional)
		if(!empty($link_namespace) && !empty($link_object_id)) {
			@$fields[DAO_Task::SOURCE_EXTENSION] = $link_namespace;
			@$fields[DAO_Task::SOURCE_ID] = $link_object_id;
		}
		
		// Save
		if(!empty($id)) {
			DAO_Task::update($id, $fields);
		} else {
			$id = DAO_Task::create($fields);
		}
		
		if(!empty($view_id) && null != ($view = C4_AbstractViewLoader::getView('', $view_id))) {
			$view->render();
		}
		
		exit;
	}
	
	function viewCompleteAction() {
		@$ids = DevblocksPlatform::importGPC($_POST['row_id'],'array',array());
		
		if(empty($ids))
			return;
		
		$fields = array(
			DAO_Task::IS_COMPLETED => 1,
			DAO_Task::COMPLETED_DATE => time()
		);
		DAO_Task::update($ids, $fields);
	}
	
	function viewDeleteAction() {
		@$ids = DevblocksPlatform::importGPC($_POST['row_id'],'array',array());
		
		if(empty($ids))
			return;
		
		DAO_Task::delete($ids);
	}
	
	function viewPostponeAction() {
		@$ids = DevblocksPlatform::importGPC($_POST['row_id'],'array',array());
		
		if(empty($ids))
			return;
		
		$tasks = DAO_Task::getWhere(sprintf("%s IN (%s)",
			DAO_Task::ID,
			implode(',', $ids)
		));
		
		foreach($tasks as $task) {
			$time = ($task->due_date) ? $task->due_date : time();
			$fields = array(
				DAO_Task::DUE_DATE => strtotime('+24 hours',$time)
			);
			DAO_Task::update($task->id, $fields);
		}
	}
	
	function viewDueTodayAction() {
		@$ids = DevblocksPlatform::importGPC($_POST['row_id'],'array',array());

		if(empty($ids))
			return;
		
		$fields = array(
			DAO_Task::DUE_DATE => intval(strtotime("tomorrow"))
		);
		DAO_Task::update($ids, $fields);
	}
	
	function viewTakeAction() {
		@$ids = DevblocksPlatform::importGPC($_POST['row_id'],'array',array());

		if(empty($ids))
			return;
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Only unassigned
		$where = sprintf("%s IN (%s) AND %s = %d",
			DAO_Task::ID,
			implode(',', $ids),
			DAO_Task::WORKER_ID,
			0
		);
		
		$fields = array(
			DAO_Task::WORKER_ID => intval($active_worker->id)
		);
		DAO_Task::updateWhere($fields, $where);
	}
	
	function viewSurrenderAction() {
		@$ids = DevblocksPlatform::importGPC($_POST['row_id'],'array',array());

		if(empty($ids))
			return;
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		// Only unassigned
		$where = sprintf("%s IN (%s) AND %s = %d",
			DAO_Task::ID,
			implode(',', $ids),
			DAO_Task::WORKER_ID,
			$active_worker->id
		);
		
		$fields = array(
			DAO_Task::WORKER_ID => 0
		);
		DAO_Task::updateWhere($fields, $where);
	}
	
	function viewPriorityHighAction() {
		@$ids = DevblocksPlatform::importGPC($_POST['row_id'],'array',array());

		if(empty($ids))
			return;
		
		$fields = array(
			DAO_Task::PRIORITY => 1 // [TODO] These should be Model_Task constants
		);
		DAO_Task::update($ids, $fields);
	}
	
	function viewPriorityNormalAction() {
		@$ids = DevblocksPlatform::importGPC($_POST['row_id'],'array',array());

		if(empty($ids))
			return;
		
		$fields = array(
			DAO_Task::PRIORITY => 2
		);
		DAO_Task::update($ids, $fields);
	}
	
	function viewPriorityLowAction() {
		@$ids = DevblocksPlatform::importGPC($_POST['row_id'],'array',array());

		if(empty($ids))
			return;
		
		$fields = array(
			DAO_Task::PRIORITY => 3
		);
		DAO_Task::update($ids, $fields);
	}
	
	function viewPriorityNoneAction() {
		@$ids = DevblocksPlatform::importGPC($_POST['row_id'],'array',array());
		
		if(empty($ids))
			return;
		
		$fields = array(
			DAO_Task::PRIORITY => 4
		);
		DAO_Task::update($ids, $fields);
	}
};

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
		
		$tab_manifests = DevblocksPlatform::getExtensions('cerberusweb.contacts.tab', false);
		$tpl->assign('tab_manifests', $tab_manifests);
		
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
				$tpl->assign('view_fields', C4_AddressView::getFields());
				$tpl->assign('view_searchable_fields', C4_AddressView::getSearchFields());
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
					$tpl->assign('view_fields', C4_ContactOrgView::getFields());
					$tpl->assign('view_searchable_fields', C4_ContactOrgView::getSearchFields());
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
		
		@$replace_passwords = DevblocksPlatform::importGPC($_REQUEST['replace_passwords'],'integer',0);
		
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
			
			// Overrides
			$contact_password = '';
			
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
							case 'password':
								$key = null;
								$contact_password = $val;
								break;
						}
					}

					if(!empty($key)) {
						$fields[$key] = $val;
					
						// [JAS]: Are we looking for matches in a certain field?
						if($sync_column==$key && !empty($val)) {
							$sync_field = $key;
							$sync_val = $val;
						}
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
							$id = key($addys);
							DAO_Address::update($id, $fields);
						}

						// Overrides
						if(!empty($contact_password) && !empty($id)) {
							if($replace_passwords) { // always replace
								DAO_AddressAuth::update(
									$id,
									array(DAO_AddressAuth::PASS => $contact_password) 
								);
							} else { // only replace if null
								if(null == ($auth = DAO_AddressAuth::get($id))) {
									DAO_AddressAuth::update(
										$id,
										array(DAO_AddressAuth::PASS => $contact_password) 
									);
								}
							}
						}
					}
				}
			}
		}
		
		@unlink($csv_file); // nuke the imported file
		
		$visit->set('import.last.csv',null);
		$visit->set('import.last.type',null);
	}
	
	// Ajax
	function showTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_ContactsTab) {
			$inst->showTab();
		}
	}
	
	function showTabDetailsAction() {
		@$org = DevblocksPlatform::importGPC($_REQUEST['org']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$contact = DAO_ContactOrg::get($org);
		$tpl->assign('contact', $contact);

		$slas = DAO_Sla::getAll();
		$tpl->assign('slas', $slas);
		
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
		
		C4_AbstractViewLoader::setView($view->id, $view);
		
		$tpl->assign('contacts_page', 'orgs');
		$tpl->assign('search_columns', SearchFields_Address::getFields());
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/contacts/orgs/tabs/people.tpl.php');
		exit;
	}
	
	function showTabTasksAction() {
		@$org = DevblocksPlatform::importGPC($_REQUEST['org']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$contact = DAO_ContactOrg::get($org);
		$tpl->assign('contact', $contact);
		
		$view = C4_AbstractViewLoader::getView('C4_TaskView', 'org_tasks');
		$view->id = 'org_tasks';
		$view->name = 'Organization Tasks';
		$view->view_columns = array(
			SearchFields_Task::SOURCE_EXTENSION,
			SearchFields_Task::PRIORITY,
			SearchFields_Task::DUE_DATE,
			SearchFields_Task::WORKER_ID,
			SearchFields_Task::COMPLETED_DATE,
		);
		$view->params = array(
			new DevblocksSearchCriteria(SearchFields_Task::SOURCE_EXTENSION,'=','cerberusweb.tasks.org'),
			new DevblocksSearchCriteria(SearchFields_Task::SOURCE_ID,'=',$org),
		);
		$tpl->assign('view', $view);
		
		C4_AbstractViewLoader::setView($view->id, $view);
		
		$tpl->assign('contacts_page', 'orgs');
		$tpl->assign('search_columns', SearchFields_Address::getFields());
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/contacts/orgs/tabs/tasks.tpl.php');
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

		$tickets_view = C4_AbstractViewLoader::getView('','contact_history');
		
		if(null == $tickets_view) {
			$tickets_view = new C4_TicketView();
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
			);
			$tickets_view->renderLimit = 10;
			$tickets_view->renderPage = 0;
			$tickets_view->renderSortBy = SearchFields_Ticket::TICKET_CREATED_DATE;
			$tickets_view->renderSortAsc = false;
		}

		@$tickets_view->name = "Most recent tickets from " . htmlentities($contact->name);
		$tickets_view->params = array(
			SearchFields_Ticket::TICKET_FIRST_CONTACT_ORG_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_CONTACT_ORG_ID,DevblocksSearchCriteria::OPER_EQ,$contact->id)
		);
		$tpl->assign('contact_history', $tickets_view);
		
		C4_AbstractViewLoader::setView($tickets_view->id,$tickets_view);
		
		$workers = DAO_Worker::getAll();
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
		@$sla_id = DevblocksPlatform::importGPC($_REQUEST['sla_id'],'integer',0);
		
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
				DAO_ContactOrg::WEBSITE => $website,
				DAO_ContactOrg::SLA_ID => $sla_id
		);
		
		if($id==0) {
			$id = DAO_ContactOrg::create($fields);
		}
		else {
			DAO_ContactOrg::update($id, $fields);	
		}
		
		// SLA Updates
		DAO_Sla::cascadeOrgSla($id, $sla_id);
				
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('contacts','orgs',$id)));
	}
	
	function showAddressPeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer','');
		@$address_id = DevblocksPlatform::importGPC($_REQUEST['address_id'],'integer',0);
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$org_id = DevblocksPlatform::importGPC($_REQUEST['org_id'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$slas = DAO_Sla::getAll();
		$tpl->assign('slas', $slas);
		
		if(!empty($address_id)) {
			$email = '';
			if(null != ($addy = DAO_Address::get($address_id))) {
				@$email = $addy->email;
			}
		}
		$tpl->assign('email', $email);
		
		if(!empty($email)) {
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
			$tpl->assign('address', $address);
			$id = $address[SearchFields_Address::ID];
			
			list($open_tickets, $open_count) = DAO_Ticket::search(
				array(),
				array(
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',0),
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE_ID,'=',$address[SearchFields_Address::ID]),
				),
				1
			);
			$tpl->assign('open_count', $open_count);
			
			list($closed_tickets, $closed_count) = DAO_Ticket::search(
				array(),
				array(
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',1),
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE_ID,'=',$address[SearchFields_Address::ID]),
				),
				1
			);
			$tpl->assign('closed_count', $closed_count);
		}
		
		if (!empty($org_id)) {
			$org = DAO_ContactOrg::get($org_id);
			$tpl->assign('org_name',$org->name);
			$tpl->assign('org_id',$org->id);
		}
		
		$tpl->assign('id', $id);
		$tpl->assign('view_id', $view_id);
		$tpl->display('file:' . dirname(__FILE__) . '/templates/contacts/addresses/address_peek.tpl.php');
	}
	
	function showAddressTicketsAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$closed = DevblocksPlatform::importGPC($_REQUEST['closed'],'integer',0);
		
		if(null == ($address = DAO_Address::get($id)))
			return;
		
		if(null == ($search_view = C4_AbstractViewLoader::getView('', CerberusApplication::VIEW_SEARCH))) {
			$search_view = C4_TicketView::createSearchView();
		}
		
		$search_view->params = array(
			SearchFields_Ticket::TICKET_CLOSED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',$closed),
			SearchFields_Ticket::TICKET_FIRST_WROTE => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE,'=',$address->email),
		);
		$search_view->renderPage = 0;
		
		C4_AbstractViewLoader::setView(CerberusApplication::VIEW_SEARCH, $search_view);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','search')));
	}
	
	function showAddressBatchPanelAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->assign('view_id', $view_id);

	    if(!empty($ids)) {
	        $address_ids = DevblocksPlatform::parseCsvString($ids);
	        $tpl->assign('address_ids', $address_ids);
//	        if(empty($ticket_ids)) break;
//	        $tickets = DAO_Ticket::getTickets($ticket_ids);
	    }
		
	    // SLAs
		$slas = DAO_Sla::getAll();
		$tpl->assign('slas', $slas);
	    
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/contacts/addresses/address_bulk.tpl.php');
	}
	
	function showOrgPeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer','');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$slas = DAO_Sla::getAll();
		$tpl->assign('slas', $slas);
		
		$contact = DAO_ContactOrg::get($id);
		
		$tpl->assign('contact', $contact);
		$tpl->assign('view_id', $view_id);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/contacts/orgs/org_peek.tpl.php');
	}
	
	function saveContactAction() {
		$db = DevblocksPlatform::getDatabaseService();
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		@$email = trim(DevblocksPlatform::importGPC($_REQUEST['email'],'string',''));
		@$first_name = trim(DevblocksPlatform::importGPC($_REQUEST['first_name'],'string',''));
		@$last_name = trim(DevblocksPlatform::importGPC($_REQUEST['last_name'],'string',''));
		@$phone = trim(DevblocksPlatform::importGPC($_REQUEST['phone'],'string',''));
		@$contact_org = trim(DevblocksPlatform::importGPC($_REQUEST['contact_org'],'string',''));
		@$sla_id = DevblocksPlatform::importGPC($_REQUEST['sla_id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string', '');
		
		$contact_org_id = 0;
		
		if(!empty($contact_org)) {
			$contact_org_id = DAO_ContactOrg::lookup($contact_org, true);
			$contact_org = DAO_ContactOrg::get($contact_org_id);
			
			// Assign addy the same SLA as contact_org if not set
			if(empty($sla_id) && !empty($contact_org->sla_id))
				$sla_id = $contact_org->sla_id;
		}
		
		$fields = array(
			DAO_Address::FIRST_NAME => $first_name,
			DAO_Address::LAST_NAME => $last_name,
			DAO_Address::CONTACT_ORG_ID => $contact_org_id,
			DAO_Address::PHONE => $phone,
			DAO_Address::SLA_ID => $sla_id
		);
		
		if($id==0) {
			$fields = $fields + array(DAO_Address::EMAIL => $email);
			$id = DAO_Address::create($fields);
		}
		else {
			DAO_Address::update($id, $fields);	
		}

		/*
		 * Notify anything that wants to know when Address Peek saves.
		 */
	    $eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'address.peek.saved',
                array(
                    'address_id' => $id,
                    'changed_fields' => $fields,
                )
            )
	    );
		
		// Update SLA+Priority on any open tickets from this address
		DAO_Sla::cascadeAddressSla($id, $sla_id);
		
		if(!empty($view_id)) {
			$view = C4_AbstractViewLoader::getView('', $view_id);
			$view->render();
		}
	}
	
	function saveOrgPeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$org_name = DevblocksPlatform::importGPC($_REQUEST['org_name'],'string','');
		@$account_num = DevblocksPlatform::importGPC($_REQUEST['account_num'],'string','');
		@$street = DevblocksPlatform::importGPC($_REQUEST['street'],'string','');
		@$city = DevblocksPlatform::importGPC($_REQUEST['city'],'string','');
		@$province = DevblocksPlatform::importGPC($_REQUEST['province'],'string','');
		@$postal = DevblocksPlatform::importGPC($_REQUEST['postal'],'string','');
		@$country = DevblocksPlatform::importGPC($_REQUEST['country'],'string','');
		@$phone = DevblocksPlatform::importGPC($_REQUEST['phone'],'string','');
		@$fax = DevblocksPlatform::importGPC($_REQUEST['fax'],'string','');
		@$website = DevblocksPlatform::importGPC($_REQUEST['website'],'string','');
		@$sla_id = DevblocksPlatform::importGPC($_REQUEST['sla_id'],'integer',0);
		@$delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);

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
					DAO_ContactOrg::WEBSITE => $website,
					DAO_ContactOrg::SLA_ID => $sla_id
					);
	
			if($id==0) {
				$id = DAO_ContactOrg::create($fields);
			}
			else {
				DAO_ContactOrg::update($id, $fields);	
			}
			
			// SLA Updates
			DAO_Sla::cascadeOrgSla($id, $sla_id);
		}		
		
		$view = C4_AbstractViewLoader::getView('', $view_id);
		$view->render();		
	}
	
	function doSetOrgSlaAction() {
		@$row_ids = DevblocksPlatform::importGPC($_REQUEST['row_id'],'array',array());
		@$sla_id = DevblocksPlatform::importGPC($_REQUEST['sla_id'],'integer',null);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['id'],'string','');
		
		if(!is_null($sla_id) && !empty($row_ids)) {
			DAO_ContactOrg::update($row_ids,array(
				DAO_ContactOrg::SLA_ID => $sla_id
			));
			
			foreach($row_ids as $id) {
				DAO_Sla::cascadeOrgSla($id, $sla_id);
			}
		}
		
		$view = C4_AbstractViewLoader::getView('', $view_id);
		$view->render();		
	}
	
	function doAddressBatchUpdateAction() {
	    @$address_id_str = DevblocksPlatform::importGPC($_REQUEST['address_ids'],'string');

	    @$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
	    
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView('',$view_id);

		@$sla = DevblocksPlatform::importGPC($_POST['sla'],'string','');

		$address_ids = DevblocksPlatform::parseCsvString($address_id_str);
		
		$do = array();
		
		if('' != $sla)
			$do['sla'] = $sla;
		
		$view->doBulkUpdate($filter, $do, $address_ids);
		
		$view->render();
		return;
	}
	
	function doAddressQuickSearchAction() {
        @$type = DevblocksPlatform::importGPC($_POST['type'],'string'); 
        @$query = DevblocksPlatform::importGPC($_POST['query'],'string');

		$view = C4_AbstractViewLoader::getView('C4_AddressView', C4_AddressView::DEFAULT_ID);

        $params = array();
        
        if($query && false===strpos($query,'*'))
            $query = '*' . $query . '*';
        
        switch($type) {
            case "email":
                $params[SearchFields_Address::EMAIL] = new DevblocksSearchCriteria(SearchFields_Address::EMAIL,DevblocksSearchCriteria::OPER_LIKE,strtolower($query));               
                break;
            case "org":
                $params[SearchFields_Address::ORG_NAME] = new DevblocksSearchCriteria(SearchFields_Address::ORG_NAME,DevblocksSearchCriteria::OPER_LIKE,strtolower($query));               
                break;
        }
        
        $view->params = $params;
        $view->renderPage = 0;
        $view->renderSortBy = null;
        
        C4_AbstractViewLoader::setView(C4_AddressView::DEFAULT_ID,$view);
        
        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('contacts','addresses')));
	}
	
	function doOrgQuickSearchAction() {
        @$type = DevblocksPlatform::importGPC($_POST['type'],'string'); 
        @$query = DevblocksPlatform::importGPC($_POST['query'],'string');

		$view = C4_AbstractViewLoader::getView('C4_ContactOrgView', C4_ContactOrgView::DEFAULT_ID);

        $params = array();
        
        if($query && false===strpos($query,'*'))
            $query = '*' . $query . '*';
        
        switch($type) {
            case "name":
                $params[SearchFields_ContactOrg::NAME] = new DevblocksSearchCriteria(SearchFields_ContactOrg::NAME,DevblocksSearchCriteria::OPER_LIKE,strtolower($query));               
                break;
            case "phone":
                $params[SearchFields_ContactOrg::PHONE] = new DevblocksSearchCriteria(SearchFields_ContactOrg::PHONE,DevblocksSearchCriteria::OPER_LIKE,strtolower($query));               
                break;
        }
        
        $view->params = $params;
        $view->renderPage = 0;
        $view->renderSortBy = null;
        
        C4_AbstractViewLoader::setView(C4_ContactOrgView::DEFAULT_ID,$view);
        
        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('contacts','orgs')));
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
		
		foreach($orgs AS $val){
			echo $val[SearchFields_ContactOrg::NAME] . "\t";
			echo $val[SearchFields_ContactOrg::ID] . "\n";
		}
		exit();
	}
	
	function getCountryAutoCompletionsAction() {
		@$starts_with = DevblocksPlatform::importGPC($_REQUEST['query'],'string','');
		
		$db = DevblocksPlatform::getDatabaseService();
		
		// [TODO] Possibly internalize this exposed query.
		$sql = sprintf("SELECT DISTINCT country FROM contact_org WHERE country LIKE '%s%%' ORDER BY country",
			$starts_with
		);
		$rs = $db->Execute($sql);
		
		while(!$rs->EOF) {
			echo $rs->fields['country'];
			echo "\n";
			$rs->MoveNext();
		}
		exit;
	}
};

class ChFilesController extends DevblocksControllerExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
		
		$router = DevblocksPlatform::getRoutingService();
		$router->addRoute('files','core.controller.files');
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

class ChGroupsPage extends CerberusPageExtension  {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	// [TODO] Refactor to isAuthorized
	function isVisible() {
		$worker = CerberusApplication::getActiveWorker();
		
		if(empty($worker)) {
			return false;
		} else {
			return true;
		}
	}
	
	function getActivity() {
	    return new Model_Activity('');
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$active_worker = CerberusApplication::getActiveWorker();
		
//		if(!$worker || !$worker->is_superuser) {
//			echo "Access denied.";
//			return;
//		}

		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		$command = array_shift($stack); // groups
		
		switch(array_shift($stack)) {
		    default:
		    	$groups = DAO_Group::getAll();
		    	$tpl->assign('groups', $groups);
		    	
				$tpl->display('file:' . dirname(__FILE__) . '/templates/groups/index.tpl.php');
				break;
				
		    case 'config':
				$team_id = array_shift($stack); // id
				
				if(!$active_worker->isTeamManager($team_id)) {
					return;
				} else {
					$teams = DAO_Group::getAll();
					$team =& $teams[$team_id];					
				}
				
				if(empty($team))
				    break;

		        $tpl->cache_lifetime = "0";
			    $tpl->assign('team', $team);
			    
	            switch(array_shift($stack)) {
	                default:
	                case 'general':
						$team_categories = DAO_Bucket::getByTeam($team_id);
						$tpl->assign('categories', $team_categories);
					    
						$group_settings = DAO_GroupSettings::getSettings($team_id);
						
						@$tpl->assign('group_settings', $group_settings);
						@$tpl->assign('group_spam_threshold', $group_settings[DAO_GroupSettings::SETTING_SPAM_THRESHOLD]);
						@$tpl->assign('group_spam_action', $group_settings[DAO_GroupSettings::SETTING_SPAM_ACTION]);
						@$tpl->assign('group_spam_action_param', $group_settings[DAO_GroupSettings::SETTING_SPAM_ACTION_PARAM]);
						
	                    $tpl->display('file:' . dirname(__FILE__) . '/templates/groups/manage/index.tpl.php');
	                    break;
	                    
	                case 'members':
						$members = DAO_Group::getTeamMembers($team_id);
					    $tpl->assign('members', $members);
					    
						$workers = DAO_Worker::getAll();
					    $tpl->assign('workers', $workers);
					    
//					    $available_workers = array();
//					    foreach($workers as $worker) {
//					    	if(!isset($members[$worker->id]))
//					    		$available_workers[$worker->id] = $worker;
//					    }
//					    $tpl->assign('available_workers', $available_workers);
					    
	                    $tpl->display('file:' . dirname(__FILE__) . '/templates/groups/manage/members.tpl.php');
	                    break;
	                    
	                case 'fields':
	                	$group_fields = DAO_TicketField::getWhere(sprintf("%s = %d",
	                		DAO_TicketField::GROUP_ID,
	                		$team_id
	                	));
	                	$tpl->assign('group_fields', $group_fields);
	                    
						$types = Model_TicketField::getTypes();
						$tpl->assign('types', $types);
	                	
						$tpl->display('file:' . dirname(__FILE__) . '/templates/groups/manage/fields.tpl.php');
	                    break;
	                    
	                case 'buckets':
						$team_categories = DAO_Bucket::getByTeam($team_id);
						$tpl->assign('categories', $team_categories);
	                    
						$tpl->display('file:' . dirname(__FILE__) . '/templates/groups/manage/buckets.tpl.php');
	                    break;
	                    
	                case 'routing':
	                    $team_rules = DAO_TeamRoutingRule::getByTeamId($team_id);
	                    $tpl->assign('team_rules', $team_rules);

	                    $category_name_hash = DAO_Bucket::getCategoryNameHash();
	                    $tpl->assign('category_name_hash', $category_name_hash);

						$teams = DAO_Group::getAll();
						$tpl->assign('teams', $teams);
						
						$team_categories = DAO_Bucket::getTeams();
						$tpl->assign('team_categories', $team_categories);
	                    
	                    $workers = DAO_Worker::getAll();
	                    $tpl->assign('workers', $workers);
	                    
						// Status
						$statuses = CerberusTicketStatus::getOptions();
						$tpl->assign('statuses', $statuses);
				
						// Spam Training
						$training = CerberusTicketSpamTraining::getOptions();
						$tpl->assign('training', $training);
	                    
						$tpl->display('file:' . dirname(__FILE__) . '/templates/groups/manage/routing.tpl.php');
	                    break;
	            }
	            
		    	break;
			    
		} // end switch
		
	}
	
	// Post
	function saveTeamGeneralAction() {
	    @$team_id = DevblocksPlatform::importGPC($_REQUEST['team_id'],'integer');

	    @$active_worker = CerberusApplication::getActiveWorker();
	    if(!$active_worker->isTeamManager($team_id))
	    	return;
	    
	    //========== GENERAL
	    @$signature = DevblocksPlatform::importGPC($_REQUEST['signature'],'string','');
	    @$auto_reply_enabled = DevblocksPlatform::importGPC($_REQUEST['auto_reply_enabled'],'integer',0);
	    @$auto_reply = DevblocksPlatform::importGPC($_REQUEST['auto_reply'],'string','');
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
	    DAO_GroupSettings::set($team_id, DAO_GroupSettings::SETTING_AUTO_REPLY_ENABLED, $auto_reply_enabled);
	    DAO_GroupSettings::set($team_id, DAO_GroupSettings::SETTING_AUTO_REPLY, $auto_reply);
	       
        //DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','team',$team_id,'general')));
        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('groups','config',$team_id,'general')));
	}
	
	function changeTeamMembersAction() {
		@$team_id = DevblocksPlatform::importGPC($_REQUEST['team_id'],'integer');
		@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_ids'],'array',array());
		@$worker_levels = DevblocksPlatform::importGPC($_REQUEST['worker_levels'],'array',array());
		
	    @$active_worker = CerberusApplication::getActiveWorker();
	    @$members = DAO_Group::getTeamMembers($team_id);
	    
	    if(!$active_worker->isTeamManager($team_id))
	    	return;
	    
	    if(is_array($worker_ids) && !empty($worker_ids))
	    foreach($worker_ids as $idx => $worker_id) {
	    	@$level = $worker_levels[$idx];
	    	if(isset($members[$worker_id]) && empty($level)) {
	    		DAO_Group::unsetTeamMember($team_id, $worker_id);
	    	} elseif(!empty($level)) { // member|manager
				 DAO_Group::setTeamMember($team_id, $worker_id, (1==$level)?false:true);
	    	}
	    }
	    
	    DevblocksPlatform::redirect(new DevblocksHttpResponse(array('groups','config',$team_id,'members')));
	}
	
	function saveTeamBucketsAction() {
	    @$team_id = DevblocksPlatform::importGPC($_REQUEST['team_id'],'integer');
	    
	    @$active_worker = CerberusApplication::getActiveWorker();
	    if(!$active_worker->isTeamManager($team_id))
	    	return;
	    
	    //========== BUCKETS   
	    @$ids = DevblocksPlatform::importGPC($_REQUEST['ids'],'array');
	    @$add_str = DevblocksPlatform::importGPC($_REQUEST['add'],'string');
	    @$names = DevblocksPlatform::importGPC($_REQUEST['names'],'array');
	    @$response_hrs = DevblocksPlatform::importGPC($_REQUEST['response_hrs'],'array');
	    @$deletes = DevblocksPlatform::importGPC($_REQUEST['deletes'],'array');
	    
	    // Updates
	    $cats = DAO_Bucket::getList($ids);
	    foreach($ids as $idx => $id) {
	        @$cat = $cats[$id];
	        if(is_object($cat)) {
	        	$fields = array(
	        		DAO_Bucket::NAME => $names[$idx],
	        		DAO_Bucket::RESPONSE_HRS => intval($response_hrs[$idx]),
	        	);
	            DAO_Bucket::update($id, $fields);
	        }
	    }
	    
	    // Adds: Sort and insert team categories
	    $categories = DevblocksPlatform::parseCrlfString($add_str);

	    if(is_array($categories))
	    foreach($categories as $category) {
	        // [TODO] Dupe checking
	        $cat_id = DAO_Bucket::create($category, $team_id);
	    }
	    
	    if(!empty($deletes))
	        DAO_Bucket::delete(array_values($deletes));
	        
        //DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','team',$team_id,'buckets')));	        
        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('groups','config',$team_id,'buckets')));
	}
	
	function saveTeamRoutingAction() {
	    @$team_id = DevblocksPlatform::importGPC($_REQUEST['team_id'],'integer');
	    @$deletes = DevblocksPlatform::importGPC($_REQUEST['deletes'],'array');
	    
	    @$active_worker = CerberusApplication::getActiveWorker();
	    if(!$active_worker->isTeamManager($team_id))
	    	return;
	    
	    if(!empty($team_id) && !empty($deletes)) {
	        DAO_TeamRoutingRule::delete($deletes);
	    }
	    
        //DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','team',$team_id,'routing')));   
        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('groups','config',$team_id,'routing')));
   	}
   	
   	function addTeamRoutingAction() {
   		@$team_id = DevblocksPlatform::importGPC($_REQUEST['team_id'],'integer');
   		
   		@$field = DevblocksPlatform::importGPC($_REQUEST['field'],'string');
   		@$value = DevblocksPlatform::importGPC($_REQUEST['value'],'string');

   		@$move = DevblocksPlatform::importGPC($_REQUEST['move'],'string');
   		@$status = DevblocksPlatform::importGPC($_REQUEST['status'],'string');
   		@$spam = DevblocksPlatform::importGPC($_REQUEST['spam'],'string');
   		@$assign = DevblocksPlatform::importGPC($_REQUEST['assign'],'string');

   		$fields = array(
   			DAO_TeamRoutingRule::TEAM_ID => $team_id,
   			DAO_TeamRoutingRule::HEADER => $field,
   			DAO_TeamRoutingRule::PATTERN => $value,
   			DAO_TeamRoutingRule::POS => 0
   		);

   		if(!empty($move)) {
			$fields[DAO_TeamRoutingRule::DO_MOVE] = $move;   			
   		}
   		if(0 != strlen($status)) {
			$fields[DAO_TeamRoutingRule::DO_STATUS] = intval($status);   			
   		}
   		if(0 != strlen($spam)) {
			$fields[DAO_TeamRoutingRule::DO_SPAM] = intval($spam);   			
   		}
   		if(0 != strlen($assign)) {
			$fields[DAO_TeamRoutingRule::DO_ASSIGN] = intval($assign);
   		}
   		
   		$routing_id = DAO_TeamRoutingRule::create($fields);
   		
   		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('groups','config',$team_id,'routing')));
   	}
   	
	// Post
	function addCustomFieldAction() {
		@$name = DevblocksPlatform::importGPC($_POST['name'],'string','');
		@$type = DevblocksPlatform::importGPC($_POST['type'],'string','');
		@$group_id = DevblocksPlatform::importGPC($_POST['team_id'],'integer');
		@$options = DevblocksPlatform::importGPC($_POST['options'],'string','');
		
		$fields = array(
			DAO_TicketField::NAME => $name,
			DAO_TicketField::TYPE => $type,
			DAO_TicketField::GROUP_ID => $group_id,
			DAO_TicketField::OPTIONS => $options,
		);
		
		$id = DAO_TicketField::create($fields);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('groups','config',$group_id,'fields')));
	}
	
	// Post
	function saveCustomFieldsAction() {
		@$group_id = DevblocksPlatform::importGPC($_POST['team_id'],'integer');
		
		@$ids = DevblocksPlatform::importGPC($_POST['ids'],'array',array());
		@$names = DevblocksPlatform::importGPC($_POST['names'],'array',array());
		@$orders = DevblocksPlatform::importGPC($_POST['orders'],'array',array());
		@$options = DevblocksPlatform::importGPC($_POST['options'],'array',array());
		@$allow_delete = DevblocksPlatform::importGPC($_POST['allow_delete'],'integer',0);
		@$deletes = DevblocksPlatform::importGPC($_POST['deletes'],'array',array());
		
		if(!empty($ids))
		foreach($ids as $idx => $id) {
			@$name = $names[$idx];
			@$order = intval($orders[$idx]);
			@$option = $options[$idx];
			@$delete = (false !== array_search($id,$deletes) ? 1 : 0);
			
			if($allow_delete && $delete) {
				DAO_TicketField::delete($id);
				
			} else {
				$fields = array(
					DAO_TicketField::NAME => $name, 
					DAO_TicketField::POS => $order, 
					DAO_TicketField::OPTIONS => !is_null($option) ? $option : '',
				);
				DAO_TicketField::update($id, $fields);
			}
		}		
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('groups','config',$group_id,'fields')));
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
	    $authorized_ips = DevblocksPlatform::parseCrlfString($authorized_ips_str);
	    
	    $authorized_ip_defaults = DevblocksPlatform::parseCsvString(AUTHORIZED_IPS_DEFAULTS);
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
		"<BODY>"; // onload=\"setTimeout(\\\"window.location.replace('".$url->write('c=cron')."')\\\",30);\"

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
			array(),
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
			$workers = DAO_Worker::getAll();

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

	    $stack = $request->path;
	    array_shift($stack); // update

	    $cache = DevblocksPlatform::getCacheService(); /* @var $cache Zend_Cache_Core */
		$settings = CerberusSettings::getInstance();
	    
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
				
			    $authorized_ips_str = $settings->get(CerberusSettings::AUTHORIZED_IPS);
			    $authorized_ips = DevblocksPlatform::parseCrlfString($authorized_ips_str);
			    
		   	    $authorized_ip_defaults = DevblocksPlatform::parseCsvString(AUTHORIZED_IPS_DEFAULTS);
			    $authorized_ips = array_merge($authorized_ips, $authorized_ip_defaults);
			    
			    // Is this IP authorized?
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
				
			    // If authorized, lock and attempt update
				if(!file_exists($file) || @filectime($file)+600 < time()) { // 10 min lock
					touch($file);

				    //echo "Running plugin patches...<br>";
				    if(DevblocksPlatform::runPluginPatches()) {
						@unlink($file);

						// [JAS]: Clear all caches
						$cache->clean();
						
				    	DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login')));
				    } else {
						@unlink($file);
				    	echo "Failure!"; // [TODO] Needs elaboration
				    } 
				    break;
				}
				else {
					echo "Another administrator is currently running update.  Please wait...";
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
		C4_AbstractViewLoader::setView($id, $view);
		
		$view->render();
	}
	
	function viewPageAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$page = DevblocksPlatform::importGPC(DevblocksPlatform::importGPC($_REQUEST['page']));
		
		$view = C4_AbstractViewLoader::getView('', $id);
		$view->doPage($page);
		C4_AbstractViewLoader::setView($id, $view);
		
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
		C4_AbstractViewLoader::setView($id, $view);
		
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
		C4_AbstractViewLoader::setView($id, $view);
		
		if(!empty($response_uri))
			DevblocksPlatform::redirect(new DevblocksHttpResponse(explode('/', $response_uri)));
	}
	
	function viewResetCriteriaAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$response_uri = DevblocksPlatform::importGPC($_REQUEST['response_uri']);
		
		$view = C4_AbstractViewLoader::getView('', $id);
		$view->doResetCriteria();
		C4_AbstractViewLoader::setView($id, $view);

		if(!empty($response_uri))
			DevblocksPlatform::redirect(new DevblocksHttpResponse(explode('/', $response_uri)));
	}
	
	// Ajax
	function viewCustomizeAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->assign('id', $id);

		$view = C4_AbstractViewLoader::getView('', $id);
		$tpl->assign('view', $view);

		$tpl->assign('optColumns', $view->getColumns());

		// [TODO] This should be specific to the current view
		$tpl->assign('view_fields', C4_TicketView::getFields());
		
		// [TODO] This should be specific to the current view
		$tpl->assign('view_searchable_fields', C4_TicketView::getSearchFields());
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/internal/views/customize_view.tpl.php');
	}
	
	// Ajax
	function viewAddFilterAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		
		@$field = DevblocksPlatform::importGPC($_REQUEST['field']);
		@$oper = DevblocksPlatform::importGPC($_REQUEST['oper']);
		@$value = DevblocksPlatform::importGPC($_REQUEST['value']);
		@$field_deletes = DevblocksPlatform::importGPC($_REQUEST['field_deletes'],'array',array());
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		$view = C4_AbstractViewLoader::getView('', $id);

		// [TODO] Nuke criteria
		if(is_array($field_deletes) && !empty($field_deletes)) {
			foreach($field_deletes as $field_delete) {
				$view->doRemoveCriteria($field_delete);
			}
		}
		
		if(!empty($field)) {
			$view->doSetCriteria($field, $oper, $value);
		}
		
		// [TODO] This should be specific to the current view
		$tpl->assign('view_fields', C4_TicketView::getFields());
		$tpl->assign('view_searchable_fields', C4_TicketView::getSearchFields());

		C4_AbstractViewLoader::setView($id, $view);
		$tpl->assign('view', $view);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/internal/views/customize_view_criteria.tpl.php');
	}
	
	// Post?
	function viewSaveCustomizeAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$columns = DevblocksPlatform::importGPC($_REQUEST['columns'],'array', array());
		@$num_rows = DevblocksPlatform::importGPC($_REQUEST['num_rows'],'integer',10);
		
		$view = C4_AbstractViewLoader::getView('', $id);
		$view->doCustomize($columns, $num_rows);

		$active_worker = CerberusApplication::getActiveWorker();
		
		// Conditional Persist
		if(substr($id,0,5)=="cust_") { // custom workspace
			$list_view_id = intval(substr($id,5));
			
			// Special custom view fields
			@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string', 'New List');
			
			$view->name = $title;

			// Persist Object
			$list_view = new Model_WorkerWorkspaceListView();
			$list_view->title = $title;
			$list_view->columns = $view->view_columns;
			$list_view->num_rows = $view->renderLimit;
			$list_view->params = $view->params;
			
			DAO_WorkerWorkspaceList::update($list_view_id, array(
				DAO_WorkerWorkspaceList::LIST_VIEW => serialize($list_view)
			));
			
		} elseif($id == CerberusApplication::VIEW_OVERVIEW_ALL) { // overview
			$overview_prefs = new C4_AbstractViewModel();
			$overview_prefs->view_columns = $view->view_columns;
			$overview_prefs->renderLimit = $view->renderLimit;
			$overview_prefs->renderSortBy = $view->renderSortBy;
			$overview_prefs->renderSortAsc = $view->renderSortAsc;

			DAO_WorkerPref::set($active_worker->id, DAO_WorkerPref::SETTING_OVERVIEW, serialize($overview_prefs));
			
		}
		
		C4_AbstractViewLoader::setView($id, $view);
		
		$view->render();
	}
	
	function viewDeleteAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		
		// [TODO] Security check that the current user owns the view
		
		if(substr($id,0,5) != "cust_")
			return;

		$workspace_view_id = intval(substr($id,5));
			
		DAO_WorkerWorkspaceList::delete($workspace_view_id);
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

		$visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;

		@array_shift($stack); // display
		
		@$id = array_shift($stack);
		
		$tab_manifests = DevblocksPlatform::getExtensions('cerberusweb.ticket.tab', false);
		$tpl->assign('tab_manifests', $tab_manifests);

		@$tab_selected = array_shift($stack);
		$tpl->assign('tab_selected', $tab_selected);
		
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

		// Does a series exist?
		if(null != ($series_info = $visit->get('ch_display_series', null))) {
			@$series = $series_info['series'];
			// Is this ID part of the series?  If not, invalidate
			if(!isset($series[$id])) {
				$visit->set('ch_display_series', null);
			} else {
				$series_stats = array(
					'title' => $series_info['title'],
					'total' => $series_info['total'],
					'count' => count($series)
				);
				reset($series);
				$cur = 1;
				while(current($series)) {
					$pos = key($series);
					if(intval($pos)==intval($id)) {
						$series_stats['cur'] = $cur;
						if(false !== prev($series)) {
							@$series_stats['prev'] = $series[key($series)][SearchFields_Ticket::TICKET_MASK];
							next($series); // skip to current
						} else {
							reset($series);
						}
						next($series); // next
						@$series_stats['next'] = $series[key($series)][SearchFields_Ticket::TICKET_MASK];
						break;
					}
					next($series);
					$cur++;
				}
				
				$tpl->assign('series_stats', $series_stats);
			}
		}
		
		$quick_search_type = $visit->get('quick_search_type');
		$tpl->assign('quick_search_type', $quick_search_type);
				
		// Comments [TODO] Eventually this can be cached on ticket.num_comments
		$comments_total = DAO_TicketComment::getCountByTicketId($id);
		$tpl->assign('comments_total', $comments_total);
		
		// Custom Field Values [TODO] Eventually this can be cached on ticket.num_custom_fields
		$field_values_total = DAO_TicketFieldValue::getValueCountByTicketId($id, $ticket->team_id);
		$tpl->assign('field_values_total', $field_values_total);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		
		$team_categories = DAO_Bucket::getTeams();
		$tpl->assign('team_categories', $team_categories);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/index.tpl.php');
	}
	
	// Ajax
	function showTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_TicketTab) {
			$inst->showTab();
		}
	}
	
	// Post
	function saveTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_TicketTab) {
			$inst->saveTab();
		}
	}
	
	/*
	 * [TODO] Proxy any func requests to be handled by the tab directly, 
	 * instead of forcing tabs to implement controllers.  This should check 
	 * for the *Action() functions just as a handleRequest would
	 */
	/*
	function handleTabRequestAction() {
	}
	*/
	
	function browseAction() {
		$visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;
		
		array_shift($stack); // display
		array_shift($stack); // browse
		
		@$id = array_shift($stack);
		
		// [JAS]: Mask
		if(!is_numeric($id)) {
			$id = DAO_Ticket::getTicketIdByMask($id);
		}
		$ticket = DAO_Ticket::getTicket($id);
	
		if(empty($ticket)) {
			echo "<H1>Invalid Ticket ID.</H1>";
			return;
		}
		
		// Display series support (inherited paging from Display)
		@$view_id = array_shift($stack);
		if(!empty($view_id)) {
			$view = C4_AbstractViewLoader::getView('',$view_id);
			
			$range = 100;
			$pos = $view->renderLimit * $view->renderPage;
			$page = floor($pos / $range);
			
			list($series, $series_count) = DAO_Ticket::search(
				$view->view_columns,
				$view->params,
				$range,
				$page,
				$view->renderSortBy,
				$view->renderSortAsc,
				false
			);
			$series_info = array(
				'title' => $view->name,
				'total' => count($series),
				'series' => $series
			);
			$visit->set('ch_display_series', $series_info);
		}
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$ticket->mask)));
	}

	function getMessageAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']); // message id
		@$hide = DevblocksPlatform::importGPC($_REQUEST['hide'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$message = DAO_Ticket::getMessage($id);
		$tpl->assign('message', $message);
		$tpl->assign('message_id', $message->id);
		
		$ticket = DAO_Ticket::getTicket($message->id);
		$tpl->assign('ticket', $ticket);
		
		if(empty($hide)) {
			$content = DAO_MessageContent::get($id);
			$tpl->assign('content', $content);
			
			$notes = DAO_MessageNote::getByTicketId($message->ticket_id);
			$message_notes = array();
			// Index notes by message id
			if(is_array($notes))
			foreach($notes as $note) {
				if(!isset($message_notes[$note->message_id]))
					$message_notes[$note->message_id] = array();
				$message_notes[$note->message_id][$note->id] = $note;
			}
			$tpl->assign('message_notes', $message_notes);
		}
		
		// [TODO] Workers?
		
		$tpl->assign('expanded', (empty($hide) ? true : false));
		
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
				
		$workers = DAO_Worker::getAll();
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
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$is_forward = DevblocksPlatform::importGPC($_REQUEST['forward'],'integer',0);

		$settings = CerberusSettings::getInstance();
		
		$settings = CerberusSettings::getInstance();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->assign('id',$id);
		$tpl->assign('is_forward',$is_forward);
		
		$message = DAO_Ticket::getMessage($id);
		$tpl->assign('message',$message);
		
		$ticket = DAO_Ticket::getTicket($message->ticket_id);
		$tpl->assign('ticket',$ticket);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		
		$team_categories = DAO_Bucket::getTeams();
		$tpl->assign('team_categories', $team_categories);

		@$ticket_team = $teams[$ticket->team_id];
		
		if(null != ($worker = CerberusApplication::getActiveWorker())) { /* @var $worker CerberusWorker */
			/* [JAS]:
			 * If the worker is replying to an unassigned ticket, assign it to them to warn
			 * other workers.  By default the 'next worker' followup propery will revert back 
			 * to 'anybody' when desired.
			 * 
			 * We're intentionally not telling the template about the new owner.
			 */
			if(0 == $ticket->next_worker_id) {
				DAO_Ticket::updateTicket($ticket->id,array(
					DAO_Ticket::NEXT_WORKER_ID => $worker->id
				));
			}

			// Signatures
			if(!empty($ticket_team) && !empty($ticket_team->signature)) {
	            $signature = $ticket_team->signature;
			} else {
			    // [TODO] Default signature
		        $signature = $settings->get(CerberusSettings::DEFAULT_SIGNATURE);
			}

			$tpl->assign('signature', str_replace(
			        array('#first_name#','#last_name#','#title#'),
			        array($worker->first_name,$worker->last_name,$worker->title),
			        $signature
			));
			
		    $signature_pos = $settings->get(CerberusSettings::DEFAULT_SIGNATURE_POS,0);
			$tpl->assign('signature_pos', $signature_pos);
		}
		
		$tpl->assign('upload_max_filesize', ini_get('upload_max_filesize'));
		
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/rpc/reply.tpl.php');
	}
	
	function sendReplyAction() {
	    @$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
	    
	    $worker = CerberusApplication::getActiveWorker();
	    
		$properties = array(
		    'message_id' => DevblocksPlatform::importGPC(@$_REQUEST['id']),
		    'ticket_id' => $ticket_id,
		    'to' => DevblocksPlatform::importGPC(@$_REQUEST['to']),
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
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$tpl->register_modifier('makehrefs', array('CerberusUtils', 'smarty_modifier_makehrefs')); 
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/conversation/index.tpl.php');
	}
	
	function showCommentsAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$tpl->assign('ticket_id', $ticket_id);
		
		$comments = DAO_TicketComment::getByTicketId($ticket_id);
		arsort($comments);
		$tpl->assign('comments', $comments);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/comments/index.tpl.php');
	}
	
	function saveCommentAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		@$comment = DevblocksPlatform::importGPC($_REQUEST['comment'],'string','');
		
		@$worker_id = CerberusApplication::getActiveWorker()->id;
		
		if(empty($worker_id) || empty($ticket_id) || empty($comment))
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$ticket_id)));
			
		$fields = array(
			DAO_TicketComment::CREATED => time(),
			DAO_TicketComment::TICKET_ID => $ticket_id,
			DAO_TicketComment::WORKER_ID => $worker_id,
			DAO_TicketComment::COMMENT => $comment,
		);
		$comment_id = DAO_TicketComment::create($fields);
		
		@$ticket = DAO_Ticket::getTicket($ticket_id);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$ticket->mask,'comments')));
	}
	
	function deleteCommentAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer',0);
		@$comment_id = DevblocksPlatform::importGPC($_REQUEST['comment_id'],'integer',0);
		
		@$worker_id = CerberusApplication::getActiveWorker()->id;
		
		if(empty($worker_id) || empty($ticket_id) || empty($comment_id))
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$ticket_id)));
		
		@$active_worker = CerberusApplication::getActiveWorker();

		$comment = DAO_TicketComment::get($comment_id);
		
		if(!empty($active_worker) && ($active_worker->is_superuser || $active_worker->id==$comment->worker_id))
			DAO_TicketComment::delete($comment_id);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$ticket_id,'comments')));
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
		
		$workers = DAO_Worker::getAll();
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
		@$subject = DevblocksPlatform::importGPC($_POST['subject'],'string','');
		@$waiting = DevblocksPlatform::importGPC($_POST['waiting'],'waiting',0);
		
		@$ticket = DAO_Ticket::getTicket($ticket_id);
		
		if(empty($ticket_id) || empty($ticket))
			return;
		
		$fields = array();
		
		// Properties

		if(isset($waiting))
			$fields[DAO_Ticket::IS_WAITING] = $waiting;
			
		if(isset($next_worker_id))
			$fields[DAO_Ticket::NEXT_WORKER_ID] = $next_worker_id;
			
		if(isset($next_action))
			$fields[DAO_Ticket::NEXT_ACTION] = $next_action;

		if(!empty($subject))
			$fields[DAO_Ticket::SUBJECT] = $subject;

		if(!empty($fields)) {
			DAO_Ticket::updateTicket($ticket_id, $fields);
		}
			
		// Requesters
			
		if(!empty($add)) {
			$adds = DevblocksPlatform::parseCrlfString($add);
			$adds = array_unique($adds);
			
			foreach($adds as $addy) {
				if(null != ($addy = DAO_Address::lookupAddress($addy, true))) {
					DAO_Ticket::createRequester($addy->id, $ticket_id);
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
	
	function doSplitMessageAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		if(null == ($orig_message = DAO_Ticket::getMessage($id)))
			return;
		
		if(null == ($orig_headers = $orig_message->getHeaders()))
			return;
			
		if(null == ($orig_ticket = DAO_Ticket::getTicket($orig_message->ticket_id)))
			return;

		if(null == ($messages = DAO_Ticket::getMessagesByTicket($orig_message->ticket_id)))
			return;
			
		// Create a new ticket
		$new_ticket_mask = CerberusApplication::generateTicketMask();
		
		$new_ticket_id = DAO_Ticket::createTicket(array(
			DAO_Ticket::CREATED_DATE => $orig_message->created_date,
			DAO_Ticket::UPDATED_DATE => $orig_message->created_date,
			DAO_Ticket::CATEGORY_ID => $orig_ticket->category_id,
			DAO_Ticket::FIRST_MESSAGE_ID => $orig_message->id,
			DAO_Ticket::FIRST_WROTE_ID => $orig_message->address_id,
			DAO_Ticket::LAST_WROTE_ID => $orig_message->address_id,
			DAO_Ticket::LAST_ACTION_CODE => CerberusTicketActionCode::TICKET_OPENED,
			DAO_Ticket::IS_CLOSED => CerberusTicketStatus::OPEN,
			DAO_Ticket::IS_DELETED => 0,
			DAO_Ticket::MASK => $new_ticket_mask,
			DAO_Ticket::SUBJECT => (isset($orig_headers['subject']) ? $orig_headers['subject'] : $orig_ticket->subject),
			DAO_Ticket::TEAM_ID => $orig_ticket->team_id,
		));

		// [TODO] SLA?
		
		// Requester
		DAO_Ticket::createRequester($orig_message->address_id,$new_ticket_id);
		
		// Pull the message off the ticket (reparent)
		unset($messages[$orig_message->id]);
		
		DAO_Message::update($orig_message->id,array(
			DAO_Message::TICKET_ID => $new_ticket_id
		));
		
		// Reindex the original ticket (last wrote, etc.)
		$last_message = end($messages); /* @var CerberusMessage $last_message */
		
		DAO_Ticket::updateTicket($orig_ticket->id, array(
			DAO_Ticket::LAST_WROTE_ID => $last_message->address_id
		));
		
		// Remove requester if they don't still have messages on the original ticket
		reset($messages);
		$found = false;
		
		if(is_array($messages))
		foreach($messages as $msgid => $msg) {
			if($msg->address_id == $orig_message->address_id) {
				$found = true;	
				break;
			}
		}
		
		if(!$found)
			DAO_Ticket::deleteRequester($orig_ticket->id,$orig_message->address_id);		
			
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$new_ticket_mask)));
	}
	
	function showContactHistoryAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$ticket = DAO_Ticket::getTicket($ticket_id);
		
		$contact = DAO_Address::get($ticket->first_wrote_address_id);
		$tpl->assign('contact', $contact);
		
		$visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
		$view = C4_AbstractViewLoader::getView('','contact_history');
		
		if(null == $view) {
			$view = new C4_TicketView();
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
			);
			$view->renderLimit = 10;
			$view->renderPage = 0;
			$view->renderSortBy = SearchFields_Ticket::TICKET_CREATED_DATE;
			$view->renderSortAsc = false;
		}

		$view->name = "Most recent tickets from " . htmlentities($contact->email);
		$view->params = array(
			SearchFields_Ticket::TICKET_FIRST_WROTE => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE,DevblocksSearchCriteria::OPER_EQ,$contact->email)
		);
		$tpl->assign('view', $view);
		
		C4_AbstractViewLoader::setView($view->id,$view);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		
		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);
		
		$team_categories = DAO_Bucket::getTeams();
		$tpl->assign('team_categories', $team_categories);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/history/index.tpl.php');
	}

	function showTasksAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$ticket = DAO_Ticket::getTicket($ticket_id);
		$tpl->assign('ticket', $ticket);
		
		$view = C4_AbstractViewLoader::getView('C4_TaskView', 'ticket_tasks');
		$view->id = 'ticket_tasks';
		$view->name = 'Ticket Tasks';
		$view->view_columns = array(
			SearchFields_Task::SOURCE_EXTENSION,
			SearchFields_Task::PRIORITY,
			SearchFields_Task::DUE_DATE,
			SearchFields_Task::WORKER_ID,
			SearchFields_Task::COMPLETED_DATE,
		);
		$view->params = array(
			new DevblocksSearchCriteria(SearchFields_Task::SOURCE_EXTENSION,'=','cerberusweb.tasks.ticket'),
			new DevblocksSearchCriteria(SearchFields_Task::SOURCE_ID,'=',$ticket_id),
		);
		$tpl->assign('view', $view);
		
		C4_AbstractViewLoader::setView($view->id, $view);
		
//		$view->name = "Most recent tickets from " . htmlentities($contact->email);
//		$view->params = array(
//			SearchFields_Ticket::TICKET_FIRST_WROTE => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE,DevblocksSearchCriteria::OPER_EQ,$contact->email)
//		);
//		$tpl->assign('view', $view);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/tasks/index.tpl.php');
	}

	function showCustomFieldsAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$ticket = DAO_Ticket::getTicket($ticket_id);
		$tpl->assign('ticket', $ticket);
		$tpl->assign('ticket_id', $ticket_id);
		
		$fields = DAO_TicketField::getWhere();
		$tpl->assign('ticket_fields', $fields);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$field_values = DAO_TicketFieldValue::getValuesByTickets($ticket_id);
		if(isset($field_values[$ticket_id]))
			$tpl->assign('ticket_field_values', $field_values[$ticket_id]);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/fields/index.tpl.php');
		exit;
	}
	
	// Post
	function setCustomFieldsAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		
		@$ticket = DAO_Ticket::getTicket($ticket_id);
		$fields = DAO_TicketField::getWhere();
		
		@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'],'array',array());

		if(is_array($field_ids))
		foreach($field_ids as $field_id) {
			if(!isset($fields[$field_id]))
				continue;
			
			@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field_id],'string','');
			
			switch($fields[$field_id]->type) {
				case Model_TicketField::TYPE_MULTI_LINE:
				case Model_TicketField::TYPE_SINGLE_LINE:
					if(!empty($field_value)) {
						DAO_TicketFieldValue::setFieldValue($ticket_id, $field_id, $field_value);
					} else {
						DAO_TicketFieldValue::unsetFieldValue($ticket_id, $field_id);
					}
					break;
					
				case Model_TicketField::TYPE_DROPDOWN:
					if(!empty($field_value)) {
						DAO_TicketFieldValue::setFieldValue($ticket_id, $field_id, $field_value);
					} else {
						DAO_TicketFieldValue::unsetFieldValue($ticket_id, $field_id);
					}
					break;
					
				case Model_TicketField::TYPE_CHECKBOX:
					$set = !empty($field_value) ? 1 : 0;
					DAO_TicketFieldValue::setFieldValue($ticket_id, $field_id, $set);
					break;
					
				case Model_TicketField::TYPE_DATE:
					@$date = strtotime($field_value);
					DAO_TicketFieldValue::setFieldValue($ticket_id, $field_id, $date);
					break;
			}
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$ticket->mask))); //,'fields'
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
		@$delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
		
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
	
	function showRequestersPanelAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		@$msg_id = DevblocksPlatform::importGPC($_REQUEST['msg_id'],'integer');
		
		$tpl->assign('ticket_id', $ticket_id);
		$tpl->assign('msg_id', $msg_id);
		
		$requesters = DAO_Ticket::getRequestersByTicket($ticket_id);
		$tpl->assign('requesters', $requesters);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/rpc/requester_panel.tpl.php');
	}
	
	function saveRequestersPanelAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_POST['ticket_id'],'integer');
		@$msg_id = DevblocksPlatform::importGPC($_POST['msg_id'],'integer');

		// Dels
		@$req_deletes = DevblocksPlatform::importGPC($_POST['req_deletes'],'array',array());
		if(!empty($req_deletes))
		foreach($req_deletes as $del_id) {
			DAO_Ticket::deleteRequester($ticket_id, $del_id);
		}		

		// Adds
		@$req_adds = DevblocksPlatform::importGPC($_POST['req_adds'],'string','');
		$req_list = DevblocksPlatform::parseCrlfString($req_adds);
		
		if(is_array($req_list) && !empty($req_list))
		foreach($req_list as $req) {
			if(empty($req))
				continue;
			if(null != ($req_addy = CerberusApplication::hashLookupAddress($req, true)))
				DAO_Ticket::createRequester($req_addy->id, $ticket_id);
		}
		
		$requesters = DAO_Ticket::getRequestersByTicket($ticket_id);

		$list = array();		
		foreach($requesters as $requester) {
			$list[] = $requester->email;
		}
		
		echo implode(', ', $list);
		exit;
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
		
    	$feeds = array();
    	$where = null;
    	
    	if(!empty($sources)) {
    		$where = sprintf("%s IN (%s)",
    			DAO_FnrExternalResource::ID,
    			implode(',', array_keys($sources))
    		);
    	}
    	
    	$resources = DAO_FnrExternalResource::getWhere($where);
    	$feeds = Model_FnrExternalResource::searchResources($resources, $q);

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
                    	if ((@$failed = array_shift($stack)) == "failed") {
                    		$tpl->assign('failed',true);
                    	}
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
//				$manifest = DevblocksPlatform::getExtension('login.ldap');
				$inst = $manifest->createInstance(1); /* @var $inst CerberusLoginPageExtension */
				$inst->renderLoginForm();
                break;
        }
	}
	
	function showAction() {
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('login')));
	}

	// POST
	function authenticateAction() {
		@$email = DevblocksPlatform::importGPC($_POST['email']);
		@$password = DevblocksPlatform::importGPC($_POST['password']);
		@$original_path = explode(',',DevblocksPlatform::importGPC($_POST['original_path']));
		@$original_query_str = DevblocksPlatform::importGPC($_POST['original_query']);
		
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
	
	function authenticateLDAPAction() {
		@$server = DevblocksPlatform::importGPC($_POST['server']);
		@$port = DevblocksPlatform::importGPC($_POST['port']);
		@$dn = DevblocksPlatform::importGPC($_POST['dn']);
		@$password = DevblocksPlatform::importGPC($_POST['password']);
		@$original_path = explode(',',DevblocksPlatform::importGPC($_POST['original_path']));
		@$original_query_str = DevblocksPlatform::importGPC($_POST['original_query']);
		
		$manifest = DevblocksPlatform::getExtension('login.ldap');
		$inst = $manifest->createInstance(); /* @var $inst CerberusLoginPageExtension */

		$url_service = DevblocksPlatform::getUrlService();
		
		if($inst->authenticate(array('server' => $server, 'port' => $port, 'dn' => $dn, 'password' => $password))) {
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
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		if(null != ($worker = CerberusApplication::getActiveWorker())) {
			DAO_Worker::logActivity($worker->id, new Model_Activity(null));
		}
		
		$session->clear();
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login')));
	}
	
	// Post
	function doRecoverStep1Action() {
	    @$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string');
	    
	    $worker = DAO_Worker::lookupAgentEmail($email);
	    
	    if(empty($email) || empty($worker))
	        return;
	    
	    $_SESSION[self::KEY_FORGOT_EMAIL] = $email;
	    
	    try {
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
				throw new Exception('Password Forgot confirmation email failed to send.');
			}
	    } catch (Exception $e) {
	    	DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login','forgot','step1','failed')));
	    }
	    
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
            DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('login','forgot','step3')));	        
	    } else {
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
            
            DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login')));
	    } else {
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
		
		$tab_manifests = DevblocksPlatform::getExtensions('cerberusweb.preferences.tab', false);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		@$section = array_shift($path); // section
		switch($section) {
			case 'confirm_email':
				@$code = array_shift($path);
				$active_worker = CerberusApplication::getActiveWorker();
				
				$worker_addresses = DAO_AddressToWorker::getWhere(sprintf("%s = '%s' AND %s = %d",
					DAO_AddressToWorker::CODE,
					addslashes(str_replace(' ','',$code)),
					DAO_AddressToWorker::WORKER_ID,
					$active_worker->id
				));

				@$worker_address = array_shift($worker_addresses);
				
				if(!empty($code) 
					&& null != $worker_address 
					&& $worker_address->code == $code 
					&& $worker_address->code_expire > time()) {
						
						DAO_AddressToWorker::update($worker_address->address,array(
							DAO_AddressToWorker::CODE => '',
							DAO_AddressToWorker::IS_CONFIRMED => 1,
							DAO_AddressToWorker::CODE_EXPIRE => 0
						));
						
						$output = array(sprintf("%s has been confirmed!", $worker_address->address));
						$tpl->assign('pref_success', $output);
					
				} else {
					$errors = array('The confirmation code you provided is not valid.');
					$tpl->assign('pref_errors', $errors);
				}
				
				$tpl->display('file:' . $tpl_path . '/preferences/index.tpl.php');
				break;
			
		    default:
		    	$tpl->assign('tab', $section);
				$tpl->display('file:' . $tpl_path . '/preferences/index.tpl.php');
				break;
		}
	}
	
	// Ajax
	function showTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_PreferenceTab) {
			$inst->showTab();
		}
	}
	
	// Post
	function saveTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_PreferenceTab) {
			$inst->saveTab();
		}
	}
	
	/*
	 * [TODO] Proxy any func requests to be handled by the tab directly, 
	 * instead of forcing tabs to implement controllers.  This should check 
	 * for the *Action() functions just as a handleRequest would
	 */
	/*
	function handleTabRequestAction() {
	}
	*/
	
	// Ajax [TODO] This should probably turn into Extension_PreferenceTab
	function showGeneralAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(__FILE__) . '/templates';
		$tpl->assign('path', $tpl_path);
		$tpl->cache_lifetime = "0";
		
		$worker = CerberusApplication::getActiveWorker();
		$tpl->assign('worker', $worker);
		
		$tour_enabled = DAO_WorkerPref::get($worker->id, 'assist_mode');
		$tour_enabled = ($tour_enabled===false) ? 1 : $tour_enabled;
		$tpl->assign('assist_mode', $tour_enabled);
		
		$addresses = DAO_AddressToWorker::getByWorker($worker->id);
		$tpl->assign('addresses', $addresses);
		
		$tpl->display('file:' . $tpl_path . '/preferences/modules/general.tpl.php');
	}
	
	
	// Ajax [TODO] This should probably turn into Extension_PreferenceTab
	function showRssAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(__FILE__) . '/templates';
		$tpl->assign('path', $tpl_path);
		$tpl->cache_lifetime = "0";
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$feeds = DAO_TicketRss::getByWorker($active_worker->id);
		$tpl->assign('feeds', $feeds);
		
		$tpl->display('file:' . $tpl_path . '/preferences/modules/rss.tpl.php');
	}
	
	// Post [TODO] This should probably turn into Extension_PreferenceTab
	function saveDefaultsAction() {
		@$timezone = DevblocksPlatform::importGPC($_REQUEST['timezone'],'string');
		@$default_signature = DevblocksPlatform::importGPC($_REQUEST['default_signature'],'string');
		@$default_signature_pos = DevblocksPlatform::importGPC($_REQUEST['default_signature_pos'],'integer',0);
		@$reply_box_height = DevblocksPlatform::importGPC($_REQUEST['reply_box_height'],'integer');
	    
		$worker = CerberusApplication::getActiveWorker();
   		$tpl = DevblocksPlatform::getTemplateService();
   		$pref_errors = array();
   		
		@$new_password = DevblocksPlatform::importGPC($_REQUEST['change_pass'],'string');
		@$verify_password = DevblocksPlatform::importGPC($_REQUEST['change_pass_verify'],'string');
    	
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
		
		// Alternate Email Addresses
		@$new_email = DevblocksPlatform::importGPC($_REQUEST['new_email'],'string','');
		@$email_delete = DevblocksPlatform::importGPC($_REQUEST['email_delete'],'array',array());

		// Confirm deletions are assigned to the current worker
		if(!empty($email_delete))
		foreach($email_delete as $e) {
			if(null != ($worker_address = DAO_AddressToWorker::getByAddress($e))
				&& $worker_address->worker_id == $worker->id)
				DAO_AddressToWorker::unassign($e);
		}
		
		// Assign a new e-mail address if it's legitimate
		if(!empty($new_email)) {
			if(null != ($addy = DAO_Address::lookupAddress($new_email, true))) {
				if(null == ($assigned = DAO_AddressToWorker::getByAddress($new_email))) {
					$this->_sendConfirmationEmail($new_email, $worker);
				} else {
					$pref_errors[] = sprintf("'%s' is already assigned to a worker.", $new_email);
				}
			} else {
				$pref_errors[] = sprintf("'%s' is not a valid e-mail address.", $new_email);
			}
		}
		
		$tpl->assign('pref_errors', $pref_errors);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('preferences')));
	}
	
	function resendConfirmationAction() {
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
		
		$worker = CerberusApplication::getActiveWorker();
		$this->_sendConfirmationEmail($email, $worker);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('preferences')));
	}
	
	private function _sendConfirmationEmail($to, $worker) {
		$settings = CerberusSettings::getInstance();
		$url_writer = DevblocksPlatform::getUrlService();
		$tpl = DevblocksPlatform::getTemplateService();
							
		// Tentatively assign the e-mail address to this worker
		DAO_AddressToWorker::assign($to, $worker->id);
		
		// Create a confirmation code and save it
		$code = CerberusApplication::generatePassword(20);
		DAO_AddressToWorker::update($to, array(
			DAO_AddressToWorker::CODE => $code,
			DAO_AddressToWorker::CODE_EXPIRE => (time() + 24*60*60) 
		));
		
		// Email the confirmation code to the address
		// [TODO] This function can return false, and we need to do something different if it does.
		CerberusMail::quickSend(
			$to, 
			sprintf("New E-mail Address Confirmation (%s)", 
				$settings->get(CerberusSettings::HELPDESK_TITLE)
			),
			sprintf("%s has just added this e-mail address to their helpdesk account.\r\n\r\n".
				"To approve and continue, click the following link:\r\n".
				"%s\r\n\r\n".
				"If you did not request this, do not click the link above.  This request will expire in 24 hours.\r\n",
				$worker->getName(),
				$url_writer->write('c=preferences&a=confirm_email&code='.$code,true)
			)
		);
		
		$output = array(sprintf("A confirmation e-mail has been sent to %s", $to));
		$tpl->assign('pref_success', $output);
	}
	
	// Post [TODO] This should probably turn into Extension_PreferenceTab
	function saveRssAction() {
		@$id = DevblocksPlatform::importGPC($_POST['id']);
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(null != ($feed = DAO_TicketRss::getId($id)) && $feed->worker_id == $active_worker->id) {
			DAO_TicketRss::delete($id);
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('preferences','rss')));
	}
	
};

class ChTaskSource_Org extends Extension_TaskSource {
	function getSourceName() {
		return "Orgs";
	}
	
	function getSourceInfo($object_id) {
		if(null == ($contact_org = DAO_ContactOrg::get($object_id)))
			return;
		
		$url = DevblocksPlatform::getUrlService();
		return array(
			'name' => '[Org] '.$contact_org->name,
			'url' => $url->write(sprintf('c=contacts&a=orgs&id=%d',$object_id)),
		);
	}
};

class ChTaskSource_Ticket extends Extension_TaskSource {
	function getSourceName() {
		return "Tickets";
	}
	
	function getSourceInfo($object_id) {
		if(null == ($ticket = DAO_Ticket::getTicket($object_id)))
			return;
		
		$url = DevblocksPlatform::getUrlService();
		return array(
			'name' => '[Ticket] '.$ticket->subject,
			'url' => $url->write(sprintf('c=display&mask=%s',$ticket->mask)),
		);
	}
};

?>