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
	    /*
	     * [TODO] Move these into constants?
	    'message_id'
	    'ticket_id'
		'type' // CerberusMessageType
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
	    $mailer = DevblocksPlatform::getMailService();
        $mail = $mailer->createInstance();
        $headers = $mailer->getDefaultHeaders();
        $to = array();
	    
	    // properties
	    @$type = $properties['type'];
	    @$message_id = $properties['message_id'];
	    @$content =& $properties['content'];
	    @$files = $properties['files'];
	    @$worker_id = $properties['agent_id'];
	    
		$message = DAO_Ticket::getMessage($message_id);
        $message_headers = DAO_MessageHeader::getAll($message_id);		
		$ticket_id = $message->ticket_id;
		$ticket = DAO_Ticket::getTicket($ticket_id);
		$requesters = DAO_Ticket::getRequestersByTicket($ticket_id);

		// References
		if(!empty($message) && false !== (@$in_reply_to = $message_headers['message-id'])) {
		    $headers['References'] = $in_reply_to;
		    $headers['In-Reply-To'] = $in_reply_to;
		}
		
		// Body
//	    $mail->setBodyText($content);

	    switch($type) {
//	        case CerberusMessageType::FORWARD:
//	            // Forward to
//	            if(isset($properties['to'])) {
//	                $to[] = $properties['to'];
//	            }
//	            break;

	        case CerberusMessageType::EMAIL:
			    // Recepients
			    if(is_array($requesters)) {
				    foreach($requesters as $requester) { /* @var $requester CerberusAddress */
	                    $to[] = $requester->email;
				    }
				    
				    $headers['To'] = implode(', ', $to);
			    }
	            break;
	    }
	    
		// Send actual email (if necessary)
//		if ($type != CerberusMessageType::COMMENT) {
	    	// TODO: create DAO object for Agent, be able to pull address by having agent id.
    		// From
			//		$headers['From'] = $agent_address->personal . ' <' . $agent_address->email . '>';
			//		$message_id = DAO_Ticket::createMessage($ticket_id,$type,time(),$agent_id,$headers,$content);
			//		$mail->setFrom('somebody@example.com', 'Some Sender');

			// Subject
	        $headers['Subject'] = 'Re: ' . $ticket->subject; 
			
			// Cc
	        // [TODO] Reimplement (with validation)
			@$cc = $properties['cc'];
			if(!empty($cc)) {
			    $addresses = CerberusApplication::parseCsvString($cc);
			    if(is_array($addresses))
			    foreach($addresses as $address) {
//			        $mail->addCc($address, '');
			    }
			}
			
			// Bcc
	        // [TODO] Reimplement (with validation)
			@$bcc = $properties['bcc'];
			if(!empty($bcc)) {
			    $addresses = CerberusApplication::parseCsvString($bcc);
			    if(is_array($addresses))
			    foreach($addresses as $address) {
//			        $mail->addBcc($address, '');
			    }
			}
		    
			// Mime Attachments // [TODO] Reimplement
			if (is_array($files) && !empty($files)) {
				foreach ($files['tmp_name'] as $idx => $file) {
//					$attachment =& $mail->addAttachment(file_get_contents($files['tmp_name'][$idx]),$files['type'][$idx]); /* @var $attachment Zend_Mime_Part */
//					$attachment->filename = $files['name'][$idx];
				}
			}
			
			$mail->send($to, $headers, $content);
//		}
		
		// [TODO] Make this properly use team replies 
	    // (or reflect what the customer sent to), etc.
		$settings = CerberusSettings::getInstance();
		$from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM);
		$fromAddressId = CerberusApplication::hashLookupAddressId($from);
		
	    $fields = array(
	        DAO_Message::TICKET_ID => $ticket_id,
	        DAO_Message::MESSAGE_TYPE => $type,
	        DAO_Message::CREATED_DATE => time(),
	        DAO_Message::ADDRESS_ID => $fromAddressId // [TODO] Real sender id
	    );
		$message_id = DAO_Message::create($fields);
	    
		// Content
	    DAO_MessageContent::update($message_id, $content);
	    
	    // Headers
	    $sanitizedHeaders = self::sanitizeHeaders($headers);
	    foreach($sanitizedHeaders as $hk => $hv) {
	        DAO_MessageHeader::update($message_id, $ticket_id, $hk, $hv);
	    }

//		// if this message was submitted with attachments, store them in the filestore and link them in the db.
		if (is_array($files) && !empty($files)) {
			$attachment_path = APP_PATH . '/storage/attachments/';
		
			foreach ($files['tmp_name'] as $idx => $file) {
//				copy($files['tmp_name'][$idx],$attachment_location.$message_id.$idx);
//				DAO_Attachment::create($fields); // $message_id, $files['name'][$idx], $message_id.$idx);
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
	
//	// [TODO] We should example if there's a way to have the Zend_Mail class do this for us (generateMessage).
	static private function sanitizeHeaders($headers) {
	    $clean_headers = array();
	    
	    if(is_array($headers))
	    foreach($headers as $k => $v) {
	        $clean_headers[strtolower($k)] = $v;
	    }
	    
	    return $clean_headers;
	}
	
};

?>
