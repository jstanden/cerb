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
			$argc[DevblocksPlatform::strLower($v[0] ?? '')] = $v[1] ?? '';
		}
		
		return $argc;
	}
	
	function parseURL($url) {
		// [JAS]: Use the index.php page as a reference to deconstruct the URI
		$pos = stripos($_SERVER['SCRIPT_NAME'],'index.php',0);
		if($pos === FALSE) return [];

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
		
		if(empty($request)) return [];
		
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
		
		return http_build_query(array_combine($arg_keys, $args));
	}
	
	function arrayToQueryString(array $args) {
		return preg_replace(
			'/%5B[0-9]+%5D/simU',
			'%5B%5D',
			http_build_query($args)
		);
	}
	
	function write($sQuery='',$full=false,$check_proxy=true) {
		if(is_array($sQuery)) {
			$sQuery = $this->arrayToQuery($sQuery);
			
		} elseif(DevblocksPlatform::strStartsWith($sQuery, '/')) {
			$sQuery = $this->arrayToQuery(explode('/', trim($sQuery,'/')));
		}
		
		$args = $this->parseQueryString($sQuery);

		$c = $args['c'] ?? null;
		
		$proxyssl = null;
		$proxyhost = null;
		$proxybase = null;
	
		// Allow proxy override
		if($check_proxy) {
			$proxyssl = $_SERVER['HTTP_DEVBLOCKSPROXYSSL'] ?? null;
			$proxyhost = $_SERVER['HTTP_DEVBLOCKSPROXYHOST'] ?? null;
			$proxybase = $_SERVER['HTTP_DEVBLOCKSPROXYBASE'] ?? null;
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
			$proxyssl = $_SERVER['HTTP_DEVBLOCKSPROXYSSL'] ?? null;
			$proxyhost = $_SERVER['HTTP_DEVBLOCKSPROXYHOST'] ?? null;
			
			if($proxyhost)
				return $proxyssl;
		}
		
		if(($_SERVER["HTTPS"] ?? null) == "on"){
			return true;
		} elseif (($_SERVER["HTTPS"] ?? null) == 1){
			return true;
		} elseif (($_SERVER['SERVER_PORT'] ?? null) == 443) {
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
	
	/**
	 * @type HTMLPurifier_URI
	 */
	protected $cerbUri = null;
	
	/**
	 * @type string
	 */
	protected $secret = null;
	
	/**
	 * @type _DevblocksEmailManager
	 */
	protected $mail = null;
	
	protected $cerbFilesPath = null;
	
	protected $filterCounts = [
		'blockedImage' => 0,
		'blockedLink' => 0,
		'proxiedImage' => 0,
		'redirectedLink' => 0,
	];
	
	protected $filterUrls = [
		'blockedImage' => [],
		'blockedLink' => [],
		'proxiedImage' => [],
		'redirectedLink' => [],
	];
	
	protected $allowImages = false;
	
	public function __construct($allow_images=false) {
		$this->allowImages = $allow_images;
	}
	
	/**
	 * @param HTMLPurifier_Config $config
	 * @return bool
	 * @throws Exception
	 */
	public function prepare($config) {
		$this->parser = new HTMLPurifier_URIParser();
		$this->urlWriter = DevblocksPlatform::services()->url();
		$this->mail = DevblocksPlatform::services()->mail();
		
		$this->secret = DevblocksPlatform::getPluginSetting('cerberusweb.core', CerberusSettings::MAIL_HTML_IMAGE_SECRET, '');

		$this->cerbUri = $this->parser->parse($this->urlWriter->write('', true));
		$this->cerbFilesPath = $this->urlWriter->write('c=files', false);
		return true;
	}
	
	public function flush() {
		$filter_urls = $this->filterUrls;
		
		foreach($filter_urls as $category => $hosts) {
			foreach($hosts as $host => $urls) {
				$sorted_urls = array_keys($urls);
				sort($sorted_urls);
				$filter_urls[$category][$host] = $sorted_urls;
			}
			
			ksort($filter_urls[$category]);
		}
		
		$results = [
			'counts' => $this->filterCounts,
			'urls' => $filter_urls,
		];
		
		$this->filterCounts = [
			'blockedImage' => 0,
			'blockedLink' => 0,
			'proxiedImage' => 0,
			'redirectedLink' => 0,
		];
		
		$this->filterUrls = [
			'blockedImage' => [],
			'blockedLink' => [],
			'proxiedImage' => [],
			'redirectedLink' => [],
		];
		
		return $results;
	}
	
	/**
	 * @param HTMLPurifier_URI $uri
	 * @param HTMLPurifier_Config $config
	 * @param HTMLPurifier_Context $context
	 * @return bool
	 */
	public function filter(&$uri, $config, $context) {
		$is_embedded = $context->get('EmbeddedURI', true);
		
		// Fragments
		if($uri->fragment && !$is_embedded && !$uri->scheme && !$uri->host && !$uri->path && !$uri->query)
			return true;
		
		// Block empty schemes
		if(false == ($scheme = DevblocksPlatform::strLower($uri->scheme))) {
			if($is_embedded) {
				$this->_logBlockedImage($uri);
			} else {
				$this->_logBlockedLink($uri);
			}
			$uri = $this->parser->parse(null);
			return false;
		}
		
		// Allow data protocol images
		if($is_embedded && 0 == strcasecmp('data', $uri->scheme)) {
			return true;
		}
		
		// Block non-HTTP, non-mailto links
		if(!$is_embedded && !in_array($scheme, ['http', 'https','mailto','tel'])) {
			$this->_logBlockedLink($uri);
			$uri = $this->parser->parse(null);
			return false;
		}
		
		// Block other (non-mail) URIs with no host
		if(!$uri->host && !in_array($scheme, ['mailto', 'tel'])) {
			if($is_embedded) {
				$this->_logBlockedImage($uri);
			} else {
				$this->_logBlockedLink($uri);
			}
			$uri = $this->parser->parse(null);
			return false;
		}
		
		// Rewrite allowed URLs
		
		if($is_embedded) {
			// Allow Cerb inline images
			if (0 == strcasecmp($uri->host, $this->cerbUri->host)) {
				if (DevblocksPlatform::strStartsWith($uri->path, $this->cerbUri->path, false)) {
					if (DevblocksPlatform::strStartsWith($uri->path, $this->cerbFilesPath, false)) {
						return true;
					}
				}
			}
			
			if(!$this->allowImages) {
				$this->_logBlockedImage($uri);
				$uri = $this->parser->parse(null);
				return true;
				
			} else {
				$blocked_hosts = $this->mail->getImageProxyBlocklist();
				$host = DevblocksPlatform::strLower($uri->host);
				$url = $uri->toString();
				
				$host_patterns = [$host];
				
				$last_pos = 0;
				while (false !== ($pos = strpos($host, '.', $last_pos))) {
					$host_patterns[] = substr($host,$pos);
					$last_pos = ++$pos;
				}
				
				foreach ($host_patterns as $host_pattern) {
					if (array_key_exists($host_pattern, $blocked_hosts)) {
						foreach ($blocked_hosts[$host_pattern] as $regexp) {
							if (preg_match($regexp, $url)) {
								$this->_logBlockedImage($uri);
								$uri = $this->parser->parse(null);
								return true;
							}
						}
					}
				}
				
				$this->_logProxiedImage($uri);
				
				$new_url = $this->urlWriter->write('c=security&a=proxyImage');
				
				$new_url .= '?url=' . rawurlencode($uri->toString());
				
				if($this->secret) {
					$hash = hash_hmac('sha256', $uri->toString(), $this->secret, true);
					$hash = DevblocksPlatform::services()->string()->base64UrlEncode($hash);
					$new_url .= '&s=' . rawurlencode(substr($hash, 0, 10));
				}
				
				$uri = $this->parser->parse($new_url);
				
				return true;
			}
			
		} else {
			$whitelist_hosts = $this->mail->getLinksWhitelist();
			$host = DevblocksPlatform::strLower($uri->host);
			
			if(!$uri->path)
				$uri->path = '/';
			
			$url = $uri->toString();
			
			$host_patterns = [$host];
			
			$last_pos = 0;
			while (false !== ($pos = strpos($host, '.', $last_pos))) {
				$host_patterns[] = substr($host,$pos);
				$last_pos = ++$pos;
			}
			
			foreach ($host_patterns as $host_pattern) {
				if (array_key_exists($host_pattern, $whitelist_hosts)) {
					foreach ($whitelist_hosts[$host_pattern] as $regexp) {
						if (preg_match($regexp, $url)) {
							$this->_logRedirectedLink($uri);
							return true;
						}
					}
				}
			}
			
			$this->_logRedirectedLink($uri);
			
			$new_uri = sprintf("javascript:void(genericAjaxPopup('externalLink','c=security&a=renderLinkPopup&url=%s',null,true));",
				rawurlencode(rawurlencode($uri->toString()))
			);
			
			$new_uri = $this->parser->parse($new_uri);
			
			$uri = $new_uri;
		}
		
		return true;
	}
	
	private function _logBlockedImage(HTMLPurifier_URI $uri) {
		$host = DevblocksPlatform::strLower($uri->host);
		$url = $uri->toString();
		
		if(empty($url))
			return;
		
		if(!array_key_exists($host, $this->filterUrls['blockedImage']))
			$this->filterUrls['blockedImage'][$host] = [];
		
		if(!array_key_exists($url, $this->filterUrls['blockedImage'][$host])) {
			$this->filterUrls['blockedImage'][$host][$url] = 0;
			$this->filterCounts['blockedImage']++;
		}
		
		$this->filterUrls['blockedImage'][$host][$url]++;
	}
	
	private function _logBlockedLink(HTMLPurifier_URI $uri) {
		$host = DevblocksPlatform::strLower($uri->host);
		$url = $uri->toString();
		
		if(empty($url))
			return;
		
		if(!array_key_exists($host, $this->filterUrls['blockedLink']))
			$this->filterUrls['blockedLink'][$host] = [];
		
		if(!array_key_exists($url, $this->filterUrls['blockedLink'][$host])) {
			$this->filterUrls['blockedLink'][$host][$url] = 0;
			$this->filterCounts['blockedLink']++;
		}
		
		$this->filterUrls['blockedLink'][$host][$url]++;
	}
	
	private function _logProxiedImage(HTMLPurifier_URI $uri) {
		$host = DevblocksPlatform::strLower($uri->host);
		$url = $uri->toString();
		
		if(empty($url))
			return;
		
		if(!array_key_exists($host, $this->filterUrls['proxiedImage']))
			$this->filterUrls['proxiedImage'][$host] = [];
		
		if(!array_key_exists($url, $this->filterUrls['proxiedImage'][$host])) {
			$this->filterUrls['proxiedImage'][$host][$url] = 0;
			$this->filterCounts['proxiedImage']++;
		}
		
		$this->filterUrls['proxiedImage'][$host][$url]++;
	}
	
	private function _logRedirectedLink(HTMLPurifier_URI $uri) {
		$host = DevblocksPlatform::strLower($uri->host);
		$url = $uri->toString();
		
		if(empty($url))
			return;
		
		if(!array_key_exists($host, $this->filterUrls['redirectedLink']))
			$this->filterUrls['redirectedLink'][$host] = [];
		
		if(!array_key_exists($url, $this->filterUrls['redirectedLink'][$host])) {
			$this->filterUrls['redirectedLink'][$host][$url] = 0;
			$this->filterCounts['redirectedLink']++;
		}
		
		$this->filterUrls['redirectedLink'][$host][$url]++;
	}
}

class Cerb_HTMLPurifier_URIFilter_Extract extends HTMLPurifier_URIFilter {
	/**
	 * @type string
	 */
	public $name = 'CerbUriExtract';
	
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
	
	protected $results = [
		'tokens' => [],
	];
	
	public function __construct() {
	}
	
	/**
	 * @param HTMLPurifier_Config $config
	 * @return bool
	 * @throws Exception
	 */
	public function prepare($config) {
		$this->parser = new HTMLPurifier_URIParser();
		$this->urlWriter = DevblocksPlatform::services()->url();
		
		return true;
	}
	
	public function flush() {
		return $this->results;
	}
	
	/**
	 * @param HTMLPurifier_URI $uri
	 * @param HTMLPurifier_Config $config
	 * @param HTMLPurifier_Context $context
	 * @return bool
	 */
	public function filter(&$uri, $config, $context) {
		$current_token = $context->get('CurrentToken', true);
		$current_attr = $context->get('CurrentAttr', true);
		
		$token = uniqid('#uri-');
		
		$new_uri = $this->parser->parse($token);
		
		$this->results['tokens'][$token] = $uri->toString();
		
		$this->results['context'][$token] = [
			'is_tag' => $current_token->is_tag,
			'name' => $current_token->name,
			'attr' => $current_attr,
			'attrs' => $current_token->attr,
			'uri_parts' => json_decode(json_encode($uri), true),
		];
		
		$uri = $new_uri;
		
		return true;
	}
}