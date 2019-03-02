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

/**
 * Based on: https://raw.githubusercontent.com/Mailgarant/switfmailer-openpgp/master/OpenPGPSigner.php
 *
 */
class Cerb_SwiftPlugin_GPGSigner implements Swift_Signers_BodySigner {
	protected $micalg = 'SHA256';
	protected $encrypt = true;
	
	protected function createMessage(Swift_Message $message) {
		$mimeEntity = new Swift_Message('', $message->getBody(), $message->getContentType(), $message->getCharset());
		$mimeEntity->setChildren($message->getChildren());

		$messageHeaders = $mimeEntity->getHeaders();
		$messageHeaders->remove('Message-ID');
		$messageHeaders->remove('Date');
		$messageHeaders->remove('Subject');
		$messageHeaders->remove('MIME-Version');
		$messageHeaders->remove('To');
		$messageHeaders->remove('From');

		return $mimeEntity;
	}
	
	protected function getSignKey(Swift_Message $message) {
		if(false == ($gpg = DevblocksPlatform::services()->gpg()))
			return false;
		
		if(false == ($from = $message->getFrom()) || !is_array($from))
			return false;
		
		$email = key($from);
		
		if(false != ($keys = $gpg->keyinfo(sprintf("<%s>", $email))) && is_array($keys)) {
			foreach($keys as $key) {
				if($this->isValidKey($key, 'sign'))
				foreach($key['subkeys'] as $subkey) {
					if($this->isValidKey($subkey, 'sign')) {
						return $subkey['fingerprint'];
					}
				}
			}
		}
		
		return false;
	}
	
	protected function getRecipientKeys(Swift_Message $message) {
		$to = $message->getTo() ?: [];
		$cc = $message->getCc() ?: [];
		$bcc = $message->getBcc() ?: [];
		
		$recipients = $to + $cc	+ $bcc;
		
		if(!is_array($recipients) || empty($recipients))
			throw new Swift_SwiftException(sprintf('Error: No valid recipients for GPG encryption'));
		
		$fingerprints = [];
		
		foreach(array_keys($recipients) as $email) {
			$gpg = DevblocksPlatform::services()->gpg();
			$found = false;

			if(false != ($keys = $gpg->keyinfo(sprintf("<%s>", $email))) && is_array($keys)) {
				foreach($keys as $key) {
					if($this->isValidKey($key, 'encrypt'))
					foreach($key['subkeys'] as $subkey) {
						if($this->isValidKey($subkey, 'encrypt')) {
							$fingerprints[] = $subkey['fingerprint'];
							$found = true;
						}
					}
				}
			}
			
			if(!$found)
				throw new Swift_SwiftException(sprintf('Error: No recipient GPG public key for: %s', $email));
		}
		
		return $fingerprints;
	}
	
	protected function isValidKey($key, $purpose) {
		return !(
			$key['disabled'] 
			|| $key['expired'] 
			|| $key['revoked'] 
			|| (
				$purpose == 'sign' 
				&& !$key['can_sign']
				) 
			|| (
				$purpose == 'encrypt' 
				&& !$key['can_encrypt']
			)
		);
	}
	
	protected function signWithPGP($plaintext, $key_fingerprint) {
		$gpg = DevblocksPlatform::services()->gpg();
		
		if(false != ($signed = $gpg->sign($plaintext, $key_fingerprint)))
			return $signed;
		
		throw new Swift_SwiftException('Error: Failed to sign message (passphrase on the secret key?)');
	}
	
	protected function encryptWithPGP($plaintext, $key_fingerprints) {
		$gpg = DevblocksPlatform::services()->gpg();
		
		if(false != ($encrypted = $gpg->encrypt($plaintext, $key_fingerprints)))
			return $encrypted;
		
		throw new Swift_SwiftException('Error: Failed to encrypt message');
	}
	
	/**
	 * Change the Swift_Signed_Message to apply the singing.
	 *
	 * @param Swift_Message $message
	 *
	 * @return self
	 */
	public function signMessage(Swift_Message $message) {
		$sign_key = $this->getSignKey($message);
		
		if(false == ($recipient_keys = $this->getRecipientKeys($message)))
			throw new Swift_SwiftException('Error: No recipient GPG public keys for encryption.');
		
		$originalMessage = $this->createMessage($message);
		$message->setChildren([]);
		$message->setEncoder(Swift_DependencyContainer::getInstance()->lookup('mime.rawcontentencoder'));
		
		if($sign_key) {
			$type = $message->getHeaders()->get('Content-Type');
			$type->setValue('multipart/signed');
			$type->setParameters([
				'micalg' => sprintf('pgp-%s', DevblocksPlatform::strLower($this->micalg)),
				'protocol' => 'application/pgp-signature',
				'boundary' => $message->getBoundary(),
			]);
			
			$signed_body = $originalMessage->toString();
			
			$lines = DevblocksPlatform::parseCrlfString(rtrim($signed_body), true);
			
			array_walk($lines, function(&$line) {
				$line = rtrim($line) . "\r\n";
			});
			
			$signed_body = rtrim(implode('', $lines) . "\r\n");
			
			$signature = $this->signWithPGP($signed_body, $sign_key);
			
			$body = <<< EOD
This is an OpenPGP/MIME signed message (RFC 4880 and 3156)

--{$message->getBoundary()}
$signed_body
--{$message->getBoundary()}
Content-Type: application/pgp-signature; name="signature.asc"
Content-Description: OpenPGP digital signature
Content-Disposition: attachment; filename="signature.asc"

$signature

--{$message->getBoundary()}--
EOD;

		} else { // No signature
			$body = $originalMessage->toString();
			
		}
		
		$message->setBody($body);
		
		if($this->encrypt) {
			if($sign_key) {
				$content = sprintf("%s\r\n%s", $message->getHeaders()->get('Content-Type')->toString(), $body);
			} else {
				$content = $body;
			}
			
			$encrypted_body = $this->encryptWithPGP($content, $recipient_keys);
			
			$type = $message->getHeaders()->get('Content-Type');
			$type->setValue('multipart/encrypted');
			$type->setParameters([
				'protocol' => 'application/pgp-encrypted',
				'boundary' => $message->getBoundary(),
			]);
			
			$body = <<< EOD
This is an OpenPGP/MIME encrypted message (RFC 4880 and 3156)

--{$message->getBoundary()}
Content-Type: application/pgp-encrypted
Content-Description: PGP/MIME version identification

Version: 1

--{$message->getBoundary()}
Content-Type: application/octet-stream; name="encrypted.asc"
Content-Description: OpenPGP encrypted message
Content-Disposition: inline; filename="encrypted.asc"

$encrypted_body

--{$message->getBoundary()}--
EOD;
		
			$message->setBody($body);
		}
		
		$message_headers = $message->getHeaders();
		$message_headers->removeAll('Content-Transfer-Encoding');
		
		return $this;
	}

	/**
	 * Return the list of header a signer might tamper.
	 *
	 * @return array
	 */
	public function getAlteredHeaders() {
		return ['Content-Type', 'Content-Transfer-Encoding', 'Content-Disposition', 'Content-Description'];
	}
	
	/**
	 * return $this
	 */
	public function reset() {
		return $this;
	}
};

class Cerb_SwiftPlugin_TransportExceptionLogger implements Swift_Events_TransportExceptionListener {
	private $_lastError = null;
	
	function exceptionThrown(Swift_Events_TransportExceptionEvent $evt) {
		$exception = $evt->getException();
		$this->_lastError = str_replace(array("\r","\n"),array('',' '), $exception->getMessage());
	}
	
	function getLastError() {
		return $this->_lastError;
	}
	
	function clear() {
		$this->_lastError = null;
	}
};

class CerberusMail {
	private function __construct() {}
	
	static function parseRfcAddresses($string, $exclude_controlled_addresses=false) {
		$results = [];
		$string = rtrim(str_replace(';',',',$string),' ,');
		@$parsed = imap_rfc822_parse_adrlist($string, 'localhost');
		
		$exclude_list = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::PARSER_AUTO_REQ_EXCLUDE, CerberusSettingsDefaults::PARSER_AUTO_REQ_EXCLUDE);
		@$excludes = DevblocksPlatform::parseCrlfString($exclude_list);
		
