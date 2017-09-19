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

class Event_NewMessageChatPortal extends Extension_DevblocksEvent {
	const ID = 'event.message.chat.portal';
	
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
		$actions = array();
		
		return new Model_DevblocksEvent(
			self::ID,
			array(
				'message' => 'This is a test message',
				'actions' => &$actions,
				
				'cookie' => null,
				'portal_code' => null,
				'interaction' => null,
				'interaction_params' => [],
				'interaction_behavior_id' => 0,
				'interaction_bot_image' => null,
				'interaction_bot_name' => 'Cerb',
				'client_browser' => null,
				'client_browser_version' => null,
				'client_ip' => null,
				'client_platform' => null,
				'client_time' => null,
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
		
		// Message
		@$message = $event_model->params['message'];
		$labels['message'] = 'Message';
		$values['message'] = $message;
		
		// Actions
		$values['_actions'] =& $event_model->params['actions'];
		
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
		
		// Cookie
		@$cookie = $event_model->params['cookie'];
		$labels['cookie'] = 'Cookie';
		$values['cookie'] = $cookie;
		
		// Portal
		@$portal_code = $event_model->params['portal_code'];
		$labels['portal_code'] = 'Portal Code';
		$values['portal_code'] = $portal_code;
		
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
		@$client_time = $event_model->params['client_time'];
		@$client_url = $event_model->params['client_url'];
		
		$labels['client_browser'] = 'Client Browser';
		$labels['client_browser_version'] = 'Client Browser Version';
		$labels['client_ip'] = 'Client IP';
		$labels['client_platform'] = 'Client Platform';
		$labels['client_time'] = 'Client Time';
		$labels['client_url'] = 'Client URL';
		
		$values['client_browser'] = $client_browser;
		$values['client_browser_version'] = $client_browser_version;
		$values['client_ip'] = $client_ip;
		$values['client_platform'] = $client_platform;
		$values['client_time'] = $client_time;
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
				'label' => 'Behavior',
				'context' => CerberusContexts::CONTEXT_BEHAVIOR,
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
		
		// Cookie
		$labels['cookie'] = 'Cookie';
		$types['cookie'] = Model_CustomField::TYPE_SINGLE_LINE;
		
		// Portal
		$labels['portal_code'] = 'Portal Code';
		$types['portal_code'] = Model_CustomField::TYPE_SINGLE_LINE;
		
		// Interaction
		$labels['interaction'] = 'Interaction';
		$types['interaction'] = Model_CustomField::TYPE_SINGLE_LINE;
		
		// Interaction Parameters
		$labels['interaction_params'] = 'Interaction Params';
		$types['interaction_params'] = null;
		
		// Bot
		$labels['interaction_bot_name'] = 'Bot Name';
		$types['interaction_bot_name'] = Model_CustomField::TYPE_SINGLE_LINE;
		
		$labels['interaction_bot_image'] = 'Bot Image';
		$types['interaction_bot_image'] = Model_CustomField::TYPE_SINGLE_LINE;
		
		// Behavior
		// [TODO] Expand
		$labels['interaction_behavior_id'] = 'Behavior ID';
		$types['interaction_behavior_id'] = Model_CustomField::TYPE_NUMBER;
		
		// Client
		$labels['client_browser'] = 'Client Browser';
		$labels['client_browser_version'] = 'Client Browser Version';
		$labels['client_ip'] = 'Client IP';
		$labels['client_platform'] = 'Client Platform';
		$labels['client_time'] = 'Client Time';
		$labels['client_url'] = 'Client URL';
		
		$types['client_browser'] = Model_CustomField::TYPE_SINGLE_LINE;
		$types['client_browser_version'] = Model_CustomField::TYPE_SINGLE_LINE;
		$types['client_ip'] = Model_CustomField::TYPE_SINGLE_LINE;
		$types['client_platform'] = Model_CustomField::TYPE_SINGLE_LINE;
		$types['client_time'] = Model_CustomField::TYPE_SINGLE_LINE;
		$types['client_url'] = Model_CustomField::TYPE_SINGLE_LINE;

		$conditions = $this->_importLabelsTypesAsConditions($labels, $types);
		
		return $conditions;
	}
	
	function renderConditionExtension($token, $as_token, $trigger, $params=array(), $seq=null) {
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
				'prompt_buttons' => array('label' => 'Prompt with buttons'),
				'prompt_images' => array('label' => 'Prompt with images'),
				'prompt_rating_number' => array('label' => 'Prompt with numeric rating scale'),
				'prompt_text' => array('label' => 'Prompt with text input'),
				'prompt_wait' => array('label' => 'Prompt with wait'),
				'send_message' => array('label' => 'Respond with message'),
				'send_script' => array('label' => 'Respond with script'),
				'switch_behavior' => array('label' => 'Switch conversational behavior'),
			)
			;
		
		return $actions;
	}
	
