<?php
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

include_once(DEVBLOCKS_PATH . "api/Engine.php");

include_once(DEVBLOCKS_PATH . "api/services/bootstrap/logging.php");
include_once(DEVBLOCKS_PATH . "api/services/bootstrap/cache.php");
include_once(DEVBLOCKS_PATH . "api/services/bootstrap/database.php");
include_once(DEVBLOCKS_PATH . "api/services/bootstrap/classloader.php");

define('PLATFORM_BUILD', 2019101501);

class _DevblocksServices {
	private static $_instance = null;
	
	private function __construct() {}
	
	static function getInstance() {
		if(is_null(self::$_instance))
			self::$_instance = new _DevblocksServices();
		
		return self::$_instance;
	}
	
	/**
	 * 
	 * @return _DevblocksAutomationService
	 */
	function automation() {
		return _DevblocksAutomationService::getInstance();
	}
	
	/**
	 * 
	 * @return _DevblocksBayesClassifierService
	 */
	function bayesClassifier() {
		return _DevblocksBayesClassifierService::getInstance();
	}
	
	/**
	 * 
	 * @return _DevblocksCacheManager
	 */
	function cache() {
		return _DevblocksCacheManager::getInstance();
	}
	
	/**
	 * 
	 * @return _DevblocksCaptchaService
	 */
	function captcha() {
		return _DevblocksCaptchaService::getInstance();
	}
	
	/**
	 *
	 * @return _DevblocksChartService
	 */
	function chart() {
		return _DevblocksChartService::getInstance();
	}
	
	/**
	 * 
	 * @return _DevblocksClassLoadManager
	 */
	function classloader() {
		return _DevblocksClassLoadManager::getInstance();
	}
	
	/**
	 * 
	 * @return _DevblocksDataService
	 */
	function data() {
		return _DevblocksDataService::getInstance();
	}
	
	/**
	 * 
	 * @return _DevblocksDatabaseManager|NULL
	 */
	function database() {
		return _DevblocksDatabaseManager::getInstance();
	}
	
	/**
	 * 
	 * @return _DevblocksDateManager
	 */
	function date() {
		return _DevblocksDateManager::getInstance();
	}
	
	/**
	 * 
	 * @return _DevblocksEncryptionService
	 */
	function encryption() {
		return _DevblocksEncryptionService::getInstance();
	}
	
	/**
	 * 
	 * @return _DevblocksEventManager
	 */
	function event() {
		return _DevblocksEventManager::getInstance();
	}
	
	/**
	 *
	 * @return _DevblocksFileService
	 */
	function file() {
		return _DevblocksFileService::getInstance();
	}
	
	/**
	 * 
	 * @return _DevblocksHttpService
	 */
	function http() {
		return _DevblocksHttpService::getInstance();
	}
	
	/**
	 * 
	 * @return Extension_DevblocksGpgEngine
	 */
	function gpg() {
		return _DevblocksGPGService::getInstance();
	}
	
	/**
	 *
	 * @return _DevblocksKataService
	 */
	function kata() {
		return _DevblocksKataService::getInstance();
	}

	/**
	 * 
	 * @param string $prefix
	 * @return _DevblocksLogManager
	 */
	function log($prefix=null) {
		return _DevblocksLogManager::getConsoleLog($prefix);
	}
	
	/**
	 * 
	 * @return _DevblocksEmailManager
	 */
	function mail() {
		return _DevblocksEmailManager::getInstance();
	}
	
	/**
	 * @return _DevblocksMetricsService
	 */
	function metrics() {
		return _DevblocksMetricsService::getInstance();
	}
	
	/**
	 * @return _DevblocksMultiFactorAuthService
	 */
	function mfa() {
		return _DevblocksMultiFactorAuthService::getInstance();
	}
	
	/**
	 * @return _DevblocksOAuthService
	 */
	function oauth() {
		return _DevblocksOAuthService::getInstance();
	}
	
	/**
	 * 
	 * @return _DevblocksOpenIDManager
	 */
	function openid() {
		return _DevblocksOpenIDManager::getInstance();
	}
	
	/**
	 * 
	 * @return _DevblocksNaturalLanguageManager
	 */
	function nlp() {
		return _DevblocksNaturalLanguageManager::getInstance();
	}
	
	/**
	 * 
	 * @param integer $inputs
	 * @param integer $hiddens
	 * @param integer $outputs
	 * @param float $learning_rate
	 * @return DevblocksNeuralNetwork
	 */
	function neuralNetwork($inputs, $hiddens, $outputs, $learning_rate) {
		return _DevblocksNeuralNetworkService::createNeuralNetwork($inputs, $hiddens, $outputs, $learning_rate);
	}
	
	/**
	 * 
	 * @return _DevblocksPluginSettingsManager
	 */
	function pluginSettings() {
		return _DevblocksPluginSettingsManager::getInstance();
	}
	
	/**
	 * @return _DevblocksQueueService
	 */
	function queue() {
		return _DevblocksQueueService::getInstance();
	}
	
	/**
	 * 
	 * @return _DevblocksRegistryManager
	 */
	function registry() {
		return _DevblocksRegistryManager::getInstance();
	}
	
	/**
	 * 
	 * @return _DevblocksSessionManager
	 */
	function session() {
		return _DevblocksSessionManager::getInstance();
	}
	
	/**
	 * 
	 * @return _DevblocksSheetService
	 */
	function sheet() {
		return _DevblocksSheetService::getInstance();
	}
	
	/**
	 *
	 * @return _DevblocksStatsService
	 */
	function stats() {
		return _DevblocksStatsService::getInstance();
	}
	
	/**
	 * 
	 * @return _DevblocksStringService
	 */
	function string() {
		return _DevblocksStringService::getInstance();
	}
	
	/**
	 * 
	 * @return Smarty
	 */
	function template() {
		return _DevblocksTemplateManager::getInstance();
	}
	
	/**
	 * 
	 * @return _DevblocksTemplateBuilder
	 */
	function templateBuilder() {
		return _DevblocksTemplateBuilder::getInstance();
	}
	
	/**
	 * 
	 * @return Smarty
	 */
	function templateSandbox() {
		return _DevblocksTemplateManager::getInstanceSandbox();
	}
	
	/**
	 * @return _DevblocksUiManager
	 */
	function ui() {
		return _DevblocksUiManager::getInstance();
	}
	
	/**
	 * 
	 * @return _DevblocksUrlManager
	 */
	function url() {
		return _DevblocksUrlManager::getInstance();
	}
	
	/**
	 * 
	 * @return _DevblocksValidationService
	 */
	function validation() {
		return new _DevblocksValidationService();
	}
	
	function vobject() : _DevblocksVObjectService {
		return _DevblocksVObjectService::getInstance();
	}
}

/**
 * A platform container for plugin/extension registries.
 *
 * @author Jeff Standen <jeff@webgroupmedia.com>
 */
class DevblocksPlatform extends DevblocksEngine {
	const TRANSLATE_NONE = 0;
	const TRANSLATE_UCFIRST = 1;
	const TRANSLATE_CAPITALIZE = 2;
	const TRANSLATE_UPPER = 4;
	const TRANSLATE_LOWER = 8;
	
	private function __construct() { return false; }
	
	static function translate($token, $format = DevblocksPlatform::TRANSLATE_NONE) {
		$translate = DevblocksPlatform::getTranslationService();
		$string = $translate->_($token);
		
		switch($format) {
			case self::TRANSLATE_UCFIRST:
				return mb_ucfirst($string);
				break;
				
			case self::TRANSLATE_CAPITALIZE:
				return mb_convert_case($string, MB_CASE_TITLE);
				break;
				
			case self::TRANSLATE_UPPER:
				return mb_convert_case($string, MB_CASE_UPPER);
				break;
				
			case self::TRANSLATE_LOWER:
				return mb_convert_case($string, MB_CASE_LOWER);
				break;
				
			default:
				return $string;
				break;
		}
	}
	
	static function translateCapitalized($token) {
		return self::translate($token, DevblocksPlatform::TRANSLATE_CAPITALIZE);
	}
	
	static function translateLower($token) {
		return self::translate($token, DevblocksPlatform::TRANSLATE_LOWER);
	}

	static function uninstallPlugin($plugin_id) {
		if(null !== ($plugin = DevblocksPlatform::getPlugin($plugin_id))) {
			$plugin->uninstall();
			DevblocksPlatform::readPlugins(false);
		}
	}

	/**
	 * @param mixed $value
	 * @param string $type
	 * @param mixed $default
	 * @return mixed
	 * @test DevblocksPlatformTest
	 */
	static function importVar($value, $type=null, $default=null) {
		if(is_null($value))
			$value = $default;
		
		if(DevblocksPlatform::strStartsWith($type, 'array:')) {
			list($type, $array_cast) = explode(':', $type, 2);
		}
		
		// Sanitize input
		switch($type) {
			case 'array':
				if(!is_array($value)) {
					if(is_null($value))
						$value = array();
					else
						$value = array($value);
				}
				
				if(isset($array_cast))
					$value = DevblocksPlatform::sanitizeArray($value, $array_cast);
				break;
				
			case 'bit':
				$value = !empty($value) ? 1 : 0;
				break;
				
			case 'bool':
			case 'boolean':
				if(is_string($value) && in_array(DevblocksPlatform::strLower($value), array('true', 'false')))
					return (0 == strcasecmp($value, 'true')) ? true : false;
					
				if(is_string($value) && in_array(DevblocksPlatform::strLower($value), array('yes', 'no')))
					return (0 == strcasecmp($value, 'yes')) ? true : false;
				
				$value = !empty($value) ? true : false;
				break;
				
			case 'float':
				$value = floatval($value);
				break;
				
			case 'int':
			case 'integer':
				if(is_scalar($value)) {
					$value = intval($value);
				} else {
					$value = 0;
				}
				break;
				
			case 'string':
				if(is_bool($value))
					return $value ? 'true' : 'false';
				
				if(is_scalar($value)) {
					$value = strval($value);
				} else {
					$value = '';
				}
				break;
				
			case 'timestamp':
				if(!is_numeric($value)) {
					try {
						$value = strtotime($value);
					} catch(Exception $e) {}
				} else {
					$value = abs(intval($value));
				}
				break;
				
			default:
				settype($value, $type);
				break;
		}
		
		return $value;
	}
	
	/**
	 * @param mixed $var
	 * @param string $cast
	 * @param mixed $default
	 * @return mixed
	 * @test DevblocksPlatformTest
	 */
	static function importGPC($var, $cast=null, $default=null) {
		if (is_null($var) && !is_null($default)) {
			$var = $default;
		}

		if(!is_null($cast))
			$var = self::importVar($var, $cast, $default);
		
		return $var;
	}

	/**
	 * 
	 * @param integer $n The number to test
	 * @param integer $min Inclusive lower bounds
	 * @param integer $max Inclusive upper bounds
	 * @return integer
	 * @test DevblocksPlatformTest
	 */
	static function intClamp($n, $min, $max) {
		return min(max(intval($n), $min), $max);
	}
	
	/**
	 * 
	 * @param float $n The number to test
	 * @param float $min Inclusive lower bounds
	 * @param float $max Inclusive upper bounds
	 * @return float
	 * @test DevblocksPlatformTest
	 */
	static function floatClamp($n, $min, $max) {
		return min(max(floatval($n), $min), $max);
	}
	
	/**
	 * Zero fill and interpolate the series
	 * 
	 * @param array $array
	 * @param string $unit
	 * @param integer $step
	 * @param integer $limit
	 * @return array
	 */
	static function dateLerpArray(array $array, $unit, $step=1, $limit=10000) {
		if(empty($array))
			return [];
		
		$unit_original = $unit;
		
		if($unit == 'dayofmonth' || $unit == 'dayofweek') {
			$unit = 'day';
		} else if($unit == 'hourofday' || $unit == 'hourofdayofweek') {
			$unit = 'hour';
		} else if($unit == 'minute') {
			$unit = 'minute';
		} else if($unit == 'monthofyear') {
			$unit = 'month';
		} else if($unit == 'quarter') {
			$unit = 'day';
			$step = 90;
		} else if($unit == 'weekofyear' || $unit == 'week-sun') {
			$unit = 'week';
		}
		
		$timestamps = array_map(
			function($date_string) use ($unit, $unit_original) {
				try {
					if($unit == 'year' && is_numeric($date_string)) {
						$date_string .= '-01-01';
					} elseif ($unit_original == 'quarter') {
						$date_string = DevblocksPlatform::services()->date()->getDateFromYearQuarter($date_string);
					}
					
					$ts = new DateTime($date_string);
					
					if($unit_original == 'quarter') {
						DevblocksPlatform::noop();
					} else if($unit == 'month') {
						$ts->modify('first day of this month 00:00:00');
					}
					
					return $ts->getTimestamp();
				} catch (Exception $e) {
					return 0;
				}
			},
			$array
		);
		
		$ts = min($timestamps);
		$ts_end = max($timestamps);
		
		$values = [];
		
		try {
			$tick = new DateTime();
			$tick->setTimestamp($ts);
			
			if(false === ($interval = DateInterval::createFromDateString(sprintf('+%d %s', $step, $unit)))) {
				return [];
			}
			
		} catch (Exception $e) {
			return [];
		}
		
		$counter = 0;
		
		// Always advance in UTC to avoid DST issues
		while($tick->getTimestamp() <= $ts_end) {
			$values[] = $tick->getTimestamp();
			
			if($limit && ++$counter >= $limit)
				break;
			
			$tick->add($interval);
			
			if('quarter' == $unit_original)
				$tick->modify('last day of this month');
			
			if(end($values) == $tick->getTimestamp())
				$tick->add(DateInterval::createFromDateString(sprintf('+%d %s', $step+1, $unit)));
		}
		
		return $values;
	}
	
