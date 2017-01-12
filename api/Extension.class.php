<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2017, Webgroup Media LLC
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
/*
 * IMPORTANT LICENSING NOTE from your friends at Cerb
 *
 * Sure, it would be really easy to just cheat and edit this file to use
 * Cerb without paying for a license.  We trust you anyway.
 *
 * It takes a significant amount of time and money to develop, maintain,
 * and support high-quality enterprise software with a dedicated team.
 * For Cerb's entire history we've avoided taking money from outside
 * investors, and instead we've relied on actual sales from satisfied
 * customers to keep the project running.
 *
 * We've never believed in hiding our source code out of paranoia over not
 * getting paid.  We want you to have the full source code and be able to
 * make the tweaks your organization requires to get more done -- despite
 * having less of everything than you might need (time, people, money,
 * energy).  We shouldn't be your bottleneck.
 *
 * As a legitimate license owner, your feedback will help steer the project.
 * We'll also prioritize your issues, and work closely with you to make sure
 * your teams' needs are being met.
 *
 * - Jeff Standen and Dan Hildebrandt
 *	 Founders at Webgroup Media LLC; Developers of Cerb
 */

abstract class Extension_AppPreBodyRenderer extends DevblocksExtension {
	function render() { }
};

abstract class Extension_AppPostBodyRenderer extends DevblocksExtension {
	function render() { }
};

abstract class CerberusPageExtension extends DevblocksExtension {
	function isVisible() { return true; }
	function render() { }
};

abstract class Extension_PluginSetup extends DevblocksExtension {
	const POINT = 'cerberusweb.plugin.setup';

	static function getByPlugin($plugin_id, $as_instances=true) {
		$results = array();

		// Include disabled extensions
		$all_extensions = DevblocksPlatform::getExtensionRegistry(true, true);
		foreach($all_extensions as $k => $ext) { /* @var $ext DevblocksExtensionManifest */
			if($ext->plugin_id == $plugin_id && $ext->point == Extension_PluginSetup::POINT)
				$results[$k] = ($as_instances) ? $ext->createInstance() : $ext;
		}
		
		return $results;
	}
	
	abstract function render();
	abstract function save(&$errors);
}

abstract class Extension_PageSection extends DevblocksExtension {
	const POINT = 'cerberusweb.ui.page.section';
	
	/**
	 * @return DevblocksExtensionManifest[]|Extension_PageSection[]
	 */
	static function getExtensions($as_instances=true, $page_id=null) {
		if(empty($page_id))
			return DevblocksPlatform::getExtensions(self::POINT, $as_instances);

		$results = array();
		
		$exts = DevblocksPlatform::getExtensions(self::POINT, false);
		foreach($exts as $ext_id => $ext) {
			if(0 == strcasecmp($page_id, $ext->params['page_id']))
				$results[$ext_id] = $as_instances ? $ext->createInstance() : $ext;
		}
		
		return $results;
	}
	
	/**
	 *
	 * @param string $uri
	 * @return DevblocksExtensionManifest|Extension_PageSection
	 */
	static function getExtensionByPageUri($page_id, $uri, $as_instance=true) {
		$manifests = self::getExtensions(false, $page_id);
		
		foreach($manifests as $mft) { /* @var $mft DevblocksExtensionManifest */
			if(0==strcasecmp($uri, $mft->params['uri']))
				return $as_instance ? $mft->createInstance() : $mft;
		}
		
		return null;
	}
	
	abstract function render();
};

abstract class Extension_PageMenu extends DevblocksExtension {
	const POINT = 'cerberusweb.ui.page.menu';
	
	/**
	 * @return DevblocksExtensionManifest[]|Extension_PageMenu[]
	 */
	static function getExtensions($as_instances=true, $page_id=null) {
		if(empty($page_id))
			return DevblocksPlatform::getExtensions(self::POINT, $as_instances);

		$results = array();
		
		$exts = DevblocksPlatform::getExtensions(self::POINT, false);
		foreach($exts as $ext_id => $ext) {
			if(0 == strcasecmp($page_id, $ext->params['page_id']))
				$results[$ext_id] = $as_instances ? $ext->createInstance() : $ext;
		}
		
		// Sorting
		if($as_instances)
			DevblocksPlatform::sortObjects($results, 'manifest->name');
		else
			DevblocksPlatform::sortObjects($results, 'name');
		
		return $results;
	}
	
