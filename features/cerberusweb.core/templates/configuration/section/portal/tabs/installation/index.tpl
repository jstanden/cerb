<form action="{devblocks_url}{/devblocks_url}" method="post">
<fieldset>
<legend>index.php</legend>
{$translate->_('portal.cfg.installation_instructions')|escape|nl2br nofilter}<br>
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
define('LOCAL_HOST', $_SERVER['HTTP_HOST']);
define('LOCAL_BASE', DevblocksRouter::getLocalBase()); // NO trailing slash!
define('SCRIPT_LAST_MODIFY', 2011110101); // last change

@session_start();

class DevblocksProxy {
	function proxy($local_path) {
//		echo "RPT: ",REMOTE_PROTOCOL,"<BR>";
//		echo "RH: ",REMOTE_HOST,"<BR>";
//		echo "RP: ",REMOTE_PORT,"<BR>";
//		echo "RB: ",REMOTE_BASE,"<BR>";
//		echo "RU: ",REMOTE_URI,"<BR>";
//		echo "LP: $local_path<BR>";

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

//			$pathinfo = pathinfo($local_path);
//			switch($pathinfo['extension']) {
//				case 'css':
//					header('Content-type: text/css;');
//					break;
//				case 'js':
//					header('Content-type: text/javascript;');
//					break;
//				case 'xml':
//					header('Content-type: text/xml;');
//					break;
//				default:
//					header('Content-type: text/html;');
//					break;
//			}

			$remote_path = REMOTE_BASE;
			
		} else {
			$remote_path = REMOTE_BASE . REMOTE_URI;
		}
		
		if($this->_isPost()) {
			$this->_post($remote_path, $local_path);
		} else {
			$this->_get($remote_path, $local_path);
		}
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
	
	/**
	 * @return boolean
	 */
	function _isPost() {
		return !strcasecmp($_SERVER['REQUEST_METHOD'],"POST"); // 0=match
	}

	function _generateMimeBoundary() {
		return md5(mt_rand(0,10000).time().microtime());
	}

	function _isSSL() {
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
						(get_magic_quotes_gpc() ? stripslashes($vv) : $vv)
					);
				}
			} else {
				$content .= sprintf("--%s\r\n".
					"content-disposition: form-data; name=\"%s\"\r\n".
					"\r\n".
					"%s\r\n",
					$boundary,
					$k,
					(get_magic_quotes_gpc() ? stripslashes($v) : $v)
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
						file_get_contents($file['tmp_name'][$idx]) // [JAS] replace with a PHP4 friendly function?
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
					file_get_contents($file['tmp_name']) // [JAS] replace with a PHP4 friendly function?
				);
			}
		}

		$content .= sprintf("--%s--\r\n",
			$boundary
		);
		
		return $content; // POST
	}
	
	/**
	 * @return array
	 */
	function _getFingerprint() {
		// Create a local cookie for this user to pass to Devblocks
		if(isset($_COOKIE['GroupLoginPassport'])) {
			$cookie = get_magic_quotes_gpc() ? stripslashes($_COOKIE['GroupLoginPassport']) : $_COOKIE['GroupLoginPassport'];
			$fingerprint = unserialize($cookie);
		} else {
			$fingerprint = array('browser'=>@$_SERVER['HTTP_USER_AGENT'], 'ip'=>@$_SERVER['REMOTE_ADDR'], 'local_sessid' => session_id(), 'started' => time());
			setcookie(
				'GroupLoginPassport',
				serialize($fingerprint),
				0,
				'/'
			);
		}
		return $fingerprint;
	}
};

class DevblocksProxy_Socket extends DevblocksProxy {
	function _get($remote_path, $local_path) {
		$host = (REMOTE_PROTOCOL=='https' ? 'ssl://' : 'tcp://') . REMOTE_HOST;
		$fp = fsockopen($host, REMOTE_PORT, $errno, $errstr, 10);
		
		if ($fp) {
			$out = "GET " . $remote_path . $local_path . " HTTP/1.0\r\n";
			$out .= "Host: " . REMOTE_HOST . "\r\n";
			$out .= 'Via: 1.1 ' . LOCAL_HOST . "\r\n";
			if($this->_isSSL()) $out .= 'DevblocksProxySSL: ' . '1' . "\r\n";
			$out .= 'DevblocksProxyHost: ' . LOCAL_HOST . "\r\n";
			$out .= 'DevblocksProxyBase: ' . LOCAL_BASE . "\r\n";
			$out .= 'Cookie: GroupLoginPassport=' . urlencode(serialize($this->_getFingerprint())) . ';' . "\r\n";
			$out .= "Connection: Close\r\n\r\n";

			$this->_send($fp, $out);
		}
	}

