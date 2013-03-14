<?php
/***********************************************************************
| Cerb(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2013, Webgroup Media LLC
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
	function render() {
		@$email = DevblocksPlatform::importGPC($_REQUEST['email']);
		
		// We allow invalid workers to get this far as a diversion to finding real accounts
		@$worker_id = DAO_Worker::getByEmail($email);
		@$worker = DAO_Worker::get($worker_id);
		
		$request = DevblocksPlatform::getHttpRequest();
		$stack = $request->path;

		@array_shift($stack); // login
		@array_shift($stack); // password
		@$page = array_shift($stack);
		
		switch($page) {
			case 'setup':
				@$do_submit = DevblocksPlatform::importGPC($_REQUEST['do_submit'], 'integer', 0);
				
				if($do_submit) {
					$this->_processLoginSetupForm($worker);
					
				} else {
					$this->_renderLoginSetupForm($worker);
					
				}
				break;
				
			default:
				if(!empty($worker) && empty($worker->pass)) {
					$query = array(
						'email' => $worker->email,
					);
					
					@$code = DevblocksPlatform::importGPC($_REQUEST['code'], 'string', '');
					
					if(!empty($code))
						$query['code'] = $code;
					
					DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login','password','setup'), $query));
				}
				
				$this->_renderLoginForm($worker);
				break;
		}
	}
	
	function renderWorkerPrefs($worker) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('worker', $worker);
		$tpl->display('devblocks:cerberusweb.core::login/auth/prefs.tpl');
	}
	
	function saveWorkerPrefs($worker) {
		@$reset_login = DevblocksPlatform::importGPC($_REQUEST['reset_login'], 'integer', 0);
		
		$session = DevblocksPlatform::getSessionService();
		$visit = CerberusApplication::getVisit();
		$worker = CerberusApplication::getActiveWorker();
		
		if($reset_login) {
			$this->resetCredentials($worker);
			
			// If we're not an imposter, go to the login form
			if(!$visit->isImposter()) {
				$session->clear();
				$query = array(
					'email' => $worker->email,
				);
				DevblocksPlatform::redirect(new DevblocksHttpRequest(array('login'), $query));
			}
		}
	}
	
	private function _renderLoginForm($worker) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		@$error = DevblocksPlatform::importGPC($_REQUEST['error'], 'string', '');
		$tpl->assign('error', $error);
		
		if(!empty($worker)) {
			$email = $worker->email;
		} else {
			$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
		}
		
		$tpl->assign('email', $email);
		
		$tpl->display('devblocks:cerberusweb.core::login/auth/login.tpl');
	}
	
	private function _renderLoginSetupForm($worker) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('worker', $worker);
		
		@$code = DevblocksPlatform::importGPC($_REQUEST['code'], 'string', '');
		$tpl->assign('code', $code);
		
		@$error = DevblocksPlatform::importGPC($_REQUEST['error']);
		$tpl->assign('error', $error);
		
		if(!isset($_SESSION['recovery_code'])) {
			$recovery_code = CerberusApplication::generatePassword(8);
			
			$_SESSION['recovery_code'] = $worker->email . ':' . $recovery_code;
			
			// [TODO] Email or SMS it through the new recovery platform service
			CerberusMail::quickSend($worker->email, 'Your confirmation code', $recovery_code);
		}
		$tpl->display('devblocks:cerberusweb.core::login/auth/setup.tpl');
	}
	
	private function _processLoginSetupForm($worker) {
		try {
			// Compare the confirmation code
			if(!isset($_SESSION['recovery_code'])) {
				throw new CerbException("Invalid confirmation code.");
			}
			
			@$session_confirm_code = DevblocksPlatform::importGPC($_SESSION['recovery_code']);
			@$confirm_code = DevblocksPlatform::importGPC($_REQUEST['confirm_code']);
			
			if(empty($session_confirm_code) || empty($confirm_code)) {
				throw new CerbException("Invalid confirmation code.");
			}
			
			if($session_confirm_code != $worker->email.':'.$confirm_code) {
				unset($_SESSION['recovery_code']);
				throw new CerbException("The given confirmation code doesn't match the one on file.");
			}
			
			@$password = DevblocksPlatform::importGPC($_REQUEST['password']);
			@$password_confirm = DevblocksPlatform::importGPC($_REQUEST['password_confirm']);
			
			// Make sure the passwords match, if required
			if(empty($worker->pass)) {
				
				if(empty($password) || empty($password_confirm)) {
					throw new CerbException("Passwords cannot be blank.");
				}
				
				if($password != $password_confirm) {
					throw new CerbException("The given passwords don't match.");
				}
				
				// [TODO] Use DAO_Worker::setPassword() for strong hashing
				DAO_Worker::update($worker->id, array(
					DAO_Worker::PASSWORD => md5($password),
				));
			}
			
			DAO_WorkerPref::set($worker->id, 'login.password.google_auth.seed', $_SESSION['recovery_seed']);
			
			// If successful, clear the session data
			unset($_SESSION['recovery_code']);
			
			// Redirect to log in form
			$query = array('email' => $worker->email);
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login','password'), $query));
			
		} catch(CerbException $e) {
			$query = array(
				'email' => $worker->email,
				'error' => $e->getMessage(),
			);
			
			if(!empty($confirm_code))
				$query['code'] = $confirm_code;
			
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login','password','setup'), $query));
		}
	}
	
	function resetCredentials($worker) {
		DAO_Worker::update($worker->id, array(
			DAO_Worker::PASSWORD => '',
		));
	}
	
	function authenticate() {
		// Pull from $_POST
		@$email = DevblocksPlatform::importGPC($_POST['email']);
		@$password = DevblocksPlatform::importGPC($_POST['password']);

		$worker = DAO_Worker::login($email, $password);
		
		if(!is_null($worker)) {
			return $worker;
			
		} else {
			return false;
		}
	}
};

