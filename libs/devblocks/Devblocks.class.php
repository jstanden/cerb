<?php
include_once(DEVBLOCKS_PATH . "api/Engine.php");

include_once(DEVBLOCKS_PATH . "api/services/bootstrap/logging.php");
include_once(DEVBLOCKS_PATH . "api/services/bootstrap/cache.php");
include_once(DEVBLOCKS_PATH . "api/services/bootstrap/database.php");
include_once(DEVBLOCKS_PATH . "api/services/bootstrap/classloader.php");

define('PLATFORM_BUILD', 2015101601);

/**
 * A platform container for plugin/extension registries.
 *
 * @author Jeff Standen <jeff@webgroupmedia.com>
 */
class DevblocksPlatform extends DevblocksEngine {
	private function __construct() { return false; }

	static function installPluginZip($zip_filename) {
		$plugin_path = APP_STORAGE_PATH . '/plugins/';
		
		// Check write access
		if(!(is_dir($plugin_path) && is_writeable($plugin_path)))
			return false;
		
		// Unzip (Devblocks ZipArchive or pclzip)
		if(extension_loaded('zip')) {
			$zip = new ZipArchive();
			$result = $zip->open($zip_filename);
			
			// Read the plugin.xml file
			for($i=0;$i<$zip->numFiles;$i++) {
				$path = $zip->getNameIndex($i);
				if(preg_match("#/plugin.xml$#", $path)) {
					$manifest_fp = $zip->getStream($path);
					$manifest_data = stream_get_contents($manifest_fp);
					fclose($manifest_fp);
					$xml = simplexml_load_string($manifest_data);
					$plugin_id = (string) $xml->id;
					//[TODO] Check version info
				}
			}
			
			$zip->extractTo($plugin_path);
	
		} else {
			$zip = new PclZip($zip_filename);
			
			$contents = $zip->extract(PCLZIP_OPT_BY_PREG, "#/plugin.xml$#", PCLZIP_OPT_EXTRACT_AS_STRING);
			$manifest_data = $contents[0]['content'];
			
			$xml = simplexml_load_string($manifest_data);
			$plugin_id = (string) $xml->id;

			$list = $zip->extract(PCLZIP_OPT_PATH, $plugin_path, PCLZIP_OPT_REPLACE_NEWER);
		}
		
		if(empty($plugin_id))
			return false;
		
		return true;
	}
	
	static function installPluginZipFromUrl($url) {
		if(!extension_loaded('curl'))
			return;
		
		$fp = DevblocksPlatform::getTempFile();
		$fp_filename = DevblocksPlatform::getTempFileInfo($fp);
		
		$ch = curl_init($url);
		curl_setopt_array($ch, array(
			CURLOPT_CUSTOMREQUEST => 'GET',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_SSL_VERIFYPEER => false,
			//CURLOPT_FILE => $fp,
		));
		$data = curl_exec($ch);
		
		if(curl_errno($ch)) {
			//curl_error($ch);
			fclose($fp);
			return false;
		}
		
		// [TODO] Check status
		//$info = curl_getinfo($ch);
		//var_dump($info);
		//$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		// Write
		fwrite($fp, $data, strlen($data));
		fclose($fp);
		curl_close($ch);
		
		return self::installPluginZip($fp_filename);
	}
	
	static function uninstallPlugin($plugin_id) {
		if(null !== ($plugin = DevblocksPlatform::getPlugin($plugin_id))) {
			$plugin->uninstall();
			DevblocksPlatform::readPlugins(false);
		}
	}

	/**
	 * @param mixed $value
	 * @param string $type
	 * @param mixed $default
	 * @return mixed
	 * @test DevblocksPlatformTest
	 */
	static function importVar($value, $type=null, $default=null) {
		if(is_null($value) && !is_null($default))
			$value = $default;
		
		if(substr($type,0,6) == 'array:') {
			list($type, $array_cast) = explode(':', $type, 2);
		}
		
		// Sanitize input
		switch($type) {
			case 'array':
				if(!is_array($value)) {
					if(is_null($value))
						$value = array();
					else
						$value = array($value);
				}
				
				if(isset($array_cast))
					$value = DevblocksPlatform::sanitizeArray($value, $array_cast);
				break;
				
			case 'bit':
				$value = !empty($value) ? 1 : 0;
				break;
				
			case 'bool':
			case 'boolean':
				if(is_string($value) && in_array(strtolower($value), array('true', 'false')))
					return (0 == strcasecmp($value, 'true')) ? true : false;
					
				if(is_string($value) && in_array(strtolower($value), array('yes', 'no')))
					return (0 == strcasecmp($value, 'yes')) ? true : false;
				
				$value = !empty($value) ? true : false;
				break;
				
			case 'float':
				$value = floatval($value);
				break;
				
			case 'int':
			case 'integer':
				$value = intval($value);
				break;
				
			case 'string':
				if(is_bool($value))
					return $value ? 'true' : 'false';
				
				@$value = (string) $value;
				break;
				
			case 'timestamp':
				if(!is_numeric($value)) {
					try {
						$value = strtotime($value);
					} catch(Exception $e) {}
				} else {
					$value = abs(intval($value));
				}
				break;
				
			default:
				@settype($value,$type);
				break;
		}
		
		return $value;
	}
	
	/**
	 * @param mixed $var
	 * @param string $cast
	 * @param mixed $default
	 * @return mixed
	 * @test DevblocksPlatformTest
	 */
	static function importGPC($var, $cast=null, $default=null) {
		@$magic_quotes = (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) ? true : false;

		if(!is_null($var)) {
			if(is_string($var)) {
				$var = $magic_quotes ? stripslashes($var) : $var;
			} elseif(is_array($var)) {
				if($magic_quotes)
					array_walk_recursive($var, array('DevblocksPlatform','_stripMagicQuotes'));
			}
			
		} elseif (is_null($var) && !is_null($default)) {
			$var = $default;
		}

		if(!is_null($cast))
			$var = self::importVar($var, $cast, $default);
		
		return $var;
	}

	static private function _stripMagicQuotes(&$item, $key) {
		if(is_string($item))
			$item = stripslashes($item);
	}

	/**
	 * 
	 * @param integer $n The number to test
	 * @param integer $min Inclusive lower bounds
	 * @param integer $max Inclusive upper bounds
	 * @return integer
	 * @test DevblocksPlatformTest
	 */
	static function intClamp($n, $min, $max) {
		return min(max((integer)$n, $min), $max);
	}
	
	/**
	 * 
	 * @param float $n The number to test
	 * @param float $min Inclusive lower bounds
	 * @param float $max Inclusive upper bounds
	 * @return float
	 * @test DevblocksPlatformTest
	 */
	static function floatClamp($n, $min, $max) {
		return min(max((float)$n, $min), $max);
	}
	
	/**
	 * Returns a string as a regexp.
	 * "*bob" returns "/(.*?)bob/".
	 * @param string $string
	 * @return string
	 * @test DevblocksPlatformTest
	 */
	static function parseStringAsRegExp($string) {
		$pattern = str_replace(array('*'),'__any__', $string);
		$pattern = sprintf("/%s/i",str_replace(array('__any__'),'(.*?)', preg_quote($pattern)));
		return $pattern;
	}
	
	/**
	 * Returns a formatted string as a number of bytes (e.g. 200M = 209715200)
	 *
	 * @param string $string
	 * @return integer|FALSE
	 * @test DevblocksPlatformTest
	 */
	static function parseBytesString($string) {
		if(is_numeric($string)) {
			return intval($string);
			
		} else {
			$value = intval($string);
			$unit = strtolower(substr($string, -1));
			 
			switch($unit) {
				default:
				case 'b':
					return $value;
					break;
				case 'k':
					return $value * 1024; // 1024^1
					break;
				case 'm':
					return $value * 1048576; // 1024^2
					break;
				case 'g':
					return $value * 1073741824; // 1024^3
					break;
			}
		}
		
		return FALSE;
	}
	
	/**
	 * 
	 * @param string $string
	 * @param boolean $keep_blanks
	 * @param boolean $trim_lines
	 * @return array
	 * @test DevblocksPlatformTest
	 */
	static function parseCrlfString($string, $keep_blanks=false, $trim_lines=true) {
		$string = str_replace("\r\n","\n",$string);
		$parts = preg_split("/[\r\n]/", $string);
		
		// Remove any empty tokens
		foreach($parts as $idx => $part) {
			$parts[$idx] = $trim_lines ? trim($part) : $part;
			if(!$keep_blanks && 0 == strlen($parts[$idx]))
				unset($parts[$idx]);
		}
		
		return $parts;
	}
	
	/**
	 * 
	 * @param unknown $string
	 * @return array
	 * @test DevblocksPlatformTest
	 */
	static function parseAtMentionString($string) {
		//$string = "@Hildy Do you have time for this today?  If not, ask @Jeff, or @Darren.";
		preg_match_all('#(\@[A-Za-z0-9_]+)([^A-Za-z0-9_]|$)#', $string, $matches);
		
		if(is_array($matches) && isset($matches[1])) {
			return array_unique($matches[1]);
		}
		
		return false;
	}
	
