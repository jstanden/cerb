<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2011, WebGroup Media LLC
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
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Scott Luther,
 * 		and Jerry Kanoholani. 
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
class ChHomePage extends CerberusPageExtension {
	const VIEW_MY_EVENTS = 'home_myevents';
	
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
		
		$response = DevblocksPlatform::getHttpResponse();

		$tpl = DevblocksPlatform::getTemplateService();

		$visit = CerberusApplication::getVisit();
		if(null == ($selected_tab = @$response->path[1])) {
			$selected_tab = $visit->get(Extension_HomeTab::POINT, '');
		}
		$tpl->assign('selected_tab', $selected_tab);
		
		$tpl->display('devblocks:cerberusweb.core::home/index.tpl');
	}
	
	// Ajax
	function showTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		$visit = CerberusApplication::getVisit();
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_HomeTab) {
				$visit->set(Extension_HomeTab::POINT, $inst->manifest->params['uri']);
				$inst->showTab();
		}
	}
	
	function showMyEventsAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		$tpl = DevblocksPlatform::getTemplateService();

		// Remember tab
		$visit->set(Extension_HomeTab::POINT, 'events');
		
		// My Events
		$defaults = new C4_AbstractViewModel();
		$defaults->id = self::VIEW_MY_EVENTS;
		$defaults->class_name = 'View_WorkerEvent';
		$defaults->renderLimit = 25;
		$defaults->renderPage = 0;
		$defaults->renderSortBy = SearchFields_WorkerEvent::CREATED_DATE;
		$defaults->renderSortAsc = false;
		
		$myEventsView = C4_AbstractViewLoader::getView(self::VIEW_MY_EVENTS, $defaults);
		
		$myEventsView->name = vsprintf($translate->_('home.my_notifications.view.title'), $active_worker->getName());
		
		$myEventsView->addColumnsHidden(array(
			SearchFields_WorkerEvent::ID,
			SearchFields_WorkerEvent::IS_READ,
			SearchFields_WorkerEvent::WORKER_ID,
		));
		
		$myEventsView->addParamsHidden(array(
			SearchFields_WorkerEvent::ID,
			SearchFields_WorkerEvent::IS_READ,
			SearchFields_WorkerEvent::WORKER_ID,
		));
		$myEventsView->addParamsRequired(array(
			SearchFields_WorkerEvent::IS_READ => new DevblocksSearchCriteria(SearchFields_WorkerEvent::IS_READ,'=',0),
			SearchFields_WorkerEvent::WORKER_ID => new DevblocksSearchCriteria(SearchFields_WorkerEvent::WORKER_ID,'=',$active_worker->id),
		));
		
		/*
		 * [TODO] This doesn't need to save every display, but it was possible to 
		 * lose the params in the saved version of the view in the DB w/o recovery.
		 * This should be moved back into the if(null==...) check in a later build.
		 */
		C4_AbstractViewLoader::setView($myEventsView->id, $myEventsView);
		
		$tpl->assign('view', $myEventsView);
		$tpl->display('devblocks:cerberusweb.core::home/tabs/my_events/index.tpl');
	}
	
	function showNotificationsBulkPanelAction() {
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('view_id', $view_id);

	    if(!empty($ids)) {
	        $id_list = DevblocksPlatform::parseCsvString($ids);
	        $tpl->assign('ids', implode(',', $id_list));
	    }
		
		// Custom Fields
		//$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TASK);
		//$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->display('devblocks:cerberusweb.core::home/tabs/my_events/bulk.tpl');
	}
	
	function doNotificationsBulkUpdateAction() {
		// Filter: whole list or check
	    @$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
		$ids = array();
	    
	    // View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		
		// Task fields
		$is_read = trim(DevblocksPlatform::importGPC($_POST['is_read'],'string',''));

		$do = array();
		
		// Do: Mark Read
		if(0 != strlen($is_read))
			$do['is_read'] = $is_read;
			
		// Do: Custom fields
		//$do = DAO_CustomFieldValue::handleBulkPost($do);

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
		
		$tpl->display('devblocks:cerberusweb.core::home/tabs/workspaces_intro/index.tpl');
	}
	
	function doWorkspaceInitAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$fields = array(
			DAO_Workspace::NAME => 'My First Workspace',
			DAO_Workspace::WORKER_ID => $active_worker->id,
		);
		$workspace_id = DAO_Workspace::create($fields);
		
		// My Tickets
		
		$list = new Model_WorkspaceListView();
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
			SearchFields_Ticket::VIRTUAL_WORKERS => new DevblocksSearchCriteria(SearchFields_Ticket::VIRTUAL_WORKERS,null,array($active_worker->id))
		);
		$list->num_rows = 5;
		
		$fields = array(
			DAO_WorkspaceList::WORKER_ID => $active_worker->id,
			DAO_WorkspaceList::WORKSPACE_ID => $workspace_id,
			DAO_WorkspaceList::LIST_POS => 1,
			DAO_WorkspaceList::LIST_VIEW => serialize($list),
			DAO_WorkspaceList::CONTEXT => CerberusContexts::CONTEXT_TICKET,
		);
		DAO_WorkspaceList::create($fields);
		
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
};

class ChExplorerToolbarWorkerEvents extends Extension_ExplorerToolbar {
	function render(Model_ExplorerSet $item) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('item', $item);
		
		$tpl->display('devblocks:cerberusweb.core::home/renderer/explorer_toolbar.tpl');
	}
};
