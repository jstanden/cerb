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
/*
 * IMPORTANT LICENSING NOTE from your friends at Cerb
 *
 * Sure, it would be really easy to just cheat and edit this file to use
 * Cerb without paying for a license.  We trust you anyway.
 *
 * It takes a significant amount of time and money to develop, maintain,
 * and support high-quality enterprise software with a dedicated team.
 * For Cerb's entire history we've avoided taking money from outside
 * investors, and instead we've relied on actual sales from satisfied
 * customers to keep the project running.
 *
 * We've never believed in hiding our source code out of paranoia over not
 * getting paid.  We want you to have the full source code and be able to
 * make the tweaks your organization requires to get more done -- despite
 * having less of everything than you might need (time, people, money,
 * energy).  We shouldn't be your bottleneck.
 *
 * As a legitimate license owner, your feedback will help steer the project.
 * We'll also prioritize your issues, and work closely with you to make sure
 * your teams' needs are being met.
 *
 * - Jeff Standen and Dan Hildebrandt
 *	 Founders at Webgroup Media LLC; Developers of Cerb
 */

class CerberusParserMessage {
	public $encoding = '';
	public $headers = [];
	public $raw_headers = '';
	public $body = '';
	public $body_encoding = '';
	public $htmlbody = '';
	public $files = [];
	public $custom_fields = [];
	public $was_encrypted = false;
	public $signed_key_fingerprint = '';
	public $signed_at = 0;
	
	function build() {
		$this->_buildHeaders();
		$this->_buildThreadBounces();
	}
	
	private function _buildHeaders() {
		if(empty($this->raw_headers) && !empty($this->headers))
		foreach($this->headers as $k => $v) {
			$k = DevblocksPlatform::services()->string()->capitalizeDashed($k);
			
			if(is_array($v)) {
				foreach($v as $vv) {
					$this->raw_headers .= sprintf("%s: %s\r\n", $k, $vv);
				}
			} else if(is_string($v)) {
				$this->raw_headers .= sprintf("%s: %s\r\n", $k, $v);
			}
		}
	}
	
	// Thread a bounce to the message it references based on the message/rfc822 attachment
	private function _buildThreadBounces() {
		if(is_array($this->files))
		foreach ($this->files as $file) { /* @var $file ParserFile */
			switch($file->mime_type) {
				case 'message/rfc822':
					if(false == ($mime = new MimeMessage('file',  $file->tmpname)))
						break;
						
					if(!isset($this->headers['from']) || !isset($mime->data['headers']) || !isset($mime->data['headers']['message-id']))
						break;
					
					if(false == ($bounce_from = CerberusMail::parseRfcAddress($this->headers['from'])))
						break;
					
					// Change the inbound In-Reply-To: header to that of the bounce
					if(in_array(DevblocksPlatform::strLower($bounce_from['mailbox']), array('postmaster', 'mailer-daemon'))) {
						$this->headers['in-reply-to'] = $mime->data['headers']['message-id'];
					}
					
				break;
			}
		}
	}
}

class CerberusParserModel {
	private $_message = null;
	
	private $_pre_actions = [];
	
	private $_is_new = true;
	private $_sender_address_model = null;
	private $_sender_worker_model = null;
	private $_subject = '';
	private $_date = 0;
	private $_ticket_id = 0;
	private $_ticket_model = null;
	private $_message_id = 0;
	private $_route_group = null;
	private $_route_bucket = null;
	
	public function __construct(CerberusParserMessage $message) {
		$this->setMessage($message);
		
		$this->_parseHeadersFrom();
		$this->_parseHeadersSubject();
		$this->_parseHeadersDate();
		$this->_parseHeadersIsNew();
	}
	
	public function validate() {
		$logger = DevblocksPlatform::services()->log('Parser');
		
		// [TODO] Try...Catch
		
		// Is valid sender?
		if(null == $this->_sender_address_model) {
			$logger->error("From address could not be created.");
			return false;
		}
		
		// Is banned?
		if($this->_sender_address_model->is_banned) {
			$logger->warn("Ignoring ticket from banned address: " . $this->_sender_address_model->email);
			return null;
		}
		
		return true;
	}
	
	/**
	 * @return void
	 */
	private function _parseHeadersFrom() {
		try {
			$this->_sender_address_model = null;
			
			$sReturnPath = $this->_message->headers['return-path'] ?? null;
			$sReplyTo = $this->_message->headers['reply-to'] ?? null;
			$sFrom = $this->_message->headers['from'] ?? null;
			
			$from = [];
			
			if(empty($from) && !empty($sReplyTo))
				$from = CerberusMail::parseRfcAddresses($sReplyTo);
			
			if(empty($from) && !empty($sFrom))
				$from = CerberusMail::parseRfcAddresses($sFrom);
			
			if(empty($from) && !empty($sReturnPath))
				$from = CerberusMail::parseRfcAddresses($sReturnPath);
			
			if(!is_array($from) || !$from)
				throw new Exception('No sender headers found.');
			
			foreach($from as $addy) {
				if(!$addy['email'])
					continue;
			
				@$fromAddress = $addy['email'];
				
				if(null != ($fromInst = DAO_Address::lookupAddress($fromAddress, true))) {
					$this->setSenderAddressModel($fromInst);
					
					if(null != ($fromWorkerAuth = DAO_Address::getByEmail($fromAddress))) {
						if(null != ($fromWorker = $fromWorkerAuth->getWorker()))
							$this->setSenderWorkerModel($fromWorker);
					}
					
					return;
				}
			}
			
		} catch (Exception $e) {
			$this->_sender_address_model = null;
			$this->_sender_worker_model = null;
			return;
		}
	}
	
	/**
	 * @return string $subject
	 */
	private function _parseHeadersSubject() {
		$subject = '';
		
		// Handle multiple subjects
		if(isset($this->_message->headers['subject']) && !empty($this->_message->headers['subject'])) {
			$subject = $this->_message->headers['subject'];
			if(is_array($subject))
				$subject = array_shift($subject);
		}
		
		// Remove tabs, returns, and linefeeds
		$subject = str_replace(array("\t","\n","\r")," ",$subject);
		
		// The subject can still end up empty after QP decode
		if(0 == strlen(trim($subject)))
			$subject = "(no subject)";
		
		$this->setSubject($subject);
	}
	
	/**
	 * @return int $timestamp
	 */
	private function _parseHeadersDate() {
		$timestamp = strtotime($this->_message->headers['date'] ?? null);
		
		// If blank, or in the future, set to the current date
		if(empty($timestamp) || $timestamp > time())
			$timestamp = time();
			
		$this->_date = $timestamp;
	}
	
	public function updateThreadHeaders() {
		$this->_parseHeadersSubject();
		return $this->_parseHeadersIsNew();
	}
	
	public function updateSender() {
		$this->_parseHeadersFrom();
	}
	
	/**
	 * First we check the references and in-reply-to headers to find a
	 * historical match in the database. If those don't match we check
	 * the subject line for a mask (if one exists). If none of those
	 * options match we return null.
	 */
	private function _parseHeadersIsNew() {
		$aSubject = $this->_message->headers['subject'] ?? '';
		$sMessageId = trim($this->_message->headers['message-id'] ?? '');
		$sInReplyTo = trim($this->_message->headers['in-reply-to'] ?? '');
		$sReferences = trim($this->_message->headers['references'] ?? '');
		//$sThreadTopic = trim($this->_message->headers['thread-topic'] ?? '');

		$senderWorker = $this->getSenderWorkerModel();
		
		$aReferences = [];
		
		// Append first <*> from In-Reply-To
		if(!empty($sInReplyTo)) {
			$matches = [];
			if(preg_match("/(\<.*?\@.*?\>)/", $sInReplyTo, $matches)) {
				if(isset($matches[1])) { // only use the first In-Reply-To
					$ref = trim($matches[1]);
					if(!empty($ref) && 0 != strcasecmp($ref,$sMessageId))
						$aReferences[$ref] = 1;
				}
			}
			unset($matches);
		}
		
		// Add all References
		if(!empty($sReferences)) {
			$matches = [];
			if(preg_match_all("/(\<[^\>]*?\>)/", $sReferences, $matches)) {
				$matches = array_reverse($matches[1] ?? []);
				
				foreach($matches as $ref) {
					$ref = trim($ref);
					if(!empty($ref) && 0 != strcasecmp($ref,$sMessageId))
						$aReferences[$ref] = 1;
				}
			}
			unset($matches);
		}
		
		// Try matching in-reply-to or references
		if(is_array($aReferences) && !empty($aReferences)) {
			foreach(array_keys($aReferences) as $ref) {
				if(empty($ref))
					continue;
				
				// Only consider the watcher auth header to be a reply if it validates
				if($senderWorker instanceof Model_Worker
					&& @preg_match('#\<(.*?)\@cerb\d{0,1}\>#', $ref)
					&& false != ($relay_message_id = $this->isValidAuthHeader($ref, $senderWorker))) {
					
					if(null != ($ticket = DAO_Ticket::getTicketByMessageId($relay_message_id))) {
						$this->_ticket_id = $ticket->id;
						$this->_ticket_model = $ticket;
						$this->_message_id = $relay_message_id;
						$this->_is_new = empty($this->_ticket_model->first_message_id);
						return;
					}
				}
				
				// Otherwise, look up the normal header
				if(null != ($ids = DAO_Ticket::getTicketByMessageIdHeader($ref))) {
					$this->_ticket_id = $ids['ticket_id'];
					$this->_ticket_model = DAO_Ticket::get($this->_ticket_id);
					$this->_message_id = $ids['message_id'];
					$this->_is_new = empty($this->_ticket_model->first_message_id);
					return;
				}
			}
		}
		
		// Try matching the subject line
		// [TODO] This should only happen if the destination has subject masks enabled
		
		if(!is_array($aSubject))
			$aSubject = array($aSubject);
			
		foreach($aSubject as $subject) {
			if(preg_match("/.*\[.*?\#(.*?)\].*/", $subject, $matches)) {
				if(isset($matches[1])) {
					$mask = $matches[1];
					if($mask && null != ($ticket = DAO_Ticket::getTicketByMask($mask))) {
						$this->_ticket_id = $ticket->id;
						$this->_ticket_model = $ticket;
						$this->_message_id = $ticket->last_message_id;
						$this->_is_new = empty($this->_ticket_model->first_message_id);
						return;
					}
				}
			}
		}

		$this->_is_new = true;
		$this->_ticket_id = 0;
		$this->_ticket_model = null;
		$this->_message_id = 0;
	}
	