	/**
	 * 
	 * @param array $objects
	 * @return string[]
	 * @test DevblocksPlatformTest
	 */
	static function objectsToStrings($objects) {
		$strings = array();
		
		if(is_array($objects))
		foreach($objects as $k => $o) {
			$strings[$k] = (string) $o;
		}
		
		return $strings;
	}
	
	/**
	 * 
	 * @param integer $version
	 * @param integer $sections
	 * @return string A dot-delimited version string
	 * @test DevblocksPlatformTest
	 */
	static function intVersionToStr($version, $sections=3) {
		// If it's not an even number length, pad with one 0 on the left (e.g. 709 -> 0709)
		if(strlen($version))
			$version = '0' . $version;
		
		// If we don't have enough requested sections, pad the right.
		// We assume the given digits are always the most significant part of the version.
		$version = str_pad($version, $sections * 2, '0', STR_PAD_LEFT);
		$parts = str_split($version, 2);
		
		// Trim padded zeroes in a version section
		foreach($parts as $k => $v)
			$parts[$k] = intval($v);
		
		// Slice the version to the requested length
		$parts = array_slice($parts, 0, $sections);
		
		// Return as a dot-delimited string
		return implode('.', $parts);
	}
	
	/**
	 * 
	 * @param string $version
	 * @param integer $sections
	 * @return integer
	 * @test DevblocksPlatformTest
	 */
	static function strVersionToInt($version, $sections=3) {
		$parts = explode('.', $version);
		
		// Trim versions with too many significant places
		if(count($parts) > $sections)
			$parts = array_slice($parts, 0, $sections);
		
		// Pad versions with too few significant places
		while(count($parts) < $sections)
			array_push($parts, '0');
			
		$v = 0;
		$multiplier = 1;
		foreach(array_reverse($parts) as $part) {
			$v += intval($part)*$multiplier;
			$multiplier *= 100;
		}
		
		return intval($v);
	}
	
	/**
	 * 
	 * @param string $a
	 * @param string $b
	 * @param string $oper
	 * @return bool
	 * @test DevblocksPlatformTest
	 */
	public static function compareStrings($a, $b, $oper) {
		@$not = (substr($oper, 0, 1) == '!');
		@$oper = ltrim($oper, '!');
		
		$pass = false;
		
		switch($oper) {
			case '=':
			case 'is':
				$pass = (0==strcasecmp($a, $b));
				break;
			case 'like':
				$regexp = DevblocksPlatform::strToRegExp($b);
				$pass = @preg_match($regexp, $a);
				break;
			case 'contains':
				$pass = (false !== stripos($a, $b)) ? true : false;
				break;
			case 'regexp':
				$pass = @preg_match($b, $a);
				break;
		}
		
		return ($not) ? !$pass : $pass;
	}
	
	/**
	 * Return a string as a regular expression, parsing * into a non-greedy
	 * wildcard, etc.
	 *
	 * @param string $arg
	 * @param boolean $is_partial
	 * @return string
	 * @test DevblocksPlatformTest
	 */
	static function strToRegExp($arg, $is_partial=false) {
		$arg = str_replace(array('*'),array('__WILD__'),$arg);
		
		return sprintf("/%s%s%s/i",
			($is_partial ? '' : '^'),
			str_replace(array('__WILD__','/'),array('.*?','\/'),preg_quote($arg)),
			($is_partial ? '' : '$')
		);
	}
	
	/**
	 * Return a string with only its alphanumeric characters
	 *
	 * @param string $arg
	 * @param string $also
	 * @param string $replace
	 * @return string
	 * @test DevblocksPlatformTest
	 */
	static function strAlphaNum($arg, $also=null, $replace="") {
		return preg_replace("/[^A-Z0-9" . $also . "]/i", $replace, $arg);
	}
	
	/**
	 * 
	 * @param string $string
	 * @param string $from_encoding
	 * @return string
	 * @test DevblocksPlatformTest
	 */
	static function strUnidecode($string, $from_encoding = 'utf-8') {
		if(empty($string))
			return $string;
		
		$len = strlen($string);
		$out = '';
			
		$string = (is_null($from_encoding))
			? mb_convert_encoding($string, "UCS-4BE")
			: mb_convert_encoding($string, "UCS-4BE", $from_encoding)
			;
		
		while(false !== ($part = mb_substr($string, 0, 25000)) && 0 !== mb_strlen($part)) {
			$string = mb_substr($string, mb_strlen($part));
			
			$unpack = unpack("N*", $part);
			
			foreach($unpack as $k => $v) {
				$out .= self::_strUnidecodeLookup($v);
			}
			
			unset($unpack);
		}

		return $out;
	}
	
	/**
	 * 
	 * @param string $str
	 * @return string
	 * @test DevblocksPlatformTest
	 */
	static function strBase32Encode($str) {
		// RFC-4648
		$alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567";
		$binary = '';
		$output = '';
		$quantum = 0;
		
		// Read each ASCII character as binary octets
		foreach(str_split($str, 1) as $letter) {
			$binary .= sprintf("%08b", ord($letter));
		}
		
		// Rechunk the octets to 5-bit
		foreach(str_split($binary, 5) as $bits) {
			$quantum += strlen($bits);
			$bits = str_pad($bits, 5, '0');
			$output .= $alphabet[bindec($bits)];
		}
		
		// If our last quantum is less than 40 bits, pad
		// [TODO] There's likely a more logical way to do this
		switch($quantum % 40) {
			case 8:
				$output .= '======';
				break;
			case 16:
				$output .= '====';
				break;
			case 24:
				$output .= '===';
				break;
			case 32:
				$output .= '=';
				break;
		}
		
		return $output;
	}
	
	/**
	 * 
	 * @param string $str
	 * @param boolean $as_string
	 * @return string
	 * @test DevblocksPlatformTest
	 */
	static function strBase32Decode($str, $as_string=false) {
		// RFC-4648
		$alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567";
		$binary = '';
		$output = '';
		$pads = 0;
		
		// Iterate each letter of base32
		foreach(str_split(strtoupper($str), 1) as $idx => $letter) {
			// If padding, skip
			if($letter == '=') {
				$pads++;
				continue;
			}
	
			// Append the letter's position (0-31) as 5 bits (2^5) in binary
			$binary .= sprintf("%05b", strpos($alphabet, $letter));
		}
	
		// Split the new binary string into 8-bit octets
		foreach(str_split($binary, 8) as $idx => $byte) {
			// Skip empty octets
			if($byte == '00000000') {
				$output .= "\0";
				continue;
			}
			
			// Concat the corresponding ASCII char for each octet
			$output .= chr(bindec($byte));
		}
		
		if($as_string)
			$output = trim($output);
		
		return $output;
	}
	
