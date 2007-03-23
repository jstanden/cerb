<?php
define("APP_BUILD", 99);

include_once(APP_PATH . "/api/DAO.class.php");
include_once(APP_PATH . "/api/Model.class.php");
include_once(APP_PATH . "/api/Extension.class.php");

class CerberusApplication extends DevblocksApplication {
	
	static function writeDefaultHttpResponse($response) {
		$path = $response->path;

		// [JAS]: Ajax?
		if(empty($path))
			return;

		$tpl = DevblocksPlatform::getTemplateService();
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		$mapping = DevblocksPlatform::getMappingRegistry();
		@$extension_id = $mapping[$path[0]];
		
		if(empty($visit))
			$extension_id = 'core.module.signin';
		
		if(empty($extension_id)) 
			$extension_id = 'core.module.tickets';
	
		$modules = CerberusApplication::getModules();
		$tpl->assign('modules',$modules);		
		
		$pageManifest = DevblocksPlatform::getExtension($extension_id);
		$page = $pageManifest->createInstance();
		$tpl->assign('module',$page);
		
		$settings = CerberusSettings::getInstance();
		$tpl->assign('settings', $settings);
				
		$tpl->assign('session', $_SESSION);
		$tpl->assign('visit', $visit);
		
		$translate = DevblocksPlatform::getTranslationService();
		$tpl->assign('translate', $translate);
		
		$tpl->display('border.php');
	}
	
	static function getModules() {
		$modules = array();
		$extModules = DevblocksPlatform::getExtensions("cerberusweb.module");
		foreach($extModules as $mod) { /* @var $mod DevblocksExtensionManifest */
			$instance = $mod->createInstance(); /* @var $instance CerberusModuleExtension */
			if(is_a($instance,'devblocksextension') && $instance->isVisible())
				$modules[] = $instance;
		}
		return $modules;
	}	
	
	// [TODO] Move to a FormHelper service?
	static function parseCrlfString($string) {
		// Make linefeeds uniform (CR to LF)
//		$string = str_replace("\r","\n",$string);
		
		// Condense repeat LF into a single LF
//		$string = preg_replace('#\n+#', '\n', $string);
		
		// 
		$parts = split("[\r\n]", $string);
		
		// Remove any empty tokens
		foreach($parts as $idx => $part) {
			$parts[$idx] = trim($part);
			if(empty($parts[$idx])) 
				unset($parts[$idx]);
		}
		
		return $parts;
	}
	
	/**
	 * Takes a comma-separated value string and returns an array of tokens.
	 * [TODO] Move to a FormHelper service?
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
	static function generateTicketMask($pattern = "LLL-NNNNN-NNN") {
		$letters = "ABCDEFGHIJKLMNPQRSTUVWXYZ";
		$numbers = "1234567890";
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
	
	// TODO: may need to also have an agent_id passed to it in the request, to identify the agent making the reply
	function sendMessage($type) {
		// variable loading
		@$id		= DevblocksPlatform::importGPC($_REQUEST['id']); // message id
		@$content	= DevblocksPlatform::importGPC($_REQUEST['content']);
		@$priority	= DevblocksPlatform::importGPC($_REQUEST['priority']);	// DDH: TODO: if priority and/or status change, we need to update the
		@$status	= DevblocksPlatform::importGPC($_REQUEST['status']);		// ticket object.  not sure if we want to do that here or not.
		@$agent_id	= DevblocksPlatform::importGPC($_REQUEST['agent_id']);
		
		// object loading
		$message	= CerberusTicketDAO::getMessage($id);
		$ticket_id	= $message->ticket_id;
		$ticket		= CerberusTicketDAO::getTicket($ticket_id);
		$mailbox	= CerberusMailDAO::getMailbox($ticket->mailbox_id);
		$requesters	= CerberusTicketDAO::getRequestersByTicket($ticket_id);
		$mailMgr	= DevblocksPlatform::getMailService();
		$headers	= CerberusMailDAO::getHeaders($type, $ticket_id);
		
		$files = $_FILES['attachment'];
		// send email (if necessary)
		if ($type != CerberusMessageType::COMMENT) {
			// build MIME message if message has attachments
			if (is_array($files) && !empty($files)) {
				
				/*
				 * [JAS]: [TODO] If we're going to call platform libs directly we should just have
				 * the platform provide the functionality.
				 */
				require_once(APP_PATH . '/libs/devblocks/pear/mime.php');
				$mime_mail = new Mail_mime();
				$mime_mail->setTXTBody($content);
				foreach ($files['tmp_name'] as $idx => $file) {
					$mime_mail->addAttachment($files['tmp_name'][$idx], $files['type'][$idx], $files['name'][$idx]);
				}
				
