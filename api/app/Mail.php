<?php
class CerberusMail {
	private function __construct() {}
	
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
	    'priority'
	    'status'
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
	    @$content = $properties['content'];
	    @$files = $properties['files'];
	    
		$message = DAO_Ticket::getMessage($message_id);
		$ticket_id = $message->ticket_id;
		$ticket = DAO_Ticket::getTicket($ticket_id);
		$requesters = DAO_Ticket::getRequestersByTicket($ticket_id);

		// References
		if(!empty($message) && false !== ($in_reply_to = $message->headers['message-id'])) {
		    $headers['References'] = $in_reply_to;
		    $headers['In-Reply-To'] = $in_reply_to;
		}
		
		// Body
//	    $mail->setBodyText($content);

	    switch($type) {
	        case CerberusMessageType::FORWARD:
	            // Forward to
	            if(isset($properties['to'])) {
	                $to[] = $properties['to'];
	            }
	            break;
	            
	        case CerberusMessageType::EMAIL:
			    // Recepients
			    if(is_array($requesters))
			    foreach($requesters as $requester) { /* @var $requester CerberusAddress */
                    $to[] = $requester->email;
			    }
	            break;
	    }
	    
		// Send actual email (if necessary)
		if ($type != CerberusMessageType::COMMENT) {
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
		}
		
		// [TODO] Include real address_id
		$message_id = DAO_Ticket::createMessage($ticket_id,$type,time(),1,self::sanitizeHeaders($headers),$content);

//		// if this message was submitted with attachments, store them in the filestore and link them in the db.
		if (is_array($files) && !empty($files)) {
			$settings = CerberusSettings::getInstance();
			$attachment_location = $settings->get(CerberusSettings::SAVE_FILE_PATH);
		
			foreach ($files['tmp_name'] as $idx => $file) {
//				copy($files['tmp_name'][$idx],$attachment_location.$message_id.$idx);
//				DAO_Ticket::createAttachment($message_id, $files['name'][$idx], $message_id.$idx);
			}
		}
		
		// Handle post-mail actions
		$change_fields = array();
		
		if(!empty($properties['priority'])) {
		    $change_fields[DAO_Ticket::PRIORITY] = $properties['priority'];
		}
		
		if(!empty($properties['status'])) {
		    $change_fields[DAO_Ticket::STATUS] = $properties['status'];
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
