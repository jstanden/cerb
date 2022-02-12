<?php
class _DevblocksDateManager {
	private function __construct() {}
	
	/**
	 *
	 * @return _DevblocksDateManager
	 */
	static function getInstance() {
		static $instance = null;
		
		if(null == $instance) {
			$instance = new _DevblocksDateManager();
		}
		
		return $instance;
	}
	
	// [TODO] Unit test
	public function getIntervals($unit, $start_time, $end_time) {
		// [TODO] start/end time test
		
		$ticks = [];
		
		$from = strtotime(sprintf("-1 %s", $unit), $start_time);
		$to = $end_time;
		
		switch($unit) {
			case 'year':
				//$date_group_mysql = '%Y';
				$date_group_php = '%Y';
				break;
				
			case 'month':
				//$date_group_mysql = '%Y-%m';
				$date_group_php = '%Y-%m';
				break;
				
			case 'week':
				$from = strtotime("Monday this week 00:00:00", $start_time);
				$to = strtotime("Sunday this week 23:59:59", $end_time);
				//$date_group_mysql = '%x-%v';
				$date_group_php = '%Y-%W';
				break;
				
			case 'day':
				//$date_group_mysql = '%Y-%m-%d';
				$date_group_php = '%Y-%m-%d';
				break;
				
			case 'hour':
				//$date_group_mysql = '%Y-%m-%d %H';
				$date_group_php = '%Y-%m-%d %H';
				break;
				
			default:
				return false;
		}
		
		$time = $from;
		
		while($time < $to) {
			$time = strtotime(sprintf("+1 %s", $unit), $time);
			//if($time <= $to)
			$ticks[strftime($date_group_php, $time)] = 0;
		}
		
		return $ticks;
	}
	
	public function formatTime($format, $timestamp, $gmt=false) {
		try {
			$datetime = new DateTime();
			$datetime->setTimezone(new DateTimeZone('GMT'));
			$date = explode(' ',gmdate("Y m d", $timestamp));
			$time = explode(':',gmdate("H:i:s", $timestamp));
			$datetime->setDate($date[0],$date[1],$date[2]);
			$datetime->setTime($time[0],$time[1],$time[2]);
			
		} catch (Exception $e) {
			$datetime = new DateTime();
		}
		
		if(empty($format))
			$format = DevblocksPlatform::getDateTimeFormat();
		
		if(!$gmt)
			$datetime->setTimezone(new DateTimeZone(DevblocksPlatform::getTimezone()));
			
		return $datetime->format($format);
	}
	
	public function getTimezones() {
		return timezone_identifiers_list();
	}
	
	// Handle strtotime idiosyncrasies
	public function parseDateString($date_string, $now=null) {
		$matches = [];
		
		if(is_null($now))
			$now = time();
		
		$units_map = [
			'd' => 'day',
			'h' => 'hour',
			'hr' => 'hour',
			'hrs' => 'hour',
			'm' => 'minute',
			'mo' => 'month',
		];
		
		// +1hr -> +1 hr
		if(preg_match('#^([+-]?\d*)(\S*)$#', $date_string, $matches)) {
			$step = $matches[1]; 
			$unit = $matches[2];
			
			if(array_key_exists($unit, $units_map))
				$unit = $units_map[$unit];
			
			$date_string = $step . ' ' . $unit;
		}
		
		return strtotime($date_string, $now);
	}
	
