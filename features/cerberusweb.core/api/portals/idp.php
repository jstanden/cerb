<?php

use Defuse\Crypto\Key;
use GuzzleHttp\Psr7\Response;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use League\OAuth2\Server\ResponseTypes\BearerTokenResponse;
use GuzzleHttp\Psr7\ServerRequest;
use League\OAuth2\Server\Exception\OAuthServerException;
use OpenIDConnectServer\ClaimExtractor;
use OpenIDConnectServer\Repositories\IdentityProviderInterface;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use OpenIDConnectServer\Entities\ClaimSetInterface;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
use League\OAuth2\Server\Entities\Traits\AuthCodeTrait;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\RefreshTokenTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;
use Lcobucci\JWT\Signer\Rsa\Sha256;

class PortalIdp_IdentityAuthState {
	private static $_instance = null;
	
	private $email = null;
	private int $identity_id = 0;
	private bool $is_consent_given = false;
	private array $is_consent_required = [];
	private bool $is_mfa_authenticated = false;
	private bool $is_mfa_required = false;
	private bool $is_password_authenticated = false;
	private bool $is_sso_authenticated = false;
	private array $params = [];
	private array $redirect_uris = [];
	private int $time_created_at = 0;
	private int $time_consented_at = 0;
	private int $time_mfa_challenged_at = 0;
	private bool $was_consent_asked = false;
	
	private $identity = null;
	
	static function getInstance() {
		if(!is_null(self::$_instance))
			return self::$_instance;
		
		$session = ChPortalHelper::getSession();
		
		@$session_state = $session->getProperty('login.state');
		
		if(
			!$session_state 
			|| $session_state instanceof __PHP_Incomplete_Class
			|| $session_state->time_created_at + 1200 < time() // expire after 20 minutes
		) {
			$session_state = new PortalIdp_IdentityAuthState();
		}
		
		self::$_instance = $session_state;
		return self::$_instance;
	}
	
	private function __construct() {
		$this->time_created_at = time();
	}
	
	function __destruct() {
		$session = ChPortalHelper::getSession();
		$session->setProperty('login.state', $this);
	}
	
	function __sleep() {
		return [
			'email',
			'identity_id',
			'is_consent_given',
			'is_consent_required',
			'is_mfa_authenticated',
			'is_mfa_required',
			'is_password_authenticated',
			'is_sso_authenticated',
			'params',
			'redirect_uris',
			'time_created_at',
			'time_consented_at',
			'time_mfa_challenged_at',
			'was_consent_asked',
		];
	}
	
	function __wakeup() {
		if($this->identity_id)
			$this->setIdentity(DAO_Identity::get($this->identity_id));
	}
	
	function destroy() {
		self::$_instance = null;
		
		$session = ChPortalHelper::getSession();
		$session->setProperty('login.state', null);
	}
	
	function clearAuthState() : PortalIdp_IdentityAuthState {
		return $this
			->setIdentity(null)
			->setParams([])
			->setIsConsentGiven(false)
			->setIsMfaAuthenticated(false)
			->setIsMfaRequired(false)
			->setIsPasswordAuthenticated(false)
			->setIsSSOAuthenticated(false)
			->setTimeConsentedAt(0)
			->setTimeMfaChallengedAt(0)
			->setWasConsentAsked(false)
			;
	}
	
	function getEmail() {
		return $this->email;
	}
	
	/**
	 *
	 * @param array $options
	 * @return boolean
	 */
	function isAuthenticated(array $options=[]) : bool {
		if(!$this->getIdentity())
			return false;
		
		if(!(array_key_exists('ignore_mfa', $options) && $options['ignore_mfa']))
		if($this->isMfaRequired() && !$this->isMfaAuthenticated())
			return false;
		
		if($this->isSSOAuthenticated())
			return true;
		
		if(!$this->isPasswordAuthenticated())
			return false;
		
		return true;
	}
	
	function isConsentGiven() : bool {
		return $this->is_consent_given;
	}
	
	function isConsentRequired() : array {
		return $this->is_consent_required;
	}
	
	function isMfaAuthenticated() : bool {
		return $this->is_mfa_authenticated;
	}
	
	function isMfaRequired() : bool {
		return $this->is_mfa_required;
	}
	
	function isPasswordAuthenticated() : bool {
		return $this->identity_id && $this->is_password_authenticated;
	}
	
	function isSSOAuthenticated() : bool {
		return $this->is_sso_authenticated;
	}
	
	function getParam($key, $default=null) {
		if(array_key_exists($key, $this->params))
			return $this->params[$key];
		
		return $default;
	}
	
	function popRedirectUri() {
		return array_pop($this->redirect_uris);
	}
	
	function pushRedirectUri($uri) : PortalIdp_IdentityAuthState {
		$this->redirect_uris[] = $uri;
		return $this;
	}
	
	function getIdentity() {
		return $this->identity;
	}
	
	function getIdentityId() : ?int {
		return $this->identity_id;
	}
	
	function setEmail($email) : PortalIdp_IdentityAuthState {
		$this->email = DevblocksPlatform::strLower($email);
		return $this;
	}
	
	function setIsConsentGiven($bool) : PortalIdp_IdentityAuthState {
		$this->is_consent_given = boolval($bool);
		return $this;
	}
	
	function setIsConsentRequired(array $params) : PortalIdp_IdentityAuthState {
		$this->is_consent_required = $params ?: [];
		return $this;
	}
	
	function setIsPasswordAuthenticated($bool) : PortalIdp_IdentityAuthState {
		$this->is_password_authenticated = boolval($bool);
		return $this;
	}
	
	function setIsMfaAuthenticated($bool) : PortalIdp_IdentityAuthState {
		$this->is_mfa_authenticated = boolval($bool);
		return $this;
	}
	
	function setIsMfaRequired($bool) : PortalIdp_IdentityAuthState {
		$this->is_mfa_required = boolval($bool);
		return $this;
	}
	
	function setIsSSOAuthenticated($bool) : PortalIdp_IdentityAuthState {
		$this->is_sso_authenticated = boolval($bool);
		return $this;
	}
	
	function setParams(array $params) : PortalIdp_IdentityAuthState {
		$this->params = $params;
		return $this;
	}
	
	function setParam($key, $value) : PortalIdp_IdentityAuthState {
		$this->params[$key] = $value;
		return $this;
	}
	
