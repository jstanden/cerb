<?php
use GuzzleHttp\Psr7\Request;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Lcobucci\JWT\Validation\Validator;
use League\OAuth2\Client\Grant\AbstractGrant;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Math\BigInteger;

class InvalidTokenException extends \Exception {
}

class AccessToken extends \League\OAuth2\Client\Token\AccessToken {
	protected Token $idToken;

	public function __construct($options = []) {
		parent::__construct($options);

		if(array_key_exists('id_token', $this->values)) {
			$parser = new Parser(new JoseEncoder());
			$this->idToken = $parser->parse($this->values['id_token']);
			unset($this->values['id_token']);
		}
	}
	
	public function getIdToken() : Token {
		return $this->idToken;
	}
	
	public function jsonSerialize(): array {
		$parameters = parent::jsonSerialize();
		
		if ($this->idToken) {
			$parameters['id_token'] = $this->idToken->toString();
		}
		
		return $parameters;
	}
}

class GenericOpenIDConnectProvider extends GenericProvider {
	protected $idTokenIssuer;
	protected $urlJwks;

	/**
	 * @param array $options
	 * @param array $collaborators
	 */
	public function __construct(array $options = [], array $collaborators = []) {
		if (empty($options['scopes'])) {
			$options['scopes'] = [];
		} else if (!is_array($options['scopes'])) {
			$options['scopes'] = [$options['scopes']];
		}

		if(!in_array('openid', $options['scopes'])) {
			array_push($options['scopes'], 'openid');
		}
		
		if(defined('DEVBLOCKS_HTTP_PROXY') && DEVBLOCKS_HTTP_PROXY)
			$options['proxy'] = DEVBLOCKS_HTTP_PROXY;
		
		parent::__construct($options, $collaborators);
	}

	/**
	 * Returns all options that are required.
	 *
	 * @return array
	 */
	protected function getRequiredOptions() {
		$options = parent::getRequiredOptions();
		$options[] = 'idTokenIssuer';
		$options[] = 'urlJwks';

		return $options;
	}
	
	public function fetchJwks($url) {
		$http = DevblocksPlatform::services()->http();
		$cache = DevblocksPlatform::services()->cache();
		
		$cache_key = sprintf('jwks:%s', $url);
		
		if(!($json = $cache->load($cache_key))) {
			$request = new Request('GET', $url);
			$request_options = [];
			$error = null;
			
			if(!($response = $http->sendRequest($request, $request_options, $error))) {
				error_log($error);
				return null;
			}
			
			if(!($json = $http->getResponseAsJson($response, $error))) {
				error_log($error);
				return null;
			}
			
			$cache->save($json, $cache_key, [], 3600);
		}
		
		return $json;
	}
	
	public function getAllPublicKeys() {
		$jwks = $this->fetchJwks($this->urlJwks);

		if(!is_array($jwks) || !array_key_exists('keys', $jwks) || empty($jwks['keys']))
			return [];

		$public_keys = [];

		foreach($jwks['keys'] as $jwk) {
			$public_keys[] = $this->convertJwkToRsa($jwk);
		}

		return $public_keys;
	}
	
	public function getPublicKeyByJwkId($kid) {
		$jwks = $this->fetchJwks($this->urlJwks);
		
		if(!is_array($jwks) || !array_key_exists('keys', $jwks))
			return null;
		
		foreach($jwks['keys'] as $jwk) {
			if($jwk['kid'] == $kid)
				return $this->convertJwkToRsa($jwk);
		}
		
		return null;
	}
	
	public function convertJwkToRsa($jwk) {
		return PublicKeyLoader::load([
			'e' => new BigInteger(base64_decode($jwk['e']), 256),
			'n' => new BigInteger(DevblocksPlatform::services()->string()->base64UrlDecode($jwk['n']), 256)
		]);
	}
	
