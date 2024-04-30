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

class UmScEventListener extends DevblocksEventListenerExtension {
	/**
	 * @param Model_DevblocksEvent $event
	 */
	function handleEvent(Model_DevblocksEvent $event) {
		switch($event->id) {
			case 'cron.maint':
				DAO_SupportCenterAddressShare::maint();
				break;
		}
	}
};

class UmScApp extends Extension_CommunityPortal {
	const PARAM_PAGE_TITLE = 'common.page_title';
	const PARAM_LOGO_URL = 'common.logo_url';
	const PARAM_FAVICON_URL = 'common.favicon_url';
	const PARAM_DEFAULT_LOCALE = 'common.locale';
	const PARAM_LOGIN_EXTENSIONS = 'common.login_extensions';
	const PARAM_VISIBLE_MODULES = 'common.visible_modules';
	
	const SESSION_CAPTCHA = 'write_captcha';
	
	private function _getModules($visible_only=false) {
		static $_all_modules = null;
		static $_visible_modules = null;
		
		// Lazy load
		if(null == $_all_modules) {
			$umsession = ChPortalHelper::getSession();
			$active_contact = $umsession->getActiveContact();
			
			$modules = DevblocksPlatform::getExtensions('usermeet.sc.controller', true);
			$visible_modules = DAO_CommunityToolProperty::getJson(ChPortalHelper::getCode(), self::PARAM_VISIBLE_MODULES, '');
			
			if(is_array($visible_modules))
			uasort($modules, function($a, $b) use ($visible_modules) {
				$a_idx = array_search($a->id, array_keys($visible_modules));
				$b_idx = array_search($b->id, array_keys($visible_modules));
				
				if($a_idx === false)
					return 1;
				
				if($b_idx === false)
					return -1;
				
				if($a_idx == $b_idx)
					return 0;
				
				return ($a_idx < $b_idx) ? -1 : 1;
			});
			
			foreach($modules as $module_id => $module) {  /* @var $module Extension_UmScController */
				if(empty($module) || !$module instanceof Extension_UmScController)
					continue;
				
				$module_uri = $module->manifest->params['uri'] ?? null;

				$_all_modules[$module_uri] = $module;
				
				if(isset($visible_modules[$module_id])) {
					$visibility = $visible_modules[$module_id];
					
					// Disabled
					if(0==strcmp($visibility, '2'))
						continue;
	
					// Must be logged in
					if(0==strcmp($visibility, '1') && empty($active_contact))
						continue;
					
					if($module->isVisible())
						$_visible_modules[$module_uri] = $module;
				}
			}
		}
		
		return $visible_only ? $_visible_modules : $_all_modules;
	}
	
	public static function getLoginExtensions($as_instances=false) {
		$login_extensions = DevblocksPlatform::getExtensions('usermeet.login.authenticator', $as_instances);
		DevblocksPlatform::sortObjects($login_extensions, 'name');
		return $login_extensions;
	}
	
	public static function getLoginExtensionsEnabled($instance_id, $as_instances=false) {
		$login_extensions = self::getLoginExtensions($as_instances);
		
		$enabled = [];

		if(null != ($str = DAO_CommunityToolProperty::get($instance_id, self::PARAM_LOGIN_EXTENSIONS, ''))) {
			$ids = explode(',', $str);
			foreach($ids as $id) {
				if(isset($login_extensions[$id]))
					$enabled[$id] = $login_extensions[$id];
			}
		}
		
		return $enabled;
	}
	
	/**
	 * @param string $instance_id
	 * @param bool $as_instance
	 * @return Extension_ScLoginAuthenticator
	 */
	public static function getLoginExtensionActive($instance_id, $as_instance=true) {
		$umsession = ChPortalHelper::getSession();
		$enabled = self::getLoginExtensionsEnabled($instance_id);
		
		$login_method = $umsession->getProperty('login_method', '');
		$manifest = null;

		// If we have a preference cookied, return it
		if(isset($enabled[$login_method]))
			$manifest = $enabled[$login_method];

		// Otherwise try to default to email+pass
		if(empty($manifest) && isset($enabled['sc.login.auth.default']))
			$manifest = $enabled['sc.login.auth.default'];
			
		// If all else fails, return the first enabled login handler
		if(empty($manifest))
			$manifest = array_shift($enabled);

		if(empty($manifest))
			return NULL;
			
		if($as_instance)
			return $manifest->createInstance();
		else
			return $manifest;
	}
	
	public function handleRequest(DevblocksHttpRequest $request) {
		$stack = $request->path;
		$module_uri = array_shift($stack);
		
		$umsession = ChPortalHelper::getSession();
		
		// CSRF checking
		
		// If we are running a controller action with an active session...
		if(isset($_REQUEST['c']) || isset($_REQUEST['a'])) {
			
			// ...and we're not in DEVELOPMENT_MODE
			if(!DEVELOPMENT_MODE_ALLOW_CSRF) {
			
				// ...and the CSRF token is invalid for this session, freak out
				if(!$umsession->csrf_token || $umsession->csrf_token != $request->csrf_token) {
					//$referer = $_SERVER['HTTP_REFERER'] ?? null;
					//@$remote_addr = DevblocksPlatform::getClientIp();
					
					//error_log(sprintf("[Cerb/Security] Possible CSRF attack from IP %s using referrer %s", $remote_addr, $referer), E_USER_WARNING);
					DevblocksPlatform::dieWithHttpError("Access denied", 403);
				}
			}
		}
		
		// Set locale in scope
		$default_locale = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(), self::PARAM_DEFAULT_LOCALE, 'en_US');
		DevblocksPlatform::setLocale($default_locale);
		
