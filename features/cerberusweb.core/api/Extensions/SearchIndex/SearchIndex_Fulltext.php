<?php

namespace Cerb\Extensions\SearchIndex;

use Cerb\Extensions\Extension_SearchIndex;
use Cerb\Services\Search\PorterStemmer;
use CerberusApplication;
use DAO_Queue;
use DAO_QueueJob;
use DevblocksEngine;
use DevblocksPlatform;
use DevblocksSearchCriteria;
use Model_QueueJob;
use Model_SearchIndex;

class SearchIndex_Fulltext extends Extension_SearchIndex {
	const ID = 'cerb.search.index.fulltext';

	// Only write token hashes once per batch (keep track of 'seen')
	private static array $_indexed_token_hashes = [];

	// Bulk-load keys per index, derived from its content templates. Memoized because
	// tokenize() is a full Twig parse and this runs once per queue message.
	private static array $_record_expand_by_index = [];

	// Cap the seen set to conserve memory
	const int MAX_INDEXED_TOKEN_HASHES = 250_000;

	// How many terms a ranked search actually scores, rarest-first. A longer query is silently BROADENED --
	// the lowest-IDF terms are dropped. `search --terms` reports this, so it can't be a bare literal.
	const int MAX_RANKED_TERMS = 10;

	// How many concrete tokens a `auto*` / `automate~` row expands to. A wildcard IS a vocabulary probe --
	// the words it covers are the answer -- but an open prefix can match hundreds, so it is capped.
	const int MAX_EXPANDED_TOKENS = 10;

	function renderConfig(Model_SearchIndex $model) : void {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('model', $model);
		$tpl->display('devblocks:cerberusweb.core::records/types/search_index/fulltext/config.tpl');
	}
	
	function invokeConfig($config_action, Model_SearchIndex $model) : void {
	}
	
	/**
	 * The index's CONFIGURED record_query as record-type SQL, optionally ANDed with the caller's own
	 * filters for this one call. One code path so the configured scope and the per-call scope can't
	 * disagree about how a query becomes SQL.
	 *
	 * @param array $co_params Per-call filters (see Extension_SearchIndex::queryDocumentsWithScore)
	 */
	private function _getRecordQueryParts(Model_SearchIndex $model, array $co_params=[]) : array {
		if(!($record_ext = $model->getRecordTypeExtension()))
			return [];
		
		$record_query = $model->extension_params['record_query'] ?? '';
		
		if(!($view = $record_ext->getTempView()))
			return [];
		
		$view->addParamsWithQuickSearch($record_query);
		$view->setAutoPersist(false);
		
		$dao_class = $record_ext->getDaoClass();
		$search_class = $record_ext->getSearchClass();
		
		if(!method_exists($dao_class, 'getSearchQueryComponents'))
			return [];
		
		$params = $view->getParams();
		
		foreach($co_params as $co_key => $co_param) {
			if($co_param instanceof DevblocksSearchCriteria)
				$params['co:' . $co_key] = $co_param;
		}
		
		// getSearchQueryComponents() re-enters _parseSearchParams(), which is where a search index param
		// gets its co-params attached -- so leaving one here scopes an index by an index, forever.
		$params = array_filter(
			$params,
			fn($param) => !(
				$param instanceof DevblocksSearchCriteria
				&& \DevblocksSearchField::VIRTUAL_SEARCH_INDEX == $param->field
			)
		);
		
		$query_parts = $dao_class::getSearchQueryComponents(
			[],
			$params
		);
		
		$query_parts['key_primary'] = null;
		$query_parts['key_updated'] = null;
		
		if(method_exists($search_class, 'getPrimaryKey'))
			$query_parts['key_primary'] = $search_class::getPrimaryKey();
		
		if(method_exists($search_class, 'getUpdatedKey'))
			$query_parts['key_updated'] = $search_class::getUpdatedKey();
		
		return $query_parts;
	}
	
	/**
	 * The caller's filters as a record subquery (`SELECT <pk> FROM <table> WHERE ...`), for narrowing the
	 * scoring query BEFORE its LIMIT. Returns '' when there is nothing to narrow by -- callers must treat
	 * an empty string as "no scope" rather than emitting `IN ()`.
	 *
	 * Returned without an alias so each call site can spell the left side itself: the scoring query uses
	 * `s0.record_id`, getTokenStats() uses a bare `record_id`.
	 */
	private function _getScopeSubquery(Model_SearchIndex $model, array $co_params) : string {
		if(!$co_params)
			return '';
		
		if(!($query_parts = $this->_getRecordQueryParts($model, $co_params)))
			return '';
		
		if(!($key_primary = $query_parts['key_primary'] ?? null))
			return '';
		
		return sprintf('SELECT %s %s %s',
			$key_primary,
			$query_parts['join'] ?? '',
			$query_parts['where'] ?? ''
		);
	}
	
	private function _clearCache(Model_SearchIndex $model) : void {
		$cache = DevblocksPlatform::services()->cache();
		$cache_key = sprintf("search_index:%d:count", $model->id);
		$cache->remove($cache_key);
	}
	
	/**
	 * @param string $scope_sql A record subquery from _getScopeSubquery(); when set this is the IDF
	 *   denominator for that scope rather than for the whole index.
	 */
	public function getRecordCount(Model_SearchIndex $model, bool $no_cache=false, string $scope_sql=''): int {
		$cache = DevblocksPlatform::services()->cache();
		
		// A scoped count is a different denominator, so it gets its own key. Deliberately NOT invalidated by
		// _clearCache(): that runs after every index batch, which on a busy index would leave scoped counts
		// permanently cold and pay for the scan on every search. Staleness is harmless here -- moving the
		// denominator shifts every term's IDF by the same constant, and only their ORDER matters.
		$cache_key = $scope_sql
			? sprintf("search_index:%d:count:%s", $model->id, sha1($scope_sql))
			: sprintf("search_index:%d:count", $model->id)
			;
		
		if($no_cache || null === ($count = $cache->load($cache_key))) {
			$db = DevblocksPlatform::services()->database();
			
			// Use the engine's record query to constrain the total docs (vs. index)
			
			if(!($record_ext = $model->getRecordTypeExtension()))
				return 0;
			
			if(!($search_class = $record_ext->getSearchClass()))
				return 0;
			
			if(!method_exists($search_class, 'getPrimaryKey'))
				return 0;
			
			if($scope_sql) {
				// Already `SELECT <pk> FROM ... WHERE ...` over this record type, with this index's own
				// record_query folded in, so count it directly rather than rebuilding the same parts.
				$search_sql = sprintf('SELECT COUNT(*) AS total_docs FROM (%s) AS scoped', $scope_sql);
				
			} else {
				$primary_key = $search_class::getPrimaryKey();
				$query_parts = $this->_getRecordQueryParts($model);
				
				// SQL (no sorting for count)
				$search_sql =
					sprintf('SELECT COUNT(%s) AS total_docs ', $db->escape($primary_key)).
					$query_parts['join'].
					$query_parts['where']
				;
			}
			
			try {
				$count = $db->GetOneReader($search_sql);
				$cache->save(intval($count), $cache_key, [], 60);
				
			} catch (\Throwable) {
				$count = 0;
			}
		}
		
		return intval($count);
	}
	
	public function initializeIndex(Model_SearchIndex $model): bool {
		if($this->_searchTableExists($model))
			return true;

		return $this->_createSearchIndexTable($model);
	}

