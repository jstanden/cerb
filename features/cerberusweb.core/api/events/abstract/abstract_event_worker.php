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

abstract class AbstractEvent_Worker extends Extension_DevblocksEvent {
	protected $_event_id = null; // override

	/**
	 *
	 * @param integer $context_id
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel(Model_TriggerEvent $trigger, $context_id=null) {
		if(empty($context_id)) {
			$context_id = DAO_Worker::random();
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
		 * Worker
		 */
		
		@$context_id = $event_model->params['context_id'];
		$merge_labels = array();
		$merge_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $model, $merge_labels, $merge_values, null, true);

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
	
	function renderSimulatorTarget($trigger, $event_model) {
		$context = CerberusContexts::CONTEXT_WORKER;
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
			'worker_id' => array(
				'label' => 'Worker',
				'context' => CerberusContexts::CONTEXT_WORKER,
			),
			'worker_address_id' => array(
				'label' => 'Worker email',
				'context' => CerberusContexts::CONTEXT_ADDRESS,
			),
			'worker_address_org_id' => array(
				'label' => 'Worker org',
				'context' => CerberusContexts::CONTEXT_ORG,
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
		
		$labels['worker_calendar'] = 'Worker availability';
		
		$types['worker_calendar'] = null;
		
		$conditions = $this->_importLabelsTypesAsConditions($labels, $types);

		return $conditions;
	}
	
	function renderConditionExtension($token, $as_token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','condition'.$seq);
		
		switch($as_token) {
			case 'worker_calendar':
				$tpl->display('devblocks:cerberusweb.core::events/model/worker/condition_worker_calendar.tpl');
				break;
		}

		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('params');
	}
	
	function runConditionExtension($token, $as_token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$pass = true;
		
		switch($as_token) {
			case 'worker_calendar':
				@$worker_id = $dict->worker_id;
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();

				if(empty($worker_id)) {
					$pass = false;
					break;
				}
				
				@$from = $tpl_builder->build($params['from'], $dict);
				@$to = $tpl_builder->build($params['to'], $dict);
				$is_available = !empty($params['is_available']) ? 1 : 0;
				
				@$availability_calendar_id = $dict->worker_calendar_id;
				
				if(empty($availability_calendar_id)) {
					$pass = ($is_available) ? false : true;
					
				} else {
					if(false == ($calendar = DAO_Calendar::get($availability_calendar_id))) {
						$pass = false;
						break;
					}
					
					@$cal_from = strtotime("today", strtotime($from));
					@$cal_to = strtotime("tomorrow", strtotime($to));
					
					$calendar_events = $calendar->getEvents($cal_from, $cal_to);
					$availability = $calendar->computeAvailability($cal_from, $cal_to, $calendar_events);
					
					$pass = $availability->isAvailableBetween(strtotime($from), strtotime($to));
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
			array(
				'add_watchers' => array('label' =>'Add watchers'),
				'create_comment' => array('label' =>'Create comment'),
				'create_notification' => array('label' =>'Create notification'),
				'create_task' => array('label' =>'Create task'),
				'create_ticket' => array('label' =>'Create ticket'),
				'send_email' => array('label' => 'Send email'),
				'set_links' => array('label' => 'Set links'),
			)
			+ DevblocksEventHelper::getActionCustomFieldsFromLabels($this->getLabels($trigger))
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
			case 'add_watchers':
				DevblocksEventHelper::renderActionAddWatchers($trigger);
				break;
			
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
				
			case 'send_email':
				DevblocksEventHelper::renderActionSendEmail($trigger);
				break;

			case 'set_links':
				DevblocksEventHelper::renderActionSetLinks($trigger);
				break;

			default:
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
		@$worker_id = $dict->worker_id;

		if(empty($worker_id))
			return;
		
		switch($token) {
			case 'add_watchers':
				return DevblocksEventHelper::simulateActionAddWatchers($params, $dict, 'worker_id');
				break;
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
				return DevblocksEventHelper::simulateActionCreateTicket($params, $dict);
				break;
			case 'send_email':
				return DevblocksEventHelper::simulateActionSendEmail($params, $dict);
				break;
			case 'set_links':
				return DevblocksEventHelper::simulateActionSetLinks($trigger, $params, $dict);
				break;
			default:
				if(preg_match('#set_cf_(.*?_*)custom_([0-9]+)#', $token))
					return DevblocksEventHelper::simulateActionSetCustomField($token, $params, $dict);
				break;
		}
	}
	
	function runActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$worker_id = $dict->worker_id;

		if(empty($worker_id))
			return;
		
		switch($token) {
			case 'add_watchers':
				DevblocksEventHelper::runActionAddWatchers($params, $dict, 'worker_id');
				break;
			
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
				DevblocksEventHelper::runActionCreateTicket($params, $dict);
				break;
				
			case 'send_email':
				DevblocksEventHelper::runActionSendEmail($params, $dict);
				break;
				
			case 'set_links':
				DevblocksEventHelper::runActionSetLinks($trigger, $params, $dict);
				break;
				
			default:
				if(preg_match('#set_cf_(.*?_*)custom_([0-9]+)#', $token))
					return DevblocksEventHelper::runActionSetCustomField($token, $params, $dict);
				break;
		}
	}
	
};