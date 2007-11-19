<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2007, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerberus Helpdesk Team
 * 
 * Sure, it would be so easy to just cheat and edit this file to use the 
 * software without paying for it.  But we trust you anyway.  In fact, we're 
 * writing this software for you! 
 * 
 * Quality software backed by a dedicated team takes money to develop.  We 
 * don't want to be out of the office bagging groceries when you call up 
 * needing a helping hand.  We'd rather spend our free time coding your 
 * feature requests than mowing the neighbors' lawns for rent money. 
 * 
 * We've never believed in encoding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your in-box that you probably 
 * haven't had since spammers found you in a game of "E-mail Address 
 * Battleship".  Miss. Miss. You sunk my in-box!
 * 
 * A legitimate license entitles you to support, access to the developer 
 * mailing list, the ability to participate in betas and the warm fuzzy 
 * feeling of feeding a couple obsessed developers who want to help you get 
 * more done than 'the other guy'.
 *
 * - Jeff Standen, Mike Fogg, Brenan Cavish, Darren Sugita, Dan Hildebrandt
 * 		and Joe Geck.
 *   WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
class DefaultLoginModule extends CerberusLoginPageExtension {
	function renderLoginForm() {
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
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/login/login_form_default.tpl.php');
	}
	
	function authenticate($params=array()) {
	    $email = $params['email'];
	    $password = $params['password'];
		// pull auth info out of $_POST, check it, return user_id or false
		$worker = DAO_Worker::login($email, $password);
		
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
			return true;
			
		} else {
			return false;
		}
	}
};

class LDAPLoginModule extends CerberusLoginPageExtension {
	/**
	 * draws html form for adding necessary settings (host, port, etc) to be stored in the db
	 */
	function renderConfigForm() {
	}
	
	/**
	 * Receives posted config form, saves to manifest
	 */
	function saveConfiguration() {
//		$field_value = DevblocksPlatform::importGPC($_POST['field_value']);
//		$this->params['field_name'] = $field_value;
	}
	
	// draws HTML form of controls needed for login information
	function renderLoginForm() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		
		// add translations for calls from classes that aren't Page Extensions (mobile plugin, specifically)
		$translate = DevblocksPlatform::getTranslationService();
		$tpl->assign('translate', $translate);
		
		// set up redirect (to return to original page if redirected to login page)
		$request = DevblocksPlatform::getHttpRequest();
		$prefix = '';
		$query_str = '';
		foreach($request->query as $key=>$val) {
			$query_str .= $prefix . $key . '=' . $val;
			$prefix = '&';
		}
		$original_path = (sizeof($request->path)==0) ? 'login' : implode(',',$request->path);
		$tpl->assign('original_path', $original_path);
		$tpl->assign('original_query', $query_str);

		// TODO: pull this from a config area
		$server = 'localhost';
		$port = '10389';
		$default_dn = 'cn=William Bush,ou=people,o=sevenSeas';
		$tpl->assign('server', $server);
		$tpl->assign('port', $port);
		$tpl->assign('default_dn', $default_dn);
		
		// display login form
		$tpl->display('file:' . dirname(__FILE__) . '/templates/login/login_form_ldap.tpl.php');
	}
	
	function authenticate($params=array()) {
	    $server = $params['server'];
	    $port = $params['port'];
	    $dn = $params['dn'];
	    $password = $params['password'];
	    
		$worker_id = null;
		
	    // attempt login
		$conn = ldap_connect($server, $port);
		ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
		
		if ($conn) {
			$auth = ldap_bind($conn, $dn, $password);
			if ($auth) {
				// search for this user
				$search_results = ldap_search($conn, $dn, '(objectclass=*)', array('mail'));
				if ($search_results) {
					$user_entry = ldap_first_entry($conn, $search_results);
					if ($user_entry) {
						// get email addresses for this user
						$emails = ldap_get_values($conn, $user_entry, 'mail');
						if ($emails) {
							foreach($emails as $email) {
								if (is_null($worker_id)) {
									$worker_id = DAO_Worker::lookupAgentEmail($email);
								}
							}
						}
					}
				}
			}
		}
				
		// we found a worker, continue login
		if (!is_null($worker_id)) {
			$worker = DAO_Worker::getAgent($worker_id);
			$session = DevblocksPlatform::getSessionService();
			$visit = new CerberusVisit();
			$visit->setWorker($worker);
				
			$visit->set(CerberusVisit::KEY_DASHBOARD_ID, ''); // 't'.$team_id
			$visit->set(CerberusVisit::KEY_WORKSPACE_GROUP_ID, 0); // $team_id

			$session->setVisit($visit);
			
			return true;
		} else {
			return false;
		}
	}
};


?>