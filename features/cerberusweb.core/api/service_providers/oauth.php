<?php
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;

interface IServiceProvider_OAuth {
	function oauthRender();
	function oauthCallback();
}

class ServiceProvider_OAuth1 extends Extension_ConnectedServiceProvider implements IServiceProvider_OAuth {
	const ID = 'cerb.service.provider.oauth1';
	
	function handleActionForService(string $action) {
		return false;
	}
	
	function renderConfigForm(Model_ConnectedService $service) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl->assign('service', $service);
		
		$params = $service->decryptParams($active_worker);
		$tpl->assign('params', $params);
		
		$tpl->display('devblocks:cerberusweb.core::internal/connected_service/providers/oauth1/config_service.tpl');
	}
	
	function saveConfigForm(Model_ConnectedService $service, array &$params, &$error=null) {
		@$edit_params = DevblocksPlatform::importGPC($_POST['params'], 'array', []);
		
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField('client_id', 'Client ID')
			->string()
			->setRequired(true)
			;
		$validation
			->addField('client_secret', 'Client Secret')
			->string()
			->setRequired(true)
			;
		$validation
			->addField('request_token_url', 'Request Token URL')
			->url()
			->setRequired(true)
			;
		$validation
			->addField('authentication_url', 'Authentication URL')
			->url()
			->setRequired(true)
			;
		$validation
			->addField('access_token_url', 'Access Token URL')
			->url()
			->setRequired(true)
			;
		$validation
			->addField('signature_method', 'Signature Method')
			->string()
			->setPossibleValues(['HMAC-SHA1','PLAINTEXT'])
			->setRequired(true)
			;
		
		if(false === $validation->validateAll($edit_params, $error))
			return false;
		
		$params = $edit_params;
		
		return true;
	}
	
	public function renderAccountConfigForm(Model_ConnectedService $service, Model_ConnectedAccount $account) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl->assign('service', $service);
		$tpl->assign('account', $account);

		$params = $account->decryptParams($active_worker);
		$tpl->assign('params', $params);
		
		$tpl->display('devblocks:cerberusweb.core::internal/connected_service/providers/oauth1/config_account.tpl');
	}

	public function saveAccountConfigForm(Model_ConnectedService $service, Model_ConnectedAccount $account, array &$params, &$error=null) {
		@$edit_params = DevblocksPlatform::importGPC($_POST['params'], 'array', []);
		
		$encrypt = DevblocksPlatform::services()->encryption();
		
		// Decrypt OAuth params
		if(isset($edit_params['params_json'])) {
			if(false == ($outh_params_json = $encrypt->decrypt($edit_params['params_json']))) {
				$error = "The connected account authentication is invalid.";
				return false;
			}
				
			if(false == ($oauth_params = json_decode($outh_params_json, true))) {
				$error = "The connected account authentication is malformed.";
				return false;
			}
			
			if(is_array($oauth_params))
			foreach($oauth_params as $k => $v)
				$params[$k] = $v;
		}
		
		return true;
	}
	
	function oauthRender() {
		@$form_id = DevblocksPlatform::importGPC($_REQUEST['form_id'], 'string', '');
		@$service_id = DevblocksPlatform::importGPC($_REQUEST['service_id'], 'integer', 0);
		
		if(false == ($service = DAO_ConnectedService::get($service_id)))
			DevblocksPlatform::dieWithHttpError();
		
		// Store the $form_id in the session
		$_SESSION['oauth_form_id'] = $form_id;
		$_SESSION['oauth_service_id'] = $service_id;
		
		$url_writer = DevblocksPlatform::services()->url();
		
		if(false == ($service_params = $service->decryptParams())) {
			echo DevblocksPlatform::strEscapeHtml(sprintf("ERROR: The consumer key and secret aren't configured for %s.", $service->name));
			return false;
		}
		
		$oauth = DevblocksPlatform::services()->oauth()->getOAuth1Client($service_params['client_id'], $service_params['client_secret'], $service_params['signature_method']);
		
		// OAuth callback
		$redirect_url = $url_writer->write(sprintf('c=oauth&a=callback&ext=%s', ServiceProvider_OAuth1::ID), true);
		
		$tokens = $oauth->getRequestTokens($service_params['request_token_url'], $redirect_url);
		
		if(!isset($tokens['oauth_token'])) {
			echo DevblocksPlatform::strEscapeHtml(sprintf("ERROR: %s didn't return an access token. Verify the callback URL: %s", $service->name, $redirect_url));
			return false;
		}
		
		$url = $oauth->getAuthenticationURL($service_params['authentication_url'], $tokens['oauth_token']);
		
		header('Location: ' . $url);
		exit;
	}
	
	function oauthCallback() {
		$form_id = $_SESSION['oauth_form_id'];
		$service_id = $_SESSION['oauth_service_id'];
		
		unset($_SESSION['oauth_form_id']);
		unset($_SESSION['oauth_service_id']);
		
		$encrypt = DevblocksPlatform::services()->encryption();
		
		if(false == ($service = DAO_ConnectedService::get($service_id)))
			DevblocksPlatform::dieWithHttpError('Invalid service', 403);
		
		if(false == ($service_params = $service->decryptParams()))
			DevblocksPlatform::dieWithHttpError('Invalid service parameters', 403);
		
		$oauth_token = $_REQUEST['oauth_token'];
		$oauth_verifier = $_REQUEST['oauth_verifier'];
		
		$oauth = DevblocksPlatform::services()->oauth()->getOAuth1Client($service_params['client_id'], $service_params['client_secret'], $service_params['signature_method']);
		$oauth->setTokens($oauth_token);
		
		$params = $oauth->getAccessToken($service_params['access_token_url'], array('oauth_verifier' => $oauth_verifier));
		
		// Output
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('form_id', $form_id);
		$tpl->assign('label', $service->name);
		$tpl->assign('params_json', $encrypt->encrypt(json_encode($params)));
		$tpl->display('devblocks:cerberusweb.core::internal/connected_account/oauth_callback.tpl');
	}
	
	function authenticateHttpRequest(Model_ConnectedAccount $account, Psr\Http\Message\RequestInterface &$request, array &$options = []) : bool {
		$account_params = $account->decryptParams();
		
		if(false == ($service = $account->getService()))
			return false;
		
		if(false == ($service_params = $service->decryptParams()))
			return false;
		
		$oauth = DevblocksPlatform::services()->oauth()->getOAuth1Client($service_params['client_id'], $service_params['client_secret'], $service_params['signature_method']);
		$oauth->setTokens($account_params['oauth_token'], $account_params['oauth_token_secret']);
		
		$oauth->authenticateHttpRequest($request, $options);
		
		return true;
	}
}

