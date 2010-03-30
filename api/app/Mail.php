<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2007, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
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
 * We've never believed in encoding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your in-box that you probably 
 * haven't had since spammers found you in a game of "E-mail Address 
 * Battleship".  Miss. Miss. You sunk my in-box!
 * 
 * A legitimate license entitles you to support, access to the developer 
 * mailing list, the ability to participate in betas and the warm fuzzy 
 * feeling of feeding a couple obsessed developers who want to help you get 
 * more done than 'the other guy'.
 *
 * - Jeff Standen, Mike Fogg, Brenan Cavish, Darren Sugita, Dan Hildebrandt
 * 		and Joe Geck.
 *   WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
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
	
	static function quickSend($to, $subject, $body, $from_addy=null, $from_personal=null) {
		try {
			$mail_service = DevblocksPlatform::getMailService();
			$mailer = $mail_service->getMailer(CerberusMail::getMailerDefaults());
			$mail = $mail_service->createMessage();
	
		    $settings = DevblocksPlatform::getPluginSettingsService();
		    
		    if(empty($from_addy))
				@$from_addy = $settings->get('cerberusweb.core',CerberusSettings::DEFAULT_REPLY_FROM, CerberusSettingsDefaults::DEFAULT_REPLY_FROM);
		    
		    if(empty($from_personal))
				@$from_personal = $settings->get('cerberusweb.core',CerberusSettings::DEFAULT_REPLY_PERSONAL,CerberusSettingsDefaults::DEFAULT_REPLY_PERSONAL);
			
			$mail->setTo(array($to));
			$mail->setFrom(array($from_addy => $from_personal));
			$mail->setSubject($subject);
			$mail->generateId();
			
			$headers = $mail->getHeaders();
			
			$headers->addTextHeader('X-Mailer','Cerberus Helpdesk (Build '.APP_BUILD.')');
			
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
		@$team_id = $properties['team_id'];
		@$toStr = $properties['to'];
		@$cc = $properties['cc'];
		@$bcc = $properties['bcc'];
		@$subject = $properties['subject'];
		@$content = $properties['content'];
		@$files = $properties['files'];

		@$no_mail = $properties['no_mail'];
		
		@$closed = $properties['closed'];
		@$move_bucket = $properties['move_bucket'];
		@$next_worker_id = $properties['next_worker_id'];
		@$ticket_reopen = $properties['ticket_reopen'];
		@$unlock_date = $properties['unlock_date'];
		
		$worker = CerberusApplication::getActiveWorker();
		
		$settings = DevblocksPlatform::getPluginSettingsService();
		$default_from = $settings->get('cerberusweb.core',CerberusSettings::DEFAULT_REPLY_FROM,CerberusSettingsDefaults::DEFAULT_REPLY_FROM);
		$default_personal = $settings->get('cerberusweb.core',CerberusSettings::DEFAULT_REPLY_PERSONAL,CerberusSettingsDefaults::DEFAULT_REPLY_PERSONAL);
		
		$team_from = DAO_GroupSettings::get($team_id,DAO_GroupSettings::SETTING_REPLY_FROM,'');
		$team_personal = DAO_GroupSettings::get($team_id,DAO_GroupSettings::SETTING_REPLY_PERSONAL,'');
		$team_personal_with_worker = DAO_GroupSettings::get($team_id,DAO_GroupSettings::SETTING_REPLY_PERSONAL_WITH_WORKER,0);
		
		$from = !empty($team_from) ? $team_from : $default_from;
		$personal = !empty($team_personal) ? $team_personal : $default_personal;
		
		// Prefix the worker name on the personal line?
		if(!empty($team_personal_with_worker) && !empty($worker)) {
			$personal = $worker->getName() . ', ' . $personal;
		}
		
		$mask = CerberusApplication::generateTicketMask();

		if(empty($subject)) $subject = '(no subject)';
		
		// add mask to subject if group setting calls for it
		@$group_has_subject = intval(DAO_GroupSettings::get($team_id,DAO_GroupSettings::SETTING_SUBJECT_HAS_MASK,0));
		@$group_subject_prefix = DAO_GroupSettings::get($team_id,DAO_GroupSettings::SETTING_SUBJECT_PREFIX,'');
		$prefix = sprintf("[%s#%s] ",
			!empty($group_subject_prefix) ? ($group_subject_prefix.' ') : '',
			$mask
		);
		$subject_mailed = (sprintf('%s%s',
			$group_has_subject ? $prefix : '',
			$subject
		));
		
		// [JAS]: Replace any semi-colons with commas (people like using either)
		$toList = DevblocksPlatform::parseCsvString(str_replace(';', ',', $toStr));
		
		$mail_headers = array();
		$mail_headers['X-CerberusCompose'] = '1';
		
		$mail_succeeded = true;
		if(!empty($no_mail)) { // allow compose without sending mail
			// Headers needed for the ticket message
			$log_headers = new Swift_Message_Headers();
			$log_headers->setCharset(LANG_CHARSET_CODE);
			$log_headers->set('To', $toStr);
			$log_headers->set('From', !empty($personal) ? (sprintf("%s <%s>",$personal,$from)) : (sprintf('%s',$from)));
			$log_headers->set('Subject', $subject_mailed);
			$log_headers->set('Date', date('r'));
			
			foreach($log_headers->getList() as $hdr => $v) {
				if(null != ($hdr_val = $log_headers->getEncoded($hdr))) {
					if(!empty($hdr_val))
						$mail_headers[$hdr] = $hdr_val;
				}
			}
		} else { // regular mail sending
			try {
				$mail_service = DevblocksPlatform::getMailService();
				$mailer = $mail_service->getMailer(CerberusMail::getMailerDefaults());
				$email = $mail_service->createMessage();
		
				$email->setTo($toList);
				
				// cc
				$ccs = array();
				if(!empty($cc) && null != ($ccList = DevblocksPlatform::parseCsvString(str_replace(';',',',$cc)))) {
					$email->setCc($ccList);
				}
				
				// bcc
				if(!empty($bcc) && null != ($bccList = DevblocksPlatform::parseCsvString(str_replace(';',',',$bcc)))) {
					$email->setBcc($bccList);
				}
				
				$email->setFrom(array($from => $personal));
				$email->setSubject($subject_mailed);
				$email->generateId();
				
				$headers = $email->getHeaders();
				
				$headers->addTextHeader('X-Mailer','Cerberus Helpdesk (Build '.APP_BUILD.')');
				
				$email->setBody($content);
				
				// Mime Attachments
				if (is_array($files) && !empty($files)) {
					foreach ($files['tmp_name'] as $idx => $file) {
						if(empty($file) || empty($files['name'][$idx]))
							continue;
		
						$email->attach(Swift_Attachment::fromPath($file)->setFilename($files['name'][$idx]));
					}
				}
				
				// [TODO] Allow separated addresses (parseRfcAddress)
		//		$mailer->log->enable();
				if(!$mailer->send($email)) {
					$mail_succeeded = false;
					throw new Exception('Mail failed to send: unknown reason');
				}
		//		$mailer->log->dump();
			} catch (Exception $e) {
				// tag mail as failed, add note to message after message gets created			
				$mail_succeeded = false;
			}

		    // Headers
			foreach($email->getHeaders()->getAll() as $hdr) {
				if(null != ($hdr_val = $hdr->getFieldBody())) {
					if(!empty($hdr_val))
						$mail_headers[$hdr->getFieldName()] = $hdr_val;
				}
			}
		}
		
		$fromAddressInst = CerberusApplication::hashLookupAddress($from, true);
		$fromAddressId = $fromAddressInst->id;
		
		// [TODO] this is redundant with the Parser code.  Should be refactored later
		
		$fields = array(
			DAO_Ticket::MASK => $mask,
			DAO_Ticket::SUBJECT => $subject,
			DAO_Ticket::CREATED_DATE => time(),
			DAO_Ticket::FIRST_WROTE_ID => $fromAddressId,
			DAO_Ticket::LAST_WROTE_ID => $fromAddressId,
			DAO_Ticket::LAST_ACTION_CODE => CerberusTicketActionCode::TICKET_WORKER_REPLY,
			DAO_Ticket::LAST_WORKER_ID => $worker->id,
			DAO_Ticket::NEXT_WORKER_ID => 0,
			DAO_Ticket::TEAM_ID => $team_id,
		);
		
		// "Next:" [TODO] This is highly redundant with CerberusMail::reply
		
		if(isset($closed) && 1==$closed)
			$fields[DAO_Ticket::IS_CLOSED] = 1;
		if(isset($closed) && 2==$closed)
			$fields[DAO_Ticket::IS_WAITING] = 1;
		if(!empty($move_bucket)) {
	        list($team_id, $bucket_id) = CerberusApplication::translateTeamCategoryCode($move_bucket);
		    $fields[DAO_Ticket::TEAM_ID] = $team_id;
		    $fields[DAO_Ticket::CATEGORY_ID] = $bucket_id;
		}
		if(isset($next_worker_id))
			$fields[DAO_Ticket::NEXT_WORKER_ID] = intval($next_worker_id);
		if(isset($ticket_reopen) && !empty($ticket_reopen)) {
			$due = strtotime($ticket_reopen);
			if($due) $fields[DAO_Ticket::DUE_DATE] = $due;
		}
        // Allow anybody to reply after 
		if(!empty($unlock_date)) {
		    $unlock = strtotime($unlock_date);
		    if(intval($unlock) > 0)
	            $fields[DAO_Ticket::UNLOCK_DATE] = $unlock;
		}
		// End "Next:"
		
		$ticket_id = DAO_Ticket::createTicket($fields);
		
	    $fields = array(
	        DAO_Message::TICKET_ID => $ticket_id,
	        DAO_Message::CREATED_DATE => time(),
	        DAO_Message::ADDRESS_ID => $fromAddressId,
	        DAO_Message::IS_OUTGOING => 1,
	        DAO_Message::WORKER_ID => (!empty($worker->id) ? $worker->id : 0),
	    );
		$message_id = DAO_Message::create($fields);
	    
		// Link Message to Ticket
		DAO_Ticket::updateTicket($ticket_id, array(
			DAO_Ticket::FIRST_MESSAGE_ID => $message_id,
		));
		
		// Content
		Storage_MessageContent::put($message_id, $content);

		// Set recipients to requesters
		foreach($toList as $to) {
			if(null != ($reqAddressInst = CerberusApplication::hashLookupAddress($to, true))) {
				$reqAddressId = $reqAddressInst->id;
				DAO_Ticket::createRequester($reqAddressId, $ticket_id);
			}
		}
		
		// Headers
		foreach($mail_headers as $hdr => $hdr_val) {
			DAO_MessageHeader::create($message_id, $hdr, CerberusParser::fixQuotePrintableString($hdr_val));			
		}
		
		// add files to ticket
		if (is_array($files) && !empty($files)) {
			reset($files);
			foreach ($files['tmp_name'] as $idx => $file) {
				if(empty($file) || empty($files['name'][$idx]) || !file_exists($file))
					continue;
					
				$fields = array(
					DAO_Attachment::MESSAGE_ID => $message_id,
					DAO_Attachment::DISPLAY_NAME => $files['name'][$idx],
					DAO_Attachment::MIME_TYPE => $files['type'][$idx],
				);
				$file_id = DAO_Attachment::create($fields);

				if(null !== ($fp = fopen($file, 'rb'))) {
					Storage_Attachments::put($file_id, $fp);
					fclose($fp);
	            	unlink($file);
				}
			}
		}
		
		// Train as not spam
		CerberusBayes::markTicketAsNotSpam($ticket_id);
		
		// Inbound/Outbound Reply Event
		// [TODO] This pivots on $no_mail for now, but this functionality may change
	    $eventMgr = DevblocksPlatform::getEventService();
	    if($no_mail) { // inbound
		    $eventMgr->trigger(
		        new Model_DevblocksEvent(
		            'ticket.reply.inbound',
	                array(
	                    'ticket_id' => $ticket_id,
	                )
	            )
		    );
	    	
	    } else { // outbound
	    	if(!empty($worker->id))
		    $eventMgr->trigger(
		        new Model_DevblocksEvent(
		            'ticket.reply.outbound',
	                array(
	                    'ticket_id' => $ticket_id,
	                    'worker_id' => $worker->id
	                )
	            )
		    );
	    }
		
		// if email sending failed, add an error note to the message
		if ($mail_succeeded === false) {
			$fields = array(
				DAO_MessageNote::MESSAGE_ID => $message_id,
				DAO_MessageNote::CREATED => time(),
				DAO_MessageNote::WORKER_ID => 0,
				DAO_MessageNote::CONTENT => 'Exception thrown while sending email: ' . $e->getMessage(),
				DAO_MessageNote::TYPE => Model_MessageNote::TYPE_ERROR,
			);
			DAO_MessageNote::create($fields);
		}
		
		return $ticket_id;
	}
	
	static function sendTicketMessage($properties=array()) {
	    $settings = DevblocksPlatform::getPluginSettingsService();
	    $helpdesk_senders = CerberusApplication::getHelpdeskSenders();
	    
		@$from_addy = $settings->get('cerberusweb.core',CerberusSettings::DEFAULT_REPLY_FROM, CerberusSettingsDefaults::DEFAULT_REPLY_FROM);
		@$from_personal = $settings->get('cerberusweb.core',CerberusSettings::DEFAULT_REPLY_PERSONAL,CerberusSettingsDefaults::DEFAULT_REPLY_PERSONAL);
	    // [TODO] If we still don't have a $from_addy we need a graceful failure. 
		
		/*
	     * [TODO] Move these into constants?
	    'message_id'
	    -----'ticket_id'
		'subject'
	    'to'
	    'cc'
	    'bcc'
	    'content'
	    'files'
	    'closed'
	    'ticket_reopen'
	    'unlock_date'
	    'bucket_id'
	    'agent_id',
		'is_autoreply',
		'dont_send',
		'dont_save_copy'
		*/

		$mail_succeeded = true;
		try {
			// objects
		    $mail_service = DevblocksPlatform::getMailService();
		    $mailer = $mail_service->getMailer(CerberusMail::getMailerDefaults());
			$mail = $mail_service->createMessage();
	        
		    // properties
		    @$reply_message_id = $properties['message_id'];
		    @$content = $properties['content'];
		    @$files = $properties['files'];
		    @$forward_files = $properties['forward_files'];
		    @$worker_id = $properties['agent_id'];
		    @$subject = $properties['subject'];
		    
			$message = DAO_Message::get($reply_message_id);
	        $message_headers = DAO_MessageHeader::getAll($reply_message_id);		
			$ticket_id = $message->ticket_id;
			$ticket = DAO_Ticket::getTicket($ticket_id);
	
			// [TODO] Check that message|ticket isn't NULL
			
			// If this ticket isn't spam trained and our outgoing message isn't an autoreply
			if($ticket->spam_training == CerberusTicketSpamTraining::BLANK
				&& (!isset($properties['is_autoreply']) || !$properties['is_autoreply'])) {
				CerberusBayes::markTicketAsNotSpam($ticket_id);
			} 
			
			// Allow teams to override the default from/personal
			@$group_reply = DAO_GroupSettings::get($ticket->team_id, DAO_GroupSettings::SETTING_REPLY_FROM, '');
			@$group_personal = DAO_GroupSettings::get($ticket->team_id, DAO_GroupSettings::SETTING_REPLY_PERSONAL, '');
			@$group_personal_with_worker = DAO_GroupSettings::get($ticket->team_id, DAO_GroupSettings::SETTING_REPLY_PERSONAL_WITH_WORKER, 0);

			if(!empty($group_reply))
				$from_addy = $group_reply;
				
			if(!empty($group_personal)) 
				$from_personal = $group_personal;
			
			// Prefix the worker name on the personal line?
			if(!empty($group_personal_with_worker)
				&& null != ($reply_worker = DAO_Worker::getAgent($worker_id))) {
					$from_personal = $reply_worker->getName() .
						(!empty($from_personal) ? (', ' . $from_personal) : "");
			}
				
			// Headers
			$mail->setFrom(array($from_addy => $from_personal));
			$mail->generateId();
			
			$headers = $mail->getHeaders();
			
			$headers->addTextHeader('X-Mailer','Cerberus Helpdesk (Build '.APP_BUILD.')');
	
			// Subject
			if(empty($subject)) $subject = $ticket->subject;
			
			if(!empty($properties['to'])) { // forward
				$mail->setSubject($subject);
				
			} else { // reply
				@$group_has_subject = intval(DAO_GroupSettings::get($ticket->team_id, DAO_GroupSettings::SETTING_SUBJECT_HAS_MASK,0));
				@$group_subject_prefix = DAO_GroupSettings::get($ticket->team_id, DAO_GroupSettings::SETTING_SUBJECT_PREFIX,'');
				
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
	
			// Auto-reply handling (RFC-3834 compliant)
			if(isset($properties['is_autoreply']) && $properties['is_autoreply']) {
				$headers->addTextHeader('Auto-Submitted','auto-replied');
				
				if(null == ($first_address = DAO_Address::get($ticket->first_wrote_address_id)))
					return;
	
				// Don't send e-mail to ourselves
				if(isset($helpdesk_senders[$first_address->email]))
					return;
					
				// Make sure we haven't mailed this address an autoreply within 5 minutes
				if($first_address->last_autoreply > 0 && $first_address->last_autoreply > time()-300) {
					return;
				}
					
				$first_email = strtolower($first_address->email);
				$first_split = explode('@', $first_email);
		
				if(!is_array($first_split) || count($first_split) != 2)
					return;
		
				// If return-path is blank
				if(isset($message_headers['return-path']) && $message_headers['return-path'] == '<>')
					return;
					
				// Ignore bounces
				if($first_split[0]=="postmaster" || $first_split[0] == "mailer-daemon")
					return;
				
				// Ignore autoresponses to autoresponses
				if(isset($message_headers['auto-submitted']) && $message_headers['auto-submitted'] != 'no')
					return;
					
				if(isset($message_headers['precedence']) && 
					($message_headers['precedence']=='list' || $message_headers['precedence'] == 'junk' || $message_headers['precedence'] = 'bulk'))
					return;
					
				// Set the auto-reply date for this address to right now
				DAO_Address::update($ticket->first_wrote_address_id, array(
					DAO_Address::LAST_AUTOREPLY => time()
				));
				
				// Auto-reply just to the initial requester
				$mail->addTo($first_address->email);
				
			// Not an auto-reply
			} else {
				// Forwards
				if(!empty($properties['to'])) {
				    $aTo = DevblocksPlatform::parseCsvString(str_replace(';',',',$properties['to']));
					
					if(is_array($aTo))
					foreach($aTo as $to_addy) {
						$mail->addTo($to_addy);
					}
				    
				// Replies
				} else {
				    // Recipients
					$requesters = DAO_Ticket::getRequestersByTicket($ticket_id);
				    if(is_array($requesters))
				    foreach($requesters as $requester) { /* @var $requester Model_Address */
						$mail->addTo($requester->email);
				    }
				}
		
			    // Ccs
			    if(!empty($properties['cc'])) {
				    $aCc = DevblocksPlatform::parseCsvString(str_replace(';',',',$properties['cc']));
					$mail->setCc($aCc);
			    }
			    
			    // Bccs
			    if(!empty($properties['bcc'])) {
				    $aBcc = DevblocksPlatform::parseCsvString(str_replace(';',',',$properties['bcc']));
					$mail->setBcc($aBcc);
			    }
			}
			
			/*
			 * [IMPORTANT -- Yes, this is simply a line in the sand.]
			 * You're welcome to modify the code to meet your needs, but please respect 
			 * our licensing.  Buy a legitimate copy to help support the project!
			 * http://www.cerberusweb.com/
			 */
			$license = CerberusLicense::getInstance();
			if(empty($license) || @empty($license['serial'])) {
				$content .= base64_decode("DQoNCi0tLQ0KQ29tYmF0IHNwYW0gYW5kIGltcHJvdmUgcmVzc".
					"G9uc2UgdGltZXMgd2l0aCBDZXJiZXJ1cyBIZWxwZGVzayA0LjAhDQpodHRwOi8vd3d3LmNlc".
					"mJlcnVzd2ViLmNvbS8NCg"
				);
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
			
			// If we're not supposed to send
			if(isset($properties['dont_send']) && $properties['dont_send']) {
				// ...do nothing
			} else { // otherwise send
				if(!$mailer->send($mail)) {
					$mail_succeeded = false;
					throw new Exception('Mail not sent.');
				}
			}
		} catch (Exception $e) {
			// tag failure, so we can add a note to the message later
			$mail_succeeded = false;
		}

		// Handle post-mail actions
		$change_fields = array();
		
		$fromAddressInst = CerberusApplication::hashLookupAddress($from_addy, true);
		$fromAddressId = $fromAddressInst->id;
		
		if((!isset($properties['dont_keep_copy']) || !$properties['dont_keep_copy'])
			&& (!isset($properties['is_autoreply']) || !$properties['is_autoreply'])) {
			$change_fields[DAO_Ticket::LAST_WROTE_ID] = $fromAddressId;
			$change_fields[DAO_Ticket::UPDATED_DATE] = time();
			
		    if(!empty($worker_id)) {
		        $change_fields[DAO_Ticket::LAST_WORKER_ID] = $worker_id;
		        $change_fields[DAO_Ticket::LAST_ACTION_CODE] = CerberusTicketActionCode::TICKET_WORKER_REPLY;
		    }
		    
		    // Only change the subject if not forwarding
		    if(!empty($subject) && empty($properties['to'])) {
		    	$change_fields[DAO_Ticket::SUBJECT] = $subject;
		    }
			
		    $fields = array(
		        DAO_Message::TICKET_ID => $ticket_id,
		        DAO_Message::CREATED_DATE => time(),
		        DAO_Message::ADDRESS_ID => $fromAddressId,
		        DAO_Message::IS_OUTGOING => 1,
		        DAO_Message::WORKER_ID => (!empty($worker_id) ? $worker_id : 0),
		    );
			$message_id = DAO_Message::create($fields);
		    
			// Content
			Storage_MessageContent::put($message_id, $content);
		    
			$headers = $mail->getHeaders();
			
		    // Headers
			foreach($headers->getAll() as $hdr) {
				if(null != ($hdr_val = $hdr->getFieldBody())) {
					if(!empty($hdr_val))
		    			DAO_MessageHeader::create($message_id, $hdr->getFieldName(), CerberusParser::fixQuotePrintableString($hdr_val));
				}
			}
		    
			// Attachments
			if (is_array($files) && !empty($files)) {
				reset($files);
				foreach ($files['tmp_name'] as $idx => $file) {
					if(empty($file) || empty($files['name'][$idx]) || !file_exists($file))
						continue;

					// Create record
					$fields = array(
						DAO_Attachment::MESSAGE_ID => $message_id,
						DAO_Attachment::DISPLAY_NAME => $files['name'][$idx],
						DAO_Attachment::MIME_TYPE => $files['type'][$idx],
					);
					$file_id = DAO_Attachment::create($fields);

					if(null !== ($fp = fopen($file, 'rb'))) {
		            	Storage_Attachments::put($file_id, $fp);
						fclose($fp);
			            unlink($file);
					}
				}
			}
			
			// add note to message if email failed
			if ($mail_succeeded === false) {
				$fields = array(
					DAO_MessageNote::MESSAGE_ID => $message_id,
					DAO_MessageNote::CREATED => time(),
					DAO_MessageNote::WORKER_ID => 0,
					DAO_MessageNote::CONTENT => 'Exception thrown while sending email: ' . $e->getMessage(),
					DAO_MessageNote::TYPE => Model_MessageNote::TYPE_ERROR,
				);
				DAO_MessageNote::create($fields);
			}
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

        // Who should handle the followup?
		if(isset($properties['next_worker_id']))
        	$change_fields[DAO_Ticket::NEXT_WORKER_ID] = $properties['next_worker_id'];

        // Allow anybody to reply after 
		if(isset($properties['unlock_date']) && !empty($properties['unlock_date'])) {
		    $unlock = strtotime($properties['unlock_date']);
		    if(intval($unlock) > 0)
	            $change_fields[DAO_Ticket::UNLOCK_DATE] = $unlock;
		}

		// Move
		if(!empty($properties['bucket_id'])) {
		    // [TODO] Use API to move, or fire event
	        // [TODO] Ensure team/bucket exist
	        list($team_id, $bucket_id) = CerberusApplication::translateTeamCategoryCode($properties['bucket_id']);
		    $change_fields[DAO_Ticket::TEAM_ID] = $team_id;
		    $change_fields[DAO_Ticket::CATEGORY_ID] = $bucket_id;
		}
			
		if(!empty($ticket_id) && !empty($change_fields)) {
		    DAO_Ticket::updateTicket($ticket_id, $change_fields);
		}
		
		// Outbound Reply Event (not automated reply, etc.)
		if(!empty($worker_id)) {
		    $eventMgr = DevblocksPlatform::getEventService();
		    $eventMgr->trigger(
		        new Model_DevblocksEvent(
		            'ticket.reply.outbound',
	                array(
	                    'ticket_id' => $ticket_id,
	                    'worker_id' => $worker_id
	                )
	            )
		    );
		}
		
		return $mail_succeeded;
	}
	
	static function reflect(CerberusParserMessage $message, $to) {
		try {
			$mail_service = DevblocksPlatform::getMailService();
			$mailer = $mail_service->getMailer(CerberusMail::getMailerDefaults());
			$mail = $mail_service->createMessage();
	
		    $settings = DevblocksPlatform::getPluginSettingsService();
			@$from_addy = $settings->get('cerberusweb.core',CerberusSettings::DEFAULT_REPLY_FROM, CerberusSettingsDefaults::DEFAULT_REPLY_FROM);
			@$from_personal = $settings->get('cerberusweb.core',CerberusSettings::DEFAULT_REPLY_PERSONAL,CerberusSettingsDefaults::DEFAULT_REPLY_PERSONAL);
			
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
			if(isset($message->headers['from']))
				$mail->setFrom($message->headers['from']);
			if(isset($message->headers['return-path']))
				$mail->setReturnPath($message->headers['return-path']);
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
