<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2011, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerberus Helpdesk Team
 * 
 * Sure, it would be so easy to just cheat and edit this file to use the 
 * software without paying for it.  But we trust you anyway.  In fact, we're 
 * writing this software for you! 
 * 
 * Quality software backed by a dedicated team takes money to develop.  We 
 * don't want to be out of the office bagging groceries when you call up 
 * needing a helping hand.  We'd rather spend our free time coding your 
 * feature requests than mowing the neighbors' lawns for rent money. 
 * 
 * We've never believed in hiding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your inbox that you probably 
 * haven't had since spammers found you in a game of 'E-mail Battleship'. 
 * Miss. Miss. You sunk my inbox!
 * 
 * A legitimate license entitles you to support from the developers,  
 * and the warm fuzzy feeling of feeding a couple of obsessed developers 
 * who want to help you get more done.
 *
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Scott Luther,
 * 		and Jerry Kanoholani. 
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
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

class UmScApp extends Extension_UsermeetTool {
	const PARAM_PAGE_TITLE = 'common.page_title';
	const PARAM_DEFAULT_LOCALE = 'common.locale';
	const PARAM_LOGIN_EXTENSIONS = 'common.login_extensions';
	const PARAM_VISIBLE_MODULES = 'common.visible_modules';
	
	const SESSION_CAPTCHA = 'write_captcha';
	
