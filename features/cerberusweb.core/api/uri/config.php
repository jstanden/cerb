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

class ChConfigurationPage extends CerberusPageExtension  {
	const ID = 'core.page.configuration';
	
	function isVisible() {
		// Must be logged in
		if(null == ($worker = CerberusApplication::getActiveWorker()))
			return false;
		
		// Must be a superuser
		return !empty($worker->is_superuser);
	}
	
	function render() {
		$translate = DevblocksPlatform::getTranslationService();
		$tpl = DevblocksPlatform::services()->template();
		$worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}

		if(file_exists(APP_PATH . '/install/')) {
			$tpl->assign('install_dir_warning', true);
		}
		
		// Selected section
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		array_shift($stack); // config
		
		// Remember the last tab/URL
		if(null == ($section_uri = @$response->path[1])) {
			if(null == ($section_uri = $visit->get(ChConfigurationPage::ID, '')))
				$section_uri = 'branding';
		}

		// Subpage
		$subpage = Extension_PageSection::getExtensionByPageUri($this->manifest->id, $section_uri, true);
		$tpl->assign('subpage', $subpage);
		
		// [TODO] Search for submenu on the 'config' page.
		//Extension_PageSubmenu::
		
		$tpl->display('devblocks:cerberusweb.core::configuration/index.tpl');
	}

	function handleSectionActionAction() {
		// GET has precedence over POST
		@$section_uri = DevblocksPlatform::importGPC(isset($_GET['section']) ? $_GET['section'] : $_REQUEST['section'],'string','');
		@$action = DevblocksPlatform::importGPC(isset($_GET['action']) ? $_GET['action'] : $_REQUEST['action'],'string','');

		$inst = Extension_PageSection::getExtensionByPageUri($this->manifest->id, $section_uri, true);
		
		if($inst instanceof Extension_PageSection && method_exists($inst, $action.'Action')) {
			call_user_func(array($inst, $action.'Action'));
		}
	}
	
};
