<?php
// [TODO] Session info in bot

class Portal_ConvoBotWidget extends Extension_CommunityPortal {
	const PARAM_BOT_NAME = 'bot_name';
	const PARAM_CORS_ALLOW_ORIGIN = 'cors_allow_origin';
	const PARAM_INTERACTION_BEHAVIOR_ID = 'interaction_behavior_id';
	const PARAM_PAGE_CSS = 'page_css';
	const PARAM_PAGE_HIDE_ICON = 'page_hide_icon';
	const PARAM_PAGE_TITLE = 'page_title';
	
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
		$path = $request->path;
		
		$config = $this->getConfig();
		
		if(isset($config[self::PARAM_CORS_ALLOW_ORIGIN])) {
			$origin = $config[self::PARAM_CORS_ALLOW_ORIGIN] ?: '*';
			// [TODO] Handle HTTP and HTTPS (via ENV)
			header('Access-Control-Allow-Origin: ' . $origin);
			header('Access-Control-Allow-Credentials: true');
			//header('Access-Control-Allow-Headers: User-Agent, Content-Type');
			//header('Access-Control-Allow-Methods: GET,OPTIONS,POST');
		}
		
		@$a = DevblocksPlatform::importGPC($_REQUEST['a'],'string');
		
		if(empty($a)) {
			@$action = array_shift($path) . 'Action';
		} else {
			@$action = $a . 'Action';
		}