    private function _getModules() {
    	static $modules = null;
		
    	// Lazy load
    	if(null == $modules) {
	    	$umsession = UmPortalHelper::getSession();
			@$active_contact = $umsession->getProperty('sc_login',null);
    		
			@$visible_modules = unserialize(DAO_CommunityToolProperty::get(UmPortalHelper::getCode(), self::PARAM_VISIBLE_MODULES, ''));
			
			if(is_array($visible_modules))
			foreach($visible_modules as $module_id => $visibility) {
				// Disabled
				if(0==strcmp($visibility, '2'))
					continue;

				// Must be logged in
				if(0==strcmp($visibility, '1') && empty($active_contact))
					continue;
				
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
    
    public static function getLoginExtensions() {
		$login_extensions = DevblocksPlatform::getExtensions('usermeet.login.authenticator');
		uasort($login_extensions, create_function('$a, $b', "return strcasecmp(\$a->name,\$b->name);\n"));
		return $login_extensions;
    }
    
    public static function getLoginExtensionsEnabled($instance_id) {
    	$login_extensions = self::getLoginExtensions();
    	
    	$enabled = array();

		if(null != ($str = DAO_CommunityToolProperty::get($instance_id, self::PARAM_LOGIN_EXTENSIONS, ''))) {
			$ids = explode(',', $str);
			foreach($ids as $id) {
				if(isset($login_extensions[$id]))
					$enabled[$id] = $login_extensions[$id];
			}
		}
		
		return $enabled;
    }
    
    public static function getLoginExtensionActive($instance_id, $as_instance=true) {
    	$umsession = UmPortalHelper::getSession();
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
        
		// Set locale in scope
        $default_locale = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(), self::PARAM_DEFAULT_LOCALE, 'en_US');
		DevblocksPlatform::setLocale($default_locale);
		
		switch($module_uri) {
			case 'ajax':
				$controller = new UmScAjaxController(null);
				$controller->handleRequest(new DevblocksHttpRequest($stack));
				exit;
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
		$tpl->assign('portal_code', UmPortalHelper::getCode());
		
		$page_title = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(), self::PARAM_PAGE_TITLE, 'Support Center');
		$tpl->assign('page_title', $page_title);

       	@$visible_modules = unserialize(DAO_CommunityToolProperty::get(UmPortalHelper::getCode(), self::PARAM_VISIBLE_MODULES, ''));
		$tpl->assign('visible_modules', $visible_modules);
		
        @$active_contact = $umsession->getProperty('sc_login',null);
        $tpl->assign('active_contact', $active_contact);

        $login_extensions_enabled = UmScApp::getLoginExtensionsEnabled(UmPortalHelper::getCode());
        $tpl->assign('login_extensions_enabled', $login_extensions_enabled);
        
		// Usermeet Session
		if(null == ($fingerprint = UmPortalHelper::getFingerprint())) {
			die("A problem occurred.");
		}
        $tpl->assign('fingerprint', $fingerprint);

        $module_uri = array_shift($stack);
		
		switch($module_uri) {
			case 'rss':
				$controller = new UmScRssController(null);
				$controller->handleRequest(new DevblocksHttpRequest($stack));
				break;
				
			case 'captcha':
				@$color = DevblocksPlatform::parseCsvString(DevblocksPlatform::importGPC($_REQUEST['color'],'string','40,40,40'));
				@$bgcolor = DevblocksPlatform::parseCsvString(DevblocksPlatform::importGPC($_REQUEST['bgcolor'],'string','240,240,240'));
				
				// Sanitize colors
				// [TODO] Sanitize numeric range for elements 0-2
				if(3 != count($color))
					$color = array(40,40,40);
				if(3 != count($bgcolor))
					$color = array(240,240,240);
				
                header('Cache-control: max-age=0', true); // 1 wk // , must-revalidate
                header('Expires: ' . gmdate('D, d M Y H:i:s',time()-604800) . ' GMT'); // 1 wk
				header('Content-type: image/jpeg');

		        // Get CAPTCHA secret passphrase
				$phrase = CerberusApplication::generatePassword(4);
		        $umsession->setProperty(UmScApp::SESSION_CAPTCHA, $phrase);
                
				$im = @imagecreate(150, 70) or die("Cannot Initialize new GD image stream");
				$background_color = imagecolorallocate($im, $bgcolor[0], $bgcolor[1], $bgcolor[2]);
				$text_color = imagecolorallocate($im, $color[0], $color[1], $color[2]);
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
				
   				$tpl->display('devblocks:cerberusweb.support_center:portal_'.UmPortalHelper::getCode() . ":support_center/index.tpl");
		    	break;
		}
	}
	
	/**
	 * @param $instance Model_CommunityTool 
	 */
    public function configure(Model_CommunityTool $instance) {
        $tpl = DevblocksPlatform::getTemplateService();
        
		// Locales
		
        $default_locale = DAO_CommunityToolProperty::get($instance->code, self::PARAM_DEFAULT_LOCALE, 'en_US');
		$tpl->assign('default_locale', $default_locale);
		
		$locales = DAO_Translation::getDefinedLangCodes();
		$tpl->assign('locales', $locales);

		// Personalization

        $page_title = DAO_CommunityToolProperty::get($instance->code, self::PARAM_PAGE_TITLE, 'Support Center');
		$tpl->assign('page_title', $page_title);

		// Modules

        @$visible_modules = unserialize(DAO_CommunityToolProperty::get($instance->code, self::PARAM_VISIBLE_MODULES, ''));
		$tpl->assign('visible_modules', $visible_modules);
		
		$all_modules = DevblocksPlatform::getExtensions('usermeet.sc.controller', true, true);
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
		
        $tpl->display("devblocks:cerberusweb.support_center::portal/sc/config/index.tpl");
    }
    
    public function saveConfiguration(Model_CommunityTool $instance) {
        @$aVisibleModules = DevblocksPlatform::importGPC($_POST['visible_modules'],'array',array());
        @$aIdxModules = DevblocksPlatform::importGPC($_POST['idx_modules'],'array',array());
        @$aPosModules = DevblocksPlatform::importGPC($_POST['pos_modules'],'array',array());
        @$sPageTitle = DevblocksPlatform::importGPC($_POST['page_title'],'string','Contact Us');

		// Modules (toggle + sort)
		if(is_array($aIdxModules))
		foreach($aIdxModules as $idx => $module_id) {
			if(0==strcmp($aVisibleModules[$idx],'2')) {
				unset($aPosModules[$idx]);
			}
		}
			
		// Rearrange modules by sort order
		$aEnabledModules = array();
		asort($aPosModules); // sort enabled by order asc
		foreach($aPosModules as $idx => $null)
			$aEnabledModules[$aIdxModules[$idx]] = $aVisibleModules[$idx];
			
        DAO_CommunityToolProperty::set($instance->code, self::PARAM_VISIBLE_MODULES, serialize($aEnabledModules));
        DAO_CommunityToolProperty::set($instance->code, self::PARAM_PAGE_TITLE, $sPageTitle);

		// Default Locale
        @$sDefaultLocale = DevblocksPlatform::importGPC($_POST['default_locale'],'string','en_US');
		DAO_CommunityToolProperty::set($instance->code, self::PARAM_DEFAULT_LOCALE, $sDefaultLocale);

		// Allow modules to save their own config
		$modules = DevblocksPlatform::getExtensions('usermeet.sc.controller',true,true);
		foreach($modules as $module) { /* @var $module Extension_UmScController */
			// Only save enabled
			if(!isset($aEnabledModules[$module->manifest->id]))
				continue;
				
			$module->saveConfiguration($instance);
		}

    }
};

class UmScLoginAuthenticator extends Extension_ScLoginAuthenticator {
	function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::getTemplateService();
		$umsession = UmPortalHelper::getSession();
		
		$stack = $response->path;
		@$module = array_shift($stack);
		
		switch($module) {
			case 'register':
				$tpl->assign('email', $umsession->getProperty('register.email',''));
				
				if(isset($stack[0]) && 0==strcasecmp('confirm',$stack[0])) {
					$tpl->display("devblocks:cerberusweb.support_center:portal_".UmPortalHelper::getCode().":support_center/login/default/register_confirm.tpl");
				} else {
					$tpl->display("devblocks:cerberusweb.support_center:portal_".UmPortalHelper::getCode().":support_center/login/default/register.tpl");
				}
				break;
			case 'forgot':
				if(isset($stack[0]) && 0==strcasecmp('confirm',$stack[0])) {
					$tpl->display("devblocks:cerberusweb.support_center:portal_".UmPortalHelper::getCode().":support_center/login/default/forgot_confirm.tpl");
				} else {
					$tpl->display("devblocks:cerberusweb.support_center:portal_".UmPortalHelper::getCode().":support_center/login/default/forgot.tpl");
				}
				break;
			default:
				$tpl->display("devblocks:cerberusweb.support_center:portal_".UmPortalHelper::getCode().":support_center/login/default/login.tpl");
				break;
		}		
	}
	
	function doRegisterAction() {
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$url_writer = DevblocksPlatform::getUrlService();
		$umsession = UmPortalHelper::getSession();
		
		try {
			// Validate
			$address_parsed = imap_rfc822_parse_adrlist($email,'host');
			if(empty($email) || empty($address_parsed) || !is_array($address_parsed) || empty($address_parsed[0]->host) || $address_parsed[0]->host=='host')
				throw new Exception("The email address you provided is invalid.");
			
			// Check to see if the address is currently assigned to an account
			if(null != ($address = DAO_Address::lookupAddress($email, false)) && !empty($address->contact_person_id))
				throw new Exception("The provided email address is already associated with an account.");
				
			// Update the preferred email address
			$umsession->setProperty('register.email', $email);
				
			// Send a confirmation code
			$fields = array(
				DAO_ConfirmationCode::CONFIRMATION_CODE => CerberusApplication::generatePassword(8),
				DAO_ConfirmationCode::NAMESPACE_KEY => 'support_center.login.register.verify',
				DAO_ConfirmationCode::META_JSON => json_encode(array(
					'email' => $email,
				)),
				DAO_ConfirmationCode::CREATED => time(),
			);
			DAO_ConfirmationCode::create($fields);

			// Quick send
			$msg = sprintf(
				"Your confirmation code: %s",
				urlencode($fields[DAO_ConfirmationCode::CONFIRMATION_CODE])
			);
			CerberusMail::quickSend($email,"Please confirm your email address", $msg);
				
		} catch(Exception $e) {
			$tpl->assign('error', $e->getMessage());
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'login','register')));
			return;
			
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'login','register','confirm')));
	}
	
	function doRegisterConfirmAction() {
		@$confirm = DevblocksPlatform::importGPC($_REQUEST['confirm'],'string','');
		@$password = DevblocksPlatform::importGPC($_REQUEST['password'],'string','');
		@$password2 = DevblocksPlatform::importGPC($_REQUEST['password2'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$url_writer = DevblocksPlatform::getUrlService();
		$umsession = UmPortalHelper::getSession();
		
		try {
			// Load the session (OpenID + email)
			$email = $umsession->getProperty('register.email', '');

			// We need the basics in place
			if(empty($email)) {
				header("Location: " . $url_writer->write('c=login', true));
				exit;
			}
			
			// Lookup code
			if(null == ($code = DAO_ConfirmationCode::getByCode('support_center.login.register.verify', $confirm)))
				throw new Exception("Your confirmation code is invalid.");
			
			// Compare to address
			if(!isset($code->meta['email']) || 0 != strcasecmp($email, $code->meta['email']))
				throw new Exception("Your confirmation code is invalid.");

			// Password
			if(empty($password) || empty($password2))
				throw new Exception("Your password cannot be blank.");

			if(0 != strcmp($password, $password2))
				throw new Exception("Your passwords do not match.");
				
			// Load the address
			if(null == ($address = DAO_Address::lookupAddress($email, true)))
				throw new Exception("You have provided an invalid email address.");
				
			// Verify address is unlinked
			if(!empty($address->contact_person_id))
				throw new Exception("The email address you provided is already associated with an account.");

			// Create the contact
			$salt = CerberusApplication::generatePassword(8);
			$fields = array(
				DAO_ContactPerson::EMAIL_ID => $address->id,
				DAO_ContactPerson::LAST_LOGIN => time(),
				DAO_ContactPerson::CREATED => time(),
				DAO_ContactPerson::AUTH_SALT => $salt,
				DAO_ContactPerson::AUTH_PASSWORD => md5($salt.md5($password)),
			);
			$contact_person_id = DAO_ContactPerson::create($fields);
			
			if(empty($contact_person_id) || null == ($contact = DAO_ContactPerson::get($contact_person_id)))
				throw new Exception("There was an error creating your account.");
				
			// Link email
			DAO_Address::update($address->id,array(
				DAO_Address::CONTACT_PERSON_ID => $contact_person_id,
			));
			
			// Delete confirmation and one-time login token
			DAO_ConfirmationCode::delete($code->id);
			
			// Log in the session
			$umsession->login($contact);
			
			$address_uri = urlencode(str_replace(array('@','.'),array('_at_','_dot_'),$address->email));
			header("Location: " . $url_writer->write('c=account&a=email&address='.$address_uri, true));
			exit;
				
		} catch(Exception $e) {
			$tpl->assign('error', $e->getMessage());
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'login','register','confirm')));
	}
	
	function doRecoverAction() {
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$url_writer = DevblocksPlatform::getUrlService();
		
		try {
			// Verify email is a contact
			if(null == ($address = DAO_Address::lookupAddress($email, false))) {
				throw new Exception("The email address you provided is not registered.");
			}
			
			if(empty($address->contact_person_id) || null == ($contact = DAO_ContactPerson::get($address->contact_person_id))) {
				throw new Exception("The email address you provided is not registered.");
			}
			
			// Generate + send confirmation
			$fields = array(
				DAO_ConfirmationCode::CONFIRMATION_CODE => CerberusApplication::generatePassword(8),
				DAO_ConfirmationCode::NAMESPACE_KEY => 'support_center.login.recover',
				DAO_ConfirmationCode::META_JSON => json_encode(array(
					'contact_id' => $contact->id,
					'address_id' => $address->id,
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
			
		} catch (Exception $e) {
			$tpl->assign('error', $e->getMessage());
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'login','forgot')));
			return;
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'login','forgot','confirm')));
	}
	
	function recoverAccountAction() {
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
		@$confirm = DevblocksPlatform::importGPC($_REQUEST['confirm'],'string','');
		
		$umsession = UmPortalHelper::getSession();
		$url_writer = DevblocksPlatform::getUrlService();
		$tpl = DevblocksPlatform::getTemplateService();
		
		try {
			// Verify email is a contact
			if(null == ($address = DAO_Address::lookupAddress($email, false))) {
				throw new Exception("The email address you provided is not registered.");
			}
			
			$tpl->assign('email', $address->email);
			
			if(empty($address->contact_person_id) || null == ($contact = DAO_ContactPerson::get($address->contact_person_id))) {
				throw new Exception("The email address you provided is not registered.");
			}
			
			// Lookup code
			if(null == ($code = DAO_ConfirmationCode::getByCode('support_center.login.recover', $confirm)))
				throw new Exception("Your confirmation code is invalid.");
			
			// Compare to contact
			if(!isset($code->meta['contact_id']) || $contact->id != $code->meta['contact_id'])
				throw new Exception("Your confirmation code is invalid.");
				
			// Compare to email address
			if(!isset($code->meta['address_id']) || $address->id != $code->meta['address_id'])
				throw new Exception("Your confirmation code is invalid.");
				
			// Success (delete token and one-time log in token)
			DAO_ConfirmationCode::delete($code->id);
			$umsession->login($contact);
			header("Location: " . $url_writer->write('c=account&a=password', true));
			exit;
			
		} catch (Exception $e) {
			$tpl->assign('error', $e->getMessage());
			
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'login','forgot','confirm')));
	}	
	
	/**
	 * pull auth info out of $_POST, check it, return user_id or false
	 * 
	 * @return boolean whether login succeeded
	 */
	function authenticateAction() {
		$umsession = UmPortalHelper::getSession();
		$tpl = DevblocksPlatform::getTemplateService();
		$url_writer = DevblocksPlatform::getUrlService();

		@$email = DevblocksPlatform::importGPC($_REQUEST['email']);
		@$pass = DevblocksPlatform::importGPC($_REQUEST['password']);

		// Clear the past session
		$umsession->logout();
		
		try {
			// Find the address
			if(null == ($addy = DAO_Address::lookupAddress($email, FALSE)))
				throw new Exception("Login failed.");
				
			// Not registered
			if(empty($addy->contact_person_id) || null == ($contact = DAO_ContactPerson::get($addy->contact_person_id)))
				throw new Exception("Login failed.");
			
			// Compare salt
			if(0 != strcmp(md5($contact->auth_salt.md5($pass)),$contact->auth_password))
				throw new Exception("Login failed.");	
			
			$umsession->login($contact);
			header("Location: " . $url_writer->write('', true));
			exit;
			
		} catch (Exception $e) {
			$tpl->assign('error', $e->getMessage());
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'login')));
	}
};

