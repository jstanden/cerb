<?php
class _DevblocksKataService {
	private static $_instance = null;
	
	static function getInstance() {
		if(is_null(self::$_instance))
			self::$_instance = new _DevblocksKataService();
		
		return self::$_instance;
	}
	
	private function __construct() {}
	
	function parse($kata_string, &$error=null, $dereference=true) {
		$error = null;
		
		$lines = explode(
			"\n",
			str_replace(
				["\r"],
				[''],
				$kata_string
			)
		);
		
		$state = '';
		$tree = [];
		$ptr =& $tree;
		$indent_stack = [[0,&$ptr]];
		
		do {
			$line = current($lines);
			
			// Ignore completely blank lines
			if(0 == strlen($line))
				continue;
			
			$matches = [];
			
			$trimmed_line = ltrim($line, ' ');
			
			if($trimmed_line != ltrim($line)) {
				$error = 'Indents may not use tabs';
				return false;
			}
			
			$indent_len = strlen($line)-strlen($trimmed_line);
			list($last_indent_len,) = end($indent_stack);
			
			$indent_transition = 0;
			
			if($indent_len > $last_indent_len) {
				$indent_transition = 1;
				
			} else if($indent_len <= $last_indent_len) {
				while(end($indent_stack)[0] >= $indent_len) {
					$new_indent = end($indent_stack);
					
					if($new_indent[0] == $indent_len) {
						$ptr =& $new_indent[1];
						break;
						
					} else {
						array_pop($indent_stack);
						$ptr =& $new_indent[1];
						$indent_transition--;
					}
				}
			}
			
			$field_pattern = '^(&?[a-z0-9\-_/.*]+)(@[a-z0-9,]+)?:';
			
			switch($state) {
				case '':
					if(preg_match('#' . $field_pattern . '\s*$#i', $trimmed_line, $matches)) {
						@$field_id = $matches[1];
						@$field_attributes = DevblocksPlatform::parseCsvString(ltrim($matches[2], '@'));
						@$field_key = rtrim($matches[0], ':');
						
						$new_attributes = array_diff($field_attributes, ['text']);
						$field_key = $field_id . ($new_attributes ? ('@' . implode(',', $new_attributes)) : '');
						
						if(array_intersect($field_attributes, ['base64', 'bit', 'bool', 'csv', 'int', 'json', 'list', 'raw', 'text'])) {
							$state = 'text_block';
							
							$text_block = '';
							$text_block_indent = null;
							
							while(false !== next($lines)) {
								$text_line = current($lines);
								
								$trimmed_text = ltrim($text_line, ' ');
								
								if($trimmed_text != ltrim($text_line)) {
									$error = 'Indents may not use tabs';
									return false;
								}
								
								$text_indent_len = strlen($text_line)-strlen($trimmed_text);
								
								if(is_null($text_block_indent)) {
									$text_block_indent = $text_indent_len;
								}
								
								// Stop when we hit a line outdent
								if($text_indent_len <= $indent_len) {
									$state = '';
									prev($lines);
									break;
									
								} else {
									$text_block .= substr($text_line, $text_block_indent) . "\n";
								}
							}
							
							$ptr[$field_key] = substr($text_block, 0, -1);
							
							if($indent_transition > 0) {
								$indent_stack[] = [$indent_len, &$ptr];
							}
							
						} else {
							$ptr[$field_key] = [];
							
							if($indent_transition > 0) {
								$indent_stack[] = [$indent_len, &$ptr];
							}
							
							$ptr =& $ptr[$field_key];
						}
						
					} else if(preg_match('#' . $field_pattern . '?\s*(.*?)$#i', $trimmed_line, $matches)) {
						$key = $matches[1] . $matches[2];
						$value = $matches[3];
						
						$ptr[$key] = $value;
						
						if($indent_transition > 0) {
							$indent_stack[] = [$indent_len, &$ptr];
						}
						
					} else {
						if($indent_transition > 0) {
							$indent_stack[] = [$indent_len, &$ptr];
						}
					}
					break;
			}
			
		} while(false !== next($lines));
		
		if($dereference) {
			return $this->dereference($tree);
		} else {
			return $tree;
		}
	}
	
	function emit(array $input) {
		$output = null;
		
		$recurse = function($parent, $indent=0) use (&$output, &$recurse) {
			if(is_array($parent))
				foreach($parent as $k => $v) {
					if(is_object($v))
						$v = DevblocksPlatform::objectToArray($v);
					
					if(is_array($v)) {
						if($indent && DevblocksPlatform::arrayIsIndexed($v)) {
							$output .= str_repeat('  ', $indent) . strval($k) . "@list:\n";
							
							foreach($v as $list_item) {
								$output .= str_repeat('  ', $indent+1) . strval($list_item) . "\n";
							}
							
						} else {
							$output .= str_repeat('  ', $indent) . strval($k) . ":\n";
							
							$recurse($v, $indent+1);
						}
						
					} else {
						$lines = DevblocksPlatform::parseCrlfString($v);
						
						if(count($lines) > 1) {
							$output .= str_repeat('  ', $indent) . strval($k) . "@text:\n";
							
							foreach($lines as $line)
								$output .= str_repeat('  ', $indent+1) . strval($line) . "\n";
							
						} else {
							$output .= str_repeat('  ', $indent) . strval($k) . ": " . strval($v) . "\n";
						}
					}
				}
		};
		
		$recurse($input);
		
		return rtrim($output);
	}
	
	public function _getPathFromText($name) {
		return explode('.', $name);
	}
	