	abstract function render();
};

abstract class Extension_PageMenuItem extends DevblocksExtension {
	const POINT = 'cerberusweb.ui.page.menu.item';
	
	/**
	 * @return DevblocksExtensionManifest[]|Extension_PageMenuItem[]
	 */
	static function getExtensions($as_instances=true, $page_id=null, $menu_id=null) {
		if(empty($page_id) && empty($menu_id))
			return DevblocksPlatform::getExtensions(self::POINT, $as_instances);

		$results = array();
		
		$exts = DevblocksPlatform::getExtensions(self::POINT, false);
		foreach($exts as $ext_id => $ext) {
			if(empty($page_id) || 0 == strcasecmp($page_id, $ext->params['page_id']))
				if(empty($menu_id) || 0 == strcasecmp($menu_id, $ext->params['menu_id']))
					$results[$ext_id] = $as_instances ? $ext->createInstance() : $ext;
		}
		
		// Sorting
		if($as_instances)
			DevblocksPlatform::sortObjects($results, 'manifest->name');
		else
			DevblocksPlatform::sortObjects($results, 'name');
		
		return $results;
	}
	
	abstract function render();
};

abstract class Extension_PreferenceTab extends DevblocksExtension {
	const POINT = 'cerberusweb.preferences.tab';
	
	function showTab() {}
	function saveTab() {}
};

abstract class Extension_SendMailToolbarItem extends DevblocksExtension {
	function render() { }
};

abstract class Extension_MessageToolbarItem extends DevblocksExtension {
	function render(Model_Message $message) { }
};

abstract class Extension_ReplyToolbarItem extends DevblocksExtension {
	function render(Model_Message $message) { }
};

abstract class Extension_ExplorerToolbar extends DevblocksExtension {
	function render(Model_ExplorerSet $item) { }
};

abstract class Extension_MailTransport extends DevblocksExtension {
	const POINT = 'cerberusweb.mail.transport';
	
	static $_registry = array();
	
	/**
	 * @return DevblocksExtensionManifest[]|Extension_MailTransport[]
	 */
	static function getAll($as_instances=true) {
		$exts = DevblocksPlatform::getExtensions(self::POINT, $as_instances);

		// Sorting
		if($as_instances)
			DevblocksPlatform::sortObjects($exts, 'manifest->name');
		else
			DevblocksPlatform::sortObjects($exts, 'name');
	
		return $exts;
	}
	
	static function get($extension_id) {
		if(isset(self::$_registry[$extension_id]))
			return self::$_registry[$extension_id];
		
		if(null != ($extension = DevblocksPlatform::getExtension($extension_id, true))
			&& $extension instanceof Extension_MailTransport) {

			self::$_registry[$extension->id] = $extension;
			return $extension;
		}
		
		return null;
	}
	
	abstract function renderConfig(Model_MailTransport $model);
	abstract function testConfig(array $params, &$error=null);
	abstract function send(Swift_Message $message, Model_MailTransport $model);
	abstract function getLastError();
};

abstract class Extension_ContextProfileTab extends DevblocksExtension {
	const POINT = 'cerberusweb.ui.context.profile.tab';
	
	/**
	 * @return DevblocksExtensionManifest[]|Extension_ContextProfileTab[]
	 */
	static function getExtensions($as_instances=true, $context=null) {
		if(empty($context))
			return DevblocksPlatform::getExtensions(self::POINT, $as_instances);
	
		$results = array();
	
		$exts = DevblocksPlatform::getExtensions(self::POINT, false);
		
		foreach($exts as $ext_id => $ext) {
			if(isset($ext->params['contexts'][0]))
			foreach(array_keys($ext->params['contexts'][0]) as $ctx_pattern) {
				$ctx_pattern = DevblocksPlatform::strToRegExp($ctx_pattern);
				
				if(preg_match($ctx_pattern, $context))
					$results[$ext_id] = $as_instances ? $ext->createInstance() : $ext;
			}
		}
	
		// Sorting
		if($as_instances)
			DevblocksPlatform::sortObjects($results, 'manifest->name');
		else
			DevblocksPlatform::sortObjects($results, 'name');
	
		return $results;
	}
	
	function showTab($context, $context_id) {}
};