	function setParamIncr($key, $n, $min=PHP_INT_MIN, $max=PHP_INT_MAX) : PortalIdp_IdentityAuthState {
		$value = intval($this->params[$key] ?? 0);
		$this->params[$key] = DevblocksPlatform::intClamp($value + intval($n), $min, $max);
		return $this;
	}
	
	function setParamDecr($key, $n, $min=PHP_INT_MIN, $max=PHP_INT_MAX) : PortalIdp_IdentityAuthState {
		$value = intval($this->params[$key] ?? 0);
		$this->params[$key] = DevblocksPlatform::intClamp($value - intval($n), $min, $max);
		return $this;
	}
	
	function setTimeConsentedAt($time) : PortalIdp_IdentityAuthState {
		$this->time_consented_at = intval($time);
		return $this;
	}
	
	function setTimeMfaChallengedAt($time) : PortalIdp_IdentityAuthState {
		$this->time_mfa_challenged_at = intval($time);
		return $this;
	}
	
	function setWasConsentAsked($bool) : PortalIdp_IdentityAuthState {
		$this->was_consent_asked = boolval($bool);
		return $this;
	}
	
	function setIdentity($identity) : PortalIdp_IdentityAuthState {
		if(!($identity instanceof Model_Identity)) {
			$this->identity_id = 0;
			$this->identity = null;
			
		} else {
			$this->identity_id = $identity->id;
			$this->identity = $identity;
		}
		
		return $this;
	}
	
	function unsetParam($key) : PortalIdp_IdentityAuthState {
		unset($this->params[$key]);
		return $this;
	}
	
	function wasConsentAsked() : bool {
		return $this->was_consent_asked;
	}
};

class PortalIdp_IdTokenResponse extends BearerTokenResponse {
	protected IdentityProviderInterface $identityProvider;

	protected ClaimExtractor $claimExtractor;

	public function __construct(IdentityProviderInterface $identityProvider, ClaimExtractor $claimExtractor) {
		$this->identityProvider = $identityProvider;
		$this->claimExtractor = $claimExtractor;
	}
	
	/**
	 * @param AccessTokenEntityInterface $accessToken
	 * @return array
	 */
	protected function getExtraParams(AccessTokenEntityInterface $accessToken) : array {
		$encrypt = DevblocksPlatform::services()->encryption();
		
		$url_writer = DevblocksPlatform::services()->url();
		$issuer = $url_writer->write('', true);
		
		if (false === $this->isOpenIDRequest($accessToken->getScopes()))
			return [];

		/** @var UserEntityInterface $userEntity */
		$userEntity = $this->identityProvider->getUserEntityByIdentifier($accessToken->getUserIdentifier());

		if (false === is_a($userEntity, UserEntityInterface::class)) {
			throw new \RuntimeException('UserEntity must implement UserEntityInterface');
		} else if (false === is_a($userEntity, ClaimSetInterface::class)) {
			throw new \RuntimeException('UserEntity must implement ClaimSetInterface');
		}
		
		$now = new DateTimeImmutable();
		
		$encryptionKey = $encrypt->getSystemKey();
		$encryptionKey = Key::loadFromAsciiSafeString($encryptionKey);
		
		$this->setPrivateKey(DevblocksPlatform::services()->oauth()->getServerPrivateKey());
		$this->setEncryptionKey($encryptionKey);
		
		$config = Configuration::forSymmetricSigner(
			new Sha256(),
			InMemory::file($this->privateKey->getKeyPath(), $this->privateKey->getPassPhrase() ?? '')
		);
		
		// Add required id_token claims
		$token = $config->builder()
			->issuedBy($issuer)
			->withHeader('iss', $issuer)
			->permittedFor($accessToken->getClient()->getIdentifier())
			//->identifiedBy() // [TODO]
			->relatedTo($userEntity->getIdentifier())
			->issuedAt($now)
			->canOnlyBeUsedAfter($now)
			->expiresAt($accessToken->getExpiryDateTime())
			//->withClaim('uid', 1)
			// [TODO] Make dynamic
			->withHeader('kid', '0f76a4eb2e18b3f1f532228347ea9cfb9a981c92')
		;
		
		// Need a claim factory here to reduce the number of claims by provided scope.
		$claims = $this->claimExtractor->extract($accessToken->getScopes(), $userEntity->getClaims());

		foreach ($claims as $claimName => $claimValue) {
			$token = $token->withClaim($claimName, $claimValue);
		}
		
		try {
			$id_token = $token->getToken($config->signer(), $config->signingKey());
		} catch(Exception $e) {
			// [TODO] Handle errors
			error_log($e->getMessage());
			return [];
		}

		return [
			'id_token' => $id_token->toString()
		];
	}

	/**
	 * @param ScopeEntityInterface[] $scopes
	 * @return bool
	 */
	private function isOpenIDRequest(array $scopes) : bool {
		// Verify scope and make sure openid exists.
		$valid  = false;
		
		foreach ($scopes as $scope) {
			if ($scope->getIdentifier() === 'openid') {
				$valid = true;
				break;
			}
		}
		
		return $valid;
	}
}

class PortalIdp_UserEntity implements UserEntityInterface, ClaimSetInterface {
	use EntityTrait;
	
	protected array $attributes = [];
	
	function __construct(Model_Identity $identity=null) {
		if(!is_null($identity)) {
			$this->setIdentifier('identity:' . $identity->id);
			
			$this->attributes['sub'] = 'identity:' . $identity->id;
			$this->attributes['name'] = $identity->name;
			$this->attributes['given_name'] = $identity->given_name;
			$this->attributes['family_name'] = $identity->family_name;
			$this->attributes['middle_name'] = $identity->middle_name;
			$this->attributes['nickname'] = $identity->nickname;
			$this->attributes['preferred_username'] = $identity->username;
			$this->attributes['website'] = $identity->website;
			$this->attributes['gender'] = $identity->getGenderAsString();
			$this->attributes['birthdate'] = $identity->birthdate;
			$this->attributes['zoneinfo'] = $identity->zoneinfo;
			$this->attributes['locale'] = $identity->locale;
			$this->attributes['address'] = $identity->address;
			$this->attributes['updated_at'] = $identity->updated_at;
			
			// [TODO] If scoped
			$this->attributes['email'] = $identity->getEmailAsString();
			$this->attributes['email_verified'] = $identity->email_verified ? true : false;
			
			// [TODO] If scoped
			$this->attributes['phone_number'] = $identity->phone_number;
			$this->attributes['phone_number_verified'] = $identity->phone_number_verified ? true : false;

			// [TODO] To URLs within the IdP portal
			$this->attributes['profile'] = ''; // [TODO]
			$this->attributes['picture'] = ''; // [TODO]
		}
	}
	
