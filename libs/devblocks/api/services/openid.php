<?php
class _DevblocksOpenIDManager {
	private static $instance = null;
	
	public static function getInstance() {
		if(null == self::$instance) {
			self::$instance = new _DevblocksOpenIDManager();
		}
		
		return self::$instance;
	}
	
	public function discover($url) {
		$num_redirects = 0;
		$is_safemode = !(ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off'));
		
		do {
			$repeat = false;
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			// We can't use option this w/ safemode enabled
			if(!$is_safemode)
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			
			$content = curl_exec($ch);
			$info = curl_getinfo($ch);
			curl_close($ch);
	
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
			
			unset($lines);
			
			// Scan headers
			foreach($headers as $header) {
				// Safemode specific behavior
				if($is_safemode) {
					if(preg_match("/^Location:.*?/i", $header)) {
						$out = explode(':', $header, 2);
						$url = isset($out[1]) ? trim($out[1]) : null;
						$repeat = true;
						break;
					}
				}
				
				// Check the headers for an 'X-XRDS-Location'
				if(preg_match("/^X-XRDS-Location:.*?/i", $header)) {
					$out = explode(':', $header, 2);
					$xrds_url = isset($out[1]) ? trim($out[1]) : null;
					
					// We have a redirect header on an XRDS document
					if(0 == strcasecmp($xrds_url, $url)) {
						$repeat = false;
						
					// We're being redirected
					} else {
						$repeat = true;
						$headers = array();
						$url = $xrds_url;
					}
					
					break;
				}
			}
			
		} while($repeat || ++$num_redirects > 10);
		
		if(isset($info['content_type']))  {
			$result = explode(';', $info['content_type']);
			$type = isset($result[0]) ? trim($result[0]) : null;
			
			$server = null;
			
			switch($type) {
				case 'application/xrds+xml':
					$xml = simplexml_load_string($content);
					
					foreach($xml->XRD->Service as $service) {
						$types = array();
						foreach($service->Type as $type) {
							$types[] = $type;
						}

						// [TODO] OpenID 1.0
						if(false !== ($pos = array_search('http://specs.openid.net/auth/2.0/server', $types))) {
							$server = $service->URI;
						} elseif(false !== ($pos = array_search('http://specs.openid.net/auth/2.0/signon', $types))) {
							$server = $service->URI;
						}
					}
					break;
					
				case 'text/html':
					// [TODO] This really needs to parse syntax better (can be single or double quotes, and attribs in any order)
					preg_match("/<link rel=\"openid.server\" href=\"(.*?)\"/", $content, $found);
					if($found && isset($found[1]))
						$server = $found[1];
						
					preg_match("/<link rel=\"openid.delegate\" href=\"(.*?)\"/", $content, $found);
					if($found && isset($found[1]))
						$delegate = $found[1];
						
					break;
					
				default:
					break;
			}

			return $server;
		}		
	}
	
	public function getAuthUrl($openid_identifier, $return_to) {
		$url_writer = DevblocksPlatform::getUrlService();
		
		// Normalize the URL
		$parts = parse_url($openid_identifier);
		if(!isset($parts['scheme'])) {
			$openid_identifier = 'http://' . $openid_identifier;
		}
		
		$server = $this->discover($openid_identifier);
		
		if(empty($server))
			return FALSE;
		
		$parts = explode('?', $server, 2);
		$url = isset($parts[0]) ? $parts[0] : '';
		$query = isset($parts[1]) ? ('?'.$parts[1]) : '';
		
		$query .= (!empty($query)) ? '&' : '?';
		$query .= "openid.mode=checkid_setup";
		$query .= "&openid.claimed_id=".urlencode("http://specs.openid.net/auth/2.0/identifier_select");
		$query .= "&openid.identity=".urlencode("http://specs.openid.net/auth/2.0/identifier_select");
		$query .= "&openid.realm=".urlencode($url_writer->write('',true));
		$query .= "&openid.ns=".urlencode("http://specs.openid.net/auth/2.0");
		$query .= "&openid.return_to=".urlencode($return_to);
		
		// AX 1.0 (axschema)
		$query .= "&openid.ns.ax=".urlencode("http://openid.net/srv/ax/1.0");
		$query .= "&openid.ax.mode=".urlencode("fetch_request");
		$query .= "&openid.ax.type.nickname=".urlencode('http://axschema.org/namePerson/friendly');
		$query .= "&openid.ax.type.fullname=".urlencode('http://axschema.org/namePerson');
		$query .= "&openid.ax.type.email=".urlencode('http://axschema.org/contact/email');
		$query .= "&openid.ax.required=".urlencode('email,nickname,fullname');
		
		// SREG 1.1
		$query .= "&openid.ns.sreg=".urlencode('http://openid.net/extensions/sreg/1.1');
		$query .= "&openid.sreg.required=".urlencode("nickname,fullname,email");
		$query .= "&openid.sreg.optional=".urlencode("dob,gender,postcode,country,language,timezone");
		
		return $url.$query;
	}
	
	public function validate($scope) {
		$url_writer = DevblocksPlatform::getUrlService();
		
		if(!isset($scope['openid_identity']))
			return false;
		
		$openid_identifier = $scope['openid_identity'];
		
		$server = $this->discover($openid_identifier);
		
		$parts = explode('?', $server, 2);
		$url = isset($parts[0]) ? $parts[0] : '';
		$query = isset($parts[1]) ? ('?'.$parts[1]) : '';
		
		$query .= (!empty($query)) ? '&' : '?';
		$query .= "openid.ns=".urlencode("http://specs.openid.net/auth/2.0");
		$query .= "&openid.mode=check_authentication";
		$query .= "&openid.sig=".urlencode($_GET['openid_sig']);
		$query .= "&openid.signed=".urlencode($_GET['openid_signed']);
		
		// Append all the tokens used in the signed
		$tokens = explode(',', $scope['openid_signed']);
		foreach($tokens as $token) {
			switch($token) {
				case 'mode':
				case 'ns':
				case 'sig':
				case 'signed':
					break;
					
				default:
					$key = str_replace('.', '_', $token);
					
					if(isset($scope['openid_'.$key])) {
						$query .= sprintf("&openid.%s=%s",
							$token,
							urlencode($scope['openid_'.$key])
						);
					}
					break;
			}
		}
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url.$query);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($ch);
		curl_close($ch);
		
		if(preg_match('/is_valid:true/', $response))
			return true;
		else
			return false;
	}
	
	public function getAttributes($scope) {
		$ns = array();
		$attribs = array();
		
		foreach($scope as $ns => $spec) {
			// Namespaces
			if(preg_match("/^openid_ns_(.*)$/",$ns,$ns_found)) {
				switch(strtolower($spec)) {
					case 'http://openid.net/srv/ax/1.0';
						foreach($scope as $k => $v) {
							if(preg_match("/^openid_".$ns_found[1]."_value_(.*)$/i",$k,$attrib_found)) {
								$attribs[strtolower($attrib_found[1])] = $v;
							}
						}
						break;
						
					case 'http://openid.net/srv/sreg/1.0';
					case 'http://openid.net/extensions/sreg/1.1';
						foreach($scope as $k => $v) {
							if(preg_match("/^openid_".$ns_found[1]."_(.*)$/i",$k,$attrib_found)) {
								$attribs[strtolower($attrib_found[1])] = $v;
							}
						}
						break;
				}
			}
		}
		
		return $attribs;
	}
};