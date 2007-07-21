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
		$sSubject = (isset($headers['subject']) && !empty($headers['subject'])) ? $headers['subject'] : '(no subject)';
		
		// If quote printable subject
		if(0 == strcmp(substr($sSubject,0,2),'=?')) {
		    $sSubject = self::fixQuotePrintableString($sSubject);
		}
		
		// Date
		$iDate = @strtotime($headers['date']);
		if(empty($iDate)) $iDate = time();
		
		if(empty($from) || !is_array($from))
			return NULL;
		
		@$fromAddress = $from[0]->mailbox.'@'.$from[0]->host;
		@$fromPersonal = $from[0]->personal;
		if(null == ($fromAddressId = CerberusApplication::hashLookupAddressId($fromAddress, true))) {
			return NULL;
		}

		// Message Id / References / In-Reply-To
		@$sMessageId = $headers['message-id'];
		
		// Imports
        @$importNew = $headers['x-cerberusnew'];
        @$importAppend = $headers['x-cerberusappendto'];

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
        
        $body_append_text = array();
        $body_append_html = array();
        // [mdf]Check attached files before creating the ticket because we may need to overwrite the message-id
		// also store any contents of rfc822 files so we can include them after the body
		foreach ($message->files as $filename => $file) { /* @var $file ParserFile */
			
			switch($file->mime_type) {
				case 'message/rfc822':
					$full_filename = $file->tmpname;
					$mail = mailparse_msg_parse_file($full_filename);
					$struct = mailparse_msg_get_structure($mail);
					$msginfo = mailparse_msg_get_part_data($mail);
					
					$inline_headers = $msginfo['headers'];
					if(strtolower(substr($headers['from'], 0, 11))=='postmaster@' || strtolower(substr($headers['from'], 0, 14))=='mailer-daemon@') {
						$headers['in-reply-to'] = $inline_headers['message-id'];
					}

					if(empty($info['content-name'])) {
						if($info['content-type'] == 'text/plain') {
							$body_append_text[] = @mailparse_msg_extract_part_file($section, $full_filename, NULL);
						}
						elseif($info['content-type'] == 'text/html') {
							$body_append_html[] = @mailparse_msg_extract_part_file($section, $full_filename, NULL);
						}
					}
					//[mdf] [TODO] nuke the attachment 
						
				break;
			}
		}
        
		// [JAS] [TODO] References header may contain multiple message-ids to find
		if(empty($importNew) && empty($importAppend) && null != ($id = self::findParentMessage($headers))) {
        	$bIsNew = false;
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
		    // [mdf] append the contents of any message bodies found in message attachments earlier 
		    if(!empty($body_append_html)) {
		    	// [TODO] make the appended content formatted better so we can tell what it is
		    	for($i=0; $i < count($body_append_html); $i++) {
					$body .= "\r\n\r\n" . CerberusApplication::stripHTML($body_append_html[$i]);
		    	}
		    }
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
			
			$body = $message->body;
			//[mdf] append the contents of any message bodies found in message attachments earlier
			if(!empty($body_append_text)) {
				// [TODO] make the appended content formatted better so we can tell what it is 
				$body .= implode("\r\n\r\n", $body_append_text);
			}
			
			// Content
			DAO_MessageContent::update($email_id, $body);
			
			// Headers
			foreach($headers as $hk => $hv) {
			    DAO_MessageHeader::update($email_id, $id, $hk, $hv);
			}
		}
		
		// [mdf] Loop through files to insert attachment records in the db, and move temporary files
		if(!empty($email_id)) {
		    // No longer needed (it's in the message_header table)
//			DAO_Message::update($email_id,array(
//			    DAO_Message::MESSAGE_ID => $sMessageId
//			));
			foreach ($message->files as $filename => $file) { /* @var $file ParserFile */
				//[mdf] [TODO] We might want to just skip rfc822 messages since we extracted their content above
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

		// First Thread
		if($bIsNew && !empty($email_id)) { // First thread
			DAO_Ticket::updateTicket($id,array(
			    DAO_Ticket::FIRST_MESSAGE_ID => $email_id
		    ));
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
		
		return $id;
	}

	static private function findParentMessage($headers) {
		@$sSubject = $headers['subject'];
		@$sMessageId = trim($headers['message-id']);
		@$sInReplyTo = trim($headers['in-reply-to']);
		@$sReferences = trim($headers['references']);

		// [TODO] Could turn string comparisons into hashes here for simple equality checks
		
		$aReferences = array();
		
		// Add all References
		if(!empty($sReferences)) {
			if(preg_match("/(\<.*?\@.*?\>)/", $sReferences, $matches)) {
				unset($matches[0]); // who cares about the pattern
				foreach($matches as $ref) {
					$ref = trim($ref);
					if(!empty($ref) && 0 != strcasecmp($ref,$sMessageId))
						$aReferences[$ref] = 1;
				}
			}
		}

		unset($matches);
		
		// Append first <*> from In-Reply-To
		if(!empty($sInReplyTo)) {
			if(preg_match("/(\<.*?\@.*?\>)/", $sInReplyTo, $matches)) {
				if(isset($matches[1])) { // only use the first In-Reply-To
					$ref = trim($matches[1]);
					if(!empty($ref) && 0 != strcasecmp($ref,$sMessageId))
						$aReferences[$ref] = 1;
				}
			}
		}
		
		// Try matching our references or in-reply-to
		if(is_array($aReferences) && !empty($aReferences)) {
			foreach(array_keys($aReferences) as $ref) {
				if(empty($ref)) continue;
				if(null != ($id = DAO_Ticket::getTicketByMessageId($ref)))
				    return $id;
			}
		}
		
		return NULL;
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
		
		$aTo = array();
		$aCc = array();
		$aEnvelopeTo = array();
		$aXEnvelopeTo = array();
		$aDeliveredTo = array();
		
		$aTo = imap_rfc822_parse_adrlist(@$headers['to'],'localhost');
		if(isset($headers['cc']))
			$aCc = imap_rfc822_parse_adrlist($headers['cc'],'localhost');
		if(isset($headers['envelope-to']))
			$aEnvelopeTo = imap_rfc822_parse_adrlist(@$headers['envelope-to'],'localhost');
		if(isset($headers['x-envelope-to']))
			$aXEnvelopeTo = imap_rfc822_parse_adrlist($headers['x-envelope-to'],'localhost');
		if(isset($headers['delivered-to']))
			$aDeliveredTo = imap_rfc822_parse_adrlist($headers['delivered-to'],'localhost');
		
		// [TODO] Can we count on this?
		$destinations = $aTo + $aCc + $aEnvelopeTo + $aXEnvelopeTo + $aDeliveredTo;

		// [TODO] Should this cache be at the class level?
		if(is_null($routing))
			$routing = DAO_Mail::getMailboxRouting();
		
		foreach($destinations as $destination) {
			if(empty($destination->mailbox) || empty($destination->host))
				continue;
			
			$address = $destination->mailbox.'@'.$destination->host;
			
			// Test each pattern successively
			foreach($routing as $route) { /* @var $route Model_MailRoute */
				$pattern = sprintf("/^%s$/i",
					str_replace(array('*'),array('.*?'),$route->pattern)
				);
				if(preg_match($pattern,$address)) 
					return $route->team_id;
			}
		}
		
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
