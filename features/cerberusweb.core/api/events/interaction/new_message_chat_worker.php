<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/

class Event_NewMessageChatWorker extends Extension_DevblocksEvent {
	const ID = 'event.message.chat.worker';

	function renderEventParams(Model_TriggerEvent $trigger=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('trigger', $trigger);
		$tpl->display('devblocks:cerberusweb.core::events/record/params_macro_default.tpl');
	}

	/**
	 *
	 * @param Model_TriggerEvent $trigger
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel(Model_TriggerEvent $trigger) {
		$active_worker = CerberusApplication::getActiveWorker();
		$actions = [];

		return new Model_DevblocksEvent(
			self::ID,
			array(
				'worker_id' => $active_worker->id,
				'message' => 'This is a test message',
				'actions' => &$actions,

				'interaction' => null,
				'interaction_behavior_has_parent' => false,
				'interaction_behavior_id' => 0,
				'interaction_bot_image' => null,
				'interaction_bot_name' => 'Cerb',
				'interaction_params' => [],
				'client_browser' => null,
				'client_browser_version' => null,
				'client_ip' => null,
				'client_platform' => null,
				'client_url' => null,
			)
		);
	}

	function setEvent(Model_DevblocksEvent $event_model=null, Model_TriggerEvent $trigger=null) {
		$labels = [];
		$values = [];

		/**
		 * Behavior
		 */

