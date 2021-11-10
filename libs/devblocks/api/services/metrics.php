<?php
class _DevblocksMetricsService {
	private static ?_DevblocksMetricsService $_instance = null;
	
	private array $_buffer = [];

	static function getInstance() : _DevblocksMetricsService {
		if(is_null(self::$_instance))
			self::$_instance = new _DevblocksMetricsService();
		
		return self::$_instance;
	}
	
	private function __construct() {}
	
	function increment($metric_name, $values=1, array $dimensions=[], int $ts=null) {
		if(!$ts)
			$ts = time();
		
		if(!is_array($values))
			$values = [$values];
		
		$ts_5min = $ts - ($ts % 300);
		
		$dimension_keys = array_keys($dimensions);
		
		$hash_key = sha1(DevblocksPlatform::strLower(sprintf('%s||%s||%s||%d',
			$metric_name,
			implode('||', $dimension_keys),
			implode('||', $dimensions),
			$ts_5min
		)));
		
		if(array_key_exists($hash_key, $this->_buffer)) {
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
}