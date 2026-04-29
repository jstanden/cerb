<?php
class SearchCron extends CerberusCronPageExtension {
	function run() {
		$logger = DevblocksPlatform::services()->log();
		$runtime = microtime(true);
		
		$logger->info("[Search] Starting...");
		
		$stop_time = time() + 30;
		
		// Run custom search indexes
		$this->_processSearchIndexes($stop_time);

		// Run search schema extensions
		if($stop_time > time())
			$this->_processSearchSchemas($stop_time);
		
		$logger->info("[Search] Total Runtime: ".number_format((microtime(true)-$runtime)*1000,2)." ms");
	}
	
	function configure($instance) {
	}
	
	private function _processSearchSchemas(int $stop_time) : void {
		// Loop through search schemas and batch index by ID or timestamp
		$schemas = DevblocksPlatform::getExtensions('devblocks.search.schema', true);

		shuffle($schemas);
		
		foreach($schemas as $schema) {
			if($stop_time > time()) {
				if($schema instanceof Extension_DevblocksSearchSchema)
					$schema->index($stop_time);
			}
		}
	}
	
	private function _processSearchIndexes(int $stop_time) : void {
		$search_indexes = DAO_SearchIndex::getAll();
		
		shuffle($search_indexes);
		
		foreach($search_indexes as $search_index) {
			$limit = 250;
			
			$search_ext = $search_index->getExtension();
			if(!$search_ext->hasOption('index')) continue;
			
			// [TODO] Add an option to fetch models by ID, or a page of results, when not a queue job
			
			// If we're indexing fast and have more, let it keep going
			for($i=0 ;$i<10; $i++) {
				$count = $search_ext->indexDocumentsByModel($search_index, $limit);
				if($count < $limit || $stop_time < time()) break;
			}
			
			// Stop indexing if we hit the scheduler time limit
			if($stop_time < time()) break;
		}
	}
};