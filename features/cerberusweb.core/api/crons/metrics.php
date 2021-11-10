<?php
class Cron_Metrics extends CerberusCronPageExtension {
	function run() {
		$logger = DevblocksPlatform::services()->log('Metrics');
		$runtime = microtime(true);

		$stop_time = time() + 25;

		$logger->info("Starting...");
		
		do {
			if(false == $this->process())
				break;
			
		} while($stop_time > time());
		
		$logger->info("Total Runtime: ".number_format((microtime(true)-$runtime)*1000,2)." ms");
	}

	function configure($instance) {
	}
	
	function process() : bool {
		$queue = DevblocksPlatform::services()->queue();
		$db = DevblocksPlatform::services()->database();
		$logger = DevblocksPlatform::services()->log('Metrics');
		
		$consumer_id = null;
		$limit = 100;
		$processed = 0;
		
		$messages = $queue->dequeue('cerb.metrics.publish', $limit, $consumer_id);
		
		if(empty($messages))
			return false;
		
		$message_data = array_column($messages, 'message');
		
		// Create a map of unique metric names and their IDs
		
		$metrics = DAO_Metric::getByNames(array_unique(array_column($message_data, 'metric_name')));
		$metric_names_to_id = array_column($metrics, 'id', 'name');
		
		// Create a map of unique metric dimension keys/values and their IDs
		
		$dimensions = array_column($message_data, 'dimensions');
		$dimension_labels = [];
		
		$dimension_values = array_values(
			array_unique(
				array_merge(
					...array_map(
						fn($dims) => array_values($dims),
						$dimensions
					)
				)
			)
		);
		
		foreach($dimension_values as $v) {
			// Convert all non-ints to ints and map
			if(!(ctype_digit(strval($v)) && $v >= 0 && $v <= 4_294_967_296)) {
				$dimension_labels[strval($v)] = 0;
			}
		}
		
		// Which dimensions do we still not have an ID for?
		
		$unknown_dimensions = array_filter($dimension_labels, fn($id) => $id === 0);
		
		// Create unknown dimensions
		
		if($unknown_dimensions) {
			$insert_values = array_map(
				function($name) use ($db) {
					return sprintf('(%s)',
						$db->qstr(substr($name, 0, 255))
					);
				},
				array_keys($unknown_dimensions)
			);
			
			$db->ExecuteWriter(sprintf(
				'INSERT IGNORE INTO metric_dimension (name) ' .
				'VALUES %s',
				implode(',', $insert_values)
			));
			
			// Merge in IDs for newly created dimensions
			$dimension_labels = array_replace(
				$dimension_labels,
				array_column(DAO_MetricDimension::getByNames(array_keys($unknown_dimensions), DevblocksORMHelper::OPT_GET_MASTER_ONLY), 'id', 'name')
			);
		}
		
		$results = [
			'success' => [],
			'fail' => [],
		];
		
		foreach($messages as $message) { /* @var Model_QueueMessage $message */
			$message_data = $message->message;
			
			// Fail this queue message (unknown metric)
			if(!array_key_exists($message_data['metric_name'], $metric_names_to_id) 
				|| null == ($metric = ($metrics[$metric_names_to_id[$message_data['metric_name']]] ?? null))) {
				$results['fail'][] = $message->uuid;
				continue;
			}
			
			$metric_dimensions = $metric->getDimensions();
			$sample_dimensions = $message_data['dimensions'] ?? [];
			$sample_dimension_values = array_fill_keys(array_keys($metric_dimensions), 0);
			
			foreach($metric_dimensions as $dim_key => $dim_data) {
				if(array_key_exists($dim_key, $sample_dimensions)) {
					switch($dim_data['type'] ?? '') {
						case 'record':
							$sample_dimension_values[$dim_key] = intval($sample_dimensions[$dim_key]);
							break;
							
						default:
						case 'string':
							if(array_key_exists($sample_dimensions[$dim_key], $dimension_labels)) {
								$sample_dimension_values[$dim_key] = $dimension_labels[$sample_dimensions[$dim_key]];
							} else { // 32-bit unsigned int value
								$sample_dimension_values[$dim_key] = intval($sample_dimensions[$dim_key]);
							}
							break;
					}
				}
			}
			
			$values = new Model_MetricValueSampleSet(
				$message_data['samples'] ?? 0,
				$message_data['sum'] ?? 0,
				$message_data['min'] ?? 0,
				$message_data['max'] ?? 0,
			);
			
			$ts = $message_data['ts'] ?? null;
			
			DAO_MetricValue::increment($metric->id, $values, $ts, array_values($sample_dimension_values));
			
			$results['success'][] = $message->uuid;
			$processed++;
		}
		
		$queue->reportSuccess($results['success']);
		$queue->reportFailure($results['fail']);
		
		if($processed)
			$logger->info(sprintf("Processed %d metric samples", $processed));
		
		return true;
	}
};