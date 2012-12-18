<?php
/***********************************************************************
| Cerb(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2012, WebGroup Media LLC
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

class PageSection_SetupPlugins extends Extension_PageSection {
	const VIEW_PLUGINS = 'plugins_installed';
	
	function render() {
		$response = DevblocksPlatform::getHttpResponse();
		$path = $response->path;

		// When someone loads the plugin page, check for new or updated
		//	 user-installed plugins on disk
		if(DEVELOPMENT_MODE) {
			DevblocksPlatform::readPlugins();
			DAO_Platform::cleanupPluginTables();
			
		} else {
			DevblocksPlatform::readPlugins(false, array('storage/plugins'));
		}
		
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(ChConfigurationPage::ID, 'plugins');
		
		if(isset($path[2])) {
			$tpl->assign('selected_tab', $path[2]);
		}
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/plugins/index.tpl');
	}

	function showTabAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'View_CerbPlugin';
		$defaults->id = self::VIEW_PLUGINS;
		$defaults->renderLimit = 10;
		$defaults->renderSortBy = SearchFields_CerbPlugin::NAME;
		$defaults->renderSortAsc = true;
		
		$view = C4_AbstractViewLoader::getView(self::VIEW_PLUGINS, $defaults);
		$view->name = "Installed Plugins";
		
		// Exclude devblocks.core, cerberusweb.core
		$view->addParamsRequired(array(
			SearchFields_CerbPlugin::ID => new DevblocksSearchCriteria(SearchFields_CerbPlugin::ID, DevblocksSearchCriteria::OPER_NIN, array('devblocks.core','cerberusweb.core')),
		));
		
		C4_AbstractViewLoader::setView($view->id, $view);

		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/plugins/tab.tpl');
	}
	
	function showPopupAction() {
		@$plugin_id = DevblocksPlatform::importGPC($_REQUEST['plugin_id'],'string','');
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string','');
		
		@$worker = CerberusApplication::getActiveWorker();
		
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}
		
		$tpl = DevblocksPlatform::getTemplateService();

		if(empty($plugin_id))
			return;
		
		$plugin = DevblocksPlatform::getPlugin($plugin_id);
		$tpl->assign('plugin', $plugin);

		$is_uninstallable = (preg_match("#^storage/plugins/#", $plugin->dir) > 0);
		$tpl->assign('is_uninstallable', $is_uninstallable);
		
		// Check requirements
		$requirements = $plugin->getRequirementsErrors();
		$tpl->assign('requirements', $requirements);
		
		$tpl->assign('view_id', $view_id);

		// Setup extensions
		$config_exts = Extension_PluginSetup::getByPlugin($plugin->id, true);
		$tpl->assign('config_exts', $config_exts);

		$tpl->display('devblocks:cerberusweb.core::configuration/section/plugins/popup.tpl');
	}
	
	function savePopupAction() {
		@$plugin_id = DevblocksPlatform::importGPC($_REQUEST['plugin_id'],'string','');
		@$enabled = DevblocksPlatform::importGPC($_REQUEST['enabled'],'integer',0);
		@$uninstall = DevblocksPlatform::importGPC($_REQUEST['uninstall'],'integer',0);

		@$worker = CerberusApplication::getActiveWorker();
		
		header("Content-Type: application/json");
		
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}
		
		$errors = array();
		
		switch($plugin_id) {
			case 'devblocks.core':
			case 'cerberusweb.core':
				throw new Exception("This plugin is not editable.");
				break;
		}
		
		try {
			$plugin = DevblocksPlatform::getPlugin($plugin_id);
			
			if($uninstall) {
				$plugin->uninstall();
				DAO_Platform::cleanupPluginTables();
				DAO_Platform::maint();
				
			} else {
				// Save and test params params
				$pass = true;
				
				if($enabled) {
					// Check requirements pre-enable
					$reqs = $plugin->getRequirementsErrors();
					if(!empty($reqs)) {
						$errors = array_merge($errors, $reqs);
						throw new Exception("Requirements failed.");
					}
					
					// Save configuration
					$config_exts = Extension_PluginSetup::getByPlugin($plugin->id, true);
					foreach($config_exts as $config_ext) {
						$pass = $config_ext->save($errors);
					}
				}
				
				if(!$pass)
					throw new Exception("Failed to save parameters");
				
				$plugin->setEnabled((true == $enabled));
	
				switch($enabled) {
					case 0: // disable
						break;
						
					case 1: // enable

						// Run all the outdated plugin patches to the current app version
						// [TODO] This really should come from Devblocks
						$patches = $plugin->getPatches();
						foreach($patches as $k => $plugin_patch) {
							// Recursive patch up to _version_
							if(version_compare($plugin_patch->getVersion(), APP_VERSION, "<=")) {
								if(!$plugin_patch->run()) {
									$plugin->setEnabled(false);
									$errors[] = "Failed to run the plugin's database patch.";
									throw new Exception("Failed to patch plugin");
								}
							}
						}
							
						// Reload plugin translations
						$strings_xml = APP_PATH . '/' . $plugin->dir . '/strings.xml';
						if(file_exists($strings_xml)) {
							DAO_Translation::importTmxFile($strings_xml);
						}
						
						break;
				}
			}
			
			DevblocksPlatform::clearCache();
			
			echo json_encode(array(
				'status' => true,
			));
			
		} catch(Exception $e) {
			echo json_encode(array(
				'status' => false,
				'errors' => $errors,
			));
		}
	}
};