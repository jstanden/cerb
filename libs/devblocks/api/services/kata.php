<?php
class _DevblocksKataService {
	private static ?_DevblocksKataService $_instance = null;

	private array $_valid_annotations = [
		'base64',
		'bit',
		'bool',
		'csv',
		'date',
		'int',
		'json',
		'kata',
		'key',
		'list',
		'raw',
		'text',
		'trim',
	];
	
	static function getInstance() : _DevblocksKataService {
		if(is_null(self::$_instance))
			self::$_instance = new _DevblocksKataService();
		
		return self::$_instance;
	}
	
	private function __construct() {}
	
	function parse($kata_string, &$error=null, $dereference=true, &$symbol_meta=[]) {
		$error = null;
		
		$lines = explode(
			"\n",
			str_replace(
				["\r"],
				[''],
				$kata_string ?? ''
			)
		);
		
		$state = '';
		$tree = [];
		$ptr =& $tree;
		$indent_stack = [[0,&$ptr]];
		
		$funcValidateKeyName = function($key, $line_number, &$error) {
			list($field_type, $field_name) = array_pad(explode('/', $key, 2), 2, null);
			
			if(empty($field_name))
				return true;
			
			// Validate field name
			if($field_name) {
				if($field_name != DevblocksPlatform::strAlphaNum($field_name, '_')) {
					$error = sprintf("`%s:` name `%s` must only contain letters, numbers, and underscores (line %d)", $field_type, $field_name, $line_number+1);
					return false;
				}
			}
			
			return true;
		};
		
		do {
			$line = current($lines);
			$line_number = key($lines);
			
			// Ignore completely blank lines
			if(0 == strlen(trim($line))) {
				continue;
			}
			
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
						$field_id = $matches[1] ?? null;
						
						// Validate field name
						if(!$funcValidateKeyName($field_id, $line_number, $error))
							return false;
						
						$field_attributes = DevblocksPlatform::parseCsvString(ltrim($matches[2] ?? '', '@'));
						
						$new_attributes = array_diff($field_attributes, ['text']);
						$field_key = $field_id . ($new_attributes ? ('@' . implode(',', $new_attributes)) : '');
						
						$siblings = array_map(fn($k) => DevblocksPlatform::services()->string()->strBefore($k, '@'), array_keys($ptr));
						
						if(in_array(DevblocksPlatform::services()->string()->strBefore($field_key, '@'), $siblings)) {
							$error = sprintf("`%s:` has a sibling with the same name (line %d)", $field_key, $line_number+1);
							return false;
						}
						
						if(array_intersect($field_attributes, ['base64', 'bit', 'bool', 'csv', 'date', 'int', 'json', 'list', 'raw', 'text', 'trim'])) {
							$state = 'text_block';
							
							$text_block = '';
							$text_block_indent = null;
							
							while(false !== next($lines)) {
								$text_line = current($lines);
								
								$trimmed_text = ltrim($text_line, ' ');
								
								if($trimmed_text != ltrim($text_line)) {
									$error = sprintf('Indents may not use tabs (line %d)', $line_number+1);
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
							
							$ptr[$field_key]['_line'] = $line_number;
							$ptr[$field_key]['_data'] = substr($text_block, 0, -1);
							
							if($indent_transition > 0) {
								$indent_stack[] = [$indent_len, &$ptr];
							}
							
						} else {
							$ptr[$field_key]['_line'] = $line_number;
							$ptr[$field_key]['_data'] = [];
							
							if($indent_transition > 0) {
								$indent_stack[] = [$indent_len, &$ptr];
							}
							
							$ptr =& $ptr[$field_key]['_data'];
						}
						
					} else if(preg_match('#' . $field_pattern . '\s*(.*?)$#i', $trimmed_line, $matches)) {
						// Validate field name
						if(!$funcValidateKeyName($matches[1], $line_number, $error))
							return false;
						
						$key = $matches[1] . $matches[2];
						$value = $matches[3];
						
						$siblings = array_map(fn($k) => DevblocksPlatform::services()->string()->strBefore($k, '@'), array_keys($ptr));
						
						if(in_array(DevblocksPlatform::services()->string()->strBefore($key, '@'), $siblings)) {
							$error = sprintf("`%s:` has a sibling with the same name (line %d)", $key, $line_number+1);
							return false;
						}
						
						$ptr[$key]['_line'] = $line_number;
						$ptr[$key]['_data'] = $value;
						
						if($indent_transition > 0) {
							$indent_stack[] = [$indent_len, &$ptr];
						}

					// Comments
					} else if(DevblocksPlatform::strStartsWith($trimmed_line, '#')) {
						if($indent_transition > 0) {
							$indent_stack[] = [$indent_len, &$ptr];
						}
					
					// Unknown command
					} else {
						$error = sprintf("Unexpected syntax (line %d): %s", $line_number+1, $trimmed_line);
						return false;
					}
					break;
			}
			
		} while(false !== next($lines));
		
		if($dereference)
			$tree = $this->dereference($tree);
		
		$out_tree = [];
		$node_path = [];
		
		foreach(array_keys($tree) as $k) {
			$out_tree[$k] = $this->_processTree($tree[$k], $k, $node_path, $symbol_meta);
		}
		
		return $out_tree;
	}

