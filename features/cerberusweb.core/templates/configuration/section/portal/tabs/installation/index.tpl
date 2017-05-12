<form action="{devblocks_url}{/devblocks_url}" method="post">
<fieldset>
<legend>index.php</legend>
{'portal.cfg.installation_instructions'|devblocks_translate|escape|nl2br nofilter}<br>
<textarea rows="10" cols="80" style="width:98%;margin:10px;font-family:Courier;">
&lt;?php
define('REMOTE_PROTOCOL', '{if $is_ssl}https{else}http{/if}');
define('REMOTE_HOST', '{$host}');
define('REMOTE_PORT', '{if !empty($port) && 80!=$port}{$port}{else}80{/if}');
define('REMOTE_BASE', '{$base}{if !$smarty.const.DEVBLOCKS_REWRITE}/index.php{/if}'); // NO trailing slash!
define('REMOTE_URI', '{$path}'); // NO trailing slash!
{literal}
/*
 * ====================================================================
 * [JAS]: Don't modify the following unless you know what you're doing!
 * ====================================================================
 */
define('URL_REWRITE', file_exists('.htaccess'));
define('LOCAL_SSL', null);
define('LOCAL_HOST', $_SERVER['HTTP_HOST']);
define('LOCAL_BASE', DevblocksRouter::getLocalBase()); // NO trailing slash!
define('REMOTE_SSL_VALIDATION', true);
define('SCRIPT_LAST_MODIFY', 20170428); // last change

class DevblocksProxy {
	function proxy($local_path) {
		$path = '';
		$query = '';
		
		// Query args
		if(0 != strpos($local_path,'?'))
			list($local_path, $query) = explode('?', $local_path);
			
		$path = explode('/', substr($local_path,1));
		
		// Encode all our parts
		if(is_array($path))
		foreach($path as $idx => $p)
			$path[$idx] = rawurlencode($p);
			
		$local_path = '/'.implode('/', $path);
		if(!empty($query)) 
			$local_path .= '?' . $query;

		if(0==strcasecmp($path[0],'resource')) {
			header('Pragma: cache'); 
			header('Cache-control: max-age=86400'); // 1d
			header('Expires: ' . gmdate('D, d M Y H:i:s',time()+86400) . ' GMT'); // 1d
			$remote_path = REMOTE_BASE;
			
		} else {
			$remote_path = REMOTE_BASE . REMOTE_URI;
		}
		
		switch($this->_getVerb()) {
			case 'GET':
				$this->_get($remote_path, $local_path);
				break;
			case 'OPTIONS':
				$this->_options($remote_path, $local_path, 'OPTIONS');
				break;
			case 'POST':
				$this->_post($remote_path, $local_path);
				break;
		}
	}

	function _options($local_path, $remote_path) {
		die("Subclass abstract " . __CLASS__ . "...");
	}

	function _get($local_path, $remote_path) {
		die("Subclass abstract " . __CLASS__ . "...");
	}

	function _post($local_path, $remote_path) {
		die("Subclass abstract " . __CLASS__ . "...");
	}

	function _parseResponse($content) {
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
		
		return array(
			$headers,
			$content
		);
	}
	
	function _getVerb() {
		$verb = strtoupper($_SERVER['REQUEST_METHOD']);
		return $verb;
	}
	
	function _generateMimeBoundary() {
		return md5(mt_rand(0,10000).time().microtime());
	}

