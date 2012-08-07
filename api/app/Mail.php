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
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerberus Helpdesk Team
 * 
 * Sure, it would be so easy to just cheat and edit this file to use the 
 * software without paying for it.  But we trust you anyway.  In fact, we're 
 * writing this software for you! 
 * 
 * Quality software backed by a dedicated team takes money to develop.  We 
 * don't want to be out of the office bagging groceries when you call up 
 * needing a helping hand.  We'd rather spend our free time coding your 
 * feature requests than mowing the neighbors' lawns for rent money. 
 * 
 * We've never believed in hiding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your inbox that you probably 
 * haven't had since spammers found you in a game of 'E-mail Battleship'. 
 * Miss. Miss. You sunk my inbox!
 * 
 * A legitimate license entitles you to support from the developers,  
 * and the warm fuzzy feeling of feeding a couple of obsessed developers 
 * who want to help you get more done.
 *
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Scott Luther
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
class CerberusMail {
	private function __construct() {}
	
	static function getMailerDefaults() {
		$settings = DevblocksPlatform::getPluginSettingsService();

		return array(
			'host' => $settings->get('cerberusweb.core',CerberusSettings::SMTP_HOST,CerberusSettingsDefaults::SMTP_HOST),
			'port' => $settings->get('cerberusweb.core',CerberusSettings::SMTP_PORT,CerberusSettingsDefaults::SMTP_PORT),
			'auth_user' => $settings->get('cerberusweb.core',CerberusSettings::SMTP_AUTH_USER,null),
			'auth_pass' => $settings->get('cerberusweb.core',CerberusSettings::SMTP_AUTH_PASS,null),
			'enc' => $settings->get('cerberusweb.core',CerberusSettings::SMTP_ENCRYPTION_TYPE,CerberusSettingsDefaults::SMTP_ENCRYPTION_TYPE),
			'max_sends' => $settings->get('cerberusweb.core',CerberusSettings::SMTP_MAX_SENDS,CerberusSettingsDefaults::SMTP_MAX_SENDS),
			'timeout' => $settings->get('cerberusweb.core',CerberusSettings::SMTP_TIMEOUT,CerberusSettingsDefaults::SMTP_TIMEOUT),
		);
	}
	
	static function parseRfcAddresses($string) {
		$results = array();
		$string = rtrim(str_replace(';',',',$string),' ,');
		$parsed = imap_rfc822_parse_adrlist($string, 'localhost');
		
		if(is_array($parsed))
		foreach($parsed as $parsed_addy) {
			@$mailbox = strtolower($parsed_addy->mailbox);
			@$host = strtolower($parsed_addy->host);
			@$personal = isset($parsed_addy->personal) ? $parsed_addy->personal : null;
			
			if(empty($mailbox) || empty($host))
				continue;
			if($mailbox == 'INVALID_ADDRESS')
				continue;
			
			$results[$mailbox . '@' . $host] = array(
				'full_email' => !empty($personal) ? imap_rfc822_write_address($mailbox, $host, $personal) : imap_rfc822_write_address($mailbox, $host, null),
				'email' => $mailbox . '@' . $host,
				'mailbox' => $mailbox,
				'host' => $host,
				'personal' => $personal,
			);
		}
		
		return $results;
	}
	
	static function quickSend($to, $subject, $body, $from_addy=null, $from_personal=null) {
		try {
			$mail_service = DevblocksPlatform::getMailService();
			$mailer = $mail_service->getMailer(CerberusMail::getMailerDefaults());
			$mail = $mail_service->createMessage();
	
		    $settings = DevblocksPlatform::getPluginSettingsService();
		    
		    if(empty($from_addy) || empty($from_personal)) {
		    	if(null == ($replyto_default = DAO_AddressOutgoing::getDefault()))
		    		throw new Exception("There is no default reply-to.");
		    	
		    	if(empty($from_addy))
		    		$from_addy = $replyto_default->email;
		    	if(empty($from_personal))
		    		$from_personal = $replyto_default->getReplyPersonal();
		    }
		    
			$mail->setTo(DevblocksPlatform::parseCsvString($to));
			
			if(!empty($from_personal)) {
				$mail->setFrom($from_addy, $from_personal);
			} else {
				$mail->setFrom($from_addy);
			}
			
			$mail->setSubject($subject);
			$mail->generateId();
			
			$headers = $mail->getHeaders();
			
			$headers->addTextHeader('X-Mailer','Cerberus Helpdesk ' . APP_VERSION . ' (Build '.APP_BUILD.')');
			
			$mail->setBody($body);
		
			// [TODO] Report when the message wasn't sent.
			if(!$mailer->send($mail)) {
				return false;
			}
			
		} catch (Exception $e) {
			return false;
		}
		
		return true;
	}

