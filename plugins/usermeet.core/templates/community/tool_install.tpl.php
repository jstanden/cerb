<H2>Installation</H2>
Place the following two files in a new directory on the appropriate public website.  This directory 
can be named anything but will usually describe the tool.<br>
For example: <i>http://www.cerberusweb.com/support/</i><br>
<br>

<form action="{devblocks_url}{/devblocks_url}" method="post">
<b>index.php:</b><br>
<textarea rows="10" cols="80" style="width:98%;margin:10px;">
&lt;?php
define('REMOTE_HOST', '{$host}');
define('REMOTE_BASE', '{$base}'); // NO trailing slash!
define('REMOTE_URI', '{$path}'); // NO trailing slash!
{literal}
/*
 * ====================================================================
 * [JAS]: Don't modify the following unless you know what you're doing!
 * ====================================================================
 */
define('LOCAL_HOST', $_SERVER['HTTP_HOST']);
define('LOCAL_BASE', DevblocksRouter::getLocalBase()); // NO trailing slash!
define('SCRIPT_LAST_MODIFY', 2007082801); // last change

@session_start();

class DevblocksProxy {
    function proxy($remote_host, $remote_uri, $local_path) {
        $path = explode('/', substr($local_path,1));
        
        if(0==strcasecmp($path[0],'resource')) {
            header('Pragma: cache'); 
            header('Cache-control: max-age=86400, must-revalidate'); // 1d
            header('Expires: ' . gmdate('D, d M Y H:i:s',time()+86400) . ' GMT'); // 1d

            $pathinfo = pathinfo($local_path);
            switch($pathinfo['extension']) {
            	case 'css':
            		header('Content-type: text/css;');
            		break;
            	case 'js':
            		header('Content-type: text/javascript;');
            		break;
            	case 'xml':
            		header('Content-type: text/xml;');
            		break;
            	default:
            		header('Content-type: text/html;');
            		break;
            }
            
            $remote_uri = REMOTE_BASE;
        }
        
        if($this->_isPost()) {
            $this->_post($remote_host, $remote_uri, $local_path);
        } else {
            $this->_get($remote_host, $remote_uri, $local_path);
        }
    }

    function _get($remote_host, $remote_uri, $local_path) {
        die("Subclass abstract " . __CLASS__ . "...");
    }

    function _post($remote_host, $remote_uri, $local_path) {
        die("Subclass abstract " . __CLASS__ . "...");
    }

    /**
     * @return boolean
     */
    function _isPost() {
        return !strcasecmp($_SERVER['REQUEST_METHOD'],"POST"); // 0=match
    }

