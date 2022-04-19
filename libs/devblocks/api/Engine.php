<?php
@include_once(APP_PATH . '/vendor/autoload.php');

include_once(DEVBLOCKS_PATH . "api/Model.php");
include_once(DEVBLOCKS_PATH . "api/DAO.php");
include_once(DEVBLOCKS_PATH . "api/Extension.php");

if(!function_exists('mb_ucfirst')) {
	function mb_ucfirst($string) {
		return DevblocksPlatform::strUpperFirst($string);
	}
}

abstract class DevblocksEngine {
	const CACHE_ACL = 'devblocks_acl';
	const CACHE_ACTIVITY_POINTS = 'devblocks_activity_points';
	const CACHE_CONTEXTS = 'devblocks_contexts';
	const CACHE_CONTEXTS_INSTANCES = 'devblocks_contexts_instances';
	const CACHE_CONTEXT_ALIASES = 'devblocks_context_aliases';
	const CACHE_EVENT_POINTS = 'devblocks_event_points';
	const CACHE_EVENTS = 'devblocks_events';
	const CACHE_EXTENSIONS = 'devblocks_extensions';
	const CACHE_PLUGINS = 'devblocks_plugins';
	const CACHE_POINTS = 'devblocks_points';
	const CACHE_STORAGE_PROFILES = 'devblocks_storage_profiles';
	const CACHE_TABLES = 'devblocks_tables';
	const CACHE_TAG_TRANSLATIONS = 'devblocks_translations';

	static protected $handlerSession = null;

	static protected $start_time = 0;
	static protected $start_memory = 0;
	static protected $start_peak_memory = 0;

	static protected $timezone = '';
	static protected $locale = 'en_US';
	static protected $dateTimeFormat = 'D, d M Y h:i a';

	static protected $_tmp_files = array();

	protected static $request = null;
	protected static $response = null;
	protected static $is_stateless = false;
	protected static $_error_last = [];

	static function readPluginManifest($plugin_path, $is_update=true) {
		return self::_readPluginManifest($plugin_path, $is_update);
	}
	
