<?php
/***********************************************************************
| Cerb(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2013, WebGroup Media LLC
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

class Page_Custom extends CerberusPageExtension {
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == ($worker = CerberusApplication::getActiveWorker()))
			return false;
		
		return true;
	}
	
	function handleSectionActionAction() {
		@$section_uri = DevblocksPlatform::importGPC($_REQUEST['section'],'string','');
		@$action = DevblocksPlatform::importGPC($_REQUEST['action'],'string','');

		$inst = Extension_PageSection::getExtensionByPageUri($this->manifest->id, $section_uri, true);
		
		if($inst instanceof Extension_PageSection && method_exists($inst, $action.'Action')) {
			call_user_func(array($inst, $action.'Action'));
		}
	}
	
	function handleTabActionAction() {
		@$extension_id = DevblocksPlatform::importGPC($_REQUEST['tab'],'string','');
		@$tab_id = DevblocksPlatform::importGPC($_REQUEST['tab_id'],'integer',0);
		@$action = DevblocksPlatform::importGPC($_REQUEST['action'],'string','');

		$active_worker = CerberusApplication::getActiveWorker();
		
		if(null == ($tab = DAO_WorkspaceTab::get($tab_id)))
			return;
		
		if(null == ($page = DAO_WorkspacePage::get($tab->workspace_page_id))
			|| !$page->isReadableByWorker($active_worker)
			)
			return;
		
		$inst = DevblocksPlatform::getExtension($extension_id, true);
		
		if($inst instanceof Extension_WorkspaceTab && method_exists($inst, $action.'Action')) {
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl->assign('tab', $tab);
			$tpl->assign('tab_extension', $inst);
			
			call_user_func(array($inst, $action.'Action'));
		}
	}
	
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		
		$stack = $response->path;
		@array_shift($stack); // pages
		@$page_uri = array_shift($stack);

		$pages = DAO_WorkspacePage::getAll();
		
		$page_id = 0;
		
		if(intval($page_uri) > 0) {
			$page_id = intval($page_uri);
		}
		
		if(!isset($pages[$page_id]))
			$page_id = 0;
		
		if(empty($page_id)) {
			$this->_renderIndex();
			
		} else {
			$this->_renderPage($page_id);
		}
		
		return;
	}
	
	private function _renderIndex() {
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$pages = DAO_WorkspacePage::getAll();
		$tpl->assign('pages', $pages);
		
		// View
		$view_id = 'pages';
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_WorkspacePage';
		
		if(null != ($view = C4_AbstractViewLoader::getView($view_id, $defaults))) {
			$worker_group_ids = array_keys($active_worker->getMemberships());
			$worker_role_ids = array_keys(DAO_WorkerRole::getRolesByWorker($active_worker->id));
			
			// Restrict owners
			$params = array( '_ownership' => array(
					DevblocksSearchCriteria::GROUP_OR,
					array(
						DevblocksSearchCriteria::GROUP_AND,
						SearchFields_WorkspacePage::OWNER_CONTEXT => new DevblocksSearchCriteria(SearchFields_WorkspacePage::OWNER_CONTEXT,DevblocksSearchCriteria::OPER_EQ,CerberusContexts::CONTEXT_WORKER),
						SearchFields_WorkspacePage::OWNER_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_WorkspacePage::OWNER_CONTEXT_ID,DevblocksSearchCriteria::OPER_EQ,$active_worker->id),
					),
					array(
						DevblocksSearchCriteria::GROUP_AND,
						SearchFields_WorkspacePage::OWNER_CONTEXT => new DevblocksSearchCriteria(SearchFields_WorkspacePage::OWNER_CONTEXT,DevblocksSearchCriteria::OPER_EQ,CerberusContexts::CONTEXT_GROUP),
						SearchFields_WorkspacePage::OWNER_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_WorkspacePage::OWNER_CONTEXT_ID,DevblocksSearchCriteria::OPER_IN,$worker_group_ids),
					),
					array(
						DevblocksSearchCriteria::GROUP_AND,
						SearchFields_WorkspacePage::OWNER_CONTEXT => new DevblocksSearchCriteria(SearchFields_WorkspacePage::OWNER_CONTEXT,DevblocksSearchCriteria::OPER_EQ,CerberusContexts::CONTEXT_ROLE),
						SearchFields_WorkspacePage::OWNER_CONTEXT_ID => new DevblocksSearchCriteria(SearchFields_WorkspacePage::OWNER_CONTEXT_ID,DevblocksSearchCriteria::OPER_IN,$worker_role_ids),
					),
				)
			);
			
			$view->addParamsRequired($params, true);
			
			C4_AbstractViewLoader::setView($view->id, $view);
			
			$tpl->assign('view', $view);
		}
		
		$tpl->display('devblocks:cerberusweb.core::pages/index.tpl');
	}
	
	private function _renderPage($page_id) {
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		
		if(null == ($page = DAO_WorkspacePage::get($page_id)))
			return;
		
		if(!$page->isReadableByWorker($active_worker))
			return;
			
		$tpl->assign('page', $page);
		
		$point = sprintf("pages.worker.%d.%d",
			$active_worker->id,
			$page_id
		);
		$tpl->assign('point', $point);

		if(null != ($selected_tab = $visit->get($point, null)))
			$tpl->assign('selected_tab', $selected_tab);
		
		// Template
		if(!empty($page->extension_id)) {
			if(null != ($page_extension = DevblocksPlatform::getExtension($page->extension_id, true)))
				$tpl->assign('page_extension', $page_extension);
			
		} else {
			$tabs = $page->getTabs($active_worker);
			$tpl->assign('page_tabs', $tabs);
		}
		
		$tpl->display('devblocks:cerberusweb.core::pages/page.tpl');
	}
	
	function showPageWizardPopupAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string',null);
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('view_id', $view_id);
		
		$tpl->display('devblocks:cerberusweb.core::pages/wizard_popup.tpl');
	}
	
	function savePageWizardPopupAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string',null);
		@$page_type = DevblocksPlatform::importGPC($_REQUEST['page_type'],'string',null);
		
		$active_worker = CerberusApplication::getActiveWorker();

		$page_id = DAO_WorkspacePage::create(array(
			DAO_WorkspacePage::NAME => 'Mail',
			DAO_WorkspacePage::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
			DAO_WorkspacePage::OWNER_CONTEXT_ID => $active_worker->id,
		));
		
		$pos = 0;
		
		// Workflow
		
		$tab_id = DAO_WorkspaceTab::create(array(
			DAO_WorkspaceTab::NAME => 'Inbox',
			DAO_WorkspaceTab::POS => $pos++,
			DAO_WorkspaceTab::WORKSPACE_PAGE_ID => $page_id,
		));
		
		$list_pos = 0;

		// Workflow: My conversations
		
			$context = CerberusContexts::CONTEXT_TICKET;
			$context_ext = Extension_DevblocksContext::get($context);
			$view = $context_ext->getChooserView(); /* @var $view C4_AbstractView */
			
			$view->name = 'Needs my attention';
			$view->renderLimit = 5;
			$view->view_columns = array(
				SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
				SearchFields_Ticket::TICKET_UPDATED_DATE,
				SearchFields_Ticket::TICKET_GROUP_ID,
				SearchFields_Ticket::TICKET_BUCKET_ID,
			);
			$view->addParams(array(
				//new DevblocksSearchCriteria(SearchFields_Ticket::VIRTUAL_STATUS, 'in', array('open')),
			), true);
			$view->addParamsRequired(array(
				new DevblocksSearchCriteria(SearchFields_Ticket::VIRTUAL_STATUS, 'in', array('open')),
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_OWNER_ID, 'in', array('{{current_worker_id}}')),
			), true);
			
			$view_model = C4_AbstractViewLoader::serializeAbstractView($view);
			
			$list_view = new Model_WorkspaceListView();
			$list_view->title = $view_model->name;
			$list_view->columns = $view_model->view_columns;
			$list_view->num_rows = $view_model->renderLimit;
			$list_view->params = $view_model->paramsEditable;
			$list_view->params_required = $view_model->paramsRequired;
			$list_view->sort_by = $view_model->renderSortBy;
			$list_view->sort_asc = $view_model->renderSortAsc;
			
			$list_id = DAO_WorkspaceList::create(array(
				DAO_WorkspaceList::CONTEXT => $context,
				DAO_WorkspaceList::LIST_POS => $list_pos++,
				DAO_WorkspaceList::LIST_VIEW => serialize($list_view),
				DAO_WorkspaceList::WORKSPACE_TAB_ID => $tab_id,
			));
			
		// Workflow: Needs attention from anyone
			
			$context = CerberusContexts::CONTEXT_TICKET;
			$context_ext = Extension_DevblocksContext::get($context);
			$view = $context_ext->getChooserView(); /* @var $view C4_AbstractView */
			
			$view->name = 'Needs attention from anyone';
			$view->renderLimit = 10;
			$view->view_columns = array(
				SearchFields_Ticket::TICKET_LAST_ACTION_CODE,
				SearchFields_Ticket::TICKET_UPDATED_DATE,
				SearchFields_Ticket::TICKET_GROUP_ID,
				SearchFields_Ticket::TICKET_BUCKET_ID,
				SearchFields_Ticket::TICKET_OWNER_ID,
			);
			//$view->renderSubtotals = SearchFields_Ticket::TICKET_GROUP_ID;
			$view->addParams(array(
				//new DevblocksSearchCriteria(SearchFields_Ticket::VIRTUAL_STATUS, 'in', array('open')),
			), true);
			$view->addParamsRequired(array(
				new DevblocksSearchCriteria(SearchFields_Ticket::VIRTUAL_STATUS, 'in', array('open')),
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_OWNER_ID, 'in', array(0)),
				SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER => new DevblocksSearchCriteria(SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER, '=', '{{current_worker_id}}'),
			), true);
			
			$view_model = C4_AbstractViewLoader::serializeAbstractView($view);
			
			$list_view = new Model_WorkspaceListView();
			$list_view->title = $view_model->name;
			$list_view->columns = $view_model->view_columns;
			$list_view->num_rows = $view_model->renderLimit;
			$list_view->params = $view_model->paramsEditable;
			$list_view->params_required = $view_model->paramsRequired;
			$list_view->sort_by = $view_model->renderSortBy;
			$list_view->sort_asc = $view_model->renderSortAsc;
			
			$list_id = DAO_WorkspaceList::create(array(
				DAO_WorkspaceList::CONTEXT => $context,
				DAO_WorkspaceList::LIST_POS => $list_pos++,
				DAO_WorkspaceList::LIST_VIEW => serialize($list_view),
				DAO_WorkspaceList::WORKSPACE_TAB_ID => $tab_id,
			));
		
		// Drafts
		
		$tab_id = DAO_WorkspaceTab::create(array(
			DAO_WorkspaceTab::NAME => 'Drafts',
			DAO_WorkspaceTab::POS => $pos++,
			DAO_WorkspaceTab::WORKSPACE_PAGE_ID => $page_id,
		));
		
		// Drafts: My drafts
		
			$context = CerberusContexts::CONTEXT_DRAFT;
			$context_ext = Extension_DevblocksContext::get($context);
			$view = $context_ext->getChooserView(); /* @var $view C4_AbstractView */
			
			$view->name = 'My drafts';
			$view->renderLimit = 10;
			$view->view_columns = array(
				SearchFields_MailQueue::HINT_TO,
				SearchFields_MailQueue::WORKER_ID,
				SearchFields_MailQueue::TYPE,
				SearchFields_MailQueue::UPDATED,
			);
			$view->addParams(array(
				//new DevblocksSearchCriteria(SearchFields_MailQueue::VIRTUAL_STATUS, 'in', array('open')),
			), true);
			$view->addParamsRequired(array(
				new DevblocksSearchCriteria(SearchFields_MailQueue::WORKER_ID, 'in', array('{{current_worker_id}}')),
				//new DevblocksSearchCriteria(SearchFields_MailQueue::IS_QUEUED, '=', 0),
			), true);
			
			$view_model = C4_AbstractViewLoader::serializeAbstractView($view);
			
			$list_view = new Model_WorkspaceListView();
			$list_view->title = $view_model->name;
			$list_view->columns = $view_model->view_columns;
			$list_view->num_rows = $view_model->renderLimit;
			$list_view->params = $view_model->paramsEditable;
			$list_view->params_required = $view_model->paramsRequired;
			$list_view->sort_by = $view_model->renderSortBy;
			$list_view->sort_asc = $view_model->renderSortAsc;
			
			$list_id = DAO_WorkspaceList::create(array(
				DAO_WorkspaceList::CONTEXT => $context,
				DAO_WorkspaceList::LIST_POS => $list_pos++,
				DAO_WorkspaceList::LIST_VIEW => serialize($list_view),
				DAO_WorkspaceList::WORKSPACE_TAB_ID => $tab_id,
			));
		
		// Sent
		
		$tab_id = DAO_WorkspaceTab::create(array(
			DAO_WorkspaceTab::NAME => 'Sent',
			DAO_WorkspaceTab::POS => $pos++,
			DAO_WorkspaceTab::WORKSPACE_PAGE_ID => $page_id,
		));
		
		// Sent: my sent messages
		
			$context = CerberusContexts::CONTEXT_MESSAGE;
			$context_ext = Extension_DevblocksContext::get($context);
			$view = $context_ext->getChooserView(); /* @var $view C4_AbstractView */
			
			$view->name = 'My sent messages';
			$view->renderLimit = 10;
			$view->view_columns = array(
				SearchFields_Message::ADDRESS_EMAIL,
				SearchFields_Message::TICKET_GROUP_ID,
				SearchFields_Message::CREATED_DATE,
				SearchFields_Message::WORKER_ID,
			);
			$view->addParams(array(
				//new DevblocksSearchCriteria(SearchFields_Message::VIRTUAL_STATUS, 'in', array('open')),
			), true);
			$view->addParamsRequired(array(
				new DevblocksSearchCriteria(SearchFields_Message::WORKER_ID, 'in', array('{{current_worker_id}}')),
				new DevblocksSearchCriteria(SearchFields_Message::IS_OUTGOING, '=', 1),
			), true);
			
			$view_model = C4_AbstractViewLoader::serializeAbstractView($view);
			
			$list_view = new Model_WorkspaceListView();
			$list_view->title = $view_model->name;
			$list_view->columns = $view_model->view_columns;
			$list_view->num_rows = $view_model->renderLimit;
			$list_view->params = $view_model->paramsEditable;
			$list_view->params_required = $view_model->paramsRequired;
			$list_view->sort_by = $view_model->renderSortBy;
			$list_view->sort_asc = $view_model->renderSortAsc;
			
			$list_id = DAO_WorkspaceList::create(array(
				DAO_WorkspaceList::CONTEXT => $context,
				DAO_WorkspaceList::LIST_POS => $list_pos++,
				DAO_WorkspaceList::LIST_VIEW => serialize($list_view),
				DAO_WorkspaceList::WORKSPACE_TAB_ID => $tab_id,
			));
	}
	
	function setPageOrderAction() {
		@$page_ids_str = DevblocksPlatform::importGPC($_REQUEST['pages'],'string','');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$menu = array();
		$pages = DAO_WorkspacePage::getAll();
		
		$page_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::parseCsvString($page_ids_str), 'integer', array('nonzero','unique'));
		
		foreach($page_ids as $page_id) {
			if(!isset($pages[$page_id]))
				continue;

			// Check write permission
			if(!$pages[$page_id]->isReadableByWorker($active_worker))
				continue;
			
			$menu[] = $page_id;
		}

		DAO_WorkerPref::set($active_worker->id, 'menu_json', json_encode($menu));
		exit;
	}
	
	function setTabOrderAction() {
		@$page_id = DevblocksPlatform::importGPC($_REQUEST['page_id'],'integer','0');
		@$tab_ids_str = DevblocksPlatform::importGPC($_REQUEST['tabs'],'string','');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(null == ($page = DAO_WorkspacePage::get($page_id)))
			return;
		
		if(!$page->isReadableByWorker($active_worker))
			return;
		
		$tab_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::parseCsvString($tab_ids_str), 'integer', array('nonzero','unique'));
		
		DAO_WorkerPref::set($active_worker->id, 'page_tabs_' . $page->id . '_json', json_encode($tab_ids));
		exit;
	}
	
	function showAddTabsAction() {
		@$page_id = DevblocksPlatform::importGPC($_REQUEST['page_id'],'integer','0');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(null == ($page = DAO_WorkspacePage::get($page_id)))
			return;
		
		if(!$page->isWriteableByWorker($active_worker))
			return;

		$tpl->assign('page', $page);
		
		$tab_extensions = Extension_WorkspaceTab::getAll(false);
		$tpl->assign('tab_extensions', $tab_extensions);
		
		$tpl->display('devblocks:cerberusweb.core::pages/add_tabs.tpl');
	}
	
	function doToggleMenuPageJsonAction() {
		@$page_id = DevblocksPlatform::importGPC($_REQUEST['page_id'],'integer','0');
		@$toggle = DevblocksPlatform::importGPC($_REQUEST['toggle'],'integer','0');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-type: application/json');

		$pages = DAO_WorkspacePage::getAll();
		
		@$menu = json_decode(DAO_WorkerPref::get($active_worker->id, 'menu_json', json_encode(array())));
		
		if(!is_array($menu))
			$menu = array();
		
		if(null != ($page = DAO_WorkspacePage::get($page_id))) {
			if($page->isReadableByWorker($active_worker)) {
				if(empty($toggle)) {
					if(false !== ($idx = array_search($page_id, $menu))) {
						unset($menu[$idx]);
					}
					
				} else {
					$menu[] = $page_id;
				}
				
			}
		}
		
		// Remove dead links
		foreach($menu as $idx => $page_id) {
			if(!isset($pages[$page_id]))
				unset($menu[$idx]);
		}
		
		DAO_WorkerPref::set($active_worker->id, 'menu_json', json_encode(array_values($menu)));
		
		echo json_encode(array(
			'success' => true,
			'page_id' => $page_id,
		));
		
		exit;
	}
	
	function doAddCustomTabJsonAction() {
		@$page_id = DevblocksPlatform::importGPC($_REQUEST['page_id'],'integer','0');
		@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string','');
		@$type = DevblocksPlatform::importGPC($_REQUEST['type'],'string','');
		@$index = DevblocksPlatform::importGPC($_REQUEST['index'],'string',null);
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::getUrlService();
		
		header('Content-type: application/json');
		
		try {
			if(null == ($page = DAO_WorkspacePage::get($page_id)))
				throw new Exception("Page not found.");
			
			if(!$page->isWriteableByWorker($active_worker))
				throw new Exception("Write access to page is denied.");
			
			$fields = array(
				DAO_WorkspaceTab::NAME => !empty($title) ? $title : 'New Tab',
				DAO_WorkspaceTab::WORKSPACE_PAGE_ID => $page_id,
				DAO_WorkspaceTab::POS => is_null($index) ? 99 : intval($index) + 1,
				DAO_WorkspaceTab::EXTENSION_ID => $type,
			);
			$tab_id = DAO_WorkspaceTab::create($fields);
			
			if(empty($tab_id))
				throw new Exception("Unable to create tab.");
			
			echo json_encode(array(
				'success' => true,
				'page_id' => $page->id,
				'tab_id' => $tab_id,
				'tab_url' => $url_writer->write('ajax.php?c=pages&a=showWorkspaceTab&id=' . $tab_id),
			));
			
		} catch(Exception $e) {
			echo json_encode(array(
				'success' => false,
			));
		}
		
		exit;
	}
	
	function showWorkspaceTabAction() {
		@$tab_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		@$point = DevblocksPlatform::importGPC($_REQUEST['point'],'string', '');
		@$request = DevblocksPlatform::importGPC($_REQUEST['request'],'string', '');

		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();

		$visit = CerberusApplication::getVisit();
		$visit->set($point, 'w_'.$tab_id);

		if(null == ($tab = DAO_WorkspaceTab::get($tab_id)))
			return;
		
		if(null == ($page = DAO_WorkspacePage::get($tab->workspace_page_id))
			|| !$page->isReadableByWorker($active_worker)
			)
			return;

		$tpl->assign('page', $page);
		$tpl->assign('tab', $tab);
		$tpl->assign('request', $request);
		
		if(empty($tab->extension_id)) {
			$lists = $tab->getWorklists();
			$list_ids = array_keys($lists);
			unset($lists);
			
			$tpl->assign('list_ids', $list_ids);
			
			$tpl->display('devblocks:cerberusweb.core::pages/tab_worklists.tpl');
			
		} else {
			// Load extension
			if(null != ($tab_extension = DevblocksPlatform::getExtension($tab->extension_id, true))) {
				$tpl->assign('tab_extension', $tab_extension);
			}
			$tpl->display('devblocks:cerberusweb.core::pages/tab_extension.tpl');
		}
	}
	
	function initWorkspaceListAction() {
		@$list_id = DevblocksPlatform::importGPC($_REQUEST['list_id'],'integer', 0);
	
		if(empty($list_id))
			return;
			
		if(null == ($list = DAO_WorkspaceList::get($list_id)))
			return;
			
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
	
		if(null == ($tab = DAO_WorkspaceTab::get($list->workspace_tab_id)))
			return;
	
		if(null == ($page = DAO_WorkspacePage::get($tab->workspace_page_id))
			|| !$page->isReadableByWorker($active_worker)
		)
			return;
	
		$view_id = 'cust_' . $list->id;
	
		// Make sure our workspace source has a valid renderer class
		if(null == ($ext = DevblocksPlatform::getExtension($list->context, true))) { /* @var $ext Extension_DevblocksContext */
			return;
		}
		
		if(null == ($view = C4_AbstractViewLoader::getView($view_id))) {
			$list_view = $list->list_view; /* @var $list_view Model_WorkspaceListView */
				
			$view = $ext->getChooserView($view_id);  /* @var $view C4_AbstractView */
				
			if(empty($view))
				return;
				
			$view->name = $list_view->title;
			$view->renderLimit = $list_view->num_rows;
			$view->renderPage = 0;
			$view->is_ephemeral = 0;
			$view->view_columns = $list_view->columns;
			$view->addParams($list_view->params, true);
			if(property_exists($list_view, 'params_required'))
				$view->addParamsRequired($list_view->params_required, true);
			$view->renderSortBy = $list_view->sort_by;
			$view->renderSortAsc = $list_view->sort_asc;
	
			unset($ext);
			unset($list_view);
			unset($view_class);
		}
	
		if(!empty($view)) {
			$labels = array();
			$values = array();
				
			$labels['current_worker_id'] = array(
				'label' => 'Current Worker',
				'context' => CerberusContexts::CONTEXT_WORKER,
			);
				
			$values['current_worker_id'] = $active_worker->id;
	
			$view->setPlaceholderLabels($labels);
			$view->setPlaceholderValues($values);
				
			C4_AbstractViewLoader::setView($view_id, $view);
				
			$tpl->assign('view', $view);
			$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
			$tpl->clearAssign('view');
		}
	
		unset($list);
		unset($list_id);
		unset($view_id);
	}
	
	function showEditWorkspacePageAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string', '');
	
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
	
		$tpl->assign('view_id', $view_id);
	
		if(!empty($id)) {
			if(null == ($page = DAO_WorkspacePage::get($id)))
				return;
	
			if(!$page->isWriteableByWorker($active_worker))
				return;
				
			$tpl->assign('workspace_page', $page);
		}
	
		// Owners
		
		$roles = DAO_WorkerRole::getAll();
		$tpl->assign('roles', $roles);
	
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
	
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
	
		$owner_groups = array();
		foreach($groups as $k => $v) {
			if($active_worker->is_superuser || $active_worker->isGroupManager($k))
				$owner_groups[$k] = $v;
		}
		$tpl->assign('owner_groups', $owner_groups);
	
		$owner_roles = array();
		foreach($roles as $k => $v) { /* @var $v Model_WorkerRole */
			if($active_worker->is_superuser)
				$owner_roles[$k] = $v;
		}
		$tpl->assign('owner_roles', $owner_roles);
	
		// Extensions
		
		$page_extensions = Extension_WorkspacePage::getAll(false);
		$tpl->assign('page_extensions', $page_extensions);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::pages/edit_workspace_page.tpl');
	}
	
	function doEditWorkspacePageAction() {
		@$workspace_page_id = DevblocksPlatform::importGPC($_POST['id'],'integer', 0);
		@$view_id = DevblocksPlatform::importGPC($_POST['view_id'],'string', '');
		@$name = DevblocksPlatform::importGPC($_POST['name'],'string', '');
		@$extension_id = DevblocksPlatform::importGPC($_POST['extension_id'],'string', '');
		@$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'],'integer', '0');
	
		$active_worker = CerberusApplication::getActiveWorker();
	
		if(!empty($workspace_page_id)) {
			if(null == ($workspace_page = DAO_WorkspacePage::get($workspace_page_id)))
				return;
				
			if(!$workspace_page->isWriteableByWorker($active_worker))
				return;
		}
	
		if(!empty($workspace_page_id) && $do_delete) { // Delete
			DAO_WorkspacePage::delete($workspace_page_id);
	
		} else { // Create/Edit
			$fields = array(
				DAO_WorkspacePage::NAME => $name,
			);
			
			// Owner
			@list($owner_type, $owner_id) = explode('_', DevblocksPlatform::importGPC($_REQUEST['owner'],'string',''));
				
			switch($owner_type) {
				// Group
				case 'g':
					$owner_context = CerberusContexts::CONTEXT_GROUP;
					$owner_context_id = $owner_id;
					break;
					// Role
				case 'r':
					$owner_context = CerberusContexts::CONTEXT_ROLE;
					$owner_context_id = $owner_id;
					break;
					// Worker
				case 'w':
					$owner_context = CerberusContexts::CONTEXT_WORKER;
					$owner_context_id = $owner_id;
					break;
					// Default
				default:
					$owner_context = null;
					$owner_context_id = null;
					break;
			}

			if(!empty($owner_context)) {
				$fields[DAO_WorkspacePage::OWNER_CONTEXT] = $owner_context;
				$fields[DAO_WorkspacePage::OWNER_CONTEXT_ID] = $owner_context_id;
			}
				
			if(empty($workspace_page_id)) {
				// Extension
				$fields[DAO_WorkspacePage::EXTENSION_ID] = $extension_id;
	
				$workspace_page_id = DAO_WorkspacePage::create($fields);
	
				// View marquee
				if(!empty($workspace_page_id) && !empty($view_id)) {
					$url_writer = DevblocksPlatform::getUrlService();
					C4_AbstractView::setMarquee($view_id, sprintf("New page created: <a href='%s'><b>%s</b></a>",
						$url_writer->write(sprintf("c=pages&a=%d-%s",
							$workspace_page_id,
							DevblocksPlatform::strToPermalink($name))
						),
						htmlspecialchars($name, ENT_QUOTES, LANG_CHARSET_CODE)
					));
				}
				
			} else {
				DAO_WorkspacePage::update($workspace_page_id, $fields);
	
			}
		}
	}
	
	function showEditWorkspaceTabAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
	
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
	
		if(empty($id))
			return;
	
		if(null == ($tab = DAO_WorkspaceTab::get($id)))
			return;
	
		if(null == ($page = DAO_WorkspacePage::get($tab->workspace_page_id)))
			return;
	
		if(!$page->isWriteableByWorker($active_worker))
			return;
	
		$tpl->assign('workspace_page', $page);
		$tpl->assign('workspace_tab', $tab);
			
		$worklists = $tab->getWorklists();
		$tpl->assign('worklists', $worklists);
	
		$contexts = Extension_DevblocksContext::getAll(false);
		$tpl->assign('contexts', $contexts);
	
		$tpl->display('devblocks:cerberusweb.core::pages/edit_workspace_tab.tpl');
	}
	
	function doEditWorkspaceTabJsonAction() {
		@$workspace_tab_id = DevblocksPlatform::importGPC($_POST['id'],'integer', 0);
		@$name = DevblocksPlatform::importGPC($_POST['name'],'string', '');
	
		@$ids = DevblocksPlatform::importGPC($_POST['ids'],'array', array());
		@$names = DevblocksPlatform::importGPC($_POST['names'],'array', array());
		@$do_delete = DevblocksPlatform::importGPC($_POST['do_delete'],'integer', '0');
	
		$active_worker = CerberusApplication::getActiveWorker();
	
		header('Content-Type: application/json');
		
		if(empty($workspace_tab_id)) {
			echo json_encode(false);
			return;
		}
	
		if(null == ($workspace_tab = DAO_WorkspaceTab::get($workspace_tab_id))) {
			echo json_encode(false);
			return;
		}
	
		if(null == ($workspace_page = DAO_WorkspacePage::get($workspace_tab->workspace_page_id))) {
			echo json_encode(false);
			return;
		}
	
		if(!$workspace_page->isWriteableByWorker($active_worker)) {
			echo json_encode(false);
			return;
		}
	
		if($do_delete) { // Delete
			DAO_WorkspaceTab::delete($workspace_tab_id);
	
		} else { // Create/Edit
			if(empty($workspace_tab_id)) {
				$fields = array(
					DAO_WorkspaceTab::NAME => $name,
					DAO_WorkspaceTab::WORKSPACE_PAGE_ID => 0,
				);
				$workspace_tab_id = DAO_WorkspaceTab::create($fields);
	
			} else {
				$fields = array();
	
				// Rename tab
				if(0 != strcmp($workspace_tab->name, $name)) {
					$fields[DAO_WorkspaceTab::NAME] = $name;
				}
	
				if(!empty($fields))
					DAO_WorkspaceTab::update($workspace_tab_id, $fields);
			}
	
			// If we have no tab extension (worklists default)
			if(empty($workspace_tab->extension_id)) {
				// Create any new worklists
				if(is_array($ids) && !empty($ids))
					foreach($ids as $idx => $id) {
					if(!is_numeric($id)) { // Create
						if(null == ($context_ext = DevblocksPlatform::getExtension($id, true))) /* @var $context_ext Extension_DevblocksContext */
							continue;
							
						if(null == ($view = $context_ext->getChooserView()))  /* @var $view C4_AbstractView */
							continue;
		
						// Build the list model
						$list = new Model_WorkspaceListView();
						$list->title = $names[$idx];
						$list->columns = $view->view_columns;
						$list->params = $view->getEditableParams();
						$list->params_required = $view->getParamsRequired();
						$list->num_rows = 5;
						$list->sort_by = $view->renderSortBy;
						$list->sort_asc = $view->renderSortAsc;
		
						// Add the worklist
						$fields = array(
							DAO_WorkspaceList::LIST_POS => $idx,
							DAO_WorkspaceList::LIST_VIEW => serialize($list),
							DAO_WorkspaceList::WORKSPACE_TAB_ID => $workspace_tab_id,
							DAO_WorkspaceList::CONTEXT => $id,
						);
						$ids[$idx] = DAO_WorkspaceList::create($fields);
					}
				}
		
				$worklists = $workspace_tab->getWorklists();
		
				// Deletes
				$delete_ids = array_diff(array_keys($worklists), $ids);
				if(is_array($delete_ids) && !empty($delete_ids))
					DAO_WorkspaceList::delete($delete_ids);
		
				// Reorder worklists, rename lists, on workspace
				if(is_array($ids) && !empty($ids))
					foreach($ids as $idx => $id) {
					if(null == ($worklist = DAO_WorkspaceList::get($id)))
						continue;
		
					$list_view = $worklists[$id]->list_view; /* @var $list_view Model_WorkspaceListView */
		
					// If the name changed
					if(isset($names[$idx]) && 0 != strcmp($list_view->title, $names[$idx])) {
						$list_view->title = $names[$idx];
		
						// Save the view in the session
						$view = C4_AbstractViewLoader::getView('cust_'.$id);
						$view->name = $list_view->title;
						C4_AbstractViewLoader::setView('cust_'.$id, $view);
					}
		
					DAO_WorkspaceList::update($id,array(
						DAO_WorkspaceList::LIST_POS => intval($idx),
						DAO_WorkspaceList::LIST_VIEW => serialize($list_view),
					));
				}
				
			} else { // tab extension
				if(null != ($tab_extension = DevblocksPlatform::getExtension($workspace_tab->extension_id, true, true))) {
					/* @var $tab_extension Extension_WorkspaceTab */
					if(method_exists($tab_extension, 'saveTabConfig'))
						$tab_extension->saveTabConfig($workspace_page, $workspace_tab);
				}
			}
		}
		
		echo json_encode(array(
			'name' => $name,
		));
	}
	
};