<?php
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
	    $messages = DAO_Ticket::getMessagesByTicket($ticket_id);
	    $first_message = array_shift($messages);

		if(empty($first_message) || !($first_message instanceOf CerberusMessage)) 
		    return FALSE;
		
		// Pass text to analyze() to get back interesting words
		$content = $first_message->getContent();
	    $content = substr($content, 0, strrpos(substr($content, 0, self::MAX_BODY_LENGTH), ' '));
		
		$words = self::processText($content);
		
		// Train interesting words as spam/notspam
		$out = self::_calculateSpamProbability($words);
		self::_trainWords($out['words'],$spam);
		
		// Increase the bayes_stats spam or notspam total count by 1
		if($spam) {
		    DAO_Bayes::addOneToSpamTotal(); 
		} else {
		    DAO_Bayes::addOneToNonSpamTotal();
		}
		
		// Forced training should leave a cache of 0.0001 or 0.9999 on the ticket table
		$fields = array(
			'spam_score' => ($spam) ? 0.9999 : 0.0001,
			'spam_training' => ($spam) ? CerberusTicketSpamTraining::SPAM : CerberusTicketSpamTraining::NOT_SPAM
		);
		DAO_Ticket::updateTicket($ticket_id,$fields);
	}

	/**
	 * @param CerberusBayesWord[] $words
	 * @param boolean $spam
	 */
	static private function _trainWords($words, $spam=true) {
		if(!is_array($words))
		    return;
	
		$ids = array();
		foreach($words as $word) { /* @var $word CerberusBayesWord */
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
	static private function _calculateWordProbability(CerberusBayesWord $word) {
		static $stats = null; // [JAS]: [TODO] Keep an eye on this.
		if(is_null($stats)) $stats = DAO_Bayes::getStatistics();
		
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
	    $messages = DAO_Ticket::getMessagesByTicket($ticket_id);
	    $first_message = array_shift($messages);
	    
		if(empty($first_message) || !($first_message instanceOf CerberusMessage)) 
		    return FALSE;
		
		// Pass text to analyze() to get back interesting words
		$content = $first_message->getContent();
		
		// Only check the first 15000 characters for spam, rounded to a sentence
	    $content = substr($content, 0, strrpos(substr($content, 0, self::MAX_BODY_LENGTH), ' '));

		$words = self::processText($content);
		
		$out = self::_calculateSpamProbability($words);
		
		// Cache probability
		$fields = array('spam_score' => $out['probability']);
		DAO_Ticket::updateTicket($ticket_id, $fields);
	}
	
};
?>
