<?php

use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mime\Exception\RfcComplianceException;

class Exception_DevblocksEmailDeliveryError extends Exception_Devblocks {}

class Model_DevblocksOutboundEmail {
	private string $_type = '';
	private string $_mask = '';
	private array $_properties = [];
	private array $_results = [];
	
	private ?Model_Bucket $_bucket_model = null;
	private ?Model_Group $_group_model = null;
	private ?Model_Message $_message_model = null;
	private ?Model_Ticket $_ticket_model = null;
	private ?Model_Worker $_worker_model = null;
	
	function __construct(string $type, array $properties=[]) {
		$this->_type = $type;
		$this->setProperties($properties);
	}
	
	function clearCache() : void {
		$this->_bucket_model = null;
		$this->_group_model = null;
		$this->_message_model = null;
		$this->_ticket_model = null;
		$this->_worker_model = null;
	}
	
	public function getType() : string {
		return $this->_type;
	}
	
	public function getProperties() : array {
		return $this->_properties;
	}
	
	public function setProperties(array $new_properties) : void {
		foreach($this->_properties as $k => $v) {
			if(!array_key_exists($k, $new_properties)) {
				unset($this->_properties[$k]);
			}
		}
		
		foreach($new_properties as $k => $v) {
			$this->setProperty($k, $v);
		}
	}
	
	public function getProperty(string $key, mixed $default=null) : mixed {
		if(!array_key_exists($key, $this->_properties))
			return $default;
		
		return $this->_properties[$key];
	}
	
	public function setProperty(string $key, mixed $value) : void {
		if ($value === ($this->_properties[$key] ?? null))
			return;
		
		// Clear cached models on change
		if('bucket_id' == $key) {
			$this->_bucket_model = null;
		} else if('group_id' == $key) {
			$this->_group_model = null;
		} else if('message_id' == $key) {
			$this->_message_model = null;
		} else if('ticket_id' == $key) {
			$this->_ticket_model = null;
		} else if('worker_id' == $key) {
			$this->_worker_model = null;
		}
		
		$this->_properties[$key] = $value;
	}
	
	public function getResult(string $key, mixed $default=null) : mixed {
		if(!array_key_exists($key, $this->_results))
			return $default;
		
		return $this->_results[$key];
	}
	
	public function setResult(string $key, mixed $value) : void {
		$this->_results[$key] = $value;
	}
	
	public function getGroup() : ?Model_Group {
		if(null !== $this->_group_model)
			return $this->_group_model;
		
		$group = DAO_Group::get($this->getProperty('group_id', 0));
		$bucket_id = $this->getProperty('bucket_id');
		
		// If we have a bucket but no group
		if(!$group && $bucket_id) {
			if(($bucket = DAO_Bucket::get($bucket_id))) {
				$this->setProperty('group_id', $bucket->group_id);
				if(($group = DAO_Group::get($bucket->group_id))) {
					$this->_group_model = $group;
					return $this->_group_model;
				}
			}
		}
		
		// If we still don't have a group, use default
		if(!$group && ($group = DAO_Group::getDefaultGroup())) {
			$this->setProperty('group_id', $group->id);
			$this->_group_model = $group;
			
			if(($bucket = $group->getDefaultBucket() ?? null)) {
				$this->setProperty('bucket_id', $bucket->id);
				$this->_bucket_model = $bucket;
			}
			
			return $this->_group_model;
		}
		
		$this->_group_model = $group;
		return $this->_group_model;
	}
	
	public function getBucket() : ?Model_Bucket {
		if(null !== $this->_bucket_model)
			return $this->_bucket_model;
		
		$bucket = DAO_Bucket::get($this->getProperty('bucket_id'));
		$group_id = $this->getProperty('group_id');
		
		// If we have a bucket/group mismatch
		if($bucket && $bucket->group_id != $group_id) {
			$this->setProperty('group_id', $bucket->group_id);
			$this->_bucket_model = $bucket;
			return $this->_bucket_model;
		}

		// If we have a group and no bucket
		if(!$bucket && $group_id) {
			if(($group = DAO_Group::get($group_id))) {
				$bucket = $group->getDefaultBucket();
				$this->setProperty('bucket_id', $bucket->id);
				$this->_bucket_model = $bucket;
				return $this->_bucket_model;
			}
		}
		
		// If we still have no bucket, use the default
		if(!$bucket) {
			if(($group = DAO_Group::getDefaultGroup())) {
				$bucket = $group->getDefaultBucket();
				$this->setProperty('bucket_id', $bucket->id);
				$this->setProperty('group_id', $bucket->group_id);
				$this->_bucket_model = $bucket;
				return $this->_bucket_model;
			}
		}
		
		$this->_bucket_model = $bucket;
		return $this->_bucket_model;
	}
	
	public function getWorker() : ?Model_Worker {
		if(null !== $this->_worker_model)
			return $this->_worker_model;
		
		$worker_id = $this->getProperty('worker_id', 0);
		
		if($worker_id && ($worker = DAO_Worker::get($worker_id))) {
			$this->_worker_model = $worker;
			return $this->_worker_model;
		}
		
		return null;
	}
	
	public function triggerComposeBehaviors() {
		$group_id = $this->getGroup()->id ?? 0;
		
		// Changing the outgoing message through a VA (global)
		Event_MailBeforeSent::trigger($this->_properties, null, null, $group_id);
		
		// Changing the outgoing message through a VA (group)
		Event_MailBeforeSentByGroup::trigger($this->_properties, null, null, $group_id);
	}
	
	public function parseComposeHashCommands() : void {
		if(($worker = $this->getWorker())) {
			$hash_commands = [];
			CerberusMail::parseComposeHashCommands($worker, $this->_properties, $hash_commands);
			$this->setResult('hash_commands', $hash_commands);
		}
	}
	
	public function runComposeHashCommands(array $commands) : void {
		$ticket = $this->getTicket();
		$message = $this->getMessage();
		$worker = $this->getWorker();
		
		foreach($commands as $command_data) {
			switch($command_data['command']) {
				case 'comment':
					$comment = $command_data['args'] ?? null;
					
					if($comment && $ticket && $worker) {
						$fields = [
							DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_TICKET,
							DAO_Comment::CONTEXT_ID => $ticket->id,
							DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
							DAO_Comment::OWNER_CONTEXT_ID => $worker->id,
							DAO_Comment::CREATED => time()+2,
							DAO_Comment::COMMENT => $comment,
						];
						$comment_id = DAO_Comment::create($fields);
						DAO_Comment::onUpdateByActor($worker, $fields, $comment_id);
					}
					break;
				
				case 'note':
					$comment = $command_data['args'] ?? null;
					
					if($comment && $message && $worker) {
						$fields = [
							DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_MESSAGE,
							DAO_Comment::CONTEXT_ID => $message->id,
							DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
							DAO_Comment::OWNER_CONTEXT_ID => $worker->id,
							DAO_Comment::CREATED => time(),
							DAO_Comment::COMMENT => $comment,
						];
						$comment_id = DAO_Comment::create($fields);
						DAO_Comment::onUpdateByActor($worker, $fields, $comment_id);
					}
					break;
				
				case 'watch':
					if($ticket)
						CerberusContexts::addWatchers(CerberusContexts::CONTEXT_TICKET, $ticket->id, [$worker->id]);
					break;
				
				case 'unwatch':
					if($ticket)
						CerberusContexts::removeWatchers(CerberusContexts::CONTEXT_TICKET, $ticket->id, [$worker->id]);
					break;
			}
		}
	}
	