	public function getIndexRecordCount(Model_SearchIndex $model, bool $no_cache=false): int {
		$db = DevblocksPlatform::services()->database();
		
		if(!$this->initializeIndex($model))
			return 0;


		try {
			$count = $db->GetOneReader(sprintf(
				'SELECT COUNT(DISTINCT record_id) FROM search_index_%d',
				$model->id,
			));
		} catch (\Throwable) {
			$count = 0;
		}

		return intval($count);
	}

	/**
	 * One required term as a WHERE predicate, in whichever mode the token is written -- the same three modes
	 * the counting branches use, so `+automat*` and `+running~` can require what they counted instead of
	 * being refused for looking different.
	 */
	private function _requiredPredicate(string $alias, string $token, int $hash) : string {
		$db = DevblocksPlatform::services()->database();

		return match(true) {
			str_contains($token, '*') => sprintf('%s.token_hash IN (SELECT token_hash FROM search_index_tokens WHERE token LIKE %s)',
				$alias, $db->qstr(str_replace('*', '%', $token))),
			str_ends_with($token, '~') => sprintf('%s.token_hash IN (SELECT token_hash FROM search_index_tokens WHERE stem = %s)',
				$alias, $db->qstr(PorterStemmer::Stem(rtrim($token, '~')))),
			default => sprintf('%s.token_hash = %d', $alias, $hash),
		};
	}

	/**
	 * The concrete tokens a wildcard or stem actually covers, with their own doc counts, most common first.
	 *
	 * `auto*` counting 682 says nothing about WHICH words are in the corpus, and that vocabulary is usually
	 * the thing the caller is fishing for. One query per wildcard term, capped -- an open prefix can match
	 * hundreds of tokens and the caller is not reading hundreds.
	 *
	 * @return array token => ['docs'=>int, 'more'=>int] beyond the cap
	 */
	private function _expandToken(Model_SearchIndex $model, string $token, string $scope_sql='') : array {
		$db = DevblocksPlatform::services()->database();

		$match = match(true) {
			str_contains($token, '*') => sprintf('d.token LIKE %s', $db->qstr(str_replace('*', '%', $token))),
			str_ends_with($token, '~') => sprintf('d.stem = %s', $db->qstr(PorterStemmer::Stem(rtrim($token, '~')))),
			default => '',
		};

		if(!$match)
			return [];

		$sql = sprintf(
			"SELECT d.token, COUNT(DISTINCT s.record_id) AS docs " .
			"FROM search_index_tokens d " .
			"INNER JOIN search_index_%d s ON (s.token_hash = d.token_hash) " .
			"WHERE %s%s " .
			"GROUP BY d.token ORDER BY docs DESC, d.token ASC LIMIT %d",
			$model->id,
			$match,
			$scope_sql ? sprintf(' AND s.record_id IN (%s)', $scope_sql) : '',
			self::MAX_EXPANDED_TOKENS + 1
		);

		$rows = $db->GetArrayReader($sql) ?: [];

		// Probing one row past the cap tells us there IS more, never how much -- so the marker must not
		// name a number it doesn't know. Counting the rest honestly would mean a second join over the whole
		// prefix, which is the expensive half of the query the cap exists to avoid.
		$truncated = count($rows) > self::MAX_EXPANDED_TOKENS;
		$rows = array_slice($rows, 0, self::MAX_EXPANDED_TOKENS);

		$out = [];

		foreach($rows as $row)
			$out[strval($row['token'])] = ['docs' => intval($row['docs']), 'truncated' => false];

		if($truncated && $out)
			$out[array_key_last($out)]['truncated'] = true;

		return $out;
	}

	/**
	 * The required terms as a record subquery, so a requirement composes with the caller's scope through the
	 * SAME `record_id IN (...)` seam every counting query already has -- rather than growing a second SQL
	 * shape inside each branch.
	 *
	 * @param array $required ['token'=>string,'hash'=>int], RAREST FIRST so the anchor scans least
	 */
	private function _getRequiredSubquery(Model_SearchIndex $model, array $required, string $scope_sql='') : string {
		if(!$required)
			return $scope_sql;

		$anchor = array_shift($required);
		$joins = [];

		foreach(array_values($required) as $n => $term)
			$joins[] = sprintf('JOIN search_index_%d p%d ON (p%d.record_id = p0.record_id AND %s)',
				$model->id, $n+1, $n+1,
				$this->_requiredPredicate(sprintf('p%d', $n+1), strval($term['token']), intval($term['hash']))
			);

		return sprintf('SELECT p0.record_id FROM search_index_%d p0 %s WHERE %s%s',
			$model->id,
			implode(' ', $joins),
			$this->_requiredPredicate('p0', strval($anchor['token']), intval($anchor['hash'])),
			$scope_sql ? sprintf(' AND p0.record_id IN (%s)', $scope_sql) : ''
		);
	}

	/**
	 * Per-token document counts, zero-filled. NO bails, no sort, no IDF, no cap -- the CALLER decides what a
	 * zero means. getTokenStats() bails on one, because a zero AND-empties a search; a diagnostic needs the
	 * zero itself. Both go through here so a diagnostic can never disagree with the search it explains.
	 *
	 * @param array $index_tokens hash => [token, tf], from indexTokens()
	 * @return array hash => ['token'=>string, 'hash'=>int, 'docs'=>int]
	 */
	private function _getDocFrequencies(Model_SearchIndex $model, array $index_tokens, bool $allow_wildcards, bool $allow_stemming, string $scope_sql='') : array {
		$db = DevblocksPlatform::services()->database();
		
		$scope_where = $scope_sql ? sprintf(' AND record_id IN (%s)', $scope_sql) : '';
		
		$doc_frequencies = array_combine(
			array_keys($index_tokens),
			array_map(
				fn($token_hash) => [
					'token' => $index_tokens[$token_hash][0],
					'hash' => $token_hash,
					'docs' => 0
				],
				array_keys($index_tokens),
			)
		);
		
		$remaining_tokens = $doc_frequencies;
		
		// Check wildcards first to abort early
		if($allow_wildcards) {
			$tokens_wildcard = array_filter($remaining_tokens, fn($term) => str_contains($term['token'], '*'));
			
			foreach($tokens_wildcard as $token_hash => $token_term) {
				// Calculate the doc frequency of each token
				$sql = sprintf(
					"SELECT COUNT(DISTINCT record_id) AS hits " .
					"FROM search_index_%d " .
					"WHERE token_hash IN (".
					"SELECT token_hash FROM search_index_tokens WHERE token LIKE %s".
					") %s",
					$model->id,
					$db->qstr(str_replace('*', '%', $token_term['token'])),
					$scope_where
				);
				$result = $db->GetRowReader($sql);
				
				// hash => hits (including zeroes on non-matches)
				$doc_frequencies[$token_hash]['docs'] = intval($result['hits']);
				unset($remaining_tokens[$token_hash]);
			}
		}
		
		// Check stems next
		if($allow_stemming) {
			$tokens_stems = array_filter($remaining_tokens, fn($term) => str_ends_with($term['token'], '~'));
			
			foreach($tokens_stems as $token_hash => $token_term) {
				// Calculate the doc frequency of each token
				$sql = sprintf(
					"SELECT COUNT(DISTINCT record_id) AS hits " .
					"FROM search_index_%d " .
					"WHERE token_hash IN (".
					"SELECT token_hash FROM search_index_tokens WHERE stem = %s".
					") %s",
					$model->id,
					$db->qstr(PorterStemmer::Stem(rtrim($token_term['token'], '~'))),
					$scope_where
				);
				$result = $db->GetRowReader($sql);
				
				// hash => hits (including zeroes on non-matches)
				$doc_frequencies[$token_hash]['docs'] = intval($result['hits']);
				unset($remaining_tokens[$token_hash]);
			}
		}
		
		// Whatever is left are terms
		if($remaining_tokens) {
			$token_term_hashes = implode(',', DevblocksPlatform::sanitizeArray(array_keys($remaining_tokens), 'int'));
			
			// Calculate the doc frequency of each token
			$sql = sprintf(
				"SELECT token_hash, COUNT(record_id) AS hits " .
				"FROM search_index_%d " .
				"WHERE token_hash IN (%s) %s" .
				"GROUP BY token_hash",
				$model->id,
				$token_term_hashes,
				$scope_where
			);
			$results = $db->GetArrayReader($sql);
			
			// hash => hits (including zeroes on non-matches)
			foreach ($results as $result) {
				$doc_frequencies[$result['token_hash']]['docs'] = intval($result['hits']);
			}
		}
		
		return $doc_frequencies;
	}
	
