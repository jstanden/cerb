<?php
/***********************************************************************
 | Cerb(tm) developed by Webgroup Media, LLC.
 |-----------------------------------------------------------------------
 | All source code & content (c) Copyright 2013, Webgroup Media LLC
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

if(class_exists('Extension_PageSection')):
class PageSection_InternalWorkspaces extends Extension_PageSection {
	function render() {}
};
endif;

if(class_exists('Extension_WorkspacePage')):
class WorkspacePage_Workspace extends Extension_WorkspacePage {
	function renderPage(Model_WorkspacePage $page) {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('page', $page);

		$tabs = $page->getTabs($active_worker);
		$tpl->assign('page_tabs', $tabs);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/pages/default/page.tpl');
	}
	
	function exportPageConfigJson(Model_WorkspacePage $page) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$json_array = array(
			'page' => array(
				'name' => $page->name,
				'extension_id' => $page->extension_id,
				'tabs' => array(),
			),
		);
		
		$tabs = $page->getTabs($active_worker);

		if(is_array($tabs))
		foreach($tabs as $tab) { /* @var $tab Model_WorkspaceTab */
			if(null == ($tab_extension = $tab->getExtension())) /* @var $tab_extension Extension_WorkspaceTab */
				continue;
			
			@$tab_json = json_decode($tab_extension->exportTabConfigJson($page, $tab), true);
			
			if(!empty($tab_json))
				$json_array['page']['tabs'][] = $tab_json;
		}
		
		return json_encode($json_array);
	}
	
	function importPageConfigJson($import_json, Model_WorkspacePage $page) {
		if(!is_array($import_json) || !isset($import_json['page']))
			return false;
		
		if(!isset($import_json['page']['tabs']) || !is_array($import_json['page']['tabs']))
			return false;
		
		foreach($import_json['page']['tabs'] as $pos => $tab_json) {
			if(null == (@$tab_extension_id = $tab_json['tab']['extension_id']))
				return false;
			
			if(null == ($tab_extension = Extension_WorkspaceTab::get($tab_extension_id)))
				return false;
			
			@$name = $tab_json['tab']['name'];
			@$params = $tab_json['tab']['params'] ?: array();
			
			$tab_id = DAO_WorkspaceTab::create(array(
				DAO_WorkspaceTab::NAME => $name ?: 'New Tab',
				DAO_WorkspaceTab::EXTENSION_ID => $tab_extension_id,
				DAO_WorkspaceTab::POS => $pos,
				DAO_WorkspaceTab::WORKSPACE_PAGE_ID => $page->id,
				DAO_WorkspaceTab::PARAMS_JSON => json_encode($params),
			));
			
			if(empty($tab_id) || null == ($tab = DAO_WorkspaceTab::get($tab_id)))
				return false;
			
			$tab_extension->importTabConfigJson($tab_json, $tab);
		}
		
		return true;
	}
	
};
endif;

if(class_exists('Extension_WorkspaceTab')):
class WorkspaceTab_Worklists extends Extension_WorkspaceTab {
	public function renderTab(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('workspace_page', $page);
		$tpl->assign('workspace_tab', $tab);
		
		$lists = $tab->getWorklists();
		$list_ids = array_keys($lists);
		unset($lists);
		
		$tpl->assign('list_ids', $list_ids);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/tabs/worklists/tab.tpl');
	}
	
	function renderTabConfig(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/tabs/worklists/config.tpl');
	}
	
	function saveTabConfig(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		@$ids = DevblocksPlatform::importGPC($_POST['ids'],'array', array());
		@$names = DevblocksPlatform::importGPC($_POST['names'],'array', array());
		
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
				$list->subtotals = $view->renderSubtotals;

				// Add the worklist
				$fields = array(
					DAO_WorkspaceList::LIST_POS => $idx,
					DAO_WorkspaceList::LIST_VIEW => serialize($list),
					DAO_WorkspaceList::WORKSPACE_TAB_ID => $tab->id,
					DAO_WorkspaceList::CONTEXT => $id,
				);
				$ids[$idx] = DAO_WorkspaceList::create($fields);
			}
		}

		$worklists = $tab->getWorklists();

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
	}
	
	function exportTabConfigJson(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {
		$json = array(
			'tab' => array(
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
				$list_view = $worklist->list_view; /* @var $list_view Model_WorkspaceListView */

				if(null == ($ext = Extension_DevblocksContext::get($worklist->context)))
					continue;
				
				$view = $ext->getChooserView($view_id);  /* @var $view C4_AbstractView */
					
				if(empty($view))
					continue;
					
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
			}
			
			$model = array(
				'columns' => $view->view_columns,
				'params' => json_decode(json_encode($view->getEditableParams()), true),
				'params_required' => json_decode(json_encode($view->getParamsRequired()), true),
				'limit' => $view->renderLimit,
				'sort_by' => $view->renderSortBy,
				'sort_asc' => !empty($view->renderSortAsc),
				'subtotals' => $view->renderSubtotals,
				'context' => $worklist->context,
			);
			
			$worklist_json = array(
				'worklist' => array(
					'pos' => $worklist->list_pos,
					'title' => $worklist->list_view->title,
					'model' => $model,
				),
			);
			
			$json['tab']['worklists'][] = $worklist_json;
		}
		
		return json_encode($json);
	}
	
	function importTabConfigJson($json, Model_WorkspaceTab $tab) {
		if(empty($tab) || empty($tab->id) || !is_array($json) || !isset($json['tab']))
			return false;
		
		if(!isset($json['tab']['worklists']))
			return false;
		
		foreach($json['tab']['worklists'] as $worklist) {
			$worklist_view = C4_AbstractViewLoader::unserializeViewFromAbstractJson($worklist['worklist']['model'], '');
			
			// [TODO] This is sloppy, we need to convert it.
			$view_model = C4_AbstractViewLoader::serializeAbstractView($worklist_view);
			
			$list_view = new Model_WorkspaceListView();
			$list_view->title = $worklist['worklist']['title'];
			$list_view->columns = $view_model->view_columns;
			$list_view->num_rows = $view_model->renderLimit;
			$list_view->params = $view_model->paramsEditable;
			$list_view->params_required = $view_model->paramsRequired;
			$list_view->sort_by = $view_model->renderSortBy;
			$list_view->sort_asc = $view_model->renderSortAsc;
			$list_view->subtotals = $view_model->renderSubtotals;
			
			$worklist_id = DAO_WorkspaceList::create(array(
				DAO_WorkspaceList::CONTEXT => $worklist['worklist']['model']['context'],
				DAO_WorkspaceList::LIST_POS => $worklist['worklist']['pos'],
				DAO_WorkspaceList::LIST_VIEW => serialize($list_view),
				DAO_WorkspaceList::WORKSPACE_TAB_ID => $tab->id,
			));
		}
		
		return true;
	}
};
endif;