    function _generateMimeBoundary() {
    	return md5(rand(0,10000).time().microtime());
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
						urlencode($k.'[]'),
						urlencode((get_magic_quotes_gpc() ? stripslashes($vv) : $vv))
					);
				}
			} else {
				$content .= sprintf("--%s\r\n".
					"content-disposition: form-data; name=\"%s\"\r\n".
					"\r\n".
					"%s\r\n",
					$boundary,
					urlencode($k),
					urlencode((get_magic_quotes_gpc() ? stripslashes($v) : $v))
				);
			}
		}
		
		// Handle files
		foreach($_FILES as $k => $file) {
			$content .= sprintf("--%s\r\n".
				"content-disposition: form-data; name=\"%s\"; filename=\"%s\"\r\n".
				"Content-Type: application/octet-stream\r\n".
				"\r\n".
				"%s\r\n",
				$boundary,
				urlencode($k),
				$file['name'],
				file_get_contents($file['tmp_name']) // [JAS] replace with a PHP4 friendly function?
			);

//			if(is_array($v)) {
//				foreach($v as $vk => $vv) {
//				    $posts[] = sprintf("%s=%s",
//					    urlencode($k.'[]'),
//					    urlencode((get_magic_quotes_gpc() ? stripslashes($vv) : $vv))
//				    );
//				}
//			} else {
//			    $posts[] = sprintf("%s=%s",
//				    $k,
//				    urlencode((get_magic_quotes_gpc() ? stripslashes($v) : $v))
//			    );
//			}
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
		    $fingerprint = array('browser'=>$_SERVER['HTTP_USER_AGENT'], 'ip'=>$_SERVER['REMOTE_ADDR'], 'local_sessid' => session_id(), 'started' => time());
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
    function _get($remote_host, $remote_uri, $local_path) {
        $fp = fsockopen($remote_host, 80, $errno, $errstr, 10);
        if ($fp) {
            $out = "GET " . $remote_uri . $local_path . " HTTP/1.1\r\n";
            $out .= "Host: $remote_host\r\n";
            $out .= 'Via: 1.1 ' . LOCAL_HOST . "\r\n";
            $out .= 'DevblocksProxyHost: ' . LOCAL_HOST . "\r\n";
            $out .= 'DevblocksProxyBase: ' . LOCAL_BASE . "\r\n";
            $out .= 'Cookie: GroupLoginPassport=' . urlencode(serialize($this->_getFingerprint())) . ';' . "\r\n";
            $out .= "Connection: Close\r\n\r\n";

            $this->_send($fp, $out);
        }
    }

    function _post($remote_host, $remote_uri, $local_path) {
        $fp = fsockopen($remote_host, 80, $errno, $errstr, 10);
        if ($fp) {
        	$boundary = $this->_generateMimeBoundary();
            $content = $this->_buildPost($boundary);
            
            $out = "POST " . $remote_uri . $local_path . " HTTP/1.1\r\n";
            $out .= "Host: $remote_host\r\n";
            $out .= 'Via: 1.1 ' . LOCAL_HOST . "\r\n";
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
	
	    $in_headers = true;
	    while (!feof($fp)) {
	        $line = fgets($fp, 1024);
	        if(0 == strcmp($line,"\r\n")) $in_headers = false;
	        
	        if($in_headers) {
	            //...
	        } else {
	            fpassthru($fp);
	        }
	    }
	    
	    fclose($fp);
    }
};

class DevblocksProxy_Curl extends DevblocksProxy {
    function _get($remote_host, $remote_uri, $local_path) {
        $url = 'http://' . $remote_host . $remote_uri . $local_path;
        $header = array();
        $header[] = 'Via: 1.1 ' . LOCAL_HOST;
        $header[] = 'DevblocksProxyHost: ' . LOCAL_HOST;
        $header[] = 'DevblocksProxyBase: ' . LOCAL_BASE;
        $header[] = 'Cookie: GroupLoginPassport=' . urlencode(serialize($this->_getFingerprint())) . ';';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_exec($ch);
        curl_close($ch);
    }

    function _post($remote_host, $remote_uri, $local_path) {
        $boundary = $this->_generateMimeBoundary();
        $content = $this->_buildPost($boundary);
    	        
        $url = 'http://' . $remote_host . $remote_uri . $local_path;
        $header = array();
        $header[] = 'Content-Type: multipart/form-data; boundary='.$boundary;
        $header[] = 'Content-Length: ' .  strlen($content);
        $header[] = 'Via: 1.1 ' . LOCAL_HOST;
        $header[] = 'DevblocksProxyHost: ' . LOCAL_HOST;
        $header[] = 'DevblocksProxyBase: ' . LOCAL_BASE;
        $header[] = 'Cookie: GroupLoginPassport=' . urlencode(serialize($this->_getFingerprint())) . ';';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_exec($ch);
        curl_close($ch);
    }
};

class DevblocksRouter {
    function connect() {
        list($local_path) = sscanf($_SERVER['REQUEST_URI'], LOCAL_BASE ."%s");
        $proxy = $this->_factory();
        $proxy->proxy(REMOTE_HOST, REMOTE_BASE . REMOTE_URI, $local_path);
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
    function getLocalBase() {
        $path = explode('/', $_SERVER['PHP_SELF']);
        array_pop($path);
        return implode('/', $path);
    }
};

$router = new DevblocksRouter();
$router->connect();
{/literal}?&gt;</textarea><br>
<br>

<b>.htaccess:</b> (Apache Web Server)<br>
<textarea rows="10" cols="80" style="width:98%;margin:10px;">{literal}
&lt;IfModule mod_rewrite.c&gt;
RewriteEngine on

RewriteCond %{REQUEST_FILENAME}       !-f
RewriteCond %{REQUEST_FILENAME}       !-d

RewriteRule . index.php [L]
&lt;/IfModule&gt;{/literal}</textarea><br>

</form>
