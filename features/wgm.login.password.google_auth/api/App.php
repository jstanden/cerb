<?php
class Login_PasswordAndGoogleAuth extends Extension_LoginAuthenticator {
	function render() {
		@$email = DevblocksPlatform::importGPC($_REQUEST['email']);
		
		if(null == ($worker = DAO_Worker::getByEmail($email)))
			return;
		
		// Verify that this is a legitimate login extension for this worker
		if($worker->auth_extension_id != $this->manifest->id)
			return;
		
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;
		
		@array_shift($stack); // login
		@array_shift($stack); // password-gauth
		@$screen = array_shift($stack);
		
		switch($screen) {
			case 'setup':
				@$do_submit = DevblocksPlatform::importGPC($_REQUEST['do_submit'], 'integer', 0);
				
				if($do_submit) {
					$this->_processLoginSetupForm($worker);
					
				} else {
					$this->_renderLoginSetupForm($worker);
					
				}
				
				break;
				
			default:
				// Check the worker pref for a seed
				$seed = DAO_WorkerPref::get($worker->id, 'login.password.google_auth.seed');
				
				if(empty($seed) || !DAO_Worker::hasAuth($worker->id)) {
					$query = array();
					
					if(!empty($email))
						$query['email'] = $worker->getEmailString();
					
					@$code = DevblocksPlatform::importGPC($_REQUEST['code'], 'string', '');
					
					if(!empty($code))
						$query['code'] = $code;
					
					DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login','password-gauth','setup'), $query));
					
				} else {
					$this->_renderLoginForm($worker);
					
				}
				break;
		}
	}
	
	function renderWorkerConfig(Model_Worker $worker=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('worker', $worker);
		
		$tpl->display('devblocks:cerberusweb.core::login/auth/worker_config.tpl');
	}
	
	function saveWorkerConfig($worker_id, array $params, &$error=null) {
		$login_ext = Extension_LoginAuthenticator::get(DefaultLoginModule::ID, true);
		
		// Set the password
		if(false === ($login_ext->saveWorkerConfig($worker_id, $params, $error)))
			return false;
		
		return true;
	}
	
