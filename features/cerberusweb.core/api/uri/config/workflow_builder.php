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

class PageSection_SetupDevelopersWorkflowBuilder extends Extension_PageSection {
	function render() {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		$response = DevblocksPlatform::getHttpResponse();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$stack = $response->path;
		@array_shift($stack); // config
		@array_shift($stack); // workflow_builder
		
		$visit->set(ChConfigurationPage::ID, 'workflow_builder');
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/developers/workflow-builder/index.tpl');
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('configAction' == $scope) {
			if ($action == 'exportWorkflowKata') {
				return $this->_configAction_exportWorkflowKata();
			}
		}
		return false;
	}
	
	function _configAction_exportWorkflowKata() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		try {
			$workflow_builder_kata = DevblocksPlatform::importGPC($_POST['workflow_builder_kata'] ?? null, 'string', '');

			$export_model = new DevblocksWorkflowExportModel($workflow_builder_kata);
			
			echo json_encode([
				'status' => true,
				'workflow_kata' => $export_model->createWorkflowKata(true),
			]);
			
		} catch(Exception_DevblocksValidationError $e) {
			echo json_encode([
				'status' => false,
				'error' => $e->getMessage(),
			]);

		} catch(Throwable) {
			echo json_encode([
				'status' => false,
				'error' => 'An unexpected error occurred.',
			]);
		}
	}
}