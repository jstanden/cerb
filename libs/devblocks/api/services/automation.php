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
						
						if(is_bool($input_value))
							$input_value = $input_value ? 'yes' : 'no';
						
						if(is_string($input_value) && 0 == strlen($input_value))
							$input_value = null;
						
						switch(@$input_data['type']) {
							case 'bool':
								if(is_null($input_value) && array_key_exists('default', $input_data))
									$input_value = $input_data['default'];
								
								if(!is_null($input_value)) {
									$input_value = DevblocksPlatform::services()->string()->toBool($input_value);
									$dict->setKeyPath('inputs.' . $input_key, $input_value);
								}
								
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
								$max_length = intval($input_data['type_options']['max_length'] ?? 1024);
								$is_truncated = DevblocksPlatform::services()->string()->toBool($input_data['type_options']['truncate'] ?? 'yes');
								$input_field_type = $input_field->string()->setMaxLength($max_length)->setTruncation($is_truncated);
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
								$input_field_type = $input_field->url()
									->setMaxLength(2048)
								;
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
					
					if(array_key_exists($input_key, $input_values))
						$dict->setKeyPath('inputs.' . $input_key, $input_values[$input_key]);
					
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
					
					$record_dict = DevblocksDictionaryDelegate::instance([
						'id' => $input_values[$input_key] ?? null,
						'_context' => $input_data['record_type'],
					]);
					
					if(array_key_exists('expand', $input_data)) {
						if(is_string($input_data['expand'])) {
							$input_data['expand'] = DevblocksPlatform::parseCsvString($input_data['expand']);
						}
						
						if(is_array($input_data['expand'])) {
							foreach ($input_data['expand'] as $key)
								$record_dict->get($key);
						}
					}
					
					$dict->setKeyPath('inputs.' . $input_key, $record_dict);
					
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
					
					if(is_array($input_value)) {
						foreach ($input_value as $v) {
							$records[] = DevblocksDictionaryDelegate::instance([
								'id' => $v,
								'_context' => $input_data['record_type'],
							]);
						}
						
						if(array_key_exists('expand', $input_data)) {
							if(is_string($input_data['expand'])) {
								$input_data['expand'] = DevblocksPlatform::parseCsvString($input_data['expand']);
							}
							
							if(is_array($input_data['expand'])) {
								foreach($input_data['expand'] as $key)
									DevblocksDictionaryDelegate::bulkLazyLoad($records, $key);
							}
						}
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
	 * @return DevblocksDictionaryDelegate|false
	 */
	public function executeScript(Model_Automation $automation, array $initial_state=[], &$error=null) {
		$error = null;
		$is_simulate = array_key_exists('__simulate', $initial_state) && $initial_state['__simulate'];
		
		try {
			if(false == ($automation_script = DevblocksPlatform::services()->kata()->parse($automation->script, $error))) {
				if(!$error) {
					if(!$automation_script) {
						$error = "No `start:` node was found";
					} else {
						$error = "Invalid automation script";
					}
				}
				throw new Exception_DevblocksAutomationError($error);
			}
			
			$dict = DevblocksDictionaryDelegate::instance($initial_state);
			
			// On the first run
			if(!$dict->exists('__state')) {
				if(false === $this->_validateInputs($automation_script, $dict, $error))
					throw new Exception_DevblocksAutomationError($error);
			}
			
			// Remove inputs before running
			unset($automation_script['inputs']);
			
			if(false == $automation->execute($dict, $error))
				throw new Exception_DevblocksAutomationError($error);
			
			// Log when we exit in an error state and are not simulating
			if($dict->getKeyPath('__exit') == 'error') {
				if(!$is_simulate) {
					DAO_AutomationLog::create([
						DAO_AutomationLog::LOG_MESSAGE => $dict->getKeyPath('__error.message'),
						DAO_AutomationLog::LOG_LEVEL => 3,
						DAO_AutomationLog::CREATED_AT => time(),
						DAO_AutomationLog::AUTOMATION_NAME => $automation->name ?? '',
						DAO_AutomationLog::AUTOMATION_NODE => $dict->getKeyPath('__error.at'),
					]);
				}
			}
			
			return $dict;
			
		} catch (Exception_DevblocksAutomationError $e) {
			$error = $e->getMessage();
			
			// Only log when this isn't CLI (e.g. ignore unit tests)
			if(php_sapi_name() != 'cli')
				error_log($error);
			
			// Log exceptions
			if(!$is_simulate && class_exists('DAO_AutomationLog')) {
				DAO_AutomationLog::create([
					DAO_AutomationLog::LOG_MESSAGE => $error,
					DAO_AutomationLog::LOG_LEVEL => 3,
					DAO_AutomationLog::CREATED_AT => time(),
					DAO_AutomationLog::AUTOMATION_NAME => $automation->name ?? '',
					DAO_AutomationLog::AUTOMATION_NODE => '',
				]);
			}
			
			return false;
		}
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
	
	public function runAST(Model_Automation $automation, DevblocksDictionaryDelegate &$dict, &$error=null) {
		if(false == ($tree = $automation->getSyntaxTree($error)))
			return false;
		
		$started_at = microtime(true) * 1000;
		
		if(null == ($policy = $automation->getPolicy())) {
			$error = 'Invalid automation policy';
			return false;
		}
		
		$time_limit_ms = $policy->getTimeoutMs();
		$elapsed_ms = 0;
		$is_timed_out = false;
		
		// [TODO] Check if we're given an exit/return/error/await status
		$dict->unset('__exit');
		$dict->unset('__return');
		
		$environment = [
			'debug' => false,
			'state' => $dict->getKeyPath('__state.next', $tree->getId()),
			'state_last' => $dict->getKeyPath('__state.last', null),
		];
		
		$automation->setEnvironment($environment);
		
		// Loop while not terminated
		while($environment['state'] && !$is_timed_out) {
			// [TODO] Return error
			if(null == ($node = $this->_recurseFindNodeId($tree, $environment['state'])))
				return false;
			
			if(false === $node->activate($automation, $dict, $error)) {
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
			
			$elapsed_ms = (microtime(true) * 1000) - $started_at;
			
			if($elapsed_ms > $time_limit_ms) {
				$is_timed_out = true;
				$dict->set('__exit', 'error');
				$dict->set('__error', sprintf('Execution timed out after %dms', $time_limit_ms));
			}
		}
		
		$metrics = DevblocksPlatform::services()->metrics();
		
		if($automation->id) {
			$metrics->increment('cerb.automation.invocations', 1, ['automation_id'=>$automation->id, 'trigger'=>$automation->extension_id]);
			$metrics->increment('cerb.automation.duration', $elapsed_ms, ['automation_id'=>$automation->id, 'trigger'=>$automation->extension_id]);
		}
		
		return true;
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
		list($name,) = array_pad(explode('/', $type, 2), 2, null); // remove aliases
		list($name,) = array_pad(explode('@', $name, 2), 2, null); // remove annotations
		
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
			'while',
		];
		
		$actions = [
			'await',
			'data.query',
			'decrypt.pgp',
			'email.parse',
			'encrypt.pgp',
			'error',
			'file.read',
			'function',
			'http.request',
			'kata.parse',
			'log',
			'log.alert',
			'log.error',
			'log.warn',
			'metric.increment',
			'queue.push',
			'queue.pop',
			'record.create',
			'record.delete',
			'record.get',
			'record.search',
			'record.update',
			'record.upsert',
			'return',
			'set',
			'simulate.error',
			'simulate.success',
			'storage.delete',
			'storage.get',
			'storage.set',
			'var.expand',
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
			$error = sprintf("Unexpected command `%s:`", $path);
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
					$error = sprintf("Unexpected command `%s:`. Expected `start:`", $path);
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
					$error = sprintf("Unexpected command `%s:`. Expected `outcome:`", $path);
					return false;
				}
			}
			
		} elseif ($node_type == 'outcome') {
			$is_decision = 'decision' == $node->getParent()->getNameType();
			
			// Is the parent a decision node?
			if($is_decision) {
				$node->getParent()->removeParam($node->getId());
			}
			
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
					$error = sprintf("Unexpected command `%s:`. Expected `if:` or `then:`", $path);
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
							$error = sprintf("Unexpected command `%s:`", $path);
							return false;
						}
					}
					
					array_pop($states);
					
				} else {
					$path = implode(':', array_merge($states, [$node_name]));
					$error = sprintf("Unexpected command `%s:`. Expected `each:`, `as:`, or `do:`", $path);
					return false;
				}
			}
			
		} elseif ($node_type == 'while') {
			$node->removeParam('do');
			
			foreach($yaml as $type => $child) {
				$node_name = $this->_getNodeFromType($type);
				
				if('if' == $node_name) {
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
							$error = sprintf("Unexpected command `%s:`", $path);
							return false;
						}
					}
					
					array_pop($states);
					
				} else {
					$path = implode(':', array_merge($states, [$node_name]));
					$error = sprintf("Unexpected command `%s:`. Expected `if:` or `do:`", $path);
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
					$error = sprintf("Unexpected command `%s:`", $path);
					return false;
				}
			}
			
		} else {
			$path = implode(':', array_merge($states, [$node_type]));
			$error = sprintf("Unexpected command `%s:`.", $path);
			return false;
		}
	}
	
	private function _findActionEvents(&$params, &$states, &$new_action, $environment, &$error=null) {
		if(!is_array($params))
			return true;
		
		foreach($params as $key => $value) {
			$states[] = $key;
			
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
	private array $_rules;
	
	private array $_callers = [];
	
	private array $_commands = [];
	
	private array $_settings = [
		'time_limit_ms' => 25000,
	];
	
	public function __construct($policy_data) {
		$this->_rules = [];
		
		if(is_string($policy_data)) {
			$error = null;
			
			if(false == ($policy_data = DevblocksPlatform::services()->kata()->parse($policy_data, $error)))
				$policy_data = [];
		}
		
		if(!is_array($policy_data))
			return false;
		
		if(array_key_exists('settings', $policy_data) && is_iterable($policy_data['settings'])) {
			$time_limit_ms = $policy_data['settings']['time_limit_ms'] ?? null;
			
			if($time_limit_ms && is_numeric($time_limit_ms)) {
				$this->_settings['time_limit_ms'] = DevblocksPlatform::intClamp($time_limit_ms, 0, 120000); 
			}
		}
		
		if(array_key_exists('callers', $policy_data) && is_iterable($policy_data['callers'])) {
			foreach($policy_data['callers'] as $caller => $rules) {
				$this->_callers[$caller] = [];
				
				if(!is_array($rules))
					continue;
				
				foreach($rules as $rule_key => $rule_data) {
					list($rule_type, $rule_annotations) = array_pad(explode('@', $rule_key, 2), 2, null);
					list($rule_type, $rule_id) = array_pad(explode('/', $rule_type, 2), 2, null);
					
					$rule_type = DevblocksPlatform::strLower($rule_type);
					
					if(!in_array($rule_type, ['allow', 'deny']))
						continue;
					
					if(!$rule_id)
						$rule_id = uniqid();
					
					$this->_callers[$caller][$rule_id] = [
						'key' => $rule_key . (!$rule_annotations ? '@bool' : ''),
						'type' => $rule_type,
						'id' => $rule_id,
						'value' => $rule_data,
					];
				}
			}
		}
		
		if(array_key_exists('commands', $policy_data) && is_iterable($policy_data['commands'])) {
			foreach($policy_data['commands'] as $command => $rules) {
				$this->_commands[$command] = [];
				
				if(!is_array($rules))
					continue;
				
				foreach($rules as $rule_key => $rule_data) {
					list($rule_type, $rule_annotations) = array_pad(explode('@', $rule_key, 2), 2, null);
					list($rule_type, $rule_id) = array_pad(explode('/', $rule_type, 2), 2, null);
					
					$rule_type = DevblocksPlatform::strLower($rule_type);
					
					if(!in_array($rule_type, ['allow', 'deny']))
						continue;
					
					if(!$rule_id)
						$rule_id = uniqid();
					
					$this->_commands[$command][$rule_id] = [
						'key' => $rule_key . (!$rule_annotations ? '@bool' : ''),
						'type' => $rule_type,
						'id' => $rule_id,
						'value' => $rule_data,
					];
				}
			}
		}
		
		return $this;
	}
	
	public function getTimeoutMs() {
		return $this->_settings['time_limit_ms'] ?? 25000;
	}
	
	public function isCallerAllowed($caller_name, DevblocksDictionaryDelegate $dict) {
		$rules = [];
		
		// We're not restricting the caller at all
		if(!$this->_callers)
			return true;
		
		if(array_key_exists($caller_name, $this->_callers))
			$rules = $this->_callers[$caller_name];
		
		if(array_key_exists('all', $this->_callers))
			$rules = array_merge($rules, $this->_callers['all']);
		
		foreach($rules as $rule_data) {
			$rule = [
				$rule_data['key'] => $rule_data['value'],
			];
			$rule = DevblocksPlatform::services()->kata()->formatTree($rule, $dict);
			
			if(array_key_exists('allow', $rule) && $rule['allow'])
				return true;
			
			if(array_key_exists('deny', $rule) && $rule['deny'])
				return false;
		}
		
		return false;
	}
	
	public function isCommandAllowed($node_name, DevblocksDictionaryDelegate $dict)  {
		$rules = [];
		
		if(array_key_exists($node_name, $this->_commands))
			$rules = $this->_commands[$node_name];
		
		if(array_key_exists('all', $this->_commands))
			$rules = array_merge($rules, $this->_commands['all']);
		
		foreach($rules as $rule_data) {
			$rule = [
				$rule_data['key'] => $rule_data['value'],
			];
			$rule = DevblocksPlatform::services()->kata()->formatTree($rule, $dict);
			
			if(!is_array($rule))
				return false;
			
			list($rule_type,) = array_pad(explode('/', DevblocksPlatform::strLower(key($rule)), 2), 2, null);
			
			if('allow' == $rule_type && current($rule))
				return true;
			
			if('deny' == $rule_type && current($rule))
				return false;
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
	
	public function jsonSerialize() : array {
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
			if(false !== ($this->formatKeyValue($k, $v, $dict)))
				if(!is_null($k))
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
						if(false !== ($this->formatKeyValue($k, $v, $dict)))
							if(!is_null($k))
								$return_values[$k] = $v;
					
					return $return_values;
					
				} else if (is_string($this->_params[$key])) {
					$v = $this->_params[$key];
					
					if(false !== ($this->formatKeyValue($key, $v, $dict))) {
						if(!is_null($key)) {
							return $v;
						} else {
							return $default;
						}
					}
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
	
	/**
	 * @param string $type
	 * @return CerbAutomationAstNode|null
	 */
	public function getAncestorByType(string $type) {
		$p = $this->_parent;
		
		while($p) {
			if($p->getNameType() == $type)
				return $p;
			
			$p = $p->_parent;
		}
		
		return null;
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
		
		$error_values = [
			'at' => $this->getId(),
			'message' => $tpl_builder->build($error, $dict),
		];
		
		$dict->set('__exit', 'error');
		$dict->set('__error', $error_values);
	}
	
	public function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, &$error=null) {
		$node_memory_key = '__state|memory|' . $this->getId();
		
		if(null === ($node_memory = $dict->getKeyPath($node_memory_key, [], '|')))
			$node_memory = [];
		
		$error = null;
		
		$node_classes = [
			'action' => '\Cerb\AutomationBuilder\Node\ActionNode',
			'decision' => '\Cerb\AutomationBuilder\Node\DecisionNode',
			'event' => '\Cerb\AutomationBuilder\Node\EventNode',
			'outcome' => '\Cerb\AutomationBuilder\Node\OutcomeNode',
			'repeat' => '\Cerb\AutomationBuilder\Node\RepeatNode',
			'root' => '\Cerb\AutomationBuilder\Node\RootNode',
			'start' => '\Cerb\AutomationBuilder\Node\EventNode',
			'while' => '\Cerb\AutomationBuilder\Node\WhileNode',
		];
		
		$node_type = $this->getType();
		
		if(!array_key_exists($node_type, $node_classes)) {
			$this->_triggerError(sprintf("Unknown node `%s`", $node_type), $dict);
			return false;
		}
		
		$node = new $node_classes[$node_type]($this);
		
		if(false === ($next_state = $node->activate($automation, $dict, $node_memory, $error))) {
			$this->_triggerError($error, $dict);
			return false;
		}
		
		$dict->setKeyPath($node_memory_key, $node_memory, '|');
		
		$dict->setKeyPath('__state.last', $this->getId());
		$dict->setKeyPath('__state.next', $next_state);
		
		return $next_state;
	}
	
	// [TODO] Make reusable with prompt text
	function formatKeyValue(&$k, &$v, DevblocksDictionaryDelegate $dict) {
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
							$value = DevblocksPlatform::services()->string()->toBool($value) ? 1 : 0;
						}
						break;
						
					case 'bool':
						$value = trim(DevblocksPlatform::strLower($value));
						
						if(0 == strlen($value)) {
							$value = false;
						} else {
							$value = DevblocksPlatform::services()->string()->toBool($value);
						}
						break;
						
					case 'csv':
						$value = DevblocksPlatform::parseCsvString($value);
						break;
					
					case 'date':
						$value = DevblocksPlatform::services()->string()->toDate($value);
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
						
					case 'optional':
						if(is_null($value) || (is_string($value) && 0 == strlen($value))) {
							$k = null;
							return true;
						}
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
				if(false !== ($this->formatKeyValue($kk, $vv, $dict))) {
					if(!is_null($kk))
						$new_v[$kk] = $vv;
				}
			}
			$v = $new_v;
		}
		
		return true;
	}
	
	public function getName() {
		if(false == ($id = $this->getId()))
			return null;
		
		$parts = explode(':', $id);
		
		return array_pop($parts);
	}
	
	public function getNameType() {
		return DevblocksPlatform::services()->string()->strBefore($this->getName(), '/');		
	}
	
	public function getNameId() {
		return DevblocksPlatform::services()->string()->strAfter($this->getName(), '/');		
	}
}