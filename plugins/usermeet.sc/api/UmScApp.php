<?php
// Classes
$path = realpath(dirname(__FILE__).'/../') . DIRECTORY_SEPARATOR;

DevblocksPlatform::registerClasses($path. 'api/Extension.php', array(
    'Extension_UmScController'
));

class UmScPlugin extends DevblocksPlugin {
	function load(DevblocksPluginManifest $manifest) {
	}
};

class UmScApp extends Extension_UsermeetTool {
	const PARAM_LOGO_URL = 'logo_url';
	const PARAM_THEME_URL = 'theme_url';
	const PARAM_PAGE_TITLE = 'page_title';
	const PARAM_CAPTCHA_ENABLED = 'captcha_enabled';
	const PARAM_DISPATCH = 'dispatch';
	const PARAM_HOME_RSS = 'home_rss';
	const PARAM_FNR_SOURCES = 'fnr_sources';
	const PARAM_ALLOW_LOGINS = 'allow_logins';
	
	const SESSION_CAPTCHA = 'write_captcha';
	
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
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = realpath(dirname(__FILE__).'/../') . '/templates/';
		$tpl->assign('tpl_path', $tpl_path);
		
        $umsession = $this->getSession();
		$stack = $response->path;
		
		$logo_url = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_LOGO_URL, '');
		$tpl->assign('logo_url', $logo_url);
        
		$page_title = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_PAGE_TITLE, 'Support Center');
		$tpl->assign('page_title', $page_title);
        
        $captcha_enabled = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_CAPTCHA_ENABLED, 1);
		$tpl->assign('captcha_enabled', $captcha_enabled);

        $allow_logins = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_ALLOW_LOGINS, 0);
		$tpl->assign('allow_logins', $allow_logins);
		
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

        $tpl->assign('menu', $this->modules);
        
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
                
				$im = @imagecreate(150, 80) or die("Cannot Initialize new GD image stream");
				$background_color = imagecolorallocate($im, 0, 0, 0);
				$text_color = imagecolorallocate($im, 255, 255, 255); //233, 14, 91
				$font = DEVBLOCKS_PATH . 'resources/font/ryanlerch_-_Tuffy_Bold(2).ttf';
				imagettftext($im, 24, rand(0,20), 5, 60+6, $text_color, $font, $phrase);
//				$im = imagerotate($im, rand(-20,20), $background_color);
				imagejpeg($im,null,85);
				imagedestroy($im);
				exit;
				break;
			
	    	default:
	    		// Look up the current module
   				$tpl->display('file:' . $tpl_path . 'index.tpl.php');
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
        
        $sFnrSources = DAO_CommunityToolProperty::get($this->getPortal(),self::PARAM_FNR_SOURCES, '');
        $fnr_sources = !empty($sFnrSources) ? unserialize($sFnrSources) : array();
        $tpl->assign('fnr_sources', $fnr_sources);
        
        $logo_url = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_LOGO_URL, '');
		$tpl->assign('logo_url', $logo_url);
        
        $page_title = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_PAGE_TITLE, 'Support Center');
		$tpl->assign('page_title', $page_title);
        
        $captcha_enabled = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_CAPTCHA_ENABLED, 1);
		$tpl->assign('captcha_enabled', $captcha_enabled);

        $allow_logins = DAO_CommunityToolProperty::get($this->getPortal(), self::PARAM_ALLOW_LOGINS, 0);
		$tpl->assign('allow_logins', $allow_logins);

//		$resources = DAO_FnrExternalResource::getWhere();
		$topics = DAO_FnrTopic::getWhere();