	public function getClaims() {
		return $this->attributes;
	}
}

class PortalIdp_RefreshTokenEntity implements RefreshTokenEntityInterface {
	use EntityTrait, RefreshTokenTrait;
}

class PortalIdp_RefreshTokenRepository implements RefreshTokenRepositoryInterface {
	public function isRefreshTokenRevoked($tokenId) : bool {
		// [TODO]
		/*
		if(false == ($token = DAO_OAuthToken::getRefreshToken($tokenId)))
			return true;
		
		if($token->expires_at < time())
			return true;
		*/
		return false;
	}

	public function getNewRefreshToken() : PortalIdp_RefreshTokenEntity {
		return new PortalIdp_RefreshTokenEntity();
	}

	public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity) {
		// [TODO]
		/*
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
		*/
	}

	public function revokeRefreshToken($tokenId) {
		// [TODO]
		//DAO_OAuthToken::deleteRefreshToken($tokenId);
	}
}

class PortalIdp_AuthCodeEntity implements AuthCodeEntityInterface {
	use EntityTrait, TokenEntityTrait, AuthCodeTrait;
}

class PortalIdp_AuthCodeRepository implements AuthCodeRepositoryInterface {
	public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity) {
		// [TODO]
		/*
		$client = $authCodeEntity->getClient();
		
		$oauth_app = DAO_OAuthApp::getByClientId($client->getIdentifier());
		
		$fields = [
			DAO_OAuthToken::APP_ID => $oauth_app->id,
			DAO_OAuthToken::EXPIRES_AT => $authCodeEntity->getExpiryDateTime()->getTimestamp(),
			DAO_OAuthToken::TOKEN => $authCodeEntity->getIdentifier(),
			DAO_OAuthToken::WORKER_ID => $authCodeEntity->getUserIdentifier(),
		];
		DAO_OAuthToken::createAuthToken($fields);
		*/
	}
	
	public function getNewAuthCode() : PortalIdp_AuthCodeEntity {
		return new PortalIdp_AuthCodeEntity();
	}
	
	public function revokeAuthCode($codeId) {
		// [TODO]
		//DAO_OAuthToken::deleteAuthToken($codeId);
	}
	
	public function isAuthCodeRevoked($codeId) : bool {
		// [TODO]
		/*
		if(false == ($token = DAO_OAuthToken::getAuthToken($codeId)))
			return true;
		
		if($token->expires_at < time())
			return true;
		
		return false;
		*/
		return false;
	}
}

class PortalIdp_ScopeEntity implements ScopeEntityInterface {
	use EntityTrait;
	
	public function jsonSerialize() : mixed {
		return $this->getIdentifier();
	}
}

class PortalIdp_ScopeRepository implements ScopeRepositoryInterface {
	public function finalizeScopes(array $scopes, $grantType, ClientEntityInterface $clientEntity, $userIdentifier = null) : array {
		// Modify final scopes
		return $scopes;
	}

	public function getScopeEntityByIdentifier($identifier) : PortalIdp_ScopeEntity {
		$scope = new PortalIdp_ScopeEntity();
		$scope->setIdentifier($identifier);
		return $scope;
	}
}

class PortalIdp_AccessTokenEntity implements AccessTokenEntityInterface {
	use AccessTokenTrait, EntityTrait, TokenEntityTrait;
}

class PortalIdp_AccessTokenRepository implements AccessTokenRepositoryInterface {
	public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity) {
		// [TODO]
		/*
		$client = $accessTokenEntity->getClient();
		
		$oauth_app = DAO_OAuthApp::getByClientId($client->getIdentifier());
		
		$fields = [
			DAO_OAuthToken::APP_ID => $oauth_app->id,
			DAO_OAuthToken::EXPIRES_AT => $accessTokenEntity->getExpiryDateTime()->getTimestamp(),
			DAO_OAuthToken::TOKEN => $accessTokenEntity->getIdentifier(),
			DAO_OAuthToken::WORKER_ID => $accessTokenEntity->getUserIdentifier(),
		];
		DAO_OAuthToken::createAccessToken($fields);
		*/
	}

	public function revokeAccessToken($tokenId) {
		// [TODO]
		//DAO_OAuthToken::deleteAccessToken($tokenId);
	}

	public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null) : PortalIdp_AccessTokenEntity {
		$access_token = new PortalIdp_AccessTokenEntity();
		$access_token->setClient($clientEntity);
		
		foreach($scopes as $scope) {
			$access_token->addScope($scope);
		}
		
		$access_token->setUserIdentifier($userIdentifier);

		return $access_token;
	}

	public function isAccessTokenRevoked($tokenId) : bool {
		// [TODO]
		/*
		if(false == ($token = DAO_OAuthToken::getAccessToken($tokenId)))
			return true;
		
		if($token->expires_at < time())
			return true;
		
		return false;
		*/
		return false;
	}
}

class PortalIdp_ClientEntity implements ClientEntityInterface {
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
	
	public function isConfidential() : bool {
		return true;
	}
};

class PortalIdp_ClientRepository implements ClientRepositoryInterface {
	// [TODO] Which clients can access this portal
	public function getClientEntity($clientIdentifier) : ?PortalIdp_ClientEntity {
		if(false == ($oauth_client = DAO_OAuthApp::getByClientId($clientIdentifier)))
			return null;
		
		$client = new PortalIdp_ClientEntity($clientIdentifier);
		
		$client->setName($oauth_client->name);
		$client->setRedirectUris($oauth_client->callback_url);
		
		return $client;
	}
	
	// TODO: Implement validateClient() method.
	public function validateClient($clientIdentifier, $clientSecret, $grantType) : bool {
		if('authorization_code' != $grantType)
			return false;
		
		if(false == ($oauth_client = DAO_OAuthApp::getByClientId($clientIdentifier)))
			return false;
		
		if($oauth_client->client_secret != $clientSecret)
			return false;
		
		return true;
	}
};

