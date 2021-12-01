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

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;

/**
 * Based on: https://raw.githubusercontent.com/Mailgarant/switfmailer-openpgp/master/OpenPGPSigner.php
 *
 */
class Cerb_SwiftPlugin_GPGSigner implements Swift_Signers_BodySigner {
	protected $micalg = 'SHA256';
	private $_properties = [];
	
	function __construct(array $properties=[]) {
		$this->_properties = $properties;
	}
	
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
		$gpg = DevblocksPlatform::services()->gpg();
		
		// Check for group/bucket overrides
		
		@$bucket_id = $this->_properties['bucket_id'];
		
		if($bucket_id && false != ($bucket = DAO_Bucket::get($bucket_id))) {
			if(false != ($reply_signing_key = $bucket->getReplySigningKey())) {
				return $reply_signing_key->fingerprint;
			}
		}
		
		// Check for private keys that cover the 'From:' address
		
		if(false == ($from = $message->getFrom()) || !is_array($from))
			return false;
		
		$email = key($from);
		
		if(false != ($keys = $gpg->keyinfoPrivate(sprintf("<%s>", $email))) && is_array($keys)) {
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

			if(false != ($keys = $gpg->keyinfoPublic(sprintf("<%s>", $email))) && is_array($keys)) {
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
	 * @throws Swift_SwiftException
	 */
	public function signMessage(Swift_Message $message) {
		$sign_key = $this->getSignKey($message);
		
		$originalMessage = $this->createMessage($message);
		$message->setChildren([]);
		$message->setEncoder(Swift_DependencyContainer::getInstance()->lookup('mime.rawcontentencoder'));
		
		if(@$this->_properties['gpg_sign']) {
			if(!$sign_key)
				throw new Swift_SwiftException('Error: No PGP signing keys are configured for this group/bucket.');
			
			$type = $message->getHeaders()->get('Content-Type');
			$type->setValue('multipart/signed');
			$type->setParameters([
				'micalg' => sprintf('pgp-%s', DevblocksPlatform::strLower($this->micalg)),
				'protocol' => 'application/pgp-signature',
				'boundary' => $message->getBoundary(),
			]);
			
			$signed_body = $originalMessage->toString();
			
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
		
		if(@$this->_properties['gpg_encrypt']) {
			if(false == ($recipient_keys = $this->getRecipientKeys($message)))
				throw new Swift_SwiftException('Error: No recipient GPG public keys for encryption.');
			
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
	
	static function writeRfcAddress($email, $personal=null) {
		$is_quoted = false;
		
		if($personal) {
			if (false !== strpos($personal, '.')) {
				$is_quoted = true;
			}
			
			if (false !== strpos($personal, '"')) {
				$is_quoted = true;
				$personal = str_replace('"', '\"', $personal);
			}
			
			return sprintf("%s <%s>",
				$is_quoted ? sprintf('"%s"', $personal) : $personal,
				$email
			);
			
		} else {
			return $email;
		}
	}
	
	static function parseRfcAddress($string) {
		$addresses = self::parseRfcAddresses($string);
		
		if(!is_array($addresses))
			return false;
		
		return array_shift($addresses);
	}
	
	static function parseRfcAddresses($string, $exclude_controlled_addresses=false) {
		$strings = DevblocksPlatform::services()->string();
		
		// Always terminate the list
		$string = rtrim($string, ',;') . ';';
		
		$state = null;
		$states = [$state];
		
		$addresses = [];
		
		$personal = '';
		$email = '';
		
		for($i=0;$i<strlen($string);$i++) {
			$char = $string[$i];
			
			switch($state) {
				// Quoted block
				case '"':
					switch($char) {
						// Literal following char
						case '\\':
							$personal .= $char . $string[++$i];
							break;
							
						// Terminate quotes
						case '"':
							$personal .= $char;
							array_pop($states);
							$state = end($states);
							break;
							
						// Append personal
						default:
							$personal .= $char;
							break;
					}
					break;
				
				// Email
				case '<':
					switch($char) {
						// Terminate email
						case '>':
							array_pop($states);
							$state = end($states);
							break;
							
						// Append email
						default:
							$email .= $char;
							break;
					}
					break;
				
				case null:
					switch($char) {
						// Start quotes (personal)
						case '"':
							$personal .= $char;
							$state = '"';
							$states[] = $state;
							break;
							
						// Start email
						case '<':
							$state = '<';
							$states[] = $state;
							break;
						
						// End address
						case ';':
						case ',':
							$personal = trim($personal);
							$email = trim($email);
							
							if(!$email) {
								$matches = [];
								
								// Formmail style?
								if(preg_match('#^(.*?\@.*?) \((.*?)\)$#', $personal, $matches)) {
									$email = $matches[1];
									$personal = $matches[2];
								} else {
									$email = $personal;
									$personal = '';
								}
								
							} else {
								if(DevblocksPlatform::strStartsWith($personal, '"')) {
									$personal = mb_substr($personal, 1, -1);
								}
								
								$personal = str_replace('\"','"', trim($personal));
							}
							
							if($email) {
								$mailbox = $strings->strBefore($email,'@');
								$host = $strings->strAfter($email,'@');
								
								if($mailbox && $host) {
									// Validate
									$validator = new EmailValidator();
									if($validator->isValid($email, new RFCValidation())) {
										$addresses[$email] = [
											'full_email' => self::writeRfcAddress($email, $personal),
											'email' => $email,
											'mailbox' => $mailbox,
											'host' => $host,
											'personal' => $personal,
										];
									}
								}
							}
							
							$personal = '';
							$email = '';
							break;
						
						default:
							$personal .= $char;
							break;
					}
					break;
			}
		}
		
		if($exclude_controlled_addresses) {
			$exclude_list = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::PARSER_AUTO_REQ_EXCLUDE, CerberusSettingsDefaults::PARSER_AUTO_REQ_EXCLUDE);
			@$excludes = DevblocksPlatform::parseCrlfString($exclude_list);

			foreach(array_keys($addresses) as $check_address) {
				$is_skipped = false;
				
				// If this is a local address and we're excluding them, skip it
				if(DAO_Address::isLocalAddress($check_address)) {
					$is_skipped = true;
					
				} else {
					// Filter explicit excludes
					if (is_array($excludes) && !empty($excludes)) {
						foreach ($excludes as $excl_pattern) {
							if (@preg_match(DevblocksPlatform::parseStringAsRegExp($excl_pattern), $check_address)) {
								$is_skipped = true;
								break;
							}
						}
					}
				}
				
				if($is_skipped) {
					unset($addresses[$check_address]);
				}
			}
		}
		
		return $addresses;
	}
	
	static function decodeMimeHeader($string) {
		if(function_exists('iconv_mime_decode')) {
			return iconv_mime_decode($string, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'utf-8');
		} else {
			return mb_decode_mimeheader($string);
		}
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
	
	static function quickSend($to, $subject, $body, $from_addy=null, $from_personal=null, $custom_headers=[], $format=null, $html_template_id=null, $file_ids=[], $cc=null, $bcc=null, &$error=null) {
		$error = null;
		
		try {
			$mail_service = DevblocksPlatform::services()->mail();
			$mail = $mail_service->createMessage();
			
			if(empty($from_addy) || empty($from_personal)) {
				if(false == ($replyto_default = DAO_Address::getDefaultLocalAddress()))
					throw new Exception_DevblocksValidationError("There is no default sender address.");
				
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
				if(false != ($from = CerberusMail::parseRfcAddress($custom_headers['from']))) {
					$from_addy = $from['email'];
					$from_personal = $from['personal'];
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
				case 'markdown':
				case 'parsedown':
					$properties = [
						'html_template_id' => $html_template_id,
						'content' => $body,
					];
					self::_generateMailBodyMarkdown($mail, $properties);
					break;
					
				default:
					$properties = [
						'content' => $body,
					];
					
					$mail->setBody(self::_generateBodyContentWithPartModifications(
						$properties,
						'sent',
						'text'
					));
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
								
								if('message/rfc822' == $attachment->mime_type)
									$attach->setContentType('application/octet-stream');
								
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
				$error = $mail_service->getLastErrorMessage();
				return false;
			}
			
		} catch (Exception_DevblocksValidationError $e) {
			$error = $e->getMessage();
			return false;
			
		} catch (Exception $e) {
			$error = 'An unexpected error occurred.';
			return false;
		}
		
		return true;
	}
	
	/**
	 * @param array $properties
	 * @return array|false
	 */
	static function compose(array $properties) {
		/*
		'group_id'
		'bucket_id'
		'worker_id'
		'owner_id'
		'watcher_ids'
		'org_id'
		'to'
		'cc'
		'bcc'
		'subject'
		'content'
		'content_format'
		'html_template_id'
		'files'
		'forward_files'
		'status_id'
		'ticket_reopen'
		'dont_send'
		'draft_id'
		'gpg_encrypt'
		'gpg_sign'
		'send_at'
		'token'
		 */
		
		$mail_service = DevblocksPlatform::services()->mail();
		$email = $mail_service->createMessage();
		
		$draft_id = $properties['draft_id'] ?? null;
		$group_id = $properties['group_id'] ?? null;
		$bucket_id = intval($properties['bucket_id'] ?? 0);
		$worker_id = $properties['worker_id'] ?? null;
		
		$worker = null;
		
		if(!$worker_id && null != ($worker = CerberusApplication::getActiveWorker()))
			$worker_id = $worker->id;

		// Worker
		if($worker_id)
			$worker = DAO_Worker::get($worker_id);
		
		// Group
		if(null == ($group = DAO_Group::get($group_id))) {
			if(null == ($group = DAO_Group::getDefaultGroup()))
				return false;
			
			$group_id = $group->id;
		}
		
		// Bucket
		if(!$bucket_id || false == ($bucket = DAO_Bucket::get($bucket_id)) || $bucket->group_id != $group->id)
			$bucket = $group->getDefaultBucket();
		
		$from_replyto = $group->getReplyTo($bucket->id);
		$personal = $group->getReplyPersonal($bucket->id, $worker);
		
		// Message-Id
		$email->generateId();
		$outgoing_message_id = $email->getHeaders()->get('message-id')->getFieldBody();
		$properties['outgoing_message_id'] = $outgoing_message_id;
		
		// Changing the outgoing message through a VA (global)
		Event_MailBeforeSent::trigger($properties, null, null, $group_id);
		
		// Changing the outgoing message through a VA (group)
		Event_MailBeforeSentByGroup::trigger($properties, null, null, $group_id);
		
		$send_at = @strtotime($properties['send_at'] ?? 0);
		
		if($send_at && $send_at >= time()) {
			// If we're not resuming a draft from the UI, generate a draft
			if (false == ($draft = DAO_MailQueue::get($draft_id))) {
				$change_fields = DAO_MailQueue::getFieldsFromMessageProperties($properties);
				$change_fields[DAO_MailQueue::TYPE] = Model_MailQueue::TYPE_COMPOSE;
				$change_fields[DAO_MailQueue::IS_QUEUED] = 1;
				$change_fields[DAO_MailQueue::QUEUE_DELIVERY_DATE] = $send_at;
				
				$draft_id = DAO_MailQueue::create($change_fields);
				
				if(array_key_exists('forward_files', $properties)) {
					DAO_Attachment::addLinks(CerberusContexts::CONTEXT_DRAFT, $draft_id, $properties['forward_files']);
				}
				
			} else {
				$draft->params['send_at'] = date('r', $send_at);
				$draft_id = $draft->id;
				
				$draft_fields = [
					DAO_MailQueue::IS_QUEUED => 1,
					DAO_MailQueue::QUEUE_FAILS => 0,
					DAO_MailQueue::QUEUE_DELIVERY_DATE => $send_at,
					DAO_MailQueue::PARAMS_JSON => json_encode($draft->params),
				];
				
				DAO_MailQueue::update($draft_id, $draft_fields);
			}
			
			return [CerberusContexts::CONTEXT_DRAFT, $draft_id];
		}
		
		$mask = CerberusApplication::generateTicketMask();
		
		$hash_commands = [];
		
		if($worker) {
			CerberusMail::parseComposeHashCommands($worker, $properties, $hash_commands);
		}
		
		@$org_id = $properties['org_id'];
		@$toStr = $properties['to'];
		@$cc = $properties['cc'];
		@$bcc = $properties['bcc'];
		@$subject = $properties['subject'];
		@$content_format = $properties['content_format'];
		@$files = $properties['files'];
		@$embedded_files = [];
		@$forward_files = $properties['forward_files'];
		@$is_broadcast = intval($properties['is_broadcast']);
		
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
						if(method_exists($header, 'setValue'))
							$header->setValue($header_val);
					}
				}
			}
			
			// Body
			
			switch($content_format) {
				case 'markdown':
				case 'parsedown':
					$embedded_files = self::_generateMailBodyMarkdown($email, $properties, $group_id, $bucket->id);
					break;
					
				default:
					$content_sent = CerberusMail::getMailTemplateFromContent($properties, 'sent', 'text');
					$email->setBody($content_sent);
					break;
			}
			
			// Mime Attachments
			if (is_array($files) && !empty($files)) {
				foreach ($files['tmp_name'] as $idx => $file) {
					if(empty($file) || empty($files['name'][$idx]))
						continue;
					
					@$mime_type = $files['type'][$idx];
					
					$attach = Swift_Attachment::fromPath($file, $mime_type)->setFilename($files['name'][$idx]);
					
					if('message/rfc822' == $mime_type)
						$attach->setContentType('application/octet-stream');
	
					$email->attach($attach);
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
								
								if('message/rfc822' == $attachment->mime_type)
									$attach->setContentType('application/octet-stream');
								
								$email->attach($attach);
								fclose($fp);
							}
						}
					}
				}
			}
			
			$outgoing_mail_headers = $email->getHeaders()->toString();
			$outgoing_message_id = $email->getHeaders()->get('message-id')->getFieldBody();
			
			// Encryption and signing
			if(@$properties['gpg_sign'] || @$properties['gpg_encrypt']) {
				$signer = new Cerb_SwiftPlugin_GPGSigner($properties);
				$email->attachSigner($signer);
			}
			
			if(!empty($toList) && (!isset($properties['dont_send']) || empty($properties['dont_send']))) {
				if(!$mail_service->send($email)) {
					throw new Exception('Mail failed to send: unknown reason');
				}
			}
			
		} catch (Exception $e) {
			if(!$draft_id) {
				$fields = DAO_MailQueue::getFieldsFromMessageProperties($properties);
				$fields[DAO_MailQueue::TYPE] = Model_MailQueue::TYPE_COMPOSE;
				$fields[DAO_MailQueue::IS_QUEUED] = 1;
				$fields[DAO_MailQueue::QUEUE_FAILS] = 1;
				$fields[DAO_MailQueue::QUEUE_DELIVERY_DATE] = time() + 300;
				
				$draft_id = DAO_MailQueue::create($fields);
				
			} else {
				if(false != ($draft = DAO_MailQueue::get($draft_id))) {
					if($draft->queue_fails < 10) {
						$fields = [
							DAO_MailQueue::IS_QUEUED => 1,
							DAO_MailQueue::QUEUE_FAILS => ++$draft->queue_fails,
							DAO_MailQueue::QUEUE_DELIVERY_DATE => time() + 300,
						];
					} else {
						$fields = [
							DAO_MailQueue::IS_QUEUED => 0,
							DAO_MailQueue::QUEUE_DELIVERY_DATE => 0,
						];
					}
					DAO_MailQueue::update($draft_id, $fields);
				}
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
		
		$fromAddressInst = CerberusApplication::hashLookupAddress($from_replyto->email, true);
		$fromAddressId = $fromAddressInst->id;
		
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
			DAO_Ticket::STATUS_ID => 0,
			DAO_Ticket::OWNER_ID => 0,
			DAO_Ticket::REOPEN_AT => 0,
			DAO_Ticket::CREATED_DATE => time(),
			DAO_Ticket::FIRST_WROTE_ID => $fromAddressId,
			DAO_Ticket::LAST_WROTE_ID => $fromAddressId,
			DAO_Ticket::ORG_ID => intval($org_id),
		);
		
		$ticket_id = DAO_Ticket::create($fields);
		
		// Save a copy of the sent HTML body
		$html_body_id = 0;
		if(in_array($content_format, ['markdown','parsedown'])) {
			if(false !== ($html_saved = CerberusMail::getMailTemplateFromContent($properties, 'saved', 'html'))) {
				$html_body_id = DAO_Attachment::create([
					DAO_Attachment::NAME => 'original_message.html',
					DAO_Attachment::MIME_TYPE => 'text/html',
					DAO_Attachment::STORAGE_SHA1HASH => sha1($html_saved),
				]);
				
				Storage_Attachments::put($html_body_id, $html_saved);
				unset($html_saved);
				
				$embedded_files[] = $html_body_id;
			}
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
		
		if(array_key_exists('gpg_sign', $properties) && $properties['gpg_sign']) {
			$fields[DAO_Message::SIGNED_AT] = time();
			// [TODO]
			//$fields[DAO_Message::SIGNED_KEY_FINGERPRINT] = null;
		}
		
		if(array_key_exists('token', $properties) && $properties['token']) {
			$fields[DAO_Message::TOKEN] = $properties['token'];
		}
		
		$message_id = DAO_Message::create($fields);
		
		// Convert to a plaintext part
		$plaintext_saved = CerberusMail::getMailTemplateFromContent($properties, 'saved', 'text');
		
		Storage_MessageContent::put($message_id, $plaintext_saved);
		unset($plaintext_saved);

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
				$sha1_hash = sha1_file($file) ?? null;
				
				if(false == ($file_id = DAO_Attachment::getBySha1Hash($sha1_hash, $files['size'][$idx], $files['type'][$idx]))) {
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
		
		// Message custom fields
		if(array_key_exists('message_custom_fields', $properties)) {
			if ($message_id && is_array($properties['message_custom_fields'])) {
				DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_MESSAGE, $message_id, $properties['message_custom_fields'], true, true, false);
			}
		}
		
		// Finalize ticket
		$ticket_fields = [
			DAO_Ticket::FIRST_MESSAGE_ID => $message_id,
			DAO_Ticket::LAST_MESSAGE_ID => $message_id,
		];

		// Move last, so the event triggers properly
		$properties['group_id'] = $group->id;
		$properties['bucket_id'] = $bucket->id;
		
		if(false !== ($ticket = DAO_Ticket::get($ticket_id)))
			DAO_Ticket::updateWithMessageProperties($properties, $ticket, $ticket_fields, false);
		
		// Train as not spam
		CerberusBayes::markTicketAsNotSpam($ticket_id);
		
		if($worker) {
			DAO_WorkerPref::set($worker->id, 'compose.group_id', $group_id);
			DAO_WorkerPref::set($worker->id, 'compose.bucket_id', $bucket_id);
			
			if($hash_commands)
				CerberusMail::handleComposeHashCommands($hash_commands, $ticket_id, $worker);
		}
		
		self::_composeTriggerEvents($message_id, $group_id);
		
		// Remove the draft
		if($draft_id)
			DAO_MailQueue::delete($draft_id);
		
		return [CerberusContexts::CONTEXT_TICKET, $ticket_id];
	}
	
	static private function _composeTriggerEvents($message_id, $group_id) {
		// Events
		if(!empty($message_id) && !empty($group_id)) {
			// Changing the outgoing message through an automation
			AutomationTrigger_MailSent::trigger($message_id);
			
			// After message sent (global)
			Event_MailAfterSent::trigger($message_id);
			
			// After message sent in group
			Event_MailAfterSentByGroup::trigger($message_id, $group_id);
			
			// Mail received
			Event_MailReceived::trigger($message_id);
			
			// Mail received by group
			Event_MailReceivedByGroup::trigger($message_id, $group_id);
		}
	}
	
	/**
	 * @param array $properties
	 * @return array|false
	 */
	static function sendTransactional(array $properties) {
		/*
		'to'
		'cc'
		'bcc'
		'from'
		'subject'
		'content'
		'content_format'
		'html_template_id'
		'files'
		'forward_files'
		'draft_id'
		'gpg_encrypt'
		'gpg_sign'
		'send_at'
		'token'
		 */
		
		$mail_service = DevblocksPlatform::services()->mail();
		$email = $mail_service->createMessage();
		
		$draft_id = $properties['draft_id'] ?? null;
		
		// Message-Id
		$email->generateId();
		$outgoing_message_id = $email->getHeaders()->get('message-id')->getFieldBody();
		$properties['outgoing_message_id'] = $outgoing_message_id;
		
		$send_at = @strtotime($properties['send_at'] ?? 0);
		
		if($send_at && $send_at >= time()) {
			// If we're not resuming a draft from the UI, generate a draft
			if (false == ($draft = DAO_MailQueue::get($draft_id))) {
				$change_fields = DAO_MailQueue::getFieldsFromMessageProperties($properties);
				$change_fields[DAO_MailQueue::TYPE] = Model_MailQueue::TYPE_TRANSACTIONAL;
				$change_fields[DAO_MailQueue::IS_QUEUED] = 1;
				$change_fields[DAO_MailQueue::QUEUE_DELIVERY_DATE] = $send_at;
				
				$draft_id = DAO_MailQueue::create($change_fields);
				
				if(array_key_exists('forward_files', $properties)) {
					DAO_Attachment::addLinks(CerberusContexts::CONTEXT_DRAFT, $draft_id, $properties['forward_files']);
				}
				
			} else {
				$draft->params['send_at'] = date('r', $send_at);
				$draft_id = $draft->id;
				
				$draft_fields = [
					DAO_MailQueue::IS_QUEUED => 1,
					DAO_MailQueue::QUEUE_FAILS => 0,
					DAO_MailQueue::QUEUE_DELIVERY_DATE => $send_at,
					DAO_MailQueue::PARAMS_JSON => json_encode($draft->params),
				];
				
				DAO_MailQueue::update($draft_id, $draft_fields);
			}
			
			return [CerberusContexts::CONTEXT_DRAFT, $draft_id];
		}
		
		$toStr = $properties['to'] ?? null;
		$cc = $properties['cc'] ?? null;
		$bcc = $properties['bcc'] ?? null;
		$from = $properties['from'] ?? null;
		$subject = $properties['subject'] ?? null;
		$content_format = $properties['content_format'] ?? null;
		$files = $properties['files'] ?? null;
		$forward_files = $properties['forward_files'] ?? null;
		
		if(empty($subject))
			$subject = '(no subject)';
		
		// [JAS]: Replace any semi-colons with commas (people like using either)
		$toList = CerberusMail::parseRfcAddresses($toStr);
		
		try {
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
			
			// From
			$fromRfc = CerberusMail::parseRfcAddress($from);
			
			if($fromRfc) {
				$email->setFrom($fromRfc['email'], $fromRfc['personal'] ?: null);
			} else {
				$from_replyto = DAO_Address::getDefaultLocalAddress();
				$email->setFrom($from_replyto->email);
			}
			
			$email->setSubject($subject);
			
			$headers = $email->getHeaders();
			
			$headers->addTextHeader('X-Mailer','Cerb ' . APP_VERSION . ' (Build '.APP_BUILD.')');
			
			// Custom headers
			
			if(isset($properties['headers']) && is_array($properties['headers']))
				foreach($properties['headers'] as $header_key => $header_val) {
					if(!empty($header_key) && is_string($header_key) && is_string($header_val)) {
						if(NULL == ($header = $headers->get($header_key))) {
							$headers->addTextHeader($header_key, $header_val);
						} else {
							if(method_exists($header, 'setValue'))
								$header->setValue($header_val);
						}
					}
				}
			
			// Body
			
			switch($content_format) {
				case 'markdown':
				case 'parsedown':
					self::_generateMailBodyMarkdown($email, $properties);
					break;
				
				default:
					$content_sent = CerberusMail::getMailTemplateFromContent($properties, 'sent', 'text');
					$email->setBody($content_sent);
					break;
			}
			
			// Mime Attachments
			if (is_array($files) && !empty($files)) {
				foreach ($files['tmp_name'] as $idx => $file) {
					if(empty($file) || empty($files['name'][$idx]))
						continue;
					
					@$mime_type = $files['type'][$idx];
					
					$attach = Swift_Attachment::fromPath($file, $mime_type)->setFilename($files['name'][$idx]);
					
					if('message/rfc822' == $mime_type)
						$attach->setContentType('application/octet-stream');
					
					$email->attach($attach);
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
								
								if('message/rfc822' == $attachment->mime_type)
									$attach->setContentType('application/octet-stream');
								
								$email->attach($attach);
								fclose($fp);
							}
						}
					}
				}
			}
			
