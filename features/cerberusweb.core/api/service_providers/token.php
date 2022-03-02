<?php
class ServiceProvider_TokenBearer extends Extension_ConnectedServiceProvider {
	const ID = 'cerb.service.provider.token.bearer';
	
	function handleActionForService(string $action) {
		return false;
	}
	
	function renderConfigForm(Model_ConnectedService $service) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$params = $service->decryptParams($active_worker);
		$tpl->assign('params', $params);
		
		$tpl->display('devblocks:cerberusweb.core::internal/connected_service/providers/token_bearer/config_service.tpl');
	}
	
	function saveConfigForm(Model_ConnectedService $service, array &$params, &$error=null) {
		$edit_params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', []);
		
		$validation = DevblocksPlatform::services()->validation();
	
		$validation
			->addField('token_name','Token Name')
			->string()
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
		
		$tpl->display('devblocks:cerberusweb.core::internal/connected_service/providers/token_bearer/config_account.tpl');
	}

	public function saveAccountConfigForm(Model_ConnectedService $service, Model_ConnectedAccount $account, array &$params, &$error = null) {
		$edit_params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', []);
		
		$validation = DevblocksPlatform::services()->validation();
	
		$validation
			->addField('token','Token')
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
		$account_params = $account->decryptParams();
		
		$request = $request
			->withHeader('Authorization', sprintf("%s %s",
				@$service_params['token_name'] ?: 'Bearer',
				@$account_params['token'] ?: ''
			))
			;
		
		return true;
	}
}