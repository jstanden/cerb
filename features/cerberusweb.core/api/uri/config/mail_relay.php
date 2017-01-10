<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2017, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.io/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.io	    http://webgroup.media
***********************************************************************/

class PageSection_SetupMailRelay extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();

		$visit->set(ChConfigurationPage::ID, 'mail_relay');
		
		$replyto_default = DAO_AddressOutgoing::getDefault();
		$tpl->assign('replyto_default', $replyto_default);

		$tpl->display('devblocks:cerberusweb.core::configuration/section/mail_relay/index.tpl');
	}
	
	function saveJsonAction() {
		try {
			$translate = DevblocksPlatform::getTranslationService();
			$worker = CerberusApplication::getActiveWorker();
			
			if(!$worker || !$worker->is_superuser)
				throw new Exception("You are not an administrator.");
			
			@$relay_disable = DevblocksPlatform::importGPC($_POST['relay_disable'],'integer',0);
			@$relay_disable_auth = DevblocksPlatform::importGPC($_POST['relay_disable_auth'],'integer',0);
			@$relay_spoof_from = DevblocksPlatform::importGPC($_POST['relay_spoof_from'],'integer',0);
			
			// Save
			
			$settings = DevblocksPlatform::getPluginSettingsService();
			$settings->set('cerberusweb.core',CerberusSettings::RELAY_DISABLE, $relay_disable);
			$settings->set('cerberusweb.core',CerberusSettings::RELAY_DISABLE_AUTH, $relay_disable_auth);
			$settings->set('cerberusweb.core',CerberusSettings::RELAY_SPOOF_FROM, $relay_spoof_from);
			
			echo json_encode(array('status'=>true));
			return;
			
		} catch (Exception $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;
			
		}
				
	}
}
