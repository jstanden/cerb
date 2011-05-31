<?php
class Event_MailReceivedByGroup extends Extension_DevblocksEvent {
	const ID = 'event.mail.received.group';
	
	static function trigger($message_id, $group_id) {
		$events = DevblocksPlatform::getEventService();
		$events->trigger(
	        new Model_DevblocksEvent(
	            self::ID,
                array(
                    'message_id' => $message_id,
                    'group_id' => $group_id,
                	'_whisper' => array(
                		CerberusContexts::CONTEXT_GROUP => array($group_id),
                	),
                )
            )
		);
	} 
	
	/**
	 * 
	 * @param integer $message_id
	 * @param integer $group_id
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel($message_id=null, $group_id=null) {
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
			$group_id = $result[SearchFields_Ticket::TICKET_TEAM_ID];
		}
		
		return new Model_DevblocksEvent(
			self::ID,
			array(
				'message_id' => $message_id,
				'group_id' => $group_id,
			)
		);
	}
	
	function setEvent(Model_DevblocksEvent $event_model=null) {
		/**
		 * Message
		 */
		
		@$message_id = $event_model->params['message_id'];
		$labels = array();
		$values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_MESSAGE, $message_id, $labels, $values, null, true);

		// Fill in some custom values
		$values['sender_is_worker'] = (!empty($values['worker_id'])) ? 1 : 0;
		
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
		@$group_id = $event_model->params['group_id'];
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
			
		/**
		 * Return
		 */
		
		$this->setLabels($labels);
		$this->setValues($values);		
	}
	
	function getConditionExtensions() {
		$labels = $this->getLabels();
		
		$labels['header'] = 'Message header';
		$labels['is_first'] = 'Message is first in conversation';
		$labels['sender_is_worker'] = 'Message sender is a worker';
		
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
		
			"group_name" => Model_CustomField::TYPE_SINGLE_LINE,
		
			"ticket_bucket_name|default('Inbox')" => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_created|date' => Model_CustomField::TYPE_DATE,
			'ticket_mask' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_spam_score' => null,
			'ticket_spam_training' => null,
			'ticket_status' => null,
			'ticket_subject' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_updated|date' => Model_CustomField::TYPE_DATE,
			'ticket_url' => Model_CustomField::TYPE_URL,
		
			'header' => null,
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
			case 'ticket_spam_score':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_spam_score.tpl');
				break;
			case 'ticket_spam_training':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_spam_training.tpl');
				break;
			case 'ticket_status':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_status.tpl');
				break;
			// [TODO] Internalize
			case 'header':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_header.tpl');
				break;
		}

		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('params');
	}
	
	function runConditionExtension($token, $trigger, $params, $values) {
		$pass = true;
		
		switch($token) {
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
				
			case 'header':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				@$header = $params['header'];
				@$param_value = $params['value'];
				
				// Lazy load
				$value = DAO_MessageHeader::getOne($values['id'], $header);
				
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
				'move_to_bucket' => array('label' => 'Move to bucket'),
				'move_to_group' => array('label' => 'Move to group'),
				'relay_email' => array('label' => 'Relay to external email'),
				'send_email' => array('label' => 'Send email'),
				'send_email_recipients' => array('label' => 'Reply to recipients'),
				'set_spam_training' => array('label' => 'Set spam training'),
				'set_status' => array('label' => 'Set status'),
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
			case 'add_watchers':
				DevblocksEventHelper::renderActionAddWatchers();
				break;
				
			case 'relay_email':
				// [TODO] Filter to group members
				$group = DAO_Group::get($trigger->owner_context_id);
				DevblocksEventHelper::renderActionRelayEmail(array_keys($group->getMembers()));
				break;
				
			case 'send_email':
				DevblocksEventHelper::renderActionSendEmail();
				break;
				
			case 'send_email_recipients':
				// [TODO] Share
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
				
			case 'move_to_bucket':
				// [TODO] Share
				$buckets = DAO_Bucket::getByTeam($trigger->owner_context_id);
				$tpl->assign('buckets', $buckets);
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/action_move_to_bucket.tpl');
				break;
				
			case 'move_to_group':
				// [TODO] Use trigger cache
				$groups = DAO_Group::getAll();
				$tpl->assign('groups', $groups);
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/action_move_to_group.tpl');
				break;
				
		}
		
		$tpl->clearAssign('params');
		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('token_labels');		
	}
	
	function runActionExtension($token, $trigger, $params, &$values) {
		@$message_id = $values['id'];
		@$ticket_id = $values['ticket_id'];

		if(empty($message_id) || empty($ticket_id))
			return;
		
		switch($token) {
			case 'add_watchers':
				DevblocksEventHelper::runActionAddWatchers($params, $values, CerberusContexts::CONTEXT_TICKET, $ticket_id);
				break;
			
			case 'send_email':
				DevblocksEventHelper::runActionSendEmail($params, $values);
				break;
				
			case 'relay_email':
				DevblocksEventHelper::runActionRelayEmail($params, $values, CerberusContexts::CONTEXT_TICKET, $ticket_id);
				break;
				
			case 'send_email_recipients':
				// Translate message tokens
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$content = $tpl_builder->build($params['content'], $values);
				
				$properties = array(
					'ticket_id' => $ticket_id,
					'message_id' => $message_id,
					'content' => $content,
					'agent_id' => 0, //$worker_id,
				);
				
				if(isset($params['is_autoreply']) && !empty($params['is_autoreply']))
					$properties['is_autoreply'] = true;
				
				CerberusMail::sendTicketMessage($properties);
				break;
				
			case 'create_comment':
				DevblocksEventHelper::runActionCreateComment($params, $values, CerberusContexts::CONTEXT_TICKET, $ticket_id);
				break;
				
			case 'create_notification':
				$url_writer = DevblocksPlatform::getUrlService();
				$url = $url_writer->writeNoProxy('c=display&id='.$values['ticket_mask'], true);
				
				DevblocksEventHelper::runActionCreateNotification($params, $values, $url);
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
				
			case 'move_to_group':
				@$to_group_id = intval($params['group_id']);
				@$current_group_id = intval($values['group_id']);
				$groups = DAO_Group::getAll();
				
				// Don't trigger a move event into the same bucket.
				if($to_group_id == $current_group_id)
					break;
				
				if(!empty($to_group_id) && !isset($groups[$to_group_id]))
					break;
					
				// Move
				DAO_Ticket::update($ticket_id, array(
					DAO_Ticket::TEAM_ID => $to_group_id, 
					DAO_Ticket::CATEGORY_ID => 0, 
				));
				
				// Pull group context + merge
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
				break;				
				
			case 'move_to_bucket':
				@$to_bucket_id = intval($params['bucket_id']);
				@$current_bucket_id = intval($values['ticket_bucket_id']);
				$buckets = DAO_Bucket::getAll();
				
				// Don't trigger a move event into the same bucket.
				if($to_bucket_id == $current_bucket_id)
					break;
				
				if(!empty($to_bucket_id) && !isset($buckets[$to_bucket_id]))
					break;
					
				// Move
				DAO_Ticket::update($ticket_id, array(
					DAO_Ticket::CATEGORY_ID => $to_bucket_id, 
				));
				$values['ticket_bucket_id'] = $to_bucket_id;
				break;
		}
	}
};