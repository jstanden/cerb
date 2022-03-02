<?php
class ServiceProvider_SAML extends Extension_ConnectedServiceProvider {
	const ID = 'cerb.service.provider.saml.idp';
	
	function handleActionForService(string $action) {
		return false;
	}
	
	public function renderConfigForm(Model_ConnectedService $service) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$params = $service->decryptParams($active_worker);
		$tpl->assign('params', $params);
		
		$tpl->display('devblocks:cerberusweb.core::internal/connected_service/providers/saml/config_service.tpl');
	}

	public function saveConfigForm(Model_ConnectedService $service, array &$params, &$error=null) {
		$edit_params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', []);
		
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField('entity_id','Entity ID')
			->string()
			->setRequired(true)
			;
		$validation
			->addField('url_sso','SSO URL')
			->url()
			->setRequired(true)
			;
		$validation
			->addField('url_slo','SLO URL')
			->url()
			->setNotEmpty(false)
			;
		$validation
			->addField('cert','X.509 Certificate')
			->string()
			->setMaxLength(2048) // [TODO] Validate as a cert
			->setRequired(true)
			;
		
		if(false == $validation->validateAll($edit_params, $error))
			return false;
		
		foreach($edit_params as $k => $v)
			$params[$k] = $v;
		
		return true;
	}
	
	// If not instantiable, don't need this
	public function renderAccountConfigForm(Model_ConnectedService $service, Model_ConnectedAccount $account) {
	}

	// If not instantiable, don't need this
	public function saveAccountConfigForm(Model_ConnectedService $service, Model_ConnectedAccount $account, array &$params, &$error = null) {
	}
	
	private function _getSamlSettings(Model_ConnectedService $service=null) {
		$url_service = DevblocksPlatform::services()->url();
		
		if(false == ($uri = $service->uri))
			return false;
		
		$entity_id = $url_service->write(sprintf('c=sso&uri=%s&a=metadata', $uri), true);
		$url_acs = $url_service->write(sprintf('c=sso&uri=%s', $uri), true);
		$url_sls = $url_service->write(sprintf('c=sso&uri=%s&a=sls', $uri), true);
		
		$settings = [
			'strict' => true,
			'baseurl' => $url_service->write('c=sso', true),
			'sp' => [
				'entityId' => $entity_id,
				'assertionConsumerService' => [
					'url' => $url_acs,
					'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
				],
				'singleLogoutService' => [
					'url' => $url_sls,
					'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
				],
				'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
				//'x509cert' => '',
				//'x509certNew' => '',
				//'privateKey' => '',
			],
		];
		
		if(is_null($service))
			return $settings;
		
		$saml_params = $service->decryptParams();
		
		$settings['idp'] = [
			'entityId' => @$saml_params['entity_id'],
			'singleSignOnService' => [
				'url' => @$saml_params['url_sso'],
				'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
			],
			'singleLogoutService' => [
				'url' => @$saml_params['url_slo'],
				'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
			],
			'x509cert' => @$saml_params['cert'],
		];
		
		return $settings;
	}
	
	public function sso(Model_ConnectedService $service, array $path) {
		@$uri = array_shift($path);
		
		$settings_info = $this->_getSamlSettings($service);
		
		switch($uri) {
			case 'login':
			default:
				$url_writer = DevblocksPlatform::services()->url();
				
				$login_state = CerbLoginWorkerAuthState::getInstance()
					->clearAuthState()
					;
				
				$auth = new \OneLogin\Saml2\Auth($settings_info);
				
				if(!array_key_exists('SAMLResponse', $_POST)) {
					$sso_redirect_to = $auth->login(null, [], false, false, true);
					$_SESSION['AuthNRequestID'] = $auth->getLastRequestID();
					DevblocksPlatform::redirectUrl($sso_redirect_to);
				}
				
				if(isset($_SESSION) && isset($_SESSION['AuthNRequestID'])) {
					$requestID = $_SESSION['AuthNRequestID'];
					
				} else {
					$requestID = null;
				}
				
				$auth->processResponse($requestID);
				unset($_SESSION['AuthNRequestID']);
				
				$errors = $auth->getErrors();
				
				if(!empty($errors)) {
					$query = ['error' => 'auth.failed'];
					DevblocksPlatform::redirect(new DevblocksHttpResponse(['login'], $query), 0);
				}
				
				if(!$auth->isAuthenticated()) {
					$query = ['error' => 'auth.failed'];
					DevblocksPlatform::redirect(new DevblocksHttpResponse(['login'], $query), 0);
				}
				
				$_SESSION['samlNameId'] = $auth->getNameId();
				
				$self_url = $url_writer->write('c=sso&uri=' . $service->uri, true);
				
				if(array_key_exists('RelayState', $_POST) && $self_url != $_POST['RelayState']) {
					//$auth->redirectTo($_POST['RelayState']);
				}
				
				$email = $_SESSION['samlNameId'];
				
				
				// Look up worker by email
				if(!$email || null == ($authenticated_worker = DAO_Worker::getByEmail($email))) {
					$query = ['error' => 'auth.failed'];
					DevblocksPlatform::redirect(new DevblocksHttpResponse(['login'], $query), 0);
				}
				
				if($authenticated_worker->is_disabled) {
					$query = ['error' => 'account.disabled'];
					DevblocksPlatform::redirect(new DevblocksHttpResponse(['login'], $query), 0);
				}
				
				$login_state
					->clearAuthState()
					->setWorker($authenticated_worker)
					->setEmail($authenticated_worker->getEmailString())
					->setIsSSOAuthenticated(true)
					->setIsMfaRequired(false)
					;
				
				DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','authenticated']), 0);
				break;
			
			case 'metadata':
				try {
					$settings = new \OneLogin\Saml2\Settings($settings_info, true);
					$metadata = $settings->getSPMetadata();
					$errors = $settings->validateMetadata($metadata);
					
					if(!$errors) {
						header('Content-Type: text/xml');
						echo $metadata;
						exit;
						
					} else {
						throw new \OneLogin\Saml2\Error(
							'Invalid SP metadata: '.implode(', ', $errors),
							\OneLogin\Saml2\Error::METADATA_SP_INVALID
						);
						
					}
				} catch(Exception $e) {
					error_log($e->getMessage());
				}
				break;
		}
	}
	
	public function authenticateHttpRequest(Model_ConnectedAccount $account, Psr\Http\Message\RequestInterface &$request, array &$options = []): bool {
		return true;
	}
};