abstract class Extension_ContextProfileScript extends DevblocksExtension {
	const POINT = 'cerberusweb.ui.context.profile.script';
	
	/**
	 * @return DevblocksExtensionManifest[]|Extension_ContextProfileScript[]
	 */
	static function getExtensions($as_instances=true, $context=null) {
		if(empty($context))
			return DevblocksPlatform::getExtensions(self::POINT, $as_instances);
	
		$results = array();
	
		$exts = DevblocksPlatform::getExtensions(self::POINT, false);

		foreach($exts as $ext_id => $ext) {
			if(isset($ext->params['contexts'][0]))
			foreach(array_keys($ext->params['contexts'][0]) as $ctx_pattern) {
				$ctx_pattern = DevblocksPlatform::strToRegExp($ctx_pattern);
				
				if(preg_match($ctx_pattern, $context))
					$results[$ext_id] = $as_instances ? $ext->createInstance() : $ext;
			}
		}

		// Sorting
		if($as_instances)
			DevblocksPlatform::sortObjects($results, 'manifest->name');
		else
			DevblocksPlatform::sortObjects($results, 'name');
	
		return $results;
	}
	
	function renderScript($context, $context_id) {}
};

abstract class Extension_CalendarDatasource extends DevblocksExtension {
	const POINT = 'cerberusweb.calendar.datasource';
	
	static $_registry = array();
	
	/**
	 * @return DevblocksExtensionManifest[]|Extension_WorkspacePage[]
	 */
	static function getAll($as_instances=true) {
		$exts = DevblocksPlatform::getExtensions(self::POINT, $as_instances);

		// Sorting
		if($as_instances)
			DevblocksPlatform::sortObjects($exts, 'manifest->name');
		else
			DevblocksPlatform::sortObjects($exts, 'name');
	
		return $exts;
	}
	
	static function get($extension_id) {
		if(isset(self::$_registry[$extension_id]))
			return self::$_registry[$extension_id];
		
		if(null != ($extension = DevblocksPlatform::getExtension($extension_id, true))
			&& $extension instanceof Extension_CalendarDatasource) {

			self::$_registry[$extension->id] = $extension;
			return $extension;
		}
		
		return null;
	}
	
	abstract function renderConfig(Model_Calendar $calendar, $params, $series_prefix);
	abstract function getData(Model_Calendar $calendar, array $params=array(), $params_prefix=null, $date_range_from, $date_range_to);
};

abstract class Extension_WorkspacePage extends DevblocksExtension {
	const POINT = 'cerberusweb.ui.workspace.page';
	
	static $_registry = array();
	
	/**
	 * @return DevblocksExtensionManifest[]|Extension_WorkspacePage[]
	 */
	static function getAll($as_instances=true) {
		$exts = DevblocksPlatform::getExtensions(self::POINT, $as_instances);

		// Sorting
		if($as_instances)
			DevblocksPlatform::sortObjects($exts, 'manifest->name');
		else
			DevblocksPlatform::sortObjects($exts, 'name');
	
		return $exts;
	}
	
	static function get($extension_id) {
		if(isset(self::$_registry[$extension_id]))
			return self::$_registry[$extension_id];
		
		if(null != ($extension = DevblocksPlatform::getExtension($extension_id, true))
			&& $extension instanceof Extension_WorkspacePage) {

			self::$_registry[$extension->id] = $extension;
			return $extension;
		}
		
		return null;
	}
	
	function exportPageConfigJson(Model_WorkspacePage $page) {
		$json_array = array(
			'page' => array(
				'name' => $page->name,
				'extension_id' => $page->extension_id,
			),
		);
		
		return json_encode($json_array);
	}
	
	function importPageConfigJson($import_json, Model_WorkspacePage $page) {
		if(!is_array($import_json) || !isset($import_json['page']))
			return false;
		
		return true;
	}
	
	abstract function renderPage(Model_WorkspacePage $page);
};

abstract class Extension_WorkspaceTab extends DevblocksExtension {
	const POINT = 'cerberusweb.ui.workspace.tab';
	
	static $_registry = array();
	
