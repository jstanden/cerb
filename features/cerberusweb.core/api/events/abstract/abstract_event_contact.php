<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2013, Webgroup Media LLC
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

abstract class AbstractEvent_ContactPerson extends Extension_DevblocksEvent {
	protected $_event_id = null; // override

	/**
	 *
	 * @param integer $contact_id
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel($contact_id=null) {
		
		if(empty($contact_id)) {
			$contact_id = DAO_ContactPerson::random();
		}
		
		return new Model_DevblocksEvent(
			$this->_event_id,
			array(
				'contact_id' => $contact_id,
			)
		);
	}
	
	function setEvent(Model_DevblocksEvent $event_model=null) {
		$labels = array();
		$values = array();

		/**
		 * Contact Person
		 */
		
		@$contact_id = $event_model->params['contact_id'];
		$contact_labels = array();
		$contact_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_CONTACT_PERSON, $contact_id, $contact_labels, $contact_values, null, true);

			// Merge
			CerberusContexts::merge(
				'contact_',
				'',
				$contact_labels,
				$contact_values,
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
			'contact_id' => array(
				'label' => 'Contact',
				'context' => CerberusContexts::CONTEXT_CONTACT_PERSON,
			),
			'contact_email_id' => array(
				'label' => 'Email',
				'context' => CerberusContexts::CONTEXT_ADDRESS,
			),
			'contact_email_org_id' => array(
				'label' => 'Org',
				'context' => CerberusContexts::CONTEXT_ORG,
			),
			'contact_watchers' => array(
				'label' => 'Contact watchers',
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
		
		$labels['contact_link'] = 'Contact is linked';
		$labels['contact_watcher_count'] = 'Contact watcher count';
		
		$types = array(
			'contact_created|date' => Model_CustomField::TYPE_DATE,
			'contact_last_login|date' => Model_CustomField::TYPE_DATE,
				
			'contact_email_address' => Model_CustomField::TYPE_SINGLE_LINE,
			'contact_email_first_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'contact_email_full_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'contact_email_last_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'contact_email_is_banned' => Model_CustomField::TYPE_CHECKBOX,
			'contact_email_is_defunct' => Model_CustomField::TYPE_CHECKBOX,
			'contact_email_num_nonspam' => Model_CustomField::TYPE_NUMBER,
			'contact_email_num_spam' => Model_CustomField::TYPE_NUMBER,
				
			'contact_email_org_city' => Model_CustomField::TYPE_SINGLE_LINE,
			'contact_email_org_country' => Model_CustomField::TYPE_SINGLE_LINE,
			'contact_email_org_created|date' => Model_CustomField::TYPE_DATE,
			'contact_email_org_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'contact_email_org_phone' => Model_CustomField::TYPE_SINGLE_LINE,
			'contact_email_org_postal' => Model_CustomField::TYPE_SINGLE_LINE,
			'contact_email_org_province' => Model_CustomField::TYPE_SINGLE_LINE,
			'contact_email_org_street' => Model_CustomField::TYPE_SINGLE_LINE,
			'contact_email_org_website' => Model_CustomField::TYPE_URL,
			
			'contact_link' => null,
			'contact_watcher_count' => null,
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
			case 'contact_link':
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				$tpl->display('devblocks:cerberusweb.core::events/condition_link.tpl');
				break;
				
			case 'contact_watcher_count':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_number.tpl');
				break;
		}

		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('params');
	}
	
	function runConditionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$pass = true;
		
		switch($token) {
			case 'contact_link':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				
				$from_context = null;
				$from_context_id = null;
				
				switch($token) {
					case 'contact_link':
						$from_context = CerberusContexts::CONTEXT_CONTACT_PERSON;
						@$from_context_id = $dict->contact_id;
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

			case 'contact_watcher_count':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				$value = count($dict->contact_watchers);
				
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
				'set_links' => array('label' => 'Set links'),
				'unschedule_behavior' => array('label' => 'Unschedule behavior'),
			)
			+ DevblocksEventHelper::getActionCustomFieldsFromLabels($this->getLabels())
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
				
			case 'unschedule_behavior':
				DevblocksEventHelper::renderActionUnscheduleBehavior($trigger);
				break;
				
			case 'set_links':
				DevblocksEventHelper::renderActionSetLinks($trigger);
				break;
				
			default:
				if(preg_match('#set_cf_(.*?)_custom_([0-9]+)#', $token, $matches)) {
					$field_id = $matches[2];
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
		@$contact_id = $dict->contact_id;

		if(empty($contact_id))
			return;

		switch($token) {
			case 'add_watchers':
				return DevblocksEventHelper::simulateActionAddWatchers($params, $dict, 'contact_id');
				break;
			
			case 'create_comment':
				return DevblocksEventHelper::simulateActionCreateComment($params, $dict, 'contact_id');
				break;
				
			case 'create_notification':
				return DevblocksEventHelper::simulateActionCreateNotification($params, $dict, 'contact_id');
				break;
				
			case 'create_task':
				return DevblocksEventHelper::simulateActionCreateTask($params, $dict, 'contact_id');
				break;

			case 'create_ticket':
				return DevblocksEventHelper::simulateActionCreateTicket($params, $dict, 'contact_id');
				break;
				
			case 'schedule_behavior':
				return DevblocksEventHelper::simulateActionScheduleBehavior($params, $dict);
				break;
				
			case 'send_email':
				return DevblocksEventHelper::simulateActionSendEmail($params, $dict);
				break;
			
			case 'set_links':
				return DevblocksEventHelper::simulateActionSetLinks($trigger, $params, $dict);
				break;
				
			case 'unschedule_behavior':
				return DevblocksEventHelper::simulateActionUnscheduleBehavior($params, $dict);
				break;

			default:
				if(preg_match('#set_cf_(.*?)_custom_([0-9]+)#', $token))
					return DevblocksEventHelper::simulateActionSetCustomField($token, $params, $dict);
				break;
		}
	}
	
	function runActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$contact_id = $dict->contact_id;

		if(empty($contact_id))
			return;
		
		switch($token) {
			case 'add_watchers':
				DevblocksEventHelper::runActionAddWatchers($params, $dict, 'contact_id');
				break;
			
			case 'create_comment':
				DevblocksEventHelper::runActionCreateComment($params, $dict, 'contact_id');
				break;
				
			case 'create_notification':
				DevblocksEventHelper::runActionCreateNotification($params, $dict, 'contact_id');
				break;
				
			case 'create_task':
				DevblocksEventHelper::runActionCreateTask($params, $dict, 'contact_id');
				break;

			case 'create_ticket':
				DevblocksEventHelper::runActionCreateTicket($params, $dict, 'contact_id');
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
				
			case 'set_links':
				DevblocksEventHelper::runActionSetLinks($trigger, $params, $dict);
				break;
				
			default:
				if(preg_match('#set_cf_(.*?)_custom_([0-9]+)#', $token))
					return DevblocksEventHelper::runActionSetCustomField($token, $params, $dict);
				break;
		}
	}
	
};