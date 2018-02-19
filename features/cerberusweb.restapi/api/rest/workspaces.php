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
	}
	
	private function _getPagesList() {
		$worker = CerberusApplication::getActiveWorker();

		$workspace_pages = DAO_WorkspacePage::getByWorker($worker);
		
		$results = [];

		foreach($workspace_pages as $workspace_page) {
			// We only show workspace pages at the moment
			if(!in_array($workspace_page->extension_id, array('core.workspace.page.workspace')))
				continue;
			
			$labels = [];
			$values = [];
			CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKSPACE_PAGE, $workspace_page, $labels, $values, null, true);
			
			$results[] = $values;
		}
		
		$container = array(
			'total' => count($results),
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

		$labels = [];
		$values = [];
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
		
		$labels = [];
		$values = [];
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

		$labels = [];
		$values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKSPACE_WIDGET, $workspace_widget, $labels, $values, null, true);
		
		// Force load data for direct widget requests
		$dict = new DevblocksDictionaryDelegate($values);
		$dict->data;
		$values = $dict->getDictionary();
		
		$this->success($values);
	}
	
	private function _getWorklist($id) {
		$worker = CerberusApplication::getActiveWorker();
		$param_page = max(@DevblocksPlatform::importGPC($_REQUEST['page'], 'integer', 0), 1);
		$param_limit = DevblocksPlatform::intClamp(@DevblocksPlatform::importGPC($_REQUEST['limit'], 'integer', 10), 1, 100);
		
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
		if(null == ($ext = Extension_DevblocksContext::get($workspace_worklist->context))) {
			return;
		}
		
		if(null == ($view = C4_AbstractViewLoader::getView($view_id))) {
			$view = $ext->getChooserView($view_id);  /* @var $view C4_AbstractView */
				
			if(empty($view))
				return;
				
			$view->name = $workspace_worklist->name;
			$view->options = $workspace_worklist->options;
			$view->renderLimit = $workspace_worklist->render_limit;
			$view->renderPage = 0;
			$view->is_ephemeral = 0;
			$view->view_columns = $workspace_worklist->columns;
			$view->addParams($workspace_worklist->getParamsEditable(), true);
			$view->addParamsRequired($workspace_worklist->getParamsRequired(), true);
			$view->renderSortBy = array_keys($workspace_worklist->render_sort);
			$view->renderSortAsc = array_values($workspace_worklist->render_sort);
			$view->renderSubtotals = $workspace_worklist->render_subtotals;
		}
		
		if(!empty($view)) {
			if($worker) {
				$labels = [];
				$values = [];
				$worker->getPlaceholderLabelsValues($labels, $values);
				
				$view->setPlaceholderLabels($labels);
				$view->setPlaceholderValues($values);
			}
			
			$view->persist();
		}
		
		$view->setAutoPersist(false);
		
		$view->renderPage = $param_page - 1;
		$view->renderLimit = $param_limit;
		
		list($worklist_rows, $total) = $view->getData();
		
		$results = [];
		
		$row_ids = array_keys($worklist_rows);

		foreach($row_ids as $row_id) {
			$labels = [];
			$values = [];
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