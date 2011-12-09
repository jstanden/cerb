<?php
include_once(DEVBLOCKS_PATH . "api/Engine.php");

include_once(DEVBLOCKS_PATH . "api/services/bootstrap/cache.php");
include_once(DEVBLOCKS_PATH . "api/services/bootstrap/database.php");
include_once(DEVBLOCKS_PATH . "api/services/bootstrap/classloader.php");

define('PLATFORM_BUILD',2011120601);

/**
 * A platform container for plugin/extension registries.
 *
 * @author Jeff Standen <jeff@webgroupmedia.com>
 */
class DevblocksPlatform extends DevblocksEngine {
    private function __construct() { return false; }

    static function installPluginZip($zip_filename) {
		// [TODO] Check write access in storage/plugins/
		
		// Unzip (Devblocks ZipArchive or pclzip)
    	if(extension_loaded('zip')) {
			$zip = new ZipArchive();
			$result = $zip->open($zip_filename);
			
			// Read the plugin.xml file
			for($i=0;$i<$zip->numFiles;$i++) {
				$path = $zip->getNameIndex($i);
				if(preg_match("#/plugin.xml$#", $path)) {
					$manifest_fp = $zip->getStream($path);
					$manifest_data = stream_get_contents($manifest_fp);
					fclose($manifest_fp);
					$xml = simplexml_load_string($manifest_data);
					$plugin_id = (string) $xml->id;
					//[TODO] Check version info
				}
			}
			
			$zip->extractTo(APP_STORAGE_PATH . '/plugins/');
	
		} else {
			$zip = new PclZip($zip_filename);
			
			$contents = $zip->extract(PCLZIP_OPT_BY_PREG, "#/plugin.xml$#", PCLZIP_OPT_EXTRACT_AS_STRING);
			$manifest_data = $contents[0]['content'];
			
			$xml = simplexml_load_string($manifest_data);
			$plugin_id = (string) $xml->id;
			
			$list = $zip->extract(PCLZIP_OPT_PATH, APP_STORAGE_PATH . '/plugins/');
    	}
		
    	if(empty($plugin_id))
    		return false;
    	
		DevblocksPlatform::readPlugins();
		$plugin = DevblocksPlatform::getPlugin($plugin_id);
		$plugin->setEnabled(true);
		
		DevblocksPlatform::clearCache();
		return true;
    }
    
    static function installPluginZipFromUrl($url) {
		if(!extension_loaded('curl'))
			return;
		
		$fp = DevblocksPlatform::getTempFile();
		$fp_filename = DevblocksPlatform::getTempFileInfo($fp);
		
		$ch = curl_init($url);
		curl_setopt_array($ch, array(
			CURLOPT_CUSTOMREQUEST => 'GET',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			//CURLOPT_FILE => $fp,
		));
		$data = curl_exec($ch);
		
		// [TODO] Check status
		//$info = curl_getinfo($ch);
		//var_dump($info); 
		//$status = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
		
		// Write
		fwrite($fp, $data, strlen($data));
		fclose($fp);
		curl_close($ch);
		
		return self::installPluginZip($fp_filename);
    }
    
    static function uninstallPlugin($plugin_id) {
		// [TODO] Verify the plugin from registry
		// [TODO] Only uninstall from storage/plugins/
		
		$plugin = DevblocksPlatform::getPlugin($plugin_id);
		$plugin->setEnabled(false);
		$plugin->uninstall();
		DevblocksPlatform::readPlugins();
    }
    
	/**
	 * @param mixed $value
	 * @param string $type
	 * @param mixed $default
	 * @return mixed
	 */
	static function importVar($value,$type=null,$default=null) {
		if(is_null($value) && !is_null($default))
			$value = $default;
		
		// Sanitize input
		switch($type) {
			case 'array':
				@settype($value,$type);
				break;
			case 'bit':
				$value = !empty($value) ? 1 : 0;
				break;
			case 'boolean':
				$value = !empty($value) ? true : false;
				break;
			case 'float':
				$value = floatval($value);
				break;
			case 'integer':
				$value = intval($value);
				break;
			case 'string':
				$value = (string) $value;
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
				@settype($value,$type);
				break;
		}
		
		return $value;		
	}    
    