	/**
	 * 
	 * @param string $str
	 * @param boolean $strip_whitespace
	 * @param boolean $skip_blockquotes
	 * @return string
	 * @test DevblocksPlatformTest
	 */
	static function stripHTML($str, $strip_whitespace=true, $skip_blockquotes=false) {
		
		// Pre-process some HTML entities that confuse UTF-8
		
		$str = str_ireplace(
			array(
				'&rsquo;',     // '
				'&#8217;',
				'&#x2019;',
				'&hellip;',    // ...
				'&#8230;',
				'&#x2026;',
				'&ldquo;',     // "
				'&#8220;',
				'&#x201c;',
				'&rdquo;',     // "
				'&#8221;',
				'&#x201d;',
			),
			array(
				"'",
				"'",
				"'",
				'...',
				'...',
				'...',
				'"',
				'"',
				'"',
				'"',
				'"',
				'"',
			),
			$str
		);
		
		// Pre-process blockquotes
		if(!$skip_blockquotes) {
			$dom = new DOMDocument('1.0', LANG_CHARSET_CODE);
			$dom->strictErrorChecking = false;
			$dom->recover = true;
			$dom->validateOnParse = false;
			
			libxml_use_internal_errors(true);
			
			$dom->loadHTML(sprintf('<?xml version="1.0" encoding="%s">', LANG_CHARSET_CODE) . $str);
			
			$errors = libxml_get_errors();
			libxml_clear_errors();
			
			$xpath = new DOMXPath($dom);
			
			while(($blockquotes = $xpath->query('//blockquote')) && $blockquotes->length) {
			
				foreach($blockquotes as $blockquote) { /* @var $blockquote DOMElement */
					$nested = $xpath->query('.//blockquote', $blockquote);
					
					// If the blockquote contains another blockquote, ignore it for now
					if($nested->length > 0)
						continue;
					
					// Change the blockquote tags to DIV, prefixed with '>'
					$div = $dom->createElement('span');
					
					$plaintext = DevblocksPlatform::stripHTML($dom->saveXML($blockquote), $strip_whitespace, true);
					
					$out = explode("\n", trim($plaintext));
					
					array_walk($out, function($line) use ($dom, $div) {
						$text = $dom->createTextNode('> ' . $line);
						$div->appendChild($text);
						$div->appendChild($dom->createElement('br'));
					});
					
					$blockquote->parentNode->replaceChild($div, $blockquote);
				}
			}
			
			$html = $dom->saveXML();
			
			// Make sure it's not blank before trusting it.
			if(!empty($html)) {
				$str = $html;
				unset($html);
			}
		}
		
		// Convert hyperlinks to plaintext
		
		$str = preg_replace_callback(
			'@<a[^>]*?>(.*?)</a>@si',
			function($matches) {
				if(!isset($matches[0]))
					return false;
				
				$out = '';
				
				if(false == ($dom = simplexml_load_string($matches[0])))
					return false;
				
				@$href_link = $dom['href'];
				@$href_label = (string) $dom;
				
				// Skip if there is no label text (images, etc)
				if(empty($href_label)) {
					$out = null;
					
				// If the link and label are the same, ignore label
				} elseif($href_label == $href_link) {
					$out = $href_link;
					
				// Otherwise, format like Markdown
				} else {
					$out = sprintf("[%s](%s)",
						$href_label,
						$href_link
					);
				}
				
				return $out;
			},
			$str
		);
		
		// Code blocks to plaintext
		
		$str = preg_replace_callback(
			'@<code[^>]*?>(.*?)</code>@si',
			function($matches) {
				if(isset($matches[1])) {
					$out = $matches[1];
					$out = str_replace(" ","&nbsp;", $out);
					return $out;
				}
			},
			$str
		);
		
		// Preformatted blocks to plaintext
		
		$str = preg_replace_callback(
			'#<pre.*?/pre\>#si',
			function($matches) {
				if(isset($matches[0])) {
					$out = $matches[0];
					$out = str_replace("\n","<br>", trim($out));
					return '<br>' . $out . '<br>';
				}
			},
			$str
		);
		
		// Unordered and ordered lists
		
		$dom = new DOMDocument('1.0', LANG_CHARSET_CODE);
		$dom->strictErrorChecking = false;
		$dom->recover = true;
		$dom->validateOnParse = false;
		
		libxml_use_internal_errors(true);
		
		$dom->loadHTML(sprintf('<?xml version="1.0" encoding="%s">', LANG_CHARSET_CODE) . $str);
		
		$errors = libxml_get_errors();
		libxml_clear_errors();
		
		$xpath = new DOMXPath($dom);
		
		// Ordered lists
		
		$lists = $xpath->query('//ol');
		
		foreach($lists as $list) { /* @var $list DOMElement */
			$items = $xpath->query('./li/text()', $list);
			
			$counter = 1;
			foreach($items as $item) { /* @var $item DOMText */
				$txt = $dom->createTextNode('');
				$txt->nodeValue = $counter++ . '. ' . $item->nodeValue;
				$item->parentNode->replaceChild($txt, $item);
			}
		}

		// Unordered lists
		
		$lists = $xpath->query('//ul');
		
		foreach($lists as $list) { /* @var $list DOMElement */
			$items = $xpath->query('./li/text()', $list);
			
			foreach($items as $idx => $item) { /* @var $item DOMText */
				$txt = $dom->createTextNode('- ' . $item->nodeValue);
				$item->parentNode->replaceChild($txt, $item);
			}
		}
		
		$html = $dom->saveXML();
		
		// Make sure it's not blank before trusting it.
		if(!empty($html)) {
			$str = $html;
			unset($html);
		}
		
		// Strip all CRLF and tabs, spacify </TD>
		if($strip_whitespace) {
			$str = str_ireplace(
				array("\r","\n","\t","</td>"),
				array('','',' ',' '),
				trim($str)
			);
			
		} else {
			$str = str_ireplace(
				array("\t","</td>"),
				array(' ',' '),
				trim($str)
			);
		}
		
		// Convert Unicode nbsp to space
		$str = preg_replace(
			'#\xc2\xa0#',
			' ',
			$str
		);
		
		// Handle XHTML variations
		$str = preg_replace(
			'@<br[^>]*?>@si',
			"<br>",
			$str
		);
		
		// Turn block tags into a linefeed
		$str = str_ireplace(
			array(
				'<BR>',
				'<P>',
				'</P>',
				'</PRE>',
				'<HR>',
				'<TR>',
				'</H1>',
				'</H2>',
				'</H3>',
				'</H4>',
				'</H5>',
				'</H6>',
				'</DIV>',
				'<UL>',
				'</UL>',
				'<OL>',
				'</OL>',
				'</LI>',
				'</OPTION>',
				'<TABLE>',
				'</TABLE>',
			),
			"\n",
			$str
		);

		// Strip non-content tags
		$search = array(
			'@<head[^>]*?>.*?</head>@si',
			'@<style[^>]*?>.*?</style>@si',
			'@<script[^>]*?.*?</script>@si',
			'@<object[^>]*?.*?</object>@si',
			'@<embed[^>]*?.*?</embed>@si',
			'@<applet[^>]*?.*?</applet>@si',
			'@<noframes[^>]*?.*?</noframes>@si',
			'@<noscript[^>]*?.*?</noscript>@si',
			'@<noembed[^>]*?.*?</noembed>@si',
		);
		$str = preg_replace($search, '', $str);
		
		// Strip tags
		$str = strip_tags($str);
		
		// Flatten multiple spaces into a single
		$str = preg_replace('# +#', ' ', $str);

		// Flatten multiple linefeeds into a single
		$str = preg_replace("#\n{2,}#", "\n\n", $str);
		
		// Translate HTML entities into text
		$str = html_entity_decode($str, ENT_COMPAT, LANG_CHARSET_CODE);

		// Wrap quoted lines
		// [TODO] This should be more reusable
		$str = _DevblocksTemplateManager::modifier_devblocks_email_quote($str);
		
		// Clean up bytes (needed after HTML entities)
		$str = mb_convert_encoding($str, LANG_CHARSET_CODE, LANG_CHARSET_CODE);
		
		return ltrim($str);
	}
	
	/**
	 * 
	 * @param string $dirty_html
	 * @param boolean $inline_css
	 * @param array $options
	 * @return string
	 * @test DevblocksPlatformTest
	 */
	static function purifyHTML($dirty_html, $inline_css=false, $options=array()) {
		require_once(DEVBLOCKS_PATH . 'libs/htmlpurifier/HTMLPurifier.standalone.php');
		
		// If we're passed a file pointer, load the literal string
		if(is_resource($dirty_html)) {
			$fp = $dirty_html;
			$dirty_html = null;
			while(!feof($fp))
				$dirty_html .= fread($fp, 4096);
		}
		
		// Handle inlining CSS
		
		if($inline_css) {
			$css_converter = new TijsVerkoyen\CssToInlineStyles\CssToInlineStyles();
			$css_converter->setEncoding(LANG_CHARSET_CODE);
			$css_converter->setHTML(sprintf('<?xml encoding="%s">', LANG_CHARSET_CODE) . $dirty_html);
			$css_converter->setUseInlineStylesBlock(true);
			$dirty_html = $css_converter->convert();
			unset($css_converter);
		}
		
		// Purify
		
		$config = HTMLPurifier_Config::createDefault();
		$config->set('Core.ConvertDocumentToFragment', true);
		$config->set('HTML.Doctype', 'HTML 4.01 Transitional');
		$config->set('CSS.AllowTricky', true);
		
		// Remove class attributes if we inlined CSS styles
		if($inline_css) {
			$config->set('HTML.ForbiddenAttributes', array(
				'class',
			));
		}
		
		$config->set('URI.AllowedSchemes', array(
			'http' => true,
			'https' => true,
			'mailto' => true,
			'ftp' => true,
			'nntp' => true,
			'news' => true,
			'data' => true,
		));
		
		$dir_htmlpurifier_cache = APP_TEMP_PATH . '/cache/htmlpurifier/';
		
		if(!is_dir($dir_htmlpurifier_cache)) {
			mkdir($dir_htmlpurifier_cache, 0755);
		}
		
		$config->set('Cache.SerializerPath', $dir_htmlpurifier_cache);
		
		// Set any config overrides
		if(is_array($options) && !empty($options))
		foreach($options as $k => $v) {
			$config->set($k, $v);
		}
		
		$purifier = new HTMLPurifier($config);
		
		$dirty_html = $purifier->purify($dirty_html);
		
		return $dirty_html;
	}
	
	/**
	 * 
	 * @param string $text
	 * @return string
	 * @test DevblocksPlatformTest
	 */
	static function parseMarkdown($text) {
		$parser = new Parsedown();
		$parser->setBreaksEnabled(true);
		$parser->setMarkupEscaped(false);
		return $parser->parse($text);
	}
	