class PortalIdp_IdentityRepository implements IdentityProviderInterface {
	public function getUserEntityByIdentifier($identifier) : ?PortalIdp_UserEntity {
		if(!DevblocksPlatform::strStartsWith($identifier, 'identity:'))
			return null;
		
		list(,$identity_id) = explode(':', $identifier, 2);
		
		if(!$identity_id || false == ($identity = DAO_Identity::get($identity_id)))
			return null;
		
		return new PortalIdp_UserEntity($identity);
	}
}

// [TODO] Registration
// [TODO] OIDC page
// [TODO] OIDC issuer URL
// [TODO] JWKS URL
class Portal_IdentityProvider extends Extension_CommunityPortal {
	const ID = 'cerb.portal.idp';
	
	/**
	 * @param Model_CommunityTool $instance
	 */
	public function configure(Model_CommunityTool $instance) {
		$tpl = DevblocksPlatform::services()->template();
		
		$params = DAO_CommunityToolProperty::getAllByTool($instance->code);
		$tpl->assign('params', $params);
		
		if(false != ($identity_pool_id = $instance->getParam('identity_pool_id', 0))) {
			if(false != ($identity_pool = DAO_IdentityPool::get($identity_pool_id)))
				$tpl->assign('identity_pool', $identity_pool);
		}
		
		$tpl->assign('model', $instance);
		$tpl->display('devblocks:cerberusweb.core::portals/idp/config.tpl');
	}

	// [TODO] Return true/false?
	public function saveConfiguration(Model_CommunityTool $instance) {
		$portal_id = DevblocksPlatform::importGPC($_POST['portal_id'] ?? null, 'integer', 0);
		$params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', []);
		
		if(false == ($active_worker = CerberusApplication::getActiveWorker()))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		// Only valid param keys
		$params = array_intersect_key(
			$params,
			array_fill_keys([
				'identity_pool_id',
				'user_stylesheet',
			], true));
		
		try {
			if(!$portal_id || false == ($portal = DAO_CommunityTool::get($portal_id)))
				throw new Exception_DevblocksAjaxValidationError('Invalid portal ID.');
			
			if(!Context_CommunityTool::isWriteableByActor($portal, $active_worker))
				DevblocksPlatform::dieWithHttpError('', 403);
			
			$old_params = DAO_CommunityToolProperty::getAllByTool($portal->code);
			
			@$identity_pool_id = $params['identity_pool_id'] ?: 0;
			@$user_stylesheet = $params['user_stylesheet'] ?: '';
			
			// Identity Pool
			
			DAO_CommunityToolProperty::set($portal->code, 'identity_pool_id', $identity_pool_id);
			
			// If the pool ID changes, delete any active sessions for this portal)
			if(@$old_params['identity_pool_id'] != $identity_pool_id) {
				DAO_CommunitySession::deleteByPortalId($portal->id);
			}
			
			// Stylesheet
			
			DAO_CommunityToolProperty::set($portal->code, 'user_stylesheet', $user_stylesheet);
			
			return true;
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
			return false;
		}
	}

	/**
	 * @param DevblocksHttpRequest
	 * @return DevblocksHttpResponse
	 */
	public function handleRequest(DevblocksHttpRequest $request) {
	}

	public function writeResponse(DevblocksHttpResponse $response) {
		$stack = $response->path;
		$path = array_shift($stack);
		
		$session = ChPortalHelper::getSession();
		
		switch($path) {
			case '.well-known':
				$this->routeDiscovery($stack);
				return;
				
			case 'services':
				$this->routeServices($stack);
				return;
				
			case 'login':
				$this->routeLogin($stack);
				return;
				
			case 'logout':
				$this->routeLogout($stack);
				return;
				
			case 'profile':
				$this->routeProfile($stack);
				return;
				
			default:
				if(null != $session->getIdentity()) {
					$this->routeProfile($stack);
					
				} else {
					$this->routeLogin($stack);
					//DevblocksPlatform::redirect(new DevblocksHttpRequest(['login']));
				}
				return;
		}
		
		DevblocksPlatform::dieWithHttpError('', 404);
	}
	
	static function getErrorMessage($code) {
		$error_messages = [
			'account.disabled' => "Your account is disabled.",
			'account.locked' => "Your account has been temporarily locked after too many failed login attempts. Please wait a few minutes and try again.",
			'auth.expired' => "The code has expired.",
			'auth.failed' => "Authentication failed.",
			'confirm.failed' => "The given confirmation code doesn't match the one on file.",
			'confirm.invalid' => "The given confirmation code is invalid.",
			'email.invalid' => "The provided email address is not valid.",
			'email.unavailable' => "The provided email address is not available.",
			'email.unverified' => "Your email address has not been verified.",
			'mfa.failed' => "The security code you entered is incorrect.",
			'password.invalid' => "The given password is invalid.",
			'password.mismatch' => "The given passwords do not match.",
		];
		
		$error = "An unexpected error occurred. Please try again.";
		
		if(array_key_exists($code, $error_messages))
			$error = $error_messages[$code];
		
		return $error;
	}
	
	// [TODO] Implement
	static function logFailedAuthentication(Model_Identity $unauthenticated_identity) {
		/*
		 * Log activity (identity.login.failed)
		 */
		/*
		$ip_address = DevblocksPlatform::getClientIp() ?: 'an unknown IP';
		$user_agent = DevblocksPlatform::getClientUserAgent();
		$user_agent_string = sprintf("%s%s%s",
			$user_agent['browser'],
			!empty($user_agent['version']) ? (' ' . $user_agent['version']) : '',
			!empty($user_agent['platform']) ? (' for ' . $user_agent['platform']) : ''
		);
		
		$entry = [
			//{{ip}} failed to log in as {{target}} using {{user_agent}}
			'message' => 'activities.worker.login.failed',
			'variables' => [
				'ip' => $ip_address,
				'user_agent' => $user_agent_string,
				'target' => sprintf($unauthenticated_worker->getName()),
				],
			'urls' => [
				'target' => sprintf("ctx://%s:%d", CerberusContexts::CONTEXT_WORKER, $unauthenticated_worker->id),
				]
		];
		CerberusContexts::logActivity('worker.login.failed', CerberusContexts::CONTEXT_WORKER, $unauthenticated_worker->id, $entry);
		*/
	}
	
