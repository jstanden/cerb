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
}