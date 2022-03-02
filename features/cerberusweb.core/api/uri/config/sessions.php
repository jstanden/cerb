<?php /** @noinspection PhpUnused */
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

class PageSection_SetupSessions extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$visit->set(ChConfigurationPage::ID, 'sessions');

		// View
		
		$defaults = C4_AbstractViewModel::loadFromClass('View_DevblocksSession');
		$defaults->id = 'devblocks_sessions';
		
		$view = C4_AbstractViewLoader::getView($defaults->id, $defaults);
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/sessions/index.tpl');
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('configAction' == $scope) {
			switch ($action) {
				case 'viewDelete':
					return $this->_configAction_viewDelete();
			}
		}
		return false;
	}
	
	private function _configAction_viewDelete() {
		$session = DevblocksPlatform::services()->session();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$row_ids = DevblocksPlatform::importGPC($_POST['row_id'] ?? null, ' array', []);
		
		if(is_array($row_ids))
		foreach($row_ids as $row_id)
			$session->clear($row_id);
	}
}