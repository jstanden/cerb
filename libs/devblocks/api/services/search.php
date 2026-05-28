<?php

class _DevblocksSearchService {
	static ?_DevblocksSearchService $instance = null;
	
	private function __construct() {}
	
	static function getInstance() : _DevblocksSearchService {
		if(null == self::$instance)
			self::$instance = new _DevblocksSearchService();
		
		return self::$instance;
	}
	
	public function processQueue(Model_Queue $queue, int $stop_time, int $count_hint, ?Model_QueueJob $queue_job) {
		$queue_service = DevblocksPlatform::services()->queue();

		$processed = 0;
		$consumer_id = null;

		$job_id = $queue_job?->id ?? null;
		$search_indexes = \DAO_SearchIndex::getAll();

		// One message at a time (each carries 100 record IDs in a single fulltext
		// write batch) but loop so concurrent workers can interleave on the same
		// job within the $stop_time budget.
		while($stop_time > time()) {
			if(!($queue_messages = $queue_service->dequeue($queue->name, 1, $consumer_id, $job_id)))
				break;

			foreach($queue_messages as $queue_message) {
				if(!($search_index = ($search_indexes[$queue_message->message['index_id'] ?? 0] ?? null))) {
					// Mark message failed if the search index is invalid
					$error = sprintf('Invalid search index: %s', $queue_message->message['index_id'] ?? 0);
					$queue_message->reportStatus(QueueMessageStatus::FAILED, $error);
					continue;
				}

				$error = null;

				$search_extension = $search_index->getExtension();
				$ids = $queue_message->message['ids'] ?? [];

				if(!$search_extension->indexDocumentsByIds($search_index, $ids, $error)) {
					$queue_message->reportStatus(\QueueMessageStatus::FAILED, $error);
					continue;
				}

				$queue_message->reportStatus(
					\QueueMessageStatus::DONE,
					sprintf('Indexed %d %s', count($ids), count($ids) === 1 ? 'record' : 'records')
				);
				
				// Count work units rather than messages
				$processed += $queue_message->cardinality;
			}
		}

		return $processed;
	}
	
	private function _getTokenizerPattern(bool $allow_wildcards=false, bool $allow_stemming=false) : string {
		return sprintf(
			"[^[:alnum:]\'\.\_\-%s]",
			($allow_wildcards ? '\*' : '') . ($allow_stemming ? '\~' : '')
		);
	}
	
	private function _hashToken(string $token) : int {
		return DevblocksPlatform::services()->string()->xxh3($token);
	}
	
	public function expandTokens(array $tokens) : array {
		foreach ($tokens as $token) {
			$terms = preg_split('/[^\pL\pN]+/u', $token, -1, PREG_SPLIT_NO_EMPTY);
			if (count($terms) > 1) $tokens = array_merge($tokens, $terms);
		}
		
		return $tokens;
	}
	
	public function getTokensFromText(string $string, array $stop_words=self::DEFAULT_STOP_WORDS, int $truncate=0, int $min_length=1, int $max_length=84, bool $allow_wildcards=false, bool $allow_stemming=false) : array {
		// Truncate
		if($truncate) $string = $this->truncateOnWhitespace($string, $truncate);
		
		// Tokenize by regex
		$tokenizer_pattern = $this->_getTokenizerPattern($allow_wildcards, $allow_stemming);
		$tokens = $this->tokenize($string, $tokenizer_pattern);
		
		// Don't allow bare wildcards
		if($allow_wildcards) {
			$tokens = array_diff($tokens, ['*']);
		}
		
		// Only allow stemming at the end
		if($allow_stemming) {
			$tokens = array_map(function ($token) {
				if (!str_contains($token, '~')) return $token;
				return str_replace('~', '', $token) . '~';
			}, $tokens);
		}
		
		// Remove stop words
		if($stop_words) $tokens = $this->removeStopWords($tokens, $stop_words);
		
		// Filter min/max token lengths
		if($min_length || $max_length) {
			$tokens = array_filter(
				$tokens,
				fn($token) => strlen($token) >= $min_length && strlen($token) <= $max_length
			);
		}
		
		return $tokens;
	}
	
	public function indexTokens(array $tokens) : array {
		$total_tokens = count($tokens);
		$token_frequencies = array_count_values($tokens);
		
		return array_combine(
			array_map(fn($token) => $this->_hashToken($token), array_keys($token_frequencies)),
			array_map(
				fn($token) => [$token, intval($token_frequencies[$token]) / $total_tokens],
				array_keys($token_frequencies)
			)
		);
	}
	
	public function truncateOnWhitespace(string $content, int $length) : string {
		$start = 0;
		$len = mb_strlen($content);
		$end = $start + $length;
		
		// If our offset is past EOS, use the last pos
		if($end > $len) {
			$next_ws = $len;
			
		} else {
			if(false === ($next_ws = mb_strpos($content, ' ', $end)))
				if(false === ($next_ws = mb_strpos($content, "\n", $end)))
					$next_ws = $end;
		}
		
		return mb_substr($content, $start, $next_ws-$start);
	}
	
	public function tokenize(string $string, string $pattern) : array {
		$strings = DevblocksPlatform::services()->string();
		
		// Tokenize (term-frequency)
		$tokens = $strings->tokenize($string, true, false, $pattern);
		
		// Fix outer punctuation
		return array_filter(array_map(fn($token) => trim(str_replace(["'"], "", $token),"._-'"), $tokens));
	}
	
	public function removeStopWords(array $tokens, array $stop_words=[]) : array {
		return array_diff($tokens, $stop_words);
	}
	
	const DEFAULT_STOP_WORDS = [
		'a',
		'about',
		'almost',
		'an',
		'and',
		'are',
		'as',
		'at',
		'be',
		'but',
		'by',
		'can',
		'com',
		'de',
		'en',
		'for',
		'from',
		'how',
		'i',
		'if',
		'im',
		'in',
		'into',
		'is',
		'it',
		'la',
		'like',
		'me',
		'my',
		'no',
		'not',
		'of',
		'on',
		'or',
		'please',
		'such',
		'thank',
		'that',
		'the',
		'their',
		'then',
		'there',
		'these',
		'they',
		'this',
		'to',
		'und',
		'was',
		'what',
		'when',
		'where',
		'who',
		'will',
		'with',
		'www',
		'you',
		'your',
	];
	
	public function stripDataUris(string $text) : string {
		return preg_replace(
			'/(data:[^;]+;base64,)([a-zA-Z0-9\/\+=]+)/',
			'$1',
			$text
		);
	}
	
	public function stripPemContentBlocks(string $text) : string {
		return preg_replace(
			'/(-----BEGIN [A-Z0-9 ]+-----).*?(-----END [A-Z0-9 ]+-----)/s',
			'$1 $2',
			$text
		);
	}
	
	public function sanitizeTextUrlQueryStrings(string $string_to_index) : string {
		if(str_contains($string_to_index, 'http'))
			return preg_replace('/(\bhttps?:\/\/[^\s?]+)\?[^\s]*/', '$1', $string_to_index);
		
		return $string_to_index;
	}
}
