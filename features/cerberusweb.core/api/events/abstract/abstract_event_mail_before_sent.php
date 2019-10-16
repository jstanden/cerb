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

abstract class AbstractEvent_MailBeforeSent extends Extension_DevblocksEvent {
	protected $_event_id = null; // override
	
	/**
	 *
	 * @param array $properties
	 * @param Model_Message $message
	 * @param Model_Ticket $ticket
	 * @param Model_Group $group
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel(Model_TriggerEvent $trigger, $properties=null, $message_id=null, $ticket_id=null, $group_id=null) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$message_id = 0;
		$ticket_id = 0;
		$group_id = 0;
		$bucket_id = 0;
		
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
			$ticket_id = $result[SearchFields_Ticket::TICKET_ID];
			$group_id = $result[SearchFields_Ticket::TICKET_GROUP_ID];
			$bucket_id = $result[SearchFields_Ticket::TICKET_BUCKET_ID];
		}
		
		$properties = [
			'to' => 'customer@example.com',
			'cc' => 'boss@example.com',
			'bcc' => 'secret@example.com',
			'subject' => 'This is the subject',
			'outgoing_message_id' => '<abcdefg.012345678@example.mail>',
			'ticket_reopen' => "+2 hours",
			'status_id' => Model_Ticket::STATUS_WAITING,
			'content' => "This is the message body\r\nOn more than one line.\r\n",
			'content_format' => 0,
			'headers' => [],
			'group_id' => $group_id,
			'bucket_id' => $bucket_id,
			'worker_id' => $active_worker->id,
		];
		
		return new Model_DevblocksEvent(
			$this->_event_id,
			array(
				'properties' => $properties,
				'message_id' => $message_id,
				'ticket_id' => $ticket_id,
				'group_id' => $group_id,
			)
		);
	}
	
	function setEvent(Model_DevblocksEvent $event_model=null, Model_TriggerEvent $trigger=null) {
		$labels = [];
		$values = [];
		
		/**
		 * Behavior
		 */
		
		$merge_labels = [];
		$merge_values = [];
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
		 * Properties
		 */
		
		@$properties =& $event_model->params['properties'];
		$values['_properties'] =& $properties;
		
		$prefix = 'Sent message ';
		
		$labels['content'] = $prefix.'content';
		$values['content'] =& $properties['content'];
		
		$labels['content_format'] = $prefix.'content is HTML';
		$values['content_format'] = (@$properties['content_format'] == 'parsedown') ? 1 : 0;
		
		if(!isset($properties['headers']))
			$properties['headers'] = [];
			
		$labels['headers'] = $prefix.'headers';
		$values['headers'] =& $properties['headers'];
		
		$labels['to'] = $prefix.'to';
		$values['to'] =& $properties['to'];
		
		$labels['cc'] = $prefix.'cc';
		$values['cc'] =& $properties['cc'];
		
		$labels['bcc'] = $prefix.'bcc';
		$values['bcc'] =& $properties['bcc'];

		$labels['subject'] = $prefix.'subject';
		$values['subject'] =& $properties['subject'];
		
		$labels['message_id'] = $prefix.'ID';
		$values['message_id'] =& $properties['outgoing_message_id'];
		
		$values['waiting_until'] =& $properties['ticket_reopen'];
		
		$values['status_id'] =& $properties['status_id'];
		
		//$labels['worker_id'] = $prefix.'worker id';
		$values['worker_id'] =& $properties['worker_id'];
		
