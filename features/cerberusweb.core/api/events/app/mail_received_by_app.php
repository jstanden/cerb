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

class Event_MailReceivedByApp extends Extension_DevblocksEvent {
	const ID = 'event.mail.received.app';
	
	/**
	 *
	 * Enter description here ...
	 * @param CerberusParserModel $parser_model
	 */
	static function trigger(&$parser_model) { //, Model_Message $message, Model_Ticket $ticket, Model_Group $group
		$events = DevblocksPlatform::services()->event();
		return $events->trigger(
			new Model_DevblocksEvent(
				self::ID,
				array(
					'parser_model' => &$parser_model,
					'_whisper' => array(
					'cerberusweb.contexts.app' => array(0),
					),
				)
			)
		);
	}

	/**
	 *
	 * @param CerberusParserModel $parser_model
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel(Model_TriggerEvent $trigger, $parser_model=null) { //, Model_Message $message=null, Model_Ticket $ticket=null, Model_Group $group=null
		$replyto = DAO_Address::getDefaultLocalAddress();
		
		$parser_message = new CerberusParserMessage();
		$parser_message->headers['to'] = $replyto->email;
		$parser_message->headers['from'] = 'customer@example.com';
		$parser_message->headers['cc'] = 'boss@example.com';
		$parser_message->headers['bcc'] = 'secret@example.com';
		$parser_message->headers['subject'] = 'This is the subject';
		$parser_message->body = "This is the message body\r\nOn more than one line.\r\n";
		$parser_message->htmlbody = "This is the message body\r\n<i>with</i> <b>some</b> <span style='color:red;'>formatting</span>.\r\n";
		$parser_message->build();
		
		if(empty($parser_model) || !($parser_model instanceof CerberusParserModel)) {
			$parser_model = new CerberusParserModel($parser_message);
		}
		
		return new Model_DevblocksEvent(
			self::ID,
			array(
				'parser_model' => $parser_model,
			)
		);
	}
	
	function setEvent(Model_DevblocksEvent $event_model=null, Model_TriggerEvent $trigger=null) {
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
		
		/**
		 * Parser Message
		 */
		@$parser_model =& $event_model->params['parser_model']; /* @var $parser_model CerberusParserModel */
		
		$prefix = 'Message ';

		$labels['headers'] = $prefix.'headers';
		$values['headers'] = [];
		
		$labels['body'] = $prefix.'body';
		$values['body'] = '';
		
		$labels['body_html'] = $prefix.'body (HTML)';
		$values['body_html'] = '';
		
		$labels['subject'] = $prefix.'subject';
		$values['subject'] = '';
		
		$labels['encoding'] = $prefix.'encoding';
		$values['encoding'] = '';

		if(!empty($parser_model)) {
			$values['_parser_model'] = $parser_model;
			$values['body'] =& $parser_model->getMessage()->body;
			$values['body_html'] =& $parser_model->getMessage()->htmlbody;
			$values['encoding'] =& $parser_model->getMessage()->encoding;
			$values['headers'] =& $parser_model->getHeaders();
			$values['subject'] =& $parser_model->getSubject();
			$values['pre_actions'] =& $parser_model->getPreActions();
			$values['is_new'] = $parser_model->getIsNew();
			$values['recipients'] = $parser_model->getRecipients();
			$values['attachments'] = $parser_model->getMessage()->files;
			$values['attachment_count'] = count($values['attachments']);
			$values['sender_is_worker'] = $parser_model->isSenderWorker();
		}
		
		/**
		 * Sender Address
		 */
		
		$sender = !empty($parser_model) ? $parser_model->getSenderAddressModel() : null;
		
