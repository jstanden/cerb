<?php
class _DevblocksStringService {
	private static $_instance = null;
	
	static function getInstance() {
		if(is_null(self::$_instance))
			self::$_instance = new _DevblocksStringService();
		
		return self::$_instance;
	}
	
	private function __construct() {}
	
	function base64UrlEncode($string) {
		return strtr(base64_encode($string), ['+'=>'-', '/'=>'_', '='=>'']);
	}
	
	function base64UrlDecode($string) {
		return base64_decode(strtr($string, ['-'=>'+', '_'=>'/']));
	}
	
	function indentWith($string, $marker, $from_line=0) {
		if(0 == strlen($string))
			return '';
		
		$lines = DevblocksPlatform::parseCrlfString($string, true, false);
		
		$lines = array_map(
			function($idx) use ($marker, $lines, $from_line) {
				if($from_line && $idx < $from_line-1)
					return $lines[$idx];
				
				return $marker . $lines[$idx];
			},
			array_keys($lines)
		);
		
		return implode(PHP_EOL, $lines);
	}
	
	function strAfter($string, $marker) {
		if(false === ($pos = strpos($string, $marker)))
			return null;
		
		return substr($string, $pos+1);
	}
	
	function strBefore($string, $marker) {
		if(false === ($before = strstr($string, $marker, true)))
			return $string;
		
		return $before;
	}
	
	/*
	 * Credit: https://stackoverflow.com/a/16496730
	 */
	function has4ByteChars($string) {
		return max(array_map('ord', str_split($string))) >= 240;
	}
	
	/*
	 * Credit: https://stackoverflow.com/a/16496730
	 */
	function strip4ByteChars($string) {
		return preg_replace_callback('/./u', function(array $match) {
			return strlen($match[0]) >= 4 ? null : $match[0];
		}, $string);
	}
	
	function yamlParse($yaml_string, $pos=-1, &$error=null) {
		if(false === ($docs = @yaml_parse($yaml_string, $pos))) {
			$error = error_get_last()['message'];
			return false;
		}
		
		if(!is_array($docs) || array_key_exists(0, $docs) && !$docs[0])
			return [];
		
		return $docs;
	}
	
	function yamlEmit($object, $with_boundaries=true) {
		if($with_boundaries) {
			return yaml_emit($object);
			
		} else {
			$yaml_out = DevblocksPlatform::parseCrlfString(yaml_emit($object), false, false);
			return implode("\n", array_slice($yaml_out, 1, -1));
		}
	}
	
	private function _trimDomInlineWhitespace($str) {
		$str = preg_replace('#[ \t]*[\r\n][ \t]*#', "\n", $str);
		$str = strtr($str, ["\t" => ' ', "\n" => ' ']);
		$str = preg_replace('# +#', ' ', $str);
		return $str;
	}
	
