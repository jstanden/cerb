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

class PageSection_SetupBranding extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(ChConfigurationPage::ID, 'branding');
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/branding/index.tpl');
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
		$settings = DevblocksPlatform::services()->pluginSettings();
		$active_worker = CerberusApplication::getActiveWorker();
		
		try {
			if('POST' != DevblocksPlatform::getHttpMethod())
				throw new Exception_DevblocksValidationError(DevblocksPlatform::translate('common.access_denied'));
			
			if(!$active_worker || !$active_worker->is_superuser)
				throw new Exception(DevblocksPlatform::translate('error.core.no_acl.admin'));
			
			header('Content-Type: application/json; charset=utf-8');
			
			$title = DevblocksPlatform::importGPC($_POST['title'] ?? null, 'string','');
			$favicon = DevblocksPlatform::importGPC($_POST['favicon'] ?? null, 'string','');
			$user_stylesheet = DevblocksPlatform::importGPC($_POST['user_stylesheet'] ?? null, 'string');
	
			if(empty($title))
				$title = CerberusSettingsDefaults::HELPDESK_TITLE;
			
			// Test the favicon
			if(!empty($favicon) && null == parse_url($favicon, PHP_URL_SCHEME))
				throw new Exception("The favicon URL is not valid. Please include a full URL like http://example.com/favicon.ico");
			
			// Is there a user-defined stylesheet?
			if($user_stylesheet) {
				$user_stylesheet_updated_at = time();
			} else {
				$user_stylesheet_updated_at = 0;
			}
			
			$settings->set('cerberusweb.core',CerberusSettings::HELPDESK_TITLE, $title);
			$settings->set('cerberusweb.core',CerberusSettings::HELPDESK_FAVICON_URL, $favicon);
			$settings->set('cerberusweb.core',CerberusSettings::UI_USER_LOGO_UPDATED_AT, time());
			$settings->set('cerberusweb.core',CerberusSettings::UI_USER_STYLESHEET, $user_stylesheet);
			$settings->set('cerberusweb.core',CerberusSettings::UI_USER_STYLESHEET_UPDATED_AT, $user_stylesheet_updated_at);
			
			echo json_encode(['status'=>true]);
			return;
				
		} catch(Exception $e) {
			echo json_encode(['status'=>false,'error'=>$e->getMessage()]);
			return;
		}
	}
}