<?php
class _DevblocksFileService {
	private static ?_DevblocksFileService $_instance = null;
	
	private array $_mime_types = [
		'css' => 'text/css',
		'gif' => 'image/gif',
		'jpg' => 'image/jpeg',
		'js' => 'text/javascript',
		'json' => 'application/json',
		'png' => 'image/png',
		'svg' => 'image/svg+xml',
	];
	
	static function getInstance() : _DevblocksFileService {
		if (is_null(self::$_instance))
			self::$_instance = new _DevblocksFileService();
		
		return self::$_instance;
	}
	
	private function __construct() {}
	
	function getExtByMimeType($mime_type) : string {
		if(false === ($ext = array_search(DevblocksPlatform::strLower($mime_type), $this->_mime_types)))
			return false;
		
		return $ext;
	}
	
	function getMimeTypebyExt($ext) : string {
		return $this->_mime_types[DevblocksPlatform::strLower($ext)] ?? 'application/octet-stream';
	}
}