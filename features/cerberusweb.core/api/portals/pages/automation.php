<?php
class PortalPage_Automation extends Extension_PortalPage {
	const ID = 'cerb.portal.page.automation';
	
	function render(array $page_meta, array $route_meta, Model_CommunityTool $portal) {
		$automator = DevblocksPlatform::services()->automation();
		$portal = ChPortalHelper::getPortal();
		$identity = ChPortalHelper::getIdentity();
		$user_agent = DevblocksPlatform::getClientUserAgent();
		
		$error = null;
		
		// [TODO] Custom error page
		if(false == ($automation = DAO_Automation::getByUri($page_meta['uri'], AutomationTrigger_PortalPage::ID)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$inputs = 
				array_key_exists('inputs', $page_meta) && is_array($page_meta['inputs'])
				? $page_meta['inputs']
				: []
			;
		
		$request_headers = DevblocksPlatform::getHttpHeaders() ?: [];
		unset($request_headers['cookie']);
		
		$request = DevblocksPlatform::readRequest();
		$request_path = $request->path;
		array_shift($request_path); // portal
		array_shift($request_path); // uri
		
		$initial_state = [
			'identity__context' => CerberusContexts::CONTEXT_IDENTITY,
			'identity_id' => $identity->id ?? 0,
			
			'portal__context' => CerberusContexts::CONTEXT_PORTAL,
			'portal_id' => $portal->id ?? 0,
			
			'inputs' => $inputs,
			
			'request_method' => DevblocksPlatform::strUpper($_SERVER['REQUEST_METHOD']),
			'request_body' => DevblocksPlatform::getHttpBody(),
			'request_client_ip' => DevblocksPlatform::getClientIp(),
			'request_client_browser_name' => $user_agent['browser'] ?? null,
			'request_client_browser_platform' => $user_agent['platform'] ?? null,
			'request_client_browser_version' => $user_agent['version'] ?? null,
			'request_headers' => $request_headers,
			'request_params' => DevblocksPlatform::getHttpParams(),
			'request_path' => implode('/', $request_path),
		];
		
		$automation_results = $automator->executeScript($automation, $initial_state, $error);
		
		if(!($automation_results instanceof DevblocksDictionaryDelegate))
			DevblocksPlatform::dieWithHttpError(null, 500);
		
		//var_dump($automation_results);
		
		$return = $automation_results->getKeyPath('__return', []);
		$page_type = null;
		$page_key = $page_meta['key'] ?? null;
		
		// [TODO] From manifests
		if(array_key_exists('dashboard', $return)) {
			$page_type = 'dashboard';
		} else if(array_key_exists('interaction', $return)) {
			$page_type = 'interaction';
		} else if(array_key_exists('text', $return)) {
			$page_type = 'text';
		} else {
			DevblocksPlatform::dieWithHttpError(null, 500);
		}
		
		if(null == ($page = Extension_PortalPage::getByType($page_type)))
			DevblocksPlatform::dieWithHttpError(null, 500);
		
		// [TODO] Page meta 'layout'
		
		$page_meta = $return[$page_type];
		$page_meta['key'] = $page_key;
		$page_meta['type'] = $page_type;
		
		$page->render($page_meta, $route_meta, $portal);
	}
}