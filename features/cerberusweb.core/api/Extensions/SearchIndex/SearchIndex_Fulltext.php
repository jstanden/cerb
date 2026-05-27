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
	
	function renderConfig(Model_SearchIndex $model) : void {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('model', $model);
		$tpl->display('devblocks:cerberusweb.core::records/types/search_index/fulltext/config.tpl');
	}
	
	function invokeConfig($config_action, Model_SearchIndex $model) : void {
	}
	
	private function _getRecordQueryParts(Model_SearchIndex $model) : array {
		$record_ext = $model->getRecordTypeExtension();
		$record_query = $model->extension_params['record_query'] ?? '';
		
		$view = $record_ext->getTempView();
		$view->addParamsWithQuickSearch($record_query);
		$view->setAutoPersist(false);
		
		$dao_class = $record_ext->getDaoClass();
		$search_class = $record_ext->getSearchClass();
		
		if(!method_exists($dao_class, 'getSearchQueryComponents'))
			return [];
		
		$query_parts = $dao_class::getSearchQueryComponents(
			[],
			$view->getParams()
		);
		
		$query_parts['key_primary'] = null;
		$query_parts['key_updated'] = null;
		
		if(method_exists($search_class, 'getPrimaryKey'))
			$query_parts['key_primary'] = $search_class::getPrimaryKey();
		
		if(method_exists($search_class, 'getUpdatedKey'))
			$query_parts['key_updated'] = $search_class::getUpdatedKey();
		
		return $query_parts;
	}
	
	private function _clearCache(Model_SearchIndex $model) : void {
		$cache = DevblocksPlatform::services()->cache();
		$cache_key = sprintf("search_index:%d:count", $model->id);
		$cache->remove($cache_key);
	}
	
	// [TODO] This should probably move to the search index model
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
	
	public function initializeIndex(Model_SearchIndex $model): bool {
		if($this->_searchTableExists($model))
			return true;

		return $this->_createSearchIndexTable($model);
	}

	public function getIndexRecordCount(Model_SearchIndex $model, bool $no_cache=false): int {
		if(!$this->_searchTableExists($model))
			return 0;

		$cache = DevblocksPlatform::services()->cache();
		$cache_key = sprintf('search_index:%d:indexed_count', $model->id);

		if($no_cache || null === ($count = $cache->load($cache_key))) {
			$db = DevblocksPlatform::services()->database();

			try {
				$count = $db->GetOneReader(sprintf(
					'SELECT COUNT(DISTINCT record_id) FROM search_index_%d',
					$model->id,
				));
				$cache->save(intval($count), $cache_key, [], 60);
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
			if($doc_frequencies[array_key_first($doc_frequencies) ?? '']['docs'] == 0) return [];
			
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
		
		// Receive filters like `top:`
		$fields = \CerbQuickSearchLexer::getFieldsFromQuery($fields);

		// If `top:` parameter is provided, use scoring and limit
		if(($field_top = \CerbQuickSearchLexer::getFieldByKey('top', $fields))) {
			/* @var DevblocksSearchCriteria $field_top */
			$param_top = DevblocksSearchCriteria::getNumberParamFromTokens($field_top->key, $field_top->tokens);
			$top_k = DevblocksPlatform::intClamp($param_top->value ?? 1, 1, 1_000);
			
			if($top_k) {
				$docs = $this->queryDocumentsWithScore($model, $query, limit: $top_k);
				
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
		$lock_name = sprintf('create:%s', $table_name);
		
		if(!($db->GetOneMaster(sprintf("SELECT GET_LOCK(%s, 10)", $db->qstr($lock_name)))))
			return false;
		
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
		
		$db->ExecuteMaster(sprintf("SELECT RELEASE_LOCK(%s)", $db->qstr($lock_name)));
		
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
		
		// Delete search index on reindex
		$sql = sprintf("DROP TABLE IF EXISTS search_index_%d", $model->id);
		$db->ExecuteMaster($sql);
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
		$model->count_available = $record_count;
		$model->status_id = \QueueJobStatus::RUNNING->value;
		$model->worker_id = $active_worker ? $active_worker->id : 0;
		$model->created_at = time();
		$model->metadata = [
			'search_index_id' => $search_index->id,
			'record_type' => $search_index->record_type
		];

		if (!($model = DAO_QueueJob::create($model))) {
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

		$sql = sprintf("INSERT INTO queue_message (uuid, queue_id, job_id, status_id, status_at, message, cardinality) ".
			"SELECT UUID_TO_BIN(UUID()) AS uuid, ".
			"%d AS queue_id, ".
			"%d AS job_id, ".
			"0 AS status_id, ".
			"UNIX_TIMESTAMP() AS status_at, ".
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
	
	public function indexDocumentsByIds(Model_SearchIndex $model, array $record_ids, &$error=null) : bool {
		$db = DevblocksPlatform::services()->database();
		$search = DevblocksPlatform::services()->search();
		$logger = DevblocksPlatform::services()->log();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		// We need to check if this search table exists and create it if not
		if (!$this->_searchTableExists($model))
			$this->_createSearchIndexTable($model);
		
		$record_ext = $model->getRecordTypeExtension();
		$record_template = ($model->extension_params['content'] ?? '') ?: '{{__label}}';
		$record_template_boost = ($model->extension_params['content_boost'] ?? '');
		
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
			
			// Boost TF
			if($record_template_boost) {
				$string_to_boost = $tpl_builder->build($record_template_boost, $dict);
				$tokens_boost = $search->getTokensFromText($string_to_boost, truncate: 1_000);
				$boost_frequencies = $search->indexTokens($tokens_boost);
				foreach(array_keys($boost_frequencies) as $token_hash) {
					$doc_token_frequencies[$token_hash][1] += 2;
				}
			}
			
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
		}
		
		$logger->info(sprintf("Indexed %d records for index #%d (%s)", count($record_ids), $model->id, $model->name));
		
		if($buffer_insert_values)
			$this->_flushInsertBuffer($model, $buffer_insert_values);
		
		if($buffer_tokens_to_hashes)
			$this->_flushTokenBuffer($buffer_tokens_to_hashes);
		
		$db->ExecuteMaster('COMMIT');
		
		$db->ExecuteMaster('SET unique_checks = 1');
		$db->ExecuteMaster('SET autocommit = 1');
		
		// Clear the record count cache after indexing
		$this->_clearCache($model);
		
		return true;
	}
	
	public function indexDocumentsByModel(Model_SearchIndex $model, int $limit = 250): array {
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