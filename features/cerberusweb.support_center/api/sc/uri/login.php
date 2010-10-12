<?php
class UmScLoginController extends Extension_UmScController {
	function isVisible() {
//		$umsession = UmPortalHelper::getSession();
//		$active_user = $umsession->getProperty('sc_login', null);
//		return !empty($active_user);
		return true;
	}
	
	function authenticateAction() {
//		if(!$this->allow_logins)
//			die();

		// Fall back
        $login_handler = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(), UmScApp::PARAM_LOGIN_HANDLER, 'sc.login.auth.default');

		if(null != ($handler = DevblocksPlatform::getExtension($login_handler, true))) {
			if($handler->authenticate()) {
				// ...
				DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login')));
			}
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login','failed')));
		exit;
	}
	
	function signoutAction() {
		// Fall back
        $login_handler = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(), UmScApp::PARAM_LOGIN_HANDLER, 'sc.login.auth.default');

		if(null != ($handler = DevblocksPlatform::getExtension($login_handler, true))) {
			if($handler->signoff()) {
				// ...
			}
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'login')));
		exit;
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::getTemplateService();

		$umsession = UmPortalHelper::getSession();
		$active_user = $umsession->getProperty('sc_login', null);

		// Fall back
        $login_handler = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(), UmScApp::PARAM_LOGIN_HANDLER, 'sc.login.auth.default');
		
		if(null != ($handler = DevblocksPlatform::getExtension($login_handler, true))) {
			if($handler->renderLoginForm()) {
				// ...
			}
		}
        
//		$stack = $response->path;
//		array_shift($stack); // register
//		
//		@$step = array_shift($stack);
//		
//		switch($step) {
//			case 'forgot':
//				$tpl->display("devblocks:cerberusweb.support_center:portal_".UmPortalHelper::getCode() . ":support_center/register/forgot.tpl");
//				break;
//			case 'forgot2':
//				$tpl->display("devblocks:cerberusweb.support_center:portal_".UmPortalHelper::getCode() . ":support_center/register/forgot_confirm.tpl");
//				break;
//			case 'confirm':
//				$tpl->display("devblocks:cerberusweb.support_center:portal_".UmPortalHelper::getCode() . ":support_center/register/confirm.tpl");
//				break;
//			default:
//				$tpl->display("devblocks:cerberusweb.support_center:portal_".UmPortalHelper::getCode() . ":support_center/login/index.tpl");
//				break;
//		}
		
	}
	
};