		if(is_array($parsed))
		foreach($parsed as $parsed_addy) {
			@$mailbox = DevblocksPlatform::strLower($parsed_addy->mailbox);
			@$host = DevblocksPlatform::strLower($parsed_addy->host);
			@$personal = isset($parsed_addy->personal) ? $parsed_addy->personal : null;
			
			if(empty($mailbox) || empty($host))
				continue;
			
			if(0 == strcasecmp($mailbox, 'invalid_address'))
				continue;
			
			if(0 == strcasecmp($host, '.syntax-error.'))
				continue;
			
			// Are we excluding Cerb controlled addresses?
			if($exclude_controlled_addresses) {
				$check_address = $mailbox.'@'.$host;
				
				// If this is a local address and we're excluding them, skip it
				if(DAO_Address::isLocalAddress($check_address))
					continue;
				
				$skip = false;
				
				// Filter explicit excludes
				if(is_array($excludes) && !empty($excludes))
				foreach($excludes as $excl_pattern) {
					if(@preg_match(DevblocksPlatform::parseStringAsRegExp($excl_pattern), $check_address))
						$skip = true;
				}
				
				if($skip)
					continue;
			}
			
			$results[$mailbox . '@' . $host] = array(
				'full_email' => !empty($personal) ? imap_rfc822_write_address($mailbox, $host, $personal) : imap_rfc822_write_address($mailbox, $host, null),
				'email' => $mailbox . '@' . $host,
				'mailbox' => $mailbox,
				'host' => $host,
				'personal' => $personal,
			);
		}
		
		@imap_errors();
		
