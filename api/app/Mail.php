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
	
	static function quickSend($to, $subject, $body) {
		try {
			$mail_service = DevblocksPlatform::getMailService();
			$mailer = $mail_service->getMailer();
			$mail = $mail_service->createMessage();
	
		    $settings = CerberusSettings::getInstance();
			@$from_addy = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM, $_SERVER['SERVER_ADMIN']);
			@$from_personal = $settings->get(CerberusSettings::DEFAULT_REPLY_PERSONAL,'');
			
			$sendTo = new Swift_Address($to);
			$sendFrom = new Swift_Address($from_addy, $from_personal);
			
			$mail->setSubject($subject);
			$mail->generateId();
			$mail->headers->set('X-Mailer','Cerberus Helpdesk (Build '.APP_BUILD.')');
			$mail->attach(new Swift_Message_Part($body, 'text/plain', 'base64', 'ISO-8859-1'));
		
			if(!$mailer->send($mail, $sendTo, $sendFrom)) {
				// [TODO] Report when the message wasn't sent.
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
		@$bucket_id = $properties['bucket_id'];
		@$next_worker_id = $properties['next_worker_id'];
		@$next_action = $properties['next_action'];
		@$ticket_reopen = $properties['ticket_reopen'];
		
		$settings = CerberusSettings::getInstance();
		$default_from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM);
		$default_personal = $settings->get(CerberusSettings::DEFAULT_REPLY_PERSONAL);
		$group_settings = DAO_GroupSettings::getSettings($team_id);
		@$team_from = $group_settings[DAO_GroupSettings::SETTING_REPLY_FROM];
		@$team_personal = $group_settings[DAO_GroupSettings::SETTING_REPLY_PERSONAL];
		
		$from = !empty($team_from) ? $team_from : $default_from;
		$personal = !empty($team_personal) ? $team_personal : $default_personal;
		$mask = CerberusApplication::generateTicketMask();

		if(empty($subject)) $subject = '(no subject)';
		
		// add mask to subject if group setting calls for it
		@$group_has_subject = intval($group_settings[DAO_GroupSettings::SETTING_SUBJECT_HAS_MASK]);
		@$group_subject_prefix = $group_settings[DAO_GroupSettings::SETTING_SUBJECT_PREFIX];
		$prefix = sprintf("[%s#%s] ",
			!empty($group_subject_prefix) ? ($group_subject_prefix.' ') : '',
			$mask
		);
		$subject = (sprintf('%s%s',
			$group_has_subject ? $prefix : '',
			$subject
		));
		
		$toList = DevblocksPlatform::parseCsvString($toStr);
		
		$mail_headers = array();
		$mail_headers['X-CerberusCompose'] = '1';
		
		$mail_succeeded = true;
		if(!empty($no_mail)) { // allow compose without sending mail
			// Headers needed for the ticket message
			$mail_headers['To'] = $toStr; 
			$mail_headers['From'] = !empty($personal) ? (sprintf("%s <%s>",$personal,$from)) : (sprintf('%s',$from)); 
			$mail_headers['Subject'] = $subject; 
			$mail_headers['Date'] = gmdate('r'); 
			
		} else { // regular mail sending
			try {
				$sendTo = new Swift_RecipientList();
				foreach($toList as $to)
					$sendTo->addTo($to);
				
				$sendFrom = new Swift_Address($from, $personal);
				
				$mail_service = DevblocksPlatform::getMailService();
				$mailer = $mail_service->getMailer();
				$email = $mail_service->createMessage();
		
				$email->setTo($toList);
				
				// cc
				$ccs = array();
				if(!empty($cc) && null != ($ccList = DevblocksPlatform::parseCsvString($cc))) {
					foreach($ccList as $ccAddy) {
						$sendTo->addCc($ccAddy);
						$ccs[] = new Swift_Address($ccAddy);
					}
					if(!empty($ccs))
						$email->setCc($ccs);
				}
				
				// bcc
				if(!empty($bcc) && null != ($bccList = DevblocksPlatform::parseCsvString($bcc))) {
					foreach($bccList as $bccAddy) {
						$sendTo->addBcc($bccAddy);
					}
				}
				
				$email->setFrom($sendFrom);
				$email->setSubject($subject);
				$email->generateId();
				$email->headers->set('X-Mailer','Cerberus Helpdesk (Build '.APP_BUILD.')');
				
				$email->attach(new Swift_Message_Part($content, 'text/plain', 'base64', 'ISO-8859-1'));
				
				// [TODO] These attachments should probably save to the DB
				
				// Mime Attachments
				if (is_array($files) && !empty($files)) {
					foreach ($files['tmp_name'] as $idx => $file) {
						if(empty($file) || empty($files['name'][$idx]))
							continue;
		
						$email->attach(new Swift_Message_Attachment(
							new Swift_File($file), $files['name'][$idx], $files['type'][$idx]));
					}
				}
				
				// [TODO] Allow separated addresses (parseRfcAddress)
		//		$mailer->log->enable();
				if(!$mailer->send($email, $sendTo, $sendFrom)) {
					$mail_succeeded = false;
					throw new Exception('Mail failed to send: unknown reason');
				}
		//		$mailer->log->dump();
			} catch (Exception $e) {
				// tag mail as failed, add note to message after message gets created			
				$mail_succeeded = false;
			}

		    // Headers
		    if (!empty($email) && !empty($email->headers)) {
				foreach($email->headers->getList() as $hdr => $v) {
					if(null != ($hdr_val = $email->headers->getEncoded($hdr))) {
						if(!empty($hdr_val))
							$mail_headers[$hdr] = $hdr_val;
					}
				}
		    }
		}
		
		$worker = CerberusApplication::getActiveWorker();
		$fromAddressInst = CerberusApplication::hashLookupAddress($from, true);
		$fromAddressId = $fromAddressInst->id;
		
		// [TODO] this is redundant with the Parser code.  Should be refactored later
		
		// Is this address covered by an SLA?
		$sla_id = 0;
		$sla_priority = 0;
		
		// [TODO] This has to handle multiple TO
//		$toAddressInst = CerberusApplication::hashLookupAddress($to, true);
//		if(!empty($toAddressInst->sla_id)) {
//			if(null != ($toAddressSla = DAO_Sla::get($toAddressInst->sla_id))) {
//				@$sla_id = $toAddressSla->id;
//				@$sla_priority = $toAddressSla->priority;
//			}
//		}
		
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
			DAO_Ticket::SLA_ID => $sla_id,
			DAO_Ticket::SLA_PRIORITY => $sla_priority,
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
		if(!empty($next_action))
			$fields[DAO_Ticket::NEXT_ACTION] = $next_action;
		if(!empty($ticket_reopen)) {
			$due = strtotime($ticket_reopen);
			if($due) $fields[DAO_Ticket::DUE_DATE] = $due;
		}
		// End "Next:"
		
		$ticket_id = DAO_Ticket::createTicket($fields);
		
	    $fields = array(
	        DAO_Message::TICKET_ID => $ticket_id,
	        DAO_Message::CREATED_DATE => time(),
	        DAO_Message::ADDRESS_ID => $fromAddressId,
	        DAO_Message::IS_OUTGOING => 1,
	        DAO_Message::WORKER_ID => (!empty($worker->id) ? $worker->id : 0)
	    );
		$message_id = DAO_Message::create($fields);
	    
		// Link Message to Ticket
		DAO_Ticket::updateTicket($ticket_id, array(
			DAO_Ticket::FIRST_MESSAGE_ID => $message_id,
		));
		
		// Content
	    DAO_MessageContent::update($message_id, $content);

		// Set recipients to requesters
		foreach($toList as $to) {
			if(null != ($reqAddressInst = CerberusApplication::hashLookupAddress($to, true))) {
				$reqAddressId = $reqAddressInst->id;
				DAO_Ticket::createRequester($reqAddressId, $ticket_id);
			}
		}
		
		// Headers
		foreach($mail_headers as $hdr => $hdr_val) {
			DAO_MessageHeader::update($message_id, $ticket_id, $hdr, $hdr_val);			
		}
		
		// add files to ticket
		// [TODO] redundant with parser (like most of the rest of this function)
		if (is_array($files) && !empty($files)) {
			$attachment_path = APP_PATH . '/storage/attachments/';
		
			reset($files);
			foreach ($files['tmp_name'] as $idx => $file) {
				if(empty($file) || empty($files['name'][$idx]) || !file_exists($file))
					continue;
					
				$fields = array(
					DAO_Attachment::MESSAGE_ID => $message_id,
					DAO_Attachment::DISPLAY_NAME => $files['name'][$idx],
					DAO_Attachment::MIME_TYPE => $files['type'][$idx],
					DAO_Attachment::FILE_SIZE => filesize($file)
				);
				$file_id = DAO_Attachment::create($fields);
				
	            $attachment_bucket = sprintf("%03d/",
	                rand(1,100)
	            );
	            $attachment_file = $file_id;
	            
	            if(!file_exists($attachment_path.$attachment_bucket)) {
	                mkdir($attachment_path.$attachment_bucket, 0775, true);
	            }

	            if(!is_writeable($attachment_path.$attachment_bucket)) {
	            	echo "Can't write to " . $attachment_path.$attachment_bucket . "<BR>";
	            }
	            
	            copy($file, $attachment_path.$attachment_bucket.$attachment_file);
	            @unlink($file);
			    
			    DAO_Attachment::update($file_id, array(
			        DAO_Attachment::FILEPATH => $attachment_bucket.$attachment_file
			    ));
			}
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
	    $settings = CerberusSettings::getInstance();
		@$from_addy = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM, $_SERVER['SERVER_ADMIN']);
		@$from_personal = $settings->get(CerberusSettings::DEFAULT_REPLY_PERSONAL,'');
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
	    'next_action'
	    'bucket_id'
	    'agent_id',
		'is_autoreply',
		'dont_save_copy'
		*/

		$mail_succeeded = true;
		try {
			// objects
		    $mail_service = DevblocksPlatform::getMailService();
		    $mailer = $mail_service->getMailer();
			$mail = $mail_service->createMessage();
	        
		    // properties
		    @$reply_message_id = $properties['message_id'];
		    @$content =& $properties['content'];
		    @$files = $properties['files'];
		    @$forward_files = $properties['forward_files'];
		    @$worker_id = $properties['agent_id'];
		    @$subject = $properties['subject'];
		    
			$message = DAO_Ticket::getMessage($reply_message_id);
	        $message_headers = DAO_MessageHeader::getAll($reply_message_id);		
			$ticket_id = $message->ticket_id;
			$ticket = DAO_Ticket::getTicket($ticket_id);
	
			if($ticket->spam_training == CerberusTicketSpamTraining::BLANK) {
				CerberusBayes::markTicketAsNotSpam($ticket_id);
			} 
			
			// Allow teams to override the default from/personal
			$group_settings = DAO_GroupSettings::getSettings($ticket->team_id);
			if(!empty($group_settings[DAO_GroupSettings::SETTING_REPLY_FROM])) 
				$from_addy = $group_settings[DAO_GroupSettings::SETTING_REPLY_FROM];
			if(!empty($group_settings[DAO_GroupSettings::SETTING_REPLY_PERSONAL])) 
				$from_personal = $group_settings[DAO_GroupSettings::SETTING_REPLY_PERSONAL];
			
			$sendFrom = new Swift_Address($from_addy, $from_personal);
				
			// Headers
			$mail->setFrom($sendFrom);
			$mail->generateId();
			$mail->headers->set('X-Mailer','Cerberus Helpdesk (Build '.APP_BUILD.')');
	
			// Subject
			if(empty($subject)) $subject = $ticket->subject;
			
			if(!empty($properties['to'])) { // forward
				$mail->setSubject($subject);
				
			} else { // reply
				@$group_has_subject = intval($group_settings[DAO_GroupSettings::SETTING_SUBJECT_HAS_MASK]);
				@$group_subject_prefix = $group_settings[DAO_GroupSettings::SETTING_SUBJECT_PREFIX];
				
				$prefix = sprintf("[%s#%s] ",
					!empty($group_subject_prefix) ? ($group_subject_prefix.' ') : '',
					$ticket->mask
				);
				
				$mail->setSubject(sprintf('Re: %s%s',
					$group_has_subject ? $prefix : '',
					$subject
				));
			}
			
			$sendTo = new Swift_RecipientList();
			
			// References
			if(!empty($message) && false !== (@$in_reply_to = $message_headers['message-id'])) {
			    $mail->headers->set('References', $in_reply_to);
			    $mail->headers->set('In-Reply-To', $in_reply_to);
			}
	
			// Auto-reply handling (RFC-3834 compliant)
			if(isset($properties['is_autoreply']) && $properties['is_autoreply']) {
				$mail->headers->set('Auto-Submitted','auto-replied');
				
				if(null == ($first_address = DAO_Address::get($ticket->first_wrote_address_id)))
					return;
	
				// Make sure we haven't mailed this address an autoreply within 10 minutes
				if($first_address->last_autoreply > 0 && $first_address->last_autoreply > time()-600) {
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
				if($first_split[1]=="postmaster" || $first_split[1] == "mailer-daemon")
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
				$sendTo->addTo($first_address->email);
				$mail->setTo($first_address->email);
				
			// Not an auto-reply
			} else {
				// Forwards
				if(!empty($properties['to'])) {
				    $to = array();
				    $aTo = DevblocksPlatform::parseCsvString($properties['to']);
				    foreach($aTo as $addy) {
				    	$to[] = new Swift_Address($addy);
				    	$sendTo->addTo($addy);
				    }
				    if(!empty($to))
				    	$mail->setTo($to);
				    
				// Replies
				} else {
				    // Recipients
					$to = array();
					$requesters = DAO_Ticket::getRequestersByTicket($ticket_id);
				    if(is_array($requesters)) {
					    foreach($requesters as $requester) { /* @var $requester Model_Address */
							$to[] = new Swift_Address($requester->email);
							$sendTo->addTo($requester->email);
					    }
				    }
				    $mail->setTo($to);
				}
		
			    // Ccs
			    if(!empty($properties['cc'])) {
				    $ccs = array();
				    $aCc = DevblocksPlatform::parseCsvString($properties['cc']);
				    foreach($aCc as $addy) {
				    	$sendTo->addCc($addy);
				    	$ccs[] = new Swift_Address($addy);
				    }
				    if(!empty($ccs))
				    	$mail->setCc($ccs);
			    }
			    
			    // Bccs
			    if(!empty($properties['bcc'])) {
				    $aBcc = DevblocksPlatform::parseCsvString($properties['bcc']);
				    foreach($aBcc as $addy) {
				    	$sendTo->addBcc($addy);
				    }
			    }
			}
			
			/*
			 * [IMPORTANT -- Yes, this is simply a line in the sand.]
			 * You're welcome to modify the code to meet your needs, but please respect 
			 * our licensing.  Buy a legitimate copy to help support the project!
			 * http://www.cerberusweb.com/
			 */
			$license = CerberusLicense::getInstance();
			if(empty($license) || @empty($license['key'])) {
				$content .= base64_decode("DQoNCi0tLQ0KQ29tYmF0IHNwYW0gYW5kIGltcHJvdmUgcmVzc".
					"G9uc2UgdGltZXMgd2l0aCBDZXJiZXJ1cyBIZWxwZGVzayA0LjAhDQpodHRwOi8vd3d3LmNlc".
					"mJlcnVzd2ViLmNvbS8NCg"
				);
			}
			
			// Body
			$mail->attach(new Swift_Message_Part($content, 'text/plain', 'base64', 'ISO-8859-1'));
	
			// Mime Attachments
			if (is_array($files) && !empty($files)) {
				foreach ($files['tmp_name'] as $idx => $file) {
					if(empty($file) || empty($files['name'][$idx]))
						continue;
	
					$mail->attach(new Swift_Message_Attachment(
						new Swift_File($file), $files['name'][$idx], $files['type'][$idx]));
				}
			}
	
			// Forward Attachments
			if(!empty($forward_files) && is_array($forward_files)) {
				$attachments_path = APP_PATH . '/storage/attachments/';
				
				foreach($forward_files as $file_id) {
					$attachment = DAO_Attachment::get($file_id);
					$attachment_path = $attachments_path . $attachment->filepath;
					
					$mail->attach(new Swift_Message_Attachment(
						new Swift_File($attachment_path), $attachment->display_name, $attachment->mime_type));
				}
			}
			
			if(!DEMO_MODE) {
				if(!$mailer->send($mail, $sendTo, $sendFrom)) {
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
		    DAO_MessageContent::update($message_id, $content);
		    
		    // Headers
			foreach($mail->headers->getList() as $hdr => $v) {
				if(null != ($hdr_val = $mail->headers->getEncoded($hdr))) {
					if(!empty($hdr_val))
		    			DAO_MessageHeader::update($message_id, $ticket_id, $hdr, $hdr_val);
				}
			}
		    
			if (is_array($files) && !empty($files)) {
				$attachment_path = APP_PATH . '/storage/attachments/';
			
				reset($files);
				foreach ($files['tmp_name'] as $idx => $file) {
					if(empty($file) || empty($files['name'][$idx]) || !file_exists($file))
						continue;
						
					$fields = array(
						DAO_Attachment::MESSAGE_ID => $message_id,
						DAO_Attachment::DISPLAY_NAME => $files['name'][$idx],
						DAO_Attachment::MIME_TYPE => $files['type'][$idx],
						DAO_Attachment::FILE_SIZE => filesize($file)
					);
					$file_id = DAO_Attachment::create($fields);
					
		            $attachment_bucket = sprintf("%03d/",
		                rand(1,100)
		            );
		            $attachment_file = $file_id;
		            
		            if(!file_exists($attachment_path.$attachment_bucket)) {
		                mkdir($attachment_path.$attachment_bucket, 0775, true);
		            }
	
		            if(!is_writeable($attachment_path.$attachment_bucket)) {
		            	echo "Can't write to " . $attachment_path.$attachment_bucket . "<BR>";
		            }
		            
		            copy($file, $attachment_path.$attachment_bucket.$attachment_file);
		            @unlink($file);
				    
				    DAO_Attachment::update($file_id, array(
				        DAO_Attachment::FILEPATH => $attachment_bucket.$attachment_file
				    ));
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
			if(1==intval($properties['closed'])) { // Closing
				$change_fields[DAO_Ticket::IS_CLOSED] = 1;
				$change_fields[DAO_Ticket::IS_WAITING] = 0;

				if(isset($properties['ticket_reopen'])) {
					if(!empty($properties['ticket_reopen'])) {
					    $due = strtotime($properties['ticket_reopen']);
					    if(intval($due) > 0)
				            $change_fields[DAO_Ticket::DUE_DATE] = $due;
					}
				}
	        } else { // Open or Waiting
				$change_fields[DAO_Ticket::IS_CLOSED] = 0;
				$change_fields[DAO_Ticket::IS_WAITING] = 0;

				// Waiting for Reply
				if(2==intval($properties['closed'])) {
					$change_fields[DAO_Ticket::IS_WAITING] = 1;
				}

				if(isset($properties['next_action']))
	    	    	$change_fields[DAO_Ticket::NEXT_ACTION] = $properties['next_action'];
	    	    
				if(!empty($properties['bucket_id'])) {
				    // [TODO] Use API to move, or fire event
			        // [TODO] Ensure team/bucket exist
			        list($team_id, $bucket_id) = CerberusApplication::translateTeamCategoryCode($properties['bucket_id']);
				    $change_fields[DAO_Ticket::TEAM_ID] = $team_id;
				    $change_fields[DAO_Ticket::CATEGORY_ID] = $bucket_id;
				}
	        }
		}
        
        // Who should handle the followup?
		if(isset($properties['next_worker_id']))
        	$change_fields[DAO_Ticket::NEXT_WORKER_ID] = $properties['next_worker_id'];

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
	                    'message_id' => $message_id,
	                    'worker_id' => $worker_id
	                )
	            )
		    );
		}
	}
	
	static function reflect(CerberusParserMessage $message, $to) {
		try {
			$mail_service = DevblocksPlatform::getMailService();
			$mailer = $mail_service->getMailer();
			$mail = $mail_service->createMessage();
	
		    $settings = CerberusSettings::getInstance();
			@$from_addy = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM, $_SERVER['SERVER_ADMIN']);
			@$from_personal = $settings->get(CerberusSettings::DEFAULT_REPLY_PERSONAL,'');
			
			$sendTo = new Swift_Address($to);
			$sendFrom = new Swift_Address($from_addy, $from_personal);
			
//			if(is_array($message->headers))
//			foreach($message->headers as $header => $val) {
//				if(0==strcasecmp($header,'to'))
//					continue;
//				$mail->headers->set($header, $val);
//			}

			if(isset($message->headers['subject']))
				$mail->headers->set('Subject', $message->headers['subject']);
			if(isset($message->headers['message-id']))
				$mail->headers->set('Message-Id', $message->headers['message-id']);
			if(isset($message->headers['in-reply-to']))
				$mail->headers->set('In-Reply-To', $message->headers['in-reply-to']);
			if(isset($message->headers['references']))
				$mail->headers->set('References', $message->headers['references']);
			if(isset($message->headers['from']))
				$mail->headers->set('From', $message->headers['from']);
			if(isset($message->headers['return-path']))
				$mail->headers->set('Return-Path', $message->headers['return-path']);
			if(isset($message->headers['reply-to']))
				$mail->headers->set('Reply-To', $message->headers['reply-to']);
				
			$mail->headers->set('X-CerberusRedirect','1');
			
			$mail->attach(new Swift_Message_Part($message->body, 'text/plain', 'base64', $message->encoding));
			
			// Files
			if(is_array($message->files))
			foreach($message->files as $file_name => $file) { /* @var $file ParserFile */
				$mail->attach(new Swift_Message_Attachment(
					new Swift_File($file->tmpname), $file_name, $file->mime_type));
			}
		
			if(!$mailer->send($mail, $sendTo, $sendFrom)) {
				return false;
			}
		} catch (Exception $e) {
			return false;
		}
	}
	
};

?>
