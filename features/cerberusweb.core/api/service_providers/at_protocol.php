<?php

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Utils;

class ServiceProvider_AtProtocol extends Extension_ConnectedServiceProvider {
	const ID = 'cerb.service.provider.atproto';
	
	function handleActionForService(string $action) {
		return false;
	}
	
	function renderConfigForm(Model_ConnectedService $service) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$params = $service->decryptParams($active_worker);
		$tpl->assign('params', $params);
		
		$tpl->display('devblocks:cerberusweb.core::internal/connected_service/providers/at_protocol/config_service.tpl');
	}
	
	function saveConfigForm(Model_ConnectedService $service, array &$params, &$error=null) {
		$edit_params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', []);
		
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField('pds_base_url','PDS Entryway URL')
			->url()
			->setRequired(true)
		;
		
		if(!$validation->validateAll($edit_params, $error))
			return false;
		
		foreach($edit_params as $k => $v)
			$params[$k] = $v;
		
		return true;
	}
	
	public function renderAccountConfigForm(Model_ConnectedService $service, Model_ConnectedAccount $account) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$params = $account->decryptParams($active_worker);
		$tpl->assign('params', $params);
		
		$tpl->display('devblocks:cerberusweb.core::internal/connected_service/providers/at_protocol/config_account.tpl');
	}

	public function saveAccountConfigForm(Model_ConnectedService $service, Model_ConnectedAccount $account, array &$params, &$error = null) {
		$edit_params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', []);
		
		$validation = DevblocksPlatform::services()->validation();
	
		$validation
			->addField('identifier','Identifier')
			->string()
			->setMaxLength(255)
			->setRequired(true)
			;
		
		$validation
			->addField('password','Password')
			->string()
			->setMaxLength(255)
			->setRequired(true)
			;
		
		if(!$validation->validateAll($edit_params, $error))
			return false;
		
		foreach($edit_params as $k => $v)
			$params[$k] = $v;
		
		return true;
	}
	
	private function _createSession(Model_ConnectedAccount $account) : array|false {
		$http = DevblocksPlatform::services()->http();
		
		$account_params = $account->decryptParams();
		$service = $account->getService();
		$service_params = $service->decryptParams();
		$error = null;
		
		$base_url = rtrim($service_params['pds_base_url'], '/');
		
		$http_headers = Utils::headersFromLines([
			'Content-Type: application/json',
		]);
		
		$auth_request = new Request(
			'POST',
			$base_url . '/xrpc/com.atproto.server.createSession',
			$http_headers,
			json_encode([
				'identifier' => $account_params['identifier'],
				'password' => $account_params['password'],
			])
		);
		
		if(($response = $http->sendRequest($auth_request, [], $error))) {
			if(false === ($response_json = $http->getResponseAsJson($response, $error)))
				return false;
			
			if(!($response_json['accessJwt'] ?? null || $response_json['refreshJwt'] ?? null))
				return false;
			
			return $response_json;
			
		} else {
			DevblocksPlatform::logError('AT Protocol Auth Error: ' . $error);
			return false;
		}
	}
	
	private function _refreshSession(Model_ConnectedAccount $account, string $refreshJwt) : array|false {
		$http = DevblocksPlatform::services()->http();
		
		$service = $account->getService();
		$service_params = $service->decryptParams();
		$error = null;
		
		$base_url = rtrim($service_params['pds_base_url'], '/');
		
		$http_headers = Utils::headersFromLines([
			'Authorization: Bearer ' . $refreshJwt,
		]);
		
		$auth_request = new Request(
			'POST',
			$base_url . '/xrpc/com.atproto.server.refreshSession',
			$http_headers,
		);
		
		if(($response = $http->sendRequest($auth_request, [], $error))) {
			if(false === ($response_json = $http->getResponseAsJson($response, $error)))
				return false;
			
			if(!($response_json['accessJwt'] ?? null || $response_json['refreshJwt'] ?? null))
				return false;
			
			return $response_json;
			
		} else {
			DevblocksPlatform::logError('AT Protocol Auth Error: ' . $error);
			return false;
		}
	}
	
	private function _parseJWT($token) : ?array {
		list($header, $payload, $signature) = array_pad(explode('.', $token), 3, null);
		
		$header = json_decode(DevblocksPlatform::services()->string()->base64UrlDecode($header) ?? '', true);
		$payload = json_decode(DevblocksPlatform::services()->string()->base64UrlDecode($payload) ?? '', true);
		//$signature = DevblocksPlatform::services()->string()->base64UrlDecode($signature);
		
		// Is it expired?
		if(!($payload['exp'] ?? null) || $payload['exp'] < time())
			return null;
		
		return [
			'header' => $header,
			'payload' => $payload,
		];
	}
	
	function authenticateHttpRequest(Model_ConnectedAccount $account, Psr\Http\Message\RequestInterface &$request, array &$options = []) : bool {
		$account_params = $account->decryptParams();
		
		// Do we have an access token that isn't expired?
		if(($accessJwt = $account_params['session']['accessJwt'] ?? null))
			if(!($accessJwtParts = $this->_parseJWT($accessJwt)))
				$accessJwt = null;
		
		// Do we have a refresh token?
		if(!$accessJwt && ($refreshJwt = $account_params['session']['refreshJwt'] ?? null)) {
			if(($session_params = $this->_refreshSession($account, $refreshJwt))) {
				$accessJwt = $session_params['accessJwt'] ?? null;
				$account_params['session'] = $session_params;
				DAO_ConnectedAccount::setAndEncryptParams($account->id, $account_params);
			}
		}
		
		if(!$accessJwt) {
			if(false === ($session_params = $this->_createSession($account)))
				return false;
		
			$accessJwt = $session_params['accessJwt'] ?? null;
			$account_params['session'] = $session_params;
			DAO_ConnectedAccount::setAndEncryptParams($account->id, $account_params);
		}
		
		$request = $request
			->withHeader('Authorization', sprintf("Bearer %s",
				$accessJwt
			))
		;
		
		return true;
	}
}