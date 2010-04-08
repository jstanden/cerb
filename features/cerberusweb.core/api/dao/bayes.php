<?php
/**
 * Bayesian Anti-Spam DAO
 */
class DAO_Bayes {
	private function DAO_Bayes() {}
	
	/**
	 * @return CerberusWord[]
	 */
	static function lookupWordIds($words) {
		$db = DevblocksPlatform::getDatabaseService();
		$tmp = array();
		$outwords = array(); // CerberusWord
		
		// Escaped set
		if(is_array($words))
		foreach($words as $word) {
			$tmp[] = addslashes($word);
		}
		
		if(empty($words))
		    return array();
		
		$sql = sprintf("SELECT id,word,spam,nonspam FROM bayes_words WHERE word IN ('%s')",
			implode("','", $tmp)
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		// [JAS]: Keep a list of words we can check off as we index them with IDs
		$tmp = array_flip($words); // words are now keys
		
		// Existing Words
		
		while($row = mysql_fetch_assoc($rs)) {
			$w = new Model_BayesWord();
			$w->id = intval($row['id']);
			$w->word = mb_convert_case($row['word'], MB_CASE_LOWER);
			$w->spam = intval($row['spam']);
			$w->nonspam = intval($row['nonspam']);
			
			$outwords[mb_convert_case($w->word, MB_CASE_LOWER)] = $w;
			unset($tmp[$w->word]); // check off we've indexed this word
		}
		
		mysql_free_result($rs);
		
		// Insert new words
		if(is_array($tmp))
		foreach($tmp as $new_word => $v) {
			$new_id = $db->GenID('bayes_words_seq');
			$sql = sprintf("INSERT INTO bayes_words (id,word) VALUES (%d,%s)",
				$new_id,
				$db->qstr($new_word)
			);
			$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
			
			$w = new Model_BayesWord();
			$w->id = $new_id;
			$w->word = $new_word;
			$outwords[$w->word] = $w;
		}
		
		return $outwords;
	}
	
	/**
	 * @return array Two element array (keys: spam,nonspam)
	 */
	static function getStatistics() {
		$db = DevblocksPlatform::getDatabaseService();
		
		// [JAS]: [TODO] Change this into a 'replace' index?
		$sql = "SELECT spam, nonspam FROM bayes_stats";
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
		
		if($row = mysql_fetch_assoc($rs)) {
			$spam = intval($row['spam']);
			$nonspam = intval($row['nonspam']);
		} else {
			$spam = 0;
			$nonspam = 0;
			$sql = "INSERT INTO bayes_stats (spam, nonspam) VALUES (0,0)";
			$db->Execute($sql);
		}
		
		return array('spam' => $spam,'nonspam' => $nonspam);
	}
	
	static function addOneToSpamTotal() {
		$db = DevblocksPlatform::getDatabaseService();
		$sql = "UPDATE bayes_stats SET spam = spam + 1";
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
	}
	
	static function addOneToNonSpamTotal() {
		$db = DevblocksPlatform::getDatabaseService();
		$sql = "UPDATE bayes_stats SET nonspam = nonspam + 1";
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
	}
	
	static function addOneToSpamWord($word_ids=array()) {
	    if(!is_array($word_ids)) $word_ids = array($word_ids);
	    if(empty($word_ids)) return;
		$db = DevblocksPlatform::getDatabaseService();
		$sql = sprintf("UPDATE bayes_words SET spam = spam + 1 WHERE id IN(%s)", implode(',',$word_ids));
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
	}
	
	static function addOneToNonSpamWord($word_ids=array()) {
	    if(!is_array($word_ids)) $word_ids = array($word_ids);
	    if(empty($word_ids)) return;
		$db = DevblocksPlatform::getDatabaseService();
		$sql = sprintf("UPDATE bayes_words SET nonspam = nonspam + 1 WHERE id IN(%s)", implode(',',$word_ids));
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); 
	}
};

class Model_BayesWord {
	public $id = -1;
	public $word = '';
	public $spam = 0;
	public $nonspam = 0;
	public $probability = CerberusBayes::PROBABILITY_UNKNOWN;
	public $interest_rating = 0.0;
};

class CerberusBayes {
	const PROBABILITY_CEILING = 0.9999;
	const PROBABILITY_FLOOR = 0.0001;
	const PROBABILITY_UNKNOWN = 0.4;
	const PROBABILITY_MEDIAN = 0.5;
	const MAX_INTERESTING_WORDS = 15;
	const MAX_BODY_LENGTH = 15000;
	