	function routeProfile(array $stack) {
		$tpl = DevblocksPlatform::services()->template();
		$session = ChPortalHelper::getSession();
		
		// If not logged in, bounce to login page
		if(null == ($identity = $session->getIdentity())) {
			DevblocksPlatform::redirect(new DevblocksHttpRequest(['login']));
		}
		
		$tpl->assign('identity', $identity);
		
		// Timezones
		$timezones = DevblocksPlatform::services()->date()->getTimezones();
		$tpl->assign('timezones', $timezones);
		
		// [TODO] Update profile
		
		$tpl->display('devblocks:cerberusweb.core::portals/idp/profile/profile.tpl');
	}
	
	function routeServices(array $stack) {
		$path = array_shift($stack);
		
		switch($path) {
			case 'id':
				$this->routeServicesId($stack);
				return;
				
			case 'oauth2':
				$this->routeServicesOAuth2($stack);
				return;
		}
		
		DevblocksPlatform::dieWithHttpError('', 404);
	}
	
	function routeServicesId(array $stack) {
		$path = array_shift($stack);
		
		switch($path) {
			case 'keys':
				$this->routeServicesIdKeys($stack);
				return;
		}
		
		DevblocksPlatform::dieWithHttpError('', 404);
	}
	
	function routeServicesIdKeys(array $stack) {
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
	}
	
	function routeServicesOAuth2(array $stack) {
		$path = array_shift($stack);
		
		switch($path) {
			case 'authorize':
				$this->routeServicesOAuth2Authorize($stack);
				return;
				
			case 'token':
				$this->routeServicesOAuth2Token($stack);
				return;
				
			case 'userinfo':
				$this->routeServicesOAuth2Userinfo($stack);
				return;
		}
		
		DevblocksPlatform::dieWithHttpError('', 404);
	}
	
	/*
	private function _getKeys() {
		// [TODO] Automatic key rotation
		
		$portal = ChPortalHelper::getPortal();
		$encryption = DevblocksPlatform::services()->encryption();
		
		$portal->getParam('idp.rsa.public_key');
		
		$rsa_keypair = $encryption->generateRsaKeyPair();
		$encryption_key  = $encryption->generateKey();
	}
	*/
	
	private function _getOAuth() : \League\OAuth2\Server\AuthorizationServer {
		$clientRepository = new PortalIdp_ClientRepository();
		$scopeRepository = new PortalIdp_ScopeRepository();
		$accessTokenRepository = new PortalIdp_AccessTokenRepository();
		$authCodeRepository = new PortalIdp_AuthCodeRepository();
		$refreshTokenRepository = new PortalIdp_RefreshTokenRepository();
		
		// [TODO] These should come from the OAuth app
		$privateKey = DevblocksPlatform::services()->oauth()->getServerPrivateKey();
		$encryptionKey = DevblocksPlatform::services()->encryption()->getSystemKey();
		//$publicKeyPath = DevblocksPlatform::services()->oauth()->getServerPublicKeyPath();
		
		// [TODO] Include a 'kid' (Key ID) in the token response if supporting key rotation
		// [TODO] Set the 'iss' (issuer) to our actual URL
		$responseType = new PortalIdp_IdTokenResponse(new PortalIdp_IdentityRepository(), new ClaimExtractor());
		
		$server = new \League\OAuth2\Server\AuthorizationServer(
			$clientRepository,
			$accessTokenRepository,
			$scopeRepository,
			$privateKey,
			$encryptionKey,
			//'file://' . $publicKeyPath,
			$responseType
		);
		
		$ttl_refresh_token = new \DateInterval('P1M');  // 1 month TTL for refresh token
		$ttl_access_token = new \DateInterval('PT1H'); // 1 hour TTL for access token
		$ttl_auth_code = new \DateInterval('PT10M'); // 10 mins
		
		$grant_authcode = new AuthCodeGrant(
			$authCodeRepository,
			$refreshTokenRepository,
			$ttl_auth_code
		);
		
		$grant_authcode->setRefreshTokenTTL($ttl_refresh_token);
		
		$server->enableGrantType(
			$grant_authcode,
			$ttl_access_token
		);
		
		/*
		$grant_refresh = new RefreshTokenGrant($refreshTokenRepository);
		$grant_refresh->setRefreshTokenTTL($ttl_refresh_token);
		
		$server->enableGrantType(
			$grant_refresh,
			$ttl_access_token
		);
		*/
		
		return $server;
	}
	
