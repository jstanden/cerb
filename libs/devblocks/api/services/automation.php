<?php
class Exception_DevblocksAutomationError extends Exception_Devblocks {};

class _DevblocksAutomationService {
	private static $_instance = null;
	
	static function getInstance() {
		if(is_null(self::$_instance))
			self::$_instance = new _DevblocksAutomationService();
		
		return self::$_instance;
	}
	
	/**
	 * 
	 * @return _DevblocksAutomationService
	 */
	function newInstance() {
		return new _DevblocksAutomationService();
	}
	
	private function __construct() {}
	
	function _validateInputs(array &$automation_script, DevblocksDictionaryDelegate $dict, &$error=null) {
		if(array_key_exists('inputs', $automation_script)) {
			$inputs_meta = $automation_script['inputs'];
			
			// Enhance
			$inputs_meta = DevblocksPlatform::services()->kata()->formatTree($inputs_meta, $dict);
			
			foreach ($inputs_meta as $input_idx => $input_data) {
				list($input_type, $input_key) = explode('/', $input_idx);
				
				$input_value = $dict->getKeyPath('inputs.' . $input_key, null);
				$is_required = array_key_exists('required', $input_data) && $input_data['required'];
				
				if ('text' == $input_type) {
					$inputs_validation = DevblocksPlatform::services()->validation();
					$input_field = $inputs_validation->addField($input_key, 'inputs:' . $input_key);
					
					if(!array_key_exists('type', $input_data)) {
						$input_field_type = $input_field->string();
						
					} else {
						$input_field_type = null;
						
						switch(@$input_data['type']) {
							case 'bool':
								$bools = [
									true => true,
									1 => true,
									'yes' => true,
									'y' => true,
									'true' => true,
									false => false,
									0 => false,
									'no' => true,
									'n' => true,
									'false' => true,
									null => false,
								];
								
								$bool = $bools[$input_value] ?? false;
								$input_values[$input_key] = $bool;
								
								$dict->setKeyPath('inputs.' . $input_key, $bool);
								
								$input_field_type = $input_field->boolean();
								break;
							
							case 'date':
								$dict->setKeyPath('inputs.' . $input_key, strtotime($input_value));
								
								$input_field_type = $input_field->string()
									->addValidator($inputs_validation->validators()->date())
								;
								break;
							
							case 'decimal':
								$dict->setKeyPath('inputs.' . $input_key, floatval($input_value));
								
								$input_field_type = $input_field->float()
									//->setMin(0)
									//->setMax(255)
								;
								break;
							
							case 'email':
								// [TODO] Return email with mailbox/host/full
								$input_field_type = $input_field->string()
									->addValidator($inputs_validation->validators()->email())
								;
								break;
								
							case 'freeform':
								$input_field_type = $input_field->string();
								break;
							
							case 'geopoint':
								$dict->setKeyPath('inputs.' . $input_key, DevblocksPlatform::parseGeoPointString($input_value));
								
								$input_field_type = $input_field->geopoint();
								break;
							
							case 'ip':
								$input_field_type = $input_field->string()
									->addValidator($inputs_validation->validators()->ip())
								;
								break;
							
							case 'ipv4':
								$input_field_type = $input_field->string()
									->addValidator($inputs_validation->validators()->ipv4())
								;
								break;
							
							case 'ipv6':
								$input_field_type = $input_field->string()
									->addValidator($inputs_validation->validators()->ipv6())
								;
								break;
							
							case 'record_type':
								$input_field_type = $input_field->string()
									->addValidator($inputs_validation->validators()->context(true))
								;
								break;
							
							case 'number':
								$dict->setKeyPath('inputs.' . $input_key, intval($input_value));
								
								$input_field_type = $input_field->number()
									//->setMin(0)
									//->setMax(255)
								;
								break;
							
							case 'timestamp':
								$input_field_type = $input_field->timestamp();
								break;
							
							case 'uri':
								$input_field_type = $input_field->string()
									->addValidator($inputs_validation->validators()->uri())
								;
								break;
							
							case 'url':
								$input_field_type = $input_field->url();
								break;
							
							default:
								$error = sprintf('Unknown text type `%s` for input `%s`',
									$input_data['type'],
									$input_key
								);
								return false;
						}
					}
					
					if($is_required)
						$input_field_type->setRequired(true);
					
					$error = null;
					$input_values = [];
					
					// Defaults
					if(is_null($input_value) && array_key_exists('default', $input_data)) {
						$input_value = $input_data['default'];
						$dict->setKeyPath('inputs.' . $input_key, $input_value);
					}
					
					// If not required, don't add a value
					if($is_required || !is_null($input_value))
						$input_values[$input_key] = $input_value;
					
					if(false == ($inputs_validation->validateAll($input_values, $error))) {
						return false;
					}
					
				} else if ('array' == $input_type) {
					$inputs_validation = DevblocksPlatform::services()->validation();
					$input_values = [];
					
					$input_field = $inputs_validation->addField($input_key, sprintf('inputs:' . $input_key))
						->array()
						;
					
					if($is_required)
						$input_field->setRequired(true);
					
					// Defaults
					if(is_null($input_value) && array_key_exists('default', $input_data)) {
						$input_value = $input_data['default'];
						$dict->setKeyPath('inputs.' . $input_key, $input_value);
					}
					
					if($is_required || !is_null($input_value))
						$input_values[$input_key] = $input_value;
					
					if(false == ($inputs_validation->validateAll($input_values, $error))) {
						return false;
					}
				
				} else if ('record' == $input_type) {
					$inputs_validation = DevblocksPlatform::services()->validation();
					$input_values = [];
					
					$input_field = $inputs_validation->addField($input_key, sprintf('inputs:' . $input_key))
						->id()
						->addValidator($inputs_validation->validators()->contextId($input_data['record_type'], !$is_required))
					;
					
					if($is_required)
						$input_field->setRequired(true);
					
					// Defaults
					if(is_null($input_value) && array_key_exists('default', $input_data)) {
						$input_value = $input_data['default'];
						$dict->setKeyPath('inputs.' . $input_key, $input_value);
					}
					
					if($is_required || !is_null($input_value))
						$input_values[$input_key] = $input_value;
					
					if(false == ($inputs_validation->validateAll($input_values, $error))) {
						return false;
					}
					
					$dict->setKeyPath('inputs.' . $input_key,
						DevblocksDictionaryDelegate::instance([
							'id' => $input_value,
							'_context' => $input_data['record_type'],
						])
					);
					
				} else if ('records' == $input_type) {
					$inputs_validation = DevblocksPlatform::services()->validation();
					
					$records = [];
					$input_values = [];
					
					$input_field = $inputs_validation->addField($input_key, sprintf('inputs:' . $input_key))
						->idArray()
						->addValidator($inputs_validation->validators()->contextIds($input_data['record_type'], !$is_required))
					;
					
					if($is_required)
						$input_field->setRequired(true);
					
					if($is_required || !is_null($input_value))
						$input_values[$input_key] = $input_value;
					
					if(false == ($inputs_validation->validateAll($input_values, $error))) {
						return false;
					}
					
					if(is_array($input_value))
						foreach($input_value as $v) {
							$records[] = DevblocksDictionaryDelegate::instance([
								'id' => $v,
								'_context' => $input_data['record_type'],
							]);
						}
					
					$dict->setKeyPath('inputs.' . $input_key, $records);
					
				} else {
					$error = sprintf("`inputs:%s` has an unknown type `%s`", $input_key, $input_type);
					return false;
				}
			}
		}
	}
	
