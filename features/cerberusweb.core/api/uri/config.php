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

class ChConfigurationPage extends CerberusPageExtension  {
	const ID = 'core.page.configuration';
	
	function isVisible() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if($active_worker && $active_worker->is_superuser)
			return true;
		
		return false;
	}
	
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		$visit = CerberusApplication::getVisit();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if(file_exists(APP_PATH . '/install/')) {
			$tpl->assign('install_dir_warning', true);
		}
		
		// Selected section
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		array_shift($stack); // config
		
		// Remember the last tab/URL
		if(null == ($section_uri = ($response->path[1] ?? null))) {
			if(null == ($section_uri = $visit->get(ChConfigurationPage::ID, '')))
				$section_uri = 'branding';
		}

		// Subpage
		if(null == ($subpage = Extension_PageSection::getExtensionByPageUri($this->manifest->id, $section_uri, true))) {
			$tpl->display('devblocks:cerberusweb.core::404.tpl');
			DevblocksPlatform::dieWithHttpError(null, 404);
		}
		
		$tpl->assign('subpage', $subpage);
		
		$tpl->display('devblocks:cerberusweb.core::configuration/index.tpl');
	}
	
	function invoke(string $action) {
		switch($action) {
			case 'invoke':
				return $this->_pageAction_invoke();
		}
		return false;
	}
	
	private function _pageAction_invoke() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!$active_worker || !$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		// GET has precedence over POST
		$page_uri = DevblocksPlatform::importGPC($_GET['module'] ?? $_REQUEST['module'] ?? null,'string','');
		$action = DevblocksPlatform::importGPC($_GET['action'] ?? $_REQUEST['action'] ?? null,'string','');
		
		$inst = Extension_PageSection::getExtensionByPageUri($this->manifest->id, $page_uri, true);
		
		/* @var $inst Extension_PageSection */
		
		if($inst instanceof Extension_PageSection) {
			if(false === ($inst->handleActionForPage($action, 'configAction'))) {
				if(!DEVELOPMENT_MODE_SECURITY_SCAN) {
					trigger_error(
						sprintf('Call to undefined config action `%s::%s`',
							get_class($inst),
							$action
						),
						E_USER_NOTICE
					);
				}
				DevblocksPlatform::dieWithHttpError(null, 404);
			}
		}
	}
}
