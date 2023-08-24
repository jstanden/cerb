<?php
use PHPUnit\Framework\TestCase;

class DevblocksStatsTest extends TestCase {
	final function __construct($name = null, array $data = array(), $dataName = '') {
		parent::__construct($name, $data, $dataName);
	}
	
	function testCount() {
		$stats = DevblocksPlatform::services()->stats();
		$expected = 3;
		$actual = $stats->count([1,2,3]);
		$this->assertEquals($expected, $actual);
	}
	
	function testCosineSimilarity() {
		$stats = DevblocksPlatform::services()->stats();
		
		$vec_a = [1,2,3,4,5];
		$vec_b = [5,4,3,2,1];
		
		$expected = 0.6364;
		$actual = $stats->cosineSimilarity($vec_a, $vec_b);
		
		$this->assertEqualsWithDelta($expected, $actual, 0.001);
	}
	
	function testMax() {
		$stats = DevblocksPlatform::services()->stats();
		
		// Empty
		$expected = null;
		$actual = $stats->max([]);
		$this->assertEquals($expected, $actual);
		
		// Single
		$expected = 100;
		$actual = $stats->max([100,50,10,25]);
		$this->assertEquals($expected, $actual);
	}
	
	function testMean() {
		$stats = DevblocksPlatform::services()->stats();
		$expected = 3;
		$actual = $stats->mean([1,2,3,4,5]);
		$this->assertEquals($expected, $actual);
	}
	
	function testMedian() {
		$stats = DevblocksPlatform::services()->stats();
		
		// Empty
		$expected = null;
		$actual = $stats->median([]);
		$this->assertEquals($expected, $actual);
		
		// Single
		$expected = 100;
		$actual = $stats->median([100]);
		$this->assertEquals($expected, $actual);
		
		// Odd count
		$expected = 3;
		$actual = $stats->median([1,2,3,4,5,4,3,2,1]);
		$this->assertEquals($expected, $actual);
		
		// Even count
		$expected = 2.5;
		$actual = $stats->median([1,4,2,3]);
		$this->assertEquals($expected, $actual);
	}
	
	function testMin() {
		$stats = DevblocksPlatform::services()->stats();
		
		// Empty
		$expected = null;
		$actual = $stats->min([]);
		$this->assertEquals($expected, $actual);
		
		// Single
		$expected = 10;
		$actual = $stats->min([100,50,10,25]);
		$this->assertEquals($expected, $actual);
	}
	
	function testMode() {
		$stats = DevblocksPlatform::services()->stats();
		
		// Empty
		$expected = null;
		$actual = $stats->mode([]);
		$this->assertEquals($expected, $actual);
		
		// Single
		$expected = 100;
		$actual = $stats->mode([100]);
		$this->assertEquals($expected, $actual);
		
		// Tie
		$expected = 2;
		$actual = $stats->mode([1,2,3,4,5,4,3,2]);
		$this->assertEquals($expected, $actual);
		
		// Single
		$expected = 1;
		$actual = $stats->median([1,2,1,2,1,2,1]);
		$this->assertEquals($expected, $actual);
	}
	
	function testStdevp() {
		$stats = DevblocksPlatform::services()->stats();
		
		// Empty
		$expected = null;
		$actual = $stats->stdevp([]);
		$this->assertEquals($expected, $actual);
		
		// Stdev
		$expected = 1.87;
		$actual = round($stats->stdevp([6,2,3,1]), 2);
		$this->assertEquals($expected, $actual);
	}
	
	function testStdevs() {
		$stats = DevblocksPlatform::services()->stats();
		
		// Empty
		$expected = null;
		$actual = $stats->stdevs([]);
		$this->assertEquals($expected, $actual);
		
		// Stdev
		$expected = 2.16;
		$actual = round($stats->stdevs([6,2,3,1]), 2);
		$this->assertEquals($expected, $actual);
	}
	
	function testSum() {
		$stats = DevblocksPlatform::services()->stats();
		
		// Empty
		$expected = null;
		$actual = $stats->sum([]);
		$this->assertEquals($expected, $actual);
		
		// Single
		$expected = 100;
		$actual = $stats->sum([100]);
		$this->assertEquals($expected, $actual);
		
		// Single
		$expected = 55;
		$actual = $stats->sum(range(1,10));
		$this->assertEquals($expected, $actual);
	}
	
	function testVarp() {
		$stats = DevblocksPlatform::services()->stats();
		
		// Empty
		$expected = null;
		$actual = $stats->varp([]);
		$this->assertEquals($expected, $actual);
		
		// Single
		$expected = 5.81;
		$actual = round($stats->varp([4,6,7,9,10,11]), 2);
		$this->assertEquals($expected, $actual);
	}
	
	function testVars() {
		$stats = DevblocksPlatform::services()->stats();
		
		// Empty
		$expected = null;
		$actual = $stats->vars([]);
		$this->assertEquals($expected, $actual);
		
		// Single
		$expected = 6.97;
		$actual = round($stats->vars([4,6,7,9,10,11]), 2);
		$this->assertEquals($expected, $actual);
	}	
}
