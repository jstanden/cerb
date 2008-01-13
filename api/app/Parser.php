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
     *
     * @param string $file
     * @return CerberusParserMessage
     */
    static public function parseMimeFile($file) {
		$mime = mailparse_msg_parse_file($file);
		$message = self::_parseMimeFile($mime, $file);
		return $message;
    }
    
    /**
     * Enter description here...
     *
     * @param string $source
     * @return $filename
     */
    static public function saveMimeToFile($source, $path=null) {
    	if(empty($path))
    		$path = DEVBLOCKS_PATH . 'tmp' . DIRECTORY_SEPARATOR;
    	else
    		$path = realpath($path) . DIRECTORY_SEPARATOR;
    	
		do {
			$unique = sprintf("%s.%04d.msg",
				time(),
				rand(0,9999)
			);
			$filename = $path . $unique;
        } while(file_exists($filename));

          $fp = fopen($filename,'w');
          
          if($fp) {
              fwrite($fp,$source,strlen($source));
              @fclose($fp);
          }
          
		return $filename;
    }
    
    /**
     * Enter description here...
     *
     * @param resource $mime
     * @return CerberusParserMessage
     */
    static private function _parseMimeFile($mime, $full_filename) {
		$struct = mailparse_msg_get_structure($mime);
		$msginfo = mailparse_msg_get_part_data($mime);
		
		$message = new CerberusParserMessage();
		$message->headers = $msginfo['headers'];
		
		$settings = CerberusSettings::getInstance();
		$is_attachments_enabled = $settings->get(CerberusSettings::ATTACHMENTS_ENABLED,1);
		$attachments_max_size = $settings->get(CerberusSettings::ATTACHMENTS_MAX_SIZE,10);
		
		foreach($struct as $st) {
//		    echo "PART $st...<br>\r\n";

		    $section = mailparse_msg_get_part($mime, $st);
		    $info = mailparse_msg_get_part_data($section);
		    
		    // handle parts that shouldn't have a contact-name, don't handle twice
		    $handled = 0;
		    if(empty($info['content-name'])) {
		        if($info['content-type'] == 'text/plain') {
	            	@$message->body .= mailparse_msg_extract_part_file($section, $full_filename, NULL);
		            $handled = 1;
		            
		        } elseif($info['content-type'] == 'text/html') {
	        		@$message->htmlbody .= mailparse_msg_extract_part_file($section, $full_filename, NULL);
		            
		            // [TODO] Add the html part as an attachment
	                $tmpname = ParserFile::makeTempFilename();
	                $html_attach = new ParserFile();
	                $html_attach->setTempFile($tmpname,'text/html');
	                @file_put_contents($tmpname,$message->htmlbody);
	                $html_attach->file_size = filesize($tmpname);
	                $message->files["original_message.html"] = $html_attach;
	                unset($html_attach);
		            $handled = 1;
		            
		        } elseif($info['content-type'] == 'message/rfc822') {
					@$message_content = mailparse_msg_extract_part_file($section, $full_filename, NULL);
					
		        	$message_counter = empty($message_counter) ? 1 : $message_counter + 1;
	                $tmpname = ParserFile::makeTempFilename();
	                $html_attach = new ParserFile();
	                $html_attach->setTempFile($tmpname,'message/rfc822');
	                @file_put_contents($tmpname,$message_content);
	                $html_attach->file_size = filesize($tmpname);
	                $message->files['inline'.$message_counter.'.msg'] = $html_attach;
	                unset($html_attach);		        	 
		            $handled = 1;
		        }
		    }
		    
		    // whether or not it has a content-name, we need to add it as an attachment (if not already handled)
		    if ($handled == 0 && isset($info['content-disposition'])) {
		        switch($info['content-disposition']) {
		            case 'inline':
		            case 'attachment':
		                if(!$is_attachments_enabled) {
		                    break; // skip attachment
		                }
					    $attach = new ParseCronFileBuffer($section, $info, $full_filename);
		                
					    // [TODO] This could be more efficient by not even saving in the first place above:
	                    // Make sure our attachment is under the max preferred size
					    if(filesize($attach->tmpname) > ($attachments_max_size * 1024000)) {
					        @unlink($attach->tmpname);
					        break;
					    }
					    
					    // if un-named, call it "unnamed message part"
					    if (!$info['content-name']) { $info['content-name'] = 'unnamed message part'; }
	
					    // content-name is not necessarily unique...
						if (isset($message->files[$info['content-name']])) {
							$j=1;
							while ($message->files[$info['content-name'] . '(' . $j . ')']) {
								$j++;
							}
							$info['content-name'] = $info['content-name'] . '(' . $j . ')';
						}
						$message->files[$info['content-name']] = $attach;
					    
		                break;
		                
		            default: // default?
		                break;
		        }
		    }
		}
		
		mailparse_msg_free($mime);
		
		return $message;
    }
    
	/**
	 * Enter description here...
	 *
	 * @param CerberusParserMessage $message
	 * @return integer
	 */
	static public function parseMessage(CerberusParserMessage $message, $options=array()) {
//		print_r($rfcMessage);
		
		/*
		 * options:
		 * 'no_autoreply'
		 */

		$settings = CerberusSettings::getInstance();
		
		$headers =& $message->headers;

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
			return NULL; // [TODO] Log
		
		@$fromAddress = $from[0]->mailbox.'@'.$from[0]->host;
		@$fromPersonal = $from[0]->personal;
		if(null == ($fromAddressInst = CerberusApplication::hashLookupAddress($fromAddress, true))) {
			return NULL; // [TODO] Log
		} else {
			$fromAddressId = $fromAddressInst->id;
		}

		// Imports
        @$importNew = $headers['x-cerberusnew'];
        @$importAppend = $headers['x-cerberusappendto'];
		
		// Message Id / References / In-Reply-To
		@$sMessageId = $headers['message-id'];
		
		$helpdesk_senders = CerberusApplication::getHelpdeskSenders();
		
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
            
        // Not importing
        } else {
			// Is this from the helpdesk to itself?  If so, bail out
			if(isset($helpdesk_senders[$fromAddressInst->email])) {
				return NULL; // [TODO] Log
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
					
					//[mdf] commented out this block because the text of such messages seems to already
					//be included in the message because of code in cron.classes.php
					//this probably does it better, but for now isn't needed because
					//it results in redundant inclusion of the attached text from forwards and bounces
					// [TODO] Make sure the body of forwards/bounces are formatted clearly properly
//					foreach($struct as $st) {
//						$section = mailparse_msg_get_part($mail, $st);
//						$info = mailparse_msg_get_part_data($section);
//						if(empty($info['content-name'])) {
//							if($info['content-type'] == 'text/plain') {
//								$str = '';								
//								if(sizeof($body_append_text) == 0 ) {
//									foreach($inline_headers as $in_header_key=>$in_header_val) {
//										$str.=$in_header_key.': '. $in_header_val . "\r\n";
//									}
//								}
//								$str .= @mailparse_msg_extract_part_file($section, $full_filename, NULL);
//								$body_append_text[] = $str;
//							}
//							elseif($info['content-type'] == 'text/html') {
//								$str = '';								
//								if(sizeof($body_append_html) == 0 ) {
//									foreach($inline_headers as $in_header_key=>$in_header_val) {
//										$str.=$in_header_key.': '. $in_header_val . "\r\n";
//									}
//								}
//								$str .= "\r\n";
//								$str .= @mailparse_msg_extract_part_file($section, $full_filename, NULL);
//								$body_append_html[] = @mailparse_msg_extract_part_file($section, $full_filename, NULL);
//							}
//						}						
//					}


				break;
			}
		}
        
		// [JAS] [TODO] References header may contain multiple message-ids to find
		if(empty($importNew) && empty($importAppend) && null != ($ids = self::findParentMessage($headers))) {
        	$bIsNew = false;
        	$id = $ids['ticket_id'];
        	$msgid = $ids['message_id'];

        	// Is it a worker reply from an external client?  If so, proxy
        	if(null != ($worker_address = DAO_AddressToWorker::getByAddress($fromAddress))) {
//        		echo "Proxying reply to ticket $id for $fromAddress<br>";

				// Watcher Commands [TODO] Document on wiki/etc
				if(0 != ($matches = @preg_match("/^\[(.*?)\]$/i",$message->headers['subject'],$commands))) {
					@$command = strtolower($commands[1]);
					switch($command) {
						case 'close':
							DAO_Ticket::updateTicket($id,array(
								DAO_Ticket::IS_CLOSED => CerberusTicketStatus::CLOSED
							));
							break;
						case 'take':
							DAO_Ticket::updateTicket($id,array(
								DAO_Ticket::NEXT_WORKER_ID => $worker_address->worker_id
							));
							return $id;
							break;
						default:
							// Typo?
							break;
					}
				}

        		CerberusMail::sendTicketMessage(array(
					'message_id' => $msgid,
					'content' => $message->body,
					//'files' => $message->files, // [TODO] Proxy attachments 	
					'agent_id' => $worker_address->worker_id,
				));
				
        		return $id;
        	}
        }
        
		if(empty($id)) { // New Ticket
			// Are we delivering or bouncing?
			@list($team_id,$matchingToAddress) = CerberusParser::findDestination($headers);
			
			if(empty($team_id)) {
				// Bounce
				return null;
			}
			
			if(empty($sMask))
				$sMask = CerberusApplication::generateTicketMask();
			
			// Is this address covered by an SLA?
			$sla_id = 0;
			$sla_priority = 0;
			if(!empty($fromAddressInst->sla_id)) {
				if(null != ($fromAddressSla = DAO_Sla::get($fromAddressInst->sla_id))) {
					@$sla_id = $fromAddressSla->id;
					@$sla_priority = $fromAddressSla->priority;
				}
			}
				
			$fields = array(
				DAO_Ticket::MASK => $sMask,
				DAO_Ticket::SUBJECT => $sSubject,
				DAO_Ticket::IS_CLOSED => $iClosed,
				DAO_Ticket::FIRST_WROTE_ID => intval($fromAddressId),
				DAO_Ticket::LAST_WROTE_ID => intval($fromAddressId),
				DAO_Ticket::CREATED_DATE => $iDate,
				DAO_Ticket::UPDATED_DATE => $iDate,
				DAO_Ticket::TEAM_ID => intval($team_id),
				DAO_Ticket::LAST_ACTION_CODE => CerberusTicketActionCode::TICKET_OPENED,
				DAO_Ticket::SLA_ID => $sla_id,
				DAO_Ticket::SLA_PRIORITY => $sla_priority,
			);
			$id = DAO_Ticket::createTicket($fields);

			// Don't replace this with the master event listener
			if(false !== ($rule = CerberusApplication::parseTeamRules($team_id, $id, $fromAddress, $sSubject))) { /* @var $rule Model_TeamRoutingRule */
                //Assume our rule match is not spam
                if(empty($rule->do_spam)) { // if we didn't already train
                    $enumSpamTraining = CerberusTicketSpamTraining::NOT_SPAM;
                }
			}
		}

		// [JAS]: Add requesters to the ticket
	    // [TODO] Make sure they aren't a worker
		if(!empty($fromAddressId) && !empty($id)) {
			DAO_Ticket::createRequester($fromAddressId,$id);
		}
	    
		// Add the other TO/CC addresses to the ticket
		// [TODO] This should be cleaned up and optimized
		if($settings->get(CerberusSettings::PARSER_AUTO_REQ,0)) {
			@$autoreq_exclude_list = $settings->get(CerberusSettings::PARSER_AUTO_REQ_EXCLUDE,'');
			$destinations = self::getDestinations($headers);
			
			if(is_array($destinations) && !empty($destinations)) {
				
				// Filter out any excluded requesters
				if(!empty($autoreq_exclude_list)) {
					@$autoreq_exclude = DevblocksPlatform::parseCrlfString($autoreq_exclude_list);
					
					if(is_array($autoreq_exclude) && !empty($autoreq_exclude))
					foreach($autoreq_exclude as $excl_pattern) {
						// [TODO] DevblocksPlatform::parseStringAsRegexp();
						$excl_regexp = DevblocksPlatform::parseStringAsRegExp($excl_pattern);
						
						// Check all destinations for this pattern
						foreach($destinations as $idx => $dest) {
							if(@preg_match($excl_regexp, $dest)) {
								unset($destinations[$idx]);
							}
						}
					}
				}
				
				foreach($destinations as $dest) {
					if(null != ($destInst = CerberusApplication::hashLookupAddress($dest, true))) {
						// Skip if the destination is one of our senders or the matching TO
						if(isset($helpdesk_senders[$destInst->email]) || 0 == strcasecmp($matchingToAddress,$destInst->email))
							continue;
					 	
						DAO_Ticket::createRequester($destInst->id,$id);
					}
				}
			}
		}
		
		$attachment_path = APP_PATH . '/storage/attachments/'; // [TODO] This should allow external attachments (S3)
		
		if(empty($message->body) && !empty($message->htmlbody)) { // generate the plaintext part
	        $fields = array(
	            DAO_Message::TICKET_ID => $id,
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
	            DAO_Message::CREATED_DATE => $iDate,
	            DAO_Message::ADDRESS_ID => $fromAddressId
	        );
			$email_id = DAO_Message::create($fields);
			
			$body = $message->body;

			// Content
			DAO_MessageContent::update($email_id, $body);
			
			// Headers
			foreach($headers as $hk => $hv) {
			    DAO_MessageHeader::update($email_id, $id, $hk, $hv);
			}
		}
		
		// [mdf] Loop through files to insert attachment records in the db, and move temporary files
		if(!empty($email_id)) {
			foreach ($message->files as $filename => $file) { /* @var $file ParserFile */
				//[mdf] skip rfc822 messages since we extracted their content above
				if($file->mime_type == 'message/rfc822') {
					continue;
				}
				
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

		// New ticket processing
		if($bIsNew) {
			static $group_settings = null;
			if(null == $group_settings)
				$group_settings = DAO_GroupSettings::getSettings();
			
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
			    	@$spam_threshold = $group_settings[$team_id][DAO_GroupSettings::SETTING_SPAM_THRESHOLD];
			        @$spam_action = $group_settings[$team_id][DAO_GroupSettings::SETTING_SPAM_ACTION];
			        @$spam_action_param = $group_settings[$team_id][DAO_GroupSettings::SETTING_SPAM_ACTION_PARAM];
			        
				    if($out['probability']*100 >= $spam_threshold) {
				    	$enumSpamTraining = CerberusTicketSpamTraining::SPAM;
				    	
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
								$buckets = DAO_Bucket::getAll();
								
								// Verify bucket exists
	                            if(!empty($spam_action_param) && isset($buckets[$spam_action_param])) {
		                            DAO_Ticket::updateTicket($id,array(
		                                DAO_Ticket::TEAM_ID => $team_id,
		                                DAO_Ticket::CATEGORY_ID => $spam_action_param
		                            ));
	                            }	                            
				                break;
				        }
				    }
			    }
			} // end spam training

			// Auto reply
			@$autoreply_enabled = $group_settings[$team_id][DAO_GroupSettings::SETTING_AUTO_REPLY_ENABLED];
			@$autoreply = $group_settings[$team_id][DAO_GroupSettings::SETTING_AUTO_REPLY];
			
			/*
			 * Send the group's autoreply if one exists, as long as this ticket isn't spam and
			 * we aren't importing this message.
			 */
			if(!isset($options['no_autoreply'])
				&& $autoreply_enabled 
				&& !empty($autoreply) 
				&& $enumSpamTraining != CerberusTicketSpamTraining::SPAM
				&& (empty($importNew) && empty($importAppend))) {
					CerberusMail::sendTicketMessage(array(
						'ticket_id' => $id,
						'message_id' => $email_id,
						'content' => str_replace(
				        	array('#mask#','#subject#','#sender#'), // ,'#group#','#bucket#'
				        	array($sMask, $sSubject, $fromAddress),
				        	$autoreply
						),
						'is_autoreply' => true,
						'dont_keep_copy' => true
					));
			}
			
		} // end bIsNew
		
		unset($message);
		
		// Re-open and update our date on new replies
		if(!$bIsNew && empty($importAppend)) {
			DAO_Ticket::updateTicket($id,array(
			    DAO_Ticket::UPDATED_DATE => time(),
			    DAO_Ticket::IS_WAITING => 0,
			    DAO_Ticket::IS_CLOSED => 0,
			    DAO_Ticket::IS_DELETED => 0,
			    DAO_Ticket::LAST_WROTE_ID => $fromAddressId,
			    DAO_Ticket::LAST_ACTION_CODE => CerberusTicketActionCode::TICKET_CUSTOMER_REPLY,
			));
			
			// [TODO] The TICKET_CUSTOMER_REPLY should be sure of this message address not being a worker
		}
		
		// Inbound Reply Event
	    $eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'ticket.reply.inbound',
                array(
                    'ticket_id' => $id,
                    'message_id' => $email_id,
                )
            )
	    );
		
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
	
	static public function getDestinations($headers) {
		$aTo = array();
		$aCc = array();
		$aEnvelopeTo = array();
		$aXEnvelopeTo = array();
		$aDeliveredTo = array();
		
		$aTo = imap_rfc822_parse_adrlist(@$headers['to'],'localhost');
		
		if(isset($headers['cc'])) {
			$aCc = imap_rfc822_parse_adrlist($headers['cc'],'localhost');
			if(!is_array($aCc)) $aCc = array($aCc);
		}
		if(isset($headers['envelope-to'])) {
			$aEnvelopeTo = imap_rfc822_parse_adrlist(@$headers['envelope-to'],'localhost');
			if(!is_array($aEnvelopeTo)) $aEnvelopeTo = array($aEnvelopeTo);
		}
		if(isset($headers['x-envelope-to'])) {
			$aXEnvelopeTo = imap_rfc822_parse_adrlist($headers['x-envelope-to'],'localhost');
			if(!is_array($aXEnvelopeTo)) $aXEnvelopeTo = array($aXEnvelopeTo);
		}
		if(isset($headers['delivered-to'])) {
			$aDeliveredTo = imap_rfc822_parse_adrlist($headers['delivered-to'],'localhost');
			if(!is_array($aDeliveredTo)) $aDeliveredTo = array($aDeliveredTo);
		}
		
		$d = array_merge($aTo, $aCc, $aEnvelopeTo, $aXEnvelopeTo, $aDeliveredTo);
		
		$addresses = array();
		
		if(is_array($d))
		foreach($d as $destination) {
			if(empty($destination->mailbox) || empty($destination->host))
				continue;
			
			$addresses[] = $destination->mailbox.'@'.$destination->host;
		}
		
		return $addresses;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param array $headers
	 * @return array (group_id,address)
	 */
	static private function findDestination($headers) {
		static $routing = null;
		
		$settings = CerberusSettings::getInstance();

		// [TODO] Should this cache be at the class level?
		if(is_null($routing))
			$routing = DAO_Mail::getMailboxRouting();
		
		$destinations = self::getDestinations($headers);
		if(is_array($destinations))
		foreach($destinations as $address) {
			// Test each pattern successively
			foreach($routing as $route) { /* @var $route Model_MailRoute */
				$pattern = sprintf("/^%s$/i",
					str_replace(array('*'),array('.*?'),$route->pattern)
				);
				if(preg_match($pattern,$address)) 
					return array($route->team_id,$address);
			}
		}
		
		// Check if we have a default mailbox configured before returning NULL.		
		$default_team_id = $settings->get(CerberusSettings::DEFAULT_TEAM_ID,0);
		
		if(!empty($default_team_id)) { // catchall
			return array($default_team_id,'');
		}
		
		return null; // bounce
	}
	
	// [TODO] Phase out in favor of the CerberusUtils class
	static function parseRfcAddress($address_string) {
		return CerberusUtils::parseRfcAddressList($address_string);
	}
	
	static private function fixQuotePrintableString($str) {
		$out = imap_utf8($str);		
		return $out;
	}

	
};
?>
