<?php
/***********************************************************************
| Cerb(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2012, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/

class PageSection_SetupSecurity extends Extension_PageSection {
	function render() {
		if(ONDEMAND_MODE)
			return;
		
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(ChConfigurationPage::ID, 'security');
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/security/index.tpl');
	}
	
	function saveJsonAction() {
		try {
			$worker = CerberusApplication::getActiveWorker();
			
			if(!$worker || !$worker->is_superuser)
				throw new Exception("You are not a superuser.");
			
			if(!ONDEMAND_MODE) {
				@$authorized_ips = DevblocksPlatform::importGPC($_POST['authorized_ips'],'string','');
				DevblocksPlatform::setPluginSetting('cerberusweb.core',CerberusSettings::AUTHORIZED_IPS, $authorized_ips);
			}
			
			@$session_lifespan = DevblocksPlatform::importGPC($_POST['session_lifespan'],'integer',0);
			DevblocksPlatform::setPluginSetting('cerberusweb.core',CerberusSettings::SESSION_LIFESPAN, $session_lifespan);
			
			echo json_encode(array('status'=>true));
			return;
			
		} catch(Exception $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;	
		}
	}
};