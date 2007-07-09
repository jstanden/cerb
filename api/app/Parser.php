<?php
class CerberusParserMessage {
    public $headers = array();
    public $body = '';
    public $htmlbody = '';
    public $files = array();
};

class CerberusParser {
    const ATTACHMENT_BUCKETS = 100; // hash
	
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
		
		@$fromAddress = $from[0]->mailbox.'@'.$from[0]->host;
		@$fromPersonal = $from[0]->personal;
		$fromAddressId = CerberusApplication::hashLookupAddressId($fromAddress, true); 

		// [TODO] This wasn't doing anything
//		if(is_array($to))
//		foreach($to as $recipient) {
//			@$toAddress = $recipient->mailbox.'@'.$recipient->host;
//			@$toPersonal = $recipient->personal;
//			@$toAddressId = CerberusApplication::hashLookupAddressId($toAddress, true); // $toPersonal
//		}
		
		// Message Id / References / In-Reply-To
		@$sInReplyTo = $headers['in-reply-to'];
		@$sMessageId = $headers['message-id'];
//		$sReferences = @$headers['references'];

        @$importNew = $headers['x-cerberusnew'];
        @$importAppend = $headers['x-cerberusappendto'];

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
            }
            
            if(!empty($importAppend)) {
                $appendTo = CerberusApplication::hashLookupTicketIdByMask($importAppend);
                if(!empty($appendTo)) {
                    $id = $appendTo;
                    $bIsNew = false;
                }
//                echo "IMPORT DETECTED: Appending to existing ",$importAppend," (",$id,")<br>";
            }
        }
		
		if(empty($id)) { // New Ticket
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
				DAO_Ticket::LAST_ACTION_CODE => CerberusTicketActionCode::TICKET_OPENED,
			);
			$id = DAO_Ticket::createTicket($fields);

			// Don't replace this with the master event listener
			if(false !== ($rule = CerberusApplication::parseTeamRules($team_id, $id, $fromAddress, $sSubject))) { /* @var $rule Model_TeamRoutingRule */
                //Assume our rule match is not spam
                if(empty($rule->do_spam)) { // if we didn't already train
//	                echo "Assuming our match isn't spam!<br>";
                    $enumSpamTraining = CerberusTicketSpamTraining::NOT_SPAM;
                }
			}
			