	static function parseRss($url) {
		// [TODO] curl | file_get_contents() support
		
		// Handle 'feed://' scheme
		if(preg_match('/^feed\:/', $url)) {
			$url = preg_replace("/^feed\:\/\//","http://", $url);
			$url = preg_replace("/^feed\:/","", $url);
		}
		
		if(extension_loaded("curl")) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			
			$user_agent = 'Cerb ' . APP_VERSION . ' (Build ' . APP_BUILD . ')';
			curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
			
			$is_safemode = !(ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off'));
	
			// We can't use option this w/ safemode enabled
			if(!$is_safemode)
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			
			$data = curl_exec($ch);
			curl_close($ch);
			
		} elseif(ini_get('allow_url_fopen')) {
			@$data = file_get_contents($url);
			
		} else {
			$logger = DevblocksPlatform::getConsoleLog();
			$logger->error("[Platform] 'curl' extension is not enabled and 'allow_url_fopen' is Off. Can not load a URL.");
			return;
		}
		
		if(empty($data))
			return true;
		
		if(null == (@$xml = simplexml_load_string($data)))
			return false;
			
		$root_tag = strtolower(dom_import_simplexml($xml)->tagName);
		
		if('feed'==$root_tag && count($xml->entry)) { // Atom
			$feed = array(
				'title' => (string) $xml->title,
				'url' => $url,
				'items' => array(),
			);
			
			if(!count($xml))
				return $feed;
	
			foreach($xml->entry as $entry) {
				$id = (string) $entry->id;
				$date = (string) $entry->published;
				$title = (string) $entry->title;
				$content = (string) $entry->summary;
				$link = '';

				// Fallbacks
				if(empty($date))
					$date = (string) $entry->updated;

				$date_timestamp = strtotime($date);
					
				// Link as the <id> element
				if(preg_match("/^(.*)\:\/\/(.*$)/i", $id, $matches)) {
					$link = $id;
				// Link as 'alternative' attrib
				} elseif(count($entry->link)) {
					foreach($entry->link as $link) {
						if(0==strcasecmp('alternate',(string)$link['rel'])) {
							$link = (string) $link['href'];
							break;
						}
					}
				}
				 
				$feed['items'][] = array(
					'date' => empty($date_timestamp) ? time() : $date_timestamp,
					'link' => $link,
					'title' => $title,
					'content' => $content,
				);
			}
			
		} elseif('rdf:rdf'==$root_tag && count($xml->item)) { // RDF
			$feed = array(
				'title' => (string) $xml->channel->title,
				'url' => $url,
				'items' => array(),
			);
			
			if(!count($xml))
				return $feed;
	
			foreach($xml->item as $item) {
				$date = (string) $item->pubDate;
				$link = (string) $item->link;
				$title = (string) $item->title;
				$content = (string) $item->description;
				
				$date_timestamp = strtotime($date);
				
				$feed['items'][] = array(
					'date' => empty($date_timestamp) ? time() : $date_timestamp,
					'link' => $link,
					'title' => $title,
					'content' => $content,
				);
			}
			
		} elseif('rss'==$root_tag && count($xml->channel->item)) { // RSS
			$feed = array(
				'title' => (string) $xml->channel->title,
				'url' => $url,
				'items' => array(),
			);
			
			if(!count($xml))
				return $feed;
	
			foreach($xml->channel->item as $item) {
				$date = (string) $item->pubDate;
				$link = (string) $item->link;
				$title = (string) $item->title;
				$content = (string) $item->description;

				$date_timestamp = strtotime($date);
				
				$feed['items'][] = array(
					'date' => empty($date_timestamp) ? time() : $date_timestamp,
					'link' => $link,
					'title' => $title,
					'content' => $content,
				);
			}
		}

		if(empty($feed))
			return false;
		
		return $feed;
	}
	
	static function strEscapeHtml($string) {
		if(empty($string))
			return '';
		
		return htmlentities($string, ENT_QUOTES, LANG_CHARSET_CODE);
	}

	/**
	 * Returns a string as alphanumerics delimited by underscores.
	 * For example: "Devs: 1000 Ways to Improve Sales" becomes
	 * "devs-1000-ways-to-improve-sales", which is suitable for
	 * displaying in a URL of a blog, faq, etc.
	 *
	 * @param string $str
	 * @return string
	 * @test DevblocksPlatformTest
	 */
	static function strToPermalink($string, $spaces_as='-') {
		if(empty($string))
			return '';
		
		// Unidecode
		$string = DevblocksPlatform::strUnidecode($string, LANG_CHARSET_CODE);
		
		// Remove certain marks
		$string = preg_replace('#[\'\"]#', '', $string);
		
		// Strip all punctuation to underscores
		$string = preg_replace('#[^a-zA-Z0-9\+\.\-_\(\)]#', $spaces_as, $string);
			
		// Collapse all underscores to singles
		$string = preg_replace(('#' . $spaces_as . $spaces_as . '+#'), $spaces_as, $string);
		
		return rtrim($string, $spaces_as);
	}
	
	/**
	 * 
	 * @param string $string
	 * @return string
	 * @test DevblocksPlatformTest
	 */
	static function strToHyperlinks($string) {
		// Bail out if we're asked to auto-hyperlink a huge block of text
		if(strlen($string) > 512000)
			return $string;
		
		return preg_replace_callback('@([^\s]+){0,1}(https?://(.*?))((?:[>"\.\?,\)]{0,1}(\s|$))|(&(?:quot|gt);))@i', function($matches) {
			$prefix = $matches[1];
			$url = $matches[2];
			$suffix = $matches[4];
			
			// Fix unbalanced terminators
			switch($suffix) {
				case ')':
					if($prefix != '(') {
						$url .= $suffix;
						$suffix = '';
					}
					break;
			}
			
			return sprintf('%s<a href="%s" target="_blank">%s</a>%s',
				$prefix,
				$url,
				$url,
				$suffix
			);
		}, $string);
	}
	
	/**
	 * 
	 * @param string $string
	 * @param integer $length
	 * @return string
	 * @test DevblocksPlatformTest
	 */
	static function strSecsToString($string, $length=0) {
		if(empty($string) || !is_numeric($string))
			return '0 secs';
		
		$blocks = array(
			'year' => 52*7*24*60*60,
			'month' => 30*24*60*60,
			'week' => 7*24*60*60,
			'day' => 24*60*60,
			'hour' => 60*60,
			'min' => 60,
			'sec' => 1,
		);
		
		$secs = intval($string);
		$output = array();
		
		foreach($blocks as $label => $increment) {
			$n = floor($secs/$increment);
			$secs -= ($n * $increment);
			
			if(!empty($n))
				$output[] = sprintf("%d %s%s",
					$n,
					$label,
					($n==1) ? '' : 's'
				);
		}
		
		if(!empty($length))
			$output = array_slice($output, 0, $length);
		
		return implode(', ', $output);
	}
	
	/**
	 * 
	 * @param string $string
	 * @param boolean $is_delta
	 * return string
	 * @test DevblocksPlatformTest
	 */
	static function strPrettyTime($string, $is_delta=false) {
		if(empty($string) || !is_numeric($string))
			return '';
		
		if(!$is_delta) {
			$diffsecs = time() - intval($string);
		} else {
			$diffsecs = intval($string);
		}
		
		$whole = '';

		// Prefix
		if($is_delta) {
			if($diffsecs > 0)
				$whole .= '+';
			elseif($diffsecs < 0)
				$whole .= '-';
		}
		
		// The past
		if($diffsecs >= 0) {
			if($diffsecs >= 31557600) { // years
				$whole .= round($diffsecs/31557600).' year';
			} elseif($diffsecs >= 2592000) { // mo
				$whole .= round($diffsecs/2592000).' month';
			} elseif($diffsecs >= 86400) { // days
				$whole .= round($diffsecs/86400).' day';
			} elseif($diffsecs >= 3600) { // hours
				$whole .= floor($diffsecs/3600).' hour';
			} elseif($diffsecs >= 60) { // mins
				$whole .= floor($diffsecs/60).' min';
			} elseif($diffsecs >= 0) { // secs
				$whole .= $diffsecs.' sec';
			}
			
		} else { // The future
			if($diffsecs <= -31557600) { // years
				$whole .= round($diffsecs/-31557600).' year';
			} elseif($diffsecs <= -2592000) { // mo
				$whole .= round($diffsecs/-2592000).' month';
			} elseif($diffsecs <= -86400) { // days
				$whole .= round($diffsecs/-86400).' day';
			} elseif($diffsecs <= -3600) { // hours
				$whole .= floor($diffsecs/-3600).' hour';
			} elseif($diffsecs <= -60) { // mins
				$whole .= floor($diffsecs/-60).' min';
			} elseif($diffsecs <= 0) { // secs
				$whole .= round($diffsecs/-1).' sec';
			}
		}

		// Pluralize
		$whole .= (1 == abs(intval($whole))) ? '' : 's';

		if($diffsecs > 0 && !$is_delta)
			$whole .= ' ago';
		
		if($diffsecs == 0)
			$whole = 'just now';
		
		return $whole;
	}
	
	/**
	 * 
	 * @param string $string
	 * @param integer $precision
	 * @return string
	 * @test DevblocksPlatformTest
	 */
	static function strPrettyBytes($string, $precision='0') {
		if(!is_numeric($string))
			return '';
			
		$is_negative = (intval($string) < 0) ? true : false;
		$bytes = abs(intval($string));
		$precision = floatval($precision);
		$out = '';
		
		if($bytes >= 1000000000000) {
			$out = number_format($bytes/1000000000000,$precision) . ' TB';
		} elseif($bytes >= 1000000000) {
			$out = number_format($bytes/1000000000,$precision) . ' GB';
		} elseif ($bytes >= 1000000) {
			$out = number_format($bytes/1000000,$precision) . ' MB';
		} elseif ($bytes >= 1000) {
			$out = number_format($bytes/1000,$precision) . ' KB';
		} else {
			$out = $bytes . ' bytes';
		}
		
		return (($is_negative) ? '-' : '') . $out;
	}
	