	function renderActionExtension($token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','action'.$seq);

		$labels = $this->getLabels($trigger);
		$tpl->assign('token_labels', $labels);
			
		switch($token) {
			case 'prompt_buttons':
				$tpl->display('devblocks:cerberusweb.core::events/pm/action_prompt_buttons.tpl');
				break;
				
			case 'prompt_images':
				$tpl->display('devblocks:cerberusweb.core::events/pm/action_prompt_images.tpl');
				break;
			
			case 'prompt_rating_number':
				$tpl->display('devblocks:cerberusweb.core::events/pm/action_prompt_rating_number.tpl');
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
				
			case 'switch_behavior':
				$tpl->display('devblocks:cerb.bots.portal.widget::widget/convo/event/action_switch_behavior.tpl');
				break;
		}
		
		$tpl->clearAssign('params');
		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('token_labels');
	}
	
	function simulateActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		switch($token) {
			case 'prompt_buttons':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$options = $tpl_builder->build($params['options'], $dict);
				
				$out = sprintf(">>> Prompting with buttons:\n".
					"%s\n",
					$options
				);
				break;
				
			case 'prompt_images':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				
				$out = sprintf(">>> Prompting with buttons:\n"
				);
				break;
			
			case 'prompt_rating_number':
				$options = [
					'label_from' => @$params['label_from'],
					'label_to' => @$params['label_to'],
					'range_from' => @$params['range_from'],
					'range_to' => @$params['range_to'],
					'color_from' => @$params['color_from'],
					'color_from' => @$params['color_from'],
					'color_mid' => @$params['color_mid'],
				];
				
				$out = sprintf(">>> Prompting with numeric rating:\n".
					"%s\n",
					$options
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
				//$placeholder = $tpl_builder->build($params['placeholder'], $dict);
				
				$out = sprintf(">>> Prompting with wait\n");
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
				@$behavior_id = $params['behavior_id'];
				//$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				//$content = $tpl_builder->build($params['script'], $dict);
				
				$out = sprintf(">>> Switching behavior\n".
					"%d\n",
					$behavior_id
				);
				break;
		}
		
		return $out;
	}
	
	function runActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		switch($token) {
			case 'prompt_buttons':
				$actions =& $dict->_actions;
				
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$options = $tpl_builder->build(@$params['options'], $dict);
				$style = $tpl_builder->build(@$params['style'], $dict);
				
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
					'options' => DevblocksPlatform::parseCrlfString($options),
					'style' => $style,
					'color_from' => $color_from,
					'color_to' => $color_to,
					'color_mid' => $color_mid,
				);
				
				$dict->__exit = 'suspend';
				break;
				
			case 'prompt_images':
				$actions =& $dict->_actions;
				
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$images = $params['images'];
				$labels = $params['labels'];
				$actions[] = array(
					'_action' => 'prompt.images',
					'_trigger_id' => $trigger->id,
					'images' => $images,
					'labels' => $labels,
				);
				
				$dict->__exit = 'suspend';
				break;
				
			case 'prompt_rating_number':
				$actions =& $dict->_actions;
				
				$options = [
					'range_from' => @$params['range_from'] ?: 0,
					'range_to' => @$params['range_to'] ?: 10,
					'color_from' => @$params['color_from'] ?: '#FF0000',
					'color_to' => @$params['color_to'] ?: '#19B700',
					'color_mid' => @$params['color_mid'] ?: '#FFFFFF',
					'label_from' => @$params['label_from'] ?: '',
					'label_to' => @$params['label_to'] ?: '',
				];
				
				$actions[] = array(
					'_action' => 'prompt.rating.number',
					'_trigger_id' => $trigger->id,
					'options' => $options,
				);
				
				$dict->__exit = 'suspend';
				break;
				
			case 'prompt_text':
				$actions =& $dict->_actions;
				
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
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
				
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				//$placeholder = $tpl_builder->build($params['placeholder'], $dict);
				
				$actions[] = array(
					'_action' => 'prompt.wait',
					'_trigger_id' => $trigger->id,
					//'placeholder' => $placeholder,
				);
				
				// [TODO] Delays should be configurable
				// [TODO] This should be configurable
				$dict->__exit = 'suspend';
				break;
			
			case 'send_message':
				$actions =& $dict->_actions;
				
				@$delay_ms = @$params['delay_ms'];
				@$format = $params['format'];
				
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
					'delay_ms' => $delay_ms,
					'format' => $format,
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
				
				// Variables as parameters
				
				$vars = array();
				
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
		}
	}
};