//		} else { // Reply
//		    $reply_ticket = DAO_Ticket::getTicket($id);
//		    if(!empty($reply_ticket)) { //  && !empty($reply_ticket->last_worker_id)
//		        DAO_Ticket::updateTicket($id,array(
//		            DAO_Ticket::LAST_ACTION = 'customer replied'
//		        ));
//		    }
		    
		}
		
		// [JAS]: Add requesters to the ticket
	    // [TODO] Make sure they aren't a worker
	    if(!empty($fromAddressId) && !empty($id))
		    DAO_Ticket::createRequester($fromAddressId,$id);
		
		$settings = CerberusSettings::getInstance();
		$attachment_path = APP_PATH . '/storage/attachments/';
		
		if(empty($message->body) && !empty($message->htmlbody)) { // generate the plaintext part
	        $fields = array(
	            DAO_Message::TICKET_ID => $id,
	            DAO_Message::MESSAGE_TYPE => CerberusMessageType::EMAIL,
	            DAO_Message::CREATED_DATE => $iDate,
	            DAO_Message::ADDRESS_ID => $fromAddressId
	        );
		    $email_id = DAO_Message::create($fields);
		    
		    // Content
		    $body = CerberusApplication::stripHTML($message->htmlbody);
		    DAO_MessageContent::update($email_id, $body);
			
		    // Headers
			foreach($headers as $hk => $hv) {
			    DAO_MessageHeader::update($email_id, $id, $hk, $hv);
			}
		    
			unset($body);
		} else { // Insert the plaintext body (even blank)
	        $fields = array(
	            DAO_Message::TICKET_ID => $id,
	            DAO_Message::MESSAGE_TYPE => CerberusMessageType::EMAIL,
	            DAO_Message::CREATED_DATE => $iDate,
	            DAO_Message::ADDRESS_ID => $fromAddressId
	        );
			$email_id = DAO_Message::create($fields);
			
			// Content
			DAO_MessageContent::update($email_id, $message->body);
			
			// Headers
			foreach($headers as $hk => $hv) {
			    DAO_MessageHeader::update($email_id, $id, $hk, $hv);
			}
		}
		
		// First Thread
		if($bIsNew && !empty($email_id)) { // First thread
			DAO_Ticket::updateTicket($id,array(
			    DAO_Ticket::FIRST_MESSAGE_ID => $email_id
		    ));
		}
		
		if(!empty($email_id)) {
		    // No longer needed (it's in the message_header table)
//			DAO_Message::update($email_id,array(
//			    DAO_Message::MESSAGE_ID => $sMessageId
//			));
		    
			foreach ($message->files as $filename => $file) { /* @var $file ParserFile */
			    $fields = array(
			        DAO_Attachment::MESSAGE_ID => $email_id,
			        DAO_Attachment::DISPLAY_NAME => $filename,
			        DAO_Attachment::MIME_TYPE => $file->mime_type,
			        DAO_Attachment::FILE_SIZE => intval($file->file_size),
			    );
			    $file_id = DAO_Attachment::create($fields);
				
			    if(empty($file_id)) {
			        @unlink($file->tmpname); // remove our temp file
				    continue;
				}
				
			    // Make file attachments use buckets so we have a max per directory
	            $attachment_bucket = sprintf("%03d/",
	                rand(1,100)
	            );
	            $attachment_file = $file_id;
	            
	            if(!file_exists($attachment_path.$attachment_bucket)) {
	                @mkdir($attachment_path.$attachment_bucket, 0770, true);
	                // [TODO] Needs error checking
	            }

	            rename($file->getTempFile(), $attachment_path.$attachment_bucket.$attachment_file);
			    
			    // [TODO] Split off attachments into its own DAO
			    DAO_Attachment::update($file_id, array(
			        DAO_Attachment::FILEPATH => $attachment_bucket.$attachment_file
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
			    $out = CerberusBayes::calculateTicketSpamProbability($id);
			    
			    if(!empty($team_id)) {
				    static $group_settings = null;
				    if(null == $group_settings) {
				        $group_settings = DAO_GroupSettings::getSettings();
				    }
			    
			        @$spam_threshold = $group_settings[$team_id][DAO_GroupSettings::SETTING_SPAM_THRESHOLD];
			        @$spam_action = $group_settings[$team_id][DAO_GroupSettings::SETTING_SPAM_ACTION];
			        @$spam_action_param = $group_settings[$team_id][DAO_GroupSettings::SETTING_SPAM_ACTION_PARAM];
			        
				    if($out['probability']*100 >= $spam_threshold) {
				        switch($spam_action) {
				            default:
				            case 0: // do nothing
	                            break;
				            case 1: // delete
	                            // [TODO] Would have been much nicer to delete before this point
	                            DAO_Ticket::updateTicket($id,array(
	                                DAO_Ticket::IS_CLOSED => 1,
	                                DAO_Ticket::IS_DELETED => 1
	                            ));
	                            break;
				            case 2: // move
	                            // [TODO] Verify destination bucket exists
	                            if(!empty($spam_action_param) && !empty($spam_action_param)) {
		                            DAO_Ticket::updateTicket($id,array(
		                                DAO_Ticket::TEAM_ID => $team_id,
		                                DAO_Ticket::CATEGORY_ID => $spam_action_param
		                            ));
	                            }	                            
				                break;
				        }
				    }
			    }
			}
		}
		
		unset($message);
		
		// Re-open and update our date on new replies
		if(!$bIsNew && empty($importAppend)) {
			DAO_Ticket::updateTicket($id,array(
			    DAO_Ticket::UPDATED_DATE => time(),
			    DAO_Ticket::IS_CLOSED => 0,
			    DAO_Ticket::LAST_WROTE_ID => $fromAddressId,
			    DAO_Ticket::LAST_ACTION_CODE => CerberusTicketActionCode::TICKET_CUSTOMER_REPLY,
			));
			
			// [TODO] The TICKET_CUSTOMER_REPLY should be sure of this message address not being a worker
		}
		
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

	
};
?>
