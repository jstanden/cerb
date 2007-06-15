<?php
class CerberusParserMessage {
    public $headers = array();
    public $body = '';
    public $htmlbody = '';
    public $files = array();
};

class CerberusParser {
	
	/**
	 * Enter description here...
	 * @param CerberusParserMessage $message
	 * @return integer $id
	 */
	static public function parseMessage($message) {
//		if (DEVBLOCKS_DEBUG == 'true') {echo ('Entering parseMessage() with rfcMessage :<br>'); print_r ($message); echo ('<hr>');}
		
		$id = CerberusParser::parseToTicket($message);
		
		return $id;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param CerberusParserMessage $message
	 * @return integer
	 */
	static public function parseToTicket($message) {
//		print_r($rfcMessage);

		$headers =& $message->headers;

		// To/From/Cc/Bcc
		$sReturnPath = @$headers['return-path'];
		$sReplyTo = @$headers['reply-to'];
		$sFrom = @$headers['from'];
		$sTo = @$headers['to'];
		$bIsNew = true;

//		echo htmlentities($sTo),' ',htmlentities($sFrom),' ',htmlentities($sReplyTo),' ',htmlentities($sReturnPath),"<BR>";
		
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
		    // [TODO] Do we still need this RFC address parser?
			$to = CerberusParser::parseRfcAddress($sTo);
		}
		
		// Subject
		$sSubject = isset($headers['subject']) ? $headers['subject'] : '(no subject)';
		
		// If quote printable subject
		if(0 == strcmp(substr($sSubject,0,2),'=?')) {
		    $sSubject = self::fixQuotePrintableString($sSubject);
		}
		
		// Date
		$iDate = @strtotime($headers['date']);
		if(empty($iDate)) $iDate = time();
		
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
		
		// Message Id / References / In-Reply-To
		$sInReplyTo = @$headers['in-reply-to'];
		$sMessageId = @$headers['message-id'];
//		$sReferences = @$headers['references'];

        $importNew = @$headers['x-cerberusnew'];
        $importAppend = @$headers['x-cerberusappendto'];

		// [JAS] [TODO] References header may contain multiple message-ids to find
//		if(!empty($sReferences) || !empty($sInReplyTo)) {
		if(!empty($sInReplyTo) && 0 != strcmp($sMessageId,$sInReplyTo) && empty($importNew) && empty($importAppend)) {
//			$findMessageId = (!empty($sInReplyTo)) ? $sInReplyTo : $sReferences;
			$findMessageId = $sInReplyTo;
			
//			echo "Find message id (", htmlentities($sInReplyTo), ") for (" . htmlentities($sMessageId) . ")... ";
			
			if(0 != strcmp($findMessageId,"''")) {
				if(null != ($id = DAO_Ticket::getTicketByMessageId($findMessageId))) {
				    $bIsNew = false;
//				    echo "matched $id!<br>";
				} else {
//				    echo "thought so, but no.<br>";
				}
			} else {
//			    echo "no match!<br>";
			}
			
//			return;
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
//		    echo "Creating new ticket<br>";
			$team_id = CerberusParser::parseDestination($headers);
			
			$fields = array(
				DAO_Ticket::MASK => (!empty($sMask) ? $sMask : CerberusApplication::generateTicketMask()),
				DAO_Ticket::SUBJECT => $sSubject,
				DAO_Ticket::IS_CLOSED => $iClosed,
				DAO_Ticket::FIRST_WROTE_ID => intval($fromAddressId),
				DAO_Ticket::LAST_WROTE_ID => intval($fromAddressId),
				DAO_Ticket::CREATED_DATE => $iDate,
				DAO_Ticket::UPDATED_DATE => $iDate,
				DAO_Ticket::TEAM_ID => intval($team_id),
			);
			$id = DAO_Ticket::createTicket($fields);

			if(false !== ($rule = self::parseTeamRules($team_id, $id, $fromAddress, $sSubject))) { /* @var $rule Model_TeamRoutingRule */
                //Assume our rule match is not spam
                if(empty($rule->params['spam'])) { // if we didn't already train
	                echo "Assuming our match isn't spam!<br>";
                    $enumSpamTraining = CerberusTicketSpamTraining::NOT_SPAM;
                }
			}
		}
		
		// [JAS]: Add requesters to the ticket
	    if(!empty($fromAddressId) && !empty($id))
		    DAO_Ticket::createRequester($fromAddressId,$id);
		
		$settings = CerberusSettings::getInstance();
		$attachmentlocation = $settings->get(CerberusSettings::SAVE_FILE_PATH);
		
		// [TODO] Move createMessage to DAO_Message and fix ->create($fields)
		
		if(!empty($message->body)) {
			$email_id = DAO_Ticket::createMessage($id,CerberusMessageType::EMAIL,$iDate,$fromAddressId,$headers,$message->body);

		} else { // generate the plaintext part
			if(!empty($message->htmlbody)) {
			    $body = CerberusApplication::stripHTML($message->htmlbody);
				$email_id = DAO_Ticket::createMessage($id,CerberusMessageType::EMAIL,$iDate,$fromAddressId,$headers,$body);
				unset($body);
			}
		}
		
		if(!empty($email_id)) {
		    // [TODO] Clean up (add to create)
			DAO_Message::update($email_id,array(
			    DAO_Message::MESSAGE_ID => $sMessageId
			));
		    
			foreach ($message->files as $filename => $file) {
			    
				$file_id = DAO_Ticket::createAttachment($email_id, $filename);
				if(empty($file_id)) continue;
				
//			    file_put_contents($attachmentlocation.$file_id,$file);

	            // [TODO] Need to push this out to a generic class (no ->tmpname access)
	            rename($file->tmpname, $attachmentlocation.$file_id);
			    
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
		
		unset($message);
		
//		$ticket = DAO_Ticket::getTicket($id);
		return $id;
//		return $ticket;
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
		
		// [TODO] The split could be handled by ::parseAddressList (commas, semi-colons, etc.)
		$aTo = CerberusApplication::parseCsvString(@$headers['to']);
		$aCc = CerberusApplication::parseCsvString(@$headers['cc']);
		
		$destinations = $aTo + $aCc; // [TODO] Can we count on this?

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
	
//	static private function parseMimeParts($parts,&$attachments) {
//		
//		foreach($parts as $part) {
//			CerberusParser::parseMimePart($part,$attachments);
//		}
//		
//		return $attachments;
//	}
//	
//	static private function parseMimePart($part,&$attachments) {
//		// valid primary types are found at http://www.iana.org/assignments/media-types/
//		$contentType = @$part->ctype_primary.'/'.@$part->ctype_secondary;
//		$fileName = @$part->d_parameters['filename'];
//		if (empty($fileName)) $fileName = @$part->ctype_parameters['name'];
//		
//		if(0 == strcasecmp($contentType,'text/plain') && empty($fileName)) {
//			$attachments['plaintext'] .= $part->body;
//			
//		} elseif(0 == strcasecmp($contentType,'text/html') && empty($fileName)) {
//			$attachments['html'] .= $part->body;
//			
//		} elseif(0 == strcasecmp(@$part->ctype_primary,'multipart')) {
//			CerberusParser::parseMimeParts($part, $attachments);
//			
//		} else {
//			if (empty($fileName))
//				$attachments['files'][] = $part->body;
//			else
//				$attachments['files'][$fileName] = $part->body;
//		}
//	}
	
	// [TODO] Phase out in favor of the CerberusUtils class
	static function parseRfcAddress($address_string) {
		return CerberusUtils::parseRfcAddressList($address_string);
	}
	
	static private function fixQuotePrintableString($str) {
		preg_match("/\=\?(.*?)\?(.*?)\?(.*?)\?\=/", $str, $matches);
		
		if(count($matches) != 4) {
		    return $str;
		}
		
		$encoding = $matches[1];
		$code = $matches[2];
		$s = $matches[3];
		
		switch(strtolower($code)) {
		    case 'b':
		        $out = base64_decode($s);
		        break;
		    case 'q':
		        $out = quoted_printable_decode($s);
		        break;
		    default:
		        $out = $s;
		        break;
		}
		
		return $out;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $team_id
	 * @param CerberusTicket $ticket
	 */
	static private function parseTeamRules($team_id, $ticket_id, $fromAddress, $sSubject) {
		// Check the team's inbox rules and see if we have a new destination
        if(!empty($team_id)) {
            
//            if(!empty($rule_ids)) {
                // [TODO] Cache this call
//   	            $team_rules = DAO_TeamRoutingRule::getList($rule_ids);
   	            $team_rules = DAO_TeamRoutingRule::getByTeamId($team_id);
   	            
   	            echo "Scanning (From: ",$fromAddress,"; Subject: ",$sSubject,")<BR>";
   	            
   	            foreach($team_rules as $rule) { /* @var $rule Model_TeamRoutingRule */
   	                $pattern = $rule->getPatternAsRegexp();
   	                $haystack = ($rule->header=='from') ? $fromAddress : $sSubject ;
   	                if(preg_match($pattern, $haystack)) {
   	                    echo "I matched ($pattern) for ($ticket_id)!<br>";
   	                    
   	                    $action = new Model_DashboardViewAction();
   	                    $action->params = $rule->params;
   	                    $action->run(array($ticket_id));
   	                    
   	                    DAO_TeamRoutingRule::update($rule->id, array(
   	                        DAO_TeamRoutingRule::POS => intval($rule->pos) + 1
   	                    ));
   	                    
   	                    return $rule;
   	                }
   	            }
//            }
        }
        
        return false;
	}
};
?>