	/**
	 * Requests an access token using a specified grant and option set.
	 *
	 * @param  mixed $grant
	 * @param  array $options
	 * @return AccessToken
	 * @throws InvalidTokenException|IdentityProviderException
	 */
	public function getAccessToken($grant, array $options = []) {
		$accessToken = parent::getAccessToken($grant, $options); /* @var $accessToken AccessToken */
		$token = $accessToken->getIdToken(); /* @var $token Token */
		
		// id_token is empty.
		if(!($token instanceof Token)) {
			throw new InvalidTokenException('Expected an id_token but did not receive one from the authorization server.');
		}
		
		$validation = new Validator();

		// Not all ID tokens provide a 'kid' (Key ID) header
		if($token->headers()->has('kid')) {
			$kid = $token->headers()->get('kid');

			if(!($public_key = $this->getPublicKeyByJwkId($kid)))
				throw new InvalidTokenException('Received an invalid key ID (kid) header from authorization server.');

		} else {
			// Without a `kid` header, try every JWK in the JWKS until one verifies
			// the signature. This stays compatible with IdPs that don't emit `kid`
			// during key rotation, where the first JWK isn't always the signer.
			$public_key = null;

			foreach($this->getAllPublicKeys() as $candidate_key) {
				$candidate_constraint = new Lcobucci\JWT\Validation\Constraint\SignedWith(
					new Sha256(), InMemory::plainText($candidate_key)
				);

				if($validation->validate($token, $candidate_constraint)) {
					$public_key = $candidate_key;
					break;
				}
			}

			if(null === $public_key)
				throw new InvalidTokenException('The id_token signature did not validate against any JWKS key.');
		}

		$constraints = [
			new Lcobucci\JWT\Validation\Constraint\SignedWith(new Sha256(), InMemory::plainText($public_key)),
			new Lcobucci\JWT\Validation\Constraint\LooseValidAt(new SystemClock(new DateTimeZone(\date_default_timezone_get()))),
			new Lcobucci\JWT\Validation\Constraint\IssuedBy($this->getIdTokenIssuer()),
			new Lcobucci\JWT\Validation\Constraint\PermittedFor($this->clientId)
		];
		
		if(!$validation->validate($token, ...$constraints)) {
			// Get the exact violations
			try {
				$validation->assert($token, ...$constraints);
			} catch(RequiredConstraintsViolated $e) {
				DevblocksPlatform::logException($e);
			}
			throw new InvalidTokenException('The id_token did not pass validation.');
		}
		
		return $accessToken;
	}

	/**
	 * Overload parent as OpenID Connect specification states scopes shall be separated by spaces
	 *
	 * @return string
	 */
	protected function getScopeSeparator() {
		return ' ';
	}

	/**
	 * Get the issuer of the OpenID Connect id_token
	 *
	 * @return string
	 */
	protected function getIdTokenIssuer() {
		return $this->idTokenIssuer;
	}


	/**
	 * Creates an access token from a response.
	 *
	 * The grant that was used to fetch the response can be used to provide
	 * additional context.
	 *
	 * @param  array $response
	 * @param  AbstractGrant $grant
	 * @return AccessToken
	 */
	protected function createAccessToken(array $response, AbstractGrant $grant) {
		return new AccessToken($response);
	}
};

class ServiceProvider_OpenIdConnect extends Extension_ConnectedServiceProvider {
	const ID = 'cerb.service.provider.oidc';
	
	function handleActionForService(string $action) {
		switch($action) {
			case 'runDiscovery':
				return $this->_connectedServiceAction_runDiscovery();
		}
		return false;
	}
	
	public function renderConfigForm(Model_ConnectedService $service) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$service->extension_id = self::ID;
		$tpl->assign('service', $service);
		
		$params = $service->decryptParams($active_worker);
		$tpl->assign('params', $params);
		
