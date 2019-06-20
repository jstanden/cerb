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

abstract class AbstractEvent_Notification extends Extension_DevblocksEvent {
	protected $_event_id = null; // override

	/**
	 *
	 * @param integer $context_id
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel(Model_TriggerEvent $trigger, $context_id=null) {
		
		if(empty($context_id)) {
			// Pull the latest record
			list($results) = DAO_Notification::search(
				array(),
				array(),
				10,
				0,
				SearchFields_Notification::ID,
				false,
				false
			);
			
			shuffle($results);
			
			$result = array_shift($results);
			
			$context_id = $result[SearchFields_Notification::ID];
		}
		
		return new Model_DevblocksEvent(
			$this->_event_id,
			array(
				'context_id' => $context_id,
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
		
		// We can accept a model object or a context_id
		@$model = $event_model->params['context_model'] ?: $event_model->params['context_id'];
		
		/**
		 * Notification
		 */
		
		$merge_labels = array();
		$merge_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_NOTIFICATION, $model, $merge_labels, $merge_values, null, true);

			// Merge
			CerberusContexts::merge(
				'notification_',
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
		$context = CerberusContexts::CONTEXT_NOTIFICATION;
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
			'notification_id' => array(
				'label' => 'Notification',
				'context' => CerberusContexts::CONTEXT_NOTIFICATION,
			),
			'notification_assignee_id' => array(
				'label' => 'Notification assignee',
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
		
		$labels['notification_link'] = 'Notification is linked';
		
		$types['notification_link'] = null;

		$conditions = $this->_importLabelsTypesAsConditions($labels, $types);
		
		return $conditions;
	}
	
	function renderConditionExtension($token, $as_token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','condition'.$seq);
		
		switch($as_token) {
			case 'notification_link':
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				$tpl->display('devblocks:cerberusweb.core::events/condition_link.tpl');
				break;
		}

		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('params');
	}
	
	function runConditionExtension($token, $as_token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$pass = true;
		
		switch($as_token) {
			case 'notification_link':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				
				$from_context = null;
				$from_context_id = null;
				
				switch($as_token) {
					case 'notification_link':
						$from_context = CerberusContexts::CONTEXT_NOTIFICATION;
						@$from_context_id = $dict->notification_id;
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
					
					$pass = ($not) ? !$pass : $pass;
					
				} else {
					$pass = false;
				}
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
				'copy_notification' => [
					'label' =>'Copy notification',
					'notes' => '',
					'params' => [
						'worker_id' => [
							'type' => 'array',
							'required' => true,
							'notes' => 'An array of [worker](/docs/records/types/worker/) IDs, or placeholders containing worker IDs',
						]
					],
				],
				'set_notification_is_read' => [
					'label' => 'Set notification is read',
					'deprecated' => true,
				],
			]
			+ DevblocksEventHelper::getActionCustomFieldsFromLabels($this->getLabels($trigger))
			;
			
		return $actions;
	}
	
	function getActionDefaultOn() {
		return 'notification_id';
	}
	
	function renderActionExtension($token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','action'.$seq);

		$labels = $this->getLabels($trigger);
		$tpl->assign('token_labels', $labels);
			
		switch($token) {
			case 'copy_notification':
				/* @var $trigger Model_TriggerEvent */
				$event = $trigger->getEvent();
				
				$values_to_contexts = $event->getValuesContexts($trigger);
				$tpl->assign('values_to_contexts', $values_to_contexts);
				
				$tpl->display('devblocks:cerberusweb.core::events/model/notification/action_copy.tpl');
				break;
				
			case 'set_notification_is_read':
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
	
	function simulateActionCopyNotification($trigger, $params, DevblocksDictionaryDelegate $dict) {
		$worker_ids = $params['worker_id'];
		
		foreach($worker_ids as &$worker_id) {
			if(!is_numeric($worker_id) && isset($dict->$worker_id))
				if(false != ($new_worker_id = intval($dict->$worker_id)))
					$worker_id = $new_worker_id;
		}
		
		$workers = DAO_Worker::getIds($worker_ids);
		
		$out = ">>> Sending a copy of notification to:\n";
		
		foreach($workers as $worker) {
			$out .= " * " . $worker->getName() . "\n";
		}
		
		return $out;
	}
	
	function simulateActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$notification_id = $dict->notification_id;

		if(empty($notification_id))
			return;
		
		switch($token) {
			case 'copy_notification':
				return self::simulateActionCopyNotification($trigger, $params, $dict);
				break;
				
			case 'set_notification_is_read':
				return DevblocksEventHelper::simulateActionSetAbstractField('is read', Model_CustomField::TYPE_CHECKBOX, 'notification_is_read', $params, $dict);
				break;
				
			default:
				if(preg_match('#set_cf_(.*?_*)custom_([0-9]+)#', $token))
					return DevblocksEventHelper::simulateActionSetCustomField($token, $params, $dict);
				break;
		}
	}
	
	function runActionCopyNotification($trigger, $params, DevblocksDictionaryDelegate $dict) {
		if(false == (@$notification_id = $dict->notification_id))
			return;
		
		$worker_ids = $params['worker_id'];
		
		foreach($worker_ids as &$worker_id) {
			if(!is_numeric($worker_id) && isset($dict->$worker_id))
				if(false != ($new_worker_id = intval($dict->$worker_id)))
					$worker_id = $new_worker_id;
		}
		
		if(false == ($notification = DAO_Notification::get($notification_id)))
			return;
		
		$workers = DAO_Worker::getIds($worker_ids);

		foreach($workers as $worker) {
			$fields = [
				DAO_Notification::ACTIVITY_POINT => $notification->activity_point,
				DAO_Notification::CONTEXT => $notification->context,
				DAO_Notification::CONTEXT_ID => $notification->context_id,
				DAO_Notification::CREATED_DATE => time(),
				DAO_Notification::ENTRY_JSON => $notification->entry_json,
				DAO_Notification::IS_READ => 0,
				DAO_Notification::WORKER_ID => $worker->id,
			];
			DAO_Notification::create($fields);
		}
	}
	
	function runActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$notification_id = $dict->notification_id;

		if(empty($notification_id))
			return;
		
		switch($token) {
			case 'copy_notification':
				self::runActionCopyNotification($trigger, $params, $dict);
				break;
				
			case 'set_notification_is_read':
				if(false != ($notification = DAO_Notification::get($notification_id))) {
					$notification->markRead();
				}
				break;
				
			default:
				if(preg_match('#set_cf_(.*?_*)custom_([0-9]+)#', $token))
					return DevblocksEventHelper::runActionSetCustomField($token, $params, $dict);
				break;
		}
	}
	
};