	/**
	 * Takes a comma-separated value string and returns an array of tokens.
	 * [TODO] Move to a FormHelper service?
	 *
	 * @param string $string
	 * @param boolean $keep_blanks
	 * @param mixed $typecast
	 * @return array
	 * @test DevblocksPlatformTest
	 */
	static function parseCsvString($string, $keep_blanks=false, $typecast=null) {
		if(empty($string))
			return array();
		
		if(!$keep_blanks)
			$string = rtrim($string, ', ');
		
		$tokens = explode(',', $string);

		if(!is_array($tokens))
			return array();
		
		foreach($tokens as $k => $v) {
			if(!$keep_blanks && 0==strlen($tokens[$k])) {
				unset($tokens[$k]);
				continue;
			}
			
			if(!is_null($typecast)) {
				settype($v, $typecast);
			}
			
			$tokens[$k] = trim($v);
		}
		
		return $tokens;
	}
	
	/**
	 * 
	 * @param integer $number
	 * @param string $as
	 * @return string
	 * @test DevblocksPlatformTest
	 */
	static function formatNumberAs($number, $as) {
		$label = $number;
		
		switch($as) {
			case 'bytes':
				$label = DevblocksPlatform::strPrettyBytes(intval($number));
				break;
				
			case 'seconds':
				$label = DevblocksPlatform::strSecsToString(intval($number), 2);
				break;
				
			case 'minutes':
				$label = DevblocksPlatform::strSecsToString(intval($number) * 60);
				break;
				
			case 'number':
				$label = number_format($number, 0);
				break;
				
			case 'decimal':
				$label = number_format($number, 2);
				break;
			
			case 'percent':
				$label = number_format($number) . '%';
				break;
			
			// [TODO] Currency
				
			default:
				break;
		}
		
		return $label;
	}
	
	/**
	 * Indents a flat JSON string to make it more human-readable.
	 *
	 * @author http://www.daveperrett.com/articles/2008/03/11/format-json-with-php/
	 * @todo This won't be needed when we require PHP 5.4+ with JSON_PRETTY_PRINT
	 *
	 * @param string $json The original JSON string to process.
	 * @return string Indented version of the original JSON string.
	 * @test DevblocksPlatformTest
	 */
	static function strFormatJson($json) {
		$result = '';
		$pos = 0;
		$strLen  = strlen($json);
		$indentStr = '  ';
		$newLine = "\n";
		$prevChar = '';
		$outOfQuotes = true;

		for ($i=0; $i<=$strLen; $i++) {

			// Grab the next character in the string.
			$char = substr($json, $i, 1);

			// Are we inside a quoted string?
			if ($char == '"' && $prevChar != '\\') {
				$outOfQuotes = !$outOfQuotes;

				// If this character is the end of an element,
				// output a new line and indent the next line.
			} else if(($char == '}' || $char == ']') && $outOfQuotes) {
				$result .= $newLine;
				$pos --;
				for ($j=0; $j<$pos; $j++) {
					$result .= $indentStr;
				}
			}

			// Add the character to the result string.
			$result .= $char;

			// If the last character was the beginning of an element,
			// output a new line and indent the next line.
			if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
				$result .= $newLine;
				if ($char == '{' || $char == '[') {
					$pos ++;
				}

				for ($j = 0; $j < $pos; $j++) {
					$result .= $indentStr;
				}
			}

			$prevChar = $char;
		}