	function _post($remote_path, $local_path) {
		$host = (REMOTE_PROTOCOL=='https' ? 'ssl://' : 'tcp://') . REMOTE_HOST;
		$fp = fsockopen($host, REMOTE_PORT, $errno, $errstr, 10);
		
		if ($fp) {
			$boundary = $this->_generateMimeBoundary();
			$content = $this->_buildPost($boundary);
			
			$out = "POST " . $remote_path . $local_path . " HTTP/1.0\r\n";
			$out .= "Host: " . REMOTE_HOST . "\r\n";
			$out .= 'Via: 1.1 ' . LOCAL_HOST . "\r\n";
			if($this->_isSSL()) $out .= 'DevblocksProxySSL: ' . '1' . "\r\n";
			$out .= 'DevblocksProxyHost: ' . LOCAL_HOST . "\r\n";
			$out .= 'DevblocksProxyBase: ' . LOCAL_BASE . "\r\n";
			$out .= 'Cookie: GroupLoginPassport=' . urlencode(serialize($this->_getFingerprint())) . ';' . "\r\n";
			$out .= "Content-Type: multipart/form-data, boundary=".$boundary."\r\n";
			$out .= "Content-Length: " . strlen($content) . "\r\n";
			$out .= "Connection: Close\r\n";
			$out .= "\r\n";
			$out .= $content;
			
			$this->_send($fp, $out);
		}
	}
	
	function _send($fp, $out) {
		fwrite($fp, $out);
	
		$content = '';
		
		while(!feof($fp))
			$content .= fread($fp, 1024);		
		fclose($fp);
		
		list($headers, $content) = $this->_parseResponse($content);
		
		foreach($headers as $header) {
			// Do we need to redirect?
			if(preg_match("/^Location:/i", $header)) {
				header($header);
				exit;
			}
			// Passthru content type
			if(preg_match("/^Content-Type:/i", $header)) {
				header($header);
			}
		}
		
		echo $content;
	}
};

class DevblocksProxy_Curl extends DevblocksProxy {
	function _get($remote_path, $local_path) {
		$url = REMOTE_PROTOCOL . '://' . REMOTE_HOST . ':' . REMOTE_PORT . $remote_path . $local_path;
		
		$header = array();
		$header[] = 'Via: 1.1 ' . LOCAL_HOST;
		if($this->_isSSL()) $header[] = 'DevblocksProxySSL: 1';
		$header[] = 'DevblocksProxyHost: ' . LOCAL_HOST;
		$header[] = 'DevblocksProxyBase: ' . LOCAL_BASE;
		$header[] = 'Cookie: GroupLoginPassport=' . urlencode(serialize($this->_getFingerprint())) . ';';
		$ch = curl_init();
		$out = "";
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
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
		$header[] = 'Via: 1.1 ' . LOCAL_HOST;
		if($this->_isSSL()) $header[] = 'DevblocksProxySSL: 1';
		$header[] = 'DevblocksProxyHost: ' . LOCAL_HOST;
		$header[] = 'DevblocksProxyBase: ' . LOCAL_BASE;
		$header[] = 'Cookie: GroupLoginPassport=' . urlencode(serialize($this->_getFingerprint())) . ';';
		$ch = curl_init();
		$out = "";
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
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

		list($headers, $content) = $this->_parseResponse($out);		
		
		foreach($headers as $header) {
			// Do we need to redirect?
			if(preg_match("/^Location:/i", $header)) {
				header($header);
				exit;
			}
		}
		
		$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

		if(!empty($content_type))
			header("Content-type: " . $content_type);
		
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
		
//		echo "SRU: ",$_SERVER['REQUEST_URI'],"<BR>";
//		echo "Localbase: ",LOCAL_BASE,"<BR>";
//		echo $local_path,"<BR>";
		$proxy = $this->_factory();
		$proxy->proxy($local_path);
	}

	/**
	 * @return DevblocksProxy
	 */
	function _factory() {
		$proxy = null;

		// Determine if CURL or FSOCK is available
		if(function_exists('curl_exec')) {
			$proxy = new DevblocksProxy_Curl();
		} elseif(function_exists('fsockopen')) {
			$proxy = new DevblocksProxy_Socket();
		}

		return $proxy;
	}

	/**
	 * @static
	 * @return string
	 */
	static function getLocalBase() {
		$uri = $_SERVER['PHP_SELF'];
		if(substr($uri,-1,1)=='/') // strip trailing slash
			$uri = substr($uri,0,-1);
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
{$translate->_('portal.cfg.htaccess_hint')}<br>
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