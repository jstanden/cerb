<?php
// Classes
$path = realpath(dirname(__FILE__).'/../') . DIRECTORY_SEPARATOR;

DevblocksPlatform::registerClasses($path. 'api/Extension.php', array(
    'Extension_UmScController'
));

class UmScApp extends Extension_UsermeetTool {
	const PARAM_BASE_URL = 'base_url';
	const PARAM_LOGO_URL = 'logo_url';
	const PARAM_THEME = 'theme';
//	const PARAM_THEME_URL = 'theme_url';
	const PARAM_PAGE_TITLE = 'page_title';
	const PARAM_FOOTER_HTML = 'footer_html';
	const PARAM_CAPTCHA_ENABLED = 'captcha_enabled';
	const PARAM_DISPATCH = 'dispatch';
	const PARAM_HOME_RSS = 'home_rss';
//	const PARAM_KB_TOPICS = 'kb_topics';
	const PARAM_KB_ENABLED = 'kb_enabled';
	const PARAM_KB_ROOTS = 'kb_roots';
	const PARAM_FNR_SOURCES = 'fnr_sources';
	const PARAM_ALLOW_LOGINS = 'allow_logins';
	const PARAM_ALLOW_SUBJECTS = 'allow_subjects';
	
	const DEFAULT_THEME = 'classic_green';
	
	const SESSION_CAPTCHA = 'write_captcha';
	const SESSION_ARTICLE_LIST = 'kb_article_list';	
	
	private $default_controller = 'sc.controller.core';
	private $modules = array();

    function __construct($manifest) {
        parent::__construct($manifest);
//        $filepath = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
		
		// [TODO] Load sub-controller plugins
		$module_manifests = DevblocksPlatform::getExtensions('usermeet.sc.controller', false);
		foreach($module_manifests as $module_manifest) { /* @var $module_manifest DevblocksExtensionManifest */
			if(null != ($mods = $module_manifest->params['modules'])) {
				foreach($mods as $mod) {
					$mod['extension_id'] = $module_manifest->id;
					$this->modules[$mod['uri']] = $mod;
				}
			}
		}
    }
    
    public function handleRequest(DevblocksHttpRequest $request) {
    	$stack = $request->path;
        $module_uri = array_shift($stack);
        
        if(isset($this->modules[$module_uri])) {
        	$mf = DevblocksPlatform::getExtension($this->modules[$module_uri]['extension_id']);
        } else {
        	$mf = DevblocksPlatform::getExtension($this->default_controller);
        }
        
        array_unshift($stack, $module_uri);
		$controller = $mf->createInstance(); /* @var $controller Extension_UmScController */
		$controller->setPortal($this->getPortal());
		$controller->handleRequest(new DevblocksHttpRequest($stack));
    }
    
	public function writeResponse(DevblocksHttpResponse $response) {
        $umsession = $this->getSession();
		$stack = $response->path;

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = realpath(dirname(__FILE__).'/../') . '/templates/';
		$tpl->assign('tpl_path', $tpl_path);
		
		$logo_url = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_LOGO_URL, '');
		$tpl->assign('logo_url', $logo_url);
        