	public function getRecipients() : array {
		$headers =& $this->_message->headers;
		$sources = [];
		
		if(isset($headers['to']))
			$sources = array_merge($sources, is_array($headers['to']) ? $headers['to'] : array($headers['to']));

		if(isset($headers['cc']))
			$sources = array_merge($sources, is_array($headers['cc']) ? $headers['cc'] : array($headers['cc']));
		
		if(isset($headers['envelope-to']))
			$sources = array_merge($sources, is_array($headers['envelope-to']) ? $headers['envelope-to'] : array($headers['envelope-to']));
		
		if(isset($headers['x-envelope-to']))
			$sources = array_merge($sources, is_array($headers['x-envelope-to']) ? $headers['x-envelope-to'] : array($headers['x-envelope-to']));
		
		if(isset($headers['delivered-to']))
			$sources = array_merge($sources, is_array($headers['delivered-to']) ? $headers['delivered-to'] : array($headers['delivered-to']));
		
		$destinations = [];
		foreach($sources as $source) {
			$parsed = CerberusMail::parseRfcAddresses($source);
			$destinations = array_merge($destinations, is_array($parsed) ? $parsed : array($parsed));
		}
		
		$addresses = [];
		foreach($destinations as $destination) {
			if(empty($destination['mailbox']) || empty($destination['host']))
				continue;
			
			$addresses[] = $destination['mailbox'].'@'.$destination['host'];
		}
		
		return $addresses;
	}
	
	public function isWorkerRelayReply() {
		$message_id = trim($this->_message->headers['message-id'] ?? '');
		$in_reply_to = trim($this->_message->headers['in-reply-to'] ?? '');
		$references = trim($this->_message->headers['references'] ?? '');
		
		$target_message_ids = [];
		
		// Add all References
		if(!empty($references)) {
			$matches = [];
			if(preg_match("/(\<.*?\@.*?\>)/", $references, $matches)) {
				unset($matches[0]); // who cares about the pattern
				foreach($matches as $ref) {
					$ref = trim($ref);
					if(!empty($ref) && 0 != strcasecmp($ref, $message_id))
						$target_message_ids[$ref] = true;
				}
			}
			unset($matches);
		}
		
		// Append first <*> from In-Reply-To
		if(!empty($in_reply_to)) {
			$matches = [];
			if(preg_match("/(\<.*?\@.*?\>)/", $in_reply_to, $matches)) {
				if(isset($matches[1])) { // only use the first In-Reply-To
					$ref = trim($matches[1]);
					if(!empty($ref) && 0 != strcasecmp($ref, $message_id))
						$target_message_ids[$ref] = true;
				}
			}
			unset($matches);
		}
		
		// Try matching references
		foreach(array_keys($target_message_ids) as $ref) {
			if($ref && @preg_match('#\<(.*?)\@cerb\d{0,1}\>#', $ref)) {
				return $ref;
			}
		}
		
		return false;
	}
	
	public function isValidAuthHeader($auth_header, $worker) {
		if(empty($worker) || !($worker instanceof Model_Worker))
			return false;
		
		return CerberusMail::relayVerify($auth_header, $worker->id);
	}
	
	// Getters/Setters
	
	/**
	 * @return CerberusParserMessage
	 */
	public function &getMessage() {
		return $this->_message;
	}
	
	public function setMessage(CerberusParserMessage $message) {
		$this->_message = $message;
	}
	
	public function &getHeaders() {
		return $this->_message->headers;
	}
	
	public function &getPreActions() {
		return $this->_pre_actions;
	}
	
	public function addPreAction($action, $params=[]) {
		$this->_pre_actions[$action] = $params;
	}
	
	public function &getIsNew() {
		return $this->_is_new;
	}
	
	public function setIsNew($bool) {
		$this->_is_new = $bool;
	}
	
	public function &getSenderAddressModel() {
		return $this->_sender_address_model;
	}
	
	public function setSenderAddressModel($model) {
		$this->_sender_address_model = $model;
	}
	
	/**
	 * @return null|Model_Worker
	 */
	public function &getSenderWorkerModel() {
		return $this->_sender_worker_model;
	}
	
	public function setSenderWorkerModel($model) {
		$this->_sender_worker_model = $model;
	}
	
	public function isSenderWorker() {
		return $this->_sender_worker_model instanceof Model_Worker;
	}
	
	public function &getSubject() {
		return $this->_subject;
	}
	
	public function setSubject($subject) {
		$this->_subject = $subject;
	}
	
	public function &getDate() {
		return $this->_date;
	}
	
	public function setDate($date) {
		$this->_date = $date;
	}
	
	public function setTicketId($id) {
		$this->_ticket_id = $id;
	}
	
	public function &getTicketId() {
		return $this->_ticket_id;
	}
	
	/**
	 * @return Model_Ticket
	 */
	public function getTicketModel() {
		if(!empty($this->_ticket_model))
			return $this->_ticket_model;

		if(empty($this->_ticket_id)) {
			$this->setTicketModel(null);
			return null;
		}
		
		$ticket = DAO_Ticket::get($this->_ticket_id);
		
		$this->setTicketModel($ticket);
		
		return $ticket;
	}
	
	public function setTicketModel($model) {
		$this->_ticket_model = $model;
	}
	
	public function setMessageId($id) {
		$this->_message_id = $id;
	}
	
	public function getMessageId() {
		return $this->_message_id;
	}
	
	public function setRouteGroup($id) {
		$this->_route_group = null;
		$this->_route_bucket = null;

		if($id instanceof Model_Group) {
			$this->_route_group = $id;
			$this->_route_bucket = $this->_route_group->getDefaultBucket();
		
		} elseif (is_numeric($id) && false != ($to_group = DAO_Group::get($id))) {
			$this->_route_group = $to_group;
			$this->_route_bucket = $to_group->getDefaultBucket();
		}
		
		return $this->_route_group;
	}
	
	public function getRouteGroup() {
		if($this->_route_group)
			return $this->_route_group;
			
		if(null != ($model = $this->getTicketModel())) {
			$this->_route_group = $model->getGroup();
			$this->_route_bucket = $model->getBucket();
		} else {
			$this->_route_group = null;
			$this->_route_bucket = null;
		}
		
		return $this->_route_group;
	}
	
	public function setRouteBucket($id) {
		$this->_route_group = null;
		$this->_route_bucket = null;
		
		if($id instanceof Model_Bucket) {
			$this->_route_bucket = $id;
			$this->_route_group = $this->_route_bucket->getGroup();
		
		} elseif (is_numeric($id) && $to_bucket = DAO_Bucket::get($id)) {
			$this->_route_bucket = $to_bucket;
			$this->_route_group = $this->_route_bucket->getGroup();
		}
		
		return $this->_route_bucket;
	}
	
	public function getRouteBucket() {
		if($this->_route_bucket)
			return $this->_route_bucket;
		
		if(null != ($model = $this->getTicketModel())) {
			$this->_route_group = $model->getGroup();
			$this->_route_bucket = $model->getBucket();
		} else {
			$this->_route_group = null;
			$this->_route_bucket = null;
		}
		
		return $this->_route_bucket;
	}
	
