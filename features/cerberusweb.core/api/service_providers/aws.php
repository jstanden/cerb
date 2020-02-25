<?php
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;

class ServiceProvider_Aws extends Extension_ConnectedServiceProvider {
	const ID = 'cerb.service.provider.aws';
	
	function renderConfigForm(Model_ConnectedService $service) {
	}
	
	function saveConfigForm(Model_ConnectedService $service, array &$params, &$error=null) {
	}
	
	public function handleActionForService(string $action) {
		return false;
	}
	
	public function renderAccountConfigForm(Model_ConnectedService $service, Model_ConnectedAccount $account) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$params = $account->decryptParams($active_worker);
		$tpl->assign('params', $params);
		
		$tpl->display('devblocks:cerberusweb.core::internal/connected_service/providers/aws/config_account.tpl');
	}

	public function saveAccountConfigForm(Model_ConnectedService $service, Model_ConnectedAccount $account, array &$params, &$error = null) {
		@$edit_params = DevblocksPlatform::importGPC($_POST['params'], 'array', []);
		
		$validation = DevblocksPlatform::services()->validation();
	
		$validation
			->addField('access_key','Access Key')
			->string()
			->setRequired(true)
			;
		$validation
			->addField('secret_key','Secret Key')
			->string()
			->setRequired(true)
			;
		
		if(false == $validation->validateAll($edit_params, $error))
			return false;
		
		foreach($edit_params as $k => $v)
			$params[$k] = $v;
		
		return true;
	}
	
	/**
	 * 
	 * @param Model_ConnectedAccount $account
	 * @param Psr\Http\Message\RequestInterface $request
	 * @return array|false
	 */
	function _generateRequestSignature(Model_ConnectedAccount $account, Psr\Http\Message\RequestInterface $request) {
		$credentials = $account->decryptParams();
		
		if(
			!isset($credentials['access_key'])
			|| !isset($credentials['secret_key'])
			)
			return false;
		
		if($request->hasHeader('x-amz-date')) {
			$date_iso_8601 = $request->getHeaderLine('x-amz-date');
		} else {
			$date_iso_8601 = gmdate('Ymd\THis\Z');
		}
		
		$host = $request->getUri()->getHost();

		// Derive service + region from URL
		$service = $region = null;
		if(!$this->_getServiceRegionFromHost($host, $service, $region))
			return false;
		
		$canonical_path = $this->_createCanonicalPath($request->getUri()->getPath());
		$canonical_query = $this->_createCanonicalQueryString($request->getUri()->getQuery());
		$canonical_headers = $this->_createCanonicalHeaders($request->getHeaders());
		$signed_headers = $this->_createSignedHeaders($request->getHeaders());
		
		$canonical_string = 
			DevblocksPlatform::strUpper($request->getMethod()) . "\n" .
			$canonical_path . "\n" .
			$canonical_query . "\n" .
			$canonical_headers . "\n" .
			$signed_headers . "\n" .
			DevblocksPlatform::strLower(hash('sha256', $request->getBody()->getContents()))
			;
		
		$credential_scope = sprintf("%s/%s/%s/aws4_request",
			gmdate("Ymd"),
			$region,
			$service
		);
		
		$string_to_sign = 
			'AWS4-HMAC-SHA256' . "\n" .
			$date_iso_8601 . "\n" .
			$credential_scope . "\n" .
			DevblocksPlatform::strLower(hash('sha256', $canonical_string))
			;
		
		$secret = $credentials['secret_key'];
		$hash_date = hash_hmac('sha256', gmdate('Ymd'), 'AWS4' . $secret, true);
		$hash_region = hash_hmac('sha256', $region, $hash_date, true);
		$hash_service = hash_hmac('sha256', $service, $hash_region, true);
		$hash_signing = hash_hmac('sha256', 'aws4_request', $hash_service, true);
		
		$signature = hash_hmac('sha256', $string_to_sign, $hash_signing, false);
		
		$auth_header = sprintf('%s Credential=%s/%s, SignedHeaders=%s, Signature=%s',
			'AWS4-HMAC-SHA256',
			$credentials['access_key'],
			$credential_scope,
			$signed_headers,
			$signature
		);
		
		return [
			'access_key' => $credentials['access_key'],
			'authorization' => $auth_header,
			'credential_scope' => $credential_scope,
			'date' => $date_iso_8601,
			'signature' => $signature,
			'signed_headers' => $signed_headers,
		];
	}
	
	private function _getServiceRegionFromHost($host, &$service=null, &$region=null) {
		// Derive service + region from URL
		$matches = [];
		$service = $region = null;
		
		if(preg_match('#^(.*?)\.(.*?)\.amazonaws\.com$#', $host, $matches)) {
			$service = DevblocksPlatform::strLower($matches[1]);
			$region = DevblocksPlatform::strLower($matches[2]);
			
		} else if(preg_match('#^(.*?)\.amazonaws\.com$#', $host, $matches)) {
			$service = $matches[1];
			$region = 'us-east-1';
		}
		
		if(empty($region) || empty($service))
			return false;
		
		return true;
	}
	
	function authenticateHttpRequest(Model_ConnectedAccount $account, Psr\Http\Message\RequestInterface &$request, array &$options = []) : bool {
		if(false == ($result = $this->_generateRequestSignature($account, $request)))
			return false;
		
		$request = $request->withHeader('Authorization', $result['authorization']);
		return true;
	}
	
	function generatePresignedUrl(RequestInterface $request, Model_ConnectedAccount $account, $expires_secs=300) {
		if(false == ($credentials = $account->decryptParams()))
			return false;
		
		$uri = $request->getUri();
		$query_params = \GuzzleHttp\Psr7\parse_query($uri->getQuery());
		
		$request = $request->withHeader('Host', $uri->getHost());
		
		$service = $region = null;
		if(!$this->_getServiceRegionFromHost($request->getUri()->getHost(), $service, $region))
			return false;
		
		$date_iso_8601 = gmdate('Ymd\THis\Z');
		
		$credential_scope = sprintf("%s/%s/%s/aws4_request",
			gmdate("Ymd"),
			$region,
			$service
		);
		
		$query_params['X-Amz-Algorithm'] = 'AWS4-HMAC-SHA256';
		$query_params['X-Amz-Credential'] = sprintf("%s/%s",
			$credentials['access_key'],
			$credential_scope
		);
		$query_params['X-Amz-Date'] = $date_iso_8601;
		$query_params['X-Amz-Expires'] = $expires_secs;
		$query_params['X-Amz-SignedHeaders'] = $this->_createSignedHeaders($request->getHeaders());
		
		$query = http_build_query($query_params, null, '&', PHP_QUERY_RFC3986);
		$uri = $uri->withQuery($query);
		$request = $request->withUri($uri);
		
		if(false == ($result = $this->_generateRequestSignature($account, $request)))
			return false;
		
		return sprintf("%s://%s%s?%s&X-Amz-Signature=%s",
			$request->getUri()->getScheme(),
			$request->getUri()->getHost(),
			$request->getUri()->getPath(),
			$request->getUri()->getQuery(),
			$result['signature']
		);
	}
	
	private function _createCanonicalPath($path=null) {
		$path = $path ?: '/';
		$path_parts = explode('/', $path);
		
		foreach($path_parts as &$segment)
			$segment = rawurlencode($segment);
		
		return implode('/', $path_parts);
	}
	
	private function _createCanonicalQueryString($query=null) {
		$query = $query ?: '';
		$canonical_query = '';
		$query_parts = DevblocksPlatform::strParseQueryString($query);
		
		ksort($query_parts, SORT_STRING);
		
		$canonical_query = http_build_query($query_parts, null, '&', PHP_QUERY_RFC3986);
		
		return $canonical_query;
	}
	
	private function _createCanonicalHeaders($headers) {
		$canonical_headers = '';
		
		ksort($headers, SORT_STRING | SORT_FLAG_CASE);
		
		foreach($headers as $key => $vals) {
			$canonical_headers .= DevblocksPlatform::strLower(trim($key)) . ':' . trim(implode(',', $vals)) . "\n";
		}
		
		return $canonical_headers;
	}
	
	private function _createSignedHeaders($headers) {
		$signed_headers = [];
		
		foreach(array_keys($headers) as $key) {
			$signed_headers[] = DevblocksPlatform::strLower(trim($key));
		}
		
		sort($signed_headers, SORT_STRING | SORT_FLAG_CASE);
		
		return implode(';', $signed_headers);
	}
}

