<?php /** @noinspection PhpUnused DuplicatedCode */

class Portal_WebsiteInteractions extends Extension_CommunityPortal {
	const ID = 'cerb.website.interactions';
	
	const PARAM_AUTOMATIONS_KATA = 'automations_kata';
	const PARAM_CORS_ORIGINS_ALLOWED = 'cors_origins_allowed';
	const PARAM_PORTAL_KATA = 'portal_kata';
	
	private ?array $_config = null;
	private ?CerbPortalWebsiteInteractions_Model $_schema = null;
	
	private function getConfig() {
		if(is_null($this->_config)) {
			$portal_code = ChPortalHelper::getCode();
			$this->_config = DAO_CommunityToolProperty::getAllByTool($portal_code);
		}
		
		return $this->_config;
	}
	
	private function _getPortalSchema() : CerbPortalWebsiteInteractions_Model {
		if(!is_null($this->_schema))
			return $this->_schema;
		
		$kata = DevblocksPlatform::services()->kata();
		$portal = ChPortalHelper::getPortal();
		$config = $this->getConfig();
			
		if(false === ($portal_schema = $kata->parse($config[self::PARAM_PORTAL_KATA] ?? '', $error)))
			$portal_schema = [];
		
		$portal_dict = DevblocksDictionaryDelegate::instance([]);
		$portal_dict->mergeKeys('portal_', DevblocksDictionaryDelegate::getDictionaryFromModel($portal, CerberusContexts::CONTEXT_PORTAL));
		
		if(false === ($portal_schema = $kata->formatTree($portal_schema, $portal_dict, $error)))
			$portal_schema = [];
		
		return new CerbPortalWebsiteInteractions_Model($portal_schema);
	}
	
	/**
	 * @param Model_CommunityTool $instance
	 * @throws SmartyException
	 */
	public function configure(Model_CommunityTool $instance) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('portal', $instance);
		
		$params = DAO_CommunityToolProperty::getAllByTool($instance->code);
		$tpl->assign('params', $params);
		
