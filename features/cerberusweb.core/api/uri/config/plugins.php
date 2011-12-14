<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2011, WebGroup Media LLC
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
		// Auto synchronize when viewing Config->Extensions
        DevblocksPlatform::readPlugins();

        if(DEVELOPMENT_MODE)
        	DAO_Platform::cleanupPluginTables();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(ChConfigurationPage::ID, 'plugins');
		
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

// 		$quick_search_type = $visit->get('wgm_cerb5licenses.quick_search_type');
// 		$tpl->assign('quick_search_type', $quick_search_type);
		
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/plugins/tab.tpl');
		
		exit;        
		
// 		$tpl->display('devblocks:cerberusweb.core::configuration/section/plugins/tab.tpl');
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
		//$requirements = Model_PluginLibrary::testRequirements($plugin->requirements);
		//$tpl->assign('requirements', $requirements);
		
		$tpl->assign('view_id', $view_id);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/plugins/popup.tpl');
	}
	
	function savePopupAction() {
		@$plugin_id = DevblocksPlatform::importGPC($_REQUEST['plugin_id'],'string','');
		@$enabled = DevblocksPlatform::importGPC($_REQUEST['enabled'],'integer',0);
		@$uninstall = DevblocksPlatform::importGPC($_REQUEST['uninstall'],'integer',0);

		@$worker = CerberusApplication::getActiveWorker();
		
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}
		
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
				$plugin->setEnabled((true == $enabled));
	
				switch($enabled) {
					case 0: // disable
						break;
						
					case 1: // enable
						try {
							CerberusApplication::update();
						} catch (Exception $e) {
						}
						
				        // Reload plugin translations
						DAO_Translation::reloadPluginStrings();
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
			));
		}
	}
};