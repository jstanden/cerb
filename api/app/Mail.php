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
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\IdentificationHeader;
use Symfony\Component\Mime\Part\DataPart;

class CerberusMail {
	private function __construct() {}
	
	static function writeRfcAddress($email, $personal=null) {
		$is_quoted = false;
		
		if($personal) {
			if (str_contains($personal, '.')) {
				$is_quoted = true;
			}
			
			if (str_contains($personal, '"')) {
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
		
		if(is_null($string))
			$string = '';
		
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
								
								if(strlen($mailbox ?? '') && strlen($host ?? '')) {
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
			list($name, $value) = array_pad(explode(':', $header, 2), 2, null);
			
			$name = trim(DevblocksPlatform::strLower($name));
			$value = trim($value);
			
			$results[$name] = $value;
		}
		
		return $results;
	}
	
	static function quickSend($to, $subject, $body, $from_addy=null, $from_personal=null, $custom_headers=[], $format=null, $html_template_id=null, $file_ids=[], $cc=null, $bcc=null, $is_sensitive=false, &$error=null) : bool {
		$error = null;
		
		$properties = [
			'to' => $to,
			'subject' => $subject,
			'content' => $body,
			'content_format' => $format,
			'html_template_id' => $html_template_id,
			'forward_file_ids' => $file_ids,
			'link_forward_files' => true,
			'headers' => $custom_headers ?? [],
			'is_sensitive' => $is_sensitive,
		];
		
		if($bcc) $properties['bcc'] = $bcc;
		if($cc) $properties['cc'] = $cc;
		
		if($from_addy) {
			$properties['from'] = $from_addy;
			$properties['from_personal'] = $from_personal;
		}
		
		return (bool) CerberusMail::sendTransactional($properties, $error);
	}
	
	/**
	 * @param array $properties
	 * @return array|false
	 */
	static function compose(array $properties, ?string &$error=null) : array|false {
		$mail_service = DevblocksPlatform::services()->mail();
		
		if(!($email_model = $mail_service->createComposeModelFromProperties($properties, $error)))
			return false;
		
		unset($properties);
		
		// Modify with behaviors
		$email_model->triggerComposeBehaviors();
		
		// #commands
		$email_model->parseComposeHashCommands();
		
		if(!$email_model->send($error)) {
			if($error) return false;
			return [CerberusContexts::CONTEXT_DRAFT, $email_model->getProperty('draft_id', 0)];
		}
		
		unset($worker);
		
		// Use the email model to create the ticket and message
		
		$draft_id = $email_model->getProperty('draft_id');
		$from_address = $email_model->getFromAddressModel();
		$embedded_files = [];
		
		$fields = [
			DAO_Ticket::MASK => $email_model->getTicketMask(),
			DAO_Ticket::SUBJECT => $email_model->getProperty('subject', '(no subject)'),
			DAO_Ticket::STATUS_ID => 0,
			DAO_Ticket::OWNER_ID => 0,
			DAO_Ticket::REOPEN_AT => 0,
			DAO_Ticket::CREATED_DATE => time(),
			DAO_Ticket::FIRST_WROTE_ID => $from_address->id ?? 0,
			DAO_Ticket::LAST_WROTE_ID => $from_address->id ?? 0,
			DAO_Ticket::ORG_ID => $email_model->getOrgId(),
		];
		
		// If we failed to create the ticket, save the draft
		if(!($ticket_id = DAO_Ticket::create($fields))) {
			if ($draft_id) {
				return [CerberusContexts::CONTEXT_DRAFT, $draft_id];
			} else {
				return false;
			}
		}
		
		// Save a copy of the sent HTML body
		$fields = [
			DAO_Message::TICKET_ID => $ticket_id,
			DAO_Message::CREATED_DATE => time(),
			DAO_Message::ADDRESS_ID => $from_address->id ?? 0,
			DAO_Message::IS_OUTGOING => 1,
			DAO_Message::WORKER_ID => $email_model->getProperty('worker_id', 0),
			DAO_Message::IS_BROADCAST => $email_model->getProperty('is_broadcast') ? 1 : 0,
			DAO_Message::IS_NOT_SENT => !$email_model->isDeliverable() ? 1 : 0,
			DAO_Message::HASH_HEADER_MESSAGE_ID => sha1('<' . $email_model->getProperty('outgoing_message_id') . '>'),
			DAO_Message::WAS_ENCRYPTED => ($email_model->getProperty('gpg_encrypt')) ? 1 : 0,
		];
		
		if($email_model->isBodyFormatted()) {
			if(($html_saved = $email_model->getBodyHtmlSaved($embedded_files))) {
				$url_writer = DevblocksPlatform::services()->url();
				$base_url = $url_writer->write('c=files', true);
				
				$attachments = DAO_Attachment::getIds($embedded_files);
				
				foreach($embedded_files as $cid => $file_id) {
					if(($file = $attachments[$file_id] ?? null)) {
						$html_saved = str_replace(
							$cid,
							sprintf('%s/%d/%s', $base_url, $file->id, rawurlencode($file->name)),
							$html_saved
						);
					}
				}
				
				$fields[DAO_Message::_CONTENT_HTML] = $html_saved;
				
				unset($attachments);
			}
		}
		
		if($email_model->getProperty('gpg_sign')) {
			$fields[DAO_Message::SIGNED_AT] = time();
		}
		
		if(($message_token = $email_model->getProperty('token'))) {
			$fields[DAO_Message::TOKEN] = $message_token;
		}
		
		// If we fail to create the record, keep the draft
		if(!($message_id = DAO_Message::create($fields))) {
			if($draft_id) {
				return [CerberusContexts::CONTEXT_DRAFT, $draft_id];
			} else {
				return false;
			}
		}
		
		// Convert to a plaintext part
		$plaintext_saved = $email_model->getBodyTextSaved();
		Storage_MessageContent::put($message_id, $plaintext_saved);
		unset($plaintext_saved);

		// Set recipients to requesters
		foreach(array_keys($email_model->getTo()) as $to_addy) {
			DAO_Ticket::createRequester($to_addy, $ticket_id);
		}
		
		// Headers
		if(($outgoing_email_headers = $email_model->getResult('outgoing_email_headers'))) {
			DAO_MessageHeaders::upsert($message_id, $outgoing_email_headers);
			unset($outgoing_email_headers);
			
		} else {
			$outgoing_email_headers = $email_model->getHeadersText();
			DAO_MessageHeaders::upsert($message_id, $outgoing_email_headers);
			unset($outgoing_email_headers);
		}
		
		// Forwarded attachments
		if($email_model->getProperty('link_forward_files')) {
			// Attachments
			if(($forward_files = $email_model->getProperty('forward_files')) && is_array($forward_files)) {
				if(($file_models = DAO_Attachment::getIds($forward_files))) {
					DAO_Attachment::addLinks(CerberusContexts::CONTEXT_MESSAGE, $message_id, array_keys($file_models));
				}
				unset($file_models);
			}
			unset($forward_files);
		}
		
		// Link embedded files
		if($embedded_files) {
			DAO_Attachment::addLinks(CerberusContexts::CONTEXT_MESSAGE, $message_id, $embedded_files);
			unset($embedded_files);
		}
		
		// Message custom fields
		if(($message_custom_fields = $email_model->getProperty('message_custom_fields'))) {
			if ($message_id && is_array($message_custom_fields)) {
				DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_MESSAGE, $message_id, $message_custom_fields, true, true, false);
			}
			unset($message_custom_fields);
		}
		
		// Finalize ticket
		$ticket_fields = [
			DAO_Ticket::FIRST_MESSAGE_ID => $message_id,
			DAO_Ticket::LAST_MESSAGE_ID => $message_id,
		];

		$bucket = $email_model->getBucket();
		$worker = $email_model->getWorker();
		
		if(($ticket = DAO_Ticket::get($ticket_id))) {
			$properties = $email_model->getProperties();
			DAO_Ticket::updateWithMessageProperties($properties, $ticket, $ticket_fields, false);
			$email_model->setProperties($properties);
		}
		
		// Train as not spam
		CerberusBayes::markTicketAsNotSpam($ticket_id);
		
		if($worker) {
			// Save worker prefs
			DAO_WorkerPref::set($worker->id, 'compose.group_id', $bucket->group_id ?? 0);
			DAO_WorkerPref::set($worker->id, 'compose.bucket_id', $bucket->id ?? 0);
			
			// #commands
			if(($hash_commands = $email_model->getResult('hash_commands'))) {
				$email_model->runComposeHashCommands($hash_commands);
				unset($hash_commands);
			}
		}
		
		self::_composeTriggerEvents($message_id, $bucket->group_id ?? 0);
		
		// Remove the draft
		if($draft_id)
			DAO_MailQueue::delete($draft_id);
		
		return [CerberusContexts::CONTEXT_TICKET, $ticket_id];
	}
	
	static private function _composeTriggerEvents($message_id, $group_id) {
		// Events
		if(!empty($message_id) && !empty($group_id)) {
			// Changing the outgoing message through an automation (before behaviors)
			AutomationTrigger_MailSent::trigger($message_id, priority_range:[0, 127]);
			
			// After message sent (global)
			Event_MailAfterSent::trigger($message_id);
			
			// After message sent in group
			Event_MailAfterSentByGroup::trigger($message_id, $group_id);
			
			// Mail received
			Event_MailReceived::trigger($message_id);
			
			// Mail received by group
			Event_MailReceivedByGroup::trigger($message_id, $group_id);
			
			// Changing the outgoing message through an automation (after behaviors)
			AutomationTrigger_MailSent::trigger($message_id, priority_range:[128, 255]);
		}
	}
	
	static function sendTransactional(array $properties, ?string &$error=null) : array|false {
		$mail_service = DevblocksPlatform::services()->mail();
		
		if(!($email_model = $mail_service->createTransactionalModelFromProperties($properties, $error)))
			return false;
		
		unset($properties);
		
		if(!$email_model->send($error)) {
			if($error) return false;
			return [CerberusContexts::CONTEXT_DRAFT, $email_model->getProperty('draft_id', 0)];
		}
		
		// Remove the draft on successful delivery
		if (($draft_id = $email_model->getProperty('draft_id')))
			DAO_MailQueue::delete($draft_id);
		
		return [CerberusContexts::CONTEXT_DRAFT, $email_model->getProperty('draft_id', 0)];
	}
	
	static function sendTicketReply(array $properties, ?string &$error=null) : array|false {
		$mail_service = DevblocksPlatform::services()->mail();
		
		if(!($email_model = $mail_service->createReplyModelFromProperties($properties, $error)))
			return false;
		
		$ticket = $email_model->getTicket();
		
		// Modify with behaviors
		$runners = $email_model->triggerReplyBehaviors(
			$email_model->getProperty('message_id'),
			$ticket->id,
			$email_model->getProperty('group_id') ?? $ticket->group_id
		);
		
		$hash_commands = [];
		
		DAO_Ticket::updateWithMessageProperties($properties, $ticket, [], false);
		
		// Re-cache if the ticket was modified, or we have override group/bucket
		if(
			$runners
			|| (
				$email_model->getProperty('group_id')
				&& $ticket->group_id != $email_model->getProperty('group_id'))
			|| (
				$email_model->getProperty('bucket_id')
				&& $ticket->bucket_id != $email_model->getProperty('bucket_id'))
		) {
			$email_model->setProperty('ticket_id', null);
			$email_model->setProperty('ticket_id', $ticket->id);
			$ticket = $email_model->getTicket();
		}
		
		$email_model->setProperty('group_id', $ticket->group_id);
		$email_model->setProperty('bucket_id', $ticket->bucket_id);
		
		unset($properties);
		
		if(($worker = $email_model->getWorker())) {
			$change_properties = $email_model->getProperties();
			// [TODO] move this into the message model
			CerberusMail::parseReplyHashCommands($worker, $change_properties, $hash_commands);
			$email_model->setProperties($change_properties);
		}
		
		if(!$email_model->send($error)) {
			if($error) return false;
			return [CerberusContexts::CONTEXT_DRAFT, $email_model->getProperty('draft_id', 0)];
		}
		
		unset($ticket);
		unset($worker);
		
		// Save the new message from the email model
		
		$draft_id = $email_model->getProperty('draft_id');
		$message = $email_model->getMessage();
		$ticket = $email_model->getTicket();
		$worker = $email_model->getWorker();
		$from_address = $email_model->getFromAddressModel();
		
		$change_fields = [];
		
		// Not spam if untrained, not an autoreply, and sent by a worker
		if ($ticket->spam_training == CerberusTicketSpamTraining::BLANK
			&& !$email_model->getProperty('is_autoreply')
			&& !$worker
		) {
			CerberusBayes::markTicketAsNotSpam($ticket->id);
		}
		
		// If we're not saving or it's an auto-reply
		if($email_model->getProperty('dont_keep_copy') || $email_model->getProperty('is_autoreply')) {
			// Remove the draft
			if ($draft_id)
				DAO_MailQueue::delete($draft_id);
			
			return match(true) {
				!empty($ticket->id) => [CerberusContexts::CONTEXT_TICKET, $ticket->id],
				!empty($draft_id) => [CerberusContexts::CONTEXT_DRAFT, $draft_id],
				default => [true],
			};
		}
		
		$change_fields[DAO_Ticket::LAST_WROTE_ID] = $from_address->id;
		$change_fields[DAO_Ticket::UPDATED_DATE] = time();
		
		// If not forwarding, set the subject
		if (!$email_model->getProperty('is_forward') && ($subject = $email_model->getProperty('subject'))) {
			$change_fields[DAO_Ticket::SUBJECT] = $subject;
		}
		
		// Fields
		$fields = [
			DAO_Message::TICKET_ID => $ticket->id ?? 0,
			DAO_Message::CREATED_DATE => time(),
			DAO_Message::ADDRESS_ID => $from_address->id ?? 0,
			DAO_Message::IS_OUTGOING => 1,
			DAO_Message::RESPONSE_TIME => 0,
			DAO_Message::WORKER_ID => $worker->id ?? 0,
			DAO_Message::IS_BROADCAST => $email_model->getProperty('is_broadcast') ? 1 : 0,
			DAO_Message::IS_NOT_SENT => $email_model->getProperty('dont_send') ? 1 : 0,
			DAO_Message::WAS_ENCRYPTED => $email_model->getProperty('gpg_encrypt') ? 1 : 0,
		];
		
		// Response time
		if ($worker && $ticket && $message) {
			$fields[DAO_Message::RESPONSE_TIME] = time() - max($ticket->created_date, $message->created_date);
		}
		
		// Message-Id header for threading
		if (($outgoing_message_id = $email_model->getProperty('outgoing_message_id'))) {
			$fields[DAO_Message::HASH_HEADER_MESSAGE_ID] = sha1('<' . $outgoing_message_id . '>');
		}
		
		$embedded_files = [];
		
		// [TODO] Move this to the model to share in compose/reply?
		if ($email_model->isBodyFormatted()) {
			if (($html_saved = $email_model->getBodyHtmlSaved($embedded_files))) {
				$url_writer = DevblocksPlatform::services()->url();
				$base_url = $url_writer->write('c=files', true);
				
				$attachments = DAO_Attachment::getIds($embedded_files);
				
				// [TODO] We can use `cid:1234@attachment` or something, which survives URL changes and works in the API
				// [TODO] Also need to do that in the parser
				foreach ($embedded_files as $cid => $file_id) {
					if (($file = $attachments[$file_id] ?? null)) {
						$html_saved = str_replace(
							$cid,
							sprintf('%s/%d/%s', $base_url, $file->id, rawurlencode($file->name)),
							$html_saved
						);
					}
				}
				
				$fields[DAO_Message::_CONTENT_HTML] = $html_saved;
				
				unset($attachments);
			}
		}
		
		// Did we sign it?
		if ($email_model->getProperty('gpg_sign')) {
			$fields[DAO_Message::SIGNED_AT] = time();
		}
		
		// Draft->Message token consistency
		if (($token = $email_model->getProperty('token'))) {
			$fields[DAO_Message::TOKEN] = $token;
		}
		
		// Create the new message record
		$new_message_id = DAO_Message::create($fields);
		
		// If we fail to create the record, keep the draft
		if (!$new_message_id) {
			if ($draft_id) {
				return [CerberusContexts::CONTEXT_DRAFT, $draft_id];
			} else {
				return false;
			}
		}
		
		// Index the first message
		if(!$ticket->first_message_id) {
			$change_fields[DAO_Ticket::FIRST_MESSAGE_ID] = $new_message_id;
			$change_fields[DAO_Ticket::FIRST_WROTE_ID] = $from_address->id ?? 0;
		}
		
		// Store ticket.last_message_id
		$change_fields[DAO_Ticket::LAST_MESSAGE_ID] = $new_message_id;
		
		// First outgoing message?
		if (empty($ticket->first_outgoing_message_id) && $worker) {
			$change_fields[DAO_Ticket::FIRST_OUTGOING_MESSAGE_ID] = $new_message_id;
			$change_fields[DAO_Ticket::ELAPSED_RESPONSE_FIRST] = max(time() - $ticket->created_date, 0);
		}
		
		// Convert to a plaintext part
		$plaintext_saved = $email_model->getBodyTextSaved();
		Storage_MessageContent::put($new_message_id, $plaintext_saved);
		unset($plaintext_saved);
		
		// Headers
		if ($outgoing_email_headers = $email_model->getResult('outgoing_email_headers')) {
			DAO_MessageHeaders::upsert($new_message_id, $outgoing_email_headers);
			unset($outgoing_email_headers);
		} else {
			$outgoing_email_headers = $email_model->getHeadersText();
			DAO_MessageHeaders::upsert($new_message_id, $outgoing_email_headers);
			unset($outgoing_email_headers);
		}
		
		// Forwarded attachments
		if ($email_model->getProperty('link_forward_files')) {
			// Attachments
			if (($forward_files = $email_model->getProperty('forward_files')) && is_array($forward_files)) {
				if(($file_models = DAO_Attachment::getIds($forward_files))) {
					DAO_Attachment::addLinks(CerberusContexts::CONTEXT_MESSAGE, $new_message_id, array_keys($file_models));
				}
				unset($file_models);
			}
			unset($forward_files);
		}
		
		// Link embedded files
		if ($embedded_files && is_array($embedded_files)) {
			DAO_Attachment::addLinks(CerberusContexts::CONTEXT_MESSAGE, $new_message_id, $embedded_files);
		}
		
		// Ticket
		DAO_Ticket::update($ticket->id, $change_fields);
		
		// Message custom fields
		if (($message_custom_fields = $email_model->getProperty('message_custom_fields'))) {
			if ($new_message_id && is_array($message_custom_fields)) {
				DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_MESSAGE, $new_message_id, $email_model->getProperty('message_custom_fields'), true, true, false);
			}
			unset($message_custom_fields);
		}
		
		// Events
		if($new_message_id) {
			if($worker && $hash_commands)
				CerberusMail::handleReplyHashCommands($hash_commands, $ticket, $new_message_id, $worker);
			
			// Changing the outgoing message through an automation (before behaviors)
			AutomationTrigger_MailSent::trigger($new_message_id, priority_range:[0, 127]);
			
			// After message sent (global)
			Event_MailAfterSent::trigger($new_message_id);
			
			// After message sent in group
			Event_MailAfterSentByGroup::trigger($new_message_id, $ticket->group_id);
			
			// Mail received
			Event_MailReceived::trigger($new_message_id);
			
			// New message for group
			Event_MailReceivedByGroup::trigger($new_message_id, $ticket->group_id);

			// Watchers
			$context_watchers = CerberusContexts::getWatchers(CerberusContexts::CONTEXT_TICKET, $ticket->id);
			
			// Include the owner
			if(!empty($ticket->owner_id) && !isset($context_watchers[$ticket->owner_id]))
				$context_watchers[$ticket->owner_id] = true;

			if(is_array($context_watchers)) {
				foreach (array_unique(array_keys($context_watchers)) as $watcher_id) {
					Event_MailReceivedByWatcher::trigger($new_message_id, $watcher_id);
				}
			}
			
			// Changing the outgoing message through an automation (after behaviors)
			AutomationTrigger_MailSent::trigger($new_message_id, priority_range:[128, 255]);
		}
		
		/*
		 * Log activity (ticket.message.outbound)
		 * {{actor}} responded to ticket {{target}}
		 */
		$entry = [];
		$log_actor_context = $worker ? CerberusContexts::CONTEXT_WORKER : null;
		$log_actor_context_id = $worker->id ?? null;
		CerberusContexts::logActivity('ticket.message.outbound', CerberusContexts::CONTEXT_TICKET, $ticket->id, $entry, $log_actor_context, $log_actor_context_id);
		
		// Remove the draft
		if($draft_id)
			DAO_MailQueue::delete($draft_id);
		
		return match(true) {
			!empty($new_message_id) => [CerberusContexts::CONTEXT_MESSAGE, $new_message_id],
			!empty($draft_id) => [CerberusContexts::CONTEXT_DRAFT, $draft_id],
			default => [true]
		};
	}
	
