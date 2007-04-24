<?php
class CerberusMail {
	private function __construct() {}
	
	/**
	 * @return Zend_Mail
	 */
	static function createInstance() {
		$mail = new Zend_Mail();
		$settings = CerberusSettings::getInstance();

		// [TODO] Transport toggle
		
		// SMTP
		require_once 'Zend/Mail/Transport/Smtp.php';
		$smtp_host = $settings->get(CerberusSettings::SMTP_HOST,'localhost');
		$smtp_user = $settings->get(CerberusSettings::SMTP_AUTH_USER,null);
		$smtp_pass = $settings->get(CerberusSettings::SMTP_AUTH_PASS,null);
		
		// [TODO] Test SMTP Auth
		if(!empty($smtp_user)) { // AUTH
			$config = array('auth' => 'login',
                'username' => $smtp_user,
                'password' => $smtp_pass);
		} else { // no AUTH
			$config = array();
		}
		$tr = new Zend_Mail_Transport_Smtp($settings->get(CerberusSettings::SMTP_HOST), $config);
		
		// Mail()
		//	require_once 'Zend/Mail/Transport/Sendmail.php';
		//	$tr = new Zend_Mail_Transport_Sendmail();
		
		Zend_Mail::setDefaultTransport($tr);
		
		// Mail Defaults
		$from_addy = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM,'nobody@localhost');
		$from_personal = $settings->get(CerberusSettings::DEFAULT_REPLY_PERSONAL,'');
		$mail->setFrom($from_addy, $from_personal);
		
		$mail->addHeader('Date', gmdate('r'));
		$mail->addHeader('Message-Id', CerberusApplication::generateMessageId());
		$mail->addHeader('X-Mailer', 'Cerberus Helpdesk (Build '.APP_BUILD.')');
		$mail->addHeader('X-MailGenerator', 'Cerberus Helpdesk (Build '.APP_BUILD.')');
		
        return $mail;
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
	    'priority'
	    'status'
	    'agent_id'
		*/
	    
	    // properties
	    @$type = $properties['type'];
	    @$message_id = $properties['message_id'];
	    @$content = $properties['content'];
	    @$files = $properties['files'];
	    
		// objects
	    $mail = self::createInstance(); /* @var $mail Zend_Mail */
		$message = DAO_Ticket::getMessage($message_id);
		$ticket_id = $message->ticket_id;
		$ticket = DAO_Ticket::getTicket($ticket_id);
		$requesters = DAO_Ticket::getRequestersByTicket($ticket_id);

		// References
		if(!empty($message) && false !== ($in_reply_to = $message->headers['message-id'])) {
		    $mail->addHeader('References', $in_reply_to);
		    $mail->addHeader('In-Reply-To', $in_reply_to);
		}
		
		// Body
	    $mail->setBodyText($content);

	    switch($type) {
	        case CerberusMessageType::FORWARD:
	            // Forward to
	            if(isset($properties['to'])) {
	                $mail->addTo($properties['to'],'');
	            }
	            break;
	            
	        case CerberusMessageType::EMAIL:
			    // Recepients
			    if(is_array($requesters))
			    foreach($requesters as $requester) { /* @var $requester CerberusAddress */
			        $mail->addTo($requester->email, ''); // $requester->personal    
			    }
	            break;
	    }
	    
		// Send actual email (if necessary)
		if ($type != CerberusMessageType::COMMENT) {
	    	// TODO: create DAO object for Agent, be able to pull address by having agent id.
    		// From
			//		$headers['From'] = $agent_address->personal . ' <' . $agent_address->email . '>';
			//		$message_id = DAO_Ticket::createMessage($ticket_id,$type,gmmktime(),$agent_id,$headers,$content);
			//		$mail->setFrom('somebody@example.com', 'Some Sender');

			// Subject
			$mail->setSubject('Re: ' . $ticket->subject); // [TODO] Do properly
			
			// Cc
			@$cc = $properties['cc'];
			if(!empty($cc)) {
			    $addresses = CerberusApplication::parseCsvString($cc);
			    if(is_array($addresses))
			    foreach($addresses as $address) {
			        $mail->addCc($address, '');
			    }
			}
			
			// Bcc
			@$bcc = $properties['bcc'];
			if(!empty($bcc)) {
			    $addresses = CerberusApplication::parseCsvString($bcc);
			    if(is_array($addresses))
			    foreach($addresses as $address) {
			        $mail->addBcc($address, '');
			    }
			}
		    
			// Mime Attachments
			if (is_array($files) && !empty($files)) {
				foreach ($files['tmp_name'] as $idx => $file) {
					$attachment =& $mail->addAttachment(file_get_contents($files['tmp_name'][$idx]),$files['type'][$idx]); /* @var $attachment Zend_Mime_Part */
					$attachment->filename = $files['name'][$idx];
				}
			}
			
			$mail->send();
		}
		
		// Add message to the database
		$headers = self::sanitizeHeaders($mail->getHeaders());
		// [TODO] Include real address_id
		$message_id = DAO_Ticket::createMessage($ticket_id,$type,gmmktime(),1,$headers,$content);

//		// if this message was submitted with attachments, store them in the filestore and link them in the db.
		if (is_array($files) && !empty($files)) {
			$settings = CerberusSettings::getInstance();
			$attachment_location = $settings->get(CerberusSettings::SAVE_FILE_PATH);
		
			foreach ($files['tmp_name'] as $idx => $file) {
				copy($files['tmp_name'][$idx],$attachment_location.$message_id.$idx);
				DAO_Ticket::createAttachment($message_id, $files['name'][$idx], $message_id.$idx);
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
	
	// [TODO] We should example if there's a way to have the Zend_Mail class do this for us (generateMessage).
	static private function sanitizeHeaders($raw_headers) {
	    $headers = array();
	    
	    if(is_array($raw_headers))
	    foreach($raw_headers as $k => $v) {
	        $vals = array();
	        
	        if(is_array($v))
	        foreach($v as $vk => $vv) {
	            if(is_numeric($vk))
	                $vals[] = $vv;    
	        }
	        
	        if(isset($v['append'])) {
	            $val = implode(', ', $vals);
	        } else {
	            $val = implode(Zend_Mime::LINEEND, $vals);
	        }
	        
	        $headers[strtolower($k)] = $val;
	    }
	    
	    return $headers;
	}

}
?>