	function renderWorkerPrefs($worker) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('worker', $worker);
		$tpl->display('devblocks:wgm.login.password.google_auth::login/prefs.tpl');
	}
	
	function saveWorkerPrefs($worker) {
		@$reset_login = DevblocksPlatform::importGPC($_REQUEST['reset_login'], 'integer', 0);
		
		$session = DevblocksPlatform::services()->session();
		$visit = CerberusApplication::getVisit();
		$worker = CerberusApplication::getActiveWorker();
		
		if($reset_login) {
			$this->resetCredentials($worker);
			
			// If we're not an imposter, go to the login form
			if(!$visit->isImposter()) {
				$session->clear();
				$query = array(
					'email' => $worker->getEmailString(),
					//'url' => '', // [TODO] This prefs URL
				);
				DevblocksPlatform::redirect(new DevblocksHttpRequest(array('login'), $query));
			}
		}
	}
	
	private function _renderLoginForm(Model_Worker $worker) {
		// draws HTML form of controls needed for login information
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('worker', $worker);
		
		@$error = DevblocksPlatform::importGPC($_REQUEST['error']);
		
		if($error) {
			$error_msg = ChSignInPage::getErrorMessage($error);
			$tpl->assign('error', $error_msg);
		}
		
		$tpl->display('devblocks:wgm.login.password.google_auth::login/login.tpl');
	}
	
	private function _renderLoginSetupForm(Model_Worker $worker) {
		// draws HTML form of controls needed for login information
		$tpl = DevblocksPlatform::services()->template();

		$tpl->assign('worker', $worker);

		@$code = DevblocksPlatform::importGPC($_REQUEST['code'], 'string', '');
		$tpl->assign('code', $code);
		
		@$error = DevblocksPlatform::importGPC($_REQUEST['error']);
		
		if($error) {
			$error_msg = ChSignInPage::getErrorMessage($error);
			$tpl->assign('error', $error_msg);
		}
		
		if(!isset($_SESSION['recovery_code'])) {
			$recovery_code = CerberusApplication::generatePassword(8);
			
			$_SESSION['recovery_code'] = $worker->getEmailString() . ':' . $recovery_code;
			
			$labels = $values = $worker_labels = $worker_values = [];
			CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $worker, $worker_labels, $worker_values, '', true, true);
			CerberusContexts::merge('worker_', null, $worker_labels, $worker_values, $labels, $values);
			
			$values['code'] = $recovery_code;
			$values['ip'] = DevblocksPlatform::getClientIp();
			
			CerberusApplication::sendEmailTemplate($worker->getEmailString(), 'worker_recover', $values);
		}
		
		if(isset($_SESSION['recovery_seed'])) {
			$seed = $_SESSION['recovery_seed'];
			
		} else {
			$seed = $this->_generateRandomSeed();
			$_SESSION['recovery_seed'] = $seed;
		}
		
		$tpl->assign('seed', $seed);
		$tpl->assign('worker_has_auth', DAO_Worker::hasAuth($worker->id));
		
		$tpl->display('devblocks:wgm.login.password.google_auth::login/setup.tpl');
	}
	
	private function _processLoginSetupForm(Model_Worker $worker) {
		try {
			// Compare the confirmation code
			if(!isset($_SESSION['recovery_code'])) {
				throw new CerbException("confirm.invalid");
			}
			
			@$session_confirm_code = DevblocksPlatform::importGPC($_SESSION['recovery_code']);
			@$confirm_code = DevblocksPlatform::importGPC($_REQUEST['confirm_code']);
			
			if(empty($session_confirm_code) || empty($confirm_code)) {
				throw new CerbException("confirm.invalid");
			}
			
			if($session_confirm_code != $worker->getEmailString().':'.$confirm_code) {
				unset($_SESSION['recovery_code']);
				throw new CerbException("confirm.failed");
			}
			
			// Compare the OTP
			if(!isset($_SESSION['recovery_seed'])) {
				throw new CerbException("confirm.failed");
			}

			$otp = $this->_getOneTimePassword($_SESSION['recovery_seed']);
			@$otp_code = DevblocksPlatform::importGPC($_REQUEST['otp_code']);
			
			// Compare the OTP code
			if(empty($otp) || empty($otp_code) || $otp != $otp_code)
				throw new CerbException("confirm.invalid");
			
			@$password = DevblocksPlatform::importGPC($_REQUEST['password']);
			@$password_confirm = DevblocksPlatform::importGPC($_REQUEST['password_confirm']);
			
			// Make sure the passwords match, if required
			if(!DAO_Worker::hasAuth($worker->id)) {
				
				if(empty($password) || empty($password_confirm)) {
					throw new CerbException("password.invalid");
				}
				
				if($password != $password_confirm) {
					throw new CerbException("password.mismatch");
				}
				
				// Update the password when correct, even if Google Auth fails
				DAO_Worker::setAuth($worker->id, $password);
			}
			
			DAO_WorkerPref::set($worker->id, 'login.password.google_auth.seed', $_SESSION['recovery_seed']);
			
			// If successful, clear the session data
			unset($_SESSION['recovery_code']);
			unset($_SESSION['recovery_seed']);
			
			// Redirect to log in form
			$query = array('email' => $worker->getEmailString());
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login','password-gauth'), $query));
			
		} catch(CerbException $e) {
			$query = array(
				'email' => $worker->getEmailString(),
				'error' => $e->getMessage(),
			);
			
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login','password-gauth','setup'), $query));
		}
		
	}
	
	private function _generateRandomSeed() {
		$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
		$seed = '';
		
		for($x=0; $x<16; $x++) {
			$seed .= substr($alphabet, mt_rand(0, 31), 1);
		}
		
		return $seed;
	}
	
	private function _getOneTimePassword($seed) {
		$timestamp = floor(microtime(true)/30);
		$binary_key = DevblocksPlatform::strBase32Decode($seed);
		$binary_timestamp = pack('N*', 0) . pack('N*', $timestamp);
		
		// References: https://github.com/tadeck/onetimepass
		$hash = hash_hmac('sha1', $binary_timestamp, $binary_key, true);
		$offset = ord($hash[19]) & 0xf;
		$bytes = unpack("N*", substr($hash, $offset, 4));
		
		return (array_shift($bytes) & 0x7fffffff) % 1000000;
	}
	
	function resetCredentials($worker) {
		DAO_Worker::setAuth($worker->id, null);
		DAO_WorkerPref::delete($worker->id, 'login.password.google_auth.seed');
	}
	
	function authenticate() {
		// Pull from $_POST
		@$email = DevblocksPlatform::importGPC($_POST['email']);
		@$password = DevblocksPlatform::importGPC($_POST['password']);
		@$access_code = DevblocksPlatform::importGPC($_POST['access_code']);

		if(null == ($worker = DAO_Worker::getByEmail($email)))
			return;
		
		// Load OTP seed from worker prefs
		$seed = DAO_WorkerPref::get($worker->id, 'login.password.google_auth.seed');
		
		if(empty($seed))
			return false;
		
		$otp = $this->_getOneTimePassword($seed);
		
		// Test access code
		if($otp != $access_code)
			return false;
		
		$worker = DAO_Worker::login($email, $password);
		
		if($worker instanceof Model_Worker) {
			return $worker;
			
		} else {
			return false;
		}
	}
};