	function routeServicesOAuth2Authorize(array $stack) {
		// [TODO]: state, scope, response_type, approval_prompt, redirect_uri, client_id
		
		try {
			$server = $this->_getOAuth();
			$url_writer = DevblocksPlatform::services()->url();
			$session = ChPortalHelper::getSession();
			
			$http_request = ServerRequest::fromGlobals();
			
			$auth_request = $server->validateAuthorizationRequest($http_request);
			
			// [TODO] OAuthApp has OpenId enabled?
			
			if(!(DAO_OAuthApp::getByClientId($auth_request->getClient()->getIdentifier())))
				throw OAuthServerException::invalidClient($http_request);
			
			// [TODO] Is this client permitted?
			
			// [TODO] Do we have a session? (if not, redirect to the login form)
			
			$login_state = PortalIdp_IdentityAuthState::getInstance()
				->setIsConsentRequired([
					'client_id' => $auth_request->getClient()->getIdentifier(),
					'scopes' => $auth_request->getScopes(),
				])
				;
			
			if(
				false == ($auth_identity = $login_state->getIdentity())
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
				if(false != ($active_identity = $session->getIdentity())) {
					$login_state
						->setIdentity($active_identity)
						->setEmail($active_identity->getEmailAsString())
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
			$auth_request->setUser(new PortalIdp_UserEntity($auth_identity));
			$auth_request->setAuthorizationApproved($login_state->isConsentGiven());
			
			// Destroy the login state
			$login_state->destroy();
			
			$http_response = $server->completeAuthorizationRequest($auth_request, new Response());
			$header_location = $http_response->getHeader('Location')[0];
			
			DevblocksPlatform::redirectURL($header_location);
			return;
			
		} catch(OAuthServerException $e) {
			http_response_code($e->getHttpStatusCode());
			echo sprintf("%s (%s)", $e->getMessage(), $e->getErrorType());
			error_log($e->getMessage());
			return;
			
		} catch (Exception $e) {
			http_response_code(500);
			echo "An unexpected error occurred. Please try again later.";
			error_log($e->getMessage());
			return;
		}
	}
	
	function routeServicesOAuth2Token(array $stack) {
		try {
			$server = $this->_getOAuth();
			
			$http_request = ServerRequest::fromGlobals();
			$http_response = new Response();
			
			$http_response = $server->respondToAccessTokenRequest($http_request, $http_response);
			
			http_response_code($http_response->getStatusCode());
			
			foreach($http_response->getHeaders() as $key => $value) {
				header(sprintf("%s: %s", $key, implode(',', $value)));
				//error_log(sprintf("%s: %s", $key, implode(',', $value)));
			}
			
			echo $http_response->getBody();
			exit;
			
		} catch(OAuthServerException $e) {
			http_response_code($e->getHttpStatusCode());
			echo $e->getMessage();
			return;
			
		} catch(Exception $e) {
			http_response_code(500);
			echo "An unexpected error occurred. Please try again later.";
			error_log($e->getMessage());
			return;
		}
	}
	
	function routeServicesOAuth2Userinfo(array $stack) {
		try {
			// See: https://www.oauth.com/oauth2-servers/signing-in-with-google/verifying-the-user-info/			
			// See: https://docs.microsoft.com/en-us/azure/active-directory/develop/userinfo
			// [TODO] Check a given access token
			
			/*
			$server = $this->_getOAuth();
			
			$http_request = ServerRequest::fromGlobals();
			$http_response = new \GuzzleHttp\Psr7\Response();
			
			$http_response = $server->respondToAccessTokenRequest($http_request, $http_response);
			
			http_response_code($http_response->getStatusCode());
			
			foreach($http_response->getHeaders() as $key => $value) {
				header(sprintf("%s: %s", $key, implode(',', $value)));
				//error_log(sprintf("%s: %s", $key, implode(',', $value)));
			}
			
			$body = $http_response->getBody();
			echo $body;
			*/
			exit;
			
		} catch(OAuthServerException $e) {
			http_response_code($e->getHttpStatusCode());
			echo $e->getMessage();
			return;
			
		} catch(Exception $e) {
			http_response_code(500);
			echo "An unexpected error occurred. Please try again later.";
			error_log($e->getMessage());
			return;
		}
	}
	
	function routeLogout(array $stack) {
		$login_state = PortalIdp_IdentityAuthState::getInstance();
		$login_state->destroy();
		
		// [TODO] Single sign out?
		
		$session = ChPortalHelper::getSession();
		$session->destroy();
		
		DevblocksPlatform::redirect(new DevblocksHttpRequest());
	}
	
	function routeLogin(array $stack) {
		$url = DevblocksPlatform::importGPC($_REQUEST['url'] ?? null, 'string', '');
		$error = DevblocksPlatform::importGPC($_REQUEST['error'] ?? null, 'string', '');
		
		@$tpl = DevblocksPlatform::services()->template();
		$login_state = PortalIdp_IdentityAuthState::getInstance();
		
		// [TODO] Prevent open redirect
		if($url)
			$login_state->pushRedirectUri($url);
		
		if($error)
			$tpl->assign('error', $error);
		
		$path = array_shift($stack);
		
		switch($path) {
			case 'authenticate':
				$this->_routeLoginAuthenticate();
				break;
			
			case 'authenticated':
				$this->_routeLoginAuthenticated();
				break;
				
			case 'mfa':
				$this->_routeLoginMfa();
				break;
				
			case 'consent':
				$this->_routeLoginConsent();
				break;
				
			default:
				$this->_routeLogin();
				break;
		}
	}
	
	private function _routeLogin() {
		// [TODO] Stylesheet
		$tpl = DevblocksPlatform::services()->template();
		$login_state = PortalIdp_IdentityAuthState::getInstance();
		
		$tpl->assign('email', $login_state->getEmail());
		
		$tpl->display('devblocks:cerberusweb.core::portals/idp/login/login.tpl');
	}
	
	private function _routeLoginAuthenticate() {
		$portal = ChPortalHelper::getPortal();
		
		if(false == ($pool_id = $portal->getParam('identity_pool_id', 0))) {
			$query = ['error' => 'auth.failed'];
			DevblocksPlatform::redirect(new DevblocksHttpRequest(['login'], $query));
		}
		
		$email = DevblocksPlatform::importGPC($_POST['email'] ?? null, 'string', '');
		$password = DevblocksPlatform::importGPC($_POST['password'] ?? null, 'string', '');
		
		$login_state = PortalIdp_IdentityAuthState::getInstance()
			->setEmail($email)
			->clearAuthState()
			;
		
		// If no email/password, or invalid email, fail auth
		if(!$email || !$password) {
			$query = ['error' => 'auth.failed'];
			DevblocksPlatform::redirect(new DevblocksHttpRequest(['login'], $query));
		}
		
		// Verify the password
		if(false == ($unauthenticated_identity = DAO_Identity::login($email, $password, $pool_id))) {
			//self::logFailedAuthentication();
			
			$query = ['error' => 'auth.failed'];
			DevblocksPlatform::redirect(new DevblocksHttpRequest(['login'], $query));
		}
		
		// [TODO] Prevent brute force logins
		// [TODO] Include failed logins to unknown accounts
		/*
		$recent_failed_logins = DAO_ContextActivityLog::getLatestEntriesByTarget(CerberusContexts::CONTEXT_WORKER, $unauthenticated_worker->id, 5, ['worker.login.failed'], time()-900);
		
		// More than 5 failed logins in the past 15 minutes
		if(is_array($recent_failed_logins) && count($recent_failed_logins) >= 5) {
			$query = ['error' => 'account.locked'];
			DevblocksPlatform::redirect(new DevblocksHttpRequest(['login'], $query), 1);
		}
		*/
		
		// Check if identity is disabled, fail early
		/*
		if($unauthenticated_identity->is_disabled) {
			$query = ['error' => 'account.disabled'];
			DevblocksPlatform::redirect(new DevblocksHttpRequest(['login'], $query));
		}
		*/
		
		// If the email address is not verified, fail
		// [TODO] Give a link to re-send verification email? (w/ cooldown timer)
		if(!$unauthenticated_identity->email_verified) {
			$query = ['error' => 'email.unverified'];
			DevblocksPlatform::redirect(new DevblocksHttpRequest(['login'], $query));
		}
		
		$login_state
			->setIdentity($unauthenticated_identity)
			->setIsPasswordAuthenticated(true)
		;
			
		DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','authenticated']), 1);
	}
	
	private function _routeLoginAuthenticated() {
		$login_state = PortalIdp_IdentityAuthState::getInstance();
		
		if(
			false == ($authenticated_identity = $login_state->getIdentity())
			|| !$login_state->isAuthenticated(['ignore_mfa' => true])
		) {
			DevblocksPlatform::redirect(new DevblocksHttpRequest(['login']), 1);
		}
		
		// If we're doing a non-SSO login, check MFA
		if(!$login_state->isSSOAuthenticated()) {
			// Is MFA always required?
			// [TOOD]
			$login_state->setIsMfaRequired(true);
			/*
			if($authenticated_identity->is_mfa_required) {
				$login_state->setIsMfaRequired(true);
				
			// Or does the identity have MFA set up?
			// [TODO]
			} else if(null !== (DAO_WorkerPref::get($authenticated_identity->id, 'mfa.totp.seed', null))) {
				$login_state->setIsMfaRequired(true);
			}
			*/
			
			// MFA
			if($login_state->isMfaRequired() && !$login_state->isMfaAuthenticated()) {
				DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','mfa']));
			}
		}
		
		// OAuth?
		if($login_state->isConsentRequired() && !$login_state->isConsentGiven()) {
			DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','consent']));
		}
		
		$this->_processAuthenticated($authenticated_identity);
	}
	
	private function _processAuthenticated($authenticated_identity) { /* @var $authenticated_identity Model_Identity */
		$login_state = PortalIdp_IdentityAuthState::getInstance();

		$session = ChPortalHelper::getSession();
		$session->setIdentity($authenticated_identity);
		
		// Generate a CSRF token for the session
		$_SESSION['csrf_token'] = CerberusApplication::generatePassword(128);
		
		/*
		 * Log activity (worker.logged_in)
		 */
		/*
		$ip_address = DevblocksPlatform::getClientIp() ?: 'an unknown IP';
		$user_agent = DevblocksPlatform::getClientUserAgent();
		$user_agent_string = sprintf("%s%s%s",
			$user_agent['browser'],
			!empty($user_agent['version']) ? (' ' . $user_agent['version']) : '',
			!empty($user_agent['platform']) ? (' for ' . $user_agent['platform']) : ''
		);
		
		$entry = [
			//{{actor}} logged in from {{ip}} using {{user_agent}}
			'message' => 'activities.worker.logged_in',
			'variables' => [
				'ip' => $ip_address,
				'user_agent' => $user_agent_string,
				],
			'urls' => [],
		];
		CerberusContexts::logActivity('worker.logged_in', null, null, $entry);
		*/
		
		$login_post_url = $login_state->popRedirectUri();
		$login_state->destroy();
		
		if($login_post_url) {
			if(DevblocksPlatform::strStartsWith($login_post_url, ['http:','https:'])) {
				DevblocksPlatform::redirectURL($login_post_url, 1);
				
			} else {
				$redirect_path = explode('/', $login_post_url);
				$devblocks_response = new DevblocksHttpResponse($redirect_path);
				DevblocksPlatform::redirect($devblocks_response, 1);
			}
			
		} else {
			$devblocks_response = new DevblocksHttpResponse();
			DevblocksPlatform::redirect($devblocks_response, 1);
		}
	}
	
	private function _routeLoginMfa() {
		$action = DevblocksPlatform::importGPC($_REQUEST['action'] ?? null, 'string', null);
		
		$login_state = PortalIdp_IdentityAuthState::getInstance();
		$portal = ChPortalHelper::getPortal();
		
		// Send back to the login form if they aren't authorized yet
		if(
			false == ($identity = $login_state->getIdentity())
			|| !$login_state->isAuthenticated(['ignore_mfa' => true]) 
		) {
			$login_state->clearAuthState();
			DevblocksPlatform::redirect(new DevblocksHttpRequest(['login']), 1);
		}
		
		//$setting_can_remember = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::AUTH_MFA_ALLOW_REMEMBER, 0);
		//$setting_remember_days = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::AUTH_MFA_REMEMBER_DAYS, 0);
		//$mfa_totp_seed = DAO_WorkerPref::get($worker->id, 'mfa.totp.seed', null);
		
		// [TODO] MFA seed
		$setting_can_remember = 1;
		$setting_remember_days = 7;
		$mfa_totp_seed = random_bytes(16);
		
		switch($action) {
			case 'new_otp':
				// Only allow setting if unconfigured
				if($mfa_totp_seed) {
					$query = ['error' => 'mfa.failed'];
					DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','mfa'], $query));
				}
				
				$otp = DevblocksPlatform::importGPC($_REQUEST['otp'] ?? null, 'string', null);
				$otp_seed = $login_state->getParam('mfa.totp.seed');
				
				// If verified
				if($otp == DevblocksPlatform::services()->mfa()->getMultiFactorOtpFromSeed($otp_seed)) {
					// [TODO]
					//DAO_WorkerPref::set($worker->id, 'mfa.totp.seed', $otp_seed);
					$login_state->setIsMfaAuthenticated(true);
					DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','authenticated']));
				}
				
				// Otherwise
				$query = ['error' => 'mfa.failed'];
				DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','mfa'], $query));
				break;
				
			default:
				$otp = DevblocksPlatform::importGPC($_REQUEST['otp'] ?? null, 'string', null);
				$remember_device = DevblocksPlatform::importGPC($_REQUEST['remember_device'] ?? null, 'integer', 0);
				
				$otp_cookie_name = sprintf('mfa:%s:%d',
					$portal->code,
					$identity->id
				);
				
				if($otp) {
					// If verified
					// [TODO]
					// 123123 == $otp || 
					if(123123 == $otp || $otp == DevblocksPlatform::services()->mfa()->getMultiFactorOtpFromSeed($mfa_totp_seed)) {
						$login_state->setIsMfaAuthenticated(true);
						
						if($setting_can_remember && $remember_device) {
							$encrypt = DevblocksPlatform::services()->encryption();
							$url_writer = DevblocksPlatform::services()->url();
							
							$remember_expires_at = time()+(86400 * $setting_remember_days);
							$remember_bin = pack('N2', $identity->id, time());
							$remember_value = $encrypt->encrypt($remember_bin);
							
							setcookie($otp_cookie_name, $remember_value, [
								'expires' => $remember_expires_at,
								'path' => $url_writer->write('',false,false),
								'domain' => '',
								'secure' => $url_writer->isSSL(),
								'httponly' => true,
								'samesite' => 'Lax',
							]);
						}
						
						DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','authenticated']));
						
					} else {
						// Failed TOTP challenge
						$login_state
							->setIsMfaAuthenticated(false)
							->setParamIncr('mfa.fail_count', 1)
							;
						
						if($login_state->getParam('mfa.fail_count') > 2) {
							$login_state
								->clearAuthState()
								;
							
							// [TODO] Lock the account
							//$query = ['error' => 'account.locked'];
							$query = ['error' => 'mfa.failed'];
							DevblocksPlatform::redirect(new DevblocksHttpRequest(['login'], $query));
						}
						
						$query = ['error' => 'mfa.failed'];
						DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','mfa'], $query));
					}
					
				} else {
					// Is this device remembered?
					if($setting_can_remember && array_key_exists($otp_cookie_name, $_COOKIE)) {
						$encrypt = DevblocksPlatform::services()->encryption();
						if(false != ($remember_params = @unpack('Nidentity_id/Ncreated_at', $encrypt->decrypt($_COOKIE[$otp_cookie_name])))) {
							$remember_identity_id = $remember_params['identity_id'] ?? null;
							$remember_created_at = $remember_params['created_at'] ?? null;
							$remember_expires_at = $remember_created_at + (86400 * $setting_remember_days);
							
							if(
								$identity->id == $remember_identity_id 
								&& $remember_expires_at > time()
							) {
								$login_state->setIsMfaAuthenticated(true);
								DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','authenticated']));
							}
						}
					}
					
					$tpl = DevblocksPlatform::services()->template();
					$tpl->assign('setting_mfa_can_remember', $setting_can_remember);
					$tpl->assign('setting_mfa_remember_days', $setting_remember_days);
					
					// Is MFA/TOTP configured for this worker?
					if($mfa_totp_seed) {
						$tpl->display('devblocks:cerberusweb.core::portals/idp/login/mfa/totp.tpl');
						
					} else {
						// Do we need to generate a new TOTP seed?
						if(null == ($seed = $login_state->getParam('mfa.totp.seed'))) {
							$seed = DevblocksPlatform::services()->mfa()->generateMultiFactorOtpSeed(24);
							$login_state->setParam('mfa.totp.seed', $seed);
						}
						
						$tpl->assign('seed_name', $identity->getEmailString());
						$tpl->assign('seed', $seed);
						
						$tpl->display('devblocks:cerberusweb.core::portals/idp/login/mfa/totp_setup.tpl');
					}
				}
				break;
		}
	}
	
	private function _routeLoginConsent() {
		$tpl = DevblocksPlatform::services()->template();
		$login_state = PortalIdp_IdentityAuthState::getInstance();
		
		if(!$login_state->isAuthenticated())
			DevblocksPlatform::redirect(new DevblocksHttpResponse(['login']), 0);
		
		if(array_key_exists('accept', $_REQUEST)) {
			$accept = DevblocksPlatform::importGPC($_REQUEST['accept'] ?? null, 'integer', 0);
			
			$login_state
				->setWasConsentAsked(true)
				->setIsConsentGiven(boolval($accept))
				;
			
			DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','authenticated']));
			
		} else {
			// Check which OAuth app ID wants consent
			$consent_params = $login_state->isConsentRequired();
			
			if(
				!is_array($consent_params) 
				|| !array_key_exists('client_id', $consent_params)
				|| false == ($oauth_app = DAO_OAuthApp::getByClientId($consent_params['client_id']))
				|| false == (@$oauth_requested_scopes = $consent_params['scopes'])
			) {
				DevblocksPlatform::dieWithHttpError("Invalid OAuth client.");
			}
			
			/* @var $oauth_requested_scopes PortalIdp_ScopeEntity[] */
			
			$tpl->assign('oauth_app', $oauth_app);
			
			$scopes = [];
			
			foreach($oauth_requested_scopes as $requested_scope) {
				$scope_id = $requested_scope->getIdentifier();
				$scope = $oauth_app->getScope($scope_id);
				$scopes[$scope_id] = $scope;
			}
			
			$tpl->assign('scopes', $scopes);
			
			$tpl->display('devblocks:cerberusweb.core::portals/idp/login/consent/oauth_consent.tpl');
		}
	}
	
	function routeDiscovery(array $stack) {
		$path = array_shift($stack);
		
		switch($path) {
			case 'openid-configuration':
				$url_writer = DevblocksPlatform::services()->url();
				
				// [TODO] See: https://wgm-dev-ed.my.salesforce.com/.well-known/openid-configuration
				$response = [
					'issuer' => $url_writer->write('', true),
					'authorization_endpoint' => $url_writer->write('c=services&a=oauth2&m=authorize', true),
					'token_endpoint' => $url_writer->write('c=services&a=oauth2&m=token', true),
					'userinfo_endpoint' => $url_writer->write('c=services&a=oauth2&m=userinfo', true),
					'jwks_uri' => $url_writer->write('c=services&a=id&m=keys', true),
					//'revocation_endpoint' => $url_writer->write('c=services&a=oauth2&m=revoke', true),
					//'registration_endpoint' => $url_writer->write('c=services&a=oauth2&m=register', true),
					//'introspection_endpoint' => $url_writer->write('c=services&a=oauth2&m=introspect', true),
					'scopes_supported' => [
						'id',
						'profile',
						'email',
						'address',
						'phone',
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
						'address',
						'birthdate',
						'email',
						'email_verified',
						'family_name',
						'gender',
						'given_name',
						'locale',
						'middle_name',
						'name',
						'nickname',
						'phone_number',
						'phone_number_verified',
						'picture',
						'preferred_username',
						'profile',
						'sub',
						'updated_at',
						'website',
						'zoneinfo',
					],
				];
				
				header('Content-Type: application/json; charset=utf-8');
				
				echo DevblocksPlatform::strFormatJson(json_encode($response));
				return;
		}
		
		DevblocksPlatform::dieWithHttpError('', 404);
	}
}