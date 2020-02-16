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

class PageSection_SetupLocalization extends Extension_PageSection {
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$visit = CerberusApplication::getVisit();
		$date = DevblocksPlatform::services()->date();
		
		$visit->set(ChConfigurationPage::ID, 'localization');
		
		$timezones = $date->getTimezones();
		$tpl->assign('timezones', $timezones);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/localization/index.tpl');
	}
	
	function saveJsonAction() {
		header('Content-Type: application/json; charset=utf-8');
		
		$worker = CerberusApplication::getActiveWorker();
		
		try {
			if('POST' != DevblocksPlatform::getHttpMethod())
				throw new Exception_DevblocksValidationError(DevblocksPlatform::translate('common.access_denied'));
			
			if(!$worker || !$worker->is_superuser)
				throw new Exception(DevblocksPlatform::translate('error.core.no_acl.admin'));
			
			@$timezone = DevblocksPlatform::importGPC($_POST['timezone'],'string','');
			@$time_format = DevblocksPlatform::importGPC($_POST['time_format'],'string',CerberusSettingsDefaults::TIME_FORMAT);
	
			$settings = DevblocksPlatform::services()->pluginSettings();
			$settings->set('cerberusweb.core',CerberusSettings::TIMEZONE, $timezone);
			$settings->set('cerberusweb.core',CerberusSettings::TIME_FORMAT, $time_format);
			
			echo json_encode([
				'status'=>true,
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