	/**
	 * @param array $values
	 * @return array|false
	 */
	private function _parseDateRangeArray(array $values) {
		if(2 != count($values))
			return false;
		
		$from_date = $values[0];
		$to_date = $values[1];
		
		if(!is_numeric($from_date)) {
			// Translate periods into dashes on string dates
			if(false !== strpos($from_date,'.'))
				$from_date = str_replace(".", "-", $from_date);
			
			// If we weren't given a time, assume it's 00:00:00
			
			if(false == ($from_date_parts = date_parse($from_date))) {
				$from_date = 0;
				
			} else {
				// If we weren't given a time, default to the last second of the day
				if(
					$from_date != 'now'
					&& false == $from_date_parts['hour']
					&& false == $from_date_parts['minute']
					&& false == $from_date_parts['second']
					&& (!array_key_exists('relative', $from_date_parts)
						|| '0:0:0' == implode(':', [$from_date_parts['relative']['hour'], $from_date_parts['relative']['minute'], $from_date_parts['relative']['second']]))
					&& '0:0:0' != implode(':', [$from_date_parts['hour'], $from_date_parts['minute'], $from_date_parts['second']])
				) {
					$from_date .= ' 00:00:00';
				}
				
				if(false === ($from_date = strtotime($from_date)))
					$from_date = 0;
			}
		}
		
		if(!is_numeric($to_date)) {
			// Translate periods into dashes on string dates
			if(false !== strpos($to_date,'.'))
				$to_date = str_replace(".", "-", $to_date);
			
			// If we weren't given a time, assume it's 23:59:59
			
			if(false == ($to_date_parts = date_parse($to_date))) {
				$to_date = strtotime("now");
				
			} else {
				// If we weren't given a time, default to the last second of the day
				if(
					$to_date != 'now'
					&& false == $to_date_parts['hour']
					&& false == $to_date_parts['minute']
					&& false == $to_date_parts['second']
					&& (!array_key_exists('relative', $to_date_parts)
						|| '0:0:0' == implode(':', [$to_date_parts['relative']['hour'], $to_date_parts['relative']['minute'], $to_date_parts['relative']['second']]))
					&& '23:59:59' != implode(':', [$to_date_parts['hour'], $to_date_parts['minute'], $to_date_parts['second']])
				) {
					$to_date .= ' 23:59:59';
				}
				
				if(false === ($to_date = strtotime($to_date)))
					$to_date = strtotime("now");
			}
		}
		
		return [
			'from_ts' => $from_date,
			'from_string' => date('r', $from_date),
			'to_ts' => $to_date,
			'to_string' => date('r', $to_date),
		];
	}
	
	public function parseDateRangeShortcut($shortcut_key) {
		$shortcuts = [
			'this year' => 'Jan 1 to Dec 31',
			'next year' => 'Jan 1 +1 year to Dec 31 +1 year',
			'last year' => 'Jan 1 -1 year to Dec 31 -1 year',
			'this month' => 'first day of this month to last day of this month',
			'next month' => 'first day of next month to last day of next month',
			'last month' => 'first day of last month to last day of last month',
			'this week' => 'Monday this week to Monday this week +6 days',
			'next week' => 'Monday next week to Monday next week +6 days',
			'last week' => 'Monday last week to Monday last week +6 days',
			'today' => 'today 00:00:00 to today 23:59:59',
			'yesterday' => 'yesterday 00:00:00 to yesterday 23:59:59',
			'tomorrow' => 'tomorrow 00:00:00 to tomorrow 23:59:59',
		];
		
		$shortcut_key = DevblocksPlatform::strLower($shortcut_key);
		
		if(array_key_exists($shortcut_key, $shortcuts))
			return $shortcuts[$shortcut_key];
		
		return false;
	}
	
	public function isValidTimezoneLocation($timezone) {
		$timezones = array_fill_keys(
			array_map(fn($tz) => DevblocksPlatform::strLower($tz), $this->getTimezones()),
			true,
		);
		
		if(array_key_exists(DevblocksPlatform::strLower($timezone), $timezones))
			return true;
		
		return false;
	}
	
	// [TODO] Optional timezone override
	public function parseDateRange($value) {
		if(is_array($value)) {
			return $this->_parseDateRangeArray($value);
			
		} else if(is_string($value)) {
			if(false != ($shortcut = $this->parseDateRangeShortcut($value))) {
				$value = $shortcut;
			}
			
			if(false === strpos($value, ' to '))
				$value .= ' to now';
			
			if(false == ($value = explode(' to ', DevblocksPlatform::strLower($value), 2)))
				return false;
			
			if(2 != count($value))
				return false;
			
			return $this->_parseDateRangeArray($value);
		}
		
		return false;
	}
	
