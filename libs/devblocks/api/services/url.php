<?php
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
			@$argc[DevblocksPlatform::strLower($v[0])] = $v[1];
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
	
	function writeNoProxy($sQuery='',$full=false) {
		return $this->write($sQuery, $full, false);
	}
	
	function arrayToQuery(array $args) {
		// Handle indexed arrays
		$arg_keys = array_map(function($p) {
			if(is_numeric($p)) {
				if(0 == $p)
					return 'c';
				else
					return 'arg' . $p;
			} else {
				return $p;
			}
		}, array_keys($args));
		
		$query = http_build_query(array_combine($arg_keys, $args));
		return $query;
	}
	
	function write($sQuery='',$full=false,$check_proxy=true) {
		if(is_array($sQuery)) {
			$sQuery = $this->arrayToQuery($sQuery);
			
		} elseif(DevblocksPlatform::strStartsWith($sQuery, '/')) {
			$sQuery = $this->arrayToQuery(explode('/', trim($sQuery,'/')));
		}
		
		$args = $this->parseQueryString($sQuery);

		$c = @$args['c'];
		
		$proxyssl = null;
		$proxyhost = null;
		$proxybase = null;
	
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
				$hostname = DevblocksPlatform::getHostname();
				
				$prefix = sprintf("%s://%s%s",
					($this->isSSL() ? 'https' : 'http'),
					$hostname,
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
	 *
	 * @return boolean
	 */
	public function isSSL($check_proxy=true) {
		// Allow proxy override
		if($check_proxy) {
			@$proxyssl = $_SERVER['HTTP_DEVBLOCKSPROXYSSL'];
			@$proxyhost = $_SERVER['HTTP_DEVBLOCKSPROXYHOST'];
			
			if($proxyhost)
				return $proxyssl;
		}
		
		if(@$_SERVER["HTTPS"] == "on"){
			return true;
		} elseif (@$_SERVER["HTTPS"] == 1){
			return true;
		} elseif (@$_SERVER['SERVER_PORT'] == 443) {
			return true;
		} elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && 0 == strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https')) {
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
		
		if(is_array($request->query))
			if(false != ($query = http_build_query($request->query)))
				$url .= '?' . $query;

		return $url;
	}
};

class Cerb_HTMLPurifier_URIFilter_Email extends HTMLPurifier_URIFilter {
	/**
	 * @type string
	 */
	public $name = 'CerbEmail';
	
	/**
	 * @type bool
	 */
	public $post = true;
	
	/**
	 * @type HTMLPurifier_URIParser
	 */
	private $parser;
	
	/**
	 * @type _DevblocksUrlManager
	 */
	protected $urlWriter = null;
	
	protected $cerbUri = null;
	protected $cerbFilesPath = null;
	
	/**
	 * @param HTMLPurifier_Config $config
	 * @return bool
	 * @throws Exception
	 */
	public function prepare($config) {
		$this->parser = new HTMLPurifier_URIParser();
		$this->urlWriter = DevblocksPlatform::services()->url();

		$this->cerbUri = $this->parser->parse($this->urlWriter->write('', true));
		$this->cerbFilesPath = $this->urlWriter->write('c=files', false);
		return true;
	}
	
	/**
	 * @param HTMLPurifier_URI $uri
	 * @param HTMLPurifier_Config $config
	 * @param HTMLPurifier_Context $context
	 * @return bool
	 */
	public function filter(&$uri, $config, $context) {
		$is_embedded = $context->get('EmbeddedURI', true);
		
		// Block empty schemes
		if(false == ($scheme = DevblocksPlatform::strLower($uri->scheme))) {
			$uri = $this->parser->parse(null);
			return false;
		}
		
		// Allow data protocol images
		if($is_embedded && 0 == strcasecmp('data', $uri->scheme)) {
			return true;
		}
		
		// Allow mailto links
		if(!$is_embedded && in_array($scheme, ['mailto'])) {
			return true;
		}
		
		// Block non-HTTP links
		if(!$is_embedded && !in_array($scheme, ['http', 'https'])) {
			$uri = $this->parser->parse(null);
			return false;
		}
		
		// Block other URIs with no host
		if(!$uri->host) {
			$uri = $this->parser->parse(null);
			return false;
		}
		
		// Allow Cerb inline images
		if(0 == strcasecmp($uri->host, $this->cerbUri->host)) {
			if(DevblocksPlatform::strStartsWith($uri->path, $this->cerbUri->path, false)) {
				if(DevblocksPlatform::strStartsWith($uri->path, $this->cerbFilesPath, false)) {
					return true;
				} else {
					$uri = $this->parser->parse(null);
					return false;
				}
			}
		}
		
		return true;
	}
}