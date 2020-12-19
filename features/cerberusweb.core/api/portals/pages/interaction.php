<?php
// [TODO] Show/hide nav menu
// [TODO] Logo
// [TODO] Stylesheet

/**
 * Class PortalPage_Interaction
 */
class PortalPage_Interaction extends Extension_PortalPage {
	const ID = 'cerb.portal.page.interaction';
	
	public function renderConfig(Model_PortalPage $model) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('model', $model);
		$tpl->display('devblocks:cerberusweb.core::portals/builder/pages/interaction/config.tpl');
	}
	
	public function saveConfig(array $fields, $id, &$error=null) {
		if (!array_key_exists(DAO_PortalPage::PARAMS_JSON, $fields)) {
			$error = 'Portal page parameters are required.';
			return false;
		}
		
		if (false === ($params = json_decode($fields[DAO_PortalPage::PARAMS_JSON], true))) {
			$error = 'Unable to read portal parameters.';
			return false;
		}
		
		if (false == ($automations = @$params['automations'])) {
			$error = 'An interaction is required.';
			return false;
		}
		
//		if(false == ($interaction_id = @$automations['cerb.trigger.portal.interaction'])) {
//			$error = 'A bot interaction is required.';
//			return false;
//		}
		
//		if(false == ($interaction = DAO_BotInteraction::get($interaction_id))) {
//			$error = 'The selected bot interaction does not exist.';
//			return false;
//		}
		
		return true;
	}
	
	function invoke(Model_PortalPage $page, Model_CommunityTool $portal, DevblocksHttpResponse $response) {
		if('POST' != DevblocksPlatform::getHttpMethod())
			DevblocksPlatform::dieWithHttpError(null, 405);
		
		//@$invoke = DevblocksPlatform::importGPC($_POST['invoke'], 'string', null);
		
		return false;
	}
	
	// [TODO] Pass a dictionary to pages?
	// [TODO] Convert to bot interactions
	function render(Model_PortalPage $page, Model_CommunityTool $portal, DevblocksHttpResponse $response) {
		$renderer = new Extension_PortalPageRenderer(function() use ($page, $portal, $response) {
			$tpl = DevblocksPlatform::services()->template();
			
			$tpl->assign('page', $page);
			$tpl->assign('page_ext', $this);
			$tpl->assign('portal', $portal);
			$tpl->display('devblocks:cerberusweb.core::portals/builder/pages/interaction.tpl');
		});
		
		// [TODO] Toggle by page
		parent::renderDefaultLayout($page, $portal, $renderer);
		//parent::renderBareLayout($page, $portal, $renderer);
	}
	
	/*
	private function _getStateKey(Model_PortalPage $page) {
		$session = ChPortalHelper::getSession();
		$portal = ChPortalHelper::getPortal();
		
		if(false == $behavior_id = @$page->params['behavior_id'])
			return;
		
		$state_key = sprintf("state:%s:%d:%d:%s",
			$portal->code,
			$page->id,
			$behavior_id,
			$session->session_id
		);
		
		return $state_key;
	}
	*/
	
	// [TODO] Dictionary?
	// [TODO] If the behavior no longer exists, this breaks
	
	/*
	function renderForm(Model_PortalPage $page, Model_CommunityTool $portal) {
		if(false == (@$interaction_id = $page->params['interaction_id']))
			return;
		
		if(false == ($interaction = DAO_BotInteraction::get($interaction_id)))
			return;
		
		if(false == ($bot = $interaction->getBot()))
			return;
		
		$session = ChPortalHelper::getSession();
		
		// Do we have a state for this form by this session?
		
		$state_key = $this->_getStateKey($page);
		
		@$reset = DevblocksPlatform::importGPC($_POST['reset'], 'integer', 0);
		
		if($reset || null == ($state_id = $session->getProperty($state_key, null))) {
			$state_id = $this->_startInteractionAutomationSession($interaction);
			$session->setProperty($state_key, $state_id);
		
		} else {
			// If the session no longer exists, reset it
			if(false == ($bot_session = DAO_BotSession::get($state_id))) {
				$state_id = $this->_startInteractionAutomationSession($interaction);
				$session->setProperty($state_key, $state_id);
			}
		}
		
		$bot_session = DAO_BotSession::get($state_id);
		
		// [TODO] If the behavior exited, then remove the state
		$this->_renderFormState($page, $interaction, $bot_session);
	}
	*/
	
	/*
	private function _startInteractionAutomationSession(Model_Automation $automation, $session_id=null, &$error=null) {
		$portal = ChPortalHelper::getPortal();
		$identity = ChPortalHelper::getIdentity();

		// [TODO] Set scope
		$initial_state = [
			'identity__context' => CerberusContexts::CONTEXT_IDENTITY,
			'identity_id' => $identity->id,
			
			'interaction__context' => CerberusContexts::CONTEXT_AUTOMATION,
			'interaction_id' => $automation->id,
			
			'client_ip' => DevblocksPlatform::getClientIp(),
			
			// [TODO]
//			'page__context' => CerberusContexts::CONTEXT_PORTAL_PAGE,
//			'page_id' => $page->id,
		];
		
		$session_data = [
			'actor' => ['context' => CerberusContexts::CONTEXT_IDENTITY, 'id' => $identity->id],
			'interaction_id' => $automation->id,
			'dict' => $initial_state,
			//'interaction' => $interaction,
			//'interaction_params' => $interaction_params,
			//'client_browser' => $client_browser,
			//'client_browser_version' => $client_browser_version,
			//'client_ip' => $client_ip,
			//'client_platform' => $client_platform,
			//'client_time' => $client_time,
			//'client_url' => $client_url,
		];
		
		if(!$session_id) {
			$session_id = DAO_BotSession::create([
				DAO_BotSession::SESSION_DATA => json_encode($session_data),
				DAO_BotSession::UPDATED_AT => time(),
			]);
		} else {
			DAO_BotSession::update($session_id, [
				DAO_BotSession::SESSION_DATA => json_encode($session_data),
				DAO_BotSession::UPDATED_AT => time(),
			]);
		}
		
		return $session_id;
	}
	*/
	
	private function _renderFormState(Model_PortalPage $page, Model_Automation $automation, Model_BotSession $bot_session) {
		@$prompts = DevblocksPlatform::importGPC($_POST['prompts'], 'array', []);
		@$reset = DevblocksPlatform::importGPC($_POST['reset'], 'integer', 0);
		
//		$identity = ChPortalHelper::getIdentity();
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$automator = DevblocksPlatform::services()->automation();
		
		if(!$automation || !$bot_session)
			return false;
		
		if($automation->extension_id != AutomationTrigger_InteractionWebWorker::ID)
			return false;
		
		// Restart the session
		if($reset) {
			$this->_startInteractionAutomationSession($automation, $bot_session->session_id);
			$bot_session = DAO_BotSession::get($bot_session->session_id);
		}
		
		$initial_state = $bot_session->session_data['dict'];
		$error = null;
		
		unset($initial_state['__return']['respond_error/validation']);
		
		@$last_prompts = $initial_state['__return'] ?: [];
		$validation_errors = [];
		$validation_values = [];
		$validation = DevblocksPlatform::services()->validation();
		
		foreach($last_prompts as $last_prompt_key => $last_prompt) {
			$last_prompt_type = strstr($last_prompt_key, '/', true) ?: $last_prompt_key;
			
			// [TODO] Validation as: email, number, etc.
			
			// [TODO] Checkboxes
			
			switch($last_prompt_type) {
				case 'prompt_choice':
				case 'prompt_choices': // [TODO] Validation
				case 'prompt_text':
					@$last_prompt_validate = $last_prompt['validate'];
					@$prompt_label = $last_prompt['label'];
					@$prompt_set_key = $last_prompt['set'];
					@$prompt_value = $prompts[$prompt_set_key];
					
					if($prompt_set_key) {
						$initial_state[$prompt_set_key] = $prompt_value;
						
						if(is_array($last_prompt_validate)) {
							$field = $validation->addField($prompt_set_key, $prompt_label);
							
							// [TODO] Date
							if(array_key_exists('text', $last_prompt_validate)) {
								$field_type = $field->string();
								
								if(array_key_exists('required', $last_prompt_validate['text']))
									$field_type->setRequired(true);
								
								if(array_key_exists('min_length', $last_prompt_validate['text']))
									$field_type->setMinLength(intval($last_prompt_validate['text']['min_length']));
								
								if(array_key_exists('max_length', $last_prompt_validate['text']))
									$field_type->setMaxLength(intval($last_prompt_validate['text']['max_length']));
								
								if(
									array_key_exists('possible_values', $last_prompt_validate['text'])
									&& is_array($last_prompt_validate['text']['possible_values'])
								)
									$field_type->setPossibleValues($last_prompt_validate['text']['possible_values']);
								
							} else if(array_key_exists('number', $last_prompt_validate)) {
								$field_type = $field->number();
								
								if(array_key_exists('required', $last_prompt_validate['number']))
									$field_type->setRequired(true);
								
								if(array_key_exists('min', $last_prompt_validate['number']))
									$field_type->setMin(intval($last_prompt_validate['number']['min']));
								
								if(array_key_exists('max', $last_prompt_validate['number']))
									$field_type->setMax(intval($last_prompt_validate['number']['max']));
							}
							
							$validation_values[$prompt_set_key] = $prompt_value;
							
						} else if(is_string($last_prompt_validate) && $last_prompt_validate) {
							$validation_error = trim($tpl_builder->build($last_prompt_validate, $initial_state));
							
							if($validation_error) {
								$validation_errors[] = $validation_error;
							}
						}
					}
					break;
			}
		}
		
		if($validation_values) {
			if(false === $validation->validateAll($validation_values, $error))
				$validation_errors[] = $error;
		}
		
		if($validation_errors) {
			// [TODO] Style error
			$initial_state['__return'] = [
				'respond/error_validation' => [
					'message' => sprintf("# Correct the following errors to continue:\n%s",
						implode("\n", array_map(function($error) {
							return '* ' . rtrim($error);
						}, $validation_errors))
					),
					'format' => 'markdown',
					'style' => 'error',
				]
			] + $last_prompts;
			
			$automation_results = DevblocksDictionaryDelegate::instance($initial_state);
			
		} else {
			if(false === ($automation_results = $automator->executeScript($automation, $initial_state, $error))) {
				// [TODO] Handle error
				return;
			}
		}
		
		$exit_code = $automation_results->get('__exit');
		
		$actions = $automation_results->get('__return', []);
		
		//var_dump($actions);
		
		//$tpl->assign('bot', $bot);
		$tpl->assign('bot_name', 'Cerb');
		
		//var_dump($exit_code);
		//var_dump($actions);
		
		$show_reset = true;
		$show_continue = 'await' == $exit_code;
		
		foreach($actions as $action_key => $action_data) {
			$action_key_type = strstr($action_key, '/', true) ?: $action_key;
			
			switch($action_key_type) {
				case 'continue':
					if(is_string($action_data))
						// [TODO] Make this reusable for bools
						$show_continue = in_array(DevblocksPlatform::strLower($action_data), ['false','no','n']) ? false : true;
					break;
				
				case 'reset':
					if(is_string($action_data))
						$show_reset = in_array(DevblocksPlatform::strLower($action_data), ['false','no','n']) ? false : true;
					break;
				
					// [TODO]
//				case 'interaction.end':
//					$this->_resetState($state_key);
//					break;
				
				case 'prompt_choice':
					@$var = $action_data['set'];
					@$label = $action_data['label'];
					@$options = $action_data['options'];
					@$style = $action_data['style'] ?: 'radios';
					@$orientation = $action_data['orientation'] ?: 'horizontal';
					@$default = $action_data['default'];
					
					$tpl->assign('label', $label);
					$tpl->assign('orientation', $orientation);
					$tpl->assign('options', $options);
					$tpl->assign('default', $default);
					$tpl->assign('var', $var);
					$tpl->assign('dict', $automation_results);
					
					if($style == 'buttons') {
						$tpl->display('devblocks:cerberusweb.core::events/form_interaction/portal/prompts/prompt_buttons.tpl');
					} else if($style == 'picklist') {
						$tpl->display('devblocks:cerberusweb.core::events/form_interaction/portal/prompts/prompt_picklist.tpl');
					} else {
						$tpl->display('devblocks:cerberusweb.core::events/form_interaction/portal/prompts/prompt_radios.tpl');
					}
					break;
					
				case 'prompt_choices':
					@$var = $action_data['set'];
					@$label = $action_data['label'];
					@$options = $action_data['options'];
					@$default = $action_data['default'];
					@$orientation = $action_data['orientation'] ?: 'horizontal';
					
					$tpl->assign('label', $label);
					$tpl->assign('orientation', $orientation);
					$tpl->assign('options', $options);
					$tpl->assign('default', $default);
					$tpl->assign('var', $var);
					$tpl->assign('dict', $automation_results);
					
					$tpl->display('devblocks:cerberusweb.core::events/form_interaction/portal/prompts/prompt_checkboxes.tpl');
					break;
				
				case 'prompt_text':
					@$var = $action_data['set'];
					@$label = $action_data['label'];
					@$placeholder = $action_data['placeholder'];
					@$default = $action_data['default'];
					@$mode = $action_data['mode'];
					@$max_length = $action_data['max_length'];
					
					$tpl->assign('label', $label);
					$tpl->assign('placeholder', $placeholder);
					$tpl->assign('default', $default);
					$tpl->assign('mode', $mode);
					$tpl->assign('var', $var);
					$tpl->assign('max_length', $max_length);
					$tpl->assign('dict', $automation_results);
					$tpl->display('devblocks:cerberusweb.core::events/form_interaction/portal/prompts/prompt_text.tpl');
					
					/*
					@$placeholder = $action_data['placeholder'];
					@$default = $action_data['default'];
					@$mode = $action_data['mode'];

					if(empty($placeholder))
						$placeholder = 'say something';
					
					$tpl->assign('delay_ms', 0);
					$tpl->assign('placeholder', $placeholder);
					$tpl->assign('default', $default);
					$tpl->assign('mode', $mode);
					$tpl->display('devblocks:cerberusweb.core::console/prompt_text.tpl');
					*/
					break;
				
				case 'respond':
				case 'respond_error':
				case 'say':
					@$format = @$action_data['format'];
					
					if(is_string($action_data)) {
						$action_data = [
							'message' => $action_data,
						];
					}
					
					if(false == ($msg = @$action_data['message']))
						break;
					
					switch($format) {
						case 'markdown':
							$msg = DevblocksPlatform::parseMarkdown($msg);
							break;
					}
					
					// [TODO] Make errors red
					if($action_key == 'say' && !array_key_exists('style', $action_data))
						$action_data['style'] = 'say';
					
					$tpl->assign('message', $msg);
					$tpl->assign('format', $format);
					$tpl->assign('style', @$action_data['style']);
					$tpl->display('devblocks:cerberusweb.core::events/form_interaction/portal/responses/respond_text.tpl');
					break;
				
				case 'respond_sheet':
					$sheets = DevblocksPlatform::services()->sheet()->newInstance();
					$error = null;
					
					// [TODO] Error handling
					
					$sheet_schema = $action_data['schema'];
					$sheet_data = [];
					
					if(array_key_exists('data_key', $action_data)) {
						$data_key = $action_data['data_key'];
						$data = [];
						
						if(false !== strpos($data_key, '.')) {
							$data = $automation_results->getKeyPath($data_key, []);
						} else {
							$data = $automation_results->get($data_key, []);
						}
						
						// [TODO] This needs work
						if($data instanceof DevblocksDictionaryDelegate) {
							$sheet_data = [$data];
							
						} else if(is_array($data)) {
							if(0 === key($data)) {
								$sheet_data = DevblocksDictionaryDelegate::instance($data);
							} else {
								$sheet_data = [DevblocksDictionaryDelegate::instance($data)];
							}
						}
						
					} elseif (array_key_exists('data_query', $action_data)) {
						if(false == ($results = DevblocksPlatform::services()->data()->executeQuery($action_data['data_query'], $error)))
							break;
						
						$sheet_data = $results['data'];
					}
					
					$sheets->addType('card', $sheets->types()->card());
					$sheets->addType('date', $sheets->types()->date());
					$sheets->addType('selection', $sheets->types()->selection());
					$sheets->addType('icon', $sheets->types()->icon());
					$sheets->addType('link', $sheets->types()->link());
					$sheets->addType('slider', $sheets->types()->slider());
					$sheets->addType('text', $sheets->types()->text());
					$sheets->addType('time_elapsed', $sheets->types()->timeElapsed());
					$sheets->setDefaultType('text');
					
					$layout = $sheets->getLayout($sheet_schema);
					$tpl->assign('layout', $layout);
					
					$rows = $sheets->getRows($sheet_schema, $sheet_data);
					$tpl->assign('rows', $rows);
					
					$columns = $sheets->getColumns($sheet_schema);
					$tpl->assign('columns', $columns);
					
					//if(array_key_exists('_', $results) && array_key_exists('paging', $results['_']))
					//	$tpl->assign('paging', $results['_']['paging']);
					
					if($layout['style'] == 'fieldsets') {
						$tpl->display('devblocks:cerberusweb.core::events/form_interaction/portal/responses/respond_sheet_fieldsets.tpl');
					} else {
						$tpl->display('devblocks:cerberusweb.core::events/form_interaction/portal/responses/respond_sheet.tpl');
					}
					break;
				
				case 'end_chat':
					$tpl->assign('delay_ms', 0);
					$tpl->display('devblocks:cerberusweb.core::console/window_close.tpl');
					break;
			}
		}
		
		$tpl->assign('continue_options', [
			'continue' => $show_continue,
			'reset' => $show_reset,
		]);
		$tpl->display('devblocks:cerberusweb.core::portals/builder/pages/form_interaction/continue.tpl');
		
		$bot_session->session_data['dict'] = $automation_results->getDictionary();
		
		// Save session scope
		DAO_BotSession::update($bot_session->session_id, [
			DAO_BotSession::SESSION_DATA => json_encode($bot_session->session_data),
			DAO_BotSession::UPDATED_AT => time(),
		]);
	}
}