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

// [TODO] Can we extend a generic message event instead of being redundant?
class Event_MailReceivedByWatcher extends Extension_DevblocksEvent {
	const ID = 'event.mail.received.watcher';
	
	static function trigger($message_id, $worker_id) {
		$events = DevblocksPlatform::services()->event();
		return $events->trigger(
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
	function generateSampleEventModel(Model_TriggerEvent $trigger, $message_id=null, $worker_id=null) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(empty($message_id)) {
			// Pull the latest ticket
			list($results) = DAO_Ticket::search(
				array(),
				array(
					new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_STATUS_ID,'in',array(Model_Ticket::STATUS_OPEN, Model_Ticket::STATUS_WAITING)),
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
	
	function setEvent(Model_DevblocksEvent $event_model=null, Model_TriggerEvent $trigger=null) {
		@$message_id = $event_model->params['message_id'];
		@$worker_id = $event_model->params['worker_id'];
		
		$labels = array();
		$values = array();
		
		/**
		 * Behavior
		 */
		
		$merge_labels = array();
		$merge_values = array();
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
			
			$values['ticket_has_owner'] = !empty($ticket_values['owner_id']) ? 1 : 0;

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
			'group_id' => array(
				'label' => 'Group',
				'context' => CerberusContexts::CONTEXT_GROUP,
			),
			'sender_id' => array(
				'label' => 'Sender email',
				'context' => CerberusContexts::CONTEXT_ADDRESS,
			),
			'sender_watchers' => array(
				'label' => 'Sender watchers',
				'context' => CerberusContexts::CONTEXT_WORKER,
				'is_multiple' => true,
			),
			'sender_org_id' => array(
				'label' => 'Sender org',
				'context' => CerberusContexts::CONTEXT_ORG,
			),
			'sender_org_watchers' => array(
				'label' => 'Sender org_watchers',
				'context' => CerberusContexts::CONTEXT_WORKER,
				'is_multiple' => true,
			),
			'ticket_bucket_id' => array(
				'label' => 'Ticket bucket',
				'context' => CerberusContexts::CONTEXT_BUCKET,
			),
			'ticket_id' => array(
				'label' => 'Ticket',
				'context' => CerberusContexts::CONTEXT_TICKET,
			),
			'ticket_org_id' => array(
				'label' => 'Ticket org',
				'context' => CerberusContexts::CONTEXT_ORG,
			),
			'ticket_org_watchers' => array(
				'label' => 'Ticket org watchers',
				'context' => CerberusContexts::CONTEXT_WORKER,
				'is_multiple' => true,
			),
			'ticket_owner_id' => array(
				'label' => 'Ticket owner',
				'context' => CerberusContexts::CONTEXT_WORKER,
			),
			'ticket_watchers' => array(
				'label' => 'Ticket watchers',
				'context' => CerberusContexts::CONTEXT_WORKER,
				'is_multiple' => true,
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
		$labels['sender_is_me'] = 'Message sender is me';
		
		$labels['ticket_has_owner'] = 'Ticket has owner';
		
		$labels['group_id'] = 'Group';
		$labels['group_and_bucket'] = 'Group and bucket';
		
		$labels['sender_org_watcher_count'] = 'Message sender watcher count';
		$labels['sender_watcher_count'] = 'Message sender org watcher count';
		$labels['ticket_org_watcher_count'] = 'Ticket org watcher count';
		$labels['ticket_watcher_count'] = 'Ticket watcher count';
		
		$types['is_first'] = Model_CustomField::TYPE_CHECKBOX;
		$types['sender_is_worker'] = Model_CustomField::TYPE_CHECKBOX;
		$types['sender_is_me'] = Model_CustomField::TYPE_CHECKBOX;
		
		$types['ticket_has_owner'] = Model_CustomField::TYPE_CHECKBOX;
		
		$types['group_id'] = null;
		$types['group_and_bucket'] = null;
		
		$types['sender_org_watcher_count'] = null;
		$types['sender_watcher_count'] = null;
		$types['ticket_org_watcher_count'] = null;
		$types['ticket_watcher_count'] = null;
		
		$conditions = $this->_importLabelsTypesAsConditions($labels, $types);
		
		return $conditions;
	}

	function renderConditionExtension($token, $as_token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','condition'.$seq);
		
		switch($as_token) {
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
				
			case 'headers':
			case 'ticket_initial_message_headers':
			case 'ticket_initial_response_message_headers':
			case 'ticket_latest_message_headers':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_header.tpl');
				break;
				
			case 'sender_org_watcher_count':
			case 'sender_watcher_count':
			case 'ticket_org_watcher_count':
			case 'ticket_watcher_count':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_number.tpl');
				break;
				
		}
		return;
	}

	function runConditionExtension($token, $as_token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$pass = true;
		
		switch($as_token) {
			case 'group_id':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				
				@$in_group_ids = $params['group_id'];
				@$group_id = intval($dict->group_id);
				
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
				
			case 'headers':
			case 'ticket_initial_response_message_headers':
			case 'ticket_initial_message_headers':
			case 'ticket_latest_message_headers':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				@$header = rtrim(DevblocksPlatform::strLower($params['header']), ':');
				@$param_value = $params['value'];
				
				// Lazy load
				$header_values = $dict->$token;
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
		$actions = array(
			'add_watchers' => array('label' =>'Add watchers'),
			//'set_spam_training' => array('label' => 'Set spam training'),
			//'set_status' => array('label' => 'Set status'),
			'send_email' => array('label' => 'Send email'),
			'relay_email' => array('label' => 'Relay to external email'),
			'send_email_recipients' => array('label' => 'Reply to recipients'),
			'create_comment' => array('label' =>'Create comment'),
			'create_notification' => array('label' =>'Create notification'),
			'create_task' => array('label' =>'Create task'),
			'create_ticket' => array('label' =>'Create ticket'),
		);
		
		// [TODO] Add set custom fields
		
		return $actions;
	}
	
	function renderActionExtension($token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
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
				
			case 'relay_email':
				if(false == ($va = $trigger->getBot()))
					break;
				
				// Filter to trigger owner
				DevblocksEventHelper::renderActionRelayEmail(
					array($va->owner_context_id),
					array('workers'),
					'content'
				);
				break;
				
			case 'send_email':
				$placeholders = [
					'ticket_bucket_replyto_id,group_replyto_id' => 'Ticket Bucket',
				];
				
				DevblocksEventHelper::renderActionSendEmail($trigger, $placeholders);
				break;
				
			case 'send_email_recipients':
				$tpl->assign('workers', DAO_Worker::getAll());
				
				$html_templates = DAO_MailHtmlTemplate::getAll();
				$tpl->assign('html_templates', $html_templates);
				
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_owner/action_send_email_recipients.tpl');
				break;
				
//			default:
//				if('set_cf_' == substr($token,0,7)) {
//					$field_id = substr($token,7);
//					$custom_field = DAO_CustomField::get($field_id);
//					DevblocksEventHelper::renderActionSetCustomField($custom_field, $trigger);
//				}
//				break;
		}
		
		$tpl->clearAssign('params');
		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('token_labels');
	}
	
	function simulateActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$ticket_id = $dict->ticket_id;
		@$message_id = $dict->id;

		if(empty($ticket_id) || empty($message_id))
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
				break;
				
			case 'send_email':
				return DevblocksEventHelper::simulateActionSendEmail($params, $dict);
				break;
				
			case 'send_email_recipients':
				return DevblocksEventHelper::simulateActionSendEmailRecipients($params, $dict);
				break;
				
		}
	}
	
	function runActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$ticket_id = $dict->ticket_id;
		@$message_id = $dict->id;

		if(empty($ticket_id) || empty($message_id))
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
					$dict->group_id,
					@intval($dict->ticket_bucket_id),
					$message_id,
					@intval($dict->ticket_owner_id),
					$dict->sender_address,
					$dict->sender_full_name,
					$dict->ticket_subject
				);
				break;
				
			case 'send_email':
				DevblocksEventHelper::runActionSendEmail($params, $dict);
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

				// Attachments
				
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

				// Send
				
				CerberusMail::sendTicketMessage($properties);
				break;
		}
	}
};