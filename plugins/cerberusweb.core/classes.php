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

	    @$action = DevblocksPlatform::strAlphaNumDash(array_shift($path)) . 'Action';

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
			$controller = 'home';

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
		
        $tour_enabled = false;
		if(!empty($visit) && !is_null($visit->getWorker())) {
        	$worker = $visit->getWorker();
			$tour_enabled = intval(DAO_WorkerPref::get($worker->id, 'assist_mode', 1));
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
		
		$core_tpl = realpath(DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/') . DIRECTORY_SEPARATOR;
		$tpl->assign('core_tpl', $core_tpl);
		
		// Prebody Renderers
		$preBodyRenderers = DevblocksPlatform::getExtensions('cerberusweb.renderer.prebody', true);
		if(!empty($preBodyRenderers))
			$tpl->assign('prebody_renderers', $preBodyRenderers);
		
		// Timings
		$tpl->assign('render_time', (microtime(true) - DevblocksPlatform::getStartTime()));
		if(function_exists('memory_get_usage') && function_exists('memory_get_peak_usage')) {
			$tpl->assign('render_memory', memory_get_usage() - DevblocksPlatform::getStartMemory());
			$tpl->assign('render_peak_memory', memory_get_peak_usage() - DevblocksPlatform::getStartPeakMemory());
		}
		
		$tpl->display($core_tpl.'border.tpl');
		
//		$cache = DevblocksPlatform::getCacheService();
//		$cache->printStatistics();
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

class ChHomePage extends CerberusPageExtension {
	const VIEW_MY_EVENTS = 'home_myevents';
	
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

	function getActivity() {
		return new Model_Activity('activity.home');
	}
	
	function render() {
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$response = DevblocksPlatform::getHttpResponse();
		$tpl->assign('request_path', implode('/',$response->path));
		
		// Remember the last tab/URL
		if(null == ($selected_tab = @$response->path[1])) {
			$selected_tab = $visit->get(CerberusVisit::KEY_HOME_SELECTED_TAB, 'events');
		}
		$tpl->assign('selected_tab', $selected_tab);
		
		$tab_manifests = DevblocksPlatform::getExtensions('cerberusweb.home.tab', false);
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Custom workspaces
		$workspaces = DAO_WorkerWorkspaceList::getWorkspaces($active_worker->id);
		$tpl->assign('workspaces', $workspaces);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/home/index.tpl');
	}
	
	// Ajax
	function showTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_HomeTab) {
			$inst->showTab();
		}
	}
	
	function showMyEventsAction() {
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		// Select tab
		$visit->set(CerberusVisit::KEY_HOME_SELECTED_TAB, 'events');
		
		// My Events
		$myEventsView = C4_AbstractViewLoader::getView('', self::VIEW_MY_EVENTS);
		
		$title = vsprintf($translate->_('home.my_notifications.view.title'), $active_worker->getName());
		
		if(null == $myEventsView) {
			$myEventsView = new C4_WorkerEventView();
			$myEventsView->id = self::VIEW_MY_EVENTS;
			$myEventsView->name = $title;
			$myEventsView->renderLimit = 25;
			$myEventsView->renderPage = 0;
			$myEventsView->renderSortBy = SearchFields_WorkerEvent::CREATED_DATE;
			$myEventsView->renderSortAsc = 0;
			
			// Overload criteria
			$myEventsView->params = array(
				SearchFields_WorkerEvent::WORKER_ID => new DevblocksSearchCriteria(SearchFields_WorkerEvent::WORKER_ID,'=',$active_worker->id),
				SearchFields_WorkerEvent::IS_READ => new DevblocksSearchCriteria(SearchFields_WorkerEvent::IS_READ,'=',0),
			);
			
			C4_AbstractViewLoader::setView($myEventsView->id,$myEventsView);
		}
		
		$tpl->assign('view', $myEventsView);
		$tpl->display('file:' . dirname(__FILE__) . '/templates/home/tabs/my_events/index.tpl');
	}
	
	function showWorkspacesIntroTabAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/home/tabs/workspaces_intro/index.tpl');
	}
	
	function doWorkspaceInitAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		
		$workspace = 'My Work';
		
		// My Tickets
		
		$list = new Model_WorkerWorkspaceListView();
		$list->title = 'My Mail';
		$list->columns = array(
			SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
			SearchFields_Ticket::TICKET_UPDATED_DATE,
			SearchFields_Ticket::TEAM_NAME,
			SearchFields_Ticket::TICKET_CATEGORY_ID,
		);
		$list->params = array(
			SearchFields_Ticket::TICKET_CLOSED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',0), 
			SearchFields_Ticket::TICKET_WAITING => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_WAITING,'=',0), 
			SearchFields_Ticket::TICKET_NEXT_WORKER_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_NEXT_WORKER_ID,'=',$active_worker->id), 
		);
		$list->num_rows = 5;
		
		$fields = array(
			DAO_WorkerWorkspaceList::WORKER_ID => $active_worker->id,
			DAO_WorkerWorkspaceList::LIST_POS => 1,
			DAO_WorkerWorkspaceList::LIST_VIEW => serialize($list),
			DAO_WorkerWorkspaceList::WORKSPACE => $workspace,
			DAO_WorkerWorkspaceList::SOURCE_EXTENSION => ChWorkspaceSource_Ticket::ID,
		);
		DAO_WorkerWorkspaceList::create($fields);
		
		// My Tasks
		
		$list = new Model_WorkerWorkspaceListView();
		$list->title = 'My Tasks';
		$list->columns = array(
			SearchFields_Task::SOURCE_EXTENSION,
			SearchFields_Task::PRIORITY,
			SearchFields_Task::DUE_DATE,
		);
		$list->params = array(
			SearchFields_Task::IS_COMPLETED => new DevblocksSearchCriteria(SearchFields_Task::IS_COMPLETED,'=',0), 
			SearchFields_Task::WORKER_ID => new DevblocksSearchCriteria(SearchFields_Task::WORKER_ID,'=',$active_worker->id), 
		);
		$list->num_rows = 5;
		
		$fields = array(
			DAO_WorkerWorkspaceList::WORKER_ID => $active_worker->id,
			DAO_WorkerWorkspaceList::LIST_POS => 2,
			DAO_WorkerWorkspaceList::LIST_VIEW => serialize($list),
			DAO_WorkerWorkspaceList::WORKSPACE => $workspace,
			DAO_WorkerWorkspaceList::SOURCE_EXTENSION => ChWorkspaceSource_Task::ID,
		);
		DAO_WorkerWorkspaceList::create($fields);
		
		// Select the new tab
		$visit->set(CerberusVisit::KEY_HOME_SELECTED_TAB, 'w_'.$workspace);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('home')));
	}
	
	/**
	 * Open an event, mark it read, and redirect to its URL.
	 * Used by Home->Notifications view.
	 *
	 */
	function redirectReadAction() {
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;
		
		array_shift($stack); // home
		array_shift($stack); // redirectReadAction
		@$id = array_shift($stack); // id
		
		if(null != ($event = DAO_WorkerEvent::get($id))) {
			// Mark as read before we redirect
			DAO_WorkerEvent::update($id, array(
				DAO_WorkerEvent::IS_READ => 1
			));

			session_write_close();
			header("Location: " . $event->url);
		}
		exit;
	} 
	
	function doNotificationsMarkReadAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		@$row_ids = DevblocksPlatform::importGPC($_REQUEST['row_id'],'array',array());

		if(is_array($row_ids) && !empty($row_ids)) {
			DAO_WorkerEvent::updateWhere(
				array(
					DAO_WorkerEvent::IS_READ => 1,
				), 
				sprintf("%s IN (%s)",
					DAO_WorkerEvent::ID,
					implode(',', $row_ids)
				)
			);
		}
		
		$myEventsView = C4_AbstractViewLoader::getView('', $view_id);
		$myEventsView->render();
	}
	
	function showWorkspaceTabAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$visit = CerberusApplication::getVisit();
		$db = DevblocksPlatform::getDatabaseService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$current_workspace = DevblocksPlatform::importGPC($_REQUEST['workspace'],'string','');
		$workspaces = DAO_WorkerWorkspaceList::getWorkspaces($active_worker->id);

		// Fix a bad/old cache
		if(!empty($current_workspace) && false === array_search($current_workspace,$workspaces))
			$current_workspace = '';
		
		$views = array();
			
		if(empty($current_workspace) && !empty($workspaces)) { // custom dashboards
			$current_workspace = reset($workspaces);
		}
		
		if(!empty($current_workspace)) {
			// Remember the tab
			$visit->set(CerberusVisit::KEY_HOME_SELECTED_TAB, 'w_'.$current_workspace);
			
			$lists = DAO_WorkerWorkspaceList::getWhere(sprintf("%s = %d AND %s = %s",
				DAO_WorkerWorkspaceList::WORKER_ID,
				$active_worker->id,
				DAO_WorkerWorkspaceList::WORKSPACE,
				$db->qstr($current_workspace)
			));

			// Load the workspace sources to map to view renderer
	        $source_manifests = DevblocksPlatform::getExtensions(Extension_WorkspaceSource::EXTENSION_POINT, false);

	        // Loop through list schemas
			if(is_array($lists) && !empty($lists))
			foreach($lists as $list) { /* @var $list Model_WorkerWorkspaceList */
				$view_id = 'cust_'.$list->id;
				if(null == ($view = C4_AbstractViewLoader::getView('',$view_id))) {
					$list_view = $list->list_view; /* @var $list_view Model_WorkerWorkspaceListView */
					
					// Make sure we can find the workspace source (plugin not disabled)
					if(!isset($source_manifests[$list->source_extension])
						|| null == ($workspace_source = $source_manifests[$list->source_extension])
						|| !isset($workspace_source->params['view_class']))
						continue;
					
					// Make sure our workspace source has a valid renderer class
					$view_class = $workspace_source->params['view_class'];
					if(!class_exists($view_class))
						continue;
						
					$view = new $view_class;
					$view->id = $view_id;
					$view->name = $list_view->title;
					$view->renderLimit = $list_view->num_rows;
					$view->renderPage = 0;
					$view->view_columns = $list_view->columns;
					$view->params = $list_view->params;
					C4_AbstractViewLoader::setView($view_id, $view);
				}
				
				if(!empty($view))
					$views[] = $view;
			}
		
			$tpl->assign('current_workspace', $current_workspace);
			$tpl->assign('views', $views);
		}
		
		// Log activity
		DAO_Worker::logActivity(
			$active_worker->id,
			new Model_Activity(
				'activity.mail.workspaces',
				array(
					'<i>'.$current_workspace.'</i>'
				)
			)
		);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/home/workspaces/index.tpl');
	}
	
	function showEditWorkspacePanelAction() {
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
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/home/workspaces/edit_workspace_panel.tpl');
	}
	
	function doEditWorkspaceAction() {
		@$workspace = DevblocksPlatform::importGPC($_POST['workspace'],'string', '');
		@$rename_workspace = DevblocksPlatform::importGPC($_POST['rename_workspace'],'string', '');
		@$ids = DevblocksPlatform::importGPC($_POST['ids'],'array', array());
		@$pos = DevblocksPlatform::importGPC($_POST['pos'],'array', array());
		
		$db = DevblocksPlatform::getDatabaseService();
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		
		if(!empty($rename_workspace)) {
			$fields = array(
				DAO_WorkerWorkspaceList::WORKSPACE => $rename_workspace,
			);
			DAO_WorkerWorkspaceList::updateWhere($fields, sprintf("%s = %s AND %s = %d",
				DAO_WorkerWorkspaceList::WORKSPACE,
				$db->qstr($workspace),
				DAO_WorkerWorkspaceList::WORKER_ID,
				$active_worker->id
			));
			
			$workspace = $rename_workspace;
		}
		
		// Reorder worklists on workspace
		if(is_array($ids) && !empty($ids))
		foreach($ids as $idx => $id) {
			DAO_WorkerWorkspaceList::update($id,array(
				DAO_WorkerWorkspaceList::LIST_POS => @intval($pos[$idx])
			));
		}
		
		// Change active tab
		$visit->set(CerberusVisit::KEY_HOME_SELECTED_TAB, 'w_'.$workspace);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('home')));	
	}
	
	function doDeleteWorkspaceAction() {
		@$workspace = DevblocksPlatform::importGPC($_POST['workspace'],'string', '');
		
		$db = DevblocksPlatform::getDatabaseService();
		$active_worker = CerberusApplication::getActiveWorker();

		$lists = DAO_WorkerWorkspaceList::getWhere(sprintf("%s = %s AND %s = %d",
			DAO_WorkerWorkspaceList::WORKSPACE,
			$db->qstr($workspace),
			DAO_WorkerWorkspaceList::WORKER_ID,
			$active_worker->id
		));

		DAO_WorkerWorkspaceList::delete(array_keys($lists));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('home')));	
	}
};

class ChActivityPage extends CerberusPageExtension {
	
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
	
	function getActivity() {
		return new Model_Activity('activity.activity');
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		array_shift($stack); // activity

		$tab_manifests = DevblocksPlatform::getExtensions('cerberusweb.activity.tab', false);
		uasort($tab_manifests, create_function('$a, $b', "return strcasecmp(\$a->name,\$b->name);\n"));
		$tpl->assign('tab_manifests', $tab_manifests);
		
		@$tab_selected = array_shift($stack);
		if(empty($tab_selected)) $tab_selected = 'tasks';
		$tpl->assign('tab_selected', $tab_selected);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/activity/index.tpl');
	}
	