	public function parseDays($days) {
		$results = [];
		
		if(is_string($days))
			$days = DevblocksPlatform::parseCsvString($days);
		
		if(!is_array($days))
			return [];
		
		foreach($days as $day) {
			$day = DevblocksPlatform::strLower($day);
			
			if (DevblocksPlatform::strStartsWith($day, ['weekday'])) {
				$results[1] = true;
				$results[2] = true;
				$results[3] = true;
				$results[4] = true;
				$results[5] = true;
			} elseif (DevblocksPlatform::strStartsWith($day, ['weekend'])) {
				$results[0] = true;
				$results[6] = true;
			} elseif (DevblocksPlatform::strStartsWith($day, ['su'])) {
				$results[0] = true;
			} elseif (DevblocksPlatform::strStartsWith($day, ['m'])) {
				$results[1] = true;
			} elseif (DevblocksPlatform::strStartsWith($day, ['tu'])) {
				$results[2] = true;
			} elseif (DevblocksPlatform::strStartsWith($day, ['w'])) {
				$results[3] = true;
			} elseif (DevblocksPlatform::strStartsWith($day, ['th'])) {
				$results[4] = true;
			} elseif (DevblocksPlatform::strStartsWith($day, ['f'])) {
				$results[5] = true;
			} elseif (DevblocksPlatform::strStartsWith($day, ['sa'])) {
				$results[6] = true;
			}
		}
		
		return array_keys($results);
	}
	
	public function parseDayOfMonth($doms) {
		if (is_string($doms))
			$doms = DevblocksPlatform::parseCsvString($doms);
		
		if (!is_array($doms))
			return [];
		
		return array_map(function($w) {
			return DevblocksPlatform::intClamp($w, 1, 31);
		}, $doms);
	}
	
	public function parseWeeks($weeks) {
		if (is_string($weeks))
			$weeks = DevblocksPlatform::parseCsvString($weeks);
		
		if (!is_array($weeks))
			return [];
		
		return array_map(function($w) {
			return DevblocksPlatform::intClamp($w, 0, 53);
		}, $weeks);
	}
	
	public function parseMonths($months) {
		$results = [];
		
		if(is_string($months))
			$months = DevblocksPlatform::parseCsvString($months);
		
		if(!is_array($months))
			return [];
		
		foreach($months as $month) {
			$month = DevblocksPlatform::strLower($month);
			
			if (DevblocksPlatform::strStartsWith($month, ['ja'])) {
				$results[1] = true;
			} elseif (DevblocksPlatform::strStartsWith($month, ['f'])) {
				$results[2] = true;
			} elseif (DevblocksPlatform::strStartsWith($month, ['mar'])) {
				$results[3] = true;
			} elseif (DevblocksPlatform::strStartsWith($month, ['ap'])) {
				$results[4] = true;
			} elseif (DevblocksPlatform::strStartsWith($month, ['may'])) {
				$results[5] = true;
			} elseif (DevblocksPlatform::strStartsWith($month, ['jun'])) {
				$results[6] = true;
			} elseif (DevblocksPlatform::strStartsWith($month, ['jul'])) {
				$results[7] = true;
			} elseif (DevblocksPlatform::strStartsWith($month, ['au'])) {
				$results[8] = true;
			} elseif (DevblocksPlatform::strStartsWith($month, ['se'])) {
				$results[9] = true;
			} elseif (DevblocksPlatform::strStartsWith($month, ['o'])) {
				$results[10] = true;
			} elseif (DevblocksPlatform::strStartsWith($month, ['n'])) {
				$results[11] = true;
			} elseif (DevblocksPlatform::strStartsWith($month, ['d'])) {
				$results[12] = true;
			}
		}
		
		return array_keys($results);
	}
	
	public function parseTimes($times, $as_secs=false) {
		$results = [];
		
		if(is_string($times))
			$times = DevblocksPlatform::parseCsvString($times);
		
		if(!is_array($times))
			return [];
		
		foreach($times as $time) {
			if(false !== strpos($time, '-'))  {
				list($from,$to) = explode('-', $time);
				$to = $this->_parseTime($to, $as_secs);
				$from = $this->_parseTime($from, $as_secs, $to);
				$results[] = [$from,$to];
				
			} else {
				$results[] = $this->_parseTime($time, $as_secs);
			}
		}
		
		return $results;
	}
	
