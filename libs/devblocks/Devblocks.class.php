<?php
include_once(DEVBLOCKS_PATH . "api/Model.php");
include_once(DEVBLOCKS_PATH . "api/DAO.php");
include_once(DEVBLOCKS_PATH . "api/Extension.php");

define('PLATFORM_BUILD',2010120101);

/**
 * A platform container for plugin/extension registries.
 *
 * @author Jeff Standen <jeff@webgroupmedia.com>
 */
class DevblocksPlatform extends DevblocksEngine {
    const CACHE_ACL = 'devblocks_acl';
    const CACHE_EVENT_POINTS = 'devblocks_event_points';
    const CACHE_EVENTS = 'devblocks_events';
    const CACHE_EXTENSIONS = 'devblocks_extensions';
    const CACHE_PLUGINS = 'devblocks_plugins';
    const CACHE_POINTS = 'devblocks_points';
    const CACHE_SETTINGS = 'devblocks_settings';
    const CACHE_STORAGE_PROFILES = 'devblocks_storage_profiles';
    const CACHE_TABLES = 'devblocks_tables';
    const CACHE_TAG_TRANSLATIONS = 'devblocks_translations';
    
    static private $extensionDelegate = null;
    
    static private $start_time = 0;
    static private $start_memory = 0;
    static private $start_peak_memory = 0;
    
    static private $locale = 'en_US';
    
    static private $_tmp_files = array();
    