	static function parseBroadcastHashCommands(array &$message_properties) : void {
		$worker = DAO_Worker::get($message_properties['worker_id'] ?? 0) ?: new Model_Worker();
		
		$lines_in = DevblocksPlatform::parseCrlfString($message_properties['content'], true, false);
		$lines_out = [];
		
		$is_cut = false;
		
		foreach($lines_in as $line) {
			$handled = false;
			$matches = [];
			
			if(preg_match('/^\#([A-Za-z0-9_]+)(.*)$/', $line, $matches)) {
				$command = $matches[1] ?? null;
				
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
						
						if(!($group = DAO_Group::get($group_id))) {
							$line = '';
							break;
						}
						
						$bucket = $group->getDefaultBucket();
						
						switch($content_format) {
							case 'markdown':
							case 'parsedown':
								// Determine if we have an HTML template
								
								if(!$html_template_id || !($html_template = DAO_MailHtmlTemplate::get($html_template_id))) {
									if(!($html_template = $group->getReplyHtmlTemplate($bucket->id)))
										$html_template = null;
								}
								
								// Determine signature
								
								if(!$html_template || !($signature = $html_template->getSignature($worker, 'html'))) {
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
		$lines_out = [];
		
		$is_cut = false;
		
		while(false !== ($line = current($lines_in))) {
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
						
						if(!($bundle = DAO_FileBundle::getByTag($bundle_tag)))
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
					
					case 'sig':
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
					
					case 'start':
						if(!in_array($args, ['comment','note']))
							break;
						
						$comment_body = '';
						while(false !== ($comment_line = next($lines_in)) 
							&& !DevblocksPlatform::strStartsWith($comment_line, '#end')
						) {
							$comment_body .= $comment_line .  "\n";
						}
						
						if($comment_body) {
							$handled = true;
							$commands[] = [
								'command' => $args,
								'args' => $comment_body,
							];
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
			
			next($lines_in);
		}
		
		$message_properties['content'] = implode("\n", $lines_out);
	}
	
	static function parseReplyHashCommands(Model_worker $worker, array &$message_properties, array &$commands) {
		$lines_in = DevblocksPlatform::parseCrlfString($message_properties['content'], true, false);
		$lines_out = [];
		
		$is_cut = false;
		
		while(false !== ($line = current($lines_in))) {
			$handled = false;
			$matches = [];
			
			if(preg_match('/^\#([A-Za-z0-9_]+)(.*)$/', $line, $matches)) {
				$command = $matches[1] ?? '';
				$args = ltrim($matches[2] ?? '');
				
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
						if(!(@$in_reply_message_id = $message_properties['message_id']))
							break;
						
						if(!($message = DAO_Message::get($in_reply_message_id)))
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
					
					case 'sig':
					case 'signature':
						$group_id = $message_properties['group_id'] ?? null;
						$bucket_id = $message_properties['bucket_id'] ?? null;
						$content_format = $message_properties['content_format'] ?? null;
						$html_template_id = $message_properties['html_template_id'] ?? null;
						
						if(!($group = DAO_Group::get($group_id)))
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
					
					case 'start':
						if(!in_array($args, ['comment','note']))
							break;
						
						$comment_body = '';
						while(false !== ($comment_line = next($lines_in))
							&& !DevblocksPlatform::strStartsWith($comment_line, '#end')
						) {
							$comment_body .= $comment_line .  "\n";
						}
						
						if($comment_body) {
							$handled = true;
							$commands[] = array(
								'command' => $args,
								'args' => $comment_body,
							);
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
			
			next($lines_in);
		}
		
		$message_properties['content'] = implode("\n", $lines_out);
	}
	
	static function handleReplyHashCommands(array $commands, Model_Ticket $ticket, $message_id, Model_Worker $worker) {
		foreach($commands as $command_data) {
			switch($command_data['command']) {
				case 'comment':
					$comment = $command_data['args'] ?? null;
					
					if(!empty($comment)) {
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
					
					if(!empty($comment)) {
						$fields = [
							DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_MESSAGE,
							DAO_Comment::CONTEXT_ID => $message_id,
							DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
							DAO_Comment::OWNER_CONTEXT_ID => $worker->id,
							DAO_Comment::CREATED => time(),
							DAO_Comment::COMMENT => $comment,
							DAO_Comment::IS_MARKDOWN => 1,
						];
						$comment_id = DAO_Comment::create($fields);
						DAO_Comment::onUpdateByActor($worker, $fields, $comment_id);
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
	
	static function relay($message_id, $emails, $include_attachments = false, $content = null, $actor_context = null, $actor_context_id = null) : bool {
		$mail_service = DevblocksPlatform::services()->mail();
		$settings = DevblocksPlatform::services()->pluginSettings();

		$relay_spoof_from = $settings->get('cerberusweb.core', CerberusSettings::RELAY_SPOOF_FROM, CerberusSettingsDefaults::RELAY_SPOOF_FROM);
		
		if(!($message = DAO_Message::get($message_id)))
			return false;
		
		if(!($ticket = DAO_Ticket::get($message->ticket_id)))
			return false;

		if(!($group = DAO_Group::get($ticket->group_id)))
			return false;
		
		if(!($sender = $message->getSender()))
			return false;
		
		if($actor_context) {
			if (!Context_Ticket::isWriteableByActor($ticket, [$actor_context, $actor_context_id]))
				return false;
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
				if(!($to_model = DAO_Address::getByEmail($to)))
					continue;
				
				if(!($worker = $to_model->getWorker()))
					continue;
				
				$properties = [
					'is_sensitive' => true,
					'headers' => [],
				];
				
				$properties['to'] = $to;
				
				if($relay_spoof_from) {
					$properties['from'] = $sender->email;
					$properties['from_personal'] = $sender_name ?? null;
					$properties['reply_to'] = $replyto->email;
				} else {
					$properties['from'] = $replyto->email;
					$properties['reply_to'] = $replyto->email;
				}

				// Subject
				$properties['subject'] = sprintf("[relay #%s] %s", $ticket->mask, $ticket->subject);;
				
				$signed_message_id = CerberusMail::relaySign($message->id, $worker->id);
				$properties['outgoing_message_id'] = $signed_message_id;
				
				$properties['headers']['X-CerberusRedirect'] = '1';
				
				$properties['content'] = $content;
				
				$properties['forward_files'] = array_keys($attachments);
				
				$email_model = $mail_service->createTransactionalModelFromProperties($properties);
				
				$result = $mail_service->send($email_model);
				unset($mail);
				
				/*
				 * Log activity (ticket.message.relay)
				 * {{actor}} relayed ticket {{target}} to {{worker}} ({{worker_email}})
				 */
				$entry = [
					'variables' => [
						'worker' => $worker->getName(),
						'worker_email' => $to,
					],
					'urls' => [
						'worker' => sprintf("cerb:worker:%d", $worker->id),
					],
				];
				CerberusContexts::logActivity('ticket.message.relay', CerberusContexts::CONTEXT_TICKET, $ticket->id, $entry, $actor_context, $actor_context_id);
				
				if(!$result)
					return false;
				
			} catch (Exception $e) {
				DevblocksPlatform::logException($e);
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
	 * @return string
	 */
	static function relaySign($message_id, $worker_id) {
		$encrypt = DevblocksPlatform::services()->encryption();
		
		$string_to_encrypt = sprintf('%s:%s',
			base_convert($message_id, 10, 16),
			base_convert($worker_id, 10, 16)
		);
		
		$encrypted_header = $encrypt->encrypt($string_to_encrypt);
		
		return sprintf("%s.%s@cerb",
			rtrim($encrypted_header,'='),
			base_convert(time(), 10, 16)
		);
	}
	
	static function relayVerify($auth_header, $worker_id) {
		$encrypt = DevblocksPlatform::services()->encryption();
		$hits = [];
		
		// Procedural signing format
		if(@preg_match('#\<(.*?)\.([a-f0-9]+)\@cerb\>#', $auth_header, $hits)) {
			$encrypted_message = $hits[1] ?? null;
			
			if(!$encrypted_message)
				return false;
			
			$decrypted_message = $encrypt->decrypt($encrypted_message);
			
			$ids = explode(':', $decrypted_message, 2);
			
			$in_message_id = base_convert($ids[0] ?? '', 16, 10);
			$in_worker_id = base_convert($ids[1] ?? '', 16, 10);
			
			if($in_worker_id != $worker_id)
				return false;
			
			return $in_message_id;
		}
		
		return false;
	}
	
	static function resend(Model_Message $message, &$error=null, $only_return_source=false) : string|bool {
		try {
			$mail_service = DevblocksPlatform::services()->mail();
			
			$properties = [
				'headers' => [],
			];
			
			$current_headers = $message->getHeaders();
			$attachments = $message->getAttachments();
			
			$from = CerberusMail::parseRfcAddresses($current_headers['from']);
			$from = array_shift($from);
			
			$properties['from'] = $from['email'];
			$properties['from_personal'] = $from['personal'] ?: '';
			
			if(($tos = CerberusMail::parseRfcAddresses($current_headers['to'] ?? '')))
				$properties['to'] = implode(', ', array_keys($tos));
			
			if(($ccs = CerberusMail::parseRfcAddresses($current_headers['cc'] ?? '')))
				$properties['cc'] = implode(', ', array_keys($ccs));
			
			if(($bccs = CerberusMail::parseRfcAddresses($current_headers['bcc'] ?? '')))
				$properties['bcc'] = implode(', ', array_keys($bccs));
			
			$properties['subject'] = $current_headers['subject'] ?? '(no subject)';
			
			if(isset($current_headers['in-reply-to']))
				$properties['headers']['In-Reply-To'] = $current_headers['in-reply-to'];
			
			if(isset($current_headers['references']))
				$properties['headers']['References'] = $current_headers['references'] . ' ' . $current_headers['message-id'];
			
			$properties['headers']['X-Cerb-Resend'] = 'true';
			
			// Set the plaintext body

			$properties['content'] = $message->getContent();
			
			// Attachments
			
			if(array_filter($attachments, fn(Model_Attachment $attachment) => $attachment->name == 'original_message.html'))
				$properties['content_format'] = 'markdown';
			
			$properties['forward_files'] = array_keys(array_filter($attachments, fn(Model_Attachment $attachment) => $attachment->name != 'original_message.html'));
			
			$email_model = $mail_service->createTransactionalModelFromProperties($properties);
			
			if($only_return_source) {
				$smtp_message = CerberusMail::getSmtpMessageFromModel($email_model);
				return $smtp_message->toString();
				
			} else {
				if(!$mail_service->send($email_model)) {
					$error = $mail_service->getLastErrorMessage();
					return false;
				}
				
				return true;
			}
			
		} catch (Throwable $e) {
			DevblocksPlatform::logException($e);
			return false;
		}
	}
	
	static function reflect(CerberusParserModel $model, $to) {
		try {
			$message = $model->getParserMessage(); /* @var $message CerberusParserMessage */
			
			$mail_service = DevblocksPlatform::services()->mail();
			
			$properties = [
				'to' => $to,
				'headers' => [],
			];
			
			if(isset($message->headers['subject'])) {
				if(is_array($message->headers['subject']))
					$subject = array_shift($message->headers['subject']);
				else
					$subject = $message->headers['subject'];
				$properties['subject'] = $subject;
			}
			if(isset($message->headers['message-id']))
				$properties['outgoing_message_id'] = $message->headers['message-id'];
			if(isset($message->headers['in-reply-to']))
				$properties['headers']['In-Reply-To'] = $message->headers['in-reply-to'];
			if(isset($message->headers['references']))
				$properties['headers']['References'] = $message->headers['references'];
			if(isset($message->headers['from'])) {
				$sender_addy = $model->getSenderAddressModel(); /* @var $sender_addy Model_Address */
				
				if($message->headers['reply-to'] ?? null) {
					$properties['from_personal'] = $message->headers['reply-to'];
					$properties['reply_to'] = $message->headers['reply-to'];
				} else {
					$properties['from_personal'] = $sender_addy->email;
					$properties['reply_to'] = $sender_addy->email;
				}
			}
			if(isset($message->headers['return-path'])) {
				$return_path = is_array($message->headers['return-path'])
					? array_shift($message->headers['return-path'])
					: $message->headers['return-path'];
				$return_path = trim($return_path,'<>');
				$properties['return_path'] = $return_path;
			}
			
			$properties['headers']['X-CerberusRedirect'] = '1';
			
			$properties['content'] = $message->body;
			
			$properties['forward_files'] = [];
			
			if(is_array($message->files))
			foreach($message->files as $file_name => $file) { /* @var $file ParserFile */
				$storage_sha1hash = sha1_file($file->tmpname);
				
				if(($attachment = DAO_Attachment::getBySha1Hash($storage_sha1hash))) {
					$file_id = $attachment->id;
					
				} else {
					if('message/rfc822' == $file->mime_type)
						$file->mime_type = 'application/octet-stream';
					
					$file_id = DAO_Attachment::create([
						DAO_Attachment::NAME => $file_name,
						DAO_Attachment::MIME_TYPE => $file->mime_type,
						DAO_Attachment::STORAGE_SHA1HASH => $storage_sha1hash,
					]);
					
					// Store the content in the new file
					if(null !== ($fp = fopen($file->getTempFile(), 'rb'))) {
						Storage_Attachments::put($file_id, $fp);
						fclose($fp);
					}
				}
				
				if($file_id)
					$properties['forward_files'][] = $file_id;
			}
			
			$email_model = $mail_service->createTransactionalModelFromProperties($properties);
		
			if(!$mail_service->send($email_model)) {
				return false;
			}
			
		} catch (Exception $e) {
			DevblocksPlatform::logException($e);
			return false;
		}
	}
	
	public static function getSmtpMessageFromModel(Model_DevblocksOutboundEmail $email_model, $as_summary=false) : Email {
		$smtp_email = new Email();
		
		$headers = $smtp_email->getHeaders();

		try {
			// To
			foreach ($email_model->getTo() as $k => $v) {
				$smtp_email->addTo(new Address($k, $v['personal'] ?: ''));
			}
			
			// Cc
			foreach ($email_model->getCc() as $k => $v) {
				$smtp_email->addCc(new Address($k, $v['personal'] ?: ''));
			}
			
			// Bcc
			foreach ($email_model->getBcc() as $k => $v) {
				$smtp_email->addBcc(new Address($k, $v['personal'] ?: ''));
			}
			
			$from_model = $email_model->getFromAddressModel();
			$from_personal = $email_model->getFromPersonal();
			
			$smtp_email->from(new Address($from_model->email, $from_personal ?: ''));
			$smtp_email->subject($email_model->getSubject());
			
			// Allow 'Reply-To:' overrides on transactional
			if(Model_MailQueue::TYPE_TRANSACTIONAL == $email_model->getType()) {
				if(($reply_to = $email_model->getProperty('reply_to'))) {
					$smtp_email->replyTo($reply_to);
				}
				unset($reply_to);
				
				if(($return_path = $email_model->getProperty('return_path'))) {
					$smtp_email->returnPath($return_path);
				}
				unset($return_path);
			}
			
			// Custom headers
			if(($email_headers = $email_model->getProperty('headers', [])) && is_array($email_headers)) {
				foreach ($email_headers as $header_key => $header_val) {
					if (!empty($header_key) && is_string($header_key) && is_string($header_val)) {
						// Overrides
						if (strtolower(trim($header_key)) == 'from') {
							if (($address = CerberusMail::parseRfcAddress($header_val)))
								$smtp_email->from($address['email']);
							unset($email_headers[$header_key]);
						} elseif (strtolower(trim($header_key)) == 'to') {
							if (($addresses = CerberusMail::parseRfcAddresses($header_val)))
								$smtp_email->to(...array_map(fn($address) => new Address($address['email']), $addresses));
							unset($email_headers[$header_key]);
						} elseif (strtolower(trim($header_key)) == 'cc') {
							if (($addresses = CerberusMail::parseRfcAddresses($header_val)))
								$smtp_email->cc(...array_map(fn($address) => new Address($address['email']), $addresses));
							unset($email_headers[$header_key]);
						} elseif (strtolower(trim($header_key)) == 'bcc') {
							if (($addresses = CerberusMail::parseRfcAddresses($header_val)))
								$smtp_email->bcc(...array_map(fn($address) => new Address($address['email']), $addresses));
							unset($email_headers[$header_key]);
						} else {
							if (null == ($header = $headers->get($header_key))) {
								$headers->addTextHeader($header_key, $header_val);
								
							} else {
								if ($header instanceof IdentificationHeader)
									continue;
								
								if (method_exists($header, 'setValue'))
									$header->setValue($header_val);
							}
						}
					}
				}
				
				$email_model->setProperty('headers', $email_headers);
				unset($email_headers);
			}
			
			// Body
			
			if(!$as_summary) {
				if ($email_model->isBodyFormatted()) {
					$content_sent = $email_model->getBodyTextSent();
					$smtp_email->text($content_sent);
					unset($content_sent);
					
					$embedded_files = [];
					$content_html_sent = $email_model->getBodyHtmlSent($embedded_files);
					$email_model->setResult('embedded_files', $embedded_files);
					
					// Embed content-id images
					foreach ($embedded_files as $cid => $file_id) {
						// [TODO] Bulk load files
						if ($file = DAO_Attachment::get($file_id)) {
							$image = new DataPart($file->getFileContents(), $file->name, $file->mime_type);
							$smtp_email->addPart($image->setContentId(substr($cid, 4))->asInline());
						}
					}
					$smtp_email->html($content_html_sent);
					
				} else {
					$content_sent = $email_model->getBodyTextSent();
					$smtp_email->text($content_sent);
				}
			}
			
			// Forward Attachments
			
			if(!$as_summary && ($forward_files = $email_model->getProperty('forward_files')) && is_array($forward_files)) {
				$attachments = DAO_Attachment::getIds($forward_files);
				
				foreach ($attachments as $attachment) {
					if (($fp = DevblocksPlatform::getTempFile())) {
						if ($attachment->getFileContents($fp) !== false) {
							if ('message/rfc822' == $attachment->mime_type)
								$attachment->mime_type = 'application/octet-stream';
							
							$smtp_email->addPart(DataPart::fromPath(DevblocksPlatform::getTempFileInfo($fp), $attachment->name, $attachment->mime_type));
							fclose($fp);
						}
					}
				}
			}
			
		} catch (Exception $e) {
			throw $e;
		}
		
		return $smtp_email;
	}
};
