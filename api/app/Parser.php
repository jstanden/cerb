<?php
class CerberusParser {
	
	/**
	 * Enter description here...
	 * @param object $rfcMessage
	 * @return CerberusTicket ticket object
	 */
	static public function parseMessage($rfcMessage) {
		if (DEVBLOCKS_DEBUG == 'true') {echo ('Entering parseMessage() with rfcMessage :<br>'); print_r ($rfcMessage); echo ('<hr>');}
		
//		$continue = CerberusParser::parsePreRules($rfcMessage);
		$ticket = CerberusParser::parseToTicket($rfcMessage);
//		CerberusParser::parsePostRules($ticket);
		
		return $ticket;
	}
	
	static public function parseToTicket($rfcMessage) {
//		print_r($rfcMessage);

		$headers =& $rfcMessage->headers;

		// To/From/Cc/Bcc
		$sReturnPath = @$headers['return-path'];
		$sReplyTo = @$headers['reply-to'];
		$sFrom = @$headers['from'];
		$sTo = @$headers['to'];
		$sMask = CerberusApplication::generateTicketMask();
		$bIsNew = true;
		
		$from = array();
		$to = array();
		
		if(!empty($sReplyTo)) {
			$from = CerberusParser::parseRfcAddress($sReplyTo);
		} elseif(!empty($sFrom)) {
			$from = CerberusParser::parseRfcAddress($sFrom);
		} elseif(!empty($sReturnPath)) {
			$from = CerberusParser::parseRfcAddress($sReturnPath);
		}
		
		if(!empty($sTo)) {
			$to = CerberusParser::parseRfcAddress($sTo);
		}
		
		// Subject
		$sSubject = @$headers['subject'];
		
		// Date
		$iDate = @strtotime($headers['date']);
		if(empty($iDate)) $iDate = time();
		
		// Message Id / References / In-Reply-To
//		echo "Parsing message-id: ",@$headers['message-id'],"<BR>\r\n";

		if(empty($from) || !is_array($from))
			return false;
		
		$fromAddress = $from[0]->mailbox.'@'.$from[0]->host;
		$fromPersonal = $from[0]->personal;
		$fromAddressId = DAO_Contact::lookupAddress($fromAddress, true); 
		//DAO_Contact::createAddress($fromAddress, $fromPersonal);

		if(is_array($to))
		foreach($to as $recipient) {
			$toAddress = $recipient->mailbox.'@'.$recipient->host;
			$toPersonal = $recipient->personal;
			$toAddressId = DAO_Contact::createAddress($toAddress,$toPersonal);
		}
		
		$sReferences = @$headers['references'];
		$sInReplyTo = @$headers['in-reply-to'];
		
		// [JAS] [TODO] References header may contain multiple message-ids to find
//		if(!empty($sReferences) || !empty($sInReplyTo)) {
		if(!empty($sInReplyTo)) {
//			$findMessageId = (!empty($sInReplyTo)) ? $sInReplyTo : $sReferences;
			$findMessageId = $sInReplyTo;
			$id = DAO_Ticket::getTicketByMessageId($findMessageId);
			$bIsNew = false;
		}
		
		if(empty($id)) {
			$team_id = CerberusParser::parseDestination($headers);
//			$wrote_id = DAO_Contact::lookupAddress($fromAddress, true);
			
			$fields = array(
				DAO_Ticket::MASK => $sMask,
				DAO_Ticket::SUBJECT => $sSubject,
				DAO_Ticket::STATUS => CerberusTicketStatus::OPEN,
				DAO_Ticket::FIRST_WROTE_ID => intval($fromAddressId),
				DAO_Ticket::LAST_WROTE_ID => intval($fromAddressId),
				DAO_Ticket::CREATED_DATE => $iDate,
				DAO_Ticket::UPDATED_DATE => $iDate,
				DAO_Ticket::TEAM_ID => intval($team_id)
			);
			$id = DAO_Ticket::createTicket($fields);
		}
		
		// [JAS]: Add requesters to the ticket
	    if(!empty($fromAddressId) && !empty($id))
		    DAO_Ticket::createRequester($fromAddressId,$id);
		
		$attachments = array();
		$attachments['plaintext'] = '';
		$attachments['html'] = '';
		$attachments['files'] = array();
		
		if(@is_array($rfcMessage->parts)) {
			CerberusParser::parseMimeParts($rfcMessage->parts,$attachments);
		} else {
			CerberusParser::parseMimePart($rfcMessage,$attachments);			
		}

		if(!empty($attachments['plaintext'])) {
			$settings = CerberusSettings::getInstance();
			$attachmentlocation = $settings->get(CerberusSettings::SAVE_FILE_PATH);
		
			$message_id = DAO_Ticket::createMessage($id,CerberusMessageType::EMAIL,$iDate,$fromAddressId,$headers,$attachments['plaintext']);

//			if(!empty($attachments['html'])) {
//				$message_id = DAO_Ticket::createMessage($id,CerberusMessageType::EMAIL,$iDate,$fromAddressId,$headers,$attachments['plaintext']);
//			}
			
			$idx = 0;
			foreach ($attachments['files'] as $filename => $file) {
				file_put_contents($attachmentlocation.$message_id.$idx,$file);
				DAO_Ticket::createAttachment($message_id, $filename, $message_id.$idx);
				$idx++;
			}
		}

		// Spam scoring
		if($bIsNew) CerberusBayes::calculateTicketSpamProbability($id);
		
		$ticket = DAO_Ticket::getTicket($id);
		return $ticket;
	}