	static function compose($properties) {
		$worker = CerberusApplication::getActiveWorker();
		
		@$group_id = $properties['group_id'];
		@$bucket_id = intval($properties['bucket_id']);
		$properties['worker_id'] = $worker->id;
	
		if(null == ($group = DAO_Group::get($group_id)))
			return;
		
	    // Changing the outgoing message through a VA
	    Event_MailBeforeSentByGroup::trigger($properties, null, null, $group_id);
		
		@$org_id = $properties['org_id'];
		@$toStr = $properties['to'];
		@$cc = $properties['cc'];
		@$bcc = $properties['bcc'];
		@$subject = $properties['subject'];
		@$content = $properties['content'];
		@$files = $properties['files'];
		@$forward_files = $properties['forward_files'];
		
		@$closed = $properties['closed'];
		@$ticket_reopen = $properties['ticket_reopen'];
		
		@$dont_send = $properties['dont_send'];
		
		$from_replyto = $group->getReplyTo($bucket_id);
		$personal = $group->getReplyPersonal($bucket_id, $worker);
		
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
		
		$mail_headers = array();
		$mail_headers['X-CerberusCompose'] = '1';
		
		try {
			$mail_service = DevblocksPlatform::getMailService();
			$mailer = $mail_service->getMailer(CerberusMail::getMailerDefaults());
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
			$email->generateId();
			
			$headers = $email->getHeaders();
			
			$headers->addTextHeader('X-Mailer','Cerberus Helpdesk ' . APP_VERSION . ' (Build '.APP_BUILD.')');
			
			$email->setBody($content);
			
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
					$attachment = DAO_Attachment::get($file_id);
					if(false !== ($fp = DevblocksPlatform::getTempFile())) {
						if(false !== $attachment->getFileContents($fp)) {
							$attach = Swift_Attachment::fromPath(DevblocksPlatform::getTempFileInfo($fp), $attachment->mime_type);
							$attach->setFilename($attachment->display_name);
							$email->attach($attach);
							fclose($fp);
						}
					}
				}
			}
			
		    // Headers
			foreach($email->getHeaders()->getAll() as $hdr) {
				if(null != ($hdr_val = $hdr->getFieldBody())) {
					if(!empty($hdr_val))
						$mail_headers[$hdr->getFieldName()] = CerberusParser::fixQuotePrintableString($hdr_val, LANG_CHARSET_CODE);
				}
			}
			
