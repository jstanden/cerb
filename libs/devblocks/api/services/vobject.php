<?php
class _DevblocksVObjectService {
	private static ?_DevblocksVObjectService $_instance = null;

	static function getInstance() : _DevblocksVObjectService {
		if(is_null(self::$_instance))
			self::$_instance = new _DevblocksVObjectService();
		
		return self::$_instance;
	}
	
	private function __construct() {}
	
	public function parse($string, &$error) : array {
		$lines = DevblocksPlatform::parseCrlfString($string, false, false);
		$index = 0;
		$data = [
			'props' => [],
			'children' => [],
		];
		
		$this->_extractVObject($lines, $index, $data);
		
		return $data['children'];
	}
	
	private function _extractVObject(&$lines, &$index, &$parent) {
		$props = [];
		
		while(array_key_exists($index, $lines)) {
			$line = $lines[$index];
			
			// Look ahead for concatenated lines
			while(
				array_key_exists($index+1, $lines)
				&& DevblocksPlatform::strStartsWith($lines[$index+1], [' ',"\t"])
			) {
				$index++;
				$line .= mb_substr($lines[$index], 1);
			}
			
			if(DevblocksPlatform::strStartsWith($line, 'END:', false)) {
				$parent['props'] = $props;
				$index++;
				return;
				
			} else if(DevblocksPlatform::strStartsWith($line, 'BEGIN:', false)) {
				$type = DevblocksPlatform::strUpper(
					DevblocksPlatform::services()->string()->strAfter($line, ':')
				);
				
				$new_node = [
					'props' => [],
					'children' => [],
				];
				
				$index++;
				$this->_extractVObject($lines, $index, $new_node);
				
				if(empty($new_node['props']))
					unset($new_node['props']);
				
				if(empty($new_node['children']))
					unset($new_node['children']);
				
				if(!array_key_exists('children', $parent))
					$parent['children'] = [];
				
				if(!array_key_exists($type, $parent['children']))
					$parent['children'][$type] = [];
				
				$parent['children'][$type][] = $new_node;
				
			// Parameters
			} else {
				list($prop_key, $prop_value) = array_pad(explode(':', $line, 2), 2, null);
				list($prop_key, $prop_params) = array_pad(explode(';', $prop_key, 2), 2, null);
				
				$prop_key = DevblocksPlatform::strUpper($prop_key);
				
				if(!array_key_exists($prop_key, $props))
					$props[$prop_key] = [];
				
				$props[$prop_key][] = [
					'params' => $prop_params ? $this->_parseParams($prop_params) : [],
					'value' => $this->_unescapeValue($prop_value),
				];
				
				$index++;
			}
		}
	}
	
	// State-based character parser
	private function _parseParams(string $str) : array {
		$state = 'KEY'; // KEY, VALUE, QUOTED_VALUE, ESCAPE_VALUE
		$len = strlen($str);
		$index = 0;
		$results = [];
		$key_name = null;
		$cur_key = null;
		
		while($index < $len) {
			if('KEY' === $state) {
				if('=' == $str[$index]) {
					$results[$key_name] = '';
					$cur_key = $key_name;
					$key_name = null;
					$state = 'VALUE';
				} else if(';' == $str[$index]) {
					$results[$key_name] = '';
					$cur_key = null;
					$key_name = null;
				} else {
					$key_name .= $str[$index];
				}
				
			} else if('VALUE' === $state) {
				if(';' === $str[$index]) {
					$cur_key = null;
					$state = 'KEY';
				} else if('"' === $str[$index]) {
					$state = 'QUOTED_VALUE';
				} else if('\\' === $str[$index]) {
					$state = 'ESCAPE_VALUE';
				} else {
					if($cur_key)
						$results[$cur_key] .= $str[$index];
				}
				
			} else if('QUOTED_VALUE' === $state) {
				if('"' === $str[$index]) {
					$state = 'VALUE';
				} else if('\\' === $str[$index]) {
					$state = 'QUOTED_ESCAPE_VALUE';
				} else {
					if($cur_key)
						$results[$cur_key] .= $str[$index];
				}
				
			} else if('ESCAPE_VALUE' === $state) {
				if($cur_key)
					$results[$cur_key] .= $str[$index];
				$state = 'VALUE';
					
			} else if('QUOTED_ESCAPE_VALUE' === $state) {
				if($cur_key)
					$results[$cur_key] .= $str[$index];
				$state = 'QUOTED_VALUE';
			}
			
			$index++;
		}
		
		// We can end parsing while still naming a key
		if('KEY' === $state && $key_name) {
			$results[$key_name] = '';
		}
		
		return $results;
	}
	
	private function _unescapeValue($str) {
		return str_replace(
			[
				'\r',
				'\n',
				'\N',
				'\t',
				'\,',
				'\;',
				'\"',
				"\'",
				"\\\\",
			],
			[
				"\r",
				"\n",
				"\n",
				"\t",
				",",
				";",
				'"',
				"'",
				"\\",
			],
			$str ?? ''
		);
	}
}