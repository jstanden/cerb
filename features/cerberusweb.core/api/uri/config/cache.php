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

class PageSection_SetupCache extends Extension_PageSection {
	function render() {
		if(DEVBLOCKS_CACHE_ENGINE_PREVENT_CHANGE)
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		$cache = DevblocksPlatform::services()->cache();
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$visit->set(ChConfigurationPage::ID, 'cache');
		
		// Load the currently configured cacher
		$cacher = $cache->getEngine();
		$tpl->assign('cacher', $cacher);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/cache/index.tpl');
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('configAction' == $scope) {
			switch ($action) {
				case 'showCachePeek':
					return $this->_configAction_showCachePeek();
				case 'saveCachePeek':
					return $this->_configAction_saveCachePeek();
			}
		}
		return false;
	}
	
	private function _configAction_showCachePeek() {
		if(DEVBLOCKS_CACHE_ENGINE_PREVENT_CHANGE)
			return;
		
		$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'] ?? null,'string','');
		
		$tpl = DevblocksPlatform::services()->template();
		$cache = DevblocksPlatform::services()->cache();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		// All engines
		$engines = Extension_DevblocksCacheEngine::getAll(true);
		$tpl->assign('engines', $engines);
		
		// Load the currently configured cacher
		$cacher = $cache->getEngine();
		$tpl->assign('current_cacher', $cacher);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/cache/peek.tpl');
	}
	
	private function _configAction_saveCachePeek() {
		if(DEVBLOCKS_CACHE_ENGINE_PREVENT_CHANGE)
			return;
		
		header('Content-Type: application/json');
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		try {
			if('POST' != DevblocksPlatform::getHttpMethod())
				throw new Exception_DevblocksAjaxError(DevblocksPlatform::translate('common.access_denied'));
			
			$engine_extension_id = DevblocksPlatform::importGPC($_POST['engine_extension_id'] ?? null,'string','');
			$params = DevblocksPlatform::importGPC($_POST['params'] ?? null,'array',array());
	
			if(false == ($engine = Extension_DevblocksCacheEngine::get($engine_extension_id)))
				throw new Exception_DevblocksAjaxError("Failed to load the cache engine.");
			
			$config = ($params[$engine_extension_id] ?? null) ?: [];
			
			if(true !== ($test_output = $engine->testConfig($config)))
				throw new Exception_DevblocksAjaxError($test_output);
			
			DevblocksPlatform::setPluginSetting('devblocks.core', 'cacher.extension_id', $engine_extension_id);
			DevblocksPlatform::setPluginSetting('devblocks.core', 'cacher.params_json', $config, true);
			
			// Reset the cache on disk and in the target
			$cache = DevblocksPlatform::services()->cache();
			$cache->setEngine($engine_extension_id, $config);
			$cache->clean();
			
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