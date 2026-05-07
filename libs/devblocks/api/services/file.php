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
	
	public function countLines($fp) : int {
		$start = ftell($fp);
		$lines = 0;
		$partial = false;
		
		while(!feof($fp)) {
			$chunk = fread($fp, 65_536);
			
			if($chunk === false || $chunk === '')
				continue;
			
			$lines += substr_count($chunk, "\n");
			$partial = $chunk[-1] !== "\n";
		}
		
		if($partial)
			$lines++;
		
		fseek($fp, $start);
		
		return $lines;
	}
	
	public function indexLines($fp): \Generator {
		if (!is_resource($fp) || get_resource_type($fp) != 'stream') {
			throw new InvalidArgumentException('Expected an open stream resource.');
		}
		
		$start = ftell($fp);
		$line_number = 0;
		$line_offset = 0;
		
		while(!feof($fp)) {
			if(false === ($line = fgets($fp))) continue;
			$length = strlen($line);
			
			yield [$line_number++, $line_offset, $length];
			$line_offset += $length;
		}
		
		fseek($fp, $start);
	}
	
	public function indexCsv($fp) : \Generator {
		if(!is_resource($fp) || get_resource_type($fp) != 'stream') {
			throw new InvalidArgumentException('Expected an open stream resource.');
		}
		
		$start = ftell($fp);
		
		$line_number = 0;
		$line_offset = $start;
		
		while(!feof($fp)) {
			fgetcsv($fp, 64_000, ',', '"');
			$current = ftell($fp);
			$buffer_length = $current - $line_offset;
		
			// If we have a blank final line, ignore it
			if(!$buffer_length) continue;
			
			yield [$line_number, $line_offset, $buffer_length];
			$line_offset = $current;
			$line_number++;
		}
		
		fseek($fp, $start);
	}
}