	/**
	 * 
	 * @param Model_Automation $automation
	 * @param array $initial_state
	 * @param string $error
	 * @return DevblocksDictionaryDelegate|FALSE
	 */
	public function executeScript(Model_Automation $automation, array $initial_state=[], &$error=null) {
		$error = null;
		
		$policy = $automation->getPolicy();
		
		if(false == ($automation_script = DevblocksPlatform::services()->kata()->parse($automation->script, $error))) {
			if(!$automation_script) {
				$error = "No `start:` node was found";
			} else {
				$error = "Invalid automation script";
			}
			return false;
		}
		
		$dict = DevblocksDictionaryDelegate::instance($initial_state);
		
		// On the first run
		if(!$dict->exists('__state')) {
			if(false === $this->_validateInputs($automation_script, $dict, $error))
				return false;
		}
		
		// Remove inputs before running
		unset($automation_script['inputs']);
		
		// [TODO] Cache?
		if(false === ($ast_tree = $this->buildAstFromKata($automation_script, $error))) {
			return false;
		}
		
		$this->runAST($ast_tree, $dict, $policy);
		
		// Convert any nested dictionaries to arrays
		$nested_keys = [];
		
		$findNested = function($node, $path=[]) use (&$findNested, &$nested_keys) {
			if($node instanceof DevblocksDictionaryDelegate) {
				if($path) {
					$nested_keys[] = implode('.', $path);
				}
				
				foreach($node as $k => $v) {
					$path[] = $k;
					$findNested($v, $path);
					array_pop($path);
				}
				
			} else if(is_array($node)) {
				foreach ($node as $k => $v) {
					$path[] = $k;
					$findNested($v, $path);
					array_pop($path);
				}
			}
		};
		
		foreach($dict as $k => $v) {
			$findNested($v, [$k]);
		}
		
		// Sort the deepest paths first
		rsort($nested_keys);
		
		$dict->set('__expandable', $nested_keys);
		
		return $dict;
	}
	
