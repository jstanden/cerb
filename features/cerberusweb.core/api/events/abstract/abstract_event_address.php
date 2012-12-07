<?php
/***********************************************************************
| Cerb(tm) developed by WebGroup Media, LLC.
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
				'label' => 'Email',
				'context' => CerberusContexts::CONTEXT_ADDRESS,
			),
			'email_org_id' => array(
				'label' => 'Org',
				'context' => CerberusContexts::CONTEXT_ORG,
			),
			'email_org_watchers' => array(
				'label' => 'Org watchers',
				'context' => CerberusContexts::CONTEXT_WORKER,
			),
			'email_watchers' => array(
				'label' => 'Email watchers',
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
		
		$labels['email_link'] = 'Email is linked';
		$labels['email_org_link'] = 'Org is linked';
		$labels['email_org_watcher_count'] = 'Org watcher count';
		$labels['email_watcher_count'] = 'Email watcher count';
		
		$types = array(
			'email_address' => Model_CustomField::TYPE_SINGLE_LINE,
			'email_is_banned' => Model_CustomField::TYPE_CHECKBOX,
			'email_is_defunct' => Model_CustomField::TYPE_CHECKBOX,
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
			
			'email_org_watcher_count' => null,
			'email_watcher_count' => null,
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
				
			case 'email_org_watcher_count':
			case 'email_watcher_count':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_number.tpl');
				break;
		}

		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('params');
	}
	
	function runConditionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
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

				switch($token) {
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
				'set_is_banned' => array('label' => 'Set is banned'),
				'set_is_defunct' => array('label' => 'Set is defunct'),
				'set_links' => array('label' => 'Set links'),
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
					if(isset($data['type']) && $data['type'] == Model_CustomField::TYPE_DATE)
						$dates[$key] = $data['label'];
				}
				$tpl->assign('dates', $dates);
			
				DevblocksEventHelper::renderActionScheduleBehavior($trigger);
				break;

			case 'send_email':
				DevblocksEventHelper::renderActionSendEmail($trigger);
				break;

			case 'set_is_banned':
			case 'set_is_defunct':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_bool.tpl');
				break;
				
			case 'set_links':
				DevblocksEventHelper::renderActionSetLinks($trigger);
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
	
	function simulateActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$address_id = $dict->email_id;

		if(empty($address_id))
			return;
		
		switch($token) {
			case 'add_watchers':
				return DevblocksEventHelper::simulateActionAddWatchers($params, $dict, 'email_id');
				break;
			case 'create_comment':
				return DevblocksEventHelper::simulateActionCreateComment($params, $dict, 'email_id');
				break;
			case 'create_notification':
				return DevblocksEventHelper::simulateActionCreateNotification($params, $dict, 'email_id');
				break;
			case 'create_task':
				return DevblocksEventHelper::simulateActionCreateTask($params, $dict, 'email_id');
				break;
			case 'create_ticket':
				return DevblocksEventHelper::simulateActionCreateTicket($params, $dict);
				break;
			case 'schedule_behavior':
				return DevblocksEventHelper::simulateActionScheduleBehavior($params, $dict);
				break;
			case 'send_email':
				return DevblocksEventHelper::simulateActionSendEmail($params, $dict);
				break;
			case 'set_is_banned':
				return DevblocksEventHelper::simulateActionSetAbstractField('is banned', Model_CustomField::TYPE_CHECKBOX, 'email_is_banned', $params, $dict);
				break;
			case 'set_is_defunct':
				return DevblocksEventHelper::simulateActionSetAbstractField('is defunct', Model_CustomField::TYPE_CHECKBOX, 'email_is_defunct', $params, $dict);
				break;
			case 'set_links':
				return DevblocksEventHelper::simulateActionSetLinks($trigger, $params, $dict);
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
						case CerberusContexts::CONTEXT_ADDRESS:
							$context = $custom_field->context;
							$context_id = $address_id;
							break;
					}
					
					if(!empty($context) && !empty($context_id))
						return DevblocksEventHelper::simulateActionSetCustomField($custom_field, 'email_custom', $params, $dict, $context, $context_id);
				}
				break;
		}
	}
	
	function runActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$address_id = $dict->email_id;

		if(empty($address_id))
			return;
		
		switch($token) {
			case 'add_watchers':
				DevblocksEventHelper::runActionAddWatchers($params, $dict, 'email_id');
				break;
			
			case 'create_comment':
				DevblocksEventHelper::runActionCreateComment($params, $dict, 'email_id');
				break;
				
			case 'create_notification':
				DevblocksEventHelper::runActionCreateNotification($params, $dict, 'email_id');
				break;
				
			case 'create_task':
				DevblocksEventHelper::runActionCreateTask($params, $dict, 'email_id');
				break;

			case 'create_ticket':
				DevblocksEventHelper::runActionCreateTicket($params, $dict);
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
				
			case 'set_is_banned':
				@$value = $params['value'];
				
				DAO_Address::update($address_id, array(
					DAO_Address::IS_BANNED => $value,
				));
				$dict->is_banned = $value;
				break;
				
			case 'set_is_defunct':
				@$value = $params['value'];
				
				DAO_Address::update($address_id, array(
					DAO_Address::IS_DEFUNCT => $value,
				));
				$dict->is_defunct = $value;
				break;
				
			case 'set_links':
				DevblocksEventHelper::runActionSetLinks($trigger, $params, $dict);
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
						DevblocksEventHelper::runActionSetCustomField($custom_field, 'email_custom', $params, $dict, $context, $context_id);
				}
				break;
		}
	}
	
};