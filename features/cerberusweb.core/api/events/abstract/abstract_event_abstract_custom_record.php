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

abstract class AbstractEvent_AbstractCustomRecord extends Extension_DevblocksEvent {
	protected $_event_id = null; // override
	protected $_context = null;
	
	private function _getContext($trigger) {
		if(is_null($this->_context)) {
			if(is_null($trigger)) {
				$parts = explode('_', get_called_class());
				$custom_record_id = intval(end($parts));
				$this->_context = sprintf("contexts.custom_record.%d", $custom_record_id);
				
			} else {
				$parts = explode('.', $trigger->event_point);
				$custom_record_id = intval(end($parts));
				$this->_context = sprintf("contexts.custom_record.%d", $custom_record_id);
			}
		}
		
		return $this->_context;
	}
	
	/**
	 *
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel(Model_TriggerEvent $trigger, $context_id=null) {
		@$context = $this->_getContext($trigger);
		
		if(empty($context_id)) {
			if(null == ($context_ext = Extension_DevblocksContext::get($context)))
				return;
			
			if(false != ($dao_class = $context_ext->getDaoClass())) {
				$context_id = $dao_class::random();
			}
		}
		
		$event_model = new Model_DevblocksEvent(
			$this->_event_id,
			array(
				'context' => $context,
				'context_id' => $context_id,
				'actor' => CerberusContexts::getCurrentActor(),
				'__trigger' => $trigger,
			)
		);
		
		return $event_model;
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
		
		@$context = $this->_getContext($trigger);
		@$actor = $event_model->params['actor'];
		
		if(empty($actor))
			$actor = CerberusContexts::getCurrentActor();
		
		if(!empty($actor) && is_array($actor)) {
			$labels['actor__context'] = 'Actor type';
			$labels['actor__label'] = 'Actor name';
			$labels['actor_id'] = 'Actor id';
			$labels['actor_url'] = 'Actor record url';
			
			$values['actor__context'] = $actor['context'];
			$values['actor__label'] = $actor['name'];
			$values['actor_id'] = $actor['context_id'];
			$values['actor_url'] = $actor['url'];
			
			$values['_types'] = array(
				'actor__context' => null,
				'actor__label' => 'S',
				'actor_id' => 'S',
				'actor_url' => 'S',
			);
		}
		
		// We can accept a model object or a context_id
		@$model = $event_model->params['context_model'] ?: $event_model->params['context_id'];
		
		/**
		 * Custom Record
		 */
		
		$merge_labels = [];
		$merge_values = [];
		CerberusContexts::getContext($context, $model, $merge_labels, $merge_values, null, true);
		
			// Merge
			CerberusContexts::merge(
				'record_',
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

	function renderSimulatorTarget($trigger, $event_model) {
		$context = $this->_getContext($trigger);
		$context_id = $event_model->params['context_id'];
		DevblocksEventHelper::renderSimulatorTarget($context, $context_id, $trigger, $event_model);
	}
	
	function getValuesContexts($trigger) {
		$context = $this->_getContext($trigger);
		
		$vals = array(
			'behavior_id' => array(
				'label' => 'Behavior',
				'context' => CerberusContexts::CONTEXT_BEHAVIOR,
			),
			'behavior_bot_id' => array(
				'label' => 'Bot',
				'context' => CerberusContexts::CONTEXT_BOT,
			),
			'record_id' => array(
				'label' => 'Record',
				'context' => $context,
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
		
		$conditions = $this->_importLabelsTypesAsConditions($labels, $types);
		
		return $conditions;
	}
	
	function renderConditionExtension($token, $as_token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix','condition'.$seq);
		
		switch($as_token) {
			case 'actor__context':
				$context_exts = Extension_DevblocksContext::getAll(false);
				$options = array();
				
				// Only some contexts can be actors
				foreach($context_exts as $context_id => $context_mft) {
					switch($context_id) {
						case CerberusContexts::CONTEXT_APPLICATION:
						case CerberusContexts::CONTEXT_ROLE:
						case CerberusContexts::CONTEXT_GROUP:
						case CerberusContexts::CONTEXT_WORKER:
						case CerberusContexts::CONTEXT_BOT:
							$options[$context_id] = $context_mft->name;
							break;
					}
				}
				
				$tpl->assign('options', $options);
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_list.tpl');
				break;
				
			default:
				break;
		}

		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('params');
	}
	
	function runConditionExtension($token, $as_token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$pass = true;
		
		switch($as_token) {
			case 'actor__context':
				if(isset($dict->$as_token) && isset($params['values']) && is_array($params['values'])) {
					$pass = in_array($dict->$as_token, $params['values']);
				} else {
					$pass = false;
				}
				break;
				
			default:
				break;
		}
		
		return $pass;
	}
	
	function getActionExtensions(Model_TriggerEvent $trigger) {
		$actions =
			[
				'set_name' => ['label' => 'Set name'],
			]
			+ DevblocksEventHelper::getActionCustomFieldsFromLabels($this->getLabels($trigger))
			;
			
		return $actions;
	}
	
	function getActionDefaultOn() {
		return 'record_id';
	}
	
	function renderActionExtension($token, $trigger, $params=[], $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','action'.$seq);

		$labels = $this->getLabels($trigger);
		$tpl->assign('token_labels', $labels);
		
		switch($token) {
			case 'set_name':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_string.tpl');
				break;
				
			default:
				$matches=[];
				if(preg_match('#set_cf_(.*?_*)custom_([0-9]+)#', $token, $matches)) {
					$field_id = $matches[2];
					$custom_field = DAO_CustomField::get($field_id);
					DevblocksEventHelper::renderActionSetCustomField($custom_field, $trigger);
				}
				break;
		}
		
		$tpl->clearAssign('params');
		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('token_labels');
	}
	
	function simulateActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		switch($token) {
			case 'set_name':
				return DevblocksEventHelper::simulateActionSetAbstractField('name', Model_CustomField::TYPE_SINGLE_LINE, 'record_name', $params, $dict);
				break;
			
			default:
				if(preg_match('#set_cf_(.*?_*)custom_([0-9]+)#', $token))
					return DevblocksEventHelper::simulateActionSetCustomField($token, $params, $dict);
				break;
		}
	}
	
	function runActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$record_id = $dict->record_id;
		
		switch($token) {
			case 'set_name':
				$context = $this->_getContext($trigger);
				$context_ext = Extension_DevblocksContext::get($context);
				$dao_class = $context_ext->getDaoClass();
				
				@$value = $params['value'];
				
				$dao_class::update($record_id, array(
					'name' => $value,
				));
				$dict->name = $value;
				break;
				
			default:
				if(preg_match('#set_cf_(.*?_*)custom_([0-9]+)#', $token))
					return DevblocksEventHelper::runActionSetCustomField($token, $params, $dict);
				break;
		}
	}
	
};