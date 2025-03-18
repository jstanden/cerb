<?php
class ServiceProvider_TelegramBot extends Extension_ConnectedServiceProvider {
	const ID = 'cerb.service.provider.telegram.bot';
	
	function handleActionForService(string $action) {
		return false;
	}
	
	function renderConfigForm(Model_ConnectedService $service) {
	}
	
	function saveConfigForm(Model_ConnectedService $service, array &$params, &$error=null) {
	}
	
	public function renderAccountConfigForm(Model_ConnectedService $service, Model_ConnectedAccount $account) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$params = $account->decryptParams($active_worker);
		$tpl->assign('params', $params);
		
		$tpl->display('devblocks:cerberusweb.core::internal/connected_service/providers/telegram_bot/config_account.tpl');
	}

	public function saveAccountConfigForm(Model_ConnectedService $service, Model_ConnectedAccount $account, array &$params, &$error = null) {
		$edit_params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', []);
		
		$validation = DevblocksPlatform::services()->validation();
	
		$validation
			->addField('token','Token')
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
	
	function authenticateHttpRequest(Model_ConnectedAccount $account, Psr\Http\Message\RequestInterface &$request, array &$options = []) : bool {
		$account_params = $account->decryptParams();
		
		$uri = $request->getUri();
		$path = $uri->getPath();
		
		// /bot/ -> /bot<token>
		$new_path  = str_replace('/bot/', '/bot' . ($account_params['token'] ?? '') . '/', $path);
		
		$request = $request->withUri($uri->withPath($new_path));
		
		return true;
	}
}