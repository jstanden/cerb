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
	
	function writeNoProxy($sQuery='',$full=false) {
		return $this->write($sQuery, $full, false);
	}
	
	function write($sQuery='',$full=false,$check_proxy=true) {
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