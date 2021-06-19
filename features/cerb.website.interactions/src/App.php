<?php /** @noinspection PhpUnused DuplicatedCode */

class Portal_WebsiteInteractions extends Extension_CommunityPortal {
	const ID = 'cerb.website.interactions';
	
	const PARAM_AUTOMATIONS_KATA = 'automations_kata';
	const PARAM_CORS_ORIGINS_ALLOWED = 'cors_origins_allowed';
	
	private ?array $_config = null;
	
	private function getConfig() {
		if(is_null($this->_config)) {
			$portal_code = ChPortalHelper::getCode();
			$this->_config = DAO_CommunityToolProperty::getAllByTool($portal_code);
		}
		
		return $this->_config;
	}
	
	/**
	 * @param Model_CommunityTool $instance
	 */
	public function configure(Model_CommunityTool $instance) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('portal', $instance);
		
		$params = DAO_CommunityToolProperty::getAllByTool($instance->code);
		$tpl->assign('params', $params);
		
		$tpl->display('devblocks:cerb.website.interactions::portal/config.tpl');
	}
	
	public function saveConfiguration(Model_CommunityTool $instance) {
		@$params = DevblocksPlatform::importGPC($_POST['params'],'array',[]);
		
		if(array_key_exists(self::PARAM_AUTOMATIONS_KATA, $params)) {
			$value = strval($params[self::PARAM_AUTOMATIONS_KATA]);
			DAO_CommunityToolProperty::set($instance->code, self::PARAM_AUTOMATIONS_KATA, $value);
		}
		
		if(array_key_exists(self::PARAM_CORS_ORIGINS_ALLOWED, $params)) {
			$value = strval($params[self::PARAM_CORS_ORIGINS_ALLOWED]);
			DAO_CommunityToolProperty::set($instance->code, self::PARAM_CORS_ORIGINS_ALLOWED, $value);
		}
	}
	
	/**
	 * @param DevblocksHttpRequest
	 * @return DevblocksHttpResponse
	 */
	public function handleRequest(DevblocksHttpRequest $request) {
		return null;
	}
	
	public function _respondWithCORS() {
		$config = $this->getConfig();
		
		if(!array_key_exists(self::PARAM_CORS_ORIGINS_ALLOWED, $config))
			return;
		
		$allowed_origins = array_fill_keys(DevblocksPlatform::parseCrlfString($config[self::PARAM_CORS_ORIGINS_ALLOWED]), true);
		
		if(!array_key_exists('HTTP_ORIGIN', $_SERVER))
			return;
		
		$origin = rtrim(DevblocksPlatform::strLower($_SERVER['HTTP_ORIGIN']), '/');
		
		if(!array_key_exists('*', $allowed_origins) && !array_key_exists($origin, $allowed_origins))
			return;
		
		header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
		header('Access-Control-Allow-Methods: POST');
		header('Access-Control-Allow-Headers: User-Agent, Content-Type');
	}
	
	public function writeResponse(DevblocksHttpResponse $response) {
		$path = $response->path;
		$stack = array_shift($path);
		
		switch($stack) {
			case 'interaction':
				// Require an HTTP POST
				if('POST' != DevblocksPlatform::getHttpMethod())
					DevblocksPlatform::dieWithHttpError(null, 405);
				
				// Add conditional CORS headers
				$this->_respondWithCORS();
					
				// For cache invalidation
				header(sprintf('X-Cerb-Version: %s',  APP_BUILD));
				
				$action = array_shift($path);
				
				switch($action) {
					case 'start':
						$this->_handleInteractionStart();
						break;
						
					case 'continue':
						$continuation_token = DevblocksPlatform::importGPC($_POST['continuation_token'] ?? null, 'string');
						
						$this->_handleInteractionContinue($continuation_token);
						break;
						
					case 'invoke':
						$continuation_token = DevblocksPlatform::importGPC($_POST['continuation_token'] ?? null, 'string');
						$this->_handleInteractionInvoke($continuation_token);
						break;
				}
				break;
				
			case 'assets':
				$file = array_shift($path);
				
				switch($file) {
					case 'cerb.js':
						// Allow caching, but invalidate from the `X-Cerb-Version` header
						$ttl_secs = 86400; // 1 day
						header('Content-Type: text/javascript');
						header('Pragma: cache');
						header(sprintf('Cache-control: max-age=%d', $ttl_secs));
						header(sprintf('Expires: %s GMT', gmdate('D, d M Y H:i:s', time() + $ttl_secs)));
						
						$tpl = DevblocksPlatform::services()->templateSandbox();
						$tpl->display('devblocks:cerb.website.interactions::public/cerb.js');
						break;
				}
				break;
				
			default:
				header('Content-Type: text/html');
				$tpl = DevblocksPlatform::services()->templateSandbox();
				
				if(null != ($interaction = $stack)) {
					$interaction_params = DevblocksPlatform::services()->url()->arrayToQueryString($_GET ?? []);
					
					$tpl->assign('page_interaction', $interaction);
					$tpl->assign('page_interaction_params', $interaction_params);
				}
				
				$tpl->display('devblocks:cerb.website.interactions::public/index.tpl');
				break;
		}
	}
	
	private function _handleInteractionStart() {
		$event_handler = DevblocksPlatform::services()->ui()->eventHandler();
		
		$portal = ChPortalHelper::getPortal();
		$config = $this->getConfig();
		$user_agent = DevblocksPlatform::getClientUserAgent();
		
		$interaction = DevblocksPlatform::importGPC($_POST['interaction'] ?? null, 'string');
		
		$interaction_params = DevblocksPlatform::strParseQueryString(
			DevblocksPlatform::importGPC($_POST['interaction_params'] ?? null, 'string')
		);
		
		$error = null;
		
		$toolbar_dict = DevblocksDictionaryDelegate::instance([
			'interaction' => $interaction,
			'interaction_params' => $interaction_params,
			
			'portal__context' => CerberusContexts::CONTEXT_PORTAL,
			'portal_id' => $portal->id ?? 0,
			
			'client_ip' => DevblocksPlatform::getClientIp(),
			'client_browser_name' => $user_agent['browser'] ?? null,
			'client_browser_platform' => $user_agent['platform'] ?? null,
			'client_browser_version' => $user_agent['version'] ?? null,
		]);
		
		$handlers = $event_handler->parse($config[self::PARAM_AUTOMATIONS_KATA] ?? '', $toolbar_dict, $error);
		$handler = null;
		
		// This is only part of the events KATA toolbar, not the trigger
		$initial_state = [
			'interaction' => $interaction,
			'interaction_params' => $interaction_params,
			
			'portal__context' => CerberusContexts::CONTEXT_PORTAL,
			'portal_id' => $portal->id ?? 0,
			
			'client_ip' => DevblocksPlatform::getClientIp(),
			'client_browser_name' => $user_agent['browser'] ?? null,
			'client_browser_platform' => $user_agent['platform'] ?? null,
			'client_browser_version' => $user_agent['version'] ?? null,
		];
		
		$automation_results = $event_handler->handleOnce(
			AutomationTrigger_InteractionWebsite::ID,
			$handlers,
			$initial_state,
			$error,
			null,
			$handler
		);
		
		if(false == $automation_results) {
			error_log('Interaction error:' . $error);
			DevblocksPlatform::dieWithHttpError("null automation results", 404);
		}
		
		// [TODO] Limit the continuation to this session/identity/visitor
		$state_data = [
			'trigger' => AutomationTrigger_InteractionWebsite::ID,
			'dict' => $automation_results->getDictionary(),
		];
		
		$continuation_token = DAO_AutomationContinuation::create([
			DAO_AutomationContinuation::UPDATED_AT => time(),
			DAO_AutomationContinuation::EXPIRES_AT => time() + 1200, // 20 mins
			DAO_AutomationContinuation::STATE => $automation_results->getKeyPath('__exit'),
			DAO_AutomationContinuation::STATE_DATA => json_encode($state_data),
			DAO_AutomationContinuation::URI => $handler->name,
		]);
		
		$tpl = DevblocksPlatform::services()->templateSandbox();
		$tpl->assign('continuation_token', $continuation_token);
		$tpl->display('devblocks:cerb.website.interactions::public/popup.tpl');
	}
	
	private function _handleInteractionContinue(string $continuation_token) : void {
		// Get all continuations by root token
		// [TODO] Reassemble the call hierarchy
		// $continuations = DAO_AutomationContinuation::getByRootToken($continuation_token);
		
		if(false == ($continuation = DAO_AutomationContinuation::getByToken($continuation_token)))
			DevblocksPlatform::dieWithHttpError("null continuation token", 404);
		
		$initial_state = $continuation->state_data['dict'] ?? [];
		
		if(array_key_exists('__return', $initial_state)) {
			if(array_key_exists('interaction', $initial_state['__return'])) {
				$this->_handleAwaitInteraction($continuation);
			} else if(array_key_exists('form', $initial_state['__return'])) {
				$this->_handleAwaitForm($continuation);
			}
 		}
	}
	
	private function _handleInteractionInvoke(string $continuation_token) : void {
		if(false == ($continuation = DAO_AutomationContinuation::getByToken($continuation_token)))
			DevblocksPlatform::dieWithHttpError("null continuation token", 404);
		
		$form_components = AutomationTrigger_InteractionWebsite::getFormComponentMeta();
		
		$initial_state = $continuation->state_data['dict'] ?? [];
		$last_prompts = $initial_state['__return']['form']['elements'] ?? [];
		
		$prompt_key = rtrim(DevblocksPlatform::importGPC($_POST['prompt_key'] ?? null, 'string'), '/');
		$prompt_action = DevblocksPlatform::importGPC($_POST['prompt_action'] ?? null, 'string');
		
		if(!array_key_exists($prompt_key, $last_prompts))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$last_prompt = $last_prompts[$prompt_key];
		
		list($prompt_type, $prompt_set_key) = array_pad(explode('/', $prompt_key), 2, null);
		
		if(array_key_exists($prompt_type, $form_components)) {
			$component = new $form_components[$prompt_type]($prompt_set_key, null, $last_prompt);
			$component->invoke($prompt_key, $prompt_action, $continuation);
		}
	}
	
	private function _handleAwaitForm(Model_AutomationContinuation $continuation) {
		$automator = DevblocksPlatform::services()->automation();
		$validation = DevblocksPlatform::services()->validation();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		$prompts_without_output = ['say','submit'];
		
		$form_components = AutomationTrigger_InteractionWebsite::getFormComponentMeta();
		
		$prompts = DevblocksPlatform::importGPC($_POST['prompts'] ?? null, 'array', []);
		$is_submit = DevblocksPlatform::importGPC($_POST['__submit'] ?? null, 'bool', false);
		
		unset($_POST);
		
		$initial_state = $continuation->state_data['dict'] ?? [];
		
		if(false == ($automation = $continuation->getAutomation()))
			DevblocksPlatform::dieWithHttpError("No automation", 404);
		
		if($automation->extension_id != AutomationTrigger_InteractionWebsite::ID)
			DevblocksPlatform::dieWithHttpError("Wrong extension", 403);
		
		// Clear validation errors
		unset($initial_state['__return']['form']['elements']['say/__validation']);
		
		if($is_submit) {
			$last_prompts = $initial_state['__return']['form']['elements'] ?? [];
			$validation_errors = [];
			$validation_values = [];
			
			foreach ($last_prompts as $last_prompt_key => $last_prompt) {
				list($last_prompt_type, $prompt_set_key) = array_pad(explode('/', $last_prompt_key, 2), 2, null);
				
				if (!$prompt_set_key)
					continue;
				
				if (array_key_exists($last_prompt_type, $form_components)) {
					if(in_array($last_prompt_type, $prompts_without_output))
						continue;
					
					$prompt_value = $prompts[$prompt_set_key] ?? null;
					
					$is_required = array_key_exists('required', $last_prompt) && $last_prompt['required'];
					
					$is_set = (is_string($prompt_value) && strlen($prompt_value))
						|| (is_array($prompt_value) && count($prompt_value));
					
					if ($is_required || $is_set) {
						$component = new $form_components[$last_prompt_type]($prompt_set_key, $prompt_value, $last_prompt);
						
						$component->validate($validation);
						
						$validation_values[$prompt_set_key] = $prompt_value;
						
						// Run custom validation if it exists
						if(array_key_exists('validation', $last_prompt)) {
							$validation_set_key = $prompt_set_key . '__custom';
							$validation_dict = DevblocksDictionaryDelegate::instance($initial_state);
							$validation_dict->set($prompt_set_key, $prompt_value);
							$validation_error = trim($tpl_builder->build($last_prompt['validation'], $validation_dict));
							
							if($validation_error) {
								$validation_values[$validation_set_key] = $prompt_value;
								
								$validation
									->addField($validation_set_key, $last_prompt['label'] ?? $prompt_set_key)
									->error()
									->setError($validation_error)
								;
							}
						}
					}
					
					$initial_state[$prompt_set_key] = $prompt_value;
				}
			}
			
			if ($validation_values) {
				if (false === $validation->validateAll($validation_values, $error))
					$validation_errors[] = $error;
				
				$initial_state = array_merge($initial_state, $validation_values);
			}
			
			if($validation_errors) {
				$initial_state['__return']['form']['elements'] = [
						'say/__validation' => [
							'content' => sprintf("# Correct the following errors to continue:\n%s",
								implode("\n", array_map(function($error) {
									return '* ' . rtrim($error);
								}, $validation_errors))
							),
							'style' => 'error',
						]
					] + $last_prompts;
				
				$automation_results = DevblocksDictionaryDelegate::instance($initial_state);
				
			} else {
				// Format dictionary keys
				foreach($last_prompts as $last_prompt_key => $last_prompt) {
					list($last_prompt_type, $prompt_set_key) = array_pad(explode('/', $last_prompt_key, 2), 2, null);
					$prompt_value = $prompts[$prompt_set_key] ?? null;
					
					if(array_key_exists($last_prompt_type, $form_components)) {
						if(in_array($last_prompt_type, $prompts_without_output))
							continue;
						
						$component = new $form_components[$last_prompt_type]($prompt_set_key, $prompt_value, $last_prompt);
						$initial_state[$prompt_set_key] = $component->formatValue();
					}
				}
				
				if(false === ($automation_results = $automator->executeScript($automation, $initial_state, $error))) {
					$initial_state['__exit'] = 'await';
					$initial_state['__return'] = [
						'form' => [
							'elements' => [
								'say/__validation' => [
									'content' => $error,
									'style' => 'error',
								],
							],
						],
					];
					$automation_results = DevblocksDictionaryDelegate::instance($initial_state);
				}
			}
			
		} else {
			$automation_results = DevblocksDictionaryDelegate::instance($initial_state);
		}
		
		if($automation_results->getKeyPath('__return.interaction')) {
			$this->_respondAwaitInteraction($automation_results, $continuation);
		} else {
			$this->_respondAwaitForm($automation_results, $continuation);
		}
	}
	
	private function _handleAwaitInteraction(Model_AutomationContinuation $continuation) : void {
		$delegate_token = $continuation->state_data['dict']['__return']['interaction']['token'] ?? null;
		
		if(null == $delegate_token)
			DevblocksPlatform::dieWithHttpError("Null delegate", 404);
		
		if(false == ($delegate_continuation = DAO_AutomationContinuation::getByToken($delegate_token)))
			DevblocksPlatform::dieWithHttpError("Null delegate continuation", 404);
		
		// Is the delegate completed?
		
		if('await' != $delegate_continuation->state) {
			$automator = DevblocksPlatform::services()->automation();
			
			if(false == ($automation = $continuation->getAutomation()))
				DevblocksPlatform::dieWithHttpError("Null delegate automation", 404);
			
			$error = null;
			$initial_state = $continuation->state_data['dict'];
			
			if(false === ($automation_results = $automator->executeScript($automation, $initial_state, $error))) {
				$initial_state['__exit'] = 'error';
				$initial_state['__error'] = [
					'message' => 'An unexpected error occurred.'
				];
				$automation_results = DevblocksDictionaryDelegate::instance($initial_state);
			}
			
			if($automation_results->getKeyPath('__return.interaction')) {
				$this->_respondAwaitInteraction($automation_results, $continuation);
			} else {
				$this->_respondAwaitForm($automation_results, $continuation);
			}
			
			return;
		}
		
		$initial_state = $delegate_continuation->state_data['dict'] ?? [];
		
		if(array_key_exists('__return', $initial_state)) {
			if(array_key_exists('interaction', $initial_state['__return'])) {
				$this->_handleAwaitInteraction($delegate_continuation);
			} else if(array_key_exists('form', $initial_state['__return'])) {
				$this->_handleAwaitForm($delegate_continuation);
			}
		}
	}
	
	private function _respondAwaitForm(DevblocksDictionaryDelegate $automation_results, Model_AutomationContinuation $continuation) {
		$form_components = AutomationTrigger_InteractionWebsite::getFormComponentMeta();
		
		$exit_code = $automation_results->get('__exit');
		
		$form_title = $automation_results->getKeyPath('__return.form.title');
		
		if($form_title) {
			$tpl = DevblocksPlatform::services()->templateSandbox();
			$tpl->assign('popup_title', $form_title);
			$tpl->display('devblocks:cerberusweb.core::automations/triggers/interaction.website/await/_set_popup_title.tpl');
		}
		
		$continuation->state_data['dict'] = $automation_results->getDictionary();
		
		$elements = $automation_results->getKeyPath('__return.form.elements', []);
		
		// Synthesize a submit button on await
		if('await' == $exit_code) {
			if(!array_key_exists('submit', $elements)) {
				$submits = array_filter(array_keys($elements), function($element_key) {
					return ($element_key == 'submit' || DevblocksPlatform::strStartsWith($element_key, 'submit/'));
				});
				
				if(!$submits) {
					$elements['submit/' . uniqid()] = [
						'continue' => true,
						'reset' => false,
					];
				}
			}
			
			// Wait up to a day
			$continuation->expires_at = time() + 86400;
			
			// Synthesize an end action on other states
		} else {
			// We just finished a delegate interaction
			if(null != ($parent_continuation = $continuation->getParent())) {
				// Save delegate state
				DAO_AutomationContinuation::update($continuation->token, [
					DAO_AutomationContinuation::STATE => $exit_code,
					DAO_AutomationContinuation::STATE_DATA => json_encode($continuation->state_data),
					DAO_AutomationContinuation::UPDATED_AT => time(),
				]);
				
				if(null != ($output_placeholder = $parent_continuation->state_data['dict']['__return']['interaction']['output'] ?? null)) {
					$parent_continuation->state_data['dict'][$output_placeholder] = $automation_results->get('__return', []);
				}
				
				// Save parent state
				DAO_AutomationContinuation::update($parent_continuation->token, [
					DAO_AutomationContinuation::STATE_DATA => json_encode($parent_continuation->state_data),
					DAO_AutomationContinuation::UPDATED_AT => time(),
				]);
				
				$this->_handleAwaitInteraction($parent_continuation);
				return;
				
			} else { // Not a delegate
				$elements['end/' . uniqid()] = $automation_results->get('__return', []);
			}
		}
		
		foreach($elements as $element_key => $element_data) {
			list($action_key_type, $var) = array_pad(explode('/', $element_key, 2), 2, null);
			
			if(is_array($element_data) && array_key_exists('hidden', $element_data) && $element_data['hidden'])
				continue;
			
			if(array_key_exists($action_key_type, $form_components)) {
				$value = $automation_results->get($var, null);
				
				if(!array_key_exists($action_key_type, $form_components))
					continue;
				
				$component = new $form_components[$action_key_type]($var, $value, $element_data);
				$component->render($continuation);
			}
		}
		
		// Save session scope
		DAO_AutomationContinuation::update($continuation->token, [
			DAO_AutomationContinuation::STATE => $exit_code,
			DAO_AutomationContinuation::STATE_DATA => json_encode($continuation->state_data),
			DAO_AutomationContinuation::EXPIRES_AT => $continuation->expires_at,
			DAO_AutomationContinuation::UPDATED_AT => time(),
		]);		
	}
	
	private function _respondAwaitInteraction(DevblocksDictionaryDelegate $automation_results, Model_AutomationContinuation $continuation) {
		$event_handler = DevblocksPlatform::services()->ui()->eventHandler();
		
		// Must have a URI
		if(false == ($interaction_uri = trim($automation_results->getKeyPath('__return.interaction.uri'))))
			DevblocksPlatform::dieWithHttpError("invalid return interaction uri", 404);
		
		$handler_name = uniqid();
		
		$handlers = [
			$handler_name => [
				'id' => 'automation/' . $handler_name,
				'type' => 'automation',
				'key' => $handler_name,
				'data' => [
					'uri' => $interaction_uri,
				]
			]
		];
		
		$initial_state = [
			'inputs' => $automation_results->getKeyPath('__return.interaction.inputs', []),
			'portal__context' => $automation_results->get('portal__context'),
			'portal_id' => $automation_results->get('portal_id'),
			'client_ip' => $automation_results->get('client_ip'),
			'client_browser_name' => $automation_results->get('client_browser_name'),
			'client_browser_platform' => $automation_results->get('client_browser_platform'),
			'client_browser_version' => $automation_results->get('client_browser_version'),
		];
		
		$delegate_results = $event_handler->handleOnce(
			AutomationTrigger_InteractionWebsite::ID,
			$handlers,
			$initial_state,
			$error,
			null,
			$handler
		);
		
		if(false == $delegate_results)
			DevblocksPlatform::dieWithHttpError("null delegate results", 404);
		
		$state_data = [
			'trigger' => AutomationTrigger_InteractionWebsite::ID,
			'dict' => $delegate_results->getDictionary(),
		];
		
		// Create a new continuation to track the delegate
		
		$delegate_continuation = new Model_AutomationContinuation();
		$delegate_continuation->updated_at = time();
		$delegate_continuation->expires_at = time() + 1200;
		$delegate_continuation->state = $delegate_results->getKeyPath('__exit');
		$delegate_continuation->state_data = $state_data;
		$delegate_continuation->uri = $handler->name;
		
		$delegate_continuation->token = DAO_AutomationContinuation::create([
			DAO_AutomationContinuation::PARENT_TOKEN => $continuation->token,
			DAO_AutomationContinuation::ROOT_TOKEN => $continuation->root_token ?: $continuation->token,
			DAO_AutomationContinuation::UPDATED_AT => $delegate_continuation->updated_at,
			DAO_AutomationContinuation::EXPIRES_AT => $delegate_continuation->expires_at,
			DAO_AutomationContinuation::STATE => $delegate_continuation->state,
			DAO_AutomationContinuation::STATE_DATA => json_encode($delegate_continuation->state_data),
			DAO_AutomationContinuation::URI => $delegate_continuation->uri,
		]);
		
		// Update the parent continuation with the delegate token
		
		$continuation->state_data['dict'] = $automation_results->getDictionary();
		$continuation->state_data['dict']['__return']['interaction']['token'] = $delegate_continuation->token;
		
		DAO_AutomationContinuation::update($continuation->token, [
			DAO_AutomationContinuation::STATE_DATA => json_encode($continuation->state_data),
		]);
		
		if($delegate_results->getKeyPath('__return.interaction')) {
			$this->_respondAwaitInteraction($delegate_results, $delegate_continuation);
		} else {
			$this->_respondAwaitForm($delegate_results, $delegate_continuation);
		}
	}
}