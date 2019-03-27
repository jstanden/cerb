<?php
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericResourceOwner;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
use League\OAuth2\Server\Entities\Traits\AuthCodeTrait;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\RefreshTokenTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AbstractGrant;
use League\OAuth2\Server\ResponseTypes\BearerTokenResponse;
use GuzzleHttp\Psr7\Response;

class Cerb_OAuth2Provider extends AbstractProvider {
	use BearerAuthorizationTrait;

	private $urlAuthorize;
	private $urlAccessToken;
	private $urlResourceOwnerDetails;
	private $accessTokenMethod;
	private $accessTokenResourceOwnerId;
	private $approvalPrompt = 'auto';
	private $scopes = null;
	private $scopeSeparator;
	private $responseError = 'error';
	private $responseCode;
	private $responseResourceOwnerId = 'id';

	/**
	 * @param array $options
	 * @param array $collaborators
	 */
	public function __construct(array $options = [], array $collaborators = []) {
		$this->assertRequiredOptions($options);

		$possible   = $this->getConfigurableOptions();
		$configured = array_intersect_key($options, array_flip($possible));

		foreach ($configured as $key => $value) {
			$this->$key = $value;
		}

		// Remove all options that are only used locally
		$options = array_diff_key($options, $configured);

		parent::__construct($options, $collaborators);
	}

	/**
	 * Returns all options that can be configured.
	 *
	 * @return array
	 */
	protected function getConfigurableOptions() {
		return array_merge($this->getRequiredOptions(), [
			'accessTokenMethod',
			'accessTokenResourceOwnerId',
			'approvalPrompt',
			'scopeSeparator',
			'responseError',
			'responseCode',
			'responseResourceOwnerId',
			'scopes',
		]);
	}

	/**
	 * Returns all options that are required.
	 *
	 * @return array
	 */
	protected function getRequiredOptions() {
		return [
			'urlAuthorize',
			'urlAccessToken',
			'urlResourceOwnerDetails',
		];
	}

	/**
	 * Verifies that all required options have been passed.
	 *
	 * @param  array $options
	 * @return void
	 * @throws InvalidArgumentException
	 */
	private function assertRequiredOptions(array $options) {
		$missing = array_diff_key(array_flip($this->getRequiredOptions()), $options);

		if (!empty($missing)) {
			throw new InvalidArgumentException(
				'Required options not defined: ' . implode(', ', array_keys($missing))
			);
		}
	}

	public function getBaseAuthorizationUrl() {
		return $this->urlAuthorize;
	}

	public function getBaseAccessTokenUrl(array $params) {
		return $this->urlAccessToken;
	}

	public function getResourceOwnerDetailsUrl(AccessToken $token) {
		return $this->urlResourceOwnerDetails;
	}

	public function getDefaultScopes() {
		return $this->scopes;
	}
	
	public function getApprovalPrompt() {
		return $this->approvalPrompt;
	}

	protected function getAccessTokenMethod() {
		return $this->accessTokenMethod ?: parent::getAccessTokenMethod();
	}

	protected function getAccessTokenResourceOwnerId() {
		return $this->accessTokenResourceOwnerId ?: parent::getAccessTokenResourceOwnerId();
	}

	protected function getScopeSeparator() {
		return $this->scopeSeparator ?: parent::getScopeSeparator();
	}

	protected function checkResponse(ResponseInterface $response, $data) {
		if (!empty($data[$this->responseError])) {
			$error = $data[$this->responseError];
			if (!is_string($error)) {
				$error = var_export($error, true);
			}
			$code  = $this->responseCode && !empty($data[$this->responseCode])? $data[$this->responseCode] : 0;
			if (!is_int($code)) {
				$code = intval($code);
			}
			throw new IdentityProviderException($error, $code, $data);
		}
	}

	protected function createResourceOwner(array $response, AccessToken $token) {
		return new GenericResourceOwner($response, $this->responseResourceOwnerId);
	}
	
	public function getAuthorizationUrl(array $options = []) {
		$approval_prompt = $this->getApprovalPrompt();
		
		$options['approval_prompt'] = $approval_prompt ?: null;
		
		return parent::getAuthorizationUrl($options);
	}
}

class Cerb_OAuth2UserEntity implements UserEntityInterface {
	use EntityTrait;
	
	function __construct(Model_Worker $worker=null) {
		if(!is_null($worker)) {
			$this->setIdentifier($worker->id);
		}
	}
}

