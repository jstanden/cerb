<?php

use Ramsey\Uuid\Provider\Node\RandomNodeProvider;
use Ramsey\Uuid\Uuid;

class _DevblocksMetricsService {
	private static ?_DevblocksMetricsService $_instance = null;
	
	private array $_buffer = [];

	static function getInstance() : _DevblocksMetricsService {
		if(is_null(self::$_instance))
			self::$_instance = new _DevblocksMetricsService();
		
		return self::$_instance;
	}
	
	private function __construct() {}
	
	function increment($metric_name, $values=1, array $dimensions=[], int $ts=null, $buffer=true) {
		if(!$ts)
			$ts = time();
		
		if(!is_array($values))
			$values = [$values];
		
		if($buffer) {
			$ts_5min = $ts - ($ts % 300);
			
			$dimension_keys = array_keys($dimensions);
			
			$hash_key = sha1(DevblocksPlatform::strLower(sprintf('%s||%s||%s||%d',
				$metric_name,
				implode('||', $dimension_keys),
				implode('||', $dimensions),
				$ts_5min
			)));
			
			if (array_key_exists($hash_key, $this->_buffer)) {
				$this->_buffer[$hash_key]['samples'] += count($values);
				$this->_buffer[$hash_key]['sum'] += array_sum($values);
				$this->_buffer[$hash_key]['min'] = min($this->_buffer[$hash_key]['min'], min($values));
				$this->_buffer[$hash_key]['max'] = max($this->_buffer[$hash_key]['max'], max($values));
				
			} else {
				$this->_buffer[$hash_key] = [
					'metric_name' => $metric_name,
					'dimensions' => $dimensions,
					'samples' => count($values),
					'sum' => array_sum($values),
					'min' => min($values),
					'max' => max($values),
					'ts' => $ts,
				];
			}
			
		} else {
			$nodeProvider = new RandomNodeProvider();
			
			$uuid = Uuid::uuid6($nodeProvider->getNode());
			
			$message = new Model_QueueMessage();
			$message->queue_id = 0;
			$message->uuid = $uuid->getHex();
			$message->message = [
				'metric_name' => $metric_name,
				'dimensions' => $dimensions,
				'samples' => count($values),
				'sum' => array_sum($values),
				'min' => min($values),
				'max' => max($values),
				'ts' => $ts,
			];
			
			return $this->processMessages([$message]);
		}
	}
	
	function publish() {
		$queue = DevblocksPlatform::services()->queue();
		
		if(empty($this->_buffer))
			return;
		
		// Flush the buffer into a queue
		$queue->enqueue('cerb.metrics.publish', $this->_buffer);
		
		// Reset the buffer
		$this->_buffer = [];
	}
	
	function maint() {
		DAO_MetricValue::gc();
	}
	
	/**
	 * @param Model_QueueMessage[] $messages
	 */
	function processMessages(array $messages): array {
		$db = DevblocksPlatform::services()->database();
		
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
		}
		
		return $results;
	}
}