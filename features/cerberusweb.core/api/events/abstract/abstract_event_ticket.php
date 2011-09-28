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

abstract class AbstractEvent_Ticket extends Extension_DevblocksEvent {
	protected $_event_id = null; // override
	
	/**
	 * 
	 * @param integer $ticket_id
	 * @param integer $group_id
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel($ticket_id=null) {
		if(empty($ticket_id)) {
			// Pull the latest ticket
			list($results) = DAO_Ticket::search(
				array(),
				array(
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_CLOSED,'=',0),
				),
				10,
				0,
				SearchFields_Ticket::TICKET_ID,
				false,
				false
			);
			
			shuffle($results);
			
			$result = array_shift($results);
			
			$ticket_id = $result[SearchFields_Ticket::TICKET_ID];
		}
		
		return new Model_DevblocksEvent(
			$this->_event_id,
			array(
				'ticket_id' => $ticket_id,
			)
		);
	}	
	
	function setEvent(Model_DevblocksEvent $event_model=null) {
		$labels = array();
		$values = array();
		$blank = array();

		@$ticket_id = $event_model->params['ticket_id']; 
		
		/**
		 * Ticket
		 */
		$merge_token_labels = array();
		$merge_token_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_TICKET, $ticket_id, $merge_token_labels, $merge_token_values, null, true);

			@$group_id = $values['group_id'];

			// Clear dupe labels
			CerberusContexts::scrubTokensWithRegexp(
				$merge_token_labels,
				$blank, // ignore
				array(
					"#^group_#",
					"#^bucket_id$#",
				)
			);
			
			// Merge
			CerberusContexts::merge(
				'ticket_',
				'',
				$merge_token_labels,
				$merge_token_values,
				$labels,
				$values
			);

		/**
		 * Group
		 */
		
		$group_labels = array();
		$group_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_GROUP, $group_id, $group_labels, $group_values, null, true);
				
			// Merge
			CerberusContexts::merge(
				'group_',
				'',
				$group_labels,
				$group_values,
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
		
		$labels['ticket_has_owner'] = 'Ticket has owner';
		$labels['ticket_initial_message_header'] = 'Ticket initial message email header';
		$labels['ticket_latest_message_header'] = 'Ticket latest message email header';
		$labels['ticket_latest_incoming_activity'] = 'Ticket latest incoming activity';
		$labels['ticket_latest_outgoing_activity'] = 'Ticket latest outgoing activity';
		$labels['ticket_watcher_count'] = 'Ticket watcher count';
		
		$labels['group_id'] = 'Group';
		$labels['group_and_bucket'] = 'Group and bucket';
		
		$labels['group_link'] = 'Group is linked';
		$labels['owner_link'] = 'Ticket owner is linked';
		$labels['ticket_initial_message_sender_link'] = 'Ticket initial message sender is linked';
		$labels['ticket_initial_message_sender_org_link'] = 'Ticket initial message sender org is linked';
		$labels['ticket_latest_message_sender_link'] = 'Ticket latest message sender is linked';
		$labels['ticket_latest_message_sender_org_link'] = 'Ticket latest message sender org is linked';
		$labels['ticket_link'] = 'Ticket is linked';
		
		$types = array(
			'ticket_initial_message_content' => Model_CustomField::TYPE_MULTI_LINE,
			'ticket_initial_message_created|date' => Model_CustomField::TYPE_DATE,
			'ticket_initial_message_is_outgoing' => Model_CustomField::TYPE_CHECKBOX,
			'ticket_initial_message_sender_address' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_sender_first_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_sender_full_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_sender_is_banned' => Model_CustomField::TYPE_CHECKBOX,
			'ticket_initial_message_sender_last_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_sender_num_nonspam' => Model_CustomField::TYPE_NUMBER,
			'ticket_initial_message_sender_num_spam' => Model_CustomField::TYPE_NUMBER,
			'ticket_initial_message_sender_org_city' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_sender_org_country' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_sender_org_created' => Model_CustomField::TYPE_DATE,
			'ticket_initial_message_sender_org_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_sender_org_phone' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_sender_org_postal' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_sender_org_province' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_sender_org_street' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_sender_org_website' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_initial_message_storage_size' => Model_CustomField::TYPE_NUMBER,
		
			'ticket_latest_message_content' => Model_CustomField::TYPE_MULTI_LINE,
			'ticket_latest_message_created|date' => Model_CustomField::TYPE_DATE,
			'ticket_latest_message_is_outgoing' => Model_CustomField::TYPE_CHECKBOX,
			'ticket_latest_message_sender_address' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_sender_first_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_sender_full_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_sender_is_banned' => Model_CustomField::TYPE_CHECKBOX,
			'ticket_latest_message_sender_last_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_sender_num_nonspam' => Model_CustomField::TYPE_NUMBER,
			'ticket_latest_message_sender_num_spam' => Model_CustomField::TYPE_NUMBER,
			'ticket_latest_message_sender_org_city' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_sender_org_country' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_sender_org_created' => Model_CustomField::TYPE_DATE,
			'ticket_latest_message_sender_org_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_sender_org_phone' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_sender_org_postal' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_sender_org_province' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_sender_org_street' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_sender_org_website' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_latest_message_storage_size' => Model_CustomField::TYPE_NUMBER,
		
			'group_id' => null,
			"group_name" => Model_CustomField::TYPE_SINGLE_LINE,
			'group_and_bucket' => null,
		
			'ticket_owner_address_address' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_owner_first_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_owner_full_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_owner_last_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_owner_title' => Model_CustomField::TYPE_SINGLE_LINE,
		
			"ticket_bucket_name|default('Inbox')" => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_created|date' => Model_CustomField::TYPE_DATE,
			'ticket_mask' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_spam_score' => null,
			'ticket_spam_training' => null,
			'ticket_status' => null,
			'ticket_subject' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_updated|date' => Model_CustomField::TYPE_DATE,
			'ticket_url' => Model_CustomField::TYPE_URL,
		
			'ticket_has_owner' => null,
			'ticket_initial_message_header' => null,
			'ticket_latest_message_header' => null,
			'ticket_latest_incoming_activity' => null,
			'ticket_latest_outgoing_activity' => null,
			'ticket_watcher_count' => null,
			
			'group_link' => null,
			'owner_link' => null,
			'ticket_initial_message_sender_link' => null,
			'ticket_initial_message_sender_org_link' => null,
			'ticket_latest_message_sender_link' => null,
			'ticket_latest_message_sender_org_link' => null,
			'ticket_link' => null,
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
			case 'ticket_has_owner':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_bool.tpl');
				break;
			case 'ticket_watcher_count':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_number.tpl');
				break;
			case 'ticket_spam_score':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_spam_score.tpl');
				break;
			case 'ticket_spam_training':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_spam_training.tpl');
				break;
			case 'ticket_status':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_status.tpl');
				break;
			case 'ticket_initial_message_header':
			case 'ticket_latest_message_header':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_header.tpl');
				break;
			case 'ticket_latest_incoming_activity':
			case 'ticket_latest_outgoing_activity':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_date.tpl');
				break;
			case 'group_id':
				$groups = DAO_Group::getAll();
				$tpl->assign('groups', $groups);
				
				$tpl->display('devblocks:cerberusweb.core::events/model/ticket/condition_group.tpl');
				break;
			case 'group_and_bucket':
				$groups = DAO_Group::getAll();
				
				switch($trigger->owner_context) {
					// If the owner of the behavior is a group
					case CerberusContexts::CONTEXT_GROUP:
						foreach($groups as $group_id => $group) {
							if($group_id != $trigger->owner_context_id)
								unset($groups[$group_id]);
						}
						break;
				}
				
				$tpl->assign('groups', $groups);
				
				$group_buckets = DAO_Bucket::getGroups();
				$tpl->assign('buckets_by_group', $group_buckets);
				
				$tpl->display('devblocks:cerberusweb.core::events/model/ticket/condition_group_and_bucket.tpl');
				break;
			case 'group_link':
			case 'owner_link':
			case 'ticket_initial_message_sender_link':
			case 'ticket_initial_message_sender_org_link':
			case 'ticket_latest_message_sender_link':
			case 'ticket_latest_message_sender_org_link':
			case 'ticket_link':
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
			case 'ticket_has_owner':
				$bool = $params['bool'];
				@$value = $values['ticket_owner_id'];
				$pass = ($bool == !empty($value));
				break;
				
			case 'ticket_watcher_count':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				@$ticket_id = $values['ticket_id'];

				$watchers = CerberusContexts::getWatchers(CerberusContexts::CONTEXT_TICKET, $ticket_id);
				$value = count($watchers);
				
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
							
			case 'ticket_spam_score':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				@$value = intval($values[$token] * 100);

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
				
			case 'ticket_spam_training':
			case 'ticket_status':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				@$value = $values[$token];
				
				if(!isset($params['values']) || !is_array($params['values'])) {
					$pass = false;
					break;
				}
				
				switch($oper) {
					case 'in':
						$pass = false;
						foreach($params['values'] as $v) {
							if($v == $value) {
								$pass = true;
								break;
							}
						}
						break;
				}
				$pass = ($not) ? !$pass : $pass;
				break;
				
			case 'ticket_initial_message_header':
			case 'ticket_latest_message_header':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				@$header = $params['header'];
				@$param_value = $params['value'];
				
				// Lazy load
				$token_msgid = str_replace('_header', '_id', $token);
				$value = DAO_MessageHeader::getOne($values[$token_msgid], $header);
				
				// Operators
				switch($oper) {
					case 'is':
						$pass = (0==strcasecmp($value,$param_value));
						break;
					case 'like':
						$regexp = DevblocksPlatform::strToRegExp($param_value);
						$pass = @preg_match($regexp, $value);
						break;
					case 'contains':
						$pass = (false !== stripos($value, $param_value)) ? true : false;
						break;
					case 'regexp':
						$pass = @preg_match($param_value, $value);
						break;
					default:
						$pass = false;
						break;
				}
				
				$pass = ($not) ? !$pass : $pass;
				break;
				
			case 'ticket_latest_incoming_activity':
			case 'ticket_latest_outgoing_activity':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				@$from = $params['from'];
				@$to = $params['to'];

				$value = $this->_lazyLoadToken($token, $values);
				
				@$from = intval(strtotime($from));
				@$to = intval(strtotime($to));
				
				$pass = ($value > $from && $value < $to);
				$pass = ($not) ? !$pass : $pass;
				break;
				
			case 'group_id':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				
				@$in_group_ids = $params['group_id'];
				@$group_id = intval($values['ticket_group_id']);
				
				$pass = in_array($group_id, $in_group_ids);
				$pass = ($not) ? !$pass : $pass;
				break;
				
			case 'group_and_bucket':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				
				@$in_group_id = $params['group_id'];
				@$in_bucket_ids = $params['bucket_id'];
				
				@$group_id = intval($values['ticket_group_id']);
				@$bucket_id = intval($values['ticket_bucket_id']);
				
				$pass = ($group_id==$in_group_id) && in_array($bucket_id, $in_bucket_ids);
				$pass = ($not) ? !$pass : $pass;
				break;
				
			case 'group_link':
			case 'owner_link':
			case 'ticket_initial_message_sender_link':
			case 'ticket_initial_message_sender_org_link':
			case 'ticket_latest_message_sender_link':
			case 'ticket_latest_message_sender_org_link':
			case 'ticket_link':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				
				$from_context = null;
				$from_context_id = null;

				switch($token) {
					case 'group_link':
						$from_context = CerberusContexts::CONTEXT_GROUP;
						@$from_context_id = $values['ticket_group_id'];
						break;
					case 'owner_link':
						$from_context = CerberusContexts::CONTEXT_WORKER;
						@$from_context_id = $values['ticket_owner_id'];
						break;
					case 'ticket_initial_message_sender_link':
						$from_context = CerberusContexts::CONTEXT_ADDRESS;
						@$from_context_id = $values['ticket_initial_message_sender_id'];
						break;
					case 'ticket_initial_message_sender_org_link':
						$from_context = CerberusContexts::CONTEXT_ORG;
						@$from_context_id = $values['ticket_initial_message_sender_org_id'];
						break;
					case 'ticket_latest_message_sender_link':
						$from_context = CerberusContexts::CONTEXT_ADDRESS;
						@$from_context_id = $values['ticket_latest_message_sender_id'];
						break;
					case 'ticket_latest_message_sender_org_link':
						$from_context = CerberusContexts::CONTEXT_ORG;
						@$from_context_id = $values['ticket_latest_message_sender_org_id'];
						break;
					case 'ticket_link':
						$from_context = CerberusContexts::CONTEXT_TICKET;
						@$from_context_id = $values['ticket_id'];
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
				'move_to' => array('label' => 'Move to'),
				'relay_email' => array('label' => 'Relay to worker email'),
				'schedule_behavior' => array('label' => 'Schedule behavior'),
				'schedule_email_recipients' => array('label' => 'Schedule email to recipients'),
				'send_email' => array('label' => 'Send email'),
				'send_email_recipients' => array('label' => 'Send email to recipients'),
				'set_owner' => array('label' =>'Set owner'),
				'set_spam_training' => array('label' => 'Set spam training'),
				'set_status' => array('label' => 'Set status'),
				'set_subject' => array('label' => 'Set subject'),
				'set_initial_sender_links' => array('label' => 'Set links on initial sender'),
				'set_initial_sender_org_links' => array('label' => 'Set links on initial sender org'),
				'set_latest_sender_links' => array('label' => 'Set links on latest sender'),
				'set_latest_sender_org_links' => array('label' => 'Set links on latest sender org'),
				'set_ticket_links' => array('label' => 'Set links on ticket'),
				'unschedule_behavior' => array('label' => 'Unschedule behavior'),
			)
			+ DevblocksEventHelper::getActionCustomFields(CerberusContexts::CONTEXT_TICKET)
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
			case 'set_owner':
				DevblocksEventHelper::renderActionSetTicketOwner();
				break;
			
			case 'add_watchers':
				DevblocksEventHelper::renderActionAddWatchers();
				break;

			case 'relay_email':
				switch($trigger->owner_context) {
					case CerberusContexts::CONTEXT_GROUP:
						// Filter to group members
						$group = DAO_Group::get($trigger->owner_context_id);
						DevblocksEventHelper::renderActionRelayEmail(
							array_keys($group->getMembers()),
							array('owner','watchers','workers'),
							'ticket_latest_message_content'
						);
						break;
						
					case CerberusContexts::CONTEXT_WORKER:
					default:
						$active_worker = CerberusApplication::getActiveWorker();
						DevblocksEventHelper::renderActionRelayEmail(
							array($active_worker->id),
							array('workers'),
							'ticket_latest_message_content'
						);
						break;
				}
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
				
			case 'schedule_email_recipients':
				DevblocksEventHelper::renderActionScheduleTicketReply();
				break;
				
			case 'send_email':
				DevblocksEventHelper::renderActionSendEmail();
				break;
				
			case 'send_email_recipients':
				$tpl->assign('workers', DAO_Worker::getAll());
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_owner/action_send_email_recipients.tpl');
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
				
			case 'set_spam_training':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/action_set_spam_training.tpl');
				break;
				
			case 'set_status':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/action_set_status.tpl');
				break;
				
			case 'set_subject':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_string.tpl');
				break;
			
			case 'move_to':
				$groups = DAO_Group::getAll();
				$tpl->assign('groups', $groups);

				$group_buckets = DAO_Bucket::getGroups();
				$tpl->assign('group_buckets', $group_buckets);
				
				$tpl->display('devblocks:cerberusweb.core::events/model/ticket/action_move_to.tpl');
				break;
				
			case 'set_initial_sender_links':
			case 'set_initial_sender_org_links':
			case 'set_latest_sender_links':
			case 'set_latest_sender_org_links':
			case 'set_ticket_links':
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
		@$ticket_id = $values['ticket_id'];
		@$message_id = $values['ticket_latest_message_id'];

		if(empty($message_id) || empty($ticket_id))
			return;
		
		switch($token) {
			case 'set_owner':
				DevblocksEventHelper::runActionSetTicketOwner($params, $values, $ticket_id);
				break;
			
			case 'add_watchers':
				DevblocksEventHelper::runActionAddWatchers($params, $values, CerberusContexts::CONTEXT_TICKET, $ticket_id);
				break;
			
			case 'relay_email':
				DevblocksEventHelper::runActionRelayEmail(
					$params,
					$values,
					CerberusContexts::CONTEXT_TICKET,
					$ticket_id,
					$values['ticket_group_id'],
					@$values['ticket_bucket_id'] or 0,
					$values['ticket_latest_message_id'],
					@$values['ticket_owner_id'] or 0,
					$values['ticket_latest_message_sender_address'],
					$values['ticket_latest_message_sender_full_name'],
					$values['ticket_subject']
				);
				break;
				
			case 'schedule_behavior':
				DevblocksEventHelper::runActionScheduleBehavior($params, $values, CerberusContexts::CONTEXT_TICKET, $ticket_id);
				break;
				
			case 'unschedule_behavior':
				DevblocksEventHelper::runActionUnscheduleBehavior($params, $values, CerberusContexts::CONTEXT_TICKET, $ticket_id);
				break;
				
			case 'schedule_email_recipients':
				DevblocksEventHelper::runActionScheduleTicketReply($params, $values, $ticket_id, $message_id);
				break;
				
			case 'send_email':
				DevblocksEventHelper::runActionSendEmail($params, $values);
				break;
				
			case 'send_email_recipients':
				// Translate message tokens
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$content = $tpl_builder->build($params['content'], $values);
				
				$properties = array(
					'ticket_id' => $ticket_id,
					'message_id' => $message_id,
					'content' => $content,
					'worker_id' => 0, //$worker_id,
				);
				
				if(isset($params['is_autoreply']) && !empty($params['is_autoreply']))
					$properties['is_autoreply'] = true;
				
				CerberusMail::sendTicketMessage($properties);
				break;
				
			case 'create_comment':
				DevblocksEventHelper::runActionCreateComment($params, $values, CerberusContexts::CONTEXT_TICKET, $ticket_id);
				break;
				
			case 'create_notification':
				DevblocksEventHelper::runActionCreateNotification($params, $values, CerberusContexts::CONTEXT_TICKET, $ticket_id);
				break;
				
			case 'create_task':
				DevblocksEventHelper::runActionCreateTask($params, $values, CerberusContexts::CONTEXT_TICKET, $ticket_id);
				break;

			case 'create_ticket':
				DevblocksEventHelper::runActionCreateTicket($params, $values, CerberusContexts::CONTEXT_TICKET, $ticket_id);
				break;

			case 'set_spam_training':
				@$to_training = $params['value'];
				@$current_training = $values['ticket_spam_training'];

				if($to_training == $current_training)
					break;
					
				switch($to_training) {
					case 'S':
						CerberusBayes::markTicketAsSpam($ticket_id);
						$values['ticket_spam_training'] = $to_training;
						break;
					case 'N':
						CerberusBayes::markTicketAsNotSpam($ticket_id);
						$values['ticket_spam_training'] = $to_training;
						break;
				}
				break;
				
			case 'set_status':
				@$to_status = $params['status'];
				@$current_status = $values['ticket_status'];
				
				if($to_status == $current_status)
					break;
					
				// Status
				switch($to_status) {
					case 'open':
						$fields = array(
							DAO_Ticket::IS_WAITING => 0,
							DAO_Ticket::IS_CLOSED => 0,
							DAO_Ticket::IS_DELETED => 0,
						);
						break;
					case 'waiting':
						$fields = array(
							DAO_Ticket::IS_WAITING => 1,
							DAO_Ticket::IS_CLOSED => 0,
							DAO_Ticket::IS_DELETED => 0,
						);
						break;
					case 'closed':
						$fields = array(
							DAO_Ticket::IS_WAITING => 0,
							DAO_Ticket::IS_CLOSED => 1,
							DAO_Ticket::IS_DELETED => 0,
						);
						break;
					case 'deleted':
						$fields = array(
							DAO_Ticket::IS_WAITING => 0,
							DAO_Ticket::IS_CLOSED => 1,
							DAO_Ticket::IS_DELETED => 1,
						);
						break;
					default:
						$fields = array();
						break;
				}
				if(!empty($fields)) {
					DAO_Ticket::update($ticket_id, $fields);
					$values['ticket_status'] = $to_status;
				}
				break;
				
			case 'set_subject':
				DAO_Ticket::update($ticket_id,array(
					DAO_Ticket::SUBJECT => $params['value'],
				));
				$values['ticket_subject'] = $params['value'];
				break;
				
			case 'move_to':
				@$to_group_id = intval($params['group_id']);
				@$current_group_id = intval($values['group_id']);
				
				@$to_bucket_id = intval($params['bucket_id']);
				@$current_bucket_id = intval($values['ticket_bucket_id']);

				$groups = DAO_Group::getAll();
				$buckets = DAO_Bucket::getAll();
				
				// Don't trigger a move event into the same group+bucket.
				if(
					($to_group_id == $current_group_id)
					&& ($to_bucket_id == $current_bucket_id)
					)
					break;
				
				// Don't move into non-existent groups
				if(empty($to_group_id) || !isset($groups[$to_group_id]))
					break;
				
				// ... or non-existent buckets
				if(!empty($to_bucket_id) && !isset($buckets[$to_bucket_id]))
					break;
				
				// Move
				DAO_Ticket::update($ticket_id, array(
					DAO_Ticket::GROUP_ID => $to_group_id,
					DAO_Ticket::BUCKET_ID => $to_bucket_id,
				));
				
				$values['group_id'] = $to_group_id;
				$values['ticket_bucket_id'] = $to_bucket_id;
				
				// Pull group context + merge
				if($to_group_id != $current_group_id) {
					$merge_token_labels = array();
					$merge_token_values = array();
					$labels = $this->getLabels();
					CerberusContexts::getContext(CerberusContexts::CONTEXT_GROUP, $to_group_id, $merge_token_labels, $merge_token_values, '', true);
			
					CerberusContexts::merge(
						'group_',
						'Group:',
						$merge_token_labels,
						$merge_token_values,
						$labels,
						$values
					);
				}
				
				if(!empty($to_bucket_id)) {
					$merge_token_labels = array();
					$merge_token_values = array();
					$labels = $this->getLabels();
					CerberusContexts::getContext(CerberusContexts::CONTEXT_BUCKET, $to_bucket_id, $merge_token_labels, $merge_token_values, '', true);
			
					CerberusContexts::merge(
						'ticket_bucket_',
						'Bucket:',
						$merge_token_labels,
						$merge_token_values,
						$labels,
						$values
					);
				}
				break;	
			
			case 'set_initial_sender_links':
			case 'set_initial_sender_org_links':
			case 'set_latest_sender_links':
			case 'set_latest_sender_org_links':
			case 'set_ticket_links':
				@$to_context_strings = $params['context_objects'];

				if(!is_array($to_context_strings) || empty($to_context_strings))
					break;

				$from_context = null;
				$from_context_id = null;
				
				switch($token) {
					case 'set_initial_sender_links':
						$from_context = CerberusContexts::CONTEXT_ADDRESS;
						@$from_context_id = $values['ticket_initial_message_sender_id'];
						break;
					case 'set_initial_sender_org_links':
						$from_context = CerberusContexts::CONTEXT_ORG;
						@$from_context_id = $values['ticket_initial_message_sender_org_id'];
						break;
					case 'set_latest_sender_links':
						$from_context = CerberusContexts::CONTEXT_ADDRESS;
						@$from_context_id = $values['ticket_latest_message_sender_id'];
						break;
					case 'set_latest_sender_org_links':
						$from_context = CerberusContexts::CONTEXT_ORG;
						@$from_context_id = $values['ticket_latest_message_sender_org_id'];
						break;
					case 'set_ticket_links':
						$from_context = CerberusContexts::CONTEXT_TICKET;
						@$from_context_id = $values['ticket_id'];
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
						case CerberusContexts::CONTEXT_TICKET:
							$context = $custom_field->context;
							$context_id = $ticket_id;
							break;
					}
					
					if(!empty($context) && !empty($context_id))
						DevblocksEventHelper::runActionSetCustomField($custom_field, 'ticket_custom', $params, $values, $context, $context_id);
				}
				break;				
		}
	}

	function _lazyLoadToken($token, &$values) {
		if(isset($values[$token]))
			return $values[$token];
		
		switch($token) {
			case '_messages':
				if(isset($values['_messages'])) {
					return $values['_messages'];
				} else {
					$messages = DAO_Message::getMessagesByTicket($values['ticket_id']);
					$values['_messages'] = $messages;
					return $messages;
				}
				break;
			
			case 'ticket_latest_incoming_activity':
			case 'ticket_latest_outgoing_activity':
				// We have some hints about the latest message
				// It'll either be incoming or outgoing
				@$latest_created = $values['ticket_latest_message_created'];
				@$latest_is_outgoing = !empty($values['ticket_latest_message_is_outgoing']);
				
				switch($token) {
					case 'ticket_latest_incoming_activity':
						// Can we just use the info we have already?
						if(!$latest_is_outgoing) {
							// Yes, cache it.
							$values[$token] = $latest_created;
							return $latest_created;
						} else {
							// No, find it.
							$messages = $this->_lazyLoadToken('_messages', $values);
							$value = null;
							foreach($messages as $message) { /* @var $message Model_Message */
								if(empty($message->is_outgoing))
									$value = $message->created_date;
							}
							$values[$token] = $value;
							return $value;
						}
						break;
						
					case 'ticket_latest_outgoing_activity':
						// Can we just use the info we have already?
						if($latest_is_outgoing) {
							// Yes, cache it.
							$values[$token] = $latest_created;
							return $latest_created;
						} else {
							// No, find it.
							$messages = $this->_lazyLoadToken('_messages', $values);
							$value = null;
							foreach($messages as $message) { /* @var $message Model_Message */
								if(!empty($message->is_outgoing))
									$value = $message->created_date;
							}
							$values[$token] = $value;
							return $value;
						}
						break;
				}
				break;
		}
		
		// No match
		return NULL;
	}
	
};
