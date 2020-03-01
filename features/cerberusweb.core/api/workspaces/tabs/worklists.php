<?php
class WorkspaceTab_Worklists extends Extension_WorkspaceTab {
	const ID = 'core.workspace.tab.worklists';
	
	public function renderTab(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('workspace_page', $page);
		$tpl->assign('workspace_tab', $tab);
		
		$worklists = $tab->getWorklists();
		$tpl->assign('worklists', $worklists);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/tabs/worklists/tab.tpl');
	}
	
	function renderTabConfig(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		$tpl = DevblocksPlatform::services()->template();
		
		$worklists = $tab->getWorklists();
		$tpl->assign('worklists', $worklists);
		
		$contexts = Extension_DevblocksContext::getAll(false, ['workspace']);
		$tpl->assign('contexts', $contexts);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/tabs/worklists/config.tpl');
	}
	
	function saveTabConfig(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		@$ids = DevblocksPlatform::importGPC($_POST['ids'],'array', []);
		@$names = DevblocksPlatform::importGPC($_POST['names'],'array', []);
		
		// Create any new worklists
		if(is_array($ids) && !empty($ids))
			foreach($ids as $idx => $id) {
			if(!is_numeric($id)) { // Create
				if(null == ($context_ext = Extension_DevblocksContext::get($id)))
					continue;
				
				if(null == ($view = $context_ext->getChooserView()))  /* @var $view C4_AbstractView */
					continue;
				
				// Add the worklist
				$fields = [
					DAO_WorkspaceList::COLUMNS_JSON => json_encode($view->view_columns),
					DAO_WorkspaceList::CONTEXT => $id,
					DAO_WorkspaceList::NAME => $names[$idx],
					DAO_WorkspaceList::OPTIONS_JSON => json_encode($view->options),
					DAO_WorkspaceList::PARAMS_EDITABLE_JSON => json_encode($view->getParams()),
					DAO_WorkspaceList::PARAMS_REQUIRED_JSON => json_encode($view->getParamsRequired()),
					DAO_WorkspaceList::PARAMS_REQUIRED_QUERY => $view->getParamsRequiredQuery(),
					DAO_WorkspaceList::RENDER_LIMIT => $view->renderLimit,
					DAO_WorkspaceList::RENDER_SORT_JSON => json_encode($view->getSorts()),
					DAO_WorkspaceList::RENDER_SUBTOTALS => $view->renderSubtotals ?: '',
					DAO_WorkspaceList::WORKSPACE_TAB_ID => $tab->id,
					DAO_WorkspaceList::WORKSPACE_TAB_POS => $idx,
				];
				
				$worklist_id = DAO_WorkspaceList::create($fields);
				$ids[$idx] = $worklist_id;
				
				// Clear any worklist caches using this identifier
				// [TODO] Why does this happen??
				C4_AbstractViewLoader::deleteView('cust_' . $worklist_id);
			}
		}
		
		$worklists = $tab->getWorklists();
		
		// Deletes
		$delete_ids = array_diff(array_keys($worklists), $ids);
		if(is_array($delete_ids) && !empty($delete_ids)) {
			CerberusContexts::logActivityRecordDelete(CerberusContexts::CONTEXT_WORKSPACE_WORKLIST, $delete_ids);
			DAO_WorkspaceList::delete($delete_ids);
		}

		// Reorder worklists, rename lists, on workspace
		if(is_array($ids) && !empty($ids)) {
			foreach($ids as $idx => $id) {
				if(null == (DAO_WorkspaceList::get($id)))
					continue;
				
				$worklist_name = $names[$idx];
	
				// Save the view in the session
				if(false != ($view = C4_AbstractViewLoader::getView('cust_' . $id))) {
					$view->name = $worklist_name;
				}
				
				// Save the view in the database
				DAO_WorkspaceList::update($id, [
					DAO_WorkspaceList::NAME => $worklist_name,
					DAO_WorkspaceList::WORKSPACE_TAB_POS => intval($idx),
				]);
			}
		}
	}
	
