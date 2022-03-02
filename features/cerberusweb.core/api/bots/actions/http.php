<?php
use GuzzleHttp\Psr7\Request;

class BotAction_HttpRequest extends Extension_DevblocksEventAction {
	static function getMeta() {
		return [
			'notes' => '',
			'params' => [
				'http_verb' => [
					'type' => 'text',
					'required' => true,
					'notes' => 'The request method: `GET`, `POST`, `PUT`, `PATCH`, `DELETE`',
				],
				'http_url' => [
					'type' => 'text',
					'required' => true,
					'notes' => 'The request URL',
				],
				'http_headers' => [
					'type' => 'text',
					'notes' => 'A list of `Header: Value` pairs (e.g. `Content-Type: application/json`), separated by newlines',
				],
				'http_body' => [
					'type' => 'text',
					'notes' => 'If `POST` or `PUT`, the HTTP request body',
				],
				'auth' => [
					'type' => 'text',
					'notes' => '`connected_account`, `placeholder`, or omitted',
				],
				'auth_connected_account_id' => [
					'type' => 'text',
					'notes' => 'When using auth=`connected_account` must return a [connected account](/docs/records/types/connected-account/] ID',
				],
				'auth_placeholder' => [
					'type' => 'text',
					'notes' => 'When using auth=`placeholder` the template must result to a [connected account](/docs/records/types/connected-account/] ID',
				],
				'options[ignore_ssl_validation]' => [
					'type' => 'bit',
					'notes' => '`0` (validate SSL), `1` (ignore SSL validation)',
				],
				'options[raw_response_body]' => [
					'type' => 'bit',
					'notes' => '`0` (auto-convert response by content type), `1` (keep raw response)',
				],
				'run_in_simulator' => [
					'type' => 'bit',
					'notes' => 'Make HTTP requests in the simulator: `0`=no, `1`=yes',
				],
				'response_placeholder' => [
					'type' => 'placeholder',
					'notes' => 'The placeholder to set with the HTTP response',
				],
			],
		];
	}
	
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		$connected_accounts = DAO_ConnectedAccount::getUsableByActor($trigger->getBot());
		$tpl->assign('connected_accounts', $connected_accounts);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_action_http_request.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();

		$http_verb = $params['http_verb'] ?? null;
		$http_url = $tpl_builder->build($params['http_url'] ?? '', $dict);
		$http_headers = DevblocksPlatform::parseCrlfString($tpl_builder->build($params['http_headers'] ?? '', $dict));
		$http_body = $tpl_builder->build($params['http_body'] ?? '', $dict);
		$auth = $params['auth'] ?? null;
		$run_in_simulator = $params['run_in_simulator'] ?? null;
		$response_placeholder = $params['response_placeholder'] ?? null;
		
		if(empty($http_verb))
			return "[ERROR] HTTP verb is required.";
		
		if(empty($http_url))
			return "[ERROR] HTTP URL is required.";
		
		if(empty($response_placeholder))
			return "[ERROR] No result placeholder given.";
		
		if(extension_loaded('fileinfo')) {
			$finfo = finfo_open(FILEINFO_MIME);
			$file_type = finfo_buffer($finfo, $http_body);
			finfo_close($finfo);
		} else {
			$file_type = 'text/plain';
		}
		
		// Output
		$out = sprintf(">>> Sending HTTP request:\n%s %s\n%s%s\n",
			mb_convert_case($http_verb, MB_CASE_UPPER),
			$http_url,
			!empty($http_headers) ? (implode("\n", $http_headers)."\n") : '',
			(in_array($http_verb, array('post','put')) ? ("\n" . (!$http_body || DevblocksPlatform::strStartsWith($file_type, 'text') ? $http_body : '[binary content]') . "\n") : "")
		);
		
		switch($auth) {
			case 'connected_account':
				$connected_account_id = $params['auth_connected_account_id'] ?? null;
				if(false != ($connected_account = DAO_ConnectedAccount::get($connected_account_id))) {
					if(!Context_ConnectedAccount::isUsableByActor($connected_account, $trigger->getBot()))
						return "[ERROR] This behavior is attempting to use an unauthorized connected account.";
					
					$out .= sprintf(">>> Authenticating with %s\n\n", $connected_account->name);
				}
				break;
				
			case 'placeholder':
				$placeholder = $params['auth_placeholder'] ?? null;
				@$connected_account_id = $tpl_builder->build($placeholder, $dict);
				
				if(!$connected_account_id || false != ($connected_account = DAO_ConnectedAccount::get($connected_account_id))) {
					if(!Context_ConnectedAccount::isUsableByActor($connected_account, $trigger->getBot()))
						return "[ERROR] This behavior is attempting to use an unauthorized connected account.";
					
					$out .= sprintf(">>> Authenticating with %s\n\n", $connected_account->name);
				}
				break;
		}
		
		$out .= sprintf(">>> Saving response to {{%1\$s}}\n".
			" * {{%1\$s.body}}\n".
			" * {{%1\$s.content_type}}\n".
			" * {{%1\$s.error}}\n".
			" * {{%1\$s.headers}}\n".
			" * {{%1\$s.info}}\n".
			" * {{%1\$s.info.http_code}}\n".
			"\n",
			$response_placeholder
		);
		