	public function triggerReplyBehaviors($message_id, $ticket_id, $group_id) : array {
		$results = [];
		
		// Changing the outgoing message through a VA (global)
		$runners = Event_MailBeforeSent::trigger($this->_properties, $message_id, $ticket_id, $group_id);
		$results = array_merge(
			$results,
			!is_array($runners) ? [] : $runners,
		);
		
		// Changing the outgoing message through a VA (group)
		$runners = Event_MailBeforeSentByGroup::trigger($this->_properties, $message_id, $ticket_id, $group_id);
		return array_merge(
			$results,
			!is_array($runners) ? [] : $runners,
		);
	}
	
	private function isSensitive() : bool {
		return $this->getProperty('is_sensitive', 0);
	}
	
	public function isDeliverable() : bool {
		return(
			// Has recipients
			!empty($this->getTo())
			// The 'dont_send' property isn't set
			&& !$this->getProperty('dont_send')
		);
	}
	
	public function getFutureDeliveryTime() : int|false {
		$send_at = $this->getProperty('send_at', 0);
		
		if($send_at) {
			if(!is_numeric($send_at))
				$send_at = strtotime($send_at);
			
			if(false !== $send_at) {
				if ($send_at >= time())
					return $send_at;
			}
		}
		
		return false;
	}
	
	public function saveQueuedDraft(int $send_at, $is_a_failure=false) : int {
		$draft_id = $this->getProperty('draft_id');
		
		// If we're not resuming a draft from the UI, generate a draft
		if (!($draft = DAO_MailQueue::get($draft_id))) {
			$change_fields = DAO_MailQueue::getFieldsFromMessageProperties($this->getProperties(), $this->getType());
			$change_fields[DAO_MailQueue::TYPE] = $this->getType();
			$change_fields[DAO_MailQueue::IS_QUEUED] = 1;
			$change_fields[DAO_MailQueue::QUEUE_FAILS] = $is_a_failure ? 1 : 0;
			$change_fields[DAO_MailQueue::QUEUE_DELIVERY_DATE] = $send_at;
			
			$draft_id = DAO_MailQueue::create($change_fields);
			
			if(($forward_files = $this->getProperty('forward_files')))
				DAO_Attachment::addLinks(CerberusContexts::CONTEXT_DRAFT, $draft_id, $forward_files);
			
			$this->setProperty('draft_id', $draft_id);
			
		} else {
			// If we're saving due to failure, increment the counters
			if($is_a_failure) {
				if($draft->queue_fails < 10) {
					$fields = [
						DAO_MailQueue::IS_QUEUED => 1,
						DAO_MailQueue::QUEUE_FAILS => ++$draft->queue_fails,
						DAO_MailQueue::QUEUE_DELIVERY_DATE => $send_at,
					];
				} else {
					$fields = [
						DAO_MailQueue::IS_QUEUED => 0,
						DAO_MailQueue::QUEUE_DELIVERY_DATE => 0,
					];
				}
				DAO_MailQueue::update($draft_id, $fields);
			
			} else {
				$draft->params['send_at'] = date('r', $send_at);
				
				$draft_fields = [
					DAO_MailQueue::IS_QUEUED => 1,
					DAO_MailQueue::QUEUE_FAILS => 0,
					DAO_MailQueue::QUEUE_DELIVERY_DATE => $send_at,
					DAO_MailQueue::PARAMS_JSON => json_encode($draft->params),
				];
				
				DAO_MailQueue::update($draft->id, $draft_fields);
			}
		}
		
		return $draft_id;
	}
	
	public function getOrgId() : int {
		if(($org_id = $this->getProperty('org_id')))
			return $org_id;

		$to_list = $this->getTo();
		
		// Organization ID from first requester
		reset($to_list);
		
		if(null != ($first_req = DAO_Address::lookupAddress(key($to_list) ?? ''))) {
			if(!empty($first_req->contact_org_id))
				return $first_req->contact_org_id;
		}
		
		return 0;
	}
	
	public function getTicket() : ?Model_Ticket {
		if(!is_null($this->_ticket_model))
			return $this->_ticket_model;
		
		$ticket = DAO_Ticket::get($this->getProperty('ticket_id'));
		$message_id = $this->getProperty('message_id');

		// Default from the parent message_id
		if (!$ticket && $message_id) {
			$ticket = DAO_Ticket::getTicketByMessageId($message_id);
		}
		
		$this->_ticket_model = $ticket;
		return $this->_ticket_model;
	}
	
	public function getTicketMask() : string {
		if(empty($this->_mask)) {
			// If an existing ticket
			if(($ticket = $this->getTicket())) {
				$this->_mask = $ticket->mask;
			} else {
				// Otherwise generate a new mask
				$this->_mask = CerberusApplication::generateTicketMask();
			}
		}
		
		return $this->_mask;
	}
	
	public function getMessage() : ?Model_Message {
		if(!is_null($this->_message_model))
			return $this->_message_model;
		
		$message_id = $this->getProperty('message_id');
		
		if (!$message_id) {
			// Default to the last message from the parent ticket_id
			if(($ticket = $this->getTicket())) {
				$message_id = $ticket->last_message_id;
			} else {
				return null;
			}
		}
		
		$this->_message_model = DAO_Message::get($message_id);
		return $this->_message_model;
	}
	
	public function getTo() : array {
		return CerberusMail::parseRfcAddresses($this->getProperty('to')) ?: [];
	}
	
	public function getCc() : array {
		return CerberusMail::parseRfcAddresses($this->getProperty('cc')) ?: [];
	}
	
	public function getBcc() : array {
		return CerberusMail::parseRfcAddresses($this->getProperty('bcc')) ?: [];
	}
	
