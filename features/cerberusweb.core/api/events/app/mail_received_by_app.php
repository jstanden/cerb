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

class Event_MailReceivedByApp extends Extension_DevblocksEvent {
	const ID = 'event.mail.received.app';
	
	/**
	 * 
	 * Enter description here ...
	 * @param CerberusParserModel $parser_model
	 */
	static function trigger(&$parser_model) { //, Model_Message $message, Model_Ticket $ticket, Model_Group $group
		$events = DevblocksPlatform::getEventService();
		$events->trigger(
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
	function generateSampleEventModel($parser_model=null) { //, Model_Message $message=null, Model_Ticket $ticket=null, Model_Group $group=null
		$active_worker = CerberusApplication::getActiveWorker();
		
		$replyto = DAO_AddressOutgoing::getDefault();
		
		$parser_message = new CerberusParserMessage();
		$parser_message->headers['to'] = 'customer@example.com';
		$parser_message->headers['from'] = $replyto->email;
		$parser_message->headers['cc'] = 'boss@example.com';
		$parser_message->headers['bcc'] = 'secret@example.com';
		$parser_message->headers['subject'] = 'This is the subject';
		$parser_message->body = "This is the message body\r\nOn more than one line.\r\n";
		
		if(empty($parser_model)) {
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
		
		if(!is_null($parser_model)) {
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
		}
		
		/**
		 * Sender Address
		 */
		
		$sender = !is_null($parser_model) ? $parser_model->getSenderAddressModel() : null;
		$sender_labels = array();
		$sender_values = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_ADDRESS, $sender, $sender_labels, $sender_values, null, true);

		// Fill in some custom values
		//$values['sender_is_worker'] = (!empty($values['worker_id'])) ? 1 : 0;
		
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
	
	function getConditionExtensions() {
		$labels = $this->getLabels();
		
		$labels['attachment_name'] = 'Message attachment name';
		$labels['attachment_mimetype'] = 'Message attachment MIME type';
		$labels['attachment_size'] = 'Message attachment size (MB)';
		$labels['attachment_count'] = 'Message attachment count';
		$labels['header'] = 'Message header';
		$labels['is_new'] = 'Message is new';
		$labels['recipients'] = 'Message recipients';
		
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
			'sender_is_banned' => Model_CustomField::TYPE_CHECKBOX,
			'sender_org_name' => Model_CustomField::TYPE_SINGLE_LINE,
			'sender_org_created' => Model_CustomField::TYPE_DATE,
		
			'attachment_mimetype' => null,
			'attachment_name' => null,
			'attachment_size' => null,
			'attachment_count' => Model_CustomField::TYPE_NUMBER,
			'header' => null,
			'is_new' => null,
			'recipients' => null,
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
			case 'is_new':
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_bool.tpl');
				break;
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
	
	function runConditionExtension($token, $trigger, $params, $values) {
		$pass = true;
		
		switch($token) {
			case 'is_new':
				$bool = $params['bool'];
				@$value = $values['is_new'];
				$pass = ($bool == !empty($value));
				break;
				
			case 'attachment_name':
			case 'attachment_mimetype':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				$attachments = $values['attachments'];
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
				$attachments = $values['attachments'];
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
				@$recipients = $values['recipients'];
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
				
				if(!isset($values['headers'][$header])) {
					$value = '';
				} else {
					$value = $values['headers'][$header];
				}
				
				// Operators
				switch($oper) {
					case 'is':
						$pass = (0==strcasecmp($value,$param_value));
						break;
					case 'like':
						if(isset($values['headers'][$header])) {
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
				'prepend_to_content' => array('label' =>'Prepend text to message content'),
				'replace_content' => array('label' =>'Replace text in message content'),
				'reject' => array('label' =>'Reject delivery of message'),
				'redirect_email' => array('label' =>'Redirect delivery to another email address'),
				'send_email_sender' => array('label' => 'Reply to sender'),
				'set_header' => array('label' => 'Set message header'),
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

			case 'send_email_sender':
				//$tpl->assign('workers', DAO_Worker::getAll());
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_app/action_send_email_sender.tpl');
				break;
				
			case 'set_header':
				$tpl->display('devblocks:cerberusweb.core::events/mail_received_by_app/action_set_header.tpl');
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
		
		switch($token) {
			case 'append_to_content':
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$values['body'] .= "\r\n" . $tpl_builder->build($params['content'], $values);
				break;
				
			case 'prepend_to_content':
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$values['body'] = $tpl_builder->build($params['content'], $values) . "\r\n" . $values['body'];
				break;
				
			case 'replace_content':
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$replace = $tpl_builder->build($params['replace'], $values);
				$with = $tpl_builder->build($params['with'], $values);
				
				if(isset($params['is_regexp']) && !empty($params['is_regexp'])) {
					@$value = preg_replace($replace, $with, $values['body']);
				} else {
					$value = str_replace($replace, $with, $values['body']);
				}
				
				if(!empty($value)) {
					$values['body'] = trim($value,"\r\n");
				}
				break;
				
			case 'reject':
				$values['pre_actions']['reject'] = true;
				break;
			
			case 'redirect_email':
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
   				@$to = $tpl_builder->build($params['to'], $values);

   				@$parser_model = $values['_parser_model'];
   				if(empty($parser_model) || !is_a($parser_model,'CerberusParserModel'))
   					break;
   					
  				CerberusMail::reflect($parser_model, $to);
				break;
				
			case 'send_email_sender':
				// Translate message tokens
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$subject = $tpl_builder->build($params['subject'], $values);
				$body = $tpl_builder->build($params['content'], $values);
				
				@$to = $values['sender_address'];
				
				if(empty($to) || empty($subject) || empty($body))
					break;
				
				// Handle crude reply-as functionality
				$replyto_addresses = DAO_AddressOutgoing::getAll();
				$replyto_address = null;
				@$recipients = $values['recipients'];
				
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
				@$header = strtolower($params['header']);
				
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$value = $tpl_builder->build($params['value'], $values);
				
   				@$parser_model = $values['_parser_model'];
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
				
			default:
				if('set_cf_' == substr($token,0,7)) {
					@$parser_model = $values['_parser_model'];
					
					$field_id = substr($token,7);
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
						default:
							$value = $params['value'];
							break;
					}
					
					$parser_model->getMessage()->custom_fields[$field_id] = $value; 
				}
				break;				
		}
	}
};