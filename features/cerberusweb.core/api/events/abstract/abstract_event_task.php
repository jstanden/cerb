<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2012, WebGroup Media LLC
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

abstract class AbstractEvent_Task extends Extension_DevblocksEvent {
	protected $_event_id = null; // override

	/**
	 * 
	 * @param integer $task_id
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel($task_id=null) {
		
		if(empty($task_id)) {
			$task_id = DAO_Task::random();
		}
		
		return new Model_DevblocksEvent(
			$this->_event_id,
			array(
				'task_id' => $task_id,
			)
		);
	}	
	
	function setEvent(Model_DevblocksEvent $event_model=null) {
		$labels = array();
		$values = array();

		/**
		 * Task
		 */
		
		@$task_id = $event_model->params['task_id']; 
		$task_labels = array();
		$task_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_TASK, $task_id, $task_labels, $task_values, null, true);

			// Merge
			CerberusContexts::merge(
				'task_',
				'',
				$task_labels,
				$task_values,
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
			'task_id' => array(
				'label' => 'Task',
				'context' => CerberusContexts::CONTEXT_TASK,
			),
			'task_watchers' => array(
				'label' => 'Task watchers',
				'context' => CerberusContexts::CONTEXT_WORKER,
			),
		);
		
		$vars = parent::getValuesContexts($trigger);
		
		$vals_to_ctx = array_merge($vals, $vars);
		asort($vals_to_ctx);
		
		return $vals_to_ctx;
	}
	
	function getConditionExtensions() {
		$labels = $this->getLabels();
		
		$labels['task_link'] = 'Task is linked';
		$labels['task_watcher_count'] = 'Task watcher count';
		
		$types = array(
			'task_is_completed' => Model_CustomField::TYPE_CHECKBOX,
			'task_completed|date' => Model_CustomField::TYPE_DATE,
			'task_due|date' => Model_CustomField::TYPE_DATE,
			'task_updated|date' => Model_CustomField::TYPE_DATE,
			'task_status' => Model_CustomField::TYPE_SINGLE_LINE,
			'task_title' => Model_CustomField::TYPE_SINGLE_LINE,
			
			'task_link' => null,
			'task_watcher_count' => null,
		);

		$conditions = $this->_importLabelsTypesAsConditions($labels, $types);
		
		return $conditions;		
	}
	
	function renderConditionExtension($token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','condition'.$seq);
		
		switch($token) {
			case 'task_link':
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				$tpl->display('devblocks:cerberusweb.core::events/condition_link.tpl');
				break;
				
			case 'task_watcher_count':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_number.tpl');
				break;
		}

		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('params');
	}
	
	function runConditionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$pass = true;
		
		switch($token) {
			case 'task_link':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				
				$from_context = null;
				$from_context_id = null;
				
				switch($token) {
					case 'task_link':
						$from_context = CerberusContexts::CONTEXT_TASK;
						@$from_context_id = $dict->task_id;
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

			case 'task_watcher_count':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				$value = count($dict->task_watchers);
				
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
	
	function getActionExtensions() {
		$actions = 
			array(
				'add_watchers' => array('label' =>'Add watchers'),
				'create_comment' => array('label' =>'Create a comment'),
				'create_notification' => array('label' =>'Create a notification'),
				'create_task' => array('label' =>'Create a task'),
				'create_ticket' => array('label' =>'Create a ticket'),
				'schedule_behavior' => array('label' => 'Schedule behavior'),
				'send_email' => array('label' => 'Send email'),
				'set_due_date' => array('label' => 'Set due date'),
				'set_status' => array('label' => 'Set status'),
				'set_task_links' => array('label' => 'Set links on task'),
				'unschedule_behavior' => array('label' => 'Unschedule behavior'),
			)
			+ DevblocksEventHelper::getActionCustomFields(CerberusContexts::CONTEXT_TASK)
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
				
			case 'schedule_behavior':
				$dates = array();
				$conditions = $this->getConditions($trigger);
				foreach($conditions as $key => $data) {
					if($data['type'] == Model_CustomField::TYPE_DATE)
					$dates[$key] = $data['label'];
				}
				$tpl->assign('dates', $dates);
			
				DevblocksEventHelper::renderActionScheduleBehavior($trigger);
				break;
				
			case 'send_email':
				DevblocksEventHelper::renderActionSendEmail($trigger);
				break;
				
			case 'unschedule_behavior':
				DevblocksEventHelper::renderActionUnscheduleBehavior($trigger);
				break;
				
			case 'set_due_date':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_date.tpl');
				break;
				
			case 'set_status':
				$tpl->display('devblocks:cerberusweb.core::events/model/task/action_set_status.tpl');
				break;
				
			case 'set_task_links':
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				$tpl->display('devblocks:cerberusweb.core::events/action_set_links.tpl');
				break;
				
			default:
				if('set_cf_' == substr($token,0,7)) {
					$field_id = substr($token,7);
					$custom_field = DAO_CustomField::get($field_id);
					DevblocksEventHelper::renderActionSetCustomField($custom_field);
				}
				break;
		}
		
		$tpl->clearAssign('params');
		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('token_labels');		
	}
	
	function simulateActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$task_id = $dict->task_id;

		if(empty($task_id))
			return;

		switch($token) {
			case 'add_watchers':
				return DevblocksEventHelper::simulateActionAddWatchers($params, $dict, 'task_id');
				break;
			
			case 'create_comment':
				return DevblocksEventHelper::simulateActionCreateComment($params, $dict, 'task_id');
				break;
				
			case 'create_notification':
				return DevblocksEventHelper::simulateActionCreateNotification($params, $dict, 'task_id');
				break;
				
			case 'create_task':
				return DevblocksEventHelper::simulateActionCreateTask($params, $dict, 'task_id');
				break;

			case 'create_ticket':
				return DevblocksEventHelper::simulateActionCreateTicket($params, $dict, 'task_id');
				break;
				
			case 'schedule_behavior':
				return DevblocksEventHelper::simulateActionScheduleBehavior($params, $dict);
				break;
				
			case 'send_email':
				return DevblocksEventHelper::simulateActionSendEmail($params, $dict);
				break;
				
			case 'unschedule_behavior':
				return DevblocksEventHelper::simulateActionUnscheduleBehavior($params, $dict);
				break;

			default:
				if('set_cf_' == substr($token,0,7)) {
					$field_id = substr($token,7);
					$custom_field = DAO_CustomField::get($field_id);
					$context = null;
					$context_id = null;
					
					// If different types of custom fields, need to find the proper context_id
					switch($custom_field->context) {
						case CerberusContexts::CONTEXT_TASK:
							$context = $custom_field->context;
							$context_id = $task_id;
							break;
					}
					
					if(!empty($context) && !empty($context_id))
						return DevblocksEventHelper::simulateActionSetCustomField($custom_field, 'task_custom', $params, $dict, $context, $context_id);
				}
				break;				
		}
	}
	
	function runActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$task_id = $dict->task_id;

		if(empty($task_id))
			return;
		
		switch($token) {
			case 'add_watchers':
				DevblocksEventHelper::runActionAddWatchers($params, $dict, 'task_id');
				break;
			
			case 'create_comment':
				DevblocksEventHelper::runActionCreateComment($params, $dict, 'task_id');
				break;
				
			case 'create_notification':
				DevblocksEventHelper::runActionCreateNotification($params, $dict, 'task_id');
				break;
				
			case 'create_task':
				DevblocksEventHelper::runActionCreateTask($params, $dict, 'task_id');
				break;

			case 'create_ticket':
				DevblocksEventHelper::runActionCreateTicket($params, $dict, 'task_id');
				break;
				
			case 'schedule_behavior':
				DevblocksEventHelper::runActionScheduleBehavior($params, $dict);
				break;
				
			case 'send_email':
				DevblocksEventHelper::runActionSendEmail($params, $dict);
				break;
				
			case 'unschedule_behavior':
				DevblocksEventHelper::runActionUnscheduleBehavior($params, $dict);
				break;
				
			case 'set_due_date':
				@$to_date = intval(strtotime($params['value']));
				
				DAO_Task::update($task_id, array(
					DAO_Task::DUE_DATE => $to_date,
				));
				
				$dict->task_due = $to_date;
				break;
				
			case 'set_status':
				@$to_status = $params['status'];
				@$current_status = $dict->task_status;
				
				if($to_status == $current_status)
					break;
				
				$fields = array();
					
				switch($to_status) {
					case 'active':
						$fields = array(
							DAO_Task::IS_COMPLETED => 0,
							DAO_Task::COMPLETED_DATE => 0,
						);
						break;
					case 'completed':
						$fields = array(
							DAO_Task::IS_COMPLETED => 1,
							DAO_Task::COMPLETED_DATE => time(),
						);
						break;
				}
				
				if(!empty($fields)) {
					$dict->task_status = $to_status;
					DAO_Task::update($task_id, $fields);
				}
				break;
				
			case 'set_task_links':
				@$to_context_strings = $params['context_objects'];

				if(!is_array($to_context_strings) || empty($to_context_strings))
					break;

				$from_context = null;
				$from_context_id = null;
				
				switch($token) {
					case 'set_task_links':
						$from_context = CerberusContexts::CONTEXT_TASK;
						@$from_context_id = $dict->task_id;
						break;
				}
				
				if(empty($from_context) || empty($from_context_id))
					break;
				
				foreach($to_context_strings as $to_context_string) {
					@list($to_context, $to_context_id) = explode(':', $to_context_string);
					
					if(empty($to_context) || empty($to_context_id))
						continue;
					
					DAO_ContextLink::setLink($from_context, $from_context_id, $to_context, $to_context_id);
				}				
				break;
				
			default:
				if('set_cf_' == substr($token,0,7)) {
					$field_id = substr($token,7);
					$custom_field = DAO_CustomField::get($field_id);
					$context = null;
					$context_id = null;
					
					// If different types of custom fields, need to find the proper context_id
					switch($custom_field->context) {
						case CerberusContexts::CONTEXT_TASK:
							$context = $custom_field->context;
							$context_id = $task_id;
							break;
					}
					
					if(!empty($context) && !empty($context_id))
						DevblocksEventHelper::runActionSetCustomField($custom_field, 'task_custom', $params, $dict, $context, $context_id);
				}
				break;	
		}
	}
	
};