	public function getFromAddressModel() : ?Model_Address {
		if(Model_MailQueue::TYPE_TRANSACTIONAL == $this->getType()) {
			// If we have an explicit `from` try that
			if (($from = $this->getProperty('from'))) {
				// If this is a legitimate email address, and it has a mail transport
				if (($from_model = DAO_Address::getByEmail($from)) && $from_model->mail_transport_id)
					return $from_model;
			}
			
			// Otherwise use the default sender
			if(($default_sender = DAO_Address::getDefaultLocalAddress())) {
				$this->setProperty('from', $default_sender->email);
				return $default_sender;
			}
		}
		
		if(null == ($bucket = $this->getBucket()))
			return null;
		
		if(null == ($group = $bucket->getGroup()))
			return null;
		
		return $group->getReplyTo($bucket->id);
	}
	
	public function getFromPersonal() : ?string {
		if(Model_MailQueue::TYPE_TRANSACTIONAL == $this->getType()) {
			return $this->getProperty('from_personal');
		}
		
		$worker = $this->getWorker();
		$group = $this->getGroup();
		$bucket = $this->getBucket();
		return $group->getReplyPersonal($bucket->id, $worker);
	}
	
	public function getSubject() {
		$subject = $this->getProperty('subject') ?: '(no subject)';
		
		if($this->getProperty('is_forward'))
			return $subject;
		
		$group_id = $this->getProperty('group_id');
		$mask = $this->getTicketMask();
		
		// add mask to subject if group setting calls for it
		$group = DAO_Group::get($group_id);
		$group_has_subject = $group ? intval($group->subject_has_mask) : 0;
		$group_subject_prefix = $group ? $group->subject_prefix : '';
		
		$prefix = sprintf("[%s#%s] ",
			!empty($group_subject_prefix) ? ($group_subject_prefix.' ') : '',
			$mask
		);
		
		// Prefix 'Re:' to the subject if not exists and the conversation has messages (i.e. this isn't the first)
		$do_prefix_re = false;
		if($this->getType() == Model_MailQueue::TYPE_TICKET_REPLY) {
			$has_re_already = str_contains($subject, 'Re:') || str_contains($subject, 're:');
			$has_messages = intval($this->getTicket()?->num_messages) > 0;
			$do_prefix_re = !$has_re_already && $has_messages;
		}
		
		return (sprintf('%s%s%s',
			$do_prefix_re ? 'Re: ' : '',
			$group_has_subject ? $prefix : '',
			$subject
		));
	}
	
	private function _generateBodyContentWithPartModifications(string $part, string $format) : string {
		$content = $this->_properties['content'] ?? '';
		
		// Apply content modifications in FIFO order
		if(($content_modifications = ($this->_properties['content_modifications'] ?? null)))
			foreach($content_modifications as $content_modification) {
				$action = $content_modification['action'] ?? null;
				$params = $content_modification['params'] ?? null;
				
				$params_on = $params['on'] ?? [];
				
				$on = [
					'html' => true,
					'text' => true,
					'saved' => true,
					'sent' => true,
				];
				
				if(is_array($params_on))
					foreach($params_on as $on_key => $is_on) {
						if(array_key_exists($on_key, $on) && !$is_on)
							$on[$on_key] = false;
					}
				
				// Backwards compatibility for bot behaviors. Remove in 11.0
				// @deprecated ==========
				if(array_key_exists('replace_on', $params)) {
					$params['mode'] = $params['replace_on'];
					unset($params['replace_on']);
				}
				
				if(array_key_exists('mode', $params)) {
					if ('saved' == $params['mode']) {
						$on['saved'] = true;
						$on['sent'] = false;
					} elseif ('sent' == $params['mode']) {
						$on['saved'] = false;
						$on['sent'] = true;
					}
				}
				// ==========@deprecated
				
				// If this part or format is disabled, skip it
				if(!$on[$part] || !$on[$format])
					continue;
				
				// Replacement
				if($action == 'replace') {
					$replace = $params['replace'] ?? null;
					$replace_is_regexp = $params['replace_is_regexp'] ?? null;
					$with = $params['with'] ?? null;
					
					if($replace_is_regexp) {
						$content = preg_replace($replace, $with, $content);
					} else {
						$content = str_replace($replace, $with, $content);
					}
					
					// Prepends
				} elseif ($action == 'prepend') {
					$prepend_content = $params['content'] ?? null;
					
					$content = $prepend_content . "\n" . $content;
					
					// Appends
				} elseif ($action == 'append') {
					$append_content = $params['content'] ?? null;
					
					$content = $content . "\n" . $append_content;
				}
			}
		
		return $content;
	}
	
	// Strip some Markdown in the plaintext version
	private function _generateTextFromMarkdown($markdown) : string {
		$plaintext = null;
		
		$url_writer = DevblocksPlatform::services()->url();
		$base_url = $url_writer->write('c=files', true) . '/';
		
		// Fix references to internal files
		try {
			$plaintext = preg_replace_callback(
				sprintf('|(\!\[(.*?)\]\(%s(.*?)\))|', preg_quote($base_url)),
				function($matches) use ($base_url) {
					if(4 == count($matches)) {
						list($file_id, $file_name) = array_pad(explode('/', $matches[3], 2), 2, null);
						
						$file_name = urldecode($file_name);
						
						if($file_id && $file_name) {
							$inline_text = $file_name . ($matches[2] ? (' ' . $matches[2]) : '');
							return sprintf("[%s]", $inline_text);
						}
					}
					
					return $matches[0];
				},
				$markdown
			);
			
		} catch (Exception $e) {
			error_log($e->getMessage());
		}
		
		// Images
		try {
			$plaintext = preg_replace_callback(
				'|(\!\[(.*?)\]\((.*?)\))|',
				function($matches) {
					if(4 == count($matches)) {
						$inline_text = $matches[3] . ($matches[2] ? (' ' . $matches[2]) : '');
						return sprintf("[%s]", $inline_text);
					}
					
					return $matches[0];
				},
				$plaintext
			);
			
		} catch (Exception $e) {
			error_log($e->getMessage());
		}
		
		return $plaintext;
	}
	
	public function getBodyTemplateFromContent(string $part, string $format='text') : string {
		$output = $this->_generateBodyContentWithPartModifications($part, $format);
		
		$output = strtr($output, "\r", '');
		
		if('html' == $format) {
			$output = preg_replace('/^#signature$/m', addcslashes($this->_properties['signature_html'] ?? '', '\\$'), $output);
			$output = preg_replace('/^#original_message$/m', addcslashes($this->_properties['original_message_html'] ?? '', '\\$'), $output);
			return DevblocksPlatform::parseMarkdown($output);
			
		} else {
			$output = preg_replace('/^#signature$/m', addcslashes($this->_properties['signature'] ?? '', '\\$'), $output);
			$output = preg_replace('/^#original_message$/m', addcslashes($this->_properties['original_message'] ?? '', '\\$'), $output);
			return $this->_generateTextFromMarkdown($output);
		}
	}
	
