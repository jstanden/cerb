<?php
class UmScRegisterController extends Extension_UmScController {
	
	function isVisible() {
//		$umsession = UmPortalHelper::getSession();
//		$active_user = $umsession->getProperty('sc_login', null);
//		return !empty($active_user);
		return true;
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/templates/';

		$umsession = UmPortalHelper::getSession();
		$active_user = $umsession->getProperty('sc_login', null);
		
		$stack = $response->path;
		array_shift($stack); // register
		
		@$step = array_shift($stack);
		
		switch($step) {
			case 'forgot':
				$tpl->display("file:${tpl_path}portal/sc/module/register/forgot.tpl");
				break;
			case 'forgot2':
				$tpl->display("file:${tpl_path}portal/sc/module/register/forgot_confirm.tpl");
				break;
			case 'confirm':
				$tpl->display("file:${tpl_path}portal/sc/module/register/confirm.tpl");
				break;
			default:
				$tpl->display("file:${tpl_path}portal/sc/module/register/index.tpl");
				break;
		}
		
	}
	
	function doForgotAction() {
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');

		$tpl = DevblocksPlatform::getTemplateService();
		
		$settings = DevblocksPlatform::getPluginSettingsService();
		$from = $settings->get('cerberusweb.core',CerberusSettings::DEFAULT_REPLY_FROM,null);
		$from_personal = $settings->get('cerberusweb.core',CerberusSettings::DEFAULT_REPLY_PERSONAL,"Support Dept.");
		
		$url = DevblocksPlatform::getUrlService();
		try {
			$mail_service = DevblocksPlatform::getMailService();
			$mailer = $mail_service->getMailer(CerberusMail::getMailerDefaults());
			
			$code = CerberusApplication::generatePassword(8);
			
			if(!empty($email) && null != ($addy = DAO_Address::lookupAddress($email, false))) {
				$fields = array(
					DAO_Address::IS_REGISTERED => 0,
					DAO_Address::PASS => $code,
				);
				DAO_Address::update($addy->id, $fields);
				
			} else {
				$tpl->assign('register_error', sprintf("'%s' is not a registered e-mail address.",$email));
				DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'register','forgot')));
				return;
			}
			
			$message = $mail_service->createMessage();
			$message->addTo($email);
			$message->setFrom(array($from => $from_personal));
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
			
			$headers = $message->getHeaders();
			
			$headers->addTextHeader('X-Mailer','Cerberus Helpdesk (Build '.APP_BUILD.')');
			
			$result = $mailer->send($message);
		}
		catch (Exception $e) {
			$tpl->assign('register_error', 'Fatal error encountered while sending forgot password confirmation code.');
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'register','forgot')));
			return;
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'register','forgot2')));
	}		
	
	function doForgotConfirmAction() {
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
		@$code = trim(DevblocksPlatform::importGPC($_REQUEST['code'],'string',''));
		@$pass = DevblocksPlatform::importGPC($_REQUEST['pass'],'string','');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('register_email', $email);
		$tpl->assign('register_code', $code);
		
		if(!empty($email) && !empty($pass) && !empty($code)) {
			if(null != ($addy = DAO_Address::lookupAddress($email, false))
				&& !$addy->is_registered 
				&& !empty($addy->pass) 
				&& 0 == strcasecmp($code,$addy->pass)) {
					$fields = array(
						DAO_Address::IS_REGISTERED => 1,
						DAO_Address::PASS => md5($pass),
					);
					DAO_Address::update($addy->id, $fields);
				
			} else {
				$tpl->assign('register_error', sprintf("The confirmation code you entered does not match our records.  Try again."));
				DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'register','forgot2')));
				return;
			}
			
		} else {
			$tpl->assign('register_error', sprintf("You must enter a valid e-mail address, confirmation code and desired password to continue."));
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'register','forgot2')));
			return;
		}
	}
		
	function doRegisterAction() {
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');

		$tpl = DevblocksPlatform::getTemplateService();
		
		$settings = DevblocksPlatform::getPluginSettingsService();
		$from = $settings->get('cerberusweb.core',CerberusSettings::DEFAULT_REPLY_FROM,null);
		$from_personal = $settings->get('cerberusweb.core',CerberusSettings::DEFAULT_REPLY_PERSONAL,"Support Dept.");
		
		$url = DevblocksPlatform::getUrlService();
		$mail_service = DevblocksPlatform::getMailService();
		$mailer = $mail_service->getMailer(CerberusMail::getMailerDefaults());
		
		$code = CerberusApplication::generatePassword(8);
		
		if(!empty($email) && null != ($addy = DAO_Address::lookupAddress($email, true))) {
			
			// Already registered?
			if($addy->is_registered) {
				$tpl->assign('register_error', sprintf("'%s' is already registered.",$email));
				DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'register')));
				return;
			}
			
			$fields = array(
				DAO_Address::IS_REGISTERED => 0,
				DAO_Address::PASS => $code
			);
			DAO_Address::update($addy->id, $fields);
			
		} else {
			$tpl->assign('register_error', sprintf("'%s' is an invalid e-mail address.",$email));
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'register')));
			return;
		}
		
		$message = $mail_service->createMessage();
		$message->setTo($email);
		$message->setFrom(array($from => $from_personal));
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
		
		$headers = $message->getHeaders();
		
		$headers->addTextHeader('X-Mailer','Cerberus Helpdesk (Build '.APP_BUILD.')');
		
		$result = $mailer->send($message);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'register','confirm')));
	}
	
	function doRegisterConfirmAction() {
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
		@$code = trim(DevblocksPlatform::importGPC($_REQUEST['code'],'string',''));
		@$pass = DevblocksPlatform::importGPC($_REQUEST['pass'],'string','');
		
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('register_email', $email);
		$tpl->assign('register_code', $code);
		
		if(!empty($email) && !empty($pass) && !empty($code)) {
			if(null != ($addy = DAO_Address::lookupAddress($email, false))
				&& !$addy->is_registered 
				&& !empty($addy->pass) 
				&& 0 == strcasecmp($code,$addy->pass)) {
					$fields = array(
						DAO_Address::IS_REGISTERED => 1,
						DAO_Address::PASS => md5($pass)
					);
					DAO_Address::update($addy->id, $fields);
				
			} else {
				$tpl->assign('register_error', sprintf("The confirmation code you entered does not match our records.  Try again."));
				DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'register','confirm')));
				return;
			}
			
		} else {
			$tpl->assign('register_error', sprintf("You must enter a valid e-mail address, confirmation code and desired password to continue."));
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'register','confirm')));
			return;
		}
	}
	
};