	/**
	 * @param mixed $var
	 * @param string $cast
	 * @param mixed $default
	 * @return mixed
	 */
	static function importGPC($var,$cast=null,$default=null) {
		@$magic_quotes = (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) ? true : false;

		if(!is_null($var)) {
	        if(is_string($var)) {
	            $var = $magic_quotes ? stripslashes($var) : $var;
	        } elseif(is_array($var)) {
	        	if($magic_quotes)
	        		array_walk_recursive($var, create_function('&$item, $key','if(!is_array($item)) $item = stripslashes($item);'));
	        }
	        
	    } elseif (is_null($var) && !is_null($default)) {
	        $var = $default;
	    }

	    if(!is_null($cast))
	    	$var = self::importVar($var, $cast, $default);
	    
	    return $var;
	}

	/**
	 * Returns a string as a regexp. 
	 * "*bob" returns "/(.*?)bob/".
	 */
	static function parseStringAsRegExp($string) {
		$pattern = str_replace(array('*'),'__any__', $string);
		$pattern = sprintf("/%s/i",str_replace(array('__any__'),'(.*?)', preg_quote($pattern)));
		return $pattern;
	}
	
	/**
	 * Returns a formatted string as a number of bytes (e.g. 200M = 209715200)
	 * 
	 * @param string $string
	 * @return integer|FALSE
	 */
	static function parseBytesString($string) {
	    if(is_numeric($string)) { 
	        return intval($string);
	        
	    } else { 
	        $value = intval($string); 
	        $unit = strtolower(substr($string, -1)); 
	         
	        switch($unit) { 
	            default: 
	            case 'm': 
	                return $value * 1048576; // 1024^2
	                break; 
	            case 'g': 
	                return $value * 1073741824; // 1024^3 
	                break;
	            case 'k': 
	                return $value * 1024; // 1024^1
	                break; 
	        }
	    }
	    
	    return FALSE;
	}
	
	static function parseCrlfString($string, $keep_blanks=false) {
		$string = str_replace("\r\n","\n",$string);
		$parts = preg_split("/[\r\n]/", $string);
		
		// Remove any empty tokens
		foreach($parts as $idx => $part) {
			$parts[$idx] = trim($part);
			if(!$keep_blanks && 0 == strlen($parts[$idx])) 
				unset($parts[$idx]);
		}
		
		return $parts;
	}
	
	static function intVersionToStr($version, $sections=3) {
		$version = str_pad($version, $sections*2, '0', STR_PAD_LEFT);
		$parts = str_split($version, 2);
		
		foreach($parts as $k => $v)
			$parts[$k] = intval($v);
		
		return implode('.', $parts);
	}
	
    static function strVersionToInt($version, $sections=3) {
    	$parts = explode('.', $version);
    	
    	// Trim versions with too many significant places
    	if(count($parts) > $sections)
    		$parts = array_slice($parts, 0, $sections);
    	
    	// Pad versions with too few significant places
    	for($ctr=count($parts); $ctr < $sections; $ctr++)
    		$parts[] = '0';
    	
    	$v = 0;
    	$multiplier = 1;
    	foreach(array_reverse($parts) as $part) {
    		$v += intval($part)*$multiplier;
    		$multiplier *= 100;
    	}
    	
    	return intval($v);
    }
	
	/**
	 * Return a string as a regular expression, parsing * into a non-greedy 
	 * wildcard, etc.
	 *
	 * @param string $arg
	 * @return string
	 */
	static function strToRegExp($arg, $is_partial=false) {
		$arg = str_replace(array('*'),array('__WILD__'),$arg);
		
		return sprintf("/%s%s%s/i",
			($is_partial ? '' : '^'),
			str_replace(array('__WILD__','/'),array('.*?','\/'),preg_quote($arg)),
			($is_partial ? '' : '$')
		);
	}
	
	/**
	 * Return a string with only its alphanumeric characters
	 *
	 * @param string $arg
	 * @return string
	 */
	static function strAlphaNum($arg, $also=null) {
		return preg_replace("/[^A-Z0-9" . $also . "]/i","", $arg);
	}
	
