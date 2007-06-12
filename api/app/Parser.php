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
		$bIsNew = true;

		// Overloadable
		$sMask = '';
		$iClosed = 0;
		$enumSpamTraining = '';
		$iDate = time();
		
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
		$sSubject = isset($headers['subject']) ? $headers['subject'] : '(no subject)';
		
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
		
		$sInReplyTo = @$headers['in-reply-to'];
		$sReferences = @$headers['references'];

        $importNew = @$headers['x-cerberusnew'];
        $importAppend = @$headers['x-cerberusappendto'];
		
		// [JAS] [TODO] References header may contain multiple message-ids to find
//		if(!empty($sReferences) || !empty($sInReplyTo)) {
		if(!APP_PARSER_ALLOW_IMPORTS || (empty($importNew) && empty($importAppend))) {
//			$findMessageId = (!empty($sInReplyTo)) ? $sInReplyTo : $sReferences;
			$findMessageId = $sInReplyTo;
			$id = DAO_Ticket::getTicketByMessageId($findMessageId);
			$bIsNew = false;
		}

		// Are we importing a ticket?
        if(APP_PARSER_ALLOW_IMPORTS && (!empty($importNew) || !empty($importAppend))) {
            
            if(!empty($importNew)) {
                $importMask = @$headers['x-cerberusmask'];
                $importStatus = @$headers['x-cerberusstatus'];
                $importCreatedDate = @$headers['x-cerberuscreateddate'];
                $importSpamTraining = @$headers['x-cerberusspamtraining'];
                
                switch($importStatus) {
                    case 'C':
                        $iClosed = CerberusTicketStatus::CLOSED;
                        break;
                    default:
                        $iClosed = CerberusTicketStatus::OPEN;
                        break;
                }
                
                // [TODO] Need to check that this is unique in the local desk
                if(!empty($importMask))
                    $sMask = $importMask;
                
                if(!empty($importCreatedDate))
                    $iDate = $importCreatedDate;
                    
                if(!empty($importSpamTraining))
                    $enumSpamTraining = $importSpamTraining;
                    
                // [TODO] Mailbox ID/Name mapping to Teams/Categories
            }
            
            if(!empty($importAppend)) {
                $appendTo = DAO_Ticket::getTicketByMask($importAppend);
                $id = $appendTo->id;
                if(!empty($id)) $bIsNew = false;
//                echo "IMPORT DETECTED: Appending to existing ",$importAppend," (",$id,")<br>";
            }
        }
		
		if(empty($id)) {
			$team_id = CerberusParser::parseDestination($headers);
//			$wrote_id = DAO_Contact::lookupAddress($fromAddress, true);
			
			$fields = array(
				DAO_Ticket::MASK => (!empty($sMask) ? $sMask : CerberusApplication::generateTicketMask()),
				DAO_Ticket::SUBJECT => $sSubject,
				DAO_Ticket::IS_CLOSED => $iClosed,
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

		$settings = CerberusSettings::getInstance();
		$attachmentlocation = $settings->get(CerberusSettings::SAVE_FILE_PATH);
		
		if(!empty($attachments['plaintext'])) {
			$message_id = DAO_Ticket::createMessage($id,CerberusMessageType::EMAIL,$iDate,$fromAddressId,$headers,$attachments['plaintext']);

		} else { // generate the plaintext part
			if(!empty($attachments['html'])) {
			    $body = CerberusApplication::stripHTML($attachments['html']);
				$message_id = DAO_Ticket::createMessage($id,CerberusMessageType::EMAIL,$iDate,$fromAddressId,$headers,$body);
	            $attachments['files']['html_part.html'] = $attachments['html'];
//				unset($body);
//	            unset($attachments['html']);
			}
		}
		
		if(!empty($message_id)) {
			foreach ($attachments['files'] as $filename => $file) {
				$file_id = DAO_Ticket::createAttachment($message_id, $filename);
				if(empty($file_id)) continue;
				
			    file_put_contents($attachmentlocation.$file_id,$file);
			    
			    // [TODO] Make file attachments use buckets so we have a max per directory
			    
			    // [TODO] Split off attachments into its own DAO
			    DAO_Ticket::updateAttachment($file_id, array(
			        'filepath' => $file_id // [TODO] Make this a const later
			    ));
			}
		}

		// Spam scoring
		if($bIsNew) {
    		// Allow spam training overloading
		    if(!empty($enumSpamTraining)) {
			    if($enumSpamTraining == CerberusTicketSpamTraining::SPAM) {
	                CerberusBayes::markTicketAsSpam($id);		        
			    } elseif($enumSpamTraining == CerberusTicketSpamTraining::NOT_SPAM) {
		            CerberusBayes::markTicketAsNotSpam($id);
		        }
			} else { // No overload
			    CerberusBayes::calculateTicketSpamProbability($id);
			}
		}
		
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
