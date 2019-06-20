<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/

abstract class AbstractEvent_Message extends Extension_DevblocksEvent {
	protected $_event_id = null; // override
	
	/**
	 *
	 * @param Model_TriggerEvent $trigger
	 * @param integer $context_id
	 * @param integer $group_id
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel(Model_TriggerEvent $trigger, $context_id=null, $group_id=null) {
		if(empty($context_id)) {
			// Pull the latest ticket
			list($results) = DAO_Ticket::search(
				array(),
				array(
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_STATUS_ID,'!=',Model_Ticket::STATUS_DELETED),
				),
				10,
				0,
				SearchFields_Ticket::TICKET_ID,
				false,
				false
			);
			
			shuffle($results);
			
			$result = array_shift($results);
			
			$context_id = $result[SearchFields_Ticket::TICKET_LAST_MESSAGE_ID];
			$group_id = $result[SearchFields_Ticket::TICKET_GROUP_ID];
		}
		
		return new Model_DevblocksEvent(
			$this->_event_id,
			array(
				'context_id' => $context_id,
				'group_id' => $group_id,
			)
		);
	}
	
	function setEvent(Model_DevblocksEvent $event_model=null, Model_TriggerEvent $trigger=null) {
		// We can accept a model object or a context_id
		@$model = $event_model->params['context_model'] ?: $event_model->params['context_id'];
		
		/**
		 * Message
		 */
		
