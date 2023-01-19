<?php
class Controller_Webhooks implements DevblocksHttpRequestHandler {
	function handleRequest(DevblocksHttpRequest $request) {
		$stack = $request->path;
		$metrics = DevblocksPlatform::services()->metrics();
		
		array_shift($stack); // webhooks
		@$guid = array_shift($stack); // guid
		
		if(!$guid)
			DevblocksPlatform::dieWithHttpError(null, 404);
			
		if(false == ($webhook = DAO_WebhookListener::getByGUID($guid)))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$metrics->increment('cerb.webhook.invocations', 1, ['webhook_id' => $webhook->id, 'client_ip' => DevblocksPlatform::getClientIp()]);
		
		$error = null;
		
		$request_headers = DevblocksPlatform::getHttpHeaders() ?: [];
		unset($request_headers['cookie']);
		
		$dict = DevblocksDictionaryDelegate::instance([
			'request_method' => DevblocksPlatform::strUpper($_SERVER['REQUEST_METHOD']),
			'request_body' => DevblocksPlatform::getHttpBody(),
			'request_client_ip' => DevblocksPlatform::getClientIp(),
			'request_headers' => $request_headers,
			'request_params' => DevblocksPlatform::getHttpParams(),
			'request_path' => implode('/', $stack),
		]);
		$dict->mergeKeys('webhook_', DevblocksDictionaryDelegate::getDictionaryFromModel($webhook, CerberusContexts::CONTEXT_WEBHOOK_LISTENER));
		
		$this->respond($webhook->automations_kata, $dict, $error);
	}
	
	public function respond(string $automations_kata, DevblocksDictionaryDelegate $dict, &$error=null) {
		$event_handler = DevblocksPlatform::services()->ui()->eventHandler();
		
		$handlers = $event_handler->parse($automations_kata, $dict, $error);
		
		$automation_results = $event_handler->handleOnce(
			AutomationTrigger_WebhookRespond::ID,
			$handlers,
			$dict->getDictionary(),
			$error,
			function(Model_TriggerEvent $behavior, array $handler) use ($dict) {
				if($behavior->event_point != Event_WebhookReceived::ID)
					return false;
				
				if(false == ($bot = $behavior->getBot()))
					return false;
				
				if($behavior->is_disabled || $bot->is_disabled) {
					DevblocksPlatform::dieWithHttpError('503: Temporarily unavailable', 503);
					return false;
				}
				
				$variables = [];
				
				$http_request = [
					'body' => $dict->get('request_body', ''),
					'client_ip' => $dict->get('request_client_ip', ''),
					'headers' => $dict->get('request_headers', ''),
					'params' => $dict->get('request_params', ''),
					'path' => $dict->get('request_path', ''),
					'verb' => $dict->get('request_method', ''),
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
			// If the body is a Cerb URI, stream it
			if(is_string($results['body']) && str_starts_with($results['body'], 'cerb:')) {
				$uri_parts = DevblocksPlatform::services()->ui()->parseURI($results['body']);
				
				$resource = match($uri_parts['context'] ?? null) {
					CerberusContexts::CONTEXT_AUTOMATION_RESOURCE => DAO_AutomationResource::getByToken($uri_parts['context_id']), 
					CerberusContexts::CONTEXT_RESOURCE => DAO_Resource::getByName($uri_parts['context_id']),
					default => null,
				};
				
				if($resource) {
					// If larger than 1MB, use streams
					if($resource->storage_size > 1_000_000) {
						$fp = DevblocksPlatform::getTempFile();
						
						if(($resource->getFileContents($fp)))
							fpassthru($fp);
						
						fclose($fp);
					} else { // Otherwise output as a string
						echo $resource->getFileContents();
					}
				}
				
			} else {
				echo $results['body'];
			}
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
	const PARAM_WEBHOOK_AUTOMATIONS_KATA = 'automations_kata';
	
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
		$portal = ChPortalHelper::getPortal();
		
		$automations_kata = $config[self::PARAM_WEBHOOK_AUTOMATIONS_KATA] ?? '';
		$error = null;
		
		$controller = DevblocksPlatform::getExtension('webhooks.controller', true);
		
		/** @var $controller Controller_Webhooks */
		
		$request_headers = DevblocksPlatform::getHttpHeaders() ?: [];
		unset($request_headers['cookie']);
		
		$dict = DevblocksDictionaryDelegate::instance([
			'request_method' => DevblocksPlatform::strUpper($_SERVER['REQUEST_METHOD']),
			'request_body' => DevblocksPlatform::getHttpBody(),
			'request_client_ip' => DevblocksPlatform::getClientIp(),
			'request_headers' => $request_headers,
			'request_params' => DevblocksPlatform::getHttpParams(),
			'request_path' => implode('/', $path),
		]);
		$dict->mergeKeys('portal_', DevblocksDictionaryDelegate::getDictionaryFromModel($portal, CerberusContexts::CONTEXT_PORTAL));	
		
		$controller->respond($automations_kata, $dict, $error);
	}
	
	/**
	 * @param Model_CommunityTool $instance
	 */
	public function configure(Model_CommunityTool $instance) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('portal', $instance);
		
		$params = DAO_CommunityToolProperty::getAllByTool($instance->code);
		$tpl->assign('params', $params);
		
		$tpl->display('devblocks:cerb.webhooks::portal/config.tpl');
	}
	
	public function saveConfiguration(Model_CommunityTool $instance) {
		$params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array',[]);
		
		$automations_kata = $params[self::PARAM_WEBHOOK_AUTOMATIONS_KATA] ?? '';
		
		DAO_CommunityToolProperty::set($instance->code, self::PARAM_WEBHOOK_AUTOMATIONS_KATA, $automations_kata);
	}
}