/*
class Cerb_OAuth2UserRepository implements UserRepositoryInterface {
	public function getUserEntityByUserCredentials($username, $password, $grantType, ClientEntityInterface $clientEntity) {
		error_log('Cerb_OAuth2UserRepository->getUserEntityByUserCredentials()');
		error_log(json_encode([$username, $password, $grantType, $clientEntity]));
		return new Cerb_OAuth2UserEntity();
	}
}
*/

class Cerb_OAuth2RefreshTokenEntity implements RefreshTokenEntityInterface {
	use EntityTrait, RefreshTokenTrait;
}

class Cerb_OAuth2RefreshTokenRepository implements RefreshTokenRepositoryInterface {
	public function isRefreshTokenRevoked($tokenId) {
		if(false == ($token = DAO_OAuthToken::getRefreshToken($tokenId)))
			return true;
		
		if($token->expires_at < time())
			return true;
		
		return false;
	}

	public function getNewRefreshToken() {
		return new Cerb_OAuth2RefreshTokenEntity();
	}

	public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity) {
		$access_token = $refreshTokenEntity->getAccessToken();
		$client = $access_token->getClient();
		
		$oauth_app = DAO_OAuthApp::getByClientId($client->getIdentifier());
		
		$fields = [
			DAO_OAuthToken::APP_ID => $oauth_app->id,
			DAO_OAuthToken::EXPIRES_AT => $refreshTokenEntity->getExpiryDateTime()->getTimestamp(),
			DAO_OAuthToken::TOKEN => $refreshTokenEntity->getIdentifier(),
			DAO_OAuthToken::WORKER_ID => $access_token->getUserIdentifier(),
		];
		DAO_OAuthToken::createRefreshToken($fields);
	}

	public function revokeRefreshToken($tokenId) {
		DAO_OAuthToken::deleteRefreshToken($tokenId);
	}
}

class Cerb_OAuth2AuthCodeEntity implements AuthCodeEntityInterface {
	use EntityTrait, TokenEntityTrait, AuthCodeTrait;
}

class Cerb_OAuth2AuthCodeRepository implements AuthCodeRepositoryInterface {
	public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity) {
		$client = $authCodeEntity->getClient();
		
		$oauth_app = DAO_OAuthApp::getByClientId($client->getIdentifier());
		
		$fields = [
			DAO_OAuthToken::APP_ID => $oauth_app->id,
			DAO_OAuthToken::EXPIRES_AT => $authCodeEntity->getExpiryDateTime()->getTimestamp(),
			DAO_OAuthToken::TOKEN => $authCodeEntity->getIdentifier(),
			DAO_OAuthToken::WORKER_ID => $authCodeEntity->getUserIdentifier(),
		];
		DAO_OAuthToken::createAuthToken($fields);
	}
	
	public function getNewAuthCode() {
		return new Cerb_OAuth2AuthCodeEntity();
	}
	
	public function revokeAuthCode($codeId) {
		DAO_OAuthToken::deleteAuthToken($codeId);
	}
	
	public function isAuthCodeRevoked($codeId) {
		if(false == ($token = DAO_OAuthToken::getAuthToken($codeId)))
			return true;
		
		if($token->expires_at < time())
			return true;
		
		return false;
	}
}

class Cerb_OAuth2ScopeEntity implements ScopeEntityInterface {
	use EntityTrait;
	
	public function jsonSerialize() {
		return $this->getIdentifier();
	}
}

class Cerb_OAuth2ScopeRepository implements ScopeRepositoryInterface {
	public function finalizeScopes(array $scopes, $grantType, ClientEntityInterface $clientEntity, $userIdentifier = null) {
		// Modify final scopes
		return $scopes;
	}

	public function getScopeEntityByIdentifier($identifier) {
		$scope = new Cerb_OAuth2ScopeEntity();
		$scope->setIdentifier($identifier);
		return $scope;
	}
}

class Cerb_OAuth2AccessTokenEntity implements AccessTokenEntityInterface {
	use AccessTokenTrait, EntityTrait, TokenEntityTrait;
}

