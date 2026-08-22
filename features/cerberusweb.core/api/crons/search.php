<?php
class SearchCron extends CerberusCronPageExtension {
	function run() {
		$logger = DevblocksPlatform::services()->log();
		$runtime = microtime(true);
		
		$logger->info("[Search] Starting...");
		
		$stop_time = time() + 30;

		// Run custom search indexes
		$this->_processSearchIndexes($stop_time);

		$logger->info("[Search] Total Runtime: ".number_format((microtime(true)-$runtime)*1000,2)." ms");
	}

	function configure($instance) {
	}
	
	private function _processSearchIndexes(int $stop_time) : void {
		$registry = DevblocksPlatform::services()->registry();
		$search_indexes = DAO_SearchIndex::getAll();
		
		shuffle($search_indexes);
		
		foreach($search_indexes as $search_index) {
			$limit = 250;
			
			$search_ext = $search_index->getExtension();
			if(!$search_ext->hasOption('index')) continue;
			
			// Skip if sampled within the last 5 minutes
			$registry_key = sprintf('search_index_%d.metrics_sampled_at', $search_index->id);
			$last_ts = $registry->get($registry_key, DevblocksRegistryEntry::TYPE_NUMBER, 0);

			// Periodically sample each index's indexed-record count into a gauge metric so the
			// worklist can render a sparkline instead of an expensive live COUNT(DISTINCT) per row.
			// Throttled per-index to once per 5 minutes (the finest metric bin) to bound the cost.
			if(!($last_ts && (time() - $last_ts) < 300))
				$search_ext->sampleRecordCountMetric($search_index);
			
			// If we're indexing fast and have more, let it keep going
			for($i=0 ;$i<10; $i++) {
				$count = count($search_ext->indexDocumentsByModel($search_index, $limit));
				if($count < $limit || $stop_time < time()) break;
			}
			
			// Stop indexing if we hit the scheduler time limit
			if($stop_time < time()) break;
		}
	}
};