		// If set to run in simulator as well
		if($run_in_simulator) {
			$this->run($token, $trigger, $params, $dict);
			
			$response = $dict->$response_placeholder;
			
			if(isset($response['error']) && !empty($response['error'])) {
				$out .= sprintf(">>> Error in response:\n%s\n", $response['error']);
			} else {
				if(isset($response['info']))
					$out .= sprintf(">>> Response info:\n%s\n\n", DevblocksPlatform::strFormatJson(json_encode($response['info'])));
				
				if(isset($response['headers']))
					$out .= sprintf(">>> Response headers:\n%s\n\n", DevblocksPlatform::strFormatJson(json_encode($response['headers'])));
				
				if(isset($response['body']))
					$out .= sprintf(">>> Response body:\n%s\n",
						is_array($response['body']) ? DevblocksPlatform::strFormatJson(json_encode($response['body'])) : $response['body']
					);
			}
			
		} else {
			$out .= ">>> NOTE: This HTTP request is not configured to run in the simulator.\n";
		}
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();

		$http_verb = $params['http_verb'] ?? null;
		$http_url = $tpl_builder->build($params['http_url'] ?? '', $dict);
		$http_headers = DevblocksPlatform::parseCrlfString($tpl_builder->build($params['http_headers'] ?? '', $dict));
		$http_body = $tpl_builder->build($params['http_body'] ?? '', $dict);
		$auth = $params['auth'] ?? null;
		$options = $params['options'] ?? [];
		$response_placeholder = $params['response_placeholder'] ?? null;
		
		if(empty($http_verb) || empty($http_url))
			return false;
		
		if(empty($response_placeholder))
			return false;
		
		switch($auth) {
			case 'connected_account':
				$options['connected_account_id'] = @intval($params['auth_connected_account_id']);
				$options['trigger'] = $trigger;
				break;
				
			case 'placeholder':
				$connected_account_id = $tpl_builder->build($params['auth_placeholder'] ?? '', $dict);
				$options['connected_account_id'] = @intval($connected_account_id);
				$options['trigger'] = $trigger;
				break;
		}
		
		$response = $this->_execute($http_verb, $http_url, [], $http_body, $http_headers, $options);
		$dict->$response_placeholder = $response;
	}
	
	private function _execute($verb='GET', $url=null, $params=[], $body=null, $headers=[], $options=[]) {
		$http = DevblocksPlatform::services()->http();
		
		if(!empty($params) && is_array($params))
			$url .= '?' . http_build_query($params);
		
		$headers = GuzzleHttp\headers_from_lines($headers);
		
		$request = new Request($verb, $url, $headers, $body);
		$request_options = [
			'http_errors' => false,
		];
		
		switch(DevblocksPlatform::strLower($verb)) {
			case 'post':
			case 'put':
			case 'patch':
				if(!$request->hasHeader('content-type'))
					$request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded'); 
				break;
		}
		
		if(isset($options['ignore_ssl_validation']) && $options['ignore_ssl_validation']) {
			$request_options['verify'] = false;
		}
		
		if(isset($options['connected_account_id']) && $options['connected_account_id']) {
			if(false == ($connected_account = DAO_ConnectedAccount::get($options['connected_account_id'])))
				return false;
			
			if(false == $connected_account->authenticateHttpRequest($request, $request_options, CerberusContexts::getCurrentActor()))
				return false;
		}
		
		// [TODO] User-level option to follow redirects
		
		$content_type = null;
		$response_headers = [];
		$info = [];
		$error = null;
		
		if(false === ($response = $http->sendRequest($request, $request_options, $error))) {
			
		} else {
			foreach($response->getHeaders() as $k => $v) {
				$response_headers[DevblocksPlatform::strLower($k)] = implode(', ', $v);
			}
			
			$content_type = $response->getHeaderLine('Content-Type');
			$body = $response->getBody()->getContents();
			
			$info = [
				'url' => $url,
				'http_code' => $response->getStatusCode(),
			];
			
			// Split content_type + charset in the header
			list($content_type, $content_attributes) = array_pad(explode(';', $content_type, 2), 2, null);
			
			$content_type = trim(DevblocksPlatform::strLower($content_type));
			$content_attributes = DevblocksPlatform::parseHttpHeaderAttributes($content_attributes);
			
			// Fix bad encodings
			if(isset($content_attributes['charset'])) {
				@$body = mb_convert_encoding($body, $content_attributes['charset']);
			}
			
			// Auto-convert the response body based on the type
			if(!(isset($options['raw_response_body']) && $options['raw_response_body'])) {
				switch($content_type) {
					case 'application/json':
					case 'text/javascript':
						@$body = json_decode($body, true);
						break;
						
					case 'application/octet-stream':
					case 'application/pdf':
					case 'application/zip':
						@$body = base64_encode($body);
						break;
						
					case 'audio/mpeg':
					case 'audio/ogg':
						@$body = base64_encode($body);
						break;
					
					case 'image/gif':
					case 'image/jpeg':
					case 'image/jpg':
					case 'image/png':
						@$body = base64_encode($body);
						break;
						
					case 'text/csv':
					case 'text/html':
					case 'text/plain':
					case 'text/xml':
						break;
						
					default:
						break;
				}
			}
		}
		
		$result = [
			'content_type' => $content_type,
			'headers' => $response_headers,
			'body' => $body,
			'info' => $info,
			'error' => $error,
		];
		
		return $result;
	}
};