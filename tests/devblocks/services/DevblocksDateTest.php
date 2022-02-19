<?php
use PHPUnit\Framework\TestCase;

class DevblocksDateTest extends TestCase {
	final function __construct($name = null, array $data = array(), $dataName = '') {
		parent::__construct($name, $data, $dataName);
	}
	
	function testParseDateString() {
		$date = DevblocksPlatform::services()->date();
		
		$now = time();
		
		// Blank dates
		$actual = $date->parseDateString('', $now);
		$expected = '';
		$this->assertEquals($expected, $actual);
		
		// Absolute YYYY-MM-DD
		$actual = $date->parseDateString('2025-12-31', $now);
		$expected = strtotime('2025-12-31', $now);
		$this->assertEquals($expected, $actual);
		
		// Absolute YYYY-MM-DD HH:ii
		$actual = $date->parseDateString('2025-12-31 23:59', $now);
		$expected = strtotime('2025-12-31 23:59:00', $now);
		$this->assertEquals($expected, $actual);
		
		// Absolute US MM/DD
		$actual = $date->parseDateString('12/31', $now);
		$expected = strtotime('December 31', $now);
		$this->assertEquals($expected, $actual);
		
		// +5mins
		$actual = $date->parseDateString('+5mins', $now);
		$expected = strtotime('5 mins', $now);
		$this->assertEquals($expected, $actual);
		
		// +5mins (CASE)
		$actual = $date->parseDateString('+5M', $now);
		$expected = strtotime('5 mins', $now);
		$this->assertEquals($expected, $actual);
		
		// +1hr
		$actual = $date->parseDateString('+1hr', $now);
		$expected = strtotime('+1 hour', $now);
		$this->assertEquals($expected, $actual);
		
		// 2h
		$actual = $date->parseDateString('2h', $now);
		$expected = strtotime('+2 hours', $now);
		$this->assertEquals($expected, $actual);
		
		// -5d
		$actual = $date->parseDateString('-5d', $now);
		$expected = strtotime('-5 days', $now);
		$this->assertEquals($expected, $actual);
		
		// +3mo
		$actual = $date->parseDateString('+3mo', $now);
		$expected = strtotime('+3 months', $now);
		$this->assertEquals($expected, $actual);
		
		/*
		// [TODO] Tues, Weds, Thur, Thurs
		$actual = $date->parseDateString('Tues', $now);
		$expected = strtotime('Tuesday', $now);
		$this->assertEquals($expected, $actual);
		*/
	}
	
