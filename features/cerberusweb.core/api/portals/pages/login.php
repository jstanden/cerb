<?php
class PortalPage_Login extends Extension_PortalPage {
	const ID = 'cerb.portal.page.login';
	
	public function renderConfig(Model_PortalPage $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('model', $model);
		$tpl->display('devblocks:cerberusweb.core::portals/builder/pages/login/config.tpl');
	}
	
	public function saveConfig(array $fields, $id, &$error=null) {
		if(!array_key_exists(DAO_PortalPage::PARAMS_JSON, $fields)) {
			$error = 'Portal page parameters are required.';
			return false;
		}
		
		if(false === ($params = json_decode($fields[DAO_PortalPage::PARAMS_JSON], true))) {
			$error = 'Unable to read portal parameters.';
			return false;
		}
		
		/*
		if(false == ($tab_ids = @$params['tab_ids'])) {
			$error = 'A form builder behavior is required.';
			return false;
		}
		*/
		
		return true;
	}
	
	function invoke(Model_PortalPage $page, Model_CommunityTool $portal, DevblocksHttpResponse $response) {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		//@$invoke = DevblocksPlatform::importGPC($_POST['invoke'], 'string', null);
		
		return false;
	}
	
	function render(Model_PortalPage $page, Model_CommunityTool $portal, DevblocksHttpResponse $response) {
		// [TODO] Redirect to an OpenID Connect provider with a callback URL
		
		$renderer = new Extension_PortalPageRenderer(function() use ($page, $portal, $response) {
			$stack = $response->path;
			
			@$uri = array_shift($stack);
			
			switch($uri) {
				case 'logout':
					$this->_routeLogout($page, $portal, $stack);
					break;

				default:
				case 'sso':
					$this->_routeLoginSSO($page, $portal, $stack);
					break;
			}
			
		});
		
		parent::renderBareLayout($page, $portal, $renderer);
	}
	
	private function _getProvider(Model_CommunityTool $portal) {
		//if(false == ($service_params = $service->decryptParams()))
		//	return null;
		
		$url_writer = DevblocksPlatform::services()->url();
		
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
		
		$provider = new GenericOpenIDConnectProvider([
			'clientId' => 'mag87br1pffawzvpmep5u7stjb4qufs1',
			'clientSecret' => 'ufafvcqgz5anct3l6rmcxwtfkx5cq5y2x8rtktzujblfp5s5lz1yzxehn37lzmv5',
			// [TODO]
			'idTokenIssuer' => 'http://localhost/index.php/portal/idp/',
			'redirectUri' => $url_writer->write(sprintf('c=login&a=sso&provider=%s', $service_uri), true),
			'urlAuthorize' => 'http://localhost:9090/index.php/portal/idp/services/oauth2/authorize',
			'urlAccessToken' => 'http://localhost:9090/index.php/portal/idp/services/oauth2/token',
			'urlResourceOwnerDetails' => 'http://localhost:9090/index.php/portal/idp/services/oauth2/userinfo',
			'urlJwks' => 'http://localhost:9090/index.php/portal/idp/services/id/keys',
		]);
		
		return $provider;
	}
	
	private function _routeLogout(Model_PortalPage $page, Model_CommunityTool $portal, array $path = []) {
		$session = ChPortalHelper::getSession();
		$session->destroy();
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(), 0);
	}
	
	private function _routeLoginSSO(Model_PortalPage $page, Model_CommunityTool $portal, array $path = []) {
		// [TODO] Look up connected service from config
		
		// [TODO]
		//if(false == ($pool_id = $portal->getParam('identity_pool_id', 0)))
		//	return;
		
		$pool_id = 2;
		
		$session = ChPortalHelper::getSession();
		
		// [TODO] Log out
		$session->setProperty('identity', null);
		
		$provider = $this->_getProvider($portal);
		
		if(!array_key_exists('code', $_GET)) {
			// Send to the authentication URL
			$redirectUrl = $provider->getAuthorizationUrl();
			header(sprintf("Location: %s", $redirectUrl, true, 302));
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
		}
		
		$id_token = $token->getIdToken();
		
		// [TODO] Verify ID token
		
		if(
			false == ($email = $id_token->getClaim('email'))
			|| false == ($identity = DAO_Identity::getByEmailAndPool($email, $pool_id))
		) {
			$query = ['error' => 'auth.failed'];
			DevblocksPlatform::redirect(new DevblocksHttpResponse(['login'], $query), 0);
		}
		
		$session->setIdentity($identity);
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(), 0);
	}
}