		$tpl->display('devblocks:cerb.website.interactions::portal/config.tpl');
	}
	
	public function saveConfiguration(Model_CommunityTool $instance) {
		$params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array',[]);
		
		if(array_key_exists(self::PARAM_AUTOMATIONS_KATA, $params)) {
			$value = strval($params[self::PARAM_AUTOMATIONS_KATA]);
			DAO_CommunityToolProperty::set($instance->code, self::PARAM_AUTOMATIONS_KATA, $value);
		}
		
		if(array_key_exists(self::PARAM_CORS_ORIGINS_ALLOWED, $params)) {
			$value = strval($params[self::PARAM_CORS_ORIGINS_ALLOWED]);
			DAO_CommunityToolProperty::set($instance->code, self::PARAM_CORS_ORIGINS_ALLOWED, $value);
		}
		
		if(array_key_exists(self::PARAM_PORTAL_KATA, $params)) {
			$value = strval($params[self::PARAM_PORTAL_KATA]);
			DAO_CommunityToolProperty::set($instance->code, self::PARAM_PORTAL_KATA, $value);
		}
	}
	
	/**
	 * @param DevblocksHttpRequest
	 * @return DevblocksHttpResponse|null
	 */
	public function handleRequest(DevblocksHttpRequest $request) {
		return null;
	}
	
	public function _respondWithCORS() {
		$config = $this->getConfig();
		
		if(!array_key_exists('HTTP_ORIGIN', $_SERVER))
			return;
		
		$origin = rtrim(DevblocksPlatform::strLower($_SERVER['HTTP_ORIGIN']), '/');
		
		if(array_key_exists(self::PARAM_CORS_ORIGINS_ALLOWED, $config) && $config[self::PARAM_CORS_ORIGINS_ALLOWED]) {
			$allowed_origins = array_fill_keys(DevblocksPlatform::parseCrlfString($config[self::PARAM_CORS_ORIGINS_ALLOWED]), true);
		} else {
			$allowed_origins = [$origin => true];	
		}
		
		if(!array_key_exists('*', $allowed_origins) && !array_key_exists($origin, $allowed_origins))
			return;
		
		header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
		header('Access-Control-Allow-Methods: GET, POST');
		header('Access-Control-Allow-Headers: User-Agent, Content-Type');
	}
	
	public function writeResponse(DevblocksHttpResponse $response) {
		$path = $response->path;
		$stack = array_shift($path);
		
		$portal = ChPortalHelper::getPortal();
		$session = ChPortalHelper::getSession();
		
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
					case 'logo':
						$portal_schema = $this->_getPortalSchema();
						
						if(!($logo = $portal_schema->getLogo()))
							break;
				
						$this->_renderPortalImage($logo);
						break;
						
					case 'favicon':
						$portal_schema = $this->_getPortalSchema();
						
						if(!($icon = $portal_schema->getFavicon()))
							break;
				
						$this->_renderPortalImage($icon);
						break;
						
					case 'cerb.css':
						header('Content-Type: text/css');
						$tpl = DevblocksPlatform::services()->templateSandbox();
						$tpl->display('devblocks:cerb.website.interactions::public/cerb.css');
						break;
						
					case 'cerb.js':
						// Add conditional CORS headers
						$this->_respondWithCORS();
						
						// Allow caching, but invalidate from the `X-Cerb-Version` header
						$ttl_secs = 86400; // 1 day
						header('Content-Type: text/javascript');
						header('Pragma: cache');
						header(sprintf('Cache-control: max-age=%d', $ttl_secs));
						header(sprintf('Expires: %s GMT', gmdate('D, d M Y H:i:s', time() + $ttl_secs)));
						
						$tpl = DevblocksPlatform::services()->templateSandbox();
						$tpl->assign('cerb_app_build', APP_BUILD);
						$tpl->display('devblocks:cerb.website.interactions::public/cerb.js');
						break;
						
					case 'image':
						$portal_schema = $this->_getPortalSchema();
						$portal_code = ChPortalHelper::getCode();
						
						$hash = array_shift($path);
						$file = array_shift($path);
						
						$secret = $portal_schema->getImageRequestsSecret() ?? sha1(DevblocksPlatform::services()->encryption()->getSystemKey());
						$hash_calc = hash_hmac('sha256', implode('/',[$file,$portal_code]), $secret);
						
						// If the signature doesn't match or is expired, forbid
						if(!($hash===$hash_calc))
							DevblocksPlatform::dieWithHttpError(null, 403);
						
						if(($resource = \DAO_Resource::getByNameAndType($file, \ResourceType_PortalImage::ID))) {
							$this->_renderPortalImage($resource);
						}
						break;
				}
				break;
				
			default:
				$tpl = DevblocksPlatform::services()->templateSandbox();
				$portal_schema = $this->_getPortalSchema();
				
				header('Content-Type: text/html');
				
				$csp = $portal_schema->getContentSecurityPolicy();
				
				$csp_header = sprintf("Content-Security-Policy: default-src 'self'; img-src 'self' %s; script-src 'nonce-%s'; object-src 'none';",
					implode(' ', $csp['imageHosts'] ?? []),
					$session->nonce
				);
				
				header($csp_header);
				
				if(null != ($interaction = $stack)) {
					$interaction_params = DevblocksPlatform::services()->url()->arrayToQueryString($_GET ?? []);
					
					$tpl->assign('page_interaction', $interaction);
					$tpl->assign('page_interaction_params', $interaction_params);
				}
				
				$tpl->assign('session', $session);
				$tpl->assign('portal', $portal);
				$tpl->assign('portal_schema', $portal_schema);
				$tpl->display('devblocks:cerb.website.interactions::public/index.tpl');
				break;
		}
	}
	
	public static function parseMarkdown($string) : string {
		$parser = new CerbMarkdown_InteractionWebsite();
		$parser->setBreaksEnabled(true);
		$parser->setMarkupEscaped(true);
		$parser->setSafeMode(true);
		
		return $parser->parse($string);
	}
	
	private function _renderPortalImage(Model_Resource $resource) {
		if(!($resource_type = $resource->getExtension()))
			DevblocksPlatform::dieWithHttpError('Not found', 404);
		
		// Only portal images
		if($resource_type->id != ResourceType_PortalImage::ID)
			DevblocksPlatform::dieWithHttpError('Forbidden', 403);
		
		if(false == ($resource_content = $resource_type->getContentData($resource)))
			DevblocksPlatform::dieWithHttpError(null, 500);
		
		if($resource_content->error) {
			DevblocksPlatform::dieWithHttpError($resource_content->error, 500);
		}
		
		if($resource_content->expires_at) {
			$resource_content->headers =
				array_merge(
					$resource_content->headers,
					[
						'Pragma: cache',
						sprintf('Cache-control: max-age=%d', $resource_content->expires_at - time()),
						'Expires: ' . gmdate('D, d M Y H:i:s', $resource_content->expires_at) . ' GMT',
						'Accept-Ranges: bytes',
					]
				)
			;
		}
		
		$resource_content->writeHeaders();
		$resource_content->writeBody();		
	}
	
	private function _startInteractionAutomationSession(string $interaction, array $interaction_params=[], $continuation_token=null) : array {
		$event_handler = DevblocksPlatform::services()->ui()->eventHandler();
		
		$portal = ChPortalHelper::getPortal();
		$config = $this->getConfig();
		$user_agent = DevblocksPlatform::getClientUserAgent();
		
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
		
		$handler = null;
		
		if(!($handlers = $event_handler->parse($config[self::PARAM_AUTOMATIONS_KATA] ?? '', $toolbar_dict, $error))) {
			error_log('Interaction error:' . $error);
			DevblocksPlatform::dieWithHttpError("null automation results", 404);
		}
		
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
		
		if(!$automation_results) {
			error_log('Interaction error:' . $error);
			DevblocksPlatform::dieWithHttpError("null automation results", 404);
		}
		
		// [TODO] Limit the continuation to this session/identity/visitor
		$state_data = [
			'trigger' => AutomationTrigger_InteractionWebsite::ID,
			'interaction' => $interaction,
			'interaction_params' => $interaction_params,
			'dict' => $automation_results->getDictionary(),
		];
		
		if($continuation_token) {
			DAO_AutomationContinuation::update($continuation_token, [
				DAO_AutomationContinuation::UPDATED_AT => time(),
				DAO_AutomationContinuation::STATE_DATA => json_encode($state_data),
			]);
			
		} else {
			$continuation_token = DAO_AutomationContinuation::create([
				DAO_AutomationContinuation::UPDATED_AT => time(),
				DAO_AutomationContinuation::EXPIRES_AT => time() + 1200, // 20 mins
				DAO_AutomationContinuation::STATE => $automation_results->getKeyPath('__exit'),
				DAO_AutomationContinuation::STATE_DATA => json_encode($state_data),
				DAO_AutomationContinuation::URI => $handler->name,
			]);
		}
		
		return [
			'token' => $continuation_token,
			'state_data' => $state_data,
		];
	}
	
	private function _handleInteractionStart() {
		$interaction = DevblocksPlatform::importGPC($_POST['interaction'] ?? null, 'string');
		
		$interaction_params = DevblocksPlatform::strParseQueryString(
			DevblocksPlatform::importGPC($_POST['interaction_params'] ?? null, 'string')
		);
		
		list($continuation_token,) = array_values(
			$this->_startInteractionAutomationSession(
				$interaction,
				$interaction_params
			)
		);
		
		$tpl = DevblocksPlatform::services()->templateSandbox();
		$tpl->assign('continuation_token', $continuation_token);
		$tpl->display('devblocks:cerb.website.interactions::public/popup.tpl');
	}
	
	private function _handleInteractionContinue(string $continuation_token) : void {
		// Get all continuations by root token
		// [TODO] Reassemble the call hierarchy
		// $continuations = DAO_AutomationContinuation::getByRootToken($continuation_token);
		
		if(!($continuation = DAO_AutomationContinuation::getByToken($continuation_token)))
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
		if(!($continuation = DAO_AutomationContinuation::getByToken($continuation_token)))
			DevblocksPlatform::dieWithHttpError("null continuation token", 404);
		
		$form_components = AutomationTrigger_InteractionWebsite::getFormComponentMeta();
		$portal_schema = $this->_getPortalSchema();
		
		$initial_state = $continuation->state_data['dict'] ?? [];
		$last_prompts = ($initial_state['__return']['form']['elements'] ?? null) ?: [];
		
		$prompt_key = rtrim(DevblocksPlatform::importGPC($_POST['prompt_key'] ?? null, 'string'), '/');
		$prompt_action = DevblocksPlatform::importGPC($_POST['prompt_action'] ?? null, 'string');
		
		if(!array_key_exists($prompt_key, $last_prompts))
			DevblocksPlatform::dieWithHttpError(null, 404);
		
		$last_prompt = $last_prompts[$prompt_key];
		
		list($prompt_type, $prompt_set_key) = array_pad(explode('/', $prompt_key), 2, null);
		
		if(array_key_exists($prompt_type, $form_components)) {
			$component = new $form_components[$prompt_type]($prompt_set_key, null, $last_prompt, $portal_schema);
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
		$is_reset = DevblocksPlatform::importGPC($_POST['__reset'] ?? null, 'bool', false);
		
		unset($_POST);
		
		if($is_reset) {
			if($continuation->root_token) {
				// Exit this continuation
				DAO_AutomationContinuation::update($continuation->token,[
					DAO_AutomationContinuation::STATE => 'exit',
				]);
				$continuation = $continuation->getRoot();
			}
			
			$this->_startInteractionAutomationSession($continuation->state_data['interaction'], $continuation->state_data['interaction_params'], $continuation->token);
			$continuation = DAO_AutomationContinuation::getByToken($continuation->token);
		}
		
		$initial_state = $continuation->state_data['dict'] ?? [];
		
		if(!($automation = $continuation->getAutomation()))
			DevblocksPlatform::dieWithHttpError("No automation", 404);
		
		if($automation->extension_id != AutomationTrigger_InteractionWebsite::ID)
			DevblocksPlatform::dieWithHttpError("Wrong extension", 403);
		
		// Clear validation errors
		unset($initial_state['__return']['form']['elements']['say/__validation']);
		
		if($is_submit) {
			$last_prompts = ($initial_state['__return']['form']['elements'] ?? null) ?: [];
			$validation_errors = [];
			$validation_values = [];
			
			$portal_schema = $this->_getPortalSchema();
			
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
						$component = new $form_components[$last_prompt_type]($prompt_set_key, $prompt_value, $last_prompt, $portal_schema);
						
						$component->validate($validation);
						
						$validation_values[$prompt_set_key] = $prompt_value;
						
						// Run custom validation if it exists
						if(array_key_exists('validation', $last_prompt)) {
							$validation_set_key = $prompt_set_key . '__custom';
							$validation_dict = DevblocksDictionaryDelegate::instance($initial_state);
							$component->setValue($prompt_set_key, $prompt_value, $validation_dict);
							
							// The validation template must be a string
							if(is_string($last_prompt['validation'])) {
								$validation_error = trim($tpl_builder->build($last_prompt['validation'], $validation_dict));
								
								if($validation_error) {
									$validation_values[$validation_set_key] = $prompt_value;
									
									$validation
										->addField($validation_set_key, $last_prompt['label'] ?? $prompt_set_key)
										->error()
										->setError($validation_error)
									;
								}
								
							} else {
								$validation_values[$validation_set_key] = false;
								
								$error_message = sprintf("`%s:validation:` must be a string.", $last_prompt_key);
								
								$validation
									->addField($validation_set_key, $last_prompt['label'] ?? $prompt_set_key)
									->error()
									->setError($error_message)
								;
								
								$automation->logError(
									$error_message,
									''
								);
							}
							
							$validation_dict->unset($validation_set_key);
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
						
						$component = new $form_components[$last_prompt_type]($prompt_set_key, $prompt_value, $last_prompt, $portal_schema);
						$initial_state = $component->setValue($prompt_set_key, $component->formatValue(), $initial_state);
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
		$session = \ChPortalHelper::getSession();
		$form_components = AutomationTrigger_InteractionWebsite::getFormComponentMeta();
		
		$exit_code = $automation_results->get('__exit');
		
		$form_title = $automation_results->getKeyPath('__return.form.title');
		
		if($form_title) {
			$tpl = DevblocksPlatform::services()->templateSandbox();
			$tpl->assign('session', $session);
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
		
		$portal_schema = self::_getPortalSchema();
		
		foreach($elements as $element_key => $element_data) {
			list($action_key_type, $var) = array_pad(explode('/', $element_key, 2), 2, null);
			
			if(is_array($element_data) && array_key_exists('hidden', $element_data) && $element_data['hidden'])
				continue;
			
			if(array_key_exists($action_key_type, $form_components)) {
				$value = $automation_results->get($var, null);
				
				$component = new $form_components[$action_key_type]($var, $value, $element_data, $portal_schema);
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

class CerbPortalWebsiteInteractions_Model {
	private array $_schema = [];
	
	function __construct(array $schema) {
		$this->_schema = $schema;
	}
	
	function getTitle() {
		return $this->_schema['layout']['meta']['title'] ?? null;
	}
	
	function getLogoText() {
		if(false == ($logo_text = $this->_schema['layout']['header']['logo']['text'] ?? null))
			return null;

		return $logo_text;
	}
	
	function getLogoImageUri() {
		if(false == ($logo_uri = $this->_schema['layout']['header']['logo']['image']['uri'] ?? null))
			return null;

		return $logo_uri;
	}
	
	function getLogo() {
		if(false == ($logo_uri = $this->getLogoImageUri()))
			return null;
		
		if(false == ($uri_parts = DevblocksPlatform::services()->ui()->parseURI($logo_uri)))
			return null;
		
		if(false == ($resource = DAO_Resource::getByName($uri_parts['context_id'] ?? null)))
			return null;
		
		return $resource;
	}
	
	function getFaviconUri() {
		if(false == ($icon_uri = $this->_schema['layout']['meta']['favicon']['uri'] ?? null))
			return null;

		return $icon_uri;
	}
	
	function getFavicon() {
		if(false == ($icon_uri = $this->getFaviconUri()))
			return null;
		
		if(false == ($uri_parts = DevblocksPlatform::services()->ui()->parseURI($icon_uri)))
			return null;
		
		if(false == ($resource = DAO_Resource::getByName($uri_parts['context_id'] ?? null)))
			return null;
		
		return $resource;
	}
	
	function getBadgeInteraction() {
		return $this->_schema['layout']['badge']['interaction'] ?? null;
	}
	
	function getNavbar() {
		$url_writer = DevblocksPlatform::services()->url();
		
		$base_url = rtrim($url_writer->write('', true), '/');
		
		$navbar = $this->_schema['layout']['header']['navbar'] ?? [];
		
		if(is_iterable($navbar)) {
			foreach($navbar as $k => $v) {
				if(
					is_array($v)
					&& array_key_exists('href', $v)
					&& DevblocksPlatform::strStartsWith($v['href'], '/')
				) {
					$navbar[$k]['href'] = $base_url . $v['href'];
				}
			}
		}
		
		return $navbar;
	}
	
	function getContentSecurityPolicy() {
		return $this->_schema['security']['contentSecurityPolicy'] ?? [];
	}
	
	public function getImageRequestsSecret() {
		return $this->_schema['security']['imageRequests']['secret'] ?? null;
	}
}

class CerbMarkdown_InteractionWebsite extends Parsedown {
	protected $safeLinksWhitelist = [
		'http://',
		'https://',
		'mailto:',
		'tel:',
	];
	
	protected function inlineImage($Excerpt) {
		$image = parent::inlineImage($Excerpt);
		
		$alt = $image['element']['attributes']['alt'] ?? null;
		
		$matches = [];
		
		if($alt && preg_match('#^(.*?) =(\d*)x(\d*)$#', $alt, $matches)) {
			$width = $matches[2];
			$height = $matches[3];
			
			if($width || $height) {
				$image['element']['attributes']['alt'] = $matches[1];
				
				if($width)
					$image['element']['attributes']['width'] = $width;
				
				if($height)
					$image['element']['attributes']['height'] = $height;
			}
		}
		
		return $image;
	}
	
	protected function inlineLink($Excerpt) {
		$url_writer = DevblocksPlatform::services()->url();
		
		$link = parent::inlineLink($Excerpt);
		
		$href = $link['element']['attributes']['href'] ?? null;
		
		if(DevblocksPlatform::strStartsWith($href, '/')) {
			$link['element']['attributes']['href'] = $url_writer->write('') . ltrim($href, '/');
			
		} else if (DevblocksPlatform::strStartsWith($href, ['http:','https:'])) {
			$link['element']['attributes']['target'] = '_blank';
			$link['element']['attributes']['rel'] = 'nofollow noopener';
		}
		
		return $link;
	}
}