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

class Event_MailReceivedByApp extends Extension_DevblocksEvent {
	const ID = 'event.mail.received.app';
	
	/**
	 *
	 * Enter description here ...
	 * @param CerberusParserModel $parser_model
	 */
	static function trigger(&$parser_model) { //, Model_Message $message, Model_Ticket $ticket, Model_Group $group
		$events = DevblocksPlatform::getEventService();
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
		$active_worker = CerberusApplication::getActiveWorker();
		
		$replyto = DAO_AddressOutgoing::getDefault();
		
		$parser_message = new CerberusParserMessage();
		$parser_message->headers['to'] = $replyto->email;
		$parser_message->headers['from'] = 'customer@example.com';
		$parser_message->headers['cc'] = 'boss@example.com';
		$parser_message->headers['bcc'] = 'secret@example.com';
		$parser_message->headers['subject'] = 'This is the subject';
		$parser_message->body = "This is the message body\r\nOn more than one line.\r\n";
		
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
	
	function setEvent(Model_DevblocksEvent $event_model=null) {
		$labels = array();
		$values = array();
		
		/**
		 * Parser Message
		 */
		@$parser_model =& $event_model->params['parser_model']; /* @var $parser_model CerberusParserModel */
		
		$prefix = 'Message ';

		$values['headers'] = array();
		
		$labels['body'] = $prefix.'body';
		$values['body'] = '';
		
		$labels['subject'] = $prefix.'subject';
		$values['subject'] = '';
		
		$labels['encoding'] = $prefix.'encoding';
		$values['encoding'] = '';

		if(!empty($parser_model)) {
			$values['_parser_model'] = $parser_model;
			$values['body'] =& $parser_model->getMessage()->body;
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
		 * Return
		 */

		$this->setLabels($labels);
		$this->setValues($values);
	}
	
	function getValuesContexts($trigger) {
		$vals = array(
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
		asort($vals_to_ctx);
		
		return $vals_to_ctx;
	}
	
	function getConditionExtensions() {
		$labels = $this->getLabels();
		
		$labels['attachment_name'] = 'Message attachment name';
		$labels['attachment_mimetype'] = 'Message attachment MIME type';
		$labels['attachment_size'] = 'Message attachment size (MB)';
		$labels['attachment_count'] = 'Message attachment count';
		$labels['header'] = 'Message header';
		$labels['is_new'] = 'Message is new';
		$labels['recipients'] = 'Message recipients';
		$labels['sender_is_worker'] = 'Sender is worker';
		
		$types = array(
			'subject' => Model_CustomField::TYPE_SINGLE_LINE,
			'body' => Model_CustomField::TYPE_MULTI_LINE,
			'encoding' => Model_CustomField::TYPE_SINGLE_LINE,
		
			'sender_full_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_address' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_first_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_last_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_num_spam' => Model_CustomField::TYPE_NUMBER,
			'sender_num_nonspam' => Model_CustomField::TYPE_NUMBER,
			'sender_is_defunct' => Model_CustomField::TYPE_CHECKBOX,
			'sender_is_banned' => Model_CustomField::TYPE_CHECKBOX,
			'sender_org_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_org_created' => Model_CustomField::TYPE_DATE,
		
			'attachment_mimetype' => null,
			'attachment_name' => null,
			'attachment_size' => null,
			'attachment_count' => Model_CustomField::TYPE_NUMBER,
			'header' => null,
			'is_new' => Model_CustomField::TYPE_CHECKBOX,
			'recipients' => null,
			'sender_is_worker' => Model_CustomField::TYPE_CHECKBOX,
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
			case 'attachment_mimetype':
			case 'attachment_name':
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
	
	function runConditionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$pass = true;
		
		switch($token) {
			case 'attachment_name':
			case 'attachment_mimetype':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				$attachments = $dict->attachments;
				@$param_value = $params['value'];
				
				$found = false;
				
				foreach($attachments as $attachment_name => $attachment) {
					if($found)
						continue;
						
					switch($token) {
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
				@$header = strtolower($params['header']);
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
	
	function getActionExtensions() {
		$actions =
			array(
				'append_to_content' => array('label' =>'Append text to message content'),
				'create_notification' => array('label' =>'Create a notification'),
				'prepend_to_content' => array('label' =>'Prepend text to message content'),
				'replace_content' => array('label' =>'Replace text in message content'),
				'reject' => array('label' =>'Reject delivery of message'),
				'redirect_email' => array('label' =>'Redirect delivery to another email address'),
				'remove_attachments' => array('label' => 'Remove attachments by filename'),
				'send_email' => array('label' => 'Send email'),
				'send_email_sender' => array('label' => 'Reply to sender'),
				'set_header' => array('label' => 'Set message header'),
				'set_sender_is_banned' => array('label' => 'Set sender is banned'),
				'set_sender_is_defunct' => array('label' => 'Set sender is defunct'),
			)
			+ DevblocksEventHelper::getActionCustomFieldsFromLabels($this->getLabels())
			;
		
		$ticket_custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TICKET);
		
		foreach($ticket_custom_fields as $cf_id => $cf) {
			$actions['set_cf_ticket_custom_' . $cf_id] = array('label' => 'Set ticket ' . mb_convert_case($cf->name, MB_CASE_LOWER));
		}
		
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
			case 'append_to_content':
			case 'prepend_to_content':
				$tpl->display('devblocks:cerberusweb.core::events/mail_before_sent_by_group/action_add_content.tpl');
				break;
				
			case 'create_notification':
				$translate = DevblocksPlatform::getTranslationService();
				DevblocksEventHelper::renderActionCreateNotification($trigger);
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
			
			case 'send_email':
				DevblocksEventHelper::renderActionSendEmail($trigger);
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
				if(preg_match('#set_cf_(.*?)_custom_([0-9]+)#', $token, $matches)) {
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
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
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
				
			case 'create_notification':
				return DevblocksEventHelper::simulateActionCreateNotification($params, $dict);
				break;
				
			case 'prepend_to_content':
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
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
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$replace = $tpl_builder->build($params['replace'], $dict);
				$with = $tpl_builder->build($params['with'], $dict);
				
				if(isset($params['is_regexp']) && !empty($params['is_regexp'])) {
					@$value = preg_replace($replace, $with, $dict->body);
				} else {
					$value = str_replace($replace, $with, $dict->body);
				}
				
				$before = $dict->body;
				
				if(!empty($value)) {
					$dict->body = trim($value,"\r\n");
				}
				
				$out = sprintf(">>> Replacing content\n".
					"Before:\n%s\n".
					"After:\n%s\n",
					$before,
					$dict->body
				);
				
				return $out;
				break;
				
			case 'reject':
				$dict->pre_actions['reject'] = true;
				$out = ">>> Rejecting message\n";
				
				return $out;
				break;
			
			case 'redirect_email':
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
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
				
			case 'send_email':
				return DevblocksEventHelper::simulateActionSendEmail($params, $dict);
				break;
				
			case 'send_email_sender':
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
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
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				
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
				if(preg_match('#set_cf_(.*?)_custom_([0-9]+)#', $token, $matches)) {
					$field_key = $matches[1];
					$field_id = $matches[2];
					$custom_field = DAO_CustomField::get($field_id);
					
					$value_label = array();
					
					switch($custom_field->type) {
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
						case Model_CustomField::TYPE_DATE:
							$value = $params['value'];
							break;
						default:
							$tpl_builder = DevblocksPlatform::getTemplateBuilder();
							$value = $tpl_builder->build($params['value'], $dict);
							break;
					}

					if(empty($value_label))
						$value_label = (is_array($value) ? implode(', ', $value) : $value);
					
					$field_context_id = null;
					
					switch($custom_field->context) {
						case CerberusContexts::CONTEXT_TICKET:
							break;
							
						default:
							$field_id_key = $field_key . '_id';
							$field_context_id = $dict->$field_id_key;
							break;
					}
					
					if(!empty($parser_model))
						$parser_model->getMessage()->custom_fields[] = array(
							'field_id' => $field_id,
							'value' => $value,
						);
					
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
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$dict->body .= "\r\n" . $tpl_builder->build($params['content'], $dict);
				break;
				
			case 'create_notification':
				DevblocksEventHelper::runActionCreateNotification($params, $dict);
				break;
				
			case 'prepend_to_content':
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$dict->body = $tpl_builder->build($params['content'], $dict) . "\r\n" . $dict->body;
				break;
				
			case 'replace_content':
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$replace = $tpl_builder->build($params['replace'], $dict);
				$with = $tpl_builder->build($params['with'], $dict);
				
				if(isset($params['is_regexp']) && !empty($params['is_regexp'])) {
					@$value = preg_replace($replace, $with, $dict->body);
				} else {
					$value = str_replace($replace, $with, $dict->body);
				}
				
				if(!empty($value)) {
					$dict->body = trim($value,"\r\n");
				}
				break;
				
			case 'reject':
				$dict->pre_actions['reject'] = true;
				break;
			
			case 'redirect_email':
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
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
				
			case 'send_email':
				return DevblocksEventHelper::runActionSendEmail($params, $dict);
				break;

			case 'send_email_sender':
				// Translate message tokens
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$subject = $tpl_builder->build($params['subject'], $dict);
				$body = $tpl_builder->build($params['content'], $dict);
				
				@$to = $dict->sender_address;
				
				if(empty($to) || empty($subject) || empty($body))
					break;
				
				// Handle crude reply-as functionality
				$replyto_addresses = DAO_AddressOutgoing::getAll();
				$replyto_address = null;
				@$recipients = $dict->recipients;
				
				if(!empty($recipients))
				foreach($recipients as $recipient) {
					if(!is_null($replyto_address))
						continue;
					
					foreach($replyto_addresses as $reply_to) { /* @var $reply_to Model_AddressOutgoing */
						if(!is_null($replyto_address))
							continue;
						
						if($reply_to->email == $recipient) {
							$replyto_address = $reply_to;
							break;
						}
					}
				}
				
				if(is_null($replyto_address))
					$replyto_address = DAO_AddressOutgoing::getDefault();
				
				CerberusMail::quickSend($to, $subject, $body, $replyto_address->email, $replyto_address->getReplyPersonal());
				break;
				
			case 'set_header':
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				
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
				if(preg_match('#set_cf_(.*?)_custom_([0-9]+)#', $token, $matches)) {
					@$parser_model = $dict->_parser_model;
					
					$field_key = $matches[1];
					$field_id = $matches[2];
					$custom_field = DAO_CustomField::get($field_id);
					$context = null;
					$context_id = null;
					
					switch($custom_field->type) {
						case Model_CustomField::TYPE_MULTI_CHECKBOX:
							$value = $params['values'];
							break;
						case Model_CustomField::TYPE_WORKER:
							$value = $params['worker_id'];
							break;
						case Model_CustomField::TYPE_DROPDOWN:
						case Model_CustomField::TYPE_CHECKBOX:
						case Model_CustomField::TYPE_DATE:
							$value = $params['value'];
							break;
						default:
							$tpl_builder = DevblocksPlatform::getTemplateBuilder();
							$value = $tpl_builder->build($params['value'], $dict);
							break;
					}
					
					$field_context_id = null;
					
					switch($custom_field->context) {
						case CerberusContexts::CONTEXT_TICKET:
							break;
							
						default:
							$field_id_key = $field_key . '_id';
							$field_context_id = $dict->$field_id_key;
							break;
					}
					
					$parser_model->getMessage()->custom_fields[] = array(
						'field_id' => $field_id,
						'context' => $custom_field->context,
						'context_id' => $field_context_id,
						'value' => $value,
					);
				}
				break;
		}
	}
};