	private function _processTree($v, $k, &$node_path=[], &$out_meta=[]) {
		$response = null;
		
		list($key_type,) = explode('@', $k, 2);
		
		$node_path[] = $key_type;
		
		if(is_array($v) && array_key_exists('_data', $v) && array_key_exists('_line', $v)) {
			$out_meta[implode(':', $node_path)] = $v['_line'];
			
			if(is_string($v['_data'])) {
				$response = $v['_data'];
				
			} else if (is_array($v['_data'])) {
				$new_v = [];
				foreach($v['_data'] as $kk => $vv) {
					$new_v[$kk] = $this->_processTree($vv, $kk, $node_path, $out_meta);
				}
				$response = $new_v;
			}
			
		} else if (is_string($v)) {
			$response = $v;
			
		} else if (is_array($v)) {
			$new_v = [];
			foreach($v as $kk => $vv) {
				$new_v[$kk] = $this->_processTree($vv, $kk, $node_path, $out_meta);
			}
			$response = $new_v;
		}
		
		array_pop($node_path);
		return $response;
	}
	
	function emit(array $input) : string {
		$output = '';
		
		$recurse = function($parent, $indent=0) use (&$output, &$recurse) {
			if(is_array($parent))
				foreach($parent as $k => $v) {
					if(is_object($v))
						$v = DevblocksPlatform::objectToArray($v);
					
					if(is_array($v)) {
						if(DevblocksPlatform::arrayIsIndexed($v)) {
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
							
						} else if(is_integer($v)) {
							$output .= str_repeat('  ', $indent) . strval($k) . "@int: " . intval($v) . "\n";
							
						} else {
							$output .= str_repeat('  ', $indent) . strval($k) . ": " . strval($v) . "\n";
						}
					}
				}
		};
		
		$recurse($input);
		
		return rtrim($output);
	}
	