	static function strUnidecode($string, $from_encoding = 'utf-8') {
		if(empty($string))
			return $string;
		
		$len = strlen($string);
		$out = '';
	        
		$string = (is_null($from_encoding))
            ? mb_convert_encoding($string, "UCS-4BE")
            : mb_convert_encoding($string, "UCS-4BE", $from_encoding)
            ;		
		
		while(false !== ($part = mb_substr($string, 0, 25000)) && 0 !== mb_strlen($part)) {
			$string = mb_substr($string, mb_strlen($part));
			
			$unpack = unpack("N*", $part);
	        
			foreach($unpack as $k => $v) {
				$out .= self::_strUnidecodeLookup($v);
			}
			
			unset($unpack);			
		}

		return $out;
	}
	
	static function stripHTML($str) {
		// Strip all CRLF and tabs, spacify </TD>
		$str = str_ireplace(
			array("\r","\n","\t","</td>"),
			array('','',' ',' '),
			trim($str)
		);
		
		// Handle XHTML variations
		$str = str_ireplace(
			array("<br />", "<br/>"),
			"<br>",
			$str
		);
		
		// Turn block tags into a linefeed
		$str = str_ireplace(
			array('<BR>','<P>','</P>','<HR>','</TR>','</H1>','</H2>','</H3>','</H4>','</H5>','</H6>','</DIV>'),
			"\n",
			$str
		);		
		
		// Strip tags
		$search = array(
			'@<script[^>]*?>.*?</script>@si',
		    '@<style[^>]*?>.*?</style>@siU',
		    '@<[\/\!]*?[^<>]*?>@si',
		    '@<![\s\S]*?--[ \t\n\r]*>@',
		);
		$str = preg_replace($search, '', $str);
		
		// Flatten multiple spaces into a single
		$str = preg_replace('# +#', ' ', $str);

		// Translate HTML entities into text
		$str = html_entity_decode($str, ENT_COMPAT, LANG_CHARSET_CODE);

		// Loop through each line, ltrim, and concat if not empty
		$lines = explode("\n", $str);
		if(is_array($lines)) {
			$str = '';
			$blanks = 0;
			foreach($lines as $idx => $line) {
				$lines[$idx] = ltrim($line);
				
				if(empty($lines[$idx])) {
					if(++$blanks >= 2)
						unset($lines[$idx]);
						//continue; // skip more than 2 blank lines in a row
				} else {
					$blanks = 0;
				}
			}
			$str = implode("\n", $lines);
		}
		unset($lines);
		
		// Clean up bytes (needed after HTML entities)
		$str = mb_convert_encoding($str, LANG_CHARSET_CODE, LANG_CHARSET_CODE);
		
		return $str;
	}	
	
