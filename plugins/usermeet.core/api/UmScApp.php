<?php
class UmScApp extends Extension_UsermeetTool {
	const PARAM_PAGE_TITLE = 'common.page_title';
	const PARAM_DEFAULT_LOCALE = 'common.locale';
	const PARAM_STYLE_CSS = 'common.style_css';
	const PARAM_HEADER_HTML = 'common.header_html';
	const PARAM_FOOTER_HTML = 'common.footer_html';
	const PARAM_ALLOW_LOGINS = 'common.allow_logins';
	const PARAM_ENABLED_MODULES = 'common.enabled_modules';
	
	const SESSION_CAPTCHA = 'write_captcha';
	
    function __construct($manifest) {
        parent::__construct($manifest);
    }
    
    private function _getModules() {
    	static $modules = null;
		
    	// Lazy load
    	if(null == $modules) {
	        $enabled_modules = DevblocksPlatform::parseCsvString(DAO_CommunityToolProperty::get(UmPortalHelper::getCode(), self::PARAM_ENABLED_MODULES, ''));
			
			if(is_array($enabled_modules))
			foreach($enabled_modules as $module_id) {
				$module = DevblocksPlatform::getExtension($module_id,true,true); /* @var $module Extension_UmScController */
				
				if(empty($module) || !$module instanceof Extension_UmScController)
					continue;
				
				@$module_uri = $module->manifest->params['uri'];
	
				if($module->isVisible())
					$modules[$module_uri] = $module;
			}
    	}
		
    	return $modules;
    }
    
    public function handleRequest(DevblocksHttpRequest $request) {
    	$stack = $request->path;
        $module_uri = array_shift($stack);
        
		// Set locale in scope
        $default_locale = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(), self::PARAM_DEFAULT_LOCALE, 'en_US');
		DevblocksPlatform::setLocale($default_locale);
		
		switch($module_uri) {
			case 'login':
				$this->doLogin();
				break;
				
			case 'logout':
				$this->doLogout();
				break;
				
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
        $umsession = UmPortalHelper::getSession();
		$stack = $response->path;
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('tpl_path', $tpl_path);
		
		$page_title = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(), self::PARAM_PAGE_TITLE, 'Support Center');
		$tpl->assign('page_title', $page_title);

