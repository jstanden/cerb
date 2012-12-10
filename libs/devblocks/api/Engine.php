<?php
include_once(DEVBLOCKS_PATH . "api/Model.php");
include_once(DEVBLOCKS_PATH . "api/DAO.php");
include_once(DEVBLOCKS_PATH . "api/Extension.php");

interface DevblocksExtensionDelegate {
	static function shouldLoadExtension(DevblocksExtensionManifest $extension_manifest);
};

abstract class DevblocksEngine {
	const CACHE_ACL = 'devblocks_acl';
	const CACHE_ACTIVITY_POINTS = 'devblocks_activity_points';
	const CACHE_EVENT_POINTS = 'devblocks_event_points';
	const CACHE_EVENTS = 'devblocks_events';
	const CACHE_EXTENSIONS = 'devblocks_extensions';
	const CACHE_PLUGINS = 'devblocks_plugins';
	const CACHE_POINTS = 'devblocks_points';
	const CACHE_SETTINGS = 'devblocks_settings';
	const CACHE_STORAGE_PROFILES = 'devblocks_storage_profiles';
	const CACHE_TABLES = 'devblocks_tables';
	const CACHE_TAG_TRANSLATIONS = 'devblocks_translations';
	
	static protected $extensionDelegate = null;
	
	static protected $start_time = 0;
	static protected $start_memory = 0;
	static protected $start_peak_memory = 0;
	
	static protected $locale = 'en_US';
	
	static protected $_tmp_files = array();
	
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
	static protected function _readPluginManifest($rel_dir, $is_update=true) {
		$manifest_file = APP_PATH . '/' . $rel_dir . '/plugin.xml';
		$persist = true;
		
		if(!file_exists($manifest_file))
			return NULL;
		
		$plugin = simplexml_load_file($manifest_file);
		$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup
				
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
			
		if(!$persist)
			return $manifest;
		
		$db = DevblocksPlatform::getDatabaseService();
		if(is_null($db))
			return;
			
		// Persist manifest
		if($db->GetOne(sprintf("SELECT id FROM ${prefix}plugin WHERE id = %s", $db->qstr($manifest->id)))) { // update
			$db->Execute(sprintf(
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
			$enabled = ('devblocks.core'==$manifest->id) ? 1 : 0;
			$db->Execute(sprintf(
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
					foreach($eParam->value as $eValue) {
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
							$extension->params[$key][] = $value;
						
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
					die();
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