				$email_body = $mime_mail->get();
				$email_headers = $mime_mail->headers($headers);
			} else {
				$email_body = $content;
				$email_headers = $headers;
			}
			
			$mail_result =& $mailMgr->send('mail.webgroupmedia.com', $headers['x-rcpt'], $email_headers, $email_body); // DDH: TODO: this needs to pull the servername from a config, not hardcoded.
			if ($mail_result !== true) die("Error message was: " . $mail_result->getMessage());
		}
		
		// TODO: create DAO object for Agent, be able to pull address by having agent id.
//		$headers['From'] = $agent_address->personal . ' <' . $agent_address->email . '>';
//		$message_id = CerberusTicketDAO::createMessage($ticket_id,$type,gmmktime(),$agent_id,$headers,$content);
		$message_id = CerberusTicketDAO::createMessage($ticket_id,$type,gmmktime(),1,$headers,$content);
		
		// if this message was submitted with attachments, store them in the filestore and link them in the db.
		if (is_array($files) && !empty($files)) {
			foreach ($files['tmp_name'] as $idx => $file) {
				$timestamp = gmdate('Y.m.d.H.i.s.', gmmktime());
				list($usec, $sec) = explode(' ', microtime());
				$timestamp .= substr($usec,2,3) . '.';
				copy($files['tmp_name'][$idx],DEVBLOCKS_ATTACHMENT_SAVE_PATH . $timestamp . $files['name'][$idx]);
				CerberusTicketDAO::createAttachment($message_id, $files['name'][$idx], $timestamp . $files['name'][$idx]);
			}
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('display',$id)));
	}
	
	// ***************** DUMMY [TODO] Move to Model?  Combine with search fields?
	// [JAS]: [TODO] Translate
	static function getDashboardViewColumns() {
		return array(
			new CerberusDashboardViewColumn(CerberusSearchFields::TICKET_MASK,'ID'),
			new CerberusDashboardViewColumn(CerberusSearchFields::TICKET_STATUS,'Status'),
			new CerberusDashboardViewColumn(CerberusSearchFields::TICKET_PRIORITY,'Priority'),
			new CerberusDashboardViewColumn(CerberusSearchFields::TICKET_LAST_WROTE,'Last Wrote'),
			new CerberusDashboardViewColumn(CerberusSearchFields::TICKET_FIRST_WROTE,'First Wrote'),
			new CerberusDashboardViewColumn(CerberusSearchFields::TICKET_CREATED_DATE,'Created Date'),
			new CerberusDashboardViewColumn(CerberusSearchFields::TICKET_UPDATED_DATE,'Updated Date'),
			new CerberusDashboardViewColumn(CerberusSearchFields::TICKET_SPAM_SCORE,'Spam Score'),
			new CerberusDashboardViewColumn(CerberusSearchFields::MAILBOX_NAME,'Mailbox'),
		);
	}
	// ***************** DUMMY
	
};

class CerberusBayes {
	const PROBABILITY_CEILING = 0.9999;
	const PROBABILITY_FLOOR = 0.0001;
	const PROBABILITY_UNKNOWN = 0.4;
	const PROBABILITY_MEDIAN = 0.5;
	const MAX_INTERESTING_WORDS = 15;
	