        $style_css = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(), self::PARAM_STYLE_CSS, '');
		$tpl->assign('style_css', $style_css);

        $header_html = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(), self::PARAM_HEADER_HTML, '');
		$tpl->assign('header_html', $header_html);

        $footer_html = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(), self::PARAM_FOOTER_HTML, '');
		$tpl->assign('footer_html', $footer_html);
		
        $allow_logins = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(), self::PARAM_ALLOW_LOGINS, 0);
		$tpl->assign('allow_logins', $allow_logins);
		
        $enabled_modules = DevblocksPlatform::parseCsvString(DAO_CommunityToolProperty::get(UmPortalHelper::getCode(), self::PARAM_ENABLED_MODULES, ''));
		$tpl->assign('enabled_modules', $enabled_modules);
		
        @$active_user = $umsession->getProperty('sc_login',null);
        $tpl->assign('active_user', $active_user);

		// Usermeet Session
		if(null == ($fingerprint = UmPortalHelper::getFingerprint())) {
			die("A problem occurred.");
		}
        $tpl->assign('fingerprint', $fingerprint);

        $module_uri = array_shift($stack);
		
		switch($module_uri) {
			case 'ajax':
				$controller = new UmScAjaxController(null);
				$controller->handleRequest(new DevblocksHttpRequest($stack));
				break;
				
			case 'rss':
				$controller = new UmScRssController(null);
				$controller->handleRequest(new DevblocksHttpRequest($stack));
				break;
				
			case 'captcha':
                header('Cache-control: max-age=0', true); // 1 wk // , must-revalidate
                header('Expires: ' . gmdate('D, d M Y H:i:s',time()-604800) . ' GMT'); // 1 wk
				header('Content-type: image/jpeg');

		        // Get CAPTCHA secret passphrase
				$phrase = CerberusApplication::generatePassword(4);
		        $umsession->setProperty(UmScApp::SESSION_CAPTCHA, $phrase);
                
				$im = @imagecreate(150, 70) or die("Cannot Initialize new GD image stream");
				$background_color = imagecolorallocate($im, 240, 240, 240);
				$text_color = imagecolorallocate($im, 40, 40, 40); //233, 14, 91
				$font = DEVBLOCKS_PATH . 'resources/font/ryanlerch_-_Tuffy_Bold(2).ttf';
				imagettftext($im, 24, mt_rand(0,20), 5, 60+6, $text_color, $font, $phrase);
				imagejpeg($im,null,85);
				imagedestroy($im);
				exit;
				break;
			
			case 'captcha.check':
				$entered = DevblocksPlatform::importGPC($_REQUEST['captcha'],'string','');
				$captcha = $umsession->getProperty(UmScApp::SESSION_CAPTCHA, '');
				
				if(!empty($entered) && !empty($captcha) && 0 == strcasecmp($entered, $captcha)) {
					echo 'true';
					exit;
				}
				
				echo 'false';
				exit;
				
				break;
			
	    	default:
				// Build the menu
				$modules = $this->_getModules();
		        $menu_modules = array();
				if(is_array($modules))
				foreach($modules as $uri => $module) {
					// Must be menu renderable
					if(!empty($module->manifest->params['menu_title']) && !empty($uri)) {
						$menu_modules[$uri] = $module;
					}
				}
		        $tpl->assign('menu', $menu_modules);

				// Modules
		        if(isset($modules[$module_uri])) {
					$controller = $modules[$module_uri];
		        } else {
		        	// First menu item
					$controller = reset($menu_modules);
		        }

				array_unshift($stack, $module_uri);
				$tpl->assign('module', $controller);
				$tpl->assign('module_response', new DevblocksHttpResponse($stack));

   				$tpl->display('file:' . $tpl_path . 'portal/sc/module/index.tpl');
		    	break;
		}
	}
	
	/**
	 * @param $instance Model_CommunityTool 
	 */
    public function configure(Model_CommunityTool $instance) {
        $tpl = DevblocksPlatform::getTemplateService();
        $tpl_path = dirname(dirname(__FILE__)) . '/templates/';
        $tpl->assign('config_path', $tpl_path);
        
		// Locales
		
        $default_locale = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(), self::PARAM_DEFAULT_LOCALE, 'en_US');
		$tpl->assign('default_locale', $default_locale);
		
		$locales = DAO_Translation::getDefinedLangCodes();
		$tpl->assign('locales', $locales);

		// Personalization

        $page_title = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(), self::PARAM_PAGE_TITLE, 'Support Center');
		$tpl->assign('page_title', $page_title);

        $style_css = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(), self::PARAM_STYLE_CSS, '');
		$tpl->assign('style_css', $style_css);

        $header_html = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(), self::PARAM_HEADER_HTML, '');
		$tpl->assign('header_html', $header_html);

        $footer_html = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(), self::PARAM_FOOTER_HTML, '');
		$tpl->assign('footer_html', $footer_html);
        
		// Options
		
        $allow_logins = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(), self::PARAM_ALLOW_LOGINS, 0);
		$tpl->assign('allow_logins', $allow_logins);

        $enabled_modules = DevblocksPlatform::parseCsvString(DAO_CommunityToolProperty::get(UmPortalHelper::getCode(), self::PARAM_ENABLED_MODULES, array()));
		$tpl->assign('enabled_modules', $enabled_modules);

		$all_modules = DevblocksPlatform::getExtensions('usermeet.sc.controller', true, true);
		$modules = array();
		
		// Sort the enabled modules first, in order.
		if(is_array($enabled_modules))
		foreach($enabled_modules as $module_id) {
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
		
        $tpl->display("file:${tpl_path}portal/sc/config/index.tpl");
    }
    
    public function saveConfiguration() {
        @$aEnabledModules = DevblocksPlatform::importGPC($_POST['enabled_modules'],'array',array());
        @$aIdxModules = DevblocksPlatform::importGPC($_POST['idx_modules'],'array',array());
        @$aPosModules = DevblocksPlatform::importGPC($_POST['pos_modules'],'array',array());
        @$sPageTitle = DevblocksPlatform::importGPC($_POST['page_title'],'string','Contact Us');

		// Modules (toggle + sort)
		if(is_array($aIdxModules))
		foreach($aIdxModules as $idx => $module_id) {
			if(!in_array($module_id, $aEnabledModules)) {
				unset($aPosModules[$idx]);
			}
		}
			
		// Rearrange modules by sort order
		$aEnabledModules = array();
		asort($aPosModules); // sort enabled by order asc
		foreach($aPosModules as $idx => $null)
			$aEnabledModules[] = $aIdxModules[$idx]; 

        DAO_CommunityToolProperty::set(UmPortalHelper::getCode(), self::PARAM_ENABLED_MODULES, implode(',',$aEnabledModules));
        DAO_CommunityToolProperty::set(UmPortalHelper::getCode(), self::PARAM_PAGE_TITLE, $sPageTitle);

		// Logins
        @$iAllowLogins = DevblocksPlatform::importGPC($_POST['allow_logins'],'integer',0);
        DAO_CommunityToolProperty::set(UmPortalHelper::getCode(), self::PARAM_ALLOW_LOGINS, $iAllowLogins);

		// Default Locale
        @$sDefaultLocale = DevblocksPlatform::importGPC($_POST['default_locale'],'string','en_US');
		DAO_CommunityToolProperty::set(UmPortalHelper::getCode(), self::PARAM_DEFAULT_LOCALE, $sDefaultLocale);

        // Style
        @$sStyleCss = DevblocksPlatform::importGPC($_POST['style_css'],'string','');
        DAO_CommunityToolProperty::set(UmPortalHelper::getCode(), self::PARAM_STYLE_CSS, $sStyleCss);

        // Header
        @$sHeaderHtml = DevblocksPlatform::importGPC($_POST['header_html'],'string','');
        DAO_CommunityToolProperty::set(UmPortalHelper::getCode(), self::PARAM_HEADER_HTML, $sHeaderHtml);

        // Footer
        @$sFooterHtml = DevblocksPlatform::importGPC($_POST['footer_html'],'string','');
        DAO_CommunityToolProperty::set(UmPortalHelper::getCode(), self::PARAM_FOOTER_HTML, $sFooterHtml);
        
		// Allow modules to save their own config
		$modules = DevblocksPlatform::getExtensions('usermeet.sc.controller',true,true);
		foreach($modules as $module) { /* @var $module Extension_UmScController */
			// Only save enabled
			if(!in_array($module->manifest->id, $aEnabledModules))
				continue;
				
			$module->saveConfiguration();
		}

    }
	
	function doLogin() {
		$umsession = UmPortalHelper::getSession();
		
//		if(!$this->allow_logins)
//			die();
		
		@$email = DevblocksPlatform::importGPC($_REQUEST['email']);
		@$pass = DevblocksPlatform::importGPC($_REQUEST['pass']);
		$valid = false;
		
		// [TODO] Test login combination using the appropriate adapter
		if(null != ($addy = DAO_Address::lookupAddress($email, false))) {
			$auth = DAO_AddressAuth::get($addy->id);
			
			if(!empty($auth->pass) && md5($pass)==$auth->pass) {
				$valid = true;
				$umsession->setProperty('sc_login',$addy);
			}
		}
		
		if(!$valid) {
			$umsession->setProperty('sc_login',null);
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode())));
	}
	
	function doLogout() {
		$umsession = UmPortalHelper::getSession();
		$umsession->setProperty('sc_login',null);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode())));
	}
	
};