	// Ajax
	function showTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_ActivityTab) {
			$inst->showTab();
		}
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
		return new Model_Activity('activity.tickets',array(
	    	""
	    ));
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$visit = CerberusApplication::getVisit();
		
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
				$settings = CerberusSettings::getInstance();
				
				$workers = DAO_Worker::getAllActive();
				$tpl->assign('workers', $workers);
				
				$teams = DAO_Group::getAll();
				$tpl->assign_by_ref('teams', $teams);
				
				if($visit->exists('compose.last_ticket')) {
					$ticket_mask = $visit->get('compose.last_ticket');
					$tpl->assign('last_ticket_mask', $ticket_mask);
					$visit->set('compose.last_ticket',null); // clear
				}
				
				$team_categories = DAO_Bucket::getTeams();
				$tpl->assign('team_categories', $team_categories);
				
				// SendMailToolbarItem Extensions
				$sendMailToolbarItems = DevblocksPlatform::getExtensions('cerberusweb.mail.send.toolbaritem', true);
				if(!empty($sendMailToolbarItems))
					$tpl->assign('sendmail_toolbaritems', $sendMailToolbarItems);
				
				$tpl->assign('upload_max_filesize', ini_get('upload_max_filesize'));
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/compose/index.tpl');
				break;
				
			case 'create':
				$workers = DAO_Worker::getAllActive();
				$tpl->assign('workers', $workers);
				
				$teams = DAO_Group::getAll();
				$tpl->assign('teams', $teams);
				
				if($visit->exists('compose.last_ticket')) {
					$ticket_mask = $visit->get('compose.last_ticket');
					$tpl->assign('last_ticket_mask', $ticket_mask);
					$visit->set('compose.last_ticket',null); // clear
				}
				
				$team_categories = DAO_Bucket::getTeams();
				$tpl->assign('team_categories', $team_categories);
				
				// LogMailToolbarItem Extensions
				$logMailToolbarItems = DevblocksPlatform::getExtensions('cerberusweb.mail.log.toolbaritem', true);
				if(!empty($logMailToolbarItems))
					$tpl->assign('logmail_toolbaritems', $logMailToolbarItems);
				
				$tpl->assign('upload_max_filesize', ini_get('upload_max_filesize'));
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/create/index.tpl');
				break;
				
			default:
				$active_worker = CerberusApplication::getActiveWorker();
				
				// Clear all undo actions on reload
			    C4_TicketView::clearLastActions();
			    				
				$quick_search_type = $visit->get('quick_search_type');
				$tpl->assign('quick_search_type', $quick_search_type);

				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/index.tpl');
				break;
		}
		
	}
	
	function showWorkflowTabAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
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
		$workflowView = C4_AbstractViewLoader::getView('', CerberusApplication::VIEW_MAIL_WORKFLOW);
		
		$title = $translate->_('mail.overview.all_groups');
		
		// [JAS]: Recover from a bad cached ID.
		if(null == $workflowView) {
			// Defaults
			$workflowViewDefaults = new C4_AbstractViewModel();
			$workflowViewDefaults->view_columns = array(
				SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
				SearchFields_Ticket::TICKET_UPDATED_DATE,
				SearchFields_Ticket::TEAM_NAME,
				SearchFields_Ticket::TICKET_CATEGORY_ID,
			);
			$workflowViewDefaults->renderLimit = 10;
			$workflowViewDefaults->renderSortBy = SearchFields_Ticket::TICKET_UPDATED_DATE;
			$workflowViewDefaults->renderSortAsc = 0;
			
			$workflowView = new C4_TicketView();
			$workflowView->id = CerberusApplication::VIEW_MAIL_WORKFLOW;
			$workflowView->name = $title;
			$workflowView->dashboard_id = 0;
			$workflowView->view_columns = $workflowViewDefaults->view_columns;
			$workflowView->params = array();
			$workflowView->renderLimit = $workflowViewDefaults->renderLimit;
			$workflowView->renderPage = 0;
			$workflowView->renderSortBy = $workflowViewDefaults->renderSortBy;
			$workflowView->renderSortAsc = $workflowViewDefaults->renderSortAsc;
			
			C4_AbstractViewLoader::setView($workflowView->id,$workflowView);
		}
		
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
					$workflowView->params[SearchFields_Ticket::TEAM_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TEAM_ID,'=',$filter_group_id);
					
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
						SearchFields_Ticket::TEAM_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TEAM_ID,'=',$group_id),
						SearchFields_Ticket::TICKET_CATEGORY_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CATEGORY_ID,'in',$assignable_bucket_ids),
					);
				}
				
				$workflowView->params['tmp_GrpBkt'] = $subparams;
				
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
		
        $tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/workflow/index.tpl');
	}
	
	function showOverviewTabAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
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
		$overView = C4_AbstractViewLoader::getView('', CerberusApplication::VIEW_OVERVIEW_ALL);
		
		$title = $translate->_('mail.overview.all_groups');
		
		// [JAS]: Recover from a bad cached ID.
		if(null == $overView) {
			// Defaults
			$overViewDefaults = new C4_AbstractViewModel();
			$overViewDefaults->view_columns = array(
				SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
				SearchFields_Ticket::TICKET_UPDATED_DATE,
				SearchFields_Ticket::TEAM_NAME,
				SearchFields_Ticket::TICKET_CATEGORY_ID,
				SearchFields_Ticket::TICKET_NEXT_WORKER_ID,
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
			$overView->view_columns = $overViewDefaults->view_columns;
			$overView->params = array();
			$overView->renderLimit = $overViewDefaults->renderLimit;
			$overView->renderPage = 0;
			$overView->renderSortBy = $overViewDefaults->renderSortBy;
			$overView->renderSortAsc = $overViewDefaults->renderSortAsc;
			
			C4_AbstractViewLoader::setView($overView->id,$overView);
		}
		
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
					$overView->params[SearchFields_Ticket::TEAM_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TEAM_ID,'=',$filter_group_id);
					
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
					$overView->params[SearchFields_Ticket::TEAM_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TEAM_ID,'=',$filter_group_id);
				}

				break;
				
			case 'worker':
				@$filter_worker_id = array_shift($response_path);

				$overView->params = array(
					SearchFields_Ticket::TICKET_CLOSED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',CerberusTicketStatus::OPEN),
					SearchFields_Ticket::TICKET_WAITING => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_WAITING,'=',0),
					$overView->params[SearchFields_Ticket::TEAM_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TEAM_ID,'in',array_keys($memberships)), // censor
				);

				if(!is_null($filter_worker_id)) {
					$title = vsprintf($translate->_('mail.overview.assigned.title'), $workers[$filter_worker_id]->getName());
					$overView->params[SearchFields_Ticket::TICKET_NEXT_WORKER_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_NEXT_WORKER_ID,'=',$filter_worker_id);
					
					@$filter_group_id = array_shift($response_path);
					if(!is_null($filter_group_id) && isset($groups[$filter_group_id])) {
						$overView->params[SearchFields_Ticket::TEAM_ID] = new DevblocksSearchCriteria(SearchFields_Ticket::TEAM_ID,'=',$filter_group_id);
					}
				}
				
				break;
				
			case 'all':
			default:
				$overView->params = array(
					SearchFields_Ticket::TICKET_CLOSED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',CerberusTicketStatus::OPEN),
					SearchFields_Ticket::TICKET_WAITING => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_WAITING,'=',0),
					SearchFields_Ticket::TEAM_ID => new DevblocksSearchCriteria(SearchFields_Ticket::TEAM_ID,'in',array_keys($memberships)),
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
		
        $tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/overview/index.tpl');		
	}
	
	function showSearchTabAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
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
		
		$view = C4_AbstractViewLoader::getView('', CerberusApplication::VIEW_SEARCH);
		
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
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/search/index.tpl');
	}
	
	// Ajax
	function refreshSidebarAction() {
		$db = DevblocksPlatform::getDatabaseService();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

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
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/workflow/sidebar.tpl');
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
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/overview/sidebar.tpl');
				break;
		}
	}
	
	// Ajax
	// [TODO] Move to 'c=internal'
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

	// Ajax
	function showAddInboxRulePanelAction() {
	    @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
	    
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(__FILE__) . '/templates/';
		$tpl->assign('path', $tpl_path);
		
		$tpl->assign('ticket_id', $ticket_id);
		$tpl->assign('view_id', $view_id);
		
		$ticket = DAO_Ticket::getTicket($ticket_id);
		$tpl->assign('ticket', $ticket);

		$messages = $ticket->getMessages();
		$message = array_shift($messages); /* @var $message CerberusMessage */
		$message_headers = $message->getHeaders();
		$tpl->assign('message', $message);
		$tpl->assign('message_headers', $message_headers);

		// To/Cc
		$tocc = array();
		@$to_list = imap_rfc822_parse_adrlist($message_headers['to'],'localhost');
		@$cc_list = imap_rfc822_parse_adrlist($message_headers['cc'],'localhost');
		
		if(is_array($to_list))
		foreach($to_list as $addy) {
			$tocc[] = $addy->mailbox . '@' . $addy->host;
		}
		if(is_array($cc_list))
		foreach($cc_list as $addy) {
			$tocc[] = $addy->mailbox . '@' . $addy->host;
		}
		
		if(!empty($tocc))
			$tpl->assign('tocc_list', implode(', ', $tocc));
		
		@$first_address = DAO_Address::get($ticket->first_wrote_address_id);
		$tpl->assign('first_address', $first_address);
		
		// Grops
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		// Buckets
		$team_categories = DAO_Bucket::getTeams();
		$tpl->assign('team_categories', $team_categories);
	    
		// Workers
	    $workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/rpc/inbox_rule_panel.tpl');
	}
	
	// Ajax
	function saveAddInboxRulePanelAction() {
		$translate = DevblocksPlatform::getTranslationService();
		
   		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
   		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer');

   		$view = C4_AbstractViewLoader::getView('C4_TicketView', $view_id); /* @var $view C4_TicketView */
   		
   		if(empty($group_id)) {
   			$view->render();
   			exit;
   		}
   		
		@$name = DevblocksPlatform::importGPC($_POST['name'],'string','');
		@$rules = DevblocksPlatform::importGPC($_POST['rules'],'array',array());
		@$do = DevblocksPlatform::importGPC($_POST['do'],'array',array());
   		
		if(empty($name))
			$name = $translate->_('mail.inbox_filter');
		
		$criterion = array();
		$actions = array();
		
		// Criteria
		if(is_array($rules))
		foreach($rules as $rule) {
			$rule = DevblocksPlatform::strAlphaNumDash($rule);
			@$value = DevblocksPlatform::importGPC($_POST['value_'.$rule],'string','');
			
			// [JAS]: Allow empty $value (null/blank checking)
			
			$criteria = array(
				'value' => $value,
			);
			
			// Any special rule handling
			switch($rule) {
				case 'subject':
					break;
				case 'from':
					break;
				case 'tocc':
					break;
				case 'header1':
				case 'header2':
				case 'header3':
				case 'header4':
				case 'header5':
					if(null != (@$header = DevblocksPlatform::importGPC($_POST[$rule],'string',null)))
						$criteria['header'] = strtolower($header);
					break;
				case 'body':
					break;
				case 'attachment':
					break;
				default: // ignore invalids
					continue;
					break;
			}
			
			$criterion[$rule] = $criteria;
		}
   		
			// Actions
		if(is_array($do))
		foreach($do as $act) {
			$action = array();
			
			switch($act) {
				// Move group/bucket
				case 'move':
					@$move_code = DevblocksPlatform::importGPC($_REQUEST['do_move'],'string',null);
					if(0 != strlen($move_code)) {
						list($g_id, $b_id) = CerberusApplication::translateTeamCategoryCode($move_code);
						$action = array(
							'group_id' => intval($g_id),
							'bucket_id' => intval($b_id),
						);
					}
					break;
				// Assign to worker
				case 'assign':
					@$worker_id = DevblocksPlatform::importGPC($_REQUEST['do_assign'],'string',null);
					if(0 != strlen($worker_id))
						$action = array(
							'worker_id' => intval($worker_id)
						);
					break;
				// Spam training
				case 'spam':
					@$is_spam = DevblocksPlatform::importGPC($_REQUEST['do_spam'],'string',null);
					if(0 != strlen($is_spam))
						$action = array(
							'is_spam' => (!$is_spam?0:1)
						);
					break;
				// Set status
				case 'status':
					@$status = DevblocksPlatform::importGPC($_REQUEST['do_status'],'string',null);
					if(0 != strlen($status))
						$action = array(
							'is_closed' => (0==$status?0:1),
							'is_deleted' => (2==$status?1:0),
						);
					break;
				default: // ignore invalids
					continue;
					break;
			}
			
			$actions[$act] = $action;
		}		
		
   		$fields = array(
   			DAO_GroupInboxFilter::NAME => $name,
   			DAO_GroupInboxFilter::GROUP_ID => $group_id,
   			DAO_GroupInboxFilter::CRITERIA_SER => serialize($criterion),
   			DAO_GroupInboxFilter::ACTIONS_SER => serialize($actions),
   			DAO_GroupInboxFilter::POS => 0
   		);
   		
   		$routing_id = DAO_GroupInboxFilter::create($fields);
   		
   		// Loop through all the tickets in this inbox
   		list($inbox_tickets, $null) = DAO_Ticket::search(
   			null,
   			array(
   				new DevblocksSearchCriteria(SearchFields_Ticket::TEAM_ID,'=',$group_id),
   				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CATEGORY_ID,'=','0'),
   			),
   			-1,
   			0,
   			null,
   			null,
   			false
   		);
   		
   		if(is_array($inbox_tickets))
   		foreach($inbox_tickets as $inbox_ticket) { /* @var $inbox_ticket CerberusTicket */
   			// Run only this new rule against all tickets in the group inbox
   			CerberusApplication::runGroupRouting($group_id, intval($inbox_ticket[SearchFields_Ticket::TICKET_ID]), $routing_id);
   		}
   		
   		$view->render();
   		exit;
	}
	
	function showComposePeekAction() {
	    @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');
	    
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(__FILE__) . '/templates/';
		$tpl->assign('path', $tpl_path);
		
		$tpl->assign('view_id', $view_id);
		$tpl->assign('to', $to);
		
		$teams = DAO_Group::getAll();
		$tpl->assign_by_ref('teams', $teams);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/compose/peek.tpl');
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
//		@$move_bucket = DevblocksPlatform::importGPC($_POST['bucket_id'],'string','');
		@$next_worker_id = DevblocksPlatform::importGPC($_POST['next_worker_id'],'integer',0);
//		@$next_action = DevblocksPlatform::importGPC($_POST['next_action'],'string','');
//		@$ticket_reopen = DevblocksPlatform::importGPC($_POST['ticket_reopen'],'string','');
		
		$properties = array(
			'team_id' => $team_id,
			'to' => $to,
//			'cc' => $cc,
//			'bcc' => $bcc,
			'subject' => $subject,
			'content' => $content,
			'files' => $files,
			'closed' => $closed,
//			'move_bucket' => $move_bucket,
			'next_worker_id' => $next_worker_id,
//			'next_action' => $next_action,
//			'ticket_reopen' => $ticket_reopen,
		);
		
		$ticket_id = CerberusMail::compose($properties);

		if(!empty($view_id)) {
			$view = C4_AbstractViewLoader::getView('C4_TicketView', $view_id);
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
		$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/rpc/preview_panel.tpl');
	}
	
	// Ajax
	function savePreviewAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$subject = DevblocksPlatform::importGPC($_REQUEST['subject'],'string','');
		@$closed = DevblocksPlatform::importGPC($_REQUEST['closed'],'integer',0);
		@$next_action = DevblocksPlatform::importGPC($_REQUEST['next_action'],'string','');
		@$next_worker_id = DevblocksPlatform::importGPC($_REQUEST['next_worker_id'],'integer',0);
		@$bucket = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'string','');
		@$spam_training = DevblocksPlatform::importGPC($_REQUEST['spam_training'],'string','');
		
		$fields = array(
			DAO_Ticket::SUBJECT => $subject,
			DAO_Ticket::NEXT_ACTION => $next_action,
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
		
		$view = C4_AbstractViewLoader::getView('C4_TicketView', $view_id);
		$view->render();
		exit;
	}
	
	function composeMailAction() {
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
		@$next_action = DevblocksPlatform::importGPC($_POST['next_action'],'string','');
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
			'next_action' => $next_action,
			'ticket_reopen' => $ticket_reopen,
			'unlock_date' => $unlock_date,
		);
		
		$ticket_id = CerberusMail::compose($properties);
		
		$ticket = DAO_Ticket::getTicket($ticket_id);
		
		$visit = CerberusApplication::getVisit(); /* @var CerberusVisit $visit */
		$visit->set('compose.last_ticket', $ticket->mask);
		
		//DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('tickets','compose')));
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','compose')));
	}
	
	function logTicketAction() {
		@$team_id = DevblocksPlatform::importGPC($_POST['team_id'],'integer'); 
		@$to = DevblocksPlatform::importGPC($_POST['to'],'string');
		@$subject = DevblocksPlatform::importGPC($_POST['subject'],'string');
		@$content = DevblocksPlatform::importGPC($_POST['content'],'string');
		@$files = $_FILES['attachment'];
		
		@$closed = DevblocksPlatform::importGPC($_POST['closed'],'integer',0);
		@$move_bucket = DevblocksPlatform::importGPC($_POST['bucket_id'],'string','');
		@$next_worker_id = DevblocksPlatform::importGPC($_POST['next_worker_id'],'integer',0);
		@$next_action = DevblocksPlatform::importGPC($_POST['next_action'],'string','');
		@$ticket_reopen = DevblocksPlatform::importGPC($_POST['ticket_reopen'],'string','');
		@$unlock_date = DevblocksPlatform::importGPC($_POST['unlock_date'],'string','');
		
		if(DEMO_MODE) {
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','create')));
			return;
		}
		
		if(empty($to) || empty($team_id)) {
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','create')));
			return;
		}
		
		// [TODO] "Opened/sent on behalf of..."
		
		$properties = array(
			'team_id' => $team_id,
			'to' => $to,
//			'cc' => $cc,
//			'bcc' => $bcc,
			'subject' => $subject,
			'content' => $content,
			'files' => $files,
			'closed' => $closed,
			'move_bucket' => $move_bucket,
			'next_worker_id' => $next_worker_id,
			'next_action' => $next_action,
			'ticket_reopen' => $ticket_reopen,
			'unlock_date' => $unlock_date,
			'no_mail' => true,
		);
		
		$ticket_id = CerberusMail::compose($properties);
		
		$ticket = DAO_Ticket::getTicket($ticket_id);

		$visit = CerberusApplication::getVisit(); /* @var CerberusVisit $visit */
		$visit->set('compose.last_ticket', $ticket->mask);
		
		//DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('display',$ticket_id)));
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('tickets','create')));
	}
	
	function showViewAutoAssistAction() {
        @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
        @$mode = DevblocksPlatform::importGPC($_REQUEST['mode'],'string','senders');
        @$mode_param = DevblocksPlatform::importGPC($_REQUEST['mode_param'],'string','');

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(__FILE__) . '/templates/';
		$tpl->assign('path', $tpl_path);
        
        $visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */

        $view = C4_AbstractViewLoader::getView('',$view_id);
        
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
			$view->params[] = new DevblocksSearchCriteria(SearchFields_Ticket::TEAM_ID, 'in', array_keys($memberships)); 
			
	        // [JAS]: Calculate statistics about the current view (top unique senders/subjects/domains)
		    $biggest = DAO_Ticket::analyze($view->params, 15, $mode, $mode_param);
		    $tpl->assign('biggest', $biggest);
	        
	        $tpl->display($tpl_path.'tickets/rpc/ticket_view_assist.tpl');
        }
	}
	
	function viewAutoAssistAction() {
	    @$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');

        $visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
		$view = C4_AbstractViewLoader::getView('',$view_id);

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
		    $view = C4_AbstractViewLoader::getView('',$view_id);
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

	function showBatchPanelAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
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
		$tpl->display('file:' . dirname(__FILE__) . '/templates/tickets/rpc/batch_panel.tpl');
	}
	
	// Ajax
	function doBatchUpdateAction() {
	    @$ticket_id_str = DevblocksPlatform::importGPC($_REQUEST['ticket_ids'],'string');
	    @$shortcut_name = DevblocksPlatform::importGPC($_REQUEST['shortcut_name'],'string','');

	    @$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
	    @$senders = DevblocksPlatform::importGPC($_REQUEST['senders'],'string','');
	    @$subjects = DevblocksPlatform::importGPC($_REQUEST['subjects'],'string','');
	    
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView('',$view_id);

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
		$view->params['tmp'] = new DevblocksSearchCriteria(SearchFields_Ticket::TEAM_ID, 'in', array_keys($memberships)); 
	    
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
		
		$view = C4_AbstractViewLoader::getView('',$view_id);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->assign('view_id', $view_id);
		$tpl->assign('view', $view);
		$tpl->assign('source', $source);
		
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/internal/views/view_rss_builder.tpl');
	}
	
	// post
	function viewBuildRssAction() {
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id']);
		@$source = DevblocksPlatform::importGPC($_POST['source']);
		@$title = DevblocksPlatform::importGPC($_POST['title']);
		$active_worker = CerberusApplication::getActiveWorker();

		$view = C4_AbstractViewLoader::getView('',$view_id);
		
		$hash = md5($title.$view_id.$active_worker->id.$now);
		
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

	    $view = C4_AbstractViewLoader::getView('',$id);

		if(!empty($view->params)) {
		    $params = array();
		    
		    // Index by field name for search system
		    if(is_array($view->params))
		    foreach($view->params as $key => $criteria) { /* @var $criteria DevblocksSearchCriteria */
                $params[$key] = $criteria;
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
		$translate = DevblocksPlatform::getTranslationService();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$worker = CerberusApplication::getActiveWorker();
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}

		if(file_exists(APP_PATH . '/install/')) {
			$tpl->assign('install_dir_warning', true);
		}
		
		$tab_manifests = DevblocksPlatform::getExtensions('cerberusweb.config.tab', false);
		uasort($tab_manifests, create_function('$a, $b', "return strcasecmp(\$a->name,\$b->name);\n"));
		$tpl->assign('tab_manifests', $tab_manifests);
		
		// Selected tab
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		array_shift($stack); // config
		$tab_selected = array_shift($stack);
		$tpl->assign('tab_selected', $tab_selected);
		
		// [TODO] check showTab* hooks for active_worker->is_superuser (no ajax bypass)
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/index.tpl');
	}
	
	// Ajax
	function showTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_ConfigTab) {
			$inst->showTab();
		}
	}
	
	// Post
	function saveTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_ConfigTab) {
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
			&& $inst instanceof Extension_ConfigTab) {
				if(method_exists($inst,$action.'Action')) {
					call_user_method($action.'Action',$inst);
				}
		}
	}
	
	// Ajax
	function showTabSettingsAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$license = CerberusLicense::getInstance();
		$tpl->assign('license', $license);
		
		$db = DevblocksPlatform::getDatabaseService();
		$rs = $db->Execute("SHOW TABLE STATUS");

		$total_db_size = 0;
		$total_db_data = 0;
		$total_db_indexes = 0;
		$total_db_slack = 0;
		$total_file_size = 0;
		
		// [TODO] This would likely be helpful to the /debug controller
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$table_name = $rs->fields['Name'];
			$table_size_data = intval($rs->fields['Data_length']);
			$table_size_indexes = intval($rs->fields['Index_length']);
			$table_size_slack = intval($rs->fields['Data_free']);
			
			$total_db_size += $table_size_data + $table_size_indexes;
			$total_db_data += $table_size_data;
			$total_db_indexes += $table_size_indexes;
			$total_db_slack += $table_size_slack;
			
			$rs->MoveNext();
		}
		
		$sql = "SELECT SUM(file_size) FROM attachment";
		$total_file_size = intval($db->GetOne($sql));

		$tpl->assign('total_db_size', number_format($total_db_size/1048576,2));
		$tpl->assign('total_db_data', number_format($total_db_data/1048576,2));
		$tpl->assign('total_db_indexes', number_format($total_db_indexes/1048576,2));
		$tpl->assign('total_db_slack', number_format($total_db_slack/1048576,2));
		$tpl->assign('total_file_size', number_format($total_file_size/1048576,2));
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/tabs/settings/index.tpl');
	}
	
	// Ajax
	function showTabAttachmentsAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$core_tplpath = realpath(dirname(__FILE__) . '/../cerberusweb.core/templates') . DIRECTORY_SEPARATOR;
		$tpl->assign('core_tplpath', $core_tplpath);
		
		$tpl->assign('response_uri', 'config/attachments');

		$view = C4_AbstractViewLoader::getView('C4_AttachmentView', C4_AttachmentView::DEFAULT_ID);
		$tpl->assign('view', $view);
		$tpl->assign('view_fields', C4_AttachmentView::getFields());
		$tpl->assign('view_searchable_fields', C4_AttachmentView::getSearchFields());
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/tabs/attachments/index.tpl');
	}
	
	function showAttachmentsBulkPanelAction() {
		@$id_csv = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$path = realpath(dirname(__FILE__) . '/templates/') . DIRECTORY_SEPARATOR;
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
		
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . $path . 'configuration/tabs/attachments/bulk.tpl');
	}
	
	function doAttachmentsBulkUpdateAction() {
		// Checked rows
	    @$ids_str = DevblocksPlatform::importGPC($_REQUEST['ids'],'string');
		$ids = DevblocksPlatform::parseCsvString($ids_str);

		// Filter: whole list or check
	    @$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
	    
	    // View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView('',$view_id);
		
		// Attachment fields
		@$deleted = trim(DevblocksPlatform::importGPC($_POST['deleted'],'integer',0));

		$do = array();
		
		// Do: Deleted
		if(0 != strlen($deleted))
			$do['deleted'] = $deleted;
			
		// Do: Custom fields
//		$do = DAO_CustomFieldValue::handleBulkPost($do);
			
		$view->doBulkUpdate($filter, $do, $ids);
		
		$view->render();
		return;
	}
		
//	function doAttachmentsSyncAction() {
//		if(null != ($active_worker = CerberusApplication::getActiveWorker()) 
//			&& $active_worker->is_superuser) {
//				
//			// Try to grab as many resources as we can
//			@ini_set('memory_limit','128M');
//			@set_time_limit(0);
//			
//			$db = DevblocksPlatform::getDatabaseService();
//			$attachment_path = APP_PATH . '/storage/attachments/';
//			
//			// Look up all our valid file ids
//			$sql = sprintf("SELECT id,filepath FROM attachment");
//			$rs = $db->Execute($sql);
//			
//			// Build a hash of valid ids
//			$valid_ids_set = array();
//			if(is_a($rs,'ADORecordSet'))
//			while(!$rs->EOF) {
//		        $valid_ids_set[intval($rs->fields['id'])] = $rs->fields['filepath'];
//		        $rs->MoveNext();
//			}
//			
//			$total_files_db = count($valid_ids_set);
//			
//			// Get all our attachment hash directories
//			$dir_handles = glob($attachment_path.'*',GLOB_ONLYDIR|GLOB_NOSORT);
//			
//			$orphans = 0;
//			$checked = 0;
//			
//			// Loop through all our hash directories and check that IDs are valid
//			if(!empty($dir_handles))
//			foreach($dir_handles as $dir) {
//		        $dirinfo = pathinfo($dir);
//		
//		        if(!is_numeric($dirinfo['basename']))
//		                continue;
//		
//		        if(false == ($dh = opendir($dir)))
//	                die("Couldn't open " . $dir);
//		
//		        while($file = readdir($dh)) {
//	                // Skip dirs and files we can't change
//	                if(is_dir($file))
//                        continue;
//	
//	                $info = pathinfo($file);
//	                $disk_file_id = $info['filename'];
//	
//	                // Only numeric filenames are valid
//	                if(!is_numeric($disk_file_id))
//                        continue;
//	
//	                if(!isset($valid_ids_set[$disk_file_id])) {
//                        $orphans++;
//
//                        //if(DO_DELETE_FILES)
//						unlink($dir . DIRECTORY_SEPARATOR . $file);
//	
//	                } else {
//                        unset($valid_ids_set[$disk_file_id]);
//	                }
//	                $checked++;
//		        }
//		        closedir($dh);
//			}
//			
//			$db_orphans = count($valid_ids_set);
//			
//	        foreach($valid_ids_set as $db_id => $null) {
//                $db->Execute(sprintf("DELETE FROM attachment WHERE id = %d", $db_id));
//	        }
//			
//			$tpl = DevblocksPlatform::getTemplateService();
//			$tpl->cache_lifetime = "0";
//			$tpl->assign('path', dirname(__FILE__) . '/templates/');
//	        
//	        $tpl->assign('checked', $checked);
//	        $tpl->assign('total_files_db', $total_files_db);
//	        $tpl->assign('orphans', $orphans);
//	        $tpl->assign('db_orphans', $db_orphans);
//	        
//	        $tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/tabs/attachments/cleanup_output.tpl');
//		}
//
//		exit;
//	}
	
	// Ajax
	function showTabWorkersAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$workers = DAO_Worker::getAllWithDisabled();
		$tpl->assign('workers', $workers);

		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		
		$tpl->assign('license',CerberusLicense::getInstance());
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/tabs/workers/index.tpl');
	}
	
	// Ajax
	function showTabGroupsAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);

		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		
		$tpl->assign('license',CerberusLicense::getInstance());
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/tabs/groups/index.tpl');
	}
	
	function showKbImportPanelAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/tabs/kb/import.tpl');
	}
	
