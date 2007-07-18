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
	
	static function sendTicketMessage($properties=array()) {
	    $settings = CerberusSettings::getInstance();
		$from_addy = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM, $_SERVER['SERVER_ADMIN']);
		$from_personal = $settings->get(CerberusSettings::DEFAULT_REPLY_PERSONAL,'');
		
		/*
	     * [TODO] Move these into constants?
	    'message_id'
	    'ticket_id'
		'to' // if type==FORWARD
	    'cc'
	    'bcc'
	    'content'
	    'files'
	    'closed'
	    'ticket_reopen'
	    'next_action'
	    'bucket_id'
	    'agent_id'
		*/

		// objects
	    $mail_service = DevblocksPlatform::getMailService();
	    $mail = $mail_service->createEmail();
        
	    // properties
	    @$type = $properties['type']; // [TODO] Phase out
	    @$message_id = $properties['message_id'];
	    @$content =& $properties['content'];
	    @$files = $properties['files'];
	    @$worker_id = $properties['agent_id'];
	    
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
		
		// Headers
		$mail->setFrom($from_addy, $from_personal);
		$mail->setSubject('Re: ' . $ticket->subject);
		$mail->headers->set('Date', gmdate('r'));
		$mail->headers->set('Message-Id',CerberusApplication::generateMessageId());
		$mail->headers->set('X-Mailer','Cerberus Helpdesk (Build '.APP_BUILD.')');
		$mail->headers->set('X-MailGenerator','Cerberus Helpdesk (Build '.APP_BUILD.')');
		
		// References
		if(!empty($message) && false !== (@$in_reply_to = $message_headers['message-id'])) {
		    $mail->headers->set('References', $in_reply_to);
		    $mail->headers->set('In-Reply-To', $in_reply_to);
		}
		
		// Body
		$mail->setTextBody($content); // , 'iso-8859-1' 

	    // Recepients
		$to = array();
		$requesters = DAO_Ticket::getRequestersByTicket($ticket_id);
	    if(is_array($requesters)) {
		    foreach($requesters as $requester) { /* @var $requester CerberusAddress */
				$mail->addRecipient($requester->email);
				$to[] = $requester->email;
		    }
	    }
	    
		// Mime Attachments
		if (is_array($files) && !empty($files)) {
			foreach ($files['tmp_name'] as $idx => $file) {
				if(empty($file) || empty($files['name'][$idx]))
					continue;

				// $files['type'][$idx]
				$mail->attachFromString(file_get_contents($file), $files['name'][$idx]);
				
//				$tmp = tempnam(DEVBLOCKS_PATH . 'tmp/','mime');
//				if($mail_service->streamedBase64Encode($file, $tmp)) {
//					$mail->attachFromString(file_get_contents($tmp), $files['name'][$idx]);
//				}
//				@unlink($tmp);
			}
		}

		if(!$mail_service->send($from_addy, $to, $mail)) {
			// [TODO] Do error reporting
		}
		
		// [TODO] Make this properly use team replies 
	    // (or reflect what the customer sent to), etc.
		$fromAddressId = CerberusApplication::hashLookupAddressId($from_addy);
		
	    $fields = array(
	        DAO_Message::TICKET_ID => $ticket_id,
	        DAO_Message::MESSAGE_TYPE => $type, // [TODO] Phase out
	        DAO_Message::CREATED_DATE => time(),
	        DAO_Message::ADDRESS_ID => $fromAddressId // [TODO] Real sender id
	    );
		$message_id = DAO_Message::create($fields);
	    
		// Content
	    DAO_MessageContent::update($message_id, $content);
	    
		foreach(array('Date', 'From', 'To', 'Subject', 'Message-Id', 'X-Mailer') as $hdr) {
			if(null != ($hdr_val = $mail->headers->get($hdr)))
				if(false !== ($pos = strpos($hdr_val,':'))) {
					$hdr_val = trim(substr($hdr_val, $pos+1));
		    		DAO_MessageHeader::update($message_id, $ticket_id, $hdr, $hdr_val);
				}
		}

		if (is_array($files) && !empty($files)) {
			$attachment_path = APP_PATH . '/storage/attachments/';
		
			// [TODO] Use API to abstract writing these to disk (share w/ Parser)
			foreach ($files['tmp_name'] as $idx => $file) {
				if(empty($file) || empty($files['name'][$idx]))
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
	                @mkdir($attachment_path.$attachment_bucket, 0770, true);
	            }

	            rename($file, $attachment_path.$attachment_bucket.$attachment_file);
			    
			    DAO_Attachment::update($file_id, array(
			        DAO_Attachment::FILEPATH => $attachment_bucket.$attachment_file
			    ));
			}
		}
		
		// Handle post-mail actions
		$change_fields = array();
		
	    if(!empty($worker_id)) {
	        $change_fields[DAO_Ticket::LAST_WORKER_ID] = $worker_id;
	        $change_fields[DAO_Ticket::LAST_ACTION_CODE] = CerberusTicketActionCode::TICKET_WORKER_REPLY;
	    }
		
        $change_fields[DAO_Ticket::IS_CLOSED] = intval($properties['closed']);
		
        if(intval($properties['closed'])) { // Closing
			if(!empty($properties['ticket_reopen'])) {
			    $due = strtotime($properties['ticket_reopen']);
			    if(intval($due) > 0)
		            $change_fields[DAO_Ticket::DUE_DATE] = $due;
			}
			
        } else { // Open
    	    $change_fields[DAO_Ticket::NEXT_ACTION] = $properties['next_action'];
    	    
			if(!empty($properties['bucket_id'])) {
			    // [TODO] Use API to move, or fire event
		        // [TODO] Ensure team/bucket exist
		        list($team_id, $bucket_id) = CerberusApplication::translateTeamCategoryCode($properties['bucket_id']);
			    $change_fields[DAO_Ticket::TEAM_ID] = $team_id;
			    $change_fields[DAO_Ticket::CATEGORY_ID] = $bucket_id;
			}
        }

		if(!empty($ticket_id) && !empty($change_fields)) {
		    DAO_Ticket::updateTicket($ticket_id, $change_fields);
		}
	}
	
};

?>
