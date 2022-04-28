<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/

use GuzzleHttp\Psr7\ServerRequest;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use OpenIDConnectServer\ClaimExtractor;
use Psr\Http\Message\ServerRequestInterface;

class CerbAuthCodeGrant extends AuthCodeGrant {
	public function getClientCredentials(ServerRequestInterface $request): array {
		try {
			return parent::getClientCredentials($request);
		} catch (Throwable) {
			return [null, null];
		}
	}
}

class Controller_OAuth extends DevblocksControllerExtension {
	private function _getOAuth(ServerRequest $http_request) {
		$encrypt = DevblocksPlatform::services()->encryption();
		
		$clientRepository = new Cerb_OAuth2ClientRespository();
		$scopeRepository = new Cerb_OAuth2ScopeRepository();
		$accessTokenRepository = new Cerb_OAuth2AccessTokenRepository();
		$authCodeRepository = new Cerb_OAuth2AuthCodeRepository();
		$refreshTokenRepository = new Cerb_OAuth2RefreshTokenRepository();
		
		$privateKey = DevblocksPlatform::services()->oauth()->getServerPrivateKey();
		
		$encryptionKey = $encrypt->getSystemKey();
		$encryptionKey = \Defuse\Crypto\Key::loadFromAsciiSafeString($encryptionKey);
		
		$responseType = new Cerb_OAuthIdTokenResponse(new Cerb_OAuth2IdentityRepository(), new ClaimExtractor());
		
		$server = new \League\OAuth2\Server\AuthorizationServer(
			$clientRepository,
			$accessTokenRepository,
			$scopeRepository,
			$privateKey,
			$encryptionKey,
			$responseType
		);
		
		$ttl_refresh_token = new \DateInterval('P1M');  // 1 month TTL for refresh token
		$ttl_access_token = new \DateInterval('PT1H'); // 1 hour TTL for access token
		$ttl_auth_code = new \DateInterval('PT10M'); // 10 mins
		
		$grant_authcode = new CerbAuthCodeGrant(
			$authCodeRepository,
			$refreshTokenRepository,
			$ttl_auth_code
		);
		
		[$client_id,] = $grant_authcode->getClientCredentials($http_request);
		
		// Per OAuth app token expirations
		if($client_id && ($oauth_app = DAO_OAuthApp::getByClientId($client_id))) {
			$ttl_access_token = \DateInterval::createFromDateString($oauth_app->access_token_ttl ?? '1 hour');
			$ttl_refresh_token = \DateInterval::createFromDateString($oauth_app->refresh_token_ttl ?? '1 month');
		}
		
		$grant_authcode->setRefreshTokenTTL($ttl_refresh_token);
		
		$server->enableGrantType(
			$grant_authcode,
			$ttl_access_token
		);
		
		$grant_refresh = new RefreshTokenGrant($refreshTokenRepository);
		$grant_refresh->setRefreshTokenTTL($ttl_refresh_token);
		
		$server->enableGrantType(
			$grant_refresh,
			$ttl_access_token
		);
		
		return $server;
	}
	