//	function doKbImportXmlAction() {
//    	@$import_file = $_FILES['xml_file'];
//		$file = $import_file['tmp_name'];
//    	
//		if(empty($file))
//			return;
//		
//		$count = 0;
//			
//    	if(!empty($import_file)) {
//			$xml = simplexml_load_file($file); /* @var $xml_in SimpleXMLElement */
//			
//			foreach($xml->articles->article AS $article) {
//				$title = (string) $article->title;
//				$content = (string) $article->content;
//				$views = (integer) $article->views;
//
//				// [TODO] Import votes, etc.
//				$fields = array(
//						DAO_KbArticle::TITLE => $title,
//						DAO_KbArticle::CONTENT_RAW => $content,
//						DAO_KbArticle::CONTENT => $content,
//						DAO_KbArticle::FORMAT => 1,
//						DAO_KbArticle::VIEWS => $views,
//				);
//				$id = DAO_KbArticle::create($fields);
//				
//				$count++;
//			}
//    	}
//    	
//    	echo "Imported $count articles.<br>";
//    	
//    	DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','kb')));
//    	return;
//	}
	
	// Ajax
	function showTabKbAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$categories = DAO_KbCategory::getWhere(sprintf("%s = 0",
			DAO_KbCategory::PARENT_ID
		));
		$tpl->assign('categories', $categories);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/tabs/kb/index.tpl');
	}
	
	function getKBCategoryAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		if(!empty($id)) {
			@$category = DAO_KbCategory::get($id);
			$tpl->assign('category', $category);
		}
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/tabs/kb/edit_category.tpl');
		
		return;
	}
	
	function saveKbCategoryAction() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$worker = CerberusApplication::getActiveWorker();
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}

		if(DEMO_MODE) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','kb')));
			return;
		}		
		
		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer');
		@$category_name = DevblocksPlatform::importGPC($_POST['category_name'],'string','');
		@$delete = DevblocksPlatform::importGPC($_POST['delete_box'],'integer');

		if(empty($category_name))
			$category_name = "(".$translate->_('common.category').")";
		
		if(!empty($id) && !empty($delete)) {
			$ids = DAO_KbCategory::getDescendents($id);
			DAO_KbCategory::delete($ids);
			
		} elseif(!empty($id)) {
			$fields = array(
			    DAO_KbCategory::NAME => $category_name,
			);
			DAO_KbCategory::update($id, $fields);
			
		} else {
                $fields = array(
                DAO_KbCategory::NAME => $category_name,
			);
			$id = DAO_KbCategory::create($fields);
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','kb')));
		
		return;
	}
	
	// Ajax
	function showTabMailAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$settings = CerberusSettings::getInstance();
		$mail_service = DevblocksPlatform::getMailService();
		
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
		$smtp_max_sends = $settings->get(CerberusSettings::SMTP_MAX_SENDS,'20');
		
		$pop3_accounts = DAO_Mail::getPop3Accounts();
		$tpl->assign('pop3_accounts', $pop3_accounts);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/tabs/mail/index.tpl');
	}
	
	function getMailboxAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		if(!empty($id)) {
			@$pop3 = DAO_Mail::getPop3Account($id);
			$tpl->assign('pop3_account', $pop3);
		}
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/tabs/mail/edit_pop3_account.tpl');
		
		return;
	}
	
	function saveMailboxAction() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$worker = CerberusApplication::getActiveWorker();
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}
		
		if(DEMO_MODE) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','mail')));
			return;
		}
		
		@$id = DevblocksPlatform::importGPC($_POST['account_id'],'integer');
		@$enabled = DevblocksPlatform::importGPC($_POST['pop3_enabled'],'integer',0);
		@$nickname = DevblocksPlatform::importGPC($_POST['nickname'],'string');
		@$protocol = DevblocksPlatform::importGPC($_POST['protocol'],'string');
		@$host = DevblocksPlatform::importGPC($_POST['host'],'string');
		@$username = DevblocksPlatform::importGPC($_POST['username'],'string');
		@$password = DevblocksPlatform::importGPC($_POST['password'],'string');
		@$port = DevblocksPlatform::importGPC($_POST['port'],'integer');
		@$delete = DevblocksPlatform::importGPC($_POST['delete'],'integer');

		if(empty($nickname))
			$nickname = "POP3";
		
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
		
		if(!empty($id) && !empty($delete)) {
			DAO_Mail::deletePop3Account($id);
			
		} elseif(!empty($id)) {
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
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','mail')));
		
		return;
	}
	
	function getSmtpTestAction() {
		$translate = DevblocksPlatform::getTranslationService();
		
		@$host = DevblocksPlatform::importGPC($_REQUEST['host'],'string','');
		@$port = DevblocksPlatform::importGPC($_REQUEST['port'],'integer',25);
		@$enc = DevblocksPlatform::importGPC($_REQUEST['enc'],'string','');
		@$smtp_auth = DevblocksPlatform::importGPC($_REQUEST['smtp_auth'],'integer',0);
		@$smtp_user = DevblocksPlatform::importGPC($_REQUEST['smtp_user'],'string','');
		@$smtp_pass = DevblocksPlatform::importGPC($_REQUEST['smtp_pass'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		// [JAS]: Test the provided SMTP settings and give form feedback
		if(!empty($host)) {
			try {
				$mail_service = DevblocksPlatform::getMailService();
				$mailer = $mail_service->getMailer(array(
					'host' => $host,
					'port' => $port,
					'auth_user' => $smtp_user,
					'auth_pass' => $smtp_pass,
					'enc' => $enc,
				));
				
				$mailer->connect();
				$mailer->disconnect();
				$tpl->assign('smtp_test', true);
				
			} catch(Exception $e) {
				$tpl->assign('smtp_test', false);
				$tpl->assign('smtp_test_output', $translate->_('config.mail.smtp.failed') . ' ' . $e->getMessage());
			}
			
			$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/tabs/mail/test_smtp.tpl');			
		}
		
		return;
	}
	
	function getMailboxTestAction() {
		$translate = DevblocksPlatform::getTranslationService();
		
		@$protocol = DevblocksPlatform::importGPC($_REQUEST['protocol'],'string','');
		@$host = DevblocksPlatform::importGPC($_REQUEST['host'],'string','');
		@$port = DevblocksPlatform::importGPC($_REQUEST['port'],'integer',110);
		@$user = DevblocksPlatform::importGPC($_REQUEST['user'],'string','');
		@$pass = DevblocksPlatform::importGPC($_REQUEST['pass'],'string','');
		
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
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		// [JAS]: Test the provided POP settings and give form feedback
		if(!empty($host)) {
			$mail_service = DevblocksPlatform::getMailService();
			
			if(false !== $mail_service->testImap($host, $port, $protocol, $user, $pass)) {
				$tpl->assign('pop_test', true);
				
			} else {
				$tpl->assign('pop_test', false);
				$tpl->assign('pop_test_output', $translate->_('config.mail.pop3.failed'));
			}
			
		} else {
			$tpl->assign('pop_test, false');
			$tpl->assign('pop_test_output', $translate->_('config.mail.pop.error_hostname'));
		}
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/tabs/mail/test_pop.tpl');
		
		return;
	}
	
	// Ajax
	function showTabPreParserAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$filters = DAO_PreParseRule::getAll(true);
		$tpl->assign('filters', $filters);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/tabs/mail/mail_preparse.tpl');
	}

	function saveTabPreParseFiltersAction() {
		@$ids = DevblocksPlatform::importGPC($_POST['deletes'],'array',array());

		if(DEMO_MODE) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','preparser')));
			return;
		}
		
		DAO_PreParseRule::delete($ids);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','preparser')));
	}
	
	// Ajax
	function showPreParserPanelAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		if(null != ($filter = DAO_PreParseRule::get($id))) {
			$tpl->assign('filter', $filter);
		}
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/tabs/mail/preparser/peek.tpl');
	}
	
	// Post
	function saveTabPreParserAction() {
		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_POST['name'],'string','');
		@$rules = DevblocksPlatform::importGPC($_POST['rules'],'array',array());
		@$do = DevblocksPlatform::importGPC($_POST['do'],'array',array());
		
		if(DEMO_MODE) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','preparser')));
			return;
		}
		
		$criterion = array();
		$actions = array();
		
		// Criteria
		if(is_array($rules))
		foreach($rules as $rule) {
			$rule = DevblocksPlatform::strAlphaNumDash($rule);
			@$value = DevblocksPlatform::importGPC($_POST['value_'.$rule],'string','');
			
			// [JAS]: Allow empty $value (null/blank checking)
			
			$criteria = array(
				'value' => $value,
			);
			
			// Any special rule handling
			switch($rule) {
				case 'type':
					break;
				case 'from':
					break;
				case 'to':
					break;
				case 'header1':
				case 'header2':
				case 'header3':
				case 'header4':
				case 'header5':
					if(null != (@$header = DevblocksPlatform::importGPC($_POST[$rule],'string',null)))
						$criteria['header'] = strtolower($header);
					break;
				case 'body':
					break;
				case 'body_encoding':
					break;
				case 'attachment':
					break;
				default: // ignore invalids
					continue;
					break;
			}
			
			$criterion[$rule] = $criteria;
		}
		
		// Actions
		if(is_array($do))
		foreach($do as $act) {
			$action = array();
			
			switch($act) {
				case 'blackhole':
					$action = array();
					break;
				case 'redirect':
					if(null != (@$to = DevblocksPlatform::importGPC($_POST['do_redirect'],'string',null)))
						$action = array(
							'to' => $to
						);
					break;
				case 'bounce':
					if(null != (@$msg = DevblocksPlatform::importGPC($_POST['do_bounce'],'string',null)))
						$action = array(
							'message' => $msg
						);
					break;
				default: // ignore invalids
					continue;
					break;
			}
			
			$actions[$act] = $action;
		}
		
		if(!empty($criterion) && !empty($actions)) {
			if(empty($id))  {
				$fields = array(
					DAO_PreParseRule::NAME => $name,
					DAO_PreParseRule::CRITERIA_SER => serialize($criterion),
					DAO_PreParseRule::ACTIONS_SER => serialize($actions),
					DAO_PreParseRule::POS => 0,
				);
				$id = DAO_PreParseRule::create($fields);
			} else {
				$fields = array(
					DAO_PreParseRule::NAME => $name,
					DAO_PreParseRule::CRITERIA_SER => serialize($criterion),
					DAO_PreParseRule::ACTIONS_SER => serialize($actions),
				);
				DAO_PreParseRule::update($id, $fields);
			}
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','preparser')));
	}	
	
	// Ajax
	function showTabParserAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$routing = DAO_Mail::getMailboxRouting();
		$tpl->assign('routing', $routing);

		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/tabs/mail/mail_routing.tpl');
	}
	
	// Ajax
	function showTabFieldsAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		// Alphabetize
		$source_manifests = DevblocksPlatform::getExtensions('cerberusweb.fields.source', false);
		uasort($source_manifests, create_function('$a, $b', "return strcasecmp(\$a->name,\$b->name);\n"));
		$tpl->assign('source_manifests', $source_manifests);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/tabs/fields/index.tpl');
	}
	
	// Ajax
	function showTabFnrAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$topics = DAO_FnrTopic::getWhere();
		$tpl->assign('topics', $topics);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/tabs/fnr/index.tpl');
	}
	
	// Ajax
	function showTabPluginsAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		// Auto synchronize when viewing Config->Extensions
        DevblocksPlatform::readPlugins();
		
		$plugins = DevblocksPlatform::getPluginRegistry();
		unset($plugins['cerberusweb.core']);
		$tpl->assign('plugins', $plugins);
		
		$points = DevblocksPlatform::getExtensionPoints();
		$tpl->assign('points', $points);
		
		$license = CerberusLicense::getInstance();
		$tpl->assign('license', $license);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/tabs/plugins/index.tpl');
	}
	
	// Ajax
	function showTabSchedulerAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
	    $jobs = DevblocksPlatform::getExtensions('cerberusweb.cron', true);
		$tpl->assign('jobs', $jobs);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/tabs/scheduler/index.tpl');
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
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/tabs/fnr/topic_panel.tpl');
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
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/tabs/fnr/external_resource_panel.tpl');
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
	
	private function _getFieldSource($ext_id) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$tpl->assign('ext_id', $ext_id);

		// [TODO] Make sure the extension exists before continuing
		$source_manifest = DevblocksPlatform::getExtension($ext_id, false);
		$tpl->assign('source_manifest', $source_manifest);
		
		$types = Model_CustomField::getTypes();
		$tpl->assign('types', $types);

		// Look up the defined global fields by the given extension
		$fields = DAO_CustomField::getBySourceAndGroupId($ext_id, 0);
		$tpl->assign('fields', $fields);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/tabs/fields/edit_source.tpl');
	}
	
	// Ajax
	function getFieldSourceAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$worker = CerberusApplication::getActiveWorker();
		
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}
		
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id']);
		$this->_getFieldSource($ext_id);
	}
		
	// Post
	function saveFieldsAction() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$worker = CerberusApplication::getActiveWorker();
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}
		
		if(DEMO_MODE) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','fields')));
			return;
		}
		
		// Type of custom fields
		@$ext_id = DevblocksPlatform::importGPC($_POST['ext_id'],'string','');
		
		// Properties
		@$ids = DevblocksPlatform::importGPC($_POST['ids'],'array',array());
		@$names = DevblocksPlatform::importGPC($_POST['names'],'array',array());
		@$orders = DevblocksPlatform::importGPC($_POST['orders'],'array',array());
		@$options = DevblocksPlatform::importGPC($_POST['options'],'array',array());
		@$deletes = DevblocksPlatform::importGPC($_POST['deletes'],'array',array());
		
		if(!empty($ids) && !empty($ext_id))
		foreach($ids as $idx => $id) {
			@$name = $names[$idx];
			@$order = intval($orders[$idx]);
			@$option = $options[$idx];
			@$delete = (false !== array_search($id,$deletes) ? 1 : 0);
			
			if($delete) {
				DAO_CustomField::delete($id);
				
			} else {
				$fields = array(
					DAO_CustomField::NAME => $name, 
					DAO_CustomField::POS => $order, 
					DAO_CustomField::OPTIONS => !is_null($option) ? $option : '', 
				);
				DAO_CustomField::update($id, $fields);
			}
		}
		
		// Adding
		@$add_name = DevblocksPlatform::importGPC($_POST['add_name'],'string','');
		@$add_type = DevblocksPlatform::importGPC($_POST['add_type'],'string','');
		@$add_options = DevblocksPlatform::importGPC($_POST['add_options'],'string','');
		
		if(!empty($add_name) && !empty($add_type)) {
			$fields = array(
				DAO_CustomField::NAME => $add_name,
				DAO_CustomField::TYPE => $add_type,
				DAO_CustomField::GROUP_ID => 0,
				DAO_CustomField::SOURCE_EXTENSION => $ext_id,
				DAO_CustomField::OPTIONS => $add_options,
			);
			$id = DAO_CustomField::create($fields);
		}

		// Redraw the form
		$this->_getFieldSource($ext_id);
	}
	
	// Post
	function saveJobAction() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$worker = CerberusApplication::getActiveWorker();
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}
		
		if(DEMO_MODE) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','scheduler')));
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
	        die($translate->_('common.access_denied'));
	    
	    // [TODO] This is really kludgey
	    $job->setParam(CerberusCronPageExtension::PARAM_ENABLED, $enabled);
	    $job->setParam(CerberusCronPageExtension::PARAM_LOCKED, $locked);
	    $job->setParam(CerberusCronPageExtension::PARAM_DURATION, $duration);
	    $job->setParam(CerberusCronPageExtension::PARAM_TERM, $term);
	    
	    $job->saveConfigurationAction();
	    	    
	    DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','scheduler')));
	}
	
	// Post
	function saveLicensesAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$settings = CerberusSettings::getInstance();
		$worker = CerberusApplication::getActiveWorker();
		
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}
		
		@$key = DevblocksPlatform::importGPC($_POST['key'],'string','');
		@$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'],'integer',0);

		if(DEMO_MODE) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','settings')));
			return;
		}

		if(!empty($do_delete)) {
			$settings->set('company', '');
			$settings->set(CerberusSettings::LICENSE, '');
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','settings')));
			return;
		}
		
		if(empty($key)) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','settings','empty')));
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
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','settings','invalid')));
			return;
		}
		
		// Save for reuse in form in case we need to redraw on error
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
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','settings')));
	}
	
	// Ajax
	function getWorkerAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$worker = CerberusApplication::getActiveWorker();
		
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
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
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/tabs/workers/edit_worker.tpl');
	}
	
	// Post
	function saveWorkerAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}
		
		if(DEMO_MODE) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','workers')));
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
		@$group_ids = DevblocksPlatform::importGPC($_POST['group_ids'],'array');
		@$group_roles = DevblocksPlatform::importGPC($_POST['group_roles'],'array');
		@$disabled = DevblocksPlatform::importGPC($_POST['do_disable'],'integer',0);
		@$delete = DevblocksPlatform::importGPC($_POST['do_delete'],'integer',0);

		// Global privs
		@$can_export = DevblocksPlatform::importGPC($_POST['can_export'],'integer',0);
		@$can_delete = DevblocksPlatform::importGPC($_POST['can_delete'],'integer');
		
		// [TODO] The superuser set bit here needs to be protected by ACL
		
		if(empty($first_name)) $first_name = "Anonymous";
		
		if(!empty($id) && !empty($delete)) {
			// Can't delete or disable self
			if($active_worker->id == $id)
				return;
			
			DAO_Worker::deleteAgent($id);
			
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
					        $mailer = $mail_service->getMailer(CerberusMail::getMailerDefaults());
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
					        
						    $mail->attach(new Swift_Message_Part($body, 'text/plain', 'base64', LANG_CHARSET_CODE));
	
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
					DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','workers')));
					return;
				}
			}
		    
			$fields = array(
				DAO_Worker::FIRST_NAME => $first_name,
				DAO_Worker::LAST_NAME => $last_name,
				DAO_Worker::TITLE => $title,
				DAO_Worker::EMAIL => $email,
				DAO_Worker::IS_SUPERUSER => $is_superuser,
				DAO_Worker::IS_DISABLED => $disabled,
				DAO_Worker::CAN_EXPORT => $can_export,
				DAO_Worker::CAN_DELETE => $can_delete,
			);
			
			// if we're resetting the password
			if(!empty($password)) {
				$fields[DAO_Worker::PASSWORD] = md5($password);
			}
			
			// Update worker
			DAO_Worker::updateAgent($id, $fields);
			
			// Update group memberships
			if(is_array($group_ids) && is_array($group_roles))
			foreach($group_ids as $idx => $group_id) {
				if(empty($group_roles[$idx])) {
					DAO_Group::unsetTeamMember($group_id, $id);
				} else {
					DAO_Group::setTeamMember($group_id, $id, (2==$group_roles[$idx]));
				}
			}

			// Add the worker e-mail to the addresses table
			if(!empty($email))
				DAO_Address::lookupAddress($email, true);
			
			// Addresses
			if(null == DAO_AddressToWorker::getByAddress($email)) {
				DAO_AddressToWorker::assign($email, $id);
				DAO_AddressToWorker::update($email, array(
					DAO_AddressToWorker::IS_CONFIRMED => 1
				));
			}
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','workers')));
	}
	
	// Ajax
	function getTeamAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$worker = CerberusApplication::getActiveWorker();
		
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
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
		
		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
		
		$tpl->assign('license',CerberusLicense::getInstance());
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/tabs/groups/edit_group.tpl');
	}
	
	// Post
	function saveTeamAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$worker = CerberusApplication::getActiveWorker();
		
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}
		
		if(DEMO_MODE) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','workflow')));
			return;
		}
		
		@$id = DevblocksPlatform::importGPC($_POST['id']);
		@$name = DevblocksPlatform::importGPC($_POST['name']);
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
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','groups')));
	}
	
	// Post
	function saveSettingsAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$worker = CerberusApplication::getActiveWorker();
		
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}
		
		if(DEMO_MODE) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','settings')));
			return;
		}
		
	    @$title = DevblocksPlatform::importGPC($_POST['title'],'string','');
	    @$logo = DevblocksPlatform::importGPC($_POST['logo'],'string');
	    @$authorized_ips_str = DevblocksPlatform::importGPC($_POST['authorized_ips'],'string','');

	    if(empty($title))
	    	$title = 'Cerberus Helpdesk :: Team-based E-mail Management';
	    
	    $settings = CerberusSettings::getInstance();
	    $settings->set(CerberusSettings::HELPDESK_TITLE, $title);
	    $settings->set(CerberusSettings::HELPDESK_LOGO_URL, $logo); // [TODO] Enforce some kind of max resolution?
	    $settings->set(CerberusSettings::AUTHORIZED_IPS, $authorized_ips_str);
	    
	    DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','settings')));
	}
	
	function saveIncomingMailSettingsAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$worker = CerberusApplication::getActiveWorker();
		
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
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
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','mail')));
	}
	
	// Form Submit
	function saveOutgoingMailSettingsAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$worker = CerberusApplication::getActiveWorker();
		
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
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
	    @$smtp_timeout = DevblocksPlatform::importGPC($_REQUEST['smtp_timeout'],'integer',30);
	    @$smtp_max_sends = DevblocksPlatform::importGPC($_REQUEST['smtp_max_sends'],'integer',20);

	    @$smtp_auth_enabled = DevblocksPlatform::importGPC($_REQUEST['smtp_auth_enabled'],'integer', 0);
	    if($smtp_auth_enabled) {
		    @$smtp_auth_user = DevblocksPlatform::importGPC($_REQUEST['smtp_auth_user'],'string');
		    @$smtp_auth_pass = DevblocksPlatform::importGPC($_REQUEST['smtp_auth_pass'],'string');
	    	@$smtp_enc = DevblocksPlatform::importGPC($_REQUEST['smtp_enc'],'string','None');
	    } else { // need to clear auth info when smtp auth is disabled
		    @$smtp_auth_user = '';
		    @$smtp_auth_pass = '';
	    	@$smtp_enc = 'None';
	    }
	    
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
	    $settings->set(CerberusSettings::SMTP_TIMEOUT, !empty($smtp_timeout) ? $smtp_timeout : 30);
	    $settings->set(CerberusSettings::SMTP_MAX_SENDS, !empty($smtp_max_sends) ? $smtp_max_sends : 20);
	    
	    DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','mail','outgoing','test')));
	}
	
	// Ajax
	function ajaxGetRoutingAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$worker = CerberusApplication::getActiveWorker();
		
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$routing = DAO_Mail::getMailboxRouting();
		$tpl->assign('routing', $routing);

		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/tabs/mail/mail_routing.tpl');
	}
	
	// Form Submit
	function saveRoutingAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$worker = CerberusApplication::getActiveWorker();
		
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
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
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','parser')));
	}
	
	// Ajax
	function ajaxDeleteRoutingAction() {
		$translate = DevblocksPlatform::getTranslationService();
		
		if(DEMO_MODE) {
			return;
		}
		
		$worker = CerberusApplication::getActiveWorker();
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		DAO_Mail::deleteMailboxRouting($id);
	}
	
	// Ajax
	function getMailRoutingAddAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$worker = CerberusApplication::getActiveWorker();
		
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$teams = DAO_Group::getTeams();
		$tpl->assign('teams', $teams);

		$tpl->display('file:' . dirname(__FILE__) . '/templates/configuration/tabs/mail/mail_routing_add.tpl');
	}
	
	function savePluginsAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$worker = CerberusApplication::getActiveWorker();
		
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}
		
		if(DEMO_MODE) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','plugins')));
			return;
		}
		
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
		
        // Reload plugin translations
		DAO_Translation::reloadPluginStrings();
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','plugins')));
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

		$tpl->display('file:' . dirname(__FILE__) . '/templates/welcome/index.tpl');
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
		
	function getActivity() {
		return new Model_Activity('activity.address_book');
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
	
	function browseOrgsAction() {
		$visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;
		
		array_shift($stack); // contacts
		array_shift($stack); // browseOrgs
		
		@$id = array_shift($stack);
		
		$org = DAO_ContactOrg::get($id);
//		$opp = DAO_CrmOpportunity::get($id);
	
		if(empty($org)) {
			echo "<H1>Invalid Organization ID.</H1>";
			return;
		}
		
		// Display series support (inherited paging from Display)
		@$view_id = array_shift($stack);
		if(!empty($view_id)) {
			$view = C4_AbstractViewLoader::getView('',$view_id);

			// Restrict to the active worker's groups
			$active_worker = CerberusApplication::getActiveWorker();
//			$memberships = $active_worker->getMemberships();
//			$view->params['tmp'] = new DevblocksSearchCriteria(SearchFields_CrmOpportunity::TEAM_ID, 'in', array_keys($memberships)); 
			
			$range = 500;
			$pos = $view->renderLimit * $view->renderPage;
			$page = floor($pos / $range);
			
			list($series, $series_count) = DAO_ContactOrg::search(
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
			
			$visit->set('ch_org_series', $series_info);
		}
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('contacts','orgs','display',$org->id)));
		exit;
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
						
						$tpl->display('file:' . dirname(__FILE__) . '/templates/contacts/import/mapping.tpl');
						break;
					default:
						$tpl->display('file:' . dirname(__FILE__) . '/templates/contacts/import/index.tpl');
						break;
				}
				break;

			case 'addresses':
				$view = C4_AbstractViewLoader::getView('C4_AddressView', C4_AddressView::DEFAULT_ID);
				$tpl->assign('view', $view);
				$tpl->assign('contacts_page', 'addresses');
				$tpl->assign('view_fields', C4_AddressView::getFields());
				$tpl->assign('view_searchable_fields', C4_AddressView::getSearchFields());
				$tpl->display('file:' . dirname(__FILE__) . '/templates/contacts/addresses/index.tpl');
				break;
				
