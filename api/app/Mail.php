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
	    $mail->attach(new Swift_Message_Part($body));
	
		if(!$mailer->send($mail, $sendTo, $sendFrom)) {
			// [TODO] Report when the message wasn't sent.
			return false;
		}
		
		return true;
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
		-----'to' // if type==FORWARD
	    'cc'
	    'bcc'
	    'content'
	    'files'
	    'closed'
	    'ticket_reopen'
	    'next_action'
	    'bucket_id'
	    'agent_id',
		'dont_save_copy'
		*/

		// objects
	    $mail_service = DevblocksPlatform::getMailService();
	    $mailer = $mail_service->getMailer();
		$mail = $mail_service->createMessage();
        
	    // properties
	    @$type = $properties['type']; // [TODO] Phase out
	    @$message_id = $properties['message_id'];
	    @$content =& $properties['content'];
	    @$files = $properties['files'];
	    @$worker_id = $properties['agent_id'];
	    
//	    $files = $mail_service->persistTempFiles($files);
	    
		$message = DAO_Ticket::getMessage($message_id);
        $message_headers = DAO_MessageHeader::getAll($message_id);		
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
		$mail->setSubject('Re: ' . $ticket->subject);
		$mail->generateId();
		$mail->headers->set('X-Mailer','Cerberus Helpdesk (Build '.APP_BUILD.')');
		
		// References
		if(!empty($message) && false !== (@$in_reply_to = $message_headers['message-id'])) {
		    $mail->headers->set('References', $in_reply_to);
		    $mail->headers->set('In-Reply-To', $in_reply_to);
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
		$mail->attach(new Swift_Message_Part($content));

		$sendTo = new Swift_RecipientList();
		
	    // Recepients
		$to = array();
		$requesters = DAO_Ticket::getRequestersByTicket($ticket_id);
	    if(is_array($requesters)) {
		    foreach($requesters as $requester) { /* @var $requester Model_Address */
				$to[] = new Swift_Address($requester->email);
				$sendTo->addTo($requester->email);
		    }
	    }
	    $mail->setTo($to);

	    // Ccs
	    if(!empty($properties['cc'])) {
		    $ccs = array();
		    $aCc = CerberusApplication::parseCsvString($properties['cc']);
		    foreach($aCc as $addy) {
		    	$sendTo->addCc($addy);
		    	$ccs[] = new Swift_Address($addy);
		    }
		    if(!empty($ccs))
		    	$mail->setCc($ccs);
	    }
	    
		// Mime Attachments
		if (is_array($files) && !empty($files)) {
			foreach ($files['tmp_name'] as $idx => $file) {
				if(empty($file) || empty($files['name'][$idx]))
					continue;

				$mail->attach(new Swift_Message_Attachment(
					new Swift_File($file), $files['name'][$idx], $files['type'][$idx]));
			}
		}

		if(!DEMO_MODE) {
			if(!$mailer->send($mail, $sendTo, $sendFrom)) {
				// [TODO] Report when the message wasn't sent.
			}
		}

		// Handle post-mail actions
		$change_fields = array();
		
		// [TODO] Make this properly use team replies 
	    // (or reflect what the customer sent to), etc.
		$fromAddressId = CerberusApplication::hashLookupAddressId($from_addy, true);
		
		if(empty($properties['dont_keep_copy']) || !$properties['dont_keep_copy']) {
			$change_fields[DAO_Ticket::LAST_WROTE_ID] = $fromAddressId;
			$change_fields[DAO_Ticket::UPDATED_DATE] = time();
			
		    if(!empty($worker_id)) {
		        $change_fields[DAO_Ticket::LAST_WORKER_ID] = $worker_id;
		        $change_fields[DAO_Ticket::LAST_ACTION_CODE] = CerberusTicketActionCode::TICKET_WORKER_REPLY;
		    }
			
		    $fields = array(
		        DAO_Message::TICKET_ID => $ticket_id,
		        DAO_Message::MESSAGE_TYPE => $type, // [TODO] Phase out
		        DAO_Message::CREATED_DATE => time(),
		        DAO_Message::ADDRESS_ID => $fromAddressId // [TODO] Real sender id
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
		}

		// Post-Reply Change Properties

		if(isset($properties['closed'])) {
        	$change_fields[DAO_Ticket::IS_CLOSED] = intval($properties['closed']);
        	
			if(intval($properties['closed'])) { // Closing
				if(isset($properties['ticket_reopen'])) {
					if(!empty($properties['ticket_reopen'])) {
					    $due = strtotime($properties['ticket_reopen']);
					    if(intval($due) > 0)
				            $change_fields[DAO_Ticket::DUE_DATE] = $due;
					}
				}
				
	        } else { // Open
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
	
};

?>