	private function _recurseNodeToText(DOMNode $node, &$text, $max_length=50000) {
		if($max_length && strlen($text) > $max_length)
			return;
		
		if($node instanceof DOMText) {
			switch(DevblocksPlatform::strLower($node->parentNode->nodeName)) {
				case 'pre':
					$text .= $node->textContent;
					break;
					
				default:
					$str = $this->_trimDomInlineWhitespace($node->textContent);
					
					if(!$node->previousSibling)
						$str = ltrim($str);
					
					if(!$node->nextSibling)
						$str = rtrim($str);
					
					$text .= $str;
					break;
			}
		}
		
		if(!($node instanceof DOMElement))
			return;
		
		/* @var $node DOMElement */
		
		switch(DevblocksPlatform::strLower($node->tagName)) {
			case 'a':
				if(false == ($label = trim($node->textContent)))
					break;
				
				if(false == ($href = $node->getAttribute('href')))
					break;
				
				if($label == $href) {
					$text .= sprintf('%s', $href);
					
				} else {
					$url_parts = parse_url($href);
					
					if(array_key_exists('host', $url_parts) && array_key_exists('scheme', $url_parts)) {
						$text .= sprintf('%s <%s>', $label, $href);
					} else {
						$text .= $label;
					}
				}
				break;
				
			case 'br':
				$text .= "\n";
				break;
				
			case 'p':
			case 'div':
			case 'h1':
			case 'h2':
			case 'h3':
			case 'h4':
			case 'h5':
			case 'h6':
				if($text && substr($text,-1) != "\n")
					$text .= "\n";
				
				foreach($node->childNodes as $child)
					$this->_recurseNodeToText($child, $text, $max_length);
				
				$text .= "\n";
				break;
				
			case 'pre':
				if($text && substr($text,-1) != "\n")
					$text .= "\n";
				
				if(!trim($node->textContent))
					break;
				
				$text .= $node->textContent . "\n";
				break;
			
			case 'ol':
				if($text && substr($text,-1) != "\n")
					$text .= "\n";
				
				$xpath = new DOMXPath($node->ownerDocument);
				$items = $xpath->query("li", $node);
				$counter = 1;
				
				foreach($items as $item) {
					if(!trim($item->textContent))
						continue;
					
					$text .= sprintf("%d. ", $counter++);
					$this->_recurseNodeToText($item, $text, $max_length);
					$text .= "\n";
				}
				
				$text .= "\n";
				break;
				
			case 'ul':
				if($text && substr($text,-1) != "\n")
					$text .= "\n";
				
				$xpath = new DOMXPath($node->ownerDocument);
				$items = $xpath->query("li", $node);
				
				foreach($items as $item) {
					if(!trim($item->textContent))
						continue;
					
					$text .= "* ";
					$this->_recurseNodeToText($item, $text, $max_length);
					$text .= "\n";
				}
				
				$text .= "\n";
				break;
				
			// Blockquote
			case 'blockquote':
				$text .= DevblocksPlatform::services()->string()->indentWith($node->textContent, '> ');
				$text .= "\n";
				break;
			
			// Ignore
			case 'head':
			case 'script':
			case 'style':
				break;
				
			case 'img':
				$alt = $node->getAttribute('alt');
				$src = $node->getAttribute('src');
				
				$url_parts = parse_url($src);
				
				if(array_key_exists('host', $url_parts) && array_key_exists('scheme', $url_parts)) {
					$text .= sprintf("[Image %s]", $alt ?: $src);
				}
				break;
			
			// Recurse
			default:
				foreach($node->childNodes as $child)
					$this->_recurseNodeToText($child, $text, $max_length);
				break;
		}
	}
	
	function htmlToText($str, $truncate=50000) {
		$dom = new DOMDocument('1.0', LANG_CHARSET_CODE);
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = false;
		$dom->strictErrorChecking = false;
		$dom->recover = true;
		$dom->validateOnParse = false;
		
		libxml_use_internal_errors(true);
		
		$dom->loadHTML(sprintf('<?xml version="1.0" encoding="%s">', LANG_CHARSET_CODE) . $str);
		
		$dom->normalizeDocument();
		
		libxml_clear_errors();
		
		$text = '';
		
		$xpath = new DOMXPath($dom);
		
		$elements = $xpath->query('*');
		
		foreach($elements as $node) {
			$this->_recurseNodeToText($node, $text, $truncate);
		}
		
		return $text;
	}
	
	/**
	 * @param $string
	 * @return bool
	 */
	public function toBool($string) : bool {
		if(is_array($string))
			return !empty($string);
		
		$false_list = [
			false,
			'false',
			'0',
			'',
			'no',
			'n',
			'off',
			null
		];
		
		if(in_array(DevblocksPlatform::strLower(trim($string)), $false_list))
			return false;
		
		return true;
	}
	
	public function toDate(string $value) {
		$ts = @strtotime($value);
		
		if(false === $ts)
			return false;
		
		return intval($ts);
	}
	
	public function capitalizeDashed(string $k) : string {
		return implode(
			'-',
			array_map(
				function($str) { 
					return DevblocksPlatform::strUpperFirst($str); 
				},
				explode('-', $k)
			)
		);
	}
	
	public function isPrintable(string $bytes) : bool {
		if(strlen($bytes) && false === @yaml_emit($bytes))
			return false;
		
		return true;
	}
}