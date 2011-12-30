<?php
abstract class AbstractEvent_TimeTracking extends Extension_DevblocksEvent {
	protected $_event_id = null; // override

	/**
	 * 
	 * @param integer $time_id
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel($time_id=null) {
		
		if(empty($time_id)) {
			// Pull the latest record
			list($results) = DAO_TimeTrackingEntry::search(
				array(),
				array(
					//new DevblocksSearchCriteria(SearchFields_TimeTrackingEntry::IS_CLOSED,'=',0),
				),
				10,
				0,
				SearchFields_TimeTrackingEntry::ID,
				false,
				false
			);
			
			shuffle($results);
			
			$result = array_shift($results);
			
			$time_id = $result[SearchFields_TimeTrackingEntry::ID];
		}
		
		return new Model_DevblocksEvent(
			$this->_event_id,
			array(
				'time_id' => $time_id,
			)
		);
	}	
	
	function setEvent(Model_DevblocksEvent $event_model=null) {
		$labels = array();
		$values = array();

		/**
		 * Time Tracking Entry
		 */
		
		@$time_id = $event_model->params['time_id']; 
		$merge_labels = array();
		$merge_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_TIMETRACKING, $time_id, $merge_labels, $merge_values, null, true);

			// Merge
			CerberusContexts::merge(
				'time_',
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
	
	function getConditionExtensions() {
		$labels = $this->getLabels();
		
		$labels['time_link'] = 'Time entry is linked';
		
		$types = array(
			'time_log_date' => Model_CustomField::TYPE_DATE,
			'time_mins' => Model_CustomField::TYPE_NUMBER,
			'time_summary' => Model_CustomField::TYPE_SINGLE_LINE,
			
			'time_link' => null,
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
			case 'time_link':
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				$tpl->display('devblocks:cerberusweb.core::events/condition_link.tpl');
				break;
		}

		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('params');
	}
	
	function runConditionExtension($token, $trigger, $params, $values) {
		$pass = true;
		
		switch($token) {
			case 'time_link':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				
				$from_context = null;
				$from_context_id = null;
				
				switch($token) {
					case 'time_link':
						$from_context = CerberusContexts::CONTEXT_TIMETRACKING;
						@$from_context_id = $values['time_id'];
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
	
	function getActionExtensions() {
		$actions = 
			array(
				'add_watchers' => array('label' =>'Add watchers'),
				'create_comment' => array('label' =>'Create a comment'),
				'create_notification' => array('label' =>'Create a notification'),
				'create_task' => array('label' =>'Create a task'),
				'create_ticket' => array('label' =>'Create a ticket'),
				'schedule_behavior' => array('label' => 'Schedule behavior'),
				'set_time_links' => array('label' => 'Set links on time tracking entry'),
				'unschedule_behavior' => array('label' => 'Unschedule behavior'),
			)
			+ DevblocksEventHelper::getActionCustomFields(CerberusContexts::CONTEXT_TIMETRACKING)
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
				$conditions = $this->getConditions($trigger);
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
				
			case 'set_time_links':
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
	
	function runActionExtension($token, $trigger, $params, &$values) {
		@$time_id = $values['time_id'];

		if(empty($time_id))
			return;
		
		switch($token) {
			case 'add_watchers':
				DevblocksEventHelper::runActionAddWatchers($params, $values, CerberusContexts::CONTEXT_TIMETRACKING, $time_id);
				break;
			
			case 'create_comment':
				DevblocksEventHelper::runActionCreateComment($params, $values, CerberusContexts::CONTEXT_TIMETRACKING, $time_id);
				break;
				
			case 'create_notification':
				DevblocksEventHelper::runActionCreateNotification($params, $values, CerberusContexts::CONTEXT_TIMETRACKING, $time_id);
				break;
				
			case 'create_task':
				DevblocksEventHelper::runActionCreateTask($params, $values, CerberusContexts::CONTEXT_TIMETRACKING, $time_id);
				break;

			case 'create_ticket':
				DevblocksEventHelper::runActionCreateTicket($params, $values, CerberusContexts::CONTEXT_TIMETRACKING, $time_id);
				break;
				
			case 'schedule_behavior':
				DevblocksEventHelper::runActionScheduleBehavior($params, $values, CerberusContexts::CONTEXT_TIMETRACKING, $time_id);
				break;
				
			case 'unschedule_behavior':
				DevblocksEventHelper::runActionUnscheduleBehavior($params, $values, CerberusContexts::CONTEXT_TIMETRACKING, $time_id);
				break;
				
			case 'set_time_links':
				@$to_context_strings = $params['context_objects'];

				if(!is_array($to_context_strings) || empty($to_context_strings))
					break;

				$from_context = null;
				$from_context_id = null;
				
				switch($token) {
					case 'set_time_links':
						$from_context = CerberusContexts::CONTEXT_TIMETRACKING;
						@$from_context_id = $values['time_id'];
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
						case CerberusContexts::CONTEXT_TIMETRACKING:
							$context = $custom_field->context;
							$context_id = $time_id;
							break;
					}
					
					if(!empty($context) && !empty($context_id))
						DevblocksEventHelper::runActionSetCustomField($custom_field, 'time_custom', $params, $values, $context, $context_id);
				}
				break;	
		}
	}
	
};