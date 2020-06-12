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
		
		$results = $date->parseDateRange('Jan 1 2019 to Dec 31 2019');
		
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
		
		// Big bang, relative seconds, string input, explicit end time
		
		$expected = [
			0,
			strtotime('-30 seconds')
		];
		
		$results = $date->parseDateRange('big bang to -30 secs');
		
		$this->assertTrue(is_array($results));
		
		$actual = [
			$results['from_ts'],
			$results['to_ts'],
		];
		
		$this->assertEquals($expected, $actual);
		
		// Big bang, relative mins, string input, explicit end time
		
		$expected = [
			0,
			strtotime('-5 minutes')
		];
		
		$results = $date->parseDateRange('big bang to -5 mins');
		
		$this->assertTrue(is_array($results));
		
		$actual = [
			$results['from_ts'],
			$results['to_ts'],
		];
		
		$this->assertEquals($expected, $actual);
		
		// Big bang, relative start, string input
		
		$expected = [
			strtotime('-10 minutes'),
			time()
		];
		
		$results = $date->parseDateRange('-10 mins to now');
		
		$this->assertTrue(is_array($results));
		
		$actual = [
			$results['from_ts'],
			$results['to_ts'],
		];
		
		$this->assertEquals($expected, $actual);
		
		// Big bang, relative start, string input
		
		$expected = [
			strtotime('-10 minutes'),
			time()
		];
		
		$results = $date->parseDateRange('-10 mins to now');
		
		$this->assertTrue(is_array($results));
		
		$actual = [
			$results['from_ts'],
			$results['to_ts'],
		];
		
		$this->assertEquals($expected, $actual);
		
		// Big bang, relative hours, string input, explicit end time
		
		$expected = [
			0,
			strtotime('-6 hours')
		];
		
		$results = $date->parseDateRange('big bang to -6 hours');
		
		$this->assertTrue(is_array($results));
		
		$actual = [
			$results['from_ts'],
			$results['to_ts'],
		];
		
		$this->assertEquals($expected, $actual);
		
		// Big bang, relative days, string input, explicit end time
		
		$expected = [
			0,
			strtotime('-2 days 23:59:59')
		];
		
		$results = $date->parseDateRange('big bang to -2 days');
		
		$this->assertTrue(is_array($results));
		
		$actual = [
			$results['from_ts'],
			$results['to_ts'],
		];
		
		$this->assertEquals($expected, $actual);
		
		// Relative hours range, string input, explicit start/end time
		
		$expected = [
			strtotime('-12 hours'),
			strtotime('-6 hours')
		];
		
		$results = $date->parseDateRange('-12 hours to -6 hours');
		
		$this->assertTrue(is_array($results));
		
		$actual = [
			$results['from_ts'],
			$results['to_ts'],
		];
		
		$this->assertEquals($expected, $actual);
		
		// Relative compound range, string input
		
		$expected = [
			strtotime('-12 hours 30 mins'),
			strtotime('-6 hours 15 mins 20 seconds')
		];
		
		$results = $date->parseDateRange('-12 hours 30 mins to -6 hours 15 mins 20 secs');
		
		$this->assertTrue(is_array($results));
		
		$actual = [
			$results['from_ts'],
			$results['to_ts'],
		];
		
		$this->assertEquals($expected, $actual);
		
		// Relative compound range, string input
		
		$expected = [
			strtotime('-2 days 08:00:00'),
			strtotime('-1 day 12:00:00')
		];
		
		$results = $date->parseDateRange('-2 days 8am to -1 day noon');
		
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
		
		// Relative and current days, string input, implied times
		
		$expected = [
			strtotime('-2 days 03:00:00'),
			strtotime('yesterday 03:00:00')
		];
		
		$results = $date->parseDateRange('-2 days 3am to yesterday +3 hours');
		
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
	
	function testParseDays() {
		$date = DevblocksPlatform::services()->date();
		
		// Weekdays
		$expected = [1,2,3,4,5];
		$actual = $date->parseDays('weekdays');
		$this->assertEquals($expected, $actual);
		
		// Weekends
		$expected = [0,6];
		$actual = $date->parseDays('weekends');
		$this->assertEquals($expected, $actual);
		
		// Mon + Wed + Fri
		$expected = [1,3,5];
		$actual = $date->parseDays('mon,w,friday');
		$this->assertEquals($expected, $actual);
		
		// Mon + Fri (array)
		$expected = [1,5];
		$actual = $date->parseDays(['mon','friday']);
		$this->assertEquals($expected, $actual);
		
		// Mon + Tue + nonsense (array)
		$expected = [1,2];
		$actual = $date->parseDays(['m','z-day','tues']);
		$this->assertEquals($expected, $actual);
	}
	
	function testParseTimes() {
		$date = DevblocksPlatform::services()->date();
		
		$expected = ['08:00'];
		$actual = $date->parseTimes('08:00');
		$this->assertEquals($expected, $actual);
		
		$expected = ['20:00'];
		$actual = $date->parseTimes('8p');
		$this->assertEquals($expected, $actual);
		
		$expected = ['21:00'];
		$actual = $date->parseTimes('9pm');
		$this->assertEquals($expected, $actual);
		
		$expected = [['09:00','17:00']];
		$actual = $date->parseTimes('09:00-17:00');
		$this->assertEquals($expected, $actual);
		
		$expected = [['08:00','17:00']];
		$actual = $date->parseTimes('8a-5p');
		$this->assertEquals($expected, $actual);
		
		$expected = [['08:00','09:00']];
		$actual = $date->parseTimes('8-9a');
		$this->assertEquals($expected, $actual);
		
		$expected = [[46800, 61200]];
		$actual = $date->parseTimes('1-5p', true);
		$this->assertEquals($expected, $actual);
		
		$expected = [['09:00','17:00']];
		$actual = $date->parseTimes('9-5p');
		$this->assertEquals($expected, $actual);
		
		$expected = [['12:00','13:00']];
		$actual = $date->parseTimes('12-1p');
		$this->assertEquals($expected, $actual);
		
		$expected = [['08:00','09:00'],['14:00','15:30']];
		$actual = $date->parseTimes('8-9a,2-3:30p');
		$this->assertEquals($expected, $actual);
	}
}
