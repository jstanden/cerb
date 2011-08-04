<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2011, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/

class DefaultLoginModule extends Extension_LoginAuthenticator {
	function renderLoginForm() {
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;
		
		@array_shift($stack); // login
		
		// draws HTML form of controls needed for login information
		$tpl = DevblocksPlatform::getTemplateService();
		
		// add translations for calls from classes that aren't Page Extensions (mobile plugin, specifically)
		$translate = DevblocksPlatform::getTranslationService();
		$tpl->assign('translate', $translate);
		
		// Must be a valid page controller
		@$redir_path = explode('/',urldecode(DevblocksPlatform::importGPC($_REQUEST["url"],"string","")));
		if(is_array($redir_path) && isset($redir_path[0]) && ($redir_path[0]=='explore' || CerberusApplication::getPageManifestByUri($redir_path[0])))
			$tpl->assign('original_path', implode('/',$redir_path));
		
		switch(array_shift($stack)) {
			case 'too_many':
				@$secs = array_shift($stack);
				$tpl->assign('error', sprintf("The maximum number of simultaneous workers are currently signed on.  The next session expires in %s.", ltrim(_DevblocksTemplateManager::modifier_devblocks_prettytime($secs,true),'+')));
				break;
			case 'failed':
				$tpl->assign('error', 'Login failed.');
				break;
		}
		
		$tpl->display('devblocks:cerberusweb.core::login/login_form_default.tpl');
	}
	
	function authenticate() {
		// Pull from $_POST
		@$email = DevblocksPlatform::importGPC($_POST['email']);
		@$password = DevblocksPlatform::importGPC($_POST['password']);

		$worker = DAO_Worker::login($email, $password);
		
		if(!is_null($worker)) {
			$session = DevblocksPlatform::getSessionService();
			$visit = new CerberusVisit();
			$visit->setWorker($worker);
				
			$session->setVisit($visit);
			
			// [TODO] Only direct to /welcome when tour is enabled
			return true;
			
		} else {
			return false;
		}
	}
};

