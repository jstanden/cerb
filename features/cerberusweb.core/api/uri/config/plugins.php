<?php
class PageSection_SetupPlugins extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(ChConfigurationPage::ID, 'plugins');
		
		// Auto synchronize when viewing Config->Extensions
        DevblocksPlatform::readPlugins();

        if(DEVELOPMENT_MODE)
        	DAO_Platform::cleanupPluginTables();
		
		$plugins = DevblocksPlatform::getPluginRegistry();
		unset($plugins['devblocks.core']);
		unset($plugins['cerberusweb.core']);
		$tpl->assign('plugins', $plugins);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/plugins/index.tpl');
	}
	
	function saveAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$worker = CerberusApplication::getActiveWorker();
		
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}
		
		$pluginStack = DevblocksPlatform::getPluginRegistry();
		@$plugins_enabled = DevblocksPlatform::importGPC($_REQUEST['plugins_enabled']);
		
		if(is_array($pluginStack))
		foreach($pluginStack as $plugin) { /* @var $plugin DevblocksPluginManifest */
			switch($plugin->id) {
				case 'devblocks.core':
				case 'cerberusweb.core':
					$plugin->setEnabled(true);
					break;
					
				default:
					if(null !== $plugins_enabled && false !== array_search($plugin->id, $plugins_enabled)) {
						$plugin->setEnabled(true);
					} else {
						$plugin->setEnabled(false);
					}
					break;
			}
		}
		
		try {
			CerberusApplication::update();
		} catch (Exception $e) {
			// [TODO] ...
		}

		DevblocksPlatform::clearCache();
		
        // Reload plugin translations
		DAO_Translation::reloadPluginStrings();
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','plugins')));		
	}
};