//		$tpl->assign('resources', $resources);
		$tpl->assign('topics', $topics);
		
        $tpl->display("file:${tpl_path}config/index.tpl.php");
    }
    
    public function saveConfiguration() {
        @$sLogoUrl = DevblocksPlatform::importGPC($_POST['logo_url'],'string','');
        @$sPageTitle = DevblocksPlatform::importGPC($_POST['page_title'],'string','Contact Us');
        @$iCaptcha = DevblocksPlatform::importGPC($_POST['captcha_enabled'],'integer',1);
        @$iAllowLogins = DevblocksPlatform::importGPC($_POST['allow_logins'],'integer',0);

        DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_LOGO_URL, $sLogoUrl);
        DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_PAGE_TITLE, $sPageTitle);
        DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_CAPTCHA_ENABLED, $iCaptcha);
        DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_ALLOW_LOGINS, $iAllowLogins);

        @$aHomeRssTitles = DevblocksPlatform::importGPC($_POST['home_rss_title'],'array',array());
        @$aHomeRssUrls = DevblocksPlatform::importGPC($_POST['home_rss_url'],'array',array());
        @$aFnrSources = DevblocksPlatform::importGPC($_POST['fnr_sources'],'array',array());
        
        $aHomeRss = array();
        
        foreach($aHomeRssUrls as $idx => $rss) {
        	if(empty($rss)) {
        		unset($aHomeRss[$idx]);
        		continue;
        	}
        	$aHomeRss[$aHomeRssTitles[$idx]] = $rss;
        }
        
        $aFnrSources = array_flip($aFnrSources);
        
		DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_HOME_RSS, serialize($aHomeRss));
		DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_FNR_SOURCES, serialize($aFnrSources));

        $settings = CerberusSettings::getInstance();
        $default_from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM);
        
    	@$sEditReason = DevblocksPlatform::importGPC($_POST['edit_reason'],'string','');
    	@$sReason = DevblocksPlatform::importGPC($_POST['reason'],'string','');
        @$sTo = DevblocksPlatform::importGPC($_POST['to'],'string','');
        @$aFollowup = DevblocksPlatform::importGPC($_POST['followup'],'array',array());
        @$aFollowupLong = DevblocksPlatform::importGPC($_POST['followup_long'],'array',array());
        
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

        // Nuke a record we're replacing
       	if(!empty($sEditReason)) {
			// will be MD5
	        if(is_array($dispatch))
	        foreach($dispatch as $d_reason => $d_params) {
	        	if(md5($d_reason)==$sEditReason) {
	        		unset($dispatch[$d_reason]);
	        	}
	        }
       	}
        
       	// If we have new data, add it
        if(!empty($sReason) && !empty($sTo)) {
			$dispatch[$sReason] = array(
				'to' => $sTo,
				'followups' => array()
			);
			
			$followups =& $dispatch[$sReason]['followups'];
			
			if(!empty($aFollowup))
			foreach($aFollowup as $idx => $followup) {
				if(empty($followup)) continue;
				$followups[$followup] = (false !== array_search($idx,$aFollowupLong)) ? 1 : 0;
			}
        }
        
        ksort($dispatch);
        
		DAO_CommunityToolProperty::set($this->getPortal(), self::PARAM_DISPATCH, serialize($dispatch));
    }
    
    // Ajax
    public function getSituation() {
		@$sCode = DevblocksPlatform::importGPC($_REQUEST['code'],'string','');
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
        
        $tpl->display("file:${tpl_path}config/add_situation.tpl.php");
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
		if(null != ($address_id = DAO_Address::lookupAddress($email, false))) {
			$auth = DAO_AddressAuth::get($address_id);
			
			if(!empty($auth->pass) && md5($pass)==$auth->pass) {
				$valid = true;
				$address = DAO_Address::get($address_id);
				$umsession->setProperty('sc_login',$address);
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
		$mail_service = DevblocksPlatform::getMailService();
		$mailer = $mail_service->getMailer();
		
		$code = CerberusApplication::generatePassword(8);
		
		if(!empty($email) && null != ($address_id = DAO_Address::lookupAddress($email, false))) {
			$fields = array(
				DAO_AddressAuth::CONFIRM => $code
			);
			DAO_AddressAuth::update($address_id, $fields);
			
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
			if(null != ($address_id = DAO_Address::lookupAddress($email, false))
				&& null != ($auth = DAO_AddressAuth::get($address_id))
				&& !empty($auth) 
				&& !empty($auth->confirm) 
				&& 0 == strcasecmp($code,$auth->confirm)) {
					$fields = array(
						DAO_AddressAuth::PASS => md5($pass)
					);
					DAO_AddressAuth::update($address_id, $fields);
				
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
		
		if(!empty($email) && null != ($address_id = DAO_Address::lookupAddress($email, true))) {
			$auth = DAO_AddressAuth::get($address_id);
			
			// Already registered?
			if(!empty($auth) && !empty($auth->pass)) {
				$tpl->assign('register_error', sprintf("'%s' is already registered.",$email));
				DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'register')));
				return;
			}
			
			$fields = array(
				DAO_AddressAuth::CONFIRM => $code
			);
			DAO_AddressAuth::update($address_id, $fields);
			
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
			if(null != ($address_id = DAO_Address::lookupAddress($email, false))
				&& null != ($auth = DAO_AddressAuth::get($address_id))
				&& !empty($auth) 
				&& !empty($auth->confirm) 
				&& 0 == strcasecmp($code,$auth->confirm)) {
					$fields = array(
						DAO_AddressAuth::PASS => md5($pass)
					);
					DAO_AddressAuth::update($address_id, $fields);
				
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
		$umsession->setProperty('support.write.last_content', null);
		$umsession->setProperty('support.write.last_error', null);
		
		$sDispatch = DAO_CommunityToolProperty::get($this->getPortal(),UmScApp::PARAM_DISPATCH, '');
		$dispatch = !empty($sDispatch) ? unserialize($sDispatch) : array();
		
		// Check if this nature has followups, if not skip to step3
		$followups = array();
		if(is_array($dispatch))
        foreach($dispatch as $k => $v) {
        	if(md5($k)==$sNature) {
        		$umsession->setProperty('support.write.last_nature_string', $k);
        		@$followups = $v['followups'];
        		break;
        	}
        }

        if(empty($followups)) {		
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'contact','step3')));
        } else {
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'contact','step2')));
        }
	}
	
	function doContactStep3Action() {
		$umsession = $this->getSession();
		$fingerprint = parent::getFingerprint();

		@$aFollowUpQ = DevblocksPlatform::importGPC($_POST['followup_q'],'array',array());
		@$aFollowUpA = DevblocksPlatform::importGPC($_POST['followup_a'],'array',array());
		$nature = $umsession->getProperty('support.write.last_nature_string','');
		$content = '';
		
		if(!empty($aFollowUpQ)) {
			$content = "Comments:\r\n\r\n\r\n";
			$content .= "--------------------------------------------\r\n";
			if(!empty($nature)) {
				$content .= $nature . "\r\n";
				$content .= "--------------------------------------------\r\n";
			}
			foreach($aFollowUpQ as $idx => $q) {
				$content .= "Q) " . $q . "\r\n" . "A) " . $aFollowUpA[$idx] . "\r\n";
				if($idx+1 < count($aFollowUpQ)) $content .= "\r\n";
			}
			$content .= "--------------------------------------------\r\n";
			"\r\n";
		}
		
		$umsession->setProperty('support.write.last_content', $content);
		$umsession->setProperty('support.write.last_error', null);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'contact','step3')));
	}
	
	function doContactSendAction() {
		@$sFrom = DevblocksPlatform::importGPC($_POST['from'],'string','');
		@$sContent = DevblocksPlatform::importGPC($_POST['content'],'string','');
		@$sCaptcha = DevblocksPlatform::importGPC($_POST['captcha'],'string','');
		
		$umsession = $this->getSession();
		$fingerprint = parent::getFingerprint();

        $settings = CerberusSettings::getInstance();
        $default_from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM);

		$umsession->setProperty('support.write.last_from',$sFrom);
		$umsession->setProperty('support.write.last_content',$sContent);
        
		$sNature = $umsession->getProperty('support.write.last_nature', '');
		
        $captcha_enabled = DAO_CommunityToolProperty::get($this->getPortal(), UmScApp::PARAM_CAPTCHA_ENABLED, 1);
		
		if(empty($sFrom) || ($captcha_enabled && 0 != strcasecmp($sCaptcha,@$umsession->getProperty(UmScApp::SESSION_CAPTCHA,'***')))) {
			
			if(empty($sFrom)) {
				$umsession->setProperty('support.write.last_error','Invalid e-mail address.');
			} else {
				$umsession->setProperty('support.write.last_error','What you typed did not match the image.');
			}
			
			// [TODO] Need to report the captcha didn't match and redraw the form
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'contact','step3')));
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
        		$subject = 'Contact me: ' . $k;
        		break;
        	}
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

		$message->body = 'IP: ' . $fingerprint['ip'] . "\r\n\r\n" . $sContent;

		$ticket_id = CerberusParser::parseMessage($message);
		$ticket = DAO_Ticket::getTicket($ticket_id);
		
