<?php
class DevblocksDateTest extends PHPUnit_Framework_TestCase {
	final function __construct($name = null, array $data = array(), $dataName = '') {
		parent::__construct($name, $data, $dataName);
	}
	
	function testParseDateRange() {
		$date = DevblocksPlatform::services()->date();
		
		// Implied full ending date
		
		$expected = [
			strtotime('Jan 1 2019 00:00:00'),
			strtotime('Dec 31 2019 23:59:59')
		];
		
		$results = $date->parseDateRange('Jan 1 2019 to Dec 31 2019');;
		
		$this->assertTrue(is_array($results));
		
		$actual = [
			$results['from_ts'],
			$results['to_ts'],
		];
		
		$this->assertEquals($expected, $actual);
		
		// Relative, array input, implied times
		
		$expected = [
			strtotime('first day of this month 00:00:00'),
			strtotime('last day of this month 23:59:59')
		];
		
		$results = $date->parseDateRange(['first day of this month', 'last day of this month']);
		
		$this->assertTrue(is_array($results));
		
		$actual = [
			$results['from_ts'],
			$results['to_ts'],
		];
		
		$this->assertEquals($expected, $actual);
		
		// Relative, string input, explicit times
		
		$expected = [
			strtotime('-30 days 08:00:00'),
			strtotime('-7 days 17:00:00')
		];
		
		$results = $date->parseDateRange('-30 days 8am to -7 days 5pm');
		
		$this->assertTrue(is_array($results));
		
		$actual = [
			$results['from_ts'],
			$results['to_ts'],
		];
		
		$this->assertEquals($expected, $actual);
		
		// Relative, string input, implied times
		
		$expected = [
			strtotime('-30 days 00:00:00'),
			strtotime('-7 days 23:59:59')
		];
		
		$results = $date->parseDateRange('-30 days to -7 days');
		
		$this->assertTrue(is_array($results));
		
		$actual = [
			$results['from_ts'],
			$results['to_ts'],
		];
		
		$this->assertEquals($expected, $actual);
		
		// Relative days, string input, implied times
		
		$expected = [
			strtotime('yesterday 00:00:00'),
			strtotime('tomorrow 23:59:59')
		];
		
		$results = $date->parseDateRange('yesterday to tomorrow');
		
		$this->assertTrue(is_array($results));
		
		$actual = [
			$results['from_ts'],
			$results['to_ts'],
		];
		
		$this->assertEquals($expected, $actual);
		
		// Relative and current days, string input, implied times
		
		$expected = [
			strtotime('yesterday 00:00:00'),
			strtotime('now')
		];
		
		$results = $date->parseDateRange('yesterday to now');
		
		$this->assertTrue(is_array($results));
		
		$actual = [
			$results['from_ts'],
			$results['to_ts'],
		];
		
		$this->assertEquals($expected, $actual);
		
		// Mixed absolute and relative with explicit end time
		
		$expected = [
			strtotime('Jan 1 2019 00:00:00'),
			strtotime('today 17:00:00')
		];
		
		$results = $date->parseDateRange('Jan 1 2019 to today 5pm');
		
		$this->assertTrue(is_array($results));
		
		$actual = [
			$results['from_ts'],
			$results['to_ts'],
		];
		
		$this->assertEquals($expected, $actual);
		
		// Shortcuts
		
		$expected = [
			strtotime('first day of this month 00:00:00'),
			strtotime('last day of this month 23:59:59')
		];
		
		$results = $date->parseDateRange('this month');
		
		$this->assertTrue(is_array($results));
		
		$actual = [
			$results['from_ts'],
			$results['to_ts'],
		];
		
		$this->assertEquals($expected, $actual);
	}
}