	private function _parseTime($string, $as_secs=false, $rel_to=null) {
		$string = trim($string);
		
		if (is_numeric($string) && $string >= 0 && $string <= 12) {
			if ($rel_to) {
				if($as_secs) {
					$is_pm = $rel_to >= 43200;
					$this_hour = ($string<12 ? $string+12 : $string);
					
					if($is_pm && $this_hour*3600 > $rel_to)
						$is_pm = false;
					
				} else {
					$parts = date_parse($rel_to);
					$is_pm = $parts['hour'] >= 12;
					$this_hour = ($string<12 ? $string+12 : $string);
					
					if($is_pm && $this_hour > $parts['hour'])
						$is_pm = false;
				}
				
				if($is_pm) {
					$string .= 'pm';
				} else {
					$string .= 'am';
				}
				
			} elseif ($string < 12) {
				$string .= 'am';
			}
			
		} else {
			$ends_with = strtolower(substr($string, -1));
			
			if (in_array($ends_with, ['a', 'p'])) {
				$string .= 'm';
			}
		}
		
		$parts = date_parse($string);
		
		if($as_secs) {
			return $parts['hour']*3600 + $parts['minute']*60;
		} else {
			return sprintf("%02d:%02d", $parts['hour'], $parts['minute']);
		}
	}
	
	public function getTimezoneOffsetFromLocation($timezone) {
		$timezones = array_fill_keys(
			array_map(fn($tz) => DevblocksPlatform::strLower($tz), self::getTimezones()),
			true
		);
		
		if(!array_key_exists(DevblocksPlatform::strLower($timezone), $timezones))
			return false;
		
		try {
			if(false == ($tz = new DateTimeZone($timezone)))
				return false;
			
			if(false == ($now = new DateTime('now', $tz)))
				return false;
			
			$offset = $tz->getOffset($now);
			
			return ($offset < 0 ? '-' : '+') . gmdate('H:i', abs($offset));
			
		} catch (Exception $e) {
			return false;
		}
	}
	
	public function parseTimezoneOffset(string $timezone, ?string &$error=null) {
		if(!is_string($timezone)) {
			$error = 'timezone must be a string';
			return false;
		}
		
		if(!strstr($timezone,':')) {
			if(false == ($offset = self::getTimezoneOffsetFromLocation($timezone))) {
				$error = sprintf("Timezone `%s` is unknown.", $timezone);
				return false;
			}
			
			$timezone = $offset;
		}
		
		$matches = [];
		$is_neg = DevblocksPlatform::strStartsWith($timezone, '-');
		$timezone = ltrim($timezone, '+-');
		
		if(false == (preg_match('#^(\d{1,2}):(\d{1,2})$#', $timezone, $matches))) {
			$error = "timezone must be specified like `-07:00`";
			return false;
		}
		
		$hour = DevblocksPlatform::intClamp($matches[1], 0, 12);
		$min = DevblocksPlatform::intClamp($matches[2], 0, 59);
		
		if($hour != $matches[1]) {
			$error = "timezone hour must be between -12 and +12";
			return false;
		}
		
		if($min != $matches[2]) {
			$error = "timezone minute must be between 0 and 59";
			return false;
		}
		
		return sprintf("%s%s:%s",
			$is_neg ? '-' : '+',
			str_pad($hour,2,'0', STR_PAD_LEFT),
			str_pad($min,2,'0', STR_PAD_LEFT)
		);
	}
	
	public function getNextOccurrence(array $patterns, $timezone=null, $from='now') {
		if(empty($timezone))
			$timezone = DevblocksPlatform::getTimezone();
		
		$earliest = null;
		$now = null;
		
		try {
			$now = new DateTime($from, new DateTimeZone($timezone));
		} catch(Exception $e) {
			return false;
		}
		
		foreach($patterns as $pattern) {
			// Skip commented lines
			if(empty($pattern) || DevblocksPlatform::strStartsWith($pattern, '#'))
				continue;
			
			$cron = Cron\CronExpression::factory($pattern);
			$next = $cron->getNextRunDate($now);
			
			$next_ts = $next->getTimestamp();
			
			if(!$earliest || $next_ts < $earliest)
				$earliest = $next_ts;
		}
		
		return $earliest;
	}
	