	/**
	 * @param string $text A string of text to break into unique words
	 * @param integer $min The minimum word length used
	 * @param integer $max The maximum word length used
	 * @return array An array with unique words as keys
	 */
	static function parseUniqueWords($text,$min=3,$max=24) {
		$chars = array('\'');
		$tokens = array('__apos__');
		
		// Encode apostrophes/etc
		$text = str_replace($chars,$tokens,$text);
		
		// Force lowercase and strip non-word punctuation (a-z, 0-9, _)
		$text = preg_replace('#\W+#', ' ', strtolower($text));

		// Decode apostrophes/etc
		$text = str_replace($tokens,$chars,$text);
				
		// Sort unique words w/ condensed spaces
		$words = array_flip(explode(' ', preg_replace('#\s+#', ' ', $text)));
		
		// Toss anything over/under the word length bounds
		foreach($words as $k => $v) {
			$len = strlen($k);
			if($len < $min || $len > $max || is_numeric($k)) { // [TODO]: Make decision on !numeric?
				unset($words[$k]); // toss
			}
		}
		
		return $words;
	}
	
	/**
	 * @param string $text A string of text to run through spam scoring
	 * @return array Analyzed statistics
	 */
	static function processText($text) {
		$words = self::parseUniqueWords($text);
		$words = self::_lookupWordIds($words);
		$words = self::_analyze($words);
		return $words; 
	}
	
	static function markTicketAsSpam($ticket_id) {
		self::_markTicketAs($ticket_id, true);
	}
	
	static function markTicketAsNotSpam($ticket_id) {
		self::_markTicketAs($ticket_id, false);
	}
	
	static private function _markTicketAs($ticket_id,$spam=true) {
		// pull up text of first ticket message
		@list($message_id, $first_message) = each(array_shift(CerberusTicketDAO::getMessagesByTicket($ticket_id))); /* @var $first_message CerberusMessage */
		if(!is_a($first_message,'CerberusMessage')) return FALSE;
		
		// Pass text to analyze() to get back interesting words
		$content = $first_message->getContent();
		$words = self::processText($content);
		
		// Train interesting words as spam/notspam
		$out = self::_calculateSpamProbability($words);
		self::_trainWords($out['words'],$spam);
		
		// Increase the bayes_stats spam or notspam total count by 1
		if($spam) DAO_Bayes::addOneToSpamTotal(); else DAO_Bayes::addOneToNonSpamTotal();
		
		// Forced training should leave a cache of 0.0001 or 0.9999 on the ticket table
		$fields = array(
			'spam_score' => ($spam) ? 0.9999 : 0.0001,
			'spam_training' => ($spam) ? 'S' : 'N'
		);
		CerberusTicketDAO::updateTicket($ticket_id,$fields);
	}

	/**
	 * @param CerberusBayesWord[] $words
	 * @param boolean $spam
	 */
	static private function _trainWords($words, $spam=true) {
		if(is_array($words))
		foreach($words as $word) { /* @var $word CerberusBayesWord */
			if($spam) DAO_Bayes::addOneToSpamWord($word->id); 
				else DAO_Bayes::addOneToNonSpamWord($word->id);  
		}
	}
	
	/**
	 * @param array $words An array indexed with words to look up 
	 */
	static private function _lookupWordIds($words) {
		$pos = 0;
		$batch_size = 10;
		$outwords = array(); // 
				
		while(array() != ($batch = array_slice($words,$pos,$batch_size,true))) {
			$batch = array_keys($batch); // words are now values
			$word_ids = DAO_Bayes::lookupWordIds($batch);
			$outwords = array_merge($outwords, $word_ids);
			$pos += $batch_size;
		}
		return $outwords;
	}
	
	static private function _analyze($words) {
		foreach($words as $k => $w) {
			$words[$k]->probability = self::_calculateWordProbability($w);
			
			// [JAS]: If a word appears more than 5 times (counting weight) in the corpus, use it.  Otherwise discard.
			if(($w->nonspam * 2) + $w->spam >= 5)
				$words[$k]->interest_rating = self::_getMedianDeviation($w->probability);
			else
				$words[$k]->interest_rating = 0.00;
		}
		
		return $words;
	}
	
