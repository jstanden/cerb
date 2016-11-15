<?php
// [TODO] Unit test the hell out of this
class _DevblocksBayesClassifierService {
	static $AVAIL_FREE = ['available', 'free', 'unoccupied'];
	static $AVAIL_BUSY = ['busy', 'occupied', 'preoccupied', 'unavailable'];
	static $DAYS_LONG = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
	static $DAYS_LONG_PLURAL = ['mondays', 'tuesdays', 'wednesdays', 'thursdays', 'fridays', 'saturdays', 'sundays'];
	static $DAYS_NTH = ['1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th', '13th', '14th', '15th', '16th', '17th', '18th', '19th', '20th', '21st', '22nd', '23rd', '24th', '25th', '26th', '27th', '28th', '29th', '30th', '31st'];
	static $DAYS_REL = ['today', 'tomorrow', 'yesterday'];
	static $DAYS_SHORT = ['mon', 'tue', 'tues', 'wed', 'weds', 'thu', 'thur', 'thurs', 'fri', 'sat', 'sun'];
	//static $DAYS_SHORT_PLURAL = ['mons', 'tues', 'weds', 'thus', 'thurs', 'fris', 'sats', 'suns '];
	static $MONTHS_LONG = ['january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december'];
	static $MONTHS_SHORT = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'sept', 'oct', 'nov', 'dec'];
	static $NUM_ORDINAL = ['first', 'second', 'third', 'fourth', 'fifth', 'sixth', 'seventh', 'eighth', 'ninth', 'tenth', 'eleventh', 'twelfth', 'thirteenth', 'fourteenth', 'fifteenth', 'sixteenth', 'seventeenth', 'eighteenth', 'nineteenth', 'twentieth', 'thirtieth'];
	static $NUM_WORDS = ['one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety', 'hundred', 'thousand', 'million', 'billion', 'trillion', 'quadrillion'];
	static $TIME_MERIDIEM = ['am', 'pm', 'a.m', 'p.m'];
	static $TIME_REL = ['now', 'morning', 'afternoon', 'noon', 'evening', 'night', 'tonight', 'midnight'];
	static $TIME_UNITS = ['ms', 'millisecond', 'milliseconds', 'sec', 'secs', 'second', 'seconds', 'min', 'mins', 'minute', 'minutes', 'hr', 'hrs', 'hour', 'hours'];
	static $DATE_UNIT = ['day', 'days', 'wk', 'wks', 'week', 'weeks', 'mo', 'mos', 'month', 'months', 'yr', 'yrs', 'year', 'years'];
	
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
	
	static $TAGS_TO_TOKENS = [
		//'#\{\{alias\:(.*?)\}\}#' => '[alias]',
		'#\{\{avail\:(.*?)\}\}#' => '[avail]',
		'#\{\{contact\:(.*?)\}\}#' => '[contact]',
		'#\{\{contact_method\:(.*?)\}\}#' => '[contact_method]',
		'#\{\{context\:(.*?)\}\}#' => '[context]',
		'#\{\{date\:(.*?)\}\}#' => '[date]',
		'#\{\{duration\:(.*?)\}\}#' => '[duration]',
		//'#\{\{entity\:(.*?)\}\}#' => '[entity]',
		'#\{\{event\:(.*?)\}\}#' => '[event]',
		'#\{\{name\:(.*?)\}\}#' => '[name]',
		'#\{\{number\:(.*?)\}\}#' => '[number]',
		'#\{\{org\:(.*?)\}\}#' => '[org]',
		//'#\{\{phone\:(.*?)\}\}#' => '[phone]',
		//'#\{\{place\:(.*?)\}\}#' => '[place]',
		'#\{\{remind\:(.*?)\}\}#' => '[remind]',
		'#\{\{status\:(.*?)\}\}#' => '[status]',
		'#\{\{time\:(.*?)\}\}#' => '[time]',
		'#\{\{worker\:(.*?)\}\}#' => '[worker]',
	];
	
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
		
		return implode(' ', $words);
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
		$text = strtolower($text);
		
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
		$text = strtolower($text);
		
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
	
	static function _findSubsetInArray(array $find, array $array) {
		reset($array);
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

	/*
	static function extractWorkersUsingClassifier(&$tokens, &$words, &$meta) {
		$classifier = DevblocksPlatform::getBayesClassifierService();
		
		// [TODO] Strip possessive? (e.g. Dan's)
		
		$text = str_replace("'s",'', implode(' ',array_filter($tokens, function($token) {
			return !('[' == substr($token,0,1));
		})));
		
		$result = $classifier::predict($text, 3);
		
		//var_dump($result);
		
		if(isset($result['prediction']) && is_array($result['prediction'])) {
			$terms = $classifier::getNGramsForClass($result['prediction']['classification']['id']);
			
			// [TODO] Add possessive
			foreach($terms as $term) {
				$terms[] = sprintf("%s's", $term);
			};
			
			// [TODO] Force this to whole string tokens
			// [TODO] Ignore pointless terms (salutations, etc)?
			// [TODO] Sort desc by n, len 
			
			$hits = array_intersect($tokens, $terms);
			
			foreach($hits as $idx => $token)
				$tokens[$idx] = '{worker}';
		}
		
		// [TODO] We want to keep track of the worker's actual ID
		// [TODO] Normalize the worker name
		
		$worker_refinements = [
			['{worker}','{worker}','{worker}','{worker}'],
			['{worker}','{worker}','{worker}'],
			['{worker}','{worker}'],
			['{worker}'],
		];
		
		foreach($worker_refinements as $find) {
			while(false !== ($key = self::_findSubsetInArrayKeys($find, $tokens))) {
				array_splice($tokens, $key, count($find), ['[worker]']);
				$slice = array_slice($words, $key, count($find));
				array_splice($words, $key, count($find), implode(' ', $slice));
				$meta[$key] = $result['prediction']['classification']['attribs'];
			}
		}
		
		// Revert unused intermediate tokens
		array_walk($tokens, function(&$token, $idx) use (&$words) {
			if('{' == substr($token,0,1))
				$token = $words[$idx];
		});
		
		//var_dump([$tokens, $words]);
		
		return $tokens;
	}
	*/
	
	/*
	static function combineConditionalProbabilities($probs) {
		$AB = 1; // probabilities: A*B*C...
		$ZY = 1; // compliments: (1-A)*(1-B)*(1-C)...
		
		foreach($probs as $p) {
			$AB *= $p;
			$ZY *= (1-$p);
		}

		$combined_p = $AB / ($AB + $ZY);
		return $combined_p;
	}
	*/
	
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
	
	static function getNGramsForClass($class_id, $n=0, $oper='>') {
		$db = DevblocksPlatform::getDatabaseService();
		
		// Validate
		switch($oper) {
			case '=':
			case '!=':
			case '>':
			case '>=':
			case '<':
			case '<=':
			case '<>':
				break;
				
			default:
				return false;
		}
		
		//$sql = sprintf("SELECT id, token FROM classifier_ngram WHERE n %s %d AND class_id = %d",
		$sql = sprintf("SELECT id, token FROM classifier_ngram WHERE id IN (SELECT token_id FROM classifier_ngram_to_class WHERE class_id = %d) AND n %s %d",
			$class_id,
			$oper,
			$n
		);
		$results = $db->GetArraySlave($sql);
		
		return array_column($results, 'token', 'id');
	}
	
	static function clearModel($classifier_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		DAO_Classifier::update($classifier_id, array(
			DAO_Classifier::DICTIONARY_SIZE => 0,
			DAO_Classifier::UPDATED_AT => time(),
		));
		
		$db->ExecuteMaster(sprintf("UPDATE classifier_class SET training_count = 0, dictionary_size = 0 WHERE classifier_id = %d", $classifier_id));
		
		$db->ExecuteMaster(sprintf("DELETE FROM classifier_ngram_to_class WHERE class_id IN (SELECT id FROM classifier_class WHERE classifier_id = %d)", $classifier_id));
		
		return true;
	}
	
	static function train($text, $classifier_id, $class_id, $delta=false) {
		$db = DevblocksPlatform::getDatabaseService();
		
		// Convert tags to tokens
		
		$tagged_text = preg_replace(array_keys(self::$TAGS_TO_TOKENS), self::$TAGS_TO_TOKENS, $text);
		
		// Tokenize words
		// [TODO] Apply filtering based on classifier
		
		// [TODO] Unigrams
		$tokens = self::tokenizeWords($tagged_text);
		//$tokens = self::tokenizeStrings($tagged_text);
		
		// Remove stop words
		// [TODO] Make this configurable
		//$tokens = array_diff($tokens, self::$STOP_WORDS_EN);
		
		// Generate ngrams (if configured)
		// [TODO] Make this configurable
		
		if(false) {
			$ngrams = self::getNGrams(implode(' ', $tokens), 2);
				
			array_walk($ngrams, function($ngram) use (&$tokens) {
				$tokens[] = implode(' ', $ngram);
			});
		}
		
		// [TODO] Don't care about frequency in a single example, just over # examples
		//$words = array_count_values($tokens);
		$words = array_fill_keys($tokens, 1);
		
		$values = array_fill_keys(array_keys($words), 0);
		
		array_walk($values, function(&$v, $word) use ($db){
			$v = sprintf("(%s, %d)",
				$db->qstr($word),
				count(explode(' ', $word))
			);
		});
		
		// Save any new unigrams
		$db->ExecuteMaster(sprintf("INSERT IGNORE INTO classifier_ngram (token, n) VALUES %s",
			implode(',', $values)
		));
		
		// Pull the IDs for all unigrams in this text
		$results = $db->GetArrayMaster(sprintf("SELECT id, token FROM classifier_ngram WHERE token IN (%s)",
			implode(',', $db->qstrArray(array_keys($words)))
		));
		$token_ids = array_column($results, 'id', 'token');
		
		$values = [];
		
		foreach($token_ids as $token => $token_id) {
			$values[] = sprintf("(%d,%d,%d)",
				$token_id, $class_id, $words[$token]
			);
		}
		
		if(!empty($values)) {
			$sql = sprintf("INSERT INTO classifier_ngram_to_class (token_id, class_id, training_count) ".
				"VALUES %s ".
				"ON DUPLICATE KEY UPDATE training_count=training_count+VALUES(training_count)",
				implode(',', $values)
			);
			$db->ExecuteMaster($sql);
			
			if(!$delta) {
				self::updateCounts($classifier_id);
			}
			
			// [TODO] Invalidate caches
		}
	}
	
	static function updateCounts($classifier_id) {
		$db = DevblocksPlatform::getDatabaseService();

		$sql = sprintf("SET @class_ids := (SELECT GROUP_CONCAT(id) FROM classifier_class WHERE classifier_id = %d)",
			$classifier_id
		);
		$db->ExecuteMaster($sql);

		$sql = sprintf("UPDATE classifier_class SET dictionary_size = (SELECT COUNT(DISTINCT token_id) FROM classifier_ngram_to_class WHERE class_id=classifier_class.id), training_count = (SELECT count(id) FROM classifier_example WHERE class_id=classifier_class.id) WHERE FIND_IN_SET(id, @class_ids)");
		$db->ExecuteMaster($sql);
		
		$sql = sprintf("UPDATE classifier SET dictionary_size = (SELECT COUNT(DISTINCT token_id) FROM classifier_ngram_to_class WHERE FIND_IN_SET(class_id, @class_ids)) WHERE id = %d",
			$classifier_id
		);
		$db->ExecuteMaster($sql);
	}
	
	private static function _tagEntitySynonyms($labels, $tag, $words, &$tags) {
		foreach($labels as $label=> $synonyms) {
			foreach($synonyms as $synonym) {
				$stokens = self::tokenizeWords($synonym);
				$hits = array_intersect($words, $stokens);
				
				// Only if we match the entire phrase
				if(count($hits) == count($stokens))
				foreach($hits as $idx => $token) {
					$context_idx = sprintf('{%s}', $tag);
					if(!isset($tags[$idx][$context_idx]))
						$tags[$idx][$context_idx] = [];
					$tags[$idx][$context_idx][$label] = $label;
				}
			}
		}
	}
	
	public static function tag($words) {
		if(!is_array($words))
			return [];
		
		$tags = array_fill_keys(array_keys($words), []);
		$db = DevblocksPlatform::getDatabaseService();
		
		/**
		 * Punctuation
		 */
		
		array_walk($words, function($word, $idx) use (&$tags) {
			if($word == '?') {
				$tags[$idx]['{question}'] = $word;
			} else if($word == "'s") {
				$tags[$idx]['{possessive}'] = $word;
			}
		});
		
		/**
		 * Numbers
		 */
		
		array_walk($words, function($word, $idx) use (&$tags) {
			if(is_numeric($word) || (false !== ($hits = array_intersect([$word], array_merge(self::$NUM_ORDINAL, self::$NUM_WORDS))) && $hits)) {
				$tags[$idx]['{number}'] = $word;
			
				if($word >= 1900 && $word <= 2100)
					$tags[$idx]['{year}'] = $word;
			}
		});
		
		/**
		 * Dates
		 */
		
		array_walk($words, function($word, $idx) use (&$tags) {
			if(preg_match('#^\d{1,4}[/-]\d{1,2}[/-]\d{1,4}$#', $word))
				$tags[$idx]['{date}'] = $word;
		});
		
		$hits = array_intersect($words, array_merge(self::$MONTHS_SHORT, self::$MONTHS_LONG));
		foreach($hits as $idx => $token)
			$tags[$idx]['{month}'] = $token;
		
		$hits = array_intersect($words, array_merge(self::$DAYS_NTH, self::$DAYS_SHORT, self::$DAYS_LONG, self::$DAYS_LONG_PLURAL, self::$DAYS_REL));
		foreach($hits as $idx => $token)
			$tags[$idx]['{day}'] = $token;
			
		$hits = array_intersect($words, ['next', 'previous', 'prev', 'last', 'past', 'prior', 'this']);
		foreach($hits as $idx => $token)
			$tags[$idx]['{unit_rel}'] = $token;
		
		$hits = array_intersect($words, self::$DATE_UNIT);
		foreach($hits as $idx => $token)
			$tags[$idx]['{date_unit}'] = $token;
		
		$hits = array_intersect($words, self::$TIME_UNITS);
		foreach($hits as $idx => $token)
			$tags[$idx]['{time_unit}'] = $token;
		
		/**
		 * Times
		 */
		
		$hits = array_intersect($words, self::$TIME_REL);
		foreach($hits as $idx => $token)
			$tags[$idx]['{time_rel}'] = $token;
		
		$hits = array_intersect($words, self::$TIME_MERIDIEM);
		foreach($hits as $idx => $token)
			$tags[$idx]['{time_meridiem}'] = $token;
		
		// Times (5pm, 8a)
		array_walk($words, function(&$token, $idx) use (&$tags) {
			if(preg_match('#^\d+(a|am|p|pm|a.m|p.m){1}$#', $token))
				$tags[$idx]['{time}'] = $token;
		});
		
		// Times (hh:ii:ss)
		array_walk($words, function(&$token, $idx) use (&$tags) {
			if(preg_match('#^\d+\:\d+\s*(a|p|am|pm|a.m|p.m){0,1}$#', $token))
				$tags[$idx]['{time}'] = $token;
		});
		
		/**
		 * Availability (busy, available)
		 */
		
		$availability = [
			'available' => self::$AVAIL_FREE,
			'busy' => self::$AVAIL_BUSY,
		];
		
		self::_tagEntitySynonyms($availability, 'avail', $words, $tags);
		
		/**
		 * Contexts (tasks, tickets, etc)
		 */
		
		$contexts = [];
		$aliases = Extension_DevblocksContext::getAliasesForAllContexts();
		
		foreach($aliases as $alias => $context) {
			if(!isset($contexts[$context]))
				$contexts[$context] = [];
			
			$contexts[$context][] = $alias;
		}
		
		self::_tagEntitySynonyms($contexts, 'context', $words, $tags);
		
		/**
		 * Statuses (open, waiting, closed)
		 */
		
		$statuses = [
			'open' => [
				'active',
				'incomplete',
				'open',
				'unfinished',
				'unresolved',
			],
			'waiting' => [
				'pending',
				'waiting',
				'waiting for reply',
			],
			'closed' => [
				'closed',
				'complete',
				'completed',
				'finished',
				'resolved',
			],
			'deleted' => [
				'deleted'
			],
		];
		
		self::_tagEntitySynonyms($statuses, 'status', $words, $tags);
		
		/**
		 * Contact methods
		 */
			
		$contact_methods = [
			'email' => [
				'email',
				'emails',
				'email address',
				'email addresses',
			],
			'mobile' => [
				'cell',
				'cells',
				'cell phone',
				'cell phones',
				'cellphone',
				'cellphones',
				'mobile',
				'mobiles',
				'mobile phone',
				'mobile phones',
			],
			'phone' => [
				'phone',
				'phones',
			],
			'website' => [
				'site',
				'sites',
				'website',
				'websites',
				'url',
				'urls',
			],
			'address' => [
				'address',
				'addresses',
				'mailing address',
				'mailing addresses',
				'street address',
				'street addresses',
			],
		];
		
		self::_tagEntitySynonyms($contact_methods, 'contact_method', $words, $tags);
		
		/**
		 * Email
		 */
		// [TODO]
		
		/**
		 * IP Addresses
		 */
		
		array_walk($words, function(&$token, $idx) use (&$tags) {
			if(preg_match('#^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$#', $token))
				$tags[$idx]['{ip}'] = $token;
		});
		
		// [TODO] Aliases (WGM -> Webgroup Media)
		
		/**
		 * Entities (worker, contact, org)
		 */
		
		// [TODO] Always tag 'my' as the current worker
		
		$lookup = implode(' ', $words);
		
		$sql = implode(' UNION ALL ', [
			sprintf("(SELECT context, id, name FROM lookup_entity WHERE MATCH (name) AGAINST (%s IN NATURAL LANGUAGE MODE) AND context = 'worker' LIMIT 5)",
				$db->qstr($lookup)
			),
			sprintf("(SELECT context, id, name FROM lookup_entity WHERE MATCH (name) AGAINST (%s IN NATURAL LANGUAGE MODE) AND context = 'org' LIMIT 5)",
				$db->qstr($lookup)
			),
			sprintf("(SELECT context, id, name FROM lookup_entity WHERE MATCH (name) AGAINST (%s IN NATURAL LANGUAGE MODE) AND context = 'contact' LIMIT 5)",
				$db->qstr($lookup)
			),
		]);
		
		$results = $db->GetArraySlave($sql);
		$hits = [];
		
		foreach($results as $idx => $result) {
			$name = self::tokenizeWords($result['name']);
			
			while(!empty($name)) {
				if(false !== ($pos = self::_findSubsetInArray($name, $words))) {
					$hits[$idx] = array_combine(range($pos, $pos + count($name) - 1), $name);
					break;
				}
				array_pop($name);
			}
		}
		
		//var_dump($hits);
		
		foreach($hits as $pos => $hit) {
			$result = $results[$pos];
			$context_idx = '{' . $result['context'] . '}';
			
			foreach(array_keys($hit) as $idx) {
				if(!isset($tags[$idx][$context_idx]))
					$tags[$idx][$context_idx] = [];
				
				$tags[$idx][$context_idx][$result['id']] = $result['name'];
			}
		}
		
		self::_disambiguateActorTags($tags);
		
		return $tags;
	}
	
	private static function _disambiguateActorTags(&$tags) {
		$idx = 0;
		
		$contexts = [
			'worker' => '{worker}',
			'contact' => '{contact}',
			'org' => '{org}',
		];
		
		while($idx < count($tags)) {
			$hits = array_intersect($contexts, array_keys($tags[$idx]));

			if(empty($hits)) {
				$idx++;
				continue;
			}
			
			$start_idx = $idx;
			$candidates = [];
			
			// Record stats about each candidate so we can compare them
			foreach($hits as $context => $tag) {
				if(isset($tags[$idx][$tag]))
				foreach($tags[$idx][$tag] as $candidate_id => $candidate_label) {
					$idx = $start_idx;
					$cand_key = sprintf('%s:%d', $context, $candidate_id);
					$max = count(self::tokenizeWords($candidate_label));
					
					$candidates[$cand_key] = [
						'n' => 1,
						'max' => $max,
					];
					
					$n = 1;
					
					while(isset($tags[++$idx])) {
						if(!isset($tags[$idx][$tag][$candidate_id]))
							break;
						
						$n++;
					}
					
					$candidates[$cand_key]['n'] = $n;
					
					$fit = $n / $candidates[$cand_key]['max'];
					$candidates[$cand_key]['fit'] = $fit;
				}
			}
			
			$idx = $start_idx;
			
			// Find the longest match and the highest coverage match
			$longest = max(array_column($candidates, 'n'));
			$best_fit = max(array_column($candidates, 'fit'));
			
			// Reject the least likely candidates
			foreach($candidates as $key => $candidate) {
				if($candidate['n'] < $longest || $candidate['fit'] < $best_fit) {
					list($context, $candidate_id) = explode(':', $key);
					$tag = $contexts[$context];
					
					// Remove rejected candidate chains
					for($x=$idx; $x < $idx + $candidate['max']; $x++) {
						unset($tags[$x][$tag][$candidate_id]);
					}
					
					if(empty($tags[$x][$tag]))
						unset($tags[$x][$tag]);
				}
			}
			
			// Remove other tag starts in the rest of our range
			for($x=$idx+1; $x < $idx + $longest; $x++) {
				if(isset($tags[$x]))
				foreach($tags[$x] as $tag => $members) {
					if(isset($tags[$idx][$tag])) {
						$members = array_intersect($tags[$idx][$tag], $members);
						
					} else {
						if(is_array($members))
						foreach(array_keys($members) as $id) {
							$y = $x;
							
							while(isset($tags[$y][$tag][$id])) {
								unset($tags[$y][$tag][$id]);
								$y++;
							}
						}
					}
					
					if(empty($tags[$x][$tag]))
						unset($tags[$x][$tag]);
				}
			}
			
			$idx += $longest + 1;
		}
	}
	
	private static function _tagToEntity($context, &$words, &$tags, &$entities) {
		$idx = 0;
		$tag = sprintf('{%s}', $context);
		while($idx < count($tags)) {
			if(isset($tags[$idx][$tag])) {
				$from_idx = $idx;
				$candidates = array_keys($tags[$idx][$tag]);
				
				$to_idx = $idx+1;
				while(isset($tags[$to_idx]) && isset($tags[$to_idx][$tag])) {
					foreach($tags[$to_idx][$tag] as $id => $label) {
						if(!isset($tags[$to_idx]) || !isset($tags[$to_idx][$tag]))
							break 2;
						
						$matches = array_intersect($candidates, array_keys($tags[$to_idx][$tag]));
						
						if(empty($matches)) {
							break 2;
						}
						
						$candidates = $matches;
						$idx++;
						$to_idx++;
					}
				}

				$to_idx--;
				
				$range = array_slice($words, $from_idx, $to_idx - $from_idx + 1, true);
				
				if(!empty($candidates)) {
					$entity = [
						'range' => $range,
						'value' => array_combine($candidates, array_fill(0, count($candidates), implode(' ', $range))),
					];
					
					if(!isset($entities[$context]))
						$entities[$context] = [];
					
					$entities[$context][$from_idx] = $entity;
				}
			}
			
			$idx++;
		}
	}
	
	private static function _sequenceToEntity($sequences, $entity_name, $words, &$tags, &$entities) {
		foreach($sequences as $seq) {
			$seq = explode(' ', $seq);
			
			$left = $seq;
			$found = false;
			
			for($idx = 0; $idx < count($tags); $idx++) {
				$k = $left[0];
				$pass = false;
				
				if('{' == substr($k, 0, 1)) {
					if(isset($tags[$idx][$k]))
						$pass = true;
				} else {
					if($words[$idx] == $k)
						$pass = true;
				}
				
				if($pass) {
					array_shift($left);
					//var_dump([$words[$idx], $k, $left]);
					
					if(false === $found)
						$found = $idx;
				} else {
					$left = $seq;
					$found = false;
				}
				
				if(empty($left)) {
					$left = $seq;
					
					if(!isset($entities[$entity_name]))
						$entities[$entity_name] = [];
					
					$range = array_slice($words, $found, count($seq), true);
					
					$entity = [
						'range' => $range,
						'sequence' => array_combine(array_keys($range), $seq),
					];
					
					// Wipe out our tags on these tokens
					array_splice($tags, $found, count($seq), array_fill(0, count($seq), []));
					
					$entities[$entity_name][] = $entity;
				}
			}
		}
	}
	
	// [TODO] This can determine how many entities we expect too
	public static function extractNamedEntities(array $words, array $tags, array $types=[]) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$entities = [];
		
		// [TODO] Learn 'ago'
		// [TODO] Convert 'a' {date_unit} to {number:1}
		// [TODO] Lemmatize from/for/... ?
		if(in_array('date', $types)) {
			$sequences = [
				'from {number} {date_unit} ago',
				'for {number} {date_unit} ago',
				'since {number} {date_unit} ago',
				'from {number} {date_unit} before {day}',
				'from {number} {date_unit} before {unit_rel} {day}',
				'from a {date_unit} before {day}',
				'from a {date_unit} before {unit_rel} {day}',
				'for {number} {date_unit} before {day}',
				'for {number} {date_unit} before {unit_rel} {day}',
				'for a {date_unit} before {day}',
				'for a {date_unit} before {unit_rel} {day}',
				'since {unit_rel} {day}',
				'since {unit_rel} {date_unit} ago',
				'since {unit_rel} {date_unit}',
				'since {day} {date_unit}',
				'in the {unit_rel} {number} {date_unit}',
				'for the {unit_rel} {number} {date_unit}',
				'for {day} {number} {month} {year}',
				'for {day} {month} {number} {year}',
				'for {day} {month} {year}',
				'for {month} {number} {year}',
				'for {month} {day} {year}',
				'for {number} {month} {year}',
				'on {day} {number} {month} {year}',
				'on {day} {month} {number} {year}',
				'on the {day} of {month} {year}',
				'on {day} {unit_rel} {date_unit}',
				'{day} {unit_rel} {date_unit}',
				'{unit_rel} {date_unit} on {day}',
				'from {month} {number} {year}',
				'from {unit_rel} {day}',
				'from {month} {year}',
				'from {month}',
				'to {month} {number} {year}',
				'to {month} {year}',
				'on {month} {number} {year}',
				'on {month} {day} {year}',
				'on {month} {day}',
				'on {month} {year}',
				'on {month} {number}',
				'on {number} {month} {year}',
				'on {number} {month}',
				'{month} {number} {year}',
				'{month} {day}',
				'{month} {year}',
				'{month} {number}',
				'on the {number} {number}',
				'on the {number}',
				'on the {day}',
				'in {month}',
				'in {number} {day}',
				'in {number} {date_unit}',
				'{number} {date_unit} ago',
				'{unit_rel} {date_unit}',
				'{unit_rel} {day}',
				'since {month}',
				'since {day}',
				'{unit_rel} {date_unit}',
				'{unit_rel} {day}',
				'on {day}',
				'on {date}',
				'for {date}',
				'{day}',
				'{date}',
			];
			
			self::_sequenceToEntity($sequences, 'date', $words, $tags, $entities);
		}
		
		if(in_array('time', $types)) {
			$sequences = [
				'at {number} {number} {number} {time_meridiem}',
				'at {number} {number} {number}',
				'at {number} {number} {time_meridiem}',
				'at {number} {number}',
				'at {number} {time_meridiem}',
				'at {number} in the {time_rel}',
				'in {number} {time_unit}',
				'at {time} {time_meridiem}',
				'at {number} {time_meridiem}',
				'at {time}',
				'at {number}',
				'from {time} {time_meridiem}',
				'from {number} {time_meridiem}',
				'from {time}',
				'to {time} {time_meridiem}',
				'to {number} {time_meridiem}',
				'to {time}',
				'until {time} {time_meridiem}',
				'until {number} {time_meridiem}',
				'until {time}',
				'at {time_rel}',
				'in the {time_rel}',
				'{time_rel}',
				'{time}',
			];
			
			self::_sequenceToEntity($sequences, 'time', $words, $tags, $entities);
		}
		
		if(in_array('duration', $types)) {
			$sequences = [
				'for the {unit_rel} {number} {date_unit}',
				'for the {unit_rel} {number} {time_unit}',
				'for {number} {date_unit} {number} {date_unit}',
				'for {number} {date_unit} {number} {time_unit}',
				'for {number} {time_unit} {number} {time_unit}',
				'for {number} {date_unit}',
				'for {number} {time_unit}',
				'for a {date_unit}',
				'for a {time_unit}',
				'for an {date_unit}',
				'for an {time_unit}',
				'{number} {date_unit}',
				'{number} {time_unit}',
			];
			
			self::_sequenceToEntity($sequences, 'duration', $words, $tags, $entities);
		}
		
		if(in_array('avail', $types))
			self::_tagToEntity('avail', $words, $tags, $entities);
		
		if(in_array('contact_method', $types))
			self::_tagToEntity('contact_method', $words, $tags, $entities);
		
		if(in_array('context', $types))
			self::_tagToEntity('context', $words, $tags, $entities);
		
		if(in_array('status', $types))
			self::_tagToEntity('status', $words, $tags, $entities);

		if(in_array('worker', $types))
			self::_tagToEntity('worker', $words, $tags, $entities);
		
		if(in_array('contact', $types))
			self::_tagToEntity('contact', $words, $tags, $entities);

		if(in_array('org', $types))
			self::_tagToEntity('org', $words, $tags, $entities);
		
		// If we're finding a contact, use '{contact}+ at {org}' and '{contact}+ @ {org}' patterns
		// If we have both an org and a contact described
		if(isset($entities['contact']) && isset($entities['org'])) {
			foreach($entities['org'] as $from_idx => $org) {
				// If we have room for at least two words before the org, and the joiner before {org} is [@, at, from, of]
				if($from_idx - 2 >= 0 && in_array($words[$from_idx-1], ['at','@','from','of'])) {
					// If any contact ends at the position before the joiner
					foreach($entities['contact'] as $contact_idx => $contact) {
						$range = array_keys($contact['range']);
						
						if(end($range) == $from_idx - 2) {
							// Set the range of the contact through the org
							$contact['range'] = $contact['range'] + [$from_idx-1 => $words[$from_idx-1]] + $org['range'];
							$entities['contact'][$contact_idx]['range'] = $contact['range'];
							
							// Find a matching contact at the org
							$contact_find = implode(' ', array_slice($words, $contact_idx, end($range) - $contact_idx + 1));
	
							$sql = sprintf("SELECT id, name FROM lookup_entity WHERE MATCH (name) AGAINST (%s IN NATURAL LANGUAGE MODE) AND context = 'contact' AND id IN (SELECT id FROM contact WHERE org_id = %d) LIMIT 5",
								$db->qstr($contact_find),
								key($org['value'])
							);
							
							$res = $db->GetArraySlave($sql);
							
							// If we found no matches, the whole contact + org is invalid
							if(empty($res)) {
								unset($entities['contact'][$contact_idx]);
								unset($entities['org'][$from_idx]);
								
							// If we found contacts at that org, inject them
							} else {
								$candidates = array_column($res, 'name', 'id');
								$entities['contact'][$contact_idx]['value'] = $candidates;
								
								unset($entities['org'][$from_idx]);
							}
							
							break 1;
						}
					}
				}
			}
			
			// If we have no orgs left, remove the parent
			if(empty($entities['contact']))
				unset($entities['contact']);
			
			if(empty($entities['org']))
				unset($entities['org']);
		}
		
		if(in_array('remind', $types)) {
			$tokens = $words;
			
			// [TODO] Windowing (3 words before and after?)
			// [TODO] We can use this for declare.entity.alias and others
			
			foreach($entities as $entity_type => $results) {
				foreach($results as $result) {
					if(isset($result['range']))
					foreach(array_keys($result['range']) as $key) {
						$tokens[$key] = sprintf('{%s}', $entity_type);
					}
				}
			}
			
			$text = implode(' ', $tokens);
			
			// [TODO] These patterns should be learnable
			// [TODO] These can be optimized as a tree
			$patterns = [
				'remind me to * \{date\}',
				'remind me to * \{time\}',
				'remind me to *$',
				'add * to my reminders',
				'remind me about * \{date\}',
				'remind me about * \{time\}',
				'remind me about *$',
				'remind me * \{date\}',
				'remind me * \{time\}',
				'remind me *$',
			];
			
			foreach($patterns as $pattern) {
				$pattern = str_replace('\{remind\}', '(.*?)', DevblocksPlatform::strToRegExp($pattern, true, false));
				$matches = array();
				
				if(preg_match($pattern, $text, $matches)) {
					$terms = explode(' ', $matches[1]);
					if(false !== ($pos = self::_findSubsetInArray($terms, $words))) {
						if(!isset($entities['event']))
							$entities['event'] = [];
						
						$remind = [
							'range' => array_combine(range($pos, $pos+count($terms)-1), $terms),
						];
							
						$entities['remind'][] = $remind;
						break;
					}
				}
			}
		}
		
		// [TODO] Redundant with 'alias' and 'remind'
		if(in_array('event', $types)) {
			$tokens = $words;
			
			// [TODO] Windowing (3 words before and after?)
			// [TODO] We can use this for declare.entity.alias and others
			
			foreach($entities as $entity_type => $results) {
				foreach($results as $result) {
					if(isset($result['range']))
					foreach(array_keys($result['range']) as $key) {
						$tokens[$key] = sprintf('{%s}', $entity_type);
					}
				}
			}
			
			$text = implode(' ', $tokens);
			
			// [TODO] These patterns should be learnable
			// [TODO] These can be optimized as a tree
			// [TODO] Lemm 'called' -> named, titled
			// [TODO] I'm having [lunch] (at) ...
			// [TODO] I'll be at [lunch] ...
			$patterns = [
				'schedule * \{date\}',
				'schedule * \{time\}',
				'add * to my calendar',
				'create an event called * \{date\}',
				'create an event called * \{time\}',
			];
			
			foreach($patterns as $pattern) {
				$pattern = str_replace('\{event\}', '(.*?)', DevblocksPlatform::strToRegExp($pattern, true, false));
				$matches = array();
				
				if(preg_match($pattern, $text, $matches)) {
					$terms = explode(' ', $matches[1]);
					if(false !== ($pos = self::_findSubsetInArray($terms, $words))) {
						if(!isset($entities['event']))
							$entities['event'] = [];
						
						$event = [
							'range' => array_combine(range($pos, $pos+count($terms)-1), $terms),
						];
						
						$entities['event'][] = $event;
						break;
					}
				}
			}
		}
		
		// [TODO] This shares everything but $patterns in common with alias
		if(in_array('alias', $types)) {
			$tokens = $words;
			
			// [TODO] Windowing (3 words before and after?)
			// [TODO] We can use this for declare.entity.alias and others
			
			foreach($entities as $entity_type => $results) {
				foreach($results as $result) {
					if(isset($result['range']))
					foreach(array_keys($result['range']) as $key) {
						$tokens[$key] = sprintf('{%s}', $entity_type);
					}
				}
			}
			
			$text = implode(' ', $tokens);
			
			// [TODO] These patterns should be learnable
			// [TODO] These can be optimized as a tree
			$patterns = [
				'* refers to ',
				'* is another name for ',
				'is also known as *$',
				'* aka ',
				'i say * i mean ',
			];
			
			foreach($patterns as $pattern) {
				$pattern = str_replace('\{alias\}', '(.*?)', DevblocksPlatform::strToRegExp($pattern, true, false));
				$matches = array();
				
				if(preg_match($pattern, $text, $matches)) {
					$terms = explode(' ', $matches[1]);
					if(false !== ($pos = self::_findSubsetInArray($terms, $words))) {
						if(!isset($entities['event']))
							$entities['event'] = [];
						
						$alias = [
							'range' => array_combine(range($pos, $pos+count($terms)-1), $terms),
						];
							
						$entities['alias'][] = $alias;
						break;
					}
				}
			}
		}
		
		return $entities;
	}
	
	static function predict($text, $classifier_id) {
		$db = DevblocksPlatform::getDatabaseService();
		
		// Load all classes
		// [TODO] This would be cached between training
		// [TODO] Add the training sum to the classifier
		$results = $db->GetArrayMaster('SELECT id, name, dictionary_size, params_json FROM classifier');
		$classifiers = array_column($results, null, 'id');
		//var_dump($classifiers);

		if(!isset($classifiers[$classifier_id]))
			return false;
		
		// Load the frequency of classes for this classifier from the database
		// [TODO] This would be cached between training
		$results = $db->GetArrayMaster(sprintf('SELECT id, name, training_count, dictionary_size, attribs_json FROM classifier_class WHERE classifier_id = %d', $classifier_id));
		$classes = array_column($results, null, 'id');
		$class_freqs = array_column($results, 'training_count', 'id');
		
		array_walk($classes, function(&$class) {
			@$attribs = json_decode($class['attribs_json'], true);
			unset($class['attribs_json']);
			if(!is_array($attribs))
				$attribs = [];
			$class['attribs'] = $attribs;
		});
		//var_dump($classes);
		
		$raw_words = self::tokenizeWords($text);
		
		$tags = self::tag($raw_words);
		//var_dump($tags);
		
		// [TODO] Disambiguate tags once
		
		$class_data = [];
		$unique_tokens = [];
		
		// [TODO] Can we limit which classes even check tokens?
		foreach($classes as $class_id => $class) {
			$tokens = $words = $raw_words;
			$types = [];
			
			// [TODO] Filter by class
			// [TODO] Convert this to param handling
			// [TODO] This needs to come from classification params
			switch($class_id) {
				case 4: // schedule.add
					$types = ['event', 'date','time','duration'];
					break;
					
				case 5: // schedule.check
					$types = ['avail', 'date', 'time', 'worker'];
					break;
					
				case 14: // reminder.add
					$types = ['date', 'time', 'duration', 'remind'];
					break;
					
				case 15: // worklist.search
					$types = ['context', 'org', 'status', 'worker', 'date'];
					break;
					
				case 16: // declare.entity.alias
					$types = ['alias', 'org', 'contact', 'worker'];
					break;
					
				case 27: // ask.entity.field
					// [TODO] $limit based on class?
					$types = ['worker','contact','org','contact_method'];
					break;
					
				case 30: // ask.org.roster
					$types = ['org'];
					break;
					
				default:
					break;
			}
			
			$entities = self::extractNamedEntities($raw_words, $tags, $types);
			
			foreach($entities as $entity_type => $results) {
				foreach($results as $result_id => $result) {
					foreach($result['range'] as $pos => $val) {
						$tokens[$pos] = sprintf('[%s]', $entity_type);
					}
				}
			}
			
			// [TODO] Don't include some tokens (if not required)
			//var_dump($tokens);
			
			/*
			if(false) {
				$ngrams = self::getNGrams(implode(' ', $tokens), 2);
				
				array_walk($ngrams, function($ngram) use (&$tokens) {
					$tokens[] = implode(' ', $ngram);
				});
			}
			*/
			
			$class_data[$class_id] = [
				'tokens' => $tokens,
				'words' => $words,
				//'tags' => $tags,
				'token_counts' => array_count_values($tokens),
				'token_freqs' => array_fill_keys($tokens, 0),
				//'token_tfidf' => array_fill_keys($tokens, 0),
				'entities' => $entities,
				'p_class' => 0.0000,
				'p' => 0.0000,
			];
			
			$unique_tokens = array_replace($unique_tokens, array_flip($tokens));
		}
	
		$unique_tokens = array_keys($unique_tokens);
		
		// [TODO] Suppress stop words
		$unique_tokens = self::preprocessWordsRemoveStopWords($unique_tokens, self::$STOP_WORDS_EN);
		
		$corpus_freqs = [];
		
		// Bayes theorem: P(i=ask|x) = P(x|ask=1) * P(ask=1) / P(x|ask=0) * P(ask=0) + P(x|ask=1) * P(ask=1) ...
		
		// [TODO] Handle classifier options (stop words, etc)
		
		// Do a single query to pull all the tokens for the classifier
		
		if(empty($classes) || empty($unique_tokens)) {
			$results = array();
		} else {
			$sql = sprintf("SELECT u.token, utc.class_id, utc.training_count FROM classifier_ngram_to_class utc INNER JOIN classifier_ngram u ON (utc.token_id=u.id) WHERE utc.class_id IN (%s) AND u.token IN (%s)",
				implode(',', array_keys($classes)),
				implode(',', $db->qstrArray($unique_tokens))
			);
			
			$results = $db->GetArrayMaster($sql);
		}
		
		//var_dump($unique_tokens);
		//var_dump($results);
		
		//****** IDF *******
		
		/*
		$idf = [];
		
		foreach($results as $row) {
			@$count = intval($idf[$row['token']]);
 			$idf[$row['token']] = ++$count;
		}
		
		$num_classes = count($classes);
		
		array_walk($idf, function(&$val, $term) use ($num_classes) {
			$val = log($num_classes/$val);
		});
		
		//var_dump($idf);
		 */
		
		//****** IDF *******
		
		// Merge in the classification frequencies
		
		foreach($results as $row) {
			if(isset($class_data[$row['class_id']]['token_freqs'][$row['token']]))
				$class_data[$row['class_id']]['token_freqs'][$row['token']] = intval($row['training_count']);
				
			$corpus_freqs[$row['token']] = intval(@$corpus_freqs[$row['token']]) + intval($row['training_count']);
		}
		
		// Test each class
		
		$class_freqs_sum = array_sum($class_freqs);
		$class_probs = [];
		
		foreach($class_data as $class_id => $data) {
			
			// If we've never seen this class in training (or any class), we can't make any predictions
			if(0 == $class_freqs_sum || 0 == $classifiers[$classifier_id]['dictionary_size']) {
				$class_data[$class_id]['p'] = 0;
				continue;
			}
			
			// [TODO] If none of our tokens matched up, skip this intent
			// [TODO] If we uncomment this, it just picks something arbitrary anyway
			/*
			if(0 == array_sum($data['token_freqs'])) {
				$class_data[$class_id]['p'] = 0;
				continue;
			}
			*/
			
			// [TODO] Option for weighted classes 
			//$class_prob = $class_freqs[$class_id] / $class_freqs_sum;
			
			// [TODO] Option for equiprobable classes 
			$class_prob = 1/count($classes);
			
			$class_data[$class_id]['p_class'] = $class_prob;
			$probs = [];
			
			// Laplace smoothing
			// [TODO] How many examples had the term vs how many examples exist for this intent
			foreach($data['token_freqs'] as $token => $count) {
				$probs[$token] = ($count + 0.4) / (@$corpus_freqs[$token] + $classifiers[$classifier_id]['dictionary_size']);
			}
			
			$class_data[$class_id]['p'] = array_product($probs) * $class_prob;
			
		}
		
		$p_x = array_sum(array_column($class_data, 'p'));
		$results = array();
		
		// Normalize confidence scores
		foreach($class_data as $class_id => $data) {
			if(0 == $p_x) {
				$p = 0;
			} else {
				$p = $data['p'] / $p_x;
			}
			
			$results[$class_id] = $p;
		}
		
		arsort($results);
		
		$predicted_class_id = key($results);
		$predicted_class_confidence = current($results);
		
		/*
		if($predicted_class_confidence < 0.30) {
			$predicted_class_id = 2;
			$predicted_class_confidence = $results[2];
		}
		*/
		
		//var_dump($class_data[$predicted_class_id]);
		
		$params = [];
		
		foreach($class_data[$predicted_class_id]['entities'] as $entity_type => $results) {
			if(!isset($params[$entity_type]))
				$params[$entity_type] = [];
			
			foreach($results as $result_id => $result) {
				$param = [];
				
				switch($entity_type) {
					case 'alias':
						if(!isset($params[$entity_type]))
							$params[$entity_type] = [];
						
						$param_key = implode(' ', $result['range']);
						
						// [TODO] We should keep a case-sensitive version of the original tokenized string for params
						$params[$entity_type][$param_key] = [
							//'value' => implode(' ', array_intersect_key(explode(' ', $text), $result['range'])),
							'value' => implode(' ', $result['range']),
						];
						break;
					
					case 'avail':
						if(!empty($result['value']))
							$params[$entity_type] = array_merge($params[$entity_type], array_slice($result['value'], 0, 1));
						break;
						
					case 'contact':
						@$ids = array_keys($result['value']);
						
						if(empty($ids))
							break;
						
						$contacts = DAO_Contact::getIds($ids);
						
						$param_key = implode(' ', $result['range']);
						
						if(!isset($params['contact']))
							$params['contact'] = [];
						
						// Preserve order
						foreach($ids as $id) {
							//if(!isset($contacts[$id]) || isset($params['contact'][$id]))
							if(!isset($contacts[$id]))
								continue;
							
							$contact = $contacts[$id];
							
							if(!isset($params['contact'][$param_key]))
								$params['contact'][$param_key] = [];
							
							$params['contact'][$param_key][$contact->id] = [
								'id' => $contact->id,
								'email_id' => $contact->primary_email_id,
								'email' => $contact->getEmailAsString(),
								'first_name' => $contact->first_name,
								'full_name' => $contact->getName(),
								'gender' => $contact->gender,
								'language' => $contact->language,
								'last_name' => $contact->last_name,
								'location' => $contact->location,
								'mobile' => $contact->mobile,
								'org_id' => $contact->org_id,
								'org' => $contact->getOrgAsString(),
								'phone' => $contact->phone,
								'timezone' => $contact->timezone,
								'title' => $contact->title,
								'updated' => $contact->updated_at,
							];
						}
						break;
						
					case 'contact_method':
						if(!empty($result['value']))
							$params[$entity_type] = array_merge($params[$entity_type], array_slice($result['value'], 0, 1));
						break;
						
					case 'context':
						if(!empty($result['value']))
							$params[$entity_type] = array_merge($params[$entity_type], array_slice($result['value'], 0, 1));
						break;
						
					case 'date':
						$param_key = implode(' ', $result['range']);
						$date_words = $result['range'];
						$seq = $result['sequence'];
						
						// [TODO] normalize 'on the fifth' -> '5th'
						
						// Normalize wed/weds
						if(false !== ($hits = array_intersect($date_words, ['weds']))) {
							foreach($hits as $idx => $hit) {
								$date_words[$idx] = 'wed';
							}
						}
						
						// Normalize thu/thur/thurs
						if(false !== ($hits = array_intersect($date_words, ['thur', 'thurs']))) {
							foreach($hits as $idx => $hit) {
								$date_words[$idx] = 'thu';
							}
						}
						
						// In dates and times, tag 'a' and 'an' as a {number:1}
						if(false !== ($hits = array_intersect($seq, ['a','an']))) {
							foreach($hits as $idx => $hit) {
								$date_words[$idx] = '1';
								$seq[$idx] = '{number}';
							}
						}
						
						// Flip a relative number negative if _before_ an anchor
						if(false !== ($pos = self::_findSubsetInArray(['{number}','{date_unit}','before'], $seq))) {
							$date_words[$pos] = '-' . abs($date_words[$pos]);
							unset($date_words[$pos+2]);
							unset($seq[$pos+2]);
						}
						
						// Flip a relative number negative if _ago_ an anchor
						if(false !== ($pos = self::_findSubsetInArray(['{number}','{date_unit}','ago'], $seq))) {
							$date_words[$pos] = '-' . abs($date_words[$pos]);
							unset($date_words[$pos+2]);
							unset($seq[$pos+2]);
						}
						
						$date_string = implode(' ', array_intersect_key($date_words, array_filter($seq, function($seq) {
							return '{' == substr($seq, 0, 1);
						})));
						
						$date_string = str_replace(self::$DAYS_LONG_PLURAL, self::$DAYS_LONG, $date_string);
						
						// [TODO] Handle ranges
						if($date_string == 'this month') {
							$date_string = 'first day of this month';
							
						} else if($date_string == 'last month') {
							$date_string = 'first day of last month';
							
						} else if($date_string == 'this year') {
							$date_string = '1 Jan ' . date('Y');
							
						} else if($date_string == 'last year') {
							$date_string = '1 Jan ' . date('Y', strtotime('last year'));
							
						// [TODO] Verify the third position here is a date_unit
						} else if(preg_match('#^(past|last|prev|previous|prior) (\d+) (\w+)$#i', $date_string, $matches)) {
							$date_string = sprintf("-%d %s",
								$matches[2],
								$matches[3]
							);
							
						// [TODO] Verify the second position here is a date_unit
						} else if(preg_match('#^(past|last|prev|previous|prior) (\w+)$#i', $date_string, $matches)) {
							$date_string = sprintf("-1 %s",
								$matches[2]
							);
							
						// [TODO] Verify the third position here is a date_unit
						} else if(preg_match('#^(next) (\d+) (\w+)$#i', $date_string, $matches)) {
							$date_string = sprintf("+%d %s",
								$matches[2],
								$matches[3]
							);
						}
						
						//var_dump($date_string);
						
						if(!isset($params['date']))
							$params['date'] = [];
						
						$params['date'][$param_key] = [
							'date' => date('Y-m-d', strtotime($date_string)),
						];
						break;
						
					// [TODO] This can't handle "for 1 hr"
					case 'duration':
						$param_key = implode(' ', $result['range']);
						
						$dur_string = implode(' ', array_intersect_key($result['range'], array_filter($result['sequence'], function($seq) {
							return '{' == substr($seq, 0, 1);
						})));
						
						// [TODO] for time_unit we can use seconds.  For date_unit we could pick an absolute
						
						if(!isset($params['duration']))
							$params['duration'] = [];
						
						$params['duration'][$param_key] = [
							'secs' => strtotime($dur_string) - time(),
						];
						break;
						
					case 'event':
						if(!isset($params[$entity_type]))
							$params[$entity_type] = [];
						
						$param_key = implode(' ', $result['range']);
						
						// [TODO] We should keep a case-sensitive version of the original tokenized string for params
						$params[$entity_type][$param_key] = [
							//'value' => implode(' ', array_intersect_key(explode(' ', $text), $result['range'])),
							'value' => implode(' ', $result['range']),
						];
						break;
						
					case 'org':
						@$ids = array_keys($result['value']);
						
						if(empty($ids))
							break;
						
						$orgs = DAO_ContactOrg::getIds($ids);
						
						$param_key = implode(' ', $result['range']);
						
						if(!isset($params['org']))
							$params['org'] = [];
						
						foreach($ids as $id) {
							if(!isset($orgs[$id]) || isset($params['org'][$id]))
								continue;
							
							$org = $orgs[$id];
							
							if(!isset($params['org'][$param_key]))
								$params['org'][$param_key] = [];
							
							$params['org'][$param_key][$org->id] = [
								'id' => $org->id,
								'name' => $org->name,
								'street' => $org->street,
								'city' => $org->city,
								'postal' => $org->postal,
								'province' => $org->province,
								'country' => $org->country,
								'email' => $org->getEmailAsString(),
								'phone' => $org->phone,
								'website' => $org->website,
								'updated' => $org->updated,
							];
						}
						break;
						
					case 'remind':
						if(!isset($params[$entity_type]))
							$params[$entity_type] = [];
						
						$param_key = implode(' ', $result['range']);
						
						// [TODO] We should keep a case-sensitive version of the original tokenized string for params
						$params[$entity_type][$param_key] = [
							//'value' => implode(' ', array_intersect_key(explode(' ', $text), $result['range'])),
							'value' => implode(' ', $result['range']),
						];
						break;
						
					case 'status':
						if(!empty($result['value']))
							$params[$entity_type] = array_merge($params[$entity_type], array_slice($result['value'], 0, 1));
						break;
					
					case 'time':
						$param_key = implode(' ', $result['range']);
						
						$time_string = implode(' ', array_intersect_key($result['range'], array_filter($result['sequence'], function($seq) {
							return '{' == substr($seq, 0, 1);
						})));
						
						// [TODO] in the (morning/afternoon/evening/night)
						$time_string = preg_replace('#^(\d+) (morning)$#', '\1am', $time_string);
						$time_string = preg_replace('#^(\d+) (afternoon|evening)$#', '\1pm', $time_string);
						//$time_string = preg_replace('$#([12,1-4]) (night)$#', '\1am', $time_string);
						//$time_string = preg_replace('$#([5-11]) (night)$#', '\1pm', $time_string);
						
						/*
						switch($val) {
							case 'midnight':
								$val = '00:00';
								break;
							case 'morning':
								$val = '08:00';
								break;
							case 'noon':
								$val = '12:00';
								break;
							case 'afternoon':
								$val = '13:00';
								break;
							case 'evening':
								$val = '17:00';
								break;
							case 'night':
							case 'tonight':
								$val = '20:00';
								break;
						}
						
						// Normalize 8a 5:00p
						if(preg_match('#^([\d\:]+)\s*([ap])$#', $val, $matches)) {
							$val = $matches[1] . $matches[2] . 'm';
						}
						*/
						
						if(!isset($params['time']))
							$params['time'] = [];
						
						$params['time'][$param_key] = [
							'time' => date('H:i:s', strtotime($time_string)),
						];
						break;
						
					case 'worker':
						@$ids = array_keys($result['value']);
						
						if(empty($ids))
							break;
						
						$workers = DAO_Worker::getIds($ids);
						
						$param_key = implode(' ', $result['range']);
						
						if(!isset($params['worker']))
							$params['worker'] = [];
						
						foreach($ids as $id) {
							if(!isset($workers[$id]) || isset($params['worker'][$id]))
								continue;
							
							$worker = $workers[$id];
							
							if(!isset($params['worker'][$param_key]))
								$params['worker'][$param_key] = [];
							
							$params['worker'][$param_key][$worker->id] = [
								'id' => $worker->id,
								'at_mention_name' => $worker->at_mention_name,
								'email_id' => $worker->email_id,
								'email' => $worker->getEmailString(),
								'first_name' => $worker->first_name,
								'full_name' => $worker->getName(),
								'gender' => $worker->gender,
								'language' => $worker->language,
								'last_name' => $worker->last_name,
								'location' => $worker->location,
								'mobile' => $worker->mobile,
								'phone' => $worker->phone,
								'timezone' => $worker->timezone,
								'title' => $worker->title,
								'updated' => $worker->updated,
							];
						}
						break;
						
				}
			}
			
			if(empty($params[$entity_type]))
				unset($params[$entity_type]);
		}
		
		$prediction = [
			'prediction' => [
				'text' => $text,
				//'words' => $words,
				//'tags' => $tags,
				'classifier' => array_intersect_key($classifiers[$classifier_id],['id'=>true,'name'=>true]),
				'classification' => array_intersect_key($classes[$predicted_class_id],['id'=>true,'name'=>true,'attribs'=>true]),
				'confidence' => $predicted_class_confidence,
				'params' => $params
			]
		];
		
		//var_dump($prediction);
		
		return $prediction;
		
		/*
		// [TODO] Allow more than one prediction to be returned
		// [TODO] Configurable threshold
		if($predicted_class_confidence >= 0.20) {
		} else {
			// [TODO] Default intent
			return ['input'=>$text, 'prediction'=>null];
		}
		*/
	}
}