		$page_title = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_PAGE_TITLE, 'Support Center');
		$tpl->assign('page_title', $page_title);
        
        $footer_html = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_FOOTER_HTML, '');
		$tpl->assign('footer_html', $footer_html);
		
        $captcha_enabled = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_CAPTCHA_ENABLED, 1);
		$tpl->assign('captcha_enabled', $captcha_enabled);

        $allow_logins = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_ALLOW_LOGINS, 0);
		$tpl->assign('allow_logins', $allow_logins);
		
        $allow_subjects = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_ALLOW_SUBJECTS, 0);
		$tpl->assign('allow_subjects', $allow_subjects);

		$sFnrSources = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_FNR_SOURCES, '');
		$fnr_sources = !empty($sFnrSources) ? unserialize($sFnrSources) : array();
		
		$iKbEnabled = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_KB_ENABLED, 0);
		
		$tpl->assign('show_search', (!empty($fnr_sources) || $iKbEnabled) ? true : false);
		
        $theme = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_THEME, self::DEFAULT_THEME);
        if(!is_dir($tpl_path . 'portal/sc/themes/'.$theme))
        	$theme = self::DEFAULT_THEME;
		$tpl->assign('theme', $theme);
		
        @$active_user = $umsession->getProperty('sc_login',null);
        $tpl->assign('active_user', $active_user);

		// Usermeet Session
		if(null == ($fingerprint = parent::getFingerprint())) {
			die("A problem occurred.");
		}
        $tpl->assign('fingerprint', $fingerprint);

        $module_uri = array_shift($stack);
        
        if(isset($this->modules[$module_uri])) {
        	$mf = DevblocksPlatform::getExtension($this->modules[$module_uri]['extension_id']);
        } else {
        	$mf = DevblocksPlatform::getExtension($this->default_controller);
        }

        $menu_modules = array();
        
		if(is_array($this->modules))
		foreach($this->modules as $idx => $item) {
			// Must be menu renderable
			if(!empty($item['menu_title']) && !empty($item['uri'])) {
				// Must not require login, or we must be logged in
				// [TODO] Check if the module wants to render (KB not empty, etc.)
				if(!isset($item['requires_login']) 
					|| (isset($item['requires_login']) && !empty($active_user))) {
						
						// Skip KB in menu if no topics are set
						// [TODO] Hack (shouldn't be hardcoded in menu logic)
						if(0==strcasecmp($item['uri'],'kb') && !$iKbEnabled)
							continue;
						
						$menu_modules[$idx] = $item;
				}
			}			
		}
        $tpl->assign('menu', $menu_modules);
        
        array_unshift($stack, $module_uri);
        $controller = $mf->createInstance();
        $controller->setPortal($this->getPortal());
		$tpl->assign('module', $controller);
		$tpl->assign('module_response', new DevblocksHttpResponse($stack));
        
		switch($module_uri) {
			case 'captcha':
                header('Cache-control: max-age=0', true); // 1 wk // , must-revalidate
                header('Expires: ' . gmdate('D, d M Y H:i:s',time()-604800) . ' GMT'); // 1 wk
				header('Content-type: image/jpeg');
                //header('Content-length: '. count($jpg));

//		        // Get CAPTCHA secret passphrase
				$phrase = CerberusApplication::generatePassword(4);
		        $umsession->setProperty(UmScApp::SESSION_CAPTCHA, $phrase);
                
				$im = @imagecreate(150, 70) or die("Cannot Initialize new GD image stream");
				$background_color = imagecolorallocate($im, 240, 240, 240);
				$text_color = imagecolorallocate($im, 40, 40, 40); //233, 14, 91
				$font = DEVBLOCKS_PATH . 'resources/font/ryanlerch_-_Tuffy_Bold(2).ttf';
				imagettftext($im, 24, rand(0,20), 5, 60+6, $text_color, $font, $phrase);
//				$im = imagerotate($im, rand(-20,20), $background_color);
				imagejpeg($im,null,85);
				imagedestroy($im);
				exit;
				break;
			
	    	default:
	    		// Look up the current module
   				$tpl->display('file:' . $tpl_path . 'portal/sc/themes/'.$theme.'/index.tpl.php');
		    	break;
		}
	}
	
	/**
	 * @param $instance Model_CommunityTool 
	 */
    public function configure(Model_CommunityTool $instance) {
        $tpl = DevblocksPlatform::getTemplateService();
        $tpl_path = realpath(dirname(__FILE__).'/../') . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;
        $tpl->assign('config_path', $tpl_path);
        
        $settings = CerberusSettings::getInstance();
        
        $default_from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM);
        $tpl->assign('default_from', $default_from);
        
        $sDispatch = DAO_CommunityToolProperty::get($this->getPortal(),self::PARAM_DISPATCH, '');
        $dispatch = !empty($sDispatch) ? unserialize($sDispatch) : array();
        $tpl->assign('dispatch', $dispatch);
        
        $sHomeRss = DAO_CommunityToolProperty::get($this->getPortal(),self::PARAM_HOME_RSS, '');
        $home_rss = !empty($sHomeRss) ? unserialize($sHomeRss) : array();
        $tpl->assign('home_rss', $home_rss);
        
        $base_url = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_BASE_URL, '');
		$tpl->assign('base_url', $base_url);
        
        $logo_url = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_LOGO_URL, '');
		$tpl->assign('logo_url', $logo_url);
        
        $page_title = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_PAGE_TITLE, 'Support Center');
		$tpl->assign('page_title', $page_title);
        
        $footer_html = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_FOOTER_HTML, '');
		$tpl->assign('footer_html', $footer_html);
        
        $theme = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_THEME, self::DEFAULT_THEME);
        if(!is_dir($tpl_path . 'portal/sc/themes/'.$theme))
        	$theme = self::DEFAULT_THEME;
        $tpl->assign('theme', $theme);
        
        $captcha_enabled = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_CAPTCHA_ENABLED, 1);
		$tpl->assign('captcha_enabled', $captcha_enabled);

        $allow_logins = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_ALLOW_LOGINS, 0);
		$tpl->assign('allow_logins', $allow_logins);

        $allow_subjects = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_ALLOW_SUBJECTS, 0);
		$tpl->assign('allow_subjects', $allow_subjects);

		// Knowledgebase
		$tree_map = DAO_KbCategory::getTreeMap();
		$tpl->assign('tree_map', $tree_map);
		
		$levels = DAO_KbCategory::getTree(0);
		$tpl->assign('levels', $levels);
		
		$categories = DAO_KbCategory::getWhere();
		$tpl->assign('categories', $categories);
		
		$sKbRoots = DAO_CommunityToolProperty::get($this->getPortal(),self::PARAM_KB_ROOTS, '');
        $kb_roots = !empty($sKbRoots) ? unserialize($sKbRoots) : array();
        $tpl->assign('kb_roots', $kb_roots);
		
//		$kb_topics = DAO_KbTopic::getWhere();
//		$tpl->assign('kb_topics', $kb_topics);
//		
//		$sKbTopics = DAO_CommunityToolProperty::get($this->getPortal(),self::PARAM_KB_TOPICS, '');
//        $kb_topics_enabled = !empty($sKbTopics) ? unserialize($sKbTopics) : array();
//        $tpl->assign('kb_topics_enabled', $kb_topics_enabled);
		
		// F&R
		$fnr_topics = DAO_FnrTopic::getWhere();
		$tpl->assign('topics', $fnr_topics);

		$sFnrSources = DAO_CommunityToolProperty::get($this->getPortal(),self::PARAM_FNR_SOURCES, '');
        $fnr_sources = !empty($sFnrSources) ? unserialize($sFnrSources) : array();
        $tpl->assign('fnr_sources', $fnr_sources);
		
		// Themes
		$themes = array();
		if(false !== ($dir = opendir($tpl_path . 'portal/sc/themes'))) {
			while($file = readdir($dir)) {
				if(is_dir($tpl_path.'portal/sc/themes/'.$file) && substr($file,0,1) != '.') {
					$themes[] = $file;
				}
			}
			@closedir($dir);
		}
		$tpl->assign('themes', $themes);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
		
		// Contact: Fields
		$ticket_fields = DAO_TicketField::getWhere();
		$tpl->assign('ticket_fields', $ticket_fields);
		
        $tpl->display("file:${tpl_path}portal/sc/config/index.tpl.php");
    }
    
    public function saveConfiguration() {
        @$sBaseUrl = DevblocksPlatform::importGPC($_POST['base_url'],'string','');
        @$sLogoUrl = DevblocksPlatform::importGPC($_POST['logo_url'],'string','');
        @$sPageTitle = DevblocksPlatform::importGPC($_POST['page_title'],'string','Contact Us');
        @$sTheme = DevblocksPlatform::importGPC($_POST['theme'],'string',UmScApp::DEFAULT_THEME);
        @$iCaptcha = DevblocksPlatform::importGPC($_POST['captcha_enabled'],'integer',1);
        @$iAllowLogins = DevblocksPlatform::importGPC($_POST['allow_logins'],'integer',0);
        @$iAllowSubjects = DevblocksPlatform::importGPC($_POST['allow_subjects'],'integer',0);

        DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_BASE_URL, $sBaseUrl);
        DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_LOGO_URL, $sLogoUrl);
        DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_PAGE_TITLE, $sPageTitle);
        DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_THEME, $sTheme);
        DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_CAPTCHA_ENABLED, $iCaptcha);
        DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_ALLOW_LOGINS, $iAllowLogins);
        DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_ALLOW_SUBJECTS, $iAllowSubjects);

        // Home RSS Feeds
        @$aHomeRssTitles = DevblocksPlatform::importGPC($_POST['home_rss_title'],'array',array());
        @$aHomeRssUrls = DevblocksPlatform::importGPC($_POST['home_rss_url'],'array',array());
        
        $aHomeRss = array();
        
        foreach($aHomeRssUrls as $idx => $rss) {
        	if(empty($rss)) {
        		unset($aHomeRss[$idx]);
        		continue;
        	}
        	$aHomeRss[$aHomeRssTitles[$idx]] = $rss;
        }
        
		DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_HOME_RSS, serialize($aHomeRss));
        
        // Footer
        @$sFooterHtml = DevblocksPlatform::importGPC($_POST['footer_html'],'string','');
        DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_FOOTER_HTML, $sFooterHtml);
        
        // KB
        @$aKbRoots = DevblocksPlatform::importGPC($_POST['category_ids'],'array',array());
        $aKbRoots = array_flip($aKbRoots);
		DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_KB_ROOTS, serialize($aKbRoots));
		DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_KB_ENABLED,!empty($aKbRoots)?1:0);
