<?php
abstract class AbstractEvent_Call extends Extension_DevblocksEvent {
	protected $_event_id = null; // override

	/**
	 * 
	 * @param integer $call_id
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel($call_id=null) {
		
		if(empty($call_id)) {
			// Pull the latest record
			list($results) = DAO_CallEntry::search(
				array(),
				array(
					//new DevblocksSearchCriteria(SearchFields_Task::IS_CLOSED,'=',0),
				),
				10,
				0,
				SearchFields_CallEntry::ID,
				false,
				false
			);
			
			shuffle($results);
			
			$result = array_shift($results);
			
			$call_id = $result[SearchFields_CallEntry::ID];
		}
		
		return new Model_DevblocksEvent(
			$this->_event_id,
			array(
				'call_id' => $call_id,
			)
		);
	}	
	
	function setEvent(Model_DevblocksEvent $event_model=null) {
		$labels = array();
		$values = array();

		/**
		 * Call
		 */
		
		@$call_id = $event_model->params['call_id']; 
		$call_labels = array();
		$call_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_CALL, $call_id, $call_labels, $call_values, null, true);

			// Merge
			CerberusContexts::merge(
				'call_',
				'',
				$call_labels,
				$call_values,
				$labels,
				$values
			);

		/**
		 * Return
		 */

		$this->setLabels($labels);
		$this->setValues($values);		
	}
	
	function getConditionExtensions() {
		$labels = $this->getLabels();
		
		$types = array(
			'call_created|date' => Model_CustomField::TYPE_DATE,
			'call_is_closed' => Model_CustomField::TYPE_CHECKBOX,
			'call_is_outgoing' => Model_CustomField::TYPE_CHECKBOX,
			'call_phone' => Model_CustomField::TYPE_SINGLE_LINE,
			'call_subject' => Model_CustomField::TYPE_SINGLE_LINE,
			'call_updated|date' => Model_CustomField::TYPE_DATE,
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
		}

		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('params');
	}
	
	function runConditionExtension($token, $trigger, $params, $values) {
		$pass = true;
		
		switch($token) {
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
				'unschedule_behavior' => array('label' => 'Unschedule behavior'),
			)
			+ DevblocksEventHelper::getActionCustomFields(CerberusContexts::CONTEXT_CALL)
			;
			
		return $actions;
	}
	
	function renderActionExtension($token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','action'.$seq);

		$labels = $this->getLabels();
		$tpl->assign('token_labels', $labels);
			
		switch($token) {
			case 'add_watchers':
				DevblocksEventHelper::renderActionAddWatchers();
				break;
			
			case 'create_comment':
				DevblocksEventHelper::renderActionCreateComment();
				break;
				
			case 'create_notification':
				DevblocksEventHelper::renderActionCreateNotification();
				break;
				
			case 'create_task':
				DevblocksEventHelper::renderActionCreateTask();
				break;
				
			case 'create_ticket':
				DevblocksEventHelper::renderActionCreateTicket();
				break;
				
			case 'schedule_behavior':
				$dates = array();
				$conditions = $this->getConditions();
				foreach($conditions as $key => $data) {
					if($data['type'] == Model_CustomField::TYPE_DATE)
					$dates[$key] = $data['label'];
				}
				$tpl->assign('dates', $dates);
			
				DevblocksEventHelper::renderActionScheduleBehavior($trigger->owner_context, $trigger->owner_context_id, $this->_event_id);
				break;
				
			case 'unschedule_behavior':
				DevblocksEventHelper::renderActionUnscheduleBehavior($trigger->owner_context, $trigger->owner_context_id, $this->_event_id);
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
	
	function runActionExtension($token, $trigger, $params, &$values) {
		@$call_id = $values['call_id'];

		if(empty($call_id))
			return;
		
		switch($token) {
			case 'add_watchers':
				DevblocksEventHelper::runActionAddWatchers($params, $values, CerberusContexts::CONTEXT_CALL, $call_id);
				break;
			
			case 'create_comment':
				DevblocksEventHelper::runActionCreateComment($params, $values, CerberusContexts::CONTEXT_CALL, $call_id);
				break;
				
			case 'create_notification':
				DevblocksEventHelper::runActionCreateNotification($params, $values, CerberusContexts::CONTEXT_CALL, $call_id);
				break;
				
			case 'create_task':
				DevblocksEventHelper::runActionCreateTask($params, $values, CerberusContexts::CONTEXT_CALL, $call_id);
				break;

			case 'create_ticket':
				DevblocksEventHelper::runActionCreateTicket($params, $values, CerberusContexts::CONTEXT_CALL, $call_id);
				break;
				
			case 'schedule_behavior':
				DevblocksEventHelper::runActionScheduleBehavior($params, $values, CerberusContexts::CONTEXT_CALL, $call_id);
				break;
				
			case 'unschedule_behavior':
				DevblocksEventHelper::runActionUnscheduleBehavior($params, $values, CerberusContexts::CONTEXT_CALL, $call_id);
				break;
				
			default:
				if('set_cf_' == substr($token,0,7)) {
					$field_id = substr($token,7);
					$custom_field = DAO_CustomField::get($field_id);
					$context = null;
					$context_id = null;
					
					// If different types of custom fields, need to find the proper context_id
					switch($custom_field->context) {
						case CerberusContexts::CONTEXT_CALL:
							$context = $custom_field->context;
							$context_id = $call_id;
							break;
					}
					
					if(!empty($context) && !empty($context_id))
						DevblocksEventHelper::runActionSetCustomField($custom_field, 'call_custom', $params, $values, $context, $context_id);
				}
				break;	
		}
	}
	
};