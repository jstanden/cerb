<?php
class ChRest_Workspaces extends Extension_RestController { // implements IExtensionRestController
	function getAction($stack) {
		@$context_tag = array_shift($stack);
		
		switch($context_tag) {
			case 'pages':
				@$action = array_shift($stack);
				
				switch($action) {
					case 'list':
						$this->_getPagesList();
						break;
					
					default:
						if(is_numeric($action))
							$this->_getPage($action);
						
						$this->error(self::ERRNO_NOT_IMPLEMENTED);
						break;
				}
				break;
				
			case 'tabs':
				@$action = array_shift($stack);

				if(is_numeric($action))
					$this->_getTab($action);
				
				$this->error(self::ERRNO_NOT_IMPLEMENTED);
				break;
				
			case 'worklists':
				@$action = array_shift($stack);
				
				if(is_numeric($action))
					$this->_getWorklist($action);

				$this->error(self::ERRNO_NOT_IMPLEMENTED);
				break;
				
			case 'widgets':
				@$action = array_shift($stack);
				
				if(is_numeric($action))
					$this->_getWidget($action);
				
				$this->error(self::ERRNO_NOT_IMPLEMENTED);
				break;
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function putAction($stack) {
		@$action = array_shift($stack);
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function postAction($stack) {
		@$action = array_shift($stack);
		
		switch($action) {
			//case 'search':
			//	$this->postSearch();
			//	break;
		}
		
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
	}
	
	function deleteAction($stack) {
		// Consistency with the Web-UI
		$this->error(self::ERRNO_NOT_IMPLEMENTED);
		
//		$worker = CerberusApplication::getActiveWorker();
//		if(!$worker->hasPriv('core.addybook.person.actions.delete'))
//			$this->error(self::ERRNO_ACL);
//
//		$id = array_shift($stack);
//
//		if(null == ($task = DAO_Address::get($id)))
//			$this->error(self::ERRNO_CUSTOM, sprintf("Invalid address ID %d", $id));
//
//		DAO_Address::delete($id);
//
//		$result = array('id' => $id);
//		$this->success($result);
	}
	
	private function _getPagesList() {
		$worker = CerberusApplication::getActiveWorker();

		$workspace_pages = DAO_WorkspacePage::getByWorker($worker);
		
		$results = array();

		foreach($workspace_pages as $workspace_page) {
			if(!empty($workspace_page->extension_id))
				continue;
			
			$labels = array();
			$values = array();
			CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKSPACE_PAGE, $workspace_page, $labels, $values, null, true);
			
			$results[] = $values;
		}
		
		$container = array(
			'total' => count($results), // [TODO] $total
			'count' => count($results),
			'page' => 0,
			'results' => $results,
		);
		
		$this->success($container);
	}
	
	private function _getPage($id) {
		$worker = CerberusApplication::getActiveWorker();

		$workspace_pages = DAO_WorkspacePage::getByWorker($worker);
		
		// [TODO] Allow superuser to see any pages
		
		if(!isset($workspace_pages[$id]))
			$this->error(self::ERRNO_CUSTOM, "You do not have permission to view this page.");

		$labels = array();
		$values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKSPACE_PAGE, $workspace_pages[$id], $labels, $values, null, true);
		
		$this->success($values);
	}
	
	private function _getTab($id) {
		$worker = CerberusApplication::getActiveWorker();

		if(null == ($workspace_tab = DAO_WorkspaceTab::get($id)))
			$this->error(self::ERRNO_CUSTOM, "The requested tab does not exist.");
		
		if($worker->is_superuser) {
			// They can see any tab
			
		} else {
			$workspace_pages = DAO_WorkspacePage::getByWorker($worker);
			
			if(!isset($workspace_pages[$id]))
				$this->error(self::ERRNO_CUSTOM, "You do not have permission to view this tab.");
		}
		
		$labels = array();
		$values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKSPACE_TAB, $workspace_tab, $labels, $values, null, true);
		
		$this->success($values);
	}
	