//			case 'people':
//				$view = C4_AbstractViewLoader::getView('', 'addybook_people'); // C4_AddressView::DEFAULT_ID
//				
//				if(null == $view) {
//					$view = new C4_AddressView();
//					$view->id = 'addybook_people';
//					$view->name = 'People';
//					$view->params = array(
//						new DevblocksSearchCriteria(SearchFields_Address::CONTACT_ORG_ID,'!=',0),
//					);
//					
//					C4_AbstractViewLoader::setView('addybook_people', $view);
//				}
//				
//				$tpl->assign('view', $view);
//				$tpl->assign('contacts_page', 'people');
//				$tpl->assign('view_fields', C4_AddressView::getFields());
//				$tpl->assign('view_searchable_fields', C4_AddressView::getSearchFields());
//				$tpl->display('file:' . dirname(__FILE__) . '/templates/contacts/people/index.tpl');
//				break;
				
			default:
			case 'orgs':
				$param = array_shift($stack);

				switch($param) {
					case 'display':
						$tab_manifests = DevblocksPlatform::getExtensions('cerberusweb.org.tab', false);
						$tpl->assign('tab_manifests', $tab_manifests);
						
						$id = array_shift($stack);
						
						$contact = DAO_ContactOrg::get($id);
						$tpl->assign('contact', $contact);
						
						$task_count = DAO_Task::getCountBySourceObjectId('cerberusweb.tasks.org', $contact->id);
						$tpl->assign('tasks_total', $task_count);
						
						$people_count = DAO_Address::getCountByOrgId($contact->id);
						$tpl->assign('people_total', $people_count);
						
						// Does a series exist?
						// [TODO] This is highly redundant
						if(null != ($series_info = $visit->get('ch_org_series', null))) {
							@$series = $series_info['series'];
							// Is this ID part of the series?  If not, invalidate
							if(!isset($series[$contact->id])) {
								$visit->set('ch_org_series', null);
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
									if(intval($pos)==intval($contact->id)) {
										$series_stats['cur'] = $cur;
										if(false !== prev($series)) {
											@$series_stats['prev'] = $series[key($series)][SearchFields_ContactOrg::ID];
											next($series); // skip to current
										} else {
											reset($series);
										}
										next($series); // next
										@$series_stats['next'] = $series[key($series)][SearchFields_ContactOrg::ID];
										break;
									}
									next($series);
									$cur++;
								}
								
								$tpl->assign('series_stats', $series_stats);
							}
						}
						
						$tpl->display('file:' . dirname(__FILE__) . '/templates/contacts/orgs/display.tpl');
						break;
						
					default: // index
						$view = C4_AbstractViewLoader::getView('C4_ContactOrgView', C4_ContactOrgView::DEFAULT_ID);
						$tpl->assign('view', $view);
						$tpl->assign('contacts_page', 'orgs');
						$tpl->assign('view_fields', C4_ContactOrgView::getFields());
						$tpl->assign('view_searchable_fields', C4_ContactOrgView::getSearchFields());
						$tpl->display('file:' . dirname(__FILE__) . '/templates/contacts/orgs/index.tpl');
						break;
				}
		}	
	}
	
	// Post
	function parseUploadAction() {
		@$type = DevblocksPlatform::importGPC($_REQUEST['type'],'string','');
		$csv_file = $_FILES['csv_file'];

		if(empty($type) || !is_array($csv_file) || !isset($csv_file['tmp_name']) || empty($csv_file['tmp_name'])) {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('contacts','import')));
			return;
		}
		
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
	// [TODO] Allow XML also?
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
			&& $inst instanceof Extension_OrgTab) {
			$inst->showTab();
		}
	}
	
	function showTabPropertiesAction() {
		@$org = DevblocksPlatform::importGPC($_REQUEST['org']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$tpl->assign('org_id', $org);
		
		$contact = DAO_ContactOrg::get($org);
		$tpl->assign('contact', $contact);

		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Org::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		$custom_field_values = DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Org::ID, $org);
		if(isset($custom_field_values[$org]))
			$tpl->assign('custom_field_values', $custom_field_values[$org]);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/contacts/orgs/tabs/properties.tpl');
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
		$view->name = 'Contacts: ' . $contact->name;
		$view->view_columns = array(
			SearchFields_Address::FIRST_NAME,
			SearchFields_Address::LAST_NAME,
			SearchFields_Address::NUM_NONSPAM,
		);
		$view->params = array(
			new DevblocksSearchCriteria(SearchFields_Address::CONTACT_ORG_ID,'=',$org)
		);
		$tpl->assign('view', $view);
		
		C4_AbstractViewLoader::setView($view->id, $view);
		
		$tpl->assign('contacts_page', 'orgs');
		$tpl->assign('search_columns', SearchFields_Address::getFields());
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/contacts/orgs/tabs/people.tpl');
		exit;
	}
	
	function showTabTasksAction() {
		$translate = DevblocksPlatform::getTranslationService();
		@$org = DevblocksPlatform::importGPC($_REQUEST['org']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$contact = DAO_ContactOrg::get($org);
		$tpl->assign('contact', $contact);
		
		$view = C4_AbstractViewLoader::getView('C4_TaskView', 'org_tasks');
		$view->id = 'org_tasks';
		$view->name = $translate->_('common.tasks') . ' ' . $contact->name;
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
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/contacts/orgs/tabs/tasks.tpl');
		exit;
	}
	
	function showTabNotesAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		@$org_id = DevblocksPlatform::importGPC($_REQUEST['org']);
		
		$org = DAO_ContactOrg::get($org_id);
		$tpl->assign('org', $org);
		
		list($notes, $null) = DAO_Note::search(
			array(
				new DevblocksSearchCriteria(SearchFields_Note::SOURCE_EXT_ID,'=',ChNotesSource_Org::ID),
				new DevblocksSearchCriteria(SearchFields_Note::SOURCE_ID,'=',$org->id),
			),
			25,
			0,
			DAO_Note::CREATED,
			false,
			false
		);

		$tpl->assign('notes', $notes);
		
		$active_workers = DAO_Worker::getAllActive();
		$tpl->assign('active_workers', $active_workers);

		$workers = DAO_Worker::getAllWithDisabled();
		$tpl->assign('workers', $workers);

		$tpl->display('file:' . dirname(__FILE__) . '/templates/contacts/orgs/tabs/notes.tpl');
	}
	
	function showTabHistoryAction() {
		$translate = DevblocksPlatform::getTranslationService();
		
		@$org = DevblocksPlatform::importGPC($_REQUEST['org']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$contact = DAO_ContactOrg::get($org);
		$tpl->assign('contact', $contact);
		
		$visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */

		$tickets_view = C4_AbstractViewLoader::getView('','contact_history');
		
		// All org contacts
		$people = DAO_Address::getWhere(sprintf("%s = %d",
			DAO_Address::CONTACT_ORG_ID,
			$contact->id
		));
		
		if(null == $tickets_view) {
			$tickets_view = new C4_TicketView();
			$tickets_view->id = 'contact_history';
			$tickets_view->name = $translate->_('addy_book.history.view_title');
			$tickets_view->view_columns = array(
				SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
				SearchFields_Ticket::TICKET_CREATED_DATE,
				SearchFields_Ticket::TEAM_NAME,
				SearchFields_Ticket::TICKET_CATEGORY_ID,
				SearchFields_Ticket::TICKET_NEXT_ACTION,
			);
			$tickets_view->params = array(
			);
			$tickets_view->renderLimit = 10;
			$tickets_view->renderPage = 0;
			$tickets_view->renderSortBy = SearchFields_Ticket::TICKET_CREATED_DATE;
			$tickets_view->renderSortAsc = false;
		}

		@$tickets_view->name = $translate->_('ticket.requesters') . ": " . htmlspecialchars($contact->name) . ' - ' . intval(count($people)) . ' contact(s)';
		$tickets_view->params = array(
			SearchFields_Ticket::REQUESTER_ID => new DevblocksSearchCriteria(SearchFields_Ticket::REQUESTER_ID,'in',array_keys($people)),
			SearchFields_Ticket::TICKET_DELETED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_DELETED,DevblocksSearchCriteria::OPER_EQ,0)
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
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/contacts/orgs/tabs/history.tpl');
		exit;
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
		
		if(!empty($address_id)) {
			$email = '';
			if(null != ($addy = DAO_Address::get($address_id))) {
				@$email = $addy->email;
			}
		}
		$tpl->assign('email', $email);
		
		if(!empty($email)) {
			list($addresses,$null) = DAO_Address::search(
				array(),
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
					new DevblocksSearchCriteria(SearchFields_Ticket::REQUESTER_ID,'=',$address[SearchFields_Address::ID]),
				),
				1
			);
			$tpl->assign('open_count', $open_count);
			
			list($closed_tickets, $closed_count) = DAO_Ticket::search(
				array(),
				array(
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',1),
					new DevblocksSearchCriteria(SearchFields_Ticket::REQUESTER_ID,'=',$address[SearchFields_Address::ID]),
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
		
		// Custom fields
		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Address::ID); 
		$tpl->assign('custom_fields', $custom_fields);

		$custom_field_values = DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Address::ID, $id);
		if(isset($custom_field_values[$id]))
			$tpl->assign('custom_field_values', $custom_field_values[$id]);
		
		$types = Model_CustomField::getTypes();
		$tpl->assign('types', $types);
		
		// Display
		$tpl->assign('id', $id);
		$tpl->assign('view_id', $view_id);
		$tpl->display('file:' . dirname(__FILE__) . '/templates/contacts/addresses/address_peek.tpl');
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
			SearchFields_Ticket::REQUESTER_ADDRESS => new DevblocksSearchCriteria(SearchFields_Ticket::REQUESTER_ADDRESS,'=',$address->email),
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
	    }
		
	    $custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Address::ID);
	    $tpl->assign('custom_fields', $custom_fields);
	    
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/contacts/addresses/address_bulk.tpl');
	}
	
	function showOrgBulkPanelAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->assign('view_id', $view_id);

	    if(!empty($ids)) {
	        $org_ids = DevblocksPlatform::parseCsvString($ids);
	        $tpl->assign('org_ids', implode(',', $org_ids));
	    }
		
		// Custom Fields
		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Org::ID);
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/contacts/orgs/org_bulk.tpl');
	}
		
	function showOrgPeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer','');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$contact = DAO_ContactOrg::get($id);
		$tpl->assign('contact', $contact);

		// Custom fields
		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Org::ID); 
		$tpl->assign('custom_fields', $custom_fields);

		$custom_field_values = DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Org::ID, $id);
		if(isset($custom_field_values[$id]))
			$tpl->assign('custom_field_values', $custom_field_values[$id]);
		
		$types = Model_CustomField::getTypes();
		$tpl->assign('types', $types);
				
		// View
		$tpl->assign('view_id', $view_id);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/contacts/orgs/org_peek.tpl');
	}
	
	function saveContactAction() {
		$db = DevblocksPlatform::getDatabaseService();
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		@$email = trim(DevblocksPlatform::importGPC($_REQUEST['email'],'string',''));
		@$first_name = trim(DevblocksPlatform::importGPC($_REQUEST['first_name'],'string',''));
		@$last_name = trim(DevblocksPlatform::importGPC($_REQUEST['last_name'],'string',''));
		@$contact_org = trim(DevblocksPlatform::importGPC($_REQUEST['contact_org'],'string',''));
		@$is_banned = DevblocksPlatform::importGPC($_REQUEST['is_banned'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string', '');
		
		$contact_org_id = 0;
		
		if(!empty($contact_org)) {
			$contact_org_id = DAO_ContactOrg::lookup($contact_org, true);
			$contact_org = DAO_ContactOrg::get($contact_org_id);
		}
		
		$fields = array(
			DAO_Address::FIRST_NAME => $first_name,
			DAO_Address::LAST_NAME => $last_name,
			DAO_Address::CONTACT_ORG_ID => $contact_org_id,
			DAO_Address::IS_BANNED => $is_banned,
		);
		
		if($id==0) {
			$fields = $fields + array(DAO_Address::EMAIL => $email);
			$id = DAO_Address::create($fields);
		}
		else {
			DAO_Address::update($id, $fields);
		}

		// Custom field saves
		@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
		DAO_CustomFieldValue::handleFormPost(ChCustomFieldSource_Address::ID, $id, $field_ids);
		
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
		
		if(!empty($view_id)) {
			$view = C4_AbstractViewLoader::getView('', $view_id);
			$view->render();
		}
	}
	
	function saveOrgPropertiesAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['org_id'],'integer');
		
		@$org_name = DevblocksPlatform::importGPC($_REQUEST['org_name'],'string','');
		@$street = DevblocksPlatform::importGPC($_REQUEST['street'],'string','');
		@$city = DevblocksPlatform::importGPC($_REQUEST['city'],'string','');
		@$province = DevblocksPlatform::importGPC($_REQUEST['province'],'string','');
		@$postal = DevblocksPlatform::importGPC($_REQUEST['postal'],'string','');
		@$country = DevblocksPlatform::importGPC($_REQUEST['country'],'string','');
		@$phone = DevblocksPlatform::importGPC($_REQUEST['phone'],'string','');
		@$fax = DevblocksPlatform::importGPC($_REQUEST['fax'],'string','');
		@$website = DevblocksPlatform::importGPC($_REQUEST['website'],'string','');
		@$delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);

		if(!empty($id) && !empty($delete)) { // delete
			DAO_ContactOrg::delete($id);
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('contacts','orgs')));
			return;
			
		} else { // create/edit
			$fields = array(
				DAO_ContactOrg::NAME => $org_name,
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
			
			// Custom field saves
			@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
			DAO_CustomFieldValue::handleFormPost(ChCustomFieldSource_Org::ID, $id, $field_ids);
		}		
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('contacts','orgs','display',$id))); //,'fields'
	}	
	
	function saveOrgNoteAction() {
		@$org_id = DevblocksPlatform::importGPC($_REQUEST['org_id'],'integer', 0);
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!empty($org_id) && 0 != strlen(trim($content))) {
			$fields = array(
				DAO_Note::SOURCE_EXTENSION_ID => ChNotesSource_Org::ID,
				DAO_Note::SOURCE_ID => $org_id,
				DAO_Note::WORKER_ID => $active_worker->id,
				DAO_Note::CREATED => time(),
				DAO_Note::CONTENT => $content,
			);
			$note_id = DAO_Note::create($fields);
		}
		
		$org = DAO_ContactOrg::get($org_id);
		
		// Worker notifications
		$url_writer = DevblocksPlatform::getUrlService();
		@$notify_worker_ids = DevblocksPlatform::importGPC($_REQUEST['notify_worker_ids'],'array',array());
		if(is_array($notify_worker_ids) && !empty($notify_worker_ids))
		foreach($notify_worker_ids as $notify_worker_id) {
			$fields = array(
				DAO_WorkerEvent::CREATED_DATE => time(),
				DAO_WorkerEvent::WORKER_ID => $notify_worker_id,
				DAO_WorkerEvent::URL => $url_writer->write('c=contacts&a=orgs&d=display&id='.$org_id,true),
				DAO_WorkerEvent::TITLE => 'New Organization Note', // [TODO] Translate
				DAO_WorkerEvent::CONTENT => sprintf("%s\n%s notes: %s", $org->name, $active_worker->getName(), $content), // [TODO] Translate
				DAO_WorkerEvent::IS_READ => 0,
			);
			DAO_WorkerEvent::create($fields);
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('contacts','orgs','display',$org_id)));
	}
	
	// [TODO] This is redundant and should be handled by ?c=internal by passing a $return_path
	function deleteOrgNoteAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		@$org_id = DevblocksPlatform::importGPC($_REQUEST['org_id'],'integer', 0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(null != ($note = DAO_Note::get($id))) {
			if($note->worker_id == $active_worker->id || $active_worker->is_superuser) {
				DAO_Note::delete($id);
			}
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('contacts','orgs','display',$org_id)));
	}
	
	function saveOrgPeekAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		@$org_name = DevblocksPlatform::importGPC($_REQUEST['org_name'],'string','');
		@$street = DevblocksPlatform::importGPC($_REQUEST['street'],'string','');
		@$city = DevblocksPlatform::importGPC($_REQUEST['city'],'string','');
		@$province = DevblocksPlatform::importGPC($_REQUEST['province'],'string','');
		@$postal = DevblocksPlatform::importGPC($_REQUEST['postal'],'string','');
		@$country = DevblocksPlatform::importGPC($_REQUEST['country'],'string','');
		@$phone = DevblocksPlatform::importGPC($_REQUEST['phone'],'string','');
		@$fax = DevblocksPlatform::importGPC($_REQUEST['fax'],'string','');
		@$website = DevblocksPlatform::importGPC($_REQUEST['website'],'string','');
		@$delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);

		if(!empty($id) && !empty($delete)) { // delete
			DAO_ContactOrg::delete($id);
			
		} else { // create/edit
			$fields = array(
				DAO_ContactOrg::NAME => $org_name,
				DAO_ContactOrg::STREET => $street,
				DAO_ContactOrg::CITY => $city,
				DAO_ContactOrg::PROVINCE => $province,
				DAO_ContactOrg::POSTAL => $postal,
				DAO_ContactOrg::COUNTRY => $country,
				DAO_ContactOrg::PHONE => $phone,
				DAO_ContactOrg::FAX => $fax,
				DAO_ContactOrg::WEBSITE => $website,
			);
	
			if($id==0) {
				$id = DAO_ContactOrg::create($fields);
			}
			else {
				DAO_ContactOrg::update($id, $fields);	
			}
			
			// Custom field saves
			@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
			DAO_CustomFieldValue::handleFormPost(ChCustomFieldSource_Org::ID, $id, $field_ids);
		}
		
		$view = C4_AbstractViewLoader::getView('', $view_id);
		$view->render();		
	}
	
	function doAddressBatchUpdateAction() {
	    @$address_id_str = DevblocksPlatform::importGPC($_REQUEST['address_ids'],'string');

	    @$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
	    
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView('',$view_id);

		@$org_name = trim(DevblocksPlatform::importGPC($_POST['contact_org'],'string',''));
		@$sla = DevblocksPlatform::importGPC($_POST['sla'],'string','');
		@$is_banned = DevblocksPlatform::importGPC($_POST['is_banned'],'integer',0);

		$address_ids = DevblocksPlatform::parseCsvString($address_id_str);
		
		$do = array();
		
		// Do: Organization
		if(!empty($org_name)) {
			if(null != ($org_id = DAO_ContactOrg::lookup($org_name, true)))
				$do['org_id'] = $org_id;
		}
		// Do: SLA
		if('' != $sla)
			$do['sla'] = $sla;
		// Do: Banned
		if(0 != strlen($is_banned))
			$do['banned'] = $is_banned;
		
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);
			
		$view->doBulkUpdate($filter, $do, $address_ids);
		
		$view->render();
		return;
	}

	function doOrgBulkUpdateAction() {
		// Checked rows
	    @$org_ids_str = DevblocksPlatform::importGPC($_REQUEST['org_ids'],'string');
		$org_ids = DevblocksPlatform::parseCsvString($org_ids_str);

		// Filter: whole list or check
	    @$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
	    
	    // View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView('',$view_id);
		
		// Org fields
		@$country = trim(DevblocksPlatform::importGPC($_POST['country'],'string',''));

		$do = array();
		
		// Do: Country
		if(0 != strlen($country))
			$do['country'] = $country;
			
		// Do: Custom fields
		$do = DAO_CustomFieldValue::handleBulkPost($do);
			
		$view->doBulkUpdate($filter, $do, $org_ids);
		
		$view->render();
		return;
	}
	
	function doAddressQuickSearchAction() {
        @$type = DevblocksPlatform::importGPC($_POST['type'],'string'); 
        @$query = DevblocksPlatform::importGPC($_POST['query'],'string');

        $query = trim($query);
        
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

        $query = trim($query);
        
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
			array(),
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
		exit;
	}
	
	function getEmailAutoCompletionsAction() {
		$db = DevblocksPlatform::getDatabaseService();
		@$query = DevblocksPlatform::importGPC($_REQUEST['query'],'string','');
		
		$starts_with = strtolower($query) . '%';
		
		$sql = sprintf("SELECT first_name,last_name,email ".
			"FROM address ".
			"WHERE lower(email) LIKE %s ".
			"OR lower(%s) LIKE %s ".
			"OR lower(last_name) LIKE %s ".
			"ORDER BY first_name, last_name, email",
			$db->qstr($starts_with),
			$db->Concat('first_name',"' '",'last_name'),
			$db->qstr($starts_with),
			$db->qstr($starts_with)
		);
		$rs = $db->Execute($sql);
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$first = $rs->fields['first_name'];
			$last = $rs->fields['last_name'];
			$email = $rs->fields['email'];
			
			$personal = sprintf("%s%s%s",
				(!empty($first)) ? $first : '',
				(!empty($first) && !empty($last)) ? ' ' : '',
				(!empty($last)) ? $last : ''
			);
			
			echo sprintf("%s\t%s%s\n",
				$email,
				!empty($personal) ? ('"'.$personal.'" ') : '',
				!empty($personal) ? ("<".$email.">") : $email
			);
			
			$rs->MoveNext();
		}
		
		exit;
	}
	
	function getCountryAutoCompletionsAction() {
		@$starts_with = DevblocksPlatform::importGPC($_REQUEST['query'],'string','');
		
		$db = DevblocksPlatform::getDatabaseService();
		
		// [TODO] Possibly internalize this exposed query.
		$sql = sprintf("SELECT DISTINCT country FROM contact_org WHERE country LIKE '%s%%' ORDER BY country",
			$starts_with
		);
		$rs = $db->Execute($sql);
		
		if(is_a($rs,'ADORecordSet'))
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
		$translate = DevblocksPlatform::getTranslationService();
		
		$stack = $request->path;				// URLS like: /files/10000/plaintext.txt
		array_shift($stack);					// files	
		$file_id = array_shift($stack); 		// 10000
		$file_name = array_shift($stack); 		// plaintext.txt

		// Security
		if(null == ($active_worker = CerberusApplication::getActiveWorker()))
			die($translate->_('common.access_denied'));
		
		if(empty($file_id) || empty($file_name) || null == ($file = DAO_Attachment::get($file_id)))
			die($translate->_('files.not_found'));
			
		// Security
			$message = DAO_Ticket::getMessage($file->message_id);
		if(null == ($ticket = DAO_Ticket::getTicket($message->ticket_id)))
			die($translate->_('common.access_denied'));
			
		// Security
		$active_worker_memberships = $active_worker->getMemberships();
		if(null == ($active_worker_memberships[$ticket->team_id]))
			die($translate->_('common.access_denied'));
			
		// Set headers
		header("Expires: Mon, 26 Nov 1962 00:00:00 GMT\n");
		header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT\n");
		header("Cache-control: private\n");
		header("Pragma: no-cache\n");
		header("Content-Type: " . $file->mime_type . "\n");
		header("Content-transfer-encoding: binary\n"); 
		header("Content-Length: " . $file->getFileSize() . "\n");
		
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
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		$command = array_shift($stack); // groups
		
    	$groups = DAO_Group::getAll();
    	$tpl->assign('groups', $groups);
    	
    	@$team_id = array_shift($stack); // team_id

		// Only group managers and superusers can configure
		if(empty($team_id) || (!$active_worker->isTeamManager($team_id) && !$active_worker->is_superuser)) {
			// do nothing (only show list)
			
		} else {
			$teams = DAO_Group::getAll();
			
			$team =& $teams[$team_id];
	    	$tpl->assign('team', $team);
	    	
    		@$tab_selected = array_shift($stack); // tab
	    	if(!empty($tab_selected))
	    		$tpl->assign('tab_selected', $tab_selected);
		}
    	
		$tpl->display('file:' . dirname(__FILE__) . '/templates/groups/index.tpl');
	}
	
	function showTabMailAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__) . '/templates/') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		
		$active_worker = CerberusApplication::getActiveWorker();
		if(!$active_worker->isTeamManager($group_id) && !$active_worker->is_superuser) {
			return;
		} else {
			$group = DAO_Group::getTeam($group_id);
			$tpl->assign('team', $group);
		}
		
		$team_categories = DAO_Bucket::getByTeam($group_id);
		$tpl->assign('categories', $team_categories);
	    
		$group_settings = DAO_GroupSettings::getSettings($group_id);
		$tpl->assign('group_settings', $group_settings);
		
		@$tpl->assign('group_spam_threshold', $group_settings[DAO_GroupSettings::SETTING_SPAM_THRESHOLD]);
		@$tpl->assign('group_spam_action', $group_settings[DAO_GroupSettings::SETTING_SPAM_ACTION]);
		@$tpl->assign('group_spam_action_param', $group_settings[DAO_GroupSettings::SETTING_SPAM_ACTION_PARAM]);
		
		$tpl->display('file:' . $tpl_path . 'groups/manage/index.tpl');
	}
	
	function showTabInboxAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__) . '/templates/') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);

		$tpl->assign('group_id', $group_id);
		
		$active_worker = CerberusApplication::getActiveWorker();
		if(!$active_worker->isTeamManager($group_id) && !$active_worker->is_superuser) {
			return;
		}
		
		$team_rules = DAO_GroupInboxFilter::getByGroupId($group_id);
		$tpl->assign('team_rules', $team_rules);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);

		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);
                    
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

		// Custom Field Sources
		$source_manifests = DevblocksPlatform::getExtensions('cerberusweb.fields.source', false);
		$tpl->assign('source_manifests', $source_manifests);
		
		// Custom Fields
		$custom_fields =  DAO_CustomField::getAll();
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->display('file:' . $tpl_path . 'groups/manage/filters/index.tpl');
	}
	
	function saveTabInboxAction() {
	    @$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer');
	    @$deletes = DevblocksPlatform::importGPC($_REQUEST['deletes'],'array',array());
	    @$sticky_ids = DevblocksPlatform::importGPC($_REQUEST['sticky_ids'],'array',array());
	    @$sticky_order = DevblocksPlatform::importGPC($_REQUEST['sticky_order'],'array',array());
	    
	    @$active_worker = CerberusApplication::getActiveWorker();
	    if(!$active_worker->isTeamManager($group_id) && !$active_worker->is_superuser)
	    	return;
	    
	    // Deletes
	    if(!empty($group_id) && !empty($deletes)) {
	        DAO_GroupInboxFilter::delete($deletes);
	    }
	    
	    // Reordering
	    if(is_array($sticky_ids) && is_array($sticky_order))
	    foreach($sticky_ids as $idx => $id) {
	    	@$order = intval($sticky_order[$idx]);
	    	DAO_GroupInboxFilter::update($id, array(
	    		DAO_GroupInboxFilter::STICKY_ORDER => $order
	    	));
	    }
	    
        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('groups',$group_id,'inbox')));
   	}
   	
   	function showInboxFilterPanelAction() {
   		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
   		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer',0);

		$active_worker = CerberusApplication::getActiveWorker();

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__) . '/templates/') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
   		
		$tpl->assign('group_id', $group_id);
		
		if(null != ($filter = DAO_GroupInboxFilter::get($id))) {
			$tpl->assign('filter', $filter);
		}

		// Make sure we're allowed to change this group's setup
		if(!$active_worker->isTeamManager($group_id) && !$active_worker->is_superuser) {
			return;
		}
		
		$category_name_hash = DAO_Bucket::getCategoryNameHash();
		$tpl->assign('category_name_hash', $category_name_hash);

		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		$team_categories = DAO_Bucket::getTeams();
		$tpl->assign('team_categories', $team_categories);
                    
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		// Custom Fields: Address
		$address_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Address::ID);
		$tpl->assign('address_fields', $address_fields);
		
		// Custom Fields: Orgs
		$org_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Org::ID);
		$tpl->assign('org_fields', $org_fields);
		
		// Custom Fields: Tickets
		$ticket_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Ticket::ID);
		$tpl->assign('ticket_fields', $ticket_fields);
		
		$tpl->display('file:' . $tpl_path . 'groups/manage/filters/peek.tpl');
   	}
   	
   	function saveTabInboxAddAction() {
   		$translate = DevblocksPlatform::getTranslationService();
   		
   		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
   		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer');
   		
	    @$active_worker = CerberusApplication::getActiveWorker();
	    if(!$active_worker->isTeamManager($group_id) && !$active_worker->is_superuser)
	    	return;

	    /*****************************/
		@$name = DevblocksPlatform::importGPC($_POST['name'],'string','');
		@$is_sticky = DevblocksPlatform::importGPC($_POST['is_sticky'],'integer',0);
		@$is_stackable = DevblocksPlatform::importGPC($_POST['is_stackable'],'integer',0);
		@$rules = DevblocksPlatform::importGPC($_POST['rules'],'array',array());
		@$do = DevblocksPlatform::importGPC($_POST['do'],'array',array());
		
		if(empty($name))
			$name = $translate->_('mail.inbox_filter');
		
		$criterion = array();
		$actions = array();
		
		// Custom fields
		$custom_fields = DAO_CustomField::getAll();
		
		// Criteria
		if(is_array($rules))
		foreach($rules as $rule) {
			$rule = DevblocksPlatform::strAlphaNumDash($rule);
			@$value = DevblocksPlatform::importGPC($_POST['value_'.$rule],'string','');
			
			// [JAS]: Allow empty $value (null/blank checking)
			
			$criteria = array(
				'value' => $value,
			);
			
			// Any special rule handling
			switch($rule) {
				case 'subject':
					break;
				case 'from':
					break;
				case 'tocc':
					break;
				case 'header1':
				case 'header2':
				case 'header3':
				case 'header4':
				case 'header5':
					if(null != (@$header = DevblocksPlatform::importGPC($_POST[$rule],'string',null)))
						$criteria['header'] = strtolower($header);
					break;
				case 'body':
					break;
				case 'attachment':
					break;
				default: // ignore invalids
					// Custom fields
					if("cf_" == substr($rule,0,3)) {
						$field_id = intval(substr($rule,3));
						
						if(!isset($custom_fields[$field_id]))
							continue;

						// [TODO] Operators
							
						switch($custom_fields[$field_id]->type) {
							case 'S': // string
							case 'T': // clob
								$oper = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id.'_oper'],'string','regexp');
								$criteria['oper'] = $oper;
								break;
							case 'D': // dropdown
							case 'M': // multi-dropdown
							case 'X': // multi-checkbox
								$in_array = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id],'array',array());
								$out_array = array();
								
								// Hash key on the option for quick lookup later
								if(is_array($in_array))
								foreach($in_array as $k => $v) {
									$out_array[$v] = $v;
								}
								
								$criteria['value'] = $out_array;
								break;
							case 'E': // date
								$from = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id.'_from'],'string','0');
								$to = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id.'_to'],'string','now');
								$criteria['from'] = $from;
								$criteria['to'] = $to;
								unset($criteria['value']);
								break;
							case 'N': // number
								$oper = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id.'_oper'],'string','=');
								$criteria['oper'] = $oper;
								$criteria['value'] = intval($value);
								break;
							case 'C': // checkbox
								$criteria['value'] = intval($value);
								break;
						}
						
					} else {
						continue;
					}
					
					break;
			}
			
			$criterion[$rule] = $criteria;
		}
		
		// Actions
		if(is_array($do))
		foreach($do as $act) {
			$action = array();
			
			switch($act) {
				// Move group/bucket
				case 'move':
					@$move_code = DevblocksPlatform::importGPC($_REQUEST['do_move'],'string',null);
					if(0 != strlen($move_code)) {
						list($g_id, $b_id) = CerberusApplication::translateTeamCategoryCode($move_code);
						$action = array(
							'group_id' => intval($g_id),
							'bucket_id' => intval($b_id),
						);
					}
					break;
				// Assign to worker
				case 'assign':
					@$worker_id = DevblocksPlatform::importGPC($_REQUEST['do_assign'],'string',null);
					if(0 != strlen($worker_id))
						$action = array(
							'worker_id' => intval($worker_id)
						);
					break;
				// Spam training
				case 'spam':
					@$is_spam = DevblocksPlatform::importGPC($_REQUEST['do_spam'],'string',null);
					if(0 != strlen($is_spam))
						$action = array(
							'is_spam' => (!$is_spam?0:1)
						);
					break;
				// Set status
				case 'status':
					@$status = DevblocksPlatform::importGPC($_REQUEST['do_status'],'string',null);
					if(0 != strlen($status)) {
						$action = array(
							'is_waiting' => (3==$status?1:0), // explicit waiting
							'is_closed' => ((0==$status||3==$status)?0:1), // not open or waiting
							'is_deleted' => (2==$status?1:0), // explicit deleted
						);
					}
					break;
				default: // ignore invalids
					// Custom fields
					if("cf_" == substr($act,0,3)) {
						$field_id = intval(substr($act,3));
						
						if(!isset($custom_fields[$field_id]))
							continue;

						$action = array();
							
						// [TODO] Operators
							
						switch($custom_fields[$field_id]->type) {
							case 'S': // string
							case 'T': // clob
							case 'D': // dropdown
								$value = DevblocksPlatform::importGPC($_REQUEST['do_cf_'.$field_id],'string','');
								$action['value'] = $value;
								break;
							case 'M': // multi-dropdown
							case 'X': // multi-checkbox
								$in_array = DevblocksPlatform::importGPC($_REQUEST['do_cf_'.$field_id],'array',array());
								$out_array = array();
								
								// Hash key on the option for quick lookup later
								if(is_array($in_array))
								foreach($in_array as $k => $v) {
									$out_array[$v] = $v;
								}
								
								$action['value'] = $out_array;
								break;
							case 'E': // date
								$value = DevblocksPlatform::importGPC($_REQUEST['do_cf_'.$field_id],'string','');
								$action['value'] = $value;
								break;
							case 'N': // number
							case 'C': // checkbox
								$value = DevblocksPlatform::importGPC($_REQUEST['do_cf_'.$field_id],'string','');
								$action['value'] = intval($value);
								break;
						}
						
					} else {
						continue;
					}
					break;
			}
			
			$actions[$act] = $action;
		}

   		$fields = array(
   			DAO_GroupInboxFilter::NAME => $name,
   			DAO_GroupInboxFilter::IS_STICKY => $is_sticky,
   			DAO_GroupInboxFilter::CRITERIA_SER => serialize($criterion),
   			DAO_GroupInboxFilter::ACTIONS_SER => serialize($actions),
   		);

   		// Only sticky filters can manual order and be stackable
   		if(!$is_sticky) {
   			$fields[DAO_GroupInboxFilter::STICKY_ORDER] = 0;
   			$fields[DAO_GroupInboxFilter::IS_STACKABLE] = 0;
   		} else { // is sticky
   			$fields[DAO_GroupInboxFilter::IS_STACKABLE] = $is_stackable;
   		}
   		
   		// Create
   		if(empty($id)) {
   			$fields[DAO_GroupInboxFilter::GROUP_ID] = $group_id;
   			$fields[DAO_GroupInboxFilter::POS] = 0;
	   		$id = DAO_GroupInboxFilter::create($fields);
	   		
	   	// Update
   		} else {
   			DAO_GroupInboxFilter::update($id, $fields);
   		}
   		
   		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('groups',$group_id,'inbox')));
   	}
	
	// Post
	function saveTabMailAction() {
	    @$team_id = DevblocksPlatform::importGPC($_REQUEST['team_id'],'integer');

	    @$active_worker = CerberusApplication::getActiveWorker();
	    if(!$active_worker->isTeamManager($team_id) && !$active_worker->is_superuser)
	    	return;
	    	
		// Validators
		// [TODO] This could move into a Devblocks validation class later.
		$validator_email = new Zend_Validate_EmailAddress(Zend_Validate_Hostname::ALLOW_DNS | Zend_Validate_Hostname::ALLOW_LOCAL);
	    
	    //========== GENERAL
	    @$signature = DevblocksPlatform::importGPC($_REQUEST['signature'],'string','');
	    @$auto_reply_enabled = DevblocksPlatform::importGPC($_REQUEST['auto_reply_enabled'],'integer',0);
	    @$auto_reply = DevblocksPlatform::importGPC($_REQUEST['auto_reply'],'string','');
	    @$close_reply_enabled = DevblocksPlatform::importGPC($_REQUEST['close_reply_enabled'],'integer',0);
	    @$close_reply = DevblocksPlatform::importGPC($_REQUEST['close_reply'],'string','');
	    @$sender_address = DevblocksPlatform::importGPC($_REQUEST['sender_address'],'string','');
	    @$sender_personal = DevblocksPlatform::importGPC($_REQUEST['sender_personal'],'string','');
	    @$sender_personal_with_worker = DevblocksPlatform::importGPC($_REQUEST['sender_personal_with_worker'],'integer',0);
	    @$subject_has_mask = DevblocksPlatform::importGPC($_REQUEST['subject_has_mask'],'integer',0);
	    @$subject_prefix = DevblocksPlatform::importGPC($_REQUEST['subject_prefix'],'string','');
	    @$spam_threshold = DevblocksPlatform::importGPC($_REQUEST['spam_threshold'],'integer',80);
	    @$spam_action = DevblocksPlatform::importGPC($_REQUEST['spam_action'],'integer',0);
	    @$spam_moveto = DevblocksPlatform::importGPC($_REQUEST['spam_action_moveto'],'integer',0);

	    // Validate sender address
	    if(!$validator_email->isValid($sender_address)) {
	    	$sender_address = '';
	    }
	    
	    // [TODO] Move this into DAO_GroupSettings
	    DAO_Group::updateTeam($team_id, array(
	        DAO_Group::TEAM_SIGNATURE => $signature
	    ));
	    
	    // [TODO] Verify the sender address has been used in a 'To' header in the past
		// select count(header_value) from message_header where header_name = 'to' AND (header_value = 'sales@webgroupmedia.com' OR header_value = '<sales@webgroupmedia.com>');
		// DAO_MessageHeader::
	    
	    DAO_GroupSettings::set($team_id, DAO_GroupSettings::SETTING_REPLY_FROM, $sender_address);
	    DAO_GroupSettings::set($team_id, DAO_GroupSettings::SETTING_REPLY_PERSONAL, $sender_personal);
	    DAO_GroupSettings::set($team_id, DAO_GroupSettings::SETTING_REPLY_PERSONAL_WITH_WORKER, $sender_personal_with_worker);
	    DAO_GroupSettings::set($team_id, DAO_GroupSettings::SETTING_SUBJECT_HAS_MASK, $subject_has_mask);
	    DAO_GroupSettings::set($team_id, DAO_GroupSettings::SETTING_SUBJECT_PREFIX, $subject_prefix);
	    DAO_GroupSettings::set($team_id, DAO_GroupSettings::SETTING_SPAM_THRESHOLD, $spam_threshold);
	    DAO_GroupSettings::set($team_id, DAO_GroupSettings::SETTING_SPAM_ACTION, $spam_action);
	    DAO_GroupSettings::set($team_id, DAO_GroupSettings::SETTING_SPAM_ACTION_PARAM, $spam_moveto);
	    DAO_GroupSettings::set($team_id, DAO_GroupSettings::SETTING_AUTO_REPLY_ENABLED, $auto_reply_enabled);
	    DAO_GroupSettings::set($team_id, DAO_GroupSettings::SETTING_AUTO_REPLY, $auto_reply);
	    DAO_GroupSettings::set($team_id, DAO_GroupSettings::SETTING_CLOSE_REPLY_ENABLED, $close_reply_enabled);
	    DAO_GroupSettings::set($team_id, DAO_GroupSettings::SETTING_CLOSE_REPLY, $close_reply);
	       
        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('groups',$team_id)));
	}
	
	function showTabMembersAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__) . '/templates/') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		
		$active_worker = CerberusApplication::getActiveWorker();
		if(!$active_worker->isTeamManager($group_id) && !$active_worker->is_superuser) {
			return;
		} else {
			$group = DAO_Group::getTeam($group_id);
			$tpl->assign('team', $group);
		}
		
		$members = DAO_Group::getTeamMembers($group_id);
	    $tpl->assign('members', $members);
	    
		$workers = DAO_Worker::getAllActive();
	    $tpl->assign('workers', $workers);
		
		$tpl->display('file:' . $tpl_path . 'groups/manage/members.tpl');
	}
	
	function saveTabMembersAction() {
		@$team_id = DevblocksPlatform::importGPC($_REQUEST['team_id'],'integer');
		@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_ids'],'array',array());
		@$worker_levels = DevblocksPlatform::importGPC($_REQUEST['worker_levels'],'array',array());
		
	    @$active_worker = CerberusApplication::getActiveWorker();
	    @$members = DAO_Group::getTeamMembers($team_id);
	    
	    if(!$active_worker->isTeamManager($team_id) && !$active_worker->is_superuser)
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
	    
	    DevblocksPlatform::redirect(new DevblocksHttpResponse(array('groups',$team_id,'members')));
	}
	
	function showTabBucketsAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__) . '/templates/') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);

		$active_worker = CerberusApplication::getActiveWorker();
		if(!$active_worker->isTeamManager($group_id) && !$active_worker->is_superuser) {
			return;
		} else {
			$group = DAO_Group::getTeam($group_id);
			$tpl->assign('team', $group);
		}
		
		$team_categories = DAO_Bucket::getByTeam($group_id);
		$tpl->assign('categories', $team_categories);
		
		$inbox_is_assignable = DAO_GroupSettings::get($group_id, DAO_GroupSettings::SETTING_INBOX_IS_ASSIGNABLE, 1);
		$tpl->assign('inbox_is_assignable', $inbox_is_assignable);
		
		$tpl->display('file:' . $tpl_path . 'groups/manage/buckets.tpl');
	}
	
	function saveTabBucketsAction() {
	    @$team_id = DevblocksPlatform::importGPC($_REQUEST['team_id'],'integer');
	    
	    @$active_worker = CerberusApplication::getActiveWorker();
	    if(!$active_worker->isTeamManager($team_id) && !$active_worker->is_superuser)
	    	return;
	    
	    // Inbox assignable
	    @$inbox_assignable = DevblocksPlatform::importGPC($_REQUEST['inbox_assignable'],'integer',0);
	    DAO_GroupSettings::set($team_id, DAO_GroupSettings::SETTING_INBOX_IS_ASSIGNABLE, intval($inbox_assignable));
	    	
	    //========== BUCKETS   
	    @$ids = DevblocksPlatform::importGPC($_REQUEST['ids'],'array');
	    @$add_str = DevblocksPlatform::importGPC($_REQUEST['add'],'string');
	    @$pos = DevblocksPlatform::importGPC($_REQUEST['pos'],'array');
	    @$names = DevblocksPlatform::importGPC($_REQUEST['names'],'array');
	    @$assignables = DevblocksPlatform::importGPC($_REQUEST['is_assignable'],'array');
	    @$deletes = DevblocksPlatform::importGPC($_REQUEST['deletes'],'array');
	    
	    // Updates
	    if(!empty($ids)) {
		    $cats = DAO_Bucket::getList($ids);
		    foreach($ids as $idx => $id) {
		        @$cat = $cats[$id];
		        if(is_object($cat)) {
		        	$is_assignable = (false === array_search($id, $assignables)) ? 0 : 1;
		        	
		        	$fields = array(
		        		DAO_Bucket::NAME => $names[$idx],
		        		DAO_Bucket::POS => intval($pos[$idx]),
		        		DAO_Bucket::IS_ASSIGNABLE => intval($is_assignable),
		        	);
		            DAO_Bucket::update($id, $fields);
		        }
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
	        
        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('groups',$team_id,'buckets')));
	}
	
	function showTabFieldsAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__) . '/templates/') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		
		$active_worker = CerberusApplication::getActiveWorker();
		if(!$active_worker->isTeamManager($group_id) && !$active_worker->is_superuser) {
			return;
		} else {
			$group = DAO_Group::getTeam($group_id);
			$tpl->assign('team', $group);
		}
		
		$group_fields = DAO_CustomField::getBySourceAndGroupId(ChCustomFieldSource_Ticket::ID, $group_id); 
		$tpl->assign('group_fields', $group_fields);
                    
		$types = Model_CustomField::getTypes();
		$tpl->assign('types', $types);
		
		$tpl->display('file:' . $tpl_path . 'groups/manage/fields.tpl');
	}
	
	// Post
	function saveTabFieldsAction() {
		@$group_id = DevblocksPlatform::importGPC($_POST['team_id'],'integer');
		
	    @$active_worker = CerberusApplication::getActiveWorker();
	    if(!$active_worker->isTeamManager($group_id) && !$active_worker->is_superuser)
	    	return;
	    	
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
				DAO_CustomField::delete($id);
				
			} else {
				$fields = array(
					DAO_CustomField::NAME => $name, 
					DAO_CustomField::POS => $order,
					DAO_CustomField::OPTIONS => !is_null($option) ? $option : '',
				);
				DAO_CustomField::update($id, $fields);
			}
		}
		
		// Add custom field
		@$add_name = DevblocksPlatform::importGPC($_POST['add_name'],'string','');
		@$add_type = DevblocksPlatform::importGPC($_POST['add_type'],'string','');
		@$add_options = DevblocksPlatform::importGPC($_POST['add_options'],'string','');
		
		if(!empty($add_name) && !empty($add_type)) {
			$fields = array(
				DAO_CustomField::NAME => $add_name,
				DAO_CustomField::TYPE => $add_type,
				DAO_CustomField::GROUP_ID => $group_id,
				DAO_CustomField::SOURCE_EXTENSION => ChCustomFieldSource_Ticket::ID,
				DAO_CustomField::OPTIONS => $add_options,
			);
			$id = DAO_CustomField::create($fields);
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('groups',$group_id,'fields')));
	}
	
	function showGroupPanelAction() {
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__) . '/templates/') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		
		$tpl->assign('view_id', $view_id);
		
		if(!empty($group_id) && null != ($group = DAO_Group::getTeam($group_id))) {
			$tpl->assign('group', $group);
		}
		
		$tpl->display('file:' . $tpl_path . 'groups/rpc/peek.tpl');
	}
	
	function saveGroupPanelAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string','');

		$fields = array(
			DAO_Group::TEAM_NAME => $name			
		);
		
		// [TODO] Delete
		
		if(empty($group_id)) { // new
			$group_id = DAO_Group::create($fields);
			
		} else { // update
			DAO_Group::update($group_id, $fields);
			
		}
		
		// Reload view (if linked)
		if(!empty($view_id) && null != ($view = C4_AbstractViewLoader::getView('', $view_id))) {
			$view->render();
		}
		exit;
	}
   	
};

