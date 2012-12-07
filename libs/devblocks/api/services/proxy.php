<?php
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
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//		curl_setopt($ch, CURLOPT_TIMEOUT, 1);
//		curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);
		curl_exec($ch);
		curl_close($ch);
	}
};