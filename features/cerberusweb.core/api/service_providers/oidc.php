<?php
use GuzzleHttp\Psr7\Request;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Validation\Validator;
use League\OAuth2\Client\Grant\AbstractGrant;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;
use phpseclib\Crypt\RSA;
use phpseclib\Math\BigInteger;

class InvalidTokenException extends \Exception {
}

class AccessToken extends \League\OAuth2\Client\Token\AccessToken {
	protected Token $idToken;

	public function __construct($options = []) {
		parent::__construct($options);

		$config = Configuration::forUnsecuredSigner();
		
		if(array_key_exists('id_token', $this->values)) {
			$this->idToken = $config->parser()->parse($this->values['id_token']);
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
		
		if(false == ($json = $cache->load($cache_key))) {
			$request = new Request('GET', $url);
			$request_options = [];
			$error = null;
			
			if(false == ($response = $http->sendRequest($request, $request_options, $error))) {
				error_log($error);
				return null;
			}
			
			if(false == ($json = $http->getResponseAsJson($response, $error))) {
				error_log($error);
				return null;
			}
			
			$cache->save($json, $cache_key, [], 3600);
		}
		
		return $json;
	}
	
	public function getPublicKeyByDefault() {
		$jwks = $this->fetchJwks($this->urlJwks);
		
		if(!is_array($jwks) || !array_key_exists('keys', $jwks) || empty($jwks['keys']))
			return null;
		
		$jwk = current($jwks['keys']);
		
		return $this->convertJwkToRsa($jwk);
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
		$rsa = new RSA();
		
		$rsa->loadKey(
			[
				'e' => new BigInteger(base64_decode($jwk['e']), 256),
				'n' => new BigInteger(DevblocksPlatform::services()->string()->base64UrlDecode($jwk['n']), 256)
			]
		);
		
		return $rsa->getPublicKey();
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
		
		// Not all ID tokens provide a 'kid' (Key ID) header
		if($token->headers()->has('kid')) {
			$kid = $token->headers()->get('kid');
			
			if(false == ($public_key = $this->getPublicKeyByJwkId($kid)))
				throw new InvalidTokenException('Received an invalid key ID (kid) header from authorization server.');
			
		} else {
			// Use the first key if one wasn't specified
			$public_key = $this->getPublicKeyByDefault();
		}

		$validation = new Validator();
		
		if(!$validation->validate(
			$token,
			new Lcobucci\JWT\Validation\Constraint\SignedWith(new Sha256(), InMemory::plainText($public_key)),
			new Lcobucci\JWT\Validation\Constraint\StrictValidAt(new SystemClock(new DateTimeZone(\date_default_timezone_get()))),
			new Lcobucci\JWT\Validation\Constraint\IssuedBy($this->getIdTokenIssuer()),
			new Lcobucci\JWT\Validation\Constraint\PermittedFor($this->clientId)
			)) {
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
		@$edit_params = DevblocksPlatform::importGPC($_POST['params'], 'array', []);
		
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
		
		if(false == $validation->validateAll($edit_params, $error))
			return false;
		
		foreach($edit_params as $k => $v)
			$params[$k] = $v;
		
		return true;
	}
	
	private function _connectedServiceAction_runDiscovery() {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		@$issuer = DevblocksPlatform::importGPC($_POST['issuer'], 'string', '');
		
		header('Content-Type: application/json; charset=utf-8');
		
		try {
			if(empty($issuer)) {
				throw new Exception_DevblocksAjaxValidationError("Issuer can't be empty");
			}
			
			$request = new Request('GET', rtrim($issuer,'/') . '/.well-known/openid-configuration');
			$request_options = [];
			$error = null;
			
			if(false == ($response = DevblocksPlatform::services()->http()->sendRequest($request, $request_options, $error))) {
				throw new Exception_DevblocksAjaxValidationError($error);
			}
			
			if(false == ($json = DevblocksPlatform::services()->http()->getResponseAsJson($response, $error))) {
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
		if(false == ($service_params = $service->decryptParams()))
			return null;
		
		$url_writer = DevblocksPlatform::services()->url();
		
		return new GenericOpenIDConnectProvider([
			'clientId' => $service_params['client_id'],
			'clientSecret' => $service_params['client_secret'],
			'idTokenIssuer' => $service_params['issuer'],
			'redirectUri' => $url_writer->write(sprintf('c=sso&provider=%s', $service->uri), true),
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
		
		$provider = $this->_getProvider($service);
		
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
			
			$id_token = $token->getIdToken();
			
			if(false == ($email = $id_token->claims()->get('email')))
				throw new Exception_DevblocksValidationError("The ID token does not have an 'email' claim.");
			
			if(false == ($worker = DAO_Worker::getByEmail($email)))
				throw new Exception_DevblocksValidationError("The ID token 'email' claim does not match a worker account.");
			
			$login_state
				->clearAuthState()
				->setWorker($worker)
				->setEmail($worker->getEmailString())
				->setIsSSOAuthenticated(true)
				->setIsMfaRequired(false)
			;
			
			DevblocksPlatform::redirect(new DevblocksHttpRequest(array('login','authenticated')), 0);
			
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