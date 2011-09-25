<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2011, WebGroup Media LLC
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

// [TODO] Abstract
class Event_CrmOpportunityMacro extends Extension_DevblocksEvent {
	const ID = 'event.macro.crm.opportunity';
	
	static function trigger($trigger_id, $opp_id) {
		$events = DevblocksPlatform::getEventService();
		$events->trigger(
	        new Model_DevblocksEvent(
	            self::ID,
                array(
                    'opp_id' => $opp_id,
                	'_whisper' => array(
                		'_trigger_id' => array($trigger_id),
                	),
                )
            )
		);
	}
	
	/**
	 * 
	 * @param integer $opp_id
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel($opp_id=null) {
		
		if(empty($opp_id)) {
			// Pull the latest record
			list($results) = DAO_CrmOpportunity::search(
				array(),
				array(
					new DevblocksSearchCriteria(SearchFields_CrmOpportunity::IS_CLOSED,'=',0),
				),
				10,
				0,
				SearchFields_CrmOpportunity::ID,
				false,
				false
			);
			
			shuffle($results);
			
			$result = array_shift($results);
			
			$opp_id = $result[SearchFields_CrmOpportunity::ID];
		}
		
		return new Model_DevblocksEvent(
			self::ID,
			array(
				'opp_id' => $opp_id,
			)
		);
	}	
	
	function setEvent(Model_DevblocksEvent $event_model=null) {
		$labels = array();
		$values = array();

		/**
		 * Opportunity
		 */
		
		@$opp_id = $event_model->params['opp_id']; 
		$opp_labels = array();
		$opp_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_OPPORTUNITY, $opp_id, $opp_labels, $opp_values, null, true);

			// Merge
			CerberusContexts::merge(
				'opp_',
				'',
				$opp_labels,
				$opp_values,
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
		
		$labels['opp_link'] = 'Opportunity is linked';
		$labels['opp_email_link'] = 'Lead is linked';
		$labels['opp_email_org_link'] = 'Lead org is linked';
		
		$types = array(
			'opp_email_num_nonspam' => Model_CustomField::TYPE_NUMBER,
			'opp_email_num_spam' => Model_CustomField::TYPE_NUMBER,
			'opp_email_address' => Model_CustomField::TYPE_SINGLE_LINE,
			'opp_email_first_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'opp_email_full_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'opp_email_is_banned' => Model_CustomField::TYPE_CHECKBOX,
			'opp_email_last_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'opp_email_org_city' => Model_CustomField::TYPE_SINGLE_LINE,
			'opp_email_org_country' => Model_CustomField::TYPE_SINGLE_LINE,
			'opp_email_org_created' => Model_CustomField::TYPE_DATE,
			'opp_email_org_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'opp_email_org_phone' => Model_CustomField::TYPE_SINGLE_LINE,
			'opp_email_org_postal' => Model_CustomField::TYPE_SINGLE_LINE,
			'opp_email_org_province' => Model_CustomField::TYPE_SINGLE_LINE,
			'opp_email_org_street' => Model_CustomField::TYPE_SINGLE_LINE,
			'opp_email_org_website' => Model_CustomField::TYPE_SINGLE_LINE,
			'opp_amount' => Model_CustomField::TYPE_NUMBER,
			'opp_is_closed' => Model_CustomField::TYPE_CHECKBOX,
			'opp_created|date' => Model_CustomField::TYPE_DATE,
			'opp_status' => Model_CustomField::TYPE_SINGLE_LINE,
			'opp_title' => Model_CustomField::TYPE_SINGLE_LINE,
			'opp_updated|date' => Model_CustomField::TYPE_DATE,
			'opp_is_won' => Model_CustomField::TYPE_CHECKBOX,
			
			'opp_link' => null,
			'opp_email_link' => null,
			'opp_email_org_link' => null,
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
			case 'opp_link':
			case 'opp_email_link':
			case 'opp_email_org_link':
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
			case 'opp_link':
			case 'opp_email_link':
			case 'opp_email_org_link':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				
				$from_context = null;
				$from_context_id = null;
				
				switch($token) {
					case 'opp_link':
						$from_context = CerberusContexts::CONTEXT_OPPORTUNITY;
						@$from_context_id = $values['opp_id'];
						break;
					case 'opp_email_link':
						$from_context = CerberusContexts::CONTEXT_ADDRESS;
						@$from_context_id = $values['opp_email_id'];
						break;
					case 'opp_email_org_link':
						$from_context = CerberusContexts::CONTEXT_ORG;
						@$from_context_id = $values['opp_email_org_id'];
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
				'send_email' => array('label' => 'Send email'),
				'set_status' => array('label' => 'Set status'),
			)
			+ DevblocksEventHelper::getActionCustomFields(CerberusContexts::CONTEXT_OPPORTUNITY)
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
			
			case 'send_email':
				DevblocksEventHelper::renderActionSendEmail();
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
				
			case 'set_status':
				$tpl->display('devblocks:cerberusweb.crm::crm/opps/events/macro/action_set_status.tpl');
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
		@$opp_id = $values['opp_id'];

		if(empty($opp_id))
			return;
		
		switch($token) {
			case 'add_watchers':
				DevblocksEventHelper::runActionAddWatchers($params, $values, CerberusContexts::CONTEXT_OPPORTUNITY, $opp_id);
				break;
			
			case 'send_email':
				DevblocksEventHelper::runActionSendEmail($params, $values);
				break;
				
			case 'create_comment':
				DevblocksEventHelper::runActionCreateComment($params, $values, CerberusContexts::CONTEXT_OPPORTUNITY, $opp_id);
				break;
				
			case 'create_notification':
				DevblocksEventHelper::runActionCreateNotification($params, $values, CerberusContexts::CONTEXT_OPPORTUNITY, $opp_id);
				break;
				
			case 'create_task':
				DevblocksEventHelper::runActionCreateTask($params, $values, CerberusContexts::CONTEXT_OPPORTUNITY, $opp_id);
				break;

			case 'create_ticket':
				DevblocksEventHelper::runActionCreateTicket($params, $values, CerberusContexts::CONTEXT_OPPORTUNITY, $opp_id);
				break;
				
			case 'set_status':
				@$to_status = $params['status'];
				@$current_status = $values['opp_status'];
				
				if($to_status == $current_status)
					break;
				
				$fields = array();
					
				switch($to_status) {
					case 'open':
						$fields = array(
							DAO_CrmOpportunity::IS_CLOSED => 0,
							DAO_CrmOpportunity::IS_WON => 0,
						);
						break;
					case 'closed_won':
						$fields = array(
							DAO_CrmOpportunity::IS_CLOSED => 1,
							DAO_CrmOpportunity::IS_WON => 1,
						);
						break;
					case 'closed_lost':
						$fields = array(
							DAO_CrmOpportunity::IS_CLOSED => 1,
							DAO_CrmOpportunity::IS_WON => 0,
						);
						break;
				}
				
				if(!empty($fields)) {
					DAO_CrmOpportunity::update($opp_id, $fields);
					$values['status'] = $to_status;
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
						case CerberusContexts::CONTEXT_OPPORTUNITY:
							$context = $custom_field->context;
							$context_id = $opp_id;
							break;
					}
					
					if(!empty($context) && !empty($context_id))
						DevblocksEventHelper::runActionSetCustomField($custom_field, 'opp_custom', $params, $values, $context, $context_id);
				}
				break;				
		}
	}	
};