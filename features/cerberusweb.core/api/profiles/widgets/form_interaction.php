<?php
use function GuzzleHttp\Psr7\parse_query;

class ProfileWidget_FormInteraction extends Extension_ProfileWidget {
	function renderConfig(Model_ProfileWidget $widget) {
		$tpl = DevblocksPlatform::services()->template();
		
		if(!array_key_exists('interactions_yaml', $widget->extension_params)) {
			$widget->extension_params['interactions_yaml'] = "behaviors:\r\n- ";
		}
		
		$tpl->assign('widget', $widget);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/form_interaction/config.tpl');
	}
	
	private function _getStateKey(Model_ProfileWidget $widget, DevblocksDictionaryDelegate $dict) {
		$worker = CerberusApplication::getActiveWorker();
		
		$state_key = 'form:profile:' . sha1(sprintf("%d:%d:%s:%d",
			$widget->id,
			$dict->get('worker_id', $worker->id),
			$dict->get('record__context', $dict->get('record__context', null)),
			$dict->get('record_id', $dict->get('record_id', 0))
		));
		
		return $state_key;
	}
	
	function render(Model_ProfileWidget $widget, $context, $context_id) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();

		$dict = DevblocksDictionaryDelegate::instance([
			'widget__context' => CerberusContexts::CONTEXT_PROFILE_WIDGET,
			'widget_id' => $widget->id,
			
			'record__context' => $context,
			'record_id' => $context_id,
			
			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $active_worker->id,
		]);
		