	/**
	 * 
	 * @return Model_Group|null
	 */
	public function getRoutingGroup() {
		$model = clone $this; /* @var $model CerberusParserModel */
		$group_id = 0;
		
		if(false == ($sender_address = $model->getSenderAddressModel())) {
			$sender_address = new Model_Address();
			$sender_address->email = 'missing-sender@example.com';
		}
		
		if(false == ($sender_message = $model->getMessage()))
			return null;
		
		// Routing new tickets
		if(null != ($routing_rules = Model_MailToGroupRule::getMatches(
			$sender_address,
			$sender_message
		))) {
			
			// Update our model with the results of the routing rules
			if(is_array($routing_rules))
			foreach($routing_rules as $rule) {
				if(array_key_exists('move', $rule->actions)) {
					if(false != ($move_group_id = $rule->actions['move']['group_id']))
						$group_id = $move_group_id;
				}
			}
		}
		
		if($group_id && false != ($group = DAO_Group::get($group_id)))
			return $group;
		
		if(false != ($group = DAO_Group::getDefaultGroup()))
			return $group;
		
		return null;
	}
};

class ParserFile {
	public $tmpname = null;
	public $mime_type = '';
	public $file_size = 0;
	public $parsed_attachment_id = 0;

	function __destruct() {
		if(file_exists($this->tmpname)) {
			@unlink($this->tmpname);
		}
	}

	public function setTempFile($tmpname, $mimetype='application/octet-stream') {
		$this->mime_type = $mimetype;

		if(!empty($tmpname) && file_exists($tmpname)) {
			$this->tmpname = $tmpname;
		}
	}

	public function getTempFile() {
		return $this->tmpname;
	}

	static public function makeTempFilename() {
		$path = APP_TEMP_PATH . DIRECTORY_SEPARATOR;
		return tempnam($path,'mime');
	}
};

class ParseFileBuffer extends ParserFile {
	public $section = null;
	public $info = null;

	function __construct($section) {
		$this->setTempFile(ParserFile::makeTempFilename(), @$section->data['content-type']);
		$fp = fopen($this->getTempFile(),'wb');

		if(isset($section->data))
			$this->info = $section->data;
		
		if($fp && $section) {
			$section->extract_body(MAILPARSE_EXTRACT_STREAM, $fp);
		}

		@fclose($fp);
	}
};

class CerberusParser {
	static public function parseMessageSource($message_source, $delete_on_success=true, $delete_on_failure=true) {
		$file = null;
		
		try {
			$matches = [];
			
			if(preg_match('/^file:\/\/(.*?)$/', $message_source, $matches)) {
				$file = $matches[1];
				
				if(null == ($parser_msg = CerberusParser::parseMimeFile($file))) {
					throw new Exception("The message mime could not be parsed (it's probably malformed).");
				}
				
			} else {
				$message_source .= PHP_EOL;
				
				if(null == ($parser_msg = CerberusParser::parseMimeString($message_source))) {
					throw new Exception("The message mime could not be parsed (it's probably malformed).");
				}
			}
			
			if(false === ($ticket_id = CerberusParser::parseMessage($parser_msg))) {
				throw new Exception("The message was rejected by the parser.");
			}
			
			if($file && $delete_on_success)
				@unlink($file);
			
			if(is_numeric($ticket_id)) {
				$dict = DevblocksDictionaryDelegate::instance([
					'_context' => CerberusContexts::CONTEXT_TICKET,
					'id' => $ticket_id,
				]);
				return $dict;
				
			} else {
				return $ticket_id;
			}
			
		} catch (Exception $e) {
			if($file && $delete_on_failure)
				@unlink($file);
			throw $e;
		}
		
		return false;
	}
	
	static public function parseMimeFile($full_filename) {
		$mm = new MimeMessage("file", $full_filename);
		return self::_parseMime($mm);
	}
	
	static public function parseMimeString($string) {
		$mm = new MimeMessage("var", rtrim($string, PHP_EOL) . PHP_EOL);
		return self::_parseMime($mm);
	}
	
	static private function _recurseMimeParts($part, &$results, &$mime_meta=[]) {
		if(!($part instanceof MimeMessage))
			return false;
		
		if(!is_array($results))
			$results = [];
		
		// Normalize charsets
		switch(DevblocksPlatform::strLower($part->data['charset'])) {
			case 'gb2312':
				$part->data['charset'] = 'gbk';
				break;
		}
		
		$do_ignore = false;
		$do_recurse = true;
		
		switch(DevblocksPlatform::strLower($part->data['content-type'])) {
			case 'application/pgp-signature':
				$do_ignore = true;
				break;
				
			case 'multipart/signed':
				$gpg = DevblocksPlatform::services()->gpg();
				$do_ignore = true;
				$do_recurse = true;
				
				// We only care about PGP signatures
				if(0 != strcasecmp('application/pgp-signature', $part->data['content-protocol']))
					break;
				
				if($part->get_child_count() != 3)
					break;
				
				$raw_body = $part->extract_body(MAILPARSE_EXTRACT_RETURN);
				
				$boundary = $part->data['content-boundary'];
				$boundary_parts = preg_split("#\\r?\\n--" . preg_quote($boundary) . '#', $raw_body);
				$signed_content = ltrim($boundary_parts[1]);
				
				$part_signature = $part->get_child(2);
				
				$signature = $part_signature->extract_body(MAILPARSE_EXTRACT_RETURN);
				
				// Denote valid signature on saved message
				if(false != ($info = $gpg->verify($signed_content, $signature))) {
					$mime_meta['gpg_verified_signatures'] = $info;
				}
				break;
				
			case 'multipart/encrypted':
				$do_ignore = true;
				$do_recurse = false;
				
				// We must have at least two parts (control + encrypted)
				if($part->get_child_count() < 2)
					break;
				
				$control_part = $part->get_child(1);
				$encrypted_part = $part->get_child(2);
				
				// PGP Encrypted
				if(0 == strcasecmp('application/pgp-encrypted', @$control_part->data['content-type'])) {
					try {
						$gpg = DevblocksPlatform::services()->gpg();
						
						$encrypted_content = $encrypted_part->extract_body(MAILPARSE_EXTRACT_RETURN);
						
						if (false == ($decrypt_results = $gpg->decrypt($encrypted_content))) {
							throw new Exception("Failed to find a decryption key for PGP message content.");
						}
						
						if (false == ($decrypted_mime = new MimeMessage("var", rtrim($decrypt_results['data'], PHP_EOL) . PHP_EOL)))
							throw new Exception("Failed to parse decrypted MIME content.");
						
						// Denote encryption on saved message
						$mime_meta['gpg_encrypted'] = true;
						
						// Signed?
						if (array_key_exists('verified_signatures', $decrypt_results))
							$mime_meta['gpg_verified_signatures'] = $decrypt_results['verified_signatures'];
						
						// Add to the mime tree
						$new_mime_parts = [];
						
						self::_recurseMimeParts($decrypted_mime, $new_mime_parts, $mime_meta);
						
						$mime_meta['references'][] = $decrypted_mime;
						
						foreach ($new_mime_parts as $k => $v) {
							$results[$k] = $v;
						}
						
					} catch (Exception $e) {
						// If we failed, keep the whole part
						$results[spl_object_hash($part)] = $part;
					}
				}
				break;
				
			case 'multipart/alternative':
			case 'multipart/mixed':
			case 'multipart/related':
			case 'multipart/report':
			case 'text/plain; (error)':
				$do_ignore = true;
				break;
				
			case 'message/rfc822':
				$do_recurse = false;
				break;
		}
		
		if(!$do_ignore)
			$results[spl_object_hash($part)] = $part;
		
		if($do_recurse)
		for($n = 1; $n < $part->get_child_count(); $n++) {
			self::_recurseMimeParts($part->get_child($n), $results, $mime_meta);
		}
	}
	
	static private function _getMimePartFilename($part) {
		$content_filename = isset($part->data['disposition-filename']) ? $part->data['disposition-filename'] : '';
		
		if(empty($content_filename))
			$content_filename = isset($part->data['content-name']) ? $part->data['content-name'] : '';
		
		return CerberusParser::fixQuotePrintableString($content_filename, $part->data['charset']);
	}
	
