<?php



class ChMobileLoginPage  extends CerberusMobilePageExtension  {
    const KEY_FORGOT_EMAIL = 'login.recover.email';
    const KEY_FORGOT_SENTCODE = 'login.recover.sentcode';
    const KEY_FORGOT_CODE = 'login.recover.code';
    
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function isVisible() {
		return true;
	}
	
	function render() {
		// draws HTML form of controls needed for login information
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		
		// add translations for calls from classes that aren't Page Extensions (mobile plugin, specifically)
		$translate = DevblocksPlatform::getTranslationService();
		$tpl->assign('translate', $translate);
		
		$request = DevblocksPlatform::getHttpRequest();
		$prefix = '';
		$query_str = '';
		foreach($request->query as $key=>$val) {
			$query_str .= $prefix . $key . '=' . $val;
			$prefix = '&';
		}
		
		//$url_service = DevblocksPlatform::getUrlService();
		//$original_url = $url_service->writeDevblocksHttpIO($request);
		
		//$tpl->assign('original_url', $original_url);
		$original_path = (sizeof($request->path)==0) ? 'login' : implode(',',$request->path);
		
		$tpl->assign('original_path', $original_path);
		$tpl->assign('original_query', $query_str);
		
		$tpl->display('file:' . dirname(__FILE__) . '/../../templates/login/login_form_default.tpl.php');
	}
	
	function authenticateAction() {
		//echo "authing!";
		@$email = DevblocksPlatform::importGPC($_POST['email']);
		@$password = DevblocksPlatform::importGPC($_POST['password']);
	    
	    
		// pull auth info out of $_POST, check it, return user_id or false
		$worker = DAO_Worker::login($email, $password);
		//echo $email. '-'.$password;print_r($worker);exit();
		if(!is_null($worker)) {
			$session = DevblocksPlatform::getSessionService();
			$visit = new CerberusVisit();
			$visit->setWorker($worker);
				
//			$memberships = DAO_Worker::getGroupMemberships($worker->id);
//			$team_id = key($memberships);
//			if(null != ($team_id = key($memberships))) {
			$visit->set(CerberusVisit::KEY_DASHBOARD_ID, ''); // 't'.$team_id
			$visit->set(CerberusVisit::KEY_WORKSPACE_GROUP_ID, 0); // $team_id
//			}

			$session->setVisit($visit);
			
			// [TODO] Only direct to /welcome when tour is enabled
			//return true;

			//$devblocks_response = new DevblocksHttpResponse(array('mobile','mytickets'));
			$devblocks_response = new DevblocksHttpResponse(array('mobile','tickets'));
		
			

			
		} else {
			$devblocks_response = new DevblocksHttpResponse(array('mobile', 'login'));
			//return false;
		}
		DevblocksPlatform::redirect($devblocks_response);
		
	}
}


?>