class ChKbPage extends CerberusPageExtension {
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
	
	function getActivity() {
		return new Model_Activity('activity.kb');
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$visit = CerberusApplication::getVisit();
		$translate = DevblocksPlatform::getTranslationService();
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;

		array_shift($stack); // contacts
		
		switch(array_shift($stack)) {
			case 'search':
				if(null == ($view = C4_AbstractViewLoader::getView(null, 'kb_search'))) {
					$view = new C4_KbArticleView();
					$view->id = 'kb_search';
					$view->name = $translate->_('common.search_results');
					C4_AbstractViewLoader::setView($view->id, $view);
				}
				$tpl->assign('view', $view);
				
				$tpl->assign('view_fields', C4_KbArticleView::getFields());
				$tpl->assign('view_searchable_fields', C4_KbArticleView::getSearchFields());
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/kb/search.tpl');
				break;
				
			default:
			case 'overview':
				@$root_id = intval(array_shift($stack));
				$tpl->assign('root_id', $root_id);

				$tree = DAO_KbCategory::getTreeMap($root_id);
				$tpl->assign('tree', $tree);

				$categories = DAO_KbCategory::getWhere();
				$tpl->assign('categories', $categories);
				
				// Breadcrumb // [TODO] API-ize inside Model_KbTree ?
				$breadcrumb = array();
				$pid = $root_id;
				while(0 != $pid) {
					$breadcrumb[] = $pid;
					$pid = $categories[$pid]->parent_id;
				}
				$tpl->assign('breadcrumb',array_reverse($breadcrumb));
				
				$tpl->assign('mid', @intval(ceil(count($tree[$root_id])/2)));
				
				if(null == ($view = C4_AbstractViewLoader::getView(null, C4_KbArticleView::DEFAULT_ID))) {
					$view = new C4_KbArticleView();
				}
				
				// Articles
				if(empty($root_id)) {
					$view->params = array(
						new DevblocksSearchCriteria(SearchFields_KbArticle::CATEGORY_ID,DevblocksSearchCriteria::OPER_IS_NULL,true),
					);
					$view->name = $translate->_('kb.view.uncategorized');
					
				} else {
					$view->params = array(
						new DevblocksSearchCriteria(SearchFields_KbArticle::CATEGORY_ID,'=',$root_id),
					);
					$view->name = vsprintf($translate->_('kb.view.articles'), $categories[$root_id]->name);
				}

				C4_AbstractViewLoader::setView($view->id, $view);
				
				$tpl->assign('view', $view);
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/kb/index.tpl');
				break;
		}
	}
	
	function doViewDeleteAction() {
		// [TODO] Check privs/superuser
		// [TODO] Check that articles are in no topic (XSS/etc)
		
		@$row_id = DevblocksPlatform::importGPC($_POST['row_id'],'array',array());
		@$return = DevblocksPlatform::importGPC($_POST['return'],'string','');
		
		if(!empty($row_id)) {
			DAO_KbArticle::delete($row_id);
		}
		
		if(!empty($return)) {
			$return_path = explode('/', $return);
			DevblocksPlatform::redirect(new DevblocksHttpResponse($return_path));
		}
	}
	
	function doArticleQuickSearchAction() {
        @$type = DevblocksPlatform::importGPC($_POST['type'],'string'); 
        @$query = DevblocksPlatform::importGPC($_POST['query'],'string');

        $query = trim($query);
        
        $visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
		$searchView = C4_AbstractViewLoader::getView('','kb_search');
		
		// [TODO]
//		if(null == $searchView)
//			$searchView = C4_KbArticleView:

//        $visit->set('quick_search_type', $type);
        
        $params = array();
        
        switch($type) {
            case "content":
//		        if($query && false===strpos($query,'*'))
//		            $query = '*' . $query . '*';
            	$params[SearchFields_KbArticle::CONTENT] = new DevblocksSearchCriteria(SearchFields_KbArticle::CONTENT,DevblocksSearchCriteria::OPER_FULLTEXT,$query);               
                break;
        }
        
        $searchView->params = $params;
        $searchView->renderPage = 0;
        $searchView->renderSortBy = null;
        
        C4_AbstractViewLoader::setView($searchView->id,$searchView);
        
        DevblocksPlatform::redirect(new DevblocksHttpResponse(array('kb','search')));
	}
	
	function showArticlePeekPanelAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
//		@$root_id = DevblocksPlatform::importGPC($_REQUEST['root_id']);
		@$return = DevblocksPlatform::importGPC($_REQUEST['return']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

//		$tpl->assign('root_id', $root_id);
		$tpl->assign('return', $return);
		
		if(!empty($id)) {
			$article = DAO_KbArticle::get($id);
			$tpl->assign('article', $article);
			
//			$article_categories = DAO_KbArticle::getCategoriesByArticleId($id);
//			$tpl->assign('article_categories', $article_categories);
		}
		
//		$categories = DAO_KbCategory::getWhere();
//		$tpl->assign('categories', $categories);
		
//		$levels = DAO_KbCategory::getTree(0,$map); //$root_id
//		$tpl->assign('levels',$levels);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/kb/rpc/article_peek_panel.tpl');
	}
	
	function showArticleEditPanelAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$root_id = DevblocksPlatform::importGPC($_REQUEST['root_id']);
		@$return = DevblocksPlatform::importGPC($_REQUEST['return']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$tpl->assign('root_id', $root_id);
		$tpl->assign('return', $return);
		
		if(!empty($id)) {
			$article = DAO_KbArticle::get($id);
			$tpl->assign('article', $article);
			
			$article_categories = DAO_KbArticle::getCategoriesByArticleId($id);
			$tpl->assign('article_categories', $article_categories);
		}
		
		$categories = DAO_KbCategory::getWhere();
		$tpl->assign('categories', $categories);
		
		$levels = DAO_KbCategory::getTree(0); //$root_id
		$tpl->assign('levels',$levels);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/kb/rpc/article_edit_panel.tpl');
	}

	function saveArticleEditPanelAction() {
		$translate = DevblocksPlatform::getTranslationService();
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$do_delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
		@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string');
		@$category_ids = DevblocksPlatform::importGPC($_REQUEST['category_ids'],'array',array());
		@$content_raw = DevblocksPlatform::importGPC($_REQUEST['content_raw'],'string');
		@$format = DevblocksPlatform::importGPC($_REQUEST['format'],'integer',0);
		
		@$return = DevblocksPlatform::importGPC($_REQUEST['return']);
		
		if(!empty($id) && !empty($do_delete)) { // Delete
			DAO_KbArticle::delete($id);
			
		} else { // Create|Modify
			// Sanitize
			if($format > 2 || $format < 0)
				$format = 0;
				
			if(empty($title))
				$title = '(' . $translate->_('kb_article.title') . ')';
			
			switch($format) {
				default:
				case 0: // plaintext
					$content_html = nl2br($content_raw);
					break;
				case 1: // HTML
					$content_html = $content_raw;
					break;
			}
				
			if(empty($id)) { // create
				$fields = array(
					DAO_KbArticle::TITLE => $title,
					DAO_KbArticle::FORMAT => $format,
					DAO_KbArticle::CONTENT_RAW => $content_raw,
					DAO_KbArticle::CONTENT => $content_html,
					DAO_KbArticle::UPDATED => time(),
				);
				$id = DAO_KbArticle::create($fields);
				
			} else { // update
				$fields = array(
					DAO_KbArticle::TITLE => $title,
					DAO_KbArticle::FORMAT => $format,
					DAO_KbArticle::CONTENT_RAW => $content_raw,
					DAO_KbArticle::CONTENT => $content_html,
					DAO_KbArticle::UPDATED => time(),
				);
				DAO_KbArticle::update($id, $fields);
				
			}
			
			DAO_KbArticle::setCategories($id, $category_ids, true);
		}
		
		if(!empty($return)) {
			$return_path = explode('/', $return);
			DevblocksPlatform::redirect(new DevblocksHttpResponse($return_path));
		}
	}
	
	function showKbCategoryEditPanelAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$root_id = DevblocksPlatform::importGPC($_REQUEST['root_id']);
		@$return = DevblocksPlatform::importGPC($_REQUEST['return']);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$tpl->assign('root_id', $root_id);
		$tpl->assign('return', $return);
		
		if(!empty($id)) {
			$category = DAO_KbCategory::get($id);
			$tpl->assign('category', $category);
		}
		
		/*
		 * [TODO] Remove the current category + descendents from the categories, 
		 * so the worker can't create a closed subtree (e.g. category's parent is its child)
		 */
		
		$categories = DAO_KbCategory::getWhere();
		$tpl->assign('categories', $categories);
		