	public function isBodyFormatted() : bool {
		return in_array($this->getProperty('content_format'), ['markdown','parsedown']);
	}
	
	public function getBodyTextSaved() : string {
		return $this->_getBodyText('saved');
	}
	
	public function getBodyTextSent() : string {
		return $this->_getBodyText('sent');
	}
	
	private function _getBodyText(string $part) : string {
		return $this->getBodyTemplateFromContent($part, 'text');
	}
	
	public function getBodyHtmlSaved(&$embedded_files=[]) : string {
		return $this->_getBodyHtml('saved', $embedded_files);
	}
	
	public function getBodyHtmlSent(&$embedded_files=[]) : string {
		return $this->_getBodyHtml('sent', $embedded_files);
	}
	
	private function _getBodyHtml(string $part, &$embedded_files=[]) : string {
		$group = $this->getGroup();
		$bucket_id = $this->getProperty('bucket_id', 0);
		$html_template_id = $this->getProperty('html_template_id', 0);
		
		// Replace signatures for each part
		if(!($html_body = $this->getBodyTemplateFromContent($part, 'html')))
			return '';
		
		// We only use HTML templates on the sent version
		if('sent' == $part) {
			// Determine if we have an HTML template
			if (!$html_template_id || !($html_template = DAO_MailHtmlTemplate::get($html_template_id))) {
				if (!$group || !$bucket_id || !($html_template = $group->getReplyHtmlTemplate($bucket_id)))
					$html_template = null;
			}
			
			// Use an HTML template wrapper if we have one
			if ($html_template instanceof Model_MailHtmlTemplate) {
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				
				$values = [
					'message_body' => $html_body,
				];
				
				if ($this->getProperty('outgoing_message_id')) {
					$values['message_id_header'] = $this->getProperty('outgoing_message_id', '');
				}
				
				if ($this->getProperty('token')) {
					$values['message_token'] = $this->getProperty('token', '');
				}
				
				if ($this->getProperty('bucket_id')) {
					$values['bucket__context'] = CerberusContexts::CONTEXT_BUCKET;
					$values['bucket_id'] = $this->getProperty('bucket_id', 0);
				}
				
				if ($this->getProperty('group_id')) {
					$values['group__context'] = CerberusContexts::CONTEXT_GROUP;
					$values['group_id'] = $this->getProperty('group_id', 0);
				}
				
				if ($this->getProperty('ticket_id')) {
					$values['ticket__context'] = CerberusContexts::CONTEXT_TICKET;
					$values['ticket_id'] = $this->getProperty('ticket_id', 0);
				}
				
				if ($this->getProperty('worker_id')) {
					$values['worker__context'] = CerberusContexts::CONTEXT_WORKER;
					$values['worker_id'] = $this->getProperty('worker_id', 0);
				}
				
				$html_body = $tpl_builder->build(
					$html_template->content,
					$values
				);
			}
			
			// Purify the HTML and inline the CSS
			$html_body = DevblocksPlatform::purifyHTML($html_body, true, true);
		}
			
		// Replace links with cid: in HTML part
		try {
			$url_writer = DevblocksPlatform::services()->url();
			$base_url = $url_writer->write('c=files', true) . '/';
			
			$html_body = preg_replace_callback(
				sprintf('|(\"%s(.*?)\")|', preg_quote($base_url)),
				function ($matches) use ($base_url, &$embedded_files) {
					if (3 == count($matches)) {
						list($file_id, $file_name) = array_pad(explode('/', $matches[2], 2), 2, null);
						if ($file_id && $file_name) {
							$cid = 'cid:' . sha1(random_bytes(32)) . '@cerb';
							$embedded_files[$cid] = intval($file_id);
							return sprintf('"%s"', $cid);
						}
					}
					
					return $matches[0];
				},
				$html_body
			);
			
		} catch (Exception $e) {
			DevblocksPlatform::logException($e);
		}
		
		return $html_body;
	}
	
	public function send(?string &$error=null) : bool {
		$mail_service = DevblocksPlatform::services()->mail();
		
		// Attempt to deliver the message before continuing
		try {
			// Is this a future draft?
			if(($send_at = $this->getFutureDeliveryTime())) {
				$this->saveQueuedDraft($send_at);
				return false;
			}
			
			// Attempt to deliver via transport extension
			if($this->isDeliverable()) {
				if(!$mail_service->send($this)) {
					if($mail_service->getLastErrorMessage()) {
						throw new Exception_DevblocksEmailDeliveryError('Mail failed to send: ' . $mail_service->getLastErrorMessage());
					} else {
						throw new Exception_DevblocksEmailDeliveryError('Mail failed to send: unknown reason');
					}
				}
			}
			
			return true;
			
		} catch (Throwable $e) {
			DevblocksPlatform::logException($e);
			
			if(!$this->isSensitive())
				$draft_id = $this->saveQueuedDraft(time() + 300, true);
			
			if(!($last_error_message = $mail_service->getLastErrorMessage())) {
				if($e instanceof TransportException) {
					$last_error_message = $e->getMessage();
				} elseif($e instanceof RfcComplianceException) {
					$last_error_message = $e->getMessage();
				} elseif($e instanceof Exception_DevblocksEmailDeliveryError) {
					$last_error_message = $e->getMessage();
				}
			}
			
			if($last_error_message)
				$error = $last_error_message;
			
			// If we have an error message, log it on the draft
			if(isset($draft_id) && $draft_id && $last_error_message) {
				$fields = [
					DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_APPLICATION,
					DAO_Comment::OWNER_CONTEXT_ID => 0,
					DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_DRAFT,
					DAO_Comment::CONTEXT_ID => $draft_id,
					DAO_Comment::COMMENT => 'Error sending message: ' . $last_error_message,
					DAO_Comment::CREATED => time(),
				];
				DAO_Comment::create($fields);
			}
			
			return false;
		}
	}
	
	public function getHeadersText() : string {
		try {
			$smtp_model = CerberusMail::getSmtpMessageFromModel($this, true);
			
			if(($message_id = $this->getProperty('outgoing_message_id')))
				$smtp_model->getHeaders()->addIdHeader('Message-ID', $message_id);
			
			$headers_text = $smtp_model->getPreparedHeaders()->toString();
			
			if($bcc_header = $smtp_model->getHeaders()->get('Bcc'))
				$headers_text .= $bcc_header->toString() . "\r\n";
			
			return $headers_text;
			
		} catch(Throwable) {
			return '';
		}
	}
}

/**
 * Email Management Singleton
 *
 * @static
 * @ingroup services
 */
class _DevblocksEmailManager {
	private static $instance = null;
	private $_lastErrorMessage = null;
	
	/**
	 * @private
	 */
	private function __construct() {
		
	}
	