	public function buildAstFromKata(array $yaml, &$error=null) {
		$environment = [];
		
		$root = new CerbAutomationAstNode('automation', 'root');
		
		@$tree = $yaml ?: [];
		
		$states = [];
		$error = null;
		
		if(false === ($this->_recurseBuildAST($tree, $root, $environment, $states, $error)))
			return false;
		
		return $root;
	}
	
	private function runAST(CerbAutomationAstNode $tree, DevblocksDictionaryDelegate &$dict, CerbAutomationPolicy $policy=null) {
		// [TODO] Time limits by role?
		
		// [TODO] Check if we're given an exit/return/error/await status
		$dict->unset('__exit');
		$dict->unset('__return');
		
		// [TODO] Raise this in production
		$iterations = 0;
		$max_iterations = 2500;
		
		$environment = [
			'debug' => false,
			'policy' => $policy,
			'state' => $dict->getKeyPath('__state.next', $tree->getId()),
			'state_last' => $dict->getKeyPath('__state.last', null),
		];
		
		// Loop while not terminated
		while($environment['state'] && $iterations++ < $max_iterations) {
			// [TODO] Return error
			if(null == ($node = $this->_recurseFindNodeId($tree, $environment['state'])))
				return false;
			
			if(false === ($node->activate($dict, $environment, $error))) {
				return false;
			}
			
			$exit_code = $dict->get('__exit', null);
			
			// Exit
			if('exit' == $exit_code) {
				$dict->setKeyPath('__state.next', $environment['state']);
				$environment['state'] = null;
				
			// Return
			} else if('return' == $exit_code) {
				$dict->setKeyPath('__state.next', $environment['state']);
				$environment['state'] = null;
			
			// Error
			} else if('error' == $exit_code) {
				$dict->setKeyPath('__state.next', $environment['state']);
				$environment['state'] = null;
			
			// Await
			} else if ($environment['debug'] || 'await' == $exit_code) {
				if($environment['debug'])
					$dict->set('__exit', 'await');
				
				$environment['state'] = null;
				
			// Continue
			} else {
				$environment['state_last'] = $dict->getKeyPath('__state.last', null);
				$environment['state'] = $dict->getKeyPath('__state.next', null);
			}
		}
	}
	
	private function _recurseFindNodeId(CerbAutomationAstNode $node, $id) {
		if($node->getId() == $id)
			return $node;
		
		foreach($node->getChildren() as $child)
			if(null != ($found = $this->_recurseFindNodeId($child, $id)))
				return $found;
			
		return null;
	}
	
	private function _getNodeFromType($type) {
		@list($name,) = explode('/', $type, 2); // remove aliases
		@list($name,) = explode('@', $name, 2); // remove annotations
		
		return $name;
	}
	
