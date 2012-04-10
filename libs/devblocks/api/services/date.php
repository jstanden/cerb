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
			$format = DateTime::RFC822; 
		
		if(!$gmt)
			$datetime->setTimezone(new DateTimeZone(date_default_timezone_get()));
			
		return $datetime->format($format);
	}
	
	public function getTimezones() {
		return array(
			'Africa/Abidjan',
			'Africa/Accra',
			'Africa/Addis_Ababa',
			'Africa/Algiers',
			'Africa/Asmera',
			'Africa/Bamako',
			'Africa/Bangui',
			'Africa/Banjul',
			'Africa/Bissau',
			'Africa/Blantyre',
			'Africa/Brazzaville',
			'Africa/Bujumbura',
			'Africa/Cairo',
			'Africa/Casablanca',
			'Africa/Ceuta',
			'Africa/Conakry',
			'Africa/Dakar',
			'Africa/Dar_es_Salaam',
			'Africa/Djibouti',
			'Africa/Douala',
			'Africa/El_Aaiun',
			'Africa/Freetown',
			'Africa/Gaborone',
			'Africa/Harare',
			'Africa/Johannesburg',
			'Africa/Kampala',
			'Africa/Khartoum',
			'Africa/Kigali',
			'Africa/Kinshasa',
			'Africa/Lagos',
			'Africa/Libreville',
			'Africa/Lome',
			'Africa/Luanda',
			'Africa/Lubumbashi',
			'Africa/Lusaka',
			'Africa/Malabo',
			'Africa/Maputo',
			'Africa/Maseru',
			'Africa/Mbabane',
			'Africa/Mogadishu',
			'Africa/Monrovia',
			'Africa/Nairobi',
			'Africa/Ndjamena',
			'Africa/Niamey',
			'Africa/Nouakchott',
			'Africa/Ouagadougou',
			'Africa/Porto-Novo',
			'Africa/Sao_Tome',
			'Africa/Timbuktu',
			'Africa/Tripoli',
			'Africa/Tunis',
			'Africa/Windhoek',
			'America/Adak',
			'America/Anchorage',
			'America/Anguilla',
			'America/Antigua',
			'America/Araguaina',
			'America/Aruba',
			'America/Asuncion',
			'America/Barbados',
			'America/Belem',
			'America/Belize',
			'America/Bogota',
			'America/Boise',
			'America/Buenos_Aires',
			'America/Cancun',
			'America/Caracas',
			'America/Catamarca',
			'America/Cayenne',
			'America/Cayman',
			'America/Chicago',
			'America/Chihuahua',
			'America/Cordoba',
			'America/Costa_Rica',
			'America/Cuiaba',
			'America/Curacao',
			'America/Dawson',
			'America/Dawson_Creek',
			'America/Denver',
			'America/Detroit',
			'America/Dominica',
			'America/Edmonton',
			'America/El_Salvador',
			'America/Ensenada',
			'America/Fortaleza',
			'America/Glace_Bay',
			'America/Godthab',
			'America/Goose_Bay',
			'America/Grand_Turk',
			'America/Grenada',
			'America/Guadeloupe',
			'America/Guatemala',
			'America/Guayaquil',
			'America/Guyana',
			'America/Halifax',
			'America/Havana',
			'America/Indiana/Knox',
			'America/Indiana/Marengo',
			'America/Indiana/Vevay',
			'America/Indianapolis',
			'America/Inuvik',
			'America/Iqaluit',
			'America/Jamaica',
			'America/Jujuy',
			'America/Juneau',
			'America/La_Paz',
			'America/Lima',
			'America/Los_Angeles',
			'America/Louisville',
			'America/Maceio',
			'America/Managua',
			'America/Manaus',
			'America/Martinique',
			'America/Mazatlan',
			'America/Mendoza',
			'America/Menominee',
			'America/Mexico_City',
			'America/Miquelon',
			'America/Montevideo',
			'America/Montreal',
			'America/Montserrat',
			'America/Nassau',
			'America/New_York',
			'America/Nipigon',
			'America/Nome',
			'America/Noronha',
			'America/Panama',
			'America/Pangnirtung',
			'America/Paramaribo',
			'America/Phoenix',
			'America/Port-au-Prince',
			'America/Port_of_Spain',
			'America/Porto_Acre',
			'America/Porto_Velho',
			'America/Puerto_Rico',
			'America/Rainy_River',
			'America/Rankin_Inlet',
			'America/Regina',
			'America/Rosario',
			'America/Santiago',
			'America/Santo_Domingo',
			'America/Sao_Paulo',
			'America/Scoresbysund',
			'America/Shiprock',
			'America/St_Johns',
			'America/St_Kitts',
			'America/St_Lucia',
			'America/St_Thomas',
			'America/St_Vincent',
			'America/Swift_Current',
			'America/Tegucigalpa',
			'America/Thule',
			'America/Thunder_Bay',
			'America/Tijuana',
			'America/Tortola',
			'America/Vancouver',
			'America/Whitehorse',
			'America/Winnipeg',
			'America/Yakutat',
			'America/Yellowknife',
			'Antarctica/Casey',
			'Antarctica/Davis',
			'Antarctica/DumontDUrville',
			'Antarctica/Mawson',
			'Antarctica/McMurdo',
			'Antarctica/Palmer',
			'Antarctica/South_Pole',
			'Arctic/Longyearbyen',
			'Asia/Aden',
			'Asia/Almaty',
			'Asia/Amman',
			'Asia/Anadyr',
			'Asia/Aqtau',
			'Asia/Aqtobe',
			'Asia/Ashkhabad',
			'Asia/Baghdad',
			'Asia/Bahrain',
			'Asia/Baku',
			'Asia/Bangkok',
			'Asia/Beirut',
			'Asia/Bishkek',
			'Asia/Brunei',
			'Asia/Calcutta',
			'Asia/Chungking',
			'Asia/Colombo',
			'Asia/Dacca',
			'Asia/Damascus',
			'Asia/Dubai',
			'Asia/Dushanbe',
			'Asia/Gaza',
			'Asia/Harbin',
			'Asia/Hong_Kong',
			'Asia/Irkutsk',
			'Asia/Jakarta',
			'Asia/Jayapura',
			'Asia/Jerusalem',
			'Asia/Kabul',
			'Asia/Kamchatka',
			'Asia/Karachi',
			'Asia/Kashgar',
			'Asia/Katmandu',
			'Asia/Krasnoyarsk',
			'Asia/Kuala_Lumpur',
			'Asia/Kuching',
			'Asia/Kuwait',
			'Asia/Macao',
			'Asia/Magadan',
			'Asia/Manila',
			'Asia/Muscat',
			'Asia/Nicosia',
			'Asia/Novosibirsk',
			'Asia/Omsk',
			'Asia/Phnom_Penh',
			'Asia/Pyongyang',
			'Asia/Qatar',
			'Asia/Rangoon',
			'Asia/Riyadh',
			'Asia/Saigon',
			'Asia/Samarkand',
			'Asia/Seoul',
			'Asia/Shanghai',
			'Asia/Singapore',
			'Asia/Taipei',
			'Asia/Tashkent',
			'Asia/Tbilisi',
			'Asia/Tehran',
			'Asia/Thimbu',
			'Asia/Tokyo',
			'Asia/Ujung_Pandang',
			'Asia/Ulan_Bator',
			'Asia/Urumqi',
			'Asia/Vientiane',
			'Asia/Vladivostok',
			'Asia/Yakutsk',
			'Asia/Yekaterinburg',
			'Asia/Yerevan',
			'Atlantic/Azores',
			'Atlantic/Bermuda',
			'Atlantic/Canary',
			'Atlantic/Cape_Verde',
			'Atlantic/Faeroe',
			'Atlantic/Jan_Mayen',
			'Atlantic/Madeira',
			'Atlantic/Reykjavik',
			'Atlantic/South_Georgia',
			'Atlantic/St_Helena',
			'Atlantic/Stanley',
			'Australia/Adelaide',
			'Australia/Brisbane',
			'Australia/Broken_Hill',
			'Australia/Darwin',
			'Australia/Hobart',
			'Australia/Lindeman',
			'Australia/Lord_Howe',
			'Australia/Melbourne',
			'Australia/Perth',
			'Australia/Sydney',
			'Europe/Amsterdam',
			'Europe/Andorra',
			'Europe/Athens',
			'Europe/Belfast',
			'Europe/Belgrade',
			'Europe/Berlin',
			'Europe/Bratislava',
			'Europe/Brussels',
			'Europe/Bucharest',
			'Europe/Budapest',
			'Europe/Chisinau',
			'Europe/Copenhagen',
			'Europe/Dublin',
			'Europe/Gibraltar',
			'Europe/Helsinki',
			'Europe/Istanbul',
			'Europe/Kaliningrad',
			'Europe/Kiev',
			'Europe/Lisbon',
			'Europe/Ljubljana',
			'Europe/London',
			'Europe/Luxembourg',
			'Europe/Madrid',
			'Europe/Malta',
			'Europe/Minsk',
			'Europe/Monaco',
			'Europe/Moscow',
			'Europe/Oslo',
			'Europe/Paris',
			'Europe/Prague',
			'Europe/Riga',
			'Europe/Rome',
			'Europe/Samara',
			'Europe/San_Marino',
			'Europe/Sarajevo',
			'Europe/Simferopol',
			'Europe/Skopje',
			'Europe/Sofia',
			'Europe/Stockholm',
			'Europe/Tallinn',
			'Europe/Tirane',
			'Europe/Vaduz',
			'Europe/Vatican',
			'Europe/Vienna',
			'Europe/Vilnius',
			'Europe/Warsaw',
			'Europe/Zagreb',
			'Europe/Zurich',
			'Indian/Antananarivo',
			'Indian/Chagos',
			'Indian/Christmas',
			'Indian/Cocos',
			'Indian/Comoro',
			'Indian/Kerguelen',
			'Indian/Mahe',
			'Indian/Maldives',
			'Indian/Mauritius',
			'Indian/Mayotte',
			'Indian/Reunion',
			'Pacific/Apia',
			'Pacific/Auckland',
			'Pacific/Chatham',
			'Pacific/Easter',
			'Pacific/Efate',
			'Pacific/Enderbury',
			'Pacific/Fakaofo',
			'Pacific/Fiji',
			'Pacific/Funafuti',
			'Pacific/Galapagos',
			'Pacific/Gambier',
			'Pacific/Guadalcanal',
			'Pacific/Guam',
			'Pacific/Honolulu',
			'Pacific/Johnston',
			'Pacific/Kiritimati',
			'Pacific/Kosrae',
			'Pacific/Kwajalein',
			'Pacific/Majuro',
			'Pacific/Marquesas',
			'Pacific/Midway',
			'Pacific/Nauru',
			'Pacific/Niue',
			'Pacific/Norfolk',
			'Pacific/Noumea',
			'Pacific/Pago_Pago',
			'Pacific/Palau',
			'Pacific/Pitcairn',
			'Pacific/Ponape',
			'Pacific/Port_Moresby',
			'Pacific/Rarotonga',
			'Pacific/Saipan',
			'Pacific/Tahiti',
			'Pacific/Tarawa',
			'Pacific/Tongatapu',
			'Pacific/Truk',
			'Pacific/Wake',
			'Pacific/Wallis',
			'Pacific/Yap',
		);
	}
};

class DevblocksCalendarHelper {
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
	
	static function getWeeklyDates($start, array $weekdays, $until=null, $max_iter=null) {
		$dates = array();
		
		// If we're asked to make things starting at a date beyond the end date, stop.
		if(!is_null($until) && $start > $until)
			return $dates;		

		$name_weekdays = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
		$num_weekdays = count($weekdays);

		$cur_dow = (integer) date('w', $start);
		
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
			
			$date = strtotime(
				sprintf("%s %s",
					$name_weekdays[$next_dow],
					($next_dow <= $cur_dow) ? 'next week' : 'this week' 
				),
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