class ScOpenIDLoginAuthenticator extends Extension_ScLoginAuthenticator {
	function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::getTemplateService();
		$umsession = UmPortalHelper::getSession();
		
		$stack = $response->path;
		@$module = array_shift($stack);
		
		switch($module) {
			case 'register':
				$tpl->assign('openid_url', $umsession->getProperty('register.openid_claimed_id',''));
				$tpl->assign('email', $umsession->getProperty('register.email',''));
				
				if(isset($stack[0]) && 0==strcasecmp('confirm',$stack[0])) {
					$tpl->display("devblocks:cerberusweb.support_center:portal_".UmPortalHelper::getCode().":support_center/login/openid/register_confirm.tpl");
				} else {
					$tpl->display("devblocks:cerberusweb.support_center:portal_".UmPortalHelper::getCode().":support_center/login/openid/register.tpl");
				}
				break;
			case 'forgot':
				if(isset($stack[0]) && 0==strcasecmp('confirm',$stack[0])) {
					$tpl->display("devblocks:cerberusweb.support_center:portal_".UmPortalHelper::getCode().":support_center/login/openid/forgot_confirm.tpl");
				} else {
					$tpl->display("devblocks:cerberusweb.support_center:portal_".UmPortalHelper::getCode().":support_center/login/openid/forgot.tpl");
				}
				break;
			default:
				$tpl->display("devblocks:cerberusweb.support_center:portal_".UmPortalHelper::getCode().":support_center/login/openid/login.tpl");
				break;
		}
	}	
	
	function discoverAction() {
		@$openid_url = DevblocksPlatform::importGPC($_REQUEST['openid_url']);
				
		$openid = DevblocksPlatform::getOpenIDService();
		$url_writer = DevblocksPlatform::getUrlService();
		
		$return_url = $url_writer->write('c=login&a=authenticate', true);
		
		// Handle invalid URLs
		if(false === ($auth_url = $openid->getAuthUrl($openid_url, $return_url))) {
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl->assign('error', 'The OpenID you provided is invalid.');
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'login')));
			
		} else {
			header("Location: " . $auth_url);
			exit;
		}
	}
	
	function doRegisterAction() {
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$url_writer = DevblocksPlatform::getUrlService();
		$umsession = UmPortalHelper::getSession();
		
		try {
			// Validate
			$address_parsed = imap_rfc822_parse_adrlist($email,'host');
			if(empty($email) || empty($address_parsed) || !is_array($address_parsed) || empty($address_parsed[0]->host) || $address_parsed[0]->host=='host')
				throw new Exception("The email address you provided is invalid.");
			
			// Check to see if the address is currently assigned to an account
			if(null != ($address = DAO_Address::lookupAddress($email, false)) && !empty($address->contact_person_id))
				throw new Exception("The provided email address is already associated with an account.");
			
			// Update the preferred email address
			$umsession->setProperty('register.email', $email);
				
			// Send a confirmation code
			$fields = array(
				DAO_ConfirmationCode::CONFIRMATION_CODE => CerberusApplication::generatePassword(8),
				DAO_ConfirmationCode::NAMESPACE_KEY => 'support_center.openid.register.verify',
				DAO_ConfirmationCode::META_JSON => json_encode(array(
					'email' => $email,
				)),
				DAO_ConfirmationCode::CREATED => time(),
			);
			DAO_ConfirmationCode::create($fields);

			// Quick send
			$msg = sprintf(
				"Your confirmation code: %s",
				urlencode($fields[DAO_ConfirmationCode::CONFIRMATION_CODE])
			);
			CerberusMail::quickSend($email,"Please confirm your email address", $msg);
				
		} catch(Exception $e) {
			$tpl->assign('error', $e->getMessage());
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'login','register')));
			return;
			
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'login','register','confirm')));
	}
	
	function doRegisterConfirmAction() {
		@$confirm = DevblocksPlatform::importGPC($_REQUEST['confirm'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$url_writer = DevblocksPlatform::getUrlService();
		$umsession = UmPortalHelper::getSession();
		
		try {
			// Load the session (OpenID + email)
			$openid_claimed_id = $umsession->getProperty('register.openid_claimed_id', '');
			$email = $umsession->getProperty('register.email', '');

			// We need the basics in place
			if(empty($openid_claimed_id) || empty($email)) {
				header("Location: " . $url_writer->write('c=login', true));
				exit;
			}

			// Lookup code
			if(null == ($code = DAO_ConfirmationCode::getByCode('support_center.openid.register.verify', $confirm)))
				throw new Exception("Your confirmation code is invalid.");

			// Compare to address
			if(!isset($code->meta['email']) || 0 != strcasecmp($email, $code->meta['email']))
				throw new Exception("Your confirmation code is invalid.");
				
			// Load the address
			if(null == ($address = DAO_Address::lookupAddress($email, true)))
				throw new Exception("You have provided an invalid email address.");
				
			// Verify address is unlinked
			if(!empty($address->contact_person_id))
				throw new Exception("The email address you provided is already associated with an account.");

			// Create the contact
			$fields = array(
				DAO_ContactPerson::EMAIL_ID => $address->id,
				DAO_ContactPerson::LAST_LOGIN => time(),
				DAO_ContactPerson::CREATED => time(),
				DAO_ContactPerson::AUTH_SALT => substr($code->confirmation_code,0,3),
				DAO_ContactPerson::AUTH_PASSWORD => md5(substr($code->confirmation_code,0,3).md5($code->confirmation_code)),
			);
			$contact_person_id = DAO_ContactPerson::create($fields);
			
			if(empty($contact_person_id) || null == ($contact = DAO_ContactPerson::get($contact_person_id)))
				throw new Exception("There was an error creating your account.");
				
			// Link email
			DAO_Address::update($address->id,array(
				DAO_Address::CONTACT_PERSON_ID => $contact_person_id,
			));
			
			// Link OpenID
			DAO_OpenIdToContactPerson::addOpenId($openid_claimed_id, $contact_person_id);
				
			// Delete confirmation and one-time login token
			DAO_ConfirmationCode::delete($code->id);
			
			// Log in the session
			$umsession->login($contact);
			
			$address_uri = urlencode(str_replace(array('@','.'),array('_at_','_dot_'),$address->email));
			header("Location: " . $url_writer->write('c=account&a=email&address='.$address_uri, true));
			exit;
				
		} catch(Exception $e) {
			$tpl->assign('error', $e->getMessage());
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'login','register','confirm')));
	}
	
	function doRecoverAction() {
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$url_writer = DevblocksPlatform::getUrlService();
		
		try {
			// Verify email is a contact
			if(null == ($address = DAO_Address::lookupAddress($email, false))) {
				throw new Exception("The email address you provided is not registered.");
			}
			
			if(empty($address->contact_person_id) || null == ($contact = DAO_ContactPerson::get($address->contact_person_id))) {
				throw new Exception("The email address you provided is not registered.");
			}
			
			// Generate + send confirmation
			$fields = array(
				DAO_ConfirmationCode::CONFIRMATION_CODE => CerberusApplication::generatePassword(8),
				DAO_ConfirmationCode::NAMESPACE_KEY => 'support_center.openid.recover',
				DAO_ConfirmationCode::META_JSON => json_encode(array(
					'contact_id' => $contact->id,
					'address_id' => $address->id,
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
			
		} catch (Exception $e) {
			$tpl->assign('error', $e->getMessage());
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'login','forgot')));
			return;
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'login','forgot','confirm')));
	}
	
	function recoverAccountAction() {
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
		@$confirm = DevblocksPlatform::importGPC($_REQUEST['confirm'],'string','');
		
		$umsession = UmPortalHelper::getSession();
		$url_writer = DevblocksPlatform::getUrlService();
		$tpl = DevblocksPlatform::getTemplateService();
		
		try {
			// Verify email is a contact
			if(null == ($address = DAO_Address::lookupAddress($email, false))) {
				throw new Exception("The email address you provided is not registered.");
			}
			
			$tpl->assign('email', $address->email);
			
			if(empty($address->contact_person_id) || null == ($contact = DAO_ContactPerson::get($address->contact_person_id))) {
				throw new Exception("The email address you provided is not registered.");
			}
			
			// Lookup code
			if(null == ($code = DAO_ConfirmationCode::getByCode('support_center.openid.recover', $confirm)))
				throw new Exception("Your confirmation code is invalid.");
			
			// Compare to contact
			if(!isset($code->meta['contact_id']) || $contact->id != $code->meta['contact_id'])
				throw new Exception("Your confirmation code is invalid.");
				
			// Compare to email address
			if(!isset($code->meta['address_id']) || $address->id != $code->meta['address_id'])
				throw new Exception("Your confirmation code is invalid.");
				
			// Success (delete token and one-time log in token)
			DAO_ConfirmationCode::delete($code->id);
			$umsession->login($contact);
			header("Location: " . $url_writer->write('c=account&a=openid', true));
			exit;
			
		} catch (Exception $e) {
			$tpl->assign('error', $e->getMessage());
			
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'login','forgot','confirm')));
	}
	
	/**
	 * pull auth info out of $_POST, check it, return user_id or false
	 * 
	 * @return boolean whether login succeeded
	 */
	function authenticateAction() {
		$umsession = UmPortalHelper::getSession();
		$url_writer = DevblocksPlatform::getUrlService();
		$openid = DevblocksPlatform::getOpenIDService();
		$tpl = DevblocksPlatform::getTemplateService();

		// Clear the past session
		$umsession->logout();
		
		try {
			// Mode (Cancel)
			if(isset($_GET['openid_mode']))
			switch($_GET['openid_mode']) {
				case 'cancel':
					header("Location: " . $url_writer->write('c=login', true));
					exit;
					break;
					
				default:
					// If we failed validation
					if(!$openid->validate($_REQUEST))
						throw new Exception("Login failed.");
	
					// Get parameters
					$attribs = $openid->getAttributes($_REQUEST);
	
					// Compare OpenIDs
					$contact_id = DAO_OpenIdToContactPerson::getContactIdByOpenId($_REQUEST['openid_claimed_id']);
					
					if(null != ($contact = DAO_ContactPerson::get($contact_id))) {
						$umsession->login($contact);
						header("Location: " . $url_writer->write('', true));
						exit;
						
					} else {
						// Preserve the OpenID URL
						$umsession->setProperty('register.openid_claimed_id', $_REQUEST['openid_claimed_id']);
						
						// If we can introspect the email or name, save them too.
						if(isset($attribs['email']))
							$umsession->setProperty('register.email', $attribs['email']);
							
						if(isset($attribs['full_name'])) {
							$nameParts = explode(' ', $attribs['full_name']); 
							$umsession->setProperty('register.last_name', array_pop($nameParts));
							$umsession->setProperty('register.first_name', implode(' ',$nameParts));
						}
						
						//throw new Exception("The OpenID you provided is not registered.");
						header("Location: " . $url_writer->write('c=login&a=register', true));
						exit;
					}
					break;
			}
					
		} catch (Exception $e) {
			$tpl->assign('error', $e->getMessage());
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'login')));
	}
};

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
		$model->columnsHidden = $view->getColumnsHidden();
		
		$model->paramsEditable = $view->getEditableParams();
		$model->paramsDefault = $view->getParamsDefault();
		$model->paramsHidden = $view->getParamsHidden();
		$model->paramsRequired = $view->getParamsRequired();

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
		$inst->addColumnsHidden($model->columnsHidden);
		
		$inst->addParams($model->paramsEditable, true);
		$inst->addParamsDefault($model->paramsDefault);
		$inst->addParamsHidden($model->paramsHidden);
		$inst->addParamsRequired($model->paramsRequired);

		$inst->renderPage = $model->renderPage;
		$inst->renderLimit = $model->renderLimit;
		$inst->renderSortBy = $model->renderSortBy;
		$inst->renderSortAsc = $model->renderSortAsc;

		return $inst;
	}
};

class UmScRssController extends Extension_UmScController {
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