		$levels = DAO_KbCategory::getTree(0); //$root_id
		$tpl->assign('levels',$levels);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/kb/rpc/subcategory_edit_panel.tpl');
	}
	
	function saveKbCategoryEditPanelAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string');
		@$parent_id = DevblocksPlatform::importGPC($_REQUEST['parent_id'],'integer',0);
		@$delete = DevblocksPlatform::importGPC($_REQUEST['delete_box'],'integer',0);

		@$return = DevblocksPlatform::importGPC($_REQUEST['return']);
		
		if(!empty($id) && !empty($delete)) {
			$ids = DAO_KbCategory::getDescendents($id);
			DAO_KbCategory::delete($ids);
			
			// Change $return to category parent
			$return = "kb/overview/" . sprintf("%06d", $parent_id);
			
		} elseif(empty($id)) { // create
			$fields = array(
				DAO_KbCategory::NAME => $name,
				DAO_KbCategory::PARENT_ID => $parent_id,
			);
			DAO_KbCategory::create($fields);
			
		} else { // update
			$fields = array(
				DAO_KbCategory::NAME => $name,
				DAO_KbCategory::PARENT_ID => $parent_id,
			);
			DAO_KbCategory::update($id, $fields);
			
		}
		
		if(!empty($return)) {
			$return_path = explode('/', $return);
			DevblocksPlatform::redirect(new DevblocksHttpResponse($return_path));
		}
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
		@$reload = DevblocksPlatform::importGPC($_REQUEST['reload'],'integer',0);
		@$loglevel = DevblocksPlatform::importGPC($_REQUEST['loglevel'],'integer',0);
		
		$logger = DevblocksPlatform::getConsoleLog();
		$translate = DevblocksPlatform::getTranslationService();
		
	    $settings = CerberusSettings::getInstance();
	    $authorized_ips_str = $settings->get(CerberusSettings::AUTHORIZED_IPS);
	    $authorized_ips = DevblocksPlatform::parseCrlfString($authorized_ips_str);
	    
	    $authorized_ip_defaults = DevblocksPlatform::parseCsvString(AUTHORIZED_IPS_DEFAULTS);
	    $authorized_ips = array_merge($authorized_ips, $authorized_ip_defaults);
	    
	    @$is_ignoring_wait = DevblocksPlatform::importGPC($_REQUEST['ignore_wait'],'integer',0);
	    
	    $pass = false;
		foreach ($authorized_ips as $ip) {
			if(substr($ip,0,strlen($ip)) == substr($_SERVER['REMOTE_ADDR'],0,strlen($ip)))
		 	{ $pass=true; break; }
		}
	    if(!$pass) {
		    echo vsprintf($translate->_('cron.ip_unauthorized'), $_SERVER['REMOTE_ADDR']);
		    return;
	    }
		
		$stack = $request->path;
		
		array_shift($stack); // cron
		$job_id = array_shift($stack);

        @set_time_limit(0); // Unlimited (if possible)
		
		$url = DevblocksPlatform::getUrlService();
        $timelimit = intval(ini_get('max_execution_time'));
		
        if($reload) {
        	$reload_url = sprintf("%s?reload=%d&loglevel=%d&ignore_wait=%d",
        		$url->write('c=cron' . ($job_id ? ("&a=".$job_id) : "")),
        		intval($reload),
        		intval($loglevel),
        		intval($is_ignoring_wait)
        	);
			echo "<HTML>".
			"<HEAD>".
			"<TITLE></TITLE>".
			"<meta http-equiv='Refresh' content='".intval($reload).";".$reload_url."'>". 
		    "</HEAD>".
			"<BODY>"; // onload=\"setTimeout(\\\"window.location.replace('".$url->write('c=cron')."')\\\",30);\"
        }

	    // [TODO] Determine if we're on a time limit under 60 seconds
		
	    $cron_manifests = DevblocksPlatform::getExtensions('cerberusweb.cron', true);
        $jobs = array();
	    
	    if(empty($job_id)) { // do everything 
			
		    // Determine who wants to go first by next time and longest waiting
            $nexttime = time() + 86400;
		    
			if(is_array($cron_manifests))
			foreach($cron_manifests as $idx => $instance) { /* @var $instance CerberusCronPageExtension */
			    $lastrun = $instance->getParam(CerberusCronPageExtension::PARAM_LASTRUN, 0);
			    
			    if($instance->isReadyToRun($is_ignoring_wait)) {
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
			    if($instance->isReadyToRun($is_ignoring_wait)) {
			        $jobs[0] =& $instance;
			    }
			}
	    }
	    
		if(!empty($jobs)) {
		    foreach($jobs as $nextjob) {
		        $nextjob->setParam(CerberusCronPageExtension::PARAM_LOCKED, time());
	    	    $nextjob->_run();
	        }
		} elseif($reload) {
		    $logger->info(vsprintf($translate->_('cron.nothing_to_do'), intval($reload)));
		}
		
		if($reload) {
	    	echo "</BODY>".
	    	"</HTML>";
		}
		
		exit;
	}
};

class ChPrintController extends DevblocksControllerExtension {
	const ID = 'core.controller.print';
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$router = DevblocksPlatform::getRoutingService();
		$router->addRoute('print',self::ID);
	}
	
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		$worker = CerberusApplication::getActiveWorker();
		if(empty($worker)) return;
		
		$stack = $request->path;
		array_shift($stack); // print
		@$object = strtolower(array_shift($stack)); // ticket|message|etc
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$settings = CerberusSettings::getInstance();
		$tpl->assign('settings', $settings);
		
		$translate = DevblocksPlatform::getTranslationService();
		$tpl->assign('translate', $translate);
		
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		// Security
		$active_worker = CerberusApplication::getActiveWorker();
		$active_worker_memberships = $active_worker->getMemberships();
		
		// [TODO] Make this pluggable
		// Subcontroller
		switch($object) {
			case 'ticket':
				@$id = array_shift($stack);
				@$ticket = is_numeric($id) ? DAO_Ticket::getTicket($id) : DAO_Ticket::getTicketByMask($id);

				$convo_timeline = array();
				$messages = $ticket->getMessages();		
				foreach($messages as $message_id => $message) { /* @var $message CerberusMessage */
					$key = $message->created_date . '_m' . $message_id;
					// build a chrono index of messages
					$convo_timeline[$key] = array('m',$message_id);
				}				
				@$mail_inline_comments = DAO_WorkerPref::get($active_worker->id,'mail_inline_comments',1);
				
				if($mail_inline_comments) { // if inline comments are enabled
					$comments = DAO_TicketComment::getByTicketId($ticket->id);
					arsort($comments);
					$tpl->assign('comments', $comments);
					
					// build a chrono index of comments
					foreach($comments as $comment_id => $comment) { /* @var $comment Model_TicketComment */
						$key = $comment->created . '_c' . $comment_id;
						$convo_timeline[$key] = array('c',$comment_id);
					}
				}

				ksort($convo_timeline);
				
				$tpl->assign('convo_timeline', $convo_timeline);

				// Comment parent addresses
				$comment_addresses = array();
				foreach($comments as $comment) { /* @var $comment Model_TicketComment */
					$address_id = intval($comment->address_id);
					if(!isset($comment_addresses[$address_id])) {
						$address = DAO_Address::get($address_id);
						$comment_addresses[$address_id] = $address;
					}
				}
				$tpl->assign('comment_addresses', $comment_addresses);				
				
				// Message Notes
				$notes = DAO_MessageNote::getByTicketId($ticket->id);
				$message_notes = array();
				// Index notes by message id
				if(is_array($notes))
				foreach($notes as $note) {
					if(!isset($message_notes[$note->message_id]))
						$message_notes[$note->message_id] = array();
					$message_notes[$note->message_id][$note->id] = $note;
				}
				$tpl->assign('message_notes', $message_notes);
				
				
				// Make sure we're allowed to view this ticket or message
				if(!isset($active_worker_memberships[$ticket->team_id])) {
					echo "<H1>" . $translate->_('common.access_denied') . "</H1>";
					return;
				}

				$tpl->assign('ticket', $ticket);
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/print/ticket.tpl');
				break;
				
			case 'message':
				@$id = array_shift($stack);
				@$message = DAO_Ticket::getMessage($id);
				@$ticket = DAO_Ticket::getTicket($message->ticket_id);
				
				// Make sure we're allowed to view this ticket or message
				if(!isset($active_worker_memberships[$ticket->team_id])) {
					echo "<H1>" . $translate->_('common.access_denied') . "</H1>";
					return;
				}
				
				// Message Notes
				$notes = DAO_MessageNote::getByTicketId($ticket->id);
				$message_notes = array();
				// Index notes by message id
				if(is_array($notes))
				foreach($notes as $note) {
					if(!isset($message_notes[$note->message_id]))
						$message_notes[$note->message_id] = array();
					$message_notes[$note->message_id][$note->id] = $note;
				}
				$tpl->assign('message_notes', $message_notes);				
				
				$tpl->assign('message', $message);
				$tpl->assign('ticket', $ticket);
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/print/message.tpl');
				break;
		}
	}
};

// Note Sources

class ChNotesSource_Org extends Extension_NoteSource {
	const ID = 'cerberusweb.notes.source.org';
};

// Custom Field Sources

class ChCustomFieldSource_Address extends Extension_CustomFieldSource {
	const ID = 'cerberusweb.fields.source.address';
};

class ChCustomFieldSource_Org extends Extension_CustomFieldSource {
	const ID = 'cerberusweb.fields.source.org';
};

class ChCustomFieldSource_Task extends Extension_CustomFieldSource {
	const ID = 'cerberusweb.fields.source.task';
};

class ChCustomFieldSource_Ticket extends Extension_CustomFieldSource {
	const ID = 'cerberusweb.fields.source.ticket';
};

// Workspace Sources

class ChWorkspaceSource_Address extends Extension_WorkspaceSource {
	const ID = 'core.workspace.source.address';
};

class ChWorkspaceSource_Org extends Extension_WorkspaceSource {
	const ID = 'core.workspace.source.org';
};

class ChWorkspaceSource_Task extends Extension_WorkspaceSource {
	const ID = 'core.workspace.source.task';
};

class ChWorkspaceSource_Ticket extends Extension_WorkspaceSource {
	const ID = 'core.workspace.source.ticket';
};

// RSS

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
		$translate = DevblocksPlatform::getTranslationService();
		
		// [TODO] Do we want any concept of authentication here?

        $stack = $request->path;
		array_shift($stack); // rss
		$hash = array_shift($stack);

		$feed = DAO_ViewRss::getByHash($hash);
        if(empty($feed)) {
            die($translate->_('rss.bad_feed'));
        }

        // Sources
        $rss_sources = DevblocksPlatform::getExtensions('cerberusweb.rss.source', true);
        if(isset($rss_sources[$feed->source_extension])) {
        	$rss_source =& $rss_sources[$feed->source_extension]; /* @var $rss_source Extension_RssSource */
			header("Content-Type: text/xml");
        	echo $rss_source->getFeedAsRss($feed);
        }
        
		exit;
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

	    $translate = DevblocksPlatform::getTranslationService();
	    
	    $stack = $request->path;
	    array_shift($stack); // update

	    $cache = DevblocksPlatform::getCacheService(); /* @var $cache _DevblocksCacheManager */
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
				    echo vsprintf($translate->_('update.ip_unauthorized'), $_SERVER['REMOTE_ADDR']);
				    return;
			    }
				
			    // Check requirements
			    $errors = CerberusApplication::checkRequirements();
			    
			    if(!empty($errors)) {
			    	echo $translate->_('update.correct_errors');
			    	echo "<ul style='color:red;'>";
			    	foreach($errors as $error) {
			    		echo "<li>".$error."</li>";
			    	}
			    	echo "</ul>";
			    	exit;
			    }
			    
			    // If authorized, lock and attempt update
				if(!file_exists($file) || @filectime($file)+600 < time()) { // 10 min lock
					touch($file);

				    //echo "Running plugin patches...<br>";
				    if(DevblocksPlatform::runPluginPatches('core.patches')) {
						@unlink($file);

						// [JAS]: Clear all caches
						$cache->clean();

						// Reload plugin translations
						DAO_Translation::reloadPluginStrings();
						
				    	DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login')));
				    } else {
						@unlink($file);
				    	echo "Failure!"; // [TODO] Needs elaboration
				    } 
				    break;
				}
				else {
					echo $translate->_('update.locked_another');
				}
	    }
	    
		exit;
	}
}

class ChDebugController extends DevblocksControllerExtension  {
	function __construct($manifest) {
		parent::__construct($manifest);
		$router = DevblocksPlatform::getRoutingService();
		$router->addRoute('debug','core.controller.debug');
	}
		
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
	    @set_time_limit(0); // no timelimit (when possible)

	    $stack = $request->path;
	    array_shift($stack); // update

//	    $cache = DevblocksPlatform::getCacheService(); /* @var $cache _DevblocksCacheManager */
		$settings = CerberusSettings::getInstance();

		$authorized_ips_str = $settings->get(CerberusSettings::AUTHORIZED_IPS);
		$authorized_ips = DevblocksPlatform::parseCrlfString($authorized_ips_str);
	    
		$authorized_ip_defaults = DevblocksPlatform::parseCsvString(AUTHORIZED_IPS_DEFAULTS);
		$authorized_ips = array_merge($authorized_ips, $authorized_ip_defaults);
	    
	    // Is this IP authorized?
	    $pass = false;
		foreach ($authorized_ips as $ip) {
			if(substr($ip,0,strlen($ip)) == substr($_SERVER['REMOTE_ADDR'],0,strlen($ip))) { 
				$pass = true; 
				break;
			}
		}
		
	    if(!$pass) {
		    echo 'Your IP address ('.$_SERVER['REMOTE_ADDR'].') is not authorized to debug this helpdesk.';
		    return;
	    }
		