	private function _getWidget($id) {
		$worker = CerberusApplication::getActiveWorker();

		if(null == ($workspace_widget = DAO_WorkspaceWidget::get($id)))
			$this->error(self::ERRNO_CUSTOM, "The requested widget does not exist.");
		
		if($worker->is_superuser) {
			// They can see any widget
		} else {
			if(null == ($workspace_tab = DAO_WorkspaceTab::get($workspace_widget->workspace_tab_id)))
				$this->error(self::ERRNO_CUSTOM, "The requested widget is on an invalid workspace tab.");
			
			$workspace_pages = DAO_WorkspacePage::getByWorker($worker);

			if(!isset($workspace_pages[$workspace_tab->workspace_page_id]))
				$this->error(self::ERRNO_CUSTOM, "You do not have permission to view this widget.");
				
		}

		$labels = array();
		$values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKSPACE_WIDGET, $workspace_widget, $labels, $values, null, true);
		
		// Force load data for direct widget requests
		$dict = new DevblocksDictionaryDelegate($values);
		$dict->data;
		$values = $dict->getDictionary();
		
		$this->success($values);
	}
	
	private function _getWorklist($id) {
		$worker = CerberusApplication::getActiveWorker();
		$param_page = max(DevblocksPlatform::importGPC($_REQUEST['page'], 'integer', 0), 1);
		$param_limit = DevblocksPlatform::intClamp(DevblocksPlatform::importGPC($_REQUEST['limit'], 'integer', 10), 1, 100);
		
		if(null == ($workspace_worklist = DAO_WorkspaceList::get($id)))
			$this->error(self::ERRNO_CUSTOM, "The requested worklist does not exist.");
		
		if($worker->is_superuser) {
			// They can see any widget
		} else {
			if(null == ($workspace_tab = DAO_WorkspaceTab::get($workspace_worklist->workspace_tab_id)))
				$this->error(self::ERRNO_CUSTOM, "The requested worklist is on an invalid workspace tab.");
			
			$workspace_pages = DAO_WorkspacePage::getByWorker($worker);

			if(!isset($workspace_pages[$workspace_tab->workspace_page_id]))
				$this->error(self::ERRNO_CUSTOM, "You do not have permission to view this worklist.");
				
		}
		
		$view_id = 'cust_' . $workspace_worklist->id;
		
			// Make sure our workspace source has a valid renderer class
		if(null == ($ext = DevblocksPlatform::getExtension($workspace_worklist->context, true))) { /* @var $ext Extension_DevblocksContext */
			return;
		}
		
		// [TODO] Convert this to the abstract worklist format
		if(null == ($view = C4_AbstractViewLoader::getView($view_id))) {
			$list_view = $workspace_worklist->list_view; /* @var $list_view Model_WorkspaceListView */
				
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
			$view->renderSubtotals = $list_view->subtotals;
		}
	
		if(!empty($view)) {
			$labels = array();
			$values = array();
				
			$labels['current_worker_id'] = array(
				'label' => 'Current Worker',
				'context' => CerberusContexts::CONTEXT_WORKER,
			);
				
			$values['current_worker_id'] = $worker->id;
	
			$view->setPlaceholderLabels($labels);
			$view->setPlaceholderValues($values);
				
			C4_AbstractViewLoader::setView($view_id, $view);
		}
		
		$view->renderPage = $param_page - 1;
		$view->renderLimit = $param_limit;
		
		list($worklist_rows, $total) = $view->getData();
		
		$results = array();
		
		$row_ids = array_keys($worklist_rows);

		foreach($row_ids as $row_id) {
			$labels = array();
			$values = array();
			CerberusContexts::getContext($workspace_worklist->context, $row_id, $labels, $values, null, true);
			$results[] = $values;
		}
		
		$container = array(
			'name' => $view->name,
			'total' => $total,
			'count' => count($results),
			'page' => $param_page,
			'results' => $results,
		);
		
		$this->success($container);
	}
}