<?php
class PageSection_SetupSecurity extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(Extension_ConfigTab::POINT, 'security');
		
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
			
			echo json_encode(array('status'=>true));
			return;
			
		} catch(Exception $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;	
		}
	}
};