	    switch(array_shift($stack)) {
	    	case 'phpinfo':
	    		phpinfo();
	    		break;
	    		
	    	case 'check':
				echo sprintf(
					"<html>
					<head>
						<title></title>
						<style>
							BODY {font-family: Arial, Helvetica, sans-serif; font-size: 12px;}
							FORM {margin:0px; } 
							H1 { margin:0px; }
							.fail {color:red;font-weight:bold;}
							.pass {color:green;font-weight:bold;}
						</style>
					</head>
					<body>
						<h1>Cerberus Helpdesk - Requirements Checker:</h1>
					"
				);

				$errors = CerberusApplication::checkRequirements();

				if(!empty($errors)) {
					echo "<ul class='fail'>";
					foreach($errors as $error) {
						echo sprintf("<li>%s</li>",$error);
					}
					echo "</ul>";
					
				} else {
					echo '<span class="pass">Your server is compatible with Cerberus Helpdesk 4.0!</span>';
				}
				
				echo sprintf("
					</body>
					</html>
				");
	    		
	    		break;
	    		
	    	case 'report':
	    		@$db = DevblocksPlatform::getDatabaseService();
	    		@$settings = CerberusSettings::getInstance();
	    		
	    		@$tables = $db->MetaTables('TABLE',false);
	    		
				$report_output = sprintf(
					"[Cerberus Helpdesk] App Build: %s\n".
					"[Cerberus Helpdesk] Devblocks Build: %s\n".
					"[Cerberus Helpdesk] URL-Rewrite: %s\n".
					"\n".
					"[Privs] libs/devblocks/tmp: %s\n".
					"[Privs] libs/devblocks/tmp/templates_c: %s\n".
					"[Privs] libs/devblocks/tmp/cache: %s\n".
					"[Privs] storage/attachments: %s\n".
					"[Privs] storage/mail/new: %s\n".
					"[Privs] storage/mail/fail: %s\n".
					"[Privs] storage/indexes: %s\n".
					"\n".
					"[PHP] Version: %s\n".
					"[PHP] OS: %s\n".
					"[PHP] SAPI: %s\n".
					"\n".
					"[php.ini] safe_mode: %s\n".
					"[php.ini] max_execution_time: %s\n".
					"[php.ini] memory_limit: %s\n".
					"[php.ini] file_uploads: %s\n".
					"[php.ini] upload_max_filesize: %s\n".
					"[php.ini] post_max_size: %s\n".
					"\n".
					"[PHP:Extension] MySQL: %s\n".
					"[PHP:Extension] PostgreSQL: %s\n".
					"[PHP:Extension] MailParse: %s\n".
					"[PHP:Extension] IMAP: %s\n".
					"[PHP:Extension] Session: %s\n".
					"[PHP:Extension] PCRE: %s\n".
					"[PHP:Extension] GD: %s\n".
					"[PHP:Extension] mbstring: %s\n".
					"[PHP:Extension] XML: %s\n".
					"[PHP:Extension] SimpleXML: %s\n".
					"[PHP:Extension] DOM: %s\n".
					"[PHP:Extension] SPL: %s\n".
					"\n".
					'%s',
					APP_BUILD,
					PLATFORM_BUILD,
					(file_exists(APP_PATH . '/.htaccess') ? 'YES' : 'NO'),
					substr(sprintf('%o', fileperms(DEVBLOCKS_PATH.'tmp')), -4),
					substr(sprintf('%o', fileperms(DEVBLOCKS_PATH.'tmp/templates_c')), -4),
					substr(sprintf('%o', fileperms(DEVBLOCKS_PATH.'tmp/cache')), -4),
					substr(sprintf('%o', fileperms(APP_PATH.'/storage/attachments')), -4),
					substr(sprintf('%o', fileperms(APP_PATH.'/storage/mail/new')), -4),
					substr(sprintf('%o', fileperms(APP_PATH.'/storage/mail/fail')), -4),
					substr(sprintf('%o', fileperms(APP_PATH.'/storage/indexes')), -4),
					PHP_VERSION,
					PHP_OS . ' (' . php_uname() . ')',
					php_sapi_name(),
					ini_get('safe_mode'),
					ini_get('max_execution_time'),
					ini_get('memory_limit'),
					ini_get('file_uploads'),
					ini_get('upload_max_filesize'),
					ini_get('post_max_size'),
					(extension_loaded("mysql") ? 'YES' : 'NO'),
					(extension_loaded("pgsql") ? 'YES' : 'NO'),
					(extension_loaded("mailparse") ? 'YES' : 'NO'),
					(extension_loaded("imap") ? 'YES' : 'NO'),
					(extension_loaded("session") ? 'YES' : 'NO'),
					(extension_loaded("pcre") ? 'YES' : 'NO'),
					(extension_loaded("gd") ? 'YES' : 'NO'),
					(extension_loaded("mbstring") ? 'YES' : 'NO'),
					(extension_loaded("xml") ? 'YES' : 'NO'),
					(extension_loaded("simplexml") ? 'YES' : 'NO'),
					(extension_loaded("dom") ? 'YES' : 'NO'),
					(extension_loaded("spl") ? 'YES' : 'NO'),
					''
				);
				
				if(!empty($settings)) {
					$report_output .= sprintf(
						"[Setting] HELPDESK_TITLE: %s\n".
						"[Setting] DEFAULT_REPLY_FROM: %s\n".
						"[Setting] DEFAULT_REPLY_PERSONAL: %s\n".
						"[Setting] SMTP_HOST: %s\n".
						"[Setting] SMTP_PORT: %s\n".
						"[Setting] SMTP_ENCRYPTION_TYPE: %s\n".
						"\n".
						'%s',
						$settings->get(CerberusSettings::HELPDESK_TITLE,''),
						str_replace(array('@','.'),array(' at ',' dot '),$settings->get(CerberusSettings::DEFAULT_REPLY_FROM,'')),
						$settings->get(CerberusSettings::DEFAULT_REPLY_PERSONAL,''),
						$settings->get(CerberusSettings::SMTP_HOST,''),
						$settings->get(CerberusSettings::SMTP_PORT,''),
						$settings->get(CerberusSettings::SMTP_ENCRYPTION_TYPE,''),
						''
					);
				}
				
				if(is_array($tables) && !empty($tables)) {
					$report_output .= sprintf(
						"[Stats] # Workers: %s\n".
						"[Stats] # Groups: %s\n".
						"[Stats] # Tickets: %s\n".
						"[Stats] # Messages: %s\n".
						"\n".
						"[Database] Tables:\n * %s\n".
						"\n".
						'%s',
						intval($db->getOne('SELECT count(id) FROM worker')),
						intval($db->getOne('SELECT count(id) FROM team')),
						intval($db->getOne('SELECT count(id) FROM ticket')),
						intval($db->getOne('SELECT count(id) FROM message')),
						implode("\n * ",array_values($tables)),
						''
					);
				}
				
				echo sprintf(
					"<html>
					<head>
						<title></title>
						<style>
							BODY {font-family: Arial, Helvetica, sans-serif; font-size: 12px;}
							FORM {margin:0px; } 
							H1 { margin:0px; }
							.fail {color:red;font-weight:bold;}
							.pass {color:green;font-weight:bold;}
						</style>
					</head>
					<body>
						<form>
							<h1>Cerberus Helpdesk - Debug Report:</h1>
							<textarea rows='25' cols='100'>%s</textarea>
						</form>	
					</body>
					</html>
					",
				$report_output
				);
	    		
				break;
				
	    	default:
	    		$url_service = DevblocksPlatform::getUrlService();
	    		
				echo sprintf(
					"<html>
					<head>
						<title></title>
						<style>
							BODY {font-family: Arial, Helvetica, sans-serif; font-size: 12px;}
							FORM {margin:0px; } 
							H1 { margin:0px; }
						</style>
					</head>
					<body>
						<form>
							<h1>Cerberus Helpdesk - Debug Menu:</h1>
							<ul>
								<li><a href='%s'>Requirements Checker</a></li>
								<li><a href='%s'>Debug Report (for technical support)</a></li>
								<li><a href='%s'>phpinfo()</a></li>
							</ul>
						</form>	
					</body>
					</html>
					"
					,
					$url_service->write('c=debug&a=check'),
					$url_service->write('c=debug&a=report'),
					$url_service->write('c=debug&a=phpinfo')
				);
	    		break;
	    }
	    
		exit;
	}
	
};

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
		
		$tpl->assign('optColumns', $view->getColumns());
		$tpl->assign('view_fields', $view->getFields());
		$tpl->assign('view_searchable_fields', $view->getSearchFields());
		
		C4_AbstractViewLoader::setView($id, $view);
		$tpl->assign('view', $view);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/internal/views/customize_view_criteria.tpl');
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
		$tpl->assign('view_fields', $view->getFields());
		$tpl->assign('view_searchable_fields', $view->getSearchFields());
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/internal/views/customize_view.tpl');
	}
	
	function viewShowCopyAction() {
        @$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');

		$active_worker = CerberusApplication::getActiveWorker();

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(__FILE__) . '/templates/';
		$tpl->assign('path', $tpl_path);
        
        $view = C4_AbstractViewLoader::getView('',$view_id);

		$workspaces = DAO_WorkerWorkspaceList::getWorkspaces($active_worker->id);
		$tpl->assign('workspaces', $workspaces);
        
        $tpl->assign('view_id', $view_id);
		$tpl->assign('view', $view);

        $tpl->display($tpl_path.'internal/views/copy.tpl');
	}
	
	function viewDoCopyAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		
	    @$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView('', $view_id);
	    
		@$list_title = DevblocksPlatform::importGPC($_POST['list_title'],'string', '');
		@$workspace = DevblocksPlatform::importGPC($_POST['workspace'],'string', '');
		@$new_workspace = DevblocksPlatform::importGPC($_POST['new_workspace'],'string', '');
		
		if(empty($workspace) && empty($new_workspace))
			$new_workspace = $translate->_('mail.workspaces.new');
			
		if(empty($list_title))
			$list_title = $translate->_('mail.workspaces.new_list');
		
		$workspace_name = (!empty($new_workspace) ? $new_workspace : $workspace);
		
        // Find the proper workspace source based on the class of the view
        $source_manifests = DevblocksPlatform::getExtensions(Extension_WorkspaceSource::EXTENSION_POINT, false);
        $source_manifest = null;
        if(is_array($source_manifests))
        foreach($source_manifests as $mft) {
        	if(is_a($view, $mft->params['view_class'])) {
				$source_manifest = $mft;       		
        		break;
        	}
        }
		
        if(!is_null($source_manifest)) {
			// View params inside the list for quick render overload
			$list_view = new Model_WorkerWorkspaceListView();
			$list_view->title = $list_title;
			$list_view->num_rows = $view->renderLimit;
			$list_view->columns = $view->view_columns;
			$list_view->params = $view->params;
			// [TODO] Sort order?
			
			// Save the new worklist
			$fields = array(
				DAO_WorkerWorkspaceList::WORKER_ID => $active_worker->id,
				DAO_WorkerWorkspaceList::WORKSPACE => $workspace_name,
				DAO_WorkerWorkspaceList::SOURCE_EXTENSION => $source_manifest->id,
				DAO_WorkerWorkspaceList::LIST_VIEW => serialize($list_view),
				DAO_WorkerWorkspaceList::LIST_POS => 99,
			);
			$list_id = DAO_WorkerWorkspaceList::create($fields);
        }
		
		// Select the workspace tab
		$visit->set(CerberusVisit::KEY_HOME_SELECTED_TAB, 'w_'.$workspace_name);
        
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('home')));
	}

	// Ajax
	function viewShowExportAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['id']);

		$active_worker = CerberusApplication::getActiveWorker();
		if(!$active_worker->is_superuser && !$active_worker->can_export)
			return;
				
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->assign('view_id', $view_id);

		$view = C4_AbstractViewLoader::getView('', $view_id);
		$tpl->assign('view', $view);
		
		$model_columns = $view->getColumns();
		$tpl->assign('model_columns', $model_columns);
		
		$view_columns = $view->view_columns;
		$tpl->assign('view_columns', $view_columns);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/internal/views/view_export.tpl');
	}
	
	function viewDoExportAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);
		@$columns = DevblocksPlatform::importGPC($_REQUEST['columns'],'array',array());
		@$export_as = DevblocksPlatform::importGPC($_REQUEST['export_as'],'string','csv');

		$active_worker = CerberusApplication::getActiveWorker();
		if(!$active_worker->is_superuser && !$active_worker->can_export)
			return;
		
		// Scan through the columns and remove any blanks
		if(is_array($columns))
		foreach($columns as $idx => $col) {
			if(empty($col))
				unset($columns[$idx]);
		}
		
		$view = C4_AbstractViewLoader::getView('', $view_id);
		$column_manifests = $view->getColumns();

		// Override display
		$view->view_columns = $columns;
		$view->renderPage = 0;
		$view->renderLimit = -1;

		if('csv' == $export_as) {
			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Content-Type: text/plain; charset=".LANG_CHARSET_CODE);
			
			// Column headers
			if(is_array($columns)) {
				$cols = array();
				foreach($columns as $col) {
					$cols[] = sprintf("\"%s\"",
						str_replace('"','\"',mb_convert_case($column_manifests[$col]->db_label,MB_CASE_TITLE))
					);
				}
				echo implode(',', $cols) . "\r\n";
			}
			
			// Get data
			list($results, $null) = $view->getData();
			if(is_array($results))
			foreach($results as $row) {
				if(is_array($row)) {
					$cols = array();
					if(is_array($columns))
					foreach($columns as $col) {
						$cols[] = sprintf("\"%s\"",
							str_replace('"','\"',$row[$col])
						);
					}
					echo implode(',', $cols) . "\r\n";
				}
			}
			
		} elseif('xml' == $export_as) {
			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Content-Type: text/plain; charset=".LANG_CHARSET_CODE);
			
			$xml = simplexml_load_string("<results/>"); /* @var $xml SimpleXMLElement */
			
			// Get data
			list($results, $null) = $view->getData();
			if(is_array($results))
			foreach($results as $row) {
				$result =& $xml->addChild("result");
				if(is_array($columns))
				foreach($columns as $col) {
					$field =& $result->addChild("field",htmlspecialchars($row[$col],null,LANG_CHARSET_CODE));
					$field->addAttribute("id", $col);
				}
			}
		
			// Pretty format and output
			$doc = new DOMDocument('1.0');
			$doc->preserveWhiteSpace = false;
			$doc->loadXML($xml->asXML());
			$doc->formatOutput = true;
			echo $doc->saveXML();			
		}
		
		exit;
	}
	
	// Post?
	function viewSaveCustomizeAction() {
		$translate = DevblocksPlatform::getTranslationService();
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);
		@$columns = DevblocksPlatform::importGPC($_REQUEST['columns'],'array', array());
		@$num_rows = DevblocksPlatform::importGPC($_REQUEST['num_rows'],'integer',10);
		
		$num_rows = max($num_rows, 1); // make 1 the minimum
		
		$view = C4_AbstractViewLoader::getView('', $id);
		$view->doCustomize($columns, $num_rows);

		$active_worker = CerberusApplication::getActiveWorker();
		
		// Conditional Persist
		if(substr($id,0,5)=="cust_") { // custom workspace
			$list_view_id = intval(substr($id,5));
			
			// Special custom view fields
			@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string', $translate->_('views.new_list'));
			
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
	
//	function getActivity() {
//		$response = DevblocksPlatform::getHttpResponse();
//		$stack = $response->path;
//		@$id = $stack[1];
//	       
//		$url = DevblocksPlatform::getUrlService();
//		$link = sprintf("<a href='%s'>#%s</a>",
//		    $url->write("c=display&id=".$id),
//		    $id
//		);
//	    return new Model_Activity('activity.display_ticket',array($link));
//	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
		$response = DevblocksPlatform::getHttpResponse();
		$active_worker = CerberusApplication::getActiveWorker();
		$translate = DevblocksPlatform::getTranslationService();
		$url = DevblocksPlatform::getUrlService();
		
		$stack = $response->path;
		@array_shift($stack); // display
		
		@$id = array_shift($stack);
		
		// Tabs
		
		$tab_manifests = DevblocksPlatform::getExtensions('cerberusweb.ticket.tab', false);
		$tpl->assign('tab_manifests', $tab_manifests);

		@$tab_selected = array_shift($stack);
		if(empty($tab_selected)) $tab_selected = 'conversation';
		$tpl->assign('tab_selected', $tab_selected);
		
		switch($tab_selected) {
			case 'conversation':
				@$mail_always_show_all = DAO_WorkerPref::get($active_worker->id,'mail_always_show_all',0);
				@$tab_option = array_shift($stack);
				
				if($mail_always_show_all || 0==strcasecmp("read_all",$tab_option)) {
					$tpl->assign('expand_all', true);
				}
				break;
		}
		
		// [JAS]: Translate Masks
		if(!is_numeric($id)) {
			$id = DAO_Ticket::getTicketIdByMask($id);
		}
		$ticket = DAO_Ticket::getTicket($id);
	
		if(empty($ticket)) {
			echo "<H1>".$translate->_('display.invalid_ticket')."</H1>";
			return;
		}

		// Permissions 
		
		$active_worker_memberships = $active_worker->getMemberships();
		
		// Check group membership ACL
		if(!isset($active_worker_memberships[$ticket->team_id])) {
			echo "<H1>".$translate->_('common.access_denied')."</H1>";
			return;
		}
		
		$tpl->assign('ticket', $ticket);

		// TicketToolbarItem Extensions
		$ticketToolbarItems = DevblocksPlatform::getExtensions('cerberusweb.ticket.toolbaritem', true);
		if(!empty($ticketToolbarItems))
			$tpl->assign('ticket_toolbaritems', $ticketToolbarItems);
		
		// Next+Prev: Does a series exist?
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
		
		// Tasks Total [TODO] Eventually this can be ticket.num_tasks
		$tasks_total = DAO_Task::getCountBySourceObjectId('cerberusweb.tasks.ticket',$id);
		$tpl->assign('tasks_total', $tasks_total);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);
		
		$team_categories = DAO_Bucket::getTeams();
		$tpl->assign('team_categories', $team_categories);
		
		// Log Activity
		DAO_Worker::logActivity(
			$active_worker->id,
			new Model_Activity('activity.display_ticket',array(
				sprintf("<a href='%s' title='[%s] %s'>#%s</a>",
		    		$url->write("c=display&id=".$ticket->mask),
		    		htmlspecialchars(@$teams[$ticket->team_id]->name, ENT_QUOTES, LANG_CHARSET_CODE),
		    		htmlspecialchars($ticket->subject, ENT_QUOTES, LANG_CHARSET_CODE),
		    		$ticket->mask
		    	)
			))
		);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/index.tpl');
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
	function handleTabActionAction() {
	}
	*/
	
	function browseAction() {
		$translate = DevblocksPlatform::getTranslationService();
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
			echo "<H1>".$translate->_('display.invalid_ticket')."</H1>";
			return;
		}
		
		// Display series support (inherited paging from Display)
		@$view_id = array_shift($stack);
		if(!empty($view_id)) {
			$view = C4_AbstractViewLoader::getView('',$view_id);

			// Restrict to the active worker's groups
			$active_worker = CerberusApplication::getActiveWorker();
			$memberships = $active_worker->getMemberships();
			$view->params['tmp'] = new DevblocksSearchCriteria(SearchFields_Ticket::TEAM_ID, 'in', array_keys($memberships)); 
			
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
		
		// Sender info
		$message_senders = array();
		$message_sender_orgs = array();
		
		if(null != ($sender_addy = DAO_Address::get($message->address_id))) {
			$message_senders[$sender_addy->id] = $sender_addy;
			
			if(null != $sender_org = DAO_ContactOrg::get($sender_addy->contact_org_id)) {
				$message_sender_orgs[$sender_org->id] = $sender_org;
			}
		}

		$tpl->assign('message_senders', $message_senders);
		$tpl->assign('message_sender_orgs', $message_sender_orgs);
		
		// Workers
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		// Ticket
		$ticket = DAO_Ticket::getTicket($message->ticket_id);
		$tpl->assign('ticket', $ticket);
		$tpl->assign('requesters', $ticket->getRequesters());
		
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

		// Message toolbar items
		$messageToolbarItems = DevblocksPlatform::getExtensions('cerberusweb.message.toolbaritem', true);
		if(!empty($messageToolbarItems))
			$tpl->assign('message_toolbaritems', $messageToolbarItems);
		
		// [TODO] Workers?
		
		$tpl->assign('expanded', (empty($hide) ? true : false));
		
		$tpl->register_modifier('makehrefs', array('CerberusUtils', 'smarty_modifier_makehrefs')); 
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/conversation/message.tpl');
	}

	function updatePropertiesAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']); // ticket id
		@$closed = DevblocksPlatform::importGPC($_REQUEST['closed'],'integer',0);
		@$spam = DevblocksPlatform::importGPC($_REQUEST['spam'],'integer',0);
		@$deleted = DevblocksPlatform::importGPC($_REQUEST['deleted'],'integer',0);
		@$bucket = DevblocksPlatform::importGPC($_REQUEST['bucket_id'],'string');
		@$next_worker_id = DevblocksPlatform::importGPC($_REQUEST['next_worker_id'],'integer',0);
		@$unlock_date = DevblocksPlatform::importGPC($_REQUEST['unlock_date'],'integer',0);
		
		@$ticket = DAO_Ticket::getTicket($id);
		
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
		);

		// Undeleting?
		if(empty($spam) && empty($closed) && empty($deleted) 
			&& $ticket->spam_training == CerberusTicketSpamTraining::SPAM && $ticket->is_closed) {
				$score = CerberusBayes::calculateTicketSpamProbability($id);
				$properties[DAO_Ticket::SPAM_SCORE] = $score['probability']; 
				$properties[DAO_Ticket::SPAM_TRAINING] = CerberusTicketSpamTraining::BLANK;
		}
		
		// Team/Category
		if(!empty($bucket)) {
			list($team_id,$bucket_id) = CerberusApplication::translateTeamCategoryCode($bucket);

			if(!empty($team_id)) {
			    $properties[DAO_Ticket::TEAM_ID] = $team_id;
			    $properties[DAO_Ticket::CATEGORY_ID] = $bucket_id;
			}
		}
		
		if($next_worker_id != $ticket->next_worker_id) {
			$properties[DAO_Ticket::NEXT_WORKER_ID] = $next_worker_id;
		}
		
		// Reset the unlock date (next worker "until")
		$properties[DAO_Ticket::UNLOCK_DATE] = $unlock_date;
		
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
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/conversation/notes.tpl');
	}
	
	function addNoteAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->assign('id',$id);
		
		$message = DAO_Ticket::getMessage($id);
		$ticket = DAO_Ticket::getTicket($message->ticket_id);
		$tpl->assign('message',$message);
		$tpl->assign('ticket',$ticket);
		
		$worker = CerberusApplication::getActiveWorker();
		$tpl->assign('worker', $worker);
		
		$active_workers = DAO_Worker::getAllActive();
		$tpl->assign('active_workers', $active_workers);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/rpc/add_note.tpl');
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

		if(null != ($ticket = DAO_Ticket::getTicket($ticket_id))) {
			
			// Notifications
			$url_writer = DevblocksPlatform::getUrlService();
			@$notify_worker_ids = DevblocksPlatform::importGPC($_REQUEST['notify_worker_ids'],'array',array());
			if(is_array($notify_worker_ids) && !empty($notify_worker_ids))
			foreach($notify_worker_ids as $notify_worker_id) {
				$fields = array(
					DAO_WorkerEvent::CREATED_DATE => time(),
					DAO_WorkerEvent::WORKER_ID => $notify_worker_id,
					DAO_WorkerEvent::URL => $url_writer->write('c=display&id='.$ticket->mask,true),
					DAO_WorkerEvent::TITLE => 'New Ticket Note', // [TODO] Translate
					DAO_WorkerEvent::CONTENT => sprintf("#%s: %s\n%s notes: %s", $ticket->mask, $ticket->subject, $worker->getName(), $content), // [TODO] Translate
					DAO_WorkerEvent::IS_READ => 0,
				);
				DAO_WorkerEvent::create($fields);
			}
		}
		
		$this->_renderNotes($id);
	}
	
	function deleteNoteAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$note = DAO_MessageNote::get($id);
		$message_id = $note->message_id;
		DAO_MessageNote::delete($id);
		
		$this->_renderNotes($message_id);
	}
	
	function discardAndSurrenderAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer',0);

		$active_worker = CerberusApplication::getActiveWorker();
		
		$fields = array(
			DAO_Ticket::NEXT_WORKER_ID => 0, // anybody
			DAO_Ticket::UNLOCK_DATE => 0,
		);
		
		DAO_Ticket::updateWhere($fields, sprintf("%s = %d AND %s = %d",
			DAO_Ticket::ID,
			$ticket_id,
			DAO_Ticket::NEXT_WORKER_ID,
			$active_worker->id
		));
	}
	
	function replyAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$is_forward = DevblocksPlatform::importGPC($_REQUEST['forward'],'integer',0);

		$settings = CerberusSettings::getInstance();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		$tpl->assign('id',$id);
		$tpl->assign('is_forward',$is_forward);
		
		$message = DAO_Ticket::getMessage($id);
		$tpl->assign('message',$message);
		
		$ticket = DAO_Ticket::getTicket($message->ticket_id);
		$tpl->assign('ticket',$ticket);

		// ReplyToolbarItem Extensions
		$replyToolbarItems = DevblocksPlatform::getExtensions('cerberusweb.reply.toolbaritem', true);
		if(!empty($replyToolbarItems))
			$tpl->assign('reply_toolbaritems', $replyToolbarItems);
		
		// Show attachments for forwarded messages
		if($is_forward) {
			$forward_attachments = $message->getAttachments();
			$tpl->assign('forward_attachments', $forward_attachments);
		}
		
		$workers = DAO_Worker::getAllActive();
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
		
		$kb_topics = DAO_KbCategory::getWhere(sprintf("%s = %d",
			DAO_KbCategory::PARENT_ID,
			0
		));
		$tpl->assign('kb_topics', $kb_topics);
		
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/rpc/reply.tpl');
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
		    'unlock_date' => DevblocksPlatform::importGPC(@$_REQUEST['unlock_date'],'string',''),
		    'agent_id' => @$worker->id,
		    'forward_files' => DevblocksPlatform::importGPC(@$_REQUEST['forward_files'],'array',array()),
		);
		
		CerberusMail::sendTicketMessage($properties);

        DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('display',$ticket_id)));
	}
	
	function showConversationAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		@$expand_all = DevblocksPlatform::importGPC($_REQUEST['expand_all'],'integer','0');

		@$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$tpl->assign('expand_all', $expand_all);
		
		$ticket = DAO_Ticket::getTicket($id);
		$tpl->assign('ticket', $ticket);
		$tpl->assign('requesters', $ticket->getRequesters());

		$messages = $ticket->getMessages();
		
		arsort($messages);
				
		$tpl->assign('latest_message_id',key($messages));
		$tpl->assign('messages', $messages);

		// Thread comments and messages on the same level
		$convo_timeline = array();

		// Track senders and their orgs
		$message_senders = array();
		$message_sender_orgs = array();

		// Loop messages
		foreach($messages as $message_id => $message) { /* @var $message CerberusMessage */
			$key = $message->created_date . '_m' . $message_id;
			// build a chrono index of messages
			$convo_timeline[$key] = array('m',$message_id);
			
			// If we haven't cached this sender address yet
			if(!isset($message_senders[$message->address_id])) {
				if(null != ($sender_addy = DAO_Address::get($message->address_id))) {
					$message_senders[$sender_addy->id] = $sender_addy;	

					// If we haven't cached this sender org yet
					if(!isset($message_sender_orgs[$sender_addy->contact_org_id])) {
						if(null != ($sender_org = DAO_ContactOrg::get($sender_addy->contact_org_id))) {
							$message_sender_orgs[$sender_org->id] = $sender_org;
						}
					}
				}
			}
		}
		
		$tpl->assign('message_senders', $message_senders);
		$tpl->assign('message_sender_orgs', $message_sender_orgs);
		
		@$mail_inline_comments = DAO_WorkerPref::get($active_worker->id,'mail_inline_comments',1);
		
		if($mail_inline_comments) { // if inline comments are enabled
			$comments = DAO_TicketComment::getByTicketId($id);
			arsort($comments);
			$tpl->assign('comments', $comments);
			
			// build a chrono index of comments
			foreach($comments as $comment_id => $comment) { /* @var $comment Model_TicketComment */
				$key = $comment->created . '_c' . $comment_id;
				$convo_timeline[$key] = array('c',$comment_id);
			}
		}
		
		// sort the timeline
		if(!$expand_all) {
			krsort($convo_timeline);
		} else {
			ksort($convo_timeline);
		}
		$tpl->assign('convo_timeline', $convo_timeline);
		
		// Message Notes
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
		
		// Message toolbar items
		$messageToolbarItems = DevblocksPlatform::getExtensions('cerberusweb.message.toolbaritem', true);
		if(!empty($messageToolbarItems))
			$tpl->assign('message_toolbaritems', $messageToolbarItems);

		// Workers
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$tpl->register_modifier('makehrefs', array('CerberusUtils', 'smarty_modifier_makehrefs')); 
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/conversation/index.tpl');
	}
	
	function showCommentsAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$tpl->assign('ticket_id', $ticket_id);
		
		$ticket = DAO_Ticket::getTicket($ticket_id);
		$tpl->assign('ticket', $ticket);

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

		$active_workers = DAO_Worker::getAllActive();
		$tpl->assign('active_workers', $active_workers);
		
		$comments = DAO_TicketComment::getByTicketId($ticket_id);
		arsort($comments);
		$tpl->assign('comments', $comments);

		// Comment parent addresses
		$comment_addresses = array();
		foreach($comments as $comment) { /* @var $comment Model_TicketComment */
			$address_id = intval($comment->address_id);
			if(!isset($comment_addresses[$address_id])) {
				$address = DAO_Address::get($address_id);
				$comment_addresses[$address_id] = $address;
			}
		}
		$tpl->assign('comment_addresses', $comment_addresses);
		
		$tpl->register_modifier('makehrefs', array('CerberusUtils', 'smarty_modifier_makehrefs'));		
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/comments/index.tpl');
	}
	
	function saveCommentAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');
		@$comment = DevblocksPlatform::importGPC($_REQUEST['comment'],'string','');
		
		// Worker is logged in
		if(null === ($active_worker = CerberusApplication::getActiveWorker()))
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$ticket_id)));
		
		$worker_email = $active_worker->email;
		
		// Worker address exists
		if(null === ($address = CerberusApplication::hashLookupAddress($active_worker->email,true)))
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$ticket_id)));
		
		// Form was filled in
		if(empty($ticket_id) || empty($comment))
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$ticket_id)));
			
		$fields = array(
			DAO_TicketComment::CREATED => time(),
			DAO_TicketComment::TICKET_ID => $ticket_id,
			DAO_TicketComment::ADDRESS_ID => $address->id,
			DAO_TicketComment::COMMENT => $comment,
		);
		$comment_id = DAO_TicketComment::create($fields);
		
		@$ticket = DAO_Ticket::getTicket($ticket_id);
		
		// Notifications
		$url_writer = DevblocksPlatform::getUrlService();
		@$notify_worker_ids = DevblocksPlatform::importGPC($_REQUEST['notify_worker_ids'],'array',array());
		if(is_array($notify_worker_ids) && !empty($notify_worker_ids))
		foreach($notify_worker_ids as $notify_worker_id) {
			$fields = array(
				DAO_WorkerEvent::CREATED_DATE => time(),
				DAO_WorkerEvent::WORKER_ID => $notify_worker_id,
				DAO_WorkerEvent::URL => $url_writer->write('c=display&id='.$ticket->mask,true),
				DAO_WorkerEvent::TITLE => 'New Ticket Comment', // [TODO] Translate
				DAO_WorkerEvent::CONTENT => sprintf("#%s: %s\n%s comments: %s", $ticket->mask, $ticket->subject, $active_worker->getName(), $comment), // [TODO] Translate
				DAO_WorkerEvent::IS_READ => 0,
			);
			DAO_WorkerEvent::create($fields);
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$ticket->mask,'comments')));
	}
	
	function deleteCommentAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer',0);
		@$comment_id = DevblocksPlatform::importGPC($_REQUEST['comment_id'],'integer',0);
		
