<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/

class Page_Custom extends CerberusPageExtension {
	function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == (CerberusApplication::getActiveWorker()))
			return false;
		
		return true;
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
			$this->_renderPage($page_id, $stack);
		}
		
		return;
	}
	
	function invoke(string $action) {
		switch($action) {
			case 'invoke':
				return $this->_pageAction_invoke();
			case 'invokeWidget':
				return $this->_pageAction_invokeWidget();
			case 'renderAddTabs':
				return $this->_pageAction_renderAddTabs();
			case 'renderExport':
				return $this->_pageAction_renderExport();
			case 'renderExportTab':
				return $this->_pageAction_renderExportTab();
			case 'renderTab':
				return $this->_pageAction_renderTab();
			case 'renderWorklist':
				return $this->_pageAction_renderWorklist();
			case 'setOrder':
				return $this->_pageAction_setOrder();
			case 'setTabOrder':
				return $this->_pageAction_setTabOrder();
			case 'toggleMenuPageJson':
				return $this->_pageAction_toggleMenuPageJson();
		}
		return false;
	}
	
	private function _pageAction_invoke() {
		@$page_uri = DevblocksPlatform::importGPC($_GET['module'] ?? $_POST['module'],'string','');
		@$action = DevblocksPlatform::importGPC($_GET['action'] ?? $_POST['action'],'string','');
		
		$inst = Extension_PageSection::getExtensionByPageUri($this->manifest->id, $page_uri, true);
		
		/* @var $inst Extension_PageSection */
		
		if($inst instanceof Extension_PageSection) {
			if(false === ($inst->handleActionForPage($action, 'pageAction'))) {
				trigger_error(
					sprintf('Call to undefined page action `%s::%s`',
						get_class($inst),
						$action
					),
					E_USER_NOTICE
				);
				DevblocksPlatform::dieWithHttpError(null, 404);
			}
		}
	}
	
	private function _pageAction_invokeWidget() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$widget_id = DevblocksPlatform::importGPC($_REQUEST['widget_id'],'integer',0);
		@$action = DevblocksPlatform::importGPC(isset($_GET['action']) ? $_GET['action'] : $_REQUEST['action'],'string','');
		
		if(false == ($workspace_widget = DAO_WorkspaceWidget::get($widget_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(false == ($extension = $workspace_widget->getExtension()))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_WorkspaceWidget::isReadableByActor($workspace_widget, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if($extension instanceof Extension_WorkspaceWidget) {
			if(false === ($extension->invoke($action, $workspace_widget))) {
				trigger_error(
					sprintf('Call to undefined workspace widget action `%s::%s`',
						get_class($extension),
						$action
					),
					E_USER_NOTICE
				);
				DevblocksPlatform::dieWithHttpError(null, 404);
			}
		}
	}
	
	private function _renderIndex() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$pages = DAO_WorkspacePage::getAll();
		$tpl->assign('pages', $pages);
		
		// View
		$view_id = 'pages';
		$defaults = C4_AbstractViewModel::loadFromClass('View_WorkspacePage');
		$defaults->id = $view_id;
		
		if(null != ($view = C4_AbstractViewLoader::getView($view_id, $defaults))) {
			if(!$active_worker->is_superuser) {
				$worker_group_ids = array_keys($active_worker->getMemberships());
				$worker_role_ids = array_keys(DAO_WorkerRole::getReadableBy($active_worker->id));
				
				// Restrict owners
				
				$params = $view->getParamsFromQuickSearch(sprintf('(owner.app:cerb OR owner.worker:(id:[%d]) OR owner.group:(id:[%s]) OR owner.role:(id:[%s])',
					$active_worker->id,
					implode(',', $worker_group_ids),
					implode(',', $worker_role_ids)
				));
				
				$view->addParamsRequired(['_ownership' => $params[0]], true);
				
			} else {
				$view->removeParamRequired('_ownership');
			}
			
			$tpl->assign('view', $view);
		}
		
		$tpl->display('devblocks:cerberusweb.core::pages/index.tpl');
	}
	
	private function _renderPage($page_id, array $path=[]) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(null == ($page = DAO_WorkspacePage::get($page_id)))
			return;
		
		if(!Context_WorkspacePage::isReadableByActor($page, $active_worker)) {
			$tpl->assign('page', $page);
			$tpl->assign('error_title', DevblocksPlatform::translate('common.access_denied'));
			$tpl->assign('error_message', 'You do not have permission to view this page.');
			$tpl->display('devblocks:cerberusweb.core::pages/error.tpl');
			return;
		}
			
		$point = sprintf("pages.worker.%d.%d",
			$active_worker->id,
			$page_id
		);
		$tpl->assign('point', $point);
		
		// Active tab
		
		if(!empty($path)) {
			$tpl->assign('tab_selected', array_shift($path));
		}

		// Template
		if(null != ($page_extension = DevblocksPlatform::getExtension($page->extension_id, true)))
			$tpl->assign('page_extension', $page_extension);
		
		$tpl->assign('page', $page);
		$tpl->display('devblocks:cerberusweb.core::pages/page.tpl');
	}
	
	private function _pageAction_setOrder() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$page_ids_str = DevblocksPlatform::importGPC($_POST['pages'],'string','');
		
		$menu = [];
		$pages = DAO_WorkspacePage::getAll();
		
		$page_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::parseCsvString($page_ids_str), 'integer', array('nonzero','unique'));
		
		foreach($page_ids as $page_id) {
			if(!isset($pages[$page_id]))
				continue;

			if(!Context_WorkspacePage::isReadableByActor($pages[$page_id], $active_worker))
				continue;
			
			$menu[] = $page_id;
		}

		DAO_WorkerPref::setAsJson($active_worker->id, 'menu_json', $menu);
		
		$active_worker->clearPagesMenuCache();
		exit;
	}
	
	private function _pageAction_setTabOrder() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$page_id = DevblocksPlatform::importGPC($_POST['page_id'],'integer','0');
		@$tab_ids_str = DevblocksPlatform::importGPC($_POST['tabs'],'string','');
		
		if(null == ($page = DAO_WorkspacePage::get($page_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_WorkspacePage::isReadableByActor($page, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$tab_ids = DevblocksPlatform::sanitizeArray(DevblocksPlatform::parseCsvString($tab_ids_str), 'integer', array('nonzero','unique'));
		
		DAO_WorkerPref::setAsJson($active_worker->id, 'page_tabs_' . $page->id . '_json', $tab_ids);
		
		$active_worker->clearPagesMenuCache();
		exit;
	}
	
	private function _pageAction_renderAddTabs() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$page_id = DevblocksPlatform::importGPC($_REQUEST['page_id'],'integer','0');
		
		if(null == ($page = DAO_WorkspacePage::get($page_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_WorkspacePage::isWriteableByActor($page, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$tpl->assign('page', $page);
		$tpl->display('devblocks:cerberusweb.core::pages/add_tabs.tpl');
	}
	
	private function _pageAction_toggleMenuPageJson() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$page_id = DevblocksPlatform::importGPC($_POST['page_id'],'integer','0');
		@$toggle = DevblocksPlatform::importGPC($_POST['toggle'],'integer','0');
		
		header('Content-type: application/json');

		$pages = DAO_WorkspacePage::getAll();
		
		@$menu = json_decode(DAO_WorkerPref::get($active_worker->id, 'menu_json', '[]'));
		
		if(!is_array($menu))
			$menu = [];
		
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
		
		$active_worker->clearPagesMenuCache();
		
		echo json_encode(array(
			'success' => true,
			'page_id' => $page_id,
		));
		
		DevblocksPlatform::exit();
	}
	
	private function _pageAction_renderTab() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();

		@$tab_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer', 0);
		@$request = DevblocksPlatform::importGPC($_REQUEST['request'],'string', '');

		if(null == ($tab = DAO_WorkspaceTab::get($tab_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(null == ($page = DAO_WorkspacePage::get($tab->workspace_page_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
			
		if(!Context_WorkspacePage::isReadableByActor($page, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$tpl->assign('page', $page);
		$tpl->assign('tab', $tab);
		$tpl->assign('request', $request);

		if(null != ($tab_extension = $tab->getExtension())) {
			$tab_extension->renderTab($page, $tab);
		}
	}
	
	private function _pageAction_renderWorklist() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$list_id = DevblocksPlatform::importGPC($_POST['list_id'],'integer', 0);
	
		if(empty($list_id))
			DevblocksPlatform::dieWithHttpError(null, 404);
			
		if(null == ($list = DAO_WorkspaceList::get($list_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
			
		if(null == ($tab = DAO_WorkspaceTab::get($list->workspace_tab_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
	
		if(null == ($page = DAO_WorkspacePage::get($tab->workspace_page_id))
			|| !Context_WorkspacePage::isReadableByActor($page, $active_worker)
		)
			DevblocksPlatform::dieWithHttpError(null, 403);
	
		$view_id = 'cust_' . $list->id;
	
		// Make sure our workspace source has a valid renderer class
		if(null == ($ext = Extension_DevblocksContext::get($list->context))) {
			DevblocksPlatform::dieWithHttpError(null, 404);
		}
		
		if(null == ($view = C4_AbstractViewLoader::getView($view_id))) {
			$view = $ext->getChooserView($view_id);  /* @var $view C4_AbstractView */
			
			if(empty($view))
				DevblocksPlatform::dieWithHttpError(null, 404);
			
			$view->name = $list->name;
			$view->options = $list->options;
			$view->renderLimit = $list->render_limit;
			$view->renderPage = 0;
			$view->is_ephemeral = 0;
			$view->view_columns = $list->columns;
			$view->addParams($list->getParamsEditable(), true);
			$view->addParamsRequired($list->getParamsRequired(), true);
			$view->setParamsRequiredQuery($list->params_required_query);
			$view->renderSortBy = array_keys($list->render_sort);
			$view->renderSortAsc = array_values($list->render_sort);
			$view->renderSubtotals = $list->render_subtotals;
		}
		
		$tpl->assign('view', $view);
		$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
	}
	
	private function _pageAction_renderExport() {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		
		@$page_id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		
		if(null == ($page = DAO_WorkspacePage::get($page_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_WorkspacePage::isReadableByActor($page, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$tpl->assign('page', $page);
		
		$page_extension = $page->getExtension();
		$page_json = $page_extension->exportPageConfigJson($page);
		
		$tpl->assign('json', DevblocksPlatform::strFormatJson($page_json));
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/export_page.tpl');
	}
	
	private function _pageAction_renderExportTab() {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		
		@$tab_id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		
		if(null == ($tab = DAO_WorkspaceTab::get($tab_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(null == ($page = DAO_WorkspacePage::get($tab->workspace_page_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_WorkspacePage::isReadableByActor($page, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);

		if(null == ($tab_extension = $tab->getExtension()))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		@$json = $tab_extension->exportTabConfigJson($page, $tab);
		
		$tpl->assign('tab', $tab);
		$tpl->assign('page', $page);
		$tpl->assign('json', DevblocksPlatform::strFormatJson($json));
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/export_tab.tpl');
	}
};