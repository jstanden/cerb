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
		return strtr(base64_encode($string), '+/', '-_');
	}
	
	function base64UrlDecode($string) {
		return base64_decode(strtr($string,'-_','+/'));
	}
}