	/**
	 * @param string $scope_sql A record subquery from _getScopeSubquery(). Both halves of IDF are counted
	 *   within it -- a term that is rare in a small mounted volume but common across the whole index has to
	 *   score as rare, or ranking inside the scope is decided by documents the caller can't see.
	 */
	public function getTokenStats(Model_SearchIndex $model, string $query, bool $allow_wildcards = false, bool $allow_stemming = false, int $max_terms=self::MAX_RANKED_TERMS, string $scope_sql=''): array {
		$search = DevblocksPlatform::services()->search();
		
		if(!$query) return [];
		
		try {
			$total_docs = $this->getRecordCount($model, scope_sql: $scope_sql);
			$query_tokens = $search->getTokensFromText($query, allow_wildcards: $allow_wildcards, allow_stemming: $allow_stemming);
			$index_tokens = $search->indexTokens($query_tokens);
			
			$doc_frequencies = $this->_getDocFrequencies($model, $index_tokens, $allow_wildcards, $allow_stemming, $scope_sql);
			
			// A query that tokenizes to nothing (all stopwords or punctuation) has no terms to score
			if(!$doc_frequencies) return [];

			// Sort by rarest terms first
			DevblocksPlatform::sortObjects($doc_frequencies, '[docs]');

			// If the rarest term is zero, match nothing w/ AND operator.
			// Keep this AHEAD of the $max_terms slice below: the slice drops the lowest-IDF terms, so
			// reordering them would let a capped-away term be the real zero and make `search --terms`
			// blame the wrong word.
			if($doc_frequencies[array_key_first($doc_frequencies)]['docs'] == 0) return [];
			
			// Pre-calculate TF-IDF
			// A scoped denominator can legitimately be small; zero would make log() -INF and poison every
			// score, and docs > total_docs (a stale index) would make IDF negative.
			$total_docs = max($total_docs, 1);
			
			$doc_frequencies = array_map(
				fn($term) => array_merge($term, ['idf' => max(0, log($total_docs / $term['docs']))]),
				$doc_frequencies
			);
			
			// Sort by the highest IDF score first
			DevblocksPlatform::sortObjects($doc_frequencies, '[idf]', false);
			
			// Use the top 10 terms by IDF
			$doc_frequencies = array_slice($doc_frequencies, 0, $max_terms, true);
			
			return array_values($doc_frequencies);
			
		} catch (\Throwable $e) {
			// Without this a broken index reports "no matches" forever, which is exactly the failure
			// `search --terms` exists to explain. The sibling catch in queryJoinFromRecordQuickSearch logs.
			DevblocksPlatform::logException($e);
			return [];
		}
	}
	
	/**
	 * Per-term document counts for the words a caller actually typed. Answers "why did my search find
	 * nothing" and "which of these words is worth searching" -- NOT a ranking.
	 *
	 * Two passes, and the pair of numbers is the point. Pass 1 requires nothing, which yields what every
	 * word matches on its own AND each pin's own baseline; pass 2 re-counts inside the pins. A term at 0
	 * with a non-zero `alone` matches in scope but never in the same file as the pin -- reporting that as
	 * "no matches" would send the reader off to find a different word when the word was fine.
	 *
	 * @param array $words Typed order, `+` markers already stripped
	 * @param array $required Which of $words must appear
	 * @param array $co_params The caller's other filters (see queryDocumentsWithScore)
	 */
	public function queryTermStats(Model_SearchIndex $model, array $words, array $required=[], array $co_params=[]) : array {
		$search = DevblocksPlatform::services()->search();
		
		$allow_wildcards = !($model->extension_params['wildcards_disable'] ?? false);
		$allow_stemming = !($model->extension_params['stemming_disable'] ?? false);
		
		$scope_sql = $this->_getScopeSubquery($model, $co_params);
		
		// Tokenize each typed word ALONE. getTokensFromText() splits on a character class and then filters
		// per token, so the per-word union is the same multiset as tokenizing the joined string -- and this
		// way a count is attributable to the word that produced it instead of re-derived afterwards.
		$rows = [];
		$index_tokens = [];
		$required_map = array_flip(array_map('strval', $required));
		
		foreach($words as $word) {
			$word = strval($word);
			$is_required = array_key_exists($word, $required_map);
			$tokens = $search->getTokensFromText($word, allow_wildcards: $allow_wildcards, allow_stemming: $allow_stemming);
			
			if(!$tokens) {
				// The index never looked for it -- a stop word, or punctuation only
				$rows[] = ['term' => $word, 'token' => null, 'hash' => null, 'docs' => null, 'alone' => null, 'status' => 'common', 'required' => $is_required, 'parent' => null];
				continue;
			}
			
			foreach($tokens as $token) {
				$hashes = $search->indexTokens([$token]);
				$hash = array_key_first($hashes);
				
				$rows[] = ['term' => $word, 'token' => $token, 'hash' => $hash, 'docs' => null, 'alone' => null, 'status' => 'ok', 'required' => $is_required, 'parent' => null];
				$index_tokens[$hash] = $hashes[$hash];
				
				// Indexing expands `llm.agent` into `llm` and `agent` as well; querying never does, so the
				// compound looks rarer than its parts. Reporting the parts costs nothing (same IN list) and
				// the fix -- type the words separately -- is actionable.
				if(str_contains($token, '*') || str_contains($token, '~'))
					continue;
				
				foreach(array_slice($search->expandTokens([$token]), 1) as $part) {
					if(!($part_tokens = $search->getTokensFromText($part)))
						continue;
					
					$part = reset($part_tokens);
					$part_hashes = $search->indexTokens([$part]);
					$part_hash = array_key_first($part_hashes);
					
					if($part_hash === $hash)
						continue;
					
					$rows[] = ['term' => $word, 'token' => $part, 'hash' => $part_hash, 'docs' => null, 'alone' => null, 'status' => 'ok', 'required' => false, 'parent' => $token];
					$index_tokens[$part_hash] = $part_hashes[$part_hash];
				}
			}
		}
		
		$out = [
			'scope_docs' => $this->getRecordCount($model, scope_sql: $scope_sql),
			'required_docs' => null,
			'max_terms' => self::MAX_RANKED_TERMS,
			'terms' => $rows,
		];
		
		if(!$index_tokens)
			return $out;
		
		try {
			// Pass 1 -- what each word matches on its own, in scope
			$alone = array_column($this->_getDocFrequencies($model, $index_tokens, $allow_wildcards, $allow_stemming, $scope_sql), 'docs', 'hash');
			
			foreach($out['terms'] as &$row) {
				if(!is_null($row['hash']))
					$row['alone'] = intval($alone[$row['hash']] ?? 0);
			}
			unset($row);
			
			$required_terms = [];
			
			foreach($out['terms'] as $row) {
				if($row['required'] && !is_null($row['hash']) && is_null($row['parent']))
					$required_terms[$row['hash']] = ['token' => $row['token'], 'hash' => $row['hash']];
			}
			
			// Rarest first, so the subquery's anchor scans the smallest posting list -- the same ordering
			// _getSqlQueryPartsForTokenFrequencies() uses, and pass 1 already paid for the counts.
			uasort($required_terms, fn($a, $b) => intval($alone[$a['hash']] ?? 0) <=> intval($alone[$b['hash']] ?? 0));
			$required_terms = array_values($required_terms);
			
			// A requirement nothing matches makes every combination zero, so pass 2 would be a wall of zeros
			// with no information in it. Report the standalone counts and let the caller say why.
			if($required_terms && min(array_map(fn($t) => intval($alone[$t['hash']] ?? 0), $required_terms)) < 1) {
				$out['required_docs'] = 0;
				return $this->_classifyTermStats($out);
			}
			
			// Pass 2 -- the same counts, restricted to files carrying every required term
			$counting_scope = $scope_sql;
			
			if($required_terms) {
				$counting_scope = $this->_getRequiredSubquery($model, $required_terms, $scope_sql);
				
				$required_counts = array_column(
					$this->_getDocFrequencies($model, $index_tokens, $allow_wildcards, $allow_stemming, $counting_scope),
					'docs', 'hash'
				);
				
				foreach($out['terms'] as &$row) {
					if(!is_null($row['hash']))
						$row['docs'] = intval($required_counts[$row['hash']] ?? 0);
				}
				unset($row);
				
				// A required term counted inside its own set IS the intersection size, so the baseline is free
				$out['required_docs'] = intval($required_counts[$required_terms[0]['hash']] ?? 0);
				
			} else {
				foreach($out['terms'] as &$row)
					$row['docs'] = $row['alone'];
				unset($row);
			}
			
			$out['terms'] = $this->_withExpandedTokens($model, $out['terms'], $counting_scope);
			
		} catch (\Throwable $e) {
			DevblocksPlatform::logException($e);
			return $out;
		}
		
		return $this->_classifyTermStats($out);
	}
	