	private function _isNodeName($name, $type=null) {
		$node_names = $this->_getNodeGrammar($type);
		$node_name = $this->_getNodeFromType($name);
		
		if(in_array($node_name, $node_names))
			return true;
		
		return false;
	}
	
	private function _getNodeGrammar($type=null) {
		$commands = [
			'start',
			'decision',
			'outcome',
			'repeat',
		];
		
		$actions = [
			'await',
			'data.query',
			'email.parse',
			'email.send',
			'error',
			'function',
			'http.request',
			'log',
			'record.create',
			'record.delete',
			'record.get',
			'record.update',
			'record.upsert',
			'return',
			'set',
			'storage.delete',
			'storage.get',
			'storage.set',
			'var.push',
			'var.set',
			'var.unset',
		];
		
		if(is_null($type)) {
			return array_merge($commands, $actions);
		} else if('action' == $type) {
			return $actions;
		} else if('command' == $type) {
			return $commands;
		} else {
			return false;
		}
	}
	
	private function _buildNode(CerbAutomationAstNode $node, string $type, $children, array $environment, array &$states, &$error=null) {
		$states[] = $type;
		$id = implode(':', $states);
		
		$type = $this->_getNodeFromType($type);
		$error = null;
		
		if(is_string($children)) {
			$children = ['' => $children];
		} else {
			if(!is_array($children))
				$children = [];
		}
		
		if($this->_isNodeName($type, 'action')) {
			$new_node = new CerbAutomationAstNode($id, 'action');
			$node->addChild($new_node);
			
			if(false === ($this->_findActionEvents($children, $states, $new_node, $environment, $error)))
				return false;
			
			$new_node->setParams($children);
			
		} else if ($this->_isNodeName($type)) {
			$new_node = new CerbAutomationAstNode($id, $type, $children);
			$node->addChild($new_node);
			
			if(false === ($this->_recurseBuildAST($children, $new_node, $environment, $states, $error)))
				return false;
			
		} else {
			$path = implode(':', array_merge($states));
			$error = sprintf("Unexpected node `%s:`", $path);
			return false;
		}
		
		array_pop($states);
	}
	
