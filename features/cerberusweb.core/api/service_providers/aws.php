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
		$edit_params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', []);
		
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
		$validation
			->addField('allow_non_aws_hosts','Allow Non-AWS Hosts')
			->uint()
			->setMin(0)
			->setMax(1)
			;
		
		if(!$validation->validateAll($edit_params, $error))
			return false;

		foreach($edit_params as $k => $v)
			$params[$k] = $v;
		
		$params['allow_non_aws_hosts'] = !empty($edit_params['allow_non_aws_hosts']) ? 1 : 0;

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

		$allow_non_aws = !empty($credentials['allow_non_aws_hosts']);

		// Derive service + region from the request host (also enforces the AWS-hosts-only restriction)
		$service = $region = null;
		if(!DevblocksPlatform::services()->aws()->deriveServiceRegionFromHost($request->getUri()->getHost(), $allow_non_aws, $service, $region))
			return false;

		$signer = DevblocksPlatform::services()->aws()->signer($credentials['access_key'], $credentials['secret_key']);

		return $signer->calculate(
			$request,
			$region,
			$service,
			DevblocksPlatform::strLower(hash('sha256', $request->getBody()->getContents()))
		);
	}

	function authenticateHttpRequest(Model_ConnectedAccount $account, Psr\Http\Message\RequestInterface &$request, array &$options = []) : bool {
		$credentials = $account->decryptParams();
		$allow_non_aws = !empty($credentials['allow_non_aws_hosts']);

		// Derive service + region (also enforces the AWS-hosts-only restriction)
		$service = $region = null;
		if(!DevblocksPlatform::services()->aws()->deriveServiceRegionFromHost($request->getUri()->getHost(), $allow_non_aws, $service, $region))
			return false;

		// AWS requires x-amz-date to be present and signed
		if(!$request->hasHeader('x-amz-date'))
			$request = $request->withHeader('X-Amz-Date', gmdate('Ymd\THis\Z'));

		// S3 requires a signed x-amz-content-sha256 header
		if('s3' == $service && !$request->hasHeader('x-amz-content-sha256')) {
			$body = $request->getBody();
			$content_sha256 = hash('sha256', (string) $body);

			if($body->isSeekable())
				$body->rewind();

			$request = $request->withHeader('x-amz-content-sha256', $content_sha256);
		}

		if(!($result = $this->_generateRequestSignature($account, $request)))
			return false;

		$request = $request->withHeader('Authorization', $result['authorization']);

		return true;
	}
	
	function generatePresignedUrl(RequestInterface $request, Model_ConnectedAccount $account, $expires_secs=300) {
		if(!($credentials = $account->decryptParams()))
			return false;
		
		$uri = $request->getUri();
		$query_params = \GuzzleHttp\Psr7\parse_query($uri->getQuery());
		
		$request = $request->withHeader('Host', $uri->getHost());

		$allow_non_aws = !empty($credentials['allow_non_aws_hosts']);

		$service = $region = null;
		if(!DevblocksPlatform::services()->aws()->deriveServiceRegionFromHost($request->getUri()->getHost(), $allow_non_aws, $service, $region))
			return false;

		$signer = DevblocksPlatform::services()->aws()->signer($credentials['access_key'], $credentials['secret_key']);

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
		$query_params['X-Amz-SignedHeaders'] = $signer->signedHeaders($request->getHeaders());
		
		$query = http_build_query($query_params, '', '&', PHP_QUERY_RFC3986);
		$uri = $uri->withQuery($query);
		$request = $request->withUri($uri);
		
		if(!($result = $this->_generateRequestSignature($account, $request)))
			return false;
		
		return sprintf("%s://%s%s?%s&X-Amz-Signature=%s",
			$request->getUri()->getScheme(),
			$request->getUri()->getHost(),
			$request->getUri()->getPath(),
			$request->getUri()->getQuery(),
			$result['signature']
		);
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
		
		$tpl->display('devblocks:cerb.behaviors.legacy::events/action_aws_get_presigned_url.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		$out = null;
		
		$http_verb = $params['http_verb'] ?? null;
		$http_url = $tpl_builder->build($params['http_url'] ?? '', $dict);
		$http_headers = DevblocksPlatform::parseCrlfString($tpl_builder->build($params['http_headers'] ?? '', $dict));
		$http_body = $tpl_builder->build($params['http_body'] ?? '', $dict);
		$connected_account_id = $params['auth_connected_account_id'] ?? null;
		$response_placeholder = $params['response_placeholder'] ?? null;
		
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
		if(!($connected_account = DAO_ConnectedAccount::get($connected_account_id)))
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
		
		$http_verb = $params['http_verb'] ?? null;
		$http_url = $tpl_builder->build($params['http_url'] ?? '', $dict);
		$http_headers = DevblocksPlatform::parseCrlfString($tpl_builder->build($params['http_headers'] ?? '', $dict));
		$http_body = $tpl_builder->build($params['http_body'] ?? '', $dict);
		$response_placeholder = $params['response_placeholder'] ?? null;
		$expires_secs = $params['expires_secs'] ?? null;
		
		// [TODO] Validation
		if(empty($http_verb) || empty($http_url))
			return false;
		
		if(empty($response_placeholder))
			return false;
		
		@$connected_account_id = intval($params['auth_connected_account_id']);
		
		if(empty($connected_account_id))
			return false;
		
		if(!($connected_account = DAO_ConnectedAccount::get($connected_account_id)))
			return false;
		
		// Make sure we're authorized to use this connected account!
		if(!(Context_ConnectedAccount::isUsableByActor($connected_account, $trigger->getBot())))
			return false;
		
		$http_headers = GuzzleHttp\Utils::headersFromLines($http_headers);
		
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