	/** Splice each wildcard/stem term's concrete tokens in as child rows, right after their parent. */
	private function _withExpandedTokens(Model_SearchIndex $model, array $terms, string $scope_sql) : array {
		$out = [];
		
		foreach($terms as $term) {
			$out[] = $term;
			
			$token = strval($term['token'] ?? '');
			
			if($term['parent'] ?? null)
				continue;
			
			if(!str_contains($token, '*') && !str_ends_with($token, '~'))
				continue;
			
			foreach($this->_expandToken($model, $token, $scope_sql) as $concrete => $meta) {
				$out[] = [
					'term' => $term['term'], 'token' => $concrete, 'hash' => null,
					'docs' => $meta['docs'], 'alone' => $meta['docs'],
					'status' => 'ok', 'required' => false, 'parent' => $token,
					'truncated' => $meta['truncated'],
				];
			}
		}
		
		return $out;
	}
	
	/** ok / none / apart, once both counts are known. `apart` needs a pin and is the one worth spelling out. */
	private function _classifyTermStats(array $out) : array {
		foreach($out['terms'] as &$row) {
			if('common' == $row['status'])
				continue;
			
			$row['status'] = match(true) {
				// A pin that matches nothing never ran pass 2, so `alone` is the only real number here --
				// calling these 'apart' would blame the words for a pin that was never satisfiable.
				is_null($row['docs']) => (intval($row['alone']) > 0 ? 'ok' : 'none'),
				intval($row['docs']) > 0 => 'ok',
				intval($row['alone']) > 0 => 'apart',
				default => 'none',
			};
		}
		
		return $out;
	}
	
	private function _getSqlQueryPartsForTokenFrequencies(Model_SearchIndex $model, array $doc_frequencies, string $scope_sql='') : array {
		$db = DevblocksPlatform::services()->database();
		
		$select_sql = $join_sql = $where_sql = $tables = [];
		$counter = 0;
		
		foreach($doc_frequencies as $term) {
			$mode = match(true) {
				str_contains($term['token'], '*') => 'wildcard',
				str_ends_with($term['token'], '~') => 'stem',
				default => 'term',
			};
			
			$tables[$term['hash']] = sprintf('s%d', $counter);
			
			if($counter) {
				$join_sql[] = sprintf('JOIN search_index_%1$d s%2$d ON (s0.record_id = s%2$d.record_id%3$s)',
					$model->id,
					$counter,
					'term' == $mode ? sprintf(' AND s%d.token_hash = %d', $counter, $term['hash']) : '',
				);
			} else {
				$select_sql[] = 'DISTINCT s0.record_id';
				$join_sql[] = sprintf('FROM search_index_%d s0', $model->id);
			}
			
			if('wildcard' == $mode)
				$join_sql[] = sprintf('JOIN search_index_tokens t%1$d ON (s%1$d.token_hash = t%1$d.token_hash AND t%1$d.token LIKE %2$s)',
					$counter,
					$db->qstr(str_replace('*', '%', $term['token']))
				);
			elseif('stem' == $mode)
				$join_sql[] = sprintf('JOIN search_index_tokens t%1$d ON (s%1$d.token_hash = t%1$d.token_hash AND t%1$d.stem = %2$s)',
					$counter,
					$db->qstr(PorterStemmer::Stem(rtrim($term['token'], '~')))
				);
			elseif('term' == $mode && !$counter)
				$where_sql[] = sprintf('s0.token_hash = %d', $term['hash']);
			
			$counter++;
		}
		
		// Narrow BEFORE the LIMIT, which is the whole point -- afterwards the k is already spent. Only s0
		// needs it: s1..sN join on `s0.record_id = sK.record_id`, so they're transitively narrowed already,
		// and repeating the predicate on them just pulls the optimizer off the exact PK point lookup.
		if($scope_sql)
			$where_sql[] = sprintf('s0.record_id IN (%s)', $scope_sql);
		
		return [
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'tables' => $tables
		];
	}
	
	// Split a query into included (default) and excluded (-prefix) terms on whitespace
	// Note: This will not be double-quote safe when we introduce phrases.
	private function _splitQueryTerms(string $query) : array {
		$include = [];
		$exclude = [];

		foreach(preg_split('/\s+/', trim($query)) as $chunk) {
			if($chunk === '') continue;

			if(str_starts_with($chunk, '-') && strlen($chunk) > 1) {
				$exclude[] = substr($chunk, 1);
			} else {
				$include[] = $chunk;
			}
		}

		return [
			'include' => implode(' ', $include),
			'exclude' => implode(' ', $exclude),
		];
	}