		// Ticket custom fields
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TICKET, true, true);
		
		foreach($custom_fields as $custom_field) {
			$labels['custom_' . $custom_field->id] = $prefix.'custom field ' . DevblocksPlatform::strLower($custom_field->name);
			
			if(
				array_key_exists('custom_fields', $properties)
				&& is_array($properties['custom_fields'])
				&& array_key_exists($custom_field->id, $properties['custom_fields'])
			) {
				$values['custom_' . $custom_field->id] = $properties['custom_fields'][$custom_field->id];
			}
		}
		
		/**
		 * Ticket
		 */

		@$ticket_id = $event_model->params['ticket_id'];
		
		$ticket_labels = [];
		$ticket_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_TICKET, $ticket_id, $ticket_labels, $ticket_values, null, true);
		
			// Fill some custom values

			// Clear dupe content
			CerberusContexts::scrubTokensWithRegexp(
				$ticket_labels,
				$ticket_values,
				[
					"#^group_#",
					//"#^id$#",
				]
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
		@$group_id = $properties['group_id'] ?: $event_model->params['group_id'];
		$group_labels = [];
		$group_values = [];
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
		 * Worker
		 */
		@$worker_id = $properties['worker_id'];
		$worker_labels = [];
		$worker_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $worker_id, $worker_labels, $worker_values, '', true);
				
			// Clear dupe content
			CerberusContexts::scrubTokensWithRegexp(
				$worker_labels,
				$worker_values,
				[
					"#^address_org_#",
				]
			);
		
			// Merge
			CerberusContexts::merge(
				'worker_',
				'Worker ',
				$worker_labels,
				$worker_values,
				$labels,
				$values
			);

		/**
		 * Signatures
		 */
		
		$labels['group_sig'] = 'Group signature';
		if(!empty($group_id)) {
			if(null != ($group = DAO_Group::get($group_id))) {
				if(null != ($worker = DAO_Worker::get($worker_id))) {
					$sig_bucket_id = isset($ticket_values['bucket_id']) ? $ticket_values['bucket_id'] : 0;
					$values['group_sig'] = $group->getReplySignature($sig_bucket_id, $worker, false);
				}
			}
		}
		
		$labels['group_sig_html'] = 'Group signature HTML';
		if(!empty($group_id)) {
			if(null != ($group = DAO_Group::get($group_id))) {
				if(null != ($worker = DAO_Worker::get($worker_id))) {
					$sig_bucket_id = isset($ticket_values['bucket_id']) ? $ticket_values['bucket_id'] : 0;
					$values['group_sig'] = $group->getReplySignature($sig_bucket_id, $worker, true);
				}
			}
		}
		
		/**
		 * Return
		 */

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
			'worker_id' => array(
				'label' => 'Worker',
				'context' => CerberusContexts::CONTEXT_WORKER,
			),
			'worker_email_id' => array(
				'label' => 'Worker email',
				'context' => CerberusContexts::CONTEXT_ADDRESS,
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
		
		$labels['ticket_org_watcher_count'] = 'Ticket org watcher count';
		$labels['ticket_watcher_count'] = 'Ticket watcher count';
		
		$types['bcc'] = Model_CustomField::TYPE_SINGLE_LINE;
		$types['cc'] = Model_CustomField::TYPE_SINGLE_LINE;
		$types['content'] = Model_CustomField::TYPE_MULTI_LINE;
		$types['content_format'] = Model_CustomField::TYPE_CHECKBOX;
		$types['subject'] = Model_CustomField::TYPE_SINGLE_LINE;
		$types['message_id'] = Model_CustomField::TYPE_SINGLE_LINE;
		$types['to'] = Model_CustomField::TYPE_SINGLE_LINE;
	
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
			case 'ticket_spam_score':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_spam_score.tpl');
				break;
			case 'ticket_spam_training':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_spam_training.tpl');
				break;
			case 'ticket_status':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_status.tpl');
				break;
			case 'ticket_org_watcher_count':
			case 'ticket_watcher_count':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_number.tpl');
				break;
		}

		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('params');
	}
	
	function runConditionExtension($token, $as_token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$pass = true;
		
		switch($as_token) {
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
				
			case 'ticket_org_watcher_count':
			case 'ticket_watcher_count':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				
				switch($as_token) {
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
				'append_to_content' => [
					'label' => 'Append text to message content',
					'notes' => '',
					'params' => [
						'content' => [
							'type' => 'text',
							'required' => true,
							'notes' => 'The content to append to the message body',
						],
						'mode' => [
							'type' => 'text',
							'notes' => '`sent` (only sent message), `saved` (only saved message), or omit for both',
						],
					],
				],
				'prepend_to_content' => [
					'label' => 'Prepend text to message content',
					'notes' => '',
					'params' => [
						'content' => [
							'type' => 'text',
							'required' => true,
							'notes' => 'The content to prepend to the message body',
						],
						'mode' => [
							'type' => 'text',
							'notes' => '`sent` (only sent message), `saved` (only saved message), or omit for both',
						],
					],
				],
				'replace_content' => [
					'label' => 'Replace text in message content',
					'notes' => '',
					'params' => [
						'is_regexp' => [
							'type' => 'bit',
							'notes' => '`0` (plaintext match), `1` (regular expression match)',
						],
						'replace_mode' => [
							'type' => 'text',
							'notes' => '`text`, `html`, or omit for both',
						],
						'replace' => [
							'type' => 'text',
							'required' => true,
							'notes' => 'The content to match in the message body',
						],
						'with' => [
							'type' => 'text',
							'required' => true,
							'notes' => 'The new content to replace the match with',
						],
					],
				],
				'set_header' => [
					'label' => 'Set message header',
					'notes' => '',
					'params' => [
						'header' => [
							'type' => 'text',
							'required' => true,
							'notes' => 'The email header to set',
						],
						'value' => [
							'type' => 'text',
							'required' => true,
							'notes' => 'The value of the email header',
						],
					],
				],
			]
			+ DevblocksEventHelper::getActionCustomFieldsFromLabels($this->getLabels($trigger))
			;
		
		return $actions;
	}
	
	function getActionDefaultOn() {
		return 'ticket_id';
	}
	
	function renderActionExtension($token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','action'.$seq);

		$labels = $this->getLabels($trigger);
		$tpl->assign('token_labels', $labels);
			
		switch($token) {
			case 'append_to_content':
			case 'prepend_to_content':
				$tpl->assign('is_sent', true);
				$tpl->display('devblocks:cerberusweb.core::events/mail_before_sent_by_group/action_add_content.tpl');
				break;
				
			case 'replace_content':
				$tpl->display('devblocks:cerberusweb.core::events/mail_before_sent_by_group/action_replace_content.tpl');
				break;
				
			case 'set_header':
				$tpl->display('devblocks:cerberusweb.core::events/model/mail/action_set_header.tpl');
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
		@$ticket_id = $dict->ticket_id;

		switch($token) {
			case 'append_to_content':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				@$mode = $params['mode'];
				$content = $tpl_builder->build($params['content'], $dict);
				
				if(!isset($dict->_properties['content_appends']))
					$dict->_properties['content_appends'] = [
						'sent' => [],
						'saved' => [],
					];
					
				$label = '';
				
				switch($mode) {
					case 'saved':
					case 'sent':
						$label = $mode . ' ';
						$dict->_properties['content_appends'][$mode][] = $content;
						break;
						
					default:
						$dict->_properties['content_appends']['saved'][] = $content;
						$dict->__properties['content_appends']['sent'][] = $content;
						break;
				}
				
				$out = sprintf(">>> Appending text to %smessage content\n".
					"%s\n",
					$label,
					$content
				);
				
				return $out;
				break;
				
			case 'prepend_to_content':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				@$mode = $params['mode'];
				$content = $tpl_builder->build($params['content'], $dict);
				
				if(!isset($dict->_properties['content_prepends']))
					$dict->_properties['content_prepends'] = [
						'sent' => [],
						'saved' => [],
					];
					
				$label = '';
				
				switch($mode) {
					case 'saved':
					case 'sent':
						$label = $mode . ' ';
						$dict->_properties['content_prepends'][$mode][] = $content;
						break;
						
					default:
						$dict->_properties['content_prepends']['saved'][] = $content;
						$dict->_properties['content_prepends']['sent'][] = $content;
						break;
				}
				
				$out = sprintf(">>> Prepending text to %smessage content\n".
					"%s\n",
					$label,
					$content
				);
				
				return $out;
				break;
				
			case 'replace_content':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$replace = $tpl_builder->build($params['replace'], $dict);
				$with = $tpl_builder->build($params['with'], $dict);
				
				if(isset($params['is_regexp']) && !empty($params['is_regexp'])) {
					@$value = preg_replace($replace, $with, $dict->content);
				} else {
					$value = str_replace($replace, $with, $dict->content);
				}
				
				$before = $dict->content;
				
				if(!empty($value)) {
					$dict->content = trim($value,"\r\n");
				}
				
				$out = sprintf(">>> Replacing content\n".
					"Before:\n%s\n".
					"After:\n%s\n",
					$before,
					$dict->content
				);
				
				return $out;
				break;
				
			case 'set_header':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				
				$header = $tpl_builder->build($params['header'], $dict);
				$value = $tpl_builder->build($params['value'], $dict);
				
				$headers =& $dict->headers;
				
				if(!isset($headers))
					$headers = array();
				
				$header_string = sprintf("%s: %s", $header, $value);
				
				if(empty($value)) {
					if(isset($headers[$header]))
						unset($headers[$header]);
					
				} else {
					$headers[$header] = $value;
					
				}
				
				$out = sprintf(">>> Setting header\n%s\n",
					$header_string
				);
				
				return $out;
				break;
		}

		if(empty($ticket_id))
			return;
		
		switch($token) {
			default:
				if(preg_match('#set_cf_(.*?_*)custom_([0-9]+)#', $token))
					return DevblocksEventHelper::simulateActionSetCustomField($token, $params, $dict);
				break;
		}
	}
	
	function runActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$ticket_id = $dict->ticket_id;
		
		switch($token) {
			case 'append_to_content':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				@$mode = $params['mode'];
				@$content = $tpl_builder->build($params['content'], $dict);
				
				if(!isset($dict->_properties['content_appends']))
					$dict->_properties['content_appends'] = [
						'sent' => [],
						'saved' => [],
					];
					
				switch($mode) {
					case 'saved':
					case 'sent':
						$dict->_properties['content_appends'][$mode][] = $content;
						break;
						
					default:
						$dict->_properties['content_appends']['saved'][] = $content;
						$dict->_properties['content_appends']['sent'][] = $content;
						break;
				}
				break;
				
			case 'prepend_to_content':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				@$mode = $params['mode'];
				@$content = $tpl_builder->build($params['content'], $dict);
				
				if(!isset($dict->_properties['content_prepends']))
					$dict->_properties['content_prepends'] = [
						'sent' => [],
						'saved' => [],
					];
					
				switch($mode) {
					case 'saved':
					case 'sent':
						$dict->_properties['content_prepends'][$mode][] = $content;
						break;
						
					default:
						$dict->_properties['content_prepends']['saved'][] = $content;
						$dict->_properties['content_prepends']['sent'][] = $content;
						break;
				}
				break;
				
			case 'replace_content':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$replace = $tpl_builder->build($params['replace'], $dict);
				$with = $tpl_builder->build($params['with'], $dict);
				
				if(isset($params['is_regexp']) && !empty($params['is_regexp'])) {
					@$value = preg_replace($replace, $with, $dict->content);
				} else {
					$value = str_replace($replace, $with, $dict->content);
				}
				
				if(!empty($value)) {
					$dict->content = trim($value,"\r\n");
				}
				break;
				
			case 'set_header':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				
				$header = $tpl_builder->build($params['header'], $dict);
				$value = $tpl_builder->build($params['value'], $dict);
				
				$headers =& $dict->headers;
				
				if(!isset($headers))
					$headers = [];
				
				if(empty($value)) {
					if(isset($headers[$header]))
						unset($headers[$header]);
					
				} else {
					$headers[$header] = $value;
					
				}
				break;
		}

		if(empty($ticket_id))
			return;
		
		switch($token) {
			default:
				if(preg_match('#set_cf_(.*?_*)custom_([0-9]+)#', $token))
					return DevblocksEventHelper::runActionSetCustomField($token, $params, $dict);
				break;
		}
	}
};