		return $results;
	}
	
	static private function _parseCustomHeaders(array $headers) {
		if(!is_array($headers))
			return [];
		
		$results = [];
		
		foreach($headers as $header) {
			@list($name, $value) = explode(':', $header);
			
			$name = trim(DevblocksPlatform::strLower($name));
			$value = trim($value);
			
			$results[$name] = $value;
		}
		
		return $results;
	}
	
	static function quickSend($to, $subject, $body, $from_addy=null, $from_personal=null, $custom_headers=[], $format=null, $html_template_id=null, $file_ids=[], $cc=null, $bcc=null) {
		try {
			$mail_service = DevblocksPlatform::services()->mail();
			$mail = $mail_service->createMessage();
			
			if(empty($from_addy) || empty($from_personal)) {
				if(false == ($replyto_default = DAO_Address::getDefaultLocalAddress()))
					throw new Exception("There is no default sender address.");
				
				if(empty($from_addy))
					$from_addy = $replyto_default->email;
			}
			
			$mail->setTo(DevblocksPlatform::parseCsvString($to));
			
			if(!empty($cc))
				$mail->setCc(DevblocksPlatform::parseCsvString($cc));
			
			if(!empty($bcc))
				$mail->setBcc(DevblocksPlatform::parseCsvString($bcc));

			$custom_headers = self::_parseCustomHeaders($custom_headers);
			
			// If we have a custom from, override the sender info
			if(isset($custom_headers['from'])) {
				if(false !== ($custom_froms = imap_rfc822_parse_adrlist($custom_headers['from'], '')) && !empty($custom_froms)) {
					$from_addy = $custom_froms[0]->mailbox . '@' . $custom_froms[0]->host;
					$from_personal = (isset($custom_froms[0]->personal) && $custom_froms[0]->personal != $from_addy) ? $custom_froms[0]->personal : null;
				}
				
				unset($custom_headers['from']);
			}
			
			if(!empty($from_personal)) {
				$mail->setFrom($from_addy, trim($from_personal));
			} else {
				$mail->setFrom($from_addy);
			}
			
			// If we have a custom subject, use it instead
			if(isset($custom_headers['subject'])) {
				$mail->setSubject($custom_headers['subject']);
				unset($custom_headers['subject']);
				
			} else {
				$mail->setSubject($subject);
			}
			
			$headers = $mail->getHeaders();
			
			$headers->addTextHeader('X-Mailer','Cerb ' . APP_VERSION . ' (Build '.APP_BUILD.')');
			
			// Add custom headers
			
			if(is_array($custom_headers) && !empty($custom_headers))
			foreach($custom_headers as $header_key => $header_val) {
				if(!empty($header_key) && !empty($header_val)) {
					if($headers->has($header_key))
						$headers->removeAll($header_key);
					
					$headers->addTextHeader(mb_convert_case($header_key, MB_CASE_TITLE), $header_val);
				}
			}
			
			// Body
			
			switch($format) {
				case 'parsedown':
					self::_generateMailBodyMarkdown($mail, $body, null, null, $html_template_id);
					break;
					
				default:
					$mail->setBody($body);
					break;
			}
			
			// Attachments
			
			if(!empty($file_ids) && is_array($file_ids)) {
				foreach($file_ids as $file_id) {
					// Attach the file
					if(false != ($attachment = DAO_Attachment::get($file_id))) {
						if(false !== ($fp = DevblocksPlatform::getTempFile())) {
							if(false !== $attachment->getFileContents($fp)) {
								$attach = Swift_Attachment::fromPath(DevblocksPlatform::getTempFileInfo($fp), $attachment->mime_type);
								$attach->setFilename($attachment->name);
								$mail->attach($attach);
								fclose($fp);
							}
						}
					}
				}
			}
			
			// [TODO] Report when the message wasn't sent.
			// [TODO] We can use '$failedRecipients' for this
			if(!$mail_service->send($mail)) {
				return false;
			}
			
		} catch (Exception $e) {
			return false;
		}
		
		return true;
	}

	static function compose($properties) {
		/*
		'group_id'
		'bucket_id'
		'worker_id'
		'owner_id'
		'org_id'
		'to'
		'cc'
		'bcc'
		'subject'
		'content'
		'content_format'
		'content_saved'
		'content_sent'
		'html_template_id'
		'files'
		'forward_files'
		'status_id'
		'ticket_reopen'
		'dont_send'
		'draft_id'
		'gpg_encrypt'
		 */
		
		@$group_id = $properties['group_id'];
		@$bucket_id = intval($properties['bucket_id']);
		
		@$is_broadcast = intval($properties['is_broadcast']);
		
		@$worker_id = $properties['worker_id'];
		$worker = null;
		
		if(empty($worker_id) && null != ($worker = CerberusApplication::getActiveWorker()))
			$worker_id = $worker->id;

		// Worker
		if(!empty($worker_id))
			$worker = DAO_Worker::get($worker_id);
		
		// Group
		if(null == ($group = DAO_Group::get($group_id)))
			return;
		
		// Bucket
		if(!$bucket_id || false == ($bucket = DAO_Bucket::get($bucket_id)) || $bucket->group_id != $group->id)
			$bucket = $group->getDefaultBucket();
		
		// Changing the outgoing message through a VA (global)
		Event_MailBeforeSent::trigger($properties, null, null, $group_id);
		
		// Changing the outgoing message through a VA (group)
		Event_MailBeforeSentByGroup::trigger($properties, null, null, $group_id);
		
		// Handle content appends and prepends
		self::_generateBodiesWithPrependsAppends($properties);
		
		@$org_id = $properties['org_id'];
		@$toStr = $properties['to'];
		@$cc = $properties['cc'];
		@$bcc = $properties['bcc'];
		@$subject = $properties['subject'];
		@$content_format = $properties['content_format'];
		@$content_saved = $properties['content_saved'];
		@$content_sent = $properties['content_sent'];
		@$html_template_id = $properties['html_template_id'];
		@$files = $properties['files'];
		@$embedded_files = [];
		@$forward_files = $properties['forward_files'];
		
		@$status_id = $properties['status_id'];
		@$ticket_reopen = $properties['ticket_reopen'];
		
		$from_replyto = $group->getReplyTo($bucket->id);
		$personal = $group->getReplyPersonal($bucket->id, $worker);
		
		$mask = CerberusApplication::generateTicketMask();

		if(empty($subject)) $subject = '(no subject)';
		
		// add mask to subject if group setting calls for it
		@$group_has_subject = intval(DAO_GroupSettings::get($group_id,DAO_GroupSettings::SETTING_SUBJECT_HAS_MASK,0));
		@$group_subject_prefix = DAO_GroupSettings::get($group_id,DAO_GroupSettings::SETTING_SUBJECT_PREFIX,'');
		$prefix = sprintf("[%s#%s] ",
			!empty($group_subject_prefix) ? ($group_subject_prefix.' ') : '',
			$mask
		);
		$subject_mailed = (sprintf('%s%s',
			$group_has_subject ? $prefix : '',
			$subject
		));
		
		// [JAS]: Replace any semi-colons with commas (people like using either)
		$toList = CerberusMail::parseRfcAddresses($toStr);
		
		
		try {
			$mail_service = DevblocksPlatform::services()->mail();
			$email = $mail_service->createMessage();

			// To
			if(is_array($toList))
			foreach($toList as $k => $v) {
				if(!empty($v['personal'])) {
					$email->addTo($k, $v['personal']);
				} else {
					$email->addTo($k);
				}
			}
			
			// Cc
			$ccList = CerberusMail::parseRfcAddresses($cc);
			if(is_array($ccList) && !empty($ccList)) {
				foreach($ccList as $k => $v) {
					if(!empty($v['personal'])) {
						$email->addCc($k, $v['personal']);
					} else {
						$email->addCc($k);
					}
				}
			}
			
			// Bcc
			$bccList = CerberusMail::parseRfcAddresses($bcc);
			if(is_array($bccList) && !empty($bccList)) {
				foreach($bccList as $k => $v) {
					if(!empty($v['personal'])) {
						$email->addBcc($k, $v['personal']);
					} else {
						$email->addBcc($k);
					}
				}
			}
			
			if(!empty($personal)) {
				$email->setFrom($from_replyto->email, $personal);
			} else {
				$email->setFrom($from_replyto->email);
			}
			
			$email->setSubject($subject_mailed);
			
			$headers = $email->getHeaders();
			
			$headers->addTextHeader('X-Mailer','Cerb ' . APP_VERSION . ' (Build '.APP_BUILD.')');
			
			// Custom headers
			
			if(isset($properties['headers']) && is_array($properties['headers']))
			foreach($properties['headers'] as $header_key => $header_val) {
				if(!empty($header_key) && is_string($header_key) && is_string($header_val)) {
					if(NULL == ($header = $headers->get($header_key))) {
						$headers->addTextHeader($header_key, $header_val);
					} else {
						$header->setValue($header_val);
					}
				}
			}
			
			// Body
			
			switch($content_format) {
				case 'parsedown':
					$embedded_files = self::_generateMailBodyMarkdown($email, $content_sent, $group_id, $bucket->id, $html_template_id);
					break;
					
				default:
					$email->setBody($content_sent);
					break;
			}
			
			// Mime Attachments
			if (is_array($files) && !empty($files)) {
				foreach ($files['tmp_name'] as $idx => $file) {
					if(empty($file) || empty($files['name'][$idx]))
						continue;
	
					$email->attach(Swift_Attachment::fromPath($file)->setFilename($files['name'][$idx]));
				}
			}
			
			// Forward Attachments
			if(!empty($forward_files) && is_array($forward_files)) {
				foreach($forward_files as $file_id) {
					if(false != ($attachment = DAO_Attachment::get($file_id))) {
						if(false !== ($fp = DevblocksPlatform::getTempFile())) {
							if(false !== $attachment->getFileContents($fp)) {
								$attach = Swift_Attachment::fromPath(DevblocksPlatform::getTempFileInfo($fp), $attachment->mime_type);
								$attach->setFilename($attachment->name);
								$email->attach($attach);
								fclose($fp);
							}
						}
					}
				}
			}
			
			$outgoing_mail_headers = $email->getHeaders()->toString();
			$outgoing_message_id = $email->getHeaders()->get('message-id')->getFieldBody();
			
			// Encryption
			if(isset($properties['gpg_encrypt']) && $properties['gpg_encrypt']) {
				$signer = new Cerb_SwiftPlugin_GPGSigner();
				$email->attachSigner($signer);
			}
			
			if(!empty($toList) && (!isset($properties['dont_send']) || empty($properties['dont_send']))) {
				if(!$mail_service->send($email)) {
					throw new Exception('Mail failed to send: unknown reason');
				}
			}
	
		} catch (Exception $e) {
			@$draft_id = $properties['draft_id'];
			
			if(empty($draft_id)) {
				$params = array(
					'to' => $toStr,
					'group_id' => $group->id,
					'bucket_id' => $bucket->id,
				);
				
				if(!empty($cc))
					$params['cc'] = $cc;
					
				if(!empty($bcc))
					$params['bcc'] = $bcc;
					
				$fields = array(
					DAO_MailQueue::TYPE => Model_MailQueue::TYPE_COMPOSE,
					DAO_MailQueue::TICKET_ID => 0,
					DAO_MailQueue::WORKER_ID => intval($worker_id),
					DAO_MailQueue::UPDATED => time()+5, // small offset
					DAO_MailQueue::HINT_TO => $toStr,
					DAO_MailQueue::SUBJECT => $subject,
					DAO_MailQueue::BODY => $params['content'],
					DAO_MailQueue::PARAMS_JSON => json_encode($params),
					DAO_MailQueue::IS_QUEUED => !empty($worker) ? 0 : 1,
					DAO_MailQueue::QUEUE_DELIVERY_DATE => time(),
				);
				DAO_MailQueue::create($fields);
			}
			
			return false;
		}
		
		$fromAddressInst = CerberusApplication::hashLookupAddress($from_replyto->email, true);
		$fromAddressId = $fromAddressInst->id;
		
		// [TODO] this is redundant with the Parser code.  Should be refactored later
		
		// Organization ID from first requester
		if(empty($org_id)) {
			reset($toList);
			if(null != ($first_req = DAO_Address::lookupAddress(key($toList),true))) {
				if(!empty($first_req->contact_org_id))
					$org_id = $first_req->contact_org_id;
			}
		}
		
		$fields = array(
			DAO_Ticket::MASK => $mask,
			DAO_Ticket::SUBJECT => $subject,
			DAO_Ticket::CREATED_DATE => time(),
			DAO_Ticket::FIRST_WROTE_ID => $fromAddressId,
			DAO_Ticket::LAST_WROTE_ID => $fromAddressId,
			DAO_Ticket::ORG_ID => intval($org_id),
			DAO_Ticket::IMPORTANCE => 50,
		);
		
		// "Next:" [TODO] This is highly redundant with CerberusMail::reply
		
		if(isset($properties['owner_id'])) {
			$fields[DAO_Ticket::OWNER_ID] = intval($properties['owner_id']);
		}
		
		if(isset($ticket_reopen) && !empty($ticket_reopen)) {
			if(is_numeric($ticket_reopen) && false != (@$reopen_at = strtotime('now', $ticket_reopen))) {
				$fields[DAO_Ticket::REOPEN_AT] = $reopen_at;
			} else if(false !== (@$reopen_at = strtotime($ticket_reopen))) {
				$fields[DAO_Ticket::REOPEN_AT] = $reopen_at;
			}
		}

		// End "Next:"
		
		$ticket_id = DAO_Ticket::create($fields);
		
		// Save a copy of the sent HTML body
		$html_body_id = 0;
		if($content_format == 'parsedown') {
			if(false !== ($html = DevblocksPlatform::parseMarkdown($content_saved))) {
				$html_body_id = DAO_Attachment::create([
					DAO_Attachment::NAME => 'original_message.html',
					DAO_Attachment::MIME_TYPE => 'text/html',
					DAO_Attachment::STORAGE_SHA1HASH => sha1($html),
				]);
				
				Storage_Attachments::put($html_body_id, $html);
				
				$embedded_files[] = $html_body_id;
			}
			
			// Convert to a plaintext part
			$content_saved = self::_generateTextFromMarkdown($content_saved);
		}

		$fields = array(
			DAO_Message::TICKET_ID => $ticket_id,
			DAO_Message::CREATED_DATE => time(),
			DAO_Message::ADDRESS_ID => $fromAddressId,
			DAO_Message::IS_OUTGOING => 1,
			DAO_Message::WORKER_ID => intval($worker_id),
			DAO_Message::IS_BROADCAST => $is_broadcast ? 1 : 0,
			DAO_Message::IS_NOT_SENT => @$properties['dont_send'] ? 1 : 0,
			DAO_Message::HASH_HEADER_MESSAGE_ID => sha1($outgoing_message_id),
			DAO_Message::WAS_ENCRYPTED => !empty(@$properties['gpg_encrypt']) ? 1 : 0,
			DAO_Message::HTML_ATTACHMENT_ID => $html_body_id,
		);
		$message_id = DAO_Message::create($fields);
		
		// Content
		Storage_MessageContent::put($message_id, $content_saved);

		// Set recipients to requesters
		foreach(array_keys($toList) as $to_addy) {
			DAO_Ticket::createRequester($to_addy, $ticket_id);
		}
		
		// Headers
		$email->getHeaders()->addTextHeader('X-CerberusCompose', 1);
		DAO_MessageHeaders::upsert($message_id, $outgoing_mail_headers);
		
		// add files to ticket
		if (is_array($files) && !empty($files)) {
			reset($files);
			foreach ($files['tmp_name'] as $idx => $file) {
				if(empty($file) || empty($files['name'][$idx]) || !file_exists($file))
					continue;

				// Dupe detection
				@$sha1_hash = sha1_file($file, false);
				
				if(false == ($file_id = DAO_Attachment::getBySha1Hash($sha1_hash, $files['name'][$idx]))) {
					$fields = array(
						DAO_Attachment::NAME => $files['name'][$idx],
						DAO_Attachment::MIME_TYPE => $files['type'][$idx],
						DAO_Attachment::STORAGE_SHA1HASH => $sha1_hash,
					);
					$file_id = DAO_Attachment::create($fields);
					
					// Content
					if(null !== ($fp = fopen($file, 'rb'))) {
						Storage_Attachments::put($file_id, $fp);
						fclose($fp);
					}
				}

				// Link
				if($file_id)
					DAO_Attachment::addLinks(CerberusContexts::CONTEXT_MESSAGE, $message_id, $file_id);
				
				@unlink($file);
			}
		}

		// Forwarded attachments
		if(isset($properties['link_forward_files']) && !empty($properties['link_forward_files'])) {
			// Attachments
			if(is_array($forward_files) && !empty($forward_files)) {
				DAO_Attachment::addLinks(CerberusContexts::CONTEXT_MESSAGE, $message_id, $forward_files);
			}
		}
		
		// Link embedded files
		if(isset($embedded_files) && is_array($embedded_files) && !empty($embedded_files)) {
			DAO_Attachment::addLinks(CerberusContexts::CONTEXT_MESSAGE, $message_id, $embedded_files);
		}
		
		// Finalize ticket
		$fields = array(
			DAO_Ticket::FIRST_MESSAGE_ID => $message_id,
			DAO_Ticket::LAST_MESSAGE_ID => $message_id,
		);

		// Status
		if(in_array($status_id, array(Model_Ticket::STATUS_WAITING, Model_Ticket::STATUS_CLOSED)))
			$fields[DAO_Ticket::STATUS_ID] = $status_id;
		
		// Move last, so the event triggers properly
		$fields[DAO_Ticket::GROUP_ID] = $group->id;
		$fields[DAO_Ticket::BUCKET_ID] = $bucket->id;
		
		DAO_Ticket::update($ticket_id, $fields);
		
		// Train as not spam
		CerberusBayes::markTicketAsNotSpam($ticket_id);
		
		// Custom fields
		@$custom_fields = isset($properties['custom_fields']) ? $properties['custom_fields'] : [];
		if(is_array($custom_fields) && !empty($custom_fields)) {
			DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_TICKET, $ticket_id, $custom_fields);
		}
		
		// Events
		if(!empty($message_id) && !empty($group_id)) {
			// After message sent (global)
			Event_MailAfterSent::trigger($message_id, $group_id);
			
			// After message sent in group
			Event_MailAfterSentByGroup::trigger($message_id, $group_id);

			// Mail received
			Event_MailReceived::trigger($message_id);
			
			// Mail received by group
			Event_MailReceivedByGroup::trigger($message_id, $group_id);
		}
		
		return intval($ticket_id);
	}
	
	static function sendTicketMessage($properties=[]) {
		/*
		'draft_id'
		'message_id'
		'is_forward'
		'is_broadcast'
		'subject'
		'to'
		'cc'
		'bcc'
		'content',
		'content_format', // markdown, parsedown, html
		'content_saved',
		'content_sent',
		'html_template_id'
		'headers'
		'files'
		'forward_files'
		'link_forward_files'
		'status_id'
		'ticket_reopen'
		'group_id'
		'bucket_id'
		'owner_id'
		'worker_id'
		'is_autoreply'
		'custom_fields'
		'gpg_encrypt'
		'dont_send'
		'dont_keep_copy'
		*/

		try {
			// objects
			$mail_service = DevblocksPlatform::services()->mail();
			$mail = $mail_service->createMessage();
			
			@$reply_message_id = $properties['message_id'];
			
			if(null == ($message = DAO_Message::get($reply_message_id)))
				return;
				
			$ticket_id = $message->ticket_id;
			
			// Ticket
			if(null == ($ticket = DAO_Ticket::get($ticket_id)))
				return;
				
			// Group
			if(null == ($group = DAO_Group::get($ticket->group_id)))
				return;
			
			$mail->generateId();
			$outgoing_message_id = $mail->getHeaders()->get('message-id')->getFieldBody();
			$properties['outgoing_message_id'] = $outgoing_message_id;
			
			// Changing the outgoing message through a VA (global)
			Event_MailBeforeSent::trigger($properties, $message->id, $ticket->id, $group->id);
			
			// Changing the outgoing message through a VA (group)
			Event_MailBeforeSentByGroup::trigger($properties, $message->id, $ticket->id, $group->id);
			
			// Handle content appends and prepends
			self::_generateBodiesWithPrependsAppends($properties);
			
			// Re-read properties
			@$content_format = $properties['content_format'];
			@$content_saved = $properties['content_saved'];
			@$content_sent = $properties['content_sent'];
			@$html_template_id = intval($properties['html_template_id']);
			@$files = $properties['files'];
			@$is_forward = $properties['is_forward'];
			@$is_broadcast = $properties['is_broadcast'];
			@$forward_files = $properties['forward_files'];
			@$embedded_files = [];
			@$worker_id = $properties['worker_id'];
			@$subject = $properties['subject'];
			
			@$is_autoreply = $properties['is_autoreply'];
			
			$message_headers = DAO_MessageHeaders::getAll($reply_message_id);

			$from_replyto = $group->getReplyTo($ticket->bucket_id);
			$from_personal = $group->getReplyPersonal($ticket->bucket_id, $worker_id);
			
			/*
			 * If this ticket isn't spam trained
			 * and our outgoing message isn't an autoreply
			 * and a worker sent this
			 */
			if($ticket->spam_training == CerberusTicketSpamTraining::BLANK
				&& empty($is_autoreply)
				&& !empty($worker_id)) {
				CerberusBayes::markTicketAsNotSpam($ticket_id);
			}
				
			// Headers
			if(!empty($from_personal)) {
				$mail->setFrom($from_replyto->email, $from_personal);
			} else {
				$mail->setFrom($from_replyto->email);
			}

			$headers = $mail->getHeaders();
			
			$headers->addTextHeader('X-Mailer','Cerb ' . APP_VERSION . ' (Build '.APP_BUILD.')');
	
			// Subject
			if(empty($subject)) $subject = $ticket->subject;
			
			if(!empty($is_forward)) { // forward
				$mail->setSubject($subject);
				
			} else { // reply
				@$group_has_subject = intval(DAO_GroupSettings::get($ticket->group_id, DAO_GroupSettings::SETTING_SUBJECT_HAS_MASK,0));
				@$group_subject_prefix = DAO_GroupSettings::get($ticket->group_id, DAO_GroupSettings::SETTING_SUBJECT_PREFIX,'');
				
				$prefix = sprintf("[%s#%s] ",
					!empty($group_subject_prefix) ? ($group_subject_prefix.' ') : '',
					$ticket->mask
				);
				
				$mail->setSubject(sprintf('Re: %s%s',
					$group_has_subject ? $prefix : '',
					$subject
				));
			}
			
			// References
			if(!empty($message) && false !== (@$in_reply_to = $message_headers['message-id'])) {
				$headers->addTextHeader('References', $in_reply_to);
				$headers->addTextHeader('In-Reply-To', $in_reply_to);
			}
	
			// Default requester reply
			if(empty($properties['to']) && !$is_forward) {
				// Auto-reply handling (RFC-3834 compliant)
				if(!empty($is_autoreply))
					$headers->addTextHeader('Auto-Submitted','auto-replied');
				
				// Recipients
				$requesters = DAO_Ticket::getRequestersByTicket($ticket_id);
				
				if(is_array($requesters))
				foreach($requesters as $requester) { /* @var $requester Model_Address */
					$first_email = DevblocksPlatform::strLower($requester->email);
					$first_split = explode('@', $first_email);
			
					if(!is_array($first_split) || count($first_split) != 2)
						continue;
			
					// Ourselves?
					if(DAO_Address::isLocalAddressId($requester->id))
						continue;

					if($is_autoreply) {
						// If return-path is blank
						if(isset($message_headers['return-path']) && $message_headers['return-path'] == '<>')
							continue;
						
						// Ignore autoresponses to autoresponses
						if(isset($message_headers['auto-submitted']) && $message_headers['auto-submitted'] != 'no')
							continue;
	
						// Bulk mail?
						if(isset($message_headers['precedence']) &&
							($message_headers['precedence'] == 'list' || $message_headers['precedence'] == 'junk' || $message_headers['precedence'] == 'bulk'))
							continue;
					}
						
					// Ignore bounces
					if($first_split[0] == "postmaster" || $first_split[0] == "mailer-daemon")
						continue;
						
					// Auto-reply just to the initial requester
					$mail->addTo($requester->email);
				}
				
			// Forward or overload
			} elseif(!empty($properties['to'])) {
				// To
				$aTo = CerberusMail::parseRfcAddresses($properties['to']);
				if(is_array($aTo))
				foreach($aTo as $k => $v) {
					if(!empty($v['personal'])) {
						$mail->addTo($k, $v['personal']);
					} else {
						$mail->addTo($k);
					}
				}
			}
			
			// Ccs
			if(!empty($properties['cc'])) {
				$aCc = CerberusMail::parseRfcAddresses($properties['cc']);
				if(is_array($aCc))
				foreach($aCc as $k => $v) {
					if(!empty($v['personal'])) {
						$mail->addCc($k, $v['personal']);
					} else {
						$mail->addCc($k);
					}
				}
			}
			
			// Bccs
			if(!empty($properties['bcc'])) {
				$aBcc = CerberusMail::parseRfcAddresses($properties['bcc']);
				if(is_array($aBcc))
				foreach($aBcc as $k => $v) {
					if(!empty($v['personal'])) {
						$mail->addBcc($k, $v['personal']);
					} else {
						$mail->addBcc($k);
					}
				}
			}
			
			// Custom headers
			
			if(isset($properties['headers']) && is_array($properties['headers']))
			foreach($properties['headers'] as $header_key => $header_val) {
				if(!empty($header_key) && is_string($header_key) && is_string($header_val)) {
					
					// Overrides
					switch(strtolower(trim($header_key))) {
						case 'to':
							if(false != ($addresses = CerberusMail::parseRfcAddresses($header_val)))
								foreach(array_keys($addresses) as $address)
									$mail->addTo($address);
							unset($properties['headers'][$header_key]);
							break;
						
						case 'cc':
							if(false != ($addresses = CerberusMail::parseRfcAddresses($header_val)))
								foreach(array_keys($addresses) as $address)
									$mail->addCc($address);
							unset($properties['headers'][$header_key]);
							break;
						
						case 'bcc':
							if(false != ($addresses = CerberusMail::parseRfcAddresses($header_val)))
								foreach(array_keys($addresses) as $address)
									$mail->addBcc($address);
							unset($properties['headers'][$header_key]);
							break;
							
						default:
							if(NULL == ($header = $headers->get($header_key))) {
								$headers->addTextHeader($header_key, $header_val);
								
							} else {
								if($header instanceof Swift_Mime_Headers_IdentificationHeader)
									continue;
								
								$header->setValue($header_val);
							}
							break;
					}
				}
			}
			
			// Body
			
			switch($content_format) {
				case 'parsedown':
					$embedded_files = self::_generateMailBodyMarkdown($mail, $content_sent, $ticket->group_id, $ticket->bucket_id, $html_template_id);
					break;
					
				default:
					$mail->setBody($content_sent);
					break;
			}
			
			// Mime Attachments
			if (is_array($files) && !empty($files)) {
				if(isset($files['tmp_name']))
				foreach($files['tmp_name'] as $idx => $file) {
					if(empty($file) || empty($files['name'][$idx]))
						continue;
	
					$mail->attach(Swift_Attachment::fromPath($file)->setFilename($files['name'][$idx]));
				}
			}
	
			// Forward Attachments
			if(!empty($forward_files) && is_array($forward_files)) {
				foreach($forward_files as $file_id) {
					// Attach the file
					if(false != ($attachment = DAO_Attachment::get($file_id))) {
						if(false !== ($fp = DevblocksPlatform::getTempFile())) {
							if(false !== $attachment->getFileContents($fp)) {
								$attach = Swift_Attachment::fromPath(DevblocksPlatform::getTempFileInfo($fp), $attachment->mime_type);
								$attach->setFilename($attachment->name);
								$mail->attach($attach);
								fclose($fp);
							}
						}
					}
				}
			}
			
			// Encryption
			if(isset($properties['gpg_encrypt']) && $properties['gpg_encrypt']) {
				$signer = new Cerb_SwiftPlugin_GPGSigner();
				$mail->attachSigner($signer);
			}

			// Send
			$recipients = $mail->getTo();
			$outgoing_mail_headers = $mail->getHeaders()->toString();
			$outgoing_message_id = $mail->getHeaders()->get('message-id')->getFieldBody();
			
			// If blank recipients or we're not supposed to send
			if(empty($recipients) || (isset($properties['dont_send']) && $properties['dont_send'])) {
				// ...do nothing
				
			} else { // otherwise send
				if(false === $mail_service->send($mail)) {
					throw new Exception('Mail not sent.');
				}
			}
			
		} catch (Exception $e) {
			@$draft_id = $properties['draft_id'];

			// Only if we weren't trying to send a draft already...
			if(empty($draft_id)) {
				$params = array(
					'in_reply_message_id' => $properties['message_id'],
				);
				
				if(isset($properties['cc']))
					$params['cc'] = $properties['cc'];
					
				if(isset($properties['bcc']))
					$params['bcc'] = $properties['bcc'];
					
				if(!empty($is_autoreply))
					$params['is_autoreply'] = true;
				
				if(!$mail->getTo()) {
					$hint_to = '(requesters)';
				} else {
					$hint_to = implode(', ', array_keys($mail->getTo()));
				}
				
				$fields = array(
					DAO_MailQueue::TYPE => empty($is_forward) ? Model_MailQueue::TYPE_TICKET_REPLY : Model_MailQueue::TYPE_TICKET_FORWARD,
					DAO_MailQueue::TICKET_ID => $properties['ticket_id'],
					DAO_MailQueue::WORKER_ID => intval($worker_id),
					DAO_MailQueue::UPDATED => time()+5, // small offset
					DAO_MailQueue::HINT_TO => $hint_to,
					DAO_MailQueue::SUBJECT => $subject,
					DAO_MailQueue::BODY => $properties['content'],
					DAO_MailQueue::PARAMS_JSON => json_encode($params),
					DAO_MailQueue::IS_QUEUED => empty($worker_id) ? 1 : 0,
					DAO_MailQueue::QUEUE_DELIVERY_DATE => time(),
				);
				$draft_id = DAO_MailQueue::create($fields);
			}
			
			$last_error_message = $mail_service->getLastErrorMessage();
			
			if($e instanceof Swift_TransportException && !$last_error_message) {
				$last_error_message = $e->getMessage();
			} elseif($e instanceof Swift_RfcComplianceException && !$last_error_message) {
				$last_error_message = $e->getMessage();
			}
			
			// If we have an error message, log it on the draft
			if($draft_id && !empty($last_error_message)) {
				$fields = array(
					DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_APPLICATION,
					DAO_Comment::OWNER_CONTEXT_ID => 0,
					DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_DRAFT,
					DAO_Comment::CONTEXT_ID => $draft_id,
					DAO_Comment::COMMENT => 'Error sending message: ' . $last_error_message,
					DAO_Comment::CREATED => time(),
				);
				DAO_Comment::create($fields);
			}
			
			return false;
		}
		
		$change_fields = [];
		
		$fromAddressInst = CerberusApplication::hashLookupAddress($from_replyto->email, true);
		$fromAddressId = $fromAddressInst->id;
		
		if((!isset($properties['dont_keep_copy']) || !$properties['dont_keep_copy'])
			&& empty($is_autoreply)) {
			$change_fields[DAO_Ticket::LAST_MESSAGE_ID] = $fromAddressId;
			$change_fields[DAO_Ticket::LAST_WROTE_ID] = $fromAddressId;
			$change_fields[DAO_Ticket::UPDATED_DATE] = time();
			
			// Only change the subject if not forwarding
			if(!empty($subject) && !$is_forward) {
				$change_fields[DAO_Ticket::SUBJECT] = $subject;
			}

			// Response time
			
			$response_epoch = ($ticket->created_date > $message->created_date) ? $ticket->created_date : $message->created_date;
			$response_time = (!empty($worker_id) ? (time() - $response_epoch) : 0);
			
			unset($response_epoch);
			
			// Save a copy of the sent HTML body
			$html_body_id = 0;
			if($content_format == 'parsedown') {
				if(false !== ($html = DevblocksPlatform::parseMarkdown($content_saved))) {
					$html_body_id = DAO_Attachment::create([
						DAO_Attachment::NAME => 'original_message.html',
						DAO_Attachment::MIME_TYPE => 'text/html',
						DAO_Attachment::STORAGE_SHA1HASH => sha1($html),
					]);
					
					Storage_Attachments::put($html_body_id, $html);
					
					$embedded_files[] = $html_body_id;
				}
				
				// Convert to a plaintext part
				$content_saved = self::_generateTextFromMarkdown($content_saved);
			}
			
			// Fields
			
			$fields = array(
				DAO_Message::TICKET_ID => $ticket_id,
				DAO_Message::CREATED_DATE => time(),
				DAO_Message::ADDRESS_ID => $fromAddressId,
				DAO_Message::IS_OUTGOING => 1,
				DAO_Message::WORKER_ID => (!empty($worker_id) ? $worker_id : 0),
				DAO_Message::RESPONSE_TIME => $response_time,
				DAO_Message::IS_BROADCAST => $is_broadcast ? 1 : 0,
				DAO_Message::IS_NOT_SENT => @$properties['dont_send'] ? 1 : 0,
				DAO_Message::HASH_HEADER_MESSAGE_ID => sha1($outgoing_message_id),
				DAO_Message::WAS_ENCRYPTED => !empty(@$properties['gpg_encrypt']) ? 1 : 0,
				DAO_Message::HTML_ATTACHMENT_ID => $html_body_id,
			);
			$message_id = DAO_Message::create($fields);
			
			// Store ticket.last_message_id
			$change_fields[DAO_Ticket::LAST_MESSAGE_ID] = $message_id;
			
			// First outgoing message?
			if(empty($ticket->first_outgoing_message_id) && !empty($worker_id)) {
				$change_fields[DAO_Ticket::FIRST_OUTGOING_MESSAGE_ID] = $message_id;
				$change_fields[DAO_Ticket::ELAPSED_RESPONSE_FIRST] = $response_time;
			}
			
			// Content
			Storage_MessageContent::put($message_id, $content_saved);

			// Save cached headers
			DAO_MessageHeaders::upsert($message_id, $outgoing_mail_headers);
			
			// Attachments
			if (is_array($files) && !empty($files)) {
				reset($files);
				if(isset($files['tmp_name']))
				foreach ($files['tmp_name'] as $idx => $file) {
					if(empty($file) || empty($files['name'][$idx]) || !file_exists($file))
						continue;

					// Dupe detection
					@$sha1_hash = sha1_file($file, false);
					
					if(false == ($file_id = DAO_Attachment::getBySha1Hash($sha1_hash, $files['name'][$idx]))) {
						// Create record
						$fields = array(
							DAO_Attachment::NAME => $files['name'][$idx],
							DAO_Attachment::MIME_TYPE => $files['type'][$idx],
							DAO_Attachment::STORAGE_SHA1HASH => $sha1_hash,
						);
						$file_id = DAO_Attachment::create($fields);
						
						// Content
						if(null !== ($fp = fopen($file, 'rb'))) {
							Storage_Attachments::put($file_id, $fp);
							fclose($fp);
						}
					}
					
					@unlink($file);

					// Link
					if($file_id)
						DAO_Attachment::addLinks(CerberusContexts::CONTEXT_MESSAGE, $message_id, $file_id);
				}
			}
			
			// Forwarded attachments
			if(isset($properties['link_forward_files']) && !empty($properties['link_forward_files'])) {
				// Attachments
				if(is_array($forward_files) && !empty($forward_files)) {
					DAO_Attachment::addLinks(CerberusContexts::CONTEXT_MESSAGE, $message_id, $forward_files);
				}
			}
			
			// Link embedded files
			if(isset($embedded_files) && is_array($embedded_files) && !empty($embedded_files)) {
				DAO_Attachment::addLinks(CerberusContexts::CONTEXT_MESSAGE, $message_id, $embedded_files);
			}
		}
		
		if(isset($properties['owner_id'])) {
			if(empty($properties['owner_id']) || null != (DAO_Worker::get($properties['owner_id']))) {
				$ticket->owner_id = intval($properties['owner_id']);
				$change_fields[DAO_Ticket::OWNER_ID] = $ticket->owner_id;
			}
		}
		
		// Post-Reply Change Properties

		if(isset($properties['status_id'])) {
			$reopen_at = 0;
			
			// Handle reopen date
			if(isset($properties['ticket_reopen']) && !empty($properties['ticket_reopen'])) {
				if(is_numeric($properties['ticket_reopen']) && false != (@$reopen_at = strtotime('now', $properties['ticket_reopen']))) {
				} else if(false !== (@$reopen_at = strtotime($properties['ticket_reopen']))) {
				}
			}
			
			switch($properties['status_id']) {
				case Model_Ticket::STATUS_OPEN:
					$change_fields[DAO_Ticket::STATUS_ID] = Model_Ticket::STATUS_OPEN;
					$change_fields[DAO_Ticket::REOPEN_AT] = 0;
					break;
				case Model_Ticket::STATUS_CLOSED:
					$change_fields[DAO_Ticket::STATUS_ID] = Model_Ticket::STATUS_CLOSED;
					
					if(isset($properties['ticket_reopen']))
						$change_fields[DAO_Ticket::REOPEN_AT] = $reopen_at;
					break;
				case Model_Ticket::STATUS_WAITING:
					$change_fields[DAO_Ticket::STATUS_ID] = Model_Ticket::STATUS_WAITING;
					
					if(isset($properties['ticket_reopen']))
						$change_fields[DAO_Ticket::REOPEN_AT] = $reopen_at;
					break;
			}
		}

		// Move
		if(isset($properties['group_id']) || isset($properties['bucket_id'])) {
			@$move_to_group_id = intval($properties['group_id']);
			@$move_to_bucket_id = intval($properties['bucket_id']);
			
			// Move to the new group if it exists
			if($move_to_group_id && false != ($move_to_group = DAO_Group::get($move_to_group_id)))
				$change_fields[DAO_Ticket::GROUP_ID] = $move_to_group_id;
			
			// Validate the given bucket id
			if($move_to_bucket_id 
				&& (false == ($move_to_bucket = DAO_Bucket::get($move_to_bucket_id)) 
					|| $move_to_bucket->group_id != $move_to_group->id)) {
					$move_to_bucket_id = 0;
			}
			
			// Move to the new bucket if it is an inbox, or it belongs to the group
			if(empty($move_to_bucket_id)) {
				$move_to_bucket = $move_to_group->getDefaultBucket();
				$move_to_bucket_id = $move_to_bucket->id;
			}
			
			if($move_to_bucket) {
				$change_fields[DAO_Ticket::BUCKET_ID] = $move_to_bucket->id;
			}
		}
			
		if(!empty($ticket_id) && !empty($change_fields)) {
			DAO_Ticket::update($ticket_id, $change_fields);
		}

		// Custom fields
		@$custom_fields = isset($properties['custom_fields']) ? $properties['custom_fields'] : [];
		if(is_array($custom_fields) && !empty($custom_fields)) {
			DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_TICKET, $ticket_id, $custom_fields, true, true, false);
		}
		
		// Events
		if(!empty($message_id)) {
			// After message sent (global)
			Event_MailAfterSent::trigger($message_id, $group->id);
			
			// After message sent in group
			Event_MailAfterSentByGroup::trigger($message_id, $group->id);
			
			// Mail received
			Event_MailReceived::trigger($message_id);
			
			// New message for group
			Event_MailReceivedByGroup::trigger($message_id, $group->id);

			// Watchers
			$context_watchers = CerberusContexts::getWatchers(CerberusContexts::CONTEXT_TICKET, $ticket_id);
			
			// Include the owner
			if(!empty($ticket->owner_id) && !isset($context_watchers[$ticket->owner_id]))
				$context_watchers[$ticket->owner_id] = true;

			if(is_array($context_watchers))
			foreach(array_unique(array_keys($context_watchers)) as $watcher_id) {
				Event_MailReceivedByWatcher::trigger($message_id, $watcher_id);
			}
		}
		
		/*
		 * Log activity (ticket.message.outbound)
		 */
		$entry = array(
			//{{actor}} responded to ticket {{target}}
			'message' => 'activities.ticket.message.outbound',
			'variables' => array(
				'target' => sprintf("[%s] %s", $ticket->mask, $ticket->subject),
				),
			'urls' => array(
				'target' => sprintf("ctx://%s:%s", CerberusContexts::CONTEXT_TICKET, $ticket->mask),
				)
		);
		CerberusContexts::logActivity('ticket.message.outbound', CerberusContexts::CONTEXT_TICKET, $ticket_id, $entry);
		
		if(isset($message_id))
			return $message_id;
		
		return true;
	}
	
	static function relay($message_id, $emails, $include_attachments = false, $content = null, $actor_context = null, $actor_context_id = null) {
		$mail_service = DevblocksPlatform::services()->mail();
		$settings = DevblocksPlatform::services()->pluginSettings();

		$relay_spoof_from = $settings->get('cerberusweb.core', CerberusSettings::RELAY_SPOOF_FROM, CerberusSettingsDefaults::RELAY_SPOOF_FROM);
		
		if(false == ($message = DAO_Message::get($message_id)))
			return;
		
		if(false == ($ticket = DAO_Ticket::get($message->ticket_id)))
			return;

		if(false == ($group = DAO_Group::get($ticket->group_id)))
			return;
		
		if(false == ($sender = $message->getSender()))
			return;

		$sender_name = $sender->getName();
		
		$url_writer = DevblocksPlatform::services()->url();
		$ticket_url = $url_writer->write(sprintf('c=profiles&w=ticket&mask=%s', $ticket->mask), true);

		if($relay_spoof_from) {
			$replyto = $group->getReplyTo($ticket->bucket_id);
		} else {
			// Use the default so our 'From:' is always consistent
			$replyto = DAO_Address::getDefaultLocalAddress();
		}
		
		$attachments = ($include_attachments)
			? DAO_Attachment::getByContextIds(CerberusContexts::CONTEXT_MESSAGE, $message->id)
			: []
			;
		
		if(empty($content)) {
			$content = sprintf("## Relayed from %s\r\n".
				"## Your reply to this message will be sent to the requesters.\r\n".
				"## Instructions: https://cerb.ai/guides/mail/relaying/\r\n".
				"##\r\n".
				"## %s%s wrote:\r\n".
				"%s",
				$ticket_url,
				(!empty($sender_name) ? ($sender_name . ' ') : ''),
				$sender->email,
				$message->getContent()
			);
		}
		
		if(is_array($emails))
		foreach($emails as $to) {
			try {
				if(false == ($to_model = DAO_Address::getByEmail($to)))
					continue;
				
				if(false == ($worker = $to_model->getWorker()))
					continue;
				
				$mail = $mail_service->createMessage();
				
				$mail->setTo(array($to));
	
				$headers = $mail->getHeaders(); /* @var $headers Swift_Mime_Header */
	
				if($relay_spoof_from) {
					$mail->setFrom($sender->email, !empty($sender_name) ? $sender_name : null);
					$mail->setReplyTo($replyto->email);
					
				} else {
					$mail->setFrom($replyto->email);
					$mail->setReplyTo($replyto->email);
				}

				// Subject
				$subject = sprintf("[relay #%s] %s", $ticket->mask, $ticket->subject);
				$mail->setSubject($subject);
				
				$headers->removeAll('message-id');
				
				$signed_message_id = CerberusMail::relaySign($message->id, $worker->id);
				$headers->addTextHeader('Message-Id', $signed_message_id);
				
				$headers->addTextHeader('X-CerberusRedirect','1');
				
				// [TODO] HTML body?
				
				$mail->setBody($content);
				
				// Files
				if(!empty($attachments) && is_array($attachments))
				foreach($attachments as $file) { /* @var $file Model_Attachment */
					//if('original_message.html' == $file->name)
					//	continue;
					
					if(false !== ($fp = DevblocksPlatform::getTempFile())) {
						if(false !== $file->getFileContents($fp)) {
							$attach = Swift_Attachment::fromPath(DevblocksPlatform::getTempFileInfo($fp), $file->mime_type);
							$attach->setFilename($file->name);
							$mail->attach($attach);
							fclose($fp);
						}
					}
				}
				
				$result = $mail_service->send($mail);
				unset($mail);
				
				/*
				 * Log activity (ticket.message.relay)
				 */
				$entry = array(
					//{{actor}} relayed ticket {{target}} to {{worker}} ({{worker_email}})
					'message' => 'activities.ticket.message.relay',
					'variables' => array(
						'target' => sprintf("[%s] %s", $ticket->mask, $ticket->subject),
						'worker' => $worker->getName(),
						'worker_email' => $to,
						),
					'urls' => array(
						'target' => sprintf("ctx://%s:%d", CerberusContexts::CONTEXT_TICKET, $ticket->id),
						'worker' => sprintf("ctx://%s:%d", CerberusContexts::CONTEXT_WORKER, $worker->id),
						)
				);
				CerberusContexts::logActivity('ticket.message.relay', CerberusContexts::CONTEXT_TICKET, $ticket->id, $entry, $actor_context, $actor_context_id);
				
				if(!$result)
					return false;
				
			} catch (Exception $e) {
				return false;
				
			}
		}
		
		return true;
	}
	
	/**
	 * Sign the message so we can verify that a future relay reply is genuine
	 * 
	 * @param integer $message_id
	 * @param integer $worker_id
	 * @param integer $time
	 * @return string
	 */
	static function relaySign($message_id, $worker_id) {
		$encrypt = DevblocksPlatform::services()->encryption();
		
		$string_to_encrypt = sprintf('%s:%s',
			base_convert($message_id, 10, 16),
			base_convert($worker_id, 10, 16)
		);
		
		$encrypted_header = $encrypt->encrypt($string_to_encrypt);
		
		$header_value = sprintf("<%s.%s@cerb>",
			rtrim($encrypted_header,'='),
			base_convert(time(), 10, 16)
		);
		
		return $header_value;
	}
	
	static function relayVerify($auth_header, $worker_id) {
		$encrypt = DevblocksPlatform::services()->encryption();
		$hits = [];
		
		// Procedural signing format
		if(@preg_match('#\<(.*?)\.([a-f0-9]+)\@cerb\>#', $auth_header, $hits)) {
			@$encrypted_message = $hits[1];
			
			if(!$encrypted_message)
				return false;
			
			$decrypted_message = $encrypt->decrypt($encrypted_message);
			
			$ids = explode(':', $decrypted_message, 2);
			
			$in_message_id = base_convert($ids[0], 16, 10);
			$in_worker_id = base_convert($ids[1], 16, 10);
			
			if($in_worker_id != $worker_id)
				return false;
			
			return $in_message_id;
			
		// Traditional signing format
		// @deprecated (Remove in 9.2)
		} else if(@preg_match('#\<([a-f0-9]+)\@cerb\d{0,1}\>#', $auth_header, $hits)) {
			@$hash = $hits[1];
			@$signed = substr($hash, 4, 40);
			@$message_id = hexdec(substr($hash, 44));
			
			$signed_compare = sha1($message_id . $worker_id . APP_DB_PASS);
			
			$is_authenticated = ($signed_compare == $signed);
			
			if($is_authenticated)
				return $message_id;
		}
		
		return false;
	}
	
	// [TODO] Encryption?
	static function resend(Model_Message $message, &$error=null, $only_return_source=false) {
		try {
			$mail_service = DevblocksPlatform::services()->mail();
			
			$mail = $mail_service->createMessage();
			$mail_headers = $mail->getHeaders();
			
			$headers = $message->getHeaders();
			$content = $message->getContent();
			$attachments = $message->getAttachments();
			
			$from = CerberusMail::parseRfcAddresses($headers['from']);
			$from = array_shift($from);
			$mail->setFrom($from['email'], $from['personal']);
			
			$tos = CerberusMail::parseRfcAddresses($headers['to']);
			foreach($tos as $to => $to_data)
				$mail->addTo($to, $to_data['personal']);
			
			if(isset($headers['cc'])) {
				$ccs = CerberusMail::parseRfcAddresses($headers['cc']);
				foreach($ccs as $cc => $cc_data)
					$mail->addCc($cc, $cc_data['personal']);
			}
			
			if(isset($headers['bcc'])) {
				$bccs = CerberusMail::parseRfcAddresses($headers['bcc']);
				foreach($bccs as $bcc => $bcc_data)
					$mail->addBcc($bcc, $bcc_data['personal']);
			}
			
			$mail->setDate(time());
			$mail->setSubject($headers['subject']);
			
			// Message-ID
			$mail->generateId();
			
			// Reuse message-id ?
			//if(isset($headers['message-id']))
			//	$mail_headers->get('message-id')->setFieldBodyModel(trim($headers['message-id'],'<>'));
			
			// Add some headers
			
			if(isset($headers['in-reply-to']))
				$mail_headers->addTextHeader('In-Reply-To',$headers['in-reply-to']);
			
			if(isset($headers['references']))
				$mail_headers->addTextHeader('References', $headers['references'] . ' ' . $headers['message-id']);
			
			if(isset($headers['x-mailer']))
				$mail_headers->addTextHeader('X-Mailer', $headers['x-mailer']);
			
			$mail_headers->addTextHeader('X-Cerb-Resend','true');
			
			// Set the plaintext body

			$mail->setBody($content);
			
			// Attachments
			
			if(is_array($attachments))
			foreach($attachments as $file) { /* @var $file Model_Attachment */
				
				// If HTML, include as a text/html part
				if($file->name == 'original_message.html') {
					$mail->addPart($file->getFileContents(), 'text/html');
					
				} else {
					$fp = DevblocksPlatform::getTempFile();
					$fp_path = DevblocksPlatform::getTempFileInfo($fp);
					$file->getFileContents($fp);
					$mail->attach(Swift_Attachment::fromPath($fp_path)->setFilename($file->name)->setContentType($file->mime_type));
				}
			}
			
			if($only_return_source) {
				$mime = $mail->toString();
				return $mime;
				
			} else {
				$result = $mail_service->send($mail);
				
				if(!$result) {
					return false;
				}
				
				return true;
			}
			
		} catch (Exception $e) {
			$error = $e->getMessage();
			error_log($error);
			return false;
		}
	}
	
	static function reflect(CerberusParserModel $model, $to) {
		try {
			$message = $model->getMessage(); /* @var $message CerberusParserMessage */
			
			$mail_service = DevblocksPlatform::services()->mail();
			$mail = $mail_service->createMessage();
	
			$mail->setTo(array($to));

			$headers = $mail->getHeaders();

			if(isset($message->headers['subject'])) {
				if(is_array($message->headers['subject']))
					$subject = array_shift($message->headers['subject']);
				else
					$subject = $message->headers['subject'];
				$mail->setSubject($subject);
			}
			if(isset($message->headers['message-id']))
				$headers->addTextHeader('Message-Id', $message->headers['message-id']);
			if(isset($message->headers['in-reply-to']))
				$headers->addTextHeader('In-Reply-To', $message->headers['in-reply-to']);
			if(isset($message->headers['references']))
				$headers->addTextHeader('References', $message->headers['references']);
			if(isset($message->headers['from'])) {
				$sender_addy = $model->getSenderAddressModel(); /* @var $sender_addy Model_Address */
				$sender_name = $sender_addy->getName();
				$mail->setFrom($sender_addy->email, !empty($sender_name) ? $sender_name : null);
			}
			if(isset($message->headers['return-path'])) {
				$return_path = is_array($message->headers['return-path'])
					? array_shift($message->headers['return-path'])
					: $message->headers['return-path'];
				$return_path = trim($return_path,'<>');
				$mail->setReturnPath($return_path);
			}
			if(isset($message->headers['reply-to']))
				$mail->setReplyTo($message->headers['reply-to']);
				
			$headers->addTextHeader('X-CerberusRedirect','1');

			$mail->setBody($message->body);
			
			// Files
			if(is_array($message->files))
			foreach($message->files as $file_name => $file) { /* @var $file ParserFile */
				$mail->attach(Swift_Attachment::fromPath($file->tmpname)->setFilename($file_name));
			}
		
			$result = $mail_service->send($mail);
			
			if(!$result) {
				return false;
			}
			
		} catch (Exception $e) {
			return false;
		}
	}
	
	static private function _generateBodiesWithPrependsAppends(&$properties) {
		if(!isset($properties['content']))
			return;
		
		@$content_prepends = $properties['content_prepends'];
		@$content_appends = $properties['content_appends'];
		
		$properties['content_sent'] = $properties['content_saved'] = $properties['content'];
		
		foreach(['saved','sent'] as $type) {
			if(is_array(@$content_prepends[$type]))
			foreach($content_prepends[$type] as $prepend) {
				$properties['content_'.$type] = $prepend . "\r\n" . $properties['content_'.$type];
			}
			
			if(is_array(@$content_appends[$type]))
			foreach($content_appends[$type] as $append) {
				$properties['content_'.$type] .= "\r\n" . $append;
			}
		}
	}
	
	static private function _generateTextFromMarkdown($markdown) {
		$plaintext = null;
		
		$url_writer = DevblocksPlatform::services()->url();
		$base_url = $url_writer->write('c=files', true) . '/';
		
		// Strip some Markdown in the plaintext version
		try {
			$plaintext = preg_replace_callback(
				sprintf('|(\!\[inline-image\]\(%s(.*?)\))|', preg_quote($base_url)),
				function($matches) use ($base_url) {
					if(3 == count($matches)) {
						@list($file_id, $file_name) = explode('/', $matches[2], 2);
						
						if($file_id && $file_name)
							return sprintf("[Image %s]", urldecode($file_name));
					}
					
					return $matches[0];
				},
				$markdown
			);
			
		} catch (Exception $e) {
			error_log($e->getMessage());
		}
		
		try {
			$plaintext = preg_replace_callback(
				sprintf('|(\!\[Image\]\((.*?)\))|'),
				function($matches) {
					if(3 == count($matches)) {
						return sprintf("%s", $matches[2]);
					}
					
					return $matches[0];
				},
				$plaintext
			);
			
		} catch (Exception $e) {
			error_log($e->getMessage());
		}
		
		try {
			$plaintext = DevblocksPlatform::parseMarkdown($plaintext);
			$plaintext = DevblocksPlatform::stripHTML($plaintext);
			
		} catch (Exception $e) {
			error_log($e->getMessage());
		}
		
		return $plaintext;
	}
	
	static private function _generateMailBodyMarkdown(&$mail, &$content, $group_id=0, $bucket_id=0, $html_template_id=0) {
		$embedded_files = [];
		$exclude_files = [];
		
		$url_writer = DevblocksPlatform::services()->url();
		$base_url = $url_writer->write('c=files', true) . '/';
		
		// Generate an HTML part using Parsedown
		if(false !== ($html_body = DevblocksPlatform::parseMarkdown($content))) {
			
			// Determine if we have an HTML template
			if(!$html_template_id || false == ($html_template = DAO_MailHtmlTemplate::get($html_template_id))) {
				if(false == ($group = DAO_Group::get($group_id)) || false == ($html_template = $group->getReplyHtmlTemplate($bucket_id)))
					$html_template = null;
			}
			
			// Use an HTML template wrapper if we have one
			if($html_template instanceof Model_MailHtmlTemplate) {
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				
				$html_body = $tpl_builder->build(
					$html_template->content,
					array(
						'message_body' => $html_body
					)
				);
				
				// Load the attachment links from the HTML template
				$exclude_files = array_keys($html_template->getAttachments());
			}
			
			// Purify the HTML and inline the CSS
			$html_body = DevblocksPlatform::purifyHTML($html_body, true, true);
			
			// Replace links with cid: in HTML part
			try {
				$html_body = preg_replace_callback(
					sprintf('|(\"%s(.*?)\")|', preg_quote($base_url)),
					function($matches) use ($base_url, $mail, &$embedded_files, $exclude_files) {
						if(3 == count($matches)) {
							@list($file_id, $file_name) = explode('/', $matches[2], 2);
							if($file_id && $file_name) {
								if($file = DAO_Attachment::get($file_id)) {
									
									if(!in_array($file_id, $exclude_files))
										$embedded_files[] = $file_id;
									
									$cid = $mail->embed(Swift_Image::newInstance($file->getFileContents(), $file->name, $file->mime_type));
									return sprintf('"%s"', $cid);
								}
							}
						}
						
						return $matches[0];
					},
					$html_body
				);
				
			} catch(Exception $e) {
				error_log($e->getMessage());
			}

			$mail->addPart($html_body, 'text/html');
		}
		
		$plaintext = self::_generateTextFromMarkdown($content);
		
		$mail->addPart($plaintext, 'text/plain');
		
		return $embedded_files;
	}
};