	/**
	 *
	 * @return _DevblocksEmailManager
	 */
	public static function getInstance() {
		if(null == self::$instance) {
			self::$instance = new _DevblocksEmailManager();
		}
		return self::$instance;
	}
	
	public function createTransactionalModelFromProperties(array $properties, string &$error=null) : Model_DevblocksOutboundEmail|false {
		/*
		'bcc'
		'cc'
		'content'
		'content_format'
		'draft_id'
		'forward_files'
		'from'
		'from_personal'
		'gpg_encrypt'
		'gpg_sign'
		'html_template_id'
		'is_sensitive'
		'reply_to'
		'return_path'
		'send_at'
		'subject'
		'to'
		'token'
		 */
		
		if(!array_key_exists('outgoing_message_id', $properties))
			$properties['outgoing_message_id'] = $this->generateMessageId();
		
		$email_model = new Model_DevblocksOutboundEmail(Model_MailQueue::TYPE_TRANSACTIONAL, $properties);
		
		if(!$email_model->getTo()) {
			$error = "`to` is required.";
			return false;
		}
		
		if(!$email_model->getProperty('subject')) {
			$error = "`subject` is required.";
			return false;
		}
		
		if(!$email_model->getFromAddressModel()) {
			$error = "`from` is required.";
			return false;
		}
		
		return $email_model;
	}
	
	public function createComposeModelFromProperties(array $properties, string &$error=null) : Model_DevblocksOutboundEmail|false {
		/*
		'bcc'
		'bucket_id'
		'cc'
		'content'
		'content_format'
		'custom_fields'
		'dont_send'
		'draft_id'
		'forward_files'
		'gpg_encrypt'
		'gpg_sign'
		'group_id'
		'html_template_id'
		'link_forward_files'
		'org_id'
		'owner_id'
		'send_at'
		'status_id'
		'subject'
		'ticket_reopen'
		'to'
		'token'
		'watcher_ids'
		'worker_id'
		 */
		
		if(!array_key_exists('headers', $properties))
			$properties['headers'] = [];
		
		if(!array_key_exists('outgoing_message_id', $properties))
			$properties['outgoing_message_id'] = $this->generateMessageId();
		
		$group_id = $properties['group_id'] ?? null;
		
		// Invalid or missing group
		if(!$group_id || !($group = DAO_Group::get($group_id))) {
			if(!($group = DAO_Group::getDefaultGroup())) {
				$error = 'No default group';
				return false;
			}
			$properties['group_id'] = $group->id;
		}
		
		$bucket_id = $properties['bucket_id'] ?? null;
		
		// Invalid or missing bucket
		if(!$bucket_id || !(DAO_Bucket::get($bucket_id))) {
			if(!($bucket = $group->getDefaultBucket())) {
				$error = 'No default bucket';
				return false;
			}
			$properties['bucket_id'] = $bucket->id;
		}
		
		return new Model_DevblocksOutboundEmail(Model_MailQueue::TYPE_COMPOSE, $properties);
	}
	
	public function createReplyModelFromProperties(array $properties, string &$error=null) : Model_DevblocksOutboundEmail|false {
		/*
		'bcc'
		'bucket_id'
		'cc'
		'content'
		'content_format' // markdown, parsedown, html
		'custom_fields'
		'dont_keep_copy'
		'dont_send'
		'draft_id'
		'forward_files'
		'gpg_encrypt'
		'gpg_sign'
		'group_id'
		'headers'
		'html_template_id'
		'is_autoreply'
		'is_broadcast'
		'is_forward'
		'link_forward_files'
		'message_id'
		'owner_id'
		'send_at'
		'status_id'
		'subject'
		'ticket_id'
		'ticket_reopen'
		'to'
		'token'
		'worker_id'
		*/
		
		if(!array_key_exists('headers', $properties))
			$properties['headers'] = [];
		
		if(!array_key_exists('outgoing_message_id', $properties))
			$properties['outgoing_message_id'] = $this->generateMessageId();
		
		$type = ($properties['is_forward'] ?? null) ? Model_MailQueue::TYPE_TICKET_FORWARD : Model_MailQueue::TYPE_TICKET_REPLY;
		
		$reply_message_id = $properties['message_id'] ?? null;
		
		if(null == ($message = DAO_Message::get($reply_message_id))) {
			if(!($ticket = DAO_Ticket::get($properties['ticket_id'] ?? 0))) {
				$error = 'A target `message_id` or `ticket_id` to reply to is required.';
				return false;
			}
			
			if(null != ($message = $ticket->getLastMessage())) {
				$reply_message_id = $message->id;
				$properties['message_id'] = $reply_message_id;
			}
			
		} else {
			// Ticket
			if(null == ($ticket = $message->getTicket())) {
				$error = 'A target `message_id` or `ticket_id` to reply to is required.';
				return false;
			}
		}
		
		// References (on replies)
		if(($message_headers = DAO_MessageHeaders::getAll($properties['message_id'] ?? 0))) {
			if(($in_reply_to = ($message_headers['message-id'] ?? null))) {
				$properties['headers']['References'] = $in_reply_to;
				$properties['headers']['In-Reply-To'] = $in_reply_to;
			}
		}
		
		$properties['ticket_id'] = $ticket->id;
		
		$is_forward = $properties['is_forward'] ?? null;
		
		// If we have no subject, use the parent ticket's
		if(!($properties['subject'] ?? null))
			$properties['subject'] = $ticket->subject ?? '(no subject)';
		
		// If we have no 'To:', use the current recipients list
		if(!($properties['to'] ?? null) && !$is_forward) {
			// Auto-reply handling (RFC-3834 compliant)
			if (($is_autoreply = $properties['is_autoreply'] ?? null))
				$properties['headers']['Auto-Submitted'] = 'auto-replied';
			
			// Recipients
			$requesters = $ticket->getRequesters() ?? [];
			
			if (is_array($requesters)) {
				$new_recipients = [];
				
				foreach ($requesters as $requester) {
					/* @var $requester Model_Address */
					$first_email = DevblocksPlatform::strLower($requester->email);
					$first_split = explode('@', $first_email);
					
					if (!is_array($first_split) || count($first_split) != 2)
						continue;
					
					// Ourselves?
					if (DAO_Address::isLocalAddressId($requester->id))
						continue;
					
					if ($is_autoreply) {
						// If return-path is blank
						if (isset($message_headers['return-path']) && $message_headers['return-path'] == '<>')
							continue;
						
						// Ignore autoresponses to autoresponses
						if (isset($message_headers['auto-submitted']) && $message_headers['auto-submitted'] != 'no')
							continue;
						
						// Bulk mail?
						if (isset($message_headers['precedence']) &&
							($message_headers['precedence'] == 'list' || $message_headers['precedence'] == 'junk' || $message_headers['precedence'] == 'bulk'))
							continue;
					}
					
					// Ignore bounces
					if ($first_split[0] == "postmaster" || $first_split[0] == "mailer-daemon")
						continue;
					
					// Auto-reply just to the initial requester
					$new_recipients[] = $requester->email;
				}
				
				if($new_recipients)
					$properties['to'] = implode(',', $new_recipients);
			}
		}
		
		return new Model_DevblocksOutboundEmail($type, $properties);
	}
	
