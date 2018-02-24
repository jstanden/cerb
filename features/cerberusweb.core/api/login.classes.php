<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2017, Webgroup Media LLC
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

class DefaultLoginModule extends Extension_LoginAuthenticator {
	function render() {
		@$email = DevblocksPlatform::importGPC($_REQUEST['email']);
		
		// We allow invalid workers to get this far as a diversion to finding real accounts
		@$worker = DAO_Worker::getByEmail($email);
		
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
				if($worker instanceof Model_Worker && !DAO_Worker::hasAuth($worker->id)) {
					$query = array(
						'email' => $worker->getEmailString(),
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
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('worker', $worker);
		$tpl->display('devblocks:cerberusweb.core::login/auth/prefs.tpl');
	}
	
	function saveWorkerPrefs($worker) {
		@$reset_login = DevblocksPlatform::importGPC($_REQUEST['reset_login'], 'integer', 0);
		
		$session = DevblocksPlatform::services()->session();
		$visit = CerberusApplication::getVisit();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!($active_worker->is_superuser || $active_worker->id == $worker->id))
			return false;
		
		if($reset_login) {
			$this->resetCredentials($worker);
			
			// Redirect if we're the current worker
			if(!$visit->isImposter() && $worker->id == $active_worker->id) {
				$session->clear();
				
				$query = array(
					'email' => $worker->getEmailString(),
				);
				DevblocksPlatform::redirect(new DevblocksHttpRequest(array('login'), $query));
			}
		}
	}
	
	private function _renderLoginForm($worker) {
		$tpl = DevblocksPlatform::services()->template();
		
		@$error = DevblocksPlatform::importGPC($_REQUEST['error'], 'string', '');
		$tpl->assign('error', $error);
		
		if(!empty($worker)) {
			$email = $worker->getEmailString();
		} else {
			$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
		}
		
		$tpl->assign('email', $email);
		
		$tpl->display('devblocks:cerberusweb.core::login/auth/login.tpl');
	}
	
	private function _renderLoginSetupForm($worker) {
		$tpl = DevblocksPlatform::services()->template();
		
		if(!$worker)
			DevblocksPlatform::redirect(new DevblocksHttpRequest(['login']));
		
		$tpl->assign('worker', $worker);
		
		@$code = DevblocksPlatform::importGPC($_REQUEST['code'], 'string', '');
		$tpl->assign('code', $code);
		
		@$error = DevblocksPlatform::importGPC($_REQUEST['error']);
		$tpl->assign('error', $error);
		
		if(!isset($_SESSION['recovery_code'])) {
			$recovery_code = CerberusApplication::generatePassword(8);
			$_SESSION['recovery_code'] = $worker->getEmailString() . ':' . $recovery_code;
			
			$labels = $values = [];
			CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $worker, $worker_labels, $worker_values, '', true, true);
			CerberusContexts::merge('worker_', null, $worker_labels, $worker_values, $labels, $values);
			
			$values['code'] = $recovery_code;
			$values['ip'] = DevblocksPlatform::getClientIp();
			
			CerberusApplication::sendEmailTemplate($worker->getEmailString(), 'worker_recover', $values);
		}
		$tpl->display('devblocks:cerberusweb.core::login/auth/setup.tpl');
	}
	
	private function _processLoginSetupForm($worker) {
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
				
				DAO_Worker::setAuth($worker->id, $password);
			}
			
			// If successful, clear the session data
			unset($_SESSION['recovery_code']);
			
			// Redirect to log in form
			$query = array('email' => $worker->getEmailString());
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login','password'), $query));
			
		} catch(CerbException $e) {
			$query = array(
				'email' => $worker->getEmailString(),
				'error' => $e->getMessage(),
			);
			
			if(!empty($confirm_code))
				$query['code'] = $confirm_code;
			
			DevblocksPlatform::redirect(new DevblocksHttpResponse(array('login','password','setup'), $query));
		}
	}
	
	function resetCredentials($worker) {
		DAO_Worker::setAuth($worker->id, null);
	}
	
	function authenticate() {
		// Pull from $_POST
		@$email = DevblocksPlatform::importGPC($_POST['email']);
		@$password = DevblocksPlatform::importGPC($_POST['password']);

		$worker = DAO_Worker::login($email, $password);
		
		if($worker instanceof Model_Worker) {
			return $worker;
			
		} else {
			return false;
		}
	}
};