// [TODO] Redundant w/ C4_AbstractViewLoader
class UmScAbstractViewLoader {
	static $views = null;
	const VISIT_ABSTRACTVIEWS = 'abstractviews_list';

	static protected function _init() {
		$umsession = UmPortalHelper::getSession();
		self::$views = $umsession->getProperty(self::VISIT_ABSTRACTVIEWS,array());
	}

	/**
	 * @param string $view_label Abstract view identifier
	 * @return boolean
	 */
	static function exists($view_label) {
		if(is_null(self::$views)) self::_init();
		return isset(self::$views[$view_label]);
	}

	/**
	 * Enter description here...
	 *
	 * @param string $class C4_AbstractView
	 * @param string $view_label ID
	 * @return C4_AbstractView instance
	 */
	static function getView($class, $view_label) {
		if(is_null(self::$views)) self::_init();

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
	 * Enter description here...
	 *
	 * @param string $class C4_AbstractView
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
		$umsession = UmPortalHelper::getSession();
		$umsession->setProperty(self::VISIT_ABSTRACTVIEWS, self::$views);
	}

	static function serializeAbstractView($view) {
		if(!$view instanceof C4_AbstractView) {
			return null;
		}
		
		$model = new C4_AbstractViewModel();
			
		$model->class_name = get_class($view);

		$model->id = $view->id;
		$model->name = $view->name;
		$model->view_columns = $view->view_columns;
		$model->params = $view->params;

		$model->renderPage = $view->renderPage;
		$model->renderLimit = $view->renderLimit;
		$model->renderSortBy = $view->renderSortBy;
		$model->renderSortAsc = $view->renderSortAsc;

		return $model;
	}

	static function unserializeAbstractView(C4_AbstractViewModel $model) {
		if(!class_exists($model->class_name, true))
			return null;
		
		if(null == ($inst = new $model->class_name))
			return null;

		/* @var $inst C4_AbstractView */
			
		$inst->id = $model->id;
		$inst->name = $model->name;
		$inst->view_columns = $model->view_columns;
		$inst->params = $model->params;

		$inst->renderPage = $model->renderPage;
		$inst->renderLimit = $model->renderLimit;
		$inst->renderSortBy = $model->renderSortBy;
		$inst->renderSortAsc = $model->renderSortAsc;

		return $inst;
	}
};

class UmScRssController extends Extension_UmScController {
	function __construct($manifest=null) {
		parent::__construct($manifest);
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
		
		// [TOOD] subcontroller not found
	}
};

