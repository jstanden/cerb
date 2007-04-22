<?php
class CerberusParser {
	
	/**
	 * Enter description here...
	 * @param object $rfcMessage
	 * @return CerberusTicket ticket object
	 */
	static public function parseMessage($rfcMessage) {
		if (DEVBLOCKS_DEBUG == 'true') {echo ('Entering parseMessage() with rfcMessage :<br>'); print_r ($rfcMessage); echo ('<hr>');}
		
		$continue = CerberusParser::parsePreRules($rfcMessage);
		if (false === $continue) return;
		
		$ticket = CerberusParser::parseToTicket($rfcMessage);
		
		CerberusParser::parsePostRules($ticket);
		
		return $ticket;
	}
	
	static public function parsePreRules(&$rfcMessage) {
		$continue_parsing = true;
		
		$mailRules = DAO_MailRule::getMailRules();
		
		foreach ($mailRules as $mailRule) { /* @var $mailRule CerberusMailRule */
			// break if any of the rules told us to stop parsing
			if (false === $continue_parsing) break;
			
			// here we only want pre-parse rules
			if (0 != strcmp('PRE',$mailRule->sequence)) continue;
			
			// check whether all or any of the criteria have to match the message
			if (0 == strcmp('ALL',$mailRule->strictness)) {
				$require_all = true;
				$perform_actions = true;
			} else {
				$require_all = false;
				$perform_actions = false;
			}
			
			// parse the rule's criteria and perform actions if conditions match requirements
			foreach ($mailRule->criteria as $criterion) { /* @var $criterion CerberusMailRuleCriterion */
				if (CerberusParser::criterionMatchesEmail($criterion, $rfcMessage)) {
					if (DEVBLOCKS_DEBUG == 'true') {echo ('criterionMatchesEmail() returned true<hr>');}
					if (!$require_all) {
						$perform_actions = true;
						break;
					}
				}
				else {
					if (DEVBLOCKS_DEBUG == 'true') {echo ('criterionMatchesEmail() returned false<hr>');}
					if ($require_all) {
						$perform_actions = false;
						break;
					}
				}
			}
			
			if ($perform_actions === true)
				$continue_parsing = CerberusParser::runMailRuleEmailActions($mailRule->id, $rfcMessage);
		}
		
		return $continue_parsing;
	}
	
	static public function parsePostRules(&$ticket) {
		$continue_parsing = true;
		
		$mailRules = DAO_MailRule::getMailRules();
		
		foreach ($mailRules as $mailRule) { /* @var $mailRule CerberusMailRule */
			// break if any of the rules told us to stop parsing
			if (false === $continue_parsing) break;
			
			// here we only want post-parse rules
			if (0 != strcmp('POST',$mailRule->sequence)) continue;
			
			// check whether all or any of the criteria have to match the message
			if (0 == strcmp('ALL',$mailRule->strictness)) {
				$require_all = true;
				$perform_actions = true;
			} else {
				$require_all = false;
				$perform_actions = false;
			}
			
			// parse the rule's criteria and perform actions if conditions match requirements
			foreach ($mailRule->criteria as $criterion) { /* @var $criterion CerberusMailRuleCriterion */
				if (CerberusParser::criterionMatchesTicket($criterion, $ticket))
					if (!$require_all) {
						$perform_actions = true;
						break;
					}
				else
					if ($require_all) {
						$perform_actions = false;
						break;
					}
			}
			
			if ($perform_actions === true)
				$continue_parsing = CerberusParser::runMailRuleTicketActions($mailRule->id, $ticket);
		}
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
		$iDate = strtotime(@$headers['date']);
		if(empty($iDate)) $iDate = gmmktime();
		
		// Message Id / References / In-Reply-To
//		echo "Parsing message-id: ",@$headers['message-id'],"<BR>\r\n";

		if(empty($from) || !is_array($from))
			return false;
		
		$fromAddress = $from[0]->mailbox.'@'.$from[0]->host;
		$fromPersonal = $from[0]->personal;
		$fromAddressId = DAO_Contact::createAddress($fromAddress, $fromPersonal);

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
			$wrote_id = DAO_Contact::lookupAddress($fromAddress, true);
			
			$fields = array(
				DAO_Ticket::MASK => $sMask,
				DAO_Ticket::SUBJECT => $sSubject,
				DAO_Ticket::STATUS => CerberusTicketStatus::OPEN,
				DAO_Ticket::FIRST_WROTE_ID => $wrote_id,
				DAO_Ticket::LAST_WROTE_ID => $wrote_id,
				DAO_Ticket::CREATED_DATE => $iDate,
				DAO_Ticket::UPDATED_DATE => $iDate,
				DAO_Ticket::TEAM_ID => $team_id
			);
			$id = DAO_Ticket::createTicket($fields);
		}
		
