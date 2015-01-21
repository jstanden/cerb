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
			@$favicon = DevblocksPlatform::importGPC($_POST['favicon'],'string','');
			@$logo = DevblocksPlatform::importGPC($_POST['logo'],'string');
	
			if(empty($title))
				$title = CerberusSettingsDefaults::HELPDESK_TITLE;
			
			// Test the logo
			if(!empty($logo) && null == parse_url($logo, PHP_URL_SCHEME))
				throw new Exception("The logo URL is not valid. Please include a full URL like http://example.com/logo.png");
			
			// Test the favicon
			if(!empty($favicon) && null == parse_url($favicon, PHP_URL_SCHEME))
				throw new Exception("The favicon URL is not valid. Please include a full URL like http://example.com/favicon.ico");
				
			$settings = DevblocksPlatform::getPluginSettingsService();
			$settings->set('cerberusweb.core',CerberusSettings::HELPDESK_TITLE, $title);
			$settings->set('cerberusweb.core',CerberusSettings::HELPDESK_FAVICON_URL, $favicon);
			$settings->set('cerberusweb.core',CerberusSettings::HELPDESK_LOGO_URL, $logo); // [TODO] Enforce some kind of max resolution?
			
			echo json_encode(array('status'=>true));
			return;
				
		} catch(Exception $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;
		}
	}
};