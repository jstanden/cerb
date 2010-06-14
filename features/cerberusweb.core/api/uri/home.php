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
class ChHomePage extends CerberusPageExtension {
	const VIEW_MY_EVENTS = 'home_myevents';
	
	private $_TPL_PATH = '';
	
	function __construct($manifest) {
		$this->_TPL_PATH = dirname(dirname(dirname(__FILE__))) . '/templates/';
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
		$tpl->assign('path', $this->_TPL_PATH);

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
		
		$tpl->display('file:' . $this->_TPL_PATH . 'home/index.tpl');
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
		$tpl->assign('path', $this->_TPL_PATH);
		
		// Select tab
		$visit->set(CerberusVisit::KEY_HOME_SELECTED_TAB, 'events');
		
		// My Events
		$myEventsView = C4_AbstractViewLoader::getView(self::VIEW_MY_EVENTS);
		
		$title = vsprintf($translate->_('home.my_notifications.view.title'), $active_worker->getName());
		
		if(null == $myEventsView) {
			$myEventsView = new View_WorkerEvent();
			$myEventsView->id = self::VIEW_MY_EVENTS;
			$myEventsView->name = $title;
			$myEventsView->renderLimit = 25;
			$myEventsView->renderPage = 0;
			$myEventsView->renderSortBy = SearchFields_WorkerEvent::CREATED_DATE;
			$myEventsView->renderSortAsc = 0;
		}

		// Overload criteria
		$myEventsView->name = $title;
		$myEventsView->params = array(
			SearchFields_WorkerEvent::WORKER_ID => new DevblocksSearchCriteria(SearchFields_WorkerEvent::WORKER_ID,'=',$active_worker->id),
			SearchFields_WorkerEvent::IS_READ => new DevblocksSearchCriteria(SearchFields_WorkerEvent::IS_READ,'=',0),
		);
		/*
		 * [TODO] This doesn't need to save every display, but it was possible to 
		 * lose the params in the saved version of the view in the DB w/o recovery.
		 * This should be moved back into the if(null==...) check in a later build.
		 */
		C4_AbstractViewLoader::setView($myEventsView->id,$myEventsView);
		
		$tpl->assign('view', $myEventsView);
		$tpl->display('file:' . $this->_TPL_PATH . 'home/tabs/my_events/index.tpl');
	}
	
	function viewEventsExploreAction() {
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
					'return_url' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_writer->write('c=home&tab=events', true),
					'toolbar_extension_id' => 'cerberusweb.explorer.toolbar.worker_events',
				);
				$models[] = $model; 
				
