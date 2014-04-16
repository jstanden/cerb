<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2014, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/

abstract class AbstractEvent_Record extends Extension_DevblocksEvent {
	protected $_event_id = null; // override
	protected $_delegate = null;
	protected $_model = null;
	
	/**
	 *
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel(Model_TriggerEvent $trigger, $context_id=null) {
		@$context = $trigger->event_params['context'];
		$change_fields = array();

		$old_model = null;
		$new_model = null;
		
		if(empty($context_id)) {
			if(null == ($context_ext = Extension_DevblocksContext::get($context)))
				return;
			
			if(false != ($dao_class = $context_ext->getDaoClass())) {
				$context_id = $dao_class::random();
			}
		}
		
		if(!empty($context_id)) {
			$old_model = $context_id;
			$new_model = $context_id;
		}
		
		return new Model_DevblocksEvent(
			$this->_event_id,
			array(
				'context' => @$trigger->event_params['context'],
				'context_id' => $context_id,
				'old_model' => $old_model,
				'new_model' => $new_model,
				'_trigger' => $trigger,
			)
		);
	}

	/**
	 *
	 * @param string $context
	 * @param integer $context_id
	 * @return Extension_DevblocksEvent|null
	 */
	private function _getCustomContextBehavior($context, $model=null) {
		if(!is_null($this->_delegate))
			return $this->_delegate;
		
		if(empty($context))
			return false;
		
		// Load the VA of the current macro
		$macros = Extension_DevblocksEvent::getAll();
		
		$macros = array_filter($macros, function($event) use ($context) {
			@$event_context = $event->params['macro_context'];
			return ($event_context == $context);
		});
		
		if(empty($macros))
			return false;
		
		$mft = array_shift($macros); /* @var $mft DevblocksExtensionManifest */
		
		$delegate = $mft->createInstance();
		
		$event_model = new Model_DevblocksEvent(
			$delegate->manifest->id,
			array(
				'context_model' => $model,
			)
		);
		
		$delegate->setEvent($event_model);
		
		$this->_delegate = $delegate;
		
		return $delegate;
	}
	
	function setEvent(Model_DevblocksEvent $event_model=null, Model_TriggerEvent $trigger) {
		$labels = array();
		$values = array();

		@$context = $trigger->event_params['context'];
		@$new_model = $event_model->params['new_model'];
		@$old_model = $event_model->params['old_model'];
		
		if(is_null($this->_model) && !empty($new_model))
			$this->_model = $new_model;
		
		/*
		 * Delegate to the custom behavior for this context type
		 */
		
		if(false == ($delegate = $this->_getCustomContextBehavior($context, $this->_model)))
			return;
		
		$macro_labels = $delegate->getLabels($trigger);
		$macro_values = $delegate->getValues();
		
		// New values
		CerberusContexts::merge(
			'',
			'(New) ',
			$macro_labels,
			$macro_values,
			$labels,
			$values
		);
		
		if(is_array($old_model) && !empty($old_model)) {
			if(null == ($context_ext = Extension_DevblocksContext::get($context)))
				return;
			
			$event_model = new Model_DevblocksEvent(
				$delegate->manifest->id,
				array(
					'context_model' => $old_model,
				)
			);
			
			$delegate->setEvent($event_model);
			
			$macro_labels = $delegate->getLabels($trigger);
			$macro_values = $delegate->getValues();
		}
		
		// Old values
		CerberusContexts::merge(
			'old_',
			'(Old) ',
			$macro_labels,
			$macro_values,
			$labels,
			$values
		);
		
		/**
		 * Return
		 */

		$this->setLabels($labels);
		$this->setValues($values);
	}

	function renderSimulatorTarget($trigger, $event_model) {
		$context = @$trigger->event_params['context'];
		$context_id = $event_model->params['context_id'];
		DevblocksEventHelper::renderSimulatorTarget($context, $context_id, $trigger, $event_model);
	}
	
	function getValuesContexts($trigger) {
		// [TODO] Test what trigger vars do here
		if(false == ($delegate = $this->_getCustomContextBehavior($trigger->event_params['context'])))
			return;
		
		$vals_to_ctx = $delegate->getValuesContexts($trigger);
		
		// [TODO] This needs to still work with linked vars
		
		/*
		$vals = array(
			'record_id' => array(
				'label' => 'Record',
				'context' => @$trigger->event_params['context'],
			),
			'va_watchers' => array(
				'label' => 'Virtual Attendant Watchers',
				'context' => CerberusContexts::CONTEXT_WORKER,
			),
		);
		*/
		
		//$vars = parent::getValuesContexts($trigger);
		
		//$vals_to_ctx = array_merge($vals, $vars);
		//asort($vals_to_ctx);
		
		return $vals_to_ctx;
	}
	
	function renderEventParams(Model_TriggerEvent $trigger=null) {
		$tpl = DevblocksPlatform::getTemplateService();

		// [TODO] Formal watched change fields
		
		$tpl->assign('trigger', $trigger);
		
		$contexts = Extension_DevblocksContext::getAll();
		$events = Extension_DevblocksEvent::getAll();
		
		$macro_contexts = array();
		
		foreach($events as $event) {
			@$event_context = $event->params['macro_context'];
			
			if(!empty($event_context) && isset($contexts[$event_context]))
				$macro_contexts[] = $contexts[$event_context];
		}
		
		DevblocksPlatform::sortObjects($macro_contexts, 'name');
		
		$tpl->assign('contexts', $macro_contexts);
		
		$tpl->display('devblocks:cerberusweb.core::events/record/params_record_changed.tpl');
	}
	
	function getConditionExtensions(Model_TriggerEvent $trigger) {
		$labels = $this->getLabels($trigger);
		$types = $this->getTypes();
		
		$conditions = $this->_importLabelsTypesAsConditions($labels, $types);
		
		return $conditions;
	}
	
	function renderConditionExtension($token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','condition'.$seq);
		
		switch($token) {
			default:
				if(false == ($delegate = $this->_getCustomContextBehavior($trigger->event_params['context'], $this->_model)))
					break;
				
				return $delegate->renderConditionExtension($token, $trigger, $params, $seq);
				break;
		}

		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('params');
	}
	
	function runConditionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$pass = true;
		
		switch($token) {
			default:
				if(false == ($delegate = $this->_getCustomContextBehavior($trigger->event_params['context'], $this->_model)))
					break;
				
				$pass = $delegate->runConditionExtension($token, $trigger, $params, $dict);
				break;
		}
		
		return $pass;
	}
	
	function getActionExtensions(Model_TriggerEvent $trigger) {
		if(false == ($delegate = $this->_getCustomContextBehavior($trigger->event_params['context'])))
			return;
		
		$actions = $delegate->getActionExtensions($trigger);
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
			default:
				if(false == ($delegate = $this->_getCustomContextBehavior($trigger->event_params['context'])))
					break;
				
				return $delegate->renderActionExtension($token, $trigger, $params, $seq);
				break;
		}
		
		$tpl->clearAssign('params');
		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('token_labels');
	}
	
	function simulateActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {

		switch($token) {
			default:
				if(false == ($delegate = $this->_getCustomContextBehavior($trigger->event_params['context'], $this->_model)))
					break;
				
				return $delegate->simulateAction($token, $trigger, $params, $dict);
				break;
		}
	}
	
	function runActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		
		switch($token) {
			default:
				if(false == ($delegate = $this->_getCustomContextBehavior($trigger->event_params['context'], $this->_model)))
					break;
				
				$delegate->runActionExtension($token, $trigger, $params, $dict);
				break;
		}
	}
	
};