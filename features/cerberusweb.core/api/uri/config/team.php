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

class PageSection_SetupTeam extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		$response = DevblocksPlatform::getHttpResponse();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$visit->set(ChConfigurationPage::ID, 'team');
		
		$stack = $response->path;
		@array_shift($stack); // config
		@array_shift($stack); // team
		@$tab = array_shift($stack);
		$tpl->assign('tab', $tab);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/team/index.tpl');
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('configAction' == $scope) {
			return match ($action) {
				'renderTabRoles' => $this->_configAction_renderTabRoles(),
				'renderTabGroups' => $this->_configAction_renderTabGroups(),
				'renderTabWorkers' => $this->_configAction_renderTabWorkers(),
				'saveConfigJson' => $this->_configAction_saveConfigJson(),
				default => $this->_configAction_renderTabConfig(),
			};
		}
		return false;
	}
	
	private function _configAction_renderTabConfig() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$default_page_ids = explode(',', DevblocksPlatform::getPluginSetting('cerberusweb.core', 'new_worker_default_page_ids', ''));
		
		if($default_page_ids) {
			$default_workspaces = DAO_WorkspacePage::getIds($default_page_ids);
			$tpl->assign('default_workspaces', $default_workspaces);
		}
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/team/tab_config.tpl');
	}

	private function _configAction_saveConfigJson() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		header('Content-Type: application/json; charset=utf-8');
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		try {
			// Default pages
			$default_page_ids = DevblocksPlatform::importGPC($_POST['default_pages'] ?? null, 'array', []);
			DevblocksPlatform::setPluginSetting('cerberusweb.core', 'new_worker_default_page_ids', implode(',', $default_page_ids));
		
		} catch (Throwable $e) {
			DevblocksPlatform::logException($e);
		}
		
		echo json_encode([]);
	}
	
	private function _configAction_renderTabRoles() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$defaults = C4_AbstractViewModel::loadFromClass('View_WorkerRole');
		$defaults->id = 'config_roles';
		$defaults->name = DevblocksPlatform::translateCapitalized('common.roles');
		
		if(null != ($view = C4_AbstractViewLoader::getView($defaults->id, $defaults))) {
			$tpl->assign('view', $view);
		}
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
	}
	
	private function _configAction_renderTabGroups() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$defaults = C4_AbstractViewModel::loadFromClass('View_Group');
		$defaults->id = 'config_groups';
		$defaults->name = DevblocksPlatform::translateCapitalized('common.groups');
		
		if(null != ($view = C4_AbstractViewLoader::getView($defaults->id, $defaults))) {
			$tpl->assign('view', $view);
		}
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
	}
	
	private function _configAction_renderTabWorkers() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$defaults = C4_AbstractViewModel::loadFromClass('View_Worker');
		$defaults->id = 'config_workers';
		$defaults->name = DevblocksPlatform::translateCapitalized('common.workers');
		
		if(null != ($view = C4_AbstractViewLoader::getView($defaults->id, $defaults))) {
			$tpl->assign('view', $view);
		}
		
		$tpl->display('devblocks:cerberusweb.core::internal/views/search_and_view.tpl');
	}
}