<?php
/***********************************************************************
| Cerb(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2012, WebGroup Media LLC
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

class ChSignInPage extends CerberusPageExtension {
	function isVisible() {
		return true;
	}
	
	function render() {
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'], 'string', '');
		@$remember_email = DevblocksPlatform::importGPC($_COOKIE['cerb_login_email'], 'string', '');
		
		if(empty($email) && !empty($remember_email))
			$email = $remember_email;
		
		if(!empty($email))
			$worker_id = DAO_Worker::getByEmail($email);
		
		if(!empty($worker_id))
			$worker = DAO_Worker::get($worker_id);
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		array_shift($stack); // login
		$section = array_shift($stack);
		
		switch($section) {
			case "recover":
				// [TODO] If the email account is given we don't need to prompt for the address
				
				$tpl = DevblocksPlatform::getTemplateService();
				$tpl->assign('email', $email);

				if(!empty($email) 
						&& null != $worker_id
						&& null != $worker
						&& !$worker->is_disabled) {
					
					@$confirm_code = DevblocksPlatform::importGPC($_REQUEST['confirm_code'], 'string', '');
					
					// Secret questions
					if(false !== ($secret_questions = @json_decode(DAO_WorkerPref::get($worker->id, 'login.recover.secret_questions', ''), true)) && is_array($secret_questions)) {
						$tpl->assign('secret_questions', $secret_questions);
					}
					
					if(!empty($confirm_code) && isset($_SESSION['recovery_code']) && !empty($_SESSION['recovery_code'])) {
						if($worker->email.':'.$confirm_code == $_SESSION['recovery_code']) {
							$pass = true;
							
							// Compare secret questions
							if(is_array($secret_questions)) {
								@$sq = DevblocksPlatform::importGPC($_REQUEST['sq'], 'array', array());
								
								foreach($secret_questions as $idx => $secret) {
									if(empty($secret['a']))
										continue;
									
									if(0 != strcasecmp($secret['a'], $sq[$idx]))
										$pass = false;
								}
							}
							
							if($pass && null != ($ext = Extension_LoginAuthenticator::get($worker->auth_extension_id, true))) {
								/* @var $ext Extension_LoginAuthenticator */
								$ext->resetCredentials($worker);
								
								$query = array(
									'email' => $worker->email,
									'code' => $confirm_code,
								);
								DevblocksPlatform::redirect(new DevblocksHttpRequest(array('login',$ext->manifest->params['uri']), $query));
								
							} else {
								$tpl->display('devblocks:cerberusweb.core::login/recover/recover2.tpl');
								
							}
							
						} else {
							$tpl->assign('code', $confirm_code);
							$tpl->display('devblocks:cerberusweb.core::login/recover/recover2.tpl');
							
						}
						
					} else {
						$recovery_code = CerberusApplication::generatePassword(8);

						// [TODO] Use the internal account recovery service to send the code by email or SMS
						// [TODO] This needs to be limited to only recovering once per hour unless successful
						
						CerberusMail::quickSend($worker->email, 'Your account recovery confirmation code', $recovery_code);
						
						$_SESSION['recovery_code'] = $worker->email.':'.$recovery_code;
						
						$tpl->display('devblocks:cerberusweb.core::login/recover/recover2.tpl');
					}
					
				} else {
					$tpl->display('devblocks:cerberusweb.core::login/recover/recover1.tpl');
				}
				
				break;

			case 'authenticate':
				if(empty($worker)) {
					sleep(2); // delay brute force
					$query = array(
						'email' => $email,
						'error' => 'Invalid password.',
					);
					DevblocksPlatform::redirect(new DevblocksHttpRequest(array('login','password'), $query));
				}
				
				// Look up the URI as an extension
				if(null != ($ext = Extension_LoginAuthenticator::get($worker->auth_extension_id, true))) {
					/* @var $ext Extension_LoginAuthenticator */
					
					if(false != ($worker = $ext->authenticate())) {
						$_SESSION['login_authenticated_worker'] = $worker;
						DevblocksPlatform::redirect(new DevblocksHttpRequest(array('login','authenticated')));
						
					} else {
						$query = array();
			
						if(isset($_POST['email']))
							$query['email'] = $_POST['email'];
						
						$query['error'] = 'Authentication failed.';
						
						$devblocks_response = new DevblocksHttpResponse(array('login', $ext->manifest->params['uri']), $query);
						DevblocksPlatform::redirect($devblocks_response);
					}
					break;
				}
				break;
				
			case 'authenticated':
				@$worker = $_SESSION['login_authenticated_worker'];
				unset($_SESSION['login_authenticated_worker']);
				
				if(empty($worker))
					DevblocksPlatform::redirect(new DevblocksHttpRequest(array('login')));
					
				$this->_processAuthenticated($worker);
				break;
			
			case 'reset':
				unset($_COOKIE['cerb_login_email']);
				setcookie('cerb_login_email', null);
				DevblocksPlatform::redirect(new DevblocksHttpRequest(array('login')));
				break;
				
			case 'failed':
			case NULL:
				@$url = DevblocksPlatform::importGPC($_REQUEST['url'], 'string', '');
				
				// If we have a cookie remembering the worker, redirect to login form
				if(empty($section) && empty($stack) && !empty($remember_email)
						&& !empty($worker)
						&& null != ($ext = Extension_LoginAuthenticator::get($worker->auth_extension_id, false))
					) {
					$query = array(
						'email' => $remember_email,
					);
					
					$devblocks_response = new DevblocksHttpResponse(array('login', $ext->params['uri']), $query);
					DevblocksPlatform::redirect($devblocks_response);
				}
				
				$tpl = DevblocksPlatform::getTemplateService();
				
				if(!empty($url))
					$_SESSION['login_post_url'] = $url;
				
				$tpl->assign('remember_me', $remember_email);
				
				if(empty($email) && !empty($remember_email))
					$email = $remember_email;
				
				$tpl->assign('email', $email);
				
				switch($section) {
					case 'failed':
						$tpl->assign('error', 'Login failed.');
						break;
				}
				
				$tpl->display('devblocks:cerberusweb.core::login/login_router.tpl');
				break;
				
			default:
				// Look up the URI as an extension
				if(null != ($ext = Extension_LoginAuthenticator::getByUri($section, true))) {
					// Confirm the tentative worker can access this auth extension
					if(!empty($worker) && $worker->auth_extension_id != $ext->id)
						return;
					
					/* @var $ext Extension_LoginAuthenticator */
					$ext->render();
					break;
				}

				DevblocksPlatform::redirect(new DevblocksHttpRequest(array('login')));
				break;
		}
	}
	
	function showAction() {
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('login')));
	}
	
	function routerAction() {
		@$email = DevblocksPlatform::importGPC($_POST['email'],'string','');
		@$remember_me = DevblocksPlatform::importGPC($_POST['remember_me'],'integer', 0);
		
		if(null == ($worker_id = DAO_Worker::getByEmail($email))) {
			sleep(2); // nag brute force attempts
			// Deceptively send invalid logins to the password page anyway, if shaped like an email
			$query = array('email' => $email);
			DevblocksPlatform::redirect(new DevblocksHttpRequest(array('login','password'), $query));
			
		} else {
			// [TODO] Check the worker's allowed IPs
			
			// Make sure it's a valid worker
			if(null == ($worker = DAO_Worker::get($worker_id))) {
				$query = array('error' => 'Invalid account.');
				DevblocksPlatform::redirect(new DevblocksHttpRequest(array('login'), $query));
			}

			// Check if worker is disabled, fail early
			if($worker->is_disabled) {
				$query = array('error' => 'Your account is disabled.');
				DevblocksPlatform::redirect(new DevblocksHttpRequest(array('login'), $query));
			}
			
			// Load the worker's auth extension
			if(null == ($auth_ext = Extension_LoginAuthenticator::get($worker->auth_extension_id)) || !isset($auth_ext->params['uri'])) {
				$query = array('error' => 'Invalid authentication method.');
				DevblocksPlatform::redirect(new DevblocksHttpRequest(array('login'), $query));
			}
			
			if($remember_me) {
				setcookie('cerb_login_email', $email, time()+14*86400);
			}
			
			$query = array(
				'email' => $email
			);
			
			DevblocksPlatform::redirect(new DevblocksHttpRequest(array('login',$auth_ext->params['uri']), $query));
		}
	}
	
	// Please be honest
	private function _checkSeats($worker) {
		$honesty = CerberusLicense::getInstance();
		$session = DevblocksPlatform::getSessionService();
		
		$online_workers = DAO_Worker::getAllOnline(86400, 100);
		$max = intval(max($honesty->w, 1));
		
		if(!isset($online_workers[$worker->id]) && $max <= count($online_workers) && 100 > $max) {
			$online_workers = DAO_Worker::getAllOnline(600, 1);

			if($max <= count($online_workers)) {
				$most_idle_worker = end($online_workers);
				$session->clear();
				$time = 600 - max(0,time()-$most_idle_worker->last_activity_date);
				
				$query = array(
					'email' => $worker->email,
					'error' => sprintf("The maximum number of simultaneous workers are currently signed on.  The next session expires in %s.", ltrim(_DevblocksTemplateManager::modifier_devblocks_prettytime($time,true),'+')),
				);
				
				if(null == ($ext = Extension_LoginAuthenticator::get($worker->auth_extension_id, false)))
					return;
				
				DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login',$ext->params['uri']), $query));
			}
		}
	}
	
	private function _processAuthenticated($worker) {
		$this->_checkSeats($worker);

		$session = DevblocksPlatform::getSessionService();

		$visit = new CerberusVisit();
		$visit->setWorker($worker);

		$session->setVisit($visit);
		
		if(isset($_SESSION['login_post_url'])) {
			$redirect_path = explode('/', $_SESSION['login_post_url']);
			
			// Only valid pages
			if(is_array($redirect_path) && !empty($redirect_path)) {
				$redirect_uri = current($redirect_path);
				
				if($redirect_uri != 'explore' && !CerberusApplication::getPageManifestByUri($redirect_uri))
					$redirect_path = array();
			}
		}
		
		$devblocks_response = new DevblocksHttpResponse($redirect_path);
		
		// Timezone
		if(null != ($timezone = DAO_WorkerPref::get($worker->id,'timezone'))) {
			$_SESSION['timezone'] = $timezone;
			@date_default_timezone_set($timezone);
		}
		
		// Language
		if(null != ($lang_code = DAO_WorkerPref::get($worker->id,'locale'))) {
			$_SESSION['locale'] = $lang_code;
			DevblocksPlatform::setLocale($lang_code);
		}
		
		// Flush views
		DAO_WorkerViewModel::flush($worker->id);
		
		// Flush caches
		DAO_WorkerRole::clearWorkerCache($worker->id);
		
		if(empty($devblocks_response->path)) {
			$tour_enabled = intval(DAO_WorkerPref::get($worker->id, 'assist_mode', 1));
			$next_page = ($tour_enabled) ?  array('welcome') : array('profiles','worker','me');
			$devblocks_response = new DevblocksHttpResponse($next_page);
		}
		
		/*
		 * Log activity (worker.logged_in)
		 */
		$ip_address = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'an unknown IP';
		$user_agent = UserAgentParser::parse();
		$user_agent_string = sprintf("%s%s%s",
			$user_agent['browser'],
			!empty($user_agent['version']) ? (' ' . $user_agent['version'] . ' ') : '',
			!empty($user_agent['platform']) ? (' for ' . $user_agent['platform']) : ''
		);
		
		$entry = array(
			//{{actor}} logged in from {{ip}} using {{user_agent}}
			'message' => 'activities.worker.logged_in',
			'variables' => array(
				'ip' => $ip_address,
				'user_agent' => $user_agent_string,
				),
			'urls' => array(
				)
		);
		CerberusContexts::logActivity('worker.logged_in', null, null, $entry);
		
		DevblocksPlatform::redirect($devblocks_response);
	}
	
	function signoutAction() {
		/*
		 * Log activity (worker.logged_out)
		 */
		$ip_address = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'an unknown IP';
		
		$entry = array(
			//{{actor}} logged out from {{ip}}
			'message' => 'activities.worker.logged_out',
			'variables' => array(
				'ip' => $ip_address,
				),
			'urls' => array(
				)
		);
		CerberusContexts::logActivity('worker.logged_out', null, null, $entry);
		
		$session = DevblocksPlatform::getSessionService();
		
		DAO_Worker::logActivity(new Model_Activity(null));
		
		$session->clear();
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login')));
	}
};