class BotAction_AwsGetPresignedUrl extends Extension_DevblocksEventAction {
	const ID = 'wgm.aws.bot.action.get_presigned_url';
	
	static function getMeta() {
		return [
			'notes' => '',
			'params' => [
				'http_verb' => [
					'type' => 'text',
					'required' => true,
					'notes' => 'The HTTP request method: `GET`, `POST`, `PUT`, `DELETE`',
				],
				'http_url' => [
					'type' => 'text',
					'required' => true,
					'notes' => 'The HTTP request URL',
				],
				'http_headers' => [
					'type' => 'text',
					'required' => true,
					'notes' => 'The HTTP request `Header: Value` pairs, separated by newlines',
				],
				'http_body' => [
					'type' => 'text',
					'required' => true,
					'notes' => 'If `POST` or `PUT`, the HTTP request body',
				],
				'expires_secs' => [
					'type' => 'number',
					'required' => true,
					'notes' => 'The duration of the pre-signed URL',
				],
				'auth_connected_account_id' => [
					'type' => 'id',
					'required' => true,
					'notes' => 'The AWS [connected account](/docs/connected-accounts/) to use for request signing',
				],
				'response_placeholder' => [
					'type' => 'placeholder',
					'notes' => 'The placeholder to set with the presigned URL',
				],
			],
		];
	}
	
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		$aws_accounts = DAO_ConnectedAccount::getReadableByActor($trigger->getBot(), ServiceProvider_Aws::ID);
		$tpl->assign('aws_accounts', $aws_accounts);
		