		$tpl->display('devblocks:cerberusweb.core::internal/connected_service/providers/oidc/config_service.tpl');
	}

	public function saveConfigForm(Model_ConnectedService $service, array &$params, &$error=null) {
		$edit_params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', []);
		
		$validation = DevblocksPlatform::services()->validation();
		
		$validation
			->addField('client_id', 'Client ID')
			->string()
			->setRequired(true)
			;
		$validation
			->addField('client_secret', 'Client Secret')
			->string()
			->setRequired(true)
			;
		$validation
			->addField('authorization_url', 'Authorization URL')
			->url()
			->setRequired(true)
			;
		$validation
			->addField('issuer', 'Issuer')
			->url()
			->setRequired(true)
			;
		$validation
			->addField('access_token_url', 'Access Token URL')
			->url()
			->setRequired(true)
			;
		$validation
			->addField('userinfo_url', 'User Info URL')
			->url()
			->setRequired(true)
			;
		$validation
			->addField('jwks_url', 'JWKS URL')
			->url()
			->setRequired(true)
			;
		$validation
			->addField('scope', 'Scope')
			->string()
			->setMaxLength(4096)
			->setRequired(true)
			;
		
		if(!$validation->validateAll($edit_params, $error))
			return false;
		
		foreach($edit_params as $k => $v)
			$params[$k] = $v;
		
		return true;
	}
	
	private function _connectedServiceAction_runDiscovery() {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		$issuer = DevblocksPlatform::importGPC($_POST['issuer'] ?? null, 'string', '');
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		try {
			if(empty($issuer)) {
				throw new Exception_DevblocksAjaxValidationError("Issuer can't be empty");
			}
			
			$request = new Request('GET', rtrim($issuer,'/') . '/.well-known/openid-configuration');
			$request_options = [];
			$error = null;
			
			if(!($response = DevblocksPlatform::services()->http()->sendRequest($request, $request_options, $error))) {
				throw new Exception_DevblocksAjaxValidationError($error);
			}
			
			if(!($json = DevblocksPlatform::services()->http()->getResponseAsJson($response, $error))) {
				throw new Exception_DevblocksAjaxValidationError($error);
			}
			
			echo json_encode($json);
			
		} catch (Exception_DevblocksAjaxValidationError $e) {
			$json = [
				'error' => $e->getMessage(),
			];
			echo json_encode($json);
		}
	}
	
	// If not instantiable, don't need this
	public function renderAccountConfigForm(Model_ConnectedService $service, Model_ConnectedAccount $account) {
	}

	// If not instantiable, don't need this
	public function saveAccountConfigForm(Model_ConnectedService $service, Model_ConnectedAccount $account, array &$params, &$error = null) {
	}
	
	/**
	 * 
	 * @param Model_ConnectedService $service
	 * @return GenericOpenIDConnectProvider|NULL
	 */
	private function _getProvider(Model_ConnectedService $service) {
		if(!($service_params = $service->decryptParams()))
			return null;
		
		$url_writer = DevblocksPlatform::services()->url();
		
		return new GenericOpenIDConnectProvider([
			'clientId' => $service_params['client_id'],
			'clientSecret' => $service_params['client_secret'],
			'idTokenIssuer' => $service_params['issuer'],
			'redirectUri' => $url_writer->write(sprintf('c=sso&provider=%s', $service->uri), true),
			'scopes' => $service_params['scope'] ?? '',
			'urlAuthorize' => $service_params['authorization_url'],
			'urlAccessToken' => $service_params['access_token_url'],
			'urlResourceOwnerDetails' => $service_params['userinfo_url'],
			'urlJwks' => $service_params['jwks_url'],
		]);
	}
	
	public function sso(Model_ConnectedService $service, array $path) {
		$login_state = CerbLoginWorkerAuthState::getInstance()
			->clearAuthState()
			;
		
		if(!($provider = $this->_getProvider($service)))
			return;
		
		if(!array_key_exists('code', $_GET)) {
			// Send to the authentication URL
			$nonce = DevblocksPlatform::services()->string()->base64UrlEncode(random_bytes(16));
			$redirectUrl = $provider->getAuthorizationUrl(['nonce' => $nonce]);
			$_SESSION['oidc.state'] = $provider->getState();
			$_SESSION['oidc.nonce'] = $nonce;
			DevblocksPlatform::redirectURL($redirectUrl);
		}

		// Verify the OAuth `state` parameter to protect the callback from CSRF
		$expected_state = $_SESSION['oidc.state'] ?? null;
		$given_state = $_GET['state'] ?? null;
		unset($_SESSION['oidc.state']);

		if(!$expected_state || !$given_state || !hash_equals($expected_state, $given_state)) {
			DevblocksPlatform::logError("[OIDC] callback state mismatch");
			$query = ['error' => 'auth.failed'];
			DevblocksPlatform::redirect(new DevblocksHttpResponse(['login'], $query), 0);
		}

		// Single-use the request `nonce` so we can compare it to the ID token claim
		$expected_nonce = $_SESSION['oidc.nonce'] ?? null;
		unset($_SESSION['oidc.nonce']);

		try {
			$token = $provider->getAccessToken('authorization_code', [
				'code' => $_GET['code']
			]);

			$id_token = $token->getIdToken();

			$returned_nonce = $id_token->claims()->get('nonce');
			if(!$expected_nonce || !$returned_nonce || !hash_equals($expected_nonce, $returned_nonce))
				throw new Exception_DevblocksValidationError("The ID token nonce did not match the request.");

			if(!($email = $id_token->claims()->get('email')))
				throw new Exception_DevblocksValidationError("The ID token does not have an 'email' claim.");

			// If the IdP reports the verification status, require it to be verified.
			// IdPs that don't emit the claim continue to work as before.
			if($id_token->claims()->has('email_verified') && true !== $id_token->claims()->get('email_verified'))
				throw new Exception_DevblocksValidationError("The ID token reports the 'email' claim as unverified.");

			if(!($worker = DAO_Worker::getByEmail($email)))
				throw new Exception_DevblocksValidationError("The ID token 'email' claim does not match a worker account.");
			
			$login_state
				->clearAuthState()
				->setWorker($worker)
				->setEmail($worker->getEmailString())
				->setIsSSOAuthenticated(true)
				->setIsMfaRequired(false)
			;
			
			DevblocksPlatform::redirect(new DevblocksHttpRequest(['login','authenticated']), 0);
			
		} catch(Exception $e) {
			error_log($e->getMessage());
			$query = ['error' => 'auth.failed'];
			DevblocksPlatform::redirect(new DevblocksHttpResponse(['login'], $query), 0);
		}
	}
	
	public function authenticateHttpRequest(Model_ConnectedAccount $account, Psr\Http\Message\RequestInterface &$request, array &$options = []): bool {
		return true;
	}
};