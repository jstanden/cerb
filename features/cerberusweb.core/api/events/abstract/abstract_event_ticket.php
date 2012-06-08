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

			@$group_id = $merge_token_values['group_id'];

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
	
	function getValuesContexts($trigger) {
		$vals = array(
			'ticket_id' => array(
				'label' => 'Ticket',
				'context' => CerberusContexts::CONTEXT_TICKET,
			),
			'ticket_watchers' => array(
				'label' => 'Ticket watchers',
				'context' => CerberusContexts::CONTEXT_WORKER,
			),
			'group_id' => array(
				'label' => 'Group',
				'context' => CerberusContexts::CONTEXT_GROUP,
			),
			'group_watchers' => array(
				'label' => 'Group watchers',
				'context' => CerberusContexts::CONTEXT_WORKER,
			),
			/*
			'bucket_id' => array(
				'label' => 'Bucket',
				'context' => CerberusContexts::CONTEXT_BUCKET,
			),
			*/
			'ticket_initial_message_sender_id' => array(
				'label' => 'Ticket initial message sender',
				'context' => CerberusContexts::CONTEXT_ADDRESS,
			),
			'ticket_initial_message_sender_org_id' => array(
				'label' => 'Ticket initial message sender org',
				'context' => CerberusContexts::CONTEXT_ORG,
			),
			'ticket_latest_message_sender_id' => array(
				'label' => 'Ticket latest message sender',
				'context' => CerberusContexts::CONTEXT_ADDRESS,
			),
			'ticket_latest_message_sender_org_id' => array(
				'label' => 'Ticket latest message sender org',
				'context' => CerberusContexts::CONTEXT_ORG,
			),
			'ticket_owner_id' => array(
				'label' => 'Ticket owner',
				'context' => CerberusContexts::CONTEXT_WORKER,
			),
			'ticket_org_id' => array(
				'label' => 'Ticket org',
				'context' => CerberusContexts::CONTEXT_ORG,
			),
			'ticket_org_watchers' => array(
				'label' => 'Ticket org watchers',
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
		
		$labels['ticket_has_owner'] = 'Ticket has owner';
		$labels['ticket_initial_message_header'] = 'Ticket initial message email header';
		$labels['ticket_latest_message_header'] = 'Ticket latest message email header';
		$labels['ticket_latest_incoming_activity'] = 'Ticket latest incoming activity';
		$labels['ticket_latest_outgoing_activity'] = 'Ticket latest outgoing activity';
		
		$labels['group_id'] = 'Group';
		$labels['group_and_bucket'] = 'Group and bucket';
		
		$labels['group_link'] = 'Group is linked';
		$labels['owner_link'] = 'Ticket owner is linked';
		$labels['ticket_initial_message_sender_link'] = 'Ticket initial message sender is linked';
		$labels['ticket_initial_message_sender_org_link'] = 'Ticket initial message sender org is linked';
		$labels['ticket_latest_message_sender_link'] = 'Ticket latest message sender is linked';
		$labels['ticket_latest_message_sender_org_link'] = 'Ticket latest message sender org is linked';
		$labels['ticket_link'] = 'Ticket is linked';
		
		$labels['group_watcher_count'] = 'Group watcher count';
		$labels['ticket_org_watcher_count'] = 'Ticket org watcher count';
		$labels['ticket_watcher_count'] = 'Ticket watcher count';
		
		$types = array(
			// First wrote
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
		
			// Last wrote
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
		
			// Group
			'group_id' => null,
			"group_name" => Model_CustomField::TYPE_SINGLE_LINE,
			'group_and_bucket' => null,
		
			// Owner
			'ticket_owner_address_address' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_owner_first_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_owner_full_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_owner_last_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_owner_title' => Model_CustomField::TYPE_SINGLE_LINE,
		
			// Org
			'ticket_org_city' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_org_country' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_org_created' => Model_CustomField::TYPE_DATE,
			'ticket_org_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_org_phone' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_org_postal' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_org_province' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_org_street' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_org_website' => Model_CustomField::TYPE_SINGLE_LINE,

			// Ticket
			"ticket_bucket_name|default('Inbox')" => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_created|date' => Model_CustomField::TYPE_DATE,
			'ticket_mask' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_num_messages' => Model_CustomField::TYPE_NUMBER,
			'ticket_reopen_date|date' => Model_CustomField::TYPE_DATE,
			'ticket_spam_score' => null,
			'ticket_spam_training' => null,
			'ticket_status' => null,
			'ticket_subject' => Model_CustomField::TYPE_SINGLE_LINE,
			'ticket_updated|date' => Model_CustomField::TYPE_DATE,
			'ticket_url' => Model_CustomField::TYPE_URL,
		
			// Virtual
			'ticket_has_owner' => null,
			'ticket_initial_message_header' => null,
			'ticket_latest_message_header' => null,
			'ticket_latest_incoming_activity' => null,
			'ticket_latest_outgoing_activity' => null,
			
			// Links
			'group_link' => null,
			'owner_link' => null,
			'ticket_initial_message_sender_link' => null,
			'ticket_initial_message_sender_org_link' => null,
			'ticket_latest_message_sender_link' => null,
			'ticket_latest_message_sender_org_link' => null,
			'ticket_link' => null,
			
			// Watchers
			'group_watcher_count' => null,
			'ticket_org_watcher_count' => null,
			'ticket_watcher_count' => null,
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
				
			case 'group_watcher_count':
			case 'ticket_org_watcher_count':
			case 'ticket_watcher_count':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_number.tpl');
				break;
		}

		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('params');
	}
	
	function runConditionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$pass = true;
		
		switch($token) {
			case 'ticket_has_owner':
				$bool = $params['bool'];
				@$value = $dict->ticket_owner_id;
				$pass = ($bool == !empty($value));
				break;
				
			case 'ticket_spam_score':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				
				if(!is_numeric($dict->token)) {
					@$dict->token = intval($dict->$token);
				}
				
				$value = $dict->token * 100;

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
				@$value = $dict->$token;
				
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
				$value = DAO_MessageHeader::getOne($dict->$token_msgid, $header);
				
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

				$value = $dict->$token;
				
				@$from = intval(strtotime($from));
				@$to = intval(strtotime($to));
				
				$pass = ($value > $from && $value < $to);
				$pass = ($not) ? !$pass : $pass;
				break;
				
			case 'group_id':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				
				@$in_group_ids = $params['group_id'];
				@$group_id = intval($dict->ticket_group_id);
				
				$pass = in_array($group_id, $in_group_ids);
				$pass = ($not) ? !$pass : $pass;
				break;
				
			case 'group_and_bucket':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				
				@$in_group_id = $params['group_id'];
				@$in_bucket_ids = $params['bucket_id'];
				
				@$group_id = intval($dict->ticket_group_id);
				@$bucket_id = intval($dict->ticket_bucket_id);
				
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
						@$from_context_id = $dict->ticket_group_id;
						break;
					case 'owner_link':
						$from_context = CerberusContexts::CONTEXT_WORKER;
						@$from_context_id = $dict->ticket_owner_id;
						break;
					case 'ticket_initial_message_sender_link':
						$from_context = CerberusContexts::CONTEXT_ADDRESS;
						@$from_context_id = $dict->ticket_initial_message_sender_id;
						break;
					case 'ticket_initial_message_sender_org_link':
						$from_context = CerberusContexts::CONTEXT_ORG;
						@$from_context_id = $dict->ticket_initial_message_sender_org_id;
						break;
					case 'ticket_latest_message_sender_link':
						$from_context = CerberusContexts::CONTEXT_ADDRESS;
						@$from_context_id = $dict->ticket_latest_message_sender_id;
						break;
					case 'ticket_latest_message_sender_org_link':
						$from_context = CerberusContexts::CONTEXT_ORG;
						@$from_context_id = $dict->ticket_latest_message_sender_org_id;
						break;
					case 'ticket_link':
						$from_context = CerberusContexts::CONTEXT_TICKET;
						@$from_context_id = $dict->ticket_id;
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
				
			case 'group_watcher_count':
			case 'ticket_org_watcher_count':
			case 'ticket_watcher_count':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');

				switch($token) {
					case 'group_watcher_count':
						$value = count($dict->group_watchers);
						break;
					case 'ticket_org_watcher_count':
						$value = count($dict->ticket_org_watchers);
						break;
					case 'ticket_watcher_count':
					default:
						$value = count($dict->ticket_watchers);
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
				$translate = DevblocksPlatform::getTranslationService();
				DevblocksEventHelper::renderActionCreateNotification($trigger);
				break;
				
			case 'create_task':
				DevblocksEventHelper::renderActionCreateTask($trigger);
				break;
				
			case 'create_ticket':
				DevblocksEventHelper::renderActionCreateTicket($trigger);
				break;
				
			case 'move_to':
				$groups = DAO_Group::getAll();
				$tpl->assign('groups', $groups);

				$group_buckets = DAO_Bucket::getGroups();
				$tpl->assign('group_buckets', $group_buckets);
				
				$tpl->display('devblocks:cerberusweb.core::events/model/ticket/action_move_to.tpl');
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
				$conditions = $this->getConditions($trigger);
				foreach($conditions as $key => $data) {
					if($data['type'] == Model_CustomField::TYPE_DATE)
					$dates[$key] = $data['label'];
				}
				$tpl->assign('dates', $dates);
			
				DevblocksEventHelper::renderActionScheduleBehavior($trigger);
				break;
				
			case 'schedule_email_recipients':
				DevblocksEventHelper::renderActionScheduleTicketReply();
				break;
				
			case 'set_owner':
				DevblocksEventHelper::renderActionSetTicketOwner($trigger);
				break;
			
			case 'send_email':
				DevblocksEventHelper::renderActionSendEmail($trigger);
				break;
				
			case 'send_email_recipients':
				$tpl->assign('workers', DAO_Worker::getAll());
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_owner/action_send_email_recipients.tpl');
				break;
				
			case 'set_spam_training':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/action_set_spam_training.tpl');
				break;
				
			case 'set_status':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/action_set_status.tpl');
				break;
				
			case 'set_subject':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_var_string.tpl');
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
		@$ticket_id = $dict->ticket_id;
		@$message_id = $dict->ticket_latest_message_id;

		if(empty($message_id) || empty($ticket_id))
			return;
		
		switch($token) {
			case 'add_watchers':
				return DevblocksEventHelper::simulateActionAddWatchers($params, $dict, 'ticket_id');
				break;
			
			case 'create_comment':
				return DevblocksEventHelper::simulateActionCreateComment($params, $dict, 'ticket_id');
				break;
				
			case 'create_notification':
				return DevblocksEventHelper::simulateActionCreateNotification($params, $dict, 'ticket_id');
				break;
				
			case 'create_task':
				return DevblocksEventHelper::simulateActionCreateTask($params, $dict, 'ticket_id');
				break;

			case 'create_ticket':
				return DevblocksEventHelper::simulateActionCreateTicket($params, $dict, 'ticket_id');
				break;

			case 'relay_email':
				/*
				DevblocksEventHelper::simulateActionRelayEmail(
					$params,
					$dict,
					CerberusContexts::CONTEXT_TICKET,
					$ticket_id,
					$dict->ticket_group_id,
					@intval($dict->ticket_bucket_id),
					$dict->ticket_latest_message_id,
					@intval($dict->ticket_owner_id),
					$dict->ticket_latest_message_sender_address,
					$dict->ticket_latest_message_sender_full_name,
					$dict->ticket_subject
				);
				*/
				break;
				
			case 'schedule_behavior':
				return DevblocksEventHelper::simulateActionScheduleBehavior($params, $dict);
				break;
				
			case 'schedule_email_recipients':
				//return DevblocksEventHelper::simulateActionScheduleTicketReply($params, $dict, $ticket_id, $message_id);
				break;
				
			case 'send_email':
				//return DevblocksEventHelper::simulateActionSendEmail($params, $dict);
				break;
				
			case 'send_email_recipients':
				break;
				
			case 'set_owner':
				break;
			
			case 'set_spam_training':
				break;
				
			case 'set_status':
				break;
				
			case 'set_subject':
				break;
				
			case 'move_to':
				break;	
			
			case 'set_initial_sender_links':
			case 'set_initial_sender_org_links':
			case 'set_latest_sender_links':
			case 'set_latest_sender_org_links':
			case 'set_ticket_links':
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
						case CerberusContexts::CONTEXT_TICKET:
							$context = $custom_field->context;
							$context_id = $ticket_id;
							break;
					}
					
					if(!empty($context) && !empty($context_id))
						return DevblocksEventHelper::simulateActionSetCustomField($custom_field, 'ticket_custom', $params, $dict, $context, $context_id);
				}
				break;				
		}
	}
	
	function runActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$ticket_id = $dict->ticket_id;
		@$message_id = $dict->ticket_latest_message_id;

		if(empty($message_id) || empty($ticket_id))
			return;
		
		switch($token) {
			case 'add_watchers':
				DevblocksEventHelper::runActionAddWatchers($params, $dict, 'ticket_id');
				break;
			
			case 'create_comment':
				DevblocksEventHelper::runActionCreateComment($params, $dict, 'ticket_id');
				break;
				
			case 'create_notification':
				DevblocksEventHelper::runActionCreateNotification($params, $dict, 'ticket_id');
				break;
				
			case 'create_task':
				DevblocksEventHelper::runActionCreateTask($params, $dict, 'ticket_id');
				break;

			case 'create_ticket':
				DevblocksEventHelper::runActionCreateTicket($params, $dict, 'ticket_id');
				break;

			case 'relay_email':
				DevblocksEventHelper::runActionRelayEmail(
					$params,
					$dict,
					CerberusContexts::CONTEXT_TICKET,
					$ticket_id,
					$dict->ticket_group_id,
					@intval($dict->ticket_bucket_id),
					$dict->ticket_latest_message_id,
					@intval($dict->ticket_owner_id),
					$dict->ticket_latest_message_sender_address,
					$dict->ticket_latest_message_sender_full_name,
					$dict->ticket_subject
				);
				break;
				
			case 'schedule_behavior':
				DevblocksEventHelper::runActionScheduleBehavior($params, $dict);
				break;
				
			case 'schedule_email_recipients':
				DevblocksEventHelper::runActionScheduleTicketReply($params, $dict, $ticket_id, $message_id);
				break;
				
			case 'send_email':
				DevblocksEventHelper::runActionSendEmail($params, $dict);
				break;
				
			case 'send_email_recipients':
				// Translate message tokens
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$content = $tpl_builder->build($params['content'], $dict);
				
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
				
			case 'set_owner':
				DevblocksEventHelper::runActionSetTicketOwner($params, $dict, $ticket_id, 'ticket_owner_');
				break;
			
			case 'set_spam_training':
				@$to_training = $params['value'];
				@$current_training = $dict->ticket_spam_training;

				if($to_training == $current_training)
					break;
					
				switch($to_training) {
					case 'S':
						CerberusBayes::markTicketAsSpam($ticket_id);
						$dict->ticket_spam_training = $to_training;
						break;
					case 'N':
						CerberusBayes::markTicketAsNotSpam($ticket_id);
						$dict->ticket_spam_training = $to_training;
						break;
				}
				break;
				
			case 'set_status':
				@$to_status = $params['status'];
				@$current_status = $dict->ticket_status;
				
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
					$dict->ticket_status = $to_status;
				}
				break;
				
			case 'set_subject':
				@$value = $params['value'];
				
				$builder = DevblocksPlatform::getTemplateBuilder();
				$value = $builder->build($value, $dict);
				
				DAO_Ticket::update($ticket_id,array(
					DAO_Ticket::SUBJECT => $value,
				));
				$dict->ticket_subject = $value;
				break;
				
			case 'move_to':
				@$to_group_id = intval($params['group_id']);
				@$current_group_id = intval($dict->group_id);
				
				@$to_bucket_id = intval($params['bucket_id']);
				@$current_bucket_id = intval($dict->ticket_bucket_id);

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
				
				$dict->group_id = $to_group_id;
				$dict->ticket_bucket_id = $to_bucket_id;
				
				/* // [TODO]
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
				*/
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
						@$from_context_id = $dict->ticket_initial_message_sender_id;
						break;
					case 'set_initial_sender_org_links':
						$from_context = CerberusContexts::CONTEXT_ORG;
						@$from_context_id = $dict->ticket_initial_message_sender_org_id;
						break;
					case 'set_latest_sender_links':
						$from_context = CerberusContexts::CONTEXT_ADDRESS;
						@$from_context_id = $dict->ticket_latest_message_sender_id;
						break;
					case 'set_latest_sender_org_links':
						$from_context = CerberusContexts::CONTEXT_ORG;
						@$from_context_id = $dict->ticket_latest_message_sender_org_id;
						break;
					case 'set_ticket_links':
						$from_context = CerberusContexts::CONTEXT_TICKET;
						@$from_context_id = $dict->ticket_id;
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
				
			case 'unschedule_behavior':
				DevblocksEventHelper::runActionUnscheduleBehavior($params, $dict);
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
						DevblocksEventHelper::runActionSetCustomField($custom_field, 'ticket_custom', $params, $dict, $context, $context_id);
				}
				break;				
		}
	}
};