		switch($module_uri) {
			default:
				$modules = $this->_getModules();
				$controller = null;
				
				if(isset($modules[$module_uri])) {
					$controller = $modules[$module_uri];
				}
				
				array_unshift($stack, $module_uri);
				
				if(!is_null($controller))
					$controller->handleRequest(new DevblocksHttpRequest($stack));
					
				break;
		}
	}
	
	public function writeResponse(DevblocksHttpResponse $response) {
		$stack = $response->path;
		
		$tpl = DevblocksPlatform::services()->templateSandbox();
		
		$umsession = ChPortalHelper::getSession();
		$tpl->assign('session', $umsession);
		
		$tpl->assign('portal_code', ChPortalHelper::getCode());
		
		$page_title = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(), self::PARAM_PAGE_TITLE, 'Support Center');
		$tpl->assign('page_title', $page_title);
		
		$logo_url = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(), self::PARAM_LOGO_URL, null);
		$tpl->assign('logo_url', $logo_url);
		
		$favicon_url = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(), self::PARAM_FAVICON_URL, null);
		$tpl->assign('favicon_url', $favicon_url);

		$visible_modules = DAO_CommunityToolProperty::getJson(ChPortalHelper::getCode(), self::PARAM_VISIBLE_MODULES, '');
		$tpl->assign('visible_modules', $visible_modules);
		
		$active_contact = $umsession->getActiveContact();
		$tpl->assign('active_contact', $active_contact);

		$login_extensions_enabled = UmScApp::getLoginExtensionsEnabled(ChPortalHelper::getCode());
		$tpl->assign('login_extensions_enabled', $login_extensions_enabled);
		
		$module_uri = array_shift($stack);
		
		switch($module_uri) {
			case 'rss':
				$controller = new UmScRssController(null);
				$controller->handleRequest(new DevblocksHttpRequest($stack));
				break;
				
			case 'captcha':
				$bgcolor = array_fill(0, 3, mt_rand(120,240));
				
				DevblocksPlatform::services()->http()
					->setHeader('Cache-Control', 'no-cache, must-revalidate')
					->setHeader('Content-Type', 'image/jpeg')
					->setHeader('Pragma', 'no-cache')
				;

				// Get CAPTCHA secret passphrase
				$phrase = CerberusApplication::generatePassword(4);
				$umsession->setProperty(UmScApp::SESSION_CAPTCHA, $phrase);
				
				if(!($im = imagecreate(mt_rand(140,160), mt_rand(75,85))))
					DevblocksPlatform::dieWithHttpError(null, 500);
				
				imagecolorallocate($im, $bgcolor[0], $bgcolor[1], $bgcolor[2]);
				$font = DEVBLOCKS_PATH . 'resources/font/Oswald-Bold.ttf';
				
				foreach(str_split($phrase) as $i => $c) {
					$text_color = imagecolorallocate($im, mt_rand(0,75),mt_rand(0,75),mt_rand(0,75));
					imagettftext($im, mt_rand(25,30), mt_rand(-15,15), 25*($i+1), 55+mt_rand(-10,10), $text_color, $font, $c);
				}
				
				imagejpeg($im,null,85);
				imagedestroy($im);
				exit;
			
			default:
				// Build the menu
				$all_modules = $this->_getModules(false);
				$visible_modules = $this->_getModules(true);
				
				$menu_modules = array();
				if(is_array($visible_modules))
				foreach($visible_modules as $uri => $module) {
					// Must be menu renderable
					if(!empty($module->manifest->params['menu_title']) && !empty($uri)) {
						$menu_modules[$uri] = $module;
					}
				}
				$tpl->assign('menu', $menu_modules);

				// If asking for a module that requires auth, redirect to login (if enabled)
				if(!isset($visible_modules[$module_uri]) && isset($all_modules[$module_uri]) && isset($visible_modules['login'])) {
					$umsession->setProperty('login.original_path', implode('/',$response->path));
					DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login')));
					
				// Display the visible module
				} elseif(isset($visible_modules[$module_uri])) {
					$controller = $visible_modules[$module_uri];
					
				} else {
					// First menu item
					$controller = reset($menu_modules);
				}

				array_unshift($stack, $module_uri);
				$tpl->assign('module', $controller);
				$tpl->assign('module_response', new DevblocksHttpResponse($stack));
				
				try {
					$tpl->display('devblocks:cerberusweb.support_center:portal_' . ChPortalHelper::getCode() . ':support_center/index.tpl');
				} catch (Exception $e) {
					DevblocksPlatform::logError($e->getMessage());
				}
				break;
		}
	}
	
	public function configure(Model_CommunityTool $portal) {
		$tpl = DevblocksPlatform::services()->template();
		
		$config_tab = DevblocksPlatform::importGPC($_REQUEST['config_tab'] ?? null, 'string', '');
		
		$tpl->assign('portal', $portal);
		
		switch($config_tab) {
			case '':
				$modules = Extension_UmScController::getAll(false, ['configurable']);
				
				$config_tabs = [
					'templates' => 'Templates',
				];
				
				foreach($modules as $module) {
					$config_tabs[$module->params['uri']] = DevblocksPlatform::translateCapitalized($module->params['menu_title']);
				}
				
				asort($config_tabs);
				
				$config_tabs = array_merge(['website' => 'Website'], $config_tabs);
				
				$tpl->assign('config_tabs', $config_tabs);
				
				$tpl->display("devblocks:cerberusweb.support_center::portal/sc/profile/tabs/configuration.tpl");
				break;
			
			case 'website':
				$this->_profileRenderConfigTabWebsite($portal);
				break;
			
			case 'templates':
				$this->_profileRenderConfigTabTemplates($portal);
				break;
			
			default:
				if(false != ($controller = Extension_UmScController::getByUri($config_tab, true))) {
					$controller->configure($portal);
				}
		}
	}
	
	function saveConfiguration(Model_CommunityTool $portal) {
		$portal_id = DevblocksPlatform::importGPC($_POST['portal_id'] ?? null, 'integer', 0);
		$config_tab = DevblocksPlatform::importGPC($_POST['config_tab'] ?? null, 'string', '');
		
		if(false == ($active_worker = CerberusApplication::getActiveWorker()))
			DevblocksPlatform::dieWithHttpError('', 403);

		if(!Context_CommunityTool::isWriteableByActor($portal, $active_worker))
			DevblocksPlatform::dieWithHttpError('', 403);
		
		switch($config_tab) {
			case 'website':
				$this->_profileSaveConfigTabWebsite($portal);
				break;
				
			case 'templates':
				$tab_action = DevblocksPlatform::importGPC($_REQUEST['tab_action'] ?? null, 'string', '');
				
				switch($tab_action) {
					case 'saveAddTemplatePeek':
						$this->_saveAddTemplatePeek();
						break;
				}
				break;
				
			default:
				if(false != ($controller = Extension_UmScController::getByUri($config_tab, true))) {
					$controller->saveConfiguration($portal);
				}
				break;
		}
	}
	
	private function _profileRenderConfigTabWebsite(Model_CommunityTool $portal) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('portal', $portal);

		// Locales
		
		$default_locale = DAO_CommunityToolProperty::get($portal->code, self::PARAM_DEFAULT_LOCALE, 'en_US');
		$tpl->assign('default_locale', $default_locale);
		
		$locales = DAO_Translation::getDefinedLangCodes();
		$tpl->assign('locales', $locales);
		
		// Personalization

		$page_title = DAO_CommunityToolProperty::get($portal->code, self::PARAM_PAGE_TITLE, 'Support Center');
		$tpl->assign('page_title', $page_title);
		
		$logo_url = DAO_CommunityToolProperty::get($portal->code, self::PARAM_LOGO_URL, null);
		$tpl->assign('logo_url', $logo_url);
		
		$favicon_url = DAO_CommunityToolProperty::get($portal->code, self::PARAM_FAVICON_URL, null);
		$tpl->assign('favicon_url', $favicon_url);

		// Modules

		$visible_modules = DAO_CommunityToolProperty::getJson($portal->code, self::PARAM_VISIBLE_MODULES, '');
		$tpl->assign('visible_modules', $visible_modules);
		
		$all_modules = DevblocksPlatform::getExtensions('usermeet.sc.controller', true);
		$modules = array();
		
		// Sort the enabled modules first, in order.
		if(is_array($visible_modules))
		foreach($visible_modules as $module_id => $visibility) {
			if(!isset($all_modules[$module_id]))
				continue;
			
			$module = $all_modules[$module_id];
			$modules[$module_id] = $module;
			unset($all_modules[$module_id]);
		}
		
		// Append the unused modules
		if(is_array($all_modules))
		foreach($all_modules as $module_id => $module) {
			$modules[$module_id] = $module;
			$modules = array_merge($modules, $all_modules);
		}
		
		$tpl->assign('modules', $modules);
		$tpl->display("devblocks:cerberusweb.support_center::portal/sc/profile/tabs/configuration/website.tpl");
	}
	
	private function _profileSaveConfigTabWebsite(Model_CommunityTool $portal) {
		$aVisibleModules = DevblocksPlatform::importGPC($_POST['visible_modules'] ?? null, 'array', []);
		$aIdxModules = DevblocksPlatform::importGPC($_POST['idx_modules'] ?? null, 'array', []);
		$sPageTitle = DevblocksPlatform::importGPC($_POST['page_title'] ?? null, 'string','Contact Us');
		$logo_url = DevblocksPlatform::importGPC($_POST['logo_url'] ?? null, 'string',null);
		$favicon_url = DevblocksPlatform::importGPC($_POST['favicon_url'] ?? null, 'string',null);
		
		// Modules (toggle + sort)
		$aEnabledModules = array();
		foreach($aVisibleModules as $idx => $visible) {
			// If not hidden
			if(0 != strcmp($aVisibleModules[$idx],'2'))
				$aEnabledModules[$aIdxModules[$idx]] = $aVisibleModules[$idx];
		}
			
		DAO_CommunityToolProperty::setJson($portal->code, self::PARAM_VISIBLE_MODULES, $aEnabledModules);
		DAO_CommunityToolProperty::set($portal->code, self::PARAM_PAGE_TITLE, $sPageTitle);
		
		// [TODO] Validate these URLs
		DAO_CommunityToolProperty::set($portal->code, self::PARAM_LOGO_URL, $logo_url);
		DAO_CommunityToolProperty::set($portal->code, self::PARAM_FAVICON_URL, $favicon_url);

		// Default Locale
		$sDefaultLocale = DevblocksPlatform::importGPC($_POST['default_locale'] ?? null, 'string','en_US');
		DAO_CommunityToolProperty::set($portal->code, self::PARAM_DEFAULT_LOCALE, $sDefaultLocale);
	}
	
	private function _profileRenderConfigTabTemplates(Model_CommunityTool $portal) {
		$config_tab = DevblocksPlatform::importGPC($_REQUEST['config_tab'] ?? null, 'string', '');
		$tab_action = DevblocksPlatform::importGPC($_REQUEST['tab_action'] ?? null, 'string', '');
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('portal', $portal);
		
		switch($tab_action) {
			case 'showAddTemplatePeek':
				$this->_showAddTemplatePeek();
				break;
				
			default:
				$defaults = C4_AbstractViewModel::loadFromClass('View_DevblocksTemplate');
				$defaults->id = 'portal_templates';
				$defaults->renderLimit = 15;
				
				if(false != ($view = C4_AbstractViewLoader::getView($defaults->id, $defaults))) {
					$view->name = 'Custom Templates';
					
					$view->addParamsRequired(array(
						new DevblocksSearchCriteria(SearchFields_DevblocksTemplate::TAG,'=','portal_'.$portal->code),
					), true);
				}
				
				$tpl->assign('view', $view);
				$tpl->assign('templates_enabled', APP_OPT_DEPRECATED_PORTAL_CUSTOM_TEMPLATES);
				
				$tpl->display("devblocks:cerberusweb.support_center::portal/sc/profile/tabs/configuration/templates.tpl");
				break;
		}
	}
	
	private function _showAddTemplatePeek() {
		$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'] ?? null,'string','');
		$portal_id = DevblocksPlatform::importGPC($_REQUEST['portal_id'] ?? null, 'integer',0);
		
		$tpl = DevblocksPlatform::services()->template();

		if(!$portal_id || null == ($portal = DAO_CommunityTool::get($portal_id)))
			return;
			
		if(null == ($tool_ext = DevblocksPlatform::getExtension($portal->extension_id, false)))
			return;
			
		$tpl->assign('view_id', $view_id);
		$tpl->assign('portal', $portal);
		
		if(null == ($template_set = @$tool_ext->params['template_set']))
			$template_set = ''; // not null
		
		$templates = DevblocksPlatform::getTemplates($template_set);
		
		$existing_templates = DAO_DevblocksTemplate::getWhere(sprintf("%s = %s",
			DAO_DevblocksTemplate::TAG,
			Cerb_ORMHelper::qstr('portal_'.$portal->code)
		));
		
		// Sort templates
		DevblocksPlatform::sortObjects($templates, 'sort_key');
		
		// Filter out templates implemented by this portal already
		if(is_array($templates))
		foreach($templates as $idx => $template) { /* @var $template DevblocksTemplate */
			if(is_array($existing_templates))
			foreach($existing_templates as $existing) { /* @var $existing Model_DevblocksTemplate */
				if(0 == strcasecmp($template->plugin_id, $existing->plugin_id)
					&& 0 == strcasecmp($template->path, $existing->path))
						unset($templates[$idx]);
			}
		}
		$tpl->assign('templates', $templates);
		
		$tpl->display('devblocks:cerberusweb.support_center::portal/sc/profile/tabs/configuration/templates/add.tpl');
	}
	
	private function _saveAddTemplatePeek() {
		$portal_id = DevblocksPlatform::importGPC($_POST['portal_id'] ?? null, 'integer',0);
		$view_id = DevblocksPlatform::importGPC($_POST['view_id'] ?? null, 'string','');
		$template = DevblocksPlatform::importGPC($_POST['template'] ?? null, 'string','');
		
		list($plugin_id, $template_path) = explode(':', $template, 2);
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('view_id', $view_id);
		
		if(false == ($portal = DAO_CommunityTool::get($portal_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_CommunityTool::isWriteableByActor($portal, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		// Pull from filesystem for editing
		$content = '';
		if(null != ($plugin = DevblocksPlatform::getPlugin($plugin_id))) {
			$basepath = realpath($plugin->getStoragePath() . '/templates/') . DIRECTORY_SEPARATOR;
		
			if(false == ($path = realpath($plugin->getStoragePath() . '/templates/' . $template_path)))
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			if(!DevblocksPlatform::strStartsWith($path, $basepath))
				DevblocksPlatform::dieWithHttpError(null, 403);
			
			if(file_exists($path)) {
				$content = file_get_contents($path);
			}
		}
		
		$fields = [
			DAO_DevblocksTemplate::LAST_UPDATED => 0,
			DAO_DevblocksTemplate::PLUGIN_ID => $plugin_id,
			DAO_DevblocksTemplate::PATH => $template_path,
			DAO_DevblocksTemplate::TAG => 'portal_' . $portal->code,
			DAO_DevblocksTemplate::CONTENT => $content,
		];
		$id = DAO_DevblocksTemplate::create($fields);

		$template = DAO_DevblocksTemplate::get($id);
		$tpl->assign('template', $template);
	}
};

class UmScLoginAuthenticator extends Extension_ScLoginAuthenticator {
	public function renderConfigForm(Model_CommunityTool $instance) {
		$tpl = DevblocksPlatform::services()->template();

		$params = DAO_CommunityToolProperty::getAllByTool($instance->code);
		$tpl->assign('params', $params);
		
		$tpl->display('devblocks:cerberusweb.support_center::portal/sc/profile/tabs/configuration/login/config.tpl');
	}
	
	public function saveConfiguration(Model_CommunityTool $instance) {
		$params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array',[]);
		
		$value = intval($params['auth_register_disabled'] ?? null);
		DAO_CommunityToolProperty::set($instance->code, 'auth.register.disabled', $value);
		
		$value = intval($params['auth_recover_disabled'] ?? null);
		DAO_CommunityToolProperty::set($instance->code, 'auth.recover.disabled', $value);
	}
	
	public function invoke(string $action) {
		switch($action) {
			case 'authenticate':
				return $this->_portalAction_authenticate();
			case 'doRecover':
				return $this->_portalAction_doRecover();
			case 'doRegister':
				return $this->_portalAction_doRegister();
			case 'doRegisterConfirm':
				return $this->_portalAction_doRegisterConfirm();
			case 'recoverAccount':
				return $this->_portalAction_recoverAccount();
		}
		return false;
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::services()->templateSandbox();
		
		$stack = $response->path;
		@$module = array_shift($stack);
		
		$params = DAO_CommunityToolProperty::getAllByTool(ChPortalHelper::getCode());
		$tpl->assign('params', $params);
		
		switch($module) {
			case 'register':
				if($params['auth.register.disabled'] ?? 0)
					DevblocksPlatform::dieWithHttpError(null, 404);
				
				if(isset($stack[0]) && 0==strcasecmp('confirm',$stack[0])) {
					$tpl->display("devblocks:cerberusweb.support_center:portal_".ChPortalHelper::getCode().":support_center/login/default/register_confirm.tpl");
				} else {
					$tpl->display("devblocks:cerberusweb.support_center:portal_".ChPortalHelper::getCode().":support_center/login/default/register.tpl");
				}
				break;
			case 'forgot':
				if($params['auth.recover.disabled'] ?? 0)
					DevblocksPlatform::dieWithHttpError(null, 404);
				
				if(isset($stack[0]) && 0==strcasecmp('confirm',$stack[0])) {
					$tpl->display("devblocks:cerberusweb.support_center:portal_".ChPortalHelper::getCode().":support_center/login/default/forgot_confirm.tpl");
				} else {
					$tpl->display("devblocks:cerberusweb.support_center:portal_".ChPortalHelper::getCode().":support_center/login/default/forgot.tpl");
				}
				break;
			default:
				$tpl->display("devblocks:cerberusweb.support_center:portal_".ChPortalHelper::getCode().":support_center/login/default/login.tpl");
				break;
		}
	}
	
	private function _portalAction_doRegister() {
		$tpl = DevblocksPlatform::services()->templateSandbox();
		$umsession = ChPortalHelper::getSession();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		if(DAO_CommunityToolProperty::get(ChPortalHelper::getCode(), 'auth.register.disabled', 0))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$email = DevblocksPlatform::importGPC($_POST['email'] ?? null, 'string','');
		$given_captcha = DevblocksPlatform::importGPC($_REQUEST['captcha'] ?? null,'string','');
		$stored_captcha = $umsession->getProperty(UmScApp::SESSION_CAPTCHA, '');
		
		// Clear the CAPTCHA after comparison
		$umsession->setProperty(UmScApp::SESSION_CAPTCHA, null);
		
		try {
			// Validate
			if(!($address_parsed = CerberusMail::parseRfcAddress($email)))
				throw new Exception_DevblocksValidationError("The email address you provided is invalid.");
			
			// Check to see if the address is currently assigned to an account
			if(null != ($address = DAO_Address::lookupAddress($address_parsed['email'], false)) && !empty($address->contact_id))
				throw new Exception_DevblocksValidationError("The provided email address is already associated with an account.");
				
			if($address instanceof Model_Address && $address->is_banned)
				throw new Exception_DevblocksValidationError("The provided email address is not available.");
			
			// Check CAPTCHA
			if(!$stored_captcha || !$given_captcha || 0 != strcasecmp($stored_captcha, $given_captcha))
				throw new Exception_DevblocksValidationError("Your text did not match the image.");
			
			// If there's already a confirmation code in the past (t) mins
			$past_confirmation = DAO_ConfirmationCode::getWhere(sprintf("%s = %s AND %s = %s AND %s > %d",
				Cerb_ORMHelper::escape(DAO_ConfirmationCode::NAMESPACE_KEY),
				Cerb_ORMHelper::qstr('support_center.login.register.verify'),
				Cerb_ORMHelper::escape(DAO_ConfirmationCode::META_JSON),
				Cerb_ORMHelper::qstr(json_encode(['email' => $address_parsed['email']])),
				Cerb_ORMHelper::escape(DAO_ConfirmationCode::CREATED),
				time()-1800
			));
			
			if($past_confirmation)
				throw new Exception_DevblocksValidationError("This email address is already pending registration. Please try again later.");
			
			// Send a confirmation code
			$fields = array(
				DAO_ConfirmationCode::CONFIRMATION_CODE => CerberusApplication::generatePassword(8),
				DAO_ConfirmationCode::NAMESPACE_KEY => 'support_center.login.register.verify',
				DAO_ConfirmationCode::META_JSON => json_encode(array(
					'email' => $address_parsed['email'],
				)),
				DAO_ConfirmationCode::CREATED => time(),
			);
			DAO_ConfirmationCode::create($fields);

			// Quick send
			$msg = sprintf(
				"Your confirmation code: %s",
				urlencode($fields[DAO_ConfirmationCode::CONFIRMATION_CODE])
			);
			CerberusMail::quickSend($address_parsed['email'],"Please confirm your email address", $msg);
			
			// Update the preferred email address
			$tpl->assign('email', $address_parsed['email']);
			
		} catch(Exception_DevblocksValidationError $e) {
			$tpl->assign('error', $e->getMessage());
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'login','register')));
			return;
			
		} catch(Throwable) {
			$tpl->assign('error', 'An unexpected error occurred.');
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'login','register')));
			return;
			
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'login','register','confirm')));
	}
	
	private function _portalAction_doRegisterConfirm() {
		$tpl = DevblocksPlatform::services()->templateSandbox();
		$url_writer = DevblocksPlatform::services()->url();
		$umsession = ChPortalHelper::getSession();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		if(DAO_CommunityToolProperty::get(ChPortalHelper::getCode(), 'auth.register.disabled', 0))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$email = DevblocksPlatform::importGPC($_POST['email'] ?? null, 'string','');
		$confirm = DevblocksPlatform::importGPC($_POST['confirm'] ?? null, 'string','');
		$first_name = DevblocksPlatform::importGPC($_POST['first_name'] ?? null, 'string','');
		$last_name = DevblocksPlatform::importGPC($_POST['last_name'] ?? null, 'string','');
		$password = DevblocksPlatform::importGPC($_POST['password'] ?? null, 'string','');
		$password2 = DevblocksPlatform::importGPC($_POST['password2'] ?? null, 'string','');
		
		try {
			// We need the basics in place
			if(empty($email)) {
				DevblocksPlatform::redirectURL($url_writer->write('c=login', true));
			}
			
			// Lookup code
			if(null == ($code = DAO_ConfirmationCode::getByCode('support_center.login.register.verify', $confirm)))
				throw new Exception_DevblocksValidationError("Your confirmation code is invalid.");
			
			// Compare to address
			if(!isset($code->meta['email']) || 0 != strcasecmp($email, $code->meta['email']))
				throw new Exception_DevblocksValidationError("Your confirmation code is invalid.");

			// Password
			if(empty($password) || empty($password2))
				throw new Exception_DevblocksValidationError("Your password cannot be blank.");

			if(0 != strcmp($password, $password2))
				throw new Exception_DevblocksValidationError("Your passwords do not match.");
			
			if(strlen($password) < 8)
				throw new Exception_DevblocksValidationError("Your password must be at least 8 characters.");
				
			// Load the address
			if(null == ($address = DAO_Address::lookupAddress($email, true)))
				throw new Exception_DevblocksValidationError("You have provided an invalid email address.");
			
			// Verify address is unlinked
			if(!empty($address->contact_id))
				throw new Exception_DevblocksValidationError("The email address you provided is already associated with an account.");

			// Create the contact
			$salt = CerberusApplication::generatePassword(8);
			$fields = array(
				DAO_Contact::PRIMARY_EMAIL_ID => $address->id,
				DAO_Contact::FIRST_NAME => $first_name,
				DAO_Contact::LAST_NAME => $last_name,
				DAO_Contact::LAST_LOGIN_AT => time(),
				DAO_Contact::CREATED_AT => time(),
				DAO_Contact::AUTH_SALT => $salt,
				DAO_Contact::AUTH_PASSWORD => md5($salt.md5($password)),
			);
			$contact_id = DAO_Contact::create($fields);
			
			if(empty($contact_id) || null == ($contact = DAO_Contact::get($contact_id)))
				throw new Exception_DevblocksValidationError("There was an error creating your account.");
				
			// Link email
			DAO_Address::update($address->id,array(
				DAO_Address::CONTACT_ID => $contact_id,
			));
			
			// Delete confirmation and one-time login token
			DAO_ConfirmationCode::delete($code->id);
			
			// Log in the session
			
			$umsession->login($contact);
			
			// Bot events
			
			Event_ContactRegisteredInSupportCenter::trigger($contact_id, null);
			
			// Redirect
			
			$address_uri = urlencode(str_replace(array('@','.'),array('_at_','_dot_'),$address->email));
			DevblocksPlatform::redirectURL($url_writer->write('c=account&a=email&address='.$address_uri, true));
			
		} catch(Exception_DevblocksValidationError $e) {
			$tpl->assign('error', $e->getMessage());
			
		} catch(Throwable) {
			$tpl->assign('error', 'An unexpected error occurred.');
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'login','register','confirm')));
	}
	
	private function _portalAction_doRecover() {
		$tpl = DevblocksPlatform::services()->templateSandbox();
		$umsession = ChPortalHelper::getSession();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		if(DAO_CommunityToolProperty::get(ChPortalHelper::getCode(), 'auth.recover.disabled', 0))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$email = DevblocksPlatform::importGPC($_POST['email'] ?? null, 'string','');
		$given_captcha = DevblocksPlatform::importGPC($_REQUEST['captcha'] ?? null,'string','');
		$stored_captcha = $umsession->getProperty(UmScApp::SESSION_CAPTCHA, '');
		
		// Clear the CAPTCHA after comparison
		$umsession->setProperty(UmScApp::SESSION_CAPTCHA, null);
		
		try {
			// Verify email is a contact
			if(null == ($address = DAO_Address::lookupAddress($email, false)))
				throw new Exception_DevblocksValidationError("Your text did not match the image.");
			
			if($address->is_banned)
				throw new Exception_DevblocksValidationError("This account is locked.");
			
			if(!$address->contact_id || null == ($contact = DAO_Contact::get($address->contact_id)))
				throw new Exception_DevblocksValidationError("The email address you provided is not registered.");
			
 			// Check CAPTCHA
			if(!$stored_captcha || !$given_captcha || 0 != strcasecmp($stored_captcha, $given_captcha))
				throw new Exception_DevblocksValidationError("Your text did not match the image.");
			
			// If there's already a confirmation code in the past (t) mins
			$past_resets = DAO_ConfirmationCode::getWhere(sprintf("%s = %s AND %s = %s AND %s > %d",
				Cerb_ORMHelper::escape(DAO_ConfirmationCode::NAMESPACE_KEY),
				Cerb_ORMHelper::qstr('support_center.login.recover'),
				Cerb_ORMHelper::escape(DAO_ConfirmationCode::META_JSON),
				Cerb_ORMHelper::qstr(json_encode(['contact_id' => intval($address->contact_id), 'address_id' => intval($address->id)])),
				Cerb_ORMHelper::escape(DAO_ConfirmationCode::CREATED),
				time()-3600
			));
			
			if($past_resets)
				throw new Exception_DevblocksValidationError("This email address is already pending recovery. Please try again later.");
			
			// Generate + send confirmation
			$fields = array(
				DAO_ConfirmationCode::CONFIRMATION_CODE => CerberusApplication::generatePassword(8),
				DAO_ConfirmationCode::NAMESPACE_KEY => 'support_center.login.recover',
				DAO_ConfirmationCode::META_JSON => json_encode(array(
					'contact_id' => intval($contact->id),
					'address_id' => intval($address->id),
				)),
				DAO_ConfirmationCode::CREATED => time(),
			);
			DAO_ConfirmationCode::create($fields);

			// Quick send
			$msg = sprintf(
				"Your confirmation code: %s",
				urlencode($fields[DAO_ConfirmationCode::CONFIRMATION_CODE])
			);
			CerberusMail::quickSend($address->email,"Please confirm your email address", $msg);
			
			$tpl->assign('email', $address->email);
			
		} catch (Exception_DevblocksValidationError $e) {
			$tpl->assign('error', $e->getMessage());
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'login','forgot')));
			return;
			
		} catch (Throwable) {
			$tpl->assign('error', 'An unexpected error occurred.');
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'login','forgot')));
			return;
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'login','forgot','confirm')));
	}
	
	private function _portalAction_recoverAccount() {
		$umsession = ChPortalHelper::getSession();
		$url_writer = DevblocksPlatform::services()->url();
		$tpl = DevblocksPlatform::services()->templateSandbox();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		if(DAO_CommunityToolProperty::get(ChPortalHelper::getCode(), 'auth.recover.disabled', 0))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$email = DevblocksPlatform::importGPC($_POST['email'] ?? null, 'string','');
		$confirm = DevblocksPlatform::importGPC($_POST['confirm'] ?? null, 'string','');
		$password_new = DevblocksPlatform::importGPC($_POST['password_new'] ?? null, 'string','');
		$password_new_confirm = DevblocksPlatform::importGPC($_POST['password_new_confirm'] ?? null, 'string','');
		
		try {
			$tpl->assign('email', $email);
			
			// Verify email is a contact
			if(!$email || !($address = DAO_Address::lookupAddress($email, false)))
				throw new Exception_DevblocksValidationError("Your confirmation code is invalid.");
			
			if(empty($address->contact_id) || null == ($contact = DAO_Contact::get($address->contact_id)))
				throw new Exception_DevblocksValidationError("Your confirmation code is invalid.");
			
			// Lookup code
			if(null == ($code = DAO_ConfirmationCode::getByCode('support_center.login.recover', $confirm)))
				throw new Exception_DevblocksValidationError("Your confirmation code is invalid.");
			
			// Compare to contact
			if(!isset($code->meta['contact_id']) || $contact->id != $code->meta['contact_id'])
				throw new Exception_DevblocksValidationError("Your confirmation code is invalid.");
				
			// Compare to email address
			if(!isset($code->meta['address_id']) || $address->id != $code->meta['address_id'])
				throw new Exception_DevblocksValidationError("Your confirmation code is invalid.");
			
			if(!$password_new || !$password_new_confirm)
				throw new Exception_DevblocksValidationError("A new password is required.");
			
			if(strlen($password_new) < 8)
				throw new Exception_DevblocksValidationError("Your new password must be at least 8 characters.");
			
			if($password_new != $password_new_confirm)
				throw new Exception_DevblocksValidationError("Your confirmed password does not match.");
			
			// Success (delete token and one-time log in token)
			DAO_ConfirmationCode::delete($code->id);
			
			// Set new password
			$salt = CerberusApplication::generatePassword(8);
			DAO_Contact::update($contact->id, [
				DAO_Contact::AUTH_SALT => $salt,
				DAO_Contact::AUTH_PASSWORD => md5($salt.md5($password_new)),
			]);
			
			// Log in the session
			$umsession->login($contact);
			DevblocksPlatform::redirectURL($url_writer->write('c=account&a=password', true));
			
		} catch (Exception_DevblocksValidationError $e) {
			$tpl->assign('error', $e->getMessage());
			
		} catch (Throwable) {
			$tpl->assign('error', 'An unexpected error occurred.');
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'login','forgot','confirm')));
	}
	
	/**
	 * pull auth info out of $_POST, check it, return user_id or false
	 *
	 * @return boolean whether login succeeded
	 */
	private function _portalAction_authenticate() {
		$umsession = ChPortalHelper::getSession();
		$tpl = DevblocksPlatform::services()->templateSandbox();
		
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$email = DevblocksPlatform::importGPC($_POST['email'] ?? null);
		$pass = DevblocksPlatform::importGPC($_POST['password'] ?? null);
		
		// Clear the past session
		$umsession->logout();
		
		try {
			// Find the address
			if(null == ($addy = DAO_Address::lookupAddress($email, FALSE)))
				throw new Exception_DevblocksValidationError("Login failed.");
			
			// Not registered
			if(empty($addy->contact_id) || null == ($contact = $addy->getContact()))
				throw new Exception_DevblocksValidationError("Login failed.");
			
			if($addy->is_banned)
				throw new Exception_DevblocksValidationError("Login failed.");
			
			// Compare salt
			if(0 != strcmp(md5($contact->auth_salt.md5($pass)),$contact->auth_password))
				throw new Exception_DevblocksValidationError("Login failed.");
			
			$umsession->login($contact);
			
			$original_path = $umsession->getProperty('login.original_path', '');
			$path = !empty($original_path) ? explode('/', $original_path) : array();
			
			DevblocksPlatform::redirect(new DevblocksHttpResponse($path));
			
		} catch (Exception_DevblocksValidationError $e) {
			$tpl->assign('error', $e->getMessage());
			
		} catch (Throwable) {
			$tpl->assign('error', 'An unexpected error occurred.');
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'login')));
	}
};

