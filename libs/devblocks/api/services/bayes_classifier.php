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
	
	static function getEntities() {
		$entities = [
			'contact' => [
				'label' => 'Contact',
				'description' => 'The name of a contact',
			],
			'date' => [
				'label' => 'Date',
				'description' => 'July 9, 2019-09-29, the 1st',
			],
			'duration' => [
				'label' => 'Duration',
				'description' => 'for 5 mins, for 2 hours',
			],
			'number' => [
				'label' => 'Number',
				'description' => 'A number',
			],
			'org' => [
				'label' => 'Organization',
				'description' => 'The name of an organization',
			],
			'context' => [
				'label' => 'Record type',
				'description' => 'message, task, ticket, worker',
			],
			'status' => [
				'label' => 'Status',
				'description' => 'open, closed, waiting, completed, active',
			],
			'temperature' => [
				'label' => 'Temperature',
				'description' => '212F, 12C, 75 degrees, 32 F',
			],
			'time' => [
				'label' => 'Time',
				'description' => 'at 2pm, 08:00, noon, in the morning, from now, in hours',
			],
			'worker' => [
				'label' => 'Worker',
				'description' => 'The name of a worker',
			]
		];
		
		$custom_entities = DAO_ClassifierEntity::getAll();
		
		foreach($custom_entities as $entity) {
			$entities[DevblocksPlatform::strLower($entity->name)] = [
				'label' => $entity->name,
				'description' => $entity->description,
			];
		}
		
		return $entities;
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
	
	static function getNGramsForClass($class_id, $n=0, $oper='>') {
		$db = DevblocksPlatform::services()->database();
		
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
		$results = $db->GetArrayReader($sql);
		
		return array_column($results, 'token', 'id');
	}
	
	static function clearModel($classifier_id) {
		$db = DevblocksPlatform::services()->database();
		
		DAO_Classifier::update($classifier_id, array(
			DAO_Classifier::DICTIONARY_SIZE => 0,
			DAO_Classifier::UPDATED_AT => time(),
		));
		
		$db->ExecuteMaster(sprintf("UPDATE classifier_class SET training_count = 0, dictionary_size = 0 WHERE classifier_id = %d", $classifier_id));
		
		$db->ExecuteMaster(sprintf("DELETE FROM classifier_ngram_to_class WHERE class_id IN (SELECT id FROM classifier_class WHERE classifier_id = %d)", $classifier_id));
		
		return true;
	}
	
	static function verify($text) {
		if(empty($text))
			return false;
		
		// [TODO] Look for tags and verify them
		$tagged_text = preg_replace('#\{\{(.*?)\:(.*?)\}\}#','[${1}]', $text);
		
		if(false == (self::tokenizeWords($tagged_text)))
			return false;
		
		return true;
	}
	
	static function train($text, $classifier_id, $class_id, $delta=false) {
		$db = DevblocksPlatform::services()->database();
		
		// Convert tags to tokens
		
		$tagged_text = preg_replace('#\{\{(.*?)\:(.*?)\}\}#','[${1}]', $text);
		
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
				self::build($classifier_id);
			}
			
			// [TODO] Invalidate caches
		}
	}
	
	static function build($classifier_id) {
		self::_updateCounts($classifier_id);
		
		DAO_Classifier::clearCache();
		DAO_ClassifierClass::clearCache();
		DAO_ClassifierEntity::clearCache();
	}
	
	private static function _updateCounts($classifier_id) {
		$db = DevblocksPlatform::services()->database();

		$sql = sprintf("SET @class_ids := (SELECT GROUP_CONCAT(id) FROM classifier_class WHERE classifier_id = %d)",
			$classifier_id
		);
		$db->ExecuteMaster($sql);
		
		// Cache entity hints per classifier
		$sql = "UPDATE classifier_class SET entities = (SELECT GROUP_CONCAT(SUBSTRING(n.token,2,LENGTH(n.token)-2)) FROM classifier_ngram n INNER JOIN classifier_ngram_to_class c ON (n.id=c.token_id) WHERE n.token LIKE '[%' AND c.class_id = classifier_class.id)";
		$db->ExecuteMaster($sql);

		$sql = sprintf("UPDATE classifier_class SET dictionary_size = (SELECT COUNT(DISTINCT token_id) FROM classifier_ngram_to_class WHERE class_id=classifier_class.id), training_count = (SELECT count(id) FROM classifier_example WHERE class_id=classifier_class.id) WHERE FIND_IN_SET(id, @class_ids)");
		$db->ExecuteMaster($sql);
		
		$sql = sprintf("UPDATE classifier SET dictionary_size = (SELECT COUNT(DISTINCT token_id) FROM classifier_ngram_to_class WHERE FIND_IN_SET(class_id, @class_ids)) WHERE id = %d",
			$classifier_id
		);
		$db->ExecuteMaster($sql);
	}
	
	private static function _tagEntitySynonyms($labels, $tag, $words, &$tags) {
		$context_idx = sprintf('{%s}', $tag);
		
		foreach($labels as $label => $synonyms) {
			foreach($synonyms as $synonym) {
				$stokens = self::tokenizeWords($synonym);
				
				if(false !== ($pos = self::_findSubsetInArray($stokens, $words))) {
					foreach(array_keys($stokens) as $n) {
						$idx = $pos + $n;
						
						if(!isset($tags[$idx][$context_idx]))
							$tags[$idx][$context_idx] = [];
						
						$tags[$idx][$context_idx][$label] = $label;
					}
				}
			}
		}
	}
	
	public static function tag($words, $environment=[]) {
		if(!is_array($words))
			return [];
		
		$tags = array_fill_keys(array_keys($words), []);
		$db = DevblocksPlatform::services()->database();
		
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
		 * Temps
		 */
		
		array_walk($words, function($word, $idx) use (&$tags) {
			if(preg_match('#^\d+[º]*(c|f)*$#', $word))
				$tags[$idx]['{temp}'] = $word;
		});
		
		$hits = array_intersect($words, self::$TEMP_UNITS);
		foreach($hits as $idx => $token)
			$tags[$idx]['{temp_unit}'] = $token;
		
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
		 * Entities (worker, contact, org)
		 */
		
		$lookup = implode(' ', self::preprocessWordsPad($words, 4));
		$terms = $db->qstr($lookup);
		
		$sql = implode(' UNION ALL ', [
			sprintf("(SELECT context, id, name FROM context_alias WHERE MATCH (terms) AGAINST (%s IN NATURAL LANGUAGE MODE) AND context = %s AND is_primary = 0 LIMIT 10)",
				$terms,
				$db->qstr(CerberusContexts::CONTEXT_WORKER),
				$terms
			),
			sprintf("(SELECT context, id, name FROM context_alias WHERE MATCH (terms) AGAINST (%s IN NATURAL LANGUAGE MODE) AND context = %s AND is_primary = 1 LIMIT 5)",
				$terms,
				$db->qstr(CerberusContexts::CONTEXT_WORKER),
				$terms
			),
			sprintf("(SELECT context, id, name FROM context_alias WHERE MATCH (terms) AGAINST (%s IN NATURAL LANGUAGE MODE) AND context = %s AND is_primary = 0 LIMIT 10)",
				$terms,
				$db->qstr(CerberusContexts::CONTEXT_ORG),
				$terms
			),
			sprintf("(SELECT context, id, name FROM context_alias WHERE MATCH (terms) AGAINST (%s IN NATURAL LANGUAGE MODE) AND context = %s AND is_primary = 1 LIMIT 5)",
				$terms,
				$db->qstr(CerberusContexts::CONTEXT_ORG),
				$terms
			),
			sprintf("(SELECT context, id, name FROM context_alias WHERE MATCH (terms) AGAINST (%s IN NATURAL LANGUAGE MODE) AND context = %s AND is_primary = 0 LIMIT 10)",
				$terms,
				$db->qstr(CerberusContexts::CONTEXT_CONTACT),
				$terms
			),
			sprintf("(SELECT context, id, name FROM context_alias WHERE MATCH (terms) AGAINST (%s IN NATURAL LANGUAGE MODE) AND context = %s AND is_primary = 1 LIMIT 5)",
				$terms,
				$db->qstr(CerberusContexts::CONTEXT_CONTACT),
				$terms
			),
		]);
		
		$results = $db->GetArrayReader($sql);
		$hits = [];
		
		// Tag me|my|i as the current user if we know who they are
		if(array_intersect($words, ['i','me','my']) && isset($environment['me']) && isset($environment['me']['context'])) {
			switch($environment['me']['context']) {
				case CerberusContexts::CONTEXT_CONTACT:
				case CerberusContexts::CONTEXT_WORKER:
					$results[] = [
						'context' => $environment['me']['context'],
						'id' => $environment['me']['id'],
						'name' => 'me',
					];
					$results[] = [
						'context' => $environment['me']['context'],
						'id' => $environment['me']['id'],
						'name' => 'my',
					];
					$results[] = [
						'context' => $environment['me']['context'],
						'id' => $environment['me']['id'],
						'name' => 'i',
					];
					break;
			}
		}
		
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
		
		foreach($hits as $pos => $hit) {
			$result = $results[$pos];
			
			$context_map = [
				CerberusContexts::CONTEXT_CONTACT => 'contact',
				CerberusContexts::CONTEXT_ORG => 'org',
				CerberusContexts::CONTEXT_WORKER => 'worker',
			];
			
			if(!isset($context_map[$result['context']]))
				continue;
			
			$context_idx = '{' . $context_map[$result['context']] . '}';
			
			foreach(array_keys($hit) as $idx) {
				if(!isset($tags[$idx][$context_idx]))
					$tags[$idx][$context_idx] = [];
				
				// Only keep the first match per context:id tuple
				if(isset($tags[$idx][$context_idx][$result['id']]))
					continue;
				
				$tags[$idx][$context_idx][$result['id']] = $result['name'];
			}
		}
		
		self::_disambiguateActorTags($tags);
		
		$custom_entities = DAO_ClassifierEntity::getAll();
		
		foreach($custom_entities as $entity) {
			switch($entity->type) {
				case 'list':
					@$label_map = $entity->params['map'];
					
					if(empty($label_map) || !is_array($label_map))
						break;
					
					self::_tagEntitySynonyms($label_map, DevblocksPlatform::strLower($entity->name), $words, $tags);
					break;
					
				case 'regexp':
					@$pattern = $entity->params['pattern'];
					$entity_name = DevblocksPlatform::strLower($entity->name);
					
					if(empty($pattern))
						break;
					
					// [TODO] Currently these patterns can only handle one token
					
					array_walk($words, function(&$token, $idx) use (&$tags, $pattern, $entity_name) {
						if(preg_match($pattern, $token))
							$tags[$idx]['{' . $entity_name . '}'] = $token;
					});
					break;
					
				case 'text':
					// Already tagged
					break;
			}
		}
		
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
			if(!isset($tags[$idx])) {
				$idx++;
				continue;
			}
			
			$hits = array_intersect($contexts, array_keys($tags[$idx]));
			
			if(empty($hits)) {
				$idx++;
				continue;
			}
			
			$start_idx = $idx;
			$candidates = [];
			
			// Record stats about each candidate so we can compare them
			foreach($hits as $context => $tag) {
				$idx = $start_idx;
				
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
					if(isset($words[$idx]) && $words[$idx] == $k)
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
		$db = DevblocksPlatform::services()->database();
		
		$entities = [];
		
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
				'in a {date_unit}',
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
		
		if(in_array('temperature', $types)) {
			$sequences = [
				'{number} {temp_unit} {temp_unit}',
				'{number} {temp_unit}',
				'{temp}',
			];
			
			self::_sequenceToEntity($sequences, 'temperature', $words, $tags, $entities);
		}
		
		if(in_array('time', $types)) {
			$sequences = [
				'at {number} {number} {number} {time_meridiem}',
				'at {number} {number} {number}',
				'at {number} {number} {time_meridiem}',
				'at {number} {number}',
				'at {number} {time_meridiem}',
				'at {number} o\'clock in the {time_rel}',
				'at {number} o\'clock',
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
				'in an {time_unit}',
				'in a {time_unit}',
				'on the {time_unit}',
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
							$contact_find = implode(' ', self::preprocessWordsPad(array_slice($words, $contact_idx, end($range) - $contact_idx + 1), 4));
							
							$sql = sprintf("SELECT id, name FROM context_alias WHERE MATCH (terms) AGAINST (%s IN NATURAL LANGUAGE MODE) AND context = %s AND id IN (SELECT id FROM contact WHERE org_id = %d) LIMIT 5",
								$db->qstr($contact_find),
								$db->qstr(CerberusContexts::CONTEXT_CONTACT),
								key($org['value'])
							);
							
							$res = $db->GetArrayReader($sql);
							
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
		
		// Custom entities
		
		$custom_entities = DAO_ClassifierEntity::getAll();
		
		foreach($custom_entities as $entity) {
			$entity_token = DevblocksPlatform::strLower($entity->name);
			
			if(!in_array($entity_token, $types))
				continue;
				
			switch($entity->type) {
				case 'list':
					self::_tagToEntity($entity_token, $words, $tags, $entities);
					break;
					
				case 'regexp':
					foreach($tags as $idx => $tagset) {
						@$entity_value = $tagset['{' . $entity_token . '}'];
						
						if($entity_value) {
							if(!isset($entities[$entity_token]))
								$entities[$entity_token] = [];
							
							$entities[$entity_token][$idx] = [
								'range' => [
									$idx => [
										$idx => $entity_value
									],
								],
								'value' => [
									$entity_value => $entity_value
								]
							];
						}
					}
					break;
					
				case 'text':
					$tokens = $words;
					
					// [TODO] Windowing (3 words before and after?)
					
					// [TODO] We don't care about entities here, just tags
					
					foreach($entities as $entity_type => $results) {
						foreach($results as $result) {
							if(isset($result['range']))
							foreach(array_keys($result['range']) as $key) {
								$tokens[$key] = sprintf('[%s]', $entity_type);
							}
						}
					}
					
					$text = implode(' ', $tokens);
					
					// [TODO] These patterns should be learnable
					// [TODO] These can be optimized as a tree
					
					@$patterns = $entity->params['patterns'];
					
					if(empty($patterns) || !is_array($patterns))
						break;
					
					foreach($patterns as $pattern) {
						$pattern = str_replace('\{' . $entity_token . '\}', '(.*?)', DevblocksPlatform::strToRegExp($pattern, true, false));
						$matches = array();
						
						if(preg_match($pattern, $text, $matches)) {
							$terms = explode(' ', $matches[1]);
							if(false !== ($pos = self::_findSubsetInArray($terms, $words))) {
								if(!isset($entities[$entity_token]))
									$entities[$entity_token] = [];
								
								$remind = [
									'range' => array_combine(range($pos, $pos+count($terms)-1), $terms),
								];
									
								$entities[$entity_token][] = $remind;
								break;
							}
						}
					}
					break;
			}
		}
		
		return $entities;
	}
	
	// [TODO] remind me about lunch at Twenty Nine Palms Resort on the fifth at five thirty pm for sixty mins
	// [TODO] $environment has locale, lang, me
	static function predict($text, $classifier_id, $environment=[]) {
		$db = DevblocksPlatform::services()->database();
		
		// Load all classes
		// [TODO] This would be cached between training
		// [TODO] Add the training sum to the classifier
		$results = $db->GetArrayMaster('SELECT id, name, dictionary_size, params_json FROM classifier');
		$classifiers = array_column($results, null, 'id');
		//var_dump($classifiers);

		if(!isset($classifiers[$classifier_id]))
			return false;
		
		// Load the frequency of classes for this classifier from the database
		// [TODO] This would be cached between training (by classifier)
		$results = $db->GetArrayMaster(sprintf('SELECT id, name, training_count, dictionary_size, entities FROM classifier_class WHERE classifier_id = %d', $classifier_id));
		$classes = array_column($results, null, 'id');
		$class_freqs = array_column($results, 'training_count', 'id');
		
		$raw_words = self::tokenizeWords($text);
		
		$tags = self::tag($raw_words, $environment);
		
		// [TODO] Disambiguate tags once
		
		$class_data = [];
		$unique_tokens = [];
		
		foreach($classes as $class_id => $class) {
			$tokens = $words = $raw_words;
			$types = DevblocksPlatform::parseCsvString($class['entities']);
			
			// [TODO] Check required types?
			
			$entities = self::extractNamedEntities($raw_words, $tags, $types);
			
			foreach($entities as $entity_type => $results) {
				foreach($results as $result) {
					foreach(array_keys($result['range']) as $pos) {
						$tokens[$pos] = sprintf('[%s]', $entity_type);
					}
				}
			}
			
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
		
		// [TODO] Add every entity too?
		//$unique_tokens = array_unique(array_merge($unique_tokens, array_values(self::$TAGS_TO_TOKENS)));
		
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

			/*
			if(DevblocksPlatform::strStartsWith($row['token'], '['))
				$class_data[$row['class_id']]['entity_counts'][$row['token']] = intval($row['training_count']);
			*/
			
			$corpus_freqs[$row['token']] = intval(@$corpus_freqs[$row['token']]) + intval($row['training_count']);
		}
		
		// Test each class
		
		$class_freqs_sum = array_sum($class_freqs);
		
		foreach($class_data as $class_id => $data) {
			//$training_count = @$classes[$class_id]['training_count'] ?: 0;
			
			// If we've never seen this class in training (or any class), we can't make any predictions
			if(0 == $class_freqs_sum || 0 == $classifiers[$classifier_id]['dictionary_size']) {
				$class_data[$class_id]['p'] = 0;
				continue;
			}
			
			// If the training includes an entity 100% of the time and we don't have it, predict 0%
			/*
			foreach(self::$TAGS_TO_TOKENS as $entity) {
				$p_entity = (@$data['entity_counts'][$entity] ?: 0) / $training_count;
				
				if($p_entity == 1 && !in_array($entity, $data['tokens'])) {
					$class_data[$class_id]['p'] = 0;
					continue 2;
				}
			}
			*/
			
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
		
		//var_dump($results);
		
		$predicted_class_id = key($results);
		$predicted_class_confidence = current($results);
		
		// [TODO] Setting for default class for low confidence
		/*
		if($predicted_class_confidence < 0.30) {
			$predicted_class_id = 2;
			$predicted_class_confidence = $results[2];
		}
		*/
		
		//var_dump($class_data[$predicted_class_id]);
		
		$params = [];
		
		if(@isset($class_data[$predicted_class_id]['entities']) && is_array($class_data[$predicted_class_id]['entities']))
		foreach($class_data[$predicted_class_id]['entities'] as $entity_type => $results) {
			if(!isset($params[$entity_type]))
				$params[$entity_type] = [];
			
			foreach($results as $result_id => $result) {
				$param = [];
				
				switch($entity_type) {
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
								'image' => $contact->getImageUrl(),
								'language' => $contact->language,
								'last_name' => $contact->last_name,
								'location' => $contact->location,
								'mobile' => $contact->mobile,
								'org' => $contact->getOrgAsString(),
								'org_id' => $contact->org_id,
								'org_image' => $contact->getOrgImageUrl(),
								'phone' => $contact->phone,
								'timezone' => $contact->timezone,
								'title' => $contact->title,
								'updated' => $contact->updated_at,
							];
						}
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
						
						foreach($seq as $idx => $token) {
							if('{number}' == $token) {
								if(false !== ($ordinal = array_search($date_words[$idx], self::$NUM_WORDS)))
									$date_words[$idx] = $ordinal;
								
								if(false !== ($ordinal = array_search($date_words[$idx], self::$NUM_ORDINAL)))
									$date_words[$idx] = $ordinal;
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
						
					// [TODO] This can't handle "for the next 2 hours"
					// [TODO] "For three and a half hours"
					case 'duration':
						$param_key = implode(' ', $result['range']);
						$dur_words = $result['range'];
						$seq = $result['sequence'];
						
						// In durations, tag 'a' and 'an' as a {number:1}
						if(false !== ($hits = array_intersect($seq, ['a','an']))) {
							foreach($hits as $idx => $hit) {
								$dur_words[$idx] = '1';
								$seq[$idx] = '{number}';
							}
						}
						
						foreach($seq as $idx => $token) {
							if('{number}' == $token) {
								if(false !== ($ordinal = array_search($dur_words[$idx], self::$NUM_WORDS)))
									$dur_words[$idx] = $ordinal;
							}
						}
						
						$dur_string = implode(' ', array_intersect_key($dur_words, array_filter($seq, function($token) {
							return '{' == substr($token, 0, 1);
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
								'image' => $org->getImageUrl(),
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
						
					case 'status':
						if(!empty($result['value']))
							$params[$entity_type] = array_merge($params[$entity_type], array_slice($result['value'], 0, 1));
						break;
					
					case 'temperature':
						$param_key = implode(' ', $result['range']);
						$temp_words = $result['range'];
						$seq = $result['sequence'];
						
						if(!isset($params['temperature']))
							$params['temperature'] = [];
						
						// [TODO] localize ºC/ºF defaults
						
						$temp_string = trim(implode(' ', $temp_words));
							
						$params['temperature'][$param_key] = [
							'value' => intval($temp_string),
							'unit' => stristr($temp_string, 'c') ? 'C' : 'F', // [TODO] This is way too naive
						];
						break;
						
					case 'time':
						$param_key = implode(' ', $result['range']);
						$time_words = $result['range'];
						$seq = $result['sequence'];
						
						// [TODO] Normalize 'five thirty five pm' -> '5:30 pm'
						
						// In dates and times, tag 'a' and 'an' as a {number:1}
						if(false !== ($hits = array_intersect($seq, ['a','an']))) {
							foreach($hits as $idx => $hit) {
								$time_words[$idx] = '1';
								$seq[$idx] = '{number}';
							}
						}
						
						foreach($seq as $idx => $token) {
							if('{number}' == $token) {
								if(false !== ($ordinal = array_search($time_words[$idx], self::$NUM_WORDS)))
									$time_words[$idx] = $ordinal;
							}
						}
						
						$time_string = implode(' ', array_intersect_key($time_words, array_filter($seq, function($token) {
							return '{' == substr($token, 0, 1);
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
								'image' => $worker->getImageUrl(),
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
				
					default:
						if(false == ($custom_entity = DAO_ClassifierEntity::getByName($entity_type)))
							break;
							
						switch($custom_entity->type) {
							case 'list':
								if(!empty($result['value']))
									$params[$entity_type] = array_merge($params[$entity_type], array_slice($result['value'], 0, 1));
								break;
								
							case 'regexp':
								if(!empty($result['value']))
									$params[$entity_type] = array_merge($params[$entity_type], array_slice($result['value'], 0, 1));
								break;
								
							case 'text':
								if(!isset($params[$entity_type]))
									$params[$entity_type] = [];
								
								$param_key = implode(' ', $result['range']);
								
								// [TODO] We should keep a case-sensitive version of the original tokenized string for params
								$params[$entity_type][$param_key] = [
									//'value' => implode(' ', array_intersect_key(explode(' ', $text), $result['range'])),
									'value' => implode(' ', $result['range']),
								];
								break;
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
				'classifier' => @array_intersect_key($classifiers[$classifier_id],['id'=>true,'name'=>true]) ?: [],
				'classification' => @array_intersect_key($classes[$predicted_class_id],['id'=>true,'name'=>true,'attribs'=>true]) ?: [],
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