	public function getEasterDayByYear($year) : ?string {
		if(extension_loaded('calendar') && function_exists('easter_date')) {
			try {
				$base = new DateTime($year . '-03-21');
				$days = easter_days($year);
				$date = $base->add(new DateInterval('P' . $days . 'D'));
				return $date->format('F j');
				
			} catch(Exception $e) {
				DevblocksPlatform::logError($e->getMessage());
			}
		}
		
		// If `ext/calendar` isn't enabled, use a precomputed table
		$easter_years = [
			'2012' => 'April 8',
			'2013' => 'March 31',
			'2014' => 'April 20',
			'2015' => 'April 5',
			'2016' => 'March 27',
			'2017' => 'April 16',
			'2018' => 'April 1',
			'2019' => 'April 21',
			'2020' => 'April 12',
			'2021' => 'April 4',
			'2022' => 'April 17',
			'2023' => 'April 9',
			'2024' => 'March 31',
			'2025' => 'April 20',
			'2026' => 'April 5',
			'2027' => 'March 28',
			'2028' => 'April 16',
			'2029' => 'April 1',
			'2030' => 'April 21',
			'2031' => 'April 13',
			'2032' => 'March 28',
			'2033' => 'April 17',
			'2034' => 'April 9',
			'2035' => 'March 25',
			'2036' => 'April 13',
			'2037' => 'April 5',
			'2038' => 'April 25',
			'2039' => 'April 10',
			'2040' => 'April 1',			
		];
		
		if(!array_key_exists($year, $easter_years))
			return null;
		
		return $easter_years[$year];
	}
};

class DevblocksCalendarHelper {
	static function getCalendar($month=null, $year=null, $start_on_mon=false) {
		if(empty($month) || empty($year)) {
			$month = date('m');
			$year = date('Y');
		}
		
		$calendar_date = mktime(0,0,0,$month,1,$year);
		
		$num_days = date('t', $calendar_date);
		
		$first_dow = $start_on_mon ? (date('N', $calendar_date)-1) : date('w', $calendar_date);
		
		$prev_month_date = mktime(0,0,0,$month,0,$year);
		$prev_month = date('m', $prev_month_date);
		$prev_year = date('Y', $prev_month_date);

		$next_month_date = mktime(0,0,0,$month+1,1,$year);
		$next_month = date('m', $next_month_date);
		$next_year = date('Y', $next_month_date);
		
		$days = array();

		for($day = 1; $day <= $num_days; $day++) {
			$timestamp = mktime(0,0,0,$month,$day,$year);
			
			$days[$timestamp] = array(
				'dom' => $day,
				'dow' => (($first_dow+$day-1) % 7),
				'is_padding' => false,
				'timestamp' => $timestamp,
			);
		}
		
		// How many cells do we need to pad the first and last weeks?
		$first_day = reset($days);
		$left_pad = $first_day['dow'];
		$last_day = end($days);
		$right_pad = 6-$last_day['dow'];

		$calendar_cells = $days;
		
		if($left_pad > 0) {
			$prev_month_days = date('t', $prev_month_date);
			
			for($i=1;$i<=$left_pad;$i++) {
				$dom = $prev_month_days - ($i-1);
				$timestamp = mktime(0,0,0,$prev_month,$dom,$prev_year);
				$day = array(
					'dom' => $dom,
					'dow' => $first_dow - $i,
					'is_padding' => true,
					'timestamp' => $timestamp,
				);
				$calendar_cells[$timestamp] = $day;
			}
		}
		
		if($right_pad > 0) {
			for($i=1;$i<=$right_pad;$i++) {
				$timestamp = mktime(0,0,0,$next_month,$i,$next_year);
				
				$day = array(
					'dom' => $i,
					'dow' => (($first_dow + $num_days + $i - 1) % 7),
					'is_padding' => true,
					'timestamp' => $timestamp,
				);
				$calendar_cells[$timestamp] = $day;
			}
		}
		
		// Sort calendar
		ksort($calendar_cells);
		
		// Break into weeks
		$calendar_weeks = array_chunk($calendar_cells, 7, true);

		// Events
		$first_cell = array_slice($calendar_cells, 0, 1, false);
		$last_cell = array_slice($calendar_cells, -1, 1, false);
		$range_from = array_shift($first_cell);
		$range_to = array_shift($last_cell);
		
		unset($days);
		unset($calendar_cells);
		
		$date_range_from = strtotime('00:00', $range_from['timestamp']);
		$date_range_to = strtotime('23:59', $range_to['timestamp']);
		
		return [
			'today' => strtotime('today'),
			'month' => $month,
			'prev_month' => $prev_month,
			'next_month' => $next_month,
			'year' => $year,
			'prev_year' => $prev_year,
			'next_year' => $next_year,
			'date_range_from' => $date_range_from,
			'date_range_to' => $date_range_to,
			'start_on_mon' => $start_on_mon,
			'calendar_date' => $calendar_date,
			'calendar_weeks' => $calendar_weeks,
		];
	}
	