	/**
	 * @param MimeMessage $mm
	 * @return CerberusParserMessage|false
	 */
	static private function _parseMime($mm) {
		if(!($mm instanceof MimeMessage))
			return false;
		
		$message = new CerberusParserMessage();
		@$message->encoding = $mm->data['charset'];
		@$message->body_encoding = $message->encoding; // default
		
		$message->raw_headers = $mm->extract_headers(MAILPARSE_EXTRACT_RETURN);
		$message->headers = CerberusParser::fixQuotePrintableArray($mm->data['headers']);
		
		$mime_parts = [];
		$mime_meta = [];

		self::_recurseMimeParts($mm, $mime_parts, $mime_meta);
		
		// Was it encrypted?
		if(isset($mime_meta['gpg_encrypted']) && $mime_meta['gpg_encrypted']) {
			$message->was_encrypted = true;
		}
		
		// Was it signed?
		if(array_key_exists('gpg_verified_signatures', $mime_meta) && $mime_meta['gpg_verified_signatures']) {
			$verified_signature = $mime_meta['gpg_verified_signatures'];
			
			if(is_array($verified_signature)) {
				if(array_key_exists('fingerprint', $verified_signature))
					$message->signed_key_fingerprint = $verified_signature['fingerprint'];
				
				if(array_key_exists('signed_at', $verified_signature))
					$message->signed_at = $verified_signature['signed_at'];
			}
		}
		
		if(is_array($mime_parts))
		foreach($mime_parts as $section_idx => $section) {
			if(!isset($section->data)) {
				unset($mime_parts[$section_idx]);
				continue;
			}
			
			$content_type = DevblocksPlatform::strLower(isset($section->data['content-type']) ? $section->data['content-type'] : '');
			$content_filename = self::_getMimePartFilename($section);
			
			if(empty($content_filename)) {
				$handled = false;
				
				switch($content_type) {
					case 'text/plain':
						$handled = self::_handleMimepartTextPlain($section, $message);
						break;
						
					case 'text/html':
						$handled = self::_handleMimepartTextHtml($section, $message);
						break;
						
					case 'text/calendar':
						$content_filename = sprintf("calendar_%s.ics", uniqid());
						break;
						
					case 'message/delivery-status':
						$message_content = $section->extract_body(MAILPARSE_EXTRACT_RETURN);

						$tmpname = ParserFile::makeTempFilename();
						$bounce_attach = new ParserFile();
						$bounce_attach->setTempFile($tmpname, 'message/delivery-status');
						@file_put_contents($tmpname, $message_content);
						$bounce_attach->file_size = filesize($tmpname);
						$bounce_attach->mime_type = 'message/delivery-status';
						$bounce_attach_filename = sprintf("delivery_status_%s.txt", uniqid());
						$message->files[$bounce_attach_filename] = $bounce_attach;
						$handled = true;
						break;

					case 'message/feedback-report':
						$content_filename = sprintf("feedback_report_%s.txt", uniqid());
						break;
						
					case 'message/rfc822':
						$message_content = $section->extract_body(MAILPARSE_EXTRACT_RETURN);

						$tmpname = ParserFile::makeTempFilename();
						$rfc_attach = new ParserFile();
						$rfc_attach->setTempFile($tmpname, $content_type);
						@file_put_contents($tmpname, $message_content);
						$rfc_attach->file_size = filesize($tmpname);
						$rfc_attach->mime_type = $content_type;
						$rfc_attach_filename = sprintf("attached_message_%s.txt", uniqid());
						$message->files[$rfc_attach_filename] = $rfc_attach;
						$handled = true;
						break;
						
					case 'image/gif':
					case 'image/jpg':
					case 'image/jpeg':
					case 'image/png':
						if(isset($section->data['content-id']) && !empty($section->data['content-id'])) {
							$content_filename = DevblocksPlatform::strToPermalink($section->data['content-id']);
						} else {
							$content_filename = sprintf("image_%s", uniqid());
						}
						
						switch(DevblocksPlatform::strLower($content_type)) {
							case 'image/gif':
								$content_filename .= ".gif";
								break;
							case 'image/jpg':
							case 'image/jpeg':
								$content_filename .= ".jpg";
								break;
							case 'image/png':
								$content_filename .= ".png";
								break;
						}
						break;
				}
				
				if($handled)
					unset($mime_parts[$section_idx]);
			}
		}
		
		// Handle file attachments
		
		$settings = DevblocksPlatform::services()->pluginSettings();
		$is_attachments_enabled = $settings->get('cerberusweb.core',CerberusSettings::ATTACHMENTS_ENABLED,CerberusSettingsDefaults::ATTACHMENTS_ENABLED);
		$attachments_max_size = $settings->get('cerberusweb.core',CerberusSettings::ATTACHMENTS_MAX_SIZE,CerberusSettingsDefaults::ATTACHMENTS_MAX_SIZE);
		
		if($is_attachments_enabled)
		foreach($mime_parts as $section_idx => $section) {
			// Pre-check: If the part is larger than our max allowed attachments, skip before writing
			if(isset($section->data['disposition-size']) && $section->data['disposition-size'] > ($attachments_max_size * 1024000)) {
				continue;
			}
			
			$content_type = DevblocksPlatform::strLower(isset($section->data['content-type']) ? $section->data['content-type'] : '');
			$content_filename = self::_getMimePartFilename($section);
			
			if(DevblocksPlatform::strLower(@$section->data['content-type']) == 'multipart/signed') {
				$content_filename = sprintf('signed_message_source_%s.txt', uniqid());
				continue;
			}
			
			$attach = new ParseFileBuffer($section);
			
			// Make sure our attachment is under the max preferred size
			if(filesize($attach->tmpname) > ($attachments_max_size * 1024000)) {
				@unlink($attach->tmpname);
				continue;
			}
			
			if(empty($content_filename))
				$content_filename = sprintf("unnamed_attachment_%s", uniqid());
			
			// If the filename already exists, make it unique
			if(isset($message->files[$content_filename])) {
				$file_parts = pathinfo($content_filename);
				$counter = 1;
				
				do {
					$content_filename = sprintf("%s-%d%s%s",
						$file_parts['filename'],
						$counter++,
						!empty($file_parts['extension']) ? '.' : '',
						$file_parts['extension']
					);
					
				} while(isset($message->files[$content_filename]));
			}
			
			$message->files[$content_filename] = $attach;
		}
		
		// Generate the plaintext part (if necessary)
		
		if(empty($message->body) && !empty($message->htmlbody)) {
			$message->body = DevblocksPlatform::services()->string()->htmlToText($message->htmlbody);
		}

		return $message;
	}

	static private function _handleMimepartTextPlain($section, CerberusParserMessage $message) {
		$text = $section->extract_body(MAILPARSE_EXTRACT_RETURN);
		
		if(isset($section->data['charset']) && !empty($section->data['charset'])) {
			
			// Extract inline bounces as attachments
			
			$bounce_token = '------ This is a copy of the message, including all the headers. ------';
			
			if(false !== ($bounce_pos = @mb_strpos($text, $bounce_token, 0, $section->data['charset']))) {
				$bounce_text = mb_substr($text, $bounce_pos + strlen($bounce_token), strlen($text), $section->data['charset']);
				$text = mb_substr($text, 0, $bounce_pos, $section->data['charset']);
				
				$bounce_text = self::convertEncoding($bounce_text);
				
				$tmpname = ParserFile::makeTempFilename();
				$rfc_attach = new ParserFile();
				$rfc_attach->setTempFile($tmpname,'message/rfc822');
				@file_put_contents($tmpname, $bounce_text);
				$rfc_attach->file_size = filesize($tmpname);
				$rfc_attach->mime_type = 'text/plain';
				$rfc_attach_filename = sprintf("attached_message_%s.txt", uniqid());
				$message->files[$rfc_attach_filename] = $rfc_attach;
				unset($rfc_attach);
			}
			
			$message->body_encoding = $section->data['charset'];
			$text = self::convertEncoding($text, $section->data['charset']);
		}
		
		$message->body .= $text;
		
		return true;
	}
	
	static private function _handleMimepartTextHtml($section, CerberusParserMessage $message) {
		$text = $section->extract_body(MAILPARSE_EXTRACT_RETURN);
		
		if(isset($section->data['charset']) && !empty($section->data['charset'])) {
			$text = self::convertEncoding($text, $section->data['charset']);
		}
		
		if(0 != strlen(trim($text)))
			$message->htmlbody .= $text;
		
		return true;
	}
	