	/**
	 * 
	 * See: http://stackoverflow.com/questions/20903106/interpolating-colors-in-php
	 * 
	 * @param string $from_hex (e.g. FF0000)
	 * @param string $to_hex (e.g. 00FF00)
	 * @param float $ratio (0.0-1.0)
	 * @return string
	 */
	static function colorLerp($from_hex, $to_hex, $ratio) {
		$from_hex = hexdec(ltrim($from_hex,'#'));
		$to_hex = hexdec(ltrim($to_hex,'#'));
		
		$from_r = $from_hex & 0xFF0000;
		$from_g = $from_hex & 0x00FF00;
		$from_b = $from_hex & 0x0000FF;
		$to_r = $to_hex & 0xFF0000;
		$to_g = $to_hex & 0x00FF00;
		$to_b = $to_hex & 0x0000FF;
		
		$lerp_r = $from_r + (($to_r - $from_r) * $ratio) & 0xFF0000;
		$lerp_g = $from_g + (($to_g - $from_g) * $ratio) & 0x00FF00;
		$lerp_b = $from_b + (($to_b - $from_b) * $ratio) & 0x0000FF;
		
		$color = dechex($lerp_r | $lerp_g | $lerp_b);
		$color = str_pad($color, 6, '0', STR_PAD_LEFT);
		
		return '#' . DevblocksPlatform::strUpper($color);
	}
	
	static function colorLerpArray(array $colors) {
		if(!is_array($colors))
			return [];
		
		$len = count($colors);
		
		foreach($colors as $pos => $color) {
			if(!in_array($color, [null, '#FFFFFF']))
				continue;
			
			$from_color = '#FFFFFF';
			$to_color = '#FFFFFF';
			
			for($last=$pos-1; $last >= 0; $last--) {
				if($colors[$last] != '#FFFFFF') {
					$from_color = $colors[$last];
					break;
				}
			}
			
			for($next=$pos+1; $next < $len; $next++) {
				if($colors[$next] != '#FFFFFF') {
					$to_color = $colors[$next];
					break;
				}
			}
			
			$colors[$pos] = DevblocksPlatform::colorLerp($from_color, $to_color, ($pos-$last)/($next-$last));
		}
		
		return $colors;
	}
	
	/*
	 * @deprecated
	 */
	static function curlInit($url=null) {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		
		return $ch;
	}
	
	/*
	 * @deprecated
	 */
	static function curlExec($ch, $follow=false, $return=true) {
		// Proxy
		if(defined('DEVBLOCKS_HTTP_PROXY') && DEVBLOCKS_HTTP_PROXY) {
			curl_setopt($ch, CURLOPT_PROXY, DEVBLOCKS_HTTP_PROXY);
		}
		
		// Return transfer
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, $return);
		
		// Follow redirects
		if($follow) {
			curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
			$out = curl_exec($ch);
			$status = curl_getinfo($ch);
			
			// It's a 3xx redirect
			// [TODO] Catch redirect loops
			if(isset($status['redirect_url']) && $status['redirect_url'] && floor($status['http_code']/100) == 3) {
				curl_setopt($ch, CURLOPT_URL, $status['redirect_url']);
				return self::curlExec($ch, $follow, $return);
			}
			
			return $out;
		}
		
