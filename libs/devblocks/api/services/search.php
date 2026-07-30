<?php

class _DevblocksSearchService {
	static ?_DevblocksSearchService $instance = null;
	
	private function __construct() {}
	
	static function getInstance() : _DevblocksSearchService {
		if(null == self::$instance)
			self::$instance = new _DevblocksSearchService();
		
		return self::$instance;
	}
	
	private bool $_defer_index_queue = false;

	/** [record_type => [record_id => true]] collected while deferred */
	private array $_deferred_index_ids = [];

	/**
	 * Collect `queueIndexRecords()` calls instead of enqueueing them, until `flushIndexQueue()`.
	 *
	 * For a bulk writer whose records go in one at a time (the agent-filesystem ZIP importer writes a row per
	 * archive entry): without this each row would enqueue its own single-id message. ALWAYS flush in a
	 * `finally` — a window abandoned on an exception just leaves those records to the next cron sweep.
	 */
	public function deferIndexQueue() : void {
		$this->_defer_index_queue = true;
	}

	public function flushIndexQueue() : int {
		$this->_defer_index_queue = false;

		$deferred = $this->_deferred_index_ids;
		$this->_deferred_index_ids = [];

		$queued = 0;

		foreach($deferred as $record_type => $record_ids)
			$queued += $this->queueIndexRecords(strval($record_type), array_keys($record_ids));

		return $queued;
	}

	/**
	 * Queue an immediate re-index of specific records, for every index built on that record type.
	 *
	 * The `search` cron already sweeps each index incrementally by `updated_at`, so this is about LATENCY, not
	 * coverage: a caller that writes a record and expects to search it moments later (an agent editing a file,
	 * then grepping for what it wrote) can't wait for the next cron tick. Messages carry the same
	 * `{index_id, ids}` shape a full reindex emits, so `processQueue()` handles them unchanged.
	 *
	 * Fire-and-forget: no queue job, so these don't show up as progress bars in the Queue Job Monitor — they
	 * ride along with whatever consumer run drains the queue next.
	 */
	public function queueIndexRecords(string $record_type, array $record_ids, int $batch_size=100) : int {
		if(!($record_ids = DevblocksPlatform::sanitizeArray($record_ids, 'int', ['unique', 'nonzero'])))
			return 0;

		// Inside a defer window a bulk writer's per-record calls pile up here instead, so one import writes a
		// few 100-id messages rather than one message per file.
		if($this->_defer_index_queue) {
			foreach($record_ids as $record_id)
				$this->_deferred_index_ids[$record_type][$record_id] = true;

			return 0;
		}

		if(!($search_indexes = DAO_SearchIndex::getByRecordType($record_type)))
			return 0;

		$queue_service = DevblocksPlatform::services()->queue();
		$queued = 0;

		foreach($search_indexes as $search_index) {
			if(!$search_index->getExtension()?->hasOption('index'))
				continue;

			foreach(array_chunk($record_ids, $batch_size) as $chunk) {
				$message = ['index_id' => $search_index->id, 'ids' => array_values($chunk)];

				// cardinality = work units, so a jobless message still reports honest throughput
				if($queue_service->enqueue('cerb.search.index', [$message], $error, cardinality: count($chunk)))
					$queued++;
			}
		}

		return $queued;
	}

	public function processQueue(Model_Queue $queue, int $stop_time, int $count_hint, ?Model_QueueJob $queue_job) {
		$queue_service = DevblocksPlatform::services()->queue();

		$processed = 0;
		$claim_id = null;

		$job_id = $queue_job?->id ?? null;
		$search_indexes = \DAO_SearchIndex::getAll();

		// One message at a time (each carries 100 record IDs in a single fulltext
		// write batch) but loop so concurrent workers can interleave on the same
		// job within the $stop_time budget.
		while($stop_time > time()) {
			if(!($queue_messages = $queue_service->dequeue($queue->name, 1, $claim_id, $job_id)))
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
		return array_filter(array_map(
			function($token) use ($strings) {
				$token = trim(str_replace(["'"], "", $token),"._-'");

				// utf8mb3 token storage can't hold 4-byte chars
				if($strings->has4ByteChars($token))
					$token = $strings->strip4ByteChars($token);

				return $token;
			},
			$tokens
		));
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
