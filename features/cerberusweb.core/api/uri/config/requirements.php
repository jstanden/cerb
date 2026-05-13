<?php /** @noinspection PhpUnused */
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

class PageSection_SetupDevelopersRequirements extends Extension_PageSection {
	function render() {
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		$tpl = DevblocksPlatform::services()->template();
		$response = DevblocksPlatform::getHttpResponse();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$stack = $response->path;
		@array_shift($stack); // config
		@array_shift($stack); // requirements
		
		$visit->set(ChConfigurationPage::ID, 'requirements');
		
		$errors = CerberusApplication::checkRequirements();
		$tpl->assign('errors', $errors);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/developers/requirements/index.tpl');
	}
	
	function handleActionForPage(string $action, ?string $scope=null) {
		return false;
	}
}