	static function getDailyDates($start, $every_n=1, $until=null, $max_iter=null) {
		$dates = array();
		$counter = 0;
		$every_n = max(intval($every_n), 1);

		$date = $start;
		$last_date = $date;
		
		$done = false;
		$num_loops = 0;
		
		while(!$done && ++$num_loops < 1096) { // Runaway protection capped at 3 years worth of days
			if($date >= $start)
				$dates[] = $date;
			
			$date = strtotime(sprintf("+%d days", $every_n), $date);
			
			// Stop if we have no end in sight
			if(!$done && empty($max_iter) && empty($until))
				$done = true;
			// Stop if we hit the 32-bit Unix armageddon
			if(!$done && $last_date >= $date)
				$done = true;
			// Stop if we have a result cap and we passed it
			if(!$done && !empty($max_iter) && count($dates) >= $max_iter)
				$done = true;
			// Stop if we have an end date and we passed it
			if(!$done && !empty($until) && $date > $until)
				$done = true;
			
			$last_date = $date;
		}

		return $dates;
	}
	
	// Sun = 0
	static function getWeeklyDates($start, array $weekdays, $until=null, $max_iter=null) {
		$dates = array();
		
		// Change the start of week from Sun to Mon
		// [TODO] This should be handled better globally
		foreach($weekdays as $idx => $day) {
			if(0 == $day) {
				$weekdays[$idx] = 6;
			} else {
				$weekdays[$idx]--;
			}
		}
		
		sort($weekdays);
		
		// If we're asked to make things starting at a date beyond the end date, stop.
		if(!is_null($until) && $start > $until)
			return $dates;

		$name_weekdays = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
		$num_weekdays = count($weekdays);

		$cur_dow = (integer) date('N', $start) - 1;
		
		$counter = null;

		if(false !== ($pos = array_search($cur_dow, $weekdays))) {
			$counter = $pos;
			$cur_dow = $weekdays[$pos];
			$date = strtotime('today', $start);
			
		} else {
			$counter = 0;
			$cur_dow = reset($weekdays);
			$date = strtotime(sprintf("%s this week", $name_weekdays[$cur_dow]), $start);
		}

		$last_date = $date;
		
		$done = false;
		$num_loops = 0;
		
		while(!$done && ++$num_loops < 1096) { // Runaway protection capped at 3 years worth of days
			if($date >= $start)
				$dates[] = $date;
			
			$cur_dow = $weekdays[$counter % $num_weekdays];
			$next_dow = $weekdays[++$counter % $num_weekdays];

			$increment_str = sprintf("%s %s",
				$name_weekdays[$next_dow],
				($next_dow == $cur_dow) ? '+1 week' : ''
			);
			
			$date = strtotime(
				$increment_str,
				$date
			);
			
			// Stop if we have no end in sight
			if(!$done && empty($max_iter) && empty($until))
				$done = true;
			// Stop if we hit the 32-bit Unix armageddon
			if(!$done && $last_date >= $date)
				$done = true;
			// Stop if we have a result cap and we passed it
			if(!$done && !empty($max_iter) && count($dates) >= $max_iter)
				$done = true;
			// Stop if we have an end date and we passed it
			if(!$done && !empty($until) && $date > $until)
				$done = true;
			
			$last_date = $date;
		}

		return $dates;
	}
	
