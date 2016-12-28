<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2016, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.io/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.io	    http://webgroup.media
***********************************************************************/

class Page_Custom extends CerberusPageExtension {
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == ($worker = CerberusApplication::getActiveWorker()))
			return false;
		
		return true;
	}
	
	function handleSectionActionAction() {
		// GET has precedence over POST
		@$section_uri = DevblocksPlatform::importGPC(isset($_GET['section']) ? $_GET['section'] : $_REQUEST['section'],'string','');
		@$action = DevblocksPlatform::importGPC(isset($_GET['action']) ? $_GET['action'] : $_REQUEST['action'],'string','');

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
			|| !Context_WorkspacePage::isReadableByActor($page, $active_worker)
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
		$defaults = C4_AbstractViewModel::loadFromClass('View_WorkspacePage');
		
		if(null != ($view = C4_AbstractViewLoader::getView($view_id, $defaults))) {
			if(!$active_worker->is_superuser) {
				$worker_group_ids = array_keys($active_worker->getMemberships());
				$worker_role_ids = array_keys(DAO_WorkerRole::getRolesByWorker($active_worker->id));
				
				// Restrict owners
				
				$params = $view->getParamsFromQuickSearch(sprintf('(owner.app:cerb OR owner.worker:(id:[%d]) OR owner.group:(id:[%s]) OR owner.role:(id:[%s])',
					$active_worker->id,
					implode(',', $worker_group_ids),
					implode(',', $worker_role_ids)
				));
				
				$view->addParamsRequired(['_ownership' => $params[0]], true);
			}
			
			$tpl->assign('view', $view);
		}
		
		$tpl->display('devblocks:cerberusweb.core::pages/index.tpl');
	}
	
	private function _renderPage($page_id) {
		$tpl = DevblocksPlatform::getTemplateService();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(null == ($page = DAO_WorkspacePage::get($page_id)))
			return;
		
		if(!Context_WorkspacePage::isReadableByActor($page, $active_worker))
			return;
			
		$point = sprintf("pages.worker.%d.%d",
			$active_worker->id,
			$page_id
		);
		$tpl->assign('point', $point);

		// Template
		if(null != ($page_extension = DevblocksPlatform::getExtension($page->extension_id, true)))
			$tpl->assign('page_extension', $page_extension);
		
		$tpl->assign('page', $page);
		$tpl->display('devblocks:cerberusweb.core::pages/page.tpl');
	}
	
	function showPageWizardPopupAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string',null);
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('view_id', $view_id);
		
		$tpl->display('devblocks:cerberusweb.core::pages/wizard_popup.tpl');
	}
	
	// [TODO] This should convert to the new JSON import format
	function savePageWizardPopupAction() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string',null);
		@$page_type = DevblocksPlatform::importGPC($_REQUEST['page_type'],'string',null);
		
		$active_worker = CerberusApplication::getActiveWorker();
		$page_id = null;

		switch($page_type) {
			case 'mail':
				$page_id = $this->_createWizardMailPage();
				break;
				
			case 'kb':
				$page_id = $this->_createWizardKbPage();
				break;
				
			case 'reports':
				$page_id = $this->_createWizardReportsPage();
				break;
		}
		
		// Add to the current worker's menu pref
		if(!empty($page_id)) {
			$menu_json = json_decode(DAO_WorkerPref::get($active_worker->id, 'menu_json'), true);
			$menu_json[] = $page_id;
			DAO_WorkerPref::set($active_worker->id, 'menu_json', json_encode($menu_json));
		}
	}
	
	private function _createWizardKbPage() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!DevblocksPlatform::isPluginEnabled('cerberusweb.kb'))
			return;
		
		$view_id = 'pages';
		$page_name = 'Knowledgebase';
		
		$page_id = DAO_WorkspacePage::create(array(
			DAO_WorkspacePage::NAME => $page_name,
			DAO_WorkspacePage::EXTENSION_ID => 'core.workspace.page.workspace',
			DAO_WorkspacePage::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
			DAO_WorkspacePage::OWNER_CONTEXT_ID => $active_worker->id,
		));
		
		$pos = 0;
		
		// Knowledgebase browser
		
		$tab_id = DAO_WorkspaceTab::create(array(
			DAO_WorkspaceTab::NAME => 'Topics',
			DAO_WorkspaceTab::EXTENSION_ID => 'cerberusweb.kb.tab.browse',
			DAO_WorkspaceTab::POS => $pos++,
			DAO_WorkspaceTab::WORKSPACE_PAGE_ID => $page_id,
		));
		
		// Marquee
		
		if(!empty($page_id) && !empty($view_id)) {
			$url_writer = DevblocksPlatform::getUrlService();
			C4_AbstractView::setMarquee($view_id, sprintf("New page created: <a href='%s'><b>%s</b></a>",
				$url_writer->write(sprintf("c=pages&a=%d-%s",
					$page_id,
					DevblocksPlatform::strToPermalink($page_name))
				),
				htmlspecialchars($page_name, ENT_QUOTES, LANG_CHARSET_CODE)
			));
		}
		
		return $page_id;
	}
	
	private function _createWizardReportsPage() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!DevblocksPlatform::isPluginEnabled('cerberusweb.reports'))
			return;
		
		$view_id = 'pages';
		$page_name = 'Reports';
		
		// Reports page
		
		$page_id = DAO_WorkspacePage::create(array(
			DAO_WorkspacePage::NAME => $page_name,
			DAO_WorkspacePage::EXTENSION_ID => 'reports.workspace.page',
			DAO_WorkspacePage::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
			DAO_WorkspacePage::OWNER_CONTEXT_ID => $active_worker->id,
		));
		
		// Marquee
		
		if(!empty($page_id) && !empty($view_id)) {
			$url_writer = DevblocksPlatform::getUrlService();
			C4_AbstractView::setMarquee($view_id, sprintf("New page created: <a href='%s'><b>%s</b></a>",
				$url_writer->write(sprintf("c=pages&a=%d-%s",
					$page_id,
					DevblocksPlatform::strToPermalink($page_name))
				),
				htmlspecialchars($page_name, ENT_QUOTES, LANG_CHARSET_CODE)
			));
		}
		
		return $page_id;
	}
	
	private function _createWizardMailPage() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$view_id = 'pages';
		$page_name = 'Mail';
		
		$page_id = DAO_WorkspacePage::create(array(
			DAO_WorkspacePage::NAME => $page_name,
			DAO_WorkspacePage::EXTENSION_ID => 'core.workspace.page.workspace',
			DAO_WorkspacePage::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
			DAO_WorkspacePage::OWNER_CONTEXT_ID => $active_worker->id,
		));
		
		$pos = 0;
		
		// Workflow
		
		$tab_id = DAO_WorkspaceTab::create(array(
			DAO_WorkspaceTab::NAME => 'Inbox',
			DAO_WorkspaceTab::EXTENSION_ID => 'core.workspace.tab.worklists',
			DAO_WorkspaceTab::POS => $pos++,
			DAO_WorkspaceTab::WORKSPACE_PAGE_ID => $page_id,
		));
		
		$list_pos = 0;

		// Workflow: Open conversations
		
			// [TODO] Recommended
		
			$context = CerberusContexts::CONTEXT_TICKET;
			$context_ext = Extension_DevblocksContext::get($context);
			$view = $context_ext->getChooserView(); /* @var $view C4_AbstractView */
			
			$view->name = 'Needs attention';
			$view->renderLimit = 10;
			$view->view_columns = array(
				SearchFields_Ticket::BUCKET_RESPONSIBILITY,
				SearchFields_Ticket::TICKET_LAST_WROTE_ID,
				SearchFields_Ticket::TICKET_UPDATED_DATE,
				SearchFields_Ticket::TICKET_GROUP_ID,
				SearchFields_Ticket::TICKET_BUCKET_ID,
				SearchFields_Ticket::TICKET_OWNER_ID,
			);
			$view->options = array('disable_watchers' => true);
			$view->renderSortBy = SearchFields_Ticket::BUCKET_RESPONSIBILITY;
			$view->renderSortAsc = 0;
			$view->renderSubtotals = SearchFields_Ticket::TICKET_GROUP_ID;
			$view->addParams(array(
			), true);
			$view->addParamsRequired(array(
				new DevblocksSearchCriteria(SearchFields_Ticket::VIRTUAL_GROUPS_OF_WORKER, '=', '{{current_worker_id}}'),
				new DevblocksSearchCriteria(SearchFields_Ticket::VIRTUAL_STATUS, 'in', array('open')),
			), true);
			
			$view_model = C4_AbstractViewLoader::serializeAbstractView($view);
			
			$list_view = new Model_WorkspaceListView();
			$list_view->title = $view_model->name;
			$list_view->options = $view_model->options;
			$list_view->columns = $view_model->view_columns;
			$list_view->num_rows = $view_model->renderLimit;
			$list_view->params = $view_model->paramsEditable;
			$list_view->params_required = $view_model->paramsRequired;
			$list_view->sort_by = $view_model->renderSortBy;
			$list_view->sort_asc = $view_model->renderSortAsc;
			$list_view->subtotals = $view_model->renderSubtotals;
			
			$list_id = DAO_WorkspaceList::create(array(
				DAO_WorkspaceList::CONTEXT => $context,
				DAO_WorkspaceList::LIST_POS => $list_pos++,
				DAO_WorkspaceList::LIST_VIEW => serialize($list_view),
				DAO_WorkspaceList::WORKSPACE_TAB_ID => $tab_id,
			));
			
		// Sent
		
		$tab_id = DAO_WorkspaceTab::create(array(
			DAO_WorkspaceTab::NAME => 'Sent',
			DAO_WorkspaceTab::EXTENSION_ID => 'core.workspace.tab.worklists',
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
			), true);
			$view->addParamsRequired(array(
				new DevblocksSearchCriteria(SearchFields_Message::WORKER_ID, 'in', array('{{current_worker_id}}')),
				new DevblocksSearchCriteria(SearchFields_Message::IS_OUTGOING, '=', 1),
			), true);
			
			$view_model = C4_AbstractViewLoader::serializeAbstractView($view);
			
			$list_view = new Model_WorkspaceListView();
			$list_view->title = $view_model->name;
			$list_view->options = $view_model->options;
			$list_view->columns = $view_model->view_columns;
			$list_view->num_rows = $view_model->renderLimit;
			$list_view->params = $view_model->paramsEditable;
			$list_view->params_required = $view_model->paramsRequired;
			$list_view->sort_by = $view_model->renderSortBy;
			$list_view->sort_asc = $view_model->renderSortAsc;
			$list_view->subtotals = $view_model->renderSubtotals;
			
			$list_id = DAO_WorkspaceList::create(array(
				DAO_WorkspaceList::CONTEXT => $context,
				DAO_WorkspaceList::LIST_POS => $list_pos++,
				DAO_WorkspaceList::LIST_VIEW => serialize($list_view),
				DAO_WorkspaceList::WORKSPACE_TAB_ID => $tab_id,
			));	

		// Drafts
		
		$tab_id = DAO_WorkspaceTab::create(array(
			DAO_WorkspaceTab::NAME => 'Drafts',
			DAO_WorkspaceTab::EXTENSION_ID => 'core.workspace.tab.worklists',
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
			), true);
			$view->addParamsRequired(array(
				new DevblocksSearchCriteria(SearchFields_MailQueue::WORKER_ID, 'in', array('{{current_worker_id}}')),
			), true);
			
			$view_model = C4_AbstractViewLoader::serializeAbstractView($view);
			
			$list_view = new Model_WorkspaceListView();
			$list_view->title = $view_model->name;
			$list_view->options = $view_model->options;
			$list_view->columns = $view_model->view_columns;
			$list_view->num_rows = $view_model->renderLimit;
			$list_view->params = $view_model->paramsEditable;
			$list_view->params_required = $view_model->paramsRequired;
			$list_view->sort_by = $view_model->renderSortBy;
			$list_view->sort_asc = $view_model->renderSortAsc;
			$list_view->subtotals = $view_model->renderSubtotals;
			
			$list_id = DAO_WorkspaceList::create(array(
				DAO_WorkspaceList::CONTEXT => $context,
				DAO_WorkspaceList::LIST_POS => $list_pos++,
				DAO_WorkspaceList::LIST_VIEW => serialize($list_view),
				DAO_WorkspaceList::WORKSPACE_TAB_ID => $tab_id,
			));
		
		// Marquee
		
		if(!empty($page_id) && !empty($view_id)) {
			$url_writer = DevblocksPlatform::getUrlService();
			C4_AbstractView::setMarquee($view_id, sprintf("New page created: <a href='%s'><b>%s</b></a>",
				$url_writer->write(sprintf("c=pages&a=%d-%s",
					$page_id,
					DevblocksPlatform::strToPermalink($page_name))
				),
				htmlspecialchars($page_name, ENT_QUOTES, LANG_CHARSET_CODE)
			));
		}
		
		return $page_id;
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

			if(!Context_WorkspacePage::isReadableByActor($pages[$page_id], $active_worker))
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
		
		if(!Context_WorkspacePage::isReadableByActor($page, $active_worker))
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
		
		if(!Context_WorkspacePage::isWriteableByActor($page, $active_worker))
			return;

		$tpl->assign('page', $page);
		
		$tab_extensions = Extension_WorkspaceTab::getAll(false);
		
		// Sort dashboards and worklists to top
		$default_tab_extensions = array('core.workspace.tab' => $tab_extensions['core.workspace.tab'], 'core.workspace.tab.worklists' => $tab_extensions['core.workspace.tab.worklists']);
		unset($tab_extensions['core.workspace.tab']);
		unset($tab_extensions['core.workspace.tab.worklists']);
		$tab_extensions = array_merge($default_tab_extensions, $tab_extensions);
		
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
			if(Context_WorkspacePage::isReadableByActor($page, $active_worker)) {
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
		@$title = DevblocksPlatform::importGPC($_REQUEST['name'],'string','');
		@$extension_id = DevblocksPlatform::importGPC($_REQUEST['extension_id'],'string','');
		@$index = DevblocksPlatform::importGPC($_REQUEST['index'],'string',null);
		@$mode = DevblocksPlatform::importGPC($_REQUEST['mode'],'string','');
		@$import_json = DevblocksPlatform::importGPC($_REQUEST['import_json'], 'string', null);
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::getUrlService();

		header('Content-type: application/json');
		
		try {
			if(null == ($page = DAO_WorkspacePage::get($page_id)))
				throw new Exception("Page not found.");
			
			if(!Context_WorkspacePage::isWriteableByActor($page, $active_worker))
				throw new Exception("Write access to page is denied.");
			
			$tab_id = null;
			
			if($mode == 'import') {
				try {
					@$json = json_decode($import_json, true);
					
					if(empty($json) || !isset($json['tab']['extension_id']))
						throw new Exception();
					
					@$title = $json['tab']['name'];
					@$extension_id = $json['tab']['extension_id'];
					
					if(null == ($tab_extension = Extension_WorkspaceTab::get($extension_id)))
						throw new Exception();

					if(
						!isset($json['tab']['extension_id'])
						|| !isset($json['tab']['name'])
						|| !isset($json['tab']['params'])
					)
						return false;
					
					$fields = array(
						DAO_WorkspaceTab::NAME => $title,
						DAO_WorkspaceTab::POS => is_null($index) ? 99 : intval($index) + 1,
						DAO_WorkspaceTab::EXTENSION_ID => $json['tab']['extension_id'],
						DAO_WorkspaceTab::PARAMS_JSON => json_encode($json['tab']['params']),
						DAO_WorkspaceTab::WORKSPACE_PAGE_ID => $page_id,
					);
					
					if(false == ($tab_id = DAO_WorkspaceTab::create($fields)))
						throw new Exception();

					if(false == ($tab = DAO_WorkspaceTab::get($tab_id)))
						throw new Exception();
					
					if(false == $tab_extension->importTabConfigJson($json, $tab))
						throw new Exception();
					
				} catch (Exception $e) {
				}
				
				
			} else {
				$fields = array(
					DAO_WorkspaceTab::NAME => !empty($title) ? $title : 'New Tab',
					DAO_WorkspaceTab::WORKSPACE_PAGE_ID => $page_id,
					DAO_WorkspaceTab::POS => is_null($index) ? 99 : intval($index) + 1,
					DAO_WorkspaceTab::EXTENSION_ID => $extension_id,
				);
				$tab_id = DAO_WorkspaceTab::create($fields);
				
			}
			
			if(empty($tab_id))
				throw new Exception("Unable to create tab.");
			
			echo json_encode(array(
				'success' => true,
				'page_id' => $page->id,
				'tab_name' => $title,
				'tab_id' => $tab_id,
				'tab_url' => $url_writer->write(sprintf('ajax.php?c=pages&a=showWorkspaceTab&id=%d&_csrf_token=%s', $tab_id, $_SESSION['csrf_token'])),
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

		if(null == ($tab = DAO_WorkspaceTab::get($tab_id)))
			return;
		
		if(null == ($page = DAO_WorkspacePage::get($tab->workspace_page_id))
			|| !Context_WorkspacePage::isReadableByActor($page, $active_worker)
			)
			return;

		$tpl->assign('page', $page);
		$tpl->assign('tab', $tab);
		$tpl->assign('request', $request);

		if(null != ($tab_extension = $tab->getExtension())) {
			$tab_extension->renderTab($page, $tab);
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
			|| !Context_WorkspacePage::isReadableByActor($page, $active_worker)
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
			$view->options = $list_view->options;
			$view->renderLimit = $list_view->num_rows;
			$view->renderPage = 0;
			$view->is_ephemeral = 0;
			$view->view_columns = $list_view->columns;
			$view->addParams($list_view->params, true);
			if(property_exists($list_view, 'params_required'))
				$view->addParamsRequired($list_view->params_required, true);
			$view->renderSortBy = $list_view->sort_by;
			$view->renderSortAsc = $list_view->sort_asc;
			$view->renderSubtotals = $list_view->subtotals;
	
			unset($ext);
			unset($list_view);
			unset($view_class);
		}
	
		if(!empty($view)) {
			if($active_worker) {
				$labels = array();
				$values = array();
				$active_worker->getPlaceholderLabelsValues($labels, $values);
				
				$view->setPlaceholderLabels($labels);
				$view->setPlaceholderValues($values);
			}
				
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
	
			if(!Context_WorkspacePage::isWriteableByActor($page, $active_worker))
				return;
			
			$page_users = $page->getUsers();
			$tpl->assign('page_users', $page_users);
			
			$tpl->assign('workers', DAO_Worker::getAll());
			
			$tpl->assign('workspace_page', $page);
		}
	
		// Owner
		$owners_menu = Extension_DevblocksContext::getOwnerTree();
		$tpl->assign('owners_menu', $owners_menu);
		
		// Extensions
		
		$page_extensions = Extension_WorkspacePage::getAll(false);
		
		// Sort workspaces to top
		$workspaces_extension = array('core.workspace.page.workspace' => $page_extensions['core.workspace.page.workspace']);
		unset($page_extensions['core.workspace.page.workspace']);
		$page_extensions = array_merge($workspaces_extension, $page_extensions);
		
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
			
			if(!Context_WorkspacePage::isWriteableByActor($workspace_page, $active_worker))
				return;
		}
	
		if(!empty($workspace_page_id) && $do_delete) { // Delete
			DAO_WorkspacePage::delete($workspace_page_id);
	
		} else { // Create/Edit
			$fields = array(
				DAO_WorkspacePage::NAME => $name,
			);
			
			// Owner
			@list($owner_context, $owner_context_id) = explode(':', DevblocksPlatform::importGPC($_REQUEST['owner'],'string',''));
				
			switch($owner_context) {
				case CerberusContexts::CONTEXT_APPLICATION:
				case CerberusContexts::CONTEXT_ROLE:
				case CerberusContexts::CONTEXT_GROUP:
				case CerberusContexts::CONTEXT_WORKER:
					break;
					
				default:
					$owner_context = null;
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
	
	function importWorkspacePageJsonAction() {
		@$import_json = DevblocksPlatform::importGPC($_REQUEST['import_json'],'string', '');
		@$owner = DevblocksPlatform::importGPC($_REQUEST['owner'],'string', '');
		
		header('Content-Type: application/json');
		
		// [TODO] Allow configurable imports
		
		try {
			@$json = json_decode($import_json, true);
			
			if(empty($json) || !isset($json['page']))
				throw new Exception();
			
			@$name = $json['page']['name'];
			@$extension_id = $json['page']['extension_id'];
			
			if(empty($extension_id) || null == ($page_extension = Extension_WorkspacePage::get($extension_id)))
				throw new Exception();
			
			// Owner
			// [TODO] This could be cleaner
			
			@list($owner_context, $owner_context_id) = explode(':', $owner);
				
			switch($owner_context) {
				case CerberusContexts::CONTEXT_APPLICATION:
				case CerberusContexts::CONTEXT_ROLE:
				case CerberusContexts::CONTEXT_GROUP:
				case CerberusContexts::CONTEXT_WORKER:
					break;
				
				default:
					$owner_context = null;
					$owner_context_id = null;
					break;
			}
			
			// [TODO] Check $active_worker access to this context
			
			if(empty($owner_context))
				throw new Exception();
			
			// Create page
			
			$page_id = DAO_WorkspacePage::create(array(
				DAO_WorkspacePage::NAME => $name ?: 'New Page',
				DAO_WorkspacePage::EXTENSION_ID => $extension_id,
				DAO_WorkspacePage::OWNER_CONTEXT => $owner_context,
				DAO_WorkspacePage::OWNER_CONTEXT_ID => $owner_context_id,
			));
			
			if(null == ($page = DAO_WorkspacePage::get($page_id)))
				throw new Exception();
			
			if(false == $page_extension->importPageConfigJson($json, $page))
				throw new Exception();
			
			$url_writer = DevblocksPlatform::getUrlService();
			
			echo json_encode(array(
				'page_id' => $page->id,
				'page_url' => $url_writer->writeNoProxy(sprintf('c=pages&id=%d-%s', $page->id, DevblocksPlatform::strToPermalink($page->name)), true),
			));
			
		} catch(Exception $e) {
			// [TODO] Pass the error message
			echo json_encode(false);
			return;
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
	
		if(!Context_WorkspacePage::isWriteableByActor($page, $active_worker))
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
	
		if(!Context_WorkspacePage::isWriteableByActor($workspace_page, $active_worker)) {
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
	
			if(null != ($tab_extension = DevblocksPlatform::getExtension($workspace_tab->extension_id, true, true))) {
				/* @var $tab_extension Extension_WorkspaceTab */
				if(method_exists($tab_extension, 'saveTabConfig'))
					$tab_extension->saveTabConfig($workspace_page, $workspace_tab);
			}
		}
		
		echo json_encode(array(
			'name' => $name,
		));
	}
	
	function showExportWorkspacePageAction() {
		@$page_id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::getTemplateService();
		
		if(null == ($page = DAO_WorkspacePage::get($page_id)))
			return;
		
		if(!Context_WorkspacePage::isReadableByActor($page, $active_worker))
			return;
		
		$tpl->assign('page', $page);
		
		$page_extension = $page->getExtension();
		$page_json = $page_extension->exportPageConfigJson($page);
		
		$tpl->assign('json', DevblocksPlatform::strFormatJson($page_json));
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/export_page.tpl');
	}
	
	function showExportWorkspaceTabAction() {
		@$tab_id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::getTemplateService();
		
		if(null == ($tab = DAO_WorkspaceTab::get($tab_id)))
			return;
		
		if(null == ($page = DAO_WorkspacePage::get($tab->workspace_page_id)))
			return;
		
		if(!Context_WorkspacePage::isReadableByActor($page, $active_worker))
			return;

		if(null == ($tab_extension = $tab->getExtension()))
			return;
		
		@$json = $tab_extension->exportTabConfigJson($page, $tab);
		
		$tpl->assign('tab', $tab);
		$tpl->assign('page', $page);
		$tpl->assign('json', DevblocksPlatform::strFormatJson($json));
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/export_tab.tpl');
	}
};