class UmScAbstractViewLoader {
	static $views = null;
	const VISIT_ABSTRACTVIEWS = 'abstractviews_list';

	static protected function _init() {
		$umsession = ChPortalHelper::getSession();
		self::$views = $umsession->getProperty(self::VISIT_ABSTRACTVIEWS, array());
	}

	/**
	 * @param string $view_label Abstract view identifier
	 * @return boolean
	 */
	static function exists($view_label) {
		if(is_null(self::$views))
			self::_init();
		
		return isset(self::$views[$view_label]);
	}

	/**
	 *
	 * @param string $class UmScAbstractView
	 * @param string $view_label ID
	 * @return C4_AbstractView instance
	 */
	static function getView($class, $view_label) {
		if(is_null(self::$views)) {
			self::_init();
		}

		if(!self::exists($view_label)) {
			if(empty($class) || !class_exists($class))
				return null;
				
			$view = new $class;
			self::setView($view_label, $view);
			return $view;
		}

		$model = self::$views[$view_label];
		$view = self::unserializeAbstractView($model);

		return $view;
	}

	/**
	 *
	 * @param string $class UmScAbstractView
	 * @param string $view_label ID
	 * @param C4_AbstractView $view
	 */
	static function setView($view_label, $view) {
		if(is_null(self::$views)) self::_init();
		self::$views[$view_label] = self::serializeAbstractView($view);
		self::_save();
	}

