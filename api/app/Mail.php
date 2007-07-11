<?php
class CerberusMail {
	private function __construct() {}
	
	static function generateMessageFilename() {
	    return sprintf("%s.%s.msg",
	        time(),
	        rand(0,9999)
	    );
	}
	
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

		// [TODO] Consider ticket's team for reply from/personal
		
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

//		// if this message was submitted with attachments, store them in the filestore and link them in the db.
		if (is_array($files) && !empty($files)) {
			$attachment_path = APP_PATH . '/storage/attachments/';
		
			foreach ($files['tmp_name'] as $idx => $file) {
				// [TODO] Use API to abstract writing these to disk
//				DAO_Attachment::create($fields); // $message_id, $files['name'][$idx], $message_id.$idx);
//				copy($files['tmp_name'][$idx],$attachment_path.$message_id.$idx);
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
