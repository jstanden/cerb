<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2013, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/

class PageSection_SetupPluginLibrary extends Extension_PageSection {
	const VIEW_PLUGIN_LIBRARY = 'plugin_library';
	
	function render() {
		$visit = CerberusApplication::getVisit();
		$visit->set(ChConfigurationPage::ID, 'plugin_library');
	}
	
	function showTabAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		//$visit->set(ChConfigurationPage::ID, 'plugin_library');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_PluginLibrary';
		$defaults->id = self::VIEW_PLUGIN_LIBRARY;
		$defaults->renderSortBy = SearchFields_PluginLibrary::UPDATED;
		$defaults->renderSortAsc = 0;
		
		$view = C4_AbstractViewLoader::getView(self::VIEW_PLUGIN_LIBRARY, $defaults);
		$view->name = "Compatible Plugins";
		
		C4_AbstractViewLoader::setView($view->id, $view);

		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/plugin_library/tab.tpl');
	}
	
	function syncAction() {
		header('Content-Type: application/json');

		if(!extension_loaded("curl") || false == ($results = DAO_PluginLibrary::downloadUpdatedPluginsFromRepository())) {
			echo json_encode(array(
				'status' => false,
				'message' => 'Failed to connect to plugin server.'
			));
			exit;
		}
		
		// If we updated plugins, update and clear the cache
		if(isset($results['updated']) && !empty($results['upated'])) {
			try {
				CerberusApplication::update();
				
			} catch (Exception $e) {}
	
			DevblocksPlatform::clearCache();
		}
		
		// Return JSON
		echo json_encode(array(
			'status' => true,
			'count' => $results['count'],
			'updated' => $results['updated'],
		));
		
		exit;
	}
	
	function showDownloadPopupAction() {
		@$plugin_id = DevblocksPlatform::importGPC($_REQUEST['plugin_id'],'string','');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();

		if(empty($plugin_id))
			return;
		
		$plugin = DAO_PluginLibrary::get($plugin_id);
		$tpl->assign('plugin', $plugin);

		// Check requirements
		$requirements = Model_PluginLibrary::testRequirements($plugin->requirements);
		$tpl->assign('requirements', $requirements);
		
		$tpl->assign('view_id', $view_id);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/plugin_library/download_popup.tpl');
	}
	
	function saveDownloadPopupAction() {
		@$plugin_id = DevblocksPlatform::importGPC($_REQUEST['plugin_id'],'integer',0);

		try {
			if(empty($plugin_id))
				return;
			
			$plugin = DAO_PluginLibrary::get($plugin_id);
			$requirements = Model_PluginLibrary::testRequirements($plugin->requirements);
			
			// [TODO] This should come from somewhere in Setup
			$url = sprintf("http://plugins.cerb6.com/plugins/download?plugin=%s&version=%d",
				urlencode($plugin->plugin_id),
				$plugin->latest_version
			);
			
			if(!extension_loaded("curl"))
				throw new Exception("The cURL PHP extension is not installed");
			
			// Connect to portal for download URL
			$ch = curl_init($url);
			curl_setopt_array($ch, array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_SSL_VERIFYPEER => false,
			));
			$json_data = curl_exec($ch);
			
			// [TODO] Check success
			if(false === ($response = json_decode($json_data, true)))
				throw new Exception("Invalid response.");
			
			$package_url = $response['package_url'];
			
			if(!empty($package_url))
				$success = DevblocksPlatform::installPluginZipFromUrl($package_url);

			if($success) {
				DevblocksPlatform::readPlugins(false);
				DevblocksPlatform::clearCache();
			}
			
			// Reload plugin translations
			if(null != ($plugin_manifest = DevblocksPlatform::getPlugin($plugin->id))) {
				$strings_xml = APP_PATH . '/' . $plugin_manifest->dir . '/strings.xml';
				if(file_exists($strings_xml)) {
					DAO_Translation::importTmxFile($strings_xml);
				}
			}
			
			echo json_encode(array(
				'status' => $success,
			));
			
		} catch(Exception $e) {
			echo json_encode(array(
				'status' => false,
				'message' => $e->getMessage()
			));
		}
	}
};