	static function deleteView($view_label) {
		unset(self::$views[$view_label]);
		self::_save();
	}
	
	static protected function _save() {
		// persist
		$umsession = ChPortalHelper::getSession();
		$umsession->setProperty(self::VISIT_ABSTRACTVIEWS, self::$views);
	}

	static function serializeAbstractView($view) {
		if(!($view instanceof C4_AbstractView))
			return null;
		
		return [
			'class_name' => get_class($view),
			'id' => $view->id,
			'name' => $view->name,
			
			'view_columns' => $view->view_columns,
			'columnsHidden' => $view->getColumnsHidden(),
			
			'paramsEditable' => json_encode($view->getEditableParams()),
			'paramsDefault' => json_encode($view->getParamsDefault()),
			'paramsRequired' => json_encode($view->getParamsRequired()),
			
			'renderPage' => $view->renderPage,
			'renderLimit' => $view->renderLimit,
			
			'renderSort' => $view->getSorts(),
		];
	}

	static function unserializeAbstractView(array $model) : ?C4_AbstractView {
		if(!class_exists($model['class_name'] ?? ''))
			return null;
		
		if(!in_array($model['class_name'], [
			'UmSc_KbArticleView',
			'UmSc_TicketHistoryView',
		]));
		
		if(!is_a($model['class_name'], 'C4_AbstractView', true))
			return null;
		
		if(null == ($inst = new $model['class_name']))
			return null;

		/* @var $inst C4_AbstractView */
			
		$inst->id = $model['id'] ?? '';
		$inst->name = $model['name'] ?? '';
		
		$inst->view_columns = $model['view_columns'] ?? [];
		$inst->addColumnsHidden($model['columnsHidden'] ?? [], true);
		
		$inst->addParams(DAO_WorkerViewModel::decodeParamsJson($model['paramsEditable'] ?? ''));
		$inst->addParamsDefault(DAO_WorkerViewModel::decodeParamsJson($model['paramsDefault'] ?? ''));
		$inst->addParamsRequired(DAO_WorkerViewModel::decodeParamsJson($model['paramsRequired'] ?? ''));

		$inst->renderPage = (int)$model['renderPage'];
		$inst->renderLimit = (int)$model['renderLimit'];
		
		if(is_array($model['renderSort'] ?? null)) {
			if(1 == count($model['renderSort'])) {
				$inst->renderSortBy = key($model['renderSort']);
				$inst->renderSortAsc = current($model['renderSort']);
				
			} else {
				$inst->renderSortBy = array_keys($model['renderSort']);
				$inst->renderSortAsc = array_values($model['renderSort']);
			}
		}

		return $inst;
	}
};

class UmScRssController extends Extension_UmScController {
	public function isVisible() {
		return false;
	}
	
	function invoke(string $action, DevblocksHttpRequest $request=null) {
		return false;
	}
	
	function handleRequest(DevblocksHttpRequest $request) {
		@$path = $request->path;
				
		if(empty($path) || !is_array($path))
			return;

		$uri = array_shift($path);
		
		$rss_controllers = DevblocksPlatform::getExtensions('usermeet.sc.rss.controller');
		
		foreach($rss_controllers as $extension_id => $rss_controller) {
			if(0==strcasecmp($rss_controller->params['uri'],$uri)) {
				$controller = DevblocksPlatform::getExtension($extension_id, true);
				$controller->handleRequest(new DevblocksHttpRequest($path));
				return;
			}
		}
		
		// [TODO] sub-controller not found
	}
};
