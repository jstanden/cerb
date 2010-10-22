<?php
class UmScLoginController extends Extension_UmScController {
	function isVisible() {
		return true;
	}
	
	function signoutAction() {
		$umsession = UmPortalHelper::getSession();
		
		// Fall back
        $login_handler = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(), UmScApp::PARAM_LOGIN_HANDLER, 'sc.login.auth.default');

		if(null != ($handler = DevblocksPlatform::getExtension($login_handler, true))) {
			if($handler->signoff()) {
				// ...
			}
		}
		
		// Globally destroy
		$umsession->destroy();
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login')));
		exit;
	}
	
	function handleRequest(DevblocksHttpRequest $request) {
		$umsession = UmPortalHelper::getSession();

		$stack = $request->path;
		@array_shift($stack); // login
		
		@$a = DevblocksPlatform::importGPC($_REQUEST['a'],'string');

		if(empty($a)) {
    	    @$action = $stack[0] . 'Action';
		} else {
	    	@$action = $a . 'Action';
		}

		// Login extension
        $login_handler_id = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(), UmScApp::PARAM_LOGIN_HANDLER, 'sc.login.auth.default');

        // Try the extension subcontroller first (overload)
        if(null != ($handler = DevblocksPlatform::getExtension($login_handler_id, true)) 
        	&& method_exists($handler, $action)) {
				call_user_func(array($handler, $action));
        
		// Then try the login controller
		} elseif(method_exists($this, $action)) {
			call_user_func(array($this, $action));
		}
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
		$umsession = UmPortalHelper::getSession();

		$stack = $response->path;
		@array_shift($stack); // login
		
		// Fall back
        $login_handler_id = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(), UmScApp::PARAM_LOGIN_HANDLER, 'sc.login.auth.default');
		
		if(null != ($handler = DevblocksPlatform::getExtension($login_handler_id, true))) {
			$handler->writeResponse(new DevblocksHttpResponse($stack));
		}
	}
	
};