	private function _recurseBuildAST(array $yaml, CerbAutomationAstNode $node, $environment, &$states=[], &$error=null) {
		$node_type = $node->getType();
		
		if($node_type == 'root') {
			if(is_array($yaml))
			foreach($yaml as $type => $child) {
				$node_name = $this->_getNodeFromType($type);
				
				if('start' == $node_name) {
					if(false === ($this->_buildNode($node, $type, $child, $environment, $states, $error)))
						return false;
					
				} else {
					$path = implode(':', array_merge($states, [$node_name]));
					$error = sprintf("Unexpected node `%s:`. Expected `start:`", $path);
					return false;
				}
			}
		
		} elseif ($node_type == 'decision') {
			if(is_array($yaml))
			foreach($yaml as $type => $child) {
				$node_name = $this->_getNodeFromType($type);
				
				if('outcome' == $node_name) {
					$node->removeParam($type);
					
					if(false === ($this->_buildNode($node, $type, $child, $environment, $states, $error)))
						return false;
					
				} else {
					$path = implode(':', array_merge($states, [$node_name]));
					$error = sprintf("Unexpected node `%s:`. Expected `outcome:`", $path);
					return false;
				}
			}
			
		} elseif ($node_type == 'outcome') {
			$node->getParent()->removeParam($node->getId());
			$node->removeParam('then');
			
			foreach($yaml as $type => $child) {
				$node_name = $this->_getNodeFromType($type);
				
				if('if' == $node_name) {
					true;
					
				} else if('then' == $node_name) {
					$states[] = 'then';
					
					if(is_array($yaml['then']))
					foreach($yaml['then'] as $then_type => $then_child) {
						if ($this->_isNodeName($then_type)) {
							if(false === ($this->_buildNode($node, $then_type, $then_child, $environment, $states, $error)))
								return false;
						}
					}
					
					array_pop($states);
					
				} else {
					$path = implode(':', array_merge($states, [$node_name]));
					$error = sprintf("Unexpected node `%s:`. Expected `if:` or `then:`", $path);
					return false;
				}
			}
			
		} elseif ($node_type == 'repeat') {
			$node->removeParam('do');
			
			foreach($yaml as $type => $child) {
				$node_name = $this->_getNodeFromType($type);
				
				if('each' == $node_name) {
					true;
				} else if('as' == $node_name) {
					true;
				} else if('do' == $node_name) {
					$states[] = 'do';
					
					if(is_array($yaml['do']))
					foreach($yaml['do'] as $do_type => $do_child) {
						if ($this->_isNodeName($do_type)) {
							if(false === ($this->_buildNode($node, $do_type, $do_child, $environment, $states, $error)))
								return false;
							
						} else {
							$path = implode(':', array_merge($states, [$do_type]));
							$error = sprintf("Unexpected node `%s:`", $path);
							return false;
						}
					}
					
					array_pop($states);
					
				} else {
					$path = implode(':', array_merge($states, [$node_name]));
					$error = sprintf("Unexpected node `%s:`. Expected `each:`, `as:`, or `do:`", $path);
					return false;
				}
			}
			
		} elseif (in_array($node_type, ['event','start'])) {
			$node->removeParams();
			
			if(is_array($yaml))
			foreach($yaml as $type => $child) {
				if($this->_isNodeName($type)) {
					if(false === ($this->_buildNode($node, $type, $child, $environment, $states, $error)))
						return false;
					
				} else {
					$path = implode(':', array_merge($states, [$type]));
					$error = sprintf("Unexpected node `%s:`", $path);
					return false;
				}
			}
			
		} else {
			$path = implode(':', array_merge($states, [$node_type]));
			$error = sprintf("Unexpected node `%s:`.", $path);
			return false;
		}
	}
	
	private function _findActionEvents(&$params, &$states, &$new_action, $environment, &$error=null) {
		if(!is_array($params))
			return true;
		
		foreach($params as $key => $value) {
			$states[] = $key;
			
			// [TODO] Change to `onSuccess` etc
			if(in_array(strval($key), ['on_success','on_error','on_simulate'])) {
				$event_id = implode(':', $states);
				
				$new_event = new CerbAutomationAstNode($event_id, 'event', []);
				$new_action->addChild($new_event);
				
				$event_params = $params[$key];
				unset($params[$key]);
				
				if(is_array($event_params))
					if(false === ($this->_recurseBuildAST($event_params, $new_event, $environment, $states, $error)))
						return false;
				
			} else {
				if(is_array($value)) {
					if(false === ($this->_findActionEvents($params[$key], $states, $new_action, $environment, $error)))
						return false;
				}
			}
			
			array_pop($states);
		}
	}
}

class CerbAutomationPolicy {
	private $_rules;
	
	public function __construct($policy_data) {
		$this->_rules = [];
		
		if(is_string($policy_data)) {
			$error = null;
			
			if(false == ($policy_data = DevblocksPlatform::services()->kata()->parse($policy_data, $error)))
				$policy_data = [];
		}
		
		if(!is_array($policy_data))
			return false;
		
		foreach($policy_data as $node_name => $rules) {
			foreach($rules as $rule_key => $rule_data) {
				@list($rule_name, $rule_id) = explode('/', $rule_key, 2);
				
				if('rule' != $rule_name)
					continue;
				
				if(!$rule_id)
					$rule_id = $rule_name;
				
				if(!$rule_id)
					$rule_id = uniqid();
				
				$this->_rules[$node_name][$rule_id] = $rule_data;
			}
		}
		
		return $this;
	}
	
	public function isAllowed($node_name, DevblocksDictionaryDelegate $dict)  {
		if(!array_key_exists($node_name, $this->_rules))
			return false;
		
		foreach($this->_rules[$node_name] as $rule_data) {
			$rule = DevblocksPlatform::services()->kata()->formatTree($rule_data, $dict);
			
			if(array_key_exists('allow', $rule) && $rule['allow'])
				return true;
		}
		
		return false;
	}
}