	/**
	 * @param string $text A string of text to break into unique words
	 * @param integer $min The minimum word length used
	 * @param integer $max The maximum word length used
	 * @return array An array with unique words as keys
	 */
	static function parseUniqueWords($text,$min=3,$max=24) {
		$chars = array('\'');
		$tokens = array('__apos__');
		
// ** REFACTOR BEGIN
		// Encode apostrophes/etc
//		$tokens = array(
//			'__apos__' => '\''
//		);

		// [TODO] Implement this back in Bayes
		
		// URLs
		// Reference: http://daringfireball.net/2009/11/liberal_regex_for_matching_urls
//		$matches = array();
//		$count = 0;
//		if(preg_match_all("#\b(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))#", $text, $matches)) {
//			foreach($matches[0] as $match) {
//				$token = '__url'.$count++.'__';
//				$tokens[$token] = $match;
//			}
//		}
		
		// IPs
//		$matches = array();
//		$count = 0;
//		if(preg_match_all("#(\d{1,3}\.){3}\d{1,3}#", $text, $matches)) {
//			foreach($matches[0] as $match) {
//				$token = '__ip'.$count++.'__';
//				$tokens[$token] = $match;
//			}
//		}
		
		// Email addresses
//		$matches = array();
//		$count = 0;
//		if(preg_match_all("#\w+\@\w+\.\w+#", $text, $matches)) {
//			foreach($matches[0] as $match) {
//				$token = '__email'.$count++.'__';
//				$tokens[$token] = $match;
//			}
//		}
		
//		$text = str_replace(array_values($tokens), array_keys($tokens), $text);
		
// ** REFACTOR END		
		
		// Encode apostrophes/etc
		$text = str_replace($chars,$tokens,$text);
		
		// Force lowercase and strip non-word punctuation (a-z, 0-9, _)
		if(function_exists('mb_ereg_replace'))
			$text = mb_ereg_replace('[^a-z0-9_]+', ' ', mb_convert_case($text, MB_CASE_LOWER));
		else
			$text = preg_replace('/[^a-z0-9_]+/', ' ', mb_convert_case($text, MB_CASE_LOWER));

		// Decode apostrophes/etc
		$text = str_replace($tokens,$chars,$text);

		// Sort unique words w/ condensed spaces
		if(function_exists('mb_ereg_replace'))
			$words = array_flip(explode(' ', mb_ereg_replace('\s+', ' ', $text)));
		else 
			$words = array_flip(explode(' ', preg_replace('/\s+/', ' ', $text)));

		// Toss words that are too common
	    $words = self::_removeCommonWords($words);
		
		// Toss anything over/under the word length bounds
		foreach($words as $k => $v) {
			$len = mb_strlen($k);
			if($len < $min || $len > $max || is_numeric($k)) { // [TODO]: Make decision on !numeric?
				unset($words[$k]); // toss
			}
		}

		return $words;
	}
	
	static private function _removeCommonWords($words) {
	    // English
	    unset($words['']);
	    unset($words['a']);
	    unset($words['about']);
	    unset($words['all']);
	    unset($words['am']);
	    unset($words['an']);
	    unset($words['and']);
	    unset($words['any']);
	    unset($words['as']);
	    unset($words['at']);
	    unset($words['are']);
	    unset($words['be']);
	    unset($words['been']);
	    unset($words['but']);
	    unset($words['by']);
	    unset($words['can']);
	    unset($words['could']);
	    unset($words['did']);
	    unset($words['do']);
	    unset($words['doesn\'t']);
	    unset($words['don\'t']);
	    unset($words['for']);
	    unset($words['from']);
	    unset($words['get']);
	    unset($words['had']);
	    unset($words['has']);
	    unset($words['have']);
	    unset($words['hello']);
	    unset($words['hi']);
	    unset($words['how']);
	    unset($words['i']);
	    unset($words['i\'m']);
	    unset($words['if']);
	    unset($words['in']);
	    unset($words['into']);
	    unset($words['is']);
	    unset($words['it']);
	    unset($words['it\'s']);
	    unset($words['its']);
	    unset($words['may']);
	    unset($words['me']);
	    unset($words['my']);
	    unset($words['not']);
	    unset($words['of']);
	    unset($words['on']);
	    unset($words['or']);
	    unset($words['our']);
	    unset($words['out']);
	    unset($words['please']);
	    unset($words['than']);
	    unset($words['thank']);
	    unset($words['thanks']);
	    unset($words['that']);
	    unset($words['the']);
	    unset($words['their']);
	    unset($words['them']);
	    unset($words['then']);
	    unset($words['there']);
	    unset($words['these']);
	    unset($words['they']);
	    unset($words['this']);
	    unset($words['those']);
	    unset($words['to']);
	    unset($words['us']);
	    unset($words['want']);
	    unset($words['was']);
	    unset($words['we']);
	    unset($words['were']);
	    unset($words['what']);
	    unset($words['when']);
	    unset($words['which']);
	    unset($words['while']);
	    unset($words['why']);
	    unset($words['will']);
	    unset($words['with']);
	    unset($words['would']);
	    unset($words['you']);
	    unset($words['your']);
	    unset($words['you\'re']);
	    
	    return $words;
	}
	
