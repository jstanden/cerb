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

class Event_InteractionChatWorker extends Extension_DevblocksEvent {
	const ID = 'event.interaction.chat.worker';
	
	/**
	 *
	 * Enter description here ...
	 * @param CerberusParserModel $parser_model
	 */
	static function trigger($worker_id, $message, array &$actions) {
		$events = DevblocksPlatform::getEventService();
		return $events->trigger(
			new Model_DevblocksEvent(
				self::ID,
				array(
					'worker_id' => $worker_id,
					'message' => $message,
					'actions' => &$actions,
					/*
					'_whisper' => array(
						'cerberusweb.contexts.app' => array(0),
					),
					*/
				)
			)
		);
	}

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
			)
		);
	}
	
	function setEvent(Model_DevblocksEvent $event_model=null, Model_TriggerEvent $trigger=null) {
		$labels = array();
		$values = array();
		
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
		
		/**
		 * Return
		 */

		$this->setLabels($labels);
		$this->setValues($values);
	}
	
	function getValuesContexts($trigger) {
		$vals = array(
			'sender_id' => array(
				'label' => 'Sender',
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
			case 'send_message':
				$actions =& $dict->_actions;
				
				@$format = $params['format'];
				
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