	private function getDataKeyPath($name, $tree, $default=null, &$ptr_key=null) {
		$queue = explode(':', $name);
		
		$ptr =& $tree;
		$ptr_key = null;
		
		if(!is_array($queue))
			return $default;
		
		while(null !== ($k = array_shift($queue))) {
			if(is_array($ptr)) {
				if(array_key_exists('_line', $ptr) && array_key_exists('_data', $ptr))
					$ptr =& $ptr['_data'];
				
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
	
	private function dereference(array $tree) : array {
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
		foreach(array_keys($references) as $k) {
			$result = $this->_dereference($references[$k], $k, $references);
			$k = key($result);
			$references[$k] = current($result);
		}
		
		// Replace references in the given tree
		foreach(array_keys($tree) as $k) {
			$result = $this->_dereference($tree[$k], $k, $references);
			$parsed_tree[key($result)] = current($result);
		}
		
		return $parsed_tree;
	}
	
	private function _dereference($v, $k, array $references=[]) : array {
		if (array_key_exists('_line', $v) && array_key_exists('_data', $v) && is_array($v['_data'])) {
			$values = [
				'_line' => $v['_line'],
				'_data' => [],
			];
			
			foreach (array_keys($v['_data']) as $kk) {
				$result = $this->_dereference($v['_data'][$kk], $kk, $references);
				$kk = key($result);
				$values['_data'][$kk] = current($result);
			}
			
			return [$k => $values];
			
		} else if (array_key_exists('_line', $v) && array_key_exists('_data', $v) && is_string($v['_data'])) {
			list($key, $annotations) = array_pad(explode('@', $k, 2), 2, null);
			
			$annotations = DevblocksPlatform::parseCsvString($annotations);
			
			$ref_at = array_search('ref', $annotations);
			
			if(false !== $ref_at) {
				$ref_key = '&' . trim($v['_data']);
				
				$k_matched = null;
				$ref_v = $this->getDataKeyPath($ref_key, $references, null, $k_matched);
				
				if(!is_null($ref_v)) {
					$result = $this->_dereference($ref_v, $ref_key, $references);
					
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
			
		} else {
			return [$k => $v];
		}
	}
	
	private array $_formatTreeStack;
	
	function formatTree($tree, DevblocksDictionaryDelegate $dict=null, &$error=null) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		if(!is_array($tree))
			return false;
		
		$parsed_tree = [];
		$this->_formatTreeStack = [];
		
		foreach(array_keys($tree) as $k) {
			$merge_tree = $this->_formatTree($tree[$k], $k, $dict, $tpl_builder, $error);
			
			if(!is_null($error)) {
				$error = sprintf("[%s] %s",
					implode(':', $this->_formatTreeStack),
					$error
				);
				return false;
			}
			
			array_pop($this->_formatTreeStack);
			
			$parsed_tree = array_merge($parsed_tree, $merge_tree);
		}
		
		return $parsed_tree;
	}
	
	private function _formatTree($v, $k, DevblocksDictionaryDelegate $dict=null, _DevblocksTemplateBuilder $tpl_builder=null, &$error=null) {
		$this->_formatTreeStack[] = DevblocksPlatform::services()->string()->strBefore($k, '@');
		
		if(is_string($v)) {
			$annotations = [];
			
			if(false !== strpos($k,'@')) {
				list($k, $ann) = array_pad(explode('@', $k, 2), 2, null);
				$annotations = DevblocksPlatform::parseCsvString($ann);
			}
			
			if($dict) {
				if(in_array('raw', $annotations)) {
					$annotations = array_diff($annotations, ['raw']);
					
					if ($annotations)
						$k .= '@' . implode(',', $annotations);
					
					return [$k => $v];
				}
				
				if(false === ($v = $tpl_builder->build($v, $dict))) {
					$error = $tpl_builder->getLastError();
					return false;
				}
			}
			
			foreach($annotations as $annotation) {
				if($annotation == 'base64') {
					$v = base64_decode($v);
				} else if(in_array($annotation, ['bit'])) {
					$v = DevblocksPlatform::services()->string()->toBool($v) ? 1 : 0;
				} else if(in_array($annotation, ['bool'])) {
					$v = DevblocksPlatform::services()->string()->toBool($v);
				} else if($annotation == 'csv') {
					$v = DevblocksPlatform::parseCsvString($v);
				} else if(in_array($annotation, ['date'])) {
					$v = DevblocksPlatform::services()->string()->toDate($v);
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
				} else if(in_array($annotation, ['optional','raw','text'])) {
					// Do nothing
				} else if($annotation == 'trim') {
					if(is_string($v))
						$v = trim($v);
				} else {
					$error = sprintf("Unknown annotation: @%s", $annotation);
					return false;
				}
			}
			
			return [$k => $v];
			
		} else if (is_array($v)) {
			$values = [];
			$annotations = [];
			
			if(false !== strpos($k,'@')) {
				list($k, $ann) = array_pad(explode('@', $k, 2), 2, null);
				$annotations = DevblocksPlatform::parseCsvString($ann);
			}
			
			if(in_array('raw', $annotations)) {
				$annotations = array_diff($annotations, ['raw']);
				
				if($annotations)
					$k .= '@' . implode(',', $annotations);
				
				return [$k => $v];
			}
				
			foreach(array_keys($v) as $kk) {
				$result = $this->_formatTree($v[$kk], $kk, $dict, $tpl_builder, $error);
				
				if(!is_null($error))
					return false;
				
				array_pop($this->_formatTreeStack);
				
				$values[key($result)] = current($result);
			}
			
			return [$k => $values];
			
		} else {
			return [$k => $v];
		}
	}
	
	public function validate(string $doc_kata, string $schema_kata, ?string &$error=null, ?DevblocksDictionaryDelegate $dict=null) : bool {
		$kata = DevblocksPlatform::services()->kata();
		
		if(false === ($doc_tree = $kata->parse($doc_kata, $error)))
			return false;
		
		if(false === ($doc_tree = $kata->formatTree($doc_tree, $dict, $error)))
			return false;

		if(false === ($schema = $this->parse($schema_kata, $error))) {
			$error = sprintf("Syntax error: %s", $error);
			return false;
		}
		
		$schema_dict = DevblocksDictionaryDelegate::instance([]);
		
		if(false === ($schema = $this->formatTree($schema, $schema_dict, $error)))
			return false;
		
		// Definitions (for recursion)
		$schema_definitions = $schema['definitions'] ?? [];
		$schema = $schema['schema'] ?? [];
		
		$funcValidateKey = function($key, $value, $schema, &$key_path, &$error) use (&$funcValidateKey, &$dict, $schema_definitions) {
			$string = DevblocksPlatform::services()->string();
			
			if($key) { // If not the root
				$key_path[] = $key;
				
				list($key_type,) = array_pad(explode('/', $key), 2, null);
				
				$node_types = $schema[$key_type]['types'] ?? [];
				
			} else { // If this is the root
				$schema = ['types' => ['object' => $schema]];
				$node_types = $schema['types'];
			}
			
			$found_type = false;
			
			// Check all possible types in order
			foreach($node_types as $node_type => $node_type_params) {
				if($found_type)
					break;
				
				if('object' == $node_type && is_array($value) && !DevblocksPlatform::arrayIsIndexed($value)) {
					$found_type = true;
					
					$type_attributes = $node_type_params['attributes'] ?? [];
					
					$node_attributes =
						array_combine(
							array_keys($value),
							array_map(fn($k) => $string->strBefore($string->strBefore($k, '/'), '@'), array_keys($value))
						)
					;
					
					// Handle unknown attributes
					
					$unknown_attributes = array_diff(
						array_unique(array_values($node_attributes)),
						array_keys($type_attributes)
					);
					
					if($unknown_attributes) {
						$attribute_patterns = $node_type_params['attributePatterns'] ?? [];
						
						if(!is_array($attribute_patterns))
							$attribute_patterns = [];
						
						foreach($unknown_attributes as $unknown_attribute) {
							$found_attribute = false;
							
							foreach($attribute_patterns as $attribute_pattern_key => $attribute_pattern) {
								if('pattern' != DevblocksPlatform::services()->string()->strBefore($attribute_pattern_key, '/'))
									continue;
								
								if(is_array($attribute_pattern) && array_key_exists('match', $attribute_pattern)) {
									$attribute_pattern_match = $attribute_pattern['match'];
									
									if(!DevblocksPlatform::strStartsWith($attribute_pattern_match, '/'))
										$attribute_pattern_match = DevblocksPlatform::strToRegExp($attribute_pattern_match);
									
									if(preg_match($attribute_pattern_match, $unknown_attribute)) {
										$found_attribute = true;
										$type_attributes[$unknown_attribute] = $attribute_pattern['attributes'] ?? [];
									}
								}
							}
							
							if(!$found_attribute) {
								$error = sprintf(
									'Key `%s%s:` is unknown',
									$key_path ? (implode(':', $key_path) . ':') : '',
									$unknown_attribute
								);
								
								return false;
							}
						}
					}
					
					foreach ($type_attributes as $attr_key => &$attr_params) {
						// Using a definition?
						if(array_key_exists('ref', $attr_params) && is_string($attr_params['ref'])) {
							if(array_key_exists($attr_params['ref'], $schema_definitions)) {
								$merge_params = array_diff_key($attr_params, ['ref'=>true]);
								$attr_params = array_merge($schema_definitions[$attr_params['ref']], $merge_params);
							}
						}
						
						$attr_key_count = array_count_values($node_attributes);
						$attr_key_prefix = $key_path ? (implode(':', $key_path) . ':') : '';
						
						if (!is_array($attr_params))
							$attr_params = [];
						
						if (array_key_exists('required', $attr_params)) {
							if (!array_key_exists($attr_key, $attr_key_count)) {
								$error = sprintf('Key `%s%s:` is required', $attr_key_prefix, $attr_key);
								return false;
							}
						}
						
						if (!array_key_exists('multiple', $attr_params) || !$attr_params['multiple']) {
							// If not nameable but named
							foreach ($node_attributes as $na_key => $na_type) {
								if ($attr_key == $na_type && false !== strpos($na_key, '/')) {
									$error = sprintf('Key `%s%s:` must not have a name', $attr_key_prefix, $attr_key);
									return false;
								}
							}
							
							// If duplicated
							if (array_key_exists($attr_key, $attr_key_count) && $attr_key_count[$attr_key] > 1) {
								$error = sprintf('Key `%s%s:` can not be duplicated', $attr_key_prefix, $attr_key);
								return false;
							}
						}
					}
					
					foreach ($value as $k => $v) {
						if (false === ($funcValidateKey($k, $v, $type_attributes, $key_path, $error)))
							return false;
					}
				
				} else { // Not an object
					if(!$dict)
						$found_type = true;
					
					if('any' == $node_type) {
						$found_type = true;
						
					} elseif('bit' == $node_type) {
						if(in_array($value, [0,1]))
							$found_type = true;
						
					} elseif('bool' == $node_type) {
						if(is_bool($value))
							$found_type = true;
						
					} elseif('list' == $node_type) {
						if(is_array($value))
							$found_type = true;
						
					} elseif('number' == $node_type) {
						if(is_numeric($value))
							$found_type = true;
						
					} elseif('string' == $node_type) {
						if(is_string($value) || is_null($value))
							$found_type = true;
					}					
				}
			}
			
			// If we didn't find a suitable type
			if(!$found_type) {
				if($node_types) {
					$error = sprintf("Key `%s:` must be of type: %s",
						implode(':', $key_path),
						implode(', ', array_keys($node_types))
					);
				} else {
					$error = sprintf("Key `%s:` has no schema type",
						implode(':', $key_path),
					);
				}
				
				return false;
			}
			
			if($key)
				array_pop($key_path);
			
			return true;
		};
		
		$key_path = [];
		$error = null;
		
		if(false === $funcValidateKey(null, $doc_tree, $schema, $key_path, $error))
			return false;
		
		return true;
	}
}