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

class Event_UiCardEditorOpenedByWorker extends Extension_DevblocksEvent {
	const ID = 'event.ui.card.editor.opened.worker';

	protected $_event_id = null; // override
	
	function __construct($manifest) {
		parent::__construct($manifest);
		$this->_event_id = self::ID;
	}
	
	static function triggerForWorker($worker, $context, $view_id, &$actions, &$triggers) {
		if(empty($worker) || !($worker instanceof Model_Worker))
			return;
		
		$macros = DAO_TriggerEvent::getReadableByActor(
			$worker,
			Event_UiCardEditorOpenedByWorker::ID,
			false,
			true
		);
		
		$context_mfts = Extension_DevblocksContext::getAll();
		
		// Filter macros by context
		$macros = array_filter($macros, function($behavior) use ($context_mfts, $context) { /* @var $behavior Model_TriggerEvent */
			$listen_contexts = DevblocksPlatform::parseCrlfString($behavior->event_params['listen_contexts']);
			
			if(in_array('*', $listen_contexts))
				return true;
			
			if(false == ($context_mft = $context_mfts[$context]))
				return false;
			
			return in_array($context_mft->params['alias'], $listen_contexts);
		});
		
		if(is_array($macros))
		foreach($macros as $macro) { /* @var $macro Model_TriggerEvent */
			$new_actions = [];
			Event_UiCardEditorOpenedByWorker::trigger($macro->id, $context, $view_id, $new_actions);
			
			if(!empty($new_actions)) {
				foreach($new_actions as $idx => $action) {
					if(isset($actions[$idx]))
						$actions[$idx] = array_merge($actions[$idx], $action);
					else
						$actions[$idx] = $action;
				}
				$triggers[] = $macro;
			}
		}
	}
	
	static function trigger($trigger_id, $context, $context_id, &$actions) {
		$events = DevblocksPlatform::services()->event();
		$active_worker = CerberusApplication::getActiveWorker();
		
		return $events->trigger(
			new Model_DevblocksEvent(
				self::ID,
				array(
					'_caller_actions' => &$actions,
					'context' => $context,
					'context_id' => $context_id,
					'worker' => $active_worker,
					'_whisper' => [
						'_trigger_id' => [$trigger_id],
					],
				)
			)
		);
	}
	
	/**
	 *
	 * @param string $context
	 * @param string $view_id
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel(Model_TriggerEvent $trigger, $context=null, $context_id=null) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		return new Model_DevblocksEvent(
			$this->_event_id,
			array(
				'context' => $context ?: 'cerberusweb.contexts.ticket',
				'context_id' => $context_id ?: 0,
				'worker' => $active_worker,
			)
		);
	}
	
	function setEvent(Model_DevblocksEvent $event_model=null, Model_TriggerEvent $trigger=null) {
		@$context = $event_model->params['context'];
		@$context_id = $event_model->params['context_id'];
		@$worker = $event_model->params['worker'];
		
		$labels = $values = [];
		
		/**
		 * Behavior
		 */
		
		$merge_labels = $merge_values = [];
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
			
		/**
		 * Worker
		 */
		
		$merge_labels = $merge_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $worker, $merge_labels, $merge_values, null, true);

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
		 * Record
		 */
		
		$labels['record__context'] = 'Record Type';
		$values['record__context'] = $context;
		$values['_types']['record__context'] = null;
		
		$labels['record_id'] = 'Record ID';
		$values['record_id'] = $context_id;
		$values['_types']['record_id'] = 'id';
		
		/**
		 * Caller actions
		 */
		
		if(isset($event_model->params['_caller_actions'])) {
			$values['_caller_actions'] =& $event_model->params['_caller_actions'];
		}
			
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
	
	function renderEventParams(Model_TriggerEvent $trigger=null) {
		$tpl = DevblocksPlatform::services()->template();

		$tpl->assign('trigger', $trigger);
		
		$points = [
			'*' => DevblocksPlatform::translateCapitalized('common.everything'),
		];
		$contexts = Extension_DevblocksContext::getAll(false);
		
		foreach($contexts as $context) {
			$points[$context->params['alias']] = 'Record: ' . $context->name;
		}
		
		$menu = Extension_DevblocksContext::getPlaceholderTree($points, ':','');
		$tpl->assign('menu', $menu);
		
		$tpl->display('devblocks:cerberusweb.core::events/record/params_ui_card_editor_opened.tpl');
	}
	
	function getConditionExtensions(Model_TriggerEvent $trigger) {
		$labels = $this->getLabels($trigger);
		$types = $this->getTypes();
		
		$labels['record__context'] = 'Record Type';
		$labels['record_id'] = 'Record ID';
		
		$types['record__context'] = null;
		$types['record_id'] = Model_CustomField::TYPE_SINGLE_LINE;

		$conditions = $this->_importLabelsTypesAsConditions($labels, $types);
		
		return $conditions;
	}
	
	function renderConditionExtension($token, $as_token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','condition'.$seq);
		
		/*
		switch($as_token) {
			case 'context':
				$contexts = Extension_DevblocksContext::getAll(false, array('workspace'));
				$options = array();
				
				foreach($contexts as $context_mft) {
					$options[$context_mft->id] = $context_mft->name;
				}
				
				$tpl->assign('options', $options);
				
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_list.tpl');
				break;
		}
		*/

		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('params');
	}

	function runConditionExtension($token, $as_token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$pass = true;

		/*
		switch($as_token) {
			case 'context':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				@$value = $dict->$token;
				
				if(!isset($params['values']) || !is_array($params['values'])) {
					$pass = false;
					break;
				}
				
				switch($oper) {
					case 'in':
						$pass = false;
						foreach($params['values'] as $v) {
							if($v == $value) {
								$pass = true;
								break;
							}
						}
						break;
				}
				$pass = ($not) ? !$pass : $pass;
				break;
		}
		*/
		
		return $pass;
	}
	
	function getActionExtensions(Model_TriggerEvent $trigger) {
		$actions =
			array(
				'exec_jquery' => array('label' =>'Execute jQuery script'),
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
			case 'exec_jquery':
				$tpl->display('devblocks:cerberusweb.core::events/ui/card/editor/action_exec_jquery.tpl');
				break;
		}
		
		$tpl->clearAssign('params');
		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('token_labels');
	}
	
	function simulateActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		switch($token) {
			case 'exec_jquery':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$script = $tpl_builder->build($params['jquery_script'], $dict);
				
				$out = sprintf(">>> Executing jQuery script:\n\n%s\n",
					$script
				);
				return $out;
				break;
		}
	}
	
	function runActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		switch($token) {
			case 'exec_jquery':
				// Return the parsed script to the caller
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$dict->_caller_actions['jquery_scripts'][] = $tpl_builder->build($params['jquery_script'], $dict);
				break;
		}
	}
};