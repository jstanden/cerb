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

class Page_Login extends CerberusPageExtension {
	function isVisible() {
		return true;
	}
	
	static function getErrorMessage($code) {
		$error_messages = [
			'account.disabled' => "Your account is disabled.",
			'account.locked' => "Your account has been temporarily locked after too many failed login attempts. Please wait a few minutes and try again.",
			'auth.expired' => "The code has expired.",
			'auth.failed' => "Authentication failed.",
			'confirm.failed' => "The given confirmation code doesn't match the one on file.",
			'confirm.invalid' => "The given confirmation code is invalid.",
			'email.invalid' => "The provided email address is not valid.",
			'email.unavailable' => "The provided email address is not available.",
			'mfa.failed' => "The security code you entered is incorrect.",
			'password.invalid' => "The given password is invalid.",
			'password.mismatch' => "The given passwords do not match.",
			'seats.limit' => "The maximum number of simultaneous workers are currently active. Please try again later, or ask an administrator to increase the seat count in your license.",
		];
		
		$error = "An unexpected error occurred. Please try again.";
		
		if(array_key_exists($code, $error_messages))
			$error = $error_messages[$code];
		
		return $error;
	}
	
	static function logFailedAuthentication(Model_Worker $unauthenticated_worker) {
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
		
		$entry = [
			//{{ip}} failed to log in as {{target}} using {{user_agent}}
			'message' => 'activities.worker.login.failed',
			'variables' => [
				'ip' => $ip_address,
				'user_agent' => $user_agent_string,
				'target' => sprintf($unauthenticated_worker->getName()),
				],
			'urls' => [
				'target' => sprintf("ctx://%s:%d", CerberusContexts::CONTEXT_WORKER, $unauthenticated_worker->id),
				]
		];
		CerberusContexts::logActivity('worker.login.failed', CerberusContexts::CONTEXT_WORKER, $unauthenticated_worker->id, $entry);
	}
	
	function render() {
		@$error = DevblocksPlatform::importGPC($_REQUEST['error'], 'string', '');
		
		$response = DevblocksPlatform::getHttpResponse();
		$tpl = DevblocksPlatform::services()->template();
		
		if(!empty($error))
			$tpl->assign('error', $error);
		
		$stack = $response->path;
		array_shift($stack); // login
		$uri = array_shift($stack);
		
		switch($uri) {
			case NULL:
				$this->_routeLogin();
				break;
				
			case 'authenticate':
				$this->_routeAuthenticate();
				break;
				
			case 'mfa':
				$this->_routeMultiFactorAuth();
				break;
				
			case 'invite':
				$this->_routeInvite($stack);
				break;
			
			case 'consent':
				$this->_routeConsent();
				break;
			
			case 'authenticated':
				$this->_routeAuthenticated();
				break;
			
			case 'recover':
				$this->_routeRecover($stack);
				break;
			
			default:
				DevblocksPlatform::redirect(new DevblocksHttpRequest(['login']));
				break;
		}
	}
	
	private function _routeLogin() {
		@$url = DevblocksPlatform::importGPC($_REQUEST['url'], 'string', '');
		
		$login_state = CerbLoginWorkerAuthState::getInstance();
		
		if($url)
			$login_state->pushRedirectUri($url);
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('email', $login_state->getEmail());
		
		if(false != ($sso_service_ids = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::AUTH_SSO_SERVICE_IDS, CerberusSettingsDefaults::AUTH_SSO_SERVICE_IDS))) {
			$sso_services = DAO_ConnectedService::getIds(explode(',', $sso_service_ids));
			$tpl->assign('sso_services', $sso_services);
		}
		