class Cerb_OAuth2AccessTokenRepository implements AccessTokenRepositoryInterface {
	public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity) {
		$client = $accessTokenEntity->getClient();
		
		$oauth_app = DAO_OAuthApp::getByClientId($client->getIdentifier());
		
		$fields = [
			DAO_OAuthToken::APP_ID => $oauth_app->id,
			DAO_OAuthToken::EXPIRES_AT => $accessTokenEntity->getExpiryDateTime()->getTimestamp(),
			DAO_OAuthToken::TOKEN => $accessTokenEntity->getIdentifier(),
			DAO_OAuthToken::WORKER_ID => $accessTokenEntity->getUserIdentifier(),
		];
		DAO_OAuthToken::createAccessToken($fields);
	}

	public function revokeAccessToken($tokenId) {
		DAO_OAuthToken::deleteAccessToken($tokenId);
	}

	public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null) {
		$access_token = new Cerb_OAuth2AccessTokenEntity();
		$access_token->setClient($clientEntity);
		
		foreach($scopes as $scope) {
			$access_token->addScope($scope);
		}
		
		$access_token->setUserIdentifier($userIdentifier);

		return $access_token;
	}

	public function isAccessTokenRevoked($tokenId) {
		if(false == ($token = DAO_OAuthToken::getAccessToken($tokenId)))
			return true;
		
		if($token->expires_at < time())
			return true;
		
		return false;
	}
}

class Cerb_OAuth2ClientEntity implements ClientEntityInterface {
	use ClientTrait, EntityTrait;
	
	public function __construct($clientIdentifier) {
		$this->setIdentifier($clientIdentifier);
	}
	
	public function setName($name) {
		$this->name = $name;
	}
	
	public function setRedirectUris($uris) {
		if(!is_array($uris))
			$uris = [$uris];
		
		$this->redirectUri = $uris;
	}
};

class Cerb_OAuth2ClientRespository implements ClientRepositoryInterface {
	public function getClientEntity($clientIdentifier, $grantType = null, $clientSecret = null, $mustValidateSecret = true) {
		if(false == ($oauth_client = DAO_OAuthApp::getByClientId($clientIdentifier)))
			throw OAuthServerException::invalidClient();
		
		if($mustValidateSecret && $oauth_client->client_secret != $clientSecret)
			throw OAuthServerException::invalidCredentials();
		
		$client = new Cerb_OAuth2ClientEntity($clientIdentifier);
		
		$client->setName($oauth_client->name);
		$client->setRedirectUris($oauth_client->callback_url);
		
		return $client;
	}
};

class Cerb_OAuth2GrantManual extends AbstractGrant {
	public function __construct() {
		$this->setClientRepository(new Cerb_OAuth2ClientRespository());
		$this->setAuthCodeRepository(new Cerb_OAuth2AuthCodeRepository());
		$this->setAccessTokenRepository(new Cerb_OAuth2AccessTokenRepository());
		$this->setRefreshTokenRepository(new Cerb_OAuth2RefreshTokenRepository());
		$this->setScopeRepository(new Cerb_OAuth2ScopeRepository());
	}
	
	public function respondToAccessTokenRequest(ServerRequestInterface $request, ResponseTypeInterface $responseType, \DateInterval $accessTokenTTL) {}
	public function getIdentifier() {}
	
	public function generateBearerToken(Model_OAuthApp $oauth2_app, $actor_identifier, array $scopes=[]) {
		// [TODO] From app
		$accessTokenTTL = \DateInterval::createFromDateString('1 hour');
		$refreshTokenTTL = \DateInterval::createFromDateString('1 month');
		
		$encrypt = DevblocksPlatform::services()->encryption();
		
		$this->setRefreshTokenTTL($refreshTokenTTL);
		
		$client = $this->clientRepository->getClientEntity($oauth2_app->client_id, null, $oauth2_app->client_secret, true);

		$accessToken = $this->issueAccessToken($accessTokenTTL, $client, $actor_identifier);
		
		foreach($scopes as $scope_identifier) {
			$scope = $this->scopeRepository->getScopeEntityByIdentifier($scope_identifier);
			$accessToken->addScope($scope);
		}
		
		$refreshToken = $this->issueRefreshToken($accessToken);
		
		$encryptionKey = $encrypt->getSystemKey();
		$encryptionKey = \Defuse\Crypto\Key::loadFromAsciiSafeString($encryptionKey);
		
		$response_type = new BearerTokenResponse();
		$response_type->setAccessToken($accessToken);
		$response_type->setRefreshToken($refreshToken);
		$response_type->setPrivateKey(DevblocksPlatform::services()->oauth()->getServerPrivateKey());
		$response_type->setEncryptionKey($encryptionKey);
		
		$response = new Response();
		$response = $response_type->generateHttpResponse($response);
		
		$response->getBody()->rewind();
		$json = $response->getBody()->getContents();
		
		return json_decode($json, true);
	}
};