	/**
	 * Enter description here...
	 *
	 * @param array $headers
	 * @return integer team id
	 */
	static private function parseDestination($headers) {
		static $routing = null;
		
		$settings = CerberusSettings::getInstance();
		
		// [TODO] The split could be handled by Mail_RFC822:parseAddressList (commas, semi-colons, etc.)
		$aTo = split(',', @$headers['to']);
		$aCc = split(',', @$headers['cc']);
		
		$destinations = $aTo + $aCc;

		// [TODO] Should this cache be at the class level?
		if(is_null($routing))
			$routing = DAO_Mail::getMailboxRouting();
		
		foreach($destinations as $destination) {
			$structure = CerberusParser::parseRfcAddress($destination);
			
			if(empty($structure[0]->mailbox) || empty($structure[0]->host))
				continue;
			
			$address = $structure[0]->mailbox.'@'.$structure[0]->host;
			
			// Test each pattern successively
			foreach($routing as $route) { /* @var $route Model_MailRoute */
				$pattern = sprintf("/^%s$/i",
					str_replace(array('*'),array('.*?'),$route->pattern)
				);
				if(preg_match($pattern,$address)) 
					return $route->team_id;
			}
		}
		
		// envelope + delivered 'Delivered-To'
		// received
		
		// Check if we have a default mailbox configured before returning NULL.		
		$default_team_id = $settings->get(CerberusSettings::DEFAULT_TEAM_ID,0);
		
		if(!empty($default_team_id)) { // catchall
			return $default_team_id;
		}
		
		return null; // bounce
	}
	
	static private function parseMimeParts($parts,&$attachments) {
		
		foreach($parts as $part) {
			CerberusParser::parseMimePart($part,$attachments);
		}
		
		return $attachments;
	}
	
	static private function parseMimePart($part,&$attachments) {
		// valid primary types are found at http://www.iana.org/assignments/media-types/
		$contentType = @$part->ctype_primary.'/'.@$part->ctype_secondary;
		$fileName = @$part->d_parameters['filename'];
		if (empty($fileName)) $fileName = @$part->ctype_parameters['name'];
		
		if(0 == strcasecmp($contentType,'text/plain') && empty($fileName)) {
			$attachments['plaintext'] .= $part->body;
			
		} elseif(0 == strcasecmp($contentType,'text/html') && empty($fileName)) {
			$attachments['html'] .= $part->body;
			
		} elseif(0 == strcasecmp(@$part->ctype_primary,'multipart')) {
			CerberusParser::parseMimeParts($part, $attachments);
			
		} else {
			if (empty($fileName))
				$attachments['files'][] = $part->body;
			else
				$attachments['files'][$fileName] = $part->body;
		}
	}
	
	// [TODO] Phase out in favor of the CerberusUtils class
	static function parseRfcAddress($address_string) {
		return CerberusUtils::parseRfcAddressList($address_string);
	}
	
};
?>