//        @$aKbTopics = DevblocksPlatform::importGPC($_POST['kb_topic_ids'],'array',array());
//        $aKbTopics = array_flip($aKbTopics);
//		DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_KB_TOPICS, serialize($aKbTopics));
        
        // F&R
        @$aFnrSources = DevblocksPlatform::importGPC($_POST['fnr_sources'],'array',array());
        $aFnrSources = array_flip($aFnrSources);
		DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_FNR_SOURCES, serialize($aFnrSources));

		// Contact Form
        $settings = CerberusSettings::getInstance();
        $default_from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM);
        
    	@$arDeleteSituations = DevblocksPlatform::importGPC($_POST['delete_situations'],'array',array());
        
    	@$sEditReason = DevblocksPlatform::importGPC($_POST['edit_reason'],'string','');
    	@$sReason = DevblocksPlatform::importGPC($_POST['reason'],'string','');
        @$sTo = DevblocksPlatform::importGPC($_POST['to'],'string','');
        @$aFollowup = DevblocksPlatform::importGPC($_POST['followup'],'array',array());
        @$aFollowupField = DevblocksPlatform::importGPC($_POST['followup_fields'],'array',array());
        
        if(empty($sTo))
        	$sTo = $default_from;
        
        $sDispatch = DAO_CommunityToolProperty::get($this->getPortal(),self::PARAM_DISPATCH, '');
        $dispatch = !empty($sDispatch) ? unserialize($sDispatch) : array();

        // [JAS]: [TODO] Only needed temporarily to clean up imports
		// [TODO] Move to patch
        if(is_array($dispatch))
        foreach($dispatch as $d_reason => $d_params) {
        	if(!is_array($d_params)) {
        		$dispatch[$d_reason] = array('to'=>$d_params,'followups'=>array());
        	} else {
        		unset($d_params['']);
        	}
        }

        // Nuke a record we're replacing or any checked boxes
		// will be MD5
        foreach($dispatch as $d_reason => $d_params) {
        	if(!empty($sEditReason) && md5($d_reason)==$sEditReason) {
        		unset($dispatch[$d_reason]);
        	} elseif(!empty($arDeleteSituations) && false !== array_search(md5($d_reason),$arDeleteSituations)) {
        		unset($dispatch[$d_reason]);
        	}
        }
        
       	// If we have new data, add it
        if(!empty($sReason) && !empty($sTo) && false === array_search(md5($sReason),$arDeleteSituations)) {
			$dispatch[$sReason] = array(
				'to' => $sTo,
				'followups' => array()
			);
			
			$followups =& $dispatch[$sReason]['followups'];
			
			if(!empty($aFollowup))
			foreach($aFollowup as $idx => $followup) {
				if(empty($followup)) continue;
//				$followups[$followup] = (false !== array_search($idx,$aFollowupLong)) ? 1 : 0;
				$followups[$followup] = @$aFollowupField[$idx];
			}
        }
        
        ksort($dispatch);
        
		DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_DISPATCH, serialize($dispatch));
    }
    
    // Ajax
    public function getSituation() {
		@$sCode = DevblocksPlatform::importGPC($_REQUEST['portal'],'string','');
		@$sReason = DevblocksPlatform::importGPC($_REQUEST['reason'],'string','');
    	 
    	$tool = DAO_CommunityTool::getByCode($sCode);
    	 
        $tpl = DevblocksPlatform::getTemplateService();
        $tpl_path = realpath(dirname(__FILE__).'/../') . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;

        $settings = CerberusSettings::getInstance();
        
        $default_from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM);
        $tpl->assign('default_from', $default_from);
        
        $sDispatch = DAO_CommunityToolProperty::get($this->getPortal(),self::PARAM_DISPATCH, '');
        $dispatch = !empty($sDispatch) ? unserialize($sDispatch) : array();
        
        if(is_array($dispatch))
        foreach($dispatch as $reason => $params) {
        	if(md5($reason)==$sReason) {
        		$tpl->assign('situation_reason', $reason);
        		$tpl->assign('situation_params', $params);
        		break;
        	}
        }
        
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);
        
		// Contact: Fields
		$ticket_fields = DAO_TicketField::getWhere();
		$tpl->assign('ticket_fields', $ticket_fields);
        
        $tpl->display("file:${tpl_path}portal/sc/config/add_situation.tpl.php");
		exit;
    }
    
    public function doLoginAction() {
		@$editor_email = DevblocksPlatform::importGPC($_REQUEST['editor_email'],'string','');
		@$editor_pass = DevblocksPlatform::importGPC($_REQUEST['editor_pass'],'string','');
    	
        $sEditors = DAO_CommunityToolProperty::get($this->getPortal(),self::PARAM_EDITORS, '');
        $editors = !empty($sEditors) ? unserialize($sEditors) : array();
		
        @$editor =& $editors[$editor_email]; 
		if(!empty($editor) && $editor['password']==md5($editor_pass)) {
			$session = $this->getSession(); /* @var $session Model_CommunitySession */
			$session->setProperty(self::SESSION_EDITOR, $editor_email);
			
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal())));
		} else {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'login')));
		}		
    }
    
    public function doLogoutAction() {
    	$umsession = $this->getSession();
    	$umsession->setProperty(self::SESSION_EDITOR, null);
    	
    	DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal())));
    }
};

class UmScCoreController extends Extension_UmScController {
	private $allow_logins = 0;
	
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
    function setPortal($code) {
    	parent::setPortal($code);
		$this->allow_logins = DAO_CommunityToolProperty::get($this->getPortal(), UmScApp::PARAM_ALLOW_LOGINS, 0);
    }
	