	static private function _handleMailFilteringAutomations(CerberusParserModel &$model) {
		$event_handler = DevblocksPlatform::services()->ui()->eventHandler();
		
		$error = null;
		
		$initial_state = [
			'email_sender__context' => CerberusContexts::CONTEXT_ADDRESS,
			'email_sender_id' => @$model->getSenderAddressModel()->id ?: 0,
			'email_subject' => $model->getSubject(),
			'email_headers' => $model->getHeaders(),
			'email_body' => $model->getMessage()->body,
			'email_body_html' => $model->getMessage()->htmlbody,
			'email_recipients' => $model->getRecipients(),
			'parent_ticket__context' => CerberusContexts::CONTEXT_TICKET,
			'parent_ticket_id' => $model->getTicketId(),
		];
		
		$handlers_dict = DevblocksDictionaryDelegate::instance($initial_state);
		
		$handlers_kata = DAO_AutomationEvent::getKataByName('mail.filter', null);
		
		if($handlers_kata) {
			$handlers = $event_handler->parse($handlers_kata, $handlers_dict, $error);
			
			$behavior_callback = function (Model_TriggerEvent $behavior) use (&$model) {
				$return = DevblocksDictionaryDelegate::instance([
					'__exit' => 'exit',
				]);
				
				$event = new Model_DevblocksEvent(
					Event_MailReceivedByApp::ID,
					[
						'parser_model' => &$model,
						'_whisper' => [
							'_trigger_id' => [$behavior->id],
						],
					]
				);
				
				if (false == ($results = DevblocksPlatform::services()->event()->trigger($event)))
					return $return;
				
				$result = array_shift($results);
				
				if (!($result instanceof DevblocksDictionaryDelegate))
					return $return;
				
				$result->unset('_types');
				$result->unset('_labels');
				$result->unset('__trigger');
				$result->clearCaches();
				
				if(null === ($model_result = $result->getKeyPath('_parser_model')))
					return $return;
				
				/* @var $model CerberusParserModel */
				
				// Convert the behavior response to an automation result
				
				$pre_actions = $model_result->getPreActions();
				
				if(array_key_exists('reject', $pre_actions) && $pre_actions['reject']) {
					$return->setKeyPath('__exit', 'return');
					$return->setKeyPath('__return.reject', true);
				}
				
				if(array_key_exists('attachment_filters', $pre_actions) && $pre_actions['attachment_filters']) {
					$return->setKeyPath('__exit', 'return');
					$model->addPreAction('attachment_filters', $pre_actions['attachment_filters']);
				}
				
				return $return;
			};
			
			$results = $event_handler->handleEach(
				AutomationTrigger_MailFilter::ID,
				$handlers,
				$initial_state,
				$error,
				function(DevblocksDictionaryDelegate $result) {
					// Continue unless we rejected the message
					return false == $result->getKeyPath('__return.reject');
				},
				$behavior_callback
			);
			
			foreach($results as $result) { /** @var DevblocksDictionaryDelegate $result */
				if(null != ($reject = $result->getKeyPath('__return.reject'))) {
					$model->addPreAction('reject');
					break;
				}
				
				if(null != ($subject = $result->getKeyPath('__return.set.email_subject'))) {
					$model->setSubject($subject);
				}
				
				if(null != ($body = $result->getKeyPath('__return.set.email_body'))) {
					$model->getMessage()->body = $body;
					unset($body);
				}
				
				if(null != ($html_body = $result->getKeyPath('__return.set.email_body_html'))) {
					$model->getMessage()->htmlbody = $html_body;
					unset($html_body);
				}
				
				if(null != ($headers = $result->getKeyPath('__return.set.headers'))) {
					if(is_array($headers)) {
						foreach($headers as $k => $v) {
							$model->getMessage()->headers[$k] = $v;
						}
						
						$model->getMessage()->raw_headers = '';
						$model->getMessage()->build();
					}
				}
				
				if(null != ($custom_fields = $result->getKeyPath('__return.set.custom_fields'))) {
					if(is_array($custom_fields)) {
						foreach($custom_fields as $cf_key => $cf_value) {
							$model->getMessage()->custom_fields[] = [
								'field_id' => $cf_key,
								'context' => CerberusContexts::CONTEXT_TICKET,
								'value' => $cf_value,
							];
						}
					}
				}
			}
		}
		
		return $model;
	}
	