class UmScAjaxController extends Extension_UmScController {
	private $_TPL_PATH = '';
	
	function __construct($manifest=null) {
		$this->_TPL_PATH = dirname(dirname(__FILE__)) . '/templates/';
		parent::__construct($manifest);
		
//		$tpl = DevblocksPlatform::getTemplateService();
//		$tpl->display("file:".$this->_TPL_PATH."portal/sc/internal/views/hello.tpl");
	}
	
	function handleRequest(DevblocksHttpRequest $request) {
		@$path = $request->path;
		@$a = DevblocksPlatform::importGPC($_REQUEST['a'],'string');
	    
		if(empty($a)) {
    	    @$action = array_shift($path) . 'Action';
		} else {
	    	@$action = $a . 'Action';
		}

	    switch($action) {
	        default:
			    // Default action, call arg as a method suffixed with Action
				if(method_exists($this,$action)) {
					call_user_func(array(&$this, $action), new DevblocksHttpRequest($path)); // Pass HttpRequest as arg
				}
	            break;
	    }
	}
	
	function viewRefreshAction(DevblocksHttpRequest $request) {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['id'],'string','');
		
		if(null != ($view = UmScAbstractViewLoader::getView('', $view_id))) {
			$view->render();
		}
	}

	function viewPageAction(DevblocksHttpRequest $request) {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['id'],'string','');
		@$page = DevblocksPlatform::importGPC($_REQUEST['page'],'integer',0);
		
		if(null != ($view = UmScAbstractViewLoader::getView('', $view_id))) {
			$view->renderPage = $page;
			UmScAbstractViewLoader::setView($view->id, $view);
			
			$view->render();
		}
	}
	
	function viewSortByAction(DevblocksHttpRequest $request) {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['id'],'string','');
		@$sort_by = DevblocksPlatform::importGPC($_REQUEST['sort_by'],'string','');
		
		if(null != ($view = UmScAbstractViewLoader::getView('', $view_id))) {
			$fields = $view->getColumns();
			if(isset($fields[$sort_by])) {
				if(0==strcasecmp($view->renderSortBy,$sort_by)) { // clicked same col?
					$view->renderSortAsc = !(bool)$view->renderSortAsc; // flip order
				} else {
					$view->renderSortBy = $sort_by;
					$view->renderSortAsc = true;
				}
				
				$view->renderPage = 0;
				
				UmScAbstractViewLoader::setView($view->id, $view);
			}
			
			$view->render();
		}
		
	}
	
	function downloadFileAction(DevblocksHttpRequest $request) {
		$umsession = UmPortalHelper::getSession();
		$stack = $request->path;
		
        if(null == ($active_user = $umsession->getProperty('sc_login',null)))
			return;

		// Attachment ID + display name
		@$ticket_mask = array_shift($stack);
		@$hash = array_shift($stack);
		@$display_name = array_shift($stack);
		
		if(empty($ticket_mask) || empty($hash) || empty($display_name))
			return;
			
		if(null == ($ticket_id = DAO_Ticket::getTicketIdByMask($ticket_mask)))
			return;
		
		// Load attachments by ticket mask
		list($attachments) = DAO_Attachment::search(
			array(
				SearchFields_Attachment::TICKET_MASK => new DevblocksSearchCriteria(SearchFields_Attachment::TICKET_MASK,'=',$ticket_mask), 
			),
			-1,
			0,
			null,
			null,
			false
		);

		$attachment = null;

		if(is_array($attachments))
		foreach($attachments as $possible_file) {
			// Compare the hash
			$fingerprint = md5($possible_file[SearchFields_Attachment::ID].$possible_file[SearchFields_Attachment::MESSAGE_ID].$possible_file[SearchFields_Attachment::DISPLAY_NAME]);
			if(0 == strcmp($fingerprint,$hash)) {
				if(null == ($attachment = DAO_Attachment::get($possible_file[SearchFields_Attachment::ID])))
					return;
				break;
			}
		}

		// No hit (bad hash)
		if(null == $attachment)
			return;

		// Load requesters		
		if(null == ($requesters = DAO_Ticket::getRequestersByTicket($ticket_id)))
			return;
			
		// Security: Make sure the active user is a requester on the proper ticket
		if(!isset($requesters[$active_user->id]))
			return;
		
		// Set headers
		header("Expires: Mon, 26 Nov 1962 00:00:00 GMT\n");
		header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT\n");
		header("Cache-control: private\n");
		header("Pragma: no-cache\n");
		header("Content-Type: " . $attachment->mime_type . "\n");
		header("Content-transfer-encoding: binary\n"); 
		header("Content-Length: " . $attachment->getFileSize() . "\n");
		
		// Dump contents
		echo $attachment->getFileContents();
		exit;
	}

	
};