	function doLoginAction() {
		$umsession = $this->getSession();
		
		if(!$this->allow_logins)
			die();
		
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
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'home')));
	}
	
	function doLogoutAction() {
		$umsession = $this->getSession();
		$umsession->setProperty('sc_login',null);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'home')));
	}

	function doSearchAction() {
		@$query = DevblocksPlatform::importGPC($_REQUEST['query'],'string','');
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'answers',rawurlencode($query))));
	}

	function doForgotAction() {
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');

		if(!$this->allow_logins)
			die();
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		$settings = CerberusSettings::getInstance();
		$from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM,null);
		$from_personal = $settings->get(CerberusSettings::DEFAULT_REPLY_PERSONAL,"Support Dept.");
		
		$url = DevblocksPlatform::getUrlService();
		try {
			$mail_service = DevblocksPlatform::getMailService();
			$mailer = $mail_service->getMailer();
			
			$code = CerberusApplication::generatePassword(8);
			
			if(!empty($email) && null != ($addy = DAO_Address::lookupAddress($email, false))) {
				$fields = array(
					DAO_AddressAuth::CONFIRM => $code
				);
				DAO_AddressAuth::update($addy->id, $fields);
				
			} else {
				$tpl->assign('register_error', sprintf("'%s' is not a registered e-mail address.",$email));
				DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'register','forgot')));
				return;
			}
			
			$message = $mail_service->createMessage();
			$message->setTo($email);
			$send_from = new Swift_Address($from, $from_personal);
			$message->setFrom($send_from);
			$message->setSubject("Did you forget your support password?");
			$message->setBody(sprintf("This is a message to confirm your 'forgot password' request at:\r\n".
				"%s\r\n".
				"\r\n".
				"Your confirmation code is: %s\r\n".
				"\r\n".
				"If you've closed the browser window, you can continue by visiting:\r\n".
				"%s\r\n".
				"\r\n".
				"Thanks!\r\n".
				"%s\r\n",
				$url->write('',true),
				$code,
				$url->write('c=register&a=forgot2',true),
				$from_personal
			));
			$message->headers->set('X-Mailer','Cerberus Helpdesk (Build '.APP_BUILD.')');
			
			$mailer->send($message,$email,$send_from);
		}
		catch (Exception $e) {
			$tpl->assign('register_error', 'Fatal error encountered while sending forgot password confirmation code.');
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'register','forgot')));
			return;
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'register','forgot2')));
	}		
	
	function doForgotConfirmAction() {
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
		@$code = trim(DevblocksPlatform::importGPC($_REQUEST['code'],'string',''));
		@$pass = DevblocksPlatform::importGPC($_REQUEST['pass'],'string','');
		
		if(!$this->allow_logins)
			die();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('register_email', $email);
		$tpl->assign('register_code', $code);
		
		if(!empty($email) && !empty($pass) && !empty($code)) {
			if(null != ($addy = DAO_Address::lookupAddress($email, false))
				&& null != ($auth = DAO_AddressAuth::get($addy->id))
				&& !empty($auth) 
				&& !empty($auth->confirm) 
				&& 0 == strcasecmp($code,$auth->confirm)) {
					$fields = array(
						DAO_AddressAuth::PASS => md5($pass)
					);
					DAO_AddressAuth::update($addy->id, $fields);
				
			} else {
				$tpl->assign('register_error', sprintf("The confirmation code you entered does not match our records.  Try again."));
				DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'register','forgot2')));
				return;
			}
			
		} else {
			$tpl->assign('register_error', sprintf("You must enter a valid e-mail address, confirmation code and desired password to continue."));
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'register','forgot2')));
			return;
		}
	}
		
	function doRegisterAction() {
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');

		if(!$this->allow_logins)
			die();
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		$settings = CerberusSettings::getInstance();
		$from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM,null);
		$from_personal = $settings->get(CerberusSettings::DEFAULT_REPLY_PERSONAL,"Support Dept.");
		
		$url = DevblocksPlatform::getUrlService();
		$mail_service = DevblocksPlatform::getMailService();
		$mailer = $mail_service->getMailer();
		
		$code = CerberusApplication::generatePassword(8);
		
		if(!empty($email) && null != ($addy = DAO_Address::lookupAddress($email, true))) {
			$auth = DAO_AddressAuth::get($addy->id);
			
			// Already registered?
			if(!empty($auth) && !empty($auth->pass)) {
				$tpl->assign('register_error', sprintf("'%s' is already registered.",$email));
				DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'register')));
				return;
			}
			
			$fields = array(
				DAO_AddressAuth::CONFIRM => $code
			);
			DAO_AddressAuth::update($addy->id, $fields);
			
		} else {
			$tpl->assign('register_error', sprintf("'%s' is an invalid e-mail address.",$email));
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'register')));
			return;
		}
		
		$message = $mail_service->createMessage();
		$message->setTo($email);
		$send_from = new Swift_Address($from, $from_personal);
		$message->setFrom($send_from);
		$message->setSubject("Confirming your support e-mail address");
		$message->setBody(sprintf("This is a message to confirm your recent registration request at:\r\n".
			"%s\r\n".
			"\r\n".
			"Your confirmation code is: %s\r\n".
			"\r\n".
			"If you've closed the browser window, you can continue by visiting:\r\n".
			"%s\r\n".
			"\r\n".
			"Thanks!\r\n".
			"%s\r\n",
			$url->write('',true),
			$code,
			$url->write('c=register&a=confirm',true),
			$from_personal
		));
		$message->headers->set('X-Mailer','Cerberus Helpdesk (Build '.APP_BUILD.')');
		
		$mailer->send($message,$email,$send_from);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'register','confirm')));
	}
	
	function doRegisterConfirmAction() {
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
		@$code = trim(DevblocksPlatform::importGPC($_REQUEST['code'],'string',''));
		@$pass = DevblocksPlatform::importGPC($_REQUEST['pass'],'string','');
		
		if(!$this->allow_logins)
			die();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('register_email', $email);
		$tpl->assign('register_code', $code);
		
		if(!empty($email) && !empty($pass) && !empty($code)) {
			if(null != ($addy = DAO_Address::lookupAddress($email, false))
				&& null != ($auth = DAO_AddressAuth::get($addy->id))
				&& !empty($auth) 
				&& !empty($auth->confirm) 
				&& 0 == strcasecmp($code,$auth->confirm)) {
					$fields = array(
						DAO_AddressAuth::PASS => md5($pass)
					);
					DAO_AddressAuth::update($addy->id, $fields);
				
			} else {
				$tpl->assign('register_error', sprintf("The confirmation code you entered does not match our records.  Try again."));
				DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'register','confirm')));
				return;
			}
			
		} else {
			$tpl->assign('register_error', sprintf("You must enter a valid e-mail address, confirmation code and desired password to continue."));
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'register','confirm')));
			return;
		}
	}
	
	function saveAccountAction() {
		@$first_name = DevblocksPlatform::importGPC($_REQUEST['first_name'],'string','');
		@$last_name = DevblocksPlatform::importGPC($_REQUEST['last_name'],'string','');
		@$change_password = DevblocksPlatform::importGPC($_REQUEST['change_password'],'string','');
		@$change_password2 = DevblocksPlatform::importGPC($_REQUEST['change_password2'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$umsession = $this->getSession();
		$active_user = $umsession->getProperty('sc_login', null);
		
		if(!$this->allow_logins || empty($active_user))
			die();
		
		if(!empty($active_user)) {
			$fields = array(
				DAO_Address::FIRST_NAME => $first_name,
				DAO_Address::LAST_NAME => $last_name
			);
			DAO_Address::update($active_user->id, $fields);
			$tpl->assign('account_success', true);
			
			if(!empty($change_password)) {
				if(0 == strcmp($change_password,$change_password2)) {
					DAO_AddressAuth::update(
						$active_user->id,
						array(
							DAO_AddressAuth::PASS => md5($change_password)
						)
					);
				} else {
					$tpl->assign('account_error', "The passwords you entered did not match.");
				}
			}
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'account')));
	}
	
	function saveTicketPropertiesAction() {
		@$mask = DevblocksPlatform::importGPC($_REQUEST['mask'],'string','');
		@$closed = DevblocksPlatform::importGPC($_REQUEST['closed'],'integer','0');
		
		$umsession = $this->getSession();
		$active_user = $umsession->getProperty('sc_login', null);

		if(!$this->allow_logins || empty($active_user))
			die();
		
		// Secure retrieval (address + mask)
		list($tickets) = DAO_Ticket::search(
			array(),
			array(
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MASK,'=',$mask),
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE_ID,'=',$active_user->id),
			),
			1,
			0,
			null,
			null,
			false
		);
		$ticket = array_shift($tickets);
		$ticket_id = $ticket[SearchFields_Ticket::TICKET_ID];

		$fields = array(
			DAO_Ticket::IS_CLOSED => ($closed) ? 1 : 0
		);
		DAO_Ticket::updateTicket($ticket_id,$fields);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'history',$ticket[SearchFields_Ticket::TICKET_MASK])));		
	}
	
	function doReplyAction() {
		@$mask = DevblocksPlatform::importGPC($_REQUEST['mask'],'string','');
		@$content = DevblocksPlatform::importGPC($_REQUEST['content'],'string','');
		
		$umsession = $this->getSession();
		$active_user = $umsession->getProperty('sc_login', null);

		if(!$this->allow_logins || empty($active_user))
			die();

		// Secure retrieval (address + mask)
		list($tickets) = DAO_Ticket::search(
			array(),
			array(
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MASK,'=',$mask),
				new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE_ID,'=',$active_user->id),
			),
			1,
			0,
			null,
			null,
			false
		);
		$ticket = array_shift($tickets);
		
		$messages = DAO_Ticket::getMessagesByTicket($ticket[SearchFields_Ticket::TICKET_ID]);
		$last_message = array_pop($messages); /* @var $last_message CerberusMessage */
		$last_message_headers = $last_message->getHeaders();
		unset($messages);

		// Helpdesk settings
		$settings = CerberusSettings::getInstance();
		$global_from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM,null);
		
		// Ticket group settings
		$group_id = $ticket[SearchFields_Ticket::TEAM_ID];
		$group_settings = DAO_GroupSettings::getSettings($group_id);
		@$group_from = $group_settings[DAO_GroupSettings::SETTING_REPLY_FROM];
		
		// Headers
		$to = !empty($group_from) ? $group_from : $global_from;
		@$in_reply_to = $last_message_headers['message-id'];
		@$message_id = CerberusApplication::generateMessageId();
		
		$message = new CerberusParserMessage();
		$message->headers['from'] = $active_user->email;
		$message->headers['to'] = $to;
		$message->headers['date'] = gmdate('r');
		$message->headers['subject'] = 'Re: ' . $ticket[SearchFields_Ticket::TICKET_SUBJECT];
		$message->headers['message-id'] = $message_id;
		$message->headers['in-reply-to'] = $in_reply_to;
		
		$message->body = sprintf(
			"%s",
			$content
		);
   
		CerberusParser::parseMessage($message,array('no_autoreply'=>true));
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'history',$ticket[SearchFields_Ticket::TICKET_MASK])));
	}
	
	function doContactStep2Action() {
		$umsession = $this->getSession();
		$fingerprint = parent::getFingerprint();
		
		@$sNature = DevblocksPlatform::importGPC($_POST['nature'],'string','');

		$umsession->setProperty('support.write.last_nature', $sNature);
		$umsession->setProperty('support.write.last_subject', null);
		$umsession->setProperty('support.write.last_content', null);
		$umsession->setProperty('support.write.last_error', null);
		$umsession->setProperty('support.write.last_followup_a', null);
		
		$sDispatch = DAO_CommunityToolProperty::get($this->getPortal(),UmScApp::PARAM_DISPATCH, '');
		$dispatch = !empty($sDispatch) ? unserialize($sDispatch) : array();
		
		// Check if this nature has followups, if not skip to send
		$followups = array();
		if(is_array($dispatch))
        foreach($dispatch as $k => $v) {
        	if(md5($k)==$sNature) {
        		$umsession->setProperty('support.write.last_nature_string', $k);
        		@$followups = $v['followups'];
        		break;
        	}
        }
        
        DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'contact','step2')));
	}
	
	function doContactSendAction() {
		@$sFrom = DevblocksPlatform::importGPC($_POST['from'],'string','');
		@$sSubject = DevblocksPlatform::importGPC($_POST['subject'],'string','');
		@$sContent = DevblocksPlatform::importGPC($_POST['content'],'string','');
		@$sCaptcha = DevblocksPlatform::importGPC($_POST['captcha'],'string','');
		
		@$aFieldIds = DevblocksPlatform::importGPC($_POST['field_ids'],'array',array());
		@$aFollowUpQ = DevblocksPlatform::importGPC($_POST['followup_q'],'array',array());
		@$aFollowUpA = DevblocksPlatform::importGPC($_POST['followup_a'],'array',array());
		
		$umsession = $this->getSession();
		$fingerprint = parent::getFingerprint();

        $settings = CerberusSettings::getInstance();
        $default_from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM);

		$umsession->setProperty('support.write.last_from',$sFrom);
		$umsession->setProperty('support.write.last_subject',$sSubject);
		$umsession->setProperty('support.write.last_content',$sContent);