		$tpl->assign('dict', $dict);
		$tpl->assign('widget', $widget);
		$tpl->assign('widget_ext', $this);
		$tpl->assign('is_refresh', array_key_exists('prompts', $_REQUEST) ? true : false);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/form_interaction/render.tpl');
	}
	
	function getInteractions(Model_ProfileWidget $widget, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		$interactions_yaml = $widget->extension_params['interactions_yaml'];
		
		// Render errors
		if(false ==  ($interactions_yaml = $tpl_builder->build($interactions_yaml, $dict)))
			return false;
		
		if(false == ($interactions = DevblocksPlatform::services()->string()->yamlParse($interactions_yaml, 0)))
			return false;
		
		if(!array_key_exists('behaviors', $interactions))
			return [];
		
		$results = [];
		
		foreach($interactions['behaviors'] as $interaction) {
			if(empty($interaction) || !array_key_exists('label', $interaction))
				continue;
			
			$results[$interaction['label']] = $interaction;
		}
		
		return $results;
	}
	
	function renderInteractionChooser(Model_ProfileWidget $widget, DevblocksDictionaryDelegate $dict) {
		$tpl = DevblocksPlatform::services()->template();
		
		$interactions = $this->getInteractions($widget, $dict);
		$tpl->assign('interactions', $interactions);
		
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/form_interaction/interaction_chooser.tpl');
	}
	
	function renderForm(Model_ProfileWidget $widget, DevblocksDictionaryDelegate $dict, $is_submit=false) {
		// Do we have a state for this form by this session?
		
		$state_key = $this->_getStateKey($widget, $dict);
		$state_ttl = time() + 7200;
		
		// If we're resetting the scope, delete the session and state key
		if(array_key_exists('reset', $_REQUEST)) {
			if(false != ($state_id = DevblocksPlatform::getRegistryKey($state_key, DevblocksRegistryEntry::TYPE_STRING, null)))
				DAO_BotSession::delete($state_id);
			
			DevblocksPlatform::services()->registry()->delete($state_key);
		}
		
		// If the state key doesn't exist, show the interactions menu
		if(
			array_key_exists('reset', $_REQUEST) 
			||  null == ($state_id = DevblocksPlatform::getRegistryKey($state_key, DevblocksRegistryEntry::TYPE_STRING, null))
		) {
			@$interaction_key = DevblocksPlatform::importGPC($_REQUEST['interaction'], 'string', null);
			
			if($interaction_key) {
				$interactions = $this->getInteractions($widget, $dict);
				
				if(false == (@$interaction = $interactions[$interaction_key]))
					return;
				
				if(!array_key_exists('id', $interaction))
					return;
				
				if(is_numeric($interaction['id'])) {
					$interaction_behavior = DAO_TriggerEvent::get($interaction['id']);
				} else {
					$interaction_behavior = DAO_TriggerEvent::getByUri($interaction['id']);
				}
				
				if(!$interaction_behavior)
					return;
				
				$interaction_behavior_vars = @$interaction['inputs'] ?: [];
				
				if($interaction_behavior->event_point == Event_FormInteractionWorker::ID) {
					if(false == ($bot_session = $this->_startFormSession($widget, $dict, $interaction_behavior, $interaction_behavior_vars)))
						return;
					
					DevblocksPlatform::setRegistryKey($state_key, $bot_session->session_id, DevblocksRegistryEntry::TYPE_STRING, true, $state_ttl);
					
					$this->_renderFormState($bot_session, $dict, $state_key, $is_submit);
					return;
				}
			}
			
			$this->renderInteractionChooser($widget, $dict);
			
		// Resuming
		} else {
			if(false == ($bot_session = DAO_BotSession::get($state_id)))
				return;
			
			// [TODO] Verify session ownership
			//$bot_session->session_data
			
			// Update TTL?
			//DevblocksPlatform::setRegistryKey($state_key, $bot_session->session_id, DevblocksRegistryEntry::TYPE_STRING, true, $state_ttl);
			
			$this->_renderFormState($bot_session, $dict, $state_key, $is_submit);
		}
	}
	
	private function _startFormSession(Model_ProfileWidget $widget, DevblocksDictionaryDelegate $dict, Model_TriggerEvent $interaction_behavior, array $interaction_behavior_vars=[]) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		if(!$interaction_behavior || $interaction_behavior->event_point != Event_FormInteractionWorker::ID)
			return false;
		
		// Start the session using the behavior
		
		$client_ip = DevblocksPlatform::getClientIp();
		$client_platform = '';
		$client_browser = '';
		$client_browser_version = '';
		
		if(false !== ($client_user_agent_parts = DevblocksPlatform::getClientUserAgent())) {
			$client_platform = @$client_user_agent_parts['platform'] ?: '';
			$client_browser = @$client_user_agent_parts['browser'] ?: '';
			$client_browser_version = @$client_user_agent_parts['version'] ?: '';
		}
		
		$behavior_dict = DevblocksDictionaryDelegate::instance([]);
		
		if(is_array($interaction_behavior_vars))
		foreach($interaction_behavior_vars as $k => &$v) {
			if(DevblocksPlatform::strStartsWith($k, 'var_')) {
				if(!array_key_exists($k, $interaction_behavior->variables))
					continue;
				
				if(false == ($value = $tpl_builder->build($v, $dict)))
					$value = null;
				
				$value = $interaction_behavior->formatVariable($interaction_behavior->variables[$k], $value, $behavior_dict);
				$behavior_dict->set($k, $value);
			}
		}
		
		$session_data = [
			'behavior_id' => $interaction_behavior->id,
			'dict' => $behavior_dict->getDictionary(null, false),
			'client_browser' => $client_browser,
			'client_browser_version' => $client_browser_version,
			'client_ip' => $client_ip,
			'client_platform' => $client_platform,
		];
		
		$created_at = time();
		
		$session_id = DAO_BotSession::create([
			DAO_BotSession::SESSION_DATA => json_encode($session_data),
			DAO_BotSession::UPDATED_AT => $created_at,
		]);
		
		$model = new Model_BotSession();
		$model->session_id = $session_id;
		$model->session_data = $session_data;
		$model->updated_at = $created_at;
		
		return $model;
	}
	
	private function _prepareResumeDecisionTree(Model_TriggerEvent $behavior, $prompts, &$interaction, &$actions, DevblocksDictionaryDelegate &$dict, &$resume_path) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		unset($interaction->session_data['form_validation_errors']);
		
		// Do we have special prompt handling instructions?
		if(array_key_exists('form_state', $interaction->session_data)) {
			$form_state = $interaction->session_data['form_state'];
			$validation_errors = [];
			
			foreach($form_state as $form_element) {
				if(!array_key_exists('_prompt', $form_element))
					continue;
				
				$prompt_var = $form_element['_prompt']['var'];
				$prompt_value = @$prompts[$prompt_var] ?: null;
				
				// If we lazy loaded a sub dictionary on the last attempt, clear it
				if(DevblocksPlatform::strEndsWith($prompt_var, '_id'))
					$dict->scrubKeys(substr($prompt_var, 0, -2));
				
				// Prompt-specific options
				switch(@$form_element['_action']) {
					case 'prompt.captcha':
						$otp_key = $prompt_var . '__otp';
						$otp = $dict->get($otp_key);
						
						if(!$otp || 0 !== strcasecmp($otp, $prompt_value)) {
							$validation_errors[] = 'Your CAPTCHA text does not match the image.';
							
							// Re-generate the challenge
							$otp = CerberusApplication::generatePassword(4);
							$dict->set($otp_key, $otp);
						}
						break;
						
					case 'prompt.checkboxes':
						if(is_null($prompt_value))
							$prompt_value = [];
						break;
						
					case 'prompt.file':
						if(!DevblocksPlatform::strEndsWith($prompt_var, '_id'))
							break;
							
						$dict->set(substr($prompt_var,0,-2) . '_context', CerberusContexts::CONTEXT_ATTACHMENT);
						break;
				}
				
				$dict->set($prompt_var, $prompt_value);
				
				if(false != (@$format_tpl = $form_element['_prompt']['format'])) {
					$var_message = $tpl_builder->build($format_tpl, $dict);
					$dict->set($prompt_var, $var_message);
				}
				
				if(false != (@$validate_tpl = $form_element['_prompt']['validate'])) {
					$validation_result = trim($tpl_builder->build($validate_tpl, $dict));
					
					if(!empty($validation_result)) {
						$validation_errors[] = $validation_result;
					}
				}
			}
			
			$interaction->session_data['form_validation_errors'] = $validation_errors;
		}
		
		return true;
	}
	
	private function _renderFormState(Model_BotSession $interaction, DevblocksDictionaryDelegate $dict, $state_key, $is_submit=false) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$prompts = DevblocksPlatform::importGPC($_REQUEST['prompts'], 'array', []);
		
		// Load our default behavior for this interaction
		if(false == (@$behavior_id = $interaction->session_data['behavior_id']))
			return false;
		
		$actions = [];
		
		if(false == ($behavior = DAO_TriggerEvent::get($behavior_id)))
			return;
		
		$event_params = [
			'prompts' => $prompts,
			'actions' => &$actions,
			
			'worker_id' => $active_worker ? $active_worker->id : 0,
			
			'client_browser' => @$interaction->session_data['client_browser'],
			'client_browser_version' => @$interaction->session_data['client_browser_version'],
			'client_ip' => @$interaction->session_data['client_ip'],
			'client_platform' => @$interaction->session_data['client_platform'],
		];
		
		$event_model = new Model_DevblocksEvent(
			Event_FormInteractionWorker::ID,
			$event_params
		);
		
		if(false == ($event = Extension_DevblocksEvent::get($event_model->id, true)))
			return;
		
		if(!($event instanceof Event_FormInteractionWorker))
			return;
		
		$event->setEvent($event_model, $behavior);
		
		$values = $event->getValues();
		
		// Are we resuming a scope?
		$resume_dict = @$interaction->session_data['dict'];
		if($resume_dict) {
			$values = array_replace($values, $resume_dict);
		}
		
		$behavior_dict = new DevblocksDictionaryDelegate($values);
		
		$resume_path = @$interaction->session_data['path'];
		$result = [];
		
		if($resume_path) {
			// Did we try to submit?
			if($is_submit) {
				$this->_prepareResumeDecisionTree($behavior, $prompts, $interaction, $actions, $behavior_dict, $resume_path);
				
				$form_validation_errors = $interaction->session_data['form_validation_errors'];
				
				if($form_validation_errors) {
					$tpl->assign('errors', $form_validation_errors);
					$tpl->display('devblocks:cerberusweb.core::events/form_interaction/worker/responses/respond_errors.tpl');
					
					// If we had validation errors, repeat the form state
					$actions = $interaction->session_data['form_state'];
					
					// Simulate the end state
					$result = [
						'exit_state' => 'SUSPEND',
						'path' => $resume_path,
					];
					
				} else {
					if(false == ($result = $behavior->resumeDecisionTree($behavior_dict, false, $event, $resume_path)))
						return;
				}
				
			// Re-render without changing state
			} else {
				// If we had validation errors, repeat the form state
				$actions = $interaction->session_data['form_state'];
				$exit_state = $interaction->session_data['exit_state'];
				
				// Simulate the end state
				$result = [
					'exit_state' => $exit_state,
					'path' => $resume_path,
				];
			}
			
		} else {
			if(false == ($result = $behavior->runDecisionTree($behavior_dict, false, $event)))
				return;
		}
		
		$values = $behavior_dict->getDictionary(null, false);
		$values = array_diff_key($values, $event->getValues());
		
		$interaction->session_data['dict'] = $values;
		$interaction->session_data['path'] = $result['path'];
		$interaction->session_data['form_state'] = $actions;
		$interaction->session_data['exit_state'] = $result['exit_state'];
		
		foreach($actions as $params) {
			switch(@$params['_action']) {
				case 'prompt.captcha':
					$captcha = DevblocksPlatform::services()->captcha();
					
					@$label = $params['label'];
					@$var = $params['_prompt']['var'];
					
					$otp_key = $var . '__otp';
					$otp = $behavior_dict->get($otp_key);
					
					$image_bytes = $captcha->createImage($otp);
					$tpl->assign('image_bytes', $image_bytes);
					
					$tpl->assign('label', $label);
					$tpl->assign('var', $var);
					$tpl->assign('dict', $behavior_dict);
					$tpl->display('devblocks:cerberusweb.core::events/form_interaction/worker/prompts/prompt_captcha.tpl');
					break;
					
				case 'prompt.checkboxes':
					@$label = $params['label'];
					@$options = $params['options'];
					@$var = $params['_prompt']['var'];
					
					$tpl->assign('label', $label);
					$tpl->assign('options', $options);
					$tpl->assign('var', $var);
					$tpl->assign('dict', $behavior_dict);
					$tpl->display('devblocks:cerberusweb.core::events/form_interaction/worker/prompts/prompt_checkboxes.tpl');
					break;
					
				case 'prompt.radios':
					@$label = $params['label'];
					@$style = $params['style'];
					@$orientation = $params['orientation'];
					@$options = $params['options'];
					@$default = $params['default'];
					@$var = $params['_prompt']['var'];
					
					$tpl->assign('label', $label);
					$tpl->assign('orientation', $orientation);
					$tpl->assign('options', $options);
					$tpl->assign('default', $default);
					$tpl->assign('var', $var);
					$tpl->assign('dict', $behavior_dict);
					
					if($style == 'buttons') {
						$tpl->display('devblocks:cerberusweb.core::events/form_interaction/worker/prompts/prompt_buttons.tpl');
					} else {
						$tpl->display('devblocks:cerberusweb.core::events/form_interaction/worker/prompts/prompt_radios.tpl');
					}
					break;
					
				case 'prompt.text':
					@$label = $params['label'];
					@$placeholder = $params['placeholder'];
					@$default = $params['default'];
					@$mode = $params['mode'];
					@$var = $params['_prompt']['var'];
					
					$tpl->assign('label', $label);
					$tpl->assign('placeholder', $placeholder);
					$tpl->assign('default', $default);
					$tpl->assign('mode', $mode);
					$tpl->assign('var', $var);
					$tpl->assign('dict', $behavior_dict);
					$tpl->display('devblocks:cerberusweb.core::events/form_interaction/worker/prompts/prompt_text.tpl');
					break;
					
				case 'respond.text':
					if(false == ($msg = @$params['message']))
						break;
					
					$tpl->assign('message', $msg);
					$tpl->assign('format', @$params['format']);
					$tpl->assign('style', @$params['style']);
					$tpl->display('devblocks:cerberusweb.core::events/form_interaction/worker/responses/respond_text.tpl');
					break;
					
				case 'respond.sheet':
					$sheets = DevblocksPlatform::services()->sheet()->newInstance();
					$error = null;
					
					if(false == ($results = DevblocksPlatform::services()->data()->executeQuery($params['data_query'], $error)))
						return;
					
					if(false == ($sheet = $sheets->parseYaml($params['sheet_yaml'], $error)))
						return;
					
					$sheets->addType('card', $sheets->types()->card());
					$sheets->addType('date', $sheets->types()->date());
					$sheets->addType('link', $sheets->types()->link());
					$sheets->addType('slider', $sheets->types()->slider());
					$sheets->addType('text', $sheets->types()->text());
					$sheets->addType('time_elapsed', $sheets->types()->timeElapsed());
					$sheets->setDefaultType('text');
					
					$sheet_dicts = $results['data'];
					
					$layout = $sheets->getLayout($sheet);
					$tpl->assign('layout', $layout);
					
					$rows = $sheets->getRows($sheet, $sheet_dicts);
					$tpl->assign('rows', $rows);
					
					$columns = $sheets->getColumns($sheet);
					$tpl->assign('columns', $columns);
					
					if(array_key_exists('_', $results) && array_key_exists('paging', $results['_']))
						$tpl->assign('paging', $results['_']['paging']);
					
					if($layout['style'] == 'fieldsets') {
						$tpl->display('devblocks:cerberusweb.core::events/form_interaction/worker/responses/respond_sheet_fieldsets.tpl');
					} else {
						$tpl->display('devblocks:cerberusweb.core::events/form_interaction/worker/responses/respond_sheet.tpl');
					}
					break;
					
				default:
					$tpl->assign('continue_options', [
						'continue' => true,
						'reset' => true,
					]);
					$tpl->display('devblocks:cerberusweb.core::events/form_interaction/worker/responses/continue.tpl');
					break;
			}
		}
		
		if($result['exit_state'] != 'SUSPEND') {
			$tpl->assign('continue_options', [
				'continue' => false,
				'reset' => true,
			]);
			$tpl->display('devblocks:cerberusweb.core::events/form_interaction/worker/responses/continue.tpl');
		}
		
		// Save session scope
		DAO_BotSession::update($interaction->session_id, [
			DAO_BotSession::SESSION_DATA => json_encode($interaction->session_data),
			DAO_BotSession::UPDATED_AT => time(),
		]);
	}
}