class ServiceProvider_OAuth2 extends Extension_ConnectedServiceProvider implements IServiceProvider_OAuth {
	const ID = 'cerb.service.provider.oauth2';
	
	function handleActionForService(string $action) {
		return false;
	}
	
	private function _getProvider(Model_ConnectedService $service) {
		$url_writer = DevblocksPlatform::services()->url();
		$service_params = $service->decryptParams();
		
		$settings = [
			'clientId' => $service_params['client_id'],
			'clientSecret' => $service_params['client_secret'],
			'redirectUri' => $url_writer->write('c=oauth&a=callback', true),
			'urlAuthorize' => $service_params['authorization_url'],
			'urlAccessToken' => $service_params['access_token_url'],
			'urlResourceOwnerDetails' => @$service_params['resource_owner_url'],
			'approvalPrompt' => $service_params['approval_prompt'],
			'scopes' => $service_params['scope'],
		];
		
		if(defined('DEVBLOCKS_HTTP_PROXY') && DEVBLOCKS_HTTP_PROXY)
			$settings['proxy'] = DEVBLOCKS_HTTP_PROXY;
		
		try {
			$provider = new Cerb_OAuth2Provider($settings);
		} catch (InvalidArgumentException $e) {
			error_log($e->getMessage());
		} catch (Exception $e) {
			error_log($e->getMessage());
		}
		
		return $provider;
	}
	
	function renderConfigForm(Model_ConnectedService $service) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl->assign('service', $service);
		
		$params = $service->decryptParams($active_worker);
		$tpl->assign('params', $params);
		
