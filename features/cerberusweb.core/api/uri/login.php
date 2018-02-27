<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2018, Webgroup Media LLC
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

class ChSignInPage extends CerberusPageExtension {
	function isVisible() {
		return true;
	}
	
	static function getErrorMessage($code) {
		$error = "An unexpected error occurred. Please try again.";
		
		switch($code) {
			case 'account.disabled':
				$error = "Your account is disabled.";
				break;
				
			case 'account.locked':
				$error = "Your account has been temporarily locked after too many failed login attempts. Please wait a few minutes and try again.";
				break;
				
			case 'auth.failed':
				$error = "Authentication failed.";
				break;
				
			case 'auth.invalid':
				$error = "Invalid authentication method.";
				break;
				
			case 'confim.failed':
				$error = "The given confirmation code doesn't match the one on file.";
				break;
				
			case 'confirm.invalid':
				$error = "The given confirmation code is invalid.";
				break;
				
			case 'email.unavailable':
				$error = "The provided email address is not available.";
				break;
				
			case 'password.invalid':
				$error = "The given password is invalid.";
				break;
				
			case 'password.mismatch':
				$error = "The given passwords do not match.";
				break;
				
			case 'seats.limit':
				$error = "The maximum number of simultaneous workers are currently active. Please try again later, or ask an administrator to increase the seat count in your license.";
				break;
		}
		
		return $error;
	}
	
