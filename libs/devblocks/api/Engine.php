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
		
		$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup

		$rel_dir = trim(substr($plugin_path, strlen(APP_PATH)), '/');
		
		if($rel_dir == 'libs/devblocks') {
			// It's what we want
		} elseif(substr($rel_dir, 0, 9) == 'features/') {
			// It's what we want
		} else {
			// Get rid of the storage prefix in the dir
			$rel_dir = 'plugins/' . $plugin->id;
		}

		$manifest = new DevblocksPluginManifest();
		$manifest->id = (string) $plugin->id;
		$manifest->dir = $rel_dir;
		$manifest->description = (string) $plugin->description;
		$manifest->author = (string) $plugin->author;
		$manifest->version = (integer) DevblocksPlatform::strVersionToInt($plugin->version);
		$manifest->link = (string) $plugin->link;
		$manifest->name = (string) $plugin->name;

		// Only re-persist the plugins when the version changes
		if(!$is_update && null != ($current_plugin = DevblocksPlatform::getPlugin($manifest->id))
				&& ($current_plugin->version == $manifest->version)) {
			$persist = false;
		}

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

		list($columns, $indexes) = $db->metaTable($prefix . 'plugin');

		// If this is a 4.x upgrade
		if(!isset($columns['version']))
			return $manifest;

		// Persist manifest
		if($db->GetOneMaster(sprintf("SELECT id FROM ${prefix}plugin WHERE id = %s", $db->qstr($manifest->id)))) { // update
			$db->ExecuteMaster(sprintf(
				"UPDATE ${prefix}plugin ".
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
				"INSERT INTO ${prefix}plugin (id,enabled,name,description,author,version,link,dir,manifest_cache_json) ".
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
		$results = $db->GetArrayMaster($sql);

		foreach($results as $row) {
			$plugin_ext_id = $row['id'];
			if(!isset($new_extensions[$plugin_ext_id]))
				DAO_Platform::deleteExtension($plugin_ext_id);
		}

		// Class loader cache
		$db->ExecuteMaster(sprintf("DELETE FROM %sclass_loader WHERE plugin_id = %s",$prefix,$db->qstr($plugin->id)));
		if(is_array($manifest->class_loader))
		foreach($manifest->class_loader as $file_path => $classes) {
			if(is_array($classes) && !empty($classes))
			foreach($classes as $class)
			$db->ExecuteMaster(sprintf(
				"REPLACE INTO ${prefix}class_loader (class,plugin_id,rel_path) ".
				"VALUES (%s,%s,%s)",
				$db->qstr($class),
				$db->qstr($manifest->id),
				$db->qstr($file_path)
			));
		}

		// ACL caching
		$db->ExecuteMaster(sprintf("DELETE FROM %sacl WHERE plugin_id = %s",$prefix,$db->qstr($plugin->id)));
		if(is_array($manifest->acl_privs))
		foreach($manifest->acl_privs as $priv) { /* @var $priv DevblocksAclPrivilege */
			$db->ExecuteMaster(sprintf(
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
			$db->ExecuteMaster(sprintf(
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

	static function getClientIp() {
		if(null == ($ip = @$_SERVER['REMOTE_ADDR']))
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
	
	static function getClientUserAgent() {
		require_once(DEVBLOCKS_PATH . 'libs/user_agent_parser.php');
		return parse_user_agent();
	}
	
	static function getHostname() {
		$app_hostname = APP_HOSTNAME;
		
		if(!empty($app_hostname))
			return $app_hostname;
		
		$host = @$_SERVER['HTTP_HOST'];
		
		if(!empty($host))
			return $host;
			
		$server_name = @$_SERVER['SERVER_NAME'];
		
		if(!empty($server_name))
			return $server_name;
		
		return 'localhost';
	}
	
	static function getWebPath() {
		$location = "";

		// Read the relative URL into an array
		if(isset($_SERVER['HTTP_X_REWRITE_URL'])) { // IIS Rewrite
			$location = $_SERVER['HTTP_X_REWRITE_URL'];
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
			if(!empty($uri)) $parts[] = DevblocksPlatform::strAlphaNum($uri, '\_\-\.');

			// Action (GET has precedence over POST)
			if(isset($_GET['a'])) {
				@$listener = DevblocksPlatform::importGPC($_GET['a']); // listener
			} elseif (isset($_POST['a'])) {
				@$listener = DevblocksPlatform::importGPC($_POST['a']); // listener
			}
			if(!empty($listener)) $parts[] = DevblocksPlatform::strAlphaNum($listener, '\_');
		}

		// Controller XSS security (alphanum+under only)
		if(isset($parts[0])) {
			$parts[0] = DevblocksPlatform::strAlphaNum($parts[0], '\_\-\.');
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
				$dir = $plugin->getStoragePath() . '/' . 'resources';
				if(!is_dir($dir)) DevblocksPlatform::dieWithHttpError(null, 403); // basedir security
				$resource = $dir . '/' . $file;
				if(0 != strstr($dir,$resource)) DevblocksPlatform::dieWithHttpError(null, 403);
				$ext = @array_pop(explode('.', $resource));
				if(!is_file($resource) || 'php' == $ext) DevblocksPlatform::dieWithHttpError(null, 403); // extension security

				// Caching
				switch($ext) {
					case 'css':
					case 'gif':
					case 'jpg':
					case 'js':
					case 'png':
					case 'ttf':
					case 'woff':
					case 'woff2':
						header('Cache-control: max-age=604800', true); // 1 wk // , must-revalidate
						header('Expires: ' . gmdate('D, d M Y H:i:s',time()+604800) . ' GMT'); // 1 wk
						break;
				}

				switch($ext) {
					case 'css':
						header('Content-type: text/css');
						break;
					case 'gif':
						header('Content-type: image/gif');
						break;
					case 'jpeg':
					case 'jpg':
						header('Content-type: image/jpeg');
						break;
					case 'js':
						header('Content-type: text/javascript');
						break;
					case 'pdf':
						header('Content-type: application/pdf');
						break;
					case 'png':
						header('Content-type: image/png');
						break;
					case 'ttf':
						header('Content-type: application/x-font-ttf');
						break;
					case 'woff':
						header('Content-type: application/font-woff');
						break;
					case 'woff2':
						header('Content-type: font/woff2');
						break;
					case 'xml':
						header('Content-type: text/xml');
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

		$method = DevblocksPlatform::strUpper(@$_SERVER['REQUEST_METHOD']);
		
		$request = new DevblocksHttpRequest($parts,$queryArgs,$method);
		$request->csrf_token = isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : @$_REQUEST['_csrf_token'];
		
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
		
		// Controllers

		$controller_uri = array_shift($path);
		
		// Security: IP Whitelist
		
		if(!in_array($controller_uri, array('oauth', 'portal')) && defined('APP_SECURITY_FIREWALL_WHITELIST') && !empty(APP_SECURITY_FIREWALL_WHITELIST)) {
			@$remote_addr = DevblocksPlatform::getClientIp();
			$valid_ips = DevblocksPlatform::parseCsvString(APP_SECURITY_FIREWALL_WHITELIST);
			
			if(!DevblocksPlatform::isIpAuthorized($remote_addr, $valid_ips)) {
				DevblocksPlatform::dieWithHttpError(sprintf("<h1>403 Forbidden for %s</h1>", $remote_addr), 403);
			}
		}
		
		// Security: CSRF
		
		// If we are running a controller action with an active session...
		if(!in_array($controller_uri, array('oauth', 'portal')) && (isset($_REQUEST['c']) || isset($_REQUEST['a']))) {
			
			// ...and we're not in DEVELOPMENT_MODE
			if(!DEVELOPMENT_MODE_ALLOW_CSRF) {
			
				// ...and the CSRF token is invalid for this session, freak out
				if(!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] != $request->csrf_token) {
					@$referer = $_SERVER['HTTP_REFERER'];
					@$remote_addr = DevblocksPlatform::getClientIp();
					
					//error_log(sprintf("[Cerb/Security] Possible CSRF attack from IP %s using referrer %s", $remote_addr, $referer), E_USER_WARNING);
					DevblocksPlatform::dieWithHttpError("Access denied", 403);
				}
			}
		}

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

				if(empty($controllers))
					DevblocksPlatform::dieWithHttpError("No controllers are available!", 500);

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
					DevblocksPlatform::dieWithHttpError(null, 404);
				}

				break;
		}

		return;
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