	static public function _combineP($argv) {
		// [JAS]: Variable for all our probabilities multiplied, for Naive Bayes
		$AB = 1; // probabilities: A*B*C...
		$ZY = 1; // compliments: (1-A)*(1-B)*(1-C)...
		
		foreach($argv as $v) {
			$AB *= $v;
			$ZY *= (1-$v);
		}

		$combined_p = $AB / ($AB + $ZY);
		
		switch($combined_p)
		{
			case $combined_p > self::PROBABILITY_CEILING:
				return self::PROBABILITY_CEILING;
				break;
			case $combined_p < self::PROBABILITY_FLOOR:
				return self::PROBABILITY_FLOOR;
				break;
		}
		
		return number_format($combined_p,4);
	}
	
	/**
	 * @param float $p Probability
	 * @return float Median Deviation
	 */
	static private function _getMedianDeviation($p) {
		if($p > self::PROBABILITY_MEDIAN)
			return $p - self::PROBABILITY_MEDIAN;
		else
			return self::PROBABILITY_MEDIAN - $p;
	}
	
	/**
	 * @param CerberusBayesWord $word
	 * @return float The probability of the word being spammy.
	 */
	static private function _calculateWordProbability($word) {
		static $stats = null; // [JAS]: [TODO] Keep an eye on this.
		if(is_null($stats)) $stats = DAO_Bayes::getStatistics();
		
		if(!is_a($word,'CerberusBayesWord')) return FALSE;
		
		$ngood = max($stats['nonspam'],1);
		$nbad = max($stats['spam'],1);
		
		$g = intval($word->nonspam * 2);
		$b = intval($word->spam);

		// [JAS]: If less than 5 occurrences total
		if($g*2 + $b < 5) {
			$prob = self::PROBABILITY_UNKNOWN;
			
		} else {
			$prob = max(self::PROBABILITY_FLOOR,
				min(self::PROBABILITY_CEILING,
					floatval( 
						min(1,($b/$nbad))	
						/ 
						( $g/$ngood + $b/$nbad )
					)
				)
			);
		}
		
		return number_format($prob,4);
	}
	
	/**
	 * @param CerberusBayesWord $a
	 * @param CerberusBayesWord $b
	 */
	static private function _sortByInterest($a, $b) {
	   if ($a->interest_rating == $b->interest_rating) {
	       return 0;
	   }
	   return ($a->interest_rating < $b->interest_rating) ? -1 : 1;
	}
	
	/**
	 * @param CerberusBayesWord[] $words
	 * @return array 'probability' = Overall Spam Probability, 'words' = interesting words
	 */
	static private function _calculateSpamProbability($words) {
		$probabilities = array();
		
		// Sort words by interest descending
		$interesting_words = $words; 
		usort($interesting_words,array('CerberusBayes','_sortByInterest'));
		$interesting_words = array_slice($interesting_words,-1 * self::MAX_INTERESTING_WORDS);

		// Combine word probabilities into an overall probability
		foreach($interesting_words as $word) { /* @var $word CerberusBayesWord */
			$probabilities[] = $word->probability;
		}
		$combined = self::_combineP($probabilities);
		
		return array('probability' => $combined, 'words' => $interesting_words);
	}
	