//		@$worker_id = CerberusApplication::getActiveWorker()->id;
		
		if(empty($ticket_id) || empty($comment_id)) // empty($worker_id) || 
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('display',$ticket_id)));
		
		@$active_worker = CerberusApplication::getActiveWorker();

		$comment = DAO_TicketComment::get($comment_id);
		
		if(!empty($active_worker) && ($active_worker->is_superuser || $comment->getAddress()->email==$active_worker->email))
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
		
		$workers = DAO_Worker::getAllActive();
		$tpl->assign('workers', $workers);
		
		// Groups (for custom fields)
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);

		// Custom fields
		$fields = DAO_CustomField::getBySource(ChCustomFieldSource_Ticket::ID);
		$tpl->assign('ticket_fields', $fields);
		
		$field_values = DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Ticket::ID, $ticket_id);
		
		if(isset($field_values[$ticket->id]))
			$tpl->assign('ticket_field_values', $field_values[$ticket->id]);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/properties/index.tpl');
	}
	
	// Post
	function savePropertiesAction() {
		@$ticket_id = DevblocksPlatform::importGPC($_POST['ticket_id'],'integer',0);
		@$add = DevblocksPlatform::importGPC($_POST['add'],'string','');
		@$remove = DevblocksPlatform::importGPC($_POST['remove'],'array',array());
		@$next_worker_id = DevblocksPlatform::importGPC($_POST['next_worker_id'],'integer',0);
		@$next_action = DevblocksPlatform::importGPC($_POST['next_action'],'string','');
		@$ticket_reopen = DevblocksPlatform::importGPC($_POST['ticket_reopen'],'string','');
		@$unlock_date = DevblocksPlatform::importGPC($_POST['unlock_date'],'string','');
		@$subject = DevblocksPlatform::importGPC($_POST['subject'],'string','');
		@$closed = DevblocksPlatform::importGPC($_POST['closed'],'closed',0);
		
		@$ticket = DAO_Ticket::getTicket($ticket_id);
		
		if(empty($ticket_id) || empty($ticket))
			return;
		
		$fields = array();
		
		// Properties

		if(empty($next_worker_id))
			$unlock_date = "";
		
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
					
					if(isset($ticket_reopen)) {
						@$time = intval(strtotime($ticket_reopen));
						$fields[DAO_Ticket::DUE_DATE] = $time;
					}
					break;
				case 2: // waiting
					$fields[DAO_Ticket::IS_WAITING] = 1;
					$fields[DAO_Ticket::IS_CLOSED] = 0;
					$fields[DAO_Ticket::IS_DELETED] = 0;
					
					if(isset($ticket_reopen)) {
						@$time = intval(strtotime($ticket_reopen));
						$fields[DAO_Ticket::DUE_DATE] = $time;
					}
					break;
				case 3: // deleted
					$fields[DAO_Ticket::IS_WAITING] = 0;
					$fields[DAO_Ticket::IS_CLOSED] = 1;
					$fields[DAO_Ticket::IS_DELETED] = 1;
					$fields[DAO_Ticket::DUE_DATE] = 0;
					break;
			}
		}
			
		if(isset($next_worker_id))
			$fields[DAO_Ticket::NEXT_WORKER_ID] = $next_worker_id;
			
		if(isset($next_action))
			$fields[DAO_Ticket::NEXT_ACTION] = $next_action;

		if(isset($unlock_date)) {
			@$time = intval(strtotime($unlock_date));
			$fields[DAO_Ticket::UNLOCK_DATE] = $time;
		}

		if(!empty($subject))
			$fields[DAO_Ticket::SUBJECT] = $subject;

		if(!empty($fields)) {
			DAO_Ticket::updateTicket($ticket_id, $fields);
		}

		// Custom field saves
		@$field_ids = DevblocksPlatform::importGPC($_POST['field_ids'], 'array', array());
		DAO_CustomFieldValue::handleFormPost(ChCustomFieldSource_Ticket::ID, $ticket_id, $field_ids);
		
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
		
		// Copy all the original tickets requesters
		$orig_requesters = DAO_Ticket::getRequestersByTicket($orig_ticket->id);
		foreach($orig_requesters as $orig_req_id => $orig_req_addy) {
			DAO_Ticket::createRequester($orig_req_id, $new_ticket_id);
		}
		
		// Pull the message off the ticket (reparent)
		unset($messages[$orig_message->id]);
		
		DAO_Message::update($orig_message->id,array(
			DAO_Message::TICKET_ID => $new_ticket_id
		));
		
		//[mdf] [CHD-979] The ticket id is also in the message_header table, so update those too
		$message_headers = DAO_MessageHeader::getAll($orig_message->id);
		foreach($message_headers as $hk => $hv) {
		    DAO_MessageHeader::create($orig_message->id, $new_ticket_id, $hk, $hv);
		}		
		
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
		$translate = DevblocksPlatform::getTranslationService();
		
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$ticket = DAO_Ticket::getTicket($ticket_id);
		$requesters = $ticket->getRequesters();
		
		$contact = DAO_Address::get($ticket->first_wrote_address_id);
		$tpl->assign('contact', $contact);
		
		$visit = CerberusApplication::getVisit(); /* @var $visit CerberusVisit */
		$view = C4_AbstractViewLoader::getView('','contact_history');
		
		if(null == $view) {
			$view = new C4_TicketView();
			$view->id = 'contact_history';
			$view->name = $translate->_('addy_book.history.view.title');
			$view->view_columns = array(
				SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
				SearchFields_Ticket::TICKET_CREATED_DATE,
				SearchFields_Ticket::TEAM_NAME,
				SearchFields_Ticket::TICKET_CATEGORY_ID,
				SearchFields_Ticket::TICKET_NEXT_ACTION,
			);
			$view->params = array(
			);
			$view->renderLimit = 10;
			$view->renderPage = 0;
			$view->renderSortBy = SearchFields_Ticket::TICKET_CREATED_DATE;
			$view->renderSortAsc = false;
		}

		$view->name = vsprintf($translate->_('addy_book.history.view.requester'), intval(count($requesters)));
		$view->params = array(
			SearchFields_Ticket::REQUESTER_ID => new DevblocksSearchCriteria(SearchFields_Ticket::REQUESTER_ID,'in',array_keys($requesters)),
			SearchFields_Ticket::TICKET_DELETED => new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_DELETED,DevblocksSearchCriteria::OPER_EQ,0)
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
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/history/index.tpl');
	}

	function showTasksAction() {
		$translate = DevblocksPlatform::getTranslationService();
		
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer');

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$ticket = DAO_Ticket::getTicket($ticket_id);
		$tpl->assign('ticket', $ticket);
		
		$view = C4_AbstractViewLoader::getView('C4_TaskView', 'ticket_tasks');
		$view->id = 'ticket_tasks';
		$view->name = $translate->_('tasks.ticket.tab.view');
		$view->view_columns = array(
			SearchFields_Task::SOURCE_EXTENSION,
			SearchFields_Task::PRIORITY,
			SearchFields_Task::DUE_DATE,
			SearchFields_Task::WORKER_ID,
			SearchFields_Task::COMPLETED_DATE,
		);
		$view->params = array(
			SearchFields_Task::SOURCE_EXTENSION => new DevblocksSearchCriteria(SearchFields_Task::SOURCE_EXTENSION,'=','cerberusweb.tasks.ticket'),
			SearchFields_Task::SOURCE_ID => new DevblocksSearchCriteria(SearchFields_Task::SOURCE_ID,'=',$ticket_id),
			SearchFields_Task::IS_COMPLETED => new DevblocksSearchCriteria(SearchFields_Task::IS_COMPLETED,'=',0),
		);
		$tpl->assign('view', $view);
		
		C4_AbstractViewLoader::setView($view->id, $view);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/tasks/index.tpl');
	}

	// Ajax
	function showTemplatesPanelAction() {
		@$txt_name = DevblocksPlatform::importGPC($_REQUEST['txt_name'],'string','');
		@$reply_id = DevblocksPlatform::importGPC($_REQUEST['reply_id'],'integer');
		@$type = DevblocksPlatform::importGPC($_REQUEST['type'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$tpl->assign('reply_id', $reply_id);
		$tpl->assign('txt_name', $txt_name);
		$tpl->assign('type', $type);
		
		$folders = DAO_MailTemplate::getFolders($type);
		$tpl->assign('folders', $folders);

		$where = null;
		if(empty($folder)) {
			$where = sprintf("%s = %d",
				DAO_MailTemplate::TEMPLATE_TYPE,
				$type
			);
		} 
		
		$templates = DAO_MailTemplate::getWhere($where);
		$tpl->assign('templates', $templates);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/rpc/email_templates/templates_panel.tpl');
	}
	
	// Ajax
	function showTemplateEditPanelAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
//		@$txt_name = DevblocksPlatform::importGPC($_REQUEST['txt_name'],'string','');		
		@$reply_id = DevblocksPlatform::importGPC($_REQUEST['reply_id'],'integer');
		@$type = DevblocksPlatform::importGPC($_REQUEST['type'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$tpl->assign('reply_id', $reply_id);
//		$tpl->assign('txt_name', $txt_name);
		$tpl->assign('type', $type);
		
		$folders = DAO_MailTemplate::getFolders($type);
		$tpl->assign('folders', $folders);
		
		$template = DAO_MailTemplate::get($id);
		$tpl->assign('template', $template);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/rpc/email_templates/template_edit_panel.tpl');
	}
	
	// Ajax
	function saveReplyTemplateAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string','');
		@$description = DevblocksPlatform::importGPC($_REQUEST['description'],'string','');
		@$folder = DevblocksPlatform::importGPC($_REQUEST['folder'],'string','');
		@$folder_new = DevblocksPlatform::importGPC($_REQUEST['folder_new'],'string','');
		@$content = DevblocksPlatform::importGPC($_REQUEST['template'],'string','');
		@$type = DevblocksPlatform::importGPC($_REQUEST['type'],'integer',0);
		@$delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
		
		$worker = CerberusApplication::getActiveWorker();
		
		if(empty($delete)) {
			$fields = array(
				DAO_MailTemplate::TITLE => $title,
				DAO_MailTemplate::FOLDER => (!empty($folder)?$folder:$folder_new),
				DAO_MailTemplate::DESCRIPTION => $description,
				DAO_MailTemplate::CONTENT => $content,
				DAO_MailTemplate::TEMPLATE_TYPE => $type,
				DAO_MailTemplate::OWNER_ID => $worker->id,
			);
			
			if(empty($id)) { // new
				$id = DAO_MailTemplate::create($fields);
				
			} else { // edit
				DAO_MailTemplate::update($id, $fields);			
				
			}
			
		} else { // delete
			DAO_MailTemplate::delete($id);
		}
		
	}
	
	// Ajax
	function getTemplateAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		@$reply_id = DevblocksPlatform::importGPC($_REQUEST['reply_id'],'integer');

		$template = DAO_MailTemplate::get($id);
		echo $template->getRenderedContent($reply_id);
	}

	// Ajax
	function getTemplatesAction() {
		@$txt_name = DevblocksPlatform::importGPC($_REQUEST['txt_name'],'string','');
		@$reply_id = DevblocksPlatform::importGPC($_REQUEST['reply_id'],'integer');
		@$folder = DevblocksPlatform::importGPC($_REQUEST['folder'],'string','');
		@$type = DevblocksPlatform::importGPC($_REQUEST['type'],'integer',0);
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$tpl->assign('reply_id', $reply_id);
		$tpl->assign('txt_name', $txt_name);
		
		if(empty($folder)) {
			$where = sprintf("%s = %d",
				DAO_MailTemplate::TEMPLATE_TYPE,
				$type
			);
		} else {
			$where = sprintf("%s = %s AND %s = %d ",
				DAO_MailTemplate::FOLDER,
				$db->qstr($folder),
				DAO_MailTemplate::TEMPLATE_TYPE,
				$type
			);
		} 
		
		$templates = DAO_MailTemplate::getWhere($where);
		$tpl->assign('templates', $templates);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/rpc/email_templates/template_results.tpl');
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
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/rpc/requester_panel.tpl');
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
	
	function doReplyKbSearchAction() {
		@$q = DevblocksPlatform::importGPC($_REQUEST['q'], 'string', '');
		@$topic_id = DevblocksPlatform::importGPC($_REQUEST['topic_id'], 'integer', 0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		$tpl->assign('query', $q);

		// Query
		$params = array(
			array(
				DevblocksSearchCriteria::GROUP_OR,
				new DevblocksSearchCriteria(SearchFields_KbArticle::TITLE,DevblocksSearchCriteria::OPER_FULLTEXT,$q),
				new DevblocksSearchCriteria(SearchFields_KbArticle::CONTENT,DevblocksSearchCriteria::OPER_FULLTEXT,$q)
			)
		);
		
		// Limiting topics?
		if(!empty($topic_id)) {
			$params[SearchFields_KbArticle::TOP_CATEGORY_ID] = new DevblocksSearchCriteria(SearchFields_KbArticle::TOP_CATEGORY_ID,'=', $topic_id);
		}
		
		list($results, $null) = DAO_KbArticle::search(
			$params,
			25,
			0,
			null,
			null,
			false
		);
		
		$tpl->assign('results', $results);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/rpc/reply_kb_results.tpl');
	}
	
	// Ajax
	function showFnrPanelAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$topics = DAO_FnrTopic::getWhere();
		$tpl->assign('topics', $topics);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/rpc/reply_links_panel.tpl');
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
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/modules/fnr/results.tpl');
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
                        $tpl->display("file:${path}/login/forgot1.tpl");
                        break;
                    
                    case "step2":
                        $tpl->display("file:${path}/login/forgot2.tpl");
                        break;
                        
                    case "step3":
                        $tpl->display("file:${path}/login/forgot3.tpl");
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
		        	
					$tour_enabled = intval(DAO_WorkerPref::get($worker->id, 'assist_mode', 1));
					
					// Timezone
					if(null != ($timezone = DAO_WorkerPref::get($worker->id,'timezone'))) {
						$_SESSION['timezone'] = $timezone;
						@date_default_timezone_set($timezone);
					}
					// Language
					if(null != ($lang_code = DAO_WorkerPref::get($worker->id,'locale'))) {
						$_SESSION['locale'] = $lang_code;
						DevblocksPlatform::setLocale($lang_code);
					}
				}
				$next_page = ($tour_enabled) ?  'welcome' : 'home';				
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
					$tour_enabled = intval(DAO_WorkerPref::get($worker->id, 'assist_mode', 1));
				}
				$next_page = ($tour_enabled) ?  'welcome' : 'home';				
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
		$translate = DevblocksPlatform::getTranslationService();
		
	    @$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string');
	    
	    $worker = DAO_Worker::lookupAgentEmail($email);
	    
	    if(empty($email) || empty($worker))
	        return;
	    
	    $_SESSION[self::KEY_FORGOT_EMAIL] = $email;
	    
	    try {
		    $mail_service = DevblocksPlatform::getMailService();
		    $mailer = $mail_service->getMailer(CerberusMail::getMailerDefaults());
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
			$mail->setSubject($translate->_('signin.forgot.mail.subject'));
			$mail->generateId();
			$mail->headers->set('X-Mailer','Cerberus Helpdesk (Build '.APP_BUILD.')');
	
			$mail->attach(new Swift_Message_Part(
				vsprintf($translate->_('signin.forgot.mail.body'), $code),
				'text/plain',
				'base64',
				LANG_CHARSET_CODE
			));
			
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
		$translate = DevblocksPlatform::getTranslationService();
		
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
						
						$output = array(vsprintf($translate->_('prefs.address.confirm.tip'), $worker_address->address));
						$tpl->assign('pref_success', $output);
					
				} else {
					$errors = array($translate->_('prefs.address.confirm.invalid_code'));
					$tpl->assign('pref_errors', $errors);
				}
				
				$tpl->display('file:' . $tpl_path . '/preferences/index.tpl');
				break;
			
		    default:
		    	$tpl->assign('tab', $section);
				$tpl->display('file:' . $tpl_path . '/preferences/index.tpl');
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
	function handleTabActionAction() {
	}
	*/
	
	// Ajax [TODO] This should probably turn into Extension_PreferenceTab
	function showGeneralAction() {
		$locale = DevblocksPlatform::getLocaleService();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(__FILE__) . '/templates';
		$tpl->assign('path', $tpl_path);
		$tpl->cache_lifetime = "0";
		
		$worker = CerberusApplication::getActiveWorker();
		$tpl->assign('worker', $worker);
		
		$tour_enabled = intval(DAO_WorkerPref::get($worker->id, 'assist_mode', 1));
		$tpl->assign('assist_mode', $tour_enabled);

		$mail_inline_comments = DAO_WorkerPref::get($worker->id,'mail_inline_comments',1);
		$tpl->assign('mail_inline_comments', $mail_inline_comments);
		
		$mail_always_show_all = DAO_WorkerPref::get($worker->id,'mail_always_show_all',0);
		$tpl->assign('mail_always_show_all', $mail_always_show_all);
		
		$addresses = DAO_AddressToWorker::getByWorker($worker->id);
		$tpl->assign('addresses', $addresses);
				
		// Timezones
		$timezones = $locale->getTranslationList('TerritoryToTimezone');
		$tpl->assign('timezones', $timezones);
		
		@$server_timezone = date_default_timezone_get();
		$tpl->assign('server_timezone', $server_timezone);
		
		// Languages
		$langs = DAO_Translation::getDefinedLangCodes();
		$tpl->assign('langs', $langs);
		$tpl->assign('selected_language', DAO_WorkerPref::get($worker->id,'locale','en_US')); 
		
		// Date formats
		$date_formats = $locale->getTranslationList('DateTime');
		$tpl->assign('date_formats', $date_formats);
		$tpl->assign('zend_date', new Zend_Date());
		$tpl->assign('current_time', time());
		
		$tpl->display('file:' . $tpl_path . '/preferences/modules/general.tpl');
	}
	
	// Ajax [TODO] This should probably turn into Extension_PreferenceTab
	function showRssAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(__FILE__) . '/templates';
		$tpl->assign('path', $tpl_path);
		$tpl->cache_lifetime = "0";
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$feeds = DAO_ViewRss::getByWorker($active_worker->id);
		$tpl->assign('feeds', $feeds);
		
		$tpl->display('file:' . $tpl_path . '/preferences/modules/rss.tpl');
	}
	
	// Post [TODO] This should probably turn into Extension_PreferenceTab
	function saveDefaultsAction() {
		@$timezone = DevblocksPlatform::importGPC($_REQUEST['timezone'],'string');
		@$lang_code = DevblocksPlatform::importGPC($_REQUEST['lang_code'],'string','en_US');
		@$default_signature = DevblocksPlatform::importGPC($_REQUEST['default_signature'],'string');
		@$default_signature_pos = DevblocksPlatform::importGPC($_REQUEST['default_signature_pos'],'integer',0);
		@$reply_box_height = DevblocksPlatform::importGPC($_REQUEST['reply_box_height'],'integer');
	    
		$worker = CerberusApplication::getActiveWorker();
   		$tpl = DevblocksPlatform::getTemplateService();
   		$pref_errors = array();
   		
   		// Time
   		$_SESSION['timezone'] = $timezone;
   		@date_default_timezone_set($timezone);
   		DAO_WorkerPref::set($worker->id,'timezone',$timezone);
   		
   		// Language
   		$_SESSION['locale'] = $lang_code;
   		DevblocksPlatform::setLocale($lang_code);
   		DAO_WorkerPref::set($worker->id,'locale',$lang_code);
   		
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

		@$mail_inline_comments = DevblocksPlatform::importGPC($_REQUEST['mail_inline_comments'],'integer',0);
		DAO_WorkerPref::set($worker->id, 'mail_inline_comments', $mail_inline_comments);
		
		@$mail_always_show_all = DevblocksPlatform::importGPC($_REQUEST['mail_always_show_all'],'integer',0);
		DAO_WorkerPref::set($worker->id, 'mail_always_show_all', $mail_always_show_all);
		
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
					$pref_errors[] = vsprintf($translate->_('prefs.address.exists'), $new_email);
				}
			} else {
				$pref_errors[] = vsprintf($translate->_('prefs.address.invalid'), $new_email);
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
		$translate = DevblocksPlatform::getTranslationService();
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
			vsprintf($translate->_('prefs.address.confirm.mail.subject'), 
				$settings->get(CerberusSettings::HELPDESK_TITLE)
			),
			vsprintf($translate->_('prefs.address.confirm.mail.body'),
				array(
					$worker->getName(),
					$url_writer->write('c=preferences&a=confirm_email&code='.$code,true)
				)
			)
		);
		
		$output = array(vsprintf($translate->_('prefs.address.confirm.mail.subject'), $to));
		$tpl->assign('pref_success', $output);
	}
	
	// Post [TODO] This should probably turn into Extension_PreferenceTab
	function saveRssAction() {
		@$id = DevblocksPlatform::importGPC($_POST['id']);
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(null != ($feed = DAO_ViewRss::getId($id)) && $feed->worker_id == $active_worker->id) {
			DAO_ViewRss::delete($id);
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('preferences','rss')));
	}
	
};

class ChRssSource_Ticket extends Extension_RssSource {
	function getSourceName() {
		return "Tickets";
	}
	
	function getFeedAsRss($feed) {
        $xmlstr = <<<XML
		<rss version='2.0' xmlns:atom='http://www.w3.org/2005/Atom'>
		</rss>
XML;

        $xml = new SimpleXMLElement($xmlstr);
        $translate = DevblocksPlatform::getTranslationService();
		$url = DevblocksPlatform::getUrlService();

        // Channel
        $channel = $xml->addChild('channel');
        $channel->addChild('title', $feed->title);
        $channel->addChild('link', $url->write('',true));
        $channel->addChild('description', '');
        
        // View
        $view = new C4_TicketView();
        $view->name = $feed->title;
        $view->params = $feed->params['params'];
        $view->renderLimit = 100;
        $view->renderSortBy = $feed->params['sort_by'];
        $view->renderSortAsc = $feed->params['sort_asc'];

        // Results
        list($tickets, $count) = $view->getData();
        
        // [TODO] We should probably be building this feed with Zend Framework for compliance
        
        foreach($tickets as $ticket) {
        	$created = intval($ticket[SearchFields_Ticket::TICKET_UPDATED_DATE]);
            if(empty($created)) $created = time();

            $eItem = $channel->addChild('item');
            
            $escapedSubject = htmlspecialchars($ticket[SearchFields_Ticket::TICKET_SUBJECT],null,LANG_CHARSET_CODE);
            //filter out a couple non-UTF-8 characters (0xC and ESC)
            $escapedSubject = preg_replace("/[]/", '', $escapedSubject);
            $eTitle = $eItem->addChild('title', $escapedSubject);

            $eDesc = $eItem->addChild('description', $this->_getTicketLastAction($ticket));
            
            $link = $url->write('c=display&id='.$ticket[SearchFields_Ticket::TICKET_MASK], true);
            $eLink = $eItem->addChild('link', $link);
            	
            $eDate = $eItem->addChild('pubDate', gmdate('D, d M Y H:i:s T', $created));
            
            $eGuid = $eItem->addChild('guid', md5($escapedSubject . $link . $created));
            $eGuid->addAttribute('isPermaLink', "false");
        }

        return $xml->asXML();
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

class ChRssSource_Task extends Extension_RssSource {
	function getSourceName() {
		return "Tasks";
	}
	
	function getFeedAsRss($feed) {
        $xmlstr = <<<XML
		<rss version='2.0' xmlns:atom='http://www.w3.org/2005/Atom'>
		</rss>
XML;

        $xml = new SimpleXMLElement($xmlstr);
        $translate = DevblocksPlatform::getTranslationService();
		$url = DevblocksPlatform::getUrlService();

        // Channel
        $channel = $xml->addChild('channel');
        $channel->addChild('title', $feed->title);
        $channel->addChild('link', $url->write('',true));
        $channel->addChild('description', '');
        
        // View
        $view = new C4_TaskView();
        $view->name = $feed->title;
        $view->params = $feed->params['params'];
        $view->renderLimit = 100;
        $view->renderSortBy = $feed->params['sort_by'];
        $view->renderSortAsc = $feed->params['sort_asc'];

        // Results
        list($results, $count) = $view->getData();

        $task_sources = DevblocksPlatform::getExtensions('cerberusweb.task.source',true);
        
        // [TODO] We should probably be building this feed with Zend Framework for compliance
        
        foreach($results as $task) {
        	$created = intval($task[SearchFields_Task::DUE_DATE]);
            if(empty($created)) $created = time();

            $eItem = $channel->addChild('item');
            
            $escapedSubject = htmlspecialchars($task[SearchFields_Task::TITLE],null,LANG_CHARSET_CODE);
            //filter out a couple non-UTF-8 characters (0xC and ESC)
            $escapedSubject = preg_replace("/[]/", '', $escapedSubject);
            $eTitle = $eItem->addChild('title', $escapedSubject);

//            $eDesc = $eItem->addChild('description', $this->_getTicketLastAction($ticket));
            $eDesc = $eItem->addChild('description', htmlspecialchars($task[SearchFields_Task::CONTENT],null,LANG_CHARSET_CODE));

            if(isset($task_sources[$task[SearchFields_Task::SOURCE_EXTENSION]]) && isset($task[SearchFields_Task::SOURCE_ID])) {
            	$source_ext =& $task_sources[$task[SearchFields_Task::SOURCE_EXTENSION]]; /* @var $source_ext Extension_TaskSource */
            	$source_ext_info = $source_ext->getSourceInfo($task[SearchFields_Task::SOURCE_ID]);
            	
	            $link = $source_ext_info['url'];
	            $eLink = $eItem->addChild('link', $link);
	            
            } else {
	            $link = $url->write('c=activity&tab=tasks', true);
	            $eLink = $eItem->addChild('link', $link);
            	
            }
            	
            $eDate = $eItem->addChild('pubDate', gmdate('D, d M Y H:i:s T', $created));
            
            $eGuid = $eItem->addChild('guid', md5($escapedSubject . $link . $created));
            $eGuid->addAttribute('isPermaLink', "false");
        }

        return $xml->asXML();
	}
};

class ChRssSource_Notification extends Extension_RssSource {
	function getSourceName() {
		return "Notifications";
	}
	
	function getFeedAsRss($feed) {
        $xmlstr = <<<XML
		<rss version='2.0' xmlns:atom='http://www.w3.org/2005/Atom'>
		</rss>
XML;

        $xml = new SimpleXMLElement($xmlstr);
        $translate = DevblocksPlatform::getTranslationService();
		$url = DevblocksPlatform::getUrlService();

        // Channel
        $channel = $xml->addChild('channel');
        $channel->addChild('title', $feed->title);
        $channel->addChild('link', $url->write('',true));
        $channel->addChild('description', '');
        
        // View
        $view = new C4_WorkerEventView();
        $view->name = $feed->title;
        $view->params = $feed->params['params'];
        $view->renderLimit = 100;
        $view->renderSortBy = $feed->params['sort_by'];
        $view->renderSortAsc = $feed->params['sort_asc'];

        // Results
        list($results, $count) = $view->getData();

        // [TODO] We should probably be building this feed with Zend Framework for compliance
        
        foreach($results as $event) {
        	$created = intval($event[SearchFields_WorkerEvent::CREATED_DATE]);
            if(empty($created)) $created = time();

            $eItem = $channel->addChild('item');
            
            $escapedSubject = htmlspecialchars($event[SearchFields_WorkerEvent::TITLE],null,LANG_CHARSET_CODE);
            //filter out a couple non-UTF-8 characters (0xC and ESC)
            $escapedSubject = preg_replace("/[]/", '', $escapedSubject);
            $eTitle = $eItem->addChild('title', $escapedSubject);

            $eDesc = $eItem->addChild('description', htmlspecialchars($event[SearchFields_WorkerEvent::CONTENT],null,LANG_CHARSET_CODE));

            if(isset($event[SearchFields_WorkerEvent::URL])) {
//	            $link = $event[SearchFields_WorkerEvent::URL];
	            $link = $url->write('c=home&a=redirectRead&id='.$event[SearchFields_WorkerEvent::ID], true);
	            $eLink = $eItem->addChild('link', $link);
	            
            } else {
	            $link = $url->write('c=activity&tab=events', true);
	            $eLink = $eItem->addChild('link', $link);
            	
            }
            	
            $eDate = $eItem->addChild('pubDate', gmdate('D, d M Y H:i:s T', $created));
            
            $eGuid = $eItem->addChild('guid', md5($escapedSubject . $link . $created));
            $eGuid->addAttribute('isPermaLink', "false");
        }

        return $xml->asXML();
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
			'url' => $url->write(sprintf('c=contacts&a=orgs&id=%d',$object_id), true),
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
			'url' => $url->write(sprintf('c=display&mask=%s&tab=tasks',$ticket->mask), true),
		);
	}
};

?>
