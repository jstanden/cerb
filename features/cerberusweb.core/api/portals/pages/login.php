<?php
class PortalPage_Login extends Extension_PortalPage {
	const ID = 'cerb.portal.page.login';
	
	function render(array $page_meta, array $route_meta, Model_CommunityTool $portal) {
		// [TODO] Redirect to an OpenID Connect provider with a callback URL
		
		$renderer = new Extension_PortalPageRenderer(function() use ($page_meta, $route_meta, $portal) {
			$stack = [];
			
			$request_headers = DevblocksPlatform::getHttpHeaders() ?: [];
			unset($request_headers['cookie']);
			
			$request = DevblocksPlatform::readRequest();
			$request_path = $request->path;
			array_shift($request_path); // portal
			array_shift($request_path); // uri
			
			/*
			$initial_state = [
				//'identity__context' => CerberusContexts::CONTEXT_IDENTITY,
				//'identity_id' => $identity->id ?? 0,
				
				'portal__context' => CerberusContexts::CONTEXT_PORTAL,
				'portal_id' => $portal->id ?? 0,
				
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
			*/
			
			$stack = $request_path;
			array_shift($stack); // login
			
			@$uri = current($stack);
			
			switch($uri) {
				case 'logout':
					$this->_routeLogout($page_meta, $portal);
					break;

				default:
				case 'sso':
					$this->_routeLoginSSO($page_meta, $portal);
					break;
			}
			
		});
		
		parent::renderBareLayout($page_meta, $portal, $renderer);
	}
	
	private function _getProvider(Model_CommunityTool $portal) {
		//if(false == ($service_params = $service->decryptParams()))
		//	return null;
		
		$url_writer = DevblocksPlatform::services()->url();
		
		// [TODO] Allow multiple OIDC providers
		
		// [TODO]
		$service_uri = 'cerb';
		
		/*
		$provider = new GenericOpenIDConnectProvider([
			'clientId' => $service_params['client_id'],
			'clientSecret' => $service_params['client_secret'],
			'idTokenIssuer' => $service_params['issuer'],
			'redirectUri' => $url_writer->write(sprintf('c=login&a=sso&provider=%s', $service->uri), true),
			'urlAuthorize' => $service_params['authorization_url'],
			'urlAccessToken' => $service_params['access_token_url'],
			'urlResourceOwnerDetails' => $service_params['userinfo_url'],
			'urlJwks' => $service_params['jwks_url'],
		]);
		*/
		
		/*
		// [TODO] Implement
		$provider = new GenericOpenIDConnectProvider([
			// [TODO] Support Center
			'clientId' => '4u6jgcrycgdzdde9n4457z79tvuvqe3m',
			'clientSecret' => '1w3m2kyehpwv3hbj38gclj4ff3mz5bd717yywusml2slgmdpfrsar74lchqavs7g',
			'redirectUri' => $url_writer->write(sprintf('c=login&a=sso&provider=%s', $service_uri), true),
			// [TODO] Community
			//'clientId' => 'mag87br1pffawzvpmep5u7stjb4qufs1',
			//'clientSecret' => 'ufafvcqgz5anct3l6rmcxwtfkx5cq5y2x8rtktzujblfp5s5lz1yzxehn37lzmv5',
			// [TODO]
			'idTokenIssuer' => 'http://localhost:9090/index.php/portal/idp/',
			'urlAuthorize' => 'http://localhost:9090/index.php/portal/idp/services/oauth2/authorize',
			'urlAccessToken' => 'http://localhost:9090/index.php/portal/idp/services/oauth2/token',
			'urlResourceOwnerDetails' => 'http://localhost:9090/index.php/portal/idp/services/oauth2/userinfo',
			'urlJwks' => 'http://localhost:9090/index.php/portal/idp/services/id/keys',
			'scopes' => [
				'openid',
				'email',
				'profile',
			],
		]);
		*/
		
		// [TODO] Implement
		$provider = new GenericOpenIDConnectProvider([
			'clientId' => 'mag87br1pffawzvpmep5u7stjb4qufs1',
			'clientSecret' => 'ufafvcqgz5anct3l6rmcxwtfkx5cq5y2x8rtktzujblfp5s5lz1yzxehn37lzmv5',
			'redirectUri' => $url_writer->write(sprintf('c=login&a=sso&provider=%s', $service_uri), true),
			// [TODO] Dynamic
			'idTokenIssuer' => 'https://73a6-2600-1700-7250-4400-3055-91a7-f65d-3299.ngrok.io/portal/idp/',
			'urlAuthorize' => 'https://73a6-2600-1700-7250-4400-3055-91a7-f65d-3299.ngrok.io/portal/idp/services/oauth2/authorize',
			'urlAccessToken' => 'https://73a6-2600-1700-7250-4400-3055-91a7-f65d-3299.ngrok.io/portal/idp/services/oauth2/token',
			'urlResourceOwnerDetails' => 'https://73a6-2600-1700-7250-4400-3055-91a7-f65d-3299.ngrok.io/portal/idp/services/oauth2/userinfo',
			'urlJwks' => 'https://73a6-2600-1700-7250-4400-3055-91a7-f65d-3299.ngrok.io/portal/idp/services/id/keys',
			'scopes' => [
				'openid',
				'email',
				'profile',
			],
		]);
		
		return $provider;
	}
	
