<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2015, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerbweb.com	    http://www.webgroupmedia.com/
***********************************************************************/

class PageSection_SetupAvatars extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(ChConfigurationPage::ID, 'avatars');
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/avatars/index.tpl');
	}
	
	function saveJsonAction() {
		try {
			$worker = CerberusApplication::getActiveWorker();
			
			if(!$worker || !$worker->is_superuser)
				throw new Exception("You are not a superuser.");
			
			@$avatar_default_style_contact = DevblocksPlatform::importGPC($_POST['avatar_default_style_contact'],'string',CerberusSettingsDefaults::AVATAR_DEFAULT_STYLE_CONTACT);
			@$avatar_default_style_worker = DevblocksPlatform::importGPC($_POST['avatar_default_style_worker'],'string',CerberusSettingsDefaults::AVATAR_DEFAULT_STYLE_WORKER);
	
			$settings = DevblocksPlatform::getPluginSettingsService();
			$settings->set('cerberusweb.core',CerberusSettings::AVATAR_DEFAULT_STYLE_CONTACT, $avatar_default_style_contact);
			$settings->set('cerberusweb.core',CerberusSettings::AVATAR_DEFAULT_STYLE_WORKER, $avatar_default_style_worker);
			
			echo json_encode(array('status'=>true));
			return;
				
		} catch(Exception $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;
		}
	}
};