	function exportTabConfigJson(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		$json = array(
			'tab' => array(
				'uid' => 'workspace_tab_' . $tab->id,
				'_context' => CerberusContexts::CONTEXT_WORKSPACE_TAB,
				'name' => $tab->name,
				'extension_id' => $tab->extension_id,
				'params' => $tab->params,
				'worklists' => array(),
			),
		);
		
		$worklists = DAO_WorkspaceList::getByTab($tab->id);
		
		foreach($worklists as $worklist) {
			$view_id = 'cust_' . $worklist->id;
			
			if(null == ($view = C4_AbstractViewLoader::getView($view_id))) {
				if(null == ($ext = Extension_DevblocksContext::get($worklist->context)))
					continue;
				
				$view = $ext->getChooserView($view_id);  /* @var $view C4_AbstractView */
					
				if(empty($view))
					continue;
					
				$view->name = $worklist->name;
				$view->renderLimit = $worklist->render_limit;
				$view->renderPage = 0;
				$view->is_ephemeral = 0;
				$view->view_columns = $worklist->columns;
				$view->addParams($worklist->params_editable, true);
				$view->addParamsRequired($worklist->params_required, true);
				$view->renderSortBy = array_keys($worklist->render_sort);
				$view->renderSortAsc = array_values($worklist->render_sort);
				$view->options = $worklist->options;
			}
			
			$sorts = $view->getSorts();
			
			$model = array(
				'options' => json_decode(json_encode($view->options), true),
				'columns' => $view->view_columns,
				'params' => json_decode(json_encode($view->getEditableParams()), true),
				'params_required' => json_decode(json_encode($view->getParamsRequired()), true),
				'params_required_query' => $view->getParamsRequiredQuery(),
				'limit' => $view->renderLimit,
				'sort_by' => array_keys($sorts),
				'sort_asc' => array_values($sorts),
				'subtotals' => $view->renderSubtotals,
				'context' => $worklist->context,
			);
			
			$worklist_json = array(
				'pos' => $worklist->workspace_tab_pos,
				'title' => $worklist->name,
				'model' => $model,
			);
			
			$json['tab']['worklists'][] = $worklist_json;
		}
		
		return json_encode($json);
	}
	
	function importTabConfigJson($json, Model_WorkspaceTab $tab) {
		if(empty($tab) || empty($tab->id) || !is_array($json))
			return false;
		
		// Backwards compatibility
		if(isset($json['tab']))
			$json = $json['tab'];
			
		if(!isset($json['worklists']))
			return false;
		
		foreach($json['worklists'] as $worklist) {
			// [TODO] Reuse this somewhere
			$sort_by = !is_array($worklist['model']['sort_by']) ? [$worklist['model']['sort_by']] : $worklist['model']['sort_by'];
			$sort_asc = !is_array($worklist['model']['sort_asc']) ? [$worklist['model']['sort_asc']] : $worklist['model']['sort_asc'];
			if(count($sort_by) != count($sort_asc)) {
				$sort_by = array_slice($sort_by, 0, 1);
				$sort_asc = array_slice($sort_asc, 0, 1);
			}
			$sorts = array_combine($sort_by, $sort_asc);
			
			$fields = [
				DAO_WorkspaceList::CONTEXT => $worklist['model']['context'],
				DAO_WorkspaceList::NAME => $worklist['title'],
				DAO_WorkspaceList::OPTIONS_JSON => json_encode(@$worklist['model']['options'] ?: []),
				DAO_WorkspaceList::COLUMNS_JSON => json_encode(@$worklist['model']['columns'] ?: []),
				DAO_WorkspaceList::PARAMS_EDITABLE_JSON => json_encode(@$worklist['model']['params'] ?: []),
				DAO_WorkspaceList::PARAMS_REQUIRED_JSON => json_encode(@$worklist['model']['params_required'] ?: []),
				DAO_WorkspaceList::PARAMS_REQUIRED_QUERY => @$worklist['model']['params_required_query'] ?: '',
				DAO_WorkspaceList::RENDER_SORT_JSON => json_encode($sorts),
				DAO_WorkspaceList::RENDER_LIMIT => @$worklist['model']['limit'] ?: 10,
				DAO_WorkspaceList::RENDER_SUBTOTALS => @$worklist['model']['subtotals'] ?: '',
				DAO_WorkspaceList::WORKSPACE_TAB_POS => $worklist['pos'],
				DAO_WorkspaceList::WORKSPACE_TAB_ID => $tab->id,
			];
			
			// [TODO] Validation
			
			DAO_WorkspaceList::create($fields);
		}
		
		return true;
	}
};