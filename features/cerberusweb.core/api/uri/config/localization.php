<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2017, Webgroup Media LLC
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
		$tpl = DevblocksPlatform::getTemplateService();
		$visit = CerberusApplication::getVisit();
		$date = DevblocksPlatform::services()->date();
		
		$visit->set(ChConfigurationPage::ID, 'localization');
		
		$timezones = $date->getTimezones();
		$tpl->assign('timezones', $timezones);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/section/localization/index.tpl');
	}
	
	function saveJsonAction() {
		try {
			$worker = CerberusApplication::getActiveWorker();
			
			if(!$worker || !$worker->is_superuser)
				throw new Exception("You are not a superuser.");
			
			@$timezone = DevblocksPlatform::importGPC($_POST['timezone'],'string','');
			@$time_format = DevblocksPlatform::importGPC($_POST['time_format'],'string',CerberusSettingsDefaults::TIME_FORMAT);
	
			$settings = $db = DevblocksPlatform::services()->pluginSettings();
			$settings->set('cerberusweb.core',CerberusSettings::TIMEZONE, $timezone);
			$settings->set('cerberusweb.core',CerberusSettings::TIME_FORMAT, $time_format);
			
			echo json_encode(array('status'=>true));
			return;
				
		} catch(Exception $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;
		}
	}
};