			// Encryption and signing
			if(@$properties['gpg_sign'] || @$properties['gpg_encrypt']) {
				$signer = new Cerb_SwiftPlugin_GPGSigner($properties);
				$email->attachSigner($signer);
			}
			
			if(!empty($toList)) {
				if(!$mail_service->send($email)) {
					throw new Exception('Mail failed to send: unknown reason');
				}
			}
			
		} catch (Exception $e) {
			if(!$draft_id) {
				$fields = DAO_MailQueue::getFieldsFromMessageProperties($properties);
				$fields[DAO_MailQueue::TYPE] = Model_MailQueue::TYPE_TRANSACTIONAL;
				$fields[DAO_MailQueue::IS_QUEUED] = 1;
				$fields[DAO_MailQueue::QUEUE_FAILS] = 1;
				$fields[DAO_MailQueue::QUEUE_DELIVERY_DATE] = time() + 300;
				
				$draft_id = DAO_MailQueue::create($fields);
				
			} else {
				if(false != ($draft = DAO_MailQueue::get($draft_id))) {
					if($draft->queue_fails < 10) {
						$fields = [
							DAO_MailQueue::IS_QUEUED => 1,
							DAO_MailQueue::QUEUE_FAILS => ++$draft->queue_fails,
							DAO_MailQueue::QUEUE_DELIVERY_DATE => time() + 300,
						];
					} else {
						$fields = [
							DAO_MailQueue::IS_QUEUED => 0,
							DAO_MailQueue::QUEUE_DELIVERY_DATE => 0,
						];
					}
					DAO_MailQueue::update($draft_id, $fields);
				}
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
		
		// Remove the draft
		if($draft_id)
			DAO_MailQueue::delete($draft_id);
		
		return [CerberusContexts::CONTEXT_DRAFT, $draft_id];
	}
	
	/**
	 * @param array $properties
	 * @return array|false
	 */
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
		'gpg_sign'
		'dont_send'
		'dont_keep_copy'
		'send_at'
		'token'
		*/

		try {
			// objects
			$mail_service = DevblocksPlatform::services()->mail();
			$mail = $mail_service->createMessage();
			
			@$reply_message_id = $properties['message_id'];
			@$draft_id = $properties['draft_id'];
			
			if(null == ($message = DAO_Message::get($reply_message_id))) {
				if(false == ($ticket = DAO_Ticket::get($properties['ticket_id'] ?? 0)))
					return false;
				
				$message = $ticket->getLastMessage();
				$reply_message_id = $message->id;
				
			} else {
				// Ticket
				if(null == ($ticket = $message->getTicket()))
					return false;
			}
			
			// Group
			if(null == ($group = DAO_Group::get($ticket->group_id)))
				return false;
			
			// Message-Id
			$mail->generateId();
			$outgoing_message_id = $mail->getHeaders()->get('message-id')->getFieldBody();
			$properties['outgoing_message_id'] = $outgoing_message_id;
			
			// Changing the outgoing message through a VA (global)
			Event_MailBeforeSent::trigger($properties, $message->id, $ticket->id, $group->id);
			
			// Changing the outgoing message through a VA (group)
			Event_MailBeforeSentByGroup::trigger($properties, $message->id, $ticket->id, $group->id);
			
			@$send_at = strtotime($properties['send_at'] ?? 0);
			
			DAO_Ticket::updateWithMessageProperties($properties, $ticket, [], false);
			
			if($send_at && $send_at >= time()) {
				// If we're not resuming a draft from the UI, generate a draft
				if(false == ($draft = DAO_MailQueue::get($draft_id))) {
					if (!array_key_exists('subject', $properties))
						$properties['subject'] = $ticket->subject;
					
					if (!array_key_exists('message_id', $properties))
						$properties['message_id'] = $message->id;
					
					if (!array_key_exists('ticket_id', $properties))
						$properties['ticket_id'] = $ticket->id;
					
					$change_fields = DAO_MailQueue::getFieldsFromMessageProperties($properties);
					
					$change_fields[DAO_MailQueue::TYPE] = empty($is_forward) ? Model_MailQueue::TYPE_TICKET_REPLY : Model_MailQueue::TYPE_TICKET_FORWARD;
					$change_fields[DAO_MailQueue::IS_QUEUED] = 1;
					$change_fields[DAO_MailQueue::QUEUE_DELIVERY_DATE] = $send_at;
					
					$draft_id = DAO_MailQueue::create($change_fields);
					
					if(array_key_exists('forward_files', $properties)) {
						DAO_Attachment::addLinks(CerberusContexts::CONTEXT_DRAFT, $draft_id, $properties['forward_files']);
					}
					
				} else {
					// Update the draft sending date
					$draft->params['send_at'] = date('r', $send_at);
					
					$draft_fields = [
						DAO_MailQueue::IS_QUEUED => 1,
						DAO_MailQueue::QUEUE_FAILS => 0,
						DAO_MailQueue::QUEUE_DELIVERY_DATE => $send_at,
						DAO_MailQueue::PARAMS_JSON => json_encode($draft->params),
					];
					
					DAO_MailQueue::update($draft->id, $draft_fields);
				}
				
				return [CerberusContexts::CONTEXT_DRAFT, $draft_id];
			}
			
			$worker = null;
			$hash_commands = [];
			
			if(array_key_exists('worker_id', $properties)) {
				if(false != ($worker = DAO_Worker::get($properties['worker_id']))) {
					CerberusMail::parseReplyHashCommands($worker, $properties, $hash_commands);
				}
			}
			
			// Re-read properties
			@$content_format = $properties['content_format'];
			@$files = $properties['files'];
			@$is_forward = $properties['is_forward'];
			@$is_broadcast = $properties['is_broadcast'];
			@$forward_files = $properties['forward_files'];
			@$embedded_files = [];
			@$worker_id = $properties['worker_id'];
			@$subject = $properties['subject'];
			
			@$is_autoreply = $properties['is_autoreply'];
			
			$message_id = null;
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
				CerberusBayes::markTicketAsNotSpam($ticket->id);
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
				$requesters = DAO_Ticket::getRequestersByTicket($ticket->id);
				
				if(is_array($requesters))
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
						$mail->addTo($requester->email);
					}
				
				// Forward or overload
			} elseif (!empty($properties['to'])) {
				// To
				$aTo = CerberusMail::parseRfcAddresses($properties['to']);
				if (is_array($aTo))
					foreach ($aTo as $k => $v) {
						if (!empty($v['personal'])) {
							$mail->addTo($k, $v['personal']);
						} else {
							$mail->addTo($k);
						}
					}
			}
			
			// Ccs
			if (!empty($properties['cc'])) {
				$aCc = CerberusMail::parseRfcAddresses($properties['cc']);
				if (is_array($aCc))
					foreach ($aCc as $k => $v) {
						if (!empty($v['personal'])) {
							$mail->addCc($k, $v['personal']);
						} else {
							$mail->addCc($k);
						}
					}
			}
			
			// Bccs
			if (!empty($properties['bcc'])) {
				$aBcc = CerberusMail::parseRfcAddresses($properties['bcc']);
				if (is_array($aBcc))
					foreach ($aBcc as $k => $v) {
						if (!empty($v['personal'])) {
							$mail->addBcc($k, $v['personal']);
						} else {
							$mail->addBcc($k);
						}
					}
			}
			
			// Custom headers
			
			if (isset($properties['headers']) && is_array($properties['headers']))
				foreach ($properties['headers'] as $header_key => $header_val) {
					if (!empty($header_key) && is_string($header_key) && is_string($header_val)) {
						
						// Overrides
						switch (strtolower(trim($header_key))) {
							case 'from':
								if(false != ($address = CerberusMail::parseRfcAddress($header_val)))
									$mail->setFrom($address['email']);
								break;
								
							case 'to':
								if (false != ($addresses = CerberusMail::parseRfcAddresses($header_val)))
									foreach (array_keys($addresses) as $address)
										$mail->addTo($address);
								unset($properties['headers'][$header_key]);
								break;
							
							case 'cc':
								if (false != ($addresses = CerberusMail::parseRfcAddresses($header_val)))
									foreach (array_keys($addresses) as $address)
										$mail->addCc($address);
								unset($properties['headers'][$header_key]);
								break;
							
							case 'bcc':
								if (false != ($addresses = CerberusMail::parseRfcAddresses($header_val)))
									foreach (array_keys($addresses) as $address)
										$mail->addBcc($address);
								unset($properties['headers'][$header_key]);
								break;
							
							default:
								if (NULL == ($header = $headers->get($header_key))) {
									$headers->addTextHeader($header_key, $header_val);
									
								} else {
									if ($header instanceof Swift_Mime_Headers_IdentificationHeader)
										continue 2;
								
									if(method_exists($header, 'setValue'))
										$header->setValue($header_val);
								}
								break;
						}
					}
				}
			
			// Body
			
			switch ($content_format) {
				case 'markdown':
				case 'parsedown':
					$embedded_files = self::_generateMailBodyMarkdown($mail, $properties, $ticket->group_id, $ticket->bucket_id);
					break;
				
				default:
					$content_sent = CerberusMail::getMailTemplateFromContent($properties, 'sent', 'text');
					$mail->setBody($content_sent);
					break;
			}
			
			// Mime Attachments
			if (is_array($files) && !empty($files)) {
				if (isset($files['tmp_name']))
					foreach ($files['tmp_name'] as $idx => $file) {
						if (empty($file) || empty($files['name'][$idx]))
							continue;
						
						@$mime_type = $files['type'][$idx];
						
						$attach = Swift_Attachment::fromPath($file, $mime_type)->setFilename($files['name'][$idx]);
						
						if('message/rfc822' == $mime_type)
							$attach->setContentType('application/octet-stream');
						
						$mail->attach($attach);
					}
			}
			
			// Forward Attachments
			if (!empty($forward_files) && is_array($forward_files)) {
				foreach ($forward_files as $file_id) {
					// Attach the file
					if (false != ($attachment = DAO_Attachment::get($file_id))) {
						if (false !== ($fp = DevblocksPlatform::getTempFile())) {
							if (false !== $attachment->getFileContents($fp)) {
								$attach = Swift_Attachment::fromPath(DevblocksPlatform::getTempFileInfo($fp), $attachment->mime_type);
								$attach->setFilename($attachment->name);
								
								if('message/rfc822' == $attachment->mime_type)
									$attach->setContentType('application/octet-stream');
								
								$mail->attach($attach);
								fclose($fp);
							}
						}
					}
				}
			}
			
			// Encryption and signing
			if(@$properties['gpg_sign'] || @$properties['gpg_encrypt']) {
				$signer = new Cerb_SwiftPlugin_GPGSigner($properties);
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
			
			if($worker && $hash_commands)
				CerberusMail::handleReplyHashCommands($hash_commands, $ticket, $worker);
			
		} catch (Exception $e) {
			// Only if we weren't trying to send a draft already...
			if(empty($draft_id)) {
				$params = [
					'in_reply_message_id' => $properties['message_id'],
					'subject' => $properties['subject'] ?? '',
					'content' => $properties['content'] ?? '',
				];
				
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
					DAO_MailQueue::NAME => $properties['subject'] ?? '',
					DAO_MailQueue::PARAMS_JSON => json_encode($params),
					DAO_MailQueue::IS_QUEUED => 1,
					DAO_MailQueue::QUEUE_FAILS => 1,
					DAO_MailQueue::QUEUE_DELIVERY_DATE => time() + 300,
				);
				$draft_id = DAO_MailQueue::create($fields);
				
			} else {
				if(false != ($draft = DAO_MailQueue::get($draft_id))) {
					if($draft->queue_fails < 10) {
						$fields = [
							DAO_MailQueue::IS_QUEUED => 1,
							DAO_MailQueue::QUEUE_DELIVERY_DATE => time() + 300,
							DAO_MailQueue::QUEUE_FAILS => ++$draft->queue_fails,
						];
					} else {
						$fields = [
							DAO_MailQueue::IS_QUEUED => 0,
							DAO_MailQueue::QUEUE_DELIVERY_DATE => 0,
						];
					}
					DAO_MailQueue::update($draft_id, $fields);
				}
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
				
				// If we have a ticket, reopen it
				if(array_key_exists('ticket_id', $properties) && $properties['ticket_id']) {
					DAO_Ticket::update($properties['ticket_id'], [
						DAO_Ticket::STATUS_ID => 0,
					]);
				}
			}
			
			return [CerberusContexts::CONTEXT_DRAFT, $draft_id];
		}
		
		$change_fields = [];
		
		$fromAddressInst = CerberusApplication::hashLookupAddress($from_replyto->email, true);
		$fromAddressId = $fromAddressInst->id;
		
		if((!isset($properties['dont_keep_copy']) || !$properties['dont_keep_copy'])
			&& empty($is_autoreply)) {
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
			if(in_array($content_format,  ['markdown', 'parsedown'])) {
				if(false !== ($html_saved = CerberusMail::getMailTemplateFromContent($properties, 'saved', 'html'))) {
					$html_body_id = DAO_Attachment::create([
						DAO_Attachment::NAME => 'original_message.html',
						DAO_Attachment::MIME_TYPE => 'text/html',
						DAO_Attachment::STORAGE_SHA1HASH => sha1($html_saved),
					]);
					
					Storage_Attachments::put($html_body_id, $html_saved);
					unset($html_saved);
					
					$embedded_files[] = $html_body_id;
				}
			}
			
			// Fields
			
			$fields = array(
				DAO_Message::TICKET_ID => $ticket->id,
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
			
			// Did we sign it?
			// [TODO] We may need to sort out the signing key ahead of time to log this
			if(!empty(@$properties['gpg_sign'])) {
				$fields[DAO_Message::SIGNED_AT] = time();
				//$fields[DAO_Message::SIGNED_KEY_FINGERPRINT] = null;
			}
			
			if(array_key_exists('token', $properties) && $properties['token']) {
				$fields[DAO_Message::TOKEN] = $properties['token'];
			}
			
			$message_id = DAO_Message::create($fields);
			
			// Store ticket.last_message_id
			$change_fields[DAO_Ticket::LAST_MESSAGE_ID] = $message_id;
			
			// First outgoing message?
			if(empty($ticket->first_outgoing_message_id) && !empty($worker_id)) {
				$change_fields[DAO_Ticket::FIRST_OUTGOING_MESSAGE_ID] = $message_id;
				$change_fields[DAO_Ticket::ELAPSED_RESPONSE_FIRST] = max(time() - $ticket->created_date, 0);
			}
			
			// Convert to a plaintext part
			$plaintext_saved = CerberusMail::getMailTemplateFromContent($properties, 'saved', 'text');
			
			Storage_MessageContent::put($message_id, $plaintext_saved);
			unset($plaintext_saved);

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
					$sha1_hash = sha1_file($file, false) ?? null;
					
					if(false == ($file_id = DAO_Attachment::getBySha1Hash($sha1_hash, $files['size'][$idx], $files['type'][$idx]))) {
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
			
			// Ticket
			DAO_Ticket::update($ticket->id, $change_fields);
			
			// Message custom fields
			if(array_key_exists('message_custom_fields', $properties)) {
				if ($message_id && is_array($properties['message_custom_fields'])) {
					DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_MESSAGE, $message_id, $properties['message_custom_fields'], true, true, false);
				}
			}
		}
		
		// Events
		if(!empty($message_id)) {
			// Changing the outgoing message through an automation
			AutomationTrigger_MailSent::trigger($message_id);
			
			// After message sent (global)
			Event_MailAfterSent::trigger($message_id);
			
			// After message sent in group
			Event_MailAfterSentByGroup::trigger($message_id, $group->id);
			
			// Mail received
			Event_MailReceived::trigger($message_id);
			
			// New message for group
			Event_MailReceivedByGroup::trigger($message_id, $group->id);

			// Watchers
			$context_watchers = CerberusContexts::getWatchers(CerberusContexts::CONTEXT_TICKET, $ticket->id);
			
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
		CerberusContexts::logActivity('ticket.message.outbound', CerberusContexts::CONTEXT_TICKET, $ticket->id, $entry);
		
		// Remove the draft
		if($draft_id)
			DAO_MailQueue::delete($draft_id);
		
		if($message_id)
			return [CerberusContexts::CONTEXT_MESSAGE, $message_id];
		
		return true;
	}
	
	static function parseBroadcastHashCommands(array &$message_properties) {
		@$worker = DAO_Worker::get($message_properties['worker_id']) ?: new Model_Worker();
		
		$lines_in = DevblocksPlatform::parseCrlfString($message_properties['content'], true, false);
		$lines_out = [];
		
		$is_cut = false;
		
		foreach($lines_in as $line) {
			$handled = false;
			$matches = [];
			
			if(preg_match('/^\#([A-Za-z0-9_]+)(.*)$/', $line, $matches)) {
				@$command = $matches[1];
				@$args = ltrim($matches[2]);
				
				switch($command) {
					case 'cut':
						$is_cut = true;
						$handled = true;
						break;
						
					case 'original_message':
						$handled = true;
						break;
					
					case 'signature':
						@$group_id = $message_properties['group_id'] ?: 0;
						@$content_format = $message_properties['content_format'] ?: '';
						@$html_template_id = $message_properties['html_template_id'] ?: 0;
						
						if(false == ($group = DAO_Group::get($group_id))) {
							$line = '';
							break;
						}
						
						$bucket = $group->getDefaultBucket();
						
						switch($content_format) {
							case 'markdown':
							case 'parsedown':
								// Determine if we have an HTML template
								
								if(!$html_template_id || false == ($html_template = DAO_MailHtmlTemplate::get($html_template_id))) {
									if(false == ($html_template = $group->getReplyHtmlTemplate($bucket->id)))
										$html_template = null;
								}
								
								// Determine signature
								
								if(!$html_template || false == ($signature = $html_template->getSignature($worker, 'html'))) {
									$signature = $group->getReplySignature($bucket->id, $worker, true);
								}
								
								// Replace signature
								
								$line = $signature;
								break;
							
							default:
								if($group instanceof Model_Group) {
									$line = $group->getReplySignature($bucket->id, $worker, false);
								} else {
									$line = null;
								}
								break;
						}
						break;
						
					default:
						$handled = false;
						break;
				}
			}
			
			if(!$handled && !$is_cut) {
				$lines_out[] = $line;
			}
		}
		
		$message_properties['content'] = implode("\n", $lines_out);
	}
	
	static function parseComposeHashCommands(Model_Worker $worker, array &$message_properties, array &$commands) {
		$lines_in = DevblocksPlatform::parseCrlfString($message_properties['content'], true, false);
		$lines_out = array();
		
		$is_cut = false;
		
		foreach($lines_in as $line) {
			$handled = false;
			$matches = [];
			
			if(preg_match('/^\#([A-Za-z0-9_]+)(.*)$/', $line, $matches)) {
				@$command = $matches[1];
				@$args = ltrim($matches[2]);
				
				switch($command) {
					case 'attach':
						@$bundle_tag = $args;
						$handled = true;
						
						if(empty($bundle_tag))
							break;
						
						if(false == ($bundle = DAO_FileBundle::getByTag($bundle_tag)))
							break;
						
						$attachments = $bundle->getAttachments();
						
						$message_properties['link_forward_files'] = true;
						
						if(!isset($message_properties['forward_files']))
							$message_properties['forward_files'] = array();
						
						$message_properties['forward_files'] = array_merge($message_properties['forward_files'], array_keys($attachments));
						break;
					
					case 'cut':
						$is_cut = true;
						$handled = true;
						break;
					
					case 'original_message':
						$handled = true;
						break;
					
					case 'signature':
						$group_id = $message_properties['group_id'] ?? null;
						$bucket_id = $message_properties['bucket_id'] ?? null;
						$content_format = $message_properties['content_format'] ?? null;
						$html_template_id = $message_properties['html_template_id'] ?? null;
						
						if(false == ($group = DAO_Group::get($group_id)))
							break;
						
						if(in_array($content_format, ['markdown','parsedown'])) {
							// Template override
							
							$html_template = null;
							
							if($html_template_id)
								$html_template = DAO_MailHtmlTemplate::get($html_template_id);
							
							$signature_text = $signature_html = null;
							
							// If we don't have an HTML template yet, try the group
							if(!$html_template)
								$html_template = $group->getReplyHtmlTemplate($bucket_id);
							
							// If we have a template, use those signatures first
							if($html_template) {
								$signature_text = $html_template->getSignature($worker, 'text');
								$signature_html = $html_template->getSignature($worker, 'html');
							}
							
							// If we don't have a plaintext sig still, use the group one
							if(!$signature_text)
								$signature_text = $group->getReplySignature($bucket_id, $worker, false);
							
							// If we don't have an HTML sig still, use the group one
							if(!$signature_html)
								$signature_html = $group->getReplySignature($bucket_id, $worker, true);
							
							if($signature_text)
								$message_properties['signature'] = $signature_text;
							
							if($signature_html)
								$message_properties['signature_html'] = $signature_html;
							
						} else {
							$message_properties['signature'] = $group->getReplySignature($bucket_id, $worker, false);
						}
						break;
					
					case 'comment':
					case 'watch':
					case 'unwatch':
						$handled = true;
						$commands[] = array(
							'command' => $command,
							'args' => $args,
						);
						break;
					
					default:
						$handled = false;
						break;
				}
			}
			
			if(!$handled && !$is_cut) {
				$lines_out[] = $line;
			}
		}
		
		$message_properties['content'] = implode("\n", $lines_out);
	}
	
	static function handleComposeHashCommands(array $commands, $ticket_id, Model_Worker $worker) {
		foreach($commands as $command_data) {
			switch($command_data['command']) {
				case 'comment':
					@$comment = $command_data['args'];
					
					if(!empty($comment)) {
						$also_notify_worker_ids = array_keys(CerberusApplication::getWorkersByAtMentionsText($comment));
						
						$fields = array(
							DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_TICKET,
							DAO_Comment::CONTEXT_ID => $ticket_id,
							DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
							DAO_Comment::OWNER_CONTEXT_ID => $worker->id,
							DAO_Comment::CREATED => time()+2,
							DAO_Comment::COMMENT => $comment,
						);
						DAO_Comment::create($fields, $also_notify_worker_ids);
					}
					break;
				
				case 'watch':
					CerberusContexts::addWatchers(CerberusContexts::CONTEXT_TICKET, $ticket_id, array($worker->id));
					break;
				
				case 'unwatch':
					CerberusContexts::removeWatchers(CerberusContexts::CONTEXT_TICKET, $ticket_id, array($worker->id));
					break;
			}
		}
	}
	
	static function parseReplyHashCommands(Model_worker $worker, array &$message_properties, array &$commands) {
		$lines_in = DevblocksPlatform::parseCrlfString($message_properties['content'], true, false);
		$lines_out = [];
		
		$is_cut = false;
		
		foreach($lines_in as $line) {
			$handled = false;
			$matches = [];
			
			if(preg_match('/^\#([A-Za-z0-9_]+)(.*)$/', $line, $matches)) {
				@$command = $matches[1];
				@$args = ltrim($matches[2]);
				
				switch($command) {
					case 'attach':
						@$bundle_tag = $args;
						$handled = true;
						
						if(empty($bundle_tag))
							break;
						
						if(false == ($bundle = DAO_FileBundle::getByTag($bundle_tag)))
							break;
						
						$attachments = $bundle->getAttachments();
						
						$message_properties['link_forward_files'] = true;
						
						if(!isset($message_properties['forward_files']))
							$message_properties['forward_files'] = [];
						
						$message_properties['forward_files'] = array_merge($message_properties['forward_files'], array_keys($attachments));
						break;
					
					case 'cut':
						$is_cut = true;
						$handled = true;
						break;
					
					case 'original_message':
						if(false == (@$in_reply_message_id = $message_properties['message_id']))
							break;
						
						if(false == ($message = DAO_Message::get($in_reply_message_id)))
							break;
						
						// Is the worker able to view this message?
						if(!Context_Message::isReadableByActor($message, $worker))
							break;
						
						$message_properties['original_message'] = sprintf("\nOn %s, %s wrote:\n%s",
							date('D, d M Y'),
							$message->getSender()->getNameWithEmail(),
							DevblocksPlatform::services()->string()->indentWith($message->getContent(), '> ')
						);
						
						if(in_array($message_properties['content_format'] ?? null, ['markdown','parsedown'])) {
							if($message->html_attachment_id) {
								$message_content = $message->getContentAsHtml();
							} else {
								$message_content = nl2br(DevblocksPlatform::strEscapeHtml($message->getContent()));
							}
							
							$message_properties['original_message_html'] = sprintf('<div style="margin-top:10px;font-weight:bold;">On %s, %s wrote:</div>',
									date('D, d M Y'),
									DevblocksPlatform::strEscapeHtml($message->getSender()->getNameWithEmail())
							)
							. '<div style="margin-left: 5px;border-left: 3px solid gray;padding-left: 10px;">'
							. $message_content
							. '</div>'
							;
						}
						break;
					
					case 'signature':
						@$group_id = $message_properties['group_id'];
						@$bucket_id = $message_properties['bucket_id'];
						@$content_format = $message_properties['content_format'];
						@$html_template_id = $message_properties['html_template_id'];
						
						if(false == ($group = DAO_Group::get($group_id)))
							break;

						
						if(in_array($content_format, ['markdown','parsedown'])) {
							$signature_text = $signature_html = null;
							
							// HTML template override
							
							$html_template = null;
							
							if($html_template_id)
								$html_template = DAO_MailHtmlTemplate::get($html_template_id);
							
							// Determine if we have an HTML template
							
							// If we don't have an HTML template override, try the group template
							if(!$html_template)
								$html_template = $group->getReplyHtmlTemplate($bucket_id);
							
							if($html_template) {
								$signature_text = $html_template->getSignature($worker, 'text');
								$signature_html = $html_template->getSignature($worker, 'html');
							}
							
							if(!$signature_text)
								$signature_text = $group->getReplySignature($bucket_id, $worker, false);
							
							if(!$signature_html)
								$signature_html = $group->getReplySignature($bucket_id, $worker, true);
							
							if($signature_text)
								$message_properties['signature'] = $signature_text;
							
							if($signature_html)
								$message_properties['signature_html'] = $signature_html;
							
						} else {
							$message_properties['signature'] = $group->getReplySignature($bucket_id, $worker, false);
						}
						break;
					
					case 'comment':
					case 'watch':
					case 'unwatch':
						$handled = true;
						$commands[] = array(
							'command' => $command,
							'args' => $args,
						);
						break;
					
					default:
						$handled = false;
						break;
				}
			}
			
			if(!$handled && !$is_cut) {
				$lines_out[] = $line;
			}
		}
		
		$message_properties['content'] = implode("\n", $lines_out);
	}
	
	static function handleReplyHashCommands(array $commands, Model_Ticket $ticket, Model_Worker $worker) {
		foreach($commands as $command_data) {
			switch($command_data['command']) {
				case 'comment':
					@$comment = $command_data['args'];
					
					if(!empty($comment)) {
						$also_notify_worker_ids = array_keys(CerberusApplication::getWorkersByAtMentionsText($comment));
						
						$fields = array(
							DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_TICKET,
							DAO_Comment::CONTEXT_ID => $ticket->id,
							DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
							DAO_Comment::OWNER_CONTEXT_ID => $worker->id,
							DAO_Comment::CREATED => time()+2,
							DAO_Comment::COMMENT => $comment,
						);
						DAO_Comment::create($fields, $also_notify_worker_ids);
					}
					break;
				
				case 'watch':
					CerberusContexts::addWatchers(CerberusContexts::CONTEXT_TICKET, $ticket->id, array($worker->id));
					break;
				
				case 'unwatch':
					CerberusContexts::removeWatchers(CerberusContexts::CONTEXT_TICKET, $ticket->id, array($worker->id));
					break;
			}
		}
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
		
		if($actor_context) {
			if (!Context_Ticket::isWriteableByActor($ticket, [$actor_context, $actor_context_id]))
				return;
		}

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
							
							if('message/rfc822' == $file->mime_type)
								$attach->setContentType('application/octet-stream');
							
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
			
			@$in_message_id = base_convert($ids[0], 16, 10);
			@$in_worker_id = base_convert($ids[1], 16, 10);
			
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
			
			$mail->setDate(new DateTime('now'));
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
					
					$attach = Swift_Attachment::fromPath($fp_path)
						->setFilename($file->name)
						->setContentType($file->mime_type)
						;
					
					if('message/rfc822' == $file->mime_type)
						$attach->setContentType('application/octet-stream');
					
					$mail->attach($attach);
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
				$attach = Swift_Attachment::fromPath($file->tmpname)
					->setFilename($file_name)
					->setContentType($file->mime_type)
					;
				
				if('message/rfc822' == $file->mime_type)
					$attach->setContentType('application/octet-stream');
				
				$mail->attach($attach);
			}
		
			$result = $mail_service->send($mail);
			
			if(!$result) {
				return false;
			}
			
		} catch (Exception $e) {
			return false;
		}
	}
	
	static private function _generateBodyContentWithPartModifications(array $properties, string $part, string $format) : string {
		$content = $properties['content'] ?? '';
		
		// Apply content modifications in FIFO order
		if(false != ($content_modifications = @$properties['content_modifications']))
		foreach($content_modifications as $content_modification) {
			@$action = $content_modification['action'];
			@$params = $content_modification['params'];
			
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
				@$replace = $params['replace'];
				@$replace_is_regexp = $params['replace_is_regexp'];
				@$with = $params['with'];
				
				if($replace_is_regexp) {
					$content = preg_replace($replace, $with, $content);
				} else {
					$content = str_replace($replace, $with, $content);
				}
			
			// Prepends
			} elseif ($action == 'prepend') {
				@$prepend_content = $params['content'];
				
				$content = $prepend_content . "\n" . $content;
				
			// Appends
			} elseif ($action == 'append') {
				@$append_content = $params['content'];
			
				$content = $content . "\n" . $append_content;
			}
		}
		
		return $content;
	}
	
	// Strip some Markdown in the plaintext version
	static function generateTextFromMarkdown($markdown) {
		$plaintext = null;
		
		$url_writer = DevblocksPlatform::services()->url();
		$base_url = $url_writer->write('c=files', true) . '/';
		
		// Fix references to internal files
		try {
			$plaintext = preg_replace_callback(
				sprintf('|(\!\[(.*?)\]\(%s(.*?)\))|', preg_quote($base_url)),
				function($matches) use ($base_url) {
					if(4 == count($matches)) {
						@list($file_id, $file_name) = explode('/', $matches[3], 2);
						
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
	
	static private function _generateMailBodyMarkdown(&$mail, array $properties, int $group_id=0, int $bucket_id=0) {
		$url_writer = DevblocksPlatform::services()->url();
		@$html_template_id = $properties['html_template_id'] ?: 0;
		
		$embedded_files = [];
		$exclude_files = [];
		
		// Replace signatures for each part
		
		$text_body = CerberusMail::getMailTemplateFromContent($properties, 'sent', 'text');
		$html_body = CerberusMail::getMailTemplateFromContent($properties, 'sent', 'html');
		
		//
		$base_url = $url_writer->write('c=files', true) . '/';
		
		// Generate an HTML part using Parsedown
		if($html_body) {
			
			// Determine if we have an HTML template
			if(!$html_template_id || false == ($html_template = DAO_MailHtmlTemplate::get($html_template_id))) {
				if(!$group_id || !$bucket_id || false == ($group = DAO_Group::get($group_id)) || false == ($html_template = $group->getReplyHtmlTemplate($bucket_id)))
					$html_template = null;
			}
			
			// Use an HTML template wrapper if we have one
			if($html_template instanceof Model_MailHtmlTemplate) {
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				
				$values = [
					'message_body' => $html_body,
				];
				
				if(array_key_exists('outgoing_message_id', $properties)) {
					$values['message_id_header'] = $properties['outgoing_message_id'] ?? '';
				}
				
				if(array_key_exists('token', $properties)) {
					$values['message_token'] = $properties['token'] ?? '';
				}
				
				if(array_key_exists('bucket_id', $properties)) {
					$values['bucket__context'] = CerberusContexts::CONTEXT_BUCKET;
					$values['bucket_id'] = $properties['bucket_id'] ?? 0;
				}
				
				if(array_key_exists('group_id', $properties)) {
					$values['group__context'] = CerberusContexts::CONTEXT_GROUP;
					$values['group_id'] = $properties['group_id'] ?? 0;
				}
				
				if(array_key_exists('ticket_id', $properties)) {
					$values['ticket__context'] = CerberusContexts::CONTEXT_TICKET;
					$values['ticket_id'] = $properties['ticket_id'] ?? 0;
				}
				
				if(array_key_exists('worker_id', $properties)) {
					$values['worker__context'] = CerberusContexts::CONTEXT_WORKER;
					$values['worker_id'] = $properties['worker_id'] ?? 0;
				}
				
				$html_body = $tpl_builder->build(
					$html_template->content,
					$values
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
							list($file_id, $file_name) = array_pad(explode('/', $matches[2], 2), 2, null);
							if($file_id && $file_name) {
								if($file = DAO_Attachment::get($file_id)) {
									
									if(!in_array($file_id, $exclude_files))
										$embedded_files[] = $file_id;
									
									$cid = $mail->embed(new Swift_Image($file->getFileContents(), $file->name, $file->mime_type));
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
		
		$mail->addPart($text_body, 'text/plain');
		
		return $embedded_files;
	}
	
	public static function getMailTemplateFromContent(array $message_properties, string $part, string $format='text') {
		$output = self::_generateBodyContentWithPartModifications(
			$message_properties,
			$part,
			$format
		);
		
		$output = strtr($output, "\r", '');
		
		if('html' == $format) {
			$output = preg_replace('/^#signature$/m', addcslashes($message_properties['signature_html'] ?? '', '\\$'), $output);
			$output = preg_replace('/^#original_message$/m', addcslashes($message_properties['original_message_html'] ?? '', '\\$'), $output);
			return DevblocksPlatform::parseMarkdown($output);
			
		} else {
			$output = preg_replace('/^#signature$/m', addcslashes($message_properties['signature'] ?? '', '\\$'), $output);
			$output = preg_replace('/^#original_message$/m', addcslashes($message_properties['original_message'] ?? '', '\\$'), $output);
			return CerberusMail::generateTextFromMarkdown($output);
		}
	}
};