//		echo "Created Ticket ID: $ticket_id<br>";
		// [TODO] Could set this ID/mask into the UMsession

		// Clear any errors
		$umsession->setProperty('support.write.last_nature',null);
		$umsession->setProperty('support.write.last_nature_string',null);
		$umsession->setProperty('support.write.last_content',null);
		$umsession->setProperty('support.write.last_error',null);
		$umsession->setProperty('support.write.last_opened',$ticket->mask);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',$this->getPortal(),'contact','confirm')));
	}	
	
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
        $tpl_path = realpath(dirname(__FILE__).'/../') . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;
		
		$umsession = $this->getSession();
		$active_user = $umsession->getProperty('sc_login', null);

        $sHomeRss = DAO_CommunityToolProperty::get($this->getPortal(),UmScApp::PARAM_HOME_RSS, '');
        $aHomeRss = !empty($sHomeRss) ? unserialize($sHomeRss) : array();
		
		$stack = $response->path;
		@$module = array_shift($stack);

		switch($module) {
			default:
			case 'home':
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
				
				$tpl->display("file:${tpl_path}modules/home/index.tpl.php");
				break;
				
			case 'account':
				if(!$this->allow_logins || empty($active_user))
					break;
					
				$address = DAO_Address::get($active_user->id);
				$tpl->assign('address',$address);
				
				$tpl->display("file:${tpl_path}modules/account/index.tpl.php");
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
				}
				
				$tpl->display("file:${tpl_path}modules/answers/index.tpl.php");
				break;
				
			case 'contact':
		    	$response = array_shift($stack);
		    	
		    	$settings = CerberusSettings::getInstance();
        		$default_from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM);
        		$tpl->assign('default_from', $default_from);
		    	
		    	switch($response) {
		    		case 'confirm':
		    			$tpl->assign('last_opened',$umsession->getProperty('support.write.last_opened',''));
		    			$tpl->display("file:${tpl_path}modules/contact/confirm.tpl.php");
		    			break;
		    		
		    		default:
		    		case 'step1':
		    		case 'step2':
		    		case 'step3':
		    			$sFrom = $umsession->getProperty('support.write.last_from','');
		    			$sNature = $umsession->getProperty('support.write.last_nature','');
		    			$sContent = $umsession->getProperty('support.write.last_content','');
		    			$sError = $umsession->getProperty('support.write.last_error','');
		    			
						$tpl->assign('last_from', $sFrom);
						$tpl->assign('last_nature', $sNature);
						$tpl->assign('last_content', $sContent);
						$tpl->assign('last_error', $sError);
						
	       				$sDispatch = DAO_CommunityToolProperty::get($this->getPortal(),UmScApp::PARAM_DISPATCH, '');
		    			$dispatch = !empty($sDispatch) ? unserialize($sDispatch) : array();
				        $tpl->assign('dispatch', $dispatch);
				        
				        switch($response) {
				        	default:
				        		$tpl->display("file:${tpl_path}modules/contact/step1.tpl.php");
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
				        		$tpl->display("file:${tpl_path}modules/contact/step2.tpl.php");
				        		break;
				        		
				        	case 'step3':
				        		$tpl->display("file:${tpl_path}modules/contact/step3.tpl.php");
				        		break;
				        }
				        break;
		    		}
		    		
		    	break;
