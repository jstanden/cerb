<?php
class _DevblocksStatsService {
	private static ?_DevblocksStatsService $_instance = null;

	static function getInstance(): _DevblocksStatsService {
		if (is_null(self::$_instance))
			self::$_instance = new _DevblocksStatsService();
		
		return self::$_instance;
	}
	
	private function __construct() {}
	
	public function count(array $array) : int {
		return count($array);
	}
	
	public function max(array $array) : ?float {
		if(0 === ($this->count($array)))
			return null;
		
		return max($array);
	}
	
	public function mean(array $array) : ?float {
		if(0 == ($count = $this->count($array)))
			return null;
		
		return $this->sum($array) / $count;
	}
	
	public function median(array $array) : ?float {
		$count = $this->count($array);
		
		if(0 == $count)
			return 0;
		
		if(1 == $count)
			return current($array);
		
		sort($array);
		
		if($count & 1) { // Odd
			return $array[floor($count/2)];
		} else { // Even
			return ($array[floor($count/2)-1] + $array[floor($count/2)])/2;
		}
	}
	
	public function min(array $array) : ?float {
		if(0 === ($this->count($array)))
			return null;
		
		return min($array);
	}
	
	public function mode(array $array) : ?float {
		if(!($counts = array_count_values($array)))
			return null;
		
		arsort($counts);
		
		return key($counts);
	}
	
	public function stdevp(array $array) : ?float {
		if(null === ($variance = $this->varp($array)))
			return null;
		
		return sqrt($variance);
	}
	
	public function stdevs(array $array, int $decimals=2) : ?float {
		if(null === ($variance = $this->vars($array)))
			return null;
		
		return sqrt($variance);
	}
	
	public function sum(array $array) : float {
		return array_sum($array);
	}
	
	public function varp(array $array) : ?float {
		if(null === ($mean = $this->mean($array)))
			return null;
		
		$sum_of_squares = array_sum(array_map(fn($n) => pow($n - $mean, 2), $array));
		$n = $this->count($array);
		
		return $sum_of_squares / $n;
	}
	
	public function vars(array $array) : ?float {
		if(null === ($mean = $this->mean($array)))
			return null;
		
		$sum_of_squares = array_sum(array_map(fn($n) => pow($n - $mean, 2), $array));
		$n = $this->count($array);
		
		return $sum_of_squares / ($n - 1);
	}
}
