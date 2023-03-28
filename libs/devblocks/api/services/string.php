<?php

use Ramsey\Uuid\Provider\Node\RandomNodeProvider;
use Ramsey\Uuid\Uuid;

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
	
	public function splitQuotedPhrases(string $text) : array {
		$terms = [];
		
		// While we have text left to process
		while($text) {
			$text = trim($text);
			$start_pos = strpos($text, '"');
			
			// If we don't have any quoted phrases, return space-delimited terms
			if(false === $start_pos) {
				$terms = array_merge($terms, explode(' ', $text));
				$text = '';
				
			} else {
				// If our quote is not at the start, space-delimit up to that point
				if($start_pos > 0)
					$terms = array_merge($terms, explode(' ', trim(substr($text, 0, $start_pos))));
				
				// Look for an ending quote
				$end_pos = strpos($text, '"', $start_pos+1);
				
				// If non-terminated quote, treat as terms
				if(false === $end_pos) {
					$terms = array_merge($terms, explode(' ', substr($text, $start_pos+1)));
					$text = '';
					
				// Otherwise return the entire quoted phrase with quotes
				} else {
					$terms[] = substr($text, $start_pos, 1+$end_pos-$start_pos);
					$text = substr($text, $end_pos+1);
				}
			}
		}
		
		return $terms;
	}
	
	/*
	 * Credit: https://stackoverflow.com/a/16496730
	 */
	function has4ByteChars($string) {
		if(empty($string))
			return false;
		
		return max(array_map('ord', str_split(strval($string)))) >= 240;
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
		if(false === ($docs = yaml_parse($yaml_string, $pos))) {
			if(null != ($last_error = DevblocksPlatform::getLastError())) {
				$error = $last_error['message'] ?? '';
			} else {
				$error = 'Invalid YAML syntax.';
			}
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
			$yaml_out = yaml_emit($object, YAML_ANY_ENCODING, YAML_LN_BREAK);
			
			if(DevblocksPlatform::strStartsWith($yaml_out, "---\n"))
				$yaml_out = substr($yaml_out, 4);
			
			if(DevblocksPlatform::strEndsWith($yaml_out, "\n...\n"))
				$yaml_out = substr($yaml_out, 0, -5);
			
			return $yaml_out;
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
					if(false === ($url_parts = parse_url($href)))
						break;
					
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
				
				if(!($url_parts = parse_url($src)) || !is_array($url_parts))
					break;
				
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
	
	function detectEncoding($text, $charset=null) {
		$charset = DevblocksPlatform::strLower($charset);
		
		// Otherwise, fall back to mbstring's auto-detection
		mb_detect_order('iso-2022-jp-ms, iso-2022-jp, utf-8, iso-8859-1, windows-1252');
		
		// Normalize charsets
		switch($charset) {
			case 'us-ascii':
				$charset = 'ascii';
				break;
			
			case 'win-1252':
				$charset = 'windows-1252';
				break;
			
			case 'ks_c_5601-1987':
			case 'ks_c_5601-1992':
			case 'ks_c_5601-1998':
			case 'ks_c_5601-2002':
				$charset = 'cp949';
				break;
			
			case NULL:
				$charset = mb_detect_encoding($text);
				break;
		}
		
		return $charset;
	}
	
	function htmlToText($str, $truncate=50000) {
		$dom = new DOMDocument('1.0', LANG_CHARSET_CODE);
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = false;
		$dom->strictErrorChecking = false;
		$dom->recover = true;
		$dom->validateOnParse = false;
		
		libxml_use_internal_errors(true);
		
		$str = mb_convert_encoding($str, 'HTML-ENTITIES');
		
		$replacements = [
			'&nbsp;' => '',
		];
		
		$str = str_replace(array_keys($replacements), array_values($replacements), $str);
		
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
		if(strlen($bytes) && !is_string(@yaml_emit($bytes)))
			return false;
		
		return true;
	}
	
	public function uuid() : string {
		$nodeProvider = new RandomNodeProvider();
		return Uuid::uuid1($nodeProvider->getNode());
	}
	
	public function truncate(?string $string, int $length, string $separator='...') : string {
		$string = strval($string);
		
		if(mb_strwidth($string) > $length)
			$string = mb_strimwidth($string, 0, $length, $separator);
		
		return $string;
	}
	
	public function arraySortLength(array $strings, $is_ascending=true) {
		usort($strings, fn($a, $b) => strlen($a) <=> strlen($b));
		return $is_ascending ? $strings : array_reverse($strings);
	}
}