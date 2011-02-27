<?php
class PageSection_SetupMailOutgoing extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$settings = DevblocksPlatform::getPluginSettingsService();
		
		$visit->set(Extension_ConfigTab::POINT, 'mail_outgoing');
		
		$smtp_host = $settings->get('cerberusweb.core',CerberusSettings::SMTP_HOST,CerberusSettingsDefaults::SMTP_HOST);
		$smtp_port = $settings->get('cerberusweb.core',CerberusSettings::SMTP_PORT,CerberusSettingsDefaults::SMTP_PORT);
		$smtp_auth_enabled = $settings->get('cerberusweb.core',CerberusSettings::SMTP_AUTH_ENABLED,CerberusSettingsDefaults::SMTP_AUTH_ENABLED);
		
		if ($smtp_auth_enabled) {
			$smtp_auth_user = $settings->get('cerberusweb.core',CerberusSettings::SMTP_AUTH_USER,CerberusSettingsDefaults::SMTP_AUTH_USER);
			$smtp_auth_pass = $settings->get('cerberusweb.core',CerberusSettings::SMTP_AUTH_PASS,CerberusSettingsDefaults::SMTP_AUTH_PASS); 
		} else {
			$smtp_auth_user = '';
			$smtp_auth_pass = ''; 
		}
		
		$smtp_enc = $settings->get('cerberusweb.core',CerberusSettings::SMTP_ENCRYPTION_TYPE,CerberusSettingsDefaults::SMTP_ENCRYPTION_TYPE);
		$smtp_max_sends = $settings->get('cerberusweb.core',CerberusSettings::SMTP_MAX_SENDS,CerberusSettingsDefaults::SMTP_MAX_SENDS);
		
		// Signature
		
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, null, $token_labels, $token_values);
		$tpl->assign('token_labels', $token_labels);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/mail_outgoing/index.tpl');
	}
	
	function saveJsonAction() {
		try {
			$worker = CerberusApplication::getActiveWorker();
		
			if(!$worker || !$worker->is_superuser)
				throw new Exception("You are not an administrator.");
				
		    @$default_reply_address = DevblocksPlatform::importGPC($_REQUEST['sender_address'],'string');
		    @$default_reply_personal = DevblocksPlatform::importGPC($_REQUEST['sender_personal'],'string');
		    @$default_signature = DevblocksPlatform::importGPC($_POST['default_signature'],'string');
		    @$default_signature_pos = DevblocksPlatform::importGPC($_POST['default_signature_pos'],'integer',0);
		    
		    $settings = DevblocksPlatform::getPluginSettingsService();
		    $settings->set('cerberusweb.core',CerberusSettings::DEFAULT_REPLY_FROM, $default_reply_address);
		    $settings->set('cerberusweb.core',CerberusSettings::DEFAULT_REPLY_PERSONAL, $default_reply_personal);
		    $settings->set('cerberusweb.core',CerberusSettings::DEFAULT_SIGNATURE, $default_signature);
		    $settings->set('cerberusweb.core',CerberusSettings::DEFAULT_SIGNATURE_POS, $default_signature_pos);
		    
		    echo json_encode(array('status'=>true));
		    return;
		    
		} catch(Exception $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;
			
		}
	}
	
	function saveSmtpJsonAction() {
		try {
			$worker = CerberusApplication::getActiveWorker();
		
			if(!$worker || !$worker->is_superuser)
				throw new Exception("You are not an administrator.");
				
		    @$smtp_host = DevblocksPlatform::importGPC($_REQUEST['smtp_host'],'string','localhost');
		    @$smtp_port = DevblocksPlatform::importGPC($_REQUEST['smtp_port'],'integer',25);
		    @$smtp_enc = DevblocksPlatform::importGPC($_REQUEST['smtp_enc'],'string','None');
		    @$smtp_timeout = DevblocksPlatform::importGPC($_REQUEST['smtp_timeout'],'integer',30);
		    @$smtp_max_sends = DevblocksPlatform::importGPC($_REQUEST['smtp_max_sends'],'integer',20);
	
		    @$smtp_auth_enabled = DevblocksPlatform::importGPC($_REQUEST['smtp_auth_enabled'],'integer', 0);
		    if($smtp_auth_enabled) {
			    @$smtp_auth_user = DevblocksPlatform::importGPC($_REQUEST['smtp_auth_user'],'string');
			    @$smtp_auth_pass = DevblocksPlatform::importGPC($_REQUEST['smtp_auth_pass'],'string');
		    	
		    } else { // need to clear auth info when smtp auth is disabled
			    @$smtp_auth_user = '';
			    @$smtp_auth_pass = '';
		    }
		    
			if(empty($smtp_host))
				throw new Exception("SMTP server is blank.");
				
		    $this->_testSmtp($smtp_host, $smtp_port, $smtp_enc, $smtp_auth_enabled, $smtp_auth_user, $smtp_auth_pass);
		    
	    	$settings = DevblocksPlatform::getPluginSettingsService();
		    $settings->set('cerberusweb.core',CerberusSettings::SMTP_HOST, $smtp_host);
		    $settings->set('cerberusweb.core',CerberusSettings::SMTP_PORT, $smtp_port);
		    $settings->set('cerberusweb.core',CerberusSettings::SMTP_AUTH_ENABLED, $smtp_auth_enabled);
		    $settings->set('cerberusweb.core',CerberusSettings::SMTP_AUTH_USER, $smtp_auth_user);
		    $settings->set('cerberusweb.core',CerberusSettings::SMTP_AUTH_PASS, $smtp_auth_pass);
		    $settings->set('cerberusweb.core',CerberusSettings::SMTP_ENCRYPTION_TYPE, $smtp_enc);
		    $settings->set('cerberusweb.core',CerberusSettings::SMTP_TIMEOUT, !empty($smtp_timeout) ? $smtp_timeout : 30);
		    $settings->set('cerberusweb.core',CerberusSettings::SMTP_MAX_SENDS, !empty($smtp_max_sends) ? $smtp_max_sends : 20);
			
		    echo json_encode(array('status'=>true));
		    return;
		    
		} catch(Exception $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;
			
		}
	}
	
	private function _testSmtp($host, $port=25, $smtp_enc=null, $smtp_auth=null, $smtp_user=null, $smtp_pass=null) {
		if(empty($host))
			throw new Exception("SMTP server is blank.");
		
		try {
			$mail_service = DevblocksPlatform::getMailService();
			$mailer = $mail_service->getMailer(array(
				'host' => $host,
				'port' => $port,
				'auth_user' => $smtp_user,
				'auth_pass' => $smtp_pass,
				'enc' => $smtp_enc,
			));
			
			@$transport = $mailer->getTransport();
			@$transport->start();
			@$transport->stop();
			return true;
			
		} catch(Exception $e) {
			throw new Exception($e->getMessage());
			return false;
			
		}
	}	
}