	public function queryJoinFromRecordQuickSearch(Model_SearchIndex $model, string $query, string $fields='', array $co_params=[]): string {
		if(!$query) return '-1';
		
		// Receive filters like `top:`
		$fields = \CerbQuickSearchLexer::getFieldsFromQuery($fields);

		// If `top:` parameter is provided, use scoring and limit
		if(($field_top = \CerbQuickSearchLexer::getFieldByKey('top', $fields))) {
			/* @var DevblocksSearchCriteria $field_top */
			$param_top = DevblocksSearchCriteria::getNumberParamFromTokens($field_top->key, $field_top->tokens);
			$top_k = DevblocksPlatform::intClamp($param_top->value ?? 1, 1, 1_000);
			
			if($top_k) {
				$docs = $this->queryDocumentsWithScore($model, $query, limit: $top_k, co_params: $co_params);
				
				$doc_ids = DevblocksPlatform::sanitizeArray(
					array_column($docs, 'id'),
					'int'
				);
				
				if(is_array($doc_ids) && $doc_ids) {
					return implode(',', $doc_ids);
				}
				
				return '-1';
			}
		}
		
		try {
			$allow_wildcards = !($model->extension_params['wildcards_disable'] ?? false);
			$allow_stemming = !($model->extension_params['stemming_disable'] ?? false);
			
			// Split into included and excluded terms
			$query_terms = $this->_splitQueryTerms($query);
			
			// We refuse to only exclude, so bail if no included terms
			if(!$query_terms['include']) return '-1';
			
			// Redundant with the outer WHERE on this branch (there's no LIMIT to spend), but it keeps one
			// code path and lets the optimizer narrow before the token joins instead of after.
			$scope_sql = $this->_getScopeSubquery($model, $co_params);

			$doc_frequencies = $this->getTokenStats(
				$model,
				$query_terms['include'],
				allow_wildcards: $allow_wildcards,
				allow_stemming: $allow_stemming,
				scope_sql: $scope_sql
			);
			
			if(!$doc_frequencies) return '-1';
			
			// If the rarest term is zero, match nothing w/ AND operator
			if($doc_frequencies[array_key_first($doc_frequencies)]['docs'] == 0) return '-1';
			
			// Get ordered nested subqueries for tokens
			$sql_query_parts = $this->_getSqlQueryPartsForTokenFrequencies($model, $doc_frequencies, $scope_sql);
			
			// Handle excluded terms
			if(($negation_where = $this->_getNegationWhereClause($query_terms['exclude'], $model, $allow_wildcards, $allow_stemming, 5, $scope_sql)))
				$sql_query_parts['where'][] = $negation_where;
			
			// Assemble the query from the rarest term
			return sprintf('SELECT %s %s %s',
				implode(',', $sql_query_parts['select']),
				implode(' ', $sql_query_parts['join']),
				($sql_query_parts['where'] ?? null)
					? sprintf('WHERE %s', implode(' AND ', $sql_query_parts['where']))
					: ''
			);
			
		} catch (\Throwable $e) {
			DevblocksPlatform::logException($e);
			return '-1';
		}
	}
	
	public function queryDocumentsWithScore(Model_SearchIndex $model, string $query, int $limit = 100, array $co_params=[]): array {
		$db = DevblocksPlatform::services()->database();
		
		if(!$query) return [];
		
		$allow_wildcards = !($model->extension_params['wildcards_disable'] ?? false);
		$allow_stemming = !($model->extension_params['stemming_disable'] ?? false);
		
		// Split into included and excluded terms
		$query_terms = $this->_splitQueryTerms($query);
		
		// We refuse to only exclude, so bail if no included terms
		if(!$query_terms['include']) return [];
		
		// Both the candidate set and the IDF behind the ranking are limited to this
		$scope_sql = $this->_getScopeSubquery($model, $co_params);

		$doc_frequencies = $this->getTokenStats(
			$model,
			$query_terms['include'],
			allow_wildcards: $allow_wildcards,
			allow_stemming: $allow_stemming,
			scope_sql: $scope_sql
		);
		
		if(!$doc_frequencies) return [];
		
		// Get ordered nested subqueries for tokens
		$sql_query_parts = $this->_getSqlQueryPartsForTokenFrequencies($model, $doc_frequencies, $scope_sql);
		
		// Index IDFs by hash for scoring
		$idfs = array_column($doc_frequencies, 'idf', 'hash');
		
		// Append score per term
		$idf_scores = array_map(
			fn($hash) => sprintf(
				'MAX(%s.token_tf) * %f',
				$sql_query_parts['tables'][$hash],
				$idfs[$hash] ?? 0.0
			),
			array_keys($sql_query_parts['tables'])
		);
		
		$sql_query_parts['select'][] = sprintf('(%s) AS score',
			implode(' + ', $idf_scores)
		);

		// Handle excluded terms
		if(($negation_where = $this->_getNegationWhereClause($query_terms['exclude'], $model, $allow_wildcards, $allow_stemming, 5, $scope_sql)))
			$sql_query_parts['where'][] = $negation_where;
		
		// Assemble query
		$sql = sprintf('SELECT %s %s %s GROUP BY s0.record_id ORDER BY score DESC LIMIT %d',
			implode(', ', $sql_query_parts['select']),
			implode(' ', $sql_query_parts['join']),
			($sql_query_parts['where'] ?? null)
				? sprintf('WHERE %s', implode(' AND ', $sql_query_parts['where']))
				: '',
			$limit
		);
		
		try {
			$rows = $db->GetArrayReader($sql);
		} catch (\Throwable) {
			$rows = [];
		}
		
		if(!is_array($rows)) return [];
		
		return array_map(
			fn($row) => [
				'id' => intval($row['record_id'] ?? 0),
				'score' => floatval($row['score'] ?? 0)
			],
			$rows,
		);
	}

	private function _createSearchIndexTable(Model_SearchIndex $model) : bool {
		$db = DevblocksPlatform::services()->database();
		
		$table_name = sprintf('search_index_%d', $model->id);
		$lock_name = sprintf('search_index:create:%d', $model->id);
		
		if(!$db->getLock($lock_name, 10))
			return false;
		
		// The CREATE's early return used to skip the release, holding the lock until the connection
		// closed -- which in a long-lived drain blocks every later attempt in the same process.
		try {
			$sql = sprintf(
				<<< EOD
				CREATE TABLE IF NOT EXISTS %s (
					token_hash BIGINT NOT NULL DEFAULT 0,
					record_id INT UNSIGNED NOT NULL DEFAULT 0,
					token_tf FLOAT UNSIGNED NOT NULL DEFAULT 0,
					PRIMARY KEY (token_hash, record_id),
					INDEX (record_id)
				) ENGINE=%s
				EOD,
				$db->escape($table_name),
				$db->escape(APP_DB_ENGINE),
			);
			
			if(!$db->ExecuteMaster($sql))
				return false;
			
			DevblocksPlatform::clearCache(DevblocksEngine::CACHE_TABLES);
			
			return true;
			
		} finally {
			$db->releaseLock($lock_name);
		}
	}
	
	private function _searchTableExists(Model_SearchIndex $model) : bool {
		$tables = DevblocksPlatform::getDatabaseTables();
		$table_name = sprintf('search_index_%d', $model->id);
		
		return array_key_exists($table_name, $tables);
	}

	private function _flushInsertBuffer(Model_SearchIndex $model, array &$insert_values) : void {
		if(!$insert_values) return;
		
		$db = DevblocksPlatform::services()->database();
		
		$db->ExecuteMaster(sprintf(
			"INSERT IGNORE INTO search_index_%d (token_hash, record_id, token_tf) VALUES %s",
			$model->id,
			implode(',', $insert_values),
		));
		
		$insert_values = [];
	}

	// Only buffer each seen token_hash once this batch
	private function _rememberTokenHashes(array $token_hashes) : void {
		if(count(self::$_indexed_token_hashes) >= self::MAX_INDEXED_TOKEN_HASHES)
			self::$_indexed_token_hashes = [];
		
		foreach($token_hashes as $token_hash) {
			self::$_indexed_token_hashes[$token_hash] = true;
		}
	}

