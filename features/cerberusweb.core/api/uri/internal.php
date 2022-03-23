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

class ChInternalController extends DevblocksControllerExtension {
	const ID = 'core.controller.internal';
	
	function handleRequest(DevblocksHttpRequest $request) {
		// Security
		if(null == (CerberusApplication::getActiveWorker())) {
			$this->redirectRequestToLogin($request);
			return;
		}
		
		$stack = $request->path;
		array_shift($stack); // internal
		@$action = array_shift($stack);
		
		if(!$action)
			return;

		// Default action, call arg as a method suffixed with Action
		if(false === ($this->invoke($action))) {
			if(!DEVELOPMENT_MODE_SECURITY_SCAN) {
				trigger_error(
					sprintf('Call to undefined internal action `%s::%s`',
						get_class($this),
						$action
					),
					E_USER_NOTICE
				);
			}
			DevblocksPlatform::dieWithHttpError(null, 404);
		}
	}
	
	private function invoke(string $action) {
		switch($action) {
			case 'invoke':
				return $this->_internalAction_invoke();
			case 'redirectRead':
				$page = new PageSection_InternalNotifications();
				return $page->handleActionForPage('redirectRead', 'internalAction');
		}
		
		return false;
	}

	function _internalAction_invoke() {
		$page_uri = DevblocksPlatform::importGPC($_GET['module'] ?? $_REQUEST['module'] ?? null,'string','');
		$action = DevblocksPlatform::importGPC($_GET['action'] ?? $_REQUEST['action'] ?? null,'string','');

		$page = Extension_PageSection::getExtensionByPageUri($this->manifest->id, $page_uri, true);
		
		if($page instanceof Extension_PageSection) {
			if(false === ($page->handleActionForPage($action, 'internalAction'))) {
				if(!DEVELOPMENT_MODE_SECURITY_SCAN) {
					trigger_error(
						sprintf('Call to undefined internal action `%s::%s`',
							get_class($page),
							$action
						),
						E_USER_NOTICE
					);
				}
				DevblocksPlatform::dieWithHttpError(null, 404);
			}
		}
	}
};