	function send(Model_DevblocksOutboundEmail $email_model) : bool {
		$metrics = DevblocksPlatform::services()->metrics();
		
		if(!($sender_address = $email_model->getFromAddressModel())) {
			$this->_lastErrorMessage = "The 'From:' has no sender address configured.";
			return false;
		}
		
		if(!($transport_model = $sender_address->getMailTransport())) {
			$this->_lastErrorMessage = "The 'From:' sender address has no mail transport.";
			return false;
		}
		
		if(!($transport_ext = $transport_model->getExtension())) {
			$this->_lastErrorMessage = "The 'From:' sender address mail transport is invalid.";
			return false;
		}
		
		if(!($result = $transport_ext->send($email_model, $transport_model))) {
			$this->_lastErrorMessage = $transport_ext->getLastError();
			
			if(!empty($this->_lastErrorMessage)) {
				/*
				 * Log activity (transport.delivery.error)
				 * {{actor}} failed to deliver message: {{error}}
				 */
				$entry = [
					'variables' => [
						'error' => sprintf("%s", $this->_lastErrorMessage),
					],
				];
				
				CerberusContexts::logActivity(
					'transport.delivery.error',
					CerberusContexts::CONTEXT_MAIL_TRANSPORT,
					$transport_model->id,
					$entry,
					CerberusContexts::CONTEXT_MAIL_TRANSPORT,
					$transport_model->id
				);
			}
			
			DAO_MailDeliveryLog::createFromEmailDeliveryFailure($email_model, $transport_model, $this->_lastErrorMessage ?: 'An unexpected error occurred.');
			
			// Increment mail transport error metric
			$metrics->increment(
				'cerb.mail.transport.failures',
				1,
				[
					'transport_id' => $transport_model->id,
					'sender_id' => $sender_address->id,
				]
			);
			
			// If we have a ticket, reopen it
			if(($ticket = $email_model->getTicket())) {
				DAO_Ticket::update($ticket->id, [
					DAO_Ticket::STATUS_ID => 0,
				]);
			}
			
		} else {
			DAO_MailDeliveryLog::createFromEmailDeliverySuccess($email_model, $transport_model);
			
			// Increment mail transport success metric
			$metrics->increment(
				'cerb.mail.transport.deliveries',
				1,
				[
					'transport_id' => $transport_model->id,
					'sender_id' => $sender_address->id,
				]
			);
			
		}
		
		return $result;
	}
	
	function getLastErrorMessage() : ?string {
		return $this->_lastErrorMessage;
	}
	
	/**
	 * @param $server
	 * @param $port
	 * @param $service
	 * @param $username
	 * @param $password
	 * @param int $timeout_secs
	 * @param int $connected_account_id
	 * @return bool
	 * @throws Exception
	 */
	private function _testMailboxImap($server, $port, $service, $username, $password, $timeout_secs=30, $connected_account_id=0) {
		$imap_timeout = !empty($timeout_secs) ? $timeout_secs : 30;
		
		//$fp_log = fopen('php://memory', 'w');
		
		try {
			$options = [
				'username' => $username,
				'password' => $password,
				'hostspec' => $server,
				'port' => $port,
				'timeout' => $imap_timeout,
				'secure' => false,
				//'debug' => $fp_log,
				//'capability_ignore' => ['LOGIN','PLAIN','NTLM','GSSAPI','XOAUTH2','AUTHENTICATE'],
			];
			
			if($service == 'imap-ssl') {
				$options['secure'] = 'tlsv1';
			} else if($service == 'imap-starttls') {
				$options['secure'] = 'tls';
			}
			
			// Are we using a connected account for XOAUTH2?
			if($connected_account_id) {
				if(!($connected_account = DAO_ConnectedAccount::get($connected_account_id)))
					throw new Exception("Failed to load the connected account");
					
				if(!($service = $connected_account->getService()))
					throw new Exception("Failed to load the connected service");
				
				if(!($service_extension = $service->getExtension()))
					throw new Exception("Failed to load the connected service extension");
				
				if(!($service_extension instanceof ServiceProvider_OAuth2))
					throw new Exception("The connected account is not an OAuth2 provider");
				
				if(!($access_token = $service_extension->getAccessToken($connected_account)))
					throw new Exception("Failed to load the access token");
				
				$options['xoauth2_token'] = new Horde_Imap_Client_Password_Xoauth2($username, $access_token->getToken());
				
				if(!$options['password'])
					$options['password'] = 'XOAUTH2';
			}
			
			$client = new Horde_Imap_Client_Socket($options);
			
			$mailbox = 'INBOX';
			
			$client->status($mailbox);
			
		} catch (Horde_Imap_Client_Exception $e) {
			throw new Exception($e->getMessage());
			
		} finally {
//			fseek($fp_log, 0);
//			error_log(fread($fp_log, 1024000));
//			fclose($fp_log);
		}
		
		return TRUE;
	}
	
	private function _testMailboxPop3($server, $port, $service, $username, $password, $timeout_secs=30, $connected_account_id=0) {
		$imap_timeout = !empty($timeout_secs) ? $timeout_secs : 30;
		
		//$fp_log = fopen('php://memory', 'w');
		
		try {
			$options = [
				'username' => $username,
				'password' => $password,
				'hostspec' => $server,
				'port' => $port,
				'timeout' => $imap_timeout,
				'secure' => false,
				//'debug' => $fp_log,
			];
			
			if($service == 'pop3-ssl') {
				$options['secure'] = 'tlsv1';
			} else if($service == 'pop3-starttls') {
				$options['secure'] = 'tls';
			}
			
			// Are we using a connected account for XOAUTH2?
			if($connected_account_id) {
				if(!($connected_account = DAO_ConnectedAccount::get($connected_account_id)))
					throw new Exception("Failed to load the connected account");
				
				if(!($service = $connected_account->getService()))
					throw new Exception("Failed to load the connected service");
				
				if(!($service_extension = $service->getExtension()))
					throw new Exception("Failed to load the connected service extension");
				
				if(!($service_extension instanceof ServiceProvider_OAuth2))
					throw new Exception("The connected account is not an OAuth2 provider");
				
				if(!($access_token = $service_extension->getAccessToken($connected_account)))
					throw new Exception("Failed to load the access token");
				
				$xoauth2 = new Horde_Imap_Client_Password_Xoauth2($username, $access_token->getToken());
				
				$options['xoauth2_token'] = $xoauth2;
				
				if(!$options['password'])
					$options['password'] = 'XOAUTH2';
			}
			
			$client = new Horde_Imap_Client_Socket_Pop3($options);
			
			$mailbox = 'INBOX';
			
			$client->status($mailbox);
			
		} catch (Horde_Imap_Client_Exception $e) {
			throw new Exception($e->getMessage());
			
		} finally {
//			fseek($fp_log, 0);
//			error_log(fread($fp_log, 1024000));
//			fclose($fp_log);
		}
		
		return TRUE;
	}
	
