<?php
class Cron_Metrics extends CerberusCronPageExtension {
	function run() {
		$logger = DevblocksPlatform::services()->log('Metrics');
		$runtime = microtime(true);

		$stop_time = time() + 25;

		$logger->info("Starting...");
		
		do {
			if(false == $this->checkQueueAndProcess())
				break;
			
		} while($stop_time > time());
		
		$logger->info("Total Runtime: ".number_format((microtime(true)-$runtime)*1000,2)." ms");
	}

	function configure($instance) {
	}
	
	function checkQueueAndProcess() : bool {
		$queue = DevblocksPlatform::services()->queue();
		$metrics = DevblocksPlatform::services()->metrics();
		$logger = DevblocksPlatform::services()->log('Metrics');
		
		$consumer_id = null;
		$limit = 100;
		
		$messages = $queue->dequeue('cerb.metrics.publish', $limit, $consumer_id);
		
		if(empty($messages))
			return false;
		
		$results = $metrics->processMessages($messages);
		
		$queue->reportSuccess($results['success'] ?? []);
		$queue->reportFailure($results['fail'] ?? []);
		
		$processed = count($results['success'] ?? []);
		
		if($processed)
			$logger->info(sprintf("Processed %d metric samples", $processed));
		
		return true;
	}
};