		// [JAS]: Add requesters to the ticket
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

			if(!empty($attachments['html'])) {
				$message_id = DAO_Ticket::createMessage($id,CerberusMessageType::EMAIL,$iDate,$fromAddressId,$headers,$attachments['plaintext']);
			}
			
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
	
	static public function criterionMatchesEmail($criterion, $rfcMessage) { /* @var $criterion CerberusMailRuleCriterion */
		if (DEVBLOCKS_DEBUG == 'true') {echo ('Entering criterionMatchesEmail() with criterion :<br>'); print_r ($criterion); echo ('<br>and message :<br>'); print_r($rfcMessage); echo ('<hr>');}
		
		switch ($criterion->operator) {
			case 'equals':
				if ($rfcMessage->$$criterion->field == $$criterion->value) return true;
				break;
			case 'not-equals':
				if ($rfcMessage->$$criterion->field != $$criterion->value) return true;
				break;
			case 'less-than':
				if ($rfcMessage->$$criterion->field < $$criterion->value) return true;
				break;
			case 'not-less-than':
				if ($rfcMessage->$$criterion->field >= $$criterion->value) return true;
				break;
			case 'greater-than':
				if ($rfcMessage->$$criterion->field > $$criterion->value) return true;
				break;
			case 'not-greater-than':
				if ($rfcMessage->$$criterion->field <= $$criterion->value) return true;
				break;
			case 'regex':
				if (true) return true; // [TODO]: um... figure out how to do this...
				break;
			case 'match':
//				if (strpos($rfcMessage->{$criterion->field}, $criterion->value) !== false) return true;
				if (strpos($rfcMessage->headers['subject'], $criterion->value) !== false) return true;
				break;
			case 'not-match':
				if (strpos($rfcMessage->$$criterion->field, $$criterion->value) === false) return true;
				break;
		}
		return false;
	}
	
	static public function runMailRuleEmailActions($id, &$rfcMessage) {
		if (DEVBLOCKS_DEBUG == 'true') {echo ('Entering runMailRuleEmailActions() with mailRule id = ' . $id . ' and message:<br>'); print_r ($rfcMessage); echo ('<hr>');}
		return true;
	}
	
	static public function criterionMatchesTicket($criterion, $ticket) {
		if (DEVBLOCKS_DEBUG == 'true') {echo ('Entering criterionMatchesTicket() with criterion :<br>'); print_r ($criterion); echo ('<br>and ticket :<br>'); print_r($ticket); echo ('<hr>');}
		
		switch ($criterion->operator) {
			case 'equals':
				if ($ticket->$$criterion->field == $$criterion->value) return true;
				break;
			case 'not-equals':
				if ($ticket->$$criterion->field != $$criterion->value) return true;
				break;
			case 'less-than':
				if ($ticket->$$criterion->field < $$criterion->value) return true;
				break;
			case 'not-less-than':
				if ($ticket->$$criterion->field >= $$criterion->value) return true;
				break;
			case 'greater-than':
				if ($ticket->$$criterion->field > $$criterion->value) return true;
				break;
			case 'not-greater-than':
				if ($ticket->$$criterion->field <= $$criterion->value) return true;
				break;
			case 'regex':
				if (true) return true; // [TODO]: um... figure out how to do this...
				break;
			case 'match':
				if (strpos($ticket->$$criterion->field, $$criterion->value) !== false) return true;
				break;
			case 'not-match':
				if (strpos($ticket->$$criterion->field, $$criterion->value) === false) return true;
				break;
		}
		return false;
	}
	
	static public function runMailRuleTicketActions($id, &$ticket) {
		if (DEVBLOCKS_DEBUG == 'true') {echo ('Entering runMailRuleTicketActions() with mailRule id = ' . $id . ' and ticket :<br>'); print_r ($ticket); echo ('<hr>');}
		return true;
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
			CerberusParser::parseMimeParts($part);
			
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