//				$tpl->display("file:${tpl_path}modules/contact/index.tpl.php");
//				break;
				
			case 'history':
				if(!$this->allow_logins || empty($active_user))
					break;
				
				$mask = array_shift($stack);
				
				if(empty($mask)) {
					list($open_tickets) = DAO_Ticket::search(
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
					$tpl->display("file:${tpl_path}modules/history/index.tpl.php");
					
				} else {
					// Secure retrieval (address + mask)
					list($tickets) = DAO_Ticket::search(
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
						$tpl->display("file:${tpl_path}modules/history/display.tpl.php");						
					}
				}
				
				break;
				
			case 'register':
				if(!$this->allow_logins)
					break;
					 
				@$step = array_shift($stack);
				
				switch($step) {
					case 'forgot':
						$tpl->display("file:${tpl_path}modules/register/forgot.tpl.php");
						break;
					case 'forgot2':
						$tpl->display("file:${tpl_path}modules/register/forgot_confirm.tpl.php");
						break;
					case 'confirm':
						$tpl->display("file:${tpl_path}modules/register/confirm.tpl.php");
						break;
					default:
						$tpl->display("file:${tpl_path}modules/register/index.tpl.php");
						break;
				}
				
				break;
		}
		
//		print_r($response);
	}
};

?>