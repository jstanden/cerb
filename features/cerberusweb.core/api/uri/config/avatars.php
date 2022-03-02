<?php /** @noinspection PhpUnused */

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
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$visit->set(ChConfigurationPage::ID, 'avatars');
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/avatars/index.tpl');
	}
	
	function handleActionForPage(string $action, string $scope=null) {
		if('configAction' == $scope) {
			switch ($action) {
				case 'saveJson':
					return $this->_configAction_saveJson();
			}
		}
		return false;
	}
	
	private function _configAction_saveJson() {
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if('POST' != DevblocksPlatform::getHttpMethod())
				throw new Exception_DevblocksValidationError(DevblocksPlatform::translate('common.access_denied'));
			
			$active_worker = CerberusApplication::getActiveWorker();
			
			if(!$active_worker || !$active_worker->is_superuser)
				throw new Exception(DevblocksPlatform::translate('error.core.no_acl.admin'));
			
			$avatar_default_style_contact = DevblocksPlatform::importGPC($_POST['avatar_default_style_contact'] ?? null, 'string',CerberusSettingsDefaults::AVATAR_DEFAULT_STYLE_CONTACT);
			$avatar_default_style_worker = DevblocksPlatform::importGPC($_POST['avatar_default_style_worker'] ?? null, 'string',CerberusSettingsDefaults::AVATAR_DEFAULT_STYLE_WORKER);
	
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
}