	/**
	 * @param CerberusParserMessage $message
	 * @return integer
	 */
	static public function parseMessage(CerberusParserMessage $message, $options=[]) {
		/*
		 * options:
		 * 'no_autoreply'
		 */
		$logger = DevblocksPlatform::services()->log();
		$url_writer = DevblocksPlatform::services()->url();

		// Make sure the object is well-formatted and ready to send
		$message->build();
		
		// Parse headers into $model
		$model = new CerberusParserModel($message); /* @var $model CerberusParserModel */
		
		// Pre-parse mail filters
		// Changing the incoming message through a VA
		self::_handleMailFilteringAutomations($model);
		
		// Log headers after bots run
		
		$log_headers = array(
			'from' => 'From',
			'to' => 'To',
			'delivered-to' => 'Delivered-To',
			'envelope-to' => 'Envelope-To',
			'subject' => 'Subject',
			'date' => 'Date',
			'message-id' => 'Message-Id',
			'in-reply-to' => 'In-Reply-To',
			'references' => 'References',
		);
		
		foreach($log_headers as $log_header => $log_label) {
			if(!isset($message->headers[$log_header]))
				continue;
			
			$vals = $message->headers[$log_header];
			
			if(!is_array($vals))
				$vals = array($vals);
			
			foreach($vals as $val)
				$logger->info("[Parser] [Headers] " . $log_label . ': ' . $val);
		}
		
		$pre_actions = $model->getPreActions();
		
		// Reject?
		if(isset($pre_actions['reject'])) {
			$logger->warn('Rejecting based on bot filtering.');
			return null;
		}
		
		
		// Filter attachments?
		if(isset($pre_actions['attachment_filters']) && !empty($message->files)) {
			foreach($message->files as $filename => $file) {
				$matched = false;
				
				foreach($pre_actions['attachment_filters'] as $filter) {
					if($matched)
						continue;
					
					if(!isset($filter['oper']) || !isset($filter['value']))
						continue;
					
					$not = false;
					
					if(substr($filter['oper'],0,1)=='!') {
						$not = true;
						$filter['oper'] = substr($filter['oper'],1);
					}
					
					switch($filter['oper']) {
						case 'is':
							$matched = (0 == strcasecmp($filename, $filter['value']));
							break;
							
						case 'like':
						case 'regexp':
							if($filter['oper'] == 'like')
								$pattern = DevblocksPlatform::strToRegExp($filter['value']);
							else
								$pattern = $filter['value'];
							
							$matched = (@preg_match($pattern, $filename));
							break;
					}
					
					// Did we want a negative match?
					if(!$matched && $not)
						$matched = true;
					
					// Remove a matched attachment
					if($matched) {
						$logger->info(sprintf("Removing attachment '%s' based on bot filtering.", $filename));
						@unlink($file->tmpname);
						unset($message->files[$filename]);
						/** @noinspection PhpUnnecessaryStopStatementInspection */
						continue;
					}
				}
			}
		}
		
		if(false == ($validated = $model->validate()))
			return $validated; // false or null
		
		// Is it a worker reply from an external client?  If so, proxy
		if(false === ($relay_result = self::_checkRelayReply($model, $message)))
			return false;
		
		if($relay_result)
			return $relay_result;
		
		// New Ticket
		if($model->getIsNew()) {
			// Insert a bare minimum record so we can get a ticket ID back
			$fields = array(
				DAO_Ticket::CREATED_DATE => time(),
				DAO_Ticket::UPDATED_DATE => time(),
			);
			$model->setTicketId(DAO_Ticket::create($fields));
			
			if(null == $model->getTicketId()) {
				$logger->error("Problem saving ticket...");
				return false;
			}
			
			// [JAS]: Add requesters to the ticket
			$sender = $model->getSenderAddressModel();
			if(!empty($sender)) {
				DAO_Ticket::createRequester($sender->email, $model->getTicketId());
			}
			
			// Add the other TO/CC addresses to the ticket
			if(DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::PARSER_AUTO_REQ, CerberusSettingsDefaults::PARSER_AUTO_REQ)) {
				$destinations = $model->getRecipients();
				
				// [TODO] Multiple values in one insert
				if(is_array($destinations))
				foreach($destinations as $dest) {
					DAO_Ticket::createRequester($dest, $model->getTicketId());
				}
			}
			
			// Trigger automations first, and if we don't have an explicit `return` match then run routing rules
			
			$did_rule_match = false;
			$error = null;
			
			$initial_state = [
				'email_sender__context' => CerberusContexts::CONTEXT_ADDRESS,
				'email_sender_id' => @$model->getSenderAddressModel()->id ?: 0,
				'email_subject' => $model->getSubject(),
				'email_headers' => $model->getHeaders(),
				'email_body' => $model->getMessage()->body,
				'email_body_html' => $model->getMessage()->htmlbody,
				'email_recipients' => $model->getRecipients(),
				'parent_ticket__context' => CerberusContexts::CONTEXT_TICKET,
				'parent_ticket_id' => $model->getTicketId(),
			];
			
			$events_kata = DAO_AutomationEvent::getKataByName('mail.route');
			
			$handlers = DevblocksPlatform::services()->ui()->eventHandler()->parse(
				$events_kata,
				DevblocksDictionaryDelegate::instance($initial_state),
				$error
			);
			
			if(false === $handlers && $error) {
				error_log('[KATA] Invalid mail.route KATA: ' . $error);
				$handlers = [];
			}
			
			$automation_results = DevblocksPlatform::services()->ui()->eventHandler()->handleUntilReturn(
				AutomationTrigger_MailRoute::ID,
				$handlers,
				$initial_state,
				$error
			);
			
			if($automation_results instanceof DevblocksDictionaryDelegate) {
				if('return' == $automation_results->getKeyPath('__exit')) {
					$group_id = $automation_results->getKeyPath('__return.group_id');
					$group_name = $automation_results->getKeyPath('__return.group_name');
					$bucket_id = $automation_results->getKeyPath('__return.bucket_id');
					$bucket_name = $automation_results->getKeyPath('__return.bucket_name');
					
					if($bucket_id && null != ($model->setRouteBucket($bucket_id))) {
						$did_rule_match = true;
					
					} elseif($bucket_name && ($group_id || $group_name)) {
						if($group_name && !$group_id)
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
									$did_rule_match = true;
							}
						}
					
					} elseif($group_id) {
						if(null != ($model->setRouteGroup($group_id)))
							$did_rule_match = true;
						
					} elseif($group_name) {
						if(null != ($model->setRouteGroup(DAO_Group::getByName($group_name))))
							$did_rule_match = true;
					}
				}
			}
			
			// Only run legacy routing rules if no automations matched above
			
			if(!$did_rule_match) {
				// Routing new tickets
				if(null != ($routing_rules = Model_MailToGroupRule::getMatches(
					$model->getSenderAddressModel(),
					$message
				))) {
					
					// Update our model with the results of the routing rules
					if(is_array($routing_rules))
					foreach($routing_rules as $rule) {
						$rule->run($model);
					}
				}
			}
			
			// Last ditch effort to check for a default group to deliver to
			if(null == $model->getRouteGroup()) {
				if(null != ($default_group = DAO_Group::getDefaultGroup())) {
					$model->setRouteGroup($default_group);
					$model->setRouteBucket($default_group->getDefaultBucket());
				}
			}

			// Bounce if we can't set the group id
			if(null == $model->getRouteGroup()) {
				$logger->error("[Parser] Can't determine a default group to deliver to.");
				return false;
			}

		} // endif ($model->getIsNew())
		
		$fields = [
			DAO_Message::TICKET_ID => $model->getTicketId(),
			DAO_Message::CREATED_DATE => $model->getDate(),
			DAO_Message::ADDRESS_ID => $model->getSenderAddressModel()->id,
			DAO_Message::WORKER_ID => $model->isSenderWorker() ? $model->getSenderWorkerModel()->id : 0,
			DAO_Message::WAS_ENCRYPTED => $message->was_encrypted ? 1 : 0,
			DAO_Message::SIGNED_KEY_FINGERPRINT => $message->signed_key_fingerprint,
			DAO_Message::SIGNED_AT => $message->signed_at,
		];
		
		if(!isset($message->headers['message-id'])) {
			$new_message_id = sprintf("<%s.%s@%s>", 
				base_convert(intval(microtime(true))*1000, 10, 36),
				base_convert(bin2hex(openssl_random_pseudo_bytes(8)), 16, 36),
				DevblocksPlatform::getHostname()
			);
			$message->headers['message-id'] = $new_message_id;
			$message->raw_headers = sprintf("Message-Id: %s\r\n%s",
				$new_message_id,
				$message->raw_headers
			);
		}
		
		$fields[DAO_Message::HASH_HEADER_MESSAGE_ID] = sha1($message->headers['message-id']);
		
		$model->setMessageId(DAO_Message::create($fields));

		$message_id = $model->getMessageId();
		if(empty($message_id)) {
			$logger->error("Problem saving message to database...");
			return false;
		}
		
		// Save message content
		Storage_MessageContent::put($model->getMessageId(), $message->body);
		
		// Save headers
		DAO_MessageHeaders::upsert($model->getMessageId(), $message->raw_headers);
		
		// [mdf] Loop through files to insert attachment records in the db, and move temporary files
		foreach ($message->files as $filename => $file) { /* @var $file ParserFile */
			$handled = false;
			
			switch($file->mime_type) {
				case 'message/delivery-status':
					$message_content = file_get_contents($file->tmpname);
					
					$status_code = 0;
					
					if(preg_match('#Diagnostic-Code:(.*);\s*(\d+) (\d\.\d\.\d)(.*)#i', $message_content, $matches)) {
						$status_code = intval($matches[2]);
						
					} elseif(preg_match('#Status:\s*(\d\.\d\.\d)#i', $message_content, $matches)) {
						$status_code = intval(DevblocksPlatform::strAlphaNum($matches[1]));
					}
					
					// Permanent failure
					if($status_code >= 500 && $status_code < 600) {
						$logger->info(sprintf("[Parser] This is a permanent failure delivery-status (%d)", $status_code));
						
						$matches = [];
						
						if(preg_match('#Original-Recipient:\s*(.*)#i', $message_content, $matches)) {
							// Use the original address if provided
						} elseif (preg_match('#Final-Recipient:\s*(.*)#i', $message_content, $matches)) {
							// Otherwise, try to fall back to the final recipient
						}
						
						if(is_array($matches) && isset($matches[1])) {
							$entry = explode(';', trim($matches[1]));
							
							if(2 == count($entry)) {
								// Set this sender to defunct
								if(false != ($bouncer = DAO_Address::lookupAddress(trim($entry[1]), true))) {
									DAO_Address::update($bouncer->id, array(
										DAO_Address::IS_DEFUNCT => 1,
									));
									
									$logger->info(sprintf("[Parser] Setting %s to defunct", $bouncer->email));
									
									// ... and add them as a requester
									DAO_Ticket::createRequester($bouncer->email, $model->getTicketId());
								}
							}
							
							// [TODO] Find the original message-id ?
							
							// Nuke the attachment?
							$handled = true;
						}
					}
					//}
					break;
			}
			
			if(!$handled) {
				$sha1_hash = sha1_file($file->getTempFile(), false);
				
				// Dupe detection
				if(null == ($file_id = DAO_Attachment::getBySha1Hash($sha1_hash, $file->file_size, $file->mime_type))) {
					$fields = array(
						DAO_Attachment::NAME => $filename,
						DAO_Attachment::MIME_TYPE => $file->mime_type,
						DAO_Attachment::STORAGE_SHA1HASH => $sha1_hash,
					);
					$file_id = DAO_Attachment::create($fields);

					// Store the content in the new file
					if(null !== ($fp = fopen($file->getTempFile(), 'rb'))) {
						Storage_Attachments::put($file_id, $fp);
						fclose($fp);
					}
				}
				
				// Link
				if($file_id) {
					$file->parsed_attachment_id = $file_id;
					DAO_Attachment::addLinks(CerberusContexts::CONTEXT_MESSAGE, $model->getMessageId(), $file_id);
				}
				
				// Rewrite any inline content-id images in the HTML part
				if(isset($file->info) && isset($file->info['content-id'])) {
					$inline_cid_url = $url_writer->write('c=files&id=' . $file_id . '&name=' . urlencode($filename), true);
					$message->htmlbody = str_replace('cid:' . $file->info['content-id'], $inline_cid_url, $message->htmlbody);
				}
			}
			
			// Remove the temp file
			@unlink($file->tmpname);
		}
		
		// Save the HTML part as an 'original_message.html' attachment
		// [TODO] Make attaching the HTML part an optional config option (off by default)
		if(!empty($message->htmlbody)) {
			$sha1_hash = sha1($message->htmlbody, false);
			
			if(null == ($file_id = DAO_Attachment::getBySha1Hash($sha1_hash, strlen($message->htmlbody), 'text/html'))) {
				$fields = array(
					DAO_Attachment::NAME => 'original_message.html',
					DAO_Attachment::MIME_TYPE => 'text/html',
					DAO_Attachment::STORAGE_SHA1HASH => $sha1_hash,
				);
				
				if(false != ($file_id = DAO_Attachment::create($fields))) {
					Storage_Attachments::put($file_id, $message->htmlbody);
				}
			}
			
			// Link the HTML part to the message
			if(!empty($file_id)) {
				DAO_Attachment::addLinks(CerberusContexts::CONTEXT_MESSAGE, $model->getMessageId(), $file_id);
				
				// This built-in field is faster than searching for the HTML part again in the attachments
				DAO_Message::update($message_id, array(
					DAO_Message::HTML_ATTACHMENT_ID => $file_id,
				));
			}
		}
		
		// Pre-load custom fields
		
		$cf_values = [];
		$custom_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_TICKET);
		$custom_field_uris = array_column($custom_fields, 'id', 'uri');
		
		if(isset($message->custom_fields) && !empty($message->custom_fields))
		foreach($message->custom_fields as $cf_data) {
			if(!is_array($cf_data))
				continue;
			
			$cf_id = $cf_data['field_id'] ?? null;
			$cf_context = $cf_data['context'] ?? null;
			$cf_context_id = $cf_data['context_id'] ?? null;
			$cf_val = $cf_data['value'] ?? null;
			
			if($cf_id && !is_numeric($cf_id) && is_string($cf_id)) {
				if(array_key_exists($cf_id, $custom_field_uris)) {
					$cf_id = $custom_field_uris[$cf_id];
				} else {
					$cf_id = 0;
				}
			}
			
			if(!$cf_id || !array_key_exists($cf_id, $custom_fields))
				continue;
			
			// If we're setting fields on the ticket, find the ticket ID
			if($cf_context == CerberusContexts::CONTEXT_TICKET && empty($cf_context_id))
				$cf_context_id = $model->getTicketId();
			
			if((is_array($cf_val) && !empty($cf_val))
				|| (!is_array($cf_val) && 0 != strlen($cf_val))) {
					$cf_key = sprintf("%s:%d", $cf_context, $cf_context_id);
					
					if(!isset($cf_values[$cf_key]))
						$cf_values[$cf_key] = [];
					
					$cf_values[$cf_key][$cf_id] = $cf_val;
			}
		}
		
		if(!empty($cf_values))
		foreach($cf_values as $ctx_pair => $cf_data) {
			list($cf_context, $cf_context_id) = explode(':', $ctx_pair, 2);
			DAO_CustomFieldValue::formatAndSetFieldValues($cf_context, $cf_context_id, $cf_data);
		}

		// If the sender was previously defunct, remove the flag
		
		$sender = $model->getSenderAddressModel();
		if($sender->is_defunct && $sender->id) {
			DAO_Address::update($sender->id, array(
				DAO_Address::IS_DEFUNCT => 0,
			));
			
			$logger->info(sprintf("[Parser] Setting %s as no longer defunct", $sender->email));
		}
		
		// Finalize our new ticket details (post-message creation)
		/* @var $model CerberusParserModel */
		if($model->getIsNew()) {
			$deliver_to_group = $model->getRouteGroup();
			$deliver_to_bucket = $model->getRouteBucket();
			
			$change_fields = array(
				DAO_Ticket::MASK => CerberusApplication::generateTicketMask(),
				DAO_Ticket::SUBJECT => $model->getSubject(),
				DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_OPEN,
				DAO_Ticket::FIRST_WROTE_ID => intval($model->getSenderAddressModel()->id),
				DAO_Ticket::LAST_WROTE_ID => intval($model->getSenderAddressModel()->id),
				DAO_Ticket::CREATED_DATE => time(),
				DAO_Ticket::UPDATED_DATE => time(),
				DAO_Ticket::ORG_ID => intval($model->getSenderAddressModel()->contact_org_id),
				DAO_Ticket::FIRST_MESSAGE_ID => $model->getMessageId(),
				DAO_Ticket::LAST_MESSAGE_ID => $model->getMessageId(),
				DAO_Ticket::GROUP_ID => $deliver_to_group->id, // this triggers move rules
				DAO_Ticket::BUCKET_ID => $deliver_to_bucket->id ?? $deliver_to_group->getDefaultBucket()->id, // this triggers move rules
			);
			
			// Spam probabilities
			// [TODO] Check headers?
			if(false !== ($spam_data = CerberusBayes::calculateContentSpamProbability($model->getSubject() . ' ' . $message->body))) {
				$change_fields[DAO_Ticket::SPAM_SCORE] = $spam_data['probability'];
				$change_fields[DAO_Ticket::INTERESTING_WORDS] = $spam_data['interesting_words'];
			}
		
			// Save properties
			if(array_key_exists('properties', $options)) {
				$ticket_properties = $options['properties'];
				
				$ticket_properties['ticket_id'] = $model->getTicketId();
				$ticket_properties['message_id'] = $model->getMessageId();
				
				$ticket = $model->getTicketModel();
				
				DAO_Ticket::updateWithMessageProperties($ticket_properties, $ticket, $change_fields);
				
				$model->setTicketModel($ticket);
				
			} else {
				if($change_fields)
					DAO_Ticket::update($model->getTicketId(), $change_fields);
			}
			
		} else { // Reply
		
			// Re-open and update our date on new replies
			DAO_Ticket::update($model->getTicketId(),array(
				DAO_Ticket::UPDATED_DATE => time(),
				DAO_Ticket::STATUS_ID => Model_Ticket::STATUS_OPEN,
				DAO_Ticket::LAST_MESSAGE_ID => $model->getMessageId(),
				DAO_Ticket::LAST_WROTE_ID => $model->getSenderAddressModel()->id,
			));
			// [TODO] The TICKET_CUSTOMER_REPLY should be sure of this message address not being a worker
		}
		
		/*
		 * Log activity (ticket.message.inbound)
		 */
		$entry = array(
			//{{actor}} replied to ticket {{target}}
			'message' => 'activities.ticket.message.inbound',
			'variables' => array(
				'target' => $model->getSubject(),
				),
			'urls' => array(
				'target' => sprintf("ctx://%s:%d", CerberusContexts::CONTEXT_TICKET, $model->getTicketId()),
				)
		);
		CerberusContexts::logActivity('ticket.message.inbound', CerberusContexts::CONTEXT_TICKET, $model->getTicketId(), $entry, CerberusContexts::CONTEXT_ADDRESS, $model->getSenderAddressModel()->id);

		AutomationTrigger_MailReceived::trigger($model->getMessageId(), $model->getIsNew());
		
		// Trigger Mail Received
		Event_MailReceived::trigger($model->getMessageId());
		
		// Trigger Group Mail Received
		Event_MailReceivedByGroup::trigger($model->getMessageId(), $model->getRouteGroup()->id);
		
		// Trigger Watcher Mail Received
		$context_watchers = CerberusContexts::getWatchers(CerberusContexts::CONTEXT_TICKET, $model->getTicketId());
		
		// Include the owner

		@$ticket_owner_id = $model->getTicketModel()->owner_id;
		
		if(!empty($ticket_owner_id) && !isset($context_watchers[$ticket_owner_id]))
			$context_watchers[$ticket_owner_id] = true;

		if(is_array($context_watchers) && !empty($context_watchers))
		foreach(array_unique(array_keys($context_watchers)) as $watcher_id) {
			Event_MailReceivedByWatcher::trigger($model->getMessageId(), $watcher_id);
		}
		
		return $model->getTicketId();
	}
	
	static function convertEncoding($text, $charset=null) {
		$has_iconv = extension_loaded('iconv') ? true : false;
		$charset = DevblocksPlatform::strLower($charset);
		
		// Otherwise, fall back to mbstring's auto-detection
		mb_detect_order('iso-2022-jp-ms, iso-2022-jp, utf-8, iso-8859-1, windows-1252');
		
		// Normalize charsets
		switch($charset) {
			case 'us-ascii':
				$charset = 'ascii';
				break;
				
			case 'win-1252':
				$charset = 'windows-1252';
				break;
				
			case 'ks_c_5601-1987':
			case 'ks_c_5601-1992':
			case 'ks_c_5601-1998':
			case 'ks_c_5601-2002':
				$charset = 'cp949';
				break;
				
			case NULL:
				$charset = mb_detect_encoding($text);
				break;
		}
		
		// If we're starting with Windows-1252, convert some special characters
		if(0 == strcasecmp($charset, 'windows-1252')) {
		
			// http://www.toao.net/48-replacing-smart-quotes-and-em-dashes-in-mysql
			$text = str_replace(
				array(chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)),
				array("'", "'", '"', '"', '-', '--', '...'),
				$text
			);
		}
		
		// If we can use iconv, do so first
		if($has_iconv && false !== ($out = @iconv($charset, LANG_CHARSET_CODE . '//TRANSLIT//IGNORE', $text))) {
			return $out;
		}
		
		// Otherwise, try mbstring
		if(@mb_check_encoding($text, $charset)) {
			if(false !== ($out = mb_convert_encoding($text, LANG_CHARSET_CODE, $charset)))
				return $out;
			
			// Try with the internal charset
			if(false !== ($out = mb_convert_encoding($text, LANG_CHARSET_CODE)))
				return $out;
		}
		
		return $text;
	}
	
	static function fixQuotePrintableArray($input, $encoding=null) {
		array_walk_recursive($input, function(&$v, $k) {
			if(!is_string($v))
				return;
			
			$v = CerberusParser::fixQuotePrintableString($v);
		});
		
		return $input;
	}
	
	static function fixQuotePrintableString($input, $encoding=null) {
		$out = '';
		
		// Make a single element array from any !array input
		if(!is_array($input))
			$input = array($input);

		if(is_array($input))
		foreach($input as $str) {
			$out .= !empty($out) ? ' ' : '';
			$out .= CerberusMail::decodeMimeHeader($str);
		}

		// Strip invalid characters in our encoding
		if(!mb_check_encoding($out, LANG_CHARSET_CODE))
			$out = mb_convert_encoding($out, LANG_CHARSET_CODE, LANG_CHARSET_CODE);
		
		return $out;
	}
	
	/**
	 * @return false|null|int
	 */
	private static function _checkRelayReply(CerberusParserModel $model, CerberusParserMessage $message) {
		$logger = DevblocksPlatform::services()->log();
		
		$relay_disabled = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::RELAY_DISABLE, CerberusSettingsDefaults::RELAY_DISABLE);
		
		if($relay_disabled)
			return null;
		
		if(!$model->isSenderWorker())
			return null;
		
		if(false == ($relay_auth_header = $model->isWorkerRelayReply()))
			return null;
		
		if(false == ($proxy_ticket = $model->getTicketModel()))
			return null;
		
		if(false == ($proxy_worker = $model->getSenderWorkerModel()))
			return null;
		
		if($proxy_worker->is_disabled)
			return null;
		
		$logger->info("[Worker Relay] Handling an external worker relay for " . $model->getSenderAddressModel()->email);
		
		$is_authenticated = false;
		
		// If it's a watcher reply
		$relay_auth_disabled = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::RELAY_DISABLE_AUTH, CerberusSettingsDefaults::RELAY_DISABLE_AUTH);
		
		if($relay_auth_disabled) {
			$is_authenticated = true;
			
		} else {
			if($relay_auth_header && $proxy_worker instanceof Model_Worker)
				$is_authenticated = $model->isValidAuthHeader($relay_auth_header, $proxy_worker);
		}
		
		// Compare worker signature, then auth
		if($is_authenticated) {
			$logger->info("[Worker Relay] Worker authentication successful. Proceeding.");
			
			if(!empty($proxy_ticket)) {
				$in_reply_message_id = is_numeric($is_authenticated) ? $is_authenticated : $proxy_ticket->last_message_id;
				self::_createRelayReply($model, $message, $proxy_ticket, $proxy_worker, $in_reply_message_id);
				
				return $proxy_ticket->id;
			}
			
		} else { // failed worker auth
			// [TODO] Bounce
			$logger->error("[Worker Relay] Worker authentication failed. Ignoring.");
			return false;
		}
		
		return null;
	}
	
	private static function _createRelayReply(CerberusParserModel $model, CerberusParserMessage $message, Model_Ticket $proxy_ticket, Model_Worker $proxy_worker, $in_reply_message_id) {
		// Log activity as the worker
		CerberusContexts::pushActivityDefaultActor(CerberusContexts::CONTEXT_WORKER, $proxy_worker->id);
		
		$parser_message = $model->getMessage();
		$attachment_file_ids = [];
		
		foreach($parser_message->files as $filename => $file) {
			/* @var $file ParserFile */
			if(0 == strcasecmp($filename, 'original_message.html'))
				continue;
			
			// Dupe detection
			$sha1_hash = sha1_file($file->tmpname, false);
			
			if(false == ($file_id = DAO_Attachment::getBySha1Hash($sha1_hash, $file->file_size, $file->mime_type))) {
				$fields = [
					DAO_Attachment::NAME => $filename,
					DAO_Attachment::MIME_TYPE => $file->mime_type,
					DAO_Attachment::STORAGE_SHA1HASH => $sha1_hash,
				];
				
				$file_id = DAO_Attachment::create($fields);
			}
			
			if($file_id) {
				$attachment_file_ids[] = $file_id;
			}
			
			if(null !== ($fp = fopen($file->getTempFile(), 'rb'))) {
				Storage_Attachments::put($file_id, $fp);
				fclose($fp);
			}
			
			@unlink($file->getTempFile());
		}
		
		// Properties
		$properties = [
			'ticket_id' => $proxy_ticket->id,
			'message_id' => $in_reply_message_id,
			'forward_files' => $attachment_file_ids,
			'link_forward_files' => true,
			'worker_id' => $proxy_worker->id,
		];
		
		// Clean the reply body
		$body = '';
		$lines = DevblocksPlatform::parseCrlfString($message->body, true);
		
		$state = '';
		$comments = [];
		$comment_ptr = null;
		$notes = [];
		$note_ptr = null;
		
		$matches = [];
		
		if(is_array($lines))
		foreach($lines as $line) {
			// Ignore quoted relay comments
			if(preg_match('/[\s\>]*\s*##/', $line))
				continue;
			
			// Insert worker sig for this bucket
			if(preg_match('/^#sig/', $line, $matches)) {
				$state = '#sig';
				$group = DAO_Group::get($proxy_ticket->group_id);
				$sig = $group->getReplySignature($proxy_ticket->bucket_id, $proxy_worker, false);
				$body .= $sig . PHP_EOL;
				
			} elseif(preg_match('/^#start (.*)/', $line, $matches)) {
				switch(@$matches[1]) {
					case 'comment':
						$state = '#comment_block';
						$comment_ptr =& $comments[];
						break;
					
					case 'note':
						$state = '#note_block';
						$note_ptr =& $notes[];
						break;
				}
				
			} elseif(preg_match('/^#end/', $line, $matches)) {
				$state = '';
				
			} elseif(preg_match('/^#cut/', $line, $matches)) {
				$state = '#cut';
				
			} elseif(preg_match('/^#watch/', $line, $matches)) {
				$state = '#watch';
				CerberusContexts::addWatchers(CerberusContexts::CONTEXT_TICKET, $proxy_ticket->id, $proxy_worker->id);
				
			} elseif(preg_match('/^#unwatch/', $line, $matches)) {
				$state = '#unwatch';
				CerberusContexts::removeWatchers(CerberusContexts::CONTEXT_TICKET, $proxy_ticket->id, $proxy_worker->id);
				
			} elseif(preg_match('/^#noreply/', $line, $matches)) {
				$state = '#noreply';
				$properties['dont_send'] = 1;
				$properties['dont_keep_copy'] = 1;
				
			} elseif(preg_match('/^#status (.*)/', $line, $matches)) {
				$state = '#status';
				switch(DevblocksPlatform::strLower($matches[1])) {
					case 'o':
					case 'open':
						$properties['status_id'] = Model_Ticket::STATUS_OPEN;
						break;
					case 'w':
					case 'waiting':
						$properties['status_id'] = Model_Ticket::STATUS_WAITING;
						break;
					case 'c':
					case 'closed':
						$properties['status_id'] = Model_Ticket::STATUS_CLOSED;
						break;
				}
				
			} elseif(preg_match('/^#reopen (.*)/', $line, $matches)) {
				$state = '#reopen';
				$properties['ticket_reopen'] = $matches[1];
				
			} elseif(preg_match('/^#comment(.*)/', $line, $matches)) {
				$state = '#comment';
				$comment_ptr =& $comments[];
				$comment_ptr = @$matches[1] ? ltrim($matches[1]) : '';
				
			} else {
				
				switch($state) {
					case '#comment':
						if(empty($line)) {
							$state = '';
						} else {
							$comment_ptr .= ' ' . ltrim($line);
						}
						break;
					
					case '#comment_block':
						$comment_ptr .= $line . "\n";
						break;
						
					case '#note_block':
						$note_ptr .= $line . "\n";
						break;
				}
				
				if(!in_array($state, array('#cut', '#comment', '#comment_block', '#note_block'))) {
					$body .= $line . PHP_EOL;
				}
			}
		}
		
		$properties['content'] = ltrim($body);
		
		$draft_fields = DAO_MailQueue::getFieldsFromMessageProperties($properties);
		$draft_fields[DAO_MailQueue::NAME] = sprintf('Watcher reply by %s on [#%s] %s', $proxy_worker->getName(), $proxy_ticket->mask, $proxy_ticket->subject);
		$draft_fields[DAO_MailQueue::HINT_TO] = '(participants)';
		$draft_fields[DAO_MailQueue::TYPE] = Model_MailQueue::TYPE_TICKET_REPLY;
		$draft_fields[DAO_MailQueue::IS_QUEUED] = 1;
		$draft_fields[DAO_MailQueue::QUEUE_DELIVERY_DATE] = time() + 300;
		
		if(false == ($draft_id = DAO_MailQueue::create($draft_fields)))
			return false;
		
		if(false == ($draft = DAO_MailQueue::get($draft_id)))
			return false;
		
		// If successful, run post actions
		if(false !== ($response = $draft->send())) {
			// Comments
			if (is_array($comments)) {
				foreach ($comments as $comment) {
					DAO_Comment::create([
						DAO_Comment::CREATED => time(),
						DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
						DAO_Comment::OWNER_CONTEXT_ID => $proxy_worker->id,
						DAO_Comment::COMMENT => $comment,
						DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_TICKET,
						DAO_Comment::CONTEXT_ID => $proxy_ticket->id,
					]);
				}
			}
			
			// Notes
			if (
				is_array($notes) 
				&& is_array($response) 
				&& CerberusContexts::CONTEXT_MESSAGE == ($response[0] ?? null)
				&& null !== ($response[1] ?? null)
			) {
				foreach ($notes as $note) {
					DAO_Comment::create([
						DAO_Comment::CREATED => time(),
						DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
						DAO_Comment::OWNER_CONTEXT_ID => $proxy_worker->id,
						DAO_Comment::COMMENT => $note,
						DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_MESSAGE,
						DAO_Comment::CONTEXT_ID => $response[1],
					]);
				}
			}
		}
		
		// Stop logging activity as the worker
		CerberusContexts::popActivityDefaultActor();
		
		return true;
	}
};