	/*
	 * Request Overload
	 */
	function handleRequest(DevblocksHttpRequest $request) {
		$translate = DevblocksPlatform::getTranslationService();
		$url_writer = DevblocksPlatform::services()->url();
		
		$stack = $request->path; // URLs like: /oauth/callback
		array_shift($stack); // oauth
		@$action = array_shift($stack); // callback
		
		switch($action) {
			case 'authorize':
				try {
					$http_request = ServerRequest::fromGlobals();
					$server = $this->_getOAuth($http_request);
					
					$http_response = new \GuzzleHttp\Psr7\Response();
					$auth_request = $server->validateAuthorizationRequest($http_request);
					
					if(!(DAO_OAuthApp::getByClientId($auth_request->getClient()->getIdentifier())))
						throw OAuthServerException::invalidClient($http_request);
					
					$login_state = CerbLoginWorkerAuthState::getInstance()
						->setIsConsentRequired([
								'client_id' => $auth_request->getClient()->getIdentifier(),
								'scopes' => $auth_request->getScopes(),
							])
							;
					
					if(
						!($auth_worker = $login_state->getWorker())
						|| !$login_state->isAuthenticated()
						|| !$login_state->wasConsentAsked()
					) {
						$uri = $http_request->getUri();
						
						// Fix HTTPS for proxies
						if($url_writer->isSSL())
							$uri = $uri->withScheme('https');
						
						// [TODO] When this happens we need to stow the current login state until the flow is done
						
						// If we don't have consent yet
						$login_state
							->clearAuthState()
							->pushRedirectUri($uri->__toString())
							;
						
						// If we have an active session, reuse the details
						if($active_worker = CerberusApplication::getActiveWorker()) {
							$login_state
								->setWorker($active_worker)
								->setEmail($active_worker->getEmailString())
								->setIsPasswordAuthenticated(true)
								->setIsMfaRequired(false)
								;
							
							DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','consent']));
							
						// Otherwise, start a new login
						} else {
							DevblocksPlatform::redirect(new DevblocksHttpRequest(['login']));
						}
					}
					
					// If we have a legit session
					$auth_request->setUser(new Cerb_OAuth2UserEntity($auth_worker));
					$auth_request->setAuthorizationApproved($login_state->isConsentGiven());
					
					// Destroy the login state
					$login_state->destroy();
					
					$http_response = $server->completeAuthorizationRequest($auth_request, $http_response);
					$header_location = $http_response->getHeader('Location')[0];
					
					DevblocksPlatform::redirectURL($header_location);
					
				} catch(OAuthServerException $e) {
					http_response_code($e->getHttpStatusCode());
					echo $e->getMessage(); 
					DevblocksPlatform::logException($e);
					return;
					
				} catch (Exception $e) {
					http_response_code(500);
					echo "An unexpected error occurred. Please try again later.";
					DevblocksPlatform::logException($e);
					return;
				}
				
			case 'access_token':
				try {
					$http_request = ServerRequest::fromGlobals();
					$server = $this->_getOAuth($http_request);
					
					$http_response = new \GuzzleHttp\Psr7\Response();
					
					$http_response = $server->respondToAccessTokenRequest($http_request, $http_response);
					
					http_response_code($http_response->getStatusCode());
					
					foreach($http_response->getHeaders() as $key => $value) {
						DevblocksPlatform::services()->http()->setHeader($key, implode(',', $value));
					}
					
					echo $http_response->getBody();
					exit;
					
				} catch(OAuthServerException $e) {
					http_response_code($e->getHttpStatusCode());
					echo $e->getMessage();
					DevblocksPlatform::logException($e);
					return;
					
				} catch(Exception $e) {
					http_response_code(500);
					echo "An unexpected error occurred. Please try again later.";
					DevblocksPlatform::logException($e);
					return;
				}
				
			case 'callback':
				@$ext_id = array_shift($stack);
				
				// A session must exist to use this controller
				if(null == (CerberusApplication::getActiveWorker()))
					DevblocksPlatform::dieWithHttpError($translate->_('common.access_denied'), 403);
				
				// Assume a blank extension means the generic OAuth provider
				if(!$ext_id)
					$ext_id = ServiceProvider_OAuth2::ID;
				
				// The given extension must be valid
				if(!($ext = Extension_ConnectedServiceProvider::get($ext_id)))
					DevblocksPlatform::dieWithHttpError($translate->_('common.access_denied'), 403);
				
				// The given extension must implement OAuth callbacks
				if(!($ext instanceof IServiceProvider_OAuth))
					DevblocksPlatform::dieWithHttpError($translate->_('common.access_denied'), 403);
				
				// Trigger the extension's oauth callback
				$ext->oauthCallback();
				break;
				
			// [TODO] JWKS
			case 'keys':
				// [TODO] Load keys from OAuth app
				// [TODO] Support key rotation
				// [TODO] Cache this output
				
				$privateKeyPath = DevblocksPlatform::services()->oauth()->getServerPrivateKeyPath();
				$privateKey = file_get_contents($privateKeyPath);
				
				$data = openssl_pkey_get_private($privateKey);
				$data = openssl_pkey_get_details($data);
				
				$n = DevblocksPlatform::services()->string()->base64UrlEncode($data['rsa']['n']);
				$e = base64_encode($data['rsa']['e']);
				
				$response = [
					'keys' => [
						[
							'kty' => 'RSA',
							'n' => $n,
							'e' => $e,
							'alg' => 'RS256',
							'use' => 'sig',
							'kid' => sha1($e.$n),
						],
					]
				];
				
				header('Content-Type: application/json; charset=utf-8');
				
				echo DevblocksPlatform::strFormatJson(json_encode($response));
				break;
			
			// [TODO] Discovery
			case 'well-known':
				$url_writer = DevblocksPlatform::services()->url();
				
				// [TODO] See: https://wgm-dev-ed.my.salesforce.com/.well-known/openid-configuration
				$response = [
					'issuer' => $url_writer->write('', true),
					'authorization_endpoint' => $url_writer->write('c=oauth&a=authorize', true),
					'token_endpoint' => $url_writer->write('c=oauth&a=access_token', true),
					'userinfo_endpoint' => $url_writer->write('c=oauth&a=userinfo', true),
					'jwks_uri' => $url_writer->write('c=oauth&a=keys', true),
					//'revocation_endpoint' => $url_writer->write('c=services&a=oauth2&m=revoke', true),
					//'registration_endpoint' => $url_writer->write('c=services&a=oauth2&m=register', true),
					//'introspection_endpoint' => $url_writer->write('c=services&a=oauth2&m=introspect', true),
					'scopes_supported' => [
						'id',
						'email',
//						'profile',
//						'address',
//						'phone',
					],
					'response_types_supported' => [
						'code',
						'token',
						'token id_token',
					],
					'subject_types_supported' => [
						'public',
					],
					'id_token_signing_alg_supported' => [
						'RS256'
					],
					'display_values_supported' => [
						'page',
						//'popup',
						//'touch',
					],
					'token_endpoint_auth_methods_supported' => [
						'client_secret_post',
						//'client_secret_basic',
						//'private_key_jwt',
					],
					'claims_supported' => [
						'email',
						'email_verified',
						'name',
						'sub',
//						'address',
//						'birthdate',
//						'family_name',
//						'gender',
//						'given_name',
//						'locale',
//						'middle_name',
//						'nickname',
//						'phone_number',
//						'phone_number_verified',
//						'picture',
//						'preferred_username',
//						'profile',
//						'updated_at',
//						'website',
//						'zoneinfo',
					],
				];
				
				header('Content-Type: application/json; charset=utf-8');
				
				echo DevblocksPlatform::strFormatJson(json_encode($response));
				return;
		
			default:
				DevblocksPlatform::dieWithHttpError($translate->_('common.access_denied'), 403);
				break;
		}
	}
};