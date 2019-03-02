<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/

class PageSection_SetupAvatars extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(ChConfigurationPage::ID, 'avatars');
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/avatars/index.tpl');
	}
	
	function saveJsonAction() {
		try {
			$worker = CerberusApplication::getActiveWorker();
			
			header('Content-Type: application/json; charset=utf-8');
			
			if(!$worker || !$worker->is_superuser)
				throw new Exception(DevblocksPlatform::translate('error.core.no_acl.admin'));
			
			@$avatar_default_style_contact = DevblocksPlatform::importGPC($_POST['avatar_default_style_contact'],'string',CerberusSettingsDefaults::AVATAR_DEFAULT_STYLE_CONTACT);
			@$avatar_default_style_worker = DevblocksPlatform::importGPC($_POST['avatar_default_style_worker'],'string',CerberusSettingsDefaults::AVATAR_DEFAULT_STYLE_WORKER);
	
			$settings = DevblocksPlatform::services()->pluginSettings();
			$settings->set('cerberusweb.core',CerberusSettings::AVATAR_DEFAULT_STYLE_CONTACT, $avatar_default_style_contact);
			$settings->set('cerberusweb.core',CerberusSettings::AVATAR_DEFAULT_STYLE_WORKER, $avatar_default_style_worker);
			
			echo json_encode([
				'status' => true,
				'message' => DevblocksPlatform::translate('success.saved_changes'),
			]);
			return;
				
		} catch(Exception $e) {
			echo json_encode([
				'status' => false,
				'error' => $e->getMessage(),
			]);
			return;
		}
	}
};