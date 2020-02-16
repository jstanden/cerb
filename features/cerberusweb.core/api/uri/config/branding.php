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

class PageSection_SetupBranding extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		
		$visit->set(ChConfigurationPage::ID, 'branding');
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/branding/index.tpl');
	}
	
	function saveJsonAction() {
		try {
			$settings = DevblocksPlatform::services()->pluginSettings();
			$worker = CerberusApplication::getActiveWorker();
			
			if('POST' != DevblocksPlatform::getHttpMethod())
				throw new Exception_DevblocksValidationError(DevblocksPlatform::translate('common.access_denied'));
			
			if(!$worker || !$worker->is_superuser)
				throw new Exception(DevblocksPlatform::translate('error.core.no_acl.admin'));
			
			header('Content-Type: application/json; charset=utf-8');
			
			@$title = DevblocksPlatform::importGPC($_POST['title'],'string','');
			@$logo_id = DevblocksPlatform::importGPC($_POST['logo_id'],'string','');
			@$favicon = DevblocksPlatform::importGPC($_POST['favicon'],'string','');
			@$user_stylesheet = DevblocksPlatform::importGPC($_POST['user_stylesheet'],'string');
	
			if(empty($title))
				$title = CerberusSettingsDefaults::HELPDESK_TITLE;
			
			// Logo
			if($logo_id) {
				if($logo_id == 'delete') {
					$settings->set('cerberusweb.core',CerberusSettings::UI_USER_LOGO_MIME_TYPE,'');
					$settings->set('cerberusweb.core',CerberusSettings::UI_USER_LOGO_UPDATED_AT,0);
					@unlink(APP_STORAGE_PATH . '/logo');
					
				} else {
					$logo_id = intval($logo_id);
					
					if(false != ($logo = DAO_Attachment::get($logo_id))) {
						if(!in_array($logo->mime_type, ['image/png', 'image/jpeg', 'image/gif'])) {
							throw new Exception("The logo image must be a PNG, JPEG, or GIF.");
						}
						
						$settings->set('cerberusweb.core',CerberusSettings::UI_USER_LOGO_MIME_TYPE,$logo->mime_type);
						$settings->set('cerberusweb.core',CerberusSettings::UI_USER_LOGO_UPDATED_AT,time());
						
						$fp = fopen(APP_STORAGE_PATH . '/logo', 'wb');
						$logo->getFileContents($fp);
						fclose($fp);
					}
				}
			}
			
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
			$settings->set('cerberusweb.core',CerberusSettings::UI_USER_STYLESHEET, $user_stylesheet);
			$settings->set('cerberusweb.core',CerberusSettings::UI_USER_STYLESHEET_UPDATED_AT, $user_stylesheet_updated_at);
			
			echo json_encode(array('status'=>true));
			return;
				
		} catch(Exception $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;
		}
	}
};