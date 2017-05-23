<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2017, Webgroup Media LLC
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

	/**
	 *
	 * @param Model_TriggerEvent $trigger
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel(Model_TriggerEvent $trigger) {
		$active_worker = CerberusApplication::getActiveWorker();
		$actions = array();
		
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
		$labels = array();
		$values = array();
		
		/**
		 * Behavior
		 */
		
		$merge_labels = array();
		$merge_values = array();
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
		
		$merge_labels = array();
		$merge_values = array();
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
				'label' => 'Behavior',
				'context' => CerberusContexts::CONTEXT_BOT,
			),
			'interaction_behavior_id' => array(
				'label' => 'Behavior',
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
		$labels['interaction_bot_name'] = 'Bot Name';
		$types['interaction_bot_name'] = Model_CustomField::TYPE_SINGLE_LINE;
		
		$labels['interaction_bot_image'] = 'Bot Image';
		$types['interaction_bot_image'] = Model_CustomField::TYPE_SINGLE_LINE;
		
		// Behavior
		// [TODO] Expand
		$labels['interaction_behavior_id'] = 'Behavior ID';
		$types['interaction_behavior_id'] = Model_CustomField::TYPE_NUMBER;
		
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
	
	function renderConditionExtension($token, $as_token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::getTemplateService();
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
				'prompt_buttons' => array('label' => 'Prompt with buttons'),
				'prompt_text' => array('label' => 'Prompt with text input'),
				'prompt_wait' => array('label' => 'Prompt with wait'),
				'send_message' => array('label' => 'Respond with message'),
				'send_script' => array('label' => 'Respond with script'),
				'worklist_open' => array('label' => 'Open a worklist popup'),
			)
			;
		
		return $actions;
	}
	
	function renderActionExtension($token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','action'.$seq);

		$labels = $this->getLabels($trigger);
		$tpl->assign('token_labels', $labels);
			
		switch($token) {
			case 'prompt_buttons':
				$tpl->display('devblocks:cerberusweb.core::events/pm/action_prompt_buttons.tpl');
				break;
				
			case 'prompt_text':
				$tpl->display('devblocks:cerberusweb.core::events/pm/action_prompt_text.tpl');
				break;
				
			case 'prompt_wait':
				$tpl->display('devblocks:cerberusweb.core::events/pm/action_prompt_wait.tpl');
				break;
				
			case 'send_message':
				$tpl->display('devblocks:cerberusweb.core::events/pm/action_send_response.tpl');
				break;
				
			case 'send_script':
				$tpl->display('devblocks:cerberusweb.core::events/pm/action_send_script.tpl');
				break;
				
			case 'worklist_open':
				$context_mfts = Extension_DevblocksContext::getAll(false);
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
			case 'prompt_buttons':
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$options = $tpl_builder->build($params['options'], $dict);
				
				$out = sprintf(">>> Prompting with buttons:\n".
					"%s\n",
					$options
				);
				break;
				
			case 'prompt_text':
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$placeholder = $tpl_builder->build($params['placeholder'], $dict);
				
				$out = sprintf(">>> Prompting with text input\nPlaceholder: %s\n",
					$placeholder
				);
				break;
				
			case 'prompt_wait':
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				
				$out = sprintf(">>> Prompting with wait\n");
				break;
				
			case 'send_message':
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$content = $tpl_builder->build($params['message'], $dict);
				
				$out = sprintf(">>> Sending response message\n".
					"%s\n",
					$content
				);
				break;
				
			case 'send_script':
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$content = $tpl_builder->build($params['script'], $dict);
				
				$out = sprintf(">>> Sending response script\n".
					"%s\n",
					$content
				);
				break;
				
			case 'worklist_open':
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
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
			case 'prompt_buttons':
				$actions =& $dict->_actions;
				
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$options = $tpl_builder->build($params['options'], $dict);
				$style = $tpl_builder->build(@$params['style'], $dict);
				
				$actions[] = array(
					'_action' => 'prompt.buttons',
					'_trigger_id' => $trigger->id,
					'options' => DevblocksPlatform::parseCrlfString($options),
					'style' => $style,
				);
				
				$dict->__exit = 'suspend';
				break;
				
			case 'prompt_text':
				$actions =& $dict->_actions;
				
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$placeholder = $tpl_builder->build($params['placeholder'], $dict);
				
				$actions[] = array(
					'_action' => 'prompt.text',
					'_trigger_id' => $trigger->id,
					'placeholder' => $placeholder,
				);
				
				$dict->__exit = 'suspend';
				break;
			
			case 'prompt_wait':
				$actions =& $dict->_actions;
				
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				
				$actions[] = array(
					'_action' => 'prompt.wait',
					'_trigger_id' => $trigger->id,
				);
				
				$dict->__exit = 'suspend';
				break;
			
			case 'send_message':
				$actions =& $dict->_actions;
				
				@$format = $params['format'];
				@$delay_ms = @$params['delay_ms'];
				
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
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
				
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$content = $tpl_builder->build($params['script'], $dict);
				
				$actions[] = array(
					'_action' => 'script.send',
					'_trigger_id' => $trigger->id,
					'script' => $content,
				);
				break;
				
			case 'worklist_open':
				$actions =& $dict->_actions;
				$query = null;
				
				if(!isset($params['context']) || empty($params['context']))
					break;
				
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				
				if(isset($params['quick_search']))
					$query = $tpl_builder->build($params['quick_search'], $dict);
				
				$actions[] = array(
					'_action' => 'worklist.open',
					'_trigger_id' => $trigger->id,
					'context' => $params['context'],
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