	static function parseMarkdown($text) {
		static $parser = null;
		
		if(is_null($parser))
			$parser = new markdown();
			
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
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			
			$user_agent = 'Cerberus Helpdesk ' . APP_VERSION . ' (Build ' . APP_BUILD . ')';
			curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
			
			$is_safemode = !(ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off'));	
	
			// We can't use option this w/ safemode enabled
			if(!$is_safemode)
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			
			$data = curl_exec($ch);
			curl_close($ch);
			
		} elseif(ini_get('allow_url_fopen')) {
			@$data = file_get_contents($url);
			
		} else {
			$logger = DevblocksPlatform::getConsoleLog();
			$logger->error("[Platform] 'curl' extension is not enabled and 'allow_url_fopen' is Off. Can not load a URL.");
			return;
		}
		
		if(empty($data))
			return true;
		
		if(null == (@$xml = simplexml_load_string($data)))
			return false;
			
		$root_tag = strtolower(dom_import_simplexml($xml)->tagName);
		
		if('feed'==$root_tag && count($xml->entry)) { // Atom
			$feed = array(
				'title' => (string) $xml->title,
				'url' => $url,
				'items' => array(),
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

	/**
	 * Returns a string as alphanumerics delimited by underscores.
	 * For example: "Devs: 1000 Ways to Improve Sales" becomes 
	 * "devs_1000_ways_to_improve_sales", which is suitable for 
	 * displaying in a URL of a blog, faq, etc.
	 *
	 * @param string $str
	 * @return string
	 */
	static function strToPermalink($string) {
		if(empty($string))
			return '';
		
		// Remove certain marks
		$string = preg_replace('#[\'\"]#', '', $string);
		
		// Strip all punctuation to underscores
		$string = preg_replace('#[^a-zA-Z0-9\+\.\-_\(\)]#', '_', $string);
			
		// Collapse all underscores to singles
		$string = preg_replace('#__+#', '_', $string);
		
		return rtrim($string,'_');
	}
	
	static function strToHyperlinks($string) {
		$regex = '@(https?://(.*?))(([>"\.\?,\)]{0,1}(\s|$))|(&(quot|gt);))@i';
		return preg_replace($regex,'<a href="$1" target="_blank">$1</a>$3',$string);
	}
	
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
				$whole .= round($diffsecs/3600).' hour';
			} elseif($diffsecs >= 60) { // mins
				$whole .= round($diffsecs/60).' min';
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
				$whole .= round($diffsecs/-3600).' hour';
			} elseif($diffsecs <= -60) { // mins
				$whole .= round($diffsecs/-60).' min';
			} elseif($diffsecs <= 0) { // secs
				$whole .= round($diffsecs/-1).' sec';
			}
		}

		// Pluralize
		$whole .= (1 == abs(intval($whole))) ? '' : 's';

		if($diffsecs > 0 && !$is_delta)
			$whole .= ' ago';
		
		return $whole;		
	}
	
	static function strPrettyBytes($string, $precision='0') {
		if(!is_numeric($string))
			return '';
			
		$bytes = floatval($string);
		$precision = floatval($precision);
		$out = '';
		
		if($bytes >= 1000000000) {
			$out = number_format($bytes/1000000000,$precision) . ' GB';
		} elseif ($bytes >= 1000000) {
			$out = number_format($bytes/1000000,$precision) . ' MB';
		} elseif ($bytes >= 1000) {
			$out = number_format($bytes/1000,$precision) . ' KB';
		} else {
			$out = $bytes . ' bytes';
		}
		
		return $out;		
	}
	
	/**
	 * Takes a comma-separated value string and returns an array of tokens.
	 * [TODO] Move to a FormHelper service?
	 * 
	 * @param string $string
	 * @return array
	 */
	static function parseCsvString($string, $keep_blanks=false, $typecast=null) {
		if(empty($string))
			return array();
		
		$tokens = explode(',', $string);

		if(!is_array($tokens))
			return array();
		
		foreach($tokens as $k => $v) {
			if(!is_null($typecast)) {
				settype($v, $typecast);
			}
			
			$tokens[$k] = trim($v);
			
			if(!$keep_blanks && 0==strlen($tokens[$k]))
				unset($tokens[$k]);
		}
		
		return $tokens;
	}
	
