<?php

use Cerb\Extensions\QueueConsumer\QueueConsumer_Manual;

class Cron_BackgroundQueue extends CerberusCronPageExtension {
	function configure($instance) {
	}
	
	function run() {
		$logger = DevblocksPlatform::services()->log('Background Queue');
		$runtime = microtime(true);

		$stop_time = time() + 25;

		$logger->info("Starting...");

		// Reap stalled claims first (covers manual queues the poll below skips)
		if(($reaped = DevblocksPlatform::services()->queue()->reapStalledMessages()))
			$logger->info(sprintf("Reclaimed %d stalled message(s)", $reaped));

		// Loop until we're done, or we run out of time
		while($stop_time > time() && $this->_pollQueues($stop_time))
			continue;
		
		$logger->info("Total Runtime: ".number_format((microtime(true)-$runtime)*1000,2)." ms");
	}

	private function _pollQueues(int $stop_time) : bool {
		$logger = DevblocksPlatform::services()->log('Background Queue');

		$available_counts = DAO_QueueMessage::getAvailableCountsByQueue();

		// Bail out if no work to do
		if(!$available_counts)
			return false;

		// Filter available queues
		$queues = array_filter(DAO_Queue::getAll(), function(Model_Queue $queue) use ($available_counts) {
			// No available messages
			if(!array_key_exists($queue->id, $available_counts))
				return false;
			
			// Exclude manually managed queues
			if($queue->extension_id == QueueConsumer_Manual::ID)
				return false;
			
			return true;
		});
		
		if(empty($queues))
			return false;
		
		// Random timeshare
		shuffle($queues);
		
		foreach($queues as $queue) {
			if(!($queue_extension = $queue->getExtension()))
				continue;
			
			if($stop_time <= time())
				break;
			
			$count_hint = $available_counts[$queue->id] ?? 0;
			$logger->info(sprintf("Dispatching messages to queue `%s` (%d available)", $queue->name, $count_hint));
			$processed = $queue_extension->processQueueMessages($queue, $stop_time, $count_hint);
			$logger->info(sprintf("Queue `%s` processed %d messages", $queue->name, $processed));
		}
		
		return true;
	}
};