	/**
	 * Reads and caches a single manifest from a given plugin directory.
	 *
	 * @static
	 * @param string $dir
	 * @return DevblocksPluginManifest
	 */
	static protected function _readPluginManifest($plugin_path, $is_update=true) {
		/*
		 * Translate all paths to Unix-style slashes. Newer builds in Windows seem to 
		 * use Unix-slashes too, while DIRECTORY_SEPARATOR still returns '\'.
		 */
		$plugin_path = str_replace('\\', '/', $plugin_path);
		
		$manifest_file = rtrim($plugin_path, '/') . '/plugin.xml';
		$persist = true;

		if(!file_exists($manifest_file))
			return NULL;

		if(false === ($plugin = @simplexml_load_file($manifest_file)))
			return NULL;
		
		// Make the plugin path relative
		if(DevblocksPlatform::strStartsWith($plugin_path, APP_STORAGE_PATH)) {
			$rel_dir = 'storage/' . trim(substr($plugin_path, strlen(APP_STORAGE_PATH)), '/');
		} else {
			$rel_dir = trim(substr($plugin_path, strlen(APP_PATH)), '/');
		}
		
		if($rel_dir == 'libs/devblocks') {
			// It's what we want
		} else if(DevblocksPlatform::strStartsWith($rel_dir, 'features/')) {
			// It's what we want
		} else if(DevblocksPlatform::strStartsWith($rel_dir, 'plugins/')) {
			// It's what we want
		} else if(DevblocksPlatform::strStartsWith($rel_dir, 'storage/plugins/')) {
			// It's what we want
		} else {
			return NULL;
		}
		
		$manifest = new DevblocksPluginManifest();
		$manifest->id = (string) $plugin->id;
		$manifest->dir = $rel_dir;
		$manifest->description = (string) $plugin->description;
		$manifest->author = (string) $plugin->author;
		$manifest->version = (integer) DevblocksPlatform::strVersionToInt($plugin->version);
		$manifest->link = (string) $plugin->link;
		$manifest->name = (string) $plugin->name;
		
		// Only re-persist a plugin when the version or path changes
		if(!$is_update 
			&& null != ($current_plugin = DevblocksPlatform::getPlugin($manifest->id)) 
			&& $current_plugin->version == $manifest->version
			&& $current_plugin->dir == $manifest->dir
			)
			$persist = false;
		
		// Requirements
		if(isset($plugin->requires)) {
			if(isset($plugin->requires->app_version)) {
				$eAppVersion = $plugin->requires->app_version; /* @var $eAppVersion SimpleXMLElement */
				$manifest->manifest_cache['requires']['app_version'] = array(
					'min' => (string) $eAppVersion['min'],
					'max' => (string) $eAppVersion['max'],
				);
			}

			if(isset($plugin->requires->php_extension))
			foreach($plugin->requires->php_extension as $ePhpExtension) {
				$name = (string) $ePhpExtension['name'];
				$manifest->manifest_cache['requires']['php_extensions'][$name] = array(
					'name' => $name,
				);
			}
		}

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

		// Activity points
		$manifest->manifest_cache['activity_points'] = array();
		if(isset($plugin->activity_points->activity))
		foreach($plugin->activity_points->activity as $eActivity) {
			$activity_point = (string) $eActivity['point'];
			$params = array();
			if(isset($eActivity->param))
			foreach($eActivity->param as $eParam) {
				$key = (string) $eParam['key'];
				$value = (string) $eParam['value'];
				$params[$key] = $value;
			}
			$manifest->manifest_cache['activity_points'][$activity_point] = array(
				'point' => $activity_point,
				'params' => $params,
			);
		}
		
		// If we're not persisting, return
		if(!$persist)
			return $manifest;

		// If the database is empty, return
		if(null == ($db = DevblocksPlatform::services()->database()) || DevblocksPlatform::isDatabaseEmpty())
			return $manifest;

		list($columns,) = $db->metaTable('cerb_plugin');

		// If this is a 4.x upgrade
		if(!isset($columns['version']))
			return $manifest;

		// Persist manifest
		if($db->GetOneMaster(sprintf("SELECT id FROM cerb_plugin WHERE id = %s", $db->qstr($manifest->id)))) { // update
			$db->ExecuteMaster(sprintf(
				"UPDATE cerb_plugin ".
				"SET name=%s,description=%s,author=%s,version=%s,link=%s,dir=%s,manifest_cache_json=%s ".
				"WHERE id=%s",
				$db->qstr($manifest->name),
				$db->qstr($manifest->description),
				$db->qstr($manifest->author),
				$db->qstr($manifest->version),
				$db->qstr($manifest->link),
				$db->qstr($manifest->dir),
				$db->qstr(json_encode($manifest->manifest_cache)),
				$db->qstr($manifest->id)
			));

		} else { // insert
			$enabled = (in_array($manifest->id, array('devblocks.core', 'cerberusweb.core')) ? 1 : 0);
			$db->ExecuteMaster(sprintf(
				"INSERT INTO cerb_plugin (id,enabled,name,description,author,version,link,dir,manifest_cache_json) ".
				"VALUES (%s,%d,%s,%s,%s,%s,%s,%s,%s)",
				$db->qstr($manifest->id),
				$enabled,
				$db->qstr($manifest->name),
				$db->qstr($manifest->description),
				$db->qstr($manifest->author),
				$db->qstr($manifest->version),
				$db->qstr($manifest->link),
				$db->qstr($manifest->dir),
				$db->qstr(json_encode($manifest->manifest_cache))
			));
		}

		// Class Loader
		if(isset($plugin->class_loader->dir)) {
			foreach($plugin->class_loader->dir as $eDir) {
				@$sDirPath = (string) $eDir['path'];
				@$sNsPrefix = (string) $eDir['namespace'];
				
				$sDirPath = rtrim($sDirPath, '/\\');
				$path = realpath($plugin_path . '/' . $sDirPath);
				
				if(!file_exists($path))
					continue;
				
				$dir = new RecursiveDirectoryIterator($path);
				$iter = new RecursiveIteratorIterator($dir);
				$regex = new RegexIterator($iter, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);
				
				foreach($regex as $class_file => $o) {
					if(is_null($o))
						continue;
					
					$class_name = substr($class_file, strlen($path)+1, strlen($class_file)-strlen($path)-5);
					$class_name = $sNsPrefix . str_replace(DIRECTORY_SEPARATOR, '\\', $class_name);
					
					$class_path = $sDirPath . substr($class_file, strlen($path));
					
					$manifest->class_loader[$class_path][] = $class_name;
				}
			}
		}
		
		if(isset($plugin->class_loader->file)) {
			foreach($plugin->class_loader->file as $eFile) {
				@$sFilePath = (string) $eFile['path'];
				$manifest->class_loader[$sFilePath] = [];

				if(isset($eFile->class))
				foreach($eFile->class as $eClass) {
					@$sClassName = (string) $eClass['name'];
					$manifest->class_loader[$sFilePath][] = $sClassName;
				}
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
		if(isset($plugin->extensions->extension))
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
			
			if(!isset($manifest->class_loader[$extension->file]))
				$manifest->class_loader[$extension->file] = [];
			
			$manifest->class_loader[$extension->file][] = $extension->class;

			if(isset($eExtension->params->param))
			foreach($eExtension->params->param as $eParam) {
				$key = (string) $eParam['key'];

				if(isset($eParam->value)) {
					// [JSJ]: If there is a child of the param tag named value, then this
					//		param has multiple values and thus we need to grab them all.
					$value_idx = 0;
					foreach($eParam->value as $eValue) {
						// Determine the key of this value
						if(isset($eValue['key']) && !empty($eValue['key'])) {
							$value_key = (string) $eValue['key'];
						} else {
							$value_key = $value_idx++;
						}

						// [JSJ]: If there is a child named data, then this is a complex structure
						if(isset($eValue->data)) {
							$value = array();
							foreach($eValue->data as $eData) {
								$data_key = (string) $eData['key'];

								if(isset($eData['value'])) {
									$value[$data_key] = trim((string) $eData['value']);
								} else {
									$value[$data_key] = trim((string) $eData);
								}
							}

						} else {
							// [JSJ]: Else, just grab the value and use it
							$value = trim((string) $eValue);
						}

						if(!empty($value))
							$extension->params[$key][$value_key] = $value;

						unset($value); // Just to be extra safe
					}

				} else {
					// [JSJ]: Otherwise, we grab the single value from the params value attribute.
					$extension->params[$key] = (string) $eParam['value'];
				}
			}

			$manifest->extensions[] = $extension;
		}

		// [JAS]: Extension caching
		$new_extensions = array();
		if(is_array($manifest->extensions))
		foreach($manifest->extensions as $pos => $extension) { /* @var $extension DevblocksExtensionManifest */
			$db->ExecuteMaster(sprintf(
				"REPLACE INTO cerb_extension (id,plugin_id,point,pos,name,file,class,params) ".
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
		$sql = sprintf("SELECT id FROM cerb_extension WHERE plugin_id = %s",
			$db->qstr($plugin->id)
		);
		$results = $db->GetArrayMaster($sql);

		foreach($results as $row) {
			$plugin_ext_id = $row['id'];
			if(!isset($new_extensions[$plugin_ext_id]))
				DAO_Platform::deleteExtension($plugin_ext_id);
		}

		// Class loader cache
		$db->ExecuteMaster(sprintf("DELETE FROM cerb_class_loader WHERE plugin_id = %s",$db->qstr($plugin->id)));
		if(is_array($manifest->class_loader))
		foreach($manifest->class_loader as $file_path => $classes) {
			if(is_array($classes) && !empty($classes))
			foreach($classes as $class)
			$db->ExecuteMaster(sprintf(
				"REPLACE INTO cerb_class_loader (class,plugin_id,rel_path) ".
				"VALUES (%s,%s,%s)",
				$db->qstr($class),
				$db->qstr($manifest->id),
				$db->qstr($file_path)
			));
		}

		// ACL caching
		$db->ExecuteMaster(sprintf("DELETE FROM cerb_acl WHERE plugin_id = %s",$db->qstr($plugin->id)));
		if(is_array($manifest->acl_privs))
		foreach($manifest->acl_privs as $priv) { /* @var $priv DevblocksAclPrivilege */
			$db->ExecuteMaster(sprintf(
				"REPLACE INTO cerb_acl (id,plugin_id,label) ".
				"VALUES (%s,%s,%s)",
				$db->qstr($priv->id),
				$db->qstr($priv->plugin_id),
				$db->qstr($priv->label)
			));
		}

		// [JAS]: Event point caching
		if(is_array($manifest->event_points))
		foreach($manifest->event_points as $event) { /* @var $event DevblocksEventPoint */
			$db->ExecuteMaster(sprintf(
				"REPLACE INTO cerb_event_point (id,plugin_id,name,params) ".
				"VALUES (%s,%s,%s,%s)",
				$db->qstr($event->id),
				$db->qstr($event->plugin_id),
				$db->qstr($event->name),
				$db->qstr(serialize($event->params))
			));
		}
		
		return $manifest;
	}

	static function getClientIp() {
		if(null == ($ip = $_SERVER['REMOTE_ADDR'] ?? null))
			return null;
		
		/*
		 * It's possible to have multiple REMOTE_ADDR listed when an upstream sets 
		 * it based on X-Forwarded-For, etc.
		 */
		if(false != ($ips = DevblocksPlatform::parseCsvString($ip)) && is_array($ips) && count($ips) > 1)
			$ip = array_shift($ips);
		
		return $ip;
	}

	static function isIpAuthorized($ip, array $ip_patterns=[]) {
		foreach($ip_patterns as $ip_pattern) {
			// Wildcard subnet match
			if(DevblocksPlatform::strEndsWith($ip_pattern, ['.'])) {
				if(DevblocksPlatform::strStartsWith($ip, $ip_pattern))
					return true;
			
			// Otherwise, exact match
			} else {
				if($ip == $ip_pattern)
					return true;
			}
		}
		
		return false;
	}
	
	static private ?array $_user_agent = null;
	
	static function getClientUserAgent() : ?array {
		if(!is_null(self::$_user_agent))
			return self::$_user_agent;
		
		try {
			if(false != ($user_agent = \donatj\UserAgent\parse_user_agent())) {
				self::$_user_agent = $user_agent;
				return self::$_user_agent;
			}
		} catch(Exception $e) {}
		
		return null;
	}
	
	static function getHostname() : string {
		$app_hostname = APP_HOSTNAME;
		
		if(!empty($app_hostname))
			return $app_hostname;
		
		$host = $_SERVER['HTTP_HOST'] ?? null;
		
		if(!empty($host))
			return $host;
			
		$server_name = $_SERVER['SERVER_NAME'] ?? null;
		
		if(!empty($server_name))
			return $server_name;
		
		return 'localhost';
	}
	
	static function getWebPath() {
		$location = "";

		// Read the relative URL into an array
		if( // Legacy IIS Rewrite
			APP_OPT_IIS_LEGACY_REWRITE
			&& isset($_SERVER['HTTP_X_REWRITE_URL'])
		) {
			$location = $_SERVER['HTTP_X_REWRITE_URL'];		
		} elseif( // IIS Rewrite
			array_key_exists('IIS_WasUrlRewritten', $_SERVER)
			&& '1' == $_SERVER['IIS_WasUrlRewritten']
			&& array_key_exists('UNENCODED_URL', $_SERVER)
		) {
			$location = $_SERVER['UNENCODED_URL'];
		} elseif(isset($_SERVER['REQUEST_URI'])) { // Apache + Nginx
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
		$url = DevblocksPlatform::services()->url();

		$location = self::getWebPath();

		$parts = $url->parseURL($location);

		// Add any query string arguments (?arg=value&arg=value)
		$query = $_SERVER['QUERY_STRING'] ?? null;
		$queryArgs = $url->parseQueryString($query);

		if(empty($parts)) {
			// Overrides (Form POST, etc.)

			// Controller (GET has precedence over POST)
			if(isset($_GET['c'])) {
				$uri = DevblocksPlatform::importGPC($_GET['c'] ?? null); // extension
			} elseif (isset($_POST['c'])) {
				$uri = DevblocksPlatform::importGPC($_POST['c'] ?? null); // extension
			}
			if(!empty($uri)) $parts[] = DevblocksPlatform::strAlphaNum($uri, '\_\-\.');

			// Action (GET has precedence over POST)
			if(isset($_GET['a'])) {
				$listener = DevblocksPlatform::importGPC($_GET['a'] ?? null); // listener
			} elseif (isset($_POST['a'])) {
				$listener = DevblocksPlatform::importGPC($_POST['a'] ?? null); // listener
			}
			if(!empty($listener)) $parts[] = DevblocksPlatform::strAlphaNum($listener, '\_');
		}

		// Controller XSS security (alphanum+under only)
		if(isset($parts[0])) {
			$parts[0] = DevblocksPlatform::strAlphaNum($parts[0], '\_\-\.');
		}

		// Resource / Proxy
		if('resource' == current($parts)) {
			$resource_request = new DevblocksHttpRequest($parts);
			$controller = new Controller_Resource();
			$controller->handleRequest($resource_request);
		}

		$method = DevblocksPlatform::strUpper(@$_SERVER['REQUEST_METHOD']);
		
		$request = new DevblocksHttpRequest($parts,$queryArgs,$method);
		$request->csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf_token'] ?? null;
		
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
		$url_writer = DevblocksPlatform::services()->url();
		
		$path = $request->path;
		
		// Controllers

		$controller_uri = array_shift($path);
		
		// Security: IP Whitelist
		
		if(!in_array($controller_uri, ['sso', 'oauth', 'portal']) && defined('APP_SECURITY_FIREWALL_ALLOWLIST') && !empty(APP_SECURITY_FIREWALL_ALLOWLIST)) {
			@$remote_addr = DevblocksPlatform::getClientIp();
			$valid_ips = DevblocksPlatform::parseCsvString(APP_SECURITY_FIREWALL_ALLOWLIST);
			
			if(!DevblocksPlatform::isIpAuthorized($remote_addr, $valid_ips)) {
				DevblocksPlatform::dieWithHttpError(sprintf("Forbidden for %s", $remote_addr), 403);
			}
		}
		
		// Security: CSRF
		
		// Exclude public controllers
		if(!in_array($controller_uri, ['cron', 'oauth', 'portal', 'rest', 'sso', 'webhooks'])) {
			
			// ...and we're not in DEVELOPMENT_MODE
			if(!DEVELOPMENT_MODE_ALLOW_CSRF) {
				$origin = DevblocksPlatform::strLower($_SERVER['HTTP_ORIGIN'] ?? '');
				$referer = DevblocksPlatform::strLower($_SERVER['HTTP_REFERER'] ?? '');
				$http_method = DevblocksPlatform::getHttpMethod();
				
				// Normalize the scheme and host (e.g. ignore /index.php/)
				$base_url_parts = parse_url($url_writer->write('', true));
				$base_url_port = isset($base_url_parts['port']) ? (':' . $base_url_parts['port']) : '';
				$base_url = DevblocksPlatform::strLower(sprintf("%s://%s%s/", $base_url_parts['scheme'], $base_url_parts['host'], $base_url_port));
				
				// Always compare the origin on non-GET, Ajax, or when adding controller/action to the URL
				if('GET' != $http_method || $is_ajax || array_key_exists('c', $_REQUEST) || array_key_exists('a', $_REQUEST)) {
					if ($origin) {
						// If origin doesn't match, freak out
						if ($base_url != (rtrim($origin, '/') . '/')) {
							if(!DEVELOPMENT_MODE_SECURITY_SCAN)
								error_log(sprintf("[Cerb] CSRF Block: Origin (%s) doesn't match (%s)", $origin, $base_url), E_USER_WARNING);
							DevblocksPlatform::dieWithHttpError("Access denied", 403);
						}
						
					} elseif ($referer) {
						// Referer of a POST doesn't match, freak out
						if (!DevblocksPlatform::strStartsWith($referer, $base_url)) {
							if(!DEVELOPMENT_MODE_SECURITY_SCAN)
								error_log(sprintf("[Cerb] CSRF Block: Referer (%s) doesn't match (%s)", $referer, $base_url), E_USER_WARNING);
							DevblocksPlatform::dieWithHttpError("Access denied", 403);
						}
						
					} else {
						// No origin or referer, reject
						if(!DEVELOPMENT_MODE_SECURITY_SCAN)
							error_log(sprintf("[Cerb] CSRF Block: No origin or referrer."), E_USER_WARNING);
						DevblocksPlatform::dieWithHttpError("Access denied", 403);
					}
				}
				
				// Always check the CSRF token on non-GET
				if ('GET' != $http_method) {
					// ...if the CSRF token is invalid for this session, freak out
					if (!array_key_exists('csrf_token', $_SESSION) || $_SESSION['csrf_token'] != $request->csrf_token) {
						//$referer = $_SERVER['HTTP_REFERER'] ?? null;
						//$remote_addr = DevblocksPlatform::getClientIp();
						
						//error_log(sprintf("[Cerb] Possible CSRF attack from IP %s using referer %s", $remote_addr, $referer), E_USER_WARNING);
						DevblocksPlatform::dieWithHttpError("Access denied", 403);
					}
				}
			}
		}
		
		// [JAS]: Offer the platform a chance to intercept.
		switch($controller_uri) {

			// [JAS]: Plugin-supplied URIs
			default:
				$routing = [];
				$controllers = DevblocksPlatform::getExtensions('devblocks.controller', false, false);

				// Add any controllers which have definitive routing
				if(is_array($controllers))
				foreach($controllers as $controller_mft) {
					if(isset($controller_mft->params['uri']))
						$routing[$controller_mft->params['uri']] = $controller_mft->id;
				}

				if(empty($controllers))
					DevblocksPlatform::dieWithHttpError("No controllers are available!", 500);

				// Set our controller based on the results
				$controller_mft = (isset($routing[$controller_uri]))
					? $controllers[$routing[$controller_uri]]
					: $controllers[APP_DEFAULT_CONTROLLER];

				// Instance our manifest
				if($controller_mft instanceof DevblocksExtensionManifest) {
					$controller = $controller_mft->createInstance();
				} else { 
					$controller = null;
				}
				
				if($controller instanceof DevblocksHttpRequestHandler) {
					$controller->handleRequest($request);
					
					$response = DevblocksPlatform::getHttpResponse();

					// [JAS]: If we didn't write a new response, repeat the request
					if(null == $response) {
						$response = new DevblocksHttpResponse($request->path);
						DevblocksPlatform::setHttpResponse($response);
					}
					
					// [JAS]: An Ajax request doesn't need the full Http cycle
					if(!$is_ajax) {
						$controller->writeResponse($response);
					}

				} else {
					DevblocksPlatform::dieWithHttpError(null, 404);
				}

				break;
		}
	}

	static function update() {
		if(null == ($manifest = self::_readPluginManifest(DEVBLOCKS_PATH, false)))
			return FALSE;

		if(!isset($manifest->manifest_cache['patches']))
			return TRUE;

		foreach($manifest->manifest_cache['patches'] as $mft_patch) {
			$path = $manifest->getStoragePath() . '/' . $mft_patch['file'];

			if(!file_exists($path))
				return FALSE;

			$patch = new DevblocksPatch($manifest->id, $mft_patch['version'], $mft_patch['revision'], $path);

			if(!$patch->run())
				return FALSE;
		}

		return TRUE;
	}

	// [TODO] Move to a service
	protected static function _strUnidecodeLookup($chr) {
		static $_pages = array();

		// 7-bit ASCII
		if($chr >= 0x00 && $chr <= 0x7f)
			return chr($chr);

		$high = $chr >> 8; // page
		$low = $chr % 256; // page chr

		$page = str_pad(dechex($high),2,'0',STR_PAD_LEFT);

		if(!isset($_pages[$page])) {
			$glyphs = array();
			$file_path = DEVBLOCKS_PATH . 'libs/unidecode/data/x'.$page.'.php';
			if(file_exists($file_path)) {
				require_once($file_path);
				if(!empty($glyphs))
					$_pages[$page] = $glyphs;
				unset($glyphs);
			}
		}

		if(isset($_pages[$page]) && isset($_pages[$page][$low]))
			return $_pages[$page][$low];
	}
};