		$tpl->display('devblocks:cerberusweb.core::events/action_aws_get_presigned_url.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		$out = null;
		
		@$http_verb = $params['http_verb'];
		@$http_url = $tpl_builder->build($params['http_url'], $dict);
		@$http_headers = DevblocksPlatform::parseCrlfString($tpl_builder->build($params['http_headers'], $dict));
		@$http_body = $tpl_builder->build($params['http_body'], $dict);
		@$connected_account_id = $params['auth_connected_account_id'];
		@$response_placeholder = $params['response_placeholder'];
		
		if(empty($http_verb))
			return "[ERROR] HTTP verb is required.";
		
		if(empty($http_url))
			return "[ERROR] HTTP URL is required.";
		
		if(empty($response_placeholder))
			return "[ERROR] No result placeholder given.";
		
		// Output
		$out = sprintf(">>> Generating a pre-signed AWS URL for:\n%s %s\n%s%s\n",
			mb_convert_case($http_verb, MB_CASE_UPPER),
			$http_url,
			!empty($http_headers) ? (implode("\n", $http_headers)."\n") : '',
			(in_array($http_verb, array('post','put')) ? ("\n" . $http_body. "\n") : "")
		);
		
		// Bail out on missing account
		if(false == ($connected_account = DAO_ConnectedAccount::get($connected_account_id)))
			return "[ERROR] Missing authentication account.";
		
		$out .= sprintf(">>> Authenticating with %s\n\n", $connected_account->name);
		
		$out .= sprintf(">>> Saving pre-signed URL to {{%1\$s}}:\n",
			$response_placeholder
		);
		
		$this->run($token, $trigger, $params, $dict);
		
		// [TODO] Handle errors
		$signed_url = $dict->$response_placeholder;
		
		$out .= $signed_url . "\n";
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		@$http_verb = $params['http_verb'];
		@$http_url = $tpl_builder->build($params['http_url'], $dict);
		@$http_headers = DevblocksPlatform::parseCrlfString($tpl_builder->build($params['http_headers'], $dict));
		@$http_body = $tpl_builder->build($params['http_body'], $dict);
		@$response_placeholder = $params['response_placeholder'];
		@$expires_secs = $params['expires_secs'];
		
		// [TODO] Validation
		if(empty($http_verb) || empty($http_url))
			return false;
		
		if(empty($response_placeholder))
			return false;
		
		@$connected_account_id = intval($params['auth_connected_account_id']);
		
		if(empty($connected_account_id))
			return false;
		
		if(false == ($connected_account = DAO_ConnectedAccount::get($connected_account_id)))
			return false;
		
		// Make sure we're authorized to use this connected account!
		if(false == (Context_ConnectedAccount::isUsableByActor($connected_account, $trigger->getBot())))
			return false;
		
		$http_headers = GuzzleHttp\headers_from_lines($http_headers);
		
		$request = new Request($http_verb, $http_url, $http_headers, $http_body);
		
		$signed_url = $this->_sign_url($request, $connected_account, $expires_secs);
		$dict->$response_placeholder = $signed_url;
	}
	
	private function _sign_url(RequestInterface $request, Model_ConnectedAccount $connected_account, $expires_secs=300) {
		switch(DevblocksPlatform::strLower($request->getMethod())) {
			case 'patch':
			case 'post':
			case 'put':
				if(!$request->hasHeader('content-type'))
					$request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded'); 
				break;
		}

		$aws = new ServiceProvider_Aws();
		$signed_url = $aws->generatePresignedUrl($request, $connected_account, $expires_secs);
		
		return $signed_url;
	}
};