	static function calculateTicketSpamProbability($ticket_id) {
		// pull up text of first ticket message
		@list($message_id, $first_message) = each(array_shift(CerberusTicketDAO::getMessagesByTicket($ticket_id))); /* @var $first_message CerberusMessage */
		if(!is_a($first_message,'CerberusMessage')) return FALSE;
		
		// Pass text to analyze() to get back interesting words
		$content = $first_message->getContent();
		$words = self::processText($content);
		
		$out = self::_calculateSpamProbability($words);
		
		// Cache probability
		$fields = array('spam_score' => $out['probability']);
		CerberusTicketDAO::updateTicket($ticket_id, $fields);
	}
	
};

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
			$bIsNew = false;
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
			
			foreach ($attachments['files'] as $filepath => $filename) {
				CerberusTicketDAO::createAttachment($message_id, $filename, $filepath);
			}
		}

		// Spam scoring
		if($bIsNew) CerberusBayes::calculateTicketSpamProbability($id);
		
		$ticket = CerberusTicketDAO::getTicket($id);
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
	 * @return integer mailbox id
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
			$routing = CerberusMailDAO::getMailboxRouting();
		
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
					return $route->mailbox_id;
			}
		}
		
		// envelope + delivered 'Delivered-To'
		// received
		
		// Check if we have a default mailbox configured before returning NULL.		
		$default_mailbox_id = $settings->get(CerberusSettings::DEFAULT_MAILBOX_ID,0);
		
		if(!empty($default_mailbox_id)) { // catchall
			return $default_mailbox_id;
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
			if (false !== file_put_contents(DEVBLOCKS_ATTACHMENT_SAVE_PATH . $timestamp . $fileName, $part->body)) {
				$attachments['files'][$timestamp.$fileName] = $fileName;
//				$attachments['plaintext'] .= ' Saved file <a href="' . DEVBLOCKS_ATTACHMENT_ACCESS_PATH . $timestamp . $fileName . '">'
//											. (empty($fileName) ? 'Unnamed file' : $fileName) . '</a>. ';
			}
		}
	}
	
	static function parseRfcAddress($address_string) {
		/*
		 * [JAS]: [TODO] If we're going to call platform libs directly we should just have
		 * the platform provide the functionality.
		 */
		require_once(DEVBLOCKS_PATH . 'pear/Mail/RFC822.php');
		$structure = Mail_RFC822::parseAddressList($address_string, null, false);
		return $structure;
	}
	
};

class CerberusSettings {
	const DEFAULT_MAILBOX_ID = 'default_mailbox_id'; 
	const DEFAULT_REPLY_FROM = 'default_reply_from'; 
	const DEFAULT_REPLY_PERSONAL = 'default_reply_personal'; 
	const HELPDESK_TITLE = 'helpdesk_title'; 
	const SMTP_HOST = 'smtp_host'; 
	const SMTP_AUTH_USER = 'smtp_auth_user'; 
	const SMTP_AUTH_PASS = 'smtp_auth_pass'; 
	
	static $instance = null;
	private $settings = array( // defaults
		self::DEFAULT_MAILBOX_ID => 0,
		self::DEFAULT_REPLY_FROM => '',
		self::DEFAULT_REPLY_PERSONAL => '',
		self::HELPDESK_TITLE => 'Cerberus Helpdesk :: Team-based E-mail Management',
		self::SMTP_HOST => 'localhost',
		self::SMTP_AUTH_USER => '',
		self::SMTP_AUTH_PASS => '',
	);

	/**
	 * @return CerberusSettings
	 */
	private function __construct() {
		$saved_settings = DAO_Setting::getSettings();
		foreach($saved_settings as $k => $v) {
			$this->settings[$k] = $v;
		}
	}
	
	/**
	 * @return CerberusSettings
	 */
	public static function getInstance() {
		if(self::$instance==null) {
			self::$instance = new CerberusSettings();	
		}
		
		return self::$instance;		
	}
	
	public function set($key,$value) {
		DAO_Setting::set($key,$value);
		$this->settings[$key] = $value;
		
		return TRUE;
	}
	
	/**
	 * @param string $key
	 * @param string $default
	 * @return mixed
	 */
	public function get($key,$default=null) {
		if(isset($this->settings[$key]))
			return $this->settings[$key];
		else 
			return $default;
	}
};

// [JAS]: [TODO] This probably isn't needed
class ToCriterion implements ICerberusCriterion {
	function getValue($rfcMessage) {
		return $rfcMessage->headers['to'];
	}
};
?>