		$merge_labels = [];
		$merge_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_BEHAVIOR, $trigger, $merge_labels, $merge_values, null, true);

			// Merge
			CerberusContexts::merge(
				'behavior_',
				'',
				$merge_labels,
				$merge_values,
				$labels,
				$values
			);

		@$worker_id = $event_model->params['worker_id'];

		// Message
		@$message = $event_model->params['message'];
		$labels['message'] = 'Message';
		$values['message'] = $message;

		// Actions
		$values['_actions'] =& $event_model->params['actions'];

		/**
		 * Worker
		 */

		$merge_labels = [];
		$merge_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $worker_id, $merge_labels, $merge_values, null, true);

			// Merge
			CerberusContexts::merge(
				'worker_',
				'',
				$merge_labels,
				$merge_values,
				$labels,
				$values
			);

		// Bot
		@$bot_name = $event_model->params['bot_name'];
		$labels['interaction_bot_name'] = 'Bot Name';
		$values['interaction_bot_name'] = $bot_name;

		@$bot_image = $event_model->params['bot_image'];
		$labels['interaction_bot_image'] = 'Bot Image';
		$values['interaction_bot_image'] = $bot_image;

		// Behavior
		// [TODO] Expand
		@$behavior_id = $event_model->params['behavior_id'];
		$labels['interaction_behavior_id'] = 'Behavior ID';
		$values['interaction_behavior_id'] = $behavior_id;

		// Behavior has parent
		@$behavior_has_parent = $event_model->params['behavior_has_parent'];
		$labels['interaction_behavior_has_parent'] = 'Behavior has parent';
		$values['interaction_behavior_has_parent'] = $behavior_has_parent;

		// Interaction
		@$interaction = $event_model->params['interaction'];
		$labels['interaction'] = 'Interaction';
		$values['interaction'] = $interaction;

		// Interaction Parameters
		@$interaction_params = $event_model->params['interaction_params'];
		$labels['interaction_params'] = 'Interaction Params';
		$values['interaction_params'] = $interaction_params;

		// Client
		@$client_browser = $event_model->params['client_browser'];
		@$client_browser_version = $event_model->params['client_browser_version'];
		@$client_ip = $event_model->params['client_ip'];
		@$client_platform = $event_model->params['client_platform'];
		@$client_url = $event_model->params['client_url'];

		$labels['client_browser'] = 'Client Browser';
		$labels['client_browser_version'] = 'Client Browser Version';
		$labels['client_ip'] = 'Client IP';
		$labels['client_platform'] = 'Client Platform';
		$labels['client_url'] = 'Client URL';

		$values['client_browser'] = $client_browser;
		$values['client_browser_version'] = $client_browser_version;
		$values['client_ip'] = $client_ip;
		$values['client_platform'] = $client_platform;
		$values['client_url'] = $client_url;

		/**
		 * Return
		 */

		$this->setLabels($labels);
		$this->setValues($values);
	}

	function getValuesContexts($trigger) {
		$vals = array(
			'behavior_id' => array(
				'label' => 'Behavior',
				'context' => CerberusContexts::CONTEXT_BEHAVIOR,
			),
			'behavior_bot_id' => array(
				'label' => 'Bot',
				'context' => CerberusContexts::CONTEXT_BOT,
			),
			'interaction_behavior_id' => array(
				'label' => 'Interaction Behavior',
				'context' => CerberusContexts::CONTEXT_BEHAVIOR,
			),
			'worker_id' => array(
				'label' => 'Worker',
				'context' => CerberusContexts::CONTEXT_WORKER,
			),
		);

		$vars = parent::getValuesContexts($trigger);

		$vals_to_ctx = array_merge($vals, $vars);
		DevblocksPlatform::sortObjects($vals_to_ctx, '[label]');

		return $vals_to_ctx;
	}

	function getConditionExtensions(Model_TriggerEvent $trigger) {
		$labels = $this->getLabels($trigger);
		$types = $this->getTypes();

		$labels['message'] = 'Message';
		$types['message'] = Model_CustomField::TYPE_MULTI_LINE;

		// Bot
		$labels['interaction_bot_name'] = 'Interaction Bot Name';
		$types['interaction_bot_name'] = Model_CustomField::TYPE_SINGLE_LINE;

		$labels['interaction_bot_image'] = 'Interaction Bot Image';
		$types['interaction_bot_image'] = Model_CustomField::TYPE_SINGLE_LINE;

		// Behavior
		// [TODO] Expand
		$labels['interaction_behavior_id'] = 'Interaction Behavior ID';
		$types['interaction_behavior_id'] = Model_CustomField::TYPE_NUMBER;

		// Behavior has parent
		$labels['interaction_behavior_has_parent'] = 'Interaction Behavior has parent';
		$types['interaction_behavior_has_parent'] = Model_CustomField::TYPE_CHECKBOX;

		// Interaction
		$labels['interaction'] = 'Interaction';
		$types['interaction'] = Model_CustomField::TYPE_SINGLE_LINE;

		// Interaction Parameters
		$labels['interaction_params'] = 'Interaction Params';
		$types['interaction_params'] = null;

		// Client
		$labels['client_browser'] = 'Client Browser';
		$labels['client_browser_version'] = 'Client Browser Version';
		$labels['client_ip'] = 'Client IP';
		$labels['client_platform'] = 'Client Platform';
		$labels['client_url'] = 'Client URL';

		$types['client_browser'] = Model_CustomField::TYPE_SINGLE_LINE;
		$types['client_browser_version'] = Model_CustomField::TYPE_SINGLE_LINE;
		$types['client_ip'] = Model_CustomField::TYPE_SINGLE_LINE;
		$types['client_platform'] = Model_CustomField::TYPE_SINGLE_LINE;
		$types['client_url'] = Model_CustomField::TYPE_SINGLE_LINE;

		$conditions = $this->_importLabelsTypesAsConditions($labels, $types);

		return $conditions;
	}

	function renderConditionExtension($token, $as_token, $trigger, $params=[], $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','condition'.$seq);

		switch($as_token) {
		}

		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('params');
	}

	function runConditionExtension($token, $as_token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$pass = true;

		switch($as_token) {
			default:
				$pass = false;
				break;
		}

		return $pass;
	}

	function getActionExtensions(Model_TriggerEvent $trigger) {
		$actions =
			array(
				'create_comment' => array('label' =>'Create comment'),
				'create_notification' => array('label' =>'Create notification'),
				'create_task' => array('label' =>'Create task'),
				'create_ticket' => array('label' =>'Create ticket'),

				'prompt_buttons' => array('label' => 'Prompt with buttons'),
				'prompt_chooser' => array('label' => 'Prompt with chooser'),
				'prompt_date' => array('label' => 'Prompt with date input'),
				'prompt_images' => array('label' => 'Prompt with images'),
				'prompt_file' => array('label' => 'Prompt with file upload'),
				'prompt_text' => array('label' => 'Prompt with text input'),
				'prompt_wait' => array('label' => 'Prompt with wait'),
				'send_message' => array('label' => 'Respond with message'),
				'send_script' => array('label' => 'Respond with script'),
				'switch_behavior' => array('label' => 'Switch behavior'),
				'window_close' => array('label' => 'Close chat window'),
				'worklist_open' => array('label' => 'Open a worklist popup'),
				
				'send_email' => array('label' => 'Send email'),
			)
			;

		return $actions;
	}

	function renderActionExtension($token, $trigger, $params=[], $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','action'.$seq);

		$labels = $this->getLabels($trigger);
		$tpl->assign('token_labels', $labels);

		switch($token) {
			case 'create_comment':
				DevblocksEventHelper::renderActionCreateComment($trigger);
				break;

			case 'create_notification':
				DevblocksEventHelper::renderActionCreateNotification($trigger);
				break;

			case 'create_task':
				DevblocksEventHelper::renderActionCreateTask($trigger);
				break;

			case 'create_ticket':
				DevblocksEventHelper::renderActionCreateTicket($trigger);
				break;

			case 'prompt_buttons':
				$tpl->display('devblocks:cerberusweb.core::events/pm/action_prompt_buttons.tpl');
				break;

			case 'prompt_chooser':
				$contexts = Extension_DevblocksContext::getAll(false, ['search']);
				$tpl->assign('contexts', $contexts);

				$tpl->display('devblocks:cerberusweb.core::events/pm/action_prompt_chooser.tpl');
				break;
				
			case 'prompt_date':
				$tpl->display('devblocks:cerberusweb.core::events/pm/action_prompt_text.tpl');
				break;

			case 'prompt_file':
				$tpl->display('devblocks:cerberusweb.core::events/pm/action_prompt_file.tpl');
				break;

			case 'prompt_images':
				$tpl->display('devblocks:cerberusweb.core::events/pm/action_prompt_images.tpl');
				break;

			case 'prompt_text':
				$tpl->display('devblocks:cerberusweb.core::events/pm/action_prompt_text.tpl');
				break;

			case 'prompt_wait':
				$tpl->display('devblocks:cerberusweb.core::events/pm/action_prompt_wait.tpl');
				break;
				
			case 'send_email':
				DevblocksEventHelper::renderActionSendEmail($trigger);
				break;

			case 'send_message':
				$tpl->display('devblocks:cerberusweb.core::events/pm/action_send_response.tpl');
				break;

			case 'send_script':
				$tpl->display('devblocks:cerberusweb.core::events/pm/action_send_script.tpl');
				break;

			case 'switch_behavior':
				$tpl->display('devblocks:cerberusweb.core::events/pm/action_switch_behavior.tpl');
				break;

			case 'window_close':
				break;

			case 'worklist_open':
				$context_mfts = Extension_DevblocksContext::getAll(false, ['search']);
				$tpl->assign('context_mfts', $context_mfts);

				$tpl->display('devblocks:cerberusweb.core::events/pm/action_worklist_open.tpl');
				break;
		}

		$tpl->clearAssign('params');
		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('token_labels');
	}

	function simulateActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		switch($token) {
			case 'create_comment':
				return DevblocksEventHelper::simulateActionCreateComment($params, $dict, 'worker_id');
				break;

			case 'create_notification':
				return DevblocksEventHelper::simulateActionCreateNotification($params, $dict, 'worker_id');
				break;

			case 'create_task':
				return DevblocksEventHelper::simulateActionCreateTask($params, $dict, 'worker_id');
				break;

			case 'create_ticket':
				return DevblocksEventHelper::simulateActionCreateTicket($params, $dict, 'worker_id');
				break;

			case 'prompt_buttons':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$options = $tpl_builder->build($params['options'], $dict);

				$out = sprintf(">>> Prompting with buttons:\n".
					"%s\n",
					$options
				);
				break;

			case 'prompt_chooser':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();

				@$context = $params['context'];
				@$query = $tpl_builder->build($params['query'], $dict);
				@$selection = $params['selection'];

				$out = sprintf(">>> Prompting with chooser:\n".
					"Context: %s\n".
					"Query: %s\n".
					"Selection: %s\n",
					$context,
					$query,
					$selection
				);
				break;
				
			case 'prompt_date':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$placeholder = $tpl_builder->build($params['placeholder'], $dict);

				$out = sprintf(">>> Prompting with date input\nPlaceholder: %s\n",
					$placeholder
				);
				break;

			case 'prompt_file':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();

				$out = sprintf(">>> Prompting with file upload\n");
				break;

			case 'prompt_images':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();

				$out = sprintf(">>> Prompting with buttons:\n"
				);
				break;

			case 'prompt_text':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$placeholder = $tpl_builder->build($params['placeholder'], $dict);

				$out = sprintf(">>> Prompting with text input\nPlaceholder: %s\n",
					$placeholder
				);
				break;

			case 'prompt_wait':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();

				$out = sprintf(">>> Prompting with wait\n");
				break;
				
			case 'send_email':
				return DevblocksEventHelper::simulateActionSendEmail($params, $dict);
				break;

			case 'send_message':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$content = $tpl_builder->build($params['message'], $dict);

				$out = sprintf(">>> Sending response message\n".
					"%s\n",
					$content
				);
				break;

			case 'send_script':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$content = $tpl_builder->build($params['script'], $dict);

				$out = sprintf(">>> Sending response script\n".
					"%s\n",
					$content
				);
				break;

			case 'switch_behavior':
				@$behavior_id = intval($params['behavior_id']);

				$out = sprintf(">>> Using behavior\n".
					"%d\n",
					$behavior_id
				);
				break;

			case 'window_close':
				$out = sprintf(">>> Closing the chat window\n");
				break;

			case 'worklist_open':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$query = $tpl_builder->build($params['quick_search'], $dict);

				$context_ext = Extension_DevblocksContext::get($params['context']);

				$out = sprintf(">>> Opening a %s worklist with filters:\n%s",
					mb_convert_case($context_ext->manifest->name, MB_CASE_LOWER),
					$query
				);
				break;
		}

		return $out;
	}

	function runActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		switch($token) {
			case 'create_comment':
				DevblocksEventHelper::runActionCreateComment($params, $dict, 'worker_id');
				break;

			case 'create_notification':
				DevblocksEventHelper::runActionCreateNotification($params, $dict, 'worker_id');
				break;

			case 'create_task':
				DevblocksEventHelper::runActionCreateTask($params, $dict, 'worker_id');
				break;

			case 'create_ticket':
				DevblocksEventHelper::runActionCreateTicket($params, $dict, 'worker_id');
				break;

			case 'prompt_buttons':
				$actions =& $dict->_actions;
				
				if(!is_array($actions))
					$actions = [];

				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				
				@$options = $tpl_builder->build($params['options'], $dict);
				@$style = $tpl_builder->build($params['style'], $dict);
				@$var = $params['var'];
				@$var_format = $params['var_format'];
				@$var_validate = $params['var_validate'];
				
				@$color_from = $params['color_from'];
				@$color_to = $params['color_to'];
				@$color_mid = $params['color_mid'];
				
				if($color_from == '#FFFFFF') {
					$color_from = '';
					$color_mid = '';
					$color_to = '';
				}
				
				if($color_mid == '#FFFFFF')
					$color_mid = '';
				
				if($color_to == '#FFFFFF')
					$color_to = '';
				
				$actions[] = array(
					'_action' => 'prompt.buttons',
					'_trigger_id' => $trigger->id,
					'_prompt' => [
						'action' => 'prompt.buttons',
						'var' => $var,
						'format' => $var_format,
						'validate' => $var_validate,
					],
					'options' => DevblocksPlatform::parseCrlfString($options),
					'style' => $style,
					'color_from' => $color_from,
					'color_to' => $color_to,
					'color_mid' => $color_mid,
				);

				$dict->__exit = 'suspend';
				break;

			case 'prompt_chooser':
				$actions =& $dict->_actions;

				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				
				@$context = $params['context'];
				@$query = $tpl_builder->build($params['query'], $dict);
				@$selection = $params['selection'];
				@$autocomplete = !empty($params['autocomplete']);
				@$var = $params['var'];
				@$var_format = $params['var_format'];
				@$var_validate = $params['var_validate'];
				
				$actions[] = array(
					'_action' => 'prompt.chooser',
					'_trigger_id' => $trigger->id,
					'_prompt' => [
						'action' => 'prompt.chooser',
						'var' => $var,
						'context' => $context,
						'format' => $var_format,
						'validate' => $var_validate,
					],
					'context' => $context,
					'query' => $query,
					'selection' => $selection,
					'autocomplete' => $autocomplete,
				);

				$dict->__exit = 'suspend';
				break;
				
			case 'prompt_date':
				$actions =& $dict->_actions;

				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				
				$placeholder = $tpl_builder->build($params['placeholder'], $dict);
				@$var = $params['var'];
				@$var_format = $params['var_format'];
				@$var_validate = $params['var_validate'];

				$actions[] = array(
					'_action' => 'prompt.date',
					'_trigger_id' => $trigger->id,
					'_prompt' => [
						'action' => 'prompt.date',
						'var' => $var,
						'format' => $var_format,
						'validate' => $var_validate,
					],
					'placeholder' => $placeholder,
				);

				$dict->__exit = 'suspend';
				break;

			case 'prompt_file':
				$actions =& $dict->_actions;
				
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				
				@$var = $params['var'];
				@$var_format = $params['var_format'];
				@$var_validate = $params['var_validate'];
				
				$actions[] = array(
					'_action' => 'prompt.file',
					'_trigger_id' => $trigger->id,
					'_prompt' => [
						'action' => 'prompt.file',
						'var' => $var,
						'format' => $var_format,
						'validate' => $var_validate,
					],
				);

				$dict->__exit = 'suspend';
				break;

			case 'prompt_images':
				$actions =& $dict->_actions;

				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				
				$images = $params['images'];
				$labels = $params['labels'];
				@$var = $params['var'];
				@$var_format = $params['var_format'];
				@$var_validate = $params['var_validate'];
				
				$actions[] = array(
					'_action' => 'prompt.images',
					'_trigger_id' => $trigger->id,
					'_prompt' => [
						'action' => 'prompt.images',
						'var' => $var,
						'format' => $var_format,
						'validate' => $var_validate,
					],
					'images' => $images,
					'labels' => $labels,
				);

				$dict->__exit = 'suspend';
				break;

			case 'prompt_text':
				$actions =& $dict->_actions;

				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				
				@$placeholder = $tpl_builder->build($params['placeholder'], $dict);
				@$default = $tpl_builder->build($params['default'], $dict);
				@$mode = $params['mode'];
				@$var = $params['var'];
				@$var_format = $params['var_format'];
				@$var_validate = $params['var_validate'];

				$actions[] = array(
					'_action' => 'prompt.text',
					'_trigger_id' => $trigger->id,
					'_prompt' => [
						'action' => 'prompt.text',
						'var' => $var,
						'format' => $var_format,
						'validate' => $var_validate,
					],
					'placeholder' => $placeholder,
					'default' => $default,
					'mode' => $mode,
				);

				$dict->__exit = 'suspend';
				break;

			case 'prompt_wait':
				$actions =& $dict->_actions;

				$tpl_builder = DevblocksPlatform::services()->templateBuilder();

				$actions[] = array(
					'_action' => 'prompt.wait',
					'_trigger_id' => $trigger->id,
				);

				$dict->__exit = 'suspend';
				break;
				
			case 'send_email':
				DevblocksEventHelper::runActionSendEmail($params, $dict);
				break;

			case 'send_message':
				$actions =& $dict->_actions;

				@$format = $params['format'];
				@$delay_ms = @$params['delay_ms'];

				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$content = $tpl_builder->build($params['message'], $dict);

				switch($format) {
					case 'html':
						break;

					case 'markdown':
						$content = DevblocksPlatform::parseMarkdown($content);
						break;

					default:
						$format = '';
						break;
				}

				$actions[] = array(
					'_action' => 'message.send',
					'_trigger_id' => $trigger->id,
					'message' => $content,
					'format' => $format,
					'delay_ms' => $delay_ms,
				);
				break;

			case 'send_script':
				$actions =& $dict->_actions;

				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$content = $tpl_builder->build($params['script'], $dict);

				$actions[] = array(
					'_action' => 'script.send',
					'_trigger_id' => $trigger->id,
					'script' => $content,
				);
				break;

			case 'switch_behavior':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();

				$actions =& $dict->_actions;

				@$behavior_id = intval($params['behavior_id']);
				@$behavior_return = intval($params['return']) ? true : false;

				if(false == ($behavior = DAO_TriggerEvent::get($behavior_id)))
					break;
				
				if($behavior->isDisabled())
					break;

				// Variables as parameters

				$vars = [];

				if(is_array($params))
				foreach($params as $k => $v) {
					if(DevblocksPlatform::strStartsWith($k, 'var_')) {
						if(!isset($behavior->variables[$k]))
							continue;

						try {
							if(is_string($v))
								$v = $tpl_builder->build($v, $dict);

							$v = $behavior->formatVariable($behavior->variables[$k], $v, $dict);

							$vars[$k] = $v;

						} catch(Exception $e) {

						}
					}
				}

				$actions[] = array(
					'_action' => 'behavior.switch',
					'_trigger_id' => $trigger->id,
					'behavior_id' => $behavior_id,
					'behavior_variables' => $vars,
					'behavior_return' => $behavior_return,
				);

				$dict->__exit = 'suspend';
				break;

			case 'window_close':
				$actions =& $dict->_actions;

				$actions[] = array(
					'_action' => 'window.close',
					'_trigger_id' => $trigger->id,
				);

				$dict->__exit = 'exit';
				break;

			case 'worklist_open':
				$actions =& $dict->_actions;
				$query = null;

				if(!isset($params['context']) || empty($params['context']))
					break;

				@$worklist_model = $params['worklist_model'] ?: null;

				$tpl_builder = DevblocksPlatform::services()->templateBuilder();

				if(isset($params['quick_search']))
					$query = $tpl_builder->build($params['quick_search'], $dict);

				$actions[] = array(
					'_action' => 'worklist.open',
					'_trigger_id' => $trigger->id,
					'view_id' => 'behavior_' . $trigger->id . '_' . uniqid(),
					'context' => $params['context'],
					'model' => $worklist_model,
					'q' => $query,
				);

				$actions[] = array(
					'_action' => 'emote',
					'_trigger_id' => $trigger->id,
					'emote' => 'opened a worklist.',
				);
				break;
		}
	}
};