	/**
	 * @return DevblocksExtensionManifest[]|Extension_WorkspaceTab[]
	 */
	static function getAll($as_instances=true) {
		$exts = DevblocksPlatform::getExtensions(self::POINT, $as_instances);

		// Sorting
		if($as_instances)
			DevblocksPlatform::sortObjects($exts, 'manifest->name');
		else
			DevblocksPlatform::sortObjects($exts, 'name');
		
		return $exts;
	}

	static function get($extension_id) {
		if(isset(self::$_registry[$extension_id]))
			return self::$_registry[$extension_id];
		
		if(null != ($extension = DevblocksPlatform::getExtension($extension_id, true))
			&& $extension instanceof Extension_WorkspaceTab) {

			self::$_registry[$extension->id] = $extension;
			return $extension;
		}
		
		return null;
	}
	
	abstract function renderTab(Model_WorkspacePage $page, Model_WorkspaceTab $tab);
	function exportTabConfigJson(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {}
	function importTabConfigJson($import_json, Model_WorkspaceTab $tab) {}
	function renderTabConfig(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {}
	function saveTabConfig(Model_WorkspacePage $page, Model_WorkspaceTab $tab) {}
};

abstract class Extension_WorkspaceWidgetDatasource extends DevblocksExtension {
	static $_registry = array();
	
	static function getAll($as_instances=false, $only_for_widget=null) {
		$extensions = DevblocksPlatform::getExtensions('cerberusweb.ui.workspace.widget.datasource', false);
		
		if(!empty($only_for_widget)) {
			$results = array();
			
			foreach($extensions as $id => $ext) {
				if(in_array($only_for_widget, array_keys($ext->params['widgets'][0])))
					$results[$id] = ($as_instances) ? $ext->createInstance() : $ext;
			}
			
			$extensions = $results;
			unset($results);
		}
		
		if($as_instances)
			DevblocksPlatform::sortObjects($extensions, 'manifest->name');
		else
			DevblocksPlatform::sortObjects($extensions, 'name');
		
		return $extensions;
	}

	static function get($extension_id) {
		if(isset(self::$_registry[$extension_id]))
			return self::$_registry[$extension_id];
		
		if(null != ($extension = DevblocksPlatform::getExtension($extension_id, true))
			&& $extension instanceof Extension_WorkspaceWidgetDatasource) {

			self::$_registry[$extension->id] = $extension;
			return $extension;
		}
		
		return null;
	}
	
	abstract function renderConfig(Model_WorkspaceWidget $widget, $params=array(), $params_prefix=null);
	abstract function getData(Model_WorkspaceWidget $widget, array $params=array(), $params_prefix=null);
};

interface ICerbWorkspaceWidget_ExportData {
	function exportData(Model_WorkspaceWidget $widget, $format=null);
};

abstract class Extension_WorkspaceWidget extends DevblocksExtension {
	static $_registry = array();
	
	static function getAll($as_instances=false) {
		$extensions = DevblocksPlatform::getExtensions('cerberusweb.ui.workspace.widget', $as_instances);
		
		if($as_instances)
			DevblocksPlatform::sortObjects($extensions, 'manifest->name');
		else
			DevblocksPlatform::sortObjects($extensions, 'name');
		
		return $extensions;
	}

	static function get($extension_id) {
		if(isset(self::$_registry[$extension_id]))
			return self::$_registry[$extension_id];
		
		if(null != ($extension = DevblocksPlatform::getExtension($extension_id, true))
				&& $extension instanceof Extension_WorkspaceWidget) {

			self::$_registry[$extension->id] = $extension;
			return $extension;
		}
		
		return null;
	}
	
	static function renderWidgetFromCache($widget, $autoload=true, $nocache=false) {
		// Polymorph
		if($widget instanceof Model_WorkspaceWidget) {
			// Do nothing, it's what we want.
			
		} elseif (is_numeric($widget)) {
			$widget = DAO_WorkspaceWidget::get($widget);
			
		} else {
			$widget = null;
		}
		
		$cache = DevblocksPlatform::getCacheService();
		$is_cached = false;
				
		if($widget && $widget instanceof Model_WorkspaceWidget) {
			$cache_key = sprintf("widget%d_render", $widget->id);
			
			// Fetch and cache
			if($nocache || empty($widget->cache_ttl) || null === ($widget_contents = $cache->load($cache_key))) {
				if($autoload) {
					$tpl = DevblocksPlatform::getTemplateService();
					$tpl->assign('widget', $widget);
					
					if(false !== ($widget_contents = $tpl->fetch('devblocks:cerberusweb.core::internal/workspaces/widgets/render.tpl')))
						$cache->save($widget_contents, $cache_key, null, $widget->cache_ttl);
				}
				
			} else {
				$is_cached = true;
			}
			
			if(isset($widget_contents))
				echo $widget_contents;
		}
		
		return $is_cached;
	}
	
	abstract function render(Model_WorkspaceWidget $widget);
	abstract function renderConfig(Model_WorkspaceWidget $widget);
	abstract function saveConfig(Model_WorkspaceWidget $widget);

	public static function getViewFromParams($widget, $params, $view_id) {
		if(!isset($params['worklist_model']))
			return false;
		
		$view_model = $params['worklist_model'];
		
		if(false != ($view = C4_AbstractViewLoader::unserializeViewFromAbstractJson($view_model, $view_id))) {
			// Check for quick search
			@$mode = $params['search_mode'];
			@$q = $params['quick_search'];
			
			if($mode == 'quick_search' && $q)
				$view->addParamsWithQuickSearch($q, true);
			
			$view->persist();
			return $view;
		}
		
		return false;
	}
};

abstract class Extension_LoginAuthenticator extends DevblocksExtension {

	static function getAll($as_instances=false) {
		$extensions = DevblocksPlatform::getExtensions('cerberusweb.login', $as_instances);
		
		// [TODO] Alphabetize
		
		return $extensions;
	}
	
	static function get($extension_id, $as_instance=false) {
		$extensions = self::getAll(false);
		
		if(!isset($extensions[$extension_id]))
			return NULL;
		
		$ext = $extensions[$extension_id];
		
		if($as_instance) {
			return $ext->createInstance();
			
		} else {
			return $ext;
			
		}
	}
	
	static function getByUri($uri, $as_instance=false) {
		$extensions = self::getAll(false);
		
		foreach($extensions as $manifest) { /* @var $manifest DevblocksExtensionManifest */
			if($manifest->params['uri'] == $uri) {
				return $as_instance ? $manifest->createInstance() : $manifest;
			}
		}

		return NULL;
	}
	
	/**
	 * draws HTML form of controls needed for login information
	 */
	function render() {
	}
	
	function renderWorkerPrefs($worker) {
	}
	
	function saveWorkerPrefs($worker) {
	}
	
	function resetCredentials($worker) {
	}
	
	/**
	 * pull auth info out of $_POST, check it, return user_id or false
	 *
	 * @return boolean whether login succeeded
	 */
	function authenticate() {
		return false;
	}
	
	/**
	 * release any resources tied up by the authenticate process, if necessary
	 */
	function signoff() {
	}
};

abstract class CerberusCronPageExtension extends DevblocksExtension {
	const PARAM_ENABLED = 'enabled';
	const PARAM_LOCKED = 'locked';
	const PARAM_DURATION = 'duration';
	const PARAM_TERM = 'term';
	const PARAM_LASTRUN = 'lastrun';
	
	/**
	 * runs scheduled task
	 *
	 */
	abstract function run();
	
	function _run() {
		$duration = $this->getParam(self::PARAM_DURATION, 5);
		$term = $this->getParam(self::PARAM_TERM, 'm');
		$lastrun = $this->getParam(self::PARAM_LASTRUN, time());
		
		// [TODO] By setting the locks directly on these extensions, we're invalidating them during the same /cron
		//	and causing redundant retrievals of the params from the DB
		$this->setParam(self::PARAM_LOCKED, time());
		
		$this->run();

		$secs = self::getIntervalAsSeconds($duration, $term);
		$ran_at = time();
		
		if(!empty($secs)) {
			$gap = time() - $lastrun; // how long since we last ran
			$extra = $gap % $secs; // we waited too long to run by this many secs
			$ran_at = time() - $extra; // go back in time and lie
		}
		
		$this->setParam(self::PARAM_LASTRUN, $ran_at);
		$this->setParam(self::PARAM_LOCKED, 0);
	}
	
	/**
	 * @param boolean $is_ignoring_wait Ignore the wait time when deciding to run
	 * @return boolean
	 */
	public function isReadyToRun($is_ignoring_wait=false) {
		$locked = $this->getParam(self::PARAM_LOCKED, 0);
		$enabled = $this->getParam(self::PARAM_ENABLED, false);
		$duration = $this->getParam(self::PARAM_DURATION, 5);
		$term = $this->getParam(self::PARAM_TERM, 'm');
		$lastrun = $this->getParam(self::PARAM_LASTRUN, 0);
		
		// If we've been locked too long then unlock
		if($locked && $locked < (time() - 10 * 60)) {
			$locked = 0;
		}

		// Make sure enough time has elapsed.
		$checkpoint = ($is_ignoring_wait)
			? (0) // if we're ignoring wait times, be ready now
			: ($lastrun + self::getIntervalAsSeconds($duration, $term)) // otherwise test
			;

		// Ready?
		return (!$locked && $enabled && time() >= $checkpoint) ? true : false;
	}
	
	static public function getIntervalAsSeconds($duration, $term) {
		$seconds = 0;
		
		if($term=='d') {
			$seconds = $duration * 24 * 60 * 60; // x hours * mins * secs
		} elseif($term=='h') {
			$seconds = $duration * 60 * 60; // x * mins * secs
		} else {
			$seconds = $duration * 60; // x * secs
		}
		
		return $seconds;
	}
	
	public function configure($instance) {}
	
	public function saveConfigurationAction() {}
};

abstract class Extension_UsermeetTool extends DevblocksExtension implements DevblocksHttpRequestHandler {
	private $portal = '';
	
	/*
	 * Site Key
	 * Site Name
	 * Site URL
	 */
	
	/**
	 * @param DevblocksHttpRequest
	 * @return DevblocksHttpResponse
	 */
	public function handleRequest(DevblocksHttpRequest $request) {
		$path = $request->path;

		@$a = DevblocksPlatform::importGPC($_REQUEST['a'],'string');
		
		if(empty($a)) {
			@$action = array_shift($path) . 'Action';
		} else {
			@$action = $a . 'Action';
		}

		switch($action) {
			case NULL:
				// [TODO] Index/page render
				break;
//
			default:
				// Default action, call arg as a method suffixed with Action
				if(method_exists($this,$action)) {
					call_user_func(array(&$this, $action)); // [TODO] Pass HttpRequest as arg?
				}
				break;
		}
	}
	
	public function writeResponse(DevblocksHttpResponse $response) {
	}
	
	/**
	 * @param Model_CommunityTool $instance
	 */
	public function configure(Model_CommunityTool $instance) {
	}
	
	public function saveConfiguration(Model_CommunityTool $instance) {
	}
	
};

abstract class Extension_ServiceProvider extends DevblocksExtension {
	const POINT = 'cerb.service.provider';
	
	static $_registry = array();
	
	/**
	 * @return DevblocksExtensionManifest[]|Extension_ServiceProvider[]
	 */
	static function getAll($as_instances=true) {
		$exts = DevblocksPlatform::getExtensions(self::POINT, $as_instances);

		// Sorting
		if($as_instances)
			DevblocksPlatform::sortObjects($exts, 'manifest->name');
		else
			DevblocksPlatform::sortObjects($exts, 'name');
		
		return $exts;
	}

	static function get($extension_id) {
		if(isset(self::$_registry[$extension_id]))
			return self::$_registry[$extension_id];
		
		if(null != ($extension = DevblocksPlatform::getExtension($extension_id, true))
			&& $extension instanceof Extension_ServiceProvider) {

			self::$_registry[$extension->id] = $extension;
			return $extension;
		}
		
		return null;
	}
	
	protected function _renderPopupAuthForm() {
		if(!($this instanceof IServiceProvider_Popup))
			return false;
		
		$tpl = DevblocksPlatform::getTemplateService();
		$session = DevblocksPlatform::getSessionService();
		$settings = DevblocksPlatform::getPluginSettingsService();
		$active_worker = CerberusApplication::getActiveWorker();

		$visit = $session->getVisit();
		
		$tpl->assign('ext', $this);
		$tpl->assign('active_worker', $active_worker);
		$tpl->assign('settings', $settings);
		$tpl->assign('session', $_SESSION);
		$tpl->assign('visit', $visit);
		
		$tpl->display('devblocks:cerberusweb.core::internal/connected_account/popup_shell.tpl');
	}
	
	abstract function renderPopup();
};