	function render() {
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'], 'string', '');
		@$remember_email = DevblocksPlatform::importGPC($_COOKIE['cerb_login_email'], 'string', '');
		
		$unauthenticated_worker = null;
		
		if(empty($email) && !empty($remember_email))
			$email = $remember_email;
		
		if(!empty($email))
			$unauthenticated_worker = DAO_Worker::getByEmail($email);
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		array_shift($stack); // login
		$section = array_shift($stack);
		
		$cache = DevblocksPlatform::services()->cache();
		
		switch($section) {
			case "recover":
				$tpl = DevblocksPlatform::services()->template();
				$tpl->assign('email', $email);
				
				if(!empty($email)
						&& null != $unauthenticated_worker
						&& !$unauthenticated_worker->is_disabled) {
					
					@$confirm_code = DevblocksPlatform::importGPC($_REQUEST['confirm_code'], 'string', '');
					
					// Secret questions
					if(false !== ($secret_questions = @json_decode(DAO_WorkerPref::get($unauthenticated_worker->id, 'login.recover.secret_questions', ''), true)) && is_array($secret_questions)) {
						$tpl->assign('secret_questions', $secret_questions);
					}
					
					if(!empty($confirm_code) && isset($_SESSION['recovery_code']) && !empty($_SESSION['recovery_code'])) {
						if($unauthenticated_worker->getEmailString().':'.$confirm_code == $_SESSION['recovery_code']) {
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
							
							if($pass && null != ($ext = Extension_LoginAuthenticator::get($unauthenticated_worker->auth_extension_id, true))) {
								// Clear the recovery rate-limit on success
								$cache_key = sprintf('recover:worker:%d', $unauthenticated_worker->id);
								$cache->remove($cache_key);
								
								/* @var $ext Extension_LoginAuthenticator */
								$ext->resetCredentials($unauthenticated_worker);
								
								$query = array(
									'email' => $unauthenticated_worker->getEmailString(),
									'code' => $confirm_code,
								);
								DevblocksPlatform::redirect(new DevblocksHttpRequest(array('login',$ext->manifest->params['uri']), $query));
								
							} else {
								$tpl->display('devblocks:cerberusweb.core::login/recover/recover.tpl');
								
							}
							
						} else {
							$tpl->assign('code', $confirm_code);
							$tpl->display('devblocks:cerberusweb.core::login/recover/recover.tpl');
							
						}
						
					} else {
						// This is rate-limited
						$cache_key = sprintf('recover:worker:%d', $unauthenticated_worker->id);
						
						if(false == $cache->load($cache_key)) {
							$labels = $values = [];
							CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $unauthenticated_worker, $worker_labels, $worker_values, '', true, true);
							CerberusContexts::merge('worker_', null, $worker_labels, $worker_values, $labels, $values);
							
							$values['code'] = CerberusApplication::generatePassword(8);
							$values['ip'] = DevblocksPlatform::getClientIp();
							
							$_SESSION['recovery_code'] = $unauthenticated_worker->getEmailString() . ':' . $values['code'];
							
							CerberusApplication::sendEmailTemplate($unauthenticated_worker->getEmailString(), 'worker_recover', $values);
							
							$cache->save(time(), $cache_key, [], 1800);
						}
						
						$tpl->display('devblocks:cerberusweb.core::login/recover/recover.tpl');
					}
					
				} else {
					// Pretend we sent a recovery code for invalid email addresses
					$tpl->display('devblocks:cerberusweb.core::login/recover/recover.tpl');
				}
				
				break;

			case 'authenticate':
				// Prevent brute force logins
				$recent_failed_logins = DAO_ContextActivityLog::getLatestEntriesByTarget(CerberusContexts::CONTEXT_WORKER, $unauthenticated_worker->id, 5, ['worker.login.failed'], time()-900);
				
				if(is_array($recent_failed_logins) && count($recent_failed_logins) >= 5) {
					$query = array(
						'email' => $email,
						'error' => 'account.locked',
					);
					DevblocksPlatform::redirect(new DevblocksHttpRequest(array('login'), $query));
				}
				
				if(empty($unauthenticated_worker)) {
					$query = array(
						'email' => $email,
						'error' => 'auth.failed',
					);
					DevblocksPlatform::redirect(new DevblocksHttpRequest(array('login','password'), $query));
				}
				
				// Look up the URI as an extension
				if(null != ($ext = Extension_LoginAuthenticator::get($unauthenticated_worker->auth_extension_id, true))) {
					/* @var $ext Extension_LoginAuthenticator */
					
					if(false != ($authenticated_worker = $ext->authenticate()) && $authenticated_worker instanceof Model_Worker) {
						$this->_checkSeats($authenticated_worker);
						
						$_SESSION['login_authenticated_worker'] = $authenticated_worker;
						
						DevblocksPlatform::redirect(new DevblocksHttpRequest(array('login','authenticated')), 1);
						
					} else {
						/*
						 * Log activity (worker.login.failed)
						 */
						$ip_address = DevblocksPlatform::getClientIp() ?: 'an unknown IP';
						$user_agent = DevblocksPlatform::getClientUserAgent();
						$user_agent_string = sprintf("%s%s%s",
							$user_agent['browser'],
							!empty($user_agent['version']) ? (' ' . $user_agent['version']) : '',
							!empty($user_agent['platform']) ? (' for ' . $user_agent['platform']) : ''
						);
						
						$entry = array(
							//{{ip}} failed to log in as {{target}} using {{user_agent}}
							'message' => 'activities.worker.login.failed',
							'variables' => array(
								'ip' => $ip_address,
								'user_agent' => $user_agent_string,
								'target' => sprintf($unauthenticated_worker->getName()),
								),
							'urls' => array(
								'target' => sprintf("ctx://%s:%d", CerberusContexts::CONTEXT_WORKER, $unauthenticated_worker->id),
								)
						);
						CerberusContexts::logActivity('worker.login.failed', CerberusContexts::CONTEXT_WORKER, $unauthenticated_worker->id, $entry);
						
						// Redirect
						
						$query = [];
						
						if(isset($_POST['email']))
							$query['email'] = $_POST['email'];
						
						$query['error'] = 'auth.failed';
						
						DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login', $ext->manifest->params['uri']), $query), 1);
					}
					break;
				}
				break;
				
			case 'authenticated':
				@$authenticated_worker = $_SESSION['login_authenticated_worker'];
				unset($_SESSION['login_authenticated_worker']);
				
				if(empty($authenticated_worker))
					DevblocksPlatform::redirect(new DevblocksHttpRequest(array('login')), 1);
					
				$this->_processAuthenticated($authenticated_worker);
				break;
			
			case 'reset':
				unset($_COOKIE['cerb_login_email']);
				
				$url_writer = DevblocksPlatform::services()->url();
				setcookie('cerb_login_email', null, time()-3600, $url_writer->write('c=login',false,false), null, $url_writer->isSSL(), true);
				
				DevblocksPlatform::redirect(new DevblocksHttpRequest(array('login')));
				break;
			
			case NULL:
				@$url = DevblocksPlatform::importGPC($_REQUEST['url'], 'string', '');
				@$error = DevblocksPlatform::importGPC($_REQUEST['error'], 'string', '');
				
				if(!empty($url))
					$_SESSION['login_post_url'] = $url;
				
				// If we have a cookie remembering the worker, redirect to login form
				if(empty($section) && empty($stack) && !empty($remember_email)
						&& !empty($unauthenticated_worker)
						&& null != ($ext = Extension_LoginAuthenticator::get($unauthenticated_worker->auth_extension_id, false))
					) {
					$query = array(
						'email' => $remember_email,
					);
					
					$devblocks_response = new DevblocksHttpResponse(array('login', $ext->params['uri']), $query);
					DevblocksPlatform::redirect($devblocks_response, 1);
				}
				
				$tpl = DevblocksPlatform::services()->template();
				
				$tpl->assign('remember_me', $remember_email);
				
				if(empty($email) && !empty($remember_email))
					$email = $remember_email;
				
				$tpl->assign('email', $email);
				
				if(!empty($error))
					$tpl->assign('error', $error);
				
				$tpl->display('devblocks:cerberusweb.core::login/login_router.tpl');
				break;
				
			default:
				// Look up the URI as an extension
				if(null != ($ext = Extension_LoginAuthenticator::getByUri($section, true))) {
					// Confirm the tentative worker can access this auth extension
					if(!empty($unauthenticated_worker) && $unauthenticated_worker->auth_extension_id != $ext->id)
						return;
					
					/* @var $ext Extension_LoginAuthenticator */
					$ext->render();
					return;
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
		
		if(null == ($unauthenticated_worker = DAO_Worker::getByEmail($email))) {
			sleep(2); // nag brute force attempts
			// Deceptively send invalid logins to the password page anyway, if shaped like an email
			$query = array('email' => $email);
			DevblocksPlatform::redirect(new DevblocksHttpRequest(array('login','password'), $query));
			
		} else {
			// [TODO] Check the worker's allowed IPs
			
			// Check if worker is disabled, fail early
			if($unauthenticated_worker->is_disabled) {
				$query = array('error' => 'account.disabled');
				DevblocksPlatform::redirect(new DevblocksHttpRequest(array('login'), $query));
			}
			
			// Load the worker's auth extension
			if(null == ($auth_ext = Extension_LoginAuthenticator::get($unauthenticated_worker->auth_extension_id)) || !isset($auth_ext->params['uri'])) {
				$query = array('error' => 'auth.invalid');
				DevblocksPlatform::redirect(new DevblocksHttpRequest(array('login'), $query));
			}
			
			if($remember_me) {
				$url_writer = DevblocksPlatform::services()->url();
				setcookie('cerb_login_email', $email, time()+30*86400, $url_writer->write('c=login',false,false), null, $url_writer->isSSL(), true);
			}
			
			$query = array(
				'email' => $email
			);
			
			DevblocksPlatform::redirect(new DevblocksHttpRequest(array('login',$auth_ext->params['uri']), $query));
		}
	}
	
	// Please be honest
	private function _checkSeats($current_worker) {
		$honesty = CerberusLicense::getInstance();
		$session = DevblocksPlatform::services()->session();
		
		$online_workers = DAO_Worker::getAllOnline(PHP_INT_MAX, 0);
		$max = intval(max($honesty->w, 1));
		
		if($max <= count($online_workers) && $max != 100) {
			// Try to free up (n) seats (n = seats used - seat limit + 1)
			$online_workers = DAO_Worker::getAllOnline(600, count($online_workers) - $max + 1);
			
			// If we failed to open up a seat
			if($max <= count($online_workers) && !isset($online_workers[$current_worker->id])) {
				$session->clear();
				
				$query = array(
					'email' => $current_worker->getEmailString(),
					'error' => 'seats.limit',
				);
				
				if(null == ($ext = Extension_LoginAuthenticator::get($current_worker->auth_extension_id, false)))
					return;
				
				DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login', $ext->params['uri']), $query), 1);
			}
		}
	}
	
	private function _processAuthenticated($authenticated_worker) { /* @var $authenticated_worker Model_Worker */
		$session = DevblocksPlatform::services()->session();

		$visit = new CerberusVisit();
		$visit->setWorker($authenticated_worker);

		$session->setVisit($visit);
		
		// Generate a CSRF token for the session
		$_SESSION['csrf_token'] = CerberusApplication::generatePassword(128);
		
		if(isset($_SESSION['login_post_url'])) {
			$redirect_path = explode('/', $_SESSION['login_post_url']);
			
			// Only valid pages
			if(is_array($redirect_path) && !empty($redirect_path)) {
				$redirect_uri = current($redirect_path);
				
				if(!in_array($redirect_uri, array('explore', 'm')) && !CerberusApplication::getPageManifestByUri($redirect_uri))
					$redirect_path = array();
			}
		}
		
		$devblocks_response = new DevblocksHttpResponse($redirect_path);
		
		// Flush views
		DAO_WorkerViewModel::flush($authenticated_worker->id);
		
		// Flush caches
		DAO_WorkerRole::clearWorkerCache($authenticated_worker->id);
		
		if(empty($devblocks_response->path)) {
			$tour_enabled = intval(DAO_WorkerPref::get($authenticated_worker->id, 'assist_mode', 1));
			$next_page = ($tour_enabled) ?  array('welcome') : array('profiles','worker','me');
			$devblocks_response = new DevblocksHttpResponse($next_page);
		}
		
		/*
		 * Log activity (worker.logged_in)
		 */
		$ip_address = DevblocksPlatform::getClientIp() ?: 'an unknown IP';
		$user_agent = DevblocksPlatform::getClientUserAgent();
		$user_agent_string = sprintf("%s%s%s",
			$user_agent['browser'],
			!empty($user_agent['version']) ? (' ' . $user_agent['version']) : '',
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
		
		DevblocksPlatform::redirect($devblocks_response, 1);
	}
	
	function signoutAction() {
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;
		@array_shift($stack); // login
		@array_shift($stack); // signout
		@$option = DevblocksPlatform::strLower(array_shift($stack));
		
		/*
		 * Log activity (worker.logged_out)
		 */
		$ip_address = DevblocksPlatform::getClientIp() ?: 'an unknown IP';
		
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
		
		$session = DevblocksPlatform::services()->session();
		
		switch($option) {
			case 'all':
				if(null != ($active_worker = CerberusApplication::getActiveWorker()))
					Cerb_DevblocksSessionHandler::destroyByWorkerIds($active_worker->id);
				break;
				
			default:
				$session->clear();
				break;
		}
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login')), 1);
	}
};