	static function getMonthlyDates($start, array $days, $until=null, $max_iter=null) {
		$dates = array();
		
		// If we're asked to make things starting at a date beyond the end date, stop.
		if(!is_null($until) && $start > $until)
			return $dates;
		
		$num_days = count($days);

		$cur_dy = (integer) date('d', $start);
		$cur_mo = (integer) date('m', $start);
		$cur_yr = (integer) date('Y', $start);
		
		$counter = null;

		if(false !== ($pos = array_search($cur_dy, $days))) {
			$counter = $pos;
			
		} else {
			$counter = 0;
			$cur_dy = reset($days);
		}
		
		$date = strtotime(sprintf("%d-%d-%d", $cur_yr, $cur_mo, $cur_dy));
		$last_date = $date;
		
		$done = false;
		$num_loops = 0;
		
		while(!$done && ++$num_loops < 250) {
			if($date >= $start)
				$dates[] = $date;
			
			do {
				$cur_dy = (integer) date('d', $date);
				$cur_mo = (integer) date('m', $date);
				$cur_yr = (integer) date('Y', $date);
				
				$next_dy = $days[++$counter % $num_days];
				$next_mo = $cur_mo + ($next_dy <= $cur_dy ? 1 : 0);
				$next_yr = $cur_yr;
				
				if($next_mo > 12) {
					$next_mo = 1;
					$next_yr++;
				}
				
				$date = strtotime(sprintf("%d-%d-%d", $next_yr, $next_mo, $next_dy));
				
			} while(self::_getDaysInMonth($next_mo, $next_yr) < $next_dy);
			
			// Stop if we have no end in sight
			if(!$done && empty($max_iter) && empty($until))
				$done = true;
			// Stop if we hit the 32-bit Unix armageddon
			if(!$done && $last_date >= $date)
				$done = true;
			// Stop if we have a result cap and we passed it
			if(!$done && !empty($max_iter) && count($dates) >= $max_iter)
				$done = true;
			// Stop if we have an end date and we passed it
			if(!$done && !empty($until) && $date > $until)
				$done = true;
			
			$last_date = $date;
		}

		return $dates;
	}
	
	static function getYearlyDates($start, array $months, $until=null, $max_iter=null) {
		$dates = array();
		
		// If we're asked to make things starting at a date beyond the end date, stop.
		if(!is_null($until) && $start > $until)
			return $dates;
		
		$num_months = count($months);

		$cur_dy = (integer) date('d', $start);
		$cur_mo = (integer) date('m', $start);
		$cur_yr = (integer) date('Y', $start);
		
		$counter = null;

		if(false !== ($pos = array_search($cur_mo, $months))) {
			$counter = $pos;
			
		} else {
			$counter = 0;
			$cur_mo = reset($months);
		}
		
		$date = strtotime(sprintf("%d-%d-%d", $cur_yr, $cur_mo, $cur_dy));
		$last_date = $date;
		
		$done = false;
		$num_loops = 0;
		
		while(!$done && ++$num_loops < 250) {
			if($date >= $start)
				$dates[] = $date;
			
			do {
				$cur_mo = (integer) date('m', $date);
				$cur_yr = (integer) date('Y', $date);
				
				$next_mo = $months[++$counter % $num_months];
				$next_yr = $cur_yr + ($next_mo <= $cur_mo ? 1 : 0);
				
				$date = strtotime(sprintf("%d-%d-%d", $next_yr, $next_mo, $cur_dy));
				
			} while(self::_getDaysInMonth($next_mo, $next_yr) < $cur_dy);
			
			// Stop if we have no end in sight
			if(!$done && empty($max_iter) && empty($until))
				$done = true;
			// Stop if we hit the 32-bit Unix armageddon
			if(!$done && $last_date >= $date)
				$done = true;
			// Stop if we have a result cap and we passed it
			if(!$done && !empty($max_iter) && count($dates) >= $max_iter)
				$done = true;
			// Stop if we have an end date and we passed it
			if(!$done && !empty($until) && $date > $until)
				$done = true;
			
			$last_date = $date;
		}

		return $dates;
	}
	
	// Days in month helper
	private static function _getDaysInMonth($month, $year) {
		$days_check = mktime(
			0,
			0,
			0,
			$month,
			1,
			$year
		);
			
		return (integer) date('t', $days_check);
	}
};