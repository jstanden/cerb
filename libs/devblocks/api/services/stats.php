<?php
class _DevblocksStatsService {
	private static ?_DevblocksStatsService $_instance = null;

	static function getInstance(): _DevblocksStatsService {
		if (is_null(self::$_instance))
			self::$_instance = new _DevblocksStatsService();
		
		return self::$_instance;
	}
	
	private function __construct() {}
	
	public function cosineSimilarity($vectorA, $vectorB) : float {
		if(extension_loaded('bcmath')) {
			return $this->_cosineSimilarityBcMath($vectorA, $vectorB);
		} else {
			return $this->_cosineSimilarityPurePhp($vectorA, $vectorB);
		}
	}
	
	/** @noinspection PhpComposerExtensionStubsInspection */
	private function _cosineSimilarityBcMath($vectorA, $vectorB) : float {
		$dotProduct = '0';
		$magnitudeA = '0';
		$magnitudeB = '0';
		$scale = 20;
		
		$funcConvSciNotation = function($n) {
			return sprintf('%.20f', $n);
		};
		
		$vectorA = array_map($funcConvSciNotation, $vectorA);
		$vectorB = array_map($funcConvSciNotation, $vectorB);
		
		try {
			foreach ($vectorA as $key => $value) {
				if(array_key_exists($key, $vectorB)) {
					$dotProduct = bcadd($dotProduct, bcmul($value, $vectorB[$key], $scale), $scale);
				}
				$magnitudeA = bcadd($magnitudeA, bcmul($value, $value, $scale), $scale);
			}
			
			foreach($vectorB as $value) {
				$magnitudeB = bcadd($magnitudeB, bcmul($value, $value, $scale), $scale);
			}
			
			$magnitudeA = bcsqrt($magnitudeA, $scale);
			$magnitudeB = bcsqrt($magnitudeB, $scale);
			
			if(0 == bccomp($magnitudeA, '0', $scale) || 0 == bccomp($magnitudeB, '0', $scale))
				return 0; // Avoid division by zero
			
			return (float) bcdiv($dotProduct, bcmul($magnitudeA, $magnitudeB, $scale), $scale);
			
		} catch (Throwable) {
			return 0;
		}
	}
	
	private function _cosineSimilarityPurePhp($vectorA, $vectorB) : float {
		$dotProduct = 0;
		$magnitudeA = 0;
		$magnitudeB = 0;
		
		foreach($vectorA as $key => $value) {
			if(array_key_exists($key, $vectorB)) {
				$dotProduct += $value * $vectorB[$key];
			}
			$magnitudeA += $value * $value;
		}
		
		foreach($vectorB as $value) {
			$magnitudeB += $value * $value;
		}
		
		$magnitudeA = sqrt($magnitudeA);
		$magnitudeB = sqrt($magnitudeB);
		
		if($magnitudeA == 0 || $magnitudeB == 0) {
			return 0; // Avoid division by zero
		}
		
		return $dotProduct / ($magnitudeA * $magnitudeB);
	}
	
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
