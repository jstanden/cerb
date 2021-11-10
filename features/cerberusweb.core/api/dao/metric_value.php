<?php
class DAO_MetricValue {
	const PERIOD_MINS_5 = 300;
	const PERIOD_HOURS_1 = 3_600;
	const PERIOD_DAYS_1 = 86_400;
	
	/**
	 * @param int $metric_id
	 * @param array|int|Model_MetricValueSampleSet $values
	 * @param int|null $ts
	 * @param array $dimension_values
	 * @return bool
	 */
	static function increment(int $metric_id, $values, int $ts=null, array $dimension_values=[]) : bool {
		$db = DevblocksPlatform::services()->database();
		
		if(!$ts)
			$ts = time();
		
		$ts_5min = $ts - ($ts % self::PERIOD_MINS_5);
		$ts_1hr = $ts - ($ts % self::PERIOD_HOURS_1);
		$ts_1d = $ts - ($ts % self::PERIOD_DAYS_1);
		
		if(is_numeric($values)) {
			$values = [floatval($values)];
			
		} else if(is_array($values)) {
			$values = new Model_MetricValueSampleSet(
				count($values),
				array_sum($values),
				min($values),
				max($values)
			);
		}
		
		// Create a sample set if given an array
		if($values instanceof Model_MetricValueSampleSet) {
			$insert_values = [];
			
			// 5 min
			$insert_values[] = sprintf("(%d, %d, %d, %d, %d, %d, %d, %d, %s, %s, %s)",
				$metric_id,
				$dimension_values[0] ?? 0,
				$dimension_values[1] ?? 0,
				$dimension_values[2] ?? 0,
				self::PERIOD_MINS_5,
				$ts_5min,
				$ts_5min + self::PERIOD_DAYS_1, // 1 day
				$values->samples,
				$db->qstr($values->sum),
				$db->qstr($values->min),
				$db->qstr($values->max)
			);
			
			// 1 hour
			$insert_values[] = sprintf("(%d, %d, %d, %d, %d, %d, %d, %d, %s, %s, %s)",
				$metric_id,
				$dimension_values[0] ?? 0,
				$dimension_values[1] ?? 0,
				$dimension_values[2] ?? 0,
				self::PERIOD_HOURS_1,
				$ts_1hr,
				$ts_1hr + (self::PERIOD_DAYS_1 * 14), // 14 days
				$values->samples,
				$db->qstr($values->sum),
				$db->qstr($values->min),
				$db->qstr($values->max)
			);
			
			// 1 day
			$insert_values[] = sprintf("(%d, %d, %d, %d, %d, %d, %d, %d, %s, %s, %s)",
				$metric_id,
				$dimension_values[0] ?? 0,
				$dimension_values[1] ?? 0,
				$dimension_values[2] ?? 0,
				self::PERIOD_DAYS_1,
				$ts_1d,
				0, // forever
				$values->samples,
				$db->qstr($values->sum),
				$db->qstr($values->min),
				$db->qstr($values->max)
			);
			
			$db->ExecuteWriter(sprintf("INSERT INTO metric_value (metric_id, dim0_value_id, dim1_value_id, dim2_value_id, granularity, bin, expires_at, samples, sum, min, max) ".
				"VALUES %s ".
				"ON DUPLICATE KEY UPDATE samples=samples+VALUES(samples), sum=sum+VALUES(sum), min=LEAST(min,VALUES(min)), max=GREATEST(max,VALUES(max))",
				implode(',', $insert_values)
			));
			
		} else {
			return false;
		}
		
		return true;
	}
	
	public static function gc() {
		$db = DevblocksPlatform::services()->database();
		
		// An expires_at of 0 is forever
		$db->ExecuteWriter(sprintf("DELETE FROM metric_value WHERE expires_at BETWEEN 1 AND %d", time()));
		
		return true;
	}
};
