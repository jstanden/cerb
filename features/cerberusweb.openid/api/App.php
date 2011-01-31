<?php
class ChOpenIdAjaxController extends CerberusPageExtension {
	 function isVisible() {
		// The current session must be a logged-in worker to use this page.
		if(null == ($worker = CerberusApplication::getActiveWorker()))
			return false;
		return true;
	 }
	 
	 function validateAddPrefAction() {
		$active_worker = CerberusApplication::getActiveWorker();
	 	$openid = DevblocksPlatform::getOpenIDService();
	 	
		if(!$openid->validate($_REQUEST)) {
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('preferences','openid','failed')));
			exit;
		}

		// Check if the current ID is taken
		$openids = DAO_OpenIDToWorker::getWhere(sprintf("%s = %s",
			DAO_OpenIDToWorker::OPENID_CLAIMED_ID,
			C4_ORMHelper::qstr($_REQUEST['openid_claimed_id'])
		));
		
		if(!empty($openids)) {
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('preferences','openid','unavailable')));
			exit;
		}
		
		// Create the new row when available
		$fields = array(
			DAO_OpenIDToWorker::OPENID_URL => $_REQUEST['openid_identity'], 
			DAO_OpenIDToWorker::OPENID_CLAIMED_ID => $_REQUEST['openid_claimed_id'],
			DAO_OpenIDToWorker::WORKER_ID => $active_worker->id,
		);
		$id = DAO_OpenIDToWorker::create($fields);

		if(!empty($id)) {
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('preferences','openid','added')));
			
		} else {
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('preferences','openid','failed')));
		}
		
	 	exit;
	 }
	 
	 function deletePrefAction() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'integer', 0);
		
		// Make sure the current worker owns the IDs
		$openids = DAO_OpenIDToWorker::getWhere(sprintf("%s = %d AND %s = %d",
			DAO_OpenIDToWorker::ID,
			$id,
			DAO_OpenIDToWorker::WORKER_ID,
			$active_worker->id
		));
		
		// For now this is only going to be a single element
		foreach($openids as $id => $openid) {
			DAO_OpenIDToWorker::delete($id);
		}
		
		exit;
	 }
}

if (class_exists('Extension_PreferenceTab')):
class ChOpenIdPreferenceTab extends Extension_PreferenceTab {
	function showTab() {
		$active_worker = CerberusApplication::getActiveWorker();
		$tpl = DevblocksPlatform::getTemplateService();

		$openids = DAO_OpenIDToWorker::getWhere(sprintf("%s = %d",
			DAO_OpenIDToWorker::WORKER_ID, 
			$active_worker->id
		));
		$tpl->assignByRef('openids', $openids);
		
		$tpl->display('devblocks:cerberusweb.openid::preferences/tab.tpl');		
	}
	
	function saveTab() {
		@$openid_url = DevblocksPlatform::importGPC($_POST['openid_url'],'string','');
		
		$openid = DevblocksPlatform::getOpenIDService();
		$url_writer = DevblocksPlatform::getUrlService();
		
		if(!empty($openid_url)) {
			// Verify the OpenID url
			$auth_url = $openid->getAuthUrl($openid_url, $url_writer->write('c=openid.ajax&a=validateAddPref', true));
			header("Location: " . $auth_url);
			exit;
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('preferences','openid')));
	}
}
endif;

if(class_exists('Extension_LoginAuthenticator',true)):
class ChOpenIdLoginModule extends Extension_LoginAuthenticator {
	function renderLoginForm() {
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;
		
		@array_shift($stack); // login
		
		// draws HTML form of controls needed for login information
		$tpl = DevblocksPlatform::getTemplateService();
		
		// add translations for calls from classes that aren't Page Extensions (mobile plugin, specifically)
		//$translate = DevblocksPlatform::getTranslationService();
		//$tpl->assign('translate', $translate);
		
		// Must be a valid page controller
		@$redir_path = explode('/',urldecode(DevblocksPlatform::importGPC($_REQUEST["url"],"string","")));
		if(is_array($redir_path) && isset($redir_path[0]) && CerberusApplication::getPageManifestByUri($redir_path[0]))
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
		
		$tpl->display('devblocks:cerberusweb.openid::login/login_openid.tpl');
	}
	
	function discoverAction() {
		@$openid_url = DevblocksPlatform::importGPC($_POST['openid_url'],'string','');

		$openid = DevblocksPlatform::getOpenIDService();
		$url_writer = DevblocksPlatform::getUrlService();
		
		$return_url = $url_writer->write('c=login&a=authenticate', true);
		
		// [TODO] Handle invalid URLs
		$auth_url = $openid->getAuthUrl($openid_url, $return_url);
		header("Location: " . $auth_url);
		exit;
	}
	
	function authenticate() {
		//var_dump($_REQUEST);
		$url_writer = DevblocksPlatform::getUrlService();

		// Mode (Cancel)
		if(isset($_GET['openid_mode']))
		switch($_GET['openid_mode']) {
			case 'cancel':
				header("Location: " . $url_writer->write('c=login', true));
				break;
				
			default:
				$openid = DevblocksPlatform::getOpenIDService();

				// If we failed validation
				if(!$openid->validate($_REQUEST))
					return false;

				// Get parameters
				$attribs = $openid->getAttributes($_REQUEST);

				// Does a worker own this OpenID?
				$openids = DAO_OpenIDToWorker::getWhere(sprintf("%s = %s",
					DAO_OpenIDToWorker::OPENID_CLAIMED_ID,
					C4_ORMHelper::qstr($_REQUEST['openid_claimed_id'])
				));
				
				if(null == ($openid_owner = array_shift($openids)) || empty($openid_owner->worker_id))
					return false;
					
				if(null != ($worker = DAO_Worker::get($openid_owner->worker_id)) && !$worker->is_disabled) {
					$session = DevblocksPlatform::getSessionService();
					$visit = new CerberusVisit();
					$visit->setWorker($worker);
						
					$session->setVisit($visit);
					
					return true;
					
				} else {
					return false;
				}
				
				break;
		}
	}
};
endif;