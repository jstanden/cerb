<?php
class Controller_Webhooks implements DevblocksHttpRequestHandler {
	function handleRequest(DevblocksHttpRequest $request) {
		$event_handler = DevblocksPlatform::services()->ui()->eventHandler();
		
		$stack = $request->path;
		
		array_shift($stack); // webhooks
		@$guid = array_shift($stack); // guid
		
		$path = implode('/', $stack);
		
		if(!$guid)
			DevblocksPlatform::dieWithHttpError(null, 404);
			
		if(false == ($webhook = DAO_WebhookListener::getByGUID($guid)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$dict = DevblocksDictionaryDelegate::instance([
			'webhook__context' => CerberusContexts::CONTEXT_WEBHOOK_LISTENER,
			'webhook_id' => $webhook->id,
		]);
		
		$error = null;
		
		$request_headers = DevblocksPlatform::getHttpHeaders() ?: [];
		unset($request_headers['cookie']);
		
		$initial_state = [
			'request_method' => DevblocksPlatform::strUpper($_SERVER['REQUEST_METHOD']),
			'request_body' => DevblocksPlatform::getHttpBody(),
			'request_client_ip' => DevblocksPlatform::getClientIp(),
			'request_headers' => $request_headers,
			'request_params' => DevblocksPlatform::getHttpParams(),
			'request_path' => $path,
		];
		
		$handlers = $event_handler->parse($webhook->automations_kata, $dict, $error);
		
		$automation_results = $event_handler->handleOnce(
			AutomationTrigger_WebhookRespond::ID,
			$handlers,
			$initial_state,
			$error,
			function(Model_TriggerEvent $behavior, array $handler) use ($path) {
				if($behavior->event_point != Event_WebhookReceived::ID)
					return false;
				
				if(false == ($bot = $behavior->getBot()))
					return false;
				
				if($behavior->is_disabled || $bot->is_disabled) {
					DevblocksPlatform::dieWithHttpError('<h1>503: Temporarily unavailable</h1>', 503);
					return false;
				}
				
				$variables = [];
				
				$http_request = [
					'body' => DevblocksPlatform::getHttpBody(),
					'client_ip' => DevblocksPlatform::getClientIp(),
					'headers' => DevblocksPlatform::getHttpHeaders(),
					'params' => DevblocksPlatform::getHttpParams(),
					'path' => $path,
					'verb' => DevblocksPlatform::strUpper($_SERVER['REQUEST_METHOD']),
				];
				
				$dicts = Event_WebhookReceived::trigger($behavior->id, $http_request, $variables);
				$dict = $dicts[$behavior->id];
				
				if(!($dict instanceof DevblocksDictionaryDelegate))
					return false;
				
				return $dict;
			}
		);
		
		if($automation_results instanceof DevblocksDictionaryDelegate && $automation_results->exists('__state')) {
			$this->_webhookRequestAutomation($automation_results);
			
		} else if($automation_results instanceof DevblocksDictionaryDelegate && $automation_results->exists('__trigger')) {
			$this->_webhookRequestBehavior($automation_results);
			
		} else {
			DevblocksPlatform::dieWithHttpError(null, 404);
		}
	}
	
	private function _webhookRequestAutomation(DevblocksDictionaryDelegate $automation_results) {
		// [TODO] Check error status + throw error
		
		if(null === ($results = $automation_results->get('__return', null)))
			return;
		
		// HTTP status code
		
		if(array_key_exists('status_code', $results))
			http_response_code($results['status_code']);
		
		// HTTP response headers
		
		if(array_key_exists('headers', $results) && is_array($results['headers'])) {
			foreach($results['headers'] as $header_k => $header_v) {
				header(sprintf("%s: %s",
					$header_k,
					$header_v
				));
			}
		}
		
		// HTTP response body
		
		if(array_key_exists('body@base64', $results)) {
			echo base64_decode($results['body@base64']);
		} else if(array_key_exists('body', $results)) {
			echo $results['body'];
		}
	}
	
	// [TODO] @deprecated
	private function _webhookRequestBehavior(DevblocksDictionaryDelegate $dict) {
		
		// HTTP status code

		if(isset($dict->_http_status))
			http_response_code($dict->_http_status);
		
		// HTTP response headers
		
		if(isset($dict->_http_response_headers) && is_array($dict->_http_response_headers)) {
			foreach($dict->_http_response_headers as $header_k => $header_v) {
				header(sprintf("%s: %s",
					$header_k,
					$header_v
				));
			}
		}
		
		// HTTP response body
		
		if(isset($dict->_http_response_body)) {
			echo $dict->_http_response_body;
		}
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
	}
};

class Portal_Webhook extends Extension_CommunityPortal {
	const PARAM_WEBHOOK_BEHAVIOR_ID = 'webhook_behavior_id';
	
	private $_config = null;
	
	private function getConfig() {
		if(is_null($this->_config)) {
			$portal_code = ChPortalHelper::getCode();
			$this->_config = DAO_CommunityToolProperty::getAllByTool($portal_code);
		}
		
		return $this->_config;
	}
	
	/**
	 * @param DevblocksHttpRequest
	 * @return DevblocksHttpResponse
	 */
	public function handleRequest(DevblocksHttpRequest $request) {
	}
	
	public function writeResponse(DevblocksHttpResponse $response) {
		$path = $response->path;
		
		$config = $this->getConfig();
		
		@$webhook_behavior_id = $config[self::PARAM_WEBHOOK_BEHAVIOR_ID];
		
		if(
			!$webhook_behavior_id 
			|| false == ($behavior = DAO_TriggerEvent::get($webhook_behavior_id)) 
			)
			return;
			
		if(false == ($event = $behavior->getEvent()) || !($event instanceof Event_WebhookReceived))
			return;
		
		if(false == ($bot = $behavior->getBot()))
			return;
		
		if($behavior->is_disabled || $bot->is_disabled) {
			http_response_code(503);
			echo "<h1>503: Temporarily unavailable</h1>";
			return;
		}
		
		$variables = [];
		
		$http_request = [
			'body' => DevblocksPlatform::getHttpBody(),
			'client_ip' => DevblocksPlatform::getClientIp(),
			'headers' => DevblocksPlatform::getHttpHeaders(),
			'params' => DevblocksPlatform::getHttpParams(),
			'path' => implode('/', $path),
			'verb' => DevblocksPlatform::strUpper($_SERVER['REQUEST_METHOD']),
		];
		
		$dicts = Event_WebhookReceived::trigger($behavior->id, $http_request, $variables);
		$dict = $dicts[$behavior->id];
		
		if(!($dict instanceof DevblocksDictionaryDelegate))
			return;
		
		// HTTP status code

		if(isset($dict->_http_status)) {
			http_response_code($dict->_http_status);
		}
		
		// HTTP response headers
		
		if(isset($dict->_http_response_headers) && is_array($dict->_http_response_headers)) {
			foreach($dict->_http_response_headers as $header_k => $header_v) {
				header(sprintf("%s: %s",
					$header_k,
					$header_v
				));
			}
		}
		
		// HTTP response body
		
		if(isset($dict->_http_response_body)) {
			echo $dict->_http_response_body;
		}
	}
	
	/**
	 * @param Model_CommunityTool $instance
	 */
	public function configure(Model_CommunityTool $portal) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('portal', $portal);
		
		$params = DAO_CommunityToolProperty::getAllByTool($portal->code);
		$tpl->assign('params', $params);
		
		$tpl->display('devblocks:cerb.webhooks::portal/config.tpl');
	}
	
	public function saveConfiguration(Model_CommunityTool $instance) {
		@$params = DevblocksPlatform::importGPC($_POST['params'],'array',[]);
		
		if(array_key_exists(self::PARAM_WEBHOOK_BEHAVIOR_ID, $params)) {
			$behavior_id = $params[self::PARAM_WEBHOOK_BEHAVIOR_ID];
			
			if(false !== ($behavior = DAO_TriggerEvent::get($behavior_id))) {
				// Validate the event type
				if($behavior->event_point == Event_WebhookReceived::ID) {
					DAO_CommunityToolProperty::set($instance->code, self::PARAM_WEBHOOK_BEHAVIOR_ID, $behavior->id);
				}
			}
			
		} else {
			DAO_CommunityToolProperty::set($instance->code, self::PARAM_WEBHOOK_BEHAVIOR_ID, 0);
		}
	}
}