		return $result;
	}

	/**
	 * Returns a pointer to an arbitrary property in a deeply nested JSON tree.  The pointer
	 * can be used to get or set the value at that location.
	 *
	 * @param array|string $json
	 * @param string $path
	 * @return mixed Pointer to the value at $path, or FALSE on error
	 * @test DevblocksPlatformTest
	 */
	static function &jsonGetPointerFromPath(array &$array, $path) {
		if(empty($path))
			return false;
		
		$keys = explode('.', $path);
		$array_keys = array();

		$ptr = null;
		
		if(!is_array($keys) || empty($keys))
			return $ptr;
		
		foreach($keys as $idx => $k) {
			if(preg_match('/(.*)\[(\d+)\]/', $k, $matches)) {
				$array_keys[] = $matches[1];
				$array_keys[] = $matches[2];
			} else {
				$array_keys[] = $k;
			}
		}

		$ptr =& $array;

		while(null !== ($key = array_shift($array_keys))) {
			if(!isset($ptr[$key])) {
				$ptr = null;
				return $ptr;
			}
			
			$ptr =& $ptr[$key];
		}

		return $ptr;
	}

	/**
	 * Clears any platform-level plugin caches.
	 *
	 */
	static function clearCache($one_cache=null) {
		$cache = self::getCacheService(); /* @var $cache _DevblocksCacheManager */

		if(!empty($one_cache)) {
			$cache->remove($one_cache);
			
		} else { // All
			$cache->remove(self::CACHE_ACL);
			$cache->remove(self::CACHE_CONTEXT_ALIASES);
			$cache->remove(self::CACHE_PLUGINS);
			$cache->remove(self::CACHE_ACTIVITY_POINTS);
			$cache->remove(self::CACHE_EVENT_POINTS);
			$cache->remove(self::CACHE_EVENTS);
			$cache->remove(self::CACHE_EXTENSIONS);
			$cache->remove(self::CACHE_POINTS);
			$cache->remove(self::CACHE_TABLES);
			$cache->remove('devblocks:plugin:devblocks.core:settings');
			$cache->remove(_DevblocksClassLoadManager::CACHE_CLASS_MAP);
			
			// Flush template cache
			$tpl = DevblocksPlatform::getTemplateService();
			$tpl->clearCompiledTemplate();
			
			// Clear all locale caches
			$langs = DAO_Translation::getDefinedLangCodes();
			if(is_array($langs) && !empty($langs))
			foreach($langs as $lang_code => $lang_name) {
				$cache->remove(self::CACHE_TAG_TRANSLATIONS . '_' . $lang_code);
			}
		}

		// Cache-specific 'after' actions
		switch($one_cache) {
			case self::CACHE_PLUGINS:
			case self::CACHE_EXTENSIONS:
			case NULL:
				self::getPluginRegistry();
				self::getExtensionRegistry();
				break;
		}
	}

	public static function registerClasses($file,$classes=array()) {
		$classloader = self::getClassLoaderService();
		return $classloader->registerClasses($file,$classes);
	}
	
	public static function getStartTime() {
		return self::$start_time;
	}
	
	public static function getStartMemory() {
		return self::$start_memory;
	}
	
	public static function getStartPeakMemory() {
		return self::$start_peak_memory;
	}
	
	/**
	 * @return resource $fp
	 * @test DevblocksPlatformTest
	 */
	public static function getTempFile() {
		// Generate a new temporary file
		$file_name = tempnam(APP_TEMP_PATH, 'tmp');
		
		// Open the file pointer
		$fp = fopen($file_name, "w+b");
		
		// Manually keep track of these temporary files
		self::$_tmp_files[intval($fp)] = $file_name;
		return $fp;
	}
	
	/**
	 * @return string $filename
	 * @test DevblocksPlatformTest
	 */
	public static function getTempFileInfo($fp) {
		// If we're asking about a specific temporary file
		if(!empty($fp)) {
			if(@isset(self::$_tmp_files[intval($fp)]))
				return self::$_tmp_files[intval($fp)];
			return false;
		}
	}

	/**
	 * Checks whether the active database has any tables.
	 *
	 * @return boolean
	 */
	static function isDatabaseEmpty() {
		if(false == ($db = DevblocksPlatform::getDatabaseService()))
			return true;
		
		$tables = self::getDatabaseTables();
		return empty($tables);
	}
	
	static function getDatabaseTables($nocache=false) {
		$cache = self::getCacheService();
		$tables = array();
		
		if($nocache || null === ($tables = $cache->load(self::CACHE_TABLES))) {
			// Make sure the database connection is valid or error out.
			if(false == ($db = self::getDatabaseService()))
				return array();
			
			$tables = $db->metaTables();
			
			if(!$nocache)
				$cache->save($tables, self::CACHE_TABLES);
		}
		
		return $tables;
	}

	/**
	 * Checks to see if the application needs to patch
	 *
	 * @return boolean
	 */
	static function versionConsistencyCheck() {
		$cache = DevblocksPlatform::getCacheService(); /* @var _DevblocksCacheManager $cache */
		
		if(false !== ($build_version = @file_get_contents(APP_STORAGE_PATH . '/_version'))
			&& $build_version == APP_BUILD)
				return true;

		// If build changed, clear cache regardless of patch status
		$cache = DevblocksPlatform::getCacheService(); /* @var $cache _DevblocksCacheManager */
		$cache->clean();
		
		return false;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return boolean
	 */
	static private function _needsToPatch() {
		 $plugins = DevblocksPlatform::getPluginRegistry();
		 
		 // First install or upgrade
		 if(empty($plugins))
		 	return true;

		 foreach($plugins as $plugin) { /* @var $plugin DevblocksPluginManifest */
		 	if($plugin->enabled) {
		 		foreach($plugin->getPatches() as $patch) { /* @var $patch DevblocksPatch */
		 			if(!$patch->hasRun())
		 				return true;
		 		}
		 	}
		 }
		 
		 return false;
	}
	
	/**
	 * Returns the list of extensions on a given extension point.
	 *
	 * @static
	 * @param string $point
	 * @return DevblocksExtensionManifest[]
	 */
	static function getExtensions($point,$as_instances=false, $ignore_acl=false) {
		$results = array();
		$extensions = DevblocksPlatform::getExtensionRegistry($ignore_acl);

		if(is_array($extensions))
		foreach($extensions as $extension) { /* @var $extension DevblocksExtensionManifest */
			if($extension->point == $point) {
				$results[$extension->id] = ($as_instances) ? $extension->createInstance() : $extension;
			}
		}
		return $results;
	}

	/**
	 * Returns the manifest of a given extension ID.
	 *
	 * @static
	 * @param string $extension_id
	 * @param boolean $as_instance
	 * @return DevblocksExtensionManifest
	 */
	static function getExtension($extension_id, $as_instance=false, $ignore_acl=false) {
		$result = null;
		$extensions = DevblocksPlatform::getExtensionRegistry($ignore_acl);

		if(is_array($extensions))
		foreach($extensions as $extension) { /* @var $extension DevblocksExtensionManifest */
			if($extension->id == $extension_id) {
				$result = $extension;
				break;
			}
		}

		if($as_instance && !is_null($result)) {
			return $result->createInstance();
		}
		
		return $result;
	}

	/**
	 * Returns an array of all contributed extension manifests.
	 *
	 * @static
	 * @return DevblocksExtensionManifest[]
	 */
	static function getExtensionRegistry($ignore_acl=false, $nocache=false, $with_disabled=false) {
		$cache = self::getCacheService();
		static $acl_extensions = null;
		
		// Forced
		if($with_disabled)
			$nocache = true;
		
		// Retrieve and cache
		if($nocache || null === ($extensions = $cache->load(self::CACHE_EXTENSIONS))) {
			$db = DevblocksPlatform::getDatabaseService();
			if(is_null($db))
				return;
			
			$extensions = array();
	
			$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup
	
			$sql = sprintf("SELECT e.id , e.plugin_id, e.point, e.pos, e.name , e.file , e.class, e.params ".
				"FROM %sextension e ".
				"INNER JOIN %splugin p ON (e.plugin_id=p.id) ".
				"WHERE 1 ".
				"%s ".
				"ORDER BY e.plugin_id ASC, e.pos ASC",
					$prefix,
					$prefix,
					($with_disabled ? '' : 'AND p.enabled = 1')
				);
			$results = $db->GetArrayMaster($sql);
				
			foreach($results as $row) {
				$extension = new DevblocksExtensionManifest();
				$extension->id = $row['id'];
				$extension->plugin_id = $row['plugin_id'];
				$extension->point = $row['point'];
				$extension->name = $row['name'];
				$extension->file = $row['file'];
				$extension->class = $row['class'];
				$extension->params = @unserialize($row['params']);
		
				if(empty($extension->params))
					$extension->params = array();
				$extensions[$extension->id] = $extension;
			}

			if(!$nocache)
				$cache->save($extensions, self::CACHE_EXTENSIONS);
			
			$acl_extensions = null;
		}
		
		if(!$ignore_acl) {
			// If we don't have a cache in this request
			if(null == $acl_extensions) {
				// Check with an extension delegate if we have one
				if(class_exists(self::$extensionDelegate) && method_exists('DevblocksExtensionDelegate','shouldLoadExtension')) {
					if(is_array($extensions))
					foreach($extensions as $id => $extension) {
						// Ask the delegate if we should load the extension
						if(!call_user_func(array(self::$extensionDelegate,'shouldLoadExtension'), $extension))
							unset($extensions[$id]);
					}
				}
				// Cache for duration of request
				$acl_extensions = $extensions;
			} else {
				$extensions = $acl_extensions;
			}
		}
		
		return $extensions;
	}

	static function getActivityPointRegistry() {
		$cache = self::getCacheService();
		$plugins = DevblocksPlatform::getPluginRegistry();
		
		if(empty($plugins))
			return array();
			
		if(null !== ($activities = $cache->load(self::CACHE_ACTIVITY_POINTS)))
			return $activities;
			
		$activities = array();
			
		foreach($plugins as $plugin) { /* @var $plugin DevblocksPluginManifest */
			if($plugin->enabled)
			foreach($plugin->getActivityPoints() as $point => $data) {
				$activities[$point] = $data;
			}
		}
		
		ksort($activities);
		
		$cache->save($activities, self::CACHE_ACTIVITY_POINTS);
		return $activities;
	}
	
	/**
	 * @return DevblocksEventPoint[]
	 */
	static function getEventPointRegistry() {
		$cache = self::getCacheService();
		if(null !== ($events = $cache->load(self::CACHE_EVENT_POINTS)))
			return $events;

		$events = array();
		$plugins = self::getPluginRegistry();
		 
		// [JAS]: Event point hashing/caching
		if(is_array($plugins))
		foreach($plugins as $plugin) { /* @var $plugin DevblocksPluginManifest */
			$events = array_merge($events,$plugin->event_points);
		}
		
		$cache->save($events, self::CACHE_EVENT_POINTS);
		return $events;
	}
	
	/**
	 * @return DevblocksAclPrivilege[]
	 */
	static function getAclRegistry() {
		$cache = self::getCacheService();
		if(null !== ($acl = $cache->load(self::CACHE_ACL)))
			return $acl;

		$acl = array();

		$db = DevblocksPlatform::getDatabaseService();
		if(is_null($db)) return;

		//$plugins = self::getPluginRegistry();
		$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup

		$sql = sprintf("SELECT a.id, a.plugin_id, a.label ".
			"FROM %sacl a ".
			"INNER JOIN %splugin p ON (a.plugin_id=p.id) ".
			"WHERE p.enabled = 1 ".
			"ORDER BY a.plugin_id, a.id ASC",
			$prefix,
			$prefix
		);
		$results = $db->GetArrayMaster($sql);
		
		foreach($results as $row) {
			$priv = new DevblocksAclPrivilege();
			$priv->id = $row['id'];
			$priv->plugin_id = $row['plugin_id'];
			$priv->label = $row['label'];
			
			$acl[$priv->id] = $priv;
		}
		
		$cache->save($acl, self::CACHE_ACL);
		return $acl;
	}
	
	static function getEventRegistry() {
		$cache = self::getCacheService();
		if(null !== ($events = $cache->load(self::CACHE_EVENTS)))
			return $events;
		
		$extensions = self::getExtensions('devblocks.listener.event',false,true);
		$events = array('*');
		 
		// [JAS]: Event point hashing/caching
		if(is_array($extensions))
		foreach($extensions as $extension) { /* @var $extension DevblocksExtensionManifest */
			@$evts = $extension->params['events'][0];
			
			// Global listeners (every point)
			if(empty($evts) && !is_array($evts)) {
				$events['*'][] = $extension->id;
				continue;
			}
			
			if(is_array($evts))
			foreach(array_keys($evts) as $evt) {
				$events[$evt][] = $extension->id;
			}
		}
		
		$cache->save($events, self::CACHE_EVENTS);
		return $events;
	}
	
	/**
	 * Returns an array of all contributed plugin manifests.
	 *
	 * @static
	 * @return DevblocksPluginManifest[]
	 */
	static function getPluginRegistry() {
		$cache = self::getCacheService();
		
		if(null !== ($plugins = $cache->load(self::CACHE_PLUGINS)))
			return $plugins;
		
		if(false == ($db = DevblocksPlatform::getDatabaseService()) || DevblocksPlatform::isDatabaseEmpty())
			return;
			
		$plugins = array();
			
		$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup

		$sql = sprintf("SELECT p.* ".
			"FROM %splugin p ".
			"ORDER BY p.enabled DESC, p.name ASC ",
			$prefix
		);
		$results = $db->GetArrayMaster($sql);

		foreach($results as $row) {
			$plugin = new DevblocksPluginManifest();
			@$plugin->id = $row['id'];
			@$plugin->enabled = intval($row['enabled']);
			@$plugin->name = $row['name'];
			@$plugin->description = $row['description'];
			@$plugin->author = $row['author'];
			@$plugin->version = intval($row['version']);
			@$plugin->link = $row['link'];
			@$plugin->dir = $row['dir'];

			// JSON decode
			if(isset($row['manifest_cache_json'])
				&& null != ($manifest_cache_json = $row['manifest_cache_json'])) {
				$plugin->manifest_cache = json_decode($manifest_cache_json, true);
			}

			if(file_exists($plugin->getStoragePath() . '/' . 'plugin.xml'))
				$plugins[$plugin->id] = $plugin;
		}

		$sql = sprintf("SELECT p.id, p.name, p.params, p.plugin_id ".
			"FROM %sevent_point p ",
			$prefix
		);
		$results = $db->GetArrayMaster($sql);

		foreach($results as $row) {
			$point = new DevblocksEventPoint();
			$point->id = $row['id'];
			$point->name = $row['name'];
			$point->plugin_id = $row['plugin_id'];
			
			$params = $row['params'];
			$point->params = !empty($params) ? unserialize($params) : array();

			if(isset($plugins[$point->plugin_id])) {
				$plugins[$point->plugin_id]->event_points[$point->id] = $point;
			}
		}
		
		self::_sortPluginsByDependency($plugins);
		
		$cache->save($plugins, self::CACHE_PLUGINS);
		return $plugins;
	}
	
	static public function isPluginEnabled($plugin_id) {
		if(null != ($plugin = self::getPlugin($plugin_id))) {
			return $plugin->enabled;
		};
		return false;
	}
	
	static private function _sortPluginsByDependency(&$plugins) {
		$dependencies = array();
		$seen = array();
		$order = array();
		
		// Dependencies
		foreach($plugins as $plugin) {
			@$deps = $plugin->manifest_cache['dependencies'];
			$dependencies[$plugin->id] = is_array($deps) ? $deps : array();
		}
		
		if(is_array($plugins))
		foreach($plugins as $plugin)
			self::_recursiveDependency($plugin->id, $dependencies, $seen, $order);

		$original = $plugins;
		$plugins = array();
			
		if(is_array($order))
		foreach($order as $order_id) {
			if(!isset($original[$order_id]))
				continue;
			
			$plugins[$order_id] = $original[$order_id];
		}
	}

	static private function _recursiveDependency($id, $deps, &$seen, &$order, $level=0) {
		if(isset($seen[$id]))
			return true;
	
		if(isset($deps[$id]) && !empty($deps[$id])) {
			foreach($deps[$id] as $dep) {
				if(!self::_recursiveDependency($dep, $deps, $seen, $order, ++$level))
					return false;
			}
		}
		
		if(!isset($seen[$id])) {
			$order[] = $id;
			$seen[$id] = true;
		}
		
		return true;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $id
	 * @return DevblocksPluginManifest
	 */
	static function getPlugin($id) {
		$plugins = DevblocksPlatform::getPluginRegistry();

		if(isset($plugins[$id]))
			return $plugins[$id];
		
		return null;
	}

	/**
	 * Reads and caches manifests from the features + plugins directories.
	 *
	 * @static
	 * @return DevblocksPluginManifest[]
	 */
	static function readPlugins($is_update=true, $scan_dirs = array('features', 'plugins')) {
		$plugins = array();

		// Devblocks
		if(null !== ($manifest = self::_readPluginManifest(DEVBLOCKS_PATH, $is_update)))
			$plugin[] = $manifest;
			
		// Application
		if(is_array($scan_dirs))
		foreach($scan_dirs as $scan_dir) {
			switch($scan_dir) {
				case 'features':
					$scan_path = APP_PATH . '/features';
					break;
					
				case 'plugins':
					$scan_path = APP_STORAGE_PATH . '/plugins';
					break;
					
				default:
					continue;
			}
			
			if (is_dir($scan_path)) {
				if ($dh = opendir($scan_path)) {
					while (($file = readdir($dh)) !== false) {
						if($file=="." || $file == "..")
							continue;
							
						$plugin_path = $scan_path . '/' . $file;
						
						if(is_dir($plugin_path) && file_exists($plugin_path . '/plugin.xml')) {
							$manifest = self::_readPluginManifest($plugin_path, $is_update); /* @var $manifest DevblocksPluginManifest */
	
							if(null != $manifest) {
								$plugins[$manifest->id] = $manifest;
							}
						}
					}
					closedir($dh);
				}
			}
		}
		
		DevblocksPlatform::clearCache(DevblocksPlatform::CACHE_PLUGINS);
		
		return $plugins;
	}

	/**
	 * @return _DevblocksPluginSettingsManager
	 */
	static function getPluginSettingsService() {
		return _DevblocksPluginSettingsManager::getInstance();
	}
	
	static function getPluginSetting($plugin_id, $key, $default=null, $json_decode=false) {
		$settings = self::getPluginSettingsService();
		return $settings->get($plugin_id, $key, $default, $json_decode);
	}
	
	static function setPluginSetting($plugin_id, $key, $value, $json_encode=false) {
		$settings = self::getPluginSettingsService();
		return $settings->set($plugin_id, $key, $value, $json_encode);
	}

	/**
	 * @return _DevblocksLogManager
	 */
	static function getConsoleLog($prefix='') {
		return _DevblocksLogManager::getConsoleLog($prefix);
	}
	
	/**
	 * @return _DevblocksCacheManager
	 */
	static function getCacheService() {
		return _DevblocksCacheManager::getInstance();
	}
	
	/**
	 * Enter description here...
	 *
	 * @return _DevblocksDatabaseManager
	 */
	static function getDatabaseService() {
		return _DevblocksDatabaseManager::getInstance();
	}

	/**
	 * @return _DevblocksNaturalLanguageManager
	 */
	static function getNaturalLanguageService() {
		return _DevblocksNaturalLanguageManager::getInstance();
	}
	
	/**
	 * @return DevblocksNeuralNetwork
	 */
	static function getNeuralNetwork($inputs, $hiddens, $outputs, $learning_rate) {
		return _DevblocksNeuralNetworkService::createNeuralNetwork($inputs, $hiddens, $outputs, $learning_rate);
	}
	
	/**
	 * @return _DevblocksUrlManager
	 */
	static function getUrlService() {
		return _DevblocksUrlManager::getInstance();
	}

	/**
	 * @return _DevblocksEmailManager
	 */
	static function getMailService() {
		return _DevblocksEmailManager::getInstance();
	}

	/**
	 * @return _DevblocksEventManager
	 */
	static function getEventService() {
		return _DevblocksEventManager::getInstance();
	}
	
	/**
	 * @return _DevblocksRegistryManager
	 */
	static function getRegistryService() {
		return _DevblocksRegistryManager::getInstance();
	}
	
	/**
	 * @return DevblocksProxy
	 */
	static function getProxyService() {
		return DevblocksProxy::getProxy();
	}
	
	/**
	 * @return _DevblocksClassLoadManager
	 */
	static function getClassLoaderService() {
		return _DevblocksClassLoadManager::getInstance();
	}
	
	/**
	 * @return _DevblocksSessionManager
	 */
	static function getSessionService() {
		return _DevblocksSessionManager::getInstance();
	}
	
	/**
	 * @return _DevblocksOpenIDManager
	 */
	static function getOpenIDService() {
		return _DevblocksOpenIDManager::getInstance();
	}
	
	static private function _deepCloneArray(&$array) {
		if(is_array($array))
		foreach($array as &$element) {
			// Recurse if needed
			if(is_array($element)) {
				self::_deepCloneArray($element);
				
			} else if(is_object($element)) {
				$element = clone $element;
			}
		}
	}
	
	static function deepCloneArray($array) {
		$copy = $array;
		self::_deepCloneArray($copy);
		return $copy;
	}
	
	static function extractArrayValues($array, $key, $only_unique=true) {
		if(!is_array($array) || empty($key))
			return array();
		
		$results = array();
		
		array_walk_recursive($array, function($v, $k) use ($key, &$results) {
			if(0 == strcasecmp($key, $k))
				$results[] = $v;
		});
		
		if($only_unique)
			$results = array_unique($results);
		
		return $results;
	}
	
	/**
	 * 
	 * @param array $array
	 * @param string $type
	 * @param array $options
	 * @return mixed
	 * @test DevblocksPlatformTest
	 */
	static function sanitizeArray($array, $type, $options=array()) {
		if(!is_array($array))
			return array();
		
		switch($type) {
			case 'bit':
				$array = _DevblocksSanitizationManager::arrayAs($array, 'bit');
				return $array;
				break;
				
			case 'bool':
			case 'boolean':
				$array = _DevblocksSanitizationManager::arrayAs($array, 'boolean');
				return $array;
				break;
				
			case 'int':
			case 'integer':
				$array = _DevblocksSanitizationManager::arrayAs($array, 'integer');
				
				if(is_array($options) && in_array('nonzero', $options)) {
					foreach($array as $k => $v) {
						if(empty($v))
							unset($array[$k]);
					}
				}
				
				if(in_array('unique', $options)) {
					$array = array_unique($array);
				}
				
				return $array;
				break;
				
			default:
				break;
		}
		
		return false;
	}
	
	/**
	 * 
	 * @param array $array
	 * @param string $on
	 * @param boolean $ascending
	 * @test DevblocksPlatformTest
	 */
	static function sortObjects(&$array, $on, $ascending=true) {
		_DevblocksSortHelper::sortObjects($array, $on, $ascending);
	}
	
	/**
	 * @param $profile_id | $extension_id, $options
	 * @return Extension_DevblocksStorageEngine
	 */
	static function getStorageService() {
		$args = func_get_args();

		if(empty($args))
			return false;
		
		$profile = $args[0];
		$params = array();
		
		// Handle $profile polymorphism
		if($profile instanceof Model_DevblocksStorageProfile) {
			$extension = $profile->extension_id;
			$params['_profile_id'] = $profile->id;
			$params = array_merge($params, $profile->params);
			
		} else if(is_numeric($profile)) {
			$storage_profile = DAO_DevblocksStorageProfile::get($profile);
			$extension = $storage_profile->extension_id;
			$params['_profile_id'] = $storage_profile->id;
			$params = array_merge($params, $storage_profile->params);
			
		} else if(is_string($profile)) {
			$extension = $profile;
			$params['_profile_id'] = 0;
			
			if(isset($args[1]) && is_array($args[1]))
				$params = array_merge($params, $args[1]);
		}
		
		return _DevblocksStorageManager::getEngine($extension, $params);
	}

	/**
	 * @return Smarty
	 */
	static function getTemplateService() {
		return _DevblocksTemplateManager::getInstance();
	}

	/**
	 *
	 * @param string $set
	 * @return DevblocksTemplate[]
	 */
	static function getTemplates($set=null) {
		$templates = array();
		$plugins = self::getPluginRegistry();
		
		if(is_array($plugins))
		foreach($plugins as $plugin) {
			if(isset($plugin->manifest_cache['templates']) && is_array($plugin->manifest_cache['templates']))
			foreach($plugin->manifest_cache['templates'] as $tpl) {
				if(null === $set || 0 == strcasecmp($set, $tpl['set'])) {
					$template = new DevblocksTemplate();
					$template->plugin_id = $tpl['plugin_id'];
					$template->set = $tpl['set'];
					$template->path = $tpl['path'];
					$template->sort_key = $tpl['plugin_id'] . ' ' . $tpl['path'];
					$templates[] = $template;
				}
			}
		}
		
		return $templates;
	}
	
	/**
	 * @return _DevblocksTemplateBuilder
	 */
	static function getTemplateBuilder() {
		return _DevblocksTemplateBuilder::getInstance();
	}

	/**
	 * @return _DevblocksDateManager
	 */
	static function getDateService($datestamp=null) {
		return _DevblocksDateManager::getInstance();
	}

	/**
	 * 
	 * @param string $locale
	 * @test DevblocksPlatformTest
	 */
	static function setLocale($locale) {
		@setlocale(LC_ALL, $locale);
		self::$locale = $locale;
	}
	
	/**
	 * @test DevblocksPlatformTest
	 */
	static function getLocale() {
		if(!empty(self::$locale))
			return self::$locale;
			
		return 'en_US';
	}
	
	/**
	 * @return string
	 * @test DevblocksPlatformTest
	 */
	static function getDateTimeFormat() {
		return self::$dateTimeFormat;
	}
	
	/**
	 * 
	 * @param string $time_format
	 * @test DevblocksPlatformTest
	 */
	static function setDateTimeFormat($time_format) {
		self::$dateTimeFormat = $time_format;
	}
	
	/**
	 * @return _DevblocksTranslationManager
	 */
	static function getTranslationService() {
		static $languages = array();
		$locale = DevblocksPlatform::getLocale();

		// Registry
		if(isset($languages[$locale])) {
			return $languages[$locale];
		}
						
		$cache = self::getCacheService();
		
		if(null === ($map = $cache->load(self::CACHE_TAG_TRANSLATIONS.'_'.$locale))) { /* @var $cache _DevblocksCacheManager */
			$map = array();
			$map_en = DAO_Translation::getMapByLang('en_US');
			if(0 != strcasecmp('en_US', $locale))
				$map_loc = DAO_Translation::getMapByLang($locale);
			
			// Loop through the English string objects
			if(is_array($map_en))
			foreach($map_en as $string_id => $obj_string_en) {
				$string = '';
				
				// If we have a locale to check
				if(isset($map_loc) && is_array($map_loc)) {
					@$obj_string_loc = $map_loc[$string_id];
					@$string =
						(!empty($obj_string_loc->string_override))
						? $obj_string_loc->string_override
						: $obj_string_loc->string_default;
				}
				
				// If we didn't hit, load the English default
				if(empty($string))
				@$string =
					(!empty($obj_string_en->string_override))
					? $obj_string_en->string_override
					: $obj_string_en->string_default;
					
				// If we found any match
				if(!empty($string))
					$map[$string_id] = $string;
			}
			unset($obj_string_en);
			unset($obj_string_loc);
			unset($map_en);
			unset($map_loc);
			
			// Cache with tag (tag allows easy clean for multiple langs at once)
			$cache->save($map,self::CACHE_TAG_TRANSLATIONS.'_'.$locale);
		}
		
		$translate = _DevblocksTranslationManager::getInstance();
		$translate->addLocale($locale, $map);
		$translate->setLocale($locale);
		
		$languages[$locale] = $translate;

		return $translate;
	}

	/**
	 * Enter description here...
	 *
	 * @return DevblocksHttpRequest
	 */
	static function getHttpRequest() {
		return self::$request;
	}

	/**
	 * @param DevblocksHttpRequest $request
	 */
	static function setHttpRequest(DevblocksHttpRequest $request) {
		self::$request = $request;
	}

	/**
	 * Enter description here...
	 *
	 * @return DevblocksHttpRequest
	 */
	static function getHttpResponse() {
		return self::$response;
	}

	/**
	 * @param DevblocksHttpResponse $response
	 */
	static function setHttpResponse(DevblocksHttpResponse $response) {
		self::$response = $response;
	}

	/**
	 * Initializes the plugin platform (paths, etc).
	 *
	 * @static
	 * @return void
	 */
	static function init() {
		self::$start_time = microtime(true);
		if(function_exists('memory_get_usage') && function_exists('memory_get_peak_usage')) {
			self::$start_memory = memory_get_usage();
			self::$start_peak_memory = memory_get_peak_usage();
		}
		
		// Encoding (mbstring)
		mb_internal_encoding(LANG_CHARSET_CODE);
		if(function_exists('mb_regex_encoding'))
			mb_regex_encoding(LANG_CHARSET_CODE);
		
		// [JAS] [MDF]: Automatically determine the relative webpath to Devblocks files
		@$proxyhost = $_SERVER['HTTP_DEVBLOCKSPROXYHOST'];
		@$proxybase = $_SERVER['HTTP_DEVBLOCKSPROXYBASE'];
	
		// App path (always backend)
	
		$app_self = $_SERVER["SCRIPT_NAME"];
		
		if(DEVBLOCKS_REWRITE) {
			$pos = strrpos($app_self,'/');
			$app_self = substr($app_self,0,$pos) . '/';
		} else {
			$pos = strrpos($app_self,'index.php');
			if(false === $pos) $pos = strrpos($app_self,'ajax.php');
			$app_self = substr($app_self,0,$pos);
		}
		
		// Context path (abstracted: proxies or backend)
		
		if(!empty($proxybase)) { // proxy
			$context_self = $proxybase . '/';
		} else { // non-proxy
			$context_self = $app_self;
		}
		
		@define('DEVBLOCKS_WEBPATH',$context_self);
		@define('DEVBLOCKS_APP_WEBPATH',$app_self);
		
		// Enable the second-level cache
		
		$cache = DevblocksPlatform::getCacheService();
		
		if(null !== ($cacher_extension_id = DevblocksPlatform::getPluginSetting('devblocks.core', 'cacher.extension_id', null))) {
			$cacher_params = DevblocksPlatform::getPluginSetting('devblocks.core', 'cacher.params_json', array(), true);
			$cache->setEngine($cacher_extension_id, $cacher_params);
		}
		
		// Register shutdown function
		register_shutdown_function(array('DevblocksPlatform','shutdown'));
	}
	
	static function shutdown() {
		// Trigger changed context events
		Extension_DevblocksContext::shutdownTriggerChangedContextsEvents();
		
		if(class_exists('CerberusContexts'))
			CerberusContexts::shutdown();
		
		// Clean up any temporary files
		while(null != ($tmpfile = array_pop(self::$_tmp_files))) {
			@unlink($tmpfile);
		}
		
		// Persist the registry
		$registry = DevblocksPlatform::getRegistryService();
		$registry->save();
	}

	static function setExtensionDelegate($class) {
		if(!empty($class) && class_exists($class, true))
			self::$extensionDelegate = $class;
	}
	
	static function setHandlerSession($class) {
		if(!empty($class) && class_exists($class, true))
			self::$handlerSession = $class;
	}
	
	static function getHandlerSession() {
		return self::$handlerSession;
	}
	
	static function redirect(DevblocksHttpIO $httpIO) {
		$url_service = self::getUrlService();
		session_write_close();
		$url = $url_service->writeDevblocksHttpIO($httpIO, true);
		header('Location: '.$url);
		exit;
	}
	
	static function redirectURL($url) {
		if(empty($url)) {
			$url_service = self::getUrlService();
			$url = $url_service->writeNoProxy('', true);
		}
		session_write_close();
		header('Location: '.$url);
		exit;
	}
	
	static function markContextChanged($context, $context_ids) {
		if(empty($context_ids))
			return;
		
		if(!is_array($context_ids))
			$context_ids = array($context_ids);
		
		return Extension_DevblocksContext::markContextChanged($context, $context_ids);
	}
};

class DevblocksException extends Exception {
};

function devblocks_autoload($className) {
	$classloader = _DevblocksClassLoadManager::getInstance();
	return $classloader->loadClass($className);
}

// Register Devblocks class loader
spl_autoload_register('devblocks_autoload');

/*
 * Twig Extensions
 * This must come after devblocks_autoload
 */
if(class_exists('Twig_Autoloader', true) && method_exists('Twig_Autoloader','register')) {
	Twig_Autoloader::register();
}
