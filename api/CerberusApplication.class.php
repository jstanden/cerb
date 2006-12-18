<?php
define("CERBERUS_BUILD", 55);

include_once(UM_PATH . "/api/CerberusDAO.class.php");
include_once(UM_PATH . "/api/CerberusModel.class.php");
include_once(UM_PATH . "/api/CerberusExtension.class.php");

class CerberusApplication {
	
	private function CerberusApplication() {}
	
	static function getModules() {
		$modules = array();
		$extModules = UserMeetPlatform::getExtensions("com.cerberusweb.module");
		foreach($extModules as $mod) { /* @var $mod UserMeetExtensionManifest */
			$instance = $mod->createInstance(); /* @var $instance CerberusModuleExtension */
			if(is_a($instance,'usermeetextension') && $instance->isVisible())
				$modules[] = $instance;
		}
		return $modules;
	}
	
	static function setActiveModule($module=null) {
		static $activeModule;
		if(!is_null($module)) $activeModule = $module;
		return $activeModule;
	}
	
	static function getActiveModule() {
		return CerberusApplication::setActiveModule(); // returns
	}
	
	/**
	 * Takes a comma-separated value string and returns an array of tokens.
	 *
	 * @param string $string
	 * @return array
	 */
	static function parseCsvString($string) {
		$tokens = explode(',', $string);

		if(!is_array($tokens))
			return array();
		
		foreach($tokens as $k => $v) {
			$tokens[$k] = trim($v);
		}
		
		return $tokens;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return a unique ticket mask as a string
	 */
	static function generateTicketMask() {
		$letters = "ABCDEFGHIJKLMNPQRSTUVWXYZ";
		$numbers = "1234567890";
		$pattern = "LLL-NNNNN-NNN";
//		$pattern = "Y-M-D-LLLL";

		do {		
			// [JAS]: Seed randomness
			list($usec, $sec) = explode(' ', microtime());
			srand((float) $sec + ((float) $usec * 100000));
			
			$mask = "";
			$bytes = preg_split('//', $pattern, -1, PREG_SPLIT_NO_EMPTY);
			
			if(is_array($bytes))
			foreach($bytes as $byte) {
				switch(strtoupper($byte)) {
					case 'L':
						$mask .= substr($letters,rand(0,strlen($letters)-1),1);
						break;
					case 'N':
						$mask .= substr($numbers,rand(0,strlen($numbers)-1),1);
						break;
					case 'Y':
						$mask .= date('Y');
						break;
					case 'M':
						$mask .= date('n');
						break;
					case 'D':
						$mask .= date('j');
						break;
					default:
						$mask .= $byte;
						break;
				}
			}
		} while(null != CerberusTicketDAO::getTicketByMask($mask));
		
//		echo "Generated unique mask: ",$mask,"<BR>";
		
		return $mask;
	}
	
	static function generateMessageId() {
		$message_id = sprintf('<%s.%s@%s>', base_convert(time(), 10, 36), base_convert(rand(), 10, 36), !empty($_SERVER['HTTP_HOST']) ?  $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']);
		return $message_id;
	}
	
	// ***************** DUMMY
	static function getDashboardViewColumns() {
		return array(
			new CerberusDashboardViewColumn('t.mask','ID'),
			new CerberusDashboardViewColumn('t.status','Status'),
			new CerberusDashboardViewColumn('t.priority','Priority'),
			new CerberusDashboardViewColumn('t.last_wrote','Last Wrote'),
			new CerberusDashboardViewColumn('t.first_wrote','First Wrote'),
			new CerberusDashboardViewColumn('t.created_date','Created Date'),
			new CerberusDashboardViewColumn('t.updated_date','Updated Date'),
		);
	}
	
	// ***************** DUMMY
	
};

class CerberusParser {
	
	/**
	 * Enter description here...
	 * @param object $rfcMessage
	 * @return CerberusTicket ticket object
	 */
	static public function parseMessage($rfcMessage) {
		if (UM_DEBUG == 'true') {echo ('Entering parseMessage() with rfcMessage :<br>'); print_r ($rfcMessage); echo ('<hr>');}
		
		$continue = CerberusParser::parsePreRules($rfcMessage);
		if (false === $continue) return;
		
		$ticket = CerberusParser::parseToTicket($rfcMessage);
		
		CerberusParser::parsePostRules($ticket);
		
		return $ticket;
	}
	
	static public function parsePreRules(&$rfcMessage) {
		$continue_parsing = true;
		
		$mailRules = CerberusMailRuleDAO::getMailRules();
		
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
					if (UM_DEBUG == 'true') {echo ('criterionMatchesEmail() returned true<hr>');}
					if (!$require_all) {
						$perform_actions = true;
						break;
					}
				}
				else {
					if (UM_DEBUG == 'true') {echo ('criterionMatchesEmail() returned false<hr>');}
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
		
		$mailRules = CerberusMailRuleDAO::getMailRules();
		
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
		$fromAddressId = CerberusContactDAO::createAddress($fromAddress, $fromPersonal);

		if(is_array($to))
		foreach($to as $recipient) {
			$toAddress = $recipient->mailbox.'@'.$recipient->host;
			$toPersonal = $recipient->personal;
			$toAddressId = CerberusContactDAO::createAddress($toAddress,$toPersonal);
		}
		
		$sReferences = @$headers['references'];
		$sInReplyTo = @$headers['in-reply-to'];
		
		// [JAS] [TODO] References header may contain multiple message-ids to find
//		if(!empty($sReferences) || !empty($sInReplyTo)) {
		if(!empty($sInReplyTo)) {
//			$findMessageId = (!empty($sInReplyTo)) ? $sInReplyTo : $sReferences;
			$findMessageId = $sInReplyTo;
			$id = CerberusTicketDAO::getTicketByMessageId($findMessageId);
		}
		
		if(empty($id)) {
			$mailbox_id = CerberusParser::parseDestination($headers);
			$id = CerberusTicketDAO::createTicket($sMask,$sSubject,CerberusTicketStatus::OPEN,$mailbox_id,$fromAddress,$iDate);
		}
		
		// [JAS]: Add requesters to the ticket
		CerberusTicketDAO::createRequester($fromAddressId,$id);
		
		$attachments = array();
		$attachments['plaintext'] = '';
		$attachments['html'] = '';
		$attachments['files'] = array();
		
		if(is_array($rfcMessage->parts)) {
			CerberusParser::parseMimeParts($rfcMessage->parts,$attachments);
		} else {
			CerberusParser::parseMimePart($rfcMessage,$attachments);			
		}

		if(!empty($attachments)) {
			$message_id = CerberusTicketDAO::createMessage($id,CerberusMessageType::EMAIL,$iDate,$fromAddressId,$headers,$attachments['plaintext']);
		}
		foreach ($attachments['files'] as $filepath => $filename) {
			CerberusTicketDAO::createAttachment($message_id, $filename, $filepath);
		}
			
		$ticket = CerberusTicketDAO::getTicket($id);
		return $ticket;
	}
	
	static public function criterionMatchesEmail($criterion, $rfcMessage) { /* @var $criterion CerberusMailRuleCriterion */
		if (UM_DEBUG == 'true') {echo ('Entering criterionMatchesEmail() with criterion :<br>'); print_r ($criterion); echo ('<br>and message :<br>'); print_r($rfcMessage); echo ('<hr>');}
		
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
		if (UM_DEBUG == 'true') {echo ('Entering runMailRuleEmailActions() with mailRule id = ' . $id . ' and message:<br>'); print_r ($rfcMessage); echo ('<hr>');}
		return true;
	}
	
	static public function criterionMatchesTicket($criterion, $ticket) {
		if (UM_DEBUG == 'true') {echo ('Entering criterionMatchesTicket() with criterion :<br>'); print_r ($criterion); echo ('<br>and ticket :<br>'); print_r($ticket); echo ('<hr>');}
		
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
		if (UM_DEBUG == 'true') {echo ('Entering runMailRuleTicketActions() with mailRule id = ' . $id . ' and ticket :<br>'); print_r ($ticket); echo ('<hr>');}
		return true;
	}
	
	/**
	 * Enter description here...
	 *
	 * @todo
	 * @param array $headers
	 * @return integer
	 */
	static private function parseDestination($headers) {
		$addresses = array();
		
		// [TODO] The split could be handled by Mail_RFC822:parseAddressList (commas, semi-colons, etc.)

		$aTo = split(',', @$headers['to']);
		$aCc = split(',', @$headers['cc']);
		
		$destinations = $aTo + $aCc;
		
		foreach($destinations as $destination) {
			$structure = CerberusParser::parseRfcAddress($destination);
			
			if(empty($structure[0]->mailbox) || empty($structure[0]->host))
				continue;
			
			$address = $structure[0]->mailbox.'@'.$structure[0]->host;
				
			if(null != ($mailbox_id = CerberusContactDAO::getMailboxIdByAddress($address)))
				return $mailbox_id;
		}
		
		// envelope + delivered 'Delivered-To'
		// received
		
		// [TODO] catchall?
		
		return null;
	}
	
	static private function parseMimeParts($parts,&$attachments) {
		
		foreach($parts as $part) {
			CerberusParser::parseMimePart($part,$attachments);
		}
		
		return $attachments;
	}
	
	static private function parseMimePart($part,&$attachments) {
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
			// valid primary types are found at http://www.iana.org/assignments/media-types/
			$timestamp = gmdate('Y.m.d.H.i.s.', gmmktime());
			list($usec, $sec) = explode(' ', microtime());
			$timestamp .= substr($usec,2,3) . '.';
			if (false !== file_put_contents(UM_ATTACHMENT_SAVE_PATH . $timestamp . $fileName, $part->body)) {
				$attachments['files'][$timestamp.$fileName] = $fileName;
//				$attachments['plaintext'] .= ' Saved file <a href="' . UM_ATTACHMENT_ACCESS_PATH . $timestamp . $fileName . '">'
//											. (empty($fileName) ? 'Unnamed file' : $fileName) . '</a>. ';
			}
		}
	}
	
	static private function parseRfcAddress($address_string) {
		require_once(UM_PATH . '/libs/pear/Mail/RFC822.php');
		$structure = Mail_RFC822::parseAddressList($address_string, null, false);
		return $structure;
	}
	
};

class ToCriterion implements ICerberusCriterion {
	function getValue($rfcMessage) {
		return $rfcMessage->headers['to'];
	}
};
?>