//		$umsession->setProperty('support.write.last_followup_q',$aFollowUpQ);
		$umsession->setProperty('support.write.last_followup_a',$aFollowUpA);
        
		$sNature = $umsession->getProperty('support.write.last_nature', '');
		
        $captcha_enabled = DAO_CommunityToolProperty::get($this->getPortal(), UmScApp::PARAM_CAPTCHA_ENABLED, 1);
		
		if(empty($sFrom) || ($captcha_enabled && 0 != strcasecmp($sCaptcha,@$umsession->getProperty(UmScApp::SESSION_CAPTCHA,'***')))) {
			
			if(empty($sFrom)) {
				$umsession->setProperty('support.write.last_error','Invalid e-mail address.');
			} else {
				$umsession->setProperty('support.write.last_error','What you typed did not match the image.');
			}
			
			// [TODO] Need to report the captcha didn't match and redraw the form
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'contact','step2')));
			return;
		}

		// Dispatch
		$to = $default_from;
		$subject = 'Contact me: Other';
		
        $sDispatch = DAO_CommunityToolProperty::get($this->getPortal(),UmScApp::PARAM_DISPATCH, '');
        $dispatch = !empty($sDispatch) ? unserialize($sDispatch) : array();

        foreach($dispatch as $k => $v) {
        	if(md5($k)==$sNature) {
        		$to = $v['to'];
        		$subject = 'Contact me: ' . strip_tags($k);
        		break;
        	}
        }
        
        if(!empty($sSubject))
        	$subject = $sSubject;
		
		$fieldContent = '';
		
		if(!empty($aFollowUpQ)) {
			$fieldContent = "\r\n\r\n";
			$fieldContent .= "--------------------------------------------\r\n";
			if(!empty($sNature)) {
				$fieldContent .= $subject . "\r\n";
				$fieldContent .= "--------------------------------------------\r\n";
			}
			foreach($aFollowUpQ as $idx => $q) {
				$fieldContent .= "Q) " . $q . "\r\n" . "A) " . $aFollowUpA[$idx] . "\r\n";
				if($idx+1 < count($aFollowUpQ)) $fieldContent .= "\r\n";
			}
			$fieldContent .= "--------------------------------------------\r\n";
			"\r\n";
		}
		
		$message = new CerberusParserMessage();
		$message->headers['date'] = date('r'); 
		$message->headers['to'] = $to;
		$message->headers['subject'] = $subject;
		$message->headers['message-id'] = CerberusApplication::generateMessageId();
		$message->headers['x-cerberus-portal'] = 1; 
		
		// Sender
		$fromList = imap_rfc822_parse_adrlist($sFrom,'');
		if(empty($fromList) || !is_array($fromList)) {
			return; // abort with message
		}
		$from = array_shift($fromList);
		$message->headers['from'] = $from->mailbox . '@' . $from->host; 

		$message->body = 'IP: ' . $fingerprint['ip'] . "\r\n\r\n" . $sContent . $fieldContent;

		$ticket_id = CerberusParser::parseMessage($message);
		$ticket = DAO_Ticket::getTicket($ticket_id);
		
		// Auto-save any custom fields
		if(!empty($aFieldIds))
		foreach($aFieldIds as $iIdx => $iFieldId) {
			if(!empty($iFieldId)) {
				DAO_TicketFieldValue::setFieldValue($ticket_id,$iFieldId,$aFollowUpA[$iIdx]);
			}
		}
		
		// Clear any errors
		$umsession->setProperty('support.write.last_nature',null);
		$umsession->setProperty('support.write.last_nature_string',null);
		$umsession->setProperty('support.write.last_content',null);
		$umsession->setProperty('support.write.last_error',null);
		$umsession->setProperty('support.write.last_opened',$ticket->mask);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'contact','confirm')));
	}	
	