	function _isSSL() {
		if(LOCAL_SSL) {
			return true;
		} elseif(@$_SERVER["HTTPS"] == "on"){
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
	 * @return string
	 */
	function _buildPost($boundary) {
		$content = null;
		
		// Handle post variables
		foreach($_POST as $k => $v) {
			if(is_array($v)) {
				foreach($v as $vk => $vv) {
					$content .= sprintf("--%s\r\n".
						"content-disposition: form-data; name=\"%s\"\r\n".
						"\r\n".
						"%s\r\n",
						$boundary,
						$k.'[]',
						$vv
					);
				}
			} else {
				$content .= sprintf("--%s\r\n".
					"content-disposition: form-data; name=\"%s\"\r\n".
					"\r\n".
					"%s\r\n",
					$boundary,
					$k,
					$v
				);
			}
		}
		
		// Handle files
		if(is_array($_FILES) && !empty($_FILES))
		foreach($_FILES as $k => $file) {
			if(is_array($file['name'])) {
				foreach($file['name'] as $idx => $name) {
					if(empty($name))
						continue;
					
					$content .= sprintf("--%s\r\n".
						"content-disposition: form-data; name=\"%s[]\"; filename=\"%s\"\r\n".
						"Content-Type: application/octet-stream\r\n".
						"\r\n".
						"%s\r\n",
						$boundary,
						$k,
						$name,
						file_get_contents($file['tmp_name'][$idx])
					);
				}
				
			} else {
				$content .= sprintf("--%s\r\n".
					"content-disposition: form-data; name=\"%s\"; filename=\"%s\"\r\n".
					"Content-Type: application/octet-stream\r\n".
					"\r\n".
					"%s\r\n",
					$boundary,
					$k,
					$file['name'],
					file_get_contents($file['tmp_name'])
				);
			}
		}

		$content .= sprintf("--%s--\r\n",
			$boundary
		);
		
		return $content; // POST
	}
};

class DevblocksProxy_Curl extends DevblocksProxy {
	function _proxyHttpHeaders(&$header) {
		if(!is_array($header))
			$header = array();
		
		$header[] = 'Via: 1.1 ' . LOCAL_HOST;
		
		if($this->_isSSL()) $header[] = 'DevblocksProxySSL: 1';
		
		$header[] = 'DevblocksProxyHost: ' . LOCAL_HOST;
		$header[] = 'DevblocksProxyBase: ' . LOCAL_BASE;
		
		if(isset($_SERVER['HTTP_CONTENT_TYPE']))
			$header[] = 'Content-Type: ' . $_SERVER['HTTP_CONTENT_TYPE'];
		
		if(isset($_SERVER['HTTP_ORIGIN']))
			$header[] = 'Origin: ' . $_SERVER['HTTP_ORIGIN'];
		
		if(isset($_SERVER['HTTP_REFERER']))
			$header[] = 'Referer: ' . $_SERVER['HTTP_REFERER'];
		
		if(isset($_SERVER['HTTP_USER_AGENT']))
			$header[] = 'User-Agent: ' . $_SERVER['HTTP_USER_AGENT'];
		
		$cookies = array();
		
		foreach($_COOKIE as $key => $value) {
			$cookies[] = sprintf("%s=%s", $key, urlencode($value));
		}
		
		if(!empty($cookies))
			$header[] = 'Cookie: ' . implode('; ', $cookies);
		
		return $header;
	}
	
	function _get($remote_path, $local_path, $verb=null) {
		$url = REMOTE_PROTOCOL . '://' . REMOTE_HOST . ':' . REMOTE_PORT . $remote_path . $local_path;
		
		$header = array();
		$this->_proxyHttpHeaders($header);
		
		$ch = curl_init();
		$out = "";
		curl_setopt($ch, CURLOPT_URL, $url);
		if(!REMOTE_SSL_VALIDATION) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		if($verb)
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $verb);
		
		$this->_returnTransfer($ch, $out);
		
		curl_close($ch);
	}
	
	function _post($remote_path, $local_path) {
		$boundary = $this->_generateMimeBoundary();
		$content = $this->_buildPost($boundary);
				
		$url = REMOTE_PROTOCOL . '://' . REMOTE_HOST . ':' . REMOTE_PORT . $remote_path . $local_path;
		$header = array();
		$header[] = 'Content-Type: multipart/form-data; boundary='.$boundary;
		$header[] = 'Content-Length: ' .  strlen($content);
		$this->_proxyHttpHeaders($header);

		$ch = curl_init();
		$out = "";
		curl_setopt($ch, CURLOPT_URL, $url);
		if(!REMOTE_SSL_VALIDATION) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$this->_returnTransfer($ch, $out);
		
		curl_close($ch);
	}
	
	function _returnTransfer($ch) {
		$out = curl_exec($ch);
		$info = curl_getinfo($ch);
		
		// Status code
		
		if(function_exists('http_response_code'))
			http_response_code($info['http_code']);

		// Headers

		list($raw_headers, $content) = $this->_parseResponse($out);
		
		foreach($raw_headers as $raw_header) {
			@list($k, $v) = explode(':', $raw_header, 2);
			
			if(empty($k) || empty($v))
				continue;
			
			switch(strtolower($k)) {
				case 'host':
					break;
					
				default:
					header($k . ': ' . $v);
					break;
			}
		}
		
		echo $content;
	}
};

class DevblocksRouter {
	function connect() {
		// Read the relative URL into an array
		if(@isset($_SERVER['HTTP_X_REWRITE_URL'])) { // IIS Rewrite
			$location = $_SERVER['HTTP_X_REWRITE_URL'];
		} elseif(@isset($_SERVER['REQUEST_URI'])) { // Apache
			$location = $_SERVER['REQUEST_URI'];
		} elseif(@isset($_SERVER['REDIRECT_URL'])) { // Apache mod_rewrite (breaks on CGI)
			$location = $_SERVER['REDIRECT_URL'];
		} elseif(@isset($_SERVER['ORIG_PATH_INFO'])) { // IIS + CGI
			$location = $_SERVER['ORIG_PATH_INFO'];
		}
	
		$local_path = substr($location,strlen(LOCAL_BASE));
		
		$proxy = new DevblocksProxy_Curl();
		$proxy->proxy($local_path);
	}

	/**
	 * @static
	 * @return string
	 */
	static function getLocalBase() {
		$uri = rtrim($_SERVER['PHP_SELF'],'/');
		$path = explode('/', $uri);
		if(false !== ($pos = array_search("index.php",$path))) {
			$path = array_slice($path, 0, (URL_REWRITE?$pos:$pos+1));
		}
		return implode('/', $path);
	}
};

$router = new DevblocksRouter();
$router->connect();
{/literal}</textarea>
</fieldset>

<fieldset>
<legend>.htaccess</legend>
{'portal.cfg.htaccess_hint'|devblocks_translate}<br>
<textarea rows="10" cols="80" style="width:98%;margin:10px;font-family:Courier;">{literal}
&lt;IfModule mod_rewrite.c&gt;
RewriteEngine on

RewriteCond %{REQUEST_FILENAME}	   !-f
RewriteCond %{REQUEST_FILENAME}	   !-d

RewriteRule . index.php [L]
&lt;/IfModule&gt;{/literal}</textarea>
</fieldset>

</form>
</fieldset>