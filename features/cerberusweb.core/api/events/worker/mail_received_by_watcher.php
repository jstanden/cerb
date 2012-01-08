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

class Event_MailReceivedByWatcher extends Extension_DevblocksEvent {
	const ID = 'event.mail.received.watcher';
	
	static function trigger($message_id, $worker_id) {
		$events = DevblocksPlatform::getEventService();
		$events->trigger(
	        new Model_DevblocksEvent(
	            self::ID,
                array(
                	'message_id' => $message_id,
                    'worker_id' => $worker_id,
                	'_whisper' => array(
                		CerberusContexts::CONTEXT_WORKER => array($worker_id),
                	),
                )
            )
		);
	} 

	/**
	 * 
	 * @param integer $message_id
	 * @param integer $worker_id
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel($message_id=null, $worker_id=null) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(empty($message_id)) {
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
			
			$message_id = $result[SearchFields_Ticket::TICKET_LAST_MESSAGE_ID];
			$worker_id = $active_worker->id;
		}
		
		return new Model_DevblocksEvent(
			self::ID,
			array(
				'message_id' => $message_id,
				'worker_id' => $worker_id,
			)
		);
	}	
	
	function setEvent(Model_DevblocksEvent $event_model=null) {
		@$message_id = $event_model->params['message_id']; 
		@$worker_id = $event_model->params['worker_id'];
		 
		$labels = array();
		$values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_MESSAGE, $message_id, $labels, $values, null, true);

		$values['sender_is_worker'] = (!empty($values['worker_id'])) ? 1 : 0;
		$values['sender_is_me'] = (!empty($worker_id) && isset($values['worker_id']) && $worker_id==$values['worker_id']) ? 1 : 0;
		
		/**
		 * Ticket
		 */
		@$ticket_id = $values['ticket_id']; 
		$ticket_labels = array();
		$ticket_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_TICKET, $ticket_id, $ticket_labels, $ticket_values, null, true);

			// Fill some custom values
			if(!is_null($event_model)) {
				$values['is_first'] = ($values['id'] == $ticket_values['initial_message_id']) ? 1 : 0;
			}

			@$group_id = $ticket_values['group_id'];
			
			// Clear dupe content
			CerberusContexts::scrubTokensWithRegexp(
				$ticket_labels,
				$ticket_values,
				array(
					"#^initial_message_#",
					"#^latest_message_#",
					"#^group_#",
					"#^id$#",
				)
			);
			
			// Merge
			CerberusContexts::merge(
				'ticket_',
				'',
				$ticket_labels,
				$ticket_values,
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
		 * Sender Worker
		 */
		@$worker_id = $values['worker_id'];
		$worker_labels = array();
		$worker_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $worker_id, $worker_labels, $worker_values, null, true);
				
			// Clear dupe content
			CerberusContexts::scrubTokensWithRegexp(
				$worker_labels,
				$worker_values,
				array(
					"#^address_org_#",
				)
			);
		
			// Merge
			CerberusContexts::merge(
				'sender_worker_',
				'Message sender ',
				$worker_labels,
				$worker_values,
				$labels,
				$values
			);
						
		$this->setLabels($labels);
		$this->setValues($values);
	}
	
	function getConditionExtensions() {
		$labels = $this->getLabels();
		
		$labels['is_first'] = 'Message is first in conversation';
		$labels['sender_is_worker'] = 'Message sender is a worker';
		$labels['sender_is_me'] = 'Message sender is me';
		
		$labels['ticket_has_owner'] = 'Ticket has owner';
		
		$labels['group_id'] = 'Group';
		$labels['group_and_bucket'] = 'Group and bucket';
		
		$types = array(
			'content' => Model_CustomField::TYPE_MULTI_LINE,
			'created|date' => Model_CustomField::TYPE_DATE,
			'is_first' => Model_CustomField::TYPE_CHECKBOX,
			'is_outgoing' => Model_CustomField::TYPE_CHECKBOX,
			'sender_address' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_first_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_full_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_is_banned' => Model_CustomField::TYPE_CHECKBOX,
			'sender_is_worker' => Model_CustomField::TYPE_CHECKBOX,
			'sender_is_me' => Model_CustomField::TYPE_CHECKBOX,
			'sender_last_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_num_nonspam' => Model_CustomField::TYPE_NUMBER,
			'sender_num_spam' => Model_CustomField::TYPE_NUMBER,
			'sender_org_city' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_org_country' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_org_created' => Model_CustomField::TYPE_DATE,
			'sender_org_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_org_phone' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_org_postal' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_org_province' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_org_street' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_org_website' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_worker_address_address' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_worker_full_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'storage_size' => Model_CustomField::TYPE_NUMBER,
			
			'group_id' => null,
			'group_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'group_and_bucket' => null,
		
			'ticket_owner_address_address' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_owner_first_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_owner_full_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_owner_last_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_owner_title' => Model_CustomField::TYPE_SINGLE_LINE,
			
			"ticket_bucket_name|default('Inbox')" => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_created|date' => Model_CustomField::TYPE_DATE,
			'ticket_mask' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_subject' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_updated|date' => Model_CustomField::TYPE_DATE,
			'ticket_url' => Model_CustomField::TYPE_URL,
			
			'ticket_has_owner' => null,
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
		}
		return;
	}

	function runConditionExtension($token, $trigger, $params, $values) {
		$pass = true;
		
		switch($token) {
			case 'ticket_has_owner':
				$bool = $params['bool'];
				@$value = $values['ticket_owner_id'];
				$pass = ($bool == !empty($value));
				break;
			
			case 'group_id':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				
				@$in_group_ids = $params['group_id'];
				@$group_id = intval($values['group_id']);
				
				$pass = in_array($group_id, $in_group_ids);
				$pass = ($not) ? !$pass : $pass;
				break;
				
			case 'group_and_bucket':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				
				@$in_group_id = $params['group_id'];
				@$in_bucket_ids = $params['bucket_id'];
				
				@$group_id = intval($values['group_id']);
				@$bucket_id = intval($values['ticket_bucket_id']);
				
				$pass = ($group_id==$in_group_id) && in_array($bucket_id, $in_bucket_ids);
				$pass = ($not) ? !$pass : $pass;
				break;
			
			default:
				$pass = false;
				break;
		}
		
		return $pass;
	}	
	
	function getActionExtensions() {
		$actions = array(
			'add_watchers' => array('label' =>'Add watchers'),
			//'set_spam_training' => array('label' => 'Set spam training'),
			//'set_status' => array('label' => 'Set status'),
			'send_email' => array('label' => 'Send email'),
			'relay_email' => array('label' => 'Relay to external email'),
			'send_email_recipients' => array('label' => 'Reply to recipients'),
			'schedule_behavior' => array('label' => 'Schedule behavior'),
			'create_comment' => array('label' =>'Create a comment'),
			'create_notification' => array('label' =>'Create a notification'),
			'create_task' => array('label' =>'Create a task'),
			'create_ticket' => array('label' =>'Create a ticket'),
			'unschedule_behavior' => array('label' => 'Unschedule behavior'),
		);
		
		// [TODO] Add set custom fields
		
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
			case 'send_email':
				DevblocksEventHelper::renderActionSendEmail();
				break;
				
			case 'relay_email':
				// Filter to trigger owner
				DevblocksEventHelper::renderActionRelayEmail(
					array($trigger->owner_context_id),
					array('workers'),
					'content'
				);
				break;
				
			case 'send_email_recipients':
				$tpl->assign('workers', DAO_Worker::getAll());
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_owner/action_send_email_recipients.tpl');
				break;
				
			case 'schedule_behavior':
				$dates = array();
				$conditions = $this->getConditions($trigger);
				foreach($conditions as $key => $data) {
					if($data['type'] == Model_CustomField::TYPE_DATE)
					$dates[$key] = $data['label'];
				}
				$tpl->assign('dates', $dates);
			
				DevblocksEventHelper::renderActionScheduleBehavior($trigger->owner_context, $trigger->owner_context_id, 'event.macro.ticket');
				break;
				
			case 'unschedule_behavior':
				DevblocksEventHelper::renderActionUnscheduleBehavior($trigger->owner_context, $trigger->owner_context_id, 'event.macro.ticket');
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
				
//			default:
//				if('set_cf_' == substr($token,0,7)) {
//					$field_id = substr($token,7);
//					$custom_field = DAO_CustomField::get($field_id);
//					DevblocksEventHelper::renderActionSetCustomField($custom_field);
//				}
//				break;
		}
		
		$tpl->clearAssign('params');
		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('token_labels');
	}
	
	function runActionExtension($token, $trigger, $params, &$values) {
		@$ticket_id = $values['ticket_id'];
		@$message_id = $values['id'];

		if(empty($ticket_id) || empty($message_id))
			return;
		
		switch($token) {
			case 'send_email':
				DevblocksEventHelper::runActionSendEmail($params, $values);
				break;
				
			case 'relay_email':
				DevblocksEventHelper::runActionRelayEmail(
					$params,
					$values,
					CerberusContexts::CONTEXT_TICKET,
					$ticket_id,
					$values['group_id'],
					@intval($values['ticket_bucket_id']),
					$message_id,
					@intval($values['ticket_owner_id']),
					$values['sender_address'],
					$values['sender_full_name'],
					$values['ticket_subject']
				);
				break;
				
			case 'send_email_recipients':
				// Translate message tokens
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$content = $tpl_builder->build($params['content'], $values);
				
				$properties = array(
					'ticket_id' => $ticket_id,
					'message_id' => $message_id,
					'content' => $content,
					'worker_id' => 0,
				);
				CerberusMail::sendTicketMessage($properties);
				break;
				
			case 'schedule_behavior':
				DevblocksEventHelper::runActionScheduleBehavior($params, $values, CerberusContexts::CONTEXT_TICKET, $ticket_id);
				break;
				
			case 'unschedule_behavior':
				DevblocksEventHelper::runActionUnscheduleBehavior($params, $values, CerberusContexts::CONTEXT_TICKET, $ticket_id);
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
		}
	}
};