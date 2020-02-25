<?php
class ServiceProvider_HttpBasic extends Extension_ConnectedServiceProvider {
	const ID = 'cerb.service.provider.http.basic';
	
	public function handleActionForService(string $action) {
		return false;
	}
	
	function renderConfigForm(Model_ConnectedService $service) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$params = $service->decryptParams($active_worker);
		$tpl->assign('params', $params);
		
		$tpl->display('devblocks:cerberusweb.core::internal/connected_service/providers/http_basic/config_service.tpl');
	}
	
	function saveConfigForm(Model_ConnectedService $service, array &$params, &$error=null) {
		@$edit_params = DevblocksPlatform::importGPC($_POST['params'], 'array', []);
		
		$validation = DevblocksPlatform::services()->validation();
	
		$validation
			->addField('base_url','Base URL')
			->url()
			->setRequired(true)
			;
		
		// [TODO] Each line should validate as a URL prefix
		$validation
			->addField('url_whitelist','URL whitelist')
			->string()
			->setMaxLength(65536)
			->setNotEmpty(false)
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
		
		$tpl->display('devblocks:cerberusweb.core::internal/connected_service/providers/http_basic/config_account.tpl');
	}

	public function saveAccountConfigForm(Model_ConnectedService $service, Model_ConnectedAccount $account, array &$params, &$error = null) {
		@$edit_params = DevblocksPlatform::importGPC($_POST['params'], 'array', []);
		
		$validation = DevblocksPlatform::services()->validation();
	
		$validation
			->addField('username','Username')
			->string()
			->setRequired(true)
			;
		$validation
			->addField('password','Password')
			->string()
			->setNotEmpty(false)
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
		
		// Validate the service base URL hostname against the request's hostname
		
		if(!array_key_exists('base_url', $service_params))
			return false;
		
		$request_host = $request->getUri()->getHost();
		
		// [TODO] Require SSL?
		
		// Check whitelisted URL prefixes
		
		$whitelisted_urls = DevblocksPlatform::parseCrlfString(@$service_params['url_whitelist']);
		array_unshift($whitelisted_urls, $service_params['base_url']);
		
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
		
		// [TODO] Return errors
		
		// Auth
		
		$credentials = $account->decryptParams();
		
		if(!isset($credentials['username']) || !isset($credentials['password']))
			return false;
		
		$options['auth'] = [
			$credentials['username'],
			$credentials['password'],
			'basic'
		];
		
		return true;
	}
}