	function testParseDateRange() {
		$date = DevblocksPlatform::services()->date();
		
		// Dashes with US format and zero padding
		
		$expected = [
			strtotime('2021-09-01 00:00:00'),
			strtotime('2021-09-15 23:59:59')
		];
		
		$results = $date->parseDateRange('09/01/2021 to 09/15/2021');
		
		$this->assertTrue(is_array($results));
		
		$actual = [
			$results['from_ts'],
			$results['to_ts'],
		];
		
		$this->assertEquals($expected, $actual);
		
		// Dashes with US format and no zero padding
		
		$expected = [
			strtotime('2021-09-01 00:00:00'),
			strtotime('2021-09-15 23:59:59')
		];
		
		$results = $date->parseDateRange('9/1/2021 to 9/15/2021');
		
		$this->assertTrue(is_array($results));
		
		$actual = [
			$results['from_ts'],
			$results['to_ts'],
		];
		
		$this->assertEquals($expected, $actual);
		
		// Dashes with EU (dash) format and zero padding
		
		$expected = [
			strtotime('2021-09-01 00:00:00'),
			strtotime('2021-09-15 23:59:59')
		];
		
		$results = $date->parseDateRange('01-09-2021 to 15-09-2021');
		
		$this->assertTrue(is_array($results));
		
		$actual = [
			$results['from_ts'],
			$results['to_ts'],
		];
		
		$this->assertEquals($expected, $actual);
		
		// Dashes with EU (dot) format and zero padding
		
		$expected = [
			strtotime('2021-09-01 00:00:00'),
			strtotime('2021-09-15 23:59:59')
		];
		
		$results = $date->parseDateRange('01.09.2021 to 15.09.2021');
		
		$this->assertTrue(is_array($results));
		
		$actual = [
			$results['from_ts'],
			$results['to_ts'],
		];
		
		$this->assertEquals($expected, $actual);
		
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
		
		// Shortcuts (this month)
		
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
		
		// Shortcuts (last week)
		
		$expected = [
			strtotime('Monday last week'),
			strtotime('Monday last week +6 days 23:59:59')
		];
		
		$results = $date->parseDateRange('last week');
		
		$this->assertTrue(is_array($results));
		
		$actual = [
			$results['from_ts'],
			$results['to_ts'],
		];
		
		$this->assertEquals($expected, $actual);
		
		// Shortcuts (last month)
		
		$expected = [
			strtotime('first day of last month 00:00:00'),
			strtotime('last day of last month 23:59:59')
		];
		
		$results = $date->parseDateRange('last month');
		
		$this->assertTrue(is_array($results));
		
		$actual = [
			$results['from_ts'],
			$results['to_ts'],
		];
		
		$this->assertEquals($expected, $actual);
		
		// Shortcuts (last year)
		
		$expected = [
			strtotime('Jan 1 -1 year 00:00:00'),
			strtotime('Dec 31 -1 year 23:59:59')
		];
		
		$results = $date->parseDateRange('last year');
		
		$this->assertTrue(is_array($results));
		
		$actual = [
			$results['from_ts'],
			$results['to_ts'],
		];
		
		$this->assertEquals($expected, $actual);
		
		// One operand (implied "to now")
		
		$expected = [
			strtotime('first day of this month 00:00:00'),
			time()
		];
		
		$results = $date->parseDateRange('first day of this month');
		
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
	
	function testParseMonths() {
		$date = DevblocksPlatform::services()->date();
		
		// Northern summer months
		$expected = [6,7,8,9];
		$actual = $date->parseMonths('Jun,July,Aug,Sept');
		$this->assertEquals($expected, $actual);
		
		// Some invalid months
		$expected = [1,2,3];
		$actual = $date->parseMonths('Jan,February,  Zonkuary,Cerbeth, March');
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
	
	/**
	 * @throws Exception
	 */
	function testParseTimezoneOffset() {
		$date = DevblocksPlatform::services()->date();
		
		$expected = '-07:00';
		$actual = $date->parseTimezoneOffset('-07:00');
		$this->assertEquals($expected, $actual);
		
		$expected = '+01:30';
		$actual = $date->parseTimezoneOffset('1:30');
		$this->assertEquals($expected, $actual);
		
		$actual = $date->parseTimezoneOffset('12');
		$this->assertFalse($actual);
		
		$actual = $date->parseTimezoneOffset('a non time string');
		$this->assertFalse($actual);
		
		$actual = $date->parseTimezoneOffset('abc:123');
		$this->assertFalse($actual);
		
		// Conversion of timezone locations to offsets
		
		$ts = new DateTime('now', new DateTimeZone('America/Los_Angeles'));
		$expected = $ts->format('P');
		$actual = $date->parseTimezoneOffset('America/Los_Angeles');
		$this->assertEquals($expected, $actual);
		
		$ts = new DateTime('now', new DateTimeZone('America/Toronto'));
		$expected = $ts->format('P');
		$actual = $date->parseTimezoneOffset('America/Toronto');
		$this->assertEquals($expected, $actual);
	}
	
	function testGetNextOccurrence() {
		$date = DevblocksPlatform::services()->date();
		
		// Every 5 mins
		$patterns = [
			'*/5 * * * *',
		];
		$expected = gmmktime(0,5,0,1,1,2021);
		$actual = $date->getNextOccurrence($patterns, 'GMT', 'Jan 1 2021 00:01:23');
		$this->assertEquals($expected, $actual);
		
		// Last day of every month
		$patterns = [
			'0 0 L * *',
		];
		$expected = gmmktime(0,0,0,1,31,2021);
		$actual = $date->getNextOccurrence($patterns, 'GMT', 'Jan 1 2021 00:00:00');
		$this->assertEquals($expected, $actual);
		
		// Every Thursday at 5:30pm
		$patterns = [
			'30 17 * * 4',
		];
		$expected = gmmktime(17,30,0,1,7,2021);
		$actual = $date->getNextOccurrence($patterns, 'GMT', 'Jan 1 2021 00:00:00');
		$this->assertEquals($expected, $actual);
	}
	
	function testDateLerpArray() {
		// Days
		
		$expected = array_map(fn($d) => sprintf('2022-01-%02d', $d), range(1,31));
		
		$actual = DevblocksPlatform::dateLerpArray(['2022-01-01', '2022-01-31'], 'day', 1);
		$actual = DevblocksPlatform::services()->date()->formatTimestamps($actual, 'Y-m-d');
		$this->assertEquals($expected, $actual);
		
		// Months
		
		$expected = array_map(fn($d) => sprintf('2022-%02d', $d), range(6,12));
		
		$actual = DevblocksPlatform::dateLerpArray(['2022-06-01', '2022-12-31'], 'month', 1);
		$actual = DevblocksPlatform::services()->date()->formatTimestamps($actual, 'Y-m');
		$this->assertEquals($expected, $actual);
		
		// Months from a day not in all months
		
		$expected = array_map(fn($d) => sprintf('2022-%02d', $d), range(5,12));
		
		$actual = DevblocksPlatform::dateLerpArray(['2022-05-31', '2022-12-31'], 'month', 1);
		$actual = DevblocksPlatform::services()->date()->formatTimestamps($actual, 'Y-m');
		$this->assertEquals($expected, $actual);
		
		// Years
		
		$expected = range(2015,2022);
		
		$actual = DevblocksPlatform::dateLerpArray(['2019','2022','2015','2015','2016','2017','2020','2021'], 'year', 1);
		$actual = DevblocksPlatform::services()->date()->formatTimestamps($actual, 'Y');
		$this->assertEquals($expected, $actual);
		
		// Years with timezones
		
		$expected = range(2020,2022);
		
		DevblocksPlatform::setTimezone('America/Los_Angeles');
		
		$actual = DevblocksPlatform::dateLerpArray(['2020-01-01 00:00:00','2022-12-31 23:59:59'], 'year', 1);
		$actual = DevblocksPlatform::services()->date()->formatTimestamps($actual, 'Y');
		$this->assertEquals($expected, $actual);
		
		DevblocksPlatform::setTimezone('UTC');
		
		// Weeks (from Monday)
		
		$expected = array_map(
			function($wk) {
				return date('Y-m-d', strtotime(sprintf('Dec 28 2020 +%d week', $wk)));
			},
			range(0,52)
		);
		
		$actual = DevblocksPlatform::dateLerpArray(['2020-12-28 00:00:00','2021-12-31 23:59:59'], 'week', 1);
		$actual = DevblocksPlatform::services()->date()->formatTimestamps($actual, 'Y-m-d');
		$this->assertEquals($expected, $actual);
		
		// 5 min steps
		
		$expected = [
			'2021-01-15 10:00',
			'2021-01-15 10:05',
			'2021-01-15 10:10',
			'2021-01-15 10:15',
			'2021-01-15 10:20',
			'2021-01-15 10:25',
			'2021-01-15 10:30',
			'2021-01-15 10:35',
			'2021-01-15 10:40',
			'2021-01-15 10:45',
			'2021-01-15 10:50',
			'2021-01-15 10:55',
			'2021-01-15 11:00',
		];
		
		$actual = DevblocksPlatform::dateLerpArray(['2021-01-15 10:00:00', '2021-01-15 11:00:00'], 'minute', 5);
		$actual = DevblocksPlatform::services()->date()->formatTimestamps($actual, 'Y-m-d H:i');
		$this->assertEquals($expected, $actual);
	}
}