			if(empty($dont_send)) {
				if(!@$mailer->send($email)) {
					throw new Exception('Mail failed to send: unknown reason');
				}
			}
	
		} catch (Exception $e) {
			@$draft_id = $properties['draft_id'];
			
			if(empty($draft_id)) {
				$params = array(
					'to' => $toStr,
					'group_id' => $group_id,
					'bucket_id' => $bucket_id,
				);
				
				if(!empty($cc))
					$params['cc'] = $cc;
					
				if(!empty($bcc))
					$params['bcc'] = $bcc;
					
				$fields = array(
					DAO_MailQueue::TYPE => Model_MailQueue::TYPE_COMPOSE,
					DAO_MailQueue::TICKET_ID => 0,
					DAO_MailQueue::WORKER_ID => !empty($worker) ? $worker->id : 0,
					DAO_MailQueue::UPDATED => time()+5, // small offset
					DAO_MailQueue::HINT_TO => $toStr,
					DAO_MailQueue::SUBJECT => $subject,
					DAO_MailQueue::BODY => $content,
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
			DAO_Ticket::LAST_ACTION_CODE => CerberusTicketActionCode::TICKET_WORKER_REPLY,
		);
		
		// "Next:" [TODO] This is highly redundant with CerberusMail::reply
		
		if(isset($ticket_reopen) && !empty($ticket_reopen)) {
			$due = strtotime($ticket_reopen);
			if($due) $fields[DAO_Ticket::DUE_DATE] = $due;
		}
		// End "Next:"
		
		$ticket_id = DAO_Ticket::create($fields);

	    $fields = array(
	        DAO_Message::TICKET_ID => $ticket_id,
	        DAO_Message::CREATED_DATE => time(),
	        DAO_Message::ADDRESS_ID => $fromAddressId,
	        DAO_Message::IS_OUTGOING => 1,
	        DAO_Message::WORKER_ID => (!empty($worker->id) ? $worker->id : 0),
	    );
		$message_id = DAO_Message::create($fields);
	    
		// Content
		Storage_MessageContent::put($message_id, $content);

		// Set recipients to requesters
		foreach($toList as $to_addy => $to_data) {
			DAO_Ticket::createRequester($to_addy, $ticket_id);
		}
		
		// Headers
		foreach($mail_headers as $hdr_key => $hdr_val) {
			DAO_MessageHeader::create($message_id, $hdr_key, $hdr_val);
		}
		
		// add files to ticket
		if (is_array($files) && !empty($files)) {
			reset($files);
			foreach ($files['tmp_name'] as $idx => $file) {
				if(empty($file) || empty($files['name'][$idx]) || !file_exists($file))
					continue;
					
				$fields = array(
					DAO_Attachment::DISPLAY_NAME => $files['name'][$idx],
					DAO_Attachment::MIME_TYPE => $files['type'][$idx],
				);
				$file_id = DAO_Attachment::create($fields);

				// Link
				DAO_AttachmentLink::create($file_id, CerberusContexts::CONTEXT_MESSAGE, $message_id);
				
				// Content
				if(null !== ($fp = fopen($file, 'rb'))) {
					Storage_Attachments::put($file_id, $fp);
					fclose($fp);
	            	unlink($file);
				}
			}
		}

		// Finalize ticket
		$fields = array(
			DAO_Ticket::FIRST_MESSAGE_ID => $message_id,
			DAO_Ticket::LAST_MESSAGE_ID => $message_id,
		);
		
		if(isset($closed) && 1==$closed)
			$fields[DAO_Ticket::IS_CLOSED] = 1;
		if(isset($closed) && 2==$closed)
			$fields[DAO_Ticket::IS_WAITING] = 1;
		
		// Move last, so the event triggers properly
	    $fields[DAO_Ticket::GROUP_ID] = $group_id;
	    $fields[DAO_Ticket::BUCKET_ID] = $bucket_id;
	    
		DAO_Ticket::update($ticket_id, $fields);
		
		// Train as not spam
		CerberusBayes::markTicketAsNotSpam($ticket_id);
		
		// Custom fields
		@$custom_fields = isset($properties['custom_fields']) ? $properties['custom_fields'] : array();
		if(is_array($custom_fields) && !empty($custom_fields)) {
			DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_TICKET, $ticket_id, $custom_fields);
		}
		
        // Events
        if(!empty($message_id) && !empty($group_id)) {
			// After message sent in group
			Event_MailAfterSentByGroup::trigger($message_id, $group_id);			

			// Mail received by group
        	Event_MailReceivedByGroup::trigger($message_id, $group_id);
        }
        
		return $ticket_id;
	}
	
	static function sendTicketMessage($properties=array()) {
	    $settings = DevblocksPlatform::getPluginSettingsService();
	    
		/*
	    'draft_id'
	    'message_id'
	    'is_forward'
	    -----'ticket_id'
		'subject'
	    'to'
	    'cc'
	    'bcc'
	    'content'
	    'files'
	    'closed'
	    'ticket_reopen'
	    'bucket_id'
	    'owner_id'
	    'worker_id',
		'is_autoreply',
		'custom_fields',
		'dont_send',
		'dont_keep_copy'
		*/

		try {
			// objects
		    $mail_service = DevblocksPlatform::getMailService();
		    $mailer = $mail_service->getMailer(CerberusMail::getMailerDefaults());
			$mail = $mail_service->createMessage();
	        
		    @$reply_message_id = $properties['message_id'];
		    
		    if(null == ($message = DAO_Message::get($reply_message_id)))
				return;
				
			$ticket_id = $message->ticket_id;
	        
			if(null == ($ticket = DAO_Ticket::get($ticket_id)))
				return;
				
			if(null == ($group = DAO_Group::get($ticket->group_id)))
				return;
		    
		    // Changing the outgoing message through a VA
		    Event_MailBeforeSentByGroup::trigger($properties, $message->id, $ticket->id, $group->id);
		    
		    // Re-read properties
		    @$content = $properties['content'];
		    @$files = $properties['files'];
		    @$is_forward = $properties['is_forward']; 
		    @$forward_files = $properties['forward_files'];
		    @$worker_id = $properties['worker_id'];
		    @$subject = $properties['subject'];
		    
		    @$is_autoreply = $properties['is_autoreply'];
		    
	        $message_headers = DAO_MessageHeader::getAll($reply_message_id);

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
			
			$mail->generateId();
			
			$headers = $mail->getHeaders();
			
			$headers->addTextHeader('X-Mailer','Cerberus Helpdesk ' . APP_VERSION . ' (Build '.APP_BUILD.')');
	
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
					$first_email = strtolower($requester->email);
					$first_split = explode('@', $first_email);
			
					if(!is_array($first_split) || count($first_split) != 2)
						continue;
			
					// Ourselves?
					if(DAO_AddressOutgoing::isLocalAddressId($requester->id))
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
			
			// Body
			$mail->setBody($content);
	
			// Mime Attachments
			if (is_array($files) && !empty($files)) {
				foreach ($files['tmp_name'] as $idx => $file) {
					if(empty($file) || empty($files['name'][$idx]))
						continue;
	
					$mail->attach(Swift_Attachment::fromPath($file)->setFilename($files['name'][$idx]));
				}
			}
	
			// Forward Attachments
			if(!empty($forward_files) && is_array($forward_files)) {
				foreach($forward_files as $file_id) {
					$attachment = DAO_Attachment::get($file_id);
					if(false !== ($fp = DevblocksPlatform::getTempFile())) {
						if(false !== $attachment->getFileContents($fp)) {
							$attach = Swift_Attachment::fromPath(DevblocksPlatform::getTempFileInfo($fp), $attachment->mime_type);
							$attach->setFilename($attachment->display_name);
							$mail->attach($attach);
							fclose($fp);
						}
					}
				}
			}

			// Send
			$recipients = $mail->getTo();
			$send_headers = array();
			
		    // Save headers before sending
			foreach($headers->getAll() as $hdr) {
				if(null != ($hdr_val = $hdr->getFieldBody())) {
					if(!empty($hdr_val))
						$send_headers[$hdr->getFieldName()] = CerberusParser::fixQuotePrintableString($hdr_val, LANG_CHARSET_CODE);
				}
			}
			
			// If blank recipients or we're not supposed to send
			if(empty($recipients) || (isset($properties['dont_send']) && $properties['dont_send'])) {
				// ...do nothing
			} else { // otherwise send
				if(!@$mailer->send($mail)) {
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
				
				if(empty($to)) {
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
				DAO_MailQueue::create($fields);
			}
			
			return false;
		}
		
		$change_fields = array();
		
		$fromAddressInst = CerberusApplication::hashLookupAddress($from_replyto->email, true);
		$fromAddressId = $fromAddressInst->id;
		
		if((!isset($properties['dont_keep_copy']) || !$properties['dont_keep_copy'])
			&& empty($is_autoreply)) {
			$change_fields[DAO_Ticket::LAST_MESSAGE_ID] = $fromAddressId;
			$change_fields[DAO_Ticket::LAST_WROTE_ID] = $fromAddressId;
			$change_fields[DAO_Ticket::UPDATED_DATE] = time();
			
		    if(!empty($worker_id)) {
		        $change_fields[DAO_Ticket::LAST_ACTION_CODE] = CerberusTicketActionCode::TICKET_WORKER_REPLY;
		    }
		    
		    // Only change the subject if not forwarding
		    if(!empty($subject) && !$is_forward) {
		    	$change_fields[DAO_Ticket::SUBJECT] = $subject;
		    }
		    
		    // Fields
		    
		    $fields = array(
		        DAO_Message::TICKET_ID => $ticket_id,
		        DAO_Message::CREATED_DATE => time(),
		        DAO_Message::ADDRESS_ID => $fromAddressId,
		        DAO_Message::IS_OUTGOING => 1,
		        DAO_Message::WORKER_ID => (!empty($worker_id) ? $worker_id : 0),
		    	DAO_Message::RESPONSE_TIME => (!empty($worker_id) ? (time() - $message->created_date) : 0),
		    );
			$message_id = DAO_Message::create($fields);
		    
			// Store ticket.last_message_id
			$change_fields[DAO_Ticket::LAST_MESSAGE_ID] = $message_id;
			
			// Content
			Storage_MessageContent::put($message_id, $content);

			// Save cached headers
			foreach($send_headers as $hdr_key => $hdr_val) {
				if(empty($hdr_key) || empty($hdr_val))
					continue;
    			DAO_MessageHeader::create($message_id, $hdr_key, $hdr_val);
			}
		    
			// Attachments
			if (is_array($files) && !empty($files)) {
				reset($files);
				foreach ($files['tmp_name'] as $idx => $file) {
					if(empty($file) || empty($files['name'][$idx]) || !file_exists($file))
						continue;

					// Create record
					$fields = array(
						DAO_Attachment::DISPLAY_NAME => $files['name'][$idx],
						DAO_Attachment::MIME_TYPE => $files['type'][$idx],
					);
					$file_id = DAO_Attachment::create($fields);

					// Link
					DAO_AttachmentLink::create($file_id, CerberusContexts::CONTEXT_MESSAGE, $message_id);
					
					// Content
					if(null !== ($fp = fopen($file, 'rb'))) {
		            	Storage_Attachments::put($file_id, $fp);
						fclose($fp);
			            unlink($file);
					}
				}
			}
		}
		
		if(isset($properties['owner_id'])) {
			if(empty($properties['owner_id']) || null != (DAO_Worker::get($properties['owner_id'])))
				$change_fields[DAO_Ticket::OWNER_ID] = intval($properties['owner_id']);
		}
		
		// Post-Reply Change Properties

		if(isset($properties['closed'])) {
			switch($properties['closed']) {
				case 0: // open
					$change_fields[DAO_Ticket::IS_WAITING] = 0;
					$change_fields[DAO_Ticket::IS_CLOSED] = 0;
					$change_fields[DAO_Ticket::IS_DELETED] = 0;
					$change_fields[DAO_Ticket::DUE_DATE] = 0;
					break;
				case 1: // closed
					$change_fields[DAO_Ticket::IS_WAITING] = 0;
					$change_fields[DAO_Ticket::IS_CLOSED] = 1;
					$change_fields[DAO_Ticket::IS_DELETED] = 0;
					
					if(isset($properties['ticket_reopen'])) {
						@$time = intval(strtotime($properties['ticket_reopen']));
						$change_fields[DAO_Ticket::DUE_DATE] = $time;
					}
					break;
				case 2: // waiting
					$change_fields[DAO_Ticket::IS_WAITING] = 1;
					$change_fields[DAO_Ticket::IS_CLOSED] = 0;
					$change_fields[DAO_Ticket::IS_DELETED] = 0;
					
					if(isset($properties['ticket_reopen'])) {
						@$time = intval(strtotime($properties['ticket_reopen']));
						$change_fields[DAO_Ticket::DUE_DATE] = $time;
					}
					break;
			}
		}

		// Move
		if(!empty($properties['bucket_id'])) {
		    // [TODO] Use API to move, or fire event
	        list($group_id, $bucket_id) = CerberusApplication::translateGroupBucketCode($properties['bucket_id']);
		    $change_fields[DAO_Ticket::GROUP_ID] = $group_id;
		    $change_fields[DAO_Ticket::BUCKET_ID] = $bucket_id;
		}
			
		if(!empty($ticket_id) && !empty($change_fields)) {
		    DAO_Ticket::update($ticket_id, $change_fields);
		}

		// Custom fields
		@$custom_fields = isset($properties['custom_fields']) ? $properties['custom_fields'] : array();
		if(is_array($custom_fields) && !empty($custom_fields)) {
			DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_TICKET, $ticket_id, $custom_fields);
		}
		
		// Events
		if(!empty($message_id) && empty($no_events)) {
			// After message sent in group
			Event_MailAfterSentByGroup::trigger($message_id, $group->id);			
			
			// New message for group
			Event_MailReceivedByGroup::trigger($message_id, $group->id);

			// Watchers
			$context_watchers = CerberusContexts::getWatchers(CerberusContexts::CONTEXT_TICKET, $ticket_id);
			if(is_array($context_watchers))
			foreach($context_watchers as $watcher_id => $watcher) {
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
		
		return true;
	}
	
	static function reflect(CerberusParserModel $model, $to) {
		try {
			$message = $model->getMessage(); /* @var $message CerberusParserMessage */
			
			$mail_service = DevblocksPlatform::getMailService();
			$mailer = $mail_service->getMailer(CerberusMail::getMailerDefaults()); /* @var $mailer Swift_Mailer */
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
				$mail->setFrom($sender_addy->email, empty($sender_name) ? null : $sender_name);
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
		
			$result = $mailer->send($mail);
			
			if(!$result) {
				return false;
			}
			
		} catch (Exception $e) {
			return false;
		}
	}
	
};