		$labels = $values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_MESSAGE, $model, $labels, $values, null, true);
		
		// Fill in some custom values
		$values['sender_is_worker'] = (!empty($values['worker_id'])) ? 1 : 0;
		
		/**
		 * Behavior
		 */
		
		$merge_labels = $merge_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_BEHAVIOR, $trigger, $merge_labels, $merge_values, null, true);

			// Merge
			CerberusContexts::merge(
				'behavior_',
				'',
				$merge_labels,
				$merge_values,
				$labels,
				$values
			);
		
		/**
		 * Ticket
		 */
		
		@$ticket_id = $values['ticket_id'];
		$group_id = 0;
		
		$ticket_labels = $ticket_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_TICKET, $ticket_id, $ticket_labels, $ticket_values, 'Message:Ticket:', true);

			// Fill some custom values
			if(!is_null($event_model) && isset($values['id']) && isset($ticket_values['initial_message_id'])) {
				$values['is_first'] = (isset($ticket_values['initial_message_id']) && $values['id'] == $ticket_values['initial_message_id']) ? 1 : 0;
			}
		
			if(isset($ticket_values['group_id']))
				$group_id = $ticket_values['group_id'];
			
			$values['ticket_has_owner'] = !empty($ticket_values['owner_id']) ? 1 : 0;
			
			// Clear dupe content
			CerberusContexts::scrubTokensWithRegexp(
				$ticket_labels,
				$ticket_values,
				array(
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
		$group_labels = $group_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_GROUP, $group_id, $group_labels, $group_values, 'Message:Ticket:Group:', true);
				
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
		$worker_labels = $worker_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $worker_id, $worker_labels, $worker_values, 'Message worker:', true);
				
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
				'',
				$worker_labels,
				$worker_values,
				$labels,
				$values
			);

		/**
		 * Caller actions
		 */
		
		if(isset($event_model->params['_caller_actions'])) {
			$values['_caller_actions'] =& $event_model->params['_caller_actions'];
		}
			
		/**
		 * Return
		 */
			
		$this->setLabels($labels);
		$this->setValues($values);
	}
	
	function renderSimulatorTarget($trigger, $event_model) {
		$context = CerberusContexts::CONTEXT_MESSAGE;
		$context_id = $event_model->params['context_id'];
		DevblocksEventHelper::renderSimulatorTarget($context, $context_id, $trigger, $event_model);
	}
	
	function getValuesContexts($trigger) {
		$vals = array(
			'behavior_id' => array(
				'label' => 'Behavior',
				'context' => CerberusContexts::CONTEXT_BEHAVIOR,
			),
			'behavior_bot_id' => array(
				'label' => 'Bot',
				'context' => CerberusContexts::CONTEXT_BOT,
			),
			'group_watchers' => array(
				'label' => 'Message ticket group watchers',
				'context' => CerberusContexts::CONTEXT_WORKER,
				'is_multiple' => true,
			),
			'id' => array(
				'label' => 'Message',
				'context' => CerberusContexts::CONTEXT_MESSAGE,
			),
			'sender_id' => array(
				'label' => 'Message sender email',
				'context' => CerberusContexts::CONTEXT_ADDRESS,
			),
			'sender_org' => array(
				'label' => 'Message sender org',
				'context' => CerberusContexts::CONTEXT_ORG,
			),
			'sender_org_watchers' => array(
				'label' => 'Message sender org watchers',
				'context' => CerberusContexts::CONTEXT_WORKER,
				'is_multiple' => true,
			),
			'sender_watchers' => array(
				'label' => 'Message sender watchers',
				'context' => CerberusContexts::CONTEXT_WORKER,
				'is_multiple' => true,
			),
			'ticket_bucket_id' => array(
				'label' => 'Message ticket bucket',
				'context' => CerberusContexts::CONTEXT_BUCKET,
			),
			'ticket_id' => array(
				'label' => 'Message ticket',
				'context' => CerberusContexts::CONTEXT_TICKET,
			),
			'ticket_org_id' => array(
				'label' => 'Message ticket org',
				'context' => CerberusContexts::CONTEXT_ORG,
			),
			'ticket_org_watchers' => array(
				'label' => 'Message ticket org watchers',
				'context' => CerberusContexts::CONTEXT_WORKER,
				'is_multiple' => true,
			),
			'ticket_owner_id' => array(
				'label' => 'Message ticket owner',
				'context' => CerberusContexts::CONTEXT_WORKER,
			),
			'ticket_watchers' => array(
				'label' => 'Message ticket watchers',
				'context' => CerberusContexts::CONTEXT_WORKER,
				'is_multiple' => true,
			),
			'worker_id' => array(
				'label' => 'Message sender worker',
				'context' => CerberusContexts::CONTEXT_WORKER,
			),
		);
		
		$vars = parent::getValuesContexts($trigger);
		
		$vals_to_ctx = array_merge($vals, $vars);
		DevblocksPlatform::sortObjects($vals_to_ctx, '[label]');
		
		return $vals_to_ctx;
	}
	
	function getConditionExtensions(Model_TriggerEvent $trigger) {
		$labels = $this->getLabels($trigger);
		$types = $this->getTypes();
		
		$labels['is_first'] = 'Message is first in conversation';
		$labels['sender_is_worker'] = 'Message sender is a worker';
		$labels['ticket_has_owner'] = 'Message ticket has owner';
		$labels['ticket_watcher_count'] = 'Message ticket watcher count';
		
		$labels['group_id'] = 'Message ticket group';
		$labels['group_and_bucket'] = 'Message ticket group and bucket';
		
		$labels['sender_link'] = 'Message sender is linked';
		$labels['sender_org_link'] = 'Message sender org is linked';
		$labels['ticket_link'] = 'Message ticket is linked';
		
		$labels['ticket_org_watcher_count'] = 'Message ticket org watcher count';
		$labels['ticket_watcher_count'] = 'Message ticket watcher count';
		$labels['sender_org_watcher_count'] = 'Message sender org watcher count';
		$labels['sender_watcher_count'] = 'Message sender watcher count';
		
		$types['is_first'] = Model_CustomField::TYPE_CHECKBOX;
		$types['sender_is_worker'] = Model_CustomField::TYPE_CHECKBOX;
		$types['ticket_has_owner'] = Model_CustomField::TYPE_CHECKBOX;
		$types['ticket_watcher_count'] = Model_CustomField::TYPE_NUMBER;
		
		$types['group_id'] = null;
		$types['group_and_bucket'] = null;
		
		$types['sender_link'] = null;
		$types['sender_org_link'] = null;
		$types['ticket_link'] = null;
		
		$types['ticket_org_watcher_count'] = null;
		$types['ticket_watcher_count'] = null;
		$types['sender_org_watcher_count'] = null;
		$types['sender_watcher_count'] = null;
		
		$conditions = $this->_importLabelsTypesAsConditions($labels, $types);
		
		// Allow overrides
		$conditions['ticket_spam_score']['type'] = null;
		$conditions['ticket_spam_training']['type'] = null;
		$conditions['ticket_status']['type'] = null;
		
		return $conditions;
	}
	
	function renderConditionExtension($token, $as_token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','condition'.$seq);
		
		switch($as_token) {
			case 'ticket_spam_score':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_spam_score.tpl');
				break;
			case 'ticket_spam_training':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_spam_training.tpl');
				break;
			case 'ticket_status':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_status.tpl');
				break;
			case 'group_id':
				$groups = DAO_Group::getAll();
				$tpl->assign('groups', $groups);
				
				$tpl->display('devblocks:cerberusweb.core::events/model/ticket/condition_group.tpl');
				break;
			case 'group_and_bucket':
				$groups = DAO_Group::getAll();
				$tpl->assign('groups', $groups);
				
				$group_buckets = DAO_Bucket::getGroups();
				$tpl->assign('buckets_by_group', $group_buckets);
				
				$tpl->display('devblocks:cerberusweb.core::events/model/ticket/condition_group_and_bucket.tpl');
				break;
				
			case 'sender_link':
			case 'sender_org_link':
			case 'ticket_link':
				$contexts = Extension_DevblocksContext::getAll(false);
				$tpl->assign('contexts', $contexts);
				$tpl->display('devblocks:cerberusweb.core::events/condition_link.tpl');
				break;
				
			case 'sender_org_watcher_count':
			case 'sender_watcher_count':
			case 'ticket_org_watcher_count':
			case 'ticket_watcher_count':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_number.tpl');
				break;
				
			case 'headers':
			case 'ticket_initial_message_headers':
			case 'ticket_initial_response_message_headers':
			case 'ticket_latest_message_headers':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_header.tpl');
				break;
		}

		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('params');
	}
	
	function runConditionExtension($token, $as_token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$pass = true;
		
		switch($as_token) {
			case 'ticket_watcher_count':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				@$ticket_id = $dict->ticket_id;

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
				@$value = intval($dict->$token * 100);

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
				
			case 'headers':
			case 'ticket_initial_message_headers':
			case 'ticket_initial_response_message_headers':
			case 'ticket_latest_message_headers':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				@$header = rtrim(DevblocksPlatform::strLower($params['header']), ':');
				@$param_value = $params['value'];
				
				// Lazy load
				@$header_values = $dict->$token;
				@$value = (is_array($header_values) && isset($header_values[$header])) ? $header_values[$header] : '';

				// Operators
				switch($oper) {
					case 'is':
						$pass = (0==strcasecmp($value, $param_value));
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

			case 'group_id':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				
				@$in_group_ids = $params['group_id'];
				$group_id = intval($dict->group_id);
				
				$pass = in_array($group_id, $in_group_ids);
				$pass = ($not) ? !$pass : $pass;
				break;
				
			case 'group_and_bucket':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				
				@$in_group_id = $params['group_id'];
				@$in_bucket_ids = $params['bucket_id'];
				
				@$group_id = intval($dict->group_id);
				@$bucket_id = intval($dict->ticket_bucket_id);
				
				$pass = ($group_id==$in_group_id) && in_array($bucket_id, $in_bucket_ids);
				$pass = ($not) ? !$pass : $pass;
				break;
				
			case 'sender_link':
			case 'sender_org_link':
			case 'ticket_link':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				
				$from_context = null;
				$from_context_id = null;
				
				switch($as_token) {
					case 'sender_link':
						$from_context = CerberusContexts::CONTEXT_ADDRESS;
						@$from_context_id = $dict->sender_id;
						break;
					case 'sender_org_link':
						$from_context = CerberusContexts::CONTEXT_ORG;
						@$from_context_id = $dict->sender_org_id;
						break;
					case 'ticket_link':
						$from_context = CerberusContexts::CONTEXT_TICKET;
						@$from_context_id = $dict->ticket_id;
						break;
					// [TODO] Worker, ticket org, group
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
				
			case 'sender_org_watcher_count':
			case 'sender_watcher_count':
			case 'ticket_org_watcher_count':
			case 'ticket_watcher_count':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				
				switch($as_token) {
					case 'sender_org_watcher_count':
						$value = count($dict->sender_org_watchers);
						break;
					case 'sender_watcher_count':
						$value = count($dict->sender_watchers);
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
	
	function getActionExtensions(Model_TriggerEvent $trigger) {
		$actions =
			[
				'add_recipients' => [
					'label' =>'Add recipients',
					'notes' => '',
					'params' => [
						'recipients' => [
							'type' => 'text',
							'required' => true,
							'notes' => 'A comma-delimited list of email addresses to add as recipients',
						]
					],
				],
				'create_message_note' => [
					'label' => 'Create sticky note',
					'deprecated' => true,
				],
				'move_to' => [
					'label' => 'Move to bucket',
					'notes' => '',
					'params' => [
						'group_id' => [
							'type' => 'text',
							'required' => true,
							'notes' => 'The [group](/docs/records/types/group/) to move the ticket into',
						],
						'bucket_id' => [
							'type' => 'text',
							'required' => true,
							'notes' => 'The [bucket](/docs/records/types/bucket/) to move the ticket into',
						]
					],
				],
				'relay_email' => [
					'label' => 'Send email relay to workers',
					'notes' => '',
					'params' => [
						'to' => [
							'type' => 'text',
							'required' => true,
							'notes' => 'An array of [email addresses](/docs/records/types/address/) recipients. These must match registered worker email addresses',
						],
						'to_owner' => [
							'type' => 'text',
							'notes' => 'Any value enables this option to include the ticket owner as a recipient',
						],
						'to_watchers' => [
							'type' => 'text',
							'notes' => 'Any value enables this option to include the ticket watchers as recipients',
						],
						'subject' => [
							'type' => 'text',
							'required' => true,
							'notes' => 'The subject of the relayed message',
						],
						'content' => [
							'type' => 'text',
							'required' => true,
							'notes' => 'The body of the relayed message',
						],
						'include_attachments' => [
							'type' => 'bit',
							'notes' => '`0` (do not include attachments) or `1` (include attachments)',
						],
					],
				],
				'remove_recipients' => [
					'label' =>'Remove recipients',
					'notes' => '',
					'params' => [
						'recipients' => [
							'type' => 'text',
							'required' => true,
							'notes' => 'A comma-delimited list of email addresses to remove as recipients',
						]
					],
				],
				'schedule_email_recipients' => [
					'label' => 'Send scheduled email to recipients',
					'notes' => '',
					'params' => [
						'content' => [
							'type' => 'text',
							'required' => true,
							'notes' => 'The message to send',
						],
						'delivery_date' => [
							'type' => 'datetime',
							'required' => true,
							'notes' => 'When to deliver the message (e.g. `now`, `+2 days`, `Friday 8am`)',
						]
					],
				],
				'send_email_recipients' => [
					'label' => 'Send email to recipients',
					'notes' => '',
					'params' => [
						'headers' => [
							'type' => 'text',
							'notes' => 'A list of `Header: Value` pairs delimited by newlines',
						],
						'format' => [
							'type' => 'text',
							'notes' => '`parsedown` for Markdown/HTML, or omitted for plaintext',
						],
						'content' => [
							'type' => 'text',
							'required' => true,
							'notes' => 'The email message body',
						],
						'html_template_id' => [
							'type' => 'id',
							'notes' => 'The [html template](/docs/records/types/html_template/) to use with Markdown format',
						],
						'bundle_ids' => [
							'type' => 'id[]',
							'notes' => 'An array of [file bundles](/docs/records/types/file_bundle/) to attach',
						],
						'is_autoreply' => [
							'type' => 'bit',
							'notes' => '`0` (not an autoreply), `1` (an autoreply)',
						],
					],
				],
				'set_importance' => [
					'label' =>'Set ticket importance',
					'deprecated' => true,
				],
				'set_org' => [
					'label' =>'Set organization',
					'deprecated' => true,
				],
				'set_owner' => [
					'label' =>'Set ticket owner',
					'deprecated' => true,
				],
				'set_reopen_date' => [
					'label' => 'Set ticket reopen date',
					'deprecated' => true,
				],
				'set_spam_training' => [
					'label' => 'Set ticket spam training',
					'deprecated' => true,
				],
				'set_status' => [
					'label' => 'Set ticket status',
					'deprecated' => true,
				],
				'set_subject' => [
					'label' => 'Set ticket subject',
					'deprecated' => true,
				],
			]
			+ DevblocksEventHelper::getActionCustomFieldsFromLabels($this->getLabels($trigger))
			;
			
		return $actions;
	}
	
	function getActionDefaultOn() {
		return 'ticket_id';
	}
	
	function getActionEmailRecipients() {
		return [
			'ticket_bucket_replyto_id,group_replyto_id' => 'Ticket Bucket',
		];
	}
	
	function renderActionExtension($token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','action'.$seq);

		$labels = $this->getLabels($trigger);
		$tpl->assign('token_labels', $labels);
			
		switch($token) {
			case 'add_recipients':
				DevblocksEventHelper::renderActionAddRecipients($trigger);
				break;
				
			case 'create_message_note':
				DevblocksEventHelper::renderActionCreateMessageStickyNote($trigger);
				break;
				
			case 'relay_email':
				if(false == ($va = $trigger->getBot()))
					break;
				
				switch($va->owner_context) {
					case CerberusContexts::CONTEXT_APPLICATION:
						// Filter to all workers
						DevblocksEventHelper::renderActionRelayEmail(
							array(), // allow all
							array('owner','watchers','workers'),
							'content'
						);
						break;
						
					case CerberusContexts::CONTEXT_GROUP:
						// Filter to group members
						$group = DAO_Group::get($va->owner_context_id);
						DevblocksEventHelper::renderActionRelayEmail(
							array_keys($group->getMembers()),
							array('owner','watchers','workers'),
							'content'
						);
						break;
						
					case CerberusContexts::CONTEXT_WORKER:
					default:
						$active_worker = CerberusApplication::getActiveWorker();
						DevblocksEventHelper::renderActionRelayEmail(
							array($active_worker->id),
							array('workers'),
							'content'
						);
						break;
				}
				break;
				
			case 'remove_recipients':
				DevblocksEventHelper::renderActionRemoveRecipients($trigger);
				break;
				
			case 'schedule_email_recipients':
				DevblocksEventHelper::renderActionScheduleTicketReply();
				break;
				
			case 'set_importance':
				DevblocksEventHelper::renderActionSetTicketImportance($trigger);
				break;
				
			case 'set_org':
				DevblocksEventHelper::renderActionSetTicketOrg($trigger);
				break;
				
			case 'set_owner':
				DevblocksEventHelper::renderActionSetTicketOwner($trigger);
				break;
			
			case 'set_reopen_date':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_date.tpl');
				break;
			
			case 'send_email_recipients':
				$tpl->assign('workers', DAO_Worker::getAll());
				
				$html_templates = DAO_MailHtmlTemplate::getAll();
				$tpl->assign('html_templates', $html_templates);
				
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_owner/action_send_email_recipients.tpl');
				break;
				
			case 'move_to':
				$groups = DAO_Group::getAll();
				$tpl->assign('groups', $groups);

				$group_buckets = DAO_Bucket::getGroups();
				$tpl->assign('group_buckets', $group_buckets);
				
				$tpl->display('devblocks:cerberusweb.core::events/model/ticket/action_move_to.tpl');
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
				
			default:
				$matches = [];
				if(preg_match('#set_cf_(.*?_*)custom_([0-9]+)#', $token, $matches)) {
					$field_id = $matches[2];
					$custom_field = DAO_CustomField::get($field_id);
					DevblocksEventHelper::renderActionSetCustomField($custom_field, $trigger);
				}
				break;
		}
		
		$tpl->clearAssign('params');
		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('token_labels');
	}
	
	function simulateActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$message_id = $dict->id;
		@$ticket_id = $dict->ticket_id;

		if(empty($message_id) || empty($ticket_id))
			return;
		
		switch($token) {
			case 'add_recipients':
				return DevblocksEventHelper::simulateActionAddRecipients($params, $dict, 'ticket_id');
				break;
				
			case 'create_message_note':
				return DevblocksEventHelper::simulateActionCreateMessageStickyNote($params, $dict, 'id');
				break;

			case 'relay_email':
				return DevblocksEventHelper::simulateActionRelayEmail(
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
				
			case 'remove_recipients':
				return DevblocksEventHelper::simulateActionRemoveRecipients($params, $dict, 'ticket_id');
				break;
				
			case 'schedule_email_recipients':
				break;
				
			case 'set_importance':
				return DevblocksEventHelper::simulateActionSetTicketImportance($params, $dict, 'ticket_id', 'ticket_importance');
				break;
				
			case 'set_org':
				return DevblocksEventHelper::simulateActionSetTicketOrg($params, $dict, 'ticket_id');
				break;
				
			case 'set_owner':
				return DevblocksEventHelper::simulateActionSetTicketOwner($params, $dict, 'ticket_id');
				break;
			
			case 'set_reopen_date':
				DevblocksEventHelper::runActionSetDate('ticket_reopen_date', $params, $dict);
				$out = sprintf(">>> Setting ticket reopen date to:\n".
					"%s (%d)\n",
					date('D M d Y h:ia', $dict->ticket_reopen_date),
					$dict->ticket_reopen_date
				);
				return $out;
				break;
				
			case 'send_email_recipients':
				return DevblocksEventHelper::simulateActionSendEmailRecipients($params, $dict);
				break;
				
			case 'set_spam_training':
				$out = sprintf(">>> Setting spam training:\n".
					"%s\n",
					$params['value'] == 'N' ? 'Not Spam' : 'Spam'
				);
				return $out;
				break;
				
			case 'set_status':
				$out = sprintf(">>> Setting status to:\n%s\n",
					$params['status']
				);
				return $out;
				break;
				
			case 'set_subject':
				$out = sprintf(">>> Setting subject to:\n%s\n",
					$params['value']
				);
				return $out;
				break;
				
			case 'move_to':
				$groups = DAO_Group::getAll();
				$buckets = DAO_Bucket::getAll();

				if(!isset($params['group_id']) || !isset($params['bucket_id']))
					return false;

				$group_id = $params['group_id'];
				$bucket_id = $params['bucket_id'];
				
				if(!isset($groups[$group_id]))
					return false;
				
				if($bucket_id && !isset($buckets[$bucket_id]))
					return false;
				
				$out = sprintf(">>> Moving to:\n%s: %s\n",
					$groups[$group_id]->name,
					$buckets[$bucket_id]->name
				);
				
				return $out;
				break;

				
			default:
				if(preg_match('#set_cf_(.*?_*)custom_([0-9]+)#', $token))
					return DevblocksEventHelper::simulateActionSetCustomField($token, $params, $dict);
				break;
		}
	}
	
	function runActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$message_id = $dict->id;
		@$ticket_id = $dict->ticket_id;

		if(empty($message_id) || empty($ticket_id))
			return;
		
		switch($token) {
			case 'add_recipients':
				DevblocksEventHelper::runActionAddRecipients($params, $dict, 'ticket_id');
				break;
				
			case 'create_message_note':
				DevblocksEventHelper::runActionCreateMessageStickyNote($params, $dict, 'id');
				break;

			case 'relay_email':
				DevblocksEventHelper::runActionRelayEmail(
					$params,
					$dict,
					CerberusContexts::CONTEXT_TICKET,
					$ticket_id,
					$dict->group_id,
					@intval($dict->ticket_bucket_id),
					$dict->id,
					@intval($dict->ticket_owner_id),
					$dict->sender_address,
					$dict->sender_full_name,
					$dict->ticket_subject
				);
				break;
				
			case 'remove_recipients':
				DevblocksEventHelper::runActionRemoveRecipients($params, $dict, 'ticket_id');
				break;
				
			case 'schedule_email_recipients':
				DevblocksEventHelper::runActionScheduleTicketReply($params, $dict, $ticket_id, $message_id);
				break;
				
			case 'set_importance':
				DevblocksEventHelper::runActionSetTicketImportance($params, $dict, 'ticket_id', 'ticket_importance');
				break;
				
			case 'set_org':
				DevblocksEventHelper::runActionSetTicketOrg($params, $dict, 'ticket_id', 'ticket_org_');
				break;
				
			case 'set_owner':
				DevblocksEventHelper::runActionSetTicketOwner($params, $dict, 'ticket_id', 'ticket_owner_');
				$dict->set('ticket_has_owner', !empty($dict->ticket_owner_id) ? 1 : 0);
				break;
				
			case 'send_email_recipients':
				// Translate message tokens
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();

				@$content = $tpl_builder->build($params['content'], $dict);
				@$format = $params['format'];
				@$html_template_id = $params['html_template_id'];

				$properties = array(
					'ticket_id' => $ticket_id,
					'message_id' => $message_id,
					'content' => $content,
					'content_format' => $format,
					'html_template_id' => $html_template_id,
					'worker_id' => 0,
					'forward_files' => array(),
					'link_forward_files' => true,
				);
				
				// Headers
				
				@$headers_list = DevblocksPlatform::parseCrlfString($tpl_builder->build($params['headers'], $dict));

				if(is_array($headers_list))
				foreach($headers_list as $header_line) {
					@list($header, $value) = explode(':', $header_line);
				
					if(!empty($header) && !empty($value))
						$properties['headers'][trim($header)] = trim($value);
				}
				
				// Attachment list variables
		
				if(isset($params['attachment_vars']) && is_array($params['attachment_vars'])) {
					foreach($params['attachment_vars'] as $attachment_var) {
						if(false != ($attachments = $dict->$attachment_var) && is_array($attachments)) {
							foreach($attachments as $attachment) {
								$properties['forward_files'][] = $attachment->id;
							}
						}
					}
				}
				
				// File bundles

				if(isset($params['bundle_ids']) && is_array($params['bundle_ids'])) {
					$bundles = DAO_FileBundle::getIds($params['bundle_ids']);
					foreach($bundles as $bundle) {
						$attachments = $bundle->getAttachments();

						foreach($attachments as $attachment) {
							$properties['forward_files'][] = $attachment->id;
						}
					}
				}

				// Options
				
				if(isset($params['is_autoreply']) && !empty($params['is_autoreply']))
					$properties['is_autoreply'] = true;
				
				// Send
				
				CerberusMail::sendTicketMessage($properties);
				break;

			case 'set_reopen_date':
				DevblocksEventHelper::runActionSetDate('ticket_reopen_date', $params, $dict);
				
				$fields = array(
					DAO_Ticket::REOPEN_AT => intval($dict->ticket_reopen_date),
				);
				DAO_Ticket::update($ticket_id, $fields);
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
							DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_OPEN,
						);
						break;
					case 'waiting':
						$fields = array(
							DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_WAITING,
						);
						break;
					case 'closed':
						$fields = array(
							DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_CLOSED,
						);
						break;
					case 'deleted':
						$fields = array(
							DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_DELETED,
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
				// Translate message tokens
				@$value = $params['value'];
				
				$builder = DevblocksPlatform::services()->templateBuilder();
				$value = $builder->build($value, $dict);
				
				DAO_Ticket::update($ticket_id,array(
					DAO_Ticket::SUBJECT => $value,
				));
				$dict->ticket_subject = $value;
				break;
			
			case 'move_to':
				@$to_group_id = intval($params['group_id']);
				@$current_group_id = intval($dict->ticket_group_id);
				
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
				
				// If the bucket doesn't exist, use the group's default bucket.
				if(empty($to_bucket_id) || !isset($buckets[$to_bucket_id])) {
					$to_group = $groups[$to_group_id];  /* @var $to_group Model_Group */
					$to_bucket = $to_group->getDefaultBucket();
					$to_bucket_id = $to_bucket->id;
				}
				
				// Move
				DAO_Ticket::update($ticket_id, array(
					DAO_Ticket::GROUP_ID => $to_group_id,
					DAO_Ticket::BUCKET_ID => $to_bucket_id,
				));
				
				$dict->ticket_group_id = $to_group_id;
				$dict->ticket_bucket_id = $to_bucket_id;
				break;

			default:
				if(preg_match('#set_cf_(.*?_*)custom_([0-9]+)#', $token)) {
					return DevblocksEventHelper::runActionSetCustomField($token, $params, $dict);
				}
				break;
		}
	}
};
