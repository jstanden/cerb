<?php

namespace Cerb\Extensions\SearchIndex;

use Cerb\Extensions\Extension_SearchIndex;
use Cerb\Services\Search\PorterStemmer;
use DevblocksEngine;
use DevblocksPlatform;
use Model_SearchIndex;

class SearchIndex_Fulltext extends Extension_SearchIndex {
	const ID = 'cerb.search.index.fulltext';
	
	function renderConfig(Model_SearchIndex $model) : void {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('model', $model);
		$tpl->display('devblocks:cerberusweb.core::records/types/search_index/fulltext/config.tpl');
	}
	
	function invokeConfig($config_action, Model_SearchIndex $model) : void {
	}
	
	private function _getRecordQueryParts(Model_SearchIndex $model) {
		$record_ext = $model->getRecordTypeExtension();
		$record_query = $model->extension_params['record_query'] ?? '';
		
		$view = $record_ext->getTempView();
		$view->addParamsWithQuickSearch($record_query);
		$view->setAutoPersist(false);
		
		$dao_class = $record_ext->getDaoClass();
		
		if(!method_exists($dao_class, 'getSearchQueryComponents'))
			return [];
		
		return $dao_class::getSearchQueryComponents(
			[],
			$view->getParams()
		);
	}
	
	private function _clearCache(Model_SearchIndex $model) : void {
		$cache = DevblocksPlatform::services()->cache();
		$cache_key = sprintf("search_index:%d:count", $model->id);
		$cache->remove($cache_key);
	}
	
	public function getRecordCount(Model_SearchIndex $model, bool $no_cache=false): int {
		$cache = DevblocksPlatform::services()->cache();
		
		$cache_key = sprintf("search_index:%d:count", $model->id);
		
		if($no_cache || null === ($count = $cache->load($cache_key))) {
			$db = DevblocksPlatform::services()->database();
			
			// Use the engine's record query to constrain the total docs (vs. index)
			
			$record_ext = $model->getRecordTypeExtension();
			$search_class = $record_ext->getSearchClass();
			
			if(!method_exists($search_class, 'getPrimaryKey'))
				return 0;
			
			$primary_key = $search_class::getPrimaryKey();
			
			if(!$this->_searchTableExists($model))
				return 0;
			
			$query_parts = $this->_getRecordQueryParts($model);
			
			$select_sql = sprintf('SELECT COUNT(%s) AS total_docs ', $db->escape($primary_key));
			$join_sql = $query_parts['join'];
			$where_sql = $query_parts['where'];
			
			// SQL (no sorting for count)
			$search_sql =
				$select_sql.
				$join_sql.
				$where_sql
			;
			
			try {
				$count = $db->GetOneReader($search_sql);
				$cache->save(intval($count), $cache_key);
				
			} catch (\Throwable) {
				$count = 0;
			}
		}
		
		return intval($count);
	}
	
	public function getTokenStats(Model_SearchIndex $model, string $query, bool $allow_wildcards = false, bool $allow_stemming = false, int $max_terms=10): array {
		$db = DevblocksPlatform::services()->database();
		$search = DevblocksPlatform::services()->search();
		
		if(!$query) return [];
		
		try {
			$total_docs = $this->getRecordCount($model);
			$query_tokens = $search->getTokensFromText($query, allow_wildcards: $allow_wildcards, allow_stemming: $allow_stemming);
			$index_tokens = $search->indexTokens($query_tokens);
			
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
						") ",
						$model->id,
						$db->qstr(str_replace('*', '%', $token_term['token']))
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
						") ",
						$model->id,
						$db->qstr(PorterStemmer::Stem(rtrim($token_term['token'], '~')))
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
					"WHERE token_hash IN (%s) " .
					"GROUP BY token_hash",
					$model->id,
					$token_term_hashes
				);
				$results = $db->GetArrayReader($sql);
				
				// hash => hits (including zeroes on non-matches)
				foreach ($results as $result) {
					$doc_frequencies[$result['token_hash']]['docs'] = intval($result['hits']);
				}
			}
			
			// Sort by rarest terms first
			DevblocksPlatform::sortObjects($doc_frequencies, '[docs]');
			
