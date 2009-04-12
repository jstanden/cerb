<?php
class UmScApp extends Extension_UsermeetTool {
	const PARAM_LOGO_URL = 'common.logo_url';
	const PARAM_PAGE_TITLE = 'common.page_title';
	const PARAM_STYLE_CSS = 'common.style_css';
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
	        $enabled_modules = DevblocksPlatform::parseCsvString(DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_ENABLED_MODULES, ''));
			
			if(is_array($enabled_modules))
			foreach($enabled_modules as $module_id) {
				$module = DevblocksPlatform::getExtension($module_id,true,true); /* @var $module Extension_UmScController */
				@$module_uri = $module->manifest->params['uri'];
				$module->setPortal($this->getPortal());
	
				if($module->isVisible())
					$modules[$module_uri] = $module;
			}
    	}
		
    	return $modules;
    }
    
    public function handleRequest(DevblocksHttpRequest $request) {
    	$stack = $request->path;
        $module_uri = array_shift($stack);
        
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
        $umsession = $this->getSession();
		$stack = $response->path;
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('tpl_path', $tpl_path);
		
		$logo_url = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_LOGO_URL, '');
		$tpl->assign('logo_url', $logo_url);
        
		$page_title = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_PAGE_TITLE, 'Support Center');
		$tpl->assign('page_title', $page_title);
        
        $style_css = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_STYLE_CSS, '');
		$tpl->assign('style_css', $style_css);

        $footer_html = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_FOOTER_HTML, '');
		$tpl->assign('footer_html', $footer_html);
		
        $allow_logins = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_ALLOW_LOGINS, 0);
		$tpl->assign('allow_logins', $allow_logins);
		
        $enabled_modules = DevblocksPlatform::parseCsvString(DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_ENABLED_MODULES, ''));
		$tpl->assign('enabled_modules', $enabled_modules);
		
        @$active_user = $umsession->getProperty('sc_login',null);
        $tpl->assign('active_user', $active_user);

		// Usermeet Session
		if(null == ($fingerprint = parent::getFingerprint())) {
			die("A problem occurred.");
		}
        $tpl->assign('fingerprint', $fingerprint);

		// Build the menu
		$modules = $this->_getModules();
        $menu_modules = array();
		if(is_array($modules))
		foreach($modules as $module_uri => $module) {
			// Must be menu renderable
			if(!empty($module->manifest->params['menu_title']) && !empty($module_uri)) {
				$menu_modules[$module_uri] = $module;
			}
		}
        $tpl->assign('menu', $menu_modules);
		
        $module_uri = array_shift($stack);
        if(isset($modules[$module_uri])) {
			$controller = $modules[$module_uri];
        } else {
        	// First menu item
			$controller = reset($menu_modules);
        }
		
		switch($module_uri) {
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
			
	    	default:
				array_unshift($stack, $module_uri);
				$tpl->assign('module', $controller);
				$tpl->assign('module_response', new DevblocksHttpResponse($stack));
				
   				$tpl->display('file:' . $tpl_path . 'portal/sc/internal/index.tpl');
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
        
        $logo_url = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_LOGO_URL, '');
		$tpl->assign('logo_url', $logo_url);
        
        $page_title = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_PAGE_TITLE, 'Support Center');
		$tpl->assign('page_title', $page_title);
        
        $style_css = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_STYLE_CSS, '');
		$tpl->assign('style_css', $style_css);

        $footer_html = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_FOOTER_HTML, '');
		$tpl->assign('footer_html', $footer_html);
        
        $allow_logins = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_ALLOW_LOGINS, 0);
		$tpl->assign('allow_logins', $allow_logins);

        $enabled_modules = DevblocksPlatform::parseCsvString(DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_ENABLED_MODULES, array()));
		$tpl->assign('enabled_modules', $enabled_modules);

		$all_modules = DevblocksPlatform::getExtensions('usermeet.sc.controller', true, true);
		$modules = array();
		
		// Sort the enabled modules first, in order.
		if(is_array($enabled_modules))
		foreach($enabled_modules as $module_id) {
			if(!isset($all_modules[$module_id]))
				continue;
			$module = $all_modules[$module_id];
			$module->setPortal($this->getPortal());
			$modules[$module_id] = $module;
			unset($all_modules[$module_id]);
		}
		
		// Append the unused modules
		if(is_array($all_modules))
		foreach($all_modules as $module_id => $module) {
			$module->setPortal($this->getPortal());
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
        @$sLogoUrl = DevblocksPlatform::importGPC($_POST['logo_url'],'string','');
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

        DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_ENABLED_MODULES, implode(',',$aEnabledModules));
        DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_LOGO_URL, $sLogoUrl);
        DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_PAGE_TITLE, $sPageTitle);

		// Logins
        @$iAllowLogins = DevblocksPlatform::importGPC($_POST['allow_logins'],'integer',0);
        DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_ALLOW_LOGINS, $iAllowLogins);

        // Style
        @$sStyleCss = DevblocksPlatform::importGPC($_POST['style_css'],'string','');
        DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_STYLE_CSS, $sStyleCss);

        // Footer
        @$sFooterHtml = DevblocksPlatform::importGPC($_POST['footer_html'],'string','');
        DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_FOOTER_HTML, $sFooterHtml);
        
		// Allow modules to save their own config
		$modules = DevblocksPlatform::getExtensions('usermeet.sc.controller',true,true);
		foreach($modules as $module) { /* @var $module Extension_UmScController */
			// Only save enabled
			if(!in_array($module->manifest->id, $aEnabledModules))
				continue;
				
			$module->setPortal($this->getPortal());
			$module->saveConfiguration();
		}

    }
	
	function doLogin() {
		$umsession = $this->getSession();
		
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
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal())));
	}
	
	function doLogout() {
		$umsession = $this->getSession();
		$umsession->setProperty('sc_login',null);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal())));
	}
	
};

