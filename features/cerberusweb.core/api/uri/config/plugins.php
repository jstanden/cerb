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

class PageSection_SetupPlugins extends Extension_PageSection {
	const VIEW_CURRENT = 'plugins_installed';
	const VIEW_LEGACY = 'plugins_legacy';
	const VIEW_PLUGINS = 'plugins_third_party';

	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		// When someone loads the plugin page, check for new or updated user-installed plugins on disk
		if(DEVELOPMENT_MODE) {
			DevblocksPlatform::readPlugins();
			DAO_Platform::cleanupPluginTables();

		} else {
			DevblocksPlatform::readPlugins(false, ['plugins','storage/plugins']);
		}

		// Built-in features (modern + still-supported bundled with Cerb)
		$view_current = $this->_getPluginsView(self::VIEW_CURRENT, 'Features', 'features/');
		$tpl->assign('view_current', $view_current);

		// Built-in legacy features
		$view_legacy = $this->_getPluginsView(self::VIEW_LEGACY, 'Legacy Features', 'plugins/');
		$tpl->assign('view_legacy', $view_legacy);

		// Third-party plugins live in storage/plugins/ — only render the section when something's there
		foreach(DevblocksPlatform::getPluginRegistry() as $plugin) {
			if(DevblocksPlatform::strStartsWith($plugin->dir, 'storage/plugins/')) {
				$view_plugins = $this->_getPluginsView(self::VIEW_PLUGINS, 'Plugins', 'storage/plugins/');
				$tpl->assign('view_plugins', $view_plugins);
				break;
			}
		}

		$tpl->display('devblocks:cerberusweb.core::configuration/section/plugins/index.tpl');
	}

	/**
	 * Build a plugins worklist scoped to a manifest-directory prefix
	 * (e.g. `features/`, `plugins/`, `storage/plugins/`).
	 *
	 * @param string $view_id
	 * @param string $name
	 * @param string $dir_prefix
	 * @return View_CerbPlugin
	 */
	private function _getPluginsView(string $view_id, string $name, string $dir_prefix) {
		$defaults = C4_AbstractViewModel::loadFromClass('View_CerbPlugin');
		$defaults->id = $view_id;
		$defaults->renderLimit = 30;

		$view = C4_AbstractViewLoader::getView($view_id, $defaults);
		$view->name = $name;

		// Exclude the always-on core plugins, and scope to the requested manifest directory
		$view->addParamsRequired([
			SearchFields_CerbPlugin::ID => new DevblocksSearchCriteria(SearchFields_CerbPlugin::ID, DevblocksSearchCriteria::OPER_NIN, ['devblocks.core','cerberusweb.core']),
			SearchFields_CerbPlugin::DIR => new DevblocksSearchCriteria(SearchFields_CerbPlugin::DIR, DevblocksSearchCriteria::OPER_LIKE, $dir_prefix . '%'),
		], true);

		return $view;
	}

	function handleActionForPage(string $action, ?string $scope=null) {
		if('configAction' == $scope) {
			switch ($action) {
				case 'toggleEnabled':
					return $this->_configAction_toggleEnabled();
			}
		}
		return false;
	}

	private function _configAction_toggleEnabled() {
		$active_worker = CerberusApplication::getActiveWorker();

		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);

		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);

		$plugin_id = DevblocksPlatform::importGPC($_POST['plugin_id'] ?? null, 'string','');
		$enabled = DevblocksPlatform::importGPC($_POST['enabled'] ?? null, 'integer',0);

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		$errors = [];
		$plugin = null;

		try {
			switch($plugin_id) {
				case 'devblocks.core':
				case 'cerberusweb.core':
					throw new Exception("This plugin can't be modified.");
			}

			if(!($plugin = DevblocksPlatform::getPlugin($plugin_id)))
				throw new Exception("Plugin not found.");

			// Check requirements before enabling
			if($enabled) {
				$reqs = $plugin->getRequirementsErrors();
				if(!empty($reqs)) {
					$errors = array_merge($errors, $reqs);
					throw new Exception("Requirements failed.");
				}
			}

			$plugin->setEnabled((true == $enabled));

			if($enabled) {
				// Run all the outdated plugin patches to the current app version
				$patches = $plugin->getPatches();
				foreach($patches as $plugin_patch) {
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
				$strings_xml = $plugin->getStoragePath() . '/strings.xml';
				if(file_exists($strings_xml)) {
					DAO_Translation::importTmxFile($strings_xml);
				}
			}

			DevblocksPlatform::clearCache();
			DevblocksPlatform::clearCache(sprintf('devblocks:plugin:%s:params', $plugin->id));

			echo json_encode([
				'status' => true,
				'enabled' => $enabled ? 1 : 0,
			]);

		} catch(Exception) {
			echo json_encode([
				'status' => false,
				'enabled' => ($plugin instanceof DevblocksPluginManifest) ? intval($plugin->enabled) : 0,
				'errors' => $errors,
			]);
		}
	}
}