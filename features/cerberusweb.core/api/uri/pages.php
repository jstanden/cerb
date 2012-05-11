<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2012, WebGroup Media LLC
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
	
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		
		$stack = $response->path;
		@array_shift($stack); // pages
		@$page_uri = array_shift($stack);

		if(intval($page_uri) > 0) {
			$page_id = intval($page_uri);
		}
		
		// [TODO] If empty, show the page selection link
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
		
		// [TODO] Cache
		$pages = DAO_WorkspacePage::getWhere();
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
		
		// [TODO] Cache
		if(null == ($page = DAO_WorkspacePage::get($page_id))) {
			return;
		}
			
		$tpl->assign('page', $page);
		
		$point = sprintf("pages.worker.%d.%d",
			$active_worker->id,
			$page_id
		);
		$tpl->assign('point', $point);
		
		$tabs = $page->getTabs($active_worker);
		$tpl->assign('page_tabs', $tabs);
		
		$tpl->display('devblocks:cerberusweb.core::pages/tabs.tpl');
	}
	
	function setPageOrderAction() {
		@$page_ids_str = DevblocksPlatform::importGPC($_REQUEST['pages'],'string','');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$menu = array();
		$pages = DAO_WorkspacePage::getWhere();
		
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
		
		$tpl->display('devblocks:cerberusweb.core::pages/add_tabs.tpl');
	}
	
	function doToggleMenuPageJsonAction() {
		@$page_id = DevblocksPlatform::importGPC($_REQUEST['page_id'],'integer','0');
		@$toggle = DevblocksPlatform::importGPC($_REQUEST['toggle'],'integer','0');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-type: application/json');

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
				
				DAO_WorkerPref::set($active_worker->id, 'menu_json', json_encode(array_values($menu)));
			}
		}
		
		echo json_encode(array(
			'success' => true,
			'page_id' => $page_id,
		));
		
		exit;
	}
	
	function doAddCustomTabJsonAction() {
		@$page_id = DevblocksPlatform::importGPC($_REQUEST['page_id'],'integer','0');
		@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string','');
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
			);
			$tab_id = DAO_WorkspaceTab::create($fields);
			
			if(empty($tab_id))
				throw new Exception("Unable to create tab.");
			
			echo json_encode(array(
				'success' => true,
				'page_id' => $page->id,
				'tab_id' => $tab_id,
				'tab_url' => $url_writer->write('ajax.php?c=internal&a=showWorkspaceTab&id=' . $tab_id),
			));
			
		} catch(Exception $e) {
			echo json_encode(array(
				'success' => false,
			));
		}
		
		exit;
	}
	
};