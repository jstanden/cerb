<?php
class ServiceProvider_Ldap extends Extension_ConnectedServiceProvider {
	const ID = 'cerb.service.provider.ldap';
	
	function handleActionForService(string $action) {
		return false;
	}

	/**
	 * Open an LDAP connection with the given settings and (optionally) TLS.
	 *
	 * PHP's ldap extension can't set LDAP_OPT_X_TLS_NEWCTX, so per-connection TLS
	 * options are silently ignored. The cert-verification options must be set on the
	 * global (null) handle *before* ldap_connect() — each new connection then reads the
	 * current global defaults when it builds its TLS context. We always set both
	 * REQUIRE_CERT and CACERTFILE so nothing bleeds between php-fpm requests.
	 *
	 * @return array{0: ?\LDAP\Connection, 1: ?string} [$ldap, $error]
	 */
	public static function establishConnection(array $settings) : array {
		if(!extension_loaded('ldap'))
			return [null, "The 'ldap' extension is not enabled."];

		$host = $settings['host'] ?? '';
		$port = intval($settings['port'] ?? 389) ?: 389;
		$encryption = $settings['encryption'] ?? '';
		$cert_verify = $settings['cert_verify'] ?? '';
		$ca_cert = trim((string)($settings['ca_cert'] ?? ''));

		// Resolve the TLS mode from the encryption setting + port
		if('disabled' == $encryption) {
			$tls_mode = 'none';
		} elseif('ssl' == $encryption || (636 == $port && '' == $encryption)) {
			$tls_mode = 'ssl'; // implicit LDAPS
		} elseif('tls' == $encryption || (389 == $port && '' == $encryption)) {
			$tls_mode = 'starttls';
		} else {
			$tls_mode = 'none';
		}

		// Apply cert-verification options globally before connecting (see docblock)
		if('none' != $tls_mode) {
			$require_cert = match($cert_verify) {
				'never' => LDAP_OPT_X_TLS_NEVER,
				'allow' => LDAP_OPT_X_TLS_ALLOW,
				default => LDAP_OPT_X_TLS_DEMAND,
			};

			ldap_set_option(null, LDAP_OPT_X_TLS_REQUIRE_CERT, $require_cert);

			if('' !== $ca_cert) {
				$ca_tmpfile = tempnam(sys_get_temp_dir(), 'cerb_ldap_ca_');
				file_put_contents($ca_tmpfile, $ca_cert);
				@chmod($ca_tmpfile, 0600);
				// The CA file must survive the caller's bind; clean up at request end
				register_shutdown_function(fn() => @unlink($ca_tmpfile));
				ldap_set_option(null, LDAP_OPT_X_TLS_CACERTFILE, $ca_tmpfile);
			} else {
				// Reset any CA file lingering from a prior request in this php-fpm worker
				@ldap_set_option(null, LDAP_OPT_X_TLS_CACERTFILE, '');
			}
		}

		if('ssl' == $tls_mode && !DevblocksPlatform::strStartsWith($host, 'ldaps://'))
			$host = 'ldaps://' . $host;

		@$ldap = ldap_connect($host ?: null, $port);

		if(!$ldap)
			return [null, ldap_error($ldap) ?: 'Failed to connect to the LDAP server.'];

		ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

		if('starttls' == $tls_mode && !@ldap_start_tls($ldap))
			return [null, 'Failed to Start TLS'];

		return [$ldap, null];
	}

	private function _testLdap($params) {
		if(!extension_loaded('ldap'))
			return "The 'ldap' extension is not enabled.";
		
		if(!isset($params['host']) || empty($params['host']))
			return "The 'Host' is required.";
		
		if(!isset($params['port']) || empty($params['port']))
			return "The 'Port' is required.";
		
		if(!isset($params['bind_dn']) || empty($params['bind_dn']))
			return "The 'Bind DN' is required.";
		
		if(!isset($params['bind_password']) || empty($params['bind_password']))
			return "The 'Bind Password' is required.";
		
		// Test the credentials

		[$ldap, $error] = self::establishConnection($params);

		if(!$ldap)
			return $error ?: 'Failed to connect to the LDAP server.';

		@$login = ldap_bind($ldap, $params['bind_dn'], $params['bind_password']);
		
		if(empty($login))
			return ldap_error($ldap);
		
		return true;
	}
	
	function renderConfigForm(Model_ConnectedService $service) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$params = $service->decryptParams($active_worker);
		$tpl->assign('params', $params);
		
