<?php
class ServiceProvider_ApiKey extends Extension_ConnectedServiceProvider {
	const ID = 'cerb.service.provider.api_key';
	
	public function handleActionForService(string $action) {
		return false;
	}
	
	function renderConfigForm(Model_ConnectedService $service) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$params = $service->decryptParams($active_worker);
		$tpl->assign('params', $params);
		
		$tpl->display('devblocks:cerberusweb.core::internal/connected_service/providers/api_key/config_service.tpl');
	}
	
	function saveConfigForm(Model_ConnectedService $service, array &$params, &$error=null) {
		$edit_params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', []);
		
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField('api_base_url', 'API Base URL')
			->string()
			->setRequired(true)
			;
	
		$validation
			->addField('api_key_name', 'API Key Name')
			->string()
			->setRequired(true)
			;
	
		$validation
			->addField('api_key_location','API Key Location')
			->string()
			->setPossibleValues([
				'url',
				'header',
			])
			->setRequired(true)
			;
		
		if(false == $validation->validateAll($edit_params, $error))
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
		
		$tpl->display('devblocks:cerberusweb.core::internal/connected_service/providers/api_key/config_account.tpl');
	}

	public function saveAccountConfigForm(Model_ConnectedService $service, Model_ConnectedAccount $account, array &$params, &$error = null) {
		$edit_params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', []);
		
		$validation = DevblocksPlatform::services()->validation();
	
		$validation
			->addField('api_key','API Key')
			->string()
			->setRequired(true)
			;
		
		if(false == $validation->validateAll($edit_params, $error))
			return false;
		
		foreach($edit_params as $k => $v)
			$params[$k] = $v;
		
		return true;
	}
	
	function authenticateHttpRequest(Model_ConnectedAccount $account, Psr\Http\Message\RequestInterface &$request, array &$options = []) : bool {
		if(false == ($service = $account->getService()))
			return false;
		
		$service_params = $service->decryptParams();
		
		$api_base_url = $service_params['api_base_url'] ?? null;
		$api_key_location = $service_params['api_key_location'] ?? null;
		$api_key_name = $service_params['api_key_name'] ?? null;
		
		if(!$api_base_url || !$api_key_name)
			return false;
		
		// Validate the service base URL hostname against the request's hostname
		
		$request_host = $request->getUri()->getHost();
		
		// Check whitelisted URL prefixes
		
		$whitelisted_urls = [$api_base_url];
		
		$is_allowed = false;
		
		foreach($whitelisted_urls as $whitelisted_url) {
			$service_host = parse_url($whitelisted_url, PHP_URL_HOST);
			
			if(0 == strcasecmp($service_host, $request_host)) {
				$is_allowed = true;
				break;
			}
		}
		
		if(!$is_allowed) {
			return false;
		}
		
		// Auth
		
		$credentials = $account->decryptParams();
		
		$api_key = $credentials['api_key'] ?? null;
		
		if(!$api_key)
			return false;
		
		if('header' == $api_key_location) {
			$request = $request->withAddedHeader($api_key_name, $api_key);
			
		} else {
			$uri = $request->getUri();
			$query = $uri->getQuery();
			
			if($query)
				$query .= '&';
			
			$query .= rawurlencode($api_key_name) . '=' . rawurlencode($api_key);
			
			$request = $request->withUri($uri->withQuery($query));
		}
		
		return true;
	}
}