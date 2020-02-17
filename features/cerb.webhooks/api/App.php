<?php
abstract class Extension_WebhookListenerEngine extends DevblocksExtension {
	const POINT = 'cerb.webhooks.listener.engine';
	
	protected $_config = null;
	
	/**
	 * @internal
	 */
	public static function getAll($as_instances=false) {
		$engines = DevblocksPlatform::getExtensions(self::POINT, $as_instances);
		if($as_instances)
			DevblocksPlatform::sortObjects($engines, 'manifest->name');
		else
			DevblocksPlatform::sortObjects($engines, 'name');
		return $engines;
	}
	
	/**
	 * @internal
	 * 
	 * @param string $id
	 * @return Extension_WebhookListenerEngine
	 */
	public static function get($id) {
		static $extensions = null;
		
		if(isset($extensions[$id]))
			return $extensions[$id];
		
		if(!isset($extensions[$id])) {
			if(null == ($ext = DevblocksPlatform::getExtension($id, true)))
				return;
			
			if(!($ext instanceof Extension_WebhookListenerEngine))
				return;
			
			$extensions[$id] = $ext;
			return $ext;
		}
	}
	
	function getConfig() {
		if(is_null($this->_config)) {
			$this->_config = $this->getParams();
		}
		
		return $this->_config;
	}
	
	abstract function renderConfig(Model_WebhookListener $model);
	abstract function handleWebhookRequest(Model_WebhookListener $webhook);
};

class WebhookListenerEngine_BotBehavior extends Extension_WebhookListenerEngine {
	const ID = 'cerb.webhooks.listener.engine.va';
	
	function renderConfig(Model_WebhookListener $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('engine', $this);
		$tpl->assign('params', $model->extension_id == $this->manifest->id ? $model->extension_params : array());
		
		$behaviors = DAO_TriggerEvent::getReadableByActor($active_worker, 'event.webhook.received', false);
		$bots = DAO_Bot::getReadableByActor($active_worker);

		// Filter bots to those with existing behaviors
		
		$visible_va_ids = array();

		if(is_array($behaviors));
		foreach($behaviors as $behavior) {
			$visible_va_ids[$behavior->bot_id] = true;
		}
		
		$bots = array_filter($bots, function($va) use ($visible_va_ids) {
			if(isset($visible_va_ids[$va->id]))
				return true;
			
			return false;
		});
		
		$tpl->assign('behaviors', $behaviors);
		$tpl->assign('bots', $bots);
		
		$tpl->display('devblocks:cerb.webhooks::webhook_listener/engines/bot_behavior.tpl');
	}
	
	function handleWebhookRequest(Model_WebhookListener $webhook) {
		if(false == ($behavior_id = @$webhook->extension_params['behavior_id']))
			return false;

		if(false == ($behavior = DAO_TriggerEvent::get($behavior_id)))
			return false;
		
		if(false == ($bot = $behavior->getBot()))
			return false;
		
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
			'path' => '',
			'verb' => DevblocksPlatform::strUpper($_SERVER['REQUEST_METHOD']),
		];
		
		$dicts = Event_WebhookReceived::trigger($behavior->id, $http_request, $variables);
		$dict = $dicts[$behavior->id];
		
		if(!($dict instanceof DevblocksDictionaryDelegate))
			return;
		
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
};

class Controller_Webhooks implements DevblocksHttpRequestHandler {
	
	function handleRequest(DevblocksHttpRequest $request) {
		$stack = $request->path;
		
		// [TODO] Restrict by IP?
		
		array_shift($stack); // webhooks
		@$guid = array_shift($stack); // guid
		
		if(empty($guid) || false == ($webhook = DAO_WebhookListener::getByGUID($guid)))
			// [TODO] Return an HTTP failed status code
			return;
		
		// Load the webhook listener extension
		
		if(false == ($webhook_ext = $webhook->getExtension()))
			return;
		
		$webhook_ext->handleWebhookRequest($webhook);
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