		return curl_exec($ch);
	}
	
	/**
	 * Returns a string as a regexp.
	 * "*bob" returns "/(.*?)bob/".
	 * @param string $string
	 * @param boolean $is_substring
	 * @return string
	 * @test DevblocksPlatformTest
	 */
	static function parseStringAsRegExp($string, $is_substring=false) {
		$pattern = str_replace(array('*'),'__any__', $string);
		$pattern = sprintf("/%s%s%s/i",
			!$is_substring ? '^' : '',
			str_replace(array('__any__'),'(.*?)', preg_quote($pattern)),
			!$is_substring ? '$' : ''
		);
		return $pattern;
	}
	
	/**
	 * Returns a formatted string as a number of bytes (e.g. 200M = 209715200)
	 *
	 * @param string $string
	 * @return integer|FALSE
	 * @test DevblocksPlatformTest
	 */
	static function parseBytesString($string) {
		if(is_numeric($string)) {
			return intval($string);
			
		} else {
			$matches = [];
			
			if(!preg_match('#(\d+)(.*)#', $string, $matches))
				return false;
			
			$value = intval($matches[1]);
			$unit = DevblocksPlatform::strLower(trim($matches[2]));
			
			switch($unit) {
				default:
				case 'b':
					return $value;
				case 'k':
				case 'kb':
					return $value * 1000;
				case 'kib':
					return $value * 1024;
				case 'm':
				case 'mb':
					return $value * pow(1000,2);
				case 'mib':
					return $value * pow(1024,2);
				case 'g':
				case 'gb':
					return $value * pow(1000,3);
				case 'gib':
					return $value * pow(1024,3);
				case 't':
				case 'tb':
					return $value * pow(1000,4);
				case 'tib':
					return $value * pow(1024,4);
			}
		}
	}
	
	/**
	 * 
	 * @param string $string
	 * @param boolean $keep_blanks
	 * @param boolean $trim_lines
	 * @return array
	 * @test DevblocksPlatformTest
	 */
	static function parseCrlfString($string, $keep_blanks=false, $trim_lines=true) {
		$string = strval($string);
		$string = str_replace("\r\n","\n",$string);
		$parts = preg_split("/[\r\n]/", $string);
		
		// Remove any empty tokens
		foreach($parts as $idx => $part) {
			$parts[$idx] = $trim_lines ? trim($part) : $part;
			if(!$keep_blanks && 0 == strlen($parts[$idx]))
				unset($parts[$idx]);
		}
		
		return $parts;
	}
	
	/**
	 * Parse HTTP header attribute strings, like: charset=utf-8
	 * 
	 * @param ?string $header_value
	 * @return boolean|array
	 */
	static function parseHttpHeaderAttributes(?string $header_value) {
		$results = [];
		
		if(
			!$header_value
			|| false == ($attributes = explode(';', $header_value)) 
			|| !is_array($attributes)
			)
			return false;
		
		foreach($attributes as $attribute) {
			list($k, $v) = array_map('trim', explode('=', $attribute, 2));
			$results[DevblocksPlatform::strLower($k)] = $v;
		}
		
		return $results;
	}
	
	/**
	 * 
	 * @param string $string
	 * @return array
	 * @test DevblocksPlatformTest
	 */
	static function parseAtMentionString($string) {
		$matches = [];
		
		//$string = "@Hildy Do you have time for this today?  If not, ask @Jeff, or @Darren.";
		preg_match_all('#(\@[A-Za-z0-9_\-\.]+)#', $string, $matches);
		
		if(is_array($matches) && isset($matches[1])) {
			return array_unique($matches[1]);
		}
		
		return false;
	}
	
	/**
	 * 
	 * @param string $string
	 * @return array|false
	 */
	static function parseGeoPointString($string, &$error=null) {
		$error = null;
		$is_latlong = true;
		
		if(!is_string($string)) {
			$error = 'Must be a string.';
			return false;
		}
		
		// Parse out POINT() wrapper
		if(DevblocksPlatform::strStartsWith($string, 'POINT(')) {
			$is_latlong = false;
			$string = trim(substr($string,5),'()');
		}
		
		$replacements = [
			' N' => '' ,
			' S' => '' ,
			' W' => '' ,
			' E' => '' ,
			'"' => '' ,
			', ' => ',' ,
			' ' => ',' ,
		];
		
		// Handle formatting
		$string = str_replace(array_keys($replacements), array_values($replacements), $string);
		
		if(false == (@$coords = DevblocksPlatform::parseCsvString($string))) {
			$error = 'Must be a set of (latitude,longitude) coordinates separated with a comma.';
			return false;
		}
		
		if(2 != count($coords)) {
			$error = 'Must be a set of (latitude,longitude) coordinates separated with a comma.';
			return false;
		}
		
		if($is_latlong) {
			list($latitude, $longitude) = array_pad($coords, 2, null);
		} else {
			list($longitude, $latitude) = array_pad($coords, 2, null);
		}
		
		if(!is_numeric($latitude) || $latitude < -90 || $latitude > 90) {
			$error = 'Latitude must be -90 to 90.';
			return false;
		}
		
		if(!is_numeric($longitude) || $longitude < -180 || $longitude > 180) {
			$error = 'Longitude must be -180 to 180.';
			return false;
		}
		
		return [
			'latitude' => floatval($latitude),
			'longitude' => floatval($longitude),
		];
	}
	
	/**
	 * 
	 * @param array $objects
	 * @return string[]
	 * @test DevblocksPlatformTest
	 */
	static function objectsToStrings($objects) {
		$strings = array();
		
		if(is_array($objects))
		foreach($objects as $k => $o) {
			$strings[$k] = (string) $o;
		}
		
		return $strings;
	}
	
	static function objectToArray($object) {
		return json_decode(json_encode($object), true);
	}
	
	static function objectsToArrays($objects) {
		$arrays = [];
		
		foreach($objects as $key => $object) {
			if(!is_object($object))
				continue;
			
			$arrays[$key] = self::objectToArray($object);
		}
		
		return $arrays;
	}
	
	/**
	 * 
	 * @param integer $version
	 * @param integer $sections
	 * @return string A dot-delimited version string
	 * @test DevblocksPlatformTest
	 */
	static function intVersionToStr($version, $sections=3) {
		// If it's not an even number length, pad with one 0 on the left (e.g. 709 -> 0709)
		if(strlen($version))
			$version = '0' . $version;
		
		// If we don't have enough requested sections, pad the right.
		// We assume the given digits are always the most significant part of the version.
		$version = str_pad($version, $sections * 2, '0', STR_PAD_LEFT);
		$parts = str_split($version, 2);
		
		// Trim padded zeroes in a version section
		foreach($parts as $k => $v)
			$parts[$k] = intval($v);
		
		// Slice the version to the requested length
		$parts = array_slice($parts, 0, $sections);
		
		// Return as a dot-delimited string
		return implode('.', $parts);
	}
	
	static function arraySearchNoCase($needle, $haystack) {
		$haystack = array_map(
			function($e) {
				if(is_string($e))
					return DevblocksPlatform::strLower($e);
				
				return $e; 
			},
			$haystack
		);
		
		return array_search(DevblocksPlatform::strLower($needle), $haystack, true);
	}
	
	static function arrayInNoCase($key, $array) {
		return in_array(
			DevblocksPlatform::strLower($key),
			array_map(
				function($v) { 
					return is_string($v) ? DevblocksPlatform::strLower($v) : $v;
				},
				$array
			)
		);
	}
	
	static function arrayIsIndexed(array $array) {
		$len = count($array);
		
		// Ambiguous on empty arrays
		if(0 == $len)
			return null;
		
		return array_keys($array) === range(0, $len-1);
	}
	
	static function arrayPermutations(array $set) : array {
		// Empty set
		if(0 == count($set))
			return [];
		
		// Single item set
		if(1 == count($set))
			return [$set];
		
		$results = [];
		
		// Remove the first item and generate permutations for the rest
		foreach(array_keys($set) as $i) {
			$first = $set[$i];
			$rest = array_merge(array_slice($set,0, $i), array_slice($set, $i+1));
			
			foreach(self::arrayPermutations($rest) as $p)
				$results[] = array_merge([$first], $p);
		}
		
		return $results;
	}
	
	static function arrayDictSet($var, $path, $val, $delim=null) {
		if(empty($var))
			$var = is_array($var) ? [] : new stdClass();
		
		$parts = explode($delim ?? '.', $path);
		$ptr =& $var;
		
		if(is_array($parts))
		foreach($parts as $part) {
			$part = str_replace('{DOT}', '.', $part);
			
			if('[]' == $part) {
				if(is_array($ptr))
					$ptr =& $ptr[];
				
			} elseif(is_array($ptr)) {
				if(!isset($ptr[$part]))
					$ptr[$part] = [];

				$ptr =& $ptr[$part];
				
			} elseif(is_object($ptr)) {
				if(!isset($ptr->$part))
					$ptr->$part = [];
				
				$ptr =& $ptr->$part;
			}
		}
		
		$ptr = $val;
		
		return $var;
	}
	
	static function arrayDictUnset($var, $paths) {
		if(empty($var))
			$var = is_array($var) ? [] : new stdClass();
		
		if(!is_array($paths)) {
			if(!is_null($paths)) {
				$paths = [$paths];
			} else {
				$paths = [];
			}
		}
		
		foreach($paths as $path) {
			$parts = explode('.', $path);
			
			if (empty($path) || 0 == count($parts))
				continue;
			
			$parts_last_idx = array_slice(array_keys($parts), -1)[0];
			$ptr =& $var;
			
			if(is_array($parts))
			foreach ($parts as $part_idx => $part) {
				$part = str_replace('{DOT}', '.', $part);
				
				if (is_array($ptr)) {
					if (!isset($ptr[$part]))
						continue;
					
					if ($part_idx == $parts_last_idx) {
						unset($ptr[$part]);
					} else {
						$ptr =& $ptr[$part];
					}
					
				} elseif (is_object($ptr)) {
					if (!isset($ptr->$part))
						continue;
					
					if ($part_idx == $parts_last_idx) {
						unset($ptr->$part);
					} else {
						$ptr =& $ptr->$part;
					}
				}
			}
		}
		
		return $var;
	}
	
	static function arrayBuildQueryString(array $args, $sort=true, $fix_numeric_indices=true) {
		if($sort)
			ksort($args);
		
		$str = http_build_query($args, '', '&', PHP_QUERY_RFC3986);
		
		// Fix numeric indices
		if($fix_numeric_indices)
			$str = preg_replace('#%5B[0-9]+%5D#simU', '%5B%5D', $str);
		
		return $str;
	}
	
	static function strParseQueryString($string) : array {
		if(empty($string))
			return [];
		
		$tuples = explode('&', $string);
		$vars = [];
		
		foreach($tuples as $tuple) {
			list($key, $value) = array_pad(explode('=', $tuple), 2, null);
			
			if(empty($key))
				continue;
			
			$key = urldecode($key);
			$value = urldecode($value);
			
			// Rewrite field[key] to field.key
			$key = str_replace('.', '{DOT}', $key);
			$key = preg_replace('#\[(.+?)\]#', '.\1', $key);
			$key = preg_replace('#\[\]#', '.[]', $key);
			$vars = DevblocksPlatform::arrayDictSet($vars, $key, $value);
		}
		
		return $vars;
	}
	
	static function strUpper($string) {
		return mb_convert_case($string, MB_CASE_UPPER);
	}
	
	static function strTitleCase($string) {
		return mb_convert_case(strval($string), MB_CASE_TITLE);
	}
	
	static function strUpperFirst($string, $lower_rest=false) {
		if(is_array($string)) {
			$string = implode(', ', $string);
		} elseif (!is_string($string)) {
			$string = strval($string) ?: '';
		}
		
		if($lower_rest)
			$string = mb_strtolower($string);
		
		return mb_strtoupper(mb_substr($string, 0, 1)) . mb_substr($string, 1);
	}
	
	static function strLower($string) {
		if(is_array($string)) {
			$string = implode(', ', $string);
		} elseif (!is_string($string)) {
			$string = strval($string) ?: '';
		}
		
		return mb_convert_case($string, MB_CASE_LOWER);
	}
	
	static function strStartsWith($string, $prefixes, $case_sensitive=true) {
		if(!is_string($string))
			$string = strval($string);

		if(!is_array($prefixes))
			$prefixes = [$prefixes];
		
		foreach($prefixes as $prefix) {
			if($case_sensitive) {
				if(0 == strcmp(substr($string, 0, strlen($prefix)), $prefix))
					return $prefix;
			} else {
				if(0 == strcasecmp(substr($string, 0, strlen($prefix)), $prefix))
					return $prefix;
			}
		}
		
		return false;
	}
	
	static function strEndsWith($string, $suffixes, $case_sensitive=true) {
		if(!is_array($suffixes))
			$suffixes = [$suffixes];
		
		foreach($suffixes as $suffix) {
			if($case_sensitive) {
				if(0 == strcmp(substr($string, -strlen($suffix)), $suffix))
					return $suffix;
			} else {
				if(0 == strcasecmp(substr($string, -strlen($suffix)), $suffix))
					return $suffix;
			}
		}
		
		return false;
	}
	
	static function strTrimStart($string, $prefixes) {
		if(!is_array($prefixes))
			$prefixes = [$prefixes];
		
		while ($prefix = DevblocksPlatform::strStartsWith($string, $prefixes)) {
			$string = substr($string, strlen($prefix));
		}
		
		return $string;
	}
	
	static function strTrimEnd($string, array $suffixes = []) {
		if(!is_array($suffixes))
			$suffixes = [$suffixes];
		
		while ($suffix = DevblocksPlatform::strEndsWith($string, $suffixes)) {
			$string = substr($string, 0, -strlen($suffix));
		}
		
		return $string;
	}
	
	static function strIsListItem($string) {
		// Is it using a typical list item delimiter to start?
		if(DevblocksPlatform::strStartsWith(ltrim($string), ['*','-','#']))
			return true;
		
		if(preg_match('/^\(*\d+\.*\)*/', ltrim($string)))
			return true;
		
		return false;
	}
	
	/**
	 * 
	 * @param string $version
	 * @param integer $sections
	 * @return integer
	 * @test DevblocksPlatformTest
	 */
	static function strVersionToInt($version, $sections=3) {
		$parts = explode('.', $version);
		
		// Trim versions with too many significant places
		if(count($parts) > $sections)
			$parts = array_slice($parts, 0, $sections);
		
		// Pad versions with too few significant places
		while(count($parts) < $sections)
			array_push($parts, '0');
			
		$v = 0;
		$multiplier = 1;
		foreach(array_reverse($parts) as $part) {
			$v += intval($part)*$multiplier;
			$multiplier *= 100;
		}
		
		return intval($v);
	}
	
	/**
	 * 
	 * @param string|int $bits
	 * @return int|false
	 */
	static function strBitsToInt($bits) {
		$is_negative = false;
		
		// Handle int param
		if(is_numeric($bits)) {
			$bits = intval($bits);
			
		// Handle string param
		} else if(is_string($bits)) {
			$parts = explode(' ', $bits, 2);
			
			switch(count($parts)) {
				case 1:
					$bits = intval($parts[0]);
					break;
					
				case 2:
					$value = intval($parts[0]);
					
					switch(DevblocksPlatform::strLower($parts[1])) {
						case 'byte':
						case 'bytes':
							$bits = intval($value) * 8;
							break;
							
						case 'bit':
						case 'bits':
							break;
							
						default:
							return false;
							break;
					}
					break;
				
				default:
					return false;
					break;
			}
		}
		
		@$bits = intval($bits);
		
		if($bits < 0) {
			$is_negative = true;
			$bits = abs($bits);
		}
		
		// 32-bit overflow?
		$max_bits = (PHP_INT_SIZE * 8)-1; // PHP ints are always signed
		if($bits > $max_bits)
			$bits = $max_bits;
		
		$int = pow(2, $bits);
		
		if($is_negative) {
			$int *= -1;
		}
		
		return $int;
	}
	
	/**
	 * 
	 * @param string $a
	 * @param string $b
	 * @param string $oper
	 * @return bool
	 * @test DevblocksPlatformTest
	 */
	public static function compareStrings($a, $b, $oper) {
		@$not = (substr($oper, 0, 1) == '!');
		@$oper = ltrim($oper, '!');
		
		$pass = false;
		
		switch($oper) {
			case '=':
			case 'is':
				$pass = (0==strcasecmp($a, $b));
				break;
			case 'like':
				$regexp = DevblocksPlatform::strToRegExp($b);
				$pass = @preg_match($regexp, $a);
				break;
			case 'contains':
				$pass = (false !== stripos($a, $b)) ? true : false;
				break;
			case 'regexp':
				$pass = @preg_match($b, $a);
				break;
		}
		
		return ($not) ? !$pass : $pass;
	}
	
	/**
	 * Return a string as a regular expression, parsing * into a non-greedy
	 * wildcard, etc.
	 *
	 * @param string $arg
	 * @param boolean $is_partial
	 * @return string
	 * @test DevblocksPlatformTest
	 */
	static function strToRegExp($arg, $is_partial=false, $escape=true, $wrap=true) {
		$arg = str_replace(array('*'),array('__WILD__'),$arg);
		
		return sprintf("%s%s%s%s%s",
			($wrap ? '/' : ''),
			($is_partial ? '' : '^'),
			str_replace(array('__WILD__','/'),array('(.*?)','\/'), $escape ? preg_quote($arg) : $arg),
			($is_partial ? '' : '$'),
			($wrap ? '/i' : '')
		);
	}
	
	/**
	 * Return a string with only its alphanumeric characters
	 *
	 * @param string $arg
	 * @param string $also
	 * @param string $replace
	 * @return string
	 * @test DevblocksPlatformTest
	 */
	static function strAlphaNum($arg, $also='', $replace='') {
		$arg = $arg ?? '';
		$also = $also ?? '';
		$replace = $replace ?? '';
		
		if(!is_scalar($arg))
			return '';
		
		if(is_null($also))
			$also = '';
		
		return preg_replace("/[^A-Z0-9" . preg_quote($also, '/') . "]/i", $replace, $arg);
	}
	
	/**
	 * Return a string with only its numeric characters
	 *
	 * @param string $arg
	 * @param string $also
	 * @param string $replace
	 * @return string
	 * @test DevblocksPlatformTest
	 */
	static function strNum($arg, $also=null, $replace="") {
		return preg_replace("/[^0-9" . preg_quote($also ?? '', '/') . "]/i", $replace, $arg);
	}
	
	/**
	 * 
	 * @param string $string
	 * @param string $from_encoding
	 * @return string
	 * @test DevblocksPlatformTest
	 */
	static function strUnidecode($string, $from_encoding = 'utf-8') {
		if(0 == strlen($string))
			return '';
		
		$out = '';
			
		$string = (is_null($from_encoding))
			? mb_convert_encoding($string, "UCS-4BE")
			: mb_convert_encoding($string, "UCS-4BE", $from_encoding)
			;
		
		while(false !== ($part = mb_substr($string, 0, 25000)) && 0 !== mb_strlen($part)) {
			$string = mb_substr($string, mb_strlen($part));
			
			$unpack = unpack("N*", $part);
			
			foreach($unpack as $v) {
				$out .= self::_strUnidecodeLookup($v);
			}
			
			unset($unpack);
		}

		return $out;
	}
	
	/**
	 * 
	 * @param string $str
	 * @return string
	 * @test DevblocksPlatformTest
	 */
	static function strBase32Encode($str) {
		// RFC-4648
		$alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567";
		$binary = '';
		$output = '';
		$quantum = 0;
		
		// Read each ASCII character as binary octets
		foreach(str_split($str, 1) as $letter) {
			$binary .= sprintf("%08b", ord($letter));
		}
		
		// Rechunk the octets to 5-bit
		foreach(str_split($binary, 5) as $bits) {
			$quantum += strlen($bits);
			$bits = str_pad($bits, 5, '0');
			$output .= $alphabet[bindec($bits)];
		}
		
		// If our last quantum is less than 40 bits, pad
		// [TODO] There's likely a more logical way to do this
		switch($quantum % 40) {
			case 8:
				$output .= '======';
				break;
			case 16:
				$output .= '====';
				break;
			case 24:
				$output .= '===';
				break;
			case 32:
				$output .= '=';
				break;
		}
		
		return $output;
	}
	
	/**
	 * 
	 * @param string $str
	 * @param boolean $as_string
	 * @return string
	 * @test DevblocksPlatformTest
	 */
	static function strBase32Decode($str, $as_string=false) {
		// RFC-4648
		$alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567";
		$binary = '';
		$output = '';
		$pads = 0;
		
		// Iterate each letter of base32
		foreach(str_split(DevblocksPlatform::strUpper($str), 1) as $letter) {
			// If padding, skip
			if($letter == '=') {
				$pads++;
				continue;
			}
	
			// Append the letter's position (0-31) as 5 bits (2^5) in binary
			$binary .= sprintf("%05b", strpos($alphabet, $letter));
		}
	
		// Split the new binary string into 8-bit octets
		foreach(str_split($binary, 8) as $byte) {
			// Skip empty octets
			if($byte == '00000000') {
				$output .= "\0";
				continue;
			}
			
			// Concat the corresponding ASCII char for each octet
			$output .= chr(bindec($byte));
		}
		
		if($as_string)
			$output = trim($output);
		
		return $output;
	}
	
	static private $_purifier_configs = null;
	
	static function purifyHTMLOptions($inline_css=false, $untrusted=true, $unstyled=false) {
		$config = self::$_purifier_configs[$inline_css][$untrusted][$unstyled] ?? null;
		
		if(!$config) {
			$config = HTMLPurifier_Config::createDefault();
			$config->set('Core.ConvertDocumentToFragment', true);
			$config->set('HTML.Doctype', 'HTML 4.01 Transitional');
			$config->set('CSS.AllowTricky', true);
			$config->set('Attr.EnableID', true);
			//$config->set('HTML.TidyLevel', 'light');
			
			if($untrusted) {
				$config->set('HTML.TargetBlank', true);
				//$config->set('HTML.TargetNoreferrer', true);
				//$config->set('HTML.TargetNoopener', true);
			}
			
			// Remove class attributes if we inlined CSS styles
			if($inline_css) {
				$config->set('HTML.ForbiddenAttributes', array(
					'class',
				));
			}
			
			if($unstyled) {
				$config->set('CSS.ForbiddenProperties', [
					'background',
					'background-color',
					'border-color',
					'color',
					'filter',
					'opacity',
				]);
			}
			
			$config->set('URI.Host', DevblocksPlatform::getHostname());
			
			$config->set('URI.AllowedSchemes', array(
				'data' => true,
				'ftp' => true,
				'http' => true,
				'https' => true,
				'mailto' => true,
				'news' => true,
				'tel' => true,
			));
			
			$dir_htmlpurifier_cache = APP_TEMP_PATH . '/htmlpurifier-cache/';
			
			if(!is_dir($dir_htmlpurifier_cache)) {
				mkdir($dir_htmlpurifier_cache, 0755, true);
			}
			
			$config->set('Cache.SerializerPath', $dir_htmlpurifier_cache);
			
			$def = $config->getHTMLDefinition(true);
			
			// Allow Cerb data-* markup
			$def->info_global_attr['data-autocomplete'] = new HTMLPurifier_AttrDef_Integer();
			$def->info_global_attr['data-behavior-id'] = new HTMLPurifier_AttrDef_Integer();
			$def->info_global_attr['data-context'] = new HTMLPurifier_AttrDef_Text();
			$def->info_global_attr['data-context-id'] = new HTMLPurifier_AttrDef_Integer();
			$def->info_global_attr['data-interaction'] = new HTMLPurifier_AttrDef_Text();
			$def->info_global_attr['data-interaction-params'] = new HTMLPurifier_AttrDef_Text();
			$def->info_global_attr['data-query'] = new HTMLPurifier_AttrDef_Text();
			$def->info_global_attr['data-query-required'] = new HTMLPurifier_AttrDef_Text();
			
			// Allow basic buttons
			$html_button = $def->addElement(
				'button',
				'Inline',
				'Flow',
				'Common',
				[
					'type' => 'Enum#button'
				]
			);
			$html_button->excludes = ['a','Formctrl','form','isindex','fieldset','iframe'];
			
			self::$_purifier_configs[$inline_css][$untrusted][$unstyled] = $config;
		}
		
		return $config;
	}
	
	/**
	 * 
	 * @param string $dirty_html
	 * @param boolean $inline_css
	 * @param boolean $is_untrusted
	 * @param array $filters
	 * @return string
	 * @test DevblocksPlatformTest
	 */
	static function purifyHTML($dirty_html, $inline_css=false, $is_untrusted=true, array $filters=[], $unstyled=false) {
		// If we're passed a file pointer, load the literal string
		if(is_resource($dirty_html)) {
			$fp = $dirty_html;
			$dirty_html = null;
			while(!feof($fp))
				$dirty_html .= fread($fp, 4096);
		}
		
		// Handle inlining CSS
		if($inline_css) {
			if($dirty_html) {
				$css_converter = new CssToInlineStyles();
				$dirty_html = $css_converter->convert($dirty_html);
			}
		}
		
		$config = self::purifyHTMLOptions($inline_css, $is_untrusted, $unstyled);
		
		if($filters) {
			foreach ($filters as $filter) {
				$config->getURIDefinition()->addFilter($filter, $config);
			}
		}
		
		$purifier = new HTMLPurifier($config);
		
		return $purifier->purify($dirty_html);
	}
	
	/**
	 * 
	 * @param string $text
	 * @return string
	 * @test DevblocksPlatformTest
	 */
	static function parseMarkdown($text, $safeMode=false) {
		$text = strval($text);
		
		$parser = new Parsedown();
		$parser->setBreaksEnabled(true);
		$parser->setMarkupEscaped($safeMode);
		$parser->setSafeMode($safeMode);
		return $parser->parse($text);
	}
	
	static function parseRss($url) {
		// [TODO] curl | file_get_contents() support
		
		// Handle 'feed://' scheme
		if(preg_match('/^feed\:/', $url)) {
			$url = preg_replace("/^feed\:\/\//","http://", $url);
			$url = preg_replace("/^feed\:/","", $url);
		}
		
		if(extension_loaded("curl")) {
			$ch = DevblocksPlatform::curlInit();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			
			$user_agent = 'Cerb ' . APP_VERSION . ' (Build ' . APP_BUILD . ')';
			curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
			
			$data = DevblocksPlatform::curlExec($ch, true);
			curl_close($ch);
			
		} else {
			$logger = DevblocksPlatform::services()->log();
			$logger->error("[Platform] 'curl' extension is not enabled. Can not load a URL.");
			return;
		}
		
		if(empty($data))
			return true;
		
		if(null == (@$xml = simplexml_load_string($data)))
			return false;
			
		$root_tag = DevblocksPlatform::strLower(dom_import_simplexml($xml)->tagName);
		
		if('feed'==$root_tag && count($xml->entry)) { // Atom
			$feed = array(
				'title' => (string) $xml->title,
				'url' => $url,
				'items' => [],
			);
			
			if(!count($xml))
				return $feed;
	
			foreach($xml->entry as $entry) {
				$id = (string) $entry->id;
				$date = (string) $entry->published;
				$title = (string) $entry->title;
				$content = (string) $entry->summary;
				$link = '';

				// Fallbacks
				if(empty($date))
					$date = (string) $entry->updated;

				$date_timestamp = strtotime($date);
				$matches = [];
					
				// Link as the <id> element
				if(preg_match("/^(.*)\:\/\/(.*$)/i", $id, $matches)) {
					$link = $id;
				// Link as 'alternative' attrib
				} elseif(count($entry->link)) {
					foreach($entry->link as $link) {
						if(0==strcasecmp('alternate',(string)$link['rel'])) {
							$link = (string) $link['href'];
							break;
						}
					}
				}
				
				$feed['items'][] = array(
					'date' => empty($date_timestamp) ? time() : $date_timestamp,
					'link' => $link,
					'title' => $title,
					'content' => $content,
				);
			}
			
		} elseif('rdf:rdf'==$root_tag && count($xml->item)) { // RDF
			$feed = array(
				'title' => (string) $xml->channel->title,
				'url' => $url,
				'items' => array(),
			);
			
			if(!count($xml))
				return $feed;
	
			foreach($xml->item as $item) {
				$date = (string) $item->pubDate;
				$link = (string) $item->link;
				$title = (string) $item->title;
				$content = (string) $item->description;
				
				$date_timestamp = strtotime($date);
				
				$feed['items'][] = array(
					'date' => empty($date_timestamp) ? time() : $date_timestamp,
					'link' => $link,
					'title' => $title,
					'content' => $content,
				);
			}
			
		} elseif('rss'==$root_tag && count($xml->channel->item)) { // RSS
			$feed = array(
				'title' => (string) $xml->channel->title,
				'url' => $url,
				'items' => array(),
			);
			
			if(!count($xml))
				return $feed;
	
			foreach($xml->channel->item as $item) {
				$date = (string) $item->pubDate;
				$link = (string) $item->link;
				$title = (string) $item->title;
				$content = (string) $item->description;

				$date_timestamp = strtotime($date);
				
				$feed['items'][] = array(
					'date' => empty($date_timestamp) ? time() : $date_timestamp,
					'link' => $link,
					'title' => $title,
					'content' => $content,
				);
			}
		}

		if(empty($feed))
			return false;
		
		return $feed;
	}
	
	public static function strTruncate($string, int $length) {
		return DevblocksPlatform::services()->string()->truncate($string, $length);
	}
	
	static function strEscapeHtml($string) {
		$string = strval($string);
		
		if(0 == strlen($string))
			return '';
		
		return htmlentities($string, ENT_QUOTES, LANG_CHARSET_CODE);
	}

	static function strToInitials($string) {
		$string = strval($string);
		
		$strings = explode(' ', $string);
		
		// Two parts max
		if(count($strings) > 2)
			$strings = array(reset($strings), end($strings));
		
		array_walk($strings, function(&$string) {
			$string = substr($string,0,1);
		});
		
		return implode('', $strings);
	}
	
	/**
	 * Returns a string as alphanumerics delimited by underscores.
	 * For example: "Devs: 1000 Ways to Improve Sales" becomes
	 * "devs-1000-ways-to-improve-sales", which is suitable for
	 * displaying in a URL of a blog, faq, etc.
	 *
	 * @param string $str
	 * @return string
	 * @test DevblocksPlatformTest
	 */
	static function strToPermalink($string, $spaces_as='-') {
		$string = strval($string);
		
		if(0 == strlen($string))
			return '';
		
		// Unidecode
		$string = DevblocksPlatform::strUnidecode($string, LANG_CHARSET_CODE);
		
		// Remove certain marks
		$string = preg_replace('#[\'\"]#', '', $string);
		
		// Strip all punctuation to underscores
		$string = preg_replace('#[^a-zA-Z0-9\+\.\-_\(\)]#', $spaces_as, $string);
			
		// Collapse all underscores to singles
		$string = preg_replace(('#' . $spaces_as . $spaces_as . '+#'), $spaces_as, $string);
		
		return rtrim($string, $spaces_as);
	}
	
	/**
	 * 
	 * @param string $string
	 * @return string
	 * @test DevblocksPlatformTest
	 */
	static function strToHyperlinks($string, $as_html=true) {
		// Bail out if we're asked to auto-hyperlink a huge block of text
		if(strlen($string) > 100000)
			return $string;
		
		$replacements = [];
		
		if($as_html)
			$string = html_entity_decode($string, ENT_QUOTES, LANG_CHARSET_CODE);
		
		// Detect Markdown links
		$out = preg_replace_callback('@\[(.*?)\]\((.*?)\)@', function($matches) use ($as_html, &$replacements) {
			// If the label matches the link
			if(rtrim($matches[1],'/') == rtrim($matches[2],'/'))
				return $matches[2];
			
			$url_label = $matches[1];
			$url = $matches[2];
			
			// Ignore if dimensions (100,200)
			if(preg_match('/^\d+,\d+$/', $url))
				return $matches[0];
			
			return sprintf('%s <%s>',
				$url_label,
				$url
			);
		}, $string);
		
		// See: https://daringfireball.net/2010/07/improved_regex_for_matching_urls
		// See: https://gist.github.com/gruber/249502#gistcomment-1328838
		// Gruber2/cscott
		$out = preg_replace_callback('/\b((?:[a-z][\w\-]+:(?:\/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]|\((?:[^\s()<>]|(?:\([^\s()<>]+\)))*\))+(?:\((?:[^\s()<>]|(?:\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))/i', function($matches) use ($as_html, &$replacements) {
			// Ignore if it contains a : but not ://
			if(false !== strpos($matches[1], ':') && false === strpos($matches[1], '://')) {
				return $matches[1];
			}
			
			$token = sprintf('{{{URL_%d}}}', count($replacements));
			$url = $url_label = $matches[0];
			
			// If we don't have a protocol, default to http://
			if(false === strpos($url, '://'))
				$url = 'http://' . $url;
			
			$replacements[$token] = sprintf('<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
				$as_html ? htmlentities($url, ENT_QUOTES, LANG_CHARSET_CODE) : $url,
				$as_html ? htmlentities($url_label, ENT_QUOTES, LANG_CHARSET_CODE) : $url_label
			);
			
			return $token;
		}, $out);
		
		if(is_null($out)) {
			$out = $string;
		}
		
		if($as_html)
			$out = htmlentities($out, ENT_QUOTES, LANG_CHARSET_CODE);
		
		$out = str_replace(array_keys($replacements), $replacements, $out);
		
		return $out;
	}
	
	/**
	 * 
	 * @param string $string
	 * @param integer $length
	 * @return string
	 * @test DevblocksPlatformTest
	 */
	static function strSecsToString($string, $length=0) {
		if(empty($string) || !is_numeric($string))
			return '0 secs';
		
		$blocks = [
			'year' => 52*7*24*60*60,
			'month' => 365*24*60*60/12,
			'week' => 7*24*60*60,
			'day' => 24*60*60,
			'hour' => 60*60,
			'min' => 60,
			'sec' => 1,
		];
		
		$secs = intval($string);
		$output = [];
		
		foreach($blocks as $label => $increment) {
			$n = floor($secs/$increment);
			$secs -= ($n * $increment);
			
			if(!empty($n))
				$output[] = sprintf("%d %s%s",
					$n,
					$label,
					($n==1) ? '' : 's'
				);
		}
		
		if(!empty($length))
			$output = array_slice($output, 0, $length);
		
		return implode(', ', $output);
	}
	
	static function strTimeToSecs($string) {
		if(empty($string))
			return 0;
		
		$now = time();
		$then = strtotime("+".$string, $now);
		
		return $then-$now;
	}	
	
	/**
	 * 
	 * @param string $string
	 * @param boolean $is_delta
	 * return string
	 * @test DevblocksPlatformTest
	 */
	static function strPrettyTime($string, $is_delta=false) {
		if(empty($string) || !is_numeric($string))
			return '';
		
		if(!$is_delta) {
			$diffsecs = time() - intval($string);
		} else {
			$diffsecs = intval($string);
		}
		
		$whole = '';

		// Prefix
		if($is_delta) {
			if($diffsecs > 0)
				$whole .= '+';
			elseif($diffsecs < 0)
				$whole .= '-';
		}
		
		// The past
		if($diffsecs >= 0) {
			if($diffsecs >= 31557600) { // years
				$whole .= round($diffsecs/31557600).' year';
			} elseif($diffsecs >= 2592000) { // mo
				$whole .= round($diffsecs/2592000).' month';
			} elseif($diffsecs >= 86400) { // days
				$whole .= round($diffsecs/86400).' day';
			} elseif($diffsecs >= 3600) { // hours
				$whole .= floor($diffsecs/3600).' hour';
			} elseif($diffsecs >= 60) { // mins
				$whole .= floor($diffsecs/60).' min';
			} elseif($diffsecs >= 0) { // secs
				$whole .= $diffsecs.' sec';
			}
			
		} else { // The future
			if($diffsecs <= -31557600) { // years
				$whole .= round($diffsecs/-31557600).' year';
			} elseif($diffsecs <= -2592000) { // mo
				$whole .= round($diffsecs/-2592000).' month';
			} elseif($diffsecs <= -86400) { // days
				$whole .= round($diffsecs/-86400).' day';
			} elseif($diffsecs <= -3600) { // hours
				$whole .= floor($diffsecs/-3600).' hour';
			} elseif($diffsecs <= -60) { // mins
				$whole .= floor($diffsecs/-60).' min';
			} elseif($diffsecs <= 0) { // secs
				$whole .= round($diffsecs/-1).' sec';
			}
		}

		// Pluralize
		$whole .= (1 == abs(intval($whole))) ? '' : 's';

		if($diffsecs > 0 && !$is_delta)
			$whole .= ' ago';
		
		if($diffsecs == 0)
			$whole = 'just now';
		
		return $whole;
	}
	
	/**
	 * 
	 * @param string $string
	 * @param integer $precision
	 * @return string
	 * @test DevblocksPlatformTest
	 */
	static function strPrettyBytes($string, $precision='0') {
		if(!is_numeric($string))
			return '';
			
		$is_negative = (intval($string) < 0) ? true : false;
		$bytes = abs(intval($string));
		$precision = floatval($precision);
		$out = '';
		
		if($bytes >= 1000000000000) {
			$out = number_format($bytes/1000000000000,$precision) . ' TB';
		} elseif($bytes >= 1000000000) {
			$out = number_format($bytes/1000000000,$precision) . ' GB';
		} elseif ($bytes >= 1000000) {
			$out = number_format($bytes/1000000,$precision) . ' MB';
		} elseif ($bytes >= 1000) {
			$out = number_format($bytes/1000,$precision) . ' KB';
		} else {
			$out = $bytes . ' bytes';
		}
		
		return (($is_negative) ? '-' : '') . $out;
	}
	
	/**
	 * Takes a comma-separated value string and returns an array of tokens.
	 *
	 * @param string $string
	 * @param boolean $keep_blanks
	 * @param mixed $typecast
	 * @param boolean $limit
	 * @return array
	 * @test DevblocksPlatformTest
	 */
	static function parseCsvString($string, $keep_blanks=false, $typecast=null, $limit=null) {
		if(!is_string($string) || 0 == strlen($string))
			return [];
		
		if(!$keep_blanks)
			$string = rtrim($string, ', ');
		
		if($limit) {
			$tokens = explode(',', $string, $limit);
		} else {
			$tokens = explode(',', $string);
		}

		if(!is_array($tokens))
			return [];
		
		foreach($tokens as $k => $v) {
			if(!$keep_blanks && 0==strlen($tokens[$k])) {
				unset($tokens[$k]);
				continue;
			}
			
			if(!is_null($typecast)) {
				settype($v, $typecast);
			}
			
			$tokens[$k] = trim($v);
		}
		
		return $tokens;
	}
	
	static function strParseDecimal($number, $decimal_places=2, $decimal_separator='.') {
		if(!is_string($number) && !is_numeric($number))
			$number = '0';
		
		if(0 == strlen($number))
			$number = '0';
		
		if(false === strpos($decimal_separator, $number))
			$number .= $decimal_separator . str_repeat('0', $decimal_places);
		
		$parts = explode($decimal_separator, $number);
		
		$whole = DevblocksPlatform::strNum($parts[0]);
		$whole = str_pad($whole, 1, '0', STR_PAD_LEFT);
		
		$decimal = DevblocksPlatform::strNum($parts[1]);
		$decimal = str_pad($decimal, $decimal_places, '0', STR_PAD_RIGHT);
		
		// If the given number has too much precision, truncate it
		if(strlen($decimal) > $decimal_places)
			$decimal = substr($decimal, 0, $decimal_places);
		
		$number =  $whole . $decimal;
		
		if(0 == $number)
			$number = '0';
		
		return $number;
	}
	
	static function strFormatDecimal($number, $decimal_places=2, $decimal_separator='.', $thousands_separator=',') {
		if(!is_string($number) && !is_numeric($number))
			$number = '0';
		
		if(0 == strlen($number))
			$number = '0';
		
		if($decimal_places) {
			$whole = substr($number, 0, -$decimal_places);
			$decimal = substr($number, -$decimal_places);
			$decimal = str_pad($decimal, $decimal_places, '0', STR_PAD_LEFT);
			
		} else {
			$whole = $number;
		}
		
		if(empty($whole))
			$whole = '0';
		
		if($thousands_separator && strlen($whole) > 3) {
			$whole_parts = preg_split('//', $whole, -1, PREG_SPLIT_NO_EMPTY);
			
			for($pos=count($whole_parts)-3; $pos > 0; $pos -= 3) {
				array_splice($whole_parts, $pos, 0, [$thousands_separator]);
			}
			
			$whole = implode('', $whole_parts);
		}
		
		if($decimal_places) {
			$number = $whole . $decimal_separator . $decimal;
		} else {
			$number = $whole;
		}
		
		return $number;
	}
	
	/**
	 * 
	 * @param integer $number
	 * @param string $as
	 * @return string
	 * @test DevblocksPlatformTest
	 */
	static function formatNumberAs($number, $as) {
		$label = $number;
		
		switch($as) {
			case 'bytes':
				$label = DevblocksPlatform::strPrettyBytes(intval($number));
				break;
				
			case 'seconds':
				$label = DevblocksPlatform::strSecsToString(intval($number), 2);
				break;
				
			case 'minutes':
				$label = DevblocksPlatform::strSecsToString(intval($number) * 60);
				break;
				
			case 'number':
				$label = number_format(floatval($number), 0);
				break;
				
			case 'decimal':
				$label = number_format(floatval($number), 2);
				break;
			
			case 'percent':
				$label = number_format(floatval($number)) . '%';
				break;
			
			// [TODO] Currency
				
			default:
				break;
		}
		
		return $label;
	}
	
	/**
	 * Indents a flat JSON string or array to make it more human-readable.
	 *
	 * @param string|array $json The original JSON string to process.
	 * @return string Indented version of the original JSON string.
	 * @test DevblocksPlatformTest
	 */
	static function strFormatJson($json) {
		if(is_string($json))
			$json = json_decode($json, true);
		
		return json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	}
	
	/**
	 * Returns a pointer to an arbitrary property in a deeply nested JSON tree.  The pointer
	 * can be used to get or set the value at that location.
	 *
	 * @param array|string $json
	 * @param string $path
	 * @return mixed Pointer to the value at $path, or FALSE on error
	 * @test DevblocksPlatformTest
	 */
	static function &jsonGetPointerFromPath(array &$array, $path) {
		$ptr = null;
		$array_keys = [];
		
		if(empty($path))
			return $ptr;
		
		$keys = explode('.', $path);
		
		if(!is_array($keys) || empty($keys))
			return $ptr;
		
		foreach($keys as $k) {
			$matches = [];
			
			if(preg_match('/(.*)\[(\d+)\]/', $k, $matches)) {
				$array_keys[] = $matches[1];
				$array_keys[] = $matches[2];
			} else {
				$array_keys[] = $k;
			}
		}

		$ptr =& $array;

		while(null !== ($key = array_shift($array_keys))) {
			if(!isset($ptr[$key])) {
				$ptr = null;
				return $ptr;
			}
			
			$ptr =& $ptr[$key];
		}

		return $ptr;
	}

	/**
	 * Clears any platform-level plugin caches.
	 *
	 */
	static function clearCache($one_cache=null) {
		$cache = DevblocksPlatform::services()->cache();

		if(!empty($one_cache)) {
			$cache->remove($one_cache);
			
		} else { // All
			$cache->remove(self::CACHE_ACL);
			$cache->remove(self::CACHE_ACTIVITY_POINTS);
			$cache->remove(self::CACHE_CONTEXTS);
			$cache->remove(self::CACHE_CONTEXT_ALIASES);
			$cache->remove(self::CACHE_EVENTS);
			$cache->remove(self::CACHE_EVENT_POINTS);
			$cache->remove(self::CACHE_EXTENSIONS);
			$cache->remove(self::CACHE_PLUGINS);
			$cache->remove(self::CACHE_POINTS);
			$cache->remove(self::CACHE_TABLES);
			$cache->remove('devblocks:plugin:cerberusweb.core:params');
			$cache->remove('devblocks:plugin:devblocks.core:params');
			$cache->remove(_DevblocksClassLoadManager::CACHE_CLASS_MAP);
			$cache->removeByTags(['schema_mentions','schema_records','schema_workspaces','ui_search_menu']);
			
			// Flush template cache
			if(!APP_SMARTY_COMPILE_PATH_MULTI_TENANT) {
				$tpl = DevblocksPlatform::services()->template();
				$tpl->clearCompiledTemplate();
			}
			
			if(!APP_SMARTY_SANDBOX_COMPILE_PATH_MULTI_TENANT) {
				$tpl = DevblocksPlatform::services()->templateSandbox();
				$tpl->clearCompiledTemplate();
			}
			
			// Clear all locale caches
			$langs = DAO_Translation::getDefinedLangCodes();
			if(is_array($langs) && !empty($langs))
			foreach(array_keys($langs) as $lang_code) {
				$cache->remove(self::CACHE_TAG_TRANSLATIONS . '_' . $lang_code);
			}
		}

		// Cache-specific 'after' actions
		switch($one_cache) {
			case self::CACHE_PLUGINS:
			case self::CACHE_EXTENSIONS:
			case NULL:
				self::getPluginRegistry();
				self::getExtensionRegistry();
				break;
		}
	}

	public static function registerClasses($file,$classes=array()) {
		$classloader = DevblocksPlatform::services()->classloader();
		return $classloader->registerClasses($file,$classes);
	}
	
	public static function getStartTime() {
		return self::$start_time;
	}
	
	public static function getStartMemory() {
		return self::$start_memory;
	}
	
	public static function getStartPeakMemory() {
		return self::$start_peak_memory;
	}
	
	/**
	 * @return resource $fp
	 * @test DevblocksPlatformTest
	 */
	public static function getTempFile() {
		// Generate a new temporary file
		$file_name = tempnam(APP_TEMP_PATH, 'tmp');
		
		// Open the file pointer
		$fp = fopen($file_name, "w+b");
		
		// Manually keep track of these temporary files
		self::$_tmp_files[intval($fp)] = $file_name;
		return $fp;
	}
	
	/**
	 * @return string $filename
	 * @test DevblocksPlatformTest
	 */
	public static function getTempFileInfo($fp) {
		// If we're asking about a specific temporary file
		if(!empty($fp)) {
			if(@isset(self::$_tmp_files[intval($fp)]))
				return self::$_tmp_files[intval($fp)];
			return false;
		}
	}

	/**
	 * Checks whether the active database has any tables.
	 *
	 * @return boolean
	 */
	static function isDatabaseEmpty() {
		if(!(DevblocksPlatform::services()->database()))
			return true;
		
		$tables = self::getDatabaseTables();
		return empty($tables);
	}
	
	static function getDatabaseTables($nocache=false) {
		$cache = DevblocksPlatform::services()->cache();
		
		if($nocache || null === ($tables = $cache->load(self::CACHE_TABLES))) {
			// Make sure the database connection is valid or error out.
			if(!($db = DevblocksPlatform::services()->database()))
				return [];
			
			$tables = $db->metaTables();
			
			if(!$nocache && is_array($tables) && !empty($tables))
				$cache->save($tables, self::CACHE_TABLES);
		}
		
		return $tables;
	}

	/**
	 * Checks to see if the application needs to patch
	 *
	 * @return boolean
	 */
	static function versionConsistencyCheck() {
		if(!file_exists(APP_STORAGE_PATH . '/version.php'))
			return false;
			
		require_once(APP_STORAGE_PATH . '/version.php');
		
		if(defined('APP_BUILD_CACHED') && APP_BUILD_CACHED == APP_BUILD)
			return true;
		
		// If build changed, clear cache regardless of patch status
		$cache = DevblocksPlatform::services()->cache();
		$cache->clean();
		
		return false;
	}
	
	public static function logException(Throwable $e) {
		$orig_log_errors_max_len = ini_set('log_errors_max_len', 8192);
	
		error_log(sprintf("%s: %s [%s:%d]\n",
			get_class($e),
			$e->getMessage(),
			substr($e->getFile(), strlen(APP_PATH . DIRECTORY_SEPARATOR)),
			$e->getLine()
		));
		
		if($orig_log_errors_max_len)
			ini_set('log_errors_max_len', $orig_log_errors_max_len);
	}
	
	public static function logError($error_msg, $include_stacktrace=false, $allow_display=false) {
		if(extension_loaded('yaml')) {
            if(!is_string($error_msg) || !is_numeric($error_msg))
                $error_msg = yaml_emit($error_msg);
        }
		
		$orig_log_errors_max_len = ini_set('log_errors_max_len', 8192);
		
		error_log(rtrim($error_msg) . PHP_EOL);
		
		if($include_stacktrace) {
			error_log('Trace: ' . implode('; ', array_map(
				function($trace) {
					return sprintf("%s:%d (%s::%s)",
						substr($trace['file'] ?? null, strlen(APP_PATH)),
						$trace['line'] ?? -1,
						$trace['class'] ?? '',
						$trace['function'] ?? '',
					);
				},
				debug_backtrace()
			)) . PHP_EOL);
		}
		
		if($orig_log_errors_max_len)
			ini_set('log_errors_max_len', $orig_log_errors_max_len);
	}
	
	public static function noop() : void {
	}
	
	/**
	 *
	 * @return boolean
	 */
	static private function _needsToPatch() {
		$plugins = DevblocksPlatform::getPluginRegistry();
		
		// First install or upgrade
		if(empty($plugins))
			return true;

		foreach($plugins as $plugin) { /* @var $plugin DevblocksPluginManifest */
			if($plugin->enabled) {
				foreach($plugin->getPatches() as $patch) { /* @var $patch DevblocksPatch */
					if(!$patch->hasRun())
						return true;
				}
			}
		}
		
		return false;
	}
	
	/**
	 * Returns the list of extensions on a given extension point.
	 *
	 * @static
	 * @param string $point
	 * @return DevblocksExtensionManifest[]
	 */
	static function getExtensions($point, $as_instances=false, $sorted=true) {
		$results = [];
		$extensions = DevblocksPlatform::getExtensionRegistry();

		if(is_array($extensions))
		foreach($extensions as $extension) { /* @var $extension DevblocksExtensionManifest */
			if($extension->point == $point) {
				$results[$extension->id] = ($as_instances) ? $extension->createInstance() : $extension;
			}
		}
		
		if($sorted) {
			if($as_instances)
				DevblocksPlatform::sortObjects($results, 'manifest->name');
			else
				DevblocksPlatform::sortObjects($results, 'name');
		}
		
		return $results;
	}

	/**
	 * Returns the manifest of a given extension ID.
	 *
	 * @static
	 * @param string $extension_id
	 * @param boolean $as_instance
	 * @return DevblocksExtensionManifest
	 */
	static function getExtension($extension_id, $as_instance=false) {
		$extensions = DevblocksPlatform::getExtensionRegistry();
		
		if(!isset($extensions[$extension_id]))
			return null;
		
		$extension = $extensions[$extension_id];
		
		if($as_instance)
			return $extension->createInstance();
		
		return $extension;
	}

	/**
	 * Returns an array of all contributed extension manifests.
	 *
	 * @static
	 * @return DevblocksExtensionManifest[]
	 */
	static function getExtensionRegistry($nocache=false, $with_disabled=false) {
		$cache = DevblocksPlatform::services()->cache();
		
		// Forced
		if($with_disabled)
			$nocache = true;
		
		// Retrieve and cache
		if($nocache || null === ($extensions = $cache->load(self::CACHE_EXTENSIONS))) {
			$db = DevblocksPlatform::services()->database();
			if(is_null($db))
				return;
			
			$extensions = array();
			
			$sql = sprintf("SELECT e.id , e.plugin_id, e.point, e.pos, e.name , e.file , e.class, e.params ".
				"FROM cerb_extension e ".
				"INNER JOIN cerb_plugin p ON (e.plugin_id=p.id) ".
				"WHERE 1 ".
				"%s ".
				"ORDER BY e.plugin_id ASC, e.pos ASC",
					($with_disabled ? '' : 'AND p.enabled = 1')
				);
			
			if(false == ($results = $db->GetArrayMaster($sql)))
				return false;
				
			foreach($results as $row) {
				$extension = new DevblocksExtensionManifest();
				$extension->id = $row['id'];
				$extension->plugin_id = $row['plugin_id'];
				$extension->point = $row['point'];
				$extension->name = $row['name'];
				$extension->file = $row['file'];
				$extension->class = $row['class'];
				$extension->params = @unserialize($row['params']);
				
				if(empty($extension->params))
					$extension->params = array();
				$extensions[$extension->id] = $extension;
			}

			if(!$nocache)
				$cache->save($extensions, self::CACHE_EXTENSIONS);
		}
		
		return $extensions;
	}
	
	static function getExtensionPoints() {
		$extension_point_meta = [
			'cerb.automation.trigger' => [
				'id' => 'cerb.automation.trigger',
				'label' => 'Automation Trigger',
				'class' => 'Extension_AutomationTrigger',
				'examples' => [],
				'extensible' => true,
			],
			'cerb.resource.type' => [
				'id' => 'cerb.resource.type',
				'label' => 'Resource Type',
				'class' => 'Extension_ResourceType',
				'examples' => [],
				'extensible' => true,
			],
			'cerberusweb.plugin.setup' => [
				'label' => 'Plugin Setup',
				'class' => 'Extension_PluginSetup',
				'examples' => [],
			],
			'cerb.card.widget' => [
				'label' => 'Card Widget',
				'class' => 'Extension_CardWidget',
				'examples' => [],
				'extensible' => true,
			],
			'cerb.custom_field' => [
				'label' => 'Custom Field Type',
				'class' => 'Extension_CustomField',
				'examples' => [],
				'extensible' => true,
			],
			'cerb.portal' => [
				'label' => 'Portal',
				'class' => 'Extension_CommunityPortal',
				'examples' => [],
				'extensible' => true,
			],
			'cerb.portal.layout.widget' => [
				'label' => 'Portal Layout Widget',
				'class' => 'Extension_PortalLayoutWidget',
				'examples' => [],
				'extensible' => true,
			],
			'cerb.portal.page' => [
				'label' => 'Portal Page',
				'class' => 'Extension_PortalPage',
				'examples' => [],
				'extensible' => true,
			],
			'cerb.portal.widget' => [
				'label' => 'Portal Widget',
				'class' => 'Extension_PortalWidget',
				'examples' => [],
				'extensible' => true,
			],
			'cerb.profile.tab' => [
				'label' => 'Profile Tab Type',
				'class' => 'Extension_ProfileTab',
				'examples' => [],
				'extensible' => true,
			],
			'cerb.profile.tab.widget' => [
				'label' => 'Profile Widget Type',
				'class' => 'Extension_ProfileWidget',
				'examples' => [],
				'extensible' => true,
			],
			'cerb.connected_service.provider' => [
				'label' => 'Connected Service Provider',
				'class' => 'Extension_ConnectedServiceProvider',
				'examples' => [],
				'extensible' => true,
			],
			'cerberusweb.calendar.datasource' => [
				'label' => 'Calendar Datasource',
				'class' => 'Extension_CalendarDatasource',
				'examples' => [],
			],
			'cerberusweb.cron' => [
				'label' => 'Scheduled Job',
				'class' => 'CerberusCronPageExtension',
				'examples' => [],
			],
			'cerberusweb.datacenter.sensor' => [
				'label' => 'Sensor Type',
				'class' => 'Extension_Sensor',
				'examples' => [],
			],
			'cerberusweb.mail.transport' => [
				'label' => 'Mail Transport Type',
				'class' => 'Extension_MailTransport',
				'examples' => [],
				'extensible' => true,
			],
			'cerberusweb.page' => [
				'label' => 'Page Type',
				'class' => 'CerberusPageExtension',
				'examples' => [],
			],
			'cerberusweb.renderer.prebody' => [
				'label' => 'Prebody Renderer',
				'class' => 'Extension_AppPreBodyRenderer',
				'examples' => [],
			],
			'cerberusweb.rest.controller' => [
				'label' => 'Rest API Controller',
				'class' => 'Extension_RestController',
				'examples' => [],
			],
			'cerberusweb.ui.page.menu.item' => [
				'label' => 'Page Menu Item',
				'class' => 'Extension_PageMenuItem',
				'examples' => [],
			],
			'cerberusweb.ui.page.section' => [
				'label' => 'Page Section',
				'class' => 'Extension_PageSection',
				'examples' => [],
			],
			'cerberusweb.ui.workspace.page' => [
				'label' => 'Workspace Page Type',
				'class' => 'Extension_WorkspacePage',
				'examples' => [],
				'extensible' => true,
			],
			'cerberusweb.ui.workspace.tab' => [
				'label' => 'Workspace Tab Type',
				'class' => 'Extension_WorkspaceTab',
				'examples' => [],
				'extensible' => true,
			],
			'cerberusweb.ui.workspace.widget' => [
				'label' => 'Workspace Widget Type',
				'class' => 'Extension_WorkspaceWidget',
				'examples' => [],
				'extensible' => true,
			],
			'cerberusweb.ui.workspace.widget.datasource' => [
				'label' => 'Workspace Widget Datasource',
				'class' => 'Extension_WorkspaceWidgetDatasource',
				'examples' => [],
				'extensible' => true,
			],
			'devblocks.cache.engine' => [
				'label' => 'Cache Engine',
				'class' => 'Extension_DevblocksCacheEngine',
				'examples' => [],
				'extensible' => true,
			],
			'devblocks.context' => [
				'label' => 'Record Type',
				'class' => 'Extension_DevblocksContext',
				'examples' => [],
			],
			'devblocks.controller' => [
				'label' => 'Controller',
				'class' => 'DevblocksControllerExtension',
				'examples' => [],
			],
			'devblocks.event' => [
				'label' => 'Bot Event',
				'class' => 'Extension_DevblocksEvent',
				'examples' => [],
			],
			'devblocks.event.action' => [
				'label' => 'Bot Action',
				'class' => 'Extension_DevblocksEventAction',
				'examples' => [],
			],
			'devblocks.listener.event' => [
				'label' => 'Event Listener',
				'class' => 'DevblocksEventListenerExtension',
				'examples' => [],
			],
			'devblocks.listener.http' => [
				'label' => 'Http Request Listener',
				'class' => 'DevblocksHttpResponseListenerExtension',
				'examples' => [],
			],
			'devblocks.search.engine' => [
				'label' => 'Search Engine',
				'class' => 'Extension_DevblocksSearchEngine',
				'examples' => [],
			],
			'devblocks.search.schema' => [
				'label' => 'Search Schema',
				'class' => 'Extension_DevblocksSearchSchema',
				'examples' => [],
			],
			'devblocks.storage.engine' => [
				'label' => 'Storage Engine',
				'class' => 'Extension_DevblocksStorageEngine',
				'examples' => [],
			],
			'devblocks.storage.schema' => [
				'label' => 'Storage Schema',
				'class' => 'Extension_DevblocksStorageSchema',
				'examples' => [],
			],
			'usermeet.login.authenticator' => [
				'label' => 'Support Center Login Authenticator',
				'class' => 'Extension_ScLoginAuthenticator',
				'examples' => [],
			],
			'usermeet.sc.controller' => [
				'label' => 'Support Center Controller',
				'class' => 'Extension_UmScController',
				'examples' => [],
			],
			'usermeet.sc.rss.controller' => [
				'label' => 'Support Center RSS Feed',
				'class' => 'Extension_UmScRssController',
				'examples' => [],
			],
		];
		
		DevblocksPlatform::sortObjects($extension_point_meta, '[label]');
		
		return $extension_point_meta;
	}	

	static function getActivityPointRegistry() {
		$cache = DevblocksPlatform::services()->cache();
		$plugins = DevblocksPlatform::getPluginRegistry();
		
		if(empty($plugins))
			return [];
			
		if(null !== ($activities = $cache->load(self::CACHE_ACTIVITY_POINTS)))
			return $activities;
			
		$activities = [];
			
		foreach($plugins as $plugin) { /* @var $plugin DevblocksPluginManifest */
			if($plugin->enabled)
			foreach($plugin->getActivityPoints() as $point => $data) {
				$activities[$point] = $data;
			}
		}
		
		DevblocksPlatform::sortObjects($activities, '[params]->[label_key]');
		
		$cache->save($activities, self::CACHE_ACTIVITY_POINTS);
		return $activities;
	}
	
	/**
	 * @return DevblocksEventPoint[]
	 */
	static function getEventPointRegistry() {
		$cache = DevblocksPlatform::services()->cache();
		if(null !== ($events = $cache->load(self::CACHE_EVENT_POINTS)))
			return $events;

		$events = array();
		$plugins = self::getPluginRegistry();
		
		// [JAS]: Event point hashing/caching
		if(is_array($plugins))
		foreach($plugins as $plugin) { /* @var $plugin DevblocksPluginManifest */
			$events = array_merge($events,$plugin->event_points);
		}
		
		if(!empty($events))
			$cache->save($events, self::CACHE_EVENT_POINTS);
		
		return $events;
	}
	
	/**
	 * @return DevblocksAclPrivilege[]
	 */
	static function getAclRegistry() {
		$cache = DevblocksPlatform::services()->cache();
		if(null !== ($acl = $cache->load(self::CACHE_ACL)))
			return $acl;

		$acl = array();

		$db = DevblocksPlatform::services()->database();
		if(is_null($db)) return;

		//$plugins = self::getPluginRegistry();

		$sql = "SELECT a.id, a.plugin_id, a.label ".
			"FROM cerb_acl a ".
			"INNER JOIN cerb_plugin p ON (a.plugin_id=p.id) ".
			"WHERE p.enabled = 1 ".
			"ORDER BY a.plugin_id, a.id ASC"
			;
		
		if(false == ($results = $db->GetArrayMaster($sql)))
			return false;
		
		foreach($results as $row) {
			$priv = new DevblocksAclPrivilege();
			$priv->id = $row['id'];
			$priv->plugin_id = $row['plugin_id'];
			$priv->label = $row['label'];
			
			$acl[$priv->id] = $priv;
		}
		
		$cache->save($acl, self::CACHE_ACL);
		return $acl;
	}
	
	static function getEventRegistry() {
		$cache = DevblocksPlatform::services()->cache();
		if(null !== ($events = $cache->load(self::CACHE_EVENTS)))
			return $events;
		
		$extensions = self::getExtensions('devblocks.listener.event',false);
		$events = array('*');
		
		// [JAS]: Event point hashing/caching
		if(is_array($extensions))
		foreach($extensions as $extension) { /* @var $extension DevblocksExtensionManifest */
			$evts = $extension->params['events'][0] ?? null;
			
			// Global listeners (every point)
			if(empty($evts) && !is_array($evts)) {
				$events['*'][] = $extension->id;
				continue;
			}
			
			if(is_array($evts))
			foreach(array_keys($evts) as $evt) {
				$events[$evt][] = $extension->id;
			}
		}
		
		if(is_array($events) && !empty($events))
			$cache->save($events, self::CACHE_EVENTS);
		
		return $events;
	}
	
	/**
	 * Returns an array of all contributed plugin manifests.
	 *
	 * @static
	 * @return DevblocksPluginManifest[]
	 */
	static function getPluginRegistry() {
		$cache = DevblocksPlatform::services()->cache();
		
		if(null !== ($plugins = $cache->load(self::CACHE_PLUGINS)))
			return $plugins;
		
		if(false == ($db = DevblocksPlatform::services()->database()) || DevblocksPlatform::isDatabaseEmpty())
			return;
			
		$plugins = [];
			
		$sql = "SELECT p.* ".
			"FROM cerb_plugin p ".
			"ORDER BY p.enabled DESC, p.name ASC "
			;
		$results = $db->GetArrayMaster($sql);

		foreach($results as $row) {
			$plugin = new DevblocksPluginManifest();
			$plugin->id = $row['id'] ?? null;
			$plugin->enabled = intval($row['enabled'] ?? null);
			$plugin->name = $row['name'] ?? null;
			$plugin->description = $row['description'] ?? null;
			$plugin->author = $row['author'] ?? null;
			$plugin->version = intval($row['version'] ?? null);
			$plugin->link = $row['link'] ?? null;
			$plugin->dir = $row['dir'] ?? null;

			// JSON decode
			if(isset($row['manifest_cache_json'])
				&& null != ($manifest_cache_json = $row['manifest_cache_json'])) {
				$plugin->manifest_cache = json_decode($manifest_cache_json, true);
			}

			if(file_exists($plugin->getStoragePath() . '/' . 'plugin.xml'))
				$plugins[$plugin->id] = $plugin;
		}

		$sql = "SELECT p.id, p.name, p.params, p.plugin_id ".
			"FROM cerb_event_point p "
			;
		$results = $db->GetArrayMaster($sql);

		foreach($results as $row) {
			$point = new DevblocksEventPoint();
			$point->id = $row['id'];
			$point->name = $row['name'];
			$point->plugin_id = $row['plugin_id'];
			
			$params = $row['params'];
			$point->params = !empty($params) ? unserialize($params) : array();

			if(isset($plugins[$point->plugin_id])) {
				$plugins[$point->plugin_id]->event_points[$point->id] = $point;
			}
		}
		
		self::_sortPluginsByDependency($plugins);
		
		if(is_array($plugins) && !empty($plugins))
			$cache->save($plugins, self::CACHE_PLUGINS);
		
		return $plugins;
	}
	
	static public function isPluginEnabled($plugin_id) {
		if(null != ($plugin = self::getPlugin($plugin_id))) {
			return $plugin->enabled;
		};
		return false;
	}
	
	static private function _sortPluginsByDependency(&$plugins) {
		$dependencies = array();
		$seen = array();
		$order = array();
		
		// Dependencies
		foreach($plugins as $plugin) {
			$deps = $plugin->manifest_cache['dependencies'] ?? null;
			$dependencies[$plugin->id] = is_array($deps) ? $deps : array();
		}
		
		if(is_array($plugins))
		foreach($plugins as $plugin)
			self::_recursiveDependency($plugin->id, $dependencies, $seen, $order);

		$original = $plugins;
		$plugins = array();
			
		if(is_array($order))
		foreach($order as $order_id) {
			if(!isset($original[$order_id]))
				continue;
			
			$plugins[$order_id] = $original[$order_id];
		}
	}

	static private function _recursiveDependency($id, $deps, &$seen, &$order, $level=0) {
		if(isset($seen[$id]))
			return true;
	
		if(isset($deps[$id]) && !empty($deps[$id])) {
			foreach($deps[$id] as $dep) {
				if(!self::_recursiveDependency($dep, $deps, $seen, $order, ++$level))
					return false;
			}
		}
		
		if(!isset($seen[$id])) {
			$order[] = $id;
			$seen[$id] = true;
		}
		
		return true;
	}
	
	/**
	 *
	 * @param string $id
	 * @return DevblocksPluginManifest
	 */
	static function getPlugin($id) {
		$plugins = DevblocksPlatform::getPluginRegistry();

		if(isset($plugins[$id]))
			return $plugins[$id];
		
		return null;
	}

	/**
	 * Reads and caches manifests from the features + plugins directories.
	 *
	 * @static
	 * @return DevblocksPluginManifest[]
	 */
	static function readPlugins($is_update=true, array $only_scan_dirs = []) {
		$plugins = [];
		
		$scan_dirs = ['features','plugins','storage/plugins'];
		
		if($only_scan_dirs)
			$scan_dirs = array_values(array_intersect($scan_dirs, $only_scan_dirs));
		
		// Devblocks
		if(null !== ($manifest = self::_readPluginManifest(DEVBLOCKS_PATH, $is_update)))
			$plugins[] = $manifest;
		
		// Application
		if(is_array($scan_dirs))
		foreach($scan_dirs as $scan_dir) {
			switch($scan_dir) {
				case 'features':
					$scan_path = APP_PATH . '/features';
					break;
					
				case 'plugins':
					$scan_path = APP_PATH . '/plugins';
					break;
					
				case 'storage/plugins':
					$scan_path = APP_STORAGE_PATH . '/plugins';
					break;
					
				default:
					continue 2;
			}
			
			if(is_dir($scan_path)) {
				if($dh = opendir($scan_path)) {
					while (($file = readdir($dh)) !== false) {
						if($file=="." || $file == "..")
							continue;
							
						$plugin_path = $scan_path . '/' . $file;
						
						if(is_dir($plugin_path) && file_exists($plugin_path . '/plugin.xml')) {
							$manifest = self::_readPluginManifest($plugin_path, $is_update); /* @var $manifest DevblocksPluginManifest */
							
							if(null != $manifest) {
								$plugins[$manifest->id] = $manifest;
							}
						}
					}
					closedir($dh);
				}
			}
		}
		
		DevblocksPlatform::clearCache(DevblocksPlatform::CACHE_PLUGINS);
		DevblocksPlatform::services()->classloader()->destroy();
		
		return $plugins;
	}

	/**
	 * @return _DevblocksServices
	 */
	static function services() {
		return _DevblocksServices::getInstance();
	}
	
	static function getPluginSetting($plugin_id, $key, $default=null, $json_decode=false, $encrypted=false) {
		$settings = DevblocksPlatform::services()->pluginSettings();
		return $settings->get($plugin_id, $key, $default, $json_decode, $encrypted);
	}
	
	static function setPluginSetting($plugin_id, $key, $value, $json_encode=false, $encrypted=false) {
		$settings = DevblocksPlatform::services()->pluginSettings();
		return $settings->set($plugin_id, $key, $value, $json_encode, $encrypted);
	}
	
	static function setRegistryKey($key, $value, $as=DevblocksRegistryEntry::TYPE_STRING, $persist=false, $expires_at=0) {
		$registry = DevblocksPlatform::services()->registry();
		$registry->set($key, $value, $as);
		$registry->persist($key, $persist, $expires_at);
	}
	
	static function getRegistryKey($key, $as=DevblocksRegistryEntry::TYPE_STRING, $default=null) {
		$registry = DevblocksPlatform::services()->registry();
		
		if(null == ($value = $registry->get($key, $as, $default)))
			return null;
		
		return $value;
	}
	
	static private function _deepCloneArray(&$array) {
		if(is_array($array))
		foreach($array as &$element) {
			// Recurse if needed
			if(is_array($element)) {
				self::_deepCloneArray($element);
				
			} else if(is_object($element)) {
				$element = clone $element;
			}
		}
	}
	
	static function deepCloneArray($array) {
		$copy = $array;
		self::_deepCloneArray($copy);
		return $copy;
	}
	
	static function extractArrayValues($array, $key, $only_unique=true, array $ignore=[]) {
		if(empty($key) || !is_array($array))
			return array();
		
		// Convert any nested objects to arrays
		foreach($array as $k => $v) {
			if(is_object($v))
				$array[$k] = json_decode(json_encode($v), true);
		}
		
		$results = [];
		
		array_walk_recursive($array, function($v, $k) use ($key, &$results) {
			if(0 == strcasecmp($key, $k))
				$results[] = $v;
		});
		
		if($only_unique)
			$results = array_unique($results);
		
		if($ignore)
			$results = array_diff($results, $ignore);
		
		return array_values($results);
	}
	
	/**
	 * 
	 * @param array $array
	 * @param string $type
	 * @param array $options
	 * @return mixed
	 * @test DevblocksPlatformTest
	 */
	static function sanitizeArray($array, string $type, array $options=[]) {
		if(!is_array($array))
			return [];
		
		switch($type) {
			case 'bit':
				return _DevblocksSanitizationManager::arrayAs($array, 'bit');
				
			case 'bool':
			case 'boolean':
				return _DevblocksSanitizationManager::arrayAs($array, 'boolean');
				
			case 'decimal':
			case 'float':
				return _DevblocksSanitizationManager::arrayAs($array, 'float');
				
			case 'int':
			case 'integer':
				$array = _DevblocksSanitizationManager::arrayAs($array, 'integer');
				
				if(is_array($options) && in_array('nonzero', $options)) {
					foreach($array as $k => $v) {
						if(empty($v))
							unset($array[$k]);
					}
				}
				
				if(in_array('unique', $options)) {
					$array = array_unique($array);
				}
				
				return $array;
				
			case 'str':
			case 'string':
				return _DevblocksSanitizationManager::arrayAs($array, 'string');
				
			default:
				break;
		}
		
		return false;
	}
	
	/**
	 * 
	 * @param array $array
	 * @param string $on
	 * @param boolean $ascending
	 * @test DevblocksPlatformTest
	 */
	static function sortObjects(&$array, $on, $ascending=true) {
		_DevblocksSortHelper::sortObjects($array, $on, $ascending);
	}
	
	/**
	 * @param $profile_id | $extension_id, $options
	 * @return Extension_DevblocksStorageEngine|false
	 */
	static function getStorageService() {
		$args = func_get_args();

		if(empty($args))
			return false;
		
		$profile = $args[0];
		$params = [];
		
		// Handle $profile polymorphism
		if($profile instanceof Model_DevblocksStorageProfile) {
			$extension = $profile->extension_id;
			$params['_profile_id'] = $profile->id;
			
			if(is_array($profile->params))
				$params = array_merge($params, $profile->params);
			
		} else if(is_numeric($profile)) {
			$storage_profile = DAO_DevblocksStorageProfile::get($profile);
			$extension = $storage_profile->extension_id;
			$params['_profile_id'] = $storage_profile->id;
			
			if(is_array($storage_profile->params))
				$params = array_merge($params, $storage_profile->params);
			
		} else if(is_string($profile)) {
			$extension = $profile;
			$params['_profile_id'] = 0;
			
			if(isset($args[1]) && is_array($args[1]))
				$params = array_merge($params, $args[1]);
			
		} else {
			return false;
		}
		
		return _DevblocksStorageManager::getEngine($extension, $params);
	}

	/**
	 *
	 * @param string $set
	 * @return DevblocksTemplate[]
	 */
	static function getTemplates($set=null) {
		$templates = array();
		$plugins = self::getPluginRegistry();
		
		if(is_array($plugins))
		foreach($plugins as $plugin) {
			if(isset($plugin->manifest_cache['templates']) && is_array($plugin->manifest_cache['templates']))
			foreach($plugin->manifest_cache['templates'] as $tpl) {
				if(null === $set || 0 == strcasecmp($set, $tpl['set'])) {
					$template = new DevblocksTemplate();
					$template->plugin_id = $tpl['plugin_id'];
					$template->set = $tpl['set'];
					$template->path = $tpl['path'];
					$template->sort_key = $tpl['plugin_id'] . ' ' . $tpl['path'];
					$templates[] = $template;
				}
			}
		}
		
		return $templates;
	}
	
	private static function _discoverTimezone() {
		$timezone = null;
		
		// Try worker session
		if(empty($timezone) && isset($_SESSION['timezone']))
			$timezone = $_SESSION['timezone'];
		
		// Try app default
		if(empty($timezone))
			$timezone = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::TIMEZONE, null);
		
		// Try system timezone
		if(empty($timezone))
			@$timezone = date_default_timezone_get();
		
		// Otherwise, use UTC
		if(empty($timezone))
			$timezone = 'UTC';
		
		return $timezone;
	}

	static function getTimezone() {
		if(!empty(self::$timezone))
			return self::$timezone;
		
		return @date_default_timezone_get();
	}
	
	static function setTimezone($timezone=null) {
		if(empty($timezone))
			$timezone = self::_discoverTimezone();
		
		self::$timezone = $timezone;
		@date_default_timezone_set(self::$timezone);
		return self::$timezone;
	}
	
	/**
	 * 
	 * @param string $locale
	 * @test DevblocksPlatformTest
	 */
	static function setLocale($locale) {
		@setlocale(LC_ALL, $locale);
		self::$locale = $locale;
	}
	
	/**
	 * @test DevblocksPlatformTest
	 */
	static function getLocale() {
		if(!empty(self::$locale))
			return self::$locale;
			
		return 'en_US';
	}
	
	/**
	 * @return string
	 * @test DevblocksPlatformTest
	 */
	static function getDateTimeFormat() {
		return self::$dateTimeFormat;
	}
	
	/**
	 * 
	 * @param string $time_format
	 * @test DevblocksPlatformTest
	 */
	static function setDateTimeFormat($time_format) {
		self::$dateTimeFormat = $time_format;
	}
	
	/**
	 * @return _DevblocksTranslationManager
	 */
	static function getTranslationService() {
		static $languages = array();
		$locale = DevblocksPlatform::getLocale();

		// Registry
		if(isset($languages[$locale])) {
			return $languages[$locale];
		}

		$cache = DevblocksPlatform::services()->cache();
		
		if(null === ($map = $cache->load(self::CACHE_TAG_TRANSLATIONS.'_'.$locale))) { /* @var $cache _DevblocksCacheManager */
			$map = array();
			$map_en = DAO_Translation::getMapByLang('en_US');
			if(0 != strcasecmp('en_US', $locale))
				$map_loc = DAO_Translation::getMapByLang($locale);
			
			// Loop through the English string objects
			if(is_array($map_en))
			foreach($map_en as $string_id => $obj_string_en) {
				$string = '';
				
				// If we have a locale to check
				if(isset($map_loc) && is_array($map_loc)) {
					@$obj_string_loc = $map_loc[$string_id];
					@$string =
						(!empty($obj_string_loc->string_override))
						? $obj_string_loc->string_override
						: $obj_string_loc->string_default;
				}
				
				// If we didn't hit, load the English default
				if(empty($string))
				@$string =
					(!empty($obj_string_en->string_override))
					? $obj_string_en->string_override
					: $obj_string_en->string_default;
					
				// If we found any match
				if(!empty($string))
					$map[$string_id] = $string;
			}
			unset($obj_string_en);
			unset($obj_string_loc);
			unset($map_en);
			unset($map_loc);
			
			// Cache with tag (tag allows easy clean for multiple langs at once)
			if(is_array($map) && !empty($map))
				$cache->save($map,self::CACHE_TAG_TRANSLATIONS.'_'.$locale);
		}
		
		$translate = _DevblocksTranslationManager::getInstance();
		$translate->addLocale($locale, $map);
		$translate->setLocale($locale);
		
		$languages[$locale] = $translate;

		return $translate;
	}

	/**
	 *
	 * @return DevblocksHttpRequest
	 */
	static function getHttpRequest() {
		return self::$request;
	}

	/**
	 * @param DevblocksHttpRequest $request
	 */
	static function setHttpRequest(DevblocksHttpRequest $request) {
		self::$request = $request;
	}

	/**
	 *
	 * @return DevblocksHttpRequest
	 */
	static function getHttpResponse() {
		return self::$response;
	}

	/**
	 * @param DevblocksHttpResponse $response
	 */
	static function setHttpResponse(DevblocksHttpResponse $response) {
		self::$response = $response;
	}
	
	static function getHttpHeaders() {
		$headers = [];
		
		foreach($_SERVER as $k => $v) {
			if('HTTP_' == substr($k, 0, 5)) {
				$headers[DevblocksPlatform::strLower(substr($k, 5))] = $v;
			}
		}
		
		return $headers;
	}
	
	static function getHttpMethod() {
		return DevblocksPlatform::strUpper($_SERVER['REQUEST_METHOD'] ?? '');
	}
	
	static function getHttpParams() {
		$params = [];
		
		foreach($_GET as $k => $v) {
			$params[DevblocksPlatform::strLower(DevblocksPlatform::strAlphaNum(str_replace('-', '_', $k), '_', ''))] = $v;
		}
		foreach($_POST as $k => $v) {
			$params[DevblocksPlatform::strLower(DevblocksPlatform::strAlphaNum(str_replace('-', '_', $k), '_', ''))] = $v;
		}
		
		return $params;
	}
	
	static function getHttpBody() {
		$contents = "";
		
		$body_data = fopen("php://input" , "rb");
		while(!feof($body_data))
			$contents .= fread($body_data, 4096);
		fclose($body_data);
		
		return $contents;
	}
	
	static function isStateless() {
		return self::$is_stateless;
	}
	
	static function setStateless($bool) {
		self::$is_stateless = $bool;
	}
	
	static function getLastError() {
		return self::$_error_last;
	}
	
	static function errorHandler(int $errno=0, string $errstr=null, string $errfile=null, int $errline=null, array $errcontext=[]) : bool {
		// Suppress if we're not reporting at this level in production
		if(!DEVELOPMENT_MODE && 0 == (error_reporting() & $errno)) {
			return true;
		}
		
		// Suppress deprecation warnings in production
		if(
			!DEVELOPMENT_MODE
			&& (0 == ((E_ALL & ~E_DEPRECATED) & $errno)) 
		) {
			return true;
		}
		
		// Temporarily ignore Smarty errors in production
		if(
			!DEVELOPMENT_MODE
			&& false !== stristr($errfile, '/templates_c/')
			&& (0 == ((E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE) & $errno))
			) {
			return true;
		}
		
		// Ignore warnings/notices from dependencies in production
		if(
			!DEVELOPMENT_MODE 
			&& false !== stristr($errfile, '/vendor/')
			&& (0 == ((E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE) & $errno))
			) {
			return true;
		}
		
		if(
			!DEVELOPMENT_MODE
			&& E_WARNING == $errno
			&& $errstr == 'yaml_emit(): Invalid UTF-8 sequence in argument'
		) {
			return true;
		}
		
		self::$_error_last = [
			'type' => $errno,
			'message' => $errstr,
			'file' => $errfile,
			'line' => $errline
		];
		
		error_log(sprintf("[%d] %s %s %d (%d)\n", $errno, $errstr, $errfile, $errline, error_reporting()));
		
		return true;
	}

	/**
	 * Initializes the plugin platform (paths, etc).
	 *
	 * @static
	 * @return void
	 */
	static function init() {
		self::$start_time = microtime(true);
		if(function_exists('memory_get_usage') && function_exists('memory_get_peak_usage')) {
			self::$start_memory = memory_get_usage();
			self::$start_peak_memory = memory_get_peak_usage();
		}

		set_error_handler(['DevblocksPlatform','errorHandler']);	

		// Security
		$app_security_frameoptions = @strtolower(APP_SECURITY_FRAMEOPTIONS);
		
		if(php_sapi_name() != 'cli' && !headers_sent())
		switch($app_security_frameoptions) {
			case 'none':
				break;
				
			case 'deny':
				header("X-Frame-Options: DENY");
				break;
				
			default:
			case 'self':
				header("X-Frame-Options: SAMEORIGIN");
				break;
		}
		
		// Encoding (mbstring)
		mb_internal_encoding(LANG_CHARSET_CODE);
		if(function_exists('mb_regex_encoding'))
			mb_regex_encoding(LANG_CHARSET_CODE);
		
		// [JAS] [MDF]: Automatically determine the relative webpath to Devblocks files
		//$proxyhost = $_SERVER['HTTP_DEVBLOCKSPROXYHOST'] ?? null;
		$proxybase = $_SERVER['HTTP_DEVBLOCKSPROXYBASE'] ?? null;
	
		// App path (always backend)
	
		$app_self = $_SERVER["SCRIPT_NAME"];
		
		if(DEVBLOCKS_REWRITE) {
			$pos = strrpos($app_self,'/');
			$app_self = substr($app_self,0,$pos) . '/';
		} else {
			$pos = strrpos($app_self,'index.php');
			if(false === $pos) $pos = strrpos($app_self,'ajax.php');
			$app_self = substr($app_self,0,$pos);
		}
		
		// Context path (abstracted: proxies or backend)
		
		if(!empty($proxybase)) { // proxy
			$context_self = $proxybase . '/';
		} else { // non-proxy
			$context_self = $app_self;
		}
		
		@define('DEVBLOCKS_WEBPATH',$context_self);
		@define('DEVBLOCKS_APP_WEBPATH',$app_self);
		
		// Enable the second-level cache
		
		$cache = DevblocksPlatform::services()->cache();
		
		if(false != ($cacher_extension_id = DevblocksPlatform::getPluginSetting('devblocks.core', 'cacher.extension_id', null))) {
			$cacher_params = DevblocksPlatform::getPluginSetting('devblocks.core', 'cacher.params_json', array(), true);
			$cache->setEngine($cacher_extension_id, $cacher_params);
		}
		
		// Register shutdown function
		register_shutdown_function(array('DevblocksPlatform','shutdown'));
	}
	
	static function shutdown() : void {
		// Trigger changed context events
		Extension_DevblocksContext::flushTriggerChangedContextsEvents();
		
		if(class_exists('CerberusContexts'))
			CerberusContexts::flush();
		
		// Clean up any temporary files
		while(null != ($tmpfile = array_pop(self::$_tmp_files))) {
			if(file_exists($tmpfile))
				unlink($tmpfile);
		}
		
		// Persist the registry
		$registry = DevblocksPlatform::services()->registry();
		$registry->save();
		
		// Publish aggregated metrics
		$metrics = DevblocksPlatform::services()->metrics();
		$metrics->publish();
	}

	static function setHandlerSession($class) : void {
		if(!empty($class) && class_exists($class, true))
			self::$handlerSession = $class;
	}
	
	static function getHandlerSession() {
		return self::$handlerSession;
	}
	
	static function redirect(DevblocksHttpIO $httpIO, $wait_secs=0) : never {
		$url_service = DevblocksPlatform::services()->url();
		session_write_close();
		$url = $url_service->writeDevblocksHttpIO($httpIO, true);
		header('Location: '.$url);
		
		if($wait_secs)
			sleep($wait_secs);
			
		DevblocksPlatform::exit(302);
	}
	
	static function redirectURL($url, $wait_secs=0) : never {
		if(empty($url)) {
			$url_service = DevblocksPlatform::services()->url();
			$url = $url_service->writeNoProxy('', true);
		}
		session_write_close();
		header('Location: '.$url);
		
		if($wait_secs)
			sleep($wait_secs);
		
		DevblocksPlatform::exit(302);
	}
	
	static function exit(int $status_code=200) : never {
		if($status_code && php_sapi_name() != 'cli')
			http_response_code($status_code);
		
		exit;
	}
	
	static function dieWithHttpError($message, $status_code=500) : never {
		$message = DevblocksPlatform::strEscapeHtml($message);
		self::dieWithHttpErrorRaw($message, $status_code);
	}
	
	static function dieWithHttpErrorHtml($message, $status_code=500) : never {
		self::dieWithHttpErrorRaw($message, $status_code);
	}
	
	static function dieWithHttpErrorRaw($message, $status_code=500) : never {
		if(php_sapi_name() != 'cli') {
			switch ($status_code) {
				case 403: // Forbidden
				case 404: // Not found
				case 500: // Internal server error
				case 501: // Not implemented
				case 503: // Service unavailable
					http_response_code($status_code);
					break;
				
				default:
					http_response_code($status_code);
					break;
			}
		}
		
		die($message);
	}
	
	static function markContextChanged($context, $context_ids) {
		if(empty($context_ids))
			return;
		
		if(!is_array($context_ids))
			$context_ids = [$context_ids];
		
		Extension_DevblocksContext::markContextChanged($context, $context_ids);
	}
};

class DevblocksException extends Exception {
};

function devblocks_autoload($className) {
	$classloader = _DevblocksClassLoadManager::getInstance();
	return $classloader->loadClass($className);
}

// Register Devblocks class loader
spl_autoload_register('devblocks_autoload');