		$tpl->display('devblocks:cerberusweb.core::internal/connected_service/providers/ldap/config_service.tpl');
	}
	
	function saveConfigForm(Model_ConnectedService $service, array &$params, &$error=null) {
		$edit_params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', []);
		
		if(true !== ($result = $this->_testLdap($edit_params))) {
			$error = $result;
			return false;
		}
		
		foreach($edit_params as $k => $v) {
			switch($k) {
				case 'host':
				case 'port':
				case 'encryption':
				case 'cert_verify':
				case 'ca_cert':
				case 'bind_dn':
				case 'bind_password':
				case 'context_search':
				case 'field_email':
				case 'field_firstname':
				case 'field_lastname':
					$params[$k] = $v;
					break;
			}
		}
		
		return true;
	}
	
	public function renderAccountConfigForm(Model_ConnectedService $service, Model_ConnectedAccount $account) {
		// Not needed
	}

	public function saveAccountConfigForm(Model_ConnectedService $service, Model_ConnectedAccount $account, array &$params, &$error = null) {
		// Not needed
	}
	
	public function authenticateHttpRequest(Model_ConnectedAccount $account, Psr\Http\Message\RequestInterface &$request, array &$options = []): bool {
		return true;
	}
	
	private function _authenticate(Model_ConnectedService $service, $email, $password) {
		// Check for extension
		if(!extension_loaded('ldap'))
			return false;
		
		if(empty($email) || empty($password))
			return false;
		
		// Look up worker by email
		if(null == ($address = DAO_Address::getByEmail($email)))
			return false;
		
		if(null == ($worker = $address->getWorker()))
			return false;
		
		if($worker->is_disabled)
			return false;
		
		// Check service type of LDAP
		if(0 != strcmp(self::ID, $service->extension_id))
			return false;
		
		$service_params = $service->decryptParams();
		
		$ldap_settings = [
			'host' => @$service_params['host'],
			'port' => @$service_params['port'] ?: 389,
			'encryption' => @$service_params['encryption'] ?? '',
			'cert_verify' => @$service_params['cert_verify'] ?? '',
			'ca_cert' => @$service_params['ca_cert'] ?? '',
			'username' => @$service_params['bind_dn'],
			'password' => @$service_params['bind_password'],

			'context_search' => @$service_params['context_search'],
			'field_email' => @$service_params['field_email'],
			'field_firstname' => @$service_params['field_firstname'],
			'field_lastname' => @$service_params['field_lastname'],
		];

		[$ldap, $error] = self::establishConnection($ldap_settings);

		if(!$ldap)
			return false;

		@$login = ldap_bind($ldap, $ldap_settings['username'], $ldap_settings['password']);
		
		if(!$login)
			return false;
	
		$query = sprintf("(%s=%s)", $ldap_settings['field_email'], $address->email);
		@$results = ldap_search($ldap, $ldap_settings['context_search'], $query);
		@$entries = ldap_get_entries($ldap, $results);
		$count = intval($entries['count'] ?? null);
		
		if(empty($count))
			return false;
		
		// Try to bind as the worker's DN
		
		$dn = $entries[0]['dn'];
		
		if(@ldap_bind($ldap, $dn, $password)) {
			@ldap_unbind($ldap);
			return $worker;
		}
		
		@ldap_unbind($ldap);
		
		return false;
	}
	
	public function sso(Model_ConnectedService $service, array $path) {
		@$uri = array_shift($path);
		$error = DevblocksPlatform::importGPC($_REQUEST['error'] ?? null, 'string', '');
		
		if(!extension_loaded('ldap'))
			DevblocksPlatform::dieWithHttpError("The `ldap` PHP extension is not enabled.");
		
		$tpl = DevblocksPlatform::services()->template();
		$login_state = CerbLoginWorkerAuthState::getInstance();
		
		if($error)
			$tpl->assign('error', $error);
		
		switch($uri) {
			default:
				$settings = DevblocksPlatform::services()->pluginSettings();
				
				$login_state
					->clearAuthState()
					;
				
				$tpl->assign('settings', $settings);
				$tpl->assign('service', $service);
				$tpl->assign('email', $login_state->getEmail());
				$tpl->assign('pref_dark_mode', true);

				$tpl->display('devblocks:cerberusweb.core::header.tpl');
				$tpl->display('devblocks:cerberusweb.core::internal/connected_service/providers/ldap/sso/login.tpl');
				$tpl->display('devblocks:cerberusweb.core::footer.tpl');
				break;
				
			case 'authenticate':
				$email = DevblocksPlatform::importGPC($_POST['email'] ?? null, 'string', '');
				$password = DevblocksPlatform::importGPC($_POST['password'] ?? null, 'string', '');
				
				sleep(2);
				
				$login_state
					->setEmail($email)
					;
				
				$unauthenticated_worker = null;
				
				// Prevent brute force logins
				if($email && $unauthenticated_worker = DAO_Worker::getByEmail($email)) {
					$recent_failed_logins = DAO_ContextActivityLog::getLatestEntriesByTarget(CerberusContexts::CONTEXT_WORKER, $unauthenticated_worker->id, 5, ['worker.login.failed'], time()-900);
					
					if(is_array($recent_failed_logins) && count($recent_failed_logins) >= 5) {
						$query = ['error' => 'account.locked'];
						DevblocksPlatform::redirect(new DevblocksHttpRequest(['sso',$service->uri], $query));
					}
				}
				
				// Test auth
				if(false != ($authenticated_worker = $this->_authenticate($service, $email, $password))) {
					$login_state
						->setWorker($authenticated_worker)
						->setIsPasswordAuthenticated(true)
						//->setIsSSOAuthenticated(true)
						;
					
					DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','authenticated']));
					
				} else {
					$login_state
						->clearAuthState()
						;
					
					// Log the failed attempt
					if($unauthenticated_worker) {
						Page_Login::logFailedAuthentication($unauthenticated_worker);
					}
					
					$query = ['error' => 'auth.failed'];
					DevblocksPlatform::redirect(new DevblocksHttpRequest(['sso',$service->uri], $query));
				}
				break;
		}
	}
};