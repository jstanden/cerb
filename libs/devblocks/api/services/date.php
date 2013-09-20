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
			$datetime->setTimezone(new DateTimeZone(date_default_timezone_get()));
			
		return $datetime->format($format);
	}
	
	public function getTimezones() {
		return timezone_identifiers_list();
	}
};

class DevblocksCalendarHelper {
	static function getCalendar($month=null, $year=null) {
		if(empty($month) || empty($year)) {
			$month = date('m');
			$year = date('Y');
		}
		
		$calendar_date = mktime(0,0,0,$month,1,$year);
		
		$num_days = date('t', $calendar_date);
		$first_dow = date('w', $calendar_date);
		
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
		
		$calendar_properties = array(
			'today' => strtotime('today'),
			'month' => $month,
			'prev_month' => $prev_month,
			'next_month' => $next_month,
			'year' => $year,
			'prev_year' => $prev_year,
			'next_year' => $next_year,
			'date_range_from' => $date_range_from,
			'date_range_to' => $date_range_to,
			'calendar_date' => $calendar_date,
			'calendar_weeks' => $calendar_weeks,
		);
		
		return $calendar_properties;
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