class CerbAutomationAstNode implements JsonSerializable {
	private $_id = null;
	private $_type = null;
	private $_parent = null;
	private $_params = [];
	private $_children = [];
	
	public function __construct($id, $type, array $params=[]) {
		$this->setId($id);
		$this->setType($type);
		$this->setParams($params);
	}
	
	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'type' => $this->getType(),
			'params' => $this->getParams(),
			'children' => array_column(DevblocksPlatform::objectsToArrays($this->getChildren()), 'id'),
		];
	}
	
	public function setId($id) {
		$this->_id = $id;
		return $this;
	}
	
	public function getId() {
		return $this->_id;
	}
	
	public function setType($type) {
		$this->_type = $type;
		return $this;
	}
	
	public function getType() {
		return $this->_type;
	}
	
	public function setParams($params) {
		$this->_params = $params;
		return $this;
	}
	
	public function getParams(DevblocksDictionaryDelegate $dict=null) {
		$params = $this->_params;
		
		if(is_null($dict))
			return $params;
		
		$return_values = [];
		
		if(is_array($params))
		foreach($params as $k => $v) {
			if(false !== ($this->_formatKeyValue($k, $v, $dict)))
				$return_values[$k] = $v;
		}
		
		return $return_values;
	}
	
	public function setParam($key, $value) {
		$this->_params[$key] = $value;
		return $this;
	}
	
	public function getParam($key, $default=null, DevblocksDictionaryDelegate $dict=null) {
		if(array_key_exists($key, $this->_params)) {
			if(!is_null($dict)) {
				if(is_array($this->_params[$key])) {
					$return_values = [];
					
					foreach($this->_params[$key] as $k => $v)
						if(false !== ($this->_formatKeyValue($k, $v, $dict)))
							$return_values[$k] = $v;
					
					return $return_values;
					
				} else if (is_string($this->_params[$key])) {
					$v = $this->_params[$key];
					
					if(false !== ($this->_formatKeyValue($key, $v, $dict)))
						return $v;
				}
			}
			
			return $this->_params[$key];
		}
		
		return $default;
	}
	
	public function hasParam($key) {
		return array_key_exists($key, $this->_params);
	}
	
	public function removeParam($key) {
		unset($this->_params[$key]);
		return $this;
	}
	
	public function removeParams() {
		$this->_params = [];
		return $this;
	}
	
	public function setParent(CerbAutomationAstNode $parent=null) {
		$this->_parent = $parent;
		return $this;
	}
	
	
	/**
	 * @return CerbAutomationAstNode
	 */
	public function getParent() {
		return $this->_parent;
	}
	
	public function hasChildren() {
		return !empty($this->_children);
	}
	
	public function hasChild($id) {
		return (null !== ($this->getChild($id)));
	}
	
	/**
	 * 
	 * @param integer $id
	 * @return CerbAutomationAstNode|NULL
	 */
	public function getChild($id) {
		foreach($this->_children as $child) {
			if($id == $child->getId())
				return $child;
		}
		
		return null;
	}
	
	/**
	 * 
	 * @param string $suffix
	 * @return CerbAutomationAstNode|NULL
	 */
	public function getChildBySuffix($suffix) {
		foreach($this->_children as $child) {
			if(DevblocksPlatform::strEndsWith($child->getId(), $suffix))
				return $child;
		}
		
		return null;
	}
	
	public function addChild(CerbAutomationAstNode $node) {
		$node->setParent($this);
		$this->_children[] = $node;
		return $this;
	}
	
	/**
	 * 
	 * @return CerbAutomationAstNode[]
	 */
	public function getChildren() {
		return $this->_children;
	}
	
	private function _triggerError($error, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		$return_values = [
			'at' => $this->getId(),
		];
		
		$return_values['error'] = $tpl_builder->build($error, $dict);
		
		$dict->set('__exit', 'error');
		$dict->set('__return', $return_values);
	}
	
	public function activate(DevblocksDictionaryDelegate $dict, array $environment, &$error=null) {
		$node_memory_key = '__state.memory.' . $this->getId();
		
		if(null === ($node_memory = $dict->getKeyPath($node_memory_key, [])))
			$node_memory = [];
		
		$error = null;
		$next_state = null;
		
		$node_classes = [
			'action' => '\Cerb\AutomationBuilder\Node\ActionNode',
			'decision' => '\Cerb\AutomationBuilder\Node\DecisionNode',
			'event' => '\Cerb\AutomationBuilder\Node\EventNode',
			'outcome' => '\Cerb\AutomationBuilder\Node\OutcomeNode',
			'repeat' => '\Cerb\AutomationBuilder\Node\RepeatNode',
			'root' => '\Cerb\AutomationBuilder\Node\RootNode',
			'start' => '\Cerb\AutomationBuilder\Node\EventNode',
		];
		
		$node_type = $this->getType();
		
		if(!array_key_exists($node_type, $node_classes)) {
			return $this->_triggerError(sprintf("Unknown node `%s`", $node_type), $dict);
		}
		
		$node = new $node_classes[$node_type]($this);
		
		if(false === ($next_state = $node->activate($dict, $node_memory, $environment, $error))) {
			return $this->_triggerError($error, $dict);
		}
		
		$dict->setKeyPath($node_memory_key, $node_memory);
		
		$dict->setKeyPath('__state.last', $this->getId());
		$dict->setKeyPath('__state.next', $next_state);
		
		return $next_state;
	}
	
	// [TODO] Make reusable with prompt text
	private function _formatKeyValue(&$k, &$v, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		$annotations = [];
		
		if(false !== strpos($k, '@')) {
			list($k, $ann) = explode('@', $k, 2);
			$annotations = DevblocksPlatform::parseCsvString($ann);
		}
		
		if(is_string($v)) {
			if(in_array('raw', $annotations)) {
				$annotations = array_diff($annotations, ['raw']);
				
				if ($annotations)
					$k .= '@' . implode(',', $annotations);
				
				return true;
				
			} else {
				$value = $tpl_builder->build($v, $dict);
			}
			
			foreach($annotations as $annotation) {
				switch($annotation) {
					case 'base64':
						$value = base64_decode($value);
						break;
						
					case 'bit':
						$value = trim(DevblocksPlatform::strLower($value));
						
						if(0 == strlen($value)) {
							$value = 0;
						} else {
							$value = in_array($value, ['0','false','n','no']) ? 0 : 1;
						}
						break;
						
					case 'bool':
					case 'boolean':
					case 'yesno':
						$value = trim(DevblocksPlatform::strLower($value));
						
						if(0 == strlen($value)) {
							$value = false;
						} else {
							$value = in_array($value, ['0','false','n','no']) ? false : true;
						}
						break;
						
					case 'csv':
						$value = DevblocksPlatform::parseCsvString($value);
						break;
						
					case 'int':
						if(!strstr($v,'E+')) {
							$value = intval($value);
						}
						break;
						
					case 'json':
						$value = json_decode($value, true);
						break;
						
					case 'kata':
						$value = DevblocksPlatform::services()->kata()->parse($value);
						break;
						
					case 'key':
						$key_path = trim($value);
						
						if(false !== strpos($key_path, ':')) {
							$value = $dict->getKeyPath($key_path, null, ':');
						} else {
							$value = $dict->get($key_path);
						}
						break;
					
					case 'list':
						$value = DevblocksPlatform::parseCrlfString($value);
						break;
						
					case 'trim':
						if(is_string($value))
							$value = trim($value);
						break;
				}
			}
			
			$v = $value;
			
		} elseif(is_array($v)) {
			if(in_array('raw', $annotations)) {
				$annotations = array_diff($annotations, ['raw']);
				
				if ($annotations)
					$k .= '@' . implode(',', $annotations);
				
				return true;
			}
			
			$new_v = [];
			foreach($v as $kk => $vv) {
				if(false !== ($this->_formatKeyValue($kk, $vv, $dict))) {
					$new_v[$kk] = $vv;
				}
			}
			$v = $new_v;
		}
		
		return true;
	}
}