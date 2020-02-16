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

class PageSection_SetupCache extends Extension_PageSection {
	function render() {
		if(DEVBLOCKS_CACHE_ENGINE_PREVENT_CHANGE)
			return;
		
		$tpl = DevblocksPlatform::services()->template();
		$cache = DevblocksPlatform::services()->cache();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(ChConfigurationPage::ID, 'cache');
		
		// Load the currently configured cacher
		$cacher = $cache->getEngine();
		$tpl->assign('cacher', $cacher);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/cache/index.tpl');
	}
	
	function showCachePeekAction() {
		if(DEVBLOCKS_CACHE_ENGINE_PREVENT_CHANGE)
			return;
		
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		$tpl = DevblocksPlatform::services()->template();
		$cache = DevblocksPlatform::services()->cache();
		
		// All engines
		$engines = Extension_DevblocksCacheEngine::getAll(true);
		$tpl->assign('engines', $engines);
		
		// Load the currently configured cacher
		$cacher = $cache->getEngine();
		$tpl->assign('current_cacher', $cacher);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/cache/peek.tpl');
	}
	
	function saveCachePeekAction() {
		if(DEVBLOCKS_CACHE_ENGINE_PREVENT_CHANGE)
			return;
		
		header('Content-Type: application/json');
		
		try {
			if('POST' != DevblocksPlatform::getHttpMethod())
				throw new Exception_DevblocksValidationError(DevblocksPlatform::translate('common.access_denied'));
			
			@$engine_extension_id = DevblocksPlatform::importGPC($_POST['engine_extension_id'],'string','');
			@$params = DevblocksPlatform::importGPC($_POST['params'],'array',array());
	
			if(false == ($engine = Extension_DevblocksCacheEngine::get($engine_extension_id)))
				throw new Exception_DevblocksAjaxError("Failed to load the cache engine.");
			
			$config = @$params[$engine_extension_id] ?: array();
			
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
};