	function testMailbox($server, $port, $service, $username, $password, $timeout_secs=30, $connected_account_id=0) {
		switch($service) {
			default:
			case 'pop3':
			case 'pop3-ssl':
			case 'pop3-starttls':
				return $this->_testMailboxPop3($server, $port, $service, $username, $password, $timeout_secs, $connected_account_id);
				
			case 'imap':
			case 'imap-ssl':
			case 'imap-starttls':
				return $this->_testMailboxImap($server, $port, $service, $username, $password, $timeout_secs, $connected_account_id);
		}
	}
	
	public function getImageProxyBlocklist() {
		$cache = DevblocksPlatform::services()->cache();
		
		if(null === ($blocklist_hash = $cache->load('mail_html_image_blocklist'))) {
			$image_blocklist = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::MAIL_HTML_IMAGE_PROXY_BLOCKLIST, '');
			
			$blocklist_items = DevblocksPlatform::parseCrlfString($image_blocklist);
			$blocklist_hash = [];
			
			foreach($blocklist_items as $idx => $blocklist_item) {
				if(DevblocksPlatform::strStartsWith($blocklist_item, '#'))
					continue;
				
				if(!DevblocksPlatform::strStartsWith($blocklist_item, ['http://', 'https://']))
					$blocklist_item = 'http://' . $blocklist_item;
				
				if(!($url_parts = parse_url($blocklist_item)))
					continue;
				
				if(!array_key_exists('host', $url_parts))
					continue;
				
				if(!array_key_exists($url_parts['host'], $blocklist_hash))
					$blocklist_hash[$url_parts['host']] = [];
				
				$blocklist_hash[$url_parts['host']][] = DevblocksPlatform::strToRegExp(sprintf('*://%s%s%s',
					DevblocksPlatform::strStartsWith($url_parts['host'],'.') ? '*' : '',
					$url_parts['host'],
					array_key_exists('path', $url_parts) ? ($url_parts['path'].'*') : '/*'
				));
			}
			
			$cache->save($blocklist_hash, 'mail_html_image_blocklist', [], 0);
		}
		
