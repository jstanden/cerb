<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2026, Webgroup Media LLC
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

class WorkspacePage_Workspace extends Extension_WorkspacePage {
	const ID = 'core.workspace.page.workspace';
	
	function renderPage(Model_WorkspacePage $page) {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('page', $page);
		
		$tabs = $page->getTabs();
		
		// Does this page allow workers to sort tabs?
		if($page->extension_params['tab_sorting'] ?? null) {
			$tab_order = array_flip(DAO_WorkerPref::getAsJson($active_worker->id, 'page_tabs_' . $page->id . '_json', '[]'));
			
			// Reorder tabs by worker prefs
			if($tab_order) {
				uksort($tabs, function ($a, $b) use ($tab_order) {
					if (!array_key_exists($a, $tab_order)) return 1;
					if (!array_key_exists($b, $tab_order)) return -1;
					return $tab_order[$a] <=> $tab_order[$b];
				});
			}
		}
		
		$tpl->assign('page_tabs', $tabs);
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/pages/default/page.tpl');
	}
	
	function renderConfig(Model_WorkspacePage $page, $params=[], $params_prefix=null) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('page', $page);
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/pages/default/config.tpl');
	}
	
	function exportPageConfigJson(Model_WorkspacePage $page) {
		$json_array = array(
			'page' => array(
				'uid' => 'workspace_page_' . $page->id,
				'_context' => CerberusContexts::CONTEXT_WORKSPACE_PAGE,
				'name' => $page->name,
				'extension_id' => $page->extension_id,
				'tabs' => [],
			),
		);
		
		$tabs = $page->getTabs();

		if(is_array($tabs))
		foreach($tabs as $tab) { /* @var $tab Model_WorkspaceTab */
			if(null == ($tab_extension = $tab->getExtension())) /* @var $tab_extension Extension_WorkspaceTab */
				continue;
			
			@$tab_json = json_decode($tab_extension->exportTabConfigJson($page, $tab), true);
			
			if(!empty($tab_json))
				$json_array['page']['tabs'][] = $tab_json['tab'];
		}
		
		return json_encode($json_array);
	}
	
	function importPageConfigJson($import_json, Model_WorkspacePage $page) {
		if(!is_array($import_json) || !isset($import_json['page']))
			return false;
		
		if(!isset($import_json['page']['tabs']) || !is_array($import_json['page']['tabs']))
			return false;
		
		foreach($import_json['page']['tabs'] as $pos => $tab_json) {
			if(null == (@$tab_extension_id = $tab_json['extension_id']))
				return false;
			
			if(null == ($tab_extension = Extension_WorkspaceTab::get($tab_extension_id)))
				return false;
			
			$name = $tab_json['name'] ?? null;
			$params = $tab_json['params'] ?? [];
			
			$tab_id = DAO_WorkspaceTab::create(array(
				DAO_WorkspaceTab::NAME => $name ?: 'New Tab',
				DAO_WorkspaceTab::EXTENSION_ID => $tab_extension_id,
				DAO_WorkspaceTab::POS => $pos,
				DAO_WorkspaceTab::WORKSPACE_PAGE_ID => $page->id,
				DAO_WorkspaceTab::PARAMS_JSON => json_encode($params),
				DAO_WorkspaceTab::OPTIONS_KATA => $tab_json['options_kata'] ?? '',
			));
			
			if(empty($tab_id) || null == ($tab = DAO_WorkspaceTab::get($tab_id)))
				return false;
			
			$tab_extension->importTabConfigJson($tab_json, $tab);
		}
		
		return true;
	}
	
};