	private function _routeLogout(array $page_meta, Model_CommunityTool $portal) {
		$session = ChPortalHelper::getSession();
		$session->destroy();
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(), 0);
	}
	
	private function _routeLoginSSO(array $page_meta, Model_CommunityTool $portal) {
		// [TODO] Look up connected service from config
		
		// [TODO]
//		if(false == ($pool_id = $portal->getParam('identity_pool_id', 0)))
//			return;
		
		// [TODO]
		$pool_id = 1;
		
		$session = ChPortalHelper::getSession();
		
		// [TODO] Log out
		$session->setProperty('identity', null);
		
		$provider = $this->_getProvider($portal);
		
		if(array_key_exists('error', $_GET)) {
			// [TODO] Error messages
			DevblocksPlatform::dieWithHttpError('Login error');
			return;
		}
		
		if(!array_key_exists('code', $_GET)) {
			// Send to the authentication URL
			$redirectUrl = $provider->getAuthorizationUrl();
			header(sprintf("Location: %s", $redirectUrl), true, 302);
			return;
		}
		
		try {
			$token = $provider->getAccessToken('authorization_code', [
				'code' => $_GET['code']
			]);
			
		} catch (InvalidTokenException $e) {
			error_log($e->getMessage());
			$query = ['error' => 'auth.failed'];
			DevblocksPlatform::redirect(new DevblocksHttpResponse(['login'], $query), 0);
			
		} catch (Throwable $e) {
			error_log($e->getMessage());
			$query = ['error' => 'auth.failed'];
			DevblocksPlatform::redirect(new DevblocksHttpResponse(['login'], $query), 0);
		}
		
		$id_token = $token->getIdToken();
		
		//error_log(yaml_emit($id_token->claims()->all()));
		
		// [TODO] Verify ID token
		
		// [TODO] Match identities on other claim properties? (e.g. username)
		// [TODO] Create accounts for new identities?
		// [TODO] Update accounts for matches
		// [TODO] Multiple IdPs route to the same identity?
		// [TODO] This is a job for automations
		
		if(
			false == ($email = $id_token->claims()->get('email'))
			|| false == ($identity = DAO_Identity::getByEmailAndPool($email, $pool_id))
		) {
			// [TODO] Give a better error
			$query = ['error' => 'auth.failed'];
			DevblocksPlatform::redirect(new DevblocksHttpResponse(['login'], $query), 0);
		}
		
		var_dump($identity);
		
		$session->setIdentity($identity);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(), 0);
	}
}