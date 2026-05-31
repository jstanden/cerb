<?php
// [TODO] Unit test the hell out of this
class _DevblocksBayesClassifierService {
	static $DAYS_LONG = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
	static $DAYS_LONG_PLURAL = ['mondays', 'tuesdays', 'wednesdays', 'thursdays', 'fridays', 'saturdays', 'sundays'];
	static $DAYS_NTH = ['1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th', '13th', '14th', '15th', '16th', '17th', '18th', '19th', '20th', '21st', '22nd', '23rd', '24th', '25th', '26th', '27th', '28th', '29th', '30th', '31st'];
	static $DAYS_REL = ['today', 'tomorrow', 'yesterday'];
	static $DAYS_SHORT = ['mon', 'tue', 'tues', 'wed', 'weds', 'thu', 'thur', 'thurs', 'fri', 'sat', 'sun'];
	//static $DAYS_SHORT_PLURAL = ['mons', 'tues', 'weds', 'thus', 'thurs', 'fris', 'sats', 'suns '];
	static $MONTHS_LONG = ['january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december'];
	static $MONTHS_SHORT = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'sept', 'oct', 'nov', 'dec'];
	static $NUM_ORDINAL = ['zeroth', 'first', 'second', 'third', 'fourth', 'fifth', 'sixth', 'seventh', 'eighth', 'ninth', 'tenth', 'eleventh', 'twelfth', 'thirteenth', 'fourteenth', 'fifteenth', 'sixteenth', 'seventeenth', 'eighteenth', 'nineteenth', 'twentieth', 'thirtieth'];
	static $NUM_WORDS = ['zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety', 'hundred', 'thousand', 'million', 'billion', 'trillion', 'quadrillion'];
	static $TIME_MERIDIEM = ['am', 'pm', 'a.m', 'p.m'];
	static $TIME_REL = ['now', 'morning', 'afternoon', 'noon', 'evening', 'night', 'tonight', 'midnight'];
	static $TIME_UNITS = ['ms', 'millisecond', 'milliseconds', 'sec', 'secs', 'second', 'seconds', 'min', 'mins', 'minute', 'minutes', 'hr', 'hrs', 'hour', 'hours'];
	static $DATE_UNIT = ['day', 'days', 'wk', 'wks', 'week', 'weeks', 'mo', 'mos', 'month', 'months', 'yr', 'yrs', 'year', 'years'];
	static $TEMP_UNITS = ['c', 'celsius', 'centigrade', 'f', 'fahrenheit', 'degrees'];
	
	static $CONTRACTIONS_EN = [
		"aren't" => 'are not',
		"can't" => 'cannot',
		"could've" => 'could have',
		"couldn't" => 'could not',
		"didn't" => 'did not',
		"doesn't" => 'does not',
		"don't" => 'do not',
		"hadn't" => 'had not',
		"hasn't" => 'has not',
		"haven't" => 'have not',
		"he'd" => 'he would',
		"he'll" => 'he will',
		"he's" => 'he is',
		"i'll" => 'i will',
		"i'm" => 'i am',
		"i've" => 'i have',
		"isn't" => 'is not',
		"it'd" => 'it would',
		"it'll" => 'it will',
		"it's" => 'it is',
		"might've" => 'might have',
		"mightn't" => 'might not',
		"must've" => 'must have',
		"mustn't" => 'must not',
		"shouldn't" => 'should not',
		"shouldn've" => 'should have',
		"t'is" => 'it is',
		"wasn't" => 'was not',
		"we'll" => 'we will',
		"we're" => 'we are',
		"weren't" => 'were not',
		"what's" => 'what is',
		"won't" => 'will not',
		"would've" => 'would have',
		"wouldn't" => 'would not',
		"you'd" => 'you would',
		"you'll" => 'you will',
		"you're" => 'you are',
		"you've" => 'you have',
		
		//"r" => 'are',
		//"u" => 'you',
			
		"w/" => 'with',
		"w/o" => 'without',
	];
	
	// [TODO]
	static $STOP_WORDS_EN = [
		'a',
		'an',
		'at',
		'in',
		'is',
		'of',
		'or',
		'the',
	];
	
	/**
	 * 
	 * @return _DevblocksBayesClassifierService
	 */
	static function getInstance() {
		return self::class;
	}
	
	static function preprocessTextCondenseWhitespace($text) {
		return preg_replace('#\s{2,}#',' ', $text);
	}
	
	static function preprocessTextExpandContractions($text) {
		$words = explode(' ', $text);
		
		foreach($words as &$word)
			if(isset(self::$CONTRACTIONS_EN[$word]))
				$word = self::$CONTRACTIONS_EN[$word];
		unset($word);

		return implode(' ', $words);
	}
	
	static function preprocessWordsPad(array $words, $length=0) {
		array_walk($words, function(&$word) use ($length) {
			$word = str_pad($word, $length, '_', STR_PAD_RIGHT);
		});
		
		return $words;
	}
	
	static function preprocessWordsStripPunctuation(array $words) {
		array_walk($words, function(&$word) {
			if($word == '?')
				return;
			
			$word = trim($word, '.,?!:() ');
		});
		
		return $words;
	}
	
	static function preprocessWordsRemoveStopWords($words, $stop_words) {
		return array_diff($words, $stop_words);
	}
	
	// [TODO] Configurable
	static function tokenizeWords($text) {
		// Change symbols to words
		$text = str_replace(['º','°'], [' degrees ', ' degrees '], $text);
		
		$text = DevblocksPlatform::strLower($text);
		
		// [TODO] Normalize 5pm -> 5 pm
		// [TODO] Normalize 1hr -> 1 hr
		
		// Expand contraction tokens
		$text = self::preprocessTextExpandContractions($text);
		
		// Tokenize question mark
		$text = preg_replace('#(\S)\?(\s|$)+#', '\1 ? ', $text);
		
		// Tokenize possessive 's
		$text = preg_replace('#(\S)\'s(\s|$)+#', '\1 \'s ', $text);
		
		// Condense whitespace
		$text = self::preprocessTextCondenseWhitespace($text);
		
		$words = explode(' ', $text);
		
		$words = self::preprocessWordsStripPunctuation($words);
		
		return array_filter($words, function($word) {
			return !empty($word);
		});
	}
	
	// [TODO] Configurable
	static function tokenizeStrings($text) {
		$text = DevblocksPlatform::strLower($text);
		
		// Strip punctuation
		$text = DevblocksPlatform::strAlphaNum($text, ' ');
		
		// Condense whitespace
		$text = self::preprocessTextCondenseWhitespace($text);
		
		//$words = explode(' ', $text);
		
		$tokens = [$text];
		
		return array_filter($tokens, function($token) {
			return !empty($token);
		});
	}
	
	// [TODO] Move this into DevblocksPlatform
	/*
	static function _findSubsetInArrayKeys(array $find, array $array) {
		$keys = array_keys($array, $find[0]);
		$find_len = count($find);
		
		foreach($keys as $key) {
			$slice = array_slice($array, $key, $find_len);
		
			if($find == $slice) {
				return $key;
			}
		}
		
		return false;
	}
	*/
	
	static function _findSubsetInArray(array $find, array $array, $start=0) {
		reset($array);
		
		if($start) {
			$array = array_slice($array, $start, null, true);
		}
		
		$first_idx = key($array);
		$vals = array_values($array);
		
		$hits = array_keys($vals, $find[0]);
		
		foreach($hits as $pos) {
			$find_len = count($find);
			
			$slice = array_slice($vals, $pos, $find_len);
		
			if($find == $slice) {
				return $first_idx + $pos;
			}
		}
		
		return false;
	}

	static function getNGrams($text, $n=2, $with_terminators=true) {
		// Example: Schedule lunch at noon
		// Unigrams: schedule, lunch, at, noon
		// Bigrams: ^ schedule, schedule lunch, lunch at, at noon, noon $
		// Trigrams: ^ schedule lunch, schedule lunch at, lunch at noon, at noon $
		
		if(!is_numeric($n) || $n <= 0) {
			return false;
		
		} elseif (1 == $n) {
			return explode(' ', $text);
		}
		
		$tokens = explode(' ', $text);
		
		if($with_terminators)
			$tokens = array_merge(['[start]'],$tokens,['[end]']);
		
		$ngrams = [];
		$len = count($tokens);
		
		foreach($tokens as $idx => $token) {
			if($idx + $n > $len)
				break;
			
			$ngrams[] = array_slice($tokens, $idx, $n);
		}
		
		return $ngrams;
	}
	
	static function verify($text) {
		if(empty($text))
			return false;
		
		// [TODO] Look for tags and verify them
		$tagged_text = preg_replace('#\{\{(.*?)\:(.*?)\}\}#','[${1}]', $text);
		
		if(!(self::tokenizeWords($tagged_text)))
			return false;
		
		return true;
	}
}