	// Write with a bounded retry on deadlock (1213) / lock-wait timeout
	// (1205). ExecuteMaster returns false (and logs) on error rather than
	// throwing, so we inspect the connection's errno to decide whether to retry.
	// Safe under autocommit: each statement is its own transaction, so a 1213
	// rolls back only that statement, and the retry re-runs just it.
	private function _executeWithDeadlockRetry(string $sql, int $max_attempts=3) : bool {
		$db = DevblocksPlatform::services()->database();

		for($attempt=1; $attempt <= $max_attempts; $attempt++) {
			if(false !== $db->ExecuteMaster($sql))
				return true;

			$errno = mysqli_errno($db->getMasterConnection());

			// Not a contention error — no point retrying.
			if(!in_array($errno, [1213, 1205]))
				return false;

			// Exponential backoff with jitter before the next attempt.
			if($attempt < $max_attempts)
				usleep((2 ** $attempt) * 10_000 + random_int(0, 10_000));
		}

		return false;
	}

	// Flush the per-batch set of (token_hash => "(hash, token, stem)") tuples into
	// the shared search_index_tokens dictionary. The dictionary is write-once, so
	// this runs OUTSIDE the bulk doc transaction (in autocommit) to release locks
	// in milliseconds, inserts only the rows that don't already exist, and
	// serializes the write behind an advisory lock so the duplicate-key
	// shared→exclusive lock upgrade (the actual cause of the 1213s) can't form.
	private function _flushTokenDictionary(array &$tuples_by_hash, &$error=null) : bool {
		if(!$tuples_by_hash) return true;

		$db = DevblocksPlatform::services()->database();

		// Consistent lock order -- cheap insurance even with the advisory lock.
		ksort($tuples_by_hash, SORT_NUMERIC);

		// Diff against existing rows with a lock-free consistent read, then drop
		// the hashes that already exist. Once the dictionary is warm, most batches
		// insert nothing. A replica-lag false-miss is harmless: INSERT IGNORE plus
		// the advisory lock below make a redundant insert a no-op, never a deadlock.
		$existing = $db->GetArrayReader(sprintf(
			"SELECT token_hash FROM search_index_tokens WHERE token_hash IN (%s)",
			implode(',', array_map('intval', array_keys($tuples_by_hash))),
		)) ?: [];

		if($existing) {
			$existing_hashes = array_column($existing, 'token_hash');
			$this->_rememberTokenHashes($existing_hashes);
			
			foreach($existing_hashes as $token_hash)
				unset($tuples_by_hash[$token_hash]);
		}

		// Nothing new to write.
		if(!$tuples_by_hash) {
			$tuples_by_hash = [];
			return true;
		}

		// Single-writer on the dictionary: this makes the duplicate-key deadlock
		// structurally impossible. If the lock can't be acquired in time, we still
		// proceed — the retry below covers it — rather than dropping data.
		$lock_name = 'search_index:tokens';
		$have_lock = $db->getLock($lock_name, 10);

		$ok = true;

		try {
			// Chunk so individual statements stay reasonable on a cold batch.
			foreach(array_chunk($tuples_by_hash, 1_000, true) as $chunk) {
				$sql = sprintf(
					"INSERT IGNORE INTO search_index_tokens (token_hash, token, stem) VALUES %s",
					implode(',', $chunk),
				);

				if(!$this->_executeWithDeadlockRetry($sql)) {
					$ok = false;
					$error = 'Failed to write search_index_tokens (deadlock retries exhausted)';
					break;
				}

				$this->_rememberTokenHashes(array_keys($chunk));
			}

		} finally {
			if($have_lock)
				$db->releaseLock($lock_name);
		}

		$tuples_by_hash = [];

		return $ok;
	}
	
	private function _reindexCheckpointAtNow(Model_SearchIndex $model, array $query_parts) : void {
		$db = DevblocksPlatform::services()->database();
		
		$param_key_last_indexed_at = sprintf('search_index_%d.last_indexed_at', $model->id);
		$param_key_last_indexed_id = sprintf('search_index_%d.last_indexed_id', $model->id);
		
		$key_primary = $query_parts['key_primary'] ?? null;
		$key_updated = $query_parts['key_updated'] ?? null;
		
		if(!$key_primary || !$key_updated) return;
		
		$select_sql = sprintf(
			"SELECT %s AS record_id, %s AS updated_at ",
			$key_primary,
			$key_updated,
		);
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		$limit_sql = 'LIMIT 1';
		
		// Sort for synchronization
		$sort_sql = sprintf("ORDER BY %s DESC, %s DESC ",
			\Cerb_ORMHelper::escape($key_updated),
			\Cerb_ORMHelper::escape($key_primary),
		);
		
		// SQL
		$search_sql =
			$select_sql.
			$join_sql.
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		
		$result = $db->GetRowMaster($search_sql);
		
		DevblocksPlatform::setRegistryKey($param_key_last_indexed_at, $result['updated_at'] ?? 0, \DevblocksRegistryEntry::TYPE_NUMBER, persist: true);
		DevblocksPlatform::setRegistryKey($param_key_last_indexed_id, $result['record_id'] ?? 0, \DevblocksRegistryEntry::TYPE_NUMBER, persist: true);
		
		// Empty the index for the rebuild. TRUNCATE, not DROP
		// Everything the reindex job enqueues below therefore lands on an EMPTY table, which is what lets
		// `processQueue()` skip its pre-delete for job messages. See `indexDocumentsByIds($purge_first)`.
		$db->ExecuteMaster(sprintf("TRUNCATE TABLE search_index_%d", $model->id));
	}
	
	private function _reindexCreateJob(Model_SearchIndex $search_index, array $query_parts, &$error=null) : ?\Model_QueueJob {
		$db = DevblocksPlatform::services()->database();
		$active_worker = CerberusApplication::getActiveWorker();
		
		if (!($queue = DAO_Queue::getByName('cerb.search.index'))) {
			$error = 'Invalid queue';
			return null;
		}
		
		$queue_job_key = sprintf('search_index:%d:reindex', $search_index->id);
		
		$record_count = $this->getRecordCount($search_index);
		$batch_size = 100;

		$model = new Model_QueueJob();
		$model->queue_id = $queue->id;
		$model->name = 'Reindex ' . $search_index->name;
		$model->singleton_key = $queue_job_key; // One job per index at a time
		$model->count_total = $record_count;
		$model->status_id = \QueueJobStatus::RUNNING->value;
		$model->worker_id = $active_worker ? $active_worker->id : 0;
		$model->created_at = time();
		$model->metadata = [
			'search_index_id' => $search_index->id,
			'record_type' => $search_index->record_type
		];

		if (!($model = DAO_QueueJob::createFromModel($model))) {
			$error = 'Failed to create job';
			return null;
		}

		if(
			!($query_parts['primary_table'] ?? null)
			||!($query_parts['key_primary'] ?? null)
		) {
			$error = 'Invalid query';
			return null;
		}

		// The default 1024 bytes silently TRUNCATES the id list rather than erroring, and `cardinality`
		// is COUNT(id), so the job would report every record indexed over a partial index. 100 ids fit
		// until they reach 7 digits.
		$db->ExecuteMaster('SET SESSION group_concat_max_len = 1048576');

		$sql = sprintf("INSERT INTO queue_message (uuid, queue_id, job_id, status_id, created_at, message, cardinality) ".
			"SELECT UUID_TO_BIN(UUID()) AS uuid, ".
			"%d AS queue_id, ".
			"%d AS job_id, ".
			"0 AS status_id, ".
			"UNIX_TIMESTAMP() AS created_at, ".
			"CONCAT('{\"index_id\":',%d,',\"ids\":[',GROUP_CONCAT(id ORDER BY id),']}') AS message, ".
			"COUNT(id) AS cardinality ".
			"FROM (SELECT %s AS id, CEIL(ROW_NUMBER() OVER (ORDER BY %s) / %d) AS batch FROM %s) AS batched ".
			"GROUP BY batch",
			$model->queue_id,
			$model->id,
			$search_index->id,
			$db->escape($query_parts['key_primary']),
			$db->escape($query_parts['key_primary']),
			$batch_size,
			$db->escape($query_parts['primary_table'])
		);
		$db->ExecuteMaster($sql);
		
		return $model;
	}
	
