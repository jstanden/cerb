<?php
class UmScLoginController extends Extension_UmScController {
	function isVisible() {
		return true;
	}
	
	function signoutAction() {
		$umsession = ChPortalHelper::getSession();
		
		if(null != ($login_extension = UmScApp::getLoginExtensionActive(ChPortalHelper::getCode()))) {
			if($login_extension->signoff()) {
				// ...
			}
		}
		
		// Globally destroy
		$umsession->destroy();
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login')));
		exit;
	}
	
	function providerAction() {
		$umsession = ChPortalHelper::getSession();
		$request = DevblocksPlatform::getHttpRequest();
		
		$stack = $request->path;
		@array_shift($stack); // portal
		@array_shift($stack); // xxxxxx
		@array_shift($stack); // login
		@array_shift($stack); // provider
		@$extension_id = array_shift($stack);
		
		if(!empty($extension_id) && null != ($ext = DevblocksPlatform::getExtension($extension_id, true, true)) 
			&& $ext instanceof Extension_ScLoginAuthenticator) {
				$umsession->setProperty('login_method', $ext->manifest->id);
		} else {
			$umsession->setProperty('login_method', null);
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login')));
	}
	
	function handleRequest(DevblocksHttpRequest $request) {
		$umsession = ChPortalHelper::getSession();

		$stack = $request->path;
		@array_shift($stack); // login
		
		@$a = DevblocksPlatform::importGPC($_REQUEST['a'],'string');

		if(empty($a)) {
			@$action = $stack[0] . 'Action';
		} else {
			@$action = $a . 'Action';
		}

		// Login extension
		// Try the extension subcontroller first (overload)
		if(null != ($login_extension = UmScApp::getLoginExtensionActive(ChPortalHelper::getCode())) 
			&& method_exists($login_extension, $action)) {
				call_user_func(array($login_extension, $action));
		
		// Then try the login controller
		} elseif(method_exists($this, $action)) {
			call_user_func(array($this, $action));
		}
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
		$umsession = ChPortalHelper::getSession();
		$tpl = DevblocksPlatform::getTemplateService();

		$stack = $response->path;
		@array_shift($stack); // login

		$login_extension_active = UmScApp::getLoginExtensionActive(ChPortalHelper::getCode());
		$tpl->assign('login_extension_active', $login_extension_active);
		
		// Fall back
		if(null != ($login_extension = UmScApp::getLoginExtensionActive(ChPortalHelper::getCode()))) {
			$login_extension->writeResponse(new DevblocksHttpResponse($stack));
		}
	}
	
	function configure(Model_CommunityTool $instance) {
		$tpl = DevblocksPlatform::getTemplateService();

		// Login extensions
		$login_extensions = DevblocksPlatform::getExtensions('usermeet.login.authenticator');
		if(!empty($login_extensions)) {
			DevblocksPlatform::sortObjects($login_extensions, 'name');
			$tpl->assign('login_extensions', $login_extensions);
		}

		// Enabled login extensions
		$login_extensions_enabled = UmScApp::getLoginExtensionsEnabled($instance->code);
		$tpl->assign('login_extensions_enabled', $login_extensions_enabled);
		
		$tpl->display("devblocks:cerberusweb.support_center::portal/sc/config/module/login.tpl");
	}
	
	function saveConfiguration(Model_CommunityTool $instance) {
		@$login_extensions_enabled = DevblocksPlatform::importGPC($_POST['login_extensions'],'array',array());

		$login_extensions = DevblocksPlatform::getExtensions('usermeet.login.authenticator', false, true);
		
		// Validate
		foreach($login_extensions_enabled as $idx => $login_extension_enabled) {
			if(!isset($login_extensions[$login_extension_enabled]))
				unset($login_extensions_enabled[$idx]);
		}

		DAO_CommunityToolProperty::set($instance->code, UmScApp::PARAM_LOGIN_EXTENSIONS, implode(',', $login_extensions_enabled));
	}	
};