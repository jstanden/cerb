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

class WorkspacePage_Workspace extends Extension_WorkspacePage {
	const ID = 'core.workspace.page.workspace';
	
	function renderPage(Model_WorkspacePage $page) {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('page', $page);
		
		$tabs = $page->getTabs($active_worker);
		$tpl->assign('page_tabs', $tabs);
		
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/pages/default/page.tpl');
	}
	
	function renderConfig(Model_WorkspacePage $page, $params=[], $params_prefix=null) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('page', $page);
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/pages/default/config.tpl');
	}
	
	function exportPageConfigJson(Model_WorkspacePage $page) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$json_array = array(
			'page' => array(
				'uid' => 'workspace_page_' . $page->id,
				'_context' => CerberusContexts::CONTEXT_WORKSPACE_PAGE,
				'name' => $page->name,
				'extension_id' => $page->extension_id,
				'tabs' => [],
			),
		);
		
		$tabs = $page->getTabs($active_worker);

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
			
			@$name = $tab_json['name'];
			@$params = $tab_json['params'] ?: [];
			
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