		return $blocklist_hash;
	}
	public function getImageProxyAllowlist() {
		$cache = DevblocksPlatform::services()->cache();
		
		if(null === ($allowlist_hash = $cache->load('mail_html_image_allowlist'))) {
			$image_allowlist = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::MAIL_HTML_IMAGE_PROXY_ALLOWLIST, '');
			
			$allowlist_items = DevblocksPlatform::parseCrlfString($image_allowlist);
			$allowlist_hash = [];
			
			foreach($allowlist_items as $idx => $allowlist_item) {
				if(DevblocksPlatform::strStartsWith($allowlist_item, '#'))
					continue;
				
				if(!DevblocksPlatform::strStartsWith($allowlist_item, ['http://', 'https://']))
					$allowlist_item = 'http://' . $allowlist_item;
				
				if(!($url_parts = parse_url($allowlist_item)))
					continue;
				
				if(!array_key_exists('host', $url_parts))
					continue;
				
				if(!array_key_exists($url_parts['host'], $allowlist_hash))
					$allowlist_hash[$url_parts['host']] = [];
				
				$allowlist_hash[$url_parts['host']][] = DevblocksPlatform::strToRegExp(sprintf('*://%s%s%s',
					DevblocksPlatform::strStartsWith($url_parts['host'],'.') ? '*' : '',
					$url_parts['host'],
					array_key_exists('path', $url_parts) ? ($url_parts['path'].'*') : '/*'
				));
			}
			
			$cache->save($allowlist_hash, 'mail_html_image_allowlist', [], 0);
		}
		
		return $allowlist_hash;
	}
	
	public function getLinksWhitelist() {
		$cache = DevblocksPlatform::services()->cache();
		
		if(null === ($whitelist_hash = $cache->load('mail_html_links_whitelist'))) {
			$links_whitelist = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::MAIL_HTML_LINKS_WHITELIST, '');
			
			$whitelist_items = DevblocksPlatform::parseCrlfString($links_whitelist);
			$whitelist_hash = [];
			
			foreach($whitelist_items as $idx => $whitelist_item) {
				if (DevblocksPlatform::strStartsWith($whitelist_item, '#'))
					continue;
				
				if(!DevblocksPlatform::strStartsWith($whitelist_item, ['http://', 'https://']))
					$whitelist_item = 'http://' . $whitelist_item;
				
				if(!($url_parts = parse_url($whitelist_item)))
					continue;
				
				if(!array_key_exists('host', $url_parts))
					continue;
				
				if(!array_key_exists($url_parts['host'], $whitelist_hash))
					$whitelist_hash[$url_parts['host']] = [];
				
				if(!array_key_exists('path', $url_parts))
					$url_parts['path'] = '/';
				
				$whitelist_hash[$url_parts['host']][] = DevblocksPlatform::strToRegExp(sprintf('*://%s%s%s',
					DevblocksPlatform::strStartsWith($url_parts['host'],'.') ? '*' : '',
					$url_parts['host'],
					$url_parts['path'].'*'
				));
			}
			
			$cache->save($whitelist_hash, 'mail_html_links_whitelist', [], 0);
		}
		
		return $whitelist_hash;
	}
	
	public function runRoutingKata(array $routing, DevblocksDictionaryDelegate $routing_dict, &$match=null) : array|false {
		foreach($routing as $rule_key => $rule_data) {
			$num_ifs = 0;
			
			foreach($rule_data as $node_key => $node_data) {
				$node_type = DevblocksPlatform::services()->string()->strBefore($node_key, '/');
				
				// Check conditions
				if('if' == $node_type) {
					$num_ifs++;
					$cond_passed = 0;
					$cond_count = 0;
					
					foreach($node_data as $cond_key => $cond_data) {
						$cond_type = DevblocksPlatform::services()->string()->strBefore($cond_key, '/');
						$cond_count++;
						
						if('recipients' == $cond_type) {
							if(is_string($cond_data) && str_contains($cond_data, ','))
								$cond_data = DevblocksPlatform::parseCsvString($cond_data);
							
							// Allow lazy mailbox@ wildcard patterns
							$patterns = array_map(
								fn($addy) => str_ends_with($addy, '@') ? ($addy . '*') : $addy,
								is_string($cond_data) ? [$cond_data] : $cond_data,
							);
							
							if(DevblocksPlatform::services()->string()->arrayMatches($routing_dict->get('recipients', []), $patterns, only_first_match: true))
								$cond_passed++;
							
						} else if('subject' == $cond_type) {
							if(DevblocksPlatform::services()->string()->arrayMatches($routing_dict->get('subject', ''), $cond_data, only_first_match: true))
								$cond_passed++;
							
						} else if('body' == $cond_type) {
							if(DevblocksPlatform::services()->string()->arrayMatches($routing_dict->get('body', ''), $cond_data, only_first_match: true, extra_flags: 's'))
								$cond_passed++;
							
						} else if('sender_email' == $cond_type) {
							if(is_string($cond_data) && str_contains($cond_data, ','))
								$cond_data = DevblocksPlatform::parseCsvString($cond_data);
							
							// Allow lazy mailbox@ wildcard patterns
							$patterns = array_map(
								fn($addy) => str_ends_with($addy, '@') ? ($addy . '*') : $addy,
								is_string($cond_data) ? [$cond_data] : $cond_data,
							);
							
							if(DevblocksPlatform::services()->string()->arrayMatches($routing_dict->get('sender_email', ''), $patterns, only_first_match: true))
								$cond_passed++;
						
						} else if('spam_score' == $cond_type) {
							if(is_string($cond_data)) {
								$oper = DevblocksPlatform::strStartsWith($cond_data, ['>=','>','<=','<']);
								$spam_score = $routing_dict->get('spam_score', 0);
								$value = intval(DevblocksPlatform::strAlphaNum($cond_data))/100;
								if(
									($oper == '>=' && $spam_score >= $value)
									|| ($oper == '>' && $spam_score > $value)
									|| ($oper == '<=' && $spam_score <= $value)
									|| ($oper == '<' && $spam_score < $value)
								) $cond_passed++;
							}
							
						} else if('header' == $cond_type) {
							if(!is_array($cond_data))
								continue;
							
							// If every defined header pattern matches
							if(count($cond_data) == count(array_filter($cond_data, function($header_data, $header_key) use ($routing_dict) {
								$header_key = trim(DevblocksPlatform::strLower($header_key), ': ');
								return DevblocksPlatform::services()->string()->arrayMatches(
									$routing_dict->getKeyPath('headers:' . $header_key, '', ':'),
									$header_data,
									only_first_match: true
								);
							}, ARRAY_FILTER_USE_BOTH))) $cond_passed++;
							
						} else if('script' == $cond_type) {
							if(is_string($cond_data) && 1 == intval($cond_data))
								$cond_passed++;
							
						} else {
							DevblocksPlatform::noop();
						}
					}
					
					if($cond_passed == $cond_count) {
						$match = [$rule_key, $node_key];
						return $rule_data['then'] ?? [];
					}
				}
			}
			
			// If the rule has no `if:` then always use it
			if(0 == $num_ifs) {
				$match = [$rule_key];
				return $rule_data['then'] ?? [];
			}
		}
		
		return false;
	}
	
	public function runRoutingKataActions(array $route_actions, CerberusParserModel $model) {
		$group_name = $route_actions['group'] ?? null;
		$bucket_name = $route_actions['bucket'] ?? null;
		$importance = $route_actions['importance'] ?? null;
		$owner = $route_actions['owner'] ?? null;
		$watchers = $route_actions['watchers'] ?? null;
		$comment = $route_actions['comment'] ?? null;
		
		// Comment
		if(!is_null($comment)) {
			DAO_Comment::create([
				DAO_Comment::COMMENT => $comment,
				DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_TICKET,
				DAO_Comment::CONTEXT_ID => $model->getTicketId(),
				DAO_Comment::CREATED => time()+5,
				DAO_Comment::IS_MARKDOWN => true,
				DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_GROUP,
				DAO_Comment::OWNER_CONTEXT_ID => $model->getRouteGroup()->id,
			]);
		}
		
		// Importance
		if(!is_null($importance))
			$model->getTicketModel()->importance = DevblocksPlatform::intClamp($importance, 0, 100);
		
		// Owner
		if(is_string($owner)) {
			// Look up the @mention
			if(($owner_worker = DAO_Worker::getByAtMention(ltrim($owner, '@'))))
				$model->getTicketModel()->owner_id = $owner_worker->id;
		}
		
		// Watchers
		if(!is_null($watchers)) {
			// A string with commas is a list
			if(is_string($watchers) && str_contains($watchers, ',')) {
				$watchers = DevblocksPlatform::parseCsvString($watchers);
			// A single string is a single element list
			} elseif(is_string($watchers)) {
				$watchers = [$watchers];
			}
			
			if(is_array($watchers)) {
				if(($watcher_workers = DAO_Worker::getByAtMentions($watchers)))
					$model->getParserMessage()->watcher_ids = array_keys($watcher_workers);
			}
		}
		
		// If we just have a bucket name, see if we have a group yet
		if($bucket_name && !$group_name && $model->getRouteGroup()) {
			$buckets = $model->getRouteGroup()->getBuckets();
			$bucket_name = DevblocksPlatform::strLower($bucket_name);
			
			$bucket_names = array_change_key_case(
				array_column($buckets, 'id', 'name'),
				CASE_LOWER
			);
			
			if(array_key_exists($bucket_name, $bucket_names)) {
				if(null != ($model->setRouteBucket($buckets[$bucket_names[$bucket_name]])))
					DevblocksPlatform::noop();
			}
		
		} else if($bucket_name && $group_name) {
			$group_id = DAO_Group::getByName($group_name);
			
			if(null != ($model->setRouteGroup($group_id))) {
				$buckets = $model->getRouteGroup()->getBuckets();
				$bucket_name = DevblocksPlatform::strLower($bucket_name);
				
				$bucket_names = array_change_key_case(
					array_column($buckets, 'id', 'name'),
					CASE_LOWER
				);
				
				if(array_key_exists($bucket_name, $bucket_names)) {
					if(null != ($model->setRouteBucket($buckets[$bucket_names[$bucket_name]])))
						DevblocksPlatform::noop();
				}
			}
			
		} elseif ($group_name) {
			$model->setRouteGroup(DAO_Group::getByName($group_name));
		}
	}
	
	public function generateMessageId() : string {
		return sprintf("%s.%s@%s",
			base_convert(intval(microtime(true))*1000, 10, 36),
			base_convert(bin2hex(random_bytes(16)), 16, 36),
			'cerb'
		);
	}
};