	static function unaccentUtf8Text($text) {
		if(0 == strcasecmp(LANG_CHARSET_CODE,'utf-8')) {
			$from = array('À','Á','Â','Ã','Ä','Å','Æ','à','á','â','ã','ä','å','æ','Ò','Ó','Ô','Õ','Õ','Ö','Ø','ò','ó','ô','õ','ö','ø','È','É','Ê','Ë','è','é','ê','ë','ð','Ç','ç','Ð','Ì','Í','Î','Ï','ì','í','î','ï','Ù','Ú','Û','Ü','ù','ú','û','ü','Ñ','ñ','Þ','ß','ÿ','ý');
			$to = array('A','A','A','A','A','A','a','a','a','a','a','a','a','a','O','O','O','O','O','O','O','o','o','o','o','o','o','E','E','E','E','e','e','e','e','e','C','c','e','I','I','I','I','i','i','i','i','U','U','U','U','u','u','u','u','N','n','t','ss','y','y');
			$text = str_replace($from, $to, $text);
		}
		
		return $text;
	}
	
	/**
	 * @param string $text A string of text to run through spam scoring
	 * @return array Analyzed statistics
	 */
	static function processText($text) {
		$text = self::unaccentUtf8Text($text);
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
	
	// [TODO] Accept batch tickets for training for efficiencies sake
	static private function _markTicketAs($ticket_id,$spam=true) {
		// [TODO] Make sure we can't retrain tickets which are already spam trained
		// [TODO] This is a performance killer
		$ticket = DAO_Ticket::getTicket($ticket_id);
		
		if($ticket->spam_training != CerberusTicketSpamTraining::BLANK)
			return TRUE;
			
		// pull up text of first ticket message
		// [TODO] This is a performance killer
		$first_message = DAO_Message::get($ticket->first_message_id);

		if(empty($first_message)) 
		    return FALSE;
		
		// Pass text to analyze() to get back interesting words
		$content = '';
		if(!empty($ticket->subject)) {
			// SplitCamelCapsSubjects
			$hits = preg_split("{(?<=[a-z])(?=[A-Z])}x", $ticket->subject);
			if(is_array($hits) && !empty($hits)) {
				$content .= implode(' ',$hits);
			}
		}
		$content .= ' ' . $first_message->getContent();
		
		if(strlen($content) > self::MAX_BODY_LENGTH)
	    	$content = substr($content, 0, strrpos(substr($content, 0, self::MAX_BODY_LENGTH), ' '));
		
		$words = self::processText($content);
		
		// Train interesting words as spam/notspam
//		$out = self::_calculateSpamProbability($words);
//		self::_trainWords($out['words'],$spam);
		self::_trainWords($words,$spam); // [TODO] Testing, train all words
		
		// Increase the bayes_stats spam or notspam total count by 1
		// [TODO] This is a performance killer (could be done in batches)
		if($spam) {
		    DAO_Bayes::addOneToSpamTotal();
		    DAO_Address::addOneToSpamTotal($ticket->first_wrote_address_id); 
		} else {
		    DAO_Bayes::addOneToNonSpamTotal();
		    DAO_Address::addOneToNonSpamTotal($ticket->first_wrote_address_id);
		}
		
		// Forced training should leave a cache of 0.0001 or 0.9999 on the ticket table
		$fields = array(
			'spam_score' => ($spam) ? 0.9999 : 0.0001,
			'spam_training' => ($spam) ? CerberusTicketSpamTraining::SPAM : CerberusTicketSpamTraining::NOT_SPAM,
		);
		DAO_Ticket::updateTicket($ticket_id,$fields);

		return TRUE;
	}

	/**
	 * @param Model_BayesWord[] $words
	 * @param boolean $spam
	 */
	static private function _trainWords($words, $spam=true) {
		if(!is_array($words))
		    return;
	
		$ids = array();
		foreach($words as $word) { /* @var $word Model_BayesWord */
		    $ids[] = $word->id;
		}
		    
		if($spam) {
		    DAO_Bayes::addOneToSpamWord($ids); 
		} else { 
		    DAO_Bayes::addOneToNonSpamWord($ids);
		}
		
		unset($ids);
	}
	
	/**
	 * @param array $words An array indexed with words to look up 
	 */
	static private function _lookupWordIds($words) {
		$pos = 0;
		$batch_size = 100; //[TODO] Tune this
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
			$words[$k]->probability = self::calculateWordProbability($w);
			
			// [JAS]: If a word appears more than 5 times (counting weight) in the corpus, use it.  Otherwise de-emphasize.
			if(($w->nonspam * 1) + $w->spam >= 5)
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
	 * @param Model_BayesWord $word
	 * @return float The probability of the word being spammy.
	 */
	static public function calculateWordProbability(Model_BayesWord $word) {
		static $stats = null; // [JAS]: [TODO] Keep an eye on this.
		if(is_null($stats)) $stats = DAO_Bayes::getStatistics();
		
		$ngood = max($stats['nonspam'],1);
		$nbad = max($stats['spam'],1);
		
		$g = intval($word->nonspam * 1);
		$b = intval($word->spam);

		// [JAS]: If less than 5 occurrences total
		if(($g*1 + $b) < 5) {
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
	 * @param Model_BayesWord $a
	 * @param Model_BayesWord $b
	 */
	static private function _sortByInterest($a, $b) {
	   if ($a->interest_rating == $b->interest_rating) {
	       return 0;
	   }
	   return ($a->interest_rating < $b->interest_rating) ? -1 : 1;
	}
	
	/**
	 * @param Model_BayesWord[] $words
	 * @return array 'probability' = Overall Spam Probability, 'words' = interesting words
	 */
	static private function _calculateSpamProbability($words) {
		$probabilities = array();
		
		// Sort words by interest descending
		$interesting_words = $words; 
		usort($interesting_words,array('CerberusBayes','_sortByInterest'));
		$interesting_words = array_slice($interesting_words,-1 * self::MAX_INTERESTING_WORDS);

		// Combine word probabilities into an overall probability
		foreach($interesting_words as $word) { /* @var $word Model_BayesWord */
			$probabilities[] = $word->probability;
		}
		$combined = self::_combineP($probabilities);
		
		return array('probability' => $combined, 'words' => $interesting_words);
	}
	
	static function calculateTicketSpamProbability($ticket_id, $readonly=false) {
		// pull up text of first ticket message
	    $messages = DAO_Message::getMessagesByTicket($ticket_id);
	    $first_message = array_shift($messages);
	    $ticket = DAO_Ticket::getTicket($ticket_id);
	    
		if(empty($ticket) || empty($first_message) || !($first_message instanceOf Model_Message)) 
		    return FALSE;
		
		// Pass text to analyze() to get back interesting words
		$content = '';
		if(!empty($ticket->subject)) {
			// SplitCamelCapsSubjects
			$hits = preg_split("{(?<=[a-z])(?=[A-Z])}x", $ticket->subject);
			if(is_array($hits) && !empty($hits)) {
				$content .= implode(' ',$hits);
			}
		}
		$content .= ' ' . $first_message->getContent();		
		
		// Only check the first 15000 characters for spam, rounded to a sentence
		if(strlen($content) > self::MAX_BODY_LENGTH)
			$content = substr($content, 0, strrpos(substr($content, 0, self::MAX_BODY_LENGTH), ' '));
	    
		$words = self::processText($content);
		$out = self::_calculateSpamProbability($words);

		// Make a word list
	    $rawwords = array();
	    foreach($out['words'] as $k=>$v) { /* @var $v Model_BayesWord */
	        $rawwords[] = $v->word;
	    }
		
		// Cache probability
		if(!$readonly) {
			$fields = array(
			    DAO_Ticket::SPAM_SCORE => $out['probability'],
			    DAO_Ticket::INTERESTING_WORDS => substr(implode(',',array_reverse($rawwords)),0,255),
			);
			DAO_Ticket::updateTicket($ticket_id, $fields);
		}
		
		return $out;
	}
};