		$tpl->display('devblocks:cerberusweb.core::login/login_router.tpl');
	}
	
	private function _routeAuthenticate() {
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'], 'string', '');
		@$password = DevblocksPlatform::importGPC($_REQUEST['password'], 'string', '');
		
		$login_state = CerbLoginWorkerAuthState::getInstance()
			->setEmail($email)
			->clearAuthState()
			;
		
		// If no email/password, or invalid email, fail auth
		if(
			!$email 
			|| !$password 
			|| false == ($unauthenticated_worker = DAO_Worker::getByEmail($email))
		) {
			$query = ['error' => 'auth.failed'];
			DevblocksPlatform::redirect(new DevblocksHttpRequest(['login'], $query));
		}
		
		// Check if worker is disabled, fail early
		if($unauthenticated_worker->is_disabled) {
			$query = ['error' => 'account.disabled'];
			DevblocksPlatform::redirect(new DevblocksHttpRequest(['login'], $query));
		}
		
		// Prevent brute force logins
		$recent_failed_logins = DAO_ContextActivityLog::getLatestEntriesByTarget(CerberusContexts::CONTEXT_WORKER, $unauthenticated_worker->id, 5, ['worker.login.failed'], time()-900);
		
		// More than 5 failed logins in the past 15 minutes
		if(is_array($recent_failed_logins) && count($recent_failed_logins) >= 5) {
			$query = ['error' => 'account.locked'];
			DevblocksPlatform::redirect(new DevblocksHttpRequest(['login'], $query), 1);
		}
		
		// Check credentials
		if($authenticated_worker = DAO_Worker::login($email, $password)) {
			$login_state
				->setWorker($authenticated_worker)
				->setIsPasswordAuthenticated(true)
				;
			
			DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','authenticated']), 1);
			
		} else {
			self::logFailedAuthentication($unauthenticated_worker);
			
			$query = ['error' => 'auth.failed'];
			DevblocksPlatform::redirect(new DevblocksHttpResponse(['login'], $query), 1);
		}
	}
	
	private function _routeAuthenticated() {
		$login_state = CerbLoginWorkerAuthState::getInstance();
		
		if(
			false == ($authenticated_worker = $login_state->getWorker())
			|| !$login_state->isAuthenticated(['ignore_mfa' => true])
		) {
			DevblocksPlatform::redirect(new DevblocksHttpRequest(['login']), 1);
		}
		
		// If we're doing a non-SSO login, check MFA
		if(!$login_state->isSSOAuthenticated()) {
			// Is MFA always required?
			if($authenticated_worker->is_mfa_required) {
				$login_state->setIsMfaRequired(true);
				
			// Or does the worker have MFA set up?
			} else if(null !== (DAO_WorkerPref::get($authenticated_worker->id, 'mfa.totp.seed', null))) {
				$login_state->setIsMfaRequired(true);
			}
			
			// MFA
			if($login_state->isMfaRequired() && !$login_state->isMfaAuthenticated()) {
				DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','mfa']));
			}
		}
		
		// OAuth?
		if($login_state->isConsentRequired() && !$login_state->isConsentGiven()) {
			DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','consent']));
		}
		
		$this->_checkSeats($authenticated_worker);
		$this->_processAuthenticated($authenticated_worker);
	}
	
	private function _routeInvite(array $path=[]) {
		@$code = array_shift($path);
		
		// Do we have a logged in session?
		if(false != (CerberusApplication::getActiveWorker()))
			return;
		
		$token = DAO_ConfirmationCode::getByCode('login.invite', $code);
		
		$login_state = CerbLoginWorkerAuthState::getInstance()
			->clearAuthState()
			;
		
		// Invalid or expired token
		if(empty($token) || $token->created + 7200 < time()) {
			$query = ['error' => 'confirm.invalid'];
			DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','recover'], $query));
		}
		
		if(
			0 == (@$invite_worker_id = intval($token->meta['worker_id']))
			|| false == ($worker = DAO_Worker::get($invite_worker_id))
		) {
			$query = ['error' => 'confirm.invalid'];
			DevblocksPlatform::redirect(new DevblocksHttpRequest(['login'], $query));
		}
		
		if($worker->is_disabled) {
			$query = ['error' => 'account.disabled'];
			DevblocksPlatform::redirect(new DevblocksHttpRequest(['login'], $query));
		}
		
		if($worker->is_password_disabled) {
			$query = ['error' => 'confirm.invalid'];
			DevblocksPlatform::redirect(new DevblocksHttpRequest(['login'], $query));
		}
		
		if(DAO_Worker::hasAuth($worker->id)) {
			$query = ['error' => 'confirm.invalid'];
			DevblocksPlatform::redirect(new DevblocksHttpRequest(['login'], $query));
		}
		
		$worker_email = $worker->getEmailString();
		
		$recover_code = sprintf("%s:%s", $worker_email, $code);
		
		// Delete the one-time use confirmation code
		DAO_ConfirmationCode::delete($token->id);
		
		$login_state
			->setEmail($worker_email)
			->setParam('recover.code', $recover_code)
			->setParam('recover.code.given', $code)
			;
		
		DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','recover','verify']));
	}
	
	private function _routeConsent() {
		$tpl = DevblocksPlatform::services()->template();
		$login_state = CerbLoginWorkerAuthState::getInstance();
		
		if(!$login_state->isAuthenticated())
			DevblocksPlatform::redirect(new DevblocksHttpResponse(['login']), 0);
		
		if(array_key_exists('accept', $_REQUEST)) {
			@$accept = DevblocksPlatform::importGPC($_REQUEST['accept'], 'integer', 0);
			
			$login_state
				->setWasConsentAsked(true)
				->setIsConsentGiven(boolval($accept))
				;
			
			if(false != ($login_post_url = $login_state->popRedirectUri())) {
				DevblocksPlatform::redirectURL($login_post_url);
			}
			exit;
			
		} else {
			// Check which OAuth app ID wants consent
			$consent_params = $login_state->isConsentRequired();
			
			if(
				!is_array($consent_params) 
				|| !array_key_exists('client_id', $consent_params)
				|| false == ($oauth_app = DAO_OAuthApp::getByClientId($consent_params['client_id']))
				|| false == (@$oauth_requested_scopes = $consent_params['scopes'])
			) {
				DevblocksPlatform::dieWithHttpError("Invalid OAuth client.");
			}
			
			/* @var $oauth_requested_scopes Cerb_OAuth2ScopeEntity[] */
			
			$tpl->assign('oauth_app', $oauth_app);
			
			$scopes = [];
			
			foreach($oauth_requested_scopes as $requested_scope) {
				$scope_id = $requested_scope->getIdentifier();
				$scope = $oauth_app->getScope($scope_id);
				$scopes[$scope_id] = $scope;
			}
			
			$tpl->assign('scopes', $scopes);
			
			$tpl->display('devblocks:cerberusweb.core::login/auth/consent/oauth_consent.tpl');
		}
	}
	
	private function _routeMultiFactorAuth() {
		@$action = DevblocksPlatform::importGPC($_REQUEST['action'], 'string', null);
		
		$login_state = CerbLoginWorkerAuthState::getInstance();
		
		// Send back to the login form if they aren't authorized yet
		if(
			false == ($worker = $login_state->getWorker())
			|| !$login_state->isAuthenticated(['ignore_mfa' => true]) 
		) {
			$login_state->clearAuthState();
			DevblocksPlatform::redirect(new DevblocksHttpRequest(['login']), 1);
		}
		
		$setting_can_remember = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::AUTH_MFA_ALLOW_REMEMBER, 0);
		$setting_remember_days = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::AUTH_MFA_REMEMBER_DAYS, 0);
		
		$mfa_totp_seed = DAO_WorkerPref::get($worker->id, 'mfa.totp.seed', null);
		
		switch($action) {
			case 'new_otp':
				// Only allow setting if unconfigured
				if($mfa_totp_seed) {
					$query = ['error' => 'mfa.failed'];
					DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','mfa'], $query));
				}
				
				@$otp = DevblocksPlatform::importGPC($_REQUEST['otp'], 'string', null);
				$otp_seed = $login_state->getParam('mfa.totp.seed');
				
				// If verified
				if(DevblocksPlatform::services()->mfa()->isAuthorized($otp, $otp_seed)) {
					DAO_WorkerPref::set($worker->id, 'mfa.totp.seed', $otp_seed);
					$login_state->setIsMfaAuthenticated(true);
					DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','authenticated']));
				}
				
				// Otherwise
				$query = ['error' => 'mfa.failed'];
				DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','mfa'], $query));
				break;
				
			default:
				@$otp = DevblocksPlatform::importGPC($_REQUEST['otp'], 'string', null);
				@$remember_device = DevblocksPlatform::importGPC($_REQUEST['remember_device'], 'integer', 0);
				
				if($otp) {
					// If verified
					if(DevblocksPlatform::services()->mfa()->isAuthorized($otp, $mfa_totp_seed)) {
						$login_state->setIsMfaAuthenticated(true);
						
						if($setting_can_remember && $remember_device) {
							$encrypt = DevblocksPlatform::services()->encryption();
							$url_writer = DevblocksPlatform::services()->url();
							
							$remember_expires_at = time()+(86400 * $setting_remember_days);
							$remember_bin = pack('N2', $worker->id, time());
							$remember_value = $encrypt->encrypt($remember_bin);
							
							setcookie('mfa:' . $worker->id, $remember_value, $remember_expires_at, '/', NULL, $url_writer->isSSL(), true);
						}
						
						DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','authenticated']));
						
					} else {
						// Failed TOTP challenge
						$login_state
							->setIsMfaAuthenticated(false)
							->setParamIncr('mfa.fail_count', 1)
							;
						
						if($login_state->getParam('mfa.fail_count') > 2) {
							$login_state
								->clearAuthState()
								;
							
							$query = ['error' => 'mfa.failed'];
							DevblocksPlatform::redirect(new DevblocksHttpRequest(['login'], $query));
						}
						
						$query = ['error' => 'mfa.failed'];
						DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','mfa'], $query));
					}
					
				} else {
					// Is this device remembered?
					if($setting_can_remember && array_key_exists('mfa:'.$worker->id, $_COOKIE)) {
						$encrypt = DevblocksPlatform::services()->encryption();
						if(false != ($remember_params = @unpack('Nworker_id/Ncreated_at', $encrypt->decrypt($_COOKIE['mfa:' . $worker->id])))) {
							@$remember_worker_id = $remember_params['worker_id'];
							@$remember_created_at = $remember_params['created_at'];
							$remember_expires_at = $remember_created_at + (86400 * $setting_remember_days);
							
							if(
								$worker->id == $remember_worker_id 
								&& $remember_expires_at > time()
							) {
								$login_state->setIsMfaAuthenticated(true);
								DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','authenticated']));
							}
						}
					}
					
					$tpl = DevblocksPlatform::services()->template();
					$tpl->assign('setting_mfa_can_remember', $setting_can_remember);
					$tpl->assign('setting_mfa_remember_days', $setting_remember_days);
					
					// Is MFA/TOTP configured for this worker?
					if($mfa_totp_seed) {
						$tpl->display('devblocks:cerberusweb.core::login/auth/mfa/totp.tpl');
						
					} else {
						// Do we need to generate a new TOTP seed?
						if(null == ($seed = $login_state->getParam('mfa.totp.seed'))) {
							$seed = DevblocksPlatform::services()->mfa()->generateMultiFactorOtpSeed(24);
							$login_state->setParam('mfa.totp.seed', $seed);
						}
						
						$tpl->assign('seed_name', $worker->getEmailString());
						$tpl->assign('seed', $seed);
						
						$tpl->display('devblocks:cerberusweb.core::login/auth/mfa/totp_setup.tpl');
					}
				}
				break;
		}
	}
	
	private function _routeRecover(array $path=[]) {
		@$uri = array_shift($path);
		
		$tpl = DevblocksPlatform::services()->template();
		$cache = DevblocksPlatform::services()->cache();
		$login_state = CerbLoginWorkerAuthState::getInstance();
		
		
		switch($uri) {
			default:
				@$email = DevblocksPlatform::importGPC($_REQUEST['email'], 'string', '');
				
				$login_state
					->setEmail('')
					->clearAuthState()
					;
				
				if(!$email) {
					$tpl->display('devblocks:cerberusweb.core::login/recover/recover_email.tpl');
					
				} else {
					$validation = DevblocksPlatform::services()->validation();
					
					$validation
						->addField('email', 'Email')
						->string()
						->setNotEmpty(true)
						->setRequired(true)
						->addValidator($validation->validators()->email())
						;
					
					$error = null;
					$fields = [
						'email' => $email,
					];
					
					if(!$validation->validateAll($fields, $error)) {
						$login_state->clearAuthState();
						
						$query = ['error' => 'email.invalid'];
						DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','recover'], $query), 0);
					}
					
					$login_state
						->setEmail($email)
						->unsetParam('recover.code')
						->unsetParam('recover.code.given')
						->unsetParam('recover.code.sent_at')
						;
					
					// Not a worker, or disabled worker, fake having sent the code
					if(
						false == ($unauthenticated_worker = DAO_Worker::getByEmail($email))
						|| $unauthenticated_worker->is_disabled
						|| $unauthenticated_worker->is_password_disabled
					) {
						// Do nothing for invalid emails
						
					} else {
						// This is rate-limited
						$cache_key = sprintf('recover:worker:%d', $unauthenticated_worker->id);
						
						if(false == $cache->load($cache_key)) {
							$labels = $values = $worker_labels = $worker_values = [];
							CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $unauthenticated_worker, $worker_labels, $worker_values, '', true, true);
							CerberusContexts::merge('worker_', null, $worker_labels, $worker_values, $labels, $values);
							
							$values['code'] = CerberusApplication::generatePassword(8);
							$values['ip'] = DevblocksPlatform::getClientIp();
							
							$recover_code = $unauthenticated_worker->getEmailString() . ':' . $values['code'];
							
							CerberusApplication::sendEmailTemplate($unauthenticated_worker->getEmailString(), 'worker_recover', $values);
							
							$cache->save(time(), $cache_key, [], 1800);
							
							$login_state->setParam('recover.code.sent_at', time());
							$login_state->setParam('recover.code', $recover_code);
						}
					}
					
					DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','recover','code']), 0);
				}
				break;
				
			case 'code':
				@$code = DevblocksPlatform::importGPC($_REQUEST['code'], 'string', '');
				
				$login_state
					->unsetParam('recover.code.given')
					;
				
				if(
					false == ($email = $login_state->getEmail())
				) {
					DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','recover']), 0);
				}
				
				if(!$code) {
					$tpl->assign('email', $email);
					$tpl->display('devblocks:cerberusweb.core::login/recover/recover_code.tpl');
					
				} else {
					$login_state
						->setParam('recover.code.given', $code)
						;
					
					if(
						null === ($saved_code = $login_state->getParam('recover.code', null))
						|| !$code
						|| 0 != strcmp(sprintf('%s:%s', $login_state->getEmail(), $code), $saved_code)
					) {
						$login_state
							->setParamIncr('recover.fail_count', 1)
							;
						
						// Too many bad guesses
						if($login_state->getParam('recover.fail_count') > 2) {
							$login_state->clearAuthState();
							
							$query = [ 'error' =>  'auth.failed'];
							DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','recover'], $query), 0);
							
						} else {
							$query = [ 'error' =>  'auth.failed'];
							DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','recover','code'], $query), 0);
						}
					}
					
					// Is the recovery code too old?
					if(time() - $login_state->getParam('recover.code.sent_at', 0) > 600) {
						$query = [ 'error' =>  'auth.expired'];
						DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','recover'], $query), 0);
					}
					
					DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','recover','verify']), 0);
				}
				break;
				
			case 'verify':
				if(
					false == ($email = $login_state->getEmail())
					|| false == ($unauthenticated_worker = DAO_Worker::getByEmail($email))
					|| null === ($recover_code = $login_state->getParam('recover.code', null))
					|| null === ($recover_code_given = $login_state->getParam('recover.code.given', null))
					|| 0 != strcmp(sprintf('%s:%s', $login_state->getEmail(), $recover_code_given), $recover_code)
				) {
					DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','recover']), 0);
				}
				
				$login_state
					->setParam('recover.verified', false)
					;
				
				// MFA?
				if(null !== ($mfa_totp_seed = DAO_WorkerPref::get($unauthenticated_worker->id, 'mfa.totp.seed', null))) {
					@$otp = DevblocksPlatform::importGPC($_REQUEST['otp'], 'string', '');
					
					if(!$otp) {
						$tpl->display('devblocks:cerberusweb.core::login/recover/recover_verify_otp.tpl');
						
					} else {
						// OTP verified
						if(DevblocksPlatform::services()->mfa()->isAuthorized($otp, $mfa_totp_seed)) {
							$login_state
								->setParam('recover.verified', true)
								;
							
							DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','recover','reset']), 0);
							
						} else { // Failed
							$query = [ 'error' =>  'auth.failed'];
							DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','recover','verify'], $query), 0);
						}
					}
					
				// Secret questions?
				} else {
					@$secret_questions = @json_decode(DAO_WorkerPref::get($unauthenticated_worker->id, 'login.recover.secret_questions', '[]'), true);
					@$secret_answers = DevblocksPlatform::importGPC($_REQUEST['secrets'], 'array', []);
					
					// No MFA and no secret questions
					if(0 === count(array_filter($secret_questions, function($arr) {
						return !empty(@$arr['q']);
						
					}))) {
						$login_state
							->setParam('recover.verified', true)
							;
						
						DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','recover','reset']));
						
					} else {
						if(!$secret_answers) {
							if($secret_questions) {
								$tpl->assign('secret_questions', $secret_questions);
							}
							
							$tpl->display('devblocks:cerberusweb.core::login/recover/recover_verify_secrets.tpl');
							
						} else {
							// Test secret challenges
							$answers_needed = 0;
							$answers_correct = 0;
							
							foreach($secret_questions as $idx => $question) {
								if(!array_key_exists('a', $question) || 0 == strlen($question['a']))
									continue;
								
								$answers_needed++;
								
								// Wrong answer?
								if(0 === strcmp($question['a'], $secret_answers[$idx]))
									$answers_correct++;
							}
							
							// If everything was correct
							if($answers_needed == $answers_correct) {
								$login_state
									->setParam('recover.verified', true)
									;
								
								DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','recover','reset']));
								
							} else { // Otherwise, we had some wrong answers
								$query = [ 'error' =>  'auth.failed'];
								DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','recover','verify'], $query));
							}
						}
					}
				}
				break;
				
			case 'reset':
				if(
					false == ($email = $login_state->getEmail())
					|| false == ($unauthenticated_worker = DAO_Worker::getByEmail($email))
					|| null === ($recover_code = $login_state->getParam('recover.code', null))
					|| null === ($recover_code_given = $login_state->getParam('recover.code.given', null))
					|| 0 != strcmp(sprintf('%s:%s', $login_state->getEmail(), $recover_code_given), $recover_code)
					|| true !== $login_state->getParam('recover.verified', false)
				) {
					DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','recover']), 0);
				}
				
				@$password = DevblocksPlatform::importGPC($_REQUEST['password'], 'string', '');
				
				if(!$password) {
					$tpl->display('devblocks:cerberusweb.core::login/recover/recover_reset.tpl');
					
				} else {
					@$password_verify = DevblocksPlatform::importGPC($_REQUEST['password_verify'], 'string', '');
					
					$validation = DevblocksPlatform::services()->validation();
					
					$validation
						->addField('password', 'Password')
						->string()
						->setRequired(true)
						->setMinLength(8)
						->setMaxLength(1024)
						;
					
					$validation
						->addField('password_verify', 'Verified password')
						->string()
						->setPossibleValues([$password])
						;
					
					$error = null;
					$fields = [
						'password' => $password,
						'password_verify' => $password_verify,
					];
					
					if(!$validation->validateAll($fields, $error)) {
						$query = [ 'error' =>  'password.invalid'];
						DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','recover','reset'], $query));
					}
					
					// Success
					
					DAO_Worker::setAuth($unauthenticated_worker->id, $password);
					
					$cache_key = sprintf('recover:worker:%d', $unauthenticated_worker->id);
					$cache->remove($cache_key);
					
					$login_state
						->clearAuthState()
						;
					DevblocksPlatform::redirect(new DevblocksHttpRequest(['login']), 0);
				}
				break;
		}
	}
	
	
	function showAction() {
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('login')));
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
				
				DevblocksPlatform::redirect(new DevblocksHttpResponse(['login'], $query), 1);
			}
		}
	}
	
	private function _processAuthenticated($authenticated_worker) { /* @var $authenticated_worker Model_Worker */
		$login_state = CerbLoginWorkerAuthState::getInstance();
		$session = DevblocksPlatform::services()->session();
		
		$visit = new CerberusVisit();
		$visit->setWorker($authenticated_worker);
		
		$session->setVisit($visit);
		
		// Generate a CSRF token for the session
		$_SESSION['csrf_token'] = CerberusApplication::generatePassword(128);
		
		// Flush views
		DAO_WorkerViewModel::flush($authenticated_worker->id);
		
		// Flush caches
		DAO_WorkerRole::clearWorkerCache($authenticated_worker->id);
		
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
		
		$entry = [
			//{{actor}} logged in from {{ip}} using {{user_agent}}
			'message' => 'activities.worker.logged_in',
			'variables' => [
				'ip' => $ip_address,
				'user_agent' => $user_agent_string,
				],
			'urls' => [],
		];
		CerberusContexts::logActivity('worker.logged_in', null, null, $entry);
		
		$redirect_path = [];
		$login_post_url = $login_state->popRedirectUri();
		$login_state->destroy();
		
		if($login_post_url) {
			if(DevblocksPlatform::strStartsWith($login_post_url, 'internal/redirectRead/')) {
				$url_parts = explode('/', $login_post_url);
				$login_post_url = DevblocksPlatform::services()->url()->write('c=internal&a=redirectRead&id=' . intval(array_pop($url_parts)), true);
			}
			
			if(DevblocksPlatform::strStartsWith($login_post_url, ['http:','https:'])) {
				DevblocksPlatform::redirectURL($login_post_url, 1);
				
			} else {
				$redirect_path = explode('/', $login_post_url);
				
				// Only valid pages
				if($redirect_path && is_array($redirect_path)) {
					$redirect_uri = current($redirect_path);
					
					if(!in_array($redirect_uri, ['explore']) && !CerberusApplication::getPageManifestByUri($redirect_uri))
						$redirect_path = [];
				}
				
				$devblocks_response = new DevblocksHttpResponse($redirect_path);
				DevblocksPlatform::redirect($devblocks_response, 1);
			}
			
		} else {
			$tour_enabled = intval(DAO_WorkerPref::get($authenticated_worker->id, 'assist_mode', 1));
			$next_page = ($tour_enabled) ?  ['welcome'] : [];
			
			$devblocks_response = new DevblocksHttpResponse($next_page);
			DevblocksPlatform::redirect($devblocks_response, 1);
		}
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