	public function reindexDocumentsByModel(Model_SearchIndex $model, &$error=null): ?\Model_QueueJob {
		$error = null;
		
		// We need to check if this search table exists and create it if not
		if (!$this->_searchTableExists($model))
			$this->_createSearchIndexTable($model);
		
		$query_parts = $this->_getRecordQueryParts($model);
		
		// Checkpoint for later incremental search indexing
		$this->_reindexCheckpointAtNow($model, $query_parts);
		
		// Schedule an idempotent job to index the records
		return $this->_reindexCreateJob($model, $query_parts, $error);
	}
	
	/**
	 * Which keys getDictionariesFromModels() should bulk-load for this index.
	 *
	 * Every placeholder the content templates reference, so bulkLazyLoad() batches each embedded
	 * context once per page. Left to lazy loading these are one round trip per record: the Message
	 * Content template alone costs ~300 single-row queries per 100-record batch for
	 * `sender__label` + `ticket_subject` + `ticket_org__label`.
	 */
	private function _getRecordExpandKeys(Model_SearchIndex $model, string $record_template, string $record_template_boost) : array {
		if(array_key_exists($model->id, self::$_record_expand_by_index))
			return self::$_record_expand_by_index[$model->id];

		$tpl_builder = DevblocksPlatform::services()->templateBuilder();

		// Twig NameExpression nodes, so modifiers are already excluded. A template it can't parse
		// yields [] rather than throwing, which degrades to lazy loading.
		$keys = $tpl_builder->tokenize([$record_template, $record_template_boost]);

		// bulkLazyLoad() reads `<prefix>_id` off each dict to build its batch, so an outer context
		// has to land before an inner one's id exists: `ticket_` before `ticket_org_`.
		usort($keys, fn($a, $b) => substr_count($a, '_') <=> substr_count($b, '_'));

		$keys = array_values(array_unique(array_merge(['customfields'], $keys)));

		return self::$_record_expand_by_index[$model->id] = $keys;
	}

	/**
	 * @param bool $purge_first Drop these records' existing tokens before writing the new ones. Required
	 *   whenever a record may already be indexed: the insert is `INSERT IGNORE` on
	 *   `(token_hash, record_id)`, so without it a re-index only ADDS -- tokens for text the record no
	 *   longer contains keep matching it forever. Pass false only when the index was just emptied.
	 */
	public function indexDocumentsByIds(Model_SearchIndex $model, array $record_ids, &$error=null, bool $purge_first=true) : bool {
		$db = DevblocksPlatform::services()->database();
		$search = DevblocksPlatform::services()->search();
		$logger = DevblocksPlatform::services()->log();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		// A record type from an uninstalled or disabled plugin. Fail the message rather than fataling
		// the consumer, which would re-dequeue and fatal again forever.
		if(!($record_ext = $model->getRecordTypeExtension())) {
			$error = sprintf('Invalid record type: %s', $model->record_type);
			return false;
		}
		
		// We need to check if this search table exists and create it if not
		if (!$this->_searchTableExists($model))
			$this->_createSearchIndexTable($model);
		
		$record_template = ($model->extension_params['content'] ?? '') ?: '{{__label}}';
		$record_template_boost = ($model->extension_params['content_boost'] ?? '');
		
		$record_models = $record_ext->getModelObjects($record_ids);
		$record_expand = $this->_getRecordExpandKeys($model, $record_template, $record_template_boost);
		$record_dicts = \DevblocksDictionaryDelegate::getDictionariesFromModels($record_models, $record_ext->id, $record_expand);
		
		$buffer_insert_values = [];
		$buffer_tokens_to_hashes = [];
		$flushes = 0;
		
		$db->ExecuteMaster('SET unique_checks = 0');
		$db->ExecuteMaster('SET autocommit = 0');
		
		// Inside the transaction with the inserts that replace them, so readers never see the gap. A
		// token-heavy batch can still trip the mid-batch COMMIT below and expose one.
		if($purge_first && ($purge_ids = DevblocksPlatform::sanitizeArray($record_ids, 'int', ['nonzero','unique'])))
			$db->ExecuteMaster(sprintf("DELETE FROM search_index_%d WHERE record_id IN (%s)",
				$model->id,
				implode(',', $purge_ids)
			));
		
		foreach($record_dicts as $dict) {
			$doc_id = $dict->get('id');
			
			// Generate text to index from record
			$string_to_index = $tpl_builder->build($record_template, $dict);
			
			// Tokenize (consistently for indexing and querying)
			$tokens = $search->getTokensFromText($string_to_index, truncate: 25_000);
			
			if(!$tokens) continue;
			
			// Expand tokens (e.g. dots, dashes, underscores)
			$tokens = $search->expandTokens($tokens);
			
			// Index TF-IDF
			if(!($doc_token_frequencies = $search->indexTokens($tokens))) continue;
			
			// Boost TF
			if($record_template_boost) {
				$string_to_boost = $tpl_builder->build($record_template_boost, $dict);
				$tokens_boost = $search->getTokensFromText($string_to_boost, truncate: 1_000);
				$boost_frequencies = $search->indexTokens($tokens_boost);
				foreach(array_keys($boost_frequencies) as $token_hash) {
					if(array_key_exists($token_hash, $doc_token_frequencies))
						$doc_token_frequencies[$token_hash][1] += 2;
				}
			}
			
			// Map tokens to hashes for buffer
			foreach($doc_token_frequencies as $token_hash => $token_data) {
				// Never index empty tokens
				if('' === strval($token_data[0])) continue;

				$buffer_insert_values[] = sprintf('(%d, %d, %f)', $token_hash, $doc_id, $token_data[1]);

				// Buffer the dictionary row only if this hash hasn't already been
				// written this process and isn't yet pending in this buffer.
				if(
					!isset(self::$_indexed_token_hashes[$token_hash])
					&& !array_key_exists($token_hash, $buffer_tokens_to_hashes)
				) {
					$token = strval($token_data[0]);
					$buffer_tokens_to_hashes[$token_hash] = sprintf(
						'(%d, %s, %s)',
						$token_hash,
						$db->qstr($token),
						$db->qstr(ctype_alpha($token) ? PorterStemmer::Stem($token) : '')
					);
				}
			}
			
			// Flush only the per-index doc rows inside the bulk transaction; the
			// shared dictionary is written separately, after this commits. The
			// token buffer accumulates across the whole batch (deduped above).
			if(count($buffer_insert_values) >= 4_500) {
				$this->_flushInsertBuffer($model, $buffer_insert_values);
				$flushes++;
			}
			
			if(4 == $flushes) {
				$db->ExecuteMaster('COMMIT');
				$flushes = 0;
			}
		}
		
		$logger->info(sprintf("Indexed %d records for index #%d (%s)", count($record_ids), $model->id, $model->name));
		
		if($buffer_insert_values)
			$this->_flushInsertBuffer($model, $buffer_insert_values);

		$db->ExecuteMaster('COMMIT');
		
		$db->ExecuteMaster('SET unique_checks = 1');
		$db->ExecuteMaster('SET autocommit = 1');

		// Now that the bulk doc transaction is committed, write the shared token
		// dictionary in autocommit (short-lived locks) with a diff + advisory lock
		// + deadlock retry. Propagate failure so the queue message is marked FAILED
		// instead of silently DONE.
		if(!$this->_flushTokenDictionary($buffer_tokens_to_hashes, $error))
			return false;

		// Clear the record count cache after indexing
		$this->_clearCache($model);
		
		return true;
	}
	
