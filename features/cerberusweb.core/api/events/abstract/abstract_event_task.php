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

abstract class AbstractEvent_Task extends Extension_DevblocksEvent {
	protected $_event_id = null; // override

	/**
	 *
	 * @param integer $context_id
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel(Model_TriggerEvent $trigger, $context_id=null) {
		
		if(empty($context_id)) {
			$context_id = DAO_Task::random();
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
		 * Task
		 */
		
		$task_labels = array();
		$task_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_TASK, $model, $task_labels, $task_values, null, true);

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
	
	function renderSimulatorTarget($trigger, $event_model) {
		$context = CerberusContexts::CONTEXT_TASK;
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
			'task_id' => array(
				'label' => 'Task',
				'context' => CerberusContexts::CONTEXT_TASK,
			),
			'task_watchers' => array(
				'label' => 'Task watchers',
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
		
		$labels['task_link'] = 'Task is linked';
		$labels['task_watcher_count'] = 'Task watcher count';

		$types['task_link'] = null;
		$types['task_watcher_count'] = null;
		
		$conditions = $this->_importLabelsTypesAsConditions($labels, $types);
		
		return $conditions;
	}
	
	function renderConditionExtension($token, $as_token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','condition'.$seq);
		
		switch($as_token) {
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
	
	function runConditionExtension($token, $as_token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$pass = true;
		
		switch($as_token) {
			case 'task_link':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				
				$from_context = null;
				$from_context_id = null;
				
				switch($as_token) {
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
	
	function getActionExtensions(Model_TriggerEvent $trigger) {
		$actions =
			[
				'set_due_date' => [
					'label' => 'Set task due date',
					'deprecated' => true,
				],
				'set_importance' => [
					'label' => 'Set task importance',
					'deprecated' => true,
				],
				'set_owner' => [
					'label' => 'Set task owner',
					'deprecated' => true,
				],
				'set_reopen_date' => [
					'label' => 'Set task reopen date',
					'deprecated' => true,
				],
				'set_status' => [
					'label' => 'Set task status',
					'deprecated' => true,
				],
			]
			+ DevblocksEventHelper::getActionCustomFieldsFromLabels($this->getLabels($trigger))
			;
			
		return $actions;
	}
	
	function getActionDefaultOn() {
		return 'task_id';
	}
	
	function renderActionExtension($token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','action'.$seq);

		$labels = $this->getLabels($trigger);
		$tpl->assign('token_labels', $labels);
			
		switch($token) {
			case 'set_due_date':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_date.tpl');
				break;
				
			case 'set_importance':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_number.tpl');
				break;
				
			case 'set_owner':
				$worker_values = DevblocksEventHelper::getWorkerValues($trigger);
				$tpl->assign('worker_values', $worker_values);
				
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_worker.tpl');
				break;
				
			case 'set_reopen_date':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_date.tpl');
				break;
				
			case 'set_status':
				$tpl->display('devblocks:cerberusweb.core::events/model/task/action_set_status.tpl');
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
		@$task_id = $dict->task_id;

		if(empty($task_id))
			return;

		switch($token) {
			case 'set_due_date':
				DevblocksEventHelper::runActionSetDate('task_due', $params, $dict);
				$out = sprintf(">>> Setting task due date to:\n".
					"%s (%d)\n",
					date('D M d Y h:ia', $dict->task_due),
					$dict->task_due
				);
				return $out;
				break;
			
			case 'set_importance':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$value = $tpl_builder->build($params['value'], $dict);
				$value = DevblocksPlatform::intClamp($value, 0, 100);
				
				$dict->task_importance = $value;
				
				$out = sprintf(">>> Setting task importance to: %d\n",
					$dict->task_importance
				);
				return $out;
				break;
			
			case 'set_owner':
				@$owner_id = $params['worker_id'];
				
				if(empty($task_id))
					return false;
				
				$out = ">>> Setting owner to:\n";
		
				// Placeholder?
				if(!is_numeric($owner_id) && $dict->exists($owner_id)) {
					if(is_numeric($dict->$owner_id)) {
						@$owner_id = $dict->$owner_id;
						
					} elseif (is_array($dict->$owner_id)) {
						@$owner_id = key($dict->$owner_id);
					}
				}
				
				$owner_id = intval($owner_id);
				
				if(empty($owner_id)) {
					$out .= "(nobody)\n";
					
				} else {
					if(null != ($owner_model = DAO_Worker::get($owner_id))) {
						$out .= $owner_model->getName() . "\n";
					}
				}
				
				$dict->scrubKeys('task_owner_');
				$dict->task_owner__context = CerberusContexts::CONTEXT_WORKER;
				$dict->task_owner_id = $owner_id;
				return $out;
				break;
				
				
			case 'set_reopen_date':
				DevblocksEventHelper::runActionSetDate('task_reopen', $params, $dict);
				$out = sprintf(">>> Setting task to reopen at:\n".
					"%s (%d)\n",
					date('D M d Y h:ia', $dict->task_reopen),
					$dict->task_reopen
				);
				return $out;
				break;
				
			case 'set_status':
				@$to_status_id = DevblocksPlatform::intClamp(intval($params['status_id']), 0, 2);
				$dict->task_status_id = $to_status_id;
				
				$label_map = [
					0 => 'open',
					1 => 'closed',
					2 => 'waiting',
				];
				
				$out = sprintf(">>> Setting status to: %s\n",
					@$label_map[$dict->task_status_id]
				);
				return $out;
				break;
				
			default:
				if(preg_match('#set_cf_(.*?_*)custom_([0-9]+)#', $token))
					return DevblocksEventHelper::simulateActionSetCustomField($token, $params, $dict);
				break;
		}
	}
	
	function runActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$task_id = $dict->task_id;

		if(empty($task_id))
			return;
		
		switch($token) {
			case 'set_due_date':
				DevblocksEventHelper::runActionSetDate('task_due', $params, $dict);
				
				DAO_Task::update($task_id, array(
					DAO_Task::DUE_DATE => $dict->task_due,
				));
				break;
				
			case 'set_importance':
				$this->simulateAction($token, $trigger, $params, $dict);
				
				DAO_Task::update($task_id, array(
					DAO_Task::IMPORTANCE => $dict->task_importance,
				));
				break;
				
			case 'set_owner':
				$this->simulateAction($token, $trigger, $params, $dict);
				
				DAO_Task::update($task_id, array(
					DAO_Task::OWNER_ID => intval($dict->task_owner_id),
				));
				break;
				
			case 'set_reopen_date':
				DevblocksEventHelper::runActionSetDate('task_reopen', $params, $dict);
				
				DAO_Task::update($task_id, array(
					DAO_Task::REOPEN_AT => intval($dict->task_reopen),
				));
				break;
				
			case 'set_status':
				$this->simulateAction($token, $trigger, $params, $dict);
				
				$fields = array();
					
				switch($dict->task_status_id) {
					case 0:
						$fields = array(
							DAO_Task::STATUS_ID => 0,
							DAO_Task::COMPLETED_DATE => 0,
						);
						break;
					case 1:
						$fields = array(
							DAO_Task::STATUS_ID => 1,
							DAO_Task::COMPLETED_DATE => time(),
						);
						break;
					case 2:
						$fields = array(
							DAO_Task::STATUS_ID => 2,
							DAO_Task::COMPLETED_DATE => 0,
						);
						break;
				}
				
				if(!empty($fields)) {
					DAO_Task::update($task_id, $fields);
				}
				break;
				
			default:
				if(preg_match('#set_cf_(.*?_*)custom_([0-9]+)#', $token))
					return DevblocksEventHelper::runActionSetCustomField($token, $params, $dict);
				break;
		}
	}
	
};