			// If the rarest term is zero, match nothing w/ AND operator
			if($doc_frequencies[array_key_first($doc_frequencies)]['docs'] == 0) return [];
			
			// Pre-calculate TF-IDF
			$doc_frequencies = array_map(
				fn($term) => array_merge($term, ['idf' => log($total_docs / $term['docs'])]),
				$doc_frequencies
			);
			
			// Sort by the highest IDF score first
			DevblocksPlatform::sortObjects($doc_frequencies, '[idf]', false);
			
			// Use the top 10 terms by IDF
			$doc_frequencies = array_slice($doc_frequencies, 0, $max_terms, true);
			
			return array_values($doc_frequencies);
			
		} catch (\Throwable) {
			return [];
		}
	}
	
	private function _getSqlQueryPartsForTokenFrequencies(Model_SearchIndex $model, array $doc_frequencies) : array {
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
		
		return [
			'select' => $select_sql,
			'join' => $join_sql,
			'where' => $where_sql,
			'tables' => $tables
		];
	}
	
	public function queryJoinFromRecordQuickSearch(Model_SearchIndex $model, string $query, string $fields=''): string {
		if(!$query) return '-1';
		
		try {
			$allow_wildcards = !($model->extension_params['wildcards_disable'] ?? false);
			$allow_stemming = !($model->extension_params['stemming_disable'] ?? false);
			
			$doc_frequencies = $this->getTokenStats(
				$model,
				$query,
				allow_wildcards: $allow_wildcards,
				allow_stemming: $allow_stemming
			);
			
			if(!$doc_frequencies) return '-1';
			
			// If the rarest term is zero, match nothing w/ AND operator
			if($doc_frequencies[array_key_first($doc_frequencies)]['docs'] == 0) return '-1';
			
			// Get ordered nested subqueries for tokens
			$sql_query_parts = $this->_getSqlQueryPartsForTokenFrequencies($model, $doc_frequencies);
			
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
	
	public function queryDocumentsWithScore(Model_SearchIndex $model, string $query, int $limit = 100): array {
		$db = DevblocksPlatform::services()->database();
		
		if(!$query) return [];
		
		$allow_wildcards = !($model->extension_params['wildcards_disable'] ?? false);
		$allow_stemming = !($model->extension_params['stemming_disable'] ?? false);
		
		$doc_frequencies = $this->getTokenStats(
			$model,
			$query,
			allow_wildcards: $allow_wildcards,
			allow_stemming: $allow_stemming
		);
		
		if(!$doc_frequencies) return [];
		
		// Get ordered nested subqueries for tokens
		$sql_query_parts = $this->_getSqlQueryPartsForTokenFrequencies($model, $doc_frequencies);
		
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
	}
	
	private function _searchTableExists(Model_SearchIndex $model) : bool {
		$db = DevblocksPlatform::services()->database();
		
		$tables = $db->metaTables();
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

	private function _flushTokenBuffer(array &$insert_values) : void {
		if(!$insert_values) return;
		
		$db = DevblocksPlatform::services()->database();
		
		$db->ExecuteMaster(sprintf(
			"INSERT IGNORE INTO search_index_tokens (token_hash, token, stem) VALUES %s",
			implode(',', $insert_values),
		));
		
		$insert_values = [];
	}
	
	public function indexDocumentsByModel(Model_SearchIndex $model, int $limit = 250): array {
		$db = DevblocksPlatform::services()->database();
		$search = DevblocksPlatform::services()->search();
		$logger = DevblocksPlatform::services()->log();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		// We need to check if this search table exists and create it if not
		if(!$this->_searchTableExists($model))
			$this->_createSearchIndexTable($model);
		
		$param_key_last_indexed_at = sprintf('search_index_%d.last_indexed_at', $model->id);
		$param_key_last_indexed_id = sprintf('search_index_%d.last_indexed_id', $model->id);

		$last_indexed_at = DevblocksPlatform::getRegistryKey($param_key_last_indexed_at, \DevblocksRegistryEntry::TYPE_NUMBER,0);
		$last_indexed_id = DevblocksPlatform::getRegistryKey($param_key_last_indexed_id, \DevblocksRegistryEntry::TYPE_NUMBER,0);
		
		$next_indexed_at = $last_indexed_at;
		$next_indexed_id = $last_indexed_id;
		
		$record_ext = $model->getRecordTypeExtension();
		$record_template = ($model->extension_params['content'] ?? '') ?: '{{__label}}';
		
		$search_class = $record_ext->getSearchClass();
		
		if(!method_exists($search_class, 'getPrimaryKey')) return [];
		$key_primary = $search_class::getPrimaryKey();
		
		if(!method_exists($search_class, 'getUpdatedKey')) return [];
		$key_updated = $search_class::getUpdatedKey();
		
		if(!$key_primary || !$key_updated) return [];
		
		$query_parts = $this->_getRecordQueryParts($model);
		
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
		
		if(!is_array($results) || empty($results)) return [];
		
		$record_ids = DevblocksPlatform::sanitizeArray(array_column($results, 'record_id'), 'int');
		$results = array_combine($record_ids, $results);
		
		$record_models = $record_ext->getModelObjects($record_ids);
		$record_expand = ['customfields'];
		$record_dicts = \DevblocksDictionaryDelegate::getDictionariesFromModels($record_models, $record_ext->id, $record_expand);
		
		$buffer_insert_values = [];
		$buffer_tokens_to_hashes = [];
		$flushes = 0;
		
		$db->ExecuteMaster('SET unique_checks = 0');
		$db->ExecuteMaster('SET autocommit = 0');
		
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
			
			// Map tokens to hashes for buffer
			foreach($doc_token_frequencies as $token_hash => $token_data) {
				$buffer_insert_values[] = sprintf('(%d, %d, %f)', $token_hash, $doc_id, $token_data[1]);
				
				if(!array_key_exists($token_hash, $buffer_tokens_to_hashes)) {
					$token = strval($token_data[0]);
					$buffer_tokens_to_hashes[$token_hash] = sprintf(
						'(%d, %s, %s)',
						$token_hash,
						$db->qstr($token),
						$db->qstr(ctype_alpha($token) ? PorterStemmer::Stem($token) : '')
					);
				}
			}
			
			if(
				count($buffer_insert_values) >= 4_500
				|| count($buffer_tokens_to_hashes) >= 4_500
			) {
				$this->_flushInsertBuffer($model, $buffer_insert_values);
				$this->_flushTokenBuffer($buffer_tokens_to_hashes);
				$flushes++;
			}
			
			if(4 == $flushes) {
				$db->ExecuteMaster('COMMIT');
				$flushes = 0;
			}
		
			$next_indexed_at = $results[$doc_id]['updated_at'];
			$next_indexed_id = $doc_id;
		}
		
		$logger->info(sprintf("Indexed %d records for index #%d (%s)", count($results), $model->id, $model->name));
		
		if($buffer_insert_values)
			$this->_flushInsertBuffer($model, $buffer_insert_values);
		
		if($buffer_tokens_to_hashes)
			$this->_flushTokenBuffer($buffer_tokens_to_hashes);
		
		$db->ExecuteMaster('COMMIT');
		
		// Checkpoint
		DevblocksPlatform::setRegistryKey($param_key_last_indexed_at, $next_indexed_at, \DevblocksRegistryEntry::TYPE_NUMBER, persist: true);
		DevblocksPlatform::setRegistryKey($param_key_last_indexed_id, $next_indexed_id, \DevblocksRegistryEntry::TYPE_NUMBER, persist: true);
		
		$db->ExecuteMaster('SET unique_checks = 1');
		$db->ExecuteMaster('SET autocommit = 1');
		
		// Clear the record count cache after indexing
		$this->_clearCache($model);
		
		return array_keys($record_dicts);
	}
	
	public function deleteIndex(Model_SearchIndex $model): bool {
		$db = DevblocksPlatform::services()->database();
		$registry = DevblocksPlatform::services()->registry();
		
		$sql = sprintf("DROP TABLE IF EXISTS search_index_%d", $model->id);
		$db->ExecuteMaster($sql);
		
		$param_key_last_indexed_at = sprintf('search_index_%d.last_indexed_at', $model->id);
		$registry->delete($param_key_last_indexed_at);
		
		$param_key_last_indexed_id = sprintf('search_index_%d.last_indexed_id', $model->id);
		$registry->delete($param_key_last_indexed_id);
		
		return true;
	}
}