    private function __construct() { return false; }

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
	    if(!is_null($var)) {
	        if(is_string($var)) {
	            $var = get_magic_quotes_gpc() ? stripslashes($var) : $var;
	        } elseif(is_array($var)) {
                foreach($var as $k => $v) {
                    $var[$k] = get_magic_quotes_gpc() ? stripslashes($v) : $v;
                }
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
	
	static function parseCrlfString($string) {
		$parts = preg_split("/[\r\n]/", $string);
		
		// Remove any empty tokens
		foreach($parts as $idx => $part) {
			$parts[$idx] = trim($part);
			if(0 == strlen($parts[$idx])) 
				unset($parts[$idx]);
		}
		
		return $parts;
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
	static function strAlphaNum($arg) {
		return preg_replace("/[^A-Z0-9\.]/i","", $arg);
	}
	
	/**
	 * Return a string with only its alphanumeric characters or punctuation
	 *
	 * @param string $arg
	 * @return string
	 */
	static function strAlphaNumDash($arg) {
		return preg_replace("/[^A-Z0-9_\-\.]/i","", $arg);
	}	

	/**
	 * Return a string with only its alphanumeric characters or underscore
	 *
	 * @param string $arg
	 * @return string
	 */
	static function strAlphaNumUnder($arg) {
		return preg_replace("/[^A-Z0-9_]/i","", $arg);
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
			$parser = new Markdown_Parser();
			
		return $parser->transform($text);
	}
	
	static function parseRss($url) {
		// [TODO] curl | file_get_contents() support
		// [TODO] rss://
		
		if(null == (@$data = file_get_contents($url)))
			return false;
		
		if(null == (@$xml = simplexml_load_string($data)))
			return false;
			
		// [TODO] Better detection of RDF/RSS/Atom + versions 
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
					'date' => strtotime($date),
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
				
				$feed['items'][] = array(
					'date' => strtotime($date),
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
				
				$feed['items'][] = array(
					'date' => strtotime($date),
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
	static function getStringAsURI($str) {
		$str = strtolower($str);
		
		// turn non [a-z, 0-9, _] into whitespace
		$str = preg_replace("/[^0-9a-z]/",' ',$str);
		
		// condense whitespace to a single underscore
		$str = preg_replace('/\s\s+/', ' ', $str);

		// replace spaces with underscore
		$str = str_replace(' ','_',$str);

		// remove a leading/trailing underscores
		$str = trim($str, '_');
		
		return $str;
	}
	
	/**
	 * Takes a comma-separated value string and returns an array of tokens.
	 * [TODO] Move to a FormHelper service?
	 * 
	 * @param string $string
	 * @return array
	 */
	static function parseCsvString($string) {
		$tokens = explode(',', $string);

		if(!is_array($tokens))
			return array();
		
		foreach($tokens as $k => $v) {
			$tokens[$k] = trim($v);
			if(0==strlen($tokens[$k]))
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

	public static function loadClass($className) {
		$classloader = self::getClassLoaderService();
		return $classloader->loadClass($className);
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
	 * @return resource $fp
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

//	static function getExtensionPoints() {
//	    $cache = self::getCacheService();
//	    if(null !== ($points = $cache->load(self::CACHE_POINTS)))
//	        return $points;
//
//	    $extensions = DevblocksPlatform::getExtensionRegistry();
//	    foreach($extensions as $extension) { /* @var $extension DevblocksExtensionManifest */
//	        $point = $extension->point;
//	        if(!isset($points[$point])) {
//	            $p = new DevblocksExtensionPoint();
//	            $p->id = $point;
//	            $points[$point] = $p;
//	        }
//	        	
//	        $points[$point]->extensions[$extension->id] = $extension;
//	    }
//
//	    $cache->save($points, self::CACHE_POINTS);
//	    return $points;
//	}

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
		    @$plugin->revision = intval($row['revision']);
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
	
	static private function _sortPluginsByDependency(&$plugins) {
		$dependencies = array();
		$seen = array();
		$order = array();
		
        // Dependencies
		foreach($plugins as $plugin) {
			@$deps = $plugin->manifest_cache['dependencies'];
			$dependencies[$plugin->id] = is_array($deps) ? $deps : array();
		}
		
		foreach($plugins as $plugin)
			self::_recursiveDependency($plugin->id, $dependencies, $seen, $order);

		$original = $plugins;
		$plugins = array();
			
		foreach($order as $order_id) {
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
	static function getConsoleLog() {
		return _DevblocksLogManager::getConsoleLog();
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
};

abstract class DevblocksEngine {
	protected static $request = null;
	protected static $response = null;
	
	/**
	 * Reads and caches a single manifest from a given plugin directory.
	 * 
	 * @static 
	 * @private
	 * @param string $dir
	 * @return DevblocksPluginManifest
	 */
	static protected function _readPluginManifest($rel_dir, $persist=true) {
		$manifest_file = APP_PATH . '/' . $rel_dir . '/plugin.xml'; 
		
		if(!file_exists($manifest_file))
			return NULL;
		
		$plugin = simplexml_load_file($manifest_file);
		$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup
				
		$manifest = new DevblocksPluginManifest();
		$manifest->id = (string) $plugin->id;
		$manifest->dir = $rel_dir;
		$manifest->description = (string) $plugin->description;
		$manifest->author = (string) $plugin->author;
		$manifest->revision = (integer) $plugin->revision;
		$manifest->link = (string) $plugin->link;
		$manifest->name = (string) $plugin->name;
		
		// Dependencies
		if(isset($plugin->dependencies)) {
			if(isset($plugin->dependencies->require))
			foreach($plugin->dependencies->require as $eDependency) {
				$depends_on = (string) $eDependency['plugin_id'];
				$manifest->manifest_cache['dependencies'][] = $depends_on;
			}
		}
		
		// Patches
		if(isset($plugin->patches)) {
			if(isset($plugin->patches->patch))
			foreach($plugin->patches->patch as $ePatch) {
				$patch_version = (string) $ePatch['version'];
				$patch_revision = (string) $ePatch['revision'];
				$patch_file = (string) $ePatch['file'];
				$manifest->manifest_cache['patches'][] = array(
					'version' => $patch_version,
					'revision' => $patch_revision,
					'file' => $patch_file,
				);
			}
		}
		
		// Templates
		if(isset($plugin->templates)) {
			foreach($plugin->templates as $eTemplates) {
				$template_set = (string) $eTemplates['set'];
				
				if(isset($eTemplates->template))
				foreach($eTemplates->template as $eTemplate) {
					$manifest->manifest_cache['templates'][] = array(
						'plugin_id' => $manifest->id,
						'set' => $template_set,
						'path' => (string) $eTemplate['path'],
					);
				}
			}
		}
		
		// Image
		if(isset($plugin->image)) {
			$manifest->manifest_cache['plugin_image'] = (string) $plugin->image;
		}
			
		if(!$persist)
			return $manifest;
		
		$db = DevblocksPlatform::getDatabaseService();
		if(is_null($db)) 
			return;
			
		// Persist manifest
		if($db->GetOne(sprintf("SELECT id FROM ${prefix}plugin WHERE id = %s", $db->qstr($manifest->id)))) { // update
			$db->Execute(sprintf(
				"UPDATE ${prefix}plugin ".
				"SET name=%s,description=%s,author=%s,revision=%s,link=%s,dir=%s,manifest_cache_json=%s ".
				"WHERE id=%s",
				$db->qstr($manifest->name),
				$db->qstr($manifest->description),
				$db->qstr($manifest->author),
				$db->qstr($manifest->revision),
				$db->qstr($manifest->link),
				$db->qstr($manifest->dir),
				$db->qstr(json_encode($manifest->manifest_cache)),
				$db->qstr($manifest->id)
			));
			
		} else { // insert
			$enabled = ('devblocks.core'==$manifest->id) ? 1 : 0;
			$db->Execute(sprintf(
				"INSERT INTO ${prefix}plugin (id,enabled,name,description,author,revision,link,dir,manifest_cache_json) ".
				"VALUES (%s,%d,%s,%s,%s,%s,%s,%s,%s)",
				$db->qstr($manifest->id),
				$enabled,
				$db->qstr($manifest->name),
				$db->qstr($manifest->description),
				$db->qstr($manifest->author),
				$db->qstr($manifest->revision),
				$db->qstr($manifest->link),
				$db->qstr($manifest->dir),
				$db->qstr(json_encode($manifest->manifest_cache))
			));
		}
		
		// Class Loader
		if(isset($plugin->class_loader->file)) {
			foreach($plugin->class_loader->file as $eFile) {
				@$sFilePath = (string) $eFile['path'];
				$manifest->class_loader[$sFilePath] = array();
				
				if(isset($eFile->class))
				foreach($eFile->class as $eClass) {
					@$sClassName = (string) $eClass['name'];
					$manifest->class_loader[$sFilePath][] = $sClassName;
				}
			}
		}
		
		// Routing
		if(isset($plugin->uri_routing->uri)) {
			foreach($plugin->uri_routing->uri as $eUri) {
				@$sUriName = (string) $eUri['name'];
				@$sController = (string) $eUri['controller'];
				$manifest->uri_routing[$sUriName] = $sController;
			}
		}
		
		// ACL
		if(isset($plugin->acl->priv)) {
			foreach($plugin->acl->priv as $ePriv) {
				@$sId = (string) $ePriv['id'];
				@$sLabel = (string) $ePriv['label'];
				
				if(empty($sId) || empty($sLabel))
					continue;
					
				$priv = new DevblocksAclPrivilege();
				$priv->id = $sId;
				$priv->plugin_id = $manifest->id;
				$priv->label = $sLabel;
				
				$manifest->acl_privs[$priv->id] = $priv;
			}
			asort($manifest->acl_privs);
		}
		
		// Event points
		if(isset($plugin->event_points->event)) {
		    foreach($plugin->event_points->event as $eEvent) {
		        $sId = (string) $eEvent['id'];
		        $sName = (string) $eEvent->name;
		        
		        if(empty($sId) || empty($sName))
		            continue;
		        
		        $point = new DevblocksEventPoint();
		        $point->id = $sId;
		        $point->plugin_id = $plugin->id;
		        $point->name = $sName;
		        $point->params = array();
		        
		        if(isset($eEvent->param)) {
		            foreach($eEvent->param as $eParam) {
		                $key = (string) $eParam['key']; 
		                $val = (string) $eParam['value']; 
		                $point->param[$key] = $val;
		            }
		        }
		        
		        $manifest->event_points[] = $point;
		    }
		}
		
		// Extensions
		if(isset($plugin->extensions->extension)) {
		    foreach($plugin->extensions->extension as $eExtension) {
		        $sId = (string) $eExtension->id;
		        $sName = (string) $eExtension->name;
		        
		        if(empty($sId) || empty($sName))
		            continue;
		        
		        $extension = new DevblocksExtensionManifest();
		        
		        $extension->id = $sId;
		        $extension->plugin_id = $manifest->id;
		        $extension->point = (string) $eExtension['point'];
		        $extension->name = $sName;
		        $extension->file = (string) $eExtension->class->file;
		        $extension->class = (string) $eExtension->class->name;
		        
		        if(isset($eExtension->params->param)) {
		            foreach($eExtension->params->param as $eParam) {
				$key = (string) $eParam['key'];
		                if(isset($eParam->value)) {
					// [JSJ]: If there is a child of the param tag named value, then this 
					//        param has multiple values and thus we need to grab them all.
					foreach($eParam->value as $eValue) {
						// [JSJ]: If there is a child named data, then this is a complex structure
						if(isset($eValue->data)) {
							$value = array();
							foreach($eValue->data as $eData) {
								$key2 = (string) $eData['key'];
								if(isset($eData['value'])) {
									$value[$key2] = (string) $eData['value'];
								} else {
									$value[$key2] = (string) $eData;
								}
							}
						}
						else {
							// [JSJ]: Else, just grab the value and use it
							$value = (string) $eValue;
						}
						$extension->params[$key][] = $value;
						unset($value); // Just to be extra safe
					}
				}
				else {
					// [JSJ]: Otherwise, we grab the single value from the params value attribute.
					$extension->params[$key] = (string) $eParam['value'];
				}
		            }
		        }
		        
		        $manifest->extensions[] = $extension;
		    }
		}

		// [JAS]: Extension caching
		$new_extensions = array();
		if(is_array($manifest->extensions))
		foreach($manifest->extensions as $pos => $extension) { /* @var $extension DevblocksExtensionManifest */
			$db->Execute(sprintf(
				"REPLACE INTO ${prefix}extension (id,plugin_id,point,pos,name,file,class,params) ".
				"VALUES (%s,%s,%s,%d,%s,%s,%s,%s)",
				$db->qstr($extension->id),
				$db->qstr($extension->plugin_id),
				$db->qstr($extension->point),
				$pos,
				$db->qstr($extension->name),
				$db->qstr($extension->file),
				$db->qstr($extension->class),
				$db->qstr(serialize($extension->params))
			));
			
			$new_extensions[$extension->id] = true;
		}
		
		/*
		 * Compare our loaded XML manifest to the DB manifest cache and invalidate 
		 * the cache for extensions that are no longer in the XML.
		 */
		$sql = sprintf("SELECT id FROM %sextension WHERE plugin_id = %s",
			$prefix,
			$db->qstr($plugin->id)
		);
		$results = $db->GetArray($sql);

		foreach($results as $row) {
			$plugin_ext_id = $row['id'];
			if(!isset($new_extensions[$plugin_ext_id]))
				DAO_Platform::deleteExtension($plugin_ext_id);
		}
		
		// Class loader cache
		$db->Execute(sprintf("DELETE FROM %sclass_loader WHERE plugin_id = %s",$prefix,$db->qstr($plugin->id)));
		if(is_array($manifest->class_loader))
		foreach($manifest->class_loader as $file_path => $classes) {
			if(is_array($classes) && !empty($classes))
			foreach($classes as $class)
			$db->Execute(sprintf(
				"REPLACE INTO ${prefix}class_loader (class,plugin_id,rel_path) ".
				"VALUES (%s,%s,%s)",
				$db->qstr($class),
				$db->qstr($manifest->id),
				$db->qstr($file_path)	
			));			
		}
		
		// URI routing cache
		$db->Execute(sprintf("DELETE FROM %suri_routing WHERE plugin_id = %s",$prefix,$db->qstr($plugin->id)));
		if(is_array($manifest->uri_routing))
		foreach($manifest->uri_routing as $uri => $controller_id) {
			$db->Execute(sprintf(
				"REPLACE INTO ${prefix}uri_routing (uri,plugin_id,controller_id) ".
				"VALUES (%s,%s,%s)",
				$db->qstr($uri),
				$db->qstr($manifest->id),
				$db->qstr($controller_id)	
			));			
		}

		// ACL caching
		$db->Execute(sprintf("DELETE FROM %sacl WHERE plugin_id = %s",$prefix,$db->qstr($plugin->id)));
		if(is_array($manifest->acl_privs))
		foreach($manifest->acl_privs as $priv) { /* @var $priv DevblocksAclPrivilege */
			$db->Execute(sprintf(
				"REPLACE INTO ${prefix}acl (id,plugin_id,label) ".
				"VALUES (%s,%s,%s)",
				$db->qstr($priv->id),
				$db->qstr($priv->plugin_id),
				$db->qstr($priv->label)
			));			
		}
		
        // [JAS]: Event point caching
		if(is_array($manifest->event_points))
		foreach($manifest->event_points as $event) { /* @var $event DevblocksEventPoint */
			$db->Execute(sprintf(
				"REPLACE INTO ${prefix}event_point (id,plugin_id,name,params) ".
				"VALUES (%s,%s,%s,%s)",
				$db->qstr($event->id),
				$db->qstr($event->plugin_id),
				$db->qstr($event->name),
				$db->qstr(serialize($event->params))	
			));
		}
		
		return $manifest;
	}
	
	static function getWebPath() {
		$location = "";
		
		// Read the relative URL into an array
		if(isset($_SERVER['HTTP_X_REWRITE_URL'])) { // IIS Rewrite
			$location = $_SERVER['HTTP_X_REWRITE_URL'];
		} elseif(isset($_SERVER['REQUEST_URI'])) { // Apache
			$location = $_SERVER['REQUEST_URI'];
		} elseif(isset($_SERVER['REDIRECT_URL'])) { // Apache mod_rewrite (breaks on CGI)
			$location = $_SERVER['REDIRECT_URL'];
		} elseif(isset($_SERVER['ORIG_PATH_INFO'])) { // IIS + CGI
			$location = $_SERVER['ORIG_PATH_INFO'];
		}
		
		return $location;
	}
	
	/**
	 * Reads the HTTP Request object.
	 * 
	 * @return DevblocksHttpRequest
	 */
	static function readRequest() {
		$url = DevblocksPlatform::getUrlService();

		$location = self::getWebPath();
		
		$parts = $url->parseURL($location);
		
		// Add any query string arguments (?arg=value&arg=value)
		@$query = $_SERVER['QUERY_STRING'];
		$queryArgs = $url->parseQueryString($query);
		
		if(empty($parts)) {
			// Overrides (Form POST, etc.)

			// Controller (GET has precedence over POST)
			if(isset($_GET['c'])) {
				@$uri = DevblocksPlatform::importGPC($_GET['c']); // extension
			} elseif (isset($_POST['c'])) {
				@$uri = DevblocksPlatform::importGPC($_POST['c']); // extension
			}
			if(!empty($uri)) $parts[] = DevblocksPlatform::strAlphaNum($uri);

			// Action (GET has precedence over POST)
			if(isset($_GET['a'])) {
				@$listener = DevblocksPlatform::importGPC($_GET['a']); // listener
			} elseif (isset($_POST['a'])) {
				@$listener = DevblocksPlatform::importGPC($_POST['a']); // listener
			}
			if(!empty($listener)) $parts[] = DevblocksPlatform::strAlphaNum($listener);
		}
		
		// Controller XSS security (alphanum only)
		if(isset($parts[0])) {
			$parts[0] = DevblocksPlatform::strAlphaNum($parts[0]);
		}
		
		// Resource / Proxy
	    /*
	     * [TODO] Run this code through another audit.  Is it worth a tiny hit per resource 
	     * to verify the plugin matches exactly in the DB?  If so, make sure we cache the 
	     * resulting file.
	     * 
	     * [TODO] Make this a controller
	     */
	    $path = $parts;
		switch(array_shift($path)) {
		    case "resource":
			    $plugin_id = array_shift($path);
			    if(null == ($plugin = DevblocksPlatform::getPlugin($plugin_id)))
			    	break;
			    
			    $file = implode(DIRECTORY_SEPARATOR, $path); // combine path
		        $dir = APP_PATH . '/' . $plugin->dir . '/' . 'resources';
		        if(!is_dir($dir)) die(""); // basedir Security
		        $resource = $dir . '/' . $file;
		        if(0 != strstr($dir,$resource)) die("");
		        $ext = @array_pop(explode('.', $resource));
		        if(!is_file($resource) || 'php' == $ext) die(""); // extension security

                // Caching
                switch($ext) {
                	case 'css':
                	case 'gif':
                	case 'jpg':
                	case 'js':
                	case 'png':
		                header('Cache-control: max-age=604800', true); // 1 wk // , must-revalidate
		                header('Expires: ' . gmdate('D, d M Y H:i:s',time()+604800) . ' GMT'); // 1 wk
                		break;
                }
                
	            switch($ext) {
	            	case 'css':
	            		header('Content-type: text/css;');
	            		break;
	            	case 'gif':
	            		header('Content-type: image/gif;');
	            		break;
	            	case 'jpeg':
	            	case 'jpg':
	            		header('Content-type: image/jpeg;');
	            		break;
	            	case 'js':
	            		header('Content-type: text/javascript;');
	            		break;
	            	case 'png':
	            		header('Content-type: image/png;');
	            		break;
	            	case 'xml':
	            		header('Content-type: text/xml;');
	            		break;
	            }
	            
		        $out = file_get_contents($resource, false);
		        
                // Pass through
                if($out) {
                	header('Content-Length: '. strlen($out));
                	echo $out;
                }
		        
				exit;
    	        break;
		        
		    default:
		        break;
		}

		$request = new DevblocksHttpRequest($parts,$queryArgs);
		DevblocksPlatform::setHttpRequest($request);
		
		return $request;
	}
	
	/**
	 * Processes the HTTP request.
	 * 
	 * @param DevblocksHttpRequest $request
	 * @param boolean $is_ajax
	 */
	static function processRequest(DevblocksHttpRequest $request, $is_ajax=false) {
		$path = $request->path;
		
		$controller_uri = array_shift($path);
		
		// [JAS]: Offer the platform a chance to intercept.
		switch($controller_uri) {

			// [JAS]: Plugin-supplied URIs
			default:
				$routing = array();
	            $controllers = DevblocksPlatform::getExtensions('devblocks.controller', false);
				
				// Add any controllers which have definitive routing
				if(is_array($controllers))
				foreach($controllers as $controller_mft) {
					if(isset($controller_mft->params['uri']))
						$routing[$controller_mft->params['uri']] = $controller_mft->id;
				}

				// [TODO] Ask the platform to look at any routing maps (extension manifest) or
				// controller objects
//				print_r($routing);

				// [TODO] Pages like 'tickets' currently work because APP_DEFAULT_CONTROLLER
				// is the ChPageController which looks up those URIs in manifests
	            
				if(empty($controllers))
					die("No controllers are available!");
				
				// Set our controller based on the results
				$controller_mft = (isset($routing[$controller_uri]))
					? $controllers[$routing[$controller_uri]]
					: $controllers[APP_DEFAULT_CONTROLLER];
				
				// Instance our manifest
				if(!empty($controller_mft)) {
					$controller = $controller_mft->createInstance();
				}
				
				if($controller instanceof DevblocksHttpRequestHandler) {
					$controller->handleRequest($request);
					
					// [JAS]: If we didn't write a new response, repeat the request
					if(null == ($response = DevblocksPlatform::getHttpResponse())) {
						$response = new DevblocksHttpResponse($request->path);
						DevblocksPlatform::setHttpResponse($response);
					}
					
					// [JAS]: An Ajax request doesn't need the full Http cycle
					if(!$is_ajax) {
						$controller->writeResponse($response);
					}
					
				} else {
				    header("Status: 404");
                    die(); // [TODO] Improve
				}
					
				break;
		}
		
		return;
	}
	
	static function update() {
		if(null == ($manifest = self::_readPluginManifest('libs/devblocks', false)))
			return FALSE;

		if(!isset($manifest->manifest_cache['patches']))
			return TRUE;
		
		foreach($manifest->manifest_cache['patches'] as $mft_patch) {
			$path = APP_PATH . '/' . $manifest->dir . '/' . $mft_patch['file'];
			
			if(!file_exists($path))
				return FALSE;
			
			$patch = new DevblocksPatch($manifest->id, $mft_patch['version'], $mft_patch['revision'], $path);
			if(!$patch->run())
				return FALSE;
		}
		
		return TRUE;
	}
	
};

class _DevblocksPluginSettingsManager {
	private static $_instance = null;
	private $_settings = array();
	
	/**
	 * @return _DevblocksPluginSettingsManager
	 */
	private function __construct() {
	    // Defaults (dynamic)
		$plugin_settings = DAO_DevblocksSetting::getSettings();
		foreach($plugin_settings as $plugin_id => $kv) {
			if(!isset($this->_settings[$plugin_id]))
				$this->_settings[$plugin_id] = array();
				
			if(is_array($kv))
			foreach($kv as $k => $v)
				$this->_settings[$plugin_id][$k] = $v;
		}
	}
	
	/**
	 * @return _DevblocksPluginSettingsManager
	 */
	public static function getInstance() {
		if(self::$_instance==null) {
			self::$_instance = new _DevblocksPluginSettingsManager();	
		}
		
		return self::$_instance;		
	}
	
	public function set($plugin_id,$key,$value) {
		DAO_DevblocksSetting::set($plugin_id,$key,$value);
		
		if(!isset($this->_settings[$plugin_id]))
			$this->_settings[$plugin_id] = array();
		
		$this->_settings[$plugin_id][$key] = $value;
		
	    $cache = DevblocksPlatform::getCacheService();
		$cache->remove(DevblocksPlatform::CACHE_SETTINGS);
		
		return TRUE;
	}
	
	/**
	 * @param string $key
	 * @param string $default
	 * @return mixed
	 */
	public function get($plugin_id,$key,$default=null) {
		if(isset($this->_settings[$plugin_id][$key]))
			return $this->_settings[$plugin_id][$key];
		else 
			return $default;
	}
};

/**
 * Session Management Singleton
 *
 * @static 
 * @ingroup services
 */
class _DevblocksSessionManager {
	var $visit = null;
	
	/**
	 * @private
	 */
	private function _DevblocksSessionManager() {}
	
	/**
	 * Returns an instance of the session manager
	 *
	 * @static
	 * @return _DevblocksSessionManager
	 */
	static function getInstance() {
		static $instance = null;
		if(null == $instance) {
		    $db = DevblocksPlatform::getDatabaseService();
		    $url_writer = DevblocksPlatform::getUrlService();
		    
			if(is_null($db) || !$db->isConnected()) { 
				return null;
			}
			
			$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup
			
			@session_destroy();
			
			$handler = '_DevblocksSessionDatabaseDriver';
			
			session_set_save_handler(
				array($handler, 'open'),
				array($handler, 'close'),
				array($handler, 'read'),
				array($handler, 'write'),
				array($handler, 'destroy'),
				array($handler, 'gc')
			);
			
			session_name(APP_SESSION_NAME);
			session_set_cookie_params(0, '/', NULL, $url_writer->isSSL(), true);
			session_start();
			
			$instance = new _DevblocksSessionManager();
			$instance->visit = isset($_SESSION['db_visit']) ? $_SESSION['db_visit'] : NULL; /* @var $visit DevblocksVisit */
		}
		
		return $instance;
	}
	
	function decodeSession($data) {
		$vars=preg_split(
			'/([a-zA-Z_\.\x7f-\xff][a-zA-Z0-9_\.\x7f-\xff^|]*)\|/',
			$data,
			-1,
			PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE
		);
		
		$scope = array();
		
		while(!empty($vars)) {
			@$key = array_shift($vars);
			@$value = unserialize(array_shift($vars));
			$scope[$key] = $value;
		}
		
		return $scope; 		
	}
	
	/**
	 * Returns the current session or NULL if no session exists.
	 * 
	 * @return DevblocksVisit
	 */
	function getVisit() {
		return $this->visit;
	}
	
	/**
	 * @param DevblocksVisit $visit
	 */
	function setVisit(DevblocksVisit $visit = null) {
		$this->visit = $visit;
		$_SESSION['db_visit'] = $this->visit;
	}
	
	function getAll() {
		return _DevblocksSessionDatabaseDriver::getAll();
	}
	
	/**
	 * Kills the specified or current session.
	 *
	 */
	function clear($key=null) {
		if(is_null($key)) {
			$this->visit = null;
			unset($_SESSION['db_visit']);
			session_destroy();
		} else {
			_DevblocksSessionDatabaseDriver::destroy($key);
		}
	}
	
	function clearAll() {
		self::clear();
		// [TODO] Allow subclasses to be cleared here too
		_DevblocksSessionDatabaseDriver::destroyAll();
	}
};

class _DevblocksSessionDatabaseDriver {
	static $_data = null;
	
	static function open($save_path, $session_name) {
		return true;
	}
	
	static function close() {
		return true;
	}
	
	static function read($id) {
		$db = DevblocksPlatform::getDatabaseService();
		if(null != (self::$_data = $db->GetOne(sprintf("SELECT session_data FROM devblocks_session WHERE session_key = %s", $db->qstr($id)))))
			return self::$_data;
			
		return false;
	}
	
	static function write($id, $session_data) {
		// Nothing changed!
		if(self::$_data==$session_data) {
			return true;
		}
		
		$db = DevblocksPlatform::getDatabaseService();
		
		// Update
		$result = $db->Execute(sprintf("UPDATE devblocks_session SET updated=%d, session_data=%s WHERE session_key=%s",
			time(),
			$db->qstr($session_data),
			$db->qstr($id)
		));
		
		if(0==$db->Affected_Rows()) {
			// Insert
			$db->Execute(sprintf("INSERT INTO devblocks_session (session_key, created, updated, session_data) ".
				"VALUES (%s, %d, %d, %s)",
				$db->qstr($id),
				time(),
				time(),
				$db->qstr($session_data)
			));
		}
		
		return true;
	}
	
	static function destroy($id) {
		$db = DevblocksPlatform::getDatabaseService();
		$db->Execute(sprintf("DELETE FROM devblocks_session WHERE session_key = %s", $db->qstr($id)));
		return true;
	}
	
	static function gc($maxlifetime) {
		$db = DevblocksPlatform::getDatabaseService();
		$db->Execute(sprintf("DELETE FROM devblocks_session WHERE updated + %d < %d", $maxlifetime, time()));
		return true;
	}
	
	static function getAll() {
		$db = DevblocksPlatform::getDatabaseService();
		return $db->GetArray("SELECT session_key, created, updated, session_data FROM devblocks_session");
	}
	
	static function destroyAll() {
		$db = DevblocksPlatform::getDatabaseService();
		$db->Execute("DELETE FROM devblocks_session");
	}
};

class _DevblocksOpenIDManager {
	private static $instance = null;
	
	public static function getInstance() {
		if(null == self::$instance) {
			self::$instance = new _DevblocksOpenIDManager();
		}
		
		return self::$instance;
	}
	
	public function discover($url) {
		$num_redirects = 0;
		$is_safemode = !(ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off'));
		
		do {
			$repeat = false;
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			// We can't use option this w/ safemode enabled
			if(!$is_safemode)
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			
			$content = curl_exec($ch);
			$info = curl_getinfo($ch);
			curl_close($ch);
	
			$lines = explode("\n", $content);

			$headers = array();
			$content = '';

			$is_headers = true;
			while($line = array_shift($lines)) {
				if($is_headers && $line == "\r") {
					// Is the next line another headers block?
					$line = array_shift($lines);
					if(preg_match("/^HTTP\/\S+ \d+/i", $line)) {
						$headers = array(); // flush
					} else {
						$is_headers = false;
						array_unshift($lines, $line);
						continue;
					}
				}
				
				if($is_headers) {
					$headers[] = $line;
				} else {
					// Everything else
					$content = $line . "\n" . implode("\n", $lines);
					$lines = array();
				}
			}
			
			unset($lines);
			
			// Scan headers
			foreach($headers as $header) {
				// Safemode specific behavior
				if($is_safemode) {
					if(preg_match("/^Location:.*?/i", $header)) {
						$out = explode(':', $header, 2);
						$url = isset($out[1]) ? trim($out[1]) : null;
						$repeat = true;
						break;
					}
				}
				
				// Check the headers for an 'X-XRDS-Location'
				if(preg_match("/^X-XRDS-Location:.*?/i", $header)) {
					$out = explode(':', $header, 2);
					$xrds_url = isset($out[1]) ? trim($out[1]) : null;
					
					// We have a redirect header on an XRDS document
					if(0 == strcasecmp($xrds_url, $url)) {
						$repeat = false;
						
					// We're being redirected
					} else {
						$repeat = true;
						$headers = array();
						$url = $xrds_url;
					}
					
					break;
				}
			}
			
		} while($repeat || ++$num_redirects > 10);
		
		if(isset($info['content_type']))  {
			$result = explode(';', $info['content_type']);
			$type = isset($result[0]) ? trim($result[0]) : null;
			
			$server = null;
			
			switch($type) {
				case 'application/xrds+xml':
					$xml = simplexml_load_string($content);
					
					foreach($xml->XRD->Service as $service) {
						$types = array();
						foreach($service->Type as $type) {
							$types[] = $type;
						}

						// [TODO] OpenID 1.0
						if(false !== ($pos = array_search('http://specs.openid.net/auth/2.0/server', $types))) {
							$server = $service->URI;
						} elseif(false !== ($pos = array_search('http://specs.openid.net/auth/2.0/signon', $types))) {
							$server = $service->URI;
						}
					}
					break;
					
				case 'text/html':
					// [TODO] This really needs to parse syntax better (can be single or double quotes, and attribs in any order)
					preg_match("/<link rel=\"openid.server\" href=\"(.*?)\"/", $content, $found);
					if($found && isset($found[1]))
						$server = $found[1];
						
					preg_match("/<link rel=\"openid.delegate\" href=\"(.*?)\"/", $content, $found);
					if($found && isset($found[1]))
						$delegate = $found[1];
						
					break;
					
				default:
					break;
			}

			return $server;
		}		
	}
	
	public function getAuthUrl($openid_identifier, $return_to) {
		$url_writer = DevblocksPlatform::getUrlService();
		
		// Normalize the URL
		$parts = parse_url($openid_identifier);
		if(!isset($parts['scheme'])) {
			$openid_identifier = 'http://' . $openid_identifier;
		}
		
		$server = $this->discover($openid_identifier);
		
		if(empty($server))
			return FALSE;
		
		$parts = explode('?', $server, 2);
		$url = isset($parts[0]) ? $parts[0] : '';
		$query = isset($parts[1]) ? ('?'.$parts[1]) : '';
		
		$query .= (!empty($query)) ? '&' : '?';
		$query .= "openid.mode=checkid_setup";
		$query .= "&openid.claimed_id=".urlencode("http://specs.openid.net/auth/2.0/identifier_select");
		$query .= "&openid.identity=".urlencode("http://specs.openid.net/auth/2.0/identifier_select");
		$query .= "&openid.realm=".urlencode($url_writer->write('',true));
		$query .= "&openid.ns=".urlencode("http://specs.openid.net/auth/2.0");
		$query .= "&openid.return_to=".urlencode($return_to);
		
		// AX 1.0 (axschema)
		$query .= "&openid.ns.ax=".urlencode("http://openid.net/srv/ax/1.0");
		$query .= "&openid.ax.mode=".urlencode("fetch_request");
		$query .= "&openid.ax.type.nickname=".urlencode('http://axschema.org/namePerson/friendly');
		$query .= "&openid.ax.type.fullname=".urlencode('http://axschema.org/namePerson');
		$query .= "&openid.ax.type.email=".urlencode('http://axschema.org/contact/email');
		$query .= "&openid.ax.required=".urlencode('email,nickname,fullname');
		
		// SREG 1.1
		$query .= "&openid.ns.sreg=".urlencode('http://openid.net/extensions/sreg/1.1');
		$query .= "&openid.sreg.required=".urlencode("nickname,fullname,email");
		$query .= "&openid.sreg.optional=".urlencode("dob,gender,postcode,country,language,timezone");
		
		return $url.$query;
	}
	
	public function validate($scope) {
		$url_writer = DevblocksPlatform::getUrlService();
		
		$openid_identifier = $scope['openid_identity'];
		
		$server = $this->discover($openid_identifier);
		
		$parts = explode('?', $server, 2);
		$url = isset($parts[0]) ? $parts[0] : '';
		$query = isset($parts[1]) ? ('?'.$parts[1]) : '';
		
		$query .= (!empty($query)) ? '&' : '?';
		$query .= "openid.ns=".urlencode("http://specs.openid.net/auth/2.0");
		$query .= "&openid.mode=check_authentication";
		$query .= "&openid.sig=".urlencode($_GET['openid_sig']);
		$query .= "&openid.signed=".urlencode($_GET['openid_signed']);
		
		// Append all the tokens used in the signed
		$tokens = explode(',', $scope['openid_signed']);
		foreach($tokens as $token) {
			switch($token) {
				case 'mode':
				case 'ns':
				case 'sig':
				case 'signed':
					break;
					
				default:
					$key = str_replace('.', '_', $token);
					
					if(isset($scope['openid_'.$key])) {
						$query .= sprintf("&openid.%s=%s",
							$token,
							urlencode($scope['openid_'.$key])
						);
					}
					break;
			}
		}
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url.$query);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($ch);
		curl_close($ch);
		
		if(preg_match('/is_valid:true/', $response))
			return true;
		else
			return false;
	}
	
	public function getAttributes($scope) {
		$ns = array();
		$attribs = array();
		
		foreach($scope as $ns => $spec) {
			// Namespaces
			if(preg_match("/^openid_ns_(.*)$/",$ns,$ns_found)) {
				switch(strtolower($spec)) {
					case 'http://openid.net/srv/ax/1.0';
						foreach($scope as $k => $v) {
							if(preg_match("/^openid_".$ns_found[1]."_value_(.*)$/i",$k,$attrib_found)) {
								$attribs[strtolower($attrib_found[1])] = $v;
							}
						}
						break;
						
					case 'http://openid.net/srv/sreg/1.0';
					case 'http://openid.net/extensions/sreg/1.1';
						foreach($scope as $k => $v) {
							if(preg_match("/^openid_".$ns_found[1]."_(.*)$/i",$k,$attrib_found)) {
								$attribs[strtolower($attrib_found[1])] = $v;
							}
						}
						break;
				}
			}
		}
		
		return $attribs;
	}
};

class _DevblocksCacheManager {
    private static $instance = null;
    private static $_cacher = null;
	private $_registry = array();
	private $_statistics = array();
	private $_io_reads_long = 0;
	private $_io_reads_short = 0;
	private $_io_writes = 0;
    
    private function __construct() {}

    /**
     * @return _DevblocksCacheManager
     */
    public static function getInstance() {
		if(null == self::$instance) {
			self::$instance = new _DevblocksCacheManager();
			
			$options = array(
				'key_prefix' => ((defined('DEVBLOCKS_CACHE_PREFIX') && DEVBLOCKS_CACHE_PREFIX) ? DEVBLOCKS_CACHE_PREFIX : null), 
			);
			
			// Shared-memory cache
		    if((extension_loaded('memcache') || extension_loaded('memcached')) 
		    	&& defined('DEVBLOCKS_MEMCACHED_SERVERS') && DEVBLOCKS_MEMCACHED_SERVERS) {
		    	$pairs = DevblocksPlatform::parseCsvString(DEVBLOCKS_MEMCACHED_SERVERS);
		    	$servers = array();
		    	
		    	if(is_array($pairs) && !empty($pairs))
		    	foreach($pairs as $server) {
		    		list($host,$port) = explode(':',$server);
		    		
		    		if(empty($host) || empty($port))
		    			continue;
		    			
		    		$servers[] = array(
		    			'host'=>$host,
		    			'port'=>$port,
//		    			'persistent'=>true
		    		);
		    	}
		    	
				$options['servers'] = $servers;
				
				self::$_cacher = new _DevblocksCacheManagerMemcached($options);
		    }

		    // Disk-based cache (default)
		    if(null == self::$_cacher) {
		    	$options['cache_dir'] = APP_TEMP_PATH; 
				
				self::$_cacher = new _DevblocksCacheManagerDisk($options);
		    }
		}
		
		return self::$instance;
    }
    
	public function save($data, $key, $tags=array(), $lifetime=0) {
		// Monitor short-term cache memory usage
		@$this->_statistics[$key] = intval($this->_statistics[$key]);
		$this->_io_writes++;
		self::$_cacher->save($data, $key, $tags, $lifetime);
		$this->_registry[$key] = $data;
	}
	
	public function load($key, $nocache=false) {
		// Retrieving the long-term cache
		if($nocache || !isset($this->_registry[$key])) {
			if(false === ($this->_registry[$key] = self::$_cacher->load($key)))
				return NULL;
			
			@$this->_statistics[$key] = intval($this->_statistics[$key]) + 1;
			$this->_io_reads_long++;
			return $this->_registry[$key];
		}
		
		// Retrieving the short-term cache
		if(isset($this->_registry[$key])) {
			@$this->_statistics[$key] = intval($this->_statistics[$key]) + 1;
			$this->_io_reads_short++;
			return $this->_registry[$key];
		}
		
		return NULL;
	}
	
	public function remove($key) {
		unset($this->_registry[$key]);
		unset($this->_statistics[$key]);
		self::$_cacher->remove($key);
	}
	
	public function clean() { // $mode=null
		$this->_registry = array();
		$this->_statistics = array();
		
		self::$_cacher->clean();
	}
	
	public function printStatistics() {
		arsort($this->_statistics);
		print_r($this->_statistics);
		echo "<BR>";
		echo "Reads (short): ",$this->_io_reads_short,"<BR>";
		echo "Reads (long): ",$this->_io_reads_long,"<BR>";
		echo "Writes: ",$this->_io_writes,"<BR>";
	}
};

abstract class _DevblocksCacheManagerAbstract {
	protected $_options;
	protected $_prefix = 'devblocks_cache---';
	
	function __construct($options) {
		if(is_array($options))
			$this->_options = $options;
		
		// Key prefix
		if(!isset($this->_options['key_prefix']))
			$this->_options['key_prefix'] = '';
	}
	
	function save($data, $key, $tags=array(), $lifetime=0) {}
	function load($key) {}
	function remove($key) {}
	function clean() {} // $mode=null
};

class _DevblocksCacheManagerMemcached extends _DevblocksCacheManagerAbstract {
	private $_driver;
	
	function __construct($options) {
		parent::__construct($options);
		
		if(extension_loaded('memcached'))
			$this->_driver = new Memcached();
		elseif(extension_loaded('memcache'))
			$this->_driver = new Memcache();
		else
			die("PECL/Memcache or PECL/Memcached is not loaded.");
			
		// Check servers option
		if(!isset($this->_options['servers']) || !is_array($this->_options['servers']))
			die("_DevblocksCacheManagerMemcached requires the 'servers' option.");
			
		if(is_array($this->_options['servers']))
		foreach($this->_options['servers'] as $params) {
			$this->_driver->addServer($params['host'], $params['port']);
		}
	}
	
	function save($data, $key, $tags=array(), $lifetime=0) {
		$key = $this->_options['key_prefix'] . $key;
		
		if($this->_driver instanceof Memcached)
			return $this->_driver->set($key, $data, $lifetime);
		else
			return $this->_driver->set($key, $data, 0, $lifetime);
	}
	
	function load($key) {
		$key = $this->_options['key_prefix'] . $key;
		return $this->_driver->get($key);
	}
	
	function remove($key) {
		$key = $this->_options['key_prefix'] . $key;
		$this->_driver->delete($key);
	}
	
	function clean() {
		$this->_driver->flush();
	}
};

class _DevblocksCacheManagerDisk extends _DevblocksCacheManagerAbstract {
	function __construct($options) {
		parent::__construct($options);

		$path = $this->_getPath();
		
		if(null == $path)
			die("_DevblocksCacheManagerDisk requires the 'cache_dir' option.");

		// Ensure we have a trailing slash
		$this->_options['cache_dir'] = rtrim($path,"\\/") . DIRECTORY_SEPARATOR;
			
		if(!is_writeable($path))
			die("_DevblocksCacheManagerDisk requires write access to the 'path' directory ($path)");
	}
	
	private function _getPath() {
		return $this->_options['cache_dir'];
	}
	
	private function _getFilename($key) {
		$safe_key = preg_replace("/[^A-Za-z0-9_\-]/",'_', $key);
		return $this->_prefix . $safe_key;
	}
	
	function load($key) {
		$key = $this->_options['key_prefix'] . $key;
		return @unserialize(file_get_contents($this->_getPath() . $this->_getFilename($key)));
	}
	
	function save($data, $key, $tags=array(), $lifetime=0) {
		$key = $this->_options['key_prefix'] . $key;
		return file_put_contents($this->_getPath() . $this->_getFilename($key), serialize($data));
	}
	
	function remove($key) {
		$key = $this->_options['key_prefix'] . $key;
		$file = $this->_getPath() . $this->_getFilename($key);
		if(file_exists($file))
			unlink($file);
	}
	
	function clean() {
		$path = $this->_getPath();
		
		$files = scandir($path);
		unset($files['.']);
		unset($files['..']);
		
		if(is_array($files))
		foreach($files as $file) {
			if(0==strcmp('devblocks_cache',substr($file,0,15))) {
				unlink($path . $file);
			}
		}
		
	}	
};

class _DevblocksStorageManager {
	static $_connections = array();
	
	/**
	 * 
	 * @param string $extension_id
	 * @param array $options
	 * @return Extension_DevblocksStorageEngine
	 */
	static public function getEngine($extension_id, $options=array()) {
		$hash = sha1($extension_id.json_encode($options));
		
		if(isset(self::$_connections[$hash])) {
			return self::$_connections[$hash];
		}
		
		if(null !== ($engine = DevblocksPlatform::getExtension($extension_id, true, true))) {
			/* @var $engine Extension_DevblocksStorageEngine */
			$engine->setOptions($options);
			self::$_connections[$hash] = $engine;
			return self::$_connections[$hash];
		}
		
		return false;
	}
};

class _DevblocksSearchManager {
	static $_instance = null;
	
	/**
	 * @return _DevblocksSearchEngineMysqlFulltext
	 */
	static public function getInstance() {
		if(null == self::$_instance) {
			self::$_instance = new _DevblocksSearchEngineMysqlFulltext();
			return self::$_instance;
		}
		
		return self::$_instance;
	}
};

class _DevblocksSearchEngineMysqlFulltext {
	private $_db = null;
	
	public function __construct() {
		$db = DevblocksPlatform::getDatabaseService();
		$this->_db = $db->getConnection();
	}
	
	protected function escapeNamespace($namespace) {
		return strtolower(DevblocksPlatform::strAlphaNumUnder($namespace));
	}
	
	public function query($ns, $query, $limit=25, $boolean_mode=true) {
		$escaped_query = mysql_real_escape_string($query);
		
		// [TODO] Process the query

		if(!$boolean_mode) {
			$result = mysql_query(sprintf("SELECT id ".
				"FROM fulltext_%s ".
				"WHERE MATCH content AGAINST ('%s') ".
				"LIMIT 0,%d ",
				$this->escapeNamespace($ns),
				$escaped_query,
				$limit
			), $this->_db);
			
		} else {
			$result = mysql_query(sprintf("SELECT id, MATCH content AGAINST ('%s' IN BOOLEAN MODE) AS score ".
				"FROM fulltext_%s ".
				"WHERE MATCH content AGAINST ('%s' IN BOOLEAN MODE) ".
				"ORDER BY score DESC ".
				"LIMIT 0,%d ",
				$escaped_query,
				$this->escapeNamespace($ns),
				$escaped_query,
				$limit
			), $this->_db);
		}
		
		if(false == $result)
			return false;
			
		$ids = array();
		
		while($row = mysql_fetch_row($result)) {
			$ids[] = $row[0];
		}
		
		return $ids;
	}
	
	private function _getStopWords() {
	    // English
		$words = array(
			'' => true,
			'a' => true,
			'about' => true,
			'all' => true,
			'am' => true,
			'an' => true,
			'and' => true,
			'any' => true,
			'as' => true,
			'at' => true,
			'are' => true,
			'be' => true,
			'been' => true,
			'but' => true,
			'by' => true,
			'can' => true,
			'could' => true,
			'did' => true,
			'do' => true,
			'doesn\'t' => true,
			'don\'t' => true,
			'e.g.' => true,
			'eg' => true,
			'for' => true,
			'from' => true,
			'get' => true,
			'had' => true,
			'has' => true,
			'have' => true,
			'hello' => true,
			'hi' => true,
			'how' => true,
			'i' => true,
			'i.e.' => true,
			'ie' => true,
			'i\'m' => true,
			'if' => true,
			'in' => true,
			'into' => true,
			'is' => true,
			'it' => true,
			'it\'s' => true,
			'its' => true,
			'may' => true,
			'me' => true,
			'my' => true,
			'not' => true,
			'of' => true,
			'on' => true,
			'or' => true,
			'our' => true,
			'out' => true,
			'please' => true,
			'p.s.' => true,
			'ps' => true,
			'so' => true,
			'than' => true,
			'thank' => true,
			'thanks' => true,
			'that' => true,
			'the' => true,
			'their' => true,
			'them' => true,
			'then' => true,
			'there' => true,
			'these' => true,
			'they' => true,
			'this' => true,
			'those' => true,
			'to' => true,
			'us' => true,
			'want' => true,
			'was' => true,
			'we' => true,
			'were' => true,
			'what' => true,
			'when' => true,
			'which' => true,
			'while' => true,
			'why' => true,
			'will' => true,
			'with' => true,
			'would' => true,
			'you' => true,
			'your' => true,
			'you\'re' => true,
		);
	    return $words;
	}
	
	public function prepareText($text) {
		// Encode apostrophes/etc
		$tokens = array(
			'__apos__' => '\''
		);

		$text = str_replace(array_values($tokens), array_keys($tokens), $text);
		
		// Force lowercase and strip non-word punctuation (a-z, 0-9, _)
		if(function_exists('mb_ereg_replace'))
			$text = mb_ereg_replace('[^a-z0-9_]+', ' ', mb_convert_case($text, MB_CASE_LOWER));
		else
			$text = preg_replace('/[^a-z0-9_]+/', ' ', mb_convert_case($text, MB_CASE_LOWER));

		// Decode apostrophes/etc
		$text = str_replace(array_keys($tokens), array_values($tokens), $text);
		
		$words = explode(' ', $text);
		
		// Remove common words
		$stop_words = $this->_getStopWords();

		// Toss anything over/under the word length bounds
		// [TODO] Make these configurable
		foreach($words as $k => $v) {
			//$len = mb_strlen($v);
//			if($len < 3 || $len > 255) { // || is_numeric($k)
//				unset($words[$k]); // toss
//			} elseif(isset($stop_words[$v])) {

			if(isset($stop_words[$v])) {
				unset($words[$k]); // toss
			}
		}
		
		$text = implode(' ', $words);
		unset($words);
		
		// Flatten multiple spaces into a single
		$text = preg_replace('# +#', ' ', $text);
		
		return $text;
	}
	
	private function _index($ns, $id, $content) {
		$content = $this->prepareText($content);
		
		$result = mysql_query(sprintf("REPLACE INTO fulltext_%s VALUES (%d, '%s') ",
			$this->escapeNamespace($ns),
			$id,
			mysql_real_escape_string($content)
		), $this->_db);
		
		return (false !== $result) ? true : false;
	}
	
	public function index($ns, $id, $content) {
		if(false === ($ids = $this->_index($ns, $id, $content))) {
			// Create the table dynamically
			if($this->_createTable($ns)) {
				return $this->_index($ns, $id, $content);
			}
			return false;
		}
		
		return true;
	}
	
	private function _createTable($namespace) {
		$rs = mysql_query("SHOW TABLES", $this->_db);

		$tables = array();
		while($row = mysql_fetch_row($rs)) {
			$tables[$row[0]] = true;
		}
		
		$namespace = $this->escapeNamespace($namespace);
		
		if(isset($tables['fulltext_'.$namespace]))
			return true;
		
		$result = mysql_query(sprintf(
			"CREATE TABLE IF NOT EXISTS fulltext_%s (
				id INT UNSIGNED NOT NULL DEFAULT 0,
				content LONGTEXT,
				PRIMARY KEY (id),
				FULLTEXT content (content)
			) ENGINE=MyISAM CHARACTER SET=utf8;", // MUST stay ENGINE=MyISAM
			$this->escapeNamespace($namespace)
		), $this->_db);
		
		DevblocksPlatform::clearCache(DevblocksPlatform::CACHE_TABLES);
		
		return (false !== $result) ? true : false;
	}
	
	public function delete($ns, $ids) {
		if(!is_array($ids))
			$ids = array($ids);
			
		if(empty($ns) || empty($ids))
			return;
			
		$result = mysql_query(sprintf("DELETE FROM fulltext_%s WHERE id IN (%s) ",
			$this->escapeNamespace($ns),
			implode(',', $ids)
		), $this->_db);
		
		return (false !== $result) ? true : false;
	}
};

class _DevblocksEventManager {
    private static $instance = null;
    
    private function __construct() {}

    /**
     * @return _DevblocksEventManager
     */
	public static function getInstance() {
		if(null == self::$instance) {
			self::$instance = new _DevblocksEventManager();
		}
		return self::$instance;
	}
	
	function trigger(Model_DevblocksEvent $event) {
	    /*
	     * [TODO] Look at the hash and spawn our listeners for this particular point
	     */
		$events = DevblocksPlatform::getEventRegistry();

		if(null == ($listeners = @$events[$event->id])) {
		    $listeners = array();
		}

		// [TODO] Make sure we can't get a double listener
	    if(isset($events['*']) && is_array($events['*']))
	    foreach($events['*'] as $evt) {
	        $listeners[] = $evt;
	    }
		
		if(is_array($listeners) && !empty($listeners))
		foreach($listeners as $listener) { /* @var $listener DevblocksExtensionManifest */
			// Extensions can be invoked on these plugins even by workers who cannot see them
            if(null != ($manifest = DevblocksPlatform::getExtension($listener,false,true))) {
            	if(method_exists($manifest, 'createInstance')) {
		    		$inst = $manifest->createInstance(); /* @var $inst DevblocksEventListenerExtension */
		    		if($inst instanceof DevblocksEventListenerExtension)
            			$inst->handleEvent($event);
            	}
            }
		}
		
	}
};

/**
 * Email Management Singleton
 *
 * @static 
 * @ingroup services
 */
class _DevblocksEmailManager {
    private static $instance = null;
    
    private $mailers = array();
    
	/**
	 * @private
	 */
	private function __construct() {
		
	}
	
	/**
	 * Enter description here...
	 *
	 * @return _DevblocksEmailManager
	 */
	public static function getInstance() {
		if(null == self::$instance) {
			self::$instance = new _DevblocksEmailManager();
		}
		return self::$instance;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return Swift_Message
	 */
	function createMessage() {
		return Swift_Message::newInstance();
	}
	
	/**
	 * @return Swift
	 */
	function getMailer($options) {

		// Options
		$smtp_host = isset($options['host']) ? $options['host'] : '127.0.0.1'; 
		$smtp_port = isset($options['port']) ? $options['port'] : '25'; 
		$smtp_user = isset($options['auth_user']) ? $options['auth_user'] : null; 
		$smtp_pass = isset($options['auth_pass']) ? $options['auth_pass'] : null; 
		$smtp_enc = isset($options['enc']) ? $options['enc'] : 'None'; 
		$smtp_max_sends = isset($options['max_sends']) ? intval($options['max_sends']) : 20; 
		$smtp_timeout = isset($options['timeout']) ? intval($options['timeout']) : 30; 
		
		/*
		 * [JAS]: We'll cache connection info hashed by params and hold a persistent 
		 * connection for the request cycle.  If we ask for the same params again 
		 * we'll get the existing connection if it exists.
		 */
		$hash = md5(sprintf("%s %s %s %s %s %d %d",
			$smtp_host,
			$smtp_user,
			$smtp_pass,
			$smtp_port,
			$smtp_enc,
			$smtp_max_sends,
			$smtp_timeout
		));
		
		if(!isset($this->mailers[$hash])) {
			// Encryption
			switch($smtp_enc) {
				case 'TLS':
					$smtp_enc = 'tls';
					break;
					
				case 'SSL':
					$smtp_enc = 'ssl';
					break;
					
				default:
					$smtp_enc = null;
					break;
			}
			
			$smtp = Swift_SmtpTransport::newInstance($smtp_host, $smtp_port, $smtp_enc);
			$smtp->setTimeout($smtp_timeout);
			
			if(!empty($smtp_user) && !empty($smtp_pass)) {
				$smtp->setUsername($smtp_user);
				$smtp->setPassword($smtp_pass);
			}
			
			$mailer = Swift_Mailer::newInstance($smtp);
			$mailer->registerPlugin(new Swift_Plugins_AntiFloodPlugin($smtp_max_sends,1));
			
			$this->mailers[$hash] =& $mailer;
		}

		return $this->mailers[$hash];
	}
	
	function testImap($server, $port, $service, $username, $password) {
		if (!extension_loaded("imap")) die("IMAP Extension not loaded!");
		
        switch($service) {
            default:
            case 'pop3': // 110
                $connect = sprintf("{%s:%d/pop3/notls}INBOX",
                $server,
                $port
                );
                break;
                 
            case 'pop3-ssl': // 995
                $connect = sprintf("{%s:%d/pop3/ssl/novalidate-cert}INBOX",
                $server,
                $port
                );
                break;
                 
            case 'imap': // 143
                $connect = sprintf("{%s:%d/notls}INBOX",
                $server,
                $port
                );
                break;
                
            case 'imap-ssl': // 993
                $connect = sprintf("{%s:%d/imap/ssl/novalidate-cert}INBOX",
                $server,
                $port
                );
                break;
        }
		
		@$mailbox = imap_open(
			$connect,
			!empty($username)?$username:"superuser",
			!empty($password)?$password:"superuser"
		);

		if($mailbox === FALSE)
			return FALSE;
		
		@imap_close($mailbox);
			
		return TRUE;
	}
	
	/**
	 * @return array
	 */
	function getErrors() {
		return imap_errors();
	}
	
}

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
}

class _DevblocksTranslationManager {
	private $_locales = array();
	private $_locale = 'en_US';
	
	private function __construct() {}
	
	static function getInstance() {
		static $instance = null;
		
		if(null == $instance) {
			$instance = new _DevblocksTranslationManager();
		}
		
		return $instance;
	}
	
	public function addLocale($locale, $strings) {
		$this->_locales[$locale] = $strings;
	}
	
	public function setLocale($locale) {
		if(isset($this->_locales[$locale]))
			$this->_locale = $locale;
	}
	
	public function _($token) {
		if(isset($this->_locales[$this->_locale][$token]))
			return $this->_locales[$this->_locale][$token];
		
		// [JAS] Make it easy to find things that don't translate
		//return '$'.$token.'('.$this->_locale.')';
		
		return $token;
	}
	
	public function getLocaleCodes() {
		return array(
			'af_ZA',
			'am_ET',
			'be_BY',
			'bg_BG',
			'ca_ES',
			'cs_CZ',
			'da_DK',
			'de_AT',
			'de_CH',
			'de_DE',
			'el_GR',
			'en_AU',
			'en_CA',
			'en_GB',
			'en_IE',
			'en_NZ',
			'en_US',
			'es_ES',
			'es_MX',
			'et_EE',
			'eu_ES',
			'fi_FI',
			'fr_BE',
			'fr_CA',
			'fr_CH',
			'fr_FR',
			'he_IL',
			'hr_HR',
			'hu_HU',
			'hy_AM',
			'is_IS',
			'it_CH',
			'it_IT',
			'ja_JP',
			'kk_KZ',
			'ko_KR',
			'lt_LT',
			'nl_BE',
			'nl_NL',
			'no_NO',
			'pl_PL',
			'pt_BR',
			'pt_PT',
			'ro_RO',
			'ru_RU',
			'sk_SK',
			'sl_SI',
			'sr_RS',
			'sv_SE',
			'tr_TR',
			'uk_UA',
			'zh_CN',
			'zh_HK',
			'zh_TW',
		);
	}
	
	function getLocaleStrings() {
		$codes = $this->getLocaleCodes();
		$langs = $this->getLanguageCodes();
		$countries = $this->getCountryCodes();
		
		$lang_codes = array();
		
		if(is_array($codes))
		foreach($codes as $code) {
			$data = explode('_', $code);
			@$lang = $langs[strtolower($data[0])];
			@$terr = $countries[strtoupper($data[1])];

			$lang_codes[$code] = (!empty($lang) && !empty($terr))
				? ($lang . ' (' . $terr . ')')
				: $code;
		}
		
		asort($lang_codes);
		
		unset($codes);
		unset($langs);
		unset($countries);
		
		return $lang_codes;
	}
	
	function getLanguageCodes() {
		return array(
			'aa' => "Afar",
			'ab' => "Abkhazian",
			'ae' => "Avestan",
			'af' => "Afrikaans",
			'am' => "Amharic",
			'an' => "Aragonese",
			'ar' => "Arabic",
			'as' => "Assamese",
			'ay' => "Aymara",
			'az' => "Azerbaijani",
			'ba' => "Bashkir",
			'be' => "Belarusian",
			'bg' => "Bulgarian",
			'bh' => "Bihari",
			'bi' => "Bislama",
			'bn' => "Bengali",
			'bo' => "Tibetan",
			'br' => "Breton",
			'bs' => "Bosnian",
			'ca' => "Catalan",
			'ce' => "Chechen",
			'ch' => "Chamorro",
			'co' => "Corsican",
			'cs' => "Czech",
			'cu' => "Church Slavic; Slavonic; Old Bulgarian",
			'cv' => "Chuvash",
			'cy' => "Welsh",
			'da' => "Danish",
			'de' => "German",
			'dv' => "Divehi; Dhivehi; Maldivian",
			'dz' => "Dzongkha",
			'el' => "Greek, Modern",
			'en' => "English",
			'eo' => "Esperanto",
			'es' => "Spanish; Castilian",
			'et' => "Estonian",
			'eu' => "Basque",
			'fa' => "Persian",
			'fi' => "Finnish",
			'fj' => "Fijian",
			'fo' => "Faroese",
			'fr' => "French",
			'fy' => "Western Frisian",
			'ga' => "Irish",
			'gd' => "Gaelic; Scottish Gaelic",
			'gl' => "Galician",
			'gn' => "Guarani",
			'gu' => "Gujarati",
			'gv' => "Manx",
			'ha' => "Hausa",
			'he' => "Hebrew",
			'hi' => "Hindi",
			'ho' => "Hiri Motu",
			'hr' => "Croatian",
			'ht' => "Haitian; Haitian Creole ",
			'hu' => "Hungarian",
			'hy' => "Armenian",
			'hz' => "Herero",
			'ia' => "Interlingua",
			'id' => "Indonesian",
			'ie' => "Interlingue",
			'ii' => "Sichuan Yi",
			'ik' => "Inupiaq",
			'io' => "Ido",
			'is' => "Icelandic",
			'it' => "Italian",
			'iu' => "Inuktitut",
			'ja' => "Japanese",
			'jv' => "Javanese",
			'ka' => "Georgian",
			'ki' => "Kikuyu; Gikuyu",
			'kj' => "Kuanyama; Kwanyama",
			'kk' => "Kazakh",
			'kl' => "Kalaallisut",
			'km' => "Khmer",
			'kn' => "Kannada",
			'ko' => "Korean",
			'ks' => "Kashmiri",
			'ku' => "Kurdish",
			'kv' => "Komi",
			'kw' => "Cornish",
			'ky' => "Kirghiz",
			'la' => "Latin",
			'lb' => "Luxembourgish; Letzeburgesch",
			'li' => "Limburgan; Limburger; Limburgish",
			'ln' => "Lingala",
			'lo' => "Lao",
			'lt' => "Lithuanian",
			'lv' => "Latvian",
			'mg' => "Malagasy",
			'mh' => "Marshallese",
			'mi' => "Maori",
			'mk' => "Macedonian",
			'ml' => "Malayalam",
			'mn' => "Mongolian",
			'mo' => "Moldavian",
			'mr' => "Marathi",
			'ms' => "Malay",
			'mt' => "Maltese",
			'my' => "Burmese",
			'na' => "Nauru",
			'nb' => "Norwegian Bokmal",
			'nd' => "Ndebele, North",
			'ne' => "Nepali",
			'ng' => "Ndonga",
			'nl' => "Dutch",
			'nn' => "Norwegian Nynorsk",
			'no' => "Norwegian",
			'nr' => "Ndebele, South",
			'nv' => "Navaho, Navajo",
			'ny' => "Nyanja; Chichewa; Chewa",
			'oc' => "Occitan; Provencal",
			'om' => "Oromo",
			'or' => "Oriya",
			'os' => "Ossetian; Ossetic",
			'pa' => "Panjabi",
			'pi' => "Pali",
			'pl' => "Polish",
			'ps' => "Pushto",
			'pt' => "Portuguese",
			'qu' => "Quechua",
			'rm' => "Raeto-Romance",
			'rn' => "Rundi",
			'ro' => "Romanian",
			'ru' => "Russian",
			'rw' => "Kinyarwanda",
			'sa' => "Sanskrit",
			'sc' => "Sardinian",
			'sd' => "Sindhi",
			'se' => "Northern Sami",
			'sg' => "Sango",
			'si' => "Sinhala; Sinhalese",
			'sk' => "Slovak",
			'sl' => "Slovenian",
			'sm' => "Samoan",
			'sn' => "Shona",
			'so' => "Somali",
			'sq' => "Albanian",
			'sr' => "Serbian",
			'ss' => "Swati",
			'st' => "Sotho, Southern",
			'su' => "Sundanese",
			'sv' => "Swedish",
			'sw' => "Swahili",
			'ta' => "Tamil",
			'te' => "Telugu",
			'tg' => "Tajik",
			'th' => "Thai",
			'ti' => "Tigrinya",
			'tk' => "Turkmen",
			'tl' => "Tagalog",
			'tn' => "Tswana",
			'to' => "Tonga",
			'tr' => "Turkish",
			'ts' => "Tsonga",
			'tt' => "Tatar",
			'tw' => "Twi",
			'ty' => "Tahitian",
			'ug' => "Uighur",
			'uk' => "Ukrainian",
			'ur' => "Urdu",
			'uz' => "Uzbek",
			'vi' => "Vietnamese",
			'vo' => "Volapuk",
			'wa' => "Walloon",
			'wo' => "Wolof",
			'xh' => "Xhosa",
			'yi' => "Yiddish",
			'yo' => "Yoruba",
			'za' => "Zhuang; Chuang",
			'zh' => "Chinese",
			'zu' => "Zulu",
		);
	}
	
	function getCountryCodes() {
		return array(
			'AD' => "Andorra",
			'AE' => "United Arab Emirates",
			'AF' => "Afghanistan",
			'AG' => "Antigua and Barbuda",
			'AI' => "Anguilla",
			'AL' => "Albania",
			'AM' => "Armenia",
			'AN' => "Netherlands Antilles",
			'AO' => "Angola",
			'AQ' => "Antarctica",
			'AR' => "Argentina",
			'AS' => "American Samoa",
			'AT' => "Austria",
			'AU' => "Australia",
			'AW' => "Aruba",
			'AX' => "Aland Islands",
			'AZ' => "Azerbaijan",
			'BA' => "Bosnia and Herzegovina",
			'BB' => "Barbados",
			'BD' => "Bangladesh",
			'BE' => "Belgium",
			'BF' => "Burkina Faso",
			'BG' => "Bulgaria",
			'BH' => "Bahrain",
			'BI' => "Burundi",
			'BJ' => "Benin",
			'BL' => "Saint Barthélemy",
			'BM' => "Bermuda",
			'BN' => "Brunei Darussalam",
			'BO' => "Bolivia",
			'BR' => "Brazil",
			'BS' => "Bahamas",
			'BT' => "Bhutan",
			'BV' => "Bouvet Island",
			'BW' => "Botswana",
			'BY' => "Belarus",
			'BZ' => "Belize",
			'CA' => "Canada",
			'CC' => "Cocos (Keeling) Islands",
			'CD' => "Congo, the Democratic Republic of the",
			'CF' => "Central African Republic",
			'CG' => "Congo",
			'CH' => "Switzerland",
			'CI' => "Cote d'Ivoire Côte d'Ivoire",
			'CK' => "Cook Islands",
			'CL' => "Chile",
			'CM' => "Cameroon",
			'CN' => "China",
			'CO' => "Colombia",
			'CR' => "Costa Rica",
			'CU' => "Cuba",
			'CV' => "Cape Verde",
			'CX' => "Christmas Island",
			'CY' => "Cyprus",
			'CZ' => "Czech Republic",
			'DE' => "Germany",
			'DJ' => "Djibouti",
			'DK' => "Denmark",
			'DM' => "Dominica",
			'DO' => "Dominican Republic",
			'DZ' => "Algeria",
			'EC' => "Ecuador",
			'EE' => "Estonia",
			'EG' => "Egypt",
			'EH' => "Western Sahara",
			'ER' => "Eritrea",
			'ES' => "Spain",
			'ET' => "Ethiopia",
			'FI' => "Finland",
			'FJ' => "Fiji",
			'FK' => "Falkland Islands (Malvinas)",
			'FM' => "Micronesia, Federated States of",
			'FO' => "Faroe Islands",
			'FR' => "France",
			'GA' => "Gabon",
			'GB' => "United Kingdom",
			'GD' => "Grenada",
			'GE' => "Georgia",
			'GF' => "French Guiana",
			'GG' => "Guernsey",
			'GH' => "Ghana",
			'GI' => "Gibraltar",
			'GL' => "Greenland",
			'GM' => "Gambia",
			'GN' => "Guinea",
			'GP' => "Guadeloupe",
			'GQ' => "Equatorial Guinea",
			'GR' => "Greece",
			'GS' => "South Georgia and the South Sandwich Islands",
			'GT' => "Guatemala",
			'GU' => "Guam",
			'GW' => "Guinea-Bissau",
			'GY' => "Guyana",
			'HK' => "Hong Kong",
			'HM' => "Heard Island and McDonald Islands",
			'HN' => "Honduras",
			'HR' => "Croatia",
			'HT' => "Haiti",
			'HU' => "Hungary",
			'ID' => "Indonesia",
			'IE' => "Ireland",
			'IL' => "Israel",
			'IM' => "Isle of Man",
			'IN' => "India",
			'IO' => "British Indian Ocean Territory",
			'IQ' => "Iraq",
			'IR' => "Iran, Islamic Republic of",
			'IS' => "Iceland",
			'IT' => "Italy",
			'JE' => "Jersey",
			'JM' => "Jamaica",
			'JO' => "Jordan",
			'JP' => "Japan",
			'KE' => "Kenya",
			'KG' => "Kyrgyzstan",
			'KH' => "Cambodia",
			'KI' => "Kiribati",
			'KM' => "Comoros",
			'KN' => "Saint Kitts and Nevis",
			'KP' => "Korea, Democratic People's Republic of",
			'KR' => "Korea, Republic of",
			'KW' => "Kuwait",
			'KY' => "Cayman Islands",
			'KZ' => "Kazakhstan",
			'LA' => "Lao People's Democratic Republic",
			'LB' => "Lebanon",
			'LC' => "Saint Lucia",
			'LI' => "Liechtenstein",
			'LK' => "Sri Lanka",
			'LR' => "Liberia",
			'LS' => "Lesotho",
			'LT' => "Lithuania",
			'LU' => "Luxembourg",
			'LV' => "Latvia",
			'LY' => "Libyan Arab Jamahiriya",
			'MA' => "Morocco",
			'MC' => "Monaco",
			'MD' => "Moldova, Republic of",
			'ME' => "Montenegro",
			'MF' => "Saint Martin (French part)",
			'MG' => "Madagascar",
			'MH' => "Marshall Islands",
			'MK' => "Macedonia, the former Yugoslav Republic of",
			'ML' => "Mali",
			'MM' => "Myanmar",
			'MN' => "Mongolia",
			'MO' => "Macao",
			'MP' => "Northern Mariana Islands",
			'MQ' => "Martinique",
			'MR' => "Mauritania",
			'MS' => "Montserrat",
			'MT' => "Malta",
			'MU' => "Mauritius",
			'MV' => "Maldives",
			'MW' => "Malawi",
			'MX' => "Mexico",
			'MY' => "Malaysia",
			'MZ' => "Mozambique",
			'NA' => "Namibia",
			'NC' => "New Caledonia",
			'NE' => "Niger",
			'NF' => "Norfolk Island",
			'NG' => "Nigeria",
			'NI' => "Nicaragua",
			'NL' => "Netherlands",
			'NO' => "Norway",
			'NP' => "Nepal",
			'NR' => "Nauru",
			'NU' => "Niue",
			'NZ' => "New Zealand",
			'OM' => "Oman",
			'PA' => "Panama",
			'PE' => "Peru",
			'PF' => "French Polynesia",
			'PG' => "Papua New Guinea",
			'PH' => "Philippines",
			'PK' => "Pakistan",
			'PL' => "Poland",
			'PM' => "Saint Pierre and Miquelon",
			'PN' => "Pitcairn",
			'PR' => "Puerto Rico",
			'PS' => "Palestinian Territory, Occupied",
			'PT' => "Portugal",
			'PW' => "Palau",
			'PY' => "Paraguay",
			'QA' => "Qatar",
			'RE' => "Reunion Réunion",
			'RO' => "Romania",
			'RS' => "Serbia",
			'RU' => "Russian Federation",
			'RW' => "Rwanda",
			'SA' => "Saudi Arabia",
			'SB' => "Solomon Islands",
			'SC' => "Seychelles",
			'SD' => "Sudan",
			'SE' => "Sweden",
			'SG' => "Singapore",
			'SH' => "Saint Helena",
			'SI' => "Slovenia",
			'SJ' => "Svalbard and Jan Mayen",
			'SK' => "Slovakia",
			'SL' => "Sierra Leone",
			'SM' => "San Marino",
			'SN' => "Senegal",
			'SO' => "Somalia",
			'SR' => "Suriname",
			'ST' => "Sao Tome and Principe",
			'SV' => "El Salvador",
			'SY' => "Syrian Arab Republic",
			'SZ' => "Swaziland",
			'TC' => "Turks and Caicos Islands",
			'TD' => "Chad",
			'TF' => "French Southern Territories",
			'TG' => "Togo",
			'TH' => "Thailand",
			'TJ' => "Tajikistan",
			'TK' => "Tokelau",
			'TL' => "Timor-Leste",
			'TM' => "Turkmenistan",
			'TN' => "Tunisia",
			'TO' => "Tonga",
			'TR' => "Turkey",
			'TT' => "Trinidad and Tobago",
			'TV' => "Tuvalu",
			'TW' => "Taiwan, Province of China",
			'TZ' => "Tanzania, United Republic of",
			'UA' => "Ukraine",
			'UG' => "Uganda",
			'UM' => "United States Minor Outlying Islands",
			'US' => "United States",
			'UY' => "Uruguay",
			'UZ' => "Uzbekistan",
			'VA' => "Holy See (Vatican City State)",
			'VC' => "Saint Vincent and the Grenadines",
			'VE' => "Venezuela",
			'VG' => "Virgin Islands, British",
			'VI' => "Virgin Islands, U.S.",
			'VN' => "Viet Nam",
			'VU' => "Vanuatu",
			'WF' => "Wallis and Futuna",
			'WS' => "Samoa",
			'YE' => "Yemen",
			'YT' => "Mayotte",
			'ZA' => "South Africa",
			'ZM' => "Zambia",
			'ZW' => "Zimbabwe",
		);
	}
}

/**
 * Smarty Template Manager Singleton
 *
 * @ingroup services
 */
class _DevblocksTemplateManager {
	/**
	 * Constructor
	 * 
	 * @private
	 */
	private function _DevblocksTemplateManager() {}
	/**
	 * Returns an instance of the Smarty Template Engine
	 * 
	 * @static 
	 * @return Smarty
	 */
	static function getInstance() {
		static $instance = null;
		if(null == $instance) {
			define('SMARTY_RESOURCE_CHAR_SET', LANG_CHARSET_CODE);
			require(DEVBLOCKS_PATH . 'libs/smarty/Smarty.class.php');

			$instance = new Smarty();
			
			$instance->template_dir = APP_PATH . '/templates';
			$instance->compile_dir = APP_TEMP_PATH . '/templates_c';
			$instance->cache_dir = APP_TEMP_PATH . '/cache';

			$instance->use_sub_dirs = false;

			$instance->caching = 0;
			$instance->cache_lifetime = 0;
			$instance->compile_check = (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) ? true : false;
			
			// Auto-escape HTML output
			$instance->loadFilter('variable','htmlspecialchars');
			//$instance->register->variableFilter(array('_DevblocksTemplateManager','variable_filter_esc'));
			
			// Devblocks plugins
			$instance->register->block('devblocks_url', array('_DevblocksTemplateManager', 'block_devblocks_url'));
			$instance->register->modifier('devblocks_date', array('_DevblocksTemplateManager', 'modifier_devblocks_date'));
			$instance->register->modifier('devblocks_hyperlinks', array('_DevblocksTemplateManager', 'modifier_devblocks_hyperlinks'));
			$instance->register->modifier('devblocks_hideemailquotes', array('_DevblocksTemplateManager', 'modifier_devblocks_hide_email_quotes'));
			$instance->register->modifier('devblocks_prettytime', array('_DevblocksTemplateManager', 'modifier_devblocks_prettytime'));
			$instance->register->modifier('devblocks_prettybytes', array('_DevblocksTemplateManager', 'modifier_devblocks_prettybytes'));
			$instance->register->modifier('devblocks_translate', array('_DevblocksTemplateManager', 'modifier_devblocks_translate'));
			$instance->register->resource('devblocks', array(
				array('_DevblocksSmartyTemplateResource', 'get_template'),
				array('_DevblocksSmartyTemplateResource', 'get_timestamp'),
				array('_DevblocksSmartyTemplateResource', 'get_secure'),
				array('_DevblocksSmartyTemplateResource', 'get_trusted'),
			));
		}
		return $instance;
	}

	static function modifier_devblocks_translate($string) {
		$translate = DevblocksPlatform::getTranslationService();
		
		// Variable number of arguments
		$args = func_get_args();
		array_shift($args); // pop off $string
		
		$translated = $translate->_($string);
		$translated = @vsprintf($translated,$args);
		return $translated;
	}
	
	static function block_devblocks_url($params, $content, $smarty, $repeat, $smarty_tpl) {
		$url = DevblocksPlatform::getUrlService();
		
		$contents = $url->write($content, !empty($params['full']) ? true : false);
		
	    if (!empty($params['assign'])) {
	        $smarty->assign($params['assign'], $contents);
	    } else {
	        return $contents;
	    }
	}
	
	static function modifier_devblocks_date($string, $format=null, $gmt=false) {
		if(empty($string))
			return '';
	
		$date = DevblocksPlatform::getDateService();
		return $date->formatTime($format, $string, $gmt);
	}
	
	static function modifier_devblocks_prettytime($string, $is_delta=false) {
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
			if(!$is_delta)
				$whole .= '-';
			
			if($diffsecs >= 31557600) { // years
				$whole .= floor($diffsecs/31557600).' year';
			} elseif($diffsecs >= 2592000) { // mo
				$whole .= floor($diffsecs/2592000).' month';
			} elseif($diffsecs >= 86400) { // days
				$whole .= floor($diffsecs/86400).' day';
			} elseif($diffsecs >= 3600) { // hours
				$whole .= floor($diffsecs/3600).' hour';
			} elseif($diffsecs >= 60) { // mins
				$whole .= floor($diffsecs/60).' min';
			} elseif($diffsecs >= 0) { // secs
				$whole .= $diffsecs.' sec';
			}
			
		} else { // The future
			if($diffsecs <= -31557600) { // years
				$whole .= floor($diffsecs/-31557600).' year';
			} elseif($diffsecs <= -2592000) { // mo
				$whole .= floor($diffsecs/-2592000).' month';
			} elseif($diffsecs <= -86400) { // days
				$whole .= floor($diffsecs/-86400).' day';
			} elseif($diffsecs <= -3600) { // hours
				$whole .= floor($diffsecs/-3600).' hour';
			} elseif($diffsecs <= -60) { // mins
				$whole .= floor($diffsecs/-60).' min';
			} elseif($diffsecs <= 0) { // secs
				$whole .= $diffsecs.' sec';
			}
		}

		// Pluralize
		$whole .= (1 == abs(intval($whole))) ? '' : 's';
		
		return $whole;
	}	

	static function modifier_devblocks_prettybytes($string, $precision='0') {
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
	
	function modifier_devblocks_hyperlinks($string, $sanitize = false, $style="") {
		$from = array("&gt;");
		$to = array(">");
		
		$string = str_replace($from,$to,$string);
		
		if($sanitize !== false)
			return preg_replace("/((http|https):\/\/(.*?))(\s|\>|&lt;|&quot;|\)|$)/ie","'<a href=\"goto.php?url='.'\\1'.'\" target=\"_blank\">\\1</a>\\4'",$string);
		else
			return preg_replace("/((http|https):\/\/(.*?))(\s|\>|&lt;|&quot;|\)|$)/ie","'<a href=\"'.'\\1'.'\" target=\"_blank\">\\1</a>\\4'",$string);
	}
	
	function modifier_devblocks_hide_email_quotes($string, $length=3) {
		$string = str_replace("\r\n","\n",$string);
		$string = str_replace("\r","\n",$string);
		$string = preg_replace("/\n{3,99}/", "\n\n", $string);
		$lines = explode("\n", $string);
		
		$quote_started = false;
		$last_line = count($lines) - 1;
		
		foreach($lines as $idx => $line) {
			// Check if the line starts with a > before any content
			if(preg_match("/^\s*\>/", $line)) {
				if(false === $quote_started)
					$quote_started = $idx;
				$quote_ended = false;
			} else {
				if(false !== $quote_started)
					$quote_ended = $idx-1;
			}
			
			// Always finish quoting on the last line
			if(!$quote_ended && $last_line == $idx)
				$quote_ended = $idx;
			
			if($quote_started && $quote_ended) {
				if($quote_ended - $quote_started >= $length) {
					$lines[$quote_started] = "<div style='margin:5px;'><a href='javascript:;' style='background-color:rgb(255,255,204);' onclick=\"$(this).closest('div').next('div').toggle();$(this).parent().fadeOut();\">-show quote-</a></div><div class='hidden' style='display:none;font-style:italic;color:rgb(66,116,62);'>" . $lines[$quote_started];
					$lines[$quote_ended] = $lines[$quote_ended]."</div>";
				}
				$quote_started = false;
			}
		}
		
		return implode("\n", $lines);
	}
};

class _DevblocksSmartyTemplateResource {
	static function get_template($tpl_name, &$tpl_source, $smarty_obj) {
		list($plugin_id, $tag, $tpl_path) = explode(':',$tpl_name,3);
		
		if(empty($plugin_id) || empty($tpl_path))
			return false;
			
		$plugins = DevblocksPlatform::getPluginRegistry();
		$db = DevblocksPlatform::getDatabaseService();
			
		if(null == ($plugin = @$plugins[$plugin_id])) /* @var $plugin DevblocksPluginManifest */
			return false;
			
		// Check if template is overloaded in DB/cache
		$matches = DAO_DevblocksTemplate::getWhere(sprintf("plugin_id = %s AND path = %s %s",
			$db->qstr($plugin_id),
			$db->qstr($tpl_path),
			(!empty($tag) ? sprintf("AND tag = %s ",$db->qstr($tag)) : "")
		));
			
		if(!empty($matches)) {
			$match = array_shift($matches); /* @var $match Model_DevblocksTemplate */
			$tpl_source = $match->content;
			return true;
		}
		
		// If not in DB, check plugin's relative path on disk
		$path = APP_PATH . '/' . $plugin->dir . '/templates/' . $tpl_path;
		
		if(!file_exists($path))
			return false;
		
		$tpl_source = file_get_contents($path);
		return true;			
	}
	
	static function get_timestamp($tpl_name, &$tpl_timestamp, $smarty_obj) { /* @var $smarty_obj Smarty */
		list($plugin_id, $tag, $tpl_path) = explode(':',$tpl_name,3);
		
		if(empty($plugin_id) || empty($tpl_path))
			return false;
		
		$plugins = DevblocksPlatform::getPluginRegistry();
		$db = DevblocksPlatform::getDatabaseService();
			
		if(null == ($plugin = @$plugins[$plugin_id])) /* @var $plugin DevblocksPluginManifest */
			return false;
			
		// Check if template is overloaded in DB/cache
		$matches = DAO_DevblocksTemplate::getWhere(sprintf("plugin_id = %s AND path = %s %s",
			$db->qstr($plugin_id),
			$db->qstr($tpl_path),
			(!empty($tag) ? sprintf("AND tag = %s ",$db->qstr($tag)) : "")
		));

		if(!empty($matches)) {
			$match = array_shift($matches); /* @var $match Model_DevblocksTemplate */
//			echo time(),"==(DB)",$match->last_updated,"<BR>";
			$tpl_timestamp = $match->last_updated;
			return true; 
		}
			
		// If not in DB, check plugin's relative path on disk
		$path = APP_PATH . '/' . $plugin->dir . '/templates/' . $tpl_path;
		
		if(!file_exists($path))
			return false;
		
		$stat = stat($path);
		$tpl_timestamp = $stat['mtime'];
//		echo time(),"==(DISK)",$stat['mtime'],"<BR>";
		return true;
	}
	
	static function get_secure($tpl_name, &$smarty_obj) {
		return false;
	}
	
	static function get_trusted($tpl_name, &$smarty_obj) {
		// not used
	}
};

class _DevblocksTemplateBuilder {
	private $_twig = null;
	private $_errors = array();
	
	private function _DevblocksTemplateBuilder() {
		$this->_twig = new Twig_Environment(new Twig_Loader_String(), array(
			'cache' => false, //APP_TEMP_PATH
			'debug' => false,
			'auto_reload' => true,
			'trim_blocks' => true,
		));
		
		// [TODO] Add helpful Twig extensions
	}
	
	/**
	 * 
	 * @return _DevblocksTemplateBuilder
	 */
	static function getInstance() {
		static $instance = null;
		if(null == $instance) {
			$instance = new _DevblocksTemplateBuilder();
		}
		return $instance;
	}

	/**
	 * @return Twig_Environment
	 */
	public function getEngine() {
		return $this->_twig;
	}
	
	/**
	 * @return array
	 */
	public function getErrors() {
		return $this->_errors;
	}
	
	private function _setUp() {
		$this->_errors = array();
	}
	
	private function _tearDown() {
	}
	
	/**
	 * 
	 * @param string $template
	 * @param array $vars
	 * @return string
	 */
	function build($template, $vars) {
		$this->_setUp();
		try {
			$template = $this->_twig->loadTemplate($template);
			$out = $template->render($vars);
		} catch(Exception $e) {
			$this->_errors[] = $e->getMessage();
		}
		$this->_tearDown();

		if(!empty($this->_errors))
			return false;
		
		return $out;
	} 
};

class _DevblocksDatabaseManager {
	private $_db = null;
	static $instance = null;
	
	private function _DevblocksDatabaseManager() {}
	
	static function getInstance() {
		if(null == self::$instance) {
			// Bail out early for pre-install
			if('' == APP_DB_DRIVER || '' == APP_DB_HOST)
			    return null;
			
			self::$instance = new _DevblocksDatabaseManager();
		}
		
		return self::$instance;
	}
	
	function __construct() {
		$persistent = (defined('APP_DB_PCONNECT') && APP_DB_PCONNECT) ? true : false;
		$this->Connect(APP_DB_HOST, APP_DB_USER, APP_DB_PASS, APP_DB_DATABASE, $persistent);
	}
	
	function Connect($host, $user, $pass, $database, $persistent=false) {
		if($persistent) {
			if(false === (@$this->_db = mysql_pconnect($host, $user, $pass)))
				return false;
		} else {
			if(false === (@$this->_db = mysql_connect($host, $user, $pass, true)))
				return false;
		}

		if(false === mysql_select_db($database, $this->_db)) {
			return false;
		}
		
		// Encoding
		//mysql_set_charset(DB_CHARSET_CODE, $this->_db); 
		$this->Execute('SET NAMES ' . DB_CHARSET_CODE);
		
		return true;
	}
	
	function getConnection() {
		return $this->_db;
	}
	
	function isConnected() {
		if(!is_resource($this->_db)) {
			$this->_db = null;
			return false;
		}
		return mysql_ping($this->_db);
	}
	
	function metaTables() {
		$tables = array();
		
		$sql = "SHOW TABLES";
		$rs = $this->GetArray($sql);
		
		foreach($rs as $row) {
			$table = array_shift($row);
			$tables[$table] = $table;
		}
		
		return $tables;
	}
	
	function metaTable($table_name) {
		$columns = array();
		$indexes = array();
		
		$sql = sprintf("SHOW COLUMNS FROM %s", $table_name);
		$rs = $this->GetArray($sql);
		
		foreach($rs as $row) {
			$field = $row['Field'];
			
			$columns[$field] = array(
				'field' => $field,
				'type' => $row['Type'],
				'null' => $row['Null'],
				'key' => $row['Key'],
				'default' => $row['Default'],
				'extra' => $row['Extra'],
			);
		}
		
		$sql = sprintf("SHOW INDEXES FROM %s", $table_name);
		$rs = $this->GetArray($sql);

		foreach($rs as $row) {
			$key_name = $row['Key_name'];
			$column_name = $row['Column_name'];

			if(!isset($indexes[$key_name]))
				$indexes[$key_name] = array(
					'columns' => array(),
				);
			
			$indexes[$key_name]['columns'][$column_name] = array(
				'column_name' => $column_name,
				'cardinality' => $row['Cardinality'],
				'index_type' => $row['Index_type'],
			);
		}
		
		return array(
			$columns,
			$indexes
		);
	}
	
	function Execute($sql) {
		if(false === ($rs = mysql_query($sql, $this->_db))) {
			error_log(sprintf("[%d] %s ::SQL:: %s", 
				mysql_errno(),
				mysql_error(),
				$sql
			));
			return false;
		}
			
		return $rs;
	}
	
	function SelectLimit($sql, $limit, $start=0) {
		$limit = intval($limit);
		$start = intval($start);
		
		if($limit > 0)
			return $this->Execute($sql . sprintf(" LIMIT %d,%d", $start, $limit));
		else
			return $this->Execute($sql);
	}
	
	function qstr($string) {
		return "'".mysql_real_escape_string($string, $this->_db)."'";
	}
	
	function GetArray($sql) {
		$results = array();
		
		if(false !== ($rs = $this->Execute($sql))) {
			while($row = mysql_fetch_assoc($rs)) {
				$results[] = $row;
			}
			mysql_free_result($rs);
		}
		
		return $results;
	}
	
	function GetRow($sql) {
		if($rs = $this->Execute($sql)) {
			$row = mysql_fetch_assoc($rs);
			mysql_free_result($rs);
			return $row;
		}
		return false;
	}

	function GetOne($sql) {
		if(false !== ($rs = $this->Execute($sql))) {
			$row = mysql_fetch_row($rs);
			mysql_free_result($rs);
			return $row[0];
		}
		
		return false;
	}

	function LastInsertId() {
		return mysql_insert_id($this->_db);
	}
	
	function Affected_Rows() {
		return mysql_affected_rows($this->_db);
	}
	
	function ErrorMsg() {
		return mysql_error($this->_db);
	}
};

class _DevblocksClassLoadManager {
	const CACHE_CLASS_MAP = 'devblocks_classloader_map';
	
    private static $instance = null;
	private $classMap = array();
	
    private function __construct() {
		$cache = DevblocksPlatform::getCacheService();
		if(null !== ($map = $cache->load(self::CACHE_CLASS_MAP))) {
			$this->classMap = $map;
		} else {
			$this->_initLibs();
			$this->_initPlugins();
			$cache->save($this->classMap, self::CACHE_CLASS_MAP);
		}
	}
    
	/**
	 * @return _DevblocksClassLoadManager
	 */
	public static function getInstance() {
		if(null == self::$instance) {
			self::$instance = new _DevblocksClassLoadManager();
		}
		return self::$instance;
	}
	
	public function destroy() {
		self::$instance = null;
	}
	
	public function loadClass($className) {
		if(class_exists($className))
			return;

		@$file = $this->classMap[$className];

		if(!is_null($file) && file_exists($file)) {
			require_once($file);
		} else {
			// Not found
		}
	}
	
	public function registerClasses($file,$classes=array()) {
		if(is_array($classes))
		foreach($classes as $class) {
			$this->classMap[$class] = $file;
		}
	}
	
	private function _initLibs() {
		$this->registerClasses(DEVBLOCKS_PATH . 'libs/markdown/markdown.php', array(
			'Markdown_Parser'
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'libs/s3/S3.php', array(
			'S3'
		));
		$this->registerClasses(DEVBLOCKS_PATH . 'libs/Twig/Autoloader.php', array(
			'Twig_Autoloader'
		));
	}
	
	private function _initPlugins() {
		// Load all the exported classes defined by plugin manifests		
		$class_map = DAO_Platform::getClassLoaderMap();
		if(is_array($class_map) && !empty($class_map))
		foreach($class_map as $path => $classes) {
			$this->registerClasses($path, $classes);
		}
	}
};

class _DevblocksLogManager {
	static $_instance = null;
	
    // Used the ZF classifications
	private static $_log_levels = array(
		'emerg' => 0,		// Emergency: system is unusable
		'emergency' => 0,	
		'alert' => 1,		// Alert: action must be taken immediately
		'crit' => 2,		// Critical: critical conditions
		'critical' => 2,	
		'err' => 3,			// Error: error conditions
		'error' => 3,		
		'warn' => 4,		// Warning: warning conditions
		'warning' => 4,		
		'notice' => 5,		// Notice: normal but significant condition
		'info' => 6,		// Informational: informational messages
		'debug' => 7,		// Debug: debug messages
	);

	private $_log_level = 0;
	private $_fp = null;
	
	static function getConsoleLog() {
		if(null == self::$_instance) {
			self::$_instance = new _DevblocksLogManager();
		}
		
		return self::$_instance;
	}
	
	private function __construct() {
		// Allow query string overloading Devblocks-wide
		@$log_level = DevblocksPlatform::importGPC($_REQUEST['loglevel'],'integer', 0);
		$this->_log_level = intval($log_level);
		
		// Open file pointer
		$this->_fp = fopen('php://output', 'w+');
	}
	
	public function __destruct() {
		@fclose($this->_fp);	
	}	
	
	public function __call($name, $args) {
		if(empty($args))
			$args = array('');
			
		if(isset(self::$_log_levels[$name])) {
			if(self::$_log_levels[$name] <= $this->_log_level) {
				$out = sprintf("[%s] %s<BR>\n",
					strtoupper($name),
					$args[0]
				);
				fputs($this->_fp, $out);
			}
		}
	}
};

class _DevblocksUrlManager {
    private static $instance = null;
        
   	private function __construct() {}
	
	/**
	 * @return _DevblocksUrlManager
	 */
	public static function getInstance() {
		if(null == self::$instance) {
			self::$instance = new _DevblocksUrlManager();
		}
		return self::$instance;
	}
	
	function parseQueryString($args) {
		$argc = array();
		if(empty($args)) return $argc;
		
		$query = explode('&', $args);
		if(is_array($query))
		foreach($query as $q) {
			if(empty($q)) continue;
			$v = explode('=',$q);
			if(empty($v)) continue;
			@$argc[strtolower($v[0])] = $v[1];
		}
		
		return $argc;
	}
	
	function parseURL($url) {
		// [JAS]: Use the index.php page as a reference to deconstruct the URI
		$pos = stripos($_SERVER['SCRIPT_NAME'],'index.php',0);
		if($pos === FALSE) return array();

		// Decode proxy requests
		if(isset($_SERVER['HTTP_DEVBLOCKSPROXYHOST'])) {
			$url = urldecode($url);
		}
		
		// [JAS]: Extract the basedir of the path
		$basedir = substr($url,0,$pos);

		// [JAS]: Remove query string
		$pos = stripos($url,'?',0);
		if($pos !== FALSE) {
			$url = substr($url,0,$pos);
		}
		
		$len = strlen($basedir);
		if(!DEVBLOCKS_REWRITE) $len += strlen("index.php/");
		
		$request = substr($url, $len);
		
		if(empty($request)) return array();
		
		$parts = explode('/', $request);

		if(trim($parts[count($parts)-1]) == '') {
			unset($parts[count($parts)-1]);
		}
		
		return $parts;
	}
	
	function write($sQuery='',$full=false,$check_proxy=true) {
		$args = $this->parseQueryString($sQuery);
		$c = @$args['c'];
		
		// Allow proxy override
		if($check_proxy) {
    		@$proxyssl = $_SERVER['HTTP_DEVBLOCKSPROXYSSL'];
    		@$proxyhost = $_SERVER['HTTP_DEVBLOCKSPROXYHOST'];
    		@$proxybase = $_SERVER['HTTP_DEVBLOCKSPROXYBASE'];
		}

		// Proxy (Community Tool)
		if(!empty($proxyhost)) {
			if($full) {
				$prefix = sprintf("%s://%s%s/",
					(!empty($proxyssl) ? 'https' : 'http'),
					$proxyhost,
					$proxybase
				);
			} else {
				$prefix = $proxybase.'/';
			}
		
			// Index page
			if(empty($sQuery)) {
			    return sprintf("%s",
			        $prefix
			    );
			}
			
			// [JAS]: Internal non-component URL (images/css/js/etc)
			if(empty($c)) {
				$contents = sprintf("%s%s",
					$prefix,
					$sQuery
				);
		    
			// [JAS]: Component URL
			} else {
				$contents = sprintf("%s%s",
					$prefix,
					(!empty($args) ? implode('/',array_values($args)) : '')
				);
			}
			
		// Devblocks App
		} else {
			if($full) {
				$prefix = sprintf("%s://%s%s",
					($this->isSSL() ? 'https' : 'http'),
					$_SERVER['HTTP_HOST'],
					DEVBLOCKS_APP_WEBPATH
				);
			} else {
				$prefix = DEVBLOCKS_APP_WEBPATH;
			}

			// Index page
			if(empty($sQuery)) {
			    return sprintf("%s%s",
			        $prefix,
			        (DEVBLOCKS_REWRITE) ? '' : 'index.php/'
			    );
			}
			
			// [JAS]: Internal non-component URL (images/css/js/etc)
			if(empty($c)) {
				$contents = sprintf("%s%s",
					$prefix,
					$sQuery
				);
		    
				// [JAS]: Component URL
			} else {
				if(DEVBLOCKS_REWRITE) {
					$contents = sprintf("%s%s",
						$prefix,
						(!empty($args) ? implode('/',array_values($args)) : '')
					);
					
				} else {
					$contents = sprintf("%sindex.php/%s",
						$prefix,
						(!empty($args) ? implode('/',array_values($args)) : '')
	//					(!empty($args) ? $sQuery : '')
					);
				}
			}
		}
		
		return $contents;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return boolean
	 */
	public function isSSL() {
		if(@$_SERVER["HTTPS"] == "on"){
			return true;
		} elseif (@$_SERVER["HTTPS"] == 1){
			return true;
		} elseif (@$_SERVER['SERVER_PORT'] == 443) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Useful for converting DevblocksRequest and DevblocksResponse objects to a URL
	 */
	function writeDevblocksHttpIO($request, $full=false) {
		$url_parts = '';
		
		if(is_array($request->path) && count($request->path) > 0)
			$url_parts = 'c=' . array_shift($request->path);
		
		if(!empty($request->path))
			$url_parts .= '&f=' . implode('/', $request->path);
		
		// Build the URL		
		$url = $this->write($url_parts, $full);
		
		$query = '';
		foreach($request->query as $key=>$val) {
			$query .= 
				(empty($query)?'':'&') . // arg1=val1&arg2=val2 
				$key . 
				'=' . 
				$val
			;
		}
		
		if(!empty($query))
			$url .= '?' . $query;

		return $url;
	}
};

// [TODO] Rename URLPing or some such nonsense, these don't proxy completely
class DevblocksProxy {
    /**
     * @return DevblocksProxy
     */
    static function getProxy() {
        $proxy = null;

		// Determine if CURL or FSOCK is available
		if(function_exists('curl_exec')) {
	    	$proxy = new DevblocksProxy_Curl();
		} elseif(function_exists('fsockopen')) {
    		$proxy = new DevblocksProxy_Socket();
		}

        return $proxy;
    }
    
    function proxy($remote_host, $remote_uri) {
        $this->_get($remote_host, $remote_uri);
    }

    function _get($remote_host, $remote_uri) {
        die("Subclass abstract " . __CLASS__ . "...");
    }

};

class DevblocksProxy_Socket extends DevblocksProxy {
    function _get($remote_host, $remote_uri) {
        $fp = fsockopen($remote_host, 80, $errno, $errstr, 10);
        if ($fp) {
            $out = "GET " . $remote_uri . " HTTP/1.1\r\n";
            $out .= "Host: $remote_host\r\n";
            $out .= 'Via: 1.1 ' . $_SERVER['HTTP_HOST'] . "\r\n";
            $out .= "Connection: Close\r\n\r\n";

            $this->_send($fp, $out);
        }
    }

    function _send($fp, $out) {
	    fwrite($fp, $out);
	    
	    while(!feof($fp)) {
	        fgets($fp,4096);
	    }

	    fclose($fp);
	    return;
    }
};

class DevblocksProxy_Curl extends DevblocksProxy {
    function _get($remote_host, $remote_uri) {
        $url = 'http://' . $remote_host . $remote_uri;
        $header = array();
        $header[] = 'Via: 1.1 ' . $_SERVER['HTTP_HOST'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0);
//        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
//        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);
        curl_exec($ch);
        curl_close($ch);
    }
};

interface DevblocksExtensionDelegate {
	static function shouldLoadExtension(DevblocksExtensionManifest $extension_manifest);
};

function devblocks_autoload($className) {
	return DevblocksPlatform::loadClass($className);
}

// Register Devblocks class loader
spl_autoload_register('devblocks_autoload');

// Register SwiftMailer
require_once(DEVBLOCKS_PATH . 'libs/swift/swift_required.php');

// Twig
if(class_exists('Twig_Autoloader', true) && method_exists('Twig_Autoloader','register'))
	Twig_Autoloader::register();