	public function indexDocumentsByModel(Model_SearchIndex $model, int $limit = 250): array {
		$db = DevblocksPlatform::services()->database();
		
		// `last_indexed_at`/`_id` is one shared position, so two concurrent walkers each advance it past
		// records the other indexed -- those records are then never indexed by either. Skipping is safe:
		// whatever this pass misses, the next one starts from the same place and picks up.
		$lock_name = sprintf('search_index:walk:%d', $model->id);
		
		if(!$db->getLock($lock_name))
			return [];
		
		try {
			return $this->_indexDocumentsByModel($model, $limit);
		} finally {
			$db->releaseLock($lock_name);
		}
	}
	
	private function _indexDocumentsByModel(Model_SearchIndex $model, int $limit = 250): array {
		$db = DevblocksPlatform::services()->database();
		
		// We need to check if this search table exists and create it if not
		if(!$this->_searchTableExists($model))
			$this->_createSearchIndexTable($model);
		
		$param_key_last_indexed_at = sprintf('search_index_%d.last_indexed_at', $model->id);
		$param_key_last_indexed_id = sprintf('search_index_%d.last_indexed_id', $model->id);

		$last_indexed_at = DevblocksPlatform::getRegistryKey($param_key_last_indexed_at, \DevblocksRegistryEntry::TYPE_NUMBER,0);
		$last_indexed_id = DevblocksPlatform::getRegistryKey($param_key_last_indexed_id, \DevblocksRegistryEntry::TYPE_NUMBER,0);
		
		$query_parts = $this->_getRecordQueryParts($model);
		
		$key_primary = $query_parts['key_primary'] ?? null;
		$key_updated = $query_parts['key_updated'] ?? null;
		
		if(!$key_primary || !$key_updated) return [];
		
		$query_parts['select'] = sprintf(
			"SELECT %s AS record_id, %s AS updated_at ",
			$key_primary,
			$key_updated,
		);
		
		$select_sql = $query_parts['select'];
		$join_sql = $query_parts['join'];
		$where_sql = $query_parts['where'];
		
		// Filter by synchronization timestamp (if resuming)
		if($last_indexed_at || $last_indexed_id) {
			$where_sql .= ($where_sql ? 'AND ' : 'WHERE ') .
				sprintf(
					"(%s > %d OR (%s = %d AND %s > %d)) ",
					\Cerb_ORMHelper::escape($key_updated),
					$last_indexed_at,
					\Cerb_ORMHelper::escape($key_updated),
					$last_indexed_at,
					\Cerb_ORMHelper::escape($key_primary),
					$last_indexed_id,
				);
		}
		
		// Sort for synchronization
		$sort_sql = sprintf("ORDER BY %s ASC, %s ASC ",
			\Cerb_ORMHelper::escape($key_updated),
			\Cerb_ORMHelper::escape($key_primary),
		);
		
		// Limit
		$limit_sql = ($limit ? (sprintf('LIMIT %d ', $limit)) : '');
		
		// SQL
		$search_sql =
			$select_sql.
			$join_sql.
			$where_sql.
			$sort_sql.
			$limit_sql
		;
		
		$results = $db->GetArrayReader($search_sql);
		
		if(!is_array($results) || empty($results))
			return [];
		
		$record_ids = DevblocksPlatform::sanitizeArray(array_column($results, 'record_id'), 'int');
		
		// Index this set of records
		$error = null;
		
		$this->indexDocumentsByIds($model, $record_ids, $error);
		
		// Checkpoint for the next sync
		$last_result = end($results);
		DevblocksPlatform::setRegistryKey($param_key_last_indexed_at, intval($last_result['updated_at']), \DevblocksRegistryEntry::TYPE_NUMBER, persist: true);
		DevblocksPlatform::setRegistryKey($param_key_last_indexed_id, intval($last_result['record_id']), \DevblocksRegistryEntry::TYPE_NUMBER, persist: true);
		
		return $record_ids;
	}
	
	public function deleteDocumentsByIds(Model_SearchIndex $model, array $record_ids) : bool {
		if(!($record_ids = DevblocksPlatform::sanitizeArray($record_ids, 'int', ['nonzero','unique'])))
			return true;

		if(!$this->_searchTableExists($model))
			return true;

		$db = DevblocksPlatform::services()->database();

		// Rows per record scale with its token count, not with 1, so a bulk delete of large documents
		// removes a lot here. It's still cheaper than the alternative: nothing else ever removes these,
		// and a background sweep would cost O(index) forever to collect O(deletes) of garbage.
		$db->ExecuteMaster(sprintf("DELETE FROM search_index_%d WHERE record_id IN (%s)",
			$model->id,
			implode(',', $record_ids)
		));

		$this->_clearCache($model);

		return true;
	}

	public function deleteIndex(Model_SearchIndex $model): bool {
		$db = DevblocksPlatform::services()->database();
		$registry = DevblocksPlatform::services()->registry();
		
		$sql = sprintf("DROP TABLE IF EXISTS search_index_%d", $model->id);
		$db->ExecuteMaster($sql);
		
		DevblocksPlatform::clearCache(DevblocksEngine::CACHE_TABLES);
		
		$param_key_last_indexed_at = sprintf('search_index_%d.last_indexed_at', $model->id);
		$registry->delete($param_key_last_indexed_at);
		
		$param_key_last_indexed_id = sprintf('search_index_%d.last_indexed_id', $model->id);
		$registry->delete($param_key_last_indexed_id);
		
		return true;
	}
	
	/**
	 * @param $exclude
	 * @param Model_SearchIndex $model
	 * @param bool $allow_wildcards
	 * @param bool $allow_stemming
	 * @param int $max_terms
	 * @return string
	 */
	private function _getNegationWhereClause($exclude, Model_SearchIndex $model, bool $allow_wildcards, bool $allow_stemming, int $max_terms=5, string $scope_sql='') : string {
		if(!$exclude)
			return '';
		
		// Scoped stats, or the "least common term" this picks to exclude by is chosen from documents the
		// caller can't see. The NOT EXISTS itself needs no scope -- it already keys off s0.record_id.
		if(!($exclude_tokens = $this->getTokenStats($model, $exclude, $allow_wildcards, $allow_stemming, $max_terms, $scope_sql)))
			return '';
		
		// Exclude token hashes from the least common term for efficiency
		if (($exclude_hashes = array_column($exclude_tokens, 'hash'))) {
			return sprintf(
				'NOT EXISTS (SELECT 1 FROM search_index_%d x WHERE x.record_id = s0.record_id AND x.token_hash IN (%s))',
				$model->id,
				implode(',', $exclude_hashes)
			);
		}
		
		return '';
	}
}