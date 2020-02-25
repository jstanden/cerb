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

class PageSection_SetupSearch extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(DEVBLOCKS_SEARCH_ENGINE_PREVENT_CHANGE)
			return;
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$visit->set(ChConfigurationPage::ID, 'search');

		$schemas = Extension_DevblocksSearchSchema::getAll(true);
		$tpl->assign('schemas', $schemas);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/search/index.tpl');
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('configAction' == $scope) {
			switch ($action) {
				case 'showSearchSchemaPeek':
					return $this->_configAction_showSearchSchemaPeek();
				case 'saveSearchSchemaPeek':
					return $this->_configAction_saveSearchSchemaPeek();
			}
		}
		return false;
	}
	
	private function _configAction_showSearchSchemaPeek() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(DEVBLOCKS_SEARCH_ENGINE_PREVENT_CHANGE)
			return;
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		$schema = Extension_DevblocksSearchSchema::get($ext_id);
		$tpl->assign('schema', $schema);
		$tpl->assign('schema_engine', $schema->getEngine());
		
		$engines = Extension_DevblocksSearchEngine::getAll(true);
		$tpl->assign('search_engines', $engines);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/search/peek.tpl');
	}
	
	private function _configAction_saveSearchSchemaPeek() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(DEVBLOCKS_SEARCH_ENGINE_PREVENT_CHANGE)
			return;
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$schema_extension_id = DevblocksPlatform::importGPC($_POST['schema_extension_id'],'string','');
		@$engine_extension_id = DevblocksPlatform::importGPC($_POST['engine_extension_id'],'string','');
		@$params = DevblocksPlatform::importGPC($_POST['params'],'array',array());

		header('Content-Type: application/json');
		
		try {
			if(false == ($schema = Extension_DevblocksSearchSchema::get($schema_extension_id)))
				throw new Exception_DevblocksAjaxError("Failed to load the search schema.");
			
			if(false == ($engine = Extension_DevblocksSearchEngine::get($engine_extension_id)))
				throw new Exception_DevblocksAjaxError("Failed to load the search engine.");
			
			$engine_params = @$params[$engine_extension_id] ?: array();

			if(true !== ($test_output = $engine->testConfig($engine_params)))
				throw new Exception_DevblocksAjaxError($test_output);
			
			$engine_config = array(
				'engine_extension_id' => $engine_extension_id,
				'config' => $engine_params,
			);
			
			$schema->saveConfig($engine_config);
			
			echo json_encode(array(
				'success' => true,
			));
			
		} catch(Exception_DevblocksAjaxError $e) {
			echo json_encode(array(
				'success' => false,
				'error' => $e->getMessage(),
			));
			
		}
	}
}