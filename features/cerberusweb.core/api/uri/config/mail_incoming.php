<?php
class PageSection_SetupMailIncoming extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$settings = DevblocksPlatform::getPluginSettingsService();
		
		$visit->set(Extension_ConfigTab::POINT, 'mail_incoming');
		
		// POP3
		$pop3_accounts = DAO_Mail::getPop3Accounts();
		$tpl->assign('pop3_accounts', $pop3_accounts);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/mail_incoming/index.tpl');
	}
	
	function saveJsonAction() {
		try {
			$translate = DevblocksPlatform::getTranslationService();
			$worker = CerberusApplication::getActiveWorker();
			
			if(!$worker || !$worker->is_superuser)
				throw new Exception("You are not an administrator.");
			
		    @$parser_autoreq = DevblocksPlatform::importGPC($_POST['parser_autoreq'],'integer',0);
		    @$parser_autoreq_exclude = DevblocksPlatform::importGPC($_POST['parser_autoreq_exclude'],'string','');
		    @$attachments_enabled = DevblocksPlatform::importGPC($_POST['attachments_enabled'],'integer',0);
		    @$attachments_max_size = DevblocksPlatform::importGPC($_POST['attachments_max_size'],'integer',10);
			
		    $settings = DevblocksPlatform::getPluginSettingsService();
		    $settings->set('cerberusweb.core',CerberusSettings::PARSER_AUTO_REQ, $parser_autoreq);
		    $settings->set('cerberusweb.core',CerberusSettings::PARSER_AUTO_REQ_EXCLUDE, $parser_autoreq_exclude);
		    $settings->set('cerberusweb.core',CerberusSettings::ATTACHMENTS_ENABLED, $attachments_enabled);
		    $settings->set('cerberusweb.core',CerberusSettings::ATTACHMENTS_MAX_SIZE, $attachments_max_size);
		    
		    echo json_encode(array('status'=>true));
		    return;
			
		} catch (Exception $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;
			
		}
		
	}
}