	/**
	 * Clears any platform-level plugin caches.
	 * 
	 */
	static function clearCache($one_cache=null) {
	    $cache = self::getCacheService(); /* @var $cache _DevblocksCacheManager */

	    if(!empty($one_cache)) {
	    	$cache->remove($one_cache);
	    	
	    } else { // All
		    $cache->remove(self::CACHE_ACL);
		    $cache->remove(self::CACHE_PLUGINS);
		    $cache->remove(self::CACHE_ACTIVITY_POINTS);
		    $cache->remove(self::CACHE_EVENT_POINTS);
		    $cache->remove(self::CACHE_EVENTS);
		    $cache->remove(self::CACHE_EXTENSIONS);
		    $cache->remove(self::CACHE_POINTS);
		    $cache->remove(self::CACHE_SETTINGS);
		    $cache->remove(self::CACHE_TABLES);
		    $cache->remove(_DevblocksClassLoadManager::CACHE_CLASS_MAP);
		    
		    // Clear all locale caches
		    $langs = DAO_Translation::getDefinedLangCodes();
		    if(is_array($langs) && !empty($langs))
		    foreach($langs as $lang_code => $lang_name) {
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
		$classloader = self::getClassLoaderService();
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
		$tables = self::getDatabaseTables();
	    return empty($tables);
	}
	
	static function getDatabaseTables() {
	    $cache = self::getCacheService();
	    $tables = array();
	    
	    if(null === ($tables = $cache->load(self::CACHE_TABLES))) {
	        $db = self::getDatabaseService();
	        
	        // Make sure the database connection is valid or error out.
	        if(is_null($db) || !$db->isConnected())
	        	return array();
	        
	        $tables = $db->metaTables();
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
		$cache = DevblocksPlatform::getCacheService(); /* @var _DevblocksCacheManager $cache */ 
		
		if(null === ($build_cache = $cache->load("devblocks_app_build"))
			|| $build_cache != APP_BUILD) {
				
			// If build changed, clear cache regardless of patch status
			// [TODO] We need to find a nicer way to not clear a shared memcached cluster when only one desk needs to
			$cache = DevblocksPlatform::getCacheService(); /* @var $cache _DevblocksCacheManager */
			$cache->clean();
			
			// Re-read manifests
			DevblocksPlatform::readPlugins();
			
			if(self::_needsToPatch()) {
				return false; // the update script will handle new caches
			} else {
				$cache->save(APP_BUILD, "devblocks_app_build");
				DAO_Translation::reloadPluginStrings(); // reload strings even without DB changes
				return true;
			}
		}
		
		return true;
	}
	
	/**
	 * Enter description here...
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
	static function getExtensions($point,$as_instances=false, $ignore_acl=false) {
	    $results = array();
	    $extensions = DevblocksPlatform::getExtensionRegistry($ignore_acl);

	    if(is_array($extensions))
	    foreach($extensions as $extension) { /* @var $extension DevblocksExtensionManifest */
	        if($extension->point == $point) {
	            $results[$extension->id] = ($as_instances) ? $extension->createInstance() : $extension;
	        }
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
	static function getExtension($extension_id, $as_instance=false, $ignore_acl=false) {
	    $result = null;
	    $extensions = DevblocksPlatform::getExtensionRegistry($ignore_acl);

	    if(is_array($extensions))
	    foreach($extensions as $extension) { /* @var $extension DevblocksExtensionManifest */
	        if($extension->id == $extension_id) {
	            $result = $extension;
	            break;
	        }
	    }

	    if($as_instance && !is_null($result)) {
	    	return $result->createInstance();
	    }
	    
	    return $result;
	}

	/**
	 * Returns an array of all contributed extension manifests.
	 *
	 * @static 
	 * @return DevblocksExtensionManifest[]
	 */
	static function getExtensionRegistry($ignore_acl=false) {
	    $cache = self::getCacheService();
	    static $acl_extensions = null;
	    
	    if(null === ($extensions = $cache->load(self::CACHE_EXTENSIONS))) {
		    $db = DevblocksPlatform::getDatabaseService();
		    if(is_null($db)) return;
	
		    $prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup
	
		    $sql = sprintf("SELECT e.id , e.plugin_id, e.point, e.pos, e.name , e.file , e.class, e.params ".
				"FROM %sextension e ".
				"INNER JOIN %splugin p ON (e.plugin_id=p.id) ".
				"WHERE p.enabled = 1 ".
				"ORDER BY e.plugin_id ASC, e.pos ASC",
					$prefix,
					$prefix
				);
			$results = $db->GetArray($sql); 
				
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

			$cache->save($extensions, self::CACHE_EXTENSIONS);
			$acl_extensions = null;
		}
		
		if(!$ignore_acl) {
			// If we don't have a cache in this request
			if(null == $acl_extensions) {
				// Check with an extension delegate if we have one
				if(class_exists(self::$extensionDelegate) && method_exists('DevblocksExtensionDelegate','shouldLoadExtension')) {
					if(is_array($extensions))
					foreach($extensions as $id => $extension) {
						// Ask the delegate if we should load the extension
						if(!call_user_func(array(self::$extensionDelegate,'shouldLoadExtension'),$extension))
							unset($extensions[$id]);
					}
				}
				// Cache for duration of request
				$acl_extensions = $extensions;
			} else {
				$extensions = $acl_extensions;
			}
		}
		
		return $extensions;
	}

	static function getActivityPointRegistry() {
	    $cache = self::getCacheService();
		$plugins = DevblocksPlatform::getPluginRegistry();
		
		if(empty($plugins))
			return array();
			
	    if(null !== ($activities = $cache->load(self::CACHE_ACTIVITY_POINTS)))
    	    return $activities;
			
		$activities = array();
			
		foreach($plugins as $plugin) { /* @var $plugin DevblocksPluginManifest */
			if($plugin->enabled)
			foreach($plugin->getActivityPoints() as $point => $data) {
				$activities[$point] = $data;
			}
		}
		
		ksort($activities);
		
		$cache->save($activities, self::CACHE_ACTIVITY_POINTS);
		return $activities;
	}
	
	/**
	 * @return DevblocksEventPoint[]
	 */
	static function getEventPointRegistry() {
	    $cache = self::getCacheService();
	    if(null !== ($events = $cache->load(self::CACHE_EVENT_POINTS)))
    	    return $events;

        $events = array();
        $plugins = self::getPluginRegistry();
    	 
		// [JAS]: Event point hashing/caching
		if(is_array($plugins))
		foreach($plugins as $plugin) { /* @var $plugin DevblocksPluginManifest */
            $events = array_merge($events,$plugin->event_points);
		}
    	
		$cache->save($events, self::CACHE_EVENT_POINTS);
		return $events;
	}
	
	/**
	 * @return DevblocksAclPrivilege[]
	 */
	static function getAclRegistry() {
	    $cache = self::getCacheService();
	    if(null !== ($acl = $cache->load(self::CACHE_ACL)))
    	    return $acl;

        $acl = array();

	    $db = DevblocksPlatform::getDatabaseService();
	    if(is_null($db)) return;

        //$plugins = self::getPluginRegistry();
	    $prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup

	    $sql = sprintf("SELECT a.id, a.plugin_id, a.label ".
			"FROM %sacl a ".
			"INNER JOIN %splugin p ON (a.plugin_id=p.id) ".
			"WHERE p.enabled = 1 ".
			"ORDER BY a.plugin_id, a.id ASC",
			$prefix,
			$prefix
		);
		$results = $db->GetArray($sql); 
		
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
	    $cache = self::getCacheService();
	    if(null !== ($events = $cache->load(self::CACHE_EVENTS)))
    	    return $events;
	    
    	$extensions = self::getExtensions('devblocks.listener.event',false,true);
    	$events = array('*');
    	 
		// [JAS]: Event point hashing/caching
		if(is_array($extensions))
		foreach($extensions as $extension) { /* @var $extension DevblocksExtensionManifest */
            @$evts = $extension->params['events'][0];
            
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
	    $cache = self::getCacheService();
	    if(null !== ($plugins = $cache->load(self::CACHE_PLUGINS)))
    	    return $plugins;

	    $db = DevblocksPlatform::getDatabaseService();
	    if(is_null($db))
	    	return;

		$plugins = array();
	    	
	    $prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup

	    $sql = sprintf("SELECT p.* ".
			"FROM %splugin p ".
			"ORDER BY p.enabled DESC, p.name ASC ",
			$prefix
		);
		$results = $db->GetArray($sql); 

		foreach($results as $row) {
		    $plugin = new DevblocksPluginManifest();
		    @$plugin->id = $row['id'];
		    @$plugin->enabled = intval($row['enabled']);
		    @$plugin->name = $row['name'];
		    @$plugin->description = $row['description'];
		    @$plugin->author = $row['author'];
		    @$plugin->version = intval($row['version']);
		    @$plugin->link = $row['link'];
		    @$plugin->dir = $row['dir'];

		    // JSON decode
		    if(isset($row['manifest_cache_json'])
		    	&& null != ($manifest_cache_json = $row['manifest_cache_json'])) {
		    	$plugin->manifest_cache = json_decode($manifest_cache_json, true);
		    }

		    if(file_exists(APP_PATH . DIRECTORY_SEPARATOR . $plugin->dir . DIRECTORY_SEPARATOR . 'plugin.xml'))
	        	$plugins[$plugin->id] = $plugin;
		}

		$sql = sprintf("SELECT p.id, p.name, p.params, p.plugin_id ".
		    "FROM %sevent_point p ",
		    $prefix
		);
		$results = $db->GetArray($sql); 

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
			@$deps = $plugin->manifest_cache['dependencies'];
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
	 * Enter description here...
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
	static function readPlugins() {
		$scan_dirs = array(
			'features',
			'storage/plugins',
		);
		
	    $plugins = array();

	    // Devblocks
	    if(null !== ($manifest = self::_readPluginManifest('libs/devblocks')))
	    	$plugin[] = $manifest;
	    	
	    // Application
	    if(is_array($scan_dirs))
	    foreach($scan_dirs as $scan_dir) {
	    	$scan_path = APP_PATH . '/' . $scan_dir;
		    if (is_dir($scan_path)) {
		        if ($dh = opendir($scan_path)) {
		            while (($file = readdir($dh)) !== false) {
		                if($file=="." || $file == "..")
		                	continue;
		                	
		                $plugin_path = $scan_path . '/' . $file;
		                $rel_path = $scan_dir . '/' . $file;
		                
		                if(is_dir($plugin_path) && file_exists($plugin_path.'/plugin.xml')) {
		                    $manifest = self::_readPluginManifest($rel_path); /* @var $manifest DevblocksPluginManifest */
	
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
	    
	    return $plugins;
	}

	/**
	 * @return _DevblocksPluginSettingsManager
	 */
	static function getPluginSettingsService() {
		return _DevblocksPluginSettingsManager::getInstance();
	}
	
	static function getPluginSetting($plugin_id, $key, $default=null) {
		$settings = self::getPluginSettingsService();
		return $settings->get($plugin_id, $key, $default);
	}
	
	static function setPluginSetting($plugin_id, $key, $value) {
		$settings = self::getPluginSettingsService();
		return $settings->set($plugin_id,  $key, $value);
	}

	/**
	 * @return _DevblocksLogManager
	 */
	static function getConsoleLog($prefix='') {
		return _DevblocksLogManager::getConsoleLog($prefix);
	}
	
	/**
	 * @return _DevblocksCacheManager
	 */
	static function getCacheService() {
	    return _DevblocksCacheManager::getInstance();
	}
	
	/**
	 * Enter description here...
	 *
	 * @return _DevblocksDatabaseManager
	 */
	static function getDatabaseService() {
	    return _DevblocksDatabaseManager::getInstance();
	}

	/**
	 * @return _DevblocksUrlManager
	 */
	static function getUrlService() {
	    return _DevblocksUrlManager::getInstance();
	}

	/**
	 * @return _DevblocksEmailManager
	 */
	static function getMailService() {
	    return _DevblocksEmailManager::getInstance();
	}

	/**
	 * @return _DevblocksEventManager
	 */
	static function getEventService() {
	    return _DevblocksEventManager::getInstance();
	}
	
	/**
	 * @return DevblocksProxy
	 */
	static function getProxyService() {
	    return DevblocksProxy::getProxy();
	}
	
	/**
	 * @return _DevblocksClassLoadManager
	 */
	static function getClassLoaderService() {
		return _DevblocksClassLoadManager::getInstance();
	}
	
	/**
	 * @return _DevblocksSessionManager
	 */
	static function getSessionService() {
	    return _DevblocksSessionManager::getInstance();
	}
	
	/**
	 * @return _DevblocksOpenIDManager 
	 */
	static function getOpenIDService() {
		return _DevblocksOpenIDManager::getInstance();
	}
	
	static function sanitizeArray($array, $type) {
		switch($type) {
			case 'integer':
				return _DevblocksSanitizationManager::arrayAs($array, 'integer');
				break;
			default:
				break;
		}
		
		return false;
	}
	
	/**
	 * @return _DevblocksSearchEngineMysqlFulltext
	 */
	static function getSearchService() {
		return _DevblocksSearchManager::getInstance();
	}
	
	/**
	 * @param $profile_id | $extension_id, $options
	 * @return Extension_DevblocksStorageEngine
	 */
	static function getStorageService() {
		$args = func_get_args();

		if(empty($args))
			return false;
		
		$profile = $args[0];
		$params = array();
		
		// Handle $profile polymorphism
		if($profile instanceof Model_DevblocksStorageProfile) {
			$extension = $profile->extension_id;
			$params = $profile->params;
		} else if(is_numeric($profile)) {
			$storage_profile = DAO_DevblocksStorageProfile::get($profile);
			$extension = $storage_profile->extension_id;
			$params = $storage_profile->params;
		} else if(is_string($profile)) {
			$extension = $profile;
			
			if(isset($args[1]) && is_array($args[1]))
				$params = $args[1];
		}
		
	    return _DevblocksStorageManager::getEngine($extension, $params);
	}

	/**
	 * @return Smarty
	 */
	static function getTemplateService() {
	    return _DevblocksTemplateManager::getInstance();
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
					$templates[] = $template;
				}
			}
		}
		
		return $templates;
	}
	
	/**
	 * @return _DevblocksTemplateBuilder
	 */
	static function getTemplateBuilder() {
	    return _DevblocksTemplateBuilder::getInstance();
	}

	/**
	 * @return _DevblocksDateManager
	 */
	static function getDateService($datestamp=null) {
		return _DevblocksDateManager::getInstance();
	}

	static function setLocale($locale) {
		@setlocale(LC_ALL, $locale);
		self::$locale = $locale;
	}
	
	static function getLocale() {
		if(!empty(self::$locale))
			return self::$locale;
			
		return 'en_US';
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
						
		$cache = self::getCacheService();
	    
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
			$cache->save($map,self::CACHE_TAG_TRANSLATIONS.'_'.$locale);
	    }
	    
		$translate = _DevblocksTranslationManager::getInstance();
		$translate->addLocale($locale, $map);
		$translate->setLocale($locale);
	    
		$languages[$locale] = $translate;

	    return $translate;
	}

	/**
	 * Enter description here...
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
	 * Enter description here...
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
		
		// Encoding (mbstring)
		mb_internal_encoding(LANG_CHARSET_CODE);
		if(function_exists('mb_regex_encoding'))
			mb_regex_encoding(LANG_CHARSET_CODE);
		
	    // [JAS] [MDF]: Automatically determine the relative webpath to Devblocks files
	    @$proxyhost = $_SERVER['HTTP_DEVBLOCKSPROXYHOST'];
	    @$proxybase = $_SERVER['HTTP_DEVBLOCKSPROXYBASE'];
    
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
        
        // Register shutdown function
        register_shutdown_function(array('DevblocksPlatform','shutdown'));
	}
	
	static function shutdown() {
		// Clean up any temporary files
		while(null != ($tmpfile = array_pop(self::$_tmp_files))) {
			@unlink($tmpfile);
		}
	}

	static function setExtensionDelegate($class) {
		if(!empty($class) && class_exists($class, true))
			self::$extensionDelegate = $class;
	}
	
	static function redirect(DevblocksHttpIO $httpIO) {
		$url_service = self::getUrlService();
		session_write_close();
		$url = $url_service->writeDevblocksHttpIO($httpIO, true);
		header('Location: '.$url);
		exit;
	}
	
	static function redirectURL($url) {
		if(empty($url)) {
			$url_service = self::getUrlService();
			$url = $url_service->writeNoProxy('', true);
		}
		session_write_close();
		header('Location: '.$url);
		exit;
	}
};

function devblocks_autoload($className) {
	$classloader = _DevblocksClassLoadManager::getInstance();
	return $classloader->loadClass($className);
}

// Register Devblocks class loader
spl_autoload_register('devblocks_autoload');

/*
 * Twig Extensions
 * This must come after devblocks_autoload
 */ 
if(class_exists('Twig_Autoloader', true) && method_exists('Twig_Autoloader','register')) {
	Twig_Autoloader::register();
}
