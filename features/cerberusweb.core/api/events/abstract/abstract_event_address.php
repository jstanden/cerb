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

abstract class AbstractEvent_Address extends Extension_DevblocksEvent {
	protected $_event_id = null; // override

	/**
	 *
	 * @param integer $context_id
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel(Model_TriggerEvent $trigger, $context_id=null) {
		
		if(empty($context_id)) {
			// Pull the latest record
			list($results) = DAO_Address::search(
				array(),
				array(),
				10,
				0,
				SearchFields_Address::ID,
				false,
				false
			);
			
			shuffle($results);
			
			$result = array_shift($results);
			
			$context_id = $result[SearchFields_Address::ID];
		}
		
		return new Model_DevblocksEvent(
			$this->_event_id,
			array(
				'context_id' => $context_id,
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
		
		// We can accept a model object or a context_id
		@$model = $event_model->params['context_model'] ?: $event_model->params['context_id'];
		
		/**
		 * Address
		 */
		
		$merge_labels = [];
		$merge_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_ADDRESS, $model, $merge_labels, $merge_values, null, true);

			// Merge
			CerberusContexts::merge(
				'email_',
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
		$context = CerberusContexts::CONTEXT_ADDRESS;
		$context_id = $event_model->params['context_id'];
		DevblocksEventHelper::renderSimulatorTarget($context, $context_id, $trigger, $event_model);
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
			'email_id' => array(
				'label' => 'Email',
				'context' => CerberusContexts::CONTEXT_ADDRESS,
			),
			'email_org_id' => array(
				'label' => 'Email org',
				'context' => CerberusContexts::CONTEXT_ORG,
			),
			'email_org_watchers' => array(
				'label' => 'Email org watchers',
				'context' => CerberusContexts::CONTEXT_WORKER,
				'is_multiple' => true,
			),
			'email_watchers' => array(
				'label' => 'Email watchers',
				'context' => CerberusContexts::CONTEXT_WORKER,
				'is_multiple' => true,
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
		
		$labels['email_link'] = 'Email is linked';
		$labels['email_org_link'] = 'Email org is linked';
		$labels['email_org_watcher_count'] = 'Email org watcher count';
		$labels['email_watcher_count'] = 'Email watcher count';
		
		$types['email_link'] = null;
		$types['email_org_link'] = null;
			
		$types['email_org_watcher_count'] = null;
		$types['email_watcher_count'] = null;

		$conditions = $this->_importLabelsTypesAsConditions($labels, $types);
		
		return $conditions;
	}
	
	function renderConditionExtension($token, $as_token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','condition'.$seq);
		
		switch($as_token) {
			case 'email_link':
			case 'email_org_link':
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				$tpl->display('devblocks:cerberusweb.core::events/condition_link.tpl');
				break;
				
			case 'email_org_watcher_count':
			case 'email_watcher_count':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_number.tpl');
				break;
		}

		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('params');
	}
	
	function runConditionExtension($token, $as_token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$pass = true;
		
		switch($as_token) {
			case 'email_link':
			case 'email_org_link':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				
				$from_context = null;
				$from_context_id = null;

				switch($as_token) {
					case 'email_link':
						$from_context = CerberusContexts::CONTEXT_ADDRESS;
						@$from_context_id = $dict->email_id;
						break;
					case 'email_org_link':
						$from_context = CerberusContexts::CONTEXT_ORG;
						@$from_context_id = $dict->email_org_id;
						break;
					default:
						$pass = false;
				}
				
				// Get links by context+id
				
				if(!empty($from_context) && !empty($from_context_id)) {
					@$context_strings = $params['context_objects'];
					$links = DAO_ContextLink::intersect($from_context, $from_context_id, $context_strings);
					
					// OPER: any, !any, all
					switch($oper) {
						case 'in':
							$pass = (is_array($links) && !empty($links));
							break;
						case 'all':
							$pass = (is_array($links) && count($links) == count($context_strings));
							break;
						default:
							$pass = false;
							break;
					}
					
				} else {
					$pass = false;
				}
				
				$pass = ($not) ? !$pass : $pass;
				break;
				
			case 'email_org_watcher_count':
			case 'email_watcher_count':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');

				switch($as_token) {
					case 'email_org_watcher_count':
						$value = count($dict->email_org_watchers);
						break;
					default:
						$value = count($dict->email_watchers);
						break;
				}
				
				switch($oper) {
					case 'is':
						$pass = intval($value)==intval($params['value']);
						break;
					case 'gt':
						$pass = intval($value) > intval($params['value']);
						break;
					case 'lt':
						$pass = intval($value) < intval($params['value']);
						break;
				}
				
				$pass = ($not) ? !$pass : $pass;
				break;
				
			default:
				$pass = false;
				break;
		}
		
		return $pass;
	}
	
	function getActionExtensions(Model_TriggerEvent $trigger) {
		$actions =
			[
				'set_is_banned' => [
					'label' => 'Set is banned',
					'deprecated' => true,
				],
				'set_is_defunct' => [
					'label' => 'Set is defunct',
					'deprecated' => true,
				],
			]
			+ DevblocksEventHelper::getActionCustomFieldsFromLabels($this->getLabels($trigger))
			;
			
		return $actions;
	}
	
	function getActionDefaultOn() {
		return 'email_id';
	}
	
	function renderActionExtension($token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','action'.$seq);

		$labels = $this->getLabels($trigger);
		$tpl->assign('token_labels', $labels);
			
		switch($token) {
			case 'set_is_banned':
			case 'set_is_defunct':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_bool.tpl');
				break;
				
			default:
				$matches = [];
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
		@$address_id = $dict->email_id;

		if(empty($address_id))
			return;
		
		switch($token) {
			case 'set_is_banned':
				return DevblocksEventHelper::simulateActionSetAbstractField('is banned', Model_CustomField::TYPE_CHECKBOX, 'email_is_banned', $params, $dict);
				break;
			case 'set_is_defunct':
				return DevblocksEventHelper::simulateActionSetAbstractField('is defunct', Model_CustomField::TYPE_CHECKBOX, 'email_is_defunct', $params, $dict);
				break;
			default:
				if(preg_match('#set_cf_(.*?_*)custom_([0-9]+)#', $token))
					return DevblocksEventHelper::simulateActionSetCustomField($token, $params, $dict);
				break;
		}
	}
	
	function runActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$address_id = $dict->email_id;

		if(empty($address_id))
			return;
		
		switch($token) {
			case 'set_is_banned':
				@$value = $params['value'];
				@$bit = !empty($value) ? 1 : 0;
				
				DAO_Address::update($address_id, array(
					DAO_Address::IS_BANNED => $bit,
				));
				$dict->is_banned = $bit;
				break;
				
			case 'set_is_defunct':
				@$value = $params['value'];
				@$bit = !empty($value) ? 1 : 0;
				
				DAO_Address::update($address_id, array(
					DAO_Address::IS_DEFUNCT => $bit,
				));
				$dict->is_defunct = $bit;
				break;
				
			default:
				if(preg_match('#set_cf_(.*?_*)custom_([0-9]+)#', $token))
					return DevblocksEventHelper::runActionSetCustomField($token, $params, $dict);
				break;
		}
	}
	
};