				$view->renderTotal = false; // speed up subsequent pages
			}
			
			if(is_array($results))
			foreach($results as $event_id => $row) {
				if($event_id==$explore_from)
					$orig_pos = $pos;
				
				$model = new Model_ExplorerSet();
				$model->hash = $hash;
				$model->pos = $pos++;
				$model->params = array(
					'id' => $row[SearchFields_WorkerEvent::ID],
					'url' => $row[SearchFields_WorkerEvent::URL],
				);
				$models[] = $model; 
			}
			
			DAO_ExplorerSet::createFromModels($models);
			
			$view->renderPage++;
			
		} while(!empty($results));
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('explore',$hash,$orig_pos)));
	}	
	
	function showWorkspacesIntroTabAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		
		$tpl->display('file:' . $this->_TPL_PATH . 'home/tabs/workspaces_intro/index.tpl');
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
			SearchFields_Ticket::TICKET_TEAM_ID,
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
	
	function showAddWorkspacePanelAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);

		$source_manifests = DevblocksPlatform::getExtensions(Extension_WorkspaceSource::EXTENSION_POINT, false);
		uasort($source_manifests, create_function('$a, $b', "return strcasecmp(\$a->name,\$b->name);\n"));
		$tpl->assign('sources', $source_manifests);		
		
		$workspaces = DAO_WorkerWorkspaceList::getWorkspaces($active_worker->id);
		$tpl->assign('workspaces', $workspaces);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'home/workspaces/add_workspace_panel.tpl');
	}
	
	function doAddWorkspaceAction() {
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'], 'string', '');
		@$source = DevblocksPlatform::importGPC($_REQUEST['source'], 'string', '');
		@$workspace = DevblocksPlatform::importGPC($_REQUEST['workspace'], 'string', '');
		@$new_workspace = DevblocksPlatform::importGPC($_REQUEST['new_workspace'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();

		// Source extension exists
		if(null != ($source_manifest = DevblocksPlatform::getExtension($source, false))) {
			
			// Class exists
			if(null != (@$class = $source_manifest->params['view_class'])) {

				if(empty($name))
					$name = $source_manifest->name;
				
				// New workspace
				if(!empty($new_workspace))
					$workspace = $new_workspace;
					
				if(empty($workspace))
					$workspace = 'New Workspace';
					
				$view = new $class; /* @var $view C4_AbstractView */ 
					
				// Build the list model
				$list = new Model_WorkerWorkspaceListView();
				$list->title = $name;
				$list->columns = $view->view_columns;
				$list->params = $view->params;
				$list->num_rows = 5;
				$list->sort_by = $view->renderSortBy;
				$list->sort_asc = $view->renderSortAsc;
				
				// Add the worklist
				$fields = array(
					DAO_WorkerWorkspaceList::WORKER_ID => $active_worker->id,
					DAO_WorkerWorkspaceList::LIST_POS => 1,
					DAO_WorkerWorkspaceList::LIST_VIEW => serialize($list),
					DAO_WorkerWorkspaceList::WORKSPACE => $workspace,
					DAO_WorkerWorkspaceList::SOURCE_EXTENSION => $source_manifest->id,
				);
				DAO_WorkerWorkspaceList::create($fields);
				
				// Select the new tab
				$visit->set(CerberusVisit::KEY_HOME_SELECTED_TAB, 'w_'.$workspace);
			}
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('home')));
	}
	
	/**
	 * Open an event, mark it read, and redirect to its URL.
	 * Used by Home->Notifications view.
	 *
	 */
	function redirectReadAction() {
		$worker = CerberusApplication::getActiveWorker();
		
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
			
			DAO_WorkerEvent::clearCountCache($worker->id);

			session_write_close();
			header("Location: " . $event->url);
		}
		exit;
	} 
	
	function doNotificationsMarkReadAction() {
		$worker = CerberusApplication::getActiveWorker();
		
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		@$row_ids = DevblocksPlatform::importGPC($_REQUEST['row_id'],'array',array());

		if(is_array($row_ids) && !empty($row_ids)) {
			DAO_WorkerEvent::updateWhere(
				array(
					DAO_WorkerEvent::IS_READ => 1,
				), 
				sprintf("%s = %d AND %s IN (%s)",
					DAO_WorkerEvent::WORKER_ID,
					$worker->id,
					DAO_WorkerEvent::ID,
					implode(',', $row_ids)
				)
			);
			
			DAO_WorkerEvent::clearCountCache($worker->id);
		}
		
		$myEventsView = C4_AbstractViewLoader::getView($view_id);
		$myEventsView->render();
	}
	
	function explorerEventMarkReadAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);

		$worker = CerberusApplication::getActiveWorker();
		
		if(!empty($id)) {
			DAO_WorkerEvent::updateWhere(
				array(
					DAO_WorkerEvent::IS_READ => 1,
				), 
				sprintf("%s = %d AND %s = %d",
					DAO_WorkerEvent::WORKER_ID,
					$worker->id,
					DAO_WorkerEvent::ID,
					$id
				)
			);
			
			DAO_WorkerEvent::clearCountCache($worker->id);
		}
		
	}
	
	function showWorkspaceTabAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
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
				if(null == ($view = C4_AbstractViewLoader::getView($view_id))) {
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
					$view->renderSortBy = $list_view->sort_by;
					$view->renderSortAsc = $list_view->sort_asc;
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
			new Model_Activity(
				'activity.mail.workspaces',
				array(
					'<i>'.$current_workspace.'</i>'
				)
			)
		);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'home/workspaces/index.tpl');
	}
	
	function showEditWorkspacePanelAction() {
		@$workspace = DevblocksPlatform::importGPC($_REQUEST['workspace'],'string', '');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = $this->_TPL_PATH;
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
		
		$tpl->display('file:' . $this->_TPL_PATH . 'home/workspaces/edit_workspace_panel.tpl');
	}
	
	function doEditWorkspaceAction() {
		@$workspace = DevblocksPlatform::importGPC($_POST['workspace'],'string', '');
		@$rename_workspace = DevblocksPlatform::importGPC($_POST['rename_workspace'],'string', '');
		@$ids = DevblocksPlatform::importGPC($_POST['ids'],'array', array());
		@$names = DevblocksPlatform::importGPC($_POST['names'],'array', array());
		@$pos = DevblocksPlatform::importGPC($_POST['pos'],'array', array());
		@$deletes = DevblocksPlatform::importGPC($_POST['deletes'],'array', array());
		
		$db = DevblocksPlatform::getDatabaseService();
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		
		$worklists = DAO_WorkerWorkspaceList::getWhere(sprintf("%s = %s",
			DAO_WorkerWorkspaceList::WORKSPACE,
			$db->qstr($workspace)
		));
		
		// Reorder worklists, rename lists, delete lists, on workspace
		if(is_array($ids) && !empty($ids))
		foreach($ids as $idx => $id) {
			if(false !== array_search($id, $deletes)) {
				DAO_WorkerWorkspaceList::delete($id);
				C4_AbstractViewLoader::deleteView('cust_'.$id); // free up a little memory
				
			} else {
				if(!isset($worklists[$id]))
					continue;
					
				$list_view = $worklists[$id]->list_view; /* @var $list_view Model_WorkerWorkspaceListView */
				
				// If the name changed
				if(isset($names[$idx]) && 0 != strcmp($list_view->title,$names[$idx])) {
					$list_view->title = $names[$idx];
				
					// Save the view in the session
					$view = C4_AbstractViewLoader::getView('cust_'.$id);
					$view->name = $list_view->title;
					C4_AbstractViewLoader::setView('cust_'.$id, $view);
				}
					
				DAO_WorkerWorkspaceList::update($id,array(
					DAO_WorkerWorkspaceList::LIST_POS => @intval($pos[$idx]),
					DAO_WorkerWorkspaceList::LIST_VIEW => serialize($list_view),
				));
			}
		}

		// Rename workspace
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

class ChExplorerToolbarWorkerEvents extends Extension_ExplorerToolbar {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest);
	}
	
	function render(Model_ExplorerSet $item) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(dirname(__FILE__))) . '/templates/';
		
		$tpl->assign('item', $item);
		
		$tpl->display('file:'.$tpl_path.'home/renderer/explorer_toolbar.tpl');
	}
};
