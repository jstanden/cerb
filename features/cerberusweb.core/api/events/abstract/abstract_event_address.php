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

abstract class AbstractEvent_Address extends Extension_DevblocksEvent {
	protected $_event_id = null; // override

	/**
	 * 
	 * @param integer $address_id
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel($address_id=null) {
		
		if(empty($address_id)) {
			// Pull the latest record
			list($results) = DAO_Address::search(
				array(),
				array(
					//new DevblocksSearchCriteria(SearchFields_Task::IS_CLOSED,'=',0),
				),
				10,
				0,
				SearchFields_Address::ID,
				false,
				false
			);
			
			shuffle($results);
			
			$result = array_shift($results);
			
			$address_id = $result[SearchFields_Address::ID];
		}
		
		return new Model_DevblocksEvent(
			$this->_event_id,
			array(
				'address_id' => $address_id,
			)
		);
	}	
	
	function setEvent(Model_DevblocksEvent $event_model=null) {
		$labels = array();
		$values = array();

		/**
		 * Task
		 */
		
		@$address_id = $event_model->params['address_id']; 
		$merge_labels = array();
		$merge_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_ADDRESS, $address_id, $merge_labels, $merge_values, null, true);

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
	
	function getValuesContexts($trigger) {
		$vals = array(
			'email_id' => array(
				'label' => 'Email address',
				'context' => CerberusContexts::CONTEXT_ADDRESS,
			),
			'email_org_id' => array(
				'label' => 'Organization',
				'context' => CerberusContexts::CONTEXT_ORG,
			),
		);
		
		$vars = parent::getValuesContexts($trigger);
		
		return array_merge($vals, $vars);
	}
	
	function getConditionExtensions() {
		$labels = $this->getLabels();
		
		$labels['email_link'] = 'Email is linked';
		$labels['email_org_link'] = 'Org is linked';
		
		$types = array(
			'email_address' => Model_CustomField::TYPE_SINGLE_LINE,
			'email_is_banned' => Model_CustomField::TYPE_CHECKBOX,
			'email_first_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'email_full_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'email_last_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'email_num_nonspam' => Model_CustomField::TYPE_NUMBER,
			'email_num_spam' => Model_CustomField::TYPE_NUMBER,
			
			'email_org_city' => Model_CustomField::TYPE_SINGLE_LINE,
			'email_org_country' => Model_CustomField::TYPE_SINGLE_LINE,
			'email_org_created|date' => Model_CustomField::TYPE_DATE,
			'email_org_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'email_org_phone' => Model_CustomField::TYPE_SINGLE_LINE,
			'email_org_postal' => Model_CustomField::TYPE_SINGLE_LINE,
			'email_org_province' => Model_CustomField::TYPE_SINGLE_LINE,
			'email_org_street' => Model_CustomField::TYPE_SINGLE_LINE,
			'email_org_website' => Model_CustomField::TYPE_URL,
			
			'email_link' => null,
			'email_org_link' => null,
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
			case 'email_link':
			case 'email_org_link':
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
			case 'email_link':
			case 'email_org_link':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				
				$from_context = null;
				$from_context_id = null;

				switch($token) {
					case 'email_link':
						$from_context = CerberusContexts::CONTEXT_ADDRESS;
						@$from_context_id = $values['email_id'];
						break;
					case 'email_org_link':
						$from_context = CerberusContexts::CONTEXT_ORG;
						@$from_context_id = $values['email_org_id'];
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
				'set_email_links' => array('label' => 'Set links on email'),
				'set_email_org_links' => array('label' => 'Set links on organization'),
				'unschedule_behavior' => array('label' => 'Unschedule behavior'),
			)
			+ DevblocksEventHelper::getActionCustomFields(CerberusContexts::CONTEXT_ADDRESS)
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

			case 'set_email_links':
			case 'set_email_org_links':
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				$tpl->display('devblocks:cerberusweb.core::events/action_set_links.tpl');
				break;
				
			case 'unschedule_behavior':
				DevblocksEventHelper::renderActionUnscheduleBehavior($trigger);
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
	
	function simulateActionExtension($token, $trigger, $params, &$values) {
		@$address_id = $values['email_id'];

		if(empty($address_id))
			return;
		
		switch($token) {
			case 'add_watchers':
				return DevblocksEventHelper::simulateActionAddWatchers($params, $values, 'email_id');
				break;
			case 'create_comment':
				return DevblocksEventHelper::simulateActionCreateComment($params, $values, 'email_id');
				break;
			case 'create_notification':
				return DevblocksEventHelper::simulateActionCreateNotification($params, $values, 'email_id');
				break;
			case 'create_task':
				return DevblocksEventHelper::simulateActionCreateTask($params, $values, 'email_id');
				break;
			case 'create_ticket':
				return DevblocksEventHelper::simulateActionCreateTicket($params, $values);
				break;
			case 'schedule_behavior':
				return DevblocksEventHelper::simulateActionScheduleBehavior($params, $values);
				break;
			case 'send_email':
				return DevblocksEventHelper::simulateActionSendEmail($params, $values);
				break;
			case 'unschedule_behavior':
				return DevblocksEventHelper::simulateActionUnscheduleBehavior($params, $values);
				break;
			default:
				if('set_cf_' == substr($token,0,7)) {
					$field_id = substr($token,7);
					$custom_field = DAO_CustomField::get($field_id);
					$context = null;
					$context_id = null;
					
					// If different types of custom fields, need to find the proper context_id
					switch($custom_field->context) {
						case CerberusContexts::CONTEXT_ADDRESS:
							$context = $custom_field->context;
							$context_id = $address_id;
							break;
					}
					
					if(!empty($context) && !empty($context_id))
						return DevblocksEventHelper::simulateActionSetCustomField($custom_field, 'email_custom', $params, $values, $context, $context_id);
				}
				break;
		}
	}
	
	function runActionExtension($token, $trigger, $params, &$values) {
		@$address_id = $values['email_id'];

		if(empty($address_id))
			return;
		
		switch($token) {
			case 'add_watchers':
				DevblocksEventHelper::runActionAddWatchers($params, $values, 'email_id');
				break;
			
			case 'create_comment':
				DevblocksEventHelper::runActionCreateComment($params, $values, 'email_id');
				break;
				
			case 'create_notification':
				DevblocksEventHelper::runActionCreateNotification($params, $values, 'email_id');
				break;
				
			case 'create_task':
				DevblocksEventHelper::runActionCreateTask($params, $values, 'email_id');
				break;

			case 'create_ticket':
				DevblocksEventHelper::runActionCreateTicket($params, $values);
				break;
				
			case 'schedule_behavior':
				DevblocksEventHelper::runActionScheduleBehavior($params, $values);
				break;

			case 'send_email':
				DevblocksEventHelper::runActionSendEmail($params, $values);
				break;
				
			case 'unschedule_behavior':
				DevblocksEventHelper::runActionUnscheduleBehavior($params, $values);
				break;
				
			case 'set_email_links':
			case 'set_email_org_links':
				@$to_context_strings = $params['context_objects'];

				if(!is_array($to_context_strings) || empty($to_context_strings))
					break;

				$from_context = null;
				$from_context_id = null;
				
				switch($token) {
					case 'set_email_links':
						$from_context = CerberusContexts::CONTEXT_ADDRESS;
						@$from_context_id = $values['email_id'];
						break;
					case 'set_email_org_links':
						$from_context = CerberusContexts::CONTEXT_ORG;
						@$from_context_id = $values['email_org_id'];
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
						case CerberusContexts::CONTEXT_ADDRESS:
							$context = $custom_field->context;
							$context_id = $address_id;
							break;
					}
					
					if(!empty($context) && !empty($context_id))
						DevblocksEventHelper::runActionSetCustomField($custom_field, 'email_custom', $params, $values, $context, $context_id);
				}
				break;	
		}
	}
	
};