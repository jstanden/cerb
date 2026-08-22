<?php

namespace Cerb\Extensions;

use Context_SearchIndex;
use DevblocksExtension;
use DevblocksExtensionGetterTrait;
use Model_SearchIndex;

abstract class Extension_SearchIndex extends DevblocksExtension {
	use DevblocksExtensionGetterTrait;
	
	const POINT = 'cerb.search.index';
	
	static $_registry = [];
	
	abstract function renderConfig(Model_SearchIndex $model) : void;
	
	abstract function invokeConfig($config_action, Model_SearchIndex $model) : void;
	
	function saveConfig(array $fields, $id, &$error = null): bool {
		return true;
	}
	
	abstract public function indexDocumentsByIds(Model_SearchIndex $model, array $record_ids, &$error=null, bool $purge_first=true) : bool;
	abstract public function indexDocumentsByModel(Model_SearchIndex $model, int $limit=25) : array;
	abstract public function reindexDocumentsByModel(Model_SearchIndex $model) : ?\Model_QueueJob;

	abstract public function getIndexRecordCount(Model_SearchIndex $model, bool $no_cache=false) : int;

	// Sample this index's record count into the `cerb.search.index.records` gauge and stamp the
	// per-index throttle key. Called periodically by the search cron and once right after a
	// reindex job completes.
	public function sampleRecordCountMetric(Model_SearchIndex $model) : void {
		$metrics = \DevblocksPlatform::services()->metrics();
		$registry = \DevblocksPlatform::services()->registry();

		$count = $this->getIndexRecordCount($model);

		$metrics->increment('cerb.search.index.records', $count, [
			'index_id' => $model->id,
			'engine' => $model->extension_id,
		]);

		$registry->set(
			sprintf('search_index_%d.metrics_sampled_at', $model->id),
			time(),
			\DevblocksRegistryEntry::TYPE_NUMBER
		);
	}

	public function initializeIndex(Model_SearchIndex $model): bool {
		return true;
	}

	/**
	 * Drop these records from the index because they no longer exist.
	 *
	 * Called from the `context.delete` listener, so it arrives batched and covers every record type at
	 * once. Default is a no-op for engines with nothing to clean up.
	 */
	public function deleteDocumentsByIds(Model_SearchIndex $model, array $record_ids) : bool {
		return true;
	}

	/**
	 * @param array $co_params The rest of the caller's search params, so the engine can score WITHIN the
	 *   caller's scope. Without them `top:k` picks its k across the whole index and the outer WHERE
	 *   intersects afterward, which returns nothing when the scope is a small subset of a big index.
	 *   Threading this on the base (rather than in one engine) is what lets a composite or embedding
	 *   engine pass the same scope down to its children unchanged.
	 */
	abstract public function queryJoinFromRecordQuickSearch(Model_SearchIndex $model, string $query, string $fields='', array $co_params=[]): string;
	
	abstract public function queryDocumentsWithScore(Model_SearchIndex $model, string $query, int $limit = 100, array $co_params=[]): array;
	
	/**
	 * Per-term document counts for the words a caller typed: what each matches alone, and (with $required)
	 * in combination with the required words. For explaining an empty result and for planning a query.
	 *
	 * NOT abstract, and empty by default: "how many documents contain this term" is a question a keyword
	 * engine can answer and an embedding engine cannot, so forcing it would only produce stubs returning
	 * this same []. Callers read [] as "this engine can't say" and degrade. It lives here rather than on
	 * the fulltext engine so the terminal keeps resolving an index by record type + the `query` option,
	 * never by extension id -- and so a composite can forward it to its fulltext child unchanged.
	 */
	public function queryTermStats(Model_SearchIndex $model, array $words, array $required=[], array $co_params=[]) : array {
		return [];
	}
	
	abstract public function deleteIndex(Model_SearchIndex $model): bool;
	
	/**
	 * @internal
	 */
	public function export(Model_SearchIndex $model): string {
		$source_json = [
			'search_index' => [
				'uid' => 'search_index_' . $model->id,
				'_context' => Context_SearchIndex::ID,
				'extension_id' => $model->extension_id,
				'extension_params' => $model->extension_params,
				'name' => $model->name,
				'record_filter' => $model->record_filter,
				'record_type' => $model->record_type,
				'uri' => $model->uri,
			]
		];
		
		return json_encode($source_json);
	}
}

;