		$sender_labels = array();
		$sender_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_ADDRESS, $sender, $sender_labels, $sender_values, null, true);

		// Merge
		CerberusContexts::merge(
			'sender_',
			'Sender ',
			$sender_labels,
			$sender_values,
			$labels,
			$values
		);
		
		/**
		 * Routing Group
		 */
		
		$group = !empty($parser_model) ? $parser_model->getRoutingGroup() : null;
		
		$group_labels = $group_values = [];
		CerberusContexts::getContext(CerberusContexts::CONTEXT_GROUP, $group, $group_labels, $group_values, '', true);

		// Merge
		CerberusContexts::merge(
			'routing_group_',
			'Routing group ',
			$group_labels,
			$group_values,
			$labels,
			$values
		);
		
		/**
		 * Parent Ticket
		 */
		
		$ticket = !empty($parser_model) ? $parser_model->getTicketModel() : null;
		
		$ticket_labels = array();
		$ticket_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_TICKET, $ticket, $ticket_labels, $ticket_values, '', true);

		// Merge
		CerberusContexts::merge(
			'parent_ticket_',
			'Parent ticket ',
			$ticket_labels,
			$ticket_values,
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
			'behavior_id' => array(
				'label' => 'Behavior',
				'context' => CerberusContexts::CONTEXT_BEHAVIOR,
			),
			'behavior_bot_id' => array(
				'label' => 'Bot',
				'context' => CerberusContexts::CONTEXT_BOT,
			),
			'parent_ticket_id' => array(
				'label' => 'Parent ticket',
				'context' => CerberusContexts::CONTEXT_TICKET,
			),
			'sender_id' => array(
				'label' => 'Sender email',
				'context' => CerberusContexts::CONTEXT_ADDRESS,
			),
			'sender_org_id' => array(
				'label' => 'Sender org',
				'context' => CerberusContexts::CONTEXT_ORG,
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
		
		$labels['attachment_name'] = 'Message attachment name';
		$labels['attachment_mimetype'] = 'Message attachment MIME type';
		$labels['attachment_size'] = 'Message attachment size (MB)';
		$labels['attachment_count'] = 'Message attachment count';
		$labels['header'] = 'Message header';
		$labels['is_new'] = 'Message is new';
		$labels['recipients'] = 'Message recipients';
		$labels['sender_is_worker'] = 'Sender is worker';
		
		$types['subject'] = Model_CustomField::TYPE_SINGLE_LINE;
		$types['body'] = Model_CustomField::TYPE_MULTI_LINE;
		$types['body_html'] = Model_CustomField::TYPE_MULTI_LINE;
		$types['encoding'] = Model_CustomField::TYPE_SINGLE_LINE;
		
		$types['attachment_mimetype'] = null; // Leave this null
		$types['attachment_name'] = null;  // Leave this null
		$types['attachment_size'] = null;  // Leave this null
		$types['attachment_count'] = Model_CustomField::TYPE_NUMBER;
		$types['header'] = null;
		$types['is_new'] = Model_CustomField::TYPE_CHECKBOX;
		$types['recipients'] = null;
		$types['sender_is_worker'] = Model_CustomField::TYPE_CHECKBOX;
		
		$conditions = $this->_importLabelsTypesAsConditions($labels, $types);
		
		return $conditions;
	}
	
	function renderConditionExtension($token, $as_token, $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','condition'.$seq);
		
		switch($as_token) {
			case 'attachment_name':
			case 'attachment_mimetype':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_string.tpl');
				break;
			case 'attachment_size':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_number.tpl');
				break;
			// [TODO] Internalize
			case 'header':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_group/condition_header.tpl');
				break;
			case 'recipients':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_app/condition_recipients.tpl');
				break;
		}

		$tpl->clearAssign('namePrefix');
		$tpl->clearAssign('params');
	}
	
	function runConditionExtension($token, $as_token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$pass = true;
		
		switch($as_token) {
			case 'attachment_name':
			case 'attachment_mimetype':
				@$not = (substr($params['oper'],0,1) == '!');
				@$oper = ltrim($params['oper'],'!');
				@$attachments = $dict->attachments;
				@$param_value = $params['value'];
				
				$found = false;
				
				if(is_array($attachments))
				foreach($attachments as $attachment_name => $attachment) {
					if($found)
						continue;
					
					switch($as_token) {
						case 'attachment_name':
							$value = $attachment_name;
							break;
						case 'attachment_mimetype':
							$value = $attachment->mime_type;
							break;
					}
					
					// Operators
					switch($oper) {
						case 'is':
							$found = (0==strcasecmp($value, $param_value));
							break;
						case 'like':
							$regexp = DevblocksPlatform::strToRegExp($param_value);
							$found = @preg_match($regexp, $value);
							break;
						case 'contains':
							$found = (false !== stripos($value, $param_value)) ? true : false;
							break;
						case 'regexp':
							$found = @preg_match($param_value, $value);
							break;
						default:
							$found = false;
							break;
					}
				}
				
				$pass = ($not) ? !$found : $found;
				break;
				
			case 'attachment_size':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				$attachments = $dict->attachments;
				@$param_value = $params['value'];
				
				$found = false;
				
				foreach($attachments as $attachment_name => $attachment) {
					if($found)
						continue;
						
					$value = round($attachment->file_size/1048576);
					
					// Operators
					switch($oper) {
						case 'is':
							$found = intval($value) == intval($param_value);
							break;
						case 'gt':
							$found = intval($value) > intval($param_value);
							break;
						case 'lt':
							$found = intval($value) < intval($param_value);
							break;
						default:
							$found = false;
							break;
					}
				}
				
				$pass = ($not) ? !$found : $found;
				break;
				
			case 'recipients':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				@$recipients = $dict->recipients;
				@$param_value = $params['value'];

				$pass = false;
				
				if(is_array($recipients))
				foreach($recipients as $recipient) {
					$regexp = DevblocksPlatform::strToRegExp($param_value);
					if(@preg_match($regexp, $recipient)) {
						$pass = true;
						break;
					}
				}
				
				$pass = ($not) ? !$pass : $pass;
				break;
				
			case 'header':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				@$header = DevblocksPlatform::strLower($params['header']);
				@$param_value = $params['value'];

				if(!isset($dict->headers[$header])) {
					$value = '';
				} else {
					$value = $dict->headers[$header];
				}
				
				if(is_array($value))
					$value = implode("\n", $value);
				
				// Operators
				switch($oper) {
					case 'is':
						$pass = (0==strcasecmp($value, $param_value));
						break;
						
					case 'like':
						if(isset($dict->headers[$header])) {
							$regexp = DevblocksPlatform::strToRegExp($param_value);
							$pass = @preg_match($regexp, $value);
						} else {
							$pass = false;
						}
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
				'reject' => [
					'label' =>'Reject delivery of message',
					'notes' => 'This action has no configurable parameters.',
					'params' => [],
				],
				'redirect_email' => [
					'label' =>'Redirect delivery to another email address',
					'notes' => '',
					'params' => [
						'to' => [
							'type' => 'text',
							'required' => true,
							'notes' => 'A comma-separated list of recipient email addresses',
						],
					],
				],
				'remove_attachments' => [ 
					'label' => 'Remove attachments by filename',
					'notes' => '',
					'params' => [
						'match_oper' => [
							'type' => 'text',
							'required' => true,
							'notes' => 'The filename match operator: `is`, `like`, `regexp`',
						],
						'match_value' => [
							'type' => 'text',
							'required' => true,
							'notes' => 'The value to match in the filename',
						],
					],
				],
				'send_email_sender' => [
					'label' => 'Reply to sender',
					'notes' => '',
					'params' => [
						'subject' => [
							'type' => 'text',
							'required' => true,
							'notes' => 'The subject of the message to send',
						],
						'content' => [
							'type' => 'text',
							'required' => true,
							'notes' => 'The body content of the message to send',
						],
					],
				],
				'set_sender_is_banned' => [
					'label' => 'Set sender is banned',
					'deprecated' => true,
				],
				'set_sender_is_defunct' => [
					'label' => 'Set sender is defunct',
					'deprecated' => true,
				],
			]
			+ DevblocksEventHelper::getActionCustomFieldsFromLabels($this->getLabels($trigger))
			;
		
		$ticket_custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TICKET);
		
		foreach($ticket_custom_fields as $cf_id => $cf) {
			$actions['set_cf_ticket_custom_' . $cf_id] = array('label' => 'Set ticket ' . mb_convert_case($cf->name, MB_CASE_LOWER));
		}
		
		return $actions;
	}
	
	function getActionDefaultOn() {
		return null;
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
				$tpl->display('devblocks:cerberusweb.core::events/mail_before_sent_by_group/action_add_content.tpl');
				break;
				
			case 'replace_content':
				$tpl->display('devblocks:cerberusweb.core::events/mail_before_sent_by_group/action_replace_content.tpl');
				break;
				
			case 'reject':
				break;
				
			case 'redirect_email':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_app/action_redirect_email.tpl');
				break;
			
			case 'remove_attachments':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_app/action_remove_attachments.tpl');
				break;
			
			case 'send_email_sender':
				//$tpl->assign('workers', DAO_Worker::getAll());
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_app/action_send_email_sender.tpl');
				break;
				
			case 'set_header':
				$tpl->display('devblocks:cerberusweb.core::events/model/mail/action_set_header.tpl');
				break;
				
			case 'set_sender_is_banned':
			case 'set_sender_is_defunct':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_bool.tpl');
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
		switch($token) {
			case 'append_to_content':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$content = $tpl_builder->build($params['content'], $dict);
				$dict->body = $dict->body . "\r\n" . $content;
				
				$out = sprintf(">>> Prepending text to message content\n".
					"Text:\n%s\n".
					"Message:\n%s\n",
					$content,
					$dict->body
				);
				
				return $out;
				break;
				
			case 'prepend_to_content':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$content = $tpl_builder->build($params['content'], $dict);
				$dict->body = $content . "\r\n" . $dict->body;
				
				$out = sprintf(">>> Prepending text to message content\n".
					"Text:\n%s\n".
					"Message:\n%s\n",
					$content,
					$dict->body
				);
				
				return $out;
				break;
				
			case 'replace_content':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				
				//@$replace = $tpl_builder->build($params['replace'], $dict);
				//@$with = $tpl_builder->build($params['with'], $dict);
				@$replace_mode = $params['replace_mode'];
				
				$out = '';
				
				$before_text = $dict->body;
				$before_html = $dict->body_html;
				
				$this->runActionExtension($token, $trigger, $params, $dict);
				
				if(!$replace_mode || $replace_mode == 'text') {
					$out .= sprintf(">>> Replacing plaintext content\n".
						"Before:\n%s\n\n".
						"After:\n%s\n".
						"\r\n",
						trim($before_text,"\r\n"),
						$dict->body
					);
				}
					
				if(!$replace_mode || $replace_mode == 'html') {
					$out .= sprintf(">>> Replacing HTML content\n".
						"Before:\n%s\n\n".
						"After:\n%s\n".
						"\r\n",
						trim($before_html,"\r\n"),
						$dict->body_html
					);
					
				}
				
				return $out;
				break;
				
			case 'reject':
				$dict->pre_actions['reject'] = true;
				$out = ">>> Rejecting message\n";
				
				return $out;
				break;
			
			case 'redirect_email':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$to = $tpl_builder->build($params['to'], $dict);
			
				$out = sprintf(">>> Redirecting message to:\n%s",
					$to
				);
			
				return $out;
				break;
			
			case 'remove_attachments':
				@$oper = $params['match_oper'];
				@$value = $params['match_value'];
				
				$out = sprintf(">>> Removing attachments where filename %s %s\n",
					$oper,
					$value
				);
				return $out;
				break;
				
			case 'send_email_sender':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$subject = $tpl_builder->build($params['subject'], $dict);
				$content = $tpl_builder->build($params['content'], $dict);
				
				$out = sprintf(">>> Sending message to sender\n".
					"Subject: %s\n".
					"Content: \n%s\n",
					$subject,
					$content
				);
				
				return $out;
				break;
				
			case 'set_header':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				
				$header = $tpl_builder->build($params['header'], $dict);
				$value = $tpl_builder->build($params['value'], $dict);
				
				@$parser_model = $dict->_parser_model;
				if(empty($parser_model) || !is_a($parser_model,'CerberusParserModel'))
					break;
				
				$headers =& $parser_model->getHeaders();

				if(empty($value)) {
					if(isset($headers[$header]))
						unset($headers[$header]);
					
				} else {
					$headers[$header] = $value;
				}
				
				$out = sprintf(">>> Setting header\n".
					"Header: %s\n".
					"Value: %s\n",
					$header,
					$value
				);
				
				return $out;
				break;
				
			case 'set_sender_is_banned':
				return DevblocksEventHelper::simulateActionSetAbstractField('is banned', Model_CustomField::TYPE_CHECKBOX, 'sender_is_banned', $params, $dict);
				break;
				
			case 'set_sender_is_defunct':
				return DevblocksEventHelper::simulateActionSetAbstractField('is defunct', Model_CustomField::TYPE_CHECKBOX, 'sender_is_defunct', $params, $dict);
				break;
				
			default:
				$matches = [];
				if(preg_match('#set_cf_(.*?_*)custom_([0-9]+)#', $token, $matches)) {
					$field_key = $matches[1];
					$field_id = $matches[2];
					$custom_field = DAO_CustomField::get($field_id);
					
					$value_label = [];
					
					switch($custom_field->type) {
						case Model_CustomField::TYPE_LIST:
						case Model_CustomField::TYPE_MULTI_CHECKBOX:
							$value = $params['values'];
							break;
						
						case Model_CustomField::TYPE_WORKER:
							$workers = DAO_Worker::getAll();
							$value = $params['worker_id'];
							@$value_label = $workers[$value]->getName();
							break;
						
						case Model_CustomField::TYPE_DROPDOWN:
						case Model_CustomField::TYPE_CHECKBOX:
							$value = $params['value'];
							break;
						
						case Model_CustomField::TYPE_DATE:
							@$mode = $params['mode'];
							
							switch($mode) {
								case 'calendar':
									@$calendar_id = $params['calendar_id'];
									@$rel_date = $params['calendar_reldate'];
			
									$value = DevblocksEventHelper::getRelativeDateUsingCalendar($calendar_id, $rel_date);
									
									break;
									
								default:
									if(!isset($params['value']))
										return;
									
									$tpl_builder = DevblocksPlatform::services()->templateBuilder();
									$value = $tpl_builder->build($params['value'], $dict);
									break;
							}
			
							$value = is_numeric($value) ? $value : @strtotime($value);
							break;
						
						default:
							$tpl_builder = DevblocksPlatform::services()->templateBuilder();
							$value = $tpl_builder->build($params['value'], $dict);
							break;
					}
					
					if(empty($value_label))
						$value_label = (is_array($value) ? implode(', ', $value) : $value);
					
					// Update the dictionary
					
					$field_id_key = $field_key . 'id';
					$field_custom_key = $field_key . 'custom';
					$field_value_key = $field_key . 'custom_' . $field_id;
					$dict->$field_value_key = is_array($value) ? implode(', ', $value) : $value;
					
					if(!isset($dict->$field_custom_key))
						$dict->$field_custom_key = [];
					
					$custom =& $dict->$field_custom_key;
					$custom[$field_id] = $value;
					
					// Update the model
					
					@$parser_model = $dict->_parser_model;
					
					if(!empty($parser_model))
						$parser_model->getMessage()->custom_fields[] = [
							'field_id' => $field_id,
							'context' => $custom_field->context,
							'context_id' => $dict->$field_id_key,
							'value' => $value,
						];
						
					// Output
					
					$out = sprintf(">>> Setting custom field\n".
						"Custom Field: %s\n".
						"Value: %s\n",
						$custom_field->name,
						$value_label
					);
					return $out;
				}
				break;
		}
	}
	
	function runActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		switch($token) {
			case 'append_to_content':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$dict->body .= "\r\n" . $tpl_builder->build($params['content'], $dict);
				break;
				
			case 'prepend_to_content':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$dict->body = $tpl_builder->build($params['content'], $dict) . "\r\n" . $dict->body;
				break;
				
			case 'replace_content':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				
				$replace = $tpl_builder->build($params['replace'], $dict);
				$replace_mode = $tpl_builder->build($params['replace_mode'], $dict);
				$with = $tpl_builder->build($params['with'], $dict);
				
				if(!$replace_mode || $replace_mode == 'text') {
					if(isset($params['is_regexp']) && !empty($params['is_regexp'])) {
						@$value = preg_replace($replace, $with, $dict->body);
					} else {
						$value = str_replace($replace, $with, $dict->body);
					}
					
					if($value) {
						$dict->body = trim($value,"\r\n");
					}
				}
				
				if(!$replace_mode || $replace_mode == 'html') {
					if(isset($params['is_regexp']) && !empty($params['is_regexp'])) {
						@$value = preg_replace($replace, $with, $dict->body_html);
					} else {
						$value = str_replace($replace, $with, $dict->body_html);
					}
					
					if($value) {
						$dict->body_html = trim($value,"\r\n");
					}
				}
				break;
				
			case 'reject':
				$dict->pre_actions['reject'] = true;
				break;
				
			case 'redirect_email':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				@$to = $tpl_builder->build($params['to'], $dict);
				
				@$parser_model = $dict->_parser_model;
				if(empty($parser_model) || !is_a($parser_model,'CerberusParserModel'))
					break;
				
				CerberusMail::reflect($parser_model, $to);
				break;
				
			case 'remove_attachments':
				@$oper = $params['match_oper'];
				@$value = $params['match_value'];
				
				if(empty($oper) || empty($value))
					break;
				
				if(!isset($dict->pre_actions['attachment_filters'])) {
					$dict->pre_actions['attachment_filters'] = array();
				}
				
				$dict->pre_actions['attachment_filters'][] = array('oper' => $oper, 'value' => $value);
				break;
				
			case 'send_email_sender':
				// Translate message tokens
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				$subject = $tpl_builder->build($params['subject'], $dict);
				$body = $tpl_builder->build($params['content'], $dict);
				
				@$to = $dict->sender_address;
				
				if(empty($to) || empty($subject) || empty($body))
					break;
				
				// Handle crude reply-as functionality
				$replyto_addresses = DAO_Address::getLocalAddresses();
				$replyto_address = null;
				@$recipients = $dict->recipients;
				
				if(!empty($recipients))
				foreach($recipients as $recipient) {
					if(!is_null($replyto_address))
						continue;
					
					foreach($replyto_addresses as $reply_to) { /* @var $reply_to Model_Address */
						if(!is_null($replyto_address))
							continue;
						
						if($reply_to->email == $recipient) {
							$replyto_address = $reply_to;
							break;
						}
					}
				}
				
				if(is_null($replyto_address))
					$replyto_address = DAO_Address::getDefaultLocalAddress();
				
				CerberusMail::quickSend($to, $subject, $body, $replyto_address->email, '');
				break;
				
			case 'set_header':
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				
				$header = DevblocksPlatform::strLower($tpl_builder->build($params['header'], $dict));
				$value = $tpl_builder->build($params['value'], $dict);
				
				@$parser_model = $dict->_parser_model;
				if(empty($parser_model) || !is_a($parser_model,'CerberusParserModel'))
					break;
				
				$headers =& $parser_model->getHeaders();

				if(empty($value)) {
					if(isset($headers[$header]))
						unset($headers[$header]);
					
				} else {
					$headers[$header] = $value;
				}
				
				// Record which headers we've changed
				
				if(!isset($dict->pre_actions['headers_dirty']))
					$dict->pre_actions['headers_dirty'] = [];
				
				$dict->pre_actions['headers_dirty'][$header] = true;
				
				// Rebuild the model when a header changes
				
				switch($header) {
					case 'in-reply-to':
					case 'references':
					case 'subject':
						$parser_model->updateThreadHeaders();
						break;
					
					case 'from':
						$parser_model->updateSender();
						break;
				}
				
				break;
				
			case 'set_sender_is_banned':
				@$address_id = $dict->sender_id;
				
				if(empty($address_id))
					break;
				
				@$value = $params['value'];
				@$bit = !empty($value) ? 1 : 0;
				
				DAO_Address::update($address_id, array(
					DAO_Address::IS_BANNED => $bit,
				));
				$dict->is_banned = $bit;
				break;
				
			case 'set_sender_is_defunct':
				@$address_id = $dict->sender_id;
				
				if(empty($address_id))
					break;
				
				@$value = $params['value'];
				@$bit = !empty($value) ? 1 : 0;
				
				DAO_Address::update($address_id, array(
					DAO_Address::IS_DEFUNCT => $bit,
				));
				$dict->is_defunct = $bit;
				break;
				
			default:
				$matches = [];
				if(preg_match('#set_cf_(.*?_*)custom_([0-9]+)#', $token, $matches)) {
					$field_key = $matches[1];
					$field_id = $matches[2];
					$custom_field = DAO_CustomField::get($field_id);
					
					switch($custom_field->type) {
						case Model_CustomField::TYPE_LIST:
						case Model_CustomField::TYPE_MULTI_CHECKBOX:
							$value = $params['values'];
							break;
							
						case Model_CustomField::TYPE_WORKER:
							$value = $params['worker_id'];
							break;
							
						case Model_CustomField::TYPE_DROPDOWN:
						case Model_CustomField::TYPE_CHECKBOX:
							$value = $params['value'];
							break;
						
						case Model_CustomField::TYPE_DATE:
							@$mode = $params['mode'];
							
							switch($mode) {
								case 'calendar':
									@$calendar_id = $params['calendar_id'];
									@$rel_date = $params['calendar_reldate'];
			
									$value = DevblocksEventHelper::getRelativeDateUsingCalendar($calendar_id, $rel_date);
									break;
									
								default:
									if(!isset($params['value']))
										return;
									
									$tpl_builder = DevblocksPlatform::services()->templateBuilder();
									$value = $tpl_builder->build($params['value'], $dict);
									break;
							}
			
							$value = is_numeric($value) ? $value : @strtotime($value);
							break;
						
						default:
							$tpl_builder = DevblocksPlatform::services()->templateBuilder();
							$value = $tpl_builder->build($params['value'], $dict);
							break;
					}
					
					// Update the dictionary
					
					$field_id_key = $field_key . 'id';
					$field_custom_key = $field_key . 'custom';
					$field_value_key = $field_key . 'custom_' . $field_id;
					$dict->$field_value_key = is_array($value) ? implode(', ', $value) : $value;
					
					if(!isset($dict->$field_custom_key))
						$dict->$field_custom_key = [];
					
					$custom =& $dict->$field_custom_key;
					$custom[$field_id] = $value;
					
					// Update the model
					
					@$parser_model = $dict->_parser_model;
					
					if(!empty($parser_model))
						$parser_model->getMessage()->custom_fields[] = [
							'field_id' => $field_id,
							'context' => $custom_field->context,
							'context_id' => $dict->$field_id_key,
							'value' => $value,
						];
				}
				break;
		}
	}
};