<?php
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

class ServiceProvider_FacebookPages extends Extension_ConnectedServiceProvider {
	const ID = 'wgm.facebook.pages.service.provider';
	
	function renderConfigForm(Model_ConnectedService $service) {
	}
	
	function saveConfigForm(Model_ConnectedService $service, array &$params, &$error=null) {
	}
	
	function handleActionForService(string $action) {
		switch($action) {
			case 'getPagesFromAccount':
				return $this->_connectedServiceAction_getPagesFromAccount();
		}
		
		return false;
	}
	
	public function renderAccountConfigForm(Model_ConnectedService $service, Model_ConnectedAccount $account) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl->assign('service', $service);
		
		$params = $account->decryptParams($active_worker) ?: [];
		$tpl->assign('params', $params);
		
		if(is_array($params) && array_key_exists('connected_account_id', $params)) {
			if(false != ($connected_account = DAO_ConnectedAccount::get($params['connected_account_id']))) {
				$tpl->assign('connected_account', $connected_account);
			}
		}
		
		$tpl->display('devblocks:cerberusweb.core::internal/connected_service/providers/facebook_pages/config_account.tpl');
	}

	public function saveAccountConfigForm(Model_ConnectedService $service, Model_ConnectedAccount $account, array &$params, &$error = null) {
		@$edit_params = DevblocksPlatform::importGPC($_POST['params'], 'array', []);
		
		$validation = DevblocksPlatform::services()->validation();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$validation
			->addField('connected_account_id', 'Facebook Account')
			->id()
			->addValidator($validation->validators()->contextId(CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, false))
			->setRequired(true)
		;
		
		$validation
			->addField('page_id', 'Page')
			->string()
		;
		
		if(!$validation->validateAll($edit_params, $error))
			return false;
		
		if(false == ($connected_account = DAO_ConnectedAccount::get($edit_params['connected_account_id']))) {
			$error = "Invalid Facebook account.";
			return false;
		}
		
		// Check permissions on the connected account
		if(!Context_ConnectedAccount::isUsableByActor($connected_account, $active_worker)) {
			$error = "You do not have permission to use this Facebook account.";
			return false;
		}
		
		$params['connected_account_id'] = $edit_params['connected_account_id'];
		
		// If the page ID is set, pull the token info from Facebook API
		if(array_key_exists('page_id', $edit_params)) {
			$page_id = $edit_params['page_id'];
			unset($params['page']);
			
			$pages = $this->_getPagesFromAccount($connected_account);
			
			if(!is_array($pages) || !array_key_exists($page_id, $pages)) {
				$error = 'Invalid Facebook page.';
				return false;
			}
			
			$params['page'] = $pages[$page_id];
		}
		
		return true;
	}
	
	private function _getPagesFromAccount(Model_ConnectedAccount $connected_account) {
		if(false == ($service = $connected_account->getService()))
			return;
		
		if(false == ($service_extension = $service->getExtension()))
			return;
		
		$request = new Request('GET', 'https://graph.facebook.com/me/accounts');
		$request_options = [];
		$error = null;
		
		if(false === ($service_extension->authenticateHttpRequest($connected_account, $request, $request_options)))
			return;
		
		if(false === ($response = DevblocksPlatform::services()->http()->sendRequest($request, $request_options, $error))) /* @var $response ResponseInterface */
			return;
			
		if(200 != $response->getStatusCode())
			return;
		
		if(false == ($json = json_decode($response->getBody()->getContents(), true)))
			return;
		
		if(!array_key_exists('data', $json))
			return;
		
		// [TODO] Paging results
		
		$pages = [];
		
		foreach($json['data'] as $page) {
			$pages[$page['id']] = $page;
		}
		
		DevblocksPlatform::sortObjects($pages, 'name');
		
		return $pages;
	}
	
	private function _connectedServiceAction_getPagesFromAccount() {
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$connected_account_id = DevblocksPlatform::importGPC($_POST['connected_account_id'], 'int', 0);
		
		$connected_account = null;
		
		if(!$connected_account_id || false == ($connected_account = DAO_ConnectedAccount::get($connected_account_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		if(!Context_ConnectedAccount::isWriteableByActor($connected_account, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		$pages = $this->_getPagesFromAccount($connected_account);
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('pages', $pages);
		
		$tpl->display('devblocks:cerberusweb.core::internal/connected_service/providers/facebook_pages/select_page.tpl');
	}
	
	function authenticateHttpRequest(Model_ConnectedAccount $account, Psr\Http\Message\RequestInterface &$request, array &$options=[]) : bool {
		$account_params = $account->decryptParams();
		
		@$access_token = $account_params['page']['access_token'];
		
		if(!$access_token)
			return false;
		
		$request = $request->withHeader('Authorization', sprintf("Bearer %s",
			$access_token
		));
		
		return true;
	}
}