		$tpl->display('devblocks:cerberusweb.core::internal/connected_service/providers/oauth2/config_service.tpl');
	}
	
	function saveConfigForm(Model_ConnectedService $service, array &$params, &$error=null) {
		@$edit_params = DevblocksPlatform::importGPC($_POST['params'], 'array', []);
		
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField('grant_type', 'Grant Type')
			->string()
			->setPossibleValues(['authorization_code'])
			->setRequired(true)
			;
		$validation
			->addField('client_id', 'Client ID')
			->string()
			->setRequired(true)
			;
		$validation
			->addField('client_secret', 'Client Secret')
			->string()
			->setRequired(true)
			;
		$validation
			->addField('authorization_url', 'Authorization URL')
			->url()
			->setRequired(true)
			;
		$validation
			->addField('access_token_url', 'Access Token URL')
			->url()
			->setRequired(true)
			;
		$validation
			->addField('resource_owner_url', 'Resource Owner URL')
			->url()
			;
		$validation
			->addField('scope', 'Scope')
			->string()
			->setNotEmpty(false)
			;
		$validation
			->addField('approval_prompt', 'Approval Prompt')
			->string()
			->setNotEmpty(false)
			;
		
		if(false === $validation->validateAll($edit_params, $error))
			return false;
		
		$params = $edit_params;
		
		return true;
	}
	
	public function renderAccountConfigForm(Model_ConnectedService $service, Model_ConnectedAccount $account) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl->assign('service', $service);
		$tpl->assign('account', $account);

		$params = $account->decryptParams($active_worker);
		$tpl->assign('params', $params);
		
		$tpl->display('devblocks:cerberusweb.core::internal/connected_service/providers/oauth2/config_account.tpl');
	}

	public function saveAccountConfigForm(Model_ConnectedService $service, Model_ConnectedAccount $account, array &$params, &$error=null) {
		@$edit_params = DevblocksPlatform::importGPC($_POST['params'], 'array', []);
		
		$encrypt = DevblocksPlatform::services()->encryption();
		
		// Decrypt OAuth params
		if(isset($edit_params['params_json'])) {
			if(false == ($outh_params_json = $encrypt->decrypt($edit_params['params_json']))) {
				$error = "The connected account authentication is invalid.";
				return false;
			}
				
			if(false == ($oauth_params = json_decode($outh_params_json, true))) {
				$error = "The connected account authentication is malformed.";
				return false;
			}
			
			if(is_array($oauth_params))
			foreach($oauth_params as $k => $v)
				$params[$k] = $v;
		}
		
		return true;
	}
	
	function oauthRender() {
		@$form_id = DevblocksPlatform::importGPC($_REQUEST['form_id'], 'string', '');
		@$service_id = DevblocksPlatform::importGPC($_REQUEST['service_id'], 'integer', 0);
		
		if(false == ($service = DAO_ConnectedService::get($service_id)))
			DevblocksPlatform::dieWithHttpError();
		
		$provider = $this->_getProvider($service);
		
		$options = [];
		
		$authorizationUrl = $provider->getAuthorizationUrl($options);
		
		$_SESSION['oauth_form_id'] = $form_id;
		$_SESSION['oauth_service_id'] = $service_id;
		$_SESSION['oauth2state'] = $provider->getState();
		
		header('Location: ' . $authorizationUrl);
		exit;
	}
	
	function oauthCallback() {
		@$form_id = $_SESSION['oauth_form_id'];
		@$service_id = $_SESSION['oauth_service_id'];
		@$oauth_state = $_SESSION['oauth2state'];
		@$state = $_GET['state'];
		
		unset($_SESSION['oauth_form_id']);
		unset($_SESSION['oauth_service_id']);
		unset($_SESSION['oauth2state']);
		
		// CSRF check
		if($oauth_state != $state)
			DevblocksPlatform::dieWithHttpError('Invalid state', 403);
		
		$encrypt = DevblocksPlatform::services()->encryption();
		
		if(false == ($service = DAO_ConnectedService::get($service_id)))
			DevblocksPlatform::dieWithHttpError('Invalid service', 403);
		
		if(false == ($provider = $this->_getProvider($service)))
			DevblocksPlatform::dieWithHttpError('Failed to load provider details', 403);
		
		try {
			$access_token = $provider->getAccessToken('authorization_code', [
				'code' => $_GET['code'],
			]);
			
			$params = $access_token->jsonSerialize();
			
			$label = $service->name;
			
			// Output
			$tpl = DevblocksPlatform::services()->template();
			$tpl->assign('form_id', $form_id);
			$tpl->assign('label', $label);
			$tpl->assign('params_json', $encrypt->encrypt(json_encode($params)));
			$tpl->display('devblocks:cerberusweb.core::internal/connected_account/oauth_callback.tpl');
			
		} catch (IdentityProviderException $e) {
			error_log($e->getMessage());
			DevblocksPlatform::dieWithHttpError($e->getMessage(), 403);
			
		} catch (Exception $e) {
			error_log($e->getMessage());
			DevblocksPlatform::dieWithHttpError($e->getMessage(), 403);
		}
	}
	
	/**
	 * @param Model_ConnectedAccount $account
	 * @return AccessToken|false
	 */
	function getAccessToken(Model_ConnectedAccount $account) {
		if(false == ($params = $account->decryptParams()))
			return false;
		
		$access_token = new AccessToken($params);
		
		// If expired, try the refresh token
		if($access_token->getExpires() && $access_token->hasExpired()) {
			if(false == ($access_token = $this->_refreshToken($access_token, $params, $account)))
				return false;
		}
		
		return $access_token;
	}
	
	private function _refreshToken(AccessToken $access_token, array $params, Model_ConnectedAccount $account) {
		try {
			if(false == ($service = $account->getService()))
				return false;
			
			if(false == ($provider = $this->_getProvider($service)))
				return false;
			
			if(false == ($refresh_token = $access_token->getRefreshToken()))
				return false;
			
			$access_token = $provider->getAccessToken('refresh_token', [
				'refresh_token' => $refresh_token
			]);
			
		} catch(Exception $e) {
			error_log($e->getMessage());
			return false;
		}
		
		// Merge new params
		$new_params = $access_token->jsonSerialize();
		$params = array_merge($params, $new_params);
		
		DAO_ConnectedAccount::setAndEncryptParams($account->id, $params);
		
		return $access_token;
	}
	
	function authenticateHttpRequest(Model_ConnectedAccount $account, Psr\Http\Message\RequestInterface &$request, array &$options = []) : bool {
		if(false == ($access_token = $this->getAccessToken($account)))
			return false;
		
		if(false == ($service = $account->getService()))
			return false;
		
		if(false == ($provider = $this->_getProvider($service)))
			return false;
		
		$authed_request = $provider->getAuthenticatedRequest(
			$request->getMethod(),
			(string) $request->getUri(),
			$access_token
		);
		
		$request = $request
			->withHeader('Authorization', $authed_request->getHeaderLine('Authorization'))
			;
		
		return true;
	}
}