		switch($action) {
			case NULL:
				// [TODO] Index/page render
				break;

			default:
				// Default action, call arg as a method suffixed with Action
				if(method_exists($this, $action)) {
					call_user_func(array(&$this, $action)); // [TODO] Pass HttpRequest as arg?
				}
				break;
		}
	}
	
	public function writeResponse(DevblocksHttpResponse $response) {
		$path = $response->path;
		$stack = array_shift($path);
		
		$portal_code = ChPortalHelper::getCode();
		$config = $this->getConfig();
		
		switch($stack) {
			case 'interaction':
				$action = array_shift($path);
				
				switch($action) {
					case 'start':
						@$id = DevblocksPlatform::importGPC($_REQUEST['id'], 'string', '');
						@$browser = DevblocksPlatform::importGPC($_REQUEST['browser'], 'array', []);
						@$interaction_params = DevblocksPlatform::importGPC($_REQUEST['params'], 'array', []);

						@$interaction_behavior_id = $config[self::PARAM_INTERACTION_BEHAVIOR_ID];
						@$bot_name = @$config[self::PARAM_BOT_NAME] ?: 'Cerb';
						
						// [TODO] We have their whole session here
						// [TODO] Be able to remember things about their session
						$umsession = ChPortalHelper::getSession();
						
						if(
							!$interaction_behavior_id
							|| false == ($interaction_behavior = DAO_TriggerEvent::get($interaction_behavior_id))
							|| $interaction_behavior->event_point != Event_NewInteractionChatPortal::ID
						)
							return false;
						
						// Start the session using the behavior
						
						$actions = [];
						
						$client_ip = DevblocksPlatform::getClientIp();
						$client_platform = '';
						$client_browser = '';
						$client_browser_version = '';
						$client_url = @$browser['url'] ?: '';
						$client_time = @$browser['time'] ?: '';
						
						if(false !== ($client_user_agent_parts = DevblocksPlatform::getClientUserAgent())) {
							$client_platform = @$client_user_agent_parts['platform'] ?: '';
							$client_browser = @$client_user_agent_parts['browser'] ?: '';
							$client_browser_version = @$client_user_agent_parts['version'] ?: '';
						}
						
						$event_model = new Model_DevblocksEvent(
							Event_NewInteractionChatPortal::ID,
							array(
								'portal_code' => $portal_code,
								'interaction' => $id,
								'interaction_params' => $interaction_params,
								'coookie' => $umsession->session_id,
								'client_browser' => $client_browser,
								'client_browser_version' => $client_browser_version,
								'client_ip' => $client_ip,
								'client_platform' => $client_platform,
								'client_time' => $client_time,
								'client_url' => $client_url,
								'actions' => &$actions,
							)
						);
						
						if(false == ($event = $interaction_behavior->getEvent()))
							return;
							
						$event->setEvent($event_model, $interaction_behavior);
						
						$values = $event->getValues();
						
						$dict = DevblocksDictionaryDelegate::instance($values);
						
						$result = $interaction_behavior->runDecisionTree($dict, false, $event);
						
						//$dict->scrubKeys('__trigger');
						//$dict->scrubKeys('_cached_contexts');
						//$dict->scrubKeys('_null');
						
						$behavior_id = null;
						$dict = null;
						
						foreach($actions as $action) {
							switch($action['_action']) {
								case 'behavior.switch':
									if(isset($action['behavior_id'])) {
										@$behavior_id = $action['behavior_id'];
										@$variables = $action['behavior_variables'];
										
										if(is_array($variables))
										foreach($variables as $k => $v) {
											$dict[$k] = $v;
										}
									}
									break;
							}
						}
						
						if(
							!$behavior_id 
							|| false == ($behavior = DAO_TriggerEvent::get($behavior_id))
							|| $behavior->event_point != Event_NewMessageChatPortal::ID
							)
							return;
							
						$bot = $behavior->getBot();
						
						$session_data = [
							//'actor' => ['context' => CerberusContexts::CONTEXT_WORKER, 'id' => $active_worker->id],
							'bot_name' => $bot_name, // [TODO] Configurable
							'bot_image' => null, // [TODO] Configurable
							'behavior_id' => $behavior->id,
							'behaviors' => [
								$behavior->id => [
									'dict' => $dict,
								]
							],
							'cookie' => $umsession->session_id,
							'portal_code' => $portal_code,
							'interaction' => $id,
							'interaction_params' => $interaction_params,
							'client_browser' => $client_browser,
							'client_browser_version' => $client_browser_version,
							'client_ip' => $client_ip,
							'client_platform' => $client_platform,
							'client_time' => $client_time,
							'client_url' => $client_url,
						];
						
						$session_id = DAO_BotSession::create([
							DAO_BotSession::SESSION_DATA => json_encode($session_data),
							DAO_BotSession::UPDATED_AT => time(),
						]);
						
						$tpl = DevblocksPlatform::services()->template();
						
						$tpl->assign('bot_name', $bot_name);
						$tpl->assign('session_id', $session_id);
						
						$tpl->display('devblocks:cerb.bots.portal.widget::widget/window.tpl');
						break;
						
					case 'message':
						@$session_id = DevblocksPlatform::importGPC($_REQUEST['session_id'], 'string', '');
						@$message = DevblocksPlatform::importGPC($_REQUEST['message'], 'string', '');
						
						$tpl = DevblocksPlatform::services()->template();
						
						// Load the session
						if(false == ($interaction = DAO_BotSession::get($session_id)))
							return false;
						
						// [TODO] Verify session ownership
						// [TODO] What happens if we're chatting to a dead session? Open a new one?
				
						// Load our default behavior for this interaction
						if(false == (@$behavior_id = $interaction->session_data['behavior_id']))
							return false;
						
						if(false == (@$bot_name = $interaction->session_data['bot_name']))
							$bot_name = 'Cerb';
						
						$actions = array();
						
						if(false == ($behavior = DAO_TriggerEvent::get($behavior_id)))
							return;
						
						$event_params = [
							'message' => $message,
							'actions' => &$actions,
								
							// [TODO] If we have a caller?
							'bot_name' => $bot_name,
							'bot_image' => @$interaction->session_data['bot_image'],
							'behavior_id' => $behavior_id,
							'cookie' => @$interaction->session_data['cookie'],
							'portal_code' => @$interaction->session_data['portal_code'],
							'interaction' => @$interaction->session_data['interaction'],
							'interaction_params' => @$interaction->session_data['interaction_params'],
							'client_browser' => @$interaction->session_data['client_browser'],
							'client_browser_version' => @$interaction->session_data['client_browser_version'],
							'client_ip' => @$interaction->session_data['client_ip'],
							'client_platform' => @$interaction->session_data['client_platform'],
							'client_time' => @$interaction->session_data['client_time'],
							'client_url' => @$interaction->session_data['client_url'],
						];
						
						$event_model = new Model_DevblocksEvent(
							Event_NewMessageChatPortal::ID,
							$event_params
						);
						
						if(false == ($event = Extension_DevblocksEvent::get($event_model->id, true)))
							return;
						
						if(!($event instanceof Event_NewMessageChatPortal))
							return;
							
						$event->setEvent($event_model, $behavior);
						
						$values = $event->getValues();
						
						// Are we resuming a scope?
						$resume_dict = @$interaction->session_data['behaviors'][$behavior->id]['dict'];
						if($resume_dict) {
							$values = array_replace($values, $resume_dict);
						}
						
						$dict = new DevblocksDictionaryDelegate($values);
							
						$resume_path = @$interaction->session_data['behaviors'][$behavior->id]['path'];
						if($resume_path) {
							if(false == ($result = $behavior->resumeDecisionTree($dict, false, $event, $resume_path)))
								return;
							
						} else {
							if(false == ($result = $behavior->runDecisionTree($dict, false, $event)))
								return;
						}
						
						$values = $dict->getDictionary(null, false);
						$values = array_diff_key($values, $event->getValues());
						
						// Hibernate
						if($result['exit_state'] == 'SUSPEND') {
							// Keep everything as it is
						} else {
							// Start the tree over
							$result['path'] = [];
							
							// Return to the caller if we have one
							@$caller = array_pop($interaction->session_data['callers']);
							
							if(is_array($caller)) {
								$caller_behavior_id = $caller['behavior_id'];
								
								if($caller_behavior_id && isset($interaction->session_data['behaviors'][$caller_behavior_id])) {
									$interaction->session_data['behavior_id'] = $caller_behavior_id;
									$interaction->session_data['behaviors'][$caller_behavior_id]['dict']['_behavior'] = $values;
								}
								
								$tpl->display('devblocks:cerb.bots.portal.widget::widget/convo/prompt_wait.tpl');
							}
							
							// [TODO] If we don't have a caller, the chat should end.
						}
						
						$interaction->session_data['behaviors'][$behavior->id]['dict'] = $values;
						$interaction->session_data['behaviors'][$behavior->id]['path'] = $result['path'];
						
						if(false == ($bot = $behavior->getBot()))
							return;
						
						$tpl->assign('bot', $bot);
						$tpl->assign('bot_name', $bot_name);
						
						foreach($actions as $params) {
							switch(@$params['_action']) {
								case 'behavior.switch':
									if(!isset($interaction->session_data['callers']))
										$interaction->session_data['callers'] = [];
									
									$interaction->session_data['callers'][] = [
										'behavior_id' => $behavior->id,
										'return' => '_behavior', // [TODO] Configurable
									];
									
									if(false == ($behavior_id = @$params['behavior_id']))
										break;
										
									if(false == ($new_behavior = DAO_TriggerEvent::get($behavior_id)))
										break;
									
									if($new_behavior->event_point != Event_NewMessageChatPortal::ID)
										break;
									
									if(!Context_TriggerEvent::isReadableByActor($new_behavior, $bot))
										break;
									
									$bot = $new_behavior->getBot();
									$tpl->assign('bot', $bot);
									
									$interaction->session_data['behavior_id'] = $new_behavior->id;
									$interaction->session_data['behaviors'][$new_behavior->id]['dict'] = [];
									$interaction->session_data['behaviors'][$new_behavior->id]['path'] = [];
									
									// [TODO] Can this be implied better?
									$tpl->assign('delay_ms', 0);
									$tpl->display('devblocks:cerb.bots.portal.widget::widget/convo/prompt_wait.tpl');
									break;
									
								case 'emote':
									if(false == ($emote = @$params['emote']))
										break;
									
									$tpl->assign('emote', $emote);
									$tpl->assign('delay_ms', 500);
									$tpl->display('devblocks:cerb.bots.portal.widget::widget/convo/emote.tpl');
									break;
								
								case 'prompt.buttons':
									@$options = $params['options'];
									@$color_from = $params['color_from'];
									@$color_to = $params['color_to'];
									@$color_mid = $params['color_mid'];
									@$style = $params['style'];
									
									if(!is_array($options))
										break;
									
									$tpl->assign('options', $options);
									$tpl->assign('color_from', $color_from);
									$tpl->assign('color_to', $color_to);
									$tpl->assign('color_mid', $color_mid);
									$tpl->assign('style', $style);
									$tpl->assign('delay_ms', 250);
									
									$tpl->display('devblocks:cerb.bots.portal.widget::widget/convo/prompt_buttons.tpl');
									break;
									
								case 'prompt.images':
									@$images = $params['images'];
									@$labels = $params['labels'];
									
									if(!is_array($images) || !is_array($images))
										break;
									
									$tpl->assign('images', $images);
									$tpl->assign('labels', $labels);
									$tpl->assign('delay_ms', 0);
									$tpl->display('devblocks:cerb.bots.portal.widget::widget/convo/prompt_images.tpl');
									break;
								
								case 'prompt.rating.number':
									@$options = $params['options'];
									
									if(!is_array($options))
										break;
									
									$tpl->assign('options', $options);
									$tpl->assign('delay_ms', 0);
									
									$tpl->display('devblocks:cerb.bots.portal.widget::widget/convo/prompt_rating_number.tpl');
									break;
									
								case 'prompt.text':
									@$placeholder = $params['placeholder'];
									
									if(empty($placeholder))
										$placeholder = 'say something';
									
									$tpl->assign('delay_ms', 0);
									$tpl->assign('placeholder', $placeholder);
									$tpl->display('devblocks:cerb.bots.portal.widget::widget/convo/prompt_text.tpl');
									break;
									
								case 'prompt.wait':
									$tpl->assign('delay_ms', 0);
									$tpl->display('devblocks:cerb.bots.portal.widget::widget/convo/prompt_wait.tpl');
									break;
									
								case 'message.send':
									if(false == ($msg = @$params['message']))
										break;
									
									$delay_ms = DevblocksPlatform::intClamp(@$params['delay_ms'], 0, 10000);
									
									$tpl->assign('message', $msg);
									$tpl->assign('format', @$params['format']);
									$tpl->assign('delay_ms', $delay_ms);
									$tpl->display('devblocks:cerb.bots.portal.widget::widget/convo/message.tpl');
									break;
									
								case 'script.send':
									if(false == ($script = @$params['script']))
										break;
										
									$tpl->assign('script', $script);
									$tpl->assign('delay_ms', 0);
									$tpl->display('devblocks:cerb.bots.portal.widget::widget/convo/script.tpl');
									break;
							}
						}
						
						// Save session scope
						DAO_BotSession::update($interaction->session_id, [
							DAO_BotSession::SESSION_DATA => json_encode($interaction->session_data),
							DAO_BotSession::UPDATED_AT => time(),
						]);
						break; // end message
				}
				break;
			
			case 'assets':
				$file = array_shift($path);
				
				// [TODO] Cache headers, and &v= cache killers
				switch($file) {
					case 'embed.js':
						header('Content-Type: text/javascript');
						$tpl = DevblocksPlatform::services()->template();
						$tpl->display('devblocks:cerb.bots.portal.widget::widget/embed.js');
						break;
				}
				break;
				
			default:
				$tpl = DevblocksPlatform::services()->template();
				$tpl->assign('config', $config);
				
				$interaction = $stack;
				
				if(!empty($interaction)) {
					@$params = $_GET;
					
					if(isset($params) && is_array($params)) {
						$interaction_params = json_encode($params);
						
						$tpl->assign('interaction', $interaction);
						$tpl->assign('interaction_params', $interaction_params);
					}
				}
				
				$tpl->display('devblocks:cerb.bots.portal.widget::portal/index.tpl');
				break;
		}
	}
	
	/**
	 * @param Model_CommunityTool $instance
	 */
	public function configure(Model_CommunityTool $instance) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('instance', $instance);
		
		$params = DAO_CommunityToolProperty::getAllByTool($instance->code);
		$tpl->assign('params', $params);
		
		$tpl->display('devblocks:cerb.bots.portal.widget::portal/config.tpl');
	}
	
	public function saveConfiguration(Model_CommunityTool $instance) {
		@$params = DevblocksPlatform::importGPC($_REQUEST['params'],'array',[]);
		
		if(isset($params[self::PARAM_BOT_NAME])) {
			$value = strval($params[self::PARAM_BOT_NAME]);
			DAO_CommunityToolProperty::set($instance->code, self::PARAM_BOT_NAME, $value);
		}
		
		if(isset($params[self::PARAM_CORS_ALLOW_ORIGIN])) {
			$value = strval($params[self::PARAM_CORS_ALLOW_ORIGIN]);
			DAO_CommunityToolProperty::set($instance->code, self::PARAM_CORS_ALLOW_ORIGIN, $value);
		}
		
		if(isset($params[self::PARAM_INTERACTION_BEHAVIOR_ID])) {
			$behavior_id = $params[self::PARAM_INTERACTION_BEHAVIOR_ID];
			
			if(false !== ($behavior = DAO_TriggerEvent::get($behavior_id))) {
				// Validate the event type
				if($behavior->event_point == Event_NewInteractionChatPortal::ID) {
					DAO_CommunityToolProperty::set($instance->code, self::PARAM_INTERACTION_BEHAVIOR_ID, $behavior->id);
				}
			}
		}
		
		if(isset($params[self::PARAM_PAGE_CSS])) {
			$value = strval($params[self::PARAM_PAGE_CSS]);
			DAO_CommunityToolProperty::set($instance->code, self::PARAM_PAGE_CSS, $value);
		}
		
		if(isset($params[self::PARAM_PAGE_HIDE_ICON])) {
			$value = intval($params[self::PARAM_PAGE_HIDE_ICON]) ? 1 : 0;
			DAO_CommunityToolProperty::set($instance->code, self::PARAM_PAGE_HIDE_ICON, $value);
		}
		
		if(isset($params[self::PARAM_PAGE_TITLE])) {
			$value = strval($params[self::PARAM_PAGE_TITLE]);
			DAO_CommunityToolProperty::set($instance->code, self::PARAM_PAGE_TITLE, $value);
		}
	}
}