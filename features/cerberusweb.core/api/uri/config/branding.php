<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2011, WebGroup Media LLC
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

class PageSection_SetupBranding extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(ChConfigurationPage::ID, 'branding');
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/branding/index.tpl');
	}
	
	function saveJsonAction() {
		try {
			$worker = CerberusApplication::getActiveWorker();
			
			if(!$worker || !$worker->is_superuser)
				throw new Exception("You are not a superuser.");
			
		    @$title = DevblocksPlatform::importGPC($_POST['title'],'string','');
		    @$logo = DevblocksPlatform::importGPC($_POST['logo'],'string');
	
		    // [TODO] New branding
		    if(empty($title))
		    	$title = 'Cerberus Helpdesk :: Group-based Email Management';
		    	
		    $settings = DevblocksPlatform::getPluginSettingsService();
		    $settings->set('cerberusweb.core',CerberusSettings::HELPDESK_TITLE, $title);
		    $settings->set('cerberusweb.core',CerberusSettings::HELPDESK_LOGO_URL, $logo); // [TODO] Enforce some kind of max resolution?
		    
			echo json_encode(array('status'=>true));
			return;
		    	
		} catch(Exception $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;
		}
	}
};