//	function isVisible($uri) {
//		switch($uri) {
//			// Anyone can see
//			default:
//			case 'home':
//			case 'register':
//			case 'answers':
//			case 'contact':
//				return true;
//				break;
//				
//			// Must have a KB topic enabled
//			case 'kb':
//				// KB Roots
//				$sKbRoots = DAO_CommunityToolProperty::get($this->getPortal(),UmScApp::PARAM_KB_ROOTS, '');
//		        $kb_roots = !empty($sKbRoots) ? unserialize($sKbRoots) : array();
//		        return !empty($kb_roots);
//				break;
//			
//			// Must be logged in
//			case 'account':
//			case 'history':
//				$umsession = $this->getSession();
//				$active_user = $umsession->getProperty('sc_login', null);
//				return !empty($active_user);
//				break;
//		}
//		
//		return true;
//	}
	
	function handleRequest(DevblocksHttpRequest $request) {
		$umsession = $this->getSession();
		$active_user = $umsession->getProperty('sc_login', null);

		$stack = $request->path;
		@$module = array_shift($stack);
		
		switch($module) {
			case 'account':
			case 'history':
				// Secure these modules by login
				if(!empty($active_user))
					parent::handleRequest($request);
				break;
				
			default:
				parent::handleRequest($request);
				break;
		}		
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::getTemplateService();
        $tpl_path = realpath(dirname(__FILE__).'/../') . '/templates/';
		
        $theme = DAO_CommunityToolProperty::get($this->getPortal(), UmScApp::PARAM_THEME, UmScApp::DEFAULT_THEME);
        if(!is_dir($tpl_path . 'portal/sc/themes/'.$theme))
        	$theme = UmScApp::DEFAULT_THEME;
        
		$umsession = $this->getSession();
		$active_user = $umsession->getProperty('sc_login', null);

		$stack = $response->path;
		@$module = array_shift($stack);

		switch($module) {
			default:
			case 'home':
        		$sHomeRss = DAO_CommunityToolProperty::get($this->getPortal(),UmScApp::PARAM_HOME_RSS, '');
        		$aHomeRss = !empty($sHomeRss) ? unserialize($sHomeRss) : array();
        		
				$feeds = array();
				
				// [TODO] Implement a feed cache so we aren't bombing out
				foreach($aHomeRss as $title => $url) {
					$feed = null;
					try {
		    			$feed = Zend_Feed::import($url);
					} catch(Exception $e) {}
		    		if(!empty($feed) && $feed->count()) {
		   				$feeds[] = array(
		   					'name' => $title,
		   					'feed' => $feed
		   				);
		    		}
				}
	    		
	    		$tpl->assign('feeds', $feeds);
				
				$tpl->display("file:${tpl_path}portal/sc/internal/home/index.tpl.php");
				break;
				
			case 'account':
				if(!$this->allow_logins || empty($active_user))
					break;
					
				$address = DAO_Address::get($active_user->id);
				$tpl->assign('address',$address);
				
				$tpl->display("file:${tpl_path}portal/sc/internal/account/index.tpl.php");
				break;

			case 'kb':
				// KB Roots
				$sKbRoots = DAO_CommunityToolProperty::get($this->getPortal(),UmScApp::PARAM_KB_ROOTS, '');
		        $kb_roots = !empty($sKbRoots) ? unserialize($sKbRoots) : array();
				
				$kb_roots_str = '0';
				if(!empty($kb_roots))
					$kb_roots_str = implode(',', array_keys($kb_roots)); 
				
				switch(array_shift($stack)) {
					case 'article':
						if(empty($kb_roots))
							return;
						
						$id = intval(array_shift($stack));
		
						list($articles, $count) = DAO_KbArticle::search(
							array(
								new DevblocksSearchCriteria(SearchFields_KbArticle::ID,'=',$id),
								new DevblocksSearchCriteria(SearchFields_KbArticle::TOP_CATEGORY_ID,'in',array_keys($kb_roots))
							),
							-1,
							0,
							null,
							null,
							false
						);
						
						if(!isset($articles[$id]))
							break;
						
						$article = DAO_KbArticle::get($id);
						$tpl->assign('article', $article);
		
						@$article_list = $umsession->getProperty(UmScApp::SESSION_ARTICLE_LIST, array());
						if(!empty($article) && !isset($article_list[$id])) {
							DAO_KbArticle::update($article->id, array(
								DAO_KbArticle::VIEWS => ++$article->views
							));
							$article_list[$id] = $id;
							$umsession->setProperty(UmScApp::SESSION_ARTICLE_LIST, $article_list);
						}
		
						$categories = DAO_KbCategory::getWhere();
						$tpl->assign('categories', $categories);
						
						$cats = DAO_KbArticle::getCategoriesByArticleId($id);
		
						$breadcrumbs = array();
						foreach($cats as $cat_id) {
							if(!isset($breadcrumbs[$cat_id]))
								$breadcrumbs[$cat_id] = array();
							$pid = $cat_id;
							while($pid) {
								$breadcrumbs[$cat_id][] = $pid;
								$pid = $categories[$pid]->parent_id;
							}
							$breadcrumbs[$cat_id] = array_reverse($breadcrumbs[$cat_id]);
							
							// Remove any breadcrumbs not in this SC profile
							$pid = reset($breadcrumbs[$cat_id]);
							if(!isset($kb_roots[$pid]))
								unset($breadcrumbs[$cat_id]);
							
						}
						
						$tpl->assign('breadcrumbs',$breadcrumbs);
						$tpl->display("file:${tpl_path}portal/sc/internal/kb/article.tpl.php");
						break;
					
					default:
					case 'browse':
						@$root = intval(array_shift($stack));
						$tpl->assign('root_id', $root);
							
						$categories = DAO_KbCategory::getWhere();
						$tpl->assign('categories', $categories);
						
						$tree_map = DAO_KbCategory::getTreeMap(0);
						
						// Remove other top-level categories
						if(is_array($tree_map[0]))
						foreach($tree_map[0] as $child_id => $count) {
							if(!isset($kb_roots[$child_id]))
								unset($tree_map[0][$child_id]);
						}

						// Remove empty categories
						if(is_array($tree_map[0]))
						foreach($tree_map as $node_id => $children) {
							foreach($children as $child_id => $count) {
								if(empty($count)) {
									@$pid = $categories[$child_id]->parent_id;
									unset($tree_map[$pid][$child_id]);
									unset($tree_map[$child_id]);
								}
							}
						}
						
						$tpl->assign('tree', $tree_map);
						
						// Breadcrumb // [TODO] API-ize inside Model_KbTree ?
						$breadcrumb = array();
						$pid = $root;
						while(0 != $pid) {
							$breadcrumb[] = $pid;
							$pid = $categories[$pid]->parent_id;
						}
						$tpl->assign('breadcrumb',array_reverse($breadcrumb));
						
						$tpl->assign('mid', @intval(ceil(count($tree_map[$root])/2)));
						
						// Articles
						
						if(!empty($root))
						list($articles, $count) = DAO_KbArticle::search(
							array(
								new DevblocksSearchCriteria(SearchFields_KbArticle::CATEGORY_ID,'=',$root),
								new DevblocksSearchCriteria(SearchFields_KbArticle::TOP_CATEGORY_ID,'in',array_keys($kb_roots))
							),
							-1,
							0,
							null,
							null,
							false
						);
			    		$tpl->assign('articles', $articles);
			    		$tpl->display("file:${tpl_path}portal/sc/internal/kb/index.tpl.php");
			    	break;
				}
				
				break;
				
			case 'answers':
				$query = rawurldecode(array_shift($stack));
				$tpl->assign('query', $query);
				
		        $sFnrSources = DAO_CommunityToolProperty::get($this->getPortal(),UmScApp::PARAM_FNR_SOURCES, '');
		        $aFnrSources = !empty($sFnrSources) ? unserialize($sFnrSources) : array();
				
				if(!empty($query)) {
					// [JAS]: If we've been customized with specific sources, use them
					$where = !empty($aFnrSources) 
						? sprintf("%s IN (%s)",
							DAO_FnrExternalResource::ID,
							implode(',', array_keys($aFnrSources))
						)
						: sprintf("%s IN (-1)",
							DAO_FnrExternalResource::ID
						);
					$resources = DAO_FnrExternalResource::getWhere($where);
					$feeds = Model_FnrExternalResource::searchResources($resources, $query);
					$tpl->assign('feeds', $feeds);
					
					$fields = array(
						DAO_FnrQuery::QUERY => $query,
						DAO_FnrQuery::CREATED => time(),
						DAO_FnrQuery::SOURCE => $this->getPortal(),
						DAO_FnrQuery::NO_MATCH => (empty($feeds) ? 1 : 0)
					);
					DAO_FnrQuery::create($fields);
				}

				// KB
				$sKbRoots = DAO_CommunityToolProperty::get($this->getPortal(),UmScApp::PARAM_KB_ROOTS, '');
		        $kb_roots = !empty($sKbRoots) ? unserialize($sKbRoots) : array();
		        
				list($articles, $count) = DAO_KbArticle::search(
				 	array(
				 		array(
				 			DevblocksSearchCriteria::GROUP_OR,
				 			new DevblocksSearchCriteria(SearchFields_KbArticle::TITLE,'fulltext',$query),
				 			new DevblocksSearchCriteria(SearchFields_KbArticle::CONTENT,'fulltext',$query),
				 		),
				 		new DevblocksSearchCriteria(SearchFields_KbArticle::TOP_CATEGORY_ID,'in',array_keys($kb_roots)),
				 	),
				 	100,
				 	0,
				 	null,
				 	null,
				 	true
				);
				$tpl->assign('articles', $articles);
				
				$tpl->display("file:${tpl_path}portal/sc/internal/answers/index.tpl.php");
				break;
				
			case 'contact':
		    	$response = array_shift($stack);
		    	
		    	$settings = CerberusSettings::getInstance();
        		$default_from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM);
        		$tpl->assign('default_from', $default_from);
		    	
		    	switch($response) {
		    		case 'confirm':
		    			$tpl->assign('last_opened',$umsession->getProperty('support.write.last_opened',''));
		    			$tpl->display("file:${tpl_path}portal/sc/internal/contact/confirm.tpl.php");
		    			break;
		    		
		    		default:
		    		case 'step1':
		    			$umsession->setProperty('support.write.last_error', null);
		    		case 'step2':
		    			$sFrom = $umsession->getProperty('support.write.last_from','');
		    			$sSubject = $umsession->getProperty('support.write.last_subject','');
		    			$sNature = $umsession->getProperty('support.write.last_nature','');
		    			$sContent = $umsession->getProperty('support.write.last_content','');
//		    			$aLastFollowupQ = $umsession->getProperty('support.write.last_followup_q','');
		    			$aLastFollowupA = $umsession->getProperty('support.write.last_followup_a','');
		    			$sError = $umsession->getProperty('support.write.last_error','');
		    			
						$tpl->assign('last_from', $sFrom);
						$tpl->assign('last_subject', $sSubject);
						$tpl->assign('last_nature', $sNature);
						$tpl->assign('last_content', $sContent);
//						$tpl->assign('last_followup_q', $aLastFollowupQ);
						$tpl->assign('last_followup_a', $aLastFollowupA);
						$tpl->assign('last_error', $sError);
						
	       				$sDispatch = DAO_CommunityToolProperty::get($this->getPortal(),UmScApp::PARAM_DISPATCH, '');
		    			$dispatch = !empty($sDispatch) ? unserialize($sDispatch) : array();
				        $tpl->assign('dispatch', $dispatch);
				        
				        switch($response) {
				        	default:
				        		$tpl->display("file:${tpl_path}portal/sc/internal/contact/step1.tpl.php");
				        		break;
				        		
				        	case 'step2':
				        		// Cache along with answers?
								if(is_array($dispatch))
						        foreach($dispatch as $k => $v) {
						        	if(md5($k)==$sNature) {
						        		$umsession->setProperty('support.write.last_nature_string', $k);
						        		$tpl->assign('situation', $k);
						        		$tpl->assign('situation_params', $v);
						        		break;
						        	}
						        }
						        
						        $ticket_fields = DAO_TicketField::getWhere();
        						$tpl->assign('ticket_fields', $ticket_fields);
						        
				        		$tpl->display("file:${tpl_path}portal/sc/internal/contact/step2.tpl.php");
				        		break;
				        }
				        break;
		    		}
		    		
		    	break;
//				$tpl->display("file:${tpl_path}portal/sc/internal/contact/index.tpl.php");
//				break;
				
			case 'history':
				if(!$this->allow_logins || empty($active_user))
					break;
				
				$mask = array_shift($stack);
				
				if(empty($mask)) {
					list($open_tickets) = DAO_Ticket::search(
						array(),
						array(
							new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE_ID,'=',$active_user->id),
							new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',0)
						),
						-1,
						0,
						SearchFields_Ticket::TICKET_UPDATED_DATE,
						false,
						false
					);
					$tpl->assign('open_tickets', $open_tickets);
					
					list($closed_tickets) = DAO_Ticket::search(
						array(),
						array(
							new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE_ID,'=',$active_user->id),
							new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',1)
						),
						-1,
						0,
						SearchFields_Ticket::TICKET_UPDATED_DATE,
						false,
						false
					);
					$tpl->assign('closed_tickets', $closed_tickets);
					$tpl->display("file:${tpl_path}portal/sc/internal/history/index.tpl.php");
					
				} else {
					// Secure retrieval (address + mask)
					list($tickets) = DAO_Ticket::search(
						array(),
						array(
							new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_MASK,'=',$mask),
							new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_FIRST_WROTE_ID,'=',$active_user->id),
						),
						1,
						0,
						null,
						null,
						false
					);
					$ticket = array_shift($tickets);
					
					// Security check (mask compare)
					if(0 == strcasecmp($ticket[SearchFields_Ticket::TICKET_MASK],$mask)) {
						$messages = DAO_Ticket::getMessagesByTicket($ticket[SearchFields_Ticket::TICKET_ID]);
						$messages = array_reverse($messages, true);						
						
						$tpl->assign('ticket', $ticket);
						$tpl->assign('messages', $messages);
						$tpl->display("file:${tpl_path}portal/sc/internal/history/display.tpl.php");						
					}
				}
				
				break;
				
			case 'register':
				if(!$this->allow_logins)
					break;
					 
				@$step = array_shift($stack);
				
				switch($step) {
					case 'forgot':
						$tpl->display("file:${tpl_path}portal/sc/internal/register/forgot.tpl.php");
						break;
					case 'forgot2':
						$tpl->display("file:${tpl_path}portal/sc/internal/register/forgot_confirm.tpl.php");
						break;
					case 'confirm':
						$tpl->display("file:${tpl_path}portal/sc/internal/register/confirm.tpl.php");
						break;
					default:
						$tpl->display("file:${tpl_path}portal/sc/internal/register/index.tpl.php");
						break;
				}
				
				break;
		}
		
//		print_r($response);
	}
};

?>