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

class PageSection_SetupDevelopersPlatform extends Extension_PageSection {
	function render() {
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		$tpl = DevblocksPlatform::services()->template();
		$response = DevblocksPlatform::getHttpResponse();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$stack = $response->path;
		@array_shift($stack); // config
		@array_shift($stack); // platform
		
		$visit->set(ChConfigurationPage::ID, 'platform');
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/developers/platform/index.tpl');
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('configAction' == $scope) {
			switch ($action) {
				case 'clearCache':
					return $this->_configAction_clearCache();
				case 'reloadAutomations':
					return $this->_configAction_reloadAutomations();
				case 'reloadPackages':
					return $this->_configAction_reloadPackages();
				case 'reloadResources':
					return $this->_configAction_reloadResources();
			}
		}
		return false;
	}
	
	private function _configAction_clearCache() {
		// Flush the volatile cache
		DevblocksPlatform::services()->cache()->clean();
		
		// Flush the template cache
		$tpl = DevblocksPlatform::services()->template();
		$tpl->clearCompiledTemplate();
		$tpl->clearAllCache();
	}
	
	private function _configAction_reloadAutomations() {
		$dir = new DirectoryIterator(realpath(APP_PATH . '/features/cerberusweb.core/assets/automations/'));
		$iter = new IteratorIterator($dir);
		$regex = new RegexIterator($iter, '/^.+\.json/i', RegexIterator::MATCH);
		
		foreach($regex as $o) {
			if(is_null($o) || false === ($automation_data = json_decode(file_get_contents($o->getPathname()), true)))
				continue;
			
			DAO_Automation::importFromJson($automation_data);
			
			unset($automation_data);
		}
	}
	
	private function _configAction_reloadPackages() {
		$dir = new RecursiveDirectoryIterator(realpath(APP_PATH . '/features/cerberusweb.core/packages/library/'));
		$iter = new RecursiveIteratorIterator($dir);
		$regex = new RegexIterator($iter, '/^.+\.json/i', RegexIterator::GET_MATCH);
		
		foreach($regex as $class_file => $o) {
			if(is_null($o))
				continue;
			
			if(!($package_json = file_get_contents($class_file)))
				continue;
			
			CerberusApplication::packages()->importToLibraryFromString($package_json);
		}
	}
	
	private function _configAction_reloadResources() {
		$dir = new DirectoryIterator(realpath(APP_PATH . '/features/cerberusweb.core/assets/resources/'));
		$iter = new IteratorIterator($dir);
		$regex = new RegexIterator($iter, '/^.+\.json/i', RegexIterator::MATCH);
		
		foreach($regex as $o) {
			if(is_null($o) || false === ($resource_data = json_decode(file_get_contents($o->getPathname()), true)))
				continue;
			
			DAO_Resource::importFromJson($resource_data);
			
			unset($resource_data);
		}		
	}
}