	public function getKeyPath($name, $tree, $default=null, &$ptr_key=null) {
		$queue = $this->_getPathFromText($name);
		
		$ptr =& $tree;
		$ptr_key = null;
		
		if(!is_array($queue))
			return $default;
			
		while(null !== ($k = array_shift($queue))) {
			if(is_array($ptr)) {
				$found = false;
				
				foreach(array_keys($ptr) as $child_k) {
					$kk = DevblocksPlatform::services()->string()->strBefore($child_k, '@');
					
					if($k == $kk) {
						$ptr =& $ptr[$child_k];
						$ptr_key = $child_k;
						$found = true;
						break;
					}
				}
				
				if(!$found)
					return $default;
				
			} else {
				if(0 == count($queue)) {
					return $ptr;
				}
			}
		}
		
		return $ptr;
	}
	
	function dereference($tree) {
		$parsed_tree = [];
		$references = [];
		
		// Extract top-level references
		foreach($tree as $k => $v) {
			if(DevblocksPlatform::strStartsWith($k, '&')) {
				$references[$k] = $v;
				unset($tree[$k]);
			}
		}
		
		// Unfurl nested reference definitions
		foreach($references as $k => $v) {
			$result = $this->_dereference($v, $k, $references);
			$k = key($result);
			$v = current($result);
			$references[$k] = $v;
		}
		
		// Replace references in the given tree
		foreach(array_keys($tree) as $k) {
			$parsed_tree = array_merge($parsed_tree, $this->_dereference($tree[$k], $k, $references));
		}
		
		return $parsed_tree;
	}
	
	private function _dereference($v, $k, array $references=[]) {
		if(is_string($v)) {
			@list($key, $annotations) = explode('@', $k, 2);
			
			$annotations = DevblocksPlatform::parseCsvString($annotations);
			
			$ref_at = array_search('ref', $annotations);
			
			if(false !== $ref_at) {
				$ref_key = '&' . trim($v);
				
				$k_matched = null;
				$v = $this->getKeyPath($ref_key, $references, null, $k_matched);
				
				if(!is_null($v)) {
					$result = $this->_dereference($v, $ref_key, $references);
					$v = current($result);
					
					// Does our target key have annotations?
					$new_annotations = DevblocksPlatform::parseCsvString(
						DevblocksPlatform::services()->string()->strAfter($k_matched, '@')
					);
					
					// Merge the annotations while removing 'ref'
					array_splice($annotations, $ref_at, 1, $new_annotations);
					
					// Rewrite the target key with the merged annotations
					if($annotations) {
						$key .= '@' . implode(',', $annotations);
					}
				}
				
				return [$key => $v];
			}
			
			return [$k => $v];
			
		} else if (is_array($v)) {
			$values = [];
			
			foreach(array_keys($v) as $kk) {
				$result = $this->_dereference($v[$kk], $kk, $references);
				$values[key($result)] = current($result);
			}
			
			return [$k => $values];
			
		} else {
			return [$k => $v];
		}
	}
	
	function formatTree($tree, DevblocksDictionaryDelegate $dict=null) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		if(!is_array($tree))
			return false;
		
		$parsed_tree = [];
		
		foreach(array_keys($tree) as $k) {
			$parsed_tree = array_merge($parsed_tree, $this->_formatTree($tree[$k], $k, $dict, $tpl_builder));
		}
		
		return $parsed_tree;
	}
	
	private function _formatTree($v, $k, DevblocksDictionaryDelegate $dict=null, _DevblocksTemplateBuilder $tpl_builder=null) {
		if(is_string($v)) {
			$annotations = [];
			
			if(false !== strpos($k,'@')) {
				@list($k, $ann) = explode('@', $k, 2);
				$annotations = DevblocksPlatform::parseCsvString($ann);
			}
			
			if($dict && !in_array('raw', $annotations)) {
				$v = $tpl_builder->build($v, $dict);
			}
			
			foreach($annotations as $annotation) {
				if($annotation == 'base64') {
					$v = base64_decode($v);
				} else if(in_array($annotation, ['bit'])) {
					$v = in_array(DevblocksPlatform::strLower(trim($v)), ['','0','false','n','no','off','null']) ? 0 : 1;
				} else if(in_array($annotation, ['bool'])) {
					$v = in_array(DevblocksPlatform::strLower(trim($v)), ['','0','false','n','no','off','null']) ? false : true;
				} else if($annotation == 'csv') {
					$v = DevblocksPlatform::parseCsvString($v);
				} else if($annotation == 'int') {
					$v = intval(trim($v));
				} else if($annotation == 'json') {
					$v = @json_decode($v, true);
				} else if($annotation == 'kata') {
					$v = DevblocksPlatform::services()->kata()->parse($v);
				} else if($annotation == 'key') {
					$key_path = trim($v);
					
					if($dict) {
						if (false !== strpos($key_path, '.')) {
							$v = $dict->getKeyPath($key_path);
						} else {
							$v = $dict->get($key_path);
						}
					} else {
						$v = $key_path;
					}
				} else if($annotation == 'list') {
					$v = DevblocksPlatform::parseCrlfString($v);
				}
			}
			
			return [$k => $v];
			
		} else if (is_array($v)) {
			$values = [];
			
			foreach(array_keys($v) as $kk) {
				$result = $this->_formatTree($v[$kk], $kk, $dict, $tpl_builder);
				$values[key($result)] = current($result);
			}
			
			return [$k => $values];
			
		} else {
			return [$k => $v];
		}
	}
}