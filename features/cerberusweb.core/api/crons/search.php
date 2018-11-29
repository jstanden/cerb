<?php
class SearchCron extends CerberusCronPageExtension {
	function run() {
		$logger = DevblocksPlatform::services()->log();
		$runtime = microtime(true);
		
		$logger->info("[Search] Starting...");
		
		// Loop through search schemas and batch index by ID or timestamp
		
		$schemas = DevblocksPlatform::getExtensions('devblocks.search.schema', true);

		$stop_time = time() + 30; // [TODO] Make configurable
		
		foreach($schemas as $schema) {
			if($stop_time > time()) {
				if($schema instanceof Extension_DevblocksSearchSchema)
					$schema->index($stop_time);
			}
		}
		
		$